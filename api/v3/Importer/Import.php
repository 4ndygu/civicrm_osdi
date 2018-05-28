<?php

use CRM_Osdi_ExtensionUtil as E;

require_once __DIR__ . '/../../../importers/ActionNetworkContactImporter.php';

/**
 * Importer.Import API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_importer_Import_spec(&$spec) {
  $spec['key']['api.required'] = 1;
}

/**
 * Importer.Import API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_importer_Import($params) {
	$importer = new ActionNetworkContactImporter("https://actionnetwork.org/api/v2", "x", $params["key"]);

    $filter = NULL;
    $rule = NULL;
    if (isset($params["required"])) { 
        $filter = $params["required"];
    }

    if (isset($params["rule"])) { 
        $rule = $params["rule"];
    }

	$count = $importer->pull_endpoint_data($filter, $rule);

	$returnValues["count"] = $count; 

    $returnValues["session"] = $_SESSION["extractors"];
 
	return civicrm_api3_create_success($returnValues, $params, 'Importer', 'import');
}
