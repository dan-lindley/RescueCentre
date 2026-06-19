<?php
// views/area.php  (repurposed as Locations Manager)

if (!defined('APP_LOADED')) exit;

echo '<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2>' . htmlspecialchars($lang['LOC_LOCATIONS_MANAGER']) . '</h2>
            <p>' . htmlspecialchars($lang['LOC_LOCATIONS_MANAGER_SUBTITLE']) . '</p>
        </div>
    </div>
</div>';

// ------------------------------------------------------------
// centre_id must come from getuserinfo globals/session
// ------------------------------------------------------------
$centre_id_int = isset($centre_id) ? (int)$centre_id : 0;
if ($centre_id_int <= 0) {
    echo '<div class="rc-alert red">' . htmlspecialchars($lang['LOC_CENTRE_CONTEXT_MISSING']) . '</div>';
    return;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

// Flash message from controller
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// ------------------------------------------------------------
// Helpers
// ------------------------------------------------------------
function norm_key(string $s): string {
    $s = trim($s);
    $s = preg_replace('/\s+/', ' ', $s);
    $s = mb_strtolower($s);
    return $s;
}

// ------------------------------------------------------------
// Summary counts
// ------------------------------------------------------------
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rescue_zones WHERE centre_id=:cid");
$stmt->execute([':cid' => $centre_id_int]);
$count_zones = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM rescue_areas WHERE centre_id=:cid");
$stmt->execute([':cid' => $centre_id_int]);
$count_areas = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM rescue_locations WHERE centre_id=:cid AND (deleted=0 OR deleted IS NULL)");
$stmt->execute([':cid' => $centre_id_int]);
$count_locations_active = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM rescue_locations WHERE centre_id=:cid AND deleted=1");
$stmt->execute([':cid' => $centre_id_int]);
$count_locations_deleted = (int)$stmt->fetchColumn();

// Missing area_id but has legacy text
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM rescue_locations
    WHERE centre_id=:cid
      AND (deleted=0 OR deleted IS NULL)
      AND (area_id IS NULL OR area_id=0)
      AND location_area IS NOT NULL
      AND TRIM(location_area) <> ''
");
$stmt->execute([':cid' => $centre_id_int]);
$count_legacy_text_only = (int)$stmt->fetchColumn();

// Broken area_id (points to non-existent area)
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM rescue_locations l
    LEFT JOIN rescue_areas a
      ON a.area_id = l.area_id AND a.centre_id = l.centre_id
    WHERE l.centre_id=:cid
      AND (l.deleted=0 OR l.deleted IS NULL)
      AND l.area_id IS NOT NULL AND l.area_id <> 0
      AND a.area_id IS NULL
");
$stmt->execute([':cid' => $centre_id_int]);
$count_broken_area_id = (int)$stmt->fetchColumn();

// Areas missing zone link
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM rescue_areas
    WHERE centre_id=:cid
      AND (zone_id IS NULL OR zone_id=0)
");
$stmt->execute([':cid' => $centre_id_int]);
$count_areas_no_zone = (int)$stmt->fetchColumn();

// Duplicate area names (risk for text-based auto-link)
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM (
        SELECT area_name
        FROM rescue_areas
        WHERE centre_id=:cid
        GROUP BY area_name
        HAVING COUNT(*) > 1
    ) x
");
$stmt->execute([':cid' => $centre_id_int]);
$count_area_dupe_names = (int)$stmt->fetchColumn();

// ------------------------------------------------------------
// Load reference data: zones, areas (with zone), for dropdowns + matching
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT z.zone_id, z.zone_name
    FROM rescue_zones z
    WHERE z.centre_id=:cid
    ORDER BY z.zone_name ASC
");
$stmt->execute([':cid' => $centre_id_int]);
$zones = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$stmt = $pdo->prepare("
    SELECT a.area_id, a.area_name, a.zone_id, z.zone_name
    FROM rescue_areas a
    LEFT JOIN rescue_zones z
      ON z.zone_id = a.zone_id AND z.centre_id = a.centre_id
    WHERE a.centre_id=:cid
    ORDER BY COALESCE(z.zone_name,''), a.area_name ASC
");
$stmt->execute([':cid' => $centre_id_int]);
$areas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// area_name -> list of areas (for matching)
$areasByName = [];
foreach ($areas as $a) {
    $k = norm_key((string)$a['area_name']);
    if (!isset($areasByName[$k])) $areasByName[$k] = [];
    $areasByName[$k][] = $a;
}

// ------------------------------------------------------------
// Deleted locations (bin)
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT l.location_id, l.location_name, l.location_type, l.max_occupancy,
           l.location_area, l.area_id,
           a.area_name AS area_name_from_id,
           a.zone_id,
           z.zone_name
    FROM rescue_locations l
    LEFT JOIN rescue_areas a
      ON a.area_id = l.area_id AND a.centre_id = l.centre_id
    LEFT JOIN rescue_zones z
      ON z.zone_id = a.zone_id AND z.centre_id = a.centre_id
    WHERE l.centre_id=:cid
      AND l.deleted=1
    ORDER BY l.location_name ASC
");
$stmt->execute([':cid' => $centre_id_int]);
$deleted_locations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// ------------------------------------------------------------
// Link repair: (A) legacy text-only, (B) broken area_id
// ------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT l.location_id, l.location_name, l.location_type, l.max_occupancy,
           l.location_area, l.area_id
    FROM rescue_locations l
    WHERE l.centre_id=:cid
      AND (l.deleted=0 OR l.deleted IS NULL)
      AND (l.area_id IS NULL OR l.area_id=0)
      AND l.location_area IS NOT NULL
      AND TRIM(l.location_area) <> ''
    ORDER BY l.location_area ASC, l.location_name ASC
");
$stmt->execute([':cid' => $centre_id_int]);
$legacy_needing_link = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$stmt = $pdo->prepare("
    SELECT l.location_id, l.location_name, l.location_type, l.max_occupancy,
           l.location_area, l.area_id
    FROM rescue_locations l
    LEFT JOIN rescue_areas a
      ON a.area_id = l.area_id AND a.centre_id = l.centre_id
    WHERE l.centre_id=:cid
      AND (l.deleted=0 OR l.deleted IS NULL)
      AND l.area_id IS NOT NULL AND l.area_id <> 0
      AND a.area_id IS NULL
    ORDER BY l.location_name ASC
");
$stmt->execute([':cid' => $centre_id_int]);
$broken_area_links = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Areas missing zone
$stmt = $pdo->prepare("
    SELECT area_id, area_name, zone_id
    FROM rescue_areas
    WHERE centre_id=:cid
      AND (zone_id IS NULL OR zone_id=0)
    ORDER BY area_name ASC
");
$stmt->execute([':cid' => $centre_id_int]);
$areas_missing_zone = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>

<div class="rc-panel">

    <?php if ($flash && is_array($flash)): ?>
        <div class="rc-alert <?php echo htmlspecialchars($flash['type'] ?? 'green'); ?>" style="margin-bottom:12px;">
            <?php echo htmlspecialchars($flash['message'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <div class="rc-alert green" style="margin-bottom:12px;">
        <strong><?= htmlspecialchars($lang['LOC_CENTRE_SUMMARY']) ?></strong><br>
        <?= htmlspecialchars($lang['LOC_ZONES']) ?>: <?php echo (int)$count_zones; ?> &middot;
        <?= htmlspecialchars($lang['LOC_AREAS']) ?>: <?php echo (int)$count_areas; ?> &middot;
        <?= htmlspecialchars($lang['LOC_ACTIVE_LOCATIONS']) ?>: <?php echo (int)$count_locations_active; ?> &middot;
        <?= htmlspecialchars($lang['LOC_DELETED_LOCATIONS']) ?>: <?php echo (int)$count_locations_deleted; ?><br>
        <?= htmlspecialchars($lang['LOC_NEEDS_ATTENTION']) ?> &mdash; <?= htmlspecialchars($lang['LOC_LEGACY_TEXT_ONLY']) ?>: <?php echo (int)$count_legacy_text_only; ?> &middot;
        <?= htmlspecialchars($lang['LOC_BROKEN_AREA_ID']) ?>: <?php echo (int)$count_broken_area_id; ?> &middot;
        <?= htmlspecialchars($lang['LOC_AREAS_MISSING_ZONE']) ?>: <?php echo (int)$count_areas_no_zone; ?> &middot;
        <?= htmlspecialchars($lang['LOC_DUPLICATE_AREA_NAMES']) ?>: <?php echo (int)$count_area_dupe_names; ?>
    </div>

</div>

<hr>

<!-- 1) Deleted Locations Bin -->
<div class="rc-panel">
    <h3><?= htmlspecialchars($lang['LOC_DELETED_LOCATIONS_BIN']) ?></h3>
    <p style="opacity:.8;"><?= htmlspecialchars($lang['LOC_DELETED_LOCATIONS_HELP']) ?></p>

    <table class="rc-table row-hover">
        <thead>
        <tr>
            <th><?= htmlspecialchars($lang['LOCATION']) ?></th>
            <th><?= htmlspecialchars($lang['DIET_TH_TYPE'] ?? 'Type') ?></th>
            <th><?= htmlspecialchars($lang['LOC_MAX']) ?></th>
            <th><?= htmlspecialchars($lang['LOC_LINK_STATE']) ?></th>
            <th style="width:240px;"><?= htmlspecialchars($lang['ACTIONS']) ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($deleted_locations)): ?>
            <?php foreach ($deleted_locations as $l): ?>
                <?php
                    $link_txt = '';
                    $aid = (int)($l['area_id'] ?? 0);
                    $legacy = trim((string)($l['location_area'] ?? ''));

                    if ($aid > 0 && !empty($l['area_name_from_id'])) {
                        $link_txt = 'ID→ ' . $l['area_name_from_id'] . (!empty($l['zone_name']) ? ' (Zone: ' . $l['zone_name'] . ')' : '');
                    } elseif ($legacy !== '') {
                        $link_txt = 'TEXT→ ' . $legacy;
                    } else {
                        $link_txt = 'Orphan (no area)';
                    }
                ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$l['location_name']); ?></td>
                    <td><?php echo htmlspecialchars((string)($l['location_type'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars((string)($l['max_occupancy'] ?? '')); ?></td>
                    <td style="opacity:.85;"><?php echo htmlspecialchars($link_txt); ?></td>
                    <td>
                        <form action="../controllers/locations_handler.php" method="post" class="xform" style="display:flex; gap:8px; align-items:center; margin:0;">
                            <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">
                            <input type="hidden" name="location_id" value="<?php echo (int)$l['location_id']; ?>">

                            <button type="submit" class="btn green" name="action" value="restore_location">
                                <?= htmlspecialchars($lang['LOC_RESTORE']) ?>
                            </button>

                            <button type="submit" class="btn red" name="action" value="hard_delete_location"
                                    onclick="return confirm(<?= htmlspecialchars(json_encode($lang['LOC_HARD_DELETE_CONFIRM']), ENT_QUOTES, 'UTF-8') ?>);">
                                <?= htmlspecialchars($lang['LOC_HARD_DELETE']) ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5"><?= htmlspecialchars($lang['LOC_NO_DELETED_LOCATIONS']) ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<hr>

<!-- 2) Link Repair: legacy text-only -->
<div class="rc-panel">
    <h3><?= htmlspecialchars($lang['LOC_REPAIR_LEGACY']) ?></h3>
    <p style="opacity:.8;"><?= htmlspecialchars($lang['LOC_REPAIR_LEGACY_HELP']) ?></p>

    <table class="rc-table row-hover">
        <thead>
        <tr>
            <th><?= htmlspecialchars($lang['LOCATION']) ?></th>
            <th><?= htmlspecialchars($lang['LOC_LEGACY_AREA_TEXT']) ?></th>
            <th><?= htmlspecialchars($lang['LOC_SUGGESTED_MATCH']) ?></th>
            <th style="width:260px;"><?= htmlspecialchars($lang['LOC_ASSIGN_AREA']) ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($legacy_needing_link)): ?>
            <?php foreach ($legacy_needing_link as $l): ?>
                <?php
                    $legacy = trim((string)$l['location_area']);
                    $k = norm_key($legacy);
                    $matches = $areasByName[$k] ?? [];
                    $suggest = '';

                    if (count($matches) === 1) {
                        $m = $matches[0];
                        $suggest = '✅ ' . $m['area_name'] . (!empty($m['zone_name']) ? ' (Zone: ' . $m['zone_name'] . ')' : '');
                    } elseif (count($matches) > 1) {
                        $suggest = '⚠️ Ambiguous (' . count($matches) . ' areas share this name)';
                    } else {
                        $suggest = '❌ No match found';
                    }
                ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$l['location_name']); ?></td>
                    <td><?php echo htmlspecialchars($legacy); ?></td>
                    <td style="opacity:.85;"><?php echo htmlspecialchars($suggest); ?></td>
                    <td>
                        <form action="../controllers/locations_handler.php" method="post" class="xform" style="display:flex; gap:8px; align-items:center; margin:0;">
                            <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">
                            <input type="hidden" name="location_id" value="<?php echo (int)$l['location_id']; ?>">

                            <select name="area_id" class="xform-input" required>
                                <option value=""><?= htmlspecialchars($lang['LOC_SELECT_AREA']) ?></option>
                                <?php foreach ($areas as $a): ?>
                                    <option value="<?php echo (int)$a['area_id']; ?>">
                                        <?php
                                            $label = (string)$a['area_name'];
                                            if (!empty($a['zone_name'])) $label .= ' — ' . $a['zone_name'];
                                            echo htmlspecialchars($label);
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="btn green" name="action" value="fix_location_link">
                                <?= htmlspecialchars($lang['LINK']) ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4"><?= htmlspecialchars($lang['LOC_NO_LEGACY_LOCATIONS']) ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<hr>

<!-- 3) Repair: broken area_id -->
<div class="rc-panel">
    <h3><?= htmlspecialchars($lang['LOC_REPAIR_BROKEN_AREA']) ?></h3>
    <p style="opacity:.8;"><?= htmlspecialchars($lang['LOC_REPAIR_BROKEN_AREA_HELP']) ?></p>

    <table class="rc-table row-hover">
        <thead>
        <tr>
            <th><?= htmlspecialchars($lang['LOCATION']) ?></th>
            <th><?= htmlspecialchars($lang['LOC_CURRENT_AREA_ID']) ?></th>
            <th><?= htmlspecialchars($lang['LOC_LEGACY_AREA_TEXT']) ?></th>
            <th style="width:260px;"><?= htmlspecialchars($lang['LOC_REASSIGN_AREA']) ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($broken_area_links)): ?>
            <?php foreach ($broken_area_links as $l): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$l['location_name']); ?></td>
                    <td><?php echo (int)$l['area_id']; ?></td>
                    <td><?php echo htmlspecialchars((string)($l['location_area'] ?? '')); ?></td>
                    <td>
                        <form action="../controllers/locations_handler.php" method="post" class="xform" style="display:flex; gap:8px; align-items:center; margin:0;">
                            <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">
                            <input type="hidden" name="location_id" value="<?php echo (int)$l['location_id']; ?>">

                            <select name="area_id" class="xform-input" required>
                                <option value=""><?= htmlspecialchars($lang['LOC_SELECT_AREA']) ?></option>
                                <?php foreach ($areas as $a): ?>
                                    <option value="<?php echo (int)$a['area_id']; ?>">
                                        <?php
                                            $label = (string)$a['area_name'];
                                            if (!empty($a['zone_name'])) $label .= ' — ' . $a['zone_name'];
                                            echo htmlspecialchars($label);
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="btn green" name="action" value="fix_location_link">
                                <?= htmlspecialchars($lang['LOC_FIX']) ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4"><?= htmlspecialchars($lang['LOC_NO_BROKEN_AREA_LINKS']) ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<hr>

<!-- 4) Areas missing zone -->
<div class="rc-panel">
    <h3><?= htmlspecialchars($lang['LOC_REPAIR_AREAS_MISSING_ZONE']) ?></h3>
    <p style="opacity:.8;"><?= htmlspecialchars($lang['LOC_REPAIR_AREAS_MISSING_ZONE_HELP']) ?></p>

    <table class="rc-table row-hover">
        <thead>
        <tr>
            <th><?= htmlspecialchars($lang['LOC_AREA']) ?></th>
            <th style="width:260px;"><?= htmlspecialchars($lang['LOC_ASSIGN_ZONE']) ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($areas_missing_zone)): ?>
            <?php foreach ($areas_missing_zone as $a): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$a['area_name']); ?></td>
                    <td>
                        <form action="../controllers/locations_handler.php" method="post" class="xform" style="display:flex; gap:8px; align-items:center; margin:0;">
                            <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">
                            <input type="hidden" name="area_id" value="<?php echo (int)$a['area_id']; ?>">

                            <select name="zone_id" class="xform-input" required>
                                <option value=""><?= htmlspecialchars($lang['LOC_SELECT_ZONE']) ?></option>
                                <?php foreach ($zones as $z): ?>
                                    <option value="<?php echo (int)$z['zone_id']; ?>">
                                        <?php echo htmlspecialchars((string)$z['zone_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="btn green" name="action" value="fix_area_zone_link">
                                <?= htmlspecialchars($lang['LOC_ASSIGN']) ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="2"><?= htmlspecialchars($lang['LOC_NO_AREAS_MISSING_ZONE']) ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<hr>

<!-- 5) Bulk tools -->
<div class="rc-panel">
    <h3><?= htmlspecialchars($lang['LOC_BULK_TOOLS']) ?></h3>
    <p style="opacity:.8;"><?= htmlspecialchars($lang['LOC_BULK_TOOLS_HELP']) ?></p>

    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <form action="../controllers/locations_handler.php" method="post" class="xform" style="margin:0;">
            <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">
            <button type="submit" class="btn green" name="action" value="bulk_backfill_area_id"
                    onclick="return confirm(<?= htmlspecialchars(json_encode($lang['LOC_BACKFILL_CONFIRM']), ENT_QUOTES, 'UTF-8') ?>);">
                <?= htmlspecialchars($lang['LOC_BACKFILL_AREA_ID']) ?>
            </button>
        </form>

        <form action="../controllers/locations_handler.php" method="post" class="xform" style="margin:0;">
            <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">
            <button type="submit" class="btn green" name="action" value="bulk_sync_location_area"
                    onclick="return confirm(<?= htmlspecialchars(json_encode($lang['LOC_SYNC_CONFIRM']), ENT_QUOTES, 'UTF-8') ?>);">
                <?= htmlspecialchars($lang['LOC_SYNC_LOCATION_AREA']) ?>
            </button>
        </form>
    </div>

    <div style="opacity:.75; margin-top:10px;">
        <?= htmlspecialchars($lang['LOC_BULK_NOTE']) ?>
    </div>
</div>
