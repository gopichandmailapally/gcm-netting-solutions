<?php
/**
 * Optimized Bulk Page Generation API
 * Generates multiple pages with improved speed and efficiency
 */

define('GCM_INIT', true);

// Increase execution time and memory for bulk operations
set_time_limit(0);
ini_set('memory_limit', '512M');

// Start output buffering
ob_start();

// Set JSON header
header('Content-Type: application/json');

// Load required files
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/gemini-api.php';
require_once __DIR__ . '/../../includes/ai-prompt-generator.php';

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'bulk_generate':
        bulkGeneratePages($input);
        break;
    
    case 'get_queue_status':
        getQueueStatus();
        break;
    
    default:
        echo json_encode(['error' => 'Invalid action']);
}

/**
 * Bulk generate pages with optimized processing
 */
function bulkGeneratePages($input) {
    $apiKey = $input['api_key'] ?? '';
    $services = $input['services'] ?? [];
    $areas = $input['areas'] ?? [];
    $wordCount = $input['word_count'] ?? 700;
    $batchSize = $input['batch_size'] ?? 10; // Process 10 at a time
    
    if (!$apiKey || empty($services) || empty($areas)) {
        echo json_encode(['error' => 'Missing required parameters']);
        return;
    }
    
    $gemini = new GeminiAPI($apiKey);
    $promptGen = new AIPromptGenerator();
    $titleFormats = include __DIR__ . '/../../config/title-formats.php';
    
    $results = [
        'success' => 0,
        'failed' => 0,
        'total' => count($services) * count($areas),
        'pages' => []
    ];
    
    $protectedDir = __DIR__ . "/../../generated-pages/";
    if (!is_dir($protectedDir)) {
        mkdir($protectedDir, 0755, true);
    }
    
    $batchCount = 0;
    
    foreach ($services as $service) {
        foreach ($areas as $area) {
            try {
                // Generate prompt
                $prompt = $promptGen->generatePagePrompt(
                    $service['name'], 
                    $service['slug'], 
                    $area['name'], 
                    $area['slug'], 
                    $wordCount
                );
                
                // Call Gemini API
                $startTime = microtime(true);
                $aiContent = $gemini->generateContent($prompt);
                $generationTime = microtime(true) - $startTime;
                
                // Clean content
                $aiContent = cleanMarkdownArtifacts($aiContent);
                
                // Generate page metadata
                $randomTitleFormat = $titleFormats[array_rand($titleFormats)];
                $pageTitle = str_replace(
                    ['{service}', '{area}', '{company}'],
                    [$service['name'], $area['name'], 'GCM Netting Solutions'],
                    $randomTitleFormat
                );
                
                $h1Heading = "{$service['name']} in {$area['name']}, Chennai";
                $metaDescription = generateMetaDescription($service['name'], $area['name']);
                
                // Create file
                $fileName = "{$service['slug']}-in-{$area['slug']}.php";
                $filePath = $protectedDir . $fileName;
                
                $pageContent = createPageFile(
                    $pageTitle, 
                    $h1Heading, 
                    $metaDescription, 
                    $aiContent, 
                    $service['name'], 
                    $service['slug'], 
                    $area['name'], 
                    $area['slug']
                );
                
                file_put_contents($filePath, $pageContent);
                chmod($filePath, 0644);

                // Auto-protect: register with AI Content Security
                $_acp = dirname(__DIR__) . '/includes/ai-content-protection.php';
                if (file_exists($_acp) && !class_exists('AIContentProtection')) require_once $_acp;
                if (class_exists('AIContentProtection')) {
                    $_pg_slug = pathinfo($fileName, PATHINFO_FILENAME);
                    try { (new AIContentProtection())->protect('service_page', $_pg_slug, $pageTitle, 'generated-pages/' . $fileName); }
                    catch (\Exception $e) { /* non-fatal */ }
                }

                // Log success
                $results['success']++;
                $results['pages'][] = [
                    'file' => $fileName,
                    'status' => 'success',
                    'time' => round($generationTime, 2)
                ];
                
                // Save to database
                savePageToDatabase([
                    'service_slug' => $service['slug'],
                    'service_name' => $service['name'],
                    'area_slug' => $area['slug'],
                    'area_name' => $area['name'],
                    'page_title' => $pageTitle,
                    'meta_description' => $metaDescription,
                    'h1_heading' => $h1Heading,
                    'file_path' => 'generated-pages/' . $fileName,
                    'generation_time' => $generationTime,
                    'word_count' => str_word_count($aiContent),
                    'generated_at' => date('Y-m-d H:i:s')
                ]);
                
                $batchCount++;
                
                // Add delay every batch to avoid rate limits
                if ($batchCount % $batchSize === 0) {
                    sleep(2); // 2 second delay between batches
                }
                
            } catch (Exception $e) {
                $results['failed']++;
                $results['pages'][] = [
                    'file' => "{$service['slug']}-in-{$area['slug']}.php",
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }
    }
    
    ob_clean();
    echo json_encode($results);
}

/**
 * Get queue status
 */
function getQueueStatus() {
    $statsFile = __DIR__ . '/../../config/generation-stats.json';
    
    if (file_exists($statsFile)) {
        $stats = json_decode(file_get_contents($statsFile), true);
        echo json_encode($stats);
    } else {
        echo json_encode([
            'total_generated' => 0,
            'in_progress' => 0,
            'last_generated' => 'Never'
        ]);
    }
}

/**
 * Clean markdown artifacts
 */
function cleanMarkdownArtifacts($content) {
    $content = preg_replace('/```html\s*/i', '', $content);
    $content = preg_replace('/```\s*$/s', '', $content);
    $content = preg_replace('/```/', '', $content);
    $content = trim($content, '`');
    $content = preg_replace('/^html\s+/i', '', $content);
    return trim($content);
}

/**
 * Generate meta description
 */
function generateMetaDescription($serviceName, $areaName) {
    $templates = [
        "Professional {service} installation in {area}, Chennai. Quality materials, expert installation, 5-year warranty. Call +91 99123 99224 for free quote.",
        "Expert {service} services in {area}, Chennai. Serving residential & commercial clients with premium solutions. Free consultation available.",
        "Get quality {service} installation in {area}, Chennai. Certified installers, weather-resistant materials, affordable prices. Call now!"
    ];
    
    $template = $templates[array_rand($templates)];
    return str_replace(['{service}', '{area}'], [$serviceName, $areaName], $template);
}

/**
 * Create page file content
 */
function createPageFile($pageTitle, $h1Heading, $metaDescription, $aiContent, $serviceName, $serviceSlug, $areaName, $areaSlug) {
    return "<?php
/**
 * {$pageTitle}
 * Auto-generated service page
 */
define('GCM_INIT', true);
require_once __DIR__ . '/../config/config.php';

\$page_title = '{$pageTitle}';
\$meta_description = '{$metaDescription}';
\$current_page = 'services';

include __DIR__ . '/../includes/modern-header.php';
?>

<div class=\"service-page\">
    <div class=\"service-header\">
        <div class=\"container\">
            <h1>{$h1Heading}</h1>
            <p>Professional {$serviceName} installation services in {$areaName}</p>
        </div>
    </div>
    
    <div class=\"container\">
        <div class=\"service-content\">
            {$aiContent}
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/modern-footer.php'; ?>
";
}

/**
 * Save page to database
 */
function savePageToDatabase($pageData) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            return false;
        }
        
        $stmt = $conn->prepare("INSERT INTO generated_pages (service_slug, service_name, area_slug, area_name, page_title, meta_description, h1_heading, file_path, generation_time, word_count, generated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param(
            'ssssssssdis',
            $pageData['service_slug'],
            $pageData['service_name'],
            $pageData['area_slug'],
            $pageData['area_name'],
            $pageData['page_title'],
            $pageData['meta_description'],
            $pageData['h1_heading'],
            $pageData['file_path'],
            $pageData['generation_time'],
            $pageData['word_count'],
            $pageData['generated_at']
        );
        
        $result = $stmt->execute();
        $stmt->close();
        $conn->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Database save error: " . $e->getMessage());
        return false;
    }
}
