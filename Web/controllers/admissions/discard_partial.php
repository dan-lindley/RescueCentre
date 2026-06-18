<?php
declare(strict_types=1);

require_once __DIR__ . '/../../main.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$centreId = (int)($_SESSION['centre_id'] ?? 0);
if ($centreId <= 0) {
    http_response_code(403);
    exit('Not authorised.');
}

$postedToken = (string)($_POST['csrf_token'] ?? '');
$sessionToken = (string)($_SESSION['csrf_token'] ?? '');
if ($postedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
    http_response_code(403);
    exit('Invalid request token.');
}

$patientId = (int)($_POST['patient_id'] ?? 0);
$admissionId = (int)($_POST['admission_id'] ?? 0);
if ($patientId <= 0) {
    http_response_code(400);
    exit('Invalid patient.');
}

function discardPartialColumnExists(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
          AND COLUMN_NAME = :column
    ");
    $stmt->execute([
        ':table' => $table,
        ':column' => $column,
    ]);
    $cache[$key] = ((int)$stmt->fetchColumn() > 0);

    return $cache[$key];
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT patient_id
        FROM rescue_patients
        WHERE patient_id = :pid
          AND centre_id = :cid
          AND state = 'To Admit'
        LIMIT 1
    ");
    $stmt->execute([
        ':pid' => $patientId,
        ':cid' => $centreId,
    ]);

    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('This patient is not in the To Admit queue.');
    }

    if ($admissionId > 0) {
        $stmt = $pdo->prepare("
            SELECT admission_id
            FROM rescue_admissions
            WHERE admission_id = :aid
              AND patient_id = :pid
              AND centre_id = :cid
            LIMIT 1
        ");
        $stmt->execute([
            ':aid' => $admissionId,
            ':pid' => $patientId,
            ':cid' => $centreId,
        ]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('Admission does not match this patient.');
        }
    }

    if (discardPartialColumnExists($pdo, 'rescue_admissions', 'is_deleted')) {
        if ($admissionId > 0) {
            $stmt = $pdo->prepare("
                UPDATE rescue_admissions
                   SET is_deleted = 1
                 WHERE admission_id = :aid
                   AND patient_id = :pid
                   AND centre_id = :cid
                 LIMIT 1
            ");
            $stmt->execute([
                ':aid' => $admissionId,
                ':pid' => $patientId,
                ':cid' => $centreId,
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE rescue_admissions
                   SET is_deleted = 1
                 WHERE patient_id = :pid
                   AND centre_id = :cid
            ");
            $stmt->execute([
                ':pid' => $patientId,
                ':cid' => $centreId,
            ]);
        }
    }

    if (discardPartialColumnExists($pdo, 'rescue_patients', 'is_deleted')) {
        $stmt = $pdo->prepare("
            UPDATE rescue_patients
               SET is_deleted = 1
             WHERE patient_id = :pid
               AND centre_id = :cid
               AND state = 'To Admit'
             LIMIT 1
        ");
    } else {
        $stmt = $pdo->prepare("
            UPDATE rescue_patients
               SET state = 'Discarded'
             WHERE patient_id = :pid
               AND centre_id = :cid
               AND state = 'To Admit'
             LIMIT 1
        ");
    }
    $stmt->execute([
        ':pid' => $patientId,
        ':cid' => $centreId,
    ]);

    $pdo->commit();
    header('Location: ../../admission.php?discarded=1');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo 'Could not discard partial admission: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
