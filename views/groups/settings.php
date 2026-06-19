<?php
// views/groups/settings.php
// Network Settings tab inside viewnetwork.php
// Clean + uncluttered: members list + invite autocomplete + leave network
// Uses controllers/network_search_centres.php for autocomplete
// Posts actions to controllers/groups_handler.php

if (!defined('APP_LOADED')) {
    echo '<div class="rc-alert red"><strong>Error</strong><br>APP_LOADED not defined.</div>';
    return;
}

if (!isset($pdo)) {
    echo '<div class="rc-alert red"><strong>Error</strong><br>Database connection not available.</div>';
    return;
}

if (!isset($network_id) || (int)$network_id <= 0) {
    echo '<div class="rc-alert red"><strong>Error</strong><br>Network context missing.</div>';
    return;
}

$gid = (int)$network_id;

// Centre context (available via getcentreinfo.php / globals)
$currentCentreId = 0;
if (isset($centre_id) && (int)$centre_id > 0) $currentCentreId = (int)$centre_id;
elseif (isset($rescue_id) && (int)$rescue_id > 0) $currentCentreId = (int)$rescue_id;

if ($currentCentreId <= 0) {
    echo '<div class="rc-alert red"><strong>Error</strong><br>Centre context missing.</div>';
    return;
}

$isAdmin = !empty($network_is_admin);

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

// Load active members + counts
$members = [];
$adminCount = 0;
$memberCount = 0;
$errorMsg = null;

try {
    $stmt = $pdo->prepare("
        SELECT
            gm.group_member_id,
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
    $stmt->execute([':gid' => $gid]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN role='admin' AND status='active' THEN 1 ELSE 0 END) AS admins,
            SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) AS members
        FROM rescue_group_members
        WHERE group_id = :gid
    ");
    $stmt->execute([':gid' => $gid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $adminCount  = (int)($row['admins'] ?? 0);
    $memberCount = (int)($row['members'] ?? 0);

} catch (Throwable $e) {
    $errorMsg = $e->getMessage();
}
?>

<?php if ($errorMsg): ?>
    <div class="rc-alert red">
        <strong>Network Settings: load error</strong><br>
        <?= htmlspecialchars($errorMsg) ?>
    </div>
    <?php return; ?>
<?php endif; ?>

<div class="rc-stack">
    <div class="rc-alert purple">
        <strong>Network Settings</strong><br>
        Manage members, invitations and admin roles.
    </div>

<div class="rc-card-grid">

    <!-- =========================
         MEMBERS
    ========================= -->
    <div class="rc-panel rc-stack" style="grid-column: span 2;">
        <div class="rc-split-head">
            <h4>Members</h4>
            <div class="rc-muted">
                <?= (int)$memberCount ?> total • <?= (int)$adminCount ?> admin<?= $adminCount === 1 ? '' : 's' ?>
            </div>
        </div>

        <?php if (empty($members)): ?>
            <div class="rc-alert amber">
                No members found.
            </div>
        <?php else: ?>
            <div class="rc-list">
                <?php foreach ($members as $m): ?>
                    <?php
                        $name = $m['rescue_name'] ?? 'Unknown Centre';
                        $img  = rc_img_src($m['centre_profile_image'] ?? '');
                        $ini  = rc_initials($name);
                        $role = $m['role'] ?? 'member';
                        $isMe = ((int)$m['centre_id'] === $currentCentreId);
                    ?>
                    <div class="rc-item">
                            <div style="width:40px; height:40px; border-radius:50%; overflow:hidden; border:1px solid var(--rc-border); display:flex; align-items:center; justify-content:center; background:var(--rc-surface); flex:0 0 40px;">
                                <?php if ($img): ?>
                                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($name) ?>"
                                         style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none';">
                                <?php else: ?>
                                    <div style="font-weight:800; font-size:12px;"><?= htmlspecialchars($ini) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="rc-item-main">
                                <strong>
                                    <?= htmlspecialchars($name) ?><?= $isMe ? ' (You)' : '' ?>
                                </strong>
                                <small class="rc-muted">
                                    Role: <?= htmlspecialchars(ucfirst($role)) ?>
                                </small>
                            </div>

                            <?php if ($isAdmin && !$isMe): ?>
                                <div class="rc-actions">
                                    <?php if ($role !== 'admin'): ?>
                                        <form method="post" action="controllers/groups_handler.php">
                                            <input type="hidden" name="action" value="set_member_role">
                                            <input type="hidden" name="group_id" value="<?= (int)$gid ?>">
                                            <input type="hidden" name="network_id" value="<?= (int)$gid ?>">
                                            <input type="hidden" name="target_member_id" value="<?= (int)$m['group_member_id'] ?>">
                                            <input type="hidden" name="new_role" value="admin">
                                            <input type="hidden" name="return_tab" value="settings">
                                            <button class="btn grey" type="submit">Make admin</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="controllers/groups_handler.php">
                                            <input type="hidden" name="action" value="set_member_role">
                                            <input type="hidden" name="group_id" value="<?= (int)$gid ?>">
                                            <input type="hidden" name="network_id" value="<?= (int)$gid ?>">
                                            <input type="hidden" name="target_member_id" value="<?= (int)$m['group_member_id'] ?>">
                                            <input type="hidden" name="new_role" value="member">
                                            <input type="hidden" name="return_tab" value="settings">
                                            <button class="btn grey" type="submit">Remove admin</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" action="controllers/groups_handler.php">
                                        <input type="hidden" name="action" value="remove_member">
                                        <input type="hidden" name="group_id" value="<?= (int)$gid ?>">
                                        <input type="hidden" name="network_id" value="<?= (int)$gid ?>">
                                        <input type="hidden" name="target_member_id" value="<?= (int)$m['group_member_id'] ?>">
                                        <input type="hidden" name="return_tab" value="settings">
                                        <button class="btn red" type="submit"
                                                onclick="return confirm('Remove this centre from the network?');">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- =========================
         INVITE + LEAVE
    ========================= -->
    <div class="rc-stack">

        <!-- Invite centre (admin only) -->
        <div class="rc-panel rc-stack">
            <h4>Invite a centre</h4>

            <?php if (!$isAdmin): ?>
                <div class="rc-alert amber">
                    Only network admins can invite centres.
                </div>
            <?php else: ?>
                <div class="rc-muted">
                    Type a centre name, select it, then click <strong>Invite</strong>.
                </div>

                <div style="position:relative;">
                    <input
                        type="text"
                        id="inviteCentreSearch"
                        class="xform-input"
                        placeholder="Search centres..."
                        autocomplete="off"
                    >
                    <div id="inviteCentreResults"
                         class="rc-card"
                         style="display:none; position:absolute; left:0; right:0; top:100%; z-index:50; margin-top:6px; overflow:hidden; max-height:240px; overflow-y:auto;">
                    </div>
                </div>

                <form method="post" action="controllers/groups_handler.php">
                    <input type="hidden" name="action" value="invite_centre">
                    <input type="hidden" name="group_id" value="<?= (int)$gid ?>">
                    <input type="hidden" name="network_id" value="<?= (int)$gid ?>">
                    <input type="hidden" name="target_centre_id" id="inviteTargetCentreId" value="">
                    <input type="hidden" name="return_tab" value="settings">

                    <div class="rc-actions">
                        <button type="submit" class="btn blue"
                                onclick="if(!document.getElementById('inviteTargetCentreId').value){alert('Select a centre from the list first.'); return false;} return confirm('Send invitation to this centre?');">
                            Invite
                        </button>

                        <span class="rc-muted" id="inviteSelectedCentre">
                            No centre selected
                        </span>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- Leave network -->
        <div class="rc-panel rc-stack">
            <h4>Leave network</h4>

            <?php if ($isAdmin && $adminCount <= 1 && $memberCount > 1): ?>
                <div class="rc-alert amber">
                    You are the only admin. Promote another admin before leaving.
                </div>
            <?php else: ?>
                <div class="rc-muted">
                    Leaving will remove your centre from this network.
                </div>

                <form method="post" action="controllers/groups_handler.php">
                    <input type="hidden" name="action" value="leave_network">
                    <input type="hidden" name="group_id" value="<?= (int)$gid ?>">
                    <input type="hidden" name="network_id" value="<?= (int)$gid ?>">
                    <input type="hidden" name="return_tab" value="mynetworks">
                    <button class="btn red" type="submit"
                            onclick="return confirm('Leave this network?');">
                        Leave Network
                    </button>
                </form>
            <?php endif; ?>
        </div>

    </div>
</div>
</div>

<?php if ($isAdmin): ?>
<script>
(function() {
    const input = document.getElementById('inviteCentreSearch');
    const results = document.getElementById('inviteCentreResults');
    const hiddenId = document.getElementById('inviteTargetCentreId');
    const selectedTxt = document.getElementById('inviteSelectedCentre');

    const networkId = <?= (int)$gid ?>;
    let timer = null;

    function escHtml(s) {
        return String(s).replace(/[&<>"']/g, function(m) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]);
        });
    }

    function imgSrc(p) {
        if (!p) return '';
        p = String(p).trim();
        if (!p) return '';
        if (p.startsWith('http://') || p.startsWith('https://')) return p;
        if (p.startsWith('/')) return p;
        return '/' + p;
    }

    function hideResults() {
        results.style.display = 'none';
        results.innerHTML = '';
    }

    function showResults(items) {
        if (!items || items.length === 0) {
            results.innerHTML = '<div class="rc-muted" style="padding:10px;">No centres found.</div>';
            results.style.display = 'block';
            return;
        }

        results.innerHTML = items.map(item => {
            const img = imgSrc(item.image);
            const avatar = img
                ? `<img src="${escHtml(img)}" style="width:30px;height:30px;border-radius:50%;object-fit:cover;border:1px solid var(--rc-border);" onerror="this.style.display='none';">`
                : `<div style="width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--rc-surface-muted);border:1px solid var(--rc-border);font-weight:800;font-size:11px;">RC</div>`;

            return `
                <button type="button"
                        data-id="${item.id}"
                        data-name="${escHtml(item.name)}"
                        style="width:100%; text-align:left; border:0; border-bottom:1px solid var(--rc-border); background:var(--rc-surface); color:var(--rc-text); padding:10px; display:flex; gap:10px; align-items:center; cursor:pointer;">
                    ${avatar}
                    <div style="font-weight:700; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        ${escHtml(item.name)}
                    </div>
                </button>
            `;
        }).join('');

        results.style.display = 'block';

        results.querySelectorAll('button[data-id]').forEach(btn => {
            btn.addEventListener('click', () => {
                hiddenId.value = btn.getAttribute('data-id');
                selectedTxt.textContent = btn.getAttribute('data-name');
                input.value = btn.getAttribute('data-name');
                hideResults();
            });
        });
    }

    async function runSearch(q) {
        const url = `controllers/network_search_centres.php?network_id=${networkId}&q=${encodeURIComponent(q)}&limit=12`;
        const res = await fetch(url, { credentials: 'same-origin' });
        const data = await res.json();
        if (!data || !data.ok) {
            showResults([]);
            return;
        }
        showResults(data.items || []);
    }

    input.addEventListener('input', () => {
        const q = input.value.trim();
        hiddenId.value = '';
        selectedTxt.textContent = 'No centre selected';

        if (timer) clearTimeout(timer);

        if (q.length < 2) {
            hideResults();
            return;
        }

        timer = setTimeout(() => runSearch(q), 200);
    });

    document.addEventListener('click', (e) => {
        if (!results.contains(e.target) && e.target !== input) {
            hideResults();
        }
    });
})();
</script>
<?php endif; ?>

