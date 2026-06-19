<?php
// modules/staff_management/controllers/staff_management_handler.php

require_once __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/../../../getuserinfo.php';
require_once __DIR__ . '/../../../operations/modules_registry.php';
require_once __DIR__ . '/../../../operations/permissions.php';
require_once __DIR__ . '/../../../operations/audit.php';
require_once __DIR__ . '/staff_management_lib.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$centre_id = staff_management_centre_id();
$user_id = staff_management_user_id();
$GLOBALS['centre_id'] = $centre_id;
$GLOBALS['user_id'] = $user_id;

if ($centre_id <= 0 || $user_id <= 0) {
    staff_management_redirect(['error' => 'ADD_ACTION_FAILED']);
}

try {
    staff_management_ensure_schema($pdo);
    $module = modules_find($pdo, 'staff_management', $centre_id);
    if (!$module || empty($module['installed']) || empty($module['enabled'])) {
        staff_management_redirect(['error' => 'ADD_ACTION_FAILED']);
    }

    if (!staff_management_can_access()) {
        staff_management_redirect(['error' => 'ADD_ACCESS_DENIED']);
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save_person') {
        $id = (int)($_POST['id'] ?? 0);
        $old = $id > 0 ? staff_management_fetch_person($pdo, $id, $centre_id) : null;
        $saved_id = staff_management_save_person($pdo, $centre_id, $_POST);
        $new = staff_management_fetch_person($pdo, $saved_id, $centre_id);

        audit_write(
            $pdo,
            $old ? 'staff_profile_updated' : 'staff_profile_created',
            'staff_management',
            staff_management_audit_snapshot($old),
            staff_management_audit_snapshot($new)
        );

        staff_management_redirect(['person_id' => $saved_id, 'msg' => 'ADD_PERSON_SAVED']);
    }

    if ($action === 'delete_person') {
        $id = (int)($_POST['id'] ?? 0);
        $old = $id > 0 ? staff_management_fetch_person($pdo, $id, $centre_id) : null;
        if (!$old) {
            staff_management_redirect(['error' => 'ADD_PERSON_NOT_FOUND']);
        }

        staff_management_delete_person($pdo, $id, $centre_id);
        audit_write(
            $pdo,
            'staff_profile_deleted',
            'staff_management',
            staff_management_audit_snapshot($old),
            ['id' => $id, 'deleted' => 1]
        );

        staff_management_redirect(['msg' => 'ADD_PERSON_DELETED']);
    }
} catch (InvalidArgumentException $e) {
    $key = $e->getMessage();
    staff_management_redirect(['person_id' => (int)($_POST['id'] ?? 0), 'error' => str_starts_with($key, 'ADD_') ? $key : 'ADD_ACTION_FAILED']);
} catch (Throwable $e) {
    staff_management_redirect(['person_id' => (int)($_POST['id'] ?? 0), 'error' => 'ADD_ACTION_FAILED']);
}

staff_management_redirect(['error' => 'ADD_ACTION_FAILED']);
