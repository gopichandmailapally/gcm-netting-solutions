<?php
/**
 * Generate Pages with Progress Bar
 * Uses AJAX to show real-time progress
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

// Get keyword_id from URL
$keyword_id = isset($_GET['keyword_id']) ? (int)$_GET['keyword_id'] : 0;

if (!$keyword_id) {
    die('No keyword selected');
}

// Get keyword details
$keyword = $db->fetchOne(
    "SELECT k.*, k.category as service_name FROM seo_service_keywords k WHERE k.id = ?",
    [$keyword_id],
    'i'
);

if (!$keyword) {
    die('Keyword not found');
}

// Get all areas
$areas = $db->fetchAll("SELECT * FROM service_areas WHERE is_active = 1 ORDER BY area_name");
$total_areas = count($areas);

// Check how many already generated
$already_generated = $db->fetchOne(
    "SELECT COUNT(*) as count FROM generated_pages WHERE keyword_id = ?",
    [$keyword_id],
    'i'
)['count'];

$page_title = "Generate Pages: " . $keyword['keyword_name'];
require_once '../includes/header.php';
?>

<style>
body { background: #f1f5f9; min-height: 100vh; padding: 20px; }
.container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 20px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
h1 { color: #333; margin: 0 0 10px 0; }
.keyword-info { background: #F3F4F6; padding: 20px; border-radius: 10px; margin: 20px 0; }
.progress-container { margin: 30px 0; }
.progress-bar { width: 100%; height: 40px; background: #E5E7EB; border-radius: 20px; overflow: hidden; position: relative; }
.progress-fill { height: 100%; background: linear-gradient(90deg, #10B981, #059669); transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; }
.stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0; }
.stat-box { background: #F3F4F6; padding: 15px; border-radius: 10px; text-align: center; }
.stat-value { font-size: 32px; font-weight: bold; color: #10B981; }
.stat-label { color: #6B7280; font-size: 14px; margin-top: 5px; }
.log { background: #1F2937; color: #10B981; padding: 20px; border-radius: 10px; height: 300px; overflow-y: auto; font-family: monospace; font-size: 13px; margin: 20px 0; }
.log-entry { margin: 5px 0; }
.log-success { color: #10B981; }
.log-error { color: #EF4444; }
.log-info { color: #3B82F6; }
.btn { display: inline-block; padding: 15px 30px; background: linear-gradient(135deg, #10B981, #059669); color: #fff; text-decoration: none; border-radius: 10px; font-weight: 600; border: none; cursor: pointer; font-size: 16px; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-secondary { background: linear-gradient(135deg, #6B7280, #4B5563); }
</style>

<div class="container">
    <h1>🚀 Generate Pages with AI</h1>
    
    <div class="keyword-info">
        <h3><?php echo htmlspecialchars($keyword['keyword_name']); ?></h3>
        <p><strong>Service:</strong> <?php echo htmlspecialchars($keyword['service_name']); ?></p>
        <p><strong>Total Areas:</strong> <?php echo $total_areas; ?> | <strong>Already Generated:</strong> <?php echo $already_generated; ?></p>
    </div>
    
    <div class="stats">
        <div class="stat-box">
            <div class="stat-value" id="completed-count">0</div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-box">
            <div class="stat-value" id="failed-count">0</div>
            <div class="stat-label">Failed</div>
        </div>
        <div class="stat-box">
            <div class="stat-value" id="remaining-count"><?php echo $total_areas - $already_generated; ?></div>
            <div class="stat-label">Remaining</div>
        </div>
    </div>
    
    <div class="progress-container">
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill" style="width: 0%">0%</div>
        </div>
    </div>
    
    <div style="text-align: center; margin: 20px 0;">
        <button id="start-btn" class="btn" onclick="startGeneration()">
            ✨ Start Generating with Gemini AI
        </button>
        <button id="stop-btn" class="btn btn-secondary" onclick="stopGeneration()" style="display:none;">
            ⏸️ Stop
        </button>
    </div>
    
    <div class="log" id="log">
        <div class="log-info">Ready to generate <?php echo $total_areas - $already_generated; ?> pages using Gemini AI...</div>
    </div>
    
    <div style="text-align: center; margin-top: 20px;">
        <a href="generate-pages.php" class="btn btn-secondary">← Back to Generator</a>
    </div>
</div>

<script>
const keywordId = <?php echo $keyword_id; ?>;
const keywordText = <?php echo json_encode($keyword['keyword_name']); ?>;
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
    
    document.getElementById('progress-fill').style.width = percentage + '%';
    document.getElementById('progress-fill').textContent = percentage + '%';
    document.getElementById('completed-count').textContent = completed;
    document.getElementById('failed-count').textContent = failed;
    document.getElementById('remaining-count').textContent = total - processed;
}

async function generatePage(area) {
    const areaName = area.area_name;
    
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
        
        const saveResponse = await fetch('generate-single-keyword.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                keyword_id: keywordId,
                area_id: area.id,
                page_url: slug,
                page_title: title,
                content: content,
                word_count: wordCount,
                area_name: areaName,
                message: `Generated: ${keywordText} in ${areaName}`
            })
        });
        
        const saveResult = await saveResponse.json();
        
        if (saveResult.success) {
            if (saveResult.skipped) {
                addLog('Skipped: ' + areaName + ' (already exists)', 'info');
            } else {
                completed++;
                addLog('✅ Generated: ' + areaName + ' (' + wordCount + ' words)', 'success');
            }
        } else {
            throw new Error('Failed to save page');
        }
        
    } catch (error) {
        failed++;
        addLog('❌ Failed: ' + areaName + ' - ' + error.message, 'error');
    }
}

async function processNext() {
    if (!isRunning || currentIndex >= areas.length) {
        if (currentIndex >= areas.length) {
            addLog('🎉 Generation complete!', 'success');
            document.getElementById('start-btn').style.display = 'inline-block';
            document.getElementById('stop-btn').style.display = 'none';
        }
        return;
    }
    
    const area = areas[currentIndex];
    await generatePage(area);
    
    currentIndex++;
    updateProgress();
    
    // Continue with next (2 second delay for API rate limiting)
    setTimeout(processNext, 2000);
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
</script>

<?php require_once '../includes/footer.php'; ?>
