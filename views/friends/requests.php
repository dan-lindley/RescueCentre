<?php
// views/friends/requests.php
// Incoming + outgoing centre friend requests
// PLUS incoming + outgoing vet practice requests

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

$incoming = [];
$outgoing = [];
$vetIncoming = [];
$vetOutgoing = [];
$errorMsg = null;
$vetErrorMsg = null;

try {
    // -----------------------------------------
    // CENTRE FRIEND REQUESTS - INCOMING
    // -----------------------------------------
    $sqlIncoming = "
        SELECT
            f.friendship_id,
            f.requested_at,
            f.requested_by_centre_id,
            CASE
                WHEN f.centre_a_id = :cid THEN f.centre_b_id
                ELSE f.centre_a_id
            END AS other_centre_id,
            c.rescue_name AS other_name,
            m.centre_profile_image AS other_profile_image
        FROM rescue_centre_friends f
        JOIN rescue_centres c
          ON c.rescue_id = CASE
                WHEN f.centre_a_id = :cid THEN f.centre_b_id
                ELSE f.centre_a_id
             END
        LEFT JOIN rescue_centre_meta m
          ON m.centre_id = c.rescue_id
        WHERE f.status = 'pending'
          AND (:cid IN (f.centre_a_id, f.centre_b_id))
          AND f.requested_by_centre_id <> :cid
        ORDER BY f.requested_at DESC
    ";

    $stmt = $pdo->prepare($sqlIncoming);
    $stmt->execute([':cid' => $currentCentreId]);
    $incoming = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // -----------------------------------------
    // CENTRE FRIEND REQUESTS - OUTGOING
    // -----------------------------------------
    $sqlOutgoing = "
        SELECT
            f.friendship_id,
            f.requested_at,
            f.requested_by_centre_id,
            CASE
                WHEN f.centre_a_id = :cid THEN f.centre_b_id
                ELSE f.centre_a_id
            END AS other_centre_id,
            c.rescue_name AS other_name,
            m.centre_profile_image AS other_profile_image
        FROM rescue_centre_friends f
        JOIN rescue_centres c
          ON c.rescue_id = CASE
                WHEN f.centre_a_id = :cid THEN f.centre_b_id
                ELSE f.centre_a_id
             END
        LEFT JOIN rescue_centre_meta m
          ON m.centre_id = c.rescue_id
        WHERE f.status = 'pending'
          AND (:cid IN (f.centre_a_id, f.centre_b_id))
          AND f.requested_by_centre_id = :cid
        ORDER BY f.requested_at DESC
    ";

    $stmt = $pdo->prepare($sqlOutgoing);
    $stmt->execute([':cid' => $currentCentreId]);
    $outgoing = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $errorMsg = $e->getMessage();
}

try {
    // -----------------------------------------
    // VET REQUESTS - INCOMING
    // Requested by vet side, waiting on centre
    // -----------------------------------------
    $sqlVetIncoming = "
        SELECT
            rvc.rel_id,
            rvc.requested_at,
            rvc.requested_by_side,
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
          AND rvc.status = 'pending'
          AND rvc.requested_by_side = 'vet'
        ORDER BY rvc.requested_at DESC
    ";

    $vetStmt = $pdo->prepare($sqlVetIncoming);
    $vetStmt->execute([':cid' => $currentCentreId]);
    $vetIncoming = $vetStmt->fetchAll(PDO::FETCH_ASSOC);

    // -----------------------------------------
    // VET REQUESTS - OUTGOING
    // Requested by centre side, waiting on vet
    // -----------------------------------------
    $sqlVetOutgoing = "
        SELECT
            rvc.rel_id,
            rvc.requested_at,
            rvc.requested_by_side,
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
          AND rvc.status = 'pending'
          AND rvc.requested_by_side = 'centre'
        ORDER BY rvc.requested_at DESC
    ";

    $vetStmt = $pdo->prepare($sqlVetOutgoing);
    $vetStmt->execute([':cid' => $currentCentreId]);
    $vetOutgoing = $vetStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $vetErrorMsg = $e->getMessage();
}
?>

<?php if ($errorMsg): ?>
    <div class="alert-box alert-red" style="margin-bottom: 12px;">
        <strong>Friend Requests: load error</strong><br>
        <?= htmlspecialchars($errorMsg) ?>
    </div>
<?php endif; ?>

<?php if ($vetErrorMsg): ?>
    <div class="alert-box alert-red" style="margin-bottom: 12px;">
        <strong>Vet Requests: load error</strong><br>
        <?= htmlspecialchars($vetErrorMsg) ?>
    </div>
<?php endif; ?>

<!-- =========================
     INCOMING CENTRE REQUESTS
========================= -->
<div class="alert-box alert-purple" style="margin-bottom: 12px;">
    <strong>Incoming Requests</strong><br>
    Centres that want to connect with you.
</div>

<?php if (empty($incoming)): ?>
    <div class="alert-box alert-amber" style="margin-bottom: 12px;">
        No incoming friend requests right now.
    </div>
<?php else: ?>

    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:12px; margin-bottom: 18px;">
        <?php foreach ($incoming as $r): ?>
            <?php
                $name = $r['other_name'] ?? 'Unknown Centre';
                $img = rc_img_src($r['other_profile_image'] ?? '');
                $initials = rc_initials($name);
                $date = !empty($r['requested_at']) ? date('d M Y H:i', strtotime($r['requested_at'])) : '';
            ?>

            <div style="border:1px solid #eee; border-radius:12px; padding:12px; background:#fff;">
                <div style="display:flex; gap:12px; align-items:center;">
                    <div style="width:54px; height:54px; border-radius:50%; overflow:hidden; flex:0 0 54px; border:1px solid #eee; display:flex; align-items:center; justify-content:center; background:#f7f7fb;">
                        <?php if ($img): ?>
                            <img src="<?= htmlspecialchars($img) ?>"
                                 alt="<?= htmlspecialchars($name) ?>"
                                 style="width:100%; height:100%; object-fit:cover;"
                                 onerror="this.style.display='none';">
                        <?php else: ?>
                            <div style="font-weight:700; font-size:14px;"><?= htmlspecialchars($initials) ?></div>
                        <?php endif; ?>
                    </div>

                    <div style="flex:1;">
                        <div style="font-weight:700; font-size:15px; line-height:1.2;">
                            <?= htmlspecialchars($name) ?>
                        </div>
                        <?php if ($date): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">
                                Requested <?= htmlspecialchars($date) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap;">
                    <form method="post" action="controllers/friends_handler.php" style="margin:0;">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="friendship_id" value="<?= (int)$r['friendship_id'] ?>">
                        <input type="hidden" name="return_tab" value="requests">
                        <button class="btn btn-primary" type="submit"
                                onclick="return confirm('Approve this friend request?');">
                            Approve
                        </button>
                    </form>

                    <form method="post" action="controllers/friends_handler.php" style="margin:0;">
                        <input type="hidden" name="action" value="decline">
                        <input type="hidden" name="friendship_id" value="<?= (int)$r['friendship_id'] ?>">
                        <input type="hidden" name="return_tab" value="requests">
                        <button class="btn btn-outline" type="submit"
                                onclick="return confirm('Decline this friend request?');">
                            Decline
                        </button>
                    </form>

                    <form method="post" action="controllers/friends_handler.php" style="margin:0;">
                        <input type="hidden" name="action" value="block">
                        <input type="hidden" name="friendship_id" value="<?= (int)$r['friendship_id'] ?>">
                        <input type="hidden" name="return_tab" value="requests">
                        <button class="btn btn-outline" type="submit"
                                onclick="return confirm('Block this centre?');">
                            Block
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<!-- =========================
     OUTGOING CENTRE REQUESTS
========================= -->
<div class="alert-box alert-purple" style="margin-bottom: 12px;">
    <strong>Outgoing Requests</strong><br>
    Requests you’ve sent that are awaiting a response.
</div>

<?php if (empty($outgoing)): ?>
    <div class="alert-box alert-amber" style="margin-bottom: 12px;">
        No outgoing friend requests right now.
    </div>
<?php else: ?>

    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:12px; margin-bottom:18px;">
        <?php foreach ($outgoing as $r): ?>
            <?php
                $name = $r['other_name'] ?? 'Unknown Centre';
                $img = rc_img_src($r['other_profile_image'] ?? '');
                $initials = rc_initials($name);
                $date = !empty($r['requested_at']) ? date('d M Y H:i', strtotime($r['requested_at'])) : '';
            ?>

            <div style="border:1px solid #eee; border-radius:12px; padding:12px; background:#fff;">
                <div style="display:flex; gap:12px; align-items:center;">
                    <div style="width:54px; height:54px; border-radius:50%; overflow:hidden; flex:0 0 54px; border:1px solid #eee; display:flex; align-items:center; justify-content:center; background:#f7f7fb;">
                        <?php if ($img): ?>
                            <img src="<?= htmlspecialchars($img) ?>"
                                 alt="<?= htmlspecialchars($name) ?>"
                                 style="width:100%; height:100%; object-fit:cover;"
                                 onerror="this.style.display='none';">
                        <?php else: ?>
                            <div style="font-weight:700; font-size:14px;"><?= htmlspecialchars($initials) ?></div>
                        <?php endif; ?>
                    </div>

                    <div style="flex:1;">
                        <div style="font-weight:700; font-size:15px; line-height:1.2;">
                            <?= htmlspecialchars($name) ?>
                        </div>
                        <?php if ($date): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">
                                Sent <?= htmlspecialchars($date) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; align-items:center;">
                    <span class="rc-badge na">
                        Pending
                    </span>

                    <form method="post" action="controllers/friends_handler.php" style="margin:0;">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="friendship_id" value="<?= (int)$r['friendship_id'] ?>">
                        <input type="hidden" name="return_tab" value="requests">
                        <button class="btn btn-outline" type="submit"
                                onclick="return confirm('Cancel this outgoing friend request?');">
                            Cancel
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<!-- =========================
     INCOMING VET REQUESTS
========================= -->
<div class="alert-box alert-green" style="margin-bottom: 12px;">
    <strong>Incoming Vet Requests</strong><br>
    Vet practices that want to connect with your centre.
</div>

<?php if (empty($vetIncoming)): ?>
    <div class="alert-box alert-amber" style="margin-bottom: 12px;">
        No incoming vet requests right now.
    </div>
<?php else: ?>

    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:12px; margin-bottom: 18px;">
        <?php foreach ($vetIncoming as $r): ?>
            <?php
                $name = $r['practice_name'] ?? 'Unknown Practice';
                $initials = rc_initials($name);
                $date = !empty($r['requested_at']) ? date('d M Y H:i', strtotime($r['requested_at'])) : '';
                $tel = trim((string)($r['practice_tel'] ?? ''));
                $email = trim((string)($r['practice_email'] ?? ''));
                $city = trim((string)($r['city'] ?? ''));
                $county = trim((string)($r['county'] ?? ''));
                $locationBits = array_filter([$city, $county], fn($v) => $v !== '');
            ?>

            <div style="border:1px solid #eee; border-radius:12px; padding:12px; background:#fff;">
                <div style="display:flex; gap:12px; align-items:center;">
                    <div style="width:54px; height:54px; border-radius:50%; overflow:hidden; flex:0 0 54px; border:1px solid #eee; display:flex; align-items:center; justify-content:center; background:#f7f7fb;">
                        <div style="font-weight:700; font-size:14px;"><?= htmlspecialchars($initials) ?></div>
                    </div>

                    <div style="flex:1;">
                        <div style="font-weight:700; font-size:15px; line-height:1.2;">
                            <?= htmlspecialchars($name) ?>
                        </div>

                        <?php if ($date): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">
                                Requested <?= htmlspecialchars($date) ?>
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

                <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap;">
                    <form method="post" action="controllers/vet_handler.php" style="margin:0;">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="rel_id" value="<?= (int)$r['rel_id'] ?>">
                        <input type="hidden" name="return_tab" value="requests">
                        <button class="btn btn-primary" type="submit"
                                onclick="return confirm('Approve this vet connection request?');">
                            Approve
                        </button>
                    </form>

                    <form method="post" action="controllers/vet_handler.php" style="margin:0;">
                        <input type="hidden" name="action" value="decline">
                        <input type="hidden" name="rel_id" value="<?= (int)$r['rel_id'] ?>">
                        <input type="hidden" name="return_tab" value="requests">
                        <button class="btn btn-outline" type="submit"
                                onclick="return confirm('Decline this vet connection request?');">
                            Decline
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<!-- =========================
     OUTGOING VET REQUESTS
========================= -->
<div class="alert-box alert-green" style="margin-bottom: 12px;">
    <strong>Outgoing Vet Requests</strong><br>
    Vet connection requests you’ve sent that are awaiting a response.
</div>

<?php if (empty($vetOutgoing)): ?>
    <div class="alert-box alert-amber" style="margin-bottom: 12px;">
        No outgoing vet requests right now.
    </div>
<?php else: ?>

    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:12px;">
        <?php foreach ($vetOutgoing as $r): ?>
            <?php
                $name = $r['practice_name'] ?? 'Unknown Practice';
                $initials = rc_initials($name);
                $date = !empty($r['requested_at']) ? date('d M Y H:i', strtotime($r['requested_at'])) : '';
                $tel = trim((string)($r['practice_tel'] ?? ''));
                $email = trim((string)($r['practice_email'] ?? ''));
                $city = trim((string)($r['city'] ?? ''));
                $county = trim((string)($r['county'] ?? ''));
                $locationBits = array_filter([$city, $county], fn($v) => $v !== '');
            ?>

            <div style="border:1px solid #eee; border-radius:12px; padding:12px; background:#fff;">
                <div style="display:flex; gap:12px; align-items:center;">
                    <div style="width:54px; height:54px; border-radius:50%; overflow:hidden; flex:0 0 54px; border:1px solid #eee; display:flex; align-items:center; justify-content:center; background:#f7f7fb;">
                        <div style="font-weight:700; font-size:14px;"><?= htmlspecialchars($initials) ?></div>
                    </div>

                    <div style="flex:1;">
                        <div style="font-weight:700; font-size:15px; line-height:1.2;">
                            <?= htmlspecialchars($name) ?>
                        </div>

                        <?php if ($date): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">
                                Sent <?= htmlspecialchars($date) ?>
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

                <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; align-items:center;">
                    <span class="rc-badge na">
                        Pending
                    </span>

                    <form method="post" action="controllers/vet_handler.php" style="margin:0;">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="rel_id" value="<?= (int)$r['rel_id'] ?>">
                        <input type="hidden" name="return_tab" value="requests">
                        <button class="btn btn-outline" type="submit"
                                onclick="return confirm('Cancel this outgoing vet request?');">
                            Cancel
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>
