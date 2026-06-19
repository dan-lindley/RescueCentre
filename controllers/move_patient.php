<?php
if (!isset($patient_id, $admission_id)) {
    echo '<div class="rc-alert red">' . htmlspecialchars($lang['PATIENT_CONTEXT_MISSING']) . '</div>';
    return;
}

$current_location_id = (int)($current_location_id ?? 0);
$locations_by_area = is_array($locations_by_area ?? null) ? $locations_by_area : [];
$locationSelectId = 'move_location_' . (int)$patient_id;
?>

<?php if (empty($locations_by_area)): ?>
    <div class="rc-alert amber"><?= htmlspecialchars($lang['NO_ACTIVE_LOCATIONS']) ?></div>
<?php else: ?>
    <form method="post" action="controllers/form_handler.php" class="xform">
        <input type="hidden" name="changelocationform" value="1">
        <input type="hidden" name="patient_id" value="<?= (int)$patient_id ?>">
        <input type="hidden" name="admission_id" value="<?= (int)$admission_id ?>">

        <div class="xform-grid">
            <div class="xform-field span-3">
                <label class="xform-label" for="<?= htmlspecialchars($locationSelectId) ?>"><?= htmlspecialchars($lang['NEW_LOCATION']) ?></label>
                <select name="new_location_id" id="<?= htmlspecialchars($locationSelectId) ?>" class="xform-input" required>
                    <option value=""><?= htmlspecialchars($lang['SELECT'] . ' ' . strtolower($lang['LOCATION']) . '...') ?></option>
                    <?php foreach ($locations_by_area as $area => $locations): ?>
                        <optgroup label="<?= htmlspecialchars((string)$area) ?>">
                            <?php foreach ($locations as $loc): ?>
                                <?php $locId = (int)($loc['location_id'] ?? 0); ?>
                                <option value="<?= $locId ?>" <?= $locId === $current_location_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)($loc['location_name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="xform-field">
                <label class="xform-label">&nbsp;</label>
                <button type="submit" class="btn purple" name="changeLocationForm"><?= htmlspecialchars($lang['MOVE_PATIENT']) ?></button>
            </div>
        </div>
    </form>
<?php endif; ?>
