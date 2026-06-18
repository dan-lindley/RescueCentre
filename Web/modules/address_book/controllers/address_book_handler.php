<?php
// modules/address_book/controllers/address_book_handler.php

require_once __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/../../../operations/audit.php';
require_once __DIR__ . '/address_book_lib.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$centre_id = address_book_centre_id();
$user_id = address_book_user_id();
$GLOBALS['centre_id'] = $centre_id;
$GLOBALS['user_id'] = $user_id;

if ($centre_id <= 0 || $user_id <= 0) {
    address_book_redirect(['error' => 'ADD_ACTION_FAILED']);
}

try {
    address_book_ensure_schema($pdo);
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save_finder') {
        $finder_id = (int)($_POST['finder_id'] ?? 0);
        $old = $finder_id > 0 ? address_book_fetch_finder($pdo, $finder_id, $centre_id) : null;
        $saved_id = address_book_save_finder($pdo, $centre_id, $_POST);
        $new = address_book_fetch_finder($pdo, $saved_id, $centre_id);

        audit_write(
            $pdo,
            $old ? 'finder_address_book_updated' : 'finder_address_book_created',
            'address_book',
            address_book_audit_snapshot($old),
            address_book_audit_snapshot($new)
        );

        address_book_redirect(['finder_id' => $saved_id, 'msg' => 'ADD_FINDER_SAVED']);
    }

    if ($action === 'delete_finder') {
        $finder_id = (int)($_POST['finder_id'] ?? 0);
        $old = $finder_id > 0 ? address_book_fetch_finder($pdo, $finder_id, $centre_id) : null;
        if (!$old) {
            address_book_redirect(['error' => 'ADD_FINDER_NOT_FOUND']);
        }

        address_book_delete_finder($pdo, $finder_id, $centre_id);
        audit_write(
            $pdo,
            'finder_address_book_deleted',
            'address_book',
            address_book_audit_snapshot($old),
            ['finder_id' => $finder_id, 'deleted' => 1]
        );

        address_book_redirect(['msg' => 'ADD_FINDER_DELETED']);
    }

    if ($action === 'link_admission') {
        $finder_id = (int)($_POST['finder_id'] ?? 0);
        $admission_id = (int)($_POST['admission_id'] ?? 0);
        address_book_link_admission($pdo, $finder_id, $admission_id, $centre_id);

        audit_write(
            $pdo,
            'finder_address_book_admission_linked',
            'address_book',
            null,
            ['finder_id' => $finder_id, 'admission_id' => $admission_id]
        );

        address_book_redirect(['finder_id' => $finder_id, 'msg' => 'ADD_ADMISSION_LINKED']);
    }

    if ($action === 'link_existing_admissions') {
        $finder_id = (int)($_POST['finder_id'] ?? 0);
        $linked = address_book_link_existing_admissions($pdo, $finder_id, $centre_id);

        audit_write(
            $pdo,
            'finder_address_book_existing_admissions_linked',
            'address_book',
            null,
            ['finder_id' => $finder_id, 'linked_count' => $linked]
        );

        address_book_redirect(['finder_id' => $finder_id, 'msg' => 'ADD_EXISTING_ADMISSIONS_LINKED']);
    }
} catch (InvalidArgumentException $e) {
    $key = $e->getMessage();
    address_book_redirect(['finder_id' => (int)($_POST['finder_id'] ?? 0), 'error' => str_starts_with($key, 'ADD_') ? $key : 'ADD_ACTION_FAILED']);
} catch (Throwable $e) {
    address_book_redirect(['finder_id' => (int)($_POST['finder_id'] ?? 0), 'error' => 'ADD_ACTION_FAILED']);
}

address_book_redirect(['error' => 'ADD_ACTION_FAILED']);
