<div class="content-block">

<h2><?= $lang['MED_STOCK_ADD_TITLE'] ?? 'Add Medication to Stock' ?></h2>

<form action="controllers/medication/stock_add_batch.php" method="post" class="xform">

    <div class="xform-grid">

        <!-- Medication Profile -->
        <div class="xform-field span-4">
            <label class="xform-label"><?= $lang['MED_PROFILE'] ?? 'Medication Profile' ?></label>
            <select name="med_profile_id" class="xform-input" required>
                <option value=""><?= $lang['SELECT_MEDICATION'] ?? 'Select medication...' ?></option>

                <?php
                // Load all profiles for this centre with full detail
                $stmt = $pdo->prepare("
                    SELECT 
                        msm.medication_profile_id,
                        rm.medication_name,
                        msm.concentration_dose,
                        msm.concentration_volume,
                        msm.pack_quantity,
                        sf.form_code,
                        sf.value_unit
                    FROM rescue_stock_medication msm
                    JOIN rescue_medications rm
                      ON msm.medication = rm.medication_id
                    JOIN rescue_stock_forms sf
                      ON msm.stock_form_id = sf.stock_form_id
                    WHERE msm.centre_id = :cid
                    ORDER BY rm.medication_name ASC
                ");
                $stmt->execute([':cid' => $GLOBALS['centre_id']]);

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                    $name   = $row['medication_name'];
                    $dose   = $row['concentration_dose'];
                    $vol    = $row['concentration_volume'];
                    $pack   = $row['pack_quantity'];
                    $unit   = $row['value_unit'];
                    $form   = $row['form_code'];

                    // Determine pack type label
                    switch ($form) {
                        case 'liquid_ml':
                        case 'drops':
                        case 'spray':
                            $packtype = ($lang['PACKTYPE_BOTTLE'] ?? 'bottle');
                            break;
                        case 'gram':
                            $packtype = ($lang['PACKTYPE_TUBE'] ?? 'tube');
                            break;
                        case 'tablet':
                            $packtype = ($lang['PACKTYPE_PACK'] ?? 'pack');
                            break;
                        default:
                            $packtype = "";
                    }

                    // Build concentration text
                    // Keeping the exact same structure, just translating "in"
                    $concentration = "{$dose}mg " . ($lang['IN'] ?? 'in') . " {$vol}{$unit}";

                    // Final display string
                    $label = "{$name} – {$concentration} – {$pack}{$unit}" . ($packtype ? " ({$packtype})" : "");

                    echo '<option value="' . $row['medication_profile_id'] . '">'
                         . htmlspecialchars($label)
                         . '</option>';
                }
                ?>
            </select>
        </div>

        <!-- Packs In -->
        <div class="xform-field span-2">
            <label class="xform-label"><?= $lang['MED_STOCK_PACKS_RECEIVED'] ?? 'Number of Packs Received' ?></label>
            <input type="number" name="packs_in" class="xform-input" min="1" step="1" required>
        </div>

        <!-- Batch Number -->
        <div class="xform-field span-2">
            <label class="xform-label"><?= $lang['MED_STOCK_BATCH_NUMBER'] ?? 'Batch Number' ?></label>
            <input type="text" name="batch_number" class="xform-input" required>
        </div>

        <!-- Expiry Date -->
        <div class="xform-field span-2">
            <label class="xform-label"><?= $lang['MED_STOCK_EXPIRY_DATE'] ?? 'Expiry Date' ?></label>
            <input type="date" name="expiry" class="xform-input" required>
        </div>

        <!-- Notes -->
        <div class="xform-field span-4">
            <label class="xform-label"><?= $lang['NOTES_OPTIONAL'] ?? 'Notes (optional)' ?></label>
            <textarea name="notes" class="xform-input" rows="3"></textarea>
        </div>

    </div>

    <div class="xform-actions">
        <button type="submit" class="btn blue"><?= $lang['ADD_STOCK'] ?? 'Add Stock' ?></button>
    </div>

</form>
</div>
