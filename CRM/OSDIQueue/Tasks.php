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
            $test = civicrm_api3('Contact', 'get', array(
                'first_name' => $contact["given_name"],
                'last_name' => $contact["family_name"],
                'email' => $contact["email_addresses"][0]["address"],
                'contact_type' => 'Individual'
            ));

            // if contact exists, supply with id to update instead
            if (sizeof($test["values"]) == 0) {
                $result = civicrm_api3('Contact', 'create', array(
                    'first_name' => $contact["given_name"],
                    'last_name' => $contact["family_name"],
                    'email' => $contact["email_addresses"][0]["address"],
                    'city' => $contact["postal_addresses"][0]["locality"],
                    'state_province_name' => $contact["postal_addresses"][0]["region"],
                    'country' => $contact["postal_addresses"][0]["country"],
                    'display_name' => $contact["family_name"],
                    'contact_type' => 'Individual',
                    'dupe_check' => 1,
                    'check_permission' => 1
                ));
            } else {
                var_dump($test["values"][0]["contact_id"]);
                $result = civicrm_api3('Contact', 'create', array(
                    'id' => $test["values"][0]["contact_id"],
                    'first_name' => $contact["given_name"],
                    'last_name' => $contact["family_name"],
                    'email' => $contact["email_addresses"][0]["address"],
                    'city' => $contact["postal_addresses"][0]["locality"],
                    'state_province_name' => $contact["postal_addresses"][0]["region"],
                    'country' => $contact["postal_addresses"][0]["country"],
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
