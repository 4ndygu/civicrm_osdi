<?php

use CRM_Osdi_ExtensionUtil as E;

require_once __DIR__ . '/../../../importers/ActionNetworkContactImporter.php';
require_once __DIR__ . '/../../../importers/CiviCRMContactImporter.php';
require_once __DIR__ . '/../../../osdi.php';

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
  $spec['zone']['api.required'] = 0;
  $spec['required']['api.required'] = 0;
  $spec['rule']['api.required'] = 0;
  $spec['endpoint']['api.required'] = 1;
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
    install_groupid();

    $importer = NULL;
    if (strpos($params["endpoint"], "actionnetwork.org") !== False) {
	$importer = new ActionNetworkContactImporter("https://actionnetwork.org/api/v2", "x", $params["key"]);
    } else {
        $importer = new CiviCRMContactImporter($params["endpoint"], "x", $params["key"]);
    }

    $filter = NULL;
    $rule = NULL;
    $group = -1;
    $zone = 0;

    if (isset($params["required"])) { 
        $filter = $params["required"];
    }

    if (isset($params["rule"])) { 
        $rule = $params["rule"];
    }

    if (isset($params["group"])) { 
        $group = $params["group"];
    }

    if (isset($params["zone"])) {
        $zone = $params["zone"];
    }

    $returnValues["group"] = $group;

    $count = $importer->pull_endpoint_data($filter, $rule, $group, $zone);
 
    $returnValues["count"] = $count; 

    $returnValues["session"] = $_SESSION["extractors"];
 
	return civicrm_api3_create_success($returnValues, $params, 'Importer', 'import');
}
