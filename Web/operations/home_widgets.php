<?php
// operations/home_widgets.php

function home_widgets_load_providers(PDO $pdo, ?int $centre_id = null): array
{
    static $cache = [];

    $centre_id = $centre_id ?? (int)($GLOBALS['centre_id'] ?? $_SESSION['centre_id'] ?? 0);
    $cache_key = (string)$centre_id;
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }

    require_once __DIR__ . '/modules_registry.php';

    $providers = [];
    foreach (modules_discover($pdo, $centre_id) as $module) {
        if (empty($module['installed']) || empty($module['enabled'])) {
            continue;
        }

        $module_key = (string)($module['module_key'] ?? '');
        if ($module_key === '') {
            continue;
        }

        $provider_file = modules_base_path() . '/' . $module_key . '/controllers/home_widgets.php';
        if (!is_file($provider_file)) {
            continue;
        }

        try {
            require_once $provider_file;
        } catch (Throwable $e) {
            continue;
        }

        $function = preg_replace('/[^a-zA-Z0-9_]/', '_', $module_key) . '_home_widgets_provider';
        if (!function_exists($function)) {
            continue;
        }

        try {
            $provider = $function();
        } catch (Throwable $e) {
            continue;
        }

        if (!is_array($provider) || empty($provider['key'])) {
            continue;
        }

        $provider['order'] = (int)($provider['order'] ?? 100);
        $providers[] = $provider;
    }

    usort($providers, static function (array $a, array $b): int {
        return [$a['order'], (string)$a['key']] <=> [$b['order'], (string)$b['key']];
    });

    return $cache[$cache_key] = $providers;
}

function home_widgets_render(PDO $pdo, array $context = []): string
{
    $centre_id = (int)($context['centre_id'] ?? $GLOBALS['centre_id'] ?? $_SESSION['centre_id'] ?? 0);
    $providers = $context['providers'] ?? home_widgets_load_providers($pdo, $centre_id);
    $html = '';

    foreach ($providers as $provider) {
        $callback = $provider['render_callback'] ?? null;
        if (!is_callable($callback)) {
            continue;
        }

        try {
            $html .= (string)$callback($pdo, $context);
        } catch (Throwable $e) {
            continue;
        }
    }

    return $html;
}
