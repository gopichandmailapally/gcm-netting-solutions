<?php
/**
 * Initialize Content Generation Database - SQLite Compatible
 * Creates all content generation tables for page generation system
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

// SQLite-compatible table creation statements
$tables = [
    // Services table
    "CREATE TABLE IF NOT EXISTS services (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        service_id VARCHAR(50) NOT NULL UNIQUE,
        service_name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        category VARCHAR(100),
        description TEXT,
        icon VARCHAR(100),
        is_active INTEGER DEFAULT 1,
        display_order INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Keywords table
    "CREATE TABLE IF NOT EXISTS keywords (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        service_id INTEGER NOT NULL,
        keyword TEXT NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        search_volume INTEGER DEFAULT 0,
        competition VARCHAR(20),
        is_active INTEGER DEFAULT 1,
        display_order INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (service_id) REFERENCES services(id)
    )",
    
    // Areas table
    "CREATE TABLE IF NOT EXISTS areas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        area_name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        pincode VARCHAR(10),
        zone VARCHAR(50),
        is_active INTEGER DEFAULT 1,
        display_order INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Generated pages table
    "CREATE TABLE IF NOT EXISTS generated_pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        keyword_id INTEGER NOT NULL,
        area_id INTEGER NOT NULL,
        page_url VARCHAR(500) NOT NULL UNIQUE,
        page_title VARCHAR(255),
        meta_description TEXT,
        content TEXT,
        is_published INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (keyword_id) REFERENCES keywords(id),
        FOREIGN KEY (area_id) REFERENCES areas(id)
    )",
    
    // Pillar pages table
    "CREATE TABLE IF NOT EXISTS pillar_pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        service_id INTEGER NOT NULL,
        page_url VARCHAR(500) NOT NULL UNIQUE,
        page_title VARCHAR(255),
        meta_description TEXT,
        content TEXT,
        is_published INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (service_id) REFERENCES services(id)
    )",
    
    // Main pages table
    "CREATE TABLE IF NOT EXISTS main_pages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        page_name VARCHAR(255) NOT NULL,
        page_url VARCHAR(500) NOT NULL UNIQUE,
        page_title VARCHAR(255),
        meta_description TEXT,
        content TEXT,
        is_published INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Reviews table
    "CREATE TABLE IF NOT EXISTS reviews (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_name VARCHAR(255) NOT NULL,
        service_type VARCHAR(100),
        area VARCHAR(100),
        rating INTEGER DEFAULT 5,
        review_text TEXT,
        is_featured INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Blogs table
    "CREATE TABLE IF NOT EXISTS blogs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        excerpt TEXT,
        content TEXT,
        featured_image VARCHAR(500),
        category VARCHAR(100),
        tags TEXT,
        is_published INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // FAQs table
    "CREATE TABLE IF NOT EXISTS faqs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        question TEXT NOT NULL,
        answer TEXT NOT NULL,
        category VARCHAR(100),
        display_order INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )"
];

// Execute table creation
foreach ($tables as $sql) {
    try {
        $conn->exec($sql);
        $success_count++;
    } catch (PDOException $e) {
        $error_count++;
        $errors[] = "Table creation error: " . $e->getMessage();
    }
}

// Insert sample data only if tables are empty
try {
    // Check if services table is empty
    $count = $conn->query("SELECT COUNT(*) FROM services")->fetchColumn();
    
    if ($count == 0) {
        // Insert 6 main service categories
        $services_data = [
            ['pigeon-nets', 'Pigeon Nets', 'pigeon-nets', 'PIGEON NETS'],
            ['bird-nets', 'Bird Nets', 'bird-nets', 'BIRD NETS'],
            ['safety-nets', 'Safety Nets', 'safety-nets', 'SAFETY NETS'],
            ['cricket-nets', 'Cricket Nets', 'cricket-nets', 'SPORTS NETS'],
            ['invisible-grills', 'Invisible Grills', 'invisible-grills', 'INVISIBLE GRILLS'],
            ['mosquito-nets', 'Mosquito Nets', 'mosquito-nets', 'MOSQUITO NETS']
        ];
        
        $stmt = $conn->prepare("INSERT INTO services (service_id, service_name, slug, category, is_active, display_order) VALUES (?, ?, ?, ?, 1, ?)");
        
        foreach ($services_data as $index => $service) {
            $stmt->execute([$service[0], $service[1], $service[2], $service[3], $index + 1]);
            $success_count++;
        }
        
        // Insert keywords for each service
        $keywords_data = [
            [1, 'Pigeon Nets', 'pigeon-nets'],
            [1, 'Pigeon Net for Balcony', 'pigeon-net-for-balcony'],
            [1, 'Balcony Netting', 'balcony-netting'],
            [2, 'Bird Nets', 'bird-nets'],
            [2, 'Bird Net for Balcony', 'bird-net-for-balcony'],
            [3, 'Safety Nets', 'safety-nets'],
            [3, 'Balcony Safety Nets', 'balcony-safety-nets'],
            [4, 'Cricket Nets', 'cricket-nets'],
            [4, 'Cricket Practice Nets', 'cricket-practice-nets'],
            [5, 'Invisible Grills', 'invisible-grills'],
            [5, 'Invisible Grill for Balcony', 'invisible-grill-for-balcony'],
            [6, 'Mosquito Nets', 'mosquito-nets'],
            [6, 'Window Mosquito Nets', 'window-mosquito-nets']
        ];
        
        $stmt = $conn->prepare("INSERT INTO keywords (service_id, keyword, slug, is_active, display_order) VALUES (?, ?, ?, 1, ?)");
        
        foreach ($keywords_data as $index => $keyword) {
            $stmt->execute([$keyword[0], $keyword[1], $keyword[2], $index + 1]);
            $success_count++;
        }
        
        // Insert sample Chennai areas
        $areas_data = [
            ['Kodambakkam', 'ameerpet', '600002', 'Central'],
            ['Besant Nagar', 'banjara-hills', '500034', 'Central'],
            ['Sholinganallur', 'hitech-city', '500081', 'West'],
            ['Anna Nagar', 'gachibowli', '500032', 'West'],
            ['T Nagar', 'madhapur', '500081', 'West'],
            ['Velachery', 'kondapur', '500084', 'West'],
            ['Porur', 'kukatpally', '500072', 'North'],
            ['Ambattur', 'miyapur', '500049', 'North'],
            ['Tambaram', 'secunderabad', '500003', 'North'],
            ['Nungambakkam', 'begumpet', '600002', 'Central']
        ];
        
        $stmt = $conn->prepare("INSERT INTO areas (area_name, slug, pincode, zone, is_active, display_order) VALUES (?, ?, ?, ?, 1, ?)");
        
        foreach ($areas_data as $index => $area) {
            $stmt->execute([$area[0], $area[1], $area[2], $area[3], $index + 1]);
            $success_count++;
        }
    }
} catch (PDOException $e) {
    $error_count++;
    $errors[] = "Data insertion error: " . $e->getMessage();
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
        $tables_info[$table] = 0;
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
            <p><strong><?php echo $success_count; ?></strong> operations completed successfully.</p>
            <p>All content generation tables are ready!</p>
        </div>
        <?php else: ?>
        <div class="error-box">
            <h3>⚠️ Completed with <?php echo $error_count; ?> errors</h3>
            <p>Successfully executed: <?php echo $success_count; ?> operations</p>
            <div class="error-list">
                <?php foreach ($errors as $error): ?>
                    <p style="color: #991B1B; margin: 5px 0;">• <?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>📋 Database Tables Status</h3>
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
            <p>✅ <strong>Possible Pages:</strong> <?php echo ($tables_info['keywords'] ?? 0) * ($tables_info['areas'] ?? 0); ?> service pages can be generated</p>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="generate-pages.php" class="btn btn-success">🚀 Go to Page Generator</a>
            <a href="pillar-page-generator.php" class="btn btn-success">📄 Go to Pillar Pages</a>
            <a href="../dashboard.php" class="btn">🏠 Admin Dashboard</a>
        </div>
        
        <div class="info-box" style="margin-top: 30px;">
            <h3>💡 Next Steps</h3>
            <ol style="color: #1E40AF; line-height: 1.8;">
                <li><strong>Test Page Generator:</strong> Visit Generate Pages to see keywords and areas</li>
                <li><strong>Generate Sample Pages:</strong> Start with a single keyword to test</li>
                <li><strong>Create Pillar Pages:</strong> Use Gemini AI to generate pillar pages</li>
                <li><strong>Add More Data:</strong> Add more keywords and areas as needed</li>
            </ol>
        </div>
    </div>
</body>
</html>
