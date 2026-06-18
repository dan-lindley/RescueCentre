<?php
/**
 * controllers/tasks/locations.php
 * POST-state Locations wizard (router-safe).
 *
 * WHY THIS VERSION:
 * - Some installs drop query params / sessions are unreliable across home.php widget loads.
 * - Therefore we do NOT depend on ?mode=... or $_SESSION for wizard state.
 * - We carry state forward via POST hidden inputs (mode + step).
 *
 * Modes:
 *  single -> auto Zone (centre name) + 1 Area + Locations
 *  small  -> auto Zone (centre name) + multiple Areas + Locations per Area
 *  multi  -> Zones + Areas per Zone + Locations per Area
 *
 * Tables:
 * - rescue_zones(zone_id, centre_id, zone_name, zone_notes, sort_order, is_active, created_at)
 * - rescue_areas(area_id, centre_id, zone_id, area_name)
 * - rescue_locations(location_id, centre_id, area_id, location_name, location_type, max_occupancy, deleted, location_area)
 */

if (!isset($pdo) || !($pdo instanceof PDO) || !isset($centre_id) || $centre_id === '') {
    echo '<div class="alert-box alert-red" style="margin:0;"><strong>Locations</strong><br>Context missing.</div>';
    return;
}
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

$centre_id = (string)$centre_id;

/* ------------------------------------------------------------
   Helpers
------------------------------------------------------------ */
function rc_norm(string $s): string {
    $s = preg_replace('/\s+/', ' ', (string)$s);
    return mb_strtolower(trim($s));
}
function rc_h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function rc_get_zone_default_name(): string {
    $candidates = [];
    if (isset($GLOBALS['centre_name'])) $candidates[] = (string)$GLOBALS['centre_name'];
    if (isset($GLOBALS['centre']['name'])) $candidates[] = (string)$GLOBALS['centre']['name'];
    if (isset($GLOBALS['centre']['centre_name'])) $candidates[] = (string)$GLOBALS['centre']['centre_name'];
    foreach ($candidates as $v) {
        $v = trim((string)$v);
        if ($v !== '') return $v;
    }
    return 'Main Rescue';
}
function rc_get_or_create_default_zone(PDO $pdo, string $centre_id, string $zone_name): int {
    $stmt = $pdo->prepare("
        SELECT zone_id
        FROM rescue_zones
        WHERE centre_id = ?
          AND (is_active = 1 OR is_active IS NULL)
          AND zone_name = ?
        ORDER BY sort_order ASC, zone_id ASC
        LIMIT 1
    ");
    $stmt->execute([$centre_id, $zone_name]);
    $zid = (int)$stmt->fetchColumn();
    if ($zid > 0) return $zid;

    $stmt = $pdo->prepare("
        INSERT INTO rescue_zones (centre_id, zone_name, zone_notes, sort_order, is_active)
        VALUES (?, ?, '', 0, 1)
    ");
    $stmt->execute([$centre_id, $zone_name]);
    return (int)$pdo->lastInsertId();
}
function rc_wizard_hidden(string $mode, int $step): string {
    return '<input type="hidden" name="mode" value="'.rc_h($mode).'">'.
           '<input type="hidden" name="step" value="'.(int)$step.'">';
}

/* ------------------------------------------------------------
   Wizard state (POST-driven)
------------------------------------------------------------ */
$validModes = ['single','small','multi'];
$mode = isset($_POST['mode']) ? trim((string)$_POST['mode']) : '';
if (!in_array($mode, $validModes, true)) $mode = '';

$step = 1;
if (isset($_POST['step'])) {
    $step = (int)$_POST['step'];
} elseif (isset($_GET['step'])) {
    $step = (int)$_GET['step']; // allow manual deep link, but it will fall back to stage 0 if mode missing
}
if ($step < 1) $step = 1;
if ($step > 5) $step = 5;

$flash = null;

$types = [
    'Incubator','Tank','Pen','Kennel','Paddock','Hutch','Aviary','Flight Cage','Cage','Bat Box','Bird Box','Other'
];

/* ------------------------------------------------------------
   Load current data (for display + dedupe)
------------------------------------------------------------ */
$stmt = $pdo->prepare("
    SELECT zone_id, zone_name, zone_notes, sort_order, is_active
    FROM rescue_zones
    WHERE centre_id = ?
    ORDER BY sort_order ASC, zone_name ASC, zone_id ASC
");
$stmt->execute([$centre_id]);
$zones_all = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$stmt = $pdo->prepare("
    SELECT a.area_id, a.zone_id, a.area_name, z.zone_name
    FROM rescue_areas a
    LEFT JOIN rescue_zones z
      ON z.zone_id = a.zone_id AND z.centre_id = a.centre_id
    WHERE a.centre_id = ?
    ORDER BY COALESCE(z.zone_name,''), a.area_name ASC, a.area_id ASC
");
$stmt->execute([$centre_id]);
$areas_all = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$stmt = $pdo->prepare("
    SELECT location_id, area_id, location_name, location_type, max_occupancy
    FROM rescue_locations
    WHERE centre_id = ? AND (deleted = 0 OR deleted IS NULL)
    ORDER BY area_id ASC, location_name ASC, location_id ASC
");
$stmt->execute([$centre_id]);
$locations_all = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$areasByZone = [];
foreach ($areas_all as $a) {
    $zid = (int)$a['zone_id'];
    if (!isset($areasByZone[$zid])) $areasByZone[$zid] = [];
    $areasByZone[$zid][] = $a;
}
$locsByArea = [];
foreach ($locations_all as $l) {
    $aid = (int)$l['area_id'];
    if (!isset($locsByArea[$aid])) $locsByArea[$aid] = [];
    $locsByArea[$aid][] = $l;
}

/* ------------------------------------------------------------
   Handle POST actions
------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rc_loc_action'])) {
    $action = (string)$_POST['rc_loc_action'];

    // Stage 0: choose mode
    if ($action === 'choose_mode') {
        $chosen = trim((string)($_POST['rescue_mode'] ?? ''));
        if (!in_array($chosen, $validModes, true)) {
            $flash = ['type'=>'red','msg'=>'Please choose a rescue type.'];
            $mode = '';
            $step = 1;
        } else {
            $mode = $chosen;
            $step = 2; // render next screen immediately
        }
    }

    // Reset wizard
    if ($action === 'reset_wizard') {
        $mode = '';
        $step = 1;
    }

    // SINGLE: save area then go to locations
    if ($action === 'single_save_area' && $mode === 'single') {
        $area_name = trim((string)($_POST['area_name'] ?? ''));
        if ($area_name === '') {
            $flash = ['type'=>'red','msg'=>'Please enter an area name.'];
            $step = 2;
        } else {
            $zoneName = rc_get_zone_default_name();
            $zid = rc_get_or_create_default_zone($pdo, $centre_id, $zoneName);

            // Dedup per zone
            $existing = [];
            foreach ($areas_all as $a) {
                $existing[(int)$a['zone_id'].'|'.rc_norm((string)$a['area_name'])] = (int)$a['area_id'];
            }

            $k = $zid.'|'.rc_norm($area_name);
            if (isset($existing[$k])) {
                $aid = (int)$existing[$k];
            } else {
                $stmt = $pdo->prepare("INSERT INTO rescue_areas (centre_id, zone_id, area_name) VALUES (?, ?, ?)");
                $stmt->execute([$centre_id, $zid, $area_name]);
                $aid = (int)$pdo->lastInsertId();
            }

            // Carry the chosen area forward without URL params
            $_POST['single_aid'] = (string)$aid;
            $step = 3;
        }
    }

    // SMALL: save areas then go to locations
    if ($action === 'small_save_areas' && $mode === 'small') {
        $zoneName = rc_get_zone_default_name();
        $zid = rc_get_or_create_default_zone($pdo, $centre_id, $zoneName);

        $rows = $_POST['area_name'] ?? [];
        if (!is_array($rows)) $rows = [];

        $existing = [];
        foreach ($areas_all as $a) {
            $existing[(int)$a['zone_id'].'|'.rc_norm((string)$a['area_name'])] = true;
        }

        $added = 0;
        foreach ($rows as $n) {
            $n = trim((string)$n);
            if ($n === '') continue;
            $k = $zid.'|'.rc_norm($n);
            if (isset($existing[$k])) continue;

            $stmt = $pdo->prepare("INSERT INTO rescue_areas (centre_id, zone_id, area_name) VALUES (?, ?, ?)");
            if ($stmt->execute([$centre_id, $zid, $n])) {
                $added++;
                $existing[$k] = true;
            }
        }

        $flash = ['type'=>'green','msg'=> $added > 0 ? "Areas saved ($added added)." : "No new areas added (duplicates/blank ignored)."];
        $step = 3;
    }

    // MULTI: save zones then go to areas
    if ($action === 'multi_save_zones' && $mode === 'multi') {
        $names = $_POST['zone_name'] ?? [];
        if (!is_array($names)) $names = [];

        $existing = [];
        foreach ($zones_all as $z) $existing[rc_norm((string)$z['zone_name'])] = true;

        $maxSort = 0;
        foreach ($zones_all as $z) $maxSort = max($maxSort, (int)($z['sort_order'] ?? 0));
        $sort = $maxSort + 1;

        $added = 0;
        foreach ($names as $n) {
            $n = trim((string)$n);
            if ($n === '') continue;

            $k = rc_norm($n);
            if (isset($existing[$k])) continue;

            $stmt = $pdo->prepare("
                INSERT INTO rescue_zones (centre_id, zone_name, zone_notes, sort_order, is_active)
                VALUES (?, ?, '', ?, 1)
            ");
            if ($stmt->execute([$centre_id, $n, $sort + $added])) {
                $added++;
                $existing[$k] = true;
            }
        }

        $flash = ['type'=>'green','msg'=> $added > 0 ? "Zones saved ($added added)." : "No new zones added (duplicates/blank ignored)."];
        $step = 3;
    }

    // MULTI: save areas then go to locations
    if ($action === 'multi_save_areas' && $mode === 'multi') {
        $areasByZonePost = $_POST['areas_by_zone'] ?? [];
        if (!is_array($areasByZonePost)) $areasByZonePost = [];

        $existing = [];
        foreach ($areas_all as $a) {
            $existing[(int)$a['zone_id'].'|'.rc_norm((string)$a['area_name'])] = true;
        }

        $added = 0;
        foreach ($areasByZonePost as $zid => $rows) {
            $zid = (int)$zid;
            if ($zid <= 0) continue;

            foreach ((array)$rows as $n) {
                $n = trim((string)$n);
                if ($n === '') continue;

                $k = $zid.'|'.rc_norm($n);
                if (isset($existing[$k])) continue;

                $stmt = $pdo->prepare("INSERT INTO rescue_areas (centre_id, zone_id, area_name) VALUES (?, ?, ?)");
                if ($stmt->execute([$centre_id, $zid, $n])) {
                    $added++;
                    $existing[$k] = true;
                }
            }
        }

        $flash = ['type'=>'green','msg'=> $added > 0 ? "Areas saved ($added added)." : "No new areas added (duplicates/blank ignored)."];
        $step = 4;
    }

    // Save locations (all modes) -> finish
    if ($action === 'save_locations' && in_array($mode, $validModes, true)) {
        $byArea = $_POST['locations_by_area'] ?? [];
        if (!is_array($byArea)) $byArea = [];

        $areaNameById = [];
        foreach ($areas_all as $a) $areaNameById[(int)$a['area_id']] = (string)$a['area_name'];

        $existing = [];
        foreach ($locations_all as $l) {
            $existing[(int)$l['area_id'].'|'.rc_norm((string)$l['location_name'])] = true;
        }

        $added = 0;
        $skipped = 0;

        foreach ($byArea as $aid => $bundle) {
            $aid = (int)$aid;
            if ($aid <= 0) continue;
            if (!isset($areaNameById[$aid])) continue;

            $names  = $bundle['name'] ?? [];
            $typesP = $bundle['type'] ?? [];
            $others = $bundle['other'] ?? [];
            $caps   = $bundle['cap'] ?? [];

            $rowCount = max(count((array)$names), count((array)$typesP), count((array)$caps));
            for ($i=0; $i<$rowCount; $i++) {
                $name = isset($names[$i]) ? trim((string)$names[$i]) : '';
                if ($name === '') continue;

                $typeSel = isset($typesP[$i]) ? trim((string)$typesP[$i]) : '';
                $other   = isset($others[$i]) ? trim((string)$others[$i]) : '';
                $capRaw  = isset($caps[$i]) ? (int)$caps[$i] : 1;
                $cap     = $capRaw > 0 ? $capRaw : 1;

                $type = $typeSel;
                if ($typeSel === 'Other') {
                    if ($other === '') { $skipped++; continue; }
                    $type = $other;
                }
                if ($type === '') { $skipped++; continue; }

                $k = $aid.'|'.rc_norm($name);
                if (isset($existing[$k])) continue;

                $location_area = $areaNameById[$aid]; // keep legacy text aligned

                $stmt = $pdo->prepare("
                    INSERT INTO rescue_locations
                      (centre_id, area_id, location_name, location_type, max_occupancy, deleted, location_area)
                    VALUES
                      (?, ?, ?, ?, ?, 0, ?)
                ");
                if ($stmt->execute([$centre_id, $aid, $name, $type, $cap, $location_area])) {
                    $added++;
                    $existing[$k] = true;
                }
            }
        }

        $msg = $added > 0 ? "Locations saved ($added added)." : "No new locations added (duplicates/blank ignored).";
        if ($skipped > 0) $msg .= " ($skipped row(s) missing type/Other text skipped.)";
        $flash = ['type'=>'green','msg'=>$msg];
        $step = 5;
    }
}

/* ------------------------------------------------------------
   Render UI
------------------------------------------------------------ */

echo '<style>
.card.rc-locations-wizard .form-row{
  display:flex; gap:10px; flex-wrap:wrap; align-items:flex-start; margin-bottom:10px;
}
.card.rc-locations-wizard .form-row > div{ display:flex; flex-direction:column; }
.card.rc-locations-wizard .form-row label{ margin:0 0 4px 0; line-height:1.2; font-weight:700; }
.card.rc-locations-wizard .form-control{ height:38px; box-sizing:border-box; }
.card.rc-locations-wizard select.form-control{ height:38px; }
.card.rc-locations-wizard .rc-other-wrap{ display:none; }
</style>';

echo '<div class="card rc-locations-wizard" style="padding:12px;">';
echo '<div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">';
echo '  <div style="font-weight:800;">Locations wizard</div>';
echo '  <div style="font-size:13px; opacity:0.8; font-weight:700;">'.($mode ? 'Mode: '.rc_h($mode) : 'Choose mode').'</div>';
echo '</div>';

if ($flash) {
    $cls = ($flash['type'] ?? '') === 'green' ? 'alert-green' : 'alert-red';
    echo '<div class="alert-box '.$cls.'" style="margin:10px 0 0 0;">'.rc_h((string)$flash['msg']).'</div>';
}
echo '<div style="height:10px;"></div>';

/* ------------------------------------------------------------
   Stage 0 (Step 1): choose mode
------------------------------------------------------------ */
if ($step === 1) {
    echo '<div style="font-weight:800; margin-bottom:6px;">Select your type of rescue</div>';
    echo '<div style="opacity:0.85; margin-bottom:12px;">This picks the simplest setup process for your situation.</div>';

    echo '<form method="post" style="margin:0;">';
    echo '<input type="hidden" name="rc_loc_action" value="choose_mode">';
    echo '<input type="hidden" name="step" value="1">';

    echo '<div style="display:grid; gap:10px;">';

    $cards = [
        ['single','1) One space','Everything happens in one place (e.g. a shed/garage/room).'],
        ['small','2) Small rescue','A couple of different places where you keep animals.'],
        ['multi','3) Larger / multi-site','Different sites or offsite volunteers / carers.'],
    ];

    foreach ($cards as $c) {
        [$val,$title,$desc] = $c;
        echo '<label style="display:block; border:1px solid rgba(0,0,0,.10); border-radius:12px; padding:12px; cursor:pointer;">';
        echo '<div style="display:flex; gap:10px; align-items:flex-start;">';
        echo '<input type="radio" name="rescue_mode" value="'.rc_h($val).'" style="margin-top:3px;">';
        echo '<div>';
        echo '<div style="font-weight:800;">'.rc_h($title).'</div>';
        echo '<div style="opacity:.85;">'.rc_h($desc).'</div>';
        echo '</div></div></label>';
    }

    echo '</div>';

    echo '<div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap;">';
    echo '<button class="btn" type="submit">Continue</button>';
    echo '</div>';

    echo '</form>';

    echo '</div>';
    return;
}

/* ------------------------------------------------------------
   If step > 1 but mode missing, force back to stage 0
------------------------------------------------------------ */
if ($mode === '') {
    echo '<div class="alert-box alert-red" style="margin:0;">Wizard state missing. Please choose a rescue type.</div>';
    echo '<div style="margin-top:10px;"><a class="btn btn-secondary" href="?setup=locations">Back</a></div>';
    echo '</div>';
    return;
}

/* ------------------------------------------------------------
   Step 2: mode-specific setup
------------------------------------------------------------ */

if ($mode === 'single' && $step === 2) {
    echo '<div style="font-weight:800; margin-bottom:6px;">Single space setup</div>';
    echo '<div style="opacity:0.85; margin-bottom:10px;">Zone will be set to your rescue name automatically.</div>';

    echo '<form method="post" style="margin:0;">';
    echo '<input type="hidden" name="rc_loc_action" value="single_save_area">';
    echo rc_wizard_hidden($mode, 2);

    echo '<div class="form-row">';
    echo '<div style="flex:1; min-width:260px;">';
    echo '<label>What do you call the place where you keep animals?</label>';
    echo '<input class="form-control" type="text" name="area_name" placeholder="e.g. Shed / Garage / Main Room" required>';
    echo '</div></div>';

    echo '<div style="display:flex; gap:8px; flex-wrap:wrap;">';
    echo '<button class="btn" type="submit">Continue to locations</button>';
    echo '</div>';

    echo '</form>';
}

if ($mode === 'small' && $step === 2) {
    echo '<div style="font-weight:800; margin-bottom:6px;">Small rescue setup</div>';
    echo '<div style="opacity:0.85; margin-bottom:10px;">Zone will be set to your rescue name automatically. Add the different places you keep animals.</div>';

    echo '<form method="post" style="margin:0;">';
    echo '<input type="hidden" name="rc_loc_action" value="small_save_areas">';
    echo rc_wizard_hidden($mode, 2);

    echo '<div id="smallAreaRows">';
    for ($i=0;$i<2;$i++){
        echo '<div class="form-row">';
        echo '<div style="flex:1; min-width:260px;">';
        echo '<label>Area name</label>';
        echo '<input class="form-control" type="text" name="area_name[]" placeholder="e.g. Shed / Spare room / ICU">';
        echo '</div></div>';
    }
    echo '</div>';

    echo '<button type="button" class="btn btn-secondary" onclick="rcAddSmallAreaRow()">+ Add another area</button>';

    echo '<div style="height:10px;"></div>';
    echo '<button class="btn" type="submit">Continue to locations</button>';
    echo '</form>';
}

if ($mode === 'multi' && $step === 2) {
    echo '<div style="font-weight:800; margin-bottom:6px;">Multi-site setup</div>';
    echo '<div style="opacity:0.85; margin-bottom:10px;">Add your sites or offsite volunteers/carers as Zones.</div>';

    echo '<form method="post" style="margin:0;">';
    echo '<input type="hidden" name="rc_loc_action" value="multi_save_zones">';
    echo rc_wizard_hidden($mode, 2);

    echo '<div id="multiZoneRows">';
    for ($i=0;$i<2;$i++){
        echo '<div class="form-row">';
        echo '<div style="flex:1; min-width:260px;">';
        echo '<label>Zone name</label>';
        echo '<input class="form-control" type="text" name="zone_name[]" placeholder="e.g. Main Site / Offsite - Sarah">';
        echo '</div></div>';
    }
    echo '</div>';

    echo '<button type="button" class="btn btn-secondary" onclick="rcAddMultiZoneRow()">+ Add another zone</button>';

    echo '<div style="height:10px;"></div>';
    echo '<button class="btn" type="submit">Continue to areas</button>';
    echo '</form>';
}

/* ------------------------------------------------------------
   Step 3: locations entry (single/small) or areas entry (multi)
------------------------------------------------------------ */

if ($mode === 'single' && $step === 3) {
    $aid = isset($_POST['single_aid']) ? (int)$_POST['single_aid'] : 0;
    echo '<div style="font-weight:800; margin-bottom:6px;">Add your locations</div>';
    echo '<div style="opacity:0.85; margin-bottom:10px;">Examples: Cage 1, Incubator 1, Tank A.</div>';

    if ($aid <= 0) {
        echo '<div class="alert-box alert-red" style="margin:0;">No area found. Please go back and create the area.</div>';
    } else {
        echo '<form method="post" style="margin:0;">';
        echo '<input type="hidden" name="rc_loc_action" value="save_locations">';
        echo rc_wizard_hidden($mode, 3);

        echo '<div id="locRows_'.$aid.'">';
        for ($i=0;$i<2;$i++){
            echo '<div class="form-row" style="align-items:flex-end;">';

            echo '<div style="flex:2; min-width:220px;"><label>Location name</label>';
            echo '<input class="form-control" type="text" name="locations_by_area['.$aid.'][name][]" value=""></div>';

            echo '<div style="flex:1; min-width:180px;"><label>Type</label>';
            echo '<select class="form-control" name="locations_by_area['.$aid.'][type][]" onchange="rcTypeChanged(this)">';
            echo '<option value="">Select…</option>';
            foreach ($types as $t) echo '<option value="'.rc_h($t).'">'.rc_h($t).'</option>';
            echo '</select></div>';

            echo '<div style="flex:2; min-width:220px; display:none;" class="rc-other-wrap"><label>Other type</label>';
            echo '<input class="form-control" type="text" name="locations_by_area['.$aid.'][other][]" value="" placeholder="Specify type"></div>';

            echo '<div style="flex:1; min-width:140px;"><label>Max occupancy</label>';
            echo '<input class="form-control" type="number" min="1" step="1" name="locations_by_area['.$aid.'][cap][]" value="1"></div>';

            echo '</div>';
        }
        echo '</div>';

        echo '<button type="button" class="btn btn-secondary" onclick="rcAddLocRow('.$aid.')">+ Add another location</button>';
        echo '<div style="height:10px;"></div>';
        echo '<button class="btn" type="submit">Save locations</button>';
        echo '</form>';
    }
}

if ($mode === 'small' && $step === 3) {
    $zoneName = rc_get_zone_default_name();
    $zid = rc_get_or_create_default_zone($pdo, $centre_id, $zoneName);
    $zAreas = $areasByZone[$zid] ?? [];

    echo '<div style="font-weight:800; margin-bottom:6px;">Add locations (by area)</div>';
    echo '<div style="opacity:0.85; margin-bottom:10px;">Click an area and add the cages/containers inside it.</div>';

    if (!$zAreas) {
        echo '<div class="alert-box alert-red" style="margin:0;">No areas found. Please go back and add at least one area.</div>';
    } else {
        echo '<form method="post" style="margin:0;">';
        echo '<input type="hidden" name="rc_loc_action" value="save_locations">';
        echo rc_wizard_hidden($mode, 3);

        foreach ($zAreas as $idx => $a) {
            $aid = (int)$a['area_id'];
            $open = ($idx === 0);

            echo '<details '.($open?'open':'').' style="margin:0 0 10px 0; border:1px solid rgba(0,0,0,0.06); border-radius:10px; padding:8px 10px;">';
            echo '<summary style="cursor:pointer; font-weight:800;">'.rc_h((string)$a['area_name']).'</summary>';
            echo '<div style="padding:10px 0 0 0;">';

            echo '<div id="locRows_'.$aid.'">';
            echo '<div class="form-row" style="align-items:flex-end;">';

            echo '<div style="flex:2; min-width:220px;"><label>Location name</label>';
            echo '<input class="form-control" type="text" name="locations_by_area['.$aid.'][name][]" value=""></div>';

            echo '<div style="flex:1; min-width:180px;"><label>Type</label>';
            echo '<select class="form-control" name="locations_by_area['.$aid.'][type][]" onchange="rcTypeChanged(this)">';
            echo '<option value="">Select…</option>';
            foreach ($types as $t) echo '<option value="'.rc_h($t).'">'.rc_h($t).'</option>';
            echo '</select></div>';

            echo '<div style="flex:2; min-width:220px; display:none;" class="rc-other-wrap"><label>Other type</label>';
            echo '<input class="form-control" type="text" name="locations_by_area['.$aid.'][other][]" value="" placeholder="Specify type"></div>';

            echo '<div style="flex:1; min-width:140px;"><label>Max occupancy</label>';
            echo '<input class="form-control" type="number" min="1" step="1" name="locations_by_area['.$aid.'][cap][]" value="1"></div>';

            echo '</div></div>';

            echo '<button type="button" class="btn btn-secondary" onclick="rcAddLocRow('.$aid.')">+ Add another location</button>';

            echo '</div></details>';
        }

        echo '<button class="btn" type="submit">Save locations</button>';
        echo '</form>';
    }
}

if ($mode === 'multi' && $step === 3) {
    echo '<div style="font-weight:800; margin-bottom:6px;">Add Areas within each Zone</div>';
    echo '<div style="opacity:0.85; margin-bottom:10px;">Examples: ICU, Rehab Ward, Aviary, Shed.</div>';

    if (!$zones_all) {
        echo '<div class="alert-box alert-red" style="margin:0;">No zones exist yet. Please go back and add at least one zone first.</div>';
    } else {
        echo '<form method="post" style="margin:0;">';
        echo '<input type="hidden" name="rc_loc_action" value="multi_save_areas">';
        echo rc_wizard_hidden($mode, 3);

        foreach ($zones_all as $idx => $z) {
            $zid = (int)$z['zone_id'];
            $open = ($idx === 0);

            echo '<details '.($open?'open':'').' style="margin:0 0 10px 0;">';
            echo '<summary style="cursor:pointer; font-weight:800;">'.rc_h((string)$z['zone_name']).'</summary>';
            echo '<div style="padding:10px 0 0 0;">';

            echo '<div id="areaRows_'.$zid.'">';
            echo '<div class="form-row"><div style="flex:1; min-width:260px;">';
            echo '<label>Area name</label>';
            echo '<input class="form-control" type="text" name="areas_by_zone['.$zid.'][]" value="">';
            echo '</div></div>';
            echo '</div>';

            echo '<button type="button" class="btn btn-secondary" onclick="rcAddAreaRow('.$zid.')">+ Add another area</button>';

            echo '</div></details>';
        }

        echo '<button class="btn" type="submit">Continue to locations</button>';
        echo '</form>';
    }
}

if ($mode === 'multi' && $step === 4) {
    echo '<div style="font-weight:800; margin-bottom:6px;">Add Locations within each Area</div>';
    echo '<div style="opacity:0.85; margin-bottom:10px;">Keep it simple: Cage 1, Incubator 1, Flight Cage A.</div>';

    if (!$areas_all) {
        echo '<div class="alert-box alert-red" style="margin:0;">No areas exist yet. Please add areas first.</div>';
    } else {
        echo '<form method="post" style="margin:0;">';
        echo '<input type="hidden" name="rc_loc_action" value="save_locations">';
        echo rc_wizard_hidden($mode, 4);

        foreach ($zones_all as $zIdx => $z) {
            $zid = (int)$z['zone_id'];
            $zAreas = $areasByZone[$zid] ?? [];
            if (!$zAreas) continue;

            echo '<details '.($zIdx===0?'open':'').' style="margin:0 0 10px 0;">';
            echo '<summary style="cursor:pointer; font-weight:800;">'.rc_h((string)$z['zone_name']).'</summary>';
            echo '<div style="padding:10px 0 0 0;">';

            foreach ($zAreas as $aIdx => $a) {
                $aid = (int)$a['area_id'];
                echo '<details '.($zIdx===0 && $aIdx===0?'open':'').' style="margin:0 0 10px 0; border:1px solid rgba(0,0,0,0.06); border-radius:10px; padding:8px 10px;">';
                echo '<summary style="cursor:pointer; font-weight:800;">'.rc_h((string)$a['area_name']).'</summary>';
                echo '<div style="padding:10px 0 0 0;">';

                echo '<div id="locRows_'.$aid.'">';
                echo '<div class="form-row" style="align-items:flex-end;">';

                echo '<div style="flex:2; min-width:220px;"><label>Location name</label>';
                echo '<input class="form-control" type="text" name="locations_by_area['.$aid.'][name][]" value=""></div>';

                echo '<div style="flex:1; min-width:180px;"><label>Type</label>';
                echo '<select class="form-control" name="locations_by_area['.$aid.'][type][]" onchange="rcTypeChanged(this)">';
                echo '<option value="">Select…</option>';
                foreach ($types as $t) echo '<option value="'.rc_h($t).'">'.rc_h($t).'</option>';
                echo '</select></div>';

                echo '<div style="flex:2; min-width:220px; display:none;" class="rc-other-wrap"><label>Other type</label>';
                echo '<input class="form-control" type="text" name="locations_by_area['.$aid.'][other][]" value="" placeholder="Specify type"></div>';

                echo '<div style="flex:1; min-width:140px;"><label>Max occupancy</label>';
                echo '<input class="form-control" type="number" min="1" step="1" name="locations_by_area['.$aid.'][cap][]" value="1"></div>';

                echo '</div></div>';

                echo '<button type="button" class="btn btn-secondary" onclick="rcAddLocRow('.$aid.')">+ Add another location</button>';

                echo '</div></details>';
            }

            echo '</div></details>';
        }

        echo '<button class="btn" type="submit">Save locations</button>';
        echo '</form>';
    }
}

/* ------------------------------------------------------------
   Step 5: finish
------------------------------------------------------------ */
if ($step === 5) {
    echo '<div style="font-weight:800; margin-bottom:6px;">All set 🎉</div>';
    echo '<div style="opacity:0.9; margin-bottom:10px;">You can edit zones, areas and locations later via <strong>Management &gt; Locations</strong>.</div>';
    echo '<div style="display:flex; gap:8px; flex-wrap:wrap;">';
    echo '<a class="btn btn-secondary" href="?setup=locations">Run again</a>';
    echo '<a class="btn" href="?">Close</a>';
    echo '</div>';
}
?>
<script>
function rcTypeChanged(sel) {
  const row = sel.closest('.form-row');
  if (!row) return;
  const otherWrap = row.querySelector('.rc-other-wrap');
  if (!otherWrap) return;
  otherWrap.style.display = (sel.value === 'Other') ? '' : 'none';
}

function rcAddLocRow(areaId) {
  const wrap = document.getElementById('locRows_' + areaId);
  if (!wrap) return;

  const row = document.createElement('div');
  row.className = 'form-row';
  row.style.cssText = "align-items:flex-end;";
  row.innerHTML = `
    <div style="flex:2; min-width:220px;">
      <label>Location name</label>
      <input class="form-control" type="text" name="locations_by_area[${areaId}][name][]" value="">
    </div>

    <div style="flex:1; min-width:180px;">
      <label>Type</label>
      <select class="form-control" name="locations_by_area[${areaId}][type][]" onchange="rcTypeChanged(this)">
        <option value="">Select…</option>
        <option>Incubator</option>
        <option>Tank</option>
        <option>Pen</option>
        <option>Kennel</option>
        <option>Paddock</option>
        <option>Hutch</option>
        <option>Aviary</option>
        <option>Flight Cage</option>
        <option>Cage</option>
        <option>Bat Box</option>
        <option>Bird Box</option>
        <option>Other</option>
      </select>
    </div>

    <div style="flex:2; min-width:220px; display:none;" class="rc-other-wrap">
      <label>Other type</label>
      <input class="form-control" type="text" name="locations_by_area[${areaId}][other][]" value="" placeholder="Specify type">
    </div>

    <div style="flex:1; min-width:140px;">
      <label>Max occupancy</label>
      <input class="form-control" type="number" min="1" step="1" name="locations_by_area[${areaId}][cap][]" value="1">
    </div>
  `;
  wrap.appendChild(row);
}

function rcAddSmallAreaRow() {
  const wrap = document.getElementById('smallAreaRows');
  if (!wrap) return;
  const row = document.createElement('div');
  row.className = 'form-row';
  row.innerHTML = `
    <div style="flex:1; min-width:260px;">
      <label>Area name</label>
      <input class="form-control" type="text" name="area_name[]" placeholder="e.g. Shed / Spare room / ICU">
    </div>
  `;
  wrap.appendChild(row);
}

function rcAddMultiZoneRow() {
  const wrap = document.getElementById('multiZoneRows');
  if (!wrap) return;
  const row = document.createElement('div');
  row.className = 'form-row';
  row.innerHTML = `
    <div style="flex:1; min-width:260px;">
      <label>Zone name</label>
      <input class="form-control" type="text" name="zone_name[]" placeholder="e.g. Main Site / Offsite - Sarah">
    </div>
  `;
  wrap.appendChild(row);
}

function rcAddAreaRow(zoneId) {
  const wrap = document.getElementById('areaRows_' + zoneId);
  if (!wrap) return;
  const row = document.createElement('div');
  row.className = 'form-row';
  row.innerHTML = `
    <div style="flex:1; min-width:260px;">
      <label>Area name</label>
      <input class="form-control" type="text" name="areas_by_zone[${zoneId}][]" value="">
    </div>
  `;
  wrap.appendChild(row);
}
</script>
<?php
echo '</div>'; // end card
