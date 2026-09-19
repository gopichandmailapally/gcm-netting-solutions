-- Backlinks Tracking Table
-- Track all backlinks for SEO monitoring

CREATE TABLE IF NOT EXISTS `backlinks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(255) NOT NULL COMMENT 'Source website/directory name',
  `url` varchar(500) NOT NULL COMMENT 'Full URL of the backlink',
  `type` enum('directory','guest_post','social_media','review','citation','other') DEFAULT 'directory',
  `status` enum('pending','active','broken','removed') DEFAULT 'pending',
  `domain_authority` int(11) DEFAULT NULL COMMENT 'DA of source domain',
  `anchor_text` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_at` datetime NOT NULL,
  `last_checked` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_source` (`source`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for testing
INSERT INTO backlinks (source, url, type, status, domain_authority, created_at) VALUES
('Google My Business', 'https://www.google.com/maps/place/GCM+Safety+Nets', 'citation', 'active', 100, NOW()),
('JustDial', 'https://www.justdial.com/Chennai/GCM-Safety-Nets', 'directory', 'active', 85, NOW()),
('Facebook', 'https://www.facebook.com/gcmsafetynets', 'social_media', 'active', 96, NOW());
