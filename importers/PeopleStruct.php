<?php

require_once __DIR__ . '/../vendor/autoload.php';
use Ekino\HalClient\Resource;

class PeopleStruct {

    public $person;
    public $rule;
    public $groupid;
    public $apikey;

    public function __construct($person, $rule, $groupid, $apikey) {
        $this->person = $person;
        $this->rule = $rule;
	$this->groupid = $groupid;
	$this->apikey = $apikey;
    }
}
?>
