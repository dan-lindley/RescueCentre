<?php
/**
 * Learning Module Library
 * Utility functions for e-learning module
 */

/**
 * Get current centre ID
 */
function learning_get_centre_id() {
    return (int)($GLOBALS['centre_id'] ?? $_SESSION['centre_id'] ?? $_SESSION['rescue_id'] ?? 0);
}

/**
 * Get current user ID
 */
function learning_get_user_id() {
    return (int)($GLOBALS['user_id'] ?? $_SESSION['user_id'] ?? $_SESSION['account_id'] ?? 0);
}

/**
 * Get current user role
 */
function learning_get_user_role() {
    return (string)($GLOBALS['user_role'] ?? $_SESSION['user_role'] ?? $_SESSION['account_role'] ?? $_SESSION['role'] ?? '');
}

/**
 * Check if user has admin permissions
 */
function learning_has_admin_permission($user_role = null) {
    if (function_exists('can') && can('module.learning.admin')) {
        return true;
    }
    $role = strtolower((string)($user_role ?? learning_get_user_role()));
    $admin_roles = ['admin', 'learning_admin', 'centre_manager', 'owner', 'manager'];
    return in_array($role, $admin_roles, true);
}

function learning_is_platform_admin($user_role = null): bool
{
    $role = strtolower((string)($user_role ?? learning_get_user_role()));
    return in_array($role, ['admin', 'super_admin', 'platform_admin'], true);
}

/**
 * Sanitize output
 */
function sanitize_output($output) {
    return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect with message
 */
function learning_redirect($params = []) {
    $url = '../../../module.php?module=learning';
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $url .= '&' . urlencode($key) . '=' . urlencode($value);
        }
    }
    header('Location: ' . $url);
    exit;
}

function learning_view_url(array $params = []): string
{
    $url = 'module.php?module=learning';
    $query = http_build_query($params);
    return $url . ($query ? '&' . $query : '');
}

function learning_handler_url(): string
{
    return 'modules/learning/controllers/learning_handler.php';
}

function learning_register_permissions(): void
{
    if (function_exists('registerPermission')) {
        registerPermission('module.learning', 'Learning', 'module');
        registerPermission('module.learning.admin', 'Learning Management', 'module');
    }
}

function learning_can_access(): bool
{
    learning_register_permissions();
    return function_exists('can') ? can('module.learning') : true;
}

function learning_flash(?string $type = null): string
{
    $html = '';
    $messages = [
        'success' => 'green',
        'error' => 'red',
        'info' => 'blue',
    ];

    foreach ($messages as $key => $colour) {
        if ($type !== null && $type !== $key) {
            continue;
        }
        if (empty($_SESSION[$key])) {
            continue;
        }
        $html .= '<div class="rc-alert ' . $colour . '">' . sanitize_output($_SESSION[$key]) . '</div>';
        unset($_SESSION[$key]);
    }

    return $html;
}

/**
 * Create database schema if not exists
 */
function learning_ensure_schema($pdo) {
    // Check if main course table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'rescue_learning_courses'");
    if ($stmt->rowCount() > 0) {
        return; // Schema already exists
    }

    // Create schema from the module-local SQL file.
    $schema_file = __DIR__ . '/../INSTALL.SQL';
    if (file_exists($schema_file)) {
        $sql = file_get_contents($schema_file);
        // Execute schema creation
        $pdo->exec($sql);
    }
}

function learning_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function learning_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function learning_column_type(PDO $pdo, string $table, string $column): string
{
    $stmt = $pdo->prepare("
        SELECT COLUMN_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ");
    $stmt->execute([$table, $column]);
    return (string)($stmt->fetchColumn() ?: '');
}

function learning_ensure_question_type_schema(PDO $pdo): void
{
    if (!learning_table_exists($pdo, 'rescue_learning_questions')) {
        return;
    }

    if (strpos(learning_column_type($pdo, 'rescue_learning_questions', 'question_type'), 'yes_no_unknown') !== false) {
        return;
    }

    try {
        $pdo->exec("ALTER TABLE rescue_learning_questions MODIFY question_type ENUM('single_choice','multi_choice','true_false','yes_no','yes_no_unknown') DEFAULT 'single_choice'");
    } catch (Throwable $e) {
        error_log('learning_ensure_question_type_schema failed: ' . $e->getMessage());
    }
}

/**
 * Create a course
 */
function learning_create_course($pdo, $centre_id, $user_id, $data) {
    try {
        $sql = "INSERT INTO rescue_learning_courses
                (owner_centre_id, created_by_user_id, title, description, pass_mark_percent, max_attempts, visibility, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $centre_id,
            $user_id,
            $data['title'] ?? '',
            $data['description'] ?? '',
            $data['pass_mark_percent'] ?? 70,
            $data['max_attempts'] ?? 3,
            $data['visibility'] ?? 'private'
        ]);

        if ($result) {
            $course_id = $pdo->lastInsertId();
            $_SESSION['success'] = 'Course created successfully. Add content to the course below.';
            $_SESSION['new_course_id'] = $course_id;
            return $course_id;
        }
    } catch (Exception $e) {
        error_log("Error creating course: " . $e->getMessage());
        $_SESSION['error'] = 'Error creating course';
    }
    return false;
}

/**
 * Update a course
 */
function learning_update_course($pdo, $centre_id, $data) {
    try {
        $course_id = $data['course_id'] ?? 0;
        
        // Verify ownership
        $check = $pdo->prepare("SELECT owner_centre_id FROM rescue_learning_courses WHERE course_id = ?");
        $check->execute([$course_id]);
        $course = $check->fetch(PDO::FETCH_ASSOC);
        
        if (!$course || $course['owner_centre_id'] != $centre_id) {
            $_SESSION['error'] = 'No permission to update course';
            return false;
        }

        $sql = "UPDATE rescue_learning_courses SET 
                title = ?, description = ?, pass_mark_percent = ?, max_attempts = ?, visibility = ?, is_active = ?, updated_at = NOW()
                WHERE course_id = ?";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $data['title'] ?? '',
            $data['description'] ?? '',
            $data['pass_mark_percent'] ?? 70,
            $data['max_attempts'] ?? 3,
            $data['visibility'] ?? 'private',
            !empty($data['is_active']) ? 1 : 0,
            $course_id
        ]);

        if ($result) {
            $_SESSION['success'] = 'Course updated successfully';
        }
        return $result;
    } catch (Exception $e) {
        error_log("Error updating course: " . $e->getMessage());
        $_SESSION['error'] = 'Error updating course';
    }
    return false;
}

function learning_set_course_active(PDO $pdo, int $centre_id, array $data, bool $is_active): bool
{
    $course_id = (int)($data['course_id'] ?? 0);
    if ($course_id <= 0) {
        $_SESSION['error'] = 'Could not update course';
        return false;
    }

    if (!learning_course_owned_by_centre($pdo, $course_id, $centre_id)) {
        $_SESSION['error'] = 'No permission to update course';
        return false;
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_learning_courses
        SET is_active = ?,
            updated_at = NOW()
        WHERE course_id = ?
          AND owner_centre_id = ?
    ");
    $ok = $stmt->execute([$is_active ? 1 : 0, $course_id, $centre_id]);
    $_SESSION[$ok ? 'success' : 'error'] = $ok
        ? ($is_active ? 'Course restored' : 'Course archived')
        : 'Could not update course';
    return $ok;
}

function learning_delete_course(PDO $pdo, int $centre_id, array $data): bool
{
    $course_id = (int)($data['course_id'] ?? 0);
    if ($course_id <= 0) {
        $_SESSION['error'] = 'Could not delete course';
        return false;
    }

    $check = $pdo->prepare("
        SELECT course_id, is_active
        FROM rescue_learning_courses
        WHERE course_id = ?
          AND owner_centre_id = ?
        LIMIT 1
    ");
    $check->execute([$course_id, $centre_id]);
    $course = $check->fetch(PDO::FETCH_ASSOC);
    if (!$course) {
        $_SESSION['error'] = 'No permission to delete course';
        return false;
    }
    if (!empty($course['is_active'])) {
        $_SESSION['error'] = 'Archive the course before deleting it permanently';
        return false;
    }

    $deleteStep = 'start';
    try {
        $pdo->beginTransaction();

        $deleteStep = 'inspect related tables';
        $hasUserAnswers = learning_table_columns($pdo, 'rescue_learning_user_answers');
        $hasUserCourses = learning_table_columns($pdo, 'rescue_learning_user_courses');
        $hasQuestions = learning_table_columns($pdo, 'rescue_learning_questions');
        $hasAssessments = learning_table_columns($pdo, 'rescue_learning_assessments');
        $hasPageProgress = learning_table_columns($pdo, 'rescue_learning_page_progress');
        $hasPages = learning_table_columns($pdo, 'rescue_learning_pages');
        $hasCertificates = learning_table_columns($pdo, 'rescue_learning_certificates');
        $hasSuiteCourses = learning_table_columns($pdo, 'rescue_learning_suite_courses');
        $hasAnswers = learning_table_columns($pdo, 'rescue_learning_answers');
        $hasQuestionCorrectOptions = learning_table_columns($pdo, 'rescue_learning_question_correct_options');

        if ($hasUserAnswers && $hasUserCourses && $hasQuestions && $hasAssessments) {
            $deleteStep = 'delete user assessment answers';
            $pdo->prepare("
                DELETE ua
                FROM rescue_learning_user_answers ua
                LEFT JOIN rescue_learning_user_courses uc ON uc.user_course_id = ua.user_course_id
                LEFT JOIN rescue_learning_questions q ON q.question_id = ua.question_id
                LEFT JOIN rescue_learning_assessments a ON a.assessment_id = q.assessment_id
                WHERE uc.course_id = ?
                   OR a.course_id = ?
            ")->execute([$course_id, $course_id]);
        }

        if ($hasPageProgress && $hasUserCourses && $hasPages) {
            $deleteStep = 'delete page progress';
            $pdo->prepare("
                DELETE pp
                FROM rescue_learning_page_progress pp
                LEFT JOIN rescue_learning_user_courses uc ON uc.user_course_id = pp.user_course_id
                LEFT JOIN rescue_learning_pages p ON p.page_id = pp.page_id
                WHERE uc.course_id = ?
                   OR p.course_id = ?
            ")->execute([$course_id, $course_id]);
        }

        if ($hasCertificates && !empty($hasCertificates['course_id'])) {
            $deleteStep = 'delete certificates';
            $pdo->prepare("DELETE FROM rescue_learning_certificates WHERE course_id = ?")->execute([$course_id]);
        }
        if ($hasUserCourses && !empty($hasUserCourses['course_id'])) {
            $deleteStep = 'delete learner course progress';
            $pdo->prepare("DELETE FROM rescue_learning_user_courses WHERE course_id = ?")->execute([$course_id]);
        }
        if ($hasSuiteCourses && !empty($hasSuiteCourses['course_id'])) {
            $deleteStep = 'delete suite course links';
            $pdo->prepare("DELETE FROM rescue_learning_suite_courses WHERE course_id = ?")->execute([$course_id]);
        }

        $assignmentColumns = learning_table_columns($pdo, 'rescue_learning_assignments');
        if ($assignmentColumns) {
            $deleteStep = 'delete assignments';
            if (!empty($assignmentColumns['course_id']) && !empty($assignmentColumns['assignable_type']) && !empty($assignmentColumns['assignable_id'])) {
                $pdo->prepare("
                    DELETE FROM rescue_learning_assignments
                    WHERE course_id = ?
                       OR (assignable_type = 'course' AND assignable_id = ?)
                ")->execute([$course_id, $course_id]);
            } elseif (!empty($assignmentColumns['assignable_type']) && !empty($assignmentColumns['assignable_id'])) {
                $pdo->prepare("
                    DELETE FROM rescue_learning_assignments
                    WHERE assignable_type = 'course'
                      AND assignable_id = ?
                ")->execute([$course_id]);
            } elseif (!empty($assignmentColumns['course_id'])) {
                $pdo->prepare("DELETE FROM rescue_learning_assignments WHERE course_id = ?")->execute([$course_id]);
            }
        }

        if ($hasQuestionCorrectOptions && $hasQuestions && $hasAssessments && !empty($hasQuestionCorrectOptions['question_id'])) {
            $deleteStep = 'delete question correct options';
            $pdo->prepare("
                DELETE qco
                FROM rescue_learning_question_correct_options qco
                INNER JOIN rescue_learning_questions q ON q.question_id = qco.question_id
                INNER JOIN rescue_learning_assessments a ON a.assessment_id = q.assessment_id
                WHERE a.course_id = ?
            ")->execute([$course_id]);
        }

        if ($hasAnswers && $hasQuestions && $hasAssessments) {
            $deleteStep = 'delete answers';
            $pdo->prepare("
                DELETE ans
                FROM rescue_learning_answers ans
                INNER JOIN rescue_learning_questions q ON q.question_id = ans.question_id
                INNER JOIN rescue_learning_assessments a ON a.assessment_id = q.assessment_id
                WHERE a.course_id = ?
            ")->execute([$course_id]);
        }
        if ($hasQuestions && $hasAssessments) {
            $deleteStep = 'delete questions';
            $pdo->prepare("
                DELETE q
                FROM rescue_learning_questions q
                INNER JOIN rescue_learning_assessments a ON a.assessment_id = q.assessment_id
                WHERE a.course_id = ?
            ")->execute([$course_id]);
        }
        if ($hasAssessments && !empty($hasAssessments['course_id'])) {
            $deleteStep = 'delete assessment';
            $pdo->prepare("DELETE FROM rescue_learning_assessments WHERE course_id = ?")->execute([$course_id]);
        }
        if ($hasPages && !empty($hasPages['course_id'])) {
            $deleteStep = 'delete pages';
            $pdo->prepare("DELETE FROM rescue_learning_pages WHERE course_id = ?")->execute([$course_id]);
        }

        $deleteStep = 'delete course';
        $delete = $pdo->prepare("DELETE FROM rescue_learning_courses WHERE course_id = ? AND owner_centre_id = ? AND is_active = 0");
        $delete->execute([$course_id, $centre_id]);
        if ($delete->rowCount() < 1) {
            throw new RuntimeException('Archived course row was not deleted');
        }

        $pdo->commit();
        $_SESSION['success'] = 'Course permanently deleted';
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('learning_delete_course failed at ' . $deleteStep . ': ' . $e->getMessage());
        $_SESSION['error'] = 'Could not delete course';
        return false;
    }
}

/**
 * Enroll user in course
 */
function learning_enroll_user($pdo, $centre_id, $user_id, $data) {
    try {
        $course_id = $data['course_id'] ?? 0;

        $sql = "INSERT INTO rescue_learning_user_courses
                (user_id, course_id, centre_id, status)
                VALUES (?, ?, ?, 'in_progress')
                ON DUPLICATE KEY UPDATE status = IF(status = 'not_started', 'in_progress', status),
                                        started_at = COALESCE(started_at, NOW()),
                                        updated_at = NOW()";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $user_id,
            $course_id,
            $centre_id
        ]);

        if ($result) {
            $_SESSION['success'] = 'Enrolled in course';
        }
        return $result;
    } catch (Exception $e) {
        error_log("Error enrolling user: " . $e->getMessage());
        $_SESSION['error'] = 'Error enrolling in course';
    }
    return false;
}

/**
 * Update learner progress
 */
function learning_update_progress($pdo, $user_id, $data) {
    try {
        $user_course_id = $data['user_course_id'] ?? 0;
        $current_page_id = $data['current_page_id'] ?? null;
        $progress_percent = $data['progress_percent'] ?? 0;

        $status = ((int)$progress_percent >= 100) ? 'completed' : 'in_progress';

        $sql = "UPDATE rescue_learning_user_courses SET
                current_page_id = ?,
                progress_percent = ?,
                status = ?,
                started_at = COALESCE(started_at, NOW()),
                completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_at END,
                last_accessed_at = NOW(),
                updated_at = NOW()
                WHERE user_course_id = ? AND user_id = ?";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $current_page_id,
            $progress_percent,
            $status,
            $status,
            $user_course_id,
            $user_id
        ]);

        return $result;
    } catch (Exception $e) {
        error_log("Error updating progress: " . $e->getMessage());
    }
    return false;
}

/**
 * Submit assessment
 */
function learning_submit_assessment($pdo, $user_id, $data) {
    try {
        $user_course_id = $data['user_course_id'] ?? 0;
        $course_id = $data['course_id'] ?? 0;
        $answers = [];
        if (isset($data['answers'])) {
            $answers = json_decode((string)$data['answers'], true) ?: [];
        } elseif (isset($data['answer']) && is_array($data['answer'])) {
            $answers = $data['answer'];
        }

        // Get assessment
        $assess = $pdo->prepare("SELECT * FROM rescue_learning_assessments WHERE course_id = ?");
        $assess->execute([$course_id]);
        $assessment = $assess->fetch(PDO::FETCH_ASSOC);

        if (!$assessment) {
            $_SESSION['error'] = 'No assessment found';
            return false;
        }

        // Get questions
        $qs = $pdo->prepare("SELECT * FROM rescue_learning_questions WHERE assessment_id = ? AND is_active = 1");
        $qs->execute([$assessment['assessment_id']]);
        $questions = $qs->fetchAll(PDO::FETCH_ASSOC);

        // Calculate score
        $correct = 0;
        foreach ($questions as $q) {
            $user_answer = $answers[$q['question_id']] ?? null;
            if (check_answer_correct($pdo, $q['question_id'], $user_answer)) {
                $correct++;
            }
        }

        $total = count($questions) > 0 ? count($questions) : 1;
        $score = round(($correct / $total) * 100);

        // Get course pass mark
        $course = $pdo->prepare("SELECT pass_mark_percent FROM rescue_learning_courses WHERE course_id = ?");
        $course->execute([$course_id]);
        $course_data = $course->fetch(PDO::FETCH_ASSOC);
        $pass_mark = $course_data['pass_mark_percent'] ?? 70;

        $passed = $score >= $pass_mark;
        $status = $passed ? 'completed' : 'failed';

        // Update user course
        $update = $pdo->prepare("UPDATE rescue_learning_user_courses SET
                assessment_status = ?,
                assessment_score = ?,
                status = ?,
                attempt_count = attempt_count + 1,
                progress_percent = CASE WHEN ? = 'completed' THEN 100 ELSE progress_percent END,
                completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_at END,
                passed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE passed_at END,
                certificate_earned = CASE WHEN ? = 'completed' THEN 1 ELSE certificate_earned END,
                updated_at = NOW()
                WHERE user_course_id = ? AND user_id = ?");

        $update->execute([
            $passed ? 'passed' : 'failed',
            $score,
            $status,
            $status,
            $status,
            $status,
            $status,
            $user_course_id,
            $user_id
        ]);

        $_SESSION['assessment_result'] = [
            'score' => $score,
            'passed' => $passed,
            'correct' => $correct,
            'total' => $total
        ];

        return true;
    } catch (Exception $e) {
        error_log("Error submitting assessment: " . $e->getMessage());
        $_SESSION['error'] = 'Error submitting assessment';
    }
    return false;
}

/**
 * Check if answer is correct
 */
function check_answer_correct($pdo, $question_id, $user_answer) {
    if (!$user_answer) {
        return false;
    }

    $submitted = is_array($user_answer) ? array_map('intval', $user_answer) : [(int)$user_answer];
    sort($submitted);

    $stmt = $pdo->prepare("SELECT answer_id FROM rescue_learning_answers WHERE question_id = ? AND is_correct = 1 ORDER BY answer_id ASC");
    $stmt->execute([$question_id]);
    $correct = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    sort($correct);

    return $submitted === $correct;
}

function learning_create_page(PDO $pdo, int $centre_id, array $data): bool
{
    $course_id = (int)($data['course_id'] ?? 0);
    if ($course_id <= 0 || !learning_course_owned_by_centre($pdo, $course_id, $centre_id)) {
        $_SESSION['error'] = 'No permission to update course content';
        error_log("learning_create_page: Permission check failed for course_id=$course_id, centre_id=$centre_id");
        return false;
    }

    try {
        $orderStmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 10 FROM rescue_learning_pages WHERE course_id = ?");
        $orderStmt->execute([$course_id]);
        $sort_order = (int)$orderStmt->fetchColumn();

        $stmt = $pdo->prepare("
            INSERT INTO rescue_learning_pages
                (course_id, page_title, page_type, page_content, media_url, sort_order, is_required, is_active)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, 1)
        ");

        $ok = $stmt->execute([
            $course_id,
            trim((string)($data['title'] ?? $data['page_title'] ?? 'Untitled page')),
            in_array((string)($data['page_type'] ?? 'text'), ['text', 'video', 'file', 'link', 'mixed'], true) ? (string)$data['page_type'] : 'text',
            (string)($data['content'] ?? $data['page_content'] ?? ''),
            trim((string)($data['media_url'] ?? '')) ?: null,
            $sort_order,
            array_key_exists('is_required', $data) ? (!empty($data['is_required']) ? 1 : 0) : 1,
        ]);

        if ($ok) {
            $_SESSION['success'] = 'Page added successfully';
            error_log("learning_create_page: Page added successfully for course_id=$course_id");
        } else {
            $_SESSION['error'] = 'Could not add page: ' . implode(', ', $stmt->errorInfo());
            error_log("learning_create_page: Database error: " . implode(', ', $stmt->errorInfo()));
        }
        return $ok;
    } catch (Throwable $e) {
        $_SESSION['error'] = 'Error adding page: ' . $e->getMessage();
        error_log("learning_create_page: Exception - " . $e->getMessage());
        return false;
    }
}

function learning_delete_page(PDO $pdo, int $centre_id, array $data): bool
{
    $page_id = (int)($data['page_id'] ?? 0);
    if ($page_id <= 0) {
        return false;
    }

    $stmt = $pdo->prepare("
        DELETE p
        FROM rescue_learning_pages p
        JOIN rescue_learning_courses c ON c.course_id = p.course_id
        WHERE p.page_id = ? AND c.owner_centre_id = ?
    ");
    $ok = $stmt->execute([$page_id, $centre_id]);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Page deleted' : 'Could not delete page';
    return $ok;
}

function learning_update_page(PDO $pdo, int $centre_id, array $data): bool
{
    $page_id = (int)($data['page_id'] ?? 0);
    $course_id = (int)($data['course_id'] ?? 0);
    if ($page_id <= 0 || $course_id <= 0 || !learning_course_owned_by_centre($pdo, $course_id, $centre_id)) {
        $_SESSION['error'] = 'No permission to update page';
        return false;
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_learning_pages p
        INNER JOIN rescue_learning_courses c ON c.course_id = p.course_id
        SET p.page_title = ?,
            p.page_type = ?,
            p.page_content = ?,
            p.media_url = ?,
            p.is_required = ?,
            p.is_active = ?,
            p.updated_at = NOW()
        WHERE p.page_id = ?
          AND p.course_id = ?
          AND c.owner_centre_id = ?
    ");
    $ok = $stmt->execute([
        trim((string)($data['page_title'] ?? '')),
        (string)($data['page_type'] ?? 'text'),
        (string)($data['page_content'] ?? ''),
        trim((string)($data['media_url'] ?? '')),
        !empty($data['is_required']) ? 1 : 0,
        !isset($data['is_active']) || !empty($data['is_active']) ? 1 : 0,
        $page_id,
        $course_id,
        $centre_id,
    ]);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Page updated' : 'Could not update page';
    return $ok;
}

function learning_reorder_pages(PDO $pdo, int $centre_id, array $data): bool
{
    $course_id = (int)($data['course_id'] ?? 0);
    $pageOrder = array_filter(array_map('intval', explode(',', (string)($data['page_order'] ?? ''))));
    if ($course_id <= 0 || !$pageOrder || !learning_course_owned_by_centre($pdo, $course_id, $centre_id)) {
        $_SESSION['error'] = 'Could not reorder pages';
        return false;
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_learning_pages
        SET sort_order = ?
        WHERE page_id = ?
          AND course_id = ?
    ");

    $sort = 10;
    foreach ($pageOrder as $page_id) {
        $stmt->execute([$sort, $page_id, $course_id]);
        $sort += 10;
    }

    $_SESSION['success'] = 'Page order updated';
    return true;
}

function learning_create_assessment(PDO $pdo, int $centre_id, array $data): bool
{
    $course_id = (int)($data['course_id'] ?? 0);
    if ($course_id <= 0 || !learning_course_owned_by_centre($pdo, $course_id, $centre_id)) {
        $_SESSION['error'] = 'No permission to update assessment';
        error_log("learning_create_assessment: Permission check failed for course_id=$course_id, centre_id=$centre_id");
        return false;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO rescue_learning_assessments (course_id, title, instructions, is_active)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE title = VALUES(title), instructions = VALUES(instructions), is_active = 1, updated_at = NOW()
        ");

        $ok = $stmt->execute([
            $course_id,
            trim((string)($data['title'] ?? 'Assessment')),
            (string)($data['instructions'] ?? ''),
        ]);

        if ($ok) {
            $_SESSION['success'] = 'Assessment saved successfully';
            error_log("learning_create_assessment: Assessment saved for course_id=$course_id");
        } else {
            $_SESSION['error'] = 'Could not save assessment: ' . implode(', ', $stmt->errorInfo());
            error_log("learning_create_assessment: Database error: " . implode(', ', $stmt->errorInfo()));
        }
        return $ok;
    } catch (Throwable $e) {
        $_SESSION['error'] = 'Error saving assessment: ' . $e->getMessage();
        error_log("learning_create_assessment: Exception - " . $e->getMessage());
        return false;
    }
}

function learning_get_assessment_questions(PDO $pdo, int $assessment_id): array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM rescue_learning_questions
        WHERE assessment_id = ? AND is_active = 1
        ORDER BY sort_order ASC, question_id ASC
    ");
    $stmt->execute([$assessment_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function learning_question_type_labels(): array
{
    return [
        'single_choice' => 'Single choice',
        'multi_choice' => 'Multi choice',
        'true_false' => 'True / False',
        'yes_no' => 'Yes / No',
        'yes_no_unknown' => 'Yes / No / Don\'t know',
    ];
}

function learning_builtin_answers_for_type(string $questionType): array
{
    if ($questionType === 'true_false') {
        return [['True', 1], ['False', 0]];
    }
    if ($questionType === 'yes_no') {
        return [['Yes', 1], ['No', 0]];
    }
    if ($questionType === 'yes_no_unknown') {
        return [['Yes', 1], ['No', 0], ['Don\'t know', 0]];
    }
    return [];
}

function learning_replace_question_answers(PDO $pdo, int $question_id, array $answers): void
{
    $pdo->prepare("DELETE FROM rescue_learning_answers WHERE question_id = ?")->execute([$question_id]);
    if (!$answers) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO rescue_learning_answers (question_id, answer_text, is_correct, sort_order)
        VALUES (?, ?, ?, ?)
    ");
    foreach ($answers as $idx => $answer) {
        $stmt->execute([$question_id, (string)$answer[0], !empty($answer[1]) ? 1 : 0, ($idx + 1) * 10]);
    }
}

function learning_create_question(PDO $pdo, int $centre_id, array $data): bool
{
    $assessment_id = (int)($data['assessment_id'] ?? 0);
    $course_id = (int)($data['course_id'] ?? 0);
    if ($assessment_id <= 0 || $course_id <= 0 || !learning_course_owned_by_centre($pdo, $course_id, $centre_id)) {
        $_SESSION['error'] = 'No permission to update questions';
        error_log("learning_create_question: Permission check failed. assessment_id=$assessment_id, course_id=$course_id, centre_id=$centre_id");
        return false;
    }

    try {
        $questionType = (string)($data['question_type'] ?? 'single_choice');
        if (!isset(learning_question_type_labels()[$questionType])) {
            $questionType = 'single_choice';
        }

        $orderStmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 10 FROM rescue_learning_questions WHERE assessment_id = ?");
        $orderStmt->execute([$assessment_id]);
        $sort_order = (int)$orderStmt->fetchColumn();

        $stmt = $pdo->prepare("
            INSERT INTO rescue_learning_questions (assessment_id, question_text, question_type, sort_order, is_active)
            VALUES (?, ?, ?, ?, 1)
        ");
        $ok = $stmt->execute([
            $assessment_id,
            trim((string)($data['question_text'] ?? '')),
            $questionType,
            $sort_order,
        ]);

        if (!$ok) {
            $_SESSION['error'] = 'Could not add question: ' . implode(', ', $stmt->errorInfo());
            error_log("learning_create_question: Database error creating question: " . implode(', ', $stmt->errorInfo()));
            return false;
        }

        $question_id = (int)$pdo->lastInsertId();
        $builtinAnswers = learning_builtin_answers_for_type($questionType);
        if ($builtinAnswers) {
            learning_replace_question_answers($pdo, $question_id, $builtinAnswers);
            $_SESSION['success'] = 'Question added successfully';
            return true;
        }
        $answers = $data['answers'] ?? [];
        $correct = $data['correct_answers'] ?? [];
        if (!is_array($answers)) {
            $answers = [];
        }
        if (!is_array($correct)) {
            $correct = [$correct];
        }
        $correct = array_map('intval', $correct);

        $answerStmt = $pdo->prepare("
            INSERT INTO rescue_learning_answers (question_id, answer_text, is_correct, sort_order)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($answers as $idx => $answerText) {
            $answerText = trim((string)$answerText);
            if ($answerText === '') {
                continue;
            }
            $answerOk = $answerStmt->execute([
                $question_id,
                $answerText,
                in_array((int)$idx, $correct, true) ? 1 : 0,
                (($idx + 1) * 10),
            ]);
            if (!$answerOk) {
                error_log("learning_create_question: Failed to add answer $idx: " . implode(', ', $answerStmt->errorInfo()));
            }
        }

        $_SESSION['success'] = 'Question added successfully';
        error_log("learning_create_question: Question added successfully for assessment_id=$assessment_id");
        return true;
    } catch (Throwable $e) {
        $_SESSION['error'] = 'Error adding question: ' . $e->getMessage();
        error_log("learning_create_question: Exception - " . $e->getMessage());
        return false;
    }
}

function learning_question_owned_by_centre(PDO $pdo, int $question_id, int $course_id, int $centre_id): bool
{
    $stmt = $pdo->prepare("
        SELECT q.question_id
        FROM rescue_learning_questions q
        INNER JOIN rescue_learning_assessments a ON a.assessment_id = q.assessment_id
        INNER JOIN rescue_learning_courses c ON c.course_id = a.course_id
        WHERE q.question_id = ?
          AND c.course_id = ?
          AND c.owner_centre_id = ?
        LIMIT 1
    ");
    $stmt->execute([$question_id, $course_id, $centre_id]);
    return (bool)$stmt->fetchColumn();
}

function learning_update_question(PDO $pdo, int $centre_id, array $data): bool
{
    $course_id = (int)($data['course_id'] ?? 0);
    $question_id = (int)($data['question_id'] ?? 0);
    if ($course_id <= 0 || $question_id <= 0 || !learning_question_owned_by_centre($pdo, $question_id, $course_id, $centre_id)) {
        $_SESSION['error'] = 'Could not update question';
        return false;
    }

    $questionType = (string)($data['question_type'] ?? 'single_choice');
    if (!isset(learning_question_type_labels()[$questionType])) {
        $questionType = 'single_choice';
    }

    $existingStmt = $pdo->prepare("SELECT question_type FROM rescue_learning_questions WHERE question_id = ?");
    $existingStmt->execute([$question_id]);
    $previousQuestionType = (string)($existingStmt->fetchColumn() ?: '');

    $stmt = $pdo->prepare("
        UPDATE rescue_learning_questions
        SET question_text = ?,
            question_type = ?,
            updated_at = NOW()
        WHERE question_id = ?
    ");
    $ok = $stmt->execute([
        trim((string)($data['question_text'] ?? '')),
        $questionType,
        $question_id,
    ]);
    if ($ok) {
        $builtinAnswers = learning_builtin_answers_for_type($questionType);
        if ($builtinAnswers && $previousQuestionType !== $questionType) {
            learning_replace_question_answers($pdo, $question_id, $builtinAnswers);
        }
    }
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Question updated' : 'Could not update question';
    return $ok;
}

function learning_answer_owned_by_centre(PDO $pdo, int $answer_id, int $course_id, int $centre_id): ?array
{
    $stmt = $pdo->prepare("
        SELECT ans.answer_id, ans.question_id, q.question_type
        FROM rescue_learning_answers ans
        INNER JOIN rescue_learning_questions q ON q.question_id = ans.question_id
        INNER JOIN rescue_learning_assessments a ON a.assessment_id = q.assessment_id
        INNER JOIN rescue_learning_courses c ON c.course_id = a.course_id
        WHERE ans.answer_id = ?
          AND c.course_id = ?
          AND c.owner_centre_id = ?
        LIMIT 1
    ");
    $stmt->execute([$answer_id, $course_id, $centre_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function learning_create_answer(PDO $pdo, int $centre_id, array $data): bool
{
    $course_id = (int)($data['course_id'] ?? 0);
    $question_id = (int)($data['question_id'] ?? 0);
    $answerText = trim((string)($data['answer_text'] ?? ''));
    if ($course_id <= 0 || $question_id <= 0 || $answerText === '' || !learning_question_owned_by_centre($pdo, $question_id, $course_id, $centre_id)) {
        $_SESSION['error'] = 'Could not add answer';
        return false;
    }

    $orderStmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 10 FROM rescue_learning_answers WHERE question_id = ?");
    $orderStmt->execute([$question_id]);
    $sort_order = (int)$orderStmt->fetchColumn();

    $stmt = $pdo->prepare("
        INSERT INTO rescue_learning_answers (question_id, answer_text, is_correct, sort_order)
        VALUES (?, ?, ?, ?)
    ");
    $ok = $stmt->execute([
        $question_id,
        $answerText,
        !empty($data['is_correct']) ? 1 : 0,
        $sort_order,
    ]);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Answer added' : 'Could not add answer';
    return $ok;
}

function learning_toggle_answer_correct(PDO $pdo, int $centre_id, array $data): bool
{
    $course_id = (int)($data['course_id'] ?? 0);
    $answer_id = (int)($data['answer_id'] ?? 0);
    $answer = learning_answer_owned_by_centre($pdo, $answer_id, $course_id, $centre_id);
    if (!$answer) {
        $_SESSION['error'] = 'Could not update answer';
        return false;
    }

    $stmt = $pdo->prepare("SELECT is_correct FROM rescue_learning_answers WHERE answer_id = ?");
    $stmt->execute([$answer_id]);
    $nextValue = (int)$stmt->fetchColumn() === 1 ? 0 : 1;

    if ($nextValue === 1 && in_array((string)$answer['question_type'], ['single_choice', 'true_false', 'yes_no', 'yes_no_unknown'], true)) {
        $reset = $pdo->prepare("UPDATE rescue_learning_answers SET is_correct = 0 WHERE question_id = ?");
        $reset->execute([(int)$answer['question_id']]);
    }

    $update = $pdo->prepare("UPDATE rescue_learning_answers SET is_correct = ? WHERE answer_id = ?");
    $ok = $update->execute([$nextValue, $answer_id]);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Answer updated' : 'Could not update answer';
    return $ok;
}

function learning_delete_answer(PDO $pdo, int $centre_id, array $data): bool
{
    $course_id = (int)($data['course_id'] ?? 0);
    $answer_id = (int)($data['answer_id'] ?? 0);
    if (!learning_answer_owned_by_centre($pdo, $answer_id, $course_id, $centre_id)) {
        $_SESSION['error'] = 'Could not delete answer';
        return false;
    }

    $stmt = $pdo->prepare("DELETE FROM rescue_learning_answers WHERE answer_id = ?");
    $ok = $stmt->execute([$answer_id]);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Answer deleted' : 'Could not delete answer';
    return $ok;
}

function learning_delete_question(PDO $pdo, int $centre_id, array $data): bool
{
    $course_id = (int)($data['course_id'] ?? 0);
    $question_id = (int)($data['question_id'] ?? 0);
    if (!learning_question_owned_by_centre($pdo, $question_id, $course_id, $centre_id)) {
        $_SESSION['error'] = 'Could not delete question';
        return false;
    }

    $pdo->prepare("DELETE FROM rescue_learning_answers WHERE question_id = ?")->execute([$question_id]);
    $stmt = $pdo->prepare("DELETE FROM rescue_learning_questions WHERE question_id = ?");
    $ok = $stmt->execute([$question_id]);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Question deleted' : 'Could not delete question';
    return $ok;
}

function learning_retry_course(PDO $pdo, int $centre_id, int $user_id, array $data): bool
{
    $course_id = (int)($data['course_id'] ?? 0);
    if ($course_id <= 0) {
        return false;
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_learning_user_courses uc
        JOIN rescue_learning_courses c ON c.course_id = uc.course_id
        SET uc.status = 'in_progress',
            uc.assessment_status = 'not_attempted',
            uc.assessment_score = NULL,
            uc.progress_percent = 0,
            uc.current_page_id = NULL,
            uc.started_at = NOW(),
            uc.completed_at = NULL,
            uc.passed_at = NULL,
            uc.updated_at = NOW()
        WHERE uc.user_id = ?
          AND uc.centre_id = ?
          AND uc.course_id = ?
          AND uc.attempt_count < c.max_attempts
    ");

    $ok = $stmt->execute([$user_id, $centre_id, $course_id]);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Course reset for another attempt' : 'Could not retry course';
    return $ok;
}

function learning_course_owned_by_centre(PDO $pdo, int $course_id, int $centre_id): bool
{
    $stmt = $pdo->prepare("SELECT course_id FROM rescue_learning_courses WHERE course_id = ? AND owner_centre_id = ?");
    $stmt->execute([$course_id, $centre_id]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Get user's courses
 */
function learning_get_user_courses($pdo, $user_id, $centre_id) {
    $sql = "SELECT uc.*, c.title, c.description, c.pass_mark_percent
            FROM rescue_learning_user_courses uc
            JOIN rescue_learning_courses c ON uc.course_id = c.course_id
            WHERE uc.user_id = ? AND uc.centre_id = ?
            ORDER BY uc.updated_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $centre_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function learning_get_available_learner_courses(PDO $pdo, int $user_id, int $centre_id, string $user_role = ''): array
{
    if ($user_id <= 0 || $centre_id <= 0) {
        return [];
    }

    $normalisedRole = trim($user_role);
    $courseColumns = learning_table_columns($pdo, 'rescue_learning_courses');
    $assignmentColumns = learning_table_columns($pdo, 'rescue_learning_assignments');

    $platformCondition = !empty($courseColumns['is_platform_course'])
        ? " OR COALESCE(c.is_platform_course, 0) = 1"
        : "";

    $assignmentCourseJoin = "a.course_id = c.course_id";
    if (!empty($assignmentColumns['assignable_type']) && !empty($assignmentColumns['assignable_id'])) {
        $assignmentCourseJoin = "a.assignable_type = 'course' AND a.assignable_id = c.course_id";
    }

    $targetValueChecks = [];
    if (!empty($assignmentColumns['target_id'])) {
        $targetValueChecks[] = "CAST(a.target_id AS CHAR) = CAST(:target_user_id AS CHAR)";
    }
    if (!empty($assignmentColumns['target_value'])) {
        $targetValueChecks[] = "CAST(a.target_value AS CHAR) = CAST(:target_user_value AS CHAR)";
    }
    $userTargetSql = $targetValueChecks ? implode(' OR ', $targetValueChecks) : '1 = 0';

    $roleValueChecks = [];
    if (!empty($assignmentColumns['target_value'])) {
        $roleValueChecks[] = "CAST(a.target_value AS CHAR) IN ('ALL', :target_role)";
    }
    if (!empty($assignmentColumns['target_id'])) {
        $roleValueChecks[] = "CAST(a.target_id AS CHAR) = :target_role_id";
    }
    $roleTargetSql = $roleValueChecks ? implode(' OR ', $roleValueChecks) : '1 = 0';

    $stmt = $pdo->prepare("
        SELECT
            c.*,
            COUNT(DISTINCT p.page_id) AS page_count,
            COALESCE(uc.status, 'not_started') AS status,
            COALESCE(uc.progress_percent, 0) AS progress_percent,
            COALESCE(uc.certificate_earned, 0) AS certificate_earned
        FROM rescue_learning_courses c
        LEFT JOIN rescue_learning_pages p
            ON p.course_id = c.course_id
           AND (p.is_active = 1 OR p.is_active IS NULL)
        LEFT JOIN rescue_learning_user_courses uc
            ON uc.course_id = c.course_id
           AND uc.user_id = :user_id
           AND uc.centre_id = :centre_id
        LEFT JOIN rescue_learning_assignments a
            ON a.is_active = 1
           AND a.centre_id = :assignment_centre_id
           AND {$assignmentCourseJoin}
           AND (
                c.owner_centre_id = :owner_centre_id
                OR c.visibility IN ('centre', 'global', 'platform')
                {$platformCondition}
           )
           AND (
                a.target_type IS NULL
                OR (
                    a.target_type = 'user'
                    AND ({$userTargetSql})
                )
                OR (
                    a.target_type = 'role'
                    AND ({$roleTargetSql})
                )
           )
        WHERE c.is_active = 1
          AND (
                c.owner_centre_id = :course_centre_id
                OR c.visibility IN ('global', 'platform')
                {$platformCondition}
                OR a.assignment_id IS NOT NULL
          )
        GROUP BY c.course_id
        ORDER BY c.title ASC
    ");
    $params = [
        ':user_id' => $user_id,
        ':centre_id' => $centre_id,
        ':assignment_centre_id' => $centre_id,
        ':owner_centre_id' => $centre_id,
        ':course_centre_id' => $centre_id,
    ];
    if (!empty($assignmentColumns['target_value'])) {
        $params[':target_user_value'] = $user_id;
        $params[':target_role'] = $normalisedRole;
    }
    if (!empty($assignmentColumns['target_id'])) {
        $params[':target_user_id'] = $user_id;
        $params[':target_role_id'] = $normalisedRole;
    }
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function learning_get_learner_suites(PDO $pdo, int $user_id, int $centre_id, string $user_role, array $availableCourses, int $previewLimit = 4): array
{
    unset($user_id, $user_role);

    if (!$availableCourses || !learning_table_columns($pdo, 'rescue_learning_suites') || !learning_table_columns($pdo, 'rescue_learning_suite_courses')) {
        return [];
    }

    $courseMap = [];
    foreach ($availableCourses as $course) {
        $courseMap[(int)$course['course_id']] = $course;
    }

    $suiteColumns = learning_table_columns($pdo, 'rescue_learning_suites');
    $visibilitySelect = !empty($suiteColumns['visibility']) ? 's.visibility' : "'centre'";

    $stmt = $pdo->prepare("
        SELECT
            s.suite_id,
            s.title,
            s.description,
            {$visibilitySelect} AS visibility,
            sc.course_id,
            sc.sort_order
        FROM rescue_learning_suites s
        INNER JOIN rescue_learning_suite_courses sc
            ON sc.suite_id = s.suite_id
        WHERE s.owner_centre_id = ?
          AND (s.is_active = 1 OR s.is_active IS NULL)
        ORDER BY s.title ASC, sc.sort_order ASC, sc.suite_course_id ASC
    ");
    $stmt->execute([$centre_id]);

    $suites = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $courseId = (int)$row['course_id'];
        if (empty($courseMap[$courseId])) {
            continue;
        }

        $suiteId = (int)$row['suite_id'];
        if (!isset($suites[$suiteId])) {
            $suites[$suiteId] = [
                'suite_id' => $suiteId,
                'title' => $row['title'],
                'description' => $row['description'] ?? '',
                'visibility' => $row['visibility'] ?? 'centre',
                'courses' => [],
                'total_courses' => 0,
            ];
        }

        $suites[$suiteId]['total_courses']++;
        $suites[$suiteId]['courses'][] = $courseMap[$courseId];
    }

    foreach ($suites as &$suite) {
        shuffle($suite['courses']);
        $suite['courses'] = array_slice($suite['courses'], 0, $previewLimit);
    }
    unset($suite);

    return array_values(array_filter($suites, static fn(array $suite): bool => $suite['total_courses'] > 0));
}

function learning_status_tone($status, $certificateEarned = null): string
{
    if (!empty($certificateEarned)) {
        return 'good';
    }

    switch ((string)$status) {
        case 'completed':
        case 'passed':
            return 'good';
        case 'in_progress':
            return 'blue';
        case 'failed':
            return 'bad';
        case 'not_started':
        case 'not_attempted':
        default:
            return '';
    }
}

function learning_get_assignable_courses($pdo, $centre_id) {
    $sql = "SELECT *
            FROM rescue_learning_courses
            WHERE is_active = 1
              AND (
                    owner_centre_id = ?
                    OR visibility = 'global'
                  )
            ORDER BY title ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$centre_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get centre courses
 */
function learning_get_centre_courses($pdo, $centre_id, $active_only = true) {
    $sql = "SELECT * FROM rescue_learning_courses
            WHERE owner_centre_id = ?";

    if ($active_only) {
        $sql .= " AND is_active = 1";
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$centre_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function learning_table_columns(PDO $pdo, string $table): array
{
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
        ");
        $stmt->execute([$table]);
        $cache[$table] = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [], true);
    } catch (Throwable $e) {
        $cache[$table] = [];
    }

    return $cache[$table];
}

function learning_get_suites(PDO $pdo, int $centre_id): array
{
    $columns = learning_table_columns($pdo, 'rescue_learning_suites');
    if (!$columns) {
        return [];
    }

    $visibilitySelect = !empty($columns['visibility']) ? 's.visibility' : "'private'";
    $stmt = $pdo->prepare("
        SELECT
            s.*,
            {$visibilitySelect} AS visibility,
            (
                SELECT COUNT(*)
                FROM rescue_learning_suite_courses sc
                WHERE sc.suite_id = s.suite_id
            ) AS course_count
        FROM rescue_learning_suites s
        WHERE s.owner_centre_id = ?
        ORDER BY s.created_at DESC, s.suite_id DESC
    ");
    $stmt->execute([$centre_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function learning_get_suite_courses(PDO $pdo, int $centre_id): array
{
    if (!learning_table_columns($pdo, 'rescue_learning_suite_courses')) {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT
            sc.suite_course_id,
            sc.suite_id,
            sc.course_id,
            sc.sort_order,
            c.title AS course_title
        FROM rescue_learning_suite_courses sc
        INNER JOIN rescue_learning_suites s
            ON s.suite_id = sc.suite_id
        INNER JOIN rescue_learning_courses c
            ON c.course_id = sc.course_id
        WHERE s.owner_centre_id = ?
        ORDER BY sc.suite_id ASC, sc.sort_order ASC, c.title ASC
    ");
    $stmt->execute([$centre_id]);

    $grouped = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $grouped[(int)$row['suite_id']][] = $row;
    }

    return $grouped;
}

function learning_suite_owned_by_centre(PDO $pdo, int $suite_id, int $centre_id): bool
{
    $stmt = $pdo->prepare("
        SELECT suite_id
        FROM rescue_learning_suites
        WHERE suite_id = ?
          AND owner_centre_id = ?
        LIMIT 1
    ");
    $stmt->execute([$suite_id, $centre_id]);
    return (bool)$stmt->fetchColumn();
}

function learning_course_available_for_suite(PDO $pdo, int $course_id, int $centre_id): bool
{
    $columns = learning_table_columns($pdo, 'rescue_learning_courses');
    $platformCondition = !empty($columns['is_platform_course']) ? 'OR is_platform_course = 1' : '';
    $visibilityCondition = !empty($columns['visibility']) ? "OR visibility IN ('global', 'platform')" : '';
    $stmt = $pdo->prepare("
        SELECT course_id
        FROM rescue_learning_courses
        WHERE course_id = ?
          AND is_active = 1
          AND (
                owner_centre_id = ?
                {$visibilityCondition}
                {$platformCondition}
          )
        LIMIT 1
    ");
    $stmt->execute([$course_id, $centre_id]);
    return (bool)$stmt->fetchColumn();
}

function learning_create_suite(PDO $pdo, int $centre_id, array $data): bool
{
    $columns = learning_table_columns($pdo, 'rescue_learning_suites');
    if (!$columns) {
        $_SESSION['error'] = 'Suites table is not installed';
        return false;
    }

    $title = trim((string)($data['title'] ?? ''));
    if ($title === '') {
        $_SESSION['error'] = 'Suite name is required';
        return false;
    }

    $fields = ['owner_centre_id', 'title', 'description', 'is_active'];
    $values = [
        $centre_id,
        $title,
        trim((string)($data['description'] ?? '')),
        !empty($data['is_active']) ? 1 : 0,
    ];

    if (!empty($columns['visibility'])) {
        $visibility = (string)($data['visibility'] ?? 'private');
        if (!in_array($visibility, ['private', 'centre', 'global'], true)) {
            $visibility = 'private';
        }
        $fields[] = 'visibility';
        $values[] = $visibility;
    }

    $placeholders = implode(', ', array_fill(0, count($fields), '?'));
    $sql = 'INSERT INTO rescue_learning_suites (' . implode(', ', $fields) . ') VALUES (' . $placeholders . ')';
    $stmt = $pdo->prepare($sql);
    $ok = $stmt->execute($values);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Suite created' : 'Could not create suite';
    return $ok;
}

function learning_update_suite(PDO $pdo, int $centre_id, array $data): bool
{
    $suite_id = (int)($data['suite_id'] ?? 0);
    if ($suite_id <= 0 || !learning_suite_owned_by_centre($pdo, $suite_id, $centre_id)) {
        $_SESSION['error'] = 'Could not update suite';
        return false;
    }

    $columns = learning_table_columns($pdo, 'rescue_learning_suites');
    $sets = ['title = ?', 'description = ?', 'is_active = ?'];
    $values = [
        trim((string)($data['title'] ?? '')),
        trim((string)($data['description'] ?? '')),
        !empty($data['is_active']) ? 1 : 0,
    ];

    if ($values[0] === '') {
        $_SESSION['error'] = 'Suite name is required';
        return false;
    }

    if (!empty($columns['visibility'])) {
        $visibility = (string)($data['visibility'] ?? 'private');
        if (!in_array($visibility, ['private', 'centre', 'global'], true)) {
            $visibility = 'private';
        }
        $sets[] = 'visibility = ?';
        $values[] = $visibility;
    }
    if (!empty($columns['updated_at'])) {
        $sets[] = 'updated_at = NOW()';
    }

    $values[] = $suite_id;
    $stmt = $pdo->prepare('UPDATE rescue_learning_suites SET ' . implode(', ', $sets) . ' WHERE suite_id = ?');
    $ok = $stmt->execute($values);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Suite updated' : 'Could not update suite';
    return $ok;
}

function learning_add_course_to_suite(PDO $pdo, int $centre_id, array $data): bool
{
    $suite_id = (int)($data['suite_id'] ?? 0);
    $course_id = (int)($data['course_id'] ?? 0);
    if ($suite_id <= 0 || $course_id <= 0 || !learning_suite_owned_by_centre($pdo, $suite_id, $centre_id) || !learning_course_available_for_suite($pdo, $course_id, $centre_id)) {
        $_SESSION['error'] = 'Could not add course to suite';
        return false;
    }

    $orderStmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 10 FROM rescue_learning_suite_courses WHERE suite_id = ?");
    $orderStmt->execute([$suite_id]);
    $sort_order = (int)$orderStmt->fetchColumn();

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO rescue_learning_suite_courses (suite_id, course_id, sort_order)
        VALUES (?, ?, ?)
    ");
    $ok = $stmt->execute([$suite_id, $course_id, $sort_order]);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Course added to suite' : 'Could not add course to suite';
    return $ok;
}

function learning_remove_course_from_suite(PDO $pdo, int $centre_id, array $data): bool
{
    $suite_course_id = (int)($data['suite_course_id'] ?? 0);
    if ($suite_course_id <= 0) {
        $_SESSION['error'] = 'Could not remove course from suite';
        return false;
    }

    $stmt = $pdo->prepare("
        DELETE sc
        FROM rescue_learning_suite_courses sc
        INNER JOIN rescue_learning_suites s
            ON s.suite_id = sc.suite_id
        WHERE sc.suite_course_id = ?
          AND s.owner_centre_id = ?
    ");
    $ok = $stmt->execute([$suite_course_id, $centre_id]);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Course removed from suite' : 'Could not remove course from suite';
    return $ok;
}

function learning_get_assignments(PDO $pdo, int $centre_id): array
{
    $columns = learning_table_columns($pdo, 'rescue_learning_assignments');
    if (!$columns) {
        return [];
    }

    $hasAssignable = !empty($columns['assignable_type']) && !empty($columns['assignable_id']);
    $hasDueDate = !empty($columns['due_date']);
    $hasMandatory = !empty($columns['is_mandatory']);
    $hasTargetValue = !empty($columns['target_value']);

    $assignableTypeSelect = $hasAssignable ? 'a.assignable_type' : "'course' AS assignable_type";
    $assignableIdSelect = $hasAssignable ? 'a.assignable_id' : 'a.course_id AS assignable_id';
    $courseJoin = $hasAssignable
        ? "LEFT JOIN rescue_learning_courses c ON a.assignable_type = 'course' AND c.course_id = a.assignable_id"
        : "LEFT JOIN rescue_learning_courses c ON c.course_id = a.course_id";
    $suiteJoin = $hasAssignable
        ? "LEFT JOIN rescue_learning_suites s ON a.assignable_type = 'suite' AND s.suite_id = a.assignable_id"
        : "LEFT JOIN rescue_learning_suites s ON 1 = 0";
    $targetValueSelect = $hasTargetValue ? 'a.target_value' : 'CAST(a.target_id AS CHAR) AS target_value';
    $dueDateSelect = $hasDueDate ? 'a.due_date' : (!empty($columns['expires_at']) ? 'a.expires_at AS due_date' : 'NULL AS due_date');
    $mandatorySelect = $hasMandatory ? 'a.is_mandatory' : '1 AS is_mandatory';

    $stmt = $pdo->prepare("
        SELECT
            a.assignment_id,
            {$assignableTypeSelect},
            {$assignableIdSelect},
            COALESCE(c.title, s.title) AS assignable_title,
            a.target_type,
            {$targetValueSelect},
            {$dueDateSelect},
            {$mandatorySelect},
            a.is_active
        FROM rescue_learning_assignments a
        {$courseJoin}
        {$suiteJoin}
        WHERE a.centre_id = ?
        ORDER BY a.assignment_id DESC
    ");
    $stmt->execute([$centre_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function learning_get_rescue_roles(PDO $pdo, int $centre_id): array
{
    try {
        $columns = learning_table_columns($pdo, 'rescue_roles');
        if (!$columns) {
            return [];
        }

        $valueColumn = !empty($columns['role_key']) ? 'role_key' : (!empty($columns['role_name']) ? 'role_name' : 'role_id');
        $labelColumn = !empty($columns['role_name']) ? 'role_name' : (!empty($columns['name']) ? 'name' : $valueColumn);
        $where = !empty($columns['centre_id']) ? 'WHERE centre_id = ? OR centre_id = 0 OR centre_id IS NULL' : '';
        $sql = "
            SELECT {$valueColumn} AS role_value, {$labelColumn} AS role_label
            FROM rescue_roles
            {$where}
            ORDER BY {$labelColumn} ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(!empty($columns['centre_id']) ? [$centre_id] : []);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Get course pages
 */
function learning_get_course_pages($pdo, $course_id) {
    $sql = "SELECT page_id,
                   course_id,
                   page_title,
                   page_title AS title,
                   page_type,
                   page_content,
                   page_content AS content,
                   media_url,
                   sort_order,
                   is_required
            FROM rescue_learning_pages
            WHERE course_id = ?
            ORDER BY sort_order ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$course_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function learning_bootstrap(PDO $pdo): void
{
    learning_register_permissions();
    learning_ensure_schema($pdo);
    learning_ensure_question_type_schema($pdo);
}

function learning_require_access(): bool
{
    if (!learning_can_access()) {
        echo '<div class="rc-alert red">You do not have permission to access learning.</div>';
        return false;
    }
    return true;
}

function learning_require_admin(): bool
{
    if (!learning_has_admin_permission()) {
        echo '<div class="rc-alert red">You do not have permission to manage learning.</div>';
        return false;
    }
    return true;
}

/**
 * Get course assessment
 */
function learning_get_course_assessment($pdo, $course_id) {
    $sql = "
        SELECT *
        FROM rescue_learning_assessments
        WHERE course_id = ?
          AND (is_active = 1 OR is_active IS NULL)
        ORDER BY assessment_id DESC
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([(int)$course_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get assessment questions with their answers for learner rendering.
 */
function learning_get_assessment_questions_with_answers(PDO $pdo, int $assessment_id): array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM rescue_learning_questions
        WHERE assessment_id = ?
          AND is_active = 1
        ORDER BY sort_order ASC, question_id ASC
    ");
    $stmt->execute([$assessment_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $answerStmt = $pdo->prepare("
        SELECT answer_id, question_id, answer_text, is_correct, sort_order
        FROM rescue_learning_answers
        WHERE question_id = ?
        ORDER BY sort_order ASC, answer_id ASC
    ");

    foreach ($questions as &$question) {
        $answerStmt->execute([(int)$question['question_id']]);
        $question['answers'] = $answerStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    unset($question);

    return $questions;
}

/**
 * Calculate progress percentage
 */
function learning_calculate_progress($total_pages, $completed_pages) {
    if ($total_pages === 0) {
        return 0;
    }
    return round(($completed_pages / $total_pages) * 100);
}

/**
 * Get status label
 */
function learning_get_status_label($status) {
    $labels = [
        'not_started' => 'Not Started',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'not_attempted' => 'Not Attempted',
        'passed' => 'Passed'
    ];
    return $labels[$status] ?? $status;
}
?>
