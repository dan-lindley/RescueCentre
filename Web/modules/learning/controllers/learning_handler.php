<?php
/**
 * Learning Module Handler
 * Main controller for e-learning module
 * Handles routing, permissions, and actions
 */

require_once __DIR__ . '/../../../dashmain.php';
require_once __DIR__ . '/../../../operations/permissions.php';
require_once __DIR__ . '/../../../operations/modules_registry.php';
require_once __DIR__ . '/learning_lib.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Get current user and centre
$centre_id = learning_get_centre_id();
$user_id = learning_get_user_id();
$user_role = learning_get_user_role();

// Store in globals for use in views
$GLOBALS['centre_id'] = $centre_id;
$GLOBALS['user_id'] = $user_id;
$GLOBALS['user_role'] = $user_role;

// Check authentication
if ($centre_id <= 0 || $user_id <= 0) {
    learning_redirect(['error' => 'NOT_AUTHENTICATED']);
}

try {
    learning_bootstrap($pdo);

    $module = function_exists('modules_find') ? modules_find($pdo, 'learning', $centre_id) : null;
    if ($module && (empty($module['installed']) || empty($module['enabled']))) {
        learning_redirect(['view' => 'learner_dashboard', 'error' => 'MODULE_INACTIVE']);
    }

    $action = isset($_POST['action']) ? sanitize_input($_POST['action']) : '';
    if ($action !== '') {
        handle_learning_action($action, $pdo, $centre_id, $user_id, $user_role);
    } else {
        error_log("Learning Module: No action provided");
    }

    $returnView = sanitize_input($_POST['return_view'] ?? $_GET['return_view'] ?? 'learner_dashboard');
    $params = ['view' => $returnView];
    $returnCourseId = (int)($_POST['return_course_id'] ?? $_POST['course_id'] ?? $_SESSION['new_course_id'] ?? 0);
    if ($returnCourseId > 0 && in_array($returnView, ['edit_course', 'take_course', 'course_results'], true)) {
        $params['course_id'] = $returnCourseId;
    }
    $returnSection = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_POST['return_section'] ?? ''));
    if ($returnSection !== '' && $returnView === 'edit_course') {
        $params['section'] = $returnSection;
    }
    $returnTab = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_POST['return_tab'] ?? ''));
    if ($returnTab !== '' && $returnView === 'admin_dashboard') {
        $params['tab'] = $returnTab;
    }
    if (isset($_SESSION['new_course_id'])) {
        unset($_SESSION['new_course_id']);
    }

    if ((string)($_POST['return_context'] ?? $_GET['return_context'] ?? '') === 'learner') {
        $url = '../../../learner.php';
        $query = http_build_query($params);
        header('Location: ' . $url . ($query ? '?' . $query : ''));
        exit;
    }

    learning_redirect($params);
} catch (Exception $e) {
    error_log("Learning Module Error: " . $e->getMessage());
    learning_redirect(['view' => 'learner_dashboard', 'error' => 'ACTION_FAILED']);
}

/**
 * Handle learning module actions
 */
function handle_learning_action($action, $pdo, $centre_id, $user_id, $user_role) {
    switch ($action) {
        case 'create_course':
            if (learning_has_admin_permission($user_role)) {
                learning_create_course($pdo, $centre_id, $user_id, $_POST);
                $_POST['return_view'] = 'edit_course';
            }
            break;

        case 'update_course':
            if (learning_has_admin_permission($user_role)) {
                learning_update_course($pdo, $centre_id, $_POST);
            }
            break;

        case 'archive_course':
            if (learning_has_admin_permission($user_role)) {
                learning_set_course_active($pdo, $centre_id, $_POST, false);
            }
            break;

        case 'restore_course':
            if (learning_has_admin_permission($user_role)) {
                learning_set_course_active($pdo, $centre_id, $_POST, true);
            }
            break;

        case 'delete_course':
            if (learning_has_admin_permission($user_role)) {
                learning_delete_course($pdo, $centre_id, $_POST);
            }
            break;

        case 'enroll_course':
            learning_enroll_user($pdo, $centre_id, $user_id, $_POST);
            break;

        case 'create_page':
            if (learning_has_admin_permission($user_role)) {
                learning_create_page($pdo, $centre_id, $_POST);
            }
            break;

        case 'update_page':
            if (learning_has_admin_permission($user_role)) {
                learning_update_page($pdo, $centre_id, $_POST);
            }
            break;

        case 'reorder_pages':
            if (learning_has_admin_permission($user_role)) {
                learning_reorder_pages($pdo, $centre_id, $_POST);
            }
            break;

        case 'delete_page':
            if (learning_has_admin_permission($user_role)) {
                learning_delete_page($pdo, $centre_id, $_POST);
            }
            break;

        case 'create_assessment':
            if (learning_has_admin_permission($user_role)) {
                learning_create_assessment($pdo, $centre_id, $_POST);
            }
            break;

        case 'create_question':
            if (learning_has_admin_permission($user_role)) {
                learning_create_question($pdo, $centre_id, $_POST);
            }
            break;

        case 'update_question':
            if (learning_has_admin_permission($user_role)) {
                learning_update_question($pdo, $centre_id, $_POST);
            }
            break;

        case 'create_answer':
            if (learning_has_admin_permission($user_role)) {
                learning_create_answer($pdo, $centre_id, $_POST);
            }
            break;

        case 'toggle_answer_correct':
            if (learning_has_admin_permission($user_role)) {
                learning_toggle_answer_correct($pdo, $centre_id, $_POST);
            }
            break;

        case 'delete_answer':
            if (learning_has_admin_permission($user_role)) {
                learning_delete_answer($pdo, $centre_id, $_POST);
            }
            break;

        case 'delete_question':
            if (learning_has_admin_permission($user_role)) {
                learning_delete_question($pdo, $centre_id, $_POST);
            }
            break;

        case 'create_suite':
            if (learning_has_admin_permission($user_role)) {
                learning_create_suite($pdo, $centre_id, $_POST);
            }
            break;

        case 'update_suite':
            if (learning_has_admin_permission($user_role)) {
                learning_update_suite($pdo, $centre_id, $_POST);
            }
            break;

        case 'add_course_to_suite':
            if (learning_has_admin_permission($user_role)) {
                learning_add_course_to_suite($pdo, $centre_id, $_POST);
            }
            break;

        case 'remove_course_from_suite':
            if (learning_has_admin_permission($user_role)) {
                learning_remove_course_from_suite($pdo, $centre_id, $_POST);
            }
            break;

        case 'update_progress':
            learning_update_progress($pdo, $user_id, $_POST);
            break;

        case 'submit_assessment':
            learning_submit_assessment($pdo, $user_id, $_POST);
            break;

        case 'retry_course':
            learning_retry_course($pdo, $centre_id, $user_id, $_POST);
            break;

        // Add more actions as needed
    }
}

/**
 * Helper to sanitize input
 */
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
?>
