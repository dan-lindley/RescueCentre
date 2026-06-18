<?php

declare(strict_types=1);

$configPath = __DIR__ . '/../config.php';
$schemaPath = __DIR__ . '/../database/schema.sql';

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function post_string(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

function write_config(string $path, array $data): void
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
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    $data = [];
    foreach ($defaults as $key => $value) {
        $data[$key] = post_string($key);
    }
    $data['db_pass'] = (string)($_POST['db_pass'] ?? '');
    $adminPassword = (string)($_POST['admin_password'] ?? '');
    $adminPasswordConfirm = (string)($_POST['admin_password_confirm'] ?? '');

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

            $accountStmt = $pdo->prepare('\n                INSERT INTO accounts (centre_id, username, email, password, role, rescue_role, first_name, last_name)\n                VALUES (:centre_id, :username, :email, :password, :role, :rescue_role, :first_name, :last_name)\n            ');
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

            $pdo->commit();

            write_config($configPath, [
                'app_name' => $data['app_name'] !== '' ? $data['app_name'] : 'Rescue Centre Lite',
                'base_url' => $data['base_url'] !== '' ? $data['base_url'] : '/',
                'default_language' => $data['default_language'] !== '' ? $data['default_language'] : 'en',
                'debug' => false,
                'secret_key' => bin2hex(random_bytes(32)),
                'db_host' => $data['db_host'],
                'db_name' => $data['db_name'],
                'db_user' => $data['db_user'],
                'db_pass' => $data['db_pass'],
            ]);

            header('Location: /?installed=1');
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
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install Rescue Centre Lite</title>
    <link rel="stylesheet" href="/core/css/core.css">
</head>
<body>
<main class="rc-page-shell">
    <section class="rc-card rc-card-muted" style="max-width: 980px; margin: 32px auto;">
        <div class="rc-card-header">
            <div>
                <p class="rc-kicker">First run setup</p>
                <h1>Install Rescue Centre Lite</h1>
            </div>
        </div>

        <?php if ($installed): ?>
            <div class="rc-alert green">Rescue Centre Lite is already configured. Remove or protect the install folder.</div>
            <p><a class="btn green" href="/">Open Rescue Centre Lite</a></p>
        <?php else: ?>
            <?php if ($errors): ?>
                <div class="rc-alert red">
                    <strong>Install could not continue:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= h($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="xform">
                <h2>Application</h2>
                <div class="xform-grid">
                    <div class="xform-field span-2">
                        <label class="xform-label" for="app_name">Application name</label>
                        <input class="xform-input" id="app_name" name="app_name" value="<?= h($defaults['app_name']) ?>">
                    </div>
                    <div class="xform-field">
                        <label class="xform-label" for="base_url">Base URL</label>
                        <input class="xform-input" id="base_url" name="base_url" value="<?= h($defaults['base_url']) ?>">
                    </div>
                    <div class="xform-field">
                        <label class="xform-label" for="default_language">Language</label>
                        <select class="xform-input" id="default_language" name="default_language">
                            <?php foreach (['en' => 'English', 'es' => 'Spanish', 'de' => 'German', 'fr' => 'French', 'pl' => 'Polish'] as $code => $label): ?>
                                <option value="<?= h($code) ?>" <?= $defaults['default_language'] === $code ? 'selected' : '' ?>><?= h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <h2>Database</h2>
                <div class="xform-grid">
                    <div class="xform-field">
                        <label class="xform-label" for="db_host">Host</label>
                        <input class="xform-input" id="db_host" name="db_host" value="<?= h($defaults['db_host']) ?>" required>
                    </div>
                    <div class="xform-field">
                        <label class="xform-label" for="db_name">Database</label>
                        <input class="xform-input" id="db_name" name="db_name" value="<?= h($defaults['db_name']) ?>" required>
                    </div>
                    <div class="xform-field">
                        <label class="xform-label" for="db_user">User</label>
                        <input class="xform-input" id="db_user" name="db_user" value="<?= h($defaults['db_user']) ?>" required>
                    </div>
                    <div class="xform-field">
                        <label class="xform-label" for="db_pass">Password</label>
                        <input class="xform-input" id="db_pass" name="db_pass" type="password" value="<?= h($defaults['db_pass']) ?>">
                    </div>
                </div>

                <h2>Centre</h2>
                <div class="xform-grid">
                    <div class="xform-field span-2">
                        <label class="xform-label" for="centre_name">Centre name</label>
                        <input class="xform-input" id="centre_name" name="centre_name" value="<?= h($defaults['centre_name']) ?>" required>
                    </div>
                    <div class="xform-field">
                        <label class="xform-label" for="centre_email">Centre email</label>
                        <input class="xform-input" id="centre_email" name="centre_email" type="email" value="<?= h($defaults['centre_email']) ?>">
                    </div>
                    <div class="xform-field">
                        <label class="xform-label" for="country_code">Country code</label>
                        <input class="xform-input" id="country_code" name="country_code" maxlength="2" value="<?= h($defaults['country_code']) ?>">
                    </div>
                    <div class="xform-field">
                        <label class="xform-label" for="county">County / state</label>
                        <input class="xform-input" id="county" name="county" value="<?= h($defaults['county']) ?>">
                    </div>
                </div>

                <h2>First Admin User</h2>
                <div class="xform-grid">
                    <div class="xform-field">
                        <label class="xform-label" for="admin_first_name">First name</label>
                        <input class="xform-input" id="admin_first_name" name="admin_first_name" value="<?= h($defaults['admin_first_name']) ?>">
                    </div>
                    <div class="xform-field">
                        <label class="xform-label" for="admin_last_name">Last name</label>
                        <input class="xform-input" id="admin_last_name" name="admin_last_name" value="<?= h($defaults['admin_last_name']) ?>">
                    </div>
                    <div class="xform-field">
                        <label class="xform-label" for="admin_username">Username</label>
                        <input class="xform-input" id="admin_username" name="admin_username" value="<?= h($defaults['admin_username']) ?>" required>
                    </div>
                    <div class="xform-field">
                        <label class="xform-label" for="admin_email">Email</label>
                        <input class="xform-input" id="admin_email" name="admin_email" type="email" value="<?= h($defaults['admin_email']) ?>" required>
                    </div>
                    <div class="xform-field span-2">
                        <label class="xform-label" for="admin_password">Password</label>
                        <input class="xform-input" id="admin_password" name="admin_password" type="password" required>
                    </div>
                    <div class="xform-field span-2">
                        <label class="xform-label" for="admin_password_confirm">Confirm password</label>
                        <input class="xform-input" id="admin_password_confirm" name="admin_password_confirm" type="password" required>
                    </div>
                </div>

                <div class="xform-actions">
                    <button class="btn green" type="submit">Install Rescue Centre Lite</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>
</body>
</html>


