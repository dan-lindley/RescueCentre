<?php
// /views/print/patient_data.php
// Central data loader for the printable patient record.
// - NO styling / output
// - Expects $pdo (from /connection.php)
// - Accepts $patient_id via ($patient_id variable) OR $_GET['patient_id']
// - Optionally enforces centre scope via $_SESSION['centre_id'] if present

declare(strict_types=1);

if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new RuntimeException('patient_data.php requires $pdo (PDO). Did you include /connection.php first?');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* -----------------------------
   Helpers
----------------------------- */
function _h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function _fmt_dt($v, string $fallback = '—'): string {
    if ($v === null || $v === '' || $v === '0000-00-00' || $v === '0000-00-00 00:00:00') return $fallback;
    try { return (new DateTime((string)$v))->format('d M Y H:i'); } catch (Throwable $e) { return $fallback; }
}

function _fmt_d($v, string $fallback = '—'): string {
    if ($v === null || $v === '' || $v === '0000-00-00') return $fallback;
    try { return (new DateTime((string)$v))->format('d M Y'); } catch (Throwable $e) { return $fallback; }
}

/* -----------------------------
   Inputs + print metadata
----------------------------- */
$patient_id = (int)($patient_id ?? ($_GET['patient_id'] ?? 0));
if ($patient_id <= 0) {
    throw new RuntimeException('Invalid patient_id.');
}

$session_centre_id = (int)($_SESSION['centre_id'] ?? 0);

// For footer provenance (your print template uses these)
$printed_at = date('d M Y H:i');
$printed_by = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
if ($printed_by === '') $printed_by = 'Unknown user';

/* =====================================================================
   SECTION A — PATIENT + CENTRE (scope enforced here)
   Tables:
     - rescue_patients :contentReference[oaicite:0]{index=0}
     - rescue_centres  :contentReference[oaicite:1]{index=1}
===================================================================== */
$patient = [];
$centre  = [];

$stmt = $pdo->prepare("
    SELECT
      p.patient_id, p.name, p.ringed, p.ring_number, p.microchipped, p.microchip_number,
      p.animal_type, p.animal_order, p.animal_species, p.sex, p.status,
      p.staff_wp_id, p.centre_id, p.date_added, p.state, p.transfer_id, p.created_by, p.approx_dob, p.incomplete_fields,
      c.rescue_id, c.rescue_name, c.centre_type, c.email, c.office_tel, c.mobile, c.`24_hour`,
      c.address_line_one, c.address_line_two, c.city, c.postcode, c.coordinates,
      c.accepting_admissions, c.closed_message, c.species_accepted, c.opening_hours
    FROM rescue_patients p
    LEFT JOIN rescue_centres c ON c.rescue_id = p.centre_id
    WHERE p.patient_id = :patient_id
      AND (:session_centre_id = 0 OR p.centre_id = :session_centre_id)
    LIMIT 1
");
$stmt->execute([
    ':patient_id' => $patient_id,
    ':session_centre_id' => $session_centre_id,
]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(403);
    throw new RuntimeException('Not authorised (or patient not found).');
}

$patient = [
    'patient_id'        => (int)$row['patient_id'],
    'centre_id'         => (int)$row['centre_id'],
    'name'              => (string)($row['name'] ?? ''),
    'animal_type'       => (string)($row['animal_type'] ?? ''),
    'animal_order'      => (string)($row['animal_order'] ?? ''),
    'animal_species'    => (string)($row['animal_species'] ?? ''),
    'sex'               => (string)($row['sex'] ?? ''),
    'status'            => (string)($row['status'] ?? ''),
    'ringed'            => (string)($row['ringed'] ?? ''),
    'ring_number'       => (string)($row['ring_number'] ?? ''),
    'microchipped'      => (string)($row['microchipped'] ?? ''),
    'microchip_number'  => (string)($row['microchip_number'] ?? ''),
    'date_added'        => (string)($row['date_added'] ?? ''),
    'approx_dob'        => (string)($row['approx_dob'] ?? ''),
    'state'             => $row['state'],
    'transfer_id'       => (int)($row['transfer_id'] ?? 0),
    'created_by'        => (int)($row['created_by'] ?? 0),
    'incomplete_fields' => $row['incomplete_fields'],
];

$centre = [
    'rescue_id'           => (int)($row['rescue_id'] ?? 0),
    'rescue_name'         => (string)($row['rescue_name'] ?? ''),
    'centre_type'         => (string)($row['centre_type'] ?? ''),
    'email'               => (string)($row['email'] ?? ''),
    'office_tel'          => (string)($row['office_tel'] ?? ''),
    'mobile'              => (string)($row['mobile'] ?? ''),
    'twentyfour_hour'     => (string)($row['24_hour'] ?? ''),
    'address_line_one'    => (string)($row['address_line_one'] ?? ''),
    'address_line_two'    => (string)($row['address_line_two'] ?? ''),
    'city'                => (string)($row['city'] ?? ''),
    'postcode'            => (string)($row['postcode'] ?? ''),
    'coordinates'         => (string)($row['coordinates'] ?? ''),
    'accepting_admissions'=> (string)($row['accepting_admissions'] ?? ''),
    'closed_message'      => (string)($row['closed_message'] ?? ''),
    'species_accepted'    => (string)($row['species_accepted'] ?? ''),
    'opening_hours'       => (string)($row['opening_hours'] ?? ''),
];

/* =====================================================================
   SECTION B — ADMISSION (latest)
   Table: rescue_admissions :contentReference[oaicite:2]{index=2}
===================================================================== */
$admission = null;

$stmt = $pdo->prepare("
    SELECT *
    FROM rescue_admissions
    WHERE patient_id = :patient_id
    ORDER BY admission_id DESC
    LIMIT 1
");
$stmt->execute([':patient_id' => $patient_id]);
$adm = $stmt->fetch(PDO::FETCH_ASSOC);

if ($adm) {
    $admission = [
        'admission_id'        => (int)$adm['admission_id'],
        'patient_id'          => (int)$adm['patient_id'],
        'centre_id'           => (int)$adm['centre_id'],
        'admission_date'      => (string)($adm['admission_date'] ?? ''),
        'age_on_admission'    => (string)($adm['age_on_admission'] ?? ''),
        'presenting_complaint'=> (string)($adm['presenting_complaint'] ?? ''),
        'dehydrated'          => (string)($adm['dehydrated'] ?? ''),
        'starved'             => (string)($adm['starved'] ?? ''),
        'status'              => (string)($adm['status'] ?? ''),
        'current_location'    => (string)($adm['current_location'] ?? ''),
        'collection_location' => (string)($adm['collection_location'] ?? ''),
        'finder_id'           => (int)($adm['finder_id'] ?? 0),
        'finder_name'         => (string)($adm['finder_name'] ?? ''),
        'finder_tel'          => (string)($adm['finder_tel'] ?? ''),
        'consent_to_update'   => (string)($adm['consent_to_update'] ?? ''),
        'disposition'         => (string)($adm['disposition'] ?? ''),
        'disposition_date'    => (string)($adm['disposition_date'] ?? ''),
        'disposition_user'    => (string)($adm['disposition_user'] ?? ''),
        'disposition_centre'  => (string)($adm['disposition_centre'] ?? ''),
        'disposition_comment' => (string)($adm['disposition_comment'] ?? ''),
        'euthanasia_method'   => (string)($adm['euthanasia_method'] ?? ''),
        // intake measurements captured on admission
        'weight'              => $adm['weight'],
        'weight_unit'         => (string)($adm['weight_unit'] ?? ''),
        'measurement'         => $adm['measurement'],
        'measurement_unit'    => (string)($adm['measurement_unit'] ?? ''),
        // narrative
        'hpc'                 => (string)($adm['hpc'] ?? ''),
        'on_examination'      => (string)($adm['on_examination'] ?? ''),
        // weather
        'w_temp'              => $adm['w_temp'],
        'w_wind'              => $adm['w_wind'],
        'w_humidity'          => $adm['w_humidity'],
        'w_rainfall'          => $adm['w_rainfall'],
        'w_freetext'          => (string)($adm['w_freetext'] ?? ''),
        // location
        'location_lat'        => (string)($adm['location_lat'] ?? ''),
        'location_long'       => (string)($adm['location_long'] ?? ''),
        // scores / completeness
        'severity_score'      => $adm['severity_score'],
        'ss_text'             => (string)($adm['ss_text'] ?? ''),
        'bc_score'            => $adm['bc_score'],
        'bcs_text'            => (string)($adm['bcs_text'] ?? ''),
        'species_score'       => $adm['species_score'],
        'age_score'           => $adm['age_score'],
        'incomplete_fields'   => $adm['incomplete_fields'],
    ];
}

/* =====================================================================
   SECTION C — CARE NOTES (all)
   Table: rescue_notes_patients :contentReference[oaicite:3]{index=3}
===================================================================== */
$care_notes = [];

$stmt = $pdo->prepare("
    SELECT note_id, patient_id, message, author, date, deleted, public, image_id
    FROM rescue_notes_patients
    WHERE patient_id = :patient_id
      AND (deleted = 0 OR deleted IS NULL)
    ORDER BY date DESC
");
$stmt->execute([':patient_id' => $patient_id]);
while ($n = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $care_notes[] = [
        'note_id'    => (int)$n['note_id'],
        'date'       => (string)$n['date'],
        'author'     => (string)($n['author'] ?? ''),
        'message'    => (string)($n['message'] ?? ''),
        'public'     => $n['public'],
        'image_id'   => (int)($n['image_id'] ?? 0),
    ];
}

/* =====================================================================
   SECTION D — TREATMENTS (all)
   Table: rescue_treatments :contentReference[oaicite:4]{index=4}
===================================================================== */
$treatments = [];

$stmt = $pdo->prepare("
    SELECT treatment_given_id, patient_id, treatment, treatment_free_text, done_by, date
    FROM rescue_treatments
    WHERE patient_id = :patient_id
    ORDER BY date DESC
");
$stmt->execute([':patient_id' => $patient_id]);
while ($t = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $treatments[] = [
        'treatment_given_id' => (int)$t['treatment_given_id'],
        'date'               => (string)$t['date'],
        'treatment'          => (string)($t['treatment'] ?? ''),
        'detail'             => (string)($t['treatment_free_text'] ?? ''),
        'done_by'            => (string)($t['done_by'] ?? ''),
    ];
}

/* =====================================================================
   SECTION E — PRESCRIPTIONS (all)
   Table: rescue_prescriptions :contentReference[oaicite:5]{index=5}
===================================================================== */
$prescriptions = [];

$stmt = $pdo->prepare("
    SELECT prescription_id, patient_id, centre_id, admission_id, medication, dose, dose_type, duration, frequency, date, route, user_id, by_weight
    FROM rescue_prescriptions
    WHERE patient_id = :patient_id
    ORDER BY date DESC, prescription_id DESC
");
$stmt->execute([':patient_id' => $patient_id]);
while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $prescriptions[] = [
        'prescription_id' => (int)$p['prescription_id'],
        'date'            => (string)($p['date'] ?? ''),
        'medication'      => (string)($p['medication'] ?? ''),
        'dose'            => $p['dose'],
        'dose_type'       => (string)($p['dose_type'] ?? ''),
        'frequency'       => (string)($p['frequency'] ?? ''),
        'duration'        => $p['duration'],
        'route'           => (string)($p['route'] ?? ''),
        'by_weight'       => (int)($p['by_weight'] ?? 0),
        'user_id'         => (int)($p['user_id'] ?? 0),
        'admission_id'    => (int)($p['admission_id'] ?? 0),
        'centre_id'       => (int)($p['centre_id'] ?? 0),
    ];
}

/* =====================================================================
   SECTION F — MEDICATIONS ADMINISTERED (all)
   Table: rescue_medications_given :contentReference[oaicite:6]{index=6}
   Note: medication is stored as text in medication_given (not FK), so lookup table is informational.
===================================================================== */
$medications_given = [];

$stmt = $pdo->prepare("
    SELECT med_adm_id, patient_id, medication_given, dose, date, centre_id, given_by, dose_type, stock_item_used, given_by_id,
           vol_given, batch_given, exp_given, pack_used
    FROM rescue_medications_given
    WHERE patient_id = :patient_id
    ORDER BY date DESC, med_adm_id DESC
");
$stmt->execute([':patient_id' => $patient_id]);
while ($m = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $medications_given[] = [
        'med_adm_id'      => (int)$m['med_adm_id'],
        'date'            => (string)($m['date'] ?? ''),
        'medication_given'=> (string)($m['medication_given'] ?? ''),
        'dose'            => $m['dose'],
        'dose_type'       => (string)($m['dose_type'] ?? ''),
        'given_by'        => (string)($m['given_by'] ?? ''),
        'given_by_id'     => (int)($m['given_by_id'] ?? 0),
        'centre_id'       => (int)($m['centre_id'] ?? 0),
        'stock_item_used' => $m['stock_item_used'],
        'vol_given'       => $m['vol_given'],
        'batch_given'     => (string)($m['batch_given'] ?? ''),
        'exp_given'       => (string)($m['exp_given'] ?? ''),
        'pack_used'       => $m['pack_used'],
    ];
}

/* =====================================================================
   SECTION G — FEEDING (last 10 only)
   Table: rescue_feeding_events :contentReference[oaicite:7]{index=7}
===================================================================== */
$feeding_events = [];

$stmt = $pdo->prepare("
    SELECT feed_id, patient_id, admission_id, centre_id, diet_item_id, feed_at, feed_type, status,
           offered_value, offered_unit, is_estimated,
           remaining_value, remaining_percent,
           consumed_value, consumed_unit,
           notes, created_by, created_at
    FROM rescue_feeding_events
    WHERE patient_id = :patient_id
    ORDER BY feed_at DESC, feed_id DESC
    LIMIT 10
");
$stmt->execute([':patient_id' => $patient_id]);
while ($f = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $feeding_events[] = [
        'feed_id'          => (int)$f['feed_id'],
        'feed_at'          => (string)($f['feed_at'] ?? ''),
        'feed_type'        => (string)($f['feed_type'] ?? ''),
        'status'           => (string)($f['status'] ?? ''),
        'diet_item_id'     => $f['diet_item_id'],
        'offered_value'    => $f['offered_value'],
        'offered_unit'     => (string)($f['offered_unit'] ?? ''),
        'consumed_value'   => $f['consumed_value'],
        'consumed_unit'    => (string)($f['consumed_unit'] ?? ''),
        'remaining_value'  => $f['remaining_value'],
        'remaining_percent'=> $f['remaining_percent'],
        'is_estimated'     => (int)($f['is_estimated'] ?? 0),
        'notes'            => (string)($f['notes'] ?? ''),
        'created_by'       => (int)($f['created_by'] ?? 0),
        'created_at'       => (string)($f['created_at'] ?? ''),
        'admission_id'     => (int)($f['admission_id'] ?? 0),
        'centre_id'        => (int)($f['centre_id'] ?? 0),
    ];
}

/* =====================================================================
   SECTION H — WEIGHTS (all) + MEASUREMENTS (all)
   Tables:
     - rescue_weights       :contentReference[oaicite:8]{index=8}
     - rescue_measurements  :contentReference[oaicite:9]{index=9}
===================================================================== */
$weights = [];
$measurements = [];

$stmt = $pdo->prepare("
    SELECT weight_id, patient_id, date, weight, weight_unit
    FROM rescue_weights
    WHERE patient_id = :patient_id
    ORDER BY date DESC, weight_id DESC
");
$stmt->execute([':patient_id' => $patient_id]);
while ($w = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $weights[] = [
        'weight_id'   => (int)$w['weight_id'],
        'date'        => (string)($w['date'] ?? ''),
        'weight'      => $w['weight'],
        'weight_unit' => (string)($w['weight_unit'] ?? ''),
    ];
}

$stmt = $pdo->prepare("
    SELECT weight_id, patient_id, date, measurement, measurement_unit
    FROM rescue_measurements
    WHERE patient_id = :patient_id
    ORDER BY date DESC, weight_id DESC
");
$stmt->execute([':patient_id' => $patient_id]);
while ($m = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $measurements[] = [
        'measurement_id'   => (int)$m['weight_id'],
        'date'             => (string)($m['date'] ?? ''),
        'measurement'      => $m['measurement'],
        'measurement_unit' => (string)($m['measurement_unit'] ?? ''),
    ];
}

/* =====================================================================
   SECTION I — LAB RESULTS (joined)
   Tables:
     - rescue_labs         :contentReference[oaicite:10]{index=10}
     - rescue_labs_tests   :contentReference[oaicite:11]{index=11}
     - rescue_sample_types :contentReference[oaicite:12]{index=12}
===================================================================== */
$labs = [];

$stmt = $pdo->prepare("
    SELECT
      l.lab_id, l.lab_date, l.sample_type, l.lab_result, l.reported_by,
      l.admission_id, l.patient_id, l.centre_id, l.lab_test, l.is_positive,
      t.lab_test AS lab_test_name, t.lab_category, t.is_notifiable,
      st.sample_type AS sample_type_name
    FROM rescue_labs l
    LEFT JOIN rescue_labs_tests t ON t.l_test_id = l.lab_test
    LEFT JOIN rescue_sample_types st ON st.s_type_id = l.sample_type
    WHERE l.patient_id = :patient_id
    ORDER BY l.lab_date DESC, l.lab_id DESC
");
$stmt->execute([':patient_id' => $patient_id]);
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $labs[] = [
        'lab_id'            => (int)$r['lab_id'],
        'lab_date'          => (string)($r['lab_date'] ?? ''),
        'sample_type_id'    => $r['sample_type'],
        'sample_type_name'  => (string)($r['sample_type_name'] ?? ''),
        'lab_test_id'       => $r['lab_test'],
        'lab_test_name'     => (string)($r['lab_test_name'] ?? ''),
        'lab_category'      => (string)($r['lab_category'] ?? ''),
        'lab_result'        => (string)($r['lab_result'] ?? ''),
        'reported_by'       => (string)($r['reported_by'] ?? ''),
        'is_positive'       => $r['is_positive'],
        'is_notifiable'     => $r['is_notifiable'],
        'admission_id'      => (int)($r['admission_id'] ?? 0),
        'centre_id'         => (int)($r['centre_id'] ?? 0),
    ];
}

/* =====================================================================
   SECTION J — PARTNER LOGS (joined)
   Tables:
     - rescue_partner_log   :contentReference[oaicite:13]{index=13}
     - rescue_partner_types :contentReference[oaicite:14]{index=14}
===================================================================== */
$partner_logs = [];

$stmt = $pdo->prepare("
    SELECT
      pl.p_log_id, pl.partner_type, pl.date, pl.log_number, pl.log_notes,
      pl.centre_id, pl.patient_id, pl.user_id, pl.admission_id, pl.is_crime,
      pt.partner_type AS partner_type_name
    FROM rescue_partner_log pl
    LEFT JOIN rescue_partner_types pt ON pt.p_type_id = pl.partner_type
    WHERE pl.patient_id = :patient_id
    ORDER BY pl.date DESC, pl.p_log_id DESC
");
$stmt->execute([':patient_id' => $patient_id]);
while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $partner_logs[] = [
        'p_log_id'           => (int)$p['p_log_id'],
        'date'               => (string)($p['date'] ?? ''),
        'partner_type_id'    => $p['partner_type'],
        'partner_type_name'  => (string)($p['partner_type_name'] ?? ''),
        'log_number'         => (string)($p['log_number'] ?? ''),
        'log_notes'          => (string)($p['log_notes'] ?? ''),
        'is_crime'           => (string)($p['is_crime'] ?? ''),
        'user_id'            => (int)($p['user_id'] ?? 0),
        'admission_id'       => (int)($p['admission_id'] ?? 0),
        'centre_id'          => (int)($p['centre_id'] ?? 0),
    ];
}

/* =====================================================================
   SECTION K — IMAGES (all) + index for note linking
   Table: rescue_images :contentReference[oaicite:15]{index=15}
   Notes table has image_id column (0 = none). :contentReference[oaicite:16]{index=16}
===================================================================== */
$images = [];
$images_by_id = [];

$stmt = $pdo->prepare("
    SELECT image_id, centre_id, patient_id, image_url, file_name, is_legacy
    FROM rescue_images
    WHERE patient_id = :patient_id
    ORDER BY image_id DESC
");
$stmt->execute([':patient_id' => $patient_id]);
while ($img = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $item = [
        'image_id'   => (int)$img['image_id'],
        'image_url'  => (string)($img['image_url'] ?? ''),
        'file_name'  => (string)($img['file_name'] ?? ''),
        'is_legacy'  => (int)($img['is_legacy'] ?? 0),
        'centre_id'  => (int)($img['centre_id'] ?? 0),
    ];
    $images[] = $item;
    $images_by_id[$item['image_id']] = $item;
}

/* =====================================================================
   SECTION L — MEDICATION LOOKUP TABLE (master)
   Table: rescue_medications :contentReference[oaicite:17]{index=17}
   Purpose: availability for joins/lookup in the print view (optional)
===================================================================== */
$medications_lookup = [];

$stmt = $pdo->query("
    SELECT medication_id, medication_name, class, common_name, description, contraindications, cautions, dose, side_effects
    FROM rescue_medications
    ORDER BY medication_name ASC
");
while ($m = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $medications_lookup[(int)$m['medication_id']] = $m;
}
/* =====================================================================
   SECTION M — MOVEMENTS / TRANSFERS (latest admission)
   Table: rescue_transfers_log (+ joins)
===================================================================== */
$movements = [];

if (!empty($admission['admission_id'])) {
    $stmt = $pdo->prepare("
        SELECT
            t.transfer_id,
            t.centre_id,
            t.patient_id,
            t.admission_id,
            t.event_type,
            t.event_at,
            t.created_by_user_id,
            t.from_location_id,
            t.to_location_id,
            t.disposition_id,
            t.notes,
            lf.location_name AS from_location_name,
            lt.location_name AS to_location_name,
            d.disposition     AS disposition_text
        FROM rescue_transfers_log t
        LEFT JOIN rescue_locations lf ON lf.location_id = t.from_location_id
        LEFT JOIN rescue_locations lt ON lt.location_id = t.to_location_id
        LEFT JOIN rescue_dispositions d ON d.disposition_id = t.disposition_id
        WHERE t.patient_id   = :patient_id
          AND t.admission_id = :admission_id
          AND t.centre_id    = :centre_id
        ORDER BY t.event_at ASC, t.transfer_id ASC
    ");
    $stmt->execute([
        ':patient_id'   => $patient_id,
        ':admission_id' => (int)$admission['admission_id'],
        ':centre_id'    => (int)$patient['centre_id'],
    ]);

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $movements[] = [
            'transfer_id'         => (int)$r['transfer_id'],
            'event_type'          => (string)($r['event_type'] ?? ''),
            'event_at'            => (string)($r['event_at'] ?? ''),
            'from_location_id'    => $r['from_location_id'] !== null ? (int)$r['from_location_id'] : 0,
            'to_location_id'      => $r['to_location_id'] !== null ? (int)$r['to_location_id'] : 0,
            'from_location_name'  => (string)($r['from_location_name'] ?? ''),
            'to_location_name'    => (string)($r['to_location_name'] ?? ''),
            'disposition_id'      => $r['disposition_id'] !== null ? (int)$r['disposition_id'] : 0,
            'disposition_text'    => (string)($r['disposition_text'] ?? ''),
            'notes'               => (string)($r['notes'] ?? ''),
        ];
    }
}
