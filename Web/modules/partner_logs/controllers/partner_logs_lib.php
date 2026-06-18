<?php
// modules/partner_logs/controllers/partner_logs_lib.php

function partner_logs_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function partner_logs_user_id(): int
{
    return (int)($_SESSION['account_id'] ?? $_SESSION['user_id'] ?? $GLOBALS['user_id'] ?? 0);
}

function partner_logs_centre_id(): int
{
    return (int)($_SESSION['centre_id'] ?? $GLOBALS['centre_id'] ?? 0);
}

function partner_logs_can_add(): bool
{
    return partner_logs_can_access();
}

function partner_logs_register_permissions(): void
{
    if (function_exists('registerPermission')) {
        registerPermission('module.partnerlogs', 'Partner Logs', 'module');
    }
}

function partner_logs_can_access(): bool
{
    partner_logs_register_permissions();

    if (function_exists('can')) {
        return can('module.partnerlogs');
    }

    return true;
}

function partner_logs_redirect(array $params = []): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '../../../patients.php';
    $url = parse_url($ref);
    $path = (string)($url['path'] ?? '../../../patients.php');
    $base = basename($path) ?: 'patients.php';

    if (!empty($url['query'])) {
        parse_str($url['query'], $qs);
        foreach (['module', 'view', 'patient_id', 'area', 'location', 'zone', 'zone_id'] as $key) {
            if (isset($qs[$key]) && !isset($params[$key])) {
                $params[$key] = $qs[$key];
            }
        }
    }

    $query = http_build_query($params);
    header('Location: ../../../' . $base . ($query ? '?' . $query : ''));
    exit;
}

function partner_logs_fetch_partner_types(PDO $pdo): array
{
    $stmt = $pdo->prepare("
        SELECT p_type_id, partner_type
        FROM rescue_partner_types
        ORDER BY partner_type ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function partner_logs_fetch_partner_type_name(PDO $pdo, int $partner_type_id): string
{
    $stmt = $pdo->prepare("
        SELECT partner_type
        FROM rescue_partner_types
        WHERE p_type_id = :partner_type_id
        LIMIT 1
    ");
    $stmt->execute([':partner_type_id' => $partner_type_id]);
    return (string)($stmt->fetchColumn() ?: 'Partner');
}

function partner_logs_user_display_name(PDO $pdo, int $user_id): string
{
    $stmt = $pdo->prepare("
        SELECT first_name, last_name
        FROM accounts
        WHERE id = :user_id
        LIMIT 1
    ");
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        return 'Unknown user';
    }

    $name = trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? ''));
    return $name !== '' ? $name : 'Unknown user';
}

function partner_logs_add_care_note(PDO $pdo, int $patient_id, string $partner, string $log_number, int $user_id, string $date): void
{
    if ($patient_id <= 0 || $user_id <= 0) {
        return;
    }

    $noteDate = $date !== '' ? $date : date('Y-m-d H:i:s');
    $displayDate = date('d-m-Y H:i', strtotime($noteDate));
    $user = partner_logs_user_display_name($pdo, $user_id);
    $logNumberText = trim($log_number) !== '' ? trim($log_number) : 'No log number';
    $message = '[Partner Log Entry] - ' . $partner . ' - ' . $logNumberText . ' - by ' . $user . ' - ' . $displayDate;

    $stmt = $pdo->prepare("
        INSERT INTO rescue_notes_patients
            (patient_id, message, author, public, image_id, date)
        VALUES
            (:patient_id, :message, :author, :public, :image_id, :date)
    ");
    $stmt->execute([
        ':patient_id' => $patient_id,
        ':message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
        ':author' => htmlspecialchars($user, ENT_QUOTES, 'UTF-8'),
        ':public' => 0,
        ':image_id' => null,
        ':date' => $noteDate,
    ]);
}

function partner_logs_create(PDO $pdo, array $data): int
{
    $patient_id = (int)($data['patient_id'] ?? 0);
    $centre_id = (int)($data['centre_id'] ?? 0);
    $admission_id = (int)($data['admission_id'] ?? 0);
    $user_id = (int)($data['user_id'] ?? 0);
    $partner_type = (int)($data['partner_type'] ?? 0);
    $date = trim((string)($data['date'] ?? ''));

    if ($patient_id <= 0 || $centre_id <= 0 || $admission_id <= 0 || $user_id <= 0 || $partner_type <= 0 || $date === '') {
        throw new InvalidArgumentException('Missing required partner log information.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO rescue_partner_log
            (patient_id, centre_id, admission_id, user_id, log_notes, is_crime, log_number, partner_type, date)
        VALUES
            (:patient_id, :centre_id, :admission_id, :user_id, :log_notes, :is_crime, :log_number, :partner_type, :date)
    ");
    $stmt->execute([
        ':patient_id' => $patient_id,
        ':centre_id' => $centre_id,
        ':admission_id' => $admission_id,
        ':user_id' => $user_id,
        ':log_notes' => trim((string)($data['log_notes'] ?? '')),
        ':is_crime' => (string)($data['is_crime'] ?? 'No') === 'Yes' ? 'Yes' : 'No',
        ':log_number' => trim((string)($data['log_number'] ?? '')),
        ':partner_type' => $partner_type,
        ':date' => $date,
    ]);

    return (int)$pdo->lastInsertId();
}

function partner_logs_render_patient_button(PDO $pdo, array $patient, array $context = []): string
{
    if (!partner_logs_can_add()) {
        return '';
    }

    $patient_id = (int)($patient['patient_id'] ?? 0);
    if ($patient_id <= 0) {
        return '';
    }

    $lang = is_array($context['lang'] ?? null) ? $context['lang'] : [];
    $title = $lang['TIP_ADD_A_PARTNER_LOG'] ?? 'Add a Partner Log';

    return '
        <button title="' . partner_logs_h($title) . '" class="btn orange open-section" data-section="partner_logs" data-pid="' . $patient_id . '">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M300.9 149.2L184.3 278.8C179.7 283.9 179.9 291.8 184.8 296.7C215.3 327.2 264.8 327.2 295.3 296.7L327.1 264.9C331.3 260.7 336.6 258.4 342 258C348.8 257.4 355.8 259.7 361 264.9L537.6 440L608 384L608 96L496 160L472.2 144.1C456.4 133.6 437.9 128 418.9 128L348.5 128C347.4 128 346.2 128 345.1 128.1C328.2 129 312.3 136.6 300.9 149.2zM148.6 246.7L255.4 128L215.8 128C190.3 128 165.9 138.1 147.9 156.1L144 160L32 96L32 384L188.4 514.3C211.4 533.5 240.4 544 270.3 544L286 544L279 537C269.6 527.6 269.6 512.4 279 503.1C288.4 493.8 303.6 493.7 312.9 503.1L353.9 544.1L362.9 544.1C382 544.1 400.7 539.8 417.7 531.8L391 505C381.6 495.6 381.6 480.4 391 471.1C400.4 461.8 415.6 461.7 424.9 471.1L456.9 503.1L474.4 485.6C483.3 476.7 485.9 463.8 482 452.5L344.1 315.7L329.2 330.6C279.9 379.9 200.1 379.9 150.8 330.6C127.8 307.6 126.9 270.7 148.6 246.6z"/></svg>
        </button>';
}

function partner_logs_render_patient_form(PDO $pdo, array $patient, array $context = []): string
{
    if (!partner_logs_can_add()) {
        return '';
    }

    $patient_id = (int)($patient['patient_id'] ?? 0);
    $admission_id = (int)($patient['admission_id'] ?? 0);
    $centre_id = (int)($context['centre_id'] ?? partner_logs_centre_id());
    $user_id = partner_logs_user_id();
    if ($patient_id <= 0 || $admission_id <= 0 || $centre_id <= 0 || $user_id <= 0) {
        return '';
    }

    $partner_types = partner_logs_fetch_partner_types($pdo);
    $display = !empty($context['visible']) ? 'block' : 'none';

    ob_start();
    ?>
    <div id="form-partner_logs-<?= (int)$patient_id ?>" class="form-container" style="display:<?= partner_logs_h($display) ?>;">
        <div class="rc-card rc-card-muted">
            <form action="modules/partner_logs/controllers/partner_logs_handler.php" method="post" class="xform">
                <input type="hidden" name="action" value="create">
                <div class="xform-grid">
                    <div class="xform-field">
                        <label class="xform-label" for="partner_logs_date_<?= (int)$patient_id ?>">Date &amp; Time</label>
                        <input type="datetime-local" name="date" id="partner_logs_date_<?= (int)$patient_id ?>" class="xform-input" required>
                    </div>

                    <div class="xform-field">
                        <label class="xform-label" for="partner_logs_type_<?= (int)$patient_id ?>">Partner / Type</label>
                        <select name="partner_type" id="partner_logs_type_<?= (int)$patient_id ?>" class="xform-input" required>
                            <option value="" disabled selected>Select Partner Type</option>
                            <?php foreach ($partner_types as $pt): ?>
                                <option value="<?= (int)$pt['p_type_id'] ?>">
                                    <?= partner_logs_h($pt['partner_type']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="xform-field">
                        <label class="xform-label" for="partner_logs_number_<?= (int)$patient_id ?>">Log Number</label>
                        <input type="text" name="log_number" id="partner_logs_number_<?= (int)$patient_id ?>" class="xform-input">
                    </div>

                    <div class="xform-field">
                        <label class="xform-label" for="partner_logs_crime_<?= (int)$patient_id ?>">Is this a crime?</label>
                        <select name="is_crime" id="partner_logs_crime_<?= (int)$patient_id ?>" class="xform-input">
                            <option value="No" selected>No</option>
                            <option value="Yes">Yes</option>
                        </select>
                    </div>

                    <div class="xform-field" style="grid-column: span 4;">
                        <label class="xform-label" for="partner_logs_notes_<?= (int)$patient_id ?>">Notes</label>
                        <textarea name="log_notes" id="partner_logs_notes_<?= (int)$patient_id ?>" class="xform-input" rows="4"></textarea>
                    </div>
                </div>

                <input type="hidden" name="centre_id" value="<?= (int)$centre_id ?>">
                <input type="hidden" name="patient_id" value="<?= (int)$patient_id ?>">
                <input type="hidden" name="admission_id" value="<?= (int)$admission_id ?>">
                <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">

                <br>
                <button type="submit" name="partner_logs_form" class="btn orange">Add Partner Log</button>
            </form>
        </div>
    </div>
    <?php
    return (string)ob_get_clean();
}
