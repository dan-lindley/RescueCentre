<?php
// controllers/admissions/save_section.php
// AJAX endpoint to save individual sections of the admission wizard

// ============================================================
// JSON-SAFE OUTPUT GUARD
// Prevents stray output (warnings/echo/whitespace/BOM) from
// breaking fetch(...).json() and causing "Network/JS error".
// ============================================================
ob_start();

// ---- DEBUG (safe for JSON endpoints) ----
//ini_set('display_errors', 0);
//ini_set('display_startup_errors', 0);
//ini_set('log_errors', 1);
//error_reporting(E_ALL);
// ----------------------------------------

// Always return JSON
header('Content-Type: application/json; charset=utf-8');

// Fatal error handler (so fetch still gets JSON, not HTML)
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (ob_get_length()) { ob_clean(); }
        echo json_encode([
            'success' => false,
            'message' => 'Server fatal error in save_section.php',
            'details' => $err['message'] . ' in ' . $err['file'] . ':' . $err['line']
        ]);
        exit;
    }
});

// Central JSON responder (cleans any stray output first)
function respond(array $payload, int $httpCode = 200): void {
    http_response_code($httpCode);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode($payload);
    exit;
}

// ------------------------------------------------------------
// MINIMAL BOOTSTRAP FOR THIS ENDPOINT
// ------------------------------------------------------------
session_start();
require_once __DIR__ . '/../../operations/permissions.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../operations/transfers_log.php';
include_once __DIR__ . '/../operations/audit.php';
transfers_auto($pdo);
audit_auto($pdo);


// Build our own PDO here (same pattern you used in search_species.php)
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
    respond([
        'success' => false,
        'message' => 'Database connection failed.'
        // 'details' => $e->getMessage() // keep commented in production
    ], 500);
}

// Logged-in user ID from session
$user_id = $_SESSION['account_id'] ?? null;

// Try session-stored centre_id first
$centre_id = $_SESSION['centre_id'] ?? null;

// If centre_id missing but user_id available → look up from accounts table
if (!$centre_id && $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT centre_id 
            FROM accounts 
            WHERE id = :uid 
            LIMIT 1
        ");
        $stmt->execute([':uid' => $user_id]);
        $centre_id = $stmt->fetchColumn();

        // Store it in session for future AJAX calls
        if ($centre_id) {
            $_SESSION['centre_id'] = $centre_id;
        }
    } catch (Exception $e) {
        // fail silently; centre_id may still be null
    }
}

// FINAL PROTECTION: centre_id may not be null in rescue_patients
if (!$centre_id) {
    respond([
        'success' => false,
        'message' => 'Cannot save: centre_id is missing (session expired or invalid account).'
    ], 403);
}
// ------------------------------------------------------------
// REQUIRED: populate $GLOBALS for permissions.php can()
// ------------------------------------------------------------
$role_id = $_SESSION['role'] ?? $_SESSION['role_id'] ?? $GLOBALS['role'] ?? null;

$GLOBALS['user_id']   = $user_id;
$GLOBALS['centre_id'] = $centre_id;
$GLOBALS['role']      = $role_id;

if (!$GLOBALS['user_id'] || !$GLOBALS['role']) {
    respond([
        'success' => false,
        'message' => 'Cannot save: user or role missing (session expired).'
    ], 403);
}

// ------------------------------------------------------------

function post($key, $default = null) {
    if (!isset($_POST[$key])) return $default;
    if (is_array($_POST[$key])) return $default; // safety
    return trim((string)$_POST[$key]);
}

$sid = isset($_POST['sid']) ? (int)$_POST['sid'] : 0;

if ($sid === 0) {
    respond([
        'success' => false,
        'message' => 'No section specified.'
    ], 400);
}

try {

    // ========================================================
    // STAGE 1 – rescue_patients
    // ========================================================
    if ($sid === 1) {

        $patient_id        = post('patient_id');

        $name              = post('name', 'Not completed');
        $ringed            = post('ringed', 'No');
        $ring_number       = post('ring_number');
        $microchipped      = post('microchipped', 'No');
        $microchip_number  = post('microchip_number');

        $animal_species    = post('animal_species');
        $animal_type       = post('animal_type');
        $animal_order      = post('animal_order');

        $sex               = post('sex');
        $approx_dob        = post('approx_dob');

        if (empty($animal_species)) {
            respond([
                'success' => false,
                'message' => 'Select an animal species from the search results before saving.'
            ], 422);
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

        // Store the canonical values from the selected species record.
        $animal_species = trim((string)$selectedSpecies['species_name']);
        $animal_type = trim((string)($selectedSpecies['type_name'] ?? ''));
        $animal_order = trim((string)($selectedSpecies['animal_order'] ?? ''));

        // Defaults (mirroring your original file)
        $status            = 'Captive';
        $state             = 'To Admit';
        $transfer_id       = 0;
        $staff_wp_id       = $user_id;      // may be null if no session
        $created_by        = $user_id;
        $date_added        = date('Y-m-d H:i:s');

        // ---------------- METADATA VALIDATION ----------------
        $missing = [];

        if (empty($animal_type))    $missing[] = 'animal_type';
        if (empty($animal_order))   $missing[] = 'animal_order';
        if (empty($animal_species)) $missing[] = 'animal_species';
        if (empty($sex))            $missing[] = 'sex';

        if ($ringed === 'Yes' && empty($ring_number)) {
            $missing[] = 'ring_number';
        }
        if ($microchipped === 'Yes' && empty($microchip_number)) {
            $missing[] = 'microchip_number';
        }

        $incomplete_json = json_encode($missing);

        if (empty($patient_id)) {
            $force_new = post('force_new', '0') === '1';
            $matchName = trim((string)$name);
            $matchSpecies = trim((string)$animal_species);

            if (!$force_new && $matchName !== '' && strcasecmp($matchName, 'Not completed') !== 0 && $matchSpecies !== '') {
                $dupStmt = $pdo->prepare("
                    SELECT
                        p.patient_id,
                        p.name,
                        p.animal_species,
                        p.date_added,
                        (
                            SELECT MAX(a.admission_id)
                            FROM rescue_admissions a
                            WHERE a.patient_id = p.patient_id
                              AND a.centre_id = p.centre_id
                        ) AS admission_id
                    FROM rescue_patients p
                    WHERE p.centre_id = :centre_id
                      AND p.state = 'To Admit'
                      AND LOWER(TRIM(p.name)) = LOWER(TRIM(:name))
                      AND LOWER(TRIM(p.animal_species)) = LOWER(TRIM(:animal_species))
                    ORDER BY p.date_added DESC, p.patient_id DESC
                    LIMIT 1
                ");
                $dupStmt->execute([
                    ':centre_id' => $centre_id,
                    ':name' => $matchName,
                    ':animal_species' => $matchSpecies
                ]);
                $duplicate = $dupStmt->fetch(PDO::FETCH_ASSOC);

                if ($duplicate) {
                    respond([
                        'success' => false,
                        'duplicate_partial' => true,
                        'message' => 'A partial admission already exists for this name and species.',
                        'duplicate' => [
                            'patient_id' => (int)$duplicate['patient_id'],
                            'admission_id' => !empty($duplicate['admission_id']) ? (int)$duplicate['admission_id'] : null,
                            'name' => (string)$duplicate['name'],
                            'animal_species' => (string)$duplicate['animal_species'],
                            'date_added' => (string)($duplicate['date_added'] ?? '')
                        ]
                    ], 409);
                }
            }

            // INSERT
            $sql = "
                INSERT INTO rescue_patients (
                    name, ringed, ring_number,
                    microchipped, microchip_number,
                    animal_type, animal_order, animal_species,
                    sex, status, staff_wp_id, centre_id,
                    date_added, state, transfer_id,
                    created_by, approx_dob, incomplete_fields
                ) VALUES (
                    :name, :ringed, :ring_number,
                    :microchipped, :microchip_number,
                    :animal_type, :animal_order, :animal_species,
                    :sex, :status, :staff_wp_id, :centre_id,
                    :date_added, :state, :transfer_id,
                    :created_by, :approx_dob, :incomplete_fields
                )
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name'             => $name,
                ':ringed'           => $ringed,
                ':ring_number'      => $ring_number,
                ':microchipped'     => $microchipped,
                ':microchip_number' => $microchip_number,
                ':animal_type'      => $animal_type,
                ':animal_order'     => $animal_order,
                ':animal_species'   => $animal_species,
                ':sex'              => $sex,
                ':status'           => $status,
                ':staff_wp_id'      => $staff_wp_id,
                ':centre_id'        => $centre_id,
                ':date_added'       => $date_added,
                ':state'            => $state,
                ':transfer_id'      => $transfer_id,
                ':created_by'       => $created_by,
                ':approx_dob'       => $approx_dob,
                ':incomplete_fields'=> $incomplete_json
            ]);

            $patient_id = $pdo->lastInsertId();

        } else {
            // UPDATE
            $sql = "
                UPDATE rescue_patients
                   SET name              = :name,
                       ringed            = :ringed,
                       ring_number       = :ring_number,
                       microchipped      = :microchipped,
                       microchip_number  = :microchip_number,
                       animal_type       = :animal_type,
                       animal_order      = :animal_order,
                       animal_species    = :animal_species,
                       sex               = :sex,
                       approx_dob        = :approx_dob,
                       incomplete_fields = :incomplete_fields
                 WHERE patient_id        = :patient_id
                 AND centre_id        = :centre_id
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name'             => $name,
                ':ringed'           => $ringed,
                ':ring_number'      => $ring_number,
                ':microchipped'     => $microchipped,
                ':microchip_number' => $microchip_number,
                ':animal_type'      => $animal_type,
                ':animal_order'     => $animal_order,
                ':animal_species'   => $animal_species,
                ':sex'              => $sex,
                ':approx_dob'       => $approx_dob,
                ':incomplete_fields'=> $incomplete_json,
                ':patient_id'       => $patient_id,
                ':centre_id'        => $centre_id
            ]);
        }

        respond([
            'success'    => true,
            'patient_id' => $patient_id,
            'missing'    => $missing
        ]);
    }

// ========================================================
// STAGE 2 – rescue_admissions
// ========================================================
if ($sid === 2) {

    // ---------------- BASIC IDs ----------------
    $patient_id   = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $admission_id = isset($_POST['admission_id']) ? (int)$_POST['admission_id'] : 0;

    if ($patient_id <= 0) {
        respond(['success' => false, 'message' => 'Missing patient_id.'], 400);
    }

    // ---------------- FIELDS ----------------
    $admission_date_raw = isset($_POST['admission_date'])
        ? post('admission_date', '')
        : (string)($_POST['_existing_admission_date'] ?? '');

$current_location_id = isset($_POST['current_location_id'])
    ? (int)post('current_location_id', 0)
    : (int)($_POST['_existing_current_location_id'] ?? 0);

// Resolve the name from DB (server-truth)
$current_location = '';
if ($current_location_id > 0) {
    $stmt = $pdo->prepare("
        SELECT location_name
        FROM rescue_locations
        WHERE location_id = :lid
          AND centre_id = :cid
          AND (deleted IS NULL OR deleted = 0)
        LIMIT 1
    ");
    $stmt->execute([
        ':lid' => $current_location_id,
        ':cid' => $centre_id
    ]);
    $current_location = (string)($stmt->fetchColumn() ?: '');
}


    $disposition = isset($_POST['disposition'])
        ? post('disposition', '')
        : (string)($_POST['_existing_disposition'] ?? '');

    $time_to_admission = isset($_POST['time_to_admission'])
        ? post('time_to_admission', '')
        : (string)($_POST['_existing_time_to_admission'] ?? '');

    // ---------------- STATUS AUTO-LOGIC ----------------
    if ($disposition === 'Held in captivity') {
        $status = 'Active';
    } elseif ($disposition !== '') {
        $status = 'Closed';
    } else {
        $status = '';
    }

    // ---------------- DATETIME CONVERSION ----------------
    $admission_date = null;
    if ($admission_date_raw !== '') {
        try {
            $dt = new DateTime($admission_date_raw);
            $admission_date = $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $admission_date = null;
        }
    }

    // ---------------- REQUIRED FIELD CHECK ----------------
    $missing_required = [];
    if ($admission_date === null) $missing_required[] = 'admission_date';
    if ($current_location_id <= 0 || $current_location === '') $missing_required[] = 'current_location';
    if ($disposition === '')      $missing_required[] = 'disposition';

    // ---------------- MARK COMPLETE FLAG ----------------
    $markComplete = isset($_POST['mark_complete']) && $_POST['mark_complete'] === '1';

    if ($markComplete && !empty($missing_required)) {
        respond([
            'success' => false,
            'message' => 'Please complete required fields first.',
            'missing' => $missing_required
        ], 400);
    }

    // ---------------- INCOMPLETE JSON (MERGE-SAFE) ----------------

// Load existing incomplete_fields from DB so we don't wipe other sections
$existingMeta = [];
if ($admission_id > 0) {
    $stmt = $pdo->prepare("
        SELECT incomplete_fields
        FROM rescue_admissions
        WHERE admission_id = :aid
          AND patient_id   = :pid
          AND centre_id    = :cid
        LIMIT 1
    ");
    $stmt->execute([
        ':aid' => $admission_id,
        ':pid' => $patient_id,
        ':cid' => $centre_id
    ]);
    $raw = (string)$stmt->fetchColumn();
    $tmp = $raw !== '' ? json_decode($raw, true) : null;
    if (is_array($tmp)) $existingMeta = $tmp;
}

// Ensure shape
if (!isset($existingMeta['marked_complete']) || !is_array($existingMeta['marked_complete'])) {
    $existingMeta['marked_complete'] = [];
}
if (!isset($existingMeta['missing_by_section']) || !is_array($existingMeta['missing_by_section'])) {
    $existingMeta['missing_by_section'] = [];
}

if ($markComplete) {
    $existingMeta['marked_complete'][2] = 1;
    $existingMeta['missing_by_section'][2] = []; // marked complete = no blockers
} else {
    // Normal save: keep mark_complete as-is, update only this section's missing list
    $existingMeta['missing_by_section'][2] = $missing_required;
}

$incomplete_json = json_encode($existingMeta);


    // ========================================================
    // INSERT OR UPDATE LOGIC
    // ========================================================
    if ($admission_id > 0) {

        $sql = "
            UPDATE rescue_admissions
               SET admission_date    = :admission_date,
                   current_location  = :current_location,
                   disposition       = :disposition,
                   status            = :status,
                   current_location_id = :current_location_id,
                   time_to_admission = :time_to_admission,
                   incomplete_fields = :incomplete_fields
             WHERE admission_id     = :admission_id
               AND patient_id       = :patient_id
               AND centre_id        = :centre_id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':admission_date'    => $admission_date,
            ':current_location'  => $current_location,
            ':disposition'       => $disposition,
            ':status'            => $status,
            ':current_location_id' => $current_location_id,
            ':time_to_admission' => $time_to_admission !== '' ? $time_to_admission : null,
            ':incomplete_fields' => $incomplete_json,
            ':admission_id'      => $admission_id,
            ':patient_id'        => $patient_id,
            ':centre_id'         => $centre_id
        ]);

    } else {

        $now = date('Y-m-d H:i:s');

        // --- INSERT ONLY: add current_location_id ---

$sql = "
    INSERT INTO rescue_admissions (
        patient_id,
        admission_date,
        current_location_id,
        current_location,
        disposition,
        status,
        centre_id,
        staff_wp_id,
        time_to_admission,
        date_created,
        incomplete_fields
    ) VALUES (
        :patient_id,
        :admission_date,
        :current_location_id,
        :current_location,
        :disposition,
        :status,
        :centre_id,
        :staff_wp_id,
        :time_to_admission,
        :date_created,
        :incomplete_fields
    )
";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':patient_id'           => $patient_id,
    ':admission_date'       => $admission_date,
    ':current_location_id'  => $current_location_id,
    ':current_location'     => $current_location,
    ':disposition'          => $disposition,
    ':status'               => $status,
    ':centre_id'            => $centre_id,
    ':staff_wp_id'          => $user_id,
    ':time_to_admission'    => $time_to_admission !== '' ? $time_to_admission : null,
    ':date_created'         => $now,
    ':incomplete_fields'    => $incomplete_json,
]);


        $admission_id = (int)$pdo->lastInsertId();
    }

    respond([
        'success'      => true,
        'message'      => 'Section 2 saved',
        'admission_id' => $admission_id,
        'status'       => $status,
        'missing'      => $missing_required
    ]);
}

// ------------------------------------------------------------
// SECTION 3 server-side permission enforcement (HARDENED)
// ------------------------------------------------------------
if ((int)($_POST['sid'] ?? 0) === 3) {

    registerPermission('admission.collection.collection_location.edit', 'Edit collection location', 'field');
    registerPermission('admission.collection.location_lat.edit',        'Edit collection latitude', 'field');
    registerPermission('admission.collection.location_long.edit',       'Edit collection longitude', 'field');

    registerPermission('admission.finder.finder_name.edit',             'Edit finder name', 'field');
    registerPermission('admission.finder.finder_tel.edit',              'Edit finder telephone', 'field');
    registerPermission('admission.finder.consent_to_update.edit',       'Edit SMS consent', 'field');
    registerPermission('admission.finder.passphrase.edit',              'Edit passphrase', 'field');

    // Load existing admission (for carry-forward and tamper check)
    $pid_in = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $aid_in = isset($_POST['admission_id']) ? (int)$_POST['admission_id'] : 0;

    $existing = null;
    if ($pid_in > 0 && $aid_in > 0) {
        $stmt = $pdo->prepare("
            SELECT admission_id, patient_id,
                   collection_location, location_lat, location_long,
                   finder_id, finder_name, finder_tel,
                   consent_to_update, passphrase,
                   incomplete_fields
            FROM rescue_admissions
            WHERE admission_id = :aid
            LIMIT 1
        ");
        $stmt->execute([':aid' => $aid_in]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing || (int)$existing['patient_id'] !== $pid_in) {
            respond(['success' => false, 'message' => 'Admission/patient mismatch.'], 403);
        }
    }

    // Strip blocked fields
    if (!can('admission.collection.collection_location.edit')) unset($_POST['collection_location']);
    if (!can('admission.collection.location_lat.edit'))        unset($_POST['location_lat']);
    if (!can('admission.collection.location_long.edit'))       unset($_POST['location_long']);

    if (!can('admission.finder.finder_name.edit'))             unset($_POST['finder_name']);
    if (!can('admission.finder.finder_tel.edit'))              unset($_POST['finder_tel']);
    if (!can('admission.finder.consent_to_update.edit'))       unset($_POST['consent_to_update']);
    if (!can('admission.finder.passphrase.edit'))              unset($_POST['passphrase']);

    // Finder id should only change if finder name edit is allowed (ties selection to permission)
    if (!can('admission.finder.finder_name.edit'))             unset($_POST['finder_id']);

    // Carry-forward existing values so locked fields aren't blanked
    if ($existing) {
        if (!isset($_POST['collection_location'])) $_POST['_existing_collection_location'] = $existing['collection_location'];
        if (!isset($_POST['location_lat']))        $_POST['_existing_location_lat']        = $existing['location_lat'];
        if (!isset($_POST['location_long']))       $_POST['_existing_location_long']       = $existing['location_long'];

        if (!isset($_POST['finder_id']))           $_POST['_existing_finder_id']           = $existing['finder_id'];
        if (!isset($_POST['finder_name']))         $_POST['_existing_finder_name']         = $existing['finder_name'];
        if (!isset($_POST['finder_tel']))          $_POST['_existing_finder_tel']          = $existing['finder_tel'];

        if (!isset($_POST['consent_to_update']))   $_POST['_existing_consent_to_update']   = $existing['consent_to_update'];
        if (!isset($_POST['passphrase']))          $_POST['_existing_passphrase']          = $existing['passphrase'];

        // Carry forward existing incomplete_fields so marked_complete flags for other sections aren't lost
        if (!isset($_POST['_existing_incomplete_fields']))     $_POST['_existing_incomplete_fields'] = $existing['incomplete_fields'];
    }
}
// ========================================================
// STAGE 3 – Collection information (rescue_admissions)
// ========================================================
if ($sid === 3) {

    // ---------------- BASIC IDs ----------------
    $patient_id   = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $admission_id = isset($_POST['admission_id']) ? (int)$_POST['admission_id'] : 0;

    if ($patient_id <= 0) {
        respond(['success' => false, 'message' => 'Missing patient_id.'], 400);
    }

    // ---------------- MARK COMPLETE FLAG ----------------
    $markComplete = isset($_POST['mark_complete']) && $_POST['mark_complete'] === '1';

    // ---------------- EFFECTIVE VALUES (carry-forward safe) ----------------
    $collection_location = isset($_POST['collection_location'])
        ? post('collection_location', '')
        : (string)($_POST['_existing_collection_location'] ?? '');

    $location_lat = isset($_POST['location_lat'])
        ? post('location_lat', '')
        : (string)($_POST['_existing_location_lat'] ?? '');

    $location_long = isset($_POST['location_long'])
        ? post('location_long', '')
        : (string)($_POST['_existing_location_long'] ?? '');

    $finder_id = isset($_POST['finder_id'])
        ? (int)$_POST['finder_id']
        : (int)($_POST['_existing_finder_id'] ?? 0);

    $finder_name = isset($_POST['finder_name'])
        ? post('finder_name', '')
        : (string)($_POST['_existing_finder_name'] ?? '');

    $finder_tel = isset($_POST['finder_tel'])
        ? post('finder_tel', '')
        : (string)($_POST['_existing_finder_tel'] ?? '');

    $consent_to_update = isset($_POST['consent_to_update'])
        ? post('consent_to_update', '0')
        : (string)($_POST['_existing_consent_to_update'] ?? '0');

    $passphrase = isset($_POST['passphrase'])
        ? post('passphrase', '')
        : (string)($_POST['_existing_passphrase'] ?? '');

    // ---------------- REQUIRED FIELD CHECK (SECTION 2 PATTERN) ----------------
    $missing_required = [];
    if ($collection_location === '') $missing_required[] = 'collection_location';
    if ($location_lat === '')        $missing_required[] = 'location_lat';
    if ($location_long === '')       $missing_required[] = 'location_long';
    if ($passphrase === '')          $missing_required[] = 'passphrase';

    if ($markComplete && !empty($missing_required)) {
        respond([
            'success' => false,
            'message' => 'Please complete required fields first.',
            'missing' => $missing_required
        ], 400);
    }

// ---------------- INCOMPLETE JSON (MERGE-SAFE) ----------------

// Load existing incomplete_fields from DB so we don't wipe other sections
$existingMeta = [];
if ($admission_id > 0) {
    $stmt = $pdo->prepare("
        SELECT incomplete_fields
        FROM rescue_admissions
        WHERE admission_id = :aid
          AND patient_id   = :pid
          AND centre_id    = :cid
        LIMIT 1
    ");
    $stmt->execute([
        ':aid' => $admission_id,
        ':pid' => $patient_id,
        ':cid' => $centre_id
    ]);
    $raw = (string)$stmt->fetchColumn();
    $tmp = $raw !== '' ? json_decode($raw, true) : null;
    if (is_array($tmp)) $existingMeta = $tmp;
}

// Ensure shape
if (!isset($existingMeta['marked_complete']) || !is_array($existingMeta['marked_complete'])) {
    $existingMeta['marked_complete'] = [];
}
if (!isset($existingMeta['missing_by_section']) || !is_array($existingMeta['missing_by_section'])) {
    $existingMeta['missing_by_section'] = [];
}

if ($markComplete) {
    $existingMeta['marked_complete'][3] = 1;
    $existingMeta['missing_by_section'][3] = [];
} else {
    // Normal save: keep mark_complete as-is, update only this section's missing list
    $existingMeta['missing_by_section'][3] = $missing_required;
}

$incomplete_json = json_encode($existingMeta);


    // ---------------- ADMISSION LOOKUP ----------------
    if ($admission_id <= 0) {
        $check = $pdo->prepare("
            SELECT admission_id
            FROM rescue_admissions
            WHERE patient_id = :pid
              AND status = 'Active'
            ORDER BY admission_id DESC
            LIMIT 1
        ");
        $check->execute([':pid' => $patient_id]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $admission_id = (int)$existing['admission_id'];
        }
    }

    // ---------------- MINIMAL INSERT ----------------
    if ($admission_id <= 0) {

        $now = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("
            INSERT INTO rescue_admissions
                (patient_id, status, centre_id, staff_wp_id, date_created, incomplete_fields)
            VALUES
                (:patient_id, 'Active', :centre_id, :staff_wp_id, :date_created, :incomplete_fields)
        ");

        $stmt->execute([
            ':patient_id'        => $patient_id,
            ':centre_id'         => $centre_id,
            ':staff_wp_id'       => $user_id,
            ':date_created'      => $now,
            ':incomplete_fields' => $incomplete_json
        ]);

        $admission_id = (int)$pdo->lastInsertId();
    }

    // Once chosen, the finder passphrase is immutable. This prevents edits,
    // stale forms, or regenerated random options from replacing the stored value.
    if ($admission_id > 0) {
        $passStmt = $pdo->prepare("
            SELECT passphrase
            FROM rescue_admissions
            WHERE admission_id = :aid
              AND patient_id = :pid
              AND centre_id = :cid
            LIMIT 1
        ");
        $passStmt->execute([
            ':aid' => $admission_id,
            ':pid' => $patient_id,
            ':cid' => $centre_id
        ]);
        $storedPassphrase = trim((string)($passStmt->fetchColumn() ?: ''));
        if ($storedPassphrase !== '') {
            $passphrase = $storedPassphrase;
        }
    }

    // ---------------- UPDATE ----------------
    $stmt = $pdo->prepare("
        UPDATE rescue_admissions
           SET collection_location = :collection_location,
               location_lat        = :location_lat,
               location_long       = :location_long,
               finder_id           = :finder_id,
               finder_name         = :finder_name,
               finder_tel          = :finder_tel,
               consent_to_update   = :consent_to_update,
               passphrase          = :passphrase,
               incomplete_fields   = :incomplete_fields
         WHERE admission_id        = :admission_id
           AND patient_id          = :patient_id
           AND centre_id           = :centre_id
    ");

    $stmt->execute([
        ':collection_location' => $collection_location,
        ':location_lat'        => ($location_lat !== '' ? $location_lat : null),
        ':location_long'       => ($location_long !== '' ? $location_long : null),
        ':finder_id'           => ($finder_id > 0 ? $finder_id : 0),
        ':finder_name'         => $finder_name,
        ':finder_tel'          => $finder_tel,
        ':consent_to_update'   => $consent_to_update,
        ':passphrase'          => $passphrase,
        ':incomplete_fields'   => $incomplete_json,
        ':admission_id'        => $admission_id,
        ':patient_id'          => $patient_id,
        ':centre_id'           => $centre_id
    ]);

    respond([
        'success'      => true,
        'message'      => 'Section 3 saved',
        'admission_id' => $admission_id,
        'missing'      => $missing_required
    ]);
}
// ========================================================
// SECTION 33 – Add New Finder (used by Section 3 sub-form)
// ========================================================
if ($sid === 33) {

    if (!isset($pdo)) {
        respond(['success' => false, 'message' => 'PDO not available in Section 33'], 500);
    }

    // Permission enforcement (mirrors Section 3 registration)
    require_once __DIR__ . '/../../operations/permissions.php';
    if (!can('admission.finder.add')) {
        respond(['success' => false, 'message' => 'You do not have permission to add a new finder.'], 403);
    }

    $patient_id  = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $finder_name = post('finder_name', '');
    $finder_tel  = post('finder_tel', '');

    if ($patient_id <= 0) {
        respond(['success' => false, 'message' => 'Missing patient_id.'], 400);
    }

    if ($finder_name === '' || $finder_tel === '') {
        respond(['success' => false, 'message' => 'Finder name and telephone are required.'], 400);
    }

    // Optional: basic tel normalisation (keeps digits/+ only)
    $finder_tel_norm = preg_replace('/[^0-9+]/', '', $finder_tel);
    if ($finder_tel_norm !== '') {
        $finder_tel = $finder_tel_norm;
    }

    $now = date('Y-m-d H:i:s');

    // 1) Reuse existing finder by tel (centre-scoped, not deleted)
    $stmt = $pdo->prepare("
        SELECT finder_id
        FROM rescue_finders
        WHERE centre_id = :cid
          AND finder_tel = :tel
          AND deleted = 0
        ORDER BY finder_id DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':cid' => $centre_id,
        ':tel' => $finder_tel
    ]);

    $existing_id = (int)($stmt->fetchColumn() ?: 0);

    if ($existing_id > 0) {
        // Optional: keep name fresh if user entered a better one
        $upd = $pdo->prepare("
            UPDATE rescue_finders
               SET finder_name = :name,
                   updated_at  = :updated_at
             WHERE finder_id   = :fid
               AND centre_id   = :cid
               AND deleted     = 0
        ");
        $upd->execute([
            ':name'       => $finder_name,
            ':updated_at' => $now,
            ':fid'        => $existing_id,
            ':cid'        => $centre_id
        ]);

        respond([
            'success'   => true,
            'message'   => 'Finder already exists.',
            'finder_id' => $existing_id
        ]);
    }

    // 2) Insert new finder (schema-aligned)
    $stmt = $pdo->prepare("
        INSERT INTO rescue_finders
            (centre_id, finder_name, finder_tel, created_at, updated_at, deleted)
        VALUES
            (:centre_id, :finder_name, :finder_tel, :created_at, :updated_at, 0)
    ");
    $stmt->execute([
        ':centre_id'  => $centre_id,
        ':finder_name'=> $finder_name,
        ':finder_tel' => $finder_tel,
        ':created_at' => $now,
        ':updated_at' => $now
    ]);

    $new_id = (int)$pdo->lastInsertId();

    if ($new_id <= 0) {
        respond(['success' => false, 'message' => 'Failed to create finder.'], 500);
    }

    respond([
        'success'   => true,
        'message'   => 'Finder added successfully.',
        'finder_id' => $new_id
    ]);
}


// ------------------------------------------------------------
// SECTION 4 server-side permission enforcement (HARDENED)
// ------------------------------------------------------------
if ((int)($_POST['sid'] ?? 0) === 4) {

    registerPermission('admission.biometrics.age_on_admission.edit',    'Edit age on admission', 'field');
    registerPermission('admission.biometrics.dehydrated.edit',         'Edit dehydrated flag', 'field');
    registerPermission('admission.biometrics.starved.edit',            'Edit starved flag', 'field');
    registerPermission('admission.biometrics.weight.edit',             'Edit weight', 'field');
    registerPermission('admission.biometrics.weight_unit.edit',        'Edit weight unit', 'field');
    registerPermission('admission.biometrics.measurement.edit',        'Edit measurement', 'field');
    registerPermission('admission.biometrics.measurement_unit.edit',   'Edit measurement unit', 'field');

    $pid_in = (int)($_POST['patient_id'] ?? 0);
    $aid_in = (int)($_POST['admission_id'] ?? 0);

    // Load existing admission for carry-forward + tamper check
    $existing = null;
    if ($pid_in > 0 && $aid_in > 0) {
        $stmt = $pdo->prepare("
            SELECT admission_id, patient_id,
                   age_on_admission, dehydrated, starved,
                   weight, weight_unit, measurement, measurement_unit,
                   admission_date
            FROM rescue_admissions
            WHERE admission_id = :aid
            LIMIT 1
        ");
        $stmt->execute([':aid' => $aid_in]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing || (int)$existing['patient_id'] !== $pid_in) {
            respond(['success' => false, 'message' => 'Admission/patient mismatch.'], 403);
        }
    }

    // Strip blocked fields
    if (!can('admission.biometrics.age_on_admission.edit'))  unset($_POST['age_on_admission']);
    if (!can('admission.biometrics.dehydrated.edit'))       unset($_POST['dehydrated']);
    if (!can('admission.biometrics.starved.edit'))          unset($_POST['starved']);
    if (!can('admission.biometrics.weight.edit'))           unset($_POST['weight']);
    if (!can('admission.biometrics.weight_unit.edit'))      unset($_POST['weight_unit']);
    if (!can('admission.biometrics.measurement.edit'))      unset($_POST['measurement']);
    if (!can('admission.biometrics.measurement_unit.edit')) unset($_POST['measurement_unit']);

    // Carry-forward existing values so locked fields aren't wiped
    if ($existing) {
        if (!isset($_POST['age_on_admission']))  $_POST['_existing_age_on_admission']  = $existing['age_on_admission'];
        if (!isset($_POST['dehydrated']))        $_POST['_existing_dehydrated']        = $existing['dehydrated'];
        if (!isset($_POST['starved']))           $_POST['_existing_starved']           = $existing['starved'];
        if (!isset($_POST['weight']))            $_POST['_existing_weight']            = $existing['weight'];
        if (!isset($_POST['weight_unit']))       $_POST['_existing_weight_unit']       = $existing['weight_unit'];
        if (!isset($_POST['measurement']))       $_POST['_existing_measurement']       = $existing['measurement'];
        if (!isset($_POST['measurement_unit']))  $_POST['_existing_measurement_unit']  = $existing['measurement_unit'];
        $_POST['_existing_admission_date'] = $existing['admission_date']; // used by weights/measures date logic
    }
}
// ========================================================
// SECTION 4 – Biometrics
// ========================================================
if ($sid === 4) {

    $patient_id   = post('patient_id');
    $admission_id = post('admission_id');

    if (empty($patient_id) || empty($admission_id)) {
        respond([
            'success' => false,
            'message' => 'Missing patient_id or admission_id for Section 4.'
        ], 400);
    }

    $markComplete = isset($_POST['mark_complete']) && $_POST['mark_complete'] === '1';

    // ---------------- FIELDS ----------------
    $age_on_admission = post('age_on_admission', '');
    $dehydrated       = post('dehydrated', 'No');
    $starved          = post('starved', 'No');
    $weight           = post('weight', '');
    $weight_unit      = post('weight_unit', 'g');
    $measurement      = post('measurement', '');
    $measurement_unit = post('measurement_unit', 'mm');

    // ---------------- REQUIRED CHECK (matches Section 2/3) ----------------
    $missing_required = [];
    if ($age_on_admission === '') {
        $missing_required[] = 'age_on_admission';
    }

    if ($markComplete && !empty($missing_required)) {
        respond([
            'success' => false,
            'message' => 'Please complete required fields first.',
            'missing' => $missing_required
        ], 400);
    }

// ---------------- INCOMPLETE FIELDS JSON (MERGE-SAFE) ----------------

// Load existing incomplete_fields from DB so we don't wipe other sections
$existingMeta = [];
if ($admission_id > 0) {
    $stmt = $pdo->prepare("
        SELECT incomplete_fields
        FROM rescue_admissions
        WHERE admission_id = :aid
          AND patient_id   = :pid
          AND centre_id    = :cid
        LIMIT 1
    ");
    $stmt->execute([
        ':aid' => $admission_id,
        ':pid' => $patient_id,
        ':cid' => $centre_id
    ]);
    $raw = (string)$stmt->fetchColumn();
    $tmp = $raw !== '' ? json_decode($raw, true) : null;
    if (is_array($tmp)) $existingMeta = $tmp;
}

// Ensure shape
if (!isset($existingMeta['marked_complete']) || !is_array($existingMeta['marked_complete'])) {
    $existingMeta['marked_complete'] = [];
}
if (!isset($existingMeta['missing_by_section']) || !is_array($existingMeta['missing_by_section'])) {
    $existingMeta['missing_by_section'] = [];
}

if ($markComplete) {
    $existingMeta['marked_complete'][4] = 1;
    $existingMeta['missing_by_section'][4] = [];
} else {
    $existingMeta['missing_by_section'][4] = $missing_required;
}

$incomplete_json = json_encode($existingMeta);


    // ---------------- AGE SCORE ----------------
    $age_score_map = [
        'Newborn'              => 3,
        'Dependent Juvenile'   => 2,
        'Independent Juvenile' => 1,
        'Hatchling'            => 3,
        'Fledgling'            => 2,
        'Adult'                => 0,
    ];
    $age_score = $age_score_map[$age_on_admission] ?? null;

    // ---------------- UPDATE ADMISSION ----------------
    $stmt = $pdo->prepare("
        UPDATE rescue_admissions
           SET age_on_admission = :age_on_admission,
               dehydrated       = :dehydrated,
               starved          = :starved,
               weight           = :weight,
               weight_unit      = :weight_unit,
               measurement      = :measurement,
               measurement_unit = :measurement_unit,
               age_score        = :age_score,
               incomplete_fields = :incomplete_fields
         WHERE admission_id     = :admission_id
           AND patient_id       = :patient_id
           AND centre_id        = :centre_id
    ");
    $stmt->execute([
        ':age_on_admission' => $age_on_admission,
        ':dehydrated'       => $dehydrated,
        ':starved'          => $starved,
        ':weight'           => $weight,
        ':weight_unit'      => $weight_unit,
        ':measurement'      => $measurement,
        ':measurement_unit' => $measurement_unit,
        ':age_score'        => $age_score,
        ':incomplete_fields'=> $incomplete_json,
        ':admission_id'     => $admission_id,
        ':patient_id'       => $patient_id,
        ':centre_id'        => $centre_id
    ]);

    respond([
        'success'      => true,
        'admission_id' => $admission_id,
        'patient_id'   => $patient_id
    ]);
}


// ------------------------------------------------------------
// SECTION 5 server-side permission enforcement (HARDENED)
// ------------------------------------------------------------
if ((int)($_POST['sid'] ?? 0) === 5) {

    registerPermission('admission.triage.ss_text.edit',              'Edit severity score (text)', 'field');
    registerPermission('admission.triage.bcs_text.edit',             'Edit body condition score (text)', 'field');
    registerPermission('admission.triage.presenting_complaint.edit', 'Edit presenting complaint', 'field');
    registerPermission('admission.triage.hpc.edit',                  'Edit history of presenting complaint', 'field');
    registerPermission('admission.triage.on_examination.edit',       'Edit on examination notes', 'field');

    $pid_in = (int)($_POST['patient_id'] ?? 0);
    $aid_in = (int)($_POST['admission_id'] ?? 0);

    // Load existing admission for carry-forward + tamper check
    $existing = null;
    if ($pid_in > 0 && $aid_in > 0) {
        $stmt = $pdo->prepare("
            SELECT admission_id, patient_id,
                   ss_text, bcs_text, presenting_complaint, hpc, on_examination
            FROM rescue_admissions
            WHERE admission_id = :aid
            LIMIT 1
        ");
        $stmt->execute([':aid' => $aid_in]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing || (int)$existing['patient_id'] !== $pid_in) {
            respond(['success' => false, 'message' => 'Admission/patient mismatch.'], 403);
        }
    }

    // Strip blocked fields
    if (!can('admission.triage.ss_text.edit'))               unset($_POST['ss_text']);
    if (!can('admission.triage.bcs_text.edit'))              unset($_POST['bcs_text']);
    if (!can('admission.triage.presenting_complaint.edit'))  unset($_POST['presenting_complaint']);
    if (!can('admission.triage.hpc.edit'))                   unset($_POST['hpc']);
    if (!can('admission.triage.on_examination.edit'))        unset($_POST['on_examination']);

    // Never accept derived numeric scores from the client
    unset($_POST['severity_score'], $_POST['bc_score']);

    // Carry-forward values so locked fields aren't wiped
    if ($existing) {
        if (!isset($_POST['ss_text']))               $_POST['_existing_ss_text']               = $existing['ss_text'];
        if (!isset($_POST['bcs_text']))              $_POST['_existing_bcs_text']              = $existing['bcs_text'];
        if (!isset($_POST['presenting_complaint']))  $_POST['_existing_presenting_complaint']  = $existing['presenting_complaint'];
        if (!isset($_POST['hpc']))                   $_POST['_existing_hpc']                   = $existing['hpc'];
        if (!isset($_POST['on_examination']))        $_POST['_existing_on_examination']        = $existing['on_examination'];
    }
}
// ========================================================
// SECTION 5 – TRIAGE & ASSESSMENT (MERGE-SAFE INCOMPLETE_FIELDS)
// ========================================================
if ($sid === 5) {

    $patient_id   = post('patient_id');
    $admission_id = post('admission_id');

    if (!$patient_id || !$admission_id) {
        respond([
            'success' => false,
            'message' => 'Missing patient_id or admission_id for Section 5.'
        ], 400);
    }

    $markComplete = isset($_POST['mark_complete']) && $_POST['mark_complete'] === '1';

    $ss_text = isset($_POST['ss_text'])
        ? post('ss_text', '')
        : (string)($_POST['_existing_ss_text'] ?? '');

    $bcs_text = isset($_POST['bcs_text'])
        ? post('bcs_text', '')
        : (string)($_POST['_existing_bcs_text'] ?? '');

    $presenting = isset($_POST['presenting_complaint'])
        ? post('presenting_complaint', '')
        : (string)($_POST['_existing_presenting_complaint'] ?? '');

    $hpc = isset($_POST['hpc'])
        ? post('hpc', '')
        : (string)($_POST['_existing_hpc'] ?? '');

    $on_examination = isset($_POST['on_examination'])
        ? post('on_examination', '')
        : (string)($_POST['_existing_on_examination'] ?? '');

    $severity_map = [
        'Apparently Healthy' => 0,
        'Mildly unwell'      => 0,
        'Obvious Injuries'   => 1,
        'Severe Injuries'    => 2,
        'Near Death'         => 3
    ];
    $severity_score = ($ss_text !== '') ? ($severity_map[$ss_text] ?? null) : null;

    $bcs_map = [
        'BCS 1 Skeletal'             => 3,
        'BCS 2 Underweight'          => 2,
        'BCS 3 Slightly Underweight' => 1,
        'BCS 4 Healthy'              => 0,
        'BCS 5 Overweight'           => 0
    ];
    $bc_score = ($bcs_text !== '') ? ($bcs_map[$bcs_text] ?? null) : null;

    // Section 5 default: no required fields (kept consistent with your intent)
    $missing_required = [];

    if ($markComplete && !empty($missing_required)) {
        respond([
            'success' => false,
            'message' => 'Please complete required fields first.',
            'missing' => $missing_required
        ], 400);
    }

    // ---------------- INCOMPLETE_FIELDS JSON (MERGE-SAFE) ----------------
    $existingMeta = [];

    $stmt = $pdo->prepare("
        SELECT incomplete_fields
        FROM rescue_admissions
        WHERE admission_id = :aid
          AND patient_id   = :pid
          AND centre_id    = :cid
        LIMIT 1
    ");
    $stmt->execute([
        ':aid' => $admission_id,
        ':pid' => $patient_id,
        ':cid' => $centre_id
    ]);
    $raw = (string)$stmt->fetchColumn();

    if ($raw !== '') {
        $tmp = json_decode($raw, true);

        // If it's already the new object format
        if (is_array($tmp) && (isset($tmp['marked_complete']) || isset($tmp['missing_by_section']))) {
            $existingMeta = $tmp;
        }
        // If it's the legacy format (plain list), convert it into the object
        elseif (is_array($tmp)) {
            $existingMeta = [
                'marked_complete'   => [],
                'missing_by_section'=> []
            ];
        }
    }

    if (!isset($existingMeta['marked_complete']) || !is_array($existingMeta['marked_complete'])) {
        $existingMeta['marked_complete'] = [];
    }
    if (!isset($existingMeta['missing_by_section']) || !is_array($existingMeta['missing_by_section'])) {
        $existingMeta['missing_by_section'] = [];
    }

    if ($markComplete) {
        $existingMeta['marked_complete'][5] = 1;
    }

    // Track missing for this section (even though required is empty by default)
    $existingMeta['missing_by_section'][5] = $missing_required;

    $incomplete_json = json_encode($existingMeta);

    // ---------------- UPDATE ----------------
    $stmt = $pdo->prepare("
        UPDATE rescue_admissions
           SET ss_text              = :ss_text,
               severity_score       = :severity_score,
               bcs_text             = :bcs_text,
               bc_score             = :bc_score,
               presenting_complaint = :presenting,
               hpc                  = :hpc,
               on_examination       = :on_exam,
               incomplete_fields    = :incomplete_fields
         WHERE admission_id         = :aid
           AND patient_id           = :pid
           AND centre_id            = :centre_id
    ");
    $stmt->execute([
        ':ss_text'           => $ss_text,
        ':severity_score'    => $severity_score,
        ':bcs_text'          => $bcs_text,
        ':bc_score'          => $bc_score,
        ':presenting'        => $presenting,
        ':hpc'               => $hpc,
        ':on_exam'           => $on_examination,
        ':incomplete_fields' => $incomplete_json,
        ':aid'               => $admission_id,
        ':pid'               => $patient_id,
        ':centre_id'         => $centre_id
    ]);

    respond(['success'=>true,'message'=>'Section 5 saved','aid'=>$admission_id,'pid'=>$patient_id]);
}


// ------------------------------------------------------------
// SECTION 6 server-side permission enforcement (HARDENED)
// ------------------------------------------------------------
if ((int)($_POST['sid'] ?? 0) === 6) {

    registerPermission('admission.weather.w_temp.edit',     'Edit temperature (weather)', 'field');
    registerPermission('admission.weather.w_wind.edit',     'Edit wind speed (weather)', 'field');
    registerPermission('admission.weather.w_humidity.edit', 'Edit humidity (weather)', 'field');
    registerPermission('admission.weather.w_freetext.edit', 'Edit weather free text', 'field');

    $pid_in = (int)($_POST['patient_id'] ?? 0);
    $aid_in = (int)($_POST['admission_id'] ?? 0);

    // Load existing admission for carry-forward + tamper check
    $existing = null;
    if ($pid_in > 0 && $aid_in > 0) {
        $stmt = $pdo->prepare("
            SELECT admission_id, patient_id, w_temp, w_wind, w_humidity, w_freetext
            FROM rescue_admissions
            WHERE admission_id = :aid
            LIMIT 1
        ");
        $stmt->execute([':aid' => $aid_in]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing || (int)$existing['patient_id'] !== $pid_in) {
            respond(['success' => false, 'message' => 'Admission/patient mismatch.'], 403);
        }
    }

    // Strip blocked fields
    if (!can('admission.weather.w_temp.edit'))     unset($_POST['w_temp']);
    if (!can('admission.weather.w_wind.edit'))     unset($_POST['w_wind']);
    if (!can('admission.weather.w_humidity.edit')) unset($_POST['w_humidity']);
    if (!can('admission.weather.w_freetext.edit')) unset($_POST['w_freetext']);

    // Carry-forward to prevent wipe
    if ($existing) {
        if (!isset($_POST['w_temp']))     $_POST['_existing_w_temp']     = $existing['w_temp'];
        if (!isset($_POST['w_wind']))     $_POST['_existing_w_wind']     = $existing['w_wind'];
        if (!isset($_POST['w_humidity'])) $_POST['_existing_w_humidity'] = $existing['w_humidity'];
        if (!isset($_POST['w_freetext'])) $_POST['_existing_w_freetext'] = $existing['w_freetext'];
    }
}
// ========================================================
// SECTION 6 — WEATHER DATA (optional, merge-safe)
// ========================================================
if ($sid === 6) {

    $patient_id   = post('patient_id');
    $admission_id = post('admission_id');

    if (!$patient_id || !$admission_id) {
        respond([
            'success' => false,
            'message' => 'Missing patient_id or admission_id for Section 6.'
        ], 400);
    }

    $markComplete = isset($_POST['mark_complete']) && $_POST['mark_complete'] === '1';

    // ---------------- FIELDS ----------------
    $w_temp = isset($_POST['w_temp'])
        ? post('w_temp', '')
        : (string)($_POST['_existing_w_temp'] ?? '');

    $w_wind = isset($_POST['w_wind'])
        ? post('w_wind', '')
        : (string)($_POST['_existing_w_wind'] ?? '');

    $w_humidity = isset($_POST['w_humidity'])
        ? post('w_humidity', '')
        : (string)($_POST['_existing_w_humidity'] ?? '');

    $w_freetext = isset($_POST['w_freetext'])
        ? post('w_freetext', '')
        : (string)($_POST['_existing_w_freetext'] ?? '');

    // ---------------- MERGE-SAFE INCOMPLETE JSON ----------------
    $existingMeta = [];
    $stmt = $pdo->prepare("
        SELECT incomplete_fields
        FROM rescue_admissions
        WHERE admission_id = :aid
          AND patient_id   = :pid
          AND centre_id    = :cid
        LIMIT 1
    ");
    $stmt->execute([
        ':aid' => $admission_id,
        ':pid' => $patient_id,
        ':cid' => $centre_id
    ]);

    $raw = (string)$stmt->fetchColumn();
    $tmp = ($raw !== '') ? json_decode($raw, true) : null;
    if (is_array($tmp)) $existingMeta = $tmp;

    if (!isset($existingMeta['marked_complete']) || !is_array($existingMeta['marked_complete'])) {
        $existingMeta['marked_complete'] = [];
    }
    if (!isset($existingMeta['missing_by_section']) || !is_array($existingMeta['missing_by_section'])) {
        $existingMeta['missing_by_section'] = [];
    }

    // Section 6 is optional → always empty missing list
    $existingMeta['missing_by_section'][6] = [];

    // Mark complete flag (ONLY set when requested; do not wipe if not requested)
    if ($markComplete) {
        $existingMeta['marked_complete'][6] = 1;
    }

    $incomplete_json = json_encode($existingMeta);

    // ---------------- UPDATE ADMISSION ----------------
    $stmt = $pdo->prepare("
        UPDATE rescue_admissions
           SET w_temp            = :w_temp,
               w_wind            = :w_wind,
               w_humidity        = :w_humidity,
               w_freetext        = :w_freetext,
               incomplete_fields = :incomplete_fields
         WHERE admission_id      = :aid
           AND patient_id        = :pid
           AND centre_id         = :centre_id
    ");
    $stmt->execute([
        ':w_temp'            => $w_temp,
        ':w_wind'            => $w_wind,
        ':w_humidity'        => $w_humidity,
        ':w_freetext'        => $w_freetext,
        ':incomplete_fields' => $incomplete_json,
        ':aid'               => $admission_id,
        ':pid'               => $patient_id,
        ':centre_id'         => $centre_id
    ]);

    respond([
        'success' => true,
        'message' => 'Section 6 saved',
        'aid'     => $admission_id,
        'pid'     => $patient_id
    ]);
}


// ------------------------------------------------------------
// SECTION 7 server-side permission enforcement (HARDENED)
// ------------------------------------------------------------
if ((int)($_POST['sid'] ?? 0) === 7) {

    registerPermission('admission.declaration.complete', 'Complete declaration/signature', 'action');

    if (!can('admission.declaration.complete')) {
        respond(['success' => false, 'message' => 'You do not have permission to complete the declaration.'], 403);
    }

    $pid_in = (int)($_POST['patient_id'] ?? 0);
    $aid_in = (int)($_POST['admission_id'] ?? 0);

    // Admission/patient tamper check
    if ($pid_in > 0 && $aid_in > 0) {
        $stmt = $pdo->prepare("SELECT patient_id FROM rescue_admissions WHERE admission_id = :aid LIMIT 1");
        $stmt->execute([':aid' => $aid_in]);
        $dbPid = (int)$stmt->fetchColumn();

        if (!$dbPid || $dbPid !== $pid_in) {
            respond(['success' => false, 'message' => 'Admission/patient mismatch.'], 403);
        }
    }

    // Prevent multiple submissions: if a record already exists, block
    $stmt = $pdo->prepare("
        SELECT 1
        FROM rescue_signatures
        WHERE admission_id = :aid
          AND patient_id   = :pid
        LIMIT 1
    ");
    $stmt->execute([':aid' => $aid_in, ':pid' => $pid_in]);
    if ($stmt->fetchColumn()) {
        respond(['success' => false, 'message' => 'Declaration already recorded for this admission.'], 409);
    }
}
// ========================================================
// SECTION 7 — DECLARATION & SIGNATURE
// ========================================================
if ($sid === 7) {

    $patient_id   = post('patient_id');
    $admission_id = post('admission_id');
    $signature    = post('signature_data');   // base64 dataURL OR blank
    $refused      = post('no_signature');     // "1" if refusal checked

    if (!$patient_id || !$admission_id) {
        respond([
            'success' => false,
            'message' => 'Missing patient_id or admission_id for Section 7.'
        ], 400);
    }

    // If a signature record already exists (double-submit protection)
    $stmt = $pdo->prepare("
        SELECT 1
        FROM rescue_signatures
        WHERE admission_id = :aid
          AND patient_id   = :pid
          AND centre_id   = :centre_id
        LIMIT 1
    ");
    $stmt->execute([
        ':aid' => $admission_id,
        ':pid' => $patient_id,
        ':centre_id' => $centre_id
    ]);
    if ($stmt->fetchColumn()) {
        respond([
            'success' => false,
            'message' => 'Declaration already recorded for this admission.'
        ], 409);
    }

    // Must record either a signature OR a refusal
    if (empty($signature) && $refused !== "1") {
        respond([
            'success' => false,
            'message' => 'A signature or refusal must be recorded.'
        ], 400);
    }

    // Store refusal flag in `refused`, signature_data blank if refused
    $finalSignature = ($refused === "1") ? '' : $signature;
    $finalRefusal   = ($refused === "1") ? 1 : 0;

    $stmt = $pdo->prepare("
        INSERT INTO rescue_signatures
            (centre_id, admission_id, patient_id, user_id, signature_data, refused, signed_at)
        VALUES
            (:centre_id, :admission_id, :patient_id, :user_id, :signature_data, :refused, NOW())
    ");

    $stmt->execute([
        ':centre_id'      => $centre_id,
        ':admission_id'   => $admission_id,
        ':patient_id'     => $patient_id,
        ':user_id'        => $user_id,
        ':signature_data' => $finalSignature,
        ':refused'        => $finalRefusal
    ]);

    // ✅ Add message for consistent UI feedback
    respond([
        'success'      => true,
        'message'      => 'Section 7 saved',
        'admission_id' => (int)$admission_id,
        'patient_id'   => (int)$patient_id
    ]);
}

// ========================================================
// SID 99 — FINALISE ADMISSION (Admit Patient)
// ========================================================
if ($sid === 99) {

    $patient_id   = post('patient_id');
    $admission_id = post('admission_id');

    if (!$patient_id || !$admission_id) {
        respond([
            'success' => false,
            'message' => 'Missing patient_id or admission_id.'
        ], 400);
    }

    // Get initial location for logging
    $stmt = $pdo->prepare("
        SELECT current_location_id
        FROM rescue_admissions
        WHERE admission_id = :aid
          AND patient_id   = :pid
          AND centre_id    = :cid
        LIMIT 1
    ");
    $stmt->execute([
        ':aid' => $admission_id,
        ':pid' => $patient_id,
        ':cid' => $centre_id
    ]);
    $to_location_id = (int)($stmt->fetchColumn() ?? 0);

    // Existing finalise action
    $stmt = $pdo->prepare("
        UPDATE rescue_patients
        SET state = 'Admitted'
        WHERE patient_id = :pid
    ");
    $stmt->execute([':pid' => $patient_id]);

    // Log admission event (non-blocking)
    transfers_log($pdo, 'admission', [
        'patient_id'     => (int)$patient_id,
        'admission_id'   => (int)$admission_id,
        'to_location_id' => ($to_location_id > 0 ? $to_location_id : null)
    ]);

    respond([
        'success' => true,
        'message' => 'Patient admitted successfully'
    ]);
}


    // ========================================================
    // FALLBACK
    // ========================================================
    respond([
        'success' => false,
        'message' => 'Section handler not yet implemented for sid=' . $sid
    ], 400);

} catch (PDOException $e) {
    respond([
        'success' => false,
        'message' => 'PDO ERROR: ' . $e->getMessage()
    ], 500);
} catch (Throwable $e) {
    respond([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], 500);
}
