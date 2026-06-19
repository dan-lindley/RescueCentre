<?php
// Rescue Centre Lite installer entry point.
// This file is intentionally defensive for shared hosting/cPanel installs:
// if the installer fails, show a useful error instead of a blank HTTP 500.

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');

if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}

function rc_lite_install_escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rc_lite_install_error_page($title, $message, $details = '')
{
    if (!headers_sent()) {
        http_response_code(500);
    }

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Rescue Centre Lite install error</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#10202b;color:#eef7fb;margin:0;padding:32px}';
    echo '.box{max-width:900px;margin:auto;background:#172f3d;border:1px solid #315266;border-radius:14px;padding:24px;box-shadow:0 16px 40px rgba(0,0,0,.25)}';
    echo 'h1{margin-top:0;color:#fff}.msg{background:#3b1820;border:1px solid #d95f6a;color:#ffe6e9;border-radius:10px;padding:14px;margin:16px 0}';
    echo 'pre{white-space:pre-wrap;background:#07131a;border:1px solid #294456;border-radius:10px;padding:14px;overflow:auto}';
    echo 'code{color:#b7e6ff}</style></head><body><div class="box">';
    echo '<h1>' . rc_lite_install_escape($title) . '</h1>';
    echo '<div class="msg">' . rc_lite_install_escape($message) . '</div>';
    if ($details !== '') {
        echo '<pre>' . rc_lite_install_escape($details) . '</pre>';
    }
    echo '<p>Check the file path, PHP version, permissions, and whether <code>database/schema.sql</code> exists.</p>';
    echo '</div></body></html>';
    exit;
}

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function () {
    $error = error_get_last();
    if (!$error) {
        return;
    }

    $fatalTypes = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
    if (!in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    if (ob_get_length()) {
        ob_clean();
    }

    rc_lite_install_error_page(
        'Installer fatal error',
        $error['message'],
        $error['file'] . ':' . $error['line']
    );
});

ob_start();

try {
    if (version_compare(PHP_VERSION, '7.2.0', '<')) {
        throw new RuntimeException('PHP 7.2 or newer is required. Current PHP version: ' . PHP_VERSION);
    }

    if (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
        throw new RuntimeException('The PDO MySQL extension is required. Enable pdo_mysql for this PHP version in cPanel.');
    }

    $installer = __DIR__ . '/install/index.php';
    $schema = __DIR__ . '/database/schema.sql';

    if (!is_file($installer)) {
        throw new RuntimeException('Installer file not found: ' . $installer);
    }

    if (!is_readable($installer)) {
        throw new RuntimeException('Installer file is not readable: ' . $installer);
    }

    if (!is_file($schema)) {
        throw new RuntimeException('Database schema not found: ' . $schema);
    }

    if (!is_readable($schema)) {
        throw new RuntimeException('Database schema is not readable: ' . $schema);
    }

    require $installer;

    ob_end_flush();
} catch (Throwable $e) {
    if (ob_get_length()) {
        ob_clean();
    }

    rc_lite_install_error_page(
        'Installer could not load',
        $e->getMessage(),
        $e->getFile() . ':' . $e->getLine()
    );
}
