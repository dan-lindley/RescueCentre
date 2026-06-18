<?php
// modules/address_book/controllers/address_book_lib.php

function address_book_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function address_book_module_language(): array
{
    $language = (string)($_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'en');
    $language = preg_replace('/[^a-z]/', '', strtolower($language));
    if ($language === '') {
        $language = 'en';
    }

    $file = __DIR__ . '/../languages/lang.' . $language . '.php';
    if (!is_file($file)) {
        $file = __DIR__ . '/../languages/lang.en.php';
    }

    $translations = require $file;
    return is_array($translations) ? $translations : [];
}

function address_book_columns(PDO $pdo, string $table): array
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

function address_book_ensure_schema(PDO $pdo): void
{
    $columns = address_book_columns($pdo, 'rescue_finders');
    $adds = [
        'finder_email' => "ADD COLUMN finder_email VARCHAR(190) NULL AFTER finder_tel",
        'finder_address_line1' => "ADD COLUMN finder_address_line1 VARCHAR(190) NULL AFTER finder_email",
        'finder_town' => "ADD COLUMN finder_town VARCHAR(100) NULL AFTER finder_address_line1",
        'finder_postcode' => "ADD COLUMN finder_postcode VARCHAR(20) NULL AFTER finder_town",
        'preferred_contact_method' => "ADD COLUMN preferred_contact_method VARCHAR(30) NULL AFTER finder_postcode",
        'has_donated' => "ADD COLUMN has_donated TINYINT(1) NOT NULL DEFAULT 0 AFTER preferred_contact_method",
        'gift_aid_consent' => "ADD COLUMN gift_aid_consent TINYINT(1) NOT NULL DEFAULT 0 AFTER has_donated",
    ];

    foreach ($adds as $column => $sql) {
        if (empty($columns[$column])) {
            $pdo->exec("ALTER TABLE rescue_finders $sql");
        }
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rescue_finder_admissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            finder_id INT NOT NULL,
            admission_id INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_finder_admission (finder_id, admission_id),
            INDEX idx_finder_admissions_finder (finder_id),
            INDEX idx_finder_admissions_admission (admission_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function address_book_user_id(): int
{
    return (int)($_SESSION['user_id'] ?? $_SESSION['account_id'] ?? $GLOBALS['user_id'] ?? 0);
}

function address_book_centre_id(): int
{
    return (int)($_SESSION['centre_id'] ?? $_POST['centre_id'] ?? $GLOBALS['centre_id'] ?? 0);
}

function address_book_redirect(array $params = []): void
{
    $url = '../../../module.php?module=address_book&view=address_book';
    $query = http_build_query($params);
    header('Location: ' . $url . ($query ? '&' . $query : ''));
    exit;
}

function address_book_view_url(array $params = []): string
{
    $url = 'module.php?module=address_book&view=address_book';
    $query = http_build_query($params);
    return $url . ($query ? '&' . $query : '');
}

function address_book_null(string $value): ?string
{
    $value = trim($value);
    return $value !== '' ? $value : null;
}

function address_book_phone(string $value): ?string
{
    $value = trim($value);
    $normalised = preg_replace('/[^0-9+]/', '', $value);
    return $normalised !== '' ? $normalised : address_book_null($value);
}

function address_book_postcode(string $value): ?string
{
    $value = strtoupper(trim(preg_replace('/\s+/', ' ', $value)));
    if ($value === '') {
        return null;
    }

    return substr($value, 0, 20);
}

function address_book_contact_method(string $value): ?string
{
    $value = strtolower(trim($value));
    $allowed = ['telephone', 'sms', 'email', 'none'];
    return in_array($value, $allowed, true) ? $value : null;
}

function address_book_bool($value): int
{
    return !empty($value) ? 1 : 0;
}

function address_book_short_date($value): string
{
    if (empty($value)) {
        return '';
    }

    try {
        $date = new DateTime((string)$value);
    } catch (Throwable $e) {
        return '';
    }

    $day = (int)$date->format('j');
    $suffix = 'th';
    if (!in_array($day % 100, [11, 12, 13], true)) {
        $suffix = match ($day % 10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    }

    return $day . $suffix . ' ' . $date->format('M Y');
}

function address_book_short_duration($value): string
{
    if (empty($value)) {
        return '';
    }

    try {
        $date = new DateTime((string)$value);
        $now = new DateTime('now');
    } catch (Throwable $e) {
        return '';
    }

    $interval = $date->diff($now);
    $units = [
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
    ];

    foreach ($units as $property => $label) {
        $count = (int)$interval->$property;
        if ($count > 0) {
            return $count . ' ' . $label . ($count === 1 ? '' : 's');
        }
    }

    return 'Today';
}

function address_book_audit_snapshot(?array $finder): ?array
{
    if (!$finder) {
        return null;
    }

    return [
        'finder_id' => (int)($finder['finder_id'] ?? 0),
        'centre_id' => (int)($finder['centre_id'] ?? 0),
        'preferred_contact_method' => $finder['preferred_contact_method'] ?? null,
        'has_donated' => (int)($finder['has_donated'] ?? 0),
        'gift_aid_consent' => (int)($finder['gift_aid_consent'] ?? 0),
        'has_telephone' => !empty($finder['finder_tel']) ? 1 : 0,
        'has_email' => !empty($finder['finder_email']) ? 1 : 0,
        'has_address' => (!empty($finder['finder_address_line1']) || !empty($finder['finder_town']) || !empty($finder['finder_postcode'])) ? 1 : 0,
    ];
}

function address_book_fetch_finders(PDO $pdo, int $centre_id, string $q = ''): array
{
    $params = [':centre_id' => $centre_id];
    $where = "centre_id = :centre_id AND (deleted = 0 OR deleted IS NULL)";

    if ($q !== '') {
        $where .= " AND (
            finder_name LIKE :q_name
            OR finder_tel LIKE :q_tel
            OR finder_email LIKE :q_email
            OR finder_address_line1 LIKE :q_address
            OR finder_town LIKE :q_town
            OR finder_postcode LIKE :q_postcode
        )";
        $like = '%' . $q . '%';
        $params[':q_name'] = $like;
        $params[':q_tel'] = $like;
        $params[':q_email'] = $like;
        $params[':q_address'] = $like;
        $params[':q_town'] = $like;
        $params[':q_postcode'] = $like;
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM rescue_finders
        WHERE $where
        ORDER BY finder_name ASC, finder_id DESC
        LIMIT 150
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function address_book_fetch_finder(PDO $pdo, int $finder_id, int $centre_id): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM rescue_finders
        WHERE finder_id = :finder_id
          AND centre_id = :centre_id
          AND (deleted = 0 OR deleted IS NULL)
        LIMIT 1
    ");
    $stmt->execute([':finder_id' => $finder_id, ':centre_id' => $centre_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function address_book_find_existing(PDO $pdo, int $centre_id, string $name, ?string $tel, ?string $email): ?array
{
    if ($tel !== null) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM rescue_finders
            WHERE centre_id = :centre_id
              AND finder_tel = :finder_tel
              AND (deleted = 0 OR deleted IS NULL)
            LIMIT 1
        ");
        $stmt->execute([':centre_id' => $centre_id, ':finder_tel' => $tel]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }

    if ($email !== null) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM rescue_finders
            WHERE centre_id = :centre_id
              AND finder_email = :finder_email
              AND (deleted = 0 OR deleted IS NULL)
            LIMIT 1
        ");
        $stmt->execute([':centre_id' => $centre_id, ':finder_email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM rescue_finders
        WHERE centre_id = :centre_id
          AND finder_name = :finder_name
          AND (deleted = 0 OR deleted IS NULL)
        LIMIT 1
    ");
    $stmt->execute([':centre_id' => $centre_id, ':finder_name' => $name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function address_book_save_finder(PDO $pdo, int $centre_id, array $data): int
{
    $finder_id = (int)($data['finder_id'] ?? 0);
    $name = trim((string)($data['finder_name'] ?? ''));
    $tel = address_book_phone((string)($data['finder_tel'] ?? ''));
    $email = address_book_null(strtolower((string)($data['finder_email'] ?? '')));
    $address = address_book_null((string)($data['finder_address_line1'] ?? ''));
    $town = address_book_null((string)($data['finder_town'] ?? ''));
    $postcode = address_book_postcode((string)($data['finder_postcode'] ?? ''));
    $method = address_book_contact_method((string)($data['preferred_contact_method'] ?? ''));
    $has_donated = address_book_bool($data['has_donated'] ?? null);
    $gift_aid = address_book_bool($data['gift_aid_consent'] ?? null);

    if ($name === '') {
        throw new InvalidArgumentException('ADD_FINDER_NAME_REQUIRED');
    }

    $existing = $finder_id > 0
        ? address_book_fetch_finder($pdo, $finder_id, $centre_id)
        : address_book_find_existing($pdo, $centre_id, $name, $tel, $email);

    if ($existing) {
        $finder_id = (int)$existing['finder_id'];
        $stmt = $pdo->prepare("
            UPDATE rescue_finders
            SET finder_name = :finder_name,
                finder_tel = :finder_tel,
                finder_email = :finder_email,
                finder_address_line1 = :finder_address_line1,
                finder_town = :finder_town,
                finder_postcode = :finder_postcode,
                preferred_contact_method = :preferred_contact_method,
                has_donated = :has_donated,
                gift_aid_consent = :gift_aid_consent,
                updated_at = NOW()
            WHERE finder_id = :finder_id
              AND centre_id = :centre_id
        ");
        $stmt->execute([
            ':finder_name' => $name,
            ':finder_tel' => $tel,
            ':finder_email' => $email,
            ':finder_address_line1' => $address,
            ':finder_town' => $town,
            ':finder_postcode' => $postcode,
            ':preferred_contact_method' => $method,
            ':has_donated' => $has_donated,
            ':gift_aid_consent' => $gift_aid,
            ':finder_id' => $finder_id,
            ':centre_id' => $centre_id,
        ]);
        return $finder_id;
    }

    $stmt = $pdo->prepare("
        INSERT INTO rescue_finders
            (centre_id, finder_name, finder_tel, finder_email, finder_address_line1, finder_town, finder_postcode, preferred_contact_method, has_donated, gift_aid_consent, created_at, updated_at, deleted)
        VALUES
            (:centre_id, :finder_name, :finder_tel, :finder_email, :finder_address_line1, :finder_town, :finder_postcode, :preferred_contact_method, :has_donated, :gift_aid_consent, NOW(), NOW(), 0)
    ");
    $stmt->execute([
        ':centre_id' => $centre_id,
        ':finder_name' => $name,
        ':finder_tel' => $tel,
        ':finder_email' => $email,
        ':finder_address_line1' => $address,
        ':finder_town' => $town,
        ':finder_postcode' => $postcode,
        ':preferred_contact_method' => $method,
        ':has_donated' => $has_donated,
        ':gift_aid_consent' => $gift_aid,
    ]);

    return (int)$pdo->lastInsertId();
}

function address_book_delete_finder(PDO $pdo, int $finder_id, int $centre_id): void
{
    $stmt = $pdo->prepare("
        UPDATE rescue_finders
        SET deleted = 1,
            updated_at = NOW()
        WHERE finder_id = :finder_id
          AND centre_id = :centre_id
    ");
    $stmt->execute([':finder_id' => $finder_id, ':centre_id' => $centre_id]);
}

function address_book_fetch_admission(PDO $pdo, int $admission_id, int $centre_id): ?array
{
    $stmt = $pdo->prepare("
        SELECT a.admission_id, a.patient_id, a.admission_date, p.name, p.animal_species
        FROM rescue_admissions a
        LEFT JOIN rescue_patients p
            ON p.patient_id = a.patient_id
           AND p.centre_id = a.centre_id
        WHERE a.admission_id = :admission_id
          AND a.centre_id = :centre_id
        LIMIT 1
    ");
    $stmt->execute([':admission_id' => $admission_id, ':centre_id' => $centre_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function address_book_link_admission(PDO $pdo, int $finder_id, int $admission_id, int $centre_id): void
{
    if (!address_book_fetch_finder($pdo, $finder_id, $centre_id)) {
        throw new InvalidArgumentException('ADD_FINDER_NOT_FOUND');
    }
    if (!address_book_fetch_admission($pdo, $admission_id, $centre_id)) {
        throw new InvalidArgumentException('ADD_ADMISSION_NOT_FOUND');
    }

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO rescue_finder_admissions (finder_id, admission_id, created_at)
        VALUES (:finder_id, :admission_id, NOW())
    ");
    $stmt->execute([':finder_id' => $finder_id, ':admission_id' => $admission_id]);
}

function address_book_link_existing_admissions(PDO $pdo, int $finder_id, int $centre_id): int
{
    if (!address_book_fetch_finder($pdo, $finder_id, $centre_id)) {
        throw new InvalidArgumentException('ADD_FINDER_NOT_FOUND');
    }

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO rescue_finder_admissions (finder_id, admission_id, created_at)
        SELECT :finder_id, a.admission_id, NOW()
        FROM rescue_admissions a
        WHERE a.finder_id = :finder_id_lookup
          AND a.centre_id = :centre_id
    ");
    $stmt->execute([
        ':finder_id' => $finder_id,
        ':finder_id_lookup' => $finder_id,
        ':centre_id' => $centre_id,
    ]);

    return $stmt->rowCount();
}

function address_book_fetch_admissions(PDO $pdo, int $finder_id, int $centre_id): array
{
    $stmt = $pdo->prepare("
        SELECT
            fa.admission_id,
            fa.created_at,
            a.patient_id,
            a.admission_date,
            p.name,
            p.animal_species
        FROM rescue_finder_admissions fa
        INNER JOIN rescue_admissions a
            ON a.admission_id = fa.admission_id
           AND a.centre_id = :centre_id
        LEFT JOIN rescue_patients p
            ON p.patient_id = a.patient_id
           AND p.centre_id = a.centre_id
        WHERE fa.finder_id = :finder_id
        ORDER BY a.admission_date DESC, fa.created_at DESC
    ");
    $stmt->execute([':finder_id' => $finder_id, ':centre_id' => $centre_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
