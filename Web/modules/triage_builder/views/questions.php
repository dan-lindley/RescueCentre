<?php
// modules/triage_builder/views/questions.php
if (!defined('APP_LOADED')) exit;

require_once __DIR__ . '/../controllers/triage_builder_lib.php';

$builderCentreId = (int)($centre_id ?? $_SESSION['centre_id'] ?? $_SESSION['rescue_id'] ?? 0);
$builderUserId = triage_builder_user_id();
$builderIsAdmin = triage_builder_is_admin($pdo, $builderUserId);
$handlerPath = 'modules/triage_builder/controllers/triage_builder_handler.php';

$flash = $_SESSION['triage_builder_flash'] ?? null;
unset($_SESSION['triage_builder_flash']);

$builderError = null;
$questions = [];
$adviceItems = [];
$answersByQuestion = [];
$speciesItems = [];
$speciesTypes = [];
$flows = [];
$questionsHasDefaultNext = false;
$supportsSpeciesSearchQuestion = false;

try {
    $adviceHasSpeciesId = triage_builder_has_column($pdo, 'rescue_triage_advice', 'species_id');
    $adviceHasSpeciesType = triage_builder_has_column($pdo, 'rescue_triage_advice', 'species_type');
    $questionsHasDefaultNext = triage_builder_has_column($pdo, 'rescue_triage_questions', 'default_next_question_id');
    $supportsSpeciesSearchQuestion = triage_builder_enum_has_value($pdo, 'rescue_triage_questions', 'answer_type', 'species_search');
    $adviceSpeciesSelect = $adviceHasSpeciesId ? ", a.species_id, sp.species_name AS advice_species_name" : ", NULL AS species_id, NULL AS advice_species_name";
    $adviceSpeciesTypeSelect = $adviceHasSpeciesType ? ", a.species_type" : ", NULL AS species_type";
    $questionDefaultNextSelect = $questionsHasDefaultNext ? ", default_next_question_id" : ", NULL AS default_next_question_id";

    $adviceStmt = $pdo->prepare("
        SELECT a.advice_id, a.centre_id, a.is_global, a.title, a.advice_text, a.active, a.created_at
               $adviceSpeciesSelect
               $adviceSpeciesTypeSelect
        FROM rescue_triage_advice a
        " . ($adviceHasSpeciesId ? "LEFT JOIN rescue_animal_species sp ON sp.species_id = a.species_id" : "") . "
        WHERE (a.centre_id = 0 AND a.is_global = 1)
           OR a.centre_id = :centre_id
        ORDER BY a.active DESC, a.is_global DESC, a.title ASC
    ");
    $adviceStmt->execute([':centre_id' => $builderCentreId]);
    $adviceItems = $adviceStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $questionStmt = $pdo->prepare("
        SELECT question_id, centre_id, is_global, question_text, answer_type, help_text, active, created_at
               $questionDefaultNextSelect
        FROM rescue_triage_questions
        WHERE (centre_id = 0 AND is_global = 1)
           OR centre_id = :centre_id
        ORDER BY active DESC, is_global DESC, question_id DESC
    ");
    $questionStmt->execute([':centre_id' => $builderCentreId]);
    $questions = $questionStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $flowStmt = $pdo->prepare("
        SELECT
            f.flow_id,
            f.centre_id,
            f.is_global,
            f.flow_name,
            f.start_question_id,
            f.question_order_json,
            f.active,
            q.question_text AS start_question_text
        FROM rescue_triage_flows f
        LEFT JOIN rescue_triage_questions q
            ON q.question_id = f.start_question_id
        WHERE (f.centre_id = 0 AND f.is_global = 1)
           OR f.centre_id = :centre_id
        ORDER BY f.active DESC, f.is_global DESC, f.flow_name ASC
    ");
    $flowStmt->execute([':centre_id' => $builderCentreId]);
    $flows = $flowStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $questionIds = array_map(static fn($row) => (int)$row['question_id'], $questions);
    if (!empty($questionIds)) {
        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $answerStmt = $pdo->prepare("
            SELECT
                a.*,
                next_q.question_text AS next_question_text,
                adv.title AS advice_title
            FROM rescue_triage_answers a
            LEFT JOIN rescue_triage_questions next_q
                ON next_q.question_id = a.next_question_id
            LEFT JOIN rescue_triage_advice adv
                ON adv.advice_id = a.advice_id
            WHERE a.question_id IN ($placeholders)
            ORDER BY a.question_id ASC, a.active DESC, a.sort_order ASC, a.answer_id ASC
        ");
        $answerStmt->execute($questionIds);
        foreach ($answerStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $answer) {
            $answersByQuestion[(int)$answer['question_id']][] = $answer;
        }
    }

    $speciesStmt = $pdo->query("
        SELECT species_id, species_name, animal_type
        FROM rescue_animal_species
        ORDER BY species_name ASC
    ");
    $speciesItems = $speciesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $speciesTypesStmt = $pdo->query("
        SELECT DISTINCT animal_type
        FROM rescue_animal_species
        WHERE animal_type IS NOT NULL
          AND animal_type <> ''
        ORDER BY animal_type ASC
    ");
    $speciesTypes = array_map(static fn($row) => (string)$row['animal_type'], $speciesTypesStmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
} catch (Throwable $e) {
    $builderError = $e->getMessage();
}

$answerTypes = [
    'yes_no' => 'Yes / No',
    'select' => 'Single choice',
    'multi' => 'Multiple choice',
    'text' => 'Free text',
];
if ($supportsSpeciesSearchQuestion) {
    $answerTypes['species_search'] = 'Species search';
}

$actionTypes = [
    '' => 'No action',
    'advice_only' => 'Advice only',
    'collection' => 'Collection',
    'vet' => 'Vet',
    'disposal' => 'Disposal',
    'callback' => 'Callback',
    'admit' => 'Admit',
];

$activeQuestionCount = count(array_filter($questions, static fn($row) => !empty($row['active'])));
$activeAdviceCount = count(array_filter($adviceItems, static fn($row) => !empty($row['active'])));
$activeFlowCount = count(array_filter($flows, static fn($row) => !empty($row['active'])));
?>

<style>
    .tb-panel { margin-bottom:14px; }
    .tb-panel-head {
        display:flex;
        justify-content:space-between;
        gap:12px;
        align-items:flex-start;
        margin-bottom:12px;
    }
    .tb-panel-head h3 { margin:0; line-height:1.2; }
    .tb-panel-head p { margin:4px 0 0; color:#6b7280; }
    .tb-scope {
        display:inline-flex;
        align-items:center;
        min-height:24px;
        padding:4px 10px;
        border-radius:999px;
        font-size:.78rem;
    }
    .tb-scope { background:#eef2ff; color:#3730a3; font-weight:800; }
    .tb-scope.centre { background:#ecfdf5; color:#047857; }
    .tb-title { margin:0; font-size:1.1rem; line-height:1.25; color:#111827; }
    .tb-answer-label { font-weight:800; color:#111827; }
    .tb-add-path {
        margin-top:12px;
        border-top:1px solid #e5e7eb;
        padding-top:12px;
    }
    .tb-add-answer-summary {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-height:34px;
        padding:7px 12px;
        border-radius:4px;
        cursor:pointer;
        line-height:1;
        user-select:none;
        list-style:none;
    }
    .tb-add-answer-summary::marker { content:''; }
    .tb-add-answer-summary::-webkit-details-marker { display:none; }
    .tb-route-note {
        margin:10px 0 0;
        padding:10px 12px;
        border:1px solid #bfdbfe;
        border-radius:6px;
        background:#eff6ff;
        color:#1e3a8a;
        line-height:1.35;
    }
    .tb-mini-heading {
        margin:0 0 8px;
        color:#374151;
        font-size:.82rem;
        font-weight:800;
        text-transform:uppercase;
    }
    .tb-advice-text {
        color:#374151;
        line-height:1.35;
        max-height:120px;
        overflow:auto;
    }
    .tb-set-question-list {
        display:flex;
        flex-direction:column;
        gap:8px;
        margin-top:10px;
    }
    .tb-set-question-row {
        display:grid;
        grid-template-columns:76px minmax(0, 1fr) auto;
        gap:10px;
        align-items:start;
        padding:10px;
        border:1px solid #e5e7eb;
        border-radius:6px;
        background:#fff;
    }
    .tb-set-question-row input[type="number"] { max-width:72px; }
    @media (max-width: 900px) {
        .tb-set-question-row { grid-template-columns:1fr; }
    }
</style>

<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2>Triage Builder</h2>
            <p>UNAVAILABLE - draft builder for triage sets, questions, answers and advice.</p>
        </div>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert-box <?= ($flash['type'] ?? '') === 'success' ? 'alert-green' : 'alert-red' ?>">
        <?= triage_builder_h($flash['message'] ?? '') ?>
    </div>
<?php endif; ?>

<?php if ($builderError): ?>
    <div class="alert-box alert-red">
        <strong>Triage builder could not load.</strong><br>
        <?= triage_builder_h($builderError) ?>
    </div>
<?php else: ?>
    <div class="rc-stack">
        <div class="alert-box alert-amber">
            <strong>Draft module.</strong> Build content here; the live call screen remains unavailable until the workflow is ready.
            <?= $builderIsAdmin ? 'Account Admin users can create global content.' : 'Your content is centre-specific.' ?>
        </div>

        <?php if (!$supportsSpeciesSearchQuestion || !$questionsHasDefaultNext): ?>
            <div class="alert-box alert-grey">
                Species search questions will appear after running <strong>rescue_triage_species_search_question_type.sql</strong>.
            </div>
        <?php endif; ?>

        <div class="rc-stat-grid">
            <div class="rc-stat"><strong><?= (int)$activeFlowCount ?></strong><span>Active triage sets</span></div>
            <div class="rc-stat"><strong><?= (int)$activeQuestionCount ?></strong><span>Active questions</span></div>
            <div class="rc-stat"><strong><?= array_sum(array_map('count', $answersByQuestion)) ?></strong><span>Answer paths</span></div>
            <div class="rc-stat"><strong><?= (int)$activeAdviceCount ?></strong><span>Active advice templates</span></div>
        </div>

        <div class="rc-tabs" role="tablist">
            <button type="button" class="rc-tab is-active" data-tab="sets">Triage Sets</button>
            <button type="button" class="rc-tab" data-tab="qa">Questions &amp; Answers</button>
            <button type="button" class="rc-tab" data-tab="advice">Advice</button>
            <button type="button" class="rc-tab" data-tab="builder">Triage Builder</button>
        </div>

        <div class="rc-tab-panel is-active" data-panel="sets">
            <div class="rc-panel tb-panel">
                <div class="tb-panel-head">
                    <div>
                        <h3>Triage Sets</h3>
                        <p>These are the available triage question sets. Each set has a starting question.</p>
                    </div>
                </div>

                <?php if (empty($flows)): ?>
                    <div class="alert-box alert-grey">No triage sets have been created yet. Use the Triage Builder tab to create one.</div>
                <?php else: ?>
                    <div class="rc-list">
                        <?php foreach ($flows as $flow): ?>
                            <?php
                                $flowScope = triage_builder_scope_label($flow);
                                $canManageFlow = triage_builder_can_manage_row($flow, $builderCentreId, $builderIsAdmin);
                            ?>
                            <div class="rc-card rc-card-muted">
                                <div class="rc-row-head">
                                    <div>
                                        <p class="tb-title"><?= triage_builder_h($flow['flow_name']) ?></p>
                                        <div class="rc-chip-row">
                                            <span class="tb-scope <?= $flowScope === 'Centre' ? 'centre' : '' ?>"><?= triage_builder_h($flowScope) ?></span>
                                            <span class="rc-chip blue">Starts with: <?= triage_builder_h(triage_builder_short_text($flow['start_question_text'] ?: 'Question not set', 90)) ?></span>
                                        </div>
                                    </div>
                                    <div class="rc-actions">
                                        <span class="rc-status <?= !empty($flow['active']) ? 'tb-active' : 'tb-inactive' ?>"><?= !empty($flow['active']) ? 'Active' : 'Inactive' ?></span>
                                        <?php if ($canManageFlow): ?>
                                            <form method="post" action="<?= triage_builder_h($handlerPath) ?>" style="margin:0;">
                                                <input type="hidden" name="action" value="toggle_flow">
                                                <input type="hidden" name="return_tab" value="sets">
                                                <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                                                <input type="hidden" name="flow_id" value="<?= (int)$flow['flow_id'] ?>">
                                                <input type="hidden" name="active" value="<?= !empty($flow['active']) ? 0 : 1 ?>">
                                                <button type="submit" class="btn grey"><?= !empty($flow['active']) ? 'Deactivate' : 'Activate' ?></button>
                                            </form>
                                            <?php if (empty($flow['active'])): ?>
                                                <form method="post" action="<?= triage_builder_h($handlerPath) ?>" style="margin:0;" onsubmit="return confirm('Delete this inactive triage set?');">
                                                    <input type="hidden" name="action" value="delete_flow">
                                                    <input type="hidden" name="return_tab" value="sets">
                                                    <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                                                    <input type="hidden" name="flow_id" value="<?= (int)$flow['flow_id'] ?>">
                                                    <button type="submit" class="btn red">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="rc-tab-panel" data-panel="qa">
            <div class="rc-panel tb-panel">
                <div class="tb-panel-head">
                    <div>
                        <h3>Create Question</h3>
                        <p>Add questions to the reusable question bank.</p>
                    </div>
                </div>

                <form method="post" action="<?= triage_builder_h($handlerPath) ?>" class="xform">
                    <input type="hidden" name="action" value="create_question">
                    <input type="hidden" name="return_tab" value="qa">
                    <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                    <div class="rc-form-grid">
                        <div class="xform-field span-4">
                            <label class="xform-label">Question the handler asks</label>
                            <textarea name="question_text" class="xform-input" rows="3" placeholder="Is the animal alive?" required></textarea>
                        </div>
                        <div class="xform-field">
                            <label class="xform-label">Answer type</label>
                            <select name="answer_type" class="xform-input">
                                <?php foreach ($answerTypes as $typeKey => $typeLabel): ?>
                                    <option value="<?= triage_builder_h($typeKey) ?>"><?= triage_builder_h($typeLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($builderIsAdmin): ?>
                            <div class="xform-field">
                                <label class="xform-label">Scope</label>
                                <label style="display:flex; gap:8px; align-items:center; min-height:38px; margin:0;">
                                    <input type="checkbox" name="is_global" value="1">
                                    Global question
                                </label>
                            </div>
                        <?php endif; ?>
                        <div class="xform-field span-3">
                            <label class="xform-label">Handler note (optional)</label>
                            <input type="text" name="help_text" class="xform-input" placeholder="Short prompt shown below the question">
                        </div>
                        <div class="xform-field">
                            <button type="submit" class="btn green">Save question</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="rc-panel tb-panel">
                <div class="tb-panel-head">
                    <div>
                        <h3>Questions &amp; Answers</h3>
                        <p>Create reusable questions and the answer options available for each question.</p>
                    </div>
                </div>

                <?php if (empty($questions)): ?>
                    <div class="alert-box alert-grey">No triage questions have been created yet.</div>
                <?php else: ?>
                    <div class="rc-list">
                        <?php foreach ($questions as $question): ?>
                            <?php
                                $questionId = (int)$question['question_id'];
                                $questionAnswers = $answersByQuestion[$questionId] ?? [];
                                $questionScope = triage_builder_scope_label($question);
                                $questionIsGlobal = $questionScope === 'Global';
                                $canManageQuestion = triage_builder_can_manage_row($question, $builderCentreId, $builderIsAdmin);
                                $questionIsSpeciesSearch = (string)$question['answer_type'] === 'species_search';
                            ?>
                            <div class="rc-card rc-card-muted" id="question-<?= $questionId ?>">
                                <div class="rc-row-head">
                                    <div>
                                        <p class="tb-title"><?= triage_builder_h($question['question_text']) ?></p>
                                        <div class="rc-chip-row">
                                            <span class="tb-scope <?= $questionScope === 'Centre' ? 'centre' : '' ?>"><?= triage_builder_h($questionScope) ?></span>
                                            <span class="rc-chip"><?= triage_builder_h($answerTypes[$question['answer_type']] ?? $question['answer_type']) ?></span>
                                            <?php if (!empty($question['help_text'])): ?>
                                                <span class="rc-chip blue"><?= triage_builder_h($question['help_text']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="rc-actions">
                                        <span class="rc-status <?= !empty($question['active']) ? 'tb-active' : 'tb-inactive' ?>"><?= !empty($question['active']) ? 'Active' : 'Inactive' ?></span>
                                        <?php if ($canManageQuestion): ?>
                                            <form method="post" action="<?= triage_builder_h($handlerPath) ?>" style="margin:0;">
                                                <input type="hidden" name="action" value="toggle_question">
                                                <input type="hidden" name="return_tab" value="qa">
                                                <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                                                <input type="hidden" name="question_id" value="<?= $questionId ?>">
                                                <input type="hidden" name="active" value="<?= !empty($question['active']) ? 0 : 1 ?>">
                                                <button type="submit" class="btn grey"><?= !empty($question['active']) ? 'Deactivate' : 'Activate' ?></button>
                                            </form>
                                            <?php if (empty($question['active'])): ?>
                                                <form method="post" action="<?= triage_builder_h($handlerPath) ?>" style="margin:0;" onsubmit="return confirm('Delete this inactive question and its answer options?');">
                                                    <input type="hidden" name="action" value="delete_question">
                                                    <input type="hidden" name="return_tab" value="qa">
                                                    <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                                                    <input type="hidden" name="question_id" value="<?= $questionId ?>">
                                                    <button type="submit" class="btn red">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($canManageQuestion): ?>
                                    <details class="tb-add-path">
                                        <summary class="btn grey tb-add-answer-summary">Edit question</summary>
                                        <form method="post" action="<?= triage_builder_h($handlerPath) ?>" class="xform" style="margin-top:12px;">
                                            <input type="hidden" name="action" value="update_question">
                                            <input type="hidden" name="return_tab" value="qa">
                                            <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                                            <input type="hidden" name="question_id" value="<?= $questionId ?>">
                                            <div class="rc-form-grid">
                                                <div class="xform-field span-4">
                                                    <label class="xform-label">Question the handler asks</label>
                                                    <textarea name="question_text" class="xform-input" rows="3" required><?= triage_builder_h($question['question_text']) ?></textarea>
                                                </div>
                                                <div class="xform-field">
                                                    <label class="xform-label">Answer type</label>
                                                    <select name="answer_type" class="xform-input">
                                                        <?php foreach ($answerTypes as $typeKey => $typeLabel): ?>
                                                            <option value="<?= triage_builder_h($typeKey) ?>" <?= (string)$question['answer_type'] === (string)$typeKey ? 'selected' : '' ?>>
                                                                <?= triage_builder_h($typeLabel) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <?php if ($builderIsAdmin): ?>
                                                    <div class="xform-field">
                                                        <label class="xform-label">Scope</label>
                                                        <label style="display:flex; gap:8px; align-items:center; min-height:38px; margin:0;">
                                                            <input type="checkbox" name="is_global" value="1" <?= $questionIsGlobal ? 'checked' : '' ?>>
                                                            Global question
                                                        </label>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="xform-field span-2">
                                                    <label class="xform-label">Handler note</label>
                                                    <input type="text" name="help_text" class="xform-input" value="<?= triage_builder_h($question['help_text'] ?? '') ?>">
                                                </div>
                                                <div class="xform-field">
                                                    <button type="submit" class="btn green" style="width:100%;">Update question</button>
                                                </div>
                                            </div>
                                        </form>
                                    </details>
                                <?php endif; ?>

                                <div class="rc-list" style="margin-top:12px;">
                                    <?php if ($questionIsSpeciesSearch): ?>
                                        <div class="alert-box alert-grey" style="margin:0;">Species search questions use an autocomplete box during calls, so they do not need answer options.</div>
                                    <?php elseif (empty($questionAnswers)): ?>
                                        <div class="alert-box alert-grey" style="margin:0;">No answers have been added yet.</div>
                                    <?php else: ?>
                                        <?php foreach ($questionAnswers as $answer): ?>
                                            <div class="rc-card">
                                                <div class="rc-row-head">
                                                    <div>
                                                        <div class="rc-muted">Answer</div>
                                                        <div class="tb-answer-label"><?= triage_builder_h($answer['answer_label']) ?></div>
                                                        <div class="rc-chip-row">
                                                            <span class="rc-chip">Answer option</span>
                                                        </div>
                                                    </div>
                                                    <div class="rc-actions">
                                                        <span class="rc-status <?= !empty($answer['active']) ? 'tb-active' : 'tb-inactive' ?>"><?= !empty($answer['active']) ? 'Active' : 'Inactive' ?></span>
                                                        <?php if ($canManageQuestion): ?>
                                                            <form method="post" action="<?= triage_builder_h($handlerPath) ?>" style="margin:0;">
                                                                <input type="hidden" name="action" value="toggle_answer">
                                                                <input type="hidden" name="return_tab" value="qa">
                                                                <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                                                                <input type="hidden" name="answer_id" value="<?= (int)$answer['answer_id'] ?>">
                                                                <input type="hidden" name="active" value="<?= !empty($answer['active']) ? 0 : 1 ?>">
                                                                <button type="submit" class="btn grey"><?= !empty($answer['active']) ? 'Deactivate' : 'Activate' ?></button>
                                                            </form>
                                                            <?php if (empty($answer['active'])): ?>
                                                                <form method="post" action="<?= triage_builder_h($handlerPath) ?>" style="margin:0;" onsubmit="return confirm('Delete this inactive answer?');">
                                                                    <input type="hidden" name="action" value="delete_answer">
                                                                    <input type="hidden" name="return_tab" value="qa">
                                                                    <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                                                                    <input type="hidden" name="answer_id" value="<?= (int)$answer['answer_id'] ?>">
                                                                    <button type="submit" class="btn red">Delete</button>
                                                                </form>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php if ($canManageQuestion): ?>
                                                    <details class="tb-add-path">
                                                        <summary class="btn grey tb-add-answer-summary">Edit answer</summary>
                                                        <form method="post" action="<?= triage_builder_h($handlerPath) ?>" class="xform" style="margin-top:12px;">
                                                            <input type="hidden" name="action" value="update_answer">
                                                            <input type="hidden" name="return_tab" value="qa">
                                                            <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                                                            <input type="hidden" name="answer_id" value="<?= (int)$answer['answer_id'] ?>">
                                                            <div class="rc-form-grid">
                                                                <div class="xform-field">
                                                                    <label class="xform-label">Answer label</label>
                                                                    <input type="text" name="answer_label" class="xform-input" value="<?= triage_builder_h($answer['answer_label']) ?>" required>
                                                                </div>
                                                                <div class="xform-field">
                                                                    <label class="xform-label">Answer value</label>
                                                                    <input type="text" name="answer_value" class="xform-input" value="<?= triage_builder_h($answer['answer_value'] ?? '') ?>">
                                                                </div>
                                                                <div class="xform-field">
                                                                    <label class="xform-label">Sort order</label>
                                                                    <input type="number" name="sort_order" class="xform-input" value="<?= (int)($answer['sort_order'] ?? 0) ?>">
                                                                </div>
                                                                <div class="xform-field">
                                                                    <button type="submit" class="btn green" style="width:100%;">Update answer</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </details>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <?php if ($canManageQuestion && !$questionIsSpeciesSearch): ?>
                                    <details class="tb-add-path">
                                        <summary class="btn blue tb-add-answer-summary">+ Add answer</summary>
                                        <form method="post" action="<?= triage_builder_h($handlerPath) ?>" class="xform" style="margin-top:12px;">
                                            <input type="hidden" name="action" value="create_answer">
                                            <input type="hidden" name="return_tab" value="qa">
                                            <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                                            <input type="hidden" name="question_id" value="<?= $questionId ?>">
                                            <div class="rc-form-grid">
                                                <div class="xform-field">
                                                    <label class="xform-label">Answer label</label>
                                                    <input type="text" name="answer_label" class="xform-input" placeholder="Yes / No / Hedgehog / Injured" required>
                                                </div>
                                                <div class="xform-field">
                                                    <label class="xform-label">Answer value (optional)</label>
                                                    <input type="text" name="answer_value" class="xform-input">
                                                </div>
                                                <div class="xform-field">
                                                    <label class="xform-label">Sort order</label>
                                                    <input type="number" name="sort_order" class="xform-input" value="0">
                                                </div>
                                                <div class="xform-field">
                                                    <button type="submit" class="btn green" style="width:100%;">Save answer</button>
                                                </div>
                                            </div>
                                        </form>
                                    </details>
                                <?php elseif ($canManageQuestion && $questionIsSpeciesSearch): ?>
                                    <div class="tb-route-note">Set the next question for this species search in the Triage Builder tab, inside the triage set that uses it.</div>
                                <?php else: ?>
                                    <div class="alert-box alert-grey" style="margin:12px 0 0;">Global question. Only account Admin users can change global answers.</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="rc-tab-panel" data-panel="advice">
            <div class="rc-panel tb-panel">
                <div class="tb-panel-head">
                    <div>
                        <h3>Create Advice</h3>
                        <p>Advice templates can be attached to answer paths.</p>
                    </div>
                </div>

                <form method="post" action="<?= triage_builder_h($handlerPath) ?>" class="xform">
                    <input type="hidden" name="action" value="create_advice">
                    <input type="hidden" name="return_tab" value="advice">
                    <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                    <div class="rc-form-grid">
                        <div class="xform-field span-2">
                            <label class="xform-label">Advice title</label>
                            <input type="text" name="title" class="xform-input" placeholder="Keep warm, dark and quiet" required>
                        </div>
                        <?php if ($adviceHasSpeciesType): ?>
                            <div class="xform-field">
                                <label class="xform-label">Species type</label>
                                <select name="species_type" class="xform-input">
                                    <option value="">All types</option>
                                    <?php foreach ($speciesTypes as $speciesType): ?>
                                        <option value="<?= triage_builder_h($speciesType) ?>"><?= triage_builder_h($speciesType) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <?php if ($adviceHasSpeciesId): ?>
                            <div class="xform-field">
                                <label class="xform-label">Specific species</label>
                                <select name="species_id" class="xform-input">
                                    <option value="">All species</option>
                                    <?php foreach ($speciesItems as $species): ?>
                                        <option value="<?= (int)$species['species_id'] ?>">
                                            <?= triage_builder_h($species['species_name']) ?><?= !empty($species['animal_type']) ? ' - ' . triage_builder_h($species['animal_type']) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="xform-field span-4">
                            <label class="xform-label">Advice to give</label>
                            <textarea name="advice_text" class="xform-input" rows="5" required></textarea>
                        </div>
                        <?php if ($builderIsAdmin): ?>
                            <div class="xform-field span-3">
                                <label style="display:flex; gap:8px; align-items:center; margin:0;">
                                    <input type="checkbox" name="is_global" value="1">
                                    Make this global advice
                                </label>
                            </div>
                        <?php endif; ?>
                        <div class="xform-field">
                            <button type="submit" class="btn green">Save advice</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="rc-panel tb-panel">
                <div class="tb-panel-head">
                    <div>
                        <h3>Advice Library</h3>
                        <p>Reusable advice currently available for this centre.</p>
                    </div>
                </div>

                <?php if (empty($adviceItems)): ?>
                    <div class="alert-box alert-grey">No advice templates have been created yet.</div>
                <?php else: ?>
                    <div class="rc-list">
                        <?php foreach ($adviceItems as $advice): ?>
                            <?php
                                $adviceScope = triage_builder_scope_label($advice);
                                $canManageAdvice = triage_builder_can_manage_row($advice, $builderCentreId, $builderIsAdmin);
                            ?>
                            <div class="rc-card rc-card-muted">
                                <div class="rc-row-head">
                                    <div>
                                        <p class="tb-title"><?= triage_builder_h($advice['title']) ?></p>
                                        <div class="rc-chip-row">
                                            <span class="tb-scope <?= $adviceScope === 'Centre' ? 'centre' : '' ?>"><?= triage_builder_h($adviceScope) ?></span>
                                            <?php if (!empty($advice['advice_species_name'])): ?>
                                                <span class="rc-chip good">Species: <?= triage_builder_h($advice['advice_species_name']) ?></span>
                                            <?php elseif (!empty($advice['species_type'])): ?>
                                                <span class="rc-chip blue">Type: <?= triage_builder_h($advice['species_type']) ?></span>
                                            <?php else: ?>
                                                <span class="rc-chip">All species</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="tb-advice-text" style="margin-top:8px;"><?= nl2br(triage_builder_h($advice['advice_text'])) ?></div>
                                    </div>
                                    <div class="rc-actions">
                                        <span class="rc-status <?= !empty($advice['active']) ? 'tb-active' : 'tb-inactive' ?>"><?= !empty($advice['active']) ? 'Active' : 'Inactive' ?></span>
                                        <?php if ($canManageAdvice): ?>
                                            <form method="post" action="<?= triage_builder_h($handlerPath) ?>" style="margin:0;">
                                                <input type="hidden" name="action" value="toggle_advice">
                                                <input type="hidden" name="return_tab" value="advice">
                                                <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                                                <input type="hidden" name="advice_id" value="<?= (int)$advice['advice_id'] ?>">
                                                <input type="hidden" name="active" value="<?= !empty($advice['active']) ? 0 : 1 ?>">
                                                <button type="submit" class="btn grey"><?= !empty($advice['active']) ? 'Deactivate' : 'Activate' ?></button>
                                            </form>
                                            <?php if (empty($advice['active'])): ?>
                                                <form method="post" action="<?= triage_builder_h($handlerPath) ?>" style="margin:0;" onsubmit="return confirm('Delete this inactive advice template?');">
                                                    <input type="hidden" name="action" value="delete_advice">
                                                    <input type="hidden" name="return_tab" value="advice">
                                                    <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                                                    <input type="hidden" name="advice_id" value="<?= (int)$advice['advice_id'] ?>">
                                                    <button type="submit" class="btn red">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($canManageAdvice): ?>
                                    <details class="tb-add-path">
                                        <summary class="btn grey tb-add-answer-summary">Edit advice</summary>
                                        <form method="post" action="<?= triage_builder_h($handlerPath) ?>" class="xform" style="margin-top:12px;">
                                            <input type="hidden" name="action" value="update_advice">
                                            <input type="hidden" name="return_tab" value="advice">
                                            <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                                            <input type="hidden" name="advice_id" value="<?= (int)$advice['advice_id'] ?>">
                                            <div class="rc-form-grid">
                                                <div class="xform-field span-2">
                                                    <label class="xform-label">Advice title</label>
                                                    <input type="text" name="title" class="xform-input" value="<?= triage_builder_h($advice['title']) ?>" required>
                                                </div>
                                                <?php if ($adviceHasSpeciesType): ?>
                                                    <div class="xform-field">
                                                        <label class="xform-label">Species type</label>
                                                        <select name="species_type" class="xform-input">
                                                            <option value="">All types</option>
                                                            <?php foreach ($speciesTypes as $speciesType): ?>
                                                                <option value="<?= triage_builder_h($speciesType) ?>" <?= (string)($advice['species_type'] ?? '') === (string)$speciesType ? 'selected' : '' ?>>
                                                                    <?= triage_builder_h($speciesType) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($adviceHasSpeciesId): ?>
                                                    <div class="xform-field">
                                                        <label class="xform-label">Specific species</label>
                                                        <select name="species_id" class="xform-input">
                                                            <option value="">All species</option>
                                                            <?php foreach ($speciesItems as $species): ?>
                                                                <option value="<?= (int)$species['species_id'] ?>" <?= (int)($advice['species_id'] ?? 0) === (int)$species['species_id'] ? 'selected' : '' ?>>
                                                                    <?= triage_builder_h($species['species_name']) ?><?= !empty($species['animal_type']) ? ' - ' . triage_builder_h($species['animal_type']) : '' ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="xform-field span-4">
                                                    <label class="xform-label">Advice to give</label>
                                                    <textarea name="advice_text" class="xform-input" rows="5" required><?= triage_builder_h($advice['advice_text']) ?></textarea>
                                                </div>
                                                <?php if ($builderIsAdmin): ?>
                                                    <div class="xform-field span-3">
                                                        <label style="display:flex; gap:8px; align-items:center; margin:0;">
                                                            <input type="checkbox" name="is_global" value="1" <?= $adviceScope === 'Global' ? 'checked' : '' ?>>
                                                            Make this global advice
                                                        </label>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="xform-field">
                                                    <button type="submit" class="btn green" style="width:100%;">Update advice</button>
                                                </div>
                                            </div>
                                        </form>
                                    </details>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="rc-tab-panel" data-panel="builder">
            <div class="rc-panel tb-panel">
                <div class="tb-panel-head">
                    <div>
                        <h3>Triage Builder</h3>
                        <p>Create a triage set from the question bank. The set starts at one question and follows answer paths from there.</p>
                    </div>
                </div>

                <form method="post" action="<?= triage_builder_h($handlerPath) ?>" class="xform">
                    <input type="hidden" name="action" value="create_flow">
                    <input type="hidden" name="return_tab" value="builder">
                    <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                    <div class="rc-form-grid">
                        <div class="xform-field">
                            <label class="xform-label">Triage set name</label>
                            <input type="text" name="flow_name" class="xform-input" placeholder="Main telephone triage" required>
                        </div>
                        <div class="xform-field">
                            <label class="xform-label">Start question</label>
                            <select name="start_question_id" class="xform-input" required>
                                <option value="">Select question</option>
                                <?php foreach ($questions as $question): ?>
                                    <?php if (empty($question['active'])) continue; ?>
                                    <option value="<?= (int)$question['question_id'] ?>">
                                        <?= triage_builder_h(triage_builder_scope_label($question) . ': ' . triage_builder_short_text($question['question_text'], 92)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($builderIsAdmin): ?>
                            <div class="xform-field">
                                <label style="display:flex; gap:8px; align-items:center; min-height:38px; margin:0;">
                                    <input type="checkbox" name="is_global" value="1">
                                    Global triage set
                                </label>
                            </div>
                        <?php endif; ?>
                        <div class="xform-field">
                            <button type="submit" class="btn green" style="width:100%;">Save triage set</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="rc-panel tb-panel">
                <div class="tb-panel-head">
                    <div>
                        <h3>Modify Existing Triage Sets</h3>
                        <p>Open a triage set to manage the questions already in it. Add new questions separately at the bottom of each set.</p>
                    </div>
                </div>

                <?php if (empty($flows)): ?>
                    <div class="alert-box alert-grey">No triage sets have been created yet.</div>
                <?php else: ?>
                    <div class="rc-list">
                        <?php foreach ($flows as $flow): ?>
                            <?php
                                $flowScope = triage_builder_scope_label($flow);
                                $flowIsGlobal = $flowScope === 'Global';
                                $canManageFlow = triage_builder_can_manage_row($flow, $builderCentreId, $builderIsAdmin);
                                $flowQuestionIds = [];
                                if (!empty($flow['question_order_json'])) {
                                    $decodedFlowQuestions = json_decode((string)$flow['question_order_json'], true);
                                    if (is_array($decodedFlowQuestions)) {
                                        $flowQuestionIds = array_values(array_filter(array_map('intval', $decodedFlowQuestions)));
                                    }
                                }
                                if (empty($flowQuestionIds) && !empty($flow['start_question_id'])) {
                                    $flowQuestionIds[] = (int)$flow['start_question_id'];
                                }
                                $flowQuestionPositions = array_flip($flowQuestionIds);
                                $flowStructureFormId = 'tb-flow-structure-' . (int)$flow['flow_id'];
                            ?>
                            <details class="rc-card rc-card-muted">
                                <summary style="cursor:pointer; list-style:none;">
                                    <div class="rc-row-head">
                                        <div>
                                            <p class="tb-title"><?= triage_builder_h($flow['flow_name']) ?></p>
                                            <div class="rc-chip-row">
                                                <span class="tb-scope <?= $flowScope === 'Centre' ? 'centre' : '' ?>"><?= triage_builder_h($flowScope) ?></span>
                                                <span class="rc-chip blue">Starts with: <?= triage_builder_h(triage_builder_short_text($flow['start_question_text'] ?: 'Question not set', 90)) ?></span>
                                                <span class="rc-chip"><?= count($flowQuestionIds) ?> question<?= count($flowQuestionIds) === 1 ? '' : 's' ?> in set</span>
                                            </div>
                                        </div>
                                        <span class="rc-status <?= !empty($flow['active']) ? 'tb-active' : 'tb-inactive' ?>"><?= !empty($flow['active']) ? 'Active' : 'Inactive' ?></span>
                                    </div>
                                </summary>

                                <?php if (!$canManageFlow): ?>
                                    <div class="alert-box alert-grey" style="margin:12px 0 0;">Global triage set. Only account Admin users can modify it.</div>
                                <?php else: ?>
                                    <form id="<?= triage_builder_h($flowStructureFormId) ?>" method="post" action="<?= triage_builder_h($handlerPath) ?>"></form>
                                    <div class="xform">
                                        <input form="<?= triage_builder_h($flowStructureFormId) ?>" type="hidden" name="action" value="update_flow_structure">
                                        <input form="<?= triage_builder_h($flowStructureFormId) ?>" type="hidden" name="return_tab" value="builder">
                                        <input form="<?= triage_builder_h($flowStructureFormId) ?>" type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                                        <input form="<?= triage_builder_h($flowStructureFormId) ?>" type="hidden" name="flow_id" value="<?= (int)$flow['flow_id'] ?>">

                                        <div class="rc-form-grid" style="margin-top:12px;">
                                            <div class="xform-field">
                                                <label class="xform-label">Triage set name</label>
                                                <input form="<?= triage_builder_h($flowStructureFormId) ?>" type="text" name="flow_name" class="xform-input" value="<?= triage_builder_h($flow['flow_name']) ?>" required>
                                            </div>
                                            <div class="xform-field">
                                                <label class="xform-label">Start question</label>
                                                <select form="<?= triage_builder_h($flowStructureFormId) ?>" name="start_question_id" class="xform-input" required>
                                                    <option value="">Select question in this set</option>
                                                    <?php foreach ($flowQuestionIds as $flowQuestionId): ?>
                                                        <?php
                                                            $flowQuestion = null;
                                                            foreach ($questions as $questionCandidate) {
                                                                if ((int)$questionCandidate['question_id'] === (int)$flowQuestionId) {
                                                                    $flowQuestion = $questionCandidate;
                                                                    break;
                                                                }
                                                            }
                                                            if (!$flowQuestion || empty($flowQuestion['active'])) continue;
                                                        ?>
                                                        <option value="<?= (int)$flowQuestion['question_id'] ?>" <?= (int)$flow['start_question_id'] === (int)$flowQuestion['question_id'] ? 'selected' : '' ?>>
                                                            <?= triage_builder_h(triage_builder_short_text($flowQuestion['question_text'], 100)) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <p class="tb-mini-heading" style="margin-top:14px;">Questions currently in this set</p>
                                        <div class="tb-set-question-list">
                                            <?php if (empty($flowQuestionIds)): ?>
                                                <div class="alert-box alert-grey" style="margin:0;">No questions have been added to this set yet.</div>
                                            <?php endif; ?>
                                            <?php foreach ($flowQuestionIds as $flowQuestionId): ?>
                                                <?php
                                                    $question = null;
                                                    foreach ($questions as $questionCandidate) {
                                                        if ((int)$questionCandidate['question_id'] === (int)$flowQuestionId) {
                                                            $question = $questionCandidate;
                                                            break;
                                                        }
                                                    }
                                                    if (!$question) continue;
                                                    $questionId = (int)$question['question_id'];
                                                    $questionScope = triage_builder_scope_label($question);
                                                    $questionIsGlobal = $questionScope === 'Global';
                                                    $orderValue = isset($flowQuestionPositions[$questionId]) ? ((int)$flowQuestionPositions[$questionId] + 1) : '';
                                                    $questionAnswers = $answersByQuestion[$questionId] ?? [];
                                                    $answerCount = count($questionAnswers);
                                                    $questionIsSpeciesSearch = (string)$question['answer_type'] === 'species_search';
                                                    $defaultNextQuestionId = (int)($question['default_next_question_id'] ?? 0);
                                                    $defaultNextQuestionText = '';
                                                    $nextByOrderId = 0;
                                                    $nextByOrderText = '';
                                                    $currentFlowPosition = isset($flowQuestionPositions[$questionId]) ? (int)$flowQuestionPositions[$questionId] : null;
                                                    if ($currentFlowPosition !== null && isset($flowQuestionIds[$currentFlowPosition + 1])) {
                                                        $nextByOrderId = (int)$flowQuestionIds[$currentFlowPosition + 1];
                                                        foreach ($questions as $questionCandidate) {
                                                            if ((int)$questionCandidate['question_id'] === $nextByOrderId) {
                                                                $nextByOrderText = (string)$questionCandidate['question_text'];
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    if ($defaultNextQuestionId > 0) {
                                                        foreach ($questions as $questionCandidate) {
                                                            if ((int)$questionCandidate['question_id'] === $defaultNextQuestionId) {
                                                                $defaultNextQuestionText = (string)$questionCandidate['question_text'];
                                                                break;
                                                            }
                                                        }
                                                    }
                                                ?>
                                                <div class="rc-card">
                                                    <div class="tb-set-question-row">
                                                        <div>
                                                            <label class="xform-label">Order</label>
                                                            <input form="<?= triage_builder_h($flowStructureFormId) ?>" type="hidden" name="question_ids[]" value="<?= $questionId ?>">
                                                            <input form="<?= triage_builder_h($flowStructureFormId) ?>" type="number" name="question_order[<?= $questionId ?>]" class="xform-input" value="<?= triage_builder_h($orderValue) ?>" min="1">
                                                        </div>
                                                        <div>
                                                            <strong><?= triage_builder_h($question['question_text']) ?></strong>
                                                            <div class="rc-chip-row">
                                                                <span class="tb-scope <?= $questionScope === 'Centre' ? 'centre' : '' ?>"><?= triage_builder_h($questionScope) ?></span>
                                                                <span class="rc-chip"><?= triage_builder_h($answerTypes[$question['answer_type']] ?? $question['answer_type']) ?></span>
                                                                <?php if ($questionIsSpeciesSearch && $defaultNextQuestionText !== ''): ?>
                                                                    <span class="rc-chip blue">Override: ask <?= triage_builder_h(triage_builder_short_text($defaultNextQuestionText, 70)) ?></span>
                                                                <?php elseif ($nextByOrderText !== ''): ?>
                                                                    <span class="rc-chip good">Default: next question in set</span>
                                                                <?php elseif ($questionIsSpeciesSearch): ?>
                                                                    <span class="rc-chip warn">Default: end of set</span>
                                                                <?php else: ?>
                                                                    <span class="rc-chip"><?= (int)$answerCount ?> answer<?= $answerCount === 1 ? '' : 's' ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <label style="display:flex; gap:7px; align-items:center; margin:0;">
                                                            <input form="<?= triage_builder_h($flowStructureFormId) ?>" type="checkbox" name="remove_question_ids[]" value="<?= $questionId ?>">
                                                            Remove
                                                        </label>
                                                    </div>

                                                    <div class="tb-add-path" style="border-top:1px solid #e5e7eb;">
                                                        <?php if ($questionIsSpeciesSearch): ?>
                                                            <p class="tb-mini-heading">Species search routing</p>
                                                            <div class="tb-route-note" style="margin-bottom:10px;">
                                                                The call handler searches for a species or species type here. The call remembers that choice for advice matching, then normally continues to the next question by order.
                                                            </div>
                                                            <?php if (!$questionsHasDefaultNext): ?>
                                                                <div class="alert-box alert-grey" style="margin:0;">Run the species search SQL before setting route overrides.</div>
                                                            <?php else: ?>
                                                                <form method="post" action="<?= triage_builder_h($handlerPath) ?>" class="xform">
                                                                    <input type="hidden" name="action" value="update_question_default_route">
                                                                    <input type="hidden" name="return_tab" value="builder">
                                                                    <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                                                                    <input type="hidden" name="question_id" value="<?= $questionId ?>">
                                                                    <div class="rc-form-grid">
                                                                        <div class="xform-field span-3">
                                                                            <label class="xform-label">Override next question</label>
                                                                            <select name="default_next_question_id" class="xform-input">
                                                                                <option value="">Use triage set order<?= $nextByOrderText !== '' ? ': ' . triage_builder_h(triage_builder_short_text($nextByOrderText, 60)) : ' / end of set' ?></option>
                                                                                <?php foreach ($flowQuestionIds as $nextFlowQuestionId): ?>
                                                                                    <?php
                                                                                        if ((int)$nextFlowQuestionId === $questionId) continue;
                                                                                        $nextQuestion = null;
                                                                                        foreach ($questions as $questionCandidate) {
                                                                                            if ((int)$questionCandidate['question_id'] === (int)$nextFlowQuestionId) {
                                                                                                $nextQuestion = $questionCandidate;
                                                                                                break;
                                                                                            }
                                                                                        }
                                                                                        if (!$nextQuestion) continue;
                                                                                        if ($questionIsGlobal && triage_builder_scope_label($nextQuestion) !== 'Global') continue;
                                                                                    ?>
                                                                                    <option value="<?= (int)$nextQuestion['question_id'] ?>" <?= $defaultNextQuestionId === (int)$nextQuestion['question_id'] ? 'selected' : '' ?>>
                                                                                        <?= triage_builder_h(triage_builder_short_text($nextQuestion['question_text'], 100)) ?>
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="xform-field">
                                                                            <button type="submit" class="btn green" style="width:100%;">Save override</button>
                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <p class="tb-mini-heading">Answer routing for this question</p>
                                                            <?php if (empty($questionAnswers)): ?>
                                                            <div class="alert-box alert-grey" style="margin:0;">No answers have been created for this question yet.</div>
                                                            <?php else: ?>
                                                            <div class="rc-list">
                                                                <?php foreach ($questionAnswers as $answer): ?>
                                                                    <form method="post" action="<?= triage_builder_h($handlerPath) ?>" class="rc-card xform">
                                                                        <input type="hidden" name="return_tab" value="builder">
                                                                        <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                                                                        <input type="hidden" name="answer_id" value="<?= (int)$answer['answer_id'] ?>">
                                                                        <input type="hidden" name="active" value="<?= !empty($answer['active']) ? 0 : 1 ?>">

                                                                        <div class="rc-row-head">
                                                                            <div>
                                                                                <div class="rc-muted">If answer is</div>
                                                                                <div class="tb-answer-label"><?= triage_builder_h($answer['answer_label']) ?></div>
                                                                                <div class="rc-chip-row">
                                                                                    <?php if (!empty($answer['end_triage'])): ?>
                                                                                        <span class="rc-chip warn">Route: close triage</span>
                                                                                    <?php elseif (!empty($answer['next_question_text'])): ?>
                                                                                        <span class="rc-chip blue">Override: ask <?= triage_builder_h(triage_builder_short_text($answer['next_question_text'], 70)) ?></span>
                                                                                    <?php elseif ($nextByOrderText !== ''): ?>
                                                                                        <span class="rc-chip good">Default: ask next question</span>
                                                                                    <?php else: ?>
                                                                                        <span class="rc-chip">Default: end of set</span>
                                                                                    <?php endif; ?>
                                                                                    <?php if (!empty($answer['advice_title'])): ?>
                                                                                        <span class="rc-chip good">Advice: <?= triage_builder_h($answer['advice_title']) ?></span>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                            </div>
                                                                            <div class="rc-actions">
                                                                                <span class="rc-status <?= !empty($answer['active']) ? 'tb-active' : 'tb-inactive' ?>"><?= !empty($answer['active']) ? 'Active' : 'Inactive' ?></span>
                                                                                <button type="submit" name="action" value="toggle_answer" class="btn grey">
                                                                                    <?= !empty($answer['active']) ? 'Deactivate' : 'Activate' ?>
                                                                                </button>
                                                                                <?php if (empty($answer['active'])): ?>
                                                                                    <button type="submit" name="action" value="delete_answer" class="btn red" onclick="return confirm('Delete this inactive answer?');">Delete</button>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>

                                                                        <div class="rc-form-grid" style="margin-top:10px;">
                                                                            <div class="xform-field">
                                                                                <label class="xform-label">Override next question</label>
                                                                                <select name="next_question_id" class="xform-input">
                                                                                    <option value="">Use triage set order<?= $nextByOrderText !== '' ? ': ' . triage_builder_h(triage_builder_short_text($nextByOrderText, 60)) : ' / end of set' ?></option>
                                                                                    <?php foreach ($flowQuestionIds as $nextFlowQuestionId): ?>
                                                                                        <?php
                                                                                            if ((int)$nextFlowQuestionId === $questionId) continue;
                                                                                            $nextQuestion = null;
                                                                                            foreach ($questions as $questionCandidate) {
                                                                                                if ((int)$questionCandidate['question_id'] === (int)$nextFlowQuestionId) {
                                                                                                    $nextQuestion = $questionCandidate;
                                                                                                    break;
                                                                                                }
                                                                                            }
                                                                                            if (!$nextQuestion) continue;
                                                                                            if ($questionIsGlobal && triage_builder_scope_label($nextQuestion) !== 'Global') continue;
                                                                                        ?>
                                                                                        <option value="<?= (int)$nextQuestion['question_id'] ?>" <?= (int)($answer['next_question_id'] ?? 0) === (int)$nextQuestion['question_id'] ? 'selected' : '' ?>>
                                                                                            <?= triage_builder_h(triage_builder_short_text($nextQuestion['question_text'], 80)) ?>
                                                                                        </option>
                                                                                    <?php endforeach; ?>
                                                                                </select>
                                                                            </div>

                                                                            <div class="xform-field">
                                                                                <label class="xform-label">Advice to give</label>
                                                                                <select name="advice_id" class="xform-input">
                                                                                    <option value="">No advice</option>
                                                                                    <?php foreach ($adviceItems as $advice): ?>
                                                                                        <?php
                                                                                            $adviceScope = triage_builder_scope_label($advice);
                                                                                            if ($questionIsGlobal && $adviceScope !== 'Global') continue;
                                                                                            if (!$builderIsAdmin && $adviceScope === 'Global' && empty($advice['active'])) continue;
                                                                                        ?>
                                                                                        <option value="<?= (int)$advice['advice_id'] ?>" <?= (int)($answer['advice_id'] ?? 0) === (int)$advice['advice_id'] ? 'selected' : '' ?>>
                                                                                            <?= triage_builder_h($adviceScope . ': ' . $advice['title']) ?>
                                                                                        </option>
                                                                                    <?php endforeach; ?>
                                                                                </select>
                                                                            </div>

                                                                            <div class="xform-field">
                                                                                <label class="xform-label">Suggest action</label>
                                                                                <select name="action_type" class="xform-input">
                                                                                    <?php foreach ($actionTypes as $actionKey => $actionLabel): ?>
                                                                                        <option value="<?= triage_builder_h($actionKey) ?>" <?= (string)($answer['action_type'] ?? '') === (string)$actionKey ? 'selected' : '' ?>>
                                                                                            <?= triage_builder_h($actionLabel) ?>
                                                                                        </option>
                                                                                    <?php endforeach; ?>
                                                                                </select>
                                                                            </div>

                                                                            <div class="xform-field">
                                                                                <label class="xform-label">Priority score</label>
                                                                                <input type="number" name="priority_score" class="xform-input" value="<?= triage_builder_h($answer['priority_score'] ?? '') ?>" placeholder="Optional">
                                                                            </div>

                                                                            <div class="xform-field">
                                                                                <label style="display:flex; gap:8px; align-items:center; min-height:38px; margin:0;">
                                                                                    <input type="checkbox" name="end_triage" value="1" <?= !empty($answer['end_triage']) ? 'checked' : '' ?>>
                                                                                    End triage after this answer
                                                                                </label>
                                                                            </div>

                                                                            <div class="xform-field">
                                                                                <button type="submit" name="action" value="update_answer_route" class="btn green" style="width:100%;">Save routing</button>
                                                                            </div>
                                                                        </div>
                                                                    </form>
                                                                <?php endforeach; ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div style="margin-top:12px;">
                                            <button form="<?= triage_builder_h($flowStructureFormId) ?>" type="submit" class="btn green">Save triage set structure</button>
                                        </div>
                                    </div>

                                    <div class="tb-add-path">
                                        <p class="tb-mini-heading">Add question to this set</p>
                                        <form method="post" action="<?= triage_builder_h($handlerPath) ?>" class="xform">
                                            <input type="hidden" name="action" value="add_flow_question">
                                            <input type="hidden" name="return_tab" value="builder">
                                            <input type="hidden" name="centre_id" value="<?= (int)$builderCentreId ?>">
                                            <input type="hidden" name="flow_id" value="<?= (int)$flow['flow_id'] ?>">
                                            <div class="rc-form-grid">
                                                <div class="xform-field">
                                                    <label class="xform-label">Question</label>
                                                    <select name="question_id" class="xform-input" required>
                                                        <option value="">Select question</option>
                                                        <?php foreach ($questions as $question): ?>
                                                            <?php
                                                                if (empty($question['active'])) continue;
                                                                $questionId = (int)$question['question_id'];
                                                                if (in_array($questionId, $flowQuestionIds, true)) continue;
                                                                $questionScope = triage_builder_scope_label($question);
                                                                if ($flowIsGlobal && $questionScope !== 'Global') continue;
                                                            ?>
                                                            <option value="<?= $questionId ?>">
                                                                <?= triage_builder_h($questionScope . ': ' . triage_builder_short_text($question['question_text'], 100)) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="xform-field">
                                                    <button type="submit" class="btn blue" style="width:100%;">+ Add question</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </details>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.rc-tab');
    const panels = document.querySelectorAll('.rc-tab-panel');

    function activateTab(name) {
        tabs.forEach(function (tab) {
            tab.classList.toggle('is-active', tab.dataset.tab === name);
        });
        panels.forEach(function (panel) {
            panel.classList.toggle('is-active', panel.dataset.panel === name);
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activateTab(tab.dataset.tab);
            if (history.replaceState) {
                history.replaceState(null, '', '#triage-' + tab.dataset.tab);
            }
        });
    });

    const initial = (window.location.hash || '').replace('#triage-', '');
    if (initial) {
        activateTab(initial);
    }
});
</script>


