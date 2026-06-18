<?php
// modules/duties/views/shifts.php

if (!defined('APP_LOADED')) {
    exit;
}

require_once __DIR__ . '/../controllers/duties_lib.php';

duties_ensure_schema($pdo);
duties_register_permissions();

$dutiesLang = duties_module_language();
$centre_id_int = (int)($centre_id ?? $_SESSION['centre_id'] ?? 0);
$todayValue = date('Y-m-d');
$viewStartParam = duties_null($_GET['start'] ?? '');
$start = duties_week_start($viewStartParam ?: 'today');
$end = clone $start;
$end->modify('+13 days');
$previousStart = clone $start;
$previousStart->modify('-14 days');
$nextStart = clone $start;
$nextStart->modify('+14 days');
$action = 'modules/duties/controllers/duties_handler.php';
$staff = duties_fetch_staff($pdo, $centre_id_int);
$areas = duties_fetch_areas($pdo, $centre_id_int);
$roleOptions = duties_role_options();
$assignDate = duties_null($_GET['assign_date'] ?? '');
if (!$assignDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $assignDate) || $assignDate < $todayValue) {
    $assignDate = '';
}
$editShiftId = (int)($_GET['edit_shift_id'] ?? 0);
$viewUrl = static function (DateTime $date, array $params = []): string {
    $query = ['module' => 'duties', 'view' => 'shifts', 'start' => $date->format('Y-m-d')] + $params;
    return 'module.php?' . http_build_query($query);
};

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

if (!duties_can_access()) {
    echo '<div class="alert-box alert-red">' . duties_lang('ADD_ACCESS_DENIED') . '</div>';
    return;
}

$shifts = duties_fetch_shifts($pdo, $centre_id_int, $start, $end);
$shiftsByDate = [];
foreach ($shifts as $shift) {
    $shiftsByDate[(string)$shift['shift_date']][] = $shift;
}

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

$weeks = [];
for ($week = 0; $week < 2; $week++) {
    $days = [];
    for ($day = 0; $day < 7; $day++) {
        $date = clone $start;
        $date->modify('+' . (($week * 7) + $day) . ' days');
        $days[] = $date;
    }
    $weeks[] = $days;
}

$msg = duties_message((string)($_GET['msg'] ?? ''));
$error = duties_message((string)($_GET['error'] ?? ''));
?>

<link rel="stylesheet" href="modules/duties/duties.css">

<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2><?= duties_lang('ADD_SHIFT_OVERVIEW') ?></h2>
            <p><?= duties_lang('ADD_SHIFT_OVERVIEW_SUBTITLE') ?></p>
        </div>
    </div>
</div>

<?php if ($msg): ?><div class="alert-box alert-green"><?= $msg ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert-box alert-red"><?= $error ?></div><?php endif; ?>

<div class="rc-tabs duties-tabs">
    <a href="module.php?module=duties&view=index&tab=week" class="rc-tab"><?= duties_lang('ADD_WEEK') ?></a>
    <a href="module.php?module=duties&view=index&tab=rota" class="rc-tab"><?= duties_lang('ADD_ROTA') ?></a>
    <a href="module.php?module=duties&view=index&tab=manage" class="rc-tab"><?= duties_lang('ADD_MANAGE') ?></a>
    <a href="<?= duties_h($viewUrl($start)) ?>" class="rc-tab is-active"><?= duties_lang('ADD_SHIFT_OVERVIEW') ?></a>
</div>

<div class="duties-toolbar duties-shift-nav">
    <div>
        <strong><?= duties_h($start->format('j M Y')) ?> - <?= duties_h($end->format('j M Y')) ?></strong>
    </div>
    <div class="duties-week-nav">
        <a class="btn grey" href="<?= duties_h($viewUrl($previousStart)) ?>">&larr; Previous 2 weeks</a>
        <a class="btn" href="module.php?module=duties&view=shifts">Today</a>
        <a class="btn grey" href="<?= duties_h($viewUrl($nextStart)) ?>">Next 2 weeks &rarr;</a>
    </div>
    <form method="get" action="module.php" class="duties-jump-form">
        <input type="hidden" name="module" value="duties">
        <input type="hidden" name="view" value="shifts">
        <label class="xform-label" for="duties-jump-week"><?= duties_lang('ADD_JUMP_TO_WEEK') ?></label>
        <input id="duties-jump-week" type="date" name="start" class="xform-input" value="<?= duties_h($start->format('Y-m-d')) ?>">
        <button type="submit" class="btn"><?= duties_lang('ADD_GO') ?></button>
    </form>
</div>

<div class="content-block duties-shift-overview">
    <div class="duties-two-week-grid">
        <?php foreach ($weeks as $weekIndex => $days): ?>
            <div class="duties-manage-card">
                <div class="duties-manage-head">
                    <h3><?= $weekIndex === 0 ? duties_lang('ADD_WEEK_ONE') : duties_lang('ADD_WEEK_TWO') ?></h3>
                </div>
                <div class="duties-manage-day-list">
                    <?php foreach ($days as $day): ?>
                        <?php
                            $dateKey = $day->format('Y-m-d');
                            $dayShifts = $shiftsByDate[$dateKey] ?? [];
                            $canManageDay = $dateKey >= $todayValue;
                        ?>
                        <div id="day-<?= duties_h($dateKey) ?>" class="duties-manage-day <?= $dateKey === $todayValue ? 'is-today' : '' ?>">
                            <h4><?= duties_h($day->format('l')) ?> <span><?= duties_h($day->format('j M')) ?></span></h4>
                            <?php if (!$dayShifts): ?>
                                <div class="duties-empty-shift-row">
                                    <p class="rc-muted"><?= duties_lang('ADD_NO_SHIFTS') ?></p>
                                    <?php if ($canManageDay): ?>
                                        <?php if ($assignDate === $dateKey): ?>
                                            <a class="btn grey" href="<?= duties_h($viewUrl($start)) ?>#day-<?= duties_h($dateKey) ?>"><?= duties_lang('ADD_CANCEL') ?></a>
                                        <?php else: ?>
                                            <a class="btn" href="<?= duties_h($viewUrl($start, ['assign_date' => $dateKey])) ?>#assign-<?= duties_h($dateKey) ?>"><?= duties_lang('ADD_ASSIGN') ?></a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($canManageDay && $assignDate === $dateKey): ?>
                                    <form id="assign-<?= duties_h($dateKey) ?>" method="post" action="<?= duties_h($action) ?>" class="xform-grid duties-inline-shift-form">
                                        <input type="hidden" name="action" value="save_shift">
                                        <input type="hidden" name="week" value="<?= duties_h($dateKey) ?>">
                                        <input type="hidden" name="tab" value="manage">
                                        <input type="hidden" name="return_to" value="shifts">
                                        <input type="hidden" name="overview_start" value="<?= duties_h($start->format('Y-m-d')) ?>">
                                        <input type="hidden" name="shift_date" value="<?= duties_h($dateKey) ?>">

                                        <div class="xform-field span-2">
                                            <label class="xform-label"><?= duties_lang('ADD_PERSON') ?></label>
                                            <select name="staff_profile_id" class="xform-input" required>
                                                <option value=""></option>
                                                <?php foreach ($staff as $person): ?>
                                                    <option value="<?= (int)$person['id'] ?>"><?= duties_h(duties_person_name($person)) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="xform-field">
                                            <label class="xform-label"><?= duties_lang('ADD_START_TIME') ?></label>
                                            <input type="time" name="start_time" class="xform-input" required>
                                        </div>
                                        <div class="xform-field">
                                            <label class="xform-label"><?= duties_lang('ADD_END_TIME') ?></label>
                                            <input type="time" name="end_time" class="xform-input" required>
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
                                        <div class="xform-field">
                                            <label class="xform-label"><?= duties_lang('ADD_ROLE') ?></label>
                                            <select name="role_type" class="xform-input">
                                                <option value=""></option>
                                                <?php foreach ($roleOptions as $value => $label): ?>
                                                    <option value="<?= duties_h($value) ?>"><?= duties_lang($label) ?></option>
                                                <?php endforeach; ?>
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
                                            <textarea name="notes" class="xform-input" rows="2"></textarea>
                                        </div>
                                        <div class="xform-button-row span-2">
                                            <button type="submit" class="btn green"><?= duties_lang('ADD_SAVE_SHIFT') ?></button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="rc-list">
                                    <?php foreach ($dayShifts as $shift): ?>
                                        <?php
                                            $isVirtual = !empty($shift['virtual_shift']);
                                            $colour = $staffColour($shift['staff_profile_id'] ?? 1);
                                        ?>
                                        <div class="rc-item duties-manage-row duties-covered-shift" style="--duty-colour: <?= duties_h($colour) ?>;">
                                            <div class="rc-item-main">
                                                <strong><?= duties_h(duties_person_name($shift)) ?> (<?= duties_h(substr((string)$shift['start_time'], 0, 5)) ?> - <?= duties_h(substr((string)$shift['end_time'], 0, 5)) ?>)</strong>
                                                <small>
                                                    <?= !empty($shift['area_name']) ? duties_h($shift['area_name']) : duties_lang('ADD_NO_AREA') ?>
                                                    <?= $isVirtual ? ' - ' . duties_lang('ADD_REPEATS_WEEKLY') : '' ?>
                                                </small>
                                            </div>
                                            <?php if (!$isVirtual && $canManageDay): ?>
                                                <?php if ($editShiftId === (int)$shift['id']): ?>
                                                    <a class="btn grey" href="<?= duties_h($viewUrl($start)) ?>#day-<?= duties_h($dateKey) ?>"><?= duties_lang('ADD_CANCEL') ?></a>
                                                <?php else: ?>
                                                    <a class="btn grey" href="<?= duties_h($viewUrl($start, ['edit_shift_id' => (int)$shift['id']])) ?>#edit-shift-<?= (int)$shift['id'] ?>"><?= duties_lang('ADD_EDIT') ?></a>
                                                <?php endif; ?>
                                                <form method="post" action="<?= duties_h($action) ?>" onsubmit="return confirm('<?= duties_h(duties_lang('ADD_CONFIRM_DELETE_SHIFT')) ?>');">
                                                    <input type="hidden" name="action" value="delete_shift">
                                                    <input type="hidden" name="shift_id" value="<?= (int)$shift['id'] ?>">
                                                    <input type="hidden" name="week" value="<?= duties_h($dateKey) ?>">
                                                    <input type="hidden" name="tab" value="manage">
                                                    <input type="hidden" name="return_to" value="shifts">
                                                    <input type="hidden" name="overview_start" value="<?= duties_h($start->format('Y-m-d')) ?>">
                                                    <button type="submit" class="btn red"><?= duties_lang('ADD_DELETE') ?></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!$isVirtual && $canManageDay && $editShiftId === (int)$shift['id']): ?>
                                            <form id="edit-shift-<?= (int)$shift['id'] ?>" method="post" action="<?= duties_h($action) ?>" class="xform-grid duties-inline-shift-form">
                                                <input type="hidden" name="action" value="save_shift">
                                                <input type="hidden" name="shift_id" value="<?= (int)$shift['id'] ?>">
                                                <input type="hidden" name="week" value="<?= duties_h($dateKey) ?>">
                                                <input type="hidden" name="tab" value="manage">
                                                <input type="hidden" name="return_to" value="shifts">
                                                <input type="hidden" name="overview_start" value="<?= duties_h($start->format('Y-m-d')) ?>">

                                                <div class="xform-field span-2">
                                                    <label class="xform-label"><?= duties_lang('ADD_PERSON') ?></label>
                                                    <select name="staff_profile_id" class="xform-input" required>
                                                        <option value=""></option>
                                                        <?php foreach ($staff as $person): ?>
                                                            <option value="<?= (int)$person['id'] ?>" <?= (int)($shift['staff_profile_id'] ?? 0) === (int)$person['id'] ? 'selected' : '' ?>><?= duties_h(duties_person_name($person)) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="xform-field">
                                                    <label class="xform-label"><?= duties_lang('ADD_DATE') ?></label>
                                                    <input type="date" name="shift_date" class="xform-input" value="<?= duties_h($dateKey) ?>" min="<?= duties_h($todayValue) ?>" required>
                                                </div>
                                                <div class="xform-field">
                                                    <label class="xform-label"><?= duties_lang('ADD_START_TIME') ?></label>
                                                    <input type="time" name="start_time" class="xform-input" value="<?= duties_h(substr((string)$shift['start_time'], 0, 5)) ?>" required>
                                                </div>
                                                <div class="xform-field">
                                                    <label class="xform-label"><?= duties_lang('ADD_END_TIME') ?></label>
                                                    <input type="time" name="end_time" class="xform-input" value="<?= duties_h(substr((string)$shift['end_time'], 0, 5)) ?>" required>
                                                </div>
                                                <div class="xform-field">
                                                    <label class="xform-label"><?= duties_lang('ADD_AREA') ?></label>
                                                    <select name="area_id" class="xform-input">
                                                        <option value=""><?= duties_lang('ADD_NO_AREA') ?></option>
                                                        <?php foreach ($areas as $area): ?>
                                                            <option value="<?= (int)$area['area_id'] ?>" <?= (int)($shift['area_id'] ?? 0) === (int)$area['area_id'] ? 'selected' : '' ?>><?= duties_h($area['area_name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="xform-field">
                                                    <label class="xform-label"><?= duties_lang('ADD_ROLE') ?></label>
                                                    <select name="role_type" class="xform-input">
                                                        <option value=""></option>
                                                        <?php foreach ($roleOptions as $value => $label): ?>
                                                            <option value="<?= duties_h($value) ?>" <?= (string)($shift['role_type'] ?? '') === (string)$value ? 'selected' : '' ?>><?= duties_lang($label) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="xform-field span-2">
                                                    <label class="xform-label"><?= duties_lang('ADD_NOTES') ?></label>
                                                    <textarea name="notes" class="xform-input" rows="2"><?= duties_h($shift['notes'] ?? '') ?></textarea>
                                                </div>
                                                <div class="xform-button-row span-2">
                                                    <button type="submit" class="btn green"><?= duties_lang('ADD_UPDATE_SHIFT') ?></button>
                                                    <a class="btn grey" href="<?= duties_h($viewUrl($start)) ?>#day-<?= duties_h($dateKey) ?>"><?= duties_lang('ADD_CANCEL') ?></a>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
