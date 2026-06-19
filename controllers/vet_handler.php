<?php
// controllers/vet_handler.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config.php';

if (!isset($pdo)) {
    die('Database connection not available.');
}

$currentAccountId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
$currentCentreId = isset($_SESSION['centre_id']) ? (int)$_SESSION['centre_id'] : 0;
$returnTab = isset($_POST['return_tab']) ? trim((string)$_POST['return_tab']) : 'vets';
$redirect = '../friends.php?tab=' . urlencode($returnTab);

if ($currentAccountId <= 0 || $currentCentreId <= 0) {
    header('Location: ' . $redirect);
    exit;
}

$action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';

try {
    if ($action === 'request') {
        $practiceId = isset($_POST['practice_id']) ? (int)$_POST['practice_id'] : 0;
        if ($practiceId <= 0) {
            throw new RuntimeException('Invalid practice.');
        }

        $practiceStmt = $pdo->prepare("SELECT 1 FROM rescue_vets WHERE practice_id = ? AND status = 'Active' LIMIT 1");
        $practiceStmt->execute([$practiceId]);
        if (!$practiceStmt->fetchColumn()) {
            throw new RuntimeException('Vet practice not found.');
        }

        $existingStmt = $pdo->prepare("\n            SELECT rel_id, status\n            FROM rescue_vet_centres\n            WHERE centre_id = ? AND practice_id = ?\n            LIMIT 1\n        ");
        $existingStmt->execute([$currentCentreId, $practiceId]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $relId = (int)$existing['rel_id'];
            $status = (string)$existing['status'];

            if ($status === 'pending' || $status === 'approved') {
                header('Location: ' . $redirect);
                exit;
            }

            $updateStmt = $pdo->prepare("\n                UPDATE rescue_vet_centres\n                SET\n                    status = 'pending',\n                    requested_by_side = 'centre',\n                    requested_by_account_id = ?,\n                    approved_by_account_id = NULL,\n                    revoked_by_account_id = NULL,\n                    requested_at = NOW(),\n                    approved_at = NULL,\n                    revoked_at = NULL\n                WHERE rel_id = ?\n            ");
            $updateStmt->execute([$currentAccountId, $relId]);
        } else {
            $insertStmt = $pdo->prepare("\n                INSERT INTO rescue_vet_centres (\n                    centre_id,\n                    practice_id,\n                    status,\n                    requested_by_side,\n                    requested_by_account_id,\n                    requested_at,\n                    include_all\n                ) VALUES (\n                    ?, ?, 'pending', 'centre', ?, NOW(), 1\n                )\n            ");
            $insertStmt->execute([$currentCentreId, $practiceId, $currentAccountId]);
        }

    } elseif ($action === 'cancel') {
        $relId = isset($_POST['rel_id']) ? (int)$_POST['rel_id'] : 0;
        $stmt = $pdo->prepare("\n            UPDATE rescue_vet_centres\n            SET\n                status = 'revoked',\n                revoked_by_account_id = ?,\n                revoked_at = NOW()\n            WHERE rel_id = ?\n              AND centre_id = ?\n              AND status = 'pending'\n              AND requested_by_side = 'centre'\n        ");
        $stmt->execute([$currentAccountId, $relId, $currentCentreId]);

    } elseif ($action === 'approve') {
        $relId = isset($_POST['rel_id']) ? (int)$_POST['rel_id'] : 0;
        $stmt = $pdo->prepare("\n            UPDATE rescue_vet_centres\n            SET\n                status = 'approved',\n                approved_by_account_id = ?,\n                approved_at = NOW(),\n                revoked_by_account_id = NULL,\n                revoked_at = NULL\n            WHERE rel_id = ?\n              AND centre_id = ?\n              AND status = 'pending'\n              AND requested_by_side = 'vet'\n        ");
        $stmt->execute([$currentAccountId, $relId, $currentCentreId]);

    } elseif ($action === 'decline') {
        $relId = isset($_POST['rel_id']) ? (int)$_POST['rel_id'] : 0;
        $stmt = $pdo->prepare("\n            UPDATE rescue_vet_centres\n            SET\n                status = 'declined',\n                revoked_by_account_id = ?,\n                revoked_at = NOW()\n            WHERE rel_id = ?\n              AND centre_id = ?\n              AND status = 'pending'\n              AND requested_by_side = 'vet'\n        ");
        $stmt->execute([$currentAccountId, $relId, $currentCentreId]);

    } elseif ($action === 'remove') {
        $relId = isset($_POST['rel_id']) ? (int)$_POST['rel_id'] : 0;
        $stmt = $pdo->prepare("\n            UPDATE rescue_vet_centres\n            SET\n                status = 'revoked',\n                revoked_by_account_id = ?,\n                revoked_at = NOW()\n            WHERE rel_id = ?\n              AND centre_id = ?\n              AND status = 'approved'\n        ");
        $stmt->execute([$currentAccountId, $relId, $currentCentreId]);
    }
} catch (Throwable $e) {
    header('Location: ' . $redirect . '&error=' . urlencode($e->getMessage()));
    exit;
}

header('Location: ' . $redirect);
exit;
