<!-- ==========================================================
     TREATMENTS GIVEN
========================================================== -->

<?php
// gets the treatments from the table to display 
$stmt = $pdo->prepare("
    SELECT *
    FROM rescue_treatments
    WHERE patient_id = :patient_id
    ORDER BY date DESC
    LIMIT 10
");
$stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$stmt->execute();
$treatments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (empty($treatments)): ?>

    <!-- EMPTY STATE -->
    <div class="rc-alert grey">
        <strong>Treatments</strong><br>
        No treatments recorded for this patient.
    </div>

<?php else: ?>

    <?php foreach ($treatments as $row): ?>

        <?php
            $date = $row["date"];
            $treatment = $row["treatment"];
            $treatment_free_text = $row["treatment_free_text"];
            $done_by = $row["done_by"];
        ?>

        <!-- ==========================================================
             TREATMENT ALERT
        ========================================================== -->
        <div class="rc-card rc-card-muted">

            <strong><?= htmlspecialchars($treatment) ?></strong><br>

            <span class="rc-muted">
                <?= htmlspecialchars($date) ?> —
                given by <strong><?= htmlspecialchars($done_by) ?></strong>
            </span>

            <?php if (!empty($treatment_free_text)): ?>
                <br><br>
                <u><b>Notes:</b></u><br>
                <?= nl2br(htmlspecialchars($treatment_free_text)) ?>
            <?php endif; ?>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

<!-- ==========================================================
     ADD TREATMENT FORM
========================================================== -->
<div class="rc-card rc-card-muted">
    <?php include 'controllers/add_treatment.php'; ?>
</div>
