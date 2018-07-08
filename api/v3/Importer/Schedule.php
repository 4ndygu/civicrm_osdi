<?php

/**
 * @file
 * Require definition of resource.
 */

require __DIR__ . '/../../../vendor/autoload.php';

use Ekino\HalClient\Resource;

require_once __DIR__ . '/../../../importers/ActionNetworkContactImporter.php';
require_once __DIR__ . '/../../../importers/ResourceStruct.php';

/**
 * Importer.Schedule API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec
 *   description of fields supported by this API call.
 *
 * @return void
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_importer_Schedule_spec(&$spec) {
}

/**
 * Importer.Schedule API.
 *
 * @param array $params
 *
 * @return array API result descriptor
 *
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 *
 * @throws API_Exception
 */
function civicrm_api3_importer_Schedule($params) {
  $returnValues = array();
  // Get out if nobody started.
  if (!isset($_SESSION["extractors"]) or empty($_SESSION["extractors"])) {
    $returnValues["status"] = "no variable set";
    return civicrm_api3_create_success($returnValues, $params, 'Importer', 'schedule');
  }

  // Run this 20 times, quit if you hit NULL.
  $to_unserialize = array_pop($_SESSION["extractors"]);
  $malformed = FALSE;
  if (is_string($to_unserialize)) {
    $rootdata = unserialize($to_unserialize);
  }
  else {
    $malformed = TRUE;
  }

  if ($malformed or !($rootdata instanceof ResourceStruct)) {
    $returnValues["status"] = "malformed data";
    CRM_Core_Session::setStatus('malformed data. removing from queue', 'Queue task', 'success');
    return civicrm_api3_create_success($returnValues, $params, 'Importer', 'schedule');
  }

  $root = $rootdata->resource;
  $zone = $rootdata->zone;
  $apikey = $rootdata->apikey;

  if ($root == NULL) {
    $returnValues["status"] = "no root";
    return civicrm_api3_create_success($returnValues, $params, 'Importer', 'schedule');
  }

  // Set counter n date
  // note - we do the date_filter for *everybody* because we always want the
  // newest data if items are already modified.
  $counter = 0;

  // Add the relevant contacts that can be added to the queue.
  $returnValues["person"] = array();

  for ($i = 0; $i <= 10; $i++) {
    $people = $root->get('osdi:people');
    if ($people == NULL) {
      $returnValues["status"] = "malformed data";
      CRM_Core_Session::setStatus('malformed data. removing from queue', 'Queue task', 'success');
      return civicrm_api3_create_success($returnValues, $params, 'Importer', 'schedule');
    }

    $returnValues["group"] = $rootdata->group;

    foreach ($people as $person) {
      $properties = $person->getProperties();
      $date = $properties["modified_date"];
      $returnValues["person"][$properties["email_addresses"][0]["address"]] = array();
      $returnValues["person"][$properties["email_addresses"][0]["address"]]["valid"] = ActionNetworkContactImporter::validate_endpoint_data($person, $rootdata->filter);

      if (ActionNetworkContactImporter::validate_endpoint_data($person, $rootdata->filter)) {
        $returnValues["person"][$properties["email_addresses"][0]["address"]]["new"] = ActionNetworkContactImporter::is_newest_endpoint_data($person, $date, $zone);
        if (ActionNetworkContactImporter::is_newest_endpoint_data($person, $date, $zone)) {
          ActionNetworkContactImporter::add_task_with_page($person, $rootdata->rule, $rootdata->group, $apikey, $rootdata->endpoint);
          $counter++;
        }
      }
    }
    try {
      $root = $root->get('next');
    }
    catch (Exception $e) {
      $root = NULL;
    }

    if ($root == NULL) {
      $returnValues["status"] = "completed";

      // Add merge task
      // ActionNetworkContactImporter::merge_task_with_page($rootdata->rule);.
      CRM_Core_Session::setStatus('adding contacts to pipeline', 'Queue task', 'success');
      return civicrm_api3_create_success($returnValues, $params, 'Importer', 'schedule');
    }
  }

  // In this case, we still got stuff to do! so im gonna put it back into the array.
  // i throw it into tthe back to prevent starvation in event of multiple extractors.
  CRM_Core_Session::setStatus('adding contacts to pipeline', 'Queue task', 'success');

  $returned_data = new ResourceStruct($root, $rootdata->rule, $rootdata->filter, $rootdata->group, $zone, $apikey, $rootdata->endpoint);
  $_SESSION["extractors"][] = serialize($returned_data);

  $returnValues["status"] = "partially completed";
  $returnValues["counter"] = $counter;
  return civicrm_api3_create_success($returnValues, $params, 'Importer', 'schedule');
}
