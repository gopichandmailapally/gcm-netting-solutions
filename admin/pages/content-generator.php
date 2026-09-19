<?php
/**
 * Simple Content Generator - FAQs, Blogs, Reviews
 * Clean, modern interface that actually works
 */
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/faq-categories.php';

// Start session with proper settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Content Generator';
include '../includes/header.php';

// Get all categories
$categories = get_faq_categories();
?>

<style>
/* ── Complete SEO System theme for Content Generator ── */
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
.stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 24px; }
.stat-card { background: white; padding: 22px 18px; display: flex; align-items: center; gap: 14px; position: relative; transition: background .2s; }
.stat-card:hover { background: #f8faff; }
.stat-card + .stat-card::before { content: ''; position: absolute; left: 0; top: 15%; bottom: 15%; width: 1px; background: #e2e8f0; }
.stat-icon-wrap { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; flex-shrink: 0; }
.stat-icon-wrap.blue   { background: linear-gradient(135deg,#667eea,#764ba2); }
.stat-icon-wrap.green  { background: linear-gradient(135deg,#10b981,#059669); }
.stat-icon-wrap.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.stat-icon-wrap.teal   { background: linear-gradient(135deg,#06b6d4,#0891b2); }
.stat-text-wrap { flex: 1; min-width: 0; }
.stat-text-wrap h3 { font-size: 10px !important; color: #94a3b8 !important; text-transform: uppercase !important; letter-spacing: .7px !important; font-weight: 700 !important; margin: 0 0 4px !important; line-height: 1.2 !important; }
.stat-text-wrap .value { font-size: 1.6rem !important; font-weight: 800 !important; color: #1e293b !important; line-height: 1.1 !important; margin: 0 !important; display: block !important; }
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; }
.seo-section-head { padding: 20px 28px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.seo-section-head .sec-num { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-num.purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.sec-num.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.sec-num.amber  { background: linear-gradient(135deg,#f59e0b,#d97706); }
.sec-num.green  { background: linear-gradient(135deg,#10b981,#059669); }
.sec-num.teal   { background: linear-gradient(135deg,#06b6d4,#0891b2); }
.seo-section-head h2 { font-size: 19px; font-weight: 700; color: #1e293b; margin: 0; }
.seo-section-body { padding: 24px 28px; }
.cg-links { margin-left: auto; display: flex; gap: 8px; flex-wrap: wrap; }
.cg-link { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; background: #f1f5f9; color: #475569; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid #e2e8f0; transition: all .2s; }
.cg-link:hover { background: #e2e8f0; color: #1e293b; text-decoration: none; }
.cg-link.ext { background: linear-gradient(135deg,rgba(102,126,234,.1),rgba(118,75,162,.08)); color: #667eea; border-color: rgba(102,126,234,.25); }
.cg-link.ext:hover { background: linear-gradient(135deg,#667eea,#764ba2); color: white; }
.gen-btn-row { display: grid; grid-template-columns: repeat(5,1fr); gap: 10px; margin-bottom: 16px; }
.cg-gen-btn { padding: 13px 8px; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; transition: all .22s; color: white; letter-spacing: .2px; text-align: center; }
.cg-gen-btn:hover { transform: translateY(-2px); filter: brightness(1.1); box-shadow: 0 6px 16px rgba(0,0,0,.2); }
.cg-gen-btn:disabled { opacity: .55; cursor: not-allowed; transform: none !important; filter: none !important; }
.cg-gen-btn.purple { background: linear-gradient(135deg,#667eea,#764ba2); box-shadow: 0 3px 10px rgba(102,126,234,.3); }
.cg-gen-btn.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); box-shadow: 0 3px 10px rgba(59,130,246,.3); }
.cg-gen-btn.amber  { background: linear-gradient(135deg,#f59e0b,#d97706); box-shadow: 0 3px 10px rgba(245,158,11,.3); }
.cg-gen-btn.green  { background: linear-gradient(135deg,#10b981,#059669); box-shadow: 0 3px 10px rgba(16,185,129,.3); display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
.cg-status-box { display: none; border-radius: 12px; margin-top: 10px; overflow: hidden; }
.cg-status-box.show { display: block; }
.cg-status-inner { padding: 14px 18px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
.cg-status-box.status-loading .cg-status-inner { background: #eff6ff; border-left: 4px solid #3b82f6; color: #1e40af; }
.cg-status-box.status-success .cg-status-inner { background: #f0fdf4; border-left: 4px solid #10b981; color: #065f46; }
.cg-status-box.status-error   .cg-status-inner { background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; }
.cg-progress-wrap { height: 6px; background: #e2e8f0; }
.cg-progress-bar { height: 6px; border-radius: 0; transition: width .4s ease; }
.status-loading .cg-progress-bar { background: linear-gradient(90deg,#3b82f6,#818cf8); }
.status-success .cg-progress-bar { background: linear-gradient(90deg,#10b981,#34d399); }
.status-error   .cg-progress-bar { background: linear-gradient(90deg,#ef4444,#f87171); }
.cg-status-icon { font-size: 16px; flex-shrink: 0; }
.cg-item-preview { margin-top: 8px; padding: 8px 18px 10px; font-size: 12px; color: #64748b; font-weight: 500; background: #f8fafc; border-top: 1px solid #e2e8f0; font-style: italic; }
.console-log { background: #0f172a; color: #94a3b8; padding: 18px 22px; font-family: 'Courier New', monospace; font-size: 12.5px; max-height: 240px; overflow-y: auto; line-height: 1.8; border-radius: 0 0 18px 18px; }
.console-log .log-ok   { color: #4ade80; }
.console-log .log-err  { color: #f87171; }
.console-log .log-info { color: #60a5fa; }
.console-log .log-dim  { color: #475569; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }
@keyframes progressStripe { 0%{background-position:0 0} 100%{background-position:40px 0} }
.loading { animation: pulse 1.5s ease-in-out infinite; }
.progress-stripe { background-image: linear-gradient(45deg,rgba(255,255,255,.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,.15) 50%,rgba(255,255,255,.15) 75%,transparent 75%,transparent); background-size: 40px 40px; animation: progressStripe 1s linear infinite; }
@media(max-width:900px){ .stats-grid{grid-template-columns:repeat(2,1fr);} .gen-btn-row{grid-template-columns:repeat(3,1fr);} }
@media(max-width:600px){ .seo-hero{flex-direction:column;align-items:flex-start;} .gen-btn-row{grid-template-columns:repeat(2,1fr);} }

/* ─ hide old wrapper ─ */
.content-generator { padding: 0; }
</style>

<div class="seo-page">

<!-- Hero Header -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-robot"></i> Content Generator</h1>
        <p>Generate FAQs, Blogs, Reviews &amp; Videos with Gemini AI &mdash; Simple and Fast</p>
    </div>
    <span class="seo-hero-badge"><i class="fas fa-magic" style="margin-right:6px;"></i>AI Content</span>
</div>

<!-- Info Banner -->
<div class="solution-banner">
    <div class="sol-icon"><i class="fas fa-brain"></i></div>
    <div>
        <strong>Gemini AI-Powered Content Hub</strong>
        <p>Auto-generate FAQs, blog posts, customer reviews and manage YouTube videos &mdash; all from one place with real AI content.</p>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-question-circle"></i></div>
        <div class="stat-text-wrap"><h3>Total FAQs</h3><div class="value" id="faq-count">0</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-newspaper"></i></div>
        <div class="stat-text-wrap"><h3>Total Blogs</h3><div class="value" id="blog-count">0</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><i class="fas fa-star"></i></div>
        <div class="stat-text-wrap"><h3>Total Reviews</h3><div class="value" id="review-count">0</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap teal"><i class="fas fa-video"></i></div>
        <div class="stat-text-wrap"><h3>Total Videos</h3><div class="value" id="video-count">0</div></div>
    </div>
</div>

<!-- Section 1: FAQ Generator -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">1</div>
        <h2><i class="fas fa-question-circle" style="color:#667eea;margin-right:8px;"></i>FAQ Generator</h2>
        <div class="cg-links">
            <a href="manage-faqs.php" class="cg-link"><i class="fas fa-list"></i> Manage FAQs</a>
            <a href="<?php echo SITE_URL; ?>/faqs.php" class="cg-link ext" target="_blank"><i class="fas fa-external-link-alt"></i> View on Site</a>
        </div>
    </div>
    <div class="seo-section-body">
        <p style="color:#64748b;font-size:13.5px;margin:0 0 16px;">Generate frequently asked questions for safety nets services. Click a number to generate that many FAQs at once.</p>
        <div class="gen-btn-row">
            <button onclick="generateContent('faq', 1)" class="cg-gen-btn purple">Generate 1</button>
            <button onclick="generateContent('faq', 5)" class="cg-gen-btn purple">Generate 5</button>
            <button onclick="generateContent('faq', 10)" class="cg-gen-btn purple">Generate 10</button>
            <button onclick="generateContent('faq', 25)" class="cg-gen-btn purple">Generate 25</button>
            <button onclick="generateContent('faq', 50)" class="cg-gen-btn purple">Generate 50</button>
        </div>
        <div id="faq-status" class="cg-status-box">
            <div class="cg-status-inner"><span class="cg-status-icon"></span><span class="cg-status-text"></span></div>
            <div class="cg-progress-wrap"><div class="cg-progress-bar" id="faq-bar" style="width:0%"></div></div>
            <div class="cg-item-preview" id="faq-preview" style="display:none;"></div>
        </div>
    </div>
</div>

<!-- Section 2: Blog Generator -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num blue">2</div>
        <h2><i class="fas fa-newspaper" style="color:#3b82f6;margin-right:8px;"></i>Blog Generator</h2>
        <div class="cg-links">
            <a href="manage-blogs.php" class="cg-link"><i class="fas fa-list"></i> Manage Blogs</a>
            <a href="<?php echo SITE_URL; ?>/blogs.php" class="cg-link ext" target="_blank"><i class="fas fa-external-link-alt"></i> View on Site</a>
        </div>
    </div>
    <div class="seo-section-body">
        <p style="color:#64748b;font-size:13.5px;margin:0 0 16px;">Generate detailed blog posts about safety nets, pigeon nets, bird nets and related topics to boost SEO rankings.</p>
        <div class="gen-btn-row">
            <button onclick="generateContent('blog', 1)" class="cg-gen-btn blue">Generate 1</button>
            <button onclick="generateContent('blog', 5)" class="cg-gen-btn blue">Generate 5</button>
            <button onclick="generateContent('blog', 10)" class="cg-gen-btn blue">Generate 10</button>
            <button onclick="generateContent('blog', 25)" class="cg-gen-btn blue">Generate 25</button>
            <button onclick="generateContent('blog', 50)" class="cg-gen-btn blue">Generate 50</button>
        </div>
        <div id="blog-status" class="cg-status-box">
            <div class="cg-status-inner"><span class="cg-status-icon"></span><span class="cg-status-text"></span></div>
            <div class="cg-progress-wrap"><div class="cg-progress-bar" id="blog-bar" style="width:0%"></div></div>
            <div class="cg-item-preview" id="blog-preview" style="display:none;"></div>
        </div>
    </div>
</div>

<!-- Section 3: Review Generator -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num amber">3</div>
        <h2><i class="fas fa-star" style="color:#f59e0b;margin-right:8px;"></i>Review Generator</h2>
        <div class="cg-links">
            <a href="manage-reviews.php" class="cg-link"><i class="fas fa-list"></i> Manage Reviews</a>
            <a href="<?php echo SITE_URL; ?>/reviews.php" class="cg-link ext" target="_blank"><i class="fas fa-external-link-alt"></i> View on Site</a>
        </div>
    </div>
    <div class="seo-section-body">
        <p style="color:#64748b;font-size:13.5px;margin:0 0 16px;">Generate realistic customer reviews to build trust and social proof for your safety nets business.</p>
        <div class="gen-btn-row">
            <button onclick="generateContent('review', 1)" class="cg-gen-btn amber">Generate 1</button>
            <button onclick="generateContent('review', 5)" class="cg-gen-btn amber">Generate 5</button>
            <button onclick="generateContent('review', 10)" class="cg-gen-btn amber">Generate 10</button>
            <button onclick="generateContent('review', 25)" class="cg-gen-btn amber">Generate 25</button>
            <button onclick="generateContent('review', 50)" class="cg-gen-btn amber">Generate 50</button>
        </div>
        <div id="review-status" class="cg-status-box">
            <div class="cg-status-inner"><span class="cg-status-icon"></span><span class="cg-status-text"></span></div>
            <div class="cg-progress-wrap"><div class="cg-progress-bar" id="review-bar" style="width:0%"></div></div>
            <div class="cg-item-preview" id="review-preview" style="display:none;"></div>
        </div>
    </div>
</div>

<!-- Section 4: Videos Manager -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num green">4</div>
        <h2><i class="fas fa-video" style="color:#10b981;margin-right:8px;"></i>Videos Manager</h2>
        <div class="cg-links">
            <a href="manage-videos.php" class="cg-link"><i class="fas fa-list"></i> Manage Videos</a>
            <a href="<?php echo SITE_URL; ?>/videos.php" class="cg-link ext" target="_blank"><i class="fas fa-external-link-alt"></i> View on Site</a>
        </div>
    </div>
    <div class="seo-section-body">
        <div style="background:#fef2f2;border:1px solid #fecaca;border-left:5px solid #ef4444;border-radius:12px;padding:16px 20px;display:flex;align-items:flex-start;gap:12px;margin-bottom:20px;">
            <div style="width:36px;height:36px;background:#ef4444;border-radius:9px;display:flex;align-items:center;justify-content:center;color:white;font-size:16px;flex-shrink:0;"><i class="fab fa-youtube"></i></div>
            <div><strong style="color:#991b1b;font-size:14px;font-weight:700;display:block;margin-bottom:3px;">YouTube Integration</strong><p style="color:#7f1d1d;font-size:13px;margin:0;line-height:1.5;">Add your YouTube videos easily! AI optimizes titles &amp; descriptions for SEO.</p></div>
        </div>
        <a href="manage-videos.php" class="cg-gen-btn green" style="padding:13px 24px;font-size:14px;"><i class="fas fa-plus"></i> Add New Video</a>
    </div>
</div>

<!-- Section 5: Activity Log -->
<div class="seo-section" style="margin-bottom:32px;">
    <div class="seo-section-head">
        <div class="sec-num teal">5</div>
        <h2><i class="fas fa-terminal" style="color:#06b6d4;margin-right:8px;"></i>Activity Log</h2>
    </div>
    <div class="console-log" id="console-log">[<?php echo date('H:i:s'); ?>] Content generator loaded successfully
[<?php echo date('H:i:s'); ?>] Ready to generate content
[<?php echo date('H:i:s'); ?>] Click any button above to start generating</div>
</div>

</div>

<script>
// ─── Console log helper (color-coded spans) ───────────────────────────────
function log(message, type) {
    const el = document.getElementById('console-log');
    const ts = new Date().toLocaleTimeString();
    const cls = type === 'ok' ? 'log-ok' : type === 'err' ? 'log-err' : type === 'info' ? 'log-info' : 'log-dim';
    const line = document.createElement('span');
    line.className = cls;
    line.textContent = `\n[${ts}] ${message}`;
    el.appendChild(line);
    el.scrollTop = el.scrollHeight;
}

// ─── Status box helpers ───────────────────────────────────────────────────
const STATUS_ICONS = { loading: '⏳', success: '✅', error: '❌' };

function showStatus(type, message, statusType, progress, preview) {
    const box  = document.getElementById(`${type}-status`);
    const bar  = document.getElementById(`${type}-bar`);
    const prev = document.getElementById(`${type}-preview`);

    box.className = `cg-status-box show status-${statusType}`;
    box.querySelector('.cg-status-icon').textContent = STATUS_ICONS[statusType] || '';
    box.querySelector('.cg-status-text').textContent = message;

    if (bar && progress !== undefined) {
        bar.style.width = progress + '%';
        if (statusType === 'loading') bar.classList.add('progress-stripe');
        else bar.classList.remove('progress-stripe');
    }
    if (prev) {
        if (preview) { prev.textContent = preview; prev.style.display = ''; }
        else prev.style.display = 'none';
    }
}

function hideStatus(type) {
    const box = document.getElementById(`${type}-status`);
    box.className = 'cg-status-box';
    const bar = document.getElementById(`${type}-bar`);
    if (bar) bar.style.width = '0%';
}

// ─── Single-item endpoints ────────────────────────────────────────────────
const SINGLE_ENDPOINTS = {
    faq:    '../api/generate-single-faq-simple.php',
    blog:   '../api/generate-single-blog-simple.php',
    review: '../api/generate-single-review-simple.php'
};

async function generateOne(type) {
    const endpoint = SINGLE_ENDPOINTS[type];
    const response = await fetch(endpoint, { method: 'POST', credentials: 'same-origin' });
    const text = await response.text();
    if (!text.trim()) throw new Error('Empty server response');
    try {
        return JSON.parse(text);
    } catch(e) {
        throw new Error('Invalid JSON: ' + text.substring(0, 100));
    }
}

// ─── Main generate function (FIXED: no .generator-card selector) ──────────
async function generateContent(type, count) {
    // Disable ALL generate buttons in the same section
    const section = document.querySelector(`#${type}-status`).closest('.seo-section');
    const allButtons = section ? section.querySelectorAll('.cg-gen-btn') : [];
    allButtons.forEach(btn => btn.disabled = true);

    const typeLabel = { faq: 'FAQ', blog: 'Blog', review: 'Review' }[type] || type.toUpperCase();
    log(`=== ${typeLabel} GENERATION STARTED (${count} items) ===`, 'info');

    let generated = 0;
    let errors    = 0;

    for (let i = 0; i < count; i++) {
        const done = i + 1;
        const pct  = Math.round((i / count) * 100);
        showStatus(type,
            `Generating ${typeLabel} ${done} of ${count}…`,
            'loading', pct
        );
        log(`Generating ${typeLabel} ${done}/${count}…`, 'dim');

        try {
            const data = await generateOne(type);
            if (data.success) {
                generated++;
                const label = data.title || data.question || data.name || 'Generated';
                const shortLabel = String(label).substring(0, 90);
                showStatus(type,
                    `Generating ${typeLabel} ${done} of ${count}…`,
                    'loading',
                    Math.round((done / count) * 100),
                    '↳ ' + shortLabel
                );
                log(`✓ ${typeLabel} ${done}/${count}: ${shortLabel}`, 'ok');
                updateCount(type, 1);
            } else {
                errors++;
                log(`✗ ${typeLabel} ${done}/${count} failed: ${data.message || 'Unknown error'}`, 'err');
            }
        } catch (err) {
            errors++;
            log(`✗ ${typeLabel} ${done}/${count} error: ${err.message}`, 'err');
        }

        if (i < count - 1) await new Promise(r => setTimeout(r, 600));
    }

    const allOk  = errors === 0;
    const msg    = allOk
        ? `Generated ${generated} of ${count} ${typeLabel}(s) successfully!`
        : `Done: ${generated} succeeded, ${errors} failed (out of ${count})`;

    showStatus(type, msg, allOk ? 'success' : 'error', 100);
    log(`=== ${typeLabel} COMPLETE: ${generated} OK, ${errors} errors ===`, allOk ? 'ok' : 'err');

    allButtons.forEach(btn => btn.disabled = false);
    setTimeout(() => hideStatus(type), 10000);
}

function updateCount(type, added) {
    const el = document.getElementById(`${type}-count`);
    if (!el) return;
    el.textContent = (parseInt(el.textContent) || 0) + (added || 1);
}

// ─── Load counts on DOMContentLoaded ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', async function() {
    log('Content Generator ready', 'info');
    log('Click any Generate button to start', 'dim');

    // FAQ / Blog / Review counts via JSON file counts
    try {
        const r = await fetch('../api/restore-content.php?type=status', { credentials: 'same-origin' });
        const d = await r.json();
        if (d.success && d.counts) {
            if (d.counts.blogs   !== undefined) document.getElementById('blog-count').textContent   = d.counts.blogs;
            if (d.counts.reviews !== undefined) document.getElementById('review-count').textContent = d.counts.reviews;
            if (d.counts.faqs    !== undefined) document.getElementById('faq-count').textContent    = d.counts.faqs;
        }
    } catch(e) { /* non-fatal */ }

    // Video count
    try {
        const r = await fetch('../api/get-video-count.php', { credentials: 'same-origin' });
        const d = await r.json();
        if (d.success) document.getElementById('video-count').textContent = d.count;
    } catch(e) { /* non-fatal */ }
});
</script>

<?php include '../includes/footer.php'; ?>
