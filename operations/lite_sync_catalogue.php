<?php

function lite_sync_setting(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare('SELECT setting_value FROM lite_settings WHERE setting_key = :key LIMIT 1');
    $stmt->execute([':key' => $key]);
    $value = $stmt->fetchColumn();
    return $value === false || $value === null ? $default : (string)$value;
}

function lite_sync_settings(PDO $pdo): array
{
    return [
        'enabled' => lite_sync_setting($pdo, 'sync_enabled', '0') === '1',
        'api_url' => lite_sync_setting($pdo, 'sync_api_url', 'https://myrescuecentre.com/api/lite_sync.php'),
        'api_key' => lite_sync_setting($pdo, 'sync_api_key', ''),
        'install_id' => lite_sync_setting($pdo, 'sync_install_id', ''),
        'hosted_centre_id' => lite_sync_setting($pdo, 'sync_hosted_centre_id', ''),
    ];
}

function lite_sync_post_json(string $url, array $payload): array
{
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if (!is_string($body)) {
        throw new RuntimeException('Could not build sync request.');
    }

    $statusCode = 0;
    $response = false;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Hosted sync connection failed: ' . $curlError);
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $body,
                'timeout' => 30,
                'ignore_errors' => true,
            ],
        ]);
        $response = file_get_contents($url, false, $context);
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
            $statusCode = (int)$match[1];
        }
    }

    if (!is_string($response) || trim($response) === '') {
        throw new RuntimeException('Hosted sync returned an empty response.');
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        $preview = substr(preg_replace('/\s+/', ' ', strip_tags($response)) ?: '', 0, 180);
        throw new RuntimeException('Hosted sync returned invalid JSON' . ($preview !== '' ? ': ' . $preview : '.'));
    }

    if ($statusCode < 200 || $statusCode >= 300 || (($decoded['status'] ?? 'ok') === 'error')) {
        throw new RuntimeException((string)($decoded['message'] ?? 'Hosted sync request failed.'));
    }

    return $decoded;
}

function lite_sync_search_catalogue(PDO $pdo, string $catalogue, string $query): array
{
    $settings = lite_sync_settings($pdo);
    if (!$settings['enabled']) {
        throw new RuntimeException('Hosted sync is not enabled for this Lite install.');
    }
    if ($settings['api_url'] === '') {
        throw new RuntimeException('Hosted sync API URL is missing.');
    }

    $response = lite_sync_post_json($settings['api_url'], [
        'action' => 'catalogue_search',
        'catalogue' => $catalogue,
        'q' => $query,
        'api_key' => $settings['api_key'],
        'install_id' => $settings['install_id'],
        'hosted_centre_id' => $settings['hosted_centre_id'],
        'limit' => 25,
    ]);

    $items = $response['items'] ?? [];
    if (!is_array($items)) {
        throw new RuntimeException('Hosted sync search response did not contain an item list.');
    }

    return $items;
}

function lite_sync_fetch_catalogue(PDO $pdo, string $catalogue, string $mode, string $value, array $ids = []): array
{
    $settings = lite_sync_settings($pdo);
    if (!$settings['enabled']) {
        throw new RuntimeException('Hosted sync is not enabled for this Lite install.');
    }
    if ($settings['api_url'] === '') {
        throw new RuntimeException('Hosted sync API URL is missing.');
    }

    $response = lite_sync_post_json($settings['api_url'], [
        'action' => 'catalogue',
        'catalogue' => $catalogue,
        'mode' => $mode,
        'value' => $value,
        'ids' => array_values(array_unique(array_map('intval', $ids))),
        'api_key' => $settings['api_key'],
        'install_id' => $settings['install_id'],
        'hosted_centre_id' => $settings['hosted_centre_id'],
        'limit' => 1000,
    ]);

    $items = $response['items'] ?? $response['data'] ?? [];
    if (!is_array($items)) {
        throw new RuntimeException('Hosted sync catalogue response did not contain an item list.');
    }

    return $items;
}

function lite_sync_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
          AND COLUMN_NAME = :column
    ");
    $stmt->execute([':table' => $table, ':column' => $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function lite_sync_ensure_catalogue_schema(PDO $pdo, string $catalogue): void
{
    if ($catalogue === 'medications') {
        if (!lite_sync_column_exists($pdo, 'rescue_medications', 'medication_id') && lite_sync_column_exists($pdo, 'rescue_medications', 'med_profile_id')) {
            $pdo->exec('ALTER TABLE rescue_medications CHANGE COLUMN med_profile_id medication_id INT AUTO_INCREMENT');
        }
        if (!lite_sync_column_exists($pdo, 'rescue_medications', 'medication_name') && lite_sync_column_exists($pdo, 'rescue_medications', 'medication')) {
            $pdo->exec('ALTER TABLE rescue_medications CHANGE COLUMN medication medication_name VARCHAR(190) NOT NULL');
        }
        $columns = [
            'common_name' => 'VARCHAR(190) NULL',
            'class' => 'VARCHAR(120) NULL',
            'description' => 'TEXT NULL',
            'contraindications' => 'TEXT NULL',
            'cautions' => 'TEXT NULL',
            'dose' => 'VARCHAR(120) NULL',
            'route' => 'VARCHAR(120) NULL',
            'side_effects' => 'TEXT NULL',
        ];
        foreach ($columns as $column => $definition) {
            if (!lite_sync_column_exists($pdo, 'rescue_medications', $column)) {
                $pdo->exec('ALTER TABLE rescue_medications ADD COLUMN ' . $column . ' ' . $definition);
            }
        }
    }

    if ($catalogue === 'feed') {
        if (!lite_sync_column_exists($pdo, 'rescue_diet_items', 'name') && lite_sync_column_exists($pdo, 'rescue_diet_items', 'item_name')) {
            $pdo->exec('ALTER TABLE rescue_diet_items CHANGE COLUMN item_name name VARCHAR(190) NOT NULL');
        }
        $columns = [
            'type' => 'VARCHAR(80) NULL',
            'category' => 'VARCHAR(80) NULL',
            'shelf_life_days' => 'INT NULL',
            'kcal_per_g' => 'DECIMAL(10,3) NULL',
            'kcal_per_ml' => 'DECIMAL(10,3) NULL',
            'notes' => 'TEXT NULL',
        ];
        foreach ($columns as $column => $definition) {
            if (!lite_sync_column_exists($pdo, 'rescue_diet_items', $column)) {
                $pdo->exec('ALTER TABLE rescue_diet_items ADD COLUMN ' . $column . ' ' . $definition);
            }
        }
    }
}

function lite_sync_import_species(PDO $pdo, array $items): int
{
    $existsStmt = $pdo->prepare('SELECT species_id FROM rescue_animal_species WHERE LOWER(species_name) = LOWER(:species_name) LIMIT 1');
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO rescue_animal_species
            (species_name, scientific_name, animal_type, animal_order, gbif_id, iucn_status, reference,
             species_weight_from, species_weight_to, species_weight_unit,
             species_measurement_from, species_measurement_to, species_measurement_standard, species_measurement_unit)
        VALUES
            (:species_name, :scientific_name, :animal_type, :animal_order, :gbif_id, :iucn_status, :reference,
             :species_weight_from, :species_weight_to, :species_weight_unit,
             :species_measurement_from, :species_measurement_to, :species_measurement_standard, :species_measurement_unit)
    ");

    $count = 0;
    foreach ($items as $item) {
        if (!is_array($item)) continue;
        $name = trim((string)($item['species_name'] ?? $item['name'] ?? ''));
        if ($name === '') continue;
        $existsStmt->execute([':species_name' => $name]);
        if ($existsStmt->fetchColumn()) continue;
        $stmt->execute([
            ':species_name' => $name,
            ':scientific_name' => $item['scientific_name'] ?? null,
            ':animal_type' => $item['animal_type'] ?? $item['species_type'] ?? null,
            ':animal_order' => $item['animal_order'] ?? $item['class'] ?? null,
            ':gbif_id' => $item['gbif_id'] ?? null,
            ':iucn_status' => $item['iucn_status'] ?? null,
            ':reference' => $item['reference'] ?? null,
            ':species_weight_from' => $item['species_weight_from'] ?? null,
            ':species_weight_to' => $item['species_weight_to'] ?? null,
            ':species_weight_unit' => $item['species_weight_unit'] ?? null,
            ':species_measurement_from' => $item['species_measurement_from'] ?? null,
            ':species_measurement_to' => $item['species_measurement_to'] ?? null,
            ':species_measurement_standard' => $item['species_measurement_standard'] ?? null,
            ':species_measurement_unit' => $item['species_measurement_unit'] ?? null,
        ]);
        $count += $stmt->rowCount() > 0 ? 1 : 0;
    }
    return $count;
}

function lite_sync_import_medications(PDO $pdo, array $items): int
{
    $existsStmt = $pdo->prepare('SELECT medication_id FROM rescue_medications WHERE LOWER(medication_name) = LOWER(:medication_name) LIMIT 1');
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO rescue_medications
            (medication_name, common_name, class, description, contraindications, cautions, dose, route, side_effects, active)
        VALUES
            (:medication_name, :common_name, :class, :description, :contraindications, :cautions, :dose, :route, :side_effects, 1)
    ");

    $count = 0;
    foreach ($items as $item) {
        if (!is_array($item)) continue;
        $name = trim((string)($item['medication_name'] ?? $item['name'] ?? ''));
        if ($name === '') continue;
        $existsStmt->execute([':medication_name' => $name]);
        if ($existsStmt->fetchColumn()) continue;
        $stmt->execute([
            ':medication_name' => $name,
            ':common_name' => $item['common_name'] ?? null,
            ':class' => $item['class'] ?? null,
            ':description' => $item['description'] ?? null,
            ':contraindications' => $item['contraindications'] ?? null,
            ':cautions' => $item['cautions'] ?? null,
            ':dose' => $item['dose'] ?? null,
            ':route' => $item['route'] ?? null,
            ':side_effects' => $item['side_effects'] ?? null,
        ]);
        $count += $stmt->rowCount() > 0 ? 1 : 0;
    }
    return $count;
}

function lite_sync_import_feed(PDO $pdo, array $items, int $centreId, bool $enableForCentre): int
{
    $itemStmt = $pdo->prepare("
        INSERT IGNORE INTO rescue_diet_items
            (name, type, category, default_unit, shelf_life_days, kcal_per_g, kcal_per_ml, notes, active)
        VALUES
            (:name, :type, :category, :default_unit, :shelf_life_days, :kcal_per_g, :kcal_per_ml, :notes, 1)
    ");
    $findStmt = $pdo->prepare('SELECT diet_item_id FROM rescue_diet_items WHERE name = :name LIMIT 1');
    $centreStmt = $pdo->prepare("
        INSERT INTO rescue_centre_diet_items (centre_id, diet_item_id, is_enabled, use_within_days)
        VALUES (:centre_id, :diet_item_id, 1, :use_within_days)
        ON DUPLICATE KEY UPDATE is_enabled = 1, use_within_days = COALESCE(use_within_days, VALUES(use_within_days))
    ");

    $count = 0;
    foreach ($items as $item) {
        if (!is_array($item)) continue;
        $name = trim((string)($item['name'] ?? $item['item_name'] ?? ''));
        if ($name === '') continue;
        $category = (string)($item['category'] ?? '');
        $useWithin = isset($item['use_within_days']) ? (int)$item['use_within_days'] : (strtolower($category) === 'liquid' ? 730 : 365);
        $findStmt->execute([':name' => $name]);
        $dietItemId = (int)$findStmt->fetchColumn();
        if ($dietItemId <= 0) {
            $itemStmt->execute([
                ':name' => $name,
                ':type' => $item['type'] ?? $item['feed_type'] ?? null,
                ':category' => $category !== '' ? $category : null,
                ':default_unit' => $item['default_unit'] ?? $item['unit'] ?? null,
                ':shelf_life_days' => $item['shelf_life_days'] ?? null,
                ':kcal_per_g' => $item['kcal_per_g'] ?? null,
                ':kcal_per_ml' => $item['kcal_per_ml'] ?? null,
                ':notes' => $item['notes'] ?? null,
            ]);
            $count += $itemStmt->rowCount() > 0 ? 1 : 0;
            $findStmt->execute([':name' => $name]);
            $dietItemId = (int)$findStmt->fetchColumn();
        }

        if ($enableForCentre && $centreId > 0) {
            if ($dietItemId > 0) {
                $centreStmt->execute([
                    ':centre_id' => $centreId,
                    ':diet_item_id' => $dietItemId,
                    ':use_within_days' => $useWithin,
                ]);
            }
        }
    }
    return $count;
}
