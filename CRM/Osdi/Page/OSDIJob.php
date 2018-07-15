<?php
use CRM_Osdi_ExtensionUtil as E;

class CRM_Osdi_Page_OSDIJob extends CRM_Core_Page {

  public function run() {

    $jobresults = civicrm_api3('Job', 'get', [
      'sequential' => 1,
      'name' => ['LIKE' => "OSDIJOB_"]
    ]);

    $jobs = array();
    foreach ($jobresults["values"] as $jobs) {
      // load all the metadata into the array
    }

    $this->assign('jobs', $jobs);

    parent::run();
  }

}
