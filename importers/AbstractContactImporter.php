<?php

//include everybody in pipeline
foreach (glob("../pipeline/*.php") as $filename)
{
    require_once($filename);
}

abstract class AbstractContactImporter
{

    // endpoint
    protected $endpoint;
    protected $schema;
    protected $apikey;
    protected $client;
    protected $entrypoint;
   
    // for raw http requests
    protected $raw_client;

    public function pull_endpoint_data($filter = NULL, $rule = NULL, $group = -1, $zone = 0) {
        $counter = 0;

	// create an entry point to retrieve the data
	$resource_root = $this->entrypoint->get();// return the main resource

        if ($resource_root->get("osdi:people") == NULL) {
            $counter = 1;
            return $counter;
	}

        // shunt the root into the queue
        if (!isset($_SESSION["extractors"])) {
            $_SESSION["extractors"] = array();
	} 


        $final_data = new ResourceStruct($resource_root, $rule, $filter, $group, $zone, $this->apikey);
	//$_SESSION["extractors"][] = serialize($final_data);

        return $counter;
    }

    abstract public function update_endpoint_data($date, $filter = NULL, $rule = NULL, $group = -1);
    abstract public static function validate_endpoint_data($data);
    abstract public static function is_newest_endpoint_data($data, $date, $zone);
	abstract public static function add_task_with_page($page, $rule = NULL, $groupid = -1, $apikey);
}
?>

