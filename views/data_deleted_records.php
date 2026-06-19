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

function rc_deleted_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rc_deleted_ident(string $name): string {
    return '`' . str_replace('`', '``', $name) . '`';
}

function rc_deleted_col_exists(PDO $pdo, string $table, string $column): bool {
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

function rc_deleted_primary_key(PDO $pdo, string $table): ?string {
    $stmt = $pdo->prepare("
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
          AND CONSTRAINT_NAME = 'PRIMARY'
        ORDER BY ORDINAL_POSITION
        LIMIT 1
    ");
    $stmt->execute([':table' => $table]);
    $pk = $stmt->fetchColumn();
    return $pk ? (string)$pk : null;
}

function rc_deleted_label(string $table): string {
    global $lang;

    $labels = [
        'rescue_patients' => ($lang['PATIENT'] ?? 'Patients') . ' / ' . ($lang['ADMISSION'] ?? 'Admissions'),
        'rescue_admissions' => ($lang['PATIENT'] ?? 'Patients') . ' / ' . ($lang['ADMISSION'] ?? 'Admissions'),
        'rescue_prescriptions' => ($lang['LM_MEDICATION'] ?? 'Medication') . ' / ' . ($lang['PRESCRIPTION'] ?? 'Prescriptions'),
        'rescue_medications_given' => ($lang['LM_MEDICATION'] ?? 'Medication') . ' / ' . ($lang['PRESCRIPTION'] ?? 'Prescriptions'),
        'rescue_notes_patients' => $lang['CARE_NOTE'] ?? 'Care Notes',
        'rescue_treatments' => ($lang['TREATMENT'] ?? 'Treatments') . ' / ' . ($lang['CARE'] ?? 'Care'),
        'rescue_feeds' => $lang['FEEDING'] ?? 'Feeding',
        'rescue_labs' => $lang['LABS'] ?? 'Lab Results',
        'rescue_partner_logs' => $lang['DATA_PARTNER_LOGS'] ?? 'Partner Logs',
        'rescue_tasks_patients' => $lang['DATA_QUICK_TASKS'] ?? 'Quick Tasks',
        'rescue_finders' => $lang['FINDER'] ?? 'Finders',
        'rescue_locations' => $lang['LM_LOCATIONS'] ?? 'Locations',
        'rescue_incident_related' => $lang['LM_INCIDENTS'] ?? 'Incidents',
        'rescue_duty_shifts' => $lang['DATA_DUTIES'] ?? 'Duties',
        'rescue_duty_tasks' => $lang['DATA_DUTIES'] ?? 'Duties',
        'rescue_staff_profiles' => $lang['DATA_STAFF'] ?? 'Staff',
    ];

    if (isset($labels[$table])) {
        return $labels[$table];
    }

    $label = preg_replace('/^rescue_/', '', $table);
    $label = str_replace('_', ' ', (string)$label);
    return ucwords($label);
}

function rc_deleted_row_title(array $row, string $table, string $pk): string {
    $candidates = [
        'name',
        'patient_id',
        'admission_id',
        'medication',
        'medication_given',
        'message',
        'treatment',
        'location_name',
        'finder_name',
        'task_title',
        'title',
        $pk,
    ];

    foreach ($candidates as $key) {
        if (isset($row[$key]) && trim((string)$row[$key]) !== '') {
            return (in_array($key, ['patient_id', 'admission_id', $pk], true) ? '#' : '') . (string)$row[$key];
        }
    }

    return $table . ' #' . ($row[$pk] ?? '');
}

function rc_deleted_row_preview(array $row): string {
    $skip = ['password', 'totp_secret', 'secret_hash', 'remember_me_code', 'activation_code', 'reset_code'];
    $keep = [];

    foreach ($row as $key => $value) {
        if (count($keep) >= 6) {
            break;
        }
        if (in_array(strtolower((string)$key), $skip, true) || $value === null || $value === '') {
            continue;
        }
        $text = is_scalar($value) ? (string)$value : '[data]';
        if (strlen($text) > 80) {
            $text = substr($text, 0, 77) . '...';
        }
        $keep[] = $key . ': ' . $text;
    }

    return implode(' | ', $keep);
}

function rc_deleted_discover_tables(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT
            TABLE_NAME,
            MAX(COLUMN_NAME = 'is_deleted') AS has_is_deleted,
            MAX(COLUMN_NAME = 'deleted') AS has_deleted
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND COLUMN_NAME IN ('is_deleted', 'deleted')
        GROUP BY TABLE_NAME
        ORDER BY TABLE_NAME
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rc_deleted_fetch_rows(PDO $pdo, string $table, string $deletedColumn, string $pk, int $centreId): array {
    $where = ['COALESCE(' . rc_deleted_ident($deletedColumn) . ', 0) = 1'];
    $params = [];

    if (rc_deleted_col_exists($pdo, $table, 'centre_id')) {
        $where[] = rc_deleted_ident('centre_id') . ' = :centre_id';
        $params[':centre_id'] = $centreId;
    } elseif (rc_deleted_col_exists($pdo, $table, 'patient_id')) {
        $where[] = rc_deleted_ident('patient_id') . ' IN (SELECT patient_id FROM rescue_patients WHERE centre_id = :centre_id)';
        $params[':centre_id'] = $centreId;
    } elseif (rc_deleted_col_exists($pdo, $table, 'admission_id')) {
        $where[] = rc_deleted_ident('admission_id') . ' IN (SELECT admission_id FROM rescue_admissions WHERE centre_id = :centre_id)';
        $params[':centre_id'] = $centreId;
    } else {
        return [];
    }

    $sql = 'SELECT * FROM ' . rc_deleted_ident($table)
        . ' WHERE ' . implode(' AND ', $where)
        . ' ORDER BY ' . rc_deleted_ident($pk) . ' DESC LIMIT 200';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$deletedGroups = [];
$totalDeletedRows = 0;

foreach (rc_deleted_discover_tables($pdo) as $meta) {
    $table = (string)$meta['TABLE_NAME'];
    $deletedColumn = !empty($meta['has_is_deleted']) ? 'is_deleted' : 'deleted';
    $pk = rc_deleted_primary_key($pdo, $table);
    if ($pk === null) {
        continue;
    }

    $rows = rc_deleted_fetch_rows($pdo, $table, $deletedColumn, $pk, (int)$centre_id);
    if (!$rows) {
        continue;
    }

    $group = rc_deleted_label($table);
    if (!isset($deletedGroups[$group])) {
        $deletedGroups[$group] = [];
    }

    $deletedGroups[$group][] = [
        'table' => $table,
        'deleted_column' => $deletedColumn,
        'pk' => $pk,
        'rows' => $rows,
    ];
    $totalDeletedRows += count($rows);
}
?>

<div class="rc-card">
    <div class="rc-card-header">
        <div>
            <h3 class="rc-card-title"><?= rc_deleted_h($lang['DATA_DELETE_RECOVERY'] ?? 'Delete / Recovery') ?></h3>
            <p class="rc-muted"><?= rc_deleted_h($lang['DATA_DELETE_RECOVERY_HELP'] ?? 'Soft-deleted records across the centre. Recover restores the row; hard delete permanently removes it after verification.') ?></p>
        </div>
        <span class="rc-badge blue"><?= (int)$totalDeletedRows ?> <?= rc_deleted_h($lang['ROWS'] ?? 'rows') ?></span>
    </div>

    <?php if (!$deletedGroups): ?>
        <div class="rc-alert blue"><?= rc_deleted_h($lang['DATA_NO_DELETED_RECORDS'] ?? 'No soft-deleted records were found.') ?></div>
    <?php else: ?>
        <div class="rc-stack">
            <?php foreach ($deletedGroups as $groupLabel => $tables): ?>
                <div class="rc-card rc-card-muted">
                    <h3 class="rc-card-title"><?= rc_deleted_h($groupLabel) ?></h3>

                    <?php foreach ($tables as $tableBlock): ?>
                        <h4><?= rc_deleted_h($tableBlock['table']) ?></h4>
                        <div class="table">
                            <table>
                                <thead>
                                    <tr>
                                        <td><?= rc_deleted_h($lang['DATA_DELETED_ROW'] ?? 'Deleted Row') ?></td>
                                        <td><?= rc_deleted_h($lang['DATA_ROW_DATA'] ?? 'Row Data') ?></td>
                                        <td><?= rc_deleted_h($lang['DATA_DELETED_FLAG'] ?? 'Deleted Flag') ?></td>
                                        <td><?= rc_deleted_h($lang['DATA_RECOVER'] ?? 'Recover') ?></td>
                                        <td><?= rc_deleted_h($lang['LOC_HARD_DELETE'] ?? 'Hard Delete') ?></td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tableBlock['rows'] as $row): ?>
                                        <?php $recordId = (int)$row[$tableBlock['pk']]; ?>
                                        <tr>
                                            <td>
                                                <strong><?= rc_deleted_h(rc_deleted_row_title($row, $tableBlock['table'], $tableBlock['pk'])) ?></strong><br>
                                                <span class="grey small"><?= rc_deleted_h($tableBlock['pk']) ?>: <?= $recordId ?></span>
                                            </td>
                                            <td><?= rc_deleted_h(rc_deleted_row_preview($row)) ?></td>
                                            <td>
                                                <span class="rc-badge red">
                                                    <?= rc_deleted_h($tableBlock['deleted_column']) ?> = 1
                                                </span>
                                            </td>
                                            <td>
                                                <form method="post" action="controllers/data_review_handler.php" onsubmit="return confirm('<?= rc_deleted_h($lang['DATA_RECOVER_CONFIRM'] ?? 'Recover this deleted row?') ?>');">
                                                    <input type="hidden" name="csrf" value="<?= rc_deleted_h($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="action" value="recover_record">
                                                    <input type="hidden" name="record_table" value="<?= rc_deleted_h($tableBlock['table']) ?>">
                                                    <input type="hidden" name="record_id" value="<?= $recordId ?>">
                                                    <button type="submit" class="btn green"><?= rc_deleted_h($lang['DATA_RECOVER'] ?? 'Recover') ?></button>
                                                </form>
                                            </td>
                                            <td>
                                                <form method="post" action="controllers/data_review_handler.php" onsubmit="return confirm('<?= rc_deleted_h($lang['DATA_HARD_DELETE_CONFIRM'] ?? 'Permanently delete this row? This cannot be undone from the app.') ?>');">
                                                    <input type="hidden" name="csrf" value="<?= rc_deleted_h($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="action" value="hard_delete_record">
                                                    <input type="hidden" name="record_table" value="<?= rc_deleted_h($tableBlock['table']) ?>">
                                                    <input type="hidden" name="record_id" value="<?= $recordId ?>">
                                                    <input type="password" name="admin_password" placeholder="<?= rc_deleted_h($lang['PASSWORD'] ?? 'Password') ?>" required style="max-width:180px;">
                                                    <button type="submit" class="btn red"><?= rc_deleted_h($lang['LOC_HARD_DELETE'] ?? 'Hard Delete') ?></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
