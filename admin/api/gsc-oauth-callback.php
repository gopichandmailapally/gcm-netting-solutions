<?php
/**
 * Google Search Console OAuth 2.0 Callback Handler
 * After the user authorizes on Google, they are redirected here.
 * This script exchanges the authorization code for access + refresh tokens
 * and saves them to /data/gsc-tokens.json
 */
define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('GCM_ADMIN_SESSION');
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php'); exit;
}

$root          = dirname(dirname(dirname(__FILE__)));
$settings_file = $root . '/data/gsc-settings.json';
$tokens_file   = $root . '/data/gsc-tokens.json';

$settings = file_exists($settings_file) ? (json_decode(file_get_contents($settings_file), true) ?: []) : [];

$client_id     = $settings['oauth_client_id']     ?? '';
$client_secret = $settings['oauth_client_secret'] ?? '';
// Must exactly match what the browser sent in the authorization request.
// The JS uses location.origin so we derive the same scheme+host from the actual request.
$_cb_scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$redirect_uri = $_cb_scheme . '://' . $_SERVER['HTTP_HOST'] . '/admin/api/gsc-oauth-callback.php';
unset($_cb_scheme);

$code  = $_GET['code']  ?? '';
$error = $_GET['error'] ?? '';

if ($error) {
    $_SESSION['gsc_msg'] = ['ok' => false, 'text' => 'Authorization denied: ' . htmlspecialchars($error)];
    header('Location: ../pages/search-console.php'); exit;
}

if (!$code) {
    $_SESSION['gsc_msg'] = ['ok' => false, 'text' => 'No authorization code received from Google.'];
    header('Location: ../pages/search-console.php'); exit;
}

if (empty($client_id) || empty($client_secret)) {
    $_SESSION['gsc_msg'] = ['ok' => false, 'text' => 'Client ID or Client Secret missing. Please save them first.'];
    header('Location: ../pages/search-console.php'); exit;
}

// Exchange authorization code for tokens
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'code'          => $code,
        'client_id'     => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri'  => $redirect_uri,
        'grant_type'    => 'authorization_code',
    ]),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 20,
]);
$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$tokens = json_decode($response, true) ?: [];

if ($http_code !== 200 || empty($tokens['access_token'])) {
    $err = $tokens['error_description'] ?? ($tokens['error'] ?? $response);
    $_SESSION['gsc_msg'] = ['ok' => false, 'text' => 'Token exchange failed: ' . htmlspecialchars($err)];
    header('Location: ../pages/search-console.php'); exit;
}

// Store tokens securely
$tokens['obtained_at'] = time();
file_put_contents($tokens_file, json_encode($tokens, JSON_PRETTY_PRINT));
@chmod($tokens_file, 0600);

// Mark as connected in settings
$settings['oauth_connected'] = true;
file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT));

$_SESSION['gsc_msg'] = ['ok' => true, 'text' => '&#10003; Successfully connected to Google Search Console API! Real ranking data will now sync when you click "Sync from GSC".'];
header('Location: ../pages/search-console.php');
exit;
