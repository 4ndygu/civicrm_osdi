<?php
use CRM_Osdi_ExtensionUtil as E;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Hateoas\HateoasBuilder;

/**
 * Exporter.Export API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_exporter_Export_spec(&$spec) {
  $spec['object']['api.required'] = 1;
  $spec['apikey']['api.required'] = 0;
  $spec['sitekey']['api.required'] = 0;
  $spec['group']['api.required'] = 0;
}

/**
 * Exporter.Export API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_exporter_Export($params) {
    //TODO: convert to tertiary operators
	$offset = 0;
    if (!array_key_exists("page", $params)) {
        $params["page"] = 0;
    } else {
        $offset = $params["page"] * 25;
    }

    if (!array_key_exists("limit", $params)) {
        $limit = 25;
    } else {
        $limit = $params["limit"];
    }

    $result = NULL;
    if (strtolower($params["object"]) == "contact") {
        // dump the contacts

        $result = NULL;
        $singleuser = false;
        if (array_key_exists("id", $params)) {
            $result = civicrm_api3('Contact', 'get', array(
                'contact_type' => "Individual",
                'sequential' => 1,
                'id' => $params["id"],
                'first_name' => array('IS NOT NULL' => 1),
                'last_name' => array('IS NOT NULL' => 1),
                'email' => array('IS NOT NULL' => 1),
                'options' => array("offset" => $offset, "limit" => $limit)
            ));
            $singleuser = true;
        } else {
            $result = civicrm_api3('Contact', 'get', array(
                'contact_type' => "Individual",
                'sequential' => 1,
                'options' => array("offset" => $offset, "limit" => $limit)
            ));
        }

        $apikey = (array_key_exists("apikey", $params) ? $params["apikey"] : "apikey");
        $sitekey = (array_key_exists("sitekey", $params) ? $params["sitekey"] : "sitekey");

        $response["properties"] = array();
        $response["properties"]["page"] = $offset / 25;
        $response["properties"]["per_page"] = $result["count"];
        //nextPage
        $config = CRM_Core_Config::singleton();
        $paramscopy = $params;
        $response["_links"]["self"] = CRM_Utils_System::url("civicrm/osdi/response", URLformat($paramscopy), TRUE, NULL, FALSE, TRUE);
        if (!$singleuser) {
            $paramscopy["page"]++;
			$response["_links"]["next"] = CRM_Utils_System::url("civicrm/osdi/response", URLformat($paramscopy), TRUE, NULL, FALSE, TRUE);
        }
        $response["_links"]["osdi:people"] = array();

        $response["embedded"]["osdi:people"] = array();
        foreach ($result["values"] as $contact) {
            // generate the link give nthe ID first
            $newparams = $params;
            $newparams["limit"] = 1;
            $newparams["id"] = $contact["id"];
			$contactURL = CRM_Utils_System::url("civicrm/osdi/response", URLformat($newparams), TRUE, NULL, FALSE, TRUE);
            $response["_links"]["osdi:people"][] = $contactURL;
 
            $newcontact = convertContactOSDI($contact);

            $newcontact["_links"]["self"]["href"] = $contactURL;

            $response["embedded"]["osdi:people"][] = $newcontact;
        }
    }

    return civicrm_api3_create_success($response, $params, 'Exporter', 'export');
}

function URLformat($params) {

    $finalstring = "";

    foreach ($params as $key => $value) {
        $finalstring = $finalstring . "&" . $key . "=" . $value;
    }
    return $finalstring;
}

function convertContactOSDI($contact) {
	$newcontact = array();
	$newcontact["family_name"] = $contact["last_name"];
	$newcontact["given_name"] = $contact["first_name"];
	$newcontact["additional_name"] = $contact["middle_name"];
	$newcontact["honorific_prefix"] = $contact["prefix_id"];
	$newcontact["honorific_suffix"] = $contact["suffix_id"];
	$newcontact["gender_id"] = $contact["gender_id"];
	$newcontact["employer"] = $contact["current_employer"];

	$newcontact["email_addresses"][0]["address"] = $contact["email"];
	$newcontact["email_addresses"][0]["primary"] = True;
	 
	$newcontact["postal_addresses"][0]["primary"] = True;
	$newcontact["postal_addresses"][0]["address_lines"][0] = $contact["street_address"];
	$newcontact["postal_addresses"][0]["locality"] = $contact["city"];
	$newcontact["postal_addresses"][0]["region"] = $contact["state_province_name"];
	$newcontact["postal_addresses"][0]["country"] = $contact["country"];
	$newcontact["postal_addresses"][0]["postal_code"] = $contact["postal_code"] . $contact["postal_code_suffix"];

	$newcontact["phone_numbers"][0] = array(
		"primary" => True,
		"number" => $contact["phone"]
	);
	$newcontact["phone_numbers"][0]["do_not_call"] = $contact["do_not_phone"];

	$tokenized_bday = explode("-", $contact["birth_date"]);
    if (sizeof($tokenized_bday) == 3) {
        $newcontact["birthdate"]["month"] = $tokenized_bday[2];
        $newcontact["birthdate"]["day"] = $tokenized_bday[1];
        $newcontact["birthdate"]["year"] = $tokenized_bday[0];
    }

	$newcontact["preferred_language"] = $contact["preferred_language"];

	$optionalparams = array("modified_date", "created_date", "identifiers");
	foreach ($optionalparams as $param) {
		if (isset($newcontact[$param])) {        
			$newcontact[$param] = $contact[$param];
		}
	}

    // grab custom fields in group
    $resultfields = civicrm_api3('CustomField', 'get', array(
        'sequential' => 1,
        'custom_group_id' => $_SESSION["OSDIGROUPID"]
    ));

    // load all custom fields, add yourself too
    $customparams = array();
    $customfields = array();
    $customparams["id"] = $contact["contact_id"];
    $key = sha1(CRM_Utils_System::url("civicrm"));
    $selffound = False;

    foreach ($resultfields["values"] as $custom_field) {
        $customfields[] = "custom_" . $custom_field["id"]; 
        if ($custom_field["name"] == $key) $selffound = True;
    }
    $customparams["return"] = $customfields;

    $newcontact["custom_fields"] = array();
    if (sizeof($customparams["return"]) != 0) {
        $result = civicrm_api3('Contact', 'get', $customparams);

        foreach ($resultfields["values"] as $custom_field) {
            $newcontact["custom_fields"][$custom_field["name"]] 
                = $result["values"][0]["custom_" . $custom_field["id"]];
        }
    }

    var_dump($_SESSION["OSDIGROUPID"]);
    // load yourself into the custom fields
    if (!$selffound) {
        $fieldresult = civicrm_api3('CustomField', 'create', array(
            'custom_group_id' => $_SESSION["OSDIGROUPID"],
            'label' => $key
        ));
    }

    // add this to newcountacts
    $newcontact["custom_fields"][$key] = $result["id"];
    var_dump($newcontact["custom_fields"]);

    return $newcontact;
}
