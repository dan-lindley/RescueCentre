<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

define('APP_LOADED', true);

include 'dashmain.php'; // $pdo
include 'getcentreinfo.php';
require_once __DIR__ . '/operations/permissions.php';

// Register permission for Centre Management section
registerPermission(
    "page_centre_management",
    "Access to Centre Management Settings Page",
    "page"
);

// Enforce permission
requirePermission("page_centre_management");

// ---------------------------
// âœ… TAB ROUTING
// ---------------------------

$tab = $_GET['tab'] ?? 'centre';

$tabRoutes = [
    'centre'    => 'views/centre_profile.php',
    'profile'   => 'views/centre_profile_preview.php',
    'config'    => 'views/centre_configs.php',
    'bulletins' => 'views/bulletins.php',
    'sync'      => 'views/sync.php',

];

if (!array_key_exists($tab, $tabRoutes)) {
    $tab = 'centre';
}

// ---------------------------
// âœ… SUCCESS / ERROR ROUTING
// ---------------------------

$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;
?>

<?= template_admin_header(
    ($lang['LM_MANAGEMENT'] ?? 'Management') . ' - ' . $rescue_name . ' - Rescue Centre - Rescue Management System',
    'management',
    $tab === 'sync' ? 'sync' : 'settings'
) ?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M144 0a80 80 0 1 1 0 160A80 80 0 1 1 144 0zM512 0a80 80 0 1 1 0 160A80 80 0 1 1 512 0zM0 298.7C0 239.8 47.8 192 106.7 192h42.7c15.9 0 31 3.5 44.6 9.7c-1.3 7.2-1.9 14.7-1.9 22.3c0 38.2 16.8 72.5 43.3 96c-.2 0-.4 0-.7 0H21.3C9.6 320 0 310.4 0 298.7zM405.3 320c-.2 0-.4 0-.7 0c26.6-23.5 43.3-57.8 43.3-96c0-7.6-.7-15-1.9-22.3c13.6-6.3 28.7-9.7 44.6-9.7h42.7C592.2 192 640 239.8 640 298.7c0 11.8-9.6 21.3-21.3 21.3H405.3zM224 224a96 96 0 1 1 192 0 96 96 0 1 1 -192 0zM128 485.3C128 411.7 187.7 352 261.3 352H378.7C452.3 352 512 411.7 512 485.3c0 14.7-11.9 26.7-26.7 26.7H154.7c-14.7 0-26.7-11.9-26.7-26.7z"/></svg>
        </div>
        <div class="txt">
            <h2><?= htmlspecialchars($lang['SETTINGS_CENTRE_SETTINGS']) ?></h2>
            <p><?= htmlspecialchars($lang['SETTINGS_EDIT_CENTRE_SETTINGS']) ?></p>
        </div>
    </div>
</div>
<div class="rc-stack">

    <!-- âœ… TAB BUTTONS (URL-BASED) -->
    <div class="rc-tabs rc-tabs-pill">
        <a class="rc-tab <?= $tab === 'centre' ? 'is-active' : '' ?>" href="?tab=centre"><?= htmlspecialchars($lang['SETTINGS_CENTRE_PROFILE']) ?></a>
        <a class="rc-tab <?= $tab === 'profile' ? 'is-active' : '' ?>" href="?tab=profile"><?= htmlspecialchars($lang['SETTINGS_PROFILE_PAGE']) ?></a>
        <a class="rc-tab <?= $tab === 'config' ? 'is-active' : '' ?>" href="?tab=config"><?= htmlspecialchars($lang['SETTINGS_CONFIGURATION']) ?></a>

        <a class="rc-tab <?= $tab === 'bulletins' ? 'is-active' : '' ?>" href="?tab=bulletins"><?= htmlspecialchars($lang['SETTINGS_BULLETINS']) ?></a>
        <a class="rc-tab <?= $tab === 'sync' ? 'is-active' : '' ?>" href="?tab=sync">Sync</a>
                
    </div>

    <!-- âœ… GLOBAL MESSAGE HANDLER -->
    <?php if ($success): ?>
        <div class="rc-alert green">
            âœ… <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="rc-alert red">
            âŒ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- âœ… ROUTED TAB CONTENT -->
    <div class="rc-tab-panel is-active">
        <?php
        $tabFile = __DIR__ . '/' . $tabRoutes[$tab];
        if (is_file($tabFile)) {
            include $tabFile;
        } else {
            echo '<div class="rc-alert amber">' . htmlspecialchars($lang['SETTINGS_TAB_UNAVAILABLE']) . '</div>';
        }
        ?>
    </div>

</div>

<?= template_admin_footer() ?>

