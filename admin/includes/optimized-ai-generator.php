<?php
/**
 * Optimized AI Content Generator
 * Faster generation with caching, batch processing, and parallel requests
 */

if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}

class OptimizedAIGenerator {
    private $contentSecurity;
    private $api_key;
    private $max_parallel_requests = 3;
    private $request_timeout = 30;
    
    public function __construct() {
        require_once __DIR__ . '/content-security.php';
        $this->contentSecurity = new ContentSecurity();
    }
    
    /**
     * Initialize with API key
     */
    public function init($api_key = null) {
        if ($api_key) {
            $this->api_key = $api_key;
        } else {
            // Try to get from secure storage
            $this->api_key = $this->contentSecurity->getAPIKey();
        }
        
        return $this->api_key !== null;
    }
    
    /**
     * Generate content with caching
     */
    public function generateContent($prompt, $use_cache = true, $cache_ttl = 3600) {
        // Check cache first
        if ($use_cache) {
            $cache_key = 'ai_content_' . md5($prompt);
            $cached = $this->contentSecurity->getCachedContent($cache_key);
            if ($cached) {
                return [
                    'success' => true,
                    'content' => $cached,
                    'cached' => true,
                    'tokens_used' => 0
                ];
            }
        }
        
        // Generate new content
        $result = $this->callGeminiAPI($prompt);
        
        // Cache successful results
        if ($result['success'] && $use_cache) {
            $cache_key = 'ai_content_' . md5($prompt);
            $this->contentSecurity->cacheGeneratedContent($cache_key, $result['content'], $cache_ttl);
        }
        
        return $result;
    }
    
    /**
     * Batch generate multiple content pieces (faster)
     */
    public function batchGenerate($prompts, $use_cache = true) {
        $results = [];
        $pending_prompts = [];
        
        // Check cache for all prompts first
        foreach ($prompts as $key => $prompt) {
            if ($use_cache) {
                $cache_key = 'ai_content_' . md5($prompt);
                $cached = $this->contentSecurity->getCachedContent($cache_key);
                if ($cached) {
                    $results[$key] = [
                        'success' => true,
                        'content' => $cached,
                        'cached' => true
                    ];
                    continue;
                }
            }
            $pending_prompts[$key] = $prompt;
        }
        
        // Generate remaining in batches
        if (!empty($pending_prompts)) {
            $batches = array_chunk($pending_prompts, $this->max_parallel_requests, true);
            
            foreach ($batches as $batch) {
                $batch_results = $this->parallelGenerate($batch);
                
                foreach ($batch_results as $key => $result) {
                    $results[$key] = $result;
                    
                    // Cache successful results
                    if ($result['success'] && $use_cache) {
                        $cache_key = 'ai_content_' . md5($pending_prompts[$key]);
                        $this->contentSecurity->cacheGeneratedContent($cache_key, $result['content']);
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Generate multiple prompts in parallel (faster)
     */
    private function parallelGenerate($prompts) {
        $multi_handle = curl_multi_init();
        $curl_handles = [];
        $results = [];
        
        // Initialize all requests
        foreach ($prompts as $key => $prompt) {
            $ch = $this->initCurlHandle($prompt);
            curl_multi_add_handle($multi_handle, $ch);
            $curl_handles[$key] = $ch;
        }
        
        // Execute all requests in parallel
        $running = null;
        do {
            curl_multi_exec($multi_handle, $running);
            curl_multi_select($multi_handle);
        } while ($running > 0);
        
        // Collect results
        foreach ($curl_handles as $key => $ch) {
            $response = curl_multi_getcontent($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            $results[$key] = $this->parseAPIResponse($response, $http_code);
            
            curl_multi_remove_handle($multi_handle, $ch);
            curl_close($ch);
        }
        
        curl_multi_close($multi_handle);
        
        return $results;
    }
    
    /**
     * Initialize CURL handle for API request
     */
    private function initCurlHandle($prompt) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $this->api_key;
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 2048,
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->request_timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        return $ch;
    }
    
    /**
     * Call Gemini API (single request)
     */
    private function callGeminiAPI($prompt) {
        if (!$this->api_key) {
            return [
                'success' => false,
                'error' => 'API key not configured'
            ];
        }
        
        $ch = $this->initCurlHandle($prompt);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            return [
                'success' => false,
                'error' => 'Connection error: ' . $curl_error
            ];
        }
        
        return $this->parseAPIResponse($response, $http_code);
    }
    
    /**
     * Parse API response
     */
    private function parseAPIResponse($response, $http_code) {
        if ($http_code !== 200) {
            $error_data = json_decode($response, true);
            return [
                'success' => false,
                'error' => $error_data['error']['message'] ?? 'API request failed',
                'http_code' => $http_code
            ];
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'success' => false,
                'error' => 'Invalid API response format'
            ];
        }
        
        $content = $data['candidates'][0]['content']['parts'][0]['text'];
        
        // Clean up markdown artifacts
        $content = $this->cleanMarkdown($content);
        
        return [
            'success' => true,
            'content' => $content,
            'tokens_used' => $data['usageMetadata']['totalTokenCount'] ?? 0,
            'cached' => false
        ];
    }
    
    /**
     * Clean markdown artifacts from content
     */
    private function cleanMarkdown($content) {
        // Remove markdown code blocks
        $content = preg_replace('/```(?:html|php|css|javascript)?\s*\n/i', '', $content);
        $content = preg_replace('/\n```\s*$/i', '', $content);
        $content = str_replace('```', '', $content);
        
        // Clean up extra whitespace
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        return trim($content);
    }
    
    /**
     * Generate page content with template
     */
    public function generatePageContent($service_name, $area_name, $word_count = 800) {
        $prompt = $this->buildPagePrompt($service_name, $area_name, $word_count);
        return $this->generateContent($prompt, true, 7200); // Cache for 2 hours
    }
    
    /**
     * Build optimized prompt for page generation
     */
    private function buildPagePrompt($service_name, $area_name, $word_count) {
        return "Write comprehensive, SEO-optimized content about {$service_name} services in {$area_name}, Chennai.

Requirements:
- Length: {$word_count} words
- Include benefits, features, and why choose us
- Add local area references
- Professional tone
- No markdown formatting
- Ready-to-use HTML content

Generate the content now:";
    }
    
    /**
     * Get generation statistics
     */
    public function getStats() {
        $cache_dir = dirname(dirname(__DIR__)) . '/cache/generation';
        $cache_count = 0;
        
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '/*.cache');
            $cache_count = count($files);
        }
        
        return [
            'cached_items' => $cache_count,
            'max_parallel' => $this->max_parallel_requests,
            'timeout' => $this->request_timeout
        ];
    }
    
    /**
     * Estimate generation time
     */
    public function estimateTime($page_count, $use_cache = true) {
        $avg_time_per_page = 8; // seconds with cache
        $avg_time_no_cache = 15; // seconds without cache
        
        $time = $use_cache ? $avg_time_per_page : $avg_time_no_cache;
        
        // Account for parallel processing
        $batches = ceil($page_count / $this->max_parallel_requests);
        $total_seconds = $batches * $time;
        
        return [
            'seconds' => $total_seconds,
            'minutes' => round($total_seconds / 60, 1),
            'formatted' => $this->formatTime($total_seconds)
        ];
    }
    
    /**
     * Format time duration
     */
    private function formatTime($seconds) {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $secs = $seconds % 60;
            return $minutes . ' min ' . $secs . ' sec';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . ' hr ' . $minutes . ' min';
        }
    }
    
    /**
     * Test API connection
     */
    public function testConnection() {
        $test_prompt = "Say 'API connection successful' in exactly those words.";
        $result = $this->callGeminiAPI($test_prompt);
        
        return [
            'success' => $result['success'],
            'message' => $result['success'] ? 'API connection successful!' : ($result['error'] ?? 'Connection failed'),
            'response_time' => $result['success'] ? 'Fast' : 'N/A'
        ];
    }
}
