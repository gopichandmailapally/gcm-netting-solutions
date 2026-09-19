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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #060b17;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        /* Ambient Glowing Nebula Orbs */
        .ambient-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.45;
            pointer-events: none;
            animation: orbFloat 18s ease-in-out infinite alternate;
        }

        .orb-1 {
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, #2563eb 0%, #1e40af 100%);
            top: -100px;
            left: -100px;
        }

        .orb-2 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, #06b6d4 0%, #0284c7 100%);
            bottom: -80px;
            right: -80px;
            animation-delay: -9s;
        }

        .orb-3 {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, #10b981 0%, #047857 100%);
            top: 40%;
            left: 60%;
            opacity: 0.25;
            animation-delay: -4s;
        }

        @keyframes orbFloat {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(40px, -30px) scale(1.08); }
            100% { transform: translate(-30px, 40px) scale(0.95); }
        }
        
        .login-container {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(28px) saturate(190%);
            -webkit-backdrop-filter: blur(28px) saturate(190%);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 30px 70px -15px rgba(0, 0, 0, 0.7), 0 0 40px rgba(14, 165, 233, 0.12);
            overflow: hidden;
            max-width: 460px;
            width: 100%;
            position: relative;
            z-index: 10;
        }
        
        .login-header {
            padding: 44px 32px 28px;
            text-align: center;
            position: relative;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.03) 0%, transparent 100%);
        }
        
        .logo-icon-wrap {
            width: 76px;
            height: 76px;
            margin: 0 auto 20px;
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.2) 0%, rgba(6, 182, 212, 0.25) 100%);
            border: 1px solid rgba(56, 189, 248, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 30px -5px rgba(37, 99, 235, 0.4);
            position: relative;
        }

        .logo-icon-wrap i {
            font-size: 36px;
            background: linear-gradient(135deg, #38bdf8 0%, #60a5fa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .status-dot {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 16px;
            height: 16px;
            background: #10b981;
            border: 3px solid #0f172a;
            border-radius: 50%;
            box-shadow: 0 0 10px #10b981;
        }
        
        .login-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            margin-bottom: 6px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #ffffff;
        }
        
        .login-header p {
            font-size: 14px;
            color: #94a3b8;
            font-weight: 500;
        }
        
        .login-form {
            padding: 32px 32px 28px;
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .success-message {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 22px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .form-group {
            margin-bottom: 22px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 8px;
            font-size: 13.5px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 16px;
            transition: color 0.25s ease;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 18px 14px 48px;
            background: rgba(30, 41, 59, 0.6);
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            font-size: 15px;
            color: #ffffff;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
        }

        .form-control::placeholder {
            color: #64748b;
        }
        
        .form-control:focus {
            outline: none;
            background: rgba(30, 41, 59, 0.85);
            border-color: #38bdf8;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.15);
        }

        .form-control:focus + i {
            color: #38bdf8;
        }
        
        .btn-login {
            width: 100%;
            padding: 15px 24px;
            background: linear-gradient(135deg, #0284c7 0%, #2563eb 50%, #4f46e5 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.5);
            margin-top: 10px;
            letter-spacing: 0.01em;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -4px rgba(37, 99, 235, 0.65);
            filter: brightness(1.08);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .login-footer {
            padding: 20px 32px;
            background: rgba(10, 15, 28, 0.6);
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            text-align: center;
            font-size: 13px;
            color: #64748b;
        }

        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #94a3b8;
            font-weight: 600;
            font-size: 12.5px;
        }

        .security-badge i {
            color: #10b981;
        }
    </style>
</head>
<body>
    <!-- Ambient Lighting Nebula -->
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>
    <div class="ambient-orb orb-3"></div>

    <div class="login-container">
        <div class="login-header">
            <div class="logo-icon-wrap">
                <i class="fas fa-shield-alt"></i>
                <div class="status-dot"></div>
            </div>
            <h1>GCM Netting Solutions</h1>
            <p>Chennai Enterprise Admin Dashboard</p>
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
                    <div>
                        <?php echo htmlspecialchars($error); ?>
                        <?php if ($lockout_time > 0): ?>
                            <div style="font-size:12px;margin-top:2px;">Try again in <?php echo ceil($lockout_time / 60); ?> minute(s)</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Administrator Username</label>
                <div class="input-wrapper">
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        placeholder="Enter username"
                        required
                        autocomplete="username"
                        autofocus
                    >
                    <i class="fas fa-user"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="••••••••••••"
                        required
                        autocomplete="current-password"
                    >
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <span>Sign In Securely</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>
        
        <div class="login-footer">
            <div class="security-badge">
                <i class="fas fa-shield-check"></i> 256-Bit SSL Encrypted &bull; 2FA Protected
            </div>
        </div>
    </div>
</body>
</html>
