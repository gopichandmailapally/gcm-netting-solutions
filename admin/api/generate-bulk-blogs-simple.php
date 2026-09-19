<?php
/**
 * Bulk Blog Generator - Simple Version (No Database)
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once __DIR__ . '/sitemap-functions.php';
require_once '../../includes/gemini-api.php';

// CRITICAL: Extend execution time for large batches
set_time_limit(600); // 10 minutes
ini_set('max_execution_time', '600');
ini_set('memory_limit', '512M');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$blog_count = (int)($_POST['blog_count'] ?? 3);
$blog_count = max(1, min(50, $blog_count)); // 1-50 limit

$gemini = new GeminiAPI(GEMINI_API_KEY);

// Blog topics - 50+ unique topics for variety
$topics = [
    'Complete guide to safety net installation in Chennai',
    'How pigeon nets protect your home and health',
    'Balcony safety solutions for modern apartments',
    'Choosing the right bird nets for your property',
    'HDPE safety nets: Features and benefits',
    'Professional vs DIY safety net installation',
    'Maintaining your safety nets for longevity',
    'UV stabilized nets for Chennai weather',
    'Safety net types and their applications',
    'Protecting children and pets with balcony nets',
    'Bird control solutions for residential buildings',
    'Cost factors in safety net installation',
    'Warranty and quality assurance in safety nets',
    'Monsoon preparation: Weather-resistant safety nets',
    'Invisible safety nets for aesthetic appeal',
    'Cricket practice nets for homes',
    'Construction safety with protective netting',
    'Fire safety compliance in net installation',
    'Quick installation safety solutions',
    'Long-term value of professional safety nets',
    'Pigeon health hazards and prevention',
    'Multi-story building safety net installation',
    'Customized safety solutions for unique spaces',
    'Emergency safety net installation services',
    'Safety net colors and their impact',
    'Top 10 benefits of installing safety nets',
    'How to choose a reliable safety net company',
    'Safety net installation cost breakdown',
    'Common mistakes in DIY safety net installation',
    'Winter safety net maintenance tips',
    'Summer care for UV stabilized nets',
    'Bird netting regulations in Chennai',
    'Case studies: Successful safety net installations',
    'Safety nets for commercial buildings',
    'Apartment complex safety net solutions',
    'Villa and bungalow safety netting',
    'Industrial safety net applications',
    'School and playground safety nets',
    'Hospital and healthcare facility netting',
    'Airport bird control with safety nets',
    'Agricultural bird netting solutions',
    'Warehouse and godown safety nets',
    'Stadium and sports facility netting',
    'Hotel and resort balcony safety',
    'Shopping mall safety installations',
    'Parking lot bird control solutions',
    'Terrace garden protection nets',
    'Swimming pool area safety nets',
    'Rooftop solar panel bird protection',
    'AC outdoor unit bird netting'
];

$generated = 0;
$errors = [];
$generated_blogs = [];

// Create directory
$blogs_dir = dirname(dirname(__DIR__)) . '/data/blogs';
if (!file_exists($blogs_dir)) {
    mkdir($blogs_dir, 0755, true);
}

// Shuffle topics for variety
shuffle($topics);

// Used topics tracker to avoid duplicates in same batch
$used_topics = [];

try {
    for ($i = 0; $i < $blog_count; $i++) {
        // Get unique topic for this batch
        do {
            $topic = $topics[array_rand($topics)];
        } while (in_array($topic, $used_topics) && count($used_topics) < count($topics));
        $used_topics[] = $topic;
        
        // Random date 2010–now: makes bulk-generated blogs look established
        $start_date     = strtotime('2010-01-01');
        $generated_ts   = mt_rand($start_date, time()) + $i;
        $generated_date = date('Y-m-d H:i:s', $generated_ts);
        
        // Add variation to prompt for unique content
        $writing_styles = ['informative', 'conversational', 'technical', 'practical', 'engaging'];
        $approaches = ['problem-solution', 'how-to guide', 'expert tips', 'comprehensive overview', 'case-study based'];
        
        $style = $writing_styles[array_rand($writing_styles)];
        $approach = $approaches[array_rand($approaches)];
        
        $prompt = "You are a professional blog writer for GCM Netting Solutions in Chennai, India.

Write a UNIQUE and ORIGINAL blog post about: '$topic'

Writing Style: {$style}
Approach: {$approach}
Blog #{$i} of {$blog_count} - MUST BE COMPLETELY DIFFERENT from others!

Format as JSON (no markdown):
{
  \"title\": \"SEO-optimized UNIQUE title (50-70 chars) - NOT generic\",
  \"content\": \"<h2>Introduction</h2><p>Opening paragraph...</p><h2>Main Section</h2><p>Content with HTML tags...</p><h2>Tips and Advice</h2><ul><li>Tip 1</li></ul><h2>Conclusion</h2><p>Closing with call-to-action. Contact: +91 99123 99224</p>\",
  \"excerpt\": \"150-char summary\"
}

Requirements:
- 800-1200 words with proper HTML structure
- Include GCM Netting Solutions, HDPE, UV stabilization, 3-5 year warranty
- Professional, {$style} tone
- SEO keywords: safety nets Chennai, pigeon nets, bird nets
- IMPORTANT: Make this content COMPLETELY UNIQUE - different angle, examples, structure";

        try {
            $response = $gemini->generateContent($prompt);
            $response = trim($response);
            $response = preg_replace('/^```json\s*|\s*```$/s', '', $response);
            $response = preg_replace('/^```\s*|\s*```$/s', '', $response);
            
            $blog_data = json_decode($response, true);
            
            if (!$blog_data || !isset($blog_data['title']) || !isset($blog_data['content'])) {
                $errors[] = "Blog #" . ($i + 1) . ": Parse failed";
                continue;
            }
            
            $title = trim($blog_data['title']);
            $content = trim($blog_data['content']);
            $excerpt = trim($blog_data['excerpt'] ?? substr(strip_tags($content), 0, 150));
            
            // Generate slug — MUST lowercase first, then strip non-alnum
            $slug = strtolower($title);
            $slug = trim(preg_replace('/[^a-z0-9]+/', '-', $slug), '-');
            $slug = substr($slug, 0, 100) . '-' . time() . '-' . $i;
            
            // Bulk blog: random historical date + realistic views
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
            
            $blog_file = $blogs_dir . '/' . $slug . '.json';
            file_put_contents($blog_file, json_encode($blog, JSON_PRETTY_PRINT));

            // Auto-protect: register with AI Content Security
            $_acp = dirname(__DIR__) . '/includes/ai-content-protection.php';
            if (file_exists($_acp) && !class_exists('AIContentProtection')) require_once $_acp;
            if (class_exists('AIContentProtection')) {
                try { (new AIContentProtection())->protect('blog', $slug, $title, 'data/blogs/' . $slug . '.json'); }
                catch (\Exception $e) { /* non-fatal */ }
            }

            // Also save to MySQL ai_blogs table (survives file deletions)
            try {
                $db->execute(
                    "INSERT IGNORE INTO ai_blogs (title, slug, content, excerpt, author, featured_image, is_published, views, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)",
                    [$title, $slug, $content, $excerpt, 'GCM Netting Solutions',
                     SITE_URL . '/uploads/default-blog.jpg',
                     $blog['views'], $generated_date]
                );
            } catch (\Exception $e) { /* non-fatal */ }
            
            $generated++;
            $generated_blogs[] = [
                'title' => $title,
                'slug' => $slug
            ];
            
            // Delay (blogs take longer to generate)
            usleep(1000000); // 1 second
            
        } catch (Exception $e) {
            $errors[] = "Blog #" . ($i + 1) . ": " . $e->getMessage();
        }
    }
    
    // Auto-refresh sitemap once after all blogs are saved
    if ($generated > 0) {
        gcm_auto_refresh_sitemap();
    }

    // Update stats
    $stats_file = $blogs_dir . '/stats.json';
    $stats = file_exists($stats_file) ? json_decode(file_get_contents($stats_file), true) : [];
    $stats['total_generated'] = ($stats['total_generated'] ?? 0) + $generated;
    $stats['last_generation'] = date('Y-m-d H:i:s');
    file_put_contents($stats_file, json_encode($stats, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'generated' => $generated,
        'requested' => $blog_count,
        'errors' => $errors,
        'blogs' => $generated_blogs,
        'message' => "Successfully generated $generated blog posts!"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
