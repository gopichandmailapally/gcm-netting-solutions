<?php
/**
 * Bulk Review Generation API
 * Generate multiple reviews using AI
 */

define('ADMIN_ACCESS', true);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/gemini-api.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$count = (int)($_POST['count'] ?? 10);
$service_id = (int)($_POST['service_id'] ?? 0);
$area_id = (int)($_POST['area_id'] ?? 0);
$rating_type = sanitize_input($_POST['rating_type'] ?? 'mixed');
$is_daily = (bool)($_POST['is_daily'] ?? false); // Daily auto-gen or bulk

$count = min($count, 50); // Limit to 50 per batch

$db = Database::getInstance();
$gemini = new GeminiAPI(GEMINI_API_KEY);

// Get services and areas
$services = $db->fetchAll("SELECT id, service_name FROM services WHERE is_active = 1");
$areas = $db->fetchAll("SELECT id, area_name FROM service_areas WHERE is_active = 1 LIMIT 100");

if (empty($services) || empty($areas)) {
    echo json_encode(['success' => false, 'message' => 'No services or areas found']);
    exit;
}

// Indian names for variety
$first_names = ['Rajesh', 'Priya', 'Amit', 'Sneha', 'Vikram', 'Anita', 'Suresh', 'Kavita', 'Ravi', 'Deepa', 'Sanjay', 'Lakshmi', 'Anil', 'Meena', 'Krishna', 'Radha', 'Ramesh', 'Sita', 'Mohan', 'Geetha'];
$last_names = ['Kumar', 'Reddy', 'Sharma', 'Rao', 'Patel', 'Singh', 'Gupta', 'Nair', 'Menon', 'Iyer'];

$created = 0;

for ($i = 0; $i < $count; $i++) {
    // Select service and area
    $use_service_id = $service_id > 0 ? $service_id : $services[array_rand($services)]['id'];
    $use_area_id = $area_id > 0 ? $area_id : $areas[array_rand($areas)]['id'];
    
    // Get service and area names
    $service = $db->fetchOne("SELECT service_name FROM services WHERE id = ?", [$use_service_id], 'i');
    $area = $db->fetchOne("SELECT area_name FROM service_areas WHERE id = ?", [$use_area_id], 'i');
    
    // ✅ Determine rating: 90% positive (3,4,5 stars), 10% negative (1,2 stars)
    $random = rand(1, 100);
    if ($random <= 10) {
        // 10% negative (1 or 2 stars)
        $rating = rand(1, 2);
        $sentiment = 'negative';
    } else {
        // 90% positive (3, 4, or 5 stars)
        $rating_weights = [3 => 15, 4 => 30, 5 => 55]; // 15% 3-star, 30% 4-star, 55% 5-star
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
    
    // Generate unique review based on sentiment
    $prompt = "Write a genuine customer review (80-150 words) for ";
    $prompt .= $service['service_name'] . " installation in " . $area['area_name'] . ", Chennai. ";
    $prompt .= "Rating: $rating out of 5 stars. ";
    
    if ($sentiment === 'negative') {
        $prompt .= "This is a NEGATIVE review. Mention issues like: poor quality, delayed installation, unprofessional staff, pricing concerns, or service issues. ";
        $prompt .= "Be honest but not extremely harsh. ";
    } else {
        $prompt .= "This is a POSITIVE review. Mention: excellent quality, professional team, timely installation, good pricing, helpful service. ";
    }
    
    $prompt .= "Make it sound authentic and natural. Use natural Indian English. Only return the review text, nothing else.";
    
    try {
        $review_text = $gemini->generateContent($prompt);
        
        if ($review_text) {
            // Clean up the review
            $review_text = trim($review_text);
            $review_text = preg_replace('/^["\']|["\']$/', '', $review_text);
            
            // Generate random name
            $customer_name = $first_names[array_rand($first_names)] . ' ' . $last_names[array_rand($last_names)];
            
            // Generate random phone (dummy)
            $phone = '9' . rand(100000000, 999999999);
            
            // ✅ Generate timestamp based on type
            if ($is_daily) {
                // Daily auto-generation: Today's date with random time (0-23 hours)
                $random_hour = rand(0, 23);
                $random_minute = rand(0, 59);
                $random_second = rand(0, 59);
                $timestamp = date('Y-m-d') . ' ' . sprintf('%02d:%02d:%02d', $random_hour, $random_minute, $random_second);
            } else {
                // Bulk generation: Random date from 2014 to present with random time
                $start_date = strtotime('2014-01-01');
                $end_date = time(); // Current timestamp
                $random_timestamp = rand($start_date, $end_date);
                $timestamp = date('Y-m-d H:i:s', $random_timestamp);
            }
            
            // Insert review with custom timestamp
            $result = $db->execute("
                INSERT INTO reviews 
                (customer_name, customer_phone, rating, review_text, service_id, area_id, is_verified, is_published, is_ai_generated, verified_at, submitted_at, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 1, 1, 1, ?, ?, ?)
            ", [$customer_name, $phone, $rating, $review_text, $use_service_id, $use_area_id, $timestamp, $timestamp, $timestamp], 'sisisssss');
            
            if ($result['success']) {
                $created++;
            }
        }
        
        // Small delay
        usleep(400000); // 0.4 second
    } catch (Exception $e) {
        error_log("Review generation error: " . $e->getMessage());
    }
}

echo json_encode([
    'success' => $created > 0,
    'created' => $created,
    'message' => "Generated $created out of $count reviews"
]);
