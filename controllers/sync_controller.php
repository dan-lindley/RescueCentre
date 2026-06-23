<?php
define('APP_LOADED', true);

include __DIR__ . '/../dashmain.php';
include __DIR__ . '/../getcentreinfo.php';
require_once __DIR__ . '/../operations/permissions.php';
require_once __DIR__ . '/../operations/lite_sync_catalogue.php';

registerPermission('page_centre_management', 'Access to Centre Management Settings Page', 'page');
requirePermission('page_centre_management');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'search') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $catalogue = (string)($_GET['catalogue'] ?? '');
        $query = trim((string)($_GET['q'] ?? ''));
        if (!in_array($catalogue, ['species', 'medications', 'feed'], true)) {
            throw new RuntimeException('Unknown sync catalogue.');
        }
        if (strlen($query) < 2) {
            echo json_encode(['status' => 'ok', 'items' => []]);
            exit;
        }
        lite_sync_ensure_catalogue_schema($pdo, $catalogue);
        echo json_encode([
            'status' => 'ok',
            'items' => lite_sync_search_catalogue($pdo, $catalogue, $query),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../management.php?tab=sync&error=' . urlencode('Invalid sync request.'));
    exit;
}

$catalogue = (string)($_POST['catalogue'] ?? '');
$mode = (string)($_POST['mode'] ?? 'all');
$value = trim((string)($_POST['value'] ?? ''));
$enableForCentre = !empty($_POST['enable_for_centre']);
$selectedIds = json_decode((string)($_POST['selected_ids'] ?? '[]'), true);
if (!is_array($selectedIds)) {
    $selectedIds = [];
}
$selectedIds = array_values(array_unique(array_filter(array_map('intval', $selectedIds))));

if (!in_array($catalogue, ['species', 'medications', 'feed'], true)) {
    header('Location: ../management.php?tab=sync&error=' . urlencode('Unknown sync catalogue.'));
    exit;
}

$allowedModes = [
    'species' => ['all', 'type', 'class', 'search', 'selected'],
    'medications' => ['all', 'class', 'search', 'selected'],
    'feed' => ['all', 'type', 'category', 'search', 'selected'],
];

if (!in_array($mode, $allowedModes[$catalogue], true)) {
    header('Location: ../management.php?tab=sync&error=' . urlencode('Unknown sync filter.'));
    exit;
}

if ($mode === 'selected' && !$selectedIds) {
    header('Location: ../management.php?tab=sync&error=' . urlencode('Select at least one item before syncing.'));
    exit;
}

if (!in_array($mode, ['all', 'selected'], true) && $value === '') {
    header('Location: ../management.php?tab=sync&error=' . urlencode('Enter a filter/search value before syncing.'));
    exit;
}

try {
    lite_sync_ensure_catalogue_schema($pdo, $catalogue);
    $items = lite_sync_fetch_catalogue($pdo, $catalogue, $mode, $value, $selectedIds);
    $pdo->beginTransaction();

    if ($catalogue === 'species') {
        $count = lite_sync_import_species($pdo, $items);
    } elseif ($catalogue === 'medications') {
        $count = lite_sync_import_medications($pdo, $items);
    } else {
        $count = lite_sync_import_feed($pdo, $items, (int)$centre_id, $enableForCentre);
    }

    $pdo->commit();
    header('Location: ../management.php?tab=sync&success=' . urlencode('Sync complete. Added ' . $count . ' new ' . $catalogue . ' records. Existing local records were left unchanged.'));
    exit;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: ../management.php?tab=sync&error=' . urlencode($e->getMessage()));
    exit;
}
