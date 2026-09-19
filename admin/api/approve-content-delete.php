<?php
/**
 * AI Content Deletion Approval Endpoint
 * Accessed via emailed link — no admin login required (token is the security).
 */
define('GCM_INIT', true);
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/ai-content-protection.php';

$protection = new AIContentProtection();
$token      = trim($_GET['token'] ?? '');
$action     = trim($_GET['action'] ?? 'approve');

if (empty($token)) {
    http_response_code(400);
    die(render_page('Invalid Link', 'error', 'This link is invalid or incomplete.', ''));
}

/* ── Handle cancel ────────────────────────────────────── */
if ($action === 'cancel') {
    $r = $protection->cancelPendingDeletion($token);
    if ($r['success']) {
        echo render_page('Deletion Cancelled', 'cancel',
            'The deletion request has been cancelled.',
            htmlspecialchars($r['content_title']));
    } else {
        echo render_page('Cannot Cancel', 'error',
            htmlspecialchars($r['error']), '');
    }
    exit;
}

/* ── Handle approve (GET = confirmation page, POST = confirm) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $r = $protection->approvePendingDeletion($token);
    if ($r['success']) {
        echo render_page('Deletion Approved', 'success',
            'The AI content has been permanently deleted.',
            htmlspecialchars($r['content_title']));
    } else {
        echo render_page('Approval Failed', 'error',
            htmlspecialchars($r['error']), '');
    }
    exit;
}

/* ── GET — show confirmation page ─────────────────────── */
$pending = null;
try {
    $db      = Database::getInstance();
    $pending = $db->fetchOne(
        "SELECT * FROM ai_pending_deletions WHERE token=? AND status='pending'", [$token]
    );
} catch (\Exception $e) {}

if (!$pending) {
    echo render_page('Link Expired', 'error',
        'This approval link is invalid, expired, or has already been used.', '');
    exit;
}

$is_expired = strtotime($pending['requested_at']) < time() - 86400;
if ($is_expired) {
    echo render_page('Link Expired', 'error',
        'This approval link has expired (24-hour limit). No action was taken.', '');
    exit;
}

$type_label  = ucwords(str_replace('_', ' ', $pending['content_type']));
$short_title = htmlspecialchars(mb_substr($pending['content_title'], 0, 160));
$requested   = date('d M Y H:i:s', strtotime($pending['requested_at']));
$reason_html = htmlspecialchars($pending['reason'] ?: '(no reason given)');

echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Approve Deletion — GCM Admin</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.12);width:100%;max-width:560px;overflow:hidden;}
.card-header{background:linear-gradient(135deg,#dc2626,#991b1b);padding:28px 32px;color:#fff;}
.card-header h1{font-size:22px;font-weight:800;margin-bottom:4px;}
.card-header p{font-size:13px;opacity:.8;}
.card-body{padding:28px 32px;}
.info-row{display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:13px;}
.info-row:last-of-type{border-bottom:none;}
.info-label{font-weight:700;color:#374151;width:110px;flex-shrink:0;}
.info-val{color:#64748b;}
.warn-box{background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #dc2626;border-radius:10px;padding:14px 18px;margin:20px 0;font-size:13px;color:#991b1b;font-weight:600;}
.btn-row{display:flex;gap:12px;margin-top:24px;}
.btn-del{background:linear-gradient(135deg,#dc2626,#b91c1c);color:#fff;border:none;padding:13px 28px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;flex:1;transition:opacity .2s;}
.btn-del:hover{opacity:.88;}
.btn-cancel{background:#f1f5f9;color:#475569;border:2px solid #e2e8f0;padding:13px 20px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;}
.btn-cancel:hover{background:#e2e8f0;}
</style>
</head><body>
<div class="card">
  <div class="card-header">
    <h1>&#x26A0; Deletion Approval Required</h1>
    <p>GCM Netting Solutions — AI Content Security System</p>
  </div>
  <div class="card-body">
    <div class="info-row"><span class="info-label">Type</span><span class="info-val">' . $type_label . '</span></div>
    <div class="info-row"><span class="info-label">Title</span><span class="info-val">' . $short_title . '</span></div>
    <div class="info-row"><span class="info-label">Reason</span><span class="info-val">' . $reason_html . '</span></div>
    <div class="info-row"><span class="info-label">Requested</span><span class="info-val">' . $requested . '</span></div>
    <div class="warn-box">&#x26A0; This action is <strong>irreversible</strong>. The AI-generated content will be permanently deleted. Only proceed if you are sure.</div>
    <form method="POST">
      <input type="hidden" name="confirm_delete" value="1">
      <div class="btn-row">
        <button type="submit" class="btn-del">&#x274C; Yes, Permanently Delete</button>
        <a href="?token=' . urlencode($token) . '&action=cancel" class="btn-cancel">&#x2715; Cancel</a>
      </div>
    </form>
  </div>
</div>
</body></html>';

/* ── Shared page renderer ─────────────────────────────── */
function render_page($heading, $status, $message, $subtitle) {
    $colors = [
        'success' => ['#10b981','#065f46','#f0fdf4'],
        'cancel'  => ['#6366f1','#312e81','#eef2ff'],
        'error'   => ['#ef4444','#7f1d1d','#fef2f2'],
    ];
    [$grad, $text, $bg] = $colors[$status] ?? $colors['error'];
    $icon = ['success'=>'✅','cancel'=>'↩️','error'=>'❌'][$status] ?? '⚠️';
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($heading) . ' — GCM Admin</title>
<style>*{box-sizing:border-box;margin:0;padding:0;}body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:#fff;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.12);width:100%;max-width:480px;padding:40px 36px;text-align:center;}
.icon{font-size:52px;margin-bottom:16px;display:block;}h1{font-size:22px;font-weight:800;color:#1e293b;margin-bottom:8px;}.sub{font-size:14px;color:#64748b;margin-bottom:20px;}.msg{background:' . $bg . ';border-radius:12px;padding:14px 18px;font-size:13px;font-weight:600;color:' . $text . ';}.back{display:inline-block;margin-top:20px;color:' . $grad . ';font-size:13px;font-weight:600;text-decoration:none;}</style></head>
<body><div class="card"><span class="icon">' . $icon . '</span><h1>' . htmlspecialchars($heading) . '</h1>'
    . ($subtitle ? '<p class="sub">' . $subtitle . '</p>' : '')
    . '<div class="msg">' . htmlspecialchars($message) . '</div>
<a href="/admin/" class="back">← Back to Admin Panel</a></div></body></html>';
}
