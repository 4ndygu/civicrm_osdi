<?php

require_once __DIR__ . "/../../importers/PeopleStruct.php";

class CRM_OSDIQueue_Tasks {

    public static $OSDICiviArray = array(
        "last_name" => "family_name",
        "first_name" => "given_name",
        "middle_name" => "additional_name"
    );

    public static function AddContact(CRM_Queue_TaskContext $context, $contact_wrapper) {
        // this expects a hal object that represents a page of contacts
        // where do u load an action?
        CRM_Core_Session::setStatus('executing add contact task', 'Queue task', 'success');

        $contactresource = unserialize($contact_wrapper);
        $contact = $contactresource->person;
        $group = $contactresource->groupid;
        $rule = $contactresource->rule;
        $apikey = $contactresource->apikey;

        // check if our ID is stored already
        $contact_id = -1;
        if ($contact["custom_fields"] != NULL) {
            $hash = CRM_Utils_System::url("civicrm");
            if (isset($contact["custom_fields"][$hash])) {
                $contact_id = $contact["custom_fields"][$hash];
            }
        }

        // if not, match by dedupe rule
        if ($contact_id == -1) {
            $getParams = array();
            $getParams["sequential"] = 1;
            $getParams["contact_type"] = "Individual";

            $fieldsResponse = array();
            $fieldsResponse["values"] = array();

            if ($rule != NULL) {
                // grab fields from rule and load up Contact.get query
                $fieldsResponse = civicrm_api3('Rule', 'get', array(
                      "sequential" => 1,
                      "dedupe_rule_group_id" => $rule
                ));

                foreach ($fieldsResponse["values"] as $field) {
                    $actualField = $field["rule_field"];
                    if ($actualField == "email") {
                        $getParams[$actualField] = $contact["email_addresses"][0]["address"];
		    } else {
                        $getParams[$actualField] = $contact[CRM_OSDIQueue_Tasks::$OSDICiviArray[$actualField]];
                    }
                }
            }
 
            if ($rule == NULL or $rule == "" or sizeof($fieldsResponse["values"]) == 0) {
                $getParams["email"] = $contact["email_addresses"][0]["address"];
            }

            $test = civicrm_api3('Contact', 'get', $getParams);

            if (sizeof($test["values"]) != 0) {
                $contact_id = $test["values"][0]["contact_id"];
            }
	    }

        // this ultimately getVs passed to the api
        $params = array();
        $params["first_name"] = $contact["given_name"];
        $params["last_name"] = $contact["family_name"];
        $params["email"] = $contact["email_addresses"][0]["address"];
        $params["display_name"] = $contact["family_name"];
        $params["contact_type"] = "Individual";

        // load the ID into your group
	$custom_fields = $contact["custom_fields"];
	$custom_fields["ID_" . sha1($apikey)] = $contact["identifiers"][0];

	// load the AN ID into custom_fields
        	

        // current key is sha1 of the /civicrm endpoint
        $key = "ID_" . sha1(CRM_Utils_System::url("civicrm"));
        $currentCRMFound = False;

        try {
            foreach ($custom_fields as $custom_field => $custom_value) {
                if ($custom_field == $key) $currentCRMFound = True;

                // each custom field should be searchable
                $results = civicrm_api3('CustomField', 'get', array(
                    'custom_group_id' => $_SESSION["OSDIGROUPID"],
                    'name' => $custom_field,
                ));

                // if custom field doesn't exist, create
                if (sizeof($results["values"]) == 0) {
                    $results = civicrm_api3('CustomField', 'create', array(
                        'custom_group_id' => $_SESSION["OSDIGROUPID"],
			'label' => $custom_field,
                        'data_type' => 'String',
			'html_type' => "Text"
                    ));
                } 
                $id = $results["id"]; 
                $params["custom_" . $id] = $custom_value;
            }

            // generate the field for this instance if it isn't generated. 
            // DONT import it. only do that on export
            if (!$currentCRMFound) { 
                $results = civicrm_api3('CustomField', 'create', array(
                        'custom_group_id' => $_SESSION["OSDIGROUPID"],
			'label' => $custom_field,
                        'data_type' => 'String',
			'html_type' => "Text"
                ));
            }

	    var_dump($params);
	    $params["debug"] = 1;
            // if contact exists, supply with id to update instead
            if ($contact_id == -1) {
                $params["dupe_check"] = 1;
                $params["check_permission"] = 1;

                $result = civicrm_api3('Contact', 'create', $params);
            } else {
                $params["id"] = $contact_id;

                $result = civicrm_api3('Contact', 'create', $params);
            }

            // add to group as well
            if ($group != -1 and $group != NULL) {
                $result2 = civicrm_api3('GroupContact', 'create', array(
                    'group_id' => $group,
                    'contact_id' => $result["id"]
                ));
            }
        } catch (Exception $e) {
            var_dump($e);
            return True;
        }

        return True;
    }

    public static function MergeContacts(CRM_Queue_TaskContext $context, $contact_wrapper) {
        // deprecated
        return True;

        $contactresource = unserialize($contact_wrapper);
        $rule = $contactresource->rule;
        if ($rule == NULL or $rule == -1) return True;

        //$dupes = CRM_Dedupe_Finder::dupes($rule);
        $mergestatus = CRM_Dedupe_Merger::batchMerge($rule);
        var_dump("MERTGING");
        var_dump($mergestatus);

		return True;
    }
}
?>
