<?php
/**
 * GCM Netting Solutions — SQLite Database (Local Dev / PDO)
 * Production uses MySQL on Hostinger.
 *
 * Schema v3  — Strengthened 2025
 *   - service_areas   : 188 Chennai localities (full seeding)
 *   - seo_service_keywords : 64 SEO target keywords (full seeding)
 *   - generated_pages : proper tracking columns + indexes
 *   - All core admin tables intact
 */

if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}

class Database {

    private static $instance = null;
    private $connection;
    private $db_file;
    private $isLocal;

    private function __construct() {
        $this->isLocal = defined('ENV') ? (ENV === 'development') : false;

        if ($this->isLocal) {
            $this->db_file = dirname(__DIR__) . '/data/local-test.db';
            $this->connect();
            $this->connection->exec('PRAGMA journal_mode = WAL');
            $this->connection->exec('PRAGMA foreign_keys = ON');
            $this->connection->exec('PRAGMA synchronous = NORMAL');
            $this->initTables();
            $this->migrateColumns();
            $this->seedData();
        } else {
            $this->connectMySQL();
            $this->seedMySQLAdmin();
            $this->migrateMySQLSchema();
            try { $this->seedKeywords(); } catch (\Exception $e) { error_log('[Database] seedKeywords error: ' . $e->getMessage()); } // Ensure all 64 keywords exist on MySQL
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function getDatabaseInstance() {
        return self::getInstance();
    }

    /* ──────────────────────────────────────────────────
     * CONNECTION — SQLite (local)
     * ────────────────────────────────────────────────── */
    private function connect() {
        try {
            $this->connection = new PDO('sqlite:' . $this->db_file);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    /* ──────────────────────────────────────────────────
     * CONNECTION — MySQL (production)
     * ────────────────────────────────────────────────── */
    private function connectMySQL() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('[Database] MySQL connection failed: ' . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }

    /* ──────────────────────────────────────────────────
     * SEED ADMIN USER — MySQL production (runs once)
     * ────────────────────────────────────────────────── */
    private function seedMySQLAdmin(): void {
        try {
            $count = $this->connection->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
            if ((int)$count === 0) {
                $pw   = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $this->connection->prepare(
                    "INSERT INTO admin_users (username, password_hash, email, role, is_active)
                     VALUES (?, ?, ?, ?, 1)"
                );
                $stmt->execute(['admin', $pw, 'gopichandmailapally@gmail.com', 'super_admin']);
            }
        } catch (\Exception $e) {
            error_log('[Database] MySQL admin seed error: ' . $e->getMessage());
        }
    }

    /* ──────────────────────────────────────────────────
     * MIGRATE MYSQL SCHEMA — adds missing columns
     * ────────────────────────────────────────────────── */
    private function migrateMySQLSchema(): void {
        $db = DB_NAME;

        /* ── admin_login_attempts: add attempt_time ──────── */
        try {
            $n = (int)$this->connection->query(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA='{$db}' AND TABLE_NAME='admin_login_attempts'
                   AND COLUMN_NAME='attempt_time'"
            )->fetchColumn();
            if ($n === 0) {
                $this->connection->exec(
                    "ALTER TABLE admin_login_attempts
                     ADD COLUMN attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP"
                );
            }
        } catch (\Exception $e) { error_log('[DB migrate] login_attempts: ' . $e->getMessage()); }

        /* ── contact_submissions: add area + submitted_at ── */
        try {
            foreach (['area' => "VARCHAR(100) DEFAULT NULL",
                      'submitted_at' => "DATETIME DEFAULT CURRENT_TIMESTAMP"] as $col => $def) {
                $n = (int)$this->connection->query(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA='{$db}' AND TABLE_NAME='contact_submissions'
                       AND COLUMN_NAME='{$col}'"
                )->fetchColumn();
                if ($n === 0) {
                    $this->connection->exec(
                        "ALTER TABLE contact_submissions ADD COLUMN {$col} {$def}"
                    );
                }
            }
        } catch (\Exception $e) { error_log('[DB migrate] contact_submissions: ' . $e->getMessage()); }

        /* ── gemini_api_keys ─────────────────────────────── */
        try {
            $this->connection->exec("CREATE TABLE IF NOT EXISTS `gemini_api_keys` (
                `id`          INT(11) NOT NULL AUTO_INCREMENT,
                `key_label`   VARCHAR(100) NOT NULL DEFAULT 'API Key',
                `api_key`     TEXT NOT NULL,
                `pin_hash`    TEXT NOT NULL,
                `is_primary`  TINYINT(1) DEFAULT 0,
                `test_status` VARCHAR(20) DEFAULT 'untested',
                `test_model`  VARCHAR(100) DEFAULT NULL,
                `tested_at`   DATETIME DEFAULT NULL,
                `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Exception $e) { error_log('[DB migrate] gemini_api_keys: ' . $e->getMessage()); }

        /* ── api_key_audit ───────────────────────────────── */
        try {
            $this->connection->exec("CREATE TABLE IF NOT EXISTS `api_key_audit` (
                `id`         INT(11) NOT NULL AUTO_INCREMENT,
                `action`     VARCHAR(100) DEFAULT NULL,
                `admin_id`   INT(11) DEFAULT NULL,
                `ip_address` VARCHAR(45) DEFAULT NULL,
                `success`    TINYINT(1) DEFAULT 0,
                `details`    TEXT,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Exception $e) { error_log('[DB migrate] api_key_audit: ' . $e->getMessage()); }

        /* ── ai_protected_content ────────────────────────── */
        try {
            $this->connection->exec("CREATE TABLE IF NOT EXISTS `ai_protected_content` (
                `id`            INT(11) NOT NULL AUTO_INCREMENT,
                `content_type`  VARCHAR(100) NOT NULL,
                `content_id`    VARCHAR(255) NOT NULL,
                `content_title` VARCHAR(500) DEFAULT NULL,
                `content_path`  VARCHAR(500) DEFAULT NULL,
                `generated_by`  VARCHAR(50) DEFAULT 'gemini',
                `is_protected`  TINYINT(1) DEFAULT 1,
                `protected_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
                `protected_by`  INT(11) DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_ct_ci` (`content_type`, `content_id`(191))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Exception $e) { error_log('[DB migrate] ai_protected_content: ' . $e->getMessage()); }

        /* ── ai_deletion_log ─────────────────────────────── */
        try {
            $this->connection->exec("CREATE TABLE IF NOT EXISTS `ai_deletion_log` (
                `id`            INT(11) NOT NULL AUTO_INCREMENT,
                `content_type`  VARCHAR(100) DEFAULT NULL,
                `content_id`    VARCHAR(255) DEFAULT NULL,
                `content_title` VARCHAR(500) DEFAULT NULL,
                `action`        VARCHAR(100) DEFAULT NULL,
                `admin_id`      INT(11) DEFAULT NULL,
                `ip_address`    VARCHAR(45) DEFAULT NULL,
                `reason`        TEXT,
                `pin_correct`   TINYINT(1) DEFAULT 0,
                `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Exception $e) { error_log('[DB migrate] ai_deletion_log: ' . $e->getMessage()); }

        /* ── ai_security_settings ────────────────────────── */
        try {
            $this->connection->exec("CREATE TABLE IF NOT EXISTS `ai_security_settings` (
                `key_name`   VARCHAR(100) NOT NULL,
                `key_value`  TEXT,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`key_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Exception $e) { error_log('[DB migrate] ai_security_settings: ' . $e->getMessage()); }

        /* ── services: add PHP-expected columns + seed data ─ */
        try {
            $extraCols = [
                'service_name'   => "VARCHAR(255) DEFAULT NULL",
                'service_slug'   => "VARCHAR(100) DEFAULT NULL",
                'icon_class'     => "VARCHAR(100) DEFAULT 'fas fa-shield-alt'",
                'description'    => "TEXT",
                'display_order'  => "INT DEFAULT 0",
                'show_in_slider' => "TINYINT(1) NOT NULL DEFAULT 0",
            ];
            foreach ($extraCols as $col => $def) {
                $n = (int)$this->connection->query(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA='{$db}' AND TABLE_NAME='services'
                       AND COLUMN_NAME='{$col}'"
                )->fetchColumn();
                if ($n === 0) {
                    $this->connection->exec(
                        "ALTER TABLE services ADD COLUMN {$col} {$def}"
                    );
                }
            }

            /* Seed the 6 main services using slug as the idempotency key */
            $svcs = [
                ['Balcony Safety Nets',  'balcony-safety-nets',  'balcony-safety-nets',  'safety', 'fas fa-shield-alt',    'Professional balcony safety net installation', 1],
                ['Pigeon Nets',          'pigeon-nets',          'pigeon-nets',          'birds',  'fas fa-dove',          'Pigeon control net installation',              2],
                ['Children Safety Nets', 'children-safety-nets', 'children-safety-nets', 'safety', 'fas fa-child',         'Children safety net installation',             3],
                ['Invisible Grills',     'invisible-grills',     'invisible-grills',     'grills', 'fas fa-grip-lines',    'Invisible grill installation services',        4],
                ['Sports Nets',          'sports-nets',          'sports-nets',          'sports', 'fas fa-football-ball', 'Sports safety net installation',               5],
                ['Cloth Hangers',        'cloth-hangers',        'cloth-hangers',        'home',   'fas fa-tshirt',        'Ceiling cloth hanger installation',            6],
            ];
            $stmt = $this->connection->prepare(
                "INSERT INTO services (keyword, slug, category, is_active, service_name, service_slug, icon_class, description, display_order)
                 VALUES (?,?,?,1,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE
                   service_name=VALUES(service_name), service_slug=VALUES(service_slug),
                   icon_class=VALUES(icon_class), description=VALUES(description),
                   display_order=VALUES(display_order)"
            );
            foreach ($svcs as $s) {
                $stmt->execute([$s[1], $s[1], $s[3], $s[0], $s[2], $s[4], $s[5], $s[6]]);
            }
        } catch (\Exception $e) { error_log('[DB migrate] services: ' . $e->getMessage()); }

        /* ── generated_pages: full SEO page-tracking schema ─ */
        try {
            // Create table with full schema (no-op if already exists)
            $this->connection->exec("CREATE TABLE IF NOT EXISTS `generated_pages` (
                `id`               INT(11)      NOT NULL AUTO_INCREMENT,
                `service_id`       INT(11)      DEFAULT 0,
                `keyword_id`       INT(11)      DEFAULT 0,
                `area_id`          INT(11)      DEFAULT 0,
                `page_slug`        VARCHAR(500) DEFAULT NULL,
                `page_title`       VARCHAR(255) NOT NULL DEFAULT '',
                `meta_description` TEXT,
                `meta_keywords`    TEXT,
                `h1_heading`       VARCHAR(255) DEFAULT NULL,
                `content`          LONGTEXT,
                `word_count`       INT(11)      DEFAULT 0,
                `is_published`     TINYINT(1)   DEFAULT 1,
                `generated_by`     VARCHAR(50)  DEFAULT 'gemini_ai',
                `generated_at`     DATETIME     DEFAULT CURRENT_TIMESTAMP,
                `created_at`       DATETIME     DEFAULT CURRENT_TIMESTAMP,
                `updated_at`       DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Add any columns missing from an older version of the table
            $gpCols = [
                'service_id'       => "INT(11) DEFAULT 0",
                'keyword_id'       => "INT(11) DEFAULT 0",
                'area_id'          => "INT(11) DEFAULT 0",
                'page_slug'        => "VARCHAR(500) DEFAULT NULL",
                'meta_description' => "TEXT",
                'meta_keywords'    => "TEXT",
                'h1_heading'       => "VARCHAR(255) DEFAULT NULL",
                'content'          => "LONGTEXT",
                'is_published'     => "TINYINT(1) DEFAULT 1",
                'generated_by'     => "VARCHAR(50) DEFAULT 'gemini_ai'",
                'generated_at'     => "DATETIME DEFAULT CURRENT_TIMESTAMP",
            ];
            foreach ($gpCols as $col => $def) {
                $n = (int)$this->connection->query(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA='{$db}' AND TABLE_NAME='generated_pages'
                       AND COLUMN_NAME='{$col}'"
                )->fetchColumn();
                if ($n === 0) {
                    $this->connection->exec(
                        "ALTER TABLE `generated_pages` ADD COLUMN `{$col}` {$def}"
                    );
                }
            }

            // Add unique index on keyword_id + area_id (allows fast duplicate checks)
            try {
                $ix = (int)$this->connection->query(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
                     WHERE TABLE_SCHEMA='{$db}' AND TABLE_NAME='generated_pages'
                       AND INDEX_NAME='uq_kw_area'"
                )->fetchColumn();
                if ($ix === 0) {
                    $this->connection->exec(
                        "ALTER TABLE `generated_pages`
                         ADD UNIQUE KEY `uq_kw_area` (`keyword_id`,`area_id`)"
                    );
                }
            } catch (\Exception $e) { /* ignore – index may already exist */ }

        } catch (\Exception $e) { error_log('[DB migrate] generated_pages: ' . $e->getMessage()); }

        /* ── seo_service_keywords: ensure table exists ──────── */
        try {
            $this->connection->exec("CREATE TABLE IF NOT EXISTS `seo_service_keywords` (
                `id`            INT(11)      NOT NULL AUTO_INCREMENT,
                `keyword_name`  VARCHAR(255) NOT NULL,
                `keyword_slug`  VARCHAR(255) NOT NULL,
                `category`      VARCHAR(100) DEFAULT NULL,
                `search_volume` INT(11)      DEFAULT 0,
                `difficulty`    INT(11)      DEFAULT 0,
                `is_active`     TINYINT(1)   DEFAULT 1,
                `display_order` INT(11)      DEFAULT 0,
                `created_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_kw_slug` (`keyword_slug`(191))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Exception $e) { error_log('[DB migrate] seo_service_keywords: ' . $e->getMessage()); }
        /* ── seo_service_keywords: add missing columns for existing production tables ─ */
        try { $this->connection->exec("ALTER TABLE `seo_service_keywords` ADD COLUMN `difficulty` INT(11) DEFAULT 0"); } catch (\Exception $e) { /* already exists */ }
        try { $this->connection->exec("ALTER TABLE `seo_service_keywords` ADD COLUMN `display_order` INT(11) DEFAULT 0"); } catch (\Exception $e) { /* already exists */ }
        /* ── Ensure unique index on keyword_slug exists (needed for ON DUPLICATE KEY) ─ */
        try {
            $idx = $this->connection->query(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='seo_service_keywords'
                   AND INDEX_NAME='uq_kw_slug'"
            )->fetchColumn();
            if ((int)$idx === 0) {
                $this->connection->exec(
                    "ALTER TABLE `seo_service_keywords` ADD UNIQUE KEY `uq_kw_slug` (`keyword_slug`(191))"
                );
            }
        } catch (\Exception $e) { error_log('[DB migrate] kw unique index: ' . $e->getMessage()); }

        /* ── Force-activate all 64 known keyword slugs (runs every load, cheap UPDATE) ─ */
        try {
            $kw_file = __DIR__ . '/all-service-keywords.php';
            if (file_exists($kw_file)) {
                $slugs = array_column(require $kw_file, 'slug');
                $ph    = implode(',', array_fill(0, count($slugs), '?'));
                $this->connection->prepare(
                    "UPDATE `seo_service_keywords` SET `is_active`=1 WHERE `keyword_slug` IN ($ph) AND `is_active`=0"
                )->execute($slugs);
            }
        } catch (\Exception $e) { error_log('[DB migrate] keyword activation: ' . $e->getMessage()); }

        /* ── Direct fix: ensure Anti Bird Netting keyword exists and is active ─── */
        try {
            $svc = $this->connection->query("SELECT id FROM `services` WHERE service_slug = 'bird-nets' LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
            $svc_id = (int)($svc['id'] ?? 0);
            $this->connection->exec("SET FOREIGN_KEY_CHECKS = 0");
            if ($svc_id > 0) {
                $this->connection->exec(
                    "INSERT INTO `seo_service_keywords`
                     (keyword_name, keyword_slug, category, search_volume, difficulty, is_active, display_order, service_id)
                     VALUES ('Anti Bird Netting', 'anti-bird-netting', 'BIRD NETS', 540, 41, 1, 22, {$svc_id})
                     ON DUPLICATE KEY UPDATE is_active = 1, display_order = 22, service_id = {$svc_id}"
                );
            } else {
                $this->connection->exec(
                    "INSERT INTO `seo_service_keywords`
                     (keyword_name, keyword_slug, category, search_volume, difficulty, is_active, display_order)
                     VALUES ('Anti Bird Netting', 'anti-bird-netting', 'BIRD NETS', 540, 41, 1, 22)
                     ON DUPLICATE KEY UPDATE is_active = 1, display_order = 22"
                );
            }
            $this->connection->exec("SET FOREIGN_KEY_CHECKS = 1");
        } catch (\Exception $e) { error_log('[Database] Anti Bird Netting: ' . $e->getMessage()); }

        /* ── service_areas: ensure table exists ─────────────── */
        try {
            $this->connection->exec("CREATE TABLE IF NOT EXISTS `service_areas` (
                `id`         INT(11)      NOT NULL AUTO_INCREMENT,
                `area_name`  VARCHAR(255) NOT NULL,
                `area_slug`  VARCHAR(255) NOT NULL,
                `city`       VARCHAR(100) DEFAULT 'Chennai',
                `is_active`  TINYINT(1)   DEFAULT 1,
                `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_area_slug` (`area_slug`(191))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Exception $e) { error_log('[DB migrate] service_areas: ' . $e->getMessage()); }

        /* ── faqs: add slug + is_ai_generated columns if missing ─── */
        try {
            foreach (['slug' => "VARCHAR(400) DEFAULT NULL",
                      'is_ai_generated' => "TINYINT(1) DEFAULT 0"] as $col => $def) {
                $n = (int)$this->connection->query(
                    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA='{$db}' AND TABLE_NAME='faqs' AND COLUMN_NAME='{$col}'"
                )->fetchColumn();
                if ($n === 0) {
                    $this->connection->exec("ALTER TABLE faqs ADD COLUMN {$col} {$def}");
                }
            }
        } catch (\Exception $e) { error_log('[DB migrate] faqs cols: ' . $e->getMessage()); }

        /* ── ai_blogs: MySQL storage for AI-generated blog posts ── */
        try {
            $this->connection->exec("CREATE TABLE IF NOT EXISTS `ai_blogs` (
                `id`             INT(11)      NOT NULL AUTO_INCREMENT,
                `title`          VARCHAR(600) NOT NULL,
                `slug`           VARCHAR(600) NOT NULL,
                `content`        LONGTEXT,
                `excerpt`        TEXT,
                `author`         VARCHAR(200) DEFAULT 'GCM Netting Solutions',
                `featured_image` VARCHAR(500) DEFAULT NULL,
                `is_published`   TINYINT(1)   DEFAULT 1,
                `views`          INT(11)      DEFAULT 0,
                `created_at`     DATETIME     DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_blog_slug` (`slug`(191))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Exception $e) { error_log('[DB migrate] ai_blogs: ' . $e->getMessage()); }

        /* ── ai_reviews: MySQL storage for AI-generated reviews ─── */
        try {
            $this->connection->exec("CREATE TABLE IF NOT EXISTS `ai_reviews` (
                `id`            INT(11)      NOT NULL AUTO_INCREMENT,
                `customer_name` VARCHAR(300) NOT NULL,
                `rating`        TINYINT(1)   NOT NULL DEFAULT 5,
                `category`      VARCHAR(200) DEFAULT NULL,
                `review_text`   TEXT         NOT NULL,
                `location`      VARCHAR(300) DEFAULT NULL,
                `slug`          VARCHAR(400) DEFAULT NULL,
                `status`        VARCHAR(50)  DEFAULT 'approved',
                `verified`      TINYINT(1)   DEFAULT 1,
                `helpful_count` INT(11)      DEFAULT 0,
                `created_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Exception $e) { error_log('[DB migrate] ai_reviews: ' . $e->getMessage()); }

        /* ── blog_posts ──────────────────────────────────────── */
        try {
            $this->connection->exec("CREATE TABLE IF NOT EXISTS `blog_posts` (
                `id`               INT(11)      NOT NULL AUTO_INCREMENT,
                `title`            VARCHAR(255) NOT NULL,
                `slug`             VARCHAR(255) NOT NULL,
                `keyword`          VARCHAR(100) NOT NULL,
                `category`         VARCHAR(100) NOT NULL,
                `content`          LONGTEXT     NOT NULL,
                `excerpt`          TEXT,
                `meta_title`       VARCHAR(255) DEFAULT NULL,
                `meta_description` VARCHAR(500) DEFAULT NULL,
                `meta_keywords`    TEXT,
                `featured_image`   VARCHAR(255) DEFAULT NULL,
                `author`           VARCHAR(100) DEFAULT 'GCM Netting Solutions',
                `word_count`       INT(11)      DEFAULT 0,
                `reading_time`     INT(11)      DEFAULT 0,
                `status`           ENUM('draft','published','scheduled') DEFAULT 'draft',
                `is_published`     TINYINT(1)   DEFAULT 0,
                `publish_date`     DATETIME     DEFAULT NULL,
                `views`            INT(11)      DEFAULT 0,
                `is_featured`      TINYINT(1)   DEFAULT 0,
                `seo_score`        INT(11)      DEFAULT 0,
                `created_at`       DATETIME     DEFAULT CURRENT_TIMESTAMP,
                `updated_at`       DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_bp_slug` (`slug`(191))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            try { $this->connection->exec("ALTER TABLE `blog_posts` ADD COLUMN `is_published` TINYINT(1) DEFAULT 0"); } catch (\Exception $e) { /* already exists */ }
        } catch (\Exception $e) { error_log('[DB migrate] blog_posts: ' . $e->getMessage()); }

        /* ── blog_topics ─────────────────────────────────────── */
        try {
            $this->connection->exec("CREATE TABLE IF NOT EXISTS `blog_topics` (
                `id`             INT(11) NOT NULL AUTO_INCREMENT,
                `keyword`        VARCHAR(100) NOT NULL,
                `category`       VARCHAR(100) NOT NULL,
                `topic_type`     ENUM('service','installation','maintenance','benefits','comparison','guide','tips','faq') NOT NULL,
                `topic_template` VARCHAR(255) NOT NULL,
                `used_count`     INT(11)  DEFAULT 0,
                `last_used`      DATETIME DEFAULT NULL,
                `is_active`      TINYINT(1) DEFAULT 1,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Exception $e) { error_log('[DB migrate] blog_topics: ' . $e->getMessage()); }

        /* ── blog_settings ───────────────────────────────────── */
        try {
            $this->connection->exec("CREATE TABLE IF NOT EXISTS `blog_settings` (
                `id`            INT(11)      NOT NULL AUTO_INCREMENT,
                `setting_key`   VARCHAR(100) NOT NULL,
                `setting_value` TEXT,
                `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_bs_key` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Exception $e) { error_log('[DB migrate] blog_settings: ' . $e->getMessage()); }

        /* ── blog_generation_schedule ────────────────────────── */
        try {
            $this->connection->exec("CREATE TABLE IF NOT EXISTS `blog_generation_schedule` (
                `id`                INT(11) NOT NULL AUTO_INCREMENT,
                `schedule_date`     DATE    NOT NULL,
                `blogs_to_generate` INT(11) DEFAULT 1,
                `blogs_generated`   INT(11) DEFAULT 0,
                `status`            ENUM('pending','in_progress','completed','failed') DEFAULT 'pending',
                `error_message`     TEXT,
                `created_at`        DATETIME DEFAULT CURRENT_TIMESTAMP,
                `completed_at`      DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_sched_date` (`schedule_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Exception $e) { error_log('[DB migrate] blog_generation_schedule: ' . $e->getMessage()); }

        /* ── Seed blog_settings defaults ─────────────────────── */
        try {
            $bsDefaults = [
                ['daily_blog_count','3'], ['auto_publish','1'], ['min_word_count','1200'],
                ['max_word_count','2000'], ['enable_auto_generation','1'],
                ['generation_time','09:00:00'], ['last_generation_date',null],
            ];
            $bsStmt = $this->connection->prepare(
                "INSERT IGNORE INTO `blog_settings` (setting_key, setting_value) VALUES (?,?)"
            );
            foreach ($bsDefaults as $d) { $bsStmt->execute($d); }
        } catch (\Exception $e) { error_log('[DB migrate] blog_settings seed: ' . $e->getMessage()); }

        /* ── Seed blog_topics if empty ───────────────────────── */
        try {
            $tc = (int)$this->connection->query("SELECT COUNT(*) FROM `blog_topics`")->fetchColumn();
            if ($tc === 0) {
                $btTopics = [
                    ['Pigeon Nets','Pigeon Nets','service','Complete Guide to {keyword} in Chennai'],
                    ['Pigeon Nets','Pigeon Nets','installation','How to Install {keyword} - Step by Step Guide'],
                    ['Pigeon Nets','Pigeon Nets','maintenance','Maintaining Your {keyword} - Best Practices'],
                    ['Pigeon Nets','Pigeon Nets','benefits','Top 10 Benefits of {keyword} for Your Home'],
                    ['Pigeon Nets','Pigeon Nets','comparison','{keyword} vs Other Bird Control Methods'],
                    ['Pigeon Nets','Pigeon Nets','guide','Ultimate Buyer Guide for {keyword}'],
                    ['Pigeon Nets','Pigeon Nets','tips','7 Expert Tips for Choosing {keyword}'],
                    ['Pigeon Net','Pigeon Nets','service','Why {keyword} is Essential for Chennai Homes'],
                    ['Pigeon Net','Pigeon Nets','installation','Professional {keyword} Installation Services'],
                    ['Pigeon Net','Pigeon Nets','benefits','Health Benefits of Installing {keyword}'],
                    ['Balcony Netting','Pigeon Nets','service','{keyword} Solutions for Modern Apartments'],
                    ['Balcony Netting','Pigeon Nets','installation','DIY vs Professional {keyword} Installation'],
                    ['Balcony Netting','Pigeon Nets','maintenance','How to Clean and Maintain {keyword}'],
                    ['Pigeon Net For Balcony','Pigeon Nets','guide','Complete Guide to {keyword}'],
                    ['Pigeon Net For Balcony','Pigeon Nets','benefits','Why Every Balcony Needs {keyword}'],
                    ['Bird Nets','Bird Nets','service','Comprehensive {keyword} Solutions in Chennai'],
                    ['Bird Nets','Bird Nets','installation','Installing {keyword} - What You Need to Know'],
                    ['Bird Nets','Bird Nets','comparison','{keyword} Materials Comparison Guide'],
                    ['Bird Nets','Bird Nets','benefits','Environmental Benefits of {keyword}'],
                    ['Bird Net','Bird Nets','service','Choosing the Right {keyword} for Your Property'],
                    ['Bird Net','Bird Nets','maintenance','Long-lasting {keyword} Maintenance Tips'],
                    ['Anti Bird Netting','Bird Nets','service','{keyword} for Commercial Buildings'],
                    ['Anti Bird Netting','Bird Nets','benefits','Cost Savings with {keyword}'],
                    ['Safety Nets','Safety Nets','service','Industrial {keyword} - Complete Safety Guide'],
                    ['Safety Nets','Safety Nets','installation','Safety Standards for {keyword} Installation'],
                    ['Safety Nets','Safety Nets','benefits','How {keyword} Save Lives in Construction'],
                    ['Safety Nets','Safety Nets','guide','Choosing the Right {keyword} for Your Project'],
                    ['Balcony Safety Nets','Safety Nets','service','Child Safety with {keyword}'],
                    ['Balcony Safety Nets','Safety Nets','installation','Installing {keyword} in High-Rise Buildings'],
                    ['Balcony Safety Nets','Safety Nets','benefits','Pet Protection with {keyword}'],
                    ['Children Safety Nets','Safety Nets','service','Protecting Your Kids with {keyword}'],
                    ['Children Safety Nets','Safety Nets','guide','Parents Guide to {keyword}'],
                    ['Construction Safety Nets','Safety Nets','service','OSHA Compliant {keyword}'],
                    ['Construction Safety Nets','Safety Nets','installation','Installing {keyword} on Construction Sites'],
                    ['Cricket Nets','Sports Nets','service','Professional {keyword} for Practice'],
                    ['Cricket Nets','Sports Nets','installation','Setting Up {keyword} at Home'],
                    ['Cricket Nets','Sports Nets','guide','Buying Guide for {keyword}'],
                    ['Cricket Nets','Sports Nets','benefits','Benefits of Home {keyword}'],
                    ['Cricket Practice Net','Sports Nets','service','Building Your Own {keyword}'],
                    ['Cricket Practice Net','Sports Nets','installation','Indoor vs Outdoor {keyword}'],
                    ['Sports Nets','Sports Nets','service','Multi-Sport {keyword} Solutions'],
                    ['Sports Nets','Sports Nets','maintenance','Maintaining Your {keyword}'],
                    ['Invisible Grills','Invisible Grills','service','Modern {keyword} for Contemporary Homes'],
                    ['Invisible Grills','Invisible Grills','installation','Installing {keyword} - Complete Process'],
                    ['Invisible Grills','Invisible Grills','benefits','Why Choose {keyword} Over Traditional Grills'],
                    ['Invisible Grills','Invisible Grills','comparison','{keyword} vs Window Grills'],
                    ['Invisible Grills','Invisible Grills','guide','Ultimate Guide to {keyword}'],
                    ['SS Invisible Grills','Invisible Grills','service','Stainless Steel {keyword} Benefits'],
                    ['SS Invisible Grills','Invisible Grills','maintenance','Caring for Your {keyword}'],
                    ['Ceiling Cloth Hangers','Cloth Hangers','service','Space-Saving {keyword} Solutions'],
                    ['Ceiling Cloth Hangers','Cloth Hangers','installation','Installing {keyword} in Small Spaces'],
                    ['Ceiling Cloth Hangers','Cloth Hangers','benefits','Benefits of {keyword} for Apartments'],
                    ['Pulley Cloth Hanger','Cloth Hangers','service','Traditional {keyword} - Still Relevant?'],
                    ['Pulley Cloth Hanger','Cloth Hangers','guide','Choosing the Right {keyword}'],
                ];
                $btStmt = $this->connection->prepare(
                    "INSERT IGNORE INTO `blog_topics` (keyword, category, topic_type, topic_template) VALUES (?,?,?,?)"
                );
                foreach ($btTopics as $t) { $btStmt->execute($t); }
            }
        } catch (\Exception $e) { error_log('[DB migrate] blog_topics seed: ' . $e->getMessage()); }

        /* ── v_blog_statistics view ──────────────────────────── */
        try {
            $this->connection->exec("CREATE OR REPLACE VIEW `v_blog_statistics` AS
                SELECT COUNT(*) as total_blogs,
                    SUM(CASE WHEN status='published' THEN 1 ELSE 0 END) as published_blogs,
                    SUM(CASE WHEN status='draft'     THEN 1 ELSE 0 END) as draft_blogs,
                    COALESCE(SUM(views),0)         as total_views,
                    COALESCE(AVG(word_count),0)    as avg_word_count,
                    COALESCE(AVG(seo_score),0)     as avg_seo_score
                FROM blog_posts");
        } catch (\Exception $e) { error_log('[DB migrate] v_blog_statistics: ' . $e->getMessage()); }
    }

    /* ──────────────────────────────────────────────────
     * TABLE CREATION  (CREATE TABLE IF NOT EXISTS only)
     * ────────────────────────────────────────────────── */
    private function initTables() {

        /* ── Admin Users ──────────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS admin_users (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            username     TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            email        TEXT,
            role         TEXT DEFAULT 'admin',
            is_active    INTEGER DEFAULT 1,
            last_login   TEXT,
            created_at   TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at   TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        $check = $this->connection->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
        if ($check == 0) {
            $pw = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $this->connection->prepare(
                "INSERT INTO admin_users (username, password_hash, email, role) VALUES (?,?,?,?)"
            );
            $stmt->execute(['admin', $pw, 'admin@gcmsafetynets.in', 'super_admin']);
        }

        /* ── Login Attempts ───────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS admin_login_attempts (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            username     TEXT,
            ip_address   TEXT,
            user_agent   TEXT,
            attempt_time TEXT DEFAULT CURRENT_TIMESTAMP,
            success      INTEGER DEFAULT 0
        )");
        $this->createIndex('idx_login_ip',   'admin_login_attempts', 'ip_address');
        $this->createIndex('idx_login_user',  'admin_login_attempts', 'username');

        /* ── Security Logs ────────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS admin_security_logs (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id   INTEGER,
            action     TEXT,
            details    TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        $this->createIndex('idx_seclog_admin', 'admin_security_logs', 'admin_id');

        /* ── Blocked IPs ──────────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS admin_blocked_ips (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address    TEXT UNIQUE,
            reason        TEXT,
            blocked_until TEXT,
            permanent     INTEGER DEFAULT 0,
            created_at    TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        /* ── Sessions ─────────────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS admin_sessions (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id      INTEGER,
            session_id    TEXT UNIQUE,
            ip_address    TEXT,
            user_agent    TEXT,
            last_activity TEXT DEFAULT CURRENT_TIMESTAMP,
            created_at    TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        /* ── Content Locks ────────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS content_locks (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            content_type TEXT NOT NULL,
            content_id   TEXT NOT NULL,
            content_path TEXT,
            is_locked    INTEGER DEFAULT 0,
            locked_by    INTEGER,
            locked_at    TEXT,
            lock_reason  TEXT,
            created_at   TEXT DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(content_type, content_id)
        )");
        $this->createIndex('idx_locks_type',   'content_locks', 'content_type');
        $this->createIndex('idx_locks_locked',  'content_locks', 'is_locked');

        /* ── Gemini API Keys ──────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS gemini_api_keys (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            key_label   TEXT    NOT NULL DEFAULT 'API Key',
            api_key     TEXT    NOT NULL,
            pin_hash    TEXT    NOT NULL,
            is_primary  INTEGER DEFAULT 0,
            test_status TEXT    DEFAULT 'untested',
            test_model  TEXT,
            tested_at   TEXT,
            created_at  TEXT    DEFAULT CURRENT_TIMESTAMP,
            updated_at  TEXT    DEFAULT CURRENT_TIMESTAMP
        )");

        /* ── API Key Audit ────────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS api_key_audit (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            action     TEXT,
            admin_id   INTEGER,
            ip_address TEXT,
            success    INTEGER,
            details    TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        /* ── AI Content Protection ────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS ai_protected_content (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            content_type  TEXT NOT NULL,
            content_id    TEXT NOT NULL,
            content_title TEXT,
            content_path  TEXT,
            generated_by  TEXT DEFAULT 'gemini',
            is_protected  INTEGER DEFAULT 1,
            protected_at  TEXT DEFAULT CURRENT_TIMESTAMP,
            protected_by  INTEGER DEFAULT 0,
            UNIQUE(content_type, content_id)
        )");
        $this->connection->exec("CREATE TABLE IF NOT EXISTS ai_deletion_log (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            content_type  TEXT,
            content_id    TEXT,
            content_title TEXT,
            action        TEXT,
            admin_id      INTEGER,
            ip_address    TEXT,
            reason        TEXT,
            pin_correct   INTEGER DEFAULT 0,
            created_at    TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        $this->connection->exec("CREATE TABLE IF NOT EXISTS ai_security_settings (
            key_name   TEXT PRIMARY KEY,
            key_value  TEXT,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        /* ── Service Areas (188 Chennai localities) ─── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS service_areas (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            area_name     TEXT NOT NULL,
            area_slug     TEXT NOT NULL UNIQUE,
            zone          TEXT NOT NULL DEFAULT 'Chennai',
            pincode       TEXT,
            description   TEXT,
            latitude      REAL,
            longitude     REAL,
            is_active     INTEGER DEFAULT 1,
            display_order INTEGER DEFAULT 0,
            created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at    TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        $this->createIndex('idx_areas_slug',   'service_areas', 'area_slug');
        $this->createIndex('idx_areas_active',  'service_areas', 'is_active');
        $this->createIndex('idx_areas_zone',    'service_areas', 'zone');

        /* ── SEO Service Keywords (64 keywords) ──────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS seo_service_keywords (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            keyword_slug  TEXT NOT NULL UNIQUE,
            keyword_name  TEXT NOT NULL,
            category      TEXT NOT NULL,
            search_volume INTEGER DEFAULT 0,
            difficulty    INTEGER DEFAULT 0,
            is_active     INTEGER DEFAULT 1,
            display_order INTEGER DEFAULT 0,
            created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at    TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        $this->createIndex('idx_kw_slug',      'seo_service_keywords', 'keyword_slug');
        $this->createIndex('idx_kw_category',   'seo_service_keywords', 'category');
        $this->createIndex('idx_kw_active',     'seo_service_keywords', 'is_active');

        /* ── Services (6 main service categories) ─────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS services (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            service_name  TEXT NOT NULL,
            service_slug  TEXT NOT NULL UNIQUE,
            icon_class    TEXT,
            description   TEXT,
            is_active     INTEGER DEFAULT 1,
            display_order INTEGER DEFAULT 0,
            created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at    TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        /* ── Generated Pages (SEO page tracking) ─────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS generated_pages (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            page_url      TEXT UNIQUE,
            page_title    TEXT,
            page_description TEXT,
            keywords      TEXT,
            content       TEXT,
            slug          TEXT,
            file_path     TEXT,
            area_id       INTEGER,
            keyword_id    INTEGER,
            area_slug     TEXT,
            keyword_slug  TEXT,
            word_count    INTEGER DEFAULT 0,
            status        TEXT DEFAULT 'completed',
            is_published  INTEGER DEFAULT 1,
            generated_at  TEXT,
            created_at    TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at    TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        $this->createIndex('idx_gp_area_kw',   'generated_pages', 'area_slug, keyword_slug');
        $this->createIndex('idx_gp_status',     'generated_pages', 'status');
        $this->createIndex('idx_gp_published',  'generated_pages', 'is_published');
        $this->createIndex('idx_gp_created',    'generated_pages', 'created_at');

        /* ── SEO Rankings ─────────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS seo_rankings (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            page_url     TEXT,
            keyword      TEXT,
            position     INTEGER,
            clicks       INTEGER DEFAULT 0,
            impressions  INTEGER DEFAULT 0,
            ctr          REAL DEFAULT 0,
            last_updated TEXT DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(page_url, keyword)
        )");

        /* ── SEO Recommendations ──────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS seo_recommendations (
            id                  INTEGER PRIMARY KEY AUTOINCREMENT,
            page_url            TEXT,
            recommendation_type TEXT,
            priority            TEXT,
            issue               TEXT,
            recommendation      TEXT,
            expected_impact     TEXT,
            status              TEXT DEFAULT 'pending',
            created_at          TEXT DEFAULT CURRENT_TIMESTAMP,
            resolved_at         TEXT
        )");

        /* ── Blog Posts ───────────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS blog_posts (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            title       TEXT NOT NULL,
            slug        TEXT UNIQUE,
            content     TEXT,
            excerpt     TEXT,
            author      TEXT DEFAULT 'GCM Team',
            category    TEXT,
            tags        TEXT,
            image_url   TEXT,
            is_published INTEGER DEFAULT 1,
            published_at TEXT,
            created_at  TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at  TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        $this->createIndex('idx_blog_slug',     'blog_posts', 'slug');
        $this->createIndex('idx_blog_published', 'blog_posts', 'is_published');

        /* ── Reviews ──────────────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS reviews (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            reviewer     TEXT NOT NULL,
            rating       INTEGER DEFAULT 5,
            review_text  TEXT,
            service_type TEXT,
            area         TEXT,
            is_approved  INTEGER DEFAULT 1,
            source       TEXT DEFAULT 'manual',
            created_at   TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        /* ── FAQs ─────────────────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS faqs (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            question     TEXT NOT NULL,
            answer       TEXT,
            category     TEXT,
            display_order INTEGER DEFAULT 0,
            is_active    INTEGER DEFAULT 1,
            created_at   TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        /* ── Gallery Images ───────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS gallery_images (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            title        TEXT,
            image_url    TEXT NOT NULL,
            category     TEXT,
            display_order INTEGER DEFAULT 0,
            is_active    INTEGER DEFAULT 1,
            created_at   TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        /* ── Contact Submissions ──────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS contact_submissions (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            name        TEXT,
            phone       TEXT,
            email       TEXT,
            message     TEXT,
            service     TEXT,
            area        TEXT,
            is_read     INTEGER DEFAULT 0,
            created_at  TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        /* ── Visitor Analytics ────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS visitor_analytics (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            page_url     TEXT,
            ip_address   TEXT,
            user_agent   TEXT,
            referrer     TEXT,
            session_id   TEXT,
            time_on_page INTEGER DEFAULT 0,
            created_at   TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        $this->createIndex('idx_va_url',     'visitor_analytics', 'page_url');
        $this->createIndex('idx_va_created', 'visitor_analytics', 'created_at');

        /* ── Page Views (lightweight counter) ─────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS page_views (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            page_url   TEXT,
            view_count INTEGER DEFAULT 1,
            last_view  TEXT DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(page_url)
        )");

        /* ── Password Reset Tokens ───────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id    INTEGER NOT NULL,
            token_hash  TEXT NOT NULL UNIQUE,
            ip_address  TEXT,
            is_used     INTEGER DEFAULT 0,
            expires_at  TEXT NOT NULL,
            created_at  TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        $this->createIndex('idx_prt_admin',   'password_reset_tokens', 'admin_id');
        $this->createIndex('idx_prt_expires',  'password_reset_tokens', 'expires_at');

        /* ── Email Verification Tokens ────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS email_verification_tokens (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id    INTEGER NOT NULL,
            token_hash  TEXT NOT NULL UNIQUE,
            action      TEXT NOT NULL,
            payload     TEXT,
            ip_address  TEXT,
            is_used     INTEGER DEFAULT 0,
            expires_at  TEXT NOT NULL,
            created_at  TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        $this->createIndex('idx_evt_admin',   'email_verification_tokens', 'admin_id');
        $this->createIndex('idx_evt_action',   'email_verification_tokens', 'action');

        /* ── Account Audit Log ────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS account_audit_log (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id    INTEGER,
            actor_id    INTEGER,
            action      TEXT NOT NULL,
            details     TEXT,
            ip_address  TEXT,
            user_agent  TEXT,
            status      TEXT DEFAULT 'success',
            created_at  TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        $this->createIndex('idx_aal_admin',   'account_audit_log', 'admin_id');
        $this->createIndex('idx_aal_created',  'account_audit_log', 'created_at');

        /* ── Newsletter ───────────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            email        TEXT UNIQUE NOT NULL,
            name         TEXT,
            is_active    INTEGER DEFAULT 1,
            subscribed_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        /* ── Offers ───────────────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS offers (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            title        TEXT,
            description  TEXT,
            discount_pct INTEGER DEFAULT 0,
            valid_from   TEXT,
            valid_until  TEXT,
            is_active    INTEGER DEFAULT 1,
            created_at   TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        /* ── Videos ───────────────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS videos (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            title        TEXT,
            video_url    TEXT,
            thumbnail    TEXT,
            category     TEXT,
            display_order INTEGER DEFAULT 0,
            is_active    INTEGER DEFAULT 1,
            created_at   TEXT DEFAULT CURRENT_TIMESTAMP
        )");

        /* ── Billing Tables ───────────────────────────── */
        $this->connection->exec("CREATE TABLE IF NOT EXISTS billing_company_settings (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT UNIQUE,
            setting_val TEXT,
            updated_at  TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        $this->connection->exec("CREATE TABLE IF NOT EXISTS billing_bank_details (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            bank_name     TEXT,
            account_no    TEXT,
            ifsc_code     TEXT,
            account_type  TEXT DEFAULT 'Current',
            is_primary    INTEGER DEFAULT 0,
            created_at    TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        $this->connection->exec("CREATE TABLE IF NOT EXISTS billing_products (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            product_name TEXT NOT NULL,
            hsn_code     TEXT,
            gst_rate     REAL DEFAULT 18.0,
            unit         TEXT DEFAULT 'sqft',
            base_price   REAL DEFAULT 0,
            is_active    INTEGER DEFAULT 1,
            created_at   TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        $this->connection->exec("CREATE TABLE IF NOT EXISTS billing_invoices (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_number  TEXT UNIQUE,
            client_name     TEXT,
            client_phone    TEXT,
            client_address  TEXT,
            invoice_date    TEXT,
            due_date        TEXT,
            subtotal        REAL DEFAULT 0,
            gst_amount      REAL DEFAULT 0,
            total_amount    REAL DEFAULT 0,
            status          TEXT DEFAULT 'draft',
            notes           TEXT,
            created_at      TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at      TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        $this->connection->exec("CREATE TABLE IF NOT EXISTS billing_invoice_items (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            invoice_id    INTEGER NOT NULL,
            description   TEXT,
            quantity      REAL DEFAULT 1,
            unit_price    REAL DEFAULT 0,
            gst_rate      REAL DEFAULT 18.0,
            amount        REAL DEFAULT 0,
            created_at    TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        $this->createIndex('idx_inv_items', 'billing_invoice_items', 'invoice_id');
    }

    /* ──────────────────────────────────────────────────
     * MIGRATE — add columns to existing tables that
     * were created before this schema version.
     * SQLite does NOT support IF NOT EXISTS on ALTER TABLE.
     * ────────────────────────────────────────────────── */
    private function migrateColumns() {
        $this->addColumnIfMissing('service_areas', 'zone',       "TEXT DEFAULT 'Chennai'");
        $this->createIndex('idx_areas_zone', 'service_areas', 'zone');
        $this->addColumnIfMissing('service_areas', 'latitude',   'REAL');
        $this->addColumnIfMissing('service_areas', 'longitude',  'REAL');
        $this->addColumnIfMissing('service_areas', 'updated_at', 'TEXT');

        $this->addColumnIfMissing('generated_pages', 'slug',         'TEXT');
        $this->addColumnIfMissing('generated_pages', 'file_path',    'TEXT');
        $this->addColumnIfMissing('generated_pages', 'area_id',      'INTEGER');
        $this->addColumnIfMissing('generated_pages', 'keyword_id',   'INTEGER');
        $this->addColumnIfMissing('generated_pages', 'area_slug',    'TEXT');
        $this->addColumnIfMissing('generated_pages', 'keyword_slug', 'TEXT');
        $this->addColumnIfMissing('generated_pages', 'word_count',   'INTEGER');
        $this->addColumnIfMissing('generated_pages', 'status',       'TEXT');
        $this->addColumnIfMissing('generated_pages', 'generated_at', 'TEXT');
        $this->addColumnIfMissing('generated_pages', 'updated_at',   'TEXT');

        $this->addColumnIfMissing('admin_users', 'last_login', 'TEXT');
    }

    private function createIndex(string $name, string $table, string $columns) {
        try {
            $this->connection->exec("CREATE INDEX IF NOT EXISTS {$name} ON {$table}({$columns})");
        } catch (PDOException $e) {
            error_log("Index warning [{$name}]: " . $e->getMessage());
        }
    }

    private function addColumnIfMissing(string $table, string $column, string $definition) {
        try {
            $cols = $this->connection->query("PRAGMA table_info({$table})")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $col) {
                if ($col['name'] === $column) return; // already exists
            }
            $this->connection->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        } catch (PDOException $e) {
            error_log("Migration warning [{$table}.{$column}]: " . $e->getMessage());
        }
    }

    /* ──────────────────────────────────────────────────
     * SEED DATA — runs only when tables are underpopulated.
     * ────────────────────────────────────────────────── */
    private function seedData() {
        $this->seedAreas();
        $this->seedKeywords();
        $this->seedServices();
    }

    /* ── Seed 188 Areas ─────────────────────────────── */
    private function seedAreas() {
        $count = (int)$this->connection->query("SELECT COUNT(*) FROM service_areas")->fetchColumn();
        if ($count >= 188) return; // already complete

        $areas_file = __DIR__ . '/all-areas.php';
        if (!file_exists($areas_file)) return;
        $areas = require $areas_file;

        $stmt = $this->connection->prepare(
            "INSERT IGNORE INTO service_areas (area_name, area_slug, zone, is_active, display_order)
             VALUES (?, ?, ?, 1, ?)"
        );
        $this->connection->beginTransaction();
        foreach ($areas as $i => $a) {
            $stmt->execute([$a['area'], $a['slug'], $a['zone'] ?? 'Chennai', $i + 1]);
        }
        $this->connection->commit();
    }

    /* ── Seed 64 SEO Keywords ───────────────────────── */
    private function seedKeywords() {
        // Check ACTIVE count — inactive or missing keywords both need fixing
        $active = (int)$this->connection->query("SELECT COUNT(*) FROM seo_service_keywords WHERE is_active = 1")->fetchColumn();
        if ($active >= 64) return; // all 64 active, nothing to do

        $kw_file = __DIR__ . '/all-service-keywords.php';
        if (!file_exists($kw_file)) return;
        $keywords = require $kw_file;

        /* ── Step 1: Re-activate any inactive keywords from our known list ── */
        try {
            $slugs = array_column($keywords, 'slug');
            $ph    = implode(',', array_fill(0, count($slugs), '?'));
            $this->connection->prepare(
                "UPDATE `seo_service_keywords` SET `is_active`=1 WHERE `keyword_slug` IN ($ph) AND `is_active`=0"
            )->execute($slugs);
        } catch (\Exception $e) { /* ignore */ }

        // Re-check after activation — may already be 64 now
        $active = (int)$this->connection->query("SELECT COUNT(*) FROM seo_service_keywords WHERE is_active = 1")->fetchColumn();
        if ($active >= 64) return;

        /* Estimated search volumes per category */
        $volumes = [
            'PIGEON NETS'     => [1400, 1200, 900, 1100, 850, 780, 800, 700, 650, 600, 1200, 500, 420],
            'BIRD NETS'       => [890, 750, 650, 600, 540, 480, 320, 450, 540],
            'SAFETY NETS'     => [1800, 1100, 900, 420, 380, 780, 650, 420, 380, 620, 520],
            'SPORTS NETS'     => [1500, 480, 620, 580, 890, 420, 380, 350, 750, 320, 420, 280, 520],
            'INVISIBLE GRILLS'=> [1350, 780, 680, 920, 840, 750, 580, 620, 480],
            'CLOTH HANGERS'   => [980, 720, 820, 680, 580, 640, 420, 380, 350],
        ];
        $cat_idx = [];

        /* ── Step 2: INSERT IGNORE for any genuinely missing keywords ──────── */
        $this->connection->exec("SET FOREIGN_KEY_CHECKS = 0");
        $stmt = $this->connection->prepare(
            "INSERT IGNORE INTO `seo_service_keywords`
             (`keyword_slug`, `keyword_name`, `category`, `search_volume`, `difficulty`, `is_active`, `display_order`)
             VALUES (?, ?, ?, ?, ?, 1, ?)"
        );
        $this->connection->beginTransaction();
        $order = 1;
        foreach ($keywords as $kw) {
            $cat  = $kw['category'];
            $idx  = $cat_idx[$cat] ?? 0;
            $vol  = $volumes[$cat][$idx] ?? 300;
            $diff = min(100, 30 + (int)($vol / 50));
            $stmt->execute([$kw['slug'], $kw['keyword'], $cat, $vol, $diff, $order++]);
            $cat_idx[$cat] = $idx + 1;
        }
        $this->connection->commit();
        $this->connection->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    /* ── Seed 6 Main Services ───────────────────────── */
    private function seedServices() {
        $count = (int)$this->connection->query("SELECT COUNT(*) FROM services")->fetchColumn();
        if ($count >= 6) return;

        $svc = [
            ['Pigeon Safety Nets',  'pigeon-safety-nets',  'fa-dove',         'Professional pigeon net installation for balconies',          1],
            ['Bird Nets',           'bird-nets',           'fa-kiwi-bird',    'Anti-bird netting for residential and commercial properties', 2],
            ['Safety Nets',         'safety-nets',         'fa-shield-alt',   'Balcony and construction safety nets for fall protection',    3],
            ['Invisible Grills',    'invisible-grills',    'fa-th',           'Modern invisible grills for balconies and windows',           4],
            ['Sports Nets',         'sports-nets',         'fa-baseball-ball','Cricket nets and sports netting solutions',                   5],
            ['Cloth Hangers',       'cloth-hangers',       'fa-tshirt',       'Ceiling cloth hangers and drying systems',                    6],
        ];
        $stmt = $this->connection->prepare(
            "INSERT IGNORE INTO services (service_name, service_slug, icon_class, description, is_active, display_order)
             VALUES (?,?,?,?,1,?)"
        );
        foreach ($svc as $s) {
            $stmt->execute($s);
        }
    }

    /* ──────────────────────────────────────────────────
     * PUBLIC API
     * ────────────────────────────────────────────────── */
    public function getConnection() {
        return $this->connection;
    }

    public function fetchOne(string $query, array $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage());
            return null;
        }
    }

    public function fetchAll(string $query, array $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage());
            return [];
        }
    }

    public function execute(string $query, array $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage());
            return false;
        }
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    /* ── Convenience wrappers ───────────────────────── */

    /** Return all active areas sorted by display_order */
    public function getAreas(): array {
        return $this->fetchAll(
            "SELECT * FROM service_areas WHERE is_active = 1 ORDER BY display_order ASC"
        );
    }

    /** Return all active SEO keywords, optionally filtered by category */
    public function getKeywords(string $category = ''): array {
        if ($category) {
            return $this->fetchAll(
                "SELECT * FROM seo_service_keywords WHERE is_active = 1 AND category = ? ORDER BY display_order ASC",
                [$category]
            );
        }
        return $this->fetchAll(
            "SELECT * FROM seo_service_keywords WHERE is_active = 1 ORDER BY display_order ASC"
        );
    }

    /** Return keyword categories with counts */
    public function getKeywordCategories(): array {
        return $this->fetchAll(
            "SELECT category, COUNT(*) AS kw_count FROM seo_service_keywords WHERE is_active = 1 GROUP BY category ORDER BY kw_count DESC"
        );
    }

    /** Get DB summary stats */
    public function getDBStats(): array {
        return [
            'areas'           => (int)$this->connection->query("SELECT COUNT(*) FROM service_areas WHERE is_active = 1")->fetchColumn(),
            'keywords'        => (int)$this->connection->query("SELECT COUNT(*) FROM seo_service_keywords WHERE is_active = 1")->fetchColumn(),
            'possible_pages'  => 0, // calculated below
            'generated_pages' => (int)$this->connection->query("SELECT COUNT(*) FROM generated_pages WHERE is_published = 1")->fetchColumn(),
            'services'        => (int)$this->connection->query("SELECT COUNT(*) FROM services WHERE is_active = 1")->fetchColumn(),
        ];
    }
}
