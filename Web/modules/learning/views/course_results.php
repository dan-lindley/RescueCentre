<?php
// modules/learning/views/course_results.php

if (!defined('APP_LOADED')) {
    exit;
}

$course_id = (int)($_GET['course_id'] ?? 0);
require_once __DIR__ . '/../controllers/learning_lib.php';
learning_bootstrap($pdo);

if (!learning_require_access()) {
    return;
}

$centre_id = learning_get_centre_id();
$user_id = learning_get_user_id();

try {
    $stmt = $pdo->prepare("SELECT * FROM rescue_learning_courses WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$course) {
        throw new Exception('Course not found');
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM rescue_learning_user_courses
        WHERE user_id = ? AND course_id = ? AND centre_id = ?
    ");
    $stmt->execute([$user_id, $course_id, $centre_id]);
    $user_course = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user_course) {
        throw new Exception('Course enrolment not found');
    }

    $assessment = learning_get_course_assessment($pdo, $course_id);
} catch (Throwable $e) {
    echo '<div class="rc-alert red">' . sanitize_output($e->getMessage()) . '</div>';
    return;
}

$score = (int)($user_course['assessment_score'] ?? 0);
$score = max(0, min(100, $score));
$passMark = max(0, min(100, (int)($course['pass_mark_percent'] ?? 70)));
$maxAttempts = max(1, (int)($course['max_attempts'] ?? 1));
$attempts = (int)($user_course['attempt_count'] ?? 0);
$passed = (string)$user_course['assessment_status'] === 'passed' || ((string)$user_course['status'] === 'completed' && $score >= (int)$course['pass_mark_percent']);
$statusClass = $passed ? 'ok' : ((string)$user_course['status'] === 'in_progress' ? 'mid' : 'bad');
$statusText = $passed ? 'Passed' : ((string)$user_course['status'] === 'in_progress' ? 'In Progress' : 'Not Passed');
$needleAngle = -90 + ($score * 1.8);
$result = $_SESSION['assessment_result'] ?? [];
unset($_SESSION['assessment_result']);
$questionTotal = (int)($result['total'] ?? 0);
$questionCorrect = (int)($result['correct'] ?? 0);
$passArcPercent = max(0, min(100, $passMark));
?>

<div class="learning-shell rc-stack">
    <div class="content-title">
        <div class="title">
            <div class="txt">
                <h2><?= sanitize_output($course['title']) ?></h2>
                <p>Course results and certificate status.</p>
            </div>
        </div>
        <div class="btns">
            <a href="learner.php" class="btn grey">Back to Learning</a>
        </div>
    </div>

    <?= learning_flash() ?>

    <div class="learning-results-hero rc-panel rc-stack">
        <div class="rc-split-head">
            <div>
                <h3><?= sanitize_output($statusText) ?></h3>
                <p class="rc-muted">
                    <?= !empty($user_course['completed_at']) ? 'Completed on ' . sanitize_output(date('j M Y', strtotime((string)$user_course['completed_at']))) : 'Assessment attempt recorded.' ?>
                </p>
            </div>
            <span class="rc-badge <?= $statusClass ?>"><?= sanitize_output($statusText) ?></span>
        </div>

        <div class="learning-results-grid">
            <div class="learning-score-dial-card rc-card rc-card-muted">
                <div class="learning-score-dial"
                     style="--score: <?= $score ?>; --pass: <?= $passArcPercent ?>%; --needle: <?= $needleAngle ?>deg;">
                    <div class="learning-score-arc"></div>
                    <div class="learning-score-needle"></div>
                    <div class="learning-score-hub"></div>
                    <div class="learning-score-value">
                        <strong><?= $score ?>%</strong>
                        <span>Your score</span>
                    </div>
                </div>
                <div class="learning-dial-scale">
                    <span>0</span>
                    <span>Pass <?= $passMark ?>%</span>
                    <span>100</span>
                </div>
            </div>

            <div class="learning-results-summary rc-stack">
                <div class="rc-stat-grid">
                    <div class="rc-stat">
                        <strong><?= $score ?>%</strong>
                        <span>Your Score</span>
                    </div>
                    <div class="rc-stat">
                        <strong><?= $passMark ?>%</strong>
                        <span>Pass Mark</span>
                    </div>
                    <div class="rc-stat">
                        <strong><?= $attempts ?> / <?= $maxAttempts ?></strong>
                        <span>Attempts</span>
                    </div>
                    <div class="rc-stat">
                        <strong><?= (int)$user_course['progress_percent'] ?>%</strong>
                        <span>Progress</span>
                    </div>
                </div>

                <?php if ($questionTotal > 0): ?>
                    <div class="rc-card">
                        <h3>Question Score</h3>
                        <p class="rc-muted"><?= $questionCorrect ?> of <?= $questionTotal ?> questions answered correctly.</p>
                    </div>
                <?php endif; ?>

                <?php if ($passed && !empty($user_course['certificate_earned'])): ?>
                    <div class="rc-alert green">
                        <strong>Congratulations.</strong> You have earned a certificate for this course.
                    </div>
                <?php elseif (!$passed): ?>
                    <div class="rc-alert amber">
                        <strong>Not quite there.</strong> Review the course material and retry if attempts remain.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($user_course['feedback'])): ?>
        <div class="rc-panel">
            <h3>Feedback</h3>
            <p><?= nl2br(sanitize_output($user_course['feedback'])) ?></p>
        </div>
    <?php endif; ?>

    <div class="rc-actions">
        <a href="learner.php" class="btn grey">Back to Learning</a>
        <?php if (!$passed && $attempts < $maxAttempts): ?>
            <form method="post" action="modules/learning/controllers/learning_handler.php" style="margin:0;">
                <input type="hidden" name="action" value="retry_course">
                <input type="hidden" name="return_context" value="learner">
                <input type="hidden" name="return_view" value="take_course">
                <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                <button type="submit" class="btn blue">Retry Course</button>
            </form>
        <?php endif; ?>
    </div>
</div>
