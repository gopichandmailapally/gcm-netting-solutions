<?php
/**
 * Daily FAQ Generator Cron Job
 * Runs at 2:00 AM daily to auto-generate FAQs
 * 
 * Add to crontab:
 * 0 2 * * * /usr/bin/php /var/www/html/gcmsafetynets/cron/daily-faq-generator.php
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/gemini-api.php';

$log_file = dirname(__DIR__) . '/logs/faq-cron.log';

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

log_message("=== Daily FAQ Generation Started ===");

try {
    $db = Database::getInstance();
    $gemini = new GeminiAPI(GEMINI_API_KEY);
    
    // Check if auto-generation is enabled
    $settings = $db->fetchOne("SELECT * FROM faq_settings WHERE id = 1");
    
    if (!$settings['auto_generate_enabled']) {
        log_message("Auto-generation is disabled. Exiting.");
        exit;
    }
    
    $daily_count = $settings['daily_faq_count'] ?? 1;
    log_message("Target: Generate $daily_count FAQs");
    
    // Get active categories
    $categories = $db->fetchAll("SELECT slug, name FROM faq_categories WHERE is_active = 1");
    
    if (empty($categories)) {
        log_message("ERROR: No active categories found");
        exit;
    }
    
    // FAQ topics
    $topics = [
        'safety net installation process in Chennai',
        'types of safety nets available for different needs',
        'cost estimation and pricing factors',
        'warranty coverage and duration details',
        'maintenance tips and cleaning procedures',
        'material quality and HDPE specifications',
        'service areas covered in Chennai',
        'installation timeline and scheduling',
        'pigeon net effectiveness and benefits',
        'balcony safety solutions for children',
        'bird protection and prevention methods',
        'cricket practice net specifications',
        'construction site safety net requirements',
        'customization and color options',
        'weather resistance and UV protection',
        'safety certifications and compliance',
        'free site inspection process',
        'payment methods and installment options',
        'emergency installation services',
        'net lifespan and replacement indicators',
        'monkey net installation in residential areas',
        'debris net for construction sites',
        'anti-bird spikes vs bird nets comparison',
        'net strength and load capacity',
        'installation on high-rise buildings',
        'pet safety with balcony nets',
        'seasonal maintenance requirements',
        'net removal and re-installation',
        'bulk order discounts for builders',
        'after-sales service and support'
    ];
    
    shuffle($topics);
    
    $generated = 0;
    $errors = 0;
    
    for ($i = 0; $i < $daily_count; $i++) {
        try {
            // Select random category
            $selected_category = $categories[array_rand($categories)];
            $cat_slug = $selected_category['slug'];
            $cat_name = $selected_category['name'];
            
            // Select topic
            $topic = $topics[$i % count($topics)];
            
            log_message("Generating FAQ #" . ($i + 1) . " - Category: $cat_name - Topic: $topic");
            
            // Generate with AI
            $prompt = "You are an expert FAQ writer for GCM Netting Solutions, a professional safety net installation company in Chennai, India.

Generate ONE frequently asked question and its detailed answer about: '$topic'

Category: $cat_name

Requirements:
1. Question: Natural, conversational, 10-20 words, starts with What/How/Why/When/Do/Can/Is
2. Answer: Professional, informative, 150-250 words
3. Include: Specific details about GCM Netting Solutions services in Chennai
4. Mention: Quality (HDPE material, UV stabilized), warranty (3-5 years), professional installation
5. Tone: Helpful, confident, customer-focused
6. SEO: Naturally include keywords like 'safety nets Chennai', 'installation', relevant service names
7. No marketing fluff, provide real value

Format (JSON only, no other text):
{
  \"question\": \"Your question here?\",
  \"answer\": \"Your detailed answer here.\"
}";

            $response = $gemini->generateContent($prompt);
            
            // Parse JSON
            $response = trim($response);
            $response = preg_replace('/^```json\s*|\s*```$/s', '', $response);
            $faq_data = json_decode($response, true);
            
            if (!$faq_data || !isset($faq_data['question']) || !isset($faq_data['answer'])) {
                log_message("ERROR: Failed to parse AI response");
                $errors++;
                continue;
            }
            
            $question = trim($faq_data['question']);
            $answer = trim($faq_data['answer']);
            
            // Generate slug
            $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', $question)));
            $slug = substr($slug, 0, 100);
            
            // Check if exists
            $existing = $db->fetchOne("SELECT id FROM faqs WHERE slug = ?", [$slug], 's');
            if ($existing) {
                $slug = $slug . '-' . time();
                log_message("Slug already exists, using: $slug");
            }
            
            // Generate meta
            $meta_title = substr($question, 0, 60);
            $meta_description = substr(strip_tags($answer), 0, 155);
            
            // Schema markup
            $schema = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($answer)
                ]
            ]);
            
            // Insert FAQ
            $result = $db->execute(
                "INSERT INTO faqs (question, answer, category, slug, is_ai_generated, meta_title, meta_description, schema_markup, is_active) 
                 VALUES (?, ?, ?, ?, 1, ?, ?, ?, 1)",
                [$question, $answer, $cat_slug, $slug, $meta_title, $meta_description, $schema],
                'sssssss'
            );
            
            if ($result) {
                log_message("SUCCESS: FAQ generated - " . substr($question, 0, 50));
                $generated++;
            } else {
                log_message("ERROR: Failed to save FAQ to database");
                $errors++;
            }
            
            // Delay for API rate limiting
            sleep(1);
            
        } catch (Exception $e) {
            log_message("ERROR: " . $e->getMessage());
            $errors++;
        }
    }
    
    // Update settings
    if ($generated > 0) {
        $db->execute(
            "UPDATE faq_settings SET total_generated = total_generated + ?, last_generation_time = NOW() WHERE id = 1",
            [$generated],
            'i'
        );
    }
    
    log_message("=== Daily FAQ Generation Completed ===");
    log_message("Generated: $generated / Requested: $daily_count / Errors: $errors");
    
    // Send notification email (optional)
    if ($generated > 0) {
        $subject = "Daily FAQ Generation Complete - $generated FAQs Created";
        $message = "Daily FAQ generation completed successfully.\n\n";
        $message .= "Generated: $generated FAQs\n";
        $message .= "Requested: $daily_count FAQs\n";
        $message .= "Errors: $errors\n";
        $message .= "Time: " . date('Y-m-d H:i:s') . "\n";
        
        // Uncomment to enable email notifications
        // mail(ADMIN_EMAIL, $subject, $message);
    }
    
} catch (Exception $e) {
    log_message("CRITICAL ERROR: " . $e->getMessage());
    log_message($e->getTraceAsString());
}

log_message("=== Script Ended ===\n");
