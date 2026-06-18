<?php
define('APP_LOADED', true);

include 'dashmain.php'; // $pdo
include 'getcentreinfo.php';
require_once __DIR__ . '/operations/permissions.php';

// Register permission for User Accounts section
registerPermission(
    "page_data_management",
    $lang['DATA_PERMISSION_ACCESS'] ?? 'Access to Data Management',
    "page"
);

// Enforce permission
requirePermission("page_data_management");

// ---------------------------
// ✅ TAB ROUTING
// ---------------------------

$tab = $_GET['tab'] ?? 'logs';

// ✅ Whitelist allowed tabs (CRITICAL for security)
$tabRoutes = [
    'logs'      => 'views/logs/index.php',
    'locations' => 'views/location_fix_tool.php',
    'weather'   => 'views/weather.php',
    'review'    => 'views/data_review_queue.php',
    'deleted'   => 'views/data_deleted_records.php',
];

// ✅ Fallback if invalid tab
if (!array_key_exists($tab, $tabRoutes)) {
    $tab = 'logs';
}

// ---------------------------
// ✅ SUCCESS / ERROR ROUTING
// ---------------------------

$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;
$mfaVerified = isset($_GET['mfa']);
?>

<?= template_admin_header(
    ($lang['LM_MANAGE_DATA'] ?? 'Manage Data') . ' - ' . $rescue_name . ' - Rescue Centre - Rescue Management System',
    'management',
    'data'
) ?>


<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M160 96C124.7 96 96 124.7 96 160L96 224C96 259.3 124.7 288 160 288L480 288C515.3 288 544 259.3 544 224L544 160C544 124.7 515.3 96 480 96L160 96zM376 168C389.3 168 400 178.7 400 192C400 205.3 389.3 216 376 216C362.7 216 352 205.3 352 192C352 178.7 362.7 168 376 168zM432 192C432 178.7 442.7 168 456 168C469.3 168 480 178.7 480 192C480 205.3 469.3 216 456 216C442.7 216 432 205.3 432 192zM160 352C124.7 352 96 380.7 96 416L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 416C544 380.7 515.3 352 480 352L160 352zM376 424C389.3 424 400 434.7 400 448C400 461.3 389.3 472 376 472C362.7 472 352 461.3 352 448C352 434.7 362.7 424 376 424zM432 448C432 434.7 442.7 424 456 424C469.3 424 480 434.7 480 448C480 461.3 469.3 472 456 472C442.7 472 432 461.3 432 448z"/></svg>
        </div>
        <div class="txt">
            <h2 class="pagehead"><?= htmlspecialchars($lang['LM_MANAGE_DATA'] ?? 'Manage Data') ?></h2>
            <p><?= htmlspecialchars($lang['DATA_MANAGEMENT_SUBTITLE'] ?? 'Manage audit logs and fix data issues') ?></p>
        </div>   
    </div>
</div>


<div class="rc-stack">

    <!-- ✅ TAB BUTTONS (NOW URL-BASED) -->
    <div class="rc-tabs rc-tabs-pill">
        <a class="rc-tab <?= $tab === 'logs' ? 'is-active' : '' ?>" href="?tab=logs"><?= htmlspecialchars($lang['DATA_LOGS'] ?? 'Data Logs') ?></a>
        <a class="rc-tab <?= $tab === 'locations' ? 'is-active' : '' ?>" href="?tab=locations"><?= htmlspecialchars(($lang['LOCATION'] ?? 'Location') . ' ' . ($lang['LOC_FIX'] ?? 'Fix')) ?></a>
        <a class="rc-tab <?= $tab === 'weather' ? 'is-active' : '' ?>" href="?tab=weather"><?= htmlspecialchars(($lang['WEATHER'] ?? 'Weather') . ' ' . ($lang['LOC_FIX'] ?? 'Fix')) ?></a>
        <a class="rc-tab <?= $tab === 'review' ? 'is-active' : '' ?>" href="?tab=review"><?= htmlspecialchars($lang['DATA_REVIEW_QUEUE'] ?? 'Review Queue') ?></a>
        <a class="rc-tab <?= $tab === 'deleted' ? 'is-active' : '' ?>" href="?tab=deleted"><?= htmlspecialchars($lang['DATA_DELETE_RECOVERY'] ?? 'Delete / Recovery') ?></a>
    </div>

    <!-- ✅ GLOBAL MESSAGE HANDLER -->
    <?php if ($success): ?>
        <div class="rc-alert green">
            ✅ <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="rc-alert red">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($mfaVerified): ?>
        <div class="rc-alert blue">
            <?= htmlspecialchars($lang['DATA_MFA_VERIFIED'] ?? 'Verification complete. Please run the selected action again.') ?>
        </div>
    <?php endif; ?>

    <!-- ✅ ROUTED TAB CONTENT -->
    <div class="rc-tab-panel is-active">
        <?php
        if (is_file(__DIR__ . '/' . $tabRoutes[$tab])) {
            include $tabRoutes[$tab];
        } else {
            echo '<div class="rc-alert amber">' . htmlspecialchars($lang['DATA_TOOL_UNAVAILABLE'] ?? 'This data tool is not available yet.') . '</div>';
        }
        ?>
    </div>

</div>




<?= template_admin_footer() ?>
