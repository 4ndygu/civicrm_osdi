<?php
use CRM_Osdi_ExtensionUtil as E;

class CRM_Osdi_Page_OSDIRunner extends CRM_Core_Page {

	public function run() {
		// Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
		CRM_Utils_System::setTitle(E::ts('OSDIRunner'));

		//retrieve the queue
		$queue = CRM_OSDIQueue_Helper::singleton()->getQueue();
		$runner = new CRM_Queue_Runner(array(
			'title' => ts('OSDI Queue Page runner'), //title fo the queue
			'queue' => $queue, //the queue object
			'errorMode' => CRM_Queue_Runner::ERROR_ABORT, //abort upon error and keep task in queue
			'onEnd' => array('CRM_OSDIQueue_Runner', 'onEnd'), //method which is called as soon as the queue is finished
			'onEndUrl' => CRM_Utils_System::url('civicrm/xxxx', 'reset=0'), //go to page after all tasks are finished
		));

		$runner->runAll(); // does not return

		parent::run();
	}

	static function onEnd(CRM_Queue_TaskContext $ctx) {
		//set a status message for the user
		CRM_Core_Session::setStatus('All tasks in queue are executes', 'Queue', 'success');
	}
}
