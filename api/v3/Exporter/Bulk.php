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
  $spec['group']['api.required'] = 0;
  $spec['updatejob']['api.required'] = 0;
  $spec['updateendpoint']['api.required'] = 0;
  $spec['required']['api.required'] = 0;
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
  // init return values
  $returnValues = array();
  $returnValues["results"] = array();
  $initcode = -100;

  // grab current date
  $date = "1980-01-01";
  if (isset($params["updatejob"])) { 
      $date = ($params["updatejob"] == 1) ? date('Y-m-d', time()) : "1980-01-01";

      if (!isset($params["updateendpoint"])) {
          $returnValues["message"] = "Please provide an update endpoint.";
          return civicrm_api3_create_success($returnValues, $params, 'Exporter', 'Bulk');
      }

      if ($params["updateendpoint"] == "") {
          $returnValues["message"] = "Please provide an update endpoint.";
          return civicrm_api3_create_success($returnValues, $params, 'Exporter', 'Bulk');
      }
  }

  // handle validation
  if (!isset($params["required"])) {
      $params["required"] = "";
  }

  $client = new \GuzzleHttp\Client([
      'headers' => [
          'OSDI-API-Token' => $params["key"],
          'Content-Type' => 'application/json'
      ]
  ]);

  $count = 0;
  $offset = 0;
  
  // check if group is there
  $group = -1;
  if (isset($params["group"])) $group = $params["group"];
  if ($params["group"] == "") $group = -1;

  // use a sha1 of the key with the endpoint to generate our identifier
  $hash = sha1($params["key"]);
  $second_key = $params["endpoint"] . $hash;

  if (isset($params["updatejob"])) {
      if ($params["updatejob"] == 1) $second_key = $second_key . "update";
  }

  // check if we've run this before
  if (isset($_SESSION["exporters_offset"])) {
      if (isset($_SESSION["exporters_offset"][$second_key])) {
          $offset = $_SESSION["exporters_offset"][$second_key];
      } else {
          $_SESSION["exporters_offset"][$second_key] = 0;

          $returnValues["count"] = $initcode;
          return civicrm_api3_create_success($returnValues, $params, 'Exporter', 'Bulk');
     }
  } else {
      $_SESSION["exporters_offset"] = array();
	  $_SESSION["exporters_offset"][$second_key] = 0;

      $returnValues["count"] = $initcode;
      return civicrm_api3_create_success($returnValues, $params, 'Exporter', 'Bulk');
  }

  $result = NULL;
  $isgroup = false;
  if ($group == -1) { 
      $result = civicrm_api3('Contact', 'get', array(
	      'contact_type' => "Individual",
	      'sequential' => 1,
          'modified_date' => array('>=' => $date),
	      'options' => array('offset' => $offset, 'limit' => 100)
      ));
  } else {
      $result = civicrm_api3('GroupContact', 'get', array(
          'sequential' => 1,
          'return' => array('contact_id'),
          'group_id' => $group,
          'status' => "Added",
	      'options' => array('offset' => $offset, 'limit' => 100)
      ));
      $isgroup = true;
  }

  if (sizeof($result["values"]) != 0) {
      foreach ($result["values"] as $item) {

          // generate the actual contact if is gruop
          $contact = NULL;
          if ($isgroup) {
              $id = $item["contact_id"];
              $response = civicrm_api3('Contact', 'get', array(
                  'sequential' => 1,
	              'id' => $id
              ));
              $result_response = civicrm_api3('Contact', 'get', array(
                  'sequential' => 1,
                  'return' => ['modified_date'],
	              'id' => $id
              ));
              /*$response = civicrm_api3('Contact', 'get', array(
                  'sequential' => 1,
                  'modified_date' => array('>=' => $date),
	              'id' => $id
              ));*/
              if (sizeof($response["values"]) == 0) {
                  continue;
              }

              $contact = $response["values"][0];
              $contact["modified_date"] = $result_response["values"][0]["modified_date"];
          } else {
              $contact = $item;
          }

          $newer = True;
          if (isset($params["updateendpoint"])) {
              if ($params["updateendpoint"] == 1) $newer = contact_newer($contact, $params["updateendpoint"], $params["key"]);
          }
          if (validate_array_data($contact, $params["required"]) and $newer) {
              $newcontact = convertContactOSDI($contact);
              $body = array();
              $body["person"] = $newcontact;

              $result = $client->post($params["endpoint"], [
                  "body" => json_encode($body)
              ]);
  
              $returnValues["results"][] = $result;
              $count++;
          }
      }

      $offset = $offset + 100;
      $_SESSION["exporters_offset"][$second_key] = $offset;
  } else {
      unset($_SESSION["exporters_offset"][$second_key]);
      $count = -1;
  }

  $returnValues["count"] = $count;

  return civicrm_api3_create_success($returnValues, $params, 'Exporter', 'Bulk');
}

function contact_newer($contact, $updateendpoint, $key) {
    $cividate = $contact["modified_date"];

    $query_string = $updateendpoint . "?filter=email_address eq '" . $contact["email"] . "'";

    $raw_client = new GuzzleHttp\Client();
    $response = $raw_client->request('GET', $query_string, [
        'headers' => [
            'OSDI-API-Token' => $key,
            'Content-Type' => "application/json"
        ]
    ]);

    $response_string = $response->getBody()->getContents();
    $data = json_decode($response_string, true);

    if (sizeof($data["_embedded"]["osdi:people"]) == 0) {
        return False;
    }

    $newdate = $data["_embedded"]["osdi:people"][0]["modified_date"];
    return strtotime($cividate) > strtotime($newdate);
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
