<?php
include 'main.php';
include 'getcentreinfo.php';
require_once __DIR__ . '/operations/home_widgets.php';

check_loggedin($pdo);
include_once 'getuserinfo.php';

if (!isset($_SESSION['onboarded']) || (int)$_SESSION['onboarded'] !== 1) {
    header('Location: onboarding.php');
    exit;
}

$accountName = trim((string)($_SESSION['account_name'] ?? ''));
$displayName = $accountName !== '' ? $accountName : 'there';
$rescueDisplayName = trim((string)($_SESSION['rescue_name'] ?? $rescue_name ?? 'your rescue centre'));
$todayLabel = (new DateTime('today'))->format('l j M');
$darkModeEnabled = 0;

try {
    $themeStmt = $pdo->prepare('SELECT dark_mode FROM accounts WHERE id = ? LIMIT 1');
    $themeStmt->execute([(int)$_SESSION['account_id']]);
    $darkModeEnabled = (int)$themeStmt->fetchColumn() === 1 ? 1 : 0;
    $_SESSION['dark_mode'] = $darkModeEnabled;
} catch (Throwable $e) {
    $darkModeEnabled = (int)($_SESSION['dark_mode'] ?? 0) === 1 ? 1 : 0;
}

$homeWidgetsHtml = '';
$learningWidgetHtml = '';
try {
    $homeWidgetsHtml = home_widgets_render($pdo, [
        'centre_id' => (int)($centre_id ?? $_SESSION['centre_id'] ?? 0),
    ]);
} catch (Throwable $e) {
    $homeWidgetsHtml = '';
}



try {
    $learningWidgetPath = __DIR__ . '/modules/learning/views/home_widget.php';
    if (is_file($learningWidgetPath)) {
        ob_start();
        include $learningWidgetPath;
        $learningWidgetHtml = (string)ob_get_clean();
    }
} catch (Throwable $e) {
    $learningWidgetHtml = '';
}

$quickActions = [
    [
        'label' => 'New admission',
        'href' => 'admission.php',
        'meta' => 'Start a new patient record',
        'tone' => 'blue',
        'icon' => '+',
    ],
    [
        'label' => 'My patients',
        'href' => 'patients.php',
        'meta' => 'Review current care records',
        'tone' => 'green',
        'icon' => 'P',
    ],
    [
        'label' => 'Medication round',
        'href' => 'medication.php',
        'meta' => 'Check treatments and medicines',
        'tone' => 'amber',
        'icon' => 'M',
    ],
    [
        'label' => 'Message board',
        'href' => 'messageboard.php',
        'meta' => 'Catch up with staff updates',
        'tone' => 'purple',
        'icon' => 'B',
    ],
];
?>

<?= template_header($rescueDisplayName . ' - Staff Dashboard - Rescue Management System') ?>
<script>
    (function () {
        window.rescueAppTheme = <?= $darkModeEnabled === 1 ? "'dark'" : "'light'" ?>;
        if (window.rescueAppTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    }());
</script>
<link rel="stylesheet" href="core/css/core.css">
<link rel="stylesheet" href="core/css/home.css">
<link rel="stylesheet" href="modules/learning/css/learning.css?v=20260512-home">

<div class="page-title">
    <div class="home-title-main">
        <div class="icon">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M96 128a128 128 0 1 1 256 0A128 128 0 1 1 96 128zM0 482.3C0 383.8 79.8 304 178.3 304h91.4C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3zM504 312v-64h-64c-13.3 0-24-10.7-24-24s10.7-24 24-24h64v-64c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24h-64v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/></svg>
        </div>
        <div class="wrap">
            <h2>Staff dashboard</h2>
            <p><?= htmlspecialchars($todayLabel, ENT_QUOTES) ?> at <?= htmlspecialchars($rescueDisplayName, ENT_QUOTES) ?></p>
        </div>
    </div>
    <button class="app-theme-toggle home-theme-toggle" type="button" data-theme-toggle aria-pressed="<?= $darkModeEnabled === 1 ? 'true' : 'false' ?>">
        <span class="home-theme-track" aria-hidden="true">
            <span class="home-theme-icon home-theme-sun">
                <svg width="14" height="14" viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                    <path d="M12 18.5A6.5 6.5 0 1 0 12 5.5a6.5 6.5 0 0 0 0 13Zm0 3.5a1 1 0 0 1-1-1v-1.25a1 1 0 1 1 2 0V21a1 1 0 0 1-1 1Zm0-17.75a1 1 0 0 1-1-1V2a1 1 0 1 1 2 0v1.25a1 1 0 0 1-1 1ZM3 13H1.75a1 1 0 1 1 0-2H3a1 1 0 1 1 0 2Zm19.25 0H21a1 1 0 1 1 0-2h1.25a1 1 0 1 1 0 2ZM5.64 19.78a1 1 0 0 1-.7-1.7l.88-.89a1 1 0 0 1 1.42 1.42l-.9.88a.98.98 0 0 1-.7.29Zm12.43-12.43a1 1 0 0 1-.7-1.71l.88-.88a1 1 0 1 1 1.42 1.41l-.89.89a.98.98 0 0 1-.7.29Zm-12.25 0a.98.98 0 0 1-.7-.29l-.89-.89a1 1 0 1 1 1.42-1.41l.88.88a1 1 0 0 1-.7 1.71Zm12.43 12.43a.98.98 0 0 1-.7-.29l-.9-.88a1 1 0 0 1 1.43-1.42l.88.89a1 1 0 0 1-.71 1.7Z"/>
                </svg>
            </span>
            <span class="home-theme-thumb"></span>
            <span class="home-theme-icon home-theme-moon">
                <svg width="14" height="14" viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                    <path d="M20.7 15.1a1 1 0 0 1 .18 1.03A9.5 9.5 0 1 1 7.88 3.12a1 1 0 0 1 1.3 1.3 7.5 7.5 0 0 0 10.4 10.4 1 1 0 0 1 1.12.28Z"/>
                </svg>
            </span>
        </span>
        <span class="home-theme-text" data-theme-label><?= $darkModeEnabled === 1 ? 'Light mode' : 'Dark mode' ?></span>
    </button>
</div>

<div class="home-dashboard">
    <section class="rc-panel home-hero">
        <div>
            <span class="home-kicker">Welcome back</span>
            <h1><?= htmlspecialchars($displayName, ENT_QUOTES) ?></h1>
            <p>Here is your working view for today: your duties, key actions, and anything that needs attention.</p>
        </div>
        <div class="home-hero-actions">
            <span class="home-date-pill"><?= htmlspecialchars($todayLabel, ENT_QUOTES) ?></span>
            <a class="btn" href="duties_rota.php">Shift overview</a>
            <a class="btn grey" href="profile.php">Profile</a>
        </div>
    </section>

    <section class="home-main-grid">
        <div class="rc-stack">
            <?= trim($homeWidgetsHtml) !== '' ? $homeWidgetsHtml : '' ?>
            <?= trim($learningWidgetHtml) !== '' ? $learningWidgetHtml : '' ?>

            <div class="rc-panel home-checklist-panel">
                <div class="home-section-head">
                    <h3>Setup attention</h3>
                    <p class="rc-muted">Items that may need an administrator or senior staff member.</p>
                </div>
                <?php include __DIR__ . '/views/config_tasks.php'; ?>
            </div>
        </div>

        <aside class="rc-stack home-sidebar">
            <div class="rc-panel home-quick-panel">
                <div class="home-section-head">
                    <h3>Quick actions</h3>
                    <p class="rc-muted">Common staff workflows, kept close to hand.</p>
                </div>
                <div class="home-action-grid home-action-stack">
                    <?php foreach ($quickActions as $action): ?>
                        <a class="rc-card home-action-card is-<?= htmlspecialchars($action['tone'], ENT_QUOTES) ?>" href="<?= htmlspecialchars($action['href'], ENT_QUOTES) ?>">
                            <span class="home-action-icon"><?= htmlspecialchars($action['icon'], ENT_QUOTES) ?></span>
                            <span class="home-action-copy">
                                <strong><?= htmlspecialchars($action['label'], ENT_QUOTES) ?></strong>
                                <span><?= htmlspecialchars($action['meta'], ENT_QUOTES) ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="rc-panel">
                <div class="home-section-head">
                    <h3>Useful links</h3>
                    <p class="rc-muted">Shortcuts for day-to-day staff work.</p>
                </div>
                <div class="rc-list">
                    <a class="rc-item home-link-item" href="resources.php">
                        <div class="rc-item-main">
                            <strong>Resources</strong>
                            <small>Guides, documents, and reference material</small>
                        </div>
                    </a>
                    <a class="rc-item home-link-item" href="support.php">
                        <div class="rc-item-main">
                            <strong>Support</strong>
                            <small>Get help with the system</small>
                        </div>
                    </a>
                    <a class="rc-item home-link-item" href="groups.php">
                        <div class="rc-item-main">
                            <strong>Networks</strong>
                            <small>Work with connected rescues and partners</small>
                        </div>
                    </a>
                </div>
            </div>
        </aside>
    </section>
</div>

<?= template_footer() ?>
