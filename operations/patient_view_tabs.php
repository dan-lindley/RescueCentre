<?php
// operations/patient_view_tabs.php

function patient_view_tabs_load_providers(PDO $pdo, ?int $centre_id = null): array
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

        $provider_file = modules_base_path() . '/' . $module_key . '/controllers/patient_view_tabs.php';
        if (!is_file($provider_file)) {
            continue;
        }

        try {
            require_once $provider_file;
        } catch (Throwable $e) {
            continue;
        }

        $function = preg_replace('/[^a-zA-Z0-9_]/', '_', $module_key) . '_patient_view_tabs_provider';
        if (!function_exists($function)) {
            continue;
        }

        try {
            $provider = $function();
        } catch (Throwable $e) {
            continue;
        }
        if (!is_array($provider) || empty($provider['tabs']) || !is_array($provider['tabs'])) {
            continue;
        }

        $providers[] = $provider;
    }

    return $cache[$cache_key] = $providers;
}

function patient_view_tabs_load_tabs(PDO $pdo, ?int $centre_id = null): array
{
    $tabs = [];
    foreach (patient_view_tabs_load_providers($pdo, $centre_id) as $provider) {
        foreach (($provider['tabs'] ?? []) as $tab) {
            if (!is_array($tab) || empty($tab['id']) || empty($tab['label']) || empty($tab['view'])) {
                continue;
            }

            $tab['allowed'] = array_key_exists('allowed', $tab) ? (bool)$tab['allowed'] : true;
            $tab['order'] = (int)($tab['order'] ?? 100);
            $tabs[] = $tab;
        }
    }

    usort($tabs, static function (array $a, array $b): int {
        return [$a['order'], (string)$a['id']] <=> [$b['order'], (string)$b['id']];
    });

    return $tabs;
}
