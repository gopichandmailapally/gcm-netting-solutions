-- SEO Optimization Log Table
-- Tracks all automatic optimizations applied to pages

CREATE TABLE IF NOT EXISTS `seo_optimization_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_path` varchar(500) NOT NULL,
  `optimizations` text NOT NULL COMMENT 'JSON of optimizations applied',
  `content_word_count_before` int(11) DEFAULT NULL,
  `content_word_count_after` int(11) DEFAULT NULL,
  `images_optimized` int(11) DEFAULT 0,
  `faq_added` tinyint(1) DEFAULT 0,
  `schema_added` tinyint(1) DEFAULT 0,
  `internal_links_added` int(11) DEFAULT 0,
  `technical_fixes` int(11) DEFAULT 0,
  `meta_optimized` tinyint(1) DEFAULT 0,
  `optimized_at` datetime NOT NULL,
  `optimized_by` varchar(100) DEFAULT 'auto_system',
  PRIMARY KEY (`id`),
  KEY `idx_page_path` (`page_path`),
  KEY `idx_optimized_at` (`optimized_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index for faster queries
CREATE INDEX idx_page_optimization ON seo_optimization_log(page_path, optimized_at);
