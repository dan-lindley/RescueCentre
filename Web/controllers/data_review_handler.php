<?php
// controllers/data_review_handler.php
// Review queue, soft delete, recovery and hard delete actions for patient records.

define('APP_LOADED', true);

require_once __DIR__ . '/../dashmain.php';
require_once __DIR__ . '/../operations/permissions.php';
require_once __DIR__ . '/../operations/audit.php';
require_once __DIR__ . '/../core/mfa.php';

registerPermission('page_data_management', $lang['DATA_PERMISSION_ACCESS'] ?? 'Access to Data Management', 'page');
requirePermission('page_data_management');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function rc_data_t(string $key, string $fallback): string {
    global $lang;
    return $lang[$key] ?? $fallback;
}

function rc_data_review_redirect(string $tab, string $key, string $message): void {
    header('Location: ../data.php?tab=' . urlencode($tab) . '&' . $key . '=' . urlencode($message));
    exit;
}

function rc_data_review_ident(string $name): string {
    return '`' . str_replace('`', '``', $name) . '`';
}

function rc_data_review_column_exists(PDO $pdo, string $table, string $column): bool {
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

function rc_data_review_authorised(PDO $pdo, int $accountId): bool {
    if ($accountId <= 0) {
        return false;
    }

    $stmt = $pdo->prepare("
        SELECT a.role AS account_role, rr.role_name
        FROM accounts a
        LEFT JOIN rescue_roles rr ON rr.role_id = a.rescue_role
        WHERE a.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $accountId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return false;
    }

    $accountRole = strtolower(trim((string)($row['account_role'] ?? '')));
    $centreRole = strtolower(trim((string)($row['role_name'] ?? '')));

    return in_array($accountRole, ['admin', 'developer'], true)
        || in_array($centreRole, ['admin', 'owner', 'manager'], true);
}

function rc_data_review_fetch_admission(PDO $pdo, int $admissionId, int $centreId): ?array {
    $stmt = $pdo->prepare("
        SELECT
            a.admission_id,
            a.patient_id,
            a.centre_id,
            a.disposition,
            COALESCE(a.is_deleted, 0) AS admission_deleted,
            p.name,
            p.animal_species,
            COALESCE(p.is_deleted, 0) AS patient_deleted
        FROM rescue_admissions a
        INNER JOIN rescue_patients p ON p.patient_id = a.patient_id
        WHERE a.admission_id = :aid
          AND a.centre_id = :cid
          AND p.centre_id = :cid2
        LIMIT 1
    ");
    $stmt->execute([':aid' => $admissionId, ':cid' => $centreId, ':cid2' => $centreId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function rc_data_review_require_soft_delete_columns(PDO $pdo): void {
    if (!rc_data_review_column_exists($pdo, 'rescue_admissions', 'is_deleted')
        || !rc_data_review_column_exists($pdo, 'rescue_patients', 'is_deleted')) {
        throw new RuntimeException(rc_data_t('DATA_SOFT_DELETE_MIGRATION_MISSING', 'The is_deleted migration has not been applied to patients/admissions yet.'));
    }
}

function rc_data_review_password_ok(PDO $pdo, int $accountId, string $password): bool {
    $stmt = $pdo->prepare('SELECT password FROM accounts WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $accountId]);
    $hash = (string)$stmt->fetchColumn();
    return $hash !== '' && password_verify($password, $hash);
}

function rc_data_review_discover_linked_tables(PDO $pdo): array {
    $stmt = $pdo->query("
        SELECT
            c.TABLE_NAME,
            MAX(c.COLUMN_NAME = 'patient_id') AS has_patient_id,
            MAX(c.COLUMN_NAME = 'admission_id') AS has_admission_id
        FROM INFORMATION_SCHEMA.COLUMNS c
        INNER JOIN INFORMATION_SCHEMA.TABLES t
            ON t.TABLE_SCHEMA = c.TABLE_SCHEMA
           AND t.TABLE_NAME = c.TABLE_NAME
        WHERE c.TABLE_SCHEMA = DATABASE()
          AND c.COLUMN_NAME IN ('patient_id', 'admission_id')
          AND t.TABLE_TYPE = 'BASE TABLE'
          AND c.TABLE_NAME NOT IN ('rescue_patients', 'rescue_admissions')
        GROUP BY c.TABLE_NAME
        ORDER BY c.TABLE_NAME
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rc_data_review_pk(PDO $pdo, string $table): ?string {
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

function rc_data_review_deleted_column(PDO $pdo, string $table): ?string {
    foreach (['is_deleted', 'deleted'] as $column) {
        if (rc_data_review_column_exists($pdo, $table, $column)) {
            return $column;
        }
    }
    return null;
}

function rc_data_review_table_is_soft_deletable(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
          AND TABLE_TYPE = 'BASE TABLE'
    ");
    $stmt->execute([':table' => $table]);
    return (int)$stmt->fetchColumn() > 0
        && rc_data_review_pk($pdo, $table) !== null
        && rc_data_review_deleted_column($pdo, $table) !== null;
}

function rc_data_review_fetch_deleted_record(PDO $pdo, string $table, int $recordId, int $centreId): array {
    if (!rc_data_review_table_is_soft_deletable($pdo, $table)) {
        throw new RuntimeException(rc_data_t('DATA_TABLE_NOT_AVAILABLE', 'This table is not available for delete/recovery actions.'));
    }

    $pk = rc_data_review_pk($pdo, $table);
    $deletedColumn = rc_data_review_deleted_column($pdo, $table);
    if ($pk === null || $deletedColumn === null) {
        throw new RuntimeException(rc_data_t('DATA_DELETE_METADATA_FAILED', 'Could not resolve delete/recovery metadata.'));
    }

    $where = [rc_data_review_ident($pk) . ' = :record_id', 'COALESCE(' . rc_data_review_ident($deletedColumn) . ', 0) = 1'];
    $params = [':record_id' => $recordId];

    if (rc_data_review_column_exists($pdo, $table, 'centre_id')) {
        $where[] = rc_data_review_ident('centre_id') . ' = :centre_id';
        $params[':centre_id'] = $centreId;
    } elseif (rc_data_review_column_exists($pdo, $table, 'patient_id')) {
        $where[] = rc_data_review_ident('patient_id') . ' IN (SELECT patient_id FROM rescue_patients WHERE centre_id = :centre_id)';
        $params[':centre_id'] = $centreId;
    } elseif (rc_data_review_column_exists($pdo, $table, 'admission_id')) {
        $where[] = rc_data_review_ident('admission_id') . ' IN (SELECT admission_id FROM rescue_admissions WHERE centre_id = :centre_id)';
        $params[':centre_id'] = $centreId;
    } else {
        throw new RuntimeException(rc_data_t('DATA_RECORD_SCOPE_FAILED', 'This record cannot be safely scoped to your centre.'));
    }

    $sql = 'SELECT * FROM ' . rc_data_review_ident($table) . ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new RuntimeException(rc_data_t('DATA_DELETED_RECORD_NOT_FOUND', 'Deleted record could not be found for this centre.'));
    }

    return ['pk' => $pk, 'deleted_column' => $deletedColumn, 'row' => $row];
}

function rc_data_review_hard_delete(PDO $pdo, int $patientId, int $admissionId, int $centreId): int {
    $deleted = 0;
    foreach (rc_data_review_discover_linked_tables($pdo) as $meta) {
        $table = (string)$meta['TABLE_NAME'];
        $parts = [];
        $params = [];

        if (!empty($meta['has_patient_id'])) {
            $parts[] = rc_data_review_ident('patient_id') . ' = :pid';
            $params[':pid'] = $patientId;
        }

        if (!empty($meta['has_admission_id'])) {
            $parts[] = rc_data_review_ident('admission_id') . ' = :aid';
            $params[':aid'] = $admissionId;
        }

        if (!$parts) {
            continue;
        }

        $sql = 'DELETE FROM ' . rc_data_review_ident($table) . ' WHERE (' . implode(' OR ', $parts) . ')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $deleted += $stmt->rowCount();
    }

    $stmt = $pdo->prepare('DELETE FROM rescue_admissions WHERE admission_id = :aid AND patient_id = :pid AND centre_id = :cid LIMIT 1');
    $stmt->execute([':aid' => $admissionId, ':pid' => $patientId, ':cid' => $centreId]);
    $deleted += $stmt->rowCount();

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM rescue_admissions WHERE patient_id = :pid');
    $countStmt->execute([':pid' => $patientId]);
    if ((int)$countStmt->fetchColumn() === 0) {
        $stmt = $pdo->prepare('DELETE FROM rescue_patients WHERE patient_id = :pid AND centre_id = :cid LIMIT 1');
        $stmt->execute([':pid' => $patientId, ':cid' => $centreId]);
        $deleted += $stmt->rowCount();
    }

    return $deleted;
}

$accountId = (int)($_SESSION['account_id'] ?? 0);
$centreId = (int)($GLOBALS['centre_id'] ?? $_SESSION['centre_id'] ?? 0);
$action = (string)($_POST['action'] ?? '');
$admissionId = (int)($_POST['admission_id'] ?? 0);
$recordTable = (string)($_POST['record_table'] ?? '');
$recordId = (int)($_POST['record_id'] ?? 0);
$csrf = (string)($_POST['csrf'] ?? '');
$returnTab = in_array($action, ['recover', 'hard_delete', 'recover_record', 'hard_delete_record'], true) ? 'deleted' : 'review';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException($lang['SETTINGS_INVALID_REQUEST'] ?? 'Invalid request.');
    }

    if ($csrf === '' || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
        throw new RuntimeException(rc_data_t('DATA_SECURITY_TOKEN_FAILED', 'Security token failed. Please refresh and try again.'));
    }

    if (!rc_data_review_authorised($pdo, $accountId)) {
        throw new RuntimeException(rc_data_t('DATA_REVIEW_PERMISSION_DENIED', 'Only Admin, Owner or Manager users can perform review actions.'));
    }

    if ($centreId <= 0) {
        throw new RuntimeException(rc_data_t('DATA_INVALID_CENTRE', 'Invalid centre.'));
    }

    if ($action === 'recover_record' || $action === 'hard_delete_record') {
        if ($recordTable === '' || $recordId <= 0) {
            throw new RuntimeException(rc_data_t('DATA_INVALID_DELETED_RECORD', 'Invalid deleted record.'));
        }

        $recordMeta = rc_data_review_fetch_deleted_record($pdo, $recordTable, $recordId, $centreId);
        $purpose = 'data.deleted.' . $action . '.' . $recordTable;

        if (!rc_mfa_session_allows($purpose, $recordId)) {
            header('Location: ' . rc_mfa_redirect_url($purpose, $recordId, '/data.php?tab=deleted&mfa=1'));
            exit;
        }

        if ($action === 'recover_record') {
            $sql = 'UPDATE ' . rc_data_review_ident($recordTable)
                . ' SET ' . rc_data_review_ident($recordMeta['deleted_column']) . ' = 0'
                . ' WHERE ' . rc_data_review_ident($recordMeta['pk']) . ' = :record_id LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':record_id' => $recordId]);
            audit_write($pdo, 'soft_deleted_record_recovered', $recordTable, $recordId, ['deleted_column' => $recordMeta['deleted_column']]);

            rc_data_review_redirect('deleted', 'success', rc_data_t('DATA_RECORD_RECOVERED', 'Record recovered.'));
        }

        $password = (string)($_POST['admin_password'] ?? '');
        if (!rc_data_review_password_ok($pdo, $accountId, $password)) {
            throw new RuntimeException(rc_data_t('DATA_PASSWORD_FAILED', 'Password verification failed.'));
        }

        $sql = 'DELETE FROM ' . rc_data_review_ident($recordTable)
            . ' WHERE ' . rc_data_review_ident($recordMeta['pk']) . ' = :record_id LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':record_id' => $recordId]);
        audit_write($pdo, 'soft_deleted_record_hard_deleted', $recordTable, $recordId, ['deleted_column' => $recordMeta['deleted_column']]);

        rc_data_review_redirect('deleted', 'success', rc_data_t('DATA_RECORD_PERMANENTLY_DELETED', 'Record permanently deleted.'));
    }

    if ($centreId <= 0 || $admissionId <= 0) {
        throw new RuntimeException(rc_data_t('DATA_INVALID_REVIEW_RECORD', 'Invalid review record.'));
    }

    rc_data_review_require_soft_delete_columns($pdo);

    $row = rc_data_review_fetch_admission($pdo, $admissionId, $centreId);
    if (!$row) {
        throw new RuntimeException(rc_data_t('DATA_REVIEW_RECORD_NOT_FOUND', 'Review record could not be found for this centre.'));
    }

    $purpose = 'data.review.' . $action;
    if (!rc_mfa_session_allows($purpose, $admissionId)) {
        header('Location: ' . rc_mfa_redirect_url($purpose, $admissionId, '/data.php?tab=' . $returnTab . '&mfa=1'));
        exit;
    }

    if ($action === 'soft_delete') {
        if (strcasecmp((string)$row['disposition'], 'Review') !== 0) {
            throw new RuntimeException(rc_data_t('DATA_ONLY_REVIEW_SOFT_DELETE', 'Only admissions marked Review can be soft deleted from this queue.'));
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE rescue_admissions SET is_deleted = 1 WHERE admission_id = :aid AND centre_id = :cid LIMIT 1');
        $stmt->execute([':aid' => $admissionId, ':cid' => $centreId]);
        $stmt = $pdo->prepare('UPDATE rescue_patients SET is_deleted = 1 WHERE patient_id = :pid AND centre_id = :cid LIMIT 1');
        $stmt->execute([':pid' => (int)$row['patient_id'], ':cid' => $centreId]);
        audit_write($pdo, 'patient_review_soft_deleted', 'rescue_admissions', $admissionId, ['patient_id' => (int)$row['patient_id']]);
        $pdo->commit();

        rc_data_review_redirect('review', 'success', rc_data_t('DATA_RECORD_SOFT_DELETED', 'Record soft deleted.'));
    }

    if ($action === 'recover') {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE rescue_admissions SET is_deleted = 0 WHERE admission_id = :aid AND centre_id = :cid LIMIT 1');
        $stmt->execute([':aid' => $admissionId, ':cid' => $centreId]);
        $stmt = $pdo->prepare('UPDATE rescue_patients SET is_deleted = 0 WHERE patient_id = :pid AND centre_id = :cid LIMIT 1');
        $stmt->execute([':pid' => (int)$row['patient_id'], ':cid' => $centreId]);
        audit_write($pdo, 'patient_review_recovered', 'rescue_admissions', $admissionId, ['patient_id' => (int)$row['patient_id']]);
        $pdo->commit();

        rc_data_review_redirect('deleted', 'success', rc_data_t('DATA_RECORD_RECOVERED', 'Record recovered.'));
    }

    if ($action === 'hard_delete') {
        $password = (string)($_POST['admin_password'] ?? '');
        if (!rc_data_review_password_ok($pdo, $accountId, $password)) {
            throw new RuntimeException(rc_data_t('DATA_PASSWORD_FAILED', 'Password verification failed.'));
        }

        $pdo->beginTransaction();
        $deleted = rc_data_review_hard_delete($pdo, (int)$row['patient_id'], $admissionId, $centreId);
        audit_write($pdo, 'patient_review_hard_deleted', 'rescue_admissions', $admissionId, ['patient_id' => (int)$row['patient_id'], 'rows_deleted' => $deleted]);
        $pdo->commit();

        rc_data_review_redirect('deleted', 'success', rc_data_t('DATA_RECORD_PERMANENTLY_DELETED', 'Record permanently deleted.'));
    }

    throw new RuntimeException(rc_data_t('DATA_UNKNOWN_REVIEW_ACTION', 'Unknown review action.'));
} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    rc_data_review_redirect($returnTab, 'error', $e->getMessage());
}
