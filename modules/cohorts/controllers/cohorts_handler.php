<?php
require_once __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/cohorts_lib.php';
require_once __DIR__ . '/../../../operations/transfers_log.php';
require_once __DIR__ . '/../../../operations/audit.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$centre_id = cohorts_centre_id();
$user_id = cohorts_user_id();
$GLOBALS['centre_id'] = $centre_id;
$GLOBALS['user_id'] = $user_id;
transfers_auto($pdo);

if ($centre_id <= 0 || $user_id <= 0) {
    cohorts_redirect('../../../patients.php', ['error' => 'Cohort action failed: user or centre context missing.']);
}

$action = (string)($_POST['action'] ?? '');

try {
    if ($action === 'create') {
        $cohort_name = trim((string)($_POST['cohort_name'] ?? ''));
        $location_key = trim((string)($_POST['location_key'] ?? ''));
        $location_label = trim((string)($_POST['location_label'] ?? ''));
        $location_id = (int)($_POST['location_id'] ?? 0);
        $notes = trim((string)($_POST['notes'] ?? ''));
        $patient_ids = array_values(array_unique(array_filter(array_map('intval', $_POST['patient_ids'] ?? []))));

        if ($cohort_name === '') {
            $cohort_name = 'Cohort - ' . ($location_label !== '' ? $location_label : date('d-m-Y H:i'));
        }

        if (!$patient_ids) {
            cohorts_redirect('../../../patients.php', ['error' => 'Select at least one patient for the cohort.']);
        }

        $patients = cohorts_patient_current_admissions($pdo, $patient_ids, $centre_id);
        $valid_ids = array_keys($patients);

        if (!$valid_ids) {
            cohorts_redirect('../../../patients.php', ['error' => 'No valid admitted patients were selected.']);
        }

        foreach ($valid_ids as $pid) {
            if (cohorts_patient_has_other_active_cohort($pdo, (int)$pid)) {
                cohorts_redirect('../../../patients.php', ['error' => 'One or more selected patients are already in an active cohort.']);
            }
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO rescue_cohorts
                (centre_id, location_id, location_key, location_label, cohort_name, status, created_by, notes)
            VALUES
                (:centre_id, :location_id, :location_key, :location_label, :cohort_name, 'active', :created_by, :notes)
        ");
        $stmt->execute([
            ':centre_id' => $centre_id,
            ':location_id' => $location_id,
            ':location_key' => $location_key !== '' ? $location_key : null,
            ':location_label' => $location_label !== '' ? $location_label : null,
            ':cohort_name' => $cohort_name,
            ':created_by' => $user_id,
            ':notes' => $notes !== '' ? $notes : null,
        ]);

        $cohort_id = (int)$pdo->lastInsertId();

        foreach ($valid_ids as $pid) {
            cohorts_add_member($pdo, $cohort_id, (int)$pid, $user_id);
        }

        $pdo->commit();

        cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'msg' => 'Cohort created.']);
    }

    if ($action === 'add_member') {
        $cohort_id = (int)($_POST['cohort_id'] ?? 0);
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $cohort = cohorts_fetch($pdo, $cohort_id, $centre_id);

        if (!$cohort || $cohort['status'] !== 'active' || $patient_id <= 0) {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['error' => 'Could not add member.']);
        }

        if (cohorts_patient_has_other_active_cohort($pdo, $patient_id, $cohort_id)) {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'error' => 'Patient is already in another active cohort.']);
        }

        $patients = cohorts_patient_current_admissions($pdo, [$patient_id], $centre_id);
        if (empty($patients[$patient_id])) {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'error' => 'Patient is not currently admitted at this centre.']);
        }

        cohorts_add_member($pdo, $cohort_id, $patient_id, $user_id);
        cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'msg' => 'Member added.']);
    }

    if ($action === 'remove_member') {
        $cohort_id = (int)($_POST['cohort_id'] ?? 0);
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? 'Removed from cohort'));
        $cohort = cohorts_fetch($pdo, $cohort_id, $centre_id);

        if (!$cohort || $patient_id <= 0) {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['error' => 'Could not remove member.']);
        }

        $stmt = $pdo->prepare("
            UPDATE rescue_cohort_members
            SET left_at = NOW(),
                left_by = :left_by,
                leave_reason = :reason
            WHERE cohort_id = :cohort_id
              AND patient_id = :patient_id
              AND left_at IS NULL
        ");
        $stmt->execute([
            ':left_by' => $user_id,
            ':reason' => $reason,
            ':cohort_id' => $cohort_id,
            ':patient_id' => $patient_id,
        ]);

        cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'msg' => 'Member removed.']);
    }

    if ($action === 'end') {
        $cohort_id = (int)($_POST['cohort_id'] ?? 0);
        $cohort = cohorts_fetch($pdo, $cohort_id, $centre_id);

        if (!$cohort) {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['error' => 'Cohort not found.']);
        }

        $stmt = $pdo->prepare("
            UPDATE rescue_cohorts
            SET status = 'ended',
                ended_by = :ended_by,
                ended_at = NOW()
            WHERE cohort_id = :cohort_id
              AND centre_id = :centre_id
        ");
        $stmt->execute([
            ':ended_by' => $user_id,
            ':cohort_id' => $cohort_id,
            ':centre_id' => $centre_id,
        ]);

        cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'msg' => 'Cohort ended.']);
    }

    if ($action === 'move') {
        $cohort_id = (int)($_POST['cohort_id'] ?? 0);
        $new_location_id = (int)($_POST['new_location_id'] ?? 0);
        $cohort = cohorts_fetch($pdo, $cohort_id, $centre_id);

        if (!$cohort || $cohort['status'] !== 'active' || $new_location_id <= 0) {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'error' => 'Could not move cohort.']);
        }

        $locStmt = $pdo->prepare("
            SELECT location_id, location_name
            FROM rescue_locations
            WHERE location_id = :location_id
              AND centre_id = :centre_id
              AND (deleted = 0 OR deleted IS NULL)
            LIMIT 1
        ");
        $locStmt->execute([':location_id' => $new_location_id, ':centre_id' => $centre_id]);
        $newLocation = $locStmt->fetch(PDO::FETCH_ASSOC);
        if (!$newLocation) {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'error' => 'Invalid location selected.']);
        }

        $member_ids = cohorts_active_member_ids($pdo, $cohort_id);
        if (!$member_ids) {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'error' => 'This cohort has no active members.']);
        }

        $patient_admissions = cohorts_patient_current_admissions($pdo, $member_ids, $centre_id);
        if (!$patient_admissions) {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'error' => 'No current admissions found for cohort members.']);
        }
        if (count($patient_admissions) !== count($member_ids)) {
            $missingMemberIds = array_values(array_diff($member_ids, array_map('intval', array_keys($patient_admissions))));
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', [
                'cohort_id' => $cohort_id,
                'error' => 'Could not move cohort because current admissions could not be found for CRN ' . implode(', ', $missingMemberIds) . '.',
            ]);
        }

        $pdo->beginTransaction();

        $moveStmt = $pdo->prepare("
            UPDATE rescue_admissions
            SET current_location_id = :location_id,
                current_location = :location_name
            WHERE admission_id = :admission_id
              AND patient_id = :patient_id
              AND centre_id = :centre_id
            LIMIT 1
        ");

        foreach ($patient_admissions as $pid => $adm) {
            $oldLocationId = (int)($adm['current_location_id'] ?? 0);
            $oldLocationName = (string)($adm['current_location'] ?? '');
            $admissionId = (int)($adm['admission_id'] ?? 0);
            if ($admissionId <= 0) {
                continue;
            }

            $moveStmt->execute([
                ':location_id' => $new_location_id,
                ':location_name' => (string)$newLocation['location_name'],
                ':admission_id' => $admissionId,
                ':patient_id' => (int)$pid,
                ':centre_id' => $centre_id,
            ]);

            transfers_log($pdo, 'internal_move', [
                'patient_id' => (int)$pid,
                'admission_id' => $admissionId,
                'from_location_id' => $oldLocationId > 0 ? $oldLocationId : null,
                'to_location_id' => $new_location_id,
                'notes' => 'Cohort move: ' . (string)$cohort['cohort_name'],
            ]);

            audit_write(
                $pdo,
                'cohort_member_moved',
                'cohorts',
                [
                    'cohort_id' => $cohort_id,
                    'cohort_name' => (string)$cohort['cohort_name'],
                    'patient_id' => (int)$pid,
                    'admission_id' => $admissionId,
                    'current_location_id' => $oldLocationId > 0 ? $oldLocationId : null,
                    'current_location' => $oldLocationName !== '' ? $oldLocationName : null,
                ],
                [
                    'cohort_id' => $cohort_id,
                    'cohort_name' => (string)$cohort['cohort_name'],
                    'patient_id' => (int)$pid,
                    'admission_id' => $admissionId,
                    'current_location_id' => $new_location_id,
                    'current_location' => (string)$newLocation['location_name'],
                ]
            );
        }

        $cohortUpdate = $pdo->prepare("
            UPDATE rescue_cohorts
            SET location_id = :location_id,
                location_key = :location_key,
                location_label = :location_label
            WHERE cohort_id = :cohort_id
              AND centre_id = :centre_id
        ");
        $cohortUpdate->execute([
            ':location_id' => $new_location_id,
            ':location_key' => (string)$newLocation['location_name'],
            ':location_label' => (string)$newLocation['location_name'],
            ':cohort_id' => $cohort_id,
            ':centre_id' => $centre_id,
        ]);

        audit_write(
            $pdo,
            'cohort_moved',
            'cohorts',
            [
                'cohort_id' => $cohort_id,
                'cohort_name' => (string)$cohort['cohort_name'],
                'location_id' => !empty($cohort['location_id']) ? (int)$cohort['location_id'] : null,
                'location_label' => $cohort['location_label'] ?? null,
                'active_member_count' => count($patient_admissions),
            ],
            [
                'cohort_id' => $cohort_id,
                'cohort_name' => (string)$cohort['cohort_name'],
                'location_id' => $new_location_id,
                'location_label' => (string)$newLocation['location_name'],
                'active_member_count' => count($patient_admissions),
            ]
        );

        $pdo->commit();

        cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'msg' => 'Cohort moved to ' . (string)$newLocation['location_name'] . '.']);
    }

    if ($action === 'discharge') {
        $cohort_id = (int)($_POST['cohort_id'] ?? 0);
        $disposition_ui = trim((string)($_POST['disposition'] ?? ''));
        $disp_date = trim((string)($_POST['disposition_date'] ?? ''));
        $euth_method = trim((string)($_POST['euthanasia_method'] ?? 'Not Applicable'));
        $comment = trim((string)($_POST['disposition_comment'] ?? ''));
        $disp_date_db = str_replace('T', ' ', $disp_date);
        if (strlen($disp_date_db) === 16) {
            $disp_date_db .= ':00';
        }
        $dispDateObj = DateTime::createFromFormat('Y-m-d H:i:s', $disp_date_db);
        $disp_date_db = $dispDateObj instanceof DateTime ? $dispDateObj->format('Y-m-d H:i:s') : '';
        $cohort = cohorts_fetch($pdo, $cohort_id, $centre_id);

        if (!$cohort || $cohort['status'] !== 'active' || $disposition_ui === '' || $disp_date_db === '') {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'error' => 'Could not discharge cohort.']);
        }

        $member_ids = cohorts_active_member_ids($pdo, $cohort_id);
        if (!$member_ids) {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'error' => 'This cohort has no active members.']);
        }

        $patient_admissions = cohorts_patient_current_admissions($pdo, $member_ids, $centre_id);
        if (!$patient_admissions) {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'error' => 'No current admissions found for cohort members.']);
        }

        $pat_status = 'Captive';
        $pat_state = 'Admitted';
        $adm_status = 'Active';
        $adm_survived = 1;
        $disposition_db = 'Held in captivity';

        $died_dispositions = [
            'Died - Euthanised',
            'Died - within 48 hours',
            'Died - after 48 hours',
            'Died - on admission',
        ];

        if ($disposition_ui === 'Released') {
            $pat_status = 'Released';
            $pat_state = 'Closed';
            $adm_status = 'Closed';
            $adm_survived = 1;
            $disposition_db = 'Released';
        } elseif ($disposition_ui === 'Transferred to another rescue') {
            $pat_status = 'Transferred';
            $pat_state = 'Closed';
            $adm_status = 'Closed';
            $adm_survived = 1;
            $disposition_db = 'Transferred Out';
        } elseif ($disposition_ui === 'Long-term captive') {
            $pat_status = 'Captive';
            $pat_state = 'Admitted';
            $adm_status = 'Active';
            $adm_survived = 1;
            $disposition_db = 'Long-term Captive';
        } elseif (in_array($disposition_ui, $died_dispositions, true)) {
            $pat_status = 'Deceased';
            $pat_state = 'Deceased';
            $adm_status = 'Closed';
            $adm_survived = 0;
            $disposition_db = $disposition_ui;
        } elseif ($disposition_ui === 'Held in captivity') {
            $pat_status = 'Captive';
            $pat_state = 'Admitted';
            $adm_status = 'Active';
            $adm_survived = 1;
            $disposition_db = 'Held in captivity';
        } else {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'error' => 'Unknown disposition selected.']);
        }

        $dispIdStmt = $pdo->prepare("
            SELECT disposition_id
            FROM rescue_dispositions
            WHERE disposition = :disp
            LIMIT 1
        ");
        $dispIdStmt->execute([':disp' => $disposition_ui]);
        $disposition_id = (int)($dispIdStmt->fetchColumn() ?? 0);

        $event_type = '';
        if ($disposition_ui === 'Released') {
            $event_type = 'released';
        } elseif ($disposition_ui === 'Transferred to another rescue') {
            $event_type = 'transfer_out';
        } elseif ($disposition_ui === 'Died - Euthanised') {
            $event_type = 'euthanised';
        } elseif (in_array($disposition_ui, $died_dispositions, true)) {
            $event_type = 'died';
        }

        $pdo->beginTransaction();

        $admStmt = $pdo->prepare("
            UPDATE rescue_admissions
            SET euthanasia_method = :euthanasia_method,
                disposition_user = :disp_user,
                disposition_centre = :disp_centre,
                disposition = :disposition,
                disposition_date = :disp_date,
                status = :adm_status,
                survived = :survived,
                disposition_comment = :disp_comment
            WHERE admission_id = :admission_id
              AND patient_id = :patient_id
              AND centre_id = :centre_id
            LIMIT 1
        ");
        $patientStmt = $pdo->prepare("
            UPDATE rescue_patients
            SET status = :pat_status,
                state = :pat_state
            WHERE patient_id = :patient_id
              AND centre_id = :centre_id
            LIMIT 1
        ");
        $existingLogStmt = $pdo->prepare("
            SELECT 1
            FROM rescue_transfers_log
            WHERE admission_id = :admission_id
              AND disposition_id IS NOT NULL
            LIMIT 1
        ");

        foreach ($patient_admissions as $pid => $adm) {
            $admissionId = (int)($adm['admission_id'] ?? 0);
            if ($admissionId <= 0) {
                continue;
            }

            $admStmt->execute([
                ':euthanasia_method' => $euth_method !== '' ? $euth_method : 'Not Applicable',
                ':disp_user' => $user_id,
                ':disp_centre' => $centre_id,
                ':disposition' => $disposition_db,
                ':disp_date' => $disp_date_db,
                ':adm_status' => $adm_status,
                ':survived' => $adm_survived,
                ':disp_comment' => $comment,
                ':admission_id' => $admissionId,
                ':patient_id' => (int)$pid,
                ':centre_id' => $centre_id,
            ]);

            $patientStmt->execute([
                ':pat_status' => $pat_status,
                ':pat_state' => $pat_state,
                ':patient_id' => (int)$pid,
                ':centre_id' => $centre_id,
            ]);

            if ($adm_status === 'Closed' && $event_type !== '' && $disposition_id > 0) {
                $existingLogStmt->execute([':admission_id' => $admissionId]);
                if (!$existingLogStmt->fetchColumn()) {
                    transfers_log($pdo, $event_type, [
                        'patient_id' => (int)$pid,
                        'admission_id' => $admissionId,
                        'event_at' => $disp_date_db,
                        'from_location_id' => !empty($adm['current_location_id']) ? (int)$adm['current_location_id'] : null,
                        'disposition_id' => $disposition_id,
                        'notes' => 'Cohort disposition: ' . (string)$cohort['cohort_name'],
                    ]);
                }
            }
        }

        if ($adm_status === 'Closed') {
            $leaveStmt = $pdo->prepare("
                UPDATE rescue_cohort_members
                SET left_at = NOW(),
                    left_by = :left_by,
                    leave_reason = :reason
                WHERE cohort_id = :cohort_id
                  AND left_at IS NULL
            ");
            $leaveStmt->execute([
                ':left_by' => $user_id,
                ':reason' => 'Cohort disposition: ' . $disposition_db,
                ':cohort_id' => $cohort_id,
            ]);

            $endStmt = $pdo->prepare("
                UPDATE rescue_cohorts
                SET status = 'ended',
                    ended_by = :ended_by,
                    ended_at = NOW()
                WHERE cohort_id = :cohort_id
                  AND centre_id = :centre_id
            ");
            $endStmt->execute([
                ':ended_by' => $user_id,
                ':cohort_id' => $cohort_id,
                ':centre_id' => $centre_id,
            ]);
        }

        $pdo->commit();

        cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'msg' => 'Cohort disposition applied to active members.']);
    }

    if ($action === 'note') {
        $cohort_id = (int)($_POST['cohort_id'] ?? 0);
        $note_text = trim((string)($_POST['note_text'] ?? ''));
        $public = isset($_POST['public']) ? 1 : 0;
        $cohort = cohorts_fetch($pdo, $cohort_id, $centre_id);

        if (!$cohort || $cohort['status'] !== 'active' || $note_text === '') {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'error' => 'Could not add cohort note.']);
        }

        $member_ids = cohorts_active_member_ids($pdo, $cohort_id);
        if (!$member_ids) {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'error' => 'This cohort has no active members.']);
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO rescue_cohort_care_notes (cohort_id, note_text, created_by)
            VALUES (:cohort_id, :note_text, :created_by)
        ");
        $stmt->execute([
            ':cohort_id' => $cohort_id,
            ':note_text' => $note_text,
            ':created_by' => $user_id,
        ]);

        $author = (string)($_SESSION['account_name'] ?? 'Cohort');
        $message = '[Cohort note: ' . $cohort['cohort_name'] . '] ' . $note_text;
        $stmt = $pdo->prepare("
            INSERT INTO rescue_notes_patients (patient_id, message, author, public, image_id, date)
            VALUES (:patient_id, :message, :author, :public, NULL, NOW())
        ");
        foreach ($member_ids as $pid) {
            $stmt->execute([
                ':patient_id' => $pid,
                ':message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
                ':author' => htmlspecialchars($author, ENT_QUOTES, 'UTF-8'),
                ':public' => $public,
            ]);
        }

        $pdo->commit();

        cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'msg' => 'Cohort note added to active members.']);
    }

    if ($action === 'feed') {
        $cohort_id = (int)($_POST['cohort_id'] ?? 0);
        $cohort = cohorts_fetch($pdo, $cohort_id, $centre_id);
        $centre_diet_item_id = (int)($_POST['centre_diet_item_id'] ?? 0);
        $fed_at_raw = (string)($_POST['fed_at'] ?? '');
        $amount_in = (float)($_POST['offered_value'] ?? $_POST['amount_in'] ?? 0);
        $amount_out = (float)($_POST['remaining_value'] ?? $_POST['amount_out'] ?? 0);
        $remaining_percent = ($_POST['remaining_percent'] ?? '') !== '' ? (float)$_POST['remaining_percent'] : null;
        $is_estimated = isset($_POST['is_estimated']) ? 1 : 0;
        $notes = trim((string)($_POST['notes'] ?? ''));

        if (!$cohort || $cohort['status'] !== 'active' || $centre_diet_item_id <= 0) {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'error' => 'Could not log cohort feed.']);
        }

        $member_ids = cohorts_active_member_ids($pdo, $cohort_id);
        if (!$member_ids) {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'error' => 'This cohort has no active members.']);
        }

        if ($amount_in < 0) $amount_in = 0;
        if ($amount_out < 0) $amount_out = 0;
        if ($remaining_percent !== null) {
            if ($remaining_percent < 0) $remaining_percent = 0;
            if ($remaining_percent > 100) $remaining_percent = 100;
            $amount_out = $amount_in * ($remaining_percent / 100);
        }
        if ($amount_out > $amount_in) $amount_out = $amount_in;

        $dietStmt = $pdo->prepare("
            SELECT di.diet_item_id, di.type, di.default_unit, di.name
            FROM rescue_centre_diet_items cdi
            JOIN rescue_diet_items di ON di.diet_item_id = cdi.diet_item_id
            WHERE cdi.centre_diet_item_id = :cdi
              AND cdi.centre_id = :centre_id
              AND cdi.is_enabled = 1
            LIMIT 1
        ");
        $dietStmt->execute([':cdi' => $centre_diet_item_id, ':centre_id' => $centre_id]);
        $diet = $dietStmt->fetch(PDO::FETCH_ASSOC);

        if (!$diet) {
            cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'error' => 'Diet item is not available.']);
        }

        $fed_at = date('Y-m-d H:i:s');
        if ($fed_at_raw !== '') {
            $fed_at = str_replace('T', ' ', $fed_at_raw) . ':00';
        }

        $member_count = count($member_ids);
        $per_offered = $amount_in / $member_count;
        $per_remaining = $amount_out / $member_count;
        $per_consumed = max(0, $per_offered - $per_remaining);
        $unit = (string)$diet['default_unit'];
        if ((string)$diet['type'] !== 'solid') {
            $is_estimated = 0;
            $remaining_percent = null;
        }
        $patient_admissions = cohorts_patient_current_admissions($pdo, $member_ids, $centre_id);

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO rescue_cohort_feeding_logs
                (cohort_id, food_item_id, amount_in, amount_out, amount_unit, fed_at, logged_by, notes)
            VALUES
                (:cohort_id, :food_item_id, :amount_in, :amount_out, :amount_unit, :fed_at, :logged_by, :notes)
        ");
        $stmt->execute([
            ':cohort_id' => $cohort_id,
            ':food_item_id' => $centre_diet_item_id,
            ':amount_in' => $amount_in,
            ':amount_out' => $amount_out,
            ':amount_unit' => $unit,
            ':fed_at' => $fed_at,
            ':logged_by' => $user_id,
            ':notes' => $notes !== '' ? $notes : null,
        ]);

        $feedNotes = '[Cohort feed: ' . $cohort['cohort_name'] . ']';
        if ($notes !== '') {
            $feedNotes .= ' ' . $notes;
        }

        $stmt = $pdo->prepare("
            INSERT INTO rescue_feeding_events
                (patient_id, admission_id, centre_id, diet_item_id, feed_at, feed_type, status,
                 offered_value, offered_unit, is_estimated, remaining_value, remaining_percent,
                 consumed_value, consumed_unit, notes, created_by, created_at)
            VALUES
                (:patient_id, :admission_id, :centre_id, :diet_item_id, :feed_at, :feed_type, 'normal',
                 :offered_value, :offered_unit, :is_estimated, :remaining_value, :remaining_percent,
                 :consumed_value, :consumed_unit, :notes, :created_by, NOW())
        ");

        foreach ($member_ids as $pid) {
            $adm = $patient_admissions[$pid] ?? [];
            $stmt->execute([
                ':patient_id' => $pid,
                ':admission_id' => !empty($adm['admission_id']) ? (int)$adm['admission_id'] : null,
                ':centre_id' => $centre_id,
                ':diet_item_id' => (int)$diet['diet_item_id'],
                ':feed_at' => $fed_at,
                ':feed_type' => (string)$diet['type'],
                ':offered_value' => $per_offered,
                ':offered_unit' => $unit,
                ':is_estimated' => $is_estimated,
                ':remaining_value' => $per_remaining,
                ':remaining_percent' => $remaining_percent,
                ':consumed_value' => $per_consumed,
                ':consumed_unit' => $unit,
                ':notes' => htmlspecialchars($feedNotes, ENT_QUOTES, 'UTF-8'),
                ':created_by' => $user_id,
            ]);
        }

        $pdo->commit();

        cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['cohort_id' => $cohort_id, 'msg' => 'Cohort feed logged and split across active members.']);
    }

    cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['error' => 'Unknown cohort action.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    cohorts_redirect('../../../module.php?module=cohorts&view=cohorts', ['error' => 'Cohort action failed: ' . $e->getMessage()]);
}

