<?php
// /controllers/support_controller.php
// Controller for global public support tickets (rescue_tickets)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../connection.php'; // should define $pdo
// If you normally load user/centre globals elsewhere, include them here:
require_once __DIR__ . '/../getuserinfo.php'; // if this is where $GLOBALS['user_id'], role, etc. are set (adjust if needed)

/* -----------------------------
   Helpers
------------------------------ */
function redirect_support(string $msg, string $anchor = ''): void {
    $url = '/support.php?msg=' . urlencode($msg);
    if ($anchor) $url .= '#' . ltrim($anchor, '#');
    header('Location: ' . $url);
    exit;
}

function clean_text($v): string {
    return trim((string)$v);
}

function looks_like_personal_data(string $text): bool {
    if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text)) return true;
    if (preg_match('/\d{8,}/', $text)) return true;
    return false;
}

function decode_thread($json): array {
    if (!$json) return [];
    $arr = json_decode($json, true);
    return is_array($arr) ? $arr : [];
}

function support_ticket_email_recipient(PDO $pdo, int $ticketId): ?array {
    $stmt = $pdo->prepare("
        SELECT
            t.id,
            t.subject,
            t.centre_name,
            c.email AS centre_email,
            owner.email AS owner_email,
            centre_account.email AS account_email
        FROM rescue_tickets t
        LEFT JOIN rescue_centres c
            ON c.rescue_id = t.centre_id
        LEFT JOIN accounts owner
            ON owner.id = c.owner_id
        LEFT JOIN accounts centre_account
            ON centre_account.centre_id = t.centre_id
           AND centre_account.email <> ''
           AND centre_account.approved = 1
        WHERE t.id = ?
        ORDER BY
            CASE
                WHEN centre_account.role = 'Admin' THEN 0
                WHEN centre_account.rescue_role = 'Admin' THEN 1
                ELSE 2
            END,
            centre_account.id ASC
        LIMIT 1
    ");
    $stmt->execute([$ticketId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    foreach (['centre_email', 'owner_email', 'account_email'] as $field) {
        $email = trim((string)($row[$field] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $row['email'] = $email;
            return $row;
        }
    }

    error_log('Support ticket email not sent: no valid recipient for ticket #' . $ticketId);
    return null;
}

function support_send_ticket_reply_email(PDO $pdo, int $ticketId, string $response): bool {
    $ticket = support_ticket_email_recipient($pdo, $ticketId);
    if (!$ticket || trim($response) === '') {
        return false;
    }

    $phpmailerDir = realpath(__DIR__ . '/../lib/phpmailer');
    if (!$phpmailerDir) {
        error_log('Support ticket email not sent: PHPMailer folder not found for ticket #' . $ticketId);
        return false;
    }

    foreach (['Exception.php', 'PHPMailer.php', 'SMTP.php'] as $file) {
        $path = $phpmailerDir . '/' . $file;
        if (!is_file($path)) {
            error_log('Support ticket email not sent: missing PHPMailer file ' . $file . ' for ticket #' . $ticketId);
            return false;
        }
        require_once $path;
    }

    try {
        $ticketIdDisplay = '#' . (int)$ticket['id'];
        $subject = 'Your support ticket ' . $ticketIdDisplay . ' has been responded to';
        $centreName = trim((string)($ticket['centre_name'] ?? ''));
        $ticketSubject = trim((string)($ticket['subject'] ?? 'Support ticket'));

        $body = "Hello" . ($centreName !== '' ? " {$centreName}" : "") . ",\n\n"
            . "Your support ticket {$ticketIdDisplay} has been responded to.\n\n"
            . "Ticket: {$ticketSubject}\n\n"
            . "Response:\n{$response}\n\n"
            . "You can view the ticket by logging in to your Rescue Centre account.\n\n"
            . "Kind regards,\nRescue Centre Support";

        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'myrescuecentre.com');
        $hostClean = preg_replace('/[^a-z0-9\.\-]/i', '', (string)$host);
        $hostClean = preg_replace('/^www\./i', '', (string)$hostClean);
        $fromEmail = 'no-reply@' . ($hostClean ?: 'myrescuecentre.com');

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isMail();
        $mail->setFrom($fromEmail, 'Rescue Centre Support');
        $mail->addAddress((string)$ticket['email']);
        $mail->addBCC('support@myrescuecentre.com');
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $body;
        $mail->send();
        return true;
    } catch (Throwable $e) {
        error_log('Support ticket email not sent for ticket #' . $ticketId . ': ' . $e->getMessage());
        return false;
    }
}

/**
 * Admin detection:
 * Adapt this to your app’s truth source.
 * This is intentionally strict: if we can’t prove admin, deny.
 */
function is_admin_user(): bool {
    // Common patterns:
    if (!empty($GLOBALS['is_admin'])) return true;
    if (!empty($_SESSION['is_admin'])) return true;

    $role = $_SESSION['role'] ?? ($GLOBALS['role'] ?? null);
    if (is_string($role) && in_array(strtolower($role), ['admin','superadmin','owner'], true)) return true;

    return false;
}

/* -----------------------------
   Entry
------------------------------ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_support('error');
}

$action = $_POST['action'] ?? '';
if ($action === '') {
    redirect_support('error');
}

/* -----------------------------
   Context (trusted from session/globals, not POST)
------------------------------ */
$centre_id   = (int)($GLOBALS['centre_id'] ?? ($_SESSION['centre_id'] ?? 0));
$centre_name = (string)($GLOBALS['rescue_name'] ?? ($_SESSION['rescue_name'] ?? ''));

$user_id = (int)($GLOBALS['user_id'] ?? ($_SESSION['user_id'] ?? 0)); // for admin log entries if needed

/* -----------------------------
   Actions
------------------------------ */

// CREATE (centre-side) — create once, no edits here
if ($action === 'create_ticket') {

    $subject     = clean_text($_POST['subject'] ?? '');
    $description = clean_text($_POST['description'] ?? '');
    $priority    = clean_text($_POST['priority'] ?? 'low');

    $allowed_priority = ['low','med','high'];
    if (!in_array($priority, $allowed_priority, true)) $priority = 'low';

    if (!$centre_id || $centre_name === '' || $subject === '' || $description === '') {
        redirect_support('missing', 'createTicket');
    }

    if (looks_like_personal_data($subject . "\n" . $description)) {
        redirect_support('personal', 'createTicket');
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO rescue_tickets
                (centre_id, centre_name, created_at, subject, description, priority, progress, admin_thread, last_activity_at, updated_by_admin_id, is_hidden, duplicate_of)
            VALUES
                (:centre_id, :centre_name, NOW(), :subject, :description, :priority, 'not_started', NULL, NOW(), NULL, 0, NULL)
        ");
        $stmt->execute([
            ':centre_id'   => $centre_id,
            ':centre_name' => $centre_name,
            ':subject'     => $subject,
            ':description' => $description,
            ':priority'    => $priority,
        ]);

        redirect_support('added', 'createTicket');

    } catch (Throwable $e) {
        redirect_support('error', 'createTicket');
    }
}


// ADMIN UPDATE (priority/progress + append admin message)
if ($action === 'admin_update_ticket') {

    if (!is_admin_user()) {
        redirect_support('forbidden');
    }

    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    if ($ticket_id <= 0) redirect_support('error');

    $new_priority = clean_text($_POST['priority'] ?? '');
    $new_progress = clean_text($_POST['progress'] ?? '');
    $admin_msg    = clean_text($_POST['admin_msg'] ?? '');

    $allowed_priority = ['low','med','high'];
    $allowed_progress = ['not_started','in_dev','completed'];

    if ($new_priority !== '' && !in_array($new_priority, $allowed_priority, true)) $new_priority = '';
    if ($new_progress !== '' && !in_array($new_progress, $allowed_progress, true)) $new_progress = '';

    // Load current row (for thread + optional from/to status entries)
    $stmt = $pdo->prepare("SELECT priority, progress, admin_thread FROM rescue_tickets WHERE id = ? LIMIT 1");
    $stmt->execute([$ticket_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) redirect_support('error');

    $thread = decode_thread($row['admin_thread'] ?? null);
    $now = date('Y-m-d H:i:s');

    // Append message entry if provided
    if ($admin_msg !== '') {
        $thread[] = [
            'at' => $now,
            'admin_id' => $user_id ?: null,
            'type' => 'comment',
            'msg' => $admin_msg,
        ];
    }

    // Append status change entry if progress changed
    if ($new_progress !== '' && $new_progress !== ($row['progress'] ?? '')) {
        $thread[] = [
            'at' => $now,
            'admin_id' => $user_id ?: null,
            'type' => 'status',
            'from' => $row['progress'] ?? null,
            'to' => $new_progress,
            'msg' => 'Status updated',
        ];
    }

    $updateParts = [];
    $bind = [];

    if ($new_priority !== '') {
        $updateParts[] = "priority = :priority";
        $bind[':priority'] = $new_priority;
    }
    if ($new_progress !== '') {
        $updateParts[] = "progress = :progress";
        $bind[':progress'] = $new_progress;
    }

    // Always update thread + activity if something changed / message posted
    $updateParts[] = "admin_thread = :admin_thread";
    $bind[':admin_thread'] = json_encode($thread, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $updateParts[] = "last_activity_at = NOW()";
    $updateParts[] = "updated_by_admin_id = :admin_id";
    $bind[':admin_id'] = $user_id ?: null;

    $bind[':id'] = $ticket_id;

    try {
        $sql = "UPDATE rescue_tickets SET " . implode(", ", $updateParts) . " WHERE id = :id LIMIT 1";
        $u = $pdo->prepare($sql);
        $u->execute($bind);

        if ($admin_msg !== '') {
            support_send_ticket_reply_email($pdo, $ticket_id, $admin_msg);
        }

        redirect_support('updated');

    } catch (Throwable $e) {
        redirect_support('error');
    }
}

// ADMIN EDIT (edit everything except author) + append admin comment
if ($action === 'admin_edit_ticket') {

    // NOTE: you said admin gate not needed; leaving your existing gates untouched.
    // If you DO want to keep it consistent with the rest of this controller, uncomment:
    // if (!is_admin_user()) { redirect_support('forbidden'); }

    $ticket_id   = (int)($_POST['ticket_id'] ?? 0);
    $subject     = clean_text($_POST['subject'] ?? '');
    $description = clean_text($_POST['description'] ?? '');
    $priority    = clean_text($_POST['priority'] ?? 'low');
    $progress    = clean_text($_POST['progress'] ?? 'not_started');
    $is_hidden   = (int)($_POST['is_hidden'] ?? 0);
    $admin_msg   = clean_text($_POST['admin_msg'] ?? '');

    $allowed_priority = ['low','med','high'];
    if (!in_array($priority, $allowed_priority, true)) $priority = 'low';

    $allowed_progress = ['not_started','in_dev','completed'];
    if (!in_array($progress, $allowed_progress, true)) $progress = 'not_started';

    $is_hidden = ($is_hidden === 1) ? 1 : 0;

    // Redirect back to admin page (or wherever posted from)
    $back = $_SERVER['HTTP_REFERER'] ?? '/admin/support.php';
    $sep  = (strpos($back, '?') !== false) ? '&' : '?';

    if ($ticket_id <= 0 || $subject === '' || $description === '') {
        header('Location: ' . $back . $sep . 'msg=missing');
        exit;
    }

    // Load current row to compare progress + get existing thread
    $stmt = $pdo->prepare("SELECT progress, admin_thread FROM rescue_tickets WHERE id = ? LIMIT 1");
    $stmt->execute([$ticket_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        header('Location: ' . $back . $sep . 'msg=error');
        exit;
    }

    $thread = decode_thread($row['admin_thread'] ?? null);
    $now = date('Y-m-d H:i:s');

    // Append admin comment if provided
    if ($admin_msg !== '') {
        $thread[] = [
            'at' => $now,
            'admin_id' => $user_id ?: null,
            'type' => 'comment',
            'msg' => $admin_msg,
        ];
    }

    // Append status change entry if progress changed
    if ($progress !== ($row['progress'] ?? '')) {
        $thread[] = [
            'at' => $now,
            'admin_id' => $user_id ?: null,
            'type' => 'status',
            'from' => $row['progress'] ?? null,
            'to' => $progress,
            'msg' => 'Status updated',
        ];
    }

    try {
        $u = $pdo->prepare("
            UPDATE rescue_tickets
            SET
                subject = :subject,
                description = :description,
                priority = :priority,
                progress = :progress,
                is_hidden = :is_hidden,
                admin_thread = :admin_thread,
                last_activity_at = NOW(),
                updated_by_admin_id = :admin_id
            WHERE id = :id
            LIMIT 1
        ");
        $u->execute([
            ':subject'      => $subject,
            ':description'  => $description,
            ':priority'     => $priority,
            ':progress'     => $progress,
            ':is_hidden'    => $is_hidden,
            ':admin_thread' => json_encode($thread, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':admin_id'     => $user_id ?: null,
            ':id'           => $ticket_id,
        ]);

        if ($admin_msg !== '') {
            support_send_ticket_reply_email($pdo, $ticket_id, $admin_msg);
        }

        header('Location: ' . $back . $sep . 'msg=updated');
        exit;

    } catch (Throwable $e) {
        header('Location: ' . $back . $sep . 'msg=error');
        exit;
    }
}

// ADMIN HIDE / UNHIDE
if ($action === 'admin_hide_ticket' || $action === 'admin_unhide_ticket') {
    }

    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    if ($ticket_id <= 0) redirect_support('error');

    $hide = ($action === 'admin_hide_ticket') ? 1 : 0;

    try {
        $stmt = $pdo->prepare("UPDATE rescue_tickets SET is_hidden = ?, last_activity_at = NOW(), updated_by_admin_id = ? WHERE id = ? LIMIT 1");
        $stmt->execute([$hide, ($user_id ?: null), $ticket_id]);

        redirect_support('updated');

    } catch (Throwable $e) {
        redirect_support('error');
}



// Unknown action
redirect_support('error');
