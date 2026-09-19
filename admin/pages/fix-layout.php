<?php
/**
 * Fix Layout — Admin Dashboard
 * Scans all generated pages for broken two-column layout and repairs them.
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

$page_title = 'Fix Layout';
include '../includes/header.php';
?>
<style>
.fl-page { padding: 0; }
.fl-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 28px 32px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px; border-left: 6px solid #ef4444; }
.fl-hero h1 { font-size: 26px; font-weight: 800; color: #1e293b; margin: 0 0 4px; }
.fl-hero p  { color: #64748b; font-size: 14px; margin: 0; }
.fl-explain { background: #fff7ed; border: 1px solid #fed7aa; border-left: 5px solid #f97316; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; }
.fl-explain strong { color: #9a3412; font-size: 14px; display: block; margin-bottom: 6px; }
.fl-explain p  { color: #7c2d12; font-size: 13px; margin: 3px 0 0; line-height: 1.6; }
.fl-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.07); overflow: hidden; margin-bottom: 20px; }
.fl-stat  { padding: 22px 20px; text-align: center; position: relative; }
.fl-stat + .fl-stat::before { content:''; position:absolute; left:0; top:20%; bottom:20%; width:1px; background:#e2e8f0; }
.fl-stat-val  { font-size: 32px; font-weight: 800; color: #1e293b; }
.fl-stat-lbl  { font-size: 12px; color: #64748b; font-weight: 600; margin-top: 2px; }
.fl-stat.red  .fl-stat-val { color: #ef4444; }
.fl-stat.green .fl-stat-val { color: #10b981; }
.fl-stat.blue  .fl-stat-val { color: #667eea; }
.fl-actions { background: white; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.07); padding: 24px 28px; margin-bottom: 20px; }
.fl-actions h2 { font-size: 17px; font-weight: 800; color: #1e293b; margin: 0 0 18px; }
.fl-btn-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
.fl-btn { padding: 12px 24px; border: none; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: opacity .2s; }
.fl-btn:hover { opacity: .87; }
.fl-btn.scan { background: #3b82f6; color: white; }
.fl-btn.fix  { background: #ef4444; color: white; }
.fl-btn.fix-all { background: linear-gradient(135deg,#ef4444,#dc2626); color: white; }
.fl-btn:disabled { opacity: .5; cursor: not-allowed; }
/* Progress dashboard */
#flDash { border-radius: 14px; overflow: hidden; border: 1.5px solid #fecaca; background: white; margin-top: 8px; }
.fl-dash-hdr { background: linear-gradient(135deg,#ef4444,#dc2626); padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
.fl-dash-hdr-title { color: white; font-weight: 800; font-size: 14px; }
.fl-dash-hdr-sub { color: rgba(255,255,255,.8); font-size: 12px; }
.fl-dash-prog { padding: 14px 20px; border-bottom: 1px solid #fef2f2; }
.fl-dash-bar-wrap { height: 12px; background: #e2e8f0; border-radius: 6px; overflow: hidden; margin: 8px 0; }
.fl-dash-bar { height: 100%; width: 0%; background: linear-gradient(90deg,#ef4444,#f97316); border-radius: 6px; transition: width .5s ease; }
.fl-dash-stats { display: flex; gap: 20px; flex-wrap: wrap; font-size: 12px; color: #64748b; }
.fl-dash-cur { padding: 10px 20px; background: #fff7ed; border-bottom: 1px solid #fed7aa; display: flex; align-items: center; gap: 10px; font-size: 13px; color: #7c2d12; }
.fl-dash-log-wrap { padding: 12px 20px; }
.fl-dash-log { display: flex; flex-direction: column; gap: 5px; max-height: 180px; overflow-y: auto; }
/* Results section */
.fl-results { background: white; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.07); padding: 20px 24px; }
.fl-results h2 { font-size: 16px; font-weight: 800; color: #1e293b; margin: 0 0 14px; }
.fl-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.fl-table th { background: #f8faff; padding: 10px 12px; text-align: left; font-weight: 700; color: #64748b; border-bottom: 2px solid #e2e8f0; }
.fl-table td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
.fl-table tr:hover td { background: #fafafa; }
.fl-badge { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.fl-badge.broken { background: #fee2e2; color: #dc2626; }
.fl-badge.fixed  { background: #d1fae5; color: #065f46; }
.fl-badge.clean  { background: #f0fdf4; color: #15803d; }
.fl-fix-one { padding: 4px 10px; background: #ef4444; color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; }
.fl-fix-one:hover { background: #dc2626; }
</style>

<div class="fl-page">

<!-- Hero -->
<div class="fl-hero">
    <div>
        <h1><i class="fas fa-wrench" style="color:#ef4444;margin-right:8px;"></i>Layout Repair Tool</h1>
        <p>Finds and fixes pages where the sidebar appears below instead of on the right — no content changed</p>
    </div>
    <div style="background:#fff7ed;border:1.5px solid #fed7aa;border-radius:12px;padding:12px 18px;text-align:center;">
        <div style="font-size:24px;font-weight:800;color:#ea580c;"><?php echo number_format($total); ?></div>
        <div style="font-size:11px;color:#9a3412;font-weight:600;">Service Pages</div>
    </div>
</div>

<!-- Explanation -->
<div class="fl-explain">
    <strong><i class="fas fa-info-circle"></i> Root Cause</strong>
    <p>The old AI content expander injected extra <code>&lt;/div&gt;</code> closing tags inside the main content area.
    These extra closes prematurely shut the <code>.gcm-grid</code> container, pushing <code>.gcm-sidebar</code> (the Get Free Quote form)
    outside the two-column grid — so it appears below all content instead of on the right side.</p>
    <p style="margin-top:6px;"><strong>Fix:</strong> Removes exactly the excess <code>&lt;/div&gt;</code> tags from the region between
    <code>.gcm-grid</code> and <code>.gcm-sidebar</code>. Creates <code>.layout.bak</code> backup before every write. Page text is never changed.</p>
</div>

<!-- Stats row -->
<div class="fl-stats">
    <div class="fl-stat blue">
        <div class="fl-stat-val" id="statTotal"><?php echo number_format($total); ?></div>
        <div class="fl-stat-lbl">Total Pages</div>
    </div>
    <div class="fl-stat red">
        <div class="fl-stat-val" id="statBroken">—</div>
        <div class="fl-stat-lbl">Broken Layout</div>
    </div>
    <div class="fl-stat green">
        <div class="fl-stat-val" id="statFixed">—</div>
        <div class="fl-stat-lbl">Fixed</div>
    </div>
    <div class="fl-stat">
        <div class="fl-stat-val" id="statClean">—</div>
        <div class="fl-stat-lbl">Already Correct</div>
    </div>
</div>

<!-- Actions -->
<div class="fl-actions">
    <h2><i class="fas fa-tools" style="color:#667eea;margin-right:8px;"></i>Actions</h2>
    <div class="fl-btn-row">
        <button class="fl-btn scan" id="btnScan" onclick="startScan()">
            <i class="fas fa-search"></i> Scan All Pages for Layout Issues
        </button>
        <button class="fl-btn fix-all" id="btnFixAll" onclick="startFixAll()" disabled>
            <i class="fas fa-wrench"></i> Fix All Broken Pages
        </button>
    </div>
    <div id="flDash" style="display:none;">
        <div class="fl-dash-hdr">
            <div class="fl-dash-hdr-title"><i class="fas fa-cog fa-spin" id="flSpinIcon"></i>&nbsp;<span id="flHdrTitle">Starting...</span></div>
            <div class="fl-dash-hdr-sub" id="flHdrSub">Preparing...</div>
        </div>
        <div class="fl-dash-prog">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="font-size:12px;color:#64748b;font-weight:600;">Progress</span>
                <span style="font-size:13px;font-weight:800;color:#ef4444;" id="flPct">0%</span>
            </div>
            <div class="fl-dash-bar-wrap"><div class="fl-dash-bar" id="flBar"></div></div>
            <div class="fl-dash-stats">
                <span><i class="fas fa-search" style="color:#3b82f6;"></i> Scanned: <strong id="flScanned">0</strong></span>
                <span><i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i> Broken: <strong id="flBrokenCount">0</strong></span>
                <span><i class="fas fa-check-circle" style="color:#10b981;"></i> Fixed: <strong id="flFixedCount">0</strong></span>
                <span><i class="fas fa-file-alt" style="color:#667eea;"></i> Done: <strong id="flProcessed">0</strong> / <?php echo $total; ?></span>
            </div>
        </div>
        <div class="fl-dash-cur" id="flCurBatch">
            <i class="fas fa-spinner fa-spin" style="color:#ef4444;"></i>
            <span>Initializing...</span>
        </div>
        <div class="fl-dash-log-wrap">
            <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px;">Batch Log</div>
            <div class="fl-dash-log" id="flLog"></div>
        </div>
    </div>
</div>

<!-- Results table -->
<div class="fl-results" id="resultsSection" style="display:none;">
    <h2 id="resultsTitle"><i class="fas fa-list" style="color:#ef4444;margin-right:8px;"></i>Scan Results</h2>
    <div id="resultsContent"></div>
</div>

</div><!-- /fl-page -->

<script>
const totalPages = <?php echo $total; ?>;
let currentOffset = 0;
let mode = 'scan'; // 'scan' | 'fix'
let allBroken = [];
let totalBroken = 0, totalFixed = 0, totalScanned = 0;

function startScan() {
    allBroken = [];
    totalBroken = 0; totalFixed = 0; totalScanned = 0;
    currentOffset = 0;
    mode = 'scan';
    document.getElementById('btnScan').disabled = true;
    document.getElementById('btnFixAll').disabled = true;
    document.getElementById('statBroken').textContent = '0';
    document.getElementById('statFixed').textContent = '0';
    document.getElementById('statClean').textContent = '0';
    document.getElementById('resultsSection').style.display = 'none';
    showDash();
    processBatch();
}

function startFixAll() {
    GCMAlert.confirm(
        'Fix all ' + totalBroken + ' broken pages? Only div nesting is changed — page content stays exactly the same. A .layout.bak backup is created before every write.',
        'Confirm Fix All',
        function() {
            allBroken = [];
            totalFixed = 0;
            currentOffset = 0;
            mode = 'fix';
            document.getElementById('btnFixAll').disabled = true;
            document.getElementById('statFixed').textContent = '0';
            document.getElementById('flLog').innerHTML = '';
            document.getElementById('resultsSection').style.display = 'none';
            showDash();
            processBatch();
        }
    );
}

function showDash() {
    const d = document.getElementById('flDash');
    d.style.display = 'block';
    updateDashBar(0);
}

function updateDashBar(pct) {
    document.getElementById('flBar').style.width = pct + '%';
    document.getElementById('flPct').textContent = pct + '%';
}

function processBatch() {
    const batchNum = Math.floor(currentOffset / 100) + 1;
    const start = currentOffset + 1;
    const end   = Math.min(currentOffset + 100, totalPages);

    const cb  = document.getElementById('flCurBatch');
    const hdr = document.getElementById('flHdrTitle');
    const verb = mode === 'scan' ? 'Scanning' : 'Fixing';
    cb.innerHTML = `<i class="fas fa-spinner fa-spin" style="color:#ef4444;font-size:14px;"></i> <span>${verb} batch ${batchNum} — pages ${start.toLocaleString()} to ${end.toLocaleString()}</span>`;
    if (hdr) hdr.textContent = `${verb} batch ${batchNum} of ${Math.ceil(totalPages/100)}...`;

    fetch('../api/fix-layout-broken.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=${mode}&batch_size=100&offset=${currentOffset}`
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            cb.innerHTML = `<i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i> <span style="color:#ef4444;">Error: ${data.message}</span> <button onclick="processBatch()" style="margin-left:10px;padding:3px 10px;background:#ef4444;color:white;border:none;border-radius:5px;cursor:pointer;font-size:12px;">Retry</button>`;
            return;
        }

        currentOffset = Math.min(currentOffset + 100, totalPages);
        const progress = Math.round((currentOffset / totalPages) * 100);

        if (mode === 'scan') {
            totalBroken  += data.broken_count;
            totalScanned += data.clean + data.broken_count;
            if (data.broken && data.broken.length > 0) {
                allBroken = allBroken.concat(data.broken);
            }
            document.getElementById('flBrokenCount').textContent = totalBroken.toLocaleString();
            document.getElementById('statBroken').textContent    = totalBroken.toLocaleString();
            document.getElementById('flScanned').textContent     = totalScanned.toLocaleString();
            document.getElementById('statClean').textContent     = (totalScanned - totalBroken).toLocaleString();
        } else {
            totalFixed += data.fixed || 0;
            document.getElementById('flFixedCount').textContent = totalFixed.toLocaleString();
            document.getElementById('statFixed').textContent    = totalFixed.toLocaleString();
        }

        document.getElementById('flProcessed').textContent = currentOffset.toLocaleString();
        updateDashBar(progress);

        const sub = document.getElementById('flHdrSub');
        if (sub) sub.textContent = `${currentOffset.toLocaleString()} / ${totalPages.toLocaleString()} pages — ${progress}% done`;

        // Add batch log entry
        const log = document.getElementById('flLog');
        const item = document.createElement('div');
        item.style.cssText = 'display:flex;align-items:center;gap:8px;padding:5px 10px;background:#fff7ed;border-radius:7px;border-left:3px solid #f97316;font-size:12px;';
        if (mode === 'scan') {
            item.innerHTML = `<i class="fas fa-check" style="color:#f97316;flex-shrink:0;"></i><span style="color:#7c2d12;font-weight:600;">Batch ${batchNum}</span><span style="color:#64748b;">pp. ${start.toLocaleString()}–${end.toLocaleString()} &bull; ${data.broken_count} broken &bull; ${data.clean} OK</span><span style="margin-left:auto;color:#ef4444;font-weight:700;">${progress}%</span>`;
        } else {
            item.style.background = '#f0fdf4';
            item.style.borderLeftColor = '#10b981';
            item.innerHTML = `<i class="fas fa-check" style="color:#10b981;flex-shrink:0;"></i><span style="color:#065f46;font-weight:600;">Batch ${batchNum}</span><span style="color:#64748b;">pp. ${start.toLocaleString()}–${end.toLocaleString()} &bull; ${data.fixed||0} fixed &bull; ${data.skipped||0} already OK</span><span style="margin-left:auto;color:#10b981;font-weight:700;">${progress}%</span>`;
        }
        log.prepend(item);

        if (currentOffset < totalPages) {
            setTimeout(() => processBatch(), 800);
        } else {
            onComplete();
        }
    })
    .catch(err => {
        cb.innerHTML = `<i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i> <span style="color:#ef4444;">Network error: ${err.message}</span> <button onclick="processBatch()" style="margin-left:10px;padding:3px 10px;background:#ef4444;color:white;border:none;border-radius:5px;cursor:pointer;font-size:12px;">Retry</button>`;
    });
}

function onComplete() {
    const icon = document.getElementById('flSpinIcon');
    if (icon) { icon.className = 'fas fa-check-circle'; }
    const hdr = document.getElementById('flHdrTitle');

    if (mode === 'scan') {
        if (hdr) hdr.textContent = `Scan complete — ${totalBroken} broken pages found`;
        document.getElementById('flCurBatch').innerHTML = `<i class="fas fa-check-circle" style="color:#10b981;font-size:15px;"></i> <span style="color:#065f46;font-weight:700;">Scan finished — ${totalBroken} pages need fixing</span>`;

        if (totalBroken > 0) {
            document.getElementById('btnFixAll').disabled = false;
            showScanResults();
        } else {
            document.getElementById('btnScan').disabled = false;
            const rs = document.getElementById('resultsSection');
            rs.style.display = 'block';
            document.getElementById('resultsTitle').innerHTML = '<i class="fas fa-check-circle" style="color:#10b981;margin-right:8px;"></i>All Pages Have Correct Layout';
            document.getElementById('resultsContent').innerHTML = '<div style="padding:20px;background:#f0fdf4;border-radius:10px;color:#065f46;font-weight:700;">✅ All ' + totalPages.toLocaleString() + ' pages have the sidebar in the correct position. No fixes needed!</div>';
        }
    } else {
        if (hdr) hdr.textContent = `Fix complete — ${totalFixed} pages repaired`;
        document.getElementById('flCurBatch').innerHTML = `<i class="fas fa-trophy" style="color:#f59e0b;font-size:15px;"></i> <span style="color:#065f46;font-weight:700;">All done — ${totalFixed} layout issues fixed, sidebar now shows on right!</span>`;
        document.getElementById('btnScan').disabled = false;
        showFixResults();
    }
}

function showScanResults() {
    const rs = document.getElementById('resultsSection');
    rs.style.display = 'block';
    document.getElementById('resultsTitle').innerHTML = `<i class="fas fa-exclamation-triangle" style="color:#ef4444;margin-right:8px;"></i>${totalBroken} Pages With Broken Layout`;

    let html = `<p style="color:#64748b;font-size:13px;margin:0 0 12px;">These pages have the sidebar appearing below main content. Click <strong>Fix All Broken Pages</strong> above to repair all at once, or fix individually below.</p>`;
    html += `<table class="fl-table"><thead><tr><th>#</th><th>Page Filename</th><th>Extra &lt;/div&gt; Count</th><th>Status</th><th>Action</th></tr></thead><tbody>`;

    allBroken.forEach((item, i) => {
        const slug = item.file.replace('.php', '');
        const url  = 'https://www.gcmsafetynets.in/' + slug;
        html += `<tr>
            <td style="color:#94a3b8;">${i + 1}</td>
            <td><a href="${url}" target="_blank" style="color:#667eea;font-weight:600;">${item.file}</a></td>
            <td><span class="fl-badge broken">${item.excess} extra &lt;/div&gt;</span></td>
            <td id="status-${i}"><span class="fl-badge broken">Broken</span></td>
            <td><button class="fl-fix-one" onclick="fixOne(${i}, '${item.file}')" id="fixbtn-${i}"><i class="fas fa-wrench"></i> Fix</button></td>
        </tr>`;
    });

    html += '</tbody></table>';
    document.getElementById('resultsContent').innerHTML = html;
}

function showFixResults() {
    const rs = document.getElementById('resultsSection');
    rs.style.display = 'block';
    document.getElementById('resultsTitle').innerHTML = `<i class="fas fa-check-circle" style="color:#10b981;margin-right:8px;"></i>Fix Complete`;
    document.getElementById('resultsContent').innerHTML = `
        <div style="padding:20px;background:#f0fdf4;border-radius:10px;border:1.5px solid #86efac;">
            <div style="font-weight:800;color:#15803d;font-size:15px;margin-bottom:12px;"><i class="fas fa-check-circle"></i> Layout repair complete</div>
            <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:12px;">
                <div style="background:white;padding:10px 16px;border-radius:8px;font-size:13px;color:#065f46;"><strong style="font-size:20px;display:block;color:#10b981;">${totalFixed}</strong>Pages fixed</div>
            </div>
            <div style="font-size:13px;color:#15803d;background:white;padding:10px 14px;border-radius:8px;"><i class="fas fa-shield-alt" style="color:#10b981;margin-right:6px;"></i> All fixes used <code>.layout.bak</code> backups. Page content was never changed.</div>
        </div>`;
}

function fixOne(idx, filename) {
    const btn = document.getElementById('fixbtn-' + idx);
    const stat = document.getElementById('status-' + idx);
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch('../api/fix-layout-broken.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=fix&file=${encodeURIComponent(filename)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.fixed > 0) {
            stat.innerHTML = '<span class="fl-badge fixed">Fixed ✓</span>';
            btn.style.display = 'none';
            totalFixed++;
            document.getElementById('statFixed').textContent = totalFixed;
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-wrench"></i> Retry';
        }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-wrench"></i> Retry'; });
}
</script>

<?php include '../includes/footer.php'; ?>
