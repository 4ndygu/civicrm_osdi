<?php
use CRM_Osdi_ExtensionUtil as E;

class CRM_Osdi_Page_OSDIResponse extends CRM_Core_Page {

  public function run() {

    $result = civicrm_api3('Exporter', 'export', array(
        'object' => 'Contact'
    ));

    print json_encode($result["values"]);

    CRM_Utils_System::civiExit();
    parent::run();
  }

}
