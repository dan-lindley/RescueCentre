<?php
declare(strict_types=1);

function patient_record_value($value, string $fallback = 'Not recorded'): string
{
    $value = trim((string)$value);
    return patient_pdf_h($value !== '' ? $value : $fallback);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
body { margin: 0; color: #263648; font-family: dejavusans, sans-serif; font-size: 9pt; line-height: 1.4; }
.document-heading { padding: 0 0 3mm; }
.document-title { margin: 0; color: #111111; font-family: dejavusanscondensed, sans-serif; font-size: 23pt; font-weight: bold; line-height: 1; text-transform: uppercase; }
.document-subtitle { color: #60758a; font-size: 9pt; }
.summary { margin-top: 3mm; border: 1px solid #d7e2ec; background-color: #f3f7fa; }
.summary-title, .section-title { color: <?= patient_pdf_h($corporateIconColour) ?>; background-color: <?= patient_pdf_h($corporateColour) ?>; font-family: dejavusanscondensed, sans-serif; font-size: 11pt; font-weight: bold; text-transform: uppercase; }
.summary-cell { width: 50%; color: #172a3d; font-size: 9.5pt; vertical-align: top; }
.label { color: #526b82; font-size: 8pt; font-weight: bold; text-transform: uppercase; }
.section { margin-top: 4mm; border: 1px solid #d7e2ec; }
.section-body { background-color: #f8fafc; font-size: 9pt; }
.data-table { width: 100%; margin-top: 1mm; border: 1px solid #d7e2ec; }
.data-table th { color: <?= patient_pdf_h($corporateIconColour) ?>; background-color: <?= patient_pdf_h($corporateColour) ?>; font-size: 8pt; font-weight: bold; }
.data-table td { color: #263648; background-color: #ffffff; font-size: 8.5pt; }
.entry { margin-bottom: 2mm; }
.muted { color: #60758a; font-size: 8pt; }
</style>
</head>
<body>
<div class="document-heading">
    <div class="document-title">Full Patient Record</div>
    <div class="document-subtitle">CRN <?= (int)$patient['patient_id'] ?> | Generated <?= patient_pdf_h($printed_at) ?> by <?= patient_record_value($printed_by) ?></div>
</div>

<table class="summary" cellpadding="6" cellspacing="0">
    <tr><td colspan="2" class="summary-title">Patient Overview</td></tr>
    <tr>
        <td class="summary-cell">
            <span class="label">Name</span><br><?= patient_record_value($patient['name'] ?? '') ?><br><br>
            <span class="label">Species</span><br><?= patient_record_value($patient['animal_species'] ?? '') ?><br><br>
            <span class="label">Type / class</span><br><?= patient_record_value(trim(($patient['animal_type'] ?? '') . ' / ' . ($patient['animal_order'] ?? ''), ' /')) ?><br><br>
            <span class="label">Sex</span><br><?= patient_record_value($patient['sex'] ?? '') ?>
        </td>
        <td class="summary-cell">
            <span class="label">Status</span><br><?= patient_record_value($patient['status'] ?? '') ?><br><br>
            <span class="label">Current location</span><br><?= patient_record_value($admission['current_location'] ?? '') ?><br><br>
            <span class="label">Microchip</span><br><?= patient_record_value($patient['microchip_number'] ?? '') ?><br><br>
            <span class="label">Ring</span><br><?= patient_record_value($patient['ring_number'] ?? '') ?>
        </td>
    </tr>
</table>

<table class="section" cellpadding="6" cellspacing="0">
    <tr><td class="section-title">Admission</td></tr>
    <tr><td class="section-body">
        <span class="label">Admission date</span><br><?= patient_pdf_h(patient_pdf_dt($admission['admission_date'] ?? '')) ?><br><br>
        <span class="label">Presenting complaint</span><br><?= patient_record_value($admission['presenting_complaint'] ?? '') ?><br><br>
        <span class="label">Collection location</span><br><?= patient_record_value($admission['collection_location'] ?? '') ?><br><br>
        <span class="label">Finder</span><br><?= patient_record_value($admission['finder_name'] ?? '') ?> | <?= patient_record_value($admission['finder_tel'] ?? '') ?><br><br>
        <span class="label">Age / intake condition</span><br>
        <?= patient_record_value($admission['age_on_admission'] ?? '') ?> | Starved: <?= patient_record_value($admission['starved'] ?? '') ?> | Dehydrated: <?= patient_record_value($admission['dehydrated'] ?? '') ?>
    </td></tr>
</table>

<table class="section" cellpadding="6" cellspacing="0">
    <tr><td class="section-title">Clinical Notes On Admission</td></tr>
    <tr><td class="section-body"><span class="label">History of presenting complaint</span><br><?= nl2br(patient_record_value($admission['hpc'] ?? '')) ?><br><br><span class="label">On examination</span><br><?= nl2br(patient_record_value($admission['on_examination'] ?? '')) ?></td></tr>
</table>

<table class="section" cellpadding="6" cellspacing="0">
    <tr><td class="section-title">Care Notes (<?= count($care_notes) ?>)</td></tr>
    <tr><td class="section-body">
        <?php if (!$care_notes): ?><span class="muted">No care notes recorded.</span><?php endif; ?>
        <?php foreach ($care_notes as $note): ?>
            <div class="entry"><strong><?= patient_pdf_h(patient_pdf_dt($note['date'] ?? '')) ?> | <?= patient_record_value($note['author'] ?? '') ?></strong><br><?= nl2br(patient_record_value($note['message'] ?? '')) ?></div>
        <?php endforeach; ?>
    </td></tr>
</table>

<?php
$recordTables = [
    'Treatments' => [
        'rows' => $treatments,
        'headers' => ['Date', 'Treatment', 'Details', 'By'],
        'values' => static function (array $row): array {
            return [patient_pdf_dt($row['date'] ?? ''), $row['treatment'] ?? '', $row['detail'] ?? '', $row['done_by'] ?? ''];
        },
    ],
    'Prescriptions' => [
        'rows' => $prescriptions,
        'headers' => ['Date', 'Medication', 'Dose', 'Frequency'],
        'values' => static function (array $row): array {
            return [patient_pdf_dt($row['date'] ?? ''), $row['medication'] ?? '', trim(($row['dose'] ?? '') . ' ' . ($row['dose_type'] ?? '')), $row['frequency'] ?? ''];
        },
    ],
    'Medication Administered' => [
        'rows' => $medications_given,
        'headers' => ['Date', 'Medication', 'Dose', 'By'],
        'values' => static function (array $row): array {
            return [patient_pdf_dt($row['date'] ?? ''), $row['medication_given'] ?? '', trim(($row['dose'] ?? '') . ' ' . ($row['dose_type'] ?? '')), $row['given_by'] ?? ''];
        },
    ],
    'Feeding (Last 10)' => [
        'rows' => $feeding_events,
        'headers' => ['Date', 'Type', 'Offered', 'Consumed'],
        'values' => static function (array $row): array {
            return [patient_pdf_dt($row['feed_at'] ?? ''), $row['feed_type'] ?? '', trim(($row['offered_value'] ?? '') . ' ' . ($row['offered_unit'] ?? '')), trim(($row['consumed_value'] ?? '') . ' ' . ($row['consumed_unit'] ?? ''))];
        },
    ],
    'Weights' => [
        'rows' => $weights,
        'headers' => ['Date', 'Weight', 'Unit'],
        'values' => static function (array $row): array {
            return [patient_pdf_dt($row['date'] ?? ''), $row['weight'] ?? '', $row['weight_unit'] ?? ''];
        },
    ],
    'Measurements' => [
        'rows' => $measurements,
        'headers' => ['Date', 'Measurement', 'Unit'],
        'values' => static function (array $row): array {
            return [patient_pdf_dt($row['date'] ?? ''), $row['measurement'] ?? '', $row['measurement_unit'] ?? ''];
        },
    ],
    'Lab Results' => [
        'rows' => $labs,
        'headers' => ['Date', 'Test', 'Sample', 'Result'],
        'values' => static function (array $row): array {
            return [patient_pdf_dt($row['lab_date'] ?? ''), $row['lab_test_name'] ?? '', $row['sample_type_name'] ?? '', $row['lab_result'] ?? ''];
        },
    ],
];
?>

<?php foreach ($recordTables as $sectionName => $definition): ?>
    <table class="section" cellpadding="5" cellspacing="0">
        <tr><td class="section-title"><?= patient_pdf_h($sectionName) ?> (<?= count($definition['rows']) ?>)</td></tr>
        <tr><td class="section-body">
            <?php if (!$definition['rows']): ?>
                <span class="muted">No entries recorded.</span>
            <?php else: ?>
                <table class="data-table" cellpadding="4" cellspacing="0">
                    <tr><?php foreach ($definition['headers'] as $heading): ?><th><?= patient_pdf_h($heading) ?></th><?php endforeach; ?></tr>
                    <?php foreach ($definition['rows'] as $row): ?><tr><?php foreach ($definition['values']($row) as $value): ?><td><?= patient_record_value($value, '-') ?></td><?php endforeach; ?></tr><?php endforeach; ?>
                </table>
            <?php endif; ?>
        </td></tr>
    </table>
<?php endforeach; ?>

<table class="section" cellpadding="6" cellspacing="0">
    <tr><td class="section-title">Partner Logs (<?= count($partner_logs) ?>)</td></tr>
    <tr><td class="section-body">
        <?php if (!$partner_logs): ?><span class="muted">No partner logs recorded.</span><?php endif; ?>
        <?php foreach ($partner_logs as $log): ?>
            <div class="entry"><strong><?= patient_pdf_h(patient_pdf_dt($log['date'] ?? '')) ?> | <?= patient_record_value($log['partner_type_name'] ?? '') ?></strong><br><?= nl2br(patient_record_value($log['log_notes'] ?? '')) ?></div>
        <?php endforeach; ?>
    </td></tr>
</table>

<table class="section" cellpadding="5" cellspacing="0">
    <tr><td class="section-title">Movements (<?= count($movements) ?>)</td></tr>
    <tr><td class="section-body">
        <?php if (!$movements): ?><span class="muted">No movements recorded.</span><?php else: ?>
            <table class="data-table" cellpadding="4" cellspacing="0">
                <tr><th>Date</th><th>Event</th><th>From</th><th>To</th><th>Notes</th></tr>
                <?php foreach ($movements as $move): ?><tr>
                    <td><?= patient_pdf_h(patient_pdf_dt($move['event_at'] ?? '')) ?></td>
                    <td><?= patient_record_value($move['event_type'] ?? '') ?></td>
                    <td><?= patient_record_value($move['from_location_name'] ?? '', '-') ?></td>
                    <td><?= patient_record_value($move['to_location_name'] ?? '', '-') ?></td>
                    <td><?= patient_record_value($move['notes'] ?? '', '-') ?></td>
                </tr><?php endforeach; ?>
            </table>
        <?php endif; ?>
    </td></tr>
</table>

<table class="section" cellpadding="6" cellspacing="0">
    <tr><td class="section-title">Images</td></tr>
    <tr><td class="section-body">
        <?= count($images) ?> patient image<?= count($images) === 1 ? '' : 's' ?> recorded.
        <?php foreach ($images as $image): ?><br><?= patient_record_value($image['file_name'] ?? '', 'Unnamed image') ?><?php endforeach; ?>
    </td></tr>
</table>
</body>
</html>
