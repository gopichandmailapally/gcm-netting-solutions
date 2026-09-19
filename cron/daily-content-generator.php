<?php
/**
 * Daily Content Generator - Cron Job
 * Run this file daily at 2:00 AM
 * 
 * Crontab entry:
 * 0 2 * * * /usr/bin/php /path/to/gcm-netting-solutions-chennai/cron/daily-content-generator.php
 */

define('GCM_INIT', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/gemini-api.php';

// Ensure this is run from command line only
if (php_sapi_name() !== 'cli' && !defined('ALLOW_WEB_CRON')) {
    die('This script can only be run from command line');
}

$db = Database::getInstance();

// Log start
echo "[" . date('Y-m-d H:i:s') . "] Starting daily content generation...\n";

// Check if auto-generation is enabled
$settings = $db->fetchOne("SELECT * FROM ai_content_settings WHERE id = 1");

if (!$settings || !$settings['auto_generate_enabled']) {
    echo "Auto-generation is disabled. Exiting.\n";
    exit;
}

$blogs_count = (int)$settings['daily_blogs_count'];
$reviews_count = (int)$settings['daily_reviews_count'];

echo "Target: Generate $blogs_count blogs and $reviews_count reviews\n";

$gemini = new GeminiAPI(GEMINI_API_KEY);

$blogs_created = 0;
$reviews_created = 0;

// GENERATE BLOGS
if ($blogs_count > 0) {
    echo "\n--- Generating Blogs ---\n";
    
    $categories = ['Safety Tips', 'Installation Guide', 'Product Review', 'Industry News', 'Maintenance', 'Case Study'];
    
    for ($i = 0; $i < $blogs_count; $i++) {
        $category = $categories[array_rand($categories)];
        
        echo "Generating blog " . ($i + 1) . " (Category: $category)...";
        
        $prompt = "Write a unique, SEO-optimized blog post about safety nets in Chennai. ";
        $prompt .= "Category: $category. Include title (50-60 chars), meta description (150-160 chars), ";
        $prompt .= "and 800-1200 words content with proper headings (## for H2, ### for H3). ";
        $prompt .= "Format: Title|MetaDesc|Content";
        
        try {
            $response = $gemini->generateContent($prompt);
            
            if ($response) {
                $parts = explode('|', $response, 3);
                
                if (count($parts) === 3) {
                    $title = trim($parts[0]);
                    $meta_desc = trim($parts[1]);
                    $content = trim($parts[2]);
                    $slug = create_slug($title);
                    $excerpt = substr(strip_tags($content), 0, 200);
                    
                    $result = $db->execute("
                        INSERT INTO blog_posts 
                        (title, slug, excerpt, content, category, meta_description, is_published, is_ai_generated, created_at, published_at) 
                        VALUES (?, ?, ?, ?, ?, ?, 1, 1, NOW(), NOW())
                    ", [$title, $slug, $excerpt, $content, $category, $meta_desc], 'ssssss');
                    
                    if ($result['success']) {
                        $blogs_created++;
                        echo " ✓ Success\n";
                    } else {
                        echo " ✗ Failed to save\n";
                    }
                } else {
                    echo " ✗ Invalid format\n";
                }
            } else {
                echo " ✗ No response\n";
            }
            
            // Delay to avoid rate limits
            sleep(1);
        } catch (Exception $e) {
            echo " ✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "Blogs created: $blogs_created/$blogs_count\n";
}

// GENERATE REVIEWS
if ($reviews_count > 0) {
    echo "\n--- Generating Reviews ---\n";
    
    $services = $db->fetchAll("SELECT id, service_name FROM services WHERE is_active = 1");
    $areas = $db->fetchAll("SELECT id, area_name FROM service_areas WHERE is_active = 1 LIMIT 50");
    
    if (empty($services) || empty($areas)) {
        echo "No services or areas found. Skipping reviews.\n";
    } else {
        $first_names = ['Rajesh', 'Priya', 'Amit', 'Sneha', 'Vikram', 'Anita', 'Suresh', 'Kavita', 'Ravi', 'Deepa', 'Sanjay', 'Lakshmi'];
        $last_names = ['Kumar', 'Reddy', 'Sharma', 'Rao', 'Patel', 'Singh', 'Gupta'];
        
        for ($i = 0; $i < $reviews_count; $i++) {
            $service = $services[array_rand($services)];
            $area = $areas[array_rand($areas)];
            $rating = rand(4, 5); // Only positive reviews
            
            echo "Generating review " . ($i + 1) . " ($rating stars)...";
            
            $prompt = "Write a genuine customer review (80-150 words) for {$service['service_name']} installation in {$area['area_name']}, Chennai. ";
            $prompt .= "Rating: $rating stars. Sound authentic, mention specific details. Use natural Indian English. Only return review text.";
            
            try {
                $review_text = $gemini->generateContent($prompt);
                
                if ($review_text) {
                    $review_text = trim(preg_replace('/^["\']|["\']$/', '', $review_text));
                    $customer_name = $first_names[array_rand($first_names)] . ' ' . $last_names[array_rand($last_names)];
                    $phone = '9' . rand(100000000, 999999999);
                    
                    $result = $db->execute("
                        INSERT INTO reviews 
                        (customer_name, customer_phone, rating, review_text, service_id, area_id, is_verified, is_published, is_ai_generated, verified_at, submitted_at) 
                        VALUES (?, ?, ?, ?, ?, ?, 1, 1, 1, NOW(), NOW())
                    ", [$customer_name, $phone, $rating, $review_text, $service['id'], $area['id']], 'sisiis');
                    
                    if ($result['success']) {
                        $reviews_created++;
                        echo " ✓ Success\n";
                    } else {
                        echo " ✗ Failed to save\n";
                    }
                } else {
                    echo " ✗ No response\n";
                }
                
                // Delay
                sleep(1);
            } catch (Exception $e) {
                echo " ✗ Error: " . $e->getMessage() . "\n";
            }
        }
        
        echo "Reviews created: $reviews_created/$reviews_count\n";
    }
}

// Update last generation time
$db->execute("UPDATE ai_content_settings SET last_generation_time = NOW() WHERE id = 1");

// Log to database
$db->execute("
    INSERT INTO ai_generation_logs (generation_type, content_count, success_count) 
    VALUES ('blog', ?, ?), ('review', ?, ?)
", [$blogs_count, $blogs_created, $reviews_count, $reviews_created], 'iiii');

// Auto-refresh sitemap after daily content generation
if ($blogs_created > 0 || $reviews_created > 0) {
    echo "\n--- Refreshing Sitemap ---\n";
    require_once dirname(__DIR__) . '/admin/api/sitemap-functions.php';
    $sm_result = gcm_auto_refresh_sitemap();
    if ($sm_result) {
        echo $sm_result['success']
            ? "Sitemap updated: " . ($sm_result['total_urls'] ?? 0) . " URLs\n"
            : "Sitemap update failed: " . ($sm_result['message'] ?? '') . "\n";
    } else {
        echo "Sitemap auto-refresh is disabled in settings.\n";
    }
}

echo "\n[" . date('Y-m-d H:i:s') . "] Completed!\n";
echo "Total: $blogs_created blogs, $reviews_created reviews\n";
echo "-------------------------------------------\n";
