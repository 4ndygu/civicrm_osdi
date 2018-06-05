<?php

require_once __DIR__ . "/../../importers/PeopleStruct.php";

class CRM_OSDIQueue_Tasks {

	public static function AddContact(CRM_Queue_TaskContext $context, $contact_wrapper) {
		// this expects a hal object that represents a page of contacts
		// where do u load an action?
		CRM_Core_Session::setStatus('executing add contact task', 'Queue task', 'success');

        $contactresource = unserialize($contact_wrapper);
        $contact = $contactresource->person;
        $group = $contactresource->groupid;

		try {
                /*'middle_name' => $contact["additional_name"],
                'prefix_id' => $contact["honorific_prefix"],
                'suffix_id' => $contact["honorific_suffix"],
                'gender_id' => $contact["gender_id"],
                'preferred_language' => $contact["preferred_language"],
                'current_employer' => $contact["employer"],*/
            $result = civicrm_api3('Contact', 'create', array(
                'first_name' => $contact["given_name"],
                'last_name' => $contact["family_name"],
                'email' => $contact["email_addresses"][0]["address"],
                'display_name' => $contact["family_name"],
                'contact_type' => 'Individual',
                'dupe_check' => 1,
                'check_permission' => 1
			));
            if ($group != -1 and $group != NULL) {
                $result2 = civicrm_api3('GroupContact', 'create', array(
                    'group_id' => $group,
                    'contact_id' => $result["id"]
                ));
            }
		}
		catch (Exception $e) {
            // if duplicate, patch up
            var_dump("dupe");
            //var_dump($e->getExtraParams()['error_code']);
            //var_dump($e->getExtraParams()['error_code']["ASDFASDF"]);
            /*if ($e["extraParams"]["error_code"] == "Duplicate") {
                $result = civicrm_api3('Contact', 'create', array(
                    'id' => $e["extraParams"]["ids"][0],
                    'first_name' => $contact["given_name"],
                    'last_name' => $contact["family_name"],
                    'middle_name' => $contact["additional_name"],
                    'prefix_id' => $contact["honorific_prefix"],
                    'suffix_id' => $contact["honorific_suffix"],
                    'gender_id' => $contact["gender_id"],
                    'current_employer' => $contact["employer"],
                    'email' => $contact["email_addresses"][0]["address"],
                    'preferred_language' => $contact["preferred_language"],
                    'display_name' => $contact["family_name"],
                    'contact_type' => 'Individual',
			    ));
                return True;
            }*/
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
