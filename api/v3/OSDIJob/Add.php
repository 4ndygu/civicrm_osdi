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
  $spec['resource']['api.required'] = 1;
  $spec['rootendpoint']['api.required'] = 1;
  $spec['signupendpoint']['api.required'] = 1;
  $spec['peopleendpoint']['api.required'] = 1;
  $spec['groupid']['api.required'] = 1;
  $spec['ruleid']['api.required'] = 1;
  $spec['reqfields']['api.required'] = 1;
  $spec['syncconfig']['api.required'] = 1;
  $spec['timezone']['api.required'] = 1;
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

    /*if ($params["syncconfig"] == 1 or $params["syncconfig"] == 2) {
      civicrm_api3('Job', 'create', [
        'run_frequency' => "Daily",
        'name' => "OSDISYNC_IMPORT",
        'api_entity' => "Updater",
        'api_action' => "Update",
        'parameters' => ""
      ]);
    }

    if ($params["syncconfig"] == 1 or $params["syncconfig"] == 3) {
      civicrm_api3('Job', 'create', [
        'run_frequency' => "Daily",
        'name' => "OSDISYNC_EXPORT",
        'api_entity' => "Exporter",
        'api_action' => "Bulk",
        'parameters' => ""
      ]);
    }*/

  return civicrm_api3_create_success($returnValues, $params, 'NewEntity', 'NewAction');
}
