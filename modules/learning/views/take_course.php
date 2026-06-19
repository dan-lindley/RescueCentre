<?php
// modules/learning/views/take_course.php

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
    $stmt = $pdo->prepare("SELECT * FROM rescue_learning_courses WHERE course_id = ? AND is_active = 1");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$course) {
        throw new Exception('Course not found');
    }

    $pages = learning_get_course_pages($pdo, $course_id);
    $assessment = learning_get_course_assessment($pdo, $course_id);
    $assessmentQuestions = $assessment ? learning_get_assessment_questions_with_answers($pdo, (int)$assessment['assessment_id']) : [];

    $stmt = $pdo->prepare("SELECT * FROM rescue_learning_user_courses WHERE user_id = ? AND course_id = ? AND centre_id = ?");
    $stmt->execute([$user_id, $course_id, $centre_id]);
    $user_course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_course) {
        learning_enroll_user($pdo, $centre_id, $user_id, ['course_id' => $course_id]);
        $stmt->execute([$user_id, $course_id, $centre_id]);
        $user_course = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Throwable $e) {
    echo '<div class="rc-alert red">' . sanitize_output($e->getMessage()) . '</div>';
    return;
}

$viewingAssessment = !empty($_GET['assessment']);
$current_page_id = $viewingAssessment ? 0 : (int)($_GET['page_id'] ?? ($user_course['current_page_id'] ?? ($pages[0]['page_id'] ?? 0)));
$current_page = null;
foreach ($pages as $page) {
    if ((int)$page['page_id'] === $current_page_id) {
        $current_page = $page;
        break;
    }
}
if (!$current_page && $pages) {
    $current_page = $pages[0];
    $current_page_id = (int)$current_page['page_id'];
}

$pageIds = array_map('intval', array_column($pages, 'page_id'));
$current_index = $current_page ? array_search((int)$current_page['page_id'], $pageIds, true) : 0;
$current_index = $current_index === false ? 0 : (int)$current_index;
$pageCount = count($pages);
$onLastPage = $pageCount > 0 && $current_index === ($pageCount - 1);
$totalSteps = $pageCount + ($assessment ? 1 : 0);
$currentStep = $viewingAssessment ? $totalSteps : ($current_index + 1);
$progress = $totalSteps > 0 ? (int)round(($currentStep / $totalSteps) * 100) : 0;
$attemptCount = (int)($user_course['attempt_count'] ?? 0);
$maxAttempts = max(1, (int)($course['max_attempts'] ?? 1));
$canAttemptAssessment = $attemptCount < $maxAttempts;
?>

<div class="learning-shell rc-stack">
    <div class="content-title">
        <div class="title">
            <div class="txt">
                <h2><?= sanitize_output($course['title']) ?></h2>
                <p><?= sanitize_output($course['description'] ?? '') ?></p>
            </div>
        </div>
        <div class="btns">
            <a class="btn grey" href="learner.php">My Learning</a>
        </div>
    </div>

    <?= learning_flash() ?>

    <?php if (!$pages): ?>
        <div class="rc-alert amber">No pages have been added to this course yet.</div>
    <?php else: ?>
        <div class="learning-course-layout">
            <div class="rc-panel learning-course-nav">
                <div class="rc-split-head">
                    <div>
                        <h3>Course Content</h3>
                        <p class="rc-muted"><?= $progress ?>% through this course</p>
                    </div>
                    <span class="rc-badge"><?= $currentStep ?> / <?= $totalSteps ?></span>
                </div>
                <div class="rc-progress">
                    <div class="rc-progress-fill green" style="width: <?= $progress ?>%;"></div>
                </div>

                <div class="learning-page-toolbar">
                    <?php foreach ($pages as $idx => $page): ?>
                        <a class="learning-page-link <?= (int)$page['page_id'] === $current_page_id ? 'is-active' : '' ?>"
                           href="learner.php?view=take_course&amp;course_id=<?= (int)$course_id ?>&amp;page_id=<?= (int)$page['page_id'] ?>">
                            <strong>Page <?= $idx + 1 ?></strong>
                            <span><?= sanitize_output($page['title']) ?></span>
                        </a>
                    <?php endforeach; ?>

                    <?php if ($assessment): ?>
                        <a class="learning-page-link <?= $viewingAssessment ? 'is-active is-assessment-ready' : '' ?>"
                           href="learner.php?view=take_course&amp;course_id=<?= (int)$course_id ?>&amp;assessment=1">
                            <strong>Assessment</strong>
                            <span><?= sanitize_output($assessment['title']) ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <main class="rc-panel rc-stack">
                <?php if ($viewingAssessment): ?>
                    <div class="learning-assessment-panel rc-card rc-card-muted rc-stack">
                        <?php if (!$assessment): ?>
                            <div class="rc-alert grey">There is no assessment for this course. You can complete it now.</div>
                            <form method="post" action="modules/learning/controllers/learning_handler.php" class="xform">
                                <input type="hidden" name="action" value="update_progress">
                                <input type="hidden" name="return_context" value="learner">
                                <input type="hidden" name="return_view" value="course_results">
                                <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                                <input type="hidden" name="user_course_id" value="<?= (int)($user_course['user_course_id'] ?? 0) ?>">
                                <input type="hidden" name="current_page_id" value="<?= (int)($pages[$pageCount - 1]['page_id'] ?? 0) ?>">
                                <input type="hidden" name="progress_percent" value="100">
                                <div class="xform-actions">
                                    <button type="submit" class="btn green">Complete Course</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="rc-split-head">
                                <div>
                                    <h3>Assessment: <?= sanitize_output($assessment['title']) ?></h3>
                                    <p class="rc-muted">Pass mark: <?= (int)$course['pass_mark_percent'] ?>%. Attempts: <?= $attemptCount ?> / <?= $maxAttempts ?>.</p>
                                </div>
                                <span class="rc-chip blue">Assessment</span>
                            </div>

                            <?php if (!empty($assessment['instructions'])): ?>
                                <div class="rc-alert blue"><?= nl2br(sanitize_output($assessment['instructions'])) ?></div>
                            <?php endif; ?>

                            <?php if (!$assessmentQuestions): ?>
                                <div class="rc-alert amber">This assessment has been created, but no questions have been added yet.</div>
                            <?php elseif (!$canAttemptAssessment): ?>
                                <div class="rc-alert red">You have used all available attempts for this assessment.</div>
                                <div class="rc-actions">
                                    <a class="btn grey" href="learner.php?view=course_results&amp;course_id=<?= (int)$course_id ?>">View Results</a>
                                </div>
                            <?php else: ?>
                                <form method="post" action="modules/learning/controllers/learning_handler.php" class="xform learning-assessment-form">
                                    <input type="hidden" name="action" value="submit_assessment">
                                    <input type="hidden" name="return_context" value="learner">
                                    <input type="hidden" name="return_view" value="course_results">
                                    <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                                    <input type="hidden" name="user_course_id" value="<?= (int)($user_course['user_course_id'] ?? 0) ?>">
                                    <input type="hidden" name="page_id" value="<?= (int)($pages[$pageCount - 1]['page_id'] ?? 0) ?>">
                                    <input type="hidden" name="assessment_id" value="<?= (int)$assessment['assessment_id'] ?>">

                                    <div class="rc-stack">
                                        <?php foreach ($assessmentQuestions as $idx => $question): ?>
                                            <div class="rc-card learning-question-card">
                                                <div class="rc-split-head">
                                                    <h4><?= $idx + 1 ?>. <?= sanitize_output($question['question_text']) ?></h4>
                                                    <span class="rc-chip grey"><?= sanitize_output(str_replace('_', ' ', $question['question_type'])) ?></span>
                                                </div>

                                                <?php if (empty($question['answers'])): ?>
                                                    <div class="rc-alert amber">No answers have been added to this question.</div>
                                                <?php else: ?>
                                                    <div class="learning-answer-list">
                                                        <?php foreach ($question['answers'] as $answer): ?>
                                                            <?php $inputId = 'learning-answer-' . (int)$question['question_id'] . '-' . (int)$answer['answer_id']; ?>
                                                            <label class="learning-answer-option" for="<?= sanitize_output($inputId) ?>">
                                                                <input type="<?= $question['question_type'] === 'multi_choice' ? 'checkbox' : 'radio' ?>"
                                                                       id="<?= sanitize_output($inputId) ?>"
                                                                       name="answer[<?= (int)$question['question_id'] ?>][]"
                                                                       value="<?= (int)$answer['answer_id'] ?>"
                                                                       <?= $question['question_type'] === 'multi_choice' ? '' : 'required' ?>>
                                                                <span><?= sanitize_output($answer['answer_text']) ?></span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>

                                        <div class="xform-actions">
                                            <button type="submit" class="btn blue">Submit Assessment</button>
                                        </div>
                                    </div>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="rc-split-head">
                        <div>
                            <?php if ($pageCount > 0): ?>
                                <a class="btn grey" href="learner.php?view=take_course&amp;course_id=<?= (int)$course_id ?>&amp;page_id=<?= (int)$pages[$pageCount - 1]['page_id'] ?>">Previous</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php elseif ($current_page): ?>
                    <div class="rc-split-head">
                        <div>
                            <h3><?= sanitize_output($current_page['title']) ?></h3>
                            <p class="rc-muted"><?= sanitize_output($current_page['page_type']) ?></p>
                        </div>
                        <span class="rc-badge"><?= $current_index + 1 ?> / <?= $pageCount ?></span>
                    </div>

                    <?php if (!empty($current_page['media_url'])): ?>
                        <div class="rc-alert blue">
                            <strong>Resource:</strong>
                            <a href="<?= sanitize_output($current_page['media_url']) ?>" target="_blank" rel="noopener">Open linked material</a>
                        </div>
                    <?php endif; ?>

                    <div class="learning-content">
                        <?= $current_page['content'] ?>
                    </div>

                    <div class="rc-split-head">
                        <div>
                            <?php if ($current_index > 0): ?>
                                <a class="btn grey" href="learner.php?view=take_course&amp;course_id=<?= (int)$course_id ?>&amp;page_id=<?= (int)$pages[$current_index - 1]['page_id'] ?>">Previous</a>
                            <?php endif; ?>
                        </div>
                        <div class="rc-actions">
                            <?php if ($current_index < $pageCount - 1): ?>
                                <a class="btn blue" href="learner.php?view=take_course&amp;course_id=<?= (int)$course_id ?>&amp;page_id=<?= (int)$pages[$current_index + 1]['page_id'] ?>">Next</a>
                            <?php else: ?>
                                <?php if ($assessment): ?>
                                    <a class="btn blue" href="learner.php?view=take_course&amp;course_id=<?= (int)$course_id ?>&amp;assessment=1">Go to Assessment</a>
                                <?php else: ?>
                                    <form method="post" action="modules/learning/controllers/learning_handler.php" class="xform">
                                        <input type="hidden" name="action" value="update_progress">
                                        <input type="hidden" name="return_context" value="learner">
                                        <input type="hidden" name="return_view" value="course_results">
                                        <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                                        <input type="hidden" name="user_course_id" value="<?= (int)($user_course['user_course_id'] ?? 0) ?>">
                                        <input type="hidden" name="current_page_id" value="<?= (int)$current_page['page_id'] ?>">
                                        <input type="hidden" name="progress_percent" value="100">
                                        <button type="submit" class="btn green">Complete Course</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    <?php endif; ?>
</div>
