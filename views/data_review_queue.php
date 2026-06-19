<?php
if (!defined('APP_LOADED')) {
    exit('Direct access not permitted.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function rc_review_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rc_review_col_exists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
          AND COLUMN_NAME = :column
    ");
    $stmt->execute([':table' => $table, ':column' => $column]);
    return (int)$stmt->fetchColumn() > 0;
}

$hasSoftDelete = rc_review_col_exists($pdo, 'rescue_admissions', 'is_deleted')
    && rc_review_col_exists($pdo, 'rescue_patients', 'is_deleted');

$reviewRows = [];
if ($hasSoftDelete) {
    $stmt = $pdo->prepare("
        SELECT
            a.admission_id,
            a.patient_id,
            a.admission_date,
            a.disposition_date,
            a.disposition_comment,
            p.name,
            p.animal_species,
            p.state
        FROM rescue_admissions a
        INNER JOIN rescue_patients p ON p.patient_id = a.patient_id
        WHERE a.centre_id = :centre_id
          AND p.centre_id = :centre_id2
          AND LOWER(TRIM(a.disposition)) = 'review'
          AND COALESCE(a.is_deleted, 0) = 0
          AND COALESCE(p.is_deleted, 0) = 0
        ORDER BY a.disposition_date DESC, a.admission_id DESC
    ");
    $stmt->execute([':centre_id' => (int)$centre_id, ':centre_id2' => (int)$centre_id]);
    $reviewRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
?>

<div class="rc-card">
    <div class="rc-card-header">
        <div>
            <h3 class="rc-card-title"><?= rc_review_h($lang['DATA_REVIEW_QUEUE'] ?? 'Review Queue') ?></h3>
            <p class="rc-muted"><?= rc_review_h($lang['DATA_REVIEW_QUEUE_HELP'] ?? 'Admissions marked with the Review disposition. These are hidden from My Patients and await manager review.') ?></p>
        </div>
    </div>

    <?php if (!$hasSoftDelete): ?>
        <div class="rc-alert amber"><?= rc_review_h($lang['DATA_SOFT_DELETE_MIGRATION_MISSING'] ?? 'The is_deleted migration has not been applied to patients/admissions yet.') ?></div>
    <?php elseif (!$reviewRows): ?>
        <div class="rc-alert blue"><?= rc_review_h($lang['DATA_NO_REVIEW_RECORDS'] ?? 'No records are currently awaiting review.') ?></div>
    <?php else: ?>
        <div class="table">
            <table>
                <thead>
                    <tr>
                        <td><?= rc_review_h($lang['PAT_CRN'] ?? 'CRN') ?></td>
                        <td><?= rc_review_h($lang['ADMISSION'] ?? 'Admission') ?></td>
                        <td><?= rc_review_h($lang['PATIENT'] ?? 'Patient') ?></td>
                        <td><?= rc_review_h($lang['SPECIES'] ?? 'Species') ?></td>
                        <td><?= rc_review_h($lang['DATA_MARKED_REVIEW'] ?? 'Marked Review') ?></td>
                        <td><?= rc_review_h($lang['COMMENTS'] ?? 'Comment') ?></td>
                        <td><?= rc_review_h($lang['ACTIONS'] ?? 'Action') ?></td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviewRows as $row): ?>
                        <tr>
                            <td><strong><?= (int)$row['patient_id'] ?></strong></td>
                            <td>#<?= (int)$row['admission_id'] ?><br><span class="grey small"><?= rc_review_h($row['admission_date']) ?></span></td>
                            <td><?= rc_review_h($row['name'] ?: ($lang['LOC_UNNAMED'] ?? 'Unnamed')) ?></td>
                            <td><?= rc_review_h($row['animal_species']) ?></td>
                            <td><?= rc_review_h($row['disposition_date']) ?></td>
                            <td><?= rc_review_h($row['disposition_comment']) ?></td>
                            <td>
                                <form method="post" action="controllers/data_review_handler.php" onsubmit="return confirm('<?= rc_review_h($lang['DATA_SOFT_DELETE_CONFIRM'] ?? 'Soft delete this reviewed record?') ?>');">
                                    <input type="hidden" name="csrf" value="<?= rc_review_h($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="soft_delete">
                                    <input type="hidden" name="admission_id" value="<?= (int)$row['admission_id'] ?>">
                                    <button type="submit" class="btn red"><?= rc_review_h($lang['DATA_SOFT_DELETE'] ?? 'Soft Delete') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
