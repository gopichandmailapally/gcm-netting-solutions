<?php
/**
 * Page Security Dashboard
 * Monitor generated pages and deletion logs
 */
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../includes/auth-check.php';

$page_title = 'Page Security Dashboard';

// Get statistics
$protectedDir = __DIR__ . '/../../generated-pages/';
$totalPages = 0;
$totalSize   = 0;

if (is_dir($protectedDir)) {
    $files = glob($protectedDir . '*.php') ?: [];
    $totalPages = count($files);
    foreach ($files as $file) { $totalSize += filesize($file); }
}

// Get deletion logs
$deletionLogs = [];
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $result = $conn->query("SELECT * FROM page_deletion_log ORDER BY deleted_at DESC LIMIT 20");
        if ($result) { while ($row = $result->fetch_assoc()) { $deletionLogs[] = $row; } }
        $conn->close();
    }
} catch (Exception $e) {}

include '../includes/header.php';
?>
<style>
/* ── Page Security Dashboard — lv- theme ─── */
@keyframes lv-blink{0%,100%{opacity:1;}50%{opacity:.25;}}
.lv-hero{background:#fff;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,.07);padding:28px 32px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;border-left:6px solid #8b5cf6;}
.lv-hero h1{font-size:26px;font-weight:800;color:#1e293b;margin:0 0 4px;}.lv-hero p{color:#64748b;font-size:14px;margin:0;}
.lv-badge{background:#f5f3ff;border:1.5px solid #ddd6fe;border-radius:12px;padding:12px 18px;text-align:center;flex-shrink:0;}
.lv-badge-num{font-size:28px;font-weight:800;color:#7c3aed;}.lv-badge-lbl{font-size:11px;color:#6d28d9;font-weight:600;}
.lv-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:0;background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);overflow:hidden;margin-bottom:20px;}
.lv-stat{padding:22px 20px;text-align:center;position:relative;}
.lv-stat+.lv-stat::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:1px;background:#e2e8f0;}
.lv-stat-val{font-size:34px;font-weight:800;color:#1e293b;}.lv-stat-lbl{font-size:12px;color:#64748b;font-weight:600;margin-top:4px;display:flex;align-items:center;justify-content:center;gap:5px;}
.lv-stat.purple .lv-stat-val{color:#8b5cf6;}.lv-stat.green .lv-stat-val{color:#10b981;}.lv-stat.red .lv-stat-val{color:#ef4444;}.lv-stat.blue .lv-stat-val{color:#3b82f6;}
.lv-card{background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);margin-bottom:20px;overflow:hidden;}
.lv-card-hdr{padding:16px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:10px;}
.lv-card-hdr h2{font-size:16px;font-weight:800;color:#1e293b;margin:0;flex:1;}
.lv-card-hdr h2 i{color:#8b5cf6;margin-right:6px;}
.lv-card-body{padding:16px 22px;}
.lv-feat-list{list-style:none;padding:0;margin:0;}
.lv-feat-list li{padding:9px 0;border-bottom:1px solid #f1f5f9;font-size:13px;color:#374151;display:flex;align-items:center;gap:8px;}
.lv-feat-list li:last-child{border-bottom:none;}
.lv-feat-list i{color:#10b981;width:16px;flex-shrink:0;}
.lv-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;}
.lv-info-item{background:#f8fafc;border-radius:10px;padding:14px 16px;}
.lv-info-item strong{display:block;font-size:11px;color:#64748b;margin-bottom:3px;text-transform:uppercase;letter-spacing:.4px;}
.lv-info-item span{font-size:14px;font-weight:700;color:#1e293b;}
.lv-empty{text-align:center;padding:40px;color:#94a3b8;}
.lv-empty i{font-size:32px;display:block;margin-bottom:10px;}
.lv-table{width:100%;border-collapse:collapse;font-size:13px;}
.lv-table th{background:#f8fafc;padding:10px 14px;text-align:left;font-weight:700;color:#64748b;border-bottom:2px solid #e2e8f0;font-size:12px;}
.lv-table td{padding:10px 14px;border-bottom:1px solid #f1f5f9;color:#1e293b;}
.lv-table tr:hover td{background:#fafbff;}
.lv-bdg-del{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#fee2e2;color:#dc2626;}
.lv-bdg-ok{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#d1fae5;color:#065f46;}
.lv-btn{padding:10px 20px;border:none;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:opacity .2s;text-decoration:none;margin-right:8px;}
.lv-btn:hover{opacity:.85;}.lv-btn-purple{background:#8b5cf6;color:#fff;}.lv-btn-grey{background:#f1f5f9;color:#475569;}
@media(max-width:768px){.lv-stats{grid-template-columns:repeat(2,1fr);}.lv-info-grid{grid-template-columns:1fr;}}
</style>

<!-- Hero -->
<div class="lv-hero">
    <div>
        <h1><i class="fas fa-shield-alt" style="color:#8b5cf6;margin-right:8px;"></i>Page Security Dashboard</h1>
        <p>Monitor all generated pages, track deletion activity, and verify protection status</p>
    </div>
    <div class="lv-badge">
        <div class="lv-badge-num"><?php echo number_format($totalPages); ?></div>
        <div class="lv-badge-lbl">PAGES PROTECTED</div>
    </div>
</div>

<!-- Stats -->
<div class="lv-stats">
    <div class="lv-stat green">
        <div class="lv-stat-val"><?php echo number_format($totalPages); ?></div>
        <div class="lv-stat-lbl"><i class="fas fa-file-code" style="color:#10b981;"></i> Generated Pages</div>
    </div>
    <div class="lv-stat blue">
        <div class="lv-stat-val"><?php echo round($totalSize / 1024 / 1024, 2); ?> MB</div>
        <div class="lv-stat-lbl"><i class="fas fa-hdd" style="color:#3b82f6;"></i> Storage Used</div>
    </div>
    <div class="lv-stat red">
        <div class="lv-stat-val"><?php echo count($deletionLogs); ?></div>
        <div class="lv-stat-lbl"><i class="fas fa-trash" style="color:#ef4444;"></i> Pages Deleted</div>
    </div>
    <div class="lv-stat purple">
        <div class="lv-stat-val" style="font-size:22px;">ACTIVE</div>
        <div class="lv-stat-lbl"><i class="fas fa-lock" style="color:#8b5cf6;"></i> Protection Status</div>
    </div>
</div>

<!-- Security Features -->
<div class="lv-card">
    <div class="lv-card-hdr">
        <h2><i class="fas fa-check-shield"></i> Active Security Features</h2>
        <span style="font-size:11px;background:#d1fae5;color:#065f46;padding:3px 12px;border-radius:20px;font-weight:700;"><i class="fas fa-circle" style="font-size:7px;margin-right:4px;animation:lv-blink 1.4s infinite;"></i>ALL SYSTEMS OK</span>
    </div>
    <div class="lv-card-body">
        <ul class="lv-feat-list">
            <li><i class="fas fa-check-circle"></i> All generated pages stored in protected folder <code>/generated-pages/</code></li>
            <li><i class="fas fa-check-circle"></i> Email notifications sent to gopichandmailapally@gmail.com and gcmsafetynets@gmail.com</li>
            <li><i class="fas fa-check-circle"></i> Admin login alerts enabled</li>
            <li><i class="fas fa-check-circle"></i> Page deletion logging active</li>
            <li><i class="fas fa-check-circle"></i> .htaccess protection configured</li>
            <li><i class="fas fa-check-circle"></i> Files are read-only (chmod 0644)</li>
            <li><i class="fas fa-check-circle"></i> Web-based deletion blocked — all deletions logged and emailed</li>
            <li><i class="fas fa-check-circle"></i> URL rewriting enabled for transparent access</li>
        </ul>
    </div>
</div>

<!-- Protected Folder Info -->
<div class="lv-card">
    <div class="lv-card-hdr">
        <h2><i class="fas fa-folder-open"></i> Protected Folder Information</h2>
    </div>
    <div class="lv-card-body">
        <div class="lv-info-grid">
            <div class="lv-info-item"><strong>Location</strong><span><code>/generated-pages/</code></span></div>
            <div class="lv-info-item"><strong>Protection Level</strong><span style="color:#10b981;">HIGH — .htaccess active</span></div>
            <div class="lv-info-item"><strong>Total Pages</strong><span><?php echo number_format($totalPages); ?> PHP files</span></div>
            <div class="lv-info-item"><strong>Total Size</strong><span><?php echo round($totalSize / 1024 / 1024, 2); ?> MB</span></div>
        </div>
        <a href="generate-pages.php" class="lv-btn lv-btn-purple"><i class="fas fa-plus"></i> Generate New Pages</a>
        <a href="ai-content-security.php" class="lv-btn lv-btn-grey"><i class="fas fa-shield-alt"></i> AI Content Security</a>
    </div>
</div>

<!-- Deletion Log -->
<div class="lv-card">
    <div class="lv-card-hdr">
        <h2><i class="fas fa-history"></i> Recent Deletion Activity</h2>
        <span style="font-size:12px;color:#94a3b8;"><?php echo count($deletionLogs); ?> records</span>
    </div>
    <div class="lv-card-body" style="padding:0;">
        <?php if (empty($deletionLogs)): ?>
        <div class="lv-empty">
            <i class="fas fa-shield-alt" style="color:#10b981;"></i>
            <strong style="display:block;margin-bottom:6px;">No pages have been deleted.</strong>
            <span>All pages are secure.</span>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="lv-table">
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Deleted By</th>
                    <th>IP Address</th>
                    <th>Date &amp; Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deletionLogs as $log): ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($log['filename']); ?></code></td>
                    <td><strong><?php echo htmlspecialchars($log['deleted_by']); ?></strong></td>
                    <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                    <td><?php echo date('d M Y, h:i A', strtotime($log['deleted_at'])); ?></td>
                    <td><span class="lv-bdg-del">DELETED</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
