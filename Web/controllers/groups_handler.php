<?php
// controllers/groups_handler.php
// Handles all actions for Networks (tables: rescue_groups, rescue_group_members)

define('APP_LOADED', true);

include __DIR__ . '/../dashmain.php';       // $pdo + session
include __DIR__ . '/../getcentreinfo.php';  // centre context
require_once __DIR__ . '/../operations/permissions.php';

// Gate (same permission as groups.php wrapper)
registerPermission('page_groups', $lang['NET_PERMISSION_ACCESS'] ?? 'Access to Networks Page', 'page');
requirePermission('page_groups');

// ---------------------------
// Helpers
// ---------------------------
function ng_now(): string {
    return date('Y-m-d H:i:s');
}

function ng_t(string $key, string $fallback): string {
    global $lang;
    return $lang[$key] ?? $fallback;
}

function ng_redirect(string $tab, string $type, string $msg): void {
    $requestedReturnTab = $_REQUEST['return_tab'] ?? '';
    if ($requestedReturnTab === 'settings') {
        $gid = 0;
        if (isset($_POST['network_id'])) $gid = (int)$_POST['network_id'];
        if ($gid <= 0 && isset($_POST['group_id'])) $gid = (int)$_POST['group_id'];
        if ($gid <= 0 && isset($_GET['network_id'])) $gid = (int)$_GET['network_id'];
        if ($gid <= 0 && isset($_GET['group_id'])) $gid = (int)$_GET['group_id'];

        if ($gid > 0) {
            ng_redirect_network($gid, 'settings', $type, $msg);
        }
    }

    // map internal tab keys to groups.php routing
    $tab = $tab ?: 'mynetworks';

    $map = [
        'mygroups'    => 'mynetworks',
        'mynetworks'  => 'mynetworks',
        'requests'    => 'requests',
        'find'        => 'find',
        'view'        => 'view',
    ];
    if (isset($map[$tab])) $tab = $map[$tab];

    $params = ['tab' => $tab, $type => $msg];

    // If returning to view, preserve group_id when provided (POST forms include group_id)
    if ($tab === 'view') {
        $gid = 0;
        if (isset($_POST['group_id'])) $gid = (int)$_POST['group_id'];
        if (isset($_GET['group_id']))  $gid = (int)$_GET['group_id'];
        if ($gid > 0) $params['group_id'] = $gid;
    }

    $qs = http_build_query($params);
    header('Location: ../groups.php?' . $qs);
    exit;
}

function ng_redirect_network(int $networkId, string $tab, string $type, string $msg): void {
    $params = [
        'network_id' => $networkId,
        'tab' => $tab ?: 'patients',
        $type => $msg,
    ];

    header('Location: ../viewnetwork.php?' . http_build_query($params));
    exit;
}

function ng_centre_id(): int {
    global $centre_id, $rescue_id;
    if (isset($centre_id) && (int)$centre_id > 0) return (int)$centre_id;
    if (isset($rescue_id) && (int)$rescue_id > 0) return (int)$rescue_id;
    return 0;
}

function ng_user_id(): int {
    global $user_id;
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['account_id'])) return (int)$_SESSION['account_id'];
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['id'])) return (int)$_SESSION['id'];
    if (isset($user_id) && (int)$user_id > 0) return (int)$user_id;
    return 0;
}

// ---------------------------
// Context
// ---------------------------
$currentCentreId = ng_centre_id();
$userId = ng_user_id();

$returnTab = $_REQUEST['return_tab'] ?? ($_REQUEST['tab'] ?? 'mynetworks');
$action    = strtolower(trim($_REQUEST['action'] ?? ''));

if ($currentCentreId <= 0 || $userId <= 0) {
    ng_redirect($returnTab, 'error', ng_t('NET_SESSION_CONTEXT_MISSING', 'Session context missing (centre/user).'));
}

$allowedActions = [
    'create_network',
    'request_to_join',
    'cancel_join_request',
    'accept_invite',
    'decline_invite',
    'approve_join_request',
    'decline_join_request',
    'invite_centre',
    'remove_member',
    'set_member_role',
    'leave_network',
    'unshare_patient',
];

if (!in_array($action, $allowedActions, true)) {
    ng_redirect($returnTab, 'error', ng_t('SETTINGS_INVALID_REQUEST', 'Invalid action.'));
}

// ---------------------------
// DB helpers
// ---------------------------
function ng_get_membership(PDO $pdo, int $group_member_id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM rescue_group_members WHERE group_member_id = :id LIMIT 1");
    $stmt->execute([':id' => $group_member_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function ng_get_membership_by_pair(PDO $pdo, int $group_id, int $centre_id): ?array {
    $stmt = $pdo->prepare("
        SELECT *
        FROM rescue_group_members
        WHERE group_id = :gid AND centre_id = :cid
        LIMIT 1
    ");
    $stmt->execute([':gid' => $group_id, ':cid' => $centre_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function ng_is_admin(PDO $pdo, int $group_id, int $centre_id): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM rescue_group_members
        WHERE group_id = :gid
          AND centre_id = :cid
          AND status = 'active'
          AND role = 'admin'
    ");
    $stmt->execute([':gid' => $group_id, ':cid' => $centre_id]);
    return ((int)$stmt->fetchColumn() > 0);
}

function ng_group_visibility(PDO $pdo, int $group_id): ?string {
    $stmt = $pdo->prepare("SELECT visibility FROM rescue_groups WHERE group_id = :gid LIMIT 1");
    $stmt->execute([':gid' => $group_id]);
    $v = $stmt->fetchColumn();
    return $v !== false ? (string)$v : null;
}

// ---------------------------
// ACTION: create_network
// ---------------------------
if ($action === 'create_network') {
    $name = trim((string)($_POST['network_name'] ?? ''));
    $desc = trim((string)($_POST['network_description'] ?? ''));
    $vis  = trim((string)($_POST['visibility'] ?? 'invite_only')); // defensive default

    if ($name === '') ng_redirect('find', 'error', ng_t('NET_NAME_REQUIRED', 'Network name is required.'));
    if (mb_strlen($name) > 150) ng_redirect('find', 'error', ng_t('NET_NAME_TOO_LONG', 'Network name is too long.'));
    if (!in_array($vis, ['invite_only', 'request_to_join'], true)) ng_redirect('find', 'error', ng_t('NET_INVALID_JOIN_MODE', 'Invalid join mode.'));

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO rescue_groups
                (name, description, created_by_centre_id, created_by_user_id, visibility, created_at)
            VALUES
                (:name, :desc, :ccid, :cuid, :vis, :now)
        ");
        $stmt->execute([
            ':name' => $name,
            ':desc' => ($desc === '' ? null : $desc),
            ':ccid' => $currentCentreId,
            ':cuid' => $userId,
            ':vis'  => $vis,
            ':now'  => ng_now(),
        ]);

        $groupId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO rescue_group_members
                (group_id, centre_id, role, status, approved_by_user_id, created_at, updated_at)
            VALUES
                (:gid, :cid, 'admin', 'active', :uid, :now, :now)
        ");
        $stmt->execute([
            ':gid' => $groupId,
            ':cid' => $currentCentreId,
            ':uid' => $userId,
            ':now' => ng_now(),
        ]);

        $pdo->commit();
        ng_redirect('mynetworks', 'success', ng_t('NET_CREATED', 'Network created.'));
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        ng_redirect('find', 'error', ng_t('NET_CREATE_FAILED', 'Failed to create network: ') . $e->getMessage());
    }
}

// ---------------------------
// ACTION: request_to_join
// ---------------------------
if ($action === 'request_to_join') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    if ($groupId <= 0) ng_redirect('find', 'error', ng_t('NET_MISSING_ID', 'Missing network id.'));

    $vis = ng_group_visibility($pdo, $groupId);
    if ($vis !== 'request_to_join') {
        ng_redirect('find', 'error', ng_t('NET_JOIN_REQUESTS_NOT_ACCEPTED', 'This network does not accept join requests.'));
    }

    $existing = ng_get_membership_by_pair($pdo, $groupId, $currentCentreId);

    if ($existing) {
        $status = (string)($existing['status'] ?? '');

        if ($status === 'active')  ng_redirect('find', 'success', ng_t('NET_ALREADY_MEMBER', 'You are already a member of this network.'));
        if ($status === 'pending') ng_redirect('find', 'success', ng_t('NET_JOIN_ALREADY_PENDING', 'Your join request is already pending.'));
        if ($status === 'invited') ng_redirect('requests', 'success', ng_t('NET_INVITED_ACCEPT_REQUESTS', 'You have been invited. Accept the invite in Network Requests.'));
        if ($status === 'removed') ng_redirect('find', 'error', ng_t('NET_REMOVED_FROM_NETWORK_MSG', 'You have been removed from this network.'));

        if (in_array($status, ['declined','left'], true)) {
            $stmt = $pdo->prepare("
                UPDATE rescue_group_members
                SET status = 'pending',
                    requested_by_user_id = :uid,
                    approved_by_user_id = NULL,
                    updated_at = :now
                WHERE group_member_id = :id
            ");
            $stmt->execute([
                ':uid' => $userId,
                ':now' => ng_now(),
                ':id'  => (int)$existing['group_member_id'],
            ]);
            ng_redirect('find', 'success', ng_t('NET_JOIN_REQUEST_SENT', 'Join request sent.'));
        }

        ng_redirect('find', 'error', sprintf(ng_t('NET_JOIN_REQUEST_STATUS_ERROR', 'Unable to request to join (current status: %s).'), $status));
    }

    $stmt = $pdo->prepare("
        INSERT INTO rescue_group_members
            (group_id, centre_id, role, status, requested_by_user_id, created_at, updated_at)
        VALUES
            (:gid, :cid, 'member', 'pending', :uid, :now, :now)
    ");
    $stmt->execute([
        ':gid' => $groupId,
        ':cid' => $currentCentreId,
        ':uid' => $userId,
        ':now' => ng_now(),
    ]);

    ng_redirect('find', 'success', ng_t('NET_JOIN_REQUEST_SENT', 'Join request sent.'));
}

// ---------------------------
// ACTION: cancel_join_request (my centre)
// ---------------------------
if ($action === 'cancel_join_request') {
    $memberId = (int)($_POST['group_member_id'] ?? 0);
    if ($memberId <= 0) ng_redirect('requests', 'error', ng_t('NET_MISSING_REQUEST_ID', 'Missing request id.'));

    $m = ng_get_membership($pdo, $memberId);
    if (!$m) ng_redirect('requests', 'error', ng_t('NET_MEMBERSHIP_NOT_FOUND', 'Membership record not found.'));

    if ((int)$m['centre_id'] !== $currentCentreId) ng_redirect('requests', 'error', ng_t('NET_REQUEST_ACCESS_DENIED', 'You do not have access to this request.'));
    if ((string)$m['status'] !== 'pending') ng_redirect('requests', 'error', ng_t('NET_ONLY_PENDING_CANCEL', 'Only pending join requests can be cancelled.'));

    $stmt = $pdo->prepare("
        UPDATE rescue_group_members
        SET status = 'left',
            updated_at = :now
        WHERE group_member_id = :id
    ");
    $stmt->execute([':now' => ng_now(), ':id' => $memberId]);

    ng_redirect('requests', 'success', ng_t('NET_JOIN_REQUEST_CANCELLED', 'Join request cancelled.'));
}

// ---------------------------
// ACTION: accept_invite (auto-join)
// ---------------------------
if ($action === 'accept_invite') {
    $memberId = (int)($_POST['group_member_id'] ?? 0);
    if ($memberId <= 0) ng_redirect('requests', 'error', ng_t('NET_MISSING_INVITE_ID', 'Missing invite id.'));

    $m = ng_get_membership($pdo, $memberId);
    if (!$m) ng_redirect('requests', 'error', ng_t('NET_INVITE_NOT_FOUND', 'Invite record not found.'));

    if ((int)$m['centre_id'] !== $currentCentreId) ng_redirect('requests', 'error', ng_t('NET_INVITE_ACCESS_DENIED', 'You do not have access to this invite.'));
    if ((string)$m['status'] !== 'invited') ng_redirect('requests', 'error', ng_t('NET_ONLY_INVITES_ACCEPTED', 'Only invites can be accepted.'));

    $stmt = $pdo->prepare("
        UPDATE rescue_group_members
        SET status = 'active',
            approved_by_user_id = :uid,
            updated_at = :now
        WHERE group_member_id = :id
    ");
    $stmt->execute([
        ':uid' => $userId,
        ':now' => ng_now(),
        ':id'  => $memberId
    ]);

    ng_redirect('mynetworks', 'success', ng_t('NET_JOINED_NETWORK', 'You have joined the network.'));
}

// ---------------------------
// ACTION: decline_invite
// ---------------------------
if ($action === 'decline_invite') {
    $memberId = (int)($_POST['group_member_id'] ?? 0);
    if ($memberId <= 0) ng_redirect('requests', 'error', ng_t('NET_MISSING_INVITE_ID', 'Missing invite id.'));

    $m = ng_get_membership($pdo, $memberId);
    if (!$m) ng_redirect('requests', 'error', ng_t('NET_INVITE_NOT_FOUND', 'Invite record not found.'));

    if ((int)$m['centre_id'] !== $currentCentreId) ng_redirect('requests', 'error', ng_t('NET_INVITE_ACCESS_DENIED', 'You do not have access to this invite.'));
    if ((string)$m['status'] !== 'invited') ng_redirect('requests', 'error', ng_t('NET_ONLY_INVITES_DECLINED', 'Only invites can be declined.'));

    $stmt = $pdo->prepare("
        UPDATE rescue_group_members
        SET status = 'declined',
            updated_at = :now
        WHERE group_member_id = :id
    ");
    $stmt->execute([
        ':now' => ng_now(),
        ':id'  => $memberId
    ]);

    ng_redirect('requests', 'success', ng_t('NET_INVITATION_DECLINED', 'Invitation declined.'));
}

// ---------------------------
// ACTION: approve_join_request (admin)
// ---------------------------
if ($action === 'approve_join_request') {
    $memberId = (int)($_POST['group_member_id'] ?? 0);
    if ($memberId <= 0) ng_redirect('requests', 'error', ng_t('NET_MISSING_REQUEST_ID', 'Missing request id.'));

    $m = ng_get_membership($pdo, $memberId);
    if (!$m) ng_redirect('requests', 'error', ng_t('NET_REQUEST_NOT_FOUND', 'Request record not found.'));

    $groupId = (int)$m['group_id'];

    if ((string)$m['status'] !== 'pending') ng_redirect('requests', 'error', ng_t('NET_ONLY_PENDING_APPROVED', 'Only pending requests can be approved.'));
    if (!ng_is_admin($pdo, $groupId, $currentCentreId)) ng_redirect('requests', 'error', ng_t('NET_MUST_ADMIN_APPROVE', 'You must be a network admin to approve requests.'));

    $stmt = $pdo->prepare("
        UPDATE rescue_group_members
        SET status = 'active',
            approved_by_user_id = :uid,
            updated_at = :now
        WHERE group_member_id = :id
    ");
    $stmt->execute([
        ':uid' => $userId,
        ':now' => ng_now(),
        ':id'  => $memberId
    ]);

    ng_redirect('view', 'success', ng_t('NET_JOIN_REQUEST_APPROVED', 'Join request approved.'));
}

// ---------------------------
// ACTION: decline_join_request (admin)
// ---------------------------
if ($action === 'decline_join_request') {
    $memberId = (int)($_POST['group_member_id'] ?? 0);
    if ($memberId <= 0) ng_redirect('requests', 'error', ng_t('NET_MISSING_REQUEST_ID', 'Missing request id.'));

    $m = ng_get_membership($pdo, $memberId);
    if (!$m) ng_redirect('requests', 'error', ng_t('NET_REQUEST_NOT_FOUND', 'Request record not found.'));

    $groupId = (int)$m['group_id'];

    if ((string)$m['status'] !== 'pending') ng_redirect('requests', 'error', ng_t('NET_ONLY_PENDING_DECLINED', 'Only pending requests can be declined.'));
    if (!ng_is_admin($pdo, $groupId, $currentCentreId)) ng_redirect('requests', 'error', ng_t('NET_MUST_ADMIN_DECLINE', 'You must be a network admin to decline requests.'));

    $stmt = $pdo->prepare("
        UPDATE rescue_group_members
        SET status = 'declined',
            approved_by_user_id = :uid,
            updated_at = :now
        WHERE group_member_id = :id
    ");
    $stmt->execute([
        ':uid' => $userId,
        ':now' => ng_now(),
        ':id'  => $memberId
    ]);

    ng_redirect('view', 'success', ng_t('NET_JOIN_REQUEST_DECLINED', 'Join request declined.'));
}

// ---------------------------
// ACTION: invite_centre (admin)
// ---------------------------
if ($action === 'invite_centre') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    $targetCentreId = (int)($_POST['target_centre_id'] ?? 0);

    if ($groupId <= 0 || $targetCentreId <= 0) ng_redirect($returnTab, 'error', ng_t('NET_MISSING_NETWORK_CENTRE_ID', 'Missing network/centre id.'));
    if (!ng_is_admin($pdo, $groupId, $currentCentreId)) ng_redirect($returnTab, 'error', ng_t('NET_MUST_ADMIN_INVITE', 'You must be a network admin to invite centres.'));
    if ($targetCentreId === $currentCentreId) ng_redirect($returnTab, 'error', ng_t('NET_CANNOT_INVITE_OWN_CENTRE', 'You cannot invite your own centre.'));

    $existing = ng_get_membership_by_pair($pdo, $groupId, $targetCentreId);

    if ($existing) {
        $status = (string)($existing['status'] ?? '');
        if (in_array($status, ['active','pending','invited'], true)) {
            ng_redirect('view', 'success', ng_t('NET_CENTRE_ALREADY_IN_PROGRESS', 'Centre is already in progress for this network.'));
        }

        $stmt = $pdo->prepare("
            UPDATE rescue_group_members
            SET status = 'invited',
                role = 'member',
                invited_by_user_id = :uid,
                requested_by_user_id = NULL,
                approved_by_user_id = NULL,
                updated_at = :now
            WHERE group_member_id = :id
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':now' => ng_now(),
            ':id'  => (int)$existing['group_member_id'],
        ]);

        ng_redirect('view', 'success', ng_t('NET_INVITATION_SENT', 'Invitation sent.'));
    }

    $stmt = $pdo->prepare("
        INSERT INTO rescue_group_members
            (group_id, centre_id, role, status, invited_by_user_id, created_at, updated_at)
        VALUES
            (:gid, :cid, 'member', 'invited', :uid, :now, :now)
    ");
    $stmt->execute([
        ':gid' => $groupId,
        ':cid' => $targetCentreId,
        ':uid' => $userId,
        ':now' => ng_now(),
    ]);

    ng_redirect('view', 'success', ng_t('NET_INVITATION_SENT', 'Invitation sent.'));
}

// ---------------------------
// ACTION: remove_member (admin)
// ---------------------------
if ($action === 'remove_member') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    $targetMemberId = (int)($_POST['target_member_id'] ?? 0);

    if ($groupId <= 0 || $targetMemberId <= 0) ng_redirect($returnTab, 'error', ng_t('NET_MISSING_MEMBER_ID', 'Missing member id.'));
    if (!ng_is_admin($pdo, $groupId, $currentCentreId)) ng_redirect($returnTab, 'error', ng_t('NET_MUST_ADMIN', 'You must be a network admin.'));

    $m = ng_get_membership($pdo, $targetMemberId);
    if (!$m || (int)$m['group_id'] !== $groupId) ng_redirect($returnTab, 'error', ng_t('NET_MEMBER_NOT_FOUND', 'Member record not found.'));
    if ((int)$m['centre_id'] === $currentCentreId) ng_redirect($returnTab, 'error', ng_t('NET_USE_LEAVE_OWN_CENTRE', 'Use Leave Network to remove your own centre.'));

    $stmt = $pdo->prepare("
        UPDATE rescue_group_members
        SET status = 'removed',
            updated_at = :now
        WHERE group_member_id = :id
    ");
    $stmt->execute([':now' => ng_now(), ':id' => $targetMemberId]);

    ng_redirect('view', 'success', ng_t('NET_MEMBER_REMOVED', 'Member removed.'));
}

// ---------------------------
// ACTION: set_member_role (admin)
// ---------------------------
if ($action === 'set_member_role') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    $targetMemberId = (int)($_POST['target_member_id'] ?? 0);
    $newRole = trim((string)($_POST['new_role'] ?? ''));

    if ($groupId <= 0 || $targetMemberId <= 0) ng_redirect($returnTab, 'error', ng_t('NET_MISSING_MEMBER_ID', 'Missing member id.'));
    if (!in_array($newRole, ['admin','member'], true)) ng_redirect($returnTab, 'error', ng_t('NET_INVALID_ROLE', 'Invalid role.'));
    if (!ng_is_admin($pdo, $groupId, $currentCentreId)) ng_redirect($returnTab, 'error', ng_t('NET_MUST_ADMIN', 'You must be a network admin.'));

    $m = ng_get_membership($pdo, $targetMemberId);
    if (!$m || (int)$m['group_id'] !== $groupId) ng_redirect($returnTab, 'error', ng_t('NET_MEMBER_NOT_FOUND', 'Member record not found.'));
    if ((string)$m['status'] !== 'active') ng_redirect($returnTab, 'error', ng_t('NET_ONLY_ACTIVE_MEMBERS_UPDATED', 'Only active members can be updated.'));

    $stmt = $pdo->prepare("
        UPDATE rescue_group_members
        SET role = :role,
            updated_at = :now
        WHERE group_member_id = :id
    ");
    $stmt->execute([
        ':role' => $newRole,
        ':now'  => ng_now(),
        ':id'   => $targetMemberId
    ]);

    ng_redirect('view', 'success', ng_t('NET_MEMBER_ROLE_UPDATED', 'Member role updated.'));
}

// ---------------------------
// ACTION: leave_network (member)
// ---------------------------
if ($action === 'leave_network') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    if ($groupId <= 0) ng_redirect('mynetworks', 'error', ng_t('NET_MISSING_ID', 'Missing network id.'));

    $existing = ng_get_membership_by_pair($pdo, $groupId, $currentCentreId);
    if (!$existing || (string)$existing['status'] !== 'active') {
        ng_redirect('mynetworks', 'error', ng_t('NET_NOT_ACTIVE_MEMBER', 'You are not an active member of this network.'));
    }

    // Safety: if you are the ONLY admin and there are other members, stop leaving
    if (($existing['role'] ?? '') === 'admin') {
        $stmt = $pdo->prepare("
            SELECT
              SUM(CASE WHEN role='admin' AND status='active' THEN 1 ELSE 0 END) AS admins,
              SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) AS members
            FROM rescue_group_members
            WHERE group_id = :gid
        ");
        $stmt->execute([':gid' => $groupId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $adminCount  = (int)($row['admins'] ?? 0);
        $memberCount = (int)($row['members'] ?? 0);

        if ($adminCount <= 1 && $memberCount > 1) {
            ng_redirect('view', 'error', ng_t('NET_ONLY_ADMIN_PROMOTE_FIRST', 'You are the only admin. Promote another admin before leaving.'));
        }
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_group_members
        SET status = 'left',
            updated_at = :now
        WHERE group_id = :gid
          AND centre_id = :cid
    ");
    $stmt->execute([
        ':now' => ng_now(),
        ':gid' => $groupId,
        ':cid' => $currentCentreId
    ]);

    ng_redirect('mynetworks', 'success', ng_t('NET_LEFT_NETWORK', 'You have left the network.'));
}

// ---------------------------
// ACTION: unshare_patient
// ---------------------------
if ($action === 'unshare_patient') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    $shareId = (int)($_POST['share_id'] ?? 0);

    if ($groupId <= 0) ng_redirect_network($groupId, 'patients', 'error', ng_t('NET_MISSING_ID', 'Missing network id.'));
    if ($shareId <= 0) ng_redirect_network($groupId, 'patients', 'error', ng_t('NET_MISSING_PATIENT_SHARE_ID', 'Missing patient share id.'));

    $stmt = $pdo->prepare("
        SELECT share_id, group_id, owner_centre_id, status
        FROM rescue_patient_shares
        WHERE share_id = :sid
          AND group_id = :gid
          AND share_type = 'group'
        LIMIT 1
    ");
    $stmt->execute([
        ':sid' => $shareId,
        ':gid' => $groupId,
    ]);
    $share = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$share) {
        ng_redirect_network($groupId, 'patients', 'error', ng_t('NET_PATIENT_SHARE_NOT_FOUND', 'Patient share not found.'));
    }

    if ((string)($share['status'] ?? '') !== 'active') {
        ng_redirect_network($groupId, 'patients', 'error', ng_t('NET_PATIENT_NOT_SHARED', 'This patient is not currently shared.'));
    }

    $isOwnerCentre = ((int)($share['owner_centre_id'] ?? 0) === $currentCentreId);
    $isNetworkAdmin = ng_is_admin($pdo, $groupId, $currentCentreId);

    if (!$isOwnerCentre && !$isNetworkAdmin) {
        ng_redirect_network($groupId, 'patients', 'error', ng_t('NET_UNSHARE_PERMISSION_DENIED', 'You do not have permission to unshare this patient.'));
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_patient_shares
        SET status = 'revoked',
            revoked_at = :now,
            revoked_by_account_id = :uid
        WHERE share_id = :sid
          AND status = 'active'
        LIMIT 1
    ");
    $stmt->execute([
        ':now' => ng_now(),
        ':uid' => $userId,
        ':sid' => $shareId,
    ]);

    ng_redirect_network($groupId, 'patients', 'success', ng_t('NET_PATIENT_SHARE_REMOVED', 'Patient share removed.'));
}

// Final fallback (should never hit)
ng_redirect($returnTab, 'error', ng_t('NET_UNHANDLED_ACTION', 'Unhandled action.'));
