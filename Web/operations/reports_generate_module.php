<?php
define('APP_LOADED', true);

include __DIR__ . '/../dashmain.php';      // $pdo
include __DIR__ . '/../getcentreinfo.php'; // expects $centre_id, $rescue_name etc.
require_once __DIR__ . '/../operations/permissions.php';

// ----------------------------------
// ✅ PERMISSIONS
// ----------------------------------
registerPermission(
    "page_centre_reports",
    "Access to Rescue Reports Page",
    "page"
);
requirePermission("page_centre_reports");

// ----------------------------------
// ✅ INPUT
// ----------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../reports.php?tab=single&error=" . urlencode("Invalid request method."));
    exit;
}

$module_code = trim($_POST['module_code'] ?? '');
if ($module_code === '') {
    header("Location: ../reports.php?tab=single&error=" . urlencode("Missing module code."));
    exit;
}

// ----------------------------------
// ✅ LOAD REPORTING WINDOW (centre_meta)
// ----------------------------------
$stmt = $pdo->prepare("
    SELECT reporting_from, reporting_to
    FROM rescue_centre_meta
    WHERE centre_id = :centre_id
    LIMIT 1
");
$stmt->execute([':centre_id' => $centre_id]);
$meta = $stmt->fetch(PDO::FETCH_ASSOC);

$from_date = $meta['reporting_from'] ?? null;
$to_date   = $meta['reporting_to'] ?? null;

if (!$from_date || !$to_date) {
    header("Location: ../reports.php?tab=single&error=" . urlencode("Reporting date range is not set. Please set From/To dates first."));
    exit;
}

// Basic YYYY-MM-DD validation
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_date)) {
    header("Location: ../reports.php?tab=single&error=" . urlencode("Invalid reporting dates stored in centre meta."));
    exit;
}
if ($from_date > $to_date) {
    header("Location: ../reports.php?tab=single&error=" . urlencode("Reporting date range is invalid (From > To)."));
    exit;
}

// ----------------------------------
// ✅ LOAD MODULE
// ----------------------------------
$modStmt = $pdo->prepare("
    SELECT code, name, description, query_path
    FROM rescue_reports_modules
    WHERE code = :code AND is_active = 1
    LIMIT 1
");
$modStmt->execute([':code' => $module_code]);
$module = $modStmt->fetch(PDO::FETCH_ASSOC);

if (!$module) {
    header("Location: ../reports.php?tab=single&error=" . urlencode("Unknown or inactive module: {$module_code}"));
    exit;
}

$query_path = $module['query_path'] ?? '';
if ($query_path === '') {
    header("Location: ../reports.php?tab=single&error=" . urlencode("Module has no query_path set."));
    exit;
}

// ----------------------------------
// ✅ RESOLVE SQL FILE PATH SAFELY
// Convention: query_path stored like "reporting/CASE_INDEX.sql"
// Real path should be: models/reporting/CASE_INDEX.sql
// ----------------------------------
if (strpos($query_path, '..') !== false) {
    header("Location: ../reports.php?tab=single&error=" . urlencode("Invalid query_path (path traversal)."));
    exit;
}
if (strpos($query_path, 'reporting/') !== 0) {
    header("Location: ../reports.php?tab=single&error=" . urlencode("Invalid query_path (must start with reporting/)."));
    exit;
}

$modelsRoot = realpath(__DIR__ . '/../models');
if ($modelsRoot === false) {
    header("Location: ../reports.php?tab=single&error=" . urlencode("Models folder not found."));
    exit;
}

$sqlFile = $modelsRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $query_path);

// Ensure file exists inside models directory
$realSqlFile = realpath($sqlFile);
if ($realSqlFile === false || strpos($realSqlFile, $modelsRoot) !== 0 || !is_file($realSqlFile)) {
    header("Location: ../reports.php?tab=single&error=" . urlencode("SQL file not found for module: {$query_path}"));
    exit;
}

$sql = file_get_contents($realSqlFile);
if ($sql === false || trim($sql) === '') {
    header("Location: ../reports.php?tab=single&error=" . urlencode("SQL file is empty: {$query_path}"));
    exit;
}

// ----------------------------------
// ✅ EXECUTE MODULE QUERY (Pattern A params)
// ----------------------------------
try {
    $q = $pdo->prepare($sql);
    $q->execute([
        ':centre_id' => $centre_id,
        ':from_date' => $from_date,
        ':to_date'   => $to_date
    ]);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Avoid leaking internals; you can log $e->getMessage() server-side
    header("Location: ../reports.php?tab=single&error=" . urlencode("Failed to generate report module."));
    exit;
}

// ----------------------------------
// ✅ RENDER OUTPUT (simple HTML table)
// ----------------------------------
echo template_admin_header(
    'Report - ' . $module['name'] . ' - ' . $rescue_name,
    'management',
    'reports'
);
?>

<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2><?= htmlspecialchars($module['name']) ?></h2>
            <p>
                Module: <code><?= htmlspecialchars($module['code']) ?></code>
                &nbsp;|&nbsp; Period:
                <strong><?= htmlspecialchars($from_date) ?></strong> to <strong><?= htmlspecialchars($to_date) ?></strong>
            </p>
        </div>
    </div>
</div>

<div class="tab-container">
    <div class="panel" style="margin-bottom:12px;">
        <a class="btn btn-secondary" href="../reports.php?tab=single">← Back to Single Reports</a>
    </div>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info">No data found for this period.</div>
    <?php else: ?>
        <div class="panel">
            <table class="table">
                <thead>
                    <tr>
                        <?php foreach (array_keys($rows[0]) as $col): ?>
                            <th><?= htmlspecialchars($col) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <?php foreach ($r as $val): ?>
                                <td><?= htmlspecialchars((string)$val) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?= template_admin_footer(); ?>
