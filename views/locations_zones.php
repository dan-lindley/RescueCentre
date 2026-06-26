<?php
// views/locations_zones.php
if (!defined('APP_LOADED')) exit;

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

// Inline edit helpers
$edit_zone_id = isset($_GET['edit_zone']) ? (int)$_GET['edit_zone'] : 0;
$edit_area_id = isset($_GET['edit_area']) ? (int)$_GET['edit_area'] : 0;

// Best-effort default zone name
$default_zone_name = '';
if (isset($centre_name) && is_string($centre_name) && $centre_name !== '') $default_zone_name = $centre_name;
if ($default_zone_name === '' && isset($centre['name']) && is_string($centre['name'])) $default_zone_name = $centre['name'];
if ($default_zone_name === '' && isset($centre['centre_name']) && is_string($centre['centre_name'])) $default_zone_name = $centre['centre_name'];

// Gate: if no zones exist -> show only Add Zone form
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rescue_zones WHERE centre_id = :cid");
$stmt->execute([':cid' => $centre_id_int]);
$zones_count = (int)($stmt->fetchColumn() ?? 0);

// Location type options
$LOCATION_TYPES = [
    '' => 'Select...',
    'Incubator' => 'Incubator',
    'Tank' => 'Tank',
    'Pen' => 'Pen',
    'Kennel' => 'Kennel',
    'Paddock' => 'Paddock',
    'Hutch' => 'Hutch',
    'Aviary' => 'Aviary',
    'Flight Cage' => 'Flight Cage',
    'Cage' => 'Cage',
    'Bat Box' => 'Bat Box',
    'Bird Box' => 'Bird Box',
    'Hospital Cage' => 'Hospital Cage',
    'Terrarium' => 'Terrarium',
    'Aquarium' => 'Aquarium',
    'Pool' => 'Pool',
    'Crate/Carrier' => 'Crate/Carrier',
    'Other' => 'Other',
];

function render_type_select(array $types, string $current): string {
    $out = '<select name="location_type" class="xform-input" aria-label="' . htmlspecialchars($GLOBALS['lang']['LOC_LOCATION_TYPE']) . '">';
    foreach ($types as $val => $label) {
        $sel = ((string)$val === (string)$current) ? ' selected' : '';
        $out .= '<option value="' . htmlspecialchars((string)$val) . '"' . $sel . '>'
             . htmlspecialchars((string)$label) . '</option>';
    }
    $out .= '</select>';
    return $out;
}

echo '<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2>' . htmlspecialchars($lang['LOC_ZONES_AREAS_LOCATIONS']) . '</h2>
            <p>' . htmlspecialchars($lang['LOC_ZONES_AREAS_LOCATIONS_SUBTITLE']) . '</p>
        </div>
    </div>
</div>';
?>

<style>
/* Shared header + row grid */
.loc-grid{
    display:grid;
    grid-template-columns: 1fr 220px 220px 100px 280px; /* Location | Area | Type | Max | Actions */
    gap:10px;
    align-items:center;
}
.loc-head{
    opacity:.85;
    font-size:12px;
    padding:0 2px;
    margin:0 0 6px 0;
}
.loc-row{
    border:1px solid var(--rc-border);
    border-radius:10px;
    padding:10px;
    margin:0 0 10px 0;
    background:var(--rc-surface);
}
.loc-row.loc-add{
    border-style:dashed;
}
.loc-row .xform-input{
    width:100%;
    box-sizing:border-box;
}
.loc-actions{
    display:flex;
    gap:8px;
    align-items:center;
    justify-content:flex-start;
    white-space:nowrap;
}
.loc-actions .btn{
    margin:0;
}

/* New header action styling */
.hdr-flex{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
}
.hdr-actions{
    display:flex;
    gap:8px;
    align-items:center;
    flex-wrap:wrap;
}
.inline-edit-box{
    margin:8px 0 12px 0;
    padding:12px;
    border:1px dashed var(--rc-border);
    border-radius:10px;
    background:var(--rc-surface-muted);
}
.inline-edit-grid{
    display:grid;
    grid-template-columns: 1fr auto auto;
    gap:10px;
    align-items:end;
}
.btn.btn-sm{
    padding:7px 10px;
    font-size:12px;
    line-height:1.2;
}
@media (max-width: 860px){
    .inline-edit-grid{
        grid-template-columns: 1fr;
    }
}
</style>

<div class="rc-panel">

    <?php if ($flash && is_array($flash)): ?>
        <div class="rc-alert <?php echo htmlspecialchars($flash['type'] ?? 'green'); ?>" style="margin-bottom:12px;">
            <?php echo htmlspecialchars($flash['message'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <?php if ($zones_count <= 0): ?>

        <div class="rc-alert amber" style="margin-bottom:12px;">
            <?= htmlspecialchars($lang['LOC_NO_ZONES']) ?>
        </div>

        <h3 style="margin-top:0;"><?= htmlspecialchars($lang['LOC_ADD_ZONE']) ?></h3>
        <form action="../controllers/locations_handler.php" method="post" class="xform">
            <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">
            <input type="hidden" name="action" value="create_zone">

            <div class="xform-grid">
                <div class="xform-field">
                    <label class="xform-label"><?= htmlspecialchars($lang['LOC_ZONE_NAME']) ?></label>
                    <input type="text" name="zone_name" class="xform-input"
                           value="<?php echo htmlspecialchars($default_zone_name); ?>"
                           placeholder="<?= htmlspecialchars($lang['LOC_YOUR_RESCUE_NAME']) ?>" required>
                </div>

                <div class="xform-field">
                    <label class="xform-label"><?= htmlspecialchars($lang['ACTIVE']) ?></label>
                    <label class="xform-label" style="display:flex; gap:8px; align-items:center;">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <?= htmlspecialchars($lang['LOC_ENABLED']) ?>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn green"><?= htmlspecialchars($lang['LOC_SAVE_ZONE']) ?></button>
        </form>

    <?php else: ?>

        <?php
        // Joined query with legacy fallback: prefer ID, else match by text
        $stmt = $pdo->prepare("
            SELECT
                z.zone_id, z.zone_name, z.is_active,
                a.area_id, a.area_name,
                l.location_id, l.location_name, l.location_type, l.max_occupancy
            FROM rescue_zones z
            LEFT JOIN rescue_areas a
                ON a.zone_id   = z.zone_id
               AND a.centre_id = z.centre_id
            LEFT JOIN rescue_locations l
                ON l.centre_id = a.centre_id
               AND (l.deleted = 0 OR l.deleted IS NULL)
               AND (
                    (l.area_id IS NOT NULL AND l.area_id <> 0 AND l.area_id = a.area_id)
                 OR (
                        (l.area_id IS NULL OR l.area_id = 0)
                    AND l.location_area IS NOT NULL
                    AND TRIM(l.location_area) <> ''
                    AND TRIM(l.location_area) = TRIM(a.area_name)
                 )
               )
            WHERE z.centre_id = :cid
            ORDER BY z.zone_name ASC, a.area_name ASC, l.location_name ASC
        ");
        $stmt->execute([':cid' => $centre_id_int]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Build nested
        $zonesById = [];
        foreach ($rows as $r) {
            $zid = (int)$r['zone_id'];
            $aid = (int)($r['area_id'] ?? 0);
            $lid = (int)($r['location_id'] ?? 0);

            if (!isset($zonesById[$zid])) {
                $zonesById[$zid] = [
                    'zone_id' => $zid,
                    'zone_name' => (string)$r['zone_name'],
                    'is_active' => (int)($r['is_active'] ?? 1),
                    'areas' => []
                ];
            }

            if ($aid > 0 && !isset($zonesById[$zid]['areas'][$aid])) {
                $zonesById[$zid]['areas'][$aid] = [
                    'area_id' => $aid,
                    'area_name' => (string)$r['area_name'],
                    'locations' => []
                ];
            }

            if ($aid > 0 && $lid > 0) {
                $zonesById[$zid]['areas'][$aid]['locations'][] = [
                    'location_id' => $lid,
                    'location_name' => (string)$r['location_name'],
                    'location_type' => (string)($r['location_type'] ?? ''),
                    'max_occupancy' => $r['max_occupancy']
                ];
            }
        }

        // Ensure zones exist even if no areas/locations returned
        $zones = array_values($zonesById);
        if (empty($zones)) {
            $stmt = $pdo->prepare("SELECT zone_id, zone_name, is_active FROM rescue_zones WHERE centre_id=:cid ORDER BY zone_name ASC");
            $stmt->execute([':cid' => $centre_id_int]);
            $zs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($zs as $zrow) {
                $zones[] = [
                    'zone_id' => (int)$zrow['zone_id'],
                    'zone_name' => (string)$zrow['zone_name'],
                    'is_active' => (int)($zrow['is_active'] ?? 1),
                    'areas' => []
                ];
            }
        }
        ?>

        <?php foreach ($zones as $z): ?>
            <?php
            $zid = (int)$z['zone_id'];
            $zname = (string)$z['zone_name'];
            $zactive = (int)$z['is_active'];
            $zoneAreas = $z['areas'] ?? [];
            ?>

            <div class="rc-alert green" style="margin:0 0 12px 0; padding:12px 14px;">
                <div class="hdr-flex">
                    <div style="font-weight:800; font-size:16px;">
                        <?= htmlspecialchars($lang['LOC_ZONE']) ?>: <?php echo htmlspecialchars($zname); ?>
                        <?php if ($zactive !== 1): ?>
                            <span style="opacity:.75; font-weight:600; font-size:12px; margin-left:10px;">(<?= htmlspecialchars($lang['LOC_INACTIVE']) ?>)</span>
                        <?php endif; ?>
                    </div>
                    <div class="hdr-actions">
                        <a href="qr.php?zone_id=<?php echo $zid; ?>" target="_blank" class="btn grey btn-sm">QR</a>
                        <a href="?tab=zones&edit_zone=<?php echo $zid; ?>" class="btn btn-sm"><?= htmlspecialchars($lang['EDIT']) ?></a>

                        <form action="../controllers/locations_handler.php" method="post" style="margin:0;">
                            <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">
                            <input type="hidden" name="zone_id" value="<?php echo $zid; ?>">
                            <button type="submit" class="btn red" name="action" value="delete_zone"
                                    onclick="return confirm(<?= htmlspecialchars(json_encode($lang['LOC_DELETE_ZONE_CONFIRM']), ENT_QUOTES, 'UTF-8') ?>);">
                                <?= htmlspecialchars($lang['DELETE']) ?>
                            </button>
                        </form>

                        <div style="opacity:.75; font-size:12px;"><?= htmlspecialchars($lang['LOC_ZONE_ID']) ?>: <?php echo $zid; ?></div>
                    </div>
                </div>

                <?php if ($edit_zone_id === $zid): ?>
                    <div class="inline-edit-box">
                        <form action="../controllers/locations_handler.php" method="post" class="xform" style="margin:0;">
                            <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">
                            <input type="hidden" name="zone_id" value="<?php echo $zid; ?>">
                            <input type="hidden" name="action" value="update_zone">

                            <div class="inline-edit-grid">
                                <div class="xform-field" style="margin:0;">
                                    <label class="xform-label" style="margin-bottom:4px;"><?= htmlspecialchars($lang['LOC_EDIT_ZONE_NAME']) ?></label>
                                    <input type="text" name="zone_name" class="xform-input"
                                           value="<?php echo htmlspecialchars($zname); ?>" required>
                                </div>

                                <div class="xform-field" style="margin:0;">
                                    <label class="xform-label" style="margin-bottom:4px;"><?= htmlspecialchars($lang['ACTIVE']) ?></label>
                                    <label class="xform-label" style="display:flex; gap:8px; align-items:center; margin:0;">
                                        <input type="checkbox" name="is_active" value="1" <?php echo ($zactive === 1 ? 'checked' : ''); ?>>
                                        <?= htmlspecialchars($lang['LOC_ENABLED']) ?>
                                    </label>
                                </div>

                                <div style="display:flex; gap:8px; align-items:end;">
                                    <button type="submit" class="btn green"><?= htmlspecialchars($lang['SAVE']) ?></button>
                                    <a href="?tab=zones" class="btn"><?= htmlspecialchars($lang['CANCEL']) ?></a>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($zoneAreas)): ?>
                <?php foreach ($zoneAreas as $a): ?>
                    <?php
                    $aid = (int)$a['area_id'];
                    $aname = (string)$a['area_name'];
                    $areaLocs = $a['locations'] ?? [];
                    ?>

                    <div class="rc-alert amber" style="margin:0 0 10px 18px; padding:10px 12px;">
                        <div class="hdr-flex">
                            <div>
                                <div style="font-weight:800; font-size:14px;"><?= htmlspecialchars($lang['LOC_AREA']) ?>: <?php echo htmlspecialchars($aname); ?></div>
                                <div style="opacity:.75; font-size:12px;"><?= htmlspecialchars($lang['LOC_AREA_ID']) ?>: <?php echo $aid; ?></div>
                            </div>

                            <div class="hdr-actions">
                                <a href="qr.php?area_id=<?php echo $aid; ?>" target="_blank" class="btn grey btn-sm">QR</a>
                                <a href="?tab=zones&edit_area=<?php echo $aid; ?>" class="btn btn-sm"><?= htmlspecialchars($lang['EDIT']) ?></a>

                                <form action="../controllers/locations_handler.php" method="post" style="margin:0;">
                                    <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">
                                    <input type="hidden" name="area_id" value="<?php echo $aid; ?>">
                                    <button type="submit" class="btn red" name="action" value="delete_area"
                                            onclick="return confirm(<?= htmlspecialchars(json_encode($lang['LOC_DELETE_AREA_CONFIRM']), ENT_QUOTES, 'UTF-8') ?>);">
                                        <?= htmlspecialchars($lang['DELETE']) ?>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <?php if ($edit_area_id === $aid): ?>
                            <div class="inline-edit-box">
                                <form action="../controllers/locations_handler.php" method="post" class="xform" style="margin:0;">
                                    <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">
                                    <input type="hidden" name="area_id" value="<?php echo $aid; ?>">
                                    <input type="hidden" name="action" value="update_area">

                                    <div class="inline-edit-grid">
                                        <div class="xform-field" style="margin:0;">
                                            <label class="xform-label" style="margin-bottom:4px;"><?= htmlspecialchars($lang['LOC_EDIT_AREA_NAME']) ?></label>
                                            <input type="text" name="area_name" class="xform-input"
                                                   value="<?php echo htmlspecialchars($aname); ?>" required>
                                        </div>

                                        <div></div>

                                        <div style="display:flex; gap:8px; align-items:end;">
                                            <button type="submit" class="btn green"><?= htmlspecialchars($lang['SAVE']) ?></button>
                                            <a href="?tab=zones" class="btn"><?= htmlspecialchars($lang['CANCEL']) ?></a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="margin:0 0 16px 34px;">

                        <div class="loc-grid loc-head">
                            <div><strong><?= htmlspecialchars($lang['LOCATION']) ?></strong></div>
                            <div><strong><?= htmlspecialchars($lang['LOC_AREA']) ?></strong></div>
                            <div><strong><?= htmlspecialchars($lang['DIET_TH_TYPE'] ?? 'Type') ?></strong></div>
                            <div><strong><?= htmlspecialchars($lang['LOC_MAX']) ?></strong></div>
                            <div><strong><?= htmlspecialchars($lang['ACTIONS']) ?></strong></div>
                        </div>

                        <?php if (!empty($areaLocs)): ?>
                            <?php foreach ($areaLocs as $l): ?>
                                <?php
                                $lid = (int)$l['location_id'];
                                $lname = (string)$l['location_name'];
                                $ltype = (string)($l['location_type'] ?? '');
                                $lmax  = $l['max_occupancy'];
                                ?>

                                <div class="loc-row">
                                    <form action="../controllers/locations_handler.php" method="post" class="xform" style="margin:0;">
                                        <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">
                                        <input type="hidden" name="location_id" value="<?php echo $lid; ?>">

                                        <div class="loc-grid">
                                            <div>
                                                <input type="text" name="location_name" class="xform-input"
                                                       value="<?php echo htmlspecialchars($lname); ?>"
                                                       placeholder="<?= htmlspecialchars($lang['LOC_LOCATION_NAME']) ?>" aria-label="<?= htmlspecialchars($lang['LOC_LOCATION_NAME']) ?>" required>
                                            </div>
                                            <div>
                                                <select name="area_id" class="xform-input" aria-label="<?= htmlspecialchars($lang['LOC_AREA']) ?>" required>
                                                    <?php foreach ($zones as $areaZone): ?>
                                                        <?php foreach (($areaZone['areas'] ?? []) as $moveArea): ?>
                                                            <?php
                                                            $moveAreaId = (int)$moveArea['area_id'];
                                                            $moveAreaLabel = (string)$moveArea['area_name'];
                                                            if (!empty($areaZone['zone_name'])) {
                                                                $moveAreaLabel .= ' - ' . (string)$areaZone['zone_name'];
                                                            }
                                                            ?>
                                                            <option value="<?php echo $moveAreaId; ?>" <?php echo ($moveAreaId === $aid ? 'selected' : ''); ?>>
                                                                <?php echo htmlspecialchars($moveAreaLabel); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div><?php echo render_type_select($LOCATION_TYPES, $ltype); ?></div>
                                            <div>
                                                <input type="number" name="max_occupancy" class="xform-input"
                                                       value="<?php echo htmlspecialchars((string)($lmax ?? '')); ?>"
                                                       min="0" step="1" aria-label="<?= htmlspecialchars($lang['LOC_MAX_OCCUPANCY']) ?>">
                                            </div>
                                            <div class="loc-actions">
                                                <a href="qr.php?location_id=<?php echo $lid; ?>" target="_blank" class="btn grey">QR</a>
                                                <button type="submit" class="btn green" name="action" value="update_location"><?= htmlspecialchars($lang['UPDATED']) ?></button>
                                                <button type="submit" class="btn red" name="action" value="delete_location"
                                                        onclick="return confirm(<?= htmlspecialchars(json_encode($lang['LOC_DELETE_LOCATION_CONFIRM']), ENT_QUOTES, 'UTF-8') ?>);">
                                                    <?= htmlspecialchars($lang['DELETE']) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="opacity:.75; padding:8px 2px;"><?= htmlspecialchars($lang['LOC_NO_LOCATIONS_YET']) ?></div>
                        <?php endif; ?>

                        <!-- ADD LOCATION row -->
                        <div class="loc-row loc-add">
                            <form action="../controllers/locations_handler.php" method="post" class="xform" style="margin:0;">
                                <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">
                                <input type="hidden" name="area_id" value="<?php echo $aid; ?>">
                                <input type="hidden" name="location_area" value="<?php echo htmlspecialchars($aname); ?>">

                                <div class="loc-grid">
                                    <div>
                                        <input type="text" name="location_name" class="xform-input"
                                               placeholder="<?= htmlspecialchars($lang['LOC_ADD_NEW_LOCATION']) ?>" aria-label="<?= htmlspecialchars($lang['LOC_ADD_LOCATION_NAME']) ?>" required>
                                    </div>
                                    <div><input type="text" class="xform-input" value="<?php echo htmlspecialchars($aname); ?>" aria-label="<?= htmlspecialchars($lang['LOC_AREA']) ?>" disabled></div>
                                    <div><?php echo render_type_select($LOCATION_TYPES, ''); ?></div>
                                    <div>
                                        <input type="number" name="max_occupancy" class="xform-input"
                                               min="0" step="1" aria-label="<?= htmlspecialchars($lang['LOC_ADD_MAX_OCCUPANCY']) ?>">
                                    </div>
                                    <div class="loc-actions">
                                        <button type="submit" class="btn green" name="action" value="add_location"><?= htmlspecialchars($lang['ADD']) ?></button>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div style="opacity:.75; margin-left:18px; margin-bottom:10px;"><?= htmlspecialchars($lang['LOC_NO_AREAS_ZONE']) ?></div>
            <?php endif; ?>

            <!-- ADD AREA -->
            <div style="margin:0 0 18px 18px; border:1px dashed rgba(0,0,0,.12); border-radius:10px; padding:12px;">
                <form action="../controllers/locations_handler.php" method="post" class="xform" style="margin:0;">
                    <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">
                    <input type="hidden" name="zone_id" value="<?php echo $zid; ?>">

                    <div style="display:grid; grid-template-columns: 1fr auto; gap:10px; align-items:end;">
                        <div class="xform-field" style="margin:0;">
                            <label class="xform-label" style="margin-bottom:4px;"><?= htmlspecialchars($lang['LOC_ADD_AREA_ZONE']) ?></label>
                            <input type="text" name="area_name" class="xform-input" placeholder="e.g. Shed / Outhouse / Ward A" required>
                        </div>
                        <div style="display:flex; justify-content:flex-end;">
                            <button type="submit" class="btn green" name="action" value="add_area"><?= htmlspecialchars($lang['LOC_ADD_AREA']) ?></button>
                        </div>
                    </div>
                </form>
            </div>

            <hr style="margin:14px 0;">
        <?php endforeach; ?>

        <!-- ADD ZONE -->
        <h3 style="margin-top:0;"><?= htmlspecialchars($lang['LOC_ADD_ANOTHER_ZONE']) ?></h3>
        <form action="../controllers/locations_handler.php" method="post" class="xform">
            <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">
            <input type="hidden" name="action" value="create_zone">

            <div class="xform-grid">
                <div class="xform-field">
                    <label class="xform-label"><?= htmlspecialchars($lang['LOC_ZONE_NAME']) ?></label>
                    <input type="text" name="zone_name" class="xform-input" placeholder="e.g. Outbuildings" required>
                </div>

                <div class="xform-field">
                    <label class="xform-label"><?= htmlspecialchars($lang['ACTIVE']) ?></label>
                    <label class="xform-label" style="display:flex; gap:8px; align-items:center;">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <?= htmlspecialchars($lang['LOC_ENABLED']) ?>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn green"><?= htmlspecialchars($lang['LOC_ADD_ZONE']) ?></button>
        </form>

    <?php endif; ?>

</div>
