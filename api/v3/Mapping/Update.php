<?php

/**
 * @file
 */

/**
 * Mapping.Update API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec
 *   description of fields supported by this API call.
 *
 * @return void
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_mapping_Update_spec(&$spec) {
}

/**
 * Mapping.Update API.
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
function civicrm_api3_mapping_Update($params) {
  $response = array();

  // add custom fields that are new to this group
  $custom_results = civicrm_api3('CustomField', 'get', [
    'sequential' => 1,
    'custom_group_id' => Civi::settings()->get('OSDIGROUPID')
  ]);

  // First grab all standard mappings and load into array.
  $firstitem = civicrm_api3('Mapping', 'get', array(
    'name' => "osdi_contact_remote",
  ));
  // First grab all standard mappings and load into array.
  $seconditem = civicrm_api3('Mapping', 'get', array(
    'name' => "osdi_contact",
  ));

  $result = civicrm_api3('MappingField', 'get', [
    'sequential' => 1,
    'mapping_id' => $firstitem["id"],
    'options' => ['limit' => 0],
  ]);

  $fieldmapping = array();
  $searchmapping = array();
  foreach ($result["values"] as $value) {
    $fieldmapping[$value["name"]] = $value["value"];
    $searchmapping[$value["value"]] = $value["name"];
  }

  // make sure custom fields taht are NEW are pushed to this group before we update
  foreach ($custom_results["values"] as $custom_result) {
    if (!isset($searchmapping["custom_" . $custom_result["id"]])) {
      // add it!!!\
      $result = civicrm_api3('MappingField', 'create', [
        'mapping_id' => $firstitem["id"],
        'name' => "custom_fields|" . $custom_result["name"],
        'value' => "custom_" . $custom_result["id"],
        'column_number' => 1,
      ]);

      // Shunt the backward direction.
      $result = civicrm_api3('MappingField', 'create', [
        'mapping_id' => $seconditem["id"],
        'name' => "custom_" . $custom_result["id"],
        'value' => "custom_fields|" . $custom_result["name"],
        'column_number' => 1,
      ]);

      $fieldmapping["custom_fields|" . $custom_result["name"]] = "custom_" . $custom_result["id"];
    }
  }

  // Now grab everyone who starts with OSDI or OSDIREMOTE.
  $result = civicrm_api3('Mapping', 'get', [
    'sequential' => 1,
    'name' => ['LIKE' => "OSDIREMOTE%"],
  ]);

  foreach ($result["values"] as $value) {
    // Grab the actual values.
    $values = civicrm_api3("MappingField", "get", array(
      "sequential" => 1,
      "mapping_id" => $value["id"],
      "options" => ["limit" => 0],
    ));

    $fieldmappingcopy = $fieldmapping;
    foreach ($values["values"] as $valuetwo) {
      unset($fieldmappingcopy[$valuetwo["name"]]);
    }

    if (sizeof($fieldmappingcopy) != 0) {
      // Load next IDs and put them in the mapping.
      $name = $value["name"];
      $OSDIname = "OSDI_" . substr($name, 11);

      $idresult = civicrm_api3('Mapping', 'get', array(
        'name' => $OSDIname,
      ));

      foreach ($fieldmappingcopy as $leftoverkey => $leftovervalue) {
        // Generate leftovers in REMOTE and regular.
        $addresult = civicrm_api3('MappingField', 'create', array(
          'mapping_id' => $value["id"],
          'name' => $leftoverkey,
          'value' => $leftovervalue,
          'column_number' => 1,
        ));

        $addresult = civicrm_api3('MappingField', 'create', array(
          'mapping_id' => $idresult["id"],
          'name' => $leftovervalue,
          'value' => $leftoverkey,
          'column_number' => 1,
        ));

      }
    }
  }

  return civicrm_api3_create_success($response, $params, 'Exporter', 'export');

}
