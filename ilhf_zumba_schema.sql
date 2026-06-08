-- ILHF Santo Niño Zumba Session Scheduling App
-- Full MySQL Schema
-- Target DBMS: MySQL 8+

CREATE DATABASE IF NOT EXISTS ilhf_zumba_scheduling
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ilhf_zumba_scheduling;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `ACTIVITY_LOG`;
DROP TABLE IF EXISTS `ROLE_PERMISSION`;
DROP TABLE IF EXISTS `PERMISSION`;
DROP TABLE IF EXISTS `ACTION_TYPE`;
DROP TABLE IF EXISTS `MODULE`;
DROP TABLE IF EXISTS `MEMBERSHIP_APPLICATION`;
DROP TABLE IF EXISTS `APPLICATION_STATUS`;
DROP TABLE IF EXISTS `SESSION`;
DROP TABLE IF EXISTS `SESSION_STATUS`;
DROP TABLE IF EXISTS `MEMBER`;
DROP TABLE IF EXISTS `MEMBER_STATUS`;
DROP TABLE IF EXISTS `EDUCATIONAL_ATTAINMENT`;
DROP TABLE IF EXISTS `GENDER`;
DROP TABLE IF EXISTS `CHAPTER`;
DROP TABLE IF EXISTS `USER_ACCOUNT`;
DROP TABLE IF EXISTS `ROLE`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `ROLE` (
    `role_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(50) NOT NULL,
    CONSTRAINT `uq_role_role_name` UNIQUE (`role_name`)
) ENGINE=InnoDB;

CREATE TABLE `USER_ACCOUNT` (
    `user_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    CONSTRAINT `uq_user_account_username` UNIQUE (`username`),
    CONSTRAINT `fk_user_account_role`
        FOREIGN KEY (`role_id`) REFERENCES `ROLE` (`role_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `CHAPTER` (
    `chapter_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `chapter_name` VARCHAR(100) NOT NULL,
    CONSTRAINT `uq_chapter_chapter_name` UNIQUE (`chapter_name`)
) ENGINE=InnoDB;

CREATE TABLE `GENDER` (
    `gender_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `gender_name` VARCHAR(50) NOT NULL,
    CONSTRAINT `uq_gender_gender_name` UNIQUE (`gender_name`)
) ENGINE=InnoDB;

CREATE TABLE `EDUCATIONAL_ATTAINMENT` (
    `educational_attainment_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `level_name` VARCHAR(100) NOT NULL,
    CONSTRAINT `uq_educational_attainment_level_name` UNIQUE (`level_name`)
) ENGINE=InnoDB;

CREATE TABLE `MEMBER_STATUS` (
    `member_status_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `status_name` VARCHAR(50) NOT NULL,
    CONSTRAINT `uq_member_status_status_name` UNIQUE (`status_name`)
) ENGINE=InnoDB;

CREATE TABLE `APPLICATION_STATUS` (
    `application_status_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `status_name` VARCHAR(50) NOT NULL,
    CONSTRAINT `uq_application_status_status_name` UNIQUE (`status_name`)
) ENGINE=InnoDB;

CREATE TABLE `MEMBER` (
    `member_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `middle_name` VARCHAR(100),
    `last_name` VARCHAR(100) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `birthday` DATE NOT NULL,
    `member_status_id` INT UNSIGNED NOT NULL,
    `phone_number` VARCHAR(20) NOT NULL,
    `barangay_id` INT UNSIGNED NOT NULL,
    `chapter_id` INT UNSIGNED NOT NULL,
    `gender_id` INT UNSIGNED NOT NULL,
    `educational_attainment_id` INT UNSIGNED NOT NULL,
    `primary_school` VARCHAR(150),
    `primary_year_graduated` YEAR,
    `secondary_school` VARCHAR(150),
    `secondary_year_graduated` YEAR,
    `college_school` VARCHAR(150),
    `college_year_graduated` YEAR,
    CONSTRAINT `uq_member_user_id` UNIQUE (`user_id`),
    CONSTRAINT `fk_member_user_account`
        FOREIGN KEY (`user_id`) REFERENCES `USER_ACCOUNT` (`user_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `fk_member_chapter`
        FOREIGN KEY (`chapter_id`) REFERENCES `CHAPTER` (`chapter_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_member_gender`
        FOREIGN KEY (`gender_id`) REFERENCES `GENDER` (`gender_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_member_member_status`
        FOREIGN KEY (`member_status_id`) REFERENCES `MEMBER_STATUS` (`member_status_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_member_educational_attainment`
        FOREIGN KEY (`educational_attainment_id`) REFERENCES `EDUCATIONAL_ATTAINMENT` (`educational_attainment_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `MEMBERSHIP_APPLICATION` (
    `application_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `desired_username` VARCHAR(50) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `middle_name` VARCHAR(100),
    `last_name` VARCHAR(100) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `birthday` DATE NOT NULL,
    `member_status_id` INT UNSIGNED NOT NULL,
    `phone_number` VARCHAR(20) NOT NULL,
    `barangay_id` INT UNSIGNED NOT NULL,
    `chapter_id` INT UNSIGNED NOT NULL,
    `gender_id` INT UNSIGNED NOT NULL,
    `educational_attainment_id` INT UNSIGNED NOT NULL,
    `primary_school` VARCHAR(150),
    `primary_year_graduated` YEAR,
    `secondary_school` VARCHAR(150),
    `secondary_year_graduated` YEAR,
    `college_school` VARCHAR(150),
    `college_year_graduated` YEAR,
    `application_status_id` INT UNSIGNED NOT NULL,
    `reviewed_by_user_id` INT UNSIGNED,
    `reviewed_at` DATETIME,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `uq_membership_application_username` UNIQUE (`desired_username`),
    CONSTRAINT `fk_membership_application_member_status`
        FOREIGN KEY (`member_status_id`) REFERENCES `MEMBER_STATUS` (`member_status_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_membership_application_chapter`
        FOREIGN KEY (`chapter_id`) REFERENCES `CHAPTER` (`chapter_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_membership_application_gender`
        FOREIGN KEY (`gender_id`) REFERENCES `GENDER` (`gender_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_membership_application_education`
        FOREIGN KEY (`educational_attainment_id`) REFERENCES `EDUCATIONAL_ATTAINMENT` (`educational_attainment_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_membership_application_status`
        FOREIGN KEY (`application_status_id`) REFERENCES `APPLICATION_STATUS` (`application_status_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_membership_application_reviewer`
        FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `USER_ACCOUNT` (`user_id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `SESSION_STATUS` (
    `status_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `status_name` VARCHAR(50) NOT NULL,
    CONSTRAINT `uq_session_status_status_name` UNIQUE (`status_name`)
) ENGINE=InnoDB;

CREATE TABLE `SESSION` (
    `session_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_title` VARCHAR(150) NOT NULL,
    `session_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `location` VARCHAR(150) NOT NULL,
    `instructor_user_id` INT UNSIGNED NOT NULL,
    `status_id` INT UNSIGNED NOT NULL,
    CONSTRAINT `fk_session_instructor_user_account`
        FOREIGN KEY (`instructor_user_id`) REFERENCES `USER_ACCOUNT` (`user_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_session_session_status`
        FOREIGN KEY (`status_id`) REFERENCES `SESSION_STATUS` (`status_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `chk_session_time` CHECK (`end_time` > `start_time`)
) ENGINE=InnoDB;

CREATE TABLE `MODULE` (
    `module_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `module_name` VARCHAR(100) NOT NULL,
    CONSTRAINT `uq_module_module_name` UNIQUE (`module_name`)
) ENGINE=InnoDB;

CREATE TABLE `ACTION_TYPE` (
    `action_type_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `action_name` VARCHAR(50) NOT NULL,
    CONSTRAINT `uq_action_type_action_name` UNIQUE (`action_name`)
) ENGINE=InnoDB;

CREATE TABLE `PERMISSION` (
    `permission_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `module_id` INT UNSIGNED NOT NULL,
    `action_type_id` INT UNSIGNED NOT NULL,
    CONSTRAINT `uq_permission_module_action` UNIQUE (`module_id`, `action_type_id`),
    CONSTRAINT `fk_permission_module`
        FOREIGN KEY (`module_id`) REFERENCES `MODULE` (`module_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `fk_permission_action_type`
        FOREIGN KEY (`action_type_id`) REFERENCES `ACTION_TYPE` (`action_type_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `ROLE_PERMISSION` (
    `role_permission_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    CONSTRAINT `uq_role_permission_role_permission` UNIQUE (`role_id`, `permission_id`),
    CONSTRAINT `fk_role_permission_role`
        FOREIGN KEY (`role_id`) REFERENCES `ROLE` (`role_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `fk_role_permission_permission`
        FOREIGN KEY (`permission_id`) REFERENCES `PERMISSION` (`permission_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `ACTIVITY_LOG` (
    `log_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `log_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_activity_log_user_account`
        FOREIGN KEY (`user_id`) REFERENCES `USER_ACCOUNT` (`user_id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX `idx_user_account_role_id` ON `USER_ACCOUNT` (`role_id`);
CREATE INDEX `idx_member_barangay_id` ON `MEMBER` (`barangay_id`);
CREATE INDEX `idx_member_chapter_id` ON `MEMBER` (`chapter_id`);
CREATE INDEX `idx_member_gender_id` ON `MEMBER` (`gender_id`);
CREATE INDEX `idx_member_member_status_id` ON `MEMBER` (`member_status_id`);
CREATE INDEX `idx_member_educational_attainment_id` ON `MEMBER` (`educational_attainment_id`);
CREATE INDEX `idx_membership_application_barangay_id` ON `MEMBERSHIP_APPLICATION` (`barangay_id`);
CREATE INDEX `idx_membership_application_status_id` ON `MEMBERSHIP_APPLICATION` (`application_status_id`);
CREATE INDEX `idx_membership_application_created_at` ON `MEMBERSHIP_APPLICATION` (`created_at`);
CREATE INDEX `idx_session_instructor_user_id` ON `SESSION` (`instructor_user_id`);
CREATE INDEX `idx_session_status_id` ON `SESSION` (`status_id`);
CREATE INDEX `idx_activity_log_user_id` ON `ACTIVITY_LOG` (`user_id`);
CREATE INDEX `idx_activity_log_log_date` ON `ACTIVITY_LOG` (`log_date`);

INSERT INTO `ROLE` (`role_name`) VALUES
    ('Super Admin'),
    ('Admin'),
    ('Instructor'),
    ('Member');

INSERT INTO `ACTION_TYPE` (`action_name`) VALUES
    ('CREATE'),
    ('READ'),
    ('UPDATE'),
    ('DELETE');

INSERT INTO `MODULE` (`module_name`) VALUES
    ('USER_ACCOUNT'),
    ('ROLE'),
    ('PERMISSION'),
    ('MEMBER'),
    ('APPLICATION'),
    ('SESSION'),
    ('ACTIVITY_LOG'),
    ('CHAPTER'),
    ('GENDER'),
    ('EDUCATIONAL_ATTAINMENT');

INSERT INTO `PERMISSION` (`module_id`, `action_type_id`)
SELECT `MODULE`.`module_id`, `ACTION_TYPE`.`action_type_id`
FROM `MODULE`
CROSS JOIN `ACTION_TYPE`;

INSERT INTO `ROLE_PERMISSION` (`role_id`, `permission_id`)
SELECT `ROLE`.`role_id`, `PERMISSION`.`permission_id`
FROM `ROLE`
CROSS JOIN `PERMISSION`
WHERE `ROLE`.`role_name` = 'Super Admin';

INSERT INTO `ROLE_PERMISSION` (`role_id`, `permission_id`)
SELECT `ROLE`.`role_id`, `PERMISSION`.`permission_id`
FROM `ROLE`
INNER JOIN `PERMISSION` ON 1 = 1
INNER JOIN `MODULE` ON `MODULE`.`module_id` = `PERMISSION`.`module_id`
INNER JOIN `ACTION_TYPE` ON `ACTION_TYPE`.`action_type_id` = `PERMISSION`.`action_type_id`
WHERE `ROLE`.`role_name` = 'Admin'
  AND `MODULE`.`module_name` = 'APPLICATION'
  AND `ACTION_TYPE`.`action_name` IN ('READ', 'UPDATE');

INSERT INTO `CHAPTER` (`chapter_name`) VALUES
    ('Santo Niño Chapter');

INSERT INTO `GENDER` (`gender_name`) VALUES
    ('Male'),
    ('Female'),
    ('Prefer not to say');

INSERT INTO `EDUCATIONAL_ATTAINMENT` (`level_name`) VALUES
    ('Elementary'),
    ('High School'),
    ('Senior High School'),
    ('College'),
    ('Vocational'),
    ('Postgraduate');

INSERT INTO `MEMBER_STATUS` (`status_name`) VALUES
    ('Single'),
    ('Married'),
    ('Widowed'),
    ('Separated'),
    ('Other');

INSERT INTO `APPLICATION_STATUS` (`status_name`) VALUES
    ('Pending'),
    ('Approved'),
    ('Rejected');

INSERT INTO `SESSION_STATUS` (`status_name`) VALUES
    ('Incoming'),
    ('In Progress'),
    ('Finished');
