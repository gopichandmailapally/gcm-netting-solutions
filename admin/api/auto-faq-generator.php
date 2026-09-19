<?php
/**
 * Auto FAQ Generator — pseudo-cron / URL-callable endpoint.
 *
 * Generates 2–4 random FAQs per day at the pseudo-cron scheduled time.
 * Auth: admin session OR PSEUDO_CRON_TOKEN query param.
 *
 * URL (called automatically by pseudo-cron):
 *   https://gcmsafetynets.in/admin/api/auto-faq-generator.php?generate=1&token=TOKEN
 */

define('GCM_INIT', true);
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/config/faq-categories.php';
require_once dirname(__DIR__) . '/../includes/gemini-api.php';

ignore_user_abort(true); // Keep running even if the calling connection drops (pseudo-cron)
set_time_limit(180);

if (session_status() === PHP_SESSION_NONE) {
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

header('Content-Type: application/json');

/* ── Auth ─────────────────────────────────────────────────────────── */
$from_session = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$from_token   = isset($_GET['token']) && defined('PSEUDO_CRON_TOKEN') && hash_equals(PSEUDO_CRON_TOKEN, $_GET['token']);

if (!$from_session && !$from_token) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['generate']) && !$from_session) {
    echo json_encode(['success' => false, 'message' => 'Pass ?generate=1 to run']);
    exit;
}

/* ── Prevent double-run today (unless ?force=1) ──────────────────── */
$faqs_dir   = dirname(dirname(__DIR__)) . '/data/faqs';
$stats_file = $faqs_dir . '/stats.json';
if (!is_dir($faqs_dir)) mkdir($faqs_dir, 0755, true);

$stats = file_exists($stats_file) ? (@json_decode(file_get_contents($stats_file), true) ?: []) : [];
$today = date('Y-m-d');

if (!isset($_GET['force']) && ($stats['auto_last_run'] ?? '') === $today) {
    echo json_encode(['success' => true, 'generated' => 0, 'message' => 'Already generated today. Use ?force=1 to override.']);
    exit;
}

/* ── Generation ───────────────────────────────────────────────────── */
$gemini    = new GeminiAPI(GEMINI_API_KEY);
$count     = rand(2, 4);
$generated = [];
$errors    = [];

$all_categories = get_faq_categories();

$topics = [
    'safety net installation process in Chennai',
    'types of safety nets and their uses',
    'cost and pricing for safety nets',
    'warranty coverage and terms',
    'maintenance requirements for safety nets',
    'quality and durability factors',
    'pigeon net effectiveness and benefits',
    'balcony safety solutions for families',
    'bird protection methods for apartments',
    'HDPE material specifications',
    'service area coverage in Chennai',
    'customization options for safety nets',
    'weather resistance and UV protection',
    'free inspection service details',
    'emergency installation availability',
    'net lifespan and replacement timeline',
    'invisible grill advantages over traditional grills',
    'children safety net requirements',
    'industrial and construction net applications',
    'sports net specifications and installation',
];

for ($i = 0; $i < $count; $i++) {
    try {
        $cat_name = $all_categories[array_rand($all_categories)];
        $topic    = $topics[array_rand($topics)];

        $prompt = "You are an expert FAQ writer for GCM Netting Solutions in Chennai, India.

Generate ONE frequently asked question and detailed answer about: '{$topic}'
Category: {$cat_name}

Requirements:
1. Question: Natural, conversational, 10-20 words, starts with What/How/Why/When/Do/Can/Is
2. Answer: Professional, informative, 150-250 words in HTML format
3. Include specific details about GCM Netting Solutions services in Chennai
4. Mention quality (HDPE material, UV stabilized), warranty (3-5 years), call +91 99123 99224
5. Tone: Helpful, confident, customer-focused
6. Naturally include keywords like 'safety nets Chennai', 'installation'

Format as JSON ONLY (no markdown, no extra text):
{\"question\": \"Your question here?\", \"answer\": \"<p>Your detailed HTML answer here.</p>\"}";

        $response = trim($gemini->generateContent($prompt));

        /* ── Robust JSON extraction ─── */
        $response = preg_replace('/^```(?:json)?\s*/s', '', $response);
        $response = preg_replace('/\s*```\s*$/s', '', $response);
        $response = trim($response);
        $fb = strpos($response, '{'); $lb = strrpos($response, '}');
        if ($fb !== false && $lb !== false && $lb > $fb) {
            $response = substr($response, $fb, $lb - $fb + 1);
        }
        $faq_data = json_decode($response, true);

        if (!$faq_data || empty($faq_data['question']) || empty($faq_data['answer'])) {
            throw new \Exception('AI response parse failed');
        }

        $question = trim($faq_data['question']);
        $answer   = trim($faq_data['answer']);
        $slug     = substr(strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $question))), 0, 100);

        $faq = [
            'id'           => time() + $i,
            'question'     => $question,
            'answer'       => $answer,
            'category'     => $cat_name,
            'slug'         => $slug,
            'tags'         => ['safety nets', 'Chennai', strtolower($cat_name)],
            'created_at'   => date('Y-m-d H:i:s'),
            'is_active'    => true,
            'views'        => 0,
            'helpful_count'=> 0,
            'source'       => 'auto_generated',
        ];

        $faq_file = $faqs_dir . '/' . $slug . '.json';
        file_put_contents($faq_file, json_encode($faq, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        /* Auto-protect */
        $_acp = dirname(__DIR__) . '/includes/ai-content-protection.php';
        if (file_exists($_acp) && !class_exists('AIContentProtection')) require_once $_acp;
        if (class_exists('AIContentProtection')) {
            try { (new AIContentProtection())->protect('faq', $slug, $question, 'data/faqs/' . $slug . '.json'); }
            catch (\Exception $e) { /* non-fatal */ }
        }

        $generated[] = ['question' => $question, 'category' => $cat_name];

        if ($i < $count - 1) sleep(2);

    } catch (\Exception $e) {
        $errors[] = $e->getMessage();
    }
}

/* ── Update stats ─────────────────────────────────────────────────── */
$stats['auto_last_run']   = $today;
$stats['auto_total']      = ($stats['auto_total'] ?? 0) + count($generated);
$stats['last_auto_count'] = count($generated);
@file_put_contents($stats_file, json_encode($stats, JSON_PRETTY_PRINT));

echo json_encode([
    'success'   => true,
    'generated' => count($generated),
    'faqs'      => $generated,
    'errors'    => $errors,
    'date'      => $today,
    'message'   => count($generated) . ' FAQs auto-generated successfully.',
]);
