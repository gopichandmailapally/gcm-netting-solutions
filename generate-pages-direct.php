<?php
/**
 * Direct Page Generator - No Login Required
 * Emergency access to generate pages
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0); // No timeout

// Database and config
define('GCM_INIT', true);
$db_path = __DIR__ . '/data/local-test.db';

if (!file_exists($db_path)) {
    die('Database not found. Please run init-database-simple.php first.');
}

// Connect to database
try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Get Gemini API key from config
require_once 'config/config.php';

if (empty(GEMINI_API_KEY) || GEMINI_API_KEY === 'your-gemini-api-key-here') {
    die('Gemini API key not configured in config.php');
}

// Get keyword_id from URL
$keyword_id = isset($_GET['keyword_id']) ? (int)$_GET['keyword_id'] : 0;
$auto_start = isset($_GET['auto_start']) ? true : false;

// Get keyword details
if ($keyword_id > 0) {
    $stmt = $db->prepare("SELECT k.*, s.service_name FROM keywords k JOIN services s ON k.service_id = s.id WHERE k.id = ?");
    $stmt->execute([$keyword_id]);
    $keyword = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $keyword = null;
}

// Get all keywords for selection
$all_keywords = $db->query("SELECT k.*, s.service_name, s.category FROM keywords k JOIN services s ON k.service_id = s.id WHERE k.is_active = 1 ORDER BY s.category, k.display_order")->fetchAll(PDO::FETCH_ASSOC);

// Get all areas
$areas = $db->query("SELECT * FROM areas WHERE is_active = 1 ORDER BY area_name")->fetchAll(PDO::FETCH_ASSOC);
$total_areas = count($areas);

// Get stats
$total_keywords = $db->query("SELECT COUNT(*) FROM keywords WHERE is_active = 1")->fetchColumn();
$already_generated = $db->query("SELECT COUNT(*) FROM generated_pages")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Page Generator</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; border-radius: 20px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        h1 { color: #333; margin-bottom: 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-box { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; padding: 20px; border-radius: 12px; text-align: center; }
        .stat-value { font-size: 36px; font-weight: bold; }
        .stat-label { font-size: 14px; opacity: 0.9; margin-top: 5px; }
        .keyword-select { margin-bottom: 30px; }
        .keyword-select select { width: 100%; padding: 15px; font-size: 16px; border: 2px solid #E5E7EB; border-radius: 10px; }
        .progress-container { margin: 30px 0; }
        .progress-bar { width: 100%; height: 40px; background: #E5E7EB; border-radius: 20px; overflow: hidden; position: relative; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #10B981, #059669); transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; }
        .counters { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0; }
        .counter-box { background: #F3F4F6; padding: 15px; border-radius: 10px; text-align: center; }
        .counter-value { font-size: 28px; font-weight: bold; color: #10B981; }
        .counter-label { color: #6B7280; font-size: 13px; margin-top: 5px; }
        .log { background: #1F2937; color: #10B981; padding: 20px; border-radius: 10px; height: 400px; overflow-y: auto; font-family: monospace; font-size: 13px; margin: 20px 0; }
        .log-entry { margin: 5px 0; }
        .log-success { color: #10B981; }
        .log-error { color: #EF4444; }
        .log-info { color: #3B82F6; }
        .btn { display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #10B981, #059669); color: #fff; text-decoration: none; border-radius: 10px; font-weight: 600; border: none; cursor: pointer; font-size: 16px; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-secondary { background: linear-gradient(135deg, #6B7280, #4B5563); }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .alert-info { background: #DBEAFE; border: 2px solid #3B82F6; color: #1E40AF; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Direct Page Generator</h1>
        
        <div class="stats">
            <div class="stat-box">
                <div class="stat-value"><?php echo $total_keywords; ?></div>
                <div class="stat-label">Total Keywords</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $total_areas; ?></div>
                <div class="stat-label">Total Areas</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?php echo $already_generated; ?></div>
                <div class="stat-label">Already Generated</div>
            </div>
        </div>
        
        <?php if (!$keyword): ?>
        <div class="alert alert-info">
            Select a keyword below to generate <?php echo $total_areas; ?> unique pages using Gemini AI
        </div>
        
        <div class="keyword-select">
            <select id="keyword-select" onchange="selectKeyword()">
                <option value="">-- Select a Keyword --</option>
                <?php
                $current_category = '';
                foreach ($all_keywords as $kw):
                    if ($kw['category'] !== $current_category):
                        if ($current_category !== '') echo '</optgroup>';
                        echo '<optgroup label="' . htmlspecialchars($kw['category']) . '">';
                        $current_category = $kw['category'];
                    endif;
                ?>
                    <option value="<?php echo $kw['id']; ?>"><?php echo htmlspecialchars($kw['keyword']); ?></option>
                <?php endforeach; ?>
                <?php if ($current_category !== '') echo '</optgroup>'; ?>
            </select>
        </div>
        
        <script>
        function selectKeyword() {
            const select = document.getElementById('keyword-select');
            if (select.value) {
                window.location.href = '?keyword_id=' + select.value;
            }
        }
        </script>
        
        <?php else: ?>
        
        <div class="alert alert-info">
            <strong>Generating:</strong> <?php echo htmlspecialchars($keyword['keyword']); ?> (<?php echo htmlspecialchars($keyword['service_name']); ?>)
        </div>
        
        <div class="counters">
            <div class="counter-box">
                <div class="counter-value" id="completed">0</div>
                <div class="counter-label">Completed</div>
            </div>
            <div class="counter-box">
                <div class="counter-value" id="failed">0</div>
                <div class="counter-label">Failed</div>
            </div>
            <div class="counter-box">
                <div class="counter-value" id="remaining"><?php echo $total_areas; ?></div>
                <div class="counter-label">Remaining</div>
            </div>
        </div>
        
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill" id="progress" style="width: 0%">0%</div>
            </div>
        </div>
        
        <div style="text-align: center; margin: 20px 0;">
            <button id="start-btn" class="btn" onclick="startGeneration()">✨ Start Generating</button>
            <button id="stop-btn" class="btn btn-secondary" onclick="stopGeneration()" style="display:none;">⏸️ Stop</button>
            <a href="?" class="btn btn-secondary">← Select Different Keyword</a>
        </div>
        
        <div class="log" id="log">
            <div class="log-info">Ready to generate <?php echo $total_areas; ?> pages...</div>
        </div>
        
        <script>
        const keywordId = <?php echo $keyword_id; ?>;
        const keywordText = <?php echo json_encode($keyword['keyword']); ?>;
        const areas = <?php echo json_encode($areas); ?>;
        const apiKey = <?php echo json_encode(GEMINI_API_KEY); ?>;
        const apiUrl = <?php echo json_encode(GEMINI_API_URL); ?>;
        
        let currentIndex = 0;
        let completed = 0;
        let failed = 0;
        let isRunning = false;
        
        function addLog(message, type = 'info') {
            const log = document.getElementById('log');
            const entry = document.createElement('div');
            entry.className = 'log-entry log-' + type;
            entry.textContent = new Date().toLocaleTimeString() + ' - ' + message;
            log.appendChild(entry);
            log.scrollTop = log.scrollHeight;
        }
        
        function updateProgress() {
            const total = areas.length;
            const processed = currentIndex;
            const percentage = Math.round((processed / total) * 100);
            
            document.getElementById('progress').style.width = percentage + '%';
            document.getElementById('progress').textContent = percentage + '%';
            document.getElementById('completed').textContent = completed;
            document.getElementById('failed').textContent = failed;
            document.getElementById('remaining').textContent = total - processed;
        }
        
        async function generatePage(area) {
            const areaName = area.area_name;
            
            // Check if already exists
            try {
                const checkResponse = await fetch('check-page-exists.php?keyword_id=' + keywordId + '&area_id=' + area.id);
                const checkData = await checkResponse.json();
                if (checkData.exists) {
                    addLog('Skipped: ' + areaName + ' (already exists)', 'info');
                    return { success: true, skipped: true };
                }
            } catch (e) {
                // Continue anyway
            }
            
            // Create AI prompt
            const prompt = `Write a comprehensive SEO-optimized article about '${keywordText}' services in ${areaName}, Chennai, India.

Include the following sections:
1. Introduction (150 words) - Explain the importance of ${keywordText} in ${areaName}
2. Why Choose ${keywordText} in ${areaName} (200 words) - Local climate conditions, weather patterns, necessity
3. Our Services (200 words) - Professional installation, quality materials, warranty
4. Benefits (150 words) - Safety, durability, aesthetic appeal
5. Installation Process (150 words) - Steps, timeline, professional team
6. Pricing & Packages (100 words) - Affordable rates, free quotation
7. Local Expertise (150 words) - Why we're best for ${areaName} residents
8. FAQs (200 words) - 5 common questions and answers
9. Conclusion & Call to Action (100 words)

Requirements:
- Total word count: 1200-1600 words
- Use location-specific keywords naturally
- Mention Chennai's climate (hot, humid summers; need for protection)
- Include GCM Netting Solutions as the service provider
- Professional, informative tone
- SEO-friendly with proper keyword density
- Include call-to-action to contact us

Write in HTML format with proper heading tags (h2, h3), paragraphs, and lists.`;
            
            try {
                // Call Gemini AI
                const response = await fetch(apiUrl + '?key=' + apiKey, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        contents: [{ parts: [{ text: prompt }] }],
                        generationConfig: { temperature: 0.7, maxOutputTokens: 2048 }
                    })
                });
                
                if (!response.ok) {
                    throw new Error('API error: ' + response.status);
                }
                
                const result = await response.json();
                const content = result.candidates[0].content.parts[0].text;
                const wordCount = content.split(/\s+/).length;
                
                // Save to database
                const slug = keywordText.toLowerCase().replace(/\s+/g, '-') + '-in-' + areaName.toLowerCase().replace(/\s+/g, '-');
                const title = keywordText + ' in ' + areaName + ', Chennai | GCM Netting Solutions';
                
                const saveResponse = await fetch('save-generated-page.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        keyword_id: keywordId,
                        area_id: area.id,
                        page_url: slug,
                        page_title: title,
                        content: content
                    })
                });
                
                if (!saveResponse.ok) {
                    throw new Error('Failed to save page');
                }
                
                completed++;
                addLog('✅ Generated: ' + areaName + ' (' + wordCount + ' words)', 'success');
                return { success: true };
                
            } catch (error) {
                failed++;
                addLog('❌ Failed: ' + areaName + ' - ' + error.message, 'error');
                return { success: false };
            }
        }
        
        async function processNext() {
            if (!isRunning || currentIndex >= areas.length) {
                if (currentIndex >= areas.length) {
                    addLog('🎉 Generation complete! Generated ' + completed + ' pages.', 'success');
                    document.getElementById('start-btn').style.display = 'inline-block';
                    document.getElementById('stop-btn').style.display = 'none';
                }
                return;
            }
            
            const area = areas[currentIndex];
            await generatePage(area);
            
            currentIndex++;
            updateProgress();
            
            setTimeout(processNext, 2000); // 2 second delay for API rate limiting
        }
        
        function startGeneration() {
            isRunning = true;
            document.getElementById('start-btn').style.display = 'none';
            document.getElementById('stop-btn').style.display = 'inline-block';
            addLog('🚀 Starting generation with Gemini AI...', 'info');
            processNext();
        }
        
        function stopGeneration() {
            isRunning = false;
            document.getElementById('start-btn').style.display = 'inline-block';
            document.getElementById('stop-btn').style.display = 'none';
            addLog('⏸️ Generation paused', 'info');
        }
        
        <?php if ($auto_start): ?>
        window.onload = function() {
            setTimeout(startGeneration, 1000);
        };
        <?php endif; ?>
        </script>
        
        <?php endif; ?>
    </div>
</body>
</html>
