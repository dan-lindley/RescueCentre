<?php
// operations/patient_row_actions.php

function patient_row_actions_load_providers(PDO $pdo, ?int $centre_id = null): array
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

        $provider_file = modules_base_path() . '/' . $module_key . '/controllers/patient_row_actions.php';
        if (!is_file($provider_file)) {
            continue;
        }

        try {
            require_once $provider_file;
        } catch (Throwable $e) {
            continue;
        }

        $function = preg_replace('/[^a-zA-Z0-9_]/', '_', $module_key) . '_patient_row_actions_provider';
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

function patient_row_actions_context_providers(PDO $pdo, array $context): array
{
    if (isset($context['providers']) && is_array($context['providers'])) {
        return $context['providers'];
    }

    return patient_row_actions_load_providers($pdo, (int)($context['centre_id'] ?? 0) ?: null);
}

function patient_row_actions_render_buttons(PDO $pdo, array $patient, array $context = []): string
{
    $html = '';
    foreach (patient_row_actions_context_providers($pdo, $context) as $provider) {
        $callback = $provider['button_callback'] ?? null;
        if (is_callable($callback)) {
            $html .= (string)$callback($pdo, $patient, $context);
        }
    }
    return $html;
}

function patient_row_actions_render_icons(PDO $pdo, array $patient, array $context = []): string
{
    $html = '';
    foreach (patient_row_actions_context_providers($pdo, $context) as $provider) {
        $callback = $provider['icons_callback'] ?? null;
        if (is_callable($callback)) {
            $html .= (string)$callback($pdo, $patient, $context);
        }
    }
    return $html;
}

function patient_row_actions_render_forms(PDO $pdo, array $patient, array $context = []): string
{
    $html = '';
    foreach (patient_row_actions_context_providers($pdo, $context) as $provider) {
        $callback = $provider['form_callback'] ?? null;
        if (is_callable($callback)) {
            $html .= (string)$callback($pdo, $patient, $context);
        }
    }
    return $html;
}
