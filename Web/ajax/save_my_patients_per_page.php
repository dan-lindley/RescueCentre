<?php
include '../main.php';

header('Content-Type: application/json');

try {
    check_loggedin($pdo);

    $accountId = (int)($_SESSION['account_id'] ?? 0);
    $perPage = (int)($_POST['per_page'] ?? 0);
    $allowed = [10, 20, 25, 50, 100, 9999];

    if ($accountId <= 0) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
        exit;
    }

    if (!in_array($perPage, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid listing preference']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE accounts SET my_patients_per_page = ? WHERE id = ?');
    $stmt->execute([$perPage, $accountId]);
    $_SESSION['my_patients_per_page'] = $perPage;

    echo json_encode(['status' => 'ok', 'per_page' => $perPage]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Could not save listing preference']);
}
