<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/helpers.php';

$configPath = app_path('config/config.php');
if (!is_file($configPath)) {
    header('Location: /install/');
    exit;
}

$config = require $configPath;

$langCode = (string)($_GET['lang'] ?? $_SESSION['lang'] ?? $config['app']['default_language'] ?? 'en');
$_SESSION['lang'] = $langCode;
$lang = load_language($langCode);

try {
    $db = $config['database'];
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $db['host'],
        $db['name'],
        $db['charset'] ?? 'utf8mb4'
    );
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Check config/config.php.');
}
