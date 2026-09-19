-- Initialize Keywords and Services Tables for Page Generation
-- Upload this to phpMyAdmin and execute

-- Create services table
CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` varchar(50) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  `icon` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_id` (`service_id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create keywords table
CREATE TABLE IF NOT EXISTS `keywords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `search_volume` int(11) DEFAULT 0,
  `difficulty` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `keyword_slug` (`slug`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `keywords_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create areas table
CREATE TABLE IF NOT EXISTS `areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `area_name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create generated_pages table
CREATE TABLE IF NOT EXISTS `generated_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword_id` int(11) NOT NULL,
  `area_id` int(11) NOT NULL,
  `page_title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `word_count` int(11) DEFAULT 0,
  `status` enum('pending','generating','completed','failed') DEFAULT 'pending',
  `generated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `keyword_id` (`keyword_id`),
  KEY `area_id` (`area_id`),
  CONSTRAINT `generated_pages_ibfk_1` FOREIGN KEY (`keyword_id`) REFERENCES `keywords` (`id`) ON DELETE CASCADE,
  CONSTRAINT `generated_pages_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample services (you can add more)
INSERT INTO `services` (`service_id`, `service_name`, `slug`, `description`, `icon`, `is_active`, `display_order`) VALUES
('pigeon-nets', 'Pigeon Safety Nets', 'pigeon-nets', 'Professional pigeon net installation for balconies and buildings', 'fa-dove', 1, 1),
('bird-nets', 'Bird Nets', 'bird-nets', 'Anti-bird netting solutions for residential and commercial properties', 'fa-kiwi-bird', 1, 2),
('safety-nets', 'Safety Nets', 'safety-nets', 'Balcony and construction safety nets for fall protection', 'fa-shield-alt', 1, 3),
('invisible-grills', 'Invisible Grills', 'invisible-grills', 'Modern invisible grills for balconies and windows', 'fa-th', 1, 4),
('sports-nets', 'Sports Nets', 'sports-nets', 'Cricket nets and sports netting solutions', 'fa-baseball-ball', 1, 5),
('cloth-hangers', 'Cloth Hangers', 'cloth-hangers', 'Ceiling cloth hangers and drying systems', 'fa-tshirt', 1, 6);

-- Insert sample keywords for each service
INSERT INTO `keywords` (`service_id`, `keyword`, `slug`, `search_volume`, `is_active`, `display_order`) VALUES
-- Pigeon Nets keywords
(1, 'Pigeon Safety Nets', 'pigeon-safety-nets', 1200, 1, 1),
(1, 'Pigeon Net For Balcony', 'pigeon-net-for-balcony', 980, 1, 2),
(1, 'Pigeon Nets Installation', 'pigeon-nets-installation', 850, 1, 3),
(1, 'Balcony Netting', 'balcony-netting', 720, 1, 4),

-- Bird Nets keywords
(2, 'Bird Nets', 'bird-nets', 890, 1, 1),
(2, 'Bird Net For Balcony', 'bird-net-for-balcony', 650, 1, 2),
(2, 'Anti Bird Netting', 'anti-bird-netting', 540, 1, 3),

-- Safety Nets keywords
(3, 'Balcony Safety Nets', 'balcony-safety-nets', 1100, 1, 1),
(3, 'Construction Safety Nets', 'construction-safety-nets', 780, 1, 2),
(3, 'Children Safety Nets', 'children-safety-nets', 620, 1, 3),

-- Invisible Grills keywords
(4, 'Invisible Grills', 'invisible-grills', 1350, 1, 1),
(4, 'Invisible Grill For Balcony', 'invisible-grill-for-balcony', 920, 1, 2),
(4, 'SS Invisible Grills', 'ss-invisible-grills', 680, 1, 3),

-- Sports Nets keywords
(5, 'Cricket Nets', 'cricket-nets', 1500, 1, 1),
(5, 'Cricket Practice Nets', 'cricket-practice-nets', 890, 1, 2),
(5, 'Sports Nets', 'sports-nets', 750, 1, 3),

-- Cloth Hangers keywords
(6, 'Ceiling Cloth Hangers', 'ceiling-cloth-hangers', 980, 1, 1),
(6, 'Cloth Drying Hangers', 'cloth-drying-hangers', 820, 1, 2),
(6, 'Pulley Cloth Hanger', 'pulley-cloth-hanger', 640, 1, 3);

-- Insert sample areas in Chennai
INSERT INTO `areas` (`area_name`, `slug`, `pincode`, `is_active`, `display_order`) VALUES
('Madhapur', 'madhapur', '500081', 1, 1),
('Hitech City', 'hitech-city', '500081', 1, 2),
('Gachibowli', 'gachibowli', '500032', 1, 3),
('Kondapur', 'kondapur', '500084', 1, 4),
('Kukatpally', 'kukatpally', '500072', 1, 5),
('Miyapur', 'miyapur', '500049', 1, 6),
('Ameerpet', 'ameerpet', '600002', 1, 7),
('Tambaram', 'secunderabad', '500003', 1, 8),
('Banjara Hills', 'banjara-hills', '500034', 1, 9),
('Jubilee Hills', 'jubilee-hills', '500033', 1, 10);

-- Show summary
SELECT 'Services created:' as info, COUNT(*) as count FROM services
UNION ALL
SELECT 'Keywords created:', COUNT(*) FROM keywords
UNION ALL
SELECT 'Areas created:', COUNT(*) FROM areas;
