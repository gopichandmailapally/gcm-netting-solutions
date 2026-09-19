<?php
/**
 * Add Video API - With AI metadata generation
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, only log them
ini_set('log_errors', 1);

define('GCM_INIT', true);

// Set JSON header early
header('Content-Type: application/json');

try {
    require_once '../../config/config.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Config file error: ' . $e->getMessage()]);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please login']);
    exit;
}

try {
    $youtube_url = $_POST['youtube_url'] ?? '';
    $category = $_POST['category'] ?? 'General';
    $use_ai = isset($_POST['use_ai']);
    
    if (empty($youtube_url)) {
        throw new Exception('YouTube URL is required');
    }
    
    // Extract YouTube video ID
    $video_id = '';
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $youtube_url, $matches)) {
        $video_id = $matches[1];
    } else {
        throw new Exception('Invalid YouTube URL');
    }
    
    // Get video info from YouTube (basic info from URL)
    $oembed_url = "https://www.youtube.com/oembed?url=" . urlencode($youtube_url) . "&format=json";
    $oembed_data = @file_get_contents($oembed_url);
    
    if (!$oembed_data) {
        throw new Exception('Could not fetch video information from YouTube');
    }
    
    $video_info = json_decode($oembed_data, true);
    $youtube_title = $video_info['title'] ?? 'Untitled Video';
    
    // Generate AI title and description if requested
    if ($use_ai && defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
        try {
            $ai_prompt = "You are a professional SEO content writer for a safety nets installation business in Chennai, India.

Category: $category
Original YouTube Title: $youtube_title

Generate:
1. An SEO-optimized title (50-60 characters)
2. A compelling description (150-200 words)

The content should:
- Be professional and engaging
- Include relevant keywords naturally
- Appeal to Chennai homeowners
- Highlight safety and quality
- Be specific to the category

Format your response EXACTLY like this:
TITLE: [your title here]
DESCRIPTION: [your description here]";

            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . OPENAI_API_KEY
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $ai_prompt]
                ],
                'temperature' => 0.7,
                'max_tokens' => 400
            ]));
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code !== 200) {
                error_log('OpenAI API Error: ' . $response);
                // Fall back to enhanced title instead of failing
                $title = $youtube_title . " | GCM Netting Solutions Chennai";
                $description = "Watch our professional team demonstrate $category services in Chennai. GCM Netting Solutions provides quality safety net installation across Chennai with expert technicians and premium materials. Original video: $youtube_title";
            } else {
                $ai_response = json_decode($response, true);
                $ai_content = $ai_response['choices'][0]['message']['content'] ?? '';
                
                // Parse AI response
                if (preg_match('/TITLE:\s*(.+?)(?:\n|$)/i', $ai_content, $title_matches)) {
                    $title = trim($title_matches[1]);
                } else {
                    $title = $youtube_title . " | GCM Netting Solutions Chennai";
                }
                
                if (preg_match('/DESCRIPTION:\s*(.+)/is', $ai_content, $desc_matches)) {
                    $description = trim($desc_matches[1]);
                } else {
                    $description = "Watch our professional team demonstrate $category services in Chennai. GCM Netting Solutions provides quality safety net installation across Chennai with expert technicians and premium materials.";
                }
            }
        } catch (Exception $ai_error) {
            // AI failed, use enhanced title
            error_log('AI Error: ' . $ai_error->getMessage());
            $title = $youtube_title . " | GCM Netting Solutions Chennai";
            $description = "Watch our professional team demonstrate $category services in Chennai. GCM Netting Solutions provides quality safety net installation across Chennai with expert technicians and premium materials. Original video: $youtube_title";
        }
    } else {
        // Use manual input OR enhanced YouTube title if AI not available
        if (!empty($_POST['manual_title'])) {
            $title = $_POST['manual_title'];
        } else {
            // AI not available or not requested, enhance the YouTube title
            $title = $youtube_title . " | GCM Netting Solutions Chennai";
        }
        
        if (!empty($_POST['manual_description'])) {
            $description = $_POST['manual_description'];
        } else {
            // Generate basic SEO description
            $description = "Watch our professional team demonstrate $category services in Chennai. GCM Netting Solutions provides quality safety net installation across Chennai with expert technicians and premium materials. Original video: $youtube_title";
        }
    }
    
    // Create video object
    $video = [
        'id' => uniqid('vid_'),
        'youtube_id' => $video_id,
        'title' => $title,
        'description' => $description,
        'category' => $category,
        'duration' => '', // Can be fetched from YouTube API if needed
        'views' => rand(50, 500), // Initial random views
        'created_at' => date('Y-m-d H:i:s'),
        'source' => $use_ai ? 'ai_enhanced' : 'manual'
    ];
    
    // Save to JSON file
    $videos_dir = dirname(dirname(__DIR__)) . '/data/videos';
    if (!file_exists($videos_dir)) {
        mkdir($videos_dir, 0755, true);
    }
    
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
    $slug = substr($slug, 0, 50) . '-' . time();
    $video_file = $videos_dir . '/' . $slug . '.json';
    
    file_put_contents($video_file, json_encode($video, JSON_PRETTY_PRINT));

    // Auto-protect: register with AI Content Security (uploaded content group)
    $_acp = dirname(__DIR__) . '/includes/ai-content-protection.php';
    if (file_exists($_acp) && !class_exists('AIContentProtection')) require_once $_acp;
    if (class_exists('AIContentProtection')) {
        try { (new AIContentProtection())->protect('video', $video['id'], $title, 'data/videos/' . $slug . '.json'); }
        catch (\Exception $e) { /* non-fatal */ }
    }

    // Update stats
    $stats_file = $videos_dir . '/stats.json';
    $stats = file_exists($stats_file) ? json_decode(file_get_contents($stats_file), true) : [];
    $stats['total_added'] = ($stats['total_added'] ?? 0) + 1;
    $stats['last_added'] = date('Y-m-d H:i:s');
    file_put_contents($stats_file, json_encode($stats, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'message' => 'Video added successfully!',
        'video' => $video
    ]);
    
} catch (Exception $e) {
    error_log('Add Video Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
