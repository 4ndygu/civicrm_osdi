<?php

// include the importer classes
//require_once __DIR__ . '/OSDIQueueHelper.php';
require_once __DIR__ . '/../../../importers/ActionNetworkContactImporter.php';

use CRM_Osdi_ExtensionUtil as E;

class CRM_Osdi_Page_OSDI extends CRM_Core_Page {

  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(E::ts('OSDI'));

	$configs = include('config.php');

	$this->assign('taskNumber', '0');

	$result = civicrm_api3('Importer', 'import', array(
		'key' => $configs["key"]
	));
	$this->assign('taskNumber', $result["values"]["count"]);

	//$importer = new ActionNetworkContactImporter("https://actionnetwork.org/api/v2", "x", $configs["key"]);
	//$count = $importer->pull_endpoint_data();

	$this->assign('completed', "yes");
    parent::run();
  }

}
