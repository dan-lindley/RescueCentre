<?php

// ========================================================
// SPECIES CRON – BUILD OFFLINE DATASET
// ========================================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . db_host . ";dbname=" . db_name . ";charset=" . db_charset,
        db_user,
        db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

// ========================================================
// CONFIG
// ========================================================

$OUTPUT_DIR  = __DIR__ . '/../m/data/';
$LATEST_FILE = $OUTPUT_DIR . 'species_data.json';
$MAX_FILES   = 5;
$TOP_LIMIT   = 30;

// Ensure directory exists
if (!is_dir($OUTPUT_DIR)) {
    if (!mkdir($OUTPUT_DIR, 0755, true) && !is_dir($OUTPUT_DIR)) {
        die("Failed to create output directory: " . $OUTPUT_DIR);
    }
}

// ========================================================
// 1. GET USAGE COUNTS (ADMISSIONS)
// ========================================================

$usageMap = [];

try {
    $sql = "
        SELECT animal_species, COUNT(*) AS usage_count
        FROM rescue_patients
        WHERE animal_species IS NOT NULL AND animal_species != ''
        GROUP BY animal_species
    ";

    foreach ($pdo->query($sql) as $row) {
        $usageMap[$row['animal_species']] = (int)$row['usage_count'];
    }
} catch (Exception $e) {
    // fail safe: continue with empty usage
}

// ========================================================
// 2. GET FULL SPECIES DATA
// ========================================================

$sql = "
    SELECT 
        s.*,
        t.type_name,
        t.animal_order AS order_name
    FROM rescue_animal_species s
    LEFT JOIN rescue_animal_types t
        ON t.type_name = s.animal_type
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();

// ========================================================
// 3. BUILD DATASET
// ========================================================

$speciesList = [];

foreach ($rows as $r) {

    $speciesName = $r['species_name'];

    // --- Build species_display EXACTLY like API ---
    $display = $speciesName;
    if (!empty($r['scientific_name'])) {
        $display .= " (" . $r['scientific_name'] . ")";
    }

    // --- Usage count ---
    $usage = $usageMap[$speciesName] ?? 0;

    $speciesList[] = [

        // REQUIRED (DO NOT CHANGE)
        'species_display' => $display,
        'species_name'    => $speciesName,
        'type_name'       => $r['type_name'] ?? '',
        'order_name'      => $r['order_name'] ?? '',

        // SAFE EXTENSIONS
        'species_id'      => (int)$r['species_id'],
        'scientific_name' => $r['scientific_name'] ?? '',
        'animal_type'     => $r['animal_type'] ?? '',
        'reference'       => $r['reference'] ?? '',
        'iucn_status'     => $r['iucn_status'] ?? '',

        // WEIGHT
        'weight_from' => $r['weight_from'] ?? null,
        'weight_to'   => $r['weight_to'] ?? null,
        'weight_unit' => $r['weight_unit'] ?? '',

        // MEASUREMENTS
        'measurement_from' => $r['measurement_from'] ?? null,
        'measurement_to'   => $r['measurement_to'] ?? null,
        'measurement_unit' => $r['measurement_unit'] ?? '',
        'species_measurement_standard' => $r['species_measurement_standard'] ?? '',

        // NEW
        'usage_count' => $usage
    ];
}

// ========================================================
// 4. SORT BY USAGE (DESC)
// ========================================================

usort($speciesList, function ($a, $b) {
    return $b['usage_count'] <=> $a['usage_count'];
});

// ========================================================
// 5. EXTRACT TOP SPECIES
// ========================================================

$topSpecies = array_slice($speciesList, 0, $TOP_LIMIT);

// ========================================================
// 6. BUILD FINAL JSON STRUCTURE
// ========================================================

$data = [
    'meta' => [
        'version'   => 1,
        'timestamp' => date('c')
    ],
    'top_species' => $topSpecies,
    'all_species' => $speciesList
];

// ========================================================
// 7. GENERATE HASH (DETERMINISTIC)
// ========================================================

$hash = md5(json_encode($data['all_species']));
$data['meta']['hash'] = $hash;

// ========================================================
// 8. SKIP IF NO CHANGE
// ========================================================

if (file_exists($LATEST_FILE)) {
    $existingRaw = file_get_contents($LATEST_FILE);
    $existing = json_decode($existingRaw, true);

    if (!empty($existing['meta']['hash']) && $existing['meta']['hash'] === $hash) {
        echo "No changes detected. Skipping rebuild.\n";
        exit;
    }
}

// ========================================================
// 9. PREPARE JSON OUTPUT
// ========================================================

$jsonOut = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if ($jsonOut === false) {
    die("Failed to encode JSON output.\n");
}

// ========================================================
// 10. WRITE NEW VERSION FILE
// ========================================================

$timestamp = date('Ymd_His');
$versionFile = $OUTPUT_DIR . "species_data_{$timestamp}.json";

if (file_put_contents($versionFile, $jsonOut, LOCK_EX) === false) {
    die("Failed to write versioned file: {$versionFile}\n");
}

@chmod($versionFile, 0644);

// ========================================================
// 11. UPDATE LATEST FILE USED BY THE APP
// ========================================================

$tmpFile = $LATEST_FILE . '.tmp';

if (file_put_contents($tmpFile, $jsonOut, LOCK_EX) === false) {
    die("Failed to write temp latest file: {$tmpFile}\n");
}

@chmod($tmpFile, 0644);

$published = false;

// Preferred: atomic replace
if (@rename($tmpFile, $LATEST_FILE)) {
    $published = true;
} else {
    // Fallback for hosts where rename over an existing file fails
    if (file_exists($LATEST_FILE) && !is_writable($LATEST_FILE)) {
        @chmod($LATEST_FILE, 0644);
    }

    if (file_put_contents($LATEST_FILE, $jsonOut, LOCK_EX) !== false) {
        $published = true;
        @unlink($tmpFile);
    }
}

if (!$published) {
    $err = error_get_last();
    die("Failed to publish latest file {$LATEST_FILE}" . ($err ? ' :: ' . $err['message'] : '') . "\n");
}

@chmod($LATEST_FILE, 0644);
clearstatcache(true, $LATEST_FILE);

// ========================================================
// 12. ROTATE OLD FILES (KEEP 5)
// ========================================================

$files = glob($OUTPUT_DIR . 'species_data_*.json');

usort($files, function ($a, $b) {
    return filemtime($b) <=> filemtime($a);
});

if (count($files) > $MAX_FILES) {
    $toDelete = array_slice($files, $MAX_FILES);
    foreach ($toDelete as $f) {
        @unlink($f);
    }
}

// ========================================================
// DONE
// ========================================================
echo "Script: " . __FILE__ . "\n";
echo "Version file: " . $versionFile . "\n";
echo "Latest file: " . $LATEST_FILE . "\n";
echo "MD5 jsonOut: " . md5($jsonOut) . "\n";
echo "MD5 version file: " . md5_file($versionFile) . "\n";
echo "MD5 latest file: " . md5_file($LATEST_FILE) . "\n";
echo "Species dataset rebuilt successfully.\n";
echo "Latest file updated: {$LATEST_FILE}\n";