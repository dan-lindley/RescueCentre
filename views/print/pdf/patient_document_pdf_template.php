<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<style>
body { margin: 0; color: #263648; font-family: dejavusans, sans-serif; font-size: 10pt; line-height: 1.45; }
.document-heading { padding: 0 0 3mm; text-align: left; }
.document-title { margin: 0; color: #111111; font-family: dejavusanscondensed, sans-serif; font-size: 23pt; font-weight: bold; line-height: 1; text-transform: uppercase; }
.document-subtitle { margin: 0; color: #60758a; font-size: 9pt; }
.section-grid { margin-top: 4mm; }
.section-cell { width: 49%; vertical-align: top; }
.section-gap { width: 2%; }
.info-table { border: 1px solid #d7e2ec; background-color: #f3f7fa; }
.section-title { color: <?= patient_pdf_h($corporateIconColour) ?>; background-color: <?= patient_pdf_h($corporateColour) ?>; font-family: dejavusanscondensed, sans-serif; font-size: 12pt; font-weight: bold; text-transform: uppercase; }
.detail { color: #172a3d; font-size: 10pt; line-height: 1.4; }
.label { color: #526b82; font-size: 8.5pt; font-weight: bold; text-transform: uppercase; }
.value { color: #172a3d; font-size: 10.5pt; }
.box-table { margin-top: 4mm; border: 1px solid #d7e2ec; background-color: #f3f7fa; page-break-inside: avoid; }
.box-title { color: <?= patient_pdf_h($corporateIconColour) ?>; background-color: <?= patient_pdf_h($corporateColour) ?>; font-family: dejavusanscondensed, sans-serif; font-size: 12pt; font-weight: bold; text-transform: uppercase; }
.box-body { color: #263648; font-size: 10pt; line-height: 1.5; }
.declaration { white-space: pre-line; }
.signature { max-width: 75mm; max-height: 28mm; margin-top: 2mm; }
.lines { line-height: 2.1; }
</style>
</head>
<body>
<div class="document-heading">
    <div class="document-title"><?= patient_pdf_h($document_title) ?></div>
    <div class="document-subtitle">CRN <?= (int)$patient['patient_id'] ?> | Generated <?= patient_pdf_h($printed_at) ?></div>
</div>

<table class="section-grid" cellpadding="0" cellspacing="0"><tr>
    <td class="section-cell">
        <table class="info-table" cellpadding="6" cellspacing="0">
            <tr><td class="section-title">Patient Details</td></tr>
            <tr><td class="detail"><span class="label">Name</span><br><span class="value"><?= patient_pdf_h($patient['name'] ?? 'Not recorded') ?></span></td></tr>
            <tr><td class="detail"><span class="label">Species</span><br><span class="value"><?= patient_pdf_h($patient['animal_species'] ?? 'Not recorded') ?></span></td></tr>
            <tr><td class="detail"><span class="label">Type / class</span><br><span class="value"><?= patient_pdf_h(trim(($patient['animal_type'] ?? '') . ' / ' . ($patient['animal_order'] ?? ''), ' /')) ?></span></td></tr>
            <tr><td class="detail"><span class="label">Sex</span><br><span class="value"><?= patient_pdf_h($patient['sex'] ?? 'Not recorded') ?></span></td></tr>
            <tr><td class="detail"><span class="label">Microchip</span><br><span class="value"><?= patient_pdf_h($patient['microchip_number'] ?: 'None recorded') ?></span></td></tr>
            <tr><td class="detail"><span class="label">Ring</span><br><span class="value"><?= patient_pdf_h($patient['ring_number'] ?: 'None recorded') ?></span></td></tr>
        </table>
    </td>
    <td class="section-gap"></td>
    <td class="section-cell">
        <table class="info-table" cellpadding="6" cellspacing="0">
            <tr><td class="section-title">Admission Summary</td></tr>
            <tr><td class="detail"><span class="label">Admission date</span><br><span class="value"><?= patient_pdf_h(patient_pdf_dt($admission['admission_date'] ?? '')) ?></span></td></tr>
            <tr><td class="detail"><span class="label">Presenting complaint</span><br><span class="value"><?= patient_pdf_h($admission['presenting_complaint'] ?? 'Not recorded') ?></span></td></tr>
            <tr><td class="detail"><span class="label">Current location</span><br><span class="value"><?= patient_pdf_h($admission['current_location'] ?? 'Not recorded') ?></span></td></tr>
            <tr><td class="detail"><span class="label">Collection location</span><br><span class="value"><?= patient_pdf_h($admission['collection_location'] ?? 'Not recorded') ?></span></td></tr>
            <tr><td class="detail"><span class="label">Disposition</span><br><span class="value"><?= patient_pdf_h($admission['disposition'] ?? 'Not recorded') ?></span></td></tr>
            <tr><td class="detail"><span class="label">Finder</span><br><span class="value"><?= patient_pdf_h($admission['finder_name'] ?? 'Not recorded') ?></span></td></tr>
            <tr><td class="detail"><span class="label">Finder telephone</span><br><span class="value"><?= patient_pdf_h($admission['finder_tel'] ?? 'Not recorded') ?></span></td></tr>
        </table>
    </td>
</tr></table>

<?php if ($document_kind === 'handoff'): ?>
    <table class="box-table" cellpadding="7" cellspacing="0">
        <tr><td class="box-title">Handover Declaration</td></tr>
        <tr><td class="box-body declaration"><?= patient_pdf_h($declaration) ?></td></tr>
    </table>
    <table class="box-table" cellpadding="7" cellspacing="0">
        <tr><td class="box-title">Recorded Signature</td></tr>
        <tr><td class="box-body">
            <?php if (!empty($signature['signature_data']) && empty($signature['refused'])): ?>
                <img class="signature" src="<?= patient_pdf_h($signature['signature_data']) ?>">
                <br>Recorded <?= patient_pdf_h(patient_pdf_dt($signature['signed_at'] ?? '')) ?>
            <?php else: ?>
                <strong>No signature supplied.</strong>
            <?php endif; ?>
        </td></tr>
    </table>
<?php else: ?>
    <table class="box-table" cellpadding="7" cellspacing="0">
        <tr><td class="box-title">Transfer Details</td></tr>
        <tr><td class="box-body lines">
            Receiving organisation: _______________________________________________________<br>
            Receiving contact: ____________________________________________________________<br>
            Transfer date and time: _______________________________________________________<br>
            Reason / notes: _______________________________________________________________<br>
            _______________________________________________________________________________<br>
            Released by: __________________________ Signature: _____________________________<br>
            Received by: __________________________ Signature: _____________________________
        </td></tr>
    </table>
<?php endif; ?>
</body>
</html>
