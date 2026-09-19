<?php
/**
 * Real Visitor Tracking System
 * Tracks actual visitors with IP geolocation - NO FAKE DATA
 */

class VisitorTracker {
    private $conn;
    private $session_id;
    
    public function __construct() {
        $this->conn = $this->getDBConnection();
        $this->session_id = $this->getOrCreateSession();
    }
    
    private function getDBConnection() {
        static $conn = null;
        if ($conn === null) {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                error_log('Visitor Tracker DB Error: ' . $conn->connect_error);
                return null;
            }
            $conn->set_charset('utf8mb4');
        }
        return $conn;
    }
    
    private function getOrCreateSession() {
        // Check if session exists in cookie
        if (isset($_COOKIE['visitor_session'])) {
            $session_id = $_COOKIE['visitor_session'];
            
            // Verify session exists in database
            $stmt = $this->conn->prepare("SELECT session_id FROM visitor_sessions WHERE session_id = ? AND last_activity >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
            $stmt->bind_param('s', $session_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return $session_id;
            }
        }
        
        // Create new session
        $session_id = $this->generateSessionId();
        $this->createNewSession($session_id);
        
        // Set cookie for 30 minutes
        setcookie('visitor_session', $session_id, time() + 1800, '/');
        
        return $session_id;
    }
    
    private function generateSessionId() {
        return bin2hex(random_bytes(32));
    }
    
    private function createNewSession($session_id) {
        $ip = $this->getClientIP();
        $geo_data = $this->getGeolocation($ip);
        $device_data = $this->getDeviceInfo();
        
        $stmt = $this->conn->prepare("
            INSERT INTO visitor_sessions 
            (session_id, ip_address, country, country_code, region, city, latitude, longitude, 
             timezone, isp, device_type, browser, browser_version, os, os_version, user_agent, 
             referrer, landing_page, first_visit, last_activity, is_bot)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
        ");
        
        $stmt->bind_param('ssssssddssssssssssi',
            $session_id,
            $ip,
            $geo_data['country'],
            $geo_data['country_code'],
            $geo_data['region'],
            $geo_data['city'],
            $geo_data['latitude'],
            $geo_data['longitude'],
            $geo_data['timezone'],
            $geo_data['isp'],
            $device_data['device_type'],
            $device_data['browser'],
            $device_data['browser_version'],
            $device_data['os'],
            $device_data['os_version'],
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_REFERER'] ?? '',
            $_SERVER['REQUEST_URI'] ?? '/',
            $device_data['is_bot']
        );
        
        $stmt->execute();
        $stmt->close();
    }
    
    private function getClientIP() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    private function getGeolocation($ip) {
        // Check cache first
        $stmt = $this->conn->prepare("SELECT * FROM ip_geolocation_cache WHERE ip_address = ? AND expires_at > NOW()");
        $stmt->bind_param('s', $ip);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        // Use free IP geolocation API (ip-api.com - 45 requests per minute)
        $geo_data = [
            'country' => null,
            'country_code' => null,
            'region' => null,
            'city' => null,
            'latitude' => null,
            'longitude' => null,
            'timezone' => null,
            'isp' => null
        ];
        
        try {
            $url = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,region,regionName,city,lat,lon,timezone,isp";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            $response = curl_exec($ch);
            curl_close($ch);
            
            if ($response) {
                $data = json_decode($response, true);
                if ($data && $data['status'] === 'success') {
                    $geo_data = [
                        'country' => $data['country'] ?? null,
                        'country_code' => $data['countryCode'] ?? null,
                        'region' => $data['regionName'] ?? null,
                        'city' => $data['city'] ?? null,
                        'latitude' => $data['lat'] ?? null,
                        'longitude' => $data['lon'] ?? null,
                        'timezone' => $data['timezone'] ?? null,
                        'isp' => $data['isp'] ?? null
                    ];
                    
                    // Cache for 7 days
                    $this->cacheGeolocation($ip, $geo_data);
                }
            }
        } catch (Exception $e) {
            error_log('Geolocation API Error: ' . $e->getMessage());
        }
        
        return $geo_data;
    }
    
    private function cacheGeolocation($ip, $geo_data) {
        $stmt = $this->conn->prepare("
            INSERT INTO ip_geolocation_cache 
            (ip_address, country, country_code, region, city, latitude, longitude, timezone, isp, cached_at, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY))
            ON DUPLICATE KEY UPDATE
            country = VALUES(country),
            country_code = VALUES(country_code),
            region = VALUES(region),
            city = VALUES(city),
            latitude = VALUES(latitude),
            longitude = VALUES(longitude),
            timezone = VALUES(timezone),
            isp = VALUES(isp),
            cached_at = NOW(),
            expires_at = DATE_ADD(NOW(), INTERVAL 7 DAY)
        ");
        
        $stmt->bind_param('sssssddss',
            $ip,
            $geo_data['country'],
            $geo_data['country_code'],
            $geo_data['region'],
            $geo_data['city'],
            $geo_data['latitude'],
            $geo_data['longitude'],
            $geo_data['timezone'],
            $geo_data['isp']
        );
        
        $stmt->execute();
        $stmt->close();
    }
    
    private function getDeviceInfo() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $device_data = [
            'device_type' => 'desktop',
            'browser' => 'Unknown',
            'browser_version' => '',
            'os' => 'Unknown',
            'os_version' => '',
            'is_bot' => 0
        ];
        
        // Detect bots
        $bot_patterns = ['bot', 'crawl', 'spider', 'slurp', 'mediapartners', 'facebookexternalhit', 'whatsapp'];
        foreach ($bot_patterns as $pattern) {
            if (stripos($user_agent, $pattern) !== false) {
                $device_data['is_bot'] = 1;
                $device_data['device_type'] = 'bot';
                return $device_data;
            }
        }
        
        // Detect device type
        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile|opera mini/i', $user_agent)) {
            $device_data['device_type'] = 'mobile';
        } elseif (preg_match('/tablet|ipad|playbook|silk/i', $user_agent)) {
            $device_data['device_type'] = 'tablet';
        }
        
        // Detect browser
        if (preg_match('/MSIE|Trident/i', $user_agent)) {
            $device_data['browser'] = 'Internet Explorer';
        } elseif (preg_match('/Edge/i', $user_agent)) {
            $device_data['browser'] = 'Edge';
        } elseif (preg_match('/Chrome/i', $user_agent)) {
            $device_data['browser'] = 'Chrome';
            if (preg_match('/Chrome\/([0-9.]+)/i', $user_agent, $matches)) {
                $device_data['browser_version'] = $matches[1];
            }
        } elseif (preg_match('/Safari/i', $user_agent)) {
            $device_data['browser'] = 'Safari';
        } elseif (preg_match('/Firefox/i', $user_agent)) {
            $device_data['browser'] = 'Firefox';
            if (preg_match('/Firefox\/([0-9.]+)/i', $user_agent, $matches)) {
                $device_data['browser_version'] = $matches[1];
            }
        }
        
        // Detect OS
        if (preg_match('/Windows NT ([0-9.]+)/i', $user_agent, $matches)) {
            $device_data['os'] = 'Windows';
            $device_data['os_version'] = $matches[1];
        } elseif (preg_match('/Mac OS X ([0-9_]+)/i', $user_agent, $matches)) {
            $device_data['os'] = 'Mac OS';
            $device_data['os_version'] = str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/Android ([0-9.]+)/i', $user_agent, $matches)) {
            $device_data['os'] = 'Android';
            $device_data['os_version'] = $matches[1];
        } elseif (preg_match('/iPhone OS ([0-9_]+)/i', $user_agent, $matches)) {
            $device_data['os'] = 'iOS';
            $device_data['os_version'] = str_replace('_', '.', $matches[1]);
        } elseif (preg_match('/Linux/i', $user_agent)) {
            $device_data['os'] = 'Linux';
        }
        
        return $device_data;
    }
    
    public function trackPageView($page_url, $page_title = '') {
        if (!$this->conn || !$this->session_id) return;
        
        // Update session last activity and page view count
        $stmt = $this->conn->prepare("
            UPDATE visitor_sessions 
            SET last_activity = NOW(), 
                total_page_views = total_page_views + 1 
            WHERE session_id = ?
        ");
        $stmt->bind_param('s', $this->session_id);
        $stmt->execute();
        $stmt->close();
        
        // Insert page view
        $stmt = $this->conn->prepare("
            INSERT INTO page_views (session_id, page_url, page_title, referrer, viewed_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $stmt->bind_param('ssss', $this->session_id, $page_url, $page_title, $referrer);
        $stmt->execute();
        $stmt->close();
    }
    
    public function trackAction($action_type, $action_data = []) {
        if (!$this->conn || !$this->session_id) return;
        
        $stmt = $this->conn->prepare("
            INSERT INTO visitor_actions (session_id, action_type, action_data, page_url, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $page_url = $_SERVER['REQUEST_URI'] ?? '';
        $action_json = json_encode($action_data);
        
        $stmt->bind_param('ssss', $this->session_id, $action_type, $action_json, $page_url);
        $stmt->execute();
        $stmt->close();
    }
}

// Auto-initialize tracker on every page load
if (!isset($visitor_tracker)) {
    $visitor_tracker = new VisitorTracker();
    $current_url = $_SERVER['REQUEST_URI'] ?? '/';
    $page_title = $page_title ?? '';
    $visitor_tracker->trackPageView($current_url, $page_title);
}
