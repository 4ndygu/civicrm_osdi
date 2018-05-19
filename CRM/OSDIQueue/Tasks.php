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

        $rule = $contactresource->rule;
        if ($rule == NULL) return True;

		return True;
	}
}
?>
