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
  protected $endpath;

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
    $extractors = Civi::settings()->get("extractors");
    if ($extractors == NULL) {
      $extractors = array();
    }

    $entryobject = array();

    $entryobject["endpoint"] = $this->endpoint . $this->endpath;
    $entryobject["headers"] = $this->headers;

    $final_data = new ResourceStruct($entryobject, $rule, $filter, $group, $zone, $this->apikey, $this->endpoint);

    $extractors[] = serialize($final_data);

    Civi::settings()->set("extractors", $extractors);

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
