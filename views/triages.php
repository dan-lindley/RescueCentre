<?php
// ------------------------------------------------------------
// Get triage information from the admission form
// ------------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM rescue_admissions WHERE patient_id=:patient_id");
$stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $tr_ss = $row["severity_score"];
    $tr_ss_text = $row["ss_text"];
    $tr_bc = $row["bc_score"];
    $tr_bcs_text = $row["bcs_text"];
    $tr_age = $row["age_score"];
    $tr_age_text = $row["age_on_admission"];
    $tr_hpc = $row["hpc"];
    $tr_oe = $row["on_examination"];

    $wra_score = ($tr_ss + $tr_bc) + $tr_age;

    // --------------------------------------------------------
    // TRAFFIC LIGHT SYSTEM FOR WRA SCORE (EXISTING LOGIC)
    // --------------------------------------------------------
    if ($wra_score > 90) {
        $wraclass = '';
        $wra_score = "N/A";
        $alertClass = 'blue';
        $wra_label = 'Not applicable';
    } elseif ($wra_score >= 7) {
        $wraclass = 'table-danger';
        $alertClass = 'red';
        $wra_label = 'Critical';
    } elseif ($wra_score >= 4) {
        $wraclass = 'table-warning';
        $alertClass = 'amber';
        $wra_label = 'Moderate concern';
    } else {
        $wraclass = 'table-success';
        $alertClass = 'green';
        $wra_label = 'Low concern';
    }
}
?>

<!-- ==========================================================
     WRA ALERT SUMMARY (MATCHES PRESCRIPTIONS / PATIENT ALERT)
========================================================== -->
<div class="rc-alert <?= $alertClass ?>">
    <strong>Wildlife Rapid Assessment</strong><br>
    Score: <strong><?= htmlspecialchars($wra_score) ?></strong>
    — <?= htmlspecialchars($wra_label) ?>
</div>

<!-- ==========================================================
     WRA SCORE TABLE
========================================================== -->

    <table class="rc-table row-hover" id="wrascore">
        <thead>
            <tr>
                <th class="align-middle">Age</th>
                <th class="align-middle">Injury Severity</th>
                <th class="align-middle">Body Condition</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= $tr_age_text ?> (Score: <?= $tr_age ?>)</td>
                <td><?= $tr_ss_text ?> (Score: <?= $tr_ss ?>)</td>
                <td><?= $tr_bcs_text ?> (Score: <?= $tr_bc ?>)</td>
            </tr>
        </tbody>
    </table>


<!-- ==========================================================
     HISTORY OF PRESENTING COMPLAINT
========================================================== -->
<div class="rc-card rc-card-muted">
    <p><strong>History of Presenting Complaint:</strong></p>
    <p><?= nl2br(htmlspecialchars((string)$tr_hpc)) ?></p>
</div>
<!-- ==========================================================
     ON EXAMINATION
========================================================== -->
<div class="rc-card rc-card-muted">
    <p><strong>On Examination:</strong></p>
    <p><?= nl2br(htmlspecialchars((string)$tr_oe)) ?></p>
</div>


