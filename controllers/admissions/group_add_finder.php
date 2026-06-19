<?php
// controllers/admissions/group_add_finder.php
// Finder create endpoint for the group admission Stage 3 mini-form.

ob_start();
header('Content-Type: application/json; charset=utf-8');

function respond(array $payload, int $httpCode = 200): void
{
    http_response_code($httpCode);
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($payload);
    exit;
}

session_start();
require_once __DIR__ . '/../../config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . db_host . ";dbname=" . db_name . ";charset=" . db_charset,
        db_user,
        db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
} catch (PDOException $e) {
    respond(['success' => false, 'message' => 'Database connection failed.'], 500);
}

$user_id = $_SESSION['account_id'] ?? null;
$centre_id = $_SESSION['centre_id'] ?? null;

if (!$centre_id && $user_id) {
    $stmt = $pdo->prepare("SELECT centre_id FROM accounts WHERE id = :uid LIMIT 1");
    $stmt->execute([':uid' => $user_id]);
    $centre_id = $stmt->fetchColumn();
    if ($centre_id) {
        $_SESSION['centre_id'] = $centre_id;
    }
}

if (!$centre_id) {
    respond(['success' => false, 'message' => 'Cannot add finder: centre_id is missing.'], 403);
}

$finder_name = trim((string)($_POST['finder_name'] ?? ''));
$finder_tel = trim((string)($_POST['finder_tel'] ?? ''));

if ($finder_name === '' || $finder_tel === '') {
    respond(['success' => false, 'message' => 'Finder name and telephone are required.'], 400);
}

$finder_tel_norm = preg_replace('/[^0-9+]/', '', $finder_tel);
if ($finder_tel_norm !== '') {
    $finder_tel = $finder_tel_norm;
}

try {
    $stmt = $pdo->prepare("
        SELECT finder_id
        FROM rescue_finders
        WHERE centre_id = :cid
          AND finder_tel = :tel
          AND deleted = 0
        ORDER BY finder_id DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':cid' => $centre_id,
        ':tel' => $finder_tel,
    ]);
    $existing_id = (int)($stmt->fetchColumn() ?: 0);

    $now = date('Y-m-d H:i:s');

    if ($existing_id > 0) {
        $stmt = $pdo->prepare("
            UPDATE rescue_finders
               SET finder_name = :name,
                   updated_at = :updated_at
             WHERE finder_id = :fid
               AND centre_id = :cid
               AND deleted = 0
        ");
        $stmt->execute([
            ':name' => $finder_name,
            ':updated_at' => $now,
            ':fid' => $existing_id,
            ':cid' => $centre_id,
        ]);

        respond([
            'success' => true,
            'message' => 'Finder already exists.',
            'finder_id' => $existing_id,
        ]);
    }

    $stmt = $pdo->prepare("
        INSERT INTO rescue_finders
            (centre_id, finder_name, finder_tel, created_at, updated_at, deleted)
        VALUES
            (:centre_id, :finder_name, :finder_tel, :created_at, :updated_at, 0)
    ");
    $stmt->execute([
        ':centre_id' => $centre_id,
        ':finder_name' => $finder_name,
        ':finder_tel' => $finder_tel,
        ':created_at' => $now,
        ':updated_at' => $now,
    ]);

    respond([
        'success' => true,
        'message' => 'Finder added successfully.',
        'finder_id' => (int)$pdo->lastInsertId(),
    ]);
} catch (Throwable $e) {
    respond(['success' => false, 'message' => 'Unable to add finder.', 'details' => $e->getMessage()], 500);
}
