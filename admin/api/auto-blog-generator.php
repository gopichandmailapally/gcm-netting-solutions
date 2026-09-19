<?php
/**
 * Automated Blog Generator
 * Creates 1-5 unique blogs daily using AI
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/gemini-api.php';

ignore_user_abort(true); // Keep running even if the calling connection drops (pseudo-cron)
set_time_limit(180);

if (session_status() === PHP_SESSION_NONE) {
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

/* ── Auth: admin session OR pseudo-cron token ─────────────────────── */
$from_session = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$from_token   = isset($_GET['token']) && defined('PSEUDO_CRON_TOKEN') && hash_equals(PSEUDO_CRON_TOKEN, (string)$_GET['token']);

if (!$from_session && !$from_token) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

class AutoBlogGenerator {
    private $db;
    private $gemini;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->initGemini();
    }
    
    private function initGemini() {
        try {
            $api_key = $this->db->fetchOne("SELECT config_value FROM site_config WHERE config_key = 'gemini_api_key' LIMIT 1");
            $key = '';
            if ($api_key && !empty($api_key['config_value'])) {
                $key = (string)$api_key['config_value'];
            } elseif (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY)) {
                $key = (string)GEMINI_API_KEY;
            }
            if (empty($key)) throw new Exception('Gemini API key not configured');
            $this->gemini = new GeminiAPI($key);
        } catch (Exception $e) {
            error_log("Gemini API initialization error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate blogs for today
     */
    public function generateDailyBlogs($count = null) {
        try {
            // Get settings
            $settings = $this->getSettings();
            
            if (!$settings['enable_auto_generation']) {
                return ['status' => 'disabled', 'message' => 'Auto-generation is disabled'];
            }
            
            $blogs_to_generate = $count ?? $settings['daily_blog_count'];
            
            // Check if already generated today
            $today = date('Y-m-d');
            $schedule = $this->db->fetchOne("SELECT * FROM blog_generation_schedule WHERE schedule_date = ?", [$today]);
            
            if ($schedule && $schedule['status'] == 'completed') {
                return ['status' => 'already_generated', 'message' => 'Blogs already generated today'];
            }
            
            // Create or update schedule
            if (!$schedule) {
                $this->db->execute("INSERT INTO blog_generation_schedule (schedule_date, blogs_to_generate, status) VALUES (?, ?, ?)",
                    [$today, $blogs_to_generate, 'in_progress']
                );
                $schedule_id = $this->db->lastInsertId();
            } else {
                $schedule_id = $schedule['id'];
                $this->db->execute("UPDATE blog_generation_schedule SET status = 'in_progress' WHERE id = ?", [$schedule_id]);
            }
            
            $generated_blogs = [];
            $errors = [];
            
            // Generate blogs
            for ($i = 0; $i < $blogs_to_generate; $i++) {
                try {
                    $blog = $this->generateSingleBlog();
                    if ($blog) {
                        $generated_blogs[] = $blog;
                    }
                    
                    // Sleep to avoid rate limiting
                    if ($i < $blogs_to_generate - 1) {
                        sleep(2);
                    }
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                    error_log("Blog generation error: " . $e->getMessage());
                }
            }
            
            // Update schedule
            $this->db->execute("UPDATE blog_generation_schedule SET blogs_generated = ?, status = ?, completed_at = NOW() WHERE id = ?",
                [count($generated_blogs), count($generated_blogs) > 0 ? 'completed' : 'failed', $schedule_id]
            );
            
            // Update last generation date
            $this->db->execute("UPDATE blog_settings SET setting_value = ? WHERE setting_key = 'last_generation_date'", [$today]);
            
            return [
                'status' => 'success',
                'generated' => count($generated_blogs),
                'blogs' => $generated_blogs,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            error_log("Daily blog generation error: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Generate a single unique blog
     */
    private function generateSingleBlog() {
        // Get random unused or least used topic
        $topic = $this->db->fetchOne("
            SELECT * FROM blog_topics 
            WHERE is_active = 1 
            ORDER BY used_count ASC, RAND() 
            LIMIT 1
        ");
        
        if (!$topic) {
            throw new Exception("No topics available");
        }
        
        // Generate blog title
        $title = str_replace('{keyword}', $topic['keyword'], $topic['topic_template']);
        $slug = $this->generateSlug($title);
        
        // Check if slug exists
        $existing = $this->db->fetchOne("SELECT id FROM blog_posts WHERE slug = ?", [$slug]);
        if ($existing) {
            // Try another topic
            return $this->generateSingleBlog();
        }
        
        // Generate unique content using AI
        $content = $this->generateBlogContent($topic, $title);
        
        if (!$content) {
            throw new Exception("Failed to generate content for: " . $title);
        }
        
        // Extract excerpt (first 200 characters)
        $excerpt = substr(strip_tags($content), 0, 200) . '...';
        
        // Calculate word count and reading time
        $word_count = str_word_count(strip_tags($content));
        $reading_time = ceil($word_count / 200); // Average reading speed
        
        // Generate meta tags
        $meta_title = $this->generateMetaTitle($title, $topic['keyword']);
        $meta_description = $this->generateMetaDescription($topic, $title);
        $meta_keywords = $this->generateMetaKeywords($topic);
        
        // Calculate SEO score
        $seo_score = $this->calculateSEOScore($content, $topic['keyword'], $word_count);
        
        // Insert blog post
        $this->db->execute("
            INSERT INTO blog_posts 
            (title, slug, keyword, category, content, excerpt, meta_title, meta_description, meta_keywords, 
             word_count, reading_time, status, is_published, publish_date, seo_score)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), ?)
        ", [
            $title,
            $slug,
            $topic['keyword'],
            $topic['category'],
            $content,
            $excerpt,
            $meta_title,
            $meta_description,
            $meta_keywords,
            $word_count,
            $reading_time,
            'published', // Auto-publish
            $seo_score
        ]);
        
        $blog_id = $this->db->lastInsertId();
        
        // Update topic usage
        $this->db->execute("UPDATE blog_topics SET used_count = used_count + 1, last_used = NOW() WHERE id = ?", [$topic['id']]);
        
        return [
            'id' => $blog_id,
            'title' => $title,
            'slug' => $slug,
            'keyword' => $topic['keyword'],
            'word_count' => $word_count,
            'seo_score' => $seo_score
        ];
    }
    
    /**
     * Generate unique blog content using AI
     */
    private function generateBlogContent($topic, $title) {
        $keyword = $topic['keyword'];
        $category = $topic['category'];
        $type = $topic['topic_type'];
        
        // Get related content from website
        $related_info = $this->getRelatedWebsiteContent($keyword);
        
        $prompt = "Write a comprehensive, SEO-optimized blog post for a safety nets company in Chennai.

**Title:** {$title}

**Primary Keyword:** {$keyword}
**Category:** {$category}
**Blog Type:** {$type}

**Requirements:**
1. **Word Count:** 1200-2000 words
2. **Unique Content:** 100% original, no plagiarism
3. **SEO Optimized:** 
   - Use keyword naturally (1-2% density)
   - Include keyword in first paragraph
   - Use H2 and H3 headings
   - Include LSI keywords
4. **Structure:**
   - Engaging introduction
   - 5-7 main sections with H2 headings
   - Bullet points and numbered lists
   - Practical tips and advice
   - Strong conclusion with CTA
5. **Tone:** Professional, helpful, informative
6. **Location:** Focus on Chennai context
7. **Company:** GCM Netting Solutions (mention naturally)

**Related Information from Website:**
{$related_info}

**Content Focus Based on Type:**
";

        switch ($type) {
            case 'service':
                $prompt .= "- Explain the service in detail\n- Benefits and features\n- Why choose this service\n- Service process\n- Pricing factors";
                break;
            case 'installation':
                $prompt .= "- Step-by-step installation guide\n- Tools and materials needed\n- Safety precautions\n- Professional vs DIY\n- Installation timeline";
                break;
            case 'maintenance':
                $prompt .= "- Maintenance schedule\n- Cleaning tips\n- Common issues and solutions\n- When to replace\n- Cost of maintenance";
                break;
            case 'benefits':
                $prompt .= "- List 10+ benefits\n- Real-world examples\n- Cost savings\n- Safety improvements\n- Long-term value";
                break;
            case 'comparison':
                $prompt .= "- Compare different options\n- Pros and cons\n- Price comparison\n- Best use cases\n- Recommendation";
                break;
            case 'guide':
                $prompt .= "- Complete buying guide\n- Factors to consider\n- Quality indicators\n- Budget planning\n- Vendor selection";
                break;
            case 'tips':
                $prompt .= "- 7-10 expert tips\n- Practical advice\n- Common mistakes to avoid\n- Best practices\n- Pro recommendations";
                break;
            case 'faq':
                $prompt .= "- 10+ frequently asked questions\n- Detailed answers\n- Address common concerns\n- Technical details\n- Practical solutions";
                break;
        }
        
        $prompt .= "\n\n**Format:** HTML with proper headings (h2, h3), paragraphs, lists, and strong tags for emphasis.
**Important:** Make content highly unique and valuable. Include specific details about Chennai locations, climate, and local context.

Generate the complete blog post now:";
        
        try {
            $content = $this->gemini->generateContent($prompt);
            
            // Clean content
            $content = $this->cleanBlogContent($content);
            
            return $content;
        } catch (Exception $e) {
            error_log("AI content generation error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get related content from website
     */
    private function getRelatedWebsiteContent($keyword) {
        $slug = strtolower(str_replace(' ', '-', $keyword));
        
        // Try to get content from existing pages
        $info = "Company: GCM Netting Solutions - Leading safety nets provider in Chennai\n";
        $info .= "Services: Installation, Maintenance, Repair of {$keyword}\n";
        $info .= "Coverage: All areas in Chennai and surrounding regions\n";
        $info .= "Experience: 15+ years in the industry\n";
        $info .= "Quality: Premium materials, professional installation\n";
        $info .= "Contact: Available 24/7 for emergency services\n";
        
        return $info;
    }
    
    /**
     * Clean and format blog content
     */
    private function cleanBlogContent($content) {
        // Remove markdown code blocks
        $content = preg_replace('/```html\s*/i', '', $content);
        $content = preg_replace('/```\s*$/s', '', $content);
        $content = trim($content, '`');
        
        // Ensure proper HTML structure
        if (strpos($content, '<h2>') === false) {
            // Content might be plain text, wrap in paragraphs
            $paragraphs = explode("\n\n", $content);
            $content = '';
            foreach ($paragraphs as $para) {
                if (!empty(trim($para))) {
                    $content .= '<p>' . trim($para) . '</p>' . "\n";
                }
            }
        }
        
        return $content;
    }
    
    /**
     * Generate SEO-friendly slug
     */
    private function generateSlug($title) {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
    
    /**
     * Generate meta title
     */
    private function generateMetaTitle($title, $keyword) {
        if (strlen($title) <= 60) {
            return $title . ' | GCM Netting Solutions';
        }
        return substr($title, 0, 50) . '... | GCM Netting Solutions';
    }
    
    /**
     * Generate meta description
     */
    private function generateMetaDescription($topic, $title) {
        $keyword = $topic['keyword'];
        $descriptions = [
            "Discover everything about {$keyword} in Chennai. Expert tips, installation guides, and professional services from GCM Netting Solutions.",
            "Complete guide to {$keyword} - benefits, installation, maintenance, and more. Trusted by 10,000+ customers in Chennai.",
            "Looking for {$keyword} in Chennai? Read our comprehensive guide covering installation, benefits, and expert recommendations.",
            "Expert insights on {$keyword} from GCM Netting Solutions. Learn about installation, maintenance, costs, and best practices."
        ];
        
        return $descriptions[array_rand($descriptions)];
    }
    
    /**
     * Generate meta keywords
     */
    private function generateMetaKeywords($topic) {
        $keyword = $topic['keyword'];
        $keywords = [
            $keyword,
            $keyword . ' Chennai',
            $keyword . ' installation',
            $keyword . ' price',
            $keyword . ' near me',
            'GCM Netting Solutions',
            'safety nets Chennai',
            $topic['category']
        ];
        
        return implode(', ', $keywords);
    }
    
    /**
     * Calculate SEO score
     */
    private function calculateSEOScore($content, $keyword, $word_count) {
        $score = 0;
        
        // Word count (max 20 points)
        if ($word_count >= 1200) $score += 20;
        else $score += ($word_count / 1200) * 20;
        
        // Keyword in content (20 points)
        $keyword_count = substr_count(strtolower($content), strtolower($keyword));
        $keyword_density = ($keyword_count / $word_count) * 100;
        if ($keyword_density >= 1 && $keyword_density <= 2) $score += 20;
        else if ($keyword_density > 0) $score += 10;
        
        // Headings (20 points)
        $h2_count = substr_count($content, '<h2>');
        $h3_count = substr_count($content, '<h3>');
        if ($h2_count >= 3) $score += 10;
        if ($h3_count >= 2) $score += 10;
        
        // Lists (10 points)
        if (strpos($content, '<ul>') !== false || strpos($content, '<ol>') !== false) $score += 10;
        
        // Paragraphs (10 points)
        $p_count = substr_count($content, '<p>');
        if ($p_count >= 5) $score += 10;
        
        // Links (10 points)
        if (strpos($content, '<a ') !== false) $score += 10;
        
        // Strong/Bold (10 points)
        if (strpos($content, '<strong>') !== false || strpos($content, '<b>') !== false) $score += 10;
        
        return min(100, $score);
    }
    
    /**
     * Get settings
     */
    private function getSettings() {
        $settings = $this->db->fetchAll("SELECT setting_key, setting_value FROM blog_settings");
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $setting['setting_value'];
        }
        
        // Convert to proper types
        $result['daily_blog_count'] = (int)($result['daily_blog_count'] ?? 3);
        $result['enable_auto_generation'] = (bool)($result['enable_auto_generation'] ?? true);
        $result['min_word_count'] = (int)($result['min_word_count'] ?? 1200);
        $result['max_word_count'] = (int)($result['max_word_count'] ?? 2000);
        
        return $result;
    }
}

// API endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['generate'])) {
    header('Content-Type: application/json');
    
    try {
        $generator = new AutoBlogGenerator();
        $count = isset($_POST['count']) ? (int)$_POST['count'] : (isset($_GET['count']) ? (int)$_GET['count'] : null);
        
        $result = $generator->generateDailyBlogs($count);
        echo json_encode($result, JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'info',
        'message' => 'Send POST request or use ?generate=1 to generate blogs',
        'example' => 'POST /admin/api/auto-blog-generator.php with count=3'
    ]);
}
