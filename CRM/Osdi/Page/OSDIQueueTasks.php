<?php
class OSDIQueueTasks {

	public static function AddContact(CRM_Queue_TaskContext $context, $contact) {
		// this expects a hal object that represents a page of contacts
		// where do u load an action?
		CRM_Core_Session::setStatus('executing add contact task', 'Queue task', 'success');

		/*try {
			$properties = $contacts->getProperties();
			$result = civicrm_api3('Contact', 'create', array(
				'first_name' => $properties["first_name"],
				'last_name' => $properties["last_name"],
				'email' => $properties["email_addresses"][0]["address"],
				'display_name' => $properties["last_name"],
				'contact_type' => 'Individual'
			));
		}
		catch (CiviCRM_API3_Exception $e) {
			$error = $e->getMessage();
		}*/

		return True;
	}

}
?>
