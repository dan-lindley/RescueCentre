<?php
// modules/quick_tasks/controllers/quick_tasks_handler.php

require_once __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/../../../getuserinfo.php';
require_once __DIR__ . '/../../../operations/audit.php';
require_once __DIR__ . '/../../../operations/modules_registry.php';
require_once __DIR__ . '/quick_tasks_lib.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$centre_id = quick_tasks_centre_id();
$user_id = quick_tasks_user_id();
$GLOBALS['centre_id'] = $centre_id;
$GLOBALS['user_id'] = $user_id;

if ($user_id <= 0) {
    if (isset($_POST['complete_task'], $_POST['task_pt_id'])) {
        http_response_code(403);
        echo 'Error';
        exit;
    }
    quick_tasks_redirect(['error' => 'Quick task action failed: user context missing.']);
}

$module = modules_find($pdo, 'quick_tasks', $centre_id);
if (!$module || empty($module['installed']) || empty($module['enabled'])) {
    if (isset($_POST['complete_task'], $_POST['task_pt_id'])) {
        http_response_code(403);
        echo 'Error';
        exit;
    }
    quick_tasks_redirect(['error' => 'Quick Tasks module is not active.']);
}

$action = (string)($_POST['action'] ?? '');

if ($action === 'assign_task' || isset($_POST['quick_task_assignform'])) {
    $task_id = (int)($_POST['task_id'] ?? 0);
    $patient_id = (int)($_POST['patient_id'] ?? 0);

    try {
        $task = quick_tasks_fetch_task_name($pdo, $task_id);
        $task_pt_id = quick_tasks_assign_task($pdo, $task_id, $patient_id, $user_id);
        quick_tasks_add_care_note($pdo, $patient_id, $task, 'Added', $user_id);

        audit_write(
            $pdo,
            'quick_task_assigned',
            'quick_tasks',
            null,
            [
                'task_pt_id' => $task_pt_id,
                'task_id' => $task_id,
                'task' => $task,
                'patient_id' => $patient_id,
                'status' => 'Waiting',
            ]
        );

        quick_tasks_redirect([
            'msg' => 'Task assigned successfully.',
            'open' => 'quick_tasks',
            'pid' => $patient_id,
        ]);
    } catch (Throwable $e) {
        quick_tasks_redirect([
            'error' => 'Error assigning task: ' . $e->getMessage(),
            'open' => 'quick_tasks',
            'pid' => $patient_id,
        ]);
    }
}

if ($action === 'complete_task' || isset($_POST['complete_task'], $_POST['task_pt_id'])) {
    $task_pt_id = (int)($_POST['task_pt_id'] ?? 0);

    try {
        $old = quick_tasks_complete_task($pdo, $task_pt_id, $user_id);
        $new = $task_pt_id > 0 ? quick_tasks_fetch_patient_task($pdo, $task_pt_id) : null;

        if ($old) {
            quick_tasks_add_care_note(
                $pdo,
                (int)($old['patient_id'] ?? 0),
                (string)($old['task'] ?? 'Unknown task'),
                'Completed',
                $user_id
            );
        }

        if ($old || $new) {
            audit_write(
                $pdo,
                'quick_task_completed',
                'quick_tasks',
                $old,
                $new
            );
        }

        echo 'OK';
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo 'Error';
        exit;
    }
}

quick_tasks_redirect(['error' => 'Quick task action failed.']);
