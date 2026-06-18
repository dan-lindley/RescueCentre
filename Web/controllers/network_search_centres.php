<?php
// controllers/network_search_centres.php
// JSON endpoint for autocomplete centre search when inviting to a network

define('APP_LOADED', true);

// JSON-safe output guard (prevents warnings/whitespace breaking JSON)
ob_start();

include __DIR__ . '/../dashmain.php';       // $pdo + session
include __DIR__ . '/../getcentreinfo.php';  // centre context
require_once __DIR__ . '/../operations/permissions.php';

header('Content-Type: application/json; charset=utf-8');

// Fail-safe JSON fatal handler
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        while (ob_get_level()) ob_end_clean();
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => 'Server error',
        ]);
    }
});

registerPermission('page_groups', 'Access to Networks', 'page');
requirePermission('page_groups');

// ---------------------------
// Context: centre + user
// ---------------------------
$currentCentreId = 0;
if (isset($centre_id) && (int)$centre_id > 0) $currentCentreId = (int)$centre_id;
elseif (isset($rescue_id) && (int)$rescue_id > 0) $currentCentreId = (int)$rescue_id;

$userId = 0;
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['id'])) $userId = (int)$_SESSION['id'];
elseif (isset($user_id)) $userId = (int)$user_id;

if ($currentCentreId <= 0 || $userId <= 0) {
    while (ob_get_level()) ob_end_clean();
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
    exit;
}

// ---------------------------
// Inputs
// ---------------------------
$network_id = isset($_GET['network_id']) ? (int)$_GET['network_id'] : 0;
$q = trim((string)($_GET['q'] ?? ''));
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
if ($limit < 1) $limit = 10;
if ($limit > 25) $limit = 25;

if ($network_id <= 0) {
    while (ob_get_level()) ob_end_clean();
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing network_id']);
    exit;
}

if ($q === '' || mb_strlen($q) < 2) {
    while (ob_get_level()) ob_end_clean();
    echo json_encode([
        'ok' => true,
        'items' => []
    ]);
    exit;
}

// ---------------------------
// Authorisation: must be active admin in this network
// ---------------------------
try {
    $stmt = $pdo->prepare("
        SELECT role, status
        FROM rescue_group_members
        WHERE group_id = :gid
          AND centre_id = :cid
        LIMIT 1
    ");
    $stmt->execute([':gid' => $network_id, ':cid' => $currentCentreId]);
    $mem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mem || ($mem['status'] ?? '') !== 'active' || ($mem['role'] ?? '') !== 'admin') {
        while (ob_get_level()) ob_end_clean();
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Forbidden']);
        exit;
    }

    // ---------------------------
    // Search centres not already in this network (active/pending/invited)
    // No c.deleted filter (your rescue_centres has no deleted column)
    // ---------------------------
    $like = '%' . $q . '%';

    $sql = "
        SELECT
            rc.rescue_id,
            rc.rescue_name,
            cm.centre_profile_image
        FROM rescue_centres rc
        LEFT JOIN rescue_centre_meta cm
          ON cm.centre_id = rc.rescue_id
        WHERE rc.rescue_id <> :mycid
          AND rc.rescue_name LIKE :like
          AND rc.rescue_id NOT IN (
              SELECT centre_id
              FROM rescue_group_members
              WHERE group_id = :gid
                AND status IN ('active','pending','invited')
          )
        ORDER BY rc.rescue_name ASC
        LIMIT {$limit}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':mycid' => $currentCentreId,
        ':like'  => $like,
        ':gid'   => $network_id,
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalise output
    $items = array_map(function ($r) {
        return [
            'id' => (int)($r['rescue_id'] ?? 0),
            'name' => (string)($r['rescue_name'] ?? ''),
            'image' => (string)($r['centre_profile_image'] ?? ''),
        ];
    }, $rows);

    while (ob_get_level()) ob_end_clean();
    echo json_encode([
        'ok' => true,
        'items' => $items
    ]);
    exit;

} catch (Throwable $e) {
    while (ob_get_level()) ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
