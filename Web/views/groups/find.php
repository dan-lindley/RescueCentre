<?php
// views/groups/find.php
// Find a Network (only visibility = request_to_join).

if (!defined('APP_LOADED')) {
    echo '<div class="rc-alert red"><strong>' . htmlspecialchars($lang['ERROR'] ?? 'Error') . '</strong><br>' . htmlspecialchars($lang['NET_APP_NOT_LOADED'] ?? 'APP_LOADED not defined.') . '</div>';
    return;
}

if (!isset($pdo)) {
    echo '<div class="rc-alert red"><strong>' . htmlspecialchars($lang['ERROR'] ?? 'Error') . '</strong><br>' . htmlspecialchars($lang['DATABASE_CONNECTION_MISSING'] ?? 'Database connection not available.') . '</div>';
    return;
}

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

function net_initials(string $name): string {
    $name = trim($name);
    if ($name === '') return 'NW';
    $parts = preg_split('/\s+/', $name);
    $a = strtoupper(substr($parts[0] ?? 'N', 0, 1));
    $b = strtoupper(substr($parts[1] ?? 'W', 0, 1));
    return substr($a . $b, 0, 2);
}

$q = trim($_GET['q'] ?? '');
$qLike = '%' . $q . '%';
$networks = [];
$errorMsg = null;

try {
    $sql = "
        SELECT
            g.group_id,
            g.name,
            g.description,
            g.visibility,
            g.created_at,
            gm.group_member_id,
            gm.status AS member_status,
            gm.role AS member_role
        FROM rescue_groups g
        LEFT JOIN rescue_group_members gm
          ON gm.group_id = g.group_id
         AND gm.centre_id = :cid
        WHERE g.visibility = 'request_to_join'
          AND (:q = '' OR g.name LIKE :qlike)
        ORDER BY g.name ASC
        LIMIT 200
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':cid' => $currentCentreId,
        ':q' => $q,
        ':qlike' => $qLike
    ]);
    $networks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $errorMsg = $e->getMessage();
}
?>

<?php if ($errorMsg): ?>
    <div class="rc-alert red">
        <strong><?= htmlspecialchars($lang['NET_FIND_LOAD_ERROR'] ?? 'Find a Network: load error') ?></strong><br>
        <?= htmlspecialchars($errorMsg) ?>
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="rc-stack">
    <div class="rc-alert purple">
        <strong><?= htmlspecialchars($lang['NET_FIND_NETWORK'] ?? 'Find a Network') ?></strong><br>
        <?= htmlspecialchars($lang['NET_FIND_HELP'] ?? 'Browse professional collaborative networks and request to join.') ?>
    </div>

    <div class="rc-panel">
        <h4><?= htmlspecialchars($lang['NET_START_NEW'] ?? 'Start a new network') ?></h4>
        <form method="post" action="controllers/groups_handler.php" class="rc-stack">
            <input type="hidden" name="action" value="create_network">
            <input type="hidden" name="return_tab" value="find">

            <div class="rc-card-grid">
                <div>
                    <label class="form-label" for="network_name"><?= htmlspecialchars($lang['NET_NAME'] ?? 'Network name') ?></label>
                    <input type="text" id="network_name" name="network_name" class="xform-input" required maxlength="150" placeholder="<?= htmlspecialchars($lang['NET_NAME_PLACEHOLDER'] ?? 'e.g. North West Wildlife Network') ?>">
                </div>

                <div>
                    <label class="form-label" for="network_description"><?= htmlspecialchars($lang['DESCRIPTION'] ?? 'Description') ?></label>
                    <input type="text" id="network_description" name="network_description" class="xform-input" maxlength="255" placeholder="<?= htmlspecialchars($lang['NET_DESCRIPTION_PLACEHOLDER'] ?? 'Short description shown to other centres') ?>">
                </div>

                <div>
                    <label class="form-label" for="visibility"><?= htmlspecialchars($lang['NET_JOIN_MODE'] ?? 'Join mode') ?></label>
                    <select id="visibility" name="visibility" class="xform-input" required>
                        <option value="request_to_join"><?= htmlspecialchars($lang['NET_REQUEST_TO_JOIN'] ?? 'Request to join') ?></option>
                        <option value="invite_only"><?= htmlspecialchars($lang['NET_INVITE_ONLY'] ?? 'Invite only') ?></option>
                    </select>
                </div>
            </div>

            <p class="rc-note"><?= htmlspecialchars($lang['NET_JOIN_MODE_HELP'] ?? 'Request to join networks can be found by other centres. Invite only networks stay hidden unless you invite a centre.') ?></p>

            <div class="rc-actions">
                <button type="submit" class="btn btn-primary" onclick="return confirm('<?= htmlspecialchars($lang['NET_CREATE_CONFIRM'] ?? 'Create this network?') ?>');">
                    <?= htmlspecialchars($lang['CREATE'] ?? 'Create') ?>
                </button>
            </div>
        </form>
    </div>

    <form method="get" action="groups.php" class="rc-row-head">
        <input type="hidden" name="tab" value="find">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="<?= htmlspecialchars($lang['NET_SEARCH_PLACEHOLDER'] ?? 'Search networks by name...') ?>" class="xform-input">
        <div class="rc-actions">
            <button class="btn btn-primary" type="submit"><?= htmlspecialchars($lang['SEARCH'] ?? 'Search') ?></button>
            <?php if ($q !== ''): ?>
                <a class="btn btn-outline" href="groups.php?tab=find"><?= htmlspecialchars($lang['CLEAR'] ?? 'Clear') ?></a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($networks)): ?>
        <div class="rc-alert amber">
            <?= htmlspecialchars($lang['NET_NONE_FOUND'] ?? 'No networks found') ?><?= $q !== '' ? ' ' . htmlspecialchars($lang['FOR'] ?? 'for') . ' "' . htmlspecialchars($q) . '"' : '' ?>.
        </div>
    <?php else: ?>
        <div class="rc-card-grid">
            <?php foreach ($networks as $n): ?>
                <?php
                    $name = $n['name'] ?? ($lang['NET_UNNAMED'] ?? 'Unnamed Network');
                    $desc = trim((string)($n['description'] ?? ''));
                    $initials = net_initials($name);
                    $status = $n['member_status'] ?? null;
                    $groupMemberId = !empty($n['group_member_id']) ? (int)$n['group_member_id'] : 0;
                    $groupId = (int)$n['group_id'];

                    $state = 'none';
                    if ($status === 'active') $state = 'active';
                    elseif ($status === 'pending') $state = 'pending';
                    elseif ($status === 'invited') $state = 'invited';
                    elseif ($status === 'declined') $state = 'declined';
                    elseif ($status === 'removed') $state = 'removed';
                    elseif ($status === 'left') $state = 'left';
                ?>

                <div class="rc-card">
                    <div class="rc-stack">
                        <div class="rc-item-main">
                            <strong><span class="rc-badge dark"><?= htmlspecialchars($initials) ?></span> <?= htmlspecialchars($name) ?></strong>
                            <small>
                                <?php if ($state === 'active'): ?>
                                    <?= htmlspecialchars($lang['NET_YOU_ARE_MEMBER'] ?? 'You are a member') ?>
                                <?php elseif ($state === 'pending'): ?>
                                    <?= htmlspecialchars($lang['NET_JOIN_PENDING'] ?? 'Join request pending') ?>
                                <?php elseif ($state === 'invited'): ?>
                                    <?= htmlspecialchars($lang['NET_INVITE_WAITING'] ?? 'Invite waiting') ?>
                                <?php elseif ($state === 'declined'): ?>
                                    <?= htmlspecialchars($lang['NET_REQUEST_DECLINED_PREVIOUSLY'] ?? 'Request declined previously') ?>
                                <?php elseif ($state === 'removed'): ?>
                                    <?= htmlspecialchars($lang['NET_REMOVED_FROM_NETWORK'] ?? 'Removed from network') ?>
                                <?php elseif ($state === 'left'): ?>
                                    <?= htmlspecialchars($lang['NET_YOU_LEFT'] ?? 'You left this network') ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($lang['NET_NOT_JOINED'] ?? 'Not joined') ?>
                                <?php endif; ?>
                            </small>
                        </div>

                        <?php if ($desc !== ''): ?>
                            <p class="rc-note"><?= htmlspecialchars($desc) ?></p>
                        <?php endif; ?>

                        <div class="rc-actions">
                            <?php if ($state === 'active'): ?>
                                <a class="btn btn-primary" href="groups.php?tab=view&group_id=<?= $groupId ?>"><?= htmlspecialchars($lang['OPEN'] ?? 'Open') ?></a>
                            <?php elseif ($state === 'pending'): ?>
                                <span class="rc-badge mid"><?= htmlspecialchars($lang['NET_REQUESTED'] ?? 'Requested') ?></span>

                                <?php if ($groupMemberId): ?>
                                    <form method="post" action="controllers/groups_handler.php">
                                        <input type="hidden" name="action" value="cancel_join_request">
                                        <input type="hidden" name="group_member_id" value="<?= $groupMemberId ?>">
                                        <input type="hidden" name="return_tab" value="find">
                                        <button class="btn btn-outline" type="submit" onclick="return confirm('<?= htmlspecialchars($lang['NET_CANCEL_YOUR_JOIN_CONFIRM'] ?? 'Cancel your join request?') ?>');">
                                            <?= htmlspecialchars($lang['CANCEL'] ?? 'Cancel') ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <form method="post" action="controllers/groups_handler.php">
                                    <input type="hidden" name="action" value="request_to_join">
                                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                                    <input type="hidden" name="return_tab" value="find">
                                    <button class="btn btn-primary" type="submit" onclick="return confirm('<?= htmlspecialchars($lang['NET_REQUEST_JOIN_CONFIRM'] ?? 'Request to join this network?') ?>');">
                                        <?= htmlspecialchars($lang['NET_JOIN_NETWORK'] ?? 'Join Network') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
