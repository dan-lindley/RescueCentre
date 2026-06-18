<?php
define('APP_LOADED', true);

include 'dashmain.php'; // $pdo
include 'getcentreinfo.php'; // expects to set $centre_id, $rescue_name, etc.
require_once __DIR__ . '/operations/permissions.php';

// ---------------------------
// ✅ PERMISSIONS
// ---------------------------
registerPermission(
    "page_centre_reports",
    "Access to Rescue Reports Page",
    "page"
);
requirePermission("page_centre_reports");

// ---------------------------
// ✅ TAB ROUTING
// ---------------------------
$tab = $_GET['tab'] ?? 'single';

$tabRoutes = [
    'single'   => 'views/reports/single.php',
    'builder'  => 'views/reports/builder.php',
    'previous' => 'views/reports/previous.php'
];

if (!array_key_exists($tab, $tabRoutes)) {
    $tab = 'single';
}

// ---------------------------
// ✅ SUCCESS / ERROR ROUTING
// ---------------------------
$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;

// ---------------------------
// ✅ LOAD reporting_from / reporting_to from centre_meta
// ---------------------------
// Assumes 1 row per centre in rescue_centre_meta.
// If your schema differs, tweak the SELECT accordingly.
$stmt = $pdo->prepare("
    SELECT reporting_from, reporting_to
    FROM rescue_centre_meta
    WHERE centre_id = :centre_id
    LIMIT 1
");
$stmt->execute([':centre_id' => $centre_id]);
$meta = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$reporting_from = $meta['reporting_from'] ?? null;
$reporting_to   = $meta['reporting_to'] ?? null;

// Provide defaults if empty (choose what fits your system)
if (!$reporting_from || !$reporting_to) {
    // Default: last 30 days inclusive
    $reporting_to = date('Y-m-d');
    $reporting_from = date('Y-m-d', strtotime('-30 days'));
}

// ---------------------------
// ✅ HANDLE DATE RANGE UPDATE (Pattern A context storage)
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_reporting_range') {
    $from = trim($_POST['reporting_from'] ?? '');
    $to   = trim($_POST['reporting_to'] ?? '');

    // Basic validation
    $fromOk = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from);
    $toOk   = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to);

    if (!$fromOk || !$toOk) {
        header("Location: reports.php?tab={$tab}&error=" . urlencode("Invalid date format. Use YYYY-MM-DD."));
        exit;
    }
    if ($from > $to) {
        header("Location: reports.php?tab={$tab}&error=" . urlencode("From date must be before or equal to To date."));
        exit;
    }

    // Update centre_meta
    $upd = $pdo->prepare("
        UPDATE rescue_centre_meta
        SET reporting_from = :from_date,
            reporting_to   = :to_date
        WHERE centre_id = :centre_id
        LIMIT 1
    ");
    $upd->execute([
        ':from_date' => $from,
        ':to_date'   => $to,
        ':centre_id' => $centre_id
    ]);

    header("Location: reports.php?tab={$tab}&success=" . urlencode("Reporting date range updated."));
    exit;
}

?>
<?= template_admin_header(
    'Reports - ' . $rescue_name . ' - Rescue Centre - Rescue Management System',
    'management',
    'reports'
) ?>
<div class="content-title">
    <div class="title">
        <div class="icon">
            <!-- Pick any icon you like -->
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                <path d="M64 0C28.7 0 0 28.7 0 64V448c0 35.3 28.7 64 64 64H384c17 0 33.3-6.7 45.3-18.7l64-64c12-12 18.7-28.3 18.7-45.3V64c0-35.3-28.7-64-64-64H64zm0 64H448V320H352c-17.7 0-32 14.3-32 32v96H64V64z"/>
            </svg>
        </div>
        <div class="txt">
            <h2>Reports</h2>
            <p>Generate single modules, build combined reports, and view previous outputs</p>
        </div>
    </div>
</div>

<div class="rc-stack">

    <!-- ✅ DATE RANGE FORM (GLOBAL FOR ALL REPORTING TABS) -->
    <div class="rc-panel">
        <form method="post" action="reports.php?tab=<?= htmlspecialchars($tab) ?>" class="xform">
            <input type="hidden" name="action" value="update_reporting_range">

            <div class="xform-grid">
                <div class="xform-field">
                    <label class="xform-label">From</label>
                    <input class="xform-input" type="date" name="reporting_from" value="<?= htmlspecialchars($reporting_from) ?>" required>
                </div>

                <div class="xform-field">
                    <label class="xform-label">To</label>
                    <input class="xform-input" type="date" name="reporting_to" value="<?= htmlspecialchars($reporting_to) ?>" required>
                </div>

                <div class="xform-actions">
                    <button type="submit" class="btn green">Update</button>
                </div>
            </div>
        </form>
    </div>

    <!-- ✅ TAB BUTTONS (URL-BASED) -->
    <div class="rc-tabs rc-tabs-pill">
        <a class="rc-tab <?= $tab === 'single' ? 'is-active' : '' ?>" href="?tab=single">Single Reports</a>
        <a class="rc-tab <?= $tab === 'builder' ? 'is-active' : '' ?>" href="?tab=builder">Report Builder</a>
        <a class="rc-tab <?= $tab === 'previous' ? 'is-active' : '' ?>" href="?tab=previous">Previous Reports</a>
    </div>

    <!-- ✅ GLOBAL MESSAGE HANDLER -->
    <?php if ($success): ?>
        <div class="rc-alert green">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="rc-alert red">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ✅ ROUTED TAB CONTENT -->
    <div class="rc-tab-panel is-active">
        <?php
        // Make centre_id and reporting dates available to tabs
        // (Pattern A: runner binds these; tabs can show them / pass them to generator)
        $REPORTING_CONTEXT = [
            'centre_id' => $centre_id,
            'from_date' => $reporting_from,
            'to_date'   => $reporting_to
        ];

        if (is_file(__DIR__ . '/' . $tabRoutes[$tab])) {
            include $tabRoutes[$tab];
        } else {
            echo '<div class="rc-alert amber">This report tool is not available yet.</div>';
        }
        ?>
    </div>

</div>

<?= template_admin_footer() ?>
