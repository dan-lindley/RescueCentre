<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

// Capture any accidental output (warnings/notices/echo) so we still return JSON
ob_start();

function respond($arr, $httpCode = 200) {
    // Include any accidental output for debugging
    $noise = trim(ob_get_clean());
    if ($noise !== '') {
        $arr['_noise'] = substr($noise, 0, 500);
    }
    http_response_code($httpCode);
    echo json_encode($arr);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Invalid method'], 405);
}

$gbifId = isset($_POST['gbif_id']) ? trim($_POST['gbif_id']) : '';
$display = isset($_POST['display']) ? trim($_POST['display']) : '';

if ($gbifId === '') {
    respond(['success' => false, 'message' => 'Missing gbif_id'], 400);
}

require_once __DIR__ . '/../connection.php'; // adjust if your DB bootstrap is elsewhere

function curl_json($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: rescue-app/1.0'
        ]
    ]);
    $raw  = curl_exec($ch);
    $err  = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$raw || $http < 200 || $http >= 300) {
        return ['_error' => true, 'http' => $http, 'curl_error' => $err];
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        return ['_error' => true, 'http' => $http, 'curl_error' => 'Bad JSON from GBIF'];
    }

    return $json;
}

// Fetch GBIF usage
$usage = curl_json('https://api.gbif.org/v1/species/' . urlencode($gbifId));
if (isset($usage['_error'])) {
    respond(['success' => false, 'message' => 'GBIF usage lookup failed', 'detail' => $usage], 502);
}

$scientific = $usage['scientificName'] ?? ($usage['canonicalName'] ?? '');
if ($scientific === '') {
    respond(['success' => false, 'message' => 'GBIF response missing scientific name'], 502);
}

// Try to get one vernacular name (optional)
$common = '';
$vern = curl_json('https://api.gbif.org/v1/species/' . urlencode($gbifId) . '/vernacularNames?limit=1');
if (!isset($vern['_error']) && isset($vern['results'][0]['vernacularName'])) {
    $common = (string)$vern['results'][0]['vernacularName'];
}

$speciesName = $display !== '' ? $display : ($common ?: $scientific);


try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT species_id FROM rescue_animal_species WHERE gbif_id = ? LIMIT 1");
    $stmt->execute([$gbifId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $speciesId = (int)$existing['species_id'];
        $u = $pdo->prepare("UPDATE rescue_animal_species SET scientific_name = ?, species_name = ? WHERE species_id = ?");
        $u->execute([$scientific, $speciesName, $speciesId]);
    } else {
        $i = $pdo->prepare("INSERT INTO rescue_animal_species (species_name, scientific_name, gbif_id) VALUES (?, ?, ?)");
        $i->execute([$speciesName, $scientific, $gbifId]);
        $speciesId = (int)$pdo->lastInsertId();
    }

    $pdo->commit();

    respond([
        'success' => true,
        'species_id' => $speciesId,
        'species_name' => $speciesName,
        'type_name' => '',
        'order_name' => ''
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond(['success' => false, 'message' => 'DB error', 'detail' => $e->getMessage()], 500);
}
