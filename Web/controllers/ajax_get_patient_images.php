<?php
// Simple, read-only endpoint that returns the patient's images as JSON

require_once("../main.php");

header('Content-Type: application/json');

$pid = isset($_GET['patient_id']) ? (int) $_GET['patient_id'] : 0;

if (!$pid) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT image_id, image_url, file_name 
                            FROM rescue_images 
                            WHERE patient_id = :pid 
                            ORDER BY image_id ASC");
    $stmt->execute([':pid' => $pid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
