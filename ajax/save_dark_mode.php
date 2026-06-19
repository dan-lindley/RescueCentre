<?php
include '../main.php';

header('Content-Type: application/json');

try {
    check_loggedin($pdo);

    $accountId = (int)($_SESSION['account_id'] ?? 0);
    if ($accountId <= 0) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
        exit;
    }

    $darkMode = isset($_POST['dark_mode']) && (string)$_POST['dark_mode'] === '1' ? 1 : 0;

    $stmt = $pdo->prepare('UPDATE accounts SET dark_mode = ? WHERE id = ?');
    $stmt->execute([$darkMode, $accountId]);

    $_SESSION['dark_mode'] = $darkMode;

    echo json_encode(['status' => 'ok', 'dark_mode' => $darkMode]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Could not save theme preference']);
}
