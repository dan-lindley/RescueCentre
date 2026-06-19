<?php
// modules/incidents/controllers/incidents_lib.php

function incidents_module_language(): array
{
    $language = (string)($_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'en');
    $language = preg_replace('/[^a-z]/', '', strtolower($language));
    $language = $language !== '' ? $language : 'en';

    $file = __DIR__ . '/../languages/lang.' . $language . '.php';
    if (!is_file($file)) {
        $file = __DIR__ . '/../languages/lang.en.php';
    }

    $translations = require $file;
    return is_array($translations) ? $translations : [];
}

function incidents_text(string $key, string $fallback = ''): string
{
    global $lang, $incident_lang;

    if (isset($incident_lang[$key])) {
        return (string)$incident_lang[$key];
    }
    if (isset($lang[$key])) {
        return (string)$lang[$key];
    }

    return $fallback !== '' ? $fallback : $key;
}
