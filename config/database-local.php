<?php
/**
 * Local SQLite Database for Testing
 * Fallback when MySQL is not available
 */

if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}

class DatabaseLocal {
    private static $instance = null;
    private $connection;
    private $db_file;
    
    private function __construct() {
        $this->db_file = dirname(__DIR__) . '/data/local-test.db';
        $this->connect();
        $this->initTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Alias for compatibility
    public static function getDatabaseInstance() {
        return self::getInstance();
    }
    
    private function connect() {
        try {
            $this->connection = new PDO('sqlite:' . $this->db_file);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    private function initTables() {
        // Admin users table
        $this->connection->exec("CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            email TEXT,
            role TEXT DEFAULT 'admin',
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert default admin if not exists
        $check = $this->connection->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
        if ($check == 0) {
            $password = password_hash('admin123', PASSWORD_ARGON2ID);
            $stmt = $this->connection->prepare("INSERT INTO admin_users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
            $stmt->execute(['admin', $password, 'admin@gcmsafetynets.in', 'super_admin']);
        }
        
        // Login attempts table
        $this->connection->exec("CREATE TABLE IF NOT EXISTS admin_login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT,
            ip_address TEXT,
            user_agent TEXT,
            attempt_time TEXT DEFAULT CURRENT_TIMESTAMP,
            success INTEGER DEFAULT 0
        )");
        
        // Security logs table
        $this->connection->exec("CREATE TABLE IF NOT EXISTS admin_security_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER,
            action TEXT,
            details TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Blocked IPs table
        $this->connection->exec("CREATE TABLE IF NOT EXISTS admin_blocked_ips (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address TEXT UNIQUE,
            reason TEXT,
            blocked_until TEXT,
            permanent INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Sessions table
        $this->connection->exec("CREATE TABLE IF NOT EXISTS admin_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER,
            session_id TEXT UNIQUE,
            ip_address TEXT,
            user_agent TEXT,
            last_activity TEXT DEFAULT CURRENT_TIMESTAMP,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Content locks table
        $this->connection->exec("CREATE TABLE IF NOT EXISTS content_locks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            content_type TEXT NOT NULL,
            content_id TEXT NOT NULL,
            content_path TEXT,
            is_locked INTEGER DEFAULT 0,
            locked_by INTEGER,
            locked_at TEXT,
            lock_reason TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(content_type, content_id)
        )");
        
        // API key audit table
        $this->connection->exec("CREATE TABLE IF NOT EXISTS api_key_audit (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            action TEXT,
            admin_id INTEGER,
            ip_address TEXT,
            success INTEGER,
            details TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function fetchOne($query, $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage());
            return null;
        }
    }
    
    public function fetchAll($query, $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage());
            return [];
        }
    }
    
    public function execute($query, $params = [], $types = '') {
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
}
