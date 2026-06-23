<?php
session_start();
ob_start();

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../operations/audit.php';

header('Content-Type: application/json; charset=utf-8');

function respond(array $data, int $status = 200): void
{
    http_response_code($status);

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function str_or_null($value): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function arr_section(array $data, string $key): array
{
    return (isset($data[$key]) && is_array($data[$key])) ? $data[$key] : [];
}

function mark_missing(array &$meta, int $section, array $missing, bool $markComplete = false): void
{
    if (!isset($meta['marked_complete']) || !is_array($meta['marked_complete'])) {
        $meta['marked_complete'] = [];
    }

    if (!isset($meta['missing_by_section']) || !is_array($meta['missing_by_section'])) {
        $meta['missing_by_section'] = [];
    }

    if ($markComplete) {
        $meta['marked_complete'][$section] = 1;
        $meta['missing_by_section'][$section] = [];
        return;
    }

    $meta['missing_by_section'][$section] = array_values($missing);
}

function has_signature_payload(array $section7): bool
{
    $signature = trim((string)($section7['signature_data'] ?? ''));
    $refused   = trim((string)($section7['no_signature'] ?? ''));

    return ($signature !== '' || $refused === '1');
}

function boolish_int($value): int
{
    return ((string)$value === '1' || $value === 1 || $value === true) ? 1 : 0;
}

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        respond([
            'success' => false,
            'error'   => 'Invalid JSON payload',
            'message' => 'Invalid JSON payload'
        ], 400);
    }

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
            'error'   => 'Database connection failed.',
            'message' => 'Database connection failed.'
        ], 500);
    }

    $user_id = $_SESSION['account_id'] ?? $_SESSION['user_id'] ?? null;
    $centre_id = $_SESSION['centre_id'] ?? null;

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

            if ($centre_id) {
                $_SESSION['centre_id'] = $centre_id;
            }
        } catch (Exception $e) {
            // leave as null
        }
    }

    $centre_id = (int)$centre_id;
    $user_id   = $user_id !== null ? (int)$user_id : null;

    $GLOBALS['user_id']   = $user_id;
    $GLOBALS['centre_id'] = $centre_id;

    if ($centre_id <= 0) {
        respond([
            'success' => false,
            'error'   => 'Missing centre_id',
            'message' => 'Missing centre_id'
        ], 400);
    }

    $create_key   = trim((string)($data['create_key'] ?? ''));
    $patient_id   = (int)($data['patient_id'] ?? 0);
    $admission_id = (int)($data['admission_id'] ?? 0);

    if ($create_key === '') {
        respond([
            'success' => false,
            'error'   => 'Missing create_key',
            'message' => 'Missing create_key'
        ], 400);
    }

    if ($patient_id <= 0) {
        respond([
            'success' => false,
            'error'   => 'Missing patient_id',
            'message' => 'Missing patient_id'
        ], 400);
    }

    $section2 = arr_section($data, 'section2');
    $section3 = arr_section($data, 'section3');
    $section4 = arr_section($data, 'section4');
    $section5 = arr_section($data, 'section5');
    $section6 = arr_section($data, 'section6');
    $section7 = arr_section($data, 'section7');

    if ($admission_id <= 0) {
        $stmt = $pdo->prepare("
            SELECT admission_id
            FROM rescue_admissions
            WHERE mobile_create_key = :create_key
              AND patient_id = :patient_id
              AND centre_id = :centre_id
            LIMIT 1
        ");
        $stmt->execute([
            ':create_key' => $create_key,
            ':patient_id' => $patient_id,
            ':centre_id'  => $centre_id
        ]);
        $existingByKey = $stmt->fetch();

        if ($existingByKey && !empty($existingByKey['admission_id'])) {
            $admission_id = (int)$existingByKey['admission_id'];
        }
    }

    if ($admission_id > 0) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM rescue_admissions
            WHERE admission_id = :admission_id
              AND patient_id = :patient_id
              AND centre_id = :centre_id
            LIMIT 1
        ");
        $stmt->execute([
            ':admission_id' => $admission_id,
            ':patient_id'   => $patient_id,
            ':centre_id'    => $centre_id
        ]);
        $existingAdmission = $stmt->fetch();

        if (!$existingAdmission) {
            respond([
                'success' => false,
                'error'   => 'Admission not found for this patient.',
                'message' => 'Admission not found for this patient.'
            ], 404);
        }
    } else {
        $existingAdmission = null;
    }

    $existingMeta = [];
    if ($existingAdmission) {
        $rawMeta = (string)($existingAdmission['incomplete_fields'] ?? '');
        $tmpMeta = $rawMeta !== '' ? json_decode($rawMeta, true) : null;
        if (is_array($tmpMeta)) {
            $existingMeta = $tmpMeta;
        }
    }

    if (!isset($existingMeta['marked_complete']) || !is_array($existingMeta['marked_complete'])) {
        $existingMeta['marked_complete'] = [];
    }
    if (!isset($existingMeta['missing_by_section']) || !is_array($existingMeta['missing_by_section'])) {
        $existingMeta['missing_by_section'] = [];
    }

    $row = [
        'patient_id'            => $patient_id,
        'centre_id'             => $centre_id,
        'staff_wp_id'           => $user_id,
        'mobile_create_key'     => $create_key,
        'admission_date'        => $existingAdmission['admission_date'] ?? null,
        'status'                => $existingAdmission['status'] ?? null,
        'disposition'           => $existingAdmission['disposition'] ?? null,
        'time_to_admission'     => $existingAdmission['time_to_admission'] ?? null,
        'current_location_id'   => $existingAdmission['current_location_id'] ?? null,
        'current_location'      => $existingAdmission['current_location'] ?? null,
        'collection_location'   => $existingAdmission['collection_location'] ?? null,
        'location_lat'          => $existingAdmission['location_lat'] ?? null,
        'location_long'         => $existingAdmission['location_long'] ?? null,
        'finder_id'             => $existingAdmission['finder_id'] ?? null,
        'finder_name'           => $existingAdmission['finder_name'] ?? null,
        'finder_tel'            => $existingAdmission['finder_tel'] ?? null,
        'consent_to_update'     => $existingAdmission['consent_to_update'] ?? null,
        'passphrase'            => $existingAdmission['passphrase'] ?? null,
        'age_on_admission'      => $existingAdmission['age_on_admission'] ?? null,
        'dehydrated'            => $existingAdmission['dehydrated'] ?? null,
        'starved'               => $existingAdmission['starved'] ?? null,
        'weight'                => $existingAdmission['weight'] ?? null,
        'weight_unit'           => $existingAdmission['weight_unit'] ?? null,
        'measurement'           => $existingAdmission['measurement'] ?? null,
        'measurement_unit'      => $existingAdmission['measurement_unit'] ?? null,
        'age_score'             => $existingAdmission['age_score'] ?? null,
        'ss_text'               => $existingAdmission['ss_text'] ?? null,
        'bcs_text'              => $existingAdmission['bcs_text'] ?? null,
        'presenting_complaint'  => $existingAdmission['presenting_complaint'] ?? null,
        'hpc'                   => $existingAdmission['hpc'] ?? null,
        'on_examination'        => $existingAdmission['on_examination'] ?? null,
        'w_temp'                => $existingAdmission['w_temp'] ?? null,
        'w_wind'                => $existingAdmission['w_wind'] ?? null,
        'w_humidity'            => $existingAdmission['w_humidity'] ?? null,
        'w_freetext'            => $existingAdmission['w_freetext'] ?? null,
    ];

    // SECTION 2
    if (!empty($section2)) {
        $admission_date_raw  = trim((string)($section2['admission_date'] ?? ''));
        $time_to_admission   = trim((string)($section2['time_to_admission'] ?? ''));
        $current_location_id = (int)($section2['current_location_id'] ?? 0);
        $disposition         = trim((string)($section2['disposition'] ?? ''));
        $status              = trim((string)($section2['status'] ?? ''));

        if ($status === '') {
            $status = ($disposition === 'Held in captivity') ? 'Active' : ($disposition !== '' ? 'Closed' : '');
        }

        $admission_date = null;
        if ($admission_date_raw !== '') {
            try {
                $dt = new DateTime($admission_date_raw);
                $admission_date = $dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                $admission_date = null;
            }
        }

        $current_location = '';
        if ($current_location_id > 0) {
            $stmt = $pdo->prepare("
                SELECT location_name
                FROM rescue_locations
                WHERE location_id = :location_id
                  AND centre_id = :centre_id
                LIMIT 1
            ");
            $stmt->execute([
                ':location_id' => $current_location_id,
                ':centre_id'   => $centre_id
            ]);
            $current_location = (string)($stmt->fetchColumn() ?: '');
        }

        $missing2 = [];
        if ($admission_date === null) $missing2[] = 'admission_date';
        if ($current_location_id <= 0 || $current_location === '') $missing2[] = 'current_location';
        if ($disposition === '') $missing2[] = 'disposition';

        mark_missing($existingMeta, 2, $missing2);

        $row['admission_date']      = $admission_date;
        $row['time_to_admission']   = str_or_null($time_to_admission);
        $row['current_location_id'] = $current_location_id > 0 ? $current_location_id : null;
        $row['current_location']    = str_or_null($current_location);
        $row['disposition']         = str_or_null($disposition);
        $row['status']              = str_or_null($status);
    }

    // SECTION 3
    if (!empty($section3)) {
        $collection_location = trim((string)($section3['collection_location'] ?? ''));
        $location_lat        = trim((string)($section3['location_lat'] ?? ''));
        $location_long       = trim((string)($section3['location_long'] ?? ''));
        $finder_id           = (int)($section3['finder_id'] ?? 0);
        $finder_create_key   = trim((string)($section3['finder_create_key'] ?? ''));
        $finder_name         = trim((string)($section3['finder_name'] ?? ''));
        $finder_tel          = trim((string)($section3['finder_tel'] ?? ''));
        $consent_to_update   = isset($section3['consent_to_update']) ? (int)$section3['consent_to_update'] : null;
        $passphrase          = trim((string)($section3['passphrase'] ?? ''));
        $storedPassphrase    = trim((string)($existingAdmission['passphrase'] ?? ''));
        if ($storedPassphrase !== '') {
            $passphrase = $storedPassphrase;
        }

        $missing3 = [];
        if ($collection_location === '') $missing3[] = 'collection_location';
        if ($location_lat === '') $missing3[] = 'location_lat';
        if ($location_long === '') $missing3[] = 'location_long';
        if ($passphrase === '') $missing3[] = 'passphrase';

        mark_missing($existingMeta, 3, $missing3);

        if ($finder_id <= 0 && $finder_name !== '' && $finder_create_key !== '') {
            $stmt = $pdo->prepare("
                SELECT finder_id
                FROM rescue_finders
                WHERE mobile_create_key = :mobile_create_key
                  AND deleted = 0
                LIMIT 1
            ");
            $stmt->execute([
                ':mobile_create_key' => $finder_create_key
            ]);
            $finder_id = (int)$stmt->fetchColumn();

            if ($finder_id <= 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO rescue_finders (
                        centre_id,
                        finder_name,
                        finder_tel,
                        mobile_create_key,
                        created_at,
                        updated_at,
                        deleted
                    ) VALUES (
                        :centre_id,
                        :finder_name,
                        :finder_tel,
                        :mobile_create_key,
                        NOW(),
                        NOW(),
                        0
                    )
                ");
                $stmt->execute([
                    ':centre_id'         => $centre_id,
                    ':finder_name'       => $finder_name,
                    ':finder_tel'        => str_or_null($finder_tel),
                    ':mobile_create_key' => $finder_create_key
                ]);

                $stmt = $pdo->prepare("
                    SELECT finder_id
                    FROM rescue_finders
                    WHERE mobile_create_key = :mobile_create_key
                      AND deleted = 0
                    LIMIT 1
                ");
                $stmt->execute([
                    ':mobile_create_key' => $finder_create_key
                ]);
                $finder_id = (int)$stmt->fetchColumn();
            }
        }

        $row['collection_location'] = str_or_null($collection_location);
        $row['location_lat']        = str_or_null($location_lat);
        $row['location_long']       = str_or_null($location_long);
        $row['finder_id']           = $finder_id > 0 ? $finder_id : null;
        $row['finder_name']         = str_or_null($finder_name);
        $row['finder_tel']          = str_or_null($finder_tel);
        $row['consent_to_update']   = $consent_to_update !== null ? $consent_to_update : null;
        $row['passphrase']          = str_or_null($passphrase);
    }

    // SECTION 4
    if (!empty($section4)) {
        $age_on_admission = trim((string)($section4['age_on_admission'] ?? ''));
        $dehydrated       = trim((string)($section4['dehydrated'] ?? ''));
        $starved          = trim((string)($section4['starved'] ?? ''));
        $weight           = trim((string)($section4['weight'] ?? ''));
        $weight_unit      = trim((string)($section4['weight_unit'] ?? ''));
        $measurement      = trim((string)($section4['measurement'] ?? ''));
        $measurement_unit = trim((string)($section4['measurement_unit'] ?? ''));

        $age_score_map = [
            'Neonate'               => 10,
            'Juvenile - pre weaned' => 8,
            'Juvenile - post weaned'=> 6,
            'Subadult'              => 4,
            'Fledgling'             => 2,
            'Adult'                 => 0,
        ];
        $age_score = array_key_exists($age_on_admission, $age_score_map) ? $age_score_map[$age_on_admission] : null;

        mark_missing($existingMeta, 4, []);

        $row['age_on_admission'] = str_or_null($age_on_admission);
        $row['dehydrated']       = str_or_null($dehydrated);
        $row['starved']          = str_or_null($starved);
        $row['weight']           = str_or_null($weight);
        $row['weight_unit']      = str_or_null($weight_unit);
        $row['measurement']      = str_or_null($measurement);
        $row['measurement_unit'] = str_or_null($measurement_unit);
        $row['age_score']        = $age_score;
    }

    // SECTION 5
    if (!empty($section5)) {
        $row['ss_text']              = str_or_null($section5['ss_text'] ?? null);
        $row['bcs_text']             = str_or_null($section5['bcs_text'] ?? null);
        $row['presenting_complaint'] = str_or_null($section5['presenting_complaint'] ?? null);
        $row['hpc']                  = str_or_null($section5['hpc'] ?? null);
        $row['on_examination']       = str_or_null($section5['on_examination'] ?? null);

        mark_missing($existingMeta, 5, []);
    }

    // SECTION 6
    if (!empty($section6)) {
        $row['w_temp']     = str_or_null($section6['w_temp'] ?? null);
        $row['w_wind']     = str_or_null($section6['w_wind'] ?? null);
        $row['w_humidity'] = str_or_null($section6['w_humidity'] ?? null);
        $row['w_freetext'] = str_or_null($section6['w_freetext'] ?? null);

        mark_missing($existingMeta, 6, []);
    }

    $row['incomplete_fields'] = json_encode($existingMeta, JSON_UNESCAPED_UNICODE);
    $created = false;

    if ($admission_id <= 0) {
        $stmt = $pdo->prepare("
            INSERT INTO rescue_admissions (
                patient_id,
                admission_date,
                status,
                disposition,
                centre_id,
                staff_wp_id,
                time_to_admission,
                date_created,
                current_location,
                current_location_id,
                incomplete_fields,
                mobile_create_key,
                collection_location,
                location_lat,
                location_long,
                finder_id,
                finder_name,
                finder_tel,
                consent_to_update,
                passphrase,
                age_on_admission,
                dehydrated,
                starved,
                weight,
                weight_unit,
                measurement,
                measurement_unit,
                age_score,
                ss_text,
                bcs_text,
                presenting_complaint,
                hpc,
                on_examination,
                w_temp,
                w_wind,
                w_humidity,
                w_freetext
            ) VALUES (
                :patient_id,
                :admission_date,
                :status,
                :disposition,
                :centre_id,
                :staff_wp_id,
                :time_to_admission,
                :date_created,
                :current_location,
                :current_location_id,
                :incomplete_fields,
                :mobile_create_key,
                :collection_location,
                :location_lat,
                :location_long,
                :finder_id,
                :finder_name,
                :finder_tel,
                :consent_to_update,
                :passphrase,
                :age_on_admission,
                :dehydrated,
                :starved,
                :weight,
                :weight_unit,
                :measurement,
                :measurement_unit,
                :age_score,
                :ss_text,
                :bcs_text,
                :presenting_complaint,
                :hpc,
                :on_examination,
                :w_temp,
                :w_wind,
                :w_humidity,
                :w_freetext
            )
        ");

        $stmt->execute([
            ':patient_id'           => $row['patient_id'],
            ':admission_date'       => $row['admission_date'],
            ':status'               => $row['status'] ?: 'Active',
            ':disposition'          => $row['disposition'],
            ':centre_id'            => $row['centre_id'],
            ':staff_wp_id'          => $row['staff_wp_id'],
            ':time_to_admission'    => $row['time_to_admission'],
            ':date_created'         => date('Y-m-d H:i:s'),
            ':current_location'     => $row['current_location'],
            ':current_location_id'  => $row['current_location_id'],
            ':incomplete_fields'    => $row['incomplete_fields'],
            ':mobile_create_key'    => $row['mobile_create_key'],
            ':collection_location'  => $row['collection_location'],
            ':location_lat'         => $row['location_lat'],
            ':location_long'        => $row['location_long'],
            ':finder_id'            => $row['finder_id'],
            ':finder_name'          => $row['finder_name'],
            ':finder_tel'           => $row['finder_tel'],
            ':consent_to_update'    => $row['consent_to_update'],
            ':passphrase'           => $row['passphrase'],
            ':age_on_admission'     => $row['age_on_admission'],
            ':dehydrated'           => $row['dehydrated'],
            ':starved'              => $row['starved'],
            ':weight'               => $row['weight'],
            ':weight_unit'          => $row['weight_unit'],
            ':measurement'          => $row['measurement'],
            ':measurement_unit'     => $row['measurement_unit'],
            ':age_score'            => $row['age_score'],
            ':ss_text'              => $row['ss_text'],
            ':bcs_text'             => $row['bcs_text'],
            ':presenting_complaint' => $row['presenting_complaint'],
            ':hpc'                  => $row['hpc'],
            ':on_examination'       => $row['on_examination'],
            ':w_temp'               => $row['w_temp'],
            ':w_wind'               => $row['w_wind'],
            ':w_humidity'           => $row['w_humidity'],
            ':w_freetext'           => $row['w_freetext'],
        ]);

        $admission_id = (int)$pdo->lastInsertId();
        $created = true;
    } else {
        $stmt = $pdo->prepare("
            UPDATE rescue_admissions
               SET admission_date       = :admission_date,
                   status               = :status,
                   disposition          = :disposition,
                   time_to_admission    = :time_to_admission,
                   current_location     = :current_location,
                   current_location_id  = :current_location_id,
                   incomplete_fields    = :incomplete_fields,
                   collection_location  = :collection_location,
                   location_lat         = :location_lat,
                   location_long        = :location_long,
                   finder_id            = :finder_id,
                   finder_name          = :finder_name,
                   finder_tel           = :finder_tel,
                   consent_to_update    = :consent_to_update,
                   passphrase           = :passphrase,
                   age_on_admission     = :age_on_admission,
                   dehydrated           = :dehydrated,
                   starved              = :starved,
                   weight               = :weight,
                   weight_unit          = :weight_unit,
                   measurement          = :measurement,
                   measurement_unit     = :measurement_unit,
                   age_score            = :age_score,
                   ss_text              = :ss_text,
                   bcs_text             = :bcs_text,
                   presenting_complaint = :presenting_complaint,
                   hpc                  = :hpc,
                   on_examination       = :on_examination,
                   w_temp               = :w_temp,
                   w_wind               = :w_wind,
                   w_humidity           = :w_humidity,
                   w_freetext           = :w_freetext
             WHERE admission_id         = :admission_id
               AND patient_id           = :patient_id
               AND centre_id            = :centre_id
        ");

        $stmt->execute([
            ':admission_date'       => $row['admission_date'],
            ':status'               => $row['status'],
            ':disposition'          => $row['disposition'],
            ':time_to_admission'    => $row['time_to_admission'],
            ':current_location'     => $row['current_location'],
            ':current_location_id'  => $row['current_location_id'],
            ':incomplete_fields'    => $row['incomplete_fields'],
            ':collection_location'  => $row['collection_location'],
            ':location_lat'         => $row['location_lat'],
            ':location_long'        => $row['location_long'],
            ':finder_id'            => $row['finder_id'],
            ':finder_name'          => $row['finder_name'],
            ':finder_tel'           => $row['finder_tel'],
            ':consent_to_update'    => $row['consent_to_update'],
            ':passphrase'           => $row['passphrase'],
            ':age_on_admission'     => $row['age_on_admission'],
            ':dehydrated'           => $row['dehydrated'],
            ':starved'              => $row['starved'],
            ':weight'               => $row['weight'],
            ':weight_unit'          => $row['weight_unit'],
            ':measurement'          => $row['measurement'],
            ':measurement_unit'     => $row['measurement_unit'],
            ':age_score'            => $row['age_score'],
            ':ss_text'              => $row['ss_text'],
            ':bcs_text'             => $row['bcs_text'],
            ':presenting_complaint' => $row['presenting_complaint'],
            ':hpc'                  => $row['hpc'],
            ':on_examination'       => $row['on_examination'],
            ':w_temp'               => $row['w_temp'],
            ':w_wind'               => $row['w_wind'],
            ':w_humidity'           => $row['w_humidity'],
            ':w_freetext'           => $row['w_freetext'],
            ':admission_id'         => $admission_id,
            ':patient_id'           => $patient_id,
            ':centre_id'            => $centre_id
        ]);
    }

    $signatureSaved = false;

    if (!empty($section7) && has_signature_payload($section7)) {
        $signature = trim((string)($section7['signature_data'] ?? ''));
        $refused   = trim((string)($section7['no_signature'] ?? ''));

        $stmt = $pdo->prepare("
            SELECT sign_id
            FROM rescue_signatures
            WHERE admission_id = :aid
              AND patient_id   = :pid
            LIMIT 1
        ");
        $stmt->execute([
            ':aid' => $admission_id,
            ':pid' => $patient_id
        ]);
        $existingSignatureId = (int)$stmt->fetchColumn();

        if ($existingSignatureId <= 0) {
            $stmt = $pdo->prepare("
                INSERT INTO rescue_signatures
                    (admission_id, patient_id, user_id, signature_data, refused, signed_at)
                VALUES
                    (:admission_id, :patient_id, :user_id, :signature_data, :refused, NOW())
            ");
            $stmt->execute([
                ':admission_id'   => $admission_id,
                ':patient_id'     => $patient_id,
                ':user_id'        => $user_id,
                ':signature_data' => $refused === '1' ? '' : $signature,
                ':refused'        => boolish_int($refused)
            ]);
            $signatureSaved = true;
        }
    }

    $allMissing = [];
    if (isset($existingMeta['missing_by_section']) && is_array($existingMeta['missing_by_section'])) {
        foreach ($existingMeta['missing_by_section'] as $sectionMissing) {
            if (is_array($sectionMissing) && !empty($sectionMissing)) {
                foreach ($sectionMissing as $item) {
                    $allMissing[] = $item;
                }
            }
        }
    }
    $allMissing = array_values(array_unique($allMissing));

    audit_write(
        $pdo,
        $created ? 'Mobile admission sync create' : 'Mobile admission sync update',
        'admission_sync',
        null,
        [
            'admission_id'      => $admission_id,
            'patient_id'        => $patient_id,
            'mobile_create_key' => $create_key,
            'section2'          => !empty($section2) ? 1 : 0,
            'section3'          => !empty($section3) ? 1 : 0,
            'section4'          => !empty($section4) ? 1 : 0,
            'section5'          => !empty($section5) ? 1 : 0,
            'section6'          => !empty($section6) ? 1 : 0,
            'section7'          => !empty($section7) ? 1 : 0,
            'signature_saved'   => $signatureSaved ? 1 : 0,
            'missing'           => $allMissing
        ]
    );

    respond([
        'success'      => true,
        'message'      => 'Admission synced successfully.',
        'admission_id' => $admission_id,
        'status'       => $row['status'],
        'missing'      => $allMissing,
        'complete'     => empty($allMissing),
        'signature'    => $signatureSaved
    ]);

} catch (Throwable $e) {
    respond([
        'success' => false,
        'error'   => $e->getMessage(),
        'message' => $e->getMessage()
    ], 500);
}
