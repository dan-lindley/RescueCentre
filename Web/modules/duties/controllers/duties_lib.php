<?php
// modules/duties/controllers/duties_lib.php

function duties_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function duties_module_language(): array
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

function duties_register_permissions(): void
{
    if (function_exists('registerPermission')) {
        registerPermission('module.duties', 'Duties', 'module');
    }
}

function duties_can_access(): bool
{
    duties_register_permissions();
    return function_exists('can') ? can('module.duties') : true;
}

function duties_user_id(): int
{
    return (int)($_SESSION['user_id'] ?? $_SESSION['account_id'] ?? $GLOBALS['user_id'] ?? 0);
}

function duties_centre_id(): int
{
    return (int)($_SESSION['centre_id'] ?? $_POST['centre_id'] ?? $GLOBALS['centre_id'] ?? 0);
}

function duties_redirect(array $params = []): void
{
    $url = '../../../module.php?module=duties&view=index';
    $query = http_build_query($params);
    header('Location: ' . $url . ($query ? '&' . $query : ''));
    exit;
}

function duties_view_url(array $params = []): string
{
    $url = 'module.php?module=duties&view=index';
    $query = http_build_query($params);
    return $url . ($query ? '&' . $query : '');
}

function duties_null($value): ?string
{
    $value = trim((string)$value);
    return $value !== '' ? $value : null;
}

function duties_assert_today_or_future(?string $date, string $error = 'ADD_ACTION_FAILED'): void
{
    if (!$date) {
        throw new InvalidArgumentException($error);
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new InvalidArgumentException($error);
    }

    $dt = DateTime::createFromFormat('!Y-m-d', $date);
    if (!$dt || $dt->format('Y-m-d') !== $date) {
        throw new InvalidArgumentException($error);
    }

    $today = new DateTime('today');
    if ($dt < $today) {
        throw new InvalidArgumentException($error);
    }
}

function duties_int_or_null($value): ?int
{
    $value = (int)$value;
    return $value > 0 ? $value : null;
}

function duties_table_columns(PDO $pdo, string $table): array
{
    $columns = [];
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            if (!empty($row['Field'])) {
                $columns[(string)$row['Field']] = true;
            }
        }
    } catch (Throwable $e) {
        return [];
    }
    return $columns;
}

function duties_ensure_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rescue_duty_recurrence_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            centre_id INT NOT NULL,
            staff_profile_id INT NOT NULL,
            frequency VARCHAR(20) NOT NULL DEFAULT 'weekly',
            day_of_week TINYINT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            role_type VARCHAR(40) NULL,
            area_id INT NULL,
            starts_on DATE NOT NULL,
            ends_on DATE NULL,
            notes TEXT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            INDEX idx_duty_rules_centre (centre_id),
            INDEX idx_duty_rules_staff (staff_profile_id),
            INDEX idx_duty_rules_active (active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rescue_duty_shifts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            centre_id INT NOT NULL,
            staff_profile_id INT NOT NULL,
            shift_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            role_type VARCHAR(40) NULL,
            area_id INT NULL,
            notes TEXT NULL,
            recurrence_rule_id INT NULL,
            deleted TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            INDEX idx_duty_shifts_centre_date (centre_id, shift_date),
            INDEX idx_duty_shifts_staff (staff_profile_id),
            INDEX idx_duty_shifts_area (area_id),
            INDEX idx_duty_shifts_rule (recurrence_rule_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rescue_duty_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            centre_id INT NOT NULL,
            staff_profile_id INT NULL,
            area_id INT NULL,
            task_date DATE NOT NULL,
            task_title VARCHAR(190) NOT NULL,
            task_notes TEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'open',
            priority VARCHAR(30) NOT NULL DEFAULT 'normal',
            due_time TIME NULL,
            completed_at DATETIME NULL,
            completed_by_account_id INT NULL,
            recurrence_rule_id INT NULL,
            deleted TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            INDEX idx_duty_tasks_centre_date (centre_id, task_date),
            INDEX idx_duty_tasks_staff (staff_profile_id),
            INDEX idx_duty_tasks_area (area_id),
            INDEX idx_duty_tasks_rule (recurrence_rule_id),
            INDEX idx_duty_tasks_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $taskColumns = duties_table_columns($pdo, 'rescue_duty_tasks');
    if ($taskColumns && empty($taskColumns['recurrence_rule_id'])) {
        $pdo->exec("ALTER TABLE rescue_duty_tasks ADD COLUMN recurrence_rule_id INT NULL AFTER completed_by_account_id");
        $pdo->exec("ALTER TABLE rescue_duty_tasks ADD INDEX idx_duty_tasks_rule (recurrence_rule_id)");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rescue_duty_task_recurrence_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            centre_id INT NOT NULL,
            staff_profile_id INT NULL,
            area_id INT NULL,
            task_title VARCHAR(190) NOT NULL,
            task_notes TEXT NULL,
            priority VARCHAR(30) NOT NULL DEFAULT 'normal',
            due_time TIME NULL,
            frequency VARCHAR(20) NOT NULL DEFAULT 'weekly',
            day_of_week TINYINT NULL,
            starts_on DATE NOT NULL,
            ends_on DATE NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            INDEX idx_duty_task_rules_centre (centre_id),
            INDEX idx_duty_task_rules_staff (staff_profile_id),
            INDEX idx_duty_task_rules_active (active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function duties_week_start(string $date = ''): DateTime
{
    try {
        $dt = new DateTime($date !== '' ? $date : 'today');
    } catch (Throwable $e) {
        $dt = new DateTime('today');
    }
    $dt->setTime(0, 0, 0);
    $day = (int)$dt->format('N');
    if ($day > 1) {
        $dt->modify('-' . ($day - 1) . ' days');
    }
    return $dt;
}

function duties_week_dates(DateTime $start): array
{
    $dates = [];
    for ($i = 0; $i < 7; $i++) {
        $day = clone $start;
        $day->modify('+' . $i . ' days');
        $dates[] = $day;
    }
    return $dates;
}

function duties_fetch_staff(PDO $pdo, int $centre_id): array
{
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, known_as, role_type
        FROM rescue_staff_profiles
        WHERE centre_id = :centre_id AND deleted = 0 AND status = 'active'
        ORDER BY last_name, first_name
    ");
    $stmt->execute([':centre_id' => $centre_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function duties_fetch_areas(PDO $pdo, int $centre_id): array
{
    $stmt = $pdo->prepare("
        SELECT area_id, area_name
        FROM rescue_areas
        WHERE centre_id = :centre_id
        ORDER BY area_name
    ");
    $stmt->execute([':centre_id' => $centre_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function duties_person_name(?array $person): string
{
    if (!$person) {
        return '';
    }
    $known = trim((string)($person['known_as'] ?? ''));
    if ($known !== '') {
        return $known;
    }
    return trim((string)($person['first_name'] ?? '') . ' ' . (string)($person['last_name'] ?? ''));
}

function duties_role_options(): array
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

function duties_time_to_minutes(?string $time): int
{
    $time = substr((string)$time, 0, 5);
    if ($time === '00:00') {
        return 1440;
    }
    [$hour, $minute] = array_pad(explode(':', $time), 2, '00');
    return max(0, min(1440, ((int)$hour * 60) + (int)$minute));
}

function duties_times_overlap(string $startA, string $endA, string $startB, string $endB): bool
{
    $aStart = duties_time_to_minutes($startA);
    $aEnd = duties_time_to_minutes($endA);
    $bStart = duties_time_to_minutes($startB);
    $bEnd = duties_time_to_minutes($endB);

    if ($aEnd <= $aStart) {
        $aEnd = 1440;
    }
    if ($bEnd <= $bStart) {
        $bEnd = 1440;
    }

    return $aStart < $bEnd && $bStart < $aEnd;
}

function duties_assert_shift_available(PDO $pdo, int $centre_id, int $staff_id, string $date, string $start, string $end, ?string $ends_on = null, int $exclude_shift_id = 0): void
{
    $rangeStart = new DateTime($date);
    $rangeEnd = $ends_on ? new DateTime($ends_on) : clone $rangeStart;
    if ($rangeEnd < $rangeStart) {
        throw new InvalidArgumentException('ADD_ACTION_FAILED');
    }

    foreach (duties_fetch_shifts_for_staff($pdo, $centre_id, $staff_id, $rangeStart, $rangeEnd) as $shift) {
        if ($exclude_shift_id > 0 && (int)($shift['id'] ?? 0) === $exclude_shift_id) {
            continue;
        }
        if (duties_times_overlap($start, $end, (string)$shift['start_time'], (string)$shift['end_time'])) {
            throw new InvalidArgumentException('ADD_SHIFT_CONFLICT');
        }
    }
}

function duties_fetch_shift(PDO $pdo, int $centre_id, int $shift_id): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM rescue_duty_shifts
        WHERE id = :id AND centre_id = :centre_id AND deleted = 0
        LIMIT 1
    ");
    $stmt->execute([':id' => $shift_id, ':centre_id' => $centre_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function duties_shift_save(PDO $pdo, int $centre_id, array $data): int
{
    $id = (int)($data['shift_id'] ?? 0);
    $staff_id = (int)($data['staff_profile_id'] ?? 0);
    $date = duties_null($data['shift_date'] ?? '');
    $start = duties_null($data['start_time'] ?? '');
    $end = duties_null($data['end_time'] ?? '');
    if ($staff_id <= 0 || !$date || !$start || !$end) {
        throw new InvalidArgumentException('ADD_ACTION_FAILED');
    }

    $endsOn = duties_null($data['ends_on'] ?? '');
    duties_assert_today_or_future($date);
    if ($endsOn) {
        duties_assert_today_or_future($endsOn);
        if ($endsOn < $date) {
            throw new InvalidArgumentException('ADD_ACTION_FAILED');
        }
    }

    duties_assert_shift_available(
        $pdo,
        $centre_id,
        $staff_id,
        $date,
        $start,
        $end,
        !empty($data['repeat_weekly']) ? ($endsOn ?: (new DateTime($date))->modify('+1 year')->format('Y-m-d')) : null,
        $id
    );

    if ($id > 0) {
        if (!duties_fetch_shift($pdo, $centre_id, $id)) {
            throw new InvalidArgumentException('ADD_ACTION_FAILED');
        }

        $stmt = $pdo->prepare("
            UPDATE rescue_duty_shifts
            SET staff_profile_id = :staff_profile_id,
                shift_date = :shift_date,
                start_time = :start_time,
                end_time = :end_time,
                role_type = :role_type,
                area_id = :area_id,
                notes = :notes,
                updated_at = NOW()
            WHERE id = :id AND centre_id = :centre_id
        ");
        $stmt->execute([
            ':id' => $id,
            ':centre_id' => $centre_id,
            ':staff_profile_id' => $staff_id,
            ':shift_date' => $date,
            ':start_time' => $start,
            ':end_time' => $end,
            ':role_type' => duties_null($data['role_type'] ?? ''),
            ':area_id' => duties_int_or_null($data['area_id'] ?? 0),
            ':notes' => duties_null($data['notes'] ?? ''),
        ]);
        return $id;
    }

    $rule_id = null;
    if (!empty($data['repeat_weekly'])) {
        $day = (int)(new DateTime($date))->format('N');
        $stmt = $pdo->prepare("
            INSERT INTO rescue_duty_recurrence_rules
                (centre_id, staff_profile_id, frequency, day_of_week, start_time, end_time, role_type, area_id, starts_on, ends_on, notes)
            VALUES
                (:centre_id, :staff_profile_id, 'weekly', :day_of_week, :start_time, :end_time, :role_type, :area_id, :starts_on, :ends_on, :notes)
        ");
        $stmt->execute([
            ':centre_id' => $centre_id,
            ':staff_profile_id' => $staff_id,
            ':day_of_week' => $day,
            ':start_time' => $start,
            ':end_time' => $end,
            ':role_type' => duties_null($data['role_type'] ?? ''),
            ':area_id' => duties_int_or_null($data['area_id'] ?? 0),
            ':starts_on' => $date,
            ':ends_on' => $endsOn,
            ':notes' => duties_null($data['notes'] ?? ''),
        ]);
        $rule_id = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("
        INSERT INTO rescue_duty_shifts
            (centre_id, staff_profile_id, shift_date, start_time, end_time, role_type, area_id, notes, recurrence_rule_id)
        VALUES
            (:centre_id, :staff_profile_id, :shift_date, :start_time, :end_time, :role_type, :area_id, :notes, :recurrence_rule_id)
    ");
    $stmt->execute([
        ':centre_id' => $centre_id,
        ':staff_profile_id' => $staff_id,
        ':shift_date' => $date,
        ':start_time' => $start,
        ':end_time' => $end,
        ':role_type' => duties_null($data['role_type'] ?? ''),
        ':area_id' => duties_int_or_null($data['area_id'] ?? 0),
        ':notes' => duties_null($data['notes'] ?? ''),
        ':recurrence_rule_id' => $rule_id,
    ]);

    return (int)$pdo->lastInsertId();
}

function duties_delete_shift(PDO $pdo, int $centre_id, int $shift_id): void
{
    $stmt = $pdo->prepare("
        UPDATE rescue_duty_shifts
        SET deleted = 1, updated_at = NOW()
        WHERE id = :id AND centre_id = :centre_id
    ");
    $stmt->execute([':id' => $shift_id, ':centre_id' => $centre_id]);
}

function duties_fetch_recurring_rules(PDO $pdo, int $centre_id): array
{
    $stmt = $pdo->prepare("
        SELECT r.*, p.first_name, p.last_name, p.known_as, a.area_name
        FROM rescue_duty_recurrence_rules r
        INNER JOIN rescue_staff_profiles p ON p.id = r.staff_profile_id AND p.centre_id = r.centre_id
        LEFT JOIN rescue_areas a ON a.area_id = r.area_id AND a.centre_id = r.centre_id
        WHERE r.centre_id = :centre_id
          AND r.active = 1
        ORDER BY r.day_of_week, r.start_time, p.last_name, p.first_name
    ");
    $stmt->execute([':centre_id' => $centre_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function duties_fetch_recurring_task_rules(PDO $pdo, int $centre_id): array
{
    $stmt = $pdo->prepare("
        SELECT r.*, p.first_name, p.last_name, p.known_as, a.area_name
        FROM rescue_duty_task_recurrence_rules r
        LEFT JOIN rescue_staff_profiles p ON p.id = r.staff_profile_id AND p.centre_id = r.centre_id
        LEFT JOIN rescue_areas a ON a.area_id = r.area_id AND a.centre_id = r.centre_id
        WHERE r.centre_id = :centre_id
          AND r.active = 1
        ORDER BY r.day_of_week, r.due_time IS NULL, r.due_time, r.task_title
    ");
    $stmt->execute([':centre_id' => $centre_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function duties_end_recurring_rule(PDO $pdo, int $centre_id, int $rule_id, string $ends_on): void
{
    $ends_on = duties_null($ends_on) ?: date('Y-m-d');
    duties_assert_today_or_future($ends_on);
    $stmt = $pdo->prepare("
        UPDATE rescue_duty_recurrence_rules
        SET ends_on = :ends_on,
            active = CASE WHEN :ends_on < CURDATE() THEN 0 ELSE active END,
            updated_at = NOW()
        WHERE id = :id AND centre_id = :centre_id
    ");
    $stmt->execute([
        ':id' => $rule_id,
        ':centre_id' => $centre_id,
        ':ends_on' => $ends_on,
    ]);
}

function duties_end_recurring_task_rule(PDO $pdo, int $centre_id, int $rule_id, string $ends_on): void
{
    $ends_on = duties_null($ends_on) ?: date('Y-m-d');
    duties_assert_today_or_future($ends_on);
    $stmt = $pdo->prepare("
        UPDATE rescue_duty_task_recurrence_rules
        SET ends_on = :ends_on,
            active = CASE WHEN :ends_on < CURDATE() THEN 0 ELSE active END,
            updated_at = NOW()
        WHERE id = :id AND centre_id = :centre_id
    ");
    $stmt->execute([
        ':id' => $rule_id,
        ':centre_id' => $centre_id,
        ':ends_on' => $ends_on,
    ]);
}

function duties_task_save(PDO $pdo, int $centre_id, array $data): int
{
    $title = duties_null($data['task_title'] ?? '');
    $date = duties_null($data['task_date'] ?? '');
    if (!$title || !$date) {
        throw new InvalidArgumentException('ADD_ACTION_FAILED');
    }

    $endsOn = duties_null($data['ends_on'] ?? '');
    duties_assert_today_or_future($date);
    if ($endsOn) {
        duties_assert_today_or_future($endsOn);
        if ($endsOn < $date) {
            throw new InvalidArgumentException('ADD_ACTION_FAILED');
        }
    }

    $priority = strtolower(trim((string)($data['priority'] ?? 'normal')));
    if (!in_array($priority, ['low', 'normal', 'high'], true)) {
        $priority = 'normal';
    }

    $task_rule_id = null;
    if (!empty($data['repeat_weekly'])) {
        $day = (int)(new DateTime($date))->format('N');
        $stmt = $pdo->prepare("
            INSERT INTO rescue_duty_task_recurrence_rules
                (centre_id, staff_profile_id, area_id, task_title, task_notes, priority, due_time, frequency, day_of_week, starts_on, ends_on)
            VALUES
                (:centre_id, :staff_profile_id, :area_id, :task_title, :task_notes, :priority, :due_time, 'weekly', :day_of_week, :starts_on, :ends_on)
        ");
        $stmt->execute([
            ':centre_id' => $centre_id,
            ':staff_profile_id' => duties_int_or_null($data['staff_profile_id'] ?? 0),
            ':area_id' => duties_int_or_null($data['area_id'] ?? 0),
            ':task_title' => $title,
            ':task_notes' => duties_null($data['task_notes'] ?? ''),
            ':priority' => $priority,
            ':due_time' => duties_null($data['due_time'] ?? ''),
            ':day_of_week' => $day,
            ':starts_on' => $date,
            ':ends_on' => $endsOn,
        ]);
        $task_rule_id = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("
        INSERT INTO rescue_duty_tasks
            (centre_id, staff_profile_id, area_id, task_date, task_title, task_notes, status, priority, due_time, recurrence_rule_id)
        VALUES
            (:centre_id, :staff_profile_id, :area_id, :task_date, :task_title, :task_notes, 'open', :priority, :due_time, :recurrence_rule_id)
    ");
    $stmt->execute([
        ':centre_id' => $centre_id,
        ':staff_profile_id' => duties_int_or_null($data['staff_profile_id'] ?? 0),
        ':area_id' => duties_int_or_null($data['area_id'] ?? 0),
        ':task_date' => $date,
        ':task_title' => $title,
        ':task_notes' => duties_null($data['task_notes'] ?? ''),
        ':priority' => $priority,
        ':due_time' => duties_null($data['due_time'] ?? ''),
        ':recurrence_rule_id' => $task_rule_id,
    ]);

    return (int)$pdo->lastInsertId();
}

function duties_task_complete(PDO $pdo, int $centre_id, int $task_id, int $user_id): void
{
    $stmt = $pdo->prepare("
        UPDATE rescue_duty_tasks
        SET status = 'done', completed_at = NOW(), completed_by_account_id = :user_id, updated_at = NOW()
        WHERE id = :id AND centre_id = :centre_id
    ");
    $stmt->execute([':id' => $task_id, ':centre_id' => $centre_id, ':user_id' => $user_id]);
}

function duties_task_materialise(PDO $pdo, int $centre_id, string $task_ref): int
{
    if (ctype_digit($task_ref)) {
        return (int)$task_ref;
    }
    if (!preg_match('/^tr(\d+)-(\d{4}-\d{2}-\d{2})$/', $task_ref, $matches)) {
        throw new InvalidArgumentException('ADD_ACTION_FAILED');
    }

    $rule_id = (int)$matches[1];
    $date = $matches[2];
    $stmt = $pdo->prepare("
        SELECT *
        FROM rescue_duty_tasks
        WHERE centre_id = :centre_id
          AND recurrence_rule_id = :rule_id
          AND task_date = :task_date
          AND deleted = 0
        LIMIT 1
    ");
    $stmt->execute([':centre_id' => $centre_id, ':rule_id' => $rule_id, ':task_date' => $date]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        return (int)$existing['id'];
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM rescue_duty_task_recurrence_rules
        WHERE id = :id AND centre_id = :centre_id AND active = 1
        LIMIT 1
    ");
    $stmt->execute([':id' => $rule_id, ':centre_id' => $centre_id]);
    $rule = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$rule) {
        throw new InvalidArgumentException('ADD_ACTION_FAILED');
    }

    $stmt = $pdo->prepare("
        INSERT INTO rescue_duty_tasks
            (centre_id, staff_profile_id, area_id, task_date, task_title, task_notes, status, priority, due_time, recurrence_rule_id)
        VALUES
            (:centre_id, :staff_profile_id, :area_id, :task_date, :task_title, :task_notes, 'open', :priority, :due_time, :recurrence_rule_id)
    ");
    $stmt->execute([
        ':centre_id' => $centre_id,
        ':staff_profile_id' => duties_int_or_null($rule['staff_profile_id'] ?? 0),
        ':area_id' => duties_int_or_null($rule['area_id'] ?? 0),
        ':task_date' => $date,
        ':task_title' => $rule['task_title'],
        ':task_notes' => duties_null($rule['task_notes'] ?? ''),
        ':priority' => $rule['priority'] ?: 'normal',
        ':due_time' => duties_null($rule['due_time'] ?? ''),
        ':recurrence_rule_id' => $rule_id,
    ]);

    return (int)$pdo->lastInsertId();
}

function duties_task_delete(PDO $pdo, int $centre_id, string $task_ref): int
{
    $task_id = duties_task_materialise($pdo, $centre_id, $task_ref);
    $stmt = $pdo->prepare("
        UPDATE rescue_duty_tasks
        SET deleted = 1, updated_at = NOW()
        WHERE id = :id AND centre_id = :centre_id
    ");
    $stmt->execute([':id' => $task_id, ':centre_id' => $centre_id]);
    return $task_id;
}

function duties_task_assign(PDO $pdo, int $centre_id, string $task_ref, ?int $staff_profile_id): int
{
    $task_id = duties_task_materialise($pdo, $centre_id, $task_ref);
    $stmt = $pdo->prepare("
        UPDATE rescue_duty_tasks
        SET staff_profile_id = :staff_profile_id, updated_at = NOW()
        WHERE id = :id AND centre_id = :centre_id
    ");
    $stmt->execute([
        ':id' => $task_id,
        ':centre_id' => $centre_id,
        ':staff_profile_id' => $staff_profile_id,
    ]);
    return $task_id;
}

function duties_fetch_shifts(PDO $pdo, int $centre_id, DateTime $start, DateTime $end): array
{
    $params = [
        ':centre_id' => $centre_id,
        ':start' => $start->format('Y-m-d'),
        ':end' => $end->format('Y-m-d'),
    ];

    $stmt = $pdo->prepare("
        SELECT s.*, p.first_name, p.last_name, p.known_as, a.area_name, 0 AS virtual_shift
        FROM rescue_duty_shifts s
        INNER JOIN rescue_staff_profiles p ON p.id = s.staff_profile_id AND p.centre_id = s.centre_id
        LEFT JOIN rescue_areas a ON a.area_id = s.area_id AND a.centre_id = s.centre_id
        WHERE s.centre_id = :centre_id
          AND s.deleted = 0
          AND s.shift_date BETWEEN :start AND :end
        ORDER BY s.shift_date, s.start_time
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $directRecurringKeys = [];
    foreach ($rows as $row) {
        if (!empty($row['recurrence_rule_id'])) {
            $directRecurringKeys[(int)$row['recurrence_rule_id'] . '|' . (string)$row['shift_date']] = true;
        }
    }

    $stmt = $pdo->prepare("
        SELECT r.*, p.first_name, p.last_name, p.known_as, a.area_name
        FROM rescue_duty_recurrence_rules r
        INNER JOIN rescue_staff_profiles p ON p.id = r.staff_profile_id AND p.centre_id = r.centre_id
        LEFT JOIN rescue_areas a ON a.area_id = r.area_id AND a.centre_id = r.centre_id
        WHERE r.centre_id = :centre_id
          AND r.active = 1
          AND r.frequency = 'weekly'
          AND r.starts_on <= :end
          AND (r.ends_on IS NULL OR r.ends_on >= :start)
    ");
    $stmt->execute($params);
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $periodEnd = clone $end;
    $periodEnd->modify('+1 day');
    $periodDates = iterator_to_array(new DatePeriod($start, new DateInterval('P1D'), $periodEnd));

    foreach ($rules as $rule) {
        foreach ($periodDates as $day) {
            if ((int)$day->format('N') !== (int)$rule['day_of_week']) {
                continue;
            }
            $date = $day->format('Y-m-d');
            if ($date < (string)$rule['starts_on'] || (!empty($rule['ends_on']) && $date > (string)$rule['ends_on'])) {
                continue;
            }
            if (!empty($directRecurringKeys[(int)$rule['id'] . '|' . $date])) {
                continue;
            }
            $virtual = $rule;
            $virtual['id'] = 'r' . (int)$rule['id'] . '-' . $date;
            $virtual['shift_date'] = $date;
            $virtual['recurrence_rule_id'] = (int)$rule['id'];
            $virtual['virtual_shift'] = 1;
            $rows[] = $virtual;
        }
    }

    usort($rows, static function ($a, $b) {
        return [(string)$a['shift_date'], (string)$a['start_time'], (string)$a['last_name']] <=> [(string)$b['shift_date'], (string)$b['start_time'], (string)$b['last_name']];
    });

    return $rows;
}

function duties_fetch_tasks(PDO $pdo, int $centre_id, DateTime $start, DateTime $end): array
{
    $stmt = $pdo->prepare("
        SELECT t.*, p.first_name, p.last_name, p.known_as, a.area_name
        FROM rescue_duty_tasks t
        LEFT JOIN rescue_staff_profiles p ON p.id = t.staff_profile_id AND p.centre_id = t.centre_id
        LEFT JOIN rescue_areas a ON a.area_id = t.area_id AND a.centre_id = t.centre_id
        WHERE t.centre_id = :centre_id
          AND t.deleted = 0
          AND t.task_date BETWEEN :start AND :end
        ORDER BY t.task_date, t.status, t.due_time IS NULL, t.due_time, t.priority DESC
    ");
    $stmt->execute([
        ':centre_id' => $centre_id,
        ':start' => $start->format('Y-m-d'),
        ':end' => $end->format('Y-m-d'),
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $directRecurringKeys = [];
    foreach ($rows as $row) {
        if (!empty($row['recurrence_rule_id'])) {
            $directRecurringKeys[(int)$row['recurrence_rule_id'] . '|' . (string)$row['task_date']] = true;
        }
    }

    $stmt = $pdo->prepare("
        SELECT recurrence_rule_id, task_date
        FROM rescue_duty_tasks
        WHERE centre_id = :centre_id
          AND recurrence_rule_id IS NOT NULL
          AND task_date BETWEEN :start AND :end
    ");
    $stmt->execute([
        ':centre_id' => $centre_id,
        ':start' => $start->format('Y-m-d'),
        ':end' => $end->format('Y-m-d'),
    ]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $directRecurringKeys[(int)$row['recurrence_rule_id'] . '|' . (string)$row['task_date']] = true;
    }

    $stmt = $pdo->prepare("
        SELECT r.*, p.first_name, p.last_name, p.known_as, a.area_name
        FROM rescue_duty_task_recurrence_rules r
        LEFT JOIN rescue_staff_profiles p ON p.id = r.staff_profile_id AND p.centre_id = r.centre_id
        LEFT JOIN rescue_areas a ON a.area_id = r.area_id AND a.centre_id = r.centre_id
        WHERE r.centre_id = :centre_id
          AND r.active = 1
          AND r.frequency = 'weekly'
          AND r.starts_on <= :end
          AND (r.ends_on IS NULL OR r.ends_on >= :start)
    ");
    $stmt->execute([
        ':centre_id' => $centre_id,
        ':start' => $start->format('Y-m-d'),
        ':end' => $end->format('Y-m-d'),
    ]);
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $periodEnd = clone $end;
    $periodEnd->modify('+1 day');
    $periodDates = iterator_to_array(new DatePeriod($start, new DateInterval('P1D'), $periodEnd));
    foreach ($rules as $rule) {
        foreach ($periodDates as $day) {
            if ((int)$day->format('N') !== (int)$rule['day_of_week']) {
                continue;
            }
            $date = $day->format('Y-m-d');
            if ($date < (string)$rule['starts_on'] || (!empty($rule['ends_on']) && $date > (string)$rule['ends_on'])) {
                continue;
            }
            if (!empty($directRecurringKeys[(int)$rule['id'] . '|' . $date])) {
                continue;
            }
            $virtual = $rule;
            $virtual['id'] = 'tr' . (int)$rule['id'] . '-' . $date;
            $virtual['task_date'] = $date;
            $virtual['status'] = 'open';
            $virtual['completed_at'] = null;
            $virtual['completed_by_account_id'] = null;
            $virtual['recurrence_rule_id'] = (int)$rule['id'];
            $virtual['virtual_task'] = 1;
            $rows[] = $virtual;
        }
    }

    usort($rows, static function ($a, $b): int {
        return [(string)$a['task_date'], (string)$a['status'], empty($a['due_time']) ? 1 : 0, (string)$a['due_time']] <=> [(string)$b['task_date'], (string)$b['status'], empty($b['due_time']) ? 1 : 0, (string)$b['due_time']];
    });

    return $rows;
}

function duties_fetch_staff_profile_for_account(PDO $pdo, int $centre_id, int $account_id): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, centre_id, account_id, first_name, last_name, known_as
        FROM rescue_staff_profiles
        WHERE centre_id = :centre_id
          AND account_id = :account_id
          AND deleted = 0
        LIMIT 1
    ");
    $stmt->execute([
        ':centre_id' => $centre_id,
        ':account_id' => $account_id,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function duties_fetch_shifts_for_staff(PDO $pdo, int $centre_id, int $staff_profile_id, DateTime $start, DateTime $end): array
{
    $allShifts = duties_fetch_shifts($pdo, $centre_id, $start, $end);
    $staffShifts = array_values(array_filter($allShifts, static function ($shift) use ($staff_profile_id): bool {
        return (int)($shift['staff_profile_id'] ?? 0) === $staff_profile_id;
    }));

    usort($staffShifts, static function ($a, $b): int {
        return [(string)$a['shift_date'], (string)$a['start_time']] <=> [(string)$b['shift_date'], (string)$b['start_time']];
    });

    return $staffShifts;
}

function duties_fetch_tasks_for_staff(PDO $pdo, int $centre_id, int $staff_profile_id, DateTime $start, DateTime $end): array
{
    $allTasks = duties_fetch_tasks($pdo, $centre_id, $start, $end);
    return array_values(array_filter($allTasks, static function ($task) use ($staff_profile_id): bool {
        return (int)($task['staff_profile_id'] ?? 0) === $staff_profile_id;
    }));
}

function duties_home_data_for_account(PDO $pdo, int $centre_id, int $account_id, int $days = 42): array
{
    $profile = duties_fetch_staff_profile_for_account($pdo, $centre_id, $account_id);
    if (!$profile) {
        return [
            'profile' => null,
            'today' => new DateTime('today'),
            'today_shifts' => [],
            'today_tasks' => [],
            'upcoming_shifts' => [],
        ];
    }

    $today = new DateTime('today');
    $todayValue = $today->format('Y-m-d');
    $end = clone $today;
    $end->modify('+' . max(1, $days) . ' days');
    $staffId = (int)$profile['id'];
    $shifts = duties_fetch_shifts_for_staff($pdo, $centre_id, $staffId, $today, $end);

    return [
        'profile' => $profile,
        'today' => $today,
        'today_shifts' => array_values(array_filter($shifts, static function ($shift) use ($todayValue): bool {
            return substr((string)($shift['shift_date'] ?? ''), 0, 10) === $todayValue;
        })),
        'today_tasks' => duties_fetch_tasks_for_staff($pdo, $centre_id, $staffId, $today, $today),
        'upcoming_shifts' => array_slice($shifts, 0, 3),
    ];
}
