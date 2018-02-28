<?php

class CRM_Membershipperiod_BAO_MembershipPeriod extends CRM_Membershipperiod_DAO_MembershipPeriod {

  /**
   * Create a new Membership Period based on array-data
   *
   * This function would throw exception if membership record not found for given membership_id.
   *
   * @param array $params key-value pairs
   * @return CRM_Membershipperiod_DAO_MembershipPeriod|NULL
   *
   */

  public static $MEMBERSHIP_NOT_FOUND_ERROR_CODE = 101;
  public static $MEMBERSHIP_INVALID_STARTDATE_ERROR_CODE = 102;
  public static $CONTRIBUTION_NOT_FOUND_ERROR_CODE = 103;
  public static $MEMBERSHIP_INVALID_ENDDATE_ERROR_CODE = 104;

  public static function create($params) {
      self::validateAndThrowException($params);
      $className = 'CRM_Membershipperiod_DAO_MembershipPeriod';
      $entityName = 'MembershipPeriod';
      $hook = empty($params['id']) ? 'create' : 'edit';
      $params["sequential"] = 1;
      CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
      $instance = new $className();
      $instance->copyValues($params);
      $instance->save();
      CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);
      return $instance;
  }


  private static function validateAndThrowException(&$params) {
        if(!array_key_exists("end_date", $params) || $params["end_date"]=="null" || $params["end_date"]=="") {
            $params["end_date"] = NULL;
        }
        if(!array_key_exists("start_date", $params) || $params["start_date"]=="null" || $params["start_date"]=="") {
            $params["start_date"] = NULL;
        }
        $membership_count = civicrm_api3("Membership",'getcount',array(
            "id"=>$params["membership_id"]
        ));
        if($membership_count==0) {
            throw new API_Exception(ts('Could not find membership'),self::$MEMBERSHIP_NOT_FOUND_ERROR_CODE);
        }
        if($params["start_date"]==NULL) {
            throw new API_Exception(ts('Membership start date cannot be null.'),self::$MEMBERSHIP_INVALID_STARTDATE_ERROR_CODE);
        }
        if(!(self::isValidDate($params["start_date"]))) {
            throw new API_Exception(ts('Membership start date should be a valid date string.'),self::$MEMBERSHIP_INVALID_STARTDATE_ERROR_CODE);
        }
        if(array_key_exists("contribution_id", $params)) {
            $contribution_count = civicrm_api3("Contribution",'getcount',array(
                "id"=>$params["contribution_id"]
            ));
            if($contribution_count==0) {
                throw new API_Exception(ts('Could not find contribution'),self::$CONTRIBUTION_NOT_FOUND_ERROR_CODE);
            }
        }

        if($params["end_date"]!=NULL && !(self::isValidDate($params["end_date"]))) {
            throw new API_Exception(ts('Membership end date should be a valid date string.'),self::$MEMBERSHIP_INVALID_ENDDATE_ERROR_CODE);
        }

        if($params["end_date"]!=NULL) {
            $membership_start_date = new DateTime(date('Y-m-d',strtotime($params["start_date"])));
            $membership_end_date = new DateTime(date('Y-m-d',strtotime($params["end_date"])));

            if($membership_end_date<$membership_start_date) {
                throw new API_Exception(ts('Membership start date should be smaller then end date.'),self::$MEMBERSHIP_INVALID_STARTDATE_ERROR_CODE);
            }
        }
  }

  private static function isValidDate($datestring) {
      return (bool)strtotime($datestring);
  }


  /**
  * Create or Update Membership Period based on array-data
  * Considering following use cases.
  * 1. Membership is created.
  *    -  In this case simply add the Start date and End date as membership Period.
  *
  * 2. Membership is renewed.
  *    - In this case take the last record of membership period. Add one day in end date of it and make it as Start date of the new period.
  *        End date would same from current membership record.
  *
  * 3. Membership start date and/or end date is manually edited
  *    - Take the last record of membership period and update the start date and end date from current membership record.
  *
  *
  * @param array $params key-value pairs
  * @return NULL
  *
  */

  public static function createOrUpdate($params) {
      if($params["end_date"]=="null") {
          $params["end_date"] = NULL;
      }
      $membership_id = $params["membership_id"];
      $membership_duration_term = civicrm_api3("Membership",'getsingle',array(
          "id"=>$membership_id,
          "return" => array("membership_type_id.duration_interval", "membership_type_id.duration_unit")
      ));
      if($membership_duration_term["membership_type_id.duration_unit"]=="lifetime") {
          // If membership is of lifetime, No need to record membership period.
          return;
      }

      $membership = civicrm_api3("MembershipPeriod",'get',array(
          "membership_id"=>$membership_id,
          "sequential" => 1,
          "options" => array(
            "sort" => "id DESC"
          )
      ));
      if($membership["count"]==0) {
          /*
          * No previous membership periods found, just creating a new one.
          * Either user is editing/renewing the membership first time after installing extension or
          * simply creating new membership.
          */
          return self::create($params);
      }

      /*
      * Taking two membership periods
      * 1. First period when the membership was created.
      * 2. Last membership period
      *
      * We will need first one for the reference of Start date, to compare it or in case user manually change it.
      */
      $membershiplast=$membership["values"][0];
      $membershipfirst = $membership["values"][$membership["count"]-1];

      $membership = array();

      $membership["membership_id"] = $membershiplast["membership_id"];
      $membership["id"] = $membershiplast["id"];

      $membership["start_date"] = self::getPlaindate($membershipfirst["start_date"]);
      $membership["end_date"] = self::getPlaindate($membershiplast["end_date"]);
      $params["start_date"] = self::getPlaindate($params["start_date"]);
      $params["end_date"] = self::getPlaindate($params["end_date"]);

      if($membership["start_date"]==$params["start_date"] && $membership["end_date"]==$params["end_date"]) {
          /*
          * Just return when Start date or End date has not been changed.
          * This is case when user updated other details of the membership keeing the membership period details intact.
          */
          return;
      }

      $membership_start_date = DateTime::createFromFormat('YmdHis', $membership["start_date"]);
      $membership_end_date = DateTime::createFromFormat('YmdHis', $membership["end_date"]);
      $params_start_date = DateTime::createFromFormat('YmdHis', $params["start_date"]);
      $params_end_date = DateTime::createFromFormat('YmdHis', $params["end_date"]);

      /*
      * Now we know the membership has been edited/renewed.
      * First check and compare the end date, if it is renewed create new record in membership period.
      * If it is edited, update the last membership period record.
      * If membership's start date is changed, simply update the First record from all the membership periods.
      */

      $startdatechanged = false;
      if($membership_start_date!=$params_start_date) {
          $membershipfirst["start_date"] = $params["start_date"];
          $startdatechanged = true;
          self::create($membershipfirst);
      }

      if($membership_end_date != $params_end_date) {
            $updateenddate = true;
            if($membership_end_date<$params_end_date) {
                $datediff=$params_end_date->diff($membership_end_date);

                $duration_unit = $membership_duration_term["membership_type_id.duration_unit"];
                $duration_interval = $membership_duration_term["membership_type_id.duration_interval"];

                if($duration_unit=="year" && $datediff->y == $duration_interval && $datediff->m ==0 && $datediff->d == 0) {
                    $updateenddate = false;
                }

                else if($duration_unit=="month" && $datediff->m ==$duration_interval && $datediff->d == 0) {
                    $updateenddate = false;
                }

                else  if($duration_unit=="day" && $datediff->d == $duration_interval) {
                    $updateenddate = false;
                }
            }
            if($updateenddate) {
                if($startdatechanged && $membership["id"] == $membershipfirst["id"]) {
                    $membership["start_date"] = $membershipfirst["start_date"];
                }
                $membership["start_date"] = $membershiplast["start_date"];
                $membership["end_date"] = $params_end_date->format("YmdHis");
                self::create($membership);
            } else {
                $membership_period_start_date = clone $membership_end_date;
                $membership_period_start_date->modify("+1 day");

                $membership_period_params = array(
                    "membership_id"=>$params["membership_id"],
                    "start_date"=>$membership_period_start_date->format("YmdHis"),
                    "end_date"=>$params_end_date->format("YmdHis"),
                );
                self::create($membership_period_params);
            }
      }
  }

  /**
  * Update Membership Period record with Contribution
  *
  * We will take the last added period for the given membership_id, check if the contribution_id is NULL or not.
  * If contribution_id is NULL then update the contribution_id, Otherwise return.
  *
  * @param array $params key-value pairs
  * @return NULL
  *
  */
  public static function updateContribution($params) {
      $membership_id = $params["membership_id"];
      $membership_period = civicrm_api3("MembershipPeriod",'get',array(
          "membership_id"=>$membership_id,
          "sequential" => 1,
          "options" => array(
            "limit" => 1,
            "sort" => "id DESC"
          )
      ));

      if($membership_period["count"]==0) {
          /*
          * No previous membership periods found, no need to do anything.
          */
          return self::create($params);
      }

      $membership_period=$membership_period["values"][0];
      if(!array_key_exists("contribution_id", $membership_period) || $membership_period["contribution_id"]==NULL) {
          $membership_period["contribution_id"] = $params["contribution_id"];
      }

      self::create($membership_period);
  }


  /**
  * Implementing Util function to convert Date String to a YmdHis format.
  *
  * Function will accept the date in either Y-m-d or Ymd format and will return the
  * YmdHis formatted date. This helps to compare different date strings easily.
  *
  * @param $date in (Y-m-d) or (Ymd)
  * @return Date formatted in YmdHis
  *
  */
  private static function getPlaindate($date) {
      if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$date)) {
            $date = DateTime::createFromFormat('Y-m-d', $date);
            $date->setTime(0,0,0);
            $date=$date->format("YmdHis");
      }
      if (preg_match("/^[0-9]{8}$/",$date)) {
            $date = DateTime::createFromFormat('Ymd', $date);
            $date->setTime(0,0,0);
            $date=$date->format("YmdHis");
      }
      return $date;
  }
}
