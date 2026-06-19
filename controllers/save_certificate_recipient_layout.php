<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../connection.php'; // provides $pdo

$account_id = (int)($_SESSION['account_id'] ?? 0);
if ($account_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// Check Admin role (accounts.role)
$stmt = $pdo->prepare("SELECT role FROM accounts WHERE id = ? LIMIT 1");
$stmt->execute([$account_id]);
$role = (string)($stmt->fetchColumn() ?? '');
if ($role !== 'Admin') {
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$action = (string)($data['action'] ?? 'save_recipient'); // backward-compatible default

$template_id = (int)($data['template_id'] ?? 0);
$x = $data['x'] ?? null;
$y = $data['y'] ?? null;

if ($template_id <= 0 || !is_numeric($x) || !is_numeric($y)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing/invalid fields']);
    exit;
}

$x = max(0, min(100, (float)$x));
$y = max(0, min(100, (float)$y));

if ($action === 'save_recipient') {

    // Read existing layout_recipient so we only overwrite x/y
    $stmt = $pdo->prepare("SELECT layout_recipient FROM rescue_certificate_templates WHERE template_id = ? LIMIT 1");
    $stmt->execute([$template_id]);
    $existing = (string)($stmt->fetchColumn() ?? '');

    $layout = [];
    if ($existing !== '') {
        $decoded = json_decode($existing, true);
        if (is_array($decoded)) $layout = $decoded;
    }

    $layout['x'] = $x;
    $layout['y'] = $y;

    $newJson = json_encode($layout, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("
        UPDATE rescue_certificate_templates
        SET layout_recipient = ?
        WHERE template_id = ?
        LIMIT 1
    ");

    $ok = $stmt->execute([$newJson, $template_id]);

    echo json_encode([
        'status'  => $ok ? 'success' : 'error',
        'message' => $ok ? 'Saved' : 'Save failed',
        'layout'  => $layout
    ]);
    exit;
}

if ($action === 'save_date') {

    // Read existing layout_admin so we only overwrite layout_admin.date
    $stmt = $pdo->prepare("SELECT layout_admin FROM rescue_certificate_templates WHERE template_id = ? LIMIT 1");
    $stmt->execute([$template_id]);
    $existing = (string)($stmt->fetchColumn() ?? '');

    $layoutAdmin = [];
    if ($existing !== '') {
        $decoded = json_decode($existing, true);
        if (is_array($decoded)) $layoutAdmin = $decoded;
    }

    $layoutAdmin['date'] = ['x' => $x, 'y' => $y];

    $newJson = json_encode($layoutAdmin, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("
        UPDATE rescue_certificate_templates
        SET layout_admin = ?
        WHERE template_id = ?
        LIMIT 1
    ");

    $ok = $stmt->execute([$newJson, $template_id]);

    echo json_encode([
        'status'  => $ok ? 'success' : 'error',
        'message' => $ok ? 'Saved' : 'Save failed',
        'layout'  => ['date' => ['x' => $x, 'y' => $y]]
    ]);
    exit;
}
if ($action === 'save_logo') {

    // diameter optional, default 200
    $d = $data['d'] ?? 200;
    if (!is_numeric($d)) $d = 200;
    $d = (int)$d;

    // keep sane (optional)
    if ($d < 50)  $d = 50;
    if ($d > 500) $d = 500;

    // Read existing layout_admin so we only overwrite layout_admin.logo
    $stmt = $pdo->prepare("SELECT layout_admin FROM rescue_certificate_templates WHERE template_id = ? LIMIT 1");
    $stmt->execute([$template_id]);
    $existing = (string)($stmt->fetchColumn() ?? '');

    $layoutAdmin = [];
    if ($existing !== '') {
        $decoded = json_decode($existing, true);
        if (is_array($decoded)) $layoutAdmin = $decoded;
    }

    $layoutAdmin['logo'] = ['x' => $x, 'y' => $y, 'd' => $d];

    $newJson = json_encode($layoutAdmin, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("
        UPDATE rescue_certificate_templates
        SET layout_admin = ?
        WHERE template_id = ?
        LIMIT 1
    ");

    $ok = $stmt->execute([$newJson, $template_id]);

    echo json_encode([
        'status'  => $ok ? 'success' : 'error',
        'message' => $ok ? 'Saved' : 'Save failed',
        'layout'  => ['logo' => ['x' => $x, 'y' => $y, 'd' => $d]]
    ]);
    exit;
}
if ($action === 'save_rescue_name') {

    // Read existing layout_admin so we only overwrite layout_admin.rescue_name
    $stmt = $pdo->prepare("SELECT layout_admin FROM rescue_certificate_templates WHERE template_id = ? LIMIT 1");
    $stmt->execute([$template_id]);
    $existing = (string)($stmt->fetchColumn() ?? '');

    $layoutAdmin = [];
    if ($existing !== '') {
        $decoded = json_decode($existing, true);
        if (is_array($decoded)) $layoutAdmin = $decoded;
    }

    $layoutAdmin['rescue_name'] = ['x' => $x, 'y' => $y];

    $newJson = json_encode($layoutAdmin, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("
        UPDATE rescue_certificate_templates
        SET layout_admin = ?
        WHERE template_id = ?
        LIMIT 1
    ");

    $ok = $stmt->execute([$newJson, $template_id]);

    echo json_encode([
        'status'  => $ok ? 'success' : 'error',
        'message' => $ok ? 'Saved' : 'Save failed',
        'layout'  => ['rescue_name' => ['x' => $x, 'y' => $y]]
    ]);
    exit;
}

// Unknown action
echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
exit;
