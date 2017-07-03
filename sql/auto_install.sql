-- /*******************************************************
-- *
-- * civicrm_membership_period
-- *
-- * This entity will store membership periods of all memberships.
-- *
-- *******************************************************/
CREATE TABLE IF NOT EXISTS `civicrm_membership_period` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique MembershipPeriod ID',
     `start_date` date    COMMENT 'Beginning of membership period.',
     `end_date` date    COMMENT 'Membership period expire date.',
     `renew_timestamp` timestamp DEFAULT CURRENT_TIMESTAMP COMMENT 'Membership renew timestamp.',
     `updated_at` timestamp DEFAULT 0 ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated datetime of the record.',
     `membership_id` int unsigned    COMMENT 'FK to Membership',
     `contribution_id` int unsigned    COMMENT 'FK to Contribution' ,
      PRIMARY KEY (`id`),
      CONSTRAINT FK_civicrm_membership_period_membership_id FOREIGN KEY (`membership_id`) REFERENCES `civicrm_membership`(`id`) ON DELETE CASCADE,
      CONSTRAINT FK_civicrm_membership_period_contribution_id FOREIGN KEY (`contribution_id`) REFERENCES `civicrm_contribution`(`id`) ON DELETE SET NULL
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
