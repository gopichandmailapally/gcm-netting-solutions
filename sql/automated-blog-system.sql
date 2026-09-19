-- ============================================
-- AUTOMATED BLOG GENERATION SYSTEM
-- Creates 1-5 unique blogs daily for SEO
-- ============================================

-- Blog Posts Table
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL UNIQUE,
  `keyword` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `content` longtext NOT NULL,
  `excerpt` text,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `meta_keywords` text,
  `featured_image` varchar(255) DEFAULT NULL,
  `author` varchar(100) DEFAULT 'GCM Netting Solutions',
  `word_count` int(11) DEFAULT 0,
  `reading_time` int(11) DEFAULT 0 COMMENT 'in minutes',
  `status` enum('draft','published','scheduled') DEFAULT 'draft',
  `publish_date` datetime DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `seo_score` int(11) DEFAULT 0 COMMENT 'out of 100',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_keyword` (`keyword`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_publish_date` (`publish_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog Topics/Keywords Table
CREATE TABLE IF NOT EXISTS `blog_topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `topic_type` enum('service','installation','maintenance','benefits','comparison','guide','tips','faq') NOT NULL,
  `topic_template` varchar(255) NOT NULL,
  `used_count` int(11) DEFAULT 0,
  `last_used` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_keyword` (`keyword`),
  KEY `idx_category` (`category`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog Generation Schedule
CREATE TABLE IF NOT EXISTS `blog_generation_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_date` date NOT NULL UNIQUE,
  `blogs_to_generate` int(11) DEFAULT 1,
  `blogs_generated` int(11) DEFAULT 0,
  `status` enum('pending','in_progress','completed','failed') DEFAULT 'pending',
  `error_message` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_schedule_date` (`schedule_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog Settings Table
CREATE TABLE IF NOT EXISTS `blog_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Blog Topics for 64 Keywords
INSERT INTO `blog_topics` (`keyword`, `category`, `topic_type`, `topic_template`) VALUES
-- Pigeon Nets
('Pigeon Nets', 'Pigeon Nets', 'service', 'Complete Guide to {keyword} in Chennai'),
('Pigeon Nets', 'Pigeon Nets', 'installation', 'How to Install {keyword} - Step by Step Guide'),
('Pigeon Nets', 'Pigeon Nets', 'maintenance', 'Maintaining Your {keyword} - Best Practices'),
('Pigeon Nets', 'Pigeon Nets', 'benefits', 'Top 10 Benefits of {keyword} for Your Home'),
('Pigeon Nets', 'Pigeon Nets', 'comparison', '{keyword} vs Other Bird Control Methods'),
('Pigeon Nets', 'Pigeon Nets', 'guide', 'Ultimate Buyer Guide for {keyword}'),
('Pigeon Nets', 'Pigeon Nets', 'tips', '7 Expert Tips for Choosing {keyword}'),

('Pigeon Net', 'Pigeon Nets', 'service', 'Why {keyword} is Essential for Chennai Homes'),
('Pigeon Net', 'Pigeon Nets', 'installation', 'Professional {keyword} Installation Services'),
('Pigeon Net', 'Pigeon Nets', 'benefits', 'Health Benefits of Installing {keyword}'),

('Balcony Netting', 'Pigeon Nets', 'service', '{keyword} Solutions for Modern Apartments'),
('Balcony Netting', 'Pigeon Nets', 'installation', 'DIY vs Professional {keyword} Installation'),
('Balcony Netting', 'Pigeon Nets', 'maintenance', 'How to Clean and Maintain {keyword}'),

('Pigeon Net For Balcony', 'Pigeon Nets', 'guide', 'Complete Guide to {keyword}'),
('Pigeon Net For Balcony', 'Pigeon Nets', 'benefits', 'Why Every Balcony Needs {keyword}'),

-- Bird Nets
('Bird Nets', 'Bird Nets', 'service', 'Comprehensive {keyword} Solutions in Chennai'),
('Bird Nets', 'Bird Nets', 'installation', 'Installing {keyword} - What You Need to Know'),
('Bird Nets', 'Bird Nets', 'comparison', '{keyword} Materials Comparison Guide'),
('Bird Nets', 'Bird Nets', 'benefits', 'Environmental Benefits of {keyword}'),

('Bird Net', 'Bird Nets', 'service', 'Choosing the Right {keyword} for Your Property'),
('Bird Net', 'Bird Nets', 'maintenance', 'Long-lasting {keyword} Maintenance Tips'),

('Anti Bird Netting', 'Bird Nets', 'service', '{keyword} for Commercial Buildings'),
('Anti Bird Netting', 'Bird Nets', 'benefits', 'Cost Savings with {keyword}'),

-- Safety Nets
('Safety Nets', 'Safety Nets', 'service', 'Industrial {keyword} - Complete Safety Guide'),
('Safety Nets', 'Safety Nets', 'installation', 'Safety Standards for {keyword} Installation'),
('Safety Nets', 'Safety Nets', 'benefits', 'How {keyword} Save Lives in Construction'),
('Safety Nets', 'Safety Nets', 'guide', 'Choosing the Right {keyword} for Your Project'),

('Balcony Safety Nets', 'Safety Nets', 'service', 'Child Safety with {keyword}'),
('Balcony Safety Nets', 'Safety Nets', 'installation', 'Installing {keyword} in High-Rise Buildings'),
('Balcony Safety Nets', 'Safety Nets', 'benefits', 'Pet Protection with {keyword}'),

('Children Safety Nets', 'Safety Nets', 'service', 'Protecting Your Kids with {keyword}'),
('Children Safety Nets', 'Safety Nets', 'guide', 'Parents Guide to {keyword}'),

('Construction Safety Nets', 'Safety Nets', 'service', 'OSHA Compliant {keyword}'),
('Construction Safety Nets', 'Safety Nets', 'installation', 'Installing {keyword} on Construction Sites'),

-- Sports Nets
('Cricket Nets', 'Sports Nets', 'service', 'Professional {keyword} for Practice'),
('Cricket Nets', 'Sports Nets', 'installation', 'Setting Up {keyword} at Home'),
('Cricket Nets', 'Sports Nets', 'guide', 'Buying Guide for {keyword}'),
('Cricket Nets', 'Sports Nets', 'benefits', 'Benefits of Home {keyword}'),

('Cricket Practice Net', 'Sports Nets', 'service', 'Building Your Own {keyword}'),
('Cricket Practice Net', 'Sports Nets', 'installation', 'Indoor vs Outdoor {keyword}'),

('Sports Nets', 'Sports Nets', 'service', 'Multi-Sport {keyword} Solutions'),
('Sports Nets', 'Sports Nets', 'maintenance', 'Maintaining Your {keyword}'),

-- Invisible Grills
('Invisible Grills', 'Invisible Grills', 'service', 'Modern {keyword} for Contemporary Homes'),
('Invisible Grills', 'Invisible Grills', 'installation', 'Installing {keyword} - Complete Process'),
('Invisible Grills', 'Invisible Grills', 'benefits', 'Why Choose {keyword} Over Traditional Grills'),
('Invisible Grills', 'Invisible Grills', 'comparison', '{keyword} vs Window Grills'),
('Invisible Grills', 'Invisible Grills', 'guide', 'Ultimate Guide to {keyword}'),

('SS Invisible Grills', 'Invisible Grills', 'service', 'Stainless Steel {keyword} Benefits'),
('SS Invisible Grills', 'Invisible Grills', 'maintenance', 'Caring for Your {keyword}'),

-- Cloth Hangers
('Ceiling Cloth Hangers', 'Cloth Hangers', 'service', 'Space-Saving {keyword} Solutions'),
('Ceiling Cloth Hangers', 'Cloth Hangers', 'installation', 'Installing {keyword} in Small Spaces'),
('Ceiling Cloth Hangers', 'Cloth Hangers', 'benefits', 'Benefits of {keyword} for Apartments'),

('Pulley Cloth Hanger', 'Cloth Hangers', 'service', 'Traditional {keyword} - Still Relevant?'),
('Pulley Cloth Hanger', 'Cloth Hangers', 'guide', 'Choosing the Right {keyword}');

-- Insert Default Settings
INSERT INTO `blog_settings` (`setting_key`, `setting_value`) VALUES
('daily_blog_count', '3'),
('auto_publish', '1'),
('min_word_count', '1200'),
('max_word_count', '2000'),
('enable_auto_generation', '1'),
('generation_time', '09:00:00'),
('last_generation_date', NULL);

-- Create Views for Blog Analytics

-- View: Published Blogs
CREATE OR REPLACE VIEW v_published_blogs AS
SELECT 
    id,
    title,
    slug,
    keyword,
    category,
    excerpt,
    word_count,
    reading_time,
    views,
    publish_date,
    created_at
FROM blog_posts
WHERE status = 'published'
ORDER BY publish_date DESC;

-- View: Blog Statistics
CREATE OR REPLACE VIEW v_blog_statistics AS
SELECT 
    COUNT(*) as total_blogs,
    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_blogs,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_blogs,
    SUM(views) as total_views,
    AVG(word_count) as avg_word_count,
    AVG(seo_score) as avg_seo_score
FROM blog_posts;

-- View: Popular Blogs
CREATE OR REPLACE VIEW v_popular_blogs AS
SELECT 
    id,
    title,
    slug,
    keyword,
    views,
    publish_date
FROM blog_posts
WHERE status = 'published'
ORDER BY views DESC
LIMIT 10;

-- View: Recent Blogs
CREATE OR REPLACE VIEW v_recent_blogs AS
SELECT 
    id,
    title,
    slug,
    keyword,
    category,
    excerpt,
    publish_date
FROM blog_posts
WHERE status = 'published'
ORDER BY publish_date DESC
LIMIT 10;
