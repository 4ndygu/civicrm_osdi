<?php

require_once __DIR__ . '/../vendor/autoload.php';
use Ekino\HalClient\Resource;

class ResourceStruct {

    public $resource;
    public $rule;
    public $filter;

    public function __construct($resource, $rule, $filter) {
        $this->rule = $rule;
        $this->resource = $resource;
        $this->filter = $filter;
    }
}
?>
