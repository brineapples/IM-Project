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
DROP TABLE IF EXISTS `SESSION`;
DROP TABLE IF EXISTS `BENEFICIARY`;
DROP TABLE IF EXISTS `MEMBER`;
DROP TABLE IF EXISTS `RELATIONSHIP_TYPE`;
DROP TABLE IF EXISTS `EDUCATIONAL_ATTAINMENT`;
DROP TABLE IF EXISTS `GENDER`;
DROP TABLE IF EXISTS `CHAPTER`;
DROP TABLE IF EXISTS `BARANGAY`;
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

CREATE TABLE `BARANGAY` (
    `barangay_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `barangay_name` VARCHAR(100) NOT NULL,
    CONSTRAINT `uq_barangay_barangay_name` UNIQUE (`barangay_name`)
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

CREATE TABLE `RELATIONSHIP_TYPE` (
    `relationship_type_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `relationship_name` VARCHAR(50) NOT NULL,
    CONSTRAINT `uq_relationship_type_relationship_name` UNIQUE (`relationship_name`)
) ENGINE=InnoDB;

CREATE TABLE `MEMBER` (
    `control_no` VARCHAR(50) PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `middle_name` VARCHAR(100),
    `last_name` VARCHAR(100) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `birthday` DATE NOT NULL,
    `phone_number` VARCHAR(20) NOT NULL,
    `barangay_id` INT UNSIGNED NOT NULL,
    `chapter_id` INT UNSIGNED NOT NULL,
    `gender_id` INT UNSIGNED NOT NULL,
    `educational_attainment_id` INT UNSIGNED NOT NULL,
    CONSTRAINT `uq_member_user_id` UNIQUE (`user_id`),
    CONSTRAINT `fk_member_user_account`
        FOREIGN KEY (`user_id`) REFERENCES `USER_ACCOUNT` (`user_id`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `fk_member_barangay`
        FOREIGN KEY (`barangay_id`) REFERENCES `BARANGAY` (`barangay_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_member_chapter`
        FOREIGN KEY (`chapter_id`) REFERENCES `CHAPTER` (`chapter_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_member_gender`
        FOREIGN KEY (`gender_id`) REFERENCES `GENDER` (`gender_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT `fk_member_educational_attainment`
        FOREIGN KEY (`educational_attainment_id`) REFERENCES `EDUCATIONAL_ATTAINMENT` (`educational_attainment_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `BENEFICIARY` (
    `beneficiary_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `control_no` VARCHAR(50) NOT NULL,
    `beneficiary_name` VARCHAR(150) NOT NULL,
    `relationship_type_id` INT UNSIGNED NOT NULL,
    CONSTRAINT `fk_beneficiary_member`
        FOREIGN KEY (`control_no`) REFERENCES `MEMBER` (`control_no`)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT `fk_beneficiary_relationship_type`
        FOREIGN KEY (`relationship_type_id`) REFERENCES `RELATIONSHIP_TYPE` (`relationship_type_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `SESSION` (
    `session_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `session_title` VARCHAR(150) NOT NULL,
    `session_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `location` VARCHAR(150) NOT NULL,
    `instructor_user_id` INT UNSIGNED NOT NULL,
    CONSTRAINT `fk_session_instructor_user_account`
        FOREIGN KEY (`instructor_user_id`) REFERENCES `USER_ACCOUNT` (`user_id`)
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
    `user_id` INT UNSIGNED NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `log_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_activity_log_user_account`
        FOREIGN KEY (`user_id`) REFERENCES `USER_ACCOUNT` (`user_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX `idx_user_account_role_id` ON `USER_ACCOUNT` (`role_id`);
CREATE INDEX `idx_member_barangay_id` ON `MEMBER` (`barangay_id`);
CREATE INDEX `idx_member_chapter_id` ON `MEMBER` (`chapter_id`);
CREATE INDEX `idx_member_gender_id` ON `MEMBER` (`gender_id`);
CREATE INDEX `idx_member_educational_attainment_id` ON `MEMBER` (`educational_attainment_id`);
CREATE INDEX `idx_beneficiary_control_no` ON `BENEFICIARY` (`control_no`);
CREATE INDEX `idx_session_instructor_user_id` ON `SESSION` (`instructor_user_id`);
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
    ('DELETE'),
    ('VIEW');

INSERT INTO `MODULE` (`module_name`) VALUES
    ('USER_ACCOUNT'),
    ('ROLE'),
    ('PERMISSION'),
    ('MEMBER'),
    ('BENEFICIARY'),
    ('SESSION'),
    ('ACTIVITY_LOG'),
    ('BARANGAY'),
    ('CHAPTER'),
    ('GENDER'),
    ('EDUCATIONAL_ATTAINMENT'),
    ('RELATIONSHIP_TYPE');

INSERT INTO `PERMISSION` (`module_id`, `action_type_id`)
SELECT `MODULE`.`module_id`, `ACTION_TYPE`.`action_type_id`
FROM `MODULE`
CROSS JOIN `ACTION_TYPE`;

INSERT INTO `ROLE_PERMISSION` (`role_id`, `permission_id`)
SELECT `ROLE`.`role_id`, `PERMISSION`.`permission_id`
FROM `ROLE`
CROSS JOIN `PERMISSION`
WHERE `ROLE`.`role_name` = 'Super Admin';

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

INSERT INTO `RELATIONSHIP_TYPE` (`relationship_name`) VALUES
    ('Parent'),
    ('Spouse'),
    ('Child'),
    ('Sibling'),
    ('Relative'),
    ('Guardian'),
    ('Other');
