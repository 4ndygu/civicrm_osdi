<?php
use CRM_Osdi_ExtensionUtil as E;

/**
 * OSDIJob.Add API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_o_s_d_i_job_Add_spec(&$spec) {
  $spec['name']['api.required'] = 1;
  $spec['resource']['api.required'] = 1;
  $spec['rootendpoint']['api.required'] = 1;
  $spec['signupendpoint']['api.required'] = 1;
  $spec['peopleendpoint']['api.required'] = 1;
  $spec['groupid']['api.required'] = 0;
  $spec['ruleid']['api.required'] = 0;
  $spec['reqfields']['api.required'] = 0;
  $spec['syncconfig']['api.required'] = 1;
  $spec['timezone']['api.required'] = 1;
  $spec['key']['api.required'] = 1;
  $spec['edit']['api.required'] = 1;
}

/**
 * OSDIJob.Add API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_o_s_d_i_job_Add($params) {
  $returnValues = array();
  $params["name"] = htmlspecialchars($params["name"]);

  if ($params["name"] == "") {
    $returnValues["error_message"] = "Name cannot be empty.";
    return civicrm_api3_create_success($returnValues, $params, 'OSDIJob', 'Add');
  }

  //validate group and rule
  if (isset($params["groupid"])) {
    $validgroup = False;

    if ($params["groupid"] == "") $validgroup = True;
    else {
      $groupresult = civicrm_api3("Group", "get", [
        "sequential" => 1,
        "id" => $params["groupid"]
      ]);
      if (sizeof($groupresult["values"]) != 0) $validgroup = True;
    }
  }

  if (isset($params["ruleid"])) {
    $validrule = False;

    if ($params["ruleid"] == "") $validrule = True;
    else {
      $ruleresult = civicrm_api3("Rule", "get", [
        'sequential' => 1,
        'dedupe_rule_group_id' => $params["ruleid"],
      ]);
      if (sizeof($ruleresult["values"]) != 0) $validrule = True;
    }
  }

  if (!$validgroup) {
    $returnValues["error_message"] = "this group is not valid.";
    return civicrm_api3_create_success($returnValues, $params, 'OSDIJob', 'Add');
  }

  if (!$validrule) {
    $returnValues["error_message"] = "this rule is not valid.";
    return civicrm_api3_create_success($returnValues, $params, 'OSDIJob', 'Add');
  }

  // check valid URL
  $client = new GuzzleHttp\Client();
  $guzzleparams = array();
  $guzzleparams["headers"] = [
    'OSDI-API-Token' => $params["key"],
  ];

  if (strpos($params["peopleendpoint"], 'actionnetwork.org') !== FALSE) {
    $guzzleparams["headers"]['Content-Type'] = "application/json";
  }
  else {
    $guzzleparams["headers"]["Content-Type"] = "application/hal+json";
    $guzzleparams["headers"]['Object'] = 'Contact';
  }

  try {
    $response = $client->get($params["peopleendpoint"], $guzzleparams);
  }
  catch (Exception $exception) {
    $responseBody = $exception->getResponse()->getBody(true);
    $returnValues["error_message"] = "this URL / apikey combination is not valid";
    $returnValues["body"] = $responseBody->getContents();
    return civicrm_api3_create_success($returnValues, $params, 'OSDIJob', 'Add');
  }

  if ($response->getStatusCode() != 200) {
    $returnValues["error_message"] = "this URL / apikey combination is not valid";
    $returnValues["body"] = $response->getBody()->getContents();
    return civicrm_api3_create_success($returnValues, $params, 'OSDIJob', 'Add');
  }

  if ($params["syncconfig"] == 1 or $params["syncconfig"] == 2) {

      $valid = FALSE;
      $id = -1;

      // dedupe on name
      $results = civicrm_api3('Job', 'get', [
        'name' => "OSDISYNC_IMPORT_" . $params["name"],
        'sequential' => 1
      ]);

      if (sizeof($results["values"]) != 0) {
        if ($params["edit"] == 0) {
          $returnValues["error_message"] = "this name is not unique.";
          return civicrm_api3_create_success($returnValues, $params, 'OSDIJob', 'Add');
        } else {
          $id = $results["values"][0]["id"];
          if ($id != "") $valid = TRUE;
        }
      }

    if ($params["edit"] == 0) {
        //first time import
        civicrm_api3('Importer', 'Import', [
          "zone" => $params["timezone"],
          "group" => $params["groupid"],
          "key" => $params["key"],
          "rule" => $params["ruleid"],
          "required" => $params["reqfields"],
          "endpoint" => $params["rootendpoint"]
        ]);
    }

    $importparams = array();
      if ($params["key"] != "") {
        $importparams[] = "key=" . $params["key"];
      }
      if ($params["reqfields"] != "") {
        $importparams[] = "required=" . $params["reqfields"];
      }
      if ($params["ruleid"] != "") {
        $importparams[] = "rule=" . $params["ruleid"];
      }
      if ($params["groupid"] != "") {
        $importparams[] = "group=" . $params["groupid"];
      }
      if ($params["rootendpoint"] != "") {
        $importparams[] = "endpoint=" . $params["rootendpoint"];
      }
      if ($params["timezone"] != "") {
        $importparams[] = "zone=" . $params["timezone"];
      }

      $importparamstring = join("\n", $importparams);

      $jobcreateparams = [
        'run_frequency' => "Daily",
        'name' => "OSDISYNC_IMPORT_" . $params["name"],
        'api_entity' => "Updater",
        'api_action' => "Update",
        'parameters' => $importparamstring
      ];

      if ($params["edit"] == 1 and $valid) $jobcreateparams["id"] = $id;

      //update import
      civicrm_api3('Job', 'create', $jobcreateparams);
    }

    if ($params["syncconfig"] == 1 or $params["syncconfig"] == 3) {
      // dedupe on name
      $results = civicrm_api3('Job', 'get', [
        'sequential' => 1,
        'name' => "OSDISYNC_EXPORT_" . $params["name"]
      ]);

      $valid = FALSE;
      $id = -1;
      if (sizeof($results["values"]) != 0) {
        if ($params["edit"] == 0) {
          $returnValues["error_message"] = "this name is not unique.";
          return civicrm_api3_create_success($returnValues, $params, 'OSDIJob', 'Add');
        } else {
          // we have to DELETE the onetime job, so exportonceparams can live
          $results_onetime = civicrm_api3('Job', 'get', [
            'name' => "OSDISYNC_EXPORT_ONETIME_" . $params["name"],
            'sequential' => 1
          ]);

          if (sizeof($results_onetime["values"][0]) != 0) {
            civicrm_api3('Job', 'delete', [
              'id' => $results_onetime["values"][0]["id"]
            ]);

            // clear the relevant session data
            // Use a sha1 of the key with the endpoint to find our identifier.
            // change the hash to the URL if Civi, key if actionnetwork
            $hash = "CIVI_ID_actionnetwork";
            if (strpos($params["rootendpoint"], "actionnetwork.org") === FALSE) {
              $hash = "CIVI_ID_" . sha1($params["rootendpoint"]);
            }
            $second_key = $params["signupendpoint"] . $hash;

            unset($_SESSION["exporters_offset"][$second_key]);
          }

          // and then continue the edit job as it were
          $id = $results["values"][0]["id"];
          if ($id != "") $valid = TRUE;
        }
      }

      $exportonceparams = array();
      if ($params["key"] != "") {
        $exportonceparams[] = "key=" . $params["key"];
      }
      if ($params["signupendpoint"] != "") {
        $exportonceparams[] = "endpoint=" . $params["signupendpoint"];
      }
      if ($params["rootendpoint"] != "") {
        $exportonceparams[] = "endpoint_root=" . $params["rootendpoint"];
      }
      $exportonceparams[] = "allow_restart=0";
      if ($params["groupid"] != "") {
        $exportonceparams[] = "group=" . $params["groupid"];
      }
      $exportonceparams[] = "updatejob=0";
      if ($params["peopleendpoint"] != "") {
        $exportonceparams[] = "updateendpoint=" . $params["peopleendpoint"];
      }
      if ($params["reqfields"] != "") {
        $exportonceparams[] = "required=" . $params["reqfields"];
      }
      if ($params["timezone"] != "") {
        $exportonceparams[] = "zone=" . $params["timezone"];
      }

      $exportonceparamstring = join("\n", $exportonceparams);

      if ($params["edit"] == 0) {
        // exporter bulk one time job
        civicrm_api3('Job', 'create', [
          'run_frequency' => "Always",
          'name' => "OSDISYNC_EXPORT_ONETIME_" . $params["name"],
          'api_entity' => "Exporter",
          'api_action' => "Bulk",
          'parameters' => $exportonceparamstring
        ]);
      }

      $exportmanyparams = array();
      if ($params["key"] != "") {
        $exportmanyparams[] = "key=" . $params["key"];
      }
      if ($params["signupendpoint"] != "") {
        $exportmanyparams[] = "endpoint=" . $params["signupendpoint"];
      }
      if ($params["rootendpoint"] != "") {
        $exportmanyparams[] = "endpoint_root=" . $params["rootendpoint"];
      }
      $exportmanyparams[] = "allow_restart=1";
      if ($params["groupid"] != "") {
        $exportmanyparams[] = "group=" . $params["groupid"];
      }
      $exportmanyparams[] = "updatejob=1";
      if ($params["peopleendpoint"] != "") {
        $exportmanyparams[] = "updateendpoint=" . $params["peopleendpoint"];
      }
      if ($params["reqfields"] != "") {
        $exportmanyparams[] = "required=" . $params["reqfields"];
      }
      if ($params["timezone"] != "") {
        $exportmanyparams[] = "zone=" . $params["timezone"];
      }

      $exportmanyparamstring = join("\n", $exportmanyparams);

      $jobcreateparams = [
        'run_frequency' => "Always",
        'name' => "OSDISYNC_EXPORT_" . $params["name"],
        'api_entity' => "Exporter",
        'api_action' => "Bulk",
        'parameters' => $exportmanyparamstring,
      ];

      if ($params["edit"] == 1 and $valid) $jobcreateparams["id"] = $id;

      // exporter bulk update job
      civicrm_api3('Job', 'create', $jobcreateparams);

      if ($params["edit"] == 0) {
        // set the mapping
        $firstitemid = civicrm_api3('Mapping', 'get', array(
          'name' => "osdi_contact",
        ));

        $firstitem = civicrm_api3('MappingField', 'get', array(
          'mapping_id' => $firstitemid["id"],
          'sequential' => 1,
          'options' => ['limit' => 0],
        ));

        $data = array();
        foreach ($firstitem["values"] as $key => $value) {
          $data[$value["name"]] = $value["value"];
        }

        civicrm_api3('Mapping', 'set', [
          "changes" => json_encode(array()),
          "data" => json_encode($data),
          "endpoint" => $params["rootendpoint"]
        ]);
      }
    }

  return civicrm_api3_create_success($returnValues, $params, 'OSDIJob', 'Add');
}
