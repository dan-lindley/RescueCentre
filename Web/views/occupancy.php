<?php
// views/occupancy.php
// Allow embedding without APP_LOADED
if (!isset($OCCUPANCY_EMBED)) {
    if (!defined('APP_LOADED')) exit;
    echo '<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2>' . htmlspecialchars($lang['LOC_OCCUPANCY']) . '</h2>
            <p>' . htmlspecialchars($lang['LOC_OCCUPANCY_SUBTITLE']) . '</p>
        </div>
    </div>
</div>
';
}
$centre_id_int = isset($centre_id) ? (int)$centre_id : 0;

/* ============================================================
   LOAD AREAS
   ============================================================ */
$areaStmt = $pdo->prepare("
    SELECT area_id, area_name
    FROM rescue_areas
    WHERE centre_id = :cid
    ORDER BY area_name ASC
");
$areaStmt->execute([':cid' => $centre_id_int]);
$areasRaw = $areaStmt->fetchAll(PDO::FETCH_ASSOC);

$areaData = [];
foreach ($areasRaw as $ar) {
    $name = $ar['area_name'];
    $areaData[$name] = [
        'area_id'   => (int)$ar['area_id'],
        'capacity'  => 0,
        'occupancy' => 0,
        'locations' => []
    ];
}

$noAreaKey = $lang['LOC_NO_AREA_ASSIGNED'];
$areaData[$noAreaKey] = [
    'area_id'   => 0,
    'capacity'  => 0,
    'occupancy' => 0,
    'locations' => []
];

/* ============================================================
   LOAD LOCATIONS
   ============================================================ */
$locStmt = $pdo->prepare("
    SELECT location_id, location_name, location_area, max_occupancy
    FROM rescue_locations
    WHERE centre_id = :cid
      AND (deleted = 0 OR deleted IS NULL)
    ORDER BY location_name ASC
");
$locStmt->execute([':cid' => $centre_id_int]);
$locationRows = $locStmt->fetchAll(PDO::FETCH_ASSOC);

$centre_capacity      = 0;
$locationCapacity     = [];
$locationAreaLookup   = [];

foreach ($locationRows as $loc) {
    $name = $loc['location_name'];
    $area = trim($loc['location_area'] ?? '');
    $cap  = (int)$loc['max_occupancy'];

    $centre_capacity += $cap;
    $locationCapacity[$name]   = $cap;
    $locationAreaLookup[$name] = $area;

    if ($area !== "" && isset($areaData[$area])) {
        $areaData[$area]['capacity'] += $cap;
        $areaData[$area]['locations'][] = $loc;
    } else {
        $areaData[$noAreaKey]['capacity'] += $cap;
        $areaData[$noAreaKey]['locations'][] = $loc;
    }
}

/* ============================================================
   LOAD OCCUPANCY FROM ADMISSIONS
   ============================================================ */
$admStmt = $pdo->prepare("
    SELECT current_location
    FROM rescue_admissions
    WHERE disposition = 'Held in Captivity'
      AND centre_id = :cid
");
$admStmt->execute([':cid' => $centre_id_int]);
$admissions = $admStmt->fetchAll(PDO::FETCH_ASSOC);

$centre_occupancy  = 0;
$locationOccupancy = [];

foreach ($admissions as $adm) {
    $locName = trim($adm['current_location'] ?? '');
    if ($locName === "") {
        continue;
    }
    if (!isset($locationCapacity[$locName])) {
        continue;
    }

    $centre_occupancy++;

    if (!isset($locationOccupancy[$locName])) {
        $locationOccupancy[$locName] = 0;
    }
    $locationOccupancy[$locName]++;
}

foreach ($locationOccupancy as $locName => $count) {
    $area = $locationAreaLookup[$locName] ?? '';
    if ($area !== "" && isset($areaData[$area])) {
        $areaData[$area]['occupancy'] += $count;
    } else {
        $areaData[$noAreaKey]['occupancy'] += $count;
    }
}

/* ============================================================
   HELPERS
   ============================================================ */
function capPercent($occ, $cap) {
    if ($cap <= 0) return 0;
    return round(($occ / $cap) * 100);
}

function capClass($pct) {
    if ($pct < 60) return "green";
    if ($pct <= 80) return "amber";
    return "red";
}

/* ============================================================
   GLOBAL CSS (for both full page + summary)
   ============================================================ */
?>
<style>
/* 3-column area summary grid for the SUMMARY BLOCK ONLY */
.area-summary-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px 24px;
    margin-top: 15px;
}
@media (max-width: 900px) {
    .area-summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 600px) {
    .area-summary-grid {
        grid-template-columns: 1fr;
    }
}
</style>
<?php

/* ============================================================
   REUSABLE SUMMARY BLOCK (CENTRE + AREAS ONLY)
   ============================================================ */
function render_capacity_summary_block($centre_capacity, $centre_occupancy, $areaData)
{
    $pct = capPercent($centre_occupancy, $centre_capacity);
    $cls = capClass($pct);
    ?>
    <div class="rc-panel">
        <h3><?= htmlspecialchars($GLOBALS['lang']['LOC_CAPACITY_SUMMARY']) ?></h3>

        <p>
            <?= htmlspecialchars($GLOBALS['lang']['LOC_CAPACITY']) ?>: <strong><?= $centre_capacity ?></strong><br>
            <?= htmlspecialchars($GLOBALS['lang']['LOC_OCCUPANCY_VALUE']) ?>: <strong><?= $centre_occupancy ?></strong><br>
            <?= htmlspecialchars($GLOBALS['lang']['LOC_UTILISATION']) ?>: <strong><?= $pct ?>%</strong>
        </p>

        <div class="rc-thermometer">
            <div class="rc-thermometer-fill <?= $cls ?>" style="width: <?= $pct ?>%;"></div>
        </div>

        <hr>

        <div class="area-summary-grid">
            <?php foreach ($areaData as $areaName => $stats): ?>
                <?php
                // Hide "No Area Assigned" ONLY in summary when empty
                if (
                    $areaName === $GLOBALS['lang']['LOC_NO_AREA_ASSIGNED'] &&
                    $stats['capacity'] == 0 &&
                    $stats['occupancy'] == 0
                ) {
                    continue;
                }

                $aCap = $stats['capacity'];
                $aOcc = $stats['occupancy'];
                $aPct = capPercent($aOcc, $aCap);
                $aCls = capClass($aPct);
                ?>
                <div class="rc-card">
                    <p>
                        <strong><?= htmlspecialchars($areaName) ?></strong><br>
                        <?= htmlspecialchars($GLOBALS['lang']['LOC_CAPACITY']) ?>: <?= $aCap ?> |
                        <?= htmlspecialchars($GLOBALS['lang']['LOC_OCCUPANCY_VALUE']) ?>: <?= $aOcc ?> |
                        <?= $aPct ?>%
                    </p>

                    <div class="rc-thermometer">
                        <div class="rc-thermometer-fill <?= $aCls ?>" style="width: <?= $aPct ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/* ============================================================
   EMBED MODE: ONLY DEFINE LOGIC + FUNCTION, NO PAGE OUTPUT
   ============================================================ */
if (isset($OCCUPANCY_EMBED)) {
    return;
}

/* ============================================================
   NORMAL PAGE RENDER (DETAILED VIEW)
   ============================================================ */
?>


<?php
$centre_pct = capPercent($centre_occupancy, $centre_capacity);
$centre_cls = capClass($centre_pct);
?>

<div class="rc-panel">
    <h3><?= htmlspecialchars($lang['LOC_CENTRE_TOTAL']) ?></h3>

    <p>
        <?= htmlspecialchars($lang['LOC_CAPACITY']) ?>: <strong><?= $centre_capacity ?></strong><br>
        <?= htmlspecialchars($lang['LOC_OCCUPANCY_VALUE']) ?>: <strong><?= $centre_occupancy ?></strong><br>
        <?= htmlspecialchars($lang['LOC_UTILISATION']) ?>: <strong><?= $centre_pct ?>%</strong>
    </p>

    <div class="rc-thermometer">
        <div class="rc-thermometer-fill <?= $centre_cls ?>" style="width: <?= $centre_pct ?>%;"></div>
    </div>
</div>

<?php foreach ($areaData as $areaName => $stats): ?>
    <?php
    $cap = $stats['capacity'];
    $occ = $stats['occupancy'];
    $pct = capPercent($occ, $cap);
    $cls = capClass($pct);
    ?>
    <div class="rc-panel">
        <h3><?= htmlspecialchars($areaName) ?></h3>

        <p>
            <?= htmlspecialchars($lang['LOC_CAPACITY']) ?>: <strong><?= $cap ?></strong><br>
            <?= htmlspecialchars($lang['LOC_OCCUPANCY_VALUE']) ?>: <strong><?= $occ ?></strong><br>
            <?= htmlspecialchars($lang['LOC_UTILISATION']) ?>: <strong><?= $pct ?>%</strong>
        </p>

        <div class="rc-thermometer">
            <div class="rc-thermometer-fill <?= $cls ?>" style="width: <?= $pct ?>%;"></div>
        </div>

        <?php if (!empty($stats['locations'])): ?>
            <table class="rc-table row-hover" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th><?= htmlspecialchars($lang['LOCATION']) ?></th>
                        <th><?= htmlspecialchars($lang['LOC_CAPACITY']) ?></th>
                        <th><?= htmlspecialchars($lang['LOC_OCCUPANCY_VALUE']) ?></th>
                        <th><?= htmlspecialchars($lang['LOC_UTILISATION']) ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($stats['locations'] as $loc): ?>
                    <?php
                    $locName = $loc['location_name'];
                    $locCap  = (int)$loc['max_occupancy'];
                    $locOcc  = $locationOccupancy[$locName] ?? 0;
                    $locPct  = capPercent($locOcc, $locCap);
                    $locCls  = capClass($locPct);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($locName) ?></td>
                        <td><?= $locCap ?></td>
                        <td><?= $locOcc ?></td>
                        <td>
                            <?= $locPct ?>%
                            <div class="rc-thermometer">
                                <div class="rc-thermometer-fill <?= $locCls ?>" style="width: <?= $locPct ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><em><?= htmlspecialchars($lang['LOC_NO_LOCATIONS_AREA']) ?></em></p>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
