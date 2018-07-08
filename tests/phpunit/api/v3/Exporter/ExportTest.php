<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Exporter.Export API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 */
class api_v3_Exporter_ExportTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

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
  public function testApiNormalEmpty() {
    $result = civicrm_api3('Exporter', 'Export', array('object' => 'contact'));
    $this->assertEquals(strpos($result['values']["_links"]['self'], "page=0") != false, true);
    $this->assertEquals(strpos($result['values']["_links"]['next'], "page=1") != false, true);
  }

  /**
   * Invalid page test
   *
   * Note how the function name begins with the word "test".
   */
  public function testApiNormalPageOverflow() {
	$add= civicrm_api3('Contact', 'create', array(
		'first_name' => "AAA",
		'last_name' => "BBB",
		'email' => "CCC",
		'display_name' => "DDD",
		'contact_type' => 'Individual'
	));

    $result = civicrm_api3('Exporter', 'Export', array('object' => 'contact', 'page' => 10));
    $this->assertEquals(sizeof($result['values']["embedded"]["osdi:people"]), 0);
  }

  /**
   * grabs single user
   *
   * Note how the function name begins with the word "test".
   */
  public function testSpecificUser() {
    $result = civicrm_api3('Exporter', 'Export', array('object' => 'contact', 'id' => 25));
    $this->assertEquals(sizeof($result['values']["embedded"]["osdi:people"]), 0);
  }

}
