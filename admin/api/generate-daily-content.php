<?php
/**
 * Daily Content Generation API
 * Generate daily blogs and reviews automatically
 */

define('ADMIN_ACCESS', true);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/gemini-api.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$gemini = new GeminiAPI(GEMINI_API_KEY);

// Get settings
$settings = $db->fetchOne("SELECT * FROM ai_content_settings WHERE id = 1");

if (!$settings || !$settings['auto_generate_enabled']) {
    echo json_encode(['success' => false, 'message' => 'Auto-generation is disabled']);
    exit;
}

$blogs_count = (int)$settings['daily_blogs_count'];
$reviews_count = (int)$settings['daily_reviews_count'];

$blogs_created = 0;
$reviews_created = 0;

// Generate blogs
if ($blogs_count > 0) {
    $categories = ['Safety Tips', 'Installation Guide', 'Product Review', 'Industry News', 'Maintenance', 'Case Study'];
    
    for ($i = 0; $i < $blogs_count; $i++) {
        $category = $categories[array_rand($categories)];
        
        $prompt = "Write a unique, SEO-optimized blog post about safety nets in Chennai. ";
        $prompt .= "Category: $category. Include title (50-60 chars), meta description (150-160 chars), ";
        $prompt .= "and 800-1200 words content. Format: Title|MetaDesc|Content";
        
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
                    
                    if ($result['success']) $blogs_created++;
                }
            }
            
            usleep(500000);
        } catch (Exception $e) {
            error_log("Daily blog generation error: " . $e->getMessage());
        }
    }
}

// Generate reviews
if ($reviews_count > 0) {
    $services = $db->fetchAll("SELECT id, service_name FROM services WHERE is_active = 1");
    $areas = $db->fetchAll("SELECT id, area_name FROM service_areas WHERE is_active = 1 LIMIT 50");
    
    $first_names = ['Rajesh', 'Priya', 'Amit', 'Sneha', 'Vikram', 'Anita', 'Suresh', 'Kavita', 'Ravi', 'Deepa'];
    $last_names = ['Kumar', 'Reddy', 'Sharma', 'Rao', 'Patel'];
    
    for ($i = 0; $i < $reviews_count; $i++) {
        $service = $services[array_rand($services)];
        $area = $areas[array_rand($areas)];
        
        // ✅ 90% positive (3,4,5 stars), 10% negative (1,2 stars)
        $random = rand(1, 100);
        if ($random <= 10) {
            // 10% negative
            $rating = rand(1, 2);
            $sentiment = 'negative';
        } else {
            // 90% positive
            $rand_rating = rand(1, 100);
            if ($rand_rating <= 15) {
                $rating = 3;
            } elseif ($rand_rating <= 45) {
                $rating = 4;
            } else {
                $rating = 5;
            }
            $sentiment = 'positive';
        }
        
        $prompt = "Write a genuine customer review (80-150 words) for {$service['service_name']} installation in {$area['area_name']}, Chennai. ";
        $prompt .= "Rating: $rating stars. ";
        
        if ($sentiment === 'negative') {
            $prompt .= "This is a NEGATIVE review. Mention issues like: poor quality, delayed installation, unprofessional staff, pricing concerns. Be honest but not extremely harsh. ";
        } else {
            $prompt .= "This is a POSITIVE review. Mention: excellent quality, professional team, timely installation, good pricing, helpful service. ";
        }
        
        $prompt .= "Sound authentic and natural. Use natural Indian English. Only return review text.";
        
        try {
            $review_text = $gemini->generateContent($prompt);
            
            if ($review_text) {
                $review_text = trim(preg_replace('/^["\']|["\']$/', '', $review_text));
                $customer_name = $first_names[array_rand($first_names)] . ' ' . $last_names[array_rand($last_names)];
                $phone = '9' . rand(100000000, 999999999);
                
                // ✅ Daily generation: Today's date with random time
                $random_hour = rand(0, 23);
                $random_minute = rand(0, 59);
                $random_second = rand(0, 59);
                $timestamp = date('Y-m-d') . ' ' . sprintf('%02d:%02d:%02d', $random_hour, $random_minute, $random_second);
                
                $result = $db->execute("
                    INSERT INTO reviews 
                    (customer_name, customer_phone, rating, review_text, service_id, area_id, is_verified, is_published, is_ai_generated, verified_at, submitted_at, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 1, 1, 1, ?, ?, ?)
                ", [$customer_name, $phone, $rating, $review_text, $service['id'], $area['id'], $timestamp, $timestamp, $timestamp], 'sisisssss');
                
                if ($result['success']) $reviews_created++;
            }
            
            usleep(400000);
        } catch (Exception $e) {
            error_log("Daily review generation error: " . $e->getMessage());
        }
    }
}

// Update last generation time
$db->execute("UPDATE ai_content_settings SET last_generation_time = NOW() WHERE id = 1");

echo json_encode([
    'success' => true,
    'blogs_created' => $blogs_created,
    'reviews_created' => $reviews_created,
    'message' => "Generated $blogs_created blogs and $reviews_created reviews"
]);
