<?php
/**
 * Gemini API Integration
 * Handles communication with Google Gemini AI
 */

class GeminiAPI {
    private $apiKey;
    
    // List of models to try (in order of preference) - Updated from API list
    private $models = [
        ['name' => 'gemini-2.0-flash-001', 'version' => 'v1beta'],
        ['name' => 'gemini-2.5-flash', 'version' => 'v1beta'],
        ['name' => 'gemini-2.0-flash', 'version' => 'v1beta'],
        ['name' => 'gemini-2.5-pro', 'version' => 'v1beta']
    ];
    
    private $baseUrl = 'https://generativelanguage.googleapis.com/';
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    private $lastUsedModel = '';
    
    /**
     * Generate content using Gemini AI with model fallback
     */
    public function generateContent($prompt, $maxTokens = 2048) {
        $lastError = '';

        // Try each model until one works
        foreach ($this->models as $modelInfo) {
            // Up to 3 attempts per model on rate-limit / server-overload errors
            for ($attempt = 0; $attempt < 3; $attempt++) {
                try {
                    $result = $this->tryGenerateWithModel($modelInfo['name'], $modelInfo['version'], $prompt, $maxTokens);
                    $this->lastUsedModel = $modelInfo['name'];
                    return $result;
                } catch (Exception $e) {
                    $lastError = $e->getMessage();
                    $lower     = strtolower($lastError);

                    // Model not found / not supported → try next model immediately
                    if (strpos($lower, 'not found') !== false || strpos($lower, '404') !== false ||
                        strpos($lower, 'not supported') !== false) {
                        break; // break the attempt loop, continue model loop
                    }

                    // Rate limit / server overload → wait and retry same model
                    $isRateLimit = strpos($lower, '429') !== false || strpos($lower, 'quota') !== false ||
                                   strpos($lower, 'rate') !== false || strpos($lower, 'overload') !== false ||
                                   strpos($lower, 'resource_exhausted') !== false;
                    if ($isRateLimit && $attempt < 2) {
                        $wait = ($attempt + 1) * 8; // 8s, 16s
                        sleep($wait);
                        continue; // retry same model
                    }

                    // Auth / permanent error or exhausted retries → try next model
                    break;
                }
            }
        }

        // All models + retries exhausted
        throw new Exception('All Gemini models failed. Last error: ' . $lastError);
    }
    
    /**
     * Get the last successfully used model
     */
    public function getLastUsedModel() {
        return $this->lastUsedModel;
    }
    
    /**
     * Try to generate content with a specific model
     */
    private function tryGenerateWithModel($model, $apiVersion, $prompt, $maxTokens) {
        $url = $this->baseUrl . $apiVersion . '/models/' . $model . ':generateContent?key=' . $this->apiKey;
        
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
                'maxOutputTokens' => $maxTokens,
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);        // 90s hard cap per request
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); // 15s connect timeout

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception("Network error: " . $curlError);
        }
        
        if ($httpCode !== 200) {
            // Try to parse error response
            $errorData = json_decode($response, true);
            if (isset($errorData['error']['message'])) {
                throw new Exception($errorData['error']['message']);
            }
            throw new Exception("API Error (HTTP $httpCode): " . substr($response, 0, 200));
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        }
        
        if (isset($result['error'])) {
            throw new Exception("API Error: " . $result['error']['message']);
        }
        
        throw new Exception("Invalid response from Gemini API. Response: " . substr($response, 0, 200));
    }
    
    /**
     * Test API connection
     */
    public function testConnection() {
        try {
            $response = $this->generateContent("Say 'API connection successful' in one sentence.");
            return ['success' => true, 'message' => 'API connected successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
