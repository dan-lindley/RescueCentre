<?php
$page_css_files = [
    'core/css/dashboard.css',
    'core/css/charts.css',
];
include 'dashmain.php';
include 'getcentreinfo.php';
include 'models/all_dataModel.php';
?>
<?=template_admin_header($lang['LM_DASHBOARD'] . ' - ' . $rescue_name . ' - Rescue Centre - Rescue Management System', 'dashboard')?>

<?php include ('views/stats_view.php'); ?> 

<div class="xform-grid">
    <div class="xform-field span-4">
        <div class="content-block">
            <h5 class="card-header"><?php echo ($lang['ADMISSIONS'] ?? 'Admissions') . ' ' . ($lang['MAP'] ?? 'Map'); ?></h5>
            <div class="card-body">
                <?php include __DIR__ . '/operations/map/map.php'; ?>
            </div>
        </div>
    </div>
</div>

<div class="xform-grid">

    <!-- Monthly admissions (wide) -->
    <div class="xform-field span-2">
        <div class="content-block">
            <h5 class="card-header"><?php echo ($lang['ADMISSIONS'] ?? 'Admissions') . ' / ' . ($lang['MONTH'] ?? 'Month'); ?></h5>
            <div class="card-body">
                <?php include ('views/admissions_chart.php'); ?>
            </div>
        </div>
    </div>

    <!-- Presenting complaints (wide) -->
    <div class="xform-field span-2">
        <div class="content-block">
            <h5 class="card-header"><?php echo $lang['PRESENTING_COMPLAINT']; ?></h5>
            <div class="card-body">
                <?php include ('views/complaints_chart.php'); ?>
            </div>
        </div>
    </div>
</div>

    <!-- NEW CHARTS (3 column layout) -->
<div class="xform-grid">
    <div class="xform-field span-2">
        <div class="content-block">
            <h5 class="card-header"><?php echo ($lang['ADMISSIONS'] ?? 'Admissions') . ' / ' . ($lang['DAY'] ?? 'Day'); ?></h5>
            <div class="card-body">
                <?php include ('views/admissions_dayofweek_chart.php'); ?>
            </div>
        </div>
    </div>

    <div class="xform-field span-2">
        <div class="content-block">
            <h5 class="card-header"><?php echo ($lang['ADMISSIONS'] ?? 'Admissions') . ' / ' . ($lang['HOUR'] ?? 'Hour'); ?></h5>
            <div class="card-body">
                <?php include ('views/admissions_hourly_chart.php'); ?>
            </div>
        </div>
    </div>
</div>

<div class="xform-grid">
    <div class="xform-field span-4">
        <div class="content-block">
                 <?php include ('views/admissions_dayhour_heatmap.php'); ?>
        </div>
    </div>
</div>


<?php include 'views/species_chart.php'; ?>

<?=template_admin_footer()?>
