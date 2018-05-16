<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * OSDIQueue.Run API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 */
class api_v3_OSDIQueue_RunTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

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
   * Simple example test case.
   *
   * Note how the function name begins with the word "test".
   */
  public function testApiExample() {
    $queue = CRM_OSDIQueue_Helper::singleton()->getQueue();

    $_SESSION["extractors"] = NULL;

    // first import what you need
    $configs = include(__DIR__ . '/../../../../../CRM/Osdi/Page/config.php'); 
    $importresult = civicrm_api3('Importer', 'Import', array(
        'key' => $configs["key"]
    ));

    $result = civicrm_api3('Importer', 'Schedule');
    $this->assertEquals(35, $queue->numberOfItems());
    
    $result = civicrm_api3('OSDIQueue', 'Run');
    $this->assertEquals(0, $queue->numberOfItems());

    #TODO: Generate group with one user and check its presence in the DB
  }

}
