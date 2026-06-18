<?php
// modules/duties/controllers/duties_handler.php

require_once __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/../../../getuserinfo.php';
require_once __DIR__ . '/../../../operations/modules_registry.php';
require_once __DIR__ . '/../../../operations/permissions.php';
require_once __DIR__ . '/../../../operations/audit.php';
require_once __DIR__ . '/duties_lib.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$centre_id = duties_centre_id();
$user_id = duties_user_id();
$GLOBALS['centre_id'] = $centre_id;
$GLOBALS['user_id'] = $user_id;

if ($centre_id <= 0 || $user_id <= 0) {
    duties_redirect(['error' => 'ADD_ACTION_FAILED']);
}

try {
    duties_ensure_schema($pdo);

    $module = modules_find($pdo, 'duties', $centre_id);
    if (!$module || empty($module['installed']) || empty($module['enabled']) || modules_unmet_dependencies($pdo, 'duties', $centre_id)) {
        duties_redirect(['error' => 'ADD_ACTION_FAILED']);
    }

    if (!duties_can_access()) {
        duties_redirect(['error' => 'ADD_ACCESS_DENIED']);
    }

    $action = (string)($_POST['action'] ?? '');
    $week = duties_null($_POST['week'] ?? '');
    $tab = duties_null($_POST['tab'] ?? '');
    $return_to = duties_null($_POST['return_to'] ?? '');
    $redirect = $week ? ['week' => $week] : [];
    if ($tab && in_array($tab, ['week', 'rota', 'manage'], true)) {
        $redirect['tab'] = $tab;
    }

    if ($action === 'save_shift') {
        $id = duties_shift_save($pdo, $centre_id, $_POST);
        audit_write($pdo, !empty($_POST['shift_id']) ? 'duty_shift_updated' : 'duty_shift_created', 'duties', null, ['shift_id' => $id, 'centre_id' => $centre_id]);
        if ($return_to === 'shifts') {
            $start = duties_null($_POST['overview_start'] ?? '');
            header('Location: ../../../module.php?module=duties&view=shifts' . ($start ? '&start=' . urlencode($start) : '') . '&msg=ADD_SHIFT_SAVED');
            exit;
        }
        duties_redirect($redirect + ['msg' => 'ADD_SHIFT_SAVED']);
    }

    if ($action === 'delete_shift') {
        $shift_id = (int)($_POST['shift_id'] ?? 0);
        duties_delete_shift($pdo, $centre_id, $shift_id);
        audit_write($pdo, 'duty_shift_deleted', 'duties', null, ['shift_id' => $shift_id, 'centre_id' => $centre_id]);
        if ($return_to === 'shifts') {
            $start = duties_null($_POST['overview_start'] ?? '');
            header('Location: ../../../module.php?module=duties&view=shifts' . ($start ? '&start=' . urlencode($start) : '') . '&msg=ADD_SHIFT_DELETED');
            exit;
        }
        duties_redirect($redirect + ['msg' => 'ADD_SHIFT_DELETED']);
    }

    if ($action === 'end_recurring_rule') {
        $rule_id = (int)($_POST['rule_id'] ?? 0);
        $ends_on = duties_null($_POST['ends_on'] ?? '') ?: date('Y-m-d');
        duties_end_recurring_rule($pdo, $centre_id, $rule_id, $ends_on);
        audit_write($pdo, 'duty_recurring_rule_ended', 'duties', null, ['rule_id' => $rule_id, 'centre_id' => $centre_id, 'ends_on' => $ends_on]);
        duties_redirect($redirect + ['msg' => 'ADD_RECURRING_ENDED']);
    }

    if ($action === 'end_recurring_task_rule') {
        $rule_id = (int)($_POST['rule_id'] ?? 0);
        $ends_on = duties_null($_POST['ends_on'] ?? '') ?: date('Y-m-d');
        duties_end_recurring_task_rule($pdo, $centre_id, $rule_id, $ends_on);
        audit_write($pdo, 'duty_recurring_task_rule_ended', 'duties', null, ['rule_id' => $rule_id, 'centre_id' => $centre_id, 'ends_on' => $ends_on]);
        duties_redirect($redirect + ['msg' => 'ADD_RECURRING_ENDED']);
    }

    if ($action === 'save_task') {
        $id = duties_task_save($pdo, $centre_id, $_POST);
        audit_write($pdo, 'duty_task_created', 'duties', null, ['task_id' => $id, 'centre_id' => $centre_id]);
        duties_redirect($redirect + ['msg' => 'ADD_TASK_SAVED']);
    }

    if ($action === 'complete_task') {
        $task_ref = (string)($_POST['task_id'] ?? '');
        $task_id = duties_task_materialise($pdo, $centre_id, $task_ref);
        duties_task_complete($pdo, $centre_id, $task_id, $user_id);
        audit_write($pdo, 'duty_task_completed', 'duties', null, ['task_id' => $task_id, 'centre_id' => $centre_id]);
        if ($return_to === 'home') {
            header('Location: ../../../home.php');
            exit;
        }
        if ($return_to === 'home_rota') {
            header('Location: ../../../duties_rota.php');
            exit;
        }
        duties_redirect($redirect + ['msg' => 'ADD_TASK_COMPLETED']);
    }

    if ($action === 'delete_task') {
        $task_id = duties_task_delete($pdo, $centre_id, (string)($_POST['task_id'] ?? ''));
        audit_write($pdo, 'duty_task_deleted', 'duties', null, ['task_id' => $task_id, 'centre_id' => $centre_id]);
        duties_redirect($redirect + ['msg' => 'ADD_TASK_DELETED']);
    }

    if ($action === 'assign_task') {
        $task_id = duties_task_assign($pdo, $centre_id, (string)($_POST['task_id'] ?? ''), duties_int_or_null($_POST['staff_profile_id'] ?? 0));
        audit_write($pdo, 'duty_task_assigned', 'duties', null, ['task_id' => $task_id, 'centre_id' => $centre_id, 'staff_profile_id' => duties_int_or_null($_POST['staff_profile_id'] ?? 0)]);
        duties_redirect($redirect + ['msg' => 'ADD_TASK_ASSIGNED']);
    }
} catch (InvalidArgumentException $e) {
    $key = $e->getMessage();
    if (($return_to ?? '') === 'shifts') {
        $start = duties_null($_POST['overview_start'] ?? '');
        header('Location: ../../../module.php?module=duties&view=shifts' . ($start ? '&start=' . urlencode($start) : '') . '&error=' . urlencode(str_starts_with($key, 'ADD_') ? $key : 'ADD_ACTION_FAILED'));
        exit;
    }
    duties_redirect(['error' => str_starts_with($key, 'ADD_') ? $key : 'ADD_ACTION_FAILED']);
} catch (Throwable $e) {
    if (($return_to ?? '') === 'shifts') {
        $start = duties_null($_POST['overview_start'] ?? '');
        header('Location: ../../../module.php?module=duties&view=shifts' . ($start ? '&start=' . urlencode($start) : '') . '&error=ADD_ACTION_FAILED');
        exit;
    }
    duties_redirect(['error' => 'ADD_ACTION_FAILED']);
}

duties_redirect(['error' => 'ADD_ACTION_FAILED']);
