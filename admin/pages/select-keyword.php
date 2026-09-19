<?php
/**
 * Select Keyword for Page Generation
 * Simple interface to choose keyword and start generation
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();

// Get all keywords grouped by category from seo_service_keywords
$all_keywords = $db->fetchAll("SELECT * FROM seo_service_keywords WHERE is_active = 1 ORDER BY display_order");
$keywords_by_category = [];

foreach ($all_keywords as $kw) {
    $category = $kw['category'];
    if (!isset($keywords_by_category[$category])) {
        $keywords_by_category[$category] = [];
    }
    $keywords_by_category[$category][] = $kw;
}

// Get stats
$total_keywords = $db->fetchOne("SELECT COUNT(*) as count FROM seo_service_keywords WHERE is_active = 1")['count'] ?? 0;
$total_areas = $db->fetchOne("SELECT COUNT(*) as count FROM service_areas WHERE is_active = 1")['count'] ?? 0;
$total_possible = $total_keywords * $total_areas;
$already_generated = $db->fetchOne("SELECT COUNT(*) as count FROM generated_pages")['count'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Select Keyword - Page Generator</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; min-height: 100vh; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; border-radius: 20px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        h1 { color: #333; margin-bottom: 10px; font-size: 36px; }
        .subtitle { color: #6B7280; margin-bottom: 30px; font-size: 18px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; padding: 25px; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3); }
        .stat-value { font-size: 42px; font-weight: bold; margin-bottom: 5px; }
        .stat-label { font-size: 14px; opacity: 0.9; }
        .category-section { margin-bottom: 30px; }
        .category-title { background: #F3F4F6; padding: 15px 20px; border-radius: 10px; font-size: 20px; font-weight: 600; color: #1F2937; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .keyword-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; }
        .keyword-card { background: #fff; border: 2px solid #E5E7EB; padding: 20px; border-radius: 12px; cursor: pointer; transition: all 0.3s; position: relative; }
        .keyword-card:hover { border-color: #667eea; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.2); }
        .keyword-card.selected { border-color: #10B981; background: linear-gradient(135deg, #D1FAE5, #A7F3D0); }
        .keyword-name { font-size: 16px; font-weight: 600; color: #1F2937; margin-bottom: 8px; }
        .keyword-stats { font-size: 13px; color: #6B7280; }
        .btn { display: inline-block; padding: 18px 36px; background: linear-gradient(135deg, #10B981, #059669); color: #fff; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 18px; border: none; cursor: pointer; transition: all 0.3s; box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3); }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 15px 40px rgba(16, 185, 129, 0.4); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-secondary { background: linear-gradient(135deg, #6B7280, #4B5563); }
        .action-bar { position: sticky; bottom: 0; background: #fff; padding: 20px 0; margin-top: 30px; border-top: 2px solid #E5E7EB; text-align: center; }
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; }
        .alert-info { background: #DBEAFE; border: 2px solid #3B82F6; color: #1E40AF; }
        .icon { font-size: 24px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-magic"></i> AI Page Generator</h1>
        <p class="subtitle">Select a keyword to generate unique SEO-optimized pages using Gemini AI</p>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_keywords; ?></div>
                <div class="stat-label">Total Keywords</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_areas; ?></div>
                <div class="stat-label">Total Areas</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($total_possible); ?></div>
                <div class="stat-label">Possible Pages</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $already_generated; ?></div>
                <div class="stat-label">Already Generated</div>
            </div>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <strong>How it works:</strong> Select a keyword below, then click "Generate Pages". The system will use Gemini AI to create <?php echo $total_areas; ?> unique pages (one for each area in Chennai) with 1200-1600 words of SEO-optimized content per page.
        </div>
        
        <?php foreach ($keywords_by_category as $category => $keywords): ?>
        <div class="category-section">
            <div class="category-title">
                <i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($category); ?>
            </div>
            <div class="keyword-grid">
                <?php foreach ($keywords as $keyword): ?>
                    <?php
                    $generated_count = $db->fetchOne(
                        "SELECT COUNT(*) as count FROM generated_pages WHERE keyword_id = ?",
                        [$keyword['id']],
                        'i'
                    )['count'];
                    $is_complete = $generated_count >= $total_areas;
                    ?>
                    <div class="keyword-card" onclick="selectKeyword(<?php echo $keyword['id']; ?>, this)">
                        <div class="keyword-name"><?php echo htmlspecialchars($keyword['keyword_name']); ?></div>
                        <div class="keyword-stats">
                            <?php if ($is_complete): ?>
                                <i class="fas fa-check-circle" style="color: #10B981;"></i> Complete (<?php echo $generated_count; ?>/<?php echo $total_areas; ?>)
                            <?php else: ?>
                                <i class="fas fa-clock" style="color: #F59E0B;"></i> <?php echo $generated_count; ?>/<?php echo $total_areas; ?> pages
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div class="action-bar">
            <button id="generate-btn" class="btn" onclick="startGeneration()" disabled>
                <i class="fas fa-rocket"></i> Generate Pages with Gemini AI
            </button>
            <a href="../dashboard.php" class="btn btn-secondary" style="margin-left: 10px;">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <script>
        let selectedKeywordId = null;
        
        function selectKeyword(keywordId, element) {
            // Remove previous selection
            document.querySelectorAll('.keyword-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection to clicked card
            element.classList.add('selected');
            selectedKeywordId = keywordId;
            
            // Enable generate button
            document.getElementById('generate-btn').disabled = false;
        }
        
        function startGeneration() {
            if (!selectedKeywordId) {
                alert('Please select a keyword first');
                return;
            }
            
            // Redirect to progress page
            window.location.href = 'generate-with-progress.php?keyword_id=' + selectedKeywordId;
        }
    </script>
</body>
</html>
