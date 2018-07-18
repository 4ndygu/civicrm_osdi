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

      //first time import
      civicrm_api3('Importer', 'Import', [
        "zone" => $params["timezone"],
        "group" => $params["groupid"],
        "key" => $params["key"],
        "rule" => $params["ruleid"],
        "required" => $params["reqfields"],
        "endpoint" => $params["rootendpoint"]
      ]);

      $importparams = join("\n", array(
        "key=" . $params["key"],
        "required=" . $params["reqfields"],
        "rule=" . $params["ruleid"],
        "group=" . $params["groupid"],
        "endpoint=" . $params["rootendpoint"],
        "zone=" . $params["timezone"]
      ));

      $jobcreateparams = [
        'run_frequency' => "Daily",
        'name' => "OSDISYNC_IMPORT_" . $params["name"],
        'api_entity' => "Updater",
        'api_action' => "Update",
        'parameters' => $importparams
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

      $exportonceparams = join("\n", array(
        "key=" . $params["key"],
        "endpoint=" . $params["signupendpoint"],
        "endpoint_root=" . $params["rootendpoint"],
        "allow_restart=0",
        "group=" . $params["groupid"],
        "updatejob=0",
        "updateendpoint=" . $params["peopleendpoint"],
        "required=" . $params["reqfields"],
        "zone=" . $params["timezone"]
      ));

      // exporter bulk one time job
      civicrm_api3('Job', 'create', [
        'run_frequency' => "Always",
        'name' => "OSDISYNC_EXPORT_ONETIME_" . $params["name"],
        'api_entity' => "Exporter",
        'api_action' => "Bulk",
        'parameters' => $exportonceparams
      ]);

      $exportmanyparams = join("\n", array(
        "key=" . $params["key"],
        "endpoint=" . $params["signupendpoint"],
        "endpoint_root=" . $params["rootendpoint"],
        "allow_restart=1",
        "group=" . $params["groupid"],
        "updatejob=1",
        "updateendpoint=" . $params["peopleendpoint"],
        "required=" . $params["reqfields"],
        "zone=" . $params["timezone"]
      ));

      $jobcreateparams = [
        'run_frequency' => "Daily",
        'name' => "OSDISYNC_EXPORT_" . $params["name"],
        'api_entity' => "Exporter",
        'api_action' => "Bulk",
        'parameters' => $exportmanyparams,
      ];

      if ($params["edit"] == 1 and $valid) $jobcreateparams["id"] = $id;

      // exporter bulk update job
      civicrm_api3('Job', 'create', $jobcreateparams);

    }

  return civicrm_api3_create_success($returnValues, $params, 'OSDIJob', 'Add');
}
