<?php
declare(strict_types=1);

require_once __DIR__ . '/../../connection.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/patient_data.php';

$document_kind = ($document_kind ?? '') === 'handoff' ? 'handoff' : 'transfer';
$document_title = $document_kind === 'handoff' ? 'Patient Handoff Document' : 'Patient Transfer Document';

function patient_document_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function patient_document_dt($value): string
{
    if (!$value) {
        return 'Not recorded';
    }
    try {
        return (new DateTime((string)$value))->format('d M Y H:i');
    } catch (Throwable $e) {
        return (string)$value;
    }
}

$declaration = "By handing over this animal to the rescue centre, the finder confirms that they transfer ongoing responsibility for the care and welfare of the animal to the rescue.\n\nThe finder understands that their personal details, where provided and consented, may be stored and used for updates and audit or legal purposes in line with GDPR and the centre's privacy policy.";
$signature = [];
$centreLogo = '';
$corporateColour = '#0B3A6F';

try {
    $stmt = $pdo->prepare('SELECT centre_logo, handover_declaration_text, custom_colour FROM rescue_centre_meta WHERE centre_id = :centre_id LIMIT 1');
    $stmt->execute([':centre_id' => (int)$patient['centre_id']]);
    $centreMeta = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $centreLogo = trim((string)($centreMeta['centre_logo'] ?? ''));
    $storedColour = strtoupper(trim((string)($centreMeta['custom_colour'] ?? '')));
    if (preg_match('/^#[0-9A-F]{6}$/', $storedColour)) {
        $corporateColour = $storedColour;
    }

    if ($document_kind === 'handoff') {
        $storedDeclaration = trim((string)($centreMeta['handover_declaration_text'] ?? ''));
        if ($storedDeclaration !== '') {
            $declaration = $storedDeclaration;
        }
    }
} catch (Throwable $e) {
    // Use a text-only letterhead and the default declaration.
}

if ($centreLogo !== '' && !preg_match('~^(?:(?:https?:)?//|data:|/)~i', $centreLogo)) {
    $centreLogo = '/' . ltrim($centreLogo, '/');
}

$corporateRgb = [
    hexdec(substr($corporateColour, 1, 2)),
    hexdec(substr($corporateColour, 3, 2)),
    hexdec(substr($corporateColour, 5, 2)),
];
$luminanceChannels = array_map(static function (int $channel): float {
    $value = $channel / 255;
    return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
}, $corporateRgb);
$corporateIsLight = (
    0.2126 * $luminanceChannels[0]
    + 0.7152 * $luminanceChannels[1]
    + 0.0722 * $luminanceChannels[2]
) > 0.179;
$corporateTextColour = $corporateIsLight ? '#1F2937' : '#F8FAFC';
$corporateMutedTextColour = $corporateIsLight ? '#374151' : '#E2E8F0';

if ($document_kind === 'handoff') {
    if (!empty($admission['admission_id'])) {
        try {
            $stmt = $pdo->prepare("
                SELECT signature_data, refused, signed_at
                FROM rescue_signatures
                WHERE centre_id = :centre_id
                  AND admission_id = :admission_id
                  AND patient_id = :patient_id
                ORDER BY signed_at DESC
                LIMIT 1
            ");
            $stmt->execute([
                ':centre_id' => (int)$patient['centre_id'],
                ':admission_id' => (int)$admission['admission_id'],
                ':patient_id' => (int)$patient['patient_id'],
            ]);
            $signature = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $signature = [];
        }
    }
}

$centreAddress = implode(', ', array_filter([
    $centre['address_line_one'] ?? '',
    $centre['address_line_two'] ?? '',
    $centre['city'] ?? '',
    $centre['postcode'] ?? '',
]));
$centreContact = implode(' | ', array_filter([
    $centre['email'] ?? '',
    $centre['office_tel'] ?? '',
]));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= patient_document_h($document_title) ?> - CRN <?= (int)$patient['patient_id'] ?></title>
<style>
@page { size: A4; margin: 16mm 14mm 20mm; }
* { box-sizing: border-box; }
body { margin: 0; color: #1f2937; font: 10.5pt/1.4 Arial, Helvetica, sans-serif; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
.toolbar { display: flex; justify-content: flex-end; gap: 8px; margin: 0 auto 14px; max-width: 900px; }
.toolbar a, .toolbar button { padding: 8px 12px; border: 1px solid #94a3b8; border-radius: 6px; background: #fff; color: #1f2937; cursor: pointer; text-decoration: none; }
.document { max-width: 900px; margin: 0 auto; }
.letterhead { display: flex; align-items: center; gap: 7mm; min-height: 34mm; padding: 6mm 8mm; background: <?= patient_document_h($corporateColour) ?>; color: <?= patient_document_h($corporateTextColour) ?>; }
.letterhead-logo { display: flex; flex: 0 0 auto; align-items: center; height: 25mm; }
.letterhead-logo img { display: block; width: auto; height: auto; max-height: 25mm; }
.letterhead-details { flex: 1; text-align: right; }
.letterhead-name { color: <?= patient_document_h($corporateTextColour) ?>; font-size: 18pt; font-weight: 700; letter-spacing: -.2pt; line-height: 1.15; }
.letterhead-address, .letterhead-contact { margin-top: 1.2mm; color: <?= patient_document_h($corporateMutedTextColour) ?>; font-size: 8.5pt; }
.letterhead-contact { color: <?= patient_document_h($corporateTextColour) ?>; font-weight: 700; }
.document-heading { margin: 6mm 0 2mm; padding-bottom: 4mm; border-bottom: 2px solid <?= patient_document_h($corporateColour) ?>; text-align: center; }
.document-eyebrow { margin-bottom: 1mm; color: #61758a; font-size: 7.5pt; font-weight: 700; letter-spacing: 1.4pt; text-transform: uppercase; }
.document-title { margin: 0; color: <?= patient_document_h($corporateColour) ?>; font-size: 20pt; letter-spacing: -.2pt; line-height: 1.15; }
.document-subtitle { margin-top: 1.5mm; color: #526579; font-size: 8.5pt; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 5mm; }
.box { margin-top: 5mm; padding: 4mm; border: 1px solid #cbd5e1; break-inside: avoid; }
.box h2 { margin: -4mm -4mm 3mm; padding: 2.5mm 4mm; background: <?= patient_document_h($corporateColour) ?>; color: <?= patient_document_h($corporateTextColour) ?>; font-size: 12pt; }
.row { margin: 0 0 1.5mm; }
.label { font-weight: 700; }
.declaration { white-space: pre-line; }
.signature { display: block; max-width: 75mm; max-height: 28mm; margin-top: 3mm; }
.lines { line-height: 2.2; }
footer { position: fixed; right: 14mm; bottom: 0; left: 14mm; display: flex; justify-content: space-between; gap: 8mm; min-height: 10mm; padding: 3mm 5mm; background: <?= patient_document_h($corporateColour) ?>; color: <?= patient_document_h($corporateTextColour) ?>; font-size: 8pt; }
@media print { .toolbar { display: none; } .document { max-width: none; } }
</style>
</head>
<body>
<div class="toolbar">
    <a href="../../docspatient.php?patient_id=<?= (int)$patient['patient_id'] ?>">Back to Documents</a>
    <button type="button" onclick="window.print()">Print / Save PDF</button>
</div>

<div class="document">
    <header class="letterhead">
        <?php if ($centreLogo !== ''): ?>
            <div class="letterhead-logo">
                <img src="<?= patient_document_h($centreLogo) ?>" alt="<?= patient_document_h($centre['rescue_name'] ?? 'Rescue Centre') ?> logo">
            </div>
        <?php endif; ?>
        <div class="letterhead-details">
            <div class="letterhead-name"><?= patient_document_h($centre['rescue_name'] ?? 'Rescue Centre') ?></div>
            <?php if ($centreAddress !== ''): ?><div class="letterhead-address"><?= patient_document_h($centreAddress) ?></div><?php endif; ?>
            <?php if ($centreContact !== ''): ?><div class="letterhead-contact"><?= patient_document_h($centreContact) ?></div><?php endif; ?>
        </div>
    </header>

    <div class="document-heading">
        <div class="document-eyebrow">Official Patient Document</div>
        <h1 class="document-title"><?= patient_document_h($document_title) ?></h1>
        <div class="document-subtitle">
            CRN <?= (int)$patient['patient_id'] ?>
            &middot; Generated <?= patient_document_h($printed_at) ?>
        </div>
    </div>

    <div class="grid">
        <section class="box">
            <h2>Patient</h2>
            <p class="row"><span class="label">Name:</span> <?= patient_document_h($patient['name'] ?? 'Not recorded') ?></p>
            <p class="row"><span class="label">Species:</span> <?= patient_document_h($patient['animal_species'] ?? 'Not recorded') ?></p>
            <p class="row"><span class="label">Type / class:</span> <?= patient_document_h(trim(($patient['animal_type'] ?? '') . ' / ' . ($patient['animal_order'] ?? ''), ' /')) ?></p>
            <p class="row"><span class="label">Sex:</span> <?= patient_document_h($patient['sex'] ?? 'Not recorded') ?></p>
            <p class="row"><span class="label">Microchip:</span> <?= patient_document_h($patient['microchip_number'] ?: 'None recorded') ?></p>
            <p class="row"><span class="label">Ring:</span> <?= patient_document_h($patient['ring_number'] ?: 'None recorded') ?></p>
        </section>

        <section class="box">
            <h2>Admission</h2>
            <p class="row"><span class="label">Admission date:</span> <?= patient_document_h(patient_document_dt($admission['admission_date'] ?? '')) ?></p>
            <p class="row"><span class="label">Presenting complaint:</span> <?= patient_document_h($admission['presenting_complaint'] ?? 'Not recorded') ?></p>
            <p class="row"><span class="label">Current location:</span> <?= patient_document_h($admission['current_location'] ?? 'Not recorded') ?></p>
            <p class="row"><span class="label">Collection location:</span> <?= patient_document_h($admission['collection_location'] ?? 'Not recorded') ?></p>
            <p class="row"><span class="label">Disposition:</span> <?= patient_document_h($admission['disposition'] ?? 'Not recorded') ?></p>
            <p class="row"><span class="label">Finder:</span> <?= patient_document_h($admission['finder_name'] ?? 'Not recorded') ?></p>
            <p class="row"><span class="label">Finder telephone:</span> <?= patient_document_h($admission['finder_tel'] ?? 'Not recorded') ?></p>
        </section>
    </div>

    <?php if ($document_kind === 'handoff'): ?>
        <section class="box">
            <h2>Handover Declaration</h2>
            <div class="declaration"><?= patient_document_h($declaration) ?></div>
        </section>

        <section class="box">
            <h2>Recorded Signature</h2>
            <?php if (!empty($signature['signature_data']) && empty($signature['refused'])): ?>
                <img class="signature" src="<?= patient_document_h($signature['signature_data']) ?>" alt="Stored handover signature">
                <p class="row">Recorded <?= patient_document_h(patient_document_dt($signature['signed_at'] ?? '')) ?></p>
            <?php else: ?>
                <p class="row"><strong>No signature supplied.</strong></p>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="box">
            <h2>Transfer Details</h2>
            <div class="lines">
                Receiving organisation: _______________________________________________________<br>
                Receiving contact: ____________________________________________________________<br>
                Transfer date and time: _______________________________________________________<br>
                Reason / notes: _______________________________________________________________<br>
                _______________________________________________________________________________<br>
                Released by: __________________________ Signature: _____________________________<br>
                Received by: __________________________ Signature: _____________________________
            </div>
        </section>
    <?php endif; ?>

    <section class="box">
        <h2>Rescue Centre</h2>
        <p class="row"><span class="label">Centre:</span> <?= patient_document_h($centre['rescue_name'] ?? '') ?></p>
        <?php if ($centreAddress !== ''): ?><p class="row"><span class="label">Address:</span> <?= patient_document_h($centreAddress) ?></p><?php endif; ?>
        <?php if (!empty($centre['email'])): ?><p class="row"><span class="label">Email:</span> <?= patient_document_h($centre['email']) ?></p><?php endif; ?>
        <?php if (!empty($centre['office_tel'])): ?><p class="row"><span class="label">Telephone:</span> <?= patient_document_h($centre['office_tel']) ?></p><?php endif; ?>
    </section>
</div>

<footer>
    <span>Rescue Centre is a free platform supporting rescue organisations.</span>
    <span>CRN <?= (int)$patient['patient_id'] ?> &middot; <?= patient_document_h($document_title) ?></span>
</footer>
</body>
</html>
