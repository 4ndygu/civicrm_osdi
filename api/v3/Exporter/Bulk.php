<?php

/**
 * @file
 */

use GuzzleHttp\Client;

include "Export.php";

/**
 * Exporter.Bulk API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec
 *   description of fields supported by this API call.
 *
 * @return void
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_exporter_Bulk_spec(&$spec) {
  $spec['key']['api.required'] = 1;
  $spec['endpoint']['api.required'] = 1;
  $spec['endpoint_root']['api.required'] = 1;
  $spec['allow_restart']['api.required'] = 0;
  $spec['group']['api.required'] = 0;
  $spec['updatejob']['api.required'] = 0;
  $spec['updateendpoint']['api.required'] = 0;
  $spec['required']['api.required'] = 0;
  $spec['zone']['api.required'] = 0;
}

/**
 * Exporter.Bulk API.
 *
 * @param array $params
 *
 * @return array API result descriptor
 *
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 *
 * @throws API_Exception
 */
function civicrm_api3_exporter_Bulk($params) {
  // Init return values.
  $returnValues = array();
  $returnValues["results"] = array();
  $initcode = -100;
  $completedcode = -1;

  // Grab current date.
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

  $allow_restart = FALSE;
  if (isset($params["allow_restart"])) {
    $allow_restart = ($params["allow_restart"] == 1) ? TRUE : FALSE;
  }

  // Handle validation.
  if (!isset($params["required"])) {
    $params["required"] = "";
  }

  // Valkidate zone.
  $zone = 0;
  if (isset($params["zone"])) {
    $zone = $params["zone"];
  }

  $client = new Client([
    'headers' => [
      'OSDI-API-Token' => $params["key"],
      'Object' => 'Contact',
      'Content-Type' => 'application/json',
    ],
  ]);

  $count = 0;
  $offset = 0;

  // Check if group is there.
  $group = -1;
  if (isset($params["group"])) {
    $group = $params["group"];
  }
  if ($group == "") {
    $group = -1;
  }

  // Use a sha1 of the key with the endpoint to generate our identifier.
  // change the hash to the URL if Civi, key if actionnetwork
  $hash = "CIVI_ID_actionnetwork_" . sha1($params["key"]);
  if (strpos($params["endpoint_root"], "actionnetwork.org") === FALSE) {
    $hash = "CIVI_ID_" . sha1($params["endpoint_root"]);
  }
  $second_key = $params["endpoint"] . $hash;

  if (isset($params["updatejob"])) {
    if ($params["updatejob"] == 1) {
      $second_key = $second_key . "update";
    }
  }

  // Check if we've run this before.
  $exporters_offset = Civi::settings()->get("exporters_offset");
  if ($exporters_offset != NULL) {
    if (isset($exporters_offset[$second_key])) {
      $offset = $exporters_offset[$second_key];
    }
    else {
      $exporters_offset[$second_key] = 0;
      Civi::settings()->set("exporters_offset", $exporters_offset);

      $returnValues["count"] = $initcode;
      return civicrm_api3_create_success($returnValues, $params, 'Exporter', 'Bulk');
    }
  }
  else {
    $exporters_offset = array();
    $exporters_offset[$second_key] = 0;
    Civi::settings()->set("exporters_offset", $exporters_offset);

    $returnValues["count"] = $initcode;
    return civicrm_api3_create_success($returnValues, $params, 'Exporter', 'Bulk');
  }

  if ($offset === "DONE") {
    // If RESTART is on, or if this is an update job, restart the job and return.
    if ($params["updatejob"] == 1 or $allow_restart) {
      $exporters_offset[$second_key] = 0;
      Civi::settings()->set("exporters_offset", $exporters_offset);

      $returnValues["count"] = $initcode;
      return civicrm_api3_create_success($returnValues, $params, 'Exporter', 'Bulk');
    }

    $returnValues["count"] = $completedcode;
    return civicrm_api3_create_success($returnValues, $params, 'Exporter', 'Bulk');
  }

  $result = NULL;
  $isgroup = FALSE;
  if ($group == -1) {
    $result = civicrm_api3('Contact', 'get', array(
      'contact_type' => "Individual",
      'sequential' => 1,
      'modified_date' => array('>=' => $date),
      'options' => array('offset' => $offset, 'limit' => 100),
    ));
  }
  else {
    $result = civicrm_api3('GroupContact', 'get', array(
      'sequential' => 1,
      'return' => array('contact_id'),
      'group_id' => $group,
      'status' => "Added",
      'options' => array('offset' => $offset, 'limit' => 100),
    ));
    $isgroup = TRUE;
  }

  if (sizeof($result["values"]) != 0) {
    foreach ($result["values"] as $item) {
      // Generate the actual contact if is gruop.
      $contact = NULL;
      if ($isgroup) {
        $id = $item["contact_id"];
        $response = civicrm_api3('Contact', 'get', array(
          'modified_date' => array('>=' => $date),
          'sequential' => 1,
          'id' => $id,
        ));
        $result_response = civicrm_api3('Contact', 'get', array(
          'modified_date' => array('>=' => $date),
          'sequential' => 1,
          'return' => ['modified_date'],
          'id' => $id,
        ));
        if (sizeof($response["values"]) == 0) {
          continue;
        }

        $contact = $response["values"][0];
        $contact["modified_date"] = $result_response["values"][0]["modified_date"];
      }
      else {
        $contact = $item;
      }

      $newer = TRUE;
      if (isset($params["updateendpoint"])) {
        // this used to only be for update. I now want to check if this is the newest contact in all scenarios.
//        if ($params["updatejob"] == 1) {
//          $newer = contact_newer($contact, $params["updateendpoint"], $params["key"], $zone);
//        }
        $newer = contact_newer($contact, $params["updateendpoint"], $params["key"], $zone);
      }

      $url = "";
      if (strpos($params["endpoint"], "actionnetwork") !== FALSE) {
        $url = "actionnetwork";
      }
      else {
        $url = $params["endpoint"];
      }

      if (validate_array_data($contact, $params["required"]) and $newer) {
        $resultid = civicrm_api3('Mapping', 'get', array(
          'name' => "OSDI_" . $url,
        ));

        $fieldresults = civicrm_api3('MappingField', 'get', array(
          'mapping_id' => $resultid["id"],
          'sequential' => 1,
          'options' => ['limit' => 0],
        ));

        $fieldmapping = array();
        foreach ($fieldresults["values"] as $fieldresult) {
          $fieldmapping[$fieldresult["name"]] = $fieldresult["value"];
        }

        $newcontact = convertContactOSDI($contact, $fieldmapping);

        $body = array();
        $body["person"] = $newcontact;

        $result = $client->post($params["endpoint"], [
          "body" => json_encode($body),
        ]);

        $returnValues["results"][] = $result->getBody()->getContents();
        $count++;
      }
      else {
        $errorarray = array();
        $errorarray["newer"] = $newer;
        $errorarray["name"] = $contact["first_name"] . ' ' . $contact["last_name"];
        $errorarray["valid"] = validate_array_data($contact, $params["required"]);
        $returnValues["results"][] = $errorarray;
      }
    }

    $offset = $offset + 100;
    $exporters_offset[$second_key] = $offset;
    Civi::settings()->set("exporters_offset", $exporters_offset);

  }
  else {
    $exporters_offset[$second_key] = "DONE";
    Civi::settings()->set("exporters_offset", $exporters_offset);

    $count = $completedcode;
  }

  $returnValues["count"] = $count;

  return civicrm_api3_create_success($returnValues, $params, 'Exporter', 'Bulk');
}

/**
 *
 */
function contact_newer($contact, $updateendpoint, $key, $zone) {
  $cividate = $contact["modified_date"];

  $query_string = $updateendpoint . "?filter=email_address eq '" . $contact["email"] . "'";

  $raw_client = new Client();
  $response = $raw_client->request('GET', $query_string, [
    'headers' => [
      'OSDI-API-Token' => $key,
      'Object' => 'Contact',
      'Content-Type' => "application/json",
    ],
  ]);

  $response_string = $response->getBody()->getContents();
  $data = json_decode($response_string, TRUE);

  if (sizeof($data["_embedded"]["osdi:people"]) == 0) {
    return TRUE;
  }

  $newdate = $data["_embedded"]["osdi:people"][0]["modified_date"];

  $modified_date = strtotime($cividate) - 3600 * $zone;
  return $modified_date > strtotime($newdate);
}

/**
 *
 */
function validate_array_data($person, $filter = NULL) {
  $checks = array("first_name", "last_name", "email");
  foreach ($checks as $check) {
    if (!array_key_exists($check, $person)) {
      return FALSE;
    }
    else {
      if ($person[$check] == NULL or $person[$check] == "") {
        return FALSE;
      }
    }
  }

  $filters = preg_split('/\s+/', $filter, -1, PREG_SPLIT_NO_EMPTY);

  foreach ($filters as $single_filter) {
    if (!array_key_exists($single_filter, $person)) {
      return FALSE;
    }
    elseif (ctype_space($person[$single_filter])) {
      return FALSE;
    }
    elseif ($person[$single_filter] == NULL or $person[$single_filter] == "") {
      return FALSE;
    }
  }

  return TRUE;
}
