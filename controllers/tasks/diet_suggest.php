<?php
/**
 * controllers/tasks/diet_suggest.php
 * JSON-only endpoint for predictive diet search.
 *
 * Fix for: "Unexpected token '<' ... not valid JSON"
 * - That means HTML is being returned (wrapper/login/404 or bootstrap output).
 * - We buffer and discard ALL output from includes, then return clean JSON.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');

// Buffer EVERYTHING so no stray HTML can leak into the JSON response
ob_start();

$term = trim((string)($_POST['term'] ?? ''));
$centre_id = (string)($_POST['centre_id'] ?? '');

// Try to bootstrap PDO: locate main.php by walking up directories
$pdo = null;
$bootstrap_found = null;

$dir = __DIR__;
for ($i = 0; $i < 8; $i++) {
    $candidate = $dir . DIRECTORY_SEPARATOR . 'main.php';
    if (is_file($candidate)) { $bootstrap_found = $candidate; break; }
    $parent = dirname($dir);
    if ($parent === $dir) break;
    $dir = $parent;
}

if ($bootstrap_found) {
    require_once $bootstrap_found;
}

// Pull $pdo from common locations
if (isset($pdo) && ($pdo instanceof PDO)) {
    // ok
} elseif (isset($GLOBALS['pdo']) && ($GLOBALS['pdo'] instanceof PDO)) {
    $pdo = $GLOBALS['pdo'];
} else {
    $pdo = null;
}

// Discard ANY output produced by bootstrap (wrappers, notices, HTML, etc.)
ob_end_clean();

// Now we can safely send JSON headers + output
header('Content-Type: application/json; charset=utf-8');

function rc_json_error(string $msg, array $extra = []): void {
    echo json_encode(array_merge(['error' => $msg], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!($pdo instanceof PDO)) {
    rc_json_error('PDO missing (bootstrap did not create $pdo)', [
        'bootstrap' => $bootstrap_found ? basename($bootstrap_found) : 'not found',
        'path' => $bootstrap_found ?: '',
    ]);
}

if ($centre_id === '') {
    // Fallback: try session keys (only if you use them)
    if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }
    if (!empty($_SESSION['centre_id'])) $centre_id = (string)$_SESSION['centre_id'];
    if ($centre_id === '' && !empty($_SESSION['rescue_centre_id'])) $centre_id = (string)$_SESSION['rescue_centre_id'];
}

if ($centre_id === '') {
    rc_json_error('centre_id missing');
}

if ($term === '' || mb_strlen($term) < 2) {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

// Linked set for "Already added"
$linkedStmt = $pdo->prepare("SELECT diet_item_id FROM rescue_centre_diet_items WHERE centre_id = ?");
$linkedStmt->execute([$centre_id]);
$linkedRows = $linkedStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
$linkedSet = [];
foreach ($linkedRows as $id) $linkedSet[(int)$id] = true;

// Starts-with first (max 5)
$stmt = $pdo->prepare("
    SELECT diet_item_id, name, type, category, default_unit, notes
    FROM rescue_diet_items
    WHERE name LIKE ?
    ORDER BY name ASC
    LIMIT 5
");
$stmt->execute([$term . '%']);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Fill remainder with contains
if (count($rows) < 5) {
    $need = 5 - count($rows);
    $seen = [];
    foreach ($rows as $r) $seen[(int)$r['diet_item_id']] = true;

    $stmt2 = $pdo->prepare("
        SELECT diet_item_id, name, type, category, default_unit, notes
        FROM rescue_diet_items
        WHERE name LIKE ?
        ORDER BY name ASC
        LIMIT $need
    ");
    $stmt2->execute(['%' . $term . '%']);
    $more = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($more as $m) {
        $id = (int)$m['diet_item_id'];
        if (!isset($seen[$id])) $rows[] = $m;
        $seen[$id] = true;
        if (count($rows) >= 5) break;
    }
}

$out = [];
foreach ($rows as $r) {
    $id = (int)$r['diet_item_id'];
    $out[] = [
        'diet_item_id'  => $id,
        'name'          => (string)$r['name'],
        'type'          => (string)$r['type'],
        'category'      => (string)$r['category'],
        'default_unit'  => (string)$r['default_unit'],
        'notes'         => (string)($r['notes'] ?? ''),
        'already_added' => isset($linkedSet[$id]) ? 1 : 0,
    ];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);