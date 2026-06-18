<?php
require_once __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/../../../getuserinfo.php';
require_once __DIR__ . '/../../../lang.php';
require_once __DIR__ . '/../../../operations/modules_registry.php';
require_once __DIR__ . '/incidents_lib.php';

$incident_lang = incidents_module_language();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../../module.php?module=incidents&view=incidents');
    exit;
}

$centre_id = (int)($_SESSION['centre_id'] ?? $GLOBALS['centre_id'] ?? 0);
$user_id = (int)($_SESSION['account_id'] ?? $GLOBALS['user_id'] ?? 0);

if (!modules_is_active($pdo, 'incidents', $centre_id)) {
    header('Location: ../../../module.php?module=incidents&view=incidents');
    exit;
}

function incident_redirect(array $params = []): void
{
    $return_to = $params['_return_to'] ?? incident_post('return_to');
    unset($params['_return_to']);

    $base = '../../../module.php?module=incidents&view=incidents';
    if ($return_to === 'detail' && !empty($params['incident_id'])) {
        $base = '../../../module.php?module=incidents&view=incident';
    }

    $query = http_build_query($params);
    header('Location: ' . $base . ($query ? '&' . $query : ''));
    exit;
}

function incident_post(string $key, $default = ''): string
{
    if (!isset($_POST[$key]) || is_array($_POST[$key])) {
        return (string)$default;
    }
    return trim((string)$_POST[$key]);
}

if ($centre_id <= 0 || $user_id <= 0) {
    incident_redirect(['error' => incidents_text('CENTRE_CONTEXT_MISSING', 'Centre context missing.')]);
}

try {
    $action = incident_post('incident_action');

    if ($action === 'create') {
        $incident_date = str_replace('T', ' ', incident_post('incident_date'));

        if ($incident_date === '') {
            incident_redirect(['error' => incidents_text('INC_DATE_REQUIRED', 'Incident date is required.')]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO rescue_incidents (
                incident_date,
                incident_location_line_1,
                incident_location_line_2,
                incident_location_city,
                incident_location_postcode,
                incident_centre_ref,
                incident_total_casualties,
                incident_doa,
                incident_mass_cas,
                centre_id,
                user_id
            ) VALUES (
                :incident_date,
                :line_1,
                :line_2,
                :city,
                :postcode,
                :centre_ref,
                :total,
                :doa,
                :mass_cas,
                :centre_id,
                :user_id
            )
        ");

        $stmt->execute([
            ':incident_date' => $incident_date,
            ':line_1' => incident_post('incident_location_line_1'),
            ':line_2' => incident_post('incident_location_line_2'),
            ':city' => incident_post('incident_location_city'),
            ':postcode' => incident_post('incident_location_postcode'),
            ':centre_ref' => incident_post('incident_centre_ref'),
            ':total' => max(0, (int)incident_post('incident_total_casualties', 0)),
            ':doa' => max(0, (int)incident_post('incident_doa', 0)),
            ':mass_cas' => incident_post('incident_mass_cas', '0') === '1' ? 1 : 0,
            ':centre_id' => $centre_id,
            ':user_id' => $user_id,
        ]);

        incident_redirect(['msg' => incidents_text('INCIDENT', 'Incident') . ' ' . incidents_text('CREATED', 'Created') . '.']);
    }

    if ($action === 'update') {
        $incident_id = (int)incident_post('incident_id', 0);
        $incident_date = str_replace('T', ' ', incident_post('incident_date'));

        if ($incident_id <= 0) {
            incident_redirect(['error' => incidents_text('INC_MISSING', 'Incident missing.')]);
        }
        if ($incident_date === '') {
            incident_redirect(['incident_id' => $incident_id, 'error' => incidents_text('INC_DATE_REQUIRED', 'Incident date is required.'), '_return_to' => incident_post('return_to')]);
        }

        $stmt = $pdo->prepare("
            UPDATE rescue_incidents
               SET incident_date = :incident_date,
                   incident_location_line_1 = :line_1,
                   incident_location_line_2 = :line_2,
                   incident_location_city = :city,
                   incident_location_postcode = :postcode,
                   incident_centre_ref = :centre_ref,
                   incident_total_casualties = :total,
                   incident_doa = :doa,
                   incident_mass_cas = :mass_cas,
                   user_id = :user_id
             WHERE incident_id = :incident_id
               AND centre_id = :centre_id
        ");
        $stmt->execute([
            ':incident_date' => $incident_date,
            ':line_1' => incident_post('incident_location_line_1'),
            ':line_2' => incident_post('incident_location_line_2'),
            ':city' => incident_post('incident_location_city'),
            ':postcode' => incident_post('incident_location_postcode'),
            ':centre_ref' => incident_post('incident_centre_ref'),
            ':total' => max(0, (int)incident_post('incident_total_casualties', 0)),
            ':doa' => max(0, (int)incident_post('incident_doa', 0)),
            ':mass_cas' => incident_post('incident_mass_cas', '0') === '1' ? 1 : 0,
            ':user_id' => $user_id,
            ':incident_id' => $incident_id,
            ':centre_id' => $centre_id,
        ]);

        $stmt = $pdo->prepare("
            SELECT 1
            FROM rescue_incidents
            WHERE incident_id = :incident_id
              AND centre_id = :centre_id
            LIMIT 1
        ");
        $stmt->execute([
            ':incident_id' => $incident_id,
            ':centre_id' => $centre_id,
        ]);
        if (!$stmt->fetchColumn()) {
            incident_redirect(['incident_id' => $incident_id, 'error' => incidents_text('INC_NOT_FOUND', 'Incident not found.'), '_return_to' => incident_post('return_to')]);
        }

        incident_redirect(['incident_id' => $incident_id, 'msg' => incidents_text('INCIDENT', 'Incident') . ' ' . incidents_text('UPDATED', 'Updated') . '.', '_return_to' => incident_post('return_to')]);
    }

    if ($action === 'link') {
        $incident_id = (int)incident_post('incident_id', 0);
        $admission_id = (int)incident_post('admission_id', 0);

        if ($incident_id <= 0 || $admission_id <= 0) {
            incident_redirect(['error' => incidents_text('INC_PATIENT_REQUIRED', 'Incident and patient are required.')]);
        }

        $stmt = $pdo->prepare("
            SELECT 1
            FROM rescue_incidents
            WHERE incident_id = :incident_id
              AND centre_id = :centre_id
            LIMIT 1
        ");
        $stmt->execute([
            ':incident_id' => $incident_id,
            ':centre_id' => $centre_id,
        ]);
        if (!$stmt->fetchColumn()) {
            incident_redirect(['error' => incidents_text('INC_NOT_FOUND', 'Incident not found.')]);
        }

        $stmt = $pdo->prepare("
            SELECT finder_id
            FROM rescue_admissions
            WHERE admission_id = :admission_id
              AND centre_id = :centre_id
            LIMIT 1
        ");
        $stmt->execute([
            ':admission_id' => $admission_id,
            ':centre_id' => $centre_id,
        ]);
        $finder_id = $stmt->fetchColumn();
        if ($finder_id === false) {
            incident_redirect(['error' => incidents_text('INC_ADMISSION_NOT_FOUND', 'Admission not found for this centre.')]);
        }

        $stmt = $pdo->prepare("
            SELECT inc_rel_id, is_deleted
            FROM rescue_incident_related
            WHERE incident_id = :incident_id
              AND admission_id = :admission_id
              AND centre_id = :centre_id
            LIMIT 1
        ");
        $stmt->execute([
            ':incident_id' => $incident_id,
            ':admission_id' => $admission_id,
            ':centre_id' => $centre_id,
        ]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $stmt = $pdo->prepare("
                UPDATE rescue_incident_related
                   SET finder_id = :finder_id,
                       user_id = :user_id,
                       is_deleted = 0
                 WHERE inc_rel_id = :inc_rel_id
                   AND centre_id = :centre_id
            ");
            $stmt->execute([
                ':finder_id' => !empty($finder_id) ? (int)$finder_id : null,
                ':user_id' => $user_id,
                ':inc_rel_id' => (int)$existing['inc_rel_id'],
                ':centre_id' => $centre_id,
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO rescue_incident_related (
                    incident_id,
                    centre_id,
                    admission_id,
                    finder_id,
                    user_id,
                    is_deleted
                ) VALUES (
                    :incident_id,
                    :centre_id,
                    :admission_id,
                    :finder_id,
                    :user_id,
                    0
                )
            ");
            $stmt->execute([
                ':incident_id' => $incident_id,
                ':centre_id' => $centre_id,
                ':admission_id' => $admission_id,
                ':finder_id' => !empty($finder_id) ? (int)$finder_id : null,
                ':user_id' => $user_id,
            ]);
        }

        incident_redirect(['incident_id' => $incident_id, 'msg' => incidents_text('PATIENT', 'Patient') . ' ' . incidents_text('LINKED', 'Linked') . '.', '_return_to' => incident_post('return_to')]);
    }

    if ($action === 'unlink') {
        $incident_id = (int)incident_post('incident_id', 0);
        $inc_rel_id = (int)incident_post('inc_rel_id', 0);

        if ($inc_rel_id <= 0) {
            incident_redirect(['error' => incidents_text('INC_RELATIONSHIP_MISSING', 'Relationship missing.')]);
        }

        $stmt = $pdo->prepare("
            UPDATE rescue_incident_related
               SET is_deleted = 1
             WHERE inc_rel_id = :inc_rel_id
               AND centre_id = :centre_id
        ");
        $stmt->execute([
            ':inc_rel_id' => $inc_rel_id,
            ':centre_id' => $centre_id,
        ]);

        incident_redirect(['incident_id' => $incident_id, 'msg' => incidents_text('PATIENT', 'Patient') . ' ' . incidents_text('UNLINKED', 'Unlinked') . '.', '_return_to' => incident_post('return_to')]);
    }

    incident_redirect(['error' => incidents_text('INC_UNKNOWN_ACTION', 'Unknown incident action.')]);
} catch (Throwable $e) {
    incident_redirect(['error' => incidents_text('INC_ACTION_FAILED', 'Incident action failed:') . ' ' . $e->getMessage()]);
}
