-- ============================================
-- REAL VISITOR TRACKING SYSTEM
-- No fake data - tracks actual visitors with geolocation
-- ============================================

-- Visitor Sessions Table
CREATE TABLE IF NOT EXISTS `visitor_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL UNIQUE,
  `ip_address` varchar(45) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `country_code` varchar(10) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `latitude` decimal(10, 8) DEFAULT NULL,
  `longitude` decimal(11, 8) DEFAULT NULL,
  `timezone` varchar(50) DEFAULT NULL,
  `isp` varchar(255) DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet','bot') DEFAULT 'desktop',
  `browser` varchar(100) DEFAULT NULL,
  `browser_version` varchar(50) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `os_version` varchar(50) DEFAULT NULL,
  `user_agent` text,
  `referrer` varchar(500) DEFAULT NULL,
  `landing_page` varchar(500) DEFAULT NULL,
  `first_visit` datetime NOT NULL,
  `last_activity` datetime NOT NULL,
  `total_page_views` int(11) DEFAULT 1,
  `total_time_spent` int(11) DEFAULT 0 COMMENT 'in seconds',
  `is_bot` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_first_visit` (`first_visit`),
  KEY `idx_city` (`city`),
  KEY `idx_is_bot` (`is_bot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Page Views Table
CREATE TABLE IF NOT EXISTS `page_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL,
  `page_url` varchar(500) NOT NULL,
  `page_title` varchar(255) DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `time_on_page` int(11) DEFAULT 0 COMMENT 'in seconds',
  `scroll_depth` int(11) DEFAULT 0 COMMENT 'percentage',
  `viewed_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_page_url` (`page_url`(255)),
  KEY `idx_viewed_at` (`viewed_at`),
  FOREIGN KEY (`session_id`) REFERENCES `visitor_sessions`(`session_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Visitor Actions Table
CREATE TABLE IF NOT EXISTS `visitor_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL,
  `action_type` enum('click','form_submit','phone_click','whatsapp_click','download','scroll','exit') NOT NULL,
  `action_data` text COMMENT 'JSON data about the action',
  `page_url` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`session_id`) REFERENCES `visitor_sessions`(`session_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily Statistics Table (for quick access)
CREATE TABLE IF NOT EXISTS `daily_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stat_date` date NOT NULL UNIQUE,
  `total_visitors` int(11) DEFAULT 0,
  `unique_visitors` int(11) DEFAULT 0,
  `total_page_views` int(11) DEFAULT 0,
  `bounce_rate` decimal(5, 2) DEFAULT 0.00,
  `avg_time_on_site` int(11) DEFAULT 0 COMMENT 'in seconds',
  `mobile_visitors` int(11) DEFAULT 0,
  `desktop_visitors` int(11) DEFAULT 0,
  `tablet_visitors` int(11) DEFAULT 0,
  `bot_visitors` int(11) DEFAULT 0,
  `top_pages` text COMMENT 'JSON array of top pages',
  `top_cities` text COMMENT 'JSON array of top cities',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stat_date` (`stat_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Geolocation Cache Table (to avoid repeated API calls)
CREATE TABLE IF NOT EXISTS `ip_geolocation_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL UNIQUE,
  `country` varchar(100) DEFAULT NULL,
  `country_code` varchar(10) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `latitude` decimal(10, 8) DEFAULT NULL,
  `longitude` decimal(11, 8) DEFAULT NULL,
  `timezone` varchar(50) DEFAULT NULL,
  `isp` varchar(255) DEFAULT NULL,
  `cached_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create view for real-time analytics
CREATE OR REPLACE VIEW v_realtime_visitors AS
SELECT 
    vs.session_id,
    vs.ip_address,
    vs.city,
    vs.region,
    vs.country,
    vs.device_type,
    vs.browser,
    vs.last_activity,
    vs.total_page_views,
    TIMESTAMPDIFF(MINUTE, vs.last_activity, NOW()) as minutes_since_last_activity,
    (SELECT page_url FROM page_views WHERE session_id = vs.session_id ORDER BY viewed_at DESC LIMIT 1) as current_page
FROM visitor_sessions vs
WHERE vs.last_activity >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
  AND vs.is_bot = 0
ORDER BY vs.last_activity DESC;

-- Create view for today's statistics
CREATE OR REPLACE VIEW v_today_statistics AS
SELECT 
    COUNT(DISTINCT session_id) as total_visitors,
    COUNT(DISTINCT ip_address) as unique_ips,
    SUM(total_page_views) as total_page_views,
    ROUND(AVG(total_page_views), 2) as avg_pages_per_visitor,
    ROUND(AVG(total_time_spent), 0) as avg_time_on_site,
    SUM(CASE WHEN device_type = 'mobile' THEN 1 ELSE 0 END) as mobile_visitors,
    SUM(CASE WHEN device_type = 'desktop' THEN 1 ELSE 0 END) as desktop_visitors,
    SUM(CASE WHEN device_type = 'tablet' THEN 1 ELSE 0 END) as tablet_visitors,
    SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) as bot_visitors
FROM visitor_sessions
WHERE DATE(first_visit) = CURDATE();

-- Create view for top pages today
CREATE OR REPLACE VIEW v_top_pages_today AS
SELECT 
    page_url,
    COUNT(*) as view_count,
    COUNT(DISTINCT session_id) as unique_visitors,
    ROUND(AVG(time_on_page), 0) as avg_time_on_page
FROM page_views
WHERE DATE(viewed_at) = CURDATE()
GROUP BY page_url
ORDER BY view_count DESC
LIMIT 20;

-- Create view for top cities
CREATE OR REPLACE VIEW v_top_cities AS
SELECT 
    city,
    region,
    country,
    COUNT(DISTINCT session_id) as visitor_count,
    SUM(total_page_views) as total_page_views
FROM visitor_sessions
WHERE DATE(first_visit) = CURDATE()
  AND city IS NOT NULL
  AND is_bot = 0
GROUP BY city, region, country
ORDER BY visitor_count DESC
LIMIT 20;
