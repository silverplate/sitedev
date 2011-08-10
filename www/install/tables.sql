DROP TABLE IF EXISTS `~db prefix~bo_log`;
CREATE TABLE IF NOT EXISTS `~db prefix~bo_log` (
	`~db prefix~bo_log_id` int(11) NOT NULL auto_increment,
	`~db prefix~bo_user_id` varchar(10) NOT NULL default '',
	`~db prefix~bo_section_id` varchar(10) NOT NULL default '',
	`section_name` varchar(255) NOT NULL default '',
	`user_name` varchar(255) NOT NULL default '',
	`user_ip` varchar(15) NOT NULL default '',
	`user_agent` varchar(255) NOT NULL default '',
	`request_uri` text NOT NULL,
	`request_get` text NOT NULL,
	`request_post` text NOT NULL,
	`cookies` text NOT NULL,
	`script_name` varchar(50) NOT NULL default '',
	`action_id` int(11) NOT NULL default '0',
	`entry_id` varchar(30) NOT NULL default '',
	`description` text NOT NULL,
	`creation_date` datetime NOT NULL default '0000-00-00 00:00:00',
	PRIMARY KEY (`~db prefix~bo_log_id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `~db prefix~bo_section`;
CREATE TABLE IF NOT EXISTS `~db prefix~bo_section` (
	`~db prefix~bo_section_id` varchar(10) NOT NULL default '',
	`~db prefix~bo_section_group_id` varchar(10) NOT NULL default '',
	`title` varchar(255) NOT NULL default '',
	`uri` varchar(255) NOT NULL default '',
	`description` text NOT NULL,
	`is_published` tinyint(1) NOT NULL default '0',
	`sort_order` int(11) NOT NULL default '0',
	PRIMARY KEY (`~db prefix~bo_section_id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `~db prefix~bo_section_group`;
CREATE TABLE IF NOT EXISTS `~db prefix~bo_section_group` (
  `~db prefix~bo_section_group_id` varchar(10) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `sort_order` int(11) NOT NULL default '0',
  `is_published` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`~db prefix~bo_section_group_id`)
) TYPE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `~db prefix~bo_user`;
CREATE TABLE IF NOT EXISTS `~db prefix~bo_user` (
	`~db prefix~bo_user_id` varchar(10) NOT NULL default '',
	`status_id` int(11) NOT NULL default '1',
	`title` varchar(255) NOT NULL default '',
	`login` varchar(30) NOT NULL default '',
	`passwd` varchar(32) NOT NULL default '',
	`email` varchar(255) NOT NULL default '',
	`ip_restriction` text NOT NULL,
	`reminder_key` varchar(30) NOT NULL default '',
	`reminder_date` datetime NOT NULL default '0000-00-00 00:00:00',
	PRIMARY KEY (`~db prefix~bo_user_id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `~db prefix~bo_user_to_section`;
CREATE TABLE IF NOT EXISTS `~db prefix~bo_user_to_section` (
	`~db prefix~bo_user_id` char(10) NOT NULL default '',
	`~db prefix~bo_section_id` char(10) NOT NULL default '',
	PRIMARY KEY (`~db prefix~bo_user_id`,`~db prefix~bo_section_id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `~db prefix~fo_data`;
CREATE TABLE IF NOT EXISTS `~db prefix~fo_data` (
	`~db prefix~fo_data_id` char(30) NOT NULL default '',
	`~db prefix~fo_document_id` char(30) NOT NULL default '',
	`~db prefix~fo_handler_id` char(10) NOT NULL default '',
	`~db prefix~fo_data_content_type_id` varchar(10) NOT NULL default '',
	`auth_status_id` int(11) NOT NULL default '0',
	`tag` varchar(255) NOT NULL default '',
	`title` varchar(255) NOT NULL default '',
	`content` text NOT NULL,
	`apply_type_id` int(11) NOT NULL default '1',
	`is_mount` tinyint(1) NOT NULL default '0',
	`is_published` tinyint(1) NOT NULL default '0',
	`sort_order` int(11) NOT NULL default '0',
	PRIMARY KEY (`~db prefix~fo_data_id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `~db prefix~fo_data_content_type`;
CREATE TABLE IF NOT EXISTS `~db prefix~fo_data_content_type` (
	`~db prefix~fo_data_content_type_id` varchar(10) NOT NULL default '',
	`title` varchar(255) NOT NULL default '',
	`is_published` tinyint(1) NOT NULL default '0',
	`sort_order` int(11) NOT NULL default '0',
	PRIMARY KEY (`~db prefix~fo_data_content_type_id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `~db prefix~fo_document`;
CREATE TABLE IF NOT EXISTS `~db prefix~fo_document` (
	`~db prefix~fo_document_id` varchar(30) NOT NULL default '',
	`~db prefix~fo_handler_id` char(10) NOT NULL default '',
	`parent_id` varchar(30) NOT NULL default '',
	`auth_status_id` int(11) NOT NULL default '0',
	`title` varchar(255) NOT NULL default '',
	`title_compact` varchar(255) NOT NULL default '',
	`folder` varchar(255) NOT NULL default '',
	`link` varchar(255) NOT NULL default '',
	`uri` varchar(255) NOT NULL default '',
	`is_published` tinyint(1) NOT NULL default '0',
	`sort_order` int(11) NOT NULL default '0',
	PRIMARY KEY (`~db prefix~fo_document_id`),
	UNIQUE KEY `~db prefix~fo_document_uri` (`uri`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `~db prefix~fo_document_to_navigation`;
CREATE TABLE IF NOT EXISTS `~db prefix~fo_document_to_navigation` (
	`~db prefix~fo_document_id` char(30) NOT NULL default '',
	`~db prefix~fo_navigation_id` char(30) NOT NULL default '',
	PRIMARY KEY (`~db prefix~fo_document_id`,`~db prefix~fo_navigation_id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `~db prefix~fo_handler`;
CREATE TABLE IF NOT EXISTS `~db prefix~fo_handler` (
	`~db prefix~fo_handler_id` char(10) NOT NULL default '',
	`type_id` int(11) NOT NULL default '0',
	`title` varchar(255) NOT NULL default '',
	`filename` varchar(30) NOT NULL default '',
	`is_document_main` tinyint(1) NOT NULL default '0',
	`is_multiple` tinyint(1) NOT NULL default '0',
	`is_published` tinyint(1) NOT NULL default '0',
	PRIMARY KEY (`~db prefix~fo_handler_id`),
	UNIQUE KEY `~db prefix~fo_handler_filename` (`type_id`,`filename`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `~db prefix~fo_navigation`;
CREATE TABLE IF NOT EXISTS `~db prefix~fo_navigation` (
	`~db prefix~fo_navigation_id` varchar(30) NOT NULL default '',
	`name` varchar(255) NOT NULL default '',
	`title` varchar(255) NOT NULL default '',
	`type` enum('list','tree') NOT NULL default 'list',
	`is_published` tinyint(1) NOT NULL default '0',
	`sort_order` int(11) NOT NULL default '0',
	PRIMARY KEY (`~db prefix~fo_navigation_id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `~db prefix~session`;
CREATE TABLE IF NOT EXISTS `~db prefix~session` (
	`~db prefix~session_id` varchar(30) NOT NULL default '',
	`is_ip_match` tinyint(1) NOT NULL default '0',
	`is_logged_in` tinyint(1) NOT NULL default '0',
	`user_id` varchar(30) NOT NULL default '',
	`user_agent` varchar(32) NOT NULL default '',
	`user_ip` varchar(15) NOT NULL default '',
	`life_span` int(11) NOT NULL default '0',
	`timeout` int(11) NOT NULL default '0',
	`creation_date` datetime NOT NULL default '0000-00-00 00:00:00',
	`last_impression_date` datetime default NULL,
	`valid_date` datetime default NULL,
	PRIMARY KEY (`~db prefix~session_id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `~db prefix~session_param`;
CREATE TABLE IF NOT EXISTS `~db prefix~session_param` (
	`~db prefix~session_param_id` int(11) NOT NULL auto_increment,
	`~db prefix~session_id` varchar(30) NOT NULL default '0',
	`name` varchar(30) NOT NULL default '',
	`value` text NOT NULL,
	PRIMARY KEY (`~db prefix~session_param_id`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `~db prefix~user`;
CREATE TABLE IF NOT EXISTS `~db prefix~user` (
	`~db prefix~user_id` varchar(30) NOT NULL default '',
	`status_id` int(11) NOT NULL default '0',
	`first_name` varchar(255) NOT NULL default '',
	`last_name` varchar(255) NOT NULL default '',
	`patronymic_name` varchar(255) NOT NULL default '',
	`email` varchar(255) NOT NULL default '',
	`phone_code` varchar(255) NOT NULL default '',
	`phone` varchar(255) NOT NULL default '',
	`passwd` varchar(32) NOT NULL default '',
	`reminder_key` varchar(30) NOT NULL default '',
	`reminder_date` datetime NOT NULL default '0000-00-00 00:00:00',
	`creation_date` datetime NOT NULL default '0000-00-00 00:00:00',
	PRIMARY KEY (`~db prefix~user_id`),
	UNIQUE KEY `~db prefix~user_email` (`email`)
) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;