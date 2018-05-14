<?php

// include the importer classes
//require_once __DIR__ . '/OSDIQueueHelper.php';
require_once __DIR__ . '/../../../importers/ActionNetworkContactImporter.php';

use CRM_Osdi_ExtensionUtil as E;

class CRM_Osdi_Page_OSDI extends CRM_Core_Page {

  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(E::ts('OSDI'));

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));
	$x = new ActionNetworkContactImporter("https://actionnetwork.org/api/v2", "x", "rolling these again");
	$x->pull_endpoint_data();

    parent::run();
  }

}
