<?php

// Include everybody in pipeline.
foreach (glob("../pipeline/*.php") as $filename) {
  require_once $filename;
}
/**
 *
 */
abstract class AbstractContactImporter {

  /**
   * Endpoint.
   */
  protected $endpoint;
  protected $schema;
  protected $apikey;
  protected $client;
  protected $entrypoint;

  /**
   * For raw http requests.
   */
  protected $raw_client;

  /**
   *
   */
  public function pull_endpoint_data($filter = NULL, $rule = NULL, $group = -1, $zone = 0) {
    $counter = 0;
    // Create an entry point to retrieve the data
    // return the main resource.
    $resource_root = $this->entrypoint->get();

    if ($resource_root->get("osdi:people") == NULL) {
      $counter = 1;
      return $counter;
    }

    // Shunt the root into the queue.
    if (!isset($_SESSION["extractors"])) {
      $_SESSION["extractors"] = array();
    }

    $final_data = new ResourceStruct($resource_root, $rule, $filter, $group, $zone, $this->apikey, $this->endpoint);
    $_SESSION["extractors"][] = serialize($final_data);

    return $counter;
  }

  /**
   *
   */
  abstract public function update_endpoint_data($date, $filter = NULL, $rule = NULL, $group = -1);

  /**
   *
   */
  abstract public static function validate_endpoint_data($data);

  /**
   *
   */
  abstract public static function is_newest_endpoint_data($data, $date, $zone);

  /**
   *
   */
  abstract public static function add_task_with_page($page, $rule = NULL, $groupid = -1, $apikey, $endpoint);

}
