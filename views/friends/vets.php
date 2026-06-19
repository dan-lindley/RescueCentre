<?php
// views/friends/vets.php
// Directory of vet practices with relationship status + POST actions to controllers/vet_handler.php

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

function rv_initials(string $name): string {
    $name = trim($name);
    if ($name === '') return 'VP';
    $parts = preg_split('/\s+/', $name);
    $a = strtoupper(substr($parts[0] ?? 'V', 0, 1));
    $b = strtoupper(substr($parts[1] ?? 'P', 0, 1));
    return substr($a . $b, 0, 2);
}

$q = trim($_GET['q'] ?? '');
$qLike = '%' . $q . '%';

$vets = [];
$errorMsg = null;

try {
    $sql = "
        SELECT
            v.practice_id,
            v.practice_name,
            v.practice_tel,
            v.practice_email,
            v.practice_website,
            v.city,
            v.county,
            v.status AS practice_status,

            rvc.rel_id,
            rvc.status AS rel_status,
            rvc.requested_by_side,
            rvc.requested_by_account_id,
            rvc.requested_at,
            rvc.approved_at,
            rvc.revoked_at

        FROM rescue_vets v
        LEFT JOIN rescue_vet_centres rvc
            ON rvc.centre_id = :cid
           AND rvc.practice_id = v.practice_id

        WHERE v.status = 'Active'
          AND (
                :q = ''
                OR v.practice_name LIKE :qlike
                OR COALESCE(v.city, '') LIKE :qlike
                OR COALESCE(v.county, '') LIKE :qlike
              )
        ORDER BY v.practice_name ASC
        LIMIT 200
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':cid' => $currentCentreId,
        ':q' => $q,
        ':qlike' => $qLike,
    ]);

    $vets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $errorMsg = $e->getMessage();
}
?>

<?php if ($errorMsg): ?>
    <div class="alert-box alert-red" style="margin-bottom: 12px;">
        <strong>Find a Vet Practice: load error</strong><br>
        <?= htmlspecialchars($errorMsg) ?>
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="alert-box alert-green" style="margin-bottom: 12px;">
    <strong>Find a Vet Practice</strong><br>
    Search for a veterinary practice and send a connection request.
</div>

<form method="get" action="friends.php" style="margin-bottom: 12px;">
    <input type="hidden" name="tab" value="vets">
    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <input
            type="text"
            name="q"
            value="<?= htmlspecialchars($q) ?>"
            placeholder="Search vet practices by name or location..."
            class="xform-input"
            style="max-width:360px;"
        >
        <button class="btn green" type="submit">Search</button>

        <?php if ($q !== ''): ?>
            <a class="btn btn-outline" href="friends.php?tab=vets">Clear</a>
        <?php endif; ?>
    </div>
</form>

<?php if (empty($vets)): ?>
    <div class="alert-box alert-amber" style="margin-bottom: 12px;">
        No vet practices found<?= $q !== '' ? ' for &quot;' . htmlspecialchars($q) . '&quot;' : '' ?>.
    </div>
<?php else: ?>

    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap:12px;">
        <?php foreach ($vets as $v): ?>
            <?php
                $practiceName = $v['practice_name'] ?? 'Unknown Practice';
                $initials = rv_initials($practiceName);
                $relId = !empty($v['rel_id']) ? (int)$v['rel_id'] : 0;
                $status = $v['rel_status'] ?? null;
                $requestedBySide = (string)($v['requested_by_side'] ?? '');
                $tel = trim((string)($v['practice_tel'] ?? ''));
                $email = trim((string)($v['practice_email'] ?? ''));
                $website = trim((string)($v['practice_website'] ?? ''));
                $city = trim((string)($v['city'] ?? ''));
                $county = trim((string)($v['county'] ?? ''));
                $locationBits = array_filter([$city, $county], fn($x) => $x !== '');

                $state = 'none';
                if ($status === 'approved') {
                    $state = 'friends';
                } elseif ($status === 'pending') {
                    $state = ($requestedBySide === 'centre') ? 'pending_sent' : 'pending_received';
                } elseif ($status === 'revoked') {
                    $state = 'revoked';
                } elseif ($status === 'declined') {
                    $state = 'declined';
                }
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

                        <?php if ($state === 'friends'): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">Connected</div>
                        <?php elseif ($state === 'pending_sent'): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">Request sent (pending)</div>
                        <?php elseif ($state === 'pending_received'): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">Incoming request</div>
                        <?php elseif ($state === 'declined'): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">Previously declined</div>
                        <?php elseif ($state === 'revoked'): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">Previously removed</div>
                        <?php else: ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">Not connected</div>
                        <?php endif; ?>

                        <?php if (!empty($locationBits)): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">
                                <?= htmlspecialchars(implode(', ', $locationBits)) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($tel !== ''): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">Tel: <?= htmlspecialchars($tel) ?></div>
                        <?php endif; ?>

                        <?php if ($email !== ''): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">Email: <?= htmlspecialchars($email) ?></div>
                        <?php endif; ?>

                        <?php if ($website !== ''): ?>
                            <div class="muted" style="font-size:13px; margin-top:4px;">Website: <?= htmlspecialchars($website) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; align-items:center;">

                    <?php if ($state === 'none' || $state === 'declined' || $state === 'revoked'): ?>
                        <form method="post" action="controllers/vet_handler.php" style="margin:0;">
                            <input type="hidden" name="action" value="request">
                            <input type="hidden" name="practice_id" value="<?= (int)$v['practice_id'] ?>">
                            <input type="hidden" name="return_tab" value="vets">
                            <button class="btn green" type="submit"
                                    onclick="return confirm('Send a connection request to <?= htmlspecialchars($practiceName) ?>?');">
                                Request Connection
                            </button>
                        </form>

                    <?php elseif ($state === 'pending_sent'): ?>
                        <span class="rc-badge na">
                            Request Sent
                        </span>

                        <?php if ($relId): ?>
                            <form method="post" action="controllers/vet_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="rel_id" value="<?= $relId ?>">
                                <input type="hidden" name="return_tab" value="vets">
                                <button class="btn btn-outline" type="submit"
                                        onclick="return confirm('Cancel this connection request?');">
                                    Cancel
                                </button>
                            </form>
                        <?php endif; ?>

                    <?php elseif ($state === 'pending_received'): ?>
                        <?php if ($relId): ?>
                            <form method="post" action="controllers/vet_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="rel_id" value="<?= $relId ?>">
                                <input type="hidden" name="return_tab" value="vets">
                                <button class="btn green" type="submit"
                                        onclick="return confirm('Approve this connection request?');">
                                    Approve
                                </button>
                            </form>

                            <form method="post" action="controllers/vet_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="decline">
                                <input type="hidden" name="rel_id" value="<?= $relId ?>">
                                <input type="hidden" name="return_tab" value="vets">
                                <button class="btn btn-outline" type="submit"
                                        onclick="return confirm('Decline this connection request?');">
                                    Decline
                                </button>
                            </form>
                        <?php endif; ?>

                    <?php elseif ($state === 'friends'): ?>
                        <span class="rc-badge na">
                            Connected
                        </span>

                        <?php if ($relId): ?>
                            <form method="post" action="controllers/vet_handler.php" style="margin:0;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="rel_id" value="<?= $relId ?>">
                                <input type="hidden" name="return_tab" value="vets">
                                <button class="btn btn-outline" type="submit"
                                        onclick="return confirm('Remove this vet connection?');">
                                    Remove
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

