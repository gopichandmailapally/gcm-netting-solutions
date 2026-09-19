<?php
define('GCM_INIT', true);
require_once '../../config/config.php';

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false]); exit;
}

$tokens_file = dirname(dirname(dirname(__FILE__))) . '/data/gsc-tokens.json';
if (file_exists($tokens_file)) {
    unlink($tokens_file);
}
echo json_encode(['success' => true]);
