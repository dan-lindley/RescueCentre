<?php
// views/groups/my_groups.php
// "My Networks" tab (tables: rescue_groups, rescue_group_members)

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

$activeNetworks = [];
$pendingNetworks = [];
$errorMsg = null;

try {
    $sqlActive = "
        SELECT
            gm.group_member_id,
            gm.group_id,
            gm.role,
            gm.status,
            gm.created_at AS joined_at,
            g.name,
            g.description,
            g.visibility,
            g.created_at AS network_created_at
        FROM rescue_group_members gm
        JOIN rescue_groups g ON g.group_id = gm.group_id
        WHERE gm.centre_id = :cid
          AND gm.status = 'active'
        ORDER BY g.name ASC
    ";
    $stmt = $pdo->prepare($sqlActive);
    $stmt->execute([':cid' => $currentCentreId]);
    $activeNetworks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sqlPending = "
        SELECT
            gm.group_member_id,
            gm.group_id,
            gm.role,
            gm.status,
            gm.created_at AS requested_at,
            g.name,
            g.description,
            g.visibility,
            g.created_at AS network_created_at
        FROM rescue_group_members gm
        JOIN rescue_groups g ON g.group_id = gm.group_id
        WHERE gm.centre_id = :cid
          AND gm.status IN ('invited','pending')
        ORDER BY gm.created_at DESC
    ";
    $stmt = $pdo->prepare($sqlPending);
    $stmt->execute([':cid' => $currentCentreId]);
    $pendingNetworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $errorMsg = $e->getMessage();
}
?>

<?php if ($errorMsg): ?>
    <div class="rc-alert red">
        <strong><?= htmlspecialchars($lang['NET_MY_NETWORKS_LOAD_ERROR'] ?? 'My Networks: load error') ?></strong><br>
        <?= htmlspecialchars($errorMsg) ?><br><br>
        <span class="rc-muted">
            <?= htmlspecialchars($lang['NET_TABLES_HELP'] ?? 'If this mentions missing tables or columns, check rescue_groups and rescue_group_members.') ?>
        </span>
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="rc-stack">
    <div class="rc-alert purple">
        <strong><?= htmlspecialchars($lang['NET_MY_NETWORKS'] ?? 'My Networks') ?></strong><br>
        <?= htmlspecialchars($lang['NET_MY_NETWORKS_HELP'] ?? 'Networks your centre belongs to, and any pending invitations or requests.') ?>
    </div>

    <div class="rc-actions">
        <a class="btn btn-primary" href="groups.php?tab=find"><?= htmlspecialchars($lang['NET_FIND_NETWORK'] ?? 'Find a Network') ?></a>
        <a class="btn btn-outline" href="groups.php?tab=requests"><?= htmlspecialchars($lang['NET_REQUESTS'] ?? 'Network Requests') ?></a>
        <a class="btn btn-outline" href="groups.php?tab=mygroups&create=1"><?= htmlspecialchars($lang['NET_CREATE_NETWORK'] ?? 'Create Network') ?></a>
    </div>

    <?php if (!empty($pendingNetworks)): ?>
        <div class="rc-alert amber">
            <strong><?= htmlspecialchars($lang['NET_PENDING'] ?? 'Pending') ?></strong><br>
            <?= sprintf(htmlspecialchars($lang['NET_PENDING_COUNT'] ?? 'You have %d pending network item%s.'), (int)count($pendingNetworks), count($pendingNetworks) === 1 ? '' : 's') ?>
            <div class="rc-muted"><?= htmlspecialchars($lang['NET_PENDING_HELP'] ?? 'Use Network Requests to accept or decline invites.') ?></div>
        </div>

        <div class="rc-card-grid">
            <?php foreach ($pendingNetworks as $n): ?>
                <?php
                    $name = $n['name'] ?? ($lang['NET_UNNAMED'] ?? 'Unnamed Network');
                    $initials = net_initials($name);
                    $status = $n['status'] ?? '';
                    $when = !empty($n['requested_at']) ? date('d M Y H:i', strtotime($n['requested_at'])) : '';
                ?>
                <div class="rc-card">
                    <div class="rc-item-main">
                        <strong><span class="rc-badge dark"><?= htmlspecialchars($initials) ?></span> <?= htmlspecialchars($name) ?></strong>
                        <small><?= htmlspecialchars(ucfirst($status)) ?><?= $when ? ' - ' . htmlspecialchars($when) : '' ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($activeNetworks)): ?>
        <div class="rc-alert purple">
            <strong><?= htmlspecialchars($lang['NET_NONE_TO_SHOW'] ?? 'No networks to show') ?></strong><br>
            <?= htmlspecialchars($lang['NET_NONE_ACTIVE_HELP'] ?? 'Your centre is not currently a member of any active networks.') ?>
        </div>
    <?php else: ?>
        <div class="rc-alert purple">
            <strong><?= htmlspecialchars($lang['NET_ACTIVE_NETWORKS'] ?? 'Active Networks') ?></strong><br>
            <?= sprintf(htmlspecialchars($lang['NET_ACTIVE_COUNT'] ?? 'You are an active member of %d network%s.'), (int)count($activeNetworks), count($activeNetworks) === 1 ? '' : 's') ?>
        </div>

        <div class="rc-card-grid">
            <?php foreach ($activeNetworks as $n): ?>
                <?php
                    $name = $n['name'] ?? ($lang['NET_UNNAMED'] ?? 'Unnamed Network');
                    $initials = net_initials($name);
                    $role = $n['role'] ?? 'member';
                    $joined = !empty($n['joined_at']) ? date('d M Y', strtotime($n['joined_at'])) : '';
                    $desc = trim((string)($n['description'] ?? ''));
                ?>
                <div class="rc-card">
                    <div class="rc-stack">
                        <div class="rc-item-main">
                            <strong><span class="rc-badge dark"><?= htmlspecialchars($initials) ?></span> <?= htmlspecialchars($name) ?></strong>
                            <small>
                                <?= htmlspecialchars($lang['NET_ROLE'] ?? 'Role') ?>: <?= htmlspecialchars(ucfirst($role)) ?>
                                <?= $joined ? ' - ' . htmlspecialchars($lang['NET_JOINED'] ?? 'Joined') . ' ' . htmlspecialchars($joined) : '' ?>
                            </small>
                        </div>

                        <?php if ($desc !== ''): ?>
                            <p class="rc-note"><?= nl2br(htmlspecialchars($desc)) ?></p>
                        <?php endif; ?>

                        <div class="rc-actions">
                            <a class="btn btn-primary" href="viewnetwork.php?network_id=<?= (int)$n['group_id'] ?>&tab=dashboard">
                                <?= htmlspecialchars($lang['NET_OPEN_NETWORK'] ?? 'Open Network') ?>
                            </a>
                            <a class="btn btn-outline" href="groups.php?tab=requests">
                                <?= htmlspecialchars($lang['MANAGE'] ?? 'Manage') ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
