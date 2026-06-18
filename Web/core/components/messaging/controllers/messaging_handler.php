<?php
require_once __DIR__ . '/../../../../connection.php';
require_once __DIR__ . '/../../../../getuserinfo.php';
require_once __DIR__ . '/../../../../operations/permissions.php';
require_once __DIR__ . '/../../../../operations/audit.php';
require_once __DIR__ . '/messaging_lib.php';

function messaging_redirect(array $params = []): void
{
    $query = http_build_query($params);
    header('Location: ../../../../messages.php' . ($query ? '?' . $query : ''));
    exit;
}

$userId = messaging_user_id();
$centreId = messaging_centre_id();
if ($userId <= 0 || $centreId <= 0) messaging_redirect(['error' => 'MSG_CONTEXT_MISSING']);

messaging_register_permissions();

try {
    $action = trim((string)($_POST['action'] ?? ''));
    if ($action === 'send') {
        $body = trim((string)($_POST['body'] ?? ''));
        $type = trim((string)($_POST['recipient_type'] ?? 'direct'));
        if ($body === '') throw new RuntimeException('MSG_MESSAGE_REQUIRED');
        $recipients = messaging_validate_recipients($pdo, $userId, $centreId, $type, (array)($_POST['recipient_ids'] ?? []));
        $recipients = array_values(array_filter($recipients, static fn(array $recipient): bool => (int)$recipient['id'] !== $userId));
        if (!$recipients) throw new RuntimeException('MSG_RECIPIENT_REQUIRED');
        if ($type === 'direct' && count($recipients) > 1) $type = 'group';
        $repliesAllowed = empty($_POST['disable_replies']);
        $threadId = messaging_create_thread($pdo, $userId, $centreId, 'Conversation', $body, $type, $recipients, 'normal', $repliesAllowed);
        audit_write($pdo, 'message_sent', 'messaging', null, ['thread_id' => $threadId, 'thread_type' => $type, 'recipient_count' => count($recipients)]);
        messaging_redirect(['tab' => 'inbox', 'thread_id' => $threadId, 'msg' => 'MSG_SENT']);
    }
    if ($action === 'reply') {
        $threadId = (int)($_POST['thread_id'] ?? 0);
        $body = trim((string)($_POST['body'] ?? ''));
        $thread = messaging_fetch_thread($pdo, $threadId, $userId);
        if (!$thread || $body === '') throw new RuntimeException('MSG_REPLY_INVALID');
        if (empty($thread['replies_allowed']) && !messaging_is_app_admin()) throw new RuntimeException('MSG_REPLIES_DISABLED');
        $stmt = $pdo->prepare("INSERT INTO rescue_message_entries (thread_id, sender_user_id, sender_centre_id, body, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$threadId, $userId, $centreId, $body]);
        $pdo->prepare("UPDATE rescue_message_threads SET updated_at = NOW() WHERE thread_id = ?")->execute([$threadId]);
        messaging_mark_read($pdo, $threadId, $userId);
        audit_write($pdo, 'message_reply_sent', 'messaging', null, ['thread_id' => $threadId]);
        messaging_redirect(['tab' => 'inbox', 'thread_id' => $threadId, 'msg' => 'MSG_REPLY_SENT']);
    }
    throw new RuntimeException('MSG_ACTION_UNKNOWN');
} catch (Throwable $e) {
    $key = $e->getMessage();
    messaging_redirect(['thread_id' => (int)($_POST['thread_id'] ?? 0), 'error' => str_starts_with($key, 'MSG_') ? $key : 'MSG_SEND_FAILED']);
}
