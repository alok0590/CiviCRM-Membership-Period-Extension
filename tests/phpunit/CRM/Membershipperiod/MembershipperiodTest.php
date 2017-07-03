<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 *  Test class for Membershipperiod BAO and it's methods.
 * @group headless
 */
class CRM_Membershipperiod_MembershipperiodTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  private $test_contact_params = array(
    'contact_type' => 'Individual',
    'first_name' => 'Test',
    'last_name' => 'Contact',
  );

  private $test_membership_params = array(
    'membership_type_id' => '1',
    'contact_id' => '0',
  );

  private $test_membership_period_params = array(
    'membership_id' => '',
    'start_date' => '',
  );

  private $test_contact_id;
  private $test_membership_id;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
  * Settuing up dummy contact and membership to use during tests.
  */
  public function setUp() {
    $test_contact = \CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact', $this->test_contact_params);
    $this->test_contact_id = $test_contact->id;
    $this->test_membership_params["contact_id"] = $test_contact->id;
    $test_membership = \CRM_Core_DAO::createTestObject('CRM_Member_DAO_Membership', $this->test_membership_params);
    $this->test_membership_id = $test_membership->id;
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Test case when Membership period is being created with invalid membership.
   */

  public function testCreatewithinvalidmembership() {
      try {
          $membership_period = CRM_Membershipperiod_BAO_MembershipPeriod::create($this->test_membership_period_params);
      } catch(\Exception $e) {
          $this->assertEquals($e->getCode(), CRM_Membershipperiod_BAO_MembershipPeriod::$MEMBERSHIP_NOT_FOUND_ERROR_CODE);
      }
  }

  /**
   * Test case when Membership period is being created with invalid start date.
   */

  public function testCreatewithinvalidstartdate() {
      $this->test_membership_period_params["membership_id"] = $this->test_membership_id;
      try {
          $membership_period = CRM_Membershipperiod_BAO_MembershipPeriod::create($this->test_membership_period_params);
      } catch(\Exception $e) {
          $this->assertEquals($e->getCode(), CRM_Membershipperiod_BAO_MembershipPeriod::$MEMBERSHIP_INVALID_STARTDATE_ERROR_CODE);
      }
      $this->test_membership_period_params["start_date"] = "INVALID_DATE";
      try {
          $membership_period = CRM_Membershipperiod_BAO_MembershipPeriod::create($this->test_membership_period_params);
      } catch(\Exception $e) {
          $this->assertEquals($e->getCode(), CRM_Membershipperiod_BAO_MembershipPeriod::$MEMBERSHIP_INVALID_STARTDATE_ERROR_CODE);
      }
  }

  /**
   * Test case when Membership period is being created with valid start date and Membership (Minimum required values)
   */

  public function testCreatewithminimumvalues() {
      $this->test_membership_period_params["membership_id"] = $this->test_membership_id;
      $this->test_membership_period_params["start_date"] = "2017-01-01";
      $membership_period = CRM_Membershipperiod_BAO_MembershipPeriod::create($this->test_membership_period_params);
      $this->assertObjectHasAttribute('id', $membership_period);
  }

  /**
   * Test case when Membership period is being created with Invalid contribution_id;
   */

  public function testCreatewithinvalidcontribution() {
      $this->test_membership_period_params["membership_id"] = $this->test_membership_id;
      $this->test_membership_period_params["start_date"] = "2017-01-01";
      $this->test_membership_period_params["contribution_id"] = "";

      try {
          $membership_period = CRM_Membershipperiod_BAO_MembershipPeriod::create($this->test_membership_period_params);
      } catch(\Exception $e) {
          $this->assertEquals($e->getCode(), CRM_Membershipperiod_BAO_MembershipPeriod::$CONTRIBUTION_NOT_FOUND_ERROR_CODE);
      }
  }

  /**
   * Test case when Membership period is being created with invalid end date;
   */

  public function testCreatewithinvalidenddate() {
      $this->test_membership_period_params["membership_id"] = $this->test_membership_id;
      $this->test_membership_period_params["start_date"] = "2017-01-01";
      $this->test_membership_period_params["end_date"] = "INVALID_END_DATE";

      try {
          $membership_period = CRM_Membershipperiod_BAO_MembershipPeriod::create($this->test_membership_period_params);
      } catch(\Exception $e) {
          $this->assertEquals($e->getCode(), CRM_Membershipperiod_BAO_MembershipPeriod::$MEMBERSHIP_INVALID_ENDDATE_ERROR_CODE);
      }
  }

  /**
   * Test case when Membership period is being created with start_date > end_date
   */

  public function testCreatewithigreaterstartdate() {
      $this->test_membership_period_params["membership_id"] = $this->test_membership_id;
      $this->test_membership_period_params["start_date"] = "2017-01-01";
      $this->test_membership_period_params["end_date"] = "2016-12-31";

      try {
          $membership_period = CRM_Membershipperiod_BAO_MembershipPeriod::create($this->test_membership_period_params);
      } catch(\Exception $e) {
          $this->assertEquals($e->getCode(), CRM_Membershipperiod_BAO_MembershipPeriod::$MEMBERSHIP_INVALID_STARTDATE_ERROR_CODE);
      }
  }

  /**
   * Test case when Membership period is being created with valid start_date and end_date.
   */

  public function testCreatewithivalidstartandenddate() {
      $this->test_membership_period_params["membership_id"] = $this->test_membership_id;
      $this->test_membership_period_params["start_date"] = "2017-01-01";
      $this->test_membership_period_params["end_date"] = "2018-01-01";
      $membership_period = CRM_Membershipperiod_BAO_MembershipPeriod::create($this->test_membership_period_params);
      $this->assertObjectHasAttribute('id', $membership_period);
  }

  /**
   * Test case when Membership period is being created with all valid details.
   */

  public function testCreatewithiallvaliddetails() {
      $this->test_membership_period_params["membership_id"] = $this->test_membership_id;
      $this->test_membership_period_params["start_date"] = "2017-01-01";
      $this->test_membership_period_params["end_date"] = "2018-01-01";
      $test_contribution = \CRM_Core_DAO::createTestObject('CRM_Contribute_DAO_Contribution', array(
          "financial_type_id"=>1,
          "total_amount"=>50,
          "contact_id"=>$this->test_contact_id
      ));
      $this->test_membership_period_params["contribution_id"] = $test_contribution->id;
      $membership_period = CRM_Membershipperiod_BAO_MembershipPeriod::create($this->test_membership_period_params);
      $this->assertObjectHasAttribute('id', $membership_period);
  }
}
