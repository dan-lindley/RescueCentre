<?php
if (!defined('APP_LOADED')) exit;

/* ------------------------------------------------------------
   CONTEXT (provided by wrapper)
------------------------------------------------------------ */
$centre_id = $GLOBALS['centre_id'] ?? null;

if (!$centre_id) {
    echo '<div class="rc-alert red">' . htmlspecialchars($lang['CENTRE_CONTEXT_MISSING'] ?? 'Centre context missing.', ENT_QUOTES, 'UTF-8') . '</div>';
    return;
}

/* ------------------------------------------------------------
   DEBUG (optional)
   Use ?debug=1 to show debug banner + catch fatal errors on-screen
------------------------------------------------------------ */
$debug = false;
/*$debug = isset($_GET['debug']) && $_GET['debug'] === '1';

if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);

    // Fatal error catcher (only in debug mode)
    register_shutdown_function(function () {
        $e = error_get_last();
        if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            echo '<div style="margin:10px 0;padding:10px;border:1px solid #f5c6cb;background:#fdecea;color:#b00020;font-family:monospace;white-space:pre-wrap;">';
            echo "FATAL ERROR CAUGHT:\n";
            echo "Type: {$e['type']}\nFile: {$e['file']}\nLine: {$e['line']}\nMessage: {$e['message']}\n";
            echo '</div>';
        }
    });

    echo '<div style="padding:10px;margin:10px 0;background:#000;color:#0f0;font-family:monospace;">'
        . 'VIEW FILE LOADED: views/logs/index.php — ' . date('Y-m-d H:i:s')
        . '</div>';
}*/

/* ------------------------------------------------------------
   HELPERS
------------------------------------------------------------ */
function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// PHP 7/older compatible alternative to str_contains
function safe_contains($haystack, $needle): bool {
    $haystack = (string)$haystack;
    $needle   = (string)$needle;
    if ($needle === '') return true;
    return strpos($haystack, $needle) !== false;
}

// Make diff_arrays resilient to non-array inputs (fixes your fatal error)
function diff_arrays($old = [], $new = []): array
{
    $old = is_array($old) ? $old : [];
    $new = is_array($new) ? $new : [];

    $diff = [];
    foreach ($new as $key => $value) {
        $oldVal = $old[$key] ?? null;
        if ($oldVal != $value) {
            $diff[$key] = ['from' => $oldVal, 'to' => $value];
        }
    }
    return $diff;
}

function action_badge($action): string
{
    global $lang;

    $a = strtolower((string)$action);

    if (safe_contains($a, 'create') || safe_contains($a, 'add')) {
        return '<span class="rc-chip good">' . h($lang['CREATE'] ?? 'Create') . '</span>';
    }
    if (safe_contains($a, 'update') || safe_contains($a, 'edit')) {
        return '<span class="rc-chip warn">' . h($lang['EDIT'] ?? 'Edit') . '</span>';
    }
    if (safe_contains($a, 'delete') || safe_contains($a, 'remove')) {
        return '<span class="rc-badge bad">' . h($lang['DELETE'] ?? 'Delete') . '</span>';
    }
    return '<span class="rc-chip blue">' . h($lang['ACTIONS'] ?? 'Action') . '</span>';
}

/* ------------------------------------------------------------
   FILTERS
------------------------------------------------------------ */
$user_filter   = $_GET['user_id'] ?? '';
$action_filter = $_GET['action'] ?? '';
$module_filter = $_GET['module'] ?? '';
$from_date     = $_GET['from'] ?? '';
$to_date       = $_GET['to'] ?? '';

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 50;
$offset = ($page - 1) * $limit;

/* ------------------------------------------------------------
   QUERY BUILD
------------------------------------------------------------ */
$where  = ['rl.centre_id = :centre_id'];
$params = [':centre_id' => $centre_id];

if ($user_filter !== '') {
    $where[] = 'rl.user_id = :user_id';
    $params[':user_id'] = $user_filter;
}
if ($action_filter !== '') {
    $where[] = 'rl.action LIKE :action';
    $params[':action'] = '%' . $action_filter . '%';
}
if ($module_filter !== '') {
    $where[] = 'rl.module = :module';
    $params[':module'] = $module_filter;
}
if ($from_date !== '') {
    $where[] = 'rl.created_at >= :from';
    $params[':from'] = $from_date . ' 00:00:00';
}
if ($to_date !== '') {
    $where[] = 'rl.created_at <= :to';
    $params[':to'] = $to_date . ' 23:59:59';
}

$whereSql = implode(' AND ', $where);

/* ------------------------------------------------------------
   FETCH LOGS + USER NAMES
   (LIMIT/OFFSET injected as ints to avoid driver quirks)
------------------------------------------------------------ */
$logs = [];
$totalRows = 0;
$totalPages = 1;
$db_error = null;

try {
    // Count
    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM rescue_logs rl
        WHERE $whereSql
    ");
    $countStmt->execute($params);
    $totalRows  = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRows / $limit));

    // Main select
    $sql = "
        SELECT 
            rl.*,
            a.first_name,
            a.last_name
        FROM rescue_logs rl
        LEFT JOIN accounts a ON rl.user_id = a.id
        WHERE $whereSql
        ORDER BY rl.created_at DESC
        LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $db_error = $e->getMessage();
}

/* ------------------------------------------------------------
   CSV EXPORT (exports current page of filtered results)
------------------------------------------------------------ */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audit_logs.csv"');
    $out = fopen('php://output', 'w');

    fputcsv($out, [
        $lang['DATE'] ?? 'Date',
        $lang['USER'] ?? 'User',
        $lang['ACTIONS'] ?? 'Action',
        $lang['MODULE'] ?? 'Module',
        $lang['DATA_ENDPOINT'] ?? 'Endpoint',
    ]);

    foreach ($logs as $log) {
        $user = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: ($lang['SYSTEM'] ?? 'System');
        fputcsv($out, [
            $log['created_at'] ?? '',
            $user,
            $log['action'] ?? '',
            $log['module'] ?? '',
            $log['endpoint'] ?? ''
        ]);
    }
    fclose($out);
    exit;
}
?>

<style>
.diff-old { color:#b00020; text-decoration:line-through; }
.diff-new { color:#0a7c2f; font-weight:600; }
.timeline { font-size:12px; margin-top:4px; color: var(--rc-muted); }

.debugbox { background:#111; color:#0f0; padding:10px; border-radius:8px; font-family:monospace; white-space:pre-wrap; margin:10px 0; }
</style>

<div class="content-title">
    <h2><?= h($lang['DATA_AUDIT_LOGS'] ?? 'Audit Logs') ?></h2>
    <p><?= h($lang['DATA_AUDIT_LOGS_SUBTITLE'] ?? 'Centre activity and patient timeline') ?></p>
</div>

<?php if ($db_error): ?>
    <div class="rc-alert red">
        <strong><?= h($lang['DATA_DATABASE_ERROR'] ?? 'Database error') ?>:</strong> <?= h($db_error) ?>
    </div>
<?php endif; ?>

<?php if ($debug): ?>
<div class="debugbox"><?=
    "DEBUG\n"
  . "centre_id: " . $centre_id . "\n"
  . "whereSql: " . $whereSql . "\n"
  . "params: " . json_encode($params) . "\n"
  . "totalRows: " . $totalRows . "\n"
  . "page/offset: " . $page . " / " . $offset . "\n"
  . "logs count: " . (is_array($logs) ? count($logs) : -1) . "\n"
  . "GET: " . json_encode($_GET) . "\n"
?></div>
<?php endif; ?>

<a class="btn grey" href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>">
    <?= h(($lang['EXPORT'] ?? 'Export') . ' CSV') ?>
</a>

<div class="rc-table-scroll">
<table class="rc-table row-hover">
<colgroup>
    <col style="width:100px">
    <col style="width:150px">
    <col style="width:300px">
    <col style="width:300px">
    <col style="width:200px">
</colgroup>

<thead>
<tr>
    <th><?= h($lang['DATE'] ?? 'Date') ?></th>
    <th><?= h($lang['USER'] ?? 'User') ?></th>
    <th><?= h($lang['ACTIONS'] ?? 'Action') ?></th>
    <th><?= h($lang['MODULE'] ?? 'Module') ?></th>
    <th><?= h($lang['DETAILS'] ?? 'Details') ?></th>
</tr>
</thead>

<tbody>
<?php
if (empty($logs)) {
    echo '<tr><td colspan="5"><em>' . h($lang['DATA_NO_LOG_ENTRIES'] ?? 'No log entries found.') . '</em></td></tr>';
} else {
    foreach ($logs as $log) {

        // Safe decode: could be array/int/string/null depending on stored JSON
        $old = json_decode($log['old_data'] ?? '{}', true);
        $new = json_decode($log['new_data'] ?? '{}', true);

        // Normalize to arrays (critical fix)
        $old = is_array($old) ? $old : [];
        $new = is_array($new) ? $new : [];

        $diff = diff_arrays($old, $new);
        $patient_id = $new['patient_id'] ?? null;

        // Safe date handling: never fatal
        try {
            $dt = new DateTime($log['created_at'] ?? 'now');
        } catch (Throwable $e) {
            $dt = new DateTime('now');
        }

        $user = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')) ?: ($lang['SYSTEM'] ?? 'System');

        echo '<tr>';

        echo '<td>';
        echo h($dt->format('d-m-Y')) . '<br><small>' . h($dt->format('H:i')) . '</small>';
        echo '</td>';

        echo '<td>' . h($user) . '</td>';

        echo '<td>';
        echo action_badge($log['action'] ?? '');
        echo ' ' . h($log['action'] ?? '');
        if ($patient_id) {
            echo '<div class="timeline">' . h($lang['PATIENT'] ?? 'Patient') . ' #' . (int)$patient_id . '</div>';
        }
        echo '</td>';

        echo '<td>' . h($log['module'] ?? '') . '</td>';

        echo '<td>';
        if (!empty($diff)) {
            echo '<details><summary>' . h($lang['CHANGES'] ?? 'Changes') . '</summary><ul>';
            foreach ($diff as $field => $c) {
                echo '<li>';
                echo '<strong>' . h($field) . ':</strong> ';
                echo '<span class="diff-old">' . h($c['from'] ?? '') . '</span> → ';
                echo '<span class="diff-new">' . h($c['to'] ?? '') . '</span>';
                echo '</li>';
            }
            echo '</ul></details>';
        } elseif (!empty($new)) {
            echo '<details><summary>' . h($lang['DATA_PAYLOAD'] ?? 'Payload') . '</summary><pre>' . h(json_encode($new, JSON_PRETTY_PRINT) ?: '') . '</pre></details>';
        } else {
            echo '—';
        }
        echo '</td>';

        echo '</tr>';
    }
}
?>
</tbody>
</table>
</div>

<?php if ($totalPages > 1): ?>
<div class="rc-pager">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a class="rc-pager-btn <?= $i === $page ? 'active' : '' ?>"
       href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
       <?= (int)$i ?>
    </a>
<?php endfor; ?>
</div>
<?php endif; ?>
