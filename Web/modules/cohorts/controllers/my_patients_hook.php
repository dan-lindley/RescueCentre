<?php
// modules/cohorts/controllers/my_patients_hook.php

function cohorts_module_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cohorts_module_render_my_patients_location_panel(int $centre_id): string
{
    ob_start();
    ?>
    <div id="cohortLocationPanel" class="alert-box alert-grey" style="display:none; margin-bottom:14px;">
        <form method="post" action="modules/cohorts/controllers/cohorts_handler.php" class="xform" style="margin:0;">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="centre_id" value="<?= (int)$centre_id ?>">
            <input type="hidden" name="location_key" id="cohortLocationKey" value="">
            <input type="hidden" name="location_label" id="cohortLocationLabel" value="">
            <input type="hidden" name="location_id" value="0">

            <div style="display:flex; justify-content:space-between; gap:14px; align-items:flex-start; flex-wrap:wrap;">
                <div style="flex:1 1 320px;">
                    <strong>Make cohort</strong>
                    <div id="cohortLocationSummary" style="margin-top:4px; opacity:.85;"></div>
                </div>
                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <a href="module.php?module=cohorts&view=cohorts" class="btn grey">View cohorts</a>
                    <button type="submit" class="btn green">Create cohort</button>
                </div>
            </div>

            <div class="xform-grid" style="margin-top:12px; align-items:end;">
                <div class="xform-field" style="grid-column: span 2;">
                    <label class="xform-label">Cohort name</label>
                    <input type="text" name="cohort_name" id="cohortNameInput" class="xform-input" value="">
                </div>
                <div class="xform-field" style="grid-column: span 2;">
                    <label class="xform-label">Notes</label>
                    <input type="text" name="notes" class="xform-input" placeholder="Optional cohort notes">
                </div>
            </div>

            <div id="cohortPatientChoices" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:8px; margin-top:12px;"></div>
        </form>
    </div>
    <?php
    return (string)ob_get_clean();
}
