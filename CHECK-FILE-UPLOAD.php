<?php
/**
 * FILE UPLOAD VERIFICATION SCRIPT
 * Upload this to public_html/ and access it to verify if security-protection.php was uploaded correctly
 * URL: https://gcmsafetynets.in/CHECK-FILE-UPLOAD.php
 */

// Set password to access this script
$ACCESS_PASSWORD = 'CheckUpload2026';

if (!isset($_GET['password']) || $_GET['password'] !== $ACCESS_PASSWORD) {
    die('Access denied. Add ?password=CheckUpload2026 to URL');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>File Upload Verification</title>
    <style>
        body { font-family: Arial; background: #1a1a1a; color: #fff; padding: 30px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #28a745; }
        .section { background: #2a2a2a; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        pre { background: #0a0a0a; padding: 15px; border-radius: 5px; overflow-x: auto; }
        code { color: #28a745; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #444; }
        th { background: #333; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 File Upload Verification</h1>
        
        <?php
        // Check 1: Does security-protection.php exist?
        $security_file = __DIR__ . '/includes/security-protection.php';
        $file_exists = file_exists($security_file);
        ?>
        
        <div class="section">
            <h2>1. File Existence Check</h2>
            <p><strong>File:</strong> <code>includes/security-protection.php</code></p>
            <p><strong>Full Path:</strong> <code><?php echo $security_file; ?></code></p>
            <p><strong>Status:</strong> 
                <?php if ($file_exists): ?>
                    <span class="success">✅ FILE EXISTS</span>
                <?php else: ?>
                    <span class="error">❌ FILE NOT FOUND</span>
                <?php endif; ?>
            </p>
            
            <?php if ($file_exists): ?>
                <p><strong>File Size:</strong> <?php echo number_format(filesize($security_file)); ?> bytes</p>
                <p><strong>Last Modified:</strong> <?php echo date('Y-m-d H:i:s', filemtime($security_file)); ?></p>
                <p><strong>Permissions:</strong> <?php echo substr(sprintf('%o', fileperms($security_file)), -4); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if ($file_exists): ?>
            <?php
            // Check 2: Does file contain the fix?
            $file_content = file_get_contents($security_file);
            $has_fix = strpos($file_content, "'/admin/api/'") !== false;
            ?>
            
            <div class="section">
                <h2>2. File Content Check</h2>
                <p><strong>Looking for:</strong> <code>'/admin/api/'</code> in allowed_without_csrf array</p>
                <p><strong>Status:</strong> 
                    <?php if ($has_fix): ?>
                        <span class="success">✅ FIX IS PRESENT</span>
                    <?php else: ?>
                        <span class="error">❌ FIX NOT FOUND - OLD FILE STILL THERE</span>
                    <?php endif; ?>
                </p>
                
                <?php if (!$has_fix): ?>
                    <p class="warning">⚠️ The file exists but doesn't contain the fix. You need to upload it again and click "Overwrite".</p>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h2>3. Current allowed_without_csrf Configuration</h2>
                <?php
                // Extract the allowed_without_csrf array
                preg_match('/\$allowed_without_csrf\s*=\s*\[(.*?)\];/s', $file_content, $matches);
                if (!empty($matches[1])) {
                    echo '<pre>' . htmlspecialchars($matches[1]) . '</pre>';
                } else {
                    echo '<p class="error">Could not extract configuration</p>';
                }
                ?>
            </div>
            
            <div class="section">
                <h2>4. Expected Configuration (Should Match)</h2>
                <pre>
    '/api/contact-handler.php',
    '/admin/api/',  // All admin API endpoints use session authentication
                </pre>
            </div>
            
        <?php endif; ?>
        
        <div class="section">
            <h2>5. Config.php Check</h2>
            <?php
            $config_file = __DIR__ . '/config/config.php';
            $config_exists = file_exists($config_file);
            ?>
            <p><strong>File:</strong> <code>config/config.php</code></p>
            <p><strong>Status:</strong> 
                <?php if ($config_exists): ?>
                    <span class="success">✅ EXISTS</span>
                <?php else: ?>
                    <span class="error">❌ NOT FOUND</span>
                <?php endif; ?>
            </p>
            
            <?php if ($config_exists): ?>
                <?php
                $config_content = file_get_contents($config_file);
                $loads_security = strpos($config_content, "require_once __DIR__ . '/../includes/security-protection.php'") !== false;
                ?>
                <p><strong>Loads security-protection.php:</strong> 
                    <?php if ($loads_security): ?>
                        <span class="success">✅ YES</span>
                    <?php else: ?>
                        <span class="error">❌ NO - This is the problem!</span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>6. Server Information</h2>
            <table>
                <tr>
                    <th>Property</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>PHP Version</td>
                    <td><?php echo phpversion(); ?></td>
                </tr>
                <tr>
                    <td>Server Software</td>
                    <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                </tr>
                <tr>
                    <td>Document Root</td>
                    <td><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></td>
                </tr>
                <tr>
                    <td>Current Directory</td>
                    <td><?php echo __DIR__; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <h2>7. Diagnosis & Solution</h2>
            <?php if (!$file_exists): ?>
                <p class="error">❌ PROBLEM: security-protection.php file not found on server</p>
                <p><strong>Solution:</strong></p>
                <ol>
                    <li>Go to Hostinger File Manager</li>
                    <li>Navigate to <code>public_html/includes/</code></li>
                    <li>Upload <code>security-protection.php</code></li>
                    <li>Make sure to click "Overwrite" if asked</li>
                    <li>Refresh this page to verify</li>
                </ol>
            <?php elseif (!$has_fix): ?>
                <p class="error">❌ PROBLEM: File exists but contains old code (fix not applied)</p>
                <p><strong>Solution:</strong></p>
                <ol>
                    <li>Go to Hostinger File Manager</li>
                    <li>Navigate to <code>public_html/includes/</code></li>
                    <li>DELETE the old <code>security-protection.php</code> file</li>
                    <li>Upload the NEW <code>security-protection.php</code> from your computer</li>
                    <li>Refresh this page to verify</li>
                </ol>
            <?php else: ?>
                <p class="success">✅ SUCCESS: File is uploaded correctly with the fix!</p>
                <p><strong>Next Steps:</strong></p>
                <ol>
                    <li>Clear your browser cache (Ctrl+Shift+Delete)</li>
                    <li>Close all browser tabs</li>
                    <li>Wait 2 minutes for server cache to clear</li>
                    <li>Try accessing: <a href="/admin/pages/api-key-settings.php" style="color: #28a745;">/admin/pages/api-key-settings.php</a></li>
                    <li>Should work without CSRF errors!</li>
                </ol>
                <p class="warning">⚠️ If still not working after 2 minutes, contact Hostinger support to clear server cache.</p>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>8. Quick Actions</h2>
            <p><a href="/admin/pages/api-key-settings.php" style="color: #28a745; font-weight: bold;">→ Test API Key Settings Page</a></p>
            <p><a href="/admin/pages/generate-pages.php" style="color: #28a745; font-weight: bold;">→ Test Page Generator</a></p>
            <p><a href="/admin/pages/pillar-page-generator.php" style="color: #28a745; font-weight: bold;">→ Test Pillar Page Generator</a></p>
        </div>
        
        <div class="section" style="background: #dc3545; color: #fff;">
            <h2>⚠️ IMPORTANT: DELETE THIS FILE AFTER USE</h2>
            <p>This verification script should be deleted from your server after you've verified the upload.</p>
            <p>File to delete: <code>public_html/CHECK-FILE-UPLOAD.php</code></p>
        </div>
    </div>
</body>
</html>
