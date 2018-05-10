<?php
class OSDIQueueTasks {

	public static function AddContact($contacts) {
 		// this expects a hal object that represents a page of contacts
		// where do u load an action?
        foreach ($contacts as $contact) {
			try {
  				$contacts = civicrm_api3('Contact', 'create', array(
    				'first_name' => 'Alice',
    				'last_name' => 'Roberts',
  				));
			}
			catch (CiviCRM_API3_Exception $e) {
  				$error = $e->getMessage();
			}

        }
	}
 
}

?>
