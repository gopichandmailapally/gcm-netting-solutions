<?php
/**
 * AI-Powered Page Generator
 * Generates 12,096 unique pages using Gemini API
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Load all service keywords (64 keywords)
$allKeywords = require_once '../../config/all-service-keywords.php';

// Load all areas (188 areas)
$allAreas = require_once '../../config/all-areas.php';

$page_title = 'AI Page Generator';

// Get current settings
$settings_file = '../../config/gemini-api-settings.json';
$settings = [];
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
}

$api_key = $settings['api_key'] ?? '';
$content_length = $settings['content_length'] ?? 700;
$writing_style = $settings['writing_style'] ?? 'professional';

// Count ACTUAL existing pages (not lifetime total)
$root_dir = '../../';
$existing_pages = glob($root_dir . '*-in-*.php');
$total_generated = count($existing_pages);

// Get last generation time from stats
$stats_file = '../../config/generation-stats.json';
$stats = [];
if (file_exists($stats_file)) {
    $stats = json_decode(file_get_contents($stats_file), true);
}
$last_generated = $stats['last_generated'] ?? 'Never';

// Include admin header with sidebar
include '../includes/header.php';
?>

<script>
// Make data available to JavaScript
const ALL_KEYWORDS = <?php echo json_encode($allKeywords); ?>;
const ALL_AREAS = <?php echo json_encode($allAreas); ?>;
</script>

<div class="ai-generator-page">
    <div class="page-header">
        <h1>🤖 AI Page Generator</h1>
        <p>Generate unique, SEO-optimized pages using Google Gemini AI</p>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- API Configuration -->
    <div class="config-section card">
        <h2>⚙️ API Configuration</h2>
        
        <form id="apiConfigForm" method="POST" action="save-api-settings.php">
            <div class="form-group">
                <label for="api_key">
                    <i class="fas fa-key"></i> Google Gemini API Key
                    <a href="https://makersuite.google.com/app/apikey" target="_blank" class="help-link">
                        Get API Key →
                    </a>
                </label>
                <input type="password" id="api_key" name="api_key" 
                       value="<?php echo htmlspecialchars($api_key); ?>" 
                       placeholder="Enter your Gemini API key"
                       class="form-control">
                <small>Your API key is stored securely and never shared.</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="content_length">
                        <i class="fas fa-text-height"></i> Content Length (words)
                    </label>
                    <select id="content_length" name="content_length" class="form-control">
                        <option value="600" <?php echo $content_length == 600 ? 'selected' : ''; ?>>600 words</option>
                        <option value="700" <?php echo $content_length == 700 ? 'selected' : ''; ?>>700 words</option>
                        <option value="800" <?php echo $content_length == 800 ? 'selected' : ''; ?>>800 words</option>
                        <option value="900" <?php echo $content_length == 900 ? 'selected' : ''; ?>>900 words</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="writing_style">
                        <i class="fas fa-pen-fancy"></i> Writing Style
                    </label>
                    <select id="writing_style" name="writing_style" class="form-control">
                        <option value="professional" <?php echo $writing_style == 'professional' ? 'selected' : ''; ?>>Professional</option>
                        <option value="friendly" <?php echo $writing_style == 'friendly' ? 'selected' : ''; ?>>Friendly</option>
                        <option value="technical" <?php echo $writing_style == 'technical' ? 'selected' : ''; ?>>Technical</option>
                        <option value="persuasive" <?php echo $writing_style == 'persuasive' ? 'selected' : ''; ?>>Persuasive</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Settings
            </button>
        </form>
    </div>

    <!-- Generation Options -->
    <div class="generation-section card">
        <h2>🚀 Generate Pages</h2>
        
        <div class="generation-stats">
            <div class="stat-box">
                <div class="stat-number"><?php echo number_format($total_generated); ?></div>
                <div class="stat-label">Pages Generated</div>
                <small style="color: #888; font-size: 11px;">(Currently existing files)</small>
            </div>
            <div class="stat-box">
                <div class="stat-number">12,032</div>
                <div class="stat-label">Total Target (64×188)</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $total_generated > 0 ? round(($total_generated / 12032) * 100) : 0; ?>%</div>
                <div class="stat-label">Progress</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $last_generated; ?></div>
                <div class="stat-label">Last Generated</div>
            </div>
        </div>

        <form id="generationForm">
            <div class="generation-mode">
                <h3>Select Generation Mode:</h3>
                
                <label class="radio-card">
                    <input type="radio" name="mode" value="single" checked>
                    <div class="radio-content">
                        <i class="fas fa-file"></i>
                        <strong>Single Page</strong>
                        <span>Generate one specific page</span>
                    </div>
                </label>

                <label class="radio-card">
                    <input type="radio" name="mode" value="service">
                    <div class="radio-content">
                        <i class="fas fa-layer-group"></i>
                        <strong>By Service</strong>
                        <span>Generate all pages for one service (188 pages)</span>
                    </div>
                </label>

                <label class="radio-card">
                    <input type="radio" name="mode" value="area">
                    <div class="radio-content">
                        <i class="fas fa-map-marker-alt"></i>
                        <strong>By Area</strong>
                        <span>Generate all pages for one area (64 pages)</span>
                    </div>
                </label>

                <label class="radio-card">
                    <input type="radio" name="mode" value="all">
                    <div class="radio-content">
                        <i class="fas fa-globe"></i>
                        <strong>Generate All</strong>
                        <span>Generate all 12,096 pages (~6-10 hours)</span>
                    </div>
                </label>
            </div>

            <!-- Single Page Options -->
            <div id="singleOptions" class="mode-options">
                <div class="form-row">
                    <div class="form-group">
                        <label>Service (64 keywords available)</label>
                        <select id="single_service" class="form-control">
                            <option value="">Select Service...</option>
                            <?php
                            // Group keywords by category
                            $categorized = [];
                            foreach ($allKeywords as $kw) {
                                $categorized[$kw['category']][] = $kw;
                            }
                            
                            // Display by category
                            foreach ($categorized as $category => $keywords) {
                                echo "<optgroup label=\"$category\">";
                                foreach ($keywords as $kw) {
                                    echo "<option value=\"{$kw['slug']}\" data-name=\"{$kw['keyword']}\">{$kw['keyword']}</option>";
                                }
                                echo "</optgroup>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Area (188 areas available)</label>
                        <select id="single_area" class="form-control">
                            <option value="">Select Area...</option>
                            <?php foreach ($allAreas as $area): ?>
                                <option value="<?php echo htmlspecialchars($area['slug']); ?>" data-name="<?php echo htmlspecialchars($area['area']); ?>">
                                    <?php echo htmlspecialchars($area['area']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- By Service Options (150 pages per service) -->
            <div id="serviceOptions" class="mode-options" style="display: none;">
                <div class="form-group">
                    <label>Select Service (will generate 188 pages - one for each area)</label>
                    <select id="bulk_service" class="form-control">
                        <option value="">Select Service...</option>
                        <?php
                        foreach ($categorized as $category => $keywords) {
                            echo "<optgroup label=\"$category\">";
                            foreach ($keywords as $kw) {
                                echo "<option value=\"{$kw['slug']}\" data-name=\"{$kw['keyword']}\">{$kw['keyword']}</option>";
                            }
                            echo "</optgroup>";
                        }
                        ?>
                    </select>
                </div>
                <p style="color: #666; font-size: 14px;">
                    ℹ️ This will generate 188 pages (one for each Chennai area) for the selected service.
                </p>
            </div>

            <!-- By Area Options (64 pages per area) -->
            <div id="areaOptions" class="mode-options" style="display: none;">
                <div class="form-group">
                    <label>Select Area (will generate 64 pages - one for each service keyword)</label>
                    <select id="bulk_area" class="form-control">
                        <option value="">Select Area...</option>
                        <?php foreach ($allAreas as $area): ?>
                            <option value="<?php echo htmlspecialchars($area['slug']); ?>" data-name="<?php echo htmlspecialchars($area['area']); ?>">
                                <?php echo htmlspecialchars($area['area']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p style="color: #666; font-size: 14px;">
                    ℹ️ This will generate 64 pages (one for each service keyword) for the selected area.
                </p>
            </div>

            <!-- Generate All Options -->
            <div id="allOptions" class="mode-options" style="display: none;">
                <div style="background: #FEF3C7; border: 2px solid #F59E0B; padding: 20px; border-radius: 8px;">
                    <h4 style="margin: 0 0 10px 0; color: #D97706;">⚠️ Warning: Large Operation</h4>
                    <p style="margin: 0; color: #666;">
                        This will generate ALL 12,032 pages (64 keywords × 188 areas).
                        Estimated time: <strong>6-10 hours</strong>. Make sure you have:
                    </p>
                    <ul style="margin: 10px 0; padding-left: 20px; color: #666;">
                        <li>Stable internet connection</li>
                        <li>Sufficient API quota</li>
                        <li>Keep this tab open during generation</li>
                    </ul>
                </div>
            </div>

            <div class="cost-estimate">
                <h4>📊 Estimated Cost & Time:</h4>
                <div id="estimateDisplay">
                    <p><strong>Pages:</strong> <span id="estPages">1</span></p>
                    <p><strong>Time:</strong> <span id="estTime">~3 seconds</span></p>
                    <p><strong>API Cost:</strong> ₹<span id="estCost">0.05</span></p>
                </div>
            </div>

            <button type="button" id="startGeneration" class="btn btn-success btn-lg">
                <i class="fas fa-rocket"></i> Start Generation
            </button>
        </form>
    </div>

    <!-- Progress Section -->
    <div id="progressSection" class="progress-section card" style="display: none;">
        <h2>⚡ Generation in Progress...</h2>
        
        <div class="progress-bar-container">
            <div class="progress-bar" id="progressBar">
                <span id="progressPercent">0%</span>
            </div>
        </div>

        <div class="progress-details">
            <p><strong>Status:</strong> <span id="statusText">Initializing...</span></p>
            <p><strong>Current Page:</strong> <span id="currentPage">-</span></p>
            <p><strong>Generated:</strong> <span id="generatedCount">0</span> / <span id="totalCount">0</span></p>
            <p><strong>Time Elapsed:</strong> <span id="timeElapsed">00:00:00</span></p>
            <p><strong>Est. Remaining:</strong> <span id="timeRemaining">Calculating...</span></p>
        </div>

        <div class="progress-log">
            <h4>Recent Activity:</h4>
            <div id="activityLog" class="log-container"></div>
        </div>

        <button type="button" id="pauseGeneration" class="btn btn-warning">
            <i class="fas fa-pause"></i> Pause
        </button>
        <button type="button" id="stopGeneration" class="btn btn-danger">
            <i class="fas fa-stop"></i> Stop
        </button>
    </div>
</div>

<style>
.ai-generator-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
}

.page-header h1 {
    font-size: 36px;
    color: #1E293B;
    margin-bottom: 8px;
}

.page-header p {
    font-size: 16px;
    color: #64748B;
}

.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 15px;
    font-weight: 500;
    animation: slideIn 0.3s ease;
}

.alert i {
    font-size: 20px;
}

.alert-success {
    background: #D1FAE5;
    border: 2px solid #10B981;
    color: #065F46;
}

.alert-error {
    background: #FEE2E2;
    border: 2px solid #EF4444;
    color: #991B1B;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    background: #FFFFFF;
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.card h2 {
    font-size: 24px;
    color: #1E293B;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #475569;
    margin-bottom: 8px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #E2E8F0;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #3B82F6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.help-link {
    float: right;
    color: #3B82F6;
    font-size: 14px;
    text-decoration: none;
}

.generation-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-box {
    background: #ffffff;
    color: #1e293b;
    padding: 24px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.stat-number {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 8px;
}

.stat-label {
    font-size: 14px;
    opacity: 0.9;
}

.generation-mode {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin: 24px 0;
}

.radio-card {
    cursor: pointer;
    border: 2px solid #E2E8F0;
    border-radius: 12px;
    padding: 20px;
    transition: all 0.3s ease;
    background: white;
}

.radio-card:hover {
    border-color: #3B82F6;
    background: #F8FAFC;
}

.radio-card input[type="radio"] {
    display: none;
}

/* Active/Selected state - keep the card visibly selected */
.radio-card:has(input[type="radio"]:checked) {
    border-color: #3B82F6 !important;
    background: #EFF6FF !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.radio-card input[type="radio"]:checked + .radio-content i {
    color: #3B82F6;
    transform: scale(1.1);
}

.radio-card input[type="radio"]:checked + .radio-content strong {
    color: #3B82F6;
    font-weight: 700;
}

.radio-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 8px;
}

.radio-content i {
    font-size: 32px;
    color: #94A3B8;
    transition: all 0.3s ease;
}

.radio-content strong {
    font-size: 16px;
    color: #1E293B;
    transition: all 0.3s ease;
}

.radio-content span {
    font-size: 13px;
    color: #64748B;
}

.mode-options {
    display: block; /* Show by default since Single Page is selected */
    margin: 20px 0;
}

.cost-estimate {
    background: #F8FAFC;
    padding: 20px;
    border-radius: 12px;
    margin: 24px 0;
}

.cost-estimate h4 {
    margin-bottom: 16px;
    color: #475569;
}

.btn {
    padding: 14px 28px;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #3B82F6, #8B5CF6);
    color: white;
}

.btn-success {
    background: linear-gradient(135deg, #10B981, #059669);
    color: white;
}

.btn-warning {
    background: linear-gradient(135deg, #F59E0B, #D97706);
    color: white;
}

.btn-danger {
    background: linear-gradient(135deg, #EF4444, #DC2626);
    color: white;
}

.btn-lg {
    padding: 18px 36px;
    font-size: 18px;
    width: 100%;
    justify-content: center;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.progress-bar-container {
    background: #E2E8F0;
    height: 40px;
    border-radius: 20px;
    overflow: hidden;
    margin: 24px 0;
}

.progress-bar {
    background: linear-gradient(90deg, #10B981, #3B82F6);
    height: 100%;
    width: 0%;
    transition: width 0.5s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
}

.progress-details {
    background: #F8FAFC;
    padding: 20px;
    border-radius: 12px;
    margin: 24px 0;
}

.progress-details p {
    margin: 8px 0;
    font-size: 15px;
}

.log-container {
    background: #1E293B;
    color: #E2E8F0;
    padding: 16px;
    border-radius: 8px;
    height: 300px;
    overflow-y: auto;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.6;
}
</style>

<script>
document.getElementById('startGeneration').addEventListener('click', function() {
    const apiKey = document.getElementById('api_key').value;
    
    if (!apiKey) {
        alert('Please enter your Gemini API key first!');
        return;
    }
    
    // Get selected mode
    const mode = document.querySelector('input[name="mode"]:checked').value;
    
    // Validate based on mode
    if (mode === 'single') {
        const service = document.getElementById('single_service').value;
        const area = document.getElementById('single_area').value;
        if (!service || !area) {
            alert('Please select both service and area for single page generation!');
            return;
        }
    } else if (mode === 'service') {
        const service = document.getElementById('bulk_service').value;
        if (!service) {
            alert('Please select a service to generate 150 pages!');
            return;
        }
    } else if (mode === 'area') {
        const area = document.getElementById('bulk_area').value;
        if (!area) {
            alert('Please select an area to generate 64 pages!');
            return;
        }
    }
    
    if (confirm('Start AI page generation? This will consume API credits.')) {
        startGeneration();
    }
});

// Show/hide options based on mode
document.querySelectorAll('input[name="mode"]').forEach(radio => {
    radio.addEventListener('change', function() {
        // Hide all option divs
        document.getElementById('singleOptions').style.display = 'none';
        document.getElementById('serviceOptions').style.display = 'none';
        document.getElementById('areaOptions').style.display = 'none';
        document.getElementById('allOptions').style.display = 'none';
        
        // Show the selected mode's options
        if (this.value === 'single') {
            document.getElementById('singleOptions').style.display = 'block';
            updateEstimate(1, 3);
        } else if (this.value === 'service') {
            document.getElementById('serviceOptions').style.display = 'block';
            updateEstimate(188, 564); // 188 areas × 3 seconds
        } else if (this.value === 'area') {
            document.getElementById('areaOptions').style.display = 'block';
            updateEstimate(64, 192); // 64 keywords × 3 seconds
        } else if (this.value === 'all') {
            document.getElementById('allOptions').style.display = 'block';
            updateEstimate(12032, 36096); // 12032 pages × 3 seconds = 10 hours
        }
    });
});

function updateEstimate(pages, seconds) {
    document.getElementById('estPages').textContent = pages.toLocaleString();
    
    // Format time
    let timeText;
    if (seconds < 60) {
        timeText = `~${seconds} seconds`;
    } else if (seconds < 3600) {
        timeText = `~${Math.round(seconds / 60)} minutes`;
    } else {
        timeText = `~${Math.round(seconds / 3600)} hours`;
    }
    document.getElementById('estTime').textContent = timeText;
    
    // Calculate cost (approximate ₹0.05 per page)
    const cost = (pages * 0.05).toFixed(2);
    document.getElementById('estCost').textContent = cost;
}

function startGeneration() {
    // Reset flags
    isCancelled = false;
    isPaused = false;
    isGenerating = true;
    
    // Show progress section
    document.getElementById('progressSection').style.display = 'block';
    document.getElementById('progressSection').scrollIntoView({ behavior: 'smooth' });
    
    // Enable and reset buttons
    const pauseBtn = document.getElementById('pauseGeneration');
    const stopBtn = document.getElementById('stopGeneration');
    pauseBtn.disabled = false;
    stopBtn.disabled = false;
    pauseBtn.innerHTML = '<i class="fas fa-pause"></i> Pause';
    pauseBtn.classList.remove('btn-success');
    pauseBtn.classList.add('btn-warning');
    
    // Get form data
    const apiKey = document.getElementById('api_key').value;
    const mode = document.querySelector('input[name="mode"]:checked').value;
    const contentLength = document.getElementById('content_length').value;
    
    // Update status
    document.getElementById('statusText').textContent = 'Connecting to Gemini AI...';
    addLog('Starting generation process...');
    
    // For single page mode
    if (mode === 'single') {
        const serviceSlug = document.getElementById('single_service').value;
        const areaSlug = document.getElementById('single_area').value;
        const serviceName = document.getElementById('single_service').options[document.getElementById('single_service').selectedIndex].text;
        const areaName = document.getElementById('single_area').options[document.getElementById('single_area').selectedIndex].text;
        
        generateSinglePage(apiKey, serviceSlug, serviceName, areaSlug, areaName, contentLength);
    } else if (mode === 'service') {
        // Generate 188 pages for one service (all areas)
        const serviceSlug = document.getElementById('bulk_service').value;
        const serviceName = document.getElementById('bulk_service').options[document.getElementById('bulk_service').selectedIndex].text;
        generateByService(apiKey, serviceSlug, serviceName, contentLength);
    } else if (mode === 'area') {
        // Generate 64 pages for one area (all services)
        const areaSlug = document.getElementById('bulk_area').value;
        const areaName = document.getElementById('bulk_area').options[document.getElementById('bulk_area').selectedIndex].text;
        generateByArea(apiKey, areaSlug, areaName, contentLength);
    } else if (mode === 'all') {
        // Generate all 12,032 pages
        generateAll(apiKey, contentLength);
    }
}

function generateSinglePage(apiKey, serviceSlug, serviceName, areaSlug, areaName, wordCount) {
    addLog(`Generating: ${serviceName} in ${areaName}...`);
    document.getElementById('statusText').textContent = 'Calling Gemini AI...';
    document.getElementById('currentPage').textContent = `${serviceName} in ${areaName}`;
    document.getElementById('totalCount').textContent = '1';
    
    // Call backend API
    fetch('../api/generate-pages-api.php?action=generate_single', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'generate_single',
            api_key: apiKey,
            service_slug: serviceSlug,
            service_name: serviceName,
            area_slug: areaSlug,
            area_name: areaName,
            word_count: parseInt(wordCount)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addLog(`✅ Success! Generated: ${data.file_name}`);
            addLog(`📊 Word count: ${data.word_count} words`);
            addLog(`⏱️ Time taken: ${data.generation_time} seconds`);
            
            document.getElementById('statusText').textContent = 'Completed!';
            document.getElementById('generatedCount').textContent = '1';
            document.getElementById('progressBar').style.width = '100%';
            document.getElementById('progressPercent').textContent = '100%';
            
            addLog(`🎉 Page available at: /${data.file_name}`);
            addLog('✨ Generation complete!');
        } else {
            addLog(`❌ Error: ${data.error}`);
            document.getElementById('statusText').textContent = 'Failed!';
        }
        
        // Disable buttons after single page completion
        isGenerating = false;
        document.getElementById('pauseGeneration').disabled = true;
        document.getElementById('stopGeneration').disabled = true;
    })
    .catch(error => {
        addLog(`❌ Network Error: ${error.message}`);
        document.getElementById('statusText').textContent = 'Connection failed!';
        
        // Disable buttons on error
        isGenerating = false;
        document.getElementById('pauseGeneration').disabled = true;
        document.getElementById('stopGeneration').disabled = true;
    });
}

function addLog(message) {
    const logContainer = document.getElementById('activityLog');
    const timestamp = new Date().toLocaleTimeString();
    const logEntry = document.createElement('div');
    logEntry.textContent = `[${timestamp}] ${message}`;
    logContainer.appendChild(logEntry);
    logContainer.scrollTop = logContainer.scrollHeight;
}

// Batch Generation Functions
let isCancelled = false;
let isPaused = false;
let isGenerating = false;

async function generateByService(apiKey, serviceSlug, serviceName, wordCount) {
    addLog(`📦 Starting batch generation: ${serviceName} for all ${ALL_AREAS.length} areas`);
    document.getElementById('totalCount').textContent = ALL_AREAS.length;
    
    let successCount = 0;
    let failCount = 0;
    
    for (let i = 0; i < ALL_AREAS.length; i++) {
        // Check if stopped
        if (isCancelled) {
            addLog('🛑 Generation stopped by user');
            break;
        }
        
        // Check if paused
        while (isPaused) {
            await sleep(500); // Check every 500ms if resumed
            if (isCancelled) break;
        }
        
        const area = ALL_AREAS[i];
        const current = i + 1;
        const percent = Math.round((current / ALL_AREAS.length) * 100);
        
        document.getElementById('currentPage').textContent = `${serviceName} in ${area.area}`;
        document.getElementById('generatedCount').textContent = current;
        document.getElementById('progressBar').style.width = percent + '%';
        document.getElementById('progressPercent').textContent = percent + '%';
        
        addLog(`[${current}/${ALL_AREAS.length}] Generating: ${area.area}...`);
        
        try {
            const result = await generatePageAPI(apiKey, serviceSlug, serviceName, area.slug, area.area, wordCount);
            if (result.success) {
                successCount++;
                addLog(`✅ Success: ${result.file_name}`);
            } else {
                failCount++;
                addLog(`❌ Failed: ${area.area} - ${result.error}`);
            }
        } catch (error) {
            failCount++;
            addLog(`❌ Error: ${area.area} - ${error.message}`);
        }
        
        // Small delay to avoid rate limiting
        await sleep(1000);
    }
    
    addLog(`🎉 Batch complete! Success: ${successCount}, Failed: ${failCount}`);
    document.getElementById('statusText').textContent = 'Batch Completed!';
    isGenerating = false;
    
    // Disable buttons after completion
    document.getElementById('pauseGeneration').disabled = true;
    document.getElementById('stopGeneration').disabled = true;
}

async function generateByArea(apiKey, areaSlug, areaName, wordCount) {
    addLog(`📦 Starting batch generation: ${areaName} for all ${ALL_KEYWORDS.length} services`);
    document.getElementById('totalCount').textContent = ALL_KEYWORDS.length;
    
    let successCount = 0;
    let failCount = 0;
    
    for (let i = 0; i < ALL_KEYWORDS.length; i++) {
        // Check if stopped
        if (isCancelled) {
            addLog('🛑 Generation stopped by user');
            break;
        }
        
        // Check if paused
        while (isPaused) {
            await sleep(500);
            if (isCancelled) break;
        }
        
        const keyword = ALL_KEYWORDS[i];
        const current = i + 1;
        const percent = Math.round((current / ALL_KEYWORDS.length) * 100);
        
        document.getElementById('currentPage').textContent = `${keyword.keyword} in ${areaName}`;
        document.getElementById('generatedCount').textContent = current;
        document.getElementById('progressBar').style.width = percent + '%';
        document.getElementById('progressPercent').textContent = percent + '%';
        
        addLog(`[${current}/${ALL_KEYWORDS.length}] Generating: ${keyword.keyword}...`);
        
        try {
            const result = await generatePageAPI(apiKey, keyword.slug, keyword.keyword, areaSlug, areaName, wordCount);
            if (result.success) {
                successCount++;
                addLog(`✅ Success: ${result.file_name}`);
            } else {
                failCount++;
                addLog(`❌ Failed: ${keyword.keyword} - ${result.error}`);
            }
        } catch (error) {
            failCount++;
            addLog(`❌ Error: ${keyword.keyword} - ${error.message}`);
        }
        
        await sleep(1000);
    }
    
    addLog(`🎉 Batch complete! Success: ${successCount}, Failed: ${failCount}`);
    document.getElementById('statusText').textContent = 'Batch Completed!';
    isGenerating = false;
    
    // Disable buttons after completion
    document.getElementById('pauseGeneration').disabled = true;
    document.getElementById('stopGeneration').disabled = true;
}

async function generateAll(apiKey, wordCount) {
    const totalPages = ALL_KEYWORDS.length * ALL_AREAS.length;
    addLog(`📦 Starting FULL generation: ALL ${totalPages} pages (${ALL_KEYWORDS.length} services × ${ALL_AREAS.length} areas)`);
    document.getElementById('totalCount').textContent = totalPages;
    
    let successCount = 0;
    let failCount = 0;
    let pageNum = 0;
    
    for (let k = 0; k < ALL_KEYWORDS.length; k++) {
        if (isCancelled) break;
        
        const keyword = ALL_KEYWORDS[k];
        
        for (let a = 0; a < ALL_AREAS.length; a++) {
            // Check if stopped
            if (isCancelled) {
                addLog('🛑 Generation stopped by user');
                break;
            }
            
            // Check if paused
            while (isPaused) {
                await sleep(500);
                if (isCancelled) break;
            }
            
            const area = ALL_AREAS[a];
            pageNum++;
            const percent = Math.round((pageNum / totalPages) * 100);
            
            document.getElementById('currentPage').textContent = `${keyword.keyword} in ${area.area}`;
            document.getElementById('generatedCount').textContent = pageNum;
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressPercent').textContent = percent + '%';
            
            addLog(`[${pageNum}/${totalPages}] Generating: ${keyword.keyword} in ${area.area}...`);
            
            try {
                const result = await generatePageAPI(apiKey, keyword.slug, keyword.keyword, area.slug, area.area, wordCount);
                if (result.success) {
                    successCount++;
                } else {
                    failCount++;
                    addLog(`❌ Failed: ${result.error}`);
                }
            } catch (error) {
                failCount++;
                addLog(`❌ Error: ${error.message}`);
            }
            
            await sleep(1000);
        }
    }
    
    addLog(`🎉 Full generation complete! Success: ${successCount}, Failed: ${failCount}`);
    document.getElementById('statusText').textContent = 'All Pages Generated!';
    isGenerating = false;
    
    // Disable buttons after completion
    document.getElementById('pauseGeneration').disabled = true;
    document.getElementById('stopGeneration').disabled = true;
}

// Helper function to call the generation API
function generatePageAPI(apiKey, serviceSlug, serviceName, areaSlug, areaName, wordCount) {
    return fetch('../api/generate-pages-api.php?action=generate_single', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'generate_single',
            api_key: apiKey,
            service_slug: serviceSlug,
            service_name: serviceName,
            area_slug: areaSlug,
            area_name: areaName,
            word_count: parseInt(wordCount)
        })
    }).then(response => response.json());
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Pause/Resume button functionality
document.getElementById('pauseGeneration')?.addEventListener('click', function() {
    const pauseBtn = document.getElementById('pauseGeneration');
    
    if (isPaused) {
        // Resume generation
        isPaused = false;
        pauseBtn.innerHTML = '<i class="fas fa-pause"></i> Pause';
        pauseBtn.classList.remove('btn-success');
        pauseBtn.classList.add('btn-warning');
        document.getElementById('statusText').textContent = 'Resumed generation...';
        addLog('▶️ Generation resumed');
    } else {
        // Pause generation
        isPaused = true;
        pauseBtn.innerHTML = '<i class="fas fa-play"></i> Resume';
        pauseBtn.classList.remove('btn-warning');
        pauseBtn.classList.add('btn-success');
        document.getElementById('statusText').textContent = 'Paused - Click Resume to continue';
        addLog('⏸️ Generation paused');
    }
});

// Stop button functionality
document.getElementById('stopGeneration')?.addEventListener('click', function() {
    if (confirm('Are you sure you want to stop generation? Progress will be saved but remaining pages will not be generated.')) {
        isCancelled = true;
        isPaused = false; // Make sure it's not paused
        document.getElementById('statusText').textContent = 'Stopped by user';
        addLog('🛑 Generation stopped by user');
        
        // Disable buttons
        document.getElementById('pauseGeneration').disabled = true;
        document.getElementById('stopGeneration').disabled = true;
    }
});

// ===================================
// LIVE STATS UPDATE SYSTEM (Safe Version)
// ===================================
let liveUpdateInterval = null;

// Function to update stats in real-time
async function updateLiveStats() {
    try {
        const response = await fetch('../api/get-page-count.php');
        const data = await response.json();
        
        if (data.success) {
            // Update Pages Generated
            const pagesEl = document.querySelector('.stat-box:nth-child(1) .stat-number');
            if (pagesEl && pagesEl.textContent != data.total_generated.toLocaleString()) {
                pagesEl.textContent = data.total_generated.toLocaleString();
                pagesEl.style.transition = 'all 0.3s ease';
                pagesEl.style.transform = 'scale(1.1)';
                pagesEl.style.color = '#10B981';
                setTimeout(() => {
                    pagesEl.style.transform = 'scale(1)';
                    pagesEl.style.color = '';
                }, 300);
            }
            
            // Update Progress %
            const progressEl = document.querySelector('.stat-box:nth-child(3) .stat-number');
            if (progressEl && progressEl.textContent != data.progress_percentage + '%') {
                progressEl.textContent = data.progress_percentage + '%';
                progressEl.style.transition = 'all 0.3s ease';
                progressEl.style.transform = 'scale(1.1)';
                progressEl.style.color = '#10B981';
                setTimeout(() => {
                    progressEl.style.transform = 'scale(1)';
                    progressEl.style.color = '';
                }, 300);
            }
        }
    } catch (error) {
        console.error('Stats update error:', error);
    }
}

// Start live updates
function startLiveUpdates() {
    if (!liveUpdateInterval) {
        console.log('🔄 Live stats started');
        updateLiveStats(); // Immediate update
        liveUpdateInterval = setInterval(updateLiveStats, 3000); // Every 3 seconds
    }
}

// Stop live updates
function stopLiveUpdates() {
    if (liveUpdateInterval) {
        clearInterval(liveUpdateInterval);
        liveUpdateInterval = null;
        console.log('⏹️ Live stats stopped');
        updateLiveStats(); // Final update
    }
}

// Detect when Start Generation button is clicked (after the click happens)
document.addEventListener('click', function(e) {
    const btn = e.target.closest('button');
    if (btn && btn.textContent.includes('Start Generation')) {
        console.log('🚀 Generation detected - starting live stats');
        // Wait a moment for generation to start, then begin polling
        setTimeout(startLiveUpdates, 1000);
        
        // Auto-stop after 10 minutes (safety)
        setTimeout(stopLiveUpdates, 600000);
    }
});

// Watch for completion messages in the log
setInterval(function() {
    const logEl = document.getElementById('generationLog');
    if (logEl && logEl.textContent.includes('✅ All pages generated successfully')) {
        stopLiveUpdates();
    }
}, 2000);

// Initial update on page load
updateLiveStats();
</script>

<?php include '../includes/footer.php'; ?>
