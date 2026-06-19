<?php
// modules/quick_tasks/controllers/quick_tasks_lib.php

function quick_tasks_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function quick_tasks_user_id(): int
{
    return (int)($_SESSION['account_id'] ?? $_SESSION['user_id'] ?? $GLOBALS['user_id'] ?? 0);
}

function quick_tasks_centre_id(): int
{
    return (int)($_SESSION['centre_id'] ?? $GLOBALS['centre_id'] ?? 0);
}

function quick_tasks_can_assign(): bool
{
    if (!function_exists('can_action')) {
        return true;
    }

    return can_action('patients.tasks.add', 'Add quick task');
}

function quick_tasks_redirect(array $params = []): void
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

function quick_tasks_fetch_available_tasks(PDO $pdo): array
{
    $stmt = $pdo->prepare("
        SELECT task_id, task
        FROM rescue_tasks
        ORDER BY task ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function quick_tasks_fetch_patient_icons(PDO $pdo, int $patient_id): array
{
    $stmt = $pdo->prepare("
        SELECT
            t.task,
            t.svg,
            tp.status,
            tp.task_pt_id,
            tp.completed_date_time,
            tp.completed_by,
            a.first_name,
            a.last_name
        FROM rescue_tasks_patients tp
        JOIN rescue_tasks t
            ON t.task_id = tp.task_id
        LEFT JOIN accounts a
            ON a.id = tp.completed_by
        WHERE tp.patient_id = :patient_id
        ORDER BY tp.set_date_time ASC, tp.task_pt_id ASC
    ");
    $stmt->execute([':patient_id' => $patient_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function quick_tasks_fetch_patient_task(PDO $pdo, int $task_pt_id): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            tp.task_pt_id,
            tp.task_id,
            tp.patient_id,
            tp.status,
            tp.set_date_time,
            tp.set_by,
            tp.completed_date_time,
            tp.completed_by,
            t.task
        FROM rescue_tasks_patients tp
        LEFT JOIN rescue_tasks t
            ON t.task_id = tp.task_id
        WHERE tp.task_pt_id = :task_pt_id
        LIMIT 1
    ");
    $stmt->execute([':task_pt_id' => $task_pt_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function quick_tasks_fetch_task_name(PDO $pdo, int $task_id): string
{
    $stmt = $pdo->prepare("
        SELECT task
        FROM rescue_tasks
        WHERE task_id = :task_id
        LIMIT 1
    ");
    $stmt->execute([':task_id' => $task_id]);
    return (string)($stmt->fetchColumn() ?: 'Unknown task');
}

function quick_tasks_user_display_name(PDO $pdo, int $user_id): string
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

function quick_tasks_add_care_note(PDO $pdo, int $patient_id, string $task, string $status, int $user_id): void
{
    if ($patient_id <= 0 || $task === '' || $user_id <= 0) {
        return;
    }

    $now = date('Y-m-d H:i:s');
    $displayDate = date('d-m-Y H:i', strtotime($now));
    $user = quick_tasks_user_display_name($pdo, $user_id);
    $message = '[Quick Task] Quick Task - ' . $task . ' - ' . $status . ' by ' . $user . ' - on ' . $displayDate;

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
        ':date' => $now,
    ]);
}

function quick_tasks_assign_task(PDO $pdo, int $task_id, int $patient_id, int $user_id): int
{
    if ($task_id <= 0 || $patient_id <= 0 || $user_id <= 0) {
        throw new InvalidArgumentException('Missing required task assignment information.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO rescue_tasks_patients
            (task_id, patient_id, status, set_date_time, set_by)
        VALUES
            (:task_id, :patient_id, 'Waiting', :dt, :uid)
    ");
    $stmt->execute([
        ':task_id' => $task_id,
        ':patient_id' => $patient_id,
        ':dt' => date('Y-m-d H:i:s'),
        ':uid' => $user_id,
    ]);

    return (int)$pdo->lastInsertId();
}

function quick_tasks_complete_task(PDO $pdo, int $task_pt_id, int $user_id): ?array
{
    if ($task_pt_id <= 0 || $user_id <= 0) {
        throw new InvalidArgumentException('Missing required task completion information.');
    }

    $old = quick_tasks_fetch_patient_task($pdo, $task_pt_id);
    if (!$old) {
        return null;
    }
    if ((string)($old['status'] ?? '') === 'Completed') {
        return null;
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_tasks_patients
        SET
            status = 'Completed',
            completed_by = :uid,
            completed_date_time = :dt
        WHERE task_pt_id = :id
          AND status <> 'Completed'
        LIMIT 1
    ");
    $stmt->execute([
        ':uid' => $user_id,
        ':dt' => date('Y-m-d H:i:s'),
        ':id' => $task_pt_id,
    ]);

    return $old;
}

function quick_tasks_colour_svg(string $svg, string $colour): string
{
    $svg_coloured = preg_replace('/fill="[^"]*"/i', 'fill="' . $colour . '"', $svg);
    if (!preg_match('/fill="/i', $svg_coloured)) {
        $svg_coloured = preg_replace('/<path/i', '<path fill="' . $colour . '"', $svg_coloured, 1);
    }

    return preg_replace(
        '/<svg([^>]*)>/i',
        '<svg$1 width="30" height="30" style="width:30px;height:30px;">',
        $svg_coloured,
        1
    );
}

function quick_tasks_render_patient_button(PDO $pdo, array $patient, array $context = []): string
{
    if (!quick_tasks_can_assign()) {
        return '';
    }

    $patient_id = (int)($patient['patient_id'] ?? 0);
    if ($patient_id <= 0) {
        return '';
    }

    $lang = is_array($context['lang'] ?? null) ? $context['lang'] : [];
    $title = $lang['TIP_ADD_A_QUICK_TASK'] ?? 'Add a Quick Task';

    return '
        <button title="' . quick_tasks_h($title) . '" class="btn purple open-section" data-section="quick_tasks" data-pid="' . $patient_id . '">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="icon"><path d="M197.8 100.3C208.7 107.9 211.3 122.9 203.7 133.7L147.7 213.7C143.6 219.5 137.2 223.2 130.1 223.8C123 224.4 116 222 111 217L71 177C61.7 167.6 61.7 152.4 71 143C80.3 133.6 95.6 133.7 105 143L124.8 162.8L164.4 106.2C172 95.3 187 92.7 197.8 100.3zM197.8 260.3C208.7 267.9 211.3 282.9 203.7 293.7L147.7 373.7C143.6 379.5 137.2 383.2 130.1 383.8C123 384.4 116 382 111 377L71 337C61.6 327.6 61.6 312.4 71 303.1C80.4 293.8 95.6 293.7 104.9 303.1L124.7 322.9L164.3 266.3C171.9 255.4 186.9 252.8 197.7 260.4zM288 160C288 142.3 302.3 128 320 128L544 128C561.7 128 576 142.3 576 160C576 177.7 561.7 192 544 192L320 192C302.3 192 288 177.7 288 160zM288 320C288 302.3 302.3 288 320 288L544 288C561.7 288 576 302.3 576 320C576 337.7 561.7 352 544 352L320 352C302.3 352 288 337.7 288 320zM224 480C224 462.3 238.3 448 256 448L544 448C561.7 448 576 462.3 576 480C576 497.7 561.7 512 544 512L256 512C238.3 512 224 497.7 224 480zM128 440C150.1 440 168 457.9 168 480C168 502.1 150.1 520 128 520C105.9 520 88 502.1 88 480C88 457.9 105.9 440 128 440z"/></svg>
        </button>';
}

function quick_tasks_render_patient_icons(PDO $pdo, array $patient, array $context = []): string
{
    $patient_id = (int)($patient['patient_id'] ?? 0);
    if ($patient_id <= 0) {
        return '';
    }

    $icons = quick_tasks_fetch_patient_icons($pdo, $patient_id);
    if (!$icons) {
        return '';
    }

    $lang = is_array($context['lang'] ?? null) ? $context['lang'] : [];
    $html = '<div style="display:flex; gap:6px; justify-content:center; align-items:center;">';

    foreach ($icons as $icon) {
        $is_completed = (string)($icon['status'] ?? '') === 'Completed';
        $colour = $is_completed ? '#2ecc71' : '#bdc3c7';

        if ($is_completed && !empty($icon['completed_date_time'])) {
            $by = trim((string)($icon['first_name'] ?? '') . ' ' . (string)($icon['last_name'] ?? ''));
            if ($by === '') {
                $by = $lang['TASK_UNKNOWN_USER'] ?? 'Unknown user';
            }
            $dt = date('d-m-Y H:i', strtotime((string)$icon['completed_date_time']));
            $tooltip = strtr($lang['TASK_TOOLTIP_COMPLETED'] ?? 'Patient had {task} - Completed by {by} on {dt}', [
                '{task}' => (string)$icon['task'],
                '{by}' => $by,
                '{dt}' => $dt,
            ]);
        } else {
            $tooltip = strtr($lang['TASK_TOOLTIP_REQUIRES'] ?? 'Requires {task} (mark to complete)', [
                '{task}' => (string)$icon['task'],
            ]);
        }

        $html .= '
            <span class="task-icon"
                  data-complete-url="modules/quick_tasks/controllers/quick_tasks_handler.php"
                  data-task-pt-id="' . (int)$icon['task_pt_id'] . '"
                  data-is-complete="' . ($is_completed ? '1' : '0') . '"
                  title="' . quick_tasks_h($tooltip) . '"
                  style="display:inline-block;width:30px;height:30px;cursor:' . ($is_completed ? 'default' : 'pointer') . ';">
                ' . quick_tasks_colour_svg((string)$icon['svg'], $colour) . '
            </span>';
    }

    return $html . '</div>';
}

function quick_tasks_render_patient_form(PDO $pdo, array $patient, array $context = []): string
{
    $patient_id = (int)($patient['patient_id'] ?? 0);
    if ($patient_id <= 0 || !quick_tasks_can_assign()) {
        return '';
    }

    $tasks = quick_tasks_fetch_available_tasks($pdo);
    $lang = is_array($context['lang'] ?? null) ? $context['lang'] : [];
    $label = $lang['TASK_LABEL_QUICK_TASK'] ?? 'Quick Task';
    $placeholder = $lang['TASK_SELECT_A_TASK'] ?? 'Select a Task';
    $button = $lang['TASK_ASSIGN_TASK'] ?? 'Assign Task';

    ob_start();
    ?>
    <div id="form-quick_tasks-<?= (int)$patient_id ?>" class="form-container" style="display:none;">
        <div class="rc-card rc-card-muted">
            <form action="modules/quick_tasks/controllers/quick_tasks_handler.php" method="post" class="xform">
                <input type="hidden" name="action" value="assign_task">
                <div class="xform-grid">
                    <div class="xform-field span-3">
                        <label class="xform-label" for="quick_task_id_<?= (int)$patient_id ?>"><?= quick_tasks_h($label) ?></label>
                        <select id="quick_task_id_<?= (int)$patient_id ?>" name="task_id" class="xform-input" required>
                            <option value="" disabled selected><?= quick_tasks_h($placeholder) ?></option>
                            <?php foreach ($tasks as $task): ?>
                                <option value="<?= (int)$task['task_id'] ?>">
                                    <?= quick_tasks_h($task['task']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="xform-field">
                        <input type="hidden" name="patient_id" value="<?= (int)$patient_id ?>">
                        <button type="submit" name="quick_task_assignform" class="btn purple"><?= quick_tasks_h($button) ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php
    return (string)ob_get_clean();
}
