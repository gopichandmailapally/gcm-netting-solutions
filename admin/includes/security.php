<?php
/**
 * Advanced Security System for Admin Panel
 * Protects against brute-force, session hijacking, CSRF, and other attacks
 */

if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}

class AdminSecurity {
    private $db;
    private $max_login_attempts = 5;
    private $lockout_duration = 1800; // 30 minutes
    private $session_timeout = 3600; // 1 hour
    
    public function __construct() {
        require_once dirname(dirname(__DIR__)) . '/config/database.php';
        $this->db = Database::getInstance();
        $this->initSecurityTables();
    }
    
    /**
     * Initialize security tables if they don't exist
     */
    private function initSecurityTables() {
        // Tables are already created in Database class constructor
        // This method is kept for MySQL compatibility when deployed to production
        return;
        
        $conn = $this->db->getConnection();
        
        // Login attempts table (MySQL only)
        $conn->query("CREATE TABLE IF NOT EXISTS admin_login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100),
            ip_address VARCHAR(45),
            user_agent TEXT,
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            success TINYINT(1) DEFAULT 0,
            INDEX idx_username (username),
            INDEX idx_ip (ip_address),
            INDEX idx_time (attempt_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Security logs table
        $conn->query("CREATE TABLE IF NOT EXISTS admin_security_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT,
            action VARCHAR(100),
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin (admin_id),
            INDEX idx_action (action),
            INDEX idx_time (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Blocked IPs table
        $conn->query("CREATE TABLE IF NOT EXISTS admin_blocked_ips (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) UNIQUE,
            reason VARCHAR(255),
            blocked_until TIMESTAMP NULL,
            permanent TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip (ip_address)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Session tracking table
        $conn->query("CREATE TABLE IF NOT EXISTS admin_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT,
            session_id VARCHAR(128) UNIQUE,
            ip_address VARCHAR(45),
            user_agent TEXT,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_session (session_id),
            INDEX idx_admin (admin_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Check if IP is blocked
     */
    public function isIPBlocked($ip = null) {
        $ip = $ip ?? $this->getClientIP();
        
        $result = $this->db->fetchOne(
            "SELECT * FROM admin_blocked_ips 
             WHERE ip_address = ? 
             AND (permanent = 1 OR blocked_until > NOW())",
            [$ip],
            's'
        );
        
        return $result !== null;
    }
    
    /**
     * Check if account is locked due to failed attempts
     */
    public function isAccountLocked($username) {
        $ip = $this->getClientIP();
        
        // Check failed attempts in last 30 minutes
        $attempts = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM admin_login_attempts 
             WHERE (username = ? OR ip_address = ?) 
             AND success = 0 
             AND attempt_time > DATE_SUB(NOW(), INTERVAL 30 MINUTE)",
            [$username, $ip],
            'ss'
        );
        
        return ($attempts['count'] ?? 0) >= $this->max_login_attempts;
    }
    
    /**
     * Get remaining lockout time
     */
    public function getLockoutTime($username) {
        $ip = $this->getClientIP();
        
        $lastAttempt = $this->db->fetchOne(
            "SELECT attempt_time FROM admin_login_attempts 
             WHERE (username = ? OR ip_address = ?) 
             AND success = 0 
             ORDER BY attempt_time DESC LIMIT 1",
            [$username, $ip],
            'ss'
        );
        
        if ($lastAttempt) {
            $lockoutEnd = strtotime($lastAttempt['attempt_time']) + $this->lockout_duration;
            $remaining = $lockoutEnd - time();
            return max(0, $remaining);
        }
        
        return 0;
    }
    
    /**
     * Record login attempt
     */
    public function recordLoginAttempt($username, $success = false) {
        $ip = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $this->db->execute(
            "INSERT INTO admin_login_attempts (username, ip_address, user_agent, success) 
             VALUES (?, ?, ?, ?)",
            [$username, $ip, $userAgent, $success ? 1 : 0]
        );
        
        // If failed, check if we should block IP
        if (!$success) {
            $this->checkAndBlockIP($username);
        }
    }
    
    /**
     * Check if IP should be blocked after multiple failures
     */
    private function checkAndBlockIP($username) {
        $ip = $this->getClientIP();
        
        $attempts = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM admin_login_attempts 
             WHERE ip_address = ? 
             AND success = 0 
             AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$ip],
            's'
        );
        
        // Block IP if more than 10 failed attempts in 1 hour
        if (($attempts['count'] ?? 0) >= 10) {
            $this->blockIP($ip, 'Too many failed login attempts', 3600); // 1 hour block
        }
    }
    
    /**
     * Block an IP address
     */
    public function blockIP($ip, $reason = 'Security violation', $duration = null, $permanent = false) {
        $blockedUntil = $permanent ? null : date('Y-m-d H:i:s', time() + ($duration ?? 3600));
        
        // SQLite doesn't support ON DUPLICATE KEY, use INSERT OR REPLACE
        $this->db->execute(
            "INSERT OR REPLACE INTO admin_blocked_ips (ip_address, reason, blocked_until, permanent) 
             VALUES (?, ?, ?, ?)",
            [$ip, $reason, $blockedUntil, $permanent ? 1 : 0]
        );
    }
    
    /**
     * Clear failed login attempts after successful login
     */
    public function clearLoginAttempts($username) {
        $ip = $this->getClientIP();
        
        $this->db->execute(
            "DELETE FROM admin_login_attempts 
             WHERE (username = ? OR ip_address = ?) 
             AND success = 0",
            [$username, $ip]
        );
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        } elseif (time() - $_SESSION['csrf_token_time'] > 7200) { // 2 hours
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCSRFToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        
        if (time() - ($_SESSION['csrf_token_time'] ?? 0) > 7200) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Secure session initialization
     */
    public function initSecureSession($adminId) {
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        
        // Set secure session variables
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $adminId;
        $_SESSION['session_created'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $this->getClientIP();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Store session in database
        $this->storeSession($adminId);
        
        // Generate CSRF token
        $this->generateCSRFToken();
    }
    
    /**
     * Store session in database
     */
    private function storeSession($adminId) {
        $sessionId = session_id();
        $ip = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // SQLite doesn't support ON DUPLICATE KEY, use INSERT OR REPLACE
        $this->db->execute(
            "INSERT OR REPLACE INTO admin_sessions (admin_id, session_id, ip_address, user_agent) 
             VALUES (?, ?, ?, ?)",
            [$adminId, $sessionId, $ip, $userAgent]
        );
    }
    
    /**
     * Validate session security
     */
    public function validateSession() {
        // Check if session exists
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $this->session_timeout)) {
            $this->destroySession();
            return false;
        }
        
        // Check IP address (optional - can cause issues with dynamic IPs)
        // Uncomment if you want strict IP checking
        /*
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $this->getClientIP()) {
            $this->logSecurityEvent($_SESSION['admin_id'] ?? 0, 'session_hijack_attempt', 'IP address mismatch');
            $this->destroySession();
            return false;
        }
        */
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Destroy session securely
     */
    public function destroySession() {
        // Remove session from database
        if (isset($_SESSION['admin_id'])) {
            $sessionId = session_id();
            $this->db->execute(
                "DELETE FROM admin_sessions WHERE session_id = ?",
                [$sessionId]
            );
        }
        
        // Clear session data
        $_SESSION = array();
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Log security event
     */
    public function logSecurityEvent($adminId, $action, $details = '') {
        $ip = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $this->db->execute(
            "INSERT INTO admin_security_logs (admin_id, action, details, ip_address, user_agent) 
             VALUES (?, ?, ?, ?, ?)",
            [$adminId, $action, $details, $ip, $userAgent]
        );
    }
    
    /**
     * Get client IP address (handles proxies)
     */
    public function getClientIP() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }
        
        return filter_var(trim($ip), FILTER_VALIDATE_IP) ? trim($ip) : 'Unknown';
    }
    
    /**
     * Validate password strength
     */
    public function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 12) {
            $errors[] = 'Password must be at least 12 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character (!@#$%^&*)';
        }
        
        // Check against common passwords
        $commonPasswords = ['password', 'admin', '123456', 'qwerty', 'letmein', 'welcome'];
        foreach ($commonPasswords as $common) {
            if (stripos($password, $common) !== false) {
                $errors[] = 'Password contains a common word or pattern';
                break;
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Hash password with strong algorithm
     */
    public function hashPassword($password) {
        // Use ARGON2ID if available, otherwise fallback to BCRYPT
        if (defined('PASSWORD_ARGON2ID')) {
            return password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3
            ]);
        } else {
            // Fallback to BCRYPT with strong cost
            return password_hash($password, PASSWORD_DEFAULT, [
                'cost' => 12
            ]);
        }
    }
    
    /**
     * Get security statistics
     */
    public function getSecurityStats() {
        $stats = [];
        
        try {
            // Failed login attempts today
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM admin_login_attempts 
                 WHERE success = 0 AND DATE(attempt_time) = CURDATE()"
            );
            $stats['failed_logins_today'] = $result['count'] ?? 0;
        } catch (Exception $e) {
            $stats['failed_logins_today'] = 0;
        }
        
        try {
            // Blocked IPs
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM admin_blocked_ips 
                 WHERE permanent = 1 OR blocked_until > NOW()"
            );
            $stats['blocked_ips'] = $result['count'] ?? 0;
        } catch (Exception $e) {
            $stats['blocked_ips'] = 0;
        }
        
        try {
            // Active sessions
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM admin_sessions 
                 WHERE last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            $stats['active_sessions'] = $result['count'] ?? 0;
        } catch (Exception $e) {
            $stats['active_sessions'] = 0;
        }
        
        try {
            // Recent security events
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM admin_security_logs 
                 WHERE DATE(created_at) = CURDATE()"
            );
            $stats['security_events_today'] = $result['count'] ?? 0;
        } catch (Exception $e) {
            $stats['security_events_today'] = 0;
        }
        
        return $stats;
    }
    
    /**
     * Clean old records (run periodically)
     */
    public function cleanOldRecords() {
        $conn = $this->db->getConnection();
        
        // Delete login attempts older than 30 days
        $conn->query("DELETE FROM admin_login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        
        // Delete expired blocked IPs
        $conn->query("DELETE FROM admin_blocked_ips WHERE permanent = 0 AND blocked_until < NOW()");
        
        // Delete inactive sessions older than 24 hours
        $conn->query("DELETE FROM admin_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        
        // Delete old security logs (keep 90 days)
        $conn->query("DELETE FROM admin_security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    }
}
