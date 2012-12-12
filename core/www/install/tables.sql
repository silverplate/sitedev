DROP TABLE IF EXISTS `~db prefix~bo_log`;

CREATE TABLE IF NOT EXISTS `~db prefix~bo_log` (
  `~db prefix~bo_log_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `~db prefix~bo_user_id` CHAR(10) NOT NULL,
  `~db prefix~bo_section_id` CHAR(10) NOT NULL,
  `section_name` VARCHAR(255) NOT NULL,
  `user_name` VARCHAR(255) NOT NULL,
  `user_ip` CHAR(15) NOT NULL,
  `user_agent` VARCHAR(255) NOT NULL,
  `request_uri` TEXT NOT NULL,
  `request_get` TEXT NOT NULL,
  `request_post` TEXT NOT NULL,
  `cookies` TEXT NOT NULL,
  `script_name` VARCHAR(50) NOT NULL,
  `action_id` SMALLINT UNSIGNED NOT NULL,
  `entry_id` VARCHAR(30) NOT NULL,
  `description` TEXT NOT NULL,
  `creation_date` DATETIME NOT NULL,
  PRIMARY KEY (`~db prefix~bo_log_id`))
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;


DROP TABLE IF EXISTS `~db prefix~bo_section`;

CREATE TABLE IF NOT EXISTS `~db prefix~bo_section` (
  `~db prefix~bo_section_id` CHAR(10) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `uri` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `is_published` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `sort_order` INT UNSIGNED NULL,
  PRIMARY KEY (`~db prefix~bo_section_id`))
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;


DROP TABLE IF EXISTS `~db prefix~bo_user`;

CREATE TABLE IF NOT EXISTS `~db prefix~bo_user` (
  `~db prefix~bo_user_id` CHAR(10) NOT NULL,
  `status_id` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `login` VARCHAR(30) NOT NULL,
  `passwd` VARCHAR(32) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `title` VARCHAR(255) NULL,
  `ip_restriction` TEXT NULL,
  `reminder_key` CHAR(30) NULL,
  `reminder_date` DATETIME NULL,
  PRIMARY KEY (`~db prefix~bo_user_id`))
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;


DROP TABLE IF EXISTS `~db prefix~bo_user_to_section`;

CREATE TABLE IF NOT EXISTS `~db prefix~bo_user_to_section` (
  `~db prefix~bo_user_id` CHAR(10) NOT NULL,
  `~db prefix~bo_section_id` CHAR(10) NOT NULL,
  PRIMARY KEY (`~db prefix~bo_user_id`, `~db prefix~bo_section_id`))
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;


DROP TABLE IF EXISTS `~db prefix~fo_data`;

CREATE TABLE IF NOT EXISTS `~db prefix~fo_data` (
  `~db prefix~fo_data_id` CHAR(30) NOT NULL,
  `~db prefix~fo_document_id` CHAR(30) NOT NULL,
  `~db prefix~fo_handler_id` CHAR(10) NULL,
  `~db prefix~fo_data_content_type_id` CHAR(10) NOT NULL,
  `auth_status_id` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `tag` VARCHAR(255) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NULL,
  `apply_type_id` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `is_mount` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_published` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `sort_order` INT UNSIGNED NULL,
  PRIMARY KEY (`~db prefix~fo_data_id`))
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;


DROP TABLE IF EXISTS `~db prefix~fo_data_content_type`;

CREATE TABLE IF NOT EXISTS `~db prefix~fo_data_content_type` (
  `~db prefix~fo_data_content_type_id` CHAR(10) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `is_published` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `sort_order` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`~db prefix~fo_data_content_type_id`))
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;


DROP TABLE IF EXISTS `~db prefix~fo_document`;

CREATE TABLE IF NOT EXISTS `~db prefix~fo_document` (
  `~db prefix~fo_document_id` CHAR(30) NOT NULL,
  `~db prefix~fo_handler_id` CHAR(10) NOT NULL,
  `~db prefix~fo_template_id` SMALLINT UNSIGNED NULL,
  `parent_id` CHAR(30) NULL,
  `auth_status_id` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `title` VARCHAR(255) NOT NULL,
  `title_compact` VARCHAR(255) NULL,
  `folder` VARCHAR(255) NOT NULL,
  `link` VARCHAR(255) NULL,
  `uri` VARCHAR(255) NOT NULL,
  `is_published` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `sort_order` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`~db prefix~fo_document_id`),
  UNIQUE INDEX `uq_~db prefix~fo_document_uri` (`uri` ASC))
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;


DROP TABLE IF EXISTS `~db prefix~fo_navigation`;

CREATE TABLE IF NOT EXISTS `~db prefix~fo_navigation` (
  `~db prefix~fo_navigation_id` CHAR(30) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `type` ENUM('list','tree') NOT NULL DEFAULT 'list',
  `is_published` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `sort_order` INT(11) UNSIGNED NULL,
  PRIMARY KEY (`~db prefix~fo_navigation_id`))
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;


DROP TABLE IF EXISTS `~db prefix~fo_document_to_navigation`;

CREATE TABLE IF NOT EXISTS `~db prefix~fo_document_to_navigation` (
  `~db prefix~fo_document_id` CHAR(30) NOT NULL,
  `~db prefix~fo_navigation_id` CHAR(30) NOT NULL,
  PRIMARY KEY (`~db prefix~fo_document_id`, `~db prefix~fo_navigation_id`))
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;


DROP TABLE IF EXISTS `~db prefix~fo_handler`;

CREATE TABLE IF NOT EXISTS `~db prefix~fo_handler` (
  `~db prefix~fo_handler_id` CHAR(10) NOT NULL,
  `type_id` SMALLINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `is_document_main` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_multiple` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_published` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`~db prefix~fo_handler_id`),
  UNIQUE INDEX `uq_~db prefix~fo_handler_type_id_filename` (`type_id` ASC, `filename` ASC))
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;


DROP TABLE IF EXISTS `~db prefix~fo_template` ;

CREATE TABLE IF NOT EXISTS `~db prefix~fo_template` (
  `~db prefix~fo_template_id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `is_document_main` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_multiple` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_published` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`~db prefix~fo_template_id`))
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;


DROP TABLE IF EXISTS `~db prefix~session`;

CREATE TABLE IF NOT EXISTS `~db prefix~session` (
  `~db prefix~session_id` CHAR(30) NOT NULL,
  `is_ip_match` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_logged_in` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `user_id` CHAR(30) NULL,
  `user_agent` CHAR(32) NULL,
  `user_ip` CHAR(15) NULL,
  `life_span` INT UNSIGNED NULL,
  `timeout` INT UNSIGNED NULL,
  `creation_date` DATETIME NULL,
  `last_impression_date` DATETIME NULL,
  `valid_date` DATETIME NULL,
  PRIMARY KEY (`~db prefix~session_id`))
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;


DROP TABLE IF EXISTS `~db prefix~session_param`;

CREATE TABLE IF NOT EXISTS `~db prefix~session_param` (
  `~db prefix~session_param_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `~db prefix~session_id` CHAR(30) NOT NULL,
  `name` VARCHAR(30) NOT NULL,
  `value` TEXT NOT NULL,
  PRIMARY KEY (`~db prefix~session_param_id`))
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;


DROP TABLE IF EXISTS `~db prefix~user`;

CREATE TABLE IF NOT EXISTS `~db prefix~user` (
  `~db prefix~user_id` CHAR(30) NOT NULL,
  `status_id` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `first_name` VARCHAR(255) NULL,
  `last_name` VARCHAR(255) NULL,
  `patronymic_name` VARCHAR(255) NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone_code` VARCHAR(255) NULL,
  `phone` VARCHAR(255) NULL,
  `passwd` CHAR(32) NOT NULL,
  `reminder_key` CHAR(30) NULL,
  `reminder_date` DATETIME NULL,
  `creation_date` DATETIME NULL,
  PRIMARY KEY (`~db prefix~user_id`),
  UNIQUE INDEX `uq_~db prefix~user_email` (`email` ASC))
ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;
