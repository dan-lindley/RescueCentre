<?php
// views/groups/view.php
// Open Network page (members + admin tools)

if (!defined('APP_LOADED')) {
    echo '<div class="rc-alert red"><strong>' . htmlspecialchars($lang['ERROR'] ?? 'Error') . '</strong><br>' . htmlspecialchars($lang['NET_APP_NOT_LOADED'] ?? 'APP_LOADED not defined.') . '</div>';
    return;
}

if (!isset($pdo)) {
    echo '<div class="rc-alert red"><strong>' . htmlspecialchars($lang['ERROR'] ?? 'Error') . '</strong><br>' . htmlspecialchars($lang['DATABASE_CONNECTION_MISSING'] ?? 'Database connection not available.') . '</div>';
    return;
}

// Centre context
$currentCentreId = 0;
if (isset($centre_id) && (int)$centre_id > 0) $currentCentreId = (int)$centre_id;
elseif (isset($rescue_id) && (int)$rescue_id > 0) $currentCentreId = (int)$rescue_id;

if ($currentCentreId <= 0) {
    echo '<div class="rc-alert red"><strong>' . htmlspecialchars($lang['ERROR'] ?? 'Error') . '</strong><br>' . htmlspecialchars($lang['LOC_CENTRE_CONTEXT_MISSING'] ?? 'Centre context not available.') . '</div>';
    return;
}

// User context (accounts.id)
$userId = 0;
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['id'])) $userId = (int)$_SESSION['id'];
elseif (isset($user_id)) $userId = (int)$user_id;

function net_img_src(?string $path): string {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('~^https?://~i', $path)) return $path;
    if (strpos($path, '/') === 0) return $path;
    return '/' . $path;
}

function initials(string $name, string $fallback='NW'): string {
    $name = trim($name);
    if ($name === '') return $fallback;
    $parts = preg_split('/\s+/', $name);
    $a = strtoupper(substr($parts[0] ?? $fallback[0], 0, 1));
    $b = strtoupper(substr($parts[1] ?? ($fallback[1] ?? $fallback[0]), 0, 1));
    return substr($a . $b, 0, 2);
}

$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
if ($groupId <= 0) {
    echo '<div class="alert-box alert-red"><strong>' . htmlspecialchars($lang['ERROR'] ?? 'Error') . '</strong><br>' . htmlspecialchars($lang['NET_MISSING_ID'] ?? 'Missing network id.') . '</div>';
    return;
}

$network = null;
$myMembership = null;
$members = [];
$pendingRequests = [];
$inviteSearch = trim($_GET['invite_q'] ?? '');
$inviteCandidates = [];
$errorMsg = null;

try {
    // Network details
    $stmt = $pdo->prepare("
        SELECT group_id, name, description, logo_path, visibility, created_at, created_by_centre_id
        FROM rescue_groups
        WHERE group_id = :gid
        LIMIT 1
    ");
    $stmt->execute([':gid' => $groupId]);
    $network = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$network) {
        echo '<div class="alert-box alert-red"><strong>' . htmlspecialchars($lang['ERROR'] ?? 'Error') . '</strong><br>' . htmlspecialchars($lang['NET_NOT_FOUND'] ?? 'Network not found.') . '</div>';
        return;
    }

    // My membership (must be active to view/manage)
    $stmt = $pdo->prepare("
        SELECT *
        FROM rescue_group_members
        WHERE group_id = :gid AND centre_id = :cid
        LIMIT 1
    ");
    $stmt->execute([':gid' => $groupId, ':cid' => $currentCentreId]);
    $myMembership = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$myMembership || ($myMembership['status'] ?? '') !== 'active') {
        echo '<div class="alert-box alert-red"><strong>' . htmlspecialchars($lang['NET_ACCESS_DENIED'] ?? 'Access denied') . '</strong><br>' . htmlspecialchars($lang['NET_NOT_ACTIVE_MEMBER'] ?? 'You are not an active member of this network.') . '</div>';
        return;
    }

    $isAdmin = (($myMembership['role'] ?? '') === 'admin');

    // Members list
    $stmt = $pdo->prepare("
        SELECT
            gm.group_member_id,
            gm.group_id,
            gm.centre_id,
            gm.role,
            gm.status,
            gm.created_at,

            rc.rescue_name,
            cm.centre_profile_image
        FROM rescue_group_members gm
        JOIN rescue_centres rc ON rc.rescue_id = gm.centre_id
        LEFT JOIN rescue_centre_meta cm ON cm.centre_id = rc.rescue_id
        WHERE gm.group_id = :gid
          AND gm.status = 'active'
        ORDER BY (gm.role='admin') DESC, rc.rescue_name ASC
    ");
    $stmt->execute([':gid' => $groupId]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pending join requests (admin-only view)
    if ($isAdmin) {
        $stmt = $pdo->prepare("
            SELECT
                gm.group_member_id,
                gm.centre_id,
                gm.created_at,
                rc.rescue_name,
                cm.centre_profile_image
            FROM rescue_group_members gm
            JOIN rescue_centres rc ON rc.rescue_id = gm.centre_id
            LEFT JOIN rescue_centre_meta cm ON cm.centre_id = rc.rescue_id
            WHERE gm.group_id = :gid
              AND gm.status = 'pending'
            ORDER BY gm.created_at DESC
        ");
        $stmt->execute([':gid' => $groupId]);
        $pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Invite candidates (admin-only)
        // Show centres not already active/pending/invited in this network.
        $inviteLike = '%' . $inviteSearch . '%';
        $stmt = $pdo->prepare("
            SELECT rc.rescue_id, rc.rescue_name, cm.centre_profile_image
            FROM rescue_centres rc
            LEFT JOIN rescue_centre_meta cm ON cm.centre_id = rc.rescue_id
            WHERE rc.rescue_id <> :cid
              AND (:q = '' OR rc.rescue_name LIKE :qlike)
              AND rc.rescue_id NOT IN (
                    SELECT centre_id
                    FROM rescue_group_members
                    WHERE group_id = :gid
                      AND status IN ('active','pending','invited')
              )
            ORDER BY rc.rescue_name ASC
            LIMIT 50
        ");
        $stmt->execute([
            ':cid'   => $currentCentreId,
            ':gid'   => $groupId,
            ':q'     => $inviteSearch,
            ':qlike' => $inviteLike
        ]);
        $inviteCandidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Throwable $e) {
    $errorMsg = $e->getMessage();
}

if ($errorMsg) {
    echo '<div class="alert-box alert-red" style="margin-bottom:12px;"><strong>' . htmlspecialchars($lang['NET_LOAD_ERROR'] ?? 'Network: load error') . '</strong><br>' . htmlspecialchars($errorMsg) . '</div>';
    return;
}

$netName = (string)($network['name'] ?? ($lang['NET_UNNAMED'] ?? 'Unnamed Network'));
$netLogo = net_img_src($network['logo_path'] ?? '');
$netInit = initials($netName, 'NW');
$vis = (string)($network['visibility'] ?? 'invite_only');
$visLabel = ($vis === 'request_to_join') ? ($lang['NET_REQUEST_TO_JOIN'] ?? 'Request to join') : ($lang['NET_INVITE_ONLY'] ?? 'Invite only');
$isAdmin = (($myMembership['role'] ?? '') === 'admin');
?>

<div class="alert-box alert-purple" style="margin-bottom: 12px;">
    <strong><?= htmlspecialchars($lang['NET_NETWORK'] ?? 'Network') ?></strong><br>
    <?= htmlspecialchars($netName) ?>
</div>

<div style="border:1px solid #eee; border-radius:12px; padding:12px; background:#fff; margin-bottom:12px;">
    <div style="display:flex; gap:12px; align-items:flex-start;">
        <div style="width:64px; height:64px; border-radius:12px; overflow:hidden; border:1px solid #eee; display:flex; align-items:center; justify-content:center; background:#f7f7fb; flex:0 0 64px;">
            <?php if ($netLogo): ?>
                <img src="<?= htmlspecialchars($netLogo) ?>" alt="<?= htmlspecialchars($netName) ?>"
                     style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none';">
            <?php else: ?>
                <div style="font-weight:800; font-size:16px;"><?= htmlspecialchars($netInit) ?></div>
            <?php endif; ?>
        </div>

        <div style="flex:1;">
            <div style="font-weight:800; font-size:16px;"><?= htmlspecialchars($netName) ?></div>
            <div class="muted" style="margin-top:4px; font-size:13px;">
                <?= htmlspecialchars($lang['NET_JOIN_MODE'] ?? 'Join mode') ?>: <strong><?= htmlspecialchars($visLabel) ?></strong>
                - <?= htmlspecialchars($lang['NET_YOUR_ROLE'] ?? 'Your role') ?>: <strong><?= htmlspecialchars(ucfirst($myMembership['role'] ?? 'member')) ?></strong>
            </div>

            <?php if (!empty($network['description'])): ?>
                <div style="margin-top:10px; font-size:13px; color:#444;">
                    <?= nl2br(htmlspecialchars((string)$network['description'])) ?>
                </div>
            <?php endif; ?>

            <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap;">
                <a class="btn btn-outline" href="groups.php?tab=mynetworks"><?= htmlspecialchars($lang['NET_BACK_MY_NETWORKS'] ?? 'Back to My Networks') ?></a>

                <form method="post" action="controllers/groups_handler.php" style="margin:0;">
                    <input type="hidden" name="action" value="leave_network">
                    <input type="hidden" name="group_id" value="<?= (int)$groupId ?>">
                    <input type="hidden" name="return_tab" value="mynetworks">
                    <button class="btn btn-outline" type="submit"
                            onclick="return confirm('<?= htmlspecialchars($lang['NET_LEAVE_CONFIRM'] ?? 'Leave this network?') ?>');">
                        <?= htmlspecialchars($lang['NET_LEAVE_NETWORK'] ?? 'Leave Network') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
    <!-- Admin: pending join requests -->
    <div class="alert-box alert-purple" style="margin-bottom: 12px;">
        <strong><?= htmlspecialchars($lang['NET_ADMIN'] ?? 'Admin') ?></strong><br>
        <?= htmlspecialchars($lang['NET_ADMIN_HELP'] ?? 'Manage join requests and invite centres.') ?>
    </div>

    <div style="border:1px solid #eee; border-radius:12px; padding:12px; background:#fff; margin-bottom:12px;">
        <div style="font-weight:700; margin-bottom:8px;"><?= htmlspecialchars($lang['NET_PENDING_JOIN_REQUESTS'] ?? 'Pending join requests') ?></div>

        <?php if (empty($pendingRequests)): ?>
            <div class="alert-box alert-amber" style="margin-bottom:0;">
                <?= htmlspecialchars($lang['NET_NO_PENDING_JOIN_REQUESTS'] ?? 'No pending join requests.') ?>
            </div>
        <?php else: ?>
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap:10px;">
                <?php foreach ($pendingRequests as $pr): ?>
                    <?php
                        $cName = $pr['rescue_name'] ?? ($lang['NET_UNKNOWN_CENTRE'] ?? 'Unknown Centre');
                        $cImg  = net_img_src($pr['centre_profile_image'] ?? '');
                        $cInit = initials($cName, 'RC');
                        $when  = !empty($pr['created_at']) ? date('d M Y H:i', strtotime($pr['created_at'])) : '';
                    ?>
                    <div style="border:1px solid #eee; border-radius:10px; padding:10px;">
                        <div style="display:flex; gap:10px; align-items:center;">
                            <div style="width:44px; height:44px; border-radius:50%; overflow:hidden; border:1px solid #eee; display:flex; align-items:center; justify-content:center; background:#f7f7fb; flex:0 0 44px;">
                                <?php if ($cImg): ?>
                                    <img src="<?= htmlspecialchars($cImg) ?>" alt="<?= htmlspecialchars($cName) ?>"
                                         style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none';">
                                <?php else: ?>
                                    <div style="font-weight:800; font-size:13px;"><?= htmlspecialchars($cInit) ?></div>
                                <?php endif; ?>
                            </div>
                            <div style="flex:1;">
                                <div style="font-weight:700;"><?= htmlspecialchars($cName) ?></div>
                                <div class="muted" style="font-size:12px;"><?= htmlspecialchars($when) ?></div>
                            </div>
                        </div>

                        <div style="display:flex; gap:8px; margin-top:10px; flex-wrap:wrap;">
                            <form method="post" action="controllers/groups_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="approve_join_request">
                                <input type="hidden" name="group_member_id" value="<?= (int)$pr['group_member_id'] ?>">
                                <input type="hidden" name="return_tab" value="view">
                                <input type="hidden" name="group_id" value="<?= (int)$groupId ?>">
                                <button class="btn btn-primary" type="submit"><?= htmlspecialchars($lang['NET_APPROVE'] ?? 'Approve') ?></button>
                            </form>

                            <form method="post" action="controllers/groups_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="decline_join_request">
                                <input type="hidden" name="group_member_id" value="<?= (int)$pr['group_member_id'] ?>">
                                <input type="hidden" name="return_tab" value="view">
                                <input type="hidden" name="group_id" value="<?= (int)$groupId ?>">
                                <button class="btn btn-outline" type="submit"><?= htmlspecialchars($lang['NET_DECLINE'] ?? 'Decline') ?></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Admin: invite a centre -->
    <div style="border:1px solid #eee; border-radius:12px; padding:12px; background:#fff; margin-bottom:12px;">
        <div style="font-weight:700; margin-bottom:8px;"><?= htmlspecialchars($lang['NET_INVITE_CENTRE'] ?? 'Invite a centre') ?></div>

        <form method="get" action="groups.php" style="margin:0 0 10px 0;">
            <input type="hidden" name="tab" value="view">
            <input type="hidden" name="group_id" value="<?= (int)$groupId ?>">
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <input class="xform-input" style="max-width:360px;" type="text" name="invite_q"
                       value="<?= htmlspecialchars($inviteSearch) ?>" placeholder="<?= htmlspecialchars($lang['NET_SEARCH_CENTRES_PLACEHOLDER'] ?? 'Search centres by name...') ?>">
                <button class="btn btn-outline" type="submit"><?= htmlspecialchars($lang['SEARCH'] ?? 'Search') ?></button>
                <?php if ($inviteSearch !== ''): ?>
                    <a class="btn btn-outline" href="groups.php?tab=view&group_id=<?= (int)$groupId ?>"><?= htmlspecialchars($lang['CLEAR'] ?? 'Clear') ?></a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (empty($inviteCandidates)): ?>
            <div class="alert-box alert-amber" style="margin-bottom:0;">
                No centres found to invite<?= $inviteSearch !== '' ? ' for "' . htmlspecialchars($inviteSearch) . '"' : '' ?>.
            </div>
        <?php else: ?>
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap:10px;">
                <?php foreach ($inviteCandidates as $cand): ?>
                    <?php
                        $cName = $cand['rescue_name'] ?? ($lang['NET_UNKNOWN_CENTRE'] ?? 'Unknown Centre');
                        $cImg  = net_img_src($cand['centre_profile_image'] ?? '');
                        $cInit = initials($cName, 'RC');
                    ?>
                    <div style="border:1px solid #eee; border-radius:10px; padding:10px;">
                        <div style="display:flex; gap:10px; align-items:center;">
                            <div style="width:44px; height:44px; border-radius:50%; overflow:hidden; border:1px solid #eee; display:flex; align-items:center; justify-content:center; background:#f7f7fb; flex:0 0 44px;">
                                <?php if ($cImg): ?>
                                    <img src="<?= htmlspecialchars($cImg) ?>" alt="<?= htmlspecialchars($cName) ?>"
                                         style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none';">
                                <?php else: ?>
                                    <div style="font-weight:800; font-size:13px;"><?= htmlspecialchars($cInit) ?></div>
                                <?php endif; ?>
                            </div>
                            <div style="flex:1; font-weight:700;">
                                <?= htmlspecialchars($cName) ?>
                            </div>

                            <form method="post" action="controllers/groups_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="invite_centre">
                                <input type="hidden" name="group_id" value="<?= (int)$groupId ?>">
                                <input type="hidden" name="target_centre_id" value="<?= (int)$cand['rescue_id'] ?>">
                                <input type="hidden" name="return_tab" value="view">
                                <button class="btn btn-primary" type="submit"><?= htmlspecialchars($lang['NET_INVITE'] ?? 'Invite') ?></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Members -->
<div class="alert-box alert-purple" style="margin-bottom: 12px;">
    <strong><?= htmlspecialchars($lang['NET_MEMBERS'] ?? 'Members') ?></strong><br>
    <?= htmlspecialchars($lang['NET_MEMBERS_HELP'] ?? 'Active centres in this network.') ?>
</div>

<?php if (empty($members)): ?>
    <div class="alert-box alert-amber"><?= htmlspecialchars($lang['NET_NO_MEMBERS'] ?? 'No members found.') ?></div>
<?php else: ?>
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap:12px;">
        <?php foreach ($members as $m): ?>
            <?php
                $cName = $m['rescue_name'] ?? ($lang['NET_UNKNOWN_CENTRE'] ?? 'Unknown Centre');
                $cImg  = net_img_src($m['centre_profile_image'] ?? '');
                $cInit = initials($cName, 'RC');
                $role  = $m['role'] ?? 'member';
                $isMe  = ((int)$m['centre_id'] === $currentCentreId);
            ?>
            <div style="border:1px solid #eee; border-radius:12px; padding:12px; background:#fff;">
                <div style="display:flex; gap:12px; align-items:center;">
                    <div style="width:54px; height:54px; border-radius:50%; overflow:hidden; border:1px solid #eee; display:flex; align-items:center; justify-content:center; background:#f7f7fb; flex:0 0 54px;">
                        <?php if ($cImg): ?>
                            <img src="<?= htmlspecialchars($cImg) ?>" alt="<?= htmlspecialchars($cName) ?>"
                                 style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none';">
                        <?php else: ?>
                            <div style="font-weight:800; font-size:14px;"><?= htmlspecialchars($cInit) ?></div>
                        <?php endif; ?>
                    </div>

                    <div style="flex:1;">
                        <div style="font-weight:800;"><?= htmlspecialchars($cName) ?><?= $isMe ? ' (' . htmlspecialchars($lang['NET_YOU'] ?? 'You') . ')' : '' ?></div>
                        <div class="muted" style="font-size:13px;"><?= htmlspecialchars($lang['NET_ROLE'] ?? 'Role') ?>: <?= htmlspecialchars(ucfirst($role)) ?></div>
                    </div>
                </div>

                <?php if ($isAdmin && !$isMe): ?>
                    <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap;">
                        <?php if ($role !== 'admin'): ?>
                            <form method="post" action="controllers/groups_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="set_member_role">
                                <input type="hidden" name="group_id" value="<?= (int)$groupId ?>">
                                <input type="hidden" name="target_member_id" value="<?= (int)$m['group_member_id'] ?>">
                                <input type="hidden" name="new_role" value="admin">
                                <input type="hidden" name="return_tab" value="view">
                                <button class="btn btn-outline" type="submit"><?= htmlspecialchars($lang['NET_MAKE_ADMIN'] ?? 'Make Admin') ?></button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="controllers/groups_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="set_member_role">
                                <input type="hidden" name="group_id" value="<?= (int)$groupId ?>">
                                <input type="hidden" name="target_member_id" value="<?= (int)$m['group_member_id'] ?>">
                                <input type="hidden" name="new_role" value="member">
                                <input type="hidden" name="return_tab" value="view">
                                <button class="btn btn-outline" type="submit"><?= htmlspecialchars($lang['NET_REMOVE_ADMIN'] ?? 'Remove Admin') ?></button>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="controllers/groups_handler.php" style="margin:0;">
                            <input type="hidden" name="action" value="remove_member">
                            <input type="hidden" name="group_id" value="<?= (int)$groupId ?>">
                            <input type="hidden" name="target_member_id" value="<?= (int)$m['group_member_id'] ?>">
                            <input type="hidden" name="return_tab" value="view">
                            <button class="btn btn-outline" type="submit"
                                    onclick="return confirm('<?= htmlspecialchars($lang['NET_REMOVE_CENTRE_CONFIRM'] ?? 'Remove this centre from the network?') ?>');">
                                <?= htmlspecialchars($lang['DELETE'] ?? 'Remove') ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

