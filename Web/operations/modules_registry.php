<?php
// operations/modules_registry.php

function modules_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function modules_base_path(): string
{
    return dirname(__DIR__) . '/modules';
}

function modules_language(string $moduleName): array
{
    static $translations = [];

    $moduleName = preg_replace('/[^a-z0-9_-]/', '', strtolower($moduleName));
    $language = (string)($_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'en');
    $language = preg_replace('/[^a-z]/', '', strtolower($language));
    $language = $language !== '' ? $language : 'en';
    $cacheKey = $moduleName . ':' . $language;

    if (isset($translations[$cacheKey])) {
        return $translations[$cacheKey];
    }

    $file = modules_base_path() . '/' . $moduleName . '/languages/lang.' . $language . '.php';
    if (!is_file($file)) {
        $file = modules_base_path() . '/' . $moduleName . '/languages/lang.en.php';
    }
    if (!is_file($file)) {
        return $translations[$cacheKey] = [];
    }

    $moduleTranslations = require $file;
    return $translations[$cacheKey] = is_array($moduleTranslations) ? $moduleTranslations : [];
}

function modules_pretty_name(string $moduleName): string
{
    return ucwords(str_replace(['_', '-'], ' ', $moduleName));
}

function modules_read_manifest(string $moduleName): array
{
    $manifest = [
        'name' => modules_pretty_name($moduleName),
        'version' => '0.0.1',
        'description' => 'Module scaffold in /modules/' . $moduleName,
        'image' => '',
        'available' => true,
        'permission' => '',
        'dependencies' => [],
        'nav' => [],
    ];

    $file = modules_base_path() . '/' . $moduleName . '/module.json';
    if (!is_file($file)) {
        return $manifest;
    }

    $decoded = json_decode((string)file_get_contents($file), true);
    if (!is_array($decoded)) {
        return $manifest;
    }

    $manifest['name'] = trim((string)($decoded['name'] ?? $manifest['name'])) ?: $manifest['name'];
    $manifest['version'] = trim((string)($decoded['version'] ?? $manifest['version'])) ?: $manifest['version'];
    $manifest['description'] = trim((string)($decoded['description'] ?? $manifest['description'])) ?: $manifest['description'];
    $manifest['image'] = trim((string)($decoded['image'] ?? ''));
    $manifest['available'] = array_key_exists('available', $decoded) ? (bool)$decoded['available'] : true;
    $manifest['permission'] = trim((string)($decoded['permission'] ?? ''));
    $manifest['dependencies'] = is_array($decoded['dependencies'] ?? null) ? $decoded['dependencies'] : [];
    $manifest['nav'] = is_array($decoded['nav'] ?? null) ? $decoded['nav'] : [];

    return $manifest;
}

function modules_table_columns(PDO $pdo): array
{
    static $columns = null;
    if ($columns !== null) {
        return $columns;
    }

    $columns = [];
    $stmt = $pdo->query("DESCRIBE rescue_modules");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        if (!empty($row['Field'])) {
            $columns[(string)$row['Field']] = true;
        }
    }

    return $columns;
}

function modules_key_column(array $columns): string
{
    return !empty($columns['name']) ? 'name' : 'module_key';
}

function modules_current_centre_id(): int
{
    if (isset($GLOBALS['centre_id'])) {
        return (int)$GLOBALS['centre_id'];
    }
    if (isset($_SESSION['centre_id'])) {
        return (int)$_SESSION['centre_id'];
    }
    if (isset($_SESSION['rescue_id'])) {
        return (int)$_SESSION['rescue_id'];
    }
    return 0;
}

function modules_state_from_row(array $row): array
{
    $status = strtolower((string)($row['status'] ?? ''));
    $installed = array_key_exists('installed', $row) ? !empty($row['installed']) : in_array($status, ['active', 'inactive'], true);
    $enabled = array_key_exists('enabled', $row) ? !empty($row['enabled']) : $status === 'active';
    $core = array_key_exists('core', $row) ? !empty($row['core']) : !empty($row['is_core']);

    return [
        'installed' => $installed ? 1 : 0,
        'enabled' => $enabled ? 1 : 0,
        'core' => $core ? 1 : 0,
        'status' => $enabled ? 'active' : ($installed ? 'inactive' : 'not installed'),
    ];
}

function modules_discover(PDO $pdo, ?int $centreId = null): array
{
    static $cache = null;

    $centreId = $centreId ?? modules_current_centre_id();
    $cacheKey = (string)$centreId;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    $basePath = modules_base_path();
    if (!is_dir($basePath)) {
        return $cache[$cacheKey] = [];
    }

    $moduleDirs = array_values(array_filter(glob($basePath . '/*') ?: [], 'is_dir'));
    sort($moduleDirs);

    $columns = modules_table_columns($pdo);
    $keyColumn = modules_key_column($columns);
    $states = [];

    if (!empty($columns['centre_id']) && $centreId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM rescue_modules WHERE centre_id = :centre_id");
        $stmt->execute([':centre_id' => $centreId]);
    } else {
        $stmt = $pdo->query("SELECT * FROM rescue_modules");
    }

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $stateKey = (string)($row[$keyColumn] ?? '');
        if ($stateKey !== '') {
            $states[$stateKey] = $row;
        }
    }

    $modules = [];
    foreach ($moduleDirs as $dir) {
        $moduleName = basename($dir);
        $manifest = modules_read_manifest($moduleName);
        $imagePath = $manifest['image'];
        $stateRow = $states[$moduleName] ?? [];
        $state = modules_state_from_row($stateRow);

        if ($imagePath === '') {
            foreach (['module.png', 'module.jpg', 'module.jpeg', 'module.webp'] as $candidate) {
                if (is_file(modules_base_path() . '/' . $moduleName . '/' . $candidate)) {
                    $imagePath = 'modules/' . $moduleName . '/' . $candidate;
                    break;
                }
            }
        }

        $row = $stateRow;
        $row['name'] = $moduleName;
        $row['module_key'] = $moduleName;
        $row['module_name'] = $manifest['name'];
        $row['description'] = $manifest['description'];
        $row['version'] = $manifest['version'];
        $row['image_path'] = $imagePath;
        $row['manifest'] = $manifest;
        $row['available'] = !empty($manifest['available']) ? 1 : 0;
        $row['dependencies'] = $manifest['dependencies'];
        $row['installed'] = $state['installed'];
        $row['enabled'] = $state['enabled'];
        $row['core'] = !empty($manifest['core']) || !empty($state['core']) ? 1 : 0;
        $row['status'] = $state['status'];
        $modules[] = $row;
    }

    usort($modules, static function ($a, $b) {
        return [
            (int)$b['enabled'],
            (int)$b['installed'],
            (int)$b['core'],
            (string)$a['module_key'],
        ] <=> [
            (int)$a['enabled'],
            (int)$a['installed'],
            (int)$a['core'],
            (string)$b['module_key'],
        ];
    });

    return $cache[$cacheKey] = $modules;
}

function modules_find(PDO $pdo, string $moduleName, ?int $centreId = null): ?array
{
    foreach (modules_discover($pdo, $centreId) as $module) {
        if ((string)$module['module_key'] === $moduleName) {
            return $module;
        }
    }
    return null;
}

function modules_is_active(PDO $pdo, string $moduleName, ?int $centreId = null): bool
{
    try {
        $module = modules_find($pdo, $moduleName, $centreId);
        return !empty($module['installed']) && !empty($module['enabled']);
    } catch (Throwable $e) {
        return false;
    }
}

function modules_normalise_dependencies(array $dependencies): array
{
    $normalised = [];

    foreach ($dependencies as $key => $dependency) {
        $moduleKey = '';
        $minVersion = null;
        $required = true;

        if (is_string($dependency)) {
            if (is_string($key) && !is_numeric($key)) {
                $moduleKey = trim($key);
                $minVersion = trim($dependency);
            } else {
                $moduleKey = trim($dependency);
            }
        } elseif (is_array($dependency)) {
            $moduleKey = trim((string)($dependency['module'] ?? $dependency['module_key'] ?? $dependency['name'] ?? ''));
            $minVersion = isset($dependency['min_version']) ? trim((string)$dependency['min_version']) : (isset($dependency['version']) ? trim((string)$dependency['version']) : null);
            $required = array_key_exists('required', $dependency) ? (bool)$dependency['required'] : true;
        } elseif (is_string($key) && !is_numeric($key)) {
            $moduleKey = trim($key);
            $minVersion = trim((string)$dependency);
        }

        if ($moduleKey === '') {
            continue;
        }

        $normalised[] = [
            'module' => $moduleKey,
            'min_version' => $minVersion !== '' ? $minVersion : null,
            'required' => $required,
        ];
    }

    return $normalised;
}

function modules_dependency_label(PDO $pdo, array $dependency, ?int $centreId = null): string
{
    $moduleKey = (string)($dependency['module'] ?? '');
    $module = $moduleKey !== '' ? modules_find($pdo, $moduleKey, $centreId) : null;
    return $module ? (string)$module['module_name'] : modules_pretty_name($moduleKey);
}

function modules_unmet_dependencies(PDO $pdo, string $moduleName, ?int $centreId = null): array
{
    $module = modules_find($pdo, $moduleName, $centreId);
    if (!$module) {
        return [];
    }

    $unmet = [];
    foreach (modules_normalise_dependencies((array)($module['dependencies'] ?? [])) as $dependency) {
        if (empty($dependency['required'])) {
            continue;
        }

        $dependencyKey = (string)$dependency['module'];
        $dependencyModule = modules_find($pdo, $dependencyKey, $centreId);
        $reason = '';

        if (!$dependencyModule) {
            $reason = 'not found';
        } elseif (empty($dependencyModule['installed']) || empty($dependencyModule['enabled'])) {
            $reason = 'not active';
        } elseif (!empty($dependency['min_version']) && version_compare((string)$dependencyModule['version'], (string)$dependency['min_version'], '<')) {
            $reason = 'version ' . $dependency['min_version'] . ' or later required';
        }

        if ($reason !== '') {
            $unmet[] = [
                'module' => $dependencyKey,
                'name' => $dependencyModule['module_name'] ?? modules_pretty_name($dependencyKey),
                'reason' => $reason,
                'min_version' => $dependency['min_version'],
            ];
        }
    }

    return $unmet;
}

function modules_dependency_message(array $unmet): string
{
    if (!$unmet) {
        return '';
    }

    $labels = [];
    foreach ($unmet as $dependency) {
        $label = (string)($dependency['name'] ?? modules_pretty_name((string)($dependency['module'] ?? '')));
        if (!empty($dependency['reason'])) {
            $label .= ' (' . $dependency['reason'] . ')';
        }
        $labels[] = $label;
    }

    return implode(', ', $labels);
}

function modules_active_dependents(PDO $pdo, string $moduleName, ?int $centreId = null): array
{
    $dependents = [];
    foreach (modules_discover($pdo, $centreId) as $candidate) {
        if (empty($candidate['installed']) || empty($candidate['enabled'])) {
            continue;
        }

        foreach (modules_normalise_dependencies((array)($candidate['dependencies'] ?? [])) as $dependency) {
            if (!empty($dependency['required']) && (string)$dependency['module'] === $moduleName) {
                $dependents[] = $candidate;
                break;
            }
        }
    }

    return $dependents;
}

function modules_save_state(PDO $pdo, string $moduleName, bool $installed, bool $enabled, ?int $centreId = null): void
{
    $centreId = $centreId ?? modules_current_centre_id();
    $columns = modules_table_columns($pdo);
    $keyColumn = modules_key_column($columns);
    $manifest = modules_read_manifest($moduleName);

    if (!empty($columns['centre_id']) && $centreId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM rescue_modules WHERE `$keyColumn` = :module_key AND centre_id = :centre_id LIMIT 1");
        $stmt->execute([':module_key' => $moduleName, ':centre_id' => $centreId]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM rescue_modules WHERE `$keyColumn` = :module_key LIMIT 1");
        $stmt->execute([':module_key' => $moduleName]);
    }
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    $data = [
        'centre_id' => $centreId,
        $keyColumn => $moduleName,
        'version' => $manifest['version'],
        'installed' => $installed ? 1 : 0,
        'enabled' => $enabled ? 1 : 0,
        'core' => !empty($manifest['core']) ? 1 : 0,
        'status' => $enabled ? 'active' : ($installed ? 'inactive' : 'not installed'),
        'module_name' => $manifest['name'],
        'description' => $manifest['description'],
        'is_core' => !empty($manifest['core']) ? 1 : 0,
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    if (!$existing && !empty($columns['installed_at']) && $installed) {
        $data['installed_at'] = date('Y-m-d H:i:s');
    }

    $usable = array_intersect_key($data, $columns);
    if ($existing) {
        unset($usable[$keyColumn]);
        $sets = [];
        foreach (array_keys($usable) as $column) {
            $sets[] = "`$column` = :$column";
        }
        if (!$sets) {
            return;
        }
        $usable['module_key_where'] = $moduleName;
        if (!empty($columns['centre_id']) && $centreId > 0) {
            $usable['centre_id_where'] = $centreId;
            $sql = "UPDATE rescue_modules SET " . implode(', ', $sets) . " WHERE `$keyColumn` = :module_key_where AND centre_id = :centre_id_where";
        } else {
            $sql = "UPDATE rescue_modules SET " . implode(', ', $sets) . " WHERE `$keyColumn` = :module_key_where";
        }
    } else {
        $columnsSql = '`' . implode('`, `', array_keys($usable)) . '`';
        $paramsSql = ':' . implode(', :', array_keys($usable));
        $sql = "INSERT INTO rescue_modules ($columnsSql) VALUES ($paramsSql)";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($usable);
}

function modules_nav_links(PDO $pdo, string $category, ?int $centreId = null): array
{
    global $lang;

    $links = [];

    try {
        $modules = modules_discover($pdo, $centreId);
    } catch (Throwable $e) {
        return [];
    }

    foreach ($modules as $module) {
        if (empty($module['enabled']) || empty($module['installed'])) {
            continue;
        }

        $navItems = $module['manifest']['nav'] ?? [];
        if (!is_array($navItems)) {
            continue;
        }

        $moduleLang = modules_language((string)$module['module_key']);

        foreach ($navItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (strtolower((string)($item['category'] ?? '')) !== strtolower($category)) {
                continue;
            }

            $permission = trim((string)($item['permission'] ?? $module['manifest']['permission'] ?? ''));
            if ($permission !== '') {
                if (function_exists('registerPermission')) {
                    registerPermission($permission, (string)($item['label'] ?? $module['module_name']), 'module');
                }
                if (function_exists('can') && !can($permission)) {
                    continue;
                }
            }

            $label = (string)($item['label'] ?? $module['module_name']);
            $labelKey = trim((string)($item['label_key'] ?? ''));
            if ($labelKey !== '' && isset($moduleLang[$labelKey])) {
                $label = (string)$moduleLang[$labelKey];
            } elseif ($labelKey !== '' && isset($lang[$labelKey])) {
                $label = (string)$lang[$labelKey];
            }

            $links[] = [
                'label' => $label,
                'href' => (string)($item['href'] ?? '#'),
                'selected' => (string)($item['selected'] ?? 'module_' . $module['module_key']),
                'order' => (int)($item['order'] ?? 100),
            ];
        }
    }

    usort($links, static fn($a, $b) => $a['order'] <=> $b['order']);
    return $links;
}

function modules_nav_html(PDO $pdo, string $category, string $selected, string $selected_child, ?int $centreId = null): string
{
    $html = '';
    try {
        foreach (modules_nav_links($pdo, $category, $centreId) as $link) {
            $isSelected = $selected_child === $link['selected'] || $selected === $link['selected'];
            $html .= '<a href="' . htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') . '"' . ($isSelected ? ' class="selected"' : '') . '><span class="square"></span>' . htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') . '</a>';
        }
    } catch (Throwable $e) {
        return '';
    }
    return $html;
}
