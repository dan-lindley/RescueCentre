<?php
session_start();

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../operations/audit.php';
header('Content-Type: application/json');

function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

try {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        respond([
            'success' => false,
            'error'   => 'Invalid JSON payload'
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
            'error'   => 'Database connection failed.'
        ], 500);
    }

    $user_id = $_SESSION['account_id'] ?? null;
    $centre_id = $_SESSION['centre_id'] ?? null;
    $GLOBALS['user_id'] = $user_id;
    $GLOBALS['centre_id'] = $centre_id;
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
            // leave $centre_id as null
        }
    }

    $centre_id = (int)$centre_id;
    $create_key = trim((string)($data['create_key'] ?? ''));

    if ($centre_id <= 0) {
        respond([
            'success' => false,
            'error'   => 'Missing centre_id'
        ], 400);
    }

    if ($create_key === '') {
        respond([
            'success' => false,
            'error'   => 'Missing create_key'
        ], 400);
    }

    $name = trim((string)($data['name'] ?? ''));
    $sex = trim((string)($data['sex'] ?? ''));
    $approx_dob = trim((string)($data['approx_dob'] ?? ''));
    $animal_species = trim((string)($data['animal_species'] ?? ''));
    $animal_type = trim((string)($data['animal_type'] ?? ''));
    $animal_order = trim((string)($data['animal_order'] ?? ''));
    $ringed = trim((string)($data['ringed'] ?? 'No'));
    $ring_number = trim((string)($data['ring_number'] ?? ''));
    $microchipped = trim((string)($data['microchipped'] ?? 'No'));
    $microchip_number = trim((string)($data['microchip_number'] ?? ''));

    if ($sex === '' || $animal_species === '') {
        respond([
            'success' => false,
            'error'   => 'Missing required patient fields'
        ], 400);
    }

    if ($approx_dob === '') {
        $approx_dob = null;
    }

    $stmt = $pdo->prepare("
        SELECT patient_id
        FROM rescue_patients
        WHERE mobile_create_key = :create_key
        LIMIT 1
    ");
    $stmt->execute([
        ':create_key' => $create_key
    ]);
    $existing = $stmt->fetch();

    if ($existing && !empty($existing['patient_id'])) {
        respond([
            'success'    => true,
            'patient_id' => (int)$existing['patient_id'],
            'existing'   => true
        ]);
    }

    $stmt = $pdo->prepare("
        INSERT INTO rescue_patients
        (
            centre_id,
            mobile_create_key,
            name,
            sex,
            approx_dob,
            animal_species,
            animal_type,
            animal_order,
            ringed,
            ring_number,
            microchipped,
            microchip_number,
            state
        )
        VALUES
        (
            :centre_id,
            :create_key,
            :name,
            :sex,
            :approx_dob,
            :animal_species,
            :animal_type,
            :animal_order,
            :ringed,
            :ring_number,
            :microchipped,
            :microchip_number,
            'To Admit'
        )
    ");

    $stmt->execute([
        ':centre_id'        => $centre_id,
        ':create_key'       => $create_key,
        ':name'             => $name,
        ':sex'              => $sex,
        ':approx_dob'       => $approx_dob,
        ':animal_species'   => $animal_species,
        ':animal_type'      => $animal_type,
        ':animal_order'     => $animal_order,
        ':ringed'           => $ringed !== '' ? $ringed : 'No',
        ':ring_number'      => $ring_number,
        ':microchipped'     => $microchipped !== '' ? $microchipped : 'No',
        ':microchip_number' => $microchip_number
    ]);

   $patient_id = (int)$pdo->lastInsertId();

   audit_write(
    $pdo,
    'Mobile patient sync',
    'patient_sync',
    null,
    [
        'patient_id'        => $patient_id,
        'mobile_create_key' => $create_key,
        'name'              => $name,
        'sex'               => $sex,
        'approx_dob'        => $approx_dob,
        'animal_species'    => $animal_species,
        'animal_type'       => $animal_type,
        'animal_order'      => $animal_order,
        'ringed'            => $ringed,
        'ring_number'       => $ring_number,
        'microchipped'      => $microchipped,
        'microchip_number'  => $microchip_number
    ]
);

respond([
    'success'    => true,
    'patient_id' => $patient_id,
    'message'    => 'Patient created',
    'display_id' => $patient_id
]);

} catch (Throwable $e) {
    respond([
        'success' => false,
        'error'   => $e->getMessage()
    ], 500);
}