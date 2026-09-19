<?php
/**
 * Live Visitor Analytics Dashboard
 * Real-time visitor tracking with map and details
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Live Visitors';
include '../includes/header.php';
?>

<style>
/* ── Live Visitors — Layout Repair Tool theme ───────────── */
.lv-page { padding: 0; }

/* Hero */
.lv-hero { background:#fff; border-radius:18px; box-shadow:0 4px 24px rgba(0,0,0,.07); padding:28px 32px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; gap:16px; border-left:6px solid #06b6d4; }
.lv-hero h1 { font-size:26px; font-weight:800; color:#1e293b; margin:0 0 4px; }
.lv-hero p  { color:#64748b; font-size:14px; margin:0; }
.lv-badge   { background:#ecfeff; border:1.5px solid #a5f3fc; border-radius:12px; padding:12px 18px; text-align:center; flex-shrink:0; }
.lv-badge-num  { font-size:28px; font-weight:800; color:#0e7490; }
.lv-badge-lbl  { font-size:11px; color:#0891b2; font-weight:600; }
.lv-badge-dot  { display:inline-block; width:8px; height:8px; border-radius:50%; background:#10b981; margin-right:4px; animation:lv-blink 1.4s infinite; }
@keyframes lv-blink { 0%,100%{opacity:1;} 50%{opacity:.25;} }

/* Explanation box */
.lv-explain { background:#ecfeff; border:1px solid #a5f3fc; border-left:5px solid #06b6d4; border-radius:12px; padding:16px 20px; margin-bottom:20px; }
.lv-explain strong { color:#0e7490; font-size:14px; display:block; margin-bottom:6px; }
.lv-explain p  { color:#164e63; font-size:13px; margin:3px 0 0; line-height:1.6; }
.lv-explain code { background:#cffafe; padding:1px 5px; border-radius:4px; font-size:12px; color:#0e7490; }

/* Stats row */
.lv-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:0; background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.07); overflow:hidden; margin-bottom:20px; }
.lv-stat  { padding:22px 20px; text-align:center; position:relative; }
.lv-stat + .lv-stat::before { content:''; position:absolute; left:0; top:20%; bottom:20%; width:1px; background:#e2e8f0; }
.lv-stat-val  { font-size:34px; font-weight:800; color:#1e293b; }
.lv-stat-lbl  { font-size:12px; color:#64748b; font-weight:600; margin-top:4px; display:flex; align-items:center; justify-content:center; gap:5px; }
.lv-stat.green  .lv-stat-val { color:#10b981; }
.lv-stat.blue   .lv-stat-val { color:#3b82f6; }
.lv-stat.amber  .lv-stat-val { color:#f59e0b; }
.lv-stat.indigo .lv-stat-val { color:#6366f1; }

/* Actions bar */
.lv-actions { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.07); padding:18px 24px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.lv-btn { padding:10px 20px; border:none; border-radius:10px; font-weight:700; font-size:13px; cursor:pointer; display:inline-flex; align-items:center; gap:8px; transition:opacity .2s; text-decoration:none; }
.lv-btn:hover { opacity:.85; }
.lv-btn-refresh { background:#06b6d4; color:#fff; }
.lv-btn-export  { background:#f8fafc; color:#475569; border:1.5px solid #e2e8f0; }
.lv-auto-lbl { font-size:12px; color:#94a3b8; display:flex; align-items:center; gap:6px; }
.lv-auto-dot { width:7px; height:7px; border-radius:50%; background:#10b981; animation:lv-blink 1.4s infinite; }

/* Cards */
.lv-card { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.07); margin-bottom:20px; overflow:hidden; }
.lv-card-hdr { padding:16px 22px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; }
.lv-card-hdr h2 { font-size:16px; font-weight:800; color:#1e293b; margin:0; }
.lv-card-hdr h2 i { color:#06b6d4; margin-right:8px; }
.lv-card-body { padding:16px 22px; }
.lv-count-badge { background:linear-gradient(135deg,#10b981,#059669); color:#fff; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; }

/* Two-column grid */
.lv-grid { display:grid; grid-template-columns:1.2fr 1fr; gap:20px; }
@media(max-width:900px){ .lv-grid { grid-template-columns:1fr; } }

/* Visitor rows */
.lv-visitor-row { padding:12px 14px; background:#f8fafc; border-radius:10px; margin-bottom:8px; border-left:3px solid #06b6d4; transition:all .2s; }
.lv-visitor-row:hover { background:#f1f5f9; transform:translateX(3px); }
.lv-vr-top { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
.lv-vr-loc { font-weight:700; font-size:14px; color:#1e293b; display:flex; align-items:center; gap:6px; }
.lv-vr-time { font-size:11px; color:#94a3b8; }
.lv-vr-details { display:flex; flex-wrap:wrap; gap:12px; }
.lv-vr-det { font-size:12px; color:#64748b; display:flex; align-items:center; gap:4px; }
.lv-vr-det i { color:#06b6d4; width:13px; }
.lv-vr-page { margin-top:6px; font-size:12px; color:#475569; background:#fff; padding:5px 10px; border-radius:6px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.lv-online-dot { width:8px; height:8px; border-radius:50%; background:#10b981; animation:lv-blink 1.4s infinite; display:inline-block; }

/* Top pages */
.lv-page-row { display:flex; align-items:center; gap:10px; padding:10px 12px; background:#f8fafc; border-radius:8px; margin-bottom:6px; transition:all .2s; }
.lv-page-row:hover { background:#f1f5f9; }
.lv-page-num { width:22px; height:22px; border-radius:50%; background:#e0f2fe; color:#0369a1; font-size:11px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.lv-page-url { flex:1; font-size:13px; font-weight:600; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.lv-page-stats { display:flex; gap:10px; font-size:12px; color:#64748b; flex-shrink:0; }

/* Country rows */
.lv-country-row { display:flex; align-items:center; gap:10px; padding:8px 12px; background:#f8fafc; border-radius:8px; margin-bottom:6px; }
.lv-country-flag { font-size:20px; }
.lv-country-name { flex:1; font-weight:600; font-size:13px; color:#1e293b; }
.lv-country-cnt  { font-size:12px; color:#64748b; }
.lv-country-bar  { width:60px; height:6px; background:#e2e8f0; border-radius:3px; overflow:hidden; }
.lv-country-fill { height:100%; background:linear-gradient(90deg,#06b6d4,#0891b2); border-radius:3px; }

/* Empty state */
.lv-empty { text-align:center; padding:40px 20px; color:#94a3b8; }
.lv-empty i { font-size:32px; display:block; margin-bottom:10px; }
.lv-empty p { font-size:13px; margin:0; }

/* Visitor scroll */
.lv-scroll { max-height:420px; overflow-y:auto; }
.lv-scroll::-webkit-scrollbar { width:4px; }
.lv-scroll::-webkit-scrollbar-thumb { background:#e2e8f0; border-radius:2px; }
</style>

<div class="lv-page">

<!-- Hero -->
<div class="lv-hero">
    <div>
        <h1><i class="fas fa-satellite-dish" style="color:#06b6d4;margin-right:8px;"></i>Live Visitor Monitor</h1>
        <p>Real visitors tracked via page load events — heartbeat every 25 s keeps counts accurate</p>
    </div>
    <div class="lv-badge">
        <div class="lv-badge-num"><span class="lv-badge-dot"></span><span id="lvHeroBadge">–</span></div>
        <div class="lv-badge-lbl">ONLINE NOW</div>
    </div>
</div>

<!-- Error banner -->
<div id="lvErrBanner" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-left:5px solid #ef4444;border-radius:10px;padding:12px 18px;margin-bottom:14px;font-size:13px;color:#b91c1c;font-weight:600;"></div>

<!-- Note: admin pages are not tracked -->
<div style="background:#fffbeb;border:1px solid #fde68a;border-left:5px solid #f59e0b;border-radius:10px;padding:12px 18px;margin-bottom:14px;font-size:13px;color:#92400e;">
    <strong><i class="fas fa-info-circle" style="margin-right:6px;"></i>Note:</strong> Visitor tracking fires only on <strong>public website pages</strong> — not on admin panel pages. To see yourself tracked, open <strong>any page of the public website</strong> (e.g. the homepage or a service page) in your browser.
</div>

<!-- How it works -->
<div class="lv-explain">
    <strong><i class="fas fa-info-circle"></i> How Accuracy Works</strong>
    <p><strong>Online Now</strong> = visitors whose <code>last_activity</code> is within the last 5 minutes (same formula as Dashboard).
    <strong>Visitors Today</strong> = unique sessions since midnight. <strong>Page Views</strong> = total page loads recorded today.
    Every page on the site fires <code>track-visitor.php</code> on load and <code>visitor-heartbeat.php</code> every 25 seconds — ensuring no visitor is missed or incorrectly shown as offline.</p>
    <p style="margin-top:6px;"><strong>Why was it wrong before?</strong> The old system used a stale <code>is_online</code> flag.
    Now both the Dashboard and this page use the same real-time 5-minute activity window — counts will always match.</p>
</div>

<!-- Stats -->
<div class="lv-stats">
    <div class="lv-stat green">
        <div class="lv-stat-val" id="lvOnline">–</div>
        <div class="lv-stat-lbl"><span class="lv-online-dot"></span> Online Now</div>
    </div>
    <div class="lv-stat blue">
        <div class="lv-stat-val" id="lvToday">–</div>
        <div class="lv-stat-lbl"><i class="fas fa-users" style="color:#3b82f6;"></i> Visitors Today</div>
    </div>
    <div class="lv-stat amber">
        <div class="lv-stat-val" id="lvPageViews">–</div>
        <div class="lv-stat-lbl"><i class="fas fa-eye" style="color:#f59e0b;"></i> Page Views Today</div>
    </div>
    <div class="lv-stat indigo">
        <div class="lv-stat-val" id="lvAvgTime">–</div>
        <div class="lv-stat-lbl"><i class="fas fa-clock" style="color:#6366f1;"></i> Avg. Time on Site</div>
    </div>
</div>

<!-- Actions -->
<div class="lv-actions">
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button class="lv-btn lv-btn-refresh" onclick="loadVisitors(true)">
            <i class="fas fa-sync-alt" id="lvRefreshIcon"></i> Refresh Now
        </button>
        <a href="../api/export-visitors.php" class="lv-btn lv-btn-export">
            <i class="fas fa-download"></i> Export CSV
        </a>
    </div>
    <div class="lv-auto-lbl">
        <span class="lv-auto-dot"></span>
        Auto-refresh every 30 s &nbsp;|&nbsp; Last update: <span id="lvLastUpdate">–</span>
    </div>
</div>

<!-- Active Visitors + Top Pages -->
<div class="lv-grid">
    <div class="lv-card">
        <div class="lv-card-hdr">
            <h2><i class="fas fa-users"></i> Active Visitors</h2>
            <span class="lv-count-badge" id="lvActiveBadge">0 online</span>
        </div>
        <div class="lv-card-body">
            <div class="lv-scroll" id="lvVisitorsList">
                <div class="lv-empty"><i class="fas fa-moon"></i><p>No visitors online right now</p></div>
            </div>
        </div>
    </div>

    <div class="lv-card">
        <div class="lv-card-hdr">
            <h2><i class="fas fa-fire"></i> Top Pages Today</h2>
        </div>
        <div class="lv-card-body">
            <div id="lvTopPages">
                <div class="lv-empty"><i class="fas fa-chart-bar"></i><p>No page data yet today</p></div>
            </div>
        </div>
    </div>
</div>

<!-- Country breakdown -->
<div class="lv-card">
    <div class="lv-card-hdr">
        <h2><i class="fas fa-globe"></i> Today's Visitors by Country</h2>
    </div>
    <div class="lv-card-body">
        <div id="lvCountries">
            <div class="lv-empty"><i class="fas fa-map"></i><p>No location data yet today</p></div>
        </div>
    </div>
</div>

</div><!-- .lv-page -->

<script>
let lvTimer;

function flag(code) {
    if (!code || code === 'XX') return '🌍';
    return String.fromCodePoint(...code.toUpperCase().split('').map(c => 127397 + c.charCodeAt()));
}

function fmtTime(s) {
    s = Math.round(s);
    if (s < 60)   return s + 's';
    if (s < 3600) return Math.floor(s/60) + 'm ' + (s%60) + 's';
    return Math.floor(s/3600) + 'h ' + Math.floor((s%3600)/60) + 'm';
}

function timeAgo(ts) {
    const d = Math.round((Date.now() - new Date(ts).getTime()) / 1000);
    if (d < 60)   return d + 's ago';
    if (d < 3600) return Math.floor(d/60) + 'm ago';
    return Math.floor(d/3600) + 'h ago';
}

function loadVisitors(manual) {
    if (manual) {
        const ic = document.getElementById('lvRefreshIcon');
        ic.className = 'fas fa-sync-alt fa-spin';
        setTimeout(() => ic.className = 'fas fa-sync-alt', 1200);
    }

    const errBanner = document.getElementById('lvErrBanner');
    fetch('../api/get-live-visitors.php')
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(data => {
            if (!data.success) {
                if (errBanner) { errBanner.style.display='block'; errBanner.textContent = '⚠ API error: ' + (data.message || 'unknown') + ' — retrying in 30s'; }
                return;
            }
            if (errBanner) { errBanner.style.display='none'; errBanner.textContent=''; }
            const s = data.stats;

            /* Stats */
            document.getElementById('lvHeroBadge').textContent  = s.online_now;
            document.getElementById('lvOnline').textContent     = s.online_now;
            document.getElementById('lvToday').textContent      = s.today;
            document.getElementById('lvPageViews').textContent  = s.total_page_views_today;
            document.getElementById('lvAvgTime').textContent    = fmtTime(s.avg_time_today);
            document.getElementById('lvActiveBadge').textContent = s.online_now + ' online';
            document.getElementById('lvLastUpdate').textContent =
                new Date().toLocaleTimeString('en-IN', {hour:'2-digit', minute:'2-digit', second:'2-digit'});

            /* Active visitors */
            const vList = document.getElementById('lvVisitorsList');
            if (!data.visitors || data.visitors.length === 0) {
                vList.innerHTML = '<div class="lv-empty"><i class="fas fa-moon"></i><p>No visitors active in the last 5 minutes</p></div>';
            } else {
                vList.innerHTML = data.visitors.map(v => `
                    <div class="lv-visitor-row">
                        <div class="lv-vr-top">
                            <div class="lv-vr-loc">
                                ${flag(v.country_code)}
                                ${v.city ? v.city + ', ' : ''}${v.country || 'Unknown'}
                            </div>
                            <div class="lv-vr-time"><span class="lv-online-dot"></span> ${timeAgo(v.last_activity)}</div>
                        </div>
                        <div class="lv-vr-details">
                            <span class="lv-vr-det"><i class="fas fa-network-wired"></i>${v.ip_address || '–'}</span>
                            <span class="lv-vr-det"><i class="fas fa-${v.device_type==='mobile'?'mobile-alt':'laptop'}"></i>${v.device_type}</span>
                            <span class="lv-vr-det"><i class="fas fa-globe"></i>${v.browser || '–'}</span>
                            <span class="lv-vr-det"><i class="fas fa-clock"></i>${fmtTime(v.total_time_spent)}</span>
                            <span class="lv-vr-det"><i class="fas fa-file-alt"></i>${v.page_views} pg</span>
                        </div>
                        ${v.search_keyword ? `<div style="margin-top:5px;font-size:12px;background:#fef3c7;padding:3px 10px;border-radius:6px;color:#92400e;"><i class="fas fa-search" style="margin-right:4px;color:#d97706;"></i>Keyword: <strong>${v.search_keyword}</strong></div>` : ''}
                        <div class="lv-vr-page"><i class="fas fa-sign-in-alt" style="color:#10b981;margin-right:4px;"></i>Entry: ${v.entry_page || '/'} &nbsp;<span style="color:#94a3b8;font-size:10px;">${new Date(v.first_visit).toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',second:'2-digit'})}</span></div>
                        <div class="lv-vr-page"><i class="fas fa-link" style="color:#06b6d4;margin-right:4px;"></i>Now: ${v.current_page || '/'}</div>
                    </div>
                `).join('');
            }

            /* Top pages */
            const pList = document.getElementById('lvTopPages');
            if (!data.top_pages || data.top_pages.length === 0) {
                pList.innerHTML = '<div class="lv-empty"><i class="fas fa-chart-bar"></i><p>No page data yet today</p></div>';
            } else {
                const maxViews = data.top_pages[0].views;
                pList.innerHTML = data.top_pages.map((p, i) => `
                    <div class="lv-page-row">
                        <div class="lv-page-num">${i+1}</div>
                        <div class="lv-page-url" title="${p.page_url}">${p.page_url}</div>
                        <div class="lv-page-stats">
                            <span><i class="fas fa-eye"></i> ${p.views}</span>
                            <span><i class="fas fa-users"></i> ${p.unique_visitors}</span>
                        </div>
                    </div>
                `).join('');
            }

            /* Countries */
            const cList = document.getElementById('lvCountries');
            if (!data.countries || data.countries.length === 0) {
                cList.innerHTML = '<div class="lv-empty"><i class="fas fa-map"></i><p>No location data yet today</p></div>';
            } else {
                const maxC = data.countries[0].visitor_count;
                cList.innerHTML = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px;">' +
                    data.countries.map(c => `
                        <div class="lv-country-row">
                            <span class="lv-country-flag">${flag(c.country_code)}</span>
                            <span class="lv-country-name">${c.country || 'Unknown'}</span>
                            <div class="lv-country-bar">
                                <div class="lv-country-fill" style="width:${Math.round(c.visitor_count/maxC*100)}%"></div>
                            </div>
                            <span class="lv-country-cnt">${c.visitor_count}</span>
                        </div>
                    `).join('') + '</div>';
            }
        })
        .catch(err => { if (errBanner) { errBanner.style.display='block'; errBanner.textContent = '⚠ Could not reach tracking API: ' + err.message + ' — retrying in 30s'; } });
}

document.addEventListener('DOMContentLoaded', function() {
    loadVisitors();
    lvTimer = setInterval(loadVisitors, 30000);
});
window.addEventListener('beforeunload', () => clearInterval(lvTimer));
</script>

<?php include '../includes/footer.php'; ?>
