<?php
if (!defined('APP_LOADED')) {
    echo '<div class="rc-alert red"><strong>Error</strong><br>APP_LOADED not defined.</div>';
    return;
}

include __DIR__ . '/dashboard_data.php';
?>

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    crossorigin=""
/>
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"
>
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"
>

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    crossorigin="">
</script>
<script
    src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js">
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php include __DIR__ . '/dashboard/summary_strip.php'; ?>
<?php include __DIR__ . '/dashboard/occupancy.php'; ?>
<?php include __DIR__ . '/dashboard/kpis.php'; ?>
<?php include __DIR__ . '/dashboard/map.php'; ?>
<?php include __DIR__ . '/dashboard/admissions_chart.php'; ?>



