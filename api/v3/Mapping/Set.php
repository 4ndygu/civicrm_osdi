<?php
use CRM_Osdi_ExtensionUtil as E;

/**
 * Mapping.Set API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_mapping_Set_spec(&$spec) {
    $spec['data']['api.required'] = 1;
    $spec['changes']['api.required'] = 1;
    $spec['endpoint']['api.required'] = 1;
}

/**
 * Mapping.Set API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_mapping_Set($params) {
    $returnValues = array();

    // append OSDI to the endpoint
    $groupname = "OSDI_" . $params["endpoint"];
    $remotegroupname = "OSDIREMOTE_" . $params["endpoint"];

    // generate group if nonexistent
    $firstitem = civicrm_api3('Mapping', 'get', array(
        'name' => $groupname,
    ));

    $seconditem = civicrm_api3('Mapping', 'get', array(
        'name' => $remotegroupname,
    ));

    $new = False;
    if (sizeof($firstitem["values"]) == 0) {
        $new = True;
        $mappingresult = civicrm_api3('Mapping', 'create', array(
            'name' => $groupname,
            'description' => "field matching rules for OSDI Contacts for this specific endpoint",
            'mapping_type_id' => "Import Contact",
        ));

        $id = $mappingresult["id"];
    } else {
        $id = $firstitem["id"];
    }

    if (sizeof($firstitem["values"]) == 0) {
        $new = True;
        $mappingremoteresult= civicrm_api3('Mapping', 'create', array(
            'name' => $remotegroupname,
            'description' => "field matching rules for OSDI Contacts for this specific endpoint (remote)",
            'mapping_type_id' => "Import Contact",
        ));

        $idremote = $mappingremoteresult["id"];
    } else {
        $idremote = $seconditem["id"];
    }

    $changes = json_decode($params["changes"]);
    $data = json_decode($params["data"]);

    if ($new) {
        // if new, load everyone
        foreach ($data as $key => $value) {
            $addresult = civicrm_api3('MappingField', 'create', array(
                'mapping_id' => $id,
                'name' => $key,
                'value' => $value,
                'column_number' => 1
            ));

            $addresult = civicrm_api3('MappingField', 'create', array(
                'mapping_id' => $idremote,
                'name' => $value,
                'value' => $key,
                'column_number' => 1
            ));
        }
        $returnValues["message"] = "new item initialized";
    } else {
        var_dump($id);
        var_dump($idremote);
        // just grab everyone
        $result = civicrm_api3('MappingField', 'get', [
            'sequential' => 1,
            'mapping_id' => $id,
            'options' => ['limit' => 0],
        ]);

        $resultremote = civicrm_api3('MappingField', 'get', [
            'sequential' => 1,
            'mapping_id' => $idremote,
            'options' => ['limit' => 0],
        ]);

        $idmapping = array();
        $valuemapping = array();
        foreach ($result["values"] as $item) {
            $idmapping[$item["name"]] = $item["id"];
            $valuemapping[$item["name"]] = $item["value"];
        }

        $idmappingremote = array();
        $valuemappingremote = array();
        foreach ($resultremote["values"] as $item) {
            $idmappingremote[$item["name"]] = $item["id"];
            $valuemappingremote[$item["name"]] = $item["value"];
        }

        // if old, load changes
        foreach ($changes as $key => $value) {
            if (!isset($idmapping[$key])) continue;

            var_dump($key);
            var_dump($valuemapping[$key]);
            var_dump($idmappingremote[$valuemapping[$key]]);

            // find the ID for the current value via the mapping
            $result = civicrm_api3('MappingField', 'create', [
                'id' => $idmapping[$key],
                'mapping_id' => $id,
                'name' => $key,
                'value' => $value,
                'column_number' => 1
            ]);

            // find the ID for the current value via the mapping
            $result = civicrm_api3('MappingField', 'create', [
                'id' => $idmappingremote[$valuemapping[$key]],
                'mapping_id' => $idremote,
                'name' => $value,
                'value' => $key,
                'column_number' => 1
            ]);
        }

        $returnValues["message"] = "updated";
    }

    return civicrm_api3_create_success($returnValues, $params, 'NewEntity', 'NewAction');
}
