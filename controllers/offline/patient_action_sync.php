<?php
require_once __DIR__ . '/../../connection.php';
require_once __DIR__ . '/../../getuserinfo.php';
require_once __DIR__ . '/../../m/mobile_modules.php';

header('Content-Type: application/json');

function offline_action_json(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function offline_action_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    return is_array($data) ? $data : [];
}

function offline_action_value(array $payload, string $key, $default = '')
{
    return isset($payload[$key]) && !is_array($payload[$key]) ? trim((string)$payload[$key]) : $default;
}

function offline_score_age(string $text): int
{
    $map = [
        'Newborn' => 3,
        'Dependent Juvenile' => 2,
        'Independent Juvenile' => 1,
        'Hatchling' => 3,
        'Fledgling' => 2,
        'Adult' => 0,
    ];
    return $map[$text] ?? 0;
}

function offline_score_severity(string $text): int
{
    $map = [
        'Apparently Healthy' => 0,
        'Mildly unwell' => 0,
        'Obvious Injuries' => 1,
        'Severe Injuries' => 2,
        'Near Death' => 3,
    ];
    return $map[$text] ?? 0;
}

function offline_score_bcs(string $text): int
{
    $map = [
        'BCS 1 Skeletal' => 3,
        'BCS 2 Underweight' => 2,
        'BCS 3 Slightly Underweight' => 1,
        'BCS 4 Healthy' => 0,
        'BCS 5 Overweight' => 0,
    ];
    return $map[$text] ?? 0;
}

$body = offline_action_input();
$entity_type = offline_action_value($body, 'entity_type');
$queue_id = offline_action_value($body, 'queue_id');
$payload = isset($body['payload']) && is_array($body['payload']) ? $body['payload'] : [];

$centre_id = (int)($_SESSION['centre_id'] ?? $GLOBALS['centre_id'] ?? 0);
$user_id = (int)($_SESSION['account_id'] ?? $_SESSION['user_id'] ?? $GLOBALS['user_id'] ?? 0);
$record_name = (string)($_SESSION['account_name'] ?? $GLOBALS['record_name'] ?? '');

if ($centre_id <= 0 || $user_id <= 0) {
    offline_action_json(['success' => false, 'message' => 'Session or centre context missing.'], 401);
}

$patient_id = (int)offline_action_value($payload, 'patient_id', 0);
$admission_id = (int)offline_action_value($payload, 'admission_id', 0);

if ($patient_id <= 0) {
    offline_action_json(['success' => false, 'message' => 'Patient is required.'], 422);
}

$check = $pdo->prepare("
    SELECT p.patient_id
    FROM rescue_patients p
    WHERE p.patient_id = :patient_id
      AND p.centre_id = :centre_id
    LIMIT 1
");
$check->execute([
    ':patient_id' => $patient_id,
    ':centre_id' => $centre_id,
]);
if (!$check->fetchColumn()) {
    offline_action_json(['success' => false, 'message' => 'Patient not found for this centre.'], 404);
}

if ($admission_id <= 0) {
    $stmt = $pdo->prepare("
        SELECT admission_id
        FROM rescue_admissions
        WHERE patient_id = :patient_id
          AND centre_id = :centre_id
        ORDER BY admission_date DESC, admission_id DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':patient_id' => $patient_id,
        ':centre_id' => $centre_id,
    ]);
    $admission_id = (int)$stmt->fetchColumn();
}

try {
    switch ($entity_type) {
        case 'patient_weight':
            $stmt = $pdo->prepare("
                INSERT INTO rescue_weights (patient_id, weight, weight_unit, date)
                VALUES (:patient_id, :weight, :unit, :date)
            ");
            $stmt->execute([
                ':patient_id' => $patient_id,
                ':weight' => offline_action_value($payload, 'weight'),
                ':unit' => offline_action_value($payload, 'weight_unit', 'g'),
                ':date' => str_replace('T', ' ', offline_action_value($payload, 'date', date('Y-m-d H:i:s'))),
            ]);
            $id = (int)$pdo->lastInsertId();
            break;

        case 'patient_measurement':
            $stmt = $pdo->prepare("
                INSERT INTO rescue_measurements (patient_id, measurement, measurement_unit, date)
                VALUES (:patient_id, :measurement, :unit, :date)
            ");
            $stmt->execute([
                ':patient_id' => $patient_id,
                ':measurement' => offline_action_value($payload, 'measurement'),
                ':unit' => offline_action_value($payload, 'measurement_unit', 'cm'),
                ':date' => str_replace('T', ' ', offline_action_value($payload, 'date', date('Y-m-d H:i:s'))),
            ]);
            $id = (int)$pdo->lastInsertId();
            break;

        case 'patient_carenote':
            $message = offline_action_value($payload, 'new_note');
            if ($message === '') {
                offline_action_json(['success' => false, 'message' => 'Care note text is required.'], 422);
            }
            $stmt = $pdo->prepare("
                INSERT INTO rescue_notes_patients (patient_id, message, author, public, image_id, date)
                VALUES (:patient_id, :message, :author, :public, NULL, :date)
            ");
            $stmt->execute([
                ':patient_id' => $patient_id,
                ':message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
                ':author' => htmlspecialchars(offline_action_value($payload, 'note_author', $record_name), ENT_QUOTES, 'UTF-8'),
                ':public' => offline_action_value($payload, 'public', '0') === '1' ? 1 : 0,
                ':date' => str_replace('T', ' ', offline_action_value($payload, 'date', date('Y-m-d H:i:s'))),
            ]);
            $id = (int)$pdo->lastInsertId();
            break;

        case 'patient_observation':
            $age_text = offline_action_value($payload, 'obs_age_text');
            $sev_text = offline_action_value($payload, 'obs_sev_text');
            $bcs_text = offline_action_value($payload, 'obs_bcs_text');
            $stmt = $pdo->prepare("
                INSERT INTO rescue_observations
                    (patient_id, admission_id, user_id, obs_severity_score, obs_severity_text,
                     obs_bcs_score, obs_bcs_text, obs_age_score, obs_age_text, obs_notes, obs_date)
                VALUES
                    (:patient_id, :admission_id, :user_id, :sev_score, :sev_text,
                     :bcs_score, :bcs_text, :age_score, :age_text, :notes, :date)
            ");
            $stmt->execute([
                ':patient_id' => $patient_id,
                ':admission_id' => $admission_id ?: null,
                ':user_id' => $user_id,
                ':sev_score' => offline_score_severity($sev_text),
                ':sev_text' => $sev_text,
                ':bcs_score' => offline_score_bcs($bcs_text),
                ':bcs_text' => $bcs_text,
                ':age_score' => offline_score_age($age_text),
                ':age_text' => $age_text,
                ':notes' => offline_action_value($payload, 'obs_notes'),
                ':date' => str_replace('T', ' ', offline_action_value($payload, 'date', date('Y-m-d H:i:s'))),
            ]);
            $id = (int)$pdo->lastInsertId();
            break;

        case 'patient_treatment':
            $stmt = $pdo->prepare("
                INSERT INTO rescue_treatments (patient_id, treatment, treatment_free_text, done_by, date)
                VALUES (:patient_id, :treatment, :notes, :done_by, :date)
            ");
            $stmt->execute([
                ':patient_id' => $patient_id,
                ':treatment' => offline_action_value($payload, 'treatment'),
                ':notes' => offline_action_value($payload, 'treatment_free_text'),
                ':done_by' => offline_action_value($payload, 'done_by', $record_name),
                ':date' => str_replace('T', ' ', offline_action_value($payload, 'date', date('Y-m-d H:i:s'))),
            ]);
            $id = (int)$pdo->lastInsertId();
            break;

        case 'patient_prescription':
            $stmt = $pdo->prepare("
                INSERT INTO rescue_prescriptions
                    (patient_id, centre_id, admission_id, user_id, medication, dose, dose_type,
                     duration, frequency, route, by_weight, date)
                VALUES
                    (:patient_id, :centre_id, :admission_id, :user_id, :medication, :dose, :dose_type,
                     :duration, :frequency, :route, :by_weight, :date)
            ");
            $stmt->execute([
                ':patient_id' => $patient_id,
                ':centre_id' => $centre_id,
                ':admission_id' => $admission_id ?: null,
                ':user_id' => $user_id,
                ':medication' => offline_action_value($payload, 'medication'),
                ':dose' => offline_action_value($payload, 'dose'),
                ':dose_type' => offline_action_value($payload, 'dose_type'),
                ':duration' => offline_action_value($payload, 'duration'),
                ':frequency' => offline_action_value($payload, 'frequency'),
                ':route' => offline_action_value($payload, 'route'),
                ':by_weight' => offline_action_value($payload, 'dose_by_weight', '0') === '1' ? 1 : 0,
                ':date' => str_replace('T', ' ', offline_action_value($payload, 'date', date('Y-m-d H:i:s'))),
            ]);
            $id = (int)$pdo->lastInsertId();
            break;

        case 'patient_medication':
            $stmt = $pdo->prepare("
                INSERT INTO rescue_medications_given
                    (patient_id, centre_id, given_by_id, medication_given, given_by, dose, dose_type,
                     stock_item_used, batch_given, exp_given, vol_given, date)
                VALUES
                    (:patient_id, :centre_id, :given_by_id, :medication_given, :given_by, :dose, :dose_type,
                     NULL, :batch_given, :exp_given, :vol_given, :date)
            ");
            $vol = offline_action_value($payload, 'volume_used');
            $stmt->execute([
                ':patient_id' => $patient_id,
                ':centre_id' => $centre_id,
                ':given_by_id' => $user_id,
                ':medication_given' => offline_action_value($payload, 'medication_given'),
                ':given_by' => offline_action_value($payload, 'given_by', $record_name),
                ':dose' => offline_action_value($payload, 'dose'),
                ':dose_type' => offline_action_value($payload, 'dose_type'),
                ':batch_given' => offline_action_value($payload, 'bn_given'),
                ':exp_given' => offline_action_value($payload, 'exp_given'),
                ':vol_given' => $vol === '' ? null : (float)$vol,
                ':date' => str_replace('T', ' ', offline_action_value($payload, 'date_given', date('Y-m-d H:i:s'))),
            ]);
            $id = (int)$pdo->lastInsertId();
            break;

        case 'patient_lab':
            $stmt = $pdo->prepare("
                INSERT INTO rescue_labs
                    (patient_id, centre_id, admission_id, lab_date, sample_type, lab_result, reported_by, lab_test, is_positive)
                VALUES
                    (:patient_id, :centre_id, :admission_id, :lab_date, :sample_type, :lab_result, :reported_by, :lab_test, :is_positive)
            ");
            $stmt->execute([
                ':patient_id' => $patient_id,
                ':centre_id' => $centre_id,
                ':admission_id' => $admission_id ?: null,
                ':lab_date' => str_replace('T', ' ', offline_action_value($payload, 'lab_date', date('Y-m-d H:i:s'))),
                ':sample_type' => (int)offline_action_value($payload, 'sample_type', 0),
                ':lab_result' => offline_action_value($payload, 'lab_result'),
                ':reported_by' => offline_action_value($payload, 'reported_by', $record_name),
                ':lab_test' => (int)offline_action_value($payload, 'lab_test', 0),
                ':is_positive' => offline_action_value($payload, 'is_positive', '0') === '1' ? 1 : 0,
            ]);
            $id = (int)$pdo->lastInsertId();
            break;

        case 'patient_partnerlog':
            if (!mobile_module_is_active($pdo, 'partner_logs', $centre_id)) {
                offline_action_json(['success' => false, 'message' => 'Partner Logs module is not active for this centre.'], 403);
            }

            $stmt = $pdo->prepare("
                INSERT INTO rescue_partner_log
                    (patient_id, centre_id, admission_id, user_id, log_notes, is_crime, log_number, partner_type, date)
                VALUES
                    (:patient_id, :centre_id, :admission_id, :user_id, :log_notes, :is_crime, :log_number, :partner_type, :date)
            ");
            $stmt->execute([
                ':patient_id' => $patient_id,
                ':centre_id' => $centre_id,
                ':admission_id' => $admission_id ?: null,
                ':user_id' => $user_id,
                ':log_notes' => offline_action_value($payload, 'log_notes'),
                ':is_crime' => offline_action_value($payload, 'is_crime', 'No'),
                ':log_number' => offline_action_value($payload, 'log_number'),
                ':partner_type' => (int)offline_action_value($payload, 'partner_type', 0),
                ':date' => str_replace('T', ' ', offline_action_value($payload, 'date', date('Y-m-d H:i:s'))),
            ]);
            $id = (int)$pdo->lastInsertId();
            break;

        case 'patient_task':
            $stmt = $pdo->prepare("
                INSERT INTO rescue_tasks_patients (task_id, patient_id, status, set_date_time, set_by)
                VALUES (:task_id, :patient_id, 'Waiting', :date, :user_id)
            ");
            $stmt->execute([
                ':task_id' => (int)offline_action_value($payload, 'task_id', 0),
                ':patient_id' => $patient_id,
                ':date' => str_replace('T', ' ', offline_action_value($payload, 'date', date('Y-m-d H:i:s'))),
                ':user_id' => $user_id,
            ]);
            $id = (int)$pdo->lastInsertId();
            break;

        case 'patient_feeding':
            $centre_diet_item_id = (int)offline_action_value($payload, 'centre_diet_item_id', 0);
            $is_skipped = offline_action_value($payload, 'feed_skipped', '0') === '1';
            $is_refused = offline_action_value($payload, 'feed_refused', '0') === '1';
            $diet_item_id = null;
            $feed_type = null;
            $unit = null;

            if ($centre_diet_item_id > 0) {
                $stmt = $pdo->prepare("
                    SELECT di.diet_item_id, di.type, di.default_unit
                    FROM rescue_centre_diet_items cdi
                    JOIN rescue_diet_items di ON di.diet_item_id = cdi.diet_item_id
                    WHERE cdi.centre_diet_item_id = :centre_diet_item_id
                      AND cdi.centre_id = :centre_id
                      AND cdi.is_enabled = 1
                    LIMIT 1
                ");
                $stmt->execute([
                    ':centre_diet_item_id' => $centre_diet_item_id,
                    ':centre_id' => $centre_id,
                ]);
                $diet = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($diet) {
                    $diet_item_id = (int)$diet['diet_item_id'];
                    $feed_type = (string)$diet['type'];
                    $unit = (string)$diet['default_unit'];
                }
            }

            if (!$is_skipped && (!$diet_item_id || !$feed_type || !$unit)) {
                offline_action_json(['success' => false, 'message' => 'Diet item is required.'], 422);
            }

            $offered = (float)offline_action_value($payload, 'offered_value', 0);
            $remaining = (float)offline_action_value($payload, 'remaining_value', 0);
            if ($remaining > $offered) $remaining = $offered;

            $status = 'normal';
            $consumed = $offered - $remaining;
            if ($is_skipped) {
                $status = 'skipped';
                $offered = 0;
                $remaining = 0;
                $consumed = 0;
                $unit = null;
            } elseif ($is_refused) {
                $status = 'refused';
                $remaining = $offered;
                $consumed = 0;
            }

            $stmt = $pdo->prepare("
                INSERT INTO rescue_feeding_events
                    (patient_id, admission_id, centre_id, diet_item_id, feed_at, feed_type, status,
                     offered_value, offered_unit, is_estimated, remaining_value, remaining_percent,
                     consumed_value, consumed_unit, notes, created_by, created_at)
                VALUES
                    (:patient_id, :admission_id, :centre_id, :diet_item_id, :feed_at, :feed_type, :status,
                     :offered_value, :offered_unit, :is_estimated, :remaining_value, NULL,
                     :consumed_value, :consumed_unit, :notes, :created_by, NOW())
            ");
            $stmt->execute([
                ':patient_id' => $patient_id,
                ':admission_id' => $admission_id ?: null,
                ':centre_id' => $centre_id,
                ':diet_item_id' => $diet_item_id,
                ':feed_at' => str_replace('T', ' ', offline_action_value($payload, 'feed_at', date('Y-m-d H:i:s'))),
                ':feed_type' => $feed_type,
                ':status' => $status,
                ':offered_value' => $is_skipped ? null : $offered,
                ':offered_unit' => $unit,
                ':is_estimated' => offline_action_value($payload, 'is_estimated', '0') === '1' ? 1 : 0,
                ':remaining_value' => $is_skipped ? null : $remaining,
                ':consumed_value' => $consumed,
                ':consumed_unit' => $unit,
                ':notes' => offline_action_value($payload, 'notes'),
                ':created_by' => $user_id,
            ]);
            $id = (int)$pdo->lastInsertId();
            break;

        default:
            offline_action_json(['success' => false, 'message' => 'Unsupported action type: ' . $entity_type], 422);
    }

    offline_action_json([
        'success' => true,
        'queue_id' => $queue_id,
        'entity_type' => $entity_type,
        'record_id' => $id ?? null,
        'message' => 'Patient action synced.',
    ]);
} catch (Throwable $e) {
    offline_action_json(['success' => false, 'message' => $e->getMessage()], 500);
}
