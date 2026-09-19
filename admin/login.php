<?php
ob_start();
/**
 * Admin Login Page - Enterprise Security
 */
define('GCM_INIT', true);
require_once '../config/config.php';
require_once 'includes/security.php';

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

$security = new AdminSecurity();
// Note: IP-level block check removed — account lockout (isAccountLocked) handles brute-force protection.

// Check if coming from logout
$logout_success = isset($_GET['logged_out']) || isset($_SESSION['logged_out']);
if ($logout_success && isset($_SESSION['logged_out'])) {
    unset($_SESSION['logged_out']);
}

// Check for timeout
$timeout_message = isset($_GET['timeout']) ? 'Your session has expired. Please login again.' : '';

// If already logged in (and not logging out), redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && !$logout_success) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$lockout_time = 0;

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!$security->verifyCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please refresh and try again.';
        $security->logSecurityEvent(0, 'csrf_token_invalid', 'Username: ' . $username);
    }
    // Check if account is locked
    elseif ($security->isAccountLocked($username)) {
        $lockout_time = $security->getLockoutTime($username);
        $minutes = ceil($lockout_time / 60);
        $error = "Too many failed login attempts. Account locked for {$minutes} minute(s).";
        $security->logSecurityEvent(0, 'login_attempt_while_locked', 'Username: ' . $username);
    }
    // Validate credentials
    else {
        require_once '../config/database.php';
        $db = Database::getInstance();
        
        $user = $db->fetchOne(
            "SELECT * FROM admin_users WHERE username = ? AND is_active = 1",
            [$username],
            's'
        );
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Successful password check
            $security->recordLoginAttempt($username, true);
            $security->clearLoginAttempts($username);

            // ── 2FA check ────────────────────────────────────────
            require_once 'includes/2fa-helper.php';
            require_once '../config/database.php';
            $twofa_cfg = gcm_2fa_settings();
            $skip_2fa  = false;
            $twofa_pdo = null;
            if (!empty($twofa_cfg['enabled'])) {
                try {
                    $twofa_pdo = Database::getInstance()->getConnection();
                    gcm_2fa_init_tables($twofa_pdo);
                    $skip_2fa = gcm_is_trusted_device($twofa_pdo);
                } catch (\Throwable $e) { $skip_2fa = false; }
            } else {
                $skip_2fa = true; // 2FA disabled globally
            }

            if ($skip_2fa) {
                // Trusted device or 2FA off — direct login
                $security->initSecureSession($user['id']);
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_role']     = $user['role'] ?? 'admin';
                $security->logSecurityEvent($user['id'], 'login_success', 'Successful login (trusted device)');
                
                // Instant redirect to dashboard
                header('Location: dashboard.php');
                if (function_exists('fastcgi_finish_request')) {
                    session_write_close();
                    fastcgi_finish_request();
                }
                
                try {
                    require_once 'includes/email-notifications.php';
                    @sendAdminLoginNotification($username, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '', date('d M Y, h:i:s A'));
                } catch (\Throwable $e) {}
                exit;
            } else {
                // 2FA required — store pending auth, send OTPs, redirect
                $_SESSION['_2fa_pending']    = true;
                $_SESSION['_2fa_user_id']    = $user['id'];
                $_SESSION['_2fa_username']   = $username;
                $_SESSION['_2fa_role']       = $user['role'] ?? 'admin';
                $_SESSION['_2fa_token']      = bin2hex(random_bytes(16));
                $_SESSION['_2fa_created_at'] = time();
                try {
                    $otp_pdo = $twofa_pdo ?: Database::getInstance()->getConnection();
                    gcm_2fa_init_tables($otp_pdo);
                    gcm_dispatch_otps($otp_pdo, $_SESSION['_2fa_token'], $twofa_cfg);
                } catch (\Throwable $e) { /* non-fatal — user can resend from 2fa.php */ }
                $security->logSecurityEvent($user['id'], '2fa_challenge_sent', '2FA OTPs dispatched');
                header('Location: 2fa.php');
                exit;
            }
        } else {
            // Failed login
            $security->recordLoginAttempt($username, false);
            $security->logSecurityEvent(0, 'login_failed', 'Username: ' . $username);
            
            // Check if this triggers a lockout
            if ($security->isAccountLocked($username)) {
                $lockout_time = $security->getLockoutTime($username);
                $minutes = ceil($lockout_time / 60);
                $error = "Too many failed attempts. Account locked for {$minutes} minute(s).";
            } else {
                $error = 'Invalid username or password!';
            }
        }
    }
}

// Generate CSRF token for form
$csrf_token = $security->generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | GCM Netting Solutions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            font-weight: 800;
        }
        
        .login-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .login-header .logo-icon {
            font-size: 60px;
            margin-bottom: 16px;
        }
        
        .login-form {
            padding: 40px 30px;
        }
        
        .error-message {
            background: #FEE2E2;
            border: 2px solid #EF4444;
            color: #991B1B;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error-message i {
            font-size: 18px;
        }
        
        .success-message {
            background: #D1FAE5;
            border: 2px solid #10B981;
            color: #065F46;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .success-message i {
            font-size: 18px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
            font-size: 18px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid #E2E8F0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .login-footer {
            padding: 20px 30px;
            background: #F8FAFC;
            text-align: center;
            font-size: 13px;
            color: #64748B;
        }
        
        .forgot-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #E2E8F0;
        }
        
        .forgot-links a {
            color: #667eea;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }
        
        .forgot-links a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .default-credentials {
            background: #FEF3C7;
            border: 2px solid #F59E0B;
            color: #92400E;
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 13px;
        }
        
        .default-credentials strong {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .default-credentials code {
            background: #FDE68A;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1>GCM Netting Solutions</h1>
            <p>Admin Panel Login</p>
        </div>
        
        <form method="POST" class="login-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            
            <?php if ($logout_success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    You have been logged out successfully!
                </div>
            <?php endif; ?>
            
            <?php if ($timeout_message): ?>
                <div class="error-message">
                    <i class="fas fa-clock"></i>
                    <?php echo htmlspecialchars($timeout_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <?php if ($lockout_time > 0): ?>
                        <br><small>Try again in <?php echo ceil($lockout_time / 60); ?> minute(s)</small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        placeholder="Enter your username"
                        required
                        autocomplete="username"
                        autofocus
                    >
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Login to Admin Panel
            </button>
            
            <div class="forgot-links">
                <a href="forgot-password.php">
                    <i class="fas fa-key"></i> Forgot Password?
                </a>
                <a href="forgot-username.php">
                    <i class="fas fa-user-question"></i> Forgot Username?
                </a>
            </div>
        </form>
        
        <div class="login-footer">
            <i class="fas fa-shield-alt"></i> Protected by Enterprise Security
            <br><small style="opacity:0.7;margin-top:5px;display:block;">Brute-force protection • CSRF protection • Session security</small>
        </div>
    </div>
</body>
</html>
