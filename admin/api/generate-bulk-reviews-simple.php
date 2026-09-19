<?php
/**
 * Bulk Review Generator - Simple Version (No Database)
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
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

$review_count = (int)($_POST['review_count'] ?? 50);
$review_count = max(1, min(50, $review_count));

$gemini = new GeminiAPI(GEMINI_API_KEY);

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

// Other states names (20%)
$other_states_names = [
    'Amit Sharma', 'Priya Gupta', 'Rohit Verma', 'Neha Singh', 'Vikram Patel',
    'Anjali Mehta', 'Rahul Joshi', 'Pooja Desai', 'Arjun Nair', 'Sneha Iyer',
    'Rajiv Kumar', 'Ritu Agarwal', 'Karan Malhotra', 'Megha Kapoor'
];

// 6 Main Categories
$categories = [
    'Pigeon Safety Nets', 'Balcony Safety Nets', 'Children Safety Nets',
    'Bird Netting', 'Construction Safety Nets', 'Sports Nets'
];

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
    'Abids', 'Adikmet', 'Afzalgunj', 'Alwal', 'Amberpet', 'Ameerpet', 'Attapur', 'Bahadurpura',
    'Balapur', 'Bandlaguda', 'Banjara Hills', 'Barkas', 'Begum Bazaar', 'Begumpet', 'Boduppal',
    'Borabanda', 'Bowenpally', 'Chaitanyapuri', 'Champapet', 'Chandanagar', 'Charminar', 'Chikkadpally',
    'Chintal', 'Dilsukhnagar', 'Domalguda', 'Ecil', 'Erragadda', 'Falaknuma', 'Gachibowli',
    'Gaddiannaram', 'Ghatkesar', 'Golconda', 'Goshamahal', 'Gudimalkapur', 'Habsiguda', 'Hakimpet',
    'Hafeezpet', 'Hayathnagar', 'Himayath Nagar', 'Hitech City', 'Hyderguda', 'Ibrahimpatnam',
    'Jeedimetla', 'Jubilee Hills', 'Kachiguda', 'Kapra', 'Karwan', 'Katedan', 'Khairatabad',
    'Kharmanghat', 'Kingsway', 'Kismathpur', 'Kodandaram', 'Koh-e-Fiza', 'Kompally', 'Kondapur',
    'Kothapet', 'Koti', 'Kukatpally', 'LB Nagar', 'Lal Bahadur Nagar', 'Lakdikapul', 'Lingampally',
    'Madhapur', 'Madinaguda', 'Mahankali', 'Malakpet', 'Mallapur', 'Mamidipally', 'Malkajgiri',
    'Manikonda', 'Mansoorabad', 'Meerpet', 'Mehdipatnam', 'Miyapur', 'Moazzam Jahi Market', 'Moosapet',
    'Moosarambagh', 'Mozamjahi Market', 'Musheerabad', 'Nacharam', 'Nagole', 'Nallakunta', 'Nanakramguda',
    'Narayanguda', 'Nehru Nagar', 'New Bowenpally', 'Nizampet', 'Old City', 'Padmarao Nagar', 'Paradise',
    'Patancheru', 'Patelguda', 'Puppalaguda', 'Quthbullapur', 'Ramanthapur', 'Ramgopalpet', 'Ramnagar',
    'Rani Gunj', 'RC Puram', 'Red Hills', 'Rethibowli', 'Safilguda', 'Sainikpuri', 'Salar Jung Museum',
    'Sanath Nagar', 'Sanjeeva Reddy Nagar', 'Santosh Nagar', 'Saroornagar', 'Tambaram', 'Serilingampally',
    'Shahalibanda', 'Shamirpet', 'Shankarpally', 'Shivam Road', 'Somajiguda', 'SR Nagar', 'Srinagar Colony',
    'Suraram', 'Tank Bund', 'Tarnaka', 'Toli Chowki', 'Tolichowki', 'Trimulgherry', 'Tukaram Gate',
    'Uppal', 'Upparpally', 'Vanasthalipuram', 'Vidyanagar', 'Vikrampuri', 'Vijayanagar Colony', 'West Marredpally',
    'Yousufguda', 'Zaheerabad', 'BHEL', 'ECIL', 'KPHB', 'Pragathi Nagar', 'Almasguda', 'AS Rao Nagar',
    'Attapur', 'Balanagar', 'Bandlaguda Jagir', 'Beeramguda', 'Bharat Nagar', 'Boduppal', 'Bollaram',
    'Champapet', 'Chandrayangutta', 'Chengicherla', 'Dammaiguda', 'Dundigal', 'Gandhamguda', 'Gowlidoddy',
    'Hastinapuram', 'IDA Jeedimetla', 'Jawahar Nagar', 'Khajaguda', 'Kismatpur', 'Kowkoor', 'Lallaguda',
    'Langar Houz', 'Lingojiguda', 'Mahendra Hills', 'Mailardevpally', 'Mangalhat', 'Marredpally',
    'Masab Tank', 'Medchal', 'Mettuguda', 'Moinabad', 'Moulali', 'Nagaram', 'Narsingi', 'Neredmet',
    'Nizamabad', 'Old Alwal', 'Padma Rao Nagar', 'Peerzadiguda', 'Rajendranagar'
];

$generated = 0;
$errors = [];
$generated_reviews = [];
$used_names = [];

$reviews_dir = dirname(dirname(__DIR__)) . '/data/reviews';
if (!file_exists($reviews_dir)) {
    mkdir($reviews_dir, 0755, true);
}

try {
    for ($i = 0; $i < $review_count; $i++) {
        // Get unique name
        do {
            $is_chennai = (mt_rand(1, 100) <= 80);
            $name = $is_chennai 
                ? $chennai_names[array_rand($chennai_names)]
                : $other_states_names[array_rand($other_states_names)];
        } while (in_array($name, $used_names) && count($used_names) < (count($chennai_names) + count($other_states_names)));
        $used_names[] = $name;
        
        // Category (6 categories)
        $category = $categories[array_rand($categories)];
        
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
        
        // Random keywords (2-4 per review)
        $review_keywords = [];
        $num_keywords = mt_rand(2, 4);
        $keyword_pool = $keywords;
        shuffle($keyword_pool);
        for ($k = 0; $k < $num_keywords; $k++) {
            $review_keywords[] = $keyword_pool[$k];
        }
        
        // Random date 2010-2025
        $start_date = strtotime('2010-01-01');
        $end_date = time();
        $random_timestamp = mt_rand($start_date, $end_date);
        $random_date = date('Y-m-d H:i:s', $random_timestamp);
        
        $location = $chennai_areas[array_rand($chennai_areas)];
        
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
- Each review must be COMPLETELY UNIQUE

" . ($rating >= 4 ? "Focus: quality, service, installation" : ($rating == 3 ? "Focus: okay but mention one issue" : "Focus: specific problems faced")) . "

Return ONLY the review text (NO formatting, NO quotes, NO labels).";

        try {
            $review_text = $gemini->generateContent($prompt);
            $review_text = trim($review_text);
            $review_text = preg_replace('/^```json\s*|\s*```$/s', '', $review_text);
            $review_text = preg_replace('/^```\s*|\s*```$/s', '', $review_text);
            $review_text = trim(strip_tags($review_text));
            
            if (empty($review_text)) {
                $errors[] = "Review #" . ($i + 1) . ": Empty response";
                continue;
            }
            
            $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name)) . '-' . $random_timestamp . '-' . $i;
            
            $review = [
                'id' => $random_timestamp + $i,
                'customer_name' => $name,
                'rating' => $rating,
                'category' => $category,
                'review_text' => $review_text,
                'location' => $location . ', Chennai',
                'created_at' => $random_date,
                'status' => 'approved',
                'source' => 'ai_generated',
                'verified' => true,
                'helpful_count' => mt_rand(0, 50)
            ];
            
            $review_file = $reviews_dir . '/' . $slug . '.json';
            file_put_contents($review_file, json_encode($review, JSON_PRETTY_PRINT));

            // Auto-protect: register with AI Content Security
            $_acp = dirname(__DIR__) . '/includes/ai-content-protection.php';
            if (file_exists($_acp) && !class_exists('AIContentProtection')) require_once $_acp;
            if (class_exists('AIContentProtection')) {
                $_rev_title = $name . ($category ? ' — ' . $category : '');
                try { (new AIContentProtection())->protect('review', $slug, $_rev_title, 'data/reviews/' . $slug . '.json'); }
                catch (\Exception $e) { /* non-fatal */ }
            }

            // Also save to MySQL ai_reviews table (survives file deletions)
            try {
                $db->execute(
                    "INSERT INTO ai_reviews (customer_name, rating, category, review_text, location, slug, status, verified, helpful_count, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, 'approved', 1, ?, ?)",
                    [$name, $rating, $category, $review_text,
                     $location . ', Chennai', $slug,
                     $review['helpful_count'], $random_date]
                );
            } catch (\Exception $e) { /* non-fatal */ }
            
            $generated++;
            $generated_reviews[] = [
                'name' => $name,
                'rating' => $rating,
                'category' => $category
            ];
            
            usleep(500000); // 0.5 second delay
            
        } catch (Exception $e) {
            $errors[] = "Review #" . ($i + 1) . ": " . $e->getMessage();
        }
    }
    
    // Update stats
    $stats_file = $reviews_dir . '/stats.json';
    $stats = file_exists($stats_file) ? json_decode(file_get_contents($stats_file), true) : [];
    $stats['total_generated'] = ($stats['total_generated'] ?? 0) + $generated;
    $stats['last_generation'] = date('Y-m-d H:i:s');
    file_put_contents($stats_file, json_encode($stats, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'generated' => $generated,
        'requested' => $review_count,
        'errors' => $errors,
        'reviews' => $generated_reviews,
        'message' => "Successfully generated $generated reviews!"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
