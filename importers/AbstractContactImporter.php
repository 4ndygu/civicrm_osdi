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

    abstract public function pull_endpoint_data($filter = NULL, $rule = NULL);
    abstract public function update_endpoint_data($date, $filter = NULL, $rule = NULL, $group = -1);
    abstract public static function validate_endpoint_data($data);
    abstract public static function is_newest_endpoint_data($data, $date, $zone);
	abstract public static function add_task_with_page($page, $rule = NULL, $groupid = -1, $apikey);
}
?>

