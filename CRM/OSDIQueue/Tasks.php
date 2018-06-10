<?php

require_once __DIR__ . "/../../importers/PeopleStruct.php";

class CRM_OSDIQueue_Tasks {

    public $OSDICiviArray = array(
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

        // check if our ID is stored already
        $contact_id = -1;
        if ($contact["custom_fields"] != NULL) {
            $hash = CRM_Utils_System::url("civicrm");
            if (isset($contact["custom_fields"][$hash])) {
                $contact_id = $contact["custom_fields"][$hash];
            }
        }
        var_Dump($contact_id);

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
                      "sequential": 1,
                      "dedupe_rule_group_id": $rule
                ));

                foreach ($fieldsResponse["values"] as $field) {
                    $actualField = $field["rule_field"];
                    if ($actualField == "email") {
                        $getParams[$field] = $contact["email_addresses"][0]["address"];
                    } else {
                        $getParams[$field] = $contact[$OSDICiviArray[$field]];
                    }
                }
            }
            var_dump($getParams);
 
            if ($rule == NULL or sizeof($fieldsResponse["values"]) == 0) {
                $getParams["email"] = $contact["email_addresses"][0]["address"];
            }

            $test = civicrm_api3('Contact', 'get', $getParams);

            if (sizeof($test["values"]) != 0) {
                $contact_id = $test["values"][0]["contact_id"];
            }
        }

        return True;
        try {
            // if contact exists, supply with id to update instead
            if ($contact_id == -1) {
                $result = civicrm_api3('Contact', 'create', array(
                    'first_name' => $contact["given_name"],
                    'last_name' => $contact["family_name"],
                    'email' => $contact["email_addresses"][0]["address"],
                    'display_name' => $contact["family_name"],
                    'contact_type' => 'Individual',
                    'dupe_check' => 1,
                    'check_permission' => 1
                ));
            } else {
                $result = civicrm_api3('Contact', 'create', array(
                    'id' => $contact_id,
                    'first_name' => $contact["given_name"],
                    'last_name' => $contact["family_name"],
                    'email' => $contact["email_addresses"][0]["address"],
                    'display_name' => $contact["family_name"],
                    'contact_type' => 'Individual',
                ));
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
