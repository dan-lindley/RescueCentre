<?php
// modules/triage/controllers/triage_lib.php

function triage_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function triage_user_id(): int
{
    return (int)($_SESSION['account_id'] ?? $_SESSION['user_id'] ?? 0);
}

function triage_centre_id(): int
{
    return (int)($_POST['centre_id'] ?? $_GET['centre_id'] ?? $_SESSION['centre_id'] ?? $_SESSION['rescue_id'] ?? 0);
}

function triage_table_columns(PDO $pdo, string $tableName): array
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

function triage_has_column(PDO $pdo, string $tableName, string $columnName): bool
{
    $columns = triage_table_columns($pdo, $tableName);
    return isset($columns[$columnName]);
}

function triage_flash(string $type, string $message): void
{
    $_SESSION['triage_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function triage_redirect(array $params = []): void
{
    $params = array_merge(['module' => 'triage', 'view' => 'call'], $params);
    header('Location: ../../../module.php?' . http_build_query($params));
    exit;
}
