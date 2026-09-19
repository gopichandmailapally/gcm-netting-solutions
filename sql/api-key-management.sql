-- ============================================
-- SECURE API KEY MANAGEMENT SYSTEM
-- Centralized, password-protected API key storage
-- ============================================

-- API Keys Table (Encrypted Storage)
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) NOT NULL UNIQUE,
  `key_value` text NOT NULL COMMENT 'Encrypted API key',
  `key_type` enum('gemini','openai','other') DEFAULT 'gemini',
  `is_active` tinyint(1) DEFAULT 1,
  `usage_count` int(11) DEFAULT 0,
  `last_used` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_key_name` (`key_name`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Key Access Log
CREATE TABLE IF NOT EXISTS `api_key_access_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) NOT NULL,
  `action` enum('view','update','delete','use') NOT NULL,
  `user_ip` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `success` tinyint(1) DEFAULT 1,
  `error_message` text,
  `accessed_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_key_name` (`key_name`),
  KEY `idx_accessed_at` (`accessed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Key Security Settings
CREATE TABLE IF NOT EXISTS `api_key_security` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text NOT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default security settings
INSERT INTO `api_key_security` (`setting_key`, `setting_value`) VALUES
('require_password', '1'),
('encryption_method', 'AES-256-CBC'),
('max_failed_attempts', '3'),
('lockout_duration', '300'),
('password_hash', ''),
('last_password_change', NULL)
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- Create view for API key status
CREATE OR REPLACE VIEW v_api_key_status AS
SELECT 
    key_name,
    key_type,
    is_active,
    usage_count,
    last_used,
    created_at,
    CASE 
        WHEN last_used IS NULL THEN 'Never Used'
        WHEN last_used >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 'Active'
        WHEN last_used >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 'Recent'
        ELSE 'Inactive'
    END as status
FROM api_keys
ORDER BY created_at DESC;
