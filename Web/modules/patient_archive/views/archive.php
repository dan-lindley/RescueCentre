<?php
// modules/patient_archive/views/archive.php
if (!defined('APP_LOADED')) exit;

if (function_exists('registerPermission')) {
    registerPermission('module.patient_archive', 'Patient Archive', 'module');
}
if (function_exists('can') && !can('module.patient_archive')) {
    echo '<div class="rc-alert red">You do not have permission to access Patient Archive.</div>';
    return;
}

$archiveExportFilters = [];
foreach (['q', 'disposition'] as $filterName) {
    $filterValue = trim((string)($_GET[$filterName] ?? ''));
    if ($filterValue !== '') {
        $archiveExportFilters[$filterName] = $filterValue;
    }
}
$patientsExportUrl = 'modules/patient_archive/controllers/patient_archive_export.php?' . http_build_query(
    ['export' => 'patients'] + $archiveExportFilters
);
$lastAdmissionExportUrl = 'modules/patient_archive/controllers/patient_archive_export.php?' . http_build_query(
    ['export' => 'patients_last_admission'] + $archiveExportFilters
);
?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M129.5 464L179.5 304L558.9 304L508.9 464L129.5 464zM320.2 512L509 512C530 512 548.6 498.4 554.8 478.3L604.8 318.3C614.5 287.4 591.4 256 559 256L179.6 256C158.6 256 140 269.6 133.8 289.7L112.2 358.4L112.2 160C112.2 151.2 119.4 144 128.2 144L266.9 144C270.4 144 273.7 145.1 276.5 147.2L314.9 176C328.7 186.4 345.6 192 362.9 192L480.2 192C489 192 496.2 199.2 496.2 208L544.2 208C544.2 172.7 515.5 144 480.2 144L362.9 144C356 144 349.2 141.8 343.7 137.6L305.3 108.8C294.2 100.5 280.8 96 266.9 96L128.2 96C92.9 96 64.2 124.7 64.2 160L64.2 448C64.2 483.3 92.9 512 128.2 512L320.2 512z"/></svg>
        </div>
        <div class="txt">
            <h2><?= $lang['LM_PATIENT_ARCHIVE'] ?? 'Patient Archive' ?></h2>
            <p><?= $lang['PAT_ARCHIVE_SUBTITLE'] ?? 'Review your patients past and present' ?></p>
        </div>
    </div>
    <div class="btns">
        <a href="<?= htmlspecialchars($patientsExportUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn blue"><?= $lang['DOWNLOAD_PATIENTS_CSV'] ?? 'Download patients (CSV)' ?></a>
        <a href="<?= htmlspecialchars($lastAdmissionExportUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn orange"><?= $lang['DOWNLOAD_PATIENTS_WITH_ADMISSION_CSV'] ?? 'Download patients + admission (CSV)' ?></a>
    </div>
</div>

<?php require __DIR__ . '/patient_archive_view.php'; ?>
