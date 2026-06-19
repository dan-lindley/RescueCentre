<?php
// controllers/friends_handler.php
// Handles all actions for the Friends system (centre-to-centre)

define('APP_LOADED', true);

include __DIR__ . '/../dashmain.php';      // $pdo + session
include __DIR__ . '/../getcentreinfo.php'; // centre context
require_once __DIR__ . '/../operations/permissions.php';

// Gate (same permission used for the page)
registerPermission('page_friends', 'Access to Friends / Centre Connections Page', 'page');
requirePermission('page_friends');

// ---------------------------
// Helpers
// ---------------------------
function rc_redirect(string $tab, string $type, string $msg): void {
    $tab = $tab ?: 'myfriends';
    $qs = http_build_query([
        'tab' => $tab,
        $type => $msg
    ]);
    header('Location: ../friends.php?' . $qs);
    exit;
}

function rc_now(): string {
    return date('Y-m-d H:i:s');
}

// ---------------------------
// Context
// ---------------------------
$currentCentreId = 0;
if (isset($centre_id) && (int)$centre_id > 0) {
    $currentCentreId = (int)$centre_id;
} elseif (isset($rescue_id) && (int)$rescue_id > 0) {
    $currentCentreId = (int)$rescue_id;
}

$userId = 0;
// Most systems store logged-in user id in session
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['id'])) {
    $userId = (int)$_SESSION['id'];
} elseif (isset($user_id)) {
    $userId = (int)$user_id;
}

$returnTab = $_REQUEST['return_tab'] ?? ($_REQUEST['tab'] ?? 'myfriends');
$action    = strtolower(trim($_REQUEST['action'] ?? ''));

if ($currentCentreId <= 0 || $userId <= 0) {
    rc_redirect($returnTab, 'error', 'Session context missing (centre/user).');
}

$allowed = ['request','approve','decline','cancel','remove','block'];
if (!in_array($action, $allowed, true)) {
    rc_redirect($returnTab, 'error', 'Invalid action.');
}

// ---------------------------
// Core DB helpers
// ---------------------------
function rc_get_friendship_by_pair(PDO $pdo, int $centre1, int $centre2): ?array {
    $a = min($centre1, $centre2);
    $b = max($centre1, $centre2);

    $stmt = $pdo->prepare("
        SELECT *
        FROM rescue_centre_friends
        WHERE centre_a_id = :a AND centre_b_id = :b
        LIMIT 1
    ");
    $stmt->execute([':a' => $a, ':b' => $b]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function rc_get_friendship_by_id(PDO $pdo, int $friendshipId): ?array {
    $stmt = $pdo->prepare("
        SELECT *
        FROM rescue_centre_friends
        WHERE friendship_id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $friendshipId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function rc_require_centre_in_pair(array $f, int $centreId): bool {
    return ((int)$f['centre_a_id'] === $centreId || (int)$f['centre_b_id'] === $centreId);
}

// ---------------------------
// Action: REQUEST (send)
// ---------------------------
if ($action === 'request') {

    $targetCentreId = (int)($_REQUEST['target_centre_id'] ?? 0);
    if ($targetCentreId <= 0) {
        rc_redirect($returnTab, 'error', 'Missing target centre.');
    }
    if ($targetCentreId === $currentCentreId) {
        rc_redirect($returnTab, 'error', 'You cannot friend your own centre.');
    }

    $existing = rc_get_friendship_by_pair($pdo, $currentCentreId, $targetCentreId);

    if ($existing) {
        $status = $existing['status'] ?? '';

        if ($status === 'blocked') {
            rc_redirect($returnTab, 'error', 'This connection is blocked.');
        }
        if ($status === 'approved') {
            rc_redirect($returnTab, 'success', 'You are already friends with this centre.');
        }
        if ($status === 'pending') {
            // If they requested you, tell user to approve in Requests tab
            if ((int)$existing['requested_by_centre_id'] !== $currentCentreId) {
                rc_redirect('requests', 'success', 'This centre has already requested you. You can approve it in Friend Requests.');
            }
            rc_redirect($returnTab, 'success', 'Friend request already sent.');
        }

        // If previously declined/cancelled, re-open as pending
        $stmt = $pdo->prepare("
            UPDATE rescue_centre_friends
            SET status = 'pending',
                requested_by_centre_id = :reqCentre,
                requested_by_user_id = :reqUser,
                requested_at = :now,
                responded_by_user_id = NULL,
                responded_at = NULL
            WHERE friendship_id = :id
        ");
        $stmt->execute([
            ':reqCentre' => $currentCentreId,
            ':reqUser'   => $userId,
            ':now'       => rc_now(),
            ':id'        => (int)$existing['friendship_id'],
        ]);

        rc_redirect($returnTab, 'success', 'Friend request sent.');
    }

    // Insert new row
    $a = min($currentCentreId, $targetCentreId);
    $b = max($currentCentreId, $targetCentreId);

    $stmt = $pdo->prepare("
        INSERT INTO rescue_centre_friends
            (centre_a_id, centre_b_id, status, requested_by_centre_id, requested_by_user_id, requested_at)
        VALUES
            (:a, :b, 'pending', :reqCentre, :reqUser, :now)
    ");
    $stmt->execute([
        ':a'         => $a,
        ':b'         => $b,
        ':reqCentre' => $currentCentreId,
        ':reqUser'   => $userId,
        ':now'       => rc_now(),
    ]);

    rc_redirect($returnTab, 'success', 'Friend request sent.');
}

// From here on we mostly operate by friendship_id
$friendshipId = (int)($_REQUEST['friendship_id'] ?? 0);
if ($friendshipId <= 0) {
    rc_redirect($returnTab, 'error', 'Missing friendship_id.');
}

$f = rc_get_friendship_by_id($pdo, $friendshipId);
if (!$f) {
    rc_redirect($returnTab, 'error', 'Friendship record not found.');
}

if (!rc_require_centre_in_pair($f, $currentCentreId)) {
    rc_redirect($returnTab, 'error', 'You do not have access to this friendship record.');
}

$status = $f['status'] ?? '';
$requestedByCentre = (int)($f['requested_by_centre_id'] ?? 0);

// ---------------------------
// Action: APPROVE (incoming pending)
// ---------------------------
if ($action === 'approve') {
    if ($status !== 'pending') {
        rc_redirect($returnTab, 'error', 'Only pending requests can be approved.');
    }
    if ($requestedByCentre === $currentCentreId) {
        rc_redirect($returnTab, 'error', 'You cannot approve your own outgoing request.');
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_centre_friends
        SET status = 'approved',
            responded_by_user_id = :uid,
            responded_at = :now
        WHERE friendship_id = :id
    ");
    $stmt->execute([
        ':uid' => $userId,
        ':now' => rc_now(),
        ':id'  => $friendshipId
    ]);

    rc_redirect('myfriends', 'success', 'Friend request approved.');
}

// ---------------------------
// Action: DECLINE (incoming pending)
// ---------------------------
if ($action === 'decline') {
    if ($status !== 'pending') {
        rc_redirect($returnTab, 'error', 'Only pending requests can be declined.');
    }
    if ($requestedByCentre === $currentCentreId) {
        rc_redirect($returnTab, 'error', 'You cannot decline your own outgoing request.');
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_centre_friends
        SET status = 'declined',
            responded_by_user_id = :uid,
            responded_at = :now
        WHERE friendship_id = :id
    ");
    $stmt->execute([
        ':uid' => $userId,
        ':now' => rc_now(),
        ':id'  => $friendshipId
    ]);

    rc_redirect($returnTab, 'success', 'Friend request declined.');
}

// ---------------------------
// Action: CANCEL (outgoing pending)
// ---------------------------
if ($action === 'cancel') {
    if ($status !== 'pending') {
        rc_redirect($returnTab, 'error', 'Only pending requests can be cancelled.');
    }
    if ($requestedByCentre !== $currentCentreId) {
        rc_redirect($returnTab, 'error', 'You can only cancel requests sent by your centre.');
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_centre_friends
        SET status = 'cancelled',
            responded_by_user_id = :uid,
            responded_at = :now
        WHERE friendship_id = :id
    ");
    $stmt->execute([
        ':uid' => $userId,
        ':now' => rc_now(),
        ':id'  => $friendshipId
    ]);

    rc_redirect($returnTab, 'success', 'Friend request cancelled.');
}

// ---------------------------
// Action: REMOVE (approved -> cancelled for now)
// ---------------------------
if ($action === 'remove') {
    if ($status !== 'approved') {
        rc_redirect($returnTab, 'error', 'Only approved friends can be removed.');
    }

    // We reuse "cancelled" as "removed" without changing enum
    $stmt = $pdo->prepare("
        UPDATE rescue_centre_friends
        SET status = 'cancelled',
            responded_by_user_id = :uid,
            responded_at = :now
        WHERE friendship_id = :id
    ");
    $stmt->execute([
        ':uid' => $userId,
        ':now' => rc_now(),
        ':id'  => $friendshipId
    ]);

    rc_redirect('myfriends', 'success', 'Friend removed.');
}

// ---------------------------
// Action: BLOCK (any state -> blocked)
// ---------------------------
if ($action === 'block') {
    if ($status === 'blocked') {
        rc_redirect($returnTab, 'success', 'This centre is already blocked.');
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_centre_friends
        SET status = 'blocked',
            responded_by_user_id = :uid,
            responded_at = :now
        WHERE friendship_id = :id
    ");
    $stmt->execute([
        ':uid' => $userId,
        ':now' => rc_now(),
        ':id'  => $friendshipId
    ]);

    rc_redirect($returnTab, 'success', 'Centre blocked.');
}

// Fallback
rc_redirect($returnTab, 'error', 'Unhandled action.');
