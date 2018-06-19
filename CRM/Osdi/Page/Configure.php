<?php
use CRM_Osdi_ExtensionUtil as E;

class CRM_Osdi_Page_Configure extends CRM_Core_Page {

  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(E::ts('Configure'));

    parent::run();
  }

}
