<?php

declare(strict_types=1);

function app_path(string $path = ''): string
{
    return dirname(__DIR__) . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
}

function public_asset(string $path): string
{
    return 'assets/' . ltrim($path, '/');
}

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function load_language(string $code = 'en'): array
{
    $allowed = ['en', 'es', 'de', 'fr', 'pl'];
    if (!in_array($code, $allowed, true)) {
        $code = 'en';
    }

    $lang = [];
    $file = app_path('languages/lang.' . $code . '.php');
    if (is_file($file)) {
        require $file;
    }

    return is_array($lang) ? $lang : [];
}

function lang(array $lang, string $key, ?string $fallback = null): string
{
    return (string)($lang[$key] ?? $fallback ?? $key);
}
