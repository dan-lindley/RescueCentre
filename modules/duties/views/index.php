<?php
// modules/duties/views/index.php

if (!defined('APP_LOADED')) {
    exit;
}

require_once __DIR__ . '/../controllers/duties_lib.php';

duties_ensure_schema($pdo);
duties_register_permissions();

$dutiesLang = duties_module_language();
$centre_id_int = (int)($centre_id ?? $_SESSION['centre_id'] ?? 0);
$weekStart = duties_week_start((string)($_GET['week'] ?? ''));
$weekEnd = clone $weekStart;
$weekEnd->modify('+6 days');
$weekDates = duties_week_dates($weekStart);
$staff = duties_fetch_staff($pdo, $centre_id_int);
$areas = duties_fetch_areas($pdo, $centre_id_int);
$roleOptions = duties_role_options();
$todayValue = date('Y-m-d');
$shifts = duties_fetch_shifts($pdo, $centre_id_int, $weekStart, $weekEnd);
$tasks = duties_fetch_tasks($pdo, $centre_id_int, $weekStart, $weekEnd);
$editShiftId = (int)($_GET['edit_shift_id'] ?? 0);
$editingShift = $editShiftId > 0 ? duties_fetch_shift($pdo, $centre_id_int, $editShiftId) : null;
$recurringRules = duties_fetch_recurring_rules($pdo, $centre_id_int);
$recurringTaskRules = duties_fetch_recurring_task_rules($pdo, $centre_id_int);
$action = 'modules/duties/controllers/duties_handler.php';
$activeTab = (string)($_GET['tab'] ?? 'week');
if (!in_array($activeTab, ['week', 'rota', 'manage'], true)) {
    $activeTab = 'week';
}

function duties_lang(string $key): string
{
    global $lang, $dutiesLang;
    return duties_h($dutiesLang[$key] ?? $lang[$key] ?? $key);
}

function duties_message(?string $key): string
{
    if (!$key || !str_starts_with($key, 'ADD_')) {
        return '';
    }
    return duties_lang($key);
}

function duties_short_time($time): string
{
    $time = substr((string)$time, 0, 5);
    [$hour, $minute] = array_pad(explode(':', $time), 2, '00');
    $hour = (string)((int)$hour);
    return $minute === '00' ? $hour : $hour . ':' . $minute;
}

if (!duties_can_access()) {
    echo '<div class="alert-box alert-red">' . duties_lang('ADD_ACCESS_DENIED') . '</div>';
    return;
}

$shiftsByDate = [];
foreach ($shifts as $shift) {
    $shiftsByDate[(string)$shift['shift_date']][] = $shift;
}

$tasksByDate = [];
foreach ($tasks as $task) {
    $tasksByDate[(string)$task['task_date']][] = $task;
}

$calendarStartHour = 8;
$calendarEndHour = 18;
foreach ($shifts as $shift) {
    if (!empty($shift['start_time'])) {
        $startHour = (int)substr((string)$shift['start_time'], 0, 2);
        $calendarStartHour = min($calendarStartHour, $startHour);
        if ($startHour >= 19) {
            $calendarEndHour = max($calendarEndHour, $startHour);
        }
    }
    if (!empty($shift['end_time'])) {
        $endTime = substr((string)$shift['end_time'], 0, 5);
        $calendarEndHour = max($calendarEndHour, $endTime === '00:00' ? 24 : (int)substr($endTime, 0, 2));
    }
}
foreach ($tasks as $task) {
    if (!empty($task['due_time'])) {
        $taskHour = (int)substr((string)$task['due_time'], 0, 2);
        $calendarStartHour = min($calendarStartHour, $taskHour);
        $calendarEndHour = max($calendarEndHour, $taskHour);
    }
}
$calendarStartHour = max(0, $calendarStartHour);
$calendarEndHour = min(24, $calendarEndHour);
$earlyUsed = $calendarStartHour < 5;
$lateUsed = $calendarEndHour >= 19;
$earlyPph = $earlyUsed ? 34 : 14;
$mainPph = 52;
$latePph = $lateUsed ? 34 : 14;
$earlyHeight = 5 * $earlyPph;
$mainHeight = 14 * $mainPph;
$lateHeight = 5 * $latePph;
$timelineHeight = $earlyHeight + $mainHeight + $lateHeight;
$timeToY = static function (string $time) use ($earlyPph, $mainPph, $latePph, $earlyHeight, $mainHeight): int {
    $parts = explode(':', $time);
    $hour = isset($parts[0]) ? max(0, min(23, (int)$parts[0])) : 0;
    $minute = isset($parts[1]) ? max(0, min(59, (int)$parts[1])) : 0;
    $decimal = $hour + ($minute / 60);
    if ($decimal < 5) {
        return (int)round($decimal * $earlyPph);
    }
    if ($decimal < 19) {
        return (int)round($earlyHeight + (($decimal - 5) * $mainPph));
    }
    return (int)round($earlyHeight + $mainHeight + (($decimal - 19) * $latePph));
};
$durationHeight = static function (string $start, string $end) use ($timeToY, $timelineHeight): int {
    $startY = $timeToY($start);
    $endY = substr($end, 0, 5) === '00:00' ? $timelineHeight : $timeToY($end);
    if ($endY <= $startY) {
        $endY = $timelineHeight;
    }
    $height = $endY - $startY;
    return max(30, $height);
};
$dutyPalette = [
    '#2563eb', '#16a34a', '#d97706', '#dc2626', '#7c3aed',
    '#0891b2', '#be185d', '#4f46e5', '#0f766e', '#a16207',
    '#c2410c', '#9333ea', '#0369a1', '#15803d', '#b91c1c',
    '#6d28d9', '#047857', '#b45309', '#db2777', '#1d4ed8',
    '#65a30d', '#ea580c', '#0284c7', '#059669', '#9f1239',
];
$paletteColour = static function ($id) use ($dutyPalette): string {
    $index = (((int)$id - 1) % 25) + 1;
    return $dutyPalette[$index - 1];
};
$staffPalette = [];
$staffPaletteIndex = 0;
foreach ($staff as $person) {
    $staffPalette[(int)$person['id']] = $dutyPalette[$staffPaletteIndex];
    $staffPaletteIndex++;
    if ($staffPaletteIndex >= count($dutyPalette)) {
        $staffPaletteIndex = 0;
    }
}
$staffColour = static function ($staff_id) use ($staffPalette, $paletteColour): string {
    $staff_id = (int)$staff_id;
    return $staffPalette[$staff_id] ?? $paletteColour($staff_id);
};

$rollingDates = [];
for ($i = 0; $i < 42; $i++) {
    $rollingDay = clone $weekStart;
    $rollingDay->modify('+' . $i . ' days');
    $rollingDates[] = $rollingDay;
}
$rollingEnd = clone $weekStart;
$rollingEnd->modify('+41 days');
$rollingShifts = duties_fetch_shifts($pdo, $centre_id_int, $weekStart, $rollingEnd);
$rollingShiftsByDate = [];
foreach ($rollingShifts as $shift) {
    $rollingShiftsByDate[(string)$shift['shift_date']][] = $shift;
}

$msg = duties_message((string)($_GET['msg'] ?? ''));
$error = duties_message((string)($_GET['error'] ?? ''));
$weekValue = $weekStart->format('Y-m-d');
?>

<link rel="stylesheet" href="modules/duties/duties.css">

<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2><?= duties_lang('ADD_DUTIES_TITLE') ?></h2>
            <p><?= duties_lang('ADD_DUTIES_SUBTITLE') ?></p>
        </div>
    </div>
</div>

<?php if ($msg !== ''): ?>
    <div class="alert-box alert-green"><?= $msg ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
    <div class="alert-box alert-red"><?= $error ?></div>
<?php endif; ?>
<?php if (!$staff): ?>
    <div class="alert-box alert-grey"><?= duties_lang('ADD_NO_STAFF') ?></div>
<?php endif; ?>

<div class="duties-toolbar">
    <div>
        <strong><?= duties_lang('ADD_WEEK_OF') ?>:</strong>
        <?= duties_h($weekStart->format('j M Y')) ?> - <?= duties_h($weekEnd->format('j M Y')) ?>
    </div>
    <div class="duties-week-nav">
        <?php
            $previous = clone $weekStart;
            $previous->modify('-7 days');
            $next = clone $weekStart;
            $next->modify('+7 days');
        ?>
        <a class="btn grey" href="<?= duties_h(duties_view_url(['tab' => $activeTab, 'week' => $previous->format('Y-m-d')])) ?>"><?= duties_lang('ADD_PREVIOUS_WEEK') ?></a>
        <a class="btn" href="<?= duties_h(duties_view_url(['tab' => $activeTab])) ?>"><?= duties_lang('ADD_THIS_WEEK') ?></a>
        <a class="btn grey" href="<?= duties_h(duties_view_url(['tab' => $activeTab, 'week' => $next->format('Y-m-d')])) ?>"><?= duties_lang('ADD_NEXT_WEEK') ?></a>
    </div>
</div>

<div class="rc-tabs duties-tabs">
    <a href="<?= duties_h(duties_view_url(['tab' => 'week', 'week' => $weekValue])) ?>" class="rc-tab <?= $activeTab === 'week' ? 'is-active' : '' ?>"><?= duties_lang('ADD_WEEK') ?></a>
    <a href="<?= duties_h(duties_view_url(['tab' => 'rota', 'week' => $weekValue])) ?>" class="rc-tab <?= $activeTab === 'rota' ? 'is-active' : '' ?>"><?= duties_lang('ADD_ROTA') ?></a>
    <a href="<?= duties_h(duties_view_url(['tab' => 'manage', 'week' => $weekValue])) ?>" class="rc-tab <?= $activeTab === 'manage' ? 'is-active' : '' ?>"><?= duties_lang('ADD_MANAGE') ?></a>
    <a href="module.php?module=duties&view=shifts" class="rc-tab"><?= duties_lang('ADD_SHIFT_OVERVIEW') ?></a>
</div>

<?php if ($activeTab === 'week'): ?>
        <div class="content-block duties-panel">
            <h3><?= duties_lang('ADD_APPOINTMENTS_CALENDAR') ?></h3>
            <div class="duties-week-calendar" style="--duties-timeline-height: <?= (int)$timelineHeight ?>px;">
                <div class="duties-week-corner"></div>
                <?php foreach ($weekDates as $day): ?>
                    <div class="duties-week-head">
                        <strong><?= duties_h($day->format('D')) ?></strong>
                        <span><?= duties_h($day->format('j M')) ?></span>
                    </div>
                <?php endforeach; ?>

                <div class="duties-time-rail">
                    <?php foreach ([0, 5, 9, 12, 16, 19, 24] as $marker): ?>
                        <?php $markerTop = $marker === 24 ? $timelineHeight : $timeToY(str_pad((string)$marker, 2, '0', STR_PAD_LEFT) . ':00'); ?>
                        <span style="top: <?= (int)$markerTop ?>px;"><?= $marker === 24 ? '00:00' : duties_h(str_pad((string)$marker, 2, '0', STR_PAD_LEFT)) . ':00' ?></span>
                    <?php endforeach; ?>
                </div>
                <?php foreach ($weekDates as $day): ?>
                    <?php
                        $dateKey = $day->format('Y-m-d');
                        $anytimeTasks = array_filter($tasksByDate[$dateKey] ?? [], static fn($task) => empty($task['due_time']));
                        $dayShifts = $shiftsByDate[$dateKey] ?? [];
                        $timedTasks = array_filter($tasksByDate[$dateKey] ?? [], static fn($task) => !empty($task['due_time']));
                        $shiftLayout = [];
                        $laneEnds = [];
                        $labelRows = [];
                        foreach ($dayShifts as $index => $shift) {
                            $startTime = substr((string)$shift['start_time'], 0, 5);
                            $endTime = substr((string)$shift['end_time'], 0, 5);
                            $startY = $timeToY($startTime);
                            $endY = $endTime === '00:00' ? $timelineHeight : $timeToY($endTime);
                            if ($endY <= $startY) {
                                $endY = $timelineHeight;
                            }
                            $lane = 0;
                            while (isset($laneEnds[$lane]) && $laneEnds[$lane] > $startY) {
                                $lane++;
                            }
                            $labelKey = (string)$startY;
                            $labelRow = (int)($labelRows[$labelKey] ?? 0);
                            $labelRows[$labelKey] = $labelRow + 1;
                            $laneEnds[$lane] = $endY;
                            $shiftLayout[$index] = [
                                'lane' => $lane,
                                'top' => $startY,
                                'height' => max(30, $endY - $startY),
                                'label_offset' => $labelRow * 34,
                            ];
                        }
                        $shiftLaneCount = max(1, count($laneEnds));
                    ?>
                    <div class="duties-week-column">
                        <div class="duties-period duties-period-early" style="height: <?= (int)$earlyHeight ?>px;"></div>
                        <div class="duties-period duties-period-main" style="height: <?= (int)$mainHeight ?>px;"></div>
                        <div class="duties-period duties-period-late" style="height: <?= (int)$lateHeight ?>px;"></div>
                        <div class="duties-anytime-stack">
                        <?php foreach ($anytimeTasks as $task): ?>
                            <div class="duties-calendar-item is-task <?= (string)$task['status'] === 'done' ? 'is-done' : '' ?>">
                                <strong><?= duties_h($task['task_title']) ?></strong>
                                <span><?= duties_h(duties_person_name($task) ?: duties_lang('ADD_PERSON')) ?><?= !empty($task['area_name']) ? ' | ' . duties_h($task['area_name']) : '' ?></span>
                                <div class="duties-task-actions">
                                    <?php if ((string)$task['status'] !== 'done'): ?>
                                        <form method="post" action="<?= duties_h($action) ?>">
                                            <input type="hidden" name="action" value="complete_task">
                                            <input type="hidden" name="task_id" value="<?= duties_h($task['id']) ?>">
                                            <input type="hidden" name="week" value="<?= duties_h($weekValue) ?>">
                                            <input type="hidden" name="tab" value="week">
                                            <button type="submit" class="duties-icon-btn is-complete" title="<?= duties_lang('ADD_COMPLETE') ?>" aria-label="<?= duties_lang('ADD_COMPLETE') ?>">&#10003;</button>
                                        </form>
                                        <?php if (empty($task['staff_profile_id'])): ?>
                                            <form method="post" action="<?= duties_h($action) ?>" class="duties-assign-form">
                                                <input type="hidden" name="action" value="assign_task">
                                                <input type="hidden" name="task_id" value="<?= duties_h($task['id']) ?>">
                                                <input type="hidden" name="week" value="<?= duties_h($weekValue) ?>">
                                                <input type="hidden" name="tab" value="week">
                                                <select name="staff_profile_id" class="xform-input" aria-label="<?= duties_lang('ADD_ASSIGN') ?>">
                                                    <option value=""><?= duties_lang('ADD_ASSIGN_LATER') ?></option>
                                                    <?php foreach ($staff as $person): ?>
                                                        <option value="<?= (int)$person['id'] ?>"><?= duties_h(duties_person_name($person)) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="duties-icon-btn is-assign" title="<?= duties_lang('ADD_ASSIGN') ?>" aria-label="<?= duties_lang('ADD_ASSIGN') ?>">+</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <form method="post" action="<?= duties_h($action) ?>" onsubmit="return confirm('<?= duties_h(duties_lang('ADD_CONFIRM_DELETE_TASK')) ?>');">
                                        <input type="hidden" name="action" value="delete_task">
                                        <input type="hidden" name="task_id" value="<?= duties_h($task['id']) ?>">
                                        <input type="hidden" name="week" value="<?= duties_h($weekValue) ?>">
                                        <input type="hidden" name="tab" value="week">
                                        <button type="submit" class="duties-icon-btn is-delete" title="<?= duties_lang('ADD_DELETE') ?>" aria-label="<?= duties_lang('ADD_DELETE') ?>">&#128465;</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <?php foreach ($dayShifts as $shiftIndex => $shift): ?>
                            <?php
                                $shiftRole = (string)($shift['role_type'] ?? '');
                                $startTime = substr((string)$shift['start_time'], 0, 5);
                                $endTime = substr((string)$shift['end_time'], 0, 5);
                                $top = (int)($shiftLayout[$shiftIndex]['top'] ?? $timeToY($startTime));
                                $height = (int)($shiftLayout[$shiftIndex]['height'] ?? $durationHeight($startTime, $endTime));
                                $lane = (int)($shiftLayout[$shiftIndex]['lane'] ?? 0);
                                $labelOffset = (int)($shiftLayout[$shiftIndex]['label_offset'] ?? 0);
                                $colour = $staffColour($shift['staff_profile_id'] ?? 1);
                            ?>
                            <div class="duties-calendar-item is-shift is-span" style="--duty-colour: <?= duties_h($colour) ?>; --duty-lane: <?= (int)$lane ?>; --duty-lane-count: <?= (int)$shiftLaneCount ?>; --duty-label-offset: <?= (int)$labelOffset ?>px; top: <?= (int)$top ?>px; height: <?= (int)$height ?>px;">
                                <strong><span class="duties-person-dot"></span><?= duties_h(duties_person_name($shift)) ?></strong>
                                <em><?= duties_h($startTime) ?> - <?= duties_h($endTime) ?></em>
                                    <span>
                                        <?= !empty($roleOptions[$shiftRole]) ? duties_lang($roleOptions[$shiftRole]) : '' ?>
                                        <?= !empty($shift['area_name']) ? ' | ' . duties_h($shift['area_name']) : '' ?>
                                        <?= !empty($shift['virtual_shift']) ? ' | ' . duties_lang('ADD_REPEATS_WEEKLY') : '' ?>
                                    </span>
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($timedTasks as $task): ?>
                            <?php $taskTop = $timeToY(substr((string)$task['due_time'], 0, 5)); ?>
                            <div class="duties-calendar-item is-task is-point <?= (string)$task['status'] === 'done' ? 'is-done' : '' ?>" style="top: <?= (int)$taskTop ?>px;">
                                <strong><?= duties_h(substr((string)$task['due_time'], 0, 5)) ?> <?= duties_h($task['task_title']) ?></strong>
                                <span><?= duties_h(duties_person_name($task) ?: duties_lang('ADD_PERSON')) ?><?= !empty($task['area_name']) ? ' | ' . duties_h($task['area_name']) : '' ?></span>
                                <div class="duties-task-actions">
                                    <?php if ((string)$task['status'] !== 'done'): ?>
                                        <form method="post" action="<?= duties_h($action) ?>">
                                            <input type="hidden" name="action" value="complete_task">
                                            <input type="hidden" name="task_id" value="<?= duties_h($task['id']) ?>">
                                            <input type="hidden" name="week" value="<?= duties_h($weekValue) ?>">
                                            <input type="hidden" name="tab" value="week">
                                            <button type="submit" class="duties-icon-btn is-complete" title="<?= duties_lang('ADD_COMPLETE') ?>" aria-label="<?= duties_lang('ADD_COMPLETE') ?>">&#10003;</button>
                                        </form>
                                        <?php if (empty($task['staff_profile_id'])): ?>
                                            <form method="post" action="<?= duties_h($action) ?>" class="duties-assign-form">
                                                <input type="hidden" name="action" value="assign_task">
                                                <input type="hidden" name="task_id" value="<?= duties_h($task['id']) ?>">
                                                <input type="hidden" name="week" value="<?= duties_h($weekValue) ?>">
                                                <input type="hidden" name="tab" value="week">
                                                <select name="staff_profile_id" class="xform-input" aria-label="<?= duties_lang('ADD_ASSIGN') ?>">
                                                    <option value=""><?= duties_lang('ADD_ASSIGN_LATER') ?></option>
                                                    <?php foreach ($staff as $person): ?>
                                                        <option value="<?= (int)$person['id'] ?>"><?= duties_h(duties_person_name($person)) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="duties-icon-btn is-assign" title="<?= duties_lang('ADD_ASSIGN') ?>" aria-label="<?= duties_lang('ADD_ASSIGN') ?>">+</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <form method="post" action="<?= duties_h($action) ?>" onsubmit="return confirm('<?= duties_h(duties_lang('ADD_CONFIRM_DELETE_TASK')) ?>');">
                                        <input type="hidden" name="action" value="delete_task">
                                        <input type="hidden" name="task_id" value="<?= duties_h($task['id']) ?>">
                                        <input type="hidden" name="week" value="<?= duties_h($weekValue) ?>">
                                        <input type="hidden" name="tab" value="week">
                                        <button type="submit" class="duties-icon-btn is-delete" title="<?= duties_lang('ADD_DELETE') ?>" aria-label="<?= duties_lang('ADD_DELETE') ?>">&#128465;</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                <?php endforeach; ?>
            </div>
        </div>
<?php elseif ($activeTab === 'rota'): ?>

        <div class="content-block duties-panel">
            <h3><?= duties_lang('ADD_SIX_WEEK_ROTA') ?></h3>
            <div class="duties-six-week">
            <?php foreach ($rollingDates as $day): ?>
                <?php
                    $dateKey = $day->format('Y-m-d');
                    $dayShifts = $rollingShiftsByDate[$dateKey] ?? [];
                ?>
                    <div class="duties-six-day <?= $dateKey === date('Y-m-d') ? 'is-today' : '' ?>">
                        <div class="duties-six-day-head">
                            <strong><?= duties_h($day->format('D j')) ?></strong>
                            <span><?= duties_h($day->format('M')) ?></span>
                        </div>
                        <?php foreach ($dayShifts as $shift): ?>
                            <?php $colour = $staffColour($shift['staff_profile_id'] ?? 1); ?>
                            <div class="duties-six-shift" style="--duty-colour: <?= duties_h($colour) ?>;">
                                <span>[<?= duties_h(duties_short_time($shift['start_time'])) ?>-<?= duties_h(duties_short_time($shift['end_time'])) ?>]</span>
                                <small><?= duties_h(duties_person_name($shift)) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
            <?php endforeach; ?>
            </div>
        </div>
<?php else: ?>
    <div class="content-block duties-manage-shell">
        <div class="duties-manage-layout">
            <div class="duties-manage-card">
                <div class="duties-manage-head">
                    <h3><?= $editingShift ? duties_lang('ADD_EDIT_SHIFT') : duties_lang('ADD_ADD_SHIFT') ?></h3>
                    <?php if ($editingShift): ?>
                        <a class="btn grey" href="<?= duties_h(duties_view_url(['tab' => 'manage', 'week' => $weekValue])) ?>"><?= duties_lang('ADD_CANCEL') ?></a>
                    <?php endif; ?>
                </div>
                <form method="post" action="<?= duties_h($action) ?>" class="xform-grid">
                <input type="hidden" name="action" value="save_shift">
                <input type="hidden" name="shift_id" value="<?= (int)($editingShift['id'] ?? 0) ?>">
                <input type="hidden" name="week" value="<?= duties_h($weekValue) ?>">
                <input type="hidden" name="tab" value="manage">

                <div class="xform-field span-2">
                    <label class="xform-label"><?= duties_lang('ADD_PERSON') ?></label>
                    <select name="staff_profile_id" class="xform-input" required>
                        <option value=""></option>
                        <?php foreach ($staff as $person): ?>
                            <option value="<?= (int)$person['id'] ?>" <?= (int)($editingShift['staff_profile_id'] ?? 0) === (int)$person['id'] ? 'selected' : '' ?>><?= duties_h(duties_person_name($person)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="xform-field">
                    <label class="xform-label"><?= duties_lang('ADD_DATE') ?></label>
                    <input type="date" name="shift_date" class="xform-input" value="<?= duties_h($editingShift['shift_date'] ?? $todayValue) ?>" min="<?= duties_h($todayValue) ?>" required>
                </div>
                <div class="xform-field">
                    <label class="xform-label"><?= duties_lang('ADD_AREA') ?></label>
                    <select name="area_id" class="xform-input">
                        <option value=""><?= duties_lang('ADD_NO_AREA') ?></option>
                        <?php foreach ($areas as $area): ?>
                            <option value="<?= (int)$area['area_id'] ?>" <?= (int)($editingShift['area_id'] ?? 0) === (int)$area['area_id'] ? 'selected' : '' ?>><?= duties_h($area['area_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="xform-field">
                    <label class="xform-label"><?= duties_lang('ADD_START_TIME') ?></label>
                    <input type="time" name="start_time" class="xform-input" value="<?= duties_h(substr((string)($editingShift['start_time'] ?? ''), 0, 5)) ?>" required>
                </div>
                <div class="xform-field">
                    <label class="xform-label"><?= duties_lang('ADD_END_TIME') ?></label>
                    <input type="time" name="end_time" class="xform-input" value="<?= duties_h(substr((string)($editingShift['end_time'] ?? ''), 0, 5)) ?>" required>
                </div>
                <div class="xform-field span-2">
                    <label class="xform-label"><?= duties_lang('ADD_ROLE') ?></label>
                    <select name="role_type" class="xform-input">
                        <option value=""></option>
                        <?php foreach ($roleOptions as $value => $label): ?>
                            <option value="<?= duties_h($value) ?>" <?= (string)($editingShift['role_type'] ?? '') === (string)$value ? 'selected' : '' ?>><?= duties_lang($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!$editingShift): ?>
                    <div class="xform-field">
                        <label class="xform-label">
                            <input type="checkbox" name="repeat_weekly" value="1">
                            <?= duties_lang('ADD_REPEAT_WEEKLY') ?>
                        </label>
                    </div>
                    <div class="xform-field">
                        <label class="xform-label"><?= duties_lang('ADD_ENDS_ON') ?></label>
                        <input type="date" name="ends_on" class="xform-input" min="<?= duties_h($todayValue) ?>">
                    </div>
                <?php endif; ?>
                <div class="xform-field span-2">
                    <label class="xform-label"><?= duties_lang('ADD_NOTES') ?></label>
                    <textarea name="notes" class="xform-input" rows="3"><?= duties_h($editingShift['notes'] ?? '') ?></textarea>
                </div>
                <div class="xform-button-row span-2">
                    <button type="submit" class="btn green"><?= $editingShift ? duties_lang('ADD_UPDATE_SHIFT') : duties_lang('ADD_SAVE_SHIFT') ?></button>
                </div>
                </form>
            </div>

            <div class="duties-manage-card">
                <div class="duties-manage-head">
                    <h3><?= duties_lang('ADD_ADD_TASK') ?></h3>
                </div>
                <form method="post" action="<?= duties_h($action) ?>" class="xform-grid">
                <input type="hidden" name="action" value="save_task">
                <input type="hidden" name="week" value="<?= duties_h($weekValue) ?>">
                <input type="hidden" name="tab" value="manage">

                <div class="xform-field span-2">
                    <label class="xform-label"><?= duties_lang('ADD_TASK_TITLE') ?></label>
                    <input type="text" name="task_title" class="xform-input" required>
                </div>
                <div class="xform-field">
                    <label class="xform-label"><?= duties_lang('ADD_TASK_DATE') ?></label>
                    <input type="date" name="task_date" class="xform-input" value="<?= duties_h($todayValue) ?>" min="<?= duties_h($todayValue) ?>" required>
                </div>
                <div class="xform-field">
                    <label class="xform-label"><?= duties_lang('ADD_DUE_TIME') ?></label>
                    <input type="time" name="due_time" class="xform-input">
                </div>
                <div class="xform-field">
                    <label class="xform-label"><?= duties_lang('ADD_PERSON') ?></label>
                    <select name="staff_profile_id" class="xform-input">
                        <option value=""></option>
                        <?php foreach ($staff as $person): ?>
                            <option value="<?= (int)$person['id'] ?>"><?= duties_h(duties_person_name($person)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="xform-field">
                    <label class="xform-label"><?= duties_lang('ADD_AREA') ?></label>
                    <select name="area_id" class="xform-input">
                        <option value=""><?= duties_lang('ADD_NO_AREA') ?></option>
                        <?php foreach ($areas as $area): ?>
                            <option value="<?= (int)$area['area_id'] ?>"><?= duties_h($area['area_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="xform-field span-2">
                    <label class="xform-label"><?= duties_lang('ADD_PRIORITY') ?></label>
                    <select name="priority" class="xform-input">
                        <option value="normal"><?= duties_lang('ADD_NORMAL') ?></option>
                        <option value="high"><?= duties_lang('ADD_HIGH') ?></option>
                        <option value="low"><?= duties_lang('ADD_LOW') ?></option>
                    </select>
                </div>
                <div class="xform-field">
                    <label class="xform-label">
                        <input type="checkbox" name="repeat_weekly" value="1">
                        <?= duties_lang('ADD_REPEAT_WEEKLY') ?>
                    </label>
                </div>
                <div class="xform-field">
                    <label class="xform-label"><?= duties_lang('ADD_ENDS_ON') ?></label>
                    <input type="date" name="ends_on" class="xform-input" min="<?= duties_h($todayValue) ?>">
                </div>
                <div class="xform-field span-2">
                    <label class="xform-label"><?= duties_lang('ADD_NOTES') ?></label>
                    <textarea name="task_notes" class="xform-input" rows="3"></textarea>
                </div>
                <div class="xform-button-row span-2">
                    <button type="submit" class="btn green"><?= duties_lang('ADD_SAVE_TASK') ?></button>
                </div>
                </form>
            </div>
        </div>

        <div class="duties-manage-list-grid">
            <div class="duties-manage-card">
                <div class="duties-manage-head">
                    <h3><?= duties_lang('ADD_RECURRING_RUNS') ?></h3>
                </div>
                <?php if (!$recurringRules): ?>
                    <p class="rc-muted"><?= duties_lang('ADD_NO_RECURRING_RUNS') ?></p>
                <?php else: ?>
                    <div class="rc-list">
                        <?php foreach ($recurringRules as $rule): ?>
                            <div class="rc-item duties-manage-row">
                                <div class="rc-item-main">
                                    <strong><?= duties_h(duties_person_name($rule)) ?> <?= duties_h(substr((string)$rule['start_time'], 0, 5)) ?>-<?= duties_h(substr((string)$rule['end_time'], 0, 5)) ?></strong>
                                    <small>
                                        <?= duties_lang('ADD_REPEATS_WEEKLY') ?>
                                        <?= !empty($rule['area_name']) ? ' - ' . duties_h($rule['area_name']) : '' ?>
                                        <?= !empty($rule['ends_on']) ? ' - ' . duties_lang('ADD_ENDS_ON') . ' ' . duties_h($rule['ends_on']) : '' ?>
                                    </small>
                                </div>
                                <form method="post" action="<?= duties_h($action) ?>" class="duties-end-run-form">
                                    <input type="hidden" name="action" value="end_recurring_rule">
                                    <input type="hidden" name="rule_id" value="<?= (int)$rule['id'] ?>">
                                    <input type="hidden" name="week" value="<?= duties_h($weekValue) ?>">
                                    <input type="hidden" name="tab" value="manage">
                                    <input type="date" name="ends_on" class="xform-input" value="<?= duties_h($todayValue) ?>" min="<?= duties_h($todayValue) ?>">
                                    <button type="submit" class="btn amber"><?= duties_lang('ADD_END_RUN') ?></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="duties-manage-card">
                <div class="duties-manage-head">
                    <h3><?= duties_lang('ADD_RECURRING_TASKS') ?></h3>
                </div>
                <?php if (!$recurringTaskRules): ?>
                    <p class="rc-muted"><?= duties_lang('ADD_NO_RECURRING_TASKS') ?></p>
                <?php else: ?>
                    <div class="rc-list">
                        <?php foreach ($recurringTaskRules as $rule): ?>
                            <?php
                                $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
                                $dayName = $dayNames[(int)($rule['day_of_week'] ?? 0)] ?? '';
                            ?>
                            <div class="rc-item duties-manage-row">
                                <div class="rc-item-main">
                                    <strong><?= duties_h($rule['task_title']) ?><?= !empty($rule['due_time']) ? ' (' . duties_h(substr((string)$rule['due_time'], 0, 5)) . ')' : '' ?></strong>
                                    <small>
                                        <?= duties_h($dayName) ?> - <?= duties_lang('ADD_REPEATS_WEEKLY') ?>
                                        <?= duties_person_name($rule) !== '' ? ' - ' . duties_h(duties_person_name($rule)) : '' ?>
                                        <?= !empty($rule['area_name']) ? ' - ' . duties_h($rule['area_name']) : '' ?>
                                        <?= !empty($rule['ends_on']) ? ' - ' . duties_lang('ADD_ENDS_ON') . ' ' . duties_h($rule['ends_on']) : '' ?>
                                    </small>
                                </div>
                                <form method="post" action="<?= duties_h($action) ?>" class="duties-end-run-form">
                                    <input type="hidden" name="action" value="end_recurring_task_rule">
                                    <input type="hidden" name="rule_id" value="<?= (int)$rule['id'] ?>">
                                    <input type="hidden" name="week" value="<?= duties_h($weekValue) ?>">
                                    <input type="hidden" name="tab" value="manage">
                                    <input type="date" name="ends_on" class="xform-input" value="<?= duties_h($todayValue) ?>" min="<?= duties_h($todayValue) ?>">
                                    <button type="submit" class="btn amber"><?= duties_lang('ADD_END_RUN') ?></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
