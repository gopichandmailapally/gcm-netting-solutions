<?php
/**
 * Content Protection & Export
 * Shows deploy-safety status + lets admin download a backup of all AI-generated pages
 */
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php'); exit;
}

$db = Database::getInstance();
$page_title = 'Content Protection & Export';

// ── All content counts ─────────────────────────────────────────────────────
$root_dir = dirname(__DIR__, 2);
$gen_dir  = $root_dir . '/generated-pages/';
$data_dir = $root_dir . '/data/';

// Service pages
$db_count   = 0;
$file_count = 0;
try {
    $row = $db->fetchOne("SELECT COUNT(*) as cnt FROM generated_pages WHERE keyword_id > 0 AND area_id > 0");
    $db_count = (int)($row['cnt'] ?? 0);
} catch (\Exception $e) {}
if (is_dir($gen_dir)) {
    $fs = glob($gen_dir . '*.php') ?: [];
    foreach ($fs as $f) { if (basename($f) !== 'index.php') $file_count++; }
}

// Blogs, Reviews, FAQs — read from MySQL, fall back to JSON file count if MySQL is lower
$blog_count   = 0;
$review_count = 0;
$faq_count    = 0;
try { $blog_count   = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM ai_blogs WHERE is_published=1")['c'] ?? 0); } catch(\Throwable $e) {}
try { $review_count = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM ai_reviews WHERE status='approved'")['c'] ?? 0); } catch(\Throwable $e) {}
try { $faq_count    = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM faqs WHERE is_active=1")['c'] ?? 0); } catch(\Throwable $e) {}

// JSON file fallback: use whichever count is HIGHER (covers data stored only as JSON)
$_skip = ['index.json', 'stats.json'];
$_fc_blogs = count(array_filter(glob($data_dir . 'blogs/*.json')    ?: [], fn($f) => !in_array(basename($f), $_skip)));
$_fc_revs  = count(array_filter(glob($data_dir . 'reviews/*.json')  ?: [], fn($f) => !in_array(basename($f), $_skip)));
$_fc_faqs  = count(array_filter(glob($data_dir . 'faqs/*.json')     ?: [], fn($f) => !in_array(basename($f), $_skip)));
if ($_fc_blogs > $blog_count)   $blog_count   = $_fc_blogs;
if ($_fc_revs  > $review_count) $review_count = $_fc_revs;
if ($_fc_faqs  > $faq_count)    $faq_count    = $_fc_faqs;

// Pillar pages — match root PHP files against keyword slugs in DB
// (64 service keywords = 64 pillar pages; exclusion-list approach overcounts)
$pillar_count = 0;
try {
    $kw_rows = $db->fetchAll("SELECT keyword_slug FROM seo_service_keywords WHERE is_active = 1");
    $keyword_slugs = array_column($kw_rows, 'keyword_slug');
    $all_root = glob($root_dir . '/*.php') ?: [];
    $pillar_count = count(array_filter($all_root, fn($f) =>
        in_array(basename($f, '.php'), $keyword_slugs)
    ));
} catch (\Exception $e) {
    // Fallback: exclusion-list method (may overcount on server)
    $known_root = ['index.php','about.php','blog.php','blogs.php','contact.php','estimation.php',
                   'faqs.php','gallery.php','privacy-policy.php','reviews.php','terms-conditions.php',
                   'videos.php','thank-you.php','save-generated-page.php','generate-pages-direct.php',
                   'services.php','check-page-exists.php'];
    $all_root = glob($root_dir . '/*.php') ?: [];
    $pillar_count = count(array_filter($all_root, fn($f) =>
        !in_array(basename($f), $known_root)
        && !str_starts_with(basename($f), 'test-')
        && !str_starts_with(basename($f), 'CHECK')
        && !str_starts_with(basename($f), 'EMERGENCY')
        && !str_contains(basename($f), 'generate')));
}

// For totals, use max(DB, filesystem) for service pages so the badge is accurate
$_svc_count = max($db_count, $file_count);
$total_ai = $_svc_count + $blog_count + $review_count + $faq_count + $pillar_count;

// Permissions
$perm_gen     = is_writable($gen_dir);
$perm_data    = is_writable($data_dir);
$perm_uploads = is_writable($root_dir . '/uploads/');

include '../includes/header.php';
?>
<style>
.cex { padding: 28px; }
.cex-card { background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,.08); margin-bottom:24px; overflow:hidden; }
.cex-card-header { padding:18px 24px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:10px; }
.cex-card-header h2 { margin:0; font-size:19px; font-weight:700; color:#1e293b; }
.cex-card-body { padding:24px; }

/* Safety grid */
.cex-safety-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:16px; }
.cex-safety-item { border-radius:12px; padding:18px; display:flex; align-items:flex-start; gap:14px; }
.cex-safety-item.safe  { background:#f0fdf4; border:1px solid #86efac; }
.cex-safety-item.warn  { background:#fefce8; border:1px solid #fde047; }
.cex-safety-item.info  { background:#eff6ff; border:1px solid #bfdbfe; }
.cex-safety-icon { font-size:28px; flex-shrink:0; margin-top:2px; }
.cex-safety-title { font-size:14px; font-weight:700; color:#1e293b; margin:0 0 4px; }
.cex-safety-desc  { font-size:12px; color:#64748b; margin:0; line-height:1.5; }

/* Stat boxes */
.cex-stats { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:16px; margin-bottom:24px; }
.cex-stat  { background:#f8fafc; border-radius:12px; padding:20px; text-align:center; }
.cex-stat .num { font-size:36px; font-weight:800; color:#6366f1; }
.cex-stat .lbl { font-size:13px; color:#64748b; margin-top:4px; }

/* Download button */
.cex-btn { display:inline-flex; align-items:center; gap:8px; padding:12px 24px; border-radius:10px;
           font-size:14px; font-weight:700; cursor:pointer; text-decoration:none; transition:all .25s; border:none; }
.cex-btn-primary  { background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; }
.cex-btn-primary:hover { opacity:.88; transform:translateY(-1px); }
.cex-btn-success  { background:#10b981; color:#fff; }
.cex-btn-success:hover { background:#059669; }
.cex-btn-info     { background:#3b82f6; color:#fff; }
.cex-btn-info:hover { background:#2563eb; }

/* Steps */
.cex-steps { counter-reset:step; }
.cex-step  { display:flex; gap:16px; margin-bottom:18px; align-items:flex-start; }
.cex-step-num { background:#6366f1; color:#fff; border-radius:50%; width:30px; height:30px;
                display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px; flex-shrink:0; }
.cex-step-body h4 { margin:0 0 4px; font-size:14px; color:#1e293b; font-weight:700; }
.cex-step-body p  { margin:0; font-size:13px; color:#64748b; line-height:1.5; }
.cex-step-body code { background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:12px; color:#6366f1; }
</style>

<div class="cex">

    <!-- Page Header -->
    <div style="margin-bottom:24px;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:14px;">
        <div>
            <h1 style="margin:0;font-size:28px;font-weight:800;color:#1e293b;">
                <i class="fas fa-shield-alt" style="color:#6366f1;"></i> Content Protection &amp; Backup
            </h1>
            <p style="margin:6px 0 0;color:#64748b;">All AI-generated content is protected. Download a full backup anytime.</p>
        </div>
        <a href="../api/export-generated-pages.php?type=full" class="cex-btn cex-btn-primary" style="font-size:15px;padding:14px 28px;">
            <i class="fas fa-file-archive"></i> Download Full Backup (ZIP)
        </a>
    </div>

    <!-- SERVER BACKUP (outside public_html) -->
    <div class="cex-card" style="border:2px solid #0ea5e9;">
        <div class="cex-card-header" style="background:linear-gradient(135deg,#0ea5e9,#2563eb);">
            <i class="fas fa-hdd" style="color:#fff;font-size:20px;"></i>
            <h2 style="color:#fff;">Server Backup (Outside public_html)</h2>
            <span style="margin-left:auto;background:rgba(255,255,255,.2);color:#fff;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;">
                Anti-Deletion Protection
            </span>
        </div>
        <div class="cex-card-body">
            <p style="color:#475569;font-size:14px;margin:0 0 14px;">
                This creates a ZIP backup of <strong>generated-pages</strong>, <strong>data (blogs/faqs/reviews)</strong> and <strong>uploads</strong>
                into a folder <strong>outside</strong> <code>public_html</code>. Even if you delete <code>public_html</code> by mistake, you can restore the exact same content without AI.
            </p>
            <div id="server-backup-status" style="display:none;margin-bottom:14px;padding:14px 18px;border-radius:10px;font-size:13px;"></div>
            <div style="display:flex;gap:14px;flex-wrap:wrap;">
                <button onclick="serverBackupStatus()" class="cex-btn" style="background:#0ea5e9;color:#fff;">
                    <i class="fas fa-info-circle"></i> Check Backup Status
                </button>
                <button onclick="serverBackupNow()" class="cex-btn" style="background:#16a34a;color:#fff;">
                    <i class="fas fa-save"></i> Backup Now
                </button>
                <button onclick="serverRestoreLatest()" class="cex-btn" style="background:#f59e0b;color:#fff;">
                    <i class="fas fa-undo"></i> Restore Latest Backup
                </button>
            </div>
            <div style="margin-top:10px;font-size:12px;color:#64748b;">
                Tip: Backups are also triggered automatically by the site (no cron) at a random time each day.
            </div>
        </div>
    </div>

    <!-- CRITICAL WARNING -->
    <div style="background:#fef2f2;border:2px solid #ef4444;border-radius:14px;padding:20px 24px;margin-bottom:24px;">
        <div style="display:flex;align-items:flex-start;gap:14px;">
            <i class="fas fa-exclamation-triangle" style="color:#ef4444;font-size:28px;margin-top:2px;flex-shrink:0;"></i>
            <div>
                <strong style="font-size:16px;color:#991b1b;display:block;margin-bottom:8px;">
                    ⛔ CRITICAL: Why your content disappears when you redeploy
                </strong>
                <p style="margin:0 0 10px;color:#7f1d1d;font-size:14px;line-height:1.6;">
                    If you <strong>DELETE all server files</strong> in Hostinger File Manager before uploading your zip — you lose everything:
                    all AI blogs, reviews, FAQs, service pages, pillar pages.
                </p>
                <div style="background:#fff;border-radius:8px;padding:14px 18px;display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div style="color:#dc2626;font-size:13px;">
                        <strong>❌ WRONG way to deploy:</strong><br>
                        1. Open Hostinger File Manager<br>
                        2. Select all → <span style="background:#fecaca;padding:1px 6px;border-radius:4px;">Delete</span><br>
                        3. Upload zip → Extract<br>
                        <em style="color:#ef4444;">→ ALL content GONE</em>
                    </div>
                    <div style="color:#15803d;font-size:13px;">
                        <strong>✅ CORRECT way to deploy:</strong><br>
                        1. Open Hostinger File Manager<br>
                        2. <span style="background:#dcfce7;padding:1px 6px;border-radius:4px;">Upload zip ONLY (no deleting)</span><br>
                        3. Right-click zip → Extract<br>
                        <em style="color:#16a34a;">→ Content stays safe</em>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Inventory -->
    <div class="cex-card">
        <div class="cex-card-header">
            <i class="fas fa-boxes" style="color:#6366f1;font-size:20px;"></i>
            <h2>Your AI Content Inventory</h2>
            <span style="margin-left:auto;background:#ede9fe;color:#5b21b6;padding:5px 16px;border-radius:20px;font-size:13px;font-weight:700;">
                <?php echo number_format($total_ai); ?> Total AI Items
            </span>
        </div>
        <div class="cex-card-body">
            <div class="cex-stats">
                <div class="cex-stat">
                    <div class="num" style="color:#6366f1;"><?php echo number_format($_svc_count); ?></div>
                    <div class="lbl"><i class="fas fa-cogs" style="color:#6366f1;"></i> Service Pages</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:4px;"><?php echo number_format($file_count); ?> PHP files &bull; <?php echo number_format($db_count); ?> in DB</div>
                </div>
                <div class="cex-stat">
                    <div class="num" style="color:#10b981;"><?php echo number_format($blog_count); ?></div>
                    <div class="lbl"><i class="fas fa-blog" style="color:#10b981;"></i> Blog Posts</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:4px;">MySQL + JSON files</div>
                </div>
                <div class="cex-stat">
                    <div class="num" style="color:#f59e0b;"><?php echo number_format($review_count); ?></div>
                    <div class="lbl"><i class="fas fa-star" style="color:#f59e0b;"></i> Reviews</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:4px;">MySQL + JSON files</div>
                </div>
                <div class="cex-stat">
                    <div class="num" style="color:#8b5cf6;"><?php echo number_format($faq_count); ?></div>
                    <div class="lbl"><i class="fas fa-question-circle" style="color:#8b5cf6;"></i> FAQs</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:4px;">MySQL + JSON files</div>
                </div>
                <div class="cex-stat">
                    <div class="num" style="color:#06b6d4;"><?php echo number_format($pillar_count); ?></div>
                    <div class="lbl"><i class="fas fa-file-code" style="color:#06b6d4;"></i> Pillar Pages</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:4px;">PHP files in site root</div>
                </div>
            </div>

            <!-- Storage location explanation -->
            <div style="background:#f8fafc;border-radius:12px;padding:18px;margin-top:4px;">
                <strong style="font-size:13px;color:#1e293b;display:block;margin-bottom:12px;">
                    <i class="fas fa-info-circle" style="color:#6366f1;"></i> Where is each content type stored?
                </strong>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:10px;font-size:13px;">
                    <div style="padding:10px 14px;background:#fff;border-radius:8px;border-left:3px solid #6366f1;">
                        <strong>Service Pages</strong><br>
                        <span style="color:#64748b;">MySQL DB + <code style="font-size:11px;">/generated-pages/*.php</code></span>
                    </div>
                    <div style="padding:10px 14px;background:#fff;border-radius:8px;border-left:3px solid #10b981;">
                        <strong>Blogs</strong><br>
                        <span style="color:#64748b;">JSON files in <code style="font-size:11px;">/data/blogs/</code></span>
                    </div>
                    <div style="padding:10px 14px;background:#fff;border-radius:8px;border-left:3px solid #f59e0b;">
                        <strong>Reviews</strong><br>
                        <span style="color:#64748b;">JSON files in <code style="font-size:11px;">/data/reviews/</code></span>
                    </div>
                    <div style="padding:10px 14px;background:#fff;border-radius:8px;border-left:3px solid #8b5cf6;">
                        <strong>FAQs</strong><br>
                        <span style="color:#64748b;">JSON files in <code style="font-size:11px;">/data/faqs/</code></span>
                    </div>
                    <div style="padding:10px 14px;background:#fff;border-radius:8px;border-left:3px solid #06b6d4;">
                        <strong>Pillar Pages</strong><br>
                        <span style="color:#64748b;">PHP files in <code style="font-size:11px;">/</code> (site root)</span>
                    </div>
                    <div style="padding:10px 14px;background:#fff;border-radius:8px;border-left:3px solid #ef4444;">
                        <strong>MySQL Database</strong><br>
                        <span style="color:#64748b;">Separate DB server — <strong>NEVER affected by file uploads</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RESTORE ALL CONTENT -->
    <div class="cex-card" style="border:2px solid #6366f1;">
        <div class="cex-card-header" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
            <i class="fas fa-database" style="color:#fff;font-size:20px;"></i>
            <h2 style="color:#fff;">Restore All Content from Database</h2>
            <span style="margin-left:auto;background:rgba(255,255,255,.2);color:#fff;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;">
                MySQL &rarr; Files
            </span>
        </div>
        <div class="cex-card-body">
            <p style="color:#475569;font-size:14px;margin:0 0 14px;">
                <strong>After deleting all files and re-deploying</strong>, click this button to recreate all content files from the MySQL database.
                MySQL is <strong>never affected by file deletions</strong> — all your content stays safe there.
            </p>
            <div id="restore-status" style="display:none;margin-bottom:14px;padding:14px 18px;border-radius:10px;font-size:13px;"></div>
            <div style="display:flex;gap:14px;flex-wrap:wrap;">
                <button onclick="restoreContent('sync')" class="cex-btn cex-btn-primary" style="font-size:15px;background:linear-gradient(135deg,#dc2626,#b91c1c);">
                    <i class="fas fa-file-import"></i> Sync PHP Files &rarr; DB
                </button>
                <button onclick="restoreContent('all')" class="cex-btn cex-btn-primary" style="font-size:15px;">
                    <i class="fas fa-sync-alt"></i> Restore ALL Content
                </button>
                <button onclick="restoreContent('service_pages')" class="cex-btn" style="background:#6366f1;color:#fff;">
                    <i class="fas fa-cogs"></i> Service Pages Only
                </button>
                <button onclick="restoreContent('blogs')" class="cex-btn" style="background:#10b981;color:#fff;">
                    <i class="fas fa-blog"></i> Blogs Only
                </button>
                <button onclick="restoreContent('reviews')" class="cex-btn" style="background:#f59e0b;color:#fff;">
                    <i class="fas fa-star"></i> Reviews Only
                </button>
                <button onclick="restoreContent('faqs')" class="cex-btn" style="background:#8b5cf6;color:#fff;">
                    <i class="fas fa-question-circle"></i> FAQs Only
                </button>
            </div>
        </div>
    </div>

    <!-- Download Backup -->
    <div class="cex-card">
        <div class="cex-card-header">
            <i class="fas fa-download" style="color:#6366f1;font-size:20px;"></i>
            <h2>Download Backup</h2>
        </div>
        <div class="cex-card-body">
            <p style="color:#475569;font-size:14px;margin:0 0 18px;">
                <strong>Download a full backup before every deployment.</strong>
                The Full Backup ZIP includes: service page PHP files, all blog JSONs, all review JSONs, all FAQ JSONs, DB records + a restore guide.
            </p>
            <div style="display:flex;gap:14px;flex-wrap:wrap;">
                <a href="../api/export-generated-pages.php?type=full" class="cex-btn cex-btn-primary">
                    <i class="fas fa-file-archive"></i> Full Backup (ALL content)
                </a>
                <a href="../api/export-generated-pages.php?type=files" class="cex-btn cex-btn-info">
                    <i class="fas fa-cogs"></i> Service Pages Only (ZIP)
                </a>
                <a href="../api/export-generated-pages.php?type=db" class="cex-btn cex-btn-success">
                    <i class="fas fa-database"></i> DB Records (JSON)
                </a>
            </div>
        </div>
    </div>

    <!-- Safe Deploy Steps -->
    <div class="cex-card">
        <div class="cex-card-header">
            <i class="fas fa-rocket" style="color:#10b981;font-size:20px;"></i>
            <h2>Safe Deployment — Step by Step</h2>
        </div>
        <div class="cex-card-body">
            <div class="cex-steps">
                <div class="cex-step">
                    <div class="cex-step-num" style="background:#ef4444;">!</div>
                    <div class="cex-step-body">
                        <h4 style="color:#ef4444;">Download a Full Backup FIRST</h4>
                        <p>Click <strong>"Download Full Backup"</strong> above. This protects you if anything goes wrong.</p>
                    </div>
                </div>
                <div class="cex-step">
                    <div class="cex-step-num">1</div>
                    <div class="cex-step-body">
                        <h4>Open Hostinger File Manager</h4>
                        <p>Go to hPanel → Websites → your site → File Manager → <code>public_html</code></p>
                    </div>
                </div>
                <div class="cex-step">
                    <div class="cex-step-num">2</div>
                    <div class="cex-step-body">
                        <h4 style="color:#dc2626;">⛔ DO NOT "Select All → Delete"</h4>
                        <p>This is the mistake that wipes all blogs, reviews, FAQs and service pages. Skip this step entirely.</p>
                    </div>
                </div>
                <div class="cex-step">
                    <div class="cex-step-num">3</div>
                    <div class="cex-step-body">
                        <h4>Upload <code>gcmsafetynets-deploy.zip</code></h4>
                        <p>Click Upload → select the zip file → wait for it to finish uploading.</p>
                    </div>
                </div>
                <div class="cex-step">
                    <div class="cex-step-num">4</div>
                    <div class="cex-step-body">
                        <h4>Right-click the zip → Extract</h4>
                        <p>Hostinger will extract files to <code>public_html/gcmsafetynets.in/</code>. Files NOT in the zip (generated content) are <strong>left untouched</strong>.</p>
                    </div>
                </div>
                <div class="cex-step">
                    <div class="cex-step-num">5</div>
                    <div class="cex-step-body">
                        <h4>Verify this page still shows the same counts</h4>
                        <p>Come back here. If you see the same numbers as before — you're done. Content is safe.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Folder Permissions -->
    <div class="cex-card">
        <div class="cex-card-header">
            <i class="fas fa-folder-open" style="color:#f59e0b;font-size:20px;"></i>
            <h2>Folder Permission Status</h2>
        </div>
        <div class="cex-card-body">
            <div class="cex-safety-grid">
                <?php
                $folders = [
                    'generated-pages/' => [$perm_gen,     'Service page PHP files are written here'],
                    'data/'            => [$perm_data,    'Blogs, reviews, FAQs are saved here'],
                    'uploads/'         => [$perm_uploads, 'Logo and admin-uploaded images'],
                ];
                foreach ($folders as $name => [$ok, $desc]): ?>
                <div class="cex-safety-item <?php echo $ok ? 'safe' : 'warn'; ?>">
                    <div class="cex-safety-icon"><?php echo $ok ? '🟢' : '🟡'; ?></div>
                    <div>
                        <p class="cex-safety-title"><code><?php echo $name; ?></code></p>
                        <p class="cex-safety-desc">
                            <?php echo $ok ? '✓ Writable — ' . $desc : '✗ NOT writable — Right-click in File Manager → Permissions → set 755'; ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>

<script>
function _serverBox() {
    return document.getElementById('server-backup-status');
}

function serverBackupStatus() {
    const box = _serverBox();
    box.style.display = 'block';
    box.style.background = '#eff6ff';
    box.style.border = '1px solid #bfdbfe';
    box.style.color = '#1e40af';
    box.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking server backup status...';

    fetch('../api/restore-content.php?type=server_status')
        .then(r => r.json())
        .then(d => {
            if (!d.success) throw new Error(d.error || 'Unknown error');
            const fname = d.latest_backup ? d.latest_backup : 'None found';
            const when  = d.latest_mtime ? d.latest_mtime : '-';
            const size  = d.latest_size ? Math.round(d.latest_size / 1024 / 1024 * 100) / 100 + ' MB' : '-';
            box.style.background = '#f0fdf4';
            box.style.border = '1px solid #86efac';
            box.style.color = '#14532d';
            box.innerHTML =
                '<i class="fas fa-check-circle" style="color:#10b981;"></i> <strong>Server backup status</strong><br>' +
                '&bull; Folder: <code>' + (d.backup_dir || '-') + '</code><br>' +
                '&bull; Latest: <strong>' + fname + '</strong><br>' +
                '&bull; Modified: ' + when + '<br>' +
                '&bull; Size: ' + size;
        })
        .catch(err => {
            box.style.background = '#fef2f2';
            box.style.border = '1px solid #fecaca';
            box.style.color = '#991b1b';
            box.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error: ' + err.message;
        });
}

function serverBackupNow() {
    if (!confirm('Create a server backup now?\n\nThis will create a ZIP outside public_html.')) return;
    const box = _serverBox();
    box.style.display = 'block';
    box.style.background = '#eff6ff';
    box.style.border = '1px solid #bfdbfe';
    box.style.color = '#1e40af';
    box.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating server backup...';

    fetch('../api/restore-content.php?type=server_backup')
        .then(r => r.json())
        .then(d => {
            if (!d.success) throw new Error(d.error || 'Unknown error');
            const size = d.size ? Math.round(d.size / 1024 / 1024 * 100) / 100 + ' MB' : '-';
            box.style.background = '#f0fdf4';
            box.style.border = '1px solid #86efac';
            box.style.color = '#14532d';
            box.innerHTML =
                '<i class="fas fa-check-circle" style="color:#10b981;"></i> <strong>Backup created!</strong><br>' +
                '&bull; Folder: <code>' + (d.backup_dir || '-') + '</code><br>' +
                '&bull; File: <strong>' + (d.backup_file || '-') + '</strong><br>' +
                '&bull; Size: ' + size + '<br>' +
                '&bull; Items: ' + (d.items_added || 0);
        })
        .catch(err => {
            box.style.background = '#fef2f2';
            box.style.border = '1px solid #fecaca';
            box.style.color = '#991b1b';
            box.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error: ' + err.message;
        });
}

function serverRestoreLatest() {
    if (!confirm('Restore latest server backup now?\n\nThis will copy back generated-pages/, data/* and uploads/.\nIt will NOT call AI.')) return;
    const box = _serverBox();
    box.style.display = 'block';
    box.style.background = '#fff7ed';
    box.style.border = '1px solid #fed7aa';
    box.style.color = '#9a3412';
    box.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restoring latest server backup...';

    fetch('../api/restore-content.php?type=server_restore')
        .then(r => r.json())
        .then(d => {
            if (!d.success) throw new Error(d.error || 'Unknown error');
            const r = d.restore || {};
            box.style.background = '#f0fdf4';
            box.style.border = '1px solid #86efac';
            box.style.color = '#14532d';
            box.innerHTML =
                '<i class="fas fa-check-circle" style="color:#10b981;"></i> <strong>Server restore complete!</strong><br>' +
                '&bull; Backup: <strong>' + (d.backup_file || '-') + '</strong><br>' +
                '&bull; Files written: ' + (r.written || 0) + '<br>' +
                '&bull; Skipped entries: ' + (r.skipped || 0) + '<br><br>' +
                '<i class="fas fa-sync-alt fa-spin"></i> Refreshing in 3 seconds...';
            setTimeout(() => location.reload(), 3000);
        })
        .catch(err => {
            box.style.background = '#fef2f2';
            box.style.border = '1px solid #fecaca';
            box.style.color = '#991b1b';
            box.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error: ' + err.message;
        });
}

function restoreContent(type) {
    const label = {
        all: 'ALL content',
        sync: 'PHP Files → DB sync',
        service_pages: 'Service Pages',
        blogs: 'Blogs',
        reviews: 'Reviews',
        faqs: 'FAQs'
    }[type] || type;

    const box = document.getElementById('restore-status');
    box.style.display = 'block';
    box.style.background = '#eff6ff';
    box.style.border = '1px solid #bfdbfe';
    box.style.color = '#1e40af';
    box.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restoring ' + label + ' from MySQL database... please wait.';

    fetch('../api/restore-content.php?type=' + type)
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                box.style.background = '#fef2f2';
                box.style.border = '1px solid #fecaca';
                box.style.color = '#991b1b';
                box.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error: ' + (data.error || 'Unknown error');
                return;
            }
            let html = '<i class="fas fa-check-circle" style="color:#10b981;"></i> <strong>Restore complete!</strong><br>';
            const r = data.results || {};
            if (r.sync) {
                let syncLine = '&bull; Synced ' + r.sync.synced + ' PHP files to DB (' + r.sync.skipped + ' already existed, ' + r.sync.total_files + ' total files)';
                if (r.sync.orphaned_removed > 0) syncLine += ' &mdash; <strong style="color:#dc2626;">removed ' + r.sync.orphaned_removed + ' ghost DB record(s)</strong> with no PHP file (these pages need to be regenerated)';
                html += syncLine + '<br>';
            }
            if (r.service_pages) html += '&bull; Service Pages: ' + r.service_pages.restored + ' restored, ' + r.service_pages.skipped + ' already existed<br>';
            if (r.blogs)         html += '&bull; Blogs: '         + r.blogs.restored         + ' restored, ' + r.blogs.skipped         + ' already existed<br>';
            if (r.reviews)       html += '&bull; Reviews: '       + r.reviews.restored       + ' restored, ' + r.reviews.skipped       + ' already existed<br>';
            if (r.faqs)          html += '&bull; FAQs: '          + r.faqs.restored          + ' restored, ' + r.faqs.skipped          + ' already existed<br>';
            const c = data.counts_after || {};
            html += '<br><strong>MySQL totals:</strong> ' + (c.service_pages||0) + ' service pages &bull; ' + (c.blogs||0) + ' blogs &bull; ' + (c.reviews||0) + ' reviews &bull; ' + (c.faqs||0) + ' FAQs';
            box.style.background = '#f0fdf4';
            box.style.border = '1px solid #86efac';
            box.style.color = '#14532d';
            box.innerHTML = html + '<br><br><i class="fas fa-sync-alt fa-spin"></i> Refreshing inventory in 3 seconds...';
            // Reload page so the inventory counts at the top update
            setTimeout(() => location.reload(), 3000);
        })
        .catch(err => {
            box.style.background = '#fef2f2';
            box.style.border = '1px solid #fecaca';
            box.style.color = '#991b1b';
            box.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Network error: ' + err.message;
        });
}
</script>

<?php include '../includes/footer.php'; ?>
