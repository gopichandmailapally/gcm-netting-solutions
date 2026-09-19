<?php
/**
 * Google Search Console Integration
 * IndexNow setup, sitemap submission, and connection management
 */
define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php'); exit;
}

$root          = dirname(dirname(dirname(__FILE__)));
$settings_file = $root . '/data/gsc-settings.json';
$settings      = file_exists($settings_file) ? (json_decode(file_get_contents($settings_file), true) ?: []) : [];
$settings      = array_merge([
    'property_url'    => SITE_URL . '/',
    'indexnow_key'    => '',
    'auto_submit'     => false,
    'last_submit'     => null,
    'last_submit_urls'=> 0,
    'verification_code' => '',
], $settings);

// Detect if IndexNow key file exists on server
$key_file_exists = !empty($settings['indexnow_key']) && file_exists($root . '/' . $settings['indexnow_key'] . '.txt');

// OAuth / API status
$tokens_file     = $root . '/data/gsc-tokens.json';
$gsc_tokens      = file_exists($tokens_file) ? (json_decode(file_get_contents($tokens_file), true) ?: []) : [];
$oauth_connected = !empty($gsc_tokens['access_token']) && !empty($settings['oauth_client_id']);
$token_age_h     = $oauth_connected ? round((time() - ($gsc_tokens['obtained_at'] ?? 0)) / 3600, 1) : 0;
$last_sync       = $settings['last_sync'] ?? null;

// Flash message from OAuth callback
$gsc_flash = $_SESSION['gsc_msg'] ?? null;
unset($_SESSION['gsc_msg']);

$page_title = 'Search Console';
include '../includes/header.php';
?>
<style>
.gsc-page { padding: 0; }
.gsc-hero { background:white; border-radius:18px; box-shadow:0 4px 24px rgba(0,0,0,.07); padding:30px 36px; margin-bottom:22px; display:flex; align-items:center; justify-content:space-between; gap:20px; border-left:6px solid transparent; border-image:linear-gradient(180deg,#4285f4,#34a853) 1; }
.gsc-hero h1 { font-size:28px; font-weight:800; color:#1e293b; margin:0 0 5px; }
.gsc-hero p { color:#64748b; font-size:14px; margin:0; }
.gsc-card { background:white; border-radius:18px; box-shadow:0 4px 20px rgba(0,0,0,.07); margin-bottom:22px; overflow:hidden; }
.gsc-card-head { padding:20px 28px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:12px; }
.gsc-card-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:15px; color:white; flex-shrink:0; }
.gci-google { background:linear-gradient(135deg,#4285f4,#34a853); }
.gci-indexnow { background:linear-gradient(135deg,#f59e0b,#d97706); }
.gci-submit  { background:linear-gradient(135deg,#10b981,#059669); }
.gci-auto    { background:linear-gradient(135deg,#8b5cf6,#7c3aed); }
.gci-steps   { background:linear-gradient(135deg,#06b6d4,#0284c7); }
.gsc-card-head h2 { font-size:17px; font-weight:700; color:#1e293b; margin:0; }
.gsc-card-body { padding:26px 28px; }
.gsc-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media(max-width:640px){ .gsc-grid2 { grid-template-columns:1fr; } }
.gsc-field { margin-bottom:18px; }
.gsc-field label { display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:7px; }
.gsc-field input,.gsc-field select { width:100%; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:14px; color:#1e293b; background:#fafafa; box-sizing:border-box; }
.gsc-field input[type=text]:focus { border-color:#4285f4; outline:none; }
.gsc-btn { display:inline-flex; align-items:center; gap:8px; padding:11px 22px; border:none; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; transition:all .2s; text-decoration:none; }
.gsc-btn:hover { transform:translateY(-2px); }
.gsc-btn-blue   { background:linear-gradient(135deg,#4285f4,#1a73e8); color:white; box-shadow:0 5px 15px rgba(66,133,244,.35); }
.gsc-btn-green  { background:linear-gradient(135deg,#10b981,#059669); color:white; box-shadow:0 5px 15px rgba(16,185,129,.35); }
.gsc-btn-orange { background:linear-gradient(135deg,#f59e0b,#d97706); color:white; box-shadow:0 5px 15px rgba(245,158,11,.35); }
.gsc-btn-purple { background:linear-gradient(135deg,#8b5cf6,#7c3aed); color:white; box-shadow:0 5px 15px rgba(139,92,246,.35); }
.gsc-btn-grey   { background:#f1f5f9; color:#475569; }
.gsc-status-pill { padding:5px 14px; border-radius:20px; font-size:12px; font-weight:700; margin-left:auto; }
.pill-ok    { background:#d1fae5; color:#065f46; }
.pill-warn  { background:#fef3c7; color:#92400e; }
.pill-err   { background:#fee2e2; color:#991b1b; }
.gsc-badge-row { display:flex; gap:10px; flex-wrap:wrap; margin-top:14px; }
.gsc-badge { padding:6px 14px; border-radius:8px; font-size:12px; font-weight:600; }
.gb-green  { background:#d1fae5; color:#065f46; }
.gb-blue   { background:#dbeafe; color:#1e40af; }
.gb-orange { background:#fef3c7; color:#92400e; }
.gsc-code-block { background:#1e293b; color:#a3e635; border-radius:10px; padding:14px 18px; font-family:monospace; font-size:13px; margin:12px 0; word-break:break-all; }
.gsc-step-list { list-style:none; padding:0; margin:0; }
.gsc-step-list li { display:flex; align-items:flex-start; gap:14px; padding:14px; border-radius:10px; background:#f8fafc; margin-bottom:10px; }
.gsc-step-num { width:28px; height:28px; border-radius:50%; background:linear-gradient(135deg,#4285f4,#1a73e8); color:white; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:800; flex-shrink:0; }
.gsc-step-list li p { margin:0; font-size:13.5px; color:#475569; line-height:1.6; }
.gsc-step-list li strong { color:#1e293b; }
.gsc-toggle { position:relative; display:inline-block; width:48px; height:26px; flex-shrink:0; }
.gsc-toggle input { opacity:0; width:0; height:0; }
.gsc-toggle-slider { position:absolute; cursor:pointer; inset:0; background:#cbd5e1; border-radius:13px; transition:.3s; }
.gsc-toggle-slider:before { content:''; position:absolute; width:20px; height:20px; left:3px; bottom:3px; background:white; border-radius:50%; transition:.3s; }
.gsc-toggle input:checked + .gsc-toggle-slider { background:#10b981; }
.gsc-toggle input:checked + .gsc-toggle-slider:before { transform:translateX(22px); }
.gsc-row-between { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
.gsc-stat-row { display:grid; grid-template-columns:repeat(3,1fr); gap:0; background:white; border-radius:14px; border:1px solid #e2e8f0; overflow:hidden; margin-bottom:20px; }
.gsc-stat { padding:18px 20px; text-align:center; }
.gsc-stat + .gsc-stat { border-left:1px solid #e2e8f0; }
.gsc-stat-val { font-size:1.5rem; font-weight:800; color:#1e293b; }
.gsc-stat-lbl { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#94a3b8; margin-top:3px; }
#gscMsg { padding:12px 16px; border-radius:10px; font-size:14px; font-weight:600; margin-top:14px; display:none; }
.gscMsg-ok  { background:#d1fae5; color:#065f46; }
.gscMsg-err { background:#fee2e2; color:#991b1b; }
</style>

<div class="gsc-page">

<!-- Hero -->
<div class="gsc-hero">
    <div>
        <h1><i class="fab fa-google" style="color:#4285f4;"></i> Google Search Console</h1>
        <p>Connect your site, submit sitemaps, and ensure all 12,000+ pages are indexed by Google</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="https://search.google.com/search-console" target="_blank" class="gsc-btn gsc-btn-blue">
            <i class="fab fa-google"></i> Open Search Console
        </a>
        <button onclick="submitSitemapAll()" class="gsc-btn gsc-btn-green">
            <i class="fas fa-paper-plane"></i> Submit Sitemap Now
        </button>
    </div>
</div>

<!-- Stats row -->
<div class="gsc-stat-row">
    <div class="gsc-stat">
        <div class="gsc-stat-val"><?php echo number_format(file_exists($root.'/sitemap.xml') ? substr_count(file_get_contents($root.'/sitemap.xml'),'<url>') : 0); ?></div>
        <div class="gsc-stat-lbl">URLs in Sitemap</div>
    </div>
    <div class="gsc-stat">
        <div class="gsc-stat-val" style="color:<?php echo $key_file_exists ? '#10b981':'#f59e0b'; ?>;">
            <?php echo $key_file_exists ? '✓ Active' : '⚠ Not Set'; ?>
        </div>
        <div class="gsc-stat-lbl">IndexNow Key</div>
    </div>
    <div class="gsc-stat">
        <div class="gsc-stat-val"><?php echo $settings['last_submit'] ? date('M j', strtotime($settings['last_submit'])) : 'Never'; ?></div>
        <div class="gsc-stat-lbl">Last Submitted</div>
    </div>
</div>

<?php if ($gsc_flash): ?>
<div style="background:<?php echo $gsc_flash['ok'] ? '#d1fae5' : '#fee2e2'; ?>;border:1.5px solid <?php echo $gsc_flash['ok'] ? '#10b981' : '#ef4444'; ?>;border-radius:12px;padding:14px 20px;margin-bottom:18px;font-size:14px;font-weight:600;color:<?php echo $gsc_flash['ok'] ? '#065f46' : '#991b1b'; ?>">
    <?php echo htmlspecialchars($gsc_flash['text']); ?>
</div>
<?php endif; ?>

<!-- Section 0: Google Search Console API (OAuth) -->
<div class="gsc-card" id="oauth">
    <div class="gsc-card-head">
        <div class="gsc-card-icon" style="background:linear-gradient(135deg,#ea4335,#fbbc04);"><i class="fas fa-plug"></i></div>
        <h2>GSC API Access — Real Ranking Data</h2>
        <span class="gsc-status-pill <?php echo $oauth_connected ? 'pill-ok' : 'pill-warn'; ?>">
            <?php echo $oauth_connected ? '&#10003; API Connected' : 'Not Connected'; ?>
        </span>
    </div>
    <div class="gsc-card-body">
        <?php if ($oauth_connected): ?>
        <div style="background:#d1fae5;border-radius:10px;padding:14px 18px;margin-bottom:18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <i class="fab fa-google" style="color:#065f46;font-size:22px;"></i>
            <div style="flex:1;">
                <strong style="color:#065f46;">&#10003; Google Search Console API is connected</strong><br>
                <span style="font-size:13px;color:#064e3b;">Token obtained <?php echo $token_age_h; ?> hours ago<?php echo $last_sync ? ' &mdash; Last sync: <strong>' . htmlspecialchars($last_sync) . '</strong>' : ''; ?></span>
                <span id="gscSyncAge" style="font-size:12px;color:#065f46;margin-left:8px;"></span>
            </div>
            <button id="gscSyncBtn" onclick="syncRankingsNow(false)" style="padding:8px 16px;background:linear-gradient(135deg,#10b981,#059669);color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"><i class="fas fa-sync-alt"></i> Sync Rankings Now</button>
            <button onclick="disconnectGSC()" style="padding:8px 16px;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;"><i class="fas fa-unlink"></i> Disconnect</button>
        </div>
        <div id="gscSyncMsg" style="display:none;"></div>
        <?php endif; ?>
        <p style="color:#64748b;font-size:14px;margin-bottom:20px;">Connect via Google OAuth to pull <strong>real ranking positions, clicks, impressions and CTR</strong> directly from Google Search Console — the only accurate source of this data.</p>
        <div style="background:#fef3c7;border-left:4px solid #f59e0b;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#92400e;">
            <strong>One-time setup (5 minutes):</strong> You need a free Google Cloud project with the Search Console API enabled.
            <a href="https://console.cloud.google.com/" target="_blank" style="color:#d97706;font-weight:700;">console.cloud.google.com</a>
        </div>
        <div class="gsc-grid2">
            <div class="gsc-field">
                <label>OAuth Client ID <small style="color:#94a3b8;">(from Google Cloud Console)</small></label>
                <input type="text" id="oauthClientId" value="<?php echo htmlspecialchars($settings['oauth_client_id'] ?? ''); ?>" placeholder="123456789-xxxxx.apps.googleusercontent.com">
            </div>
            <div class="gsc-field">
                <label>OAuth Client Secret <small style="color:#94a3b8;">(keep private)</small></label>
                <input type="password" id="oauthClientSecret" value="<?php echo htmlspecialchars($settings['oauth_client_secret'] ?? ''); ?>" placeholder="GOCSPX-xxxxxxxxxxxxxxxx">
            </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:4px;">
            <button onclick="saveAndAuthorize()" class="gsc-btn gsc-btn-blue"><i class="fab fa-google"></i> Save &amp; Authorize with Google</button>
            <button onclick="saveOAuthCredentials()" class="gsc-btn gsc-btn-grey"><i class="fas fa-save"></i> Save Only</button>
        </div>
        <div id="oauthMsg" style="margin-top:14px;"></div>

        <!-- Step-by-step guide -->
        <div style="margin-top:22px;">
            <div style="font-weight:700;color:#1e293b;margin-bottom:12px;"><i class="fas fa-list-ol" style="color:#3b82f6;"></i> How to get OAuth credentials (5 steps):</div>
            <ul class="gsc-step-list">
                <li><div class="gsc-step-num">1</div><p>Go to <a href="https://console.cloud.google.com/" target="_blank" style="color:#4285f4;">Google Cloud Console</a> &rarr; Create a new project (e.g. &ldquo;GCM Netting Solutions SEO&rdquo;).</p></li>
                <li><div class="gsc-step-num">2</div><p>Go to <strong>APIs &amp; Services &rarr; Library</strong> &rarr; Search for <strong>&ldquo;Google Search Console API&rdquo;</strong> &rarr; Click <strong>Enable</strong>.</p></li>
                <li><div class="gsc-step-num">3</div><p>Go to <strong>APIs &amp; Services &rarr; OAuth consent screen</strong> &rarr; Choose <strong>External</strong> &rarr; Fill in app name &amp; email &rarr; Save. Add your Google email as a Test User.</p></li>
                <li><div class="gsc-step-num">4</div><p>Go to <strong>APIs &amp; Services &rarr; Credentials</strong> &rarr; Click <strong>&ldquo;Create Credentials&rdquo; &rarr; OAuth client ID</strong> &rarr; Type: <strong>Web application</strong>. Under <em>Authorized redirect URIs</em> add exactly:<br><code style="background:#1e293b;color:#a3e635;padding:4px 10px;border-radius:6px;font-size:12px;display:inline-block;margin-top:6px;word-break:break-all;"><?php echo rtrim(SITE_URL, '/'); ?>/admin/api/gsc-oauth-callback.php</code></p></li>
                <li><div class="gsc-step-num">5</div><p>Copy the <strong>Client ID</strong> and <strong>Client Secret</strong>, paste them above, and click <strong>&ldquo;Save &amp; Authorize with Google&rdquo;</strong>. You will be redirected to Google to authorize once.</p></li>
            </ul>
        </div>
    </div>
</div>

<!-- Section 1: GSC Property -->
<div class="gsc-card">
    <div class="gsc-card-head">
        <div class="gsc-card-icon gci-google"><i class="fab fa-google"></i></div>
        <h2>Google Search Console Setup</h2>
        <span class="gsc-status-pill <?php echo !empty($settings['property_url']) ? 'pill-ok' : 'pill-warn'; ?>">
            <?php echo !empty($settings['property_url']) ? '✓ Property Set' : 'Not Configured'; ?>
        </span>
    </div>
    <div class="gsc-card-body">
        <div class="gsc-grid2">
            <div class="gsc-field">
                <label>Site Property URL (exactly as in GSC)</label>
                <input type="text" id="gscPropertyUrl" value="<?php echo htmlspecialchars($settings['property_url']); ?>" placeholder="https://www.gcmsafetynets.in/">
            </div>
            <div class="gsc-field">
                <label>GSC Verification Meta Code (optional, for HTML tag method)</label>
                <input type="text" id="gscVerificationCode" value="<?php echo htmlspecialchars($settings['verification_code'] ?? ''); ?>" placeholder="google-site-verification: googleXXXXXXXX.html">
            </div>
        </div>
        <button onclick="saveGscSettings()" class="gsc-btn gsc-btn-blue"><i class="fas fa-save"></i> Save Settings</button>
        <a href="https://search.google.com/search-console/welcome" target="_blank" class="gsc-btn gsc-btn-grey" style="margin-left:10px;"><i class="fas fa-external-link-alt"></i> Add Property to GSC</a>
        <div id="gscMsg"></div>
    </div>
</div>

<!-- Section 2: IndexNow -->
<div class="gsc-card">
    <div class="gsc-card-head">
        <div class="gsc-card-icon gci-indexnow"><i class="fas fa-bolt"></i></div>
        <h2>IndexNow (Instant Indexing)</h2>
        <span class="gsc-status-pill <?php echo $key_file_exists ? 'pill-ok' : 'pill-warn'; ?>">
            <?php echo $key_file_exists ? '✓ Active — Pinging Bing & Google' : '⚠ Key Not Generated'; ?>
        </span>
    </div>
    <div class="gsc-card-body">
        <p style="color:#64748b;font-size:14px;margin-bottom:18px;">IndexNow notifies Bing (and indirectly Google) instantly when new pages are created, so they get indexed in minutes instead of weeks.</p>
        <?php if (!empty($settings['indexnow_key'])): ?>
        <div class="gsc-field">
            <label>Your IndexNow Key</label>
            <div class="gsc-code-block"><?php echo htmlspecialchars($settings['indexnow_key']); ?></div>
        </div>
        <div class="gsc-field">
            <label>Key Verification File URL</label>
            <a href="<?php echo SITE_URL . '/' . $settings['indexnow_key'] . '.txt'; ?>" target="_blank" style="color:#3b82f6;font-size:13px;">
                <?php echo SITE_URL . '/' . $settings['indexnow_key'] . '.txt'; ?>
                <i class="fas fa-external-link-alt"></i>
            </a>
            <?php if ($key_file_exists): ?>
                <span style="color:#10b981;font-weight:600;margin-left:10px;"><i class="fas fa-check-circle"></i> File exists on server</span>
            <?php else: ?>
                <span style="color:#f59e0b;font-weight:600;margin-left:10px;"><i class="fas fa-exclamation-triangle"></i> File not found — click Generate Key</span>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <p style="color:#f59e0b;font-weight:600;"><i class="fas fa-exclamation-triangle"></i> No IndexNow key generated yet.</p>
        <?php endif; ?>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
            <button onclick="generateIndexNowKey()" class="gsc-btn gsc-btn-orange"><i class="fas fa-key"></i> Generate Key &amp; Create File</button>
            <?php if ($key_file_exists): ?>
            <button onclick="submitIndexNow('sitemap')" class="gsc-btn gsc-btn-green"><i class="fas fa-paper-plane"></i> Submit Sitemap via IndexNow</button>
            <button onclick="submitIndexNow('all')" class="gsc-btn gsc-btn-blue"><i class="fas fa-globe"></i> Submit All Pages (Batch)</button>
            <?php endif; ?>
        </div>
        <div id="indexnowMsg" style="margin-top:14px;"></div>
    </div>
</div>

<!-- Section 3: Sitemap Status from Google -->
<div class="gsc-card" id="sitemapStatusCard">
    <div class="gsc-card-head">
        <div class="gsc-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="fas fa-chart-pie"></i></div>
        <h2>Sitemap Status — What Google Sees</h2>
        <span class="gsc-status-pill" id="smStatusPill" style="display:none;"></span>
    </div>
    <div class="gsc-card-body">

        <?php if (!$oauth_connected): ?>
        <div style="background:#fef3c7;border-left:4px solid #f59e0b;border-radius:8px;padding:14px 18px;font-size:13.5px;color:#92400e;">
            <i class="fas fa-exclamation-triangle"></i> <strong>Connect Google Search Console API</strong> (Section 6 above) to see live sitemap status directly from Google.
        </div>
        <?php else: ?>

        <div id="smLoading" style="text-align:center;padding:32px;color:#64748b;">
            <i class="fas fa-spinner fa-spin" style="font-size:32px;color:#10b981;"></i>
            <div style="margin-top:12px;font-size:14px;font-weight:600;">Checking sitemap status with Google…</div>
        </div>

        <div id="smError" style="display:none;padding:16px;background:#fee2e2;border-radius:10px;border-left:4px solid #ef4444;font-size:13.5px;color:#991b1b;font-weight:600;"></div>

        <div id="smResults" style="display:none;">

            <!-- Summary bar -->
            <div id="smSummary" style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:20px;"></div>

            <!-- Per-sitemap cards -->
            <div id="smCards"></div>

            <!-- Checked-at label -->
            <div style="margin-top:14px;font-size:12px;color:#94a3b8;text-align:right;">
                Live data from Google Search Console · Last checked: <span id="smCheckedAt">—</span>
            </div>
        </div>

        <div style="margin-top:16px;">
            <button id="smRefreshBtn" onclick="checkSitemapStatus()" class="gsc-btn gsc-btn-green">
                <i class="fas fa-sync-alt"></i> Refresh Status
            </button>
        </div>

        <?php endif; ?>
    </div>
</div>

<!-- Section 4: Submit Sitemap -->
<div class="gsc-card">
    <div class="gsc-card-head">
        <div class="gsc-card-icon gci-submit"><i class="fas fa-paper-plane"></i></div>
        <h2>Submit Sitemap to Search Engines</h2>
    </div>
    <div class="gsc-card-body">
        <p style="color:#64748b;font-size:14px;margin-bottom:20px;">
            Submitting your sitemap helps Google and Bing discover all <?php echo number_format(file_exists($root.'/sitemap.xml') ? substr_count(file_get_contents($root.'/sitemap.xml'),'<url>') : 0); ?> URLs on your site.
        </p>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px;">
            <div style="padding:18px;background:#f0f9ff;border-radius:12px;border:1px solid #bfdbfe;text-align:center;">
                <div style="font-size:24px;margin-bottom:8px;">🔵</div>
                <div style="font-weight:700;color:#1e40af;margin-bottom:4px;">Google Ping</div>
                <div style="font-size:12px;color:#64748b;">Notifies Google of sitemap update</div>
            </div>
            <div style="padding:18px;background:#fffbeb;border-radius:12px;border:1px solid #fde68a;text-align:center;">
                <div style="font-size:24px;margin-bottom:8px;">🟡</div>
                <div style="font-weight:700;color:#92400e;margin-bottom:4px;">Bing Ping</div>
                <div style="font-size:12px;color:#64748b;">Notifies Bing of sitemap update</div>
            </div>
            <div style="padding:18px;background:#f0fdf4;border-radius:12px;border:1px solid #bbf7d0;text-align:center;">
                <div style="font-size:24px;margin-bottom:8px;">⚡</div>
                <div style="font-weight:700;color:#065f46;margin-bottom:4px;">IndexNow</div>
                <div style="font-size:12px;color:#64748b;">Instant indexing via API</div>
            </div>
        </div>
        <button onclick="submitSitemapAll()" class="gsc-btn gsc-btn-green" style="font-size:15px;padding:14px 28px;">
            <i class="fas fa-rocket"></i> Submit to All Search Engines
        </button>
        <?php if ($settings['last_submit']): ?>
        <div style="margin-top:14px;font-size:13px;color:#64748b;">
            Last submitted: <strong><?php echo htmlspecialchars($settings['last_submit']); ?></strong>
            &nbsp;|&nbsp; URLs: <strong><?php echo number_format($settings['last_submit_urls'] ?? 0); ?></strong>
        </div>
        <?php endif; ?>
        <div id="submitMsg" style="margin-top:14px;"></div>
    </div>
</div>

<!-- Section 4: Auto-Submit -->
<div class="gsc-card">
    <div class="gsc-card-head">
        <div class="gsc-card-icon gci-auto"><i class="fas fa-sync-alt"></i></div>
        <h2>Auto-Submit on Content Creation</h2>
    </div>
    <div class="gsc-card-body">
        <div class="gsc-row-between" style="background:#f8fafc;padding:18px;border-radius:12px;">
            <div>
                <div style="font-weight:700;color:#1e293b;margin-bottom:4px;">Auto-submit sitemap when new content is generated</div>
                <div style="font-size:13px;color:#64748b;">Automatically ping search engines after daily blog generation, bulk blogs, or single blog creation</div>
            </div>
            <label class="gsc-toggle">
                <input type="checkbox" id="toggleAutoSubmit" <?php echo !empty($settings['auto_submit']) ? 'checked' : ''; ?> onchange="saveGscSettings()">
                <span class="gsc-toggle-slider"></span>
            </label>
        </div>
        <p style="font-size:13px;color:#94a3b8;margin-top:12px;"><i class="fas fa-info-circle"></i> This works together with the Auto-Refresh setting in Sitemap Generator — sitemap is regenerated first, then submitted.</p>
    </div>
</div>

<!-- Section 5: Setup Steps -->
<div class="gsc-card">
    <div class="gsc-card-head">
        <div class="gsc-card-icon gci-steps"><i class="fas fa-list-ol"></i></div>
        <h2>How to Add Your Site to Google Search Console</h2>
    </div>
    <div class="gsc-card-body">
        <ul class="gsc-step-list">
            <li>
                <div class="gsc-step-num">1</div>
                <p>Go to <a href="https://search.google.com/search-console" target="_blank" style="color:#4285f4;">search.google.com/search-console</a> and sign in with your Google account. Click <strong>"Add Property"</strong>.</p>
            </li>
            <li>
                <div class="gsc-step-num">2</div>
                <p>Choose <strong>"URL prefix"</strong> type and enter: <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;"><?php echo SITE_URL; ?>/</code></p>
            </li>
            <li>
                <div class="gsc-step-num">3</div>
                <p>Choose verification method: <strong>HTML file</strong> (Google gives you a .html file — upload it to your Hostinger File Manager), OR <strong>HTML tag</strong> — copy the meta tag and paste in the Verification Code box above.</p>
            </li>
            <li>
                <div class="gsc-step-num">4</div>
                <p>Once verified, go to <strong>Sitemaps</strong> in GSC left menu. Enter <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;">sitemap.xml</code> and click Submit.</p>
            </li>
            <li>
                <div class="gsc-step-num">5</div>
                <p>Use the <strong>"Submit to All Search Engines"</strong> button above to ping Google &amp; Bing every time you generate new content.</p>
            </li>
        </ul>
        <div style="margin-top:18px;padding:16px;background:#fef3c7;border-radius:10px;border-left:4px solid #f59e0b;">
            <strong style="color:#92400e;"><i class="fas fa-lightbulb"></i> Pro Tip:</strong>
            <span style="color:#78350f;font-size:13.5px;"> After submitting your sitemap, wait 2-3 days. Go to GSC → Coverage report to see how many URLs are indexed. If some show errors, use the <strong>SEO Issues Fixer</strong> in Auto SEO Optimizer to fix them.</span>
        </div>
    </div>
</div>

</div>

<script>
function showMsg(id, msg, ok) {
    const el = document.getElementById(id);
    el.style.display = 'block';
    el.className = ok ? 'gscMsg-ok' : 'gscMsg-err';
    el.innerHTML = (ok ? '✓ ' : '✗ ') + msg;
    el.style.display = 'block';
    el.style.padding = '12px 16px';
    el.style.borderRadius = '10px';
    el.style.fontWeight = '600';
    el.style.background = ok ? '#d1fae5' : '#fee2e2';
    el.style.color = ok ? '#065f46' : '#991b1b';
    el.style.marginTop = '14px';
}

function saveGscSettings() {
    const data = {
        property_url: document.getElementById('gscPropertyUrl').value,
        verification_code: document.getElementById('gscVerificationCode').value,
        auto_submit: document.getElementById('toggleAutoSubmit').checked
    };
    fetch('../api/save-gsc-settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    }).then(r => r.json())
      .then(d => showMsg('gscMsg', d.success ? 'Settings saved successfully!' : (d.message || 'Failed to save'), d.success))
      .catch(() => showMsg('gscMsg', 'Network error saving settings', false));
}

function saveOAuthCredentials(silent) {
    const clientId     = document.getElementById('oauthClientId').value.trim();
    const clientSecret = document.getElementById('oauthClientSecret').value.trim();
    if (!clientId || !clientSecret) {
        showMsg('oauthMsg', 'Please enter both Client ID and Client Secret', false); return Promise.resolve(false);
    }
    return fetch('../api/save-gsc-settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({oauth_client_id: clientId, oauth_client_secret: clientSecret})
    }).then(r => r.json())
      .then(d => {
          if (!silent) showMsg('oauthMsg', d.success ? 'Credentials saved! Now click "Save & Authorize with Google".' : 'Failed to save', d.success);
          return d.success;
      }).catch(() => { showMsg('oauthMsg','Network error',false); return false; });
}

function saveAndAuthorize() {
    saveOAuthCredentials(true).then(ok => {
        if (!ok) { showMsg('oauthMsg','Failed to save credentials. Please try again.', false); return; }
        const clientId    = document.getElementById('oauthClientId').value.trim();
        const redirectUri = encodeURIComponent(location.origin + '/admin/api/gsc-oauth-callback.php');
        const scope       = encodeURIComponent('https://www.googleapis.com/auth/webmasters.readonly');
        const authUrl     = 'https://accounts.google.com/o/oauth2/v2/auth?'
            + 'client_id=' + encodeURIComponent(clientId)
            + '&redirect_uri=' + redirectUri
            + '&response_type=code'
            + '&scope=' + scope
            + '&access_type=offline'
            + '&prompt=consent';
        showMsg('oauthMsg', '&#10003; Credentials saved! Redirecting to Google authorization...', true);
        setTimeout(() => { window.location.href = authUrl; }, 1200);
    });
}

function disconnectGSC() {
    if (!confirm('Disconnect Google Search Console API? Existing ranking data in the database will be kept.')) return;
    fetch('../api/save-gsc-settings.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({oauth_client_id: '', oauth_client_secret: '', oauth_connected: false})
    }).then(() => {
        fetch('../api/delete-gsc-tokens.php', {method:'POST'}).catch(()=>{});
        location.reload();
    });
}

function generateIndexNowKey() {
    const btn = event.target;
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    fetch('../api/indexnow-submit.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'generate_key'})
    }).then(r => r.json())
      .then(d => {
          btn.disabled = false; btn.innerHTML = '<i class="fas fa-key"></i> Generate Key & Create File';
          showMsg('indexnowMsg', d.success ? 'Key generated: ' + d.key + ' — File created on server!' : (d.message || 'Failed'), d.success);
          if (d.success) setTimeout(() => location.reload(), 2000);
      }).catch(() => { btn.disabled=false; showMsg('indexnowMsg','Network error',false); });
}

function submitIndexNow(type) {
    const btn = event.target;
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    fetch('../api/indexnow-submit.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: type === 'all' ? 'submit_all' : 'submit_sitemap'})
    }).then(r => r.json())
      .then(d => {
          btn.disabled = false; btn.innerHTML = type === 'all' ? '<i class="fas fa-globe"></i> Submit All Pages (Batch)' : '<i class="fas fa-paper-plane"></i> Submit Sitemap via IndexNow';
          showMsg('indexnowMsg', d.success ? (d.message || 'Submitted!') : (d.message || 'Failed'), d.success);
      }).catch(() => { btn.disabled=false; showMsg('indexnowMsg','Network error',false); });
}

function submitSitemapAll() {
    const btn = event.target;
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    fetch('../api/indexnow-submit.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'submit_all_engines'})
    }).then(r => r.json())
      .then(d => {
          btn.disabled = false; btn.innerHTML = '<i class="fas fa-rocket"></i> Submit to All Search Engines';
          showMsg('submitMsg', d.success ? (d.message || 'Sitemap submitted to Google & Bing!') : (d.message || 'Partial failure'), d.success);
      }).catch(() => { btn.disabled=false; showMsg('submitMsg','Network error',false); });
}


function checkSitemapStatus() {
    var loading = document.getElementById('smLoading');
    var results = document.getElementById('smResults');
    var errDiv  = document.getElementById('smError');
    var btn     = document.getElementById('smRefreshBtn');
    var pill    = document.getElementById('smStatusPill');

    if (!loading) return;

    loading.style.display = 'block';
    results.style.display = 'none';
    errDiv.style.display  = 'none';
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking…'; }

    fetch('../api/google-search-console.php?action=get_sitemap_status', {
        credentials: 'same-origin'
    }).then(function(r){ return r.json(); })
      .then(function(d) {
          loading.style.display = 'none';
          if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Status'; }

          if (!d.success) {
              errDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (d.message || 'Failed to fetch sitemap status.');
              errDiv.style.display = 'block';
              if (pill) { pill.textContent = 'Error'; pill.style.background='#fee2e2'; pill.style.color='#991b1b'; pill.style.display='inline-block'; }
              return;
          }

          document.getElementById('smCheckedAt').textContent = d.checked_at || '—';

          var sitemaps = d.sitemaps || [];
          var totalSub = 0, totalIdx = 0, hasErr = false, hasPend = false;
          sitemaps.forEach(function(sm){ totalSub += sm.submitted; totalIdx += sm.indexed; if (sm.errors > 0) hasErr = true; if (sm.is_pending) hasPend = true; });
          var overallPct = totalSub > 0 ? Math.round((totalIdx / totalSub) * 100) : 0;

          // Summary bar
          var summaryBg = hasErr ? '#fee2e2' : (hasPend ? '#fef3c7' : '#d1fae5');
          var summaryBd = hasErr ? '#ef4444' : (hasPend ? '#f59e0b' : '#10b981');
          var summaryTxt = hasErr ? '#991b1b' : (hasPend ? '#92400e' : '#065f46');
          var summaryIcon = hasErr ? '❌' : (hasPend ? '⏳' : '✅');
          var overallPctColor = overallPct >= 80 ? '#10b981' : (overallPct >= 40 ? '#f59e0b' : '#ef4444');

          document.getElementById('smSummary').innerHTML =
              '<div style="flex:1;min-width:180px;background:'+summaryBg+';border:1.5px solid '+summaryBd+';border-radius:12px;padding:16px 20px;">' +
              '  <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:'+summaryTxt+';margin-bottom:4px;">Overall Status</div>' +
              '  <div style="font-size:22px;font-weight:800;color:'+summaryTxt+';">' + summaryIcon + ' ' + (hasErr ? 'Has Errors' : (hasPend ? 'Pending' : 'Healthy')) + '</div>' +
              '</div>' +
              '<div style="flex:1;min-width:180px;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:12px;padding:16px 20px;">' +
              '  <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#15803d;margin-bottom:4px;">URLs Submitted</div>' +
              '  <div style="font-size:22px;font-weight:800;color:#15803d;">' + totalSub.toLocaleString() + '</div>' +
              '</div>' +
              '<div style="flex:1;min-width:180px;background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:12px;padding:16px 20px;">' +
              '  <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#1e40af;margin-bottom:4px;">URLs Indexed by Google</div>' +
              '  <div style="font-size:22px;font-weight:800;color:#1e40af;">' + totalIdx.toLocaleString() + ' <span style="font-size:14px;font-weight:600;color:'+overallPctColor+';">(' + overallPct + '%)</span></div>' +
              '</div>';

          // Per-sitemap detail cards
          var html = '';
          if (sitemaps.length === 0) {
              html = '<div style="background:#fef3c7;border-radius:10px;padding:16px 18px;font-size:13.5px;color:#92400e;font-weight:600;"><i class="fas fa-exclamation-triangle"></i> No sitemaps found in Google Search Console. Submit your sitemap first using the "Submit Sitemap" section below.</div>';
          } else {
              sitemaps.forEach(function(sm) {
                  var stColor = sm.status === 'ok' ? '#10b981' : (sm.status === 'error' ? '#ef4444' : (sm.status === 'warning' ? '#f59e0b' : '#3b82f6'));
                  var stBg    = sm.status === 'ok' ? '#d1fae5' : (sm.status === 'error' ? '#fee2e2' : (sm.status === 'warning' ? '#fef3c7' : '#dbeafe'));
                  var stLabel = sm.status === 'ok' ? '✅ Submitted & Active' : (sm.status === 'error' ? '❌ Has Errors' : (sm.status === 'pending' ? '⏳ Processing…' : '⚠️ Has Warnings'));
                  var pct     = sm.index_pct;
                  var pctColor = pct >= 80 ? '#10b981' : (pct >= 40 ? '#f59e0b' : '#ef4444');
                  var barFill  = pct >= 80 ? 'linear-gradient(90deg,#10b981,#059669)' : (pct >= 40 ? 'linear-gradient(90deg,#f59e0b,#d97706)' : 'linear-gradient(90deg,#ef4444,#dc2626)');

                  html += '<div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:14px;padding:20px 22px;margin-bottom:14px;">';
                  html += '  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;">';
                  html += '    <div style="flex:1;">';
                  html += '      <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:4px;">' + (sm.is_index ? 'Sitemap Index' : 'Sitemap') + '</div>';
                  html += '      <div style="font-size:14px;font-weight:700;color:#1e293b;word-break:break-all;">' + sm.path + '</div>';
                  html += '    </div>';
                  html += '    <span style="padding:6px 14px;border-radius:20px;font-size:12px;font-weight:700;background:'+stBg+';color:'+stColor+';white-space:nowrap;">'+stLabel+'</span>';
                  html += '  </div>';

                  // Progress bar
                  html += '  <div style="margin-bottom:14px;">';
                  html += '    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">';
                  html += '      <span style="font-size:12px;font-weight:700;color:#475569;">Indexing Progress</span>';
                  html += '      <span style="font-size:13px;font-weight:800;color:'+pctColor+';">'+sm.indexed.toLocaleString()+' / '+sm.submitted.toLocaleString()+' URLs ('+pct+'%)</span>';
                  html += '    </div>';
                  html += '    <div style="background:#e2e8f0;border-radius:999px;height:10px;overflow:hidden;">';
                  html += '      <div style="height:100%;width:'+pct+'%;background:'+barFill+';border-radius:999px;transition:width .6s;"></div>';
                  html += '    </div>';
                  html += '  </div>';

                  // Metadata grid
                  html += '  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;">';
                  html += '    <div style="background:white;border-radius:10px;padding:12px 14px;border:1px solid #e2e8f0;">';
                  html += '      <div style="font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;margin-bottom:4px;"><i class="fas fa-upload"></i> Submitted to GSC</div>';
                  html += '      <div style="font-size:13.5px;font-weight:700;color:#1e293b;">'+sm.last_submitted+'</div>';
                  html += '    </div>';
                  html += '    <div style="background:white;border-radius:10px;padding:12px 14px;border:1px solid #e2e8f0;">';
                  html += '      <div style="font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;margin-bottom:4px;"><i class="fas fa-cloud-download-alt"></i> Google Last Fetched</div>';
                  html += '      <div style="font-size:13.5px;font-weight:700;color:#1e293b;">'+sm.last_downloaded+'</div>';
                  if (sm.downloaded_ago) html += '      <div style="font-size:11px;color:#10b981;font-weight:600;margin-top:2px;">'+sm.downloaded_ago+'</div>';
                  html += '    </div>';
                  html += '    <div style="background:white;border-radius:10px;padding:12px 14px;border:1px solid #e2e8f0;">';
                  html += '      <div style="font-size:11px;color:#94a3b8;font-weight:700;text-transform:uppercase;margin-bottom:4px;"><i class="fas fa-exclamation-triangle"></i> Issues</div>';
                  html += '      <div style="font-size:13.5px;font-weight:700;color:#1e293b;">';
                  if (sm.errors > 0) html += '<span style="color:#ef4444;margin-right:8px;">'+sm.errors+' Error(s)</span>';
                  if (sm.warnings > 0) html += '<span style="color:#f59e0b;">'+sm.warnings+' Warning(s)</span>';
                  if (sm.errors === 0 && sm.warnings === 0) html += '<span style="color:#10b981;">None ✓</span>';
                  html += '      </div>';
                  html += '    </div>';
                  if (sm.is_pending) {
                      html += '    <div style="background:#dbeafe;border-radius:10px;padding:12px 14px;border:1px solid #bfdbfe;">';
                      html += '      <div style="font-size:11px;color:#1e40af;font-weight:700;text-transform:uppercase;margin-bottom:4px;"><i class="fas fa-clock"></i> Processing</div>';
                      html += '      <div style="font-size:13px;font-weight:700;color:#1e40af;">Google is still crawling — check again in a few hours</div>';
                      html += '    </div>';
                  }
                  html += '  </div>';
                  html += '</div>';
              });
          }
          document.getElementById('smCards').innerHTML = html;

          // Pill
          if (pill) {
              var overallStatus = hasErr ? '❌ Has Errors' : (hasPend ? '⏳ Pending' : '✅ ' + overallPct + '% Indexed');
              pill.textContent = overallStatus;
              pill.style.background = hasErr ? '#fee2e2' : (hasPend ? '#fef3c7' : '#d1fae5');
              pill.style.color = hasErr ? '#991b1b' : (hasPend ? '#92400e' : '#065f46');
              pill.style.display = 'inline-block';
          }

          results.style.display = 'block';
      }).catch(function(err) {
          loading.style.display = 'none';
          if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Status'; }
          errDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Network error: ' + err.message;
          errDiv.style.display = 'block';
      });
}

function syncRankingsNow(silent) {
    const btn = document.getElementById('gscSyncBtn');
    const msg = document.getElementById('gscSyncMsg');
    if (btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing…'; btn.disabled = true; }
    fetch('../api/google-search-console.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=sync',
        credentials: 'same-origin'
    }).then(r => r.json())
      .then(d => {
          if (btn) { btn.innerHTML = '<i class="fas fa-sync-alt"></i> Sync Rankings Now'; btn.disabled = false; }
          if (!silent && msg) {
              msg.style.display = 'block';
              msg.style.background = d.success ? '#d1fae5' : '#fee2e2';
              msg.style.color      = d.success ? '#065f46' : '#991b1b';
              msg.style.border     = '1.5px solid ' + (d.success ? '#10b981' : '#ef4444');
              msg.style.padding    = '12px 16px';
              msg.style.borderRadius = '10px';
              msg.style.fontWeight = '600';
              msg.style.fontSize   = '13.5px';
              msg.style.marginTop  = '14px';
              msg.textContent = (d.success ? '✓ ' : '✗ ') + (d.message || (d.success ? 'Rankings synced!' : 'Sync failed'));
          }
      }).catch(err => {
          if (btn) { btn.innerHTML = '<i class="fas fa-sync-alt"></i> Sync Rankings Now'; btn.disabled = false; }
          if (!silent && msg) { msg.style.display='block'; msg.textContent='✗ Network error: ' + err.message; }
      });
}
</script>

<?php
$gsc_oauth_connected = !empty($gsc_tokens['access_token']) && !empty($settings['oauth_client_id']);
$gsc_last_sync_str   = $settings['last_sync'] ?? '';
?>
<script>
(function() {
    const connected  = <?php echo $gsc_oauth_connected ? 'true' : 'false'; ?>;
    const lastSync   = <?php echo json_encode($gsc_last_sync_str); ?>;
    if (!connected) return;

    const lastMs  = lastSync ? new Date(lastSync.replace(' ','T')).getTime() : 0;
    const ageH    = lastMs  ? (Date.now() - lastMs) / 3600000 : 999;
    let syncedAt  = lastMs || Date.now();

    function fmtAge() {
        const s = Math.round((Date.now() - syncedAt) / 1000);
        return s < 60 ? s + 's ago' : s < 3600 ? Math.round(s/60) + 'm ago' : Math.round(s/3600) + 'h ago';
    }

    function updateLabel() {
        const el = document.getElementById('gscSyncAge');
        if (el) el.textContent = 'Last synced: ' + fmtAge();
    }

    // Auto-load sitemap status on page load
    setTimeout(function() { checkSitemapStatus(); }, 500);

    if (ageH > 6) {
        setTimeout(function() { syncRankingsNow(true); syncedAt = Date.now(); }, 2000);
    }

    setInterval(function() {
        updateLabel();
        if ((Date.now() - syncedAt) / 3600000 > 6) { syncedAt = Date.now(); syncRankingsNow(true); }
    }, 30000);
    setInterval(updateLabel, 10000);
    updateLabel();
})();
</script>

<?php include '../includes/footer.php'; ?>
