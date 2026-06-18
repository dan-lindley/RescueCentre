<?php
require_once('../connect_to_mysql.php');

// Fetch 10 records needing geocode
$sql = "
    SELECT admission_id, collection_location
    FROM rescue_admissions
    WHERE 
        (location_lat IS NULL OR location_lat = '' OR location_lat = 0)
        AND (collection_location IS NOT NULL AND collection_location != '')
    LIMIT 30
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count remaining
$count_sql = "
    SELECT COUNT(*) 
    FROM rescue_admissions
    WHERE 
        (location_lat IS NULL OR location_lat = '' OR location_lat = 0)
        AND (collection_location IS NOT NULL AND collection_location != '')
";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute();
$remaining = $count_stmt->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
<title>Geocode Admissions</title>
<style>
table { border-collapse: collapse; width: 100%; margin-top: 20px; }
td, th { padding: 10px; border: 1px solid #ccc; }
button { padding: 8px 16px; }
</style>
</head>
<body>

<h2>Geocode Admissions</h2>

<!-- Search for a specific admission -->
<form method="get" action="geocode_all.php" style="margin-bottom:20px;">
    <label><strong>Search by Patient ID:</strong></label>
    <input type="number" name="search_pid" placeholder="Enter patient_id" required>
    <button type="submit">Search</button>
</form>
<?php
// If a patient_id search was submitted:
if (!empty($_GET['search_pid'])) {

    $pid = intval($_GET['search_pid']);

    // Find the matching admission record
    $sql = "
        SELECT admission_id 
        FROM rescue_admissions 
        WHERE patient_id = :pid 
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':pid', $pid, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // Redirect to fix page
        $aid = $result['admission_id'];
        header("Location: geocode_fix.php?id=".$aid);
        exit;
    } else {
        echo "<p style='color:red;'><strong>No admission found for patient_id {$pid}</strong></p>";
    }
}
?>

<p><strong>Remaining:</strong> <?= $remaining ?></p>

<table>
<tr>
    <th>ID</th>
    <th>Collection Location</th>
    <th>Action</th>
</tr>

<?php foreach ($rows as $r): ?>
<tr>
    <td><?= $r['admission_id'] ?></td>
    <td><?= htmlspecialchars($r['collection_location']) ?></td>
    <td>
        <a href="geocode_fix.php?id=<?= $r['admission_id'] ?>">
            <button>Fix</button>
        </a>
    </td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>
