<?php
// controllers/cohorts_lib.php

function cohorts_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cohorts_ensure_tables(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rescue_cohorts (
            cohort_id INT AUTO_INCREMENT PRIMARY KEY,
            centre_id INT NOT NULL,
            location_id INT NOT NULL DEFAULT 0,
            location_key VARCHAR(190) NULL,
            location_label VARCHAR(190) NULL,
            cohort_name VARCHAR(150) NOT NULL,
            species_id INT NULL,
            status ENUM('active','ended') NOT NULL DEFAULT 'active',
            created_by INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ended_by INT NULL,
            ended_at DATETIME NULL,
            notes TEXT NULL,
            INDEX idx_cohorts_centre (centre_id),
            INDEX idx_cohorts_location (location_id),
            INDEX idx_cohorts_location_key (location_key),
            INDEX idx_cohorts_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $columns = $pdo->query("SHOW COLUMNS FROM rescue_cohorts")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('location_key', $columns, true)) {
        $pdo->exec("ALTER TABLE rescue_cohorts ADD COLUMN location_key VARCHAR(190) NULL AFTER location_id");
        $pdo->exec("ALTER TABLE rescue_cohorts ADD INDEX idx_cohorts_location_key (location_key)");
    }
    if (!in_array('location_label', $columns, true)) {
        $pdo->exec("ALTER TABLE rescue_cohorts ADD COLUMN location_label VARCHAR(190) NULL AFTER location_key");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rescue_cohort_members (
            cohort_member_id INT AUTO_INCREMENT PRIMARY KEY,
            cohort_id INT NOT NULL,
            patient_id INT NOT NULL,
            joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            joined_by INT NOT NULL,
            left_at DATETIME NULL,
            left_by INT NULL,
            leave_reason VARCHAR(255) NULL,
            INDEX idx_cohort_members_cohort (cohort_id),
            INDEX idx_cohort_members_patient (patient_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rescue_cohort_exclusions (
            exclusion_id INT AUTO_INCREMENT PRIMARY KEY,
            cohort_id INT NOT NULL,
            patient_id INT NOT NULL,
            reason VARCHAR(255) NULL,
            created_by INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_cohort_exclusion (cohort_id, patient_id),
            INDEX idx_cohort_exclusions_cohort (cohort_id),
            INDEX idx_cohort_exclusions_patient (patient_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rescue_cohort_feeding_logs (
            cohort_feed_id INT AUTO_INCREMENT PRIMARY KEY,
            cohort_id INT NOT NULL,
            food_item_id INT NULL,
            amount_in DECIMAL(10,2) NULL,
            amount_out DECIMAL(10,2) NULL,
            amount_unit VARCHAR(50) NULL,
            fed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            logged_by INT NOT NULL,
            notes TEXT NULL,
            INDEX idx_cohort_feed_cohort (cohort_id),
            INDEX idx_cohort_feed_fed_at (fed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rescue_cohort_care_notes (
            cohort_note_id INT AUTO_INCREMENT PRIMARY KEY,
            cohort_id INT NOT NULL,
            note_text TEXT NOT NULL,
            created_by INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_cohort_notes_cohort (cohort_id),
            INDEX idx_cohort_notes_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function cohorts_user_id(): int
{
    return (int)($_SESSION['account_id'] ?? $_SESSION['user_id'] ?? $GLOBALS['user_id'] ?? 0);
}

function cohorts_centre_id(): int
{
    return (int)($_SESSION['centre_id'] ?? $_POST['centre_id'] ?? $GLOBALS['centre_id'] ?? 0);
}

function cohorts_redirect(string $url, array $params = []): void
{
    $query = http_build_query($params);
    header('Location: ' . $url . ($query ? ((str_contains($url, '?') ? '&' : '?') . $query) : ''));
    exit;
}

function cohorts_fetch(PDO $pdo, int $cohort_id, int $centre_id): ?array
{
    $stmt = $pdo->prepare("
        SELECT c.*, rl.location_name
        FROM rescue_cohorts c
        LEFT JOIN rescue_locations rl
            ON rl.location_id = c.location_id
           AND rl.centre_id = c.centre_id
        WHERE c.cohort_id = :cohort_id
          AND c.centre_id = :centre_id
        LIMIT 1
    ");
    $stmt->execute([':cohort_id' => $cohort_id, ':centre_id' => $centre_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function cohorts_active_member_ids(PDO $pdo, int $cohort_id): array
{
    $stmt = $pdo->prepare("
        SELECT patient_id
        FROM rescue_cohort_members
        WHERE cohort_id = :cohort_id
          AND left_at IS NULL
        ORDER BY patient_id ASC
    ");
    $stmt->execute([':cohort_id' => $cohort_id]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
}

function cohorts_patient_current_admissions(PDO $pdo, array $patient_ids, int $centre_id): array
{
    if (!$patient_ids) {
        return [];
    }

    $patient_ids = array_values(array_unique(array_map('intval', $patient_ids)));
    $placeholders = implode(',', array_fill(0, count($patient_ids), '?'));

    $stmt = $pdo->prepare("
        SELECT
            p.patient_id,
            p.name,
            p.animal_species,
            p.sex,
            a.admission_id,
            a.current_location,
            a.current_location_id
        FROM rescue_patients p
        INNER JOIN rescue_admissions a
            ON a.admission_id = (
                SELECT a2.admission_id
                FROM rescue_admissions a2
                WHERE a2.patient_id = p.patient_id
                  AND a2.centre_id = ?
                ORDER BY
                    CASE WHEN a2.status = 'Active' THEN 0 ELSE 1 END,
                    a2.admission_id DESC
                LIMIT 1
            )
        WHERE p.patient_id IN ({$placeholders})
          AND p.centre_id = ?
    ");

    $stmt->execute([$centre_id, ...$patient_ids, $centre_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $by_id = [];
    foreach ($rows as $row) {
        $by_id[(int)$row['patient_id']] = $row;
    }
    return $by_id;
}

function cohorts_patient_has_other_active_cohort(PDO $pdo, int $patient_id, ?int $ignore_cohort_id = null): bool
{
    $params = [':patient_id' => $patient_id];
    $ignoreSql = '';
    if ($ignore_cohort_id) {
        $ignoreSql = 'AND cm.cohort_id <> :ignore_cohort_id';
        $params[':ignore_cohort_id'] = $ignore_cohort_id;
    }

    $stmt = $pdo->prepare("
        SELECT cm.cohort_member_id
        FROM rescue_cohort_members cm
        INNER JOIN rescue_cohorts c ON c.cohort_id = cm.cohort_id
        WHERE cm.patient_id = :patient_id
          AND cm.left_at IS NULL
          AND c.status = 'active'
          {$ignoreSql}
        LIMIT 1
    ");
    $stmt->execute($params);
    return (bool)$stmt->fetchColumn();
}

function cohorts_add_member(PDO $pdo, int $cohort_id, int $patient_id, int $user_id): void
{
    $stmt = $pdo->prepare("
        SELECT cohort_member_id
        FROM rescue_cohort_members
        WHERE cohort_id = :cohort_id
          AND patient_id = :patient_id
          AND left_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([':cohort_id' => $cohort_id, ':patient_id' => $patient_id]);
    if ($stmt->fetchColumn()) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO rescue_cohort_members (cohort_id, patient_id, joined_by)
        VALUES (:cohort_id, :patient_id, :joined_by)
    ");
    $stmt->execute([
        ':cohort_id' => $cohort_id,
        ':patient_id' => $patient_id,
        ':joined_by' => $user_id,
    ]);
}
