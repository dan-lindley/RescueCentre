<?php
// movements.php
// ------------------------------------------------------------
// VIEW ALL THE PATIENT'S MOVEMENTS (rescue_transfers_log)
// ------------------------------------------------------------

$stmt = $pdo->prepare("
    SELECT
        t.*,
        lf.location_name AS from_location_name,
        lt.location_name AS to_location_name,
        d.disposition     AS disposition_text
    FROM rescue_transfers_log t
    LEFT JOIN rescue_locations lf
        ON lf.location_id = t.from_location_id
    LEFT JOIN rescue_locations lt
        ON lt.location_id = t.to_location_id
    LEFT JOIN rescue_dispositions d
        ON d.disposition_id = t.disposition_id
    WHERE t.patient_id = :patient_id
      AND t.centre_id  = :centre_id
    ORDER BY t.event_at DESC, t.transfer_id DESC
");
$stmt->execute([
    ':patient_id' => (int)$patient_id,
    ':centre_id'  => (int)$centre_id
]);
$movements = $stmt->fetchAll(PDO::FETCH_ASSOC);

function movement_badge_class(string $event_type): string {
    // Uses existing CSS alert colours (no new colours)
    // admission = amber, internal = blue, discharge = green, death = red, transfer out = green
    switch ($event_type) {
        case 'admission':      return 'amber';
        case 'internal_move':  return 'blue';
        case 'released':       return 'green';
        case 'transfer_out':   return 'green';
        case 'died':           return 'red';
        case 'euthanised':     return 'red';
        default:               return 'blue';
    }
}

function movement_label(array $row): string {
    $t = (string)($row['event_type'] ?? '');
    if ($t === 'admission') return 'Admission';
    if ($t === 'internal_move') return 'Internal move';
    if ($t === 'released') return 'Released';
    if ($t === 'transfer_out') return 'Transferred out';
    if ($t === 'died') return 'Died';
    if ($t === 'euthanised') return 'Euthanised';

    // Fallback: show disposition text if available
    if (!empty($row['disposition_text'])) return (string)$row['disposition_text'];

    return ($t !== '') ? ucfirst(str_replace('_', ' ', $t)) : 'Movement';
}
?>

<?php if (empty($movements)): ?>

    <div class="rc-alert blue">
        <strong>Movements</strong><br>
        No movements recorded for this patient.
    </div>

<?php else: ?>

    <?php foreach ($movements as $row): ?>

        <?php
            $event_type = (string)$row['event_type'];
            $box_class  = movement_badge_class($event_type);
            $label      = movement_label($row);

            $event_at   = $row['event_at'] ?? null;
            $dt         = $event_at ? new DateTime($event_at) : null;
            $fmt_date   = $dt ? $dt->format('d-m-Y') : '';
            $fmt_time   = $dt ? $dt->format('H:i') : '';

            $from_name  = trim((string)($row['from_location_name'] ?? ''));
            $to_name    = trim((string)($row['to_location_name'] ?? ''));

            // Compact display strings
            $from_disp  = ($from_name !== '') ? $from_name : '—';
            $to_disp    = ($to_name !== '') ? $to_name : '—';

            $notes      = trim((string)($row['notes'] ?? ''));
            $has_outcome_text = !empty($row['disposition_text']);
            $outcome_text = $has_outcome_text ? trim((string)$row['disposition_text']) : '';
        ?>

        <div class="rc-alert <?= htmlspecialchars($box_class) ?>">

            <table class="rc-table">
                <colgroup>
                    <col style="width:34%;">
                    <col style="width:46%;">
                    <col style="width:20%;">
                </colgroup>

                <thead>
                    <tr style="font-size:0.75rem; opacity:0.85;">
                        <th align="left">Event</th>
                        <th align="left">From → To</th>
                        <th align="left">Date</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <!-- COLUMN 1: Event -->
                        <td style="padding:4px 8px 4px 0; vertical-align:top; overflow:hidden;">
                            <strong><?= htmlspecialchars($label) ?></strong><br>

                            <?php if ($has_outcome_text && $outcome_text !== '' && $event_type !== 'internal_move' && $event_type !== 'admission'): ?>
                                <span style="font-size:0.8em;">
                                    <?= htmlspecialchars($outcome_text) ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($notes !== ''): ?>
                                <div style="font-size:0.8em; margin-top:4px; opacity:0.95;">
                                    <?= nl2br(htmlspecialchars($notes)) ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- COLUMN 2: From -> To -->
                        <td style="padding:4px 8px; vertical-align:top; overflow:hidden;">
                            <?php if ($event_type === 'internal_move'): ?>
                                <strong><?= htmlspecialchars($from_disp) ?></strong>
                                <span style="opacity:0.85;"> → </span>
                                <strong><?= htmlspecialchars($to_disp) ?></strong>
                            <?php elseif ($event_type === 'admission'): ?>
                                <span style="opacity:0.85;">To:</span>
                                <strong><?= htmlspecialchars($to_disp) ?></strong>
                            <?php else: ?>
                                <span style="opacity:0.85;">From:</span>
                                <strong><?= htmlspecialchars($from_disp) ?></strong>
                            <?php endif; ?>

                            <div style="font-size:0.8em; margin-top:3px; opacity:0.9;">
                                Admission ID: <?= (int)$row['admission_id'] ?>
                            </div>
                        </td>

                        <!-- COLUMN 3: Date -->
                        <td style="padding:4px 0; vertical-align:top; white-space:nowrap;">
                            <?= htmlspecialchars($fmt_date) ?>
                            <strong><?= htmlspecialchars($fmt_time) ?></strong>
                        </td>
                    </tr>
                </tbody>
            </table>

        </div>

    <?php endforeach; ?>

<?php endif; ?>
