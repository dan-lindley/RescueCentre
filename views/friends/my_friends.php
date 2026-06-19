<?php
// views/friends/my_friends.php
// Lists approved centre friends + approved vet-practice relationships for the current centre

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

$friends = [];
$vetFriends = [];
$errorMsg = null;
$vetErrorMsg = null;

try {
    $sql = "
        SELECT
            f.friendship_id,
            f.responded_at,
            CASE
                WHEN f.centre_a_id = :cid THEN f.centre_b_id
                ELSE f.centre_a_id
            END AS friend_centre_id,
            c.rescue_name AS friend_name,
            m.centre_profile_image AS friend_profile_image
        FROM rescue_centre_friends f
        JOIN rescue_centres c
          ON c.rescue_id = CASE
                WHEN f.centre_a_id = :cid THEN f.centre_b_id
                ELSE f.centre_a_id
             END
        LEFT JOIN rescue_centre_meta m
          ON m.centre_id = c.rescue_id
        WHERE f.status = 'approved'
          AND (:cid IN (f.centre_a_id, f.centre_b_id))
        ORDER BY c.rescue_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cid' => $currentCentreId]);
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $errorMsg = $e->getMessage();
}

try {
    $vetSql = "
        SELECT
            rvc.rel_id,
            rvc.approved_at,
            rv.practice_id,
            rv.practice_name,
            rv.practice_tel,
            rv.practice_email,
            rv.city,
            rv.county
        FROM rescue_vet_centres rvc
        JOIN rescue_vets rv
          ON rv.practice_id = rvc.practice_id
        WHERE rvc.centre_id = :cid
          AND rvc.status = 'approved'
        ORDER BY rv.practice_name ASC
    ";

    $vetStmt = $pdo->prepare($vetSql);
    $vetStmt->execute([':cid' => $currentCentreId]);
    $vetFriends = $vetStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $vetErrorMsg = $e->getMessage();
}
?>

<?php if ($errorMsg): ?>
    <div class="alert-box alert-red" style="margin-bottom:12px;">
        <strong>My Friends: load error</strong><br>
        <?= htmlspecialchars($errorMsg) ?>
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="alert-box alert-purple" style="margin-bottom:12px;">
    <strong>My Friends</strong><br>
    You have <?= (int)count($friends) ?> approved friend<?= count($friends) === 1 ? '' : 's' ?>.
</div>

<?php if (empty($friends)): ?>

    <div class="alert-box alert-purple" style="margin-bottom:12px;">
        <strong>No friends to show</strong><br>
        Your centre doesn’t have any approved friends yet.
    </div>

    <div style="margin-top:10px; margin-bottom:18px;">
        <a class="btn btn-primary" href="friends.php?tab=find">Find a Centre</a>
        <a class="btn btn-outline" href="friends.php?tab=requests" style="margin-left:6px;">View Requests</a>
    </div>

<?php else: ?>

    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:12px; margin-bottom:18px;">
        <?php foreach ($friends as $fr): ?>
            <?php
                $friendName   = $fr['friend_name'] ?? 'Unknown Centre';
                $imgSrc       = rc_img_src($fr['friend_profile_image'] ?? '');
                $initials     = rc_initials($friendName);
                $since        = !empty($fr['responded_at']) ? date('d M Y', strtotime($fr['responded_at'])) : '';
                $friendshipId = (int)$fr['friendship_id'];
            ?>

            <div style="border:1px solid #eee; border-radius:12px; padding:12px; background:#fff;">
                <div style="display:flex; gap:12px; align-items:center;">
                    <div style="width:54px; height:54px; border-radius:50%; overflow:hidden; flex:0 0 54px; border:1px solid #eee; display:flex; align-items:center; justify-content:center; background:#f7f7fb;">
                        <?php if ($imgSrc): ?>
                            <img src="<?= htmlspecialchars($imgSrc) ?>"
                                 alt="<?= htmlspecialchars($friendName) ?>"
                                 style="width:100%; height:100%; object-fit:cover;"
                                 onerror="this.style.display='none'; this.parentNode.innerHTML = '<div style=&quot;font-weight:700;font-size:14px;&quot;><?= htmlspecialchars($initials) ?></div>';">
                        <?php else: ?>
                            <div style="font-weight:700; font-size:14px;"><?= htmlspecialchars($initials) ?></div>
                        <?php endif; ?>
                    </div>

                    <div style="flex:1;">
                        <div style="font-weight:700; font-size:15px; line-height:1.2;">
                            <?= htmlspecialchars($friendName) ?>
                        </div>
                        <?php if ($since): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">
                                Friends since <?= htmlspecialchars($since) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; align-items:center;">
                    <form method="post" action="controllers/friends_handler.php" style="margin:0;">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="friendship_id" value="<?= $friendshipId ?>">
                        <input type="hidden" name="return_tab" value="myfriends">
                        <button class="btn btn-outline" type="submit"
                                onclick="return confirm('Remove this friend centre?');">
                            Remove
                        </button>
                    </form>

                    <form method="post" action="controllers/friends_handler.php" style="margin:0;">
                        <input type="hidden" name="action" value="block">
                        <input type="hidden" name="friendship_id" value="<?= $friendshipId ?>">
                        <input type="hidden" name="return_tab" value="myfriends">
                        <button class="btn btn-outline" type="submit"
                                onclick="return confirm('Block this centre? This will prevent future requests.');">
                            Block
                        </button>
                    </form>
                </div>
            </div>

        <?php endforeach; ?>
    </div>

<?php endif; ?>


<div class="alert-box alert-green" style="margin-bottom:12px;">
    <strong>Vet Practices</strong><br>
    You are friends with <?= (int)count($vetFriends) ?> vet practice<?= count($vetFriends) === 1 ? '' : 's' ?>.
</div>

<?php if ($vetErrorMsg): ?>

    <div class="alert-box alert-red" style="margin-bottom:12px;">
        <strong>Vet Practices: load error</strong><br>
        <?= htmlspecialchars($vetErrorMsg) ?>
    </div>

<?php elseif (empty($vetFriends)): ?>

    <div style="border:1px solid #eee; border-radius:12px; padding:12px; background:#fff;">
        No approved vet practices to show yet.
    </div>

<?php else: ?>

    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:12px;">
        <?php foreach ($vetFriends as $vf): ?>
            <?php
                $practiceName = $vf['practice_name'] ?? 'Unknown Practice';
                $initials     = rc_initials($practiceName);
                $since        = !empty($vf['approved_at']) ? date('d M Y', strtotime($vf['approved_at'])) : '';
                $tel          = trim((string)($vf['practice_tel'] ?? ''));
                $email        = trim((string)($vf['practice_email'] ?? ''));
                $city         = trim((string)($vf['city'] ?? ''));
                $county       = trim((string)($vf['county'] ?? ''));
                $locationBits = array_filter([$city, $county], fn($v) => $v !== '');
            ?>

            <div style="border:1px solid #eee; border-radius:12px; padding:12px; background:#fff;">
                <div style="display:flex; gap:12px; align-items:center;">
                    <div style="width:54px; height:54px; border-radius:50%; overflow:hidden; flex:0 0 54px; border:1px solid #eee; display:flex; align-items:center; justify-content:center; background:#f7f7fb;">
                        <div style="font-weight:700; font-size:14px;"><?= htmlspecialchars($initials) ?></div>
                    </div>

                    <div style="flex:1;">
                        <div style="font-weight:700; font-size:15px; line-height:1.2;">
                            <?= htmlspecialchars($practiceName) ?>
                        </div>

                        <?php if ($since): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">
                                Friends since <?= htmlspecialchars($since) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($locationBits)): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">
                                <?= htmlspecialchars(implode(', ', $locationBits)) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($tel !== ''): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">
                                Tel: <?= htmlspecialchars($tel) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($email !== ''): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">
                                Email: <?= htmlspecialchars($email) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>
    </div>

<?php endif; ?>
