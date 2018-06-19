<?php

require_once __DIR__ . '/../vendor/autoload.php';
use Ekino\HalClient\Resource;

class ResourceStruct {

    public $resource;
    public $rule;
    public $filter;
    public $group;
    public $zone;
    public $apikey;

    public function __construct($resource, $rule, $filter, $group, $zone, $apikey) {
        $this->rule = $rule;
        $this->resource = $resource;
        $this->filter = $filter;
        $this->group = $group;
	$this->zone = $zone;
	$this->apikey = $apikey;
    }
}
?>
