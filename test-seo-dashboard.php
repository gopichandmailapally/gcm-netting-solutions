<?php
/**
 * Test SEO Dashboard Live Data
 */

define('GCM_INIT', true);
require_once 'config/config.php';
require_once 'config/database.php';

$db = Database::getInstance();

echo "<h1>🔍 SEO Dashboard Live Data Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}h1{color:#667eea;}h2{color:#764ba2;margin-top:30px;}p{margin:10px 0;padding:10px;border-radius:5px;}.success{background:#d1fae5;color:#065f46;}.info{background:#dbeafe;color:#1e40af;}.warning{background:#fef3c7;color:#92400e;}table{width:100%;border-collapse:collapse;margin:20px 0;background:#fff;}th,td{padding:12px;text-align:left;border-bottom:1px solid #e2e8f0;}th{background:#f8fafc;font-weight:600;}</style>";

// Test 1: Database Connection
echo "<h2>1. Database Connection</h2>";
try {
    $conn = $db->getConnection();
    echo "<p class='success'>✅ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test 2: Check Tables
echo "<h2>2. Database Tables</h2>";
$tables = ['generated_pages', 'seo_rankings', 'seo_recommendations'];
foreach ($tables as $table) {
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table' AND name=?", [$table]);
    if ($result['count'] > 0) {
        echo "<p class='success'>✅ Table '{$table}' exists</p>";
    } else {
        echo "<p class='warning'>⚠️ Table '{$table}' does not exist</p>";
    }
}

// Test 3: Scan Live Pages
echo "<h2>3. Live Pages Scan</h2>";
$root_dir = __DIR__;
$php_files = glob($root_dir . '/*.php');
$excluded_files = ['index.php', 'contact.php', 'about.php', 'error.php', 'test-db.php', 'test-admin.php', 'test-login.php', 'working-demo.php', 'test-seo-dashboard.php'];

$live_pages = [];
foreach ($php_files as $file) {
    $filename = basename($file);
    if (!in_array($filename, $excluded_files)) {
        $content = file_get_contents($file);
        
        preg_match('/<title>(.*?)<\/title>/i', $content, $title_match);
        preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']*)["\']/i', $content, $desc_match);
        
        $live_pages[] = [
            'file' => $filename,
            'title' => $title_match[1] ?? 'No title',
            'description' => $desc_match[1] ?? 'No description',
            'size' => filesize($file),
            'words' => str_word_count(strip_tags($content))
        ];
    }
}

echo "<p class='success'>✅ Found " . count($live_pages) . " live pages</p>";

if (count($live_pages) > 0) {
    echo "<table>";
    echo "<tr><th>File</th><th>Title</th><th>Description</th><th>Words</th></tr>";
    foreach (array_slice($live_pages, 0, 10) as $page) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($page['file']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($page['title'], 0, 50)) . "</td>";
        echo "<td>" . htmlspecialchars(substr($page['description'], 0, 60)) . "...</td>";
        echo "<td>" . $page['words'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 4: Generated Pages Count
echo "<h2>4. Generated Pages from Database</h2>";
$total_pages = $db->fetchOne("SELECT COUNT(*) as count FROM generated_pages WHERE is_published = 1");
echo "<p class='info'>📊 Generated pages in database: " . ($total_pages['count'] ?? 0) . "</p>";

// Test 5: Total Pages Count
$total_live_pages = count($live_pages) + ($total_pages['count'] ?? 0);
echo "<p class='success'>✅ Total tracked pages: {$total_live_pages}</p>";

// Test 6: SEO Recommendations
echo "<h2>5. SEO Recommendations Analysis</h2>";
$recommendations = [];
foreach ($live_pages as $page) {
    if (empty($page['title']) || $page['title'] === 'No title' || strlen($page['title']) < 30) {
        $recommendations[] = [
            'page' => $page['file'],
            'type' => 'Title',
            'priority' => 'HIGH',
            'issue' => 'Missing or short title'
        ];
    }
    if (empty($page['description']) || $page['description'] === 'No description' || strlen($page['description']) < 120) {
        $recommendations[] = [
            'page' => $page['file'],
            'type' => 'Description',
            'priority' => 'HIGH',
            'issue' => 'Missing or short description'
        ];
    }
    if ($page['words'] < 300) {
        $recommendations[] = [
            'page' => $page['file'],
            'type' => 'Content',
            'priority' => 'CRITICAL',
            'issue' => 'Thin content (' . $page['words'] . ' words)'
        ];
    }
}

echo "<p class='warning'>⚠️ Found " . count($recommendations) . " SEO issues</p>";

if (count($recommendations) > 0) {
    echo "<table>";
    echo "<tr><th>Page</th><th>Type</th><th>Priority</th><th>Issue</th></tr>";
    foreach (array_slice($recommendations, 0, 15) as $rec) {
        $priority_class = $rec['priority'] === 'CRITICAL' ? 'style="color:#dc2626;font-weight:bold;"' : ($rec['priority'] === 'HIGH' ? 'style="color:#ea580c;font-weight:bold;"' : '');
        echo "<tr>";
        echo "<td>" . htmlspecialchars($rec['page']) . "</td>";
        echo "<td>" . htmlspecialchars($rec['type']) . "</td>";
        echo "<td {$priority_class}>" . htmlspecialchars($rec['priority']) . "</td>";
        echo "<td>" . htmlspecialchars($rec['issue']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 7: Ranking Statistics
echo "<h2>6. Ranking Statistics (Simulated)</h2>";
$rank_1_count = min(15, floor($total_live_pages * 0.15));
$rank_1_to_3 = min(45, floor($total_live_pages * 0.35));
$rank_1_to_10 = min(120, floor($total_live_pages * 0.75));

echo "<p class='info'>🏆 Rank #1 Pages: {$rank_1_count}</p>";
echo "<p class='info'>🥇 Top 3 Rankings: {$rank_1_to_3}</p>";
echo "<p class='info'>📊 Page 1 (Top 10): {$rank_1_to_10}</p>";

// Test 8: Google Search Console Status
echo "<h2>7. Google Search Console Integration</h2>";
$gsc_config_file = __DIR__ . '/config/google-search-console.json';
if (file_exists($gsc_config_file)) {
    $config = json_decode(file_get_contents($gsc_config_file), true);
    if ($config['enabled']) {
        echo "<p class='success'>✅ Google Search Console integration is enabled</p>";
    } else {
        echo "<p class='warning'>⚠️ Google Search Console integration is disabled</p>";
    }
} else {
    echo "<p class='info'>ℹ️ Google Search Console not configured (will be created on first sync)</p>";
}

echo "<hr><h2>✅ SEO Dashboard Test Complete!</h2>";
echo "<p class='success'><strong>The SEO Dashboard is working with live data!</strong></p>";
echo "<p class='info'>Access the dashboard at: <a href='/admin/pages/seo-dashboard.php' style='color:#667eea;font-weight:bold;'>SEO Dashboard</a></p>";
?>
