<?php
// controllers/admissions/location_fix.php
ob_start();
header('Content-Type: application/json; charset=utf-8');

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Fatal error', 'detail' => $err['message']]);
    }
});

function rc_json_fail(int $code, string $msg, $detail = null): void {
    http_response_code($code);
    $out = ['ok' => false, 'error' => $msg];
    if ($detail !== null) $out['detail'] = $detail;
    echo json_encode($out);
    exit;
}

// ---- Bootstrap include (robust) ----
// Only this file is being updated; we try a few common relative paths.
$bootCandidates = [
    __DIR__ . '/../../main.php',
    __DIR__ . '/../../config.php',
    __DIR__ . '/../../init.php',
    __DIR__ . '/../../data.php',
];
$booted = false;
foreach ($bootCandidates as $p) {
    if (is_file($p)) {
        require_once $p;
        $booted = true;
        break;
    }
}
if (!$booted) {
    rc_json_fail(500, 'Bootstrap not found', 'Could not locate main.php/config.php/init.php/data.php relative to controller.');
}

// Expect $pdo from bootstrap
if (!isset($pdo) || !($pdo instanceof PDO)) {
    rc_json_fail(500, 'Database not available', 'Missing $pdo after bootstrap.');
}

// Auth/centre context
session_start();
$centre_id = $_SESSION['centre_id'] ?? null;
if (!$centre_id) {
    rc_json_fail(403, 'Not authorised');
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    rc_json_fail(400, 'Invalid JSON');
}

$action = $payload['action'] ?? '';
$patient_id = isset($payload['patient_id']) ? (int)$payload['patient_id'] : 0;
if ($patient_id <= 0) {
    rc_json_fail(400, 'Missing patient_id');
}

function rc_update_latest_admission_coords(PDO $pdo, int $centre_id, int $patient_id, string $lat, string $lng): int {
    // Updates the most recent admission row for that patient in this centre.
    $sql = "
        UPDATE rescue_admissions
        SET location_lat = :lat,
            location_long = :lng
        WHERE centre_id = :centre_id
          AND patient_id = :patient_id
        ORDER BY admission_date DESC
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':lat' => $lat,
        ':lng' => $lng,
        ':centre_id' => $centre_id,
        ':patient_id' => $patient_id
    ]);
    return $stmt->rowCount();
}

function rc_geocode_address_nominatim(string $address): ?array {
    $searchAddress = $address;
    $county = trim((string)($_SESSION['county'] ?? ''));
    if ($county !== '' && stripos($searchAddress, $county) === false) {
        $searchAddress .= ', ' . $county;
    }

    $params = [
        'q' => $searchAddress,
        'format' => 'json',
        'addressdetails' => 1,
        'limit' => 1,
    ];

    $countryCode = trim((string)($_SESSION['country_code'] ?? ''));
    if ($countryCode !== '') {
        $params['countrycodes'] = strtolower($countryCode);
    }

    $q = http_build_query($params);
    $url = "https://nominatim.openstreetmap.org/search?$q";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_USERAGENT => 'RescueCentreLocationFix/1.0 (admin tool)'
    ]);
    $out = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($out === false || $http !== 200) return null;

    $json = json_decode($out, true);
    if (!is_array($json) || empty($json[0]['lat']) || empty($json[0]['lon'])) return null;

    return [
        'lat' => (string)$json[0]['lat'],
        'lng' => (string)$json[0]['lon']
    ];
}

try {
    if ($action === 'update_coords') {
        $lat = $payload['lat'] ?? null;
        $lng = $payload['lng'] ?? null;

        if ($lat === null || $lng === null) rc_json_fail(400, 'Missing lat/lng');

        $lat = (string)$lat;
        $lng = (string)$lng;

        // basic sanity
        if (!is_numeric($lat) || !is_numeric($lng)) rc_json_fail(400, 'Invalid lat/lng');

        $rows = rc_update_latest_admission_coords($pdo, (int)$centre_id, $patient_id, $lat, $lng);

        echo json_encode(['ok' => true, 'rows' => $rows, 'lat' => $lat, 'lng' => $lng]);
        exit;
    }

    if ($action === 'geocode_and_update') {
        $address = trim((string)($payload['address'] ?? ''));
        if ($address === '') rc_json_fail(400, 'Missing address');

        // If you have an internal geocoder already, swap this function call only.
        $coords = rc_geocode_address_nominatim($address);
        if (!$coords) rc_json_fail(422, 'Could not geocode address');

        $rows = rc_update_latest_admission_coords($pdo, (int)$centre_id, $patient_id, $coords['lat'], $coords['lng']);

        echo json_encode(['ok' => true, 'rows' => $rows, 'lat' => $coords['lat'], 'lng' => $coords['lng']]);
        exit;
    }

    rc_json_fail(400, 'Unknown action');

} catch (Throwable $e) {
    rc_json_fail(500, 'Server error', $e->getMessage());
}
