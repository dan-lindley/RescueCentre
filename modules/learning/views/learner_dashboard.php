<?php
// modules/learning/views/learner_dashboard.php

if (!defined('APP_LOADED')) {
    exit;
}

require_once __DIR__ . '/../controllers/learning_lib.php';
learning_bootstrap($pdo);

if (!learning_require_access()) {
    return;
}

$centre_id = learning_get_centre_id();
$user_id = learning_get_user_id();
$user_role = learning_get_user_role();

try {
    $courses = learning_get_available_learner_courses($pdo, $user_id, $centre_id, $user_role);
    $suites = learning_get_learner_suites($pdo, $user_id, $centre_id, $user_role, $courses, 4);
} catch (Throwable $e) {
    echo '<div class="rc-alert red">' . sanitize_output($e->getMessage()) . '</div>';
    return;
}
?>


<div class="learning-shell rc-stack">
    <div class="content-title">
        <div class="title">
            <div class="txt">
                <h2>My Learning</h2>
                <p>Courses available to you from Rescue Centre and your organisation.</p>
            </div>
        </div>
    </div>

    <?= learning_flash() ?>

    <?php if (!$courses): ?>
        <div class="rc-alert blue">There are no courses available to you yet.</div>
    <?php else: ?>
        <?php if ($suites): ?>
            <div class="rc-panel rc-stack">
                <div class="rc-split-head">
                    <div>
                        <h3>Learning Paths</h3>
                        <p class="rc-muted">Suites group related courses into a guided path.</p>
                    </div>
                    <span class="rc-badge"><?= count($suites) ?> path<?= count($suites) === 1 ? '' : 's' ?></span>
                </div>

                <div class="rc-list">
                    <?php foreach ($suites as $suite): ?>
                        <div class="rc-card learning-suite-card">
                            <div class="learning-suite-head">
                                <div>
                                    <strong><?= sanitize_output($suite['title']) ?></strong>
                                    <?php if (!empty($suite['description'])): ?>
                                        <p class="rc-muted"><?= sanitize_output($suite['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="learning-suite-meta">
                                    <span class="rc-chip blue"><?= (int)$suite['total_courses'] ?> course<?= (int)$suite['total_courses'] === 1 ? '' : 's' ?></span>
                                    <span class="rc-chip"><?= sanitize_output(ucfirst((string)$suite['visibility'])) ?></span>
                                </div>
                            </div>

                            <div class="learning-suite-preview-grid">
                                <?php foreach ($suite['courses'] as $suiteCourse): ?>
                                    <?php
                                        $courseStatus = (string)($suiteCourse['status'] ?? 'not_started');
                                        $courseStatusLabel = learning_get_status_label($courseStatus ?: 'not_started');
                                        $courseTone = learning_status_tone($courseStatus, $suiteCourse['certificate_earned'] ?? null);
                                    ?>
                                    <div class="rc-card learning-learner-course-card">
                                        <div class="learning-learner-card-head">
                                            <div>
                                                <h3><?= sanitize_output($suiteCourse['title']) ?></h3>
                                                <p class="rc-muted learning-card-description"><?= sanitize_output($suiteCourse['description'] ?? '') ?></p>
                                            </div>
                                        </div>
                                        <div class="rc-chip-row">
                                            <span class="rc-chip"><?= (int)$suiteCourse['page_count'] ?> page<?= (int)$suiteCourse['page_count'] === 1 ? '' : 's' ?></span>
                                            <span class="rc-chip">Pass mark <?= (int)$suiteCourse['pass_mark_percent'] ?>%</span>
                                        </div>
                                        <div class="learning-card-footer">
                                            <span class="rc-chip <?= sanitize_output($courseTone) ?>"><?= sanitize_output($courseStatusLabel) ?></span>
                                            <div class="rc-actions learning-learner-actions">
                                                <a class="btn blue" href="learner.php?view=take_course&amp;course_id=<?= (int)$suiteCourse['course_id'] ?>">
                                                    <?= $courseStatus === 'in_progress' ? 'Continue' : 'Start Course' ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if ((int)$suite['total_courses'] > count($suite['courses'])): ?>
                                <p class="rc-muted">Showing 4 of <?= (int)$suite['total_courses'] ?> courses in this path.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="rc-split-head">
            <div>
                <h3>Available Courses</h3>
                <p class="rc-muted">Courses you can start or continue.</p>
            </div>
        </div>

        <div class="learning-learner-grid">
            <?php foreach ($courses as $course): ?>
                <?php
                $status = (string)($course['status'] ?? 'not_started');
                $statusLabel = learning_get_status_label($status ?: 'not_started');
                $tone = learning_status_tone($status, $course['certificate_earned'] ?? null);
                $visibility = !empty($course['is_platform_course']) || ($course['visibility'] ?? '') === 'platform'
                    ? 'Rescue Centre'
                    : ucfirst((string)($course['visibility'] ?? 'Course'));
                ?>
                <div class="rc-card learning-learner-course-card">
                    <div class="learning-learner-card-head">
                        <div>
                            <h3><?= sanitize_output($course['title']) ?></h3>
                            <p class="rc-muted learning-card-description"><?= sanitize_output($course['description'] ?? '') ?></p>
                        </div>
                    </div>
                    <div class="rc-chip-row">
                        <span class="rc-chip blue"><?= sanitize_output($visibility) ?></span>
                        <span class="rc-chip"><?= (int)$course['page_count'] ?> page<?= (int)$course['page_count'] === 1 ? '' : 's' ?></span>
                        <span class="rc-chip">Pass mark <?= (int)$course['pass_mark_percent'] ?>%</span>
                    </div>
                    <div class="learning-card-footer">
                        <span class="rc-chip <?= sanitize_output($tone) ?>"><?= sanitize_output($statusLabel) ?></span>
                        <div class="rc-actions learning-learner-actions">
                            <a class="btn blue" href="learner.php?view=take_course&course_id=<?= (int)$course['course_id'] ?>">
                                <?= $status === 'in_progress' ? 'Continue' : 'Start Course' ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
