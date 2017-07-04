# CiviCRM Membership Period Extension
A CiviCRM extension to track Membership period/term when it is created or renewed.

## Issue with core
Currently, when a membership is renewed in CiviCRM the “end date” field on the membership itself is extended by the length of the membership as defined in CiviCRM membership type configuration but no record of the actual length of any one period or term is recorded. As such it is not possible to see how many “terms” or “periods” of membership a contact may have had.

## Solution
This extension would record Membership period/term whenever it is creted/edited/renewed, every membership period is attached to a contribution record if any contribution is made for membership creation/renewal.

After installing this extension admin would be able to see terms of particular membership of a contact. Like this,

<img src="https://user-images.githubusercontent.com/13497994/27820238-67cafb9a-60bb-11e7-9905-bcc3227caeba.png" alt="Membership Periods" width="70%">

We can access the followin things from above section,

- Membership terms
- Membership renewal date
- Contribution for each term
- Link to contribution record


## Implementation of the Solution
### Creating required entity/table
First of all, When this extension is installed we create a seprate table to manage membership periods having Start date, End date, Renewal date and reference to the membership. There is a column to reference contribution if any payment is made against membership.

We DROP this added table when an extension is uninstalled.


### Implementing Hook

Now, We need to know when a Membership is created/renewed. For that I've used [hook_civicrm_post] hook. We do not perform any operation if the hooked entity is not "Membership" or "MembershipPayment". And we do not consider delete operation for each, as the reference to the membership is CASCADE DELETE.

If the hooked entity is "Membership", we get start date, end date and id of the membership. Array of these values are passed in "createOrUpdate" method of the "Membershipperiod" BAO.


### Implementing createOrUpdate BAO Method

This method would first check if the membership which is getting created/edited/renewed is not a "lifetime" membership. We as for now do not consider this case. If it's not we proceed further.

Now there are different situations/cases when Membership is created/edited/renewed.

  - First time creating/renewing memership period after installing this extension, in this case total record of particular membership would be zero and we simply create new record.
  - Membership is edited without changing start date or end date, We ignore this case as it won't effect the terms.
  - Membership is edited by chaning start date or end date or both. If start date is edited we update the start date of first record of particular membership. If end date is edited we update the last record of the particular membership.
  - Membership is renewed, In this case we simply create new record of the membership period of particular membership.

In above all cases whenever Membershipperiod is created/edited, we call create method of MembershipPeriod BAO. This method would first do the data validations. There are few things we've considered for data validation.

  - A valid Memberhsip object must exists for membership id
  - Start date of the membership must be valid date string.
  - If contribution id is given, it must be a valid contribution.
  - If End date is not NULL, it must be valid date string.

Failing to any of the case, method would throw an Exception.


### Recording contribution along with Membership period
If the membership is created/renewed with contribution we get the "MembershipPayment" post hook. In that we get contribution id and membership id for which contribution is made. Array of these two values are passed to updateContribution method of the MembershipPeriod BAO.

In this method we pick the last membership period record of particular membership and update it with the contribution id, if there are no membership period found for particular membership we create new one.


## How to check membership periods?
We can check membership periods from two places,

1. Membership search page
2. Memberships tab of the contact page

For both of the options we have a link named "Membership Periods", clicking on which opens up a pop-up box showing membership terms of particular membership.

![How to check?](https://user-images.githubusercontent.com/13497994/27820577-d5287a0e-60bc-11e7-805b-1ed59ea8ac46.png)

## Authors
- [Alok Patel]

## License
This project is licensed under the GNU Affero General Public License v3.0 - see the [LICENSE.txt] file for details

[hook_civicrm_post]: https://docs.civicrm.org/dev/en/stable/hooks/hook_civicrm_post/
[Alok Patel]: https://github.com/alok0590
[LICENSE.txt]: https://github.com/alok0590/Membership-Period-Extension/blob/master/LICENSE.txt
