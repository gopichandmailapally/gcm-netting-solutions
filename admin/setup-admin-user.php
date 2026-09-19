<?php
/**
 * Setup Admin User - Create/Update admin credentials in database
 * RUN THIS ONCE to create your admin user
 */

define('GCM_INIT', true);
require_once '../config/config.php';
require_once '../config/database.php';

// YOUR CUSTOM CREDENTIALS
$admin_username = 'Gopichand24';
$admin_password = 'Gcm@818689';  // Your desired password

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    // Check if admin_users table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'admin_users'");
    
    if ($table_check->num_rows === 0) {
        // Create table
        $create_table = "CREATE TABLE admin_users (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'admin',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->query($create_table);
        echo "✅ Created admin_users table<br>";
    }
    
    // Hash the password
    $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
    
    // Check if user exists
    $check_user = $db->fetchOne(
        "SELECT id FROM admin_users WHERE username = ?",
        [$admin_username],
        's'
    );
    
    if ($check_user) {
        // Update existing user
        $stmt = $conn->prepare("UPDATE admin_users SET password_hash = ?, is_active = 1, updated_at = NOW() WHERE username = ?");
        $stmt->bind_param('ss', $password_hash, $admin_username);
        $stmt->execute();
        $stmt->close();
        
        echo "✅ <strong>UPDATED</strong> admin user<br>";
    } else {
        // Create new user
        $stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash, role, is_active) VALUES (?, ?, 'admin', 1)");
        $stmt->bind_param('ss', $admin_username, $password_hash);
        $stmt->execute();
        $stmt->close();
        
        echo "✅ <strong>CREATED</strong> new admin user<br>";
    }
    
    echo "<br>";
    echo "<div style='background: #D1FAE5; padding: 20px; border-radius: 10px; border: 2px solid #10B981; margin: 20px 0;'>";
    echo "<h2 style='color: #065F46; margin: 0 0 10px 0;'>✅ SUCCESS!</h2>";
    echo "<p style='color: #065F46; margin: 0;'><strong>Your admin credentials are now set up!</strong></p>";
    echo "<br>";
    echo "<p style='color: #065F46; margin: 0;'><strong>Username:</strong> <code style='background: #A7F3D0; padding: 4px 8px; border-radius: 4px;'>{$admin_username}</code></p>";
    echo "<p style='color: #065F46; margin: 0;'><strong>Password:</strong> <code style='background: #A7F3D0; padding: 4px 8px; border-radius: 4px;'>{$admin_password}</code></p>";
    echo "</div>";
    
    echo "<br>";
    echo "<a href='login.php' style='display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;'>Go to Login Page →</a>";
    
    echo "<br><br>";
    echo "<div style='background: #FEF3C7; padding: 15px; border-radius: 8px; border: 2px solid #F59E0B; color: #92400E;'>";
    echo "<strong>⚠️ IMPORTANT:</strong> Delete this file after running! (setup-admin-user.php)";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #FEE2E2; padding: 20px; border-radius: 10px; border: 2px solid #EF4444; color: #991B1B;'>";
    echo "<h2>❌ ERROR</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin User Setup</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #F8FAFC;
        }
        code {
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
    </style>
</head>
<body>
</body>
</html>
