<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$appName = h($config['app']['name'] ?? 'Rescue Centre Lite');
$installedMessage = isset($_GET['installed']);
$centre = null;
$userCount = 0;

try {
    $centre = $pdo->query('SELECT rescue_id, rescue_name, email, county, country_code FROM rescue_centres ORDER BY rescue_id ASC LIMIT 1')->fetch() ?: null;
    $userCount = (int)$pdo->query('SELECT COUNT(*) FROM accounts')->fetchColumn();
} catch (Throwable $e) {
    $centre = null;
}
?>
<!doctype html>
<html lang="<?= h($_SESSION['lang'] ?? 'en') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $appName ?></title>
    <link rel="icon" href="<?= h(public_asset('img/favicon.ico')) ?>">
    <link rel="stylesheet" href="<?= h(public_asset('css/core.css')) ?>">
    <link rel="stylesheet" href="<?= h(public_asset('css/home.css')) ?>">
</head>
<body>
    <main class="rc-page-shell">
        <section class="rc-card rc-card-muted" style="max-width: 920px; margin: 40px auto;">
            <div class="rc-card-header">
                <div>
                    <p class="rc-kicker">Single-centre local install</p>
                    <h1><?= $appName ?></h1>
                </div>
                <img src="<?= h(public_asset('img/logo-square-white.png')) ?>" alt="" style="width:72px; height:auto; background:#0b3a6f; border-radius:14px; padding:10px;">
            </div>

            <?php if ($installedMessage): ?>
                <div class="rc-alert green">Install complete. You can now remove or protect the <code>install</code> folder.</div>
            <?php endif; ?>

            <?php if ($centre): ?>
                <div class="rc-stats-grid">
                    <div class="rc-stat-card">
                        <span class="rc-stat-label">Centre</span>
                        <strong><?= h($centre['rescue_name']) ?></strong>
                    </div>
                    <div class="rc-stat-card">
                        <span class="rc-stat-label">Location</span>
                        <strong><?= h(trim((string)($centre['county'] ?? '') . ' ' . (string)($centre['country_code'] ?? '')) ?: 'Not set') ?></strong>
                    </div>
                    <div class="rc-stat-card">
                        <span class="rc-stat-label">Users</span>
                        <strong><?= h((string)$userCount) ?></strong>
                    </div>
                </div>
                <p>The Lite base is installed. The next extraction step is login, then centre management and admissions.</p>
            <?php else: ?>
                <p>This clean Lite shell is ready for first-run setup.</p>
            <?php endif; ?>

            <div class="rc-button-row">
                <a class="btn green" href="/install/">Installer</a>
                <a class="btn" href="https://github.com/" aria-disabled="true">GitHub later</a>
            </div>
        </section>
    </main>
    <script src="<?= h(public_asset('js/theme.js')) ?>"></script>
</body>
</html>
