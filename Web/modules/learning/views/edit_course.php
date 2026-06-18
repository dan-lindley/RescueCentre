<?php
// modules/learning/views/edit_course.php

if (!defined('APP_LOADED')) {
    exit;
}

require_once __DIR__ . '/../controllers/learning_lib.php';
learning_bootstrap($pdo);

if (!learning_require_admin()) {
    return;
}

$centre_id = learning_get_centre_id();
$user_role = learning_get_user_role();
$canCreatePlatformCourses = learning_is_platform_admin($user_role);
$course_id = (int)($_GET['course_id'] ?? 0);
$section = (string)($_GET['section'] ?? 'details');
if (!in_array($section, ['details', 'content', 'assessment', 'questions'], true)) {
    $section = 'details';
}

try {
    $stmt = $pdo->prepare("
        SELECT *
        FROM rescue_learning_courses
        WHERE course_id = ?
          AND (owner_centre_id = ? OR visibility = 'platform' OR is_platform_course = 1)
    ");
    $stmt->execute([$course_id, $centre_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        throw new Exception('Course not found');
    }

    if (((int)$course['owner_centre_id'] !== (int)$centre_id) && empty($course['is_platform_course']) && (($course['visibility'] ?? '') !== 'platform')) {
        throw new Exception('Course not found');
    }
    if ((((int)$course['owner_centre_id'] !== (int)$centre_id) || !empty($course['is_platform_course']) || (($course['visibility'] ?? '') === 'platform')) && !learning_is_platform_admin($user_role)) {
        throw new Exception('You do not have permission to edit this platform course');
    }

    $pages = learning_get_course_pages($pdo, $course_id);
    $assessment = learning_get_course_assessment($pdo, $course_id);
    $questions = $assessment ? learning_get_assessment_questions_with_answers($pdo, (int)$assessment['assessment_id']) : [];
    $questionTypeLabels = learning_question_type_labels();
} catch (Throwable $e) {
    echo '<div class="rc-alert red">' . sanitize_output($e->getMessage()) . '</div>';
    return;
}

$baseUrl = 'module.php?module=learning&view=edit_course&course_id=' . (int)$course_id;
$sectionUrl = static fn(string $name): string => $baseUrl . '&section=' . urlencode($name);
$editPageId = (int)($_GET['page_id'] ?? 0);
$editingPage = null;
if ($editPageId > 0) {
    foreach ($pages as $candidatePage) {
        if ((int)$candidatePage['page_id'] === $editPageId) {
            $editingPage = $candidatePage;
            break;
        }
    }
}
?>

<link rel="stylesheet" href="modules/learning/css/learning.css?v=20260511-learning-pages">

<div class="learning-shell rc-stack">
    <div class="content-title">
        <div class="title">
            <div class="txt">
                <h2><?= sanitize_output($course['title']) ?></h2>
                <p>Build course details, pages, assessment, and publishing settings.</p>
            </div>
        </div>
        <div class="btns">
            <a href="module.php?module=learning&view=admin_dashboard" class="btn grey">Back to Learning</a>
        </div>
    </div>

    <?= learning_flash() ?>

    <div class="rc-tabs">
        <a class="rc-tab <?= $section === 'details' ? 'is-active' : '' ?>" href="<?= sanitize_output($sectionUrl('details')) ?>">Details</a>
        <a class="rc-tab <?= $section === 'content' ? 'is-active' : '' ?>" href="<?= sanitize_output($sectionUrl('content')) ?>">Content Pages</a>
        <a class="rc-tab <?= $section === 'assessment' ? 'is-active' : '' ?>" href="<?= sanitize_output($sectionUrl('assessment')) ?>">Assessment</a>
        <a class="rc-tab <?= $section === 'questions' ? 'is-active' : '' ?>" href="<?= sanitize_output($sectionUrl('questions')) ?>">Questions &amp; Answers</a>
    </div>

    <?php if ($section === 'details'): ?>
        <div class="rc-panel">
            <form method="post" action="modules/learning/controllers/learning_handler.php" class="xform">
                <input type="hidden" name="action" value="update_course">
                <input type="hidden" name="return_view" value="edit_course">
                <input type="hidden" name="return_section" value="details">
                <input type="hidden" name="return_course_id" value="<?= (int)$course_id ?>">
                <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">

                <div class="xform-grid">
                    <div class="xform-field span-4">
                        <label class="xform-label">Course Title *</label>
                        <input type="text" name="title" class="xform-input" value="<?= sanitize_output($course['title']) ?>" required>
                    </div>

                    <div class="xform-field span-4">
                        <label class="xform-label">Description</label>
                        <textarea name="description" class="xform-input" rows="5"><?= sanitize_output($course['description'] ?? '') ?></textarea>
                    </div>

                    <div class="xform-field">
                        <label class="xform-label">Pass Mark (%)</label>
                        <input type="number" name="pass_mark_percent" class="xform-input" value="<?= (int)$course['pass_mark_percent'] ?>" min="0" max="100">
                    </div>

                    <div class="xform-field">
                        <label class="xform-label">Max Attempts</label>
                        <input type="number" name="max_attempts" class="xform-input" value="<?= (int)$course['max_attempts'] ?>" min="1">
                    </div>

                    <div class="xform-field">
                        <label class="xform-label">Visibility</label>
                        <select name="visibility" class="xform-input">
                            <option value="private" <?= $course['visibility'] === 'private' ? 'selected' : '' ?>>Private</option>
                            <option value="centre" <?= $course['visibility'] === 'centre' ? 'selected' : '' ?>>Centre</option>
                            <option value="global" <?= $course['visibility'] === 'global' ? 'selected' : '' ?>>Global</option>
                            <?php if ($canCreatePlatformCourses): ?>
                                <option value="platform" <?= ($course['visibility'] ?? '') === 'platform' || !empty($course['is_platform_course']) ? 'selected' : '' ?>>Rescue Centre</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="xform-field">
                        <label class="xform-label">Status</label>
                        <select name="is_active" class="xform-input">
                            <option value="1" <?= !empty($course['is_active']) ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= empty($course['is_active']) ? 'selected' : '' ?>>Archived</option>
                        </select>
                    </div>
                </div>

                <div class="xform-actions">
                    <button type="submit" class="btn blue">Save Details</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($section === 'content'): ?>
        <div class="learning-content-shell rc-stack">
            <div class="rc-split-head">
                <div>
                    <h3>Course Pages</h3>
                    <p class="rc-muted">Pages appear to learners in this order. Drag the handle to reorder.</p>
                </div>
                <span class="rc-badge"><?= count($pages) ?> pages</span>
            </div>

            <div class="learning-content-grid learning-content-single">
                <div class="rc-stack learning-pages-fullwidth">
                    <?php if (!$pages): ?>
                        <div class="rc-alert blue">No pages yet. Add the first page using the form on this screen.</div>
                    <?php else: ?>
                        <div class="rc-list learning-page-row-list" id="learningPageSortable" data-course-id="<?= (int)$course_id ?>">
                            <?php foreach ($pages as $idx => $page): ?>
                                <?php
                                    $pagePreview = trim(strip_tags((string)($page['page_content'] ?? '')));
                                    if ($pagePreview !== '' && function_exists('mb_strlen') && mb_strlen($pagePreview) > 95) {
                                        $pagePreview = mb_substr($pagePreview, 0, 95) . '...';
                                    } elseif ($pagePreview !== '' && strlen($pagePreview) > 95) {
                                        $pagePreview = substr($pagePreview, 0, 95) . '...';
                                    }
                                    $isEditing = $editingPage && (int)$editingPage['page_id'] === (int)$page['page_id'];
                                ?>
                                <div class="learning-page-row-card <?= $isEditing ? 'is-selected' : '' ?> <?= empty($page['is_active']) ? 'is-muted' : '' ?>" draggable="true" data-page-id="<?= (int)$page['page_id'] ?>">
                                    <button type="button" class="learning-page-drag-handle" aria-label="Drag to reorder" title="Drag to reorder">☰</button>

                                    <div class="learning-page-row-main">
                                        <div class="learning-page-title-line">
                                            <strong>Page <?= $idx + 1 ?></strong>
                                            <span><?= sanitize_output($page['page_title']) ?></span>
                                        </div>
                                        <?php if ($pagePreview !== ''): ?>
                                            <p class="rc-muted"><?= sanitize_output($pagePreview) ?></p>
                                        <?php else: ?>
                                            <p class="rc-muted">No text content has been added yet.</p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="learning-page-row-meta">
                                        <span class="rc-chip blue"><?= sanitize_output(ucfirst((string)$page['page_type'])) ?></span>
                                        <span class="rc-chip <?= !empty($page['is_required']) ? 'warn' : '' ?>"><?= !empty($page['is_required']) ? 'Required' : 'Optional' ?></span>
                                        <span class="rc-chip <?= !empty($page['is_active']) ? 'good' : '' ?>"><?= !empty($page['is_active']) ? 'Active' : 'Hidden' ?></span>
                                        <?php if (!empty($page['media_url'])): ?>
                                            <span class="rc-chip purple">Media</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="rc-actions learning-page-row-actions">
                                        <a class="btn blue" href="<?= sanitize_output($sectionUrl('content') . '&page_id=' . (int)$page['page_id']) ?>">Edit</a>
                                        <form method="post" action="modules/learning/controllers/learning_handler.php" class="learning-inline-form">
                                            <input type="hidden" name="action" value="delete_page">
                                            <input type="hidden" name="return_view" value="edit_course">
                                            <input type="hidden" name="return_section" value="content">
                                            <input type="hidden" name="return_course_id" value="<?= (int)$course_id ?>">
                                            <input type="hidden" name="page_id" value="<?= (int)$page['page_id'] ?>">
                                            <button type="submit" class="btn red" onclick="return confirm('Delete this page?')">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <form id="learningPageOrderForm" method="post" action="modules/learning/controllers/learning_handler.php" hidden>
                            <input type="hidden" name="action" value="reorder_pages">
                            <input type="hidden" name="return_view" value="edit_course">
                            <input type="hidden" name="return_section" value="content">
                            <input type="hidden" name="return_course_id" value="<?= (int)$course_id ?>">
                            <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                            <input type="hidden" name="page_order" id="learningPageOrderInput" value="">
                        </form>
                    <?php endif; ?>
                </div>

                <div class="rc-panel learning-editor-panel learning-editor-below">
                    <div class="rc-split-head">
                        <div>
                            <h3><?= $editingPage ? 'Edit Page' : 'Add Content Page' ?></h3>
                            <p class="rc-muted"><?= $editingPage ? 'Update this course page.' : 'Create a new page for this course.' ?></p>
                        </div>
                        <?php if ($editingPage): ?>
                            <a class="btn grey" href="<?= sanitize_output($sectionUrl('content')) ?>">Cancel Edit</a>
                        <?php endif; ?>
                    </div>

                    <form method="post" action="modules/learning/controllers/learning_handler.php" class="xform">
                        <input type="hidden" name="action" value="<?= $editingPage ? 'update_page' : 'create_page' ?>">
                        <input type="hidden" name="return_view" value="edit_course">
                        <input type="hidden" name="return_section" value="content">
                        <input type="hidden" name="return_course_id" value="<?= (int)$course_id ?>">
                        <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                        <?php if ($editingPage): ?>
                            <input type="hidden" name="page_id" value="<?= (int)$editingPage['page_id'] ?>">
                        <?php endif; ?>

                        <div class="xform-grid">
                            <div class="xform-field span-4">
                                <label class="xform-label">Page Title *</label>
                                <input type="text" name="page_title" class="xform-input" value="<?= sanitize_output($editingPage['page_title'] ?? '') ?>" required>
                            </div>

                            <div class="xform-field span-2">
                                <label class="xform-label">Page Type</label>
                                <?php $selectedType = (string)($editingPage['page_type'] ?? 'text'); ?>
                                <select name="page_type" class="xform-input">
                                    <?php foreach (['text', 'video', 'file', 'link', 'mixed'] as $type): ?>
                                        <option value="<?= sanitize_output($type) ?>" <?= $selectedType === $type ? 'selected' : '' ?>><?= sanitize_output(ucfirst($type)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="xform-field span-2">
                                <label class="xform-label">Media URL / File Link</label>
                                <input type="text" name="media_url" class="xform-input" value="<?= sanitize_output($editingPage['media_url'] ?? '') ?>" placeholder="https://...">
                            </div>

                            <div class="xform-field span-4">
                                <label class="xform-label">Page Content</label>
                                <textarea name="page_content" id="learning_page_content" class="xform-input learning-rich-text" rows="12"><?= sanitize_output($editingPage['page_content'] ?? '') ?></textarea>
                            </div>

                            <div class="xform-field span-2">
                                <label class="xform-label">Required?</label>
                                <select name="is_required" class="xform-input">
                                    <option value="1" <?= !isset($editingPage['is_required']) || !empty($editingPage['is_required']) ? 'selected' : '' ?>>Required</option>
                                    <option value="0" <?= isset($editingPage['is_required']) && empty($editingPage['is_required']) ? 'selected' : '' ?>>Optional</option>
                                </select>
                            </div>

                            <div class="xform-field span-2">
                                <label class="xform-label">Status</label>
                                <select name="is_active" class="xform-input">
                                    <option value="1" <?= !isset($editingPage['is_active']) || !empty($editingPage['is_active']) ? 'selected' : '' ?>>Active</option>
                                    <option value="0" <?= isset($editingPage['is_active']) && empty($editingPage['is_active']) ? 'selected' : '' ?>>Hidden</option>
                                </select>
                            </div>
                        </div>

                        <div class="xform-actions">
                            <button type="submit" class="btn blue"><?= $editingPage ? 'Save Page' : 'Add Page' ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.3.0/tinymce.min.js" integrity="sha512-RUZ2d69UiTI+LdjfDCxqJh5HfjmOcouct56utQNVRjr90Ea8uHQa+gCxvxDTC9fFvIGP+t4TDDJWNTRV48tBpQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
            <script>
            function learningShowTinyMceError(message) {
                var target = document.getElementById('learning_page_content');
                if (!target) return;
                var alert = document.getElementById('learningTinyMceError');
                if (!alert) {
                    alert = document.createElement('div');
                    alert.id = 'learningTinyMceError';
                    alert.className = 'rc-alert amber';
                    target.parentNode.insertBefore(alert, target);
                }
                alert.textContent = message;
            }

            function learningInitTinyMce() {
                var target = document.getElementById('learning_page_content');
                if (!target) {
                    learningShowTinyMceError('TinyMCE could not find the Page Content box.');
                    return;
                }

                if (!window.tinymce) {
                    learningShowTinyMceError('TinyMCE did not load.');
                    return;
                }

                target.removeAttribute('aria-hidden');
                target.disabled = false;
                target.style.display = '';
                target.style.visibility = 'visible';

                var isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
                var editorContentStyle = isDarkMode
                    ? 'body{background:#111827;color:#e5edf4;font-family:Inter,Arial,sans-serif;font-size:15px;line-height:1.65;padding:16px;}a{color:#7dd3fc;}h1,h2,h3,h4{color:#f8fafc;}blockquote{border-left:4px solid #38bdf8;color:#cbd5e1;margin-left:0;padding-left:14px;}table{border-collapse:collapse;width:100%;}td,th{border:1px solid #334155;padding:8px;}th{background:#1f2937;}'
                    : 'body{background:#ffffff;color:#1f2937;font-family:Inter,Arial,sans-serif;font-size:15px;line-height:1.65;padding:16px;}a{color:#2563eb;}h1,h2,h3,h4{color:#172554;}blockquote{border-left:4px solid #2563eb;color:#475569;margin-left:0;padding-left:14px;}table{border-collapse:collapse;width:100%;}td,th{border:1px solid #cbd5e1;padding:8px;}th{background:#f1f5f9;}';

                var editorSettings = {
                    plugins: 'image table lists media link code',
                    toolbar: 'undo redo | blocks | bold italic forecolor | align | outdent indent | numlist bullist | table image link | code',
                    menubar: false,
                    skin: isDarkMode ? 'oxide-dark' : 'oxide',
                    content_css: isDarkMode ? 'dark' : 'default',
                    valid_elements: '*[*]',
                    extended_valid_elements: '*[*]',
                    valid_children: '+body[style]',
                    content_style: editorContentStyle,
                    height: 600,
                    branding: false,
                    promotion: false,
                    automatic_uploads: true,
                    images_upload_url: 'modules/learning/controllers/upload_image.php?course_id=<?= (int)$course_id ?>',
                    images_upload_credentials: true,
                    paste_data_images: false,
                    image_dimensions: false,
                    object_resizing: 'img',
                    image_title: true,
                    image_description: true,
                    license_key: 'gpl',
                    setup: function (editor) {
                        editor.on('init', function () {
                            var alert = document.getElementById('learningTinyMceError');
                            if (alert) alert.remove();
                        });
                        editor.on('change keyup', function () {
                            editor.save();
                        });
                    }
                };

                try {
                    if (tinymce.get('learning_page_content')) {
                        tinymce.get('learning_page_content').remove();
                    }

                    var editor = new tinymce.Editor('learning_page_content', editorSettings, tinymce.EditorManager);
                    tinymce.EditorManager.add(editor);
                    var renderResult = editor.render();

                    if (renderResult && typeof renderResult.then === 'function') {
                        renderResult.then(function () {
                            if (!tinymce.get('learning_page_content')) {
                                learningShowTinyMceDiagnostic(target);
                            }
                        }).catch(function (error) {
                            learningShowTinyMceError('TinyMCE render error: ' + (error && error.message ? error.message : String(error)));
                        });
                    } else {
                        window.setTimeout(function () {
                            if (!tinymce.get('learning_page_content')) {
                                learningShowTinyMceDiagnostic(target);
                            }
                        }, 500);
                    }
                } catch (error) {
                    learningShowTinyMceError('TinyMCE direct editor error: ' + (error && error.message ? error.message : String(error)));
                }
            }

            function learningShowTinyMceDiagnostic(target) {
                learningShowTinyMceError(
                    'TinyMCE loaded but did not attach to the Page Content box. Version: ' +
                    (tinymce.majorVersion || 'unknown') + '.' + (tinymce.minorVersion || 'unknown') +
                    '. Textarea display: ' + getComputedStyle(target).display +
                    ', visibility: ' + getComputedStyle(target).visibility +
                    ', disabled: ' + (target.disabled ? 'yes' : 'no') +
                    ', editor count: ' + (tinymce.editors ? tinymce.editors.length : 0) + '.'
                );
            }

            if (document.readyState === 'complete') {
                learningInitTinyMce();
            } else {
                window.addEventListener('load', learningInitTinyMce, { once: true });
            }
            </script>

            <script>
            (function () {
                var list = document.getElementById('learningPageSortable');
                var orderForm = document.getElementById('learningPageOrderForm');
                var orderInput = document.getElementById('learningPageOrderInput');
                var dragging = null;

                function savePageOrder() {
                    if (!list || !orderForm || !orderInput) return;
                    orderInput.value = Array.prototype.map.call(
                        list.querySelectorAll('.learning-page-row-card[data-page-id]'),
                        function (card) { return card.getAttribute('data-page-id'); }
                    ).join(',');
                    orderForm.submit();
                }

                if (list) {
                    list.addEventListener('dragstart', function (event) {
                        var card = event.target.closest('.learning-page-row-card');
                        if (!card) return;
                        dragging = card;
                        card.classList.add('is-dragging');
                        event.dataTransfer.effectAllowed = 'move';
                    });

                    list.addEventListener('dragover', function (event) {
                        event.preventDefault();
                        var target = event.target.closest('.learning-page-row-card');
                        if (!dragging || !target || target === dragging) return;
                        var rect = target.getBoundingClientRect();
                        var after = event.clientY > rect.top + (rect.height / 2);
                        list.insertBefore(dragging, after ? target.nextSibling : target);
                    });

                    list.addEventListener('drop', function (event) {
                        event.preventDefault();
                        if (dragging) {
                            dragging.classList.remove('is-dragging');
                            dragging = null;
                            savePageOrder();
                        }
                    });

                    list.addEventListener('dragend', function () {
                        if (dragging) {
                            dragging.classList.remove('is-dragging');
                            dragging = null;
                        }
                    });
                }
            }());
            </script>
        </div>
    <?php endif; ?>

    <?php if ($section === 'assessment'): ?>
        <div class="rc-panel rc-stack">
            <div class="rc-split-head">
                <div>
                    <h3>Assessment</h3>
                    <p class="rc-muted">Create the assessment, add questions, then add answer options to each question one at a time.</p>
                </div>
                <?php if ($assessment): ?>
                    <span class="rc-badge"><?= count($questions) ?> questions</span>
                <?php endif; ?>
            </div>

            <form method="post" action="modules/learning/controllers/learning_handler.php" class="xform">
                <input type="hidden" name="action" value="create_assessment">
                <input type="hidden" name="return_view" value="edit_course">
                <input type="hidden" name="return_section" value="assessment">
                <input type="hidden" name="return_course_id" value="<?= (int)$course_id ?>">
                <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">

                <div class="xform-grid">
                    <div class="xform-field span-2">
                        <label class="xform-label">Assessment Title *</label>
                        <input type="text" name="title" class="xform-input" value="<?= sanitize_output($assessment['title'] ?? '') ?>" required>
                    </div>
                    <div class="xform-field span-2">
                        <label class="xform-label">Instructions</label>
                        <textarea name="instructions" class="xform-input" rows="2"><?= sanitize_output($assessment['instructions'] ?? '') ?></textarea>
                    </div>
                    <div class="xform-actions">
                        <button type="submit" class="btn blue"><?= $assessment ? 'Update Assessment' : 'Create Assessment' ?></button>
                    </div>
                </div>
            </form>

            <div class="rc-card rc-card-muted">
                <div class="rc-split-head">
                    <div>
                        <h3>Assessment Settings</h3>
                        <p class="rc-muted">These settings control the learner assessment attempts for this course.</p>
                    </div>
                    <div class="rc-chip-row">
                        <span class="rc-chip blue">Pass mark <?= (int)$course['pass_mark_percent'] ?>%</span>
                        <span class="rc-chip warn"><?= (int)$course['max_attempts'] ?> attempt<?= (int)$course['max_attempts'] === 1 ? '' : 's' ?></span>
                    </div>
                </div>

                <form method="post" action="modules/learning/controllers/learning_handler.php" class="xform">
                    <input type="hidden" name="action" value="update_course">
                    <input type="hidden" name="return_view" value="edit_course">
                    <input type="hidden" name="return_section" value="assessment">
                    <input type="hidden" name="return_course_id" value="<?= (int)$course_id ?>">
                    <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                    <input type="hidden" name="title" value="<?= sanitize_output($course['title']) ?>">
                    <input type="hidden" name="description" value="<?= sanitize_output($course['description'] ?? '') ?>">
                    <input type="hidden" name="visibility" value="<?= sanitize_output($course['visibility'] ?? 'private') ?>">
                    <input type="hidden" name="is_active" value="<?= !empty($course['is_active']) ? 1 : 0 ?>">

                    <div class="xform-grid">
                        <div class="xform-field span-2">
                            <label class="xform-label">Pass Mark (%)</label>
                            <input type="number" name="pass_mark_percent" class="xform-input" value="<?= (int)$course['pass_mark_percent'] ?>" min="0" max="100">
                        </div>
                        <div class="xform-field span-2">
                            <label class="xform-label">Max Attempts</label>
                            <input type="number" name="max_attempts" class="xform-input" value="<?= (int)$course['max_attempts'] ?>" min="1">
                        </div>
                        <div class="xform-actions">
                            <button type="submit" class="btn blue">Save Assessment Settings</button>
                        </div>
                    </div>
                </form>
            </div>

            <?php if ($assessment): ?>
                <div class="rc-alert blue">Assessment shell is ready. Use the Questions &amp; Answers tab to build the quiz.</div>
            <?php else: ?>
                <div class="rc-alert amber">Create the assessment shell first, then you can add questions and answers.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($section === 'questions'): ?>
        <div class="rc-panel rc-stack">
            <div class="rc-split-head">
                <div>
                    <h3>Questions &amp; Answers</h3>
                    <p class="rc-muted">Build quiz questions and answer options. Yes/No and True/False question types create their answers automatically.</p>
                </div>
                <?php if ($assessment): ?>
                    <span class="rc-badge"><?= count($questions) ?> questions</span>
                <?php endif; ?>
            </div>

            <?php if (!$assessment): ?>
                <div class="rc-alert amber">Create the assessment shell first on the Assessment tab.</div>
            <?php else: ?>
                <div class="learning-assessment-builder-grid">
                    <div class="learning-question-column rc-stack">
                        <?php if (!$questions): ?>
                            <div class="rc-alert blue">No questions yet. Add the first question using the form beside this list.</div>
                        <?php else: ?>
                            <div class="learning-question-list">
                                <?php foreach ($questions as $idx => $question): ?>
                                    <?php
                                        $questionTypeLabel = $questionTypeLabels[$question['question_type']] ?? $question['question_type'];
                                        $answers = $question['answers'] ?? [];
                                    ?>
                                    <div class="rc-card learning-question-card-compact">
                                        <div class="learning-question-topline">
                                            <div class="learning-question-title">
                                                <strong>Q<?= $idx + 1 ?>. <?= sanitize_output($question['question_text']) ?></strong>
                                                <span class="rc-muted">
                                                    <?= count($answers) ?> answer<?= count($answers) === 1 ? '' : 's' ?>
                                                </span>
                                            </div>
                                            <div class="learning-question-meta-actions">
                                                <span class="rc-chip blue"><?= sanitize_output($questionTypeLabel) ?></span>
                                                <form method="post" action="modules/learning/controllers/learning_handler.php" class="learning-inline-form">
                                                    <input type="hidden" name="action" value="delete_question">
                                                    <input type="hidden" name="return_view" value="edit_course">
                                                    <input type="hidden" name="return_section" value="questions">
                                                    <input type="hidden" name="return_course_id" value="<?= (int)$course_id ?>">
                                                    <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                                                    <input type="hidden" name="question_id" value="<?= (int)$question['question_id'] ?>">
                                                    <button type="submit" class="btn red" onclick="return confirm('Delete this question and all its answers?');">Delete</button>
                                                </form>
                                            </div>
                                        </div>

                                        <?php if ($answers): ?>
                                            <div class="learning-answer-table">
                                                <?php foreach ($answers as $answer): ?>
                                                    <div class="learning-answer-row">
                                                        <div class="learning-answer-text">
                                                            <?= !empty($answer['is_correct']) ? '<span class="learning-correct-mark">&#10003;</span>' : '' ?>
                                                            <?= sanitize_output($answer['answer_text']) ?>
                                                        </div>
                                                        <div class="learning-answer-actions">
                                                            <form method="post" action="modules/learning/controllers/learning_handler.php" class="learning-inline-form">
                                                                <input type="hidden" name="action" value="toggle_answer_correct">
                                                                <input type="hidden" name="return_view" value="edit_course">
                                                                <input type="hidden" name="return_section" value="questions">
                                                                <input type="hidden" name="return_course_id" value="<?= (int)$course_id ?>">
                                                                <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                                                                <input type="hidden" name="answer_id" value="<?= (int)$answer['answer_id'] ?>">
                                                                <button type="submit" class="btn grey"><?= !empty($answer['is_correct']) ? 'Correct' : 'Mark correct' ?></button>
                                                            </form>
                                                            <form method="post" action="modules/learning/controllers/learning_handler.php" class="learning-inline-form">
                                                                <input type="hidden" name="action" value="delete_answer">
                                                                <input type="hidden" name="return_view" value="edit_course">
                                                                <input type="hidden" name="return_section" value="questions">
                                                                <input type="hidden" name="return_course_id" value="<?= (int)$course_id ?>">
                                                                <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                                                                <input type="hidden" name="answer_id" value="<?= (int)$answer['answer_id'] ?>">
                                                                <button type="submit" class="btn red" onclick="return confirm('Delete this answer?');">Delete</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="rc-alert amber">No answers yet. Add answer options below.</div>
                                        <?php endif; ?>

                                        <form method="post" action="modules/learning/controllers/learning_handler.php" class="xform">
                                            <input type="hidden" name="action" value="update_question">
                                            <input type="hidden" name="return_view" value="edit_course">
                                            <input type="hidden" name="return_section" value="questions">
                                            <input type="hidden" name="return_course_id" value="<?= (int)$course_id ?>">
                                            <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                                            <input type="hidden" name="question_id" value="<?= (int)$question['question_id'] ?>">
                                            <div class="xform-grid">
                                                <div class="xform-field span-4">
                                                    <label class="xform-label">Edit Question</label>
                                                    <textarea name="question_text" class="xform-input" rows="2" required><?= sanitize_output($question['question_text']) ?></textarea>
                                                </div>
                                                <div class="xform-field span-2">
                                                    <label class="xform-label">Question Type</label>
                                                    <select name="question_type" class="xform-input learning-question-type-select">
                                                        <?php foreach ($questionTypeLabels as $typeValue => $typeLabel): ?>
                                                            <option value="<?= sanitize_output($typeValue) ?>" <?= (string)$question['question_type'] === (string)$typeValue ? 'selected' : '' ?>><?= sanitize_output($typeLabel) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="xform-actions">
                                                    <button type="submit" class="btn blue">Save Question</button>
                                                </div>
                                            </div>
                                        </form>

                                        <?php if (!in_array((string)$question['question_type'], ['true_false', 'yes_no', 'yes_no_unknown'], true)): ?>
                                        <form method="post" action="modules/learning/controllers/learning_handler.php" class="learning-add-answer-form">
                                            <input type="hidden" name="action" value="create_answer">
                                            <input type="hidden" name="return_view" value="edit_course">
                                            <input type="hidden" name="return_section" value="questions">
                                            <input type="hidden" name="return_course_id" value="<?= (int)$course_id ?>">
                                            <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                                            <input type="hidden" name="question_id" value="<?= (int)$question['question_id'] ?>">
                                            <input type="text" name="answer_text" class="xform-input" placeholder="Add an answer option">
                                            <label class="learning-correct-toggle compact">
                                                <input type="checkbox" name="is_correct" value="1">
                                                Correct
                                            </label>
                                            <button type="submit" class="btn blue">Add Answer</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="rc-card learning-add-question-card">
                        <h3>Add Question</h3>
                        <form method="post" action="modules/learning/controllers/learning_handler.php" class="xform">
                            <input type="hidden" name="action" value="create_question">
                            <input type="hidden" name="return_view" value="edit_course">
                            <input type="hidden" name="return_section" value="questions">
                            <input type="hidden" name="return_course_id" value="<?= (int)$course_id ?>">
                            <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                            <input type="hidden" name="assessment_id" value="<?= (int)$assessment['assessment_id'] ?>">

                            <div class="xform-grid">
                                <div class="xform-field span-4">
                                    <label class="xform-label">Question *</label>
                                    <textarea name="question_text" class="xform-input" rows="3" required></textarea>
                                </div>
                                <div class="xform-field span-2">
                                    <label class="xform-label">Question Type</label>
                                    <select name="question_type" class="xform-input learning-question-type-select">
                                        <?php foreach ($questionTypeLabels as $typeValue => $typeLabel): ?>
                                            <option value="<?= sanitize_output($typeValue) ?>"><?= sanitize_output($typeLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="xform-actions">
                                    <button type="submit" class="btn blue">Add Question</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
