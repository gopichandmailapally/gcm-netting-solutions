<?php
/**
 * Generate Single Review - Simple Version (No Database)
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
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
    // Chennai names (80%)
    $chennai_names = [
        'Rajesh Kumar', 'Srinivas Reddy', 'Venkat Rao', 'Ramesh Babu', 'Krishna Prasad',
        'Suresh Kumar', 'Mahesh Reddy', 'Praveen Kumar', 'Anil Kumar', 'Vijay Kumar',
        'Ravi Teja', 'Sai Kumar', 'Naresh Reddy', 'Kiran Kumar', 'Prakash Rao',
        'Madhavi Reddy', 'Lakshmi Devi', 'Sunitha Rani', 'Kavitha Reddy', 'Priya Sharma',
        'Swathi Reddy', 'Divya Sri', 'Anitha Kumar', 'Sangeetha Rao', 'Manisha Reddy',
        'Harish Reddy', 'Naveen Kumar', 'Sandeep Rao', 'Bhanu Prakash', 'Chandra Mohan',
        'Deepak Reddy', 'Ganesh Kumar', 'Hari Krishna', 'Janardhan Reddy', 'Karthik Rao',
        'Murali Krishna', 'Nagesh Babu', 'Pavan Kumar', 'Raghav Reddy', 'Satish Kumar',
        'Usha Rani', 'Vani Reddy', 'Yamini Devi', 'Anusha Rani', 'Bindu Sri'
    ];
    
    $other_states_names = [
        'Amit Sharma', 'Priya Gupta', 'Rohit Verma', 'Neha Singh', 'Vikram Patel',
        'Anjali Mehta', 'Rahul Joshi', 'Pooja Desai', 'Arjun Nair', 'Sneha Iyer',
        'Rajiv Kumar', 'Ritu Agarwal', 'Karan Malhotra', 'Megha Kapoor'
    ];
    
    // 80% Chennai, 20% other states
    $name = (mt_rand(1, 100) <= 80) ? $chennai_names[array_rand($chennai_names)] : $other_states_names[array_rand($other_states_names)];
    
    // 6 Main Categories
    $categories = [
        'Pigeon Safety Nets', 'Balcony Safety Nets', 'Children Safety Nets',
        'Bird Netting', 'Construction Safety Nets', 'Sports Nets'
    ];
    $category = $categories[array_rand($categories)];
    
    // 64 Keywords
    $keywords = [
        'installation', 'quality', 'service', 'warranty', 'HDPE nets', 'UV protection',
        'professional team', 'clean work', 'on time', 'affordable', 'durable', 'strong nets',
        'no mess', 'fast service', 'reliable', 'experienced', 'safety first', 'bird control',
        'pigeon problem', 'balcony protection', 'child safety', 'pet safety', 'transparent nets',
        'invisible nets', 'weather resistant', 'long lasting', 'value for money', 'recommended',
        'trustworthy', 'punctual', 'skilled technicians', 'neat installation', 'no damage',
        'wall drilling', 'proper measurement', 'custom size', 'good material', 'certified',
        'licensed', 'insured', 'emergency service', 'same day', 'weekend available',
        'free inspection', 'honest quote', 'no hidden charges', 'maintenance', 'repair service',
        'replacement', 'color options', 'mesh size', 'load capacity', 'fire resistant',
        'eco friendly', 'non toxic', 'rust proof', 'corrosion free', 'aesthetic', 'unobtrusive',
        'building code', 'HOA approved', 'satisfaction guaranteed', 'customer care'
    ];
    
    // 188 Chennai Areas
    $chennai_areas = [
        'Abids', 'Adikmet', 'Afzalgunj', 'Alwal', 'Amberpet', 'Kodambakkam', 'Saidapet', 'Bahadurpura',
        'Balapur', 'Bandlaguda', 'Besant Nagar', 'Barkas', 'Begum Bazaar', 'Nungambakkam', 'Boduppal',
        'Borabanda', 'Bowenpally', 'Chaitanyapuri', 'Champapet', 'Chromepet', 'Charminar', 'Chikkadpally',
        'Chintal', 'Pallavaram', 'Domalguda', 'Ecil', 'Erragadda', 'Falaknuma', 'Anna Nagar',
        'Gaddiannaram', 'Ghatkesar', 'Golconda', 'Goshamahal', 'Gudimalkapur', 'Habsiguda', 'Hakimpet',
        'Hafeezpet', 'Hayathnagar', 'Himayath Nagar', 'Sholinganallur', 'Hyderguda', 'Ibrahimpatnam',
        'Jeedimetla', 'Adyar', 'Kachiguda', 'Kapra', 'Karwan', 'Katedan', 'Khairatabad',
        'Kharmanghat', 'Kingsway', 'Kismathpur', 'Kodandaram', 'Koh-e-Fiza', 'Madipakkam', 'Velachery',
        'Kothapet', 'Koti', 'Porur', 'Avadi', 'Lal Bahadur Nagar', 'Lakdikapul', 'Lingampally',
        'T Nagar', 'Madinaguda', 'Mahankali', 'Malakpet', 'Mallapur', 'Mamidipally', 'Malkajgiri',
        'Mylapore', 'Mansoorabad', 'Meerpet', 'Mogappair', 'Ambattur', 'Moazzam Jahi Market', 'Moosapet',
        'Moosarambagh', 'Mozamjahi Market', 'Musheerabad', 'Nacharam', 'Nagole', 'Nallakunta', 'Nanakramguda',
        'Narayanguda', 'Nehru Nagar', 'New Bowenpally', 'Kilpauk', 'Old City', 'Padmarao Nagar', 'Paradise',
        'Patancheru', 'Patelguda', 'Puppalaguda', 'Quthbullapur', 'Ramanthapur', 'Ramgopalpet', 'Ramnagar',
        'Rani Gunj', 'RC Puram', 'Red Hills', 'Rethibowli', 'Safilguda', 'Sainikpuri', 'Salar Jung Museum',
        'Sanath Nagar', 'Sanjeeva Reddy Nagar', 'Santosh Nagar', 'Saroornagar', 'Tambaram', 'Serilingampally',
        'Shahalibanda', 'Shamirpet', 'Shankarpally', 'Shivam Road', 'Alwarpet', 'SR Nagar', 'Srinagar Colony',
        'Suraram', 'Tank Bund', 'Tarnaka', 'Toli Chowki', 'Kotturpuram', 'Trimulgherry', 'Tukaram Gate',
        'Uppal', 'Upparpally', 'Vanasthalipuram', 'Vidyanagar', 'Vikrampuri', 'Vijayanagar Colony', 'West Marredpally',
        'Yousufguda', 'Zaheerabad', 'BHEL', 'ECIL', 'Thiruvanmiyur', 'Pragathi Nagar', 'Almasguda', 'AS Rao Nagar',
        'Saidapet', 'Balanagar', 'Bandlaguda Jagir', 'Beeramguda', 'Bharat Nagar', 'Boduppal', 'Bollaram',
        'Champapet', 'Chandrayangutta', 'Chengicherla', 'Dammaiguda', 'Dundigal', 'Gandhamguda', 'Gowlidoddy',
        'Hastinapuram', 'IDA Jeedimetla', 'Jawahar Nagar', 'Khajaguda', 'Kismatpur', 'Kowkoor', 'Lallaguda',
        'Langar Houz', 'Lingojiguda', 'Mahendra Hills', 'Mailardevpally', 'Mangalhat', 'Marredpally',
        'Masab Tank', 'Medchal', 'Mettuguda', 'Moinabad', 'Moulali', 'Nagaram', 'Narsingi', 'Neredmet',
        'Nizamabad', 'Old Alwal', 'Padma Rao Nagar', 'Peerzadiguda', 'Rajendranagar'
    ];
    
    // Random keywords (2-4)
    $review_keywords = [];
    $num_keywords = mt_rand(2, 4);
    $keyword_pool = $keywords;
    shuffle($keyword_pool);
    for ($k = 0; $k < $num_keywords; $k++) {
        $review_keywords[] = $keyword_pool[$k];
    }
    
    // Random area from 188
    $location = $chennai_areas[array_rand($chennai_areas)];
    
    // Rating distribution: 50% (5★), 30% (4★), 12% (3★), 6% (2★), 2% (1★)
    $rand = mt_rand(1, 100);
    if ($rand <= 50) {
        $rating = 5; // Excellent
    } elseif ($rand <= 80) {
        $rating = 4; // Very Good
    } elseif ($rand <= 92) {
        $rating = 3; // Good/Average
    } elseif ($rand <= 98) {
        $rating = 2; // Below Average
    } else {
        $rating = 1; // Poor
    }
    
    // Random date 2010–now: makes manually generated reviews look established
    $start_date     = strtotime('2010-01-01');
    $generated_ts   = mt_rand($start_date, time());
    $generated_date = date('Y-m-d H:i:s', $generated_ts);
    
    // Generate review with AI
    $keywords_str = implode(', ', $review_keywords);
    
    if ($rating >= 4) {
        $sentiment = 'positive';
        $tone = $rating == 5 ? 'very satisfied' : 'satisfied';
    } elseif ($rating == 3) {
        $sentiment = 'moderate';
        $tone = 'okay but with some concerns';
    } else {
        $sentiment = 'negative';
        $tone = 'disappointed';
    }
    
    // Random word count (5-60 words)
    $word_count = mt_rand(5, 60);
    
    $prompt = "Write a customer review for safety net installation in $location, Chennai.

Customer is $tone ($rating/5 stars) about: $category

REQUIREMENTS:
- LENGTH: EXACTLY $word_count words (count precisely!)
- Randomly vary sentence structure and length
- Naturally mention these words if fits: $keywords_str
- Write like a real person talks, not a template
- NO company name required (optional if natural)
- Be specific about: $category
- Tone: $sentiment
- Make it COMPLETELY UNIQUE

" . ($rating >= 4 ? "Focus: quality, service, installation" : ($rating == 3 ? "Focus: okay but mention one issue" : "Focus: specific problems faced")) . "

Return ONLY the review text (NO formatting, NO quotes, NO labels).";

    $review_text = $gemini->generateContent($prompt);
    $review_text = trim($review_text);
    $review_text = preg_replace('/^```json\s*|\s*```$/s', '', $review_text);
    $review_text = preg_replace('/^```\s*|\s*```$/s', '', $review_text);
    $review_text = trim(strip_tags($review_text));
    
    if (empty($review_text)) {
        throw new Exception('Empty review generated');
    }
    
    // Manual review: random historical date + realistic helpful count
    $review = [
        'id'            => $generated_ts,
        'customer_name' => $name,
        'rating'        => $rating,
        'category'      => $category,
        'review_text'   => $review_text,
        'location'      => $location,
        'created_at'    => $generated_date,
        'status'        => 'approved',
        'source'        => 'ai_generated',
        'verified'      => true,
        'helpful_count' => mt_rand(0, 50)
    ];
    
    // Save to JSON
    $reviews_dir = dirname(dirname(__DIR__)) . '/data/reviews';
    if (!file_exists($reviews_dir)) {
        mkdir($reviews_dir, 0755, true);
    }
    
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name)) . '-' . $generated_ts;
    $review_file = $reviews_dir . '/' . $slug . '.json';
    file_put_contents($review_file, json_encode($review, JSON_PRETTY_PRINT));

    // Auto-protect: register with AI Content Security
    $_acp = dirname(__DIR__) . '/includes/ai-content-protection.php';
    if (file_exists($_acp) && !class_exists('AIContentProtection')) require_once $_acp;
    if (class_exists('AIContentProtection')) {
        $_rev_title = $name . ($category ? ' — ' . $category : '');
        try { (new AIContentProtection())->protect('review', $slug, $_rev_title, 'data/reviews/' . $slug . '.json'); }
        catch (Exception $e) { /* non-fatal */ }
    }

    // Update stats
    $stats_file = $reviews_dir . '/stats.json';
    $stats = file_exists($stats_file) ? json_decode(file_get_contents($stats_file), true) : [];
    $stats['total_generated'] = ($stats['total_generated'] ?? 0) + 1;
    $stats['last_generation'] = date('Y-m-d H:i:s');
    file_put_contents($stats_file, json_encode($stats, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'name' => $name,
        'rating' => $rating,
        'category' => $category,
        'message' => 'Review generated successfully!'
    ]);
    
} catch (Exception $e) {
    error_log('Review Generation Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
