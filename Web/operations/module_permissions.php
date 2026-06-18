<?php
// operations/module_permissions.php

function module_permissions_register_all(PDO $pdo, ?int $centre_id = null): void
{
    static $registered = [];

    $centre_id = $centre_id ?? (int)($GLOBALS['centre_id'] ?? $_SESSION['centre_id'] ?? 0);
    $cache_key = (string)$centre_id;
    if (isset($registered[$cache_key])) {
        return;
    }
    $registered[$cache_key] = true;

    require_once __DIR__ . '/modules_registry.php';

    foreach (modules_discover($pdo, $centre_id) as $module) {
        if (empty($module['installed'])) {
            continue;
        }

        $module_key = (string)($module['module_key'] ?? '');
        if ($module_key === '') {
            continue;
        }

        $provider_file = modules_base_path() . '/' . $module_key . '/controllers/module_permissions.php';
        if (!is_file($provider_file)) {
            continue;
        }

        try {
            require_once $provider_file;
        } catch (Throwable $e) {
            continue;
        }

        $function = preg_replace('/[^a-zA-Z0-9_]/', '_', $module_key) . '_register_module_permissions';
        if (function_exists($function)) {
            try {
                $function();
            } catch (Throwable $e) {
                continue;
            }
        }
    }
}
