<?php
class OSDIQueueTasks {

	public static function AddContact($contacts) {
		// this expects a hal object that represents a page of contacts
		// where do u load an action?
		var_dump("appending this shit");
		try {
			$result = civicrm_api3('Contact', 'create', array(
				'first_name' => $contacts["first_name"],
				'last_name' => $contacts["last_name"],
				'contact_type' => 'Individual'
			));
		}
		catch (CiviCRM_API3_Exception $e) {
			$error = $e->getMessage();
		}
		var_dump("hello");
	}

}
?>
