<?php
// modules/partner_logs/views/viewpatient.php

require_once __DIR__ . '/../controllers/partner_logs_lib.php';

$stmt = $pdo->prepare("
    SELECT pl.*, pt.partner_type AS partner_type_label
    FROM rescue_partner_log pl
    LEFT JOIN rescue_partner_types pt
        ON pl.partner_type = pt.p_type_id
    WHERE pl.patient_id = :patient_id
    ORDER BY pl.date DESC
");
$stmt->execute([':patient_id' => (int)$patient_id]);
$partner_logs_module_rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>

<?php if (empty($partner_logs_module_rows)): ?>
    <div class="rc-alert amber">
        <strong>Partner Logs</strong><br>
        No partner logs recorded for this patient.
    </div>
<?php else: ?>
    <?php foreach ($partner_logs_module_rows as $row): ?>
        <?php
            $logDate = (string)($row['date'] ?? '');
            $displayDate = $logDate !== '' ? (new DateTime($logDate))->format('d-m-Y') : '';
            $partner = (string)($row['partner_type_label'] ?? 'Partner');
            $logNumber = (string)($row['log_number'] ?? '');
            $notes = (string)($row['log_notes'] ?? '');
            $isCrime = (string)($row['is_crime'] ?? '') === 'Yes';
        ?>
        <div class="rc-card rc-card-muted">
            <table class="rc-table">
                <thead>
                    <tr style="font-size:0.75rem; opacity:0.85;">
                        <th align="left">Date</th>
                        <th align="left">Partner</th>
                        <th align="left">Log number</th>
                        <th align="left">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding:4px 8px 4px 0; white-space:nowrap;">
                            <?= $isCrime ? '<i class="fas fa-exclamation-triangle text-danger" title="Crime Log"></i>&nbsp;' : '' ?>
                            <?= partner_logs_h($displayDate) ?>
                        </td>
                        <td style="padding:4px 8px;"><strong><?= partner_logs_h($partner) ?></strong></td>
                        <td style="padding:4px 8px; white-space:nowrap;"><?= partner_logs_h($logNumber) ?></td>
                        <td style="padding:4px 0;"><?= nl2br(partner_logs_h($notes)) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="rc-card rc-card-muted">
    <?= partner_logs_render_patient_form($pdo, [
        'patient_id' => (int)$patient_id,
        'admission_id' => (int)$admission_id,
    ], [
        'centre_id' => (int)$centre_id,
        'visible' => true,
    ]) ?>
</div>
