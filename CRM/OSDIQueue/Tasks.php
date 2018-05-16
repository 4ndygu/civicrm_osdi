<?php
class CRM_OSDIQueue_Tasks {

	public static function AddContact(CRM_Queue_TaskContext $context, $contact) {
		// this expects a hal object that represents a page of contacts
		// where do u load an action?
		CRM_Core_Session::setStatus('executing add contact task', 'Queue task', 'success');
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

		return True;
	}
}
?>
