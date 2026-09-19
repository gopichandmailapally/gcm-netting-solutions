-- SEO Rankings Table for Real Google Position Data
-- Stores actual Google search positions, not simulated data

CREATE TABLE IF NOT EXISTS `seo_rankings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_url` varchar(500) NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0 COMMENT '0 = not found, 1-100 = actual position',
  `previous_position` int(11) DEFAULT NULL,
  `clicks` int(11) DEFAULT 0,
  `impressions` int(11) DEFAULT 0,
  `ctr` decimal(5,2) DEFAULT 0.00,
  `search_page` int(11) GENERATED ALWAYS AS (CEILING(position / 10)) STORED,
  `is_real_data` tinyint(1) DEFAULT 0 COMMENT '1 = real checked data, 0 = simulated',
  `last_updated` datetime NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_keyword_page` (`keyword`, `page_url`),
  KEY `idx_position` (`position`),
  KEY `idx_keyword` (`keyword`),
  KEY `idx_page_url` (`page_url`),
  KEY `idx_is_real_data` (`is_real_data`),
  KEY `idx_last_updated` (`last_updated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SEO Improvement Recommendations Table
CREATE TABLE IF NOT EXISTS `seo_recommendations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_url` varchar(500) NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `current_position` int(11) NOT NULL,
  `target_position` int(11) NOT NULL DEFAULT 1,
  `priority` enum('critical','high','medium','low') DEFAULT 'medium',
  `recommendation_type` varchar(100) NOT NULL,
  `recommendation_text` text NOT NULL,
  `status` enum('pending','in_progress','completed','ignored') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_page_url` (`page_url`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Position History Table (Track changes over time)
CREATE TABLE IF NOT EXISTS `seo_position_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) NOT NULL,
  `page_url` varchar(500) NOT NULL,
  `position` int(11) NOT NULL,
  `checked_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_keyword` (`keyword`),
  KEY `idx_checked_at` (`checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Competitor Analysis Table
CREATE TABLE IF NOT EXISTS `seo_competitors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) NOT NULL,
  `competitor_url` varchar(500) NOT NULL,
  `position` int(11) NOT NULL,
  `page_title` varchar(255) DEFAULT NULL,
  `meta_description` text,
  `word_count` int(11) DEFAULT NULL,
  `backlinks` int(11) DEFAULT NULL,
  `domain_authority` int(11) DEFAULT NULL,
  `checked_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_keyword` (`keyword`),
  KEY `idx_position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for testing (optional)
-- This shows the structure but won't insert duplicate data
INSERT IGNORE INTO seo_rankings (page_url, keyword, position, is_real_data, last_updated) VALUES
('/pigeon-nets-in-kukatpally.php', 'Pigeon Nets in Kukatpally', 0, 0, NOW()),
('/safety-nets-in-gachibowli.php', 'Safety Nets in Gachibowli', 0, 0, NOW()),
('/bird-nets-in-hitech-city.php', 'Bird Nets in Hitech City', 0, 0, NOW());
