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
        $limit = 0;
    } else {
        $limit = $params["limit"];
    }

    $hateoas = HateoasBuilder::create()->build();

    $result = NULL;
    if (strtolower($params["object"]) == "contact") {
        // dump the contacts
        $result = civicrm_api3('Contact', 'get', array(
            'contact_type' => "Individual",
            'sequential' => 1,
            'options' => array("offset" => $offset, "limit" => $limit)
        ));

        $response["properties"]["page"] = $offset / 25;
        $response["properties"]["per_page"] = $result["count"];

        //$response["links"]["next"] = CRM.config.resourceBase . "extern/rest.php?entity=Exporter&action=export&api_key=userkey&key=sitekey&json=" . JSON

        $response["embedded"]["osdi:people"] = array();
        foreach ($result["values"] as $contact) {
            var_dump($contact);
            $newcontact = array();
            $newcontact["family_name"] = $contact["last_name"];
            $newcontact["given_name"] = $contact["first_name"];
            $newcontact["email_addresses"][0]["address"] = $contact["email"];
            $newcontact["email_addresses"][0]["primary"] = True;
 
             
            $newcontact["postal_addresses"][0]["primary"] = True;
            $newcontact["postal_addresses"][0]["address_lines"][0] = $contact["street_address"];

            $response["embedded"]["osdi:people"][] = $newcontact;
        }
    }

    var_dump($result);

    return civicrm_api3_create_success($response, $params, 'Exporter', 'export');
}
