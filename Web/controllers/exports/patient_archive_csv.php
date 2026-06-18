<?php
// controllers/exports/patient_archive_csv.php
// Outputs CSV only (no wrapper). Safe for headers.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../main.php';   // adjust if your path differs
check_loggedin($pdo);

// ---- Get centre_id (use whatever your app already uses) ----
// Option A: centre_id already stored in session
$centre_id_int = isset($_SESSION['centre_id']) ? (int)$_SESSION['centre_id'] : 0;

// Option B (fallback): if your system stores centre_id on accounts table etc,
// replace this block with your known-good centre_id resolution.
if ($centre_id_int <= 0 && isset($centre_id)) {
    $centre_id_int = (int)$centre_id;
}

if ($centre_id_int <= 0) {
    http_response_code(403);
    echo "Error: centre_id not available.";
    exit;
}

// ---- export type ----
$export = $_GET['export'] ?? '';
if (!in_array($export, ['patients', 'patients_last_admission'], true)) {
    http_response_code(400);
    echo "Invalid export.";
    exit;
}

// ---- send headers ----
$stamp = date('Y-m-d_His');
$filename = ($export === 'patients')
    ? "archive_patients_{$centre_id_int}_{$stamp}.csv"
    : "archive_patients_last_admission_{$centre_id_int}_{$stamp}.csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

if ($export === 'patients') {

    $sql_export = "
        SELECT DISTINCT
            p.patient_id,
            p.name,
            p.sex,
            p.animal_type,
            p.animal_species,
            p.status,
            p.date_added
        FROM rescue_patients p
        INNER JOIN rescue_admissions a ON a.patient_id = p.patient_id
        WHERE p.centre_id = :centre_id
        ORDER BY p.patient_id ASC
    ";
    $stmt = $pdo->prepare($sql_export);
    $stmt->bindValue(':centre_id', $centre_id_int, PDO::PARAM_INT);
    $stmt->execute();

    fputcsv($out, ['patient_id','name','sex','animal_type','animal_species','status','date_added']);

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $r['patient_id'] ?? '',
            $r['name'] ?? '',
            $r['sex'] ?? '',
            $r['animal_type'] ?? '',
            $r['animal_species'] ?? '',
            $r['status'] ?? '',
            $r['date_added'] ?? '',
        ]);
    }

} else {

    $sql_export = "
        SELECT
            p.patient_id,
            p.name,
            p.sex,
            p.animal_type,
            p.animal_species,
            p.status,
            p.date_added,

            a.admission_id,
            a.admission_date,
            a.presenting_complaint,
            a.disposition,
            a.disposition_date,
            (a.bc_score + a.age_score + a.severity_score) AS wra,
            DATEDIFF(COALESCE(a.disposition_date, NOW()), a.admission_date) AS days_in_care
        FROM rescue_patients p
        INNER JOIN (
            SELECT patient_id, MAX(admission_date) AS max_admission_date
            FROM rescue_admissions
            GROUP BY patient_id
        ) la ON la.patient_id = p.patient_id
        INNER JOIN rescue_admissions a
            ON a.patient_id = la.patient_id
           AND a.admission_date = la.max_admission_date
        WHERE p.centre_id = :centre_id
        ORDER BY p.patient_id ASC
    ";
    $stmt = $pdo->prepare($sql_export);
    $stmt->bindValue(':centre_id', $centre_id_int, PDO::PARAM_INT);
    $stmt->execute();

    fputcsv($out, [
        'patient_id','name','sex','animal_type','animal_species','status','date_added',
        'admission_id','admission_date','presenting_complaint','disposition','disposition_date','wra','days_in_care'
    ]);

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $r['patient_id'] ?? '',
            $r['name'] ?? '',
            $r['sex'] ?? '',
            $r['animal_type'] ?? '',
            $r['animal_species'] ?? '',
            $r['status'] ?? '',
            $r['date_added'] ?? '',

            $r['admission_id'] ?? '',
            $r['admission_date'] ?? '',
            $r['presenting_complaint'] ?? '',
            $r['disposition'] ?? '',
            $r['disposition_date'] ?? '',
            $r['wra'] ?? '',
            $r['days_in_care'] ?? '',
        ]);
    }
}

fclose($out);
exit;