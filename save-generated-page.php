<?php
/**
 * Save Generated Page to Database
 */

header('Content-Type: application/json');
error_reporting(0);

$db_path = __DIR__ . '/data/local-test.db';

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $db->prepare("INSERT INTO generated_pages (keyword_id, area_id, page_url, page_title, content, is_published, created_at) VALUES (?, ?, ?, ?, ?, 1, datetime('now'))");
    
    $stmt->execute([
        $input['keyword_id'],
        $input['area_id'],
        $input['page_url'],
        $input['page_title'],
        $input['content']
    ]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
