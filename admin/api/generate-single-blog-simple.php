<?php
/**
 * Generate Single Blog - Simple Version (No Database)
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once __DIR__ . '/sitemap-functions.php';
require_once '../../includes/gemini-api.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$gemini = new GeminiAPI(GEMINI_API_KEY);

try {
    // Blog topics
    $topics = [
        'Safety net installation guide for homes in Chennai',
        'How to choose the right pigeon nets for your balcony',
        'Benefits of professional bird net installation',
        'Balcony safety tips for families with children and pets',
        'Understanding HDPE safety nets and their advantages',
        'Maintenance guide for long-lasting safety nets',
        'Why UV stabilized nets are important in Chennai climate',
        'Comparing different types of safety nets',
        'Cost-effective safety solutions for high-rise buildings',
        'Pigeon problems and how to solve them permanently',
        'Bird net installation: DIY vs Professional service',
        'Safety net warranty: What you need to know',
        'Monsoon-proof safety nets for Chennai homes',
        'Cricket practice nets for home and commercial spaces',
        'Construction site safety with protective nets',
        'Invisible safety nets: Aesthetic protection solutions',
        'Pet safety on balconies and terraces',
        'Fire safety compliance in safety net installation',
        'Choosing the right color for your safety nets',
        'Quick installation safety nets for urgent needs'
    ];
    
    $topic = $topics[array_rand($topics)];
    
    // Random date 2010–now: makes manually generated blogs look established
    $start_date     = strtotime('2010-01-01');
    $generated_ts   = mt_rand($start_date, time());
    $generated_date = date('Y-m-d H:i:s', $generated_ts);
    
    // Generate blog with AI
    $prompt = "You are a professional blog writer for GCM Netting Solutions, a leading safety net installation company in Chennai, India.

Write a comprehensive blog post about: '$topic'

Requirements:
1. Title: Catchy, SEO-friendly, 50-70 characters
2. Content: 800-1200 words, well-structured with HTML headings (h2, h3), paragraphs, and lists
3. Include: Introduction, main content sections, practical tips, conclusion
4. Mention: GCM Netting Solutions, HDPE material quality, UV stabilization, 3-5 year warranty, professional installation team
5. Add: Call-to-action with contact number +91 99123 99224
6. Tone: Informative, helpful, professional
7. Keywords: Safety nets Chennai, pigeon nets, bird nets, balcony safety (natural placement)

Format as JSON:
{
  \"title\": \"Your SEO-optimized title here\",
  \"content\": \"<p>Full HTML blog content here with h2, h3, p, ul, ol tags...</p>\",
  \"excerpt\": \"A 150-character summary of the blog post\"
}

Make it valuable, engaging, and SEO-optimized.";

    $response = $gemini->generateContent($prompt);

    // Robust JSON extraction — handles text before/after JSON, backtick wrappers, etc.
    $response = trim($response);
    // Strip backtick code fences first
    $response = preg_replace('/^```(?:json)?\s*/s', '', $response);
    $response = preg_replace('/\s*```\s*$/s', '', $response);
    $response = trim($response);
    // Extract from first { to last } (handles any surrounding explanatory text)
    $_fb = strpos($response, '{');
    $_lb = strrpos($response, '}');
    if ($_fb !== false && $_lb !== false && $_lb > $_fb) {
        $response = substr($response, $_fb, $_lb - $_fb + 1);
    }

    $blog_data = json_decode($response, true);

    // Retry once if parse failed — prompt Gemini again with stricter instruction
    if (!$blog_data || !isset($blog_data['title']) || !isset($blog_data['content'])) {
        $retry_prompt = $prompt . "\n\nCRITICAL: Your previous response could not be parsed. Return ONLY valid JSON starting with { and ending with }. No markdown, no explanation.";
        $retry_resp = trim($gemini->generateContent($retry_prompt));
        $retry_resp = preg_replace('/^```(?:json)?\s*/s', '', $retry_resp);
        $retry_resp = preg_replace('/\s*```\s*$/s', '', $retry_resp);
        $_rfb = strpos($retry_resp, '{'); $_rlb = strrpos($retry_resp, '}');
        if ($_rfb !== false && $_rlb !== false && $_rlb > $_rfb) {
            $retry_resp = substr($retry_resp, $_rfb, $_rlb - $_rfb + 1);
        }
        $blog_data = json_decode($retry_resp, true);
        if (!$blog_data || !isset($blog_data['title']) || !isset($blog_data['content'])) {
            throw new Exception('Failed to parse AI response after retry');
        }
    }
    
    $title = trim($blog_data['title']);
    $content = trim($blog_data['content']);
    $excerpt = trim($blog_data['excerpt'] ?? substr(strip_tags($content), 0, 150));
    
    // Generate slug — MUST lowercase first, then strip non-alnum
    $slug = strtolower($title);
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', $slug), '-');
    $slug = substr($slug, 0, 100);
    
    // Manual blog: random historical date makes it look established
    $blog = [
        'id'             => $generated_ts,
        'title'          => $title,
        'content'        => $content,
        'excerpt'        => $excerpt,
        'slug'           => $slug,
        'author'         => 'GCM Netting Solutions',
        'created_at'     => $generated_date,
        'is_published'   => true,
        'views'          => mt_rand(50, 500),
        'featured_image' => SITE_URL . '/uploads/default-blog.jpg'
    ];
    
    // Save to JSON
    $blogs_dir = dirname(dirname(__DIR__)) . '/data/blogs';
    if (!file_exists($blogs_dir)) {
        mkdir($blogs_dir, 0755, true);
    }
    
    $blog_file = $blogs_dir . '/' . $slug . '.json';
    file_put_contents($blog_file, json_encode($blog, JSON_PRETTY_PRINT));

    // Auto-protect: register with AI Content Security
    $_acp = dirname(__DIR__) . '/includes/ai-content-protection.php';
    if (file_exists($_acp) && !class_exists('AIContentProtection')) require_once $_acp;
    if (class_exists('AIContentProtection')) {
        try { (new AIContentProtection())->protect('blog', $slug, $title, 'data/blogs/' . $slug . '.json'); }
        catch (Exception $e) { /* non-fatal */ }
    }

    // Auto-refresh sitemap if enabled in settings
    gcm_auto_refresh_sitemap();

    // Update stats
    $stats_file = $blogs_dir . '/stats.json';
    $stats = file_exists($stats_file) ? json_decode(file_get_contents($stats_file), true) : [];
    $stats['total_generated'] = ($stats['total_generated'] ?? 0) + 1;
    $stats['last_generation'] = date('Y-m-d H:i:s');
    file_put_contents($stats_file, json_encode($stats, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'title' => $title,
        'slug' => $slug,
        'message' => 'Blog generated successfully!'
    ]);
    
} catch (Exception $e) {
    error_log('Blog Generation Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
