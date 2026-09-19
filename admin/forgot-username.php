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

$csrf    = '';
$message = '';
$error   = '';
$sent    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* Load heavy dependencies only on POST submission */
    define('GCM_INIT', true);
    require_once '../config/config.php';
    require_once 'includes/account-manager.php';
    try {
        $am = new AccountManager();
        if (!$am->verifyCSRF('forgot_uname', $_POST['csrf_token'] ?? '')) {
            $error = 'Invalid security token. Please refresh and try again.';
        } else {
            $email    = trim($_POST['email'] ?? '');
            $honeypot = trim($_POST['phone_number'] ?? '');
            if (!$email) {
                $error = 'Please enter your email address.';
            } else {
                $result = $am->forgotUsername($email, $honeypot);
                if ($result['ok']) { $sent = true; $message = $result['message']; }
                else { $error = $result['error']; }
            }
        }
        $csrf = $am->generateCSRF('forgot_uname');
    } catch (\Throwable $e) {
        error_log('[ForgotUN] ' . $e->getMessage());
        $error = 'Service temporarily unavailable. Please try again in a moment.';
        $_SESSION['csrf_forgot_uname'] = ['token' => bin2hex(random_bytes(32)), 'expires' => time() + 600];
        $csrf = $_SESSION['csrf_forgot_uname']['token'];
    }
} else {
    /* GET: generate CSRF directly — no AccountManager needed */
    $_SESSION['csrf_forgot_uname'] = ['token' => bin2hex(random_bytes(32)), 'expires' => time() + 600];
    $csrf = $_SESSION['csrf_forgot_uname']['token'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Username | GCM Netting Solutions Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Inter',-apple-system,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
        .card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;max-width:440px;width:100%;}
        .card-header{background:linear-gradient(135deg,#27ae60 0%,#1e8449 100%);color:#fff;padding:36px 30px;text-align:center;}
        .card-header .icon{font-size:52px;margin-bottom:14px;}
        .card-header h1{font-size:24px;font-weight:800;margin-bottom:6px;}
        .card-header p{font-size:13px;opacity:.9;}
        .card-body{padding:36px 30px;}
        .form-group{margin-bottom:20px;}
        .form-group label{display:block;font-weight:600;color:#1e293b;margin-bottom:7px;font-size:14px;}
        .input-wrap{position:relative;}
        .input-wrap i.ico{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:17px;}
        .form-control{width:100%;padding:13px 14px 13px 44px;border:2px solid #e2e8f0;border-radius:10px;font-size:15px;transition:.3s;}
        .form-control:focus{outline:none;border-color:#27ae60;box-shadow:0 0 0 3px rgba(39,174,96,.1);}
        .btn{width:100%;padding:14px;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;transition:.3s;display:flex;align-items:center;justify-content:center;gap:8px;}
        .btn-success{background:linear-gradient(135deg,#27ae60,#1e8449);color:#fff;}
        .btn-success:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(39,174,96,.4);}
        .alert{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:14px;display:flex;align-items:flex-start;gap:10px;}
        .alert-danger{background:#fee2e2;border:1.5px solid #ef4444;color:#991b1b;}
        .alert-success{background:#d1fae5;border:1.5px solid #10b981;color:#065f46;}
        .card-footer{padding:18px 30px;background:#f8fafc;text-align:center;font-size:13px;color:#64748b;}
        .card-footer a{color:#667eea;text-decoration:none;font-weight:600;}
        .card-footer a:hover{text-decoration:underline;}
        .info-box{background:#f0fdf4;border:1.5px solid #bbf7d0;padding:14px;border-radius:8px;margin-bottom:20px;font-size:13px;color:#166534;display:flex;align-items:flex-start;gap:9px;}
        /* Honeypot — visually hidden but in layout flow */
        .hp-field{position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden;opacity:0;pointer-events:none;}
        .security-note{background:#eff6ff;border:1.5px solid #bfdbfe;padding:10px 14px;border-radius:8px;margin-top:14px;font-size:12px;color:#1e40af;display:flex;align-items:flex-start;gap:8px;}
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <div class="icon"><i class="fas fa-user-question"></i></div>
        <h1>Forgot Username?</h1>
        <p>Enter your registered email — we'll send your username securely</p>
    </div>
    <div class="card-body">
        <?php if ($sent): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle" style="margin-top:2px;flex-shrink:0;"></i>
                <div><?php echo htmlspecialchars($message); ?></div>
            </div>
            <a href="login.php" class="btn btn-success"><i class="fas fa-arrow-left"></i>Back to Login</a>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle" style="flex-shrink:0;"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <div class="info-box">
                <i class="fas fa-shield-alt" style="flex-shrink:0;margin-top:1px;"></i>
                <div>
                    Enter the email address linked to your admin account.<br>
                    Your username will be sent <strong>only</strong> if an account with that email exists.<br>
                    This request is <strong>logged and alerted</strong> to the admin.
                </div>
            </div>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                <!-- Honeypot: invisible to humans, auto-filled by bots -->
                <div class="hp-field" aria-hidden="true">
                    <label for="phone_number">Leave blank</label>
                    <input type="text" id="phone_number" name="phone_number" value="" tabindex="-1" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Registered Email Address</label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope ico"></i>
                        <input type="email" name="email" class="form-control"
                               placeholder="admin@example.com" required
                               autocomplete="email" maxlength="254">
                    </div>
                </div>
                <button type="submit" class="btn btn-success" id="submitBtn"
                        onclick="this.disabled=true;this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Sending…';this.form.submit();">
                    <i class="fas fa-paper-plane"></i>Send My Username
                </button>
                <div class="security-note">
                    <i class="fas fa-lock" style="flex-shrink:0;margin-top:1px;"></i>
                    <span>Max 2 requests per 10 minutes &bull; Auto-blocked after 8 attempts &bull; Every request is logged &amp; alerted</span>
                </div>
            </form>
        <?php endif; ?>
    </div>
    <div class="card-footer">
        <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        &nbsp;|&nbsp;
        <a href="forgot-password.php"><i class="fas fa-key"></i> Forgot Password?</a>
    </div>
</div>
</body>
</html>
