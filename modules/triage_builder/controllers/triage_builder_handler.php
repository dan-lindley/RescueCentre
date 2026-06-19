<?php
require_once __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/triage_builder_lib.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$userId = triage_builder_user_id();
$centreId = triage_builder_centre_id();
$action = (string)($_POST['action'] ?? '');

if ($userId <= 0 || $centreId <= 0) {
    triage_builder_flash('error', 'Builder action failed: user or centre context missing.');
    triage_builder_redirect();
}

try {
    $isAdmin = triage_builder_is_admin($pdo, $userId);
    $scope = triage_builder_scope_from_post($isAdmin, $centreId);

    if ($action === 'create_advice') {
        $title = trim((string)($_POST['title'] ?? ''));
        $adviceText = trim((string)($_POST['advice_text'] ?? ''));
        $adviceSpeciesId = (int)($_POST['species_id'] ?? 0);
        $adviceSpeciesType = trim((string)($_POST['species_type'] ?? ''));

        if ($title === '' || $adviceText === '') {
            throw new RuntimeException('Advice title and text are required.');
        }

        $fields = ['centre_id', 'is_global', 'title', 'advice_text', 'active'];
        $placeholders = [':centre_id', ':is_global', ':title', ':advice_text', '1'];
        $params = [
            ':centre_id' => $scope['centre_id'],
            ':is_global' => $scope['is_global'],
            ':title' => $title,
            ':advice_text' => $adviceText,
        ];

        if (triage_builder_has_column($pdo, 'rescue_triage_advice', 'species_id')) {
            $fields[] = 'species_id';
            $placeholders[] = ':species_id';
            $params[':species_id'] = $adviceSpeciesId > 0 ? $adviceSpeciesId : null;
        }

        if (triage_builder_has_column($pdo, 'rescue_triage_advice', 'species_type')) {
            $fields[] = 'species_type';
            $placeholders[] = ':species_type';
            $params[':species_type'] = $adviceSpeciesType !== '' && $adviceSpeciesId <= 0 ? $adviceSpeciesType : null;
        }

        $stmt = $pdo->prepare("
            INSERT INTO rescue_triage_advice
                (" . implode(', ', $fields) . ")
            VALUES
                (" . implode(', ', $placeholders) . ")
        ");
        $stmt->execute($params);

        triage_builder_flash('success', 'Advice template added.');
        triage_builder_redirect();
    }

    if ($action === 'create_question') {
        $questionText = trim((string)($_POST['question_text'] ?? ''));
        $answerType = (string)($_POST['answer_type'] ?? 'select');
        $helpText = trim((string)($_POST['help_text'] ?? ''));
        $allowedTypes = ['yes_no', 'select', 'multi', 'text'];
        $supportsSpeciesSearchQuestion = triage_builder_enum_has_value($pdo, 'rescue_triage_questions', 'answer_type', 'species_search');
        if ($supportsSpeciesSearchQuestion) {
            $allowedTypes[] = 'species_search';
        }

        if ($questionText === '') {
            throw new RuntimeException('Question text is required.');
        }
        if ($answerType === 'species_search' && !$supportsSpeciesSearchQuestion) {
            throw new RuntimeException('Run the species search question SQL before creating species search questions.');
        }
        if (!in_array($answerType, $allowedTypes, true)) {
            $answerType = 'select';
        }

        $stmt = $pdo->prepare("
            INSERT INTO rescue_triage_questions
                (centre_id, is_global, question_text, answer_type, help_text, active)
            VALUES
                (:centre_id, :is_global, :question_text, :answer_type, :help_text, 1)
        ");
        $stmt->execute([
            ':centre_id' => $scope['centre_id'],
            ':is_global' => $scope['is_global'],
            ':question_text' => $questionText,
            ':answer_type' => $answerType,
            ':help_text' => $helpText !== '' ? $helpText : null,
        ]);

        triage_builder_flash('success', 'Triage question added.');
        triage_builder_redirect();
    }

    if ($action === 'update_question') {
        $questionId = (int)($_POST['question_id'] ?? 0);
        $questionText = trim((string)($_POST['question_text'] ?? ''));
        $answerType = (string)($_POST['answer_type'] ?? 'select');
        $helpText = trim((string)($_POST['help_text'] ?? ''));
        $allowedTypes = ['yes_no', 'select', 'multi', 'text'];
        $supportsSpeciesSearchQuestion = triage_builder_enum_has_value($pdo, 'rescue_triage_questions', 'answer_type', 'species_search');
        if ($supportsSpeciesSearchQuestion) {
            $allowedTypes[] = 'species_search';
        }

        if ($questionId <= 0 || $questionText === '') {
            throw new RuntimeException('Question text is required.');
        }
        if ($answerType === 'species_search' && !$supportsSpeciesSearchQuestion) {
            throw new RuntimeException('Run the species search question SQL before using species search questions.');
        }
        if (!in_array($answerType, $allowedTypes, true)) {
            $answerType = 'select';
        }

        $questionStmt = $pdo->prepare("
            SELECT question_id, centre_id, is_global, answer_type
            FROM rescue_triage_questions
            WHERE question_id = ?
              AND ((centre_id = 0 AND is_global = 1) OR centre_id = ?)
            LIMIT 1
        ");
        $questionStmt->execute([$questionId, $centreId]);
        $question = $questionStmt->fetch(PDO::FETCH_ASSOC);
        if (!$question || !triage_builder_can_manage_row($question, $centreId, $isAdmin)) {
            throw new RuntimeException('You cannot edit this question.');
        }

        if ($answerType === 'species_search' && (string)$question['answer_type'] !== 'species_search') {
            $answerCountStmt = $pdo->prepare("SELECT COUNT(*) FROM rescue_triage_answers WHERE question_id = ?");
            $answerCountStmt->execute([$questionId]);
            if ((int)$answerCountStmt->fetchColumn() > 0) {
                throw new RuntimeException('Remove this question\'s answer options before changing it to species search.');
            }
        }

        $updateScope = triage_builder_scope_from_post($isAdmin, $centreId);
        if (!$isAdmin) {
            $updateScope = [
                'centre_id' => (int)$question['centre_id'],
                'is_global' => (int)$question['is_global'],
            ];
        }

        $stmt = $pdo->prepare("
            UPDATE rescue_triage_questions
            SET centre_id = :centre_id,
                is_global = :is_global,
                question_text = :question_text,
                answer_type = :answer_type,
                help_text = :help_text,
                updated_at = CURRENT_TIMESTAMP
            WHERE question_id = :question_id
        ");
        $stmt->execute([
            ':centre_id' => $updateScope['centre_id'],
            ':is_global' => $updateScope['is_global'],
            ':question_text' => $questionText,
            ':answer_type' => $answerType,
            ':help_text' => $helpText !== '' ? $helpText : null,
            ':question_id' => $questionId,
        ]);

        triage_builder_flash('success', 'Question updated.');
        triage_builder_redirect(['focus_question' => $questionId]);
    }

    if ($action === 'update_question_default_route') {
        $questionId = (int)($_POST['question_id'] ?? 0);
        $nextQuestionId = (int)($_POST['default_next_question_id'] ?? 0);

        if (!triage_builder_has_column($pdo, 'rescue_triage_questions', 'default_next_question_id')) {
            throw new RuntimeException('Run the species search question SQL before saving this route.');
        }
        if ($questionId <= 0) {
            throw new RuntimeException('Question route not selected.');
        }

        $questionStmt = $pdo->prepare("
            SELECT question_id, centre_id, is_global, answer_type
            FROM rescue_triage_questions
            WHERE question_id = ?
              AND ((centre_id = 0 AND is_global = 1) OR centre_id = ?)
            LIMIT 1
        ");
        $questionStmt->execute([$questionId, $centreId]);
        $question = $questionStmt->fetch(PDO::FETCH_ASSOC);
        if (!$question || !triage_builder_can_manage_row($question, $centreId, $isAdmin)) {
            throw new RuntimeException('You cannot edit this question route.');
        }
        if ((string)$question['answer_type'] !== 'species_search') {
            throw new RuntimeException('Default routing is only used by species search questions.');
        }

        $questionIsGlobal = (int)$question['centre_id'] === 0 || !empty($question['is_global']);
        if ($nextQuestionId > 0) {
            if ($nextQuestionId === $questionId) {
                throw new RuntimeException('A question cannot route to itself.');
            }
            $nextStmt = $pdo->prepare("
                SELECT question_id, centre_id, is_global
                FROM rescue_triage_questions
                WHERE question_id = ?
                  AND ((centre_id = 0 AND is_global = 1) OR centre_id = ?)
                LIMIT 1
            ");
            $nextStmt->execute([$nextQuestionId, $centreId]);
            $nextQuestion = $nextStmt->fetch(PDO::FETCH_ASSOC);
            if (!$nextQuestion) {
                throw new RuntimeException('Next question is not available.');
            }
            if ($questionIsGlobal && ((int)$nextQuestion['centre_id'] !== 0 || empty($nextQuestion['is_global']))) {
                throw new RuntimeException('Global species search questions can only route to global questions.');
            }
        }

        $stmt = $pdo->prepare("
            UPDATE rescue_triage_questions
            SET default_next_question_id = :default_next_question_id,
                updated_at = CURRENT_TIMESTAMP
            WHERE question_id = :question_id
        ");
        $stmt->execute([
            ':default_next_question_id' => $nextQuestionId > 0 ? $nextQuestionId : null,
            ':question_id' => $questionId,
        ]);

        triage_builder_flash('success', 'Species search route override updated.');
        triage_builder_redirect();
    }

    if ($action === 'create_flow') {
        $flowName = trim((string)($_POST['flow_name'] ?? ''));
        $startQuestionId = (int)($_POST['start_question_id'] ?? 0);

        if ($flowName === '' || $startQuestionId <= 0) {
            throw new RuntimeException('Flow name and start question are required.');
        }

        $questionStmt = $pdo->prepare("
            SELECT question_id, centre_id, is_global
            FROM rescue_triage_questions
            WHERE question_id = ?
              AND ((centre_id = 0 AND is_global = 1) OR centre_id = ? OR ? = 1)
            LIMIT 1
        ");
        $questionStmt->execute([$startQuestionId, $centreId, $isAdmin ? 1 : 0]);
        $startQuestion = $questionStmt->fetch(PDO::FETCH_ASSOC);
        if (!$startQuestion) {
            throw new RuntimeException('Start question is not available.');
        }
        if (!empty($scope['is_global']) && ((int)$startQuestion['centre_id'] !== 0 || empty($startQuestion['is_global']))) {
            throw new RuntimeException('Global flows must start with a global question.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO rescue_triage_flows
                (centre_id, is_global, flow_name, start_question_id, question_order_json, active)
            VALUES
                (:centre_id, :is_global, :flow_name, :start_question_id, NULL, 1)
        ");
        $stmt->execute([
            ':centre_id' => $scope['centre_id'],
            ':is_global' => $scope['is_global'],
            ':flow_name' => $flowName,
            ':start_question_id' => $startQuestionId,
        ]);

        triage_builder_flash('success', 'Triage flow added.');
        triage_builder_redirect();
    }

    if ($action === 'update_flow_structure') {
        $flowId = (int)($_POST['flow_id'] ?? 0);
        $flowName = trim((string)($_POST['flow_name'] ?? ''));
        $startQuestionId = (int)($_POST['start_question_id'] ?? 0);
        $selectedQuestionIds = array_map('intval', (array)($_POST['question_ids'] ?? []));
        $removeQuestionIds = array_map('intval', (array)($_POST['remove_question_ids'] ?? []));
        $questionOrder = (array)($_POST['question_order'] ?? []);

        if ($flowId <= 0 || $flowName === '' || $startQuestionId <= 0) {
            throw new RuntimeException('Triage set name and start question are required.');
        }

        $flowStmt = $pdo->prepare("
            SELECT flow_id, centre_id, is_global
            FROM rescue_triage_flows
            WHERE flow_id = :flow_id
              AND (:is_admin = 1 OR (centre_id = :centre_id AND is_global = 0))
            LIMIT 1
        ");
        $flowStmt->execute([
            ':flow_id' => $flowId,
            ':is_admin' => $isAdmin ? 1 : 0,
            ':centre_id' => $centreId,
        ]);
        $flow = $flowStmt->fetch(PDO::FETCH_ASSOC);
        if (!$flow) {
            throw new RuntimeException('You cannot edit this triage set.');
        }

        $selectedQuestionIds = array_values(array_diff($selectedQuestionIds, $removeQuestionIds));
        $selectedQuestionIds = array_values(array_unique(array_filter($selectedQuestionIds)));
        if (empty($selectedQuestionIds)) {
            throw new RuntimeException('Select at least one question for this triage set.');
        }
        if (!in_array($startQuestionId, $selectedQuestionIds, true)) {
            throw new RuntimeException('The start question must be one of the questions currently in the triage set.');
        }

        $questionPlaceholders = implode(',', array_fill(0, count($selectedQuestionIds), '?'));
        $questionSqlParams = $selectedQuestionIds;
        $questionSqlParams[] = $centreId;
        $questionStmt = $pdo->prepare("
            SELECT question_id, centre_id, is_global
            FROM rescue_triage_questions
            WHERE question_id IN ($questionPlaceholders)
              AND ((centre_id = 0 AND is_global = 1) OR centre_id = ?)
        ");
        $questionStmt->execute($questionSqlParams);
        $availableQuestions = [];
        foreach ($questionStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $questionRow) {
            $availableQuestions[(int)$questionRow['question_id']] = $questionRow;
        }
        foreach ($selectedQuestionIds as $selectedQuestionId) {
            if (!isset($availableQuestions[$selectedQuestionId])) {
                throw new RuntimeException('One or more selected questions are not available.');
            }
            if (!empty($flow['is_global']) && ((int)$availableQuestions[$selectedQuestionId]['centre_id'] !== 0 || empty($availableQuestions[$selectedQuestionId]['is_global']))) {
                throw new RuntimeException('Global triage sets can only contain global questions.');
            }
        }

        usort($selectedQuestionIds, static function ($left, $right) use ($questionOrder) {
            $leftOrder = isset($questionOrder[$left]) ? (int)$questionOrder[$left] : 9999;
            $rightOrder = isset($questionOrder[$right]) ? (int)$questionOrder[$right] : 9999;
            return $leftOrder === $rightOrder ? $left <=> $right : $leftOrder <=> $rightOrder;
        });

        $stmt = $pdo->prepare("
            UPDATE rescue_triage_flows
            SET flow_name = :flow_name,
                start_question_id = :start_question_id,
                question_order_json = :question_order_json,
                updated_at = CURRENT_TIMESTAMP
            WHERE flow_id = :flow_id
              AND (:is_admin = 1 OR (centre_id = :centre_id AND is_global = 0))
        ");
        $stmt->execute([
            ':flow_name' => $flowName,
            ':start_question_id' => $startQuestionId,
            ':question_order_json' => json_encode(array_values($selectedQuestionIds)),
            ':flow_id' => $flowId,
            ':is_admin' => $isAdmin ? 1 : 0,
            ':centre_id' => $centreId,
        ]);

        triage_builder_flash('success', 'Triage set updated.');
        triage_builder_redirect();
    }

    if ($action === 'add_flow_question') {
        $flowId = (int)($_POST['flow_id'] ?? 0);
        $questionId = (int)($_POST['question_id'] ?? 0);

        if ($flowId <= 0 || $questionId <= 0) {
            throw new RuntimeException('Select a triage set and question.');
        }

        $flowStmt = $pdo->prepare("
            SELECT flow_id, centre_id, is_global, start_question_id, question_order_json
            FROM rescue_triage_flows
            WHERE flow_id = :flow_id
              AND (:is_admin = 1 OR (centre_id = :centre_id AND is_global = 0))
            LIMIT 1
        ");
        $flowStmt->execute([
            ':flow_id' => $flowId,
            ':is_admin' => $isAdmin ? 1 : 0,
            ':centre_id' => $centreId,
        ]);
        $flow = $flowStmt->fetch(PDO::FETCH_ASSOC);
        if (!$flow) {
            throw new RuntimeException('You cannot edit this triage set.');
        }

        $questionStmt = $pdo->prepare("
            SELECT question_id, centre_id, is_global
            FROM rescue_triage_questions
            WHERE question_id = ?
              AND ((centre_id = 0 AND is_global = 1) OR centre_id = ?)
            LIMIT 1
        ");
        $questionStmt->execute([$questionId, $centreId]);
        $question = $questionStmt->fetch(PDO::FETCH_ASSOC);
        if (!$question) {
            throw new RuntimeException('Question is not available.');
        }
        if (!empty($flow['is_global']) && ((int)$question['centre_id'] !== 0 || empty($question['is_global']))) {
            throw new RuntimeException('Global triage sets can only contain global questions.');
        }

        $questionIds = [];
        if (!empty($flow['question_order_json'])) {
            $decoded = json_decode((string)$flow['question_order_json'], true);
            if (is_array($decoded)) {
                $questionIds = array_values(array_filter(array_map('intval', $decoded)));
            }
        }
        if (empty($questionIds) && !empty($flow['start_question_id'])) {
            $questionIds[] = (int)$flow['start_question_id'];
        }
        if (!in_array($questionId, $questionIds, true)) {
            $questionIds[] = $questionId;
        }

        $stmt = $pdo->prepare("
            UPDATE rescue_triage_flows
            SET question_order_json = :question_order_json,
                updated_at = CURRENT_TIMESTAMP
            WHERE flow_id = :flow_id
              AND (:is_admin = 1 OR (centre_id = :centre_id AND is_global = 0))
        ");
        $stmt->execute([
            ':question_order_json' => json_encode(array_values($questionIds)),
            ':flow_id' => $flowId,
            ':is_admin' => $isAdmin ? 1 : 0,
            ':centre_id' => $centreId,
        ]);

        triage_builder_flash('success', 'Question added to triage set.');
        triage_builder_redirect();
    }

    if ($action === 'update_answer_route') {
        $answerId = (int)($_POST['answer_id'] ?? 0);
        $nextQuestionId = (int)($_POST['next_question_id'] ?? 0);
        $adviceId = (int)($_POST['advice_id'] ?? 0);
        $priorityScoreRaw = trim((string)($_POST['priority_score'] ?? ''));
        $actionType = trim((string)($_POST['action_type'] ?? ''));
        $endTriage = !empty($_POST['end_triage']) ? 1 : 0;
        if ($endTriage) {
            $nextQuestionId = 0;
        }

        if ($answerId <= 0) {
            throw new RuntimeException('Answer route not selected.');
        }

        $answerStmt = $pdo->prepare("
            SELECT a.answer_id, a.question_id, q.centre_id, q.is_global
            FROM rescue_triage_answers a
            INNER JOIN rescue_triage_questions q ON q.question_id = a.question_id
            WHERE a.answer_id = ?
              AND ((q.centre_id = 0 AND q.is_global = 1) OR q.centre_id = ?)
            LIMIT 1
        ");
        $answerStmt->execute([$answerId, $centreId]);
        $answer = $answerStmt->fetch(PDO::FETCH_ASSOC);
        if (!$answer || !triage_builder_can_manage_row($answer, $centreId, $isAdmin)) {
            throw new RuntimeException('You cannot edit this answer route.');
        }

        $questionIsGlobal = (int)$answer['centre_id'] === 0 || !empty($answer['is_global']);
        if ($nextQuestionId > 0) {
            $nextStmt = $pdo->prepare("
                SELECT question_id, centre_id, is_global
                FROM rescue_triage_questions
                WHERE question_id = ?
                  AND ((centre_id = 0 AND is_global = 1) OR centre_id = ?)
                LIMIT 1
            ");
            $nextStmt->execute([$nextQuestionId, $centreId]);
            $nextQuestion = $nextStmt->fetch(PDO::FETCH_ASSOC);
            if (!$nextQuestion) {
                throw new RuntimeException('Next question is not available.');
            }
            if ($questionIsGlobal && ((int)$nextQuestion['centre_id'] !== 0 || empty($nextQuestion['is_global']))) {
                throw new RuntimeException('Global answers can only route to global questions.');
            }
        }

        if ($adviceId > 0) {
            $adviceStmt = $pdo->prepare("
                SELECT advice_id, centre_id, is_global
                FROM rescue_triage_advice
                WHERE advice_id = ?
                  AND ((centre_id = 0 AND is_global = 1) OR centre_id = ?)
                LIMIT 1
            ");
            $adviceStmt->execute([$adviceId, $centreId]);
            $advice = $adviceStmt->fetch(PDO::FETCH_ASSOC);
            if (!$advice) {
                throw new RuntimeException('Advice template is not available.');
            }
            if ($questionIsGlobal && ((int)$advice['centre_id'] !== 0 || empty($advice['is_global']))) {
                throw new RuntimeException('Global answers can only trigger global advice.');
            }
        }

        $allowedActions = ['advice_only', 'collection', 'vet', 'disposal', 'callback', 'admit'];
        if (!in_array($actionType, $allowedActions, true)) {
            $actionType = null;
        }

        $stmt = $pdo->prepare("
            UPDATE rescue_triage_answers
            SET next_question_id = :next_question_id,
                advice_id = :advice_id,
                priority_score = :priority_score,
                action_type = :action_type,
                end_triage = :end_triage,
                updated_at = CURRENT_TIMESTAMP
            WHERE answer_id = :answer_id
        ");
        $stmt->execute([
            ':next_question_id' => $nextQuestionId > 0 ? $nextQuestionId : null,
            ':advice_id' => $adviceId > 0 ? $adviceId : null,
            ':priority_score' => $priorityScoreRaw !== '' ? (int)$priorityScoreRaw : null,
            ':action_type' => $actionType,
            ':end_triage' => $endTriage,
            ':answer_id' => $answerId,
        ]);

        triage_builder_flash('success', 'Answer routing updated.');
        triage_builder_redirect();
    }

    if ($action === 'create_answer') {
        $questionId = (int)($_POST['question_id'] ?? 0);
        $answerLabel = trim((string)($_POST['answer_label'] ?? ''));
        $answerValue = trim((string)($_POST['answer_value'] ?? ''));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if ($questionId <= 0 || $answerLabel === '') {
            throw new RuntimeException('Question and answer label are required.');
        }

        $questionStmt = $pdo->prepare("
            SELECT question_id, centre_id, is_global, answer_type
            FROM rescue_triage_questions
            WHERE question_id = ?
              AND ((centre_id = 0 AND is_global = 1) OR centre_id = ?)
            LIMIT 1
        ");
        $questionStmt->execute([$questionId, $centreId]);
        $question = $questionStmt->fetch(PDO::FETCH_ASSOC);
        if (!$question || !triage_builder_can_manage_row($question, $centreId, $isAdmin)) {
            throw new RuntimeException('You cannot add answers to this question.');
        }
        if ((string)$question['answer_type'] === 'species_search') {
            throw new RuntimeException('Species search questions do not use answer options. Set any route override in the Triage Builder tab.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO rescue_triage_answers
                (question_id, answer_label, answer_value, sort_order, active)
            VALUES
                (:question_id, :answer_label, :answer_value, :sort_order, 1)
        ");
        $stmt->execute([
            ':question_id' => $questionId,
            ':answer_label' => $answerLabel,
            ':answer_value' => $answerValue !== '' ? $answerValue : null,
            ':sort_order' => $sortOrder,
        ]);

        triage_builder_flash('success', 'Answer option added.');
        triage_builder_redirect(['focus_question' => $questionId]);
    }

    if ($action === 'update_answer') {
        $answerId = (int)($_POST['answer_id'] ?? 0);
        $answerLabel = trim((string)($_POST['answer_label'] ?? ''));
        $answerValue = trim((string)($_POST['answer_value'] ?? ''));
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if ($answerId <= 0 || $answerLabel === '') {
            throw new RuntimeException('Answer label is required.');
        }

        $answerStmt = $pdo->prepare("
            SELECT a.answer_id, a.question_id, q.centre_id, q.is_global
            FROM rescue_triage_answers a
            JOIN rescue_triage_questions q ON q.question_id = a.question_id
            WHERE a.answer_id = ?
              AND ((q.centre_id = 0 AND q.is_global = 1) OR q.centre_id = ?)
            LIMIT 1
        ");
        $answerStmt->execute([$answerId, $centreId]);
        $answer = $answerStmt->fetch(PDO::FETCH_ASSOC);
        if (!$answer || !triage_builder_can_manage_row($answer, $centreId, $isAdmin)) {
            throw new RuntimeException('You cannot edit this answer.');
        }

        $stmt = $pdo->prepare("
            UPDATE rescue_triage_answers
            SET answer_label = :answer_label,
                answer_value = :answer_value,
                sort_order = :sort_order,
                updated_at = CURRENT_TIMESTAMP
            WHERE answer_id = :answer_id
        ");
        $stmt->execute([
            ':answer_label' => $answerLabel,
            ':answer_value' => $answerValue !== '' ? $answerValue : null,
            ':sort_order' => $sortOrder,
            ':answer_id' => $answerId,
        ]);

        triage_builder_flash('success', 'Answer updated.');
        triage_builder_redirect(['focus_question' => (int)$answer['question_id']]);
    }

    if ($action === 'toggle_question') {
        $questionId = (int)($_POST['question_id'] ?? 0);
        $active = !empty($_POST['active']) ? 1 : 0;
        $stmt = $pdo->prepare("
            UPDATE rescue_triage_questions
            SET active = :active, updated_at = CURRENT_TIMESTAMP
            WHERE question_id = :question_id
              AND (:is_admin = 1 OR (centre_id = :centre_id AND is_global = 0))
        ");
        $stmt->execute([
            ':active' => $active,
            ':question_id' => $questionId,
            ':is_admin' => $isAdmin ? 1 : 0,
            ':centre_id' => $centreId,
        ]);
        triage_builder_flash('success', 'Question status updated.');
        triage_builder_redirect(['focus_question' => $questionId]);
    }

    if ($action === 'delete_question') {
        $questionId = (int)($_POST['question_id'] ?? 0);
        if ($questionId <= 0) {
            throw new RuntimeException('Question not selected.');
        }

        $questionStmt = $pdo->prepare("
            SELECT question_id, centre_id, is_global, active
            FROM rescue_triage_questions
            WHERE question_id = ?
              AND ((centre_id = 0 AND is_global = 1) OR centre_id = ?)
            LIMIT 1
        ");
        $questionStmt->execute([$questionId, $centreId]);
        $question = $questionStmt->fetch(PDO::FETCH_ASSOC);
        if (!$question || !triage_builder_can_manage_row($question, $centreId, $isAdmin)) {
            throw new RuntimeException('You cannot delete this question.');
        }
        if (!empty($question['active'])) {
            throw new RuntimeException('Deactivate the question before deleting it.');
        }

        $flowStmt = $pdo->prepare("
            SELECT start_question_id, question_order_json
            FROM rescue_triage_flows
            WHERE (centre_id = 0 AND is_global = 1)
               OR centre_id = ?
        ");
        $flowStmt->execute([$centreId]);
        foreach ($flowStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $flowRow) {
            if ((int)($flowRow['start_question_id'] ?? 0) === $questionId) {
                throw new RuntimeException('Remove this question as a triage set start question before deleting it.');
            }
            $flowQuestionIds = [];
            if (!empty($flowRow['question_order_json'])) {
                $decoded = json_decode((string)$flowRow['question_order_json'], true);
                if (is_array($decoded)) {
                    $flowQuestionIds = array_values(array_filter(array_map('intval', $decoded)));
                }
            }
            if (in_array($questionId, $flowQuestionIds, true)) {
                throw new RuntimeException('Remove this question from all triage sets before deleting it.');
            }
        }

        $routeRefsStmt = $pdo->prepare("SELECT COUNT(*) FROM rescue_triage_answers WHERE next_question_id = ?");
        $routeRefsStmt->execute([$questionId]);
        if ((int)$routeRefsStmt->fetchColumn() > 0) {
            throw new RuntimeException('Remove answer routes pointing to this question before deleting it.');
        }

        if (triage_builder_has_column($pdo, 'rescue_triage_questions', 'default_next_question_id')) {
            $defaultRefsStmt = $pdo->prepare("SELECT COUNT(*) FROM rescue_triage_questions WHERE default_next_question_id = ?");
            $defaultRefsStmt->execute([$questionId]);
            if ((int)$defaultRefsStmt->fetchColumn() > 0) {
                throw new RuntimeException('Remove species search routes pointing to this question before deleting it.');
            }
        }

        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM rescue_triage_answers WHERE question_id = ?")->execute([$questionId]);
        $pdo->prepare("DELETE FROM rescue_triage_questions WHERE question_id = ?")->execute([$questionId]);
        $pdo->commit();

        triage_builder_flash('success', 'Question deleted.');
        triage_builder_redirect();
    }

    if ($action === 'toggle_advice') {
        $adviceId = (int)($_POST['advice_id'] ?? 0);
        $active = !empty($_POST['active']) ? 1 : 0;
        $stmt = $pdo->prepare("
            UPDATE rescue_triage_advice
            SET active = :active, updated_at = CURRENT_TIMESTAMP
            WHERE advice_id = :advice_id
              AND (:is_admin = 1 OR (centre_id = :centre_id AND is_global = 0))
        ");
        $stmt->execute([
            ':active' => $active,
            ':advice_id' => $adviceId,
            ':is_admin' => $isAdmin ? 1 : 0,
            ':centre_id' => $centreId,
        ]);
        triage_builder_flash('success', 'Advice status updated.');
        triage_builder_redirect();
    }

    if ($action === 'update_advice') {
        $adviceId = (int)($_POST['advice_id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        $adviceText = trim((string)($_POST['advice_text'] ?? ''));
        $adviceSpeciesId = (int)($_POST['species_id'] ?? 0);
        $adviceSpeciesType = trim((string)($_POST['species_type'] ?? ''));

        if ($adviceId <= 0 || $title === '' || $adviceText === '') {
            throw new RuntimeException('Advice title and text are required.');
        }

        $adviceStmt = $pdo->prepare("
            SELECT advice_id, centre_id, is_global
            FROM rescue_triage_advice
            WHERE advice_id = ?
              AND ((centre_id = 0 AND is_global = 1) OR centre_id = ?)
            LIMIT 1
        ");
        $adviceStmt->execute([$adviceId, $centreId]);
        $advice = $adviceStmt->fetch(PDO::FETCH_ASSOC);
        if (!$advice || !triage_builder_can_manage_row($advice, $centreId, $isAdmin)) {
            throw new RuntimeException('You cannot edit this advice.');
        }

        $updateScope = triage_builder_scope_from_post($isAdmin, $centreId);
        if (!$isAdmin) {
            $updateScope = [
                'centre_id' => (int)$advice['centre_id'],
                'is_global' => (int)$advice['is_global'],
            ];
        }

        $sets = [
            'centre_id = :centre_id',
            'is_global = :is_global',
            'title = :title',
            'advice_text = :advice_text',
            'updated_at = CURRENT_TIMESTAMP',
        ];
        $params = [
            ':centre_id' => $updateScope['centre_id'],
            ':is_global' => $updateScope['is_global'],
            ':title' => $title,
            ':advice_text' => $adviceText,
            ':advice_id' => $adviceId,
        ];

        if (triage_builder_has_column($pdo, 'rescue_triage_advice', 'species_id')) {
            $sets[] = 'species_id = :species_id';
            $params[':species_id'] = $adviceSpeciesId > 0 ? $adviceSpeciesId : null;
        }
        if (triage_builder_has_column($pdo, 'rescue_triage_advice', 'species_type')) {
            $sets[] = 'species_type = :species_type';
            $params[':species_type'] = $adviceSpeciesType !== '' && $adviceSpeciesId <= 0 ? $adviceSpeciesType : null;
        }

        $stmt = $pdo->prepare("
            UPDATE rescue_triage_advice
            SET " . implode(', ', $sets) . "
            WHERE advice_id = :advice_id
        ");
        $stmt->execute($params);

        triage_builder_flash('success', 'Advice updated.');
        triage_builder_redirect();
    }

    if ($action === 'delete_advice') {
        $adviceId = (int)($_POST['advice_id'] ?? 0);
        if ($adviceId <= 0) {
            throw new RuntimeException('Advice not selected.');
        }
        $adviceStmt = $pdo->prepare("
            SELECT advice_id, centre_id, is_global, active
            FROM rescue_triage_advice
            WHERE advice_id = ?
              AND ((centre_id = 0 AND is_global = 1) OR centre_id = ?)
            LIMIT 1
        ");
        $adviceStmt->execute([$adviceId, $centreId]);
        $advice = $adviceStmt->fetch(PDO::FETCH_ASSOC);
        if (!$advice || !triage_builder_can_manage_row($advice, $centreId, $isAdmin)) {
            throw new RuntimeException('You cannot delete this advice.');
        }
        if (!empty($advice['active'])) {
            throw new RuntimeException('Deactivate the advice before deleting it.');
        }
        $routeRefsStmt = $pdo->prepare("SELECT COUNT(*) FROM rescue_triage_answers WHERE advice_id = ?");
        $routeRefsStmt->execute([$adviceId]);
        if ((int)$routeRefsStmt->fetchColumn() > 0) {
            throw new RuntimeException('Remove answer routes using this advice before deleting it.');
        }
        $pdo->prepare("DELETE FROM rescue_triage_advice WHERE advice_id = ?")->execute([$adviceId]);
        triage_builder_flash('success', 'Advice deleted.');
        triage_builder_redirect();
    }

    if ($action === 'toggle_flow') {
        $flowId = (int)($_POST['flow_id'] ?? 0);
        $active = !empty($_POST['active']) ? 1 : 0;
        $stmt = $pdo->prepare("
            UPDATE rescue_triage_flows
            SET active = :active, updated_at = CURRENT_TIMESTAMP
            WHERE flow_id = :flow_id
              AND (:is_admin = 1 OR (centre_id = :centre_id AND is_global = 0))
        ");
        $stmt->execute([
            ':active' => $active,
            ':flow_id' => $flowId,
            ':is_admin' => $isAdmin ? 1 : 0,
            ':centre_id' => $centreId,
        ]);
        triage_builder_flash('success', 'Flow status updated.');
        triage_builder_redirect();
    }

    if ($action === 'delete_flow') {
        $flowId = (int)($_POST['flow_id'] ?? 0);
        if ($flowId <= 0) {
            throw new RuntimeException('Triage set not selected.');
        }
        $flowStmt = $pdo->prepare("
            SELECT flow_id, centre_id, is_global, active
            FROM rescue_triage_flows
            WHERE flow_id = :flow_id
              AND (:is_admin = 1 OR (centre_id = :centre_id AND is_global = 0))
            LIMIT 1
        ");
        $flowStmt->execute([
            ':flow_id' => $flowId,
            ':is_admin' => $isAdmin ? 1 : 0,
            ':centre_id' => $centreId,
        ]);
        $flow = $flowStmt->fetch(PDO::FETCH_ASSOC);
        if (!$flow) {
            throw new RuntimeException('You cannot delete this triage set.');
        }
        if (!empty($flow['active'])) {
            throw new RuntimeException('Deactivate the triage set before deleting it.');
        }
        $pdo->prepare("DELETE FROM rescue_triage_flows WHERE flow_id = ?")->execute([$flowId]);
        triage_builder_flash('success', 'Triage set deleted.');
        triage_builder_redirect();
    }

    if ($action === 'toggle_answer') {
        $answerId = (int)($_POST['answer_id'] ?? 0);
        $active = !empty($_POST['active']) ? 1 : 0;
        $stmt = $pdo->prepare("
            UPDATE rescue_triage_answers a
            JOIN rescue_triage_questions q ON q.question_id = a.question_id
            SET a.active = :active, a.updated_at = CURRENT_TIMESTAMP
            WHERE a.answer_id = :answer_id
              AND (:is_admin = 1 OR (q.centre_id = :centre_id AND q.is_global = 0))
        ");
        $stmt->execute([
            ':active' => $active,
            ':answer_id' => $answerId,
            ':is_admin' => $isAdmin ? 1 : 0,
            ':centre_id' => $centreId,
        ]);
        triage_builder_flash('success', 'Answer status updated.');
        triage_builder_redirect();
    }

    if ($action === 'delete_answer') {
        $answerId = (int)($_POST['answer_id'] ?? 0);
        if ($answerId <= 0) {
            throw new RuntimeException('Answer not selected.');
        }
        $answerStmt = $pdo->prepare("
            SELECT a.answer_id, a.active, q.centre_id, q.is_global
            FROM rescue_triage_answers a
            JOIN rescue_triage_questions q ON q.question_id = a.question_id
            WHERE a.answer_id = ?
              AND ((q.centre_id = 0 AND q.is_global = 1) OR q.centre_id = ?)
            LIMIT 1
        ");
        $answerStmt->execute([$answerId, $centreId]);
        $answer = $answerStmt->fetch(PDO::FETCH_ASSOC);
        if (!$answer || !triage_builder_can_manage_row($answer, $centreId, $isAdmin)) {
            throw new RuntimeException('You cannot delete this answer.');
        }
        if (!empty($answer['active'])) {
            throw new RuntimeException('Deactivate the answer before deleting it.');
        }
        $pdo->prepare("DELETE FROM rescue_triage_answers WHERE answer_id = ?")->execute([$answerId]);
        triage_builder_flash('success', 'Answer deleted.');
        triage_builder_redirect();
    }

    throw new RuntimeException('Unknown builder action.');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    triage_builder_flash('error', $e->getMessage());
    triage_builder_redirect();
}
