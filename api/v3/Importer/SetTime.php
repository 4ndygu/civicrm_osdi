<?php
use CRM_Osdi_ExtensionUtil as E;

/**
 * Importer.SetTime API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_importer_SetTime_spec(&$spec) {
  $spec['zone']['api.required'] = 1;
}

/**
 * Importer.SetTime API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_importer_SetTime($params) {
    Civi::settings()->set('server_time_zone', $params["zone"]);

    $returnValues["result_zone"] = Civi::settings()->get("server_time_zone");

    return civicrm_api3_create_success($returnValues, $params, 'Importer', 'SetTime');
}
