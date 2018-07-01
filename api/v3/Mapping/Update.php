<?php
use CRM_Osdi_ExtensionUtil as E;

/**
 * Mapping.Update API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_mapping_Update_spec(&$spec) {
}

/**
 * Mapping.Update API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_mapping_Update($params) {
    $response = array();

    // first grab all standard mappings and load into array
    $firstitem = civicrm_api3('Mapping', 'get', array(
        'name' => "osdi_contact_remote",
    ));

    $result = civicrm_api3('MappingField', 'get', [
        'sequential' => 1,
        'mapping_id' => $firstitem["id"],
        'options' => ['limit' => 0],
    ]);

    $fieldmapping = array();
    foreach ($result["values"] as $value) {
        $fieldmapping[$value["name"]] = $value["value"];
    }

    // now grab everyone who starts with OSDI or OSDIREMOTE
    $result = civicrm_api3('Mapping', 'get', [
        'sequential' => 1,
        'name' => ['LIKE' => "OSDIREMOTE%"],
    ]);

    foreach ($result["values"] as $value) {
        // grab the actual values
        $values = civicrm_api3("MappingField", "get", array(
            "sequential" => 1,
            "mapping_id" => $value["id"],
            "options" => ["limit" => 0]
        ));

        $fieldmappingcopy = $fieldmapping;
        foreach ($values["values"] as $valuetwo) {
            unset($fieldmappingcopy[$valuetwo["name"]]);
        }

        if (sizeof($fieldmappingcopy) != 0) {
            //load next IDs and put them in the mapping

            $name = $value["name"];
            $OSDIname = "OSDI_" . substr($name, 11);

            $idresult = civicrm_api3('Mapping', 'get', array(
                'name' => $OSDIname,
            ));

            foreach ($fieldmappingcopy as $leftoverkey => $leftovervalue ) {
                // generate leftovers in REMOTE and regular
                $addresult = civicrm_api3('MappingField', 'create', array(
                    'mapping_id' => $value["id"],
                    'name' => $leftoverkey,
                    'value' => $leftovervalue,
                    'column_number' => 1
                ));

                $addresult = civicrm_api3('MappingField', 'create', array(
                    'mapping_id' => $idresult["id"],
                    'name' => $leftovervalue,
                    'value' => $leftoverkey,
                    'column_number' => 1
                ));

            }
        }
    }

    return civicrm_api3_create_success($response, $params, 'Exporter', 'export');

}
