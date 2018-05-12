<?php
class OSDIQueueTasks {

	public static function AddContact($contacts) {
		// this expects a hal object that represents a page of contacts
		// where do u load an action?
        foreach ($contacts as $contact) {
			try {
				$contacts = civicrm_api3('Contact', 'create', array(
					'first_name' => $contact["first_name"],
					'last_name' => $contact["last_name"],
				));
			}
			catch (CiviCRM_API3_Exception $e) {
				$error = $e->getMessage();
			}

        }
	}

}

?>
