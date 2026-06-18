<?php
// ------------------------------------------------------------
// views/all_prescriptions.php
// ------------------------------------------------------------
// Requires:
// $pdo
// $patient_id
// ------------------------------------------------------------
require_once __DIR__ . '/../core/icons.php';

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
        prescription_id,
        medication,
        route,
        dose,
        dose_type,
        by_weight,
        date,
        duration,
        DATE_ADD(date, INTERVAL duration DAY) AS end_date
    FROM rescue_prescriptions
    WHERE patient_id = :pid
      {$prescriptionSoftDeleteFilter}
    ORDER BY date DESC
");
$stmt->execute([':pid' => $patient_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (empty($rows)): ?>

    <p><em>No prescriptions found for this patient.</em></p>

<?php else: ?>

<table class="x-table" style="width:100%;">
    <thead>
        <tr>
            <th>Medication</th>
            <th>Dose</th>
            <th>Route</th>
            <th>Start</th>
            <th>End</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>

    <?php foreach ($rows as $rx): ?>

        <?php
            $isActive = strtotime($rx['end_date']) >= strtotime('today');

            $doseText = $rx['dose'] . ' ' . $rx['dose_type'];
            if ((int)$rx['by_weight'] === 1) {
                $doseText .= '/kg';
            }
        ?>

        <tr>
            <td>
                <?= htmlspecialchars($rx['medication']) ?>
            </td>

            <td>
                <?= htmlspecialchars($doseText) ?>
            </td>

            <td>
                <?= htmlspecialchars($rx['route'] ?: '—') ?>
            </td>

            <td>
                <?= htmlspecialchars(date('j M Y', strtotime($rx['date']))) ?>
            </td>

            <td>
                <?= htmlspecialchars(date('j M Y', strtotime($rx['end_date']))) ?>
            </td>

            <td>
                <?= $isActive ? 'Active' : 'Completed' ?>
            </td>
            <td>
                <form method="post" action="controllers/medication/medication_handler.php" onsubmit="return confirm('Delete this prescription?');" style="display:inline;">
                    <input type="hidden" name="action" value="soft_delete_prescription">
                    <input type="hidden" name="patient_id" value="<?= (int)$patient_id ?>">
                    <input type="hidden" name="prescription_id" value="<?= (int)$rx['prescription_id'] ?>">
                    <button type="submit" class="btn red" title="Delete prescription" aria-label="Delete prescription">
                        <?= rc_icon('trash', 20, 'icon', 'aria-hidden="true"') ?>
                    </button>
                </form>
            </td>
        </tr>

    <?php endforeach; ?>

    </tbody>
</table>

<?php endif; ?>
