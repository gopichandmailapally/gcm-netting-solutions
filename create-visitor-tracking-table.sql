-- Create visitor tracking table
CREATE TABLE IF NOT EXISTS `visitor_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(100) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `browser` varchar(50) DEFAULT NULL,
  `browser_version` varchar(20) DEFAULT NULL,
  `device_type` varchar(20) DEFAULT 'Desktop',
  `os` varchar(50) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Unknown',
  `country_code` varchar(2) DEFAULT 'XX',
  `region` varchar(100) DEFAULT '',
  `city` varchar(100) DEFAULT 'Unknown',
  `latitude` decimal(10,7) DEFAULT 0.0000000,
  `longitude` decimal(10,7) DEFAULT 0.0000000,
  `timezone` varchar(50) DEFAULT '',
  `isp` varchar(100) DEFAULT '',
  `current_page` varchar(500) DEFAULT NULL,
  `entry_page` varchar(500) DEFAULT NULL,
  `referrer` varchar(500) DEFAULT '',
  `page_views` int(11) DEFAULT 1,
  `total_time_spent` int(11) DEFAULT 0,
  `is_online` tinyint(1) DEFAULT 1,
  `first_visit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `is_online` (`is_online`),
  KEY `last_activity` (`last_activity`),
  KEY `country_code` (`country_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create visitor heatmap table
CREATE TABLE IF NOT EXISTS `visitor_heatmap` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country_code` varchar(2) NOT NULL,
  `country_name` varchar(100) NOT NULL,
  `visitor_count` int(11) DEFAULT 1,
  `last_visit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `country_code` (`country_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create page views table
CREATE TABLE IF NOT EXISTS `page_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(100) NOT NULL,
  `page_url` varchar(500) NOT NULL,
  `page_title` varchar(200) DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create visitor actions table
CREATE TABLE IF NOT EXISTS `visitor_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(100) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_data` text,
  `page_url` varchar(500) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
