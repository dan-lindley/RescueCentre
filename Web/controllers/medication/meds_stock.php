<?php
// ------------------------------------------------------------
// meds_stock.php
// Stock-based medication administration (UI only)
// ------------------------------------------------------------

// Expected from wrapper:
// $patient_id, $patient_name, $stock_items

// Get the prescription information
$prescriptionSoftDeleteFilter = '';
try {
    $colStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'rescue_prescriptions'
          AND COLUMN_NAME = 'is_deleted'
    ");
    $colStmt->execute();
    if ((int)$colStmt->fetchColumn() > 0) {
        $prescriptionSoftDeleteFilter = " AND COALESCE(is_deleted, 0) = 0";
    }
} catch (Throwable $e) {
    $prescriptionSoftDeleteFilter = '';
}

$stmt = $pdo->prepare("
    SELECT
        medication,
        route,
        dose,
        dose_type,
        by_weight
    FROM rescue_prescriptions
    WHERE patient_id = :pid
      AND DATE_ADD(date, INTERVAL duration DAY) >= CURDATE()
      {$prescriptionSoftDeleteFilter}
");
$stmt->execute([':pid' => $patient_id]);
$active_prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$wStmt = $pdo->prepare("
    SELECT weight, weight_unit, date
    FROM rescue_weights
    WHERE patient_id = :pid
    ORDER BY date DESC
    LIMIT 1
");
$wStmt->execute([':pid' => $patient_id]);
$last_weight = $wStmt->fetch(PDO::FETCH_ASSOC);



?>
<?php if (!empty($active_prescriptions)): ?>
    <div class="rc-alert blue"
     id="active-prescriptions-alert"
     data-patient-weight="<?= $last_weight ? htmlspecialchars($last_weight['weight']) : '' ?>"
     data-patient-weight-unit="<?= $last_weight ? htmlspecialchars(strtolower($last_weight['weight_unit'])) : '' ?>">


        <strong><?= htmlspecialchars($lang['ACTIVE'] . ' ' . $lang['PRESCRIPTION']) ?></strong>

        <div style="margin-top: 6px;">
            <div style="margin-bottom: 6px;">
    <strong><?= htmlspecialchars($lang['LAST_RECORDED_WEIGHT']) ?>:</strong>
    <?php if ($last_weight): ?>
        <?= htmlspecialchars($last_weight['weight']) ?>
        <?= htmlspecialchars($last_weight['weight_unit']) ?>
        (<?= date('j M Y', strtotime($last_weight['date'])) ?>)
    <?php else: ?>
        <?= htmlspecialchars(strtolower($lang['UNAVAILABLE'])) ?>
    <?php endif; ?>
</div>
            <table class="rc-table">
                <tbody>

                <?php foreach ($active_prescriptions as $rx): ?>

                    <?php
                        $isMl = ($rx['dose_type'] === 'ml');
                        $displayMedication = $rx['medication'];
                        if (!empty($rx['route'])) {
                            $displayMedication .= ' (' . $rx['route'] . ')';
                        }

                        if ((int)$rx['by_weight'] === 1) {
                            $prescribed = $rx['dose'] . ' ' . $rx['dose_type'] . '/kg';
                        } else {
                            $prescribed = $rx['dose'] . ' ' . $rx['dose_type'];
                        }
                    ?>

                    <tr
                        class="active-prescription-row"
                        data-dose="<?= htmlspecialchars($rx['dose']) ?>"
                        data-dose-type="<?= htmlspecialchars($rx['dose_type']) ?>"
                        data-by-weight="<?= (int)$rx['by_weight'] ?>"
                    >
                        <td style="padding-top: 4px; padding-right: 12px; font-weight: 600;">
                            <?= htmlspecialchars($displayMedication) ?>
                        </td>

                        <td style="padding-top: 4px; padding-right: 12px;">
                            <?= htmlspecialchars($prescribed) ?>
                        </td>

                        <td
                            class="calculated-volume"
                            style="padding-top: 4px;"
                        > 
                            <?php if ($isMl): ?>
                             <?= htmlspecialchars($rx['dose']) ?> ml
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                <?php endforeach; ?>

                </tbody>
            </table>
        </div>

    </div>
<?php endif; ?>





<form action="controllers/medication/medication_handler.php"
      method="post"
      class="xform medication-form">

    <div class="xform-grid">

        <!-- Stock medication / batch -->
        <div class="xform-field span-2">
            <label class="xform-label"><?= htmlspecialchars($lang['LM_MEDICATION'] . ' (' . $lang['MED_STOCK_BATCH'] . ')') ?></label>

            <select name="stock_item_used"
                    id="stock_item_used"
                    class="xform-input"
                    required>

                <option value="" disabled selected>
                    Select medication batch…
                </option>

                <?php foreach ($stock_items as $s): ?>

    <?php
        // SAFE extraction (prevents undefined index & NULL warnings)
        $dose       = $s['concentration_dose']        ?? null;
        $doseUnit   = $s['concentration_dose_type']   ?? 'mg';
        $volume     = $s['concentration_volume']      ?? null;
        $volumeUnit = $s['concentration_volume_type'] ?? 'ml';
        $batch      = $s['batch_number']              ?? '';
        $exp        = $s['expiry']                    ?? '';
        $name       = $s['common_name'] ?? $s['medication_name'] ?? '';
    ?>

    <option value="<?= (int)$s['med_trans_id'] ?>"
        data-dose="<?= $dose !== null ? htmlspecialchars((string)$dose, ENT_QUOTES) : '' ?>"
        data-dose-unit="<?= htmlspecialchars($doseUnit, ENT_QUOTES) ?>"
        data-volume="<?= $volume !== null ? htmlspecialchars((string)$volume, ENT_QUOTES) : '' ?>"
        data-volume-unit="<?= htmlspecialchars($volumeUnit, ENT_QUOTES) ?>"
        data-batch="<?= htmlspecialchars($batch, ENT_QUOTES) ?>"
        data-exp="<?= htmlspecialchars($exp, ENT_QUOTES) ?>">

        <?= htmlspecialchars($name) ?>
        — <?= htmlspecialchars((string)$dose) ?><?= htmlspecialchars($doseUnit) ?>
        in <?= htmlspecialchars((string)$volume) ?><?= htmlspecialchars($volumeUnit) ?>
        (BN <?= htmlspecialchars($batch) ?>,
         Exp <?= htmlspecialchars($exp) ?>)

    </option>

<?php endforeach; ?>


            </select>
        </div>

        <!-- Pack selector -->
        <div class="xform-field span-2">
            <label class="xform-label"><?= htmlspecialchars($lang['PACK']) ?></label>

            <select name="pack_id"
                    id="pack_id"
                    class="xform-input"
                    required>

                <option value="">
                    <?= htmlspecialchars($lang['MED_SELECT_PACK']) ?>
                </option>

            </select>
        </div>

        <!-- Dose + Volume (side by side, editable) -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['MEDS_COL_DOSE']) ?></label>
            <input type="number"
                   step="0.001"
                   name="dose"
                   id="dose"
                   class="xform-input"
                   required>
        </div>

        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['VOLUME_USED']) ?></label>
            <input type="number"
                   id="volume_used"
                   step="0.001"
                   name="volume_used"
                   class="xform-input"
                   required>
        </div>

        <!-- Dose type -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['DOSE_TYPE']) ?></label>
            <select name="dose_type" class="xform-input" id="dose_type">
                <option value="mcg">mcg</option>
                <option value="mg" selected>mg</option>
                <option value="g">g</option>
                <option value="ml">ml</option>
                <option value="spray">spray</option>
            </select>
        </div>

        <!-- Date -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['GIVEN_ON']) ?></label>
            <input type="datetime-local"
                   name="date_given"
                   class="xform-input"
                   required>
        </div>

        <!-- Batch + Expiry (read-only, auto-filled later) -->
        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['MED_STOCK_BATCH_NUMBER']) ?></label>
            <input type="text"
                   name="bn_given"
                   id="bn_given"
                   class="xform-input"
                   readonly>
        </div>

        <div class="xform-field">
            <label class="xform-label"><?= htmlspecialchars($lang['MED_STOCK_EXPIRY_DATE']) ?></label>
            <input type="date"
                   name="exp_given"
                   id="exp_given"
                   class="xform-input"
                   readonly>
        </div>

    </div>

    <!-- Context -->
    <input type="hidden" name="patient_id" value="<?= (int)$patient_id ?>">
    <input type="hidden" name="centre_id" value="<?= (int)$GLOBALS['centre_id'] ?>">
    <input type="hidden" name="given_by" value="<?= htmlspecialchars($GLOBALS['record_name']) ?>">
    <input type="hidden" name="given_by_id" value="<?= (int)$GLOBALS['user_id'] ?>">

    <div class="xform-actions">
        <button type="submit"
                name="medicationform_stock"
                class="btn blue">
            <?= htmlspecialchars($lang['ADD'] . ' ' . $lang['LM_MEDICATION'] . ' ' . $lang['FOR'] . ' ' . $patient_name) ?>
        </button>
    </div>

</form>
<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.medication-form').forEach(function (form) {

        // Guard against duplicate bindings (safe in loops/includes)
        if (form.dataset.shortfallBound === '1') return;
        form.dataset.shortfallBound = '1';

        const stockSelect = form.querySelector('#stock_item_used');
        const packSelect  = form.querySelector('#pack_id');
        const bnField     = form.querySelector('#bn_given');
        const expField    = form.querySelector('#exp_given');
        const volumeInput = form.querySelector('#volume_used');

        if (!stockSelect || !packSelect || !volumeInput) return;

        // Create warning box once per form
        let warnBox = form.querySelector('.stock-shortfall-warning');
        if (!warnBox) {
            warnBox = document.createElement('div');
            warnBox.className = 'stock-shortfall-warning rc-alert red';
            warnBox.style.display = 'none';
            warnBox.style.marginTop = '10px';
            volumeInput.closest('.xform-field').appendChild(warnBox);
        }

        function checkShortfall() {
            const opt = packSelect.options[packSelect.selectedIndex];
            if (!opt) { warnBox.style.display = 'none'; return; }

            const remaining = parseFloat(opt.dataset.remaining || 0);
            const used      = parseFloat(volumeInput.value || 0);

            if (remaining > 0 && used > remaining) {
                warnBox.innerHTML =
                    '<strong>' + <?= json_encode($lang['MED_STOCK_SHORTFALL']) ?> + '</strong><br>' +
                    <?= json_encode(ucfirst($lang['MED_STOCK_REMAINING']) . ': ') ?> + `<strong>${remaining}</strong><br>` +
                    <?= json_encode($lang['MED_STOCK_SUBMIT_SHORTFALL']) ?>;
                warnBox.style.display = 'block';
            } else {
                warnBox.style.display = 'none';
            }
        }

        // Existing pack loader (kept as-is)
        stockSelect.addEventListener('change', function () {

            const medTransId = this.value;

            packSelect.innerHTML = '<option value="">' + <?= json_encode($lang['MED_SELECT_PACK']) ?> + '</option>';
            if (bnField)  bnField.value  = '';
            if (expField) expField.value = '';
            warnBox.style.display = 'none';

            if (!medTransId) return;

            fetch(`controllers/medication/get_packs.php?med_trans_id=${medTransId}`)
                .then(res => res.json())
                .then(data => {

                    const packs = data.packs || [];

                    packs.sort((a, b) => {
                        if (a.status === 'opened' && b.status !== 'opened') return -1;
                        if (a.status !== 'opened' && b.status === 'opened') return 1;
                        return 0;
                    });

                    packs.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.pack_id;

                        opt.dataset.batch     = p.batch_number;
                        opt.dataset.exp       = p.expiry || '';
                        opt.dataset.remaining = p.amount_remaining;

                        opt.textContent =
                            `${p.medication_name} — Batch ${p.batch_number} — ` +
                            `${p.amount_remaining}${p.unit} remaining — ${p.status.toUpperCase()}`;

                        packSelect.appendChild(opt);
                    });
                })
                .catch(err => console.error('Error loading packs:', err));
        });

        // Fill batch/expiry + run shortfall check
        packSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (!opt) return;
            if (bnField)  bnField.value  = opt.dataset.batch || '';
            if (expField) expField.value = opt.dataset.exp   || '';
            checkShortfall();
        });

        // Recheck on volume change
        volumeInput.addEventListener('input', checkShortfall);

    });

});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {

  document.querySelectorAll('.medication-form').forEach(function(form) {

    // Guard against duplicate bindings
    if (form.dataset.doseCalcBound === '1') return;
    form.dataset.doseCalcBound = '1';

    const stockSelect = form.querySelector('#stock_item_used');
    const doseInput   = form.querySelector('#dose');
    const volInput    = form.querySelector('#volume_used'); // ID MUST NOT CHANGE (respected)
    const doseType    = form.querySelector('#dose_type');

    if (!stockSelect || !doseInput || !volInput || !doseType) return;

    let updating = false;

    function doseToMg(v, u) {
      if (isNaN(v)) return 0;
      return (u === 'g') ? v * 1000 : (u === 'mcg') ? v / 1000 : v;
    }

    function mgToDose(v, u) {
      if (isNaN(v)) return 0;
      return (u === 'g') ? v / 1000 : (u === 'mcg') ? v * 1000 : v;
    }

    function volumeToMl(v, u) {
      if (isNaN(v)) return 0;
      return (u === 'l') ? v * 1000 : v;
    }

    function mlToVolume(v, u) {
      if (isNaN(v)) return 0;
      return (u === 'l') ? v / 1000 : v;
    }

    function round3(v) {
      return Math.round(v * 1000) / 1000;
    }

    function getProfile() {
      const opt = stockSelect.options[stockSelect.selectedIndex];
      if (!opt) return null;

      const dose = parseFloat(opt.dataset.dose);
      const vol  = parseFloat(opt.dataset.volume);

      if (!isFinite(dose) || !isFinite(vol) || vol <= 0) return null;

      return {
        dose: dose,
        doseUnit: opt.dataset.doseUnit || 'mg',
        volume: vol,
        volumeUnit: opt.dataset.volumeUnit || 'ml'
      };
    }

    // Dose -> Volume
    doseInput.addEventListener('input', function() {
      if (updating) return;

      const p = getProfile();
      const d = parseFloat(doseInput.value);
      if (!p || !isFinite(d)) return;

      const doseMg = doseToMg(d, doseType.value);
      const concMg = doseToMg(p.dose, p.doseUnit);
      const concMl = volumeToMl(p.volume, p.volumeUnit);

      const mgPerMl = concMg / concMl;
      if (!isFinite(mgPerMl) || mgPerMl <= 0) return;

      updating = true;
      const volMl = doseMg / mgPerMl;
      volInput.value = round3(mlToVolume(volMl, p.volumeUnit));
      updating = false;
    });

    // Volume -> Dose
    volInput.addEventListener('input', function() {
      if (updating) return;

      const p = getProfile();
      const v = parseFloat(volInput.value);
      if (!p || !isFinite(v)) return;

      const concMg = doseToMg(p.dose, p.doseUnit);
      const concMl = volumeToMl(p.volume, p.volumeUnit);

      const mgPerMl = concMg / concMl;
      if (!isFinite(mgPerMl) || mgPerMl <= 0) return;

      const volMl = volumeToMl(v, p.volumeUnit);

      updating = true;
      const doseMg = volMl * mgPerMl;
      doseInput.value = round3(mgToDose(doseMg, doseType.value));
      updating = false;
    });

  });

});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {

  /* ------------------------------------
     Read patient weight from alert box
  ------------------------------------ */
 const alertBox = document.getElementById('active-prescriptions-alert');

  if (!alertBox) return;

  const rawWeight = parseFloat(alertBox.dataset.patientWeight);
  const rawUnit   = (alertBox.dataset.patientWeightUnit || '').toLowerCase();

  function weightToKg(value, unit) {
    if (!isFinite(value)) return null;
    if (unit === 'kg')  return value;
    if (unit === 'g')   return value / 1000;
    if (unit === 'lbs') return value * 0.453592;
    return null;
  }

  const patientWeightKg = weightToKg(rawWeight, rawUnit);

  /* ------------------------------------
     Helpers
  ------------------------------------ */
  function round3(v) {
    return Math.round(v * 1000) / 1000;
  }

  function doseToMg(value, unit) {
    if (!isFinite(value)) return null;
    unit = unit.replace('/kg', '');
    if (unit === 'mg')  return value;
    if (unit === 'mcg') return value / 1000;
    return null;
  }

  function getMgPerMl(select) {
    const opt = select.options[select.selectedIndex];
    if (!opt) return null;

    const dose   = parseFloat(opt.dataset.dose);
    const volume = parseFloat(opt.dataset.volume);

    if (!isFinite(dose) || !isFinite(volume) || volume <= 0) return null;
    return dose / volume;
  }

  /* ------------------------------------
     Main calculation
  ------------------------------------ */
  function updateCalculatedVolumes(stockSelect) {

    const mgPerMl = getMgPerMl(stockSelect);
    if (!mgPerMl) return;

    document.querySelectorAll('.active-prescription-row').forEach(row => {

      const cell = row.querySelector('.calculated-volume');
      if (!cell) return;

      const doseVal  = parseFloat(row.dataset.dose);
      const doseType = (row.dataset.doseType || '').toLowerCase();
      const byWeight = row.dataset.byWeight === '1';

      // ml prescriptions already handled
      if (doseType === 'ml') return;

      const doseMg = doseToMg(doseVal, doseType);
      if (doseMg === null) {
        cell.textContent = '—';
        return;
      }

      let totalMg;

      if (byWeight) {
        if (!patientWeightKg) {
          cell.textContent = '—';
          return;
        }
        totalMg = doseMg * patientWeightKg;
      } else {
        totalMg = doseMg;
      }

      const volMl = totalMg / mgPerMl;
      if (!isFinite(volMl)) {
        cell.textContent = '—';
        return;
      }

      cell.textContent = <?= json_encode($lang['MED_CALCULATED_VOLUME'] . ': ') ?> + round3(volMl) + ' ml' +
        (byWeight ? ' (' + <?= json_encode($lang['MED_BASED_ON']) ?> + ' ' + round3(patientWeightKg) + ' kg)' : '');
    });
  }

  /* ------------------------------------
     Bind per medication form
  ------------------------------------ */
  document.querySelectorAll('.medication-form').forEach(form => {

    const stockSelect = form.querySelector('select[name="stock_item_used"]');
    if (!stockSelect) return;

    stockSelect.addEventListener('change', function () {
      updateCalculatedVolumes(stockSelect);
    });
  });

});
</script>
