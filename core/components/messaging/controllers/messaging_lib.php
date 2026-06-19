<?php

function messaging_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function messaging_schema_error(PDO $pdo): string
{
    foreach ([
        'rescue_message_threads',
        'rescue_message_participants',
        'rescue_message_entries',
    ] as $table) {
        try {
            $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
        } catch (Throwable $e) {
            return $table;
        }
    }

    return '';
}

function messaging_register_permissions(): void
{
    if (!function_exists('registerPermission')) return;
    registerPermission('messages.contact_friend_centres', 'Contact staff at approved friend centres', 'action');
    registerPermission('messages.send_group', 'Send messages to multiple staff or all staff', 'action');
}

function messaging_can(string $permission): bool
{
    return function_exists('can') && can($permission);
}

function messaging_is_app_admin(): bool
{
    return strtolower((string)($_SESSION['account_role'] ?? $GLOBALS['role'] ?? '')) === 'admin';
}

function messaging_user_id(): int
{
    return (int)($_SESSION['user_id'] ?? $_SESSION['account_id'] ?? $GLOBALS['user_id'] ?? 0);
}

function messaging_centre_id(): int
{
    return (int)($_SESSION['centre_id'] ?? $GLOBALS['centre_id'] ?? 0);
}

function messaging_account_label(array $account): string
{
    $name = trim((string)($account['first_name'] ?? '') . ' ' . (string)($account['last_name'] ?? ''));
    return $name !== '' ? $name : ((string)($account['username'] ?? '') ?: 'User #' . (int)($account['id'] ?? 0));
}

function messaging_fetch_centre_staff(PDO $pdo, int $centreId, int $excludeUserId = 0): array
{
    $stmt = $pdo->prepare("
        SELECT id, centre_id, username, first_name, last_name, email
        FROM accounts
        WHERE centre_id = :centre_id AND id <> :exclude_user_id
        ORDER BY first_name, last_name, username
    ");
    $stmt->execute([':centre_id' => $centreId, ':exclude_user_id' => $excludeUserId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function messaging_fetch_friend_staff(PDO $pdo, int $centreId): array
{
    $stmt = $pdo->prepare("
        SELECT a.id, a.centre_id, a.username, a.first_name, a.last_name, a.email, c.rescue_name
        FROM rescue_centre_friends f
        INNER JOIN rescue_centres c
          ON c.rescue_id = CASE WHEN f.centre_a_id = :centre_id THEN f.centre_b_id ELSE f.centre_a_id END
        INNER JOIN accounts a ON a.centre_id = c.rescue_id
        WHERE f.status = 'approved' AND :centre_id_membership IN (f.centre_a_id, f.centre_b_id)
        ORDER BY c.rescue_name, a.first_name, a.last_name, a.username
    ");
    $stmt->execute([':centre_id' => $centreId, ':centre_id_membership' => $centreId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function messaging_validate_recipients(PDO $pdo, int $senderId, int $centreId, string $type, array $requestedIds): array
{
    if ($type === 'all_staff') {
        if (!messaging_can('messages.send_group')) throw new RuntimeException('MSG_GROUP_DENIED');
        return messaging_fetch_centre_staff($pdo, $centreId, $senderId);
    }
    if ($type === 'platform') {
        if (!messaging_is_app_admin()) throw new RuntimeException('MSG_PLATFORM_DENIED');
        return $pdo->query("SELECT id, centre_id, username, first_name, last_name, email FROM accounts ORDER BY id")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    $ids = array_values(array_unique(array_filter(array_map('intval', $requestedIds))));
    if (!$ids) throw new RuntimeException('MSG_RECIPIENT_REQUIRED');
    if (count($ids) > 1 && !messaging_can('messages.send_group')) throw new RuntimeException('MSG_GROUP_DENIED');

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, centre_id, username, first_name, last_name, email FROM accounts WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if (count($accounts) !== count($ids)) throw new RuntimeException('MSG_RECIPIENT_INVALID');

    foreach ($accounts as $account) {
        $recipientCentre = (int)$account['centre_id'];
        if ($recipientCentre === $centreId) continue;
        if (!messaging_can('messages.contact_friend_centres')) throw new RuntimeException('MSG_EXTERNAL_DENIED');
        $friend = $pdo->prepare("
            SELECT 1 FROM rescue_centre_friends
            WHERE status = 'approved'
              AND ((centre_a_id = :sender AND centre_b_id = :recipient)
                OR (centre_a_id = :recipient AND centre_b_id = :sender))
            LIMIT 1
        ");
        $friend->execute([':sender' => $centreId, ':recipient' => $recipientCentre]);
        if (!$friend->fetchColumn()) throw new RuntimeException('MSG_EXTERNAL_DENIED');
    }
    return $accounts;
}

function messaging_create_thread(PDO $pdo, int $senderId, int $centreId, string $subject, string $body, string $type, array $recipients, string $priority, bool $repliesAllowed): int
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO rescue_message_threads
                (subject, thread_type, created_by_user_id, created_by_centre_id, replies_allowed, priority, created_at, updated_at)
            VALUES (:subject, :thread_type, :user_id, :centre_id, :replies_allowed, :priority, NOW(), NOW())
        ");
        $stmt->execute([
            ':subject' => $subject,
            ':thread_type' => $type,
            ':user_id' => $senderId,
            ':centre_id' => $centreId,
            ':replies_allowed' => $repliesAllowed ? 1 : 0,
            ':priority' => $priority,
        ]);
        $threadId = (int)$pdo->lastInsertId();
        $participant = $pdo->prepare("
            INSERT IGNORE INTO rescue_message_participants
                (thread_id, user_id, centre_id, is_sender, last_read_at, created_at)
            VALUES (:thread_id, :user_id, :centre_id, :is_sender, :last_read_at, NOW())
        ");
        $participant->execute([':thread_id' => $threadId, ':user_id' => $senderId, ':centre_id' => $centreId, ':is_sender' => 1, ':last_read_at' => date('Y-m-d H:i:s')]);
        foreach ($recipients as $recipient) {
            if ((int)$recipient['id'] === $senderId) continue;
            $participant->execute([':thread_id' => $threadId, ':user_id' => (int)$recipient['id'], ':centre_id' => (int)$recipient['centre_id'], ':is_sender' => 0, ':last_read_at' => null]);
        }
        $message = $pdo->prepare("INSERT INTO rescue_message_entries (thread_id, sender_user_id, sender_centre_id, body, created_at) VALUES (?, ?, ?, ?, NOW())");
        $message->execute([$threadId, $senderId, $centreId, $body]);
        $pdo->commit();
        return $threadId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function messaging_fetch_threads(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT t.*, p.last_read_at,
               (SELECT COUNT(*) FROM rescue_message_participants p2 WHERE p2.thread_id = t.thread_id) AS participant_count,
               (SELECT m.body FROM rescue_message_entries m WHERE m.thread_id = t.thread_id AND m.deleted_at IS NULL ORDER BY m.message_id DESC LIMIT 1) AS last_body,
               (SELECT COUNT(*) FROM rescue_message_entries m WHERE m.thread_id = t.thread_id AND m.deleted_at IS NULL AND m.sender_user_id <> p.user_id AND m.created_at > COALESCE(p.last_read_at, '1970-01-01')) AS unread_count
        FROM rescue_message_participants p
        INNER JOIN rescue_message_threads t ON t.thread_id = p.thread_id
        WHERE p.user_id = :user_id AND p.archived_at IS NULL
        ORDER BY t.updated_at DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function messaging_fetch_thread_participants(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT p.thread_id, a.id, a.username, a.first_name, a.last_name, c.rescue_name
        FROM rescue_message_participants p
        INNER JOIN rescue_message_participants current_participant
            ON current_participant.thread_id = p.thread_id
           AND current_participant.user_id = :current_user_id
        LEFT JOIN accounts a ON a.id = p.user_id
        LEFT JOIN rescue_centres c ON c.rescue_id = p.centre_id
        WHERE p.user_id <> :other_user_id
        ORDER BY p.thread_id, a.first_name, a.last_name, a.username
    ");
    $stmt->execute([':current_user_id' => $userId, ':other_user_id' => $userId]);

    $participants = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $participant) {
        $participants[(int)$participant['thread_id']][] = $participant;
    }
    return $participants;
}

function messaging_fetch_thread(PDO $pdo, int $threadId, int $userId): ?array
{
    $stmt = $pdo->prepare("
        SELECT t.*,
               (SELECT COUNT(*) FROM rescue_message_participants p2 WHERE p2.thread_id = t.thread_id) AS participant_count
        FROM rescue_message_threads t
        INNER JOIN rescue_message_participants p ON p.thread_id = t.thread_id
        WHERE t.thread_id = :thread_id AND p.user_id = :user_id LIMIT 1
    ");
    $stmt->execute([':thread_id' => $threadId, ':user_id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function messaging_fetch_messages(PDO $pdo, int $threadId): array
{
    $stmt = $pdo->prepare("
        SELECT m.*, a.first_name, a.last_name, a.username, c.rescue_name
        FROM rescue_message_entries m
        LEFT JOIN accounts a ON a.id = m.sender_user_id
        LEFT JOIN rescue_centres c ON c.rescue_id = m.sender_centre_id
        WHERE m.thread_id = :thread_id AND m.deleted_at IS NULL
        ORDER BY m.created_at, m.message_id
    ");
    $stmt->execute([':thread_id' => $threadId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function messaging_mark_read(PDO $pdo, int $threadId, int $userId): void
{
    $stmt = $pdo->prepare("UPDATE rescue_message_participants SET last_read_at = NOW() WHERE thread_id = ? AND user_id = ?");
    $stmt->execute([$threadId, $userId]);
}

function messaging_unread_count(PDO $pdo, int $userId): int
{
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM rescue_message_entries m
            INNER JOIN rescue_message_participants p ON p.thread_id = m.thread_id AND p.user_id = :participant_user_id
            WHERE m.deleted_at IS NULL AND m.sender_user_id <> :sender_user_id AND m.created_at > COALESCE(p.last_read_at, '1970-01-01')
        ");
        $stmt->execute([':participant_user_id' => $userId, ':sender_user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}
