<?php
/**
 * Check if Page Already Exists
 */

header('Content-Type: application/json');
error_reporting(0);

$db_path = __DIR__ . '/data/local-test.db';

try {
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $keyword_id = (int)$_GET['keyword_id'];
    $area_id = (int)$_GET['area_id'];
    
    $stmt = $db->prepare("SELECT id FROM generated_pages WHERE keyword_id = ? AND area_id = ?");
    $stmt->execute([$keyword_id, $area_id]);
    $exists = $stmt->fetch();
    
    echo json_encode(['exists' => (bool)$exists]);
    
} catch (PDOException $e) {
    echo json_encode(['exists' => false]);
}
?>
