<?php
/**
 * GCM Netting Solutions — One-Time Admin Account Setup
 * ------------------------------------------------
 * 1. Upload this file with the website files.
 * 2. Visit: https://www.gcmsafetynets.in/deploy/create-admin.php
 * 3. Fill in the form and submit.
 * 4. *** DELETE this file immediately after creating your account! ***
 *
 * This file is intentionally NOT protected by admin session checks
 * because it is used to create the very first admin account.
 * Leaving it on the server is a security risk.
 */

define('GCM_INIT', true);
$root = dirname(__DIR__);
require_once $root . '/config/config.php';

// --- Already-created guard ---
$already_done_flag = __DIR__ . '/.setup_done';

$error   = '';
$success = '';

// --- Process form ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';
    $secret   = $_POST['secret']   ?? '';

    // Simple deploy secret prevents random people from creating accounts
    // Change this to any string you like, you will need it on the form.
    $DEPLOY_SECRET = 'GCM-SETUP-2024';

    if ($secret !== $DEPLOY_SECRET) {
        $error = 'Wrong deploy secret. Check the value in create-admin.php line ~35.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Check if username already exists
            $chk = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
            $chk->execute([$username]);
            if ($chk->fetch()) {
                $error = "Username '$username' already exists. Choose another.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $ins  = $pdo->prepare(
                    "INSERT INTO admin_users (username, password_hash, role, is_active) VALUES (?, ?, 'admin', 1)"
                );
                $ins->execute([$username, $hash]);
                $success = "Admin account '$username' created successfully! You can now log in.";
                // Write guard file so the form shows a warning if revisited
                file_put_contents($already_done_flag, date('Y-m-d H:i:s'));
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$setup_done_warning = file_exists($already_done_flag)
    ? '<p style="background:#fee2e2;border:1px solid #f87171;padding:12px;border-radius:6px;color:#991b1b;">
        ⚠️ An admin was already created via this script. <strong>Delete this file now!</strong>
       </p>'
    : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>GCM Netting Solutions — Admin Setup</title>
<style>
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:system-ui,sans-serif;background:#f1f5f9;display:flex;align-items:center;justify-content:center;min-height:100vh}
  .card{background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.1);padding:40px;width:100%;max-width:440px}
  h1{font-size:1.4rem;color:#1e293b;margin-bottom:4px}
  .sub{color:#64748b;font-size:.9rem;margin-bottom:24px}
  label{display:block;font-size:.85rem;font-weight:600;color:#374151;margin-bottom:4px}
  input{width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:.95rem;margin-bottom:16px;outline:none}
  input:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15)}
  button{width:100%;padding:12px;background:#6366f1;color:#fff;border:none;border-radius:6px;font-size:1rem;font-weight:600;cursor:pointer}
  button:hover{background:#4f46e5}
  .error{background:#fee2e2;border:1px solid #f87171;color:#991b1b;padding:12px;border-radius:6px;margin-bottom:16px;font-size:.9rem}
  .success{background:#dcfce7;border:1px solid #86efac;color:#166534;padding:16px;border-radius:6px;font-size:.95rem}
  .warn{background:#fef3c7;border:1px solid #fcd34d;color:#92400e;padding:12px;border-radius:6px;margin-bottom:16px;font-size:.85rem}
  .login-link{display:block;text-align:center;margin-top:16px;color:#6366f1;text-decoration:none;font-weight:600}
</style>
</head>
<body>
<div class="card">
  <h1>🔐 Admin Account Setup</h1>
  <p class="sub">GCM Netting Solutions — One-time admin creation</p>

  <?php echo $setup_done_warning; ?>

  <div class="warn">
    ⚠️ <strong>Delete this file after use!</strong><br>
    Deploy secret is required — see line ~35 in this file.
  </div>

  <?php if ($success): ?>
    <div class="success">
      ✅ <?php echo htmlspecialchars($success); ?>
    </div>
    <a class="login-link" href="<?php echo rtrim(SITE_URL, '/'); ?>/admin/login.php">
      → Go to Admin Login
    </a>
  <?php else: ?>
    <?php if ($error): ?>
      <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="post">
      <label>Deploy Secret</label>
      <input type="password" name="secret" placeholder="Enter deploy secret" required>
      <label>Username</label>
      <input type="text" name="username" placeholder="admin" required autocomplete="off">
      <label>Password</label>
      <input type="password" name="password" placeholder="Min 8 characters" required>
      <label>Confirm Password</label>
      <input type="password" name="confirm" placeholder="Repeat password" required>
      <button type="submit">Create Admin Account</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
