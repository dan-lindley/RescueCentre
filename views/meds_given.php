<?php
// ------------------------------------------------------------
// views/meds_given.php
// ------------------------------------------------------------
// Requires: $pdo, $patient_id
// ------------------------------------------------------------
require_once __DIR__ . '/../core/icons.php';

$medicationGivenSoftDeleteFilter = '';
try {
    $colStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'rescue_medications_given'
          AND COLUMN_NAME = 'is_deleted'
    ");
    $colStmt->execute();
    if ((int)$colStmt->fetchColumn() > 0) {
        $medicationGivenSoftDeleteFilter = " AND COALESCE(is_deleted, 0) = 0";
    }
} catch (Throwable $e) {
    $medicationGivenSoftDeleteFilter = '';
}

$stmt = $pdo->prepare("
    SELECT
        med_adm_id,
        date,
        medication_given,
        dose,
        dose_type,
        vol_given,
        batch_given,
        exp_given,
        given_by
    FROM rescue_medications_given
    WHERE patient_id = :pid
      {$medicationGivenSoftDeleteFilter}
    ORDER BY date DESC
");
$stmt->execute([':pid' => $patient_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (empty($rows)): ?>

    <p><em>No medications have been administered for this patient.</em></p>

<?php else: ?>

<table class="x-table" style="width:100%;">
    <thead>
        <tr>
            <th>Date / Time</th>
            <th>Medication</th>
            <th>Batch</th>
            <th>Exp</th>
            <th>Dose</th>
            <th>Volume</th>
            <th>Given by</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>

    <?php foreach ($rows as $row): ?>

        <tr>
            <td>
                <?= htmlspecialchars(date('j M Y H:i', strtotime($row['date']))) ?>
            </td>

            <td>
                <?= htmlspecialchars($row['medication_given']) ?>
            </td>
            
            <td>
                <?= htmlspecialchars($row['batch_given']) ?>
            </td>

            
            <td>
                <?= htmlspecialchars($row['exp_given']) ?>
            </td>


            <td>
                <?= htmlspecialchars($row['dose']) ?>
                <?= htmlspecialchars($row['dose_type']) ?>
            </td>

            <td>
                <?= $row['vol_given'] > 0
                    ? htmlspecialchars($row['vol_given'])
                    : '—' ?>
            </td>

            <td>
                <?= htmlspecialchars($row['given_by'] ?: '—') ?>
            </td>
            <td>
                <form method="post" action="controllers/medication/medication_handler.php" onsubmit="return confirm('Delete this medication administration?');" style="display:inline;">
                    <input type="hidden" name="action" value="soft_delete_medication_given">
                    <input type="hidden" name="patient_id" value="<?= (int)$patient_id ?>">
                    <input type="hidden" name="med_adm_id" value="<?= (int)$row['med_adm_id'] ?>">
                    <button type="submit" class="btn red" title="Delete medication administration" aria-label="Delete medication administration">
                        <?= rc_icon('trash', 20, 'icon', 'aria-hidden="true"') ?>
                    </button>
                </form>
            </td>
        </tr>

    <?php endforeach; ?>

    </tbody>
</table>

<?php endif; ?>
