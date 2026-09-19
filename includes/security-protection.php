<?php
/**
 * Comprehensive Website Security Protection
 * Prevents hacking, attacks, and unauthorized access
 */

class SecurityProtection {
    
    /**
     * Initialize all security measures
     */
    public static function init() {
        if (php_sapi_name() === 'cli') return;
        self::setSecureHeaders();
        self::preventSQLInjection();
        self::preventXSS();
        self::enableCSRFProtection();
        self::rateLimiting();
        self::blockSuspiciousRequests();
        self::logSecurityEvents();
    }
    
    /**
     * Set secure HTTP headers
     */
    public static function setSecureHeaders() {
        // Prevent clickjacking
        header("X-Frame-Options: DENY");
        
        // Prevent MIME type sniffing
        header("X-Content-Type-Options: nosniff");
        
        // Enable XSS protection
        header("X-XSS-Protection: 1; mode=block");
        
        // Strict Transport Security (HTTPS only)
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval'; script-src 'self' https: 'unsafe-inline' 'unsafe-eval'; style-src 'self' https: 'unsafe-inline'; font-src 'self' https: data:; img-src 'self' data: https: blob:; frame-ancestors 'none';");
        
        // Referrer Policy
        header("Referrer-Policy: strict-origin-when-cross-origin");
        
        // Permissions Policy
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
        
        // Remove server signature
        header_remove("X-Powered-By");
    }
    
    /**
     * Prevent SQL Injection
     */
    public static function preventSQLInjection() {
        // NOTE: superglobals are NOT sanitized here — sanitize at output time
        // (htmlspecialchars on echo) and use parameterized queries for DB access.
        // Mutating $_GET/$_POST globally breaks JSON APIs and causes double-encoding.

        // Block unambiguous SQL injection sequences in the raw request URI only.
        $dangerous_patterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
        ];

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, urldecode($request_uri))) {
                self::blockRequest('SQL Injection attempt detected');
            }
        }
    }
    
    /**
     * Prevent XSS attacks
     */
    public static function preventXSS() {
        // Block XSS patterns in request
        $xss_patterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/javascript:/i',
            '/on\w+\s*=/i', // onclick, onload, etc.
            '/<embed[^>]*>/i',
            '/<object[^>]*>/i'
        ];
        
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $query_string = $_SERVER['QUERY_STRING'] ?? '';
        
        foreach ($xss_patterns as $pattern) {
            if (preg_match($pattern, $request_uri) || preg_match($pattern, $query_string)) {
                self::blockRequest('XSS attempt detected');
            }
        }
    }
    
    /**
     * Enable CSRF Protection
     */
    public static function enableCSRFProtection() {
        $current_script = $_SERVER['SCRIPT_NAME'] ?? '';

        // Admin pages manage their own CSRF via AdminSecurity — skip entirely.
        // Also skip for JSON/API endpoints that authenticate via session headers.
        $skip_paths = ['/admin/', '/api/'];
        foreach ($skip_paths as $path) {
            if (strpos($current_script, $path) !== false) {
                return;
            }
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Verify on POST requests to public pages
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
                self::blockRequest('CSRF token validation failed');
            }
        }
    }
    
    /**
     * Rate Limiting
     */
    public static function rateLimiting() {
        $current_script = $_SERVER['SCRIPT_NAME'] ?? '';

        // Never rate-limit authenticated admin actions.
        // The AI page generator can legitimately issue hundreds/thousands of requests.
        if (strpos($current_script, '/admin/') !== false) {
            if (session_status() === PHP_SESSION_NONE) {
                // Config boot normally starts the session already, but be defensive.
                @session_start();
            }
            if (!empty($_SESSION['admin_logged_in'])) {
                return;
            }
        }

        $ip = self::getClientIP();
        $cache_file = sys_get_temp_dir() . '/rate_limit_' . md5($ip) . '.txt';
        
        // Default: public traffic
        $max_requests = 100; // Max requests per minute
        $time_window = 60; // 1 minute

        // Slightly higher limit for unauthenticated admin endpoints (login page assets, etc.)
        if (strpos($current_script, '/admin/') !== false) {
            $max_requests = 400;
        }
        
        if (file_exists($cache_file)) {
            $data = json_decode(file_get_contents($cache_file), true);
            $current_time = time();
            
            // Reset if time window passed
            if ($current_time - $data['start_time'] > $time_window) {
                $data = ['start_time' => $current_time, 'count' => 1];
            } else {
                $data['count']++;
                
                // Block if exceeded
                if ($data['count'] > $max_requests) {
                    self::blockRequest('Rate limit exceeded', 429);
                }
            }
        } else {
            $data = ['start_time' => time(), 'count' => 1];
        }
        
        file_put_contents($cache_file, json_encode($data));
    }
    
    /**
     * Block suspicious requests
     */
    public static function blockSuspiciousRequests() {
        $ip = self::getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Block known bad bots
        $bad_bots = ['sqlmap', 'nikto', 'nmap', 'masscan', 'nessus', 'openvas', 'acunetix'];
        foreach ($bad_bots as $bot) {
            if (stripos($user_agent, $bot) !== false) {
                self::blockRequest('Malicious bot detected');
            }
        }
        
        // Block requests with no user agent
        if (empty($user_agent)) {
            self::blockRequest('No user agent');
        }
        
        // Block suspicious file access attempts
        $suspicious_files = ['.env', '.git', 'wp-config.php', 'config.php', '.htaccess', 'phpinfo.php'];
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        foreach ($suspicious_files as $file) {
            if (stripos($request_uri, $file) !== false) {
                self::blockRequest('Suspicious file access attempt');
            }
        }
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvents() {
        // Log to file
        $log_file = __DIR__ . '/../logs/security.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        
        // Log suspicious activity
        $ip = self::getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $request_method = $_SERVER['REQUEST_METHOD'] ?? '';
        
        // Check for suspicious patterns
        $is_suspicious = false;
        $reason = '';
        
        if (preg_match('/(union|select|insert|delete|drop|update|script|iframe)/i', $request_uri)) {
            $is_suspicious = true;
            $reason = 'Suspicious pattern in URI';
        }
        
        if ($is_suspicious) {
            $log_entry = date('Y-m-d H:i:s') . " | IP: $ip | Method: $request_method | URI: $request_uri | UA: $user_agent | Reason: $reason\n";
            @file_put_contents($log_file, $log_entry, FILE_APPEND);
        }
    }
    
    /**
     * Sanitize input
     */
    private static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Strip tags
        $input = strip_tags($input);
        
        // Encode special characters
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
    
    /**
     * Get client IP address
     */
    private static function getClientIP() {
        // REMOTE_ADDR is the only value that cannot be spoofed by the client.
        // Forwarded headers (X-Forwarded-For, CF-Connecting-IP) can be set
        // arbitrarily by any client and must not be trusted for security decisions.
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Block request and log
     */
    private static function blockRequest($reason, $code = 403) {
        $ip = self::getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Log blocked request
        $log_file = __DIR__ . '/../logs/blocked.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            @mkdir($log_dir, 0755, true);
        }
        
        $log_entry = date('Y-m-d H:i:s') . " | IP: $ip | URI: $request_uri | UA: $user_agent | Reason: $reason\n";
        @file_put_contents($log_file, $log_entry, FILE_APPEND);
        
        // Send response
        http_response_code($code);

        $current_script = $_SERVER['SCRIPT_NAME'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $wants_json = (
            stripos($accept, 'application/json') !== false ||
            strpos($current_script, '/api/') !== false ||
            strpos($current_script, '/admin/api/') !== false
        );
        if ($wants_json) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Access Denied: ' . $reason, 'code' => $code]);
            exit;
        }

        die("Access Denied: $reason");
    }
    
    /**
     * Get CSRF token for forms
     */
    public static function getCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Output CSRF token field
     */
    public static function csrfField() {
        $token = self::getCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}

// NOTE: SecurityProtection::init() is called from config.php AFTER session_start()
