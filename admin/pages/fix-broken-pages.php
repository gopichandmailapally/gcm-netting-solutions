<?php
/**
 * Fix Broken Pages — Admin Tool
 */
define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('GCM_ADMIN_SESSION');
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Fix Broken Pages';
include '../includes/header.php';
?>

<div class="fbp-page">

<!-- Hero -->
<div class="fbp-hero">
    <div class="fbp-hero-left">
        <h1><i class="fas fa-tools"></i> Fix Broken Pages</h1>
        <p>Scan all 12,096 service pages for issues and fix them without re-generating AI content</p>
    </div>
    <button class="fbp-btn fbp-btn-blue" onclick="runScan()">
        <i class="fas fa-search"></i> Scan Database
    </button>
</div>

<!-- ★ PRIMARY TOOL: Direct File Scanner/Fixer ★ -->
<div class="fbp-section" style="border: 2px solid #10b981;">
    <div class="fbp-section-head" style="background: linear-gradient(135deg,#ecfdf5,#f0fdf4);">
        <div class="fbp-sec-icon sci-green" style="width:42px;height:42px;font-size:18px;"><i class="fas fa-file-code"></i></div>
        <div>
            <h2 style="color:#065f46;">Scan &amp; Fix Physical Files Directly <span style="font-size:12px;background:#10b981;color:white;padding:3px 8px;border-radius:6px;margin-left:8px;">RECOMMENDED</span></h2>
            <p>Reads every PHP file in <code>/generated-pages/</code> directly — catches issues the DB scan misses (AI notes, old templates, placeholders baked into files)</p>
        </div>
        <div class="fbp-action-btns">
            <button class="fbp-btn fbp-btn-blue" onclick="startFileScan()" id="btnFileScan">
                <i class="fas fa-search"></i> Scan Files
            </button>
            <button class="fbp-btn fbp-btn-green" onclick="startFileFix()" id="btnFileFix" style="display:none;">
                <i class="fas fa-magic"></i> Fix All Issues in 1 Request
            </button>
        </div>
    </div>

    <!-- File scan stats (hidden until scan runs) -->
    <div id="fileScanStats" style="display:none; padding:16px 28px; border-bottom:1px solid #e2e8f0;">
        <div style="display:flex;gap:0;background:#f8fafc;border-radius:12px;overflow:hidden;">
            <div class="fss-card" id="fss-total">
                <div class="fss-val" id="fss-v-total">–</div>
                <div class="fss-lbl">Total Files</div>
            </div>
            <div class="fss-card fss-purple" id="fss-ai">
                <div class="fss-val" id="fss-v-ai">–</div>
                <div class="fss-lbl">AI Notes</div>
            </div>
            <div class="fss-card fss-amber" id="fss-ph">
                <div class="fss-val" id="fss-v-ph">–</div>
                <div class="fss-lbl">Placeholders</div>
            </div>
            <div class="fss-card fss-red" id="fss-empty">
                <div class="fss-val" id="fss-v-empty">–</div>
                <div class="fss-lbl">Empty Content</div>
            </div>
            <div class="fss-card fss-orange" id="fss-old">
                <div class="fss-val" id="fss-v-old">–</div>
                <div class="fss-lbl">Old Template</div>
            </div>
        </div>
    </div>

    <div class="fbp-section-body">
        <div id="fileScanProgress" style="display:none;">
            <div class="fbp-prog-bar"><div class="fbp-prog-fill" id="fspBar"></div></div>
            <p class="fbp-prog-text" id="fspText">Starting...</p>
            <div class="fbp-log" id="fspLog"></div>
        </div>
        <div id="fileScanIdle" class="fbp-idle-msg">
            <i class="fas fa-lightbulb" style="color:#10b981;"></i>
            <strong>Why this matters:</strong> The database scan found "0 issues" earlier because the DB content and physical PHP files can differ.
            This scanner reads the <strong>actual files that are served to visitors</strong> — and fixes them in place.
            No AI calls. No re-generation. Just direct file patching.
        </div>
    </div>
</div>

<!-- Stat cards (populated by scan) -->
<div class="fbp-stats" id="statsRow" style="display:none;">
    <div class="fbp-stat">
        <div class="fbp-stat-icon fsi-red"><i class="fas fa-file-slash"></i></div>
        <div class="fbp-stat-text">
            <div class="fbp-stat-val" id="s-empty">–</div>
            <div class="fbp-stat-lbl">Empty Content</div>
            <div class="fbp-stat-sub">Needs AI re-generation</div>
        </div>
    </div>
    <div class="fbp-stat">
        <div class="fbp-stat-icon fsi-orange"><i class="fas fa-compress-alt"></i></div>
        <div class="fbp-stat-text">
            <div class="fbp-stat-val" id="s-thin">–</div>
            <div class="fbp-stat-lbl">Thin Content</div>
            <div class="fbp-stat-sub">&lt;80 words</div>
        </div>
    </div>
    <div class="fbp-stat">
        <div class="fbp-stat-icon fsi-purple"><i class="fas fa-robot"></i></div>
        <div class="fbp-stat-text">
            <div class="fbp-stat-val" id="s-ai">–</div>
            <div class="fbp-stat-lbl">AI Notes Leaked</div>
            <div class="fbp-stat-sub">Fixable without AI</div>
        </div>
    </div>
    <div class="fbp-stat">
        <div class="fbp-stat-icon fsi-amber"><i class="fas fa-brackets-curly"></i></div>
        <div class="fbp-stat-text">
            <div class="fbp-stat-val" id="s-ph">–</div>
            <div class="fbp-stat-lbl">Unfilled Placeholders</div>
            <div class="fbp-stat-sub">Fixable without AI</div>
        </div>
    </div>
    <div class="fbp-stat">
        <div class="fbp-stat-icon fsi-blue"><i class="fas fa-file-missing"></i></div>
        <div class="fbp-stat-text">
            <div class="fbp-stat-val" id="s-mf">–</div>
            <div class="fbp-stat-lbl">Missing PHP Files</div>
            <div class="fbp-stat-sub">File rebuild only</div>
        </div>
    </div>
</div>

<!-- Action section (shown after scan) -->
<div id="actionSection" style="display:none;">

    <!-- Cleanable issues -->
    <div class="fbp-section">
        <div class="fbp-section-head">
            <div class="fbp-sec-icon sci-green"><i class="fas fa-magic"></i></div>
            <div>
                <h2>Auto-Fix Cleanable Issues</h2>
                <p>Strips AI commentary, fills placeholders, converts markdown — NO AI call needed</p>
            </div>
            <div class="fbp-action-btns">
                <button class="fbp-btn fbp-btn-green" onclick="startFix('clean')" id="btnClean">
                    <i class="fas fa-broom"></i> Fix All Cleanable
                </button>
                <button class="fbp-btn fbp-btn-purple" onclick="startFix('rebuild_files')" id="btnRebuild">
                    <i class="fas fa-sync-alt"></i> Rebuild Missing Files
                </button>
            </div>
        </div>
        <div class="fbp-section-body">
            <div id="cleanProgress" style="display:none;">
                <div class="fbp-prog-bar"><div class="fbp-prog-fill" id="cleanBar"></div></div>
                <p class="fbp-prog-text" id="cleanText">Starting...</p>
                <div class="fbp-log" id="cleanLog"></div>
            </div>
            <div id="cleanIdle" class="fbp-idle-msg">
                <i class="fas fa-info-circle"></i>
                Click <strong>Fix All Cleanable</strong> to automatically remove AI commentary, replace 
                <code>[Insert Phone Number]</code>, <code>[Insert Website Address]</code> etc., 
                convert raw markdown (<code>**bold**</code>) to HTML, and rebuild the PHP files from 
                existing database content.
            </div>
        </div>
    </div>

    <!-- Pages needing re-generation -->
    <div class="fbp-section" id="regenSection">
        <div class="fbp-section-head">
            <div class="fbp-sec-icon sci-red"><i class="fas fa-exclamation-triangle"></i></div>
            <div>
                <h2>Pages Needing Re-Generation</h2>
                <p>These pages have empty or very thin content — must be re-generated with AI</p>
            </div>
            <a href="generate-pages.php" class="fbp-btn fbp-btn-red">
                <i class="fas fa-redo"></i> Open Page Generator
            </a>
        </div>
        <div class="fbp-section-body">
            <div class="fbp-idle-msg" id="regenNote">
                <i class="fas fa-info-circle"></i>
                Empty pages (<span id="regenCount">0</span>) need to be re-generated via the 
                <strong>Page Generator</strong>. The generator auto-skips already-complete pages 
                and only generates missing ones, so it's safe to run without touching good pages.
            </div>
        </div>
    </div>

    <!-- Broken pages list -->
    <div class="fbp-section">
        <div class="fbp-section-head">
            <div class="fbp-sec-icon sci-amber"><i class="fas fa-list-alt"></i></div>
            <h2>Broken Pages List</h2>
            <button class="fbp-btn fbp-btn-blue" onclick="loadList()" style="margin-left:auto;">
                <i class="fas fa-list"></i> Load List
            </button>
        </div>
        <div class="fbp-section-body" style="padding:0;">
            <div id="listContainer">
                <div class="fbp-idle-msg" style="margin:24px 28px;">
                    <i class="fas fa-info-circle"></i> Click "Load List" to see the first 200 broken pages with issue type.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- What gets fixed explanation -->
<div class="fbp-section" id="helpSection">
    <div class="fbp-section-head">
        <div class="fbp-sec-icon sci-blue"><i class="fas fa-lightbulb"></i></div>
        <h2>What This Tool Fixes</h2>
    </div>
    <div class="fbp-section-body">
        <div class="fbp-help-grid">
            <div class="fbp-help-card hc-purple">
                <div class="fbp-help-icon"><i class="fas fa-robot"></i></div>
                <h4>AI Commentary Leakage</h4>
                <p>AI sometimes appends its own notes like <em>"Key improvements and explanations: **HTML5 Structure:** Proper HTML5..."</em> at the end of content. This tool strips everything after those markers.</p>
            </div>
            <div class="fbp-help-card hc-amber">
                <div class="fbp-help-icon"><i class="fas fa-brackets-curly"></i></div>
                <h4>Unfilled Placeholders</h4>
                <p>Some older pages contain <code>[Insert Phone Number]</code>, <code>[Insert Website Address]</code>, <code>[Company Name]</code> etc. These get replaced with real GCM values.</p>
            </div>
            <div class="fbp-help-card hc-blue">
                <div class="fbp-help-icon"><i class="fas fa-font"></i></div>
                <h4>Markdown Artifacts</h4>
                <p>Raw <code>**bold text**</code> and <code>*italic*</code> markdown that wasn't converted to HTML. These are rendered as literal asterisks on the live page.</p>
            </div>
            <div class="fbp-help-card hc-green">
                <div class="fbp-help-icon"><i class="fas fa-file-code"></i></div>
                <h4>Missing PHP Files</h4>
                <p>DB has the content but the <code>/generated-pages/{slug}.php</code> file is missing on disk. The tool rebuilds these files from DB content — no AI needed.</p>
            </div>
        </div>
    </div>
</div>

</div><!-- /fbp-page -->

<style>
.fbp-page { padding: 0; }

/* Hero */
.fbp-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 30px 36px; margin-bottom: 22px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-left: 6px solid transparent; border-image: linear-gradient(180deg,#ef4444,#dc2626) 1; }
.fbp-hero h1 { font-size: 28px; font-weight: 800; color: #1e293b; margin: 0 0 5px; }
.fbp-hero h1 i { color: #ef4444; margin-right: 10px; }
.fbp-hero p { color: #64748b; font-size: 14px; margin: 0; }

/* Buttons */
.fbp-btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; border: none; border-radius: 11px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .22s; white-space: nowrap; }
.fbp-btn:hover { transform: translateY(-2px); text-decoration: none; }
.fbp-btn-blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); color: white; box-shadow: 0 5px 16px rgba(59,130,246,.35); }
.fbp-btn-green  { background: linear-gradient(135deg,#10b981,#059669); color: white; box-shadow: 0 5px 16px rgba(16,185,129,.35); }
.fbp-btn-purple { background: linear-gradient(135deg,#667eea,#764ba2); color: white; box-shadow: 0 5px 16px rgba(102,126,234,.35); }
.fbp-btn-red    { background: linear-gradient(135deg,#ef4444,#dc2626); color: white; box-shadow: 0 5px 16px rgba(239,68,68,.35); }
.fbp-btn-amber  { background: linear-gradient(135deg,#f59e0b,#d97706); color: white; box-shadow: 0 5px 16px rgba(245,158,11,.35); }
.fbp-btn:disabled { opacity: .55; cursor: not-allowed; transform: none !important; }

/* Stats */
.fbp-stats { display: grid; grid-template-columns: repeat(5,1fr); gap: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 22px; }
.fbp-stat { padding: 22px 18px; display: flex; align-items: center; gap: 14px; position: relative; transition: background .2s; }
.fbp-stat:hover { background: #f8faff; }
.fbp-stat + .fbp-stat::before { content:''; position:absolute; left:0; top:15%; bottom:15%; width:1px; background:#e2e8f0; }
.fbp-stat-icon { width: 48px; height: 48px; border-radius: 13px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; flex-shrink: 0; }
.fsi-red    { background: linear-gradient(135deg,#ef4444,#dc2626); }
.fsi-orange { background: linear-gradient(135deg,#f97316,#ea580c); }
.fsi-purple { background: linear-gradient(135deg,#8b5cf6,#7c3aed); }
.fsi-amber  { background: linear-gradient(135deg,#f59e0b,#d97706); }
.fsi-blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.fbp-stat-val { font-size: 1.8rem; font-weight: 800; color: #1e293b; line-height: 1.1; }
.fbp-stat-lbl { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #64748b; margin-top: 3px; }
.fbp-stat-sub { font-size: 10px; color: #94a3b8; margin-top: 2px; }

/* Sections */
.fbp-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 22px; overflow: hidden; }
.fbp-section-head { padding: 20px 28px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.fbp-section-head h2 { font-size: 17px; font-weight: 700; color: #1e293b; margin: 0; }
.fbp-section-head p  { font-size: 13px; color: #64748b; margin: 3px 0 0; }
.fbp-sec-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 15px; color: white; flex-shrink: 0; }
.sci-green  { background: linear-gradient(135deg,#10b981,#059669); }
.sci-red    { background: linear-gradient(135deg,#ef4444,#dc2626); }
.sci-amber  { background: linear-gradient(135deg,#f59e0b,#d97706); }
.sci-blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.fbp-action-btns { display: flex; gap: 10px; flex-wrap: wrap; margin-left: auto; }
.fbp-section-body { padding: 24px 28px; }

/* Progress */
.fbp-prog-bar  { background: #e2e8f0; height: 20px; border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
.fbp-prog-fill { height: 100%; background: linear-gradient(90deg,#10b981,#3b82f6); width: 0%; transition: width .3s; border-radius: 10px; }
.fbp-prog-text { color: #475569; font-size: 14px; font-weight: 600; margin: 0 0 12px; }
.fbp-log { max-height: 200px; overflow-y: auto; background: #f8fafc; border-radius: 10px; padding: 12px 16px; font-size: 12.5px; color: #475569; line-height: 1.8; }
.fbp-log .log-ok   { color: #059669; }
.fbp-log .log-err  { color: #dc2626; }
.fbp-log .log-info { color: #2563eb; }

/* Idle message */
.fbp-idle-msg { background: #f0f9ff; border-radius: 12px; border-left: 4px solid #3b82f6; padding: 16px 20px; font-size: 13.5px; color: #475569; line-height: 1.6; }
.fbp-idle-msg i { color: #3b82f6; margin-right: 8px; }
.fbp-idle-msg code { background: #dbeafe; color: #1e40af; padding: 2px 6px; border-radius: 4px; font-size: 12px; }

/* Broken pages table */
.fbp-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.fbp-table th { background: #f8fafc; padding: 12px 14px; text-align: left; font-weight: 700; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #e2e8f0; }
.fbp-table td { padding: 11px 14px; border-bottom: 1px solid #f1f5f9; color: #374151; vertical-align: middle; }
.fbp-table tr:hover td { background: #f8faff; }
.fbp-badge { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.badge-empty  { background: #fee2e2; color: #991b1b; }
.badge-thin   { background: #ffedd5; color: #9a3412; }
.badge-ai_notes  { background: #ede9fe; color: #6b21a8; }
.badge-placeholder { background: #fef3c7; color: #92400e; }
.badge-markdown { background: #dbeafe; color: #1e40af; }
.badge-unknown  { background: #f1f5f9; color: #475569; }

/* Help grid */
.fbp-help-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 16px; }
.fbp-help-card { border-radius: 14px; padding: 20px; border: 1px solid #e2e8f0; }
.fbp-help-card h4 { font-size: 15px; font-weight: 700; margin: 12px 0 8px; }
.fbp-help-card p  { font-size: 13px; color: #64748b; margin: 0; line-height: 1.6; }
.fbp-help-card em { color: #dc2626; font-style: normal; font-weight: 600; }
.fbp-help-card code { background: #f1f5f9; padding: 1px 5px; border-radius: 4px; font-size: 12px; }
.fbp-help-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; }
.hc-purple .fbp-help-icon { background: linear-gradient(135deg,#8b5cf6,#7c3aed); }
.hc-amber  .fbp-help-icon { background: linear-gradient(135deg,#f59e0b,#d97706); }
.hc-blue   .fbp-help-icon { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.hc-green  .fbp-help-icon { background: linear-gradient(135deg,#10b981,#059669); }

/* File scan mini-stats */
.fss-card { flex:1; padding:16px 12px; text-align:center; border-right:1px solid #e2e8f0; }
.fss-card:last-child { border-right:none; }
.fss-val  { font-size:1.6rem; font-weight:800; color:#1e293b; }
.fss-lbl  { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; color:#64748b; margin-top:4px; }
.fss-purple .fss-val { color:#7c3aed; }
.fss-amber  .fss-val { color:#d97706; }
.fss-red    .fss-val { color:#dc2626; }
.fss-orange .fss-val { color:#ea580c; }

@media(max-width:768px) {
    .fbp-stats { grid-template-columns: repeat(2,1fr); }
    .fbp-help-grid { grid-template-columns: 1fr; }
    .fbp-hero { flex-direction: column; align-items: flex-start; }
    .fbp-action-btns { margin-left: 0; }
}
</style>

<script>
const API = '../api/fix-broken-pages.php';
let scanData = {};

// ══ FILE SCAN & FIX ══════════════════════════════════
let fileScanOffset = 0;
let fileScanTotals = { ai_notes:0, placeholder:0, empty_content:0, old_template:0, total:0 };

function startFileScan() {
    fileScanOffset = 0;
    fileScanTotals = { ai_notes:0, placeholder:0, empty_content:0, old_template:0, total:0 };
    document.getElementById('fileScanProgress').style.display = 'block';
    document.getElementById('fileScanIdle').style.display     = 'none';
    document.getElementById('fileScanStats').style.display    = 'block';
    document.getElementById('btnFileScan').disabled = true;
    document.getElementById('btnFileScan').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning...';
    document.getElementById('btnFileFix').style.display = 'none';
    document.getElementById('fspLog').innerHTML = '';
    updateFspBar(0, 'Starting file scan...');
    nextFileScanBatch();
}

function nextFileScanBatch() {
    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'scan_files', offset: fileScanOffset, limit: 500 })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) { fspAppendLog('error', 'Error: ' + (data.error||'Unknown')); finishFileScan(); return; }

        fileScanTotals.ai_notes      += data.ai_notes      || 0;
        fileScanTotals.placeholder   += data.placeholder   || 0;
        fileScanTotals.empty_content += data.empty_content || 0;
        fileScanTotals.old_template  += data.old_template  || 0;
        fileScanTotals.total          = data.total         || 0;

        document.getElementById('fss-v-total').textContent = fileScanTotals.total.toLocaleString();
        document.getElementById('fss-v-ai').textContent    = fileScanTotals.ai_notes.toLocaleString();
        document.getElementById('fss-v-ph').textContent    = fileScanTotals.placeholder.toLocaleString();
        document.getElementById('fss-v-empty').textContent = fileScanTotals.empty_content.toLocaleString();
        document.getElementById('fss-v-old').textContent   = fileScanTotals.old_template.toLocaleString();

        const pct = data.total > 0 ? Math.round(data.scanned / data.total * 100) : 100;
        updateFspBar(pct, `Scanned ${(data.scanned||0).toLocaleString()} / ${data.total.toLocaleString()} files...`);
        fspAppendLog('ok', `Batch: AI notes +${data.ai_notes}, Placeholders +${data.placeholder}, Old template +${data.old_template}`);

        fileScanOffset = data.scanned || (fileScanOffset + 500);

        if (data.done) {
            finishFileScan();
        } else {
            setTimeout(nextFileScanBatch, 100);
        }
    })
    .catch(e => { fspAppendLog('error', 'Network: ' + e.message); finishFileScan(); });
}

function finishFileScan() {
    const total = fileScanTotals;
    const issues = total.ai_notes + total.placeholder + total.empty_content + total.old_template;
    updateFspBar(100, `✓ Scan complete! Found ${issues.toLocaleString()} issues across ${total.total.toLocaleString()} files.`);
    fspAppendLog('info', `── Done: AI notes: ${total.ai_notes} | Placeholders: ${total.placeholder} | Empty: ${total.empty_content} | Old template: ${total.old_template} ──`);

    document.getElementById('btnFileScan').disabled = false;
    document.getElementById('btnFileScan').innerHTML = '<i class="fas fa-redo"></i> Re-Scan Files';

    if (issues > 0) {
        const fixBtn = document.getElementById('btnFileFix');
        fixBtn.style.display = 'inline-flex';
        fixBtn.innerHTML = '<i class="fas fa-magic"></i> Fix ' + issues.toLocaleString() + ' Issues in Files';
    }
}

// ── FILE FIX (single request — avoids Hostinger WAF blocking) ──
function startFileFix() {
    const btn = document.getElementById('btnFileFix');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing... (please wait)';
    document.getElementById('btnFileScan').disabled = true;

    const totalFiles = fileScanTotals.total || 12032;
    updateFspBar(30, `Processing all ${totalFiles.toLocaleString()} files server-side... This takes 20–60 seconds. Do NOT close this page.`);
    fspAppendLog('info', '── Sending single fix_all_sync request to server ──');
    fspAppendLog('info', 'Server will process every file and return when done. Please wait...');

    // Animate the bar while waiting
    let fakeProgress = 30;
    const ticker = setInterval(() => {
        fakeProgress = Math.min(90, fakeProgress + 1);
        document.getElementById('fspBar').style.width = fakeProgress + '%';
    }, 600);

    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fix_all_sync' })
    })
    .then(r => {
        clearInterval(ticker);
        if (!r.ok) throw new Error('HTTP ' + r.status + ' — Server blocked request');
        return r.json();
    })
    .then(data => {
        clearInterval(ticker);
        if (!data.success) {
            fspAppendLog('error', 'Server error: ' + (data.error || 'Unknown'));
            updateFspBar(0, 'Fix failed — see log below');
        } else {
            updateFspBar(100, `✓ Done! Fixed ${(data.fixed||0).toLocaleString()} files · Errors: ${data.errors||0} · Skipped: ${(data.skipped||0).toLocaleString()} · Total: ${(data.total||0).toLocaleString()}`);
            fspAppendLog('ok', `── Finished. Fixed: ${data.fixed} | Errors: ${data.errors} | Skipped: ${data.skipped} | Total: ${data.total} ──`);
            if (data.fixed > 0) {
                fspAppendLog('ok', 'All broken pages fixed. Press Ctrl+Shift+R on any page to hard-refresh and verify.');
                document.getElementById('fileScanStats').style.display = 'block';
                document.getElementById('fss-v-total').textContent = (data.total||0).toLocaleString();
                document.getElementById('fss-v-ai').textContent    = '0 (fixed)';
                document.getElementById('fss-v-ph').textContent    = '0 (fixed)';
            }
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-redo"></i> Fix Again';
        document.getElementById('btnFileScan').disabled = false;
        document.getElementById('btnFileScan').innerHTML = '<i class="fas fa-redo"></i> Re-Scan Files';
    })
    .catch(e => {
        clearInterval(ticker);
        fspAppendLog('error', 'Network/Server Error: ' + e.message);
        fspAppendLog('error', 'If you see "Access Denied" — Hostinger WAF blocked this request.');
        fspAppendLog('info', 'Wait 2 minutes and try again, or use the "Fix Files (Batched)" fallback below.');
        updateFspBar(0, 'Request failed — see log');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-redo"></i> Retry Fix';
        document.getElementById('btnFileScan').disabled = false;
    });
}

function updateFspBar(pct, text) {
    document.getElementById('fspBar').style.width = pct + '%';
    document.getElementById('fspText').textContent = text;
}
function fspAppendLog(type, msg) {
    const el = document.getElementById('fspLog');
    const line = document.createElement('div');
    line.className = 'log-' + type;
    line.textContent = new Date().toLocaleTimeString() + '  ' + msg;
    el.appendChild(line);
    el.scrollTop = el.scrollHeight;
}
// ══ END FILE SCAN & FIX ══════════════════════════════

function runScan() {
    const btn = event.currentTarget;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning...';

    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'scan' })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search"></i> Re-Scan';

        if (!data.success) { alert('Error: ' + (data.error || 'Scan failed')); return; }

        const s = data.stats;
        scanData = s;

        document.getElementById('s-empty').textContent = s.empty.toLocaleString();
        document.getElementById('s-thin').textContent  = s.thin.toLocaleString();
        document.getElementById('s-ai').textContent    = s.ai_notes.toLocaleString();
        document.getElementById('s-ph').textContent    = s.placeholders.toLocaleString();
        document.getElementById('s-mf').textContent    = s.missing_file.toLocaleString();
        document.getElementById('regenCount').textContent = (s.empty + s.thin).toLocaleString();

        document.getElementById('statsRow').style.display    = 'grid';
        document.getElementById('actionSection').style.display = 'block';

        // Update button labels
        document.getElementById('btnClean').innerHTML =
            '<i class="fas fa-broom"></i> Fix ' + s.total_cleanable.toLocaleString() + ' Cleanable Pages';
        document.getElementById('btnRebuild').innerHTML =
            '<i class="fas fa-sync-alt"></i> Rebuild ' + s.missing_file.toLocaleString() + ' Missing Files';
    })
    .catch(e => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search"></i> Scan All Pages';
        alert('Network error: ' + e.message);
    });
}

// ── Fix batch runner ──────────────────────────────────
let fixMode = 'clean';
let fixOffset = 0;
let fixTotal  = 0;
let fixFixed  = 0;
let fixErrors = 0;

function startFix(mode) {
    fixMode   = mode;
    fixOffset = 0;
    fixFixed  = 0;
    fixErrors = 0;
    fixTotal  = mode === 'rebuild_files'
        ? (scanData.missing_file || 0)
        : (scanData.total_cleanable || 0);

    document.getElementById('cleanProgress').style.display = 'block';
    document.getElementById('cleanIdle').style.display     = 'none';
    document.getElementById('cleanLog').innerHTML = '';
    document.getElementById('btnClean').disabled   = true;
    document.getElementById('btnRebuild').disabled = true;

    updateBar(0, 'Starting...');
    processNextBatch();
}

function processNextBatch() {
    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fix_batch', mode: fixMode, offset: fixOffset, batch_size: 50 })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            appendLog('error', 'Batch error: ' + (data.error || 'Unknown'));
            finishFix();
            return;
        }

        fixFixed  += data.fixed  || 0;
        fixErrors += data.errors || 0;
        fixOffset += data.processed || 0;

        const pct = fixTotal > 0 ? Math.min(100, Math.round(fixOffset / fixTotal * 100)) : 100;
        updateBar(pct, `Fixed ${fixFixed.toLocaleString()} pages · ${fixErrors} errors · offset ${fixOffset}`);
        appendLog('ok', `Batch: +${data.fixed} fixed, ${data.errors} errors`);

        if (data.has_more) {
            setTimeout(processNextBatch, 200);
        } else {
            finishFix();
        }
    })
    .catch(e => {
        appendLog('error', 'Network error: ' + e.message);
        finishFix();
    });
}

function finishFix() {
    updateBar(100, `✓ Done! Fixed ${fixFixed.toLocaleString()} pages. Errors: ${fixErrors}.`);
    appendLog('info', `── Finished. Fixed: ${fixFixed} | Errors: ${fixErrors} | Mode: ${fixMode} ──`);
    document.getElementById('btnClean').disabled   = false;
    document.getElementById('btnRebuild').disabled = false;

    if (fixFixed > 0) {
        appendLog('info', 'Hard-refresh any fixed page in browser (Ctrl+Shift+R) to verify.');
    }
}

function updateBar(pct, text) {
    document.getElementById('cleanBar').style.width = pct + '%';
    document.getElementById('cleanText').textContent = text;
}

function appendLog(type, msg) {
    const el = document.getElementById('cleanLog');
    const line = document.createElement('div');
    line.className = 'log-' + type;
    line.textContent = new Date().toLocaleTimeString() + '  ' + msg;
    el.appendChild(line);
    el.scrollTop = el.scrollHeight;
}

// ── List loader ───────────────────────────────────────
function loadList() {
    const btn = event.currentTarget;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_list' })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-list"></i> Refresh List';

        if (!data.success) { alert('Error: ' + data.error); return; }

        const pages = data.pages;
        if (!pages.length) {
            document.getElementById('listContainer').innerHTML =
                '<div class="fbp-idle-msg" style="margin:24px 28px;"><i class="fas fa-check-circle" style="color:#10b981;"></i> No broken pages found!</div>';
            return;
        }

        let html = '<div style="overflow-x:auto;"><table class="fbp-table"><thead><tr>'
            + '<th>#</th><th>Slug / Page</th><th>Keyword</th><th>Area</th><th>Words</th><th>Issue</th>'
            + '</tr></thead><tbody>';

        pages.forEach((p, i) => {
            const badgeClass = 'badge-' + (p.issue || 'unknown');
            const issueLabel = {
                empty: 'Empty', thin: 'Thin', ai_notes: 'AI Notes',
                placeholder: 'Placeholder', markdown: 'Markdown', unknown: 'Unknown'
            }[p.issue] || p.issue;
            html += `<tr>
                <td style="color:#94a3b8;font-size:12px;">${i+1}</td>
                <td><a href="https://gcmsafetynets.in/${p.slug}" target="_blank" style="color:#3b82f6;font-size:12.5px;text-decoration:none;">${p.slug || '–'}</a></td>
                <td style="font-size:12.5px;">${p.keyword || '–'}</td>
                <td style="font-size:12.5px;">${p.area || '–'}</td>
                <td style="font-size:12.5px;font-weight:700;color:${(p.words < 80 ? '#ef4444' : '#374151')};">${p.words || 0}</td>
                <td><span class="fbp-badge ${badgeClass}">${issueLabel}</span></td>
            </tr>`;
        });

        html += '</tbody></table></div>';
        if (pages.length === 200) {
            html += '<p style="padding:12px 18px;font-size:13px;color:#64748b;">Showing first 200. Fix and re-scan to update.</p>';
        }
        document.getElementById('listContainer').innerHTML = html;
    })
    .catch(e => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-list"></i> Load List';
        alert('Error: ' + e.message);
    });
}
</script>

<?php include '../includes/footer.php'; ?>
