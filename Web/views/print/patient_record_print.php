<?php
// /views/print/patient_record_print.php
// Full patient record print view (PDO only).

declare(strict_types=1);

require_once __DIR__ . '/../../connection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Central data loader (same folder)
require_once __DIR__ . '/patient_data.php';

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function resolve_image_src(array $img): string {
    $raw = trim((string)($img['image_url'] ?? ''));
    if ($raw === '') return '';

    // If already absolute, keep it
    if (preg_match('~^https?://~i', $raw)) return $raw;

    // Normalise leading slash
    $raw = '/' . ltrim($raw, '/');

    if (!empty($img['is_legacy']) && (int)$img['is_legacy'] === 1) {
        return 'https://legacy.rescuecentre.org.uk/wp-content/themes/brikk-child' . $raw;
    }

    return 'https://myrescuecentre.com' . $raw;
}

function fmt_dt($v, string $fallback = '—'): string {
    if (!$v) return $fallback;
    try { return (new DateTime((string)$v))->format('d M Y H:i'); } catch (Throwable $e) { return $fallback; }
}

$name = (string)($patient['name'] ?? '');
$centreLogo = '';
$corporateColour = '#0B3A6F';
try {
    $stmt = $pdo->prepare('SELECT centre_logo, custom_colour FROM rescue_centre_meta WHERE centre_id = :centre_id LIMIT 1');
    $stmt->execute([':centre_id' => (int)$patient['centre_id']]);
    $centreMeta = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $centreLogo = trim((string)($centreMeta['centre_logo'] ?? ''));
    $storedColour = strtoupper(trim((string)($centreMeta['custom_colour'] ?? '')));
    if (preg_match('/^#[0-9A-F]{6}$/', $storedColour)) {
        $corporateColour = $storedColour;
    }
} catch (Throwable $e) {
    $centreLogo = '';
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

$centreAddress = trim(implode(', ', array_filter([
    $centre['address_line_one'] ?? null,
    $centre['address_line_two'] ?? null,
    $centre['city'] ?? null,
    $centre['postcode'] ?? null,
])));
$centreContact = trim(implode(' | ', array_filter([
    $centre['email'] ?? null,
    $centre['office_tel'] ?? null,
])));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Patient Care Plan – <?= h($name) ?> (CRN <?= (int)($patient['patient_id'] ?? 0) ?>)</title>

<style>
@page { margin: 14mm 12mm 18mm; }
* { box-sizing: border-box; }

body{
  margin:0;
  font-family: Arial, Helvetica, sans-serif;
  font-size: 10.5pt;
  line-height: 1.35;
  color:#222;
  print-color-adjust:exact;
  -webkit-print-color-adjust:exact;
}

.page{ width:100%; }

/* Header */
.letterhead{
  display:flex;
  align-items:center;
  gap:7mm;
  min-height:34mm;
  padding:6mm 8mm;
  margin-bottom:6mm;
  background:<?= h($corporateColour) ?>;
  color:<?= h($corporateTextColour) ?>;
}
.letterhead-logo{ display:flex; flex:0 0 30mm; align-items:center; justify-content:center; height:25mm; padding:2mm; background:#fff; }
.letterhead-logo img{ display:block; max-width:25mm; max-height:21mm; object-fit:contain; }
.letterhead-details{ flex:1; text-align:right; }
.letterhead-name{ color:<?= h($corporateTextColour) ?>; font-size:18pt; font-weight:700; letter-spacing:-.2pt; line-height:1.15; }
.letterhead-address,.letterhead-contact{ margin-top:1.2mm; color:<?= h($corporateMutedTextColour) ?>; font-size:8.5pt; }
.letterhead-contact{ color:<?= h($corporateTextColour) ?>; font-weight:700; }
.document-heading{ margin:0 0 5mm; padding-bottom:4mm; border-bottom:2px solid <?= h($corporateColour) ?>; text-align:center; }
.document-eyebrow{ margin-bottom:1mm; color:#61758a; font-size:7.5pt; font-weight:700; letter-spacing:1.4pt; text-transform:uppercase; }
.document-heading .doc-title{ color:<?= h($corporateColour) ?>; font-size:20pt; letter-spacing:-.2pt; line-height:1.15; }
.header-block{
  border:1px solid #cfcfcf;
  padding:4mm;
  margin:0 0 5mm 0;
}
.doc-title{
  font-size:16pt;
  font-weight:700;
  margin:0 0 1mm 0;
}
.doc-subtitle{
  font-size:9.6pt;
  color:#555;
  margin:0 0 3mm 0;
}

.info-grid{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:6mm;
}
.info-box-title{
  font-size:10.5pt;
  font-weight:700;
  margin:0 0 2mm 0;
  padding:1.5mm 2mm;
  background:<?= h($corporateColour) ?>;
  color:<?= h($corporateTextColour) ?>;
}
.kv{ margin:0 0 1.1mm 0; }
.label{ font-weight:700; }
.small{ font-size:9.5pt; color:#444; }

/* Sections */
h2{
  font-size:11.2pt;
  margin:5mm 0 2mm 0;
  padding:1.5mm 2mm;
  background:<?= h($corporateColour) ?>;
  color:<?= h($corporateTextColour) ?>;
}

.grid-2{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:6mm;
}

.columns{
  column-count:2;
  column-gap:8mm;
  margin-top:2mm;
}
.section{
  break-inside: avoid;
  margin-bottom:6mm;
}

hr{
  border:none;
  border-top:1px solid #e2e2e2;
  margin:2.5mm 0;
}

table{
  width:100%;
  border-collapse:collapse;
  margin-top:2mm;
  font-size:9.6pt;
}
th,td{
  border:1px solid #cfcfcf;
  padding:1.8mm 2.4mm;
  vertical-align:top;
}
th{
  background:<?= h($corporateColour) ?>;
  color:<?= h($corporateTextColour) ?>;
  font-weight:700;
}

/* Image thumbs */
.thumb-grid{
  display:flex;
  flex-wrap:wrap;
  gap:3mm;
  margin-top:2mm;
}
.thumb{
  width:22mm;
  height:22mm;
  border:1px solid #cfcfcf;
  overflow:hidden;
  border-radius:2mm;
}
.thumb img{
  width:100%;
  height:100%;
  object-fit:cover;
  display:block;
}

/* Footer */
footer{
  position:fixed;
  left:12mm;
  right:12mm;
  bottom:0;
  display:flex;
  justify-content:space-between;
  gap:10mm;
  min-height:10mm;
  padding:3mm 5mm;
  background:<?= h($corporateColour) ?>;
  color:<?= h($corporateTextColour) ?>;
  font-size:8pt;
}
.footer-right{ text-align:right; }
.pagenum:before{ content: counter(page); }
.pagecount:before{ content: counter(pages); }
</style>
</head>

<body>
<footer>
  <div>Generated by MyRescueCentre.com</div>
  <div class="footer-right">
    Printed on <?= h($printed_at ?? '') ?> by <?= h($printed_by ?? '') ?>
    · Patient CRN <?= (int)($patient['patient_id'] ?? 0) ?>
    · Page <span class="pagenum"></span> of <span class="pagecount"></span>
  </div>
</footer>

<div class="page">

  <header class="letterhead">
    <?php if ($centreLogo !== ''): ?>
      <div class="letterhead-logo">
        <img src="<?= h($centreLogo) ?>" alt="<?= h($centre['rescue_name'] ?? 'Rescue Centre') ?> logo">
      </div>
    <?php endif; ?>
    <div class="letterhead-details">
      <div class="letterhead-name"><?= h($centre['rescue_name'] ?? 'Rescue Centre') ?></div>
      <?php if ($centreAddress !== ''): ?><div class="letterhead-address"><?= h($centreAddress) ?></div><?php endif; ?>
      <?php if ($centreContact !== ''): ?><div class="letterhead-contact"><?= h($centreContact) ?></div><?php endif; ?>
    </div>
  </header>

  <div class="document-heading">
    <div class="document-eyebrow">Official Patient Document</div>
    <div class="doc-title">Full Patient Record</div>
    <div class="doc-subtitle">Patient CRN <?= (int)($patient['patient_id'] ?? 0) ?> &middot; Generated <?= h($printed_at ?? '') ?></div>
  </div>

  <div class="header-block">
    <div class="doc-title">Patient Care Plan for <?= h($name) ?></div>
    <div class="doc-subtitle">
      <?= h($patient['sex'] ?? '') ?> ·
      <?= h($patient['animal_type'] ?? '') ?> ·
      <?= h($patient['animal_species'] ?? '') ?>
      <?php if (!empty($patient['animal_order'])): ?> (<?= h($patient['animal_order']) ?>)<?php endif; ?>
      · Status: <?= h($patient['status'] ?? '') ?>
      · CRN <?= (int)($patient['patient_id'] ?? 0) ?>
    </div>

    <div class="info-grid">
      <div>
        <div class="info-box-title">Patient</div>
        <div class="kv"><span class="label">Name:</span> <?= h($patient['name'] ?? '') ?></div>
        <div class="kv"><span class="label">Sex:</span> <?= h($patient['sex'] ?? '—') ?></div>
        <div class="kv"><span class="label">Type / Species:</span> <?= h($patient['animal_type'] ?? '—') ?> · <?= h($patient['animal_species'] ?? '—') ?></div>
        <?php if (!empty($patient['animal_order'])): ?>
          <div class="kv"><span class="label">Order:</span> <?= h($patient['animal_order']) ?></div>
        <?php endif; ?>
        <div class="kv"><span class="label">Ringed:</span> <?= h($patient['ringed'] ?? '—') ?><?php if (!empty($patient['ring_number'])): ?> <span class="small">(<?= h($patient['ring_number']) ?>)</span><?php endif; ?></div>
        <div class="kv"><span class="label">Microchipped:</span> <?= h($patient['microchipped'] ?? '—') ?><?php if (!empty($patient['microchip_number'])): ?> <span class="small">(<?= h($patient['microchip_number']) ?>)</span><?php endif; ?></div>
      </div>

      <div>
        <div class="info-box-title">Rescue Centre</div>
        <div class="kv"><span class="label">Centre:</span> <?= h($centre['rescue_name'] ?? '') ?></div>
        <?php
          $addr = trim(implode(', ', array_filter([
            $centre['address_line_one'] ?? null,
            $centre['address_line_two'] ?? null,
            $centre['city'] ?? null,
            $centre['postcode'] ?? null,
          ])));
          $contact = trim(implode(' · ', array_filter([
            $centre['email'] ?? null,
            $centre['office_tel'] ?? null,
            $centre['mobile'] ?? null,
          ])));
        ?>
        <?php if ($addr !== ''): ?><div class="kv"><?= h($addr) ?></div><?php endif; ?>
        <?php if ($contact !== ''): ?><div class="kv"><?= h($contact) ?></div><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Admission (own section) -->
  <h2>Admission</h2>
  <?php if (!$admission): ?>
    <div class="small">No admission record found.</div>
  <?php else: ?>
    <div class="grid-2">
      <div>
        <div class="kv"><span class="label">Admitted:</span> <?= h(fmt_dt($admission['admission_date'] ?? null)) ?></div>
        <div class="kv"><span class="label">Age on admission:</span> <?= h($admission['age_on_admission'] ?? '—') ?></div>
        <div class="kv"><span class="label">Starved / Dehydrated:</span> <?= h($admission['starved'] ?? '—') ?> / <?= h($admission['dehydrated'] ?? '—') ?></div>
        <?php if (!empty($admission['weight']) || !empty($admission['measurement'])): ?>
          <div class="kv">
            <span class="label">Intake:</span>
            <?php if (!empty($admission['weight'])): ?><?= h($admission['weight']) ?> <?= h($admission['weight_unit'] ?? '') ?><?php endif; ?>
            <?php if (!empty($admission['measurement'])): ?> · <?= h($admission['measurement']) ?> <?= h($admission['measurement_unit'] ?? '') ?><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
      <div>
        <div class="kv"><span class="label">Found at:</span> <?= h($admission['collection_location'] ?? '—') ?></div>
        <div class="kv"><span class="label">Presenting complaint:</span> <?= h($admission['presenting_complaint'] ?? '—') ?></div>
        <?php if (!empty($admission['finder_name'])): ?>
          <div class="kv"><span class="label">Finder:</span> <?= h($admission['finder_name']) ?><?php if (!empty($admission['finder_tel'])): ?> <span class="small">(<?= h($admission['finder_tel']) ?>)</span><?php endif; ?></div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- HPC / Examination (own section) -->
  <h2>Clinical Notes on Admission</h2>
  <div class="grid-2">
    <div>
      <div class="kv"><span class="label">History of presenting complaint:</span></div>
      <div><?= !empty($admission['hpc']) ? nl2br(h($admission['hpc'])) : '<span class="small">—</span>' ?></div>
    </div>
    <div>
      <div class="kv"><span class="label">On examination:</span></div>
      <div><?= !empty($admission['on_examination']) ? nl2br(h($admission['on_examination'])) : '<span class="small">—</span>' ?></div>
    </div>
  </div>

  <!-- Care data (two columns) -->
  <div class="columns">

    <div class="section">
      <h2>Care Notes</h2>
      <div class="small"><?= count($care_notes) ?> entr<?= count($care_notes) === 1 ? 'y' : 'ies' ?></div>
      <?php foreach ($care_notes as $n): ?>
        <div>
          <strong><?= h(fmt_dt($n['date'] ?? null)) ?></strong> – <?= h($n['author'] ?? '') ?><br>
          <?= nl2br(h($n['message'] ?? '')) ?>
        </div>
        <hr>
      <?php endforeach; ?>
    </div>

    <div class="section">
      <h2>Treatments</h2>
      <div class="small"><?= count($treatments) ?> entr<?= count($treatments) === 1 ? 'y' : 'ies' ?></div>
      <?php if ($treatments): ?>
        <table>
          <tr><th style="width:30%;">Date</th><th>Treatment</th><th style="width:22%;">By</th></tr>
          <?php foreach ($treatments as $t): ?>
            <tr>
              <td><?= h(fmt_dt($t['date'] ?? null)) ?></td>
              <td><?= h($t['treatment'] ?? '') ?><?php if (!empty($t['detail'])): ?> — <?= h($t['detail']) ?><?php endif; ?></td>
              <td><?= h($t['done_by'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>

    <div class="section">
      <h2>Prescriptions</h2>
      <div class="small"><?= count($prescriptions) ?> entr<?= count($prescriptions) === 1 ? 'y' : 'ies' ?></div>
      <?php if ($prescriptions): ?>
        <table>
          <tr>
            <th style="width:30%;">Date</th><th>Medication</th><th style="width:18%;">Dose</th><th style="width:22%;">Frequency</th>
          </tr>
          <?php foreach ($prescriptions as $p): ?>
            <tr>
              <td><?= h(fmt_dt($p['date'] ?? null)) ?></td>
              <td><?= h($p['medication'] ?? '') ?></td>
              <td><?= h(trim((string)($p['dose'] ?? '') . ' ' . (string)($p['dose_type'] ?? ''))) ?></td>
              <td><?= h($p['frequency'] ?? '') ?><?php if (!empty($p['duration'])): ?> (<?= h($p['duration']) ?> days)<?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>

    <div class="section">
      <h2>Medication Administered</h2>
      <div class="small"><?= count($medications_given) ?> entr<?= count($medications_given) === 1 ? 'y' : 'ies' ?></div>
      <?php if ($medications_given): ?>
        <table>
          <tr><th style="width:30%;">Date</th><th>Medication</th><th style="width:18%;">Dose</th><th style="width:18%;">By</th></tr>
          <?php foreach ($medications_given as $m): ?>
            <tr>
              <td><?= h(fmt_dt($m['date'] ?? null)) ?></td>
              <td><?= h($m['medication_given'] ?? '') ?></td>
              <td><?= h(trim((string)($m['dose'] ?? '') . ' ' . (string)($m['dose_type'] ?? ''))) ?></td>
              <td><?= h($m['given_by'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>

    <div class="section">
      <h2>Feeding (last 10)</h2>
      <div class="small"><?= count($feeding_events) ?> shown</div>
      <?php if ($feeding_events): ?>
        <table>
          <tr><th style="width:32%;">Date</th><th>Type</th><th style="width:18%;">Offered</th><th style="width:18%;">Consumed</th></tr>
          <?php foreach ($feeding_events as $f): ?>
            <tr>
              <td><?= h(fmt_dt($f['feed_at'] ?? null)) ?></td>
              <td><?= h($f['feed_type'] ?? '') ?></td>
              <td><?= h(trim((string)($f['offered_value'] ?? '') . ' ' . (string)($f['offered_unit'] ?? ''))) ?></td>
              <td><?= h(trim((string)($f['consumed_value'] ?? '') . ' ' . (string)($f['consumed_unit'] ?? ''))) ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>

    <div class="section">
      <h2>Weights</h2>
      <div class="small"><?= count($weights) ?> entr<?= count($weights) === 1 ? 'y' : 'ies' ?></div>
      <?php if ($weights): ?>
        <table>
          <tr><th style="width:32%;">Date</th><th style="width:28%;">Weight</th><th>Unit</th></tr>
          <?php foreach ($weights as $w): ?>
            <tr>
              <td><?= h(fmt_dt($w['date'] ?? null)) ?></td>
              <td><?= h($w['weight'] ?? '') ?></td>
              <td><?= h($w['weight_unit'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>

    <div class="section">
      <h2>Measurements</h2>
      <div class="small"><?= count($measurements) ?> entr<?= count($measurements) === 1 ? 'y' : 'ies' ?></div>
      <?php if ($measurements): ?>
        <table>
          <tr><th style="width:32%;">Date</th><th style="width:28%;">Value</th><th>Unit</th></tr>
          <?php foreach ($measurements as $m): ?>
            <tr>
              <td><?= h(fmt_dt($m['date'] ?? null)) ?></td>
              <td><?= h($m['measurement'] ?? '') ?></td>
              <td><?= h($m['measurement_unit'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>

    <div class="section">
      <h2>Lab Results</h2>
      <div class="small"><?= count($labs) ?> entr<?= count($labs) === 1 ? 'y' : 'ies' ?></div>
      <?php if ($labs): ?>
        <table>
          <tr><th style="width:30%;">Date</th><th>Test</th><th style="width:18%;">Sample</th><th style="width:22%;">Result</th></tr>
          <?php foreach ($labs as $r): ?>
            <tr>
              <td><?= h(fmt_dt($r['lab_date'] ?? null)) ?></td>
              <td><?= h($r['lab_test_name'] ?? '') ?><?php if (!empty($r['lab_category'])): ?> <span class="small">(<?= h($r['lab_category']) ?>)</span><?php endif; ?></td>
              <td><?= h($r['sample_type_name'] ?? '') ?></td>
              <td><?= h($r['lab_result'] ?? '') ?><?php if ($r['is_positive'] !== null && $r['is_positive'] !== ''): ?> <span class="small">· positive: <?= h($r['is_positive']) ?></span><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>

    <div class="section">
      <h2>Partner Logs</h2>
      <div class="small"><?= count($partner_logs) ?> entr<?= count($partner_logs) === 1 ? 'y' : 'ies' ?></div>
      <?php foreach ($partner_logs as $p): ?>
        <div>
          <strong><?= h(fmt_dt($p['date'] ?? null)) ?></strong>
          <?php if (!empty($p['partner_type_name'])): ?> – <?= h($p['partner_type_name']) ?><?php endif; ?>
          <?php if (!empty($p['log_number'])): ?> <span class="small">(<?= h($p['log_number']) ?>)</span><?php endif; ?><br>
          <?= nl2br(h($p['log_notes'] ?? '')) ?>
        </div>
        <hr>
      <?php endforeach; ?>
    </div>

    <div class="section">
      <h2>Images</h2>
      <div class="small"><?= count($images) ?> image<?= count($images) === 1 ? '' : 's' ?></div>
      <?php if ($images): ?>
        <div class="thumb-grid">
          <?php foreach ($images as $img): ?>
  <?php $src = resolve_image_src($img); ?>
  <?php if ($src): ?>
    <div class="thumb">
      <img src="<?= h($src) ?>" alt="<?= h($img['file_name'] ?? 'Image') ?>">
    </div>
  <?php endif; ?>
<?php endforeach; ?>

        </div>
      <?php endif; ?>
    </div>

  </div><!-- /.columns -->

  <!-- Movements (full width) -->
  <h2>Movements</h2>

  <?php if (empty($movements)): ?>
    <div class="small">No movements recorded for this admission.</div>
  <?php else: ?>

    <table>
      <tr>
        <th style="width:22%;">Date</th>
        <th style="width:18%;">Event</th>
        <th>From</th>
        <th>To</th>
      </tr>

      <?php foreach ($movements as $mv): ?>
        <?php
          $etype = (string)($mv['event_type'] ?? '');
          $eventLabel =
            ($etype === 'admission') ? 'Admission' :
            (($etype === 'internal_move') ? 'Internal move' :
            (($etype === 'released') ? 'Released' :
            (($etype === 'transfer_out') ? 'Transferred out' :
            (($etype === 'euthanised') ? 'Euthanised' :
            (($etype === 'died') ? 'Died' : $etype)))));

          $from = trim((string)($mv['from_location_name'] ?? ''));
          $to   = trim((string)($mv['to_location_name'] ?? ''));

          if ($from === '') $from = '—';
          if ($to === '')   $to   = '—';

          // Keep it narrow: no extra rows unless needed
          $note = trim((string)($mv['notes'] ?? ''));
          $disp = trim((string)($mv['disposition_text'] ?? ''));
        ?>
        <tr>
          <td><?= h(fmt_dt($mv['event_at'] ?? null)) ?></td>
          <td>
            <?= h($eventLabel) ?>
            <?php if ($disp !== '' && $etype !== 'internal_move' && $etype !== 'admission'): ?>
              <br><span class="small"><?= h($disp) ?></span>
            <?php endif; ?>
          </td>
          <td><?= h($from) ?></td>
          <td><?= h($to) ?></td>
        </tr>

        <?php if ($note !== ''): ?>
          <tr>
            <td colspan="4" class="small"><?= nl2br(h($note)) ?></td>
          </tr>
        <?php endif; ?>

      <?php endforeach; ?>

    </table>

  <?php endif; ?>




</div><!-- /.page -->
</body>
</html>
