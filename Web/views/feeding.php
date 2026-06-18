<?php
// views/feeding.php
// Feeding tab wrapper for individual patient view
// Assumes patient/admission context already exists

if (!isset($patient_id)) {
    echo '<div class="rc-alert red">Patient context not available.</div>';
    return;
}

require_once __DIR__ . '/../operations/permissions.php';

registerPermission('patients.feeding.delete', 'Delete feeding events', 'action');
?>

    <div class="xform-grid" style="align-items:start;">
        <!-- LEFT COLUMN: FEEDING CHART -->
        <div class="xform-field span-2 rc-card rc-card-muted">
            <?php include __DIR__ . '/feed_chart.php'; ?>
        </div>

        <!-- RIGHT COLUMN: FEED FORM + HISTORY -->
        <div class="xform-field span-2 rc-card rc-card-muted">
            <?php include __DIR__ . '/feeds.php'; ?>
        </div>

    </div>


