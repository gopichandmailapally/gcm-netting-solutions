-- ============================================
-- SERVICE HIGHLIGHTS SYSTEM
-- Replaces offers section with manageable service highlights
-- ============================================

-- Service Highlights Table
CREATE TABLE IF NOT EXISTS `service_highlights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `icon_class` varchar(100) DEFAULT 'fas fa-star',
  `description` text,
  `highlight_color` varchar(20) DEFAULT '#667eea',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_display_order` (`display_order`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Service Highlights
INSERT INTO `service_highlights` (`title`, `icon_class`, `description`, `highlight_color`, `display_order`, `is_active`) VALUES
('15+ Years Experience', 'fas fa-award', 'Trusted by 10,000+ customers across Chennai', '#667eea', 1, 1),
('Quality Materials', 'fas fa-certificate', 'Premium grade nets with 5-year warranty', '#10b981', 2, 1),
('Expert Installation', 'fas fa-user-check', 'Trained professionals with safety certifications', '#f59e0b', 3, 1),
('Same Day Service', 'fas fa-clock', 'Quick response and installation within 24 hours', '#ef4444', 4, 1),
('Free Inspection', 'fas fa-search', 'Complimentary site visit and measurement', '#3b82f6', 5, 1),
('Affordable Pricing', 'fas fa-rupee-sign', 'Competitive rates with transparent pricing', '#8b5cf6', 6, 1),
('24/7 Support', 'fas fa-headset', 'Round-the-clock customer service available', '#ec4899', 7, 1),
('All Areas Covered', 'fas fa-map-marked-alt', 'Service available across 188 locations in Chennai', '#06b6d4', 8, 1);
