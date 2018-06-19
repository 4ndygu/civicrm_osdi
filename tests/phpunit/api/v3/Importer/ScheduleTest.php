<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Importer.Schedule API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 */
class api_v3_Importer_ScheduleTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  /**
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   * See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   */
  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * The setup() method is executed before the test is executed (optional).
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * The tearDown() method is executed after the test was executed (optional)
   * This can be used for cleanup.
   */
  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Simple test of extractors being empty or null
   *
   */
  public function testEmptyExtractors() {
	$_SESSION["extractors"] = NULL;

    $result = civicrm_api3('Importer', 'Schedule');
    $this->assertEquals('no variable set', $result["values"]["status"]);

	$_SESSION["extractors"] = array();

    $result = civicrm_api3('Importer', 'Schedule');
    $this->assertEquals('no variable set', $result["values"]["status"]);
  }

  /**
   * Test of a malformed input in _SESSION
   */
  public function testMalformedExtractors() {
	$_SESSION["extractors"] = array();
	$_SESSION["extractors"][] = array("blah" => "blah");	

    $result = civicrm_api3('Importer', 'Schedule');
    $this->assertEquals('malformed data', $result["values"]["status"]);
  }

  /**
   * Test of the regular output
   */
  public function testRegularFlow() {
    $_SESSION["extractors"] = NULL;

    // first import what you need
    $configs = include(__DIR__ . '/../../../../../CRM/Osdi/Page/config.php'); 
    $importresult = civicrm_api3('Importer', 'Import', array(
        'key' => $configs["key"]
    ));

    $result = civicrm_api3('Importer', 'Schedule');

    $this->assertEquals('completed', $result["values"]["status"]);
	$this->assertEmpty($_SESSION["extractors"]);

	// check if we in the queue
	$queue = CRM_OSDIQueue_Helper::singleton()->getQueue();
	$this->assertEquals(36, $queue->numberOfItems());	
  }

  /**
   * Test inport folter for validated data / unvalidated data
   */
  public function testValidateFilter() {
    $_SESSION["extractors"] = NULL; 
    // first import what you need
    $configs = include(__DIR__ . '/../../../../../CRM/Osdi/Page/config.php'); 
    $importresult = civicrm_api3('Importer', 'Import', array(
        'key' => $configs["key"],
        'required' => "NOTAREALVALUE"
    ));

    $result = civicrm_api3('Importer', 'Schedule');

    $this->assertEquals('completed', $result["values"]["status"]);
	$this->assertEmpty($_SESSION["extractors"]);

	// check if we in the queue // theres one job to merge via the rule only
	$queue = CRM_OSDIQueue_Helper::singleton()->getQueue();
	$this->assertEquals(0, $queue->numberOfItems());	
  }

  /**
   * Test that rule specified is added as a job
   */
  public function testRuleInsertedJob() {
    $_SESSION["extractors"] = NULL; 
    // first import what you need
    $configs = include(__DIR__ . '/../../../../../CRM/Osdi/Page/config.php'); 
    $importresult = civicrm_api3('Importer', 'Import', array(
        'key' => $configs["key"],
        'required' => "NOTAREALVALUE",
        'rule' => 1
    ));

    $result = civicrm_api3('Importer', 'Schedule');

    $this->assertEquals('completed', $result["values"]["status"]);
	$this->assertEmpty($_SESSION["extractors"]);

	// check if we in the queue // theres one job to merge via the rule only
	$queue = CRM_OSDIQueue_Helper::singleton()->getQueue();
	$this->assertEquals(1, $queue->numberOfItems());	
  }

  /**
   * Tests a huge object
   */
  public function testLargeFlow() {
    //TODO: Get a huge object
  }
}
