<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_user_id = isset($_SESSION['account_id']) ? (int)$_SESSION['account_id'] : 0;

$title = trim($_POST['title'] ?? '');
$body  = trim($_POST['body'] ?? '');
$tags  = trim($_POST['tags'] ?? '');
$returnTo = $_POST['return_to'] ?? null;

if ($current_user_id <= 0) {
    header("Location: " . ($returnTo ?: "messageboard.php?tab=feed") . "&error=" . urlencode("You must be logged in."));
    exit;
}

if ($title === '' || mb_strlen($title) > 200) {
    header("Location: " . ($returnTo ?: "messageboard.php?tab=feed") . "&error=" . urlencode("Please enter a title (max 200 chars)."));
    exit;
}
if ($body === '') {
    header("Location: " . ($returnTo ?: "messageboard.php?tab=feed") . "&error=" . urlencode("Please enter a message."));
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
    // Global board via tab slug
    $tabSlug = $_POST['tab'] ?? $_GET['tab'] ?? 'feed';
    $tabSlug = strtolower(trim($tabSlug));

    if ($tabSlug === 'feed') {
        header("Location: messageboard.php?tab=feed&error=" . urlencode("You can’t create a thread in Feed."));
        exit;
    }

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

// Normalise tags (max 8)
$tagList = [];
if ($tags !== '') {
    $raw = preg_split('/[,#]+/', $tags);
    foreach ($raw as $t) {
        $t = trim($t);
        if ($t === '') continue;
        $t = mb_strtolower($t);
        $t = preg_replace('/[^a-z0-9\s\-]/', '', $t);
        $t = preg_replace('/\s+/', '-', trim($t));
        $t = preg_replace('/\-+/', '-', $t);
        if ($t === '') continue;
        $tagList[$t] = $t;
        if (count($tagList) >= 8) break;
    }
    $tagList = array_values($tagList);
}

$pdo->beginTransaction();

$stmt = $pdo->prepare("
    INSERT INTO rescue_posts (board_id, parent_id, user_id, title, body, status, last_activity_at)
    VALUES (:board_id, NULL, :user_id, :title, :body, 'active', NOW())
");
$stmt->execute([
    ':board_id' => (int)$board['id'],
    ':user_id'  => $current_user_id,
    ':title'    => $title,
    ':body'     => $body
]);

$threadId = (int)$pdo->lastInsertId();

// Tags
if (!empty($tagList)) {
    $insTag = $pdo->prepare("
        INSERT INTO rescue_tags (slug, label)
        VALUES (:slug, :label)
        ON DUPLICATE KEY UPDATE label = VALUES(label)
    ");
    $getTag = $pdo->prepare("SELECT id FROM rescue_tags WHERE slug = :slug LIMIT 1");
    $insJoin = $pdo->prepare("INSERT IGNORE INTO rescue_post_tags (post_id, tag_id) VALUES (:post_id, :tag_id)");

    foreach ($tagList as $slug) {
        $label = ucwords(str_replace('-', ' ', $slug));
        $insTag->execute([':slug' => $slug, ':label' => $label]);
        $getTag->execute([':slug' => $slug]);
        $tagRow = $getTag->fetch(PDO::FETCH_ASSOC);
        if ($tagRow) {
            $insJoin->execute([':post_id' => $threadId, ':tag_id' => (int)$tagRow['id']]);
        }
    }
}

$pdo->commit();

// Redirect
if ($returnTo) {
    // If return_to is network list, redirect into the new thread
    $sep = (strpos($returnTo, '?') !== false) ? '&' : '?';
    header("Location: " . $returnTo . $sep . "thread=" . $threadId . "&success=" . urlencode("Thread created."));
    exit;
}

header("Location: messageboard.php?tab=" . urlencode($_POST['tab'] ?? 'feed') . "&thread=" . $threadId . "&success=" . urlencode("Thread created."));
exit;
