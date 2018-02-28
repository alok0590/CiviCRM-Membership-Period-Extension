<?php

class CRM_Membershipperiod_Page_MembershipPeriod extends CRM_Core_Page {

  public function run() {
    $membership_id = CRM_Utils_Request::retrieve('id', 'Positive',
        $this, FALSE, 0
    );
    $membership = civicrm_api3("Membership",'get',array(
        "id"=>$membership_id,
        "return" => array("contact_id.display_name","membership_type_id.name"),
        "sequential"=>1
    ));
    if($membership["count"]==0) {
        throw new Exception(ts('Could not find membership'));
    }
    $membership = $membership["values"][0];
    CRM_Utils_System::setTitle(ts('Membership Periods of ').$membership["contact_id.display_name"].' ('.$membership["membership_type_id.name"].ts(' Membership').')');
    $membership_periods = civicrm_api3("MembershipPeriod",'get',array(
        "membership_id"=>$membership_id,
        "sequential" => 1,
        "return" => array("start_date","end_date", "membership_id.contact_id","renew_timestamp","contribution_id","contribution_id.total_amount","contribution_id.currency"),
        "options" => array(
          "sort" => "id DESC"
        )
    ));
    $column_headers = array(
        "Start Date",
        "End Date",
        "Renewed On",
        "Contribution",
    );
    if($membership_periods["count"]!=0) {
          $membership_periods = $membership_periods["values"];
    } else {
        $membership_periods = array();
    }

    foreach($membership_periods as $index=>$membership_period) {
        if(isset($membership_period['contribution_id'])) {
            $contributionUrl = CRM_Utils_System::url("civicrm/contact/view/contribution",
                'reset=1&action=view&cid=' . $membership_period['membership_id.contact_id'] . '&id=' . $membership_period['contribution_id']
            );
            $membership_periods[$index]["contribution_url"] = $contributionUrl;
            $membership_periods[$index]["total_contribution_amount"] = $membership_period["contribution_id.total_amount"];
            $membership_periods[$index]["contribution_amount"] = $membership_period["contribution_id.currency"];
        }
    }
    $this->assign('columnHeaders', $column_headers);
    $this->assign('membershipPeriods', $membership_periods);
    $this->assign('currentTime', date('Y-m-d H:i:s'));

    parent::run();
  }
}
