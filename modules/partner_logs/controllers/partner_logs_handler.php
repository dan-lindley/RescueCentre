<?php
// modules/partner_logs/controllers/partner_logs_handler.php

require_once __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/../../../getuserinfo.php';
require_once __DIR__ . '/../../../operations/audit.php';
require_once __DIR__ . '/../../../operations/modules_registry.php';
require_once __DIR__ . '/../../../operations/permissions.php';
require_once __DIR__ . '/partner_logs_lib.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$centre_id = partner_logs_centre_id();
$user_id = partner_logs_user_id();
$GLOBALS['centre_id'] = $centre_id;
$GLOBALS['user_id'] = $user_id;

if ($centre_id <= 0 || $user_id <= 0) {
    partner_logs_redirect(['error' => 'Partner log action failed: user or centre context missing.']);
}

$module = modules_find($pdo, 'partner_logs', $centre_id);
if (!$module || empty($module['installed']) || empty($module['enabled'])) {
    partner_logs_redirect(['error' => 'Partner Logs module is not active.']);
}

if (!partner_logs_can_access()) {
    partner_logs_redirect(['error' => 'You do not have permission to access Partner Logs.']);
}

$action = (string)($_POST['action'] ?? '');

if ($action === 'create' || isset($_POST['partner_logs_form'])) {
    $patient_id = (int)($_POST['patient_id'] ?? 0);

    try {
        $data = [
            'date' => $_POST['date'] ?? '',
            'partner_type' => $_POST['partner_type'] ?? '',
            'log_number' => $_POST['log_number'] ?? '',
            'log_notes' => $_POST['log_notes'] ?? '',
            'is_crime' => $_POST['is_crime'] ?? 'No',
            'user_id' => $user_id,
            'centre_id' => $centre_id,
            'patient_id' => $patient_id,
            'admission_id' => $_POST['admission_id'] ?? 0,
        ];

        $partner_log_id = partner_logs_create($pdo, $data);
        $partner = partner_logs_fetch_partner_type_name($pdo, (int)$data['partner_type']);
        partner_logs_add_care_note(
            $pdo,
            $patient_id,
            $partner,
            (string)$data['log_number'],
            $user_id,
            (string)$data['date']
        );

        audit_write(
            $pdo,
            'partner_log_created',
            'partner_logs',
            null,
            array_merge($data, ['partner_log_id' => $partner_log_id, 'partner' => $partner])
        );

        partner_logs_redirect([
            'msg' => 'Partner log added successfully.',
            'open' => 'partner_logs',
            'pid' => $patient_id,
        ]);
    } catch (Throwable $e) {
        partner_logs_redirect([
            'error' => 'Error adding partner log: ' . $e->getMessage(),
            'open' => 'partner_logs',
            'pid' => $patient_id,
        ]);
    }
}

partner_logs_redirect(['error' => 'Partner log action failed.']);
