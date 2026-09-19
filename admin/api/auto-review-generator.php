<?php
/**
 * Auto Review Generator — Cron / URL-callable endpoint
 *
 * Hostinger Cron URL:
 *   https://gcmsafetynets.in/admin/api/auto-review-generator.php?generate=1&token=YOUR_SECRET_TOKEN
 *
 * Can also be called from the admin panel (uses session auth).
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/gemini-api.php';

ignore_user_abort(true); // Keep running even if the calling connection drops (pseudo-cron)
set_time_limit(180);

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

/* ── Load settings from DB (persistent across redeployments) ─────── */
function _rv_load_settings(): array {
    try {
        $db   = Database::getInstance();
        $rows = $db->fetchAll("SELECT setting_key, setting_value FROM review_settings") ?: [];
        $s = [];
        foreach ($rows as $r) $s[$r['setting_key']] = $r['setting_value'];
        return $s;
    } catch (Exception $e) {
        // Fallback to JSON file if DB unavailable
        $f = dirname(dirname(__DIR__)) . '/data/reviews/auto-settings.json';
        return file_exists($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
    }
}
function _rv_save_setting(string $key, string $value): void {
    try {
        Database::getInstance()->execute(
            "INSERT INTO review_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            [$key, $value]
        );
    } catch (Exception $e) { /* non-fatal */ }
}

$settings = _rv_load_settings();

/* ── Authentication ────────────────────────────────────────────────── */
$token          = $settings['cron_token'] ?? '';
$from_session   = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$from_cron      = !empty($token) && isset($_GET['token']) && hash_equals($token, $_GET['token']);

if (!$from_session && !$from_cron) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

/* ── Settings defaults ────────────────────────────────────────────────── */
$daily_count    = (int)($settings['daily_count']     ?? 3);
$auto_enabled   = (bool)($settings['auto_enabled']   ?? true);
$run_once_today = (bool)($settings['run_once_today'] ?? true);

/* ── Only run if enabled (skip check when called manually from admin panel) */
if (!$from_session && !$auto_enabled) {
    echo json_encode(['success' => false, 'message' => 'Auto-generation is disabled.']);
    exit;
}

/* ── Random daily schedule (cron-triggered only) ──────────────────── */
$is_cron = $from_cron && !$from_session;
if ($is_cron && !isset($_GET['force'])) {
    $today_date   = date('Y-m-d');
    $current_hour = (int)date('G');
    $stored_sched = $settings['today_schedule'] ?? '';
    $sched_parts  = explode(':', $stored_sched);
    $sched_date   = $sched_parts[0] ?? '';
    $sched_hour   = isset($sched_parts[1]) ? (int)$sched_parts[1] : -1;

    if ($sched_date !== $today_date || $sched_hour < 0) {
        $sched_hour = rand(7, 21); // 7 AM – 9 PM
        _rv_save_setting('today_schedule', "{$today_date}:{$sched_hour}");
    }

    if ($current_hour < $sched_hour) {
        echo json_encode([
            'success' => true,
            'message' => "Not time yet — today's random schedule: {$sched_hour}:00.",
            'generated' => 0
        ]);
        exit;
    }
}

/* ── Prevent double-run today ────────────────────────────────────────────── */
$today    = date('Y-m-d');
$last_run = $settings['last_run_date'] ?? '';
if ($run_once_today && $last_run === $today && !isset($_GET['force'])) {
    echo json_encode([
        'success' => true,
        'message' => 'Already generated today (' . $today . '). Use ?force=1 to override.',
        'generated' => 0
    ]);
    exit;
}

/* ── Data arrays (shared with generate-single-review-simple.php) ─────── */
$chennai_names = [
    'Rajesh Kumar','Srinivas Reddy','Venkat Rao','Ramesh Babu','Krishna Prasad',
    'Suresh Kumar','Mahesh Reddy','Praveen Kumar','Anil Kumar','Vijay Kumar',
    'Ravi Teja','Sai Kumar','Naresh Reddy','Kiran Kumar','Prakash Rao',
    'Madhavi Reddy','Lakshmi Devi','Sunitha Rani','Kavitha Reddy','Priya Sharma',
    'Swathi Reddy','Divya Sri','Anitha Kumar','Sangeetha Rao','Manisha Reddy',
    'Harish Reddy','Naveen Kumar','Sandeep Rao','Bhanu Prakash','Chandra Mohan',
    'Deepak Reddy','Ganesh Kumar','Hari Krishna','Janardhan Reddy','Karthik Rao',
    'Murali Krishna','Nagesh Babu','Pavan Kumar','Raghav Reddy','Satish Kumar',
    'Usha Rani','Vani Reddy','Yamini Devi','Anusha Rani','Bindu Sri'
];
$other_names = [
    'Amit Sharma','Priya Gupta','Rohit Verma','Neha Singh','Vikram Patel',
    'Anjali Mehta','Rahul Joshi','Pooja Desai','Arjun Nair','Sneha Iyer',
    'Rajiv Kumar','Ritu Agarwal','Karan Malhotra','Megha Kapoor'
];
$categories = [
    'Pigeon Safety Nets','Balcony Safety Nets','Children Safety Nets',
    'Bird Netting','Construction Safety Nets','Sports Nets'
];
$keywords = [
    'installation','quality','service','warranty','HDPE nets','UV protection',
    'professional team','clean work','on time','affordable','durable','strong nets',
    'no mess','fast service','reliable','experienced','safety first','bird control',
    'pigeon problem','balcony protection','child safety','pet safety','transparent nets',
    'invisible nets','weather resistant','long lasting','value for money','recommended',
    'trustworthy','punctual','skilled technicians','neat installation','no damage',
    'wall drilling','proper measurement','custom size','good material','certified',
    'satisfaction guaranteed','customer care'
];
$areas = [
    'Abids','Kodambakkam','Besant Nagar','Nungambakkam','Boduppal','Borabanda',
    'Chromepet','Charminar','Pallavaram','Ecil','Anna Nagar','Habsiguda',
    'Hafeezpet','Sholinganallur','Adyar','Madipakkam','Velachery','Kothapet',
    'Porur','Avadi','T Nagar','Madinaguda','Malkajgiri','Mylapore',
    'Mogappair','Ambattur','Nacharam','Nagole','Medavakkam','Nanakramguda',
    'Kilpauk','Pragathi Nagar','RC Puram','Sainikpuri','Saroornagar',
    'Tambaram','Serilingampally','Alwarpet','SR Nagar','Tarnaka',
    'Kotturpuram','Uppal','Vanasthalipuram','Yousufguda','Thiruvanmiyur','AS Rao Nagar',
    'Moosapet','Bowenpally','Alwal','Balanagar','Himayath Nagar','Saidapet'
];

/* ── Generation loop ───────────────────────────────────────────────────── */
$reviews_dir = dirname(dirname(__DIR__)) . '/data/reviews';
if (!file_exists($reviews_dir)) mkdir($reviews_dir, 0755, true);

$generated = [];
$errors    = [];
$count     = isset($_GET['count']) ? max(1, min(50, (int)$_GET['count'])) : $daily_count;
$gemini    = new GeminiAPI(GEMINI_API_KEY);

for ($i = 0; $i < $count; $i++) {
    try {
        $name     = (mt_rand(1,100) <= 80)
                    ? $chennai_names[array_rand($chennai_names)]
                    : $other_names[array_rand($other_names)];
        $category = $categories[array_rand($categories)];
        $location = $areas[array_rand($areas)];

        // Rating distribution: 50%→5★, 30%→4★, 12%→3★, 6%→2★, 2%→1★
        $r = mt_rand(1,100);
        $rating = $r <= 50 ? 5 : ($r <= 80 ? 4 : ($r <= 92 ? 3 : ($r <= 98 ? 2 : 1)));

        // Pick 2-3 random keywords
        $kw_pool = $keywords; shuffle($kw_pool);
        $kw_str  = implode(', ', array_slice($kw_pool, 0, mt_rand(2, 3)));

        $tone = match(true) {
            $rating == 5 => 'very satisfied',
            $rating == 4 => 'satisfied',
            $rating == 3 => 'okay but with minor concerns',
            default      => 'disappointed'
        };
        $word_count = mt_rand(10, 55);

        $prompt = "Write a customer review for safety net installation in $location, Chennai.
Customer is $tone ($rating/5 stars) about: $category
REQUIREMENTS:
- LENGTH: EXACTLY $word_count words
- Naturally mention these if fits: $kw_str
- Write like a real person, not a template
- Be specific about: $category
- NO company name required
Return ONLY the review text (no quotes, no labels).";

        $text = trim($gemini->generateContent($prompt));
        $text = trim(strip_tags(preg_replace('/^```.*?```$/s', '', $text)));
        if (empty($text)) throw new Exception('Empty response');

        // Use today's actual date/time — auto reviews are dated when generated
        $ts   = time() + $i; // ensure unique id per loop iteration
        $date = date('Y-m-d H:i:s');

        $review = [
            'id'            => $ts,
            'customer_name' => $name,
            'rating'        => $rating,
            'category'      => $category,
            'review_text'   => $text,
            'location'      => $location,
            'created_at'    => $date,
            'status'        => 'approved',
            'source'        => 'ai_generated',
            'verified'      => true,
            'helpful_count' => 0
        ];

        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name)) . '-' . $ts;
        file_put_contents($reviews_dir . '/' . $slug . '.json',
                          json_encode($review, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $generated[] = ['name' => $name, 'rating' => $rating, 'category' => $category];

        if ($i < $count - 1) sleep(2); // rate-limit buffer

    } catch (Exception $e) {
        $errors[] = 'Review ' . ($i+1) . ': ' . $e->getMessage();
        error_log('[AutoReview] ' . $e->getMessage());
    }
}

/* ── Update stats.json ─────────────────────────────────────────────────── */
$stats_file = $reviews_dir . '/stats.json';
$stats = file_exists($stats_file) ? json_decode(file_get_contents($stats_file), true) : [];
$stats['total_generated'] = ($stats['total_generated'] ?? 0) + count($generated);
$stats['last_generation'] = date('Y-m-d H:i:s');
file_put_contents($stats_file, json_encode($stats, JSON_PRETTY_PRINT));

/* ── Update last run date in DB ──────────────────────────────────────────── */
_rv_save_setting('last_run_date', $today);

echo json_encode([
    'success'   => true,
    'generated' => count($generated),
    'reviews'   => $generated,
    'errors'    => $errors,
    'date'      => $today,
    'message'   => count($generated) . ' reviews generated successfully.'
]);
