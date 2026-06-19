<?php
// controllers/admissions/group_submit.php
// Final submit endpoint for the separate group admission workflow.

ob_start();
header('Content-Type: application/json; charset=utf-8');

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (ob_get_length()) {
            ob_clean();
        }
        echo json_encode([
            'success' => false,
            'message' => 'Server fatal error in group_submit.php',
            'details' => $err['message'] . ' in ' . $err['file'] . ':' . $err['line']
        ]);
        exit;
    }
});

function respond(array $payload, int $httpCode = 200): void
{
    http_response_code($httpCode);
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($payload);
    exit;
}

session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../operations/transfers_log.php';

try {
    $pdo = new PDO(
        "mysql:host=" . db_host . ";dbname=" . db_name . ";charset=" . db_charset,
        db_user,
        db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
} catch (PDOException $e) {
    respond(['success' => false, 'message' => 'Database connection failed.'], 500);
}

$user_id = isset($_SESSION['account_id']) ? (int)$_SESSION['account_id'] : 0;
$centre_id = isset($_SESSION['centre_id']) ? (int)$_SESSION['centre_id'] : 0;

if ($centre_id <= 0 && $user_id > 0) {
    $stmt = $pdo->prepare("SELECT centre_id FROM accounts WHERE id = :uid LIMIT 1");
    $stmt->execute([':uid' => $user_id]);
    $centre_id = (int)$stmt->fetchColumn();
    if ($centre_id > 0) {
        $_SESSION['centre_id'] = $centre_id;
    }
}

if ($user_id <= 0 || $centre_id <= 0) {
    respond(['success' => false, 'message' => 'Cannot save: session expired or centre is missing.'], 403);
}

transfers_auto($pdo);

function post_scalar(string $key, $default = '')
{
    if (!isset($_POST[$key]) || is_array($_POST[$key])) {
        return $default;
    }
    return trim((string)$_POST[$key]);
}

function post_array(string $key): array
{
    return (isset($_POST[$key]) && is_array($_POST[$key])) ? $_POST[$key] : [];
}

function null_if_blank($value)
{
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function parse_datetime(?string $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    try {
        return (new DateTime($value))->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return null;
    }
}

function score_age(?string $age): ?int
{
    $map = [
        'Newborn'              => 3,
        'Dependent Juvenile'   => 2,
        'Independent Juvenile' => 1,
        'Hatchling'            => 3,
        'Fledgling'            => 2,
        'Adult'                => 0,
    ];
    return ($age !== null && isset($map[$age])) ? $map[$age] : null;
}

function score_severity(?string $text): ?int
{
    $map = [
        'Apparently Healthy' => 0,
        'Mildly unwell'      => 0,
        'Obvious Injuries'   => 1,
        'Severe Injuries'    => 2,
        'Near Death'         => 3,
    ];
    return ($text !== null && isset($map[$text])) ? $map[$text] : null;
}

function score_body_condition(?string $text): ?int
{
    $map = [
        'BCS 1 Skeletal'             => 3,
        'BCS 2 Underweight'          => 2,
        'BCS 3 Slightly Underweight' => 1,
        'BCS 4 Healthy'              => 0,
        'BCS 5 Overweight'           => 0,
    ];
    return ($text !== null && isset($map[$text])) ? $map[$text] : null;
}

function posted_score_or_fallback(array $scores, int $index, ?int $fallback): ?int
{
    if (!array_key_exists($index, $scores)) {
        return $fallback;
    }

    $raw = trim((string)$scores[$index]);
    if ($raw === '') {
        return $fallback;
    }

    return is_numeric($raw) ? (int)$raw : $fallback;
}

$animal_species = post_scalar('animal_species');
$animal_type = post_scalar('animal_type');
$animal_order = post_scalar('animal_order');
$quantity = max(0, (int)post_scalar('quantity', 0));

$names = post_array('animal_name');
$sexes = post_array('sex');
$ringed_values = post_array('ringed');
$ring_numbers = post_array('ring_number');
$microchipped_values = post_array('microchipped');
$microchip_numbers = post_array('microchip_number');
$weights = post_array('weight');
$weight_units = post_array('weight_unit');
$measurements = post_array('measurement');
$measurement_units = post_array('measurement_unit');
$current_location_ids = post_array('current_location_id');
$ages = post_array('age_on_admission');
$age_scores = post_array('age_score');
$dehydrated_values = post_array('dehydrated');
$starved_values = post_array('starved');
$severity_values = post_array('ss_text');
$severity_scores = post_array('severity_score');
$body_condition_values = post_array('bcs_text');
$body_condition_scores = post_array('bc_score');
$presenting_values = post_array('presenting_complaint');
$hpc_values = post_array('hpc');
$on_exam_values = post_array('on_examination');

if ($animal_species === '') {
    respond(['success' => false, 'message' => 'Select an animal species from the search results before saving.'], 422);
}

$speciesStmt = $pdo->prepare("
    SELECT
        s.species_name,
        t.type_name,
        t.animal_order
    FROM rescue_animal_species s
    LEFT JOIN rescue_animal_types t
      ON t.type_name = s.animal_type
    WHERE LOWER(TRIM(s.species_name)) = LOWER(TRIM(:species_name))
    LIMIT 1
");
$speciesStmt->execute([':species_name' => $animal_species]);
$selectedSpecies = $speciesStmt->fetch(PDO::FETCH_ASSOC);

if (!$selectedSpecies) {
    respond([
        'success' => false,
        'message' => 'The animal species must be selected from the search results before saving.'
    ], 422);
}

$animal_species = trim((string)$selectedSpecies['species_name']);
$animal_type = trim((string)($selectedSpecies['type_name'] ?? ''));
$animal_order = trim((string)($selectedSpecies['animal_order'] ?? ''));

if ($quantity < 1 || $quantity > 200) {
    respond(['success' => false, 'message' => 'Quantity must be between 1 and 200.'], 400);
}

if (count($names) < $quantity || count($sexes) < $quantity) {
    respond(['success' => false, 'message' => 'Individual patient rows are incomplete.'], 400);
}

for ($i = 0; $i < $quantity; $i++) {
    if (trim((string)($sexes[$i] ?? '')) === '') {
        respond(['success' => false, 'message' => 'Sex is required for every animal row.'], 400);
    }
    $raw_location_id = trim((string)($current_location_ids[$i] ?? ''));
    if ($raw_location_id === '') {
        respond(['success' => false, 'message' => 'Current location is required for every animal row.'], 400);
    }
}

$admission_date = parse_datetime(post_scalar('admission_date'));
if ($admission_date === null) {
    respond(['success' => false, 'message' => 'Admission date/time is required.'], 400);
}

$locationLookupStmt = $pdo->prepare("
    SELECT location_name
    FROM rescue_locations
    WHERE location_id = :lid
      AND centre_id = :cid
      AND (deleted IS NULL OR deleted = 0)
    LIMIT 1
");

$collection_location = post_scalar('collection_location');
if ($collection_location === '') {
    respond(['success' => false, 'message' => 'Collection location is required.'], 400);
}

$disposition = post_scalar('disposition', 'Held in captivity');
$disposition = 'Held in captivity';
$status = ($disposition === 'Held in captivity') ? 'Active' : 'Closed';
$patient_status = 'Captive';
$patient_state = 'Admitted';
$now = date('Y-m-d H:i:s');

$shared = [
    'time_to_admission'          => null_if_blank(post_scalar('time_to_admission')),
    'collection_location'        => $collection_location,
    'finder_id'                  => max(0, (int)post_scalar('finder_id', 0)),
    'finder_name'                => null_if_blank(post_scalar('finder_name')),
    'finder_tel'                 => null_if_blank(post_scalar('finder_tel')),
    'consent_to_update'          => post_scalar('consent_to_update', '0'),
    'passphrase'                 => null_if_blank(post_scalar('passphrase')),
    'location_lat'               => null_if_blank(post_scalar('location_lat')),
    'location_long'              => null_if_blank(post_scalar('location_long')),
    'incident_location_postcode' => null_if_blank(post_scalar('incident_location_postcode')),
    'w_temp'                     => null_if_blank(post_scalar('w_temp')),
    'w_wind'                     => null_if_blank(post_scalar('w_wind')),
    'w_humidity'                 => null_if_blank(post_scalar('w_humidity')),
    'w_freetext'                 => null_if_blank(post_scalar('w_freetext')),
    'w_rainfall'                 => null_if_blank(post_scalar('w_rainfall')),
];

$signature = post_scalar('signature_data');
$refused = post_scalar('no_signature') === '1' ? 1 : 0;

if ($signature === '' && $refused !== 1) {
    respond(['success' => false, 'message' => 'A signature or refusal must be recorded.'], 400);
}

$incomplete_json = json_encode([
    'marked_complete' => [
        2 => 1,
        3 => 1,
        4 => 1,
        5 => 1,
        6 => 1,
    ],
    'missing_by_section' => [
        2 => [],
        3 => [],
        4 => [],
        5 => [],
        6 => [],
    ],
    'group_admission' => 1,
], JSON_UNESCAPED_UNICODE);

try {
    $pdo->beginTransaction();

    $incident_id = null;
    if ($quantity > 1) {
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
            ':incident_date' => $admission_date,
            ':line_1'       => $collection_location,
            ':line_2'       => null,
            ':city'         => null,
            ':postcode'     => $shared['incident_location_postcode'],
            ':centre_ref'   => null,
            ':total'        => $quantity,
            ':doa'          => 0,
            ':mass_cas'     => 1,
            ':centre_id'    => $centre_id,
            ':user_id'      => $user_id,
        ]);
        $incident_id = (int)$pdo->lastInsertId();
    }

    $patientStmt = $pdo->prepare("
        INSERT INTO rescue_patients (
            name,
            ringed,
            ring_number,
            microchipped,
            microchip_number,
            animal_type,
            animal_order,
            animal_species,
            sex,
            status,
            staff_wp_id,
            centre_id,
            date_added,
            state,
            transfer_id,
            created_by,
            approx_dob,
            incomplete_fields
        ) VALUES (
            :name,
            :ringed,
            :ring_number,
            :microchipped,
            :microchip_number,
            :animal_type,
            :animal_order,
            :animal_species,
            :sex,
            :status,
            :staff_wp_id,
            :centre_id,
            :date_added,
            :state,
            0,
            :created_by,
            NULL,
            :incomplete_fields
        )
    ");

    $admissionStmt = $pdo->prepare("
        INSERT INTO rescue_admissions (
            patient_id,
            admission_date,
            age_on_admission,
            presenting_complaint,
            dehydrated,
            starved,
            status,
            collection_location,
            finder_id,
            finder_name,
            disposition,
            weight,
            weight_unit,
            measurement,
            measurement_unit,
            centre_id,
            staff_wp_id,
            time_to_admission,
            date_created,
            current_location,
            survived,
            w_temp,
            w_wind,
            w_humidity,
            w_freetext,
            severity_score,
            finder_tel,
            consent_to_update,
            hpc,
            on_examination,
            ss_text,
            bc_score,
            bcs_text,
            age_score,
            location_lat,
            location_long,
            passphrase,
            w_rainfall,
            incomplete_fields,
            current_location_id
        ) VALUES (
            :patient_id,
            :admission_date,
            :age_on_admission,
            :presenting_complaint,
            :dehydrated,
            :starved,
            :status,
            :collection_location,
            :finder_id,
            :finder_name,
            :disposition,
            :weight,
            :weight_unit,
            :measurement,
            :measurement_unit,
            :centre_id,
            :staff_wp_id,
            :time_to_admission,
            :date_created,
            :current_location,
            NULL,
            :w_temp,
            :w_wind,
            :w_humidity,
            :w_freetext,
            :severity_score,
            :finder_tel,
            :consent_to_update,
            :hpc,
            :on_examination,
            :ss_text,
            :bc_score,
            :bcs_text,
            :age_score,
            :location_lat,
            :location_long,
            :passphrase,
            :w_rainfall,
            :incomplete_fields,
            :current_location_id
        )
    ");

    $relStmt = $pdo->prepare("
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

    $signatureStmt = $pdo->prepare("
        INSERT INTO rescue_signatures
            (centre_id, admission_id, patient_id, user_id, signature_data, refused, signed_at)
        VALUES
            (:centre_id, :admission_id, :patient_id, :user_id, :signature_data, :refused, NOW())
    ");

    $created = [];
    for ($i = 0; $i < $quantity; $i++) {
        $name = trim((string)($names[$i] ?? ''));
        if ($name === '') {
            $name = 'Animal ' . ($i + 1);
        }

        $patientMissing = [];
        if ($animal_type === '') $patientMissing[] = 'animal_type';
        if ($animal_order === '') $patientMissing[] = 'animal_order';
        if ($animal_species === '') $patientMissing[] = 'animal_species';
        if (trim((string)($sexes[$i] ?? '')) === '') $patientMissing[] = 'sex';

        $ringed = trim((string)($ringed_values[$i] ?? 'No')) === 'Yes' ? 'Yes' : 'No';
        $ring_number = trim((string)($ring_numbers[$i] ?? ''));
        $microchipped = trim((string)($microchipped_values[$i] ?? 'No')) === 'Yes' ? 'Yes' : 'No';
        $microchip_number = trim((string)($microchip_numbers[$i] ?? ''));

        if ($ringed === 'Yes' && $ring_number === '') $patientMissing[] = 'ring_number';
        if ($microchipped === 'Yes' && $microchip_number === '') $patientMissing[] = 'microchip_number';

        $patientStmt->execute([
            ':name'              => $name,
            ':ringed'            => $ringed,
            ':ring_number'       => $ring_number,
            ':microchipped'      => $microchipped,
            ':microchip_number'  => $microchip_number,
            ':animal_type'       => $animal_type,
            ':animal_order'      => $animal_order,
            ':animal_species'    => $animal_species,
            ':sex'               => trim((string)$sexes[$i]),
            ':status'            => $patient_status,
            ':staff_wp_id'       => $user_id,
            ':centre_id'         => $centre_id,
            ':date_added'        => $now,
            ':state'             => $patient_state,
            ':created_by'        => $user_id,
            ':incomplete_fields' => json_encode($patientMissing, JSON_UNESCAPED_UNICODE),
        ]);
        $patient_id = (int)$pdo->lastInsertId();

        $weight = trim((string)($weights[$i] ?? '0'));
        $measurement = trim((string)($measurements[$i] ?? '0'));
        $weight_unit = trim((string)($weight_units[$i] ?? 'g'));
        $measurement_unit = trim((string)($measurement_units[$i] ?? 'mm'));

        if (!in_array($weight_unit, ['g', 'kg'], true)) {
            $weight_unit = 'g';
        }
        if (!in_array($measurement_unit, ['mm', 'cm'], true)) {
            $measurement_unit = 'mm';
        }

        $raw_location_id = trim((string)($current_location_ids[$i] ?? ''));
        $current_location_id = 0;
        $current_location = 'None';

        if ($raw_location_id !== 'none') {
            $current_location_id = (int)$raw_location_id;
            $locationLookupStmt->execute([
                ':lid' => $current_location_id,
                ':cid' => $centre_id,
            ]);
            $current_location = (string)$locationLookupStmt->fetchColumn();
            if ($current_location === '') {
                throw new RuntimeException('Selected current location is not valid for this centre.');
            }
        }

        $age_on_admission = null_if_blank($ages[$i] ?? '');
        $dehydrated = trim((string)($dehydrated_values[$i] ?? 'No')) === 'Yes' ? 'Yes' : 'No';
        $starved = trim((string)($starved_values[$i] ?? 'No')) === 'Yes' ? 'Yes' : 'No';
        $ss_text = null_if_blank($severity_values[$i] ?? '');
        $bcs_text = null_if_blank($body_condition_values[$i] ?? '');
        $presenting_complaint = null_if_blank($presenting_values[$i] ?? '');
        $hpc = null_if_blank($hpc_values[$i] ?? '');
        $on_examination = null_if_blank($on_exam_values[$i] ?? '');
        $age_score = posted_score_or_fallback($age_scores, $i, score_age($age_on_admission));
        $severity_score = posted_score_or_fallback($severity_scores, $i, score_severity($ss_text));
        $bc_score = posted_score_or_fallback($body_condition_scores, $i, score_body_condition($bcs_text));

        $admissionStmt->execute([
            ':patient_id'            => $patient_id,
            ':admission_date'        => $admission_date,
            ':age_on_admission'      => $age_on_admission,
            ':presenting_complaint'  => $presenting_complaint,
            ':dehydrated'            => $dehydrated,
            ':starved'               => $starved,
            ':status'                => $status,
            ':collection_location'   => $collection_location,
            ':finder_id'             => $shared['finder_id'],
            ':finder_name'           => $shared['finder_name'],
            ':disposition'           => $disposition,
            ':weight'                => $weight === '' ? '0' : $weight,
            ':weight_unit'           => $weight_unit,
            ':measurement'           => $measurement === '' ? '0' : $measurement,
            ':measurement_unit'      => $measurement_unit,
            ':centre_id'             => $centre_id,
            ':staff_wp_id'           => $user_id,
            ':time_to_admission'     => $shared['time_to_admission'],
            ':date_created'          => $now,
            ':current_location'      => $current_location,
            ':w_temp'                => $shared['w_temp'],
            ':w_wind'                => $shared['w_wind'],
            ':w_humidity'            => $shared['w_humidity'],
            ':w_freetext'            => $shared['w_freetext'],
            ':severity_score'        => $severity_score,
            ':finder_tel'            => $shared['finder_tel'],
            ':consent_to_update'     => $shared['consent_to_update'],
            ':hpc'                   => $hpc,
            ':on_examination'        => $on_examination,
            ':ss_text'               => $ss_text,
            ':bc_score'              => $bc_score,
            ':bcs_text'              => $bcs_text,
            ':age_score'             => $age_score,
            ':location_lat'          => $shared['location_lat'],
            ':location_long'         => $shared['location_long'],
            ':passphrase'            => $shared['passphrase'],
            ':w_rainfall'            => $shared['w_rainfall'],
            ':incomplete_fields'     => $incomplete_json,
            ':current_location_id'   => $current_location_id,
        ]);
        $admission_id = (int)$pdo->lastInsertId();

        if ($incident_id !== null) {
            $relStmt->execute([
                ':incident_id'  => $incident_id,
                ':centre_id'    => $centre_id,
                ':admission_id' => $admission_id,
                ':finder_id'    => $shared['finder_id'] > 0 ? $shared['finder_id'] : null,
                ':user_id'      => $user_id,
            ]);
        }

        $signatureStmt->execute([
            ':centre_id'      => $centre_id,
            ':admission_id'   => $admission_id,
            ':patient_id'     => $patient_id,
            ':user_id'        => $user_id,
            ':signature_data' => $refused === 1 ? '' : $signature,
            ':refused'        => $refused,
        ]);

        transfers_log($pdo, 'admission', [
            'patient_id'     => $patient_id,
            'admission_id'   => $admission_id,
            'to_location_id' => $current_location_id > 0 ? $current_location_id : null,
        ]);

        $created[] = [
            'patient_id' => $patient_id,
            'admission_id' => $admission_id,
        ];
    }

    $pdo->commit();

    respond([
        'success' => true,
        'created' => count($created),
        'incident_id' => $incident_id,
        'records' => $created,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond([
        'success' => false,
        'message' => 'Group admission failed; no records were created.',
        'details' => $e->getMessage(),
    ], 500);
}
