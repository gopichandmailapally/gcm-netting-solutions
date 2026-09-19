<?php
/**
 * Initialize Content Generation Database Tables
 * Creates keywords, areas, and all content generation tables
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$success_count = 0;
$error_count = 0;
$errors = [];

// Read and execute the master SQL file
$sql_file = '../../config/MASTER-DATABASE-COMPLETE.sql';

if (!file_exists($sql_file)) {
    die("Error: Master SQL file not found at: $sql_file");
}

$sql_content = file_get_contents($sql_file);

// Split into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $sql_content)),
    function($stmt) {
        return !empty($stmt) && 
               !preg_match('/^--/', $stmt) && 
               !preg_match('/^\/\*/', $stmt);
    }
);

foreach ($statements as $statement) {
    if (empty(trim($statement))) continue;
    
    try {
        $conn->exec($statement);
        $success_count++;
    } catch (PDOException $e) {
        $error_count++;
        $errors[] = "SQL Error: " . $e->getMessage();
    }
}

// Get table counts
$tables_info = [];
$tables_to_check = ['services', 'keywords', 'areas', 'generated_pages', 'pillar_pages', 'main_pages', 'reviews', 'blogs', 'faqs'];

foreach ($tables_to_check as $table) {
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        if ($result) {
            $count = $result->fetch(PDO::FETCH_ASSOC)['count'];
            $tables_info[$table] = $count;
        }
    } catch (PDOException $e) {
        $tables_info[$table] = 'Error';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Content Database Initialization</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            margin: 0;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            margin: 0 0 10px 0;
            font-size: 32px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .icon {
            font-size: 40px;
        }
        .success-box {
            background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
            border: 3px solid #10B981;
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
        }
        .success-box h2 {
            margin: 0 0 10px 0;
            color: #065F46;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .success-box p {
            margin: 5px 0;
            color: #047857;
            font-size: 16px;
        }
        .info-box {
            background: linear-gradient(135deg, #DBEAFE, #BFDBFE);
            border: 3px solid #3B82F6;
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
        }
        .info-box h3 {
            margin: 0 0 15px 0;
            color: #1E40AF;
            font-size: 20px;
        }
        .table-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .table-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border: 2px solid #3B82F6;
        }
        .table-item strong {
            color: #1E40AF;
            display: block;
            margin-bottom: 5px;
        }
        .table-item span {
            color: #10B981;
            font-size: 24px;
            font-weight: bold;
        }
        .error-box {
            background: linear-gradient(135deg, #FEE2E2, #FECACA);
            border: 3px solid #EF4444;
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
        }
        .error-box h3 {
            margin: 0 0 15px 0;
            color: #991B1B;
        }
        .error-list {
            max-height: 300px;
            overflow-y: auto;
            background: white;
            padding: 15px;
            border-radius: 8px;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            margin: 10px 10px 10px 0;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-success {
            background: linear-gradient(135deg, #10B981, #059669);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><span class="icon">📊</span> Content Database Initialization</h1>
        
        <?php if ($error_count == 0): ?>
        <div class="success-box">
            <h2>✅ Success!</h2>
            <p><strong><?php echo $success_count; ?></strong> database statements executed successfully.</p>
        </div>
        <?php else: ?>
        <div class="error-box">
            <h3>⚠️ Completed with <?php echo $error_count; ?> errors</h3>
            <p>Successfully executed: <?php echo $success_count; ?> statements</p>
            <div class="error-list">
                <?php foreach ($errors as $error): ?>
                    <p style="color: #991B1B; margin: 5px 0;">• <?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>📋 Database Tables Created</h3>
            <div class="table-grid">
                <?php foreach ($tables_info as $table => $count): ?>
                <div class="table-item">
                    <strong><?php echo ucfirst(str_replace('_', ' ', $table)); ?></strong>
                    <span><?php echo $count; ?></span> records
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="info-box">
            <h3>🎯 What's Ready Now</h3>
            <p>✅ <strong>Services:</strong> <?php echo $tables_info['services'] ?? 0; ?> service categories</p>
            <p>✅ <strong>Keywords:</strong> <?php echo $tables_info['keywords'] ?? 0; ?> SEO keywords</p>
            <p>✅ <strong>Areas:</strong> <?php echo $tables_info['areas'] ?? 0; ?> Chennai locations</p>
            <p>✅ <strong>Generated Pages:</strong> <?php echo $tables_info['generated_pages'] ?? 0; ?> service pages</p>
            <p>✅ <strong>Pillar Pages:</strong> <?php echo $tables_info['pillar_pages'] ?? 0; ?> pillar pages</p>
            <p>✅ <strong>Main Pages:</strong> <?php echo $tables_info['main_pages'] ?? 0; ?> static pages</p>
            <p>✅ <strong>Reviews:</strong> <?php echo $tables_info['reviews'] ?? 0; ?> customer reviews</p>
            <p>✅ <strong>Blogs:</strong> <?php echo $tables_info['blogs'] ?? 0; ?> blog posts</p>
            <p>✅ <strong>FAQs:</strong> <?php echo $tables_info['faqs'] ?? 0; ?> FAQ entries</p>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="generate-pages.php" class="btn btn-success">🚀 Go to Page Generator</a>
            <a href="pillar-page-generator.php" class="btn btn-success">📄 Go to Pillar Pages</a>
            <a href="../dashboard.php" class="btn">🏠 Admin Dashboard</a>
        </div>
        
        <div class="info-box" style="margin-top: 30px;">
            <h3>💡 Next Steps</h3>
            <ol style="color: #1E40AF; line-height: 1.8;">
                <li><strong>Generate Pages:</strong> Use the Page Generator to create service pages for all 188 areas</li>
                <li><strong>Create Pillar Pages:</strong> Generate 64 AI-powered pillar pages using Gemini AI</li>
                <li><strong>Configure API:</strong> Make sure Gemini API key is set in config.php</li>
                <li><strong>Test Generation:</strong> Start with a single keyword to test the system</li>
            </ol>
        </div>
    </div>
</body>
</html>
