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

function lite_install_sync_connect($apiUrl, array $data)
{
    $apiUrl = trim((string)$apiUrl);
    if ($apiUrl === '') {
        throw new RuntimeException('Hosted sync API URL is required.');
    }

    $payload = json_encode([
        'action' => 'setup',
        'install_id' => $data['install_id'],
        'centre_name' => $data['centre_name'],
        'centre_email' => $data['centre_email'],
        'county' => $data['county'],
        'country_code' => $data['country_code'],
        'admin_username' => $data['admin_username'],
        'admin_email' => $data['admin_email'],
        'admin_password' => $data['admin_password'],
        'admin_first_name' => $data['admin_first_name'],
        'admin_last_name' => $data['admin_last_name'],
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

function lite_install_sync_check($apiUrl, array $data)
{
    $apiUrl = trim((string)$apiUrl);
    if ($apiUrl === '') {
        throw new RuntimeException('Hosted sync API URL is required.');
    }

    $query = http_build_query([
        'install_id' => $data['install_id'] ?? '',
        'centre_name' => $data['centre_name'] ?? '',
        'centre_email' => $data['centre_email'] ?? '',
        'admin_username' => $data['admin_username'] ?? '',
        'admin_email' => $data['admin_email'] ?? '',
    ]);
    $url = $apiUrl . (strpos($apiUrl, '?') !== false ? '&' : '?') . $query;
    $statusCode = 0;
    $body = false;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_TIMEOUT => 12,
        ]);
        $body = curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('Hosted check connection failed: ' . $curlError);
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\n",
                'timeout' => 12,
                'ignore_errors' => true,
            ],
        ]);
        $body = file_get_contents($url, false, $context);
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
            $statusCode = (int)$match[1];
        }
    }

    if (!is_string($body) || trim($body) === '') {
        throw new RuntimeException('Hosted check returned an empty response.');
    }

    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        $preview = trim(strip_tags((string)$body));
        $preview = preg_replace('/\s+/', ' ', $preview);
        $preview = substr((string)$preview, 0, 180);
        throw new RuntimeException('Hosted check returned invalid JSON' . ($preview !== '' ? ': ' . $preview : '.'));
    }

    if ($statusCode < 200 || $statusCode >= 300) {
        throw new RuntimeException((string)($decoded['message'] ?? 'Hosted check failed.'));
    }

    return $decoded;
}

function lite_install_sync_auth($apiUrl, array $data)
{
    $apiUrl = trim((string)$apiUrl);
    if ($apiUrl === '') {
        throw new RuntimeException('Hosted sync API URL is required.');
    }

    $payload = json_encode([
        'action' => 'auth',
        'install_id' => $data['install_id'] ?? '',
        'centre_name' => $data['centre_name'] ?? '',
        'centre_email' => $data['centre_email'] ?? '',
        'admin_email' => $data['admin_email'] ?? '',
        'admin_password' => $data['admin_password'] ?? '',
    ], JSON_UNESCAPED_SLASHES);

    if (!is_string($payload)) {
        throw new RuntimeException('Could not build hosted auth request.');
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
            CURLOPT_TIMEOUT => 12,
        ]);
        $body = curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('Hosted auth connection failed: ' . $curlError);
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $payload,
                'timeout' => 12,
                'ignore_errors' => true,
            ],
        ]);
        $body = file_get_contents($apiUrl, false, $context);
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
            $statusCode = (int)$match[1];
        }
    }

    if (!is_string($body) || trim($body) === '') {
        throw new RuntimeException('Hosted auth returned an empty response.');
    }

    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Hosted auth returned invalid JSON.');
    }

    if ($statusCode < 200 || $statusCode >= 300 || ($decoded['status'] ?? '') !== 'authenticated') {
        throw new RuntimeException((string)($decoded['message'] ?? 'Hosted authentication failed.'));
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
    'hosted_api_url' => 'https://myrescuecentre.com/api/lite_sync.php',
    'download_hosted_data' => '0',
    'install_mode' => 'existing',
    'install_id' => 'lite_' . bin2hex(random_bytes(8)),
];

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$assetBase = basename($scriptDir) === 'install' ? dirname($scriptDir) : $scriptDir;
$assetBase = rtrim($assetBase, '/');
$appHome = ($assetBase === '' ? '/' : $assetBase . '/');
if ($defaults['base_url'] === '/') {
    $defaults['base_url'] = $appHome;
}

if (isset($_GET['lite_check']) && !$installed) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    try {
        echo json_encode(lite_install_sync_check((string)($_GET['hosted_api_url'] ?? $defaults['hosted_api_url']), [
            'install_id' => (string)($_GET['install_id'] ?? ''),
            'centre_name' => (string)($_GET['centre_name'] ?? ''),
            'centre_email' => (string)($_GET['centre_email'] ?? ''),
            'admin_username' => (string)($_GET['admin_username'] ?? ''),
            'admin_email' => (string)($_GET['admin_email'] ?? ''),
        ]), JSON_UNESCAPED_SLASHES);
    } catch (Throwable $e) {
        http_response_code(502);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], JSON_UNESCAPED_SLASHES);
    }
    exit;
}

if (isset($_GET['lite_auth']) && !$installed) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    try {
        $raw = file_get_contents('php://input');
        $request = is_string($raw) && trim($raw) !== '' ? json_decode($raw, true) : [];
        if (!is_array($request)) {
            throw new RuntimeException('Invalid auth request.');
        }
        echo json_encode(lite_install_sync_auth((string)($request['hosted_api_url'] ?? $defaults['hosted_api_url']), [
            'install_id' => (string)($request['install_id'] ?? ''),
            'centre_name' => (string)($request['centre_name'] ?? ''),
            'centre_email' => (string)($request['centre_email'] ?? ''),
            'admin_email' => (string)($request['admin_email'] ?? ''),
            'admin_password' => (string)($request['admin_password'] ?? ''),
        ]), JSON_UNESCAPED_SLASHES);
    } catch (Throwable $e) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], JSON_UNESCAPED_SLASHES);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    $data = [];
    foreach ($defaults as $key => $value) {
        $data[$key] = post_string($key);
    }
    $data['db_pass'] = (string)($_POST['db_pass'] ?? '');
    $adminPassword = (string)($_POST['admin_password'] ?? '');
    $adminPasswordConfirm = (string)($_POST['admin_password_confirm'] ?? '');
    $syncResponse = null;
    $syncEnabled = in_array($data['install_mode'], ['existing', 'new'], true);

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
    if (!in_array($data['install_mode'], ['existing', 'new', 'local'], true)) $errors[] = 'Choose an install mode.';
    if ($syncEnabled && !filter_var($data['hosted_api_url'], FILTER_VALIDATE_URL)) $errors[] = 'Hosted sync API URL is not valid.';
    if (!preg_match('/^[A-Za-z0-9_-]{6,96}$/', $data['install_id'])) $errors[] = 'Install ID is invalid.';


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

            if ($syncEnabled) {
                try {
                    $data['admin_password'] = $adminPassword;
                    $syncResponse = lite_install_sync_connect($data['hosted_api_url'], $data);
                    $syncCentre = is_array($syncResponse['centre'] ?? null) ? $syncResponse['centre'] : [];
                    $data['centre_name'] = (string)($syncCentre['rescue_name'] ?? $data['centre_name']);
                    $data['centre_email'] = (string)($syncCentre['email'] ?? $data['centre_email']);
                    $data['county'] = (string)($syncCentre['county'] ?? $data['county']);
                    $data['country_code'] = (string)($syncCentre['country_code'] ?? $data['country_code']);
                } catch (Throwable $e) {
                    throw new RuntimeException('Hosted setup check failed: ' . $e->getMessage());
                }
            }

            $pdo->beginTransaction();

            $centreId = $syncEnabled ? null : -1;
            $stmt = $syncEnabled
                ? $pdo->prepare('INSERT INTO rescue_centres (rescue_name, email, county, country_code) VALUES (:name, :email, :county, :country_code)')
                : $pdo->prepare('INSERT INTO rescue_centres (rescue_id, rescue_name, email, county, country_code) VALUES (:rescue_id, :name, :email, :county, :country_code)');
            $params = [
                ':name' => $data['centre_name'],
                ':email' => $data['centre_email'] !== '' ? $data['centre_email'] : null,
                ':county' => $data['county'] !== '' ? $data['county'] : null,
                ':country_code' => strtoupper($data['country_code']),
            ];
            if (!$syncEnabled) {
                $params[':rescue_id'] = -1;
            }
            $stmt->execute($params);
            if ($syncEnabled) {
                $centreId = (int)$pdo->lastInsertId();
            }

            $pdo->prepare('INSERT INTO rescue_centre_meta (centre_id, centre_bio) VALUES (:centre_id, :bio)')
                ->execute([':centre_id' => $centreId, ':bio' => '']);

            $roleStmt = $pdo->prepare('INSERT INTO rescue_roles (centre_id, role_name, is_default) VALUES (:centre_id, :role_name, :is_default)');
            $roleStmt->execute([':centre_id' => $centreId, ':role_name' => 'Administrator', ':is_default' => 1]);
            $roleStmt->execute([':centre_id' => $centreId, ':role_name' => 'Staff', ':is_default' => 1]);

            $accountStmt = $pdo->prepare('INSERT INTO accounts (centre_id, username, email, password, role, rescue_role, first_name, last_name) VALUES (:centre_id, :username, :email, :password, :role, :rescue_role, :first_name, :last_name)');
            $accountStmt->execute([
                ':centre_id' => $centreId,
                ':username' => $data['admin_username'],
                ':email' => $data['admin_email'],
                ':password' => password_hash($adminPassword, PASSWORD_DEFAULT),
                ':role' => 'Member',
                ':rescue_role' => 1,
                ':first_name' => $data['admin_first_name'] !== '' ? $data['admin_first_name'] : null,
                ':last_name' => $data['admin_last_name'] !== '' ? $data['admin_last_name'] : null,
            ]);

            $settingsStmt = $pdo->prepare('INSERT INTO lite_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value)');
            $settingsStmt->execute([':setting_key' => 'single_centre_id', ':setting_value' => (string)$centreId]);
            $settingsStmt->execute([':setting_key' => 'installed_at', ':setting_value' => date('c')]);
            $settingsStmt->execute([':setting_key' => 'sync_enabled', ':setting_value' => $syncEnabled ? '1' : '0']);
            $settingsStmt->execute([':setting_key' => 'sync_mode', ':setting_value' => $data['install_mode']]);
            $settingsStmt->execute([':setting_key' => 'sync_download_requested', ':setting_value' => $syncEnabled && $data['download_hosted_data'] === '1' ? '1' : '0']);
            if ($syncResponse) {
                $settingsStmt->execute([':setting_key' => 'sync_provider', ':setting_value' => 'rescue_centre_hosted']);
                $settingsStmt->execute([':setting_key' => 'sync_api_url', ':setting_value' => $data['hosted_api_url']]);
                $settingsStmt->execute([':setting_key' => 'sync_install_id', ':setting_value' => $data['install_id']]);
                $settingsStmt->execute([':setting_key' => 'sync_hosted_centre_id', ':setting_value' => (string)($syncResponse['hosted_centre_id'] ?? '')]);
                $settingsStmt->execute([':setting_key' => 'sync_hosted_account_id', ':setting_value' => (string)($syncResponse['hosted_account_id'] ?? '')]);
                $settingsStmt->execute([':setting_key' => 'sync_api_key', ':setting_value' => (string)($syncResponse['api_key'] ?? '')]);
                $settingsStmt->execute([':setting_key' => 'sync_centre_payload', ':setting_value' => json_encode($syncResponse['centre'] ?? [], JSON_UNESCAPED_SLASHES)]);
                $settingsStmt->execute([':setting_key' => 'sync_last_connected_at', ':setting_value' => date('c')]);
            } else {
                $settingsStmt->execute([':setting_key' => 'sync_provider', ':setting_value' => 'local_only']);
                $settingsStmt->execute([':setting_key' => 'sync_hosted_centre_id', ':setting_value' => '-1']);
                $settingsStmt->execute([':setting_key' => 'sync_hosted_account_id', ':setting_value' => '']);
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
        .install-card.application { --accent:var(--install-blue); } .install-card.database { --accent:var(--install-green); } .install-card.centre { --accent:var(--install-orange); } .install-card.admin { --accent:var(--install-red); }
        .install-form-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:14px; }
        .install-field-full { grid-column:1 / -1; }
        .install-card .xform-label { color:#d9eef4; font-weight:700; }
        .install-card .xform-input, .install-card select.xform-input { width:100%; box-sizing:border-box; background:rgba(5,22,30,.78); color:#f5fbfd; border:1px solid rgba(151,210,225,.24); }
        .install-card .xform-input:focus { outline:none; border-color:rgba(129,216,239,.75); box-shadow:0 0 0 3px rgba(15,119,168,.22); }
        .install-card-status { grid-column:1 / -1; padding:10px 12px; border-radius:12px; border:1px solid rgba(151,210,225,.22); background:rgba(5,22,30,.46); color:var(--install-muted); font-size:.9rem; }
        .install-centre-summary { display:grid; gap:10px; }
        .install-centre-summary div { padding:12px; border-radius:14px; background:rgba(5,22,30,.46); border:1px solid rgba(151,210,225,.18); }
        .install-centre-summary span { display:block; color:var(--install-muted); font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; }
        .install-centre-summary strong { display:block; color:#fff; margin-top:3px; font-size:1rem; }
        .install-local-warning { margin-top:18px; }
        .install-card-status.is-ok { border-color:rgba(24,160,109,.55); background:rgba(24,160,109,.14); color:#c9f6e2; }
        .install-card-status.is-warn { border-color:rgba(213,164,0,.62); background:rgba(213,164,0,.14); color:#ffe7a3; }
        .install-card-status.is-error { border-color:rgba(216,91,105,.62); background:rgba(216,91,105,.14); color:#ffd2d7; }
        .install-mode-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; margin-bottom:18px; }
        .install-mode-card { text-align:left; border:1px solid rgba(151,210,225,.22); border-radius:18px; padding:16px; background:rgba(5,22,30,.58); color:var(--install-text); cursor:pointer; }
        .install-mode-card strong { display:block; font-size:1.05rem; margin-bottom:6px; }
        .install-mode-card span { display:block; color:var(--install-muted); font-size:.9rem; line-height:1.35; }
        .install-mode-card.is-active { border-color:rgba(43,190,222,.75); box-shadow:0 0 0 3px rgba(43,190,222,.18); }
        .install-step { display:none; }
        .install-step.is-active { display:block; }
        .install-card-grid.install-step.is-active { display:grid; }
        .install-step-details .install-card { display:none; }
        .install-step-details .install-card.is-stage-visible { display:block; }
        .install-actions { margin-top:20px; display:flex; justify-content:space-between; gap:12px; }
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
                <input type="hidden" name="install_mode" value="<?= h($defaults['install_mode']) ?>">
                <input type="hidden" name="install_id" value="<?= h($defaults['install_id']) ?>">

                <div class="install-step install-step-mode is-active">
                    <div class="install-mode-grid" role="group" aria-label="Choose setup type">
                        <button class="install-mode-card" type="button" data-install-mode="existing"><strong>Sign in</strong><span>Use an existing Rescue Centre account. Your centre details are pulled from hosted Rescue Centre and Lite sync/cloud backup is enabled.</span></button>
                        <button class="install-mode-card" type="button" data-install-mode="new"><strong>Register</strong><span>Create a hosted Rescue Centre user first, then add centre details. Lite sync/cloud backup is enabled.</span></button>
                        <button class="install-mode-card" type="button" data-install-mode="local"><strong>Local only</strong><span>No hosted account, no sync and no cloud backup. You are responsible for your own database backups.</span></button>
                    </div>
                </div>

                <div class="install-card-grid install-step install-step-details">
                    <section class="install-card admin install-account-card">
                        <div class="install-card-inner">
                            <h2 id="account_card_title">Account</h2>
                            <p class="install-card-note" id="account_card_note">Sign in or create the first user account.</p>
                            <div class="install-form-grid">
                                <div class="xform-field account-register-field"><label class="xform-label" for="admin_username">Username</label><input class="xform-input" id="admin_username" name="admin_username" value="<?= h($defaults['admin_username']) ?>" required></div>
                                <div class="xform-field"><label class="xform-label" id="admin_email_label" for="admin_email">Email</label><input class="xform-input" id="admin_email" name="admin_email" type="text" value="<?= h($defaults['admin_email']) ?>" required></div>
                                <div class="xform-field"><label class="xform-label" for="admin_password">Password</label><input class="xform-input" id="admin_password" name="admin_password" type="password" required></div>
                                <div class="xform-field account-register-field"><label class="xform-label" for="admin_password_confirm">Confirm password</label><input class="xform-input" id="admin_password_confirm" name="admin_password_confirm" type="password" required></div>
                                <div class="xform-field account-register-field"><label class="xform-label" for="admin_first_name">First name</label><input class="xform-input" id="admin_first_name" name="admin_first_name" value="<?= h($defaults['admin_first_name']) ?>"></div>
                                <div class="xform-field account-register-field"><label class="xform-label" for="admin_last_name">Last name</label><input class="xform-input" id="admin_last_name" name="admin_last_name" value="<?= h($defaults['admin_last_name']) ?>"></div>
                                <div class="xform-field install-field-full hosted-auth-action" style="display:none;"><button class="btn blue" type="button" id="hosted_auth_button">Authenticate hosted account</button></div>
                                <div class="xform-field install-field-full data-download-field"><label><input type="checkbox" name="download_hosted_data" value="1" <?= $defaults['download_hosted_data'] === '1' ? 'checked' : '' ?>> Offer hosted data download after install</label></div>
                                <div id="user_check_status" class="install-card-status">Choose a setup type to continue.</div>
                            </div>
                        </div>
                    </section>

                    <section class="install-card centre">
                        <div class="install-card-inner">
                            <h2 id="centre_card_title">Centre</h2>
                            <p class="install-card-note" id="centre_card_note">Centre details for this Lite install.</p>
                            <div class="install-centre-summary" id="centre_summary" style="display:none;">
                                <div><span>Centre</span><strong data-summary="centre_name">-</strong></div>
                                <div><span>Email</span><strong data-summary="centre_email">-</strong></div>
                                <div><span>Country</span><strong data-summary="country_code">-</strong></div>
                                <div><span>County/state</span><strong data-summary="county">-</strong></div>
                            </div>
                            <div class="install-form-grid" id="centre_form_fields">
                                <div class="xform-field install-field-full"><label class="xform-label" for="centre_name">Centre name</label><input class="xform-input" id="centre_name" name="centre_name" value="<?= h($defaults['centre_name']) ?>" required></div>
                                <div class="xform-field install-field-full"><label class="xform-label" for="centre_email">Centre email</label><input class="xform-input" id="centre_email" name="centre_email" type="email" value="<?= h($defaults['centre_email']) ?>"></div>
                                <div class="xform-field"><label class="xform-label" for="country_code">Country code</label><input class="xform-input" id="country_code" name="country_code" maxlength="2" value="<?= h($defaults['country_code']) ?>"></div>
                                <div class="xform-field"><label class="xform-label" for="county">County / state</label><input class="xform-input" id="county" name="county" value="<?= h($defaults['county']) ?>"></div>
                            </div>
                            <div id="centre_check_status" class="install-card-status">Centre details will appear at the correct setup step.</div>
                        </div>
                    </section>

                    <section class="install-card application">
                        <div class="install-card-inner">
                            <h2>Application</h2>
                            <p class="install-card-note">Name the local install and set its public path.</p>
                            <div class="install-form-grid">
                                <div class="xform-field install-field-full"><label class="xform-label" for="app_name">Application name</label><input class="xform-input" id="app_name" name="app_name" value="<?= h($defaults['app_name']) ?>"></div>
                                <div class="xform-field"><label class="xform-label" for="base_url">Base URL</label><input class="xform-input" id="base_url" name="base_url" value="<?= h($defaults['base_url']) ?>"></div>
                                <div class="xform-field"><label class="xform-label" for="default_language">Language</label><select class="xform-input" id="default_language" name="default_language"><?php foreach (['en' => 'English', 'es' => 'Spanish', 'de' => 'German', 'fr' => 'French', 'pl' => 'Polish'] as $code => $label): ?><option value="<?= h($code) ?>" <?= $defaults['default_language'] === $code ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?></select></div>
                                <div class="xform-field install-field-full"><label class="xform-label" for="hosted_api_url">Hosted sync API URL</label><input class="xform-input" id="hosted_api_url" name="hosted_api_url" value="<?= h($defaults['hosted_api_url']) ?>" required></div>
                            </div>
                        </div>
                    </section>

                    <section class="install-card database">
                        <div class="install-card-inner">
                            <h2>Database</h2>
                            <p class="install-card-note">Use the MySQL database and user created in cPanel.</p>
                            <div class="install-form-grid">
                                <div class="xform-field"><label class="xform-label" for="db_host">Host</label><input class="xform-input" id="db_host" name="db_host" value="<?= h($defaults['db_host']) ?>" required></div>
                                <div class="xform-field"><label class="xform-label" for="db_name">Database</label><input class="xform-input" id="db_name" name="db_name" value="<?= h($defaults['db_name']) ?>" required></div>
                                <div class="xform-field"><label class="xform-label" for="db_user">User</label><input class="xform-input" id="db_user" name="db_user" value="<?= h($defaults['db_user']) ?>" required></div>
                                <div class="xform-field"><label class="xform-label" for="db_pass">Password</label><input class="xform-input" id="db_pass" name="db_pass" type="password" value="<?= h($defaults['db_pass']) ?>"></div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="install-local-warning rc-alert orange" style="display:none;">Local-only install has no hosted sync or cloud backup. You are responsible for your own database backups.</div>
                <div class="install-actions"><button class="btn blue install-back" type="button" style="display:none;">Back</button><button class="btn green install-next" type="button">Next</button><button class="btn green install-submit" type="submit" style="display:none;">Finish</button></div>
            </form>
        <?php endif; ?>
    </section>
</main>
<script>
(function () {
    const form = document.querySelector('.install-form');
    if (!form) return;

    const apiUrl = form.querySelector('[name="hosted_api_url"]');
    const installId = form.querySelector('[name="install_id"]');
    const installMode = form.querySelector('[name="install_mode"]');
    const modeButtons = Array.from(document.querySelectorAll('[data-install-mode]'));
    const stepMode = document.querySelector('.install-step-mode');
    const stepDetails = document.querySelector('.install-step-details');
    const backButton = document.querySelector('.install-back');
    const nextButton = document.querySelector('.install-next');
    const submitButton = document.querySelector('.install-submit');
    const localWarning = document.querySelector('.install-local-warning');

    const accountCard = document.querySelector('.install-account-card');
    const centreCard = document.querySelector('.install-card.centre');
    const applicationCard = document.querySelector('.install-card.application');
    const databaseCard = document.querySelector('.install-card.database');
    const allCards = [accountCard, centreCard, applicationCard, databaseCard];

    const accountTitle = document.getElementById('account_card_title');
    const accountNote = document.getElementById('account_card_note');
    const emailLabel = document.getElementById('admin_email_label');
    const userStatus = document.getElementById('user_check_status');
    const centreStatus = document.getElementById('centre_check_status');
    const authAction = document.querySelector('.hosted-auth-action');
    const authButton = document.getElementById('hosted_auth_button');
    const registerFields = Array.from(document.querySelectorAll('.account-register-field'));
    const dataDownloadField = document.querySelector('.data-download-field');
    const centreForm = document.getElementById('centre_form_fields');
    const centreSummary = document.getElementById('centre_summary');

    const centreName = form.querySelector('[name="centre_name"]');
    const centreEmail = form.querySelector('[name="centre_email"]');
    const countryCode = form.querySelector('[name="country_code"]');
    const county = form.querySelector('[name="county"]');
    const adminUsername = form.querySelector('[name="admin_username"]');
    const adminEmail = form.querySelector('[name="admin_email"]');
    const adminPassword = form.querySelector('[name="admin_password"]');
    const adminPasswordConfirm = form.querySelector('[name="admin_password_confirm"]');
    const firstName = form.querySelector('[name="admin_first_name"]');
    const lastName = form.querySelector('[name="admin_last_name"]');

    let currentMode = installMode ? installMode.value || 'existing' : 'existing';
    let currentStep = 'mode';
    let hostedAuthenticated = false;

    function setStatus(el, message, state) {
        if (!el) return;
        el.textContent = message;
        el.classList.remove('is-ok', 'is-warn', 'is-error');
        if (state) el.classList.add(state);
    }

    function showCards(cards) {
        allCards.forEach(card => { if (card) card.classList.remove('is-stage-visible'); });
        cards.forEach(card => { if (card) card.classList.add('is-stage-visible'); });
    }

    function setRequired(el, required) {
        if (el) el.required = !!required;
    }

    function setRegisterFields(visible, required) {
        registerFields.forEach(field => { field.style.display = visible ? '' : 'none'; });
        setRequired(adminUsername, required);
        setRequired(adminPasswordConfirm, required);
    }

    function setAccountFields(mode) {
        if (mode === 'signin') {
            if (accountTitle) accountTitle.textContent = 'Sign in';
            if (accountNote) accountNote.textContent = 'Use your hosted Rescue Centre username/email and password.';
            if (emailLabel) emailLabel.textContent = 'Username or email';
            setRegisterFields(false, false);
            if (authAction) authAction.style.display = '';
            if (dataDownloadField) dataDownloadField.style.display = 'none';
            setRequired(adminEmail, true);
            setRequired(adminPassword, true);
            setRequired(centreName, false);
            setRequired(countryCode, false);
        } else {
            if (accountTitle) accountTitle.textContent = mode === 'local' ? 'Local user' : 'Register user';
            if (accountNote) accountNote.textContent = mode === 'local' ? 'Create the first local user account.' : 'Create the hosted user account first.';
            if (emailLabel) emailLabel.textContent = 'Email';
            setRegisterFields(true, true);
            if (authAction) authAction.style.display = 'none';
            if (dataDownloadField) dataDownloadField.style.display = mode === 'local' ? 'none' : '';
            setRequired(adminEmail, true);
            setRequired(adminPassword, true);
            setRequired(centreName, mode === 'local');
            setRequired(countryCode, mode === 'local');
        }
    }

    function setCentreView(mode) {
        const summaryOnly = mode === 'summary';
        if (centreForm) centreForm.style.display = summaryOnly ? 'none' : '';
        if (centreSummary) centreSummary.style.display = summaryOnly ? '' : 'none';
        setRequired(centreName, !summaryOnly);
        setRequired(countryCode, !summaryOnly);
    }

    function updateCentreSummary() {
        const values = {
            centre_name: centreName ? centreName.value : '',
            centre_email: centreEmail ? centreEmail.value : '',
            country_code: countryCode ? countryCode.value : '',
            county: county ? county.value : ''
        };
        Object.keys(values).forEach(key => {
            const el = document.querySelector('[data-summary="' + key + '"]');
            if (el) el.textContent = values[key] || 'Not recorded';
        });
    }

    function applyHostedDetails(data) {
        const centre = data && data.centre ? data.centre : {};
        const account = data && data.account ? data.account : {};
        if (centreName) centreName.value = centre.rescue_name || '';
        if (centreEmail) centreEmail.value = centre.email || '';
        if (countryCode) countryCode.value = centre.country_code || countryCode.value || 'GB';
        if (county) county.value = centre.county || '';
        if (adminUsername) adminUsername.value = account.username || '';
        if (adminEmail && account.email) adminEmail.value = account.email;
        if (firstName) firstName.value = account.first_name || '';
        if (lastName) lastName.value = account.last_name || '';
        if (adminPasswordConfirm && adminPassword) adminPasswordConfirm.value = adminPassword.value;
        updateCentreSummary();
    }

    function clearAccountFields() {
        [adminUsername, adminEmail, adminPassword, adminPasswordConfirm, firstName, lastName].forEach(el => {
            if (el) el.value = '';
        });
    }

    function clearCentreFields() {
        [centreName, centreEmail, county].forEach(el => {
            if (el) el.value = '';
        });
        if (countryCode) countryCode.value = 'GB';
        updateCentreSummary();
    }

    function resetForMode(mode) {
        if (mode === 'new') {
            clearAccountFields();
            clearCentreFields();
            hostedAuthenticated = false;
            setStatus(userStatus, 'Enter a new username and email. We will check availability before centre setup.', 'is-warn');
        } else if (mode === 'local') {
            clearAccountFields();
            clearCentreFields();
            hostedAuthenticated = false;
        } else {
            clearAccountFields();
            clearCentreFields();
            hostedAuthenticated = false;
        }
    }

    async function checkRegisterUsername() {
        if (!window.fetch || !apiUrl || !installId || !adminUsername) return true;
        if (currentMode !== 'new' || currentStep !== 'register') return true;
        const username = adminUsername.value.trim();
        lastCheckedUsername = username;
        lastUsernameAvailable = null;
        if (!username) {
            setStatus(userStatus, 'Enter a username to check availability.', 'is-warn');
            return false;
        }
        setStatus(userStatus, 'Checking username availability...', null);
        try {
            const checkUrl = new URL(window.location.href);
            checkUrl.search = '';
            checkUrl.searchParams.set('lite_check', '1');
            checkUrl.searchParams.set('hosted_api_url', apiUrl.value);
            checkUrl.searchParams.set('install_id', installId.value);
            checkUrl.searchParams.set('admin_username', username);
            const response = await fetch(checkUrl.toString(), { method: 'GET', headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            if (data.status !== 'checked') throw new Error(data.message || 'Hosted check failed.');
            lastUsernameAvailable = data.username_available !== false;
            if (!lastUsernameAvailable) {
                setStatus(userStatus, 'That username is already in use. Choose another username.', 'is-error');
                return false;
            }
            setStatus(userStatus, 'Username is available.', 'is-ok');
            return true;
        } catch (error) {
            lastUsernameAvailable = null;
            setStatus(userStatus, (error && error.message ? error.message : 'Hosted username check failed.'), 'is-error');
            return false;
        }
    }
    async function checkRegisterAccount() {
        if (!window.fetch || !apiUrl || !installId || !adminUsername || !adminEmail) return true;
        const username = adminUsername.value.trim();
        const email = adminEmail.value.trim();
        if (!username || !email) {
            setStatus(userStatus, 'Enter a username and email before continuing.', 'is-error');
            return false;
        }
        setStatus(userStatus, 'Checking username and email availability...', null);
        try {
            const checkUrl = new URL(window.location.href);
            checkUrl.search = '';
            checkUrl.searchParams.set('lite_check', '1');
            checkUrl.searchParams.set('hosted_api_url', apiUrl.value);
            checkUrl.searchParams.set('install_id', installId.value);
            checkUrl.searchParams.set('admin_username', username);
            checkUrl.searchParams.set('admin_email', email);
            const response = await fetch(checkUrl.toString(), { method: 'GET', headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            if (data.status !== 'checked') throw new Error(data.message || 'Hosted check failed.');
            if (data.user_requires_login) {
                setStatus(userStatus, 'That email is already registered. Use Sign in or choose another email.', 'is-error');
                return false;
            }
            if (data.username_available === false) {
                setStatus(userStatus, 'That username is already in use. Choose another username.', 'is-error');
                return false;
            }
            setStatus(userStatus, 'Username and email look available.', 'is-ok');
            return true;
        } catch (error) {
            setStatus(userStatus, (error && error.message ? error.message : 'Hosted check failed.'), 'is-error');
            return false;
        }
    }

    function setInstallMode(mode) {
        const changed = currentMode !== mode;
        currentMode = mode;
        if (changed) resetForMode(mode);
        if (installMode) installMode.value = mode;
        modeButtons.forEach(button => button.classList.toggle('is-active', button.dataset.installMode === mode));
    }

    function setStep(step) {
        currentStep = step;
        if (stepMode) stepMode.classList.toggle('is-active', step === 'mode');
        if (stepDetails) stepDetails.classList.toggle('is-active', step !== 'mode');
        if (backButton) backButton.style.display = step === 'mode' ? 'none' : '';
        if (nextButton) nextButton.style.display = 'none';
        if (submitButton) submitButton.style.display = 'none';
        if (localWarning) localWarning.style.display = 'none';

        setRequired(centreName, false);
        setRequired(countryCode, false);

        if (step === 'mode') {
            showCards([]);
            if (nextButton) nextButton.style.display = '';
            return;
        }

        if (step === 'signin') {
            showCards([accountCard]);
            setAccountFields('signin');
            setCentreView('form');
            setStatus(userStatus, 'Authenticate before continuing. Rescue role must be 1.', 'is-warn');
            setStatus(centreStatus, 'Centre details will be pulled after sign in.', 'is-warn');
            return;
        }

        if (step === 'register') {
            showCards([accountCard]);
            setAccountFields('register');
            setCentreView('form');
            if (nextButton) nextButton.style.display = '';
            setStatus(userStatus, 'Type a username to check availability.', 'is-warn');
            return;
        }

        if (step === 'centre') {
            showCards([centreCard]);
            setAccountFields(currentMode === 'local' ? 'local' : 'register');
            setCentreView('form');
            if (nextButton) nextButton.style.display = '';
            setRequired(centreName, true);
            setRequired(countryCode, true);
            setStatus(centreStatus, currentMode === 'local' ? 'Enter local centre details.' : 'Enter centre details for hosted sync.', currentMode === 'local' ? 'is-warn' : 'is-ok');
            return;
        }

        if (step === 'centre_confirm') {
            showCards([centreCard]);
            setCentreView('summary');
            updateCentreSummary();
            if (nextButton) nextButton.style.display = '';
            setStatus(centreStatus, 'Confirm these hosted centre details.', 'is-ok');
            return;
        }

        if (step === 'install') {
            showCards([applicationCard, databaseCard]);
            setCentreView('form');
            [centreName, countryCode, adminUsername, adminEmail, adminPassword, adminPasswordConfirm].forEach(el => setRequired(el, false));
            if (submitButton) submitButton.style.display = '';
            return;
        }

        if (step === 'local') {
            showCards([accountCard, centreCard, applicationCard, databaseCard]);
            setAccountFields('local');
            setCentreView('form');
            if (localWarning) localWarning.style.display = '';
            if (submitButton) submitButton.style.display = '';
            setRequired(centreName, true);
            setRequired(countryCode, true);
            setStatus(userStatus, 'Create the first local user account.', 'is-warn');
            setStatus(centreStatus, 'Local-only install: no sync or cloud backup.', 'is-warn');
        }
    }

    async function authenticateHosted() {
        if (!window.fetch || !apiUrl || !installId || !adminEmail || !adminPassword) return;
        setStatus(userStatus, 'Authenticating hosted account...', null);
        try {
            const authUrl = new URL(window.location.href);
            authUrl.search = '';
            authUrl.searchParams.set('lite_auth', '1');
            const response = await fetch(authUrl.toString(), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    hosted_api_url: apiUrl.value,
                    install_id: installId.value,
                    admin_email: adminEmail.value.trim(),
                    admin_password: adminPassword.value
                })
            });
            const data = await response.json();
            if (data.status !== 'authenticated') throw new Error(data.message || 'Hosted authentication failed.');
            hostedAuthenticated = true;
            applyHostedDetails(data);
            setStatus(userStatus, 'Authenticated. Centre details have been pulled from hosted Rescue Centre.', 'is-ok');
            setStep('centre_confirm');
        } catch (error) {
            hostedAuthenticated = false;
            setStatus(userStatus, (error && error.message ? error.message : 'Hosted authentication failed.'), 'is-error');
        }
    }

    if (adminUsername) {
        adminUsername.addEventListener('input', function () {
            if (currentMode !== 'new' || currentStep !== 'register') return;
            lastUsernameAvailable = null;
            clearTimeout(usernameCheckTimer);
            usernameCheckTimer = setTimeout(checkRegisterUsername, 450);
        });
        adminUsername.addEventListener('blur', function () {
            if (currentMode === 'new' && currentStep === 'register') checkRegisterUsername();
        });
    }

    if (authButton) authButton.addEventListener('click', authenticateHosted);
    modeButtons.forEach(button => {
        button.addEventListener('click', function () {
            setInstallMode(button.dataset.installMode || 'existing');
        });
    });

    if (nextButton) {
        nextButton.addEventListener('click', async function () {
            if (currentStep === 'mode') {
                if (currentMode === 'existing') return setStep('signin');
                if (currentMode === 'new') return setStep('register');
                return setStep('local');
            }
            if (currentStep === 'register') {
                if (await checkRegisterAccount()) return setStep('centre');
                return;
            }
            if (currentStep === 'centre' || currentStep === 'centre_confirm') return setStep('install');
        });
    }

    if (backButton) {
        backButton.addEventListener('click', function () {
            if (currentStep === 'install') return setStep(currentMode === 'existing' ? 'centre_confirm' : 'centre');
            if (currentStep === 'centre') return setStep(currentMode === 'new' ? 'register' : 'mode');
            if (currentStep === 'centre_confirm' || currentStep === 'signin' || currentStep === 'register' || currentStep === 'local') return setStep('mode');
            setStep('mode');
        });
    }

    form.addEventListener('submit', function (event) {
        if (currentMode === 'existing' && !hostedAuthenticated) {
            event.preventDefault();
            setStatus(userStatus, 'Sign in and authenticate before installing with an existing hosted account.', 'is-error');
        }
    });

    setInstallMode(currentMode || 'existing');
    setStep('mode');
})();
</script>
</body>
</html>
