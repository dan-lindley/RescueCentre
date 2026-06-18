<?php
// modules/triage_builder/controllers/triage_builder_lib.php

function triage_builder_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function triage_builder_user_id(): int
{
    return (int)($_SESSION['account_id'] ?? $_SESSION['user_id'] ?? 0);
}

function triage_builder_centre_id(): int
{
    return (int)($_POST['centre_id'] ?? $_GET['centre_id'] ?? $_SESSION['centre_id'] ?? $_SESSION['rescue_id'] ?? 0);
}

function triage_builder_is_admin(PDO $pdo, int $userId): bool
{
    if ($userId <= 0) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT role FROM accounts WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $role = (string)($stmt->fetchColumn() ?: '');

    return strcasecmp($role, 'Admin') === 0;
}

function triage_builder_scope_from_post(bool $isAdmin, int $centreId): array
{
    $isGlobal = $isAdmin && !empty($_POST['is_global']);

    return [
        'centre_id' => $isGlobal ? 0 : $centreId,
        'is_global' => $isGlobal ? 1 : 0,
    ];
}

function triage_builder_table_columns(PDO $pdo, string $tableName): array
{
    static $cache = [];

    if (isset($cache[$tableName])) {
        return $cache[$tableName];
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM " . $tableName);
    $columns = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $columns[(string)$row['Field']] = true;
    }

    $cache[$tableName] = $columns;
    return $columns;
}

function triage_builder_has_column(PDO $pdo, string $tableName, string $columnName): bool
{
    $columns = triage_builder_table_columns($pdo, $tableName);
    return isset($columns[$columnName]);
}

function triage_builder_enum_has_value(PDO $pdo, string $tableName, string $columnName, string $value): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $tableName) || !preg_match('/^[A-Za-z0-9_]+$/', $columnName)) {
        return false;
    }

    $stmt = $pdo->prepare("SHOW COLUMNS FROM " . $tableName . " LIKE ?");
    $stmt->execute([$columnName]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['Type'])) {
        return false;
    }

    return strpos((string)$row['Type'], "'" . str_replace("'", "\\'", $value) . "'") !== false;
}

function triage_builder_scope_label(array $row): string
{
    return !empty($row['is_global']) || (int)($row['centre_id'] ?? -1) === 0 ? 'Global' : 'Centre';
}

function triage_builder_short_text($value, int $maxLength = 80): string
{
    $text = (string)$value;
    if (strlen($text) <= $maxLength) {
        return $text;
    }

    return substr($text, 0, max(0, $maxLength - 3)) . '...';
}

function triage_builder_can_manage_row(array $row, int $centreId, bool $isAdmin): bool
{
    if ($isAdmin) {
        return true;
    }

    return (int)($row['centre_id'] ?? 0) === $centreId && empty($row['is_global']);
}

function triage_builder_flash(string $type, string $message): void
{
    $_SESSION['triage_builder_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function triage_builder_redirect(array $params = []): void
{
    $fragment = (string)($params['_fragment'] ?? '');
    unset($params['_fragment']);

    if ($fragment === '') {
        $returnTab = (string)($_POST['return_tab'] ?? '');
        if (in_array($returnTab, ['sets', 'qa', 'advice', 'builder'], true)) {
            $fragment = 'triage-' . $returnTab;
        }
    }

    $params = array_merge(['module' => 'triage_builder', 'view' => 'questions'], $params);
    header('Location: ../../../module.php?' . http_build_query($params) . ($fragment !== '' ? '#' . $fragment : ''));
    exit;
}
