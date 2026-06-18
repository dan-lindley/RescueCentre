<?php
// modules/staff_management/controllers/staff_management_lib.php

function staff_management_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function staff_management_module_language(): array
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

function staff_management_columns(PDO $pdo, string $table): array
{
    $columns = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        if (!empty($row['Field'])) {
            $columns[(string)$row['Field']] = true;
        }
    }
    return $columns;
}

function staff_management_ensure_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rescue_staff_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            centre_id INT NOT NULL,
            account_id INT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            known_as VARCHAR(100) NULL,
            role_type VARCHAR(40) NOT NULL DEFAULT 'volunteer',
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            email VARCHAR(190) NULL,
            telephone VARCHAR(40) NULL,
            address_line1 VARCHAR(190) NULL,
            town VARCHAR(100) NULL,
            postcode VARCHAR(20) NULL,
            latitude DECIMAL(10,7) NULL,
            longitude DECIMAL(10,7) NULL,
            emergency_contact_name VARCHAR(190) NULL,
            emergency_contact_tel VARCHAR(40) NULL,
            notes TEXT NULL,
            deleted TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            INDEX idx_staff_profiles_centre (centre_id),
            INDEX idx_staff_profiles_account (account_id),
            INDEX idx_staff_profiles_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $columns = staff_management_columns($pdo, 'rescue_staff_profiles');
    if (empty($columns['latitude'])) {
        $pdo->exec("ALTER TABLE rescue_staff_profiles ADD COLUMN latitude DECIMAL(10,7) NULL AFTER postcode");
    }
    if (empty($columns['longitude'])) {
        $pdo->exec("ALTER TABLE rescue_staff_profiles ADD COLUMN longitude DECIMAL(10,7) NULL AFTER latitude");
    }
}

function staff_management_register_permissions(): void
{
    if (function_exists('registerPermission')) {
        registerPermission('module.staffmanagement', 'Staff & Volunteers', 'module');
    }
}

function staff_management_can_access(): bool
{
    staff_management_register_permissions();
    if (function_exists('can')) {
        return can('module.staffmanagement');
    }
    return true;
}

function staff_management_user_id(): int
{
    return (int)($_SESSION['user_id'] ?? $_SESSION['account_id'] ?? $GLOBALS['user_id'] ?? 0);
}

function staff_management_centre_id(): int
{
    return (int)($_SESSION['centre_id'] ?? $_POST['centre_id'] ?? $GLOBALS['centre_id'] ?? 0);
}

function staff_management_redirect(array $params = []): void
{
    $url = '../../../module.php?module=staff_management&view=index';
    $query = http_build_query($params);
    header('Location: ' . $url . ($query ? '&' . $query : ''));
    exit;
}

function staff_management_view_url(array $params = []): string
{
    $url = 'module.php?module=staff_management&view=index';
    $query = http_build_query($params);
    return $url . ($query ? '&' . $query : '');
}

function staff_management_null($value): ?string
{
    $value = trim((string)$value);
    return $value !== '' ? $value : null;
}

function staff_management_allowed(string $value, array $allowed, string $fallback): string
{
    $value = strtolower(trim($value));
    return in_array($value, $allowed, true) ? $value : $fallback;
}

function staff_management_decimal_or_null($value): ?string
{
    $value = trim((string)$value);
    if ($value === '' || !is_numeric($value)) {
        return null;
    }

    return (string)round((float)$value, 7);
}

function staff_management_role_options(): array
{
    return [
        'volunteer' => 'ADD_VOLUNTEER',
        'staff' => 'ADD_STAFF',
        'vet' => 'ADD_VET',
        'vet_nurse' => 'ADD_VET_NURSE',
        'animal_care_assistant' => 'ADD_ANIMAL_CARE_ASSISTANT',
        'rehabilitator' => 'ADD_REHABILITATOR',
        'driver' => 'ADD_DRIVER',
        'reception' => 'ADD_RECEPTION',
        'administration' => 'ADD_ADMINISTRATION',
        'fundraising' => 'ADD_FUNDRAISING',
        'maintenance' => 'ADD_MAINTENANCE',
        'trustee' => 'ADD_TRUSTEE',
        'contractor' => 'ADD_CONTRACTOR',
        'other' => 'ADD_OTHER',
    ];
}

function staff_management_status_options(): array
{
    return [
        'active' => 'ADD_ACTIVE',
        'inactive' => 'ADD_INACTIVE',
        'on_leave' => 'ADD_ON_LEAVE',
    ];
}

function staff_management_status_class(string $status): string
{
    return str_replace('_', '-', staff_management_allowed($status, array_keys(staff_management_status_options()), 'inactive'));
}

function staff_management_fetch_accounts(PDO $pdo, int $centre_id): array
{
    $stmt = $pdo->prepare("
        SELECT id, username, first_name, last_name, email
        FROM accounts
        WHERE centre_id = :centre_id
        ORDER BY first_name, last_name, username
    ");
    $stmt->execute([':centre_id' => $centre_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function staff_management_account_label(array $account): string
{
    $name = trim((string)($account['first_name'] ?? '') . ' ' . (string)($account['last_name'] ?? ''));
    $username = trim((string)($account['username'] ?? ''));
    $email = trim((string)($account['email'] ?? ''));

    $label = $name !== '' ? $name : $username;
    if ($email !== '') {
        $label .= $label !== '' ? ' - ' . $email : $email;
    }

    return $label !== '' ? $label : 'Account #' . (int)($account['id'] ?? 0);
}

function staff_management_fetch_people(PDO $pdo, int $centre_id, string $q = ''): array
{
    $params = [':centre_id' => $centre_id];
    $where = "sp.centre_id = :centre_id AND sp.deleted = 0";

    if ($q !== '') {
        $where .= " AND (
            sp.first_name LIKE :q
            OR sp.last_name LIKE :q
            OR sp.known_as LIKE :q
            OR sp.email LIKE :q
            OR sp.telephone LIKE :q
            OR sp.role_type LIKE :q
        )";
        $params[':q'] = '%' . $q . '%';
    }

    $stmt = $pdo->prepare("
        SELECT sp.*, a.username, a.email AS account_email
        FROM rescue_staff_profiles sp
        LEFT JOIN accounts a ON a.id = sp.account_id AND a.centre_id = sp.centre_id
        WHERE $where
        ORDER BY sp.status = 'active' DESC, sp.last_name, sp.first_name
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function staff_management_fetch_person(PDO $pdo, int $id, int $centre_id): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM rescue_staff_profiles
        WHERE id = :id AND centre_id = :centre_id AND deleted = 0
        LIMIT 1
    ");
    $stmt->execute([':id' => $id, ':centre_id' => $centre_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function staff_management_save_person(PDO $pdo, int $centre_id, array $data): int
{
    $id = (int)($data['id'] ?? 0);
    $first_name = staff_management_null($data['first_name'] ?? '');
    $last_name = staff_management_null($data['last_name'] ?? '');

    if (!$first_name || !$last_name) {
        throw new InvalidArgumentException('ADD_ACTION_FAILED');
    }

    $account_id = (int)($data['account_id'] ?? 0);
    $values = [
        'centre_id' => $centre_id,
        'account_id' => $account_id > 0 ? $account_id : null,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'known_as' => staff_management_null($data['known_as'] ?? ''),
        'role_type' => staff_management_allowed((string)($data['role_type'] ?? ''), array_keys(staff_management_role_options()), 'volunteer'),
        'status' => staff_management_allowed((string)($data['status'] ?? ''), array_keys(staff_management_status_options()), 'active'),
        'email' => staff_management_null($data['email'] ?? ''),
        'telephone' => staff_management_null($data['telephone'] ?? ''),
        'address_line1' => staff_management_null($data['address_line1'] ?? ''),
        'town' => staff_management_null($data['town'] ?? ''),
        'postcode' => staff_management_null(strtoupper((string)($data['postcode'] ?? ''))),
        'latitude' => staff_management_decimal_or_null($data['latitude'] ?? ''),
        'longitude' => staff_management_decimal_or_null($data['longitude'] ?? ''),
        'emergency_contact_name' => staff_management_null($data['emergency_contact_name'] ?? ''),
        'emergency_contact_tel' => staff_management_null($data['emergency_contact_tel'] ?? ''),
        'notes' => staff_management_null($data['notes'] ?? ''),
    ];

    if ($id > 0) {
        $existing = staff_management_fetch_person($pdo, $id, $centre_id);
        if (!$existing) {
            throw new InvalidArgumentException('ADD_PERSON_NOT_FOUND');
        }

        $values['id'] = $id;
        $sets = [];
        foreach (array_keys($values) as $column) {
            if ($column !== 'centre_id' && $column !== 'id') {
                $sets[] = "`$column` = :$column";
            }
        }
        $sets[] = "updated_at = NOW()";

        $stmt = $pdo->prepare("
            UPDATE rescue_staff_profiles
            SET " . implode(', ', $sets) . "
            WHERE id = :id AND centre_id = :centre_id
        ");
        $stmt->execute($values);
        return $id;
    }

    $columns = array_keys($values);
    $stmt = $pdo->prepare("
        INSERT INTO rescue_staff_profiles (`" . implode('`, `', $columns) . "`)
        VALUES (:" . implode(', :', $columns) . ")
    ");
    $stmt->execute($values);
    return (int)$pdo->lastInsertId();
}

function staff_management_delete_person(PDO $pdo, int $id, int $centre_id): void
{
    $stmt = $pdo->prepare("
        UPDATE rescue_staff_profiles
        SET deleted = 1, updated_at = NOW()
        WHERE id = :id AND centre_id = :centre_id
    ");
    $stmt->execute([':id' => $id, ':centre_id' => $centre_id]);
}

function staff_management_audit_snapshot(?array $person): ?array
{
    if (!$person) {
        return null;
    }

    return [
        'id' => (int)($person['id'] ?? 0),
        'centre_id' => (int)($person['centre_id'] ?? 0),
        'account_id' => isset($person['account_id']) ? (int)$person['account_id'] : null,
        'role_type' => $person['role_type'] ?? null,
        'status' => $person['status'] ?? null,
        'has_email' => !empty($person['email']) ? 1 : 0,
        'has_telephone' => !empty($person['telephone']) ? 1 : 0,
        'has_address' => (!empty($person['address_line1']) || !empty($person['town']) || !empty($person['postcode'])) ? 1 : 0,
        'has_location' => ($person['latitude'] !== null && $person['longitude'] !== null) ? 1 : 0,
        'has_emergency_contact' => (!empty($person['emergency_contact_name']) || !empty($person['emergency_contact_tel'])) ? 1 : 0,
    ];
}
