<?php

require_once __DIR__ . "/../../importers/PeopleStruct.php";

class CRM_OSDIQueue_Tasks {

	public static function AddContact(CRM_Queue_TaskContext $context, $contact_wrapper) {
		// this expects a hal object that represents a page of contacts
		// where do u load an action?
		CRM_Core_Session::setStatus('executing add contact task', 'Queue task', 'success');

        $contactresource = unserialize($contact_wrapper);
        $contact = $contactresource->person;

		try {
			$result = civicrm_api3('Contact', 'create', array(
				'first_name' => $contact["given_name"],
				'last_name' => $contact["family_name"],
				'email' => $contact["email_addresses"][0]["address"],
				'display_name' => $contact["family_name"],
				'contact_type' => 'Individual'
			));
		}
		catch (Exception $e) {
			return False;
		}
	}

    public static function MergeContacts(CRM_Queue_TaskContext $context, $contact_wrapper) {
        var_dump("merging");
        $contactresource = unserialize($contact_wrapper);
        $rule = $contactresource->rule;
        if ($rule == NULL or $rule == -1) return True;

        $dupes = CRM_Dedupe_Finder::dupes($rule);
        //$result = CRM_Dedupe_Merger::merge($dupes);

		return True;
    }
}
?>
