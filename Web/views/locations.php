<?php
// views/locations.php

$centre_id_int = isset($centre_id) ? (int)$centre_id : 0;
if (!defined('APP_LOADED')) exit;
echo '<div class="content-title">
    <div class="title">
        <div class="txt">
            <h2>Individual Locations    </h2>
            <p>View and manage the locations within your rescue</p>
        </div>
    </div>
</div>';
// ------------------------------------------------------------
// LOAD AREAS (ALL, EVEN IF EMPTY)
// ------------------------------------------------------------
$areaStmt = $pdo->prepare("
    SELECT area_id, area_name
    FROM rescue_areas
    WHERE centre_id = :cid
    ORDER BY area_name ASC
");
$areaStmt->execute([':cid' => $centre_id_int]);
$areas = $areaStmt->fetchAll(PDO::FETCH_ASSOC);

// area names array for dropdowns
$area_names = [];
foreach ($areas as $a) {
    $area_names[] = $a['area_name'];
}

// ------------------------------------------------------------
// LOAD LOCATIONS (NOT DELETED)
// ------------------------------------------------------------
$locStmt = $pdo->prepare("
    SELECT *
    FROM rescue_locations
    WHERE centre_id = :cid
      AND (deleted = 0 OR deleted IS NULL)
    ORDER BY location_name ASC
");
$locStmt->execute([':cid' => $centre_id_int]);
$locations = $locStmt->fetchAll(PDO::FETCH_ASSOC);

// ------------------------------------------------------------
// GROUP LOCATIONS BY AREA
// ------------------------------------------------------------
$locationsByArea = [];
foreach ($areas as $ar) {
    $locationsByArea[$ar['area_name']] = [];
}

$unassignedLocations = [];

foreach ($locations as $loc) {
    $areaName = isset($loc['location_area']) ? trim($loc['location_area']) : '';

    if ($areaName === '') {
        $unassignedLocations[] = $loc;
    } elseif (isset($locationsByArea[$areaName])) {
        $locationsByArea[$areaName][] = $loc;
    } else {
        // Legacy/unknown area name – make sure it still shows
        if (!isset($locationsByArea[$areaName])) {
            $locationsByArea[$areaName] = [];
        }
        $locationsByArea[$areaName][] = $loc;

        // Also ensure it appears as an option in dropdowns
        if (!in_array($areaName, $area_names, true)) {
            $area_names[] = $areaName;
        }
    }
}

// ------------------------------------------------------------
// LOCATION TYPE LIST
// ------------------------------------------------------------
$location_types = [
    "Incubator","Tank","Pen","Kennel","Paddock","Hutch",
    "Aviary","Flight Cage","Cage","Bat Box","Bird box"
];

// ------------------------------------------------------------
// RENDER A SINGLE AREA BLOCK (NO EXTRA WRAPPERS)
// ------------------------------------------------------------
function render_area_block($area_title, $area_id, $locs, $centre_id_int, $location_types, $area_names)
{
    $block_id = (int)$area_id;
    ?>
    <div class="content-block rc-panel" data-area-id="<?php echo $block_id; ?>" data-page="1">

        <h3><?php echo htmlspecialchars($area_title); ?></h3>

        <div class="area-meta" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <span class="area-count">0 results</span>
            <div class="area-pagination-controls">
                <button type="button" class="btn" onclick="changeAreaPage(<?php echo $block_id; ?>, -1)">« Prev</button>
                <span class="area-page-label" style="margin:0 6px;">Page 1 of 1</span>
                <button type="button" class="btn" onclick="changeAreaPage(<?php echo $block_id; ?>, 1)">Next »</button>
            </div>
        </div>

        <table class="rc-table row-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Max</th>
                    <th>Area</th>
                    <th style="width:200px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($locs)): ?>
                <?php foreach ($locs as $loc): ?>
                    <?php
                    $locId      = (int)$loc['location_id'];
                    $formId     = 'locform_' . $locId;
                    $searchBlob = strtolower(
                        (string)$loc['location_name'] . ' ' .
                        (string)$loc['location_type'] . ' ' .
                        (string)$loc['location_area']
                    );
                    ?>
                    <tr class="location-row" data-search="<?php echo htmlspecialchars($searchBlob); ?>">

                        <!-- NAME -->
                        <td>
                            <input
                                type="text"
                                name="location_name"
                                class="xform-input"
                                required
                                form="<?php echo $formId; ?>"
                                value="<?php echo htmlspecialchars($loc['location_name']); ?>"
                            >
                        </td>

                        <!-- TYPE -->
                        <td>
                            <select
                                name="location_type"
                                class="xform-input"
                                required
                                form="<?php echo $formId; ?>"
                            >
                                <option value="">Select...</option>
                                <?php foreach ($location_types as $type): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($type); ?>"
                                        <?php if ($loc['location_type'] === $type) echo 'selected'; ?>
                                    >
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>

                        <!-- MAX (EDITABLE) -->
                        <td>
                            <input
                                type="number"
                                name="max_occupancy"
                                min="1"
                                class="xform-input"
                                form="<?php echo $formId; ?>"
                                value="<?php echo htmlspecialchars($loc['max_occupancy']); ?>"
                            >
                        </td>

                        <!-- AREA (EDITABLE DROPDOWN FROM rescue_areas) -->
                        <td>
                            <select
                                name="location_area"
                                class="xform-input"
                                form="<?php echo $formId; ?>"
                            >
                                <option value="">No Area</option>
                                <?php foreach ($area_names as $areaName): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($areaName); ?>"
                                        <?php if ($loc['location_area'] === $areaName) echo 'selected'; ?>
                                    >
                                        <?php echo htmlspecialchars($areaName); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>

                        <!-- ACTIONS -->
                        <td>
                            <form
                                id="<?php echo $formId; ?>"
                                action="../controllers/locations_handler.php"
                                method="post"
                                style="display:inline;"
                            >
                                <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">
                                <input type="hidden" name="location_id" value="<?php echo $locId; ?>">

                                <button type="submit" name="action" value="update_location" class="btn green">
                                    Update
                                </button>

                                <button
                                    type="submit"
                                    name="action"
                                    value="delete_location"
                                    class="btn red"
                                    onclick="return confirm('Are you sure you want to remove this location?');"
                                >
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- this row is *not* a location-row -> used when 0 matches -->
                <tr class="empty-row">
                    <td colspan="5"><em>No locations in this area.</em></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>

<!-- GLOBAL SEARCH (applies to ALL areas) -->
 <div class="rc-panel">
<div class="xform-field" style="max-width:400px; margin-bottom:20px;">
    <label class="xform-label" for="locationSearch">Search all locations</label>
    <input
        id="locationSearch"
        type="text"
        class="xform-input"
        placeholder="Search by name, type, area..."
    >
</div></div>

<?php
// RENDER EACH AREA BLOCK (one content-block per area)
foreach ($areas as $ar) {
    $areaName = $ar['area_name'];
    $area_id  = (int)$ar['area_id'];
    $locs     = isset($locationsByArea[$areaName]) ? $locationsByArea[$areaName] : [];
    render_area_block($areaName, $area_id, $locs, $centre_id_int, $location_types, $area_names);
}

// RENDER "NO AREA ASSIGNED" BLOCK
render_area_block('No Area Assigned', 0, $unassignedLocations, $centre_id_int, $location_types, $area_names);
?>

<hr>
<div class="rc-panel">
<h3>Add New Location</h3>

<form action="../controllers/locations_handler.php" method="post" class="xform">
    <input type="hidden" name="action" value="add_location">
    <input type="hidden" name="centre_id" value="<?php echo $centre_id_int; ?>">

    <div class="xform-field">
        <label class="xform-label">Location Name</label>
        <input type="text" name="location_name" class="xform-input" required>
    </div>

    <div class="xform-field">
        <label class="xform-label">Location Type</label>
        <select name="location_type" class="xform-input" required>
            <option value="">Select...</option>
            <?php foreach ($location_types as $t): ?>
                <option value="<?php echo htmlspecialchars($t); ?>">
                    <?php echo htmlspecialchars($t); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="xform-field">
        <label class="xform-label">Max Occupancy</label>
        <input type="number" name="max_occupancy" min="1" class="xform-input">
    </div>

    <div class="xform-field">
        <label class="xform-label">Location Area</label>
        <select name="location_area" class="xform-input">
            <option value="">No Area</option>
            <?php foreach ($area_names as $a): ?>
                <option value="<?php echo htmlspecialchars($a); ?>">
                    <?php echo htmlspecialchars($a); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit" class="btn green">Add Location</button>
</form>
            </div>
<script>
// SEARCH + PER-AREA PAGINATION
var PAGE_SIZE = 10;

function changeAreaPage(areaId, delta) {
    var selector = '.content-block[data-area-id="' + areaId + '"]';
    var block = document.querySelector(selector);
    if (!block) return;

    var page = parseInt(block.getAttribute('data-page') || '1', 10);
    page = page + delta;
    if (page < 1) page = 1;
    block.setAttribute('data-page', String(page));

    applyLocationFilters();
}

function applyLocationFilters() {
    var searchInput = document.getElementById('locationSearch');
    var term = searchInput ? searchInput.value.toLowerCase().trim() : '';

    var blocks = document.querySelectorAll('.content-block');

    for (var b = 0; b < blocks.length; b++) {
        var block = blocks[b];
        var page = parseInt(block.getAttribute('data-page') || '1', 10);

        var rows = block.querySelectorAll('tbody tr.location-row');
        var emptyRow = block.querySelector('tbody tr.empty-row');

        var matchingRows = [];
        // First determine which rows match
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            var haystack = (row.getAttribute('data-search') || '').toLowerCase();
            var match = (term === '') || (haystack.indexOf(term) !== -1);
            row.setAttribute('data-match', match ? '1' : '0');
        }

        // Collect matching rows
        for (var j = 0; j < rows.length; j++) {
            var r = rows[j];
            if (r.getAttribute('data-match') === '1') {
                matchingRows.push(r);
            }
        }

        var total = matchingRows.length;
        var totalPages = total > 0 ? Math.ceil(total / PAGE_SIZE) : 1;

        if (page > totalPages) {
            page = totalPages;
            block.setAttribute('data-page', String(page));
        }

        // Hide all rows initially
        for (var k = 0; k < rows.length; k++) {
            rows[k].style.display = 'none';
        }

        if (total === 0) {
            // No matches: show "no locations" row, if present
            if (emptyRow) {
                emptyRow.style.display = '';
            }
        } else {
            // There are matches: hide empty row
            if (emptyRow) {
                emptyRow.style.display = 'none';
            }

            var start = (page - 1) * PAGE_SIZE;
            var end = start + PAGE_SIZE;

            for (var m = 0; m < matchingRows.length; m++) {
                if (m >= start && m < end) {
                    matchingRows[m].style.display = '';
                } else {
                    matchingRows[m].style.display = 'none';
                }
            }
        }

        // Update meta info
        var countLabel = block.querySelector('.area-count');
        var pageLabel  = block.querySelector('.area-page-label');
        var prevBtn    = block.querySelector('.area-pagination-controls .btn:nth-child(1)');
        var nextBtn    = block.querySelector('.area-pagination-controls .btn:nth-child(3)');

        if (countLabel) {
            countLabel.textContent = total + ' result' + (total === 1 ? '' : 's');
        }
        if (pageLabel) {
            if (total === 0) {
                pageLabel.textContent = 'Page 0 of 0';
            } else {
                pageLabel.textContent = 'Page ' + page + ' of ' + totalPages;
            }
        }
        if (prevBtn) {
            prevBtn.disabled = (total === 0 || page <= 1);
        }
        if (nextBtn) {
            nextBtn.disabled = (total === 0 || page >= totalPages);
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var blocks = document.querySelectorAll('.content-block');
    for (var i = 0; i < blocks.length; i++) {
        blocks[i].setAttribute('data-page', '1');
    }

    var searchInput = document.getElementById('locationSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            // Reset to page 1 on search change
            var blocks = document.querySelectorAll('.content-block');
            for (var i = 0; i < blocks.length; i++) {
                blocks[i].setAttribute('data-page', '1');
            }
            applyLocationFilters();
        });
    }

    applyLocationFilters();
});
</script>
