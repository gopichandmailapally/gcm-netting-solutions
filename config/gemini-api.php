<?php
/**
 * Gemini API Integration Class
 * Handles all interactions with Google's Gemini API
 */

if (!defined('GCM_INIT')) {
    define('GCM_INIT', true);
}

require_once __DIR__ . '/get-api-key.php';

// Get API key from database
if (!defined('GEMINI_API_KEY')) {
    $api_key = getGeminiApiKey();
    if ($api_key) {
        define('GEMINI_API_KEY', $api_key);
    } else {
        define('GEMINI_API_KEY', ''); // Empty if not set
    }
}

class GeminiAPI {
    private $apiKey;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';
    private $maxRetries = 3;
    private $timeout = 30;
    
    public function __construct($apiKey = null) {
        $this->apiKey = $apiKey ?: GEMINI_API_KEY;
        
        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key is not configured. Please set it in admin panel.');
        }
    }
    
    /**
     * Generate content using Gemini API
     * 
     * @param string $prompt The prompt to send to Gemini
     * @param array $options Additional options (temperature, maxTokens, etc.)
     * @return string|false Generated content or false on failure
     */
    public function generateContent($prompt, $options = []) {
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['maxTokens'] ?? 8192;
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
                'topP' => 0.8,
                'topK' => 40
            ]
        ];
        
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $response = $this->makeRequest($data);
                
                if ($response && isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                    return $response['candidates'][0]['content']['parts'][0]['text'];
                }
                
                // If no content but has error
                if (isset($response['error'])) {
                    throw new Exception($response['error']['message'] ?? 'API returned an error');
                }
                
            } catch (Exception $e) {
                $errorMsg = $e->getMessage();
                
                // Log error
                error_log("[Gemini API] Attempt $attempt failed: $errorMsg");
                
                // Retry on rate limit or temporary errors
                if ($attempt < $this->maxRetries) {
                    if (strpos($errorMsg, 'rate limit') !== false || 
                        strpos($errorMsg, '429') !== false ||
                        strpos($errorMsg, 'temporarily unavailable') !== false) {
                        sleep(2 * $attempt); // Exponential backoff
                        continue;
                    }
                }
                
                // On last attempt, throw exception
                if ($attempt === $this->maxRetries) {
                    throw $e;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Make HTTP request to Gemini API
     * 
     * @param array $data Request payload
     * @return array|false Response data or false on failure
     */
    private function makeRequest($data) {
        $url = $this->baseUrl . '?key=' . $this->apiKey;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL error: $error");
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? "HTTP $httpCode error";
            throw new Exception($errorMsg);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Generate multiple content pieces in batch
     * 
     * @param array $prompts Array of prompts
     * @param array $options Generation options
     * @return array Array of generated contents
     */
    public function generateBatch($prompts, $options = []) {
        $results = [];
        $delay = $options['delay'] ?? 1; // Delay between requests in seconds
        
        foreach ($prompts as $index => $prompt) {
            try {
                $content = $this->generateContent($prompt, $options);
                $results[$index] = [
                    'success' => true,
                    'content' => $content
                ];
            } catch (Exception $e) {
                $results[$index] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
            
            // Delay to avoid rate limits (except for last request)
            if ($index < count($prompts) - 1) {
                sleep($delay);
            }
        }
        
        return $results;
    }
    
    /**
     * Test API connection
     * 
     * @return array Test result with success status and message
     */
    public function testConnection() {
        try {
            $response = $this->generateContent("Say 'Hello' in one word.");
            
            if ($response) {
                return [
                    'success' => true,
                    'message' => 'API connection successful',
                    'response' => $response
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'API returned empty response'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}

// Note: create_slug() function is already defined in config.php
