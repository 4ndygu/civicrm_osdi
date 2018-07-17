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
      // dedupe on name
      $results = civicrm_api3('Job', 'get', [
        'name' => "OSDISYNC_IMPORT_" . $params["name"],
      ]);
      if (sizeof($results["values"]) != 0) {
        $returnValues["error_message"] = "this name is not unique.";
        return civicrm_api3_create_success($returnValues, $params, 'OSDIJob', 'Add');
      }

      //first time import
      $result = civicrm_api3('Importer', 'Import', [
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

      //update import
      civicrm_api3('Job', 'create', [
        'run_frequency' => "Daily",
        'name' => "OSDISYNC_IMPORT_" . $params["name"],
        'api_entity' => "Updater",
        'api_action' => "Update",
        'parameters' => $importparams
      ]);
    }

    if ($params["syncconfig"] == 1 or $params["syncconfig"] == 3) {
      // dedupe on name
      $results = civicrm_api3('Job', 'get', [
        'name' => "OSDISYNC_EXPORT_" . $params["name"],
      ]);
      if (sizeof($results["values"]) != 0) {
        $returnValues["error_message"] = "this name is not unique.";
        return civicrm_api3_create_success($returnValues, $params, 'OSDIJob', 'Add');
      }

      $exportonceparams = join("\n", array(
        "key=" . $params["key"],
        "endpoint=" . $params["signupendpoint"],
        "endpoint_root=" . $params["rootendpoint"],
        "allow_restart=0",
        "group=" . $params["groupid"],
        "updatejob=0",
        "updateendpoint=" . $params["updateendpoint"],
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
        "updateendpoint=" . $params["updateendpoint"],
        "required=" . $params["reqfields"],
        "zone=" . $params["timezone"]
      ));

      // exporter bulk update job
      civicrm_api3('Job', 'create', [
        'run_frequency' => "Daily",
        'name' => "OSDISYNC_EXPORT_" . $params["name"],
        'api_entity' => "Exporter",
        'api_action' => "Bulk",
        'parameters' => $exportmanyparams
      ]);

    }

  return civicrm_api3_create_success($returnValues, $params, 'OSDIJob', 'Add');
}
