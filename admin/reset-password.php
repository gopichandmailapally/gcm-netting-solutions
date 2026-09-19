<?php
ob_start();

/* ── Session only — no heavy dependencies needed for GET ── */
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php'); exit;
}

$token = trim($_GET['token'] ?? '');
$error = '';
$done  = false;

if (!$token) {
    $error = 'Missing reset token. Please use the link from your email.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token) {
    /* Load heavy dependencies only on POST submission */
    define('GCM_INIT', true);
    require_once '../config/config.php';
    require_once 'includes/account-manager.php';
    try {
        $am = new AccountManager();
        if (!$am->verifyCSRF('reset_pw', $_POST['csrf_token'] ?? '')) {
            $error = 'Invalid security token. Please use the original link again.';
        } else {
            $result = $am->resetPassword(
                $token,
                trim($_POST['username_check'] ?? ''),
                $_POST['new_password']     ?? '',
                $_POST['confirm_password'] ?? ''
            );
            if ($result['ok']) {
                $done  = true;
                $token = '';
            } else {
                $error = $result['error'];
            }
        }
        $csrf = $am->generateCSRF('reset_pw');
    } catch (\Throwable $e) {
        error_log('[ResetPW] ' . $e->getMessage());
        $error = 'Service temporarily unavailable. Please try again in a moment.';
        $_SESSION['csrf_reset_pw'] = ['token' => bin2hex(random_bytes(32)), 'expires' => time() + 600];
        $csrf = $_SESSION['csrf_reset_pw']['token'];
    }
} else {
    /* GET: generate CSRF directly — no AccountManager needed */
    $_SESSION['csrf_reset_pw'] = ['token' => bin2hex(random_bytes(32)), 'expires' => time() + 600];
    $csrf = $_SESSION['csrf_reset_pw']['token'];
}
/* ─── HTML UI ─── */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | GCM Netting Solutions Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Inter',-apple-system,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
        .card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;max-width:460px;width:100%;}
        .card-header{background:linear-gradient(135deg,#e74c3c 0%,#c0392b 100%);color:#fff;padding:36px 30px;text-align:center;}
        .card-header .icon{font-size:52px;margin-bottom:14px;}
        .card-header h1{font-size:24px;font-weight:800;margin-bottom:6px;}
        .card-header p{font-size:13px;opacity:.9;}
        .card-body{padding:36px 30px;}
        .form-group{margin-bottom:20px;}
        .form-group label{display:block;font-weight:600;color:#1e293b;margin-bottom:7px;font-size:14px;}
        .input-wrap{position:relative;}
        .input-wrap i.fi{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:17px;}
        .input-wrap i.toggle{position:absolute;right:14px;top:50%;transform:translateY(-50%);color:#94a3b8;cursor:pointer;font-size:16px;}
        .form-control{width:100%;padding:13px 44px 13px 44px;border:2px solid #e2e8f0;border-radius:10px;font-size:15px;transition:.3s;}
        .form-control:focus{outline:none;border-color:#e74c3c;box-shadow:0 0 0 3px rgba(231,76,60,.1);}
        .btn{width:100%;padding:14px;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;transition:.3s;display:flex;align-items:center;justify-content:center;gap:8px;}
        .btn-danger{background:linear-gradient(135deg,#e74c3c,#c0392b);color:#fff;}
        .btn-danger:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(231,76,60,.4);}
        .btn-secondary{background:#667eea;color:#fff;margin-top:10px;}
        .alert{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:14px;display:flex;align-items:flex-start;gap:10px;}
        .alert-danger{background:#fee2e2;border:1.5px solid #ef4444;color:#991b1b;}
        .alert-success{background:#d1fae5;border:1.5px solid #10b981;color:#065f46;}
        .pw-rules{background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#475569;}
        .pw-rules ul{padding-left:18px;margin-top:6px;}
        .pw-rules li{margin-bottom:4px;}
        .card-footer{padding:16px 30px;background:#f8fafc;text-align:center;font-size:13px;color:#64748b;}
        .card-footer a{color:#667eea;text-decoration:none;font-weight:600;}
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <div class="icon"><i class="fas fa-shield-alt"></i></div>
        <h1>Reset Your Password</h1>
        <p>Enter a new strong password for your admin account</p>
    </div>
    <div class="card-body">
        <?php if ($done): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle" style="margin-top:2px;font-size:20px;"></i>
                <div><strong>Password reset successfully!</strong><br>You can now log in with your new password.</div>
            </div>
            <a href="login.php" class="btn btn-danger"><i class="fas fa-sign-in-alt"></i>Go to Login</a>
        <?php elseif ($error && !$token): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i><?php echo htmlspecialchars($error); ?></div>
            <a href="forgot-password.php" class="btn btn-danger"><i class="fas fa-key"></i>Request New Reset Link</a>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="pw-rules">
                <strong><i class="fas fa-info-circle"></i> Password must:</strong>
                <ul>
                    <li>Be at least 8 characters long</li>
                    <li>Contain uppercase &amp; lowercase letters</li>
                    <li>Contain at least one number</li>
                    <li>Contain at least one special character</li>
                </ul>
            </div>
            <div style="background:#fff3cd;border:1.5px solid #ffc107;border-radius:8px;padding:12px 14px;margin-bottom:18px;font-size:13px;color:#856404;display:flex;align-items:flex-start;gap:8px;">
                <i class="fas fa-shield-alt" style="flex-shrink:0;margin-top:2px;"></i>
                <span><strong>2-Step Verification:</strong> You must enter both your username <em>and</em> this form to reset your password. Even if someone intercepts your reset email, they cannot proceed without knowing your username.</span>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                <div class="form-group">
                    <label><i class="fas fa-user" style="margin-right:6px;"></i>Confirm Your Username</label>
                    <div class="input-wrap">
                        <i class="fas fa-user fi"></i>
                        <input type="text" name="username_check" class="form-control" placeholder="Enter your admin username" required autocomplete="username" maxlength="30">
                    </div>
                    <p style="font-size:12px;color:#94a3b8;margin-top:5px;">This confirms the reset link belongs to you</p>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock fi"></i>
                        <input type="password" name="new_password" id="newPw" class="form-control" placeholder="Enter new password" required autocomplete="new-password">
                        <i class="fas fa-eye toggle" onclick="togglePw('newPw',this)"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock fi"></i>
                        <input type="password" name="confirm_password" id="confPw" class="form-control" placeholder="Re-enter new password" required autocomplete="new-password">
                        <i class="fas fa-eye toggle" onclick="togglePw('confPw',this)"></i>
                    </div>
                </div>
                <button type="submit" class="btn btn-danger"><i class="fas fa-save"></i>Set New Password</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="card-footer"><a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a></div>
</div>
<script>
function togglePw(id, icon) {
    const f = document.getElementById(id);
    if (f.type === 'password') { f.type = 'text'; icon.className = 'fas fa-eye-slash toggle'; }
    else { f.type = 'password'; icon.className = 'fas fa-eye toggle'; }
}
</script>
</body>
</html>
