<?php
/**
 * Single Page Generation – AJAX Endpoint
 * Generates ONE keyword + area page via Gemini AI (server-side)
 * Called by the JS generation engine in generate-pages.php
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
set_time_limit(300); // allow up to 5 min for retries + 4-model fallback

define('GCM_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/gemini-api.php';

// Session auth
if (session_status() === PHP_SESSION_NONE) {
    session_name('GCM_ADMIN_SESSION');
    session_start();
}
if (empty($_SESSION['admin_logged_in'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}
session_write_close(); // Release session lock so parallel requests don't block each other

$input      = json_decode(file_get_contents('php://input'), true) ?? [];
$keyword_id = (int)($input['keyword_id'] ?? 0);
$area_id    = (int)($input['area_id']    ?? 0);
if (!$keyword_id || !$area_id) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Missing keyword_id or area_id']);
    exit;
}

$db = Database::getInstance();

// ── 1. Fetch keyword + area first (slug needed for file-based check) ──
$gen_dir = __DIR__ . '/../../generated-pages/';
$keyword = $db->fetchOne("SELECT * FROM seo_service_keywords WHERE id = ?", [$keyword_id]);
$area    = $db->fetchOne("SELECT * FROM service_areas WHERE id = ?", [$area_id]);

if (!$keyword || !$area) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Keyword or area not found']);
    exit;
}

$kw_name = $keyword['keyword_name'];
$kw_slug = $keyword['keyword_slug'];
$ar_name = $area['area_name'];
$ar_slug = $area['area_slug'];

// ── 2. Robust duplicate check: DB record AND/OR file on disk ──
$expected_slug = $kw_slug . '-in-' . $ar_slug;
$expected_file = $gen_dir . $expected_slug . '.php';

$existing = $db->fetchOne(
    "SELECT id, page_slug FROM generated_pages WHERE keyword_id = ? AND area_id = ?",
    [$keyword_id, $area_id]
);
if ($existing) {
    $existing_file = $gen_dir . ($existing['page_slug'] ?? '') . '.php';
    if (!empty($existing['page_slug']) && file_exists($existing_file)) {
        // DB record + file both exist → skip (already generated)
        ob_clean();
        echo json_encode(['success' => true, 'skipped' => true, 'message' => 'Already exists']);
        exit;
    }
    // DB record exists but file missing — delete stale record and regenerate
    $db->execute("DELETE FROM generated_pages WHERE id = ?", [$existing['id']]);
} elseif (file_exists($expected_file)) {
    // File exists on disk but no DB record — skip without regenerating
    // (avoids double-generation when DB insert previously failed)
    ob_clean();
    echo json_encode(['success' => true, 'skipped' => true, 'message' => 'File already exists']);
    exit;
}

// Get Gemini API key from DB (primary first), fall back to config constant
$apiKeyRow = $db->fetchOne("SELECT api_key FROM gemini_api_keys WHERE is_primary = 1 LIMIT 1");
if (!$apiKeyRow) {
    $apiKeyRow = $db->fetchOne("SELECT api_key FROM gemini_api_keys LIMIT 1");
}
$apiKey = $apiKeyRow['api_key'] ?? (defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '');

if (empty($apiKey) || $apiKey === 'your-gemini-api-key-here') {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Gemini API key not configured. Please set it in API Settings.']);
    exit;
}

try {
    // ── Original prompt (matches generate_ai_content in generate-pages.php) ──
    $prompt = "Write a comprehensive SEO-optimized article about '{$kw_name}' services in {$ar_name}, Chennai, India.

Include the following sections:
1. Introduction (150 words) - Explain the importance of {$kw_name} in {$ar_name}
2. Why Choose {$kw_name} in {$ar_name} (200 words) - Local climate conditions, weather patterns, necessity
3. Our Services (200 words) - Professional installation, quality materials, warranty
4. Benefits (150 words) - Safety, durability, aesthetic appeal
5. Installation Process (150 words) - Steps, timeline, professional team
6. Pricing & Packages (100 words) - Affordable rates, free quotation
7. Local Expertise (150 words) - Why we're best for {$ar_name} residents
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

    // Use GeminiAPI class (has multi-model fallback — more reliable than old curl)
    $gemini  = new GeminiAPI($apiKey);
    $rawText = $gemini->generateContent($prompt, 3000);

    // Clean markdown artifacts (same as original)
    $content = preg_replace('/```html\s*/i', '', $rawText);
    $content = preg_replace('/```\s*$/s', '',  $content);
    $content = preg_replace('/```/',       '',  $content);
    $content = trim($content, '`');
    $content = preg_replace('/^html\s+/i', '', $content);
    $content = trim($content);
    // Remove HTML entities for newlines that AI inserts (shows as visible &#10; text)
    $content = str_replace(['&#10;', '&#13;', '&#9;'], ['', '', '    '], $content);

    $word_ct = str_word_count(strip_tags($content));
    $slug    = $kw_slug . '-in-' . $ar_slug;

    // ── Original meta format (matches generate_ai_content) ──
    $hero_prefixes = require dirname(__DIR__, 2) . '/config/hero-title-prefixes.php';
    $h1_prefix  = $hero_prefixes[array_rand($hero_prefixes)];
    $page_title = ucwords($kw_name) . " in {$ar_name}, Chennai | GCM Netting Solutions";
    $h1_heading = "{$h1_prefix} " . ucwords($kw_name) . " in {$ar_name}, Chennai";
    $meta_desc  = "{$h1_prefix} {$kw_name} installation in {$ar_name}, Chennai. Quality materials, expert installation, affordable prices. Call 9912399224 for free quote.";
    $meta_kw    = "{$kw_name}, {$kw_name} in {$ar_name}, {$kw_name} chennai, safety nets {$ar_name}, GCM safety nets";

    // Resolve service_id from category
    $cat_to_slug = [
        'PIGEON NETS'      => 'pigeon-nets',
        'BIRD NETS'        => 'bird-nets',
        'SAFETY NETS'      => 'balcony-safety-nets',
        'INVISIBLE GRILLS' => 'invisible-grills',
        'SPORTS NETS'      => 'sports-nets',
        'CLOTH HANGERS'    => 'cloth-hangers',
    ];
    $svc_slug  = $cat_to_slug[$keyword['category']] ?? '';
    $svc_row   = $svc_slug ? $db->fetchOne("SELECT id FROM services WHERE service_slug = ?", [$svc_slug]) : null;
    $svc_id    = $svc_row['id'] ?? 0;

    // Save to generated_pages — retry once on MySQL 'server has gone away' (error 2006)
    $db_sql = "INSERT INTO generated_pages
             (service_id, keyword_id, area_id, page_slug, page_title, meta_description,
              meta_keywords, h1_heading, content, word_count, is_published, generated_by, generated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'gemini_ai', NOW())
             ON DUPLICATE KEY UPDATE
               page_slug=VALUES(page_slug), page_title=VALUES(page_title),
               meta_description=VALUES(meta_description), meta_keywords=VALUES(meta_keywords),
               h1_heading=VALUES(h1_heading), content=VALUES(content),
               word_count=VALUES(word_count), generated_at=NOW()";
    $db_params = [$svc_id, $keyword_id, $area_id, $slug,
                  $page_title, $meta_desc, $meta_kw, $h1_heading, $content, $word_ct];
    $pdo = $db->getConnection();
    for ($attempt = 0; $attempt < 2; $attempt++) {
        try {
            $stmt = $pdo->prepare($db_sql);
            $stmt->execute($db_params);
            break;
        } catch (PDOException $e) {
            if ($attempt === 0 && ($e->errorInfo[1] == 2006 || strpos($e->getMessage(), 'gone away') !== false)) {
                // Reconnect and retry
                try {
                    $pdo = new PDO(
                        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
                        DB_USER, DB_PASS,
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
                    );
                } catch (PDOException $ce) { /* fall through to original error */ $attempt = 2; }
                continue;
            }
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'DB: ' . $e->getMessage()]);
            exit;
        }
    }

    // Create physical PHP page file in /generated-pages/
    if (!is_dir($gen_dir)) {
        @mkdir($gen_dir, 0755, true);
    }
    $file_path = $gen_dir . $slug . '.php';
    $bytes = file_put_contents($file_path, gcm_build_page($page_title, $h1_heading, $meta_desc, $meta_kw, $content, $kw_name, $kw_slug, $ar_name, $ar_slug, $keyword['category'] ?? ''));
    if ($bytes === false) {
        // File write failed — roll back DB record so the page can be re-generated later
        try { $db->execute("DELETE FROM generated_pages WHERE keyword_id = ? AND area_id = ?", [$keyword_id, $area_id]); } catch (\Exception $e) {}
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'File save failed. Check /generated-pages/ folder permissions (needs 755 or 777 on Hostinger).']);
        exit;
    }

    // Register with AI Content Security (auto-protect on generate)
    $protection_file = __DIR__ . '/../includes/ai-content-protection.php';
    if (file_exists($protection_file) && !class_exists('AIContentProtection')) {
        require_once $protection_file;
    }
    if (class_exists('AIContentProtection')) {
        try {
            $protection = new AIContentProtection();
            $protection->protect('service_page', $slug, $page_title,
                                 '/generated-pages/' . $slug . '.php',
                                 $_SESSION['admin_id'] ?? 0);
        } catch (Exception $e) { /* non-fatal */ }
    }

    ob_clean();
    echo json_encode([
        'success'    => true,
        'keyword'    => $kw_name,
        'keyword_id' => $keyword_id,
        'area'       => $ar_name,
        'slug'       => $slug,
        'word_count' => $word_ct,
    ]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// ─────────────────────────────────────────────────
// Page template builder
// ─────────────────────────────────────────────────
function gcm_build_page($title, $h1, $metaDesc, $metaKw, $content, $svcName, $svcSlug, $areaName, $areaSlug, $category = '') {
    $t        = addslashes($title);
    $md       = addslashes($metaDesc);
    $mk       = addslashes($metaKw);
    $sn       = addslashes($svcName);
    $h1safe   = htmlspecialchars($h1);
    $anSafe   = htmlspecialchars($areaName);
    $snSafe   = htmlspecialchars($svcName);
    $catEsc   = addslashes($category);

    // Random subtitle descriptor (varies per page for SEO diversity)
    $subtitleWords = ['Trusted','Reliable','Expert','Premium','Professional','Quality','Certified',
                      'Superior','Elite','Dependable','Genuine','Verified','Specialized','Top-Rated',
                      'Affordable','Experienced','Proven','Best','No.1','Branded'];
    $subWord = $subtitleWords[array_rand($subtitleWords)];

    $part1 = <<<PHP
<?php
define('GCM_INIT', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
\$page_title       = '{$t}';
\$meta_description = '{$md}';
\$meta_keywords    = '{$mk}';
\$current_page     = 'services';
// Sidebar data — loaded once at runtime
\$_db = Database::getInstance();
\$_cat_kws = \$_db->fetchAll(
    "SELECT keyword_name, keyword_slug FROM seo_service_keywords WHERE is_active = 1 ORDER BY display_order"
);
\$_all_areas = \$_db->fetchAll(
    "SELECT area_name, area_slug FROM service_areas WHERE is_active = 1 ORDER BY area_name"
);
include dirname(__DIR__) . '/includes/modern-header.php';
?>
<style>
/* Layout & fonts handled by gcm-service-pages.css (external).
   Only the entry animation is defined inline. */
*{box-sizing:border-box;}body{overflow-x:hidden;margin:0;padding:0;}
@keyframes gcm-fi{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.gcm-content{animation:gcm-fi .6s ease-out;}
</style>

<!-- Hero — matches home page slider style -->
<section style="position:relative;width:100%;min-height:480px;background:#1E293B;display:flex;align-items:center;overflow:hidden;">
  <!-- Background image (service image, falls back to dark base if missing) -->
  <div style="position:absolute;inset:0;background-image:url('<?php echo SITE_URL; ?>/assets/img/services/{$svcSlug}.jpg');background-size:cover;background-position:center;animation:gcm-kb 20s ease infinite;"></div>
  <!-- Dark overlay identical to homepage slider -->
  <div style="position:absolute;inset:0;background:linear-gradient(135deg,rgba(71,85,105,.55) 0%,rgba(30,41,59,.80) 100%);"></div>
  <!-- Content -->
  <div style="max-width:1200px;margin:0 auto;padding:60px 20px;position:relative;z-index:3;width:100%;animation:gcm-sup .8s ease;">
    <!-- Breadcrumb -->
    <div style="font-size:13px;color:rgba(255,255,255,.7);margin-bottom:20px;">
      <a href="<?php echo SITE_URL; ?>" style="color:rgba(255,255,255,.8);text-decoration:none;">Home</a>
      <span style="margin:0 8px;opacity:.5;">&rsaquo;</span>
      <a href="<?php echo SITE_URL; ?>/services.php" style="color:rgba(255,255,255,.8);text-decoration:none;">Services</a>
      <span style="margin:0 8px;opacity:.5;">&rsaquo;</span>
      <span style="color:#fff;">{$h1safe}</span>
    </div>
    <!-- Title -->
    <h1 style="font-size:46px;font-weight:800;color:#fff;margin:0 0 16px;line-height:1.2;text-shadow:0 4px 20px rgba(0,0,0,.35);max-width:780px;font-family:'Times New Roman',Times,serif;">{$h1safe}</h1>
    <!-- Subtitle -->
    <p style="font-size:20px;color:rgba(255,255,255,.88);margin:0 0 32px;max-width:560px;">{$subWord} {$snSafe} services for {$anSafe} homes &amp; businesses</p>
    <!-- Buttons -->
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:36px;">
      <a href="tel:+919912399224" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#10B981,#059669);color:white;padding:14px 28px;border-radius:12px;text-decoration:none;font-weight:700;font-size:16px;box-shadow:0 8px 24px rgba(16,185,129,.4);">&#128222; Call: 9912399224</a>
      <a href="#contact-form" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#F59E0B,#D97706);color:white;padding:14px 28px;border-radius:12px;text-decoration:none;font-weight:700;font-size:16px;box-shadow:0 8px 24px rgba(245,158,11,.4);">&#x1F4CB; Get Free Quote</a>
    </div>
    <!-- Trust badges row -->
    <div style="display:flex;gap:24px;flex-wrap:wrap;">
      <span style="color:rgba(255,255,255,.9);font-size:14px;font-weight:600;display:flex;align-items:center;gap:6px;"><span style="color:#10B981;font-size:17px;">&#10003;</span> Quality Materials</span>
      <span style="color:rgba(255,255,255,.9);font-size:14px;font-weight:600;display:flex;align-items:center;gap:6px;"><span style="color:#10B981;font-size:17px;">&#10003;</span> Expert Installation</span>
      <span style="color:rgba(255,255,255,.9);font-size:14px;font-weight:600;display:flex;align-items:center;gap:6px;"><span style="color:#10B981;font-size:17px;">&#10003;</span> 5 Year Warranty</span>
      <span style="color:rgba(255,255,255,.9);font-size:14px;font-weight:600;display:flex;align-items:center;gap:6px;"><span style="color:#10B981;font-size:17px;">&#10003;</span> Free Inspection</span>
    </div>
  </div>
</section>
<style>
@keyframes gcm-kb{0%,100%{transform:scale(1)}50%{transform:scale(1.06)}}
@keyframes gcm-sup{from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:translateY(0)}}
</style>

<div class="gcm-wrap">
  <div class="gcm-grid">

    <!-- Main Content -->
    <div class="gcm-main">
      <div class="gcm-card">
        <div class="gcm-content">
PHP;

    $part2 = <<<PHP2

        </div>
        <!-- CTA bar -->
        <div style="background:linear-gradient(135deg,#3B82F6,#8B5CF6);padding:36px;border-radius:16px;text-align:center;margin-top:36px;">
          <h3 style="color:white;font-size:22px;margin:0 0 10px;">&#x1F680; Ready to Get Started?</h3>
          <p style="color:rgba(255,255,255,.9);margin:0 0 18px;">Contact us today for a free consultation and site visit</p>
          <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="tel:+919912399224" style="background:white;color:#3B82F6;padding:11px 22px;border-radius:8px;text-decoration:none;font-weight:700;">&#128222; Call Now</a>
            <a href="https://wa.me/919912399224" style="background:#25D366;color:white;padding:11px 22px;border-radius:8px;text-decoration:none;font-weight:700;" target="_blank">WhatsApp</a>
            <a href="<?php echo SITE_URL; ?>/contact.php" style="background:rgba(255,255,255,.2);color:white;border:2px solid white;padding:11px 22px;border-radius:8px;text-decoration:none;font-weight:700;">Contact Form</a>
          </div>
        </div>
      </div>
    </div><!-- /main -->

    <!-- Sidebar -->
    <div class="gcm-sidebar">

      <!-- Contact form -->
      <div class="gcm-card" id="contact-form">
        <h3 class="gcm-sb-title">&#128221; Get Free Quote</h3>
        <div id="gcm-form-msg" style="display:none;padding:10px 14px;border-radius:8px;margin-bottom:12px;font-size:14px;font-weight:600;"></div>
        <form id="gcm-inquiry-form" onsubmit="gcmSubmitForm(event)">
          <input type="hidden" name="form_type" value="service_page">
          <input type="hidden" name="service"   value="{$sn}">
          <input type="hidden" name="area"      value="{$areaSlug}">
          <input type="text"  name="name"    placeholder="Your Name *"    required minlength="3" style="width:100%;padding:11px 12px;border:1.5px solid #e2e8f0;border-radius:8px;margin-bottom:10px;font-size:14px;outline:none;transition:border .2s;" onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'">
          <input type="tel"   name="phone"   placeholder="Phone Number *" required pattern="[6-9][0-9]{9}" title="10-digit Indian mobile number" style="width:100%;padding:11px 12px;border:1.5px solid #e2e8f0;border-radius:8px;margin-bottom:10px;font-size:14px;outline:none;transition:border .2s;" onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'">
          <input type="email" name="email"   placeholder="Email Address *" required style="width:100%;padding:11px 12px;border:1.5px solid #e2e8f0;border-radius:8px;margin-bottom:10px;font-size:14px;outline:none;transition:border .2s;" onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'">
          <textarea name="message" placeholder="Your requirements (min 10 chars) *" rows="3" required minlength="10" style="width:100%;padding:11px 12px;border:1.5px solid #e2e8f0;border-radius:8px;margin-bottom:10px;font-size:14px;resize:vertical;outline:none;transition:border .2s;" onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
          <button type="submit" id="gcm-form-btn" style="width:100%;background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:none;padding:13px;border-radius:8px;font-weight:700;cursor:pointer;font-size:15px;transition:opacity .2s;">Send Inquiry &#x27A4;</button>
        </form>
        <script>
        function gcmSubmitForm(e){
          e.preventDefault();
          var btn=document.getElementById('gcm-form-btn');
          var msg=document.getElementById('gcm-form-msg');
          btn.disabled=true; btn.textContent='Sending...';
          var fd=new FormData(document.getElementById('gcm-inquiry-form'));
          fetch('<?php echo SITE_URL; ?>/api/contact-handler.php',{method:'POST',body:fd})
            .then(function(r){return r.json();})
            .then(function(d){
              msg.style.display='block';
              if(d.success){
                msg.style.background='#d1fae5'; msg.style.color='#065f46';
                msg.innerHTML='&#10003; '+d.message;
                document.getElementById('gcm-inquiry-form').reset();
                btn.textContent='Sent!';
              } else {
                msg.style.background='#fee2e2'; msg.style.color='#991b1b';
                msg.innerHTML='&#9888; '+(d.message||'Something went wrong. Please try again.');
                btn.disabled=false; btn.textContent='Send Inquiry \u27a4';
              }
            })
            .catch(function(){
              msg.style.display='block'; msg.style.background='#fee2e2'; msg.style.color='#991b1b';
              msg.innerHTML='&#9888; Network error. Please call us directly.';
              btn.disabled=false; btn.textContent='Send Inquiry \u27a4';
            });
        }
        </script>
      </div>

      <!-- Service Highlights -->
      <div class="gcm-card">
        <h3 class="gcm-sb-title">Service Highlights</h3>
        <ul class="gcm-highlights" style="padding:0;margin:0;">
          <li>&#10003; Free Home Inspection</li>
          <li>&#10003; Same Day Installation</li>
          <li>&#10003; 5 Year Warranty</li>
          <li>&#10003; Premium Quality Materials</li>
          <li>&#10003; Expert Certified Technicians</li>
          <li>&#10003; Best Price Guarantee</li>
        </ul>
      </div>

      <!-- Why Choose Us -->
      <div class="gcm-card">
        <h3 class="gcm-sb-title">Why Choose GCM?</h3>
        <ul class="gcm-highlights" style="padding:0;margin:0 0 14px;">
          <li>&#11088; 15+ Years Experience</li>
          <li>&#11088; 10,000+ Happy Customers</li>
          <li>&#11088; Chennai's #1 Choice</li>
          <li>&#11088; ISO Certified Company</li>
          <li>&#11088; 24/7 Customer Support</li>
        </ul>
        <a href="tel:+919912399224" style="display:block;background:linear-gradient(135deg,#10B981,#059669);color:white;padding:12px;border-radius:8px;text-decoration:none;font-weight:700;font-size:15px;text-align:center;">&#128222; 9912399224</a>
      </div>

      <!-- All Keywords for this area (64 services with current highlighted) -->
      <?php if (!empty(\$_cat_kws)): ?>
      <div class="gcm-card">
        <h3 class="gcm-sb-title">All Services in {$anSafe}</h3>
        <div class="gcm-sb-links">
          <?php foreach(\$_cat_kws as \$_kw): ?>
            <?php \$_isActive = (\$_kw['keyword_slug'] === '{$svcSlug}'); ?>
            <a href="<?php echo SITE_URL . '/' . \$_kw['keyword_slug'] . '-in-{$areaSlug}'; ?>"
               class="<?php echo \$_isActive ? 'active' : ''; ?>">
              <?php if(\$_isActive): ?>&#128205; <?php endif; ?><?php echo htmlspecialchars(\$_kw['keyword_name']); ?> in {$anSafe}
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- All Areas for this keyword (188 areas with current highlighted) -->
      <?php if (!empty(\$_all_areas)): ?>
      <div class="gcm-card">
        <h3 class="gcm-sb-title">{$snSafe} in All Areas</h3>
        <div class="gcm-sb-links">
          <?php foreach(\$_all_areas as \$_area): ?>
            <?php \$_isActive = (\$_area['area_slug'] === '{$areaSlug}'); ?>
            <a href="<?php echo SITE_URL . '/{$svcSlug}-in-' . \$_area['area_slug']; ?>"
               class="<?php echo \$_isActive ? 'active' : ''; ?>">
              <?php if(\$_isActive): ?>&#128205; <?php endif; ?>{$snSafe} in <?php echo htmlspecialchars(\$_area['area_name']); ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /sidebar -->
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/modern-footer.php'; ?>
PHP2;

    return $part1 . $content . $part2;
}
