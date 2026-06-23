<?php
define('APP_LOADED', true);

include __DIR__ . '/../dashmain.php';
include __DIR__ . '/../getcentreinfo.php';
require_once __DIR__ . '/../operations/permissions.php';
require_once __DIR__ . '/../operations/lite_sync_catalogue.php';

registerPermission('page_centre_management', 'Access to Centre Management Settings Page', 'page');
requirePermission('page_centre_management');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../management.php?tab=sync&error=' . urlencode('Invalid sync request.'));
    exit;
}

$catalogue = (string)($_POST['catalogue'] ?? '');
$mode = (string)($_POST['mode'] ?? 'all');
$value = trim((string)($_POST['value'] ?? ''));
$enableForCentre = !empty($_POST['enable_for_centre']);

if (!in_array($catalogue, ['species', 'medications', 'feed'], true)) {
    header('Location: ../management.php?tab=sync&error=' . urlencode('Unknown sync catalogue.'));
    exit;
}

$allowedModes = [
    'species' => ['all', 'type', 'class', 'search'],
    'medications' => ['all', 'class', 'search'],
    'feed' => ['all', 'type', 'category', 'search'],
];

if (!in_array($mode, $allowedModes[$catalogue], true)) {
    header('Location: ../management.php?tab=sync&error=' . urlencode('Unknown sync filter.'));
    exit;
}

if ($mode !== 'all' && $value === '') {
    header('Location: ../management.php?tab=sync&error=' . urlencode('Enter a filter/search value before syncing.'));
    exit;
}

try {
    lite_sync_ensure_catalogue_schema($pdo, $catalogue);
    $items = lite_sync_fetch_catalogue($pdo, $catalogue, $mode, $value);
    $pdo->beginTransaction();

    if ($catalogue === 'species') {
        $count = lite_sync_import_species($pdo, $items);
    } elseif ($catalogue === 'medications') {
        $count = lite_sync_import_medications($pdo, $items);
    } else {
        $count = lite_sync_import_feed($pdo, $items, (int)$centre_id, $enableForCentre);
    }

    $pdo->commit();
    header('Location: ../management.php?tab=sync&success=' . urlencode('Sync complete. Imported/updated ' . $count . ' ' . $catalogue . ' records.'));
    exit;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: ../management.php?tab=sync&error=' . urlencode($e->getMessage()));
    exit;
}
