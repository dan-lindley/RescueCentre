<?php
// modules/learning/views/home_widget.php

if (!isset($pdo) || !$pdo instanceof PDO) {
    return;
}

require_once __DIR__ . '/../controllers/learning_lib.php';

try {
    $learningCentreId = (int)($centre_id ?? $_SESSION['centre_id'] ?? $_SESSION['rescue_id'] ?? 0);
    $learningUserId = learning_get_user_id();

    if ($learningCentreId <= 0 || $learningUserId <= 0) {
        return;
    }

    $stmt = $pdo->prepare("
        SELECT
            c.*,
            COALESCE(uc.status, 'not_started') AS user_status,
            COALESCE(uc.certificate_earned, 0) AS certificate_earned
        FROM rescue_learning_courses c
        LEFT JOIN rescue_learning_user_courses uc
            ON uc.course_id = c.course_id
            AND uc.user_id = ?
            AND uc.centre_id = ?
        WHERE c.is_active = 1
        AND (
            c.visibility IN ('centre', 'global', 'platform')
            OR c.is_platform_course = 1
            OR (
                c.visibility = 'private'
                AND c.created_by_user_id = ?
            )
        )
        AND (
            c.owner_centre_id = ?
            OR c.visibility IN ('global', 'platform')
            OR c.is_platform_course = 1
            OR c.created_by_user_id = ?
        )
        ORDER BY
            CASE
                WHEN c.visibility = 'private' THEN 1
                WHEN c.is_platform_course = 1 OR c.visibility = 'platform' THEN 2
                WHEN c.visibility = 'centre' THEN 3
                WHEN c.visibility = 'global' THEN 4
                ELSE 5
            END,
            c.title ASC
        LIMIT 4
    ");

    $stmt->execute([
        $learningUserId,
        $learningCentreId,
        $learningUserId,
        $learningCentreId,
        $learningUserId,
    ]);

    $learningCourses = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

} catch (Throwable $e) {
    error_log('learning home widget: ' . $e->getMessage());
    $learningCourses = [];
}
?>

<div class="rc-panel rc-stack">
    <div class="rc-split-head home-section-head">
        <div>
            <h3>My Learning</h3>
            <p class="rc-muted">Assigned learning, courses, and progress.</p>
        </div>

        <a class="btn small" href="learner.php">View All</a>
    </div>

    <?php if (!$learningCourses): ?>

        <div class="rc-alert blue">
            No courses available.
        </div>

    <?php else: ?>

        <div class="rc-list">
            <?php foreach ($learningCourses as $learningCourse): ?>
                <?php
                $status = (string)($learningCourse['user_status'] ?? 'not_started');
                $statusLabel = function_exists('learning_get_status_label')
                    ? learning_get_status_label($status)
                    : ucfirst(str_replace('_', ' ', $status));

                $tone = 'blue';
                if (in_array($status, ['completed', 'passed'], true)) {
                    $tone = 'good';
                } elseif ($status === 'failed') {
                    $tone = 'warn';
                }

                $rawVisibility = (string)($learningCourse['visibility'] ?? '');
                $isPlatform = !empty($learningCourse['is_platform_course']) || $rawVisibility === 'platform';
                $isPreview = $rawVisibility === 'private';

                if ($isPlatform) {
                    $visibility = 'Rescue Centre';
                } elseif ($rawVisibility === 'private') {
                    $visibility = 'Private';
                } elseif ($rawVisibility !== '') {
                    $visibility = ucfirst($rawVisibility);
                } else {
                    $visibility = 'Course';
                }

                $description = trim((string)($learningCourse['description'] ?? ''));
                $description = $description !== ''
                    ? mb_substr($description, 0, 120)
                    : 'Open this course to continue your learning.';
                ?>

                <a class="rc-item home-link-item" href="learner.php?view=take_course&amp;course_id=<?= (int)$learningCourse['course_id'] ?>">
                    <div class="rc-item-main">
                        <strong><?= sanitize_output($learningCourse['title']) ?></strong>
                        <small><?= sanitize_output($description) ?></small>
                    </div>

                    <div class="rc-actions">
                        <span class="rc-chip blue"><?= sanitize_output($visibility) ?></span>

                        <?php if ($isPreview): ?>
                            <span class="rc-chip warn">Preview</span>
                        <?php endif; ?>

                        <span class="rc-chip <?= sanitize_output($tone) ?>"><?= sanitize_output($statusLabel) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>