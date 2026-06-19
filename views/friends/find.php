<?php
// views/friends/find.php
// Directory of centres with friend status + POST actions to controllers/friends_handler.php

if (!defined('APP_LOADED')) {
    echo '<div class="rc-alert red"><strong>Error</strong><br>APP_LOADED not defined.</div>';
    return;
}

if (!isset($pdo)) {
    echo '<div class="rc-alert red"><strong>Error</strong><br>Database connection not available.</div>';
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
    echo '<div class="rc-alert red"><strong>Error</strong><br>Centre context not available.</div>';
    return;
}

function rc_img_src(?string $path): string {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('~^https?://~i', $path)) return $path;
    if (strpos($path, '/') === 0) return $path;
    return '/' . $path;
}

function rc_initials(string $name): string {
    $name = trim($name);
    if ($name === '') return 'RC';
    $parts = preg_split('/\s+/', $name);
    $a = strtoupper(substr($parts[0] ?? 'R', 0, 1));
    $b = strtoupper(substr($parts[1] ?? 'C', 0, 1));
    return substr($a . $b, 0, 2);
}

// Search (GET)
$q = trim($_GET['q'] ?? '');
$qLike = '%' . $q . '%';

$centres = [];
$errorMsg = null;

try {
    $sql = "
        SELECT
            c.rescue_id,
            c.rescue_name,
            m.centre_profile_image,

            f.friendship_id,
            f.status AS friend_status,
            f.requested_by_centre_id

        FROM rescue_centres c
        LEFT JOIN rescue_centre_meta m
            ON m.centre_id = c.rescue_id

        LEFT JOIN rescue_centre_friends f
            ON f.centre_a_id = LEAST(:cid, c.rescue_id)
           AND f.centre_b_id = GREATEST(:cid, c.rescue_id)

        WHERE c.rescue_id <> :cid
          AND (
                :q = ''
                OR c.rescue_name LIKE :qlike
              )
        ORDER BY c.rescue_name ASC
        LIMIT 200
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':cid'   => $currentCentreId,
        ':q'     => $q,
        ':qlike' => $qLike
    ]);

    $centres = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $errorMsg = $e->getMessage();
}
?>

<?php if ($errorMsg): ?>
    <div class="alert-box alert-red" style="margin-bottom: 12px;">
        <strong>Find a Centre: load error</strong><br>
        <?= htmlspecialchars($errorMsg) ?>
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="alert-box alert-purple" style="margin-bottom: 12px;">
    <strong>Find a Centre</strong><br>
    Search for another rescue centre and send a friend request.
</div>

<!-- Search box (GET is correct for search) -->
<form method="get" action="friends.php" style="margin-bottom: 12px;">
    <input type="hidden" name="tab" value="find">
    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <input
            type="text"
            name="q"
            value="<?= htmlspecialchars($q) ?>"
            placeholder="Search centres by name..."
            class="xform-input"
            style="max-width:360px;"
        >
        <button class="btn btn-primary" type="submit">Search</button>

        <?php if ($q !== ''): ?>
            <a class="btn btn-outline" href="friends.php?tab=find">Clear</a>
        <?php endif; ?>
    </div>
</form>

<?php if (empty($centres)): ?>
    <div class="alert-box alert-amber" style="margin-bottom: 12px;">
        No centres found<?= $q !== '' ? ' for "' . htmlspecialchars($q) . '"' : '' ?>.
    </div>
<?php else: ?>

    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap:12px;">
        <?php foreach ($centres as $c): ?>
            <?php
                $centreName = $c['rescue_name'] ?? 'Unknown Centre';
                $img = rc_img_src($c['centre_profile_image'] ?? '');
                $initials = rc_initials($centreName);

                $status = $c['friend_status'] ?? null;
                $friendshipId = !empty($c['friendship_id']) ? (int)$c['friendship_id'] : 0;
                $requestedBy = isset($c['requested_by_centre_id']) ? (int)$c['requested_by_centre_id'] : 0;

                $state = 'none';
                if ($status === 'approved') {
                    $state = 'friends';
                } elseif ($status === 'pending') {
                    $state = ($requestedBy === $currentCentreId) ? 'pending_sent' : 'pending_received';
                } elseif ($status === 'blocked') {
                    $state = 'blocked';
                } elseif ($status === 'declined') {
                    $state = 'declined';
                } elseif ($status === 'cancelled') {
                    $state = 'cancelled';
                }

                // Keep search query on redirect (optional):
                // handler currently redirects to friends.php?tab=find&success=...
                // so the search will clear after action. We'll keep it simple for now.
            ?>

            <div style="border:1px solid #eee; border-radius:12px; padding:12px; background:#fff;">
                <div style="display:flex; gap:12px; align-items:center;">
                    <div style="width:54px; height:54px; border-radius:50%; overflow:hidden; flex:0 0 54px; border:1px solid #eee; display:flex; align-items:center; justify-content:center; background:#f7f7fb;">
                        <?php if ($img): ?>
                            <img src="<?= htmlspecialchars($img) ?>"
                                 alt="<?= htmlspecialchars($centreName) ?>"
                                 style="width:100%; height:100%; object-fit:cover;"
                                 onerror="this.style.display='none';">
                        <?php else: ?>
                            <div style="font-weight:700; font-size:14px;"><?= htmlspecialchars($initials) ?></div>
                        <?php endif; ?>
                    </div>

                    <div style="flex:1;">
                        <div style="font-weight:700; font-size:15px; line-height:1.2;">
                            <?= htmlspecialchars($centreName) ?>
                        </div>

                        <?php if ($state === 'friends'): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">Friends</div>
                        <?php elseif ($state === 'pending_sent'): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">Request sent (pending)</div>
                        <?php elseif ($state === 'pending_received'): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">Incoming request</div>
                        <?php elseif ($state === 'blocked'): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">Blocked</div>
                        <?php elseif ($state === 'declined'): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">Previously declined</div>
                        <?php elseif ($state === 'cancelled'): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">Previously cancelled</div>
                        <?php else: ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">Not connected</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; align-items:center;">

                    <?php if ($state === 'none' || $state === 'declined' || $state === 'cancelled'): ?>
                        <form method="post" action="controllers/friends_handler.php" style="margin:0;">
                            <input type="hidden" name="action" value="request">
                            <input type="hidden" name="target_centre_id" value="<?= (int)$c['rescue_id'] ?>">
                            <input type="hidden" name="return_tab" value="find">
                            <button class="btn btn-primary" type="submit"
                                    onclick="return confirm('Send a friend request to <?= htmlspecialchars($centreName) ?>?');">
                                Request Friend
                            </button>
                        </form>

                    <?php elseif ($state === 'pending_sent'): ?>
                        <span class="rc-badge na">
                            Request Sent
                        </span>

                        <?php if ($friendshipId): ?>
                            <form method="post" action="controllers/friends_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="friendship_id" value="<?= $friendshipId ?>">
                                <input type="hidden" name="return_tab" value="find">
                                <button class="btn btn-outline" type="submit"
                                        onclick="return confirm('Cancel this friend request?');">
                                    Cancel
                                </button>
                            </form>
                        <?php endif; ?>

                    <?php elseif ($state === 'pending_received'): ?>
                        <?php if ($friendshipId): ?>
                            <form method="post" action="controllers/friends_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="friendship_id" value="<?= $friendshipId ?>">
                                <input type="hidden" name="return_tab" value="find">
                                <button class="btn btn-primary" type="submit"
                                        onclick="return confirm('Approve this friend request?');">
                                    Approve
                                </button>
                            </form>

                            <form method="post" action="controllers/friends_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="decline">
                                <input type="hidden" name="friendship_id" value="<?= $friendshipId ?>">
                                <input type="hidden" name="return_tab" value="find">
                                <button class="btn btn-outline" type="submit"
                                        onclick="return confirm('Decline this friend request?');">
                                    Decline
                                </button>
                            </form>
                        <?php endif; ?>

                    <?php elseif ($state === 'friends'): ?>
                        <span class="rc-badge na">
                            Friends
                        </span>

                        <?php if ($friendshipId): ?>
                            <form method="post" action="controllers/friends_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="friendship_id" value="<?= $friendshipId ?>">
                                <input type="hidden" name="return_tab" value="find">
                                <button class="btn btn-outline" type="submit"
                                        onclick="return confirm('Remove this friend centre?');">
                                    Remove
                                </button>
                            </form>
                        <?php endif; ?>

                    <?php elseif ($state === 'blocked'): ?>
                        <span class="rc-badge na">
                            Blocked
                        </span>
                    <?php endif; ?>

                    <?php if ($state !== 'blocked' && $friendshipId): ?>
                        <form method="post" action="controllers/friends_handler.php" style="margin:0;">
                            <input type="hidden" name="action" value="block">
                            <input type="hidden" name="friendship_id" value="<?= $friendshipId ?>">
                            <input type="hidden" name="return_tab" value="find">
                            <button class="btn btn-outline" type="submit"
                                    onclick="return confirm('Block this centre?');">
                                Block
                            </button>
                        </form>
                    <?php endif; ?>

                </div>
            </div>

        <?php endforeach; ?>
    </div>

<?php endif; ?>

