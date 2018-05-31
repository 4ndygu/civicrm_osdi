<?php
require_once __DIR__ . '/../../../importers/ActionNetworkContactImporter.php';

use CRM_Osdi_ExtensionUtil as E;

/**
 * Updater.Update API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_updater_Update_spec(&$spec) {
	$spec['key']['api.required'] = 1;
	$spec['required']['api.required'] = 0;
	$spec['rule']['api.required'] = 0;
	$spec['group']['api.required'] = 0;
}

/**
 * Updater.Update API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_updater_Update($params) {

	// get the current date
	$date = date('Y-m-d', time());

    $filter = NULL;
    $rule = NULL;
    if (isset($params["required"])) {
        $filter = $params["required"];
    }
  
    if (isset($params["rule"])) {
        $rule = $params["rule"];
    }

    if (isset($params["group"])) {
        $group = $params["group"];
    }

	// run the importer
    $importer = new ActionNetworkContactImporter("https://actionnetwork.org/api/v2", "x", $params["key"]);
    $result = $importer->update_endpoint_data($date, $filter, $rule, $group);

	$returnValues = array();
	$returnValues["returned_item"] = $result;
	// if we here we ballin	
	return civicrm_api3_create_success($returnValues, $params, 'Updater', 'Update');
}
