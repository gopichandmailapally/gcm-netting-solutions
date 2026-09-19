<?php
/**
 * Secure API Key Manager
 * Centralized, encrypted API key storage with password protection
 */

class APIKeyManager {
    private $db;
    private $encryption_key;
    private $encryption_method = 'AES-256-CBC';
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->encryption_key = $this->getEncryptionKey();
    }
    
    /**
     * Get encryption key (stored securely)
     */
    private function getEncryptionKey() {
        // Use a combination of server-specific values for encryption key
        $server_key = defined('DB_PASS') ? DB_PASS : 'default_key';
        $site_key = defined('SITE_URL') ? SITE_URL : 'default_site';
        return hash('sha256', $server_key . $site_key . 'gcm_api_encryption');
    }
    
    /**
     * Encrypt API key
     */
    private function encrypt($data) {
        $iv_length = openssl_cipher_iv_length($this->encryption_method);
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted = openssl_encrypt($data, $this->encryption_method, $this->encryption_key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }
    
    /**
     * Decrypt API key
     */
    private function decrypt($data) {
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, $this->encryption_method, $this->encryption_key, 0, $iv);
    }
    
    /**
     * Verify security password
     */
    public function verifyPassword($password) {
        try {
            $stored_hash = $this->db->fetchOne("SELECT setting_value FROM api_key_security WHERE setting_key = 'password_hash'");
            
            if (!$stored_hash || empty($stored_hash['setting_value'])) {
                // No password set yet, allow first-time setup
                return true;
            }
            
            return password_verify($password, $stored_hash['setting_value']);
        } catch (Exception $e) {
            error_log("Password verification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set security password
     */
    public function setPassword($password) {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $this->db->execute("
                INSERT INTO api_key_security (setting_key, setting_value) 
                VALUES ('password_hash', ?)
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ", [$hash, $hash]);
            
            $this->db->execute("
                INSERT INTO api_key_security (setting_key, setting_value) 
                VALUES ('last_password_change', NOW())
                ON DUPLICATE KEY UPDATE setting_value = NOW(), updated_at = NOW()
            ");
            
            return true;
        } catch (Exception $e) {
            error_log("Set password error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Save API key (encrypted)
     */
    public function saveKey($key_name, $key_value, $key_type = 'gemini', $password = null) {
        try {
            // Verify password if required
            if ($this->isPasswordRequired() && !$this->verifyPassword($password)) {
                throw new Exception("Invalid password");
            }
            
            // Encrypt the API key
            $encrypted_key = $this->encrypt($key_value);
            
            // Check if key exists
            $existing = $this->db->fetchOne("SELECT id FROM api_keys WHERE key_name = ?", [$key_name]);
            
            if ($existing) {
                // Update existing key
                $this->db->execute("
                    UPDATE api_keys 
                    SET key_value = ?, key_type = ?, is_active = 1, updated_at = NOW()
                    WHERE key_name = ?
                ", [$encrypted_key, $key_type, $key_name]);
            } else {
                // Insert new key
                $this->db->execute("
                    INSERT INTO api_keys (key_name, key_value, key_type, is_active, created_by)
                    VALUES (?, ?, ?, 1, ?)
                ", [$key_name, $encrypted_key, $key_type, $_SESSION['admin_username'] ?? 'system']);
            }
            
            // Log access
            $this->logAccess($key_name, 'update', true);
            
            return true;
        } catch (Exception $e) {
            $this->logAccess($key_name, 'update', false, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get API key (decrypted)
     */
    public function getKey($key_name, $password = null) {
        try {
            // Verify password if required for viewing
            if ($this->isPasswordRequired() && $password !== null && !$this->verifyPassword($password)) {
                throw new Exception("Invalid password");
            }
            
            $result = $this->db->fetchOne("SELECT key_value FROM api_keys WHERE key_name = ? AND is_active = 1", [$key_name]);
            
            if (!$result) {
                return null;
            }
            
            // Decrypt the key
            $decrypted_key = $this->decrypt($result['key_value']);
            
            // Update usage stats
            $this->db->execute("
                UPDATE api_keys 
                SET usage_count = usage_count + 1, last_used = NOW()
                WHERE key_name = ?
            ", [$key_name]);
            
            // Log access (only if password was provided - viewing)
            if ($password !== null) {
                $this->logAccess($key_name, 'view', true);
            } else {
                $this->logAccess($key_name, 'use', true);
            }
            
            return $decrypted_key;
        } catch (Exception $e) {
            $this->logAccess($key_name, 'view', false, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete API key (requires password)
     */
    public function deleteKey($key_name, $password) {
        try {
            if (!$this->verifyPassword($password)) {
                throw new Exception("Invalid password");
            }
            
            $this->db->execute("DELETE FROM api_keys WHERE key_name = ?", [$key_name]);
            $this->logAccess($key_name, 'delete', true);
            
            return true;
        } catch (Exception $e) {
            $this->logAccess($key_name, 'delete', false, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get all API keys (without values)
     */
    public function getAllKeys() {
        try {
            return $this->db->fetchAll("
                SELECT key_name, key_type, is_active, usage_count, last_used, created_at
                FROM api_keys
                ORDER BY created_at DESC
            ");
        } catch (Exception $e) {
            error_log("Get all keys error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Test API key
     */
    public function testKey($key_name, $password) {
        try {
            $key = $this->getKey($key_name, $password);
            
            if (!$key) {
                return ['success' => false, 'message' => 'API key not found'];
            }
            
            // Test with Gemini API
            require_once __DIR__ . '/gemini-api.php';
            $gemini = new GeminiAPI($key);
            
            $test_response = $gemini->generateContent("Say 'API key is working' in 5 words or less.");
            
            if ($test_response) {
                return ['success' => true, 'message' => 'API key is working correctly', 'response' => $test_response];
            } else {
                return ['success' => false, 'message' => 'API key test failed'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Check if password is required
     */
    private function isPasswordRequired() {
        try {
            $setting = $this->db->fetchOne("SELECT setting_value FROM api_key_security WHERE setting_key = 'require_password'");
            return $setting && $setting['setting_value'] == '1';
        } catch (Exception $e) {
            return true; // Default to requiring password
        }
    }
    
    /**
     * Log access attempt
     */
    private function logAccess($key_name, $action, $success, $error_message = null) {
        try {
            $this->db->execute("
                INSERT INTO api_key_access_log (key_name, action, user_ip, user_agent, success, error_message)
                VALUES (?, ?, ?, ?, ?, ?)
            ", [
                $key_name,
                $action,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $success ? 1 : 0,
                $error_message
            ]);
        } catch (Exception $e) {
            error_log("Log access error: " . $e->getMessage());
        }
    }
    
    /**
     * Get access logs
     */
    public function getAccessLogs($key_name = null, $limit = 50) {
        try {
            if ($key_name) {
                return $this->db->fetchAll("
                    SELECT * FROM api_key_access_log 
                    WHERE key_name = ?
                    ORDER BY accessed_at DESC 
                    LIMIT ?
                ", [$key_name, $limit]);
            } else {
                return $this->db->fetchAll("
                    SELECT * FROM api_key_access_log 
                    ORDER BY accessed_at DESC 
                    LIMIT ?
                ", [$limit]);
            }
        } catch (Exception $e) {
            error_log("Get access logs error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if password is set
     */
    public function isPasswordSet() {
        try {
            $hash = $this->db->fetchOne("SELECT setting_value FROM api_key_security WHERE setting_key = 'password_hash'");
            return $hash && !empty($hash['setting_value']);
        } catch (Exception $e) {
            return false;
        }
    }
}
