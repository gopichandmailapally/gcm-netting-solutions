<?php
/**
 * Content Security Manager
 * Handles content locking, API key protection, and secure deletion
 */

if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}

class ContentSecurity {
    private $db;
    private $settings_file;
    
    public function __construct() {
        require_once dirname(dirname(__DIR__)) . '/config/database.php';
        $this->db = Database::getInstance();
        $this->settings_file = dirname(dirname(__DIR__)) . '/config/content-security-settings.json';
        $this->initTables();
    }
    
    /**
     * Initialize content security tables
     */
    private function initTables() {
        $conn = $this->db->getConnection();
        
        // Content locks table
        $conn->query("CREATE TABLE IF NOT EXISTS content_locks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            content_type VARCHAR(50) NOT NULL,
            content_id VARCHAR(255) NOT NULL,
            content_path VARCHAR(500),
            is_locked TINYINT(1) DEFAULT 0,
            locked_by INT,
            locked_at TIMESTAMP NULL,
            lock_reason TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_content (content_type, content_id),
            INDEX idx_locked (is_locked),
            INDEX idx_type (content_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // API key audit log
        $conn->query("CREATE TABLE IF NOT EXISTS api_key_audit (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(50),
            admin_id INT,
            ip_address VARCHAR(45),
            success TINYINT(1),
            details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_action (action),
            INDEX idx_admin (admin_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    
    /**
     * Save API key and generation password securely
     */
    public function saveAPISettings($api_key, $generation_password, $admin_id) {
        // Encrypt API key
        $encryption_key = $this->getEncryptionKey();
        $encrypted_api_key = openssl_encrypt($api_key, 'AES-256-CBC', $encryption_key, 0, substr(md5($encryption_key), 0, 16));
        
        // Hash generation password
        $password_hash = password_hash($generation_password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
        
        $settings = [
            'api_key' => base64_encode($encrypted_api_key),
            'password_hash' => $password_hash,
            'last_updated' => date('Y-m-d H:i:s'),
            'updated_by' => $admin_id
        ];
        
        file_put_contents($this->settings_file, json_encode($settings, JSON_PRETTY_PRINT));
        chmod($this->settings_file, 0600); // Read/write for owner only
        
        // Log action
        $this->logAPIAction('api_settings_saved', $admin_id, true, 'API key and password updated');
        
        return true;
    }
    
    /**
     * Get decrypted API key (requires password verification)
     */
    public function getAPIKey($generation_password = null) {
        if (!file_exists($this->settings_file)) {
            return null;
        }
        
        $settings = json_decode(file_get_contents($this->settings_file), true);
        
        // If password provided, verify it
        if ($generation_password !== null) {
            if (!isset($settings['password_hash']) || !password_verify($generation_password, $settings['password_hash'])) {
                return null;
            }
        }
        
        if (!isset($settings['api_key'])) {
            return null;
        }
        
        // Decrypt API key
        $encryption_key = $this->getEncryptionKey();
        $encrypted_data = base64_decode($settings['api_key']);
        $decrypted = openssl_decrypt($encrypted_data, 'AES-256-CBC', $encryption_key, 0, substr(md5($encryption_key), 0, 16));
        
        return $decrypted;
    }
    
    /**
     * Get masked API key for display
     */
    public function getMaskedAPIKey() {
        $api_key = $this->getAPIKey();
        if (!$api_key) {
            return null;
        }
        
        $length = strlen($api_key);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }
        
        return substr($api_key, 0, 4) . str_repeat('*', $length - 8) . substr($api_key, -4);
    }
    
    /**
     * Verify generation password
     */
    public function verifyGenerationPassword($password) {
        if (!file_exists($this->settings_file)) {
            return false;
        }
        
        $settings = json_decode(file_get_contents($this->settings_file), true);
        
        if (!isset($settings['password_hash'])) {
            return false;
        }
        
        return password_verify($password, $settings['password_hash']);
    }
    
    /**
     * Check if API settings exist
     */
    public function hasAPISettings() {
        if (!file_exists($this->settings_file)) {
            return false;
        }
        
        $settings = json_decode(file_get_contents($this->settings_file), true);
        return isset($settings['api_key']) && isset($settings['password_hash']);
    }
    
    /**
     * Lock content to prevent deletion
     */
    public function lockContent($content_type, $content_id, $content_path, $admin_id, $reason = '') {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare(
            "INSERT INTO content_locks (content_type, content_id, content_path, is_locked, locked_by, locked_at, lock_reason)
             VALUES (?, ?, ?, 1, ?, NOW(), ?)
             ON DUPLICATE KEY UPDATE 
             is_locked = 1, locked_by = ?, locked_at = NOW(), lock_reason = ?, content_path = ?"
        );
        
        $stmt->bind_param('sssisiss', $content_type, $content_id, $content_path, $admin_id, $reason, $admin_id, $reason, $content_path);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
    
    /**
     * Unlock content (requires password verification)
     */
    public function unlockContent($content_type, $content_id, $password, $admin_id) {
        // Verify password
        if (!$this->verifyGenerationPassword($password)) {
            $this->logAPIAction('unlock_failed', $admin_id, false, "Content: {$content_type}/{$content_id} - Wrong password");
            return false;
        }
        
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare(
            "UPDATE content_locks SET is_locked = 0 WHERE content_type = ? AND content_id = ?"
        );
        $stmt->bind_param('ss', $content_type, $content_id);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            $this->logAPIAction('content_unlocked', $admin_id, true, "Content: {$content_type}/{$content_id}");
        }
        
        return $result;
    }
    
    /**
     * Check if content is locked
     */
    public function isContentLocked($content_type, $content_id) {
        $result = $this->db->fetchOne(
            "SELECT is_locked FROM content_locks WHERE content_type = ? AND content_id = ?",
            [$content_type, $content_id],
            'ss'
        );
        
        return $result ? (bool)$result['is_locked'] : false;
    }
    
    /**
     * Get lock info
     */
    public function getLockInfo($content_type, $content_id) {
        return $this->db->fetchOne(
            "SELECT cl.*, au.username 
             FROM content_locks cl 
             LEFT JOIN admin_users au ON cl.locked_by = au.id 
             WHERE cl.content_type = ? AND cl.content_id = ?",
            [$content_type, $content_id],
            'ss'
        );
    }
    
    /**
     * Get all locked content
     */
    public function getLockedContent($content_type = null) {
        if ($content_type) {
            return $this->db->fetchAll(
                "SELECT cl.*, au.username 
                 FROM content_locks cl 
                 LEFT JOIN admin_users au ON cl.locked_by = au.id 
                 WHERE cl.is_locked = 1 AND cl.content_type = ?
                 ORDER BY cl.locked_at DESC",
                [$content_type],
                's'
            );
        } else {
            return $this->db->fetchAll(
                "SELECT cl.*, au.username 
                 FROM content_locks cl 
                 LEFT JOIN admin_users au ON cl.locked_by = au.id 
                 WHERE cl.is_locked = 1
                 ORDER BY cl.locked_at DESC"
            );
        }
    }
    
    /**
     * Delete content (requires password if locked)
     */
    public function deleteContent($content_type, $content_id, $content_path, $password, $admin_id) {
        // Check if locked
        if ($this->isContentLocked($content_type, $content_id)) {
            // Verify password
            if (!$this->verifyGenerationPassword($password)) {
                $this->logAPIAction('delete_failed', $admin_id, false, "Content: {$content_type}/{$content_id} - Wrong password");
                return ['success' => false, 'error' => 'Invalid password. Content is locked.'];
            }
        }
        
        // Delete physical file if exists
        if ($content_path && file_exists($content_path)) {
            if (!unlink($content_path)) {
                return ['success' => false, 'error' => 'Failed to delete file.'];
            }
        }
        
        // Remove lock record
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("DELETE FROM content_locks WHERE content_type = ? AND content_id = ?");
        $stmt->bind_param('ss', $content_type, $content_id);
        $stmt->execute();
        $stmt->close();
        
        $this->logAPIAction('content_deleted', $admin_id, true, "Content: {$content_type}/{$content_id}");
        
        return ['success' => true];
    }
    
    /**
     * Bulk lock content
     */
    public function bulkLockContent($content_items, $admin_id, $reason = 'Bulk lock') {
        $locked = 0;
        foreach ($content_items as $item) {
            if ($this->lockContent($item['type'], $item['id'], $item['path'] ?? '', $admin_id, $reason)) {
                $locked++;
            }
        }
        return $locked;
    }
    
    /**
     * Get encryption key (stored securely)
     */
    private function getEncryptionKey() {
        // Use a combination of server-specific values for encryption
        $key_file = dirname(dirname(__DIR__)) . '/config/.encryption_key';
        
        if (!file_exists($key_file)) {
            // Generate new key
            $key = bin2hex(random_bytes(32));
            file_put_contents($key_file, $key);
            chmod($key_file, 0600);
        } else {
            $key = file_get_contents($key_file);
        }
        
        return $key;
    }
    
    /**
     * Log API-related actions
     */
    private function logAPIAction($action, $admin_id, $success, $details = '') {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare(
            "INSERT INTO api_key_audit (action, admin_id, ip_address, success, details) 
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sisis', $action, $admin_id, $ip, $success, $details);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Get API audit logs
     */
    public function getAPIAuditLogs($limit = 50) {
        return $this->db->fetchAll(
            "SELECT aa.*, au.username 
             FROM api_key_audit aa 
             LEFT JOIN admin_users au ON aa.admin_id = au.id 
             ORDER BY aa.created_at DESC 
             LIMIT ?",
            [$limit],
            'i'
        );
    }
    
    /**
     * Get content statistics
     */
    public function getContentStats() {
        $stats = [];
        
        // Total locked content
        $stats['total_locked'] = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM content_locks WHERE is_locked = 1"
        )['count'] ?? 0;
        
        // Locked by type
        $by_type = $this->db->fetchAll(
            "SELECT content_type, COUNT(*) as count 
             FROM content_locks 
             WHERE is_locked = 1 
             GROUP BY content_type"
        );
        
        $stats['by_type'] = [];
        foreach ($by_type as $row) {
            $stats['by_type'][$row['content_type']] = $row['count'];
        }
        
        return $stats;
    }
    
    /**
     * Optimize generation speed by caching API responses
     */
    public function cacheGeneratedContent($cache_key, $content, $ttl = 3600) {
        $cache_dir = dirname(dirname(__DIR__)) . '/cache/generation';
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        
        $cache_file = $cache_dir . '/' . md5($cache_key) . '.cache';
        $cache_data = [
            'content' => $content,
            'created' => time(),
            'expires' => time() + $ttl
        ];
        
        file_put_contents($cache_file, serialize($cache_data));
    }
    
    /**
     * Get cached content if available
     */
    public function getCachedContent($cache_key) {
        $cache_dir = dirname(dirname(__DIR__)) . '/cache/generation';
        $cache_file = $cache_dir . '/' . md5($cache_key) . '.cache';
        
        if (!file_exists($cache_file)) {
            return null;
        }
        
        $cache_data = unserialize(file_get_contents($cache_file));
        
        // Check if expired
        if ($cache_data['expires'] < time()) {
            unlink($cache_file);
            return null;
        }
        
        return $cache_data['content'];
    }
    
    /**
     * Clear generation cache
     */
    public function clearGenerationCache() {
        $cache_dir = dirname(dirname(__DIR__)) . '/cache/generation';
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '/*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
            return count($files);
        }
        return 0;
    }
}
