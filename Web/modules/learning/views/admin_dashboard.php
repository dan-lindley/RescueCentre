<?php
// modules/learning/views/admin_dashboard.php

if (!defined('APP_LOADED')) {
    exit;
}

require_once __DIR__ . '/../controllers/learning_lib.php';
require_once __DIR__ . '/../../../core/icons.php';
learning_bootstrap($pdo);

if (!learning_require_admin()) {
    return;
}

$centre_id = learning_get_centre_id();
$user_role = learning_get_user_role();
$canCreatePlatformCourses = learning_is_platform_admin($user_role);
$tab = (string)($_GET['tab'] ?? 'courses');
if (!in_array($tab, ['courses', 'create', 'suites', 'reports'], true)) {
    $tab = 'courses';
}

$courses = learning_get_centre_courses($pdo, $centre_id, false);
$assignableCourses = learning_get_assignable_courses($pdo, $centre_id);
$suites = learning_get_suites($pdo, $centre_id);
$suiteCourses = learning_get_suite_courses($pdo, $centre_id);
$assignments = learning_get_assignments($pdo, $centre_id);
$roles = learning_get_rescue_roles($pdo, $centre_id);
$activeCourses = array_filter($courses, static fn($course) => !empty($course['is_active']));
$archivedCourses = array_filter($courses, static fn($course) => empty($course['is_active']));
?>

<link rel="stylesheet" href="modules/learning/css/learning.css?v=20260511-suites-top-level">

<div class="learning-shell rc-stack">
    <div class="content-title">
        <div class="title">
            <div class="txt">
                <h2>Learning Management</h2>
                <p>Manage internal courses, platform courses, suites, assignments, and learner progress.</p>
            </div>
        </div>
    </div>

    <?= learning_flash() ?>

    <div class="rc-stat-grid">
        <div class="rc-stat">
            <strong><?= count($activeCourses) ?></strong>
            <span>Active Courses</span>
        </div>
        <div class="rc-stat">
            <strong><?= count($suites) ?></strong>
            <span>Suites</span>
        </div>
        <div class="rc-stat">
            <strong><?= count($assignments) ?></strong>
            <span>Assignments</span>
        </div>
        <div class="rc-stat">
            <strong><?= count($archivedCourses) ?></strong>
            <span>Archived Courses</span>
        </div>
    </div>

    <div class="rc-tabs">
        <a class="rc-tab <?= $tab === 'courses' ? 'is-active' : '' ?>" href="module.php?module=learning&view=admin_dashboard&tab=courses">Courses</a>
        <a class="rc-tab <?= $tab === 'create' ? 'is-active' : '' ?>" href="module.php?module=learning&view=admin_dashboard&tab=create">Create Course</a>
        <a class="rc-tab <?= $tab === 'suites' ? 'is-active' : '' ?>" href="module.php?module=learning&view=admin_dashboard&tab=suites">Suites &amp; Assignments</a>
        <a class="rc-tab <?= $tab === 'reports' ? 'is-active' : '' ?>" href="module.php?module=learning&view=admin_dashboard&tab=reports">Reports</a>
    </div>

    <?php if ($tab === 'courses'): ?>
        <div class="rc-panel rc-stack">
            <div class="rc-split-head">
                <div>
                    <h3>Courses</h3>
                    <p class="rc-muted">Create the course first, then open it to add pages and assessments.</p>
                </div>
                <a href="module.php?module=learning&view=admin_dashboard&tab=create" class="btn blue">Create Course</a>
            </div>

            <div class="rc-table-scroll">
                <table class="rc-table row-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Pass Mark</th>
                            <th>Visibility</th>
                            <th>Status</th>
                            <th class="rc-table-actions" style="width: 190px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$courses): ?>
                            <tr><td colspan="6" class="rc-muted">No courses yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><strong><?= sanitize_output($course['title']) ?></strong></td>
                                    <td><?= sanitize_output(substr((string)($course['description'] ?? ''), 0, 90)) ?></td>
                                    <td><?= (int)$course['pass_mark_percent'] ?>%</td>
                                    <td><span class="rc-badge"><?= sanitize_output(($course['visibility'] ?? '') === 'platform' || !empty($course['is_platform_course']) ? 'Rescue Centre' : ucfirst((string)$course['visibility'])) ?></span></td>
                                    <td>
                                        <span class="rc-badge <?= !empty($course['is_active']) ? 'ok' : 'na' ?>">
                                            <?= !empty($course['is_active']) ? 'Active' : 'Archived' ?>
                                        </span>
                                    </td>
                                    <td class="rc-table-actions" style="width: 190px;">
                                        <div class="rc-actions" style="flex-wrap: nowrap;">
                                            <a href="module.php?module=learning&view=edit_course&course_id=<?= (int)$course['course_id'] ?>" class="btn blue">Edit</a>
                                            <form method="post" action="modules/learning/controllers/learning_handler.php" style="margin:0;">
                                                <input type="hidden" name="action" value="<?= !empty($course['is_active']) ? 'archive_course' : 'restore_course' ?>">
                                                <input type="hidden" name="course_id" value="<?= (int)$course['course_id'] ?>">
                                                <input type="hidden" name="return_view" value="admin_dashboard">
                                                <input type="hidden" name="return_tab" value="courses">
                                                <?php if (!empty($course['is_active'])): ?>
                                                    <button type="submit" class="btn red" title="Delete course" aria-label="Delete course" onclick="return confirm('Archive this course? Learners will no longer see it.');"><?= rc_icon('delete', 15, 'icon', 'aria-hidden="true" focusable="false"') ?></button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn green">Restore</button>
                                                <?php endif; ?>
                                            </form>
                                            <?php if (empty($course['is_active'])): ?>
                                                <form method="post" action="modules/learning/controllers/learning_handler.php" style="margin:0;">
                                                    <input type="hidden" name="action" value="delete_course">
                                                    <input type="hidden" name="course_id" value="<?= (int)$course['course_id'] ?>">
                                                    <input type="hidden" name="return_view" value="admin_dashboard">
                                                    <input type="hidden" name="return_tab" value="courses">
                                                    <button type="submit" class="btn red" title="Permanently delete course" aria-label="Permanently delete course" onclick="return confirm('Permanently delete this archived course and its content/progress records? This cannot be undone.');"><?= rc_icon('delete', 15, 'icon', 'aria-hidden="true" focusable="false"') ?></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'create'): ?>
        <div class="rc-panel">
            <h3>Create Course</h3>
            <form method="post" action="modules/learning/controllers/learning_handler.php" class="xform">
                <input type="hidden" name="action" value="create_course">
                <input type="hidden" name="return_view" value="admin_dashboard">

                <div class="xform-grid">
                    <div class="xform-field span-4">
                        <label class="xform-label">Course Title *</label>
                        <input type="text" name="title" class="xform-input" required>
                    </div>
                    <div class="xform-field span-4">
                        <label class="xform-label">Description</label>
                        <textarea name="description" class="xform-input" rows="5"></textarea>
                    </div>
                    <div class="xform-field">
                        <label class="xform-label">Pass Mark (%)</label>
                        <input type="number" name="pass_mark_percent" class="xform-input" value="70" min="0" max="100">
                    </div>
                    <div class="xform-field">
                        <label class="xform-label">Max Attempts</label>
                        <input type="number" name="max_attempts" class="xform-input" value="3" min="1">
                    </div>
                    <div class="xform-field span-2">
                        <label class="xform-label">Visibility</label>
                        <select name="visibility" class="xform-input">
                            <option value="private">Private</option>
                            <option value="centre">Centre</option>
                            <option value="global">Global</option>
                            <?php if ($canCreatePlatformCourses): ?>
                                <option value="platform">Rescue Centre</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="xform-actions">
                    <button type="submit" class="btn blue">Create Course</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'suites'): ?>
        <div class="learning-suites-top-grid">
            <div class="rc-panel rc-stack">
                <div class="rc-split-head">
                    <div>
                        <h3>Suites</h3>
                        <p class="rc-muted">Suites are top-level learning paths that can contain multiple courses.</p>
                    </div>
                    <span class="rc-badge"><?= count($suites) ?> suites</span>
                </div>

                <form method="post" action="modules/learning/controllers/learning_handler.php" class="xform">
                    <input type="hidden" name="action" value="create_suite">
                    <input type="hidden" name="return_view" value="admin_dashboard">
                    <input type="hidden" name="return_tab" value="suites">
                    <div class="xform-grid">
                        <div class="xform-field span-4">
                            <label class="xform-label">Suite Name *</label>
                            <input type="text" name="title" class="xform-input" placeholder="Basic Core" required>
                        </div>
                        <div class="xform-field span-4">
                            <label class="xform-label">Description</label>
                            <textarea name="description" class="xform-input" rows="3" placeholder="What this suite is for"></textarea>
                        </div>
                        <div class="xform-field span-2">
                            <label class="xform-label">Visibility</label>
                            <select name="visibility" class="xform-input">
                                <option value="private">Private</option>
                                <option value="centre">Centre</option>
                                <option value="global">Global</option>
                            </select>
                        </div>
                        <div class="xform-field span-2">
                            <label class="xform-label">Status</label>
                            <select name="is_active" class="xform-input">
                                <option value="1">Active</option>
                                <option value="0">Archived</option>
                            </select>
                        </div>
                        <div class="xform-actions">
                            <button type="submit" class="btn blue">Create Suite</button>
                        </div>
                    </div>
                </form>

                <?php if (!$suites): ?>
                    <div class="rc-alert blue">No suites yet. Create one above, then add courses to it.</div>
                <?php else: ?>
                    <div class="rc-list learning-suite-list">
                        <?php foreach ($suites as $suite): ?>
                            <?php
                                $suiteId = (int)$suite['suite_id'];
                                $assignedCourseIds = array_map(
                                    static fn($suiteCourse): int => (int)$suiteCourse['course_id'],
                                    $suiteCourses[$suiteId] ?? []
                                );
                                $availableCoursesForSuite = array_values(array_filter(
                                    $assignableCourses,
                                    static fn($availableCourse): bool => !in_array((int)$availableCourse['course_id'], $assignedCourseIds, true)
                                ));
                            ?>
                            <div class="rc-card learning-suite-card">
                                <div class="learning-suite-head">
                                    <div>
                                        <strong><?= sanitize_output($suite['title']) ?></strong>
                                        <?php if (!empty($suite['description'])): ?>
                                            <p class="rc-muted"><?= sanitize_output($suite['description']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="learning-suite-meta">
                                        <span class="rc-chip blue"><?= (int)$suite['course_count'] ?> course<?= (int)$suite['course_count'] === 1 ? '' : 's' ?></span>
                                        <span class="rc-chip"><?= sanitize_output(ucfirst((string)$suite['visibility'])) ?></span>
                                        <span class="rc-chip <?= !empty($suite['is_active']) ? 'good' : '' ?>"><?= !empty($suite['is_active']) ? 'Active' : 'Archived' ?></span>
                                    </div>
                                </div>

                                <form method="post" action="modules/learning/controllers/learning_handler.php" class="xform">
                                    <input type="hidden" name="action" value="update_suite">
                                    <input type="hidden" name="return_view" value="admin_dashboard">
                                    <input type="hidden" name="return_tab" value="suites">
                                    <input type="hidden" name="suite_id" value="<?= $suiteId ?>">
                                    <div class="xform-grid">
                                        <div class="xform-field span-4">
                                            <label class="xform-label">Suite Name</label>
                                            <input type="text" name="title" class="xform-input" value="<?= sanitize_output($suite['title']) ?>" required>
                                        </div>
                                        <div class="xform-field span-4">
                                            <label class="xform-label">Description</label>
                                            <textarea name="description" class="xform-input" rows="2"><?= sanitize_output($suite['description'] ?? '') ?></textarea>
                                        </div>
                                        <div class="xform-field span-2">
                                            <label class="xform-label">Visibility</label>
                                            <select name="visibility" class="xform-input">
                                                <option value="private" <?= (string)$suite['visibility'] === 'private' ? 'selected' : '' ?>>Private</option>
                                                <option value="centre" <?= (string)$suite['visibility'] === 'centre' ? 'selected' : '' ?>>Centre</option>
                                                <option value="global" <?= (string)$suite['visibility'] === 'global' ? 'selected' : '' ?>>Global</option>
                                            </select>
                                        </div>
                                        <div class="xform-field span-2">
                                            <label class="xform-label">Status</label>
                                            <select name="is_active" class="xform-input">
                                                <option value="1" <?= !empty($suite['is_active']) ? 'selected' : '' ?>>Active</option>
                                                <option value="0" <?= empty($suite['is_active']) ? 'selected' : '' ?>>Archived</option>
                                            </select>
                                        </div>
                                        <div class="xform-actions">
                                            <button type="submit" class="btn blue">Save Suite</button>
                                        </div>
                                    </div>
                                </form>

                                <?php if (!empty($suiteCourses[$suiteId])): ?>
                                    <div class="learning-suite-course-list">
                                        <?php foreach ($suiteCourses[$suiteId] as $suiteCourse): ?>
                                            <div class="learning-suite-course-row">
                                                <span><?= sanitize_output($suiteCourse['course_title']) ?></span>
                                                <form method="post" action="modules/learning/controllers/learning_handler.php" class="learning-inline-form">
                                                    <input type="hidden" name="action" value="remove_course_from_suite">
                                                    <input type="hidden" name="return_view" value="admin_dashboard">
                                                    <input type="hidden" name="return_tab" value="suites">
                                                    <input type="hidden" name="suite_course_id" value="<?= (int)$suiteCourse['suite_course_id'] ?>">
                                                    <button type="submit" class="btn red" onclick="return confirm('Remove this course from the suite?')">Remove</button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="rc-alert amber">No courses in this suite yet.</div>
                                <?php endif; ?>

                                <form method="post" action="modules/learning/controllers/learning_handler.php" class="learning-suite-add-course-form">
                                    <input type="hidden" name="action" value="add_course_to_suite">
                                    <input type="hidden" name="return_view" value="admin_dashboard">
                                    <input type="hidden" name="return_tab" value="suites">
                                    <input type="hidden" name="suite_id" value="<?= $suiteId ?>">
                                    <select name="course_id" class="xform-input" required>
                                        <option value="">Add course...</option>
                                        <?php foreach ($availableCoursesForSuite as $availableCourse): ?>
                                            <option value="<?= (int)$availableCourse['course_id'] ?>"><?= sanitize_output($availableCourse['title']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn blue" <?= !$availableCoursesForSuite ? 'disabled' : '' ?>>Add</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="rc-panel rc-stack">
                <div class="rc-split-head">
                    <div>
                        <h3>Assignments</h3>
                        <p class="rc-muted">Assign courses or suites to all users, one or more roles, or an individual user ID.</p>
                    </div>
                    <span class="rc-badge"><?= count($assignments) ?> total</span>
                </div>

                <form method="post" action="modules/learning/controllers/learning_handler.php" class="xform">
                    <input type="hidden" name="action" value="create_assignment">
                    <input type="hidden" name="return_view" value="admin_dashboard">
                    <input type="hidden" name="return_tab" value="suites">
                    <div class="xform-grid">
                        <input type="hidden" name="assignable_type" id="learningAssignableType" value="course">
                        <input type="hidden" name="assignable_id" id="learningAssignableId" value="">
                        <div class="xform-field span-4">
                            <label class="xform-label">Assign Course or Suite</label>
                            <select id="learningAssignablePicker" class="xform-input" required>
                                <option value="">Choose item...</option>
                                <optgroup label="Courses">
                                    <?php foreach ($assignableCourses as $availableCourse): ?>
                                        <option value="course:<?= (int)$availableCourse['course_id'] ?>">Course: <?= sanitize_output($availableCourse['title']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php if ($suites): ?>
                                    <optgroup label="Suites">
                                        <?php foreach ($suites as $suite): ?>
                                            <option value="suite:<?= (int)$suite['suite_id'] ?>">Suite: <?= sanitize_output($suite['title']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="xform-field span-2">
                            <label class="xform-label">Target Type</label>
                            <select name="target_type" id="learningTargetType" class="xform-input">
                                <option value="role">All / Role(s)</option>
                                <option value="user">Individual User ID</option>
                            </select>
                        </div>
                        <div class="xform-field span-2 learning-role-target-field">
                            <label class="xform-label">Role(s)</label>
                            <select name="target_values[]" class="xform-input" multiple size="5">
                                <option value="ALL">ALL users</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= sanitize_output((string)$role['role_value']) ?>"><?= sanitize_output((string)$role['role_label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="rc-muted">Hold Ctrl/Cmd to choose multiple roles.</small>
                        </div>
                        <div class="xform-field span-2 learning-user-target-field" style="display:none;">
                            <label class="xform-label">User ID</label>
                            <input type="text" name="target_value" class="xform-input" placeholder="123">
                        </div>
                        <div class="xform-field span-2">
                            <label class="xform-label">Due Date</label>
                            <input type="date" name="due_date" class="xform-input">
                        </div>
                        <div class="xform-field span-2">
                            <label class="xform-label">Mandatory?</label>
                            <select name="is_mandatory" class="xform-input">
                                <option value="1">Mandatory</option>
                                <option value="0">Optional</option>
                            </select>
                        </div>
                        <div class="xform-actions">
                            <button type="submit" class="btn blue">Create Assignment</button>
                        </div>
                    </div>
                </form>

                <?php if (!$assignments): ?>
                    <div class="rc-alert blue">No assignments yet.</div>
                <?php else: ?>
                    <div class="rc-list learning-assignment-list">
                        <?php foreach ($assignments as $assignment): ?>
                            <?php $targetLabel = ($assignment['target_type'] === 'role' && $assignment['target_value'] === 'ALL') ? 'ALL users' : $assignment['target_type'] . ': ' . $assignment['target_value']; ?>
                            <div class="rc-card learning-assignment-row <?= empty($assignment['is_active']) ? 'is-muted' : '' ?>">
                                <div class="learning-assignment-main">
                                    <strong><?= sanitize_output(ucfirst((string)$assignment['assignable_type'])) ?>: <?= sanitize_output($assignment['assignable_title'] ?: ('#' . $assignment['assignable_id'])) ?></strong>
                                    <small class="rc-muted">Assigned to <?= sanitize_output($targetLabel) ?><?= !empty($assignment['due_date']) ? ' • Due ' . sanitize_output($assignment['due_date']) : '' ?></small>
                                </div>
                                <div class="learning-assignment-actions">
                                    <span class="rc-chip <?= !empty($assignment['is_mandatory']) ? 'warn' : '' ?>"><?= !empty($assignment['is_mandatory']) ? 'Mandatory' : 'Optional' ?></span>
                                    <span class="rc-chip <?= !empty($assignment['is_active']) ? 'good' : '' ?>"><?= !empty($assignment['is_active']) ? 'Active' : 'Disabled' ?></span>
                                    <?php if (!empty($assignment['is_active'])): ?>
                                        <form method="post" action="modules/learning/controllers/learning_handler.php" class="learning-inline-form">
                                            <input type="hidden" name="action" value="delete_assignment">
                                            <input type="hidden" name="return_view" value="admin_dashboard">
                                            <input type="hidden" name="return_tab" value="suites">
                                            <input type="hidden" name="assignment_id" value="<?= (int)$assignment['assignment_id'] ?>">
                                            <button type="submit" class="btn red" onclick="return confirm('Disable this assignment?')">Disable</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
        (function () {
            var picker = document.getElementById('learningAssignablePicker');
            var typeInput = document.getElementById('learningAssignableType');
            var idInput = document.getElementById('learningAssignableId');
            var targetType = document.getElementById('learningTargetType');
            var roleField = document.querySelector('.learning-role-target-field');
            var userField = document.querySelector('.learning-user-target-field');
            if (picker && typeInput && idInput) {
                var syncAssignable = function () {
                    var parts = String(picker.value || '').split(':');
                    typeInput.value = parts[0] || 'course';
                    idInput.value = parts[1] || '';
                };
                picker.addEventListener('change', syncAssignable);
                syncAssignable();
            }
            if (targetType && roleField && userField) {
                var syncTarget = function () {
                    var isUser = targetType.value === 'user';
                    roleField.style.display = isUser ? 'none' : '';
                    userField.style.display = isUser ? '' : 'none';
                };
                targetType.addEventListener('change', syncTarget);
                syncTarget();
            }
        })();
        </script>
    <?php endif; ?>

    <?php if ($tab === 'reports'): ?>
        <div class="rc-panel">
            <h3>Reports</h3>
            <div class="rc-alert blue">Reporting will use learner progress records from completed and in-progress courses.</div>
        </div>
    <?php endif; ?>
</div>
