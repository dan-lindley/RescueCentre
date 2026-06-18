<?php
// ------------------------------------------------------------
// views/prescriptions.php
// ------------------------------------------------------------
include __DIR__ . '/../controllers/meds_dependencies.php';

// meds_simple.php EXPECTS these
$patient_name = $patient_name ?? '';
$medications  = $medications  ?? [];
$stock_items  = $stock_items  ?? [];

// ------------------------------------------------------------
// ACTIVE PRESCRIPTIONS (UNCHANGED FROM meds_stock.php)
// ------------------------------------------------------------

$prescriptionSoftDeleteFilter = '';
try {
    $colStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'rescue_prescriptions'
          AND COLUMN_NAME = 'is_deleted'
    ");
    $colStmt->execute();
    if ((int)$colStmt->fetchColumn() > 0) {
        $prescriptionSoftDeleteFilter = " AND COALESCE(is_deleted, 0) = 0";
    }
} catch (Throwable $e) {
    $prescriptionSoftDeleteFilter = '';
}

$stmt = $pdo->prepare("
    SELECT
        medication,
        route,
        dose,
        dose_type,
        by_weight
    FROM rescue_prescriptions
    WHERE patient_id = :pid
      AND DATE_ADD(date, INTERVAL duration DAY) >= CURDATE()
      {$prescriptionSoftDeleteFilter}
");
$stmt->execute([':pid' => $patient_id]);
$active_prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$wStmt = $pdo->prepare("
    SELECT weight, weight_unit, date
    FROM rescue_weights
    WHERE patient_id = :pid
    ORDER BY date DESC
    LIMIT 1
");
$wStmt->execute([':pid' => $patient_id]);
$last_weight = $wStmt->fetch(PDO::FETCH_ASSOC);
?>

<?php if (!empty($active_prescriptions)): ?>
<div class="rc-alert blue"
     id="active-prescriptions-alert"
     data-patient-weight="<?= $last_weight ? htmlspecialchars($last_weight['weight']) : '' ?>"
     data-patient-weight-unit="<?= $last_weight ? htmlspecialchars(strtolower($last_weight['weight_unit'])) : '' ?>">

    <strong>Active Prescriptions</strong>

    <div class="rc-stack">

        <div>
            <strong>Last recorded weight:</strong>
            <?php if ($last_weight): ?>
                <?= htmlspecialchars($last_weight['weight']) ?>
                <?= htmlspecialchars($last_weight['weight_unit']) ?>
                (<?= date('j M Y', strtotime($last_weight['date'])) ?>)
            <?php else: ?>
                not available
            <?php endif; ?>
        </div>

        <table class="rc-table">
            <tbody>
            <?php foreach ($active_prescriptions as $rx): ?>

                <?php
                    $isMl = ($rx['dose_type'] === 'ml');
                    $displayMedication = $rx['medication'];
                    if (!empty($rx['route'])) {
                        $displayMedication .= ' (' . $rx['route'] . ')';
                    }

                    $prescribed = ((int)$rx['by_weight'] === 1)
                        ? $rx['dose'] . ' ' . $rx['dose_type'] . '/kg'
                        : $rx['dose'] . ' ' . $rx['dose_type'];
                ?>

                <tr class="active-prescription-row">
                    <td>
                        <?= htmlspecialchars($displayMedication) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($prescribed) ?>
                    </td>
                    <td>
                        <?= $isMl ? htmlspecialchars($rx['dose']) . ' ml' : '—' ?>
                    </td>
                </tr>

            <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>
<?php endif; ?>
<!-- Button and form to admin a medication -->

<!-- ==========================================================
     ADMINISTER MEDICATION
========================================================== -->

<div class="rc-actions">

    <button type="button" class="btn blue" id="btn-admin-simple">
        Administer – Simple mode
    </button>

    <button type="button" class="btn blue" id="btn-admin-stock">
        Administer – Stock mode
    </button>

</div>

<div id="admin-simple-container" class="rc-card rc-card-muted" style="display:none;">
    <?php
        // expects $patient_id — unchanged
        include __DIR__ . '/../controllers/medication/meds_simple.php';
    ?>
</div>

<div id="admin-stock-container" class="rc-card rc-card-muted" style="display:none;">
    <?php
        // expects $patient_id — unchanged
        include __DIR__ . '/../controllers/medication/meds_stock.php';
    ?>
</div>



<!-- ==========================================================
     COLLAPSIBLE PRESCRIPTIONS (DEFAULT CLOSED)
========================================================== -->
<details class="rc-card rc-card-muted">
    <summary>
        Prescriptions
    </summary>

    <div class="rc-stack">
        <?php include __DIR__ . '/all_prescriptions.php'; ?>
    </div>
</details>

<!-- ==========================================================
     MEDICATIONS GIVEN
========================================================== -->
<h3>Medications given</h3>

<div>
    <?php include __DIR__ . '/meds_given.php'; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const btnSimple = document.getElementById('btn-admin-simple');
    const btnStock  = document.getElementById('btn-admin-stock');
    const simpleBox = document.getElementById('admin-simple-container');
    const stockBox  = document.getElementById('admin-stock-container');

    if (!btnSimple || !btnStock) return;

    btnSimple.addEventListener('click', function () {
        stockBox.style.display  = 'none';
        simpleBox.style.display = (simpleBox.style.display === 'block') ? 'none' : 'block';

        if (typeof attachAutocomplete === 'function') attachAutocomplete();
    });

    btnStock.addEventListener('click', function () {
        simpleBox.style.display = 'none';
        stockBox.style.display  = (stockBox.style.display === 'block') ? 'none' : 'block';

        if (typeof attachAutocomplete === 'function') attachAutocomplete();
    });

});
</script>

<?php include __DIR__ . '/../controllers/medicationlist.php'; ?>
