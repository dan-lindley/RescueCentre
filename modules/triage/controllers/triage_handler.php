<?php
require_once __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/triage_lib.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$userId = triage_user_id();
$centreId = triage_centre_id();
$action = (string)($_POST['action'] ?? '');

if ($userId <= 0 || $centreId <= 0) {
    triage_flash('error', 'Triage save failed: user or centre context missing.');
    triage_redirect();
}

try {
    if ($action !== 'save_call') {
        throw new RuntimeException('Unknown triage action.');
    }

    $flowId = (int)($_POST['flow_id'] ?? 0);
    $finderName = trim((string)($_POST['finder_name'] ?? ''));
    $finderPhone = trim((string)($_POST['finder_phone'] ?? ''));
    $finderAddress = trim((string)($_POST['finder_address'] ?? ''));
    $finderPostcode = trim((string)($_POST['finder_postcode'] ?? ''));
    $animalLocation = trim((string)($_POST['animal_location'] ?? ''));
    $animalPostcode = trim((string)($_POST['animal_postcode'] ?? ''));
    $speciesId = (int)($_POST['species_id'] ?? 0);
    $speciesGuess = trim((string)($_POST['species_guess'] ?? ''));
    $presentingComplaint = trim((string)($_POST['presenting_complaint'] ?? ''));
    $callNotes = trim((string)($_POST['call_notes'] ?? ''));
    $actionType = trim((string)($_POST['action_type'] ?? ''));
    $actionNotes = trim((string)($_POST['action_notes'] ?? ''));
    $priority = trim((string)($_POST['priority'] ?? ''));
    $answersJson = trim((string)($_POST['answers_json'] ?? '[]'));
    $adviceJson = trim((string)($_POST['advice_given_json'] ?? '[]'));

    if ($flowId <= 0) {
        throw new RuntimeException('Select a triage set before saving the call.');
    }

    $flowStmt = $pdo->prepare("
        SELECT flow_id
        FROM rescue_triage_flows
        WHERE flow_id = ?
          AND active = 1
          AND ((centre_id = 0 AND is_global = 1) OR centre_id = ?)
        LIMIT 1
    ");
    $flowStmt->execute([$flowId, $centreId]);
    if (!$flowStmt->fetch(PDO::FETCH_ASSOC)) {
        throw new RuntimeException('This triage set is not available.');
    }

    if (!is_array(json_decode($answersJson, true))) {
        $answersJson = '[]';
    }
    if (!is_array(json_decode($adviceJson, true))) {
        $adviceJson = '[]';
    }

    $allowedActions = ['advice_only', 'collection', 'vet', 'disposal', 'callback', 'admit'];
    if (!in_array($actionType, $allowedActions, true)) {
        $actionType = null;
    }

    $status = 'open';
    if ($actionType === 'advice_only') {
        $status = 'advice_only';
    } elseif ($actionType === 'collection' || $actionType === 'admit') {
        $status = 'collection_needed';
    } elseif ($actionType === 'vet') {
        $status = 'vet_referral';
    } elseif ($actionType === 'disposal') {
        $status = 'disposal';
    }

    $fields = [
        'centre_id',
        'user_id',
        'flow_id',
        'finder_name',
        'finder_phone',
        'finder_address',
        'finder_postcode',
        'animal_location',
        'animal_postcode',
        'species_id',
        'species_guess',
        'presenting_complaint',
        'answers_json',
        'advice_given_json',
        'action_type',
        'action_notes',
        'call_notes',
        'priority',
        'status',
    ];
    $placeholders = array_map(static fn($field) => ':' . $field, $fields);
    $params = [
        ':centre_id' => $centreId,
        ':user_id' => $userId,
        ':flow_id' => $flowId,
        ':finder_name' => $finderName !== '' ? $finderName : null,
        ':finder_phone' => $finderPhone !== '' ? $finderPhone : null,
        ':finder_address' => $finderAddress !== '' ? $finderAddress : null,
        ':finder_postcode' => $finderPostcode !== '' ? $finderPostcode : null,
        ':animal_location' => $animalLocation !== '' ? $animalLocation : null,
        ':animal_postcode' => $animalPostcode !== '' ? $animalPostcode : null,
        ':species_id' => $speciesId > 0 ? $speciesId : null,
        ':species_guess' => $speciesGuess !== '' ? $speciesGuess : null,
        ':presenting_complaint' => $presentingComplaint !== '' ? $presentingComplaint : null,
        ':answers_json' => $answersJson,
        ':advice_given_json' => $adviceJson,
        ':action_type' => $actionType,
        ':action_notes' => $actionNotes !== '' ? $actionNotes : null,
        ':call_notes' => $callNotes !== '' ? $callNotes : null,
        ':priority' => $priority !== '' ? (int)$priority : null,
        ':status' => $status,
    ];

    $stmt = $pdo->prepare("
        INSERT INTO rescue_triage_calls
            (" . implode(', ', $fields) . ")
        VALUES
            (" . implode(', ', $placeholders) . ")
    ");
    $stmt->execute($params);

    triage_flash('success', 'Triage call saved as #' . (int)$pdo->lastInsertId() . '.');
    triage_redirect();
} catch (Throwable $e) {
    triage_flash('error', $e->getMessage());
    triage_redirect();
}
