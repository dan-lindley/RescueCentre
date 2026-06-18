<?php
// modules/patient_archive/controllers/patient_archive_export.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../main.php';
check_loggedin($pdo);

require_once __DIR__ . '/../../../getuserinfo.php';
require_once __DIR__ . '/../../../operations/permissions.php';
require_once __DIR__ . '/../../../operations/modules_registry.php';

$centre_id_int = (int)($GLOBALS['centre_id'] ?? 0);

if ($centre_id_int <= 0) {
    http_response_code(403);
    echo 'Error: centre_id not available.';
    exit;
}

$module = modules_find($pdo, 'patient_archive', $centre_id_int);
if (!$module || empty($module['installed']) || empty($module['enabled'])) {
    http_response_code(403);
    echo 'Patient Archive module is not active.';
    exit;
}

registerPermission('module.patient_archive', 'Patient Archive', 'module');
if (!can('module.patient_archive')) {
    http_response_code(403);
    echo 'You do not have permission to access Patient Archive.';
    exit;
}

$export = (string)($_GET['export'] ?? '');
if (!in_array($export, ['patients', 'patients_last_admission'], true)) {
    http_response_code(400);
    echo 'Invalid export.';
    exit;
}

$q = trim((string)($_GET['q'] ?? ''));
$disposition = trim((string)($_GET['disposition'] ?? ''));
$where = ' WHERE p.centre_id = :centre_id ';
$params = [':centre_id' => $centre_id_int];

if ($q !== '') {
    $where .= " AND (
        p.patient_id LIKE :q_exact
        OR p.name LIKE :q_like
        OR p.animal_species LIKE :q_like
        OR p.animal_type LIKE :q_like
        OR a.presenting_complaint LIKE :q_like
        OR a.disposition LIKE :q_like
    ) ";
    $params[':q_exact'] = $q . '%';
    $params[':q_like'] = '%' . $q . '%';
}

if ($disposition === '__none__') {
    $where .= " AND (a.disposition IS NULL OR TRIM(a.disposition) = '') ";
} elseif ($disposition === '__all_died__') {
    $where .= " AND (
        LOWER(TRIM(a.disposition)) LIKE '%died%'
        OR LOWER(TRIM(a.disposition)) LIKE '%dead%'
        OR LOWER(TRIM(a.disposition)) LIKE '%doa%'
        OR LOWER(TRIM(a.disposition)) LIKE '%euthan%'
    ) ";
} elseif ($disposition !== '') {
    $where .= ' AND TRIM(a.disposition) = :disposition ';
    $params[':disposition'] = $disposition;
}

function archive_filename_part(string $value, string $fallback): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    $value = trim((string)$value, '_');
    return $value !== '' ? $value : $fallback;
}

$centreNameStmt = $pdo->prepare('SELECT rescue_name FROM rescue_centres WHERE rescue_id = :centre_id LIMIT 1');
$centreNameStmt->execute([':centre_id' => $centre_id_int]);
$centreName = (string)($centreNameStmt->fetchColumn() ?: ($_SESSION['rescue_name'] ?? 'centre'));

$dispositionFilenameLabel = match ($disposition) {
    '__none__' => 'not_completed',
    '__all_died__' => 'all_died',
    '' => 'all_dispositions',
    default => archive_filename_part($disposition, 'all_dispositions'),
};

$filename = archive_filename_part($centreName, 'centre')
    . '_archive_'
    . $dispositionFilenameLabel
    . '_'
    . date('dmY')
    . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

if ($export === 'patients') {
    $sql = "
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
        $where
        ORDER BY p.patient_id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    fputcsv($out, ['patient_id', 'name', 'sex', 'animal_type', 'animal_species', 'status', 'date_added']);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $row['patient_id'] ?? '',
            $row['name'] ?? '',
            $row['sex'] ?? '',
            $row['animal_type'] ?? '',
            $row['animal_species'] ?? '',
            $row['status'] ?? '',
            $row['date_added'] ?? '',
        ]);
    }
} else {
    $sql = "
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
        $where
        ORDER BY p.patient_id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    fputcsv($out, [
        'patient_id', 'name', 'sex', 'animal_type', 'animal_species', 'status', 'date_added',
        'admission_id', 'admission_date', 'presenting_complaint', 'disposition', 'disposition_date', 'wra', 'days_in_care',
    ]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $row['patient_id'] ?? '',
            $row['name'] ?? '',
            $row['sex'] ?? '',
            $row['animal_type'] ?? '',
            $row['animal_species'] ?? '',
            $row['status'] ?? '',
            $row['date_added'] ?? '',
            $row['admission_id'] ?? '',
            $row['admission_date'] ?? '',
            $row['presenting_complaint'] ?? '',
            $row['disposition'] ?? '',
            $row['disposition_date'] ?? '',
            $row['wra'] ?? '',
            $row['days_in_care'] ?? '',
        ]);
    }
}

fclose($out);
exit;
