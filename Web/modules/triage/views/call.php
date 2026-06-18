<?php
// modules/triage/views/call.php
if (!defined('APP_LOADED')) exit;

require_once __DIR__ . '/../controllers/triage_lib.php';

$triageCentreId = (int)($centre_id ?? $_SESSION['centre_id'] ?? $_SESSION['rescue_id'] ?? 0);
$handlerPath = 'modules/triage/controllers/triage_handler.php';
$flash = $_SESSION['triage_flash'] ?? null;
unset($_SESSION['triage_flash']);

$triageError = null;
$triageFlows = [];
$triageData = [
    'flows' => [],
    'questions' => [],
    'answersByQuestion' => [],
    'adviceById' => [],
    'species' => [],
    'speciesTypes' => [],
];

try {
    $flowStmt = $pdo->prepare("
        SELECT flow_id, centre_id, is_global, flow_name, start_question_id, question_order_json
        FROM rescue_triage_flows
        WHERE active = 1
          AND ((centre_id = 0 AND is_global = 1) OR centre_id = :centre_id)
        ORDER BY is_global DESC, flow_name ASC
    ");
    $flowStmt->execute([':centre_id' => $triageCentreId]);
    $triageFlows = $flowStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $questionIds = [];
    foreach ($triageFlows as $flow) {
        $flowQuestionIds = [];
        if (!empty($flow['question_order_json'])) {
            $decoded = json_decode((string)$flow['question_order_json'], true);
            if (is_array($decoded)) {
                $flowQuestionIds = array_values(array_filter(array_map('intval', $decoded)));
            }
        }
        if (empty($flowQuestionIds) && !empty($flow['start_question_id'])) {
            $flowQuestionIds[] = (int)$flow['start_question_id'];
        }
        $questionIds = array_merge($questionIds, $flowQuestionIds);
        $triageData['flows'][(int)$flow['flow_id']] = [
            'flow_id' => (int)$flow['flow_id'],
            'name' => (string)$flow['flow_name'],
            'start_question_id' => (int)$flow['start_question_id'],
            'question_ids' => $flowQuestionIds,
        ];
    }

    $questionIds = array_values(array_unique(array_filter($questionIds)));
    $questionDefaultNextSelect = triage_has_column($pdo, 'rescue_triage_questions', 'default_next_question_id') ? ", default_next_question_id" : ", NULL AS default_next_question_id";
    if (!empty($questionIds)) {
        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $questionStmt = $pdo->prepare("
            SELECT question_id, question_text, answer_type, help_text $questionDefaultNextSelect
            FROM rescue_triage_questions
            WHERE active = 1
              AND question_id IN ($placeholders)
        ");
        $questionStmt->execute($questionIds);
        foreach ($questionStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $question) {
            $triageData['questions'][(int)$question['question_id']] = [
                'question_id' => (int)$question['question_id'],
                'question_text' => (string)$question['question_text'],
                'answer_type' => (string)$question['answer_type'],
                'help_text' => (string)($question['help_text'] ?? ''),
                'default_next_question_id' => (int)($question['default_next_question_id'] ?? 0),
            ];
        }

        $answerStmt = $pdo->prepare("
            SELECT answer_id, question_id, answer_label, answer_value, next_question_id, advice_id, priority_score, action_type, end_triage, sort_order
            FROM rescue_triage_answers
            WHERE active = 1
              AND question_id IN ($placeholders)
            ORDER BY question_id ASC, sort_order ASC, answer_id ASC
        ");
        $answerStmt->execute($questionIds);
        foreach ($answerStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $answer) {
            $triageData['answersByQuestion'][(int)$answer['question_id']][] = [
                'answer_id' => (int)$answer['answer_id'],
                'question_id' => (int)$answer['question_id'],
                'answer_label' => (string)$answer['answer_label'],
                'answer_value' => (string)($answer['answer_value'] ?? ''),
                'next_question_id' => (int)($answer['next_question_id'] ?? 0),
                'advice_id' => (int)($answer['advice_id'] ?? 0),
                'priority_score' => $answer['priority_score'] !== null ? (int)$answer['priority_score'] : null,
                'action_type' => (string)($answer['action_type'] ?? ''),
                'end_triage' => !empty($answer['end_triage']),
            ];
        }
    }

    $adviceSpeciesSelect = triage_has_column($pdo, 'rescue_triage_advice', 'species_id') ? ", species_id" : ", NULL AS species_id";
    $adviceSpeciesTypeSelect = triage_has_column($pdo, 'rescue_triage_advice', 'species_type') ? ", species_type" : ", NULL AS species_type";
    $adviceStmt = $pdo->prepare("
        SELECT advice_id, title, advice_text $adviceSpeciesSelect $adviceSpeciesTypeSelect
        FROM rescue_triage_advice
        WHERE active = 1
          AND ((centre_id = 0 AND is_global = 1) OR centre_id = :centre_id)
        ORDER BY title ASC
    ");
    $adviceStmt->execute([':centre_id' => $triageCentreId]);
    foreach ($adviceStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $advice) {
        $triageData['adviceById'][(int)$advice['advice_id']] = [
            'advice_id' => (int)$advice['advice_id'],
            'title' => (string)$advice['title'],
            'advice_text' => (string)$advice['advice_text'],
            'species_id' => (int)($advice['species_id'] ?? 0),
            'species_type' => (string)($advice['species_type'] ?? ''),
        ];
    }

    $speciesStmt = $pdo->query("
        SELECT species_id, species_name, animal_type
        FROM rescue_animal_species
        ORDER BY species_name ASC
    ");
    foreach ($speciesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $species) {
        $animalType = (string)($species['animal_type'] ?? '');
        $triageData['species'][] = [
            'species_id' => (int)$species['species_id'],
            'species_name' => (string)$species['species_name'],
            'animal_type' => $animalType,
            'label' => trim((string)$species['species_name'] . ($animalType !== '' ? ' - ' . $animalType : '')),
        ];
        if ($animalType !== '' && !in_array($animalType, $triageData['speciesTypes'], true)) {
            $triageData['speciesTypes'][] = $animalType;
        }
    }
} catch (Throwable $e) {
    $triageError = $e->getMessage();
}

$triageJson = json_encode($triageData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
?>

<style>
    .triage-shell {
        display:grid;
        grid-template-columns:minmax(0, 1.35fr) minmax(300px, .65fr);
        gap:14px;
        align-items:start;
    }
    .triage-step-head {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:12px;
        margin-bottom:12px;
    }
    .triage-question { font-size:1.25rem; line-height:1.25; margin:0; color:#111827; }
    .triage-answer {
        align-items:flex-start;
        cursor:pointer;
    }
    .triage-advice-item textarea { min-height:76px; }
    .triage-hidden { display:none !important; }
    @media (max-width: 950px) {
        .triage-shell { grid-template-columns:1fr; }
    }
</style>

<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2>Triage Call</h2>
            <p>Draft call screen for testing triage sets, advice and outcomes.</p>
        </div>
    </div>
</div>

<div class="rc-stack">
    <?php if ($flash): ?>
        <div class="alert-box alert-<?= ($flash['type'] ?? '') === 'success' ? 'green' : 'red' ?>">
            <?= triage_h($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <div class="alert-box alert-amber">
        <strong>Draft module</strong><br>
        This is a working preview so you can test the triage flow before it is used live.
    </div>

    <?php if ($triageError): ?>
        <div class="alert-box alert-red"><?= triage_h($triageError) ?></div>
    <?php elseif (empty($triageData['flows'])): ?>
        <div class="alert-box alert-grey">No active triage sets are available yet. Create and activate one in Triage Questions.</div>
    <?php else: ?>
        <div class="triage-shell">
            <div class="rc-panel">
                <div class="rc-form-grid">
                    <div class="xform-field">
                        <label class="xform-label">Triage set</label>
                        <select id="triage-flow" class="xform-input">
                            <?php foreach ($triageData['flows'] as $flow): ?>
                                <option value="<?= (int)$flow['flow_id'] ?>"><?= triage_h($flow['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="xform-field">
                        <button type="button" id="triage-start" class="btn green" style="width:100%;">Start call</button>
                    </div>
                </div>

                <div id="triage-question-panel" class="triage-hidden" style="margin-top:14px;">
                    <div class="triage-step-head">
                        <div>
                            <div id="triage-step-label" class="triage-pill">Question</div>
                            <h3 id="triage-question-text" class="triage-question"></h3>
                            <div id="triage-help" class="rc-muted"></div>
                        </div>
                    </div>
                    <div id="triage-answer-area"></div>
                    <div class="rc-actions" style="margin-top:12px;">
                        <button type="button" id="triage-back" class="btn grey">Back</button>
                        <button type="button" id="triage-next" class="btn blue">Continue</button>
                    </div>
                </div>

                <form id="triage-final-form" method="post" action="<?= triage_h($handlerPath) ?>" class="xform triage-hidden" style="margin-top:14px;">
                    <input type="hidden" name="action" value="save_call">
                    <input type="hidden" name="centre_id" value="<?= (int)$triageCentreId ?>">
                    <input type="hidden" name="flow_id" id="triage-save-flow-id">
                    <input type="hidden" name="answers_json" id="triage-save-answers">
                    <input type="hidden" name="advice_given_json" id="triage-save-advice">
                    <input type="hidden" name="priority" id="triage-save-priority">
                    <input type="hidden" name="action_type" id="triage-save-action-type">
                    <input type="hidden" name="species_id" id="triage-save-species-id">
                    <input type="hidden" name="species_guess" id="triage-save-species-guess">

                    <h3>Complete call log</h3>
                    <p class="rc-muted">Add finder and animal details before saving the call.</p>

                    <div class="rc-form-grid" style="margin-top:10px;">
                        <div class="xform-field">
                            <label class="xform-label">Finder name</label>
                            <input type="text" name="finder_name" class="xform-input">
                        </div>
                        <div class="xform-field">
                            <label class="xform-label">Contact number</label>
                            <input type="text" name="finder_phone" class="xform-input" required>
                        </div>
                        <div class="xform-field span-2">
                            <label class="xform-label">Finder address</label>
                            <textarea name="finder_address" class="xform-input" rows="2" required></textarea>
                        </div>
                        <div class="xform-field">
                            <label class="xform-label">Finder postcode</label>
                            <input type="text" name="finder_postcode" class="xform-input">
                        </div>
                        <div class="xform-field">
                            <label class="xform-label">Animal postcode</label>
                            <input type="text" name="animal_postcode" class="xform-input">
                        </div>
                        <div class="xform-field span-2">
                            <label class="xform-label">Animal location</label>
                            <textarea name="animal_location" class="xform-input" rows="2" required></textarea>
                        </div>
                        <div class="xform-field span-2">
                            <label class="xform-label">Presenting complaint</label>
                            <input type="text" name="presenting_complaint" id="triage-presenting-complaint" class="xform-input">
                        </div>
                        <div class="xform-field span-2">
                            <label class="xform-label">Action notes</label>
                            <input type="text" name="action_notes" class="xform-input">
                        </div>
                        <div class="xform-field span-2">
                            <label class="xform-label">Call notes</label>
                            <textarea name="call_notes" class="xform-input" rows="3"></textarea>
                        </div>
                    </div>

                    <div style="margin-top:12px;">
                        <button type="submit" class="btn green">Save triage call</button>
                        <button type="button" id="triage-restart" class="btn grey">Start again</button>
                    </div>
                </form>
            </div>

            <div class="rc-panel">
                <h3>Call summary</h3>
                <p class="rc-muted">Answers, advice and suggested outcome build as the call progresses.</p>
                <div id="triage-status" class="rc-list" style="margin-top:10px;">
                    <div class="rc-card rc-card-muted">Choose a triage set and start the call.</div>
                </div>
                <div id="triage-advice-list" class="rc-list" style="margin-top:10px;"></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<datalist id="triage-species-list"></datalist>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const triageData = <?= $triageJson ?: '{}' ?>;
    const flowSelect = document.getElementById('triage-flow');
    const startButton = document.getElementById('triage-start');
    const questionPanel = document.getElementById('triage-question-panel');
    const finalForm = document.getElementById('triage-final-form');
    if (!flowSelect || !startButton || !questionPanel || !finalForm) return;

    const questionText = document.getElementById('triage-question-text');
    const helpText = document.getElementById('triage-help');
    const stepLabel = document.getElementById('triage-step-label');
    const answerArea = document.getElementById('triage-answer-area');
    const statusBox = document.getElementById('triage-status');
    const adviceBox = document.getElementById('triage-advice-list');
    const speciesList = document.getElementById('triage-species-list');
    const backButton = document.getElementById('triage-back');
    const nextButton = document.getElementById('triage-next');
    const restartButton = document.getElementById('triage-restart');

    const actionLabels = {
        advice_only: 'Advice only',
        collection: 'Collection',
        vet: 'Vet',
        disposal: 'Disposal',
        callback: 'Callback',
        admit: 'Admit'
    };

    const state = {
        flow: null,
        currentQuestionId: 0,
        history: [],
        answers: [],
        advice: {},
        priority: 0,
        actionType: '',
        speciesId: 0,
        speciesGuess: '',
        speciesType: ''
    };

    (triageData.species || []).forEach(function (species) {
        const option = document.createElement('option');
        option.value = species.label;
        speciesList.appendChild(option);
    });
    (triageData.speciesTypes || []).forEach(function (type) {
        const option = document.createElement('option');
        option.value = 'Type: ' + type;
        speciesList.appendChild(option);
    });

    function resetState() {
        state.flow = triageData.flows[flowSelect.value] || null;
        state.currentQuestionId = state.flow ? state.flow.start_question_id : 0;
        state.history = [];
        state.answers = [];
        state.advice = {};
        state.priority = 0;
        state.actionType = '';
        state.speciesId = 0;
        state.speciesGuess = '';
        state.speciesType = '';
    }

    function getQuestion(questionId) {
        return triageData.questions[String(questionId)] || triageData.questions[questionId] || null;
    }

    function getAnswers(questionId) {
        return triageData.answersByQuestion[String(questionId)] || triageData.answersByQuestion[questionId] || [];
    }

    function nextByOrder(questionId) {
        if (!state.flow) return 0;
        const ids = state.flow.question_ids || [];
        const idx = ids.map(Number).indexOf(Number(questionId));
        return idx >= 0 && ids[idx + 1] ? Number(ids[idx + 1]) : 0;
    }

    function syncSummary() {
        const rows = [];
        rows.push('<div class="rc-card rc-card-muted"><strong>Set</strong><br>' + escapeHtml(state.flow ? state.flow.name : '-') + '</div>');
        rows.push('<div class="rc-card rc-card-muted"><strong>Suggested action</strong><br>' + escapeHtml(actionLabels[state.actionType] || 'Not set') + '</div>');
        rows.push('<div class="rc-card rc-card-muted"><strong>Priority</strong><br>' + (state.priority || '-') + '</div>');
        if (state.speciesGuess) {
            rows.push('<div class="rc-card rc-card-muted"><strong>Species/type</strong><br>' + escapeHtml(state.speciesGuess) + '</div>');
        }
        if (state.answers.length) {
            rows.push('<div class="rc-card rc-card-muted"><strong>Answers</strong><br>' + state.answers.map(function (answer) {
                return escapeHtml(answer.question_text + ': ' + answer.answer_label);
            }).join('<br>') + '</div>');
        }
        statusBox.innerHTML = rows.join('');

        const adviceItems = Object.values(state.advice);
        adviceBox.innerHTML = adviceItems.map(function (advice) {
            return '<div class="rc-card rc-card-muted triage-advice-item"><strong>' + escapeHtml(advice.title) + '</strong><textarea class="xform-input triage-advice-text" data-advice-id="' + advice.advice_id + '">' + escapeHtml(advice.advice_text) + '</textarea></div>';
        }).join('');
    }

    function renderQuestion() {
        const question = getQuestion(state.currentQuestionId);
        if (!question) {
            showFinal();
            return;
        }

        finalForm.classList.add('triage-hidden');
        questionPanel.classList.remove('triage-hidden');
        questionText.textContent = question.question_text;
        helpText.textContent = question.help_text || '';
        const pos = state.flow.question_ids.map(Number).indexOf(Number(question.question_id)) + 1;
        stepLabel.textContent = 'Question ' + (pos > 0 ? pos : state.answers.length + 1);
        answerArea.innerHTML = '';

        if (question.answer_type === 'species_search') {
            answerArea.innerHTML = '<label class="xform-label">Species or type</label><input type="text" id="triage-species-input" class="xform-input" list="triage-species-list" placeholder="Start typing species or type">';
            return;
        }

        if (question.answer_type === 'text') {
            answerArea.innerHTML = '<label class="xform-label">Answer</label><textarea id="triage-text-answer" class="xform-input" rows="4"></textarea>';
            return;
        }

        const answers = getAnswers(question.question_id);
        const inputType = question.answer_type === 'multi' ? 'checkbox' : 'radio';
        answerArea.innerHTML = '<div class="rc-list" style="margin-top:12px;">' + answers.map(function (answer) {
            return '<label class="rc-item triage-answer"><input type="' + inputType + '" name="triage_answer" value="' + answer.answer_id + '"><span>' + escapeHtml(answer.answer_label) + '</span></label>';
        }).join('') + '</div>';
    }

    function selectedAnswers(question) {
        if (question.answer_type === 'species_search') {
            const input = document.getElementById('triage-species-input');
            const value = input ? input.value.trim() : '';
            if (!value) return [];
            const match = (triageData.species || []).find(function (species) {
                return species.label.toLowerCase() === value.toLowerCase();
            });
            if (match) {
                state.speciesId = Number(match.species_id || 0);
                state.speciesType = match.animal_type || '';
                state.speciesGuess = match.label;
            } else {
                state.speciesId = 0;
                state.speciesType = value.indexOf('Type: ') === 0 ? value.substring(6) : '';
                state.speciesGuess = value;
            }
            return [{
                answer_id: 0,
                answer_label: value,
                question_id: question.question_id
            }];
        }

        if (question.answer_type === 'text') {
            const input = document.getElementById('triage-text-answer');
            const value = input ? input.value.trim() : '';
            return [{
                answer_id: 0,
                answer_label: value,
                question_id: question.question_id
            }];
        }

        const checked = Array.from(answerArea.querySelectorAll('input[name="triage_answer"]:checked')).map(function (input) {
            return Number(input.value);
        });
        const answers = getAnswers(question.question_id);
        return answers.filter(function (answer) {
            return checked.indexOf(Number(answer.answer_id)) !== -1;
        });
    }

    function continueFlow() {
        const question = getQuestion(state.currentQuestionId);
        if (!question) return;
        const answers = selectedAnswers(question);
        if (!answers.length || answers.every(function (answer) { return !String(answer.answer_label || '').trim(); })) {
            alert('Please record an answer before continuing.');
            return;
        }

        state.history.push(state.currentQuestionId);
        let nextQuestionId = question.answer_type === 'species_search'
            ? Number(question.default_next_question_id || 0) || nextByOrder(question.question_id)
            : nextByOrder(question.question_id);
        let shouldEnd = false;

        answers.forEach(function (answer) {
            state.answers.push({
                question_id: question.question_id,
                question_text: question.question_text,
                answer_id: answer.answer_id || 0,
                answer_label: answer.answer_label || ''
            });
            if (answer.priority_score !== null && answer.priority_score !== undefined) {
                state.priority = Math.max(state.priority, Number(answer.priority_score || 0));
            }
            if (answer.action_type) {
                state.actionType = answer.action_type;
            }
            if (answer.advice_id && triageData.adviceById[answer.advice_id]) {
                state.advice[answer.advice_id] = triageData.adviceById[answer.advice_id];
            }
            if (answer.end_triage) {
                shouldEnd = true;
            } else if (answer.next_question_id) {
                nextQuestionId = Number(answer.next_question_id);
            }
        });

        addSpeciesAdvice();
        syncSummary();
        if (shouldEnd || !nextQuestionId) {
            showFinal();
            return;
        }
        state.currentQuestionId = nextQuestionId;
        renderQuestion();
    }

    function addSpeciesAdvice() {
        Object.values(triageData.adviceById || {}).forEach(function (advice) {
            if (advice.species_id && state.speciesId && Number(advice.species_id) === Number(state.speciesId)) {
                state.advice[advice.advice_id] = advice;
            } else if (advice.species_type && state.speciesType && String(advice.species_type).toLowerCase() === String(state.speciesType).toLowerCase()) {
                state.advice[advice.advice_id] = advice;
            }
        });
    }

    function showFinal() {
        questionPanel.classList.add('triage-hidden');
        finalForm.classList.remove('triage-hidden');
        document.getElementById('triage-save-flow-id').value = state.flow ? state.flow.flow_id : '';
        document.getElementById('triage-save-answers').value = JSON.stringify(state.answers);
        document.getElementById('triage-save-priority').value = state.priority || '';
        document.getElementById('triage-save-action-type').value = state.actionType || '';
        document.getElementById('triage-save-species-id').value = state.speciesId || '';
        document.getElementById('triage-save-species-guess').value = state.speciesGuess || '';
        if (!document.getElementById('triage-presenting-complaint').value && state.answers.length) {
            document.getElementById('triage-presenting-complaint').value = state.answers[0].answer_label || '';
        }
        syncAdviceHidden();
        syncSummary();
    }

    function syncAdviceHidden() {
        const editedAdvice = Array.from(document.querySelectorAll('.triage-advice-text')).map(function (textarea) {
            const id = Number(textarea.dataset.adviceId || 0);
            const original = state.advice[id] || {};
            return {
                advice_id: id,
                title: original.title || 'Advice',
                advice_text: textarea.value
            };
        });
        if (!editedAdvice.length) {
            Object.values(state.advice).forEach(function (advice) {
                editedAdvice.push({
                    advice_id: advice.advice_id,
                    title: advice.title,
                    advice_text: advice.advice_text
                });
            });
        }
        document.getElementById('triage-save-advice').value = JSON.stringify(editedAdvice);
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]);
        });
    }

    startButton.addEventListener('click', function () {
        resetState();
        if (!state.flow || !state.currentQuestionId) {
            alert('This triage set has no start question.');
            return;
        }
        renderQuestion();
        syncSummary();
    });

    nextButton.addEventListener('click', continueFlow);
    backButton.addEventListener('click', function () {
        const previousId = state.history.pop();
        if (!previousId) return;
        state.answers.pop();
        state.currentQuestionId = previousId;
        renderQuestion();
        syncSummary();
    });
    restartButton.addEventListener('click', function () {
        resetState();
        finalForm.classList.add('triage-hidden');
        renderQuestion();
        syncSummary();
    });
    finalForm.addEventListener('submit', syncAdviceHidden);
});
</script>
