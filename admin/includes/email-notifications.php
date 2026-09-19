<?php
/**
 * Admin Email Notification System
 * Sends security alerts for admin panel activities
 */

if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}

/**
 * Send admin login notification email
 */
function sendAdminLoginNotification($username, $ip_address, $user_agent, $login_time) {
    $recipients = [
        'gopichandmailapally@gmail.com',
        'gcmsafetynets@gmail.com'
    ];
    
    // Get location info from IP (basic)
    $location = @file_get_contents("http://ip-api.com/json/{$ip_address}");
    $locationData = $location ? json_decode($location, true) : null;
    
    $city = $locationData['city'] ?? 'Unknown';
    $region = $locationData['regionName'] ?? 'Unknown';
    $country = $locationData['country'] ?? 'Unknown';
    $isp = $locationData['isp'] ?? 'Unknown';
    
    $subject = "🔐 Admin Login Alert - GCM Netting Solutions";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { padding: 30px; }
            .alert-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px; }
            .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .info-table td { padding: 12px; border-bottom: 1px solid #eee; }
            .info-table td:first-child { font-weight: bold; color: #667eea; width: 40%; }
            .security-tip { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; border-radius: 4px; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
            .btn { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔐 Admin Login Notification</h1>
                <p style='margin: 10px 0 0 0; opacity: 0.9;'>Someone just logged into your admin panel</p>
            </div>
            
            <div class='content'>
                <div class='alert-box'>
                    <strong>⚠️ Security Alert:</strong> A successful admin login was detected on your GCM Netting Solutions admin panel.
                </div>
                
                <h3 style='color: #667eea; margin-top: 25px;'>Login Details:</h3>
                <table class='info-table'>
                    <tr>
                        <td>👤 Username</td>
                        <td><strong>{$username}</strong></td>
                    </tr>
                    <tr>
                        <td>🕐 Login Time</td>
                        <td>{$login_time}</td>
                    </tr>
                    <tr>
                        <td>🌐 IP Address</td>
                        <td><code>{$ip_address}</code></td>
                    </tr>
                    <tr>
                        <td>📍 Location</td>
                        <td>{$city}, {$region}, {$country}</td>
                    </tr>
                    <tr>
                        <td>🏢 ISP</td>
                        <td>{$isp}</td>
                    </tr>
                    <tr>
                        <td>💻 Device</td>
                        <td>" . getBrowserInfo($user_agent) . "</td>
                    </tr>
                </table>
                
                <div class='security-tip'>
                    <strong>🛡️ Security Tip:</strong> If this wasn't you, immediately change your admin password and contact your web administrator. Check the admin security logs for any suspicious activity.
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='" . SITE_URL . "/admin/dashboard.php' class='btn'>View Admin Dashboard</a>
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>GCM Netting Solutions - Admin Security System</strong></p>
                <p>This is an automated security notification. Do not reply to this email.</p>
                <p>Website: " . SITE_URL . " | Phone: " . COMPANY_PHONE . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send to both email addresses
    $emailsSent = 0;
    foreach ($recipients as $recipient) {
        if (send_email($recipient, $subject, $body)) {
            $emailsSent++;
        }
    }
    
    // Log the notification attempt
    error_log("Admin login notification sent to {$emailsSent} recipients for user: {$username}");
    
    return $emailsSent > 0;
}

/**
 * Send page deletion alert
 */
function sendPageDeletionAlert($filename, $deleted_by, $ip_address) {
    $recipients = [
        'gopichandmailapally@gmail.com',
        'gcmsafetynets@gmail.com'
    ];
    
    $subject = "⚠️ ALERT: Page Deleted - GCM Netting Solutions";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { padding: 30px; }
            .danger-box { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; border-radius: 4px; color: #721c24; }
            .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .info-table td { padding: 12px; border-bottom: 1px solid #eee; }
            .info-table td:first-child { font-weight: bold; color: #dc3545; width: 40%; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>⚠️ Page Deletion Alert</h1>
                <p style='margin: 10px 0 0 0; opacity: 0.9;'>A generated page has been deleted</p>
            </div>
            
            <div class='content'>
                <div class='danger-box'>
                    <strong>🚨 CRITICAL ALERT:</strong> A generated page has been deleted from your website. This action has been logged for security purposes.
                </div>
                
                <h3 style='color: #dc3545; margin-top: 25px;'>Deletion Details:</h3>
                <table class='info-table'>
                    <tr>
                        <td>📄 File Name</td>
                        <td><code>{$filename}</code></td>
                    </tr>
                    <tr>
                        <td>👤 Deleted By</td>
                        <td><strong>{$deleted_by}</strong></td>
                    </tr>
                    <tr>
                        <td>🕐 Time</td>
                        <td>" . date('d M Y, h:i A') . "</td>
                    </tr>
                    <tr>
                        <td>🌐 IP Address</td>
                        <td><code>{$ip_address}</code></td>
                    </tr>
                </table>
                
                <p style='margin-top: 20px;'><strong>Action Required:</strong> Review the admin security logs and verify this deletion was authorized.</p>
            </div>
            
            <div class='footer'>
                <p><strong>GCM Netting Solutions - Security Alert System</strong></p>
                <p>This is an automated security notification.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    foreach ($recipients as $recipient) {
        send_email($recipient, $subject, $body);
    }
}

/**
 * Get browser and OS info from user agent
 */
function getBrowserInfo($user_agent) {
    $browser = 'Unknown Browser';
    $os = 'Unknown OS';
    
    // Detect browser
    if (strpos($user_agent, 'Chrome') !== false) $browser = 'Chrome';
    elseif (strpos($user_agent, 'Firefox') !== false) $browser = 'Firefox';
    elseif (strpos($user_agent, 'Safari') !== false) $browser = 'Safari';
    elseif (strpos($user_agent, 'Edge') !== false) $browser = 'Edge';
    elseif (strpos($user_agent, 'Opera') !== false) $browser = 'Opera';
    
    // Detect OS
    if (strpos($user_agent, 'Windows') !== false) $os = 'Windows';
    elseif (strpos($user_agent, 'Mac') !== false) $os = 'macOS';
    elseif (strpos($user_agent, 'Linux') !== false) $os = 'Linux';
    elseif (strpos($user_agent, 'Android') !== false) $os = 'Android';
    elseif (strpos($user_agent, 'iOS') !== false) $os = 'iOS';
    
    return "{$browser} on {$os}";
}
