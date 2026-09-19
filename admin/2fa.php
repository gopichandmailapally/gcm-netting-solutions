<?php
/**
 * Admin 2-Step Verification Page
 */
define('GCM_INIT', true);
require_once '../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure',   1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

// Guard: must come from login flow
if (empty($_SESSION['_2fa_pending']) || empty($_SESSION['_2fa_user_id'])) {
    header('Location: login.php');
    exit;
}

// Guard: 2FA window expires after 15 minutes
if ((time() - ($_SESSION['_2fa_created_at'] ?? 0)) > 900) {
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}

require_once 'includes/2fa-helper.php';
$cfg         = gcm_2fa_settings();
$email_otp   = !empty($cfg['email_otp']);
$expiry_min  = (int)($cfg['otp_expiry_minutes'] ?? 10);
$masked_email = '';
if (!empty($cfg['admin_email'])) {
    $parts = explode('@', $cfg['admin_email']);
    $masked_email = substr($parts[0], 0, 2) . str_repeat('*', max(2, strlen($parts[0]) - 2)) . '@' . ($parts[1] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>2-Step Verification | GCM Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.3);width:100%;max-width:460px;overflow:hidden;}
.card-header{background:linear-gradient(135deg,#667eea,#764ba2);padding:36px 32px;text-align:center;color:#fff;}
.card-header .icon{font-size:52px;margin-bottom:12px;}
.card-header h1{font-size:24px;font-weight:800;margin-bottom:6px;}
.card-header p{font-size:13px;opacity:.88;}
.card-body{padding:36px 32px;}
.steps{display:flex;gap:0;margin-bottom:28px;}
.step{flex:1;text-align:center;position:relative;}
.step+.step::before{content:'';position:absolute;top:16px;left:-50%;width:100%;height:2px;background:#e2e8f0;z-index:0;}
.step.done+.step::before{background:#667eea;}
.step-circle{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 6px;font-size:13px;font-weight:700;position:relative;z-index:1;}
.step.pending .step-circle{background:#f1f5f9;color:#94a3b8;border:2px solid #e2e8f0;}
.step.active .step-circle{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;box-shadow:0 4px 12px rgba(102,126,234,.4);}
.step.done .step-circle{background:#10b981;color:#fff;}
.step-label{font-size:11px;font-weight:600;color:#94a3b8;}
.step.active .step-label{color:#667eea;}
.step.done .step-label{color:#10b981;}
.otp-group{margin-bottom:20px;}
.otp-group label{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:#334155;margin-bottom:8px;}
.otp-group label i{color:#667eea;width:16px;}
.otp-hint{font-size:11px;color:#94a3b8;margin-top:4px;}
.otp-input-wrap{position:relative;}
.otp-input{width:100%;padding:14px 48px 14px 16px;border:2px solid #e2e8f0;border-radius:12px;font-size:22px;font-weight:700;letter-spacing:10px;text-align:center;font-family:monospace;transition:border-color .2s,box-shadow .2s;outline:none;color:#1e293b;}
.otp-input:focus{border-color:#667eea;box-shadow:0 0 0 4px rgba(102,126,234,.15);}
.otp-input.valid{border-color:#10b981;background:#f0fdf4;}
.otp-input.invalid{border-color:#ef4444;background:#fff5f5;}
.otp-status{position:absolute;right:14px;top:50%;transform:translateY(-50%);font-size:18px;}
.timer{font-size:12px;font-weight:600;color:#f59e0b;margin-top:5px;display:flex;align-items:center;gap:5px;}
.timer.expired{color:#ef4444;}
.remember-row{display:flex;align-items:flex-start;gap:10px;padding:14px 16px;background:#f8fafc;border-radius:10px;margin-bottom:22px;border:1px solid #e2e8f0;}
.remember-row input[type=checkbox]{width:18px;height:18px;margin-top:1px;accent-color:#667eea;flex-shrink:0;cursor:pointer;}
.remember-row .rl{font-size:13px;color:#334155;}
.remember-row .rl strong{display:block;font-weight:700;}
.remember-row .rl span{font-size:11px;color:#94a3b8;}
.btn-verify{width:100%;padding:15px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;border-radius:12px;font-size:16px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;transition:opacity .2s,transform .1s;margin-bottom:16px;}
.btn-verify:hover{opacity:.9;}
.btn-verify:active{transform:scale(.98);}
.btn-verify:disabled{opacity:.5;cursor:not-allowed;}
.btn-resend{width:100%;padding:11px;background:#f1f5f9;color:#475569;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;transition:background .2s;}
.btn-resend:hover:not(:disabled){background:#e2e8f0;}
.btn-resend:disabled{opacity:.5;cursor:not-allowed;}
.alert{padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:13px;display:flex;align-items:center;gap:10px;}
.alert-error{background:#fee2e2;border:1.5px solid #fecaca;color:#991b1b;}
.alert-success{background:#dcfce7;border:1.5px solid #bbf7d0;color:#166534;}
.back-link{text-align:center;margin-top:18px;}
.back-link a{font-size:13px;color:#667eea;text-decoration:none;font-weight:600;}
.back-link a:hover{text-decoration:underline;}
.spinner{width:18px;height:18px;border:2.5px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;display:none;}
@keyframes spin{to{transform:rotate(360deg);}}
</style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <div class="icon">🔒</div>
        <h1>2-Step Verification</h1>
        <p>Verify your identity to access the admin panel</p>
    </div>
    <div class="card-body">

        <div class="steps">
            <div class="step active" id="step-email">
                <div class="step-circle"><i class="fas fa-envelope"></i></div>
                <div class="step-label">Email OTP</div>
            </div>
            <div class="step pending" id="step-done">
                <div class="step-circle"><i class="fas fa-check"></i></div>
                <div class="step-label">Logged In</div>
            </div>
        </div>

        <div id="alert-box" style="display:none;"></div>

        <form id="verify-form" autocomplete="off">

            <?php if ($email_otp): ?>
            <div class="otp-group" id="grp-email">
                <label><i class="fas fa-envelope"></i> Email OTP</label>
                <div class="otp-input-wrap">
                    <input type="text" id="otp-email" name="otp_email" class="otp-input" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" autocomplete="off">
                    <span class="otp-status" id="stat-email"></span>
                </div>
                <?php if ($masked_email): ?>
                <div class="otp-hint"><i class="fas fa-paper-plane" style="color:#667eea;"></i> Sent to <strong><?php echo htmlspecialchars($masked_email); ?></strong></div>
                <?php endif; ?>
                <div class="timer" id="timer-email"><i class="fas fa-clock"></i> <span id="timer-email-val">Expires in <?php echo $expiry_min; ?>:00</span></div>
            </div>
            <?php endif; ?>

            <div class="remember-row">
                <input type="checkbox" id="remember_device" name="remember_device" value="1">
                <div class="rl">
                    <strong>Don't ask OTP again on this device</strong>
                    <span>Marks this device as trusted for <?php echo (int)($cfg['trusted_device_days'] ?? 30); ?> days — no OTP needed next time</span>
                </div>
            </div>

            <button type="submit" class="btn-verify" id="btn-verify">
                <i class="fas fa-shield-alt"></i>
                <span id="btn-text">Verify & Login</span>
                <div class="spinner" id="spinner"></div>
            </button>

            <button type="button" class="btn-resend" id="btn-resend" onclick="resendOtps()">
                <i class="fas fa-redo"></i> Resend all OTPs
            </button>
        </form>

        <div class="back-link">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</div>

<script>
// ── OTP input: auto-format and mark valid/invalid ──────────────────
['otp-email'].forEach(id => {
    const inp = document.getElementById(id);
    if (!inp) return;
    inp.addEventListener('input', () => {
        inp.value = inp.value.replace(/\D/g,'').slice(0,6);
        const stat = document.getElementById('stat-' + id.replace('otp-',''));
        if (inp.value.length === 6) {
            inp.classList.remove('invalid'); inp.classList.add('valid');
            stat.textContent = '✅';
        } else {
            inp.classList.remove('valid','invalid'); stat.textContent = '';
        }
    });
});

// ── Countdown timers ───────────────────────────────────────────────
let expiryTs = Date.now() + <?php echo $expiry_min; ?> * 60 * 1000;
function updateTimers() {
    const rem = Math.max(0, Math.round((expiryTs - Date.now()) / 1000));
    const m = String(Math.floor(rem/60)).padStart(2,'0');
    const s = String(rem % 60).padStart(2,'0');
    const label = rem > 0 ? `Expires in ${m}:${s}` : 'OTP expired — please resend';
    ['timer-email-val'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.textContent = label; el.closest('.timer')?.classList.toggle('expired', rem===0); }
    });
    if (rem > 0) setTimeout(updateTimers, 1000);
}
updateTimers();

// ── Show alert ─────────────────────────────────────────────────────
function showAlert(msg, type='error') {
    const box = document.getElementById('alert-box');
    box.className = 'alert alert-' + type;
    box.innerHTML = `<i class="fas fa-${type==='error'?'exclamation-circle':'check-circle'}"></i> ${msg}`;
    box.style.display = 'flex';
}

// ── Submit ─────────────────────────────────────────────────────────
document.getElementById('verify-form').addEventListener('submit', async e => {
    e.preventDefault();
    const btn    = document.getElementById('btn-verify');
    const spinner = document.getElementById('spinner');
    const btnText = document.getElementById('btn-text');
    btn.disabled = true; spinner.style.display = 'block'; btnText.textContent = 'Verifying…';

    const body = new URLSearchParams();
    const emailInp = document.getElementById('otp-email');
    if (emailInp) body.append('otp_email', emailInp.value.trim());
    body.append('remember_device', document.getElementById('remember_device').checked ? '1' : '0');

    try {
        const resp = await fetch('api/verify-2fa.php', { method:'POST', body });
        const data = await resp.json();
        if (data.success) {
            showAlert('Verified! Redirecting…', 'success');
            // Mark steps done
            const stepEmail = document.getElementById('step-email');
            if (stepEmail) { stepEmail.className = 'step done'; stepEmail.querySelector('.step-circle').innerHTML = '<i class="fas fa-check"></i>'; }
            const done = document.getElementById('step-done');
            if (done) done.className = 'step active';
            setTimeout(() => window.location.href = data.redirect || 'dashboard.php', 800);
        } else {
            showAlert(data.message || 'Invalid OTP. Please try again.');
            // Mark invalid inputs
            if (data.field === 'email' && emailInp) { emailInp.classList.add('invalid'); document.getElementById('stat-email').textContent='❌'; }
            btn.disabled = false; spinner.style.display = 'none'; btnText.textContent = 'Verify & Login';
        }
    } catch(err) {
        showAlert('Network error. Please try again.');
        btn.disabled = false; spinner.style.display = 'none'; btnText.textContent = 'Verify & Login';
    }
});

// ── Resend OTPs ────────────────────────────────────────────────────
async function resendOtps() {
    const btn = document.getElementById('btn-resend');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
    try {
        const resp = await fetch('api/resend-2fa-otp.php', {method:'POST'});
        const data = await resp.json();
        if (data.success) {
            expiryTs = Date.now() + <?php echo $expiry_min; ?> * 60 * 1000;
            updateTimers();
            showAlert('OTPs resent successfully!', 'success');
        } else {
            showAlert(data.message || 'Failed to resend OTPs.');
        }
    } catch(e) { showAlert('Network error.'); }
    setTimeout(() => { btn.disabled = false; btn.innerHTML = '<i class="fas fa-redo"></i> Resend all OTPs'; }, 30000);
}
</script>
</body>
</html>
