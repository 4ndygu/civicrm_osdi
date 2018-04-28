<?php
abstract class AbstractContactImporter
{

    // endpoint
    private $endpoint;
    private $schema;
    private $apikey;

    abstract public function pull_endpoint_data();
    abstract public function validate_endpoint_data($data);
    abstract public function format_data($data);
    abstract public function store_data($data);
}
?>

