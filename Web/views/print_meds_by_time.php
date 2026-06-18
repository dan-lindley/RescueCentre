<?php
// ================= LOAD CONFIG =================
// config.php is one directory above this file
require_once __DIR__ . '/../config.php';

// ================= DATABASE CONNECTION =================

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
    die("Database connection failed: " . $e->getMessage());
}

// ================= GET centre_id FROM URL =================
if (!isset($_GET['centre_id']) || !is_numeric($_GET['centre_id'])) {
    die("❌ Error: centre_id must be provided in the URL. Example: ?centre_id=3");
}

$centre_id = (int) $_GET['centre_id'];


function roundLabel($time) {
    if (!$time) return "Unscheduled";

    $t = (int) str_replace(":", "", substr($time,0,5)); // “08:00” → 800

    return match(true) {
        $t >= 700 && $t < 1000  => "Morning Round",
        $t >= 1000 && $t < 1200 => "Late Morning Round",
        $t >= 1200 && $t < 1400 => "Lunchtime Round",
        $t >= 1400 && $t < 1600 => "Early Afternoon Round",
        $t >= 1600 && $t < 1800 => "Teatime Round",
        $t >= 1800 && $t < 2359 => "Night Time Round",
        default                 => "Unscheduled"
    };
}

$stmt = $pdo->prepare("
    SELECT 
        rp.patient_id,
        rp.name,
        ra.current_location,
        rl.location_area,
        rpr.medication,
        rpr.route,
        rpr.dose,
        rpr.dose_type,
        rpr.frequency,
        rft.time
    FROM rescue_prescriptions rpr
    JOIN rescue_patients rp ON rp.patient_id = rpr.patient_id
    JOIN rescue_admissions ra ON ra.admission_id = rpr.admission_id
    LEFT JOIN rescue_locations rl 
        ON rl.location_name = ra.current_location
       AND rl.centre_id = rp.centre_id
    JOIN rescue_frequency_times rft 
        ON rft.frequency_name = rpr.frequency
    WHERE CURDATE() <= DATE_ADD(rpr.date, INTERVAL rpr.duration DAY)
      AND rp.centre_id = :centre_id
    ORDER BY rl.location_area, rft.time, rp.name
");

$stmt->execute([':centre_id' => $centre_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

function longDateWithSuffix($timestamp = null) {
    $timestamp = $timestamp ? strtotime($timestamp) : time();

    $day   = date('j', $timestamp);
    $month = date('F', $timestamp);
    $year  = date('Y', $timestamp);
    $dow   = date('l', $timestamp);

    // Determine suffix
    if (in_array(($day % 100), [11, 12, 13])) {
        $suffix = "th";
    } else {
        $suffix = match($day % 10) {
            1 => "st",
            2 => "nd",
            3 => "rd",
            default => "th"
        };
    }

    return "$dow {$day}{$suffix} $month $year";
}

$printDate = longDateWithSuffix();

?>


<!DOCTYPE html>
<html>
<head>
<title>Medication Rounds – By Time</title>
<style>
body {
    font-family: Arial, sans-serif;
    font-size: 13px;
    margin: 20px;
}

.page-break {
    page-break-after: always;
}

.print-card {
    border: 1px solid #ccc;
    padding: 15px;
    margin-bottom: 25px;
    border-radius: 8px;
}

.print-header {
    background: #1d4f91;
    color: white;
    padding: 8px 12px;
    font-size: 16px;
    font-weight: bold;
    border-radius: 4px 4px 0 0;
}

.print-subheader {
    background: #eaf1ff;
    padding: 6px 10px;
    font-weight: bold;
    border-left: 4px solid #1d4f91;
    margin-top: 10px;
}

.print-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.print-table th,
.print-table td {
    border: 1px solid #ddd;
    padding: 6px 8px;
}
</style>
</head>
<body>


<?php
// Group by TIME first
$grouped = [];

foreach ($rows as $r) {
    $time = $r['time'];
    $area = $r['location_area'] ?: "Unassigned";

    $grouped[$time][$area][] = $r;
}

foreach ($grouped as $time => $areas):
?>

<div class="print-card">
    <div class="print-subheader">
    <strong><?= htmlspecialchars($time) ?></strong>
    — <?= roundLabel($time) ?>
</div>


    <?php foreach ($areas as $area => $list): ?>
        <div class="print-subheader"><?= htmlspecialchars($area) ?></div>

        <table class="print-table">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Location</th>
                    <th>Medication</th>
                    <th>Route</th>
                    <th>Dose</th>
                    <th>Frequency</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($list as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['current_location']) ?></td>
                    <td><?= htmlspecialchars($row['medication']) ?></td>
                    <td><?= htmlspecialchars($row['route']) ?></td>
                    <td><?= htmlspecialchars($row['dose'] . " " . $row['dose_type']) ?></td>
                    <td><?= htmlspecialchars($row['frequency']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php endforeach; ?>

</div>

<div class="page-break"></div>

<?php endforeach; ?>

</body>
</html>
<script>
window.onload = function () {
    window.print();
};
</script>

