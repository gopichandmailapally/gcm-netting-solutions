/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: gcmnettingsolutions_db
-- ------------------------------------------------------
-- Server version	11.8.6-MariaDB-5ubuntu0.1 from Ubuntu

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `about_page_content`
--

DROP TABLE IF EXISTS `about_page_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `about_page_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_description` text DEFAULT NULL,
  `years_of_experience` int(11) DEFAULT 10,
  `team_image` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `about_page_content`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `about_page_content` WRITE;
/*!40000 ALTER TABLE `about_page_content` DISABLE KEYS */;
INSERT INTO `about_page_content` VALUES
(1,'GCM Netting Solutions has been serving Chennai for over a decade, providing top-quality safety net installation services for residential, commercial, and industrial properties...',10,'uploads/about/team_1789787299.jpg','2026-04-08 19:25:17','2026-09-19 03:08:19');
/*!40000 ALTER TABLE `about_page_content` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES
(1,'gopichand24','$2y$12$kNfa1CF/siYHzzFksyMYWegMbX3VJrgKsBCFgYm2n8kKxugjWUqVu','gopichandmailapally@gmail.com','super_admin',1,NULL,'2026-09-19 04:52:11','2026-09-19 04:52:11');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ai_blogs`
--

DROP TABLE IF EXISTS `ai_blogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_blogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(600) NOT NULL,
  `slug` varchar(600) NOT NULL,
  `content` longtext DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `author` varchar(200) DEFAULT 'GCM Netting Solutions',
  `featured_image` varchar(500) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 1,
  `views` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blog_slug` (`slug`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_blogs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ai_blogs` WRITE;
/*!40000 ALTER TABLE `ai_blogs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_blogs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ai_deletion_log`
--

DROP TABLE IF EXISTS `ai_deletion_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_deletion_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type` varchar(100) DEFAULT NULL,
  `content_id` varchar(255) DEFAULT NULL,
  `content_title` varchar(500) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `pin_correct` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_deletion_log`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ai_deletion_log` WRITE;
/*!40000 ALTER TABLE `ai_deletion_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_deletion_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ai_protected_content`
--

DROP TABLE IF EXISTS `ai_protected_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_protected_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type` varchar(100) NOT NULL,
  `content_id` varchar(255) NOT NULL,
  `content_title` varchar(500) DEFAULT NULL,
  `content_path` varchar(500) DEFAULT NULL,
  `generated_by` varchar(50) DEFAULT 'gemini',
  `is_protected` tinyint(1) DEFAULT 1,
  `protected_at` datetime DEFAULT current_timestamp(),
  `protected_by` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ct_ci` (`content_type`,`content_id`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_protected_content`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ai_protected_content` WRITE;
/*!40000 ALTER TABLE `ai_protected_content` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_protected_content` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ai_reviews`
--

DROP TABLE IF EXISTS `ai_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(300) NOT NULL,
  `rating` tinyint(1) NOT NULL DEFAULT 5,
  `category` varchar(200) DEFAULT NULL,
  `review_text` text NOT NULL,
  `location` varchar(300) DEFAULT NULL,
  `slug` varchar(400) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'approved',
  `verified` tinyint(1) DEFAULT 1,
  `helpful_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_reviews`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ai_reviews` WRITE;
/*!40000 ALTER TABLE `ai_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_reviews` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ai_security_settings`
--

DROP TABLE IF EXISTS `ai_security_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_security_settings` (
  `key_name` varchar(100) NOT NULL,
  `key_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_security_settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ai_security_settings` WRITE;
/*!40000 ALTER TABLE `ai_security_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_security_settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `api_key_audit`
--

DROP TABLE IF EXISTS `api_key_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_key_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(100) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `success` tinyint(1) DEFAULT 0,
  `details` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_key_audit`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `api_key_audit` WRITE;
/*!40000 ALTER TABLE `api_key_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_key_audit` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `billing_bank_details`
--

DROP TABLE IF EXISTS `billing_bank_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_bank_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(255) NOT NULL,
  `account_number` varchar(100) NOT NULL,
  `ifsc_code` varchar(50) NOT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `upi_id` varchar(100) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_bank_details`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `billing_bank_details` WRITE;
/*!40000 ALTER TABLE `billing_bank_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `billing_bank_details` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `billing_company_settings`
--

DROP TABLE IF EXISTS `billing_company_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_company_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `company_address` text DEFAULT NULL,
  `company_phone` varchar(20) DEFAULT NULL,
  `company_email` varchar(100) DEFAULT NULL,
  `company_gstin` varchar(50) DEFAULT NULL,
  `company_pan` varchar(50) DEFAULT NULL,
  `company_logo_path` varchar(255) DEFAULT NULL,
  `color_theme` varchar(50) DEFAULT 'blue',
  `invoice_prefix` varchar(20) DEFAULT 'INV',
  `estimation_prefix` varchar(20) DEFAULT 'EST',
  `warranty_prefix` varchar(20) DEFAULT 'WAR',
  `bill_prefix` varchar(20) DEFAULT 'BILL',
  `next_invoice_number` int(11) DEFAULT 1,
  `next_estimation_number` int(11) DEFAULT 1,
  `next_warranty_number` int(11) DEFAULT 1,
  `next_bill_number` int(11) DEFAULT 1,
  `default_terms_conditions` text DEFAULT NULL,
  `default_warranty_terms` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_company_settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `billing_company_settings` WRITE;
/*!40000 ALTER TABLE `billing_company_settings` DISABLE KEYS */;
INSERT INTO `billing_company_settings` VALUES
(1,'GCM Netting Solutions','Chennai, Telangana','8186898908','gcmsafetynets@gmail.com',NULL,NULL,NULL,'blue','INV','EST','WAR','BILL',1,1,1,1,NULL,NULL,'2026-04-05 17:00:45','2026-04-05 17:00:45');
/*!40000 ALTER TABLE `billing_company_settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `billing_products`
--

DROP TABLE IF EXISTS `billing_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `hsn_code` varchar(50) NOT NULL,
  `gst_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `default_rate` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(50) DEFAULT 'sqft',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing_products`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `billing_products` WRITE;
/*!40000 ALTER TABLE `billing_products` DISABLE KEYS */;
INSERT INTO `billing_products` VALUES
(1,'Anti Bird Net','39269099',5.00,0.00,'sqft',1,'2026-04-05 17:00:45','2026-04-05 17:00:45'),
(2,'Sports/Cricket Net','56069990',5.00,0.00,'sqft',1,'2026-04-05 17:00:45','2026-04-05 17:00:45'),
(3,'Safety Net','56089090',5.00,0.00,'sqft',1,'2026-04-05 17:00:45','2026-04-05 17:00:45'),
(4,'Artificial Grass','57033100',5.00,0.00,'sqft',1,'2026-04-05 17:00:45','2026-04-05 17:00:45'),
(5,'Artificial Cricket Pitch Turf','57033100',5.00,0.00,'sqft',1,'2026-04-05 17:00:45','2026-04-05 17:00:45'),
(6,'Invisible Grill','73144990',18.00,0.00,'sqft',1,'2026-04-05 17:00:45','2026-04-05 17:00:45'),
(7,'Cloth Hanger','73262090',18.00,0.00,'unit',1,'2026-04-05 17:00:45','2026-04-05 17:00:45'),
(8,'Mosquito Mesh Velcro Type','70199090',18.00,0.00,'sqft',1,'2026-04-05 17:00:45','2026-04-05 17:00:45'),
(9,'Mosquito Mesh for Balcony','70199090',18.00,0.00,'sqft',1,'2026-04-05 17:00:45','2026-04-05 17:00:45');
/*!40000 ALTER TABLE `billing_products` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `blog_generation_schedule`
--

DROP TABLE IF EXISTS `blog_generation_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_generation_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_date` date NOT NULL,
  `blogs_to_generate` int(11) DEFAULT 1,
  `blogs_generated` int(11) DEFAULT 0,
  `status` enum('pending','in_progress','completed','failed') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sched_date` (`schedule_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_generation_schedule`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `blog_generation_schedule` WRITE;
/*!40000 ALTER TABLE `blog_generation_schedule` DISABLE KEYS */;
/*!40000 ALTER TABLE `blog_generation_schedule` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `featured_image` varchar(500) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `author` varchar(100) DEFAULT 'GCM Netting Solutions',
  `is_published` tinyint(1) DEFAULT 1,
  `views` int(11) DEFAULT 0,
  `published_at` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_published` (`is_published`,`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_posts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `blog_posts` WRITE;
/*!40000 ALTER TABLE `blog_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `blog_posts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `blog_settings`
--

DROP TABLE IF EXISTS `blog_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=5997995 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `blog_settings` WRITE;
/*!40000 ALTER TABLE `blog_settings` DISABLE KEYS */;
INSERT INTO `blog_settings` VALUES
(1,'daily_blog_count','1','2026-04-11 21:29:41'),
(2,'enable_auto_generation','1','2026-04-09 18:30:52'),
(4,'auto_publish','1','2026-04-11 17:37:28'),
(5,'min_word_count','1200','2026-04-11 17:37:28'),
(6,'max_word_count','2000','2026-04-11 17:37:28'),
(8,'generation_time','09:00:00','2026-04-11 17:37:28'),
(9,'last_generation_date','2026-09-19','2026-09-19 02:32:51');
/*!40000 ALTER TABLE `blog_settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `blog_topics`
--

DROP TABLE IF EXISTS `blog_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `topic_type` enum('service','installation','maintenance','benefits','comparison','guide','tips','faq') NOT NULL,
  `topic_template` varchar(255) NOT NULL,
  `used_count` int(11) DEFAULT 0,
  `last_used` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_topics`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `blog_topics` WRITE;
/*!40000 ALTER TABLE `blog_topics` DISABLE KEYS */;
INSERT INTO `blog_topics` VALUES
(1,'Pigeon Nets','Pigeon Nets','service','Complete Guide to {keyword} in Chennai',2,'2026-08-03 02:02:10',1),
(2,'Pigeon Nets','Pigeon Nets','installation','How to Install {keyword} - Step by Step Guide',2,'2026-08-09 05:40:49',1),
(3,'Pigeon Nets','Pigeon Nets','maintenance','Maintaining Your {keyword} - Best Practices',3,'2026-08-30 03:36:44',1),
(4,'Pigeon Nets','Pigeon Nets','benefits','Top 10 Benefits of {keyword} for Your Home',3,'2026-09-10 04:38:42',1),
(5,'Pigeon Nets','Pigeon Nets','comparison','{keyword} vs Other Bird Control Methods',2,'2026-08-27 02:57:03',1),
(6,'Pigeon Nets','Pigeon Nets','guide','Ultimate Buyer Guide for {keyword}',3,'2026-09-05 00:59:20',1),
(7,'Pigeon Nets','Pigeon Nets','tips','7 Expert Tips for Choosing {keyword}',2,'2026-08-23 04:30:23',1),
(8,'Pigeon Net','Pigeon Nets','service','Why {keyword} is Essential for Chennai Homes',3,'2026-09-18 04:53:38',1),
(9,'Pigeon Net','Pigeon Nets','installation','Professional {keyword} Installation Services',3,'2026-08-28 04:26:39',1),
(10,'Pigeon Net','Pigeon Nets','benefits','Health Benefits of Installing {keyword}',3,'2026-09-01 03:32:45',1),
(11,'Balcony Netting','Pigeon Nets','service','{keyword} Solutions for Modern Apartments',3,'2026-09-16 04:52:15',1),
(12,'Balcony Netting','Pigeon Nets','installation','DIY vs Professional {keyword} Installation',2,'2026-07-07 05:34:42',1),
(13,'Balcony Netting','Pigeon Nets','maintenance','How to Clean and Maintain {keyword}',3,'2026-09-12 04:43:26',1),
(14,'Pigeon Net For Balcony','Pigeon Nets','guide','Complete Guide to {keyword}',2,'2026-08-25 02:33:37',1),
(15,'Pigeon Net For Balcony','Pigeon Nets','benefits','Why Every Balcony Needs {keyword}',2,'2026-07-26 02:32:13',1),
(16,'Bird Nets','Bird Nets','service','Comprehensive {keyword} Solutions in Chennai',3,'2026-09-19 02:32:51',1),
(17,'Bird Nets','Bird Nets','installation','Installing {keyword} - What You Need to Know',3,'2026-09-14 01:44:19',1),
(18,'Bird Nets','Bird Nets','comparison','{keyword} Materials Comparison Guide',2,'2026-07-29 01:43:09',1),
(19,'Bird Nets','Bird Nets','benefits','Environmental Benefits of {keyword}',2,'2026-08-08 02:45:00',1),
(20,'Bird Net','Bird Nets','service','Choosing the Right {keyword} for Your Property',3,'2026-09-02 01:32:18',1),
(21,'Bird Net','Bird Nets','maintenance','Long-lasting {keyword} Maintenance Tips',2,'2026-07-15 00:32:12',1),
(22,'Anti Bird Netting','Bird Nets','service','{keyword} for Commercial Buildings',2,'2026-07-16 02:07:08',1),
(23,'Anti Bird Netting','Bird Nets','benefits','Cost Savings with {keyword}',3,'2026-08-31 01:45:55',1),
(24,'Safety Nets','Safety Nets','service','Industrial {keyword} - Complete Safety Guide',2,'2026-08-24 04:35:45',1),
(25,'Safety Nets','Safety Nets','installation','Safety Standards for {keyword} Installation',3,'2026-09-03 03:40:30',1),
(26,'Safety Nets','Safety Nets','benefits','How {keyword} Save Lives in Construction',3,'2026-09-07 03:30:31',1),
(27,'Safety Nets','Safety Nets','guide','Choosing the Right {keyword} for Your Project',2,'2026-07-31 01:30:49',1),
(28,'Balcony Safety Nets','Safety Nets','service','Child Safety with {keyword}',3,'2026-09-09 05:37:38',1),
(29,'Balcony Safety Nets','Safety Nets','installation','Installing {keyword} in High-Rise Buildings',3,'2026-09-04 02:39:12',1),
(30,'Balcony Safety Nets','Safety Nets','benefits','Pet Protection with {keyword}',3,'2026-08-29 01:33:56',1),
(31,'Children Safety Nets','Safety Nets','service','Protecting Your Kids with {keyword}',2,'2026-07-14 03:36:48',1),
(32,'Children Safety Nets','Safety Nets','guide','Parents Guide to {keyword}',2,'2026-08-17 05:34:31',1),
(33,'Construction Safety Nets','Safety Nets','service','OSHA Compliant {keyword}',3,'2026-09-15 01:39:17',1),
(34,'Construction Safety Nets','Safety Nets','installation','Installing {keyword} on Construction Sites',2,'2026-07-23 03:31:49',1),
(35,'Cricket Nets','Sports Nets','service','Professional {keyword} for Practice',2,'2026-08-13 03:30:58',1),
(36,'Cricket Nets','Sports Nets','installation','Setting Up {keyword} at Home',2,'2026-07-09 05:31:36',1),
(37,'Cricket Nets','Sports Nets','guide','Buying Guide for {keyword}',2,'2026-08-05 05:41:20',1),
(38,'Cricket Nets','Sports Nets','benefits','Benefits of Home {keyword}',3,'2026-09-06 01:49:24',1),
(39,'Cricket Practice Net','Sports Nets','service','Building Your Own {keyword}',2,'2026-07-11 00:31:31',1),
(40,'Cricket Practice Net','Sports Nets','installation','Indoor vs Outdoor {keyword}',2,'2026-08-06 01:42:39',1),
(41,'Sports Nets','Sports Nets','service','Multi-Sport {keyword} Solutions',2,'2026-08-02 01:34:05',1),
(42,'Sports Nets','Sports Nets','maintenance','Maintaining Your {keyword}',2,'2026-07-06 03:32:37',1),
(43,'Invisible Grills','Invisible Grills','service','Modern {keyword} for Contemporary Homes',3,'2026-09-17 02:45:38',1),
(44,'Invisible Grills','Invisible Grills','installation','Installing {keyword} - Complete Process',2,'2026-08-26 04:42:14',1),
(45,'Invisible Grills','Invisible Grills','benefits','Why Choose {keyword} Over Traditional Grills',2,'2026-07-10 00:46:38',1),
(46,'Invisible Grills','Invisible Grills','comparison','{keyword} vs Window Grills',2,'2026-08-01 01:41:21',1),
(47,'Invisible Grills','Invisible Grills','guide','Ultimate Guide to {keyword}',2,'2026-08-22 02:42:46',1),
(48,'SS Invisible Grills','Invisible Grills','service','Stainless Steel {keyword} Benefits',2,'2026-08-14 01:36:13',1),
(49,'SS Invisible Grills','Invisible Grills','maintenance','Caring for Your {keyword}',2,'2026-07-18 02:47:58',1),
(50,'Ceiling Cloth Hangers','Cloth Hangers','service','Space-Saving {keyword} Solutions',3,'2026-09-11 01:50:57',1),
(51,'Ceiling Cloth Hangers','Cloth Hangers','installation','Installing {keyword} in Small Spaces',3,'2026-09-13 04:12:01',1),
(52,'Ceiling Cloth Hangers','Cloth Hangers','benefits','Benefits of {keyword} for Apartments',3,'2026-09-08 03:44:06',1),
(53,'Pulley Cloth Hanger','Cloth Hangers','service','Traditional {keyword} - Still Relevant?',2,'2026-07-28 03:46:18',1),
(54,'Pulley Cloth Hanger','Cloth Hangers','guide','Choosing the Right {keyword}',2,'2026-07-25 02:35:21',1);
/*!40000 ALTER TABLE `blog_topics` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `contact_inquiries`
--

DROP TABLE IF EXISTS `contact_inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_inquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `service` varchar(100) DEFAULT NULL,
  `area` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT 'Chennai',
  `message` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'new',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_inquiries`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `contact_inquiries` WRITE;
/*!40000 ALTER TABLE `contact_inquiries` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_inquiries` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `faq_settings`
--

DROP TABLE IF EXISTS `faq_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faq_settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `auto_generate_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `daily_faq_count` int(11) NOT NULL DEFAULT 1,
  `generation_time` varchar(50) NOT NULL DEFAULT 'random',
  `today_schedule` varchar(20) NOT NULL DEFAULT '',
  `last_run_date` date DEFAULT NULL,
  `total_generated` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faq_settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `faq_settings` WRITE;
/*!40000 ALTER TABLE `faq_settings` DISABLE KEYS */;
INSERT INTO `faq_settings` VALUES
(1,1,1,'random','',NULL,0);
/*!40000 ALTER TABLE `faq_settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `faqs`
--

DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `views` int(11) DEFAULT 0,
  `helpful_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `slug` varchar(400) DEFAULT NULL,
  `is_ai_generated` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`,`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faqs`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `gallery_images`
--

DROP TABLE IF EXISTS `gallery_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gallery_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_service_id` (`service_id`),
  KEY `idx_is_active` (`is_active`,`display_order`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery_images`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `gallery_images` WRITE;
/*!40000 ALTER TABLE `gallery_images` DISABLE KEYS */;
INSERT INTO `gallery_images` VALUES
(2,1,'Pigeon Nets','Anti Pigeon Nets',NULL,'assets/img/gallery/gallery-1777179338-9330.jpg','pigeon-nets',1,0,1,'2026-04-09 17:31:31','2026-04-26 04:55:39'),
(3,14,'Bird Nets','Net for Bird Protection',NULL,'assets/img/gallery/gallery-1777179360-2477.jpg','bird-nets',2,0,1,'2026-04-09 17:32:04','2026-04-26 04:56:00'),
(4,23,'Safety Nets','Balcony Safety Nets',NULL,'assets/img/gallery/gallery-1777179375-6803.jpg','safety-nets',3,0,1,'2026-04-09 17:32:37','2026-04-26 04:56:16'),
(5,NULL,'Sports Nets','',NULL,'assets/img/gallery/gallery-1777179388-4277.jpg','sports-nets',4,0,1,'2026-04-09 17:51:17','2026-04-26 04:56:28'),
(6,47,'Invisible Grills','Invisible Grill Installation',NULL,'assets/img/gallery/gallery-1777179400-5650.jpg','invisible-grills',5,0,1,'2026-04-09 17:52:01','2026-04-26 04:56:41'),
(7,509,'Cloth Hangers','Cloth Hangers',NULL,'assets/img/gallery/gallery-1777179414-9559.jpg','cloth-hangers',6,0,1,'2026-04-09 17:53:21','2026-04-26 04:56:54');
/*!40000 ALTER TABLE `gallery_images` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `gemini_api_keys`
--

DROP TABLE IF EXISTS `gemini_api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gemini_api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_label` varchar(100) NOT NULL DEFAULT 'API Key',
  `api_key` text NOT NULL,
  `pin_hash` text NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `test_status` varchar(20) DEFAULT 'untested',
  `test_model` varchar(100) DEFAULT NULL,
  `tested_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gemini_api_keys`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `gemini_api_keys` WRITE;
/*!40000 ALTER TABLE `gemini_api_keys` DISABLE KEYS */;
/*!40000 ALTER TABLE `gemini_api_keys` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `generated_pages`
--

DROP TABLE IF EXISTS `generated_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `generated_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) DEFAULT 0,
  `keyword_id` int(11) DEFAULT 0,
  `area_id` int(11) DEFAULT 0,
  `page_slug` varchar(500) DEFAULT NULL,
  `page_title` varchar(255) NOT NULL DEFAULT '',
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `h1_heading` varchar(255) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `word_count` int(11) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `generated_by` varchar(50) DEFAULT 'gemini_ai',
  `generated_at` datetime DEFAULT current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kw_area` (`keyword_id`,`area_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `generated_pages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `generated_pages` WRITE;
/*!40000 ALTER TABLE `generated_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `generated_pages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `homepage_images`
--

DROP TABLE IF EXISTS `homepage_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `homepage_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_name` varchar(100) NOT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `image_title` varchar(255) DEFAULT NULL,
  `image_alt` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_name` (`section_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `homepage_images`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `homepage_images` WRITE;
/*!40000 ALTER TABLE `homepage_images` DISABLE KEYS */;
INSERT INTO `homepage_images` VALUES
(1,'about_section','assets/img/about_section-1789787352.jpg','About Section Image','',1,0,'2026-04-09 18:24:05','2026-09-19 03:09:12'),
(2,'services_banner','assets/img/services_banner-1789787376.jpg','Services Section Banner','',1,0,'2026-04-09 18:24:29','2026-09-19 03:09:37'),
(3,'why_choose_bg','assets/img/why_choose_bg-1775759106.jpg','Why Choose Us Background','',1,0,'2026-04-09 18:25:06','2026-04-09 18:25:06');
/*!40000 ALTER TABLE `homepage_images` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `main_pages`
--

DROP TABLE IF EXISTS `main_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `main_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `page_type` enum('main','utility','legal') DEFAULT 'main',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `main_pages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `main_pages` WRITE;
/*!40000 ALTER TABLE `main_pages` DISABLE KEYS */;
INSERT INTO `main_pages` VALUES
(1,'Homepage','index','main',1,'2026-04-05 17:00:44'),
(2,'About Us','about','main',1,'2026-04-05 17:00:44'),
(3,'Contact','contact','main',1,'2026-04-05 17:00:44'),
(4,'Gallery','gallery','main',1,'2026-04-05 17:00:44'),
(5,'Reviews','reviews','main',1,'2026-04-05 17:00:44'),
(6,'FAQs','faqs','main',1,'2026-04-05 17:00:44'),
(7,'Videos','videos','main',1,'2026-04-05 17:00:44'),
(8,'Blogs','blogs','main',1,'2026-04-05 17:00:44'),
(9,'Estimation','estimation','utility',1,'2026-04-05 17:00:44'),
(10,'Privacy Policy','privacy-policy','legal',1,'2026-04-05 17:00:44');
/*!40000 ALTER TABLE `main_pages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `offers`
--

DROP TABLE IF EXISTS `offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `badge_text` varchar(100) DEFAULT NULL,
  `discount_text` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `valid_until` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offers`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `offers` WRITE;
/*!40000 ALTER TABLE `offers` DISABLE KEYS */;
/*!40000 ALTER TABLE `offers` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `page_views`
--

DROP TABLE IF EXISTS `page_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) DEFAULT NULL,
  `page_url` varchar(500) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `time_spent` int(11) DEFAULT 0,
  `scroll_depth` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page_views`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `page_views` WRITE;
/*!40000 ALTER TABLE `page_views` DISABLE KEYS */;
INSERT INTO `page_views` VALUES
(1,'9fhlc9hk9uvmu7wwv4e','/','2026-09-19 04:53:44',0,0),
(2,'eweci9lpqmcmu7wyt2f','/','2026-09-19 04:55:15',25,93),
(3,'eweci9lpqmcmu7wyt2f','/bird-net-for-balcony','2026-09-19 04:55:47',0,0),
(4,'eweci9lpqmcmu7wyt2f','/pigeon-nets-in-t-nagar','2026-09-19 04:55:54',25,14),
(5,'eweci9lpqmcmu7wyt2f','/estimation','2026-09-19 04:56:24',0,0),
(6,'eweci9lpqmcmu7wyt2f','/gallery','2026-09-19 04:56:34',325,97),
(7,'qlwbtqupawmu7x2l5k','/','2026-09-19 04:58:11',0,0),
(8,'edssintq4iwmu7x2l71','/','2026-09-19 04:58:11',0,0),
(9,'16f93w63ni8mu7x2ldu','/','2026-09-19 04:58:12',0,0),
(10,'l9a46e2jyugmu7x2sw6','/','2026-09-19 04:58:21',0,0),
(11,'jq9t9dgtd5rmu7x6qop','/','2026-09-19 05:01:25',0,0),
(12,'w3zxu9b22ismu7x6qxq','/','2026-09-19 05:01:25',0,0);
/*!40000 ALTER TABLE `page_views` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `review_settings`
--

DROP TABLE IF EXISTS `review_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `review_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review_settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `review_settings` WRITE;
/*!40000 ALTER TABLE `review_settings` DISABLE KEYS */;
INSERT INTO `review_settings` VALUES
('auto_enabled','1'),
('cron_token','31c6878b9c67215255f2115060f5f9cd0ede8b68'),
('daily_count','1'),
('last_run_date','2026-09-18'),
('run_once_today','1'),
('today_schedule','2026-09-18:14');
/*!40000 ALTER TABLE `review_settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `seo_service_keywords`
--

DROP TABLE IF EXISTS `seo_service_keywords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_service_keywords` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` int(11) NOT NULL,
  `keyword_name` varchar(255) NOT NULL,
  `keyword_slug` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `search_volume` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `difficulty` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_keyword_slug` (`keyword_slug`),
  UNIQUE KEY `uq_kw_slug` (`keyword_slug`(191)),
  KEY `idx_service_id` (`service_id`),
  CONSTRAINT `kw_service_fk` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=929562 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seo_service_keywords`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `seo_service_keywords` WRITE;
/*!40000 ALTER TABLE `seo_service_keywords` DISABLE KEYS */;
INSERT INTO `seo_service_keywords` VALUES
(1,1,'Pigeon Nets','pigeon-nets','PIGEON NETS',1000,1,1,'2026-04-05 17:00:44',0),
(2,1,'Pigeon Net','pigeon-net','PIGEON NETS',1000,1,2,'2026-04-05 17:00:44',0),
(3,66,'Balcony Netting','balcony-netting','SAFETY NETS',1000,1,3,'2026-04-05 17:00:44',0),
(4,1,'Pigeon Net For Balcony','pigeon-net-for-balcony','PIGEON NETS',1000,1,4,'2026-04-05 17:00:44',0),
(5,1,'Pigeon Nets Installation','pigeon-nets-installation','PIGEON NETS',1000,1,5,'2026-04-05 17:00:44',0),
(6,6,'Pigeon Bird Netting','pigeon-bird-netting','PIGEON NETS',1000,1,6,'2026-04-05 17:00:44',0),
(7,1,'Pigeon Net Installation','pigeon-net-installation','PIGEON NETS',1000,1,7,'2026-04-05 17:00:44',0),
(8,1,'Pigeon Net Near Me','pigeon-net-near-me','PIGEON NETS',1000,1,8,'2026-04-05 17:00:44',0),
(9,1,'Pigeon Net For Balcony Near Me','pigeon-net-for-balcony-near-me','PIGEON NETS',1000,1,9,'2026-04-05 17:00:44',0),
(10,10,'Pigeon Net Installation Near Me','pigeon-net-installation-near-me','PIGEON NETS',1000,1,10,'2026-04-05 17:00:44',0),
(11,1,'Pigeon Safety Nets','pigeon-safety-nets','PIGEON NETS',1000,1,11,'2026-04-05 17:00:44',0),
(12,1,'Pigeon Net Price','pigeon-net-price','PIGEON NETS',1000,1,12,'2026-04-05 17:00:44',0),
(13,13,'Kabutar Jali Near Me','kabutar-jali-near-me','PIGEON NETS',1000,1,13,'2026-04-05 17:00:44',0),
(14,10,'Bird Nets','bird-nets','BIRD NETS',1000,1,14,'2026-04-05 17:00:44',0),
(15,10,'Bird Net','bird-net','BIRD NETS',1000,1,15,'2026-04-05 17:00:44',0),
(16,10,'Bird Net For Balcony','bird-net-for-balcony','BIRD NETS',1000,1,16,'2026-04-05 17:00:44',0),
(17,10,'Bird Net Near Me','bird-net-near-me','BIRD NETS',1000,1,17,'2026-04-05 17:00:44',0),
(18,18,'Nets For Birds','nets-for-birds','BIRD NETS',1000,1,18,'2026-04-05 17:00:44',0),
(19,19,'Net For Birds','net-for-birds','BIRD NETS',1000,1,19,'2026-04-05 17:00:44',0),
(20,10,'Industrial Bird Netting','industrial-bird-netting','BIRD NETS',1000,1,20,'2026-04-05 17:00:44',0),
(21,21,'Bird Netting','bird-netting','BIRD NETS',1000,1,21,'2026-04-05 17:00:44',0),
(23,23,'Safety Nets','safety-nets','SAFETY NETS',1000,1,23,'2026-04-05 17:00:44',0),
(25,66,'Safety Nets For Balconies','safety-nets-for-balconies','SAFETY NETS',1000,1,25,'2026-04-05 17:00:44',0),
(26,26,'Duct Area Safety Nets','duct-area-safety-nets','SAFETY NETS',1000,1,26,'2026-04-05 17:00:44',0),
(27,27,'Monkey Safety Nets','monkey-safety-nets','SAFETY NETS',1000,1,27,'2026-04-05 17:00:44',0),
(28,28,'Construction Safety Nets','construction-safety-nets','SAFETY NETS',1000,1,28,'2026-04-05 17:00:44',0),
(29,29,'Industrial Safety Nets','industrial-safety-nets','SAFETY NETS',1000,1,29,'2026-04-05 17:00:44',0),
(30,30,'Fall Safety Nets','fall-safety-nets','SAFETY NETS',1000,1,30,'2026-04-05 17:00:44',0),
(31,31,'Fall Protection Nets','fall-protection-nets','SAFETY NETS',1000,1,31,'2026-04-05 17:00:44',0),
(32,32,'Children Safety Nets','children-safety-nets','SAFETY NETS',1000,1,32,'2026-04-05 17:00:44',0),
(33,33,'Pet Safety Nets','pet-safety-nets','SAFETY NETS',1000,1,33,'2026-04-05 17:00:44',0),
(36,515,'Cricket Nets Near Me','cricket-nets-near-me','SPORTS NETS',1000,1,36,'2026-04-05 17:00:44',0),
(37,515,'Cricket Practice Net','cricket-practice-net','SPORTS NETS',1000,1,37,'2026-04-05 17:00:44',0),
(38,515,'Cricket Practice Nets','cricket-practice-nets','SPORTS NETS',1000,1,38,'2026-04-05 17:00:44',0),
(39,39,'Cricket Net Price','cricket-net-price','SPORTS NETS',1000,1,39,'2026-04-05 17:00:44',0),
(40,40,'Cricket Indoor Nets Near Me','cricket-indoor-nets-near-me','SPORTS NETS',1000,1,40,'2026-04-05 17:00:44',0),
(41,515,'Indoor Cricket Nets Near Me','indoor-cricket-nets-near-me','SPORTS NETS',1000,1,41,'2026-04-05 17:00:44',0),
(42,42,'Sports Nets','sports-nets','SPORTS NETS',1000,1,42,'2026-04-05 17:00:44',0),
(43,515,'Sports Netting','sports-netting','SPORTS NETS',1000,1,43,'2026-04-05 17:00:44',0),
(44,44,'Cricket Netting','cricket-netting','SPORTS NETS',1000,1,44,'2026-04-05 17:00:44',0),
(45,513,'Box Cricket Net','box-cricket-net','SPORTS NETS',1000,1,45,'2026-04-05 17:00:44',0),
(46,46,'Cricket Net Installation','cricket-net-installation','SPORTS NETS',1000,1,46,'2026-04-05 17:00:44',0),
(47,47,'Invisible Grills','invisible-grills','INVISIBLE GRILLS',1000,1,47,'2026-04-05 17:00:44',0),
(48,48,'Invisible Grill Near Me','invisible-grill-near-me','INVISIBLE GRILLS',1000,1,48,'2026-04-05 17:00:44',0),
(49,49,'SS Invisible Grills','ss-invisible-grills','INVISIBLE GRILLS',1000,1,49,'2026-04-05 17:00:44',0),
(50,50,'Invisible Grill For Balcony','invisible-grill-for-balcony','INVISIBLE GRILLS',1000,1,50,'2026-04-05 17:00:44',0),
(51,51,'Balcony Invisible Grill','balcony-invisible-grill','INVISIBLE GRILLS',1000,1,51,'2026-04-05 17:00:44',0),
(52,52,'Invisible Grill For Balcony Near Me','invisible-grill-for-balcony-near-me','INVISIBLE GRILLS',1000,1,52,'2026-04-05 17:00:44',0),
(53,53,'Invisible Safety Grill','invisible-safety-grill','INVISIBLE GRILLS',1000,1,53,'2026-04-05 17:00:44',0),
(54,54,'Invisible Grill For Safety','invisible-grill-for-safety','INVISIBLE GRILLS',1000,1,54,'2026-04-05 17:00:44',0),
(55,55,'Invisible Grill For Pigeons','invisible-grill-for-pigeons','INVISIBLE GRILLS',1000,1,55,'2026-04-05 17:00:44',0),
(56,509,'Ceiling Cloth Hangers','ceiling-cloth-hangers','CLOTH HANGERS',1000,1,56,'2026-04-05 17:00:44',0),
(57,509,'Dry Cloth Hangers','dry-cloth-hangers','CLOTH HANGERS',1000,1,57,'2026-04-05 17:00:44',0),
(58,509,'Cloth Drying Hangers','cloth-drying-hangers','CLOTH HANGERS',1000,1,58,'2026-04-05 17:00:44',0),
(59,59,'Cloth Hanger For Balcony','cloth-hanger-for-balcony','CLOTH HANGERS',1000,1,59,'2026-04-05 17:00:44',0),
(60,60,'Pulley Cloth Drying Hanger','pulley-cloth-drying-hanger','CLOTH HANGERS',1000,1,60,'2026-04-05 17:00:44',0),
(61,61,'Pulley Cloth Hanger','pulley-cloth-hanger','CLOTH HANGERS',1000,1,61,'2026-04-05 17:00:44',0),
(62,509,'Laundry Hanger Dryer','laundry-hanger-dryer','CLOTH HANGERS',1000,1,62,'2026-04-05 17:00:44',0),
(63,63,'Clothes Hanger To Dry Clothes','clothes-hanger-to-dry-clothes','CLOTH HANGERS',1000,1,63,'2026-04-05 17:00:44',0),
(64,509,'Clothes Hanger Drier','clothes-hanger-drier','CLOTH HANGERS',1000,1,64,'2026-04-05 17:00:44',0),
(5401,14,'Anti Bird Netting','anti-bird-netting','BIRD NETS',540,1,22,'2026-04-09 13:03:29',41),
(47962,66,'Balcony Safety Nets','balcony-safety-nets','SAFETY NETS',1100,1,24,'2026-04-09 15:07:26',52),
(47973,515,'Cricket Nets Price','cricket-nets-price','SPORTS NETS',480,1,35,'2026-04-09 15:07:26',39),
(57573,515,'Cricket Nets','cricket-nets','SPORTS NETS',1500,1,34,'2026-04-09 19:04:16',60),
(927249,66,'Balcony Safety Nets Services','balcony-safety-nets-services','SAFETY NETS',1000,1,2,'2026-09-19 02:25:55',0),
(927251,66,'Balcony Nets','balcony-nets','SAFETY NETS',1000,1,4,'2026-09-19 02:25:55',0),
(927252,66,'Balcony Protection Nets','balcony-protection-nets','SAFETY NETS',1000,1,5,'2026-09-19 02:25:55',0),
(927253,66,'Safety Nets For Balcony','safety-nets-for-balcony','SAFETY NETS',1000,1,6,'2026-09-19 02:25:55',0),
(927254,66,'Safety Nets For Balcony Services','safety-nets-for-balcony-services','SAFETY NETS',1000,1,7,'2026-09-19 02:25:55',0),
(927256,66,'Balcony Mesh','balcony-mesh','SAFETY NETS',1000,1,9,'2026-09-19 02:25:55',0),
(927257,66,'Nylon Balcony Safety Nets','nylon-balcony-safety-nets','SAFETY NETS',1000,1,10,'2026-09-19 02:25:55',0),
(927258,66,'Garware Balcony Safety Nets','garware-balcony-safety-nets','SAFETY NETS',1000,1,11,'2026-09-19 02:25:55',0),
(927259,66,'High Rise Balcony Safety Nets','high-rise-balcony-safety-nets','SAFETY NETS',1000,1,12,'2026-09-19 02:25:55',0),
(927260,66,'Apartment Balcony Safety Nets','apartment-balcony-safety-nets','SAFETY NETS',1000,1,13,'2026-09-19 02:25:55',0),
(927261,66,'Villa Balcony Safety Nets','villa-balcony-safety-nets','SAFETY NETS',1000,1,14,'2026-09-19 02:25:55',0),
(927262,66,'Balcony Grill Netting','balcony-grill-netting','SAFETY NETS',1000,1,15,'2026-09-19 02:25:55',0),
(927263,66,'Transparent Balcony Safety Nets','transparent-balcony-safety-nets','SAFETY NETS',1000,1,16,'2026-09-19 02:25:55',0),
(927264,66,'Balcony Fall Protection Nets','balcony-fall-protection-nets','SAFETY NETS',1000,1,17,'2026-09-19 02:25:55',0),
(927266,32,'Children Safety Nets Services','children-safety-nets-services','SAFETY NETS',1000,1,19,'2026-09-19 02:25:55',0),
(927267,32,'Child Safety Nets','child-safety-nets','SAFETY NETS',1000,1,20,'2026-09-19 02:25:55',0),
(927268,32,'Child Safety Nets Services','child-safety-nets-services','SAFETY NETS',1000,1,21,'2026-09-19 02:25:55',0),
(927269,32,'Kids Safety Nets','kids-safety-nets','SAFETY NETS',1000,1,22,'2026-09-19 02:25:55',0),
(927270,32,'Baby Safety Nets','baby-safety-nets','SAFETY NETS',1000,1,23,'2026-09-19 02:25:55',0),
(927271,32,'Child Safety Nets For Balcony','child-safety-nets-for-balcony','SAFETY NETS',1000,1,24,'2026-09-19 02:25:55',0),
(927272,32,'Staircase Child Safety Nets','staircase-child-safety-nets','SAFETY NETS',1000,1,25,'2026-09-19 02:25:55',0),
(927273,32,'Window Child Safety Nets','window-child-safety-nets','SAFETY NETS',1000,1,26,'2026-09-19 02:25:55',0),
(927274,32,'Toddler Safety Nets','toddler-safety-nets','SAFETY NETS',1000,1,27,'2026-09-19 02:25:55',0),
(927275,32,'High Tension Child Safety Nets','high-tension-child-safety-nets','SAFETY NETS',1000,1,28,'2026-09-19 02:25:55',0),
(927276,32,'Children Balcony Fall Protection','children-balcony-fall-protection','SAFETY NETS',1000,1,29,'2026-09-19 02:25:55',0),
(927278,33,'Pet Safety Nets Services','pet-safety-nets-services','SAFETY NETS',1000,1,31,'2026-09-19 02:25:55',0),
(927279,33,'Cat Safety Nets','cat-safety-nets','SAFETY NETS',1000,1,32,'2026-09-19 02:25:55',0),
(927280,33,'Cat Safety Nets Services','cat-safety-nets-services','SAFETY NETS',1000,1,33,'2026-09-19 02:25:55',0),
(927281,33,'Cat Netting For Balcony','cat-netting-for-balcony','SAFETY NETS',1000,1,34,'2026-09-19 02:25:55',0),
(927282,33,'Dog Safety Nets','dog-safety-nets','SAFETY NETS',1000,1,35,'2026-09-19 02:25:55',0),
(927283,33,'Animal Safety Nets','animal-safety-nets','SAFETY NETS',1000,1,36,'2026-09-19 02:25:55',0),
(927285,27,'Anti Monkey Nets','anti-monkey-nets','SAFETY NETS',1000,1,38,'2026-09-19 02:25:55',0),
(927286,27,'Monkey Protection Nets','monkey-protection-nets','SAFETY NETS',1000,1,39,'2026-09-19 02:25:55',0),
(927287,27,'Monkey Barrier Nets','monkey-barrier-nets','SAFETY NETS',1000,1,40,'2026-09-19 02:25:55',0),
(927288,33,'Pet Fall Protection Nets','pet-fall-protection-nets','SAFETY NETS',1000,1,41,'2026-09-19 02:25:55',0),
(927290,28,'Construction Safety Nets Services','construction-safety-nets-services','SAFETY NETS',1000,1,43,'2026-09-19 02:25:55',0),
(927291,28,'Building Safety Nets','building-safety-nets','SAFETY NETS',1000,1,44,'2026-09-19 02:25:55',0),
(927294,26,'Duct Area Safety Nets Services','duct-area-safety-nets-services','SAFETY NETS',1000,1,47,'2026-09-19 02:25:55',0),
(927295,23,'Open Area Safety Nets','open-area-safety-nets','SAFETY NETS',1000,1,48,'2026-09-19 02:25:55',0),
(927296,23,'Staircase Safety Nets','staircase-safety-nets','SAFETY NETS',1000,1,49,'2026-09-19 02:25:55',0),
(927297,23,'Shaft Safety Nets','shaft-safety-nets','SAFETY NETS',1000,1,50,'2026-09-19 02:25:55',0),
(927298,28,'Debris Netting','debris-netting','SAFETY NETS',1000,1,51,'2026-09-19 02:25:55',0),
(927301,23,'Swimming Pool Safety Nets','swimming-pool-safety-nets','SAFETY NETS',1000,1,54,'2026-09-19 02:25:55',0),
(927302,23,'Coconut Tree Safety Nets','coconut-tree-safety-nets','SAFETY NETS',1000,1,55,'2026-09-19 02:25:55',0),
(927303,28,'Scaffolding Safety Nets','scaffolding-safety-nets','SAFETY NETS',1000,1,56,'2026-09-19 02:25:55',0),
(927304,23,'Glass Balcony Safety Nets','glass-balcony-safety-nets','SAFETY NETS',1000,1,57,'2026-09-19 02:25:55',0),
(927305,23,'Heavy Duty Safety Nets','heavy-duty-safety-nets','SAFETY NETS',1000,1,58,'2026-09-19 02:25:55',0),
(927306,23,'Garware Safety Nets','garware-safety-nets','SAFETY NETS',1000,1,59,'2026-09-19 02:25:55',0),
(927307,23,'Garware Safety Nets Services','garware-safety-nets-services','SAFETY NETS',1000,1,60,'2026-09-19 02:25:55',0),
(927308,23,'Nylon Safety Nets','nylon-safety-nets','SAFETY NETS',1000,1,61,'2026-09-19 02:25:55',0),
(927309,23,'Nylon Safety Nets Services','nylon-safety-nets-services','SAFETY NETS',1000,1,62,'2026-09-19 02:25:55',0),
(927310,23,'HDPE Safety Nets','hdpe-safety-nets','SAFETY NETS',1000,1,63,'2026-09-19 02:25:55',0),
(927311,23,'Safety Netting Solutions','safety-netting-solutions','SAFETY NETS',1000,1,64,'2026-09-19 02:25:55',0),
(927312,66,'Best Balcony Safety Nets','best-balcony-safety-nets','SAFETY NETS',1000,1,65,'2026-09-19 02:25:55',0),
(927313,32,'Best Child Safety Nets','best-child-safety-nets','SAFETY NETS',1000,1,66,'2026-09-19 02:25:55',0),
(927314,28,'Best Construction Safety Nets','best-construction-safety-nets','SAFETY NETS',1000,1,67,'2026-09-19 02:25:55',0),
(927315,66,'Top Balcony Safety Nets','top-balcony-safety-nets','SAFETY NETS',1000,1,68,'2026-09-19 02:25:55',0),
(927316,32,'Top Child Safety Nets','top-child-safety-nets','SAFETY NETS',1000,1,69,'2026-09-19 02:25:55',0),
(927317,28,'Top Construction Safety Nets','top-construction-safety-nets','SAFETY NETS',1000,1,70,'2026-09-19 02:25:55',0),
(927318,32,'Garware Child Safety Nets','garware-child-safety-nets','SAFETY NETS',1000,1,71,'2026-09-19 02:25:55',0),
(927319,28,'Garware Construction Safety Nets','garware-construction-safety-nets','SAFETY NETS',1000,1,72,'2026-09-19 02:25:55',0),
(927320,66,'Same Day Balcony Safety Nets','same-day-balcony-safety-nets','SAFETY NETS',1000,1,73,'2026-09-19 02:25:55',0),
(927321,32,'Same Day Child Safety Nets','same-day-child-safety-nets','SAFETY NETS',1000,1,74,'2026-09-19 02:25:55',0),
(927322,28,'Same Day Construction Safety Nets','same-day-construction-safety-nets','SAFETY NETS',1000,1,75,'2026-09-19 02:25:55',0),
(927323,66,'Affordable Balcony Safety Nets','affordable-balcony-safety-nets','SAFETY NETS',1000,1,76,'2026-09-19 02:25:55',0),
(927324,32,'Affordable Child Safety Nets','affordable-child-safety-nets','SAFETY NETS',1000,1,77,'2026-09-19 02:25:55',0),
(927325,28,'Affordable Construction Safety Nets','affordable-construction-safety-nets','SAFETY NETS',1000,1,78,'2026-09-19 02:25:55',0),
(927326,66,'High Quality Balcony Safety Nets','high-quality-balcony-safety-nets','SAFETY NETS',1000,1,79,'2026-09-19 02:25:55',0),
(927327,32,'High Quality Child Safety Nets','high-quality-child-safety-nets','SAFETY NETS',1000,1,80,'2026-09-19 02:25:55',0),
(927328,28,'High Quality Construction Safety Nets','high-quality-construction-safety-nets','SAFETY NETS',1000,1,81,'2026-09-19 02:25:55',0),
(927329,66,'Balcony Safety Nets Near Me','balcony-safety-nets-near-me','SAFETY NETS',1000,1,82,'2026-09-19 02:25:55',0),
(927330,66,'Balcony Netting Near Me','balcony-netting-near-me','SAFETY NETS',1000,1,83,'2026-09-19 02:25:55',0),
(927331,66,'Balcony Nets Near Me','balcony-nets-near-me','SAFETY NETS',1000,1,84,'2026-09-19 02:25:55',0),
(927332,66,'Balcony Protection Nets Near Me','balcony-protection-nets-near-me','SAFETY NETS',1000,1,85,'2026-09-19 02:25:55',0),
(927333,66,'Safety Nets For Balcony Near Me','safety-nets-for-balcony-near-me','SAFETY NETS',1000,1,86,'2026-09-19 02:25:55',0),
(927334,66,'Safety Nets For Balconies Near Me','safety-nets-for-balconies-near-me','SAFETY NETS',1000,1,87,'2026-09-19 02:25:55',0),
(927335,66,'Balcony Mesh Near Me','balcony-mesh-near-me','SAFETY NETS',1000,1,88,'2026-09-19 02:25:55',0),
(927336,66,'Nylon Balcony Safety Nets Near Me','nylon-balcony-safety-nets-near-me','SAFETY NETS',1000,1,89,'2026-09-19 02:25:55',0),
(927337,66,'Garware Balcony Safety Nets Near Me','garware-balcony-safety-nets-near-me','SAFETY NETS',1000,1,90,'2026-09-19 02:25:55',0),
(927338,66,'High Rise Balcony Safety Nets Near Me','high-rise-balcony-safety-nets-near-me','SAFETY NETS',1000,1,91,'2026-09-19 02:25:55',0),
(927339,66,'Apartment Balcony Safety Nets Near Me','apartment-balcony-safety-nets-near-me','SAFETY NETS',1000,1,92,'2026-09-19 02:25:55',0),
(927340,66,'Villa Balcony Safety Nets Near Me','villa-balcony-safety-nets-near-me','SAFETY NETS',1000,1,93,'2026-09-19 02:25:55',0),
(927341,66,'Balcony Grill Netting Near Me','balcony-grill-netting-near-me','SAFETY NETS',1000,1,94,'2026-09-19 02:25:55',0),
(927342,66,'Transparent Balcony Safety Nets Near Me','transparent-balcony-safety-nets-near-me','SAFETY NETS',1000,1,95,'2026-09-19 02:25:55',0),
(927343,66,'Balcony Fall Protection Nets Near Me','balcony-fall-protection-nets-near-me','SAFETY NETS',1000,1,96,'2026-09-19 02:25:55',0),
(927344,66,'Balcony Net Fixing Near Me','balcony-net-fixing-near-me','SAFETY NETS',1000,1,97,'2026-09-19 02:25:55',0),
(927345,32,'Children Safety Nets Near Me','children-safety-nets-near-me','SAFETY NETS',1000,1,98,'2026-09-19 02:25:55',0),
(927346,32,'Child Safety Nets Near Me','child-safety-nets-near-me','SAFETY NETS',1000,1,99,'2026-09-19 02:25:55',0),
(927347,32,'Kids Safety Nets Near Me','kids-safety-nets-near-me','SAFETY NETS',1000,1,100,'2026-09-19 02:25:55',0),
(927348,32,'Baby Safety Nets Near Me','baby-safety-nets-near-me','SAFETY NETS',1000,1,101,'2026-09-19 02:25:55',0),
(927349,32,'Child Safety Nets For Balcony Near Me','child-safety-nets-for-balcony-near-me','SAFETY NETS',1000,1,102,'2026-09-19 02:25:55',0),
(927350,32,'Staircase Child Safety Nets Near Me','staircase-child-safety-nets-near-me','SAFETY NETS',1000,1,103,'2026-09-19 02:25:55',0),
(927351,32,'Window Child Safety Nets Near Me','window-child-safety-nets-near-me','SAFETY NETS',1000,1,104,'2026-09-19 02:25:55',0),
(927352,32,'Toddler Safety Nets Near Me','toddler-safety-nets-near-me','SAFETY NETS',1000,1,105,'2026-09-19 02:25:55',0),
(927353,32,'High Tension Child Safety Nets Near Me','high-tension-child-safety-nets-near-me','SAFETY NETS',1000,1,106,'2026-09-19 02:25:55',0),
(927354,32,'Children Balcony Fall Protection Near Me','children-balcony-fall-protection-near-me','SAFETY NETS',1000,1,107,'2026-09-19 02:25:55',0),
(927355,33,'Pet Safety Nets Near Me','pet-safety-nets-near-me','SAFETY NETS',1000,1,108,'2026-09-19 02:25:55',0),
(927356,33,'Cat Safety Nets Near Me','cat-safety-nets-near-me','SAFETY NETS',1000,1,109,'2026-09-19 02:25:55',0),
(927357,33,'Cat Netting For Balcony Near Me','cat-netting-for-balcony-near-me','SAFETY NETS',1000,1,110,'2026-09-19 02:25:55',0),
(927358,33,'Dog Safety Nets Near Me','dog-safety-nets-near-me','SAFETY NETS',1000,1,111,'2026-09-19 02:25:55',0),
(927359,33,'Animal Safety Nets Near Me','animal-safety-nets-near-me','SAFETY NETS',1000,1,112,'2026-09-19 02:25:55',0),
(927360,27,'Monkey Safety Nets Near Me','monkey-safety-nets-near-me','SAFETY NETS',1000,1,113,'2026-09-19 02:25:55',0),
(927361,27,'Anti Monkey Nets Near Me','anti-monkey-nets-near-me','SAFETY NETS',1000,1,114,'2026-09-19 02:25:55',0),
(927362,27,'Monkey Protection Nets Near Me','monkey-protection-nets-near-me','SAFETY NETS',1000,1,115,'2026-09-19 02:25:55',0),
(927363,27,'Monkey Barrier Nets Near Me','monkey-barrier-nets-near-me','SAFETY NETS',1000,1,116,'2026-09-19 02:25:55',0),
(927364,33,'Pet Fall Protection Nets Near Me','pet-fall-protection-nets-near-me','SAFETY NETS',1000,1,117,'2026-09-19 02:25:55',0),
(927365,28,'Construction Safety Nets Near Me','construction-safety-nets-near-me','SAFETY NETS',1000,1,118,'2026-09-19 02:25:55',0),
(927366,28,'Building Safety Nets Near Me','building-safety-nets-near-me','SAFETY NETS',1000,1,119,'2026-09-19 02:25:55',0),
(927367,29,'Industrial Safety Nets Near Me','industrial-safety-nets-near-me','SAFETY NETS',1000,1,120,'2026-09-19 02:25:55',0),
(927369,1,'Pigeon Nets Services','pigeon-nets-services','PIGEON NETS',1000,1,122,'2026-09-19 02:25:55',0),
(927372,1,'Pigeon Safety Nets Services','pigeon-safety-nets-services','PIGEON NETS',1000,1,125,'2026-09-19 02:25:55',0),
(927373,1,'Pigeon Netting','pigeon-netting','PIGEON NETS',1000,1,126,'2026-09-19 02:25:55',0),
(927374,1,'Pigeon Nets For Balcony','pigeon-nets-for-balcony','PIGEON NETS',1000,1,127,'2026-09-19 02:25:55',0),
(927375,1,'Pigeon Nets For Balcony Services','pigeon-nets-for-balcony-services','PIGEON NETS',1000,1,128,'2026-09-19 02:25:55',0),
(927377,1,'Pigeon Control Nets','pigeon-control-nets','PIGEON NETS',1000,1,130,'2026-09-19 02:25:55',0),
(927378,1,'Anti Pigeon Netting','anti-pigeon-netting','PIGEON NETS',1000,1,131,'2026-09-19 02:25:55',0),
(927379,1,'Anti Pigeon Nets','anti-pigeon-nets','PIGEON NETS',1000,1,132,'2026-09-19 02:25:55',0),
(927380,1,'Pigeon Protection Nets','pigeon-protection-nets','PIGEON NETS',1000,1,133,'2026-09-19 02:25:55',0),
(927381,1,'AC Unit Pigeon Nets','ac-unit-pigeon-nets','PIGEON NETS',1000,1,134,'2026-09-19 02:25:55',0),
(927382,1,'Duct Area Pigeon Nets','duct-area-pigeon-nets','PIGEON NETS',1000,1,135,'2026-09-19 02:25:55',0),
(927383,1,'Window Pigeon Nets','window-pigeon-nets','PIGEON NETS',1000,1,136,'2026-09-19 02:25:55',0),
(927384,1,'Terrace Pigeon Nets','terrace-pigeon-nets','PIGEON NETS',1000,1,137,'2026-09-19 02:25:55',0),
(927385,1,'Garware Pigeon Nets','garware-pigeon-nets','PIGEON NETS',1000,1,138,'2026-09-19 02:25:55',0),
(927386,1,'Nylon Pigeon Nets','nylon-pigeon-nets','PIGEON NETS',1000,1,139,'2026-09-19 02:25:55',0),
(927387,1,'Translucent Pigeon Nets','translucent-pigeon-nets','PIGEON NETS',1000,1,140,'2026-09-19 02:25:55',0),
(927388,1,'Balcony Pigeon Netting','balcony-pigeon-netting','PIGEON NETS',1000,1,141,'2026-09-19 02:25:55',0),
(927389,1,'Pigeon Barrier Nets','pigeon-barrier-nets','PIGEON NETS',1000,1,142,'2026-09-19 02:25:55',0),
(927390,1,'Pigeon Proofing Nets','pigeon-proofing-nets','PIGEON NETS',1000,1,143,'2026-09-19 02:25:55',0),
(927391,1,'Best Pigeon Nets','best-pigeon-nets','PIGEON NETS',1000,1,144,'2026-09-19 02:25:55',0),
(927392,1,'Top Pigeon Nets','top-pigeon-nets','PIGEON NETS',1000,1,145,'2026-09-19 02:25:55',0),
(927393,1,'Same Day Pigeon Nets','same-day-pigeon-nets','PIGEON NETS',1000,1,146,'2026-09-19 02:25:55',0),
(927394,1,'Affordable Pigeon Nets','affordable-pigeon-nets','PIGEON NETS',1000,1,147,'2026-09-19 02:25:55',0),
(927395,1,'High Quality Pigeon Nets','high-quality-pigeon-nets','PIGEON NETS',1000,1,148,'2026-09-19 02:25:55',0),
(927396,1,'Pigeon Nets Near Me','pigeon-nets-near-me','PIGEON NETS',1000,1,149,'2026-09-19 02:25:55',0),
(927398,1,'Pigeon Safety Nets Near Me','pigeon-safety-nets-near-me','PIGEON NETS',1000,1,151,'2026-09-19 02:25:55',0),
(927399,1,'Pigeon Netting Near Me','pigeon-netting-near-me','PIGEON NETS',1000,1,152,'2026-09-19 02:25:55',0),
(927400,1,'Pigeon Nets For Balcony Near Me','pigeon-nets-for-balcony-near-me','PIGEON NETS',1000,1,153,'2026-09-19 02:25:55',0),
(927402,1,'Pigeon Control Nets Near Me','pigeon-control-nets-near-me','PIGEON NETS',1000,1,155,'2026-09-19 02:25:55',0),
(927403,1,'Anti Pigeon Netting Near Me','anti-pigeon-netting-near-me','PIGEON NETS',1000,1,156,'2026-09-19 02:25:55',0),
(927404,1,'Anti Pigeon Nets Near Me','anti-pigeon-nets-near-me','PIGEON NETS',1000,1,157,'2026-09-19 02:25:55',0),
(927405,1,'Pigeon Protection Nets Near Me','pigeon-protection-nets-near-me','PIGEON NETS',1000,1,158,'2026-09-19 02:25:55',0),
(927406,1,'AC Unit Pigeon Nets Near Me','ac-unit-pigeon-nets-near-me','PIGEON NETS',1000,1,159,'2026-09-19 02:25:55',0),
(927407,1,'Duct Area Pigeon Nets Near Me','duct-area-pigeon-nets-near-me','PIGEON NETS',1000,1,160,'2026-09-19 02:25:55',0),
(927408,1,'Window Pigeon Nets Near Me','window-pigeon-nets-near-me','PIGEON NETS',1000,1,161,'2026-09-19 02:25:55',0),
(927409,1,'Terrace Pigeon Nets Near Me','terrace-pigeon-nets-near-me','PIGEON NETS',1000,1,162,'2026-09-19 02:25:55',0),
(927410,1,'Garware Pigeon Nets Near Me','garware-pigeon-nets-near-me','PIGEON NETS',1000,1,163,'2026-09-19 02:25:55',0),
(927411,1,'Nylon Pigeon Nets Near Me','nylon-pigeon-nets-near-me','PIGEON NETS',1000,1,164,'2026-09-19 02:25:55',0),
(927412,1,'Translucent Pigeon Nets Near Me','translucent-pigeon-nets-near-me','PIGEON NETS',1000,1,165,'2026-09-19 02:25:55',0),
(927413,1,'Balcony Pigeon Netting Near Me','balcony-pigeon-netting-near-me','PIGEON NETS',1000,1,166,'2026-09-19 02:25:55',0),
(927414,1,'Pigeon Barrier Nets Near Me','pigeon-barrier-nets-near-me','PIGEON NETS',1000,1,167,'2026-09-19 02:25:55',0),
(927415,1,'Pigeon Proofing Nets Near Me','pigeon-proofing-nets-near-me','PIGEON NETS',1000,1,168,'2026-09-19 02:25:55',0),
(927418,1,'Pigeon Safety Nets Installation','pigeon-safety-nets-installation','PIGEON NETS',1000,1,171,'2026-09-19 02:25:55',0),
(927419,1,'Pigeon Netting Installation','pigeon-netting-installation','PIGEON NETS',1000,1,172,'2026-09-19 02:25:55',0),
(927420,1,'Pigeon Nets For Balcony Installation','pigeon-nets-for-balcony-installation','PIGEON NETS',1000,1,173,'2026-09-19 02:25:55',0),
(927421,1,'Pigeon Net For Balcony Installation','pigeon-net-for-balcony-installation','PIGEON NETS',1000,1,174,'2026-09-19 02:25:55',0),
(927422,1,'Pigeon Control Nets Installation','pigeon-control-nets-installation','PIGEON NETS',1000,1,175,'2026-09-19 02:25:55',0),
(927423,1,'Anti Pigeon Netting Installation','anti-pigeon-netting-installation','PIGEON NETS',1000,1,176,'2026-09-19 02:25:55',0),
(927424,1,'Anti Pigeon Nets Installation','anti-pigeon-nets-installation','PIGEON NETS',1000,1,177,'2026-09-19 02:25:55',0),
(927425,1,'Pigeon Protection Nets Installation','pigeon-protection-nets-installation','PIGEON NETS',1000,1,178,'2026-09-19 02:25:55',0),
(927426,1,'AC Unit Pigeon Nets Installation','ac-unit-pigeon-nets-installation','PIGEON NETS',1000,1,179,'2026-09-19 02:25:55',0),
(927427,1,'Duct Area Pigeon Nets Installation','duct-area-pigeon-nets-installation','PIGEON NETS',1000,1,180,'2026-09-19 02:25:55',0),
(927428,1,'Window Pigeon Nets Installation','window-pigeon-nets-installation','PIGEON NETS',1000,1,181,'2026-09-19 02:25:55',0),
(927429,1,'Terrace Pigeon Nets Installation','terrace-pigeon-nets-installation','PIGEON NETS',1000,1,182,'2026-09-19 02:25:55',0),
(927430,1,'Garware Pigeon Nets Installation','garware-pigeon-nets-installation','PIGEON NETS',1000,1,183,'2026-09-19 02:25:55',0),
(927431,1,'Nylon Pigeon Nets Installation','nylon-pigeon-nets-installation','PIGEON NETS',1000,1,184,'2026-09-19 02:25:55',0),
(927432,1,'Translucent Pigeon Nets Installation','translucent-pigeon-nets-installation','PIGEON NETS',1000,1,185,'2026-09-19 02:25:55',0),
(927433,1,'Balcony Pigeon Netting Installation','balcony-pigeon-netting-installation','PIGEON NETS',1000,1,186,'2026-09-19 02:25:55',0),
(927434,1,'Pigeon Barrier Nets Installation','pigeon-barrier-nets-installation','PIGEON NETS',1000,1,187,'2026-09-19 02:25:55',0),
(927435,1,'Pigeon Proofing Nets Installation','pigeon-proofing-nets-installation','PIGEON NETS',1000,1,188,'2026-09-19 02:25:55',0),
(927436,1,'Pigeon Nets Price','pigeon-nets-price','PIGEON NETS',1000,1,189,'2026-09-19 02:25:55',0),
(927438,1,'Pigeon Safety Nets Price','pigeon-safety-nets-price','PIGEON NETS',1000,1,191,'2026-09-19 02:25:55',0),
(927439,1,'Pigeon Netting Price','pigeon-netting-price','PIGEON NETS',1000,1,192,'2026-09-19 02:25:55',0),
(927440,1,'Pigeon Nets For Balcony Price','pigeon-nets-for-balcony-price','PIGEON NETS',1000,1,193,'2026-09-19 02:25:55',0),
(927441,1,'Pigeon Net For Balcony Price','pigeon-net-for-balcony-price','PIGEON NETS',1000,1,194,'2026-09-19 02:25:55',0),
(927442,1,'Pigeon Control Nets Price','pigeon-control-nets-price','PIGEON NETS',1000,1,195,'2026-09-19 02:25:55',0),
(927443,1,'Anti Pigeon Netting Price','anti-pigeon-netting-price','PIGEON NETS',1000,1,196,'2026-09-19 02:25:55',0),
(927444,1,'Anti Pigeon Nets Price','anti-pigeon-nets-price','PIGEON NETS',1000,1,197,'2026-09-19 02:25:55',0),
(927445,1,'Pigeon Protection Nets Price','pigeon-protection-nets-price','PIGEON NETS',1000,1,198,'2026-09-19 02:25:55',0),
(927446,1,'AC Unit Pigeon Nets Price','ac-unit-pigeon-nets-price','PIGEON NETS',1000,1,199,'2026-09-19 02:25:55',0),
(927447,1,'Duct Area Pigeon Nets Price','duct-area-pigeon-nets-price','PIGEON NETS',1000,1,200,'2026-09-19 02:25:55',0),
(927448,1,'Window Pigeon Nets Price','window-pigeon-nets-price','PIGEON NETS',1000,1,201,'2026-09-19 02:25:55',0),
(927449,1,'Terrace Pigeon Nets Price','terrace-pigeon-nets-price','PIGEON NETS',1000,1,202,'2026-09-19 02:25:55',0),
(927450,1,'Garware Pigeon Nets Price','garware-pigeon-nets-price','PIGEON NETS',1000,1,203,'2026-09-19 02:25:55',0),
(927451,1,'Nylon Pigeon Nets Price','nylon-pigeon-nets-price','PIGEON NETS',1000,1,204,'2026-09-19 02:25:55',0),
(927452,1,'Translucent Pigeon Nets Price','translucent-pigeon-nets-price','PIGEON NETS',1000,1,205,'2026-09-19 02:25:55',0),
(927453,1,'Balcony Pigeon Netting Price','balcony-pigeon-netting-price','PIGEON NETS',1000,1,206,'2026-09-19 02:25:55',0),
(927454,1,'Pigeon Barrier Nets Price','pigeon-barrier-nets-price','PIGEON NETS',1000,1,207,'2026-09-19 02:25:55',0),
(927455,1,'Pigeon Proofing Nets Price','pigeon-proofing-nets-price','PIGEON NETS',1000,1,208,'2026-09-19 02:25:55',0),
(927456,1,'Pigeon Nets Dealers','pigeon-nets-dealers','PIGEON NETS',1000,1,209,'2026-09-19 02:25:55',0),
(927457,1,'Pigeon Net Dealers','pigeon-net-dealers','PIGEON NETS',1000,1,210,'2026-09-19 02:25:55',0),
(927458,1,'Pigeon Safety Nets Dealers','pigeon-safety-nets-dealers','PIGEON NETS',1000,1,211,'2026-09-19 02:25:55',0),
(927459,1,'Pigeon Netting Dealers','pigeon-netting-dealers','PIGEON NETS',1000,1,212,'2026-09-19 02:25:55',0),
(927460,1,'Pigeon Nets For Balcony Dealers','pigeon-nets-for-balcony-dealers','PIGEON NETS',1000,1,213,'2026-09-19 02:25:55',0),
(927461,1,'Pigeon Net For Balcony Dealers','pigeon-net-for-balcony-dealers','PIGEON NETS',1000,1,214,'2026-09-19 02:25:55',0),
(927462,1,'Pigeon Control Nets Dealers','pigeon-control-nets-dealers','PIGEON NETS',1000,1,215,'2026-09-19 02:25:55',0),
(927463,1,'Anti Pigeon Netting Dealers','anti-pigeon-netting-dealers','PIGEON NETS',1000,1,216,'2026-09-19 02:25:55',0),
(927464,1,'Anti Pigeon Nets Dealers','anti-pigeon-nets-dealers','PIGEON NETS',1000,1,217,'2026-09-19 02:25:55',0),
(927465,1,'Pigeon Protection Nets Dealers','pigeon-protection-nets-dealers','PIGEON NETS',1000,1,218,'2026-09-19 02:25:55',0),
(927466,1,'AC Unit Pigeon Nets Dealers','ac-unit-pigeon-nets-dealers','PIGEON NETS',1000,1,219,'2026-09-19 02:25:55',0),
(927467,1,'Duct Area Pigeon Nets Dealers','duct-area-pigeon-nets-dealers','PIGEON NETS',1000,1,220,'2026-09-19 02:25:55',0),
(927468,1,'Window Pigeon Nets Dealers','window-pigeon-nets-dealers','PIGEON NETS',1000,1,221,'2026-09-19 02:25:55',0),
(927469,1,'Terrace Pigeon Nets Dealers','terrace-pigeon-nets-dealers','PIGEON NETS',1000,1,222,'2026-09-19 02:25:55',0),
(927470,1,'Garware Pigeon Nets Dealers','garware-pigeon-nets-dealers','PIGEON NETS',1000,1,223,'2026-09-19 02:25:55',0),
(927471,1,'Nylon Pigeon Nets Dealers','nylon-pigeon-nets-dealers','PIGEON NETS',1000,1,224,'2026-09-19 02:25:55',0),
(927472,1,'Translucent Pigeon Nets Dealers','translucent-pigeon-nets-dealers','PIGEON NETS',1000,1,225,'2026-09-19 02:25:55',0),
(927473,1,'Balcony Pigeon Netting Dealers','balcony-pigeon-netting-dealers','PIGEON NETS',1000,1,226,'2026-09-19 02:25:55',0),
(927474,1,'Pigeon Barrier Nets Dealers','pigeon-barrier-nets-dealers','PIGEON NETS',1000,1,227,'2026-09-19 02:25:55',0),
(927475,1,'Pigeon Proofing Nets Dealers','pigeon-proofing-nets-dealers','PIGEON NETS',1000,1,228,'2026-09-19 02:25:55',0),
(927476,1,'Pigeon Nets Fixing','pigeon-nets-fixing','PIGEON NETS',1000,1,229,'2026-09-19 02:25:55',0),
(927477,1,'Pigeon Safety Nets Fixing','pigeon-safety-nets-fixing','PIGEON NETS',1000,1,230,'2026-09-19 02:25:55',0),
(927478,1,'Pigeon Nets For Balcony Fixing','pigeon-nets-for-balcony-fixing','PIGEON NETS',1000,1,231,'2026-09-19 02:25:55',0),
(927479,1,'Pigeon Nets Cost','pigeon-nets-cost','PIGEON NETS',1000,1,232,'2026-09-19 02:25:55',0),
(927480,1,'Pigeon Safety Nets Cost','pigeon-safety-nets-cost','PIGEON NETS',1000,1,233,'2026-09-19 02:25:55',0),
(927481,1,'Pigeon Nets For Balcony Cost','pigeon-nets-for-balcony-cost','PIGEON NETS',1000,1,234,'2026-09-19 02:25:55',0),
(927482,1,'Pigeon Nets Contractors','pigeon-nets-contractors','PIGEON NETS',1000,1,235,'2026-09-19 02:25:55',0),
(927483,1,'Pigeon Net Contractors','pigeon-net-contractors','PIGEON NETS',1000,1,236,'2026-09-19 02:25:55',0),
(927484,1,'Pigeon Safety Nets Contractors','pigeon-safety-nets-contractors','PIGEON NETS',1000,1,237,'2026-09-19 02:25:55',0),
(927485,1,'Pigeon Netting Contractors','pigeon-netting-contractors','PIGEON NETS',1000,1,238,'2026-09-19 02:25:55',0),
(927486,1,'Pigeon Nets For Balcony Contractors','pigeon-nets-for-balcony-contractors','PIGEON NETS',1000,1,239,'2026-09-19 02:25:55',0),
(927487,1,'Pigeon Net For Balcony Contractors','pigeon-net-for-balcony-contractors','PIGEON NETS',1000,1,240,'2026-09-19 02:25:55',0),
(927489,10,'Anti Bird Netting Services','anti-bird-netting-services','BIRD NETS',1000,1,242,'2026-09-19 02:25:55',0),
(927490,10,'Bird Safety Nets','bird-safety-nets','BIRD NETS',1000,1,243,'2026-09-19 02:25:55',0),
(927493,10,'Bird Nets For Balcony','bird-nets-for-balcony','BIRD NETS',1000,1,246,'2026-09-19 02:25:55',0),
(927494,10,'Bird Nets For Balcony Services','bird-nets-for-balcony-services','BIRD NETS',1000,1,247,'2026-09-19 02:25:55',0),
(927496,10,'Window Bird Nets','window-bird-nets','BIRD NETS',1000,1,249,'2026-09-19 02:25:55',0),
(927497,10,'Terrace Bird Nets','terrace-bird-nets','BIRD NETS',1000,1,250,'2026-09-19 02:25:55',0),
(927498,10,'Commercial Bird Netting','commercial-bird-netting','BIRD NETS',1000,1,251,'2026-09-19 02:25:55',0),
(927500,10,'Warehouse Bird Netting','warehouse-bird-netting','BIRD NETS',1000,1,253,'2026-09-19 02:25:55',0),
(927501,10,'Factory Bird Netting','factory-bird-netting','BIRD NETS',1000,1,254,'2026-09-19 02:25:55',0),
(927502,10,'Transparent Bird Netting','transparent-bird-netting','BIRD NETS',1000,1,255,'2026-09-19 02:25:55',0),
(927503,10,'Sparrow Protection Nets','sparrow-protection-nets','BIRD NETS',1000,1,256,'2026-09-19 02:25:55',0),
(927504,10,'Crow Protection Nets','crow-protection-nets','BIRD NETS',1000,1,257,'2026-09-19 02:25:55',0),
(927505,10,'Bird Protection Nets','bird-protection-nets','BIRD NETS',1000,1,258,'2026-09-19 02:25:55',0),
(927506,10,'Garware Bird Nets','garware-bird-nets','BIRD NETS',1000,1,259,'2026-09-19 02:25:55',0),
(927507,10,'Nylon Bird Nets','nylon-bird-nets','BIRD NETS',1000,1,260,'2026-09-19 02:25:55',0),
(927508,10,'Bird Proofing Solutions','bird-proofing-solutions','BIRD NETS',1000,1,261,'2026-09-19 02:25:55',0),
(927509,512,'Anti Bird Spikes','anti-bird-spikes','BIRD NETS',1000,1,262,'2026-09-19 02:25:55',0),
(927510,512,'Anti Bird Spikes Services','anti-bird-spikes-services','BIRD NETS',1000,1,263,'2026-09-19 02:25:55',0),
(927511,512,'Bird Spikes','bird-spikes','BIRD NETS',1000,1,264,'2026-09-19 02:25:55',0),
(927512,512,'Bird Spikes Services','bird-spikes-services','BIRD NETS',1000,1,265,'2026-09-19 02:25:55',0),
(927513,512,'Pigeon Spikes','pigeon-spikes','BIRD NETS',1000,1,266,'2026-09-19 02:25:55',0),
(927514,512,'Stainless Steel Bird Spikes','stainless-steel-bird-spikes','BIRD NETS',1000,1,267,'2026-09-19 02:25:55',0),
(927515,512,'Polycarbonate Bird Spikes','polycarbonate-bird-spikes','BIRD NETS',1000,1,268,'2026-09-19 02:25:55',0),
(927516,512,'Window Bird Spikes','window-bird-spikes','BIRD NETS',1000,1,269,'2026-09-19 02:25:55',0),
(927517,512,'Balcony Bird Spikes','balcony-bird-spikes','BIRD NETS',1000,1,270,'2026-09-19 02:25:55',0),
(927518,512,'AC Outdoor Unit Bird Spikes','ac-outdoor-unit-bird-spikes','BIRD NETS',1000,1,271,'2026-09-19 02:25:55',0),
(927519,512,'Bird Deterrent Spikes','bird-deterrent-spikes','BIRD NETS',1000,1,272,'2026-09-19 02:25:55',0),
(927520,512,'Commercial Bird Spikes','commercial-bird-spikes','BIRD NETS',1000,1,273,'2026-09-19 02:25:55',0),
(927521,10,'Best Anti Bird Netting','best-anti-bird-netting','BIRD NETS',1000,1,274,'2026-09-19 02:25:55',0),
(927522,10,'Top Anti Bird Netting','top-anti-bird-netting','BIRD NETS',1000,1,275,'2026-09-19 02:25:55',0),
(927523,10,'Garware Anti Bird Netting','garware-anti-bird-netting','BIRD NETS',1000,1,276,'2026-09-19 02:25:55',0),
(927524,10,'Same Day Anti Bird Netting','same-day-anti-bird-netting','BIRD NETS',1000,1,277,'2026-09-19 02:25:55',0),
(927525,10,'Affordable Anti Bird Netting','affordable-anti-bird-netting','BIRD NETS',1000,1,278,'2026-09-19 02:25:55',0),
(927526,10,'High Quality Anti Bird Netting','high-quality-anti-bird-netting','BIRD NETS',1000,1,279,'2026-09-19 02:25:55',0),
(927527,10,'Anti Bird Netting Near Me','anti-bird-netting-near-me','BIRD NETS',1000,1,280,'2026-09-19 02:25:55',0),
(927528,10,'Bird Safety Nets Near Me','bird-safety-nets-near-me','BIRD NETS',1000,1,281,'2026-09-19 02:25:55',0),
(927529,10,'Bird Nets Near Me','bird-nets-near-me','BIRD NETS',1000,1,282,'2026-09-19 02:25:55',0),
(927531,10,'Bird Netting Services Near Me','bird-netting-services-near-me','BIRD NETS',1000,1,284,'2026-09-19 02:25:55',0),
(927532,10,'Bird Nets For Balcony Near Me','bird-nets-for-balcony-near-me','BIRD NETS',1000,1,285,'2026-09-19 02:25:55',0),
(927533,10,'Bird Net For Balcony Near Me','bird-net-for-balcony-near-me','BIRD NETS',1000,1,286,'2026-09-19 02:25:55',0),
(927534,10,'Window Bird Nets Near Me','window-bird-nets-near-me','BIRD NETS',1000,1,287,'2026-09-19 02:25:55',0),
(927535,10,'Terrace Bird Nets Near Me','terrace-bird-nets-near-me','BIRD NETS',1000,1,288,'2026-09-19 02:25:55',0),
(927536,10,'Commercial Bird Netting Near Me','commercial-bird-netting-near-me','BIRD NETS',1000,1,289,'2026-09-19 02:25:55',0),
(927537,10,'Industrial Bird Netting Near Me','industrial-bird-netting-near-me','BIRD NETS',1000,1,290,'2026-09-19 02:25:55',0),
(927538,10,'Warehouse Bird Netting Near Me','warehouse-bird-netting-near-me','BIRD NETS',1000,1,291,'2026-09-19 02:25:55',0),
(927539,10,'Factory Bird Netting Near Me','factory-bird-netting-near-me','BIRD NETS',1000,1,292,'2026-09-19 02:25:55',0),
(927540,10,'Transparent Bird Netting Near Me','transparent-bird-netting-near-me','BIRD NETS',1000,1,293,'2026-09-19 02:25:55',0),
(927541,10,'Sparrow Protection Nets Near Me','sparrow-protection-nets-near-me','BIRD NETS',1000,1,294,'2026-09-19 02:25:55',0),
(927542,10,'Crow Protection Nets Near Me','crow-protection-nets-near-me','BIRD NETS',1000,1,295,'2026-09-19 02:25:55',0),
(927543,10,'Bird Protection Nets Near Me','bird-protection-nets-near-me','BIRD NETS',1000,1,296,'2026-09-19 02:25:55',0),
(927544,10,'Garware Bird Nets Near Me','garware-bird-nets-near-me','BIRD NETS',1000,1,297,'2026-09-19 02:25:55',0),
(927545,10,'Nylon Bird Nets Near Me','nylon-bird-nets-near-me','BIRD NETS',1000,1,298,'2026-09-19 02:25:55',0),
(927546,10,'Bird Proofing Solutions Near Me','bird-proofing-solutions-near-me','BIRD NETS',1000,1,299,'2026-09-19 02:25:55',0),
(927547,512,'Anti Bird Spikes Near Me','anti-bird-spikes-near-me','BIRD NETS',1000,1,300,'2026-09-19 02:25:55',0),
(927548,512,'Bird Spikes Near Me','bird-spikes-near-me','BIRD NETS',1000,1,301,'2026-09-19 02:25:55',0),
(927549,512,'Pigeon Spikes Near Me','pigeon-spikes-near-me','BIRD NETS',1000,1,302,'2026-09-19 02:25:55',0),
(927550,512,'Stainless Steel Bird Spikes Near Me','stainless-steel-bird-spikes-near-me','BIRD NETS',1000,1,303,'2026-09-19 02:25:55',0),
(927551,512,'Polycarbonate Bird Spikes Near Me','polycarbonate-bird-spikes-near-me','BIRD NETS',1000,1,304,'2026-09-19 02:25:55',0),
(927552,512,'Window Bird Spikes Near Me','window-bird-spikes-near-me','BIRD NETS',1000,1,305,'2026-09-19 02:25:55',0),
(927553,512,'Balcony Bird Spikes Near Me','balcony-bird-spikes-near-me','BIRD NETS',1000,1,306,'2026-09-19 02:25:55',0),
(927554,512,'AC Outdoor Unit Bird Spikes Near Me','ac-outdoor-unit-bird-spikes-near-me','BIRD NETS',1000,1,307,'2026-09-19 02:25:55',0),
(927555,512,'Bird Deterrent Spikes Near Me','bird-deterrent-spikes-near-me','BIRD NETS',1000,1,308,'2026-09-19 02:25:55',0),
(927556,512,'Commercial Bird Spikes Near Me','commercial-bird-spikes-near-me','BIRD NETS',1000,1,309,'2026-09-19 02:25:55',0),
(927557,10,'Anti Bird Netting Installation','anti-bird-netting-installation','BIRD NETS',1000,1,310,'2026-09-19 02:25:55',0),
(927558,10,'Bird Safety Nets Installation','bird-safety-nets-installation','BIRD NETS',1000,1,311,'2026-09-19 02:25:55',0),
(927559,10,'Bird Nets Installation','bird-nets-installation','BIRD NETS',1000,1,312,'2026-09-19 02:25:55',0),
(927560,10,'Bird Net Installation','bird-net-installation','BIRD NETS',1000,1,313,'2026-09-19 02:25:55',0),
(927561,10,'Bird Netting Services Installation','bird-netting-services-installation','BIRD NETS',1000,1,314,'2026-09-19 02:25:55',0),
(927562,10,'Bird Nets For Balcony Installation','bird-nets-for-balcony-installation','BIRD NETS',1000,1,315,'2026-09-19 02:25:55',0),
(927563,10,'Bird Net For Balcony Installation','bird-net-for-balcony-installation','BIRD NETS',1000,1,316,'2026-09-19 02:25:55',0),
(927564,10,'Window Bird Nets Installation','window-bird-nets-installation','BIRD NETS',1000,1,317,'2026-09-19 02:25:55',0),
(927565,10,'Terrace Bird Nets Installation','terrace-bird-nets-installation','BIRD NETS',1000,1,318,'2026-09-19 02:25:55',0),
(927566,10,'Commercial Bird Netting Installation','commercial-bird-netting-installation','BIRD NETS',1000,1,319,'2026-09-19 02:25:55',0),
(927567,10,'Industrial Bird Netting Installation','industrial-bird-netting-installation','BIRD NETS',1000,1,320,'2026-09-19 02:25:55',0),
(927568,10,'Warehouse Bird Netting Installation','warehouse-bird-netting-installation','BIRD NETS',1000,1,321,'2026-09-19 02:25:55',0),
(927569,10,'Factory Bird Netting Installation','factory-bird-netting-installation','BIRD NETS',1000,1,322,'2026-09-19 02:25:55',0),
(927570,10,'Transparent Bird Netting Installation','transparent-bird-netting-installation','BIRD NETS',1000,1,323,'2026-09-19 02:25:55',0),
(927571,10,'Sparrow Protection Nets Installation','sparrow-protection-nets-installation','BIRD NETS',1000,1,324,'2026-09-19 02:25:55',0),
(927572,10,'Crow Protection Nets Installation','crow-protection-nets-installation','BIRD NETS',1000,1,325,'2026-09-19 02:25:55',0),
(927573,10,'Bird Protection Nets Installation','bird-protection-nets-installation','BIRD NETS',1000,1,326,'2026-09-19 02:25:55',0),
(927574,10,'Garware Bird Nets Installation','garware-bird-nets-installation','BIRD NETS',1000,1,327,'2026-09-19 02:25:55',0),
(927575,10,'Nylon Bird Nets Installation','nylon-bird-nets-installation','BIRD NETS',1000,1,328,'2026-09-19 02:25:55',0),
(927576,10,'Bird Proofing Solutions Installation','bird-proofing-solutions-installation','BIRD NETS',1000,1,329,'2026-09-19 02:25:55',0),
(927577,512,'Anti Bird Spikes Installation','anti-bird-spikes-installation','BIRD NETS',1000,1,330,'2026-09-19 02:25:55',0),
(927578,512,'Bird Spikes Installation','bird-spikes-installation','BIRD NETS',1000,1,331,'2026-09-19 02:25:55',0),
(927579,512,'Pigeon Spikes Installation','pigeon-spikes-installation','BIRD NETS',1000,1,332,'2026-09-19 02:25:55',0),
(927580,512,'Stainless Steel Bird Spikes Installation','stainless-steel-bird-spikes-installation','BIRD NETS',1000,1,333,'2026-09-19 02:25:55',0),
(927581,512,'Polycarbonate Bird Spikes Installation','polycarbonate-bird-spikes-installation','BIRD NETS',1000,1,334,'2026-09-19 02:25:55',0),
(927582,512,'Window Bird Spikes Installation','window-bird-spikes-installation','BIRD NETS',1000,1,335,'2026-09-19 02:25:55',0),
(927583,512,'Balcony Bird Spikes Installation','balcony-bird-spikes-installation','BIRD NETS',1000,1,336,'2026-09-19 02:25:55',0),
(927584,512,'AC Outdoor Unit Bird Spikes Installation','ac-outdoor-unit-bird-spikes-installation','BIRD NETS',1000,1,337,'2026-09-19 02:25:55',0),
(927585,512,'Bird Deterrent Spikes Installation','bird-deterrent-spikes-installation','BIRD NETS',1000,1,338,'2026-09-19 02:25:55',0),
(927586,512,'Commercial Bird Spikes Installation','commercial-bird-spikes-installation','BIRD NETS',1000,1,339,'2026-09-19 02:25:55',0),
(927587,10,'Anti Bird Netting Price','anti-bird-netting-price','BIRD NETS',1000,1,340,'2026-09-19 02:25:55',0),
(927589,47,'Invisible Grills Services','invisible-grills-services','INVISIBLE GRILLS',1000,1,342,'2026-09-19 02:25:55',0),
(927590,47,'Balcony Invisible Grills','balcony-invisible-grills','INVISIBLE GRILLS',1000,1,343,'2026-09-19 02:25:55',0),
(927591,47,'Balcony Invisible Grills Services','balcony-invisible-grills-services','INVISIBLE GRILLS',1000,1,344,'2026-09-19 02:25:55',0),
(927592,47,'Window Invisible Grills','window-invisible-grills','INVISIBLE GRILLS',1000,1,345,'2026-09-19 02:25:55',0),
(927593,47,'Invisible Grills For Balcony','invisible-grills-for-balcony','INVISIBLE GRILLS',1000,1,346,'2026-09-19 02:25:55',0),
(927594,47,'Invisible Grills For Windows','invisible-grills-for-windows','INVISIBLE GRILLS',1000,1,347,'2026-09-19 02:25:55',0),
(927595,47,'SS 316 Invisible Grills','ss-316-invisible-grills','INVISIBLE GRILLS',1000,1,348,'2026-09-19 02:25:55',0),
(927596,47,'SS 316 Invisible Grills Services','ss-316-invisible-grills-services','INVISIBLE GRILLS',1000,1,349,'2026-09-19 02:25:55',0),
(927597,47,'Stainless Steel Invisible Grills','stainless-steel-invisible-grills','INVISIBLE GRILLS',1000,1,350,'2026-09-19 02:25:55',0),
(927598,47,'Modern Invisible Grills','modern-invisible-grills','INVISIBLE GRILLS',1000,1,351,'2026-09-19 02:25:55',0),
(927599,47,'Invisible Safety Grills','invisible-safety-grills','INVISIBLE GRILLS',1000,1,352,'2026-09-19 02:25:55',0),
(927600,47,'Transparent Invisible Grills','transparent-invisible-grills','INVISIBLE GRILLS',1000,1,353,'2026-09-19 02:25:55',0),
(927601,47,'High Rise Invisible Grills','high-rise-invisible-grills','INVISIBLE GRILLS',1000,1,354,'2026-09-19 02:25:55',0),
(927602,47,'Apartment Invisible Grills','apartment-invisible-grills','INVISIBLE GRILLS',1000,1,355,'2026-09-19 02:25:55',0),
(927603,47,'Villa Invisible Grills','villa-invisible-grills','INVISIBLE GRILLS',1000,1,356,'2026-09-19 02:25:55',0),
(927604,47,'Invisible Balcony Safety Grills','invisible-balcony-safety-grills','INVISIBLE GRILLS',1000,1,357,'2026-09-19 02:25:55',0),
(927605,47,'Invisible Safety Grill For Windows','invisible-safety-grill-for-windows','INVISIBLE GRILLS',1000,1,358,'2026-09-19 02:25:55',0),
(927606,47,'Best Invisible Grills','best-invisible-grills','INVISIBLE GRILLS',1000,1,359,'2026-09-19 02:25:55',0),
(927607,47,'Top Invisible Grills','top-invisible-grills','INVISIBLE GRILLS',1000,1,360,'2026-09-19 02:25:55',0),
(927608,47,'Garware Invisible Grills','garware-invisible-grills','INVISIBLE GRILLS',1000,1,361,'2026-09-19 02:25:55',0),
(927609,47,'Same Day Invisible Grills','same-day-invisible-grills','INVISIBLE GRILLS',1000,1,362,'2026-09-19 02:25:55',0),
(927610,47,'Affordable Invisible Grills','affordable-invisible-grills','INVISIBLE GRILLS',1000,1,363,'2026-09-19 02:25:55',0),
(927611,47,'High Quality Invisible Grills','high-quality-invisible-grills','INVISIBLE GRILLS',1000,1,364,'2026-09-19 02:25:55',0),
(927612,47,'Invisible Grills Near Me','invisible-grills-near-me','INVISIBLE GRILLS',1000,1,365,'2026-09-19 02:25:55',0),
(927613,47,'Balcony Invisible Grills Near Me','balcony-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,366,'2026-09-19 02:25:55',0),
(927614,47,'Window Invisible Grills Near Me','window-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,367,'2026-09-19 02:25:55',0),
(927615,47,'Invisible Grills For Balcony Near Me','invisible-grills-for-balcony-near-me','INVISIBLE GRILLS',1000,1,368,'2026-09-19 02:25:55',0),
(927616,47,'Invisible Grills For Windows Near Me','invisible-grills-for-windows-near-me','INVISIBLE GRILLS',1000,1,369,'2026-09-19 02:25:55',0),
(927617,47,'SS 316 Invisible Grills Near Me','ss-316-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,370,'2026-09-19 02:25:55',0),
(927618,47,'Stainless Steel Invisible Grills Near Me','stainless-steel-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,371,'2026-09-19 02:25:55',0),
(927619,47,'Modern Invisible Grills Near Me','modern-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,372,'2026-09-19 02:25:55',0),
(927620,47,'Invisible Safety Grills Near Me','invisible-safety-grills-near-me','INVISIBLE GRILLS',1000,1,373,'2026-09-19 02:25:55',0),
(927621,47,'Transparent Invisible Grills Near Me','transparent-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,374,'2026-09-19 02:25:55',0),
(927622,47,'High Rise Invisible Grills Near Me','high-rise-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,375,'2026-09-19 02:25:55',0),
(927623,47,'Apartment Invisible Grills Near Me','apartment-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,376,'2026-09-19 02:25:55',0),
(927624,47,'Villa Invisible Grills Near Me','villa-invisible-grills-near-me','INVISIBLE GRILLS',1000,1,377,'2026-09-19 02:25:55',0),
(927625,47,'Invisible Balcony Safety Grills Near Me','invisible-balcony-safety-grills-near-me','INVISIBLE GRILLS',1000,1,378,'2026-09-19 02:25:55',0),
(927626,47,'Invisible Safety Grill For Windows Near Me','invisible-safety-grill-for-windows-near-me','INVISIBLE GRILLS',1000,1,379,'2026-09-19 02:25:55',0),
(927627,47,'Invisible Grills Installation','invisible-grills-installation','INVISIBLE GRILLS',1000,1,380,'2026-09-19 02:25:55',0),
(927628,47,'Balcony Invisible Grills Installation','balcony-invisible-grills-installation','INVISIBLE GRILLS',1000,1,381,'2026-09-19 02:25:55',0),
(927629,47,'Window Invisible Grills Installation','window-invisible-grills-installation','INVISIBLE GRILLS',1000,1,382,'2026-09-19 02:25:55',0),
(927630,47,'Invisible Grills For Balcony Installation','invisible-grills-for-balcony-installation','INVISIBLE GRILLS',1000,1,383,'2026-09-19 02:25:55',0),
(927631,47,'Invisible Grills For Windows Installation','invisible-grills-for-windows-installation','INVISIBLE GRILLS',1000,1,384,'2026-09-19 02:25:55',0),
(927632,47,'SS 316 Invisible Grills Installation','ss-316-invisible-grills-installation','INVISIBLE GRILLS',1000,1,385,'2026-09-19 02:25:55',0),
(927633,47,'Stainless Steel Invisible Grills Installation','stainless-steel-invisible-grills-installation','INVISIBLE GRILLS',1000,1,386,'2026-09-19 02:25:55',0),
(927634,47,'Modern Invisible Grills Installation','modern-invisible-grills-installation','INVISIBLE GRILLS',1000,1,387,'2026-09-19 02:25:55',0),
(927635,47,'Invisible Safety Grills Installation','invisible-safety-grills-installation','INVISIBLE GRILLS',1000,1,388,'2026-09-19 02:25:55',0),
(927636,47,'Transparent Invisible Grills Installation','transparent-invisible-grills-installation','INVISIBLE GRILLS',1000,1,389,'2026-09-19 02:25:55',0),
(927637,47,'High Rise Invisible Grills Installation','high-rise-invisible-grills-installation','INVISIBLE GRILLS',1000,1,390,'2026-09-19 02:25:55',0),
(927638,47,'Apartment Invisible Grills Installation','apartment-invisible-grills-installation','INVISIBLE GRILLS',1000,1,391,'2026-09-19 02:25:55',0),
(927639,47,'Villa Invisible Grills Installation','villa-invisible-grills-installation','INVISIBLE GRILLS',1000,1,392,'2026-09-19 02:25:55',0),
(927640,47,'Invisible Balcony Safety Grills Installation','invisible-balcony-safety-grills-installation','INVISIBLE GRILLS',1000,1,393,'2026-09-19 02:25:55',0),
(927641,47,'Invisible Safety Grill For Windows Installation','invisible-safety-grill-for-windows-installation','INVISIBLE GRILLS',1000,1,394,'2026-09-19 02:25:55',0),
(927642,47,'Invisible Grills Price','invisible-grills-price','INVISIBLE GRILLS',1000,1,395,'2026-09-19 02:25:55',0),
(927643,47,'Balcony Invisible Grills Price','balcony-invisible-grills-price','INVISIBLE GRILLS',1000,1,396,'2026-09-19 02:25:55',0),
(927644,47,'Window Invisible Grills Price','window-invisible-grills-price','INVISIBLE GRILLS',1000,1,397,'2026-09-19 02:25:55',0),
(927645,47,'Invisible Grills For Balcony Price','invisible-grills-for-balcony-price','INVISIBLE GRILLS',1000,1,398,'2026-09-19 02:25:55',0),
(927646,47,'Invisible Grills For Windows Price','invisible-grills-for-windows-price','INVISIBLE GRILLS',1000,1,399,'2026-09-19 02:25:55',0),
(927647,47,'SS 316 Invisible Grills Price','ss-316-invisible-grills-price','INVISIBLE GRILLS',1000,1,400,'2026-09-19 02:25:55',0),
(927648,47,'Stainless Steel Invisible Grills Price','stainless-steel-invisible-grills-price','INVISIBLE GRILLS',1000,1,401,'2026-09-19 02:25:55',0),
(927649,47,'Modern Invisible Grills Price','modern-invisible-grills-price','INVISIBLE GRILLS',1000,1,402,'2026-09-19 02:25:55',0),
(927650,47,'Invisible Safety Grills Price','invisible-safety-grills-price','INVISIBLE GRILLS',1000,1,403,'2026-09-19 02:25:55',0),
(927651,47,'Transparent Invisible Grills Price','transparent-invisible-grills-price','INVISIBLE GRILLS',1000,1,404,'2026-09-19 02:25:55',0),
(927652,47,'High Rise Invisible Grills Price','high-rise-invisible-grills-price','INVISIBLE GRILLS',1000,1,405,'2026-09-19 02:25:55',0),
(927653,47,'Apartment Invisible Grills Price','apartment-invisible-grills-price','INVISIBLE GRILLS',1000,1,406,'2026-09-19 02:25:55',0),
(927654,47,'Villa Invisible Grills Price','villa-invisible-grills-price','INVISIBLE GRILLS',1000,1,407,'2026-09-19 02:25:55',0),
(927655,47,'Invisible Balcony Safety Grills Price','invisible-balcony-safety-grills-price','INVISIBLE GRILLS',1000,1,408,'2026-09-19 02:25:55',0),
(927656,47,'Invisible Safety Grill For Windows Price','invisible-safety-grill-for-windows-price','INVISIBLE GRILLS',1000,1,409,'2026-09-19 02:25:55',0),
(927657,47,'Invisible Grills Dealers','invisible-grills-dealers','INVISIBLE GRILLS',1000,1,410,'2026-09-19 02:25:55',0),
(927658,47,'Balcony Invisible Grills Dealers','balcony-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,411,'2026-09-19 02:25:55',0),
(927659,47,'Window Invisible Grills Dealers','window-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,412,'2026-09-19 02:25:55',0),
(927660,47,'Invisible Grills For Balcony Dealers','invisible-grills-for-balcony-dealers','INVISIBLE GRILLS',1000,1,413,'2026-09-19 02:25:55',0),
(927661,47,'Invisible Grills For Windows Dealers','invisible-grills-for-windows-dealers','INVISIBLE GRILLS',1000,1,414,'2026-09-19 02:25:55',0),
(927662,47,'SS 316 Invisible Grills Dealers','ss-316-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,415,'2026-09-19 02:25:55',0),
(927663,47,'Stainless Steel Invisible Grills Dealers','stainless-steel-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,416,'2026-09-19 02:25:55',0),
(927664,47,'Modern Invisible Grills Dealers','modern-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,417,'2026-09-19 02:25:55',0),
(927665,47,'Invisible Safety Grills Dealers','invisible-safety-grills-dealers','INVISIBLE GRILLS',1000,1,418,'2026-09-19 02:25:55',0),
(927666,47,'Transparent Invisible Grills Dealers','transparent-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,419,'2026-09-19 02:25:55',0),
(927667,47,'High Rise Invisible Grills Dealers','high-rise-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,420,'2026-09-19 02:25:55',0),
(927668,47,'Apartment Invisible Grills Dealers','apartment-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,421,'2026-09-19 02:25:55',0),
(927669,47,'Villa Invisible Grills Dealers','villa-invisible-grills-dealers','INVISIBLE GRILLS',1000,1,422,'2026-09-19 02:25:55',0),
(927670,47,'Invisible Balcony Safety Grills Dealers','invisible-balcony-safety-grills-dealers','INVISIBLE GRILLS',1000,1,423,'2026-09-19 02:25:55',0),
(927671,47,'Invisible Safety Grill For Windows Dealers','invisible-safety-grill-for-windows-dealers','INVISIBLE GRILLS',1000,1,424,'2026-09-19 02:25:55',0),
(927672,47,'Invisible Grills Fixing','invisible-grills-fixing','INVISIBLE GRILLS',1000,1,425,'2026-09-19 02:25:55',0),
(927673,47,'Balcony Invisible Grills Fixing','balcony-invisible-grills-fixing','INVISIBLE GRILLS',1000,1,426,'2026-09-19 02:25:55',0),
(927674,47,'SS 316 Invisible Grills Fixing','ss-316-invisible-grills-fixing','INVISIBLE GRILLS',1000,1,427,'2026-09-19 02:25:55',0),
(927675,47,'Invisible Grills Cost','invisible-grills-cost','INVISIBLE GRILLS',1000,1,428,'2026-09-19 02:25:55',0),
(927676,47,'Balcony Invisible Grills Cost','balcony-invisible-grills-cost','INVISIBLE GRILLS',1000,1,429,'2026-09-19 02:25:55',0),
(927677,47,'SS 316 Invisible Grills Cost','ss-316-invisible-grills-cost','INVISIBLE GRILLS',1000,1,430,'2026-09-19 02:25:55',0),
(927679,515,'Cricket Nets Services','cricket-nets-services','SPORTS NETS',1000,1,432,'2026-09-19 02:25:55',0),
(927681,515,'Cricket Practice Nets Services','cricket-practice-nets-services','SPORTS NETS',1000,1,434,'2026-09-19 02:25:55',0),
(927683,513,'Box Cricket Nets','box-cricket-nets','SPORTS NETS',1000,1,436,'2026-09-19 02:25:55',0),
(927684,513,'Box Cricket Nets Services','box-cricket-nets-services','SPORTS NETS',1000,1,437,'2026-09-19 02:25:55',0),
(927686,513,'Box Cricket Netting','box-cricket-netting','SPORTS NETS',1000,1,439,'2026-09-19 02:25:55',0),
(927687,513,'Box Cricket Arena Nets','box-cricket-arena-nets','SPORTS NETS',1000,1,440,'2026-09-19 02:25:55',0),
(927688,513,'Box Cricket Turf Nets','box-cricket-turf-nets','SPORTS NETS',1000,1,441,'2026-09-19 02:25:55',0),
(927689,515,'Terrace Cricket Nets','terrace-cricket-nets','SPORTS NETS',1000,1,442,'2026-09-19 02:25:55',0),
(927690,515,'Rooftop Cricket Nets','rooftop-cricket-nets','SPORTS NETS',1000,1,443,'2026-09-19 02:25:55',0),
(927691,515,'Outdoor Cricket Nets','outdoor-cricket-nets','SPORTS NETS',1000,1,444,'2026-09-19 02:25:55',0),
(927692,515,'Indoor Cricket Nets','indoor-cricket-nets','SPORTS NETS',1000,1,445,'2026-09-19 02:25:55',0),
(927693,515,'Football Boundary Nets','football-boundary-nets','SPORTS NETS',1000,1,446,'2026-09-19 02:25:55',0),
(927694,515,'Badminton Court Nets','badminton-court-nets','SPORTS NETS',1000,1,447,'2026-09-19 02:25:55',0),
(927695,515,'Volleyball Court Nets','volleyball-court-nets','SPORTS NETS',1000,1,448,'2026-09-19 02:25:55',0),
(927696,515,'Sports Safety Nets','sports-safety-nets','SPORTS NETS',1000,1,449,'2026-09-19 02:25:55',0),
(927697,515,'Golf Practice Nets','golf-practice-nets','SPORTS NETS',1000,1,450,'2026-09-19 02:25:55',0),
(927699,515,'Best Cricket Nets','best-cricket-nets','SPORTS NETS',1000,1,452,'2026-09-19 02:25:55',0),
(927700,515,'Top Cricket Nets','top-cricket-nets','SPORTS NETS',1000,1,453,'2026-09-19 02:25:55',0),
(927701,515,'Garware Cricket Nets','garware-cricket-nets','SPORTS NETS',1000,1,454,'2026-09-19 02:25:55',0),
(927702,515,'Same Day Cricket Nets','same-day-cricket-nets','SPORTS NETS',1000,1,455,'2026-09-19 02:25:55',0),
(927703,515,'Affordable Cricket Nets','affordable-cricket-nets','SPORTS NETS',1000,1,456,'2026-09-19 02:25:55',0),
(927704,515,'High Quality Cricket Nets','high-quality-cricket-nets','SPORTS NETS',1000,1,457,'2026-09-19 02:25:55',0),
(927706,515,'Cricket Practice Nets Near Me','cricket-practice-nets-near-me','SPORTS NETS',1000,1,459,'2026-09-19 02:25:55',0),
(927707,515,'Cricket Practice Net Near Me','cricket-practice-net-near-me','SPORTS NETS',1000,1,460,'2026-09-19 02:25:55',0),
(927708,513,'Box Cricket Nets Near Me','box-cricket-nets-near-me','SPORTS NETS',1000,1,461,'2026-09-19 02:25:55',0),
(927709,513,'Box Cricket Net Near Me','box-cricket-net-near-me','SPORTS NETS',1000,1,462,'2026-09-19 02:25:55',0),
(927710,513,'Box Cricket Netting Near Me','box-cricket-netting-near-me','SPORTS NETS',1000,1,463,'2026-09-19 02:25:55',0),
(927711,513,'Box Cricket Arena Nets Near Me','box-cricket-arena-nets-near-me','SPORTS NETS',1000,1,464,'2026-09-19 02:25:55',0),
(927712,513,'Box Cricket Turf Nets Near Me','box-cricket-turf-nets-near-me','SPORTS NETS',1000,1,465,'2026-09-19 02:25:55',0),
(927713,515,'Terrace Cricket Nets Near Me','terrace-cricket-nets-near-me','SPORTS NETS',1000,1,466,'2026-09-19 02:25:55',0),
(927714,515,'Rooftop Cricket Nets Near Me','rooftop-cricket-nets-near-me','SPORTS NETS',1000,1,467,'2026-09-19 02:25:55',0),
(927715,515,'Outdoor Cricket Nets Near Me','outdoor-cricket-nets-near-me','SPORTS NETS',1000,1,468,'2026-09-19 02:25:55',0),
(927717,515,'Football Boundary Nets Near Me','football-boundary-nets-near-me','SPORTS NETS',1000,1,470,'2026-09-19 02:25:55',0),
(927718,515,'Badminton Court Nets Near Me','badminton-court-nets-near-me','SPORTS NETS',1000,1,471,'2026-09-19 02:25:55',0),
(927719,515,'Volleyball Court Nets Near Me','volleyball-court-nets-near-me','SPORTS NETS',1000,1,472,'2026-09-19 02:25:55',0),
(927720,515,'Sports Safety Nets Near Me','sports-safety-nets-near-me','SPORTS NETS',1000,1,473,'2026-09-19 02:25:55',0),
(927721,515,'Golf Practice Nets Near Me','golf-practice-nets-near-me','SPORTS NETS',1000,1,474,'2026-09-19 02:25:55',0),
(927722,515,'Sports Netting Near Me','sports-netting-near-me','SPORTS NETS',1000,1,475,'2026-09-19 02:25:55',0),
(927723,515,'Cricket Nets Installation','cricket-nets-installation','SPORTS NETS',1000,1,476,'2026-09-19 02:25:55',0),
(927724,515,'Cricket Practice Nets Installation','cricket-practice-nets-installation','SPORTS NETS',1000,1,477,'2026-09-19 02:25:55',0),
(927725,515,'Cricket Practice Net Installation','cricket-practice-net-installation','SPORTS NETS',1000,1,478,'2026-09-19 02:25:55',0),
(927726,513,'Box Cricket Nets Installation','box-cricket-nets-installation','SPORTS NETS',1000,1,479,'2026-09-19 02:25:55',0),
(927727,513,'Box Cricket Net Installation','box-cricket-net-installation','SPORTS NETS',1000,1,480,'2026-09-19 02:25:55',0),
(927728,513,'Box Cricket Netting Installation','box-cricket-netting-installation','SPORTS NETS',1000,1,481,'2026-09-19 02:25:55',0),
(927729,513,'Box Cricket Arena Nets Installation','box-cricket-arena-nets-installation','SPORTS NETS',1000,1,482,'2026-09-19 02:25:55',0),
(927730,513,'Box Cricket Turf Nets Installation','box-cricket-turf-nets-installation','SPORTS NETS',1000,1,483,'2026-09-19 02:25:55',0),
(927731,515,'Terrace Cricket Nets Installation','terrace-cricket-nets-installation','SPORTS NETS',1000,1,484,'2026-09-19 02:25:55',0),
(927732,515,'Rooftop Cricket Nets Installation','rooftop-cricket-nets-installation','SPORTS NETS',1000,1,485,'2026-09-19 02:25:55',0),
(927733,515,'Outdoor Cricket Nets Installation','outdoor-cricket-nets-installation','SPORTS NETS',1000,1,486,'2026-09-19 02:25:55',0),
(927734,515,'Indoor Cricket Nets Installation','indoor-cricket-nets-installation','SPORTS NETS',1000,1,487,'2026-09-19 02:25:55',0),
(927735,515,'Football Boundary Nets Installation','football-boundary-nets-installation','SPORTS NETS',1000,1,488,'2026-09-19 02:25:55',0),
(927736,515,'Badminton Court Nets Installation','badminton-court-nets-installation','SPORTS NETS',1000,1,489,'2026-09-19 02:25:55',0),
(927737,515,'Volleyball Court Nets Installation','volleyball-court-nets-installation','SPORTS NETS',1000,1,490,'2026-09-19 02:25:55',0),
(927738,515,'Sports Safety Nets Installation','sports-safety-nets-installation','SPORTS NETS',1000,1,491,'2026-09-19 02:25:55',0),
(927739,515,'Golf Practice Nets Installation','golf-practice-nets-installation','SPORTS NETS',1000,1,492,'2026-09-19 02:25:55',0),
(927740,515,'Sports Netting Installation','sports-netting-installation','SPORTS NETS',1000,1,493,'2026-09-19 02:25:55',0),
(927742,515,'Cricket Practice Nets Price','cricket-practice-nets-price','SPORTS NETS',1000,1,495,'2026-09-19 02:25:55',0),
(927743,515,'Cricket Practice Net Price','cricket-practice-net-price','SPORTS NETS',1000,1,496,'2026-09-19 02:25:55',0),
(927744,513,'Box Cricket Nets Price','box-cricket-nets-price','SPORTS NETS',1000,1,497,'2026-09-19 02:25:55',0),
(927745,513,'Box Cricket Net Price','box-cricket-net-price','SPORTS NETS',1000,1,498,'2026-09-19 02:25:55',0),
(927746,513,'Box Cricket Netting Price','box-cricket-netting-price','SPORTS NETS',1000,1,499,'2026-09-19 02:25:55',0),
(927747,513,'Box Cricket Arena Nets Price','box-cricket-arena-nets-price','SPORTS NETS',1000,1,500,'2026-09-19 02:25:55',0),
(927748,513,'Box Cricket Turf Nets Price','box-cricket-turf-nets-price','SPORTS NETS',1000,1,501,'2026-09-19 02:25:55',0),
(927749,515,'Terrace Cricket Nets Price','terrace-cricket-nets-price','SPORTS NETS',1000,1,502,'2026-09-19 02:25:55',0),
(927750,515,'Rooftop Cricket Nets Price','rooftop-cricket-nets-price','SPORTS NETS',1000,1,503,'2026-09-19 02:25:55',0),
(927751,515,'Outdoor Cricket Nets Price','outdoor-cricket-nets-price','SPORTS NETS',1000,1,504,'2026-09-19 02:25:55',0),
(927752,515,'Indoor Cricket Nets Price','indoor-cricket-nets-price','SPORTS NETS',1000,1,505,'2026-09-19 02:25:55',0),
(927753,515,'Football Boundary Nets Price','football-boundary-nets-price','SPORTS NETS',1000,1,506,'2026-09-19 02:25:55',0),
(927754,515,'Badminton Court Nets Price','badminton-court-nets-price','SPORTS NETS',1000,1,507,'2026-09-19 02:25:55',0),
(927755,515,'Volleyball Court Nets Price','volleyball-court-nets-price','SPORTS NETS',1000,1,508,'2026-09-19 02:25:55',0),
(927756,515,'Sports Safety Nets Price','sports-safety-nets-price','SPORTS NETS',1000,1,509,'2026-09-19 02:25:55',0),
(927757,515,'Golf Practice Nets Price','golf-practice-nets-price','SPORTS NETS',1000,1,510,'2026-09-19 02:25:55',0),
(927758,515,'Sports Netting Price','sports-netting-price','SPORTS NETS',1000,1,511,'2026-09-19 02:25:55',0),
(927759,515,'Cricket Nets Dealers','cricket-nets-dealers','SPORTS NETS',1000,1,512,'2026-09-19 02:25:55',0),
(927760,515,'Cricket Practice Nets Dealers','cricket-practice-nets-dealers','SPORTS NETS',1000,1,513,'2026-09-19 02:25:55',0),
(927761,515,'Cricket Practice Net Dealers','cricket-practice-net-dealers','SPORTS NETS',1000,1,514,'2026-09-19 02:25:55',0),
(927762,513,'Box Cricket Nets Dealers','box-cricket-nets-dealers','SPORTS NETS',1000,1,515,'2026-09-19 02:25:55',0),
(927763,513,'Box Cricket Net Dealers','box-cricket-net-dealers','SPORTS NETS',1000,1,516,'2026-09-19 02:25:55',0),
(927764,513,'Box Cricket Netting Dealers','box-cricket-netting-dealers','SPORTS NETS',1000,1,517,'2026-09-19 02:25:55',0),
(927765,513,'Box Cricket Arena Nets Dealers','box-cricket-arena-nets-dealers','SPORTS NETS',1000,1,518,'2026-09-19 02:25:55',0),
(927766,513,'Box Cricket Turf Nets Dealers','box-cricket-turf-nets-dealers','SPORTS NETS',1000,1,519,'2026-09-19 02:25:55',0),
(927767,515,'Terrace Cricket Nets Dealers','terrace-cricket-nets-dealers','SPORTS NETS',1000,1,520,'2026-09-19 02:25:55',0),
(927769,509,'Cloth Drying Hangers Services','cloth-drying-hangers-services','CLOTH HANGERS',1000,1,522,'2026-09-19 02:25:55',0),
(927771,509,'Ceiling Cloth Hangers Services','ceiling-cloth-hangers-services','CLOTH HANGERS',1000,1,524,'2026-09-19 02:25:55',0),
(927772,509,'Ceiling Cloth Drying Hangers','ceiling-cloth-drying-hangers','CLOTH HANGERS',1000,1,525,'2026-09-19 02:25:55',0),
(927773,509,'Ceiling Clothes Hangers','ceiling-clothes-hangers','CLOTH HANGERS',1000,1,526,'2026-09-19 02:25:55',0),
(927774,509,'Balcony Cloth Hangers','balcony-cloth-hangers','CLOTH HANGERS',1000,1,527,'2026-09-19 02:25:55',0),
(927775,509,'Pulley Cloth Drying Hangers','pulley-cloth-drying-hangers','CLOTH HANGERS',1000,1,528,'2026-09-19 02:25:55',0),
(927776,509,'Pulley Cloth Hangers','pulley-cloth-hangers','CLOTH HANGERS',1000,1,529,'2026-09-19 02:25:55',0),
(927777,509,'Stainless Steel Cloth Hangers','stainless-steel-cloth-hangers','CLOTH HANGERS',1000,1,530,'2026-09-19 02:25:55',0),
(927778,509,'6 Pipe Cloth Drying Hangers','6-pipe-cloth-drying-hangers','CLOTH HANGERS',1000,1,531,'2026-09-19 02:25:55',0),
(927779,509,'8 Pipe Cloth Drying Hangers','8-pipe-cloth-drying-hangers','CLOTH HANGERS',1000,1,532,'2026-09-19 02:25:55',0),
(927780,509,'Ceiling Hangers For Balcony','ceiling-hangers-for-balcony','CLOTH HANGERS',1000,1,533,'2026-09-19 02:25:55',0),
(927781,509,'Clothes Drying Rope Pulley','clothes-drying-rope-pulley','CLOTH HANGERS',1000,1,534,'2026-09-19 02:25:55',0),
(927785,509,'Best Cloth Drying Hangers','best-cloth-drying-hangers','CLOTH HANGERS',1000,1,538,'2026-09-19 02:25:55',0),
(927786,509,'Top Cloth Drying Hangers','top-cloth-drying-hangers','CLOTH HANGERS',1000,1,539,'2026-09-19 02:25:55',0),
(927787,509,'Garware Cloth Drying Hangers','garware-cloth-drying-hangers','CLOTH HANGERS',1000,1,540,'2026-09-19 02:25:55',0),
(927788,509,'Same Day Cloth Drying Hangers','same-day-cloth-drying-hangers','CLOTH HANGERS',1000,1,541,'2026-09-19 02:25:55',0),
(927789,509,'Affordable Cloth Drying Hangers','affordable-cloth-drying-hangers','CLOTH HANGERS',1000,1,542,'2026-09-19 02:25:55',0),
(927790,509,'High Quality Cloth Drying Hangers','high-quality-cloth-drying-hangers','CLOTH HANGERS',1000,1,543,'2026-09-19 02:25:55',0),
(927791,509,'Cloth Drying Hangers Near Me','cloth-drying-hangers-near-me','CLOTH HANGERS',1000,1,544,'2026-09-19 02:25:55',0),
(927792,509,'Ceiling Cloth Hangers Near Me','ceiling-cloth-hangers-near-me','CLOTH HANGERS',1000,1,545,'2026-09-19 02:25:55',0),
(927793,509,'Ceiling Cloth Drying Hangers Near Me','ceiling-cloth-drying-hangers-near-me','CLOTH HANGERS',1000,1,546,'2026-09-19 02:25:55',0),
(927794,509,'Ceiling Clothes Hangers Near Me','ceiling-clothes-hangers-near-me','CLOTH HANGERS',1000,1,547,'2026-09-19 02:25:55',0),
(927795,509,'Balcony Cloth Hangers Near Me','balcony-cloth-hangers-near-me','CLOTH HANGERS',1000,1,548,'2026-09-19 02:25:55',0),
(927796,509,'Pulley Cloth Drying Hangers Near Me','pulley-cloth-drying-hangers-near-me','CLOTH HANGERS',1000,1,549,'2026-09-19 02:25:55',0),
(927797,509,'Pulley Cloth Hangers Near Me','pulley-cloth-hangers-near-me','CLOTH HANGERS',1000,1,550,'2026-09-19 02:25:55',0),
(927798,509,'Stainless Steel Cloth Hangers Near Me','stainless-steel-cloth-hangers-near-me','CLOTH HANGERS',1000,1,551,'2026-09-19 02:25:55',0),
(927799,509,'6 Pipe Cloth Drying Hangers Near Me','6-pipe-cloth-drying-hangers-near-me','CLOTH HANGERS',1000,1,552,'2026-09-19 02:25:55',0),
(927800,509,'8 Pipe Cloth Drying Hangers Near Me','8-pipe-cloth-drying-hangers-near-me','CLOTH HANGERS',1000,1,553,'2026-09-19 02:25:55',0),
(927801,509,'Ceiling Hangers For Balcony Near Me','ceiling-hangers-for-balcony-near-me','CLOTH HANGERS',1000,1,554,'2026-09-19 02:25:55',0),
(927802,509,'Clothes Drying Rope Pulley Near Me','clothes-drying-rope-pulley-near-me','CLOTH HANGERS',1000,1,555,'2026-09-19 02:25:55',0),
(927803,509,'Dry Cloth Hangers Near Me','dry-cloth-hangers-near-me','CLOTH HANGERS',1000,1,556,'2026-09-19 02:25:55',0),
(927804,509,'Laundry Hanger Dryer Near Me','laundry-hanger-dryer-near-me','CLOTH HANGERS',1000,1,557,'2026-09-19 02:25:55',0),
(927805,509,'Clothes Hanger Drier Near Me','clothes-hanger-drier-near-me','CLOTH HANGERS',1000,1,558,'2026-09-19 02:25:55',0),
(927806,509,'Cloth Drying Hangers Installation','cloth-drying-hangers-installation','CLOTH HANGERS',1000,1,559,'2026-09-19 02:25:55',0),
(927807,509,'Ceiling Cloth Hangers Installation','ceiling-cloth-hangers-installation','CLOTH HANGERS',1000,1,560,'2026-09-19 02:25:55',0),
(927808,509,'Ceiling Cloth Drying Hangers Installation','ceiling-cloth-drying-hangers-installation','CLOTH HANGERS',1000,1,561,'2026-09-19 02:25:55',0),
(927809,509,'Ceiling Clothes Hangers Installation','ceiling-clothes-hangers-installation','CLOTH HANGERS',1000,1,562,'2026-09-19 02:25:55',0),
(927810,509,'Balcony Cloth Hangers Installation','balcony-cloth-hangers-installation','CLOTH HANGERS',1000,1,563,'2026-09-19 02:25:55',0),
(927811,509,'Pulley Cloth Drying Hangers Installation','pulley-cloth-drying-hangers-installation','CLOTH HANGERS',1000,1,564,'2026-09-19 02:25:55',0),
(927812,509,'Pulley Cloth Hangers Installation','pulley-cloth-hangers-installation','CLOTH HANGERS',1000,1,565,'2026-09-19 02:25:55',0),
(927813,509,'Stainless Steel Cloth Hangers Installation','stainless-steel-cloth-hangers-installation','CLOTH HANGERS',1000,1,566,'2026-09-19 02:25:55',0),
(927814,509,'6 Pipe Cloth Drying Hangers Installation','6-pipe-cloth-drying-hangers-installation','CLOTH HANGERS',1000,1,567,'2026-09-19 02:25:55',0),
(927815,509,'8 Pipe Cloth Drying Hangers Installation','8-pipe-cloth-drying-hangers-installation','CLOTH HANGERS',1000,1,568,'2026-09-19 02:25:55',0),
(927816,509,'Ceiling Hangers For Balcony Installation','ceiling-hangers-for-balcony-installation','CLOTH HANGERS',1000,1,569,'2026-09-19 02:25:55',0),
(927817,509,'Clothes Drying Rope Pulley Installation','clothes-drying-rope-pulley-installation','CLOTH HANGERS',1000,1,570,'2026-09-19 02:25:55',0),
(927818,509,'Dry Cloth Hangers Installation','dry-cloth-hangers-installation','CLOTH HANGERS',1000,1,571,'2026-09-19 02:25:55',0),
(927819,509,'Laundry Hanger Dryer Installation','laundry-hanger-dryer-installation','CLOTH HANGERS',1000,1,572,'2026-09-19 02:25:55',0),
(927820,509,'Clothes Hanger Drier Installation','clothes-hanger-drier-installation','CLOTH HANGERS',1000,1,573,'2026-09-19 02:25:55',0),
(927821,509,'Cloth Drying Hangers Price','cloth-drying-hangers-price','CLOTH HANGERS',1000,1,574,'2026-09-19 02:25:55',0),
(927822,509,'Ceiling Cloth Hangers Price','ceiling-cloth-hangers-price','CLOTH HANGERS',1000,1,575,'2026-09-19 02:25:55',0),
(927823,509,'Ceiling Cloth Drying Hangers Price','ceiling-cloth-drying-hangers-price','CLOTH HANGERS',1000,1,576,'2026-09-19 02:25:55',0),
(927824,509,'Ceiling Clothes Hangers Price','ceiling-clothes-hangers-price','CLOTH HANGERS',1000,1,577,'2026-09-19 02:25:55',0),
(927825,509,'Balcony Cloth Hangers Price','balcony-cloth-hangers-price','CLOTH HANGERS',1000,1,578,'2026-09-19 02:25:55',0),
(927826,509,'Pulley Cloth Drying Hangers Price','pulley-cloth-drying-hangers-price','CLOTH HANGERS',1000,1,579,'2026-09-19 02:25:55',0),
(927827,509,'Pulley Cloth Hangers Price','pulley-cloth-hangers-price','CLOTH HANGERS',1000,1,580,'2026-09-19 02:25:55',0),
(927828,509,'Stainless Steel Cloth Hangers Price','stainless-steel-cloth-hangers-price','CLOTH HANGERS',1000,1,581,'2026-09-19 02:25:55',0),
(927829,509,'6 Pipe Cloth Drying Hangers Price','6-pipe-cloth-drying-hangers-price','CLOTH HANGERS',1000,1,582,'2026-09-19 02:25:55',0),
(927830,509,'8 Pipe Cloth Drying Hangers Price','8-pipe-cloth-drying-hangers-price','CLOTH HANGERS',1000,1,583,'2026-09-19 02:25:55',0),
(927831,509,'Ceiling Hangers For Balcony Price','ceiling-hangers-for-balcony-price','CLOTH HANGERS',1000,1,584,'2026-09-19 02:25:55',0),
(927832,509,'Clothes Drying Rope Pulley Price','clothes-drying-rope-pulley-price','CLOTH HANGERS',1000,1,585,'2026-09-19 02:25:55',0),
(927833,509,'Dry Cloth Hangers Price','dry-cloth-hangers-price','CLOTH HANGERS',1000,1,586,'2026-09-19 02:25:55',0),
(927834,509,'Laundry Hanger Dryer Price','laundry-hanger-dryer-price','CLOTH HANGERS',1000,1,587,'2026-09-19 02:25:55',0),
(927835,509,'Clothes Hanger Drier Price','clothes-hanger-drier-price','CLOTH HANGERS',1000,1,588,'2026-09-19 02:25:55',0),
(927836,509,'Cloth Drying Hangers Dealers','cloth-drying-hangers-dealers','CLOTH HANGERS',1000,1,589,'2026-09-19 02:25:55',0),
(927837,509,'Ceiling Cloth Hangers Dealers','ceiling-cloth-hangers-dealers','CLOTH HANGERS',1000,1,590,'2026-09-19 02:25:55',0),
(927838,509,'Ceiling Cloth Drying Hangers Dealers','ceiling-cloth-drying-hangers-dealers','CLOTH HANGERS',1000,1,591,'2026-09-19 02:25:55',0),
(927839,509,'Ceiling Clothes Hangers Dealers','ceiling-clothes-hangers-dealers','CLOTH HANGERS',1000,1,592,'2026-09-19 02:25:55',0),
(927840,509,'Balcony Cloth Hangers Dealers','balcony-cloth-hangers-dealers','CLOTH HANGERS',1000,1,593,'2026-09-19 02:25:55',0),
(927841,509,'Pulley Cloth Drying Hangers Dealers','pulley-cloth-drying-hangers-dealers','CLOTH HANGERS',1000,1,594,'2026-09-19 02:25:55',0),
(927842,509,'Pulley Cloth Hangers Dealers','pulley-cloth-hangers-dealers','CLOTH HANGERS',1000,1,595,'2026-09-19 02:25:55',0),
(927843,509,'Stainless Steel Cloth Hangers Dealers','stainless-steel-cloth-hangers-dealers','CLOTH HANGERS',1000,1,596,'2026-09-19 02:25:55',0),
(927844,509,'6 Pipe Cloth Drying Hangers Dealers','6-pipe-cloth-drying-hangers-dealers','CLOTH HANGERS',1000,1,597,'2026-09-19 02:25:55',0),
(927845,509,'8 Pipe Cloth Drying Hangers Dealers','8-pipe-cloth-drying-hangers-dealers','CLOTH HANGERS',1000,1,598,'2026-09-19 02:25:55',0),
(927846,509,'Ceiling Hangers For Balcony Dealers','ceiling-hangers-for-balcony-dealers','CLOTH HANGERS',1000,1,599,'2026-09-19 02:25:55',0),
(927847,509,'Clothes Drying Rope Pulley Dealers','clothes-drying-rope-pulley-dealers','CLOTH HANGERS',1000,1,600,'2026-09-19 02:25:55',0),
(927855,66,'Balcony Safety Nets Installation','balcony-safety-nets-installation','SAFETY NETS',1000,1,2,'2026-09-19 02:27:21',0),
(927856,66,'Balcony Safety Nets Dealers','balcony-safety-nets-dealers','SAFETY NETS',1000,1,3,'2026-09-19 02:27:21',0),
(927857,66,'Balcony Safety Nets Contractors','balcony-safety-nets-contractors','SAFETY NETS',1000,1,4,'2026-09-19 02:27:21',0),
(927858,66,'Balcony Safety Nets Price','balcony-safety-nets-price','SAFETY NETS',1000,1,5,'2026-09-19 02:27:21',0),
(927859,66,'Balcony Safety Nets Cost','balcony-safety-nets-cost','SAFETY NETS',1000,1,6,'2026-09-19 02:27:21',0),
(927861,66,'Balcony Safety Nets Fixing','balcony-safety-nets-fixing','SAFETY NETS',1000,1,8,'2026-09-19 02:27:21',0),
(927862,66,'Balcony Safety Nets Fitting','balcony-safety-nets-fitting','SAFETY NETS',1000,1,9,'2026-09-19 02:27:21',0),
(927865,66,'Safety Nets For Balcony Installation','safety-nets-for-balcony-installation','SAFETY NETS',1000,1,12,'2026-09-19 02:27:21',0),
(927866,66,'Safety Nets For Balcony Dealers','safety-nets-for-balcony-dealers','SAFETY NETS',1000,1,13,'2026-09-19 02:27:21',0),
(927867,66,'Safety Nets For Balcony Contractors','safety-nets-for-balcony-contractors','SAFETY NETS',1000,1,14,'2026-09-19 02:27:21',0),
(927868,66,'Safety Nets For Balcony Price','safety-nets-for-balcony-price','SAFETY NETS',1000,1,15,'2026-09-19 02:27:21',0),
(927869,66,'Safety Nets For Balcony Cost','safety-nets-for-balcony-cost','SAFETY NETS',1000,1,16,'2026-09-19 02:27:21',0),
(927871,66,'Safety Nets For Balcony Fixing','safety-nets-for-balcony-fixing','SAFETY NETS',1000,1,18,'2026-09-19 02:27:21',0),
(927872,66,'Safety Nets For Balcony Fitting','safety-nets-for-balcony-fitting','SAFETY NETS',1000,1,19,'2026-09-19 02:27:21',0),
(927888,1,'Pigeon Nets Fitting','pigeon-nets-fitting','PIGEON NETS',1000,1,35,'2026-09-19 02:27:21',0),
(927898,1,'Pigeon Safety Nets Fitting','pigeon-safety-nets-fitting','PIGEON NETS',1000,1,45,'2026-09-19 02:27:21',0),
(927908,1,'Pigeon Nets For Balcony Fitting','pigeon-nets-for-balcony-fitting','PIGEON NETS',1000,1,55,'2026-09-19 02:27:21',0),
(927920,10,'Anti Bird Netting Dealers','anti-bird-netting-dealers','BIRD NETS',1000,1,67,'2026-09-19 02:27:21',0),
(927921,10,'Anti Bird Netting Contractors','anti-bird-netting-contractors','BIRD NETS',1000,1,68,'2026-09-19 02:27:21',0),
(927923,10,'Anti Bird Netting Cost','anti-bird-netting-cost','BIRD NETS',1000,1,70,'2026-09-19 02:27:21',0),
(927925,10,'Anti Bird Netting Fixing','anti-bird-netting-fixing','BIRD NETS',1000,1,72,'2026-09-19 02:27:21',0),
(927926,10,'Anti Bird Netting Fitting','anti-bird-netting-fitting','BIRD NETS',1000,1,73,'2026-09-19 02:27:21',0),
(927930,10,'Bird Safety Nets Dealers','bird-safety-nets-dealers','BIRD NETS',1000,1,77,'2026-09-19 02:27:21',0),
(927931,10,'Bird Safety Nets Contractors','bird-safety-nets-contractors','BIRD NETS',1000,1,78,'2026-09-19 02:27:21',0),
(927932,10,'Bird Safety Nets Price','bird-safety-nets-price','BIRD NETS',1000,1,79,'2026-09-19 02:27:21',0),
(927936,512,'Anti Bird Spikes Dealers','anti-bird-spikes-dealers','BIRD NETS',1000,1,83,'2026-09-19 02:27:21',0),
(927937,512,'Anti Bird Spikes Contractors','anti-bird-spikes-contractors','BIRD NETS',1000,1,84,'2026-09-19 02:27:21',0),
(927938,512,'Anti Bird Spikes Price','anti-bird-spikes-price','BIRD NETS',1000,1,85,'2026-09-19 02:27:21',0),
(927939,512,'Anti Bird Spikes Cost','anti-bird-spikes-cost','BIRD NETS',1000,1,86,'2026-09-19 02:27:21',0),
(927941,512,'Anti Bird Spikes Fixing','anti-bird-spikes-fixing','BIRD NETS',1000,1,88,'2026-09-19 02:27:21',0),
(927942,512,'Anti Bird Spikes Fitting','anti-bird-spikes-fitting','BIRD NETS',1000,1,89,'2026-09-19 02:27:21',0),
(927946,512,'Bird Spikes Dealers','bird-spikes-dealers','BIRD NETS',1000,1,93,'2026-09-19 02:27:21',0),
(927947,512,'Bird Spikes Contractors','bird-spikes-contractors','BIRD NETS',1000,1,94,'2026-09-19 02:27:21',0),
(927948,512,'Bird Spikes Price','bird-spikes-price','BIRD NETS',1000,1,95,'2026-09-19 02:27:21',0),
(927949,512,'Bird Spikes Cost','bird-spikes-cost','BIRD NETS',1000,1,96,'2026-09-19 02:27:21',0),
(927951,512,'Bird Spikes Fixing','bird-spikes-fixing','BIRD NETS',1000,1,98,'2026-09-19 02:27:21',0),
(927952,512,'Bird Spikes Fitting','bird-spikes-fitting','BIRD NETS',1000,1,99,'2026-09-19 02:27:21',0),
(927963,47,'Invisible Grills Contractors','invisible-grills-contractors','INVISIBLE GRILLS',1000,1,110,'2026-09-19 02:27:21',0),
(927968,47,'Invisible Grills Fitting','invisible-grills-fitting','INVISIBLE GRILLS',1000,1,115,'2026-09-19 02:27:21',0),
(927973,47,'Balcony Invisible Grills Contractors','balcony-invisible-grills-contractors','INVISIBLE GRILLS',1000,1,120,'2026-09-19 02:27:21',0),
(927978,47,'Balcony Invisible Grills Fitting','balcony-invisible-grills-fitting','INVISIBLE GRILLS',1000,1,125,'2026-09-19 02:27:21',0),
(927984,47,'Invisible Grills For Balcony Contractors','invisible-grills-for-balcony-contractors','INVISIBLE GRILLS',1000,1,131,'2026-09-19 02:27:21',0),
(927990,47,'Invisible Grills For Windows Contractors','invisible-grills-for-windows-contractors','INVISIBLE GRILLS',1000,1,137,'2026-09-19 02:27:21',0),
(928001,32,'Children Safety Nets Installation','children-safety-nets-installation','SAFETY NETS',1000,1,148,'2026-09-19 02:27:21',0),
(928002,32,'Children Safety Nets Dealers','children-safety-nets-dealers','SAFETY NETS',1000,1,149,'2026-09-19 02:27:21',0),
(928003,32,'Children Safety Nets Contractors','children-safety-nets-contractors','SAFETY NETS',1000,1,150,'2026-09-19 02:27:21',0),
(928004,32,'Children Safety Nets Price','children-safety-nets-price','SAFETY NETS',1000,1,151,'2026-09-19 02:27:21',0),
(928005,32,'Children Safety Nets Cost','children-safety-nets-cost','SAFETY NETS',1000,1,152,'2026-09-19 02:27:21',0),
(928007,32,'Children Safety Nets Fixing','children-safety-nets-fixing','SAFETY NETS',1000,1,154,'2026-09-19 02:27:21',0),
(928008,32,'Children Safety Nets Fitting','children-safety-nets-fitting','SAFETY NETS',1000,1,155,'2026-09-19 02:27:21',0),
(928011,32,'Child Safety Nets Installation','child-safety-nets-installation','SAFETY NETS',1000,1,158,'2026-09-19 02:27:21',0),
(928012,32,'Child Safety Nets Dealers','child-safety-nets-dealers','SAFETY NETS',1000,1,159,'2026-09-19 02:27:21',0),
(928013,32,'Child Safety Nets Contractors','child-safety-nets-contractors','SAFETY NETS',1000,1,160,'2026-09-19 02:27:21',0),
(928014,32,'Child Safety Nets Price','child-safety-nets-price','SAFETY NETS',1000,1,161,'2026-09-19 02:27:21',0),
(928015,32,'Child Safety Nets Cost','child-safety-nets-cost','SAFETY NETS',1000,1,162,'2026-09-19 02:27:21',0),
(928017,32,'Child Safety Nets Fixing','child-safety-nets-fixing','SAFETY NETS',1000,1,164,'2026-09-19 02:27:21',0),
(928018,32,'Child Safety Nets Fitting','child-safety-nets-fitting','SAFETY NETS',1000,1,165,'2026-09-19 02:27:21',0),
(928021,32,'Child Safety Nets For Balcony Installation','child-safety-nets-for-balcony-installation','SAFETY NETS',1000,1,168,'2026-09-19 02:27:21',0),
(928022,32,'Child Safety Nets For Balcony Dealers','child-safety-nets-for-balcony-dealers','SAFETY NETS',1000,1,169,'2026-09-19 02:27:21',0),
(928023,32,'Child Safety Nets For Balcony Contractors','child-safety-nets-for-balcony-contractors','SAFETY NETS',1000,1,170,'2026-09-19 02:27:21',0),
(928024,32,'Child Safety Nets For Balcony Price','child-safety-nets-for-balcony-price','SAFETY NETS',1000,1,171,'2026-09-19 02:27:21',0),
(928030,33,'Pet Safety Nets Installation','pet-safety-nets-installation','SAFETY NETS',1000,1,177,'2026-09-19 02:27:21',0),
(928031,33,'Pet Safety Nets Dealers','pet-safety-nets-dealers','SAFETY NETS',1000,1,178,'2026-09-19 02:27:21',0),
(928032,33,'Pet Safety Nets Contractors','pet-safety-nets-contractors','SAFETY NETS',1000,1,179,'2026-09-19 02:27:21',0),
(928033,33,'Pet Safety Nets Price','pet-safety-nets-price','SAFETY NETS',1000,1,180,'2026-09-19 02:27:21',0),
(928034,33,'Pet Safety Nets Cost','pet-safety-nets-cost','SAFETY NETS',1000,1,181,'2026-09-19 02:27:21',0),
(928036,33,'Pet Safety Nets Fixing','pet-safety-nets-fixing','SAFETY NETS',1000,1,183,'2026-09-19 02:27:21',0),
(928037,33,'Pet Safety Nets Fitting','pet-safety-nets-fitting','SAFETY NETS',1000,1,184,'2026-09-19 02:27:21',0),
(928040,33,'Cat Safety Nets Installation','cat-safety-nets-installation','SAFETY NETS',1000,1,187,'2026-09-19 02:27:21',0),
(928041,33,'Cat Safety Nets Dealers','cat-safety-nets-dealers','SAFETY NETS',1000,1,188,'2026-09-19 02:27:21',0),
(928042,33,'Cat Safety Nets Contractors','cat-safety-nets-contractors','SAFETY NETS',1000,1,189,'2026-09-19 02:27:21',0),
(928043,33,'Cat Safety Nets Price','cat-safety-nets-price','SAFETY NETS',1000,1,190,'2026-09-19 02:27:21',0),
(928044,33,'Cat Safety Nets Cost','cat-safety-nets-cost','SAFETY NETS',1000,1,191,'2026-09-19 02:27:21',0),
(928046,33,'Cat Safety Nets Fixing','cat-safety-nets-fixing','SAFETY NETS',1000,1,193,'2026-09-19 02:27:21',0),
(928047,33,'Cat Safety Nets Fitting','cat-safety-nets-fitting','SAFETY NETS',1000,1,194,'2026-09-19 02:27:21',0),
(928052,515,'Cricket Nets Contractors','cricket-nets-contractors','SPORTS NETS',1000,1,199,'2026-09-19 02:27:21',0),
(928054,515,'Cricket Nets Cost','cricket-nets-cost','SPORTS NETS',1000,1,201,'2026-09-19 02:27:21',0),
(928056,515,'Cricket Nets Fixing','cricket-nets-fixing','SPORTS NETS',1000,1,203,'2026-09-19 02:27:21',0),
(928057,515,'Cricket Nets Fitting','cricket-nets-fitting','SPORTS NETS',1000,1,204,'2026-09-19 02:27:21',0),
(928062,515,'Cricket Practice Nets Contractors','cricket-practice-nets-contractors','SPORTS NETS',1000,1,209,'2026-09-19 02:27:21',0),
(928064,515,'Cricket Practice Nets Cost','cricket-practice-nets-cost','SPORTS NETS',1000,1,211,'2026-09-19 02:27:21',0),
(928066,515,'Cricket Practice Nets Fixing','cricket-practice-nets-fixing','SPORTS NETS',1000,1,213,'2026-09-19 02:27:21',0),
(928067,515,'Cricket Practice Nets Fitting','cricket-practice-nets-fitting','SPORTS NETS',1000,1,214,'2026-09-19 02:27:21',0),
(928072,513,'Box Cricket Nets Contractors','box-cricket-nets-contractors','SPORTS NETS',1000,1,219,'2026-09-19 02:27:21',0),
(928074,513,'Box Cricket Nets Cost','box-cricket-nets-cost','SPORTS NETS',1000,1,221,'2026-09-19 02:27:21',0),
(928076,513,'Box Cricket Nets Fixing','box-cricket-nets-fixing','SPORTS NETS',1000,1,223,'2026-09-19 02:27:21',0),
(928077,513,'Box Cricket Nets Fitting','box-cricket-nets-fitting','SPORTS NETS',1000,1,224,'2026-09-19 02:27:21',0),
(928082,515,'Terrace Cricket Nets Contractors','terrace-cricket-nets-contractors','SPORTS NETS',1000,1,229,'2026-09-19 02:27:21',0),
(928091,509,'Cloth Drying Hangers Contractors','cloth-drying-hangers-contractors','CLOTH HANGERS',1000,1,238,'2026-09-19 02:27:21',0),
(928093,509,'Cloth Drying Hangers Cost','cloth-drying-hangers-cost','CLOTH HANGERS',1000,1,240,'2026-09-19 02:27:21',0),
(928095,509,'Cloth Drying Hangers Fixing','cloth-drying-hangers-fixing','CLOTH HANGERS',1000,1,242,'2026-09-19 02:27:21',0),
(928096,509,'Cloth Drying Hangers Fitting','cloth-drying-hangers-fitting','CLOTH HANGERS',1000,1,243,'2026-09-19 02:27:21',0),
(928101,509,'Ceiling Cloth Hangers Contractors','ceiling-cloth-hangers-contractors','CLOTH HANGERS',1000,1,248,'2026-09-19 02:27:21',0),
(928103,509,'Ceiling Cloth Hangers Cost','ceiling-cloth-hangers-cost','CLOTH HANGERS',1000,1,250,'2026-09-19 02:27:21',0),
(928105,509,'Ceiling Cloth Hangers Fixing','ceiling-cloth-hangers-fixing','CLOTH HANGERS',1000,1,252,'2026-09-19 02:27:21',0),
(928106,509,'Ceiling Cloth Hangers Fitting','ceiling-cloth-hangers-fitting','CLOTH HANGERS',1000,1,253,'2026-09-19 02:27:21',0),
(928111,509,'Ceiling Cloth Drying Hangers Contractors','ceiling-cloth-drying-hangers-contractors','CLOTH HANGERS',1000,1,258,'2026-09-19 02:27:21',0),
(928118,28,'Construction Safety Nets Installation','construction-safety-nets-installation','SAFETY NETS',1000,1,265,'2026-09-19 02:27:21',0),
(928119,28,'Construction Safety Nets Dealers','construction-safety-nets-dealers','SAFETY NETS',1000,1,266,'2026-09-19 02:27:21',0),
(928120,28,'Construction Safety Nets Contractors','construction-safety-nets-contractors','SAFETY NETS',1000,1,267,'2026-09-19 02:27:21',0),
(928121,28,'Construction Safety Nets Price','construction-safety-nets-price','SAFETY NETS',1000,1,268,'2026-09-19 02:27:21',0),
(928122,28,'Construction Safety Nets Cost','construction-safety-nets-cost','SAFETY NETS',1000,1,269,'2026-09-19 02:27:21',0),
(928124,28,'Construction Safety Nets Fixing','construction-safety-nets-fixing','SAFETY NETS',1000,1,271,'2026-09-19 02:27:21',0),
(928125,28,'Construction Safety Nets Fitting','construction-safety-nets-fitting','SAFETY NETS',1000,1,272,'2026-09-19 02:27:21',0),
(928128,28,'Building Safety Nets Installation','building-safety-nets-installation','SAFETY NETS',1000,1,275,'2026-09-19 02:27:21',0),
(928129,28,'Building Safety Nets Dealers','building-safety-nets-dealers','SAFETY NETS',1000,1,276,'2026-09-19 02:27:21',0),
(928130,28,'Building Safety Nets Contractors','building-safety-nets-contractors','SAFETY NETS',1000,1,277,'2026-09-19 02:27:21',0),
(928131,28,'Building Safety Nets Price','building-safety-nets-price','SAFETY NETS',1000,1,278,'2026-09-19 02:27:21',0),
(928134,29,'Industrial Safety Nets Installation','industrial-safety-nets-installation','SAFETY NETS',1000,1,281,'2026-09-19 02:27:21',0),
(928135,29,'Industrial Safety Nets Dealers','industrial-safety-nets-dealers','SAFETY NETS',1000,1,282,'2026-09-19 02:27:21',0),
(928136,29,'Industrial Safety Nets Contractors','industrial-safety-nets-contractors','SAFETY NETS',1000,1,283,'2026-09-19 02:27:21',0),
(928137,29,'Industrial Safety Nets Price','industrial-safety-nets-price','SAFETY NETS',1000,1,284,'2026-09-19 02:27:21',0),
(928140,26,'Duct Area Safety Nets Installation','duct-area-safety-nets-installation','SAFETY NETS',1000,1,287,'2026-09-19 02:27:21',0),
(928141,26,'Duct Area Safety Nets Dealers','duct-area-safety-nets-dealers','SAFETY NETS',1000,1,288,'2026-09-19 02:27:21',0),
(928142,26,'Duct Area Safety Nets Contractors','duct-area-safety-nets-contractors','SAFETY NETS',1000,1,289,'2026-09-19 02:27:21',0),
(928143,26,'Duct Area Safety Nets Price','duct-area-safety-nets-price','SAFETY NETS',1000,1,290,'2026-09-19 02:27:21',0),
(928144,26,'Duct Area Safety Nets Cost','duct-area-safety-nets-cost','SAFETY NETS',1000,1,291,'2026-09-19 02:27:21',0),
(928145,26,'Duct Area Safety Nets Near Me','duct-area-safety-nets-near-me','SAFETY NETS',1000,1,292,'2026-09-19 02:27:21',0),
(928146,26,'Duct Area Safety Nets Fixing','duct-area-safety-nets-fixing','SAFETY NETS',1000,1,293,'2026-09-19 02:27:21',0),
(928147,26,'Duct Area Safety Nets Fitting','duct-area-safety-nets-fitting','SAFETY NETS',1000,1,294,'2026-09-19 02:27:21',0),
(928151,23,'Garware Safety Nets Installation','garware-safety-nets-installation','SAFETY NETS',1000,1,298,'2026-09-19 02:27:21',0),
(928152,23,'Garware Safety Nets Dealers','garware-safety-nets-dealers','SAFETY NETS',1000,1,299,'2026-09-19 02:27:21',0),
(928153,23,'Garware Safety Nets Contractors','garware-safety-nets-contractors','SAFETY NETS',1000,1,300,'2026-09-19 02:27:21',0),
(928154,23,'Garware Safety Nets Price','garware-safety-nets-price','SAFETY NETS',1000,1,301,'2026-09-19 02:27:21',0),
(928155,23,'Garware Safety Nets Cost','garware-safety-nets-cost','SAFETY NETS',1000,1,302,'2026-09-19 02:27:21',0),
(928156,23,'Garware Safety Nets Near Me','garware-safety-nets-near-me','SAFETY NETS',1000,1,303,'2026-09-19 02:27:21',0),
(928157,23,'Garware Safety Nets Fixing','garware-safety-nets-fixing','SAFETY NETS',1000,1,304,'2026-09-19 02:27:21',0),
(928158,23,'Garware Safety Nets Fitting','garware-safety-nets-fitting','SAFETY NETS',1000,1,305,'2026-09-19 02:27:21',0),
(928161,23,'Nylon Safety Nets Installation','nylon-safety-nets-installation','SAFETY NETS',1000,1,308,'2026-09-19 02:27:21',0),
(928162,23,'Nylon Safety Nets Dealers','nylon-safety-nets-dealers','SAFETY NETS',1000,1,309,'2026-09-19 02:27:21',0),
(928163,23,'Nylon Safety Nets Contractors','nylon-safety-nets-contractors','SAFETY NETS',1000,1,310,'2026-09-19 02:27:21',0),
(928164,23,'Nylon Safety Nets Price','nylon-safety-nets-price','SAFETY NETS',1000,1,311,'2026-09-19 02:27:21',0),
(928165,23,'Nylon Safety Nets Cost','nylon-safety-nets-cost','SAFETY NETS',1000,1,312,'2026-09-19 02:27:21',0),
(928166,23,'Nylon Safety Nets Near Me','nylon-safety-nets-near-me','SAFETY NETS',1000,1,313,'2026-09-19 02:27:21',0),
(928167,23,'Nylon Safety Nets Fixing','nylon-safety-nets-fixing','SAFETY NETS',1000,1,314,'2026-09-19 02:27:21',0),
(928168,23,'Nylon Safety Nets Fitting','nylon-safety-nets-fitting','SAFETY NETS',1000,1,315,'2026-09-19 02:27:21',0),
(928217,66,'Balcony Netting Installation','balcony-netting-installation','SAFETY NETS',1000,1,364,'2026-09-19 02:27:21',0),
(928218,66,'Balcony Netting Dealers','balcony-netting-dealers','SAFETY NETS',1000,1,365,'2026-09-19 02:27:21',0),
(928219,66,'Balcony Netting Price','balcony-netting-price','SAFETY NETS',1000,1,366,'2026-09-19 02:27:21',0),
(928222,66,'Balcony Nets Installation','balcony-nets-installation','SAFETY NETS',1000,1,369,'2026-09-19 02:27:21',0),
(928223,66,'Balcony Nets Dealers','balcony-nets-dealers','SAFETY NETS',1000,1,370,'2026-09-19 02:27:21',0),
(928224,66,'Balcony Nets Price','balcony-nets-price','SAFETY NETS',1000,1,371,'2026-09-19 02:27:21',0),
(928227,66,'Balcony Protection Nets Installation','balcony-protection-nets-installation','SAFETY NETS',1000,1,374,'2026-09-19 02:27:21',0),
(928228,66,'Balcony Protection Nets Dealers','balcony-protection-nets-dealers','SAFETY NETS',1000,1,375,'2026-09-19 02:27:21',0),
(928229,66,'Balcony Protection Nets Price','balcony-protection-nets-price','SAFETY NETS',1000,1,376,'2026-09-19 02:27:21',0),
(928232,66,'Safety Nets For Balconies Installation','safety-nets-for-balconies-installation','SAFETY NETS',1000,1,379,'2026-09-19 02:27:21',0),
(928233,66,'Safety Nets For Balconies Dealers','safety-nets-for-balconies-dealers','SAFETY NETS',1000,1,380,'2026-09-19 02:27:21',0),
(928234,66,'Safety Nets For Balconies Price','safety-nets-for-balconies-price','SAFETY NETS',1000,1,381,'2026-09-19 02:27:21',0),
(928237,66,'Balcony Mesh Installation','balcony-mesh-installation','SAFETY NETS',1000,1,384,'2026-09-19 02:27:21',0),
(928238,66,'Balcony Mesh Dealers','balcony-mesh-dealers','SAFETY NETS',1000,1,385,'2026-09-19 02:27:21',0),
(928239,66,'Balcony Mesh Price','balcony-mesh-price','SAFETY NETS',1000,1,386,'2026-09-19 02:27:21',0),
(928241,66,'Nylon Balcony Safety Nets Installation','nylon-balcony-safety-nets-installation','SAFETY NETS',1000,1,388,'2026-09-19 02:27:21',0),
(928242,66,'Nylon Balcony Safety Nets Dealers','nylon-balcony-safety-nets-dealers','SAFETY NETS',1000,1,389,'2026-09-19 02:27:21',0),
(928243,66,'Nylon Balcony Safety Nets Price','nylon-balcony-safety-nets-price','SAFETY NETS',1000,1,390,'2026-09-19 02:27:21',0),
(928245,66,'Garware Balcony Safety Nets Installation','garware-balcony-safety-nets-installation','SAFETY NETS',1000,1,392,'2026-09-19 02:27:21',0),
(928246,66,'Garware Balcony Safety Nets Dealers','garware-balcony-safety-nets-dealers','SAFETY NETS',1000,1,393,'2026-09-19 02:27:21',0),
(928247,66,'Garware Balcony Safety Nets Price','garware-balcony-safety-nets-price','SAFETY NETS',1000,1,394,'2026-09-19 02:27:21',0),
(928249,66,'High Rise Balcony Safety Nets Installation','high-rise-balcony-safety-nets-installation','SAFETY NETS',1000,1,396,'2026-09-19 02:27:21',0),
(928250,66,'High Rise Balcony Safety Nets Dealers','high-rise-balcony-safety-nets-dealers','SAFETY NETS',1000,1,397,'2026-09-19 02:27:21',0),
(928251,66,'High Rise Balcony Safety Nets Price','high-rise-balcony-safety-nets-price','SAFETY NETS',1000,1,398,'2026-09-19 02:27:21',0),
(928253,66,'Apartment Balcony Safety Nets Installation','apartment-balcony-safety-nets-installation','SAFETY NETS',1000,1,400,'2026-09-19 02:27:21',0),
(928254,66,'Apartment Balcony Safety Nets Dealers','apartment-balcony-safety-nets-dealers','SAFETY NETS',1000,1,401,'2026-09-19 02:27:21',0),
(928255,66,'Apartment Balcony Safety Nets Price','apartment-balcony-safety-nets-price','SAFETY NETS',1000,1,402,'2026-09-19 02:27:21',0),
(928257,66,'Villa Balcony Safety Nets Installation','villa-balcony-safety-nets-installation','SAFETY NETS',1000,1,404,'2026-09-19 02:27:21',0),
(928258,66,'Villa Balcony Safety Nets Dealers','villa-balcony-safety-nets-dealers','SAFETY NETS',1000,1,405,'2026-09-19 02:27:21',0),
(928259,66,'Villa Balcony Safety Nets Price','villa-balcony-safety-nets-price','SAFETY NETS',1000,1,406,'2026-09-19 02:27:21',0),
(928262,66,'Balcony Grill Netting Installation','balcony-grill-netting-installation','SAFETY NETS',1000,1,409,'2026-09-19 02:27:21',0),
(928263,66,'Balcony Grill Netting Dealers','balcony-grill-netting-dealers','SAFETY NETS',1000,1,410,'2026-09-19 02:27:21',0),
(928264,66,'Balcony Grill Netting Price','balcony-grill-netting-price','SAFETY NETS',1000,1,411,'2026-09-19 02:27:21',0),
(928266,66,'Transparent Balcony Safety Nets Installation','transparent-balcony-safety-nets-installation','SAFETY NETS',1000,1,413,'2026-09-19 02:27:21',0),
(928267,66,'Transparent Balcony Safety Nets Dealers','transparent-balcony-safety-nets-dealers','SAFETY NETS',1000,1,414,'2026-09-19 02:27:21',0),
(928268,66,'Transparent Balcony Safety Nets Price','transparent-balcony-safety-nets-price','SAFETY NETS',1000,1,415,'2026-09-19 02:27:21',0),
(928271,66,'Balcony Fall Protection Nets Installation','balcony-fall-protection-nets-installation','SAFETY NETS',1000,1,418,'2026-09-19 02:27:21',0),
(928272,66,'Balcony Fall Protection Nets Dealers','balcony-fall-protection-nets-dealers','SAFETY NETS',1000,1,419,'2026-09-19 02:27:21',0),
(928273,66,'Balcony Fall Protection Nets Price','balcony-fall-protection-nets-price','SAFETY NETS',1000,1,420,'2026-09-19 02:27:21',0),
(928275,66,'Balcony Net Fixing Installation','balcony-net-fixing-installation','SAFETY NETS',1000,1,422,'2026-09-19 02:27:21',0),
(928276,66,'Balcony Net Fixing Dealers','balcony-net-fixing-dealers','SAFETY NETS',1000,1,423,'2026-09-19 02:27:21',0),
(928277,66,'Balcony Net Fixing Price','balcony-net-fixing-price','SAFETY NETS',1000,1,424,'2026-09-19 02:27:21',0),
(928358,10,'Bird Nets Dealers','bird-nets-dealers','BIRD NETS',1000,1,505,'2026-09-19 02:27:21',0),
(928359,10,'Bird Nets Price','bird-nets-price','BIRD NETS',1000,1,506,'2026-09-19 02:27:21',0),
(928363,10,'Bird Net Dealers','bird-net-dealers','BIRD NETS',1000,1,510,'2026-09-19 02:27:21',0),
(928364,10,'Bird Net Price','bird-net-price','BIRD NETS',1000,1,511,'2026-09-19 02:27:21',0),
(928367,10,'Bird Netting Services Dealers','bird-netting-services-dealers','BIRD NETS',1000,1,514,'2026-09-19 02:27:21',0),
(928368,10,'Bird Netting Services Price','bird-netting-services-price','BIRD NETS',1000,1,515,'2026-09-19 02:27:21',0),
(928372,10,'Bird Nets For Balcony Dealers','bird-nets-for-balcony-dealers','BIRD NETS',1000,1,519,'2026-09-19 02:27:21',0),
(928373,10,'Bird Nets For Balcony Price','bird-nets-for-balcony-price','BIRD NETS',1000,1,520,'2026-09-19 02:27:21',0),
(928374,10,'Bird Nets For Balcony Cost','bird-nets-for-balcony-cost','BIRD NETS',1000,1,521,'2026-09-19 02:27:21',0),
(928376,10,'Bird Nets For Balcony Fixing','bird-nets-for-balcony-fixing','BIRD NETS',1000,1,523,'2026-09-19 02:27:21',0),
(928379,10,'Bird Net For Balcony Dealers','bird-net-for-balcony-dealers','BIRD NETS',1000,1,526,'2026-09-19 02:27:21',0),
(928380,10,'Bird Net For Balcony Price','bird-net-for-balcony-price','BIRD NETS',1000,1,527,'2026-09-19 02:27:21',0),
(928384,10,'Window Bird Nets Dealers','window-bird-nets-dealers','BIRD NETS',1000,1,531,'2026-09-19 02:27:21',0),
(928385,10,'Window Bird Nets Price','window-bird-nets-price','BIRD NETS',1000,1,532,'2026-09-19 02:27:21',0),
(928389,10,'Terrace Bird Nets Dealers','terrace-bird-nets-dealers','BIRD NETS',1000,1,536,'2026-09-19 02:27:21',0),
(928390,10,'Terrace Bird Nets Price','terrace-bird-nets-price','BIRD NETS',1000,1,537,'2026-09-19 02:27:21',0),
(928394,10,'Commercial Bird Netting Dealers','commercial-bird-netting-dealers','BIRD NETS',1000,1,541,'2026-09-19 02:27:21',0),
(928395,10,'Commercial Bird Netting Price','commercial-bird-netting-price','BIRD NETS',1000,1,542,'2026-09-19 02:27:21',0),
(928399,10,'Industrial Bird Netting Dealers','industrial-bird-netting-dealers','BIRD NETS',1000,1,546,'2026-09-19 02:27:21',0),
(928400,10,'Industrial Bird Netting Price','industrial-bird-netting-price','BIRD NETS',1000,1,547,'2026-09-19 02:27:21',0),
(928404,10,'Warehouse Bird Netting Dealers','warehouse-bird-netting-dealers','BIRD NETS',1000,1,551,'2026-09-19 02:27:21',0),
(928405,10,'Warehouse Bird Netting Price','warehouse-bird-netting-price','BIRD NETS',1000,1,552,'2026-09-19 02:27:21',0),
(928409,10,'Factory Bird Netting Dealers','factory-bird-netting-dealers','BIRD NETS',1000,1,556,'2026-09-19 02:27:21',0),
(928410,10,'Factory Bird Netting Price','factory-bird-netting-price','BIRD NETS',1000,1,557,'2026-09-19 02:27:21',0),
(928414,10,'Transparent Bird Netting Dealers','transparent-bird-netting-dealers','BIRD NETS',1000,1,561,'2026-09-19 02:27:21',0),
(928415,10,'Transparent Bird Netting Price','transparent-bird-netting-price','BIRD NETS',1000,1,562,'2026-09-19 02:27:21',0),
(928419,10,'Sparrow Protection Nets Dealers','sparrow-protection-nets-dealers','BIRD NETS',1000,1,566,'2026-09-19 02:27:21',0),
(928420,10,'Sparrow Protection Nets Price','sparrow-protection-nets-price','BIRD NETS',1000,1,567,'2026-09-19 02:27:21',0),
(928424,10,'Crow Protection Nets Dealers','crow-protection-nets-dealers','BIRD NETS',1000,1,571,'2026-09-19 02:27:21',0),
(928425,10,'Crow Protection Nets Price','crow-protection-nets-price','BIRD NETS',1000,1,572,'2026-09-19 02:27:21',0),
(928429,10,'Bird Protection Nets Dealers','bird-protection-nets-dealers','BIRD NETS',1000,1,576,'2026-09-19 02:27:21',0),
(928430,10,'Bird Protection Nets Price','bird-protection-nets-price','BIRD NETS',1000,1,577,'2026-09-19 02:27:21',0),
(928434,10,'Garware Bird Nets Dealers','garware-bird-nets-dealers','BIRD NETS',1000,1,581,'2026-09-19 02:27:21',0),
(928435,10,'Garware Bird Nets Price','garware-bird-nets-price','BIRD NETS',1000,1,582,'2026-09-19 02:27:21',0),
(928439,10,'Nylon Bird Nets Dealers','nylon-bird-nets-dealers','BIRD NETS',1000,1,586,'2026-09-19 02:27:21',0),
(928440,10,'Nylon Bird Nets Price','nylon-bird-nets-price','BIRD NETS',1000,1,587,'2026-09-19 02:27:21',0),
(928444,10,'Bird Proofing Solutions Dealers','bird-proofing-solutions-dealers','BIRD NETS',1000,1,591,'2026-09-19 02:27:21',0),
(928445,10,'Bird Proofing Solutions Price','bird-proofing-solutions-price','BIRD NETS',1000,1,592,'2026-09-19 02:27:21',0),
(928449,512,'Pigeon Spikes Dealers','pigeon-spikes-dealers','BIRD NETS',1000,1,596,'2026-09-19 02:27:21',0),
(928450,512,'Pigeon Spikes Price','pigeon-spikes-price','BIRD NETS',1000,1,597,'2026-09-19 02:27:21',0),
(928453,512,'Stainless Steel Bird Spikes Dealers','stainless-steel-bird-spikes-dealers','BIRD NETS',1000,1,600,'2026-09-19 02:27:21',0),
(928454,512,'Stainless Steel Bird Spikes Price','stainless-steel-bird-spikes-price','BIRD NETS',1000,1,601,'2026-09-19 02:27:21',0),
(928457,512,'Polycarbonate Bird Spikes Dealers','polycarbonate-bird-spikes-dealers','BIRD NETS',1000,1,604,'2026-09-19 02:27:21',0),
(928458,512,'Polycarbonate Bird Spikes Price','polycarbonate-bird-spikes-price','BIRD NETS',1000,1,605,'2026-09-19 02:27:21',0),
(928461,512,'Window Bird Spikes Dealers','window-bird-spikes-dealers','BIRD NETS',1000,1,608,'2026-09-19 02:27:21',0),
(928462,512,'Window Bird Spikes Price','window-bird-spikes-price','BIRD NETS',1000,1,609,'2026-09-19 02:27:21',0),
(928465,512,'Balcony Bird Spikes Dealers','balcony-bird-spikes-dealers','BIRD NETS',1000,1,612,'2026-09-19 02:27:21',0),
(928466,512,'Balcony Bird Spikes Price','balcony-bird-spikes-price','BIRD NETS',1000,1,613,'2026-09-19 02:27:21',0),
(928469,512,'AC Outdoor Unit Bird Spikes Dealers','ac-outdoor-unit-bird-spikes-dealers','BIRD NETS',1000,1,616,'2026-09-19 02:27:21',0),
(928470,512,'AC Outdoor Unit Bird Spikes Price','ac-outdoor-unit-bird-spikes-price','BIRD NETS',1000,1,617,'2026-09-19 02:27:21',0),
(928474,512,'Bird Deterrent Spikes Dealers','bird-deterrent-spikes-dealers','BIRD NETS',1000,1,621,'2026-09-19 02:27:21',0),
(928475,512,'Bird Deterrent Spikes Price','bird-deterrent-spikes-price','BIRD NETS',1000,1,622,'2026-09-19 02:27:21',0),
(928478,512,'Commercial Bird Spikes Dealers','commercial-bird-spikes-dealers','BIRD NETS',1000,1,625,'2026-09-19 02:27:21',0),
(928479,512,'Commercial Bird Spikes Price','commercial-bird-spikes-price','BIRD NETS',1000,1,626,'2026-09-19 02:27:21',0),
(928531,32,'Kids Safety Nets Installation','kids-safety-nets-installation','SAFETY NETS',1000,1,678,'2026-09-19 02:27:21',0),
(928532,32,'Kids Safety Nets Dealers','kids-safety-nets-dealers','SAFETY NETS',1000,1,679,'2026-09-19 02:27:21',0),
(928533,32,'Kids Safety Nets Price','kids-safety-nets-price','SAFETY NETS',1000,1,680,'2026-09-19 02:27:21',0),
(928536,32,'Baby Safety Nets Installation','baby-safety-nets-installation','SAFETY NETS',1000,1,683,'2026-09-19 02:27:21',0),
(928537,32,'Baby Safety Nets Dealers','baby-safety-nets-dealers','SAFETY NETS',1000,1,684,'2026-09-19 02:27:21',0),
(928538,32,'Baby Safety Nets Price','baby-safety-nets-price','SAFETY NETS',1000,1,685,'2026-09-19 02:27:21',0),
(928540,32,'Staircase Child Safety Nets Installation','staircase-child-safety-nets-installation','SAFETY NETS',1000,1,687,'2026-09-19 02:27:21',0),
(928541,32,'Staircase Child Safety Nets Dealers','staircase-child-safety-nets-dealers','SAFETY NETS',1000,1,688,'2026-09-19 02:27:21',0),
(928542,32,'Staircase Child Safety Nets Price','staircase-child-safety-nets-price','SAFETY NETS',1000,1,689,'2026-09-19 02:27:21',0),
(928544,32,'Window Child Safety Nets Installation','window-child-safety-nets-installation','SAFETY NETS',1000,1,691,'2026-09-19 02:27:21',0),
(928545,32,'Window Child Safety Nets Dealers','window-child-safety-nets-dealers','SAFETY NETS',1000,1,692,'2026-09-19 02:27:21',0),
(928546,32,'Window Child Safety Nets Price','window-child-safety-nets-price','SAFETY NETS',1000,1,693,'2026-09-19 02:27:21',0),
(928549,32,'Toddler Safety Nets Installation','toddler-safety-nets-installation','SAFETY NETS',1000,1,696,'2026-09-19 02:27:21',0),
(928550,32,'Toddler Safety Nets Dealers','toddler-safety-nets-dealers','SAFETY NETS',1000,1,697,'2026-09-19 02:27:21',0),
(928551,32,'Toddler Safety Nets Price','toddler-safety-nets-price','SAFETY NETS',1000,1,698,'2026-09-19 02:27:21',0),
(928553,32,'High Tension Child Safety Nets Installation','high-tension-child-safety-nets-installation','SAFETY NETS',1000,1,700,'2026-09-19 02:27:21',0),
(928554,32,'High Tension Child Safety Nets Dealers','high-tension-child-safety-nets-dealers','SAFETY NETS',1000,1,701,'2026-09-19 02:27:21',0),
(928555,32,'High Tension Child Safety Nets Price','high-tension-child-safety-nets-price','SAFETY NETS',1000,1,702,'2026-09-19 02:27:21',0),
(928558,32,'Children Balcony Fall Protection Installation','children-balcony-fall-protection-installation','SAFETY NETS',1000,1,705,'2026-09-19 02:27:21',0),
(928559,32,'Children Balcony Fall Protection Dealers','children-balcony-fall-protection-dealers','SAFETY NETS',1000,1,706,'2026-09-19 02:27:21',0),
(928560,32,'Children Balcony Fall Protection Price','children-balcony-fall-protection-price','SAFETY NETS',1000,1,707,'2026-09-19 02:27:21',0),
(928563,33,'Cat Netting For Balcony Installation','cat-netting-for-balcony-installation','SAFETY NETS',1000,1,710,'2026-09-19 02:27:21',0),
(928564,33,'Cat Netting For Balcony Dealers','cat-netting-for-balcony-dealers','SAFETY NETS',1000,1,711,'2026-09-19 02:27:21',0),
(928565,33,'Cat Netting For Balcony Price','cat-netting-for-balcony-price','SAFETY NETS',1000,1,712,'2026-09-19 02:27:21',0),
(928568,33,'Dog Safety Nets Installation','dog-safety-nets-installation','SAFETY NETS',1000,1,715,'2026-09-19 02:27:21',0),
(928569,33,'Dog Safety Nets Dealers','dog-safety-nets-dealers','SAFETY NETS',1000,1,716,'2026-09-19 02:27:21',0),
(928570,33,'Dog Safety Nets Price','dog-safety-nets-price','SAFETY NETS',1000,1,717,'2026-09-19 02:27:21',0),
(928573,33,'Animal Safety Nets Installation','animal-safety-nets-installation','SAFETY NETS',1000,1,720,'2026-09-19 02:27:21',0),
(928574,33,'Animal Safety Nets Dealers','animal-safety-nets-dealers','SAFETY NETS',1000,1,721,'2026-09-19 02:27:21',0),
(928575,33,'Animal Safety Nets Price','animal-safety-nets-price','SAFETY NETS',1000,1,722,'2026-09-19 02:27:21',0),
(928578,27,'Monkey Safety Nets Installation','monkey-safety-nets-installation','SAFETY NETS',1000,1,725,'2026-09-19 02:27:21',0),
(928579,27,'Monkey Safety Nets Dealers','monkey-safety-nets-dealers','SAFETY NETS',1000,1,726,'2026-09-19 02:27:21',0),
(928580,27,'Monkey Safety Nets Price','monkey-safety-nets-price','SAFETY NETS',1000,1,727,'2026-09-19 02:27:21',0),
(928583,27,'Anti Monkey Nets Installation','anti-monkey-nets-installation','SAFETY NETS',1000,1,730,'2026-09-19 02:27:21',0),
(928584,27,'Anti Monkey Nets Dealers','anti-monkey-nets-dealers','SAFETY NETS',1000,1,731,'2026-09-19 02:27:21',0),
(928585,27,'Anti Monkey Nets Price','anti-monkey-nets-price','SAFETY NETS',1000,1,732,'2026-09-19 02:27:21',0),
(928588,27,'Monkey Protection Nets Installation','monkey-protection-nets-installation','SAFETY NETS',1000,1,735,'2026-09-19 02:27:21',0),
(928589,27,'Monkey Protection Nets Dealers','monkey-protection-nets-dealers','SAFETY NETS',1000,1,736,'2026-09-19 02:27:21',0),
(928590,27,'Monkey Protection Nets Price','monkey-protection-nets-price','SAFETY NETS',1000,1,737,'2026-09-19 02:27:21',0),
(928593,27,'Monkey Barrier Nets Installation','monkey-barrier-nets-installation','SAFETY NETS',1000,1,740,'2026-09-19 02:27:21',0),
(928594,27,'Monkey Barrier Nets Dealers','monkey-barrier-nets-dealers','SAFETY NETS',1000,1,741,'2026-09-19 02:27:21',0),
(928595,27,'Monkey Barrier Nets Price','monkey-barrier-nets-price','SAFETY NETS',1000,1,742,'2026-09-19 02:27:21',0),
(928598,33,'Pet Fall Protection Nets Installation','pet-fall-protection-nets-installation','SAFETY NETS',1000,1,745,'2026-09-19 02:27:21',0),
(928599,33,'Pet Fall Protection Nets Dealers','pet-fall-protection-nets-dealers','SAFETY NETS',1000,1,746,'2026-09-19 02:27:21',0),
(928600,33,'Pet Fall Protection Nets Price','pet-fall-protection-nets-price','SAFETY NETS',1000,1,747,'2026-09-19 02:27:21',0),
(928628,515,'Rooftop Cricket Nets Dealers','rooftop-cricket-nets-dealers','SPORTS NETS',1000,1,775,'2026-09-19 02:27:21',0),
(928632,515,'Outdoor Cricket Nets Dealers','outdoor-cricket-nets-dealers','SPORTS NETS',1000,1,779,'2026-09-19 02:27:21',0),
(928636,515,'Indoor Cricket Nets Dealers','indoor-cricket-nets-dealers','SPORTS NETS',1000,1,783,'2026-09-19 02:27:21',0),
(928641,515,'Football Boundary Nets Dealers','football-boundary-nets-dealers','SPORTS NETS',1000,1,788,'2026-09-19 02:27:21',0),
(928646,515,'Badminton Court Nets Dealers','badminton-court-nets-dealers','SPORTS NETS',1000,1,793,'2026-09-19 02:27:21',0),
(928651,515,'Volleyball Court Nets Dealers','volleyball-court-nets-dealers','SPORTS NETS',1000,1,798,'2026-09-19 02:27:21',0),
(928656,515,'Sports Safety Nets Dealers','sports-safety-nets-dealers','SPORTS NETS',1000,1,803,'2026-09-19 02:27:21',0),
(928661,515,'Golf Practice Nets Dealers','golf-practice-nets-dealers','SPORTS NETS',1000,1,808,'2026-09-19 02:27:21',0),
(928666,515,'Sports Netting Dealers','sports-netting-dealers','SPORTS NETS',1000,1,813,'2026-09-19 02:27:21',0),
(928713,509,'Dry Cloth Hangers Dealers','dry-cloth-hangers-dealers','CLOTH HANGERS',1000,1,860,'2026-09-19 02:27:21',0),
(928718,509,'Laundry Hanger Dryer Dealers','laundry-hanger-dryer-dealers','CLOTH HANGERS',1000,1,865,'2026-09-19 02:27:21',0),
(928723,509,'Clothes Hanger Drier Dealers','clothes-hanger-drier-dealers','CLOTH HANGERS',1000,1,870,'2026-09-19 02:27:21',0),
(928727,23,'Open Area Safety Nets Installation','open-area-safety-nets-installation','SAFETY NETS',1000,1,874,'2026-09-19 02:27:21',0),
(928728,23,'Open Area Safety Nets Dealers','open-area-safety-nets-dealers','SAFETY NETS',1000,1,875,'2026-09-19 02:27:21',0),
(928729,23,'Open Area Safety Nets Price','open-area-safety-nets-price','SAFETY NETS',1000,1,876,'2026-09-19 02:27:21',0),
(928730,23,'Open Area Safety Nets Near Me','open-area-safety-nets-near-me','SAFETY NETS',1000,1,877,'2026-09-19 02:27:21',0),
(928732,23,'Staircase Safety Nets Installation','staircase-safety-nets-installation','SAFETY NETS',1000,1,879,'2026-09-19 02:27:21',0),
(928733,23,'Staircase Safety Nets Dealers','staircase-safety-nets-dealers','SAFETY NETS',1000,1,880,'2026-09-19 02:27:21',0),
(928734,23,'Staircase Safety Nets Price','staircase-safety-nets-price','SAFETY NETS',1000,1,881,'2026-09-19 02:27:21',0),
(928735,23,'Staircase Safety Nets Near Me','staircase-safety-nets-near-me','SAFETY NETS',1000,1,882,'2026-09-19 02:27:21',0),
(928737,23,'Shaft Safety Nets Installation','shaft-safety-nets-installation','SAFETY NETS',1000,1,884,'2026-09-19 02:27:21',0),
(928738,23,'Shaft Safety Nets Dealers','shaft-safety-nets-dealers','SAFETY NETS',1000,1,885,'2026-09-19 02:27:21',0),
(928739,23,'Shaft Safety Nets Price','shaft-safety-nets-price','SAFETY NETS',1000,1,886,'2026-09-19 02:27:21',0),
(928740,23,'Shaft Safety Nets Near Me','shaft-safety-nets-near-me','SAFETY NETS',1000,1,887,'2026-09-19 02:27:21',0),
(928742,28,'Debris Netting Installation','debris-netting-installation','SAFETY NETS',1000,1,889,'2026-09-19 02:27:21',0),
(928743,28,'Debris Netting Dealers','debris-netting-dealers','SAFETY NETS',1000,1,890,'2026-09-19 02:27:21',0),
(928744,28,'Debris Netting Price','debris-netting-price','SAFETY NETS',1000,1,891,'2026-09-19 02:27:21',0),
(928745,28,'Debris Netting Near Me','debris-netting-near-me','SAFETY NETS',1000,1,892,'2026-09-19 02:27:21',0),
(928747,31,'Fall Protection Nets Installation','fall-protection-nets-installation','SAFETY NETS',1000,1,894,'2026-09-19 02:27:21',0),
(928748,31,'Fall Protection Nets Dealers','fall-protection-nets-dealers','SAFETY NETS',1000,1,895,'2026-09-19 02:27:21',0),
(928749,31,'Fall Protection Nets Price','fall-protection-nets-price','SAFETY NETS',1000,1,896,'2026-09-19 02:27:21',0),
(928750,31,'Fall Protection Nets Near Me','fall-protection-nets-near-me','SAFETY NETS',1000,1,897,'2026-09-19 02:27:21',0),
(928752,30,'Fall Safety Nets Installation','fall-safety-nets-installation','SAFETY NETS',1000,1,899,'2026-09-19 02:27:21',0),
(928753,30,'Fall Safety Nets Dealers','fall-safety-nets-dealers','SAFETY NETS',1000,1,900,'2026-09-19 02:27:21',0),
(928754,30,'Fall Safety Nets Price','fall-safety-nets-price','SAFETY NETS',1000,1,901,'2026-09-19 02:27:21',0),
(928755,30,'Fall Safety Nets Near Me','fall-safety-nets-near-me','SAFETY NETS',1000,1,902,'2026-09-19 02:27:21',0),
(928757,23,'Swimming Pool Safety Nets Installation','swimming-pool-safety-nets-installation','SAFETY NETS',1000,1,904,'2026-09-19 02:27:21',0),
(928758,23,'Swimming Pool Safety Nets Dealers','swimming-pool-safety-nets-dealers','SAFETY NETS',1000,1,905,'2026-09-19 02:27:21',0),
(928759,23,'Swimming Pool Safety Nets Price','swimming-pool-safety-nets-price','SAFETY NETS',1000,1,906,'2026-09-19 02:27:21',0),
(928760,23,'Swimming Pool Safety Nets Near Me','swimming-pool-safety-nets-near-me','SAFETY NETS',1000,1,907,'2026-09-19 02:27:21',0),
(928762,23,'Coconut Tree Safety Nets Installation','coconut-tree-safety-nets-installation','SAFETY NETS',1000,1,909,'2026-09-19 02:27:21',0),
(928763,23,'Coconut Tree Safety Nets Dealers','coconut-tree-safety-nets-dealers','SAFETY NETS',1000,1,910,'2026-09-19 02:27:21',0),
(928764,23,'Coconut Tree Safety Nets Price','coconut-tree-safety-nets-price','SAFETY NETS',1000,1,911,'2026-09-19 02:27:21',0),
(928765,23,'Coconut Tree Safety Nets Near Me','coconut-tree-safety-nets-near-me','SAFETY NETS',1000,1,912,'2026-09-19 02:27:21',0),
(928767,28,'Scaffolding Safety Nets Installation','scaffolding-safety-nets-installation','SAFETY NETS',1000,1,914,'2026-09-19 02:27:21',0),
(928768,28,'Scaffolding Safety Nets Dealers','scaffolding-safety-nets-dealers','SAFETY NETS',1000,1,915,'2026-09-19 02:27:21',0),
(928769,28,'Scaffolding Safety Nets Price','scaffolding-safety-nets-price','SAFETY NETS',1000,1,916,'2026-09-19 02:27:21',0),
(928770,28,'Scaffolding Safety Nets Near Me','scaffolding-safety-nets-near-me','SAFETY NETS',1000,1,917,'2026-09-19 02:27:21',0),
(928771,23,'Glass Balcony Safety Nets Installation','glass-balcony-safety-nets-installation','SAFETY NETS',1000,1,918,'2026-09-19 02:27:21',0),
(928772,23,'Glass Balcony Safety Nets Dealers','glass-balcony-safety-nets-dealers','SAFETY NETS',1000,1,919,'2026-09-19 02:27:21',0),
(928773,23,'Glass Balcony Safety Nets Price','glass-balcony-safety-nets-price','SAFETY NETS',1000,1,920,'2026-09-19 02:27:21',0),
(928774,23,'Glass Balcony Safety Nets Near Me','glass-balcony-safety-nets-near-me','SAFETY NETS',1000,1,921,'2026-09-19 02:27:21',0),
(928776,23,'Heavy Duty Safety Nets Installation','heavy-duty-safety-nets-installation','SAFETY NETS',1000,1,923,'2026-09-19 02:27:21',0),
(928777,23,'Heavy Duty Safety Nets Dealers','heavy-duty-safety-nets-dealers','SAFETY NETS',1000,1,924,'2026-09-19 02:27:21',0),
(928778,23,'Heavy Duty Safety Nets Price','heavy-duty-safety-nets-price','SAFETY NETS',1000,1,925,'2026-09-19 02:27:21',0),
(928779,23,'Heavy Duty Safety Nets Near Me','heavy-duty-safety-nets-near-me','SAFETY NETS',1000,1,926,'2026-09-19 02:27:21',0),
(928781,23,'HDPE Safety Nets Installation','hdpe-safety-nets-installation','SAFETY NETS',1000,1,928,'2026-09-19 02:27:21',0),
(928782,23,'HDPE Safety Nets Dealers','hdpe-safety-nets-dealers','SAFETY NETS',1000,1,929,'2026-09-19 02:27:21',0),
(928783,23,'HDPE Safety Nets Price','hdpe-safety-nets-price','SAFETY NETS',1000,1,930,'2026-09-19 02:27:21',0),
(928784,23,'HDPE Safety Nets Near Me','hdpe-safety-nets-near-me','SAFETY NETS',1000,1,931,'2026-09-19 02:27:21',0),
(928786,23,'Safety Netting Solutions Installation','safety-netting-solutions-installation','SAFETY NETS',1000,1,933,'2026-09-19 02:27:21',0),
(928787,23,'Safety Netting Solutions Dealers','safety-netting-solutions-dealers','SAFETY NETS',1000,1,934,'2026-09-19 02:27:21',0),
(928788,23,'Safety Netting Solutions Price','safety-netting-solutions-price','SAFETY NETS',1000,1,935,'2026-09-19 02:27:21',0),
(928789,23,'Safety Netting Solutions Near Me','safety-netting-solutions-near-me','SAFETY NETS',1000,1,936,'2026-09-19 02:27:21',0),
(928790,23,'Safety Net Installation','safety-net-installation','SAFETY NETS',1000,1,937,'2026-09-19 02:27:21',0),
(928791,23,'Safety Net Installation Installation','safety-net-installation-installation','SAFETY NETS',1000,1,938,'2026-09-19 02:27:21',0),
(928792,23,'Safety Net Installation Dealers','safety-net-installation-dealers','SAFETY NETS',1000,1,939,'2026-09-19 02:27:21',0),
(928793,23,'Safety Net Installation Price','safety-net-installation-price','SAFETY NETS',1000,1,940,'2026-09-19 02:27:21',0),
(928794,23,'Safety Net Installation Near Me','safety-net-installation-near-me','SAFETY NETS',1000,1,941,'2026-09-19 02:27:21',0);
/*!40000 ALTER TABLE `seo_service_keywords` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `service_areas`
--

DROP TABLE IF EXISTS `service_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `area_name` varchar(100) NOT NULL,
  `area_slug` varchar(100) NOT NULL,
  `zone` varchar(100) DEFAULT 'Chennai',
  `city` varchar(100) DEFAULT 'Chennai',
  `state` varchar(100) DEFAULT 'Tamil Nadu',
  `pincode` varchar(10) DEFAULT '600001',
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `area_slug` (`area_slug`),
  KEY `idx_slug` (`area_slug`),
  KEY `idx_zone` (`zone`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=636 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_areas`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `service_areas` WRITE;
/*!40000 ALTER TABLE `service_areas` DISABLE KEYS */;
INSERT INTO `service_areas` VALUES
(1,'Thiruvottiyur','thiruvottiyur','Thiruvottiyur','Chennai','Tamil Nadu','600019',1,1),
(2,'Ennore','ennore','Thiruvottiyur','Chennai','Tamil Nadu','600057',1,2),
(3,'Kathivakkam','kathivakkam','Thiruvottiyur','Chennai','Tamil Nadu','600057',1,3),
(4,'Ernavoor','ernavoor','Thiruvottiyur','Chennai','Tamil Nadu','600057',1,4),
(5,'Wimco Nagar','wimco-nagar','Thiruvottiyur','Chennai','Tamil Nadu','600057',1,5),
(6,'Tollgate Thiruvottiyur','tollgate-thiruvottiyur','Thiruvottiyur','Chennai','Tamil Nadu','600019',1,6),
(7,'Theradi','theradi','Thiruvottiyur','Chennai','Tamil Nadu','600019',1,7),
(8,'Kaladipet','kaladipet','Thiruvottiyur','Chennai','Tamil Nadu','600019',1,8),
(9,'Raja Shanmugam Nagar','raja-shanmugam-nagar','Thiruvottiyur','Chennai','Tamil Nadu','600019',1,9),
(10,'Sathangadu','sathangadu','Thiruvottiyur','Chennai','Tamil Nadu','600068',1,10),
(11,'Jothi Nagar Thiruvottiyur','jothi-nagar-thiruvottiyur','Thiruvottiyur','Chennai','Tamil Nadu','600019',1,11),
(12,'Bharathi Nagar Thiruvottiyur','bharathi-nagar-thiruvottiyur','Thiruvottiyur','Chennai','Tamil Nadu','600019',1,12),
(13,'Ajay Nagar','ajay-nagar','Thiruvottiyur','Chennai','Tamil Nadu','600019',1,13),
(14,'Tsunami Colony Ernavoor','tsunami-colony-ernavoor','Thiruvottiyur','Chennai','Tamil Nadu','600057',1,14),
(15,'Nandiyambakkam','nandiyambakkam','Thiruvottiyur','Chennai','Tamil Nadu','600120',1,15),
(16,'Manali','manali','Manali','Chennai','Tamil Nadu','600068',1,16),
(17,'Manali New Town','manali-new-town','Manali','Chennai','Tamil Nadu','600103',1,17),
(18,'Mathur MMDA','mathur-mmda','Manali','Chennai','Tamil Nadu','600068',1,18),
(19,'Chinnasekadu','chinnasekadu','Manali','Chennai','Tamil Nadu','600068',1,19),
(20,'Kosapur','kosapur','Manali','Chennai','Tamil Nadu','600060',1,20),
(21,'Edayanchavadi','edayanchavadi','Manali','Chennai','Tamil Nadu','600103',1,21),
(22,'Vichoor','vichoor','Manali','Chennai','Tamil Nadu','600103',1,22),
(23,'Andarkuppam','andarkuppam','Manali','Chennai','Tamil Nadu','600103',1,23),
(24,'Amullavoyal','amullavoyal','Manali','Chennai','Tamil Nadu','600068',1,24),
(25,'Sadaiyankuppam','sadaiyankuppam','Manali','Chennai','Tamil Nadu','600103',1,25),
(26,'Kadappakkam','kadappakkam','Manali','Chennai','Tamil Nadu','600103',1,26),
(27,'Madhavaram','madhavaram','Madhavaram','Chennai','Tamil Nadu','600060',1,27),
(28,'Madhavaram Milk Colony','madhavaram-milk-colony','Madhavaram','Chennai','Tamil Nadu','600051',1,28),
(29,'Puzhal','puzhal','Madhavaram','Chennai','Tamil Nadu','600066',1,29),
(30,'Red Hills','red-hills','Madhavaram','Chennai','Tamil Nadu','600052',1,30),
(31,'Surapet','surapet','Madhavaram','Chennai','Tamil Nadu','600066',1,31),
(32,'Padianallur','padianallur','Madhavaram','Chennai','Tamil Nadu','600052',1,32),
(33,'Kavangarai','kavangarai','Madhavaram','Chennai','Tamil Nadu','600066',1,33),
(34,'Vadakarai','vadakarai','Madhavaram','Chennai','Tamil Nadu','600052',1,34),
(35,'Granthian','granthian','Madhavaram','Chennai','Tamil Nadu','600052',1,35),
(36,'Theeyambakkam','theeyambakkam','Madhavaram','Chennai','Tamil Nadu','600060',1,36),
(37,'Assisi Nagar','assisi-nagar','Madhavaram','Chennai','Tamil Nadu','600060',1,37),
(38,'Lakshmipuram Madhavaram','lakshmipuram-madhavaram','Madhavaram','Chennai','Tamil Nadu','600099',1,38),
(39,'Vinayagapuram','vinayagapuram','Madhavaram','Chennai','Tamil Nadu','600099',1,39),
(40,'Kallikuppam','kallikuppam','Madhavaram','Chennai','Tamil Nadu','600053',1,40),
(41,'Retteri','retteri','Madhavaram','Chennai','Tamil Nadu','600099',1,41),
(42,'Tondiarpet','tondiarpet','Tondiarpet','Chennai','Tamil Nadu','600081',1,42),
(43,'Washermanpet','washermanpet','Tondiarpet','Chennai','Tamil Nadu','600021',1,43),
(44,'Old Washermanpet','old-washermanpet','Tondiarpet','Chennai','Tamil Nadu','600021',1,44),
(45,'New Washermanpet','new-washermanpet','Tondiarpet','Chennai','Tamil Nadu','600081',1,45),
(46,'Korukkupet','korukkupet','Tondiarpet','Chennai','Tamil Nadu','600021',1,46),
(47,'Cowl Bazaar North','cowl-bazaar-north','Tondiarpet','Chennai','Tamil Nadu','600081',1,47),
(48,'Stanley Nagar','stanley-nagar','Tondiarpet','Chennai','Tamil Nadu','600021',1,48),
(49,'Meenakshi Amman Nagar','meenakshi-amman-nagar','Tondiarpet','Chennai','Tamil Nadu','600081',1,49),
(50,'Vaidyanathan Street','vaidyanathan-street','Tondiarpet','Chennai','Tamil Nadu','600081',1,50),
(51,'Seniamman Koil','seniamman-koil','Tondiarpet','Chennai','Tamil Nadu','600081',1,51),
(52,'Royapuram','royapuram','Royapuram','Chennai','Tamil Nadu','600013',1,52),
(53,'George Town','george-town','Royapuram','Chennai','Tamil Nadu','600001',1,53),
(54,'Sowcarpet','sowcarpet','Royapuram','Chennai','Tamil Nadu','600079',1,54),
(55,'Parrys Corner','parrys-corner','Royapuram','Chennai','Tamil Nadu','600001',1,55),
(56,'Mannady','mannady','Royapuram','Chennai','Tamil Nadu','600001',1,56),
(57,'Broadway Chennai','broadway-chennai','Royapuram','Chennai','Tamil Nadu','600108',1,57),
(58,'Park Town','park-town','Royapuram','Chennai','Tamil Nadu','600003',1,58),
(59,'Central Railway Station Area','central-railway-station-area','Royapuram','Chennai','Tamil Nadu','600003',1,59),
(60,'Mint Street','mint-street','Royapuram','Chennai','Tamil Nadu','600079',1,60),
(61,'Kothawal Chavadi','kothawal-chavadi','Royapuram','Chennai','Tamil Nadu','600001',1,61),
(62,'Kasimedu','kasimedu','Royapuram','Chennai','Tamil Nadu','600013',1,62),
(63,'Kalmandapam','kalmandapam','Royapuram','Chennai','Tamil Nadu','600013',1,63),
(64,'Singarachari Street','singarachari-street','Royapuram','Chennai','Tamil Nadu','600013',1,64),
(65,'Seven Wells','seven-wells','Royapuram','Chennai','Tamil Nadu','600001',1,65),
(66,'Edapalayam','edapalayam','Royapuram','Chennai','Tamil Nadu','600003',1,66),
(67,'Perambur','perambur','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600011',1,67),
(68,'Kolathur','kolathur','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600099',1,68),
(69,'Thiru Vi Ka Nagar','thiru-vi-ka-nagar','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600082',1,69),
(70,'Vyasarpadi','vyasarpadi','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600039',1,70),
(71,'Kodungaiyur','kodungaiyur','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600118',1,71),
(72,'Erukkanchery','erukkanchery','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600118',1,72),
(73,'Sembium','sembium','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600011',1,73),
(74,'Periyar Nagar','periyar-nagar','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600082',1,74),
(75,'Jawahar Nagar','jawahar-nagar','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600082',1,75),
(76,'Agaram','agaram','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600082',1,76),
(77,'GKM Colony','gkm-colony','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600082',1,77),
(78,'Kumaran Nagar Kolathur','kumaran-nagar-kolathur','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600099',1,78),
(79,'Poombuhar Nagar','poombuhar-nagar','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600099',1,79),
(80,'Teachers Colony Kolathur','teachers-colony-kolathur','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600099',1,80),
(81,'MKB Nagar','mkb-nagar','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600039',1,81),
(82,'Muthamizh Nagar','muthamizh-nagar','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600118',1,82),
(83,'Kaviarasu Kannadasan Nagar','kaviarasu-kannadasan-nagar','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600118',1,83),
(84,'Moorthy Nagar','moorthy-nagar','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600118',1,84),
(85,'Sidco Nagar Villivakkam','sidco-nagar-villivakkam','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600049',1,85),
(86,'Villivakkam','villivakkam','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600049',1,86),
(87,'Konnur High Road','konnur-high-road','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600023',1,87),
(88,'Ayanavaram','ayanavaram','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600023',1,88),
(89,'Otteri','otteri','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600012',1,89),
(90,'Pattalam','pattalam','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600012',1,90),
(91,'Pulianthope','pulianthope','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600012',1,91),
(92,'Choolai','choolai','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600112',1,92),
(93,'Periamet','periamet','Thiru Vi Ka Nagar','Chennai','Tamil Nadu','600003',1,93),
(94,'Ambattur','ambattur','Ambattur','Chennai','Tamil Nadu','600053',1,94),
(95,'Ambattur OT','ambattur-ot','Ambattur','Chennai','Tamil Nadu','600053',1,95),
(96,'Ambattur Industrial Estate','ambattur-industrial-estate','Ambattur','Chennai','Tamil Nadu','600058',1,96),
(97,'Mogappair','mogappair','Ambattur','Chennai','Tamil Nadu','600037',1,97),
(98,'Mogappair East','mogappair-east','Ambattur','Chennai','Tamil Nadu','600037',1,98),
(99,'Mogappair West','mogappair-west','Ambattur','Chennai','Tamil Nadu','600037',1,99),
(100,'Nolambur','nolambur','Ambattur','Chennai','Tamil Nadu','600095',1,100),
(101,'Padi','padi','Ambattur','Chennai','Tamil Nadu','600050',1,101),
(102,'Korattur','korattur','Ambattur','Chennai','Tamil Nadu','600080',1,102),
(103,'Mannurpet','mannurpet','Ambattur','Chennai','Tamil Nadu','600050',1,103),
(104,'Oragadam Ambattur','oragadam-ambattur','Ambattur','Chennai','Tamil Nadu','600053',1,104),
(105,'Venkatapuram Ambattur','venkatapuram-ambattur','Ambattur','Chennai','Tamil Nadu','600053',1,105),
(106,'Vijayalakshmipuram','vijayalakshmipuram','Ambattur','Chennai','Tamil Nadu','600053',1,106),
(107,'Varadarajapuram Ambattur','varadarajapuram-ambattur','Ambattur','Chennai','Tamil Nadu','600053',1,107),
(108,'Ram Nagar Ambattur','ram-nagar-ambattur','Ambattur','Chennai','Tamil Nadu','600053',1,108),
(109,'Prithvipakkam','prithvipakkam','Ambattur','Chennai','Tamil Nadu','600053',1,109),
(110,'Menambedu','menambedu','Ambattur','Chennai','Tamil Nadu','600053',1,110),
(111,'Karukku','karukku','Ambattur','Chennai','Tamil Nadu','600053',1,111),
(112,'Gnanamoorthy Nagar','gnanamoorthy-nagar','Ambattur','Chennai','Tamil Nadu','600053',1,112),
(113,'TVS Nagar','tvs-nagar','Ambattur','Chennai','Tamil Nadu','600080',1,113),
(114,'Central Avenue Korattur','central-avenue-korattur','Ambattur','Chennai','Tamil Nadu','600080',1,114),
(115,'Jeeva Nagar Korattur','jeeva-nagar-korattur','Ambattur','Chennai','Tamil Nadu','600080',1,115),
(116,'Golden Jubilee Flats Mogappair','golden-jubilee-flats-mogappair','Ambattur','Chennai','Tamil Nadu','600037',1,116),
(117,'VGN Minerva Nolambur','vgn-minerva-nolambur','Ambattur','Chennai','Tamil Nadu','600095',1,117),
(118,'City Light Meadows Nolambur','city-light-meadows-nolambur','Ambattur','Chennai','Tamil Nadu','600095',1,118),
(119,'Anna Nagar','anna-nagar','Anna Nagar','Chennai','Tamil Nadu','600040',1,119),
(120,'Anna Nagar East','anna-nagar-east','Anna Nagar','Chennai','Tamil Nadu','600102',1,120),
(121,'Anna Nagar West','anna-nagar-west','Anna Nagar','Chennai','Tamil Nadu','600040',1,121),
(122,'Anna Nagar West Extension','anna-nagar-west-extension','Anna Nagar','Chennai','Tamil Nadu','600101',1,122),
(123,'Shenoy Nagar','shenoy-nagar','Anna Nagar','Chennai','Tamil Nadu','600030',1,123),
(124,'Kilpauk','kilpauk','Anna Nagar','Chennai','Tamil Nadu','600010',1,124),
(125,'Kilpauk Garden','kilpauk-garden','Anna Nagar','Chennai','Tamil Nadu','600010',1,125),
(126,'Kellys','kellys','Anna Nagar','Chennai','Tamil Nadu','600010',1,126),
(127,'Chetpet','chetpet','Anna Nagar','Chennai','Tamil Nadu','600031',1,127),
(128,'Harrington Road','harrington-road','Anna Nagar','Chennai','Tamil Nadu','600031',1,128),
(129,'Spur Tank Road','spur-tank-road','Anna Nagar','Chennai','Tamil Nadu','600031',1,129),
(130,'McNichols Road','mcnichols-road','Anna Nagar','Chennai','Tamil Nadu','600031',1,130),
(131,'Koyambedu','koyambedu','Anna Nagar','Chennai','Tamil Nadu','600107',1,131),
(132,'CMBT Koyambedu','cmbt-koyambedu','Anna Nagar','Chennai','Tamil Nadu','600107',1,132),
(133,'SAF Games Village','saf-games-village','Anna Nagar','Chennai','Tamil Nadu','600107',1,133),
(134,'Arumbakkam','arumbakkam','Anna Nagar','Chennai','Tamil Nadu','600106',1,134),
(135,'MMDA Colony Arumbakkam','mmda-colony-arumbakkam','Anna Nagar','Chennai','Tamil Nadu','600106',1,135),
(136,'Jafferkhanpet','jafferkhanpet','Anna Nagar','Chennai','Tamil Nadu','600083',1,136),
(137,'Choolaimedu','choolaimedu','Anna Nagar','Chennai','Tamil Nadu','600094',1,137),
(138,'Gill Nagar','gill-nagar','Anna Nagar','Chennai','Tamil Nadu','600094',1,138),
(139,'Bajanai Koil Choolaimedu','bajanai-koil-choolaimedu','Anna Nagar','Chennai','Tamil Nadu','600094',1,139),
(140,'Shanti Colony Anna Nagar','shanti-colony-anna-nagar','Anna Nagar','Chennai','Tamil Nadu','600040',1,140),
(141,'Tower Park Anna Nagar','tower-park-anna-nagar','Anna Nagar','Chennai','Tamil Nadu','600040',1,141),
(142,'Roundtana Anna Nagar','roundtana-anna-nagar','Anna Nagar','Chennai','Tamil Nadu','600040',1,142),
(143,'Thirumangalam','thirumangalam','Anna Nagar','Chennai','Tamil Nadu','600040',1,143),
(144,'Park Road Anna Nagar','park-road-anna-nagar','Anna Nagar','Chennai','Tamil Nadu','600101',1,144),
(145,'Collector Nagar','collector-nagar','Anna Nagar','Chennai','Tamil Nadu','600101',1,145),
(146,'Padikuppam','padikuppam','Anna Nagar','Chennai','Tamil Nadu','600107',1,146),
(147,'Aminjikarai','aminjikarai','Anna Nagar','Chennai','Tamil Nadu','600029',1,147),
(148,'TP Chatram','tp-chatram','Anna Nagar','Chennai','Tamil Nadu','600030',1,148),
(149,'Gajalakshmi Colony','gajalakshmi-colony','Anna Nagar','Chennai','Tamil Nadu','600030',1,149),
(150,'Teynampet','teynampet','Teynampet','Chennai','Tamil Nadu','600018',1,150),
(151,'Nungambakkam','nungambakkam','Teynampet','Chennai','Tamil Nadu','600034',1,151),
(152,'Sterling Road','sterling-road','Teynampet','Chennai','Tamil Nadu','600034',1,152),
(153,'College Road Nungambakkam','college-road-nungambakkam','Teynampet','Chennai','Tamil Nadu','600034',1,153),
(154,'Haddows Road','haddows-road','Teynampet','Chennai','Tamil Nadu','600006',1,154),
(155,'Khader Nawaz Khan Road','khader-nawaz-khan-road','Teynampet','Chennai','Tamil Nadu','600006',1,155),
(156,'Thousand Lights','thousand-lights','Teynampet','Chennai','Tamil Nadu','600006',1,156),
(157,'Greams Road','greams-road','Teynampet','Chennai','Tamil Nadu','600006',1,157),
(158,'Gopalapuram','gopalapuram','Teynampet','Chennai','Tamil Nadu','600086',1,158),
(159,'Lloyds Road','lloyds-road','Teynampet','Chennai','Tamil Nadu','600014',1,159),
(160,'Royapettah','royapettah','Teynampet','Chennai','Tamil Nadu','600014',1,160),
(161,'Triplicane','triplicane','Teynampet','Chennai','Tamil Nadu','600005',1,161),
(162,'Ice House','ice-house','Teynampet','Chennai','Tamil Nadu','600005',1,162),
(163,'Chepauk','chepauk','Teynampet','Chennai','Tamil Nadu','600005',1,163),
(164,'Marina Beach Road','marina-beach-road','Teynampet','Chennai','Tamil Nadu','600005',1,164),
(165,'Mylapore','mylapore','Teynampet','Chennai','Tamil Nadu','600004',1,165),
(166,'Luz Mylapore','luz-mylapore','Teynampet','Chennai','Tamil Nadu','600004',1,166),
(167,'San Thome','san-thome','Teynampet','Chennai','Tamil Nadu','600004',1,167),
(168,'Mandaveli','mandaveli','Teynampet','Chennai','Tamil Nadu','600028',1,168),
(169,'Raja Annamalai Puram','raja-annamalai-puram','Teynampet','Chennai','Tamil Nadu','600028',1,169),
(170,'RA Puram','ra-puram','Teynampet','Chennai','Tamil Nadu','600028',1,170),
(171,'Alwarpet','alwarpet','Teynampet','Chennai','Tamil Nadu','600018',1,171),
(172,'TTK Road Alwarpet','ttk-road-alwarpet','Teynampet','Chennai','Tamil Nadu','600018',1,172),
(173,'Eldams Road','eldams-road','Teynampet','Chennai','Tamil Nadu','600018',1,173),
(174,'Chamiers Road','chamiers-road','Teynampet','Chennai','Tamil Nadu','600018',1,174),
(175,'Poes Garden','poes-garden','Teynampet','Chennai','Tamil Nadu','600086',1,175),
(176,'Poes Road','poes-road','Teynampet','Chennai','Tamil Nadu','600018',1,176),
(177,'Kavignar Bharathidasan Road','kavignar-bharathidasan-road','Teynampet','Chennai','Tamil Nadu','600018',1,177),
(178,'Oliver Road','oliver-road','Teynampet','Chennai','Tamil Nadu','600004',1,178),
(179,'Kapaleeshwarar Nagar','kapaleeshwarar-nagar','Teynampet','Chennai','Tamil Nadu','600004',1,179),
(180,'Abiramapuram','abiramapuram','Teynampet','Chennai','Tamil Nadu','600018',1,180),
(181,'Kodambakkam','kodambakkam','Kodambakkam','Chennai','Tamil Nadu','600024',1,181),
(182,'T Nagar','t-nagar','Kodambakkam','Chennai','Tamil Nadu','600017',1,182),
(183,'Pondy Bazaar','pondy-bazaar','Kodambakkam','Chennai','Tamil Nadu','600017',1,183),
(184,'Panagal Park','panagal-park','Kodambakkam','Chennai','Tamil Nadu','600017',1,184),
(185,'Usman Road','usman-road','Kodambakkam','Chennai','Tamil Nadu','600017',1,185),
(186,'Gopathi Narayanaswami Chetty Road','gopathi-narayanaswami-chetty-road','Kodambakkam','Chennai','Tamil Nadu','600017',1,186),
(187,'Burkit Road','burkit-road','Kodambakkam','Chennai','Tamil Nadu','600017',1,187),
(188,'Venkatnarayana Road','venkatnarayana-road','Kodambakkam','Chennai','Tamil Nadu','600017',1,188),
(189,'West Mambalam','west-mambalam','Kodambakkam','Chennai','Tamil Nadu','600033',1,189),
(190,'Arya Gowda Road','arya-gowda-road','Kodambakkam','Chennai','Tamil Nadu','600033',1,190),
(191,'Station Road West Mambalam','station-road-west-mambalam','Kodambakkam','Chennai','Tamil Nadu','600033',1,191),
(192,'Postal Colony West Mambalam','postal-colony-west-mambalam','Kodambakkam','Chennai','Tamil Nadu','600033',1,192),
(193,'Vadapalani','vadapalani','Kodambakkam','Chennai','Tamil Nadu','600026',1,193),
(194,'100 Feet Road Vadapalani','100-feet-road-vadapalani','Kodambakkam','Chennai','Tamil Nadu','600026',1,194),
(195,'Vadapalani Murugan Temple Area','vadapalani-murugan-temple-area','Kodambakkam','Chennai','Tamil Nadu','600026',1,195),
(196,'Saligramam','saligramam','Kodambakkam','Chennai','Tamil Nadu','600093',1,196),
(197,'Kumaran Colony','kumaran-colony','Kodambakkam','Chennai','Tamil Nadu','600026',1,197),
(198,'Ashok Nagar','ashok-nagar','Kodambakkam','Chennai','Tamil Nadu','600083',1,198),
(199,'Ashok Pillar Area','ashok-pillar-area','Kodambakkam','Chennai','Tamil Nadu','600083',1,199),
(200,'1st Avenue Ashok Nagar','1st-avenue-ashok-nagar','Kodambakkam','Chennai','Tamil Nadu','600083',1,200),
(201,'4th Avenue Ashok Nagar','4th-avenue-ashok-nagar','Kodambakkam','Chennai','Tamil Nadu','600083',1,201),
(202,'KK Nagar','kk-nagar','Kodambakkam','Chennai','Tamil Nadu','600078',1,202),
(203,'Munusamy Salai','munusamy-salai','Kodambakkam','Chennai','Tamil Nadu','600078',1,203),
(204,'Ramasamy Salai','ramasamy-salai','Kodambakkam','Chennai','Tamil Nadu','600078',1,204),
(205,'Sector 1 KK Nagar','sector-1-kk-nagar','Kodambakkam','Chennai','Tamil Nadu','600078',1,205),
(206,'Sector 10 KK Nagar','sector-10-kk-nagar','Kodambakkam','Chennai','Tamil Nadu','600078',1,206),
(207,'MGR Nagar','mgr-nagar','Kodambakkam','Chennai','Tamil Nadu','600078',1,207),
(208,'Nesapakkam','nesapakkam','Kodambakkam','Chennai','Tamil Nadu','600078',1,208),
(209,'Saidapet','saidapet','Kodambakkam','Chennai','Tamil Nadu','600015',1,209),
(210,'Saidapet West','saidapet-west','Kodambakkam','Chennai','Tamil Nadu','600015',1,210),
(211,'Jones Road Saidapet','jones-road-saidapet','Kodambakkam','Chennai','Tamil Nadu','600015',1,211),
(212,'CIT Nagar','cit-nagar','Kodambakkam','Chennai','Tamil Nadu','600035',1,212),
(213,'Nandanam','nandanam','Kodambakkam','Chennai','Tamil Nadu','600035',1,213),
(214,'Chamiers Road Nandanam','chamiers-road-nandanam','Kodambakkam','Chennai','Tamil Nadu','600035',1,214),
(215,'Cenotaph Road','cenotaph-road','Kodambakkam','Chennai','Tamil Nadu','600018',1,215),
(216,'Valasaravakkam','valasaravakkam','Valasaravakkam','Chennai','Tamil Nadu','600087',1,216),
(217,'Alwarthirunagar','alwarthirunagar','Valasaravakkam','Chennai','Tamil Nadu','600087',1,217),
(218,'Virugambakkam','virugambakkam','Valasaravakkam','Chennai','Tamil Nadu','600092',1,218),
(219,'Kasi Theater Area','kasi-theater-area','Valasaravakkam','Chennai','Tamil Nadu','600083',1,219),
(220,'Chinmaya Nagar','chinmaya-nagar','Valasaravakkam','Chennai','Tamil Nadu','600092',1,220),
(221,'Natesan Nagar','natesan-nagar','Valasaravakkam','Chennai','Tamil Nadu','600092',1,221),
(222,'Porur','porur','Valasaravakkam','Chennai','Tamil Nadu','600116',1,222),
(223,'Porur Lake Area','porur-lake-area','Valasaravakkam','Chennai','Tamil Nadu','600116',1,223),
(224,'Porur Roundtana','porur-roundtana','Valasaravakkam','Chennai','Tamil Nadu','600116',1,224),
(225,'Ramapuram','ramapuram','Valasaravakkam','Chennai','Tamil Nadu','600089',1,225),
(226,'SRM Easwari College Area','srm-easwari-college-area','Valasaravakkam','Chennai','Tamil Nadu','600089',1,226),
(227,'Rayala Nagar','rayala-nagar','Valasaravakkam','Chennai','Tamil Nadu','600089',1,227),
(228,'Iyyappanthangal','iyyappanthangal','Valasaravakkam','Chennai','Tamil Nadu','600056',1,228),
(229,'Oil Mill Road Iyyappanthangal','oil-mill-road-iyyappanthangal','Valasaravakkam','Chennai','Tamil Nadu','600056',1,229),
(230,'Prestige Bella Vista Area','prestige-bella-vista-area','Valasaravakkam','Chennai','Tamil Nadu','600056',1,230),
(231,'Kattupakkam','kattupakkam','Valasaravakkam','Chennai','Tamil Nadu','600056',1,231),
(232,'Mugalivakkam','mugalivakkam','Valasaravakkam','Chennai','Tamil Nadu','600125',1,232),
(233,'Manapakkam','manapakkam','Valasaravakkam','Chennai','Tamil Nadu','600125',1,233),
(234,'L&T Infotech Manapakkam','l-t-infotech-manapakkam','Valasaravakkam','Chennai','Tamil Nadu','600125',1,234),
(235,'DLF IT Park Porur','dlf-it-park-porur','Valasaravakkam','Chennai','Tamil Nadu','600089',1,235),
(236,'Mount Poonamallee Road','mount-poonamallee-road','Valasaravakkam','Chennai','Tamil Nadu','600089',1,236),
(237,'Karasangal','karasangal','Valasaravakkam','Chennai','Tamil Nadu','601301',1,237),
(238,'Gerugambakkam','gerugambakkam','Valasaravakkam','Chennai','Tamil Nadu','600128',1,238),
(239,'Kolapakkam Porur','kolapakkam-porur','Valasaravakkam','Chennai','Tamil Nadu','600128',1,239),
(240,'Madhanandapuram','madhanandapuram','Valasaravakkam','Chennai','Tamil Nadu','600125',1,240),
(241,'Mowlivakkam','mowlivakkam','Valasaravakkam','Chennai','Tamil Nadu','600125',1,241),
(242,'Srinivasapuram','srinivasapuram','Valasaravakkam','Chennai','Tamil Nadu','600056',1,242),
(243,'Alandur','alandur','Alandur','Chennai','Tamil Nadu','600016',1,243),
(244,'Guindy','guindy','Alandur','Chennai','Tamil Nadu','600032',1,244),
(245,'Guindy Industrial Estate','guindy-industrial-estate','Alandur','Chennai','Tamil Nadu','600032',1,245),
(246,'Kathipara','kathipara','Alandur','Chennai','Tamil Nadu','600016',1,246),
(247,'Ekkatuthangal','ekkatuthangal','Alandur','Chennai','Tamil Nadu','600032',1,247),
(248,'CIPET Guindy','cipet-guindy','Alandur','Chennai','Tamil Nadu','600032',1,248),
(249,'Little Mount','little-mount','Alandur','Chennai','Tamil Nadu','600015',1,249),
(250,'Nanganallur','nanganallur','Alandur','Chennai','Tamil Nadu','600061',1,250),
(251,'Hindu Colony Nanganallur','hindu-colony-nanganallur','Alandur','Chennai','Tamil Nadu','600061',1,251),
(252,'Anjaneyar Temple Nanganallur','anjaneyar-temple-nanganallur','Alandur','Chennai','Tamil Nadu','600061',1,252),
(253,'Pazhavanthangal','pazhavanthangal','Alandur','Chennai','Tamil Nadu','600114',1,253),
(254,'Meenambakkam','meenambakkam','Alandur','Chennai','Tamil Nadu','600027',1,254),
(255,'Chennai Airport Area','chennai-airport-area','Alandur','Chennai','Tamil Nadu','600027',1,255),
(256,'Adambakkam','adambakkam','Alandur','Chennai','Tamil Nadu','600088',1,256),
(257,'Nilamangai Nagar','nilamangai-nagar','Alandur','Chennai','Tamil Nadu','600088',1,257),
(258,'Surendra Nagar','surendra-nagar','Alandur','Chennai','Tamil Nadu','600088',1,258),
(259,'Kakkan Nagar','kakkan-nagar','Alandur','Chennai','Tamil Nadu','600088',1,259),
(260,'Moovarasanpet','moovarasanpet','Alandur','Chennai','Tamil Nadu','600091',1,260),
(261,'St Thomas Mount','st-thomas-mount','Alandur','Chennai','Tamil Nadu','600016',1,261),
(262,'Butt Road St Thomas Mount','butt-road-st-thomas-mount','Alandur','Chennai','Tamil Nadu','600016',1,262),
(263,'Military Quarters Nandambakkam','military-quarters-nandambakkam','Alandur','Chennai','Tamil Nadu','600089',1,263),
(264,'Nandambakkam Trade Centre','nandambakkam-trade-centre','Alandur','Chennai','Tamil Nadu','600089',1,264),
(265,'Adyar','adyar','Adyar','Chennai','Tamil Nadu','600020',1,265),
(266,'Gandhi Nagar Adyar','gandhi-nagar-adyar','Adyar','Chennai','Tamil Nadu','600020',1,266),
(267,'Kasturba Nagar Adyar','kasturba-nagar-adyar','Adyar','Chennai','Tamil Nadu','600020',1,267),
(268,'Indira Nagar Adyar','indira-nagar-adyar','Adyar','Chennai','Tamil Nadu','600020',1,268),
(269,'Sardar Patel Road','sardar-patel-road','Adyar','Chennai','Tamil Nadu','600020',1,269),
(270,'Besant Nagar','besant-nagar','Adyar','Chennai','Tamil Nadu','600090',1,270),
(271,'Elliot Beach Besant Nagar','elliot-beach-besant-nagar','Adyar','Chennai','Tamil Nadu','600090',1,271),
(272,'Kalakshetra Colony','kalakshetra-colony','Adyar','Chennai','Tamil Nadu','600090',1,272),
(273,'Urvashi Nagar Besant Nagar','urvashi-nagar-besant-nagar','Adyar','Chennai','Tamil Nadu','600090',1,273),
(274,'Thiruvanmiyur','thiruvanmiyur','Adyar','Chennai','Tamil Nadu','600041',1,274),
(275,'Valmiki Nagar Thiruvanmiyur','valmiki-nagar-thiruvanmiyur','Adyar','Chennai','Tamil Nadu','600041',1,275),
(276,'Thiruvalluvar Nagar Thiruvanmiyur','thiruvalluvar-nagar-thiruvanmiyur','Adyar','Chennai','Tamil Nadu','600041',1,276),
(277,'Kamaraj Nagar Thiruvanmiyur','kamaraj-nagar-thiruvanmiyur','Adyar','Chennai','Tamil Nadu','600041',1,277),
(278,'RTO Office Thiruvanmiyur','rto-office-thiruvanmiyur','Adyar','Chennai','Tamil Nadu','600041',1,278),
(279,'Kotturpuram','kotturpuram','Adyar','Chennai','Tamil Nadu','600085',1,279),
(280,'Chitra Nagar Kotturpuram','chitra-nagar-kotturpuram','Adyar','Chennai','Tamil Nadu','600085',1,280),
(281,'Anna University Area','anna-university-area','Adyar','Chennai','Tamil Nadu','600025',1,281),
(282,'IIT Madras Campus','iit-madras-campus','Adyar','Chennai','Tamil Nadu','600036',1,282),
(283,'Cancer Institute Adyar','cancer-institute-adyar','Adyar','Chennai','Tamil Nadu','600020',1,283),
(284,'Malviya Avenue','malviya-avenue','Adyar','Chennai','Tamil Nadu','600041',1,284),
(285,'Lattice Bridge Road Adyar','lattice-bridge-road-adyar','Adyar','Chennai','Tamil Nadu','600020',1,285),
(286,'Velachery','velachery','Perungudi','Chennai','Tamil Nadu','600042',1,286),
(287,'Velachery Main Road','velachery-main-road','Perungudi','Chennai','Tamil Nadu','600042',1,287),
(288,'Velachery Bypass Road','velachery-bypass-road','Perungudi','Chennai','Tamil Nadu','600042',1,288),
(289,'Vijayanagar Velachery','vijayanagar-velachery','Perungudi','Chennai','Tamil Nadu','600042',1,289),
(290,'Baby Nagar Velachery','baby-nagar-velachery','Perungudi','Chennai','Tamil Nadu','600042',1,290),
(291,'Dhandeeswaram Nagar','dhandeeswaram-nagar','Perungudi','Chennai','Tamil Nadu','600042',1,291),
(292,'AGS Colony Velachery','ags-colony-velachery','Perungudi','Chennai','Tamil Nadu','600042',1,292),
(293,'Tansi Nagar','tansi-nagar','Perungudi','Chennai','Tamil Nadu','600042',1,293),
(294,'Bhuvaneshwari Nagar','bhuvaneshwari-nagar','Perungudi','Chennai','Tamil Nadu','600042',1,294),
(295,'Phoenix MarketCity Area','phoenix-marketcity-area','Perungudi','Chennai','Tamil Nadu','600042',1,295),
(296,'Madipakkam','madipakkam','Perungudi','Chennai','Tamil Nadu','600091',1,296),
(297,'Kuberan Nagar Madipakkam','kuberan-nagar-madipakkam','Perungudi','Chennai','Tamil Nadu','600091',1,297),
(298,'Balaiah Garden','balaiah-garden','Perungudi','Chennai','Tamil Nadu','600091',1,298),
(299,'Ram Nagar Madipakkam','ram-nagar-madipakkam','Perungudi','Chennai','Tamil Nadu','600091',1,299),
(300,'Puzhuthivakkam','puzhuthivakkam','Perungudi','Chennai','Tamil Nadu','600091',1,300),
(301,'Ullagaram','ullagaram','Perungudi','Chennai','Tamil Nadu','600091',1,301),
(302,'Keelkattalai','keelkattalai','Perungudi','Chennai','Tamil Nadu','600117',1,302),
(303,'Keelkattalai Junction','keelkattalai-junction','Perungudi','Chennai','Tamil Nadu','600117',1,303),
(304,'Perungudi','perungudi','Perungudi','Chennai','Tamil Nadu','600096',1,304),
(305,'Perungudi Industrial Estate','perungudi-industrial-estate','Perungudi','Chennai','Tamil Nadu','600096',1,305),
(306,'Kallukuttai','kallukuttai','Perungudi','Chennai','Tamil Nadu','600096',1,306),
(307,'Seevaram Perungudi','seevaram-perungudi','Perungudi','Chennai','Tamil Nadu','600096',1,307),
(308,'Kandanchavadi','kandanchavadi','Perungudi','Chennai','Tamil Nadu','600096',1,308),
(309,'Kandanchavadi MGR Salai','kandanchavadi-mgr-salai','Perungudi','Chennai','Tamil Nadu','600096',1,309),
(310,'Taramani','taramani','Perungudi','Chennai','Tamil Nadu','600113',1,310),
(311,'TIDEL Park Area','tidel-park-area','Perungudi','Chennai','Tamil Nadu','600113',1,311),
(312,'Ascendas IT Park Area','ascendas-it-park-area','Perungudi','Chennai','Tamil Nadu','600113',1,312),
(313,'SRP Tools OMR','srp-tools-omr','Perungudi','Chennai','Tamil Nadu','600041',1,313),
(314,'Palavanthangal Lake Area','palavanthangal-lake-area','Perungudi','Chennai','Tamil Nadu','600114',1,314),
(315,'Sholinganallur','sholinganallur','Sholinganallur','Chennai','Tamil Nadu','600119',1,315),
(316,'Sholinganallur Junction','sholinganallur-junction','Sholinganallur','Chennai','Tamil Nadu','600119',1,316),
(317,'Classic Farms Sholinganallur','classic-farms-sholinganallur','Sholinganallur','Chennai','Tamil Nadu','600119',1,317),
(318,'ELCOT SEZ Sholinganallur','elcot-sez-sholinganallur','Sholinganallur','Chennai','Tamil Nadu','600119',1,318),
(319,'Thoraipakkam','thoraipakkam','Sholinganallur','Chennai','Tamil Nadu','600097',1,319),
(320,'Okkiyam Thoraipakkam','okkiyam-thoraipakkam','Sholinganallur','Chennai','Tamil Nadu','600097',1,320),
(321,'Annanagar Thoraipakkam','annanagar-thoraipakkam','Sholinganallur','Chennai','Tamil Nadu','600097',1,321),
(322,'Mettukuppam OMR','mettukuppam-omr','Sholinganallur','Chennai','Tamil Nadu','600097',1,322),
(323,'Karapakkam','karapakkam','Sholinganallur','Chennai','Tamil Nadu','600097',1,323),
(324,'TCS Karapakkam Area','tcs-karapakkam-area','Sholinganallur','Chennai','Tamil Nadu','600097',1,324),
(325,'Semmancheri','semmancheri','Sholinganallur','Chennai','Tamil Nadu','600119',1,325),
(326,'Sathyabama University Area','sathyabama-university-area','Sholinganallur','Chennai','Tamil Nadu','600119',1,326),
(327,'Thalambur','thalambur','Sholinganallur','Chennai','Tamil Nadu','600130',1,327),
(328,'Navalur','navalur','Sholinganallur','Chennai','Tamil Nadu','600130',1,328),
(329,'Marina Mall Area Navalur','marina-mall-area-navalur','Sholinganallur','Chennai','Tamil Nadu','600130',1,329),
(330,'Egattur','egattur','Sholinganallur','Chennai','Tamil Nadu','603103',1,330),
(331,'Hiranandani Upscale Egattur','hiranandani-upscale-egattur','Sholinganallur','Chennai','Tamil Nadu','603103',1,331),
(332,'Siruseri','siruseri','Sholinganallur','Chennai','Tamil Nadu','603103',1,332),
(333,'SIPCOT IT Park Siruseri','sipcot-it-park-siruseri','Sholinganallur','Chennai','Tamil Nadu','603103',1,333),
(334,'Kazhipattur','kazhipattur','Sholinganallur','Chennai','Tamil Nadu','603103',1,334),
(335,'Padur','padur','Sholinganallur','Chennai','Tamil Nadu','603103',1,335),
(336,'Kelambakkam','kelambakkam','Sholinganallur','Chennai','Tamil Nadu','603103',1,336),
(337,'Kelambakkam Junction','kelambakkam-junction','Sholinganallur','Chennai','Tamil Nadu','603103',1,337),
(338,'Thaiyur','thaiyur','Sholinganallur','Chennai','Tamil Nadu','603103',1,338),
(339,'Thiruporur','thiruporur','Sholinganallur','Chennai','Tamil Nadu','603110',1,339),
(340,'Alathur','alathur','Sholinganallur','Chennai','Tamil Nadu','603110',1,340),
(341,'Illalur','illalur','Sholinganallur','Chennai','Tamil Nadu','603110',1,341),
(342,'Kovalam Road Kelambakkam','kovalam-road-kelambakkam','Sholinganallur','Chennai','Tamil Nadu','603103',1,342),
(343,'Pudupakkam','pudupakkam','Sholinganallur','Chennai','Tamil Nadu','603103',1,343),
(344,'Vandalur Kelambakkam Road','vandalur-kelambakkam-road','Sholinganallur','Chennai','Tamil Nadu','603103',1,344),
(345,'Kottivakkam','kottivakkam','ECR','Chennai','Tamil Nadu','600041',1,345),
(346,'Kottivakkam Beach Road','kottivakkam-beach-road','ECR','Chennai','Tamil Nadu','600041',1,346),
(347,'Palavakkam','palavakkam','ECR','Chennai','Tamil Nadu','600041',1,347),
(348,'Palavakkam Beach','palavakkam-beach','ECR','Chennai','Tamil Nadu','600041',1,348),
(349,'Neelankarai','neelankarai','ECR','Chennai','Tamil Nadu','600115',1,349),
(350,'Kapaleeswarar Nagar Neelankarai','kapaleeswarar-nagar-neelankarai','ECR','Chennai','Tamil Nadu','600115',1,350),
(351,'Worker Colony Neelankarai','worker-colony-neelankarai','ECR','Chennai','Tamil Nadu','600115',1,351),
(352,'Injambakkam','injambakkam','ECR','Chennai','Tamil Nadu','600115',1,352),
(353,'Prarthana Beach Injambakkam','prarthana-beach-injambakkam','ECR','Chennai','Tamil Nadu','600115',1,353),
(354,'VGP Golden Beach Area','vgp-golden-beach-area','ECR','Chennai','Tamil Nadu','600115',1,354),
(355,'Akkarai','akkarai','ECR','Chennai','Tamil Nadu','600119',1,355),
(356,'Akkarai Beach','akkarai-beach','ECR','Chennai','Tamil Nadu','600119',1,356),
(357,'Panaiyur','panaiyur','ECR','Chennai','Tamil Nadu','600119',1,357),
(358,'Uthandi','uthandi','ECR','Chennai','Tamil Nadu','600119',1,358),
(359,'Uthandi Tollgate','uthandi-tollgate','ECR','Chennai','Tamil Nadu','600119',1,359),
(360,'Kanathur','kanathur','ECR','Chennai','Tamil Nadu','603112',1,360),
(361,'Muttukadu','muttukadu','ECR','Chennai','Tamil Nadu','603112',1,361),
(362,'Muttukadu Boat House','muttukadu-boat-house','ECR','Chennai','Tamil Nadu','603112',1,362),
(363,'Kovalam ECR','kovalam-ecr','ECR','Chennai','Tamil Nadu','603112',1,363),
(364,'Nemmeli','nemmeli','ECR','Chennai','Tamil Nadu','603104',1,364),
(365,'Pattipulam','pattipulam','ECR','Chennai','Tamil Nadu','603104',1,365),
(366,'Salavankuppam','salavankuppam','ECR','Chennai','Tamil Nadu','603104',1,366),
(367,'Mahabalipuram','mahabalipuram','ECR','Chennai','Tamil Nadu','603104',1,367),
(368,'Mamallapuram','mamallapuram','ECR','Chennai','Tamil Nadu','603104',1,368),
(369,'Poonjeri','poonjeri','ECR','Chennai','Tamil Nadu','603104',1,369),
(370,'Tambaram','tambaram','Tambaram','Chennai','Tamil Nadu','600045',1,370),
(371,'Tambaram West','tambaram-west','Tambaram','Chennai','Tamil Nadu','600045',1,371),
(372,'Tambaram East','tambaram-east','Tambaram','Chennai','Tamil Nadu','600059',1,372),
(373,'Tambaram Sanatorium','tambaram-sanatorium','Tambaram','Chennai','Tamil Nadu','600047',1,373),
(374,'Chromepet','chromepet','Tambaram','Chennai','Tamil Nadu','600044',1,374),
(375,'Radha Nagar Chromepet','radha-nagar-chromepet','Tambaram','Chennai','Tamil Nadu','600044',1,375),
(376,'Hasthinapuram','hasthinapuram','Tambaram','Chennai','Tamil Nadu','600064',1,376),
(377,'Nemilichery Chromepet','nemilichery-chromepet','Tambaram','Chennai','Tamil Nadu','600044',1,377),
(378,'Pallavaram','pallavaram','Tambaram','Chennai','Tamil Nadu','600043',1,378),
(379,'East Pallavaram','east-pallavaram','Tambaram','Chennai','Tamil Nadu','600043',1,379),
(380,'Old Pallavaram','old-pallavaram','Tambaram','Chennai','Tamil Nadu','600117',1,380),
(381,'Zamin Pallavaram','zamin-pallavaram','Tambaram','Chennai','Tamil Nadu','600043',1,381),
(382,'Cantonment Pallavaram','cantonment-pallavaram','Tambaram','Chennai','Tamil Nadu','600043',1,382),
(383,'Pammal','pammal','Tambaram','Chennai','Tamil Nadu','600075',1,383),
(384,'Anakaputhur','anakaputhur','Tambaram','Chennai','Tamil Nadu','600070',1,384),
(385,'Pozhichalur','pozhichalur','Tambaram','Chennai','Tamil Nadu','600074',1,385),
(386,'Cowl Bazaar','cowl-bazaar','Tambaram','Chennai','Tamil Nadu','600074',1,386),
(387,'Tirusulam','tirusulam','Tambaram','Chennai','Tamil Nadu','600043',1,387),
(388,'Chitlapakkam','chitlapakkam','Tambaram','Chennai','Tamil Nadu','600064',1,388),
(389,'Chitlapakkam Lake Area','chitlapakkam-lake-area','Tambaram','Chennai','Tamil Nadu','600064',1,389),
(390,'Selaiyur','selaiyur','Tambaram','Chennai','Tamil Nadu','600073',1,390),
(391,'Camp Road Selaiyur','camp-road-selaiyur','Tambaram','Chennai','Tamil Nadu','600073',1,391),
(392,'Rajakilpakkam','rajakilpakkam','Tambaram','Chennai','Tamil Nadu','600073',1,392),
(393,'Sembakkam','sembakkam','Tambaram','Chennai','Tamil Nadu','600073',1,393),
(394,'Gowrivakkam','gowrivakkam','Tambaram','Chennai','Tamil Nadu','600073',1,394),
(395,'Medavakkam','medavakkam','Tambaram','Chennai','Tamil Nadu','600100',1,395),
(396,'Medavakkam Koot Road','medavakkam-koot-road','Tambaram','Chennai','Tamil Nadu','600100',1,396),
(397,'Vengaivasal','vengaivasal','Tambaram','Chennai','Tamil Nadu','600126',1,397),
(398,'Madambakkam','madambakkam','Tambaram','Chennai','Tamil Nadu','600126',1,398),
(399,'Perumbakkam','perumbakkam','Tambaram','Chennai','Tamil Nadu','600100',1,399),
(400,'Global Hospital Perumbakkam','global-hospital-perumbakkam','Tambaram','Chennai','Tamil Nadu','600100',1,400),
(401,'Sithalapakkam','sithalapakkam','Tambaram','Chennai','Tamil Nadu','600126',1,401),
(402,'Ottiyambakkam','ottiyambakkam','Tambaram','Chennai','Tamil Nadu','600130',1,402),
(403,'Jalladianpet','jalladianpet','Tambaram','Chennai','Tamil Nadu','600100',1,403),
(404,'Kovilambakkam','kovilambakkam','Tambaram','Chennai','Tamil Nadu','600129',1,404),
(405,'Nanmangalam','nanmangalam','Tambaram','Chennai','Tamil Nadu','600129',1,405),
(406,'Nemilichery Medavakkam','nemilichery-medavakkam','Tambaram','Chennai','Tamil Nadu','600100',1,406),
(407,'Santhosapuram','santhosapuram','Tambaram','Chennai','Tamil Nadu','600073',1,407),
(408,'Mambakkam Tambaram','mambakkam-tambaram','Tambaram','Chennai','Tamil Nadu','600127',1,408),
(409,'Ponmar','ponmar','Tambaram','Chennai','Tamil Nadu','600127',1,409),
(410,'Polachery','polachery','Tambaram','Chennai','Tamil Nadu','600127',1,410),
(411,'Vengambakkam','vengambakkam','Tambaram','Chennai','Tamil Nadu','600127',1,411),
(412,'Kandigai','kandigai','Tambaram','Chennai','Tamil Nadu','600127',1,412),
(413,'Rathinamangalam','rathinamangalam','Tambaram','Chennai','Tamil Nadu','600127',1,413),
(414,'Tagore Engineering College Area','tagore-engineering-college-area','Tambaram','Chennai','Tamil Nadu','600127',1,414),
(415,'Perungalathur','perungalathur','GST Road','Chennai','Tamil Nadu','600063',1,415),
(416,'New Perungalathur','new-perungalathur','GST Road','Chennai','Tamil Nadu','600063',1,416),
(417,'Old Perungalathur','old-perungalathur','GST Road','Chennai','Tamil Nadu','600063',1,417),
(418,'Peerkankaranai','peerkankaranai','GST Road','Chennai','Tamil Nadu','600063',1,418),
(419,'Mudichur','mudichur','GST Road','Chennai','Tamil Nadu','600048',1,419),
(420,'Varadharajapuram Mudichur','varadharajapuram-mudichur','GST Road','Chennai','Tamil Nadu','600048',1,420),
(421,'Mannivakkam','mannivakkam','GST Road','Chennai','Tamil Nadu','600048',1,421),
(422,'Vandalur','vandalur','GST Road','Chennai','Tamil Nadu','600048',1,422),
(423,'Vandalur Zoo Area','vandalur-zoo-area','GST Road','Chennai','Tamil Nadu','600048',1,423),
(424,'Kilambakkam','kilambakkam','GST Road','Chennai','Tamil Nadu','600048',1,424),
(425,'KCBT Kilambakkam Bus Terminus','kcbt-kilambakkam-bus-terminus','GST Road','Chennai','Tamil Nadu','600048',1,425),
(426,'Urapakkam','urapakkam','GST Road','Chennai','Tamil Nadu','603210',1,426),
(427,'Urapakkam West','urapakkam-west','GST Road','Chennai','Tamil Nadu','603210',1,427),
(428,'Urapakkam East','urapakkam-east','GST Road','Chennai','Tamil Nadu','603210',1,428),
(429,'Guduvanchery','guduvanchery','GST Road','Chennai','Tamil Nadu','603202',1,429),
(430,'Nandivaram Guduvanchery','nandivaram-guduvanchery','GST Road','Chennai','Tamil Nadu','603202',1,430),
(431,'Thailavaram','thailavaram','GST Road','Chennai','Tamil Nadu','603203',1,431),
(432,'Potheri','potheri','GST Road','Chennai','Tamil Nadu','603203',1,432),
(433,'SRM University Potheri','srm-university-potheri','GST Road','Chennai','Tamil Nadu','603203',1,433),
(434,'Kattankulathur','kattankulathur','GST Road','Chennai','Tamil Nadu','603203',1,434),
(435,'Maraimalai Nagar','maraimalai-nagar','GST Road','Chennai','Tamil Nadu','603209',1,435),
(436,'Ford Plant Area Maraimalai Nagar','ford-plant-area-maraimalai-nagar','GST Road','Chennai','Tamil Nadu','603209',1,436),
(437,'Mahindra World City','mahindra-world-city','GST Road','Chennai','Tamil Nadu','603002',1,437),
(438,'Paranur','paranur','GST Road','Chennai','Tamil Nadu','603002',1,438),
(439,'Singaperumal Koil','singaperumal-koil','GST Road','Chennai','Tamil Nadu','603204',1,439),
(440,'Appur','appur','GST Road','Chennai','Tamil Nadu','603204',1,440),
(441,'Chettipunyam','chettipunyam','GST Road','Chennai','Tamil Nadu','603204',1,441),
(442,'Chengalpattu','chengalpattu','GST Road','Chennai','Tamil Nadu','603001',1,442),
(443,'Chengalpattu Town','chengalpattu-town','GST Road','Chennai','Tamil Nadu','603001',1,443),
(444,'Gundu Medu Chengalpattu','gundu-medu-chengalpattu','GST Road','Chennai','Tamil Nadu','603001',1,444),
(445,'Melamaiyur','melamaiyur','GST Road','Chennai','Tamil Nadu','603001',1,445),
(446,'Thimmavaram','thimmavaram','GST Road','Chennai','Tamil Nadu','603101',1,446),
(447,'Alapakkam Chengalpattu','alapakkam-chengalpattu','GST Road','Chennai','Tamil Nadu','603003',1,447),
(448,'Poonamallee','poonamallee','West Corridor','Chennai','Tamil Nadu','600056',1,448),
(449,'Poonamallee Bus Terminus','poonamallee-bus-terminus','West Corridor','Chennai','Tamil Nadu','600056',1,449),
(450,'Kumananchavadi','kumananchavadi','West Corridor','Chennai','Tamil Nadu','600056',1,450),
(451,'Mangadu','mangadu','West Corridor','Chennai','Tamil Nadu','600122',1,451),
(452,'Mangadu Temple Area','mangadu-temple-area','West Corridor','Chennai','Tamil Nadu','600122',1,452),
(453,'Senneerkuppam','senneerkuppam','West Corridor','Chennai','Tamil Nadu','600056',1,453),
(454,'Nazarathpet','nazarathpet','West Corridor','Chennai','Tamil Nadu','600123',1,454),
(455,'Varadharajapuram Poonamallee','varadharajapuram-poonamallee','West Corridor','Chennai','Tamil Nadu','600123',1,455),
(456,'Thirumazhisai','thirumazhisai','West Corridor','Chennai','Tamil Nadu','600124',1,456),
(457,'Thirumazhisai Satellite Town','thirumazhisai-satellite-town','West Corridor','Chennai','Tamil Nadu','600124',1,457),
(458,'Chembarambakkam','chembarambakkam','West Corridor','Chennai','Tamil Nadu','600123',1,458),
(459,'Chembarambakkam Lake Area','chembarambakkam-lake-area','West Corridor','Chennai','Tamil Nadu','600123',1,459),
(460,'Irungattukottai','irungattukottai','West Corridor','Chennai','Tamil Nadu','602117',1,460),
(461,'SIPCOT Irungattukottai','sipcot-irungattukottai','West Corridor','Chennai','Tamil Nadu','602117',1,461),
(462,'Thandalam','thandalam','West Corridor','Chennai','Tamil Nadu','602105',1,462),
(463,'Saveetha University Area Thandalam','saveetha-university-area-thandalam','West Corridor','Chennai','Tamil Nadu','602105',1,463),
(464,'Nemam','nemam','West Corridor','Chennai','Tamil Nadu','600124',1,464),
(465,'Pennalur','pennalur','West Corridor','Chennai','Tamil Nadu','602117',1,465),
(466,'Sriperumbudur','sriperumbudur','West Corridor','Chennai','Tamil Nadu','602105',1,466),
(467,'Sriperumbudur SIPCOT','sriperumbudur-sipcot','West Corridor','Chennai','Tamil Nadu','602105',1,467),
(468,'Rajiv Gandhi Memorial Area','rajiv-gandhi-memorial-area','West Corridor','Chennai','Tamil Nadu','602105',1,468),
(469,'Sunguvarchatram','sunguvarchatram','West Corridor','Chennai','Tamil Nadu','602106',1,469),
(470,'Mambakkam Sriperumbudur','mambakkam-sriperumbudur','West Corridor','Chennai','Tamil Nadu','602105',1,470),
(471,'Vallam Vadagal','vallam-vadagal','West Corridor','Chennai','Tamil Nadu','602105',1,471),
(472,'Oragadam','oragadam','West Corridor','Chennai','Tamil Nadu','602105',1,472),
(473,'Oragadam Industrial Corridor','oragadam-industrial-corridor','West Corridor','Chennai','Tamil Nadu','602105',1,473),
(474,'Padappai','padappai','West Corridor','Chennai','Tamil Nadu','601301',1,474),
(475,'Salamangalam','salamangalam','West Corridor','Chennai','Tamil Nadu','601301',1,475),
(476,'Manimangalam','manimangalam','West Corridor','Chennai','Tamil Nadu','601301',1,476),
(477,'Somangalam','somangalam','West Corridor','Chennai','Tamil Nadu','602109',1,477),
(478,'Karanithangal','karanithangal','West Corridor','Chennai','Tamil Nadu','602105',1,478),
(479,'Kundrathur','kundrathur','West Corridor','Chennai','Tamil Nadu','600069',1,479),
(480,'Kundrathur Murugan Temple Area','kundrathur-murugan-temple-area','West Corridor','Chennai','Tamil Nadu','600069',1,480),
(481,'Mehta Nagar Kundrathur','mehta-nagar-kundrathur','West Corridor','Chennai','Tamil Nadu','600069',1,481),
(482,'Anakaputhur West','anakaputhur-west','West Corridor','Chennai','Tamil Nadu','600070',1,482),
(483,'Thiruneermalai','thiruneermalai','West Corridor','Chennai','Tamil Nadu','600044',1,483),
(484,'Thiruneermalai Temple Area','thiruneermalai-temple-area','West Corridor','Chennai','Tamil Nadu','600044',1,484),
(485,'Thandalam Kundrathur','thandalam-kundrathur','West Corridor','Chennai','Tamil Nadu','600069',1,485),
(486,'Kovur','kovur','West Corridor','Chennai','Tamil Nadu','600128',1,486),
(487,'Chikkarayapuram','chikkarayapuram','West Corridor','Chennai','Tamil Nadu','600069',1,487),
(488,'Avadi','avadi','Avadi','Chennai','Tamil Nadu','600054',1,488),
(489,'Avadi Checkpost','avadi-checkpost','Avadi','Chennai','Tamil Nadu','600054',1,489),
(490,'HVF Estate Avadi','hvf-estate-avadi','Avadi','Chennai','Tamil Nadu','600054',1,490),
(491,'IAF Avadi','iaf-avadi','Avadi','Chennai','Tamil Nadu','600055',1,491),
(492,'OCF Avadi','ocf-avadi','Avadi','Chennai','Tamil Nadu','600054',1,492),
(493,'CRPF Camp Avadi','crpf-camp-avadi','Avadi','Chennai','Tamil Nadu','600065',1,493),
(494,'Pattabiram','pattabiram','Avadi','Chennai','Tamil Nadu','600072',1,494),
(495,'TIDEL Park Pattabiram','tidel-park-pattabiram','Avadi','Chennai','Tamil Nadu','600072',1,495),
(496,'Thiruninravur','thiruninravur','Avadi','Chennai','Tamil Nadu','602024',1,496),
(497,'Thiruninravur Lake Area','thiruninravur-lake-area','Avadi','Chennai','Tamil Nadu','602024',1,497),
(498,'Mittanamallee','mittanamallee','Avadi','Chennai','Tamil Nadu','600055',1,498),
(499,'Morai','morai','Avadi','Chennai','Tamil Nadu','600055',1,499),
(500,'Veerapuram','veerapuram','Avadi','Chennai','Tamil Nadu','600055',1,500),
(501,'Kovilpathu','kovilpathu','Avadi','Chennai','Tamil Nadu','600062',1,501),
(502,'Thirumullaivoyal','thirumullaivoyal','Avadi','Chennai','Tamil Nadu','600062',1,502),
(503,'Thirumullaivoyal Women Industrial Estate','thirumullaivoyal-women-industrial-estate','Avadi','Chennai','Tamil Nadu','600062',1,503),
(504,'Pachaiamman Nagar','pachaiamman-nagar','Avadi','Chennai','Tamil Nadu','600062',1,504),
(505,'Senthil Nagar Thirumullaivoyal','senthil-nagar-thirumullaivoyal','Avadi','Chennai','Tamil Nadu','600062',1,505),
(506,'Vellanur','vellanur','Avadi','Chennai','Tamil Nadu','600062',1,506),
(507,'Pothur','pothur','Avadi','Chennai','Tamil Nadu','600062',1,507),
(508,'Alamathi','alamathi','Avadi','Chennai','Tamil Nadu','600052',1,508),
(509,'Sholavaram','sholavaram','Avadi','Chennai','Tamil Nadu','600067',1,509),
(510,'Karanodai','karanodai','Avadi','Chennai','Tamil Nadu','600067',1,510),
(511,'Janapachatram','janapachatram','Avadi','Chennai','Tamil Nadu','600067',1,511),
(512,'Minjur','minjur','Avadi','Chennai','Tamil Nadu','601203',1,512),
(513,'Ponneri','ponneri','Avadi','Chennai','Tamil Nadu','601204',1,513),
(514,'Gummudipoondi','gummudipoondi','Avadi','Chennai','Tamil Nadu','601201',1,514),
(515,'Kavaraipettai','kavaraipettai','Avadi','Chennai','Tamil Nadu','601206',1,515),
(516,'Thiruvallur','thiruvallur','Avadi','Chennai','Tamil Nadu','602001',1,516),
(517,'Thiruvallur Town','thiruvallur-town','Avadi','Chennai','Tamil Nadu','602001',1,517),
(518,'Veeraraghavaswamy Temple Area','veeraraghavaswamy-temple-area','Avadi','Chennai','Tamil Nadu','602001',1,518),
(519,'Manavala Nagar','manavala-nagar','Avadi','Chennai','Tamil Nadu','602002',1,519),
(520,'Kakkalur','kakkalur','Avadi','Chennai','Tamil Nadu','602003',1,520),
(521,'Kakkalur Industrial Estate','kakkalur-industrial-estate','Avadi','Chennai','Tamil Nadu','602003',1,521),
(522,'Putlur','putlur','Avadi','Chennai','Tamil Nadu','602025',1,522),
(523,'Sevvapet','sevvapet','Avadi','Chennai','Tamil Nadu','602025',1,523),
(524,'Vepampattu','vepampattu','Avadi','Chennai','Tamil Nadu','602024',1,524),
(525,'Perumalpattu','perumalpattu','Avadi','Chennai','Tamil Nadu','602024',1,525),
(526,'Nemilichery Avadi','nemilichery-avadi','Avadi','Chennai','Tamil Nadu','602024',1,526),
(527,'Besant Avenue Adyar','besant-avenue-adyar','Adyar','Chennai','Tamil Nadu','600020',1,527),
(528,'Padmanabha Nagar Adyar','padmanabha-nagar-adyar','Adyar','Chennai','Tamil Nadu','600020',1,528),
(529,'Karpagam Gardens Adyar','karpagam-gardens-adyar','Adyar','Chennai','Tamil Nadu','600020',1,529),
(530,'Sastri Nagar Adyar','sastri-nagar-adyar','Adyar','Chennai','Tamil Nadu','600020',1,530),
(531,'Damodarapuram Adyar','damodarapuram-adyar','Adyar','Chennai','Tamil Nadu','600020',1,531),
(532,'Nehru Nagar Adyar','nehru-nagar-adyar','Adyar','Chennai','Tamil Nadu','600020',1,532),
(533,'Venkatarathinam Nagar Adyar','venkatarathinam-nagar-adyar','Adyar','Chennai','Tamil Nadu','600020',1,533),
(534,'Indira Nagar 1st Avenue','indira-nagar-1st-avenue','Adyar','Chennai','Tamil Nadu','600020',1,534),
(535,'Indira Nagar Water Tank Area','indira-nagar-water-tank-area','Adyar','Chennai','Tamil Nadu','600020',1,535),
(536,'Customs Colony Besant Nagar','customs-colony-besant-nagar','Adyar','Chennai','Tamil Nadu','600090',1,536),
(537,'CPWD Quarters Besant Nagar','cpwd-quarters-besant-nagar','Adyar','Chennai','Tamil Nadu','600090',1,537),
(538,'Tiger Varadachari Road','tiger-varadachari-road','Adyar','Chennai','Tamil Nadu','600090',1,538),
(539,'Rukmini Road Besant Nagar','rukmini-road-besant-nagar','Adyar','Chennai','Tamil Nadu','600090',1,539),
(540,'Arunachalam Avenue Thiruvanmiyur','arunachalam-avenue-thiruvanmiyur','Adyar','Chennai','Tamil Nadu','600041',1,540),
(541,'South Mada Street Thiruvanmiyur','south-mada-street-thiruvanmiyur','Adyar','Chennai','Tamil Nadu','600041',1,541),
(542,'Marundeeswarar Temple Area','marundeeswarar-temple-area','Adyar','Chennai','Tamil Nadu','600041',1,542),
(543,'Seaward Road Thiruvanmiyur','seaward-road-thiruvanmiyur','Adyar','Chennai','Tamil Nadu','600041',1,543),
(544,'Radhakrishnan Nagar Thiruvanmiyur','radhakrishnan-nagar-thiruvanmiyur','Adyar','Chennai','Tamil Nadu','600041',1,544),
(545,'Kalaivanar Nagar Thiruvanmiyur','kalaivanar-nagar-thiruvanmiyur','Adyar','Chennai','Tamil Nadu','600041',1,545),
(546,'Brodies Road RA Puram','brodies-road-ra-puram','Teynampet','Chennai','Tamil Nadu','600028',1,546),
(547,'Greenways Road','greenways-road','Teynampet','Chennai','Tamil Nadu','600028',1,547),
(548,'Bishop Garden RA Puram','bishop-garden-ra-puram','Teynampet','Chennai','Tamil Nadu','600028',1,548),
(549,'Karpagam Avenue RA Puram','karpagam-avenue-ra-puram','Teynampet','Chennai','Tamil Nadu','600028',1,549),
(550,'De Silva Road Mylapore','de-silva-road-mylapore','Teynampet','Chennai','Tamil Nadu','600004',1,550),
(551,'Kutchery Road Mylapore','kutchery-road-mylapore','Teynampet','Chennai','Tamil Nadu','600004',1,551),
(552,'Mundakakanni Amman Koil','mundakakanni-amman-koil','Teynampet','Chennai','Tamil Nadu','600004',1,552),
(553,'Luz Church Road','luz-church-road','Teynampet','Chennai','Tamil Nadu','600004',1,553),
(554,'Alamelumangapuram','alamelumangapuram','Teynampet','Chennai','Tamil Nadu','600004',1,554),
(555,'Pelathope Mylapore','pelathope-mylapore','Teynampet','Chennai','Tamil Nadu','600004',1,555),
(556,'Venkatesa Agraharam','venkatesa-agraharam','Teynampet','Chennai','Tamil Nadu','600004',1,556),
(557,'CIT Colony Mylapore','cit-colony-mylapore','Teynampet','Chennai','Tamil Nadu','600004',1,557),
(558,'Nageswara Rao Park Area','nageswara-rao-park-area','Teynampet','Chennai','Tamil Nadu','600004',1,558),
(559,'Warren Road Mylapore','warren-road-mylapore','Teynampet','Chennai','Tamil Nadu','600004',1,559),
(560,'Bheemanna Garden Alwarpet','bheemanna-garden-alwarpet','Teynampet','Chennai','Tamil Nadu','600018',1,560),
(561,'Sriman Srinivasa Road Alwarpet','sriman-srinivasa-road-alwarpet','Teynampet','Chennai','Tamil Nadu','600018',1,561),
(562,'Co-operative Colony Alwarpet','co-operative-colony-alwarpet','Teynampet','Chennai','Tamil Nadu','600018',1,562),
(563,'Seethammal Colony','seethammal-colony','Teynampet','Chennai','Tamil Nadu','600018',1,563),
(564,'Seethammal Extension','seethammal-extension','Teynampet','Chennai','Tamil Nadu','600018',1,564),
(565,'Teynampet Signal Area','teynampet-signal-area','Teynampet','Chennai','Tamil Nadu','600018',1,565),
(566,'SIET College Area','siet-college-area','Teynampet','Chennai','Tamil Nadu','600018',1,566),
(567,'Cathedral Road','cathedral-road','Teynampet','Chennai','Tamil Nadu','600086',1,567),
(568,'Stella Maris College Area','stella-maris-college-area','Teynampet','Chennai','Tamil Nadu','600086',1,568),
(569,'Binny Road','binny-road','Teynampet','Chennai','Tamil Nadu','600006',1,569),
(570,'Peshwar Road','peshwar-road','Teynampet','Chennai','Tamil Nadu','600006',1,570),
(571,'Spurtank Road Chetpet','spurtank-road-chetpet','Anna Nagar','Chennai','Tamil Nadu','600031',1,571),
(572,'Mayor Ramanathan Salai','mayor-ramanathan-salai','Anna Nagar','Chennai','Tamil Nadu','600031',1,572),
(573,'Ormes Road Kilpauk','ormes-road-kilpauk','Anna Nagar','Chennai','Tamil Nadu','600010',1,573),
(574,'Poonamallee High Road Kilpauk','poonamallee-high-road-kilpauk','Anna Nagar','Chennai','Tamil Nadu','600010',1,574),
(575,'Balfour Road Kilpauk','balfour-road-kilpauk','Anna Nagar','Chennai','Tamil Nadu','600010',1,575),
(576,'Landons Road Kilpauk','landons-road-kilpauk','Anna Nagar','Chennai','Tamil Nadu','600010',1,576),
(577,'Halls Road Egmore','halls-road-egmore','Anna Nagar','Chennai','Tamil Nadu','600008',1,577),
(578,'Pantheon Road Egmore','pantheon-road-egmore','Anna Nagar','Chennai','Tamil Nadu','600008',1,578),
(579,'Gandhi Irwin Road Egmore','gandhi-irwin-road-egmore','Anna Nagar','Chennai','Tamil Nadu','600008',1,579),
(580,'Egmore Railway Station Area','egmore-railway-station-area','Anna Nagar','Chennai','Tamil Nadu','600008',1,580),
(581,'Rajarathinam Stadium Area','rajarathinam-stadium-area','Anna Nagar','Chennai','Tamil Nadu','600008',1,581),
(582,'Police Commissioner Road Egmore','police-commissioner-road-egmore','Anna Nagar','Chennai','Tamil Nadu','600008',1,582),
(583,'Whannels Road Egmore','whannels-road-egmore','Anna Nagar','Chennai','Tamil Nadu','600008',1,583),
(584,'Montieth Road Egmore','montieth-road-egmore','Anna Nagar','Chennai','Tamil Nadu','600008',1,584),
(585,'Commander-in-Chief Road','commander-in-chief-road','Anna Nagar','Chennai','Tamil Nadu','600105',1,585),
(586,'Ethiraj Salai','ethiraj-salai','Anna Nagar','Chennai','Tamil Nadu','600008',1,586),
(587,'Chamiers Road Nandanam Extension','chamiers-road-nandanam-extension','Kodambakkam','Chennai','Tamil Nadu','600035',1,587),
(588,'Turnbulls Road Nandanam','turnbulls-road-nandanam','Kodambakkam','Chennai','Tamil Nadu','600035',1,588),
(589,'CIT Nagar East','cit-nagar-east','Kodambakkam','Chennai','Tamil Nadu','600035',1,589),
(590,'CIT Nagar West','cit-nagar-west','Kodambakkam','Chennai','Tamil Nadu','600035',1,590),
(591,'South Boag Road','south-boag-road','Kodambakkam','Chennai','Tamil Nadu','600017',1,591),
(592,'North Boag Road','north-boag-road','Kodambakkam','Chennai','Tamil Nadu','600017',1,592),
(593,'Habibullah Road T Nagar','habibullah-road-t-nagar','Kodambakkam','Chennai','Tamil Nadu','600017',1,593),
(594,'Vidyodaya 1st Cross Street','vidyodaya-1st-cross-street','Kodambakkam','Chennai','Tamil Nadu','600017',1,594),
(595,'Raghavaiah Road T Nagar','raghavaiah-road-t-nagar','Kodambakkam','Chennai','Tamil Nadu','600017',1,595),
(596,'Bazullah Road T Nagar','bazullah-road-t-nagar','Kodambakkam','Chennai','Tamil Nadu','600017',1,596),
(597,'Venkatanarayana Road T Nagar','venkatanarayana-road-t-nagar','Kodambakkam','Chennai','Tamil Nadu','600017',1,597),
(598,'Mangesh Street T Nagar','mangesh-street-t-nagar','Kodambakkam','Chennai','Tamil Nadu','600017',1,598),
(599,'Madley Road T Nagar','madley-road-t-nagar','Kodambakkam','Chennai','Tamil Nadu','600017',1,599),
(600,'Doraiswamy Subway Area','doraiswamy-subway-area','Kodambakkam','Chennai','Tamil Nadu','600017',1,600),
(601,'Lake View Road West Mambalam','lake-view-road-west-mambalam','Kodambakkam','Chennai','Tamil Nadu','600033',1,601),
(602,'Govindan Road West Mambalam','govindan-road-west-mambalam','Kodambakkam','Chennai','Tamil Nadu','600033',1,602),
(603,'Thambiah Road West Mambalam','thambiah-road-west-mambalam','Kodambakkam','Chennai','Tamil Nadu','600033',1,603),
(604,'Jubilee Road West Mambalam','jubilee-road-west-mambalam','Kodambakkam','Chennai','Tamil Nadu','600033',1,604),
(605,'Eswaran Koil Street West Mambalam','eswaran-koil-street-west-mambalam','Kodambakkam','Chennai','Tamil Nadu','600033',1,605),
(606,'Brindavan Street West Mambalam','brindavan-street-west-mambalam','Kodambakkam','Chennai','Tamil Nadu','600033',1,606),
(607,'Rangarajapuram','rangarajapuram','Kodambakkam','Chennai','Tamil Nadu','600024',1,607),
(608,'United India Colony','united-india-colony','Kodambakkam','Chennai','Tamil Nadu','600024',1,608),
(609,'Trustpuram Kodambakkam','trustpuram-kodambakkam','Kodambakkam','Chennai','Tamil Nadu','600024',1,609),
(610,'Subbarayan Nagar Kodambakkam','subbarayan-nagar-kodambakkam','Kodambakkam','Chennai','Tamil Nadu','600024',1,610),
(611,'Puliyur Kodambakkam','puliyur-kodambakkam','Kodambakkam','Chennai','Tamil Nadu','600024',1,611),
(612,'Arcot Road Kodambakkam','arcot-road-kodambakkam','Kodambakkam','Chennai','Tamil Nadu','600024',1,612),
(613,'Palani Andavar Koil Street','palani-andavar-koil-street','Kodambakkam','Chennai','Tamil Nadu','600024',1,613),
(614,'Vadapalani Signal Area','vadapalani-signal-area','Kodambakkam','Chennai','Tamil Nadu','600026',1,614),
(615,'AVM Studios Area Vadapalani','avm-studios-area-vadapalani','Kodambakkam','Chennai','Tamil Nadu','600026',1,615),
(616,'SIMS Hospital Area Vadapalani','sims-hospital-area-vadapalani','Kodambakkam','Chennai','Tamil Nadu','600026',1,616),
(617,'Doshi Symphony Area Vadapalani','doshi-symphony-area-vadapalani','Kodambakkam','Chennai','Tamil Nadu','600026',1,617),
(618,'Vembuliamman Koil Virugambakkam','vembuliamman-koil-virugambakkam','Valasaravakkam','Chennai','Tamil Nadu','600092',1,618),
(619,'Koyambedu Wholesale Market','koyambedu-wholesale-market','Anna Nagar','Chennai','Tamil Nadu','600107',1,619),
(620,'Rohini Silver Screens Area','rohini-silver-screens-area','Anna Nagar','Chennai','Tamil Nadu','600107',1,620),
(621,'Koyambedu Roundtana','koyambedu-roundtana','Anna Nagar','Chennai','Tamil Nadu','600107',1,621),
(622,'Choolaimedu High Road','choolaimedu-high-road','Anna Nagar','Chennai','Tamil Nadu','600094',1,622),
(623,'Sowrashtra Nagar Choolaimedu','sowrashtra-nagar-choolaimedu','Anna Nagar','Chennai','Tamil Nadu','600094',1,623),
(624,'Namachivaya Puram','namachivaya-puram','Anna Nagar','Chennai','Tamil Nadu','600094',1,624),
(625,'Metha Nagar Choolaimedu','metha-nagar-choolaimedu','Anna Nagar','Chennai','Tamil Nadu','600094',1,625),
(626,'Railway Colony Aminjikarai','railway-colony-aminjikarai','Anna Nagar','Chennai','Tamil Nadu','600029',1,626),
(627,'Pulla Avenue Shenoy Nagar','pulla-avenue-shenoy-nagar','Anna Nagar','Chennai','Tamil Nadu','600030',1,627),
(628,'East Club Road Shenoy Nagar','east-club-road-shenoy-nagar','Anna Nagar','Chennai','Tamil Nadu','600030',1,628),
(629,'West Club Road Shenoy Nagar','west-club-road-shenoy-nagar','Anna Nagar','Chennai','Tamil Nadu','600030',1,629),
(630,'Kandhasamy Street Shenoy Nagar','kandhasamy-street-shenoy-nagar','Anna Nagar','Chennai','Tamil Nadu','600030',1,630),
(631,'Anna Nagar 2nd Avenue','anna-nagar-2nd-avenue','Anna Nagar','Chennai','Tamil Nadu','600040',1,631),
(632,'Anna Nagar 3rd Avenue','anna-nagar-3rd-avenue','Anna Nagar','Chennai','Tamil Nadu','600040',1,632),
(633,'Anna Nagar 4th Avenue','anna-nagar-4th-avenue','Anna Nagar','Chennai','Tamil Nadu','600040',1,633),
(634,'Anna Nagar 5th Avenue','anna-nagar-5th-avenue','Anna Nagar','Chennai','Tamil Nadu','600040',1,634),
(635,'Anna Nagar 6th Avenue','anna-nagar-6th-avenue','Anna Nagar','Chennai','Tamil Nadu','600040',1,635);
/*!40000 ALTER TABLE `service_areas` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `service_highlights`
--

DROP TABLE IF EXISTS `service_highlights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_highlights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `icon_class` varchar(100) DEFAULT 'fas fa-star',
  `description` text DEFAULT NULL,
  `highlight_color` varchar(20) DEFAULT '#667eea',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_highlights`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `service_highlights` WRITE;
/*!40000 ALTER TABLE `service_highlights` DISABLE KEYS */;
INSERT INTO `service_highlights` VALUES
(1,'15+ Years Experience','fas fa-award','Trusted by 10,000+ customers across Chennai','#667eea',1,1,'2026-04-05 17:00:45','2026-04-05 17:00:45'),
(2,'Quality Materials','fas fa-certificate','Premium grade nets with 5-year warranty','#10b981',2,1,'2026-04-05 17:00:45','2026-04-05 17:00:45'),
(3,'Expert Installation','fas fa-user-check','Trained professionals with safety certifications','#f59e0b',3,1,'2026-04-05 17:00:45','2026-04-05 17:00:45'),
(4,'Same Day Service','fas fa-clock','Quick response and installation within 24 hours','#ef4444',4,1,'2026-04-05 17:00:45','2026-04-05 17:00:45'),
(5,'Free Inspection','fas fa-search','Complimentary site visit and measurement','#3b82f6',5,1,'2026-04-05 17:00:45','2026-04-05 17:00:45'),
(6,'Affordable Pricing','fas fa-rupee-sign','Competitive rates with transparent pricing','#8b5cf6',6,1,'2026-04-05 17:00:45','2026-04-05 17:00:45'),
(7,'24/7 Support','fas fa-headset','Round-the-clock customer service available','#ec4899',7,1,'2026-04-05 17:00:45','2026-04-05 17:00:45'),
(8,'All Areas Covered','fas fa-map-marked-alt','Service available across 188 locations in Chennai','#06b6d4',8,1,'2026-04-05 17:00:45','2026-04-05 17:00:45');
/*!40000 ALTER TABLE `service_highlights` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` varchar(100) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `icon_class` varchar(100) DEFAULT 'fas fa-shield-alt',
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `service_slug` varchar(100) DEFAULT NULL,
  `show_in_slider` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_service_id` (`service_id`),
  UNIQUE KEY `idx_slug` (`slug`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=516 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='64 SEO service keywords — permanent seed data';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES
(1,'pigeon-nets','Pigeon Nets','pigeon-nets','PIGEON NETS','Expert pigeon netting to protect your balcony, terrace & AC units — fully mess-free','fas fa-shield-alt',1,1,'2026-04-05 17:00:44','2026-04-12 09:03:14','pigeon-nets',1),
(2,'pigeon-net','Pigeon Net','pigeon-net','PIGEON NETS',NULL,'fas fa-shield-alt',1,2,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(3,'balcony-netting','Balcony Netting','balcony-netting','PIGEON NETS',NULL,'fas fa-shield-alt',1,3,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(4,'pigeon-net-for-balcony','Pigeon Net For Balcony','pigeon-net-for-balcony','PIGEON NETS',NULL,'fas fa-shield-alt',1,4,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(5,'pigeon-nets-installation','Pigeon Nets Installation','pigeon-nets-installation','PIGEON NETS',NULL,'fas fa-shield-alt',1,5,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(6,'pigeon-bird-netting','Pigeon Bird Netting','pigeon-bird-netting','PIGEON NETS',NULL,'fas fa-shield-alt',1,6,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(7,'pigeon-net-installation','Pigeon Net Installation','pigeon-net-installation','PIGEON NETS',NULL,'fas fa-shield-alt',1,7,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(8,'pigeon-net-near-me','Pigeon Net Near Me','pigeon-net-near-me','PIGEON NETS',NULL,'fas fa-shield-alt',1,8,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(9,'pigeon-net-for-balcony-near-me','Pigeon Net For Balcony Near Me','pigeon-net-for-balcony-near-me','PIGEON NETS',NULL,'fas fa-shield-alt',1,9,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(10,'pigeon-net-installation-near-me','Pigeon Net Installation Near Me','pigeon-net-installation-near-me','PIGEON NETS',NULL,'fas fa-shield-alt',1,10,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(11,'pigeon-safety-nets','Pigeon Safety Nets','pigeon-safety-nets','PIGEON NETS',NULL,'fas fa-shield-alt',1,11,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(12,'pigeon-net-price','Pigeon Net Price','pigeon-net-price','PIGEON NETS',NULL,'fas fa-shield-alt',1,12,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(13,'kabutar-jali-near-me','Kabutar Jali Near Me','kabutar-jali-near-me','PIGEON NETS',NULL,'fas fa-shield-alt',1,13,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(14,'bird-nets','Bird Nets','bird-nets','BIRD NETS','Humane bird-proofing solutions for homes, offices & commercial buildings across Chennai','fas fa-shield-alt',1,2,'2026-04-05 17:00:44','2026-04-12 09:03:14','bird-nets',1),
(15,'bird-net','Bird Net','bird-net','BIRD NETS',NULL,'fas fa-shield-alt',1,15,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(16,'bird-net-for-balcony','Bird Net For Balcony','bird-net-for-balcony','BIRD NETS',NULL,'fas fa-shield-alt',1,16,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(17,'bird-net-near-me','Bird Net Near Me','bird-net-near-me','BIRD NETS',NULL,'fas fa-shield-alt',1,17,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(18,'nets-for-birds','Nets For Birds','nets-for-birds','BIRD NETS',NULL,'fas fa-shield-alt',1,18,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(19,'net-for-birds','Net For Birds','net-for-birds','BIRD NETS',NULL,'fas fa-shield-alt',1,19,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(20,'industrial-bird-netting','Industrial Bird Netting','industrial-bird-netting','BIRD NETS',NULL,'fas fa-shield-alt',1,20,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(21,'bird-netting','Bird Netting','bird-netting','BIRD NETS',NULL,'fas fa-shield-alt',1,21,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(23,'safety-nets','Safety Nets','safety-nets','SAFETY NETS','ISI-certified balcony & staircase safety nets — trusted by 10,000+ families in Chennai','fas fa-shield-alt',1,3,'2026-04-05 17:00:44','2026-04-12 09:03:14','safety-nets',1),
(25,'safety-nets-for-balconies','Safety Nets For Balconies','safety-nets-for-balconies','SAFETY NETS',NULL,'fas fa-shield-alt',1,25,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(26,'duct-area-safety-nets','Duct Area Safety Nets','duct-area-safety-nets','SAFETY NETS',NULL,'fas fa-shield-alt',1,26,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(27,'monkey-safety-nets','Monkey Safety Nets','monkey-safety-nets','SAFETY NETS',NULL,'fas fa-shield-alt',1,27,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(28,'construction-safety-nets','Construction Safety Nets','construction-safety-nets','SAFETY NETS',NULL,'fas fa-shield-alt',1,28,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(29,'industrial-safety-nets','Industrial Safety Nets','industrial-safety-nets','SAFETY NETS',NULL,'fas fa-shield-alt',1,29,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(30,'fall-safety-nets','Fall Safety Nets','fall-safety-nets','SAFETY NETS',NULL,'fas fa-shield-alt',1,30,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(31,'fall-protection-nets','Fall Protection Nets','fall-protection-nets','SAFETY NETS',NULL,'fas fa-shield-alt',1,31,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(32,'children-safety-nets','Children Safety Nets','children-safety-nets','SAFETY NETS',NULL,'fas fa-shield-alt',1,32,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(33,'pet-safety-nets','Pet Safety Nets','pet-safety-nets','SAFETY NETS',NULL,'fas fa-shield-alt',1,33,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(36,'cricket-nets-near-me','Cricket Nets Near Me','cricket-nets-near-me','SPORTS NETS',NULL,'fas fa-shield-alt',1,36,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(37,'cricket-practice-net','Cricket Practice Net','cricket-practice-net','SPORTS NETS',NULL,'fas fa-shield-alt',1,37,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(38,'cricket-practice-nets','Cricket Practice Nets','cricket-practice-nets','SPORTS NETS',NULL,'fas fa-shield-alt',1,38,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(39,'cricket-net-price','Cricket Net Price','cricket-net-price','SPORTS NETS',NULL,'fas fa-shield-alt',1,39,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(40,'cricket-indoor-nets-near-me','Cricket Indoor Nets Near Me','cricket-indoor-nets-near-me','SPORTS NETS',NULL,'fas fa-shield-alt',1,40,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(41,'indoor-cricket-nets-near-me','Indoor Cricket Nets Near Me','indoor-cricket-nets-near-me','SPORTS NETS',NULL,'fas fa-shield-alt',1,41,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(42,'sports-nets','Sports Nets','sports-nets','SPORTS NETS',NULL,'fas fa-shield-alt',1,42,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(43,'sports-netting','Sports Netting','sports-netting','SPORTS NETS',NULL,'fas fa-shield-alt',1,43,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(44,'cricket-netting','Cricket Netting','cricket-netting','SPORTS NETS',NULL,'fas fa-shield-alt',1,44,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(45,'box-cricket-net','Box Cricket Net','box-cricket-net','SPORTS NETS',NULL,'fas fa-shield-alt',1,45,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(46,'cricket-net-installation','Cricket Net Installation','cricket-net-installation','SPORTS NETS',NULL,'fas fa-shield-alt',1,46,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(47,'invisible-grills','Invisible Grills','invisible-grills','INVISIBLE GRILLS','Sleek stainless steel invisible grills — maximum safety with zero obstruction to your view','fas fa-shield-alt',1,5,'2026-04-05 17:00:44','2026-04-12 09:03:14','invisible-grills',1),
(48,'invisible-grill-near-me','Invisible Grill Near Me','invisible-grill-near-me','INVISIBLE GRILLS',NULL,'fas fa-shield-alt',1,48,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(49,'ss-invisible-grills','SS Invisible Grills','ss-invisible-grills','INVISIBLE GRILLS',NULL,'fas fa-shield-alt',1,49,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(50,'invisible-grill-for-balcony','Invisible Grill For Balcony','invisible-grill-for-balcony','INVISIBLE GRILLS',NULL,'fas fa-shield-alt',1,50,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(51,'balcony-invisible-grill','Balcony Invisible Grill','balcony-invisible-grill','INVISIBLE GRILLS',NULL,'fas fa-shield-alt',1,51,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(52,'invisible-grill-for-balcony-near-me','Invisible Grill For Balcony Near Me','invisible-grill-for-balcony-near-me','INVISIBLE GRILLS',NULL,'fas fa-shield-alt',1,52,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(53,'invisible-safety-grill','Invisible Safety Grill','invisible-safety-grill','INVISIBLE GRILLS',NULL,'fas fa-shield-alt',1,53,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(54,'invisible-grill-for-safety','Invisible Grill For Safety','invisible-grill-for-safety','INVISIBLE GRILLS',NULL,'fas fa-shield-alt',1,54,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(55,'invisible-grill-for-pigeons','Invisible Grill For Pigeons','invisible-grill-for-pigeons','INVISIBLE GRILLS',NULL,'fas fa-shield-alt',1,55,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(56,'ceiling-cloth-hangers','Ceiling Cloth Hangers','ceiling-cloth-hangers','CLOTH HANGERS',NULL,'fas fa-shield-alt',1,56,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(57,'dry-cloth-hangers','Dry Cloth Hangers','dry-cloth-hangers','CLOTH HANGERS',NULL,'fas fa-shield-alt',1,57,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(58,'cloth-drying-hangers','Cloth Drying Hangers','cloth-drying-hangers','CLOTH HANGERS',NULL,'fas fa-shield-alt',1,58,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(59,'cloth-hanger-for-balcony','Cloth Hanger For Balcony','cloth-hanger-for-balcony','CLOTH HANGERS',NULL,'fas fa-shield-alt',1,59,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(60,'pulley-cloth-drying-hanger','Pulley Cloth Drying Hanger','pulley-cloth-drying-hanger','CLOTH HANGERS',NULL,'fas fa-shield-alt',1,60,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(61,'pulley-cloth-hanger','Pulley Cloth Hanger','pulley-cloth-hanger','CLOTH HANGERS',NULL,'fas fa-shield-alt',1,61,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(62,'laundry-hanger-dryer','Laundry Hanger Dryer','laundry-hanger-dryer','CLOTH HANGERS',NULL,'fas fa-shield-alt',1,62,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(63,'clothes-hanger-to-dry-clothes','Clothes Hanger To Dry Clothes','clothes-hanger-to-dry-clothes','CLOTH HANGERS',NULL,'fas fa-shield-alt',1,63,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(64,'clothes-hanger-drier','Clothes Hanger Drier','clothes-hanger-drier','CLOTH HANGERS',NULL,'fas fa-shield-alt',1,64,'2026-04-05 17:00:44','2026-04-05 17:00:44',NULL,0),
(66,'','Balcony Safety Nets','',NULL,'Balcony Safety Nets','fas fa-shield-alt',1,1,'2026-04-09 12:11:55','2026-04-09 13:46:27','balcony-safety-nets',0),
(509,'cloth-hangers','Cloth Hangers','cloth-hangers','CLOTH HANGERS','Space-saving ceiling-mounted cloth drying systems — strong, rust-proof & built to last','fas fa-shield-alt',1,6,'2026-04-09 17:47:02','2026-04-12 09:03:14','cloth-hangers',1),
(510,'artificial-grass','Artificial Grass','artificial-grass','ARTIFICIAL GRASS','','fas fa-shield-alt',1,7,'2026-04-09 17:47:42','2026-04-09 17:47:42','artificial-grass',1),
(511,'artificial-grass-turf-for-cricket-pitch','Artificial Grass Turf for Cricket Pitch','artificial-grass-turf-for-cricket-pitch','ARTIFICIAL GRASS TURF FOR CRICKET PITCH','','fas fa-shield-alt',1,8,'2026-04-09 17:48:48','2026-04-09 17:48:48','artificial-grass-turf-for-cricket-pitch',1),
(512,'anti-bird-spikes','Anti Bird Spikes','anti-bird-spikes','ANTI BIRD SPIKES','','fas fa-shield-alt',1,9,'2026-04-09 17:49:17','2026-04-09 17:49:17','anti-bird-spikes',1),
(513,'box-cricket-nets','Box Cricket Nets','box-cricket-nets','BOX CRICKET NETS','','fas fa-shield-alt',1,13,'2026-04-09 19:00:59','2026-04-09 19:00:59','box-cricket-nets',1),
(515,'cricket-nets','Cricket Nets','cricket-nets','CRICKET NETS','High-tension practice nets for schools, clubs & private backyards — custom sizes available','fas fa-baseball-ball',1,4,'2026-04-09 19:04:22','2026-04-12 09:03:14','cricket-nets',1);
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `videos`
--

DROP TABLE IF EXISTS `videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `video_url` varchar(500) NOT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `video_type` enum('youtube','vimeo','local') DEFAULT 'youtube',
  `category` varchar(100) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `duration` varchar(20) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `views` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `videos`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `videos` WRITE;
/*!40000 ALTER TABLE `videos` DISABLE KEYS */;
/*!40000 ALTER TABLE `videos` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `visitor_heatmap`
--

DROP TABLE IF EXISTS `visitor_heatmap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visitor_heatmap` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country_code` varchar(10) DEFAULT NULL,
  `country_name` varchar(100) DEFAULT NULL,
  `visitor_count` int(11) DEFAULT 0,
  `last_visit` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_country` (`country_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitor_heatmap`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `visitor_heatmap` WRITE;
/*!40000 ALTER TABLE `visitor_heatmap` DISABLE KEYS */;
INSERT INTO `visitor_heatmap` VALUES
(1,'FI','Finland',1,'2026-09-19 04:53:44'),
(2,'IN','India',3,'2026-09-19 05:01:25'),
(3,'US','United States',4,'2026-09-19 04:58:21');
/*!40000 ALTER TABLE `visitor_heatmap` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `visitor_tracking`
--

DROP TABLE IF EXISTS `visitor_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visitor_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `browser_version` varchar(50) DEFAULT NULL,
  `device_type` varchar(20) DEFAULT 'desktop',
  `os` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `country_code` varchar(10) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `timezone` varchar(50) DEFAULT NULL,
  `isp` varchar(255) DEFAULT NULL,
  `current_page` varchar(500) DEFAULT NULL,
  `entry_page` varchar(500) DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT 1,
  `page_views` int(11) DEFAULT 1,
  `total_time_spent` int(11) DEFAULT 0,
  `first_visit` datetime DEFAULT current_timestamp(),
  `last_activity` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `search_keyword` varchar(255) DEFAULT NULL,
  `exit_page` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_session` (`session_id`),
  KEY `idx_first_visit` (`first_visit`),
  KEY `idx_is_online` (`is_online`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitor_tracking`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `visitor_tracking` WRITE;
/*!40000 ALTER TABLE `visitor_tracking` DISABLE KEYS */;
INSERT INTO `visitor_tracking` VALUES
(1,'9fhlc9hk9uvmu7wwv4e','157.180.73.225','Mozilla/5.0 (compatible; FossickBot/1.0; +https://fossick.bot)','Unknown','','desktop','Other','Finland','FI','18','Helsinki',60.17190000,24.93470000,'Europe/Helsinki','Hetzner Online GmbH','/','/','',1,1,0,'2026-09-19 04:53:44','2026-09-19 04:53:44','','/'),
(2,'eweci9lpqmcmu7wyt2f','49.43.228.18','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Chrome','152.0.0.0','desktop','Mac','India','IN','TS','Hyderabad',17.38430000,78.45830000,'Asia/Kolkata','Reliance Jio Infocomm Limited','/gallery','/','',1,5,325,'2026-09-19 04:55:15','2026-09-19 05:01:59','','/gallery'),
(3,'qlwbtqupawmu7x2l5k','103.196.9.126','Mozilla/5.0 (iPhone; CPU iPhone OS 26_3_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/144.0.7559.95 Mobile/15E148 Safari/604.1','Safari','','mobile','Mac','United States','US','NY','New York',40.71280000,-74.00600000,'America/New_York','M247 Europe SRL','/','/','',1,1,0,'2026-09-19 04:58:11','2026-09-19 04:58:11','','/'),
(4,'edssintq4iwmu7x2l71','152.39.239.236','Mozilla/5.0 (iPhone; CPU iPhone OS 26_3_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/144.0.7559.95 Mobile/15E148 Safari/604.1','Safari','','mobile','Mac','United States','US','IL','Chicago',41.87810000,-87.62980000,'America/Chicago','Oculus Networks Inc','/','/','',1,1,0,'2026-09-19 04:58:11','2026-09-19 04:58:11','','/'),
(5,'16f93w63ni8mu7x2ldu','161.123.81.37','Mozilla/5.0 (iPhone; CPU iPhone OS 26_3_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/144.0.7559.95 Mobile/15E148 Safari/604.1','Safari','','mobile','Mac','United States','US','NY','New York',40.71260000,-74.00660000,'America/New_York','Wirels Connect','/','/','',1,1,0,'2026-09-19 04:58:12','2026-09-19 04:58:12','','/'),
(6,'l9a46e2jyugmu7x2sw6','103.196.9.13','Mozilla/5.0 (iPhone; CPU iPhone OS 26_3_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/144.0.7559.95 Mobile/15E148 Safari/604.1','Safari','','mobile','Mac','United States','US','NY','New York',40.71280000,-74.00600000,'America/New_York','M247 Europe SRL','/','/','',1,1,0,'2026-09-19 04:58:21','2026-09-19 04:58:21','','/'),
(7,'jq9t9dgtd5rmu7x6qop','91.197.252.149','Mozilla/5.0 (iPhone; CPU iPhone OS 16_6_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1','Safari','16.5','mobile','Mac','India','IN','MH','Mumbai',18.95820000,72.83200000,'Asia/Kolkata','HostRoyale Technologies Pvt Ltd','/','/','',1,1,0,'2026-09-19 05:01:25','2026-09-19 05:01:25','','/'),
(8,'w3zxu9b22ismu7x6qxq','77.83.68.191','Mozilla/5.0 (iPhone; CPU iPhone OS 16_6_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1','Safari','16.5','mobile','Mac','India','IN','DL','Delhi',28.70410000,77.10250000,'Asia/Kolkata','AVENTICE','/','/','',1,1,0,'2026-09-19 05:01:25','2026-09-19 05:01:25','','/');
/*!40000 ALTER TABLE `visitor_tracking` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-09-19  5:02:15
