<?php
// views/groups/dashboard/map.php
// Expects:
// - $mapRows
// - $memberCentres
// - $networkStats
// - $rangeLabel
// - $range
// - $gid
// - $centreLocationRows
// - $selectedRadiusMiles
// - $selectedRadiusMeters

if (!isset($mapRows) || !is_array($mapRows)) {
    $mapRows = [];
}

if (!isset($memberCentres) || !is_array($memberCentres)) {
    $memberCentres = [];
}

if (!isset($networkStats) || !is_array($networkStats)) {
    return;
}

$centreLocationRows = isset($centreLocationRows) && is_array($centreLocationRows)
    ? $centreLocationRows
    : [];

$selectedRadiusMiles = isset($selectedRadiusMiles) ? (int)$selectedRadiusMiles : 0;
$selectedRadiusMeters = isset($selectedRadiusMeters) ? (float)$selectedRadiusMeters : 0.0;
$range = isset($range) ? (string)$range : 'all';
$rangeLabel = isset($rangeLabel) ? (string)$rangeLabel : 'All time';

$mapAdmissionsCount     = (int)($networkStats['map_admissions_count'] ?? count($mapRows));
$mapCentresContributing = (int)($networkStats['map_centres_contributing'] ?? 0);

$centreLegend = [];
foreach ($memberCentres as $centreId => $centre) {
    $centreLegend[] = [
        'centre_id'   => (int)($centre['centre_id'] ?? $centreId),
        'centre_name' => (string)($centre['centre_name'] ?? ('Centre ' . $centreId)),
        'colour'      => (string)($centre['colour'] ?? '#2563eb'),
    ];
}

$radiusOptions = [
    0  => 'Off',
    5  => '5mi',
    10 => '10mi',
    15 => '15mi',
    20 => '20mi',
    30 => '30mi',
    50 => '50mi',
];

$mapId = 'groupAdmissionsMap_' . (int)$gid . '_' . substr(md5((string)$gid . '|' . (string)$range . '|' . (string)$selectedRadiusMiles), 0, 10);

$encodedMapRows = json_encode(array_values($mapRows), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$encodedLegend = json_encode(array_values($centreLegend), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$encodedCentreLocations = json_encode(array_values($centreLocationRows), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($encodedMapRows === false) $encodedMapRows = '[]';
if ($encodedLegend === false) $encodedLegend = '[]';
if ($encodedCentreLocations === false) $encodedCentreLocations = '[]';
?>

<style>
.group-map {
    margin-bottom: 14px;
}

.group-map__card {
    border-radius: 18px;
    overflow: hidden;
}

.group-map__head {
    padding: 16px 16px 12px;
    border-bottom: 1px solid var(--rc-border);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    flex-wrap: wrap;
}

.group-map__title {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    line-height: 1.15;
    color: var(--rc-text);
}

.group-map__subtitle {
    margin-top: 5px;
    font-size: 13px;
    color: var(--rc-muted);
}

.group-map__meta {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.group-map__toolbar {
    padding: 12px 16px;
    border-bottom: 1px solid var(--rc-border);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    flex-wrap: wrap;
    background: var(--rc-surface-muted);
}

.group-map__toolbar-block {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.group-map__toolbar-label {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--rc-muted);
}

.group-map__toolbar-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}

.group-map__toolbar-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 46px;
    padding: 7px 10px;
    border-radius: 999px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 800;
    border: 1px solid var(--rc-border);
    color: var(--rc-grey-text);
    background: var(--rc-surface);
    transition: all .18s ease;
}

.group-map__toolbar-pill:hover {
    text-decoration: none;
    background: var(--rc-blue-bg);
    border-color: var(--rc-blue-border);
    color: var(--rc-blue-text);
}

.group-map__toolbar-pill.is-active {
    background: var(--rc-blue-bg);
    border-color: var(--rc-blue-border);
    color: var(--rc-blue-text);
}

.group-map__body {
    display: grid;
    grid-template-columns: minmax(240px, 320px) minmax(0, 1fr);
    gap: 0;
    min-height: 560px;
}

.group-map__sidebar {
    border-right: 1px solid var(--rc-border);
    background: var(--rc-surface-muted);
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.group-map__panel-title {
    font-size: 13px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--rc-muted);
    margin-bottom: 10px;
}

.group-map__panel-copy {
    font-size: 13px;
    color: var(--rc-muted);
    line-height: 1.5;
}

.group-map__legend {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 280px;
    overflow: auto;
    padding-right: 2px;
}

.group-map__legend-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 9px 10px;
    border-radius: 12px;
}

.group-map__legend-left {
    display: flex;
    align-items: center;
    gap: 9px;
    min-width: 0;
}

.group-map__legend-swatch {
    width: 12px;
    height: 12px;
    border-radius: 999px;
    flex: 0 0 auto;
    box-shadow: 0 0 0 2px rgba(255,255,255,0.95), 0 0 0 3px rgba(15,23,42,0.06);
}

.group-map__legend-name {
    font-size: 13px;
    font-weight: 700;
    color: var(--rc-text);
    line-height: 1.2;
    word-break: break-word;
}

.group-map__legend-id {
    font-size: 11px;
    color: var(--rc-muted);
    white-space: nowrap;
}

.group-map__map-wrap {
    position: relative;
    min-height: 560px;
    background: var(--rc-surface-muted);
}

.group-map__canvas {
    height: 100%;
    min-height: 560px;
    width: 100%;
}

.group-map__empty {
    margin: 16px;
    padding: 18px;
    border-radius: 14px;
    font-size: 14px;
    line-height: 1.5;
}

.group-map__overlay-note {
    position: absolute;
    left: 14px;
    bottom: 14px;
    z-index: 500;
    max-width: 430px;
    padding: 10px 12px;
    border-radius: 12px;
    background: color-mix(in srgb, var(--rc-surface) 94%, transparent);
    border: 1px solid var(--rc-border);
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
    font-size: 12px;
    color: var(--rc-muted);
    line-height: 1.45;
}

.group-map__overlay-note strong {
    color: var(--rc-text);
}

.group-map .leaflet-popup-content-wrapper {
    border-radius: 14px;
    box-shadow: 0 14px 34px rgba(15, 23, 42, 0.12);
}

.group-map .leaflet-popup-content {
    margin: 12px 14px;
    min-width: 220px;
}

.group-map__popup-title {
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 8px;
    line-height: 1.2;
}

.group-map__popup-grid {
    display: grid;
    grid-template-columns: 96px 1fr;
    gap: 6px 10px;
    font-size: 12px;
    line-height: 1.4;
}

.group-map__popup-label {
    color: #64748b;
    font-weight: 700;
}

.group-map__popup-value {
    color: #0f172a;
    word-break: break-word;
}

.group-map__popup-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 10px;
    padding: 6px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    background: var(--rc-surface-muted);
    border: 1px solid var(--rc-border);
    color: var(--rc-grey-text);
}

.group-map__popup-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    display: inline-block;
}

.group-map .leaflet-control-layers {
    border-radius: 12px;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.10);
    border: 1px solid var(--rc-border);
}

.group-map .leaflet-control-layers-toggle {
    border-radius: 12px;
}

.group-map-cluster {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    color: #fff;
    font-weight: 900;
    font-size: 13px;
    box-shadow: 0 0 0 4px rgba(255,255,255,.95), 0 0 0 5px rgba(15,23,42,.08);
    border: 2px solid rgba(255,255,255,.96);
    text-align: center;
}

.group-map-centre-icon-wrap {
    position: relative;
    width: 28px;
    height: 36px;
}

.group-map-centre-badge {
    position: absolute;
    left: 2px;
    top: 0;
    width: 24px;
    height: 24px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255,255,255,.97);
    box-shadow: 0 0 0 3px rgba(255,255,255,.95), 0 0 0 4px rgba(15,23,42,.10);
    font-size: 12px;
    line-height: 1;
    color: #fff;
    font-weight: 900;
}

.group-map-centre-tail {
    position: absolute;
    left: 11px;
    top: 20px;
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-top: 10px solid currentColor;
    filter: drop-shadow(0 1px 0 rgba(255,255,255,.55));
}

@media (max-width: 980px) {
    .group-map__body {
        grid-template-columns: 1fr;
    }

    .group-map__sidebar {
        border-right: 0;
        border-bottom: 1px solid var(--rc-border);
    }

    .group-map__map-wrap,
    .group-map__canvas {
        min-height: 460px;
    }
}

@media (max-width: 640px) {
    .group-map__card {
        border-radius: 14px;
    }

    .group-map__head,
    .group-map__toolbar,
    .group-map__sidebar {
        padding: 14px;
    }

    .group-map__map-wrap,
    .group-map__canvas {
        min-height: 400px;
    }

    .group-map__overlay-note {
        max-width: calc(100% - 28px);
        right: 14px;
    }
}
</style>

<div class="group-map">
    <div class="rc-panel group-map__card">
        <div class="group-map__head">
            <div>
                <h3 class="group-map__title">Admissions origin map</h3>
                <div class="group-map__subtitle">
                    Geographic view of admissions across the network
                    • Range: <strong><?= htmlspecialchars($rangeLabel) ?></strong>
                </div>
            </div>

            <div class="rc-chip-row group-map__meta">
                <div class="rc-chip blue">
                    Admissions plotted: <strong><?= number_format($mapAdmissionsCount) ?></strong>
                </div>
                <div class="rc-chip blue">
                    Centres contributing: <strong><?= number_format($mapCentresContributing) ?></strong>
                </div>
                <div class="rc-chip">
                    Centre bases: <strong><?= number_format(count($centreLocationRows)) ?></strong>
                </div>
            </div>
        </div>

        <div class="group-map__toolbar">
            <div class="group-map__toolbar-block">
                <div class="group-map__toolbar-label">Rescue boundary radius</div>
                <div class="group-map__toolbar-pills">
                    <?php foreach ($radiusOptions as $radiusValue => $radiusLabel): ?>
                        <?php
                        $href = 'viewnetwork.php?network_id=' . (int)$gid
                              . '&tab=dashboard'
                              . '&range=' . urlencode($range)
                              . '&radius=' . urlencode((string)$radiusValue);
                        $activeClass = ($selectedRadiusMiles === (int)$radiusValue) ? 'is-active' : '';
                        ?>
                        <a href="<?= htmlspecialchars($href) ?>" class="group-map__toolbar-pill <?= $activeClass ?>">
                            <?= htmlspecialchars($radiusLabel) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="group-map__toolbar-block" style="align-items:flex-end;">
                <div class="group-map__toolbar-label">Current setting</div>
                <div class="group-map__toolbar-pills">
                    <span class="rc-chip blue">
                        Boundary:
                        <strong><?= $selectedRadiusMiles > 0 ? number_format($selectedRadiusMiles) . ' miles' : 'Off' ?></strong>
                    </span>
                </div>
            </div>
        </div>

        <div class="group-map__body">
            <div class="group-map__sidebar">
                <div class="rc-card group-map__panel">
                    <div class="group-map__panel-title">How to read this map</div>
                    <div class="group-map__panel-copy">
                        Admissions appear as small coloured dots.
                        Rescue centres use a larger house-style marker in the same colour.
                        Cluster bubbles show admission counts and stay colour-matched to the relevant centre.
                    </div>
                </div>

                <div class="rc-card group-map__panel">
                    <div class="group-map__panel-title">Centre legend</div>

                    <?php if (empty($centreLegend)): ?>
                        <div class="group-map__panel-copy">No member centre data available.</div>
                    <?php else: ?>
                        <div class="group-map__legend">
                            <?php foreach ($centreLegend as $centre): ?>
                                <div class="rc-card rc-card-muted group-map__legend-item">
                                    <div class="group-map__legend-left">
                                        <span class="group-map__legend-swatch" style="background: <?= htmlspecialchars((string)$centre['colour']) ?>;"></span>
                                        <div class="group-map__legend-name">
                                            <?= htmlspecialchars((string)$centre['centre_name']) ?>
                                        </div>
                                    </div>
                                    <div class="group-map__legend-id">
                                        #<?= number_format((int)$centre['centre_id']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="rc-card group-map__panel">
                    <div class="group-map__panel-title">Map notes</div>
                    <div class="group-map__panel-copy">
                        Admissions use stored admission coordinates.
                        Rescue bases use the coordinates held for each rescue centre.
                        Boundary circles are visual planning guides and use the currently selected mile radius.
                    </div>
                </div>
            </div>

            <div class="group-map__map-wrap">
                <?php if (empty($mapRows) && empty($centreLocationRows)): ?>
                    <div class="rc-alert grey group-map__empty">
                        No map data is available for the selected range.
                    </div>
                <?php else: ?>
                    <div id="<?= htmlspecialchars($mapId) ?>" class="group-map__canvas"></div>

                    <div class="group-map__overlay-note">
                        <strong>Tip:</strong> small dots are admissions, house markers are rescue centres.
                        <?= $selectedRadiusMiles > 0 ? 'The shaded circles show a ' . number_format($selectedRadiusMiles) . '-mile boundary around each rescue centre.' : 'Turn on a radius above to see rescue boundaries.' ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($mapRows) || !empty($centreLocationRows)): ?>
<script>
(function () {
    var mapElId = <?= json_encode($mapId) ?>;
    var admissions = <?= $encodedMapRows ?>;
    var centreLocations = <?= $encodedCentreLocations ?>;
    var selectedRadiusMiles = <?= json_encode((int)$selectedRadiusMiles) ?>;
    var selectedRadiusMeters = <?= json_encode((float)$selectedRadiusMeters) ?>;

    if (typeof L === 'undefined') {
        var mapWrap = document.getElementById(mapElId);
        if (mapWrap) {
            mapWrap.innerHTML = '<div style="padding:18px; color:#64748b; font-size:14px;">Leaflet is not loaded, so the map cannot be displayed.</div>';
        }
        return;
    }

    var mapEl = document.getElementById(mapElId);
    if (!mapEl) return;

    function esc(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDate(value) {
        if (!value) return '—';
        var d = new Date(value);
        if (!isNaN(d.getTime())) {
            return d.toLocaleDateString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
        return esc(value);
    }

    function formatPct(value) {
        var num = parseFloat(value);
        if (!isFinite(num)) return '0.0';
        return num.toFixed(1);
    }

    function hexToRgb(hex) {
        hex = String(hex || '').replace('#', '');
        if (hex.length === 3) {
            hex = hex.split('').map(function (c) { return c + c; }).join('');
        }
        var intVal = parseInt(hex, 16);
        if (isNaN(intVal)) {
            return { r: 37, g: 99, b: 235 };
        }
        return {
            r: (intVal >> 16) & 255,
            g: (intVal >> 8) & 255,
            b: intVal & 255
        };
    }

    function rgbaFromHex(hex, alpha) {
        var rgb = hexToRgb(hex);
        return 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + alpha + ')';
    }

    function makeAdmissionIcon(colour) {
        var html =
            '<div style="' +
            'width:18px;' +
            'height:18px;' +
            'border-radius:999px;' +
            'background:' + esc(colour) + ';' +
            'border:2px solid rgba(255,255,255,.96);' +
            'box-shadow:0 0 0 3px rgba(255,255,255,.95), 0 0 0 4px rgba(15,23,42,.10);' +
            '"></div>';

        return L.divIcon({
            className: 'group-map-marker-icon',
            html: html,
            iconSize: [18, 18],
            iconAnchor: [9, 9],
            popupAnchor: [0, -9]
        });
    }

    function makeCentreIcon(colour) {
        // House-style badge with tail. If emoji rendering varies, the coloured badge still reads clearly as a centre marker.
        var html =
            '<div class="group-map-centre-icon-wrap">' +
                '<div class="group-map-centre-badge" style="background:' + esc(colour) + '; color:#fff;">⌂</div>' +
                '<div class="group-map-centre-tail" style="color:' + esc(colour) + ';"></div>' +
            '</div>';

        return L.divIcon({
            className: 'group-map-centre-icon',
            html: html,
            iconSize: [28, 36],
            iconAnchor: [14, 32],
            popupAnchor: [0, -30]
        });
    }

    function makeClusterIcon(colour, count) {
        count = parseInt(count, 10) || 0;

        var size = 34;
        if (count >= 100) {
            size = 46;
        } else if (count >= 10) {
            size = 40;
        }

        var html =
            '<div class="group-map-cluster" style="' +
                'width:' + size + 'px;' +
                'height:' + size + 'px;' +
                'background:' + esc(colour) + ';' +
                'box-shadow:0 0 0 4px rgba(255,255,255,.96), 0 0 0 5px ' + rgbaFromHex(colour, 0.16) + ';' +
            '">' +
                esc(count) +
            '</div>';

        return L.divIcon({
            className: 'group-map-cluster-wrap',
            html: html,
            iconSize: [size, size]
        });
    }

    function buildAdmissionPopup(row) {
        return '' +
            '<div class="group-map__popup-badge">' +
                '<span class="group-map__popup-dot" style="background:' + esc(row.centre_colour || '#2563eb') + ';"></span>' +
                esc(row.centre_name || 'Centre') +
            '</div>' +
            '<div class="group-map__popup-title">Admission location</div>' +
            '<div class="group-map__popup-grid">' +
                '<div class="group-map__popup-label">Patient ID</div>' +
                '<div class="group-map__popup-value">' + esc(row.patient_id || '—') + '</div>' +

                '<div class="group-map__popup-label">Species</div>' +
                '<div class="group-map__popup-value">' + esc(row.species || 'Unknown') + '</div>' +

                '<div class="group-map__popup-label">Admission</div>' +
                '<div class="group-map__popup-value">' + formatDate(row.admission_date) + '</div>' +

                '<div class="group-map__popup-label">Centre</div>' +
                '<div class="group-map__popup-value">' + esc(row.centre_name || '—') + '</div>' +

                '<div class="group-map__popup-label">Coords</div>' +
                '<div class="group-map__popup-value">' + esc(row.lat) + ', ' + esc(row.lng) + '</div>' +
            '</div>';
    }

    function buildCentrePopup(row) {
        return '' +
            '<div class="group-map__popup-badge">' +
                '<span class="group-map__popup-dot" style="background:' + esc(row.colour || '#0f172a') + ';"></span>' +
                'Rescue centre' +
            '</div>' +
            '<div class="group-map__popup-title">' + esc(row.centre_name || 'Centre') + '</div>' +
            '<div class="group-map__popup-grid">' +
                '<div class="group-map__popup-label">Centre ID</div>' +
                '<div class="group-map__popup-value">' + esc(row.centre_id || '—') + '</div>' +

                '<div class="group-map__popup-label">Occupancy</div>' +
                '<div class="group-map__popup-value">' + formatPct(row.occupancy_percent) + '%</div>' +

                '<div class="group-map__popup-label">Active</div>' +
                '<div class="group-map__popup-value">' + esc(row.active_admissions || '0') + '</div>' +

                '<div class="group-map__popup-label">Capacity</div>' +
                '<div class="group-map__popup-value">' + esc(row.total_capacity || '0') + '</div>' +

                '<div class="group-map__popup-label">Boundary</div>' +
                '<div class="group-map__popup-value">' + (selectedRadiusMiles > 0 ? esc(selectedRadiusMiles + ' miles') : 'Off') + '</div>' +
            '</div>';
    }

    var map = L.map(mapElId, {
        zoomControl: true,
        scrollWheelZoom: true
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var bounds = [];
    var overlayLayers = {};
    var centreLayer = L.layerGroup();
    var radiusLayer = L.layerGroup();

    // Cluster admissions per centre so cluster colour remains true to that centre.
    var admissionsByCentre = {};
    admissions.forEach(function (row) {
        var centreId = String(row.centre_id || '0');
        if (!admissionsByCentre[centreId]) {
            admissionsByCentre[centreId] = {
                centre_id: centreId,
                centre_name: row.centre_name || ('Centre ' + centreId),
                colour: row.centre_colour || '#2563eb',
                rows: []
            };
        }
        admissionsByCentre[centreId].rows.push(row);
    });

    var clusterAvailable = !!(L.markerClusterGroup && typeof L.markerClusterGroup === 'function');

    Object.keys(admissionsByCentre).forEach(function (centreId) {
        var groupMeta = admissionsByCentre[centreId];
        var colour = groupMeta.colour || '#2563eb';

        var layer = clusterAvailable
            ? L.markerClusterGroup({
                showCoverageOnHover: false,
                spiderfyOnMaxZoom: true,
                maxClusterRadius: 45,
                iconCreateFunction: function (cluster) {
                    return makeClusterIcon(colour, cluster.getChildCount());
                }
            })
            : L.layerGroup();

        groupMeta.rows.forEach(function (row) {
            var lat = parseFloat(row.lat);
            var lng = parseFloat(row.lng);

            if (!isFinite(lat) || !isFinite(lng)) return;

            var marker = L.marker([lat, lng], {
                icon: makeAdmissionIcon(colour),
                title: row.centre_name || ''
            });

            marker.bindPopup(buildAdmissionPopup(row), {
                maxWidth: 320
            });

            if (typeof layer.addLayer === 'function') {
                layer.addLayer(marker);
            }

            bounds.push([lat, lng]);
        });

        layer.addTo(map);
        overlayLayers['Admissions: ' + groupMeta.centre_name] = layer;
    });

    centreLocations.forEach(function (row) {
        var lat = parseFloat(row.lat);
        var lng = parseFloat(row.lng);

        if (!isFinite(lat) || !isFinite(lng)) return;

        var centreMarker = L.marker([lat, lng], {
            icon: makeCentreIcon(row.colour || '#0f172a'),
            title: row.centre_name || ''
        });

        centreMarker.bindPopup(buildCentrePopup(row), {
            maxWidth: 320
        });

        centreLayer.addLayer(centreMarker);
        bounds.push([lat, lng]);

        if (selectedRadiusMeters > 0) {
            var circle = L.circle([lat, lng], {
                radius: selectedRadiusMeters,
                color: row.colour || '#2563eb',
                weight: 2,
                opacity: 0.48,
                fillColor: row.colour || '#2563eb',
                fillOpacity: 0.08
            });

            radiusLayer.addLayer(circle);

            try {
                var circleBounds = circle.getBounds();
                bounds.push(circleBounds.getNorthEast());
                bounds.push(circleBounds.getSouthWest());
            } catch (e) {}
        }
    });

    centreLayer.addTo(map);
    overlayLayers['Rescue centres'] = centreLayer;

    if (selectedRadiusMeters > 0) {
        radiusLayer.addTo(map);
        overlayLayers['Boundaries'] = radiusLayer;
    }

    L.control.layers(null, overlayLayers, {
        collapsed: true
    }).addTo(map);

    if (bounds.length) {
        map.fitBounds(bounds, {
            padding: [28, 28]
        });

        if (bounds.length === 1) {
            map.setZoom(10);
        }
    } else {
        map.setView([54.5, -3.0], 5);
    }

    setTimeout(function () {
        map.invalidateSize();
    }, 120);

    window.addEventListener('resize', function () {
        map.invalidateSize();
    });
})();
</script>
<?php endif; ?>
