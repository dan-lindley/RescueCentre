<?php
$configPath = __DIR__ . '/../config.php';
$schemaPath = __DIR__ . '/../database/schema.sql';
require_once __DIR__ . '/../core/icons.php';

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function post_string($key)
{
    return trim((string)($_POST[$key] ?? ''));
}

function lite_install_sync_connect($apiUrl, $installId, $email, $password)
{
    $apiUrl = trim((string)$apiUrl);
    if ($apiUrl === '') {
        throw new RuntimeException('Hosted sync API URL is required.');
    }

    $payload = json_encode([
        'action' => 'connect',
        'install_id' => $installId,
        'email' => $email,
        'password' => $password,
    ], JSON_UNESCAPED_SLASHES);

    if (!is_string($payload)) {
        throw new RuntimeException('Could not build hosted sync request.');
    }

    $statusCode = 0;
    $body = false;

    if (function_exists('curl_init')) {
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 20,
        ]);
        $body = curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('Hosted sync connection failed: ' . $curlError);
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $payload,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);
        $body = file_get_contents($apiUrl, false, $context);
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
            $statusCode = (int)$match[1];
        }
    }

    if (!is_string($body) || trim($body) === '') {
        throw new RuntimeException('Hosted sync returned an empty response.');
    }

    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Hosted sync returned invalid JSON.');
    }

    if ($statusCode < 200 || $statusCode >= 300 || ($decoded['status'] ?? '') !== 'linked') {
        $message = (string)($decoded['message'] ?? 'Hosted sync refused the connection.');
        throw new RuntimeException($message);
    }

    return $decoded;
}
function write_config($path, array $data)
{
    $contents = "<?php\n\n";
    $contents .= "define('db_host', " . var_export($data['db_host'], true) . ");\n";
    $contents .= "define('db_user', " . var_export($data['db_user'], true) . ");\n";
    $contents .= "define('db_pass', " . var_export($data['db_pass'], true) . ");\n";
    $contents .= "define('db_name', " . var_export($data['db_name'], true) . ");\n";
    $contents .= "define('db_charset', 'utf8mb4');\n";
    $contents .= "define('secret_key', " . var_export($data['secret_key'], true) . ");\n";
    $contents .= "define('base_url', " . var_export($data['base_url'], true) . ");\n";
    $contents .= "define('template_editor', 'textarea');\n\n";
    $contents .= "define('auto_login_after_register', false);\n";
    $contents .= "define('account_activation', false);\n";
    $contents .= "define('account_approval', false);\n\n";
    $contents .= "define('mail_enabled', false);\n";
    $contents .= "define('mail_from', 'noreply@example.org');\n";
    $contents .= "define('mail_name', " . var_export($data['app_name'], true) . ");\n";
    $contents .= "define('notifications_enabled', false);\n";
    $contents .= "define('notification_email', 'notifications@example.org');\n";
    $contents .= "define('SMTP', false);\n";
    $contents .= "define('smtp_host', 'localhost');\n";
    $contents .= "define('smtp_port', 465);\n";
    $contents .= "define('smtp_user', '');\n";
    $contents .= "define('smtp_pass', '');\n";

    if (file_put_contents($path, $contents, LOCK_EX) === false) {
        throw new RuntimeException('Could not write config.php. Check folder permissions.');
    }
}

$errors = [];
$installed = is_file($configPath);

$defaults = [
    'app_name' => 'Rescue Centre Lite',
    'base_url' => '/',
    'default_language' => 'en',
    'db_host' => '127.0.0.1',
    'db_name' => 'rescue_centre_lite',
    'db_user' => 'root',
    'db_pass' => '',
    'centre_name' => '',
    'centre_email' => '',
    'country_code' => 'GB',
    'county' => '',
    'admin_username' => '',
    'admin_email' => '',
    'admin_first_name' => '',
    'admin_last_name' => '',
    'sync_enabled' => '1',
    'hosted_api_url' => 'https://rescuecentre.org.uk/api/lite_sync.php',
    'hosted_email' => '',
    'install_id' => 'lite_' . bin2hex(random_bytes(8)),
];

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$assetBase = basename($scriptDir) === 'install' ? dirname($scriptDir) : $scriptDir;
$assetBase = rtrim($assetBase, '/');
$appHome = ($assetBase === '' ? '/' : $assetBase . '/');
if ($defaults['base_url'] === '/') {
    $defaults['base_url'] = $appHome;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    $data = [];
    foreach ($defaults as $key => $value) {
        $data[$key] = post_string($key);
    }
    $data['db_pass'] = (string)($_POST['db_pass'] ?? '');
    $adminPassword = (string)($_POST['admin_password'] ?? '');
    $adminPasswordConfirm = (string)($_POST['admin_password_confirm'] ?? '');
    $syncPassword = (string)($_POST['hosted_password'] ?? '');
    $syncRequested = (string)($data['sync_enabled'] ?? '') === '1';
    $syncResponse = null;

    if ($syncRequested) {
        if (!filter_var($data['hosted_api_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Hosted sync API URL is not valid.';
        }
        if (!filter_var($data['hosted_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Hosted Rescue Centre account email is required for sync.';
        }
        if ($syncPassword === '') {
            $errors[] = 'Hosted Rescue Centre password is required for sync.';
        }
        if (!preg_match('/^[A-Za-z0-9_-]{6,96}$/', $data['install_id'])) {
            $errors[] = 'Install ID is invalid.';
        }
        if (!$errors) {
            try {
                $syncResponse = lite_install_sync_connect($data['hosted_api_url'], $data['install_id'], $data['hosted_email'], $syncPassword);
                $syncCentre = is_array($syncResponse['centre'] ?? null) ? $syncResponse['centre'] : [];
                $data['centre_name'] = (string)($syncCentre['rescue_name'] ?? $data['centre_name']);
                $data['centre_email'] = (string)($syncCentre['email'] ?? $data['centre_email']);
                $data['county'] = (string)($syncCentre['county'] ?? $data['county']);
                $data['country_code'] = (string)($syncCentre['country_code'] ?? $data['country_code']);
            } catch (Throwable $e) {
                $errors[] = 'Hosted sync failed: ' . $e->getMessage();
            }
        }
    }

    if ($data['db_host'] === '') $errors[] = 'Database host is required.';
    if ($data['db_name'] === '') $errors[] = 'Database name is required.';
    if ($data['db_user'] === '') $errors[] = 'Database user is required.';
    if ($data['centre_name'] === '') $errors[] = 'Centre name is required.';
    if ($data['admin_username'] === '') $errors[] = 'Admin username is required.';
    if (!filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid admin email is required.';
    if (strlen($adminPassword) < 10) $errors[] = 'Admin password must be at least 10 characters.';
    if ($adminPassword !== $adminPasswordConfirm) $errors[] = 'Admin passwords do not match.';
    if (!preg_match('/^[A-Z]{2}$/', strtoupper($data['country_code']))) $errors[] = 'Country code must be two letters, e.g. GB.';
    if (!is_file($schemaPath)) $errors[] = 'database/schema.sql was not found.';

    if (!$errors) {
        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $data['db_host'], $data['db_name']);
            $pdo = new PDO($dsn, $data['db_user'], $data['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $schema = file_get_contents($schemaPath);
            if (!is_string($schema) || trim($schema) === '') {
                throw new RuntimeException('database/schema.sql is empty.');
            }
            $pdo->exec($schema);
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO rescue_centres (rescue_name, email, county, country_code) VALUES (:name, :email, :county, :country_code)');
            $stmt->execute([
                ':name' => $data['centre_name'],
                ':email' => $data['centre_email'] !== '' ? $data['centre_email'] : null,
                ':county' => $data['county'] !== '' ? $data['county'] : null,
                ':country_code' => strtoupper($data['country_code']),
            ]);
            $centreId = (int)$pdo->lastInsertId();

            $pdo->prepare('INSERT INTO rescue_centre_meta (centre_id, centre_bio) VALUES (:centre_id, :bio)')
                ->execute([':centre_id' => $centreId, ':bio' => '']);

            $roleStmt = $pdo->prepare('INSERT INTO rescue_roles (centre_id, role_name, is_default) VALUES (:centre_id, :role_name, :is_default)');
            $roleStmt->execute([':centre_id' => $centreId, ':role_name' => 'Administrator', ':is_default' => 1]);
            $adminRescueRole = (int)$pdo->lastInsertId();
            $roleStmt->execute([':centre_id' => $centreId, ':role_name' => 'Staff', ':is_default' => 1]);

            $accountStmt = $pdo->prepare('INSERT INTO accounts (centre_id, username, email, password, role, rescue_role, first_name, last_name) VALUES (:centre_id, :username, :email, :password, :role, :rescue_role, :first_name, :last_name)');
            $accountStmt->execute([
                ':centre_id' => $centreId,
                ':username' => $data['admin_username'],
                ':email' => $data['admin_email'],
                ':password' => password_hash($adminPassword, PASSWORD_DEFAULT),
                ':role' => 'Admin',
                ':rescue_role' => $adminRescueRole,
                ':first_name' => $data['admin_first_name'] !== '' ? $data['admin_first_name'] : null,
                ':last_name' => $data['admin_last_name'] !== '' ? $data['admin_last_name'] : null,
            ]);

            $settingsStmt = $pdo->prepare('INSERT INTO lite_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value)');
            $settingsStmt->execute([':setting_key' => 'single_centre_id', ':setting_value' => (string)$centreId]);
            $settingsStmt->execute([':setting_key' => 'installed_at', ':setting_value' => date('c')]);
            $settingsStmt->execute([':setting_key' => 'sync_enabled', ':setting_value' => $syncResponse ? '1' : '0']);
            if ($syncResponse) {
                $settingsStmt->execute([':setting_key' => 'sync_provider', ':setting_value' => 'rescue_centre_hosted']);
                $settingsStmt->execute([':setting_key' => 'sync_api_url', ':setting_value' => $data['hosted_api_url']]);
                $settingsStmt->execute([':setting_key' => 'sync_install_id', ':setting_value' => $data['install_id']]);
                $settingsStmt->execute([':setting_key' => 'sync_hosted_centre_id', ':setting_value' => (string)($syncResponse['hosted_centre_id'] ?? '')]);
                $settingsStmt->execute([':setting_key' => 'sync_hosted_account_id', ':setting_value' => (string)($syncResponse['hosted_account_id'] ?? '')]);
                $settingsStmt->execute([':setting_key' => 'sync_api_key', ':setting_value' => (string)($syncResponse['api_key'] ?? '')]);
                $settingsStmt->execute([':setting_key' => 'sync_centre_payload', ':setting_value' => json_encode($syncResponse['centre'] ?? [], JSON_UNESCAPED_SLASHES)]);
                $settingsStmt->execute([':setting_key' => 'sync_last_connected_at', ':setting_value' => date('c')]);
            }
            $pdo->commit();

            write_config($configPath, [
                'app_name' => $data['app_name'] !== '' ? $data['app_name'] : 'Rescue Centre Lite',
                'base_url' => $data['base_url'] !== '' ? $data['base_url'] : $appHome,
                'default_language' => $data['default_language'] !== '' ? $data['default_language'] : 'en',
                'secret_key' => bin2hex(random_bytes(32)),
                'db_host' => $data['db_host'],
                'db_name' => $data['db_name'],
                'db_user' => $data['db_user'],
                'db_pass' => $data['db_pass'],
            ]);

            header('Location: ' . $appHome . '?installed=1');
            exit;
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e->getMessage();
        }
    }
    $defaults = array_merge($defaults, $data ?? []);
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install Rescue Centre Lite</title>
    <link rel="stylesheet" href="<?= h($assetBase . '/core/css/core.css') ?>">
    <style>
        :root { --install-bg:#071c25; --install-card:rgba(18,58,72,.92); --install-border:rgba(139,207,224,.22); --install-text:#eef8fb; --install-muted:#a7c6d0; --install-blue:#0f77a8; --install-green:#18a06d; --install-orange:#d9872b; --install-red:#d85b69; }
        body { min-height:100vh; margin:0; background:linear-gradient(135deg, rgba(4,18,25,.88), rgba(7,28,37,.78)), url("<?= h($assetBase . '/img/june.png') ?>") center / cover fixed no-repeat; color:var(--install-text); font-family:Arial, Helvetica, sans-serif; }
        .install-shell { max-width:1180px; margin:0 auto; padding:36px 18px 48px; }
        .install-logo { width:104px; height:104px; border-radius:26px; display:grid; place-items:center; background:rgba(3,22,30,.42); border:1px solid rgba(255,255,255,.24); box-shadow:0 12px 30px rgba(0,0,0,.28); }
        .install-logo svg { width:72px; height:72px; display:block; }
        .install-hero { border:1px solid var(--install-border); border-radius:24px; padding:28px; margin-bottom:20px; background:linear-gradient(135deg, rgba(10,46,61,.95), rgba(10,31,42,.90)); box-shadow:0 20px 55px rgba(0,0,0,.34); display:flex; justify-content:space-between; gap:20px; align-items:center; backdrop-filter:blur(4px); }
        .install-kicker { margin:0 0 8px; color:#81d8ef; font-size:.78rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; }
        .install-hero h1 { margin:0; color:#fff; font-size:clamp(2rem, 4vw, 3.35rem); line-height:1; letter-spacing:-.045em; }
        .install-hero p { max-width:680px; margin:12px 0 0; color:var(--install-muted); font-size:1rem; }
        .install-card-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:18px; }
        .install-card { border:1px solid var(--install-border); border-radius:20px; background:var(--install-card); box-shadow:0 16px 40px rgba(0,0,0,.22); overflow:hidden; backdrop-filter:blur(3px); }
        .install-card::before { content:""; display:block; height:6px; background:var(--accent, var(--install-blue)); }
        .install-card-inner { padding:20px; }
        .install-card h2 { margin:0 0 4px; color:#fff; font-size:1.24rem; }
        .install-card-note { margin:0 0 16px; color:var(--install-muted); font-size:.92rem; }
        .install-card.application { --accent:var(--install-blue); } .install-card.database { --accent:var(--install-green); } .install-card.centre { --accent:var(--install-orange); } .install-card.admin { --accent:var(--install-red); } .install-card.sync { --accent:#7fd6ee; grid-column:1 / -1; }
        .install-form-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:14px; }
        .install-field-full { grid-column:1 / -1; }
        .install-check { display:flex; align-items:center; gap:10px; color:#d9eef4; font-weight:700; }
        .install-card .xform-label { color:#d9eef4; font-weight:700; }
        .install-card .xform-input, .install-card select.xform-input { width:100%; box-sizing:border-box; background:rgba(5,22,30,.78); color:#f5fbfd; border:1px solid rgba(151,210,225,.24); }
        .install-card .xform-input:focus { outline:none; border-color:rgba(129,216,239,.75); box-shadow:0 0 0 3px rgba(15,119,168,.22); }
        .install-actions { margin-top:20px; display:flex; justify-content:flex-end; }
        .install-actions .btn { min-width:220px; }
        .install-submit { border-radius:999px; padding:12px 22px; font-weight:800; letter-spacing:.01em; box-shadow:0 12px 28px rgba(24,160,109,.28); }
        .install-alert { margin-bottom:18px; }
        @media (max-width:860px) { .install-hero { align-items:flex-start; flex-direction:column; } .install-card-grid { grid-template-columns:1fr; } }
        @media (max-width:560px) { .install-form-grid { grid-template-columns:1fr; } .install-actions .btn { width:100%; } }
    </style>
</head>
<body>
<main class="install-shell">
    <section class="install-hero">
        <div>
            <p class="install-kicker">First run setup</p>
            <h1>Install Rescue Centre Lite</h1>
            <p>Create the database, centre profile and first administrator account for your local Rescue Centre install.</p>
        </div>
        <div class="install-logo" aria-hidden="true"><?= rc_icon('rclogo', 72, 'install-logo-icon') ?></div>
    </section>
    <section>
        <?php if ($installed): ?>
            <div class="rc-alert green install-alert">Rescue Centre Lite is already configured. Remove or protect the install folder.</div>
            <p><a class="btn green" href="<?= h($appHome) ?>">Open Rescue Centre Lite</a></p>
        <?php else: ?>
            <?php if ($errors): ?>
                <div class="rc-alert red install-alert"><strong>Install could not continue:</strong><ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <form method="post" class="xform install-form">
                <div class="install-card-grid">
                    <section class="install-card application"><div class="install-card-inner"><h2>Application</h2><p class="install-card-note">Name the local install and set its public path.</p><div class="install-form-grid"><div class="xform-field install-field-full"><label class="xform-label" for="app_name">Application name</label><input class="xform-input" id="app_name" name="app_name" value="<?= h($defaults['app_name']) ?>"></div><div class="xform-field"><label class="xform-label" for="base_url">Base URL</label><input class="xform-input" id="base_url" name="base_url" value="<?= h($defaults['base_url']) ?>"></div><div class="xform-field"><label class="xform-label" for="default_language">Language</label><select class="xform-input" id="default_language" name="default_language"><?php foreach (['en' => 'English', 'es' => 'Spanish', 'de' => 'German', 'fr' => 'French', 'pl' => 'Polish'] as $code => $label): ?><option value="<?= h($code) ?>" <?= $defaults['default_language'] === $code ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?></select></div></div></div></section>
                    <section class="install-card database"><div class="install-card-inner"><h2>Database</h2><p class="install-card-note">Use the MySQL database and user created in cPanel.</p><div class="install-form-grid"><div class="xform-field"><label class="xform-label" for="db_host">Host</label><input class="xform-input" id="db_host" name="db_host" value="<?= h($defaults['db_host']) ?>" required></div><div class="xform-field"><label class="xform-label" for="db_name">Database</label><input class="xform-input" id="db_name" name="db_name" value="<?= h($defaults['db_name']) ?>" required></div><div class="xform-field"><label class="xform-label" for="db_user">User</label><input class="xform-input" id="db_user" name="db_user" value="<?= h($defaults['db_user']) ?>" required></div><div class="xform-field"><label class="xform-label" for="db_pass">Password</label><input class="xform-input" id="db_pass" name="db_pass" type="password" value="<?= h($defaults['db_pass']) ?>"></div></div></div></section>
                    <section class="install-card centre"><div class="install-card-inner"><h2>Centre</h2><p class="install-card-note">Create the single rescue centre for this Lite install.</p><div class="install-form-grid"><div class="xform-field install-field-full"><label class="xform-label" for="centre_name">Centre name</label><input class="xform-input" id="centre_name" name="centre_name" value="<?= h($defaults['centre_name']) ?>" required></div><div class="xform-field install-field-full"><label class="xform-label" for="centre_email">Centre email</label><input class="xform-input" id="centre_email" name="centre_email" type="email" value="<?= h($defaults['centre_email']) ?>"></div><div class="xform-field"><label class="xform-label" for="country_code">Country code</label><input class="xform-input" id="country_code" name="country_code" maxlength="2" value="<?= h($defaults['country_code']) ?>"></div><div class="xform-field"><label class="xform-label" for="county">County / state</label><input class="xform-input" id="county" name="county" value="<?= h($defaults['county']) ?>"></div></div></div></section>
                    <section class="install-card admin"><div class="install-card-inner"><h2>Admin user</h2><p class="install-card-note">This account will be able to manage the Lite install.</p><div class="install-form-grid"><div class="xform-field"><label class="xform-label" for="admin_first_name">First name</label><input class="xform-input" id="admin_first_name" name="admin_first_name" value="<?= h($defaults['admin_first_name']) ?>"></div><div class="xform-field"><label class="xform-label" for="admin_last_name">Last name</label><input class="xform-input" id="admin_last_name" name="admin_last_name" value="<?= h($defaults['admin_last_name']) ?>"></div><div class="xform-field"><label class="xform-label" for="admin_username">Username</label><input class="xform-input" id="admin_username" name="admin_username" value="<?= h($defaults['admin_username']) ?>" required></div><div class="xform-field"><label class="xform-label" for="admin_email">Email</label><input class="xform-input" id="admin_email" name="admin_email" type="email" value="<?= h($defaults['admin_email']) ?>" required></div><div class="xform-field"><label class="xform-label" for="admin_password">Password</label><input class="xform-input" id="admin_password" name="admin_password" type="password" required></div><div class="xform-field"><label class="xform-label" for="admin_password_confirm">Confirm password</label><input class="xform-input" id="admin_password_confirm" name="admin_password_confirm" type="password" required></div></div></div></section>
                    <section class="install-card sync"><div class="install-card-inner"><h2>Hosted sync</h2><p class="install-card-note">Connect this local install to your hosted Rescue Centre account. Centre details will be checked and synced by default.</p><div class="install-form-grid"><input type="hidden" name="install_id" value="<?= h($defaults['install_id']) ?>"><label class="install-check install-field-full"><input type="checkbox" name="sync_enabled" value="1" <?= (string)$defaults['sync_enabled'] === '1' ? 'checked' : '' ?>> Connect to hosted Rescue Centre</label><div class="xform-field install-field-full"><label class="xform-label" for="hosted_api_url">Hosted API URL</label><input class="xform-input" id="hosted_api_url" name="hosted_api_url" value="<?= h($defaults['hosted_api_url']) ?>"></div><div class="xform-field"><label class="xform-label" for="hosted_email">Hosted account email</label><input class="xform-input" id="hosted_email" name="hosted_email" type="email" value="<?= h($defaults['hosted_email']) ?>"></div><div class="xform-field"><label class="xform-label" for="hosted_password">Hosted password</label><input class="xform-input" id="hosted_password" name="hosted_password" type="password" autocomplete="current-password"></div></div></div></section>
                </div>
                <div class="install-actions"><button class="btn green install-submit" type="submit">Install Rescue Centre Lite</button></div>
            </form>
        <?php endif; ?>
    </section>
</main>
</body>
</html>