<?php
$centre_id = $GLOBALS['centre_id'];

/*
    CORRECT JOIN PATH (based on your fixed schema):

    rescue_medication_packs.pack_id
        → rescue_medication_trans.med_trans_id
            → rescue_stock_medication.medication_profile_id
                → rescue_medications.medication_id
*/

// Load all packs for this centre
$stmt = $pdo->prepare("
    SELECT
    p.pack_id,
    p.status,
    p.amount_remaining,
    p.date_opened,
    p.date_finished,
    p.date_destroyed,

    t.med_trans_id,
    t.batch_number,
    t.expiry,
    t.packs_in,
    t.est_volume,

    sm.medication_profile_id,
    sm.medication AS medication_id,
    sm.pack_quantity,
    sm.stock_form_id,
    sf.value_unit,

    m.medication_name

FROM rescue_medication_packs p
JOIN rescue_medication_trans t
    ON p.med_trans_id = t.med_trans_id
JOIN rescue_stock_medication sm
    ON t.med_profile_id = sm.medication_profile_id   -- THIS IS THE FIXED PART
JOIN rescue_medications m
    ON sm.medication = m.medication_id
JOIN rescue_stock_forms sf
    ON sm.stock_form_id = sf.stock_form_id

WHERE t.centre_id = :cid

ORDER BY m.medication_name ASC, t.expiry ASC, t.batch_number ASC

");
$stmt->execute([':cid' => $centre_id]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo '<div class="rc-alert blue">' . ($lang['MED_STOCK_NONE'] ?? 'No medication in stock.') . '</div>';
    return;
}

/*
    Build structure:
    medication → batches → packs
*/

$medGroups = [];

foreach ($rows as $row) {
    $med = $row['medication_name'];
    $batch = $row['batch_number'];

    if (!isset($medGroups[$med])) {
        $medGroups[$med] = [
            'unit' => $row['value_unit'],
            'batches' => []
        ];
    }

    if (!isset($medGroups[$med]['batches'][$batch])) {
        $medGroups[$med]['batches'][$batch] = [
            'expiry' => $row['expiry'],
            'packs' => []
        ];
    }

    $medGroups[$med]['batches'][$batch]['packs'][] = $row;
}
?>

<script>
function toggleStock(id) {
    const el = document.getElementById(id);
    const arrow = document.getElementById(id + "-arrow");
    if (!el) return;

    if (el.style.display === "none") {
        el.style.display = "block";
        arrow.innerText = "▼";
    } else {
        el.style.display = "none";
        arrow.innerText = "▶";
    }
}
</script>

<?php foreach ($medGroups as $medName => $medData): ?>

    <?php
    $unit = $medData['unit'];
    $allBatches = $medData['batches'];

    $totalPacks = 0;
    $totalRemaining = 0;
    $openPack = null;

    foreach ($allBatches as $batchData) {
        $packs = $batchData['packs'];
        $totalPacks += count($packs);
        foreach ($packs as $pk) {
            $totalRemaining += $pk['amount_remaining'];
            if ($pk['status'] === 'opened' && !$openPack) {
                $openPack = $pk;
            }
        }
    }

    $id = md5($medName);
    ?>
    <div class="content-block rc-stack">

    <!-- ALWAYS VISIBLE SUMMARY ROW -->
    <div class="rc-item" style="cursor:pointer;" onclick="toggleStock('<?= $id ?>')">
        <span id="<?= $id ?>-arrow" style="margin-right:10px;">▶</span>
        <span class="rc-item-main"><strong><?= htmlspecialchars($medName) ?></strong></span>
        <span>
            <strong><?= $totalPacks ?></strong> <?= $lang['MED_STOCK_PACKS'] ?? 'packs' ?>
        </span>
        <span style="margin-left:20px;">
            <strong><?= $totalRemaining ?></strong> <?= htmlspecialchars($unit) ?>
        </span>
    </div>

    <!-- ALWAYS VISIBLE OPEN PACK -->
    <div class="rc-alert blue">
        <?php if ($openPack): ?>
            <strong><?= $lang['MED_STOCK_CURRENT_OPEN_PACK'] ?? 'Current Open Pack:' ?></strong><br>
            <?= $lang['MED_STOCK_BATCH'] ?? 'Batch' ?> <?= htmlspecialchars($openPack['batch_number']) ?>
            (<?= $lang['MED_STOCK_EXP'] ?? 'EXP' ?> <?= htmlspecialchars($openPack['expiry']) ?>) –
            <?= htmlspecialchars($openPack['amount_remaining']) . ' ' . htmlspecialchars($unit) ?>
            <?= $lang['MED_STOCK_REMAINING'] ?? 'remaining' ?>
        <?php else: ?>
            <strong><?= $lang['MED_STOCK_NO_OPEN_PACKS'] ?? 'No open packs.' ?></strong>
        <?php endif; ?>
    </div>

    <!-- COLLAPSIBLE BATCH DETAILS -->
    <div id="<?= $id ?>" class="rc-stack" style="display:none;">

        <?php foreach ($allBatches as $batchNo => $batchData): ?>
            <?php
            $packs = $batchData['packs'];
            $batchRemaining = array_sum(array_column($packs, 'amount_remaining'));
            $batchCount = count($packs);

            $hasOpen = false;
            foreach ($packs as $pk) {
                if ($pk['status'] === 'opened') {
                    $hasOpen = true;
                    break;
                }
            }
            ?>
            <div class="rc-card rc-card-muted">
                <strong><?= $lang['MED_STOCK_BATCH'] ?? 'Batch' ?> <?= htmlspecialchars($batchNo) ?></strong>
                (<?= $lang['MED_STOCK_EXP'] ?? 'EXP' ?> <?= htmlspecialchars($batchData['expiry']) ?>)
                – <?= $lang['MED_STOCK_Packs'] ?? 'Packs' ?>: <?= $batchCount ?>
                – <?= $lang['MED_STOCK_REMAINING'] ?? 'Remaining' ?>: <?= $batchRemaining . ' ' . htmlspecialchars($unit) ?>
                <?php if ($hasOpen): ?>
                    <span class="rc-chip blue">
                        (<?= $lang['MED_STOCK_OPEN'] ?? 'OPEN' ?>)
                    </span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    </div>
    </div>

<?php endforeach; ?>
