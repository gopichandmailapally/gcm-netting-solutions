<?php
/**
 * Fix FAQ Format — Admin Dashboard
 * Scans all generated pages for broken FAQ paragraph format and converts them to accordion.
 */
define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php'); exit;
}

$root    = dirname(dirname(dirname(__FILE__)));
$gen_dir = $root . '/generated-pages/';
$all     = glob($gen_dir . '*.php') ?: [];
$total   = count(array_filter($all, fn($f) => basename($f) !== 'index.php'));

$page_title = 'Fix FAQ Format';
include '../includes/header.php';
?>
<style>
.fq-page { padding: 0; }
.fq-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 28px 32px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px; border-left: 6px solid #3b82f6; }
.fq-hero h1 { font-size: 26px; font-weight: 800; color: #1e293b; margin: 0 0 4px; }
.fq-hero p  { color: #64748b; font-size: 14px; margin: 0; }
.fq-explain { background: #eff6ff; border: 1px solid #bfdbfe; border-left: 5px solid #3b82f6; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; }
.fq-explain strong { color: #1e40af; font-size: 14px; display: block; margin-bottom: 6px; }
.fq-explain p  { color: #1e3a8a; font-size: 13px; margin: 3px 0 0; line-height: 1.6; }
.fq-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.07); overflow: hidden; margin-bottom: 20px; }
.fq-stat  { padding: 22px 20px; text-align: center; position: relative; }
.fq-stat + .fq-stat::before { content:''; position:absolute; left:0; top:20%; bottom:20%; width:1px; background:#e2e8f0; }
.fq-stat-val  { font-size: 32px; font-weight: 800; color: #1e293b; }
.fq-stat-lbl  { font-size: 12px; color: #64748b; font-weight: 600; margin-top: 2px; }
.fq-stat.red   .fq-stat-val { color: #ef4444; }
.fq-stat.green .fq-stat-val { color: #10b981; }
.fq-stat.blue  .fq-stat-val { color: #3b82f6; }
.fq-actions { background: white; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.07); padding: 24px 28px; margin-bottom: 20px; }
.fq-actions h2 { font-size: 17px; font-weight: 800; color: #1e293b; margin: 0 0 18px; }
.fq-btn-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
.fq-btn { padding: 12px 24px; border: none; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: opacity .2s; }
.fq-btn:hover { opacity: .87; }
.fq-btn.scan    { background: #3b82f6; color: white; }
.fq-btn.fix-all { background: linear-gradient(135deg,#3b82f6,#2563eb); color: white; }
.fq-btn.resume  { background: #f59e0b; color: white; }
.fq-btn:disabled { opacity: .5; cursor: not-allowed; }
#fqDash { border-radius: 14px; overflow: hidden; border: 1.5px solid #bfdbfe; background: white; margin-top: 8px; }
.fq-dash-hdr { background: linear-gradient(135deg,#3b82f6,#2563eb); padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
.fq-dash-hdr-title { color: white; font-weight: 800; font-size: 14px; }
.fq-dash-hdr-sub   { color: rgba(255,255,255,.8); font-size: 12px; }
.fq-dash-prog { padding: 14px 20px; border-bottom: 1px solid #eff6ff; }
.fq-dash-bar-wrap { height: 12px; background: #e2e8f0; border-radius: 6px; overflow: hidden; margin: 8px 0; }
.fq-dash-bar { height: 100%; width: 0%; background: linear-gradient(90deg,#3b82f6,#06b6d4); border-radius: 6px; transition: width .5s ease; }
.fq-dash-stats { display: flex; gap: 20px; flex-wrap: wrap; font-size: 12px; color: #64748b; }
.fq-dash-cur { padding: 10px 20px; background: #eff6ff; border-bottom: 1px solid #bfdbfe; display: flex; align-items: center; gap: 10px; font-size: 13px; color: #1e40af; }
.fq-dash-log-wrap { padding: 12px 20px; }
.fq-dash-log { display: flex; flex-direction: column; gap: 5px; max-height: 180px; overflow-y: auto; }
.fq-results { background: white; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.07); padding: 20px 24px; }
.fq-results h2 { font-size: 16px; font-weight: 800; color: #1e293b; margin: 0 0 14px; }
.fq-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.fq-table th { background: #f8faff; padding: 10px 12px; text-align: left; font-weight: 700; color: #64748b; border-bottom: 2px solid #e2e8f0; }
.fq-table td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
.fq-table tr:hover td { background: #fafafa; }
.fq-badge { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.fq-badge.broken { background: #fee2e2; color: #dc2626; }
.fq-badge.fixed  { background: #d1fae5; color: #065f46; }
.fq-fix-one { padding: 4px 10px; background: #3b82f6; color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; }
.fq-fix-one:hover { background: #2563eb; }
</style>

<div class="fq-page">

<!-- Hero -->
<div class="fq-hero">
    <div>
        <h1><i class="fas fa-question-circle" style="color:#3b82f6;margin-right:8px;"></i>Fix FAQ Format</h1>
        <p>Finds and fixes pages where FAQ sections are plain-text paragraphs (Q:/A:) instead of the collapsible accordion format — no content changed</p>
    </div>
    <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:12px;padding:12px 18px;text-align:center;">
        <div style="font-size:24px;font-weight:800;color:#2563eb;"><?php echo number_format($total); ?></div>
        <div style="font-size:11px;color:#1e40af;font-weight:600;">Service Pages</div>
    </div>
</div>

<!-- Explanation -->
<div class="fq-explain">
    <strong><i class="fas fa-info-circle"></i> Root Cause</strong>
    <p>AI-generated pages sometimes write FAQ sections as plain paragraphs starting with "Q:" and "A:" instead of the proper <code>&lt;ol&gt;&lt;li&gt;&lt;strong&gt;</code> accordion structure.
    These plain-text FAQs do not expand/collapse and appear broken on the page.</p>
    <p style="margin-top:6px;"><strong>Fix:</strong> Converts each Q/A paragraph block into the correct list format so the existing accordion JavaScript activates automatically.
    Creates <code>.faq.bak</code> backup before every write. Page content beyond FAQ sections is never changed.</p>
</div>

<!-- Stats row -->
<div class="fq-stats">
    <div class="fq-stat blue">
        <div class="fq-stat-val" id="statTotal"><?php echo number_format($total); ?></div>
        <div class="fq-stat-lbl">Total Pages</div>
    </div>
    <div class="fq-stat red">
        <div class="fq-stat-val" id="statBroken">—</div>
        <div class="fq-stat-lbl">Broken FAQ Pages</div>
    </div>
    <div class="fq-stat green">
        <div class="fq-stat-val" id="statFixed">0</div>
        <div class="fq-stat-lbl">Fixed This Session</div>
    </div>
    <div class="fq-stat">
        <div class="fq-stat-val" id="statClean">—</div>
        <div class="fq-stat-lbl">Already Correct</div>
    </div>
</div>

<!-- Actions -->
<div class="fq-actions">
    <h2><i class="fas fa-tools" style="color:#3b82f6;margin-right:8px;"></i>Actions</h2>
    <div class="fq-btn-row">
        <button class="fq-btn scan" id="btnScan" onclick="startScan()">
            <i class="fas fa-search"></i> Scan All Pages for FAQ Issues
        </button>
        <button class="fq-btn fix-all" id="btnFixAll" onclick="startFixAll()" disabled>
            <i class="fas fa-magic"></i> Fix All Broken Pages
        </button>
        <button class="fq-btn resume" id="btnResume" onclick="resumeFixAll()" style="display:none;">
            <i class="fas fa-redo"></i> Resume
        </button>
    </div>

    <div id="fqDash" style="display:none;">
        <div class="fq-dash-hdr">
            <div class="fq-dash-hdr-title"><i class="fas fa-cog fa-spin" id="fqSpinIcon"></i>&nbsp;<span id="fqHdrTitle">Starting...</span></div>
            <div class="fq-dash-hdr-sub" id="fqHdrSub">Preparing...</div>
        </div>
        <div class="fq-dash-prog">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="font-size:12px;color:#64748b;font-weight:600;">Progress</span>
                <span style="font-size:13px;font-weight:800;color:#3b82f6;" id="fqPct">0%</span>
            </div>
            <div class="fq-dash-bar-wrap"><div class="fq-dash-bar" id="fqBar"></div></div>
            <div class="fq-dash-stats">
                <span><i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i> Broken: <strong id="fqBrokenCount">0</strong></span>
                <span><i class="fas fa-check-circle" style="color:#10b981;"></i> Fixed: <strong id="fqFixedCount">0</strong></span>
                <span><i class="fas fa-file-alt" style="color:#3b82f6;"></i> Done: <strong id="fqProcessed">0</strong> / <?php echo number_format($total); ?></span>
            </div>
        </div>
        <div class="fq-dash-cur" id="fqCurBatch">
            <i class="fas fa-spinner fa-spin" style="color:#3b82f6;"></i>
            <span>Initializing...</span>
        </div>
        <div class="fq-dash-log-wrap">
            <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px;">Batch Log</div>
            <div class="fq-dash-log" id="fqLog"></div>
        </div>
    </div>
</div>

<!-- Results table -->
<div class="fq-results" id="fqResults" style="display:none;">
    <h2 id="fqResultsTitle"><i class="fas fa-list" style="color:#3b82f6;margin-right:8px;"></i>Scan Results</h2>
    <div id="fqResultsContent"></div>
</div>

</div><!-- /fq-page -->

<script>
const totalPages = <?php echo $total; ?>;
let brokenFiles = [];
let totalBroken = 0, totalFixed = 0;
let fixOffset = 0, fixRetries = 0;
let allErrors = [];
const maxRetries = 3;

/* ── Scan ─────────────────────────────────────────────────────────────── */
function startScan() {
    brokenFiles  = [];
    totalBroken  = 0;
    document.getElementById('btnScan').disabled   = true;
    document.getElementById('btnFixAll').disabled = true;
    document.getElementById('btnResume').style.display = 'none';
    document.getElementById('fqResults').style.display = 'none';
    document.getElementById('statBroken').textContent = '0';
    document.getElementById('statClean').textContent  = '0';
    showDash();
    document.getElementById('fqHdrTitle').textContent = 'Scanning all ' + totalPages.toLocaleString() + ' pages…';
    document.getElementById('fqHdrSub').textContent   = 'Checking FAQ sections…';
    document.getElementById('fqCurBatch').innerHTML   = '<i class="fas fa-spinner fa-spin" style="color:#3b82f6;font-size:14px;"></i> <span>Reading FAQ sections across all generated pages…</span>';
    updateBar(20);

    fetch('../api/fix-faq-format.php?action=scan')
        .then(r => r.json())
        .then(d => {
            if (!d.success) { scanError('API error: ' + d.message); return; }
            brokenFiles = d.broken || [];
            totalBroken = d.broken_count;

            document.getElementById('statBroken').textContent = totalBroken.toLocaleString();
            document.getElementById('statClean').textContent  = (d.total - totalBroken).toLocaleString();
            updateBar(100);

            document.getElementById('fqSpinIcon').className   = 'fas fa-check-circle';
            document.getElementById('fqHdrTitle').textContent = 'Scan complete — ' + totalBroken.toLocaleString() + ' broken FAQ pages found';
            document.getElementById('fqHdrSub').textContent   = d.total.toLocaleString() + ' pages scanned';
            document.getElementById('fqBrokenCount').textContent = totalBroken.toLocaleString();
            document.getElementById('fqProcessed').textContent  = d.total.toLocaleString();
            document.getElementById('btnScan').disabled = false;

            if (totalBroken > 0) {
                document.getElementById('fqCurBatch').innerHTML = '<i class="fas fa-check-circle" style="color:#10b981;font-size:15px;"></i> <span style="color:#065f46;font-weight:700;">Scan finished — ' + totalBroken.toLocaleString() + ' pages need fixing</span>';
                document.getElementById('btnFixAll').disabled = false;
                showScanResults();
            } else {
                document.getElementById('fqCurBatch').innerHTML = '<i class="fas fa-check-circle" style="color:#10b981;font-size:15px;"></i> <span style="color:#065f46;font-weight:700;">All FAQs are already in correct accordion format!</span>';
                const rs = document.getElementById('fqResults');
                rs.style.display = 'block';
                document.getElementById('fqResultsTitle').innerHTML = '<i class="fas fa-check-circle" style="color:#10b981;margin-right:8px;"></i>All Pages Have Correct FAQ Format';
                document.getElementById('fqResultsContent').innerHTML = '<div style="padding:20px;background:#f0fdf4;border-radius:10px;color:#065f46;font-weight:700;">✅ All ' + totalPages.toLocaleString() + ' pages already have FAQ sections in the correct accordion format. No fixes needed!</div>';
            }
        })
        .catch(e => scanError(e.message));
}

function scanError(msg) {
    document.getElementById('btnScan').disabled = false;
    document.getElementById('fqCurBatch').innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i> <span style="color:#ef4444;">Error: ' + msg + '</span> <button onclick="startScan()" style="margin-left:10px;padding:3px 10px;background:#3b82f6;color:white;border:none;border-radius:5px;cursor:pointer;font-size:12px;">Retry</button>';
}

/* ── Fix All ──────────────────────────────────────────────────────────── */
function startFixAll() {
    GCMAlert.confirm(
        'Fix all ' + totalBroken.toLocaleString() + ' broken FAQ pages? Backups (.faq.bak) will be created before every write.',
        'Confirm Fix All',
        function() {
            totalFixed = 0;
            allErrors  = [];
            fixOffset  = 0;
            fixRetries = 0;
            document.getElementById('btnFixAll').disabled = true;
            document.getElementById('btnScan').disabled   = true;
            document.getElementById('btnResume').style.display = 'none';
            document.getElementById('statFixed').textContent = '0';
            document.getElementById('fqLog').innerHTML = '';
            document.getElementById('fqResults').style.display = 'none';
            showDash();
            document.getElementById('fqHdrTitle').textContent = 'Fixing FAQ sections…';
            document.getElementById('fqHdrSub').textContent   = 'Starting…';
            nextFixBatch();
        }
    );
}

function resumeFixAll() {
    fixRetries = 0;
    document.getElementById('btnResume').style.display = 'none';
    document.getElementById('btnScan').disabled = true;
    showDash();
    nextFixBatch();
}

function nextFixBatch() {
    const batchSize = 20;
    const batchNum  = Math.floor(fixOffset / batchSize) + 1;
    const start     = fixOffset + 1;
    const end       = Math.min(fixOffset + batchSize, totalPages);

    document.getElementById('fqHdrSub').textContent = fixOffset.toLocaleString() + ' / ' + totalPages.toLocaleString() + ' pages';
    document.getElementById('fqCurBatch').innerHTML = '<i class="fas fa-spinner fa-spin" style="color:#3b82f6;font-size:14px;"></i> <span>Fixing batch ' + batchNum + ' — pages ' + start.toLocaleString() + ' to ' + end.toLocaleString() + '</span>';

    const fd = new FormData();
    fd.append('action', 'fix');
    fd.append('offset', fixOffset);
    fd.append('limit',  batchSize);

    fetch('../api/fix-faq-format.php', { method: 'POST', body: fd })
        .then(r => r.text())
        .then(function(text) {
            let d;
            try { d = JSON.parse(text); }
            catch(e) { handleFixRetry('Server error (non-JSON response)'); return; }

            fixRetries = 0;
            if (!d.success) { handleFixRetry('API: ' + (d.message || 'unknown error')); return; }

            totalFixed += d.fixed;
            if (d.errors && d.errors.length) allErrors = allErrors.concat(d.errors);
            document.getElementById('statFixed').textContent    = totalFixed.toLocaleString();
            document.getElementById('fqFixedCount').textContent = totalFixed.toLocaleString();
            document.getElementById('fqProcessed').textContent  = d.processed.toLocaleString();

            const pct = Math.round((d.processed / d.total) * 100);
            updateBar(pct);
            document.getElementById('fqHdrSub').textContent = d.processed.toLocaleString() + ' / ' + d.total.toLocaleString() + ' pages — ' + pct + '% done';

            const log  = document.getElementById('fqLog');
            const item = document.createElement('div');
            item.style.cssText = 'display:flex;align-items:center;gap:8px;padding:5px 10px;background:#f0fdf4;border-radius:7px;border-left:3px solid #10b981;font-size:12px;';
            item.innerHTML = '<i class="fas fa-check" style="color:#10b981;flex-shrink:0;"></i>'
                + '<span style="color:#065f46;font-weight:600;">Batch ' + batchNum + '</span>'
                + '<span style="color:#64748b;">pp. ' + start.toLocaleString() + '–' + end.toLocaleString()
                + ' &bull; ' + d.fixed + ' fixed &bull; ' + d.skipped + ' already OK'
                + (d.errors && d.errors.length ? ' &bull; ' + d.errors.length + ' errors' : '') + '</span>'
                + '<span style="margin-left:auto;color:#10b981;font-weight:700;">' + pct + '%</span>';
            log.prepend(item);

            fixOffset = d.processed;
            if (!d.completed) {
                setTimeout(nextFixBatch, 800);
            } else {
                onFixComplete();
            }
        })
        .catch(e => handleFixRetry(e.message));
}

function handleFixRetry(msg) {
    if (fixRetries < maxRetries) {
        fixRetries++;
        const wait = fixRetries * 3;
        document.getElementById('fqCurBatch').innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#f59e0b;"></i> <span style="color:#92400e;">⚠ ' + msg + ' — retrying in ' + wait + 's (attempt ' + fixRetries + '/' + maxRetries + ')…</span>';
        setTimeout(nextFixBatch, fixRetries * 3000);
    } else {
        document.getElementById('fqCurBatch').innerHTML = '<i class="fas fa-times-circle" style="color:#ef4444;"></i> <span style="color:#ef4444;">Too many errors at offset ' + fixOffset + '. Click Resume to continue from here.</span>';
        document.getElementById('btnResume').style.display = 'inline-flex';
        document.getElementById('btnScan').disabled = false;
    }
}

function onFixComplete() {
    document.getElementById('fqSpinIcon').className   = 'fas fa-check-circle';
    document.getElementById('fqHdrTitle').textContent = 'Fix complete — ' + totalFixed.toLocaleString() + ' pages repaired';
    document.getElementById('fqCurBatch').innerHTML   = '<i class="fas fa-trophy" style="color:#f59e0b;font-size:15px;"></i> <span style="color:#065f46;font-weight:700;">All done — ' + totalFixed.toLocaleString() + ' FAQ sections converted to accordion format!</span>';
    document.getElementById('btnScan').disabled = false;
    document.getElementById('statBroken').textContent = '0';
    document.getElementById('statClean').textContent  = totalPages.toLocaleString();

    const rs = document.getElementById('fqResults');
    rs.style.display = 'block';
    document.getElementById('fqResultsTitle').innerHTML = '<i class="fas fa-check-circle" style="color:#10b981;margin-right:8px;"></i>Fix Complete';
    document.getElementById('fqResultsContent').innerHTML =
        '<div style="padding:20px;background:#f0fdf4;border-radius:10px;border:1.5px solid #86efac;">'
        + '<div style="font-weight:800;color:#15803d;font-size:15px;margin-bottom:12px;"><i class="fas fa-check-circle"></i> FAQ format repair complete</div>'
        + '<div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:12px;">'
        + '<div style="background:white;padding:10px 16px;border-radius:8px;font-size:13px;color:#065f46;"><strong style="font-size:20px;display:block;color:#10b981;">' + totalFixed.toLocaleString() + '</strong>Pages fixed</div>'
        + '</div>'
        + '<div style="font-size:13px;color:#15803d;background:white;padding:10px 14px;border-radius:8px;"><i class="fas fa-shield-alt" style="color:#10b981;margin-right:6px;"></i> All fixes used <code>.faq.bak</code> backups. Page content beyond FAQ sections was never changed.</div>'
        + (allErrors.length ? '<div style="margin-top:12px;padding:10px 14px;background:#fef9c3;border-radius:8px;border:1px solid #fbbf24;"><strong style="color:#92400e;">⚠ ' + allErrors.length + ' page(s) detected as broken but format not recognized — still need manual review:</strong><ul style="margin:6px 0 0 16px;font-size:12px;color:#78350f;">' + allErrors.map(e => '<li>' + e + '</li>').join('') + '</ul></div>' : '')
        + '</div>';
}

/* ── Scan results table ───────────────────────────────────────────────── */
function showScanResults() {
    const rs = document.getElementById('fqResults');
    rs.style.display = 'block';
    document.getElementById('fqResultsTitle').innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#ef4444;margin-right:8px;"></i>' + totalBroken.toLocaleString() + ' Pages With Broken FAQ Format';

    let html = '<p style="color:#64748b;font-size:13px;margin:0 0 12px;">These pages have FAQ content in plain paragraph format. Click <strong>Fix All Broken Pages</strong> above to repair all at once, or fix individually below.</p>';
    html += '<table class="fq-table"><thead><tr><th>#</th><th>Page Filename</th><th>Status</th><th>Action</th></tr></thead><tbody>';
    brokenFiles.forEach((file, i) => {
        const slug = file.replace('.php', '');
        const url  = 'https://www.gcmsafetynets.in/' + slug;
        html += '<tr>'
            + '<td style="color:#94a3b8;">' + (i + 1) + '</td>'
            + '<td><a href="' + url + '" target="_blank" style="color:#3b82f6;font-weight:600;">' + file + '</a></td>'
            + '<td id="fqStatus-' + i + '"><span class="fq-badge broken">Broken FAQ</span></td>'
            + '<td><button class="fq-fix-one" onclick="fixOne(' + i + ',\'' + file + '\')" id="fqFixBtn-' + i + '"><i class="fas fa-magic"></i> Fix</button></td>'
            + '</tr>';
    });
    html += '</tbody></table>';
    document.getElementById('fqResultsContent').innerHTML = html;
}

function fixOne(idx, filename) {
    const btn  = document.getElementById('fqFixBtn-' + idx);
    const stat = document.getElementById('fqStatus-' + idx);
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    const fd = new FormData();
    fd.append('action', 'fix');
    fd.append('file',   filename);
    fetch('../api/fix-faq-format.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                stat.innerHTML = '<span class="fq-badge fixed">Fixed ✓</span>';
                btn.style.display = 'none';
                totalFixed++;
                document.getElementById('statFixed').textContent = totalFixed.toLocaleString();
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-magic"></i> Retry';
            }
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-magic"></i> Retry'; });
}

/* ── Helpers ──────────────────────────────────────────────────────────── */
function showDash() {
    document.getElementById('fqDash').style.display = 'block';
    document.getElementById('fqSpinIcon').className = 'fas fa-cog fa-spin';
    updateBar(0);
    document.getElementById('fqPct').textContent         = '0%';
    document.getElementById('fqBrokenCount').textContent = '0';
    document.getElementById('fqFixedCount').textContent  = '0';
    document.getElementById('fqProcessed').textContent   = '0';
}
function updateBar(pct) {
    document.getElementById('fqBar').style.width = pct + '%';
    document.getElementById('fqPct').textContent = pct + '%';
}
</script>

<?php include '../includes/footer.php'; ?>
