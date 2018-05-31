<?php

// require definition of resource
require __DIR__ . '/../../../vendor/autoload.php';

use Ekino\HalClient\Resource;

use CRM_Osdi_ExtensionUtil as E;

require_once __DIR__ . '/../../../importers/ActionNetworkContactImporter.php';
require_once __DIR__ . '/../../../importers/ResourceStruct.php';

/**
 * Importer.Schedule API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_importer_Schedule_spec(&$spec) {
}

/**
 * Importer.Schedule API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_importer_Schedule($params) {

	$returnValues = array();
	
	// get out if nobody started
	if (!isset($_SESSION["extractors"]) or empty($_SESSION["extractors"])) {
		$returnValues["status"] = "no variable set";
		return civicrm_api3_create_success($returnValues, $params, 'Importer', 'schedule');
	}

	// run this 20 times, quit if you hit NULL
	$to_unserialize = array_pop($_SESSION["extractors"]);
	$malformed = false;
	if (is_string($to_unserialize)) {
		$rootdata = unserialize($to_unserialize);
	} else {
		$malformed = true;
	}

	if ($malformed or ! ($rootdata instanceof ResourceStruct)) {
		$returnValues["status"] = "malformed data";
		CRM_Core_Session::setStatus('malformed data. removing from queue', 'Queue task', 'success');
		return civicrm_api3_create_success($returnValues, $params, 'Importer', 'schedule');
	}

    $root = $rootdata->resource;
	
	//$this->queue = CRM_OSDIQueue_Helper::singleton()->getQueue();

	if ($root == NULL) {
		$returnValues["status"] = "no root";
		return civicrm_api3_create_success($returnValues, $params, 'Importer', 'schedule');
	}

	$counter = 0;
	for ($i = 0; $i <= 10; $i++) {
		$people = $root->get('osdi:people');
		if ($people == NULL) {
			$returnValues["status"] = "malformed data";
			CRM_Core_Session::setStatus('malformed data. removing from queue', 'Queue task', 'success');
			return civicrm_api3_create_success($returnValues, $params, 'Importer', 'schedule');
		}
	
		foreach ($people as $person) {
			if (ActionNetworkContactImporter::validate_endpoint_data($person, $rootdata->filter)) {
				ActionNetworkContactImporter::add_task_with_page($person, $rootdata->rule, $rootdata->group);
				$counter++;
			}
		}

		$root = $root->get('next');
		if ($root == NULL) {
			$returnValues["status"] = "completed";

            // add merge task
            ActionNetworkContactImporter::merge_task_with_page($rootdata->rule);

			CRM_Core_Session::setStatus('adding contacts to pipeline', 'Queue task', 'success');
			return civicrm_api3_create_success($returnValues, $params, 'Importer', 'schedule');
		}
	} 

	// in this case, we still got stuff to do! so im gonna put it back into the array.
	// i throw it into tthe back to prevent starvation in event of multiple extractors
	CRM_Core_Session::setStatus('adding contacts to pipeline', 'Queue task', 'success');

    $returned_data = new ResourceStruct($root, $rootdata->rule, $rootdata->filter);
	$_SESSION["extractors"][] = serialize($returned_data);

	$returnValues["status"] = "partially completed";
	$returnValues["counter"] = $counter;
	return civicrm_api3_create_success($returnValues, $params, 'Importer', 'schedule');
}
