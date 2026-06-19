<?php

// ======================================================
// CLEAN IMAGE / META HANDLER
// ======================================================

require_once "../dashmain.php";
require_once "../getuserinfo.php";
require_once __DIR__ . '/../operations/audit.php';

ini_set("display_errors", "0");

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ======================================================
// UTILITY HELPERS
// ======================================================

function centre_get(PDO $pdo, int $id): ?array {
    $q = $pdo->prepare("SELECT * FROM rescue_centres WHERE rescue_id = :id LIMIT 1");
    $q->execute([':id' => $id]);
    return $q->fetch(PDO::FETCH_ASSOC) ?: null;
}

function meta_get_or_create(PDO $pdo, int $id): array {
    $q = $pdo->prepare("SELECT * FROM rescue_centre_meta WHERE centre_id = :id LIMIT 1");
    $q->execute([':id' => $id]);
    $m = $q->fetch(PDO::FETCH_ASSOC);

    if ($m) return $m;

    $pdo->prepare("INSERT INTO rescue_centre_meta (centre_id, centre_bio) VALUES (:id,'')")
        ->execute([':id' => $id]);

    return [
        'meta_id' => (int)$pdo->lastInsertId(),
        'centre_id' => $id,
        'centre_bio' => '',
        'centre_logo' => null,
        'centre_profile_image' => null,
        'centre_cover_image' => null,
        'cover_offset' => 0
    ];
}

function centre_image_paths(array $c): array {
    $id = (int)$c['rescue_id'];
    $safe = preg_replace('/[^A-Za-z0-9 _-]/', '', $c['rescue_name']);
    $folder = $id . " " . $safe;

    $base = dirname(__DIR__);  // /public_html/reception
    $dir  = $base . "/user_images/" . $folder;
    $url  = "/user_images/" . $folder;

    return [$dir, $url];
}

function json_out($data) {
    while (ob_get_level()) ob_end_clean();
    header("Content-Type: application/json");
    echo json_encode($data);
    exit;
}

function require_csrf(): void {
    global $lang;

    $sessionToken = $_SESSION['csrf_token'] ?? '';

    // Accept CSRF from either POST field OR header (useful for AJAX)
    $postToken = $_POST['_csrf'] ?? '';
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''; // header names map to HTTP_*

    $token = $postToken ?: $headerToken;

    if (!is_string($sessionToken) || $sessionToken === '' || !is_string($token) || $token === '') {
        http_response_code(403);
        // JSON if it looks like AJAX
        if (!empty($_POST['action'])) json_out(['success' => false, 'message' => $lang['SETTINGS_CSRF_MISSING']]);
        exit($lang['SETTINGS_CSRF_MISSING']);
    }

    if (!hash_equals($sessionToken, $token)) {
        http_response_code(403);
        if (!empty($_POST['action'])) json_out(['success' => false, 'message' => $lang['SETTINGS_CSRF_INVALID']]);
        exit($lang['SETTINGS_CSRF_INVALID']);
    }
}

function centre_table_has_column(PDO $pdo, string $column): bool {
    static $columns = null;
    if ($columns === null) {
        $columns = [];
        try {
            $rows = $pdo->query("SHOW COLUMNS FROM rescue_centres")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $columns[(string)$row['Field']] = true;
            }
        } catch (Throwable $e) {
            $columns = [];
        }
    }

    return isset($columns[$column]);
}

function centre_location_from_nominatim(string $url): array {
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: RescueCentre/1.0 (reception.rescuecentre.org.uk)\r\n",
            'timeout' => 4,
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    if (!is_string($raw) || $raw === '') return ['country_code' => '', 'county' => ''];

    $data = json_decode($raw, true);
    if (isset($data[0]) && is_array($data[0])) $data = $data[0];
    $address = is_array($data['address'] ?? null) ? $data['address'] : [];
    $code = strtoupper(trim((string)($address['country_code'] ?? '')));
    $county = trim((string)(
        $address['county']
        ?? $address['state_district']
        ?? $address['state']
        ?? $address['province']
        ?? $address['region']
        ?? ''
    ));

    return [
        'country_code' => preg_match('/^[A-Z]{2}$/', $code) ? $code : '',
        'county' => $county,
    ];
}

function centre_country_from_nominatim(string $url): string {
    return centre_location_from_nominatim($url)['country_code'];
}

function centre_resolve_location_context(array $post): array {
    $posted = strtoupper(trim((string)($post['country_code'] ?? '')));
    $postedCounty = trim((string)($post['county'] ?? ''));

    if (preg_match('/^[A-Z]{2}$/', $posted) && $postedCounty !== '') {
        return ['country_code' => $posted, 'county' => $postedCounty];
    }

    $lat = trim((string)($post['centre_lat'] ?? ''));
    $lon = trim((string)($post['centre_long'] ?? ''));
    if ($lat !== '' && $lon !== '' && is_numeric($lat) && is_numeric($lon)) {
        $url = 'https://nominatim.openstreetmap.org/reverse?format=json&addressdetails=1'
            . '&lat=' . rawurlencode($lat) . '&lon=' . rawurlencode($lon);
        $location = centre_location_from_nominatim($url);
        if ($location['country_code'] !== '' || $location['county'] !== '') {
            return [
                'country_code' => $posted ?: $location['country_code'],
                'county' => $postedCounty ?: $location['county'],
            ];
        }
    }

    $address = implode(', ', array_filter([
        trim((string)($post['address_line_one'] ?? '')),
        trim((string)($post['address_line_two'] ?? '')),
        trim((string)($post['city'] ?? '')),
        trim((string)($post['postcode'] ?? '')),
    ], static fn(string $part): bool => $part !== ''));

    if ($address !== '') {
        $url = 'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=1&q='
            . rawurlencode($address);
        $location = centre_location_from_nominatim($url);
        if ($location['country_code'] !== '' || $location['county'] !== '') {
            return [
                'country_code' => $posted ?: $location['country_code'],
                'county' => $postedCounty ?: $location['county'],
            ];
        }
    }

    return [
        'country_code' => preg_match('/^[A-Z]{2}$/', $posted) ? $posted : '',
        'county' => $postedCounty,
    ];
}

// ======================================================
// BIND CENTRE TO LOGGED-IN USER (SERVER-SIDE ONLY)
// ======================================================

$action = $_POST['action'] ?? '';

// ✅ Bind from session (source of truth)
$account_id = (int)($_SESSION['account_id'] ?? 0);
$centre_id  = (int)($_SESSION['centre_id'] ?? 0);

if ($account_id <= 0) {
    http_response_code(401);
    exit($lang['SETTINGS_NOT_AUTHENTICATED']);
}
if ($centre_id <= 0) {
    http_response_code(401);
    exit($lang['SETTINGS_NO_CENTRE_BOUND']);
}

// CSRF for all POST requests
require_csrf();

// Validate centre exists
$centre = centre_get($pdo, $centre_id);
if (!$centre) {
    http_response_code(404);
    exit($lang['SETTINGS_CENTRE_NOT_FOUND']);
}

// ======================================================
// 1) AJAX: IMAGE UPLOAD
// ======================================================

if ($action === "upload_image") {

    $type = $_POST['image_type'] ?? "";
    if (!in_array($type, ['logo','profile','cover'], true)) {
        json_out(['success'=>false,'message'=>$lang['SETTINGS_BAD_IMAGE_TYPE']]);
    }

    if (!isset($_FILES["image_file"]) || $_FILES["image_file"]["error"] !== UPLOAD_ERR_OK) {
        json_out(['success'=>false,'message'=>$lang['SETTINGS_UPLOAD_ERROR']]);
    }

    // Use $centre already bound to user
    [$dir, $urlBase] = centre_image_paths($centre);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);

    $tmp  = $_FILES["image_file"]["tmp_name"];
    $orig = $_FILES["image_file"]["name"];
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));

    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
        json_out(['success'=>false,'message'=>$lang['SETTINGS_INVALID_EXTENSION']]);
    }

    // ALWAYS delete all possible existing versions of the same type
    $possible = ['jpg','jpeg','png','gif','webp'];
    foreach ($possible as $ex) {
        $old = $dir . "/" . $type . "." . $ex;
        if (file_exists($old)) @unlink($old);
    }

    // NEW file target
    $filename = $type . "." . $ext;
    $path     = $dir . "/" . $filename;
    $url      = $urlBase . "/" . $filename;

    if (!move_uploaded_file($tmp, $path)) {
        json_out(['success'=>false,'message'=>$lang['SETTINGS_SERVER_REFUSED_MOVE']]);
    }

    // Update DB meta
    $meta    = meta_get_or_create($pdo, $centre_id);
    $meta_id = (int)$meta['meta_id'];

    $field = [
        'logo'    => 'centre_logo',
        'profile' => 'centre_profile_image',
        'cover'   => 'centre_cover_image'
    ][$type];

    $q = $pdo->prepare("UPDATE rescue_centre_meta SET {$field} = :u WHERE meta_id = :m AND centre_id = :c");
    $q->execute([':u'=>$url, ':m'=>$meta_id, ':c'=>$centre_id]);

    $urlWithBust = $url . '?v=' . time();
    json_out(['success'=>true, 'url'=>$urlWithBust]);
}

// ======================================================
// 2) AJAX: SAVE COVER OFFSET
// ======================================================

if ($action === "save_cover_offset") {

    $offset = (int)($_POST['cover_offset'] ?? 0);
    $meta   = meta_get_or_create($pdo, $centre_id);
    $meta_id = (int)$meta['meta_id'];

    $q = $pdo->prepare("
        UPDATE rescue_centre_meta
        SET cover_offset = :o
        WHERE meta_id = :m AND centre_id = :c
    ");
    $q->execute([':o'=>$offset, ':m'=>$meta_id, ':c'=>$centre_id]);

    json_out(['success'=>true]);
}

// ======================================================
// 3) FORM: UPDATE CUSTOM COLOUR
// ======================================================

if ($action === "update_custom_colour") {
    $colour = strtoupper(trim((string)($_POST['custom_colour'] ?? '')));
    if (!preg_match('/^#[0-9A-F]{6}$/', $colour)) {
        header("Location: /management.php?tab=profile&error=" . urlencode($lang['SETTINGS_VALID_HEX_COLOUR']));
        exit;
    }

    $meta = meta_get_or_create($pdo, $centre_id);
    $meta_id = (int)$meta['meta_id'];
    $q = $pdo->prepare("
        UPDATE rescue_centre_meta
        SET custom_colour = :colour
        WHERE meta_id = :meta_id AND centre_id = :centre_id
    ");
    $q->execute([
        ':colour' => $colour,
        ':meta_id' => $meta_id,
        ':centre_id' => $centre_id,
    ]);

    audit_write($pdo, 'centre_custom_colour_updated', 'rescue_centre_meta', null, [
        'centre_id' => $centre_id,
        'custom_colour' => $colour,
    ]);

    header("Location: /management.php?tab=profile&success=" . urlencode($lang['SETTINGS_COLOUR_UPDATED']));
    exit;
}

// ======================================================
// 4) FORM: UPDATE META BIO
// ======================================================

if ($action === "update_meta") {

    $bio  = trim($_POST['centre_bio'] ?? '');
    $meta = meta_get_or_create($pdo, $centre_id);
    $meta_id = (int)$meta['meta_id'];

    $q = $pdo->prepare("
        UPDATE rescue_centre_meta
        SET centre_bio = :b
        WHERE meta_id = :m AND centre_id = :c
    ");
    $q->execute([':b'=>$bio, ':m'=>$meta_id, ':c'=>$centre_id]);

    audit_write($pdo,'centre_meta_updated','rescue_centre_meta',$meta_id,['centre_id'=>$centre_id,'bio'=>$bio]);

    header("Location: /management.php?tab=profile&success=" . urlencode($lang['SETTINGS_UPDATED']));
    exit;
}

// ======================================================
// 5) FORM: UPDATE MAIN rescue_centres FIELDS
// ======================================================

$allowed = [
    "rescue_name","email","office_tel","mobile","24_hour",
    "address_line_one","address_line_two","city","postcode",
    "centre_lat","centre_long","coordinates","species_accepted",
    "opening_hours","accepting_admissions","closed_message"
];

$locationContext = centre_resolve_location_context($_POST);
if ($locationContext['country_code'] !== '' && centre_table_has_column($pdo, 'country_code')) {
    $_POST['country_code'] = $locationContext['country_code'];
    $allowed[] = 'country_code';
}
if (array_key_exists('county', $_POST) && centre_table_has_column($pdo, 'county')) {
    $_POST['county'] = $locationContext['county'];
    $allowed[] = 'county';
}

$updates = [];
$params  = [":id" => $centre_id];

foreach ($allowed as $f) {
    if (!array_key_exists($f, $_POST)) continue;

    // Parameter name (safe)
    $param = preg_replace('/[^A-Za-z0-9_]/', '_', $f);
    if (preg_match('/^[0-9]/', $param)) $param = "f_" . $param;

    // ✅ Backtick column names (fixes 24_hour and avoids reserved words issues)
    $col = "`" . str_replace("`", "``", $f) . "`";

    $updates[] = "$col = :$param";
    $params[":$param"] = $_POST[$f];
}

if ($updates) {
    $sql = "UPDATE rescue_centres SET " . implode(", ", $updates) . " WHERE rescue_id = :id";
    $pdo->prepare($sql)->execute($params);

    audit_write($pdo, 'centre_updated', 'rescue_centres', $centre_id, $params);

    if (array_key_exists('country_code', $_POST)) {
        $_SESSION['country_code'] = $locationContext['country_code'] !== '' ? $locationContext['country_code'] : null;
    }
    if (array_key_exists('county', $_POST)) {
        $_SESSION['county'] = $locationContext['county'] !== '' ? $locationContext['county'] : null;
    }
}

header("Location: /management.php?success=" . urlencode($lang['SETTINGS_UPDATED']));
exit;
