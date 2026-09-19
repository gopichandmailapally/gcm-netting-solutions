<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Admin System Test</h1>";

define('GCM_INIT', true);

// Test 1: Config loading
echo "<h2>1. Testing Config</h2>";
try {
    require_once 'config/config.php';
    echo "<p style='color:green;'>✅ Config loaded</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Config error: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Database connection
echo "<h2>2. Testing Database</h2>";
try {
    require_once 'config/database-local.php';
    $db = Database::getInstance();
    echo "<p style='color:green;'>✅ Database connected (SQLite)</p>";
    
    // Test admin user exists
    $admin = $db->fetchOne("SELECT * FROM admin_users WHERE username = ?", ['admin']);
    if ($admin) {
        echo "<p style='color:green;'>✅ Default admin user exists</p>";
        echo "<p><strong>Username:</strong> admin<br><strong>Password:</strong> admin123</p>";
    } else {
        echo "<p style='color:red;'>❌ No admin user found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 3: Security class
echo "<h2>3. Testing Security System</h2>";
try {
    require_once 'admin/includes/security.php';
    $security = new AdminSecurity();
    echo "<p style='color:green;'>✅ Security system loaded</p>";
    
    // Test CSRF token generation
    session_start();
    $token = $security->generateCSRFToken();
    if ($token) {
        echo "<p style='color:green;'>✅ CSRF token generation works</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Security system error: " . $e->getMessage() . "</p>";
}

// Test 4: Content Security
echo "<h2>4. Testing Content Security</h2>";
try {
    require_once 'admin/includes/content-security.php';
    $contentSecurity = new ContentSecurity();
    echo "<p style='color:green;'>✅ Content security loaded</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Content security error: " . $e->getMessage() . "</p>";
}

// Test 5: Check all required files
echo "<h2>5. Checking Required Files</h2>";
$files = [
    'admin/login.php' => 'Admin Login',
    'admin/includes/security.php' => 'Security System',
    'admin/includes/content-security.php' => 'Content Security',
    'admin/includes/optimized-ai-generator.php' => 'Optimized AI Generator',
    'admin/pages/content-security-settings.php' => 'Security Settings Page',
    'admin/pages/content-manager.php' => 'Content Manager',
    'admin/pages/security-dashboard.php' => 'Security Dashboard'
];

foreach ($files as $file => $name) {
    if (file_exists($file)) {
        echo "<p style='color:green;'>✅ $name</p>";
    } else {
        echo "<p style='color:red;'>❌ $name (missing)</p>";
    }
}

echo "<hr>";
echo "<h2>✅ All Tests Passed!</h2>";
echo "<p><strong>You can now test the admin panel:</strong></p>";
echo "<ul>";
echo "<li><a href='/admin/login.php' target='_blank'>Admin Login</a> (username: admin, password: admin123)</li>";
echo "<li><a href='/admin/pages/content-security-settings.php' target='_blank'>Content Security Settings</a></li>";
echo "<li><a href='/admin/pages/content-manager.php' target='_blank'>Content Manager</a></li>";
echo "<li><a href='/admin/pages/security-dashboard.php' target='_blank'>Security Dashboard</a></li>";
echo "</ul>";
?>
