<?php
// controllers/locations_handler.php

require_once "../dashmain.php";
require_once "../getuserinfo.php";
require_once __DIR__ . '/../operations/audit.php';

$redirect_areas    = "../locations.php?tab=areas";
$redirect_location = "../locations.php?tab=locations";
$redirect_zones    = "../locations.php?tab=zones";

// Minimal flash helper
if (!function_exists('set_flash')) {
    function set_flash(string $type, string $message): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }
}

// Ensure POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $redirect_areas");
    exit;
}

$action    = isset($_POST['action']) ? trim($_POST['action']) : '';
$centre_id = isset($_POST['centre_id']) ? (int)$_POST['centre_id'] : (int)$centre_id;

// ------------------------------------------------------------
// ADD LOCATION  (UPDATED: supports area_id + keeps legacy text)
// ------------------------------------------------------------
if ($action === 'add_location') {

    $name  = trim((string)($_POST['location_name'] ?? ''));
    $type  = trim((string)($_POST['location_type'] ?? ''));
    $max   = (isset($_POST['max_occupancy']) && $_POST['max_occupancy'] !== '') ? (int)$_POST['max_occupancy'] : null;

    $area_id  = isset($_POST['area_id']) ? (int)$_POST['area_id'] : 0;
    $area_txt = trim((string)($_POST['location_area'] ?? ''));

    if ($name === '') {
        set_flash('red', 'Location name is required.');
        header("Location: $redirect_zones");
        exit;
    }

    // If area_id provided, lookup area_name and force-sync legacy text
    if ($area_id > 0) {
        $stmt = $pdo->prepare("SELECT area_name FROM rescue_areas WHERE area_id=:aid AND centre_id=:cid LIMIT 1");
        $stmt->execute([':aid' => $area_id, ':cid' => $centre_id]);
        $found = $stmt->fetchColumn();
        if (!$found) {
            set_flash('red', 'Invalid area selected.');
            header("Location: $redirect_zones");
            exit;
        }
        $area_txt = (string)$found;
    }

    if ($area_txt === '') {
        set_flash('red', 'Area is required.');
        header("Location: $redirect_zones");
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO rescue_locations
            (centre_id, location_name, location_type, max_occupancy, location_area, area_id, deleted)
        VALUES
            (:centre, :name, :type, :max, :area_txt, :area_id, 0)
    ");
    $stmt->execute([
        ':centre'   => $centre_id,
        ':name'     => $name,
        ':type'     => $type,
        ':max'      => $max,
        ':area_txt' => $area_txt,
        ':area_id'  => ($area_id > 0 ? $area_id : null),
    ]);

    $new_id = (int)$pdo->lastInsertId();

    audit_write(
        $pdo,
        'location_added',
        'rescue_locations',
        $new_id,
        [
            'centre_id'      => $centre_id,
            'location_name'  => $name,
            'location_type'  => $type,
            'max_occupancy'  => $max,
            'location_area'  => $area_txt,
            'area_id'        => ($area_id > 0 ? $area_id : null),
        ]
    );

    header("Location: $redirect_zones");
    exit;
}

// ------------------------------------------------------------
// UPDATE LOCATION (UPDATED: supports area_id + keeps legacy text synced)
// ------------------------------------------------------------
if ($action === 'update_location') {

    $loc_id = (int)($_POST['location_id'] ?? 0);
    $name   = trim((string)($_POST['location_name'] ?? ''));
    $type   = trim((string)($_POST['location_type'] ?? ''));
    $max    = (isset($_POST['max_occupancy']) && $_POST['max_occupancy'] !== '') ? (int)$_POST['max_occupancy'] : null;

    $area_id  = isset($_POST['area_id']) ? (int)$_POST['area_id'] : 0;
    $area_txt = trim((string)($_POST['location_area'] ?? ''));

    if ($loc_id <= 0 || $name === '') {
        set_flash('red', 'Location update missing required fields.');
        header("Location: $redirect_zones");
        exit;
    }

    if ($area_id > 0) {
        $stmt = $pdo->prepare("SELECT area_name FROM rescue_areas WHERE area_id=:aid AND centre_id=:cid LIMIT 1");
        $stmt->execute([':aid' => $area_id, ':cid' => $centre_id]);
        $found = $stmt->fetchColumn();
        if (!$found) {
            set_flash('red', 'Invalid area selected.');
            header("Location: $redirect_zones");
            exit;
        }
        $area_txt = (string)$found;
    }

    if ($area_txt === '') {
        set_flash('red', 'Area is required.');
        header("Location: $redirect_zones");
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_locations
        SET location_name = :name,
            location_type = :type,
            max_occupancy = :max,
            location_area = :area_txt,
            area_id       = :area_id
        WHERE location_id = :id
          AND centre_id   = :centre
    ");
    $stmt->execute([
        ':name'     => $name,
        ':type'     => $type,
        ':max'      => $max,
        ':area_txt' => $area_txt,
        ':area_id'  => ($area_id > 0 ? $area_id : null),
        ':id'       => $loc_id,
        ':centre'   => $centre_id
    ]);

    audit_write(
        $pdo,
        'location_updated',
        'rescue_locations',
        $loc_id,
        [
            'centre_id'      => $centre_id,
            'location_name'  => $name,
            'location_type'  => $type,
            'max_occupancy'  => $max,
            'location_area'  => $area_txt,
            'area_id'        => ($area_id > 0 ? $area_id : null),
        ]
    );

    header("Location: $redirect_zones");
    exit;
}

// ------------------------------------------------------------
// DELETE LOCATION (SOFT)
// ------------------------------------------------------------
if ($action === 'delete_location') {

    $loc_id = (int)($_POST['location_id'] ?? 0);

    $stmt = $pdo->prepare("
        UPDATE rescue_locations
        SET deleted = 1
        WHERE location_id = :id AND centre_id = :centre
    ");
    $stmt->execute([
        ':id'     => $loc_id,
        ':centre' => $centre_id
    ]);

    audit_write(
        $pdo,
        'location_deleted',
        'rescue_locations',
        $loc_id,
        ['centre_id' => $centre_id]
    );

    set_flash('green', 'Location deleted.');
    header("Location: $redirect_zones");
    exit;
}

// ------------------------------------------------------------
// ADD AREA (UPDATED: supports zone_id directly)
// ------------------------------------------------------------
if ($action === 'add_area') {

    $name    = trim((string)($_POST['area_name'] ?? ''));
    $zone_id = isset($_POST['zone_id']) ? (int)$_POST['zone_id'] : 0;

    if ($name === '') {
        set_flash('red', 'Area name is required.');
        header("Location: $redirect_zones");
        exit;
    }

    if ($zone_id > 0) {
        // Ensure zone belongs to centre
        $chk = $pdo->prepare("SELECT zone_id FROM rescue_zones WHERE zone_id=:zid AND centre_id=:cid LIMIT 1");
        $chk->execute([':zid' => $zone_id, ':cid' => $centre_id]);
        if (!$chk->fetchColumn()) {
            set_flash('red', 'Invalid zone selected for area.');
            header("Location: $redirect_zones");
            exit;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO rescue_areas (centre_id, area_name, zone_id)
        VALUES (:centre, :name, :zone_id)
    ");
    $stmt->execute([
        ':centre'  => $centre_id,
        ':name'    => $name,
        ':zone_id' => ($zone_id > 0 ? $zone_id : null)
    ]);

    $new_id = (int)$pdo->lastInsertId();

    audit_write(
        $pdo,
        'area_added',
        'rescue_areas',
        $new_id,
        [
            'centre_id' => $centre_id,
            'area_name' => $name,
            'zone_id'   => ($zone_id > 0 ? $zone_id : null)
        ]
    );

    set_flash('green', 'Area added.');
    header("Location: $redirect_zones");
    exit;
}

// ------------------------------------------------------------
// UPDATE AREA
// ------------------------------------------------------------
if ($action === 'update_area') {

    $area_id = (int)($_POST['area_id'] ?? 0);
    $name    = trim((string)($_POST['area_name'] ?? ''));

    if ($area_id <= 0 || $name === '') {
        set_flash('red', 'Area update missing required fields.');
        header("Location: $redirect_zones");
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_areas
        SET area_name = :name
        WHERE area_id = :id AND centre_id = :centre
        LIMIT 1
    ");
    $stmt->execute([
        ':name'   => $name,
        ':id'     => $area_id,
        ':centre' => $centre_id
    ]);

    audit_write(
        $pdo,
        'area_updated',
        'rescue_areas',
        $area_id,
        [
            'centre_id' => $centre_id,
            'area_name' => $name
        ]
    );

    set_flash('green', 'Area updated.');
    header("Location: $redirect_zones");
    exit;
}
// ------------------------------------------------------------
// UPDATE ZONE
// ------------------------------------------------------------
if ($action === 'update_zone') {

    $zone_id    = (int)($_POST['zone_id'] ?? 0);
    $zone_name  = trim((string)($_POST['zone_name'] ?? ''));
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    if ($zone_id <= 0 || $zone_name === '') {
        set_flash('red', 'Zone update missing required fields.');
        header("Location: $redirect_zones");
        exit;
    }

    $zone_name = preg_replace('/\s+/', ' ', $zone_name);

    try {
        $stmt = $pdo->prepare("
            UPDATE rescue_zones
            SET zone_name = :zone_name,
                is_active = :is_active
            WHERE zone_id = :zone_id
              AND centre_id = :centre_id
            LIMIT 1
        ");
        $stmt->execute([
            ':zone_name' => $zone_name,
            ':is_active' => $is_active,
            ':zone_id'   => $zone_id,
            ':centre_id' => $centre_id
        ]);

        audit_write(
            $pdo,
            'zone_updated',
            'rescue_zones',
            $zone_id,
            [
                'centre_id' => $centre_id,
                'zone_name' => $zone_name,
                'is_active' => $is_active
            ]
        );

        set_flash('green', 'Zone updated.');
        header("Location: $redirect_zones");
        exit;

    } catch (PDOException $e) {
        $dup = (int)($e->errorInfo[1] ?? 0) === 1062;
        set_flash('red', $dup ? 'A zone with that name already exists.' : 'Could not update zone.');
        header("Location: $redirect_zones");
        exit;
    }
}
// ------------------------------------------------------------
// DELETE AREA (only if absolutely no locations remain, including soft deleted)
// ------------------------------------------------------------
if ($action === 'delete_area') {

    $area_id = (int)($_POST['area_id'] ?? 0);

    if ($area_id <= 0) {
        set_flash('red', 'Invalid area selected.');
        header("Location: $redirect_zones");
        exit;
    }

    // Ensure area belongs to centre and get area_name for legacy fallback match
    $stmt = $pdo->prepare("
        SELECT area_name
        FROM rescue_areas
        WHERE area_id = :aid
          AND centre_id = :cid
        LIMIT 1
    ");
    $stmt->execute([
        ':aid' => $area_id,
        ':cid' => $centre_id
    ]);
    $area_name = $stmt->fetchColumn();

    if (!$area_name) {
        set_flash('red', 'Area not found.');
        header("Location: $redirect_zones");
        exit;
    }

    // IMPORTANT:
    // Block delete if ANY locations still exist for this area,
    // including soft deleted ones, whether linked by area_id or legacy text
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM rescue_locations
        WHERE centre_id = :cid
          AND (
                area_id = :aid
                OR (
                    (area_id IS NULL OR area_id = 0)
                    AND location_area IS NOT NULL
                    AND TRIM(location_area) = TRIM(:aname)
                )
          )
    ");
    $stmt->execute([
        ':cid'   => $centre_id,
        ':aid'   => $area_id,
        ':aname' => (string)$area_name
    ]);
    $location_count = (int)$stmt->fetchColumn();

    if ($location_count > 0) {
        set_flash('red', 'To delete the area or zone please click the manage locations and perform a hard delete for all locations in this area');
        header("Location: $redirect_zones");
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            DELETE FROM rescue_areas
            WHERE area_id = :aid
              AND centre_id = :cid
            LIMIT 1
        ");
        $stmt->execute([
            ':aid' => $area_id,
            ':cid' => $centre_id
        ]);

        audit_write(
            $pdo,
            'area_deleted',
            'rescue_areas',
            $area_id,
            [
                'centre_id' => $centre_id,
                'area_name' => (string)$area_name
            ]
        );

        set_flash('green', 'Area deleted.');
    } catch (Throwable $e) {
        set_flash('red', 'To delete the area or zone please click the manage locations and perform a hard delete for all locations in this area');
    }

    header("Location: $redirect_zones");
    exit;
}
// ------------------------------------------------------------
// DELETE ZONE (only if no areas/locations remain, including soft deleted)
// ------------------------------------------------------------
if ($action === 'delete_zone') {

    $zone_id = (int)($_POST['zone_id'] ?? 0);

    if ($zone_id <= 0) {
        set_flash('red', 'Invalid zone selected.');
        header("Location: $redirect_zones");
        exit;
    }

    // Ensure zone belongs to centre
    $stmt = $pdo->prepare("
        SELECT zone_name
        FROM rescue_zones
        WHERE zone_id = :zid
          AND centre_id = :cid
        LIMIT 1
    ");
    $stmt->execute([
        ':zid' => $zone_id,
        ':cid' => $centre_id
    ]);
    $zone_name = $stmt->fetchColumn();

    if (!$zone_name) {
        set_flash('red', 'Zone not found.');
        header("Location: $redirect_zones");
        exit;
    }

    // First: block if any areas still exist in this zone
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM rescue_areas
        WHERE centre_id = :cid
          AND zone_id = :zid
    ");
    $stmt->execute([
        ':cid' => $centre_id,
        ':zid' => $zone_id
    ]);
    $area_count = (int)$stmt->fetchColumn();

    if ($area_count > 0) {
        set_flash('red', 'To delete the area or zone please click the manage locations and perform a hard delete for all locations in this area');
        header("Location: $redirect_zones");
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            DELETE FROM rescue_zones
            WHERE zone_id = :zid
              AND centre_id = :cid
            LIMIT 1
        ");
        $stmt->execute([
            ':zid' => $zone_id,
            ':cid' => $centre_id
        ]);

        audit_write(
            $pdo,
            'zone_deleted',
            'rescue_zones',
            $zone_id,
            [
                'centre_id' => $centre_id,
                'zone_name' => (string)$zone_name
            ]
        );

        set_flash('green', 'Zone deleted.');
    } catch (Throwable $e) {
        set_flash('red', 'To delete the area or zone please click the manage locations and perform a hard delete for all locations in this area');
    }

    header("Location: $redirect_zones");
    exit;
}
// ------------------------------------------------------------
// LINK AREA TO ZONE (UNCHANGED)
// ------------------------------------------------------------
if ($action === 'link_area_to_zone') {

    $area_id = (int)($_POST['area_id'] ?? 0);
    $zone_id = (int)($_POST['zone_id'] ?? 0);

    if ($area_id <= 0 || $zone_id <= 0) {
        set_flash('red', 'Please select an area and a zone.');
        header("Location: $redirect_zones");
        exit;
    }

    $chk = $pdo->prepare("
        SELECT zone_id
        FROM rescue_zones
        WHERE zone_id = :zid AND centre_id = :cid
        LIMIT 1
    ");
    $chk->execute([':zid' => $zone_id, ':cid' => $centre_id]);
    if (!$chk->fetchColumn()) {
        set_flash('red', 'Invalid zone selected.');
        header("Location: $redirect_zones");
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_areas
        SET zone_id = :zid
        WHERE area_id = :aid
          AND centre_id = :cid
        LIMIT 1
    ");
    $stmt->execute([
        ':zid' => $zone_id,
        ':aid' => $area_id,
        ':cid' => $centre_id
    ]);

    audit_write(
        $pdo,
        'area_linked_to_zone',
        'rescue_areas',
        $area_id,
        [
            'centre_id' => $centre_id,
            'area_id'   => $area_id,
            'zone_id'   => $zone_id
        ]
    );

    set_flash('green', 'Area linked to zone.');
    header("Location: $redirect_zones");
    exit;
}

// ------------------------------------------------------------
// CREATE ZONE (UNCHANGED)
// ------------------------------------------------------------
if ($action === 'create_zone') {

    $zone_name = trim((string)($_POST['zone_name'] ?? ''));
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($zone_name === '') {
        set_flash('red', 'Zone name is required.');
        header("Location: $redirect_zones");
        exit;
    }

    $zone_name = preg_replace('/\s+/', ' ', $zone_name);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO rescue_zones (centre_id, zone_name, is_active)
            VALUES (:centre_id, :zone_name, :is_active)
        ");
        $stmt->execute([
            ':centre_id' => $centre_id,
            ':zone_name' => $zone_name,
            ':is_active' => $is_active
        ]);

        $new_id = (int)$pdo->lastInsertId();

        audit_write(
            $pdo,
            'zone_created',
            'rescue_zones',
            $new_id,
            [
                'centre_id' => $centre_id,
                'zone_name' => $zone_name,
                'is_active' => $is_active
            ]
        );

        set_flash('green', 'Zone created.');
        header("Location: $redirect_zones");
        exit;

    } catch (PDOException $e) {
        $dup = (int)($e->errorInfo[1] ?? 0) === 1062;
        set_flash('red', $dup ? 'A zone with that name already exists.' : 'Could not create zone.');
        header("Location: $redirect_zones");
        exit;
    }
}
// ------------------------------------------------------------
// RESTORE LOCATION (from deleted bin)
// ------------------------------------------------------------
if ($action === 'restore_location') {
    $loc_id = (int)($_POST['location_id'] ?? 0);
    if ($loc_id <= 0) {
        set_flash('red', 'Invalid location.');
        header("Location: $redirect_areas");
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_locations
        SET deleted = 0
        WHERE location_id = :id AND centre_id = :centre
        LIMIT 1
    ");
    $stmt->execute([':id' => $loc_id, ':centre' => $centre_id]);

    audit_write($pdo, 'location_restored', 'rescue_locations', $loc_id, ['centre_id' => $centre_id]);

    set_flash('green', 'Location restored.');
    header("Location: $redirect_areas");
    exit;
}

// ------------------------------------------------------------
// HARD DELETE LOCATION (blocked if referenced elsewhere)
// NOTE: we can only enforce safety if we know reference tables.
// This version checks for obvious FK-style references in the DB schema.
// ------------------------------------------------------------
if ($action === 'hard_delete_location') {
    $loc_id = (int)($_POST['location_id'] ?? 0);
    if ($loc_id <= 0) {
        set_flash('red', 'Invalid location.');
        header("Location: $redirect_areas");
        exit;
    }

    // Try to detect references in other tables that have a 'location_id' column.
    // If any references exist, block hard delete.
    $ref_count = 0;
    try {
        $q = $pdo->query("
            SELECT TABLE_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND COLUMN_NAME = 'location_id'
              AND TABLE_NAME <> 'rescue_locations'
        ");
        $tables = $q->fetchAll(PDO::FETCH_COLUMN) ?: [];

        foreach ($tables as $t) {
            // also check if table has centre_id column
            $q2 = $pdo->prepare("
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :t
                  AND COLUMN_NAME = 'centre_id'
            ");
            $q2->execute([':t' => $t]);
            $has_centre = (int)$q2->fetchColumn() > 0;

            if ($has_centre) {
                $sql = "SELECT COUNT(*) FROM `$t` WHERE location_id = :lid AND centre_id = :cid";
                $st = $pdo->prepare($sql);
                $st->execute([':lid' => $loc_id, ':cid' => $centre_id]);
            } else {
                $sql = "SELECT COUNT(*) FROM `$t` WHERE location_id = :lid";
                $st = $pdo->prepare($sql);
                $st->execute([':lid' => $loc_id]);
            }

            $c = (int)$st->fetchColumn();
            if ($c > 0) {
                $ref_count += $c;
                break; // one is enough to block
            }
        }
    } catch (Throwable $e) {
        // If schema probing fails, be conservative: block hard delete.
        $ref_count = 1;
    }

    if ($ref_count > 0) {
        set_flash('red', 'Hard delete blocked: this location appears to be referenced elsewhere.');
        header("Location: $redirect_areas");
        exit;
    }

    $stmt = $pdo->prepare("
        DELETE FROM rescue_locations
        WHERE location_id = :id AND centre_id = :centre
        LIMIT 1
    ");
    $stmt->execute([':id' => $loc_id, ':centre' => $centre_id]);

    audit_write($pdo, 'location_hard_deleted', 'rescue_locations', $loc_id, ['centre_id' => $centre_id]);

    set_flash('green', 'Location permanently deleted.');
    header("Location: $redirect_areas");
    exit;
}

// ------------------------------------------------------------
// FIX LOCATION LINK (set area_id and sync legacy location_area)
// ------------------------------------------------------------
if ($action === 'fix_location_link') {
    $loc_id  = (int)($_POST['location_id'] ?? 0);
    $area_id = (int)($_POST['area_id'] ?? 0);

    if ($loc_id <= 0 || $area_id <= 0) {
        set_flash('red', 'Invalid link request.');
        header("Location: $redirect_areas");
        exit;
    }

    // Ensure area belongs to this centre and get area_name for legacy sync
    $stmt = $pdo->prepare("SELECT area_name FROM rescue_areas WHERE area_id=:aid AND centre_id=:cid LIMIT 1");
    $stmt->execute([':aid' => $area_id, ':cid' => $centre_id]);
    $area_name = $stmt->fetchColumn();

    if (!$area_name) {
        set_flash('red', 'Area not found for this centre.');
        header("Location: $redirect_areas");
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_locations
        SET area_id = :aid,
            location_area = :an
        WHERE location_id = :lid
          AND centre_id = :cid
        LIMIT 1
    ");
    $stmt->execute([
        ':aid' => $area_id,
        ':an'  => (string)$area_name,
        ':lid' => $loc_id,
        ':cid' => $centre_id
    ]);

    audit_write($pdo, 'location_link_repaired', 'rescue_locations', $loc_id, [
        'centre_id' => $centre_id,
        'area_id' => $area_id,
        'location_area' => (string)$area_name
    ]);

    set_flash('green', 'Location link updated.');
    header("Location: $redirect_areas");
    exit;
}

// ------------------------------------------------------------
// FIX AREA -> ZONE LINK
// ------------------------------------------------------------
if ($action === 'fix_area_zone_link') {
    $area_id = (int)($_POST['area_id'] ?? 0);
    $zone_id = (int)($_POST['zone_id'] ?? 0);

    if ($area_id <= 0 || $zone_id <= 0) {
        set_flash('red', 'Invalid assignment.');
        header("Location: $redirect_areas");
        exit;
    }

    // Ensure zone belongs to centre
    $chk = $pdo->prepare("SELECT zone_id FROM rescue_zones WHERE zone_id=:zid AND centre_id=:cid LIMIT 1");
    $chk->execute([':zid' => $zone_id, ':cid' => $centre_id]);
    if (!$chk->fetchColumn()) {
        set_flash('red', 'Invalid zone.');
        header("Location: $redirect_areas");
        exit;
    }

    // Ensure area belongs to centre
    $chk = $pdo->prepare("SELECT area_id FROM rescue_areas WHERE area_id=:aid AND centre_id=:cid LIMIT 1");
    $chk->execute([':aid' => $area_id, ':cid' => $centre_id]);
    if (!$chk->fetchColumn()) {
        set_flash('red', 'Invalid area.');
        header("Location: $redirect_areas");
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE rescue_areas
        SET zone_id = :zid
        WHERE area_id = :aid AND centre_id = :cid
        LIMIT 1
    ");
    $stmt->execute([':zid' => $zone_id, ':aid' => $area_id, ':cid' => $centre_id]);

    audit_write($pdo, 'area_zone_assigned', 'rescue_areas', $area_id, [
        'centre_id' => $centre_id,
        'zone_id' => $zone_id
    ]);

    set_flash('green', 'Area assigned to zone.');
    header("Location: $redirect_areas");
    exit;
}

// ------------------------------------------------------------
// BULK: Backfill area_id where a UNIQUE legacy text match exists
// ------------------------------------------------------------
if ($action === 'bulk_backfill_area_id') {
    // Build a mapping of normalised area_name -> unique area_id (only if unique)
    $stmt = $pdo->prepare("
        SELECT area_name, COUNT(*) AS c, MIN(area_id) AS area_id
        FROM rescue_areas
        WHERE centre_id = :cid
        GROUP BY area_name
        HAVING COUNT(*) = 1
    ");
    $stmt->execute([':cid' => $centre_id]);
    $unique = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $map = [];
    foreach ($unique as $u) {
        $k = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string)$u['area_name'])));
        $map[$k] = (int)$u['area_id'];
    }

    // Fetch candidates
    $stmt = $pdo->prepare("
        SELECT location_id, location_area
        FROM rescue_locations
        WHERE centre_id = :cid
          AND (deleted=0 OR deleted IS NULL)
          AND (area_id IS NULL OR area_id=0)
          AND location_area IS NOT NULL
          AND TRIM(location_area) <> ''
    ");
    $stmt->execute([':cid' => $centre_id]);
    $locs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $updated = 0;
    foreach ($locs as $l) {
        $k = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string)$l['location_area'])));
        if (!isset($map[$k])) continue;

        $aid = $map[$k];
        $stmtU = $pdo->prepare("
            UPDATE rescue_locations
            SET area_id = :aid
            WHERE location_id = :lid AND centre_id = :cid
            LIMIT 1
        ");
        $stmtU->execute([':aid' => $aid, ':lid' => (int)$l['location_id'], ':cid' => $centre_id]);
        $updated += (int)$stmtU->rowCount();
    }

    audit_write($pdo, 'bulk_backfill_area_id', 'rescue_locations', 0, [
        'centre_id' => $centre_id,
        'updated' => $updated
    ]);

    set_flash('green', "Backfill complete. Updated {$updated} location(s).");
    header("Location: $redirect_areas");
    exit;
}

// ------------------------------------------------------------
// BULK: Sync location_area text from area_id (normalise legacy text)
// ------------------------------------------------------------
if ($action === 'bulk_sync_location_area') {
    $stmt = $pdo->prepare("
        UPDATE rescue_locations l
        JOIN rescue_areas a
          ON a.area_id = l.area_id AND a.centre_id = l.centre_id
        SET l.location_area = a.area_name
        WHERE l.centre_id = :cid
          AND (l.deleted=0 OR l.deleted IS NULL)
          AND l.area_id IS NOT NULL AND l.area_id <> 0
    ");
    $stmt->execute([':cid' => $centre_id]);

    $updated = (int)$stmt->rowCount();

    audit_write($pdo, 'bulk_sync_location_area', 'rescue_locations', 0, [
        'centre_id' => $centre_id,
        'updated' => $updated
    ]);

    set_flash('green', "Sync complete. Updated {$updated} location(s).");
    header("Location: $redirect_areas");
    exit;
}

// ------------------------------------------------------------
// FALLBACK
// ------------------------------------------------------------
header("Location: $redirect_location");
exit;
