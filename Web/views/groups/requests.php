<?php
// views/groups/requests.php
// Network Requests tab (invites, your join requests, and admin approvals)

if (!defined('APP_LOADED')) {
    echo '<div class="rc-alert red"><strong>' . htmlspecialchars($lang['ERROR'] ?? 'Error') . '</strong><br>' . htmlspecialchars($lang['NET_APP_NOT_LOADED'] ?? 'APP_LOADED not defined.') . '</div>';
    return;
}

if (!isset($pdo)) {
    echo '<div class="rc-alert red"><strong>' . htmlspecialchars($lang['ERROR'] ?? 'Error') . '</strong><br>' . htmlspecialchars($lang['DATABASE_CONNECTION_MISSING'] ?? 'Database connection not available.') . '</div>';
    return;
}

// Centre context: support either $centre_id or $rescue_id
$currentCentreId = 0;
if (isset($centre_id) && (int)$centre_id > 0) {
    $currentCentreId = (int)$centre_id;
} elseif (isset($rescue_id) && (int)$rescue_id > 0) {
    $currentCentreId = (int)$rescue_id;
}

if ($currentCentreId <= 0) {
    echo '<div class="rc-alert red"><strong>' . htmlspecialchars($lang['ERROR'] ?? 'Error') . '</strong><br>' . htmlspecialchars($lang['LOC_CENTRE_CONTEXT_MISSING'] ?? 'Centre context not available.') . '</div>';
    return;
}

// User context (accounts.id)
$userId = 0;
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['id'])) {
    $userId = (int)$_SESSION['id'];
} elseif (isset($user_id)) {
    $userId = (int)$user_id;
}

function net_img_src(?string $path): string {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('~^https?://~i', $path)) return $path;
    if (strpos($path, '/') === 0) return $path;
    return '/' . $path;
}

function net_initials(string $name): string {
    $name = trim($name);
    if ($name === '') return 'NW';
    $parts = preg_split('/\s+/', $name);
    $a = strtoupper(substr($parts[0] ?? 'N', 0, 1));
    $b = strtoupper(substr($parts[1] ?? 'W', 0, 1));
    return substr($a . $b, 0, 2);
}

$invites = [];
$myJoinRequests = [];
$adminPendingRequests = [];
$errorMsg = null;

try {
    // 1) Invites to my centre
    $sqlInvites = "
        SELECT
            gm.group_member_id,
            gm.group_id,
            gm.status,
            gm.created_at AS invited_at,
            g.name,
            g.description,
            g.logo_path,
            g.visibility
        FROM rescue_group_members gm
        JOIN rescue_groups g ON g.group_id = gm.group_id
        WHERE gm.centre_id = :cid
          AND gm.status = 'invited'
        ORDER BY gm.created_at DESC
    ";
    $stmt = $pdo->prepare($sqlInvites);
    $stmt->execute([':cid' => $currentCentreId]);
    $invites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2) My join requests (pending) - requests made by my centre/user
    // We don't strictly need user_id, but it helps keep it "mine".
    $sqlMyReq = "
        SELECT
            gm.group_member_id,
            gm.group_id,
            gm.status,
            gm.created_at AS requested_at,
            g.name,
            g.description,
            g.logo_path,
            g.visibility
        FROM rescue_group_members gm
        JOIN rescue_groups g ON g.group_id = gm.group_id
        WHERE gm.centre_id = :cid
          AND gm.status = 'pending'
        ORDER BY gm.created_at DESC
    ";
    $stmt = $pdo->prepare($sqlMyReq);
    $stmt->execute([':cid' => $currentCentreId]);
    $myJoinRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3) Admin approvals needed (pending join requests from other centres) for networks where I am admin
    $sqlAdminPending = "
        SELECT
            gm.group_member_id,
            gm.group_id,
            gm.centre_id AS requesting_centre_id,
            gm.status,
            gm.created_at AS requested_at,

            g.name,
            g.logo_path,

            rc.rescue_name AS requesting_centre_name
        FROM rescue_group_members gm
        JOIN rescue_groups g ON g.group_id = gm.group_id

        JOIN rescue_group_members admincheck
          ON admincheck.group_id = gm.group_id
         AND admincheck.centre_id = :cid
         AND admincheck.status = 'active'
         AND admincheck.role = 'admin'

        JOIN rescue_centres rc
          ON rc.rescue_id = gm.centre_id

        WHERE gm.status = 'pending'
          AND gm.centre_id <> :cid
        ORDER BY gm.created_at DESC
    ";
    $stmt = $pdo->prepare($sqlAdminPending);
    $stmt->execute([':cid' => $currentCentreId]);
    $adminPendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $errorMsg = $e->getMessage();
}
?>

<?php if ($errorMsg): ?>
    <div class="alert-box alert-red" style="margin-bottom: 12px;">
        <strong><?= htmlspecialchars($lang['NET_REQUESTS_LOAD_ERROR'] ?? 'Network Requests: load error') ?></strong><br>
        <?= htmlspecialchars($errorMsg) ?>
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="alert-box alert-purple" style="margin-bottom: 12px;">
    <strong><?= htmlspecialchars($lang['NET_REQUESTS'] ?? 'Network Requests') ?></strong><br>
    <?= htmlspecialchars($lang['NET_REQUESTS_HELP'] ?? 'Invitations to join networks, your join requests, and approvals if you are a network admin.') ?>
</div>

<!-- =========================
     INVITES
========================= -->
<div class="alert-box alert-purple" style="margin-bottom: 12px;">
    <strong><?= htmlspecialchars($lang['NET_INVITATIONS'] ?? 'Invitations') ?></strong><br>
    <?= htmlspecialchars($lang['NET_INVITATIONS_HELP'] ?? 'Networks that have invited your centre.') ?>
</div>

<?php if (empty($invites)): ?>
    <div class="alert-box alert-amber" style="margin-bottom: 12px;">
        <?= htmlspecialchars($lang['NET_NO_INVITATIONS'] ?? 'No network invitations right now.') ?>
    </div>
<?php else: ?>
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap:12px; margin-bottom:18px;">
        <?php foreach ($invites as $n): ?>
            <?php
                $name = $n['name'] ?? ($lang['NET_UNNAMED'] ?? 'Unnamed Network');
                $img = net_img_src($n['logo_path'] ?? '');
                $initials = net_initials($name);
                $when = !empty($n['invited_at']) ? date('d M Y H:i', strtotime($n['invited_at'])) : '';
                $desc = trim((string)($n['description'] ?? ''));
            ?>

            <div style="border:1px solid #eee; border-radius:12px; padding:12px; background:#fff;">
                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <div style="width:54px; height:54px; border-radius:10px; overflow:hidden; flex:0 0 54px; border:1px solid #eee; display:flex; align-items:center; justify-content:center; background:#f7f7fb;">
                        <?php if ($img): ?>
                            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($name) ?>"
                                 style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none';">
                        <?php else: ?>
                            <div style="font-weight:700; font-size:14px;"><?= htmlspecialchars($initials) ?></div>
                        <?php endif; ?>
                    </div>

                    <div style="flex:1;">
                        <div style="font-weight:700; font-size:15px; line-height:1.2;">
                            <?= htmlspecialchars($name) ?>
                        </div>
                        <div class="muted" style="font-size:13px; margin-top:4px;">
                            <?= htmlspecialchars($lang['NET_INVITED'] ?? 'Invited') ?><?= $when ? ' • ' . htmlspecialchars($when) : '' ?>
                        </div>

                        <?php if ($desc !== ''): ?>
                            <div style="margin-top:8px; font-size:13px; color:#444;">
                                <?= nl2br(htmlspecialchars($desc)) ?>
                            </div>
                        <?php endif; ?>

                        <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; align-items:center;">
                            <form method="post" action="controllers/groups_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="accept_invite">
                                <input type="hidden" name="group_member_id" value="<?= (int)$n['group_member_id'] ?>">
                                <input type="hidden" name="return_tab" value="requests">
                                <button class="btn btn-primary" type="submit"
                                        onclick="return confirm('<?= htmlspecialchars($lang['NET_ACCEPT_INVITE_CONFIRM'] ?? 'Accept invitation to join this network?') ?>');">
                                    <?= htmlspecialchars($lang['NET_ACCEPT'] ?? 'Accept') ?>
                                </button>
                            </form>

                            <form method="post" action="controllers/groups_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="decline_invite">
                                <input type="hidden" name="group_member_id" value="<?= (int)$n['group_member_id'] ?>">
                                <input type="hidden" name="return_tab" value="requests">
                                <button class="btn btn-outline" type="submit"
                                        onclick="return confirm('<?= htmlspecialchars($lang['NET_DECLINE_INVITE_CONFIRM'] ?? 'Decline this network invitation?') ?>');">
                                    <?= htmlspecialchars($lang['NET_DECLINE'] ?? 'Decline') ?>
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- =========================
     MY JOIN REQUESTS
========================= -->
<div class="alert-box alert-purple" style="margin-bottom: 12px;">
    <strong><?= htmlspecialchars($lang['NET_MY_JOIN_REQUESTS'] ?? 'My Join Requests') ?></strong><br>
    <?= htmlspecialchars($lang['NET_MY_JOIN_REQUESTS_HELP'] ?? 'Networks your centre has requested to join.') ?>
</div>

<?php if (empty($myJoinRequests)): ?>
    <div class="alert-box alert-amber" style="margin-bottom: 12px;">
        <?= htmlspecialchars($lang['NET_NO_PENDING_JOIN_REQUESTS'] ?? 'You have no pending join requests.') ?>
    </div>
<?php else: ?>
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap:12px; margin-bottom:18px;">
        <?php foreach ($myJoinRequests as $n): ?>
            <?php
                $name = $n['name'] ?? ($lang['NET_UNNAMED'] ?? 'Unnamed Network');
                $img = net_img_src($n['logo_path'] ?? '');
                $initials = net_initials($name);
                $when = !empty($n['requested_at']) ? date('d M Y H:i', strtotime($n['requested_at'])) : '';
            ?>
            <div style="border:1px solid #eee; border-radius:12px; padding:12px; background:#fff;">
                <div style="display:flex; gap:12px; align-items:center;">
                    <div style="width:54px; height:54px; border-radius:10px; overflow:hidden; flex:0 0 54px; border:1px solid #eee; display:flex; align-items:center; justify-content:center; background:#f7f7fb;">
                        <?php if ($img): ?>
                            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($name) ?>"
                                 style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none';">
                        <?php else: ?>
                            <div style="font-weight:700; font-size:14px;"><?= htmlspecialchars($initials) ?></div>
                        <?php endif; ?>
                    </div>

                    <div style="flex:1;">
                        <div style="font-weight:700; font-size:15px; line-height:1.2;">
                            <?= htmlspecialchars($name) ?>
                        </div>
                        <div class="muted" style="font-size:13px; margin-top:4px;">
                            <?= htmlspecialchars($lang['NET_PENDING'] ?? 'Pending') ?><?= $when ? ' • ' . htmlspecialchars($lang['NET_REQUESTED'] ?? 'Requested') . ' ' . htmlspecialchars($when) : '' ?>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap;">
                    <form method="post" action="controllers/groups_handler.php" style="margin:0;">
                        <input type="hidden" name="action" value="cancel_join_request">
                        <input type="hidden" name="group_member_id" value="<?= (int)$n['group_member_id'] ?>">
                        <input type="hidden" name="return_tab" value="requests">
                        <button class="btn btn-outline" type="submit"
                                onclick="return confirm('<?= htmlspecialchars($lang['NET_CANCEL_JOIN_CONFIRM'] ?? 'Cancel this join request?') ?>');">
                            <?= htmlspecialchars($lang['NET_CANCEL_REQUEST'] ?? 'Cancel Request') ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- =========================
     ADMIN APPROVALS
========================= -->
<div class="alert-box alert-purple" style="margin-bottom: 12px;">
    <strong><?= htmlspecialchars($lang['NET_APPROVALS'] ?? 'Approvals') ?></strong><br>
    <?= htmlspecialchars($lang['NET_APPROVALS_HELP'] ?? 'Join requests waiting for approval in networks where your centre is an admin.') ?>
</div>

<?php if (empty($adminPendingRequests)): ?>
    <div class="alert-box alert-amber" style="margin-bottom: 12px;">
        <?= htmlspecialchars($lang['NET_NO_APPROVALS'] ?? 'No join requests awaiting your approval.') ?>
    </div>
<?php else: ?>
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(420px, 1fr)); gap:12px;">
        <?php foreach ($adminPendingRequests as $r): ?>
            <?php
                $networkName = $r['name'] ?? ($lang['NET_UNNAMED'] ?? 'Unnamed Network');
                $networkImg  = net_img_src($r['logo_path'] ?? '');
                $networkInit = net_initials($networkName);
                $centreName  = $r['requesting_centre_name'] ?? ($lang['NET_UNKNOWN_CENTRE'] ?? 'Unknown Centre');
                $when = !empty($r['requested_at']) ? date('d M Y H:i', strtotime($r['requested_at'])) : '';
            ?>

            <div style="border:1px solid #eee; border-radius:12px; padding:12px; background:#fff;">
                <div style="display:flex; gap:12px; align-items:flex-start;">
                    <div style="width:54px; height:54px; border-radius:10px; overflow:hidden; flex:0 0 54px; border:1px solid #eee; display:flex; align-items:center; justify-content:center; background:#f7f7fb;">
                        <?php if ($networkImg): ?>
                            <img src="<?= htmlspecialchars($networkImg) ?>" alt="<?= htmlspecialchars($networkName) ?>"
                                 style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none';">
                        <?php else: ?>
                            <div style="font-weight:700; font-size:14px;"><?= htmlspecialchars($networkInit) ?></div>
                        <?php endif; ?>
                    </div>

                    <div style="flex:1;">
                        <div style="font-weight:700; font-size:15px;">
                            <?= htmlspecialchars($centreName) ?>
                        </div>

                        <div class="muted" style="font-size:13px; margin-top:4px;">
                            <?= htmlspecialchars($lang['NET_REQUESTED_TO_JOIN'] ?? 'Requested to join') ?> <strong><?= htmlspecialchars($networkName) ?></strong>
                            <?= $when ? ' • ' . htmlspecialchars($when) : '' ?>
                        </div>

                        <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; align-items:center;">
                            <form method="post" action="controllers/groups_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="approve_join_request">
                                <input type="hidden" name="group_member_id" value="<?= (int)$r['group_member_id'] ?>">
                                <input type="hidden" name="return_tab" value="requests">
                                <button class="btn btn-primary" type="submit"
                                        onclick="return confirm('<?= htmlspecialchars($lang['NET_APPROVE_JOIN_CONFIRM'] ?? 'Approve this join request?') ?>');">
                                    <?= htmlspecialchars($lang['NET_APPROVE'] ?? 'Approve') ?>
                                </button>
                            </form>

                            <form method="post" action="controllers/groups_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="decline_join_request">
                                <input type="hidden" name="group_member_id" value="<?= (int)$r['group_member_id'] ?>">
                                <input type="hidden" name="return_tab" value="requests">
                                <button class="btn btn-outline" type="submit"
                                        onclick="return confirm('<?= htmlspecialchars($lang['NET_DECLINE_JOIN_CONFIRM'] ?? 'Decline this join request?') ?>');">
                                    <?= htmlspecialchars($lang['NET_DECLINE'] ?? 'Decline') ?>
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

        <?php endforeach; ?>
    </div>
<?php endif; ?>


