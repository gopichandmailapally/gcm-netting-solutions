<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

define('GCM_INIT', true);

echo "<p>Loading config...</p>";
require_once 'config/config.php';

echo "<p>Config loaded. DB Settings:</p>";
echo "<ul>";
echo "<li>Host: " . DB_HOST . "</li>";
echo "<li>User: " . DB_USER . "</li>";
echo "<li>Database: " . DB_NAME . "</li>";
echo "</ul>";

echo "<p>Attempting database connection...</p>";

try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    echo "<p style='color:green;'><strong>✅ Database connected successfully!</strong></p>";
    
    // Test query
    $result = $db->fetchOne("SELECT 1 as test");
    if ($result) {
        echo "<p style='color:green;'>✅ Test query successful!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'><strong>❌ Database Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Solution:</strong> The database is not configured for local testing.</p>";
    echo "<p>For testing the new security features, you need to either:</p>";
    echo "<ol>";
    echo "<li>Set up a local MySQL database, OR</li>";
    echo "<li>Upload files to your live server where database is configured</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<h2>Testing Security Files</h2>";

if (file_exists('admin/includes/security.php')) {
    echo "<p style='color:green;'>✅ admin/includes/security.php exists</p>";
} else {
    echo "<p style='color:red;'>❌ admin/includes/security.php missing</p>";
}

if (file_exists('admin/includes/content-security.php')) {
    echo "<p style='color:green;'>✅ admin/includes/content-security.php exists</p>";
} else {
    echo "<p style='color:red;'>❌ admin/includes/content-security.php missing</p>";
}

if (file_exists('admin/pages/content-security-settings.php')) {
    echo "<p style='color:green;'>✅ admin/pages/content-security-settings.php exists</p>";
} else {
    echo "<p style='color:red;'>❌ admin/pages/content-security-settings.php missing</p>";
}

if (file_exists('admin/pages/content-manager.php')) {
    echo "<p style='color:green;'>✅ admin/pages/content-manager.php exists</p>";
} else {
    echo "<p style='color:red;'>❌ admin/pages/content-manager.php missing</p>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<p><strong>To test the new security features, you have two options:</strong></p>";
echo "<ol>";
echo "<li><strong>Upload to Live Server:</strong> Upload all new files to your Hostinger server where the database is already configured</li>";
echo "<li><strong>Local MySQL Setup:</strong> Install MySQL locally and import your database</li>";
echo "</ol>";
?>
