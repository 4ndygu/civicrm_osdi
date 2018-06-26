<?php
use CRM_Osdi_ExtensionUtil as E;

class CRM_Osdi_Page_Mapping extends CRM_Core_Page {

  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(E::ts('Mapping'));

    // grab fields for column1
    $names = array();

    $firstitemid = civicrm_api3('Mapping', 'get', array(
        'name' => "osdi_contact",
    ));

    $firstitem = civicrm_api3('MappingField', 'get', array(
        'mapping_id' => $firstitemid["id"],
        'sequential' => 1,
        'options' => ['limit' => 0],
    ));

    foreach ($firstitem["values"] as $key => $value) {
        $item = array();
        $item["first"] = $value["name"];
        $item["second"] = $value["value"];
        $item["id"] = $value["id"];
        $names[] = $item;
    }

    $this->assign('names', $names);

    parent::run();
  }

}
