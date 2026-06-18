<?php
/**
 * Home dashboard setup tasks.
 *
 * Expects $pdo and $centre_id.
 */

if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo '<div class="rc-alert red"><strong>Setup</strong><br>Database connection not available.</div>';
    return;
}

if (!isset($centre_id) || $centre_id === '') {
    echo '<div class="rc-alert red"><strong>Setup</strong><br>Centre context not available.</div>';
    return;
}

require_once __DIR__ . '/../controllers/tasks/task_registry.php';

$tasks = rc_setup_tasks_registry();
if (!$tasks) {
    return;
}

$activeTaskId = isset($_GET['setup']) ? (string)$_GET['setup'] : '';
$activeTask = $activeTaskId ? rc_setup_task_get($activeTaskId) : null;
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['rc_task_action'] ?? '') === 'skip') {
    $skipId = (string)($_POST['task_id'] ?? '');
    $task = $skipId !== '' ? rc_setup_task_get($skipId) : null;

    if (!$task) {
        $flash = ['type' => 'red', 'msg' => 'Unknown task.'];
    } elseif (rc_setup_task_skip($task, $pdo, $centre_id)) {
        $flash = ['type' => 'green', 'msg' => 'Task skipped. Default settings created.'];
    } else {
        $flash = ['type' => 'red', 'msg' => 'Skip failed (task not implemented yet or schema mismatch).'];
    }
}

$doneCount = 0;
$taskStates = [];
foreach ($tasks as $task) {
    $done = rc_setup_task_is_done($task, $pdo, $centre_id);
    $taskStates[$task['id']] = $done;
    if ($done) {
        $doneCount++;
    }
}
?>

<div class="home-setup-tasks">
    <div class="home-setup-progress rc-muted">
        <?= (int)$doneCount ?> / <?= count($tasks) ?> complete
    </div>

    <?php if ($flash): ?>
        <div class="rc-alert <?= $flash['type'] === 'green' ? 'green' : 'red' ?>">
            <?= htmlspecialchars($flash['msg'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="rc-list home-setup-task-list">
        <?php foreach ($tasks as $task): ?>
            <?php
                $id = (string)$task['id'];
                $title = (string)($task['title'] ?? $id);
                $description = (string)($task['description'] ?? '');
                $done = !empty($taskStates[$id]);
                $isActive = $activeTaskId === $id;
                $actionUrl = (string)($task['action_url'] ?? ('?setup=' . urlencode($id)));
            ?>

            <?php if ($done): ?>
                <div class="rc-card home-setup-task home-setup-task-complete">
                    <span class="home-setup-check" aria-hidden="true">&#10003;</span>
                    <strong><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            <?php else: ?>
                <div class="rc-card home-setup-task home-setup-task-incomplete<?= $isActive ? ' is-active' : '' ?>">
                    <strong><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php if ($description !== ''): ?>
                        <p class="rc-muted"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <div class="rc-actions home-setup-task-actions">
                        <a class="btn" href="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>">Get started</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php if ($activeTask): ?>
        <div class="home-setup-active-task">
            <div class="rc-split-head">
                <strong><?= htmlspecialchars((string)($activeTask['title'] ?? 'Setup'), ENT_QUOTES, 'UTF-8') ?></strong>
                <a href="?" class="btn grey">Close</a>
            </div>
            <?php
                $module = $activeTask['module'] ?? '';
                if ($module && is_string($module) && file_exists($module)) {
                    include $module;
                } else {
                    echo '<div class="rc-alert red"><strong>Module missing</strong><br>This task module file does not exist yet.</div>';
                }
            ?>
        </div>
    <?php endif; ?>
</div>
