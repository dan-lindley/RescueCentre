<?php
// -----------------------------------------
// Language bootstrap (wrapper safe)
// -----------------------------------------

/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed_langs = ['en', 'es', 'fr', 'de', 'pl']; // add more later

// 1. Language explicitly changed via URL
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed_langs, true)) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, time() + (3600 * 24 * 30), '/');
}

// 2. Session language
elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $allowed_langs, true)) {
    $lang = $_SESSION['lang'];
}

// 3. Cookie fallback
elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $allowed_langs, true)) {
    $lang = $_COOKIE['lang'];
    $_SESSION['lang'] = $lang;
}

// 4. Default
else {
    $lang = 'en';
    $_SESSION['lang'] = $lang;
}

// Load language file
$lang_file = __DIR__ . "/languages/lang.$lang.php";

if (!file_exists($lang_file)) {
    $lang_file = __DIR__ . "/languages/lang.en.php";
}

require $lang_file;

