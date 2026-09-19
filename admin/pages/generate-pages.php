<?php
/**
 * Admin Panel - AI Page Generator
 * Generate service pages using Gemini AI
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

// Start session with proper settings (must match login.php)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$error = '';

// Load keyword categories directly from seo_service_keywords (6 categories: PIGEON NETS, BIRD NETS, SAFETY NETS, SPORTS NETS, INVISIBLE GRILLS, CLOTH HANGERS)
$categories = $db->fetchAll(
    "SELECT category, COUNT(*) as keyword_count FROM seo_service_keywords WHERE is_active = 1 GROUP BY category ORDER BY MIN(display_order)"
);

// All keywords grouped by category
$all_keywords_flat = $db->fetchAll("SELECT * FROM seo_service_keywords WHERE is_active = 1 ORDER BY display_order");
$keywords_by_category = [];
foreach ($all_keywords_flat as $kw) {
    $keywords_by_category[$kw['category']][] = $kw;
}

// Category display icons
$category_icons = [
    'PIGEON NETS'      => 'fas fa-dove',
    'BIRD NETS'        => 'fas fa-crow',
    'SAFETY NETS'      => 'fas fa-shield-alt',
    'SPORTS NETS'      => 'fas fa-futbol',
    'INVISIBLE GRILLS' => 'fas fa-grip-lines',
    'CLOTH HANGERS'    => 'fas fa-tshirt',
];

// Get total counts
$total_keywords = $db->fetchOne("SELECT COUNT(*) as count FROM seo_service_keywords WHERE is_active = 1")['count'] ?? 0;
$total_areas = $db->fetchOne("SELECT COUNT(*) as count FROM service_areas WHERE is_active = 1")['count'] ?? 0;
$total_possible = $total_keywords * $total_areas;
$total_pillar = $total_keywords; // One pillar page per keyword
$grand_total = $total_possible + $total_pillar; // 12,032 service pages + 64 pillar pages = 12,096
// Pre-load generated page counts from DB (DB is the source of truth — no file_exists check)
$_kw_gen_counts = []; // [keyword_id => page count]
$already_generated = 0;
$_needs_sync = false; // flag: DB count < filesystem count
try {
    // Total count from DB
    $_gen_total = $db->fetchOne(
        "SELECT COUNT(*) as cnt FROM generated_pages
         WHERE keyword_id > 0 AND area_id > 0 AND page_slug IS NOT NULL AND page_slug != ''"
    );
    $already_generated = (int)($_gen_total['cnt'] ?? 0);

    // Per-keyword counts for progress bars
    $_kw_rows = $db->fetchAll(
        "SELECT keyword_id, COUNT(*) as cnt FROM generated_pages
         WHERE keyword_id > 0 AND area_id > 0 AND page_slug IS NOT NULL AND page_slug != ''
         GROUP BY keyword_id"
    );
    foreach ($_kw_rows as $_r) {
        $_kw_gen_counts[(int)$_r['keyword_id']] = (int)$_r['cnt'];
    }
} catch (\Throwable $e) {
    $already_generated = 0;
}

// Fallback: if DB count is lower than filesystem count, use filesystem count
// (happens when DB records were lost but PHP files still exist)
$_gen_dir_path = dirname(dirname(dirname(__FILE__))) . '/generated-pages/';
$_fs_files = glob($_gen_dir_path . '*.php') ?: [];
$_fs_count = count(array_filter($_fs_files, fn($f) => basename($f) !== 'index.php'));
if ($_fs_count > $already_generated) {
    $already_generated = $_fs_count;
    $_needs_sync = true; // DB is out of sync — show sync notice
}

$_site_root = dirname(dirname(dirname(__FILE__)));
$_pillar_generated = 0;
foreach ($all_keywords_flat as $_kw) {
    $_kslug = $_kw['keyword_slug'] ?? '';
    if (!$_kslug) continue;
    if (file_exists($_site_root . '/' . $_kslug . '.php')) {
        $_pillar_generated++;
    }
}

$_all_pages_generated = ($already_generated >= $total_possible) && ($_pillar_generated >= $total_pillar);

// Filesystem-based per-keyword counts (overrides DB when out of sync)
// Parses filenames like "pigeon-nets-in-abids.php" → keyword_slug="pigeon-nets", area_slug="abids"
if ($_needs_sync && !empty($_fs_files)) {
    $_slug2id = [];
    foreach ($all_keywords_flat as $_kw) {
        if (!empty($_kw['keyword_slug'])) {
            $_slug2id[$_kw['keyword_slug']] = (int)$_kw['id'];
        }
    }
    $_fs_kw_counts = [];
    foreach ($_fs_files as $_f) {
        $_fn = basename($_f, '.php');
        if ($_fn === 'index') continue;
        $_parts  = explode('-', $_fn);
        $_in_pos = array_search('in', $_parts);
        if ($_in_pos === false || $_in_pos < 1) continue;
        // Try keyword slugs from longest to shortest (handles multi-word keywords)
        for ($_klen = $_in_pos; $_klen >= 1; $_klen--) {
            $_kslug = implode('-', array_slice($_parts, 0, $_klen));
            if (isset($_slug2id[$_kslug])) {
                $_kid = $_slug2id[$_kslug];
                $_fs_kw_counts[$_kid] = ($_fs_kw_counts[$_kid] ?? 0) + 1;
                break;
            }
        }
    }
    // Use max(DB count, filesystem count) per keyword
    foreach ($_fs_kw_counts as $_kid => $_fcnt) {
        if ($_fcnt > ($_kw_gen_counts[$_kid] ?? 0)) {
            $_kw_gen_counts[$_kid] = $_fcnt;
        }
    }
}

// All generation is now handled via AJAX (generate-page-single.php).
// This POST handler is kept as a safe fallback only — the JS intercepts all form submits.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_action'])) {
    $error = 'Please use the generation popup interface. JavaScript is required for page generation.';
}

/**
 * Generate pages for a single keyword across all areas
 */
function generate_pages_for_keyword($keyword_id) {
    $db = Database::getInstance();
    
    // Get keyword details
    $keyword = $db->fetchOne(
        "SELECT k.*, k.category as service_name FROM seo_service_keywords k WHERE k.id = ?",
        [$keyword_id]
    );
    
    if (!$keyword) {
        return ['success' => false, 'message' => 'Keyword not found'];
    }
    
    // Resolve service_id for generated_pages record (reverse map)
    $cat_service_map = [
        'PIGEON NETS'      => 'pigeon-nets',
        'BIRD NETS'        => 'bird-nets',
        'SAFETY NETS'      => 'balcony-safety-nets',
        'INVISIBLE GRILLS' => 'invisible-grills',
        'SPORTS NETS'      => 'sports-nets',
        'CLOTH HANGERS'    => 'cloth-hangers',
    ];
    $svc_slug = $cat_service_map[$keyword['category']] ?? '';
    $svc_row  = $svc_slug ? $db->fetchOne("SELECT id FROM services WHERE service_slug = ?", [$svc_slug]) : null;
    $resolved_service_id = $svc_row['id'] ?? 0;

    // Get all areas
    $areas = $db->fetchAll("SELECT * FROM service_areas WHERE is_active = 1 ORDER BY area_name");
    
    $count = 0;
    $errors = [];
    
    foreach ($areas as $area) {
        // Check if page already exists
        $exists = $db->fetchOne(
            "SELECT id FROM generated_pages WHERE keyword_id = ? AND area_id = ?",
            [$keyword_id, $area['id']],
            'ii'
        );
        
        if ($exists) {
            continue; // Skip if already generated
        }
        
        // Generate content using Gemini AI
        $content_data = generate_ai_content($keyword, $area);
        
        if ($content_data['success']) {
            // Save to database
            $page_slug = create_slug($keyword['keyword_slug'] . '-in-' . $area['area_slug']);
            
            $result = $db->execute(
                "INSERT INTO generated_pages 
                (service_id, keyword_id, area_id, page_slug, page_title, meta_description, 
                 meta_keywords, h1_heading, content, word_count, is_published, generated_by, generated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'gemini_ai', CURRENT_TIMESTAMP)",
                [
                    $resolved_service_id,
                    $keyword_id,
                    $area['id'],
                    $page_slug,
                    $content_data['title'],
                    $content_data['meta_description'],
                    $content_data['meta_keywords'],
                    $content_data['h1'],
                    $content_data['content'],
                    $content_data['word_count']
                ],
                'iiisssssii'
            );
            
            if ($result['success']) {
                // Create physical PHP file
                create_service_page_file($page_slug, $content_data);
                // Auto-protect: register with AI Content Security
                if (!class_exists('AIContentProtection')) {
                    $_acp = dirname(__FILE__) . '/../includes/ai-content-protection.php';
                    if (file_exists($_acp)) require_once $_acp;
                }
                if (class_exists('AIContentProtection')) {
                    try { (new AIContentProtection())->protect('service_page', $page_slug, $content_data['title'], 'generated-pages/' . $page_slug . '.php'); }
                    catch (Exception $e) { /* non-fatal */ }
                }
                $count++;
            }
        } else {
            $errors[] = "Failed to generate: {$area['area_name']}";
        }
        
        // Rate limiting - wait 1 second between API calls
        sleep(GEMINI_REQUEST_DELAY);
    }
    
    return [
        'success' => true,
        'count' => $count,
        'errors' => $errors
    ];
}

/**
 * Generate pages for all keywords in a service
 */
function generate_pages_for_service($category) {
    $db = Database::getInstance();
    $keywords = $db->fetchAll(
        "SELECT id FROM seo_service_keywords WHERE category = ? AND is_active = 1",
        [$category]
    );
    $total_count = 0;
    foreach ($keywords as $keyword) {
        $result = generate_pages_for_keyword($keyword['id']);
        $total_count += $result['count'];
    }
    return ['success' => true, 'count' => $total_count];
}

/**
 * Generate ALL pages (use with caution - takes 2-3 hours)
 */
function generate_all_pages() {
    // This should be run as a background process
    // For now, return a message
    return [
        'success' => false,
        'message' => 'Bulk generation should be run via command line for better performance. Use: php generate-all.php'
    ];
}

/**
 * Generate content using Gemini AI
 */
function generate_ai_content($keyword, $area) {
    if (empty(GEMINI_API_KEY) || GEMINI_API_KEY === 'your-gemini-api-key-here') {
        return [
            'success' => false,
            'message' => 'Gemini API key not configured'
        ];
    }
    
    $keyword_text = $keyword['keyword'];
    $area_name = $area['area_name'];
    
    // Create AI prompt
    $prompt = "Write a comprehensive SEO-optimized article about '{$keyword_text}' services in {$area_name}, Chennai, India.

Include the following sections:
1. Introduction (150 words) - Explain the importance of {$keyword_text} in {$area_name}
2. Why Choose {$keyword_text} in {$area_name} (200 words) - Local climate conditions, weather patterns, necessity
3. Our Services (200 words) - Professional installation, quality materials, warranty
4. Benefits (150 words) - Safety, durability, aesthetic appeal
5. Installation Process (150 words) - Steps, timeline, professional team
6. Pricing & Packages (100 words) - Affordable rates, free quotation
7. Local Expertise (150 words) - Why we're best for {$area_name} residents
8. FAQs (200 words) - 5 common questions and answers
9. Conclusion & Call to Action (100 words)

Requirements:
- Total word count: 1200-1600 words
- Use location-specific keywords naturally
- Mention Chennai's climate (hot, humid summers; need for protection)
- Include GCM Netting Solutions as the service provider
- Professional, informative tone
- SEO-friendly with proper keyword density
- Include call-to-action to contact us

Write in HTML format with proper heading tags (h2, h3), paragraphs, and lists.";
    
    // Call Gemini AI API
    $response = call_gemini_ai($prompt);
    
    if (!$response['success']) {
        return $response;
    }
    
    $content = $response['content'];
    $word_count = str_word_count(strip_tags($content));
    
    // Generate meta data
    $title = ucwords($keyword_text) . " in {$area_name}, Chennai | GCM Netting Solutions";
    $meta_description = "Professional {$keyword_text} installation in {$area_name}, Chennai. Quality materials, expert installation, affordable prices. Call 9912399224 for free quote.";
    $meta_keywords = "{$keyword_text}, {$keyword_text} in {$area_name}, {$keyword_text} chennai, safety nets {$area_name}, GCM safety nets";
    $h1 = "Professional " . ucwords($keyword_text) . " in {$area_name}, Chennai";
    
    return [
        'success' => true,
        'title' => $title,
        'meta_description' => $meta_description,
        'meta_keywords' => $meta_keywords,
        'h1' => $h1,
        'content' => $content,
        'word_count' => $word_count
    ];
}

/**
 * Call Gemini AI API
 */
function call_gemini_ai($prompt) {
    $api_key = GEMINI_API_KEY;
    $api_url = GEMINI_API_URL . '?key=' . $api_key;
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 2048,
        ]
    ];
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        return [
            'success' => false,
            'message' => 'API Error: ' . $http_code
        ];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return [
            'success' => true,
            'content' => $result['candidates'][0]['content']['parts'][0]['text']
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Failed to parse AI response'
    ];
}

/**
 * Create physical PHP file for the service page
 */
function create_service_page_file($slug, $data) {
    $file_path = dirname(dirname(__DIR__)) . '/service-pages/' . $slug . '.php';
    
    $content = '<?php
define(\'GCM_INIT\', true);
require_once \'../config/config.php\';
require_once \'../config/database.php\';

$page_title = \'' . addslashes($data['title']) . '\';
$meta_description = \'' . addslashes($data['meta_description']) . '\';
$meta_keywords = \'' . addslashes($data['meta_keywords']) . '\';
$h1_heading = \'' . addslashes($data['h1']) . '\';

include \'../includes/header.php\';
?>

<article class="service-page">
    <div class="container">
        <h1><?php echo $h1_heading; ?></h1>
        
        <div class="content">
            ' . $data['content'] . '
        </div>
        
        <!-- Contact CTA -->
        <div class="cta-section">
            <h2>Get Free Quotation</h2>
            <p>Contact us today for professional installation!</p>
            <div class="cta-buttons">
                <a href="tel:' . COMPANY_PHONE . '" class="btn btn-primary">
                    <i class="fas fa-phone"></i> Call Now
                </a>
                <a href="https://wa.me/' . COMPANY_WHATSAPP . '" class="btn btn-success" target="_blank">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-secondary">
                    <i class="fas fa-envelope"></i> Contact Form
                </a>
            </div>
        </div>
    </div>
</article>

<?php include \'../includes/footer.php\'; ?>';
    
    // Create directory if it doesn't exist
    $dir = dirname($file_path);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    file_put_contents($file_path, $content);
}

// Data arrays for JS generation engine
$areas_for_js    = $db->fetchAll("SELECT id, area_name, area_slug FROM service_areas WHERE is_active = 1 ORDER BY area_name");
$keywords_for_js = $db->fetchAll("SELECT id, keyword_name, keyword_slug, category FROM seo_service_keywords WHERE is_active = 1 ORDER BY display_order");

$page_title = 'AI Page Generator';
include '../includes/header.php';
?>

<div class="seo-page">

<!-- Hero Header -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-magic"></i> AI Page Generator</h1>
        <p>Generate unique, SEO-optimized service pages using Gemini AI</p>
    </div>
    <span class="seo-hero-badge"><i class="fas fa-rocket" style="margin-right:6px;"></i><?php echo number_format($grand_total); ?> Total Pages</span>
</div>

<!-- Info Banner -->
<div class="solution-banner">
    <div class="sol-icon"><i class="fas fa-robot"></i></div>
    <div>
        <strong>Gemini AI &mdash; Service Page Factory</strong>
        <p><?php echo number_format($total_keywords); ?> keywords &times; <?php echo number_format($total_areas); ?> areas = <?php echo number_format($total_possible); ?> service pages + <?php echo number_format($total_pillar); ?> pillar pages = <strong><?php echo number_format($grand_total); ?> total pages.</strong></p>
    </div>
</div>

<?php if ($message): ?>
<div class="gp-alert gp-alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="gp-alert gp-alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Statistics -->
<div class="stats-grid gp-6col">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-key"></i></div>
        <div class="stat-text-wrap"><h3>Keywords</h3><div class="value"><?php echo number_format($total_keywords); ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-map-marker-alt"></i></div>
        <div class="stat-text-wrap"><h3>Areas</h3><div class="value"><?php echo number_format($total_areas); ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><i class="fas fa-file-code"></i></div>
        <div class="stat-text-wrap"><h3>Service Pages</h3><div class="value"><?php echo number_format($total_possible); ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap teal"><i class="fas fa-layer-group"></i></div>
        <div class="stat-text-wrap"><h3>Pillar Pages</h3><div class="value"><?php echo number_format($total_pillar); ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap amber"><i class="fas fa-globe"></i></div>
        <div class="stat-text-wrap"><h3>Grand Total</h3><div class="value"><?php echo number_format($grand_total); ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap indigo"><i class="fas fa-check-circle"></i></div>
        <div class="stat-text-wrap"><h3>Generated</h3><div class="value" id="alreadyGeneratedValue"><?php echo number_format($already_generated); ?></div></div>
    </div>
</div>

<?php if ($_needs_sync): ?>
<div class="gp-sync-notice">
    <i class="fas fa-exclamation-triangle"></i>
    <div><strong>DB out of sync:</strong> <?php echo number_format($already_generated); ?> PHP files found but DB records are missing. Go to <a href="content-export.php">Content Protection</a> and click <strong>"Sync PHP Files &rarr; DB"</strong> to restore View Generated Pages.</div>
</div>
<?php endif; ?>

<!-- Section 1: Generate by Keyword -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">1</div>
        <h2><i class="fas fa-key" style="color:#667eea;margin-right:8px;"></i>Generate by Keyword</h2>
    </div>
    <div class="seo-section-body">
        <p class="gp-section-desc">Select a keyword to generate pages for all <?php echo number_format($total_areas); ?> areas in Chennai</p>
        <form id="formByKeyword" method="POST" class="generation-form">
            <input type="hidden" name="generate_action" value="single_keyword">
            <?php foreach ($categories as $cat_row): ?>
                <?php $cat_keywords = $keywords_by_category[$cat_row['category']] ?? []; ?>
                <?php if (!empty($cat_keywords)): ?>
                <div class="service-group">
                    <h3><i class="<?php echo $category_icons[$cat_row['category']] ?? 'fas fa-shield-alt'; ?>"></i> <?php echo ucwords(strtolower($cat_row['category'])); ?></h3>
                    <div class="keyword-grid">
                        <?php foreach ($cat_keywords as $keyword): ?>
                            <?php
                            $keyword_count = $_kw_gen_counts[$keyword['id']] ?? 0;
                            $is_complete = $keyword_count >= $total_areas;
                            ?>
                            <label class="keyword-option <?php echo $is_complete ? 'complete' : ''; ?>">
                                <input type="radio" name="keyword_id" value="<?php echo $keyword['id']; ?>" required <?php echo $is_complete ? 'disabled' : ''; ?>>
                                <span class="keyword-name"><?php echo htmlspecialchars($keyword['keyword_name']); ?></span>
                                <span class="keyword-count"><?php echo $keyword_count; ?> / <?php echo $total_areas; ?></span>
                                <?php if ($is_complete): ?><i class="fas fa-check-circle" style="color:#10b981;font-size:16px;"></i><?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="action-row" style="margin-top:24px;">
                <button type="submit" class="btn-action green big">
                    <i class="fas fa-magic"></i> Generate Pages for Selected Keyword (<?php echo number_format($total_areas); ?> pages)
                </button>
            </div>
            <p class="gp-help-text"><i class="fas fa-info-circle"></i> Approximately <?php echo number_format($total_areas); ?> pages will be generated. Estimated time: 4&ndash;6 minutes.</p>
        </form>
    </div>
</div>

<!-- Section 2: Generate by Service -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num blue">2</div>
        <h2><i class="fas fa-layer-group" style="color:#3b82f6;margin-right:8px;"></i>Generate by Service Category</h2>
    </div>
    <div class="seo-section-body">
        <p class="gp-section-desc">Generate pages for all keywords in a service category</p>
        <form id="formByService" method="POST" class="generation-form">
            <input type="hidden" name="generate_action" value="single_service">
            <div class="service-grid-large">
                <?php foreach ($categories as $cat_row): ?>
                    <?php
                    $cat_keywords     = $keywords_by_category[$cat_row['category']] ?? [];
                    $svc_kw_count     = count($cat_keywords);
                    $cat_kw_ids       = array_column($cat_keywords, 'id');
                    $svc_page_count = 0;
                    foreach ($cat_kw_ids as $_kid) {
                        $svc_page_count += $_kw_gen_counts[$_kid] ?? 0;
                    }
                    $svc_total = $svc_kw_count * $total_areas;
                    $svc_complete = ($svc_total > 0 && $svc_page_count >= $svc_total);
                    ?>
                    <label class="service-option-large <?php echo $svc_complete ? 'complete' : ''; ?>">
                        <input type="radio" name="category_name" value="<?php echo htmlspecialchars($cat_row['category']); ?>" required <?php echo $svc_complete ? 'disabled' : ''; ?>>
                        <div class="service-icon">
                            <i class="<?php echo $category_icons[$cat_row['category']] ?? 'fas fa-shield-alt'; ?>"></i>
                        </div>
                        <h3><?php echo ucwords(strtolower($cat_row['category'])); ?></h3>
                        <div class="service-stats">
                            <span><?php echo $svc_kw_count; ?> Keywords</span>
                            <span><?php echo $svc_page_count; ?> / <?php echo $svc_total; ?> Pages</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $svc_total > 0 ? round($svc_page_count / $svc_total * 100) : 0; ?>%"></div>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="action-row" style="margin-top:24px;">
                <button type="submit" class="btn-action purple big">
                    <i class="fas fa-layer-group"></i> Generate All Pages for Selected Service
                </button>
            </div>
            <p class="gp-help-text"><i class="fas fa-info-circle"></i> May generate 1,000+ pages. Estimated time: 20&ndash;30 minutes.</p>
        </form>
    </div>
</div>

<!-- Section 3: Generate All -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num amber">3</div>
        <h2><i class="fas fa-globe" style="color:#f59e0b;margin-right:8px;"></i>Generate ALL Pages</h2>
    </div>
    <div class="seo-section-body">
        <?php if ($_all_pages_generated): ?>
            <div class="gp-alert gp-alert-success" style="margin-bottom:16px;">
                <i class="fas fa-check-circle"></i>
                All service pages and pillar pages are already generated. Bulk generation is disabled to protect your existing content.
            </div>
        <?php endif; ?>
        <div class="gp-warning-banner">
            <i class="fas fa-exclamation-triangle"></i>
            <p>This will generate all <strong><?php echo number_format($grand_total); ?>+</strong> pages (<?php echo number_format($total_possible); ?> service + <?php echo number_format($total_pillar); ?> pillar). Estimated time: 2&ndash;3 hours. Estimated cost: $15&ndash;20 USD.</p>
        </div>
        <div class="warning-box">
            <h3>Before You Proceed:</h3>
            <ul>
                <li><i class="fas fa-check"></i> Ensure sufficient Gemini AI API credits</li>
                <li><i class="fas fa-check"></i> Estimated cost: $15&ndash;20 USD</li>
                <li><i class="fas fa-check"></i> Estimated time: 2&ndash;3 hours</li>
                <li><i class="fas fa-check"></i> Server must remain active during generation</li>
                <li><i class="fas fa-check"></i> Database backup recommended</li>
            </ul>
        </div>
        <form id="formGenerateAll" method="POST" class="generation-form">
            <input type="hidden" name="generate_action" value="generate_all">
            <div class="gp-form-group">
                <label>Type "GENERATE ALL PAGES" to confirm:</label>
                <input type="text" name="confirm_all" class="gp-form-control" placeholder="GENERATE ALL PAGES" required <?php echo $_all_pages_generated ? 'disabled' : ''; ?>>
            </div>
            <div class="action-row" style="margin-top:20px;">
                <button type="submit" class="btn-action red big" <?php echo $_all_pages_generated ? 'disabled' : ''; ?>>
                    <i class="fas fa-globe"></i> Generate All <?php echo number_format($grand_total); ?>+ Pages
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bottom Buttons -->
<div class="action-row" style="margin-bottom:32px;">
    <a href="../dashboard.php" class="btn-action gray"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <a href="view-generated-pages.php" class="btn-action purple"><i class="fas fa-eye"></i> View Generated Pages</a>
</div>

</div>

<style>
/* ===== MODERN SERVICE PAGE GENERATOR STYLING ===== */

/* Completed options become non-interactive to avoid accidental re-generation */
.keyword-option.complete,
.service-option-large.complete {
    opacity: 0.55;
    cursor: not-allowed;
}
.keyword-option.complete input,
.service-option-large.complete input {
    cursor: not-allowed;
}

/* Keyframe Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

@keyframes glow {
    0%, 100% {
        box-shadow: 0 0 20px rgba(102, 126, 234, 0.4);
    }
    50% {
        box-shadow: 0 0 40px rgba(102, 126, 234, 0.8);
    }
}

/* Page Container */
.page-generator { 
    padding: 30px; 
    max-width: 1400px; 
    margin: 0 auto;
    background: #f1f5f9;
    min-height: 100vh;
    animation: fadeInUp 0.6s ease-out;
}

/* Page Header */
.page-header { 
    background: white;
    border-radius: 18px;
    padding: 28px 32px;
    margin-bottom: 24px;
    box-shadow: 0 4px 24px rgba(0,0,0,.07);
    border-left: 6px solid transparent;
    border-image: linear-gradient(180deg,#667eea,#764ba2) 1;
}

.page-header h1 { 
    font-size: 28px;
    font-weight: 800;
    color: #1e293b;
    margin: 0 0 5px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-header h1 i { color: #667eea; }

.page-header p { 
    font-size: 14px;
    margin: 0;
    color: #64748b;
}

/* Stats Cards */
.stats-cards { 
    display: grid;
    grid-template-columns: repeat(6,1fr);
    gap: 0;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
    overflow: hidden;
    margin-bottom: 24px;
}

.stat-card { 
    padding: 22px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    position: relative;
    background: white;
    transition: background .2s;
}

.stat-card:hover { background: #f8faff; }

.stat-card + .stat-card::before {
    content: '';
    position: absolute;
    left: 0; top: 15%; bottom: 15%;
    width: 1px;
    background: #e2e8f0;
}

.stat-icon-box {
    width: 46px; height: 46px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 19px; color: white; flex-shrink: 0;
}
.stat-icon-box.purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.stat-icon-box.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.stat-icon-box.green  { background: linear-gradient(135deg,#10b981,#059669); }
.stat-icon-box.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.stat-icon-box.teal   { background: linear-gradient(135deg,#06b6d4,#0284c7); }
.stat-icon-box.indigo { background: linear-gradient(135deg,#6366f1,#4f46e5); }
.gp-stat-text { flex: 1; }

.stat-value { 
    font-size: 1.6rem;
    font-weight: 800;
    color: #1e293b;
    line-height: 1.1;
    margin: 0 0 3px;
}

.stat-label { 
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #94a3b8;
}

/* Tabs */
.generation-tabs { 
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 12px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.tab-btn { 
    flex: 1;
    padding: 18px 24px;
    border: none;
    background: transparent;
    color: #64748B;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    position: relative;
}

.tab-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 12px;
    padding: 2px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    opacity: 0;
    transition: opacity 0.3s;
}

.tab-btn:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    transform: translateY(-2px);
}

.tab-btn:hover::before {
    opacity: 1;
}

.tab-btn.active { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    transform: translateY(-2px);
}

.tab-btn.active i {
    animation: pulse 2s infinite;
}

/* Tab Content */
.tab-content { 
    display: none;
}

.tab-content.active { 
    display: block;
    animation: fadeInUp 0.5s ease-out;
}

/* Panel */
.panel { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
}

.panel:hover {
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
}

.panel h2 { 
    font-size: 32px;
    margin: 0 0 12px 0;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 700;
}

.panel p { 
    color: #64748B;
    margin: 0 0 30px 0;
    font-size: 17px;
    line-height: 1.6;
}

.panel-warning { 
    border: 3px solid #F59E0B;
    background: linear-gradient(135deg, rgba(254, 243, 199, 0.95), rgba(253, 230, 138, 0.95));
    animation: glow 2s infinite;
}

.warning-text { 
    color: #92400E;
    font-weight: 700;
    font-size: 18px;
}

/* Service Groups */
.service-group { 
    margin-bottom: 35px;
    animation: fadeInUp 0.6s ease-out;
}

.service-group h3 { 
    font-size: 22px;
    color: #1E293B;
    margin-bottom: 18px;
    padding: 20px;
    background: linear-gradient(135deg, rgba(239, 246, 255, 0.95), rgba(219, 234, 254, 0.95));
    backdrop-filter: blur(10px);
    border-radius: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 700;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.15);
    border: 2px solid rgba(102, 126, 234, 0.2);
}

.keyword-grid { 
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.keyword-option { 
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px;
    background: rgba(248, 250, 252, 0.95);
    backdrop-filter: blur(10px);
    border: 2px solid #E2E8F0;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.keyword-option::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
    transition: left 0.5s;
}

.keyword-option:hover::before {
    left: 100%;
}

.keyword-option:hover { 
    border-color: #667eea;
    background: rgba(241, 245, 249, 0.95);
    transform: translateX(8px) scale(1.02);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
}

.keyword-option input[type="radio"] { 
    width: 22px;
    height: 22px;
    cursor: pointer;
    accent-color: #667eea;
}

.keyword-option input[type="radio"]:checked ~ * {
    color: #667eea;
}

.keyword-option.complete { 
    background: linear-gradient(135deg, rgba(209, 250, 229, 0.95), rgba(167, 243, 208, 0.95));
    border-color: #10B981;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
}

.keyword-name { 
    flex: 1;
    font-weight: 700;
    color: #334155;
    font-size: 15px;
}

.keyword-count { 
    font-size: 13px;
    color: #64748B;
    background: white;
    padding: 6px 12px;
    border-radius: 8px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.keyword-option .fa-check-circle { 
    color: #10B981;
    font-size: 24px;
    animation: pulse 2s infinite;
}

/* Service Grid Large */
.service-grid-large { 
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 30px;
}

.service-option-large { 
    display: block;
    padding: 30px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(248, 250, 252, 0.95));
    backdrop-filter: blur(10px);
    border: 2px solid #E2E8F0;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    text-align: center;
    position: relative;
    overflow: hidden;
    animation: fadeInUp 0.6s ease-out;
    animation-fill-mode: both;
}

.service-option-large:nth-child(1) { animation-delay: 0.1s; }
.service-option-large:nth-child(2) { animation-delay: 0.15s; }
.service-option-large:nth-child(3) { animation-delay: 0.2s; }
.service-option-large:nth-child(4) { animation-delay: 0.25s; }
.service-option-large:nth-child(5) { animation-delay: 0.3s; }
.service-option-large:nth-child(6) { animation-delay: 0.35s; }

.service-option-large::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(102, 126, 234, 0.1), transparent);
    transform: rotate(45deg);
    transition: all 0.6s;
}

.service-option-large:hover::before {
    left: 100%;
}

.service-option-large:hover { 
    border-color: #667eea;
    box-shadow: 0 15px 50px rgba(102, 126, 234, 0.3);
    transform: translateY(-8px) scale(1.02);
}

.service-option-large input[type="radio"] { 
    position: absolute;
    top: 20px;
    right: 20px;
    width: 26px;
    height: 26px;
    cursor: pointer;
    accent-color: #667eea;
}

.service-icon { 
    width: 90px;
    height: 90px;
    margin: 0 auto 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
    color: white;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
}

.service-option-large:hover .service-icon {
    transform: rotate(10deg) scale(1.1);
    animation: pulse 1s infinite;
}

.service-option-large h3 { 
    font-size: 20px;
    margin: 0 0 18px 0;
    color: #1E293B;
    font-weight: 700;
}

.service-stats { 
    display: flex;
    justify-content: center;
    gap: 24px;
    margin-bottom: 18px;
    font-size: 15px;
    color: #64748B;
    font-weight: 600;
}

.progress-bar { 
    width: 100%;
    height: 10px;
    background: linear-gradient(135deg, #E2E8F0, #CBD5E1);
    border-radius: 6px;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.progress-fill { 
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
    position: relative;
}

.progress-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: shimmer 2s linear infinite;
}

/* Warning Box */
.warning-box { 
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 30px;
    border-radius: 16px;
    border-left: 5px solid #F59E0B;
    margin: 24px 0;
    box-shadow: 0 8px 24px rgba(245, 158, 11, 0.2);
}

.warning-box h3 { 
    font-size: 20px;
    margin: 0 0 18px 0;
    color: #92400E;
    font-weight: 700;
}

.warning-box ul { 
    list-style: none;
    padding: 0;
    margin: 0;
}

.warning-box li { 
    padding: 12px 0;
    color: #78350F;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    font-size: 15px;
}

.warning-box .fa-check { 
    color: #10B981;
    font-size: 18px;
}

/* Form */
.form-group { 
    margin-bottom: 24px;
}

.form-group label { 
    display: block;
    font-weight: 700;
    color: #334155;
    margin-bottom: 10px;
    font-size: 16px;
}

.form-control { 
    width: 100%;
    padding: 14px 18px;
    border: 2px solid #E2E8F0;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
}

.form-control:focus { 
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.form-actions { 
    margin-top: 35px;
    text-align: center;
}

.btn { 
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 18px 36px;
    border: none;
    border-radius: 14px;
    font-size: 17px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn:hover::before {
    width: 300px;
    height: 300px;
}

.btn-primary { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover { 
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(102, 126, 234, 0.5);
}

.btn-danger { 
    background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
    color: white;
}

.btn-danger:hover { 
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(239, 68, 68, 0.5);
}

.btn-large { 
    padding: 22px 44px;
    font-size: 19px;
}

.btn i {
    position: relative;
    z-index: 1;
}

.btn span {
    position: relative;
    z-index: 1;
}

.help-text { 
    margin-top: 18px;
    color: #64748B;
    font-size: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 500;
}

.help-text code { 
    background: #1E293B;
    color: #10B981;
    padding: 6px 12px;
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-weight: 600;
}

/* Alerts */
.alert { 
    padding: 24px;
    border-radius: 16px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 14px;
    font-size: 17px;
    font-weight: 700;
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    animation: slideInRight 0.5s ease-out;
}

.alert-success { 
    background: linear-gradient(135deg, rgba(209, 250, 229, 0.95), rgba(167, 243, 208, 0.95));
    color: #065F46;
    border: 2px solid #10B981;
}

.alert-error { 
    background: linear-gradient(135deg, rgba(254, 226, 226, 0.95), rgba(254, 202, 202, 0.95));
    color: #991B1B;
    border: 2px solid #EF4444;
}

.alert i { 
    font-size: 28px;
    animation: pulse 2s infinite;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .stats-cards { 
        grid-template-columns: repeat(2, 1fr);
    }
    .service-grid-large {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .page-generator {
        padding: 20px;
    }
    .stats-cards { 
        grid-template-columns: 1fr;
    }
    .generation-tabs { 
        flex-direction: column;
    }
    .service-grid-large { 
        grid-template-columns: 1fr;
    }
    .keyword-grid {
        grid-template-columns: 1fr;
    }
    .page-header h1 {
        font-size: 32px;
    }
    .page-header p {
        font-size: 16px;
    }
}
</style>

<style>
/* ── New theme: Complete SEO System design ─────────────── */
.seo-page { padding: 0; }
.seo-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 32px 36px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-left: 6px solid transparent; border-image: linear-gradient(180deg,#667eea,#764ba2) 1; }
.seo-hero-left h1 { font-size: 30px; font-weight: 800; color: #1e293b; margin: 0 0 6px; display: flex; align-items: center; gap: 12px; }
.seo-hero-left h1 i { color: #667eea; }
.seo-hero-left p { color: #64748b; font-size: 15px; margin: 0; }
.seo-hero-badge { background: linear-gradient(135deg,#667eea,#764ba2); color: white; padding: 8px 20px; border-radius: 30px; font-size: 13px; font-weight: 700; white-space: nowrap; flex-shrink: 0; }
.solution-banner { background: linear-gradient(135deg,rgba(102,126,234,.08),rgba(118,75,162,.05)); border: 1px solid rgba(102,126,234,.2); border-left: 5px solid #667eea; border-radius: 12px; padding: 18px 22px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px; }
.solution-banner .sol-icon { width: 40px; height: 40px; background: linear-gradient(135deg,#667eea,#764ba2); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; flex-shrink: 0; margin-top: 2px; }
.solution-banner strong { color: #3730a3; font-size: 14px; font-weight: 700; display: block; margin-bottom: 4px; }
.solution-banner p { color: #4338ca; font-size: 13.5px; margin: 0; line-height: 1.6; }
/* Stats grid */
.stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 24px; }
.gp-6col { grid-template-columns: repeat(6,1fr) !important; }
.stat-card { background: white; padding: 22px 16px !important; display: flex !important; align-items: center !important; gap: 14px !important; text-align: left !important; border: none !important; border-radius: 0 !important; box-shadow: none !important; position: relative; transition: background .2s; }
.stat-card:hover { background: #f8faff !important; transform: none !important; }
.stat-card + .stat-card::before { content: ''; position: absolute; left: 0; top: 15%; bottom: 15%; width: 1px; background: #e2e8f0; }
.stat-icon-wrap { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; flex-shrink: 0; }
.stat-icon-wrap.blue   { background: linear-gradient(135deg,#667eea,#764ba2); }
.stat-icon-wrap.green  { background: linear-gradient(135deg,#10b981,#059669); }
.stat-icon-wrap.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.stat-icon-wrap.teal   { background: linear-gradient(135deg,#06b6d4,#0891b2); }
.stat-icon-wrap.amber  { background: linear-gradient(135deg,#f97316,#ea580c); }
.stat-icon-wrap.indigo { background: linear-gradient(135deg,#6366f1,#4f46e5); }
.stat-text-wrap { flex: 1; min-width: 0; }
.stat-text-wrap h3 { font-size: 9px !important; color: #94a3b8 !important; text-transform: uppercase !important; letter-spacing: .7px !important; font-weight: 700 !important; margin: 0 0 3px !important; background: none !important; -webkit-text-fill-color: unset !important; }
.stat-text-wrap .value { font-size: 1.55rem !important; font-weight: 800 !important; color: #1e293b !important; line-height: 1.1 !important; margin: 0 !important; background: none !important; -webkit-text-fill-color: unset !important; }
/* Section card */
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; }
.seo-section-head { padding: 20px 28px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
.seo-section-head .sec-num { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-num.purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.sec-num.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.sec-num.amber  { background: linear-gradient(135deg,#f59e0b,#d97706); }
.seo-section-head h2 { font-size: 19px; font-weight: 700; color: #1e293b; margin: 0; background: none !important; -webkit-text-fill-color: unset !important; }
.seo-section-body { padding: 26px 28px; }
/* Action buttons */
.action-row { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
.btn-action { display: inline-flex; align-items: center; gap: 10px; padding: 13px 28px; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .25s; }
.btn-action:hover { transform: translateY(-3px); text-decoration: none; }
.btn-action.green  { background: linear-gradient(135deg,#28a745,#20c997); color: white; box-shadow: 0 6px 20px rgba(40,167,69,.3); }
.btn-action.green:hover  { box-shadow: 0 10px 28px rgba(40,167,69,.45); }
.btn-action.purple { background: linear-gradient(135deg,#667eea,#764ba2); color: white; box-shadow: 0 6px 20px rgba(102,126,234,.3); }
.btn-action.purple:hover { box-shadow: 0 10px 28px rgba(102,126,234,.45); }
.btn-action.red    { background: linear-gradient(135deg,#ef4444,#dc2626); color: white; box-shadow: 0 6px 20px rgba(239,68,68,.3); }
.btn-action.red:hover    { box-shadow: 0 10px 28px rgba(239,68,68,.45); }
.btn-action.gray   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.btn-action.gray:hover   { background: #e2e8f0; transform: none; }
.btn-action.big { padding: 17px 38px; font-size: 16px; border-radius: 13px; }
/* Alerts & notices */
.gp-alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; }
.gp-alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.gp-alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.gp-sync-notice { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 10px; padding: 13px 18px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 12px; font-size: 13.5px; color: #92400e; }
.gp-sync-notice i { color: #d97706; font-size: 17px; flex-shrink: 0; margin-top: 1px; }
.gp-sync-notice a { color: #d97706; font-weight: 700; }
/* Section utils */
.gp-section-desc { color: #64748b; font-size: 14px; margin: 0 0 20px; }
.gp-help-text { text-align: center; color: #64748b; font-size: 13px; margin: 10px 0 0; display: flex; align-items: center; justify-content: center; gap: 7px; }
.gp-help-text i { color: #667eea; }
/* Service group (override old glass style) */
.service-group { margin-bottom: 26px; }
.service-group h3 { font-size: 12px; font-weight: 700; color: #475569; margin: 0 0 12px; display: flex; align-items: center; gap: 8px; padding: 9px 14px; background: #f8faff; border-radius: 8px; border-left: 3px solid #667eea; text-transform: uppercase; letter-spacing: .6px; backdrop-filter: none; box-shadow: none !important; animation: none !important; }
.service-group h3 i { color: #667eea; }
/* Keyword options (clean flat override) */
.keyword-option { display: flex !important; align-items: center !important; gap: 10px !important; padding: 11px 14px !important; background: #f8faff !important; border: 1.5px solid #e2e8f0 !important; border-radius: 10px !important; cursor: pointer; transition: border-color .2s, background .2s !important; backdrop-filter: none !important; transform: none !important; box-shadow: none !important; }
.keyword-option::before { display: none !important; }
.keyword-option:hover { border-color: #667eea !important; background: #eff6ff !important; transform: none !important; box-shadow: none !important; }
.keyword-option.complete { background: #f0fdf4 !important; border-color: #10b981 !important; }
/* Service option large (clean) */
.service-option-large { padding: 22px 18px !important; background: white !important; border: 1.5px solid #e2e8f0 !important; border-radius: 14px !important; backdrop-filter: none !important; animation: none !important; }
.service-option-large::before { display: none !important; }
.service-option-large:hover { border-color: #667eea !important; box-shadow: 0 8px 22px rgba(102,126,234,.18) !important; transform: translateY(-4px) !important; }
.service-icon { width: 60px !important; height: 60px !important; background: linear-gradient(135deg,#667eea,#764ba2) !important; border-radius: 50%; box-shadow: none !important; }
/* Warning */
.gp-warning-banner { background: #fef3c7; border: 1px solid #f59e0b; border-left: 5px solid #f59e0b; border-radius: 12px; padding: 15px 18px; margin-bottom: 18px; display: flex; align-items: flex-start; gap: 12px; font-size: 13.5px; color: #92400e; }
.gp-warning-banner i { color: #d97706; font-size: 17px; flex-shrink: 0; margin-top: 2px; }
.gp-warning-banner p { margin: 0; line-height: 1.6; }
/* Form */
.gp-form-group { margin-bottom: 16px; }
.gp-form-group label { display: block; font-weight: 700; color: #334155; margin-bottom: 7px; font-size: 14px; }
.gp-form-control { width: 100%; padding: 11px 15px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; transition: border-color .2s; box-sizing: border-box; background: white; }
.gp-form-control:focus { outline: none; border-color: #667eea; }
/* Responsive */
@media(max-width:1100px){ .gp-6col { grid-template-columns: repeat(3,1fr) !important; } }
@media(max-width:720px) { .gp-6col { grid-template-columns: repeat(2,1fr) !important; } .seo-hero { flex-direction: column; align-items: flex-start; } .service-grid-large { grid-template-columns: 1fr 1fr; } }
@media(max-width:500px) { .service-grid-large { grid-template-columns: 1fr; } }
</style>

<!-- ═══ Generation Progress Popup ═══ -->
<div id="genOverlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(15,23,42,0.82);z-index:99999;backdrop-filter:blur(6px);">
  <div id="genModal" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:20px;width:92%;max-width:780px;max-height:92vh;overflow-y:auto;box-shadow:0 50px 120px rgba(0,0,0,0.5);">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:24px 28px;border-radius:20px 20px 0 0;">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
          <h2 id="genTitle" style="color:white;margin:0;font-size:20px;font-weight:700;">&#x1F680; Generating Pages</h2>
          <p id="genSubtitle" style="color:rgba(255,255,255,.85);margin:5px 0 0;font-size:13px;">Preparing...</p>
        </div>
        <div style="display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap;">
          <div>
            <label style="color:rgba(255,255,255,.8);font-size:11px;display:block;margin-bottom:4px;">SPEED</label>
            <select id="genSpeed" style="background:white;color:#1e293b;border:1px solid rgba(255,255,255,.4);padding:6px 10px;border-radius:8px;font-size:13px;font-weight:600;">
              <option value="safe">&#x1F40C; Safe (1 at a time)</option>
              <option value="normal" selected>&#x26A1; Normal (2 parallel)</option>
              <option value="fast">&#x1F680; Fast (4 parallel)</option>
              <option value="turbo">&#x26A1;&#x26A1; Turbo (8 parallel)</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(5,1fr);background:#f8fafc;border-bottom:1px solid #e2e8f0;">
      <div style="padding:16px;text-align:center;border-right:1px solid #e2e8f0;">
        <div id="genGenerated" style="font-size:26px;font-weight:800;color:#10B981;">0</div>
        <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;">Generated</div>
      </div>
      <div style="padding:16px;text-align:center;border-right:1px solid #e2e8f0;">
        <div id="genSkipped" style="font-size:26px;font-weight:800;color:#3B82F6;">0</div>
        <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;">Skipped</div>
      </div>
      <div style="padding:16px;text-align:center;border-right:1px solid #e2e8f0;">
        <div id="genFailed" style="font-size:26px;font-weight:800;color:#EF4444;">0</div>
        <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;">Failed</div>
      </div>
      <div style="padding:16px;text-align:center;border-right:1px solid #e2e8f0;">
        <div id="genTotal" style="font-size:26px;font-weight:800;color:#667eea;">0</div>
        <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;">Total</div>
      </div>
      <div style="padding:16px;text-align:center;">
        <div id="genETA" style="font-size:20px;font-weight:800;color:#F59E0B;">--</div>
        <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;">ETA</div>
      </div>
    </div>

    <!-- Progress Bar -->
    <div style="padding:18px 24px 6px;">
      <div style="background:#f1f5f9;border-radius:50px;height:18px;overflow:hidden;position:relative;">
        <div id="genProgressBar" style="height:100%;width:0%;background:linear-gradient(90deg,#667eea,#10B981);border-radius:50px;transition:width .4s ease;min-width:0;"></div>
      </div>
      <p id="genCurrentPage" style="color:#64748b;font-size:12px;margin:7px 0 0;text-align:center;">Select speed and click Start</p>
    </div>

    <!-- Log -->
    <div style="padding:8px 24px 12px;">
      <div id="genLog" style="background:#0f172a;border-radius:10px;height:195px;overflow-y:auto;padding:14px;font-family:'Courier New',monospace;font-size:12px;line-height:1.6;"></div>
    </div>

    <!-- Controls -->
    <div style="padding:16px 24px 20px;display:flex;gap:10px;justify-content:center;flex-wrap:wrap;border-top:1px solid #f0f4f8;">
      <button id="genStartBtn" onclick="genStart()" style="background:linear-gradient(135deg,#10B981,#059669);color:white;border:none;padding:11px 26px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;">&#x25B6; Start</button>
      <button id="genPauseBtn" onclick="genTogglePause()" disabled style="background:linear-gradient(135deg,#F59E0B,#D97706);color:white;border:none;padding:11px 26px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;opacity:.45;">&#x23F8; Pause</button>
      <button id="genStopBtn" onclick="genStop()" disabled style="background:linear-gradient(135deg,#EF4444,#DC2626);color:white;border:none;padding:11px 26px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;opacity:.45;">&#x23F9; Stop</button>
      <button id="genRetryBtn" onclick="genRetryFailed()" style="display:none;background:linear-gradient(135deg,#8B5CF6,#6D28D9);color:white;border:none;padding:11px 26px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;align-items:center;gap:6px;">&#x1F504; Retry Failed</button>
      <button onclick="genClose()" style="background:#f1f5f9;color:#475569;border:none;padding:11px 26px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;">&#x2715; Close</button>
    </div>

  </div>
</div>

<style>
@keyframes genShimmer{0%{transform:translateX(-100%)}100%{transform:translateX(100%)}}
#genProgressBar::after{content:'';position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.35),transparent);animation:genShimmer 1.6s linear infinite;}
#genModal select option{color:#333;background:white;}
</style>

<script>
// ─── PHP data ─────────────────────────────────────────────
const GCM_AREAS    = <?php echo json_encode(array_values($areas_for_js)); ?>;
const GCM_KEYWORDS = <?php echo json_encode(array_values($keywords_for_js)); ?>;
const GCM_API      = '../api/generate-page-single.php';
// Pre-built set of already-generated keyword_id+area_id pairs.
// Updated in real-time as pages are generated — all 3 modes skip correctly
// regardless of which mode was used previously.
const GCM_GENERATED_SET = new Set(<?php
    $_gen_pairs = [];
    foreach ($_db_pages as $_p) {
        if (!empty($_p['page_slug']) && file_exists($_gen_dir . $_p['page_slug'] . '.php')) {
            $_gen_pairs[] = (int)$_p['keyword_id'] . '_' . (int)$_p['area_id'];
        }
    }
    echo json_encode($_gen_pairs);
?>);
function isGenerated(kid, aid) { return GCM_GENERATED_SET.has(kid + '_' + aid); }

const SPEED_CFG = {
    safe:   { concurrency: 1, delay: 3000 },
    normal: { concurrency: 2, delay: 1000 },
    fast:   { concurrency: 3, delay:  300 },
    turbo:  { concurrency: 4, delay:  100 },
};

// ─── State ───────────────────────────────────────────────
let genQueue       = [];
let genState       = { generated:0, skipped:0, failed:0, total:0, idx:0 };
let genRunning     = false;
let genPaused      = false;
let genStopped     = false;
let genStartTime   = null;
let genControllers = []; // AbortControllers for in-flight requests
let genFailedTasks = []; // Tasks that failed — used for Retry button

// ─── Popup control ────────────────────────────────────────
function genShowPopup(title, subtitle, tasks) {
    genQueue     = tasks;
    genState     = { generated:0, skipped:0, failed:0, total:tasks.length, idx:0 };
    genRunning   = false; genPaused = false; genStopped = false; genStartTime = null;
    genFailedTasks = [];
    document.getElementById('genTitle').textContent       = title;
    document.getElementById('genSubtitle').textContent    = subtitle;
    document.getElementById('genTotal').textContent       = tasks.length;
    document.getElementById('genGenerated').textContent   = '0';
    document.getElementById('genSkipped').textContent     = '0';
    document.getElementById('genFailed').textContent      = '0';
    document.getElementById('genProgressBar').style.width = '0%';
    document.getElementById('genCurrentPage').textContent = 'Select speed and click Start';
    document.getElementById('genLog').innerHTML            = '';
    document.getElementById('genETA').textContent         = '--';
    document.getElementById('genStartBtn').disabled       = false;
    document.getElementById('genPauseBtn').disabled       = true;
    document.getElementById('genStopBtn').disabled        = true;
    document.getElementById('genPauseBtn').style.opacity  = '.45';
    document.getElementById('genStopBtn').style.opacity   = '.45';
    const _rb = document.getElementById('genRetryBtn');
    if (_rb) _rb.style.display = 'none';
    document.getElementById('genOverlay').style.display   = 'block';
    genAddLog('\u{1F3AF} Queue ready: ' + tasks.length + ' pages', 'info');
    genAddLog('\u26A1 Select speed above and click Start', 'info');
}

function genClose() {
    if (genRunning && !confirm('Generation is running. Stop and close?')) return;
    genStopped = true; genRunning = false;
    document.getElementById('genOverlay').style.display = 'none';
}

function genAddLog(msg, type) {
    const colors = { success:'#10B981', error:'#EF4444', info:'#60a5fa', warn:'#F59E0B' };
    const log = document.getElementById('genLog');
    const el  = document.createElement('div');
    el.style.color        = colors[type] || colors.info;
    el.style.marginBottom = '2px';
    el.textContent        = new Date().toLocaleTimeString() + ' › ' + msg;
    log.appendChild(el);
    log.scrollTop = log.scrollHeight;
}

function genUpdateStats() {
    const s = genState;
    document.getElementById('genGenerated').textContent = s.generated;
    document.getElementById('genSkipped').textContent   = s.skipped;
    document.getElementById('genFailed').textContent    = s.failed;
    const done = s.generated + s.skipped + s.failed;
    const pct  = s.total > 0 ? Math.round((done / s.total) * 100) : 0;
    document.getElementById('genProgressBar').style.width = pct + '%';
    if (genStartTime && done > 0) {
        const perPage   = (Date.now() - genStartTime) / 1000 / done;
        const remaining = (s.total - done) * perPage;
        document.getElementById('genETA').textContent =
            remaining < 60  ? Math.round(remaining) + 's'
          : remaining < 3600 ? Math.round(remaining / 60) + 'm'
          : (remaining / 3600).toFixed(1) + 'h';
    }
}

// ─── Start / Pause / Stop ────────────────────────────────
async function genStart() {
    if (genRunning) return;
    genRunning = true; genPaused = false; genStopped = false;
    genStartTime = genStartTime || Date.now();
    document.getElementById('genStartBtn').disabled = true;
    document.getElementById('genPauseBtn').disabled = false;
    document.getElementById('genStopBtn').disabled  = false;
    document.getElementById('genPauseBtn').style.opacity = '1';
    document.getElementById('genStopBtn').style.opacity  = '1';
    const cfg = SPEED_CFG[document.getElementById('genSpeed').value] || SPEED_CFG.normal;
    genAddLog('\u{1F680} Speed: ' + document.getElementById('genSpeed').value + ' (' + cfg.concurrency + ' parallel, ' + cfg.delay + 'ms delay)', 'info');
    await Promise.all(Array.from({ length: cfg.concurrency }, () => genWorker(cfg.delay)));
    genRunning = false;
    document.getElementById('genStartBtn').disabled = false;
    document.getElementById('genPauseBtn').disabled = true;
    document.getElementById('genStopBtn').disabled  = true;
    document.getElementById('genPauseBtn').style.opacity = '.45';
    document.getElementById('genStopBtn').style.opacity  = '.45';
    if (!genStopped && genState.idx >= genQueue.length) {
        genAddLog('\u{1F389} Complete! Generated:' + genState.generated + ' Skipped:' + genState.skipped + ' Failed:' + genState.failed, 'success');
        document.getElementById('genCurrentPage').textContent = '\u2705 Generation complete!';
        document.getElementById('genProgressBar').style.width = '100%';
        document.getElementById('genETA').textContent = '0s';
    }
    // Show Retry Failed button if any tasks failed
    if (genFailedTasks.length > 0) {
        const rb = document.getElementById('genRetryBtn');
        if (rb) {
            rb.textContent = '\u{1F504} Retry ' + genFailedTasks.length + ' Failed';
            rb.style.display = 'inline-flex';
        }
    }
}

function genRetryFailed() {
    if (!genFailedTasks.length) return;
    const tasks = genFailedTasks.slice();
    genShowPopup('\u{1F504} Retry Failed Pages', tasks.length + ' failed pages', tasks);
}

function genTogglePause() {
    genPaused = !genPaused;
    const btn = document.getElementById('genPauseBtn');
    if (genPaused) {
        btn.textContent = '\u25B6 Resume';
        btn.style.background = 'linear-gradient(135deg,#10B981,#059669)';
        genAddLog('\u23F8 Paused', 'warn');
    } else {
        btn.textContent = '\u23F8 Pause';
        btn.style.background = 'linear-gradient(135deg,#F59E0B,#D97706)';
        genAddLog('\u25B6 Resumed', 'info');
    }
}

function genStop() {
    genStopped = true; genRunning = false;
    genControllers.forEach(c => { try { c.abort(); } catch(e) {} });
    genControllers = [];
    genAddLog('\u23F9 Stopped — cancelling in-progress requests...', 'warn');
}

function genSleep(ms) { return new Promise(r => setTimeout(r, ms)); }

// ─── Live card counter updates ────────────────────────────
function genUpdateCards(task) {
    // Keyword card
    const kRadio = document.querySelector('input[name="keyword_id"][value="' + task.keyword_id + '"]');
    if (kRadio) {
        const kLabel = kRadio.closest('.keyword-option');
        const cs = kLabel && kLabel.querySelector('.keyword-count');
        if (cs) {
            const parts = cs.textContent.split('/');
            const newN = (parseInt(parts[0].trim()) || 0) + 1;
            const tot  = parseInt(parts[1].trim()) || 188;
            cs.textContent = newN + ' / ' + tot;
            if (newN >= tot) kLabel.classList.add('complete');
        }
    }
    // Service card
    const kwObj = GCM_KEYWORDS.find(function(k){ return k.id == task.keyword_id; });
    if (kwObj) {
        const sRadio = document.querySelector('input[name="category_name"][value="' + kwObj.category + '"]');
        if (sRadio) {
            const sLabel = sRadio.closest('.service-option-large');
            const spans  = sLabel && sLabel.querySelectorAll('.service-stats span');
            if (spans && spans[1]) {
                const m = spans[1].textContent.match(/(\d+)\s*\/\s*(\d+)/);
                if (m) {
                    const newN = parseInt(m[1]) + 1, tot = parseInt(m[2]);
                    spans[1].textContent = newN + ' / ' + tot + ' Pages';
                    const fill = sLabel.querySelector('.progress-fill');
                    if (fill && tot > 0) fill.style.width = Math.round(newN / tot * 100) + '%';
                }
            }
        }
    }
    // Already Generated stat card
    const agEl = document.getElementById('alreadyGeneratedValue');
    if (agEl) {
        const cur = parseInt(agEl.textContent.replace(/,/g, '')) || 0;
        agEl.textContent = (cur + 1).toLocaleString();
    }
}

// ─── Worker (runs in parallel, with per-task auto-retry) ─────────────────────
async function genWorker(delay) {
    const MAX_RETRIES = 3;
    while (true) {
        if (genStopped) break;
        while (genPaused && !genStopped) await genSleep(500);
        if (genStopped) break;

        const idx = genState.idx++;
        if (idx >= genQueue.length) break;
        const task = genQueue[idx];
        document.getElementById('genCurrentPage').textContent =
            '[' + (idx+1) + '/' + genQueue.length + '] ' + task.keyword + ' in ' + task.area;

        let taskSuccess = false;
        for (let attempt = 0; attempt < MAX_RETRIES; attempt++) {
            if (genStopped) break;

            // Exponential backoff on retry: 5s, 12s
            if (attempt > 0) {
                const wait = attempt === 1 ? 5000 : 12000;
                genAddLog('\u23F3 Retry ' + attempt + '/' + (MAX_RETRIES-1) + ': ' + task.keyword + ' in ' + task.area + ' (' + (wait/1000) + 's wait)', 'info');
                await genSleep(wait);
            }

            const ctrl = new AbortController();
            genControllers.push(ctrl);
            try {
                const res  = await fetch(GCM_API, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body:    JSON.stringify({ keyword_id: task.keyword_id, area_id: task.area_id }),
                    signal:  ctrl.signal
                });
                genControllers = genControllers.filter(c => c !== ctrl);

                // Defensive JSON parsing: the server can reply with plain text (e.g. Access Denied)
                const ctype = (res.headers.get('content-type') || '').toLowerCase();
                let data = null;
                if (ctype.includes('application/json')) {
                    data = await res.json();
                } else {
                    const txt = await res.text();
                    throw new Error('Non-JSON response (' + res.status + '): ' + (txt || '').slice(0, 140));
                }

                if (data.success) {
                    taskSuccess = true;
                    if (data.skipped) {
                        genState.skipped++;
                        genAddLog('\u23ED Skipped: ' + task.keyword + ' in ' + task.area, 'info');
                    } else {
                        genState.generated++;
                        GCM_GENERATED_SET.add(task.keyword_id + '_' + task.area_id);
                        genAddLog('\u2705 ' + task.keyword + ' in ' + task.area + ' (' + (data.word_count||'?') + 'w)', 'success');
                        genUpdateCards(task);
                    }
                    break; // success — exit retry loop
                }

                // Failed response — check if retryable
                const errMsg = (data.error || '').toLowerCase();
                const isRateLimit = errMsg.includes('429') || errMsg.includes('quota') ||
                                    errMsg.includes('rate') || errMsg.includes('overload') ||
                                    errMsg.includes('resource_exhausted');
                if (attempt < MAX_RETRIES - 1) {
                    genAddLog('\u26A0 Attempt ' + (attempt+1) + ' failed' + (isRateLimit ? ' (rate limit)' : '') + ': ' + task.keyword + ' in ' + task.area, 'info');
                    if (isRateLimit) await genSleep(8000); // extra pause on rate limit
                    continue; // retry
                }
                // Final attempt failed
                genAddLog('\u274C Failed (all retries): ' + task.keyword + ' in ' + task.area + ' \u2014 ' + (data.error||'?'), 'error');

            } catch(err) {
                genControllers = genControllers.filter(c => c !== ctrl);
                if (err.name === 'AbortError' || genStopped) { genStopped = true; break; }
                if (attempt < MAX_RETRIES - 1) {
                    genAddLog('\u26A0 Network error attempt ' + (attempt+1) + ': ' + task.keyword + ' in ' + task.area + ' \u2014 ' + err.message, 'info');
                    continue; // retry
                }
                genAddLog('\u274C Network failed (all retries): ' + task.keyword + ' in ' + task.area + ' \u2014 ' + err.message, 'error');
            }
        } // end retry loop

        if (!taskSuccess && !genStopped) {
            genState.failed++;
            genFailedTasks.push(task);
        }
        genUpdateStats();
        if (delay > 0) await genSleep(delay);
    }
}

// ─── Queue builders (only NOT-yet-generated pages) ───────────────────────────
function genQueueByKeyword(keyword_id) {
    const kw = GCM_KEYWORDS.find(k => k.id == keyword_id);
    if (!kw) return [];
    return GCM_AREAS
        .filter(a => !isGenerated(kw.id, a.id))
        .map(a => ({ keyword_id:kw.id, keyword:kw.keyword_name, area_id:a.id, area:a.area_name }));
}
function genQueueByCategory(category) {
    const kws = GCM_KEYWORDS.filter(k => k.category === category);
    const tasks = [];
    for (const kw of kws) for (const a of GCM_AREAS)
        if (!isGenerated(kw.id, a.id))
            tasks.push({ keyword_id:kw.id, keyword:kw.keyword_name, area_id:a.id, area:a.area_name });
    return tasks;
}
function genQueueAll() {
    const tasks = [];
    for (const kw of GCM_KEYWORDS) for (const a of GCM_AREAS)
        if (!isGenerated(kw.id, a.id))
            tasks.push({ keyword_id:kw.id, keyword:kw.keyword_name, area_id:a.id, area:a.area_name });
    return tasks;
}

// ─── Form intercepts ──────────────────────────────────────
document.getElementById('formByKeyword').addEventListener('submit', function(e) {
    e.preventDefault();
    const r = this.querySelector('input[name="keyword_id"]:checked');
    if (!r) { alert('Please select a keyword!'); return; }
    const kw = GCM_KEYWORDS.find(k => k.id == r.value);
    const tasks = genQueueByKeyword(parseInt(r.value));
    if (tasks.length === 0) { alert('\u2705 All ' + GCM_AREAS.length + ' pages for "' + (kw ? kw.keyword_name : '') + '" are already generated!'); return; }
    genShowPopup('\u{1F511} Generate by Keyword', (kw ? kw.keyword_name : '') + ' \u2014 ' + tasks.length + ' remaining of ' + GCM_AREAS.length, tasks);
});

document.getElementById('formByService').addEventListener('submit', function(e) {
    e.preventDefault();
    const r = this.querySelector('input[name="category_name"]:checked');
    if (!r) { alert('Please select a service!'); return; }
    const tasks = genQueueByCategory(r.value);
    const totalForCat = GCM_KEYWORDS.filter(k => k.category === r.value).length * GCM_AREAS.length;
    if (tasks.length === 0) { alert('\u2705 All ' + totalForCat.toLocaleString() + ' pages for "' + r.value + '" are already generated!'); return; }
    genShowPopup('\u{1F9F1} Generate by Service', r.value + ' \u2014 ' + tasks.length + ' remaining of ' + totalForCat.toLocaleString(), tasks);
});

document.getElementById('formGenerateAll').addEventListener('submit', function(e) {
    e.preventDefault();
    const ci = this.querySelector('input[name="confirm_all"]');
    if (!ci || ci.value.trim() !== 'GENERATE ALL PAGES') {
        alert('Please type "GENERATE ALL PAGES" in the confirmation box!');
        return;
    }
    const tasks = genQueueAll();
    const grandTotal = GCM_KEYWORDS.length * GCM_AREAS.length;
    if (tasks.length === 0) { alert('\u2705 All ' + grandTotal.toLocaleString() + ' pages are already generated!'); return; }
    if (!confirm('This will generate ' + tasks.length.toLocaleString() + ' remaining pages (out of ' + grandTotal.toLocaleString() + ' total). Continue?')) return;
    genShowPopup('\u{1F310} Generate ALL Pages', tasks.length.toLocaleString() + ' remaining of ' + grandTotal.toLocaleString(), tasks);
});

// Tab switching removed — replaced with seo-section cards

// Close popup when clicking outside modal
document.getElementById('genOverlay').addEventListener('click', function(e) {
    if (e.target === this) genClose();
});
</script>

<?php include '../includes/footer.php'; ?>
