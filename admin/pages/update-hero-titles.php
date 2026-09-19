<?php
/**
 * Update Hero Titles — Admin Dashboard
 * Replaces the prefix word in the H1 hero title of all generated pages
 * with a random pick from the 55-word power list. Body content is never changed.
 */
define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php'); exit;
}

$root      = dirname(dirname(dirname(__FILE__)));
$gen_dir   = $root . '/generated-pages/';
$all       = glob($gen_dir . '*.php') ?: [];
$total     = count(array_filter($all, fn($f) => basename($f) !== 'index.php'));
$prefixes  = require $root . '/config/hero-title-prefixes.php';

$page_title = 'Update Hero Titles';
include '../includes/header.php';
?>
<style>
.ht-page { padding: 0; }
.ht-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 28px 32px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px; border-left: 6px solid #6366f1; }
.ht-hero h1 { font-size: 26px; font-weight: 800; color: #1e293b; margin: 0 0 4px; }
.ht-hero p  { color: #64748b; font-size: 14px; margin: 0; }
.ht-explain { background: #f5f3ff; border: 1px solid #ddd6fe; border-left: 5px solid #6366f1; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; }
.ht-explain strong { color: #3730a3; font-size: 14px; display: block; margin-bottom: 6px; }
.ht-explain p  { color: #312e81; font-size: 13px; margin: 3px 0 0; line-height: 1.6; }
.ht-words { background: white; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.07); padding: 20px 24px; margin-bottom: 20px; }
.ht-words h3 { font-size: 14px; font-weight: 700; color: #374151; margin: 0 0 12px; }
.ht-word-cloud { display: flex; flex-wrap: wrap; gap: 8px; }
.ht-word { background: #e0e7ff; color: #4338ca; padding: 4px 11px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.ht-word-nets-excl  { background: #fee2e2 !important; color: #b91c1c !important; }
.ht-word-metal-excl { background: #fef9c3 !important; color: #92400e !important; }
.ht-word-legend { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 14px; padding-top: 12px; border-top: 1px solid #e0e7ff; }
.ht-legend-item { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #475569; }
.ht-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.07); overflow: hidden; margin-bottom: 20px; }
.ht-stat  { padding: 22px 20px; text-align: center; position: relative; }
.ht-stat + .ht-stat::before { content:''; position:absolute; left:0; top:20%; bottom:20%; width:1px; background:#e2e8f0; }
.ht-stat-val  { font-size: 32px; font-weight: 800; color: #1e293b; }
.ht-stat-lbl  { font-size: 12px; color: #64748b; font-weight: 600; margin-top: 2px; }
.ht-stat.yellow .ht-stat-val { color: #f59e0b; }
.ht-stat.green  .ht-stat-val { color: #10b981; }
.ht-stat.indigo .ht-stat-val { color: #6366f1; }
.ht-actions { background: white; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.07); padding: 24px 28px; margin-bottom: 20px; }
.ht-actions h2 { font-size: 17px; font-weight: 800; color: #1e293b; margin: 0 0 18px; }
.ht-btn-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
.ht-btn { padding: 12px 24px; border: none; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: opacity .2s; }
.ht-btn:hover { opacity: .87; }
.ht-btn.scan   { background: #6366f1; color: white; }
.ht-btn.update { background: linear-gradient(135deg,#6366f1,#8b5cf6); color: white; }
.ht-btn.resume { background: #f59e0b; color: white; }
.ht-btn:disabled { opacity: .5; cursor: not-allowed; }
#htDash { border-radius: 14px; overflow: hidden; border: 1.5px solid #ddd6fe; background: white; margin-top: 8px; }
.ht-dash-hdr { background: linear-gradient(135deg,#6366f1,#8b5cf6); padding: 12px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
.ht-dash-hdr-title { color: white; font-weight: 800; font-size: 14px; }
.ht-dash-hdr-sub   { color: rgba(255,255,255,.8); font-size: 12px; }
.ht-dash-prog { padding: 14px 20px; border-bottom: 1px solid #f5f3ff; }
.ht-dash-bar-wrap { height: 12px; background: #e2e8f0; border-radius: 6px; overflow: hidden; margin: 8px 0; }
.ht-dash-bar { height: 100%; width: 0%; background: linear-gradient(90deg,#6366f1,#a855f7); border-radius: 6px; transition: width .5s ease; }
.ht-dash-stats { display: flex; gap: 20px; flex-wrap: wrap; font-size: 12px; color: #64748b; }
.ht-dash-cur { padding: 10px 20px; background: #f5f3ff; border-bottom: 1px solid #ddd6fe; display: flex; align-items: center; gap: 10px; font-size: 13px; color: #3730a3; }
.ht-dash-log-wrap { padding: 12px 20px; }
.ht-dash-log { display: flex; flex-direction: column; gap: 5px; max-height: 180px; overflow-y: auto; }
.ht-results { background: white; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.07); padding: 20px 24px; }
.ht-results h2 { font-size: 16px; font-weight: 800; color: #1e293b; margin: 0 0 14px; }
</style>

<div class="ht-page">

<!-- Hero -->
<div class="ht-hero">
    <div>
        <h1><i class="fas fa-heading" style="color:#6366f1;margin-right:8px;"></i>Update Hero Titles</h1>
        <p>Replaces the prefix word (e.g. "Professional") in the H1 hero title of all generated pages with a random word from the 55-word power list — no content changed</p>
    </div>
    <div style="background:#f5f3ff;border:1.5px solid #ddd6fe;border-radius:12px;padding:12px 18px;text-align:center;">
        <div style="font-size:24px;font-weight:800;color:#4f46e5;"><?php echo number_format($total); ?></div>
        <div style="font-size:11px;color:#3730a3;font-weight:600;">Service Pages</div>
    </div>
</div>

<!-- Explanation -->
<div class="ht-explain">
    <strong><i class="fas fa-info-circle"></i> What This Does</strong>
    <p>All existing generated pages currently start with the same prefix word (e.g. "Professional") in their H1 hero title.
    This makes every page look identical to search engines, hurting SEO diversity.</p>
    <p style="margin-top:6px;"><strong>Fix:</strong> Replaces the first word of the H1 heading and the subtitle on each page with a random pick from
    55 varied power words (Trusted, Expert, Premium, Top-Rated, etc.). Creates <code>.title.bak</code> backup before every write.
    Page body content is never changed.</p>
    <p style="margin-top:8px;background:#fef3c7;border-radius:8px;padding:8px 12px;border-left:4px solid #f59e0b;"><strong style="color:#92400e;"><i class="fas fa-shield-alt"></i> Category Rules:</strong>
    <span style="color:#78350f;"> &nbsp;Nets (Pigeon/Bird/Safety/Sports) — <strong>Anti-Rust, Ultra-Strong, ISO-Certified</strong> are never used. &nbsp;|&nbsp; Metal (Invisible Grills/Cloth Hangers) — <strong>UV-Protected, Virgin-Quality, Safety-Tested, Eco-Friendly</strong> are never used.</span></p>
</div>

<!-- 55-word cloud -->
<div class="ht-words">
    <h3><i class="fas fa-font" style="color:#6366f1;margin-right:6px;"></i>55 Power Words — Category-Aware Usage Rules</h3>
    <div class="ht-word-cloud">
        <?php
        $nets_excluded_ui  = ['Anti-Rust','Ultra-Strong','ISO-Certified'];
        $metal_excluded_ui = ['UV-Protected','Virgin-Quality','Safety-Tested','Eco-Friendly'];
        foreach ($prefixes as $p):
            $isNetsExcl  = in_array($p, $nets_excluded_ui,  true);
            $isMetalExcl = in_array($p, $metal_excluded_ui, true);
            if ($isNetsExcl):
        ?><span class="ht-word ht-word-nets-excl" title="NOT used for Pigeon/Bird/Safety/Sports Nets"><?php echo htmlspecialchars($p); ?> <i class="fas fa-ban"></i></span>
        <?php elseif ($isMetalExcl): ?><span class="ht-word ht-word-metal-excl" title="NOT used for Invisible Grills &amp; Cloth Hangers"><?php echo htmlspecialchars($p); ?> <i class="fas fa-ban"></i></span>
        <?php else: ?><span class="ht-word"><?php echo htmlspecialchars($p); ?></span>
        <?php endif; endforeach; ?>
    </div>
    <div class="ht-word-legend">
        <span class="ht-legend-item"><span class="ht-word ht-word-nets-excl" style="pointer-events:none;">Word <i class="fas fa-ban"></i></span> Not used for Nets (Pigeon / Bird / Safety / Sports)</span>
        <span class="ht-legend-item"><span class="ht-word ht-word-metal-excl" style="pointer-events:none;">Word <i class="fas fa-ban"></i></span> Not used for Metal (Invisible Grills / Cloth Hangers)</span>
        <span class="ht-legend-item"><span class="ht-word" style="pointer-events:none;">Word</span> Used for all categories</span>
    </div>
</div>

<!-- Stats row -->
<div class="ht-stats">
    <div class="ht-stat indigo">
        <div class="ht-stat-val" id="statTotal"><?php echo number_format($total); ?></div>
        <div class="ht-stat-lbl">Total Pages</div>
    </div>
    <div class="ht-stat yellow">
        <div class="ht-stat-val" id="statReplaceable">—</div>
        <div class="ht-stat-lbl">Titles to Update</div>
    </div>
    <div class="ht-stat green">
        <div class="ht-stat-val" id="statUpdated">0</div>
        <div class="ht-stat-lbl">Updated This Session</div>
    </div>
    <div class="ht-stat">
        <div class="ht-stat-val" id="statVaried">—</div>
        <div class="ht-stat-lbl">Already Varied</div>
    </div>
</div>

<!-- Actions -->
<div class="ht-actions">
    <h2><i class="fas fa-tools" style="color:#6366f1;margin-right:8px;"></i>Actions</h2>
    <div class="ht-btn-row">
        <button class="ht-btn scan" id="btnScan" onclick="startScan()">
            <i class="fas fa-search"></i> Scan All Pages
        </button>
        <button class="ht-btn update" id="btnUpdate" onclick="startUpdate()" disabled>
            <i class="fas fa-magic"></i> Update All Titles
        </button>
        <button class="ht-btn resume" id="btnResume" onclick="resumeUpdate()" style="display:none;">
            <i class="fas fa-redo"></i> Resume
        </button>
    </div>

    <div id="htDash" style="display:none;">
        <div class="ht-dash-hdr">
            <div class="ht-dash-hdr-title"><i class="fas fa-cog fa-spin" id="htSpinIcon"></i>&nbsp;<span id="htHdrTitle">Starting...</span></div>
            <div class="ht-dash-hdr-sub" id="htHdrSub">Preparing...</div>
        </div>
        <div class="ht-dash-prog">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="font-size:12px;color:#64748b;font-weight:600;">Progress</span>
                <span style="font-size:13px;font-weight:800;color:#6366f1;" id="htPct">0%</span>
            </div>
            <div class="ht-dash-bar-wrap"><div class="ht-dash-bar" id="htBar"></div></div>
            <div class="ht-dash-stats">
                <span><i class="fas fa-check-circle" style="color:#10b981;"></i> Updated: <strong id="htUpdatedCount">0</strong></span>
                <span><i class="fas fa-forward" style="color:#94a3b8;"></i> Skipped: <strong id="htSkippedCount">0</strong></span>
                <span><i class="fas fa-file-alt" style="color:#6366f1;"></i> Done: <strong id="htProcessed">0</strong> / <?php echo number_format($total); ?></span>
            </div>
        </div>
        <div class="ht-dash-cur" id="htCurBatch">
            <i class="fas fa-spinner fa-spin" style="color:#6366f1;"></i>
            <span>Initializing...</span>
        </div>
        <div class="ht-dash-log-wrap">
            <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px;">Batch Log</div>
            <div class="ht-dash-log" id="htLog"></div>
        </div>
    </div>
</div>

<!-- Results -->
<div class="ht-results" id="htResults" style="display:none;">
    <h2 id="htResultsTitle"><i class="fas fa-list" style="color:#6366f1;margin-right:8px;"></i>Results</h2>
    <div id="htResultsContent"></div>
</div>

</div><!-- /ht-page -->

<script>
const totalPages = <?php echo $total; ?>;
let totalReplaceable = 0, totalUpdated = 0;
let updateOffset = 0, updateRetries = 0;
const maxRetries = 3;

/* ── Scan ─────────────────────────────────────────────────────────────── */
function startScan() {
    totalReplaceable = 0;
    document.getElementById('btnScan').disabled   = true;
    document.getElementById('btnUpdate').disabled = true;
    document.getElementById('btnResume').style.display = 'none';
    document.getElementById('htResults').style.display = 'none';
    document.getElementById('statReplaceable').textContent = '…';
    document.getElementById('statVaried').textContent      = '…';
    showDash();
    document.getElementById('htHdrTitle').textContent = 'Scanning all ' + totalPages.toLocaleString() + ' pages…';
    document.getElementById('htHdrSub').textContent   = 'Detecting prefix words in H1 titles…';
    document.getElementById('htCurBatch').innerHTML   = '<i class="fas fa-spinner fa-spin" style="color:#6366f1;font-size:14px;"></i> <span>Reading H1 hero titles on every generated page…</span>';
    updateBar(20);

    fetch('../api/update-hero-titles.php?action=scan')
        .then(r => r.json())
        .then(d => {
            if (!d.success) { scanError('API error: ' + d.message); return; }
            totalReplaceable = d.replaceable;

            document.getElementById('statReplaceable').textContent = totalReplaceable.toLocaleString();
            document.getElementById('statVaried').textContent      = (d.already_varied || 0).toLocaleString();
            updateBar(100);

            document.getElementById('htSpinIcon').className   = 'fas fa-check-circle';
            document.getElementById('htHdrTitle').textContent = 'Scan complete — ' + totalReplaceable.toLocaleString() + ' titles ready to update';
            document.getElementById('htHdrSub').textContent   = d.total.toLocaleString() + ' pages scanned';
            document.getElementById('htProcessed').textContent = d.total.toLocaleString();
            document.getElementById('btnScan').disabled = false;

            if (totalReplaceable > 0) {
                document.getElementById('htCurBatch').innerHTML = '<i class="fas fa-check-circle" style="color:#10b981;font-size:15px;"></i> <span style="color:#065f46;font-weight:700;">Scan finished — ' + totalReplaceable.toLocaleString() + ' pages ready for title update</span>';
                document.getElementById('btnUpdate').disabled = false;
            } else {
                document.getElementById('htCurBatch').innerHTML = '<i class="fas fa-check-circle" style="color:#10b981;font-size:15px;"></i> <span style="color:#065f46;font-weight:700;">All pages already have varied titles!</span>';
                const rs = document.getElementById('htResults');
                rs.style.display = 'block';
                document.getElementById('htResultsTitle').innerHTML = '<i class="fas fa-check-circle" style="color:#10b981;margin-right:8px;"></i>All Titles Already Varied';
                document.getElementById('htResultsContent').innerHTML = '<div style="padding:20px;background:#f0fdf4;border-radius:10px;color:#065f46;font-weight:700;">✅ All ' + totalPages.toLocaleString() + ' pages already have varied hero title prefixes. No updates needed!</div>';
            }
        })
        .catch(e => scanError(e.message));
}

function scanError(msg) {
    document.getElementById('btnScan').disabled = false;
    document.getElementById('htCurBatch').innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i> <span style="color:#ef4444;">Error: ' + msg + '</span> <button onclick="startScan()" style="margin-left:10px;padding:3px 10px;background:#6366f1;color:white;border:none;border-radius:5px;cursor:pointer;font-size:12px;">Retry</button>';
}

/* ── Update All ───────────────────────────────────────────────────────── */
function startUpdate() {
    GCMAlert.confirm(
        'Update hero titles on all ' + totalReplaceable.toLocaleString() + ' pages? Each H1 prefix will be replaced with a random power word. Backups (.title.bak) will be created before every write.',
        'Confirm Update All',
        function() {
            totalUpdated  = 0;
            updateOffset  = 0;
            updateRetries = 0;
            document.getElementById('btnUpdate').disabled = true;
            document.getElementById('btnScan').disabled   = true;
            document.getElementById('btnResume').style.display = 'none';
            document.getElementById('statUpdated').textContent = '0';
            document.getElementById('htLog').innerHTML = '';
            document.getElementById('htResults').style.display = 'none';
            showDash();
            document.getElementById('htHdrTitle').textContent = 'Updating hero title prefixes…';
            document.getElementById('htHdrSub').textContent   = 'Starting…';
            nextUpdateBatch();
        }
    );
}

function resumeUpdate() {
    updateRetries = 0;
    document.getElementById('btnResume').style.display = 'none';
    document.getElementById('btnScan').disabled = true;
    showDash();
    nextUpdateBatch();
}

function nextUpdateBatch() {
    const batchSize = 20;
    const batchNum  = Math.floor(updateOffset / batchSize) + 1;
    const start     = updateOffset + 1;
    const end       = Math.min(updateOffset + batchSize, totalPages);

    document.getElementById('htHdrSub').textContent = updateOffset.toLocaleString() + ' / ' + totalPages.toLocaleString() + ' pages';
    document.getElementById('htCurBatch').innerHTML = '<i class="fas fa-spinner fa-spin" style="color:#6366f1;font-size:14px;"></i> <span>Updating batch ' + batchNum + ' — pages ' + start.toLocaleString() + ' to ' + end.toLocaleString() + '</span>';

    const fd = new FormData();
    fd.append('action', 'update');
    fd.append('offset', updateOffset);
    fd.append('limit',  batchSize);

    fetch('../api/update-hero-titles.php', { method: 'POST', body: fd })
        .then(r => r.text())
        .then(function(text) {
            let d;
            try { d = JSON.parse(text); }
            catch(e) { handleUpdateRetry('Server error (non-JSON response)'); return; }

            updateRetries = 0;
            if (!d.success) { handleUpdateRetry('API: ' + (d.message || 'unknown error')); return; }

            totalUpdated += d.updated;
            document.getElementById('statUpdated').textContent    = totalUpdated.toLocaleString();
            document.getElementById('htUpdatedCount').textContent = totalUpdated.toLocaleString();
            document.getElementById('htSkippedCount').textContent = (parseInt(document.getElementById('htSkippedCount').textContent || '0') + d.skipped).toLocaleString();
            document.getElementById('htProcessed').textContent    = d.processed.toLocaleString();

            const pct = Math.round((d.processed / d.total) * 100);
            updateBar(pct);
            document.getElementById('htHdrSub').textContent = d.processed.toLocaleString() + ' / ' + d.total.toLocaleString() + ' pages — ' + pct + '% done';

            const log  = document.getElementById('htLog');
            const item = document.createElement('div');
            item.style.cssText = 'display:flex;align-items:center;gap:8px;padding:5px 10px;background:#f5f3ff;border-radius:7px;border-left:3px solid #6366f1;font-size:12px;';
            item.innerHTML = '<i class="fas fa-check" style="color:#6366f1;flex-shrink:0;"></i>'
                + '<span style="color:#3730a3;font-weight:600;">Batch ' + batchNum + '</span>'
                + '<span style="color:#64748b;">pp. ' + start.toLocaleString() + '–' + end.toLocaleString()
                + ' &bull; ' + d.updated + ' updated &bull; ' + d.skipped + ' skipped'
                + (d.errors && d.errors.length ? ' &bull; ' + d.errors.length + ' errors' : '') + '</span>'
                + '<span style="margin-left:auto;color:#6366f1;font-weight:700;">' + pct + '%</span>';
            log.prepend(item);

            updateOffset = d.processed;
            if (!d.completed) {
                setTimeout(nextUpdateBatch, 800);
            } else {
                onUpdateComplete();
            }
        })
        .catch(e => handleUpdateRetry(e.message));
}

function handleUpdateRetry(msg) {
    if (updateRetries < maxRetries) {
        updateRetries++;
        const wait = updateRetries * 3;
        document.getElementById('htCurBatch').innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#f59e0b;"></i> <span style="color:#92400e;">⚠ ' + msg + ' — retrying in ' + wait + 's (attempt ' + updateRetries + '/' + maxRetries + ')…</span>';
        setTimeout(nextUpdateBatch, updateRetries * 3000);
    } else {
        document.getElementById('htCurBatch').innerHTML = '<i class="fas fa-times-circle" style="color:#ef4444;"></i> <span style="color:#ef4444;">Too many errors at offset ' + updateOffset + '. Click Resume to continue from here.</span>';
        document.getElementById('btnResume').style.display = 'inline-flex';
        document.getElementById('btnScan').disabled = false;
    }
}

function onUpdateComplete() {
    document.getElementById('htSpinIcon').className   = 'fas fa-check-circle';
    document.getElementById('htHdrTitle').textContent = 'Update complete — ' + totalUpdated.toLocaleString() + ' pages updated';
    document.getElementById('htCurBatch').innerHTML   = '<i class="fas fa-trophy" style="color:#f59e0b;font-size:15px;"></i> <span style="color:#065f46;font-weight:700;">All done — ' + totalUpdated.toLocaleString() + ' hero titles updated with varied power words!</span>';
    document.getElementById('btnScan').disabled = false;
    document.getElementById('statReplaceable').textContent = '0';

    const rs = document.getElementById('htResults');
    rs.style.display = 'block';
    document.getElementById('htResultsTitle').innerHTML = '<i class="fas fa-check-circle" style="color:#10b981;margin-right:8px;"></i>Update Complete';
    document.getElementById('htResultsContent').innerHTML =
        '<div style="padding:20px;background:#f0fdf4;border-radius:10px;border:1.5px solid #86efac;">'
        + '<div style="font-weight:800;color:#15803d;font-size:15px;margin-bottom:12px;"><i class="fas fa-check-circle"></i> Hero title update complete</div>'
        + '<div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:12px;">'
        + '<div style="background:white;padding:10px 16px;border-radius:8px;font-size:13px;color:#065f46;"><strong style="font-size:20px;display:block;color:#10b981;">' + totalUpdated.toLocaleString() + '</strong>Pages updated</div>'
        + '</div>'
        + '<div style="font-size:13px;color:#15803d;background:white;padding:10px 14px;border-radius:8px;"><i class="fas fa-shield-alt" style="color:#10b981;margin-right:6px;"></i> All updates used <code>.title.bak</code> backups. Page body content was never changed.</div>'
        + '</div>';
}

/* ── Helpers ──────────────────────────────────────────────────────────── */
function showDash() {
    document.getElementById('htDash').style.display = 'block';
    document.getElementById('htSpinIcon').className = 'fas fa-cog fa-spin';
    updateBar(0);
    document.getElementById('htPct').textContent          = '0%';
    document.getElementById('htUpdatedCount').textContent = '0';
    document.getElementById('htSkippedCount').textContent = '0';
    document.getElementById('htProcessed').textContent    = '0';
}
function updateBar(pct) {
    document.getElementById('htBar').style.width = pct + '%';
    document.getElementById('htPct').textContent = pct + '%';
}
</script>

<?php include '../includes/footer.php'; ?>
