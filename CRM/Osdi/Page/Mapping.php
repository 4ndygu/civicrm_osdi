<?php

use CRM_Osdi_ExtensionUtil as E;

/**
 *
 */
class CRM_Osdi_Page_Mapping extends CRM_Core_Page {

  /**
   *
   */
  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml.
    CRM_Utils_System::setTitle(E::ts('Mapping'));

    // Grab fields for column1.
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
      $addOn = "";
      if (substr($value["name"], 0, 6) == "custom") {
        // Grab the actual name too.
        $result = civicrm_api3('CustomField', 'get', array(
          'sequential' => 1,
          'id' => (int) substr($value["name"], 7),
        ));

        $addOn = "/" . $result["values"][0]["name"];
      }
      $item = array();
      $item["first"] = $value["name"];
      $item["firstname"] = $value["name"] . $addOn;
      $item["second"] = $value["value"];
      $item["id"] = $value["id"];
      $names[] = $item;
    }

    $this->assign('names', $names);

    parent::run();
  }

}
