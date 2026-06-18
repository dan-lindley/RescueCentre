<?php
define('APP_LOADED', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'dashmain.php'; // $pdo
include 'getcentreinfo.php';
require_once __DIR__ . '/operations/permissions.php';

// Register permission for Resources section
registerPermission(
    "page_resources",
    "Access to Resources",
    "page"
);

// Enforce permission
requirePermission("page_resources");

// ---------------------------
// ✅ ADMIN CHECK (DB truth: accounts.role)
// ---------------------------
$account_id = (int)($_SESSION['account_id'] ?? 0);
$isAdmin = false;

if ($account_id > 0) {
    $stmt = $pdo->prepare("SELECT role FROM accounts WHERE id = ? LIMIT 1");
    $stmt->execute([$account_id]);
    $role = (string)($stmt->fetchColumn() ?? '');
    $isAdmin = ($role === 'Admin'); // exact match as specified
}

// ---------------------------
// ✅ TAB ROUTING
// ---------------------------
$tab = $_GET['tab'] ?? 'docs';

// ✅ Whitelist allowed tabs
$tabRoutes = [
    'docs'         => __DIR__ . '/views/resources_view.php',
    'certificates' => __DIR__ . '/views/resources/certificate_generator.php',
];

// ✅ Admin-only tab route + button
if ($isAdmin) {
    $tabRoutes['cert_config'] = __DIR__ . '/views/resources/certificate_config.php';
}

// ✅ Fallback if invalid tab (or non-admin tries to access admin tab)
if (!array_key_exists($tab, $tabRoutes)) {
    $tab = 'docs';
}

// ---------------------------
// ✅ SUCCESS / ERROR ROUTING
// ---------------------------
$success = $_GET['success'] ?? null;
$error   = $_GET['error'] ?? null;
?>

<?= template_admin_header(
    'Resources - ' . $rescue_name . ' - Rescue Centre - Rescue Management System',
    'resources',
    'resources'
) ?>

<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2 class="pagehead">Resources</h2>
            <p>Documents, links and tools for your centre.</p>
        </div>
    </div>
</div>

<div class="rc-stack">

    <div class="rc-tabs rc-tabs-pill">
        <a class="rc-tab <?= $tab === 'docs' ? 'is-active' : '' ?>" href="?tab=docs">Docs / Links</a>
        <a class="rc-tab <?= $tab === 'certificates' ? 'is-active' : '' ?>" href="?tab=certificates">Certificate Generator</a>

        <?php if ($isAdmin): ?>
            <a class="rc-tab <?= $tab === 'cert_config' ? 'is-active' : '' ?>" href="?tab=cert_config">Certificate Config</a>
        <?php endif; ?>
    </div>

    <?php if ($success): ?>
        <div class="rc-alert green"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="rc-alert red"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="rc-tab-panel is-active">
        <?php
        $viewFile = $tabRoutes[$tab];
        if (is_file($viewFile)) {
            include $viewFile;
        } else {
            echo '<div class="rc-alert red">View missing.</div>';
        }
        ?>
    </div>

</div>

<?= template_admin_footer() ?>
