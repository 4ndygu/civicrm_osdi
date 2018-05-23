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
    if (!isset($params["page"])) {
        $offset = 0;
    } else {
        $offset = $params["page"] * 25;
    }

    if (!isset($params["limit"])) {
        $limit = 25;
    } else {
        $limit = $params["limit"];
    }

    $hateoas = HateoasBuilder::create()->build();

    $result = NULL;
    if (strtolower($params["object"]) == "contact") {
        // dump the contacts

        $result = NULL;
        $singleuser = false;
        if (isset($params["id"])) {
            $result = civicrm_api3('Contact', 'get', array(
                'contact_type' => "Individual",
                'sequential' => 1,
                'id' => $params["id"],
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

        $apikey = (isset($params["apikey"]) ? $params["apikey"] : "apikey");
        $sitekey = (isset($params["sitekey"]) ? $params["sitekey"] : "sitekey");

        $response["properties"]["page"] = $offset / 25;
        $response["properties"]["per_page"] = $result["count"];

        //nextPage
        $config = CRM_Core_Config::singleton();
        $paramscopy = $params;
        $response["links"]["self"] = $config->resourceBase . "extern/rest.php?entity=Exporter&action=export&api_key=" . $apikey . "&key=" . $sitekey . "&json=" . json_encode($paramscopy);
        if ($singleuser) {
            $paramscopy["page"]++;
            $response["links"]["next"] = $config->resourceBase . "extern/rest.php?entity=Exporter&action=export&api_key=" . $apikey . "&key=" . $sitekey . "&json=" . json_encode($paramscopy);
        }
        $response["links"]["osdi:people"] = array();


        $response["embedded"]["osdi:people"] = array();
        foreach ($result["values"] as $contact) {
            // generate the link give nthe ID first
            $newparams = $params;
            $newparams["limit"] = 1;
            $newparams["id"] = $contact["id"];
            $contactURL = $config->resourceBase . "extern/rest.php?entity=Exporter&action=export&api_key=" . $apikey . "&key=" . $sitekey . "&json=" . json_encode($newparams);
            $response["links"]["osdi:people"][] = $contactURL;

            $newcontact = array();
            $newcontact["links"]["self"]["href"] = $contactURL;
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
            $newcontact["birthdate"]["month"] = $tokenized_bday[2];
            $newcontact["birthdate"]["day"] = $tokenized_bday[1];
            $newcontact["birthdate"]["year"] = $tokenized_bday[0];
 
            $newcontact["preferred_language"] = $contact["preferred_language"];

            $optionalparams = array("modified_date", "created_date", "identifiers");
            foreach ($optionalparams as $param) {
                if (isset($newcontact[$param])) {        
                    $newcontact[$param] = $contact[$param];
                }
            }

            $response["embedded"]["osdi:people"][] = $newcontact;
        }
    }

    return civicrm_api3_create_success($response, $params, 'Exporter', 'export');
}

