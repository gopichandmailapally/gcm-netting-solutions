<?php
/**
 * Verify Account Action — confirms email token for password/username changes
 * Called from links in confirmation emails.
 */
define('GCM_INIT', true);
require_once '../config/config.php';
require_once 'includes/account-manager.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

$am    = new AccountManager();
$token = trim($_GET['token'] ?? '');
$ok    = false;
$msg   = '';
$error = '';

if (!$token) {
    $error = 'Missing verification token. Please use the exact link from your email.';
} else {
    /*
     * We don't know the action type from the URL, so we try both.
     * The token uniquely identifies the action in the DB.
     */
    $result = $am->confirmPasswordChange($token);
    if ($result['ok']) {
        $ok  = true;
        $msg = $result['message'];
        /* If the user is currently logged in, regenerate session so new PW takes effect */
        if (isset($_SESSION['admin_logged_in'])) {
            session_regenerate_id(true);
        }
    } else {
        /* Try username change */
        $result2 = $am->confirmUsernameChange($token);
        if ($result2['ok']) {
            $ok  = true;
            $msg = $result2['message'];
            /* Update session username if active */
            if (isset($_SESSION['admin_username'])) {
                $user = $am->getUser($_SESSION['admin_id'] ?? 0);
                if ($user) $_SESSION['admin_username'] = $user['username'];
            }
        } else {
            /* Use the more informative error (token expired etc.) */
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Account Change | GCM Netting Solutions Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Inter',-apple-system,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
        .card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;max-width:480px;width:100%;text-align:center;}
        .card-header-ok{background:linear-gradient(135deg,#27ae60 0%,#1e8449 100%);color:#fff;padding:40px 30px;}
        .card-header-err{background:linear-gradient(135deg,#e74c3c 0%,#c0392b 100%);color:#fff;padding:40px 30px;}
        .card-header-ok .icon,.card-header-err .icon{font-size:60px;margin-bottom:16px;}
        .card-header-ok h1,.card-header-err h1{font-size:24px;font-weight:800;margin-bottom:8px;}
        .card-header-ok p,.card-header-err p{font-size:14px;opacity:.9;}
        .card-body{padding:36px 30px;}
        .message{font-size:16px;color:#374151;margin-bottom:28px;line-height:1.6;}
        .btn{display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;text-decoration:none;transition:.3s;}
        .btn-ok{background:linear-gradient(135deg,#27ae60,#1e8449);color:#fff;}
        .btn-ok:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(39,174,96,.4);}
        .btn-err{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;}
        .btn-err:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(102,126,234,.4);}
        .card-footer{padding:16px;background:#f8fafc;font-size:13px;color:#64748b;}
        .card-footer a{color:#667eea;text-decoration:none;font-weight:600;}
        .info-list{text-align:left;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:8px;padding:16px 20px;margin-bottom:24px;font-size:14px;color:#166534;}
        .info-list li{margin-bottom:6px;}
    </style>
</head>
<body>
<div class="card">
    <?php if ($ok): ?>
        <div class="card-header-ok">
            <div class="icon"><i class="fas fa-check-circle"></i></div>
            <h1>Confirmed!</h1>
            <p>Your account change has been applied successfully</p>
        </div>
        <div class="card-body">
            <p class="message"><?php echo htmlspecialchars($msg); ?></p>
            <ul class="info-list">
                <li><i class="fas fa-shield-alt"></i> A confirmation email has been sent to your registered address</li>
                <li><i class="fas fa-clock"></i> Time: <?php echo date('d M Y, h:i:s A'); ?></li>
                <li><i class="fas fa-network-wired"></i> IP: <?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'unknown'); ?></li>
            </ul>
            <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                <a href="pages/account-manager.php" class="btn btn-ok"><i class="fas fa-user-cog"></i>Account Manager</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-ok"><i class="fas fa-sign-in-alt"></i>Go to Login</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card-header-err">
            <div class="icon"><i class="fas fa-times-circle"></i></div>
            <h1>Verification Failed</h1>
            <p>The confirmation link could not be validated</p>
        </div>
        <div class="card-body">
            <p class="message"><?php echo htmlspecialchars($error ?: 'The link is invalid or has expired.'); ?></p>
            <p style="color:#64748b;font-size:14px;margin-bottom:24px;">Links expire after 30 minutes and can only be used once. Please go to Account Manager and request a new confirmation.</p>
            <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                <a href="pages/account-manager.php" class="btn btn-err"><i class="fas fa-redo"></i>Try Again</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-err"><i class="fas fa-arrow-left"></i>Back to Login</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="card-footer">
        <a href="login.php">Admin Login</a> &nbsp;|&nbsp;
        <a href="forgot-password.php">Forgot Password</a>
    </div>
</div>
</body>
</html>
