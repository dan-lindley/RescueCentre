<?php
/**
 * controllers/tasks/task_registry.php
 * Human-readable registry of setup tasks.
 */

function rc_setup_tasks_registry(): array
{
    return [
        [
            'id'          => 'centre_profile',
            'title'       => 'Complete your centre profile',
            'description' => 'Add your address, contact details, and map location so your centre information is complete.',
            'action_url'  => 'management.php?tab=centre',
            'done_check'  => 'rc_task_done_centre_profile',
        ],

        [
            'id'          => 'profile_logo',
            'title'       => 'Complete your profile page',
            'description' => 'Upload your centre logo for your profile page and generated documents.',
            'action_url'  => 'management.php?tab=profile',
            'done_check'  => 'rc_task_done_profile_logo',
        ],

        [
            'id'          => 'handover_text',
            'title'       => 'Customise your handover text',
            'description' => 'Review and customise the declaration shown during patient handovers.',
            'action_url'  => 'management.php?tab=config',
            'done_check'  => 'rc_task_done_handover_text',
        ],

        [
            'id'          => 'locations',
            'title'       => 'Locations',
            'description' => 'Set up zones, areas, and physical locations (cages/incubators etc).',
            'module'      => __DIR__ . '/locations.php',
            'done_check'  => 'rc_task_done_locations',
            'skip_action' => 'rc_task_skip_locations',
        ],

        // ✅ NEW TASK: Diet / Feeding
        [
            'id'          => 'diet',
            'title'       => 'Diet / Feeding Setup',
            'description' => 'Add the food items your centre uses (feeds, fluids, supplements) so they’re available everywhere.',
            'module'      => __DIR__ . '/diet.php',
            'done_check'  => 'rc_task_done_diet',
            'skip_action' => 'rc_task_skip_diet',
        ],
    ];
}

function rc_setup_task_get(string $task_id): ?array
{
    foreach (rc_setup_tasks_registry() as $t) {
        if (!empty($t['id']) && $t['id'] === $task_id) return $t;
    }
    return null;
}

function rc_setup_task_is_done(array $task, PDO $pdo, $centre_id): bool
{
    $fn = $task['done_check'] ?? null;
    if (!$fn || !is_string($fn) || !function_exists($fn)) return false;
    try { return (bool)$fn($pdo, $centre_id); } catch (Throwable $e) { return false; }
}

function rc_setup_task_skip(array $task, PDO $pdo, $centre_id): bool
{
    $fn = $task['skip_action'] ?? null;
    if (!$fn || !is_string($fn) || !function_exists($fn)) return false;
    try { return (bool)$fn($pdo, $centre_id); } catch (Throwable $e) { return false; }
}

/* -------------------------------------------------------------------------
   TASK: Centre profile
   Done when: core contact details, address, and map coordinates are present.
   ------------------------------------------------------------------------- */

function rc_task_done_centre_profile(PDO $pdo, $centre_id): bool
{
    $stmt = $pdo->prepare("
        SELECT
            rescue_name,
            email,
            office_tel,
            mobile,
            `24_hour`,
            address_line_one,
            city,
            postcode,
            centre_lat,
            centre_long
        FROM rescue_centres
        WHERE rescue_id = ?
        LIMIT 1
    ");
    $stmt->execute([(string)$centre_id]);
    $centre = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$centre) return false;

    foreach (['rescue_name', 'email', 'address_line_one', 'city', 'postcode'] as $field) {
        if (trim((string)($centre[$field] ?? '')) === '') return false;
    }

    $has_phone = false;
    foreach (['office_tel', 'mobile', '24_hour'] as $field) {
        if (trim((string)($centre[$field] ?? '')) !== '') {
            $has_phone = true;
            break;
        }
    }
    if (!$has_phone) return false;

    $lat = trim((string)($centre['centre_lat'] ?? ''));
    $long = trim((string)($centre['centre_long'] ?? ''));

    return $lat !== '' && $long !== '' && is_numeric($lat) && is_numeric($long);
}

/* -------------------------------------------------------------------------
   TASK: Profile page logo
   Done when: a centre logo has been uploaded.
   ------------------------------------------------------------------------- */

function rc_task_done_profile_logo(PDO $pdo, $centre_id): bool
{
    $stmt = $pdo->prepare("
        SELECT centre_logo
        FROM rescue_centre_meta
        WHERE centre_id = ?
        LIMIT 1
    ");
    $stmt->execute([(string)$centre_id]);

    return trim((string)$stmt->fetchColumn()) !== '';
}

/* -------------------------------------------------------------------------
   TASK: Handover declaration text
   Done when: stored text is present and differs from the built-in defaults.
   ------------------------------------------------------------------------- */

function rc_task_normalise_text(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", trim($text));
    $text = str_replace(["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}"], ["'", "'", '"', '"'], $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    return mb_strtolower(trim((string)$text), 'UTF-8');
}

function rc_task_done_handover_text(PDO $pdo, $centre_id): bool
{
    $stmt = $pdo->prepare("
        SELECT handover_declaration_text
        FROM rescue_centre_meta
        WHERE centre_id = ?
        LIMIT 1
    ");
    $stmt->execute([(string)$centre_id]);
    $stored = rc_task_normalise_text((string)$stmt->fetchColumn());

    if ($stored === '') return false;

    $defaults = [
        "By handing over this animal to the rescue centre, the finder confirms that they transfer ongoing responsibility for the care and welfare of the animal to the rescue.\n\nThe finder understands that their personal details (where provided and consented) may be stored and used for the purposes of providing updates on the animal's progress and for audit/legal purposes in line with GDPR and the centre's privacy policy.",
        "By handing over this animal to the rescue centre, the finder confirms that they transfer ongoing responsibility for the care and welfare of the animal to the rescue.\n\nThe finder understands that their personal details, where provided and consented, may be stored and used for updates and audit or legal purposes in line with GDPR and the centre's privacy policy.",
    ];

    foreach ($defaults as $default) {
        if ($stored === rc_task_normalise_text($default)) return false;
    }

    return true;
}

/* -------------------------------------------------------------------------
   TASK: Locations
   Done when: at least 1 zone, 1 area, and 1 non-deleted location exist.
   Skip creates: default zone + default area + default location.
   ------------------------------------------------------------------------- */

function rc_task_done_locations(PDO $pdo, $centre_id): bool
{
    $centre_id = (string)$centre_id;

    // PDO::execute returns bool; do it properly:
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rescue_zones WHERE centre_id = ? AND (is_active = 1 OR is_active IS NULL)");
    $stmt->execute([$centre_id]);
    $zoneCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rescue_areas WHERE centre_id = ?");
    $stmt->execute([$centre_id]);
    $areaCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rescue_locations WHERE centre_id = ? AND (deleted = 0 OR deleted IS NULL)");
    $stmt->execute([$centre_id]);
    $locCount = (int)$stmt->fetchColumn();

    return ($zoneCount > 0 && $areaCount > 0 && $locCount > 0);
}

function rc_task_skip_locations(PDO $pdo, $centre_id): bool
{
    $centre_id = (string)$centre_id;

    // 1) Ensure a zone exists
    $stmt = $pdo->prepare("SELECT zone_id FROM rescue_zones WHERE centre_id = ? ORDER BY sort_order ASC, zone_id ASC LIMIT 1");
    $stmt->execute([$centre_id]);
    $zone_id = $stmt->fetchColumn();

    if (!$zone_id) {
        $stmt = $pdo->prepare("
            INSERT INTO rescue_zones (centre_id, zone_name, zone_notes, sort_order, is_active)
            VALUES (?, 'Main Rescue', '', 0, 1)
        ");
        if (!$stmt->execute([$centre_id])) return false;
        $zone_id = (int)$pdo->lastInsertId();
    } else {
        $zone_id = (int)$zone_id;
    }

    // 2) Ensure an area exists under that zone
    $stmt = $pdo->prepare("SELECT area_id FROM rescue_areas WHERE centre_id = ? AND zone_id = ? ORDER BY area_id ASC LIMIT 1");
    $stmt->execute([$centre_id, $zone_id]);
    $area_id = $stmt->fetchColumn();

    if (!$area_id) {
        $stmt = $pdo->prepare("
            INSERT INTO rescue_areas (centre_id, zone_id, area_name)
            VALUES (?, ?, 'General')
        ");
        if (!$stmt->execute([$centre_id, $zone_id])) return false;
        $area_id = (int)$pdo->lastInsertId();
    } else {
        $area_id = (int)$area_id;
    }

    // 3) Ensure a non-deleted location exists under that area
    $stmt = $pdo->prepare("
        SELECT location_id
        FROM rescue_locations
        WHERE centre_id = ? AND area_id = ? AND (deleted = 0 OR deleted IS NULL)
        ORDER BY location_id ASC
        LIMIT 1
    ");
    $stmt->execute([$centre_id, $area_id]);
    $loc_id = $stmt->fetchColumn();

    if (!$loc_id) {
        $stmt = $pdo->prepare("
            INSERT INTO rescue_locations (centre_id, area_id, location_name, location_type, max_occupancy, deleted, location_area)
            VALUES (?, ?, 'Cage 1', 'Cage', 1, 0, 'General')
        ");
        if (!$stmt->execute([$centre_id, $area_id])) return false;
    }

    return true;
}

/* -------------------------------------------------------------------------
   TASK: Diet / Feeding
   Done when: at least 1 centre diet item exists (enabled OR not — your choice).
   Skip creates: links a small starter set from master rescue_diet_items.
   ------------------------------------------------------------------------- */

function rc_task_done_diet(PDO $pdo, $centre_id): bool
{
    $centre_id = (string)$centre_id;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rescue_centre_diet_items WHERE centre_id = ?");
    $stmt->execute([$centre_id]);
    return ((int)$stmt->fetchColumn() > 0);
}

// Simple default rule matching your existing helper behaviour
function rc_diet_default_use_within_days(string $category): int
{
    return (strtolower(trim($category)) === 'liquid') ? 730 : 365;
}

function rc_task_skip_diet(PDO $pdo, $centre_id): bool
{
    $centre_id = (string)$centre_id;

    // Already has at least one? then "skip" is effectively done.
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rescue_centre_diet_items WHERE centre_id = ?");
    $stmt->execute([$centre_id]);
    if ((int)$stmt->fetchColumn() > 0) return true;

    // Pull a small starter set from the master list (first 8 by name)
    $stmt = $pdo->prepare("
        SELECT diet_item_id, category
        FROM rescue_diet_items
        ORDER BY name ASC
        LIMIT 8
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if (!$rows) return true; // nothing to add, but don't block setup

    // Insert (ignore duplicates safely by checking first)
    $check = $pdo->prepare("
        SELECT 1
        FROM rescue_centre_diet_items
        WHERE centre_id = ? AND diet_item_id = ?
        LIMIT 1
    ");
    $ins = $pdo->prepare("
        INSERT INTO rescue_centre_diet_items
            (centre_id, diet_item_id, use_within_days, is_enabled, notes)
        VALUES
            (?, ?, ?, 1, '')
    ");

    foreach ($rows as $r) {
        $did = (int)($r['diet_item_id'] ?? 0);
        if ($did <= 0) continue;

        $check->execute([$centre_id, $did]);
        if ($check->fetchColumn()) continue;

        $uwd = rc_diet_default_use_within_days((string)($r['category'] ?? ''));
        if (!$ins->execute([$centre_id, $did, (int)$uwd])) return false;
    }

    return true;
}
