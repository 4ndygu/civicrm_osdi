<?php

require_once __DIR__ . '/../vendor/autoload.php';
use Ekino\HalClient\Resource;

class PeopleStruct {

    public $person;
    public $rule;

    public function __construct($person, $rule) {
        $this->person = $person;
        $this->rule = $rule;
    }
}
?>
