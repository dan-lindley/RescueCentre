<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/dashmain.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    exit('Database connection ($pdo) is not available in qr.php');
}

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$location_id = isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0;
$area_id = isset($_GET['area_id']) ? (int)$_GET['area_id'] : 0;
$zone_id = isset($_GET['zone_id']) ? (int)$_GET['zone_id'] : 0;

$mode = '';
$details = [];
$qrTargetUrl = '';

if ($patient_id > 0) {
    $stmt = $pdo->prepare("
        SELECT patient_id, name, animal_type, animal_species, sex, status, date_added, microchip_number
        FROM rescue_patients
        WHERE patient_id = ?
        LIMIT 1
    ");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        exit('No patient found for patient_id=' . $patient_id);
    }

    $mode = 'Patient';
    $qrTargetUrl = 'https://myrescuecentre.com/viewpatient.php?patient_id=' . (int)$patient['patient_id'];
    $details = [
        'CRN' => (string)(int)$patient['patient_id'],
        'Name' => trim((string)($patient['name'] ?? '')),
        'Type' => trim((string)($patient['animal_type'] ?? '')),
        'Species' => trim((string)($patient['animal_species'] ?? '')),
        'Sex' => trim((string)($patient['sex'] ?? '')),
        'Status' => trim((string)($patient['status'] ?? '')),
        'Date added' => trim((string)($patient['date_added'] ?? '')),
    ];

    $microchip = trim((string)($patient['microchip_number'] ?? ''));
    if ($microchip !== '') {
        $details['Microchip'] = $microchip;
    }
} elseif ($location_id > 0) {
    $stmt = $pdo->prepare("
        SELECT location_id, location_name, location_type, location_area
        FROM rescue_locations
        WHERE location_id = ?
        LIMIT 1
    ");
    $stmt->execute([$location_id]);
    $location = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$location) {
        exit('No location found for location_id=' . $location_id);
    }

    $mode = 'Location';
    $qrTargetUrl = 'https://myrescuecentre.com/patients.php?location=location-' . (int)$location['location_id'];
    $details = [
        'Location ID' => (string)(int)$location['location_id'],
        'Name' => trim((string)($location['location_name'] ?? '')),
        'Type' => trim((string)($location['location_type'] ?? '')),
        'Area' => trim((string)($location['location_area'] ?? '')),
    ];
} elseif ($area_id > 0) {
    $stmt = $pdo->prepare("
        SELECT a.area_id, a.area_name, z.zone_name
        FROM rescue_areas a
        LEFT JOIN rescue_zones z
            ON z.zone_id = a.zone_id
           AND z.centre_id = a.centre_id
        WHERE a.area_id = ?
        LIMIT 1
    ");
    $stmt->execute([$area_id]);
    $area = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$area) {
        exit('No area found for area_id=' . $area_id);
    }

    $mode = 'Area';
    $qrTargetUrl = 'https://myrescuecentre.com/patients.php?area=' . (int)$area['area_id'];
    $details = [
        'Area ID' => (string)(int)$area['area_id'],
        'Name' => trim((string)($area['area_name'] ?? '')),
        'Zone' => trim((string)($area['zone_name'] ?? '')),
    ];
} elseif ($zone_id > 0) {
    $stmt = $pdo->prepare("
        SELECT zone_id, zone_name, is_active
        FROM rescue_zones
        WHERE zone_id = ?
        LIMIT 1
    ");
    $stmt->execute([$zone_id]);
    $zone = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$zone) {
        exit('No zone found for zone_id=' . $zone_id);
    }

    $mode = 'Zone';
    $qrTargetUrl = 'https://myrescuecentre.com/patients.php?zone=' . (int)$zone['zone_id'];
    $details = [
        'Zone ID' => (string)(int)$zone['zone_id'],
        'Name' => trim((string)($zone['zone_name'] ?? '')),
        'Active' => ((int)($zone['is_active'] ?? 0) === 1 ? 'Yes' : 'No'),
    ];
} else {
    exit('Invalid or missing QR target');
}

$qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&format=png&data=' . urlencode($qrTargetUrl);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($mode); ?> QR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{ margin:0; padding:24px; font-family:Arial, Helvetica, sans-serif; background:#f5f7fa; color:#1f2933; }
        .toolbar{ max-width:960px; margin:0 auto 16px; display:flex; gap:10px; justify-content:flex-end; }
        .toolbar button{ padding:10px 14px; border:1px solid #d7dee7; border-radius:10px; background:#fff; cursor:pointer; }
        .card{ max-width:960px; margin:0 auto; background:#fff; border:1px solid #d7dee7; border-radius:18px; padding:22px; }
        .grid{ display:grid; grid-template-columns:320px 1fr; gap:24px; align-items:start; }
        .qrbox{ border:1px solid #d7dee7; border-radius:14px; padding:12px; text-align:center; background:#fff; }
        .qrbox img{ max-width:100%; height:auto; display:block; margin:0 auto; }
        .row{ display:grid; grid-template-columns:160px 1fr; gap:12px; padding:10px 0; border-bottom:1px solid #eef2f6; }
        .label{ font-size:12px; font-weight:700; text-transform:uppercase; color:#6b7785; }
        .value{ font-size:18px; }
        .url{ margin-top:16px; font-size:12px; color:#6b7785; word-break:break-all; }
        @media (max-width: 800px){ .grid{ grid-template-columns:1fr; } .row{ grid-template-columns:1fr; gap:4px; } }
        @media print{ .toolbar{ display:none; } body{ background:#fff; padding:0; } .card{ border:0; box-shadow:none; } }
    </style>
</head>
<body>

<div class="toolbar">
    <button onclick="window.print()">Print</button>
    <button onclick="window.close()">Close</button>
</div>

<div class="card">
    <div class="grid">
        <div class="qrbox">
            <img src="<?php echo htmlspecialchars($qrImageUrl); ?>" alt="<?php echo htmlspecialchars($mode); ?> QR">
        </div>

        <div>
            <?php foreach ($details as $label => $value): ?>
                <div class="row">
                    <div class="label"><?php echo htmlspecialchars((string)$label); ?></div>
                    <div class="value"><?php echo htmlspecialchars($value !== '' ? (string)$value : '-'); ?></div>
                </div>
            <?php endforeach; ?>

            <div class="url"><?php echo htmlspecialchars($qrTargetUrl); ?></div>
        </div>
    </div>
</div>

</body>
</html>
