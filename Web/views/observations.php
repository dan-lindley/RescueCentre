<?php
$obsStmt = $pdo->prepare("
    SELECT o.*,
           a.first_name,
           a.last_name
    FROM rescue_observations o
    LEFT JOIN accounts a ON a.id = o.user_id
    WHERE o.patient_id = :patient_id
    ORDER BY o.obs_date DESC
");
$obsStmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$obsStmt->execute();
$observations = $obsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (empty($observations)): ?>

    <!-- ==========================================================
         NO OBSERVATIONS ALERT
    ========================================================== -->
    <div class="rc-alert blue">
        <strong>Observations</strong><br>
        No observations recorded for this patient.
    </div>

<?php else: ?>

    <?php foreach ($observations as $o): ?>

        <?php
            $formatted = (new DateTime($o["obs_date"]))->format('d-m-Y H:i');

            $shortNotes = strlen($o["obs_notes"]) > 60
                ? substr($o["obs_notes"], 0, 60) . "…"
                : $o["obs_notes"];

            $addedBy = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? ''));
            if ($addedBy === '') $addedBy = "Unknown";

            $wra_score =
                $o["obs_severity_score"] +
                $o["obs_bcs_score"] +
                $o["obs_age_score"];

            // Determine alert colour (existing logic preserved)
            if ($wra_score > 99) {
                $alertClass = "grey";
                $wra_label  = "Not applicable";
            } elseif ($wra_score >= 7) {
                $alertClass = "red";
                $wra_label  = "Critical";
            } elseif ($wra_score >= 4) {
                $alertClass = "amber";
                $wra_label  = "Moderate concern";
            } else {
                $alertClass = "green";
                $wra_label  = "Low concern";
            }
        ?>

        <!-- ==========================================================
             OBSERVATION ALERT SUMMARY
        ========================================================== -->
        <div class="rc-alert <?= $alertClass ?>">
            <strong>Observation</strong> - <?= $formatted ?><br>
            WRA Score: <strong><?= $wra_score ?></strong>
            — <?= htmlspecialchars($wra_label) ?><br>
            Added by <?= htmlspecialchars($addedBy) ?>
        </div>

        <!-- ==========================================================
             OBSERVATION SCORE TABLE
        ========================================================== -->
        <table class="rc-table row-hover">
            <thead>
                <tr>
                    <th class="align-middle">Age</th>
                    <th class="align-middle">Injury Severity</th>
                    <th class="align-middle">Body Condition</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= $o["obs_age_text"] ?> (<?= $o["obs_age_score"] ?>)</td>
                    <td><?= $o["obs_severity_text"] ?> (<?= $o["obs_severity_score"] ?>)</td>
                    <td><?= $o["obs_bcs_text"] ?> (<?= $o["obs_bcs_score"] ?>)</td>
                </tr>
            </tbody>
        </table>

        <!-- ==========================================================
             OBSERVATION NOTES
        ========================================================== -->
        <div class="rc-card rc-card-muted">
            <p><strong>Notes:</strong></p>
            <p><?= nl2br(htmlspecialchars($o["obs_notes"])) ?></p>
        </div>

    <?php endforeach; ?>

<?php endif; ?>

<div class="rc-card rc-card-muted">
<?php include __DIR__ . '/../controllers/add_observation.php'; ?>
        </div>
