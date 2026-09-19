<?php
/**
 * URL Health Checker
 * Scans URLs in batches via admin/api/url-health-checker.php
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

// Start session with proper settings
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

$page_title = 'URL Health Checker';
include '../includes/header.php';
?>

<style>
.uhc-wrap{max-width:1200px;margin:0 auto;padding:20px;}
.uhc-hero{background:white;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,.07);padding:22px 26px;margin-bottom:18px;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;border-left:6px solid #3b82f6;}
.uhc-hero h1{margin:0 0 6px;font-size:22px;font-weight:800;color:#0f172a;}
.uhc-hero p{margin:0;color:#475569;font-size:13.5px;line-height:1.55;max-width:820px;}
.uhc-card{background:white;border-radius:18px;box-shadow:0 4px 20px rgba(0,0,0,.07);margin-bottom:18px;overflow:hidden;}
.uhc-card-h{padding:16px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:10px;}
.uhc-card-h h2{margin:0;font-size:15px;font-weight:800;color:#0f172a;}
.uhc-card-b{padding:18px 22px;}
.uhc-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;}
@media(max-width:980px){.uhc-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:560px){.uhc-grid{grid-template-columns:1fr;} }
.uhc-stat{border:1px solid #e2e8f0;border-radius:14px;padding:14px 14px;}
.uhc-stat .v{font-size:22px;font-weight:900;color:#0f172a;}
.uhc-stat .l{font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-top:4px;}
.uhc-controls{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;}
.uhc-field label{display:block;font-size:12px;font-weight:800;color:#475569;margin-bottom:6px;}
.uhc-field select,.uhc-field input{padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:14px;background:#fafafa;min-width:220px;}
.uhc-btn{border:none;border-radius:10px;padding:10px 16px;font-size:13.5px;font-weight:900;cursor:pointer;display:inline-flex;align-items:center;gap:8px;}
.uhc-btn-primary{background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;box-shadow:0 6px 18px rgba(37,99,235,.25);}
.uhc-btn-gray{background:#f1f5f9;color:#0f172a;border:1px solid #e2e8f0;}
.uhc-progress{height:10px;background:#e2e8f0;border-radius:10px;overflow:hidden;}
.uhc-progress > div{height:100%;width:0%;background:linear-gradient(135deg,#10b981,#059669);transition:width .25s ease;}
.uhc-table{width:100%;border-collapse:collapse;}
.uhc-table th,.uhc-table td{padding:10px 10px;border-bottom:1px solid #eef2f7;text-align:left;font-size:13px;vertical-align:top;}
.uhc-table th{font-size:12px;text-transform:uppercase;letter-spacing:.35px;color:#64748b;font-weight:900;}
.uhc-pill{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:900;}
.p200{background:#d1fae5;color:#065f46;}
.p3xx{background:#dbeafe;color:#1e40af;}
.p4xx{background:#fee2e2;color:#991b1b;}
.p5xx{background:#fde68a;color:#92400e;}
.p0{background:#f1f5f9;color:#334155;}
.uhc-small{font-size:12px;color:#64748b;word-break:break-all;}
</style>

<div class="uhc-wrap">
    <div class="uhc-hero">
        <div>
            <h1><i class="fas fa-stethoscope" style="color:#2563eb;"></i> URL Health Checker</h1>
            <p>
                This scans your real URLs (static + generated pages) and reports HTTP status codes.
                Use this to find 404s, redirects, and server errors that cause Google Search Console to show many "known but not indexed" URLs.
            </p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <a class="uhc-btn uhc-btn-gray" href="seo-dashboard.php"><i class="fas fa-arrow-left"></i> Back to SEO Dashboard</a>
        </div>
    </div>

    <div class="uhc-card">
        <div class="uhc-card-h"><i class="fas fa-sliders-h" style="color:#64748b"></i><h2>Scan Settings</h2></div>
        <div class="uhc-card-b">
            <div class="uhc-controls">
                <div class="uhc-field">
                    <label>URL Mode</label>
                    <select id="mode">
                        <option value="clean">Clean URLs (recommended)</option>
                        <option value="php">.php URLs only</option>
                        <option value="both">Both (detect duplicates/redirects)</option>
                    </select>
                </div>
                <div class="uhc-field">
                    <label>Batch Size</label>
                    <input id="batch" type="number" value="200" min="10" max="800" />
                </div>
                <div class="uhc-field">
                    <label>Include Static Pages</label>
                    <select id="static">
                        <option value="1" selected>Yes</option>
                        <option value="0">No (generated only)</option>
                    </select>
                </div>
                <button class="uhc-btn uhc-btn-primary" id="startBtn" onclick="startScan()"><i class="fas fa-play"></i> Start Scan</button>
                <button class="uhc-btn uhc-btn-gray" id="stopBtn" onclick="stopScan()" disabled><i class="fas fa-stop"></i> Stop</button>
                <button class="uhc-btn uhc-btn-gray" onclick="resetScan()"><i class="fas fa-undo"></i> Reset</button>
            </div>

            <div style="margin-top:14px;">
                <div class="uhc-progress"><div id="bar"></div></div>
                <div id="status" style="margin-top:10px;font-size:13px;font-weight:800;color:#334155;">Idle</div>
            </div>
        </div>
    </div>

    <div class="uhc-card">
        <div class="uhc-card-h"><i class="fas fa-chart-pie" style="color:#64748b"></i><h2>Totals (This Run)</h2></div>
        <div class="uhc-card-b">
            <div class="uhc-grid">
                <div class="uhc-stat"><div class="v" id="t_scanned">0</div><div class="l">Scanned</div></div>
                <div class="uhc-stat"><div class="v" id="t_200">0</div><div class="l">HTTP 200</div></div>
                <div class="uhc-stat"><div class="v" id="t_3xx">0</div><div class="l">HTTP 3xx</div></div>
                <div class="uhc-stat"><div class="v" id="t_4xx">0</div><div class="l">HTTP 4xx</div></div>
                <div class="uhc-stat"><div class="v" id="t_5xx">0</div><div class="l">HTTP 5xx</div></div>
                <div class="uhc-stat"><div class="v" id="t_other">0</div><div class="l">Other/Network</div></div>
                <div class="uhc-stat"><div class="v" id="t_total">0</div><div class="l">Total URLs</div></div>
                <div class="uhc-stat"><div class="v" id="t_mode">clean</div><div class="l">Mode</div></div>
            </div>
        </div>
    </div>

    <div class="uhc-card">
        <div class="uhc-card-h"><i class="fas fa-list" style="color:#64748b"></i><h2>Latest Results (last 200)</h2></div>
        <div class="uhc-card-b" style="padding:0;">
            <div style="max-height:520px;overflow:auto;">
                <table class="uhc-table">
                    <thead>
                        <tr>
                            <th style="width:100px;">Status</th>
                            <th style="width:120px;">Source</th>
                            <th>URL</th>
                            <th style="width:90px;">Hops</th>
                            <th>Final URL / Error</th>
                        </tr>
                    </thead>
                    <tbody id="rows"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let running = false;
let stopRequested = false;
let offset = 0;
let totals = {scanned:0, http_200:0, http_3xx:0, http_4xx:0, http_5xx:0, other:0};
let totalUrls = 0;

function pill(status){
    if (!status) return '<span class="uhc-pill p0">0</span>';
    if (status >= 200 && status < 300) return '<span class="uhc-pill p200">'+status+'</span>';
    if (status >= 300 && status < 400) return '<span class="uhc-pill p3xx">'+status+'</span>';
    if (status >= 400 && status < 500) return '<span class="uhc-pill p4xx">'+status+'</span>';
    if (status >= 500 && status < 600) return '<span class="uhc-pill p5xx">'+status+'</span>';
    return '<span class="uhc-pill p0">'+status+'</span>';
}

function setStatus(txt){
    document.getElementById('status').textContent = txt;
}

function renderTotals(){
    document.getElementById('t_scanned').textContent = totals.scanned;
    document.getElementById('t_200').textContent = totals.http_200;
    document.getElementById('t_3xx').textContent = totals.http_3xx;
    document.getElementById('t_4xx').textContent = totals.http_4xx;
    document.getElementById('t_5xx').textContent = totals.http_5xx;
    document.getElementById('t_other').textContent = totals.other;
    document.getElementById('t_total').textContent = totalUrls;
    document.getElementById('t_mode').textContent = document.getElementById('mode').value;

    const pct = totalUrls ? Math.min(100, Math.round((offset / totalUrls) * 100)) : 0;
    document.getElementById('bar').style.width = pct + '%';
}

function resetScan(){
    stopRequested = true;
    running = false;
    offset = 0;
    totals = {scanned:0, http_200:0, http_3xx:0, http_4xx:0, http_5xx:0, other:0};
    totalUrls = 0;
    document.getElementById('rows').innerHTML = '';
    document.getElementById('bar').style.width = '0%';
    setStatus('Idle');
    document.getElementById('startBtn').disabled = false;
    document.getElementById('stopBtn').disabled = true;
    renderTotals();
}

function stopScan(){
    stopRequested = true;
    setStatus('Stopping…');
}

async function startScan(){
    if (running) return;
    stopRequested = false;
    running = true;
    document.getElementById('startBtn').disabled = true;
    document.getElementById('stopBtn').disabled = false;

    offset = 0;
    totals = {scanned:0, http_200:0, http_3xx:0, http_4xx:0, http_5xx:0, other:0};
    totalUrls = 0;
    document.getElementById('rows').innerHTML = '';

    const mode = document.getElementById('mode').value;
    const batch = parseInt(document.getElementById('batch').value || '200', 10);
    const includeStatic = document.getElementById('static').value;

    setStatus('Starting scan…');
    renderTotals();

    while (!stopRequested) {
        const body = new URLSearchParams();
        body.append('action', 'scan');
        body.append('offset', String(offset));
        body.append('batch_size', String(batch));
        body.append('mode', mode);
        body.append('include_static', includeStatic);

        let json;
        try {
            const res = await fetch('../api/url-health-checker.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: body.toString()
            });
            json = await res.json();
        } catch (e) {
            setStatus('Network error: ' + (e.message || e));
            break;
        }

        if (!json || !json.success) {
            setStatus('Scan failed: ' + (json && json.message ? json.message : 'Unknown error'));
            break;
        }

        totalUrls = json.total || totalUrls;
        offset = json.next_offset || (offset + (json.results ? json.results.length : 0));

        const sum = json.summary || {};
        totals.scanned  += sum.scanned   || 0;
        totals.http_200 += sum.http_200  || 0;
        totals.http_3xx += sum.http_3xx  || 0;
        totals.http_4xx += sum.http_4xx  || 0;
        totals.http_5xx += sum.http_5xx  || 0;
        totals.other    += sum.other     || 0;

        const rows = (json.results || []).map(r => {
            const finalOrErr = r.error ? ('Error: ' + r.error) : (r.final_url || '');
            return '<tr>'
                + '<td>' + pill(r.status) + '</td>'
                + '<td><span class="uhc-small">' + (r.source || '') + '</span></td>'
                + '<td><div style="font-weight:900;color:#0f172a;">' + (r.path || '') + '</div><div class="uhc-small">' + (r.url || '') + '</div></td>'
                + '<td>' + (r.redirect_hops || 0) + '</td>'
                + '<td class="uhc-small">' + (finalOrErr || '') + '</td>'
                + '</tr>';
        }).join('');

        const tbody = document.getElementById('rows');
        tbody.insertAdjacentHTML('afterbegin', rows);

        // Keep only last ~200 rows
        while (tbody.children.length > 200) tbody.removeChild(tbody.lastElementChild);

        renderTotals();
        setStatus('Scanning… ' + offset + ' / ' + totalUrls);

        if (json.done) {
            setStatus('Done. Scanned ' + offset + ' URLs.');
            break;
        }
    }

    running = false;
    document.getElementById('startBtn').disabled = false;
    document.getElementById('stopBtn').disabled = true;

    if (stopRequested) setStatus('Stopped. Scanned ' + offset + ' URLs.');
    renderTotals();
}
</script>

<?php include '../includes/footer.php'; ?>
