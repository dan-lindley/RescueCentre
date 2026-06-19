<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_user_id = isset($_SESSION['account_id']) ? (int)$_SESSION['account_id'] : 0;

$thread_id = (int)($_POST['thread_id'] ?? 0);
$body      = trim($_POST['body'] ?? '');
$returnTo  = $_POST['return_to'] ?? null;

if ($current_user_id <= 0) {
    header("Location: " . ($returnTo ?: "messageboard.php?tab=feed") . "&error=" . urlencode("You must be logged in."));
    exit;
}
if ($thread_id <= 0 || $body === '') {
    header("Location: " . ($returnTo ?: "messageboard.php?tab=feed") . "&error=" . urlencode("Reply cannot be empty."));
    exit;
}

$network_id = isset($_POST['network_id']) ? (int)$_POST['network_id'] : 0;

if ($network_id > 0) {
    // Network board
    $stmt = $pdo->prepare("
        SELECT id
        FROM rescue_boards
        WHERE network_id = :nid AND slug='network' AND is_enabled=1
        LIMIT 1
    ");
    $stmt->execute([':nid' => $network_id]);
    $board = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$board) {
        header("Location: " . ($returnTo ?: "network.php?group_id=" . $network_id . "&tab=message_board") . "&error=" . urlencode("Network board not found."));
        exit;
    }
} else {
    // Global board via tab
    $tabSlug = $_POST['tab'] ?? $_GET['tab'] ?? 'feed';
    $tabSlug = strtolower(trim($tabSlug));

    $stmt = $pdo->prepare("
        SELECT id
        FROM rescue_boards
        WHERE network_id IS NULL AND slug = :slug AND is_enabled = 1
        LIMIT 1
    ");
    $stmt->execute([':slug' => $tabSlug]);
    $board = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$board) {
        header("Location: messageboard.php?tab=feed&error=" . urlencode("Board not found."));
        exit;
    }
}

// Confirm starter exists in this board and not locked
$stmt = $pdo->prepare("
    SELECT id, status
    FROM rescue_posts
    WHERE id = :id AND board_id = :board_id AND parent_id IS NULL
    LIMIT 1
");
$stmt->execute([':id' => $thread_id, ':board_id' => (int)$board['id']]);
$starter = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$starter) {
    header("Location: " . ($returnTo ?: "messageboard.php?tab=feed") . "&error=" . urlencode("Thread not found."));
    exit;
}
if ($starter['status'] === 'locked') {
    header("Location: " . ($returnTo ?: "messageboard.php?tab=feed") . "&error=" . urlencode("This thread is locked."));
    exit;
}

$pdo->beginTransaction();

$stmt = $pdo->prepare("
    INSERT INTO rescue_posts (board_id, parent_id, user_id, title, body, status, last_activity_at)
    VALUES (:board_id, :parent_id, :user_id, NULL, :body, 'active', NOW())
");
$stmt->execute([
    ':board_id'  => (int)$board['id'],
    ':parent_id' => $thread_id,
    ':user_id'   => $current_user_id,
    ':body'      => $body
]);

$stmt = $pdo->prepare("UPDATE rescue_posts SET last_activity_at = NOW() WHERE id = :id");
$stmt->execute([':id' => $thread_id]);

$pdo->commit();

if ($returnTo) {
    $sep = (strpos($returnTo, '?') !== false) ? '&' : '?';
    header("Location: " . $returnTo . "&success=" . urlencode("Reply posted.") . "#reply");
    exit;
}

header("Location: messageboard.php?tab=" . urlencode($_POST['tab'] ?? 'feed') . "&thread=" . $thread_id . "&success=" . urlencode("Reply posted.") . "#reply");
exit;
