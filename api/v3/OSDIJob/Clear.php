<?php
use CRM_Osdi_ExtensionUtil as E;

/**
 * OSDIJob.Clear API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_o_s_d_i_job_Clear_spec(&$spec) {
  $spec['id_import']['api.required'] = 0;
  $spec['id_export']['api.required'] = 0;
  $spec['id_export_once']['api.required'] = 0;
}

/**
 * OSDIJob.Clear API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_o_s_d_i_job_Clear($params) {
  $returnValues = array();
  if (array_key_exists('id_import', $params)) {
    var_dump("wtf");
    $results = civicrm_api3('Job', 'delete', [
      'id' => $params["id_import"]
    ]);

    $returnValues["importresults"] = $results;
  }

  if (array_key_exists('id_export', $params)) {
    $results = civicrm_api3('Job', 'delete', [
      'id' => $params["id_export"]
    ]);

    $returnValues["exportresults"] = $results;

  }

  if (array_key_exists('id_export_once', $params)) {
    $results = civicrm_api3('Job', 'delete', [
      'id' => $params["id_export_once"]
    ]);

    $returnValues["exportonceresultss"] = $results;
  }
  return civicrm_api3_create_success($returnValues, $params, 'OSDIJob', 'JobDelete');
}
