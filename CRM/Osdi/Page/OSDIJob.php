<?php
use CRM_Osdi_ExtensionUtil as E;

class CRM_Osdi_Page_OSDIJob extends CRM_Core_Page {

  public function run() {

    $jobresults = civicrm_api3('Job', 'get', [
      'sequential' => 1,
      'name' => ['LIKE' => "OSDISYNC_IMPORT_%"]
    ]);

    $jobs = array();
    foreach ($jobresults["values"] as $job) {
      $metadata = array();

      // split and extract name
      $metadata["name"] = substr($job["name"], 16);

      // extract jobID
      $metadata["id_import"] = $job["id"];

      // extract joblog
      $metadata["id_import_log"] = "No runs yet!";
      $joblogresults = civicrm_api3("JobLog", "get", array(
        "job_id" => $job["id"],
        "options" => array("sort" => "run_time DESC")
      ));

      if (sizeof($joblogresults["values"]) != 0) {
        $metadata["id_import_log"] = substr($joblogresults["values"][0]["data"], 0, 500);
      }

      // extract groupID
      $metadata["group_id"] = 0;
      $metadata["group_name"] = "butt";
      $parameters = explode("\n", $job["parameters"]);
      foreach($parameters as $parameter) {
        $params = explode("=", $parameter);
        var_dump($params);
        if ($params[0] == "group") {
          if ($params[1] != "") {
            $results = civicrm_api3("Group", "get", [
              "sequential" => 1,
              "id" => $params[1]
            ]);
            if (sizeof($results["values"] != 0)) {
              $metadata["group_id"] = $results["values"][0]["id"];
              $metadata["group_name"] = $results["values"][0]["name"];
            }
          }
        }
      }

      // load the export value
      $exportjobresults = civicrm_api3('Job', 'get', [
        'sequential' => 1,
        'name' => "OSDISYNC_EXPORT_" . $metadata["name"]
      ]);

      if (sizeof($exportjobresults["values"]) != 0) {
        $metadata["id_export"] = $exportjobresults["values"][0]["id"];
      }

      $metadata["id_export_log"] = "No runs yet!";
      $joblogresults = civicrm_api3("JobLog", "get", array(
        "job_id" => $metadata["id_export"],
        "options" => array("sort" => "run_time DESC")
      ));

      if (sizeof($joblogresults["values"]) != 0) {
        $metadata["id_export_log"] = substr($joblogresults["values"][0]["data"], 0, 500);
      }

      // load all the metadata into the array
      $jobs[] = $metadata;
    }

    $this->assign('jobs', $jobs);

    parent::run();
  }

}