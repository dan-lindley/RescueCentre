<?php
// modules/duties/controllers/home_widgets.php

function duties_home_widgets_provider(): array
{
    return [
        'key' => 'duties_today',
        'order' => 20,
        'render_callback' => 'duties_render_home_today_widget',
    ];
}

function duties_render_home_today_widget(PDO $pdo, array $context = []): string
{
    require_once __DIR__ . '/duties_lib.php';

    $centre_id = (int)($context['centre_id'] ?? $_SESSION['centre_id'] ?? $GLOBALS['centre_id'] ?? 0);
    $account_id = (int)($_SESSION['account_id'] ?? $_SESSION['user_id'] ?? $GLOBALS['user_id'] ?? 0);
    if ($centre_id <= 0 || $account_id <= 0) {
        return '';
    }

    duties_ensure_schema($pdo);
    $homeData = duties_home_data_for_account($pdo, $centre_id, $account_id);
    $profile = $homeData['profile'];
    if (!$profile) {
        return '';
    }

    $today = $homeData['today'];
    $shifts_today = $homeData['today_shifts'];
    $tasks = $homeData['today_tasks'];
    $upcoming_shifts = $homeData['upcoming_shifts'];

    $dutiesLang = duties_module_language();
    $lang_for_widget = static function (string $key) use ($dutiesLang): string {
        global $lang;
        return duties_h($dutiesLang[$key] ?? $lang[$key] ?? $key);
    };
    $short_time = static function ($time): string {
        $time = substr((string)$time, 0, 5);
        [$hour, $minute] = array_pad(explode(':', $time), 2, '00');
        $hour = (string)((int)$hour);
        return $minute === '00' ? $hour : $hour . ':' . $minute;
    };
    $format_shift_date = static function ($date): string {
        $dt = new DateTime((string)$date);
        $day = (int)$dt->format('j');
        $suffix = 'th';
        if (!in_array($day % 100, [11, 12, 13], true)) {
            $suffixes = [1 => 'st', 2 => 'nd', 3 => 'rd'];
            $suffix = $suffixes[$day % 10] ?? 'th';
        }
        return $dt->format('l ') . $day . $suffix . $dt->format(' M');
    };
    ob_start();
    ?>
    <link rel="stylesheet" href="core/css/core.css">
    <link rel="stylesheet" href="modules/duties/duties.css">
    <div class="rc-panel duties-home-panel">
        <div class="rc-split-head home-section-head duties-home-panel-head">
            <div>
                <h3>Duties</h3>
                <p class="rc-muted">Your assigned tasks and upcoming shifts.</p>
            </div>
        </div>

        <div class="duties-home-widget">
            <div class="rc-card duties-home-card">
                <div class="home-section-head">
                    <h3>Assigned duties for <?= duties_h(duties_person_name($profile)) ?></h3>
                </div>

                <?php if (!$shifts_today && !$tasks): ?>
                    <div class="alert-box alert-grey duties-home-empty"><?= $lang_for_widget('ADD_NO_DUTIES_TODAY') ?></div>
                <?php else: ?>
                    <div class="rc-list duties-home-list">
                        <?php foreach ($shifts_today as $shift): ?>
                            <div class="rc-item">
                                <div class="rc-item-main">
                                    <strong><?= duties_h($short_time($shift['start_time'])) ?>-<?= duties_h($short_time($shift['end_time'])) ?></strong>
                                    <small>
                                        <?= !empty($shift['area_name']) ? duties_h($shift['area_name']) : $lang_for_widget('ADD_NO_AREA') ?>
                                        <?= !empty($shift['virtual_shift']) ? ' - ' . $lang_for_widget('ADD_REPEATS_WEEKLY') : '' ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($tasks as $task): ?>
                            <div class="rc-item">
                                <div class="rc-item-main">
                                    <strong><?= !empty($task['due_time']) ? duties_h(substr((string)$task['due_time'], 0, 5)) . ' - ' : '' ?><?= duties_h($task['task_title']) ?></strong>
                                    <small>
                                        <?= !empty($task['area_name']) ? duties_h($task['area_name']) . ' - ' : '' ?>
                                        <?= (string)$task['status'] === 'done' ? $lang_for_widget('ADD_DONE') : $lang_for_widget('ADD_OPEN') ?>
                                    </small>
                                </div>
                                <?php if ((string)$task['status'] !== 'done'): ?>
                                    <form method="post" action="modules/duties/controllers/duties_handler.php" style="margin:0;">
                                        <input type="hidden" name="action" value="complete_task">
                                        <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                                        <input type="hidden" name="week" value="<?= duties_h($today->format('Y-m-d')) ?>">
                                        <input type="hidden" name="tab" value="week">
                                        <input type="hidden" name="return_to" value="home">
                                        <button type="submit" class="btn green"><?= $lang_for_widget('ADD_COMPLETE') ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="rc-card duties-home-card">
                <div class="home-section-head">
                    <h3><?= $lang_for_widget('ADD_UPCOMING_SHIFTS') ?></h3>
                </div>
                <?php if ($upcoming_shifts): ?>
                <ul class="duties-upcoming-list">
                    <?php foreach ($upcoming_shifts as $shift): ?>
                        <li>
                            <?= duties_h($format_shift_date($shift['shift_date'])) ?>
                            <?= duties_h($short_time($shift['start_time'])) ?>-<?= duties_h($short_time($shift['end_time'])) ?>
                            <?= !empty($shift['virtual_shift']) ? ' - ' . $lang_for_widget('ADD_REPEATS_WEEKLY') : '' ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                    <div class="alert-box alert-grey duties-home-empty"><?= $lang_for_widget('ADD_NO_UPCOMING_SHIFTS') ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="duties-home-widget-actions">
            <a class="btn" href="duties_rota.php"><?= $lang_for_widget('ADD_VIEW_ROTA') ?></a>
        </div>
    </div>
    <?php
    return trim((string)ob_get_clean());
}
