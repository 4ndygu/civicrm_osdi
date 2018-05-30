<?php
use CRM_Osdi_ExtensionUtil as E;

include "Export.php";

/**
 * Exporter.Bulk API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_exporter_Bulk_spec(&$spec) {
  $spec['key']['api.required'] = 1;
  $spec['endpoint']['api.required'] = 1;
}

/**
 * Exporter.Bulk API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_exporter_Bulk($params) {
  $client = new \GuzzleHttp\Client([
      'headers' => [
          'OSDI-API-Token' => $params["key"],
          'Content-Type' => 'application/json'
      ]
  ]);
 
  $count = 0;
  while (1) {
      $result = civicrm_api3('Contact', 'get', array(
          'contact_type' => "Individual",
          'sequential' => 1,
          'options' => array('offset' => 0, 'limit' => 100)
      ));
    
      if (sizeof($result["values"]) == 0) break;
 
      foreach ($result["values"] as $contact) {
          if (validate_array_data($contact)) {
              $newcontact = convertContactOSDI($contact);
              $body = array();
              $body["person"] = $newcontact;
              $res = $client->post($params["endpoint"], [
                  "body" => json_encode($body)
              ]);
              $count++;
              break;
          }
      }
      break;
  }
  $returnValues = array();
  $returnValues["count"] = $count;

  return civicrm_api3_create_success($returnValues, $params, 'Exporter', 'Bulk');
}

function validate_array_data($person, $filter = NULL) {
    $checks = array("first_name", "last_name", "email");
    foreach ($checks as $check) {
        if (!array_key_exists($check, $person)) {
            return False;
        } else {
            if ($person[$check] == NULL or $person[$check] == "") return False;
        }
    }

    $filters = preg_split('/\s+/', $filter, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($filters as $single_filter) {
        if (!array_key_exists($single_filter, $person)) {
            return False;
        } else if (ctype_space($properties[$single_filter])) {
            return False;
        } else if ($person[$single_filter] == NULL or $person[$single_filter] == "") {
            return False;
        }
    }

    return True;
}
