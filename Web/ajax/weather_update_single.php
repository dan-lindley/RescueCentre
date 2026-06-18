<?php
// weather_update_single.php
require_once('../connect_to_mysql.php');

header('Content-Type: application/json; charset=utf-8');

if (empty($_POST['admission_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing admission_id']);
    exit;
}

$admission_id = (int)$_POST['admission_id'];

// Fetch admission details
$sql = "
    SELECT admission_id, admission_date, location_lat, location_long
    FROM rescue_admissions
    WHERE admission_id = :id
    LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $admission_id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Admission not found']);
    exit;
}

$lat = $row['location_lat'];
$lon = $row['location_long'];
$dt  = $row['admission_date'];

if ($lat === null || $lon === null || $lat === '' || $lon === '') {
    echo json_encode(['success' => false, 'message' => 'Missing coordinates']);
    exit;
}

$dateOnly = date('Y-m-d', strtotime($dt));
$hour     = date('H', strtotime($dt));

// Build Open-Meteo archive API URL
$url =
    "https://archive-api.open-meteo.com/v1/archive" .
    "?latitude={$lat}" .
    "&longitude={$lon}" .
    "&start_date={$dateOnly}" .
    "&end_date={$dateOnly}" .
    "&hourly=temperature_2m,relative_humidity_2m,windspeed_10m,precipitation";

$json = @file_get_contents($url);
if (!$json) {
    echo json_encode(['success' => false, 'message' => 'Weather API request failed']);
    exit;
}

$data = json_decode($json, true);

if (!isset($data['hourly'])) {
    echo json_encode(['success' => false, 'message' => 'No weather data returned']);
    exit;
}

$temps   = $data['hourly']['temperature_2m'] ?? [];
$humids  = $data['hourly']['relative_humidity_2m'] ?? [];
$winds   = $data['hourly']['windspeed_10m'] ?? [];
$rains   = $data['hourly']['precipitation'] ?? [];
$times   = $data['hourly']['time'] ?? [];

if (empty($times)) {
    echo json_encode(['success' => false, 'message' => 'No hourly time data']);
    exit;
}

// Find index for admission hour
$target = $dateOnly . "T{$hour}:00";
$idx = array_search($target, $times);
if ($idx === false) {
    // Fallback to first hour
    $idx = 0;
}

// Extract values
$temp_c   = isset($temps[$idx]) ? $temps[$idx] : null;
$hum_pct  = isset($humids[$idx]) ? $humids[$idx] : null;
$wind_ms  = isset($winds[$idx]) ? $winds[$idx] : null;
$rain_mm  = isset($rains[$idx]) ? $rains[$idx] : null;

// Convert wind m/s → mph
$wind_mph = $wind_ms !== null ? round($wind_ms * 2.23694, 1) : null;

// Update record (overwrite weather fields for this record)
$update = $conn->prepare("
    UPDATE rescue_admissions
    SET w_temp = :t, w_humidity = :h, w_wind = :w, w_rainfall = :r
    WHERE admission_id = :id
");
$update->execute([
    ':t' => $temp_c,
    ':h' => $hum_pct,
    ':w' => $wind_mph,
    ':r' => $rain_mm,
    ':id' => $admission_id
]);

echo json_encode([
    'success'     => true,
    'admission_id'=> $admission_id,
    'w_temp'      => $temp_c,
    'w_humidity'  => $hum_pct,
    'w_wind'      => $wind_mph,
    'w_rainfall'  => $rain_mm
]);
