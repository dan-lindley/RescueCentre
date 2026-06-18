<?php
// weather_batch.php
require_once('../connect_to_mysql.php');

// --------- CONFIG ----------
$batch_size = 50;
// ---------------------------

// Read filters from query string
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$missing = isset($_GET['missing']) ? (int)$_GET['missing'] : 0;

$offset = ($page - 1) * $batch_size;

// Base WHERE clause
$where = "1=1";
if ($missing === 1) {
    // Only rows with at least one missing weather value
    $where .= " AND (
        w_temp IS NULL OR w_temp = '' OR
        w_humidity IS NULL OR w_humidity = '' OR
        w_wind IS NULL OR w_wind = '' OR
        w_rainfall IS NULL OR w_rainfall = ''
    )";
}

// Count total matching records
$count_sql = "
    SELECT COUNT(*) 
    FROM rescue_admissions
    WHERE $where
";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute();
$total_rows = $count_stmt->fetchColumn();

// Fetch current page of records
$sql = "
    SELECT 
        admission_id,
        admission_date,
        location_lat,
        location_long,
        w_temp,
        w_humidity,
        w_wind,
        w_rainfall
    FROM rescue_admissions
    WHERE $where
    ORDER BY admission_date ASC
    LIMIT :limit OFFSET :offset
";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':limit', $batch_size, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_pages = $total_rows > 0 ? ceil($total_rows / $batch_size) : 1;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Batch Weather Update</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; font-size: 13px; }
        th { background: #f7f7f7; }
        button { padding: 6px 10px; font-size: 13px; cursor: pointer; }
        .status { font-size: 12px; }
        .status.ok { color: green; }
        .status.err { color: red; }
        .controls { margin-bottom: 15px; }
        .controls form { display: inline-block; margin-right: 15px; }
        .badge { display:inline-block; padding:2px 6px; font-size:11px; border-radius:4px;}
        .badge-missing { background:#f0ad4e; color:#fff; }
        .badge-complete { background:#5cb85c; color:#fff; }
        .pagination { margin-top:15px; }
        .pagination a { margin-right:10px; text-decoration:none; }
        .pagination span.current { font-weight:bold; }
    </style>
</head>
<body>

<h2>Batch Weather Update</h2>

<div class="controls">
    <form method="get" action="weather_batch.php">
        <label>
            <input type="checkbox" name="missing" value="1" <?php if ($missing === 1) echo 'checked'; ?>>
            Show only records with missing weather
        </label>
        <input type="hidden" name="page" value="1">
        <button type="submit">Apply</button>
    </form>

    <span>Total matching records: <strong><?= (int)$total_rows ?></strong></span>
</div>

<?php if (empty($rows)): ?>
    <p><strong>No records found for this view.</strong></p>
<?php else: ?>

<table>
    <thead>
        <tr>
            <th>Admission ID</th>
            <th>Admission Date</th>
            <th>Lat / Long</th>
            <th>Temp (°C)</th>
            <th>Humidity (%)</th>
            <th>Wind (mph)</th>
            <th>Rainfall (mm)</th>
            <th>Status</th>
            <th>Update</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $r): 
        $id = (int)$r['admission_id'];
        $hasMissing = (
            $r['w_temp'] === null || $r['w_temp'] === '' ||
            $r['w_humidity'] === null || $r['w_humidity'] === '' ||
            $r['w_wind'] === null || $r['w_wind'] === '' ||
            $r['w_rainfall'] === null || $r['w_rainfall'] === ''
        );
    ?>
        <tr id="row-<?= $id ?>">
            <td><?= $id ?></td>
            <td><?= htmlspecialchars($r['admission_date']) ?></td>
            <td><?= htmlspecialchars($r['location_lat']) ?> / <?= htmlspecialchars($r['location_long']) ?></td>
            <td class="cell-temp"><?= $r['w_temp'] !== null && $r['w_temp'] !== '' ? htmlspecialchars($r['w_temp']) : '' ?></td>
            <td class="cell-hum"><?= $r['w_humidity'] !== null && $r['w_humidity'] !== '' ? htmlspecialchars($r['w_humidity']) : '' ?></td>
            <td class="cell-wind"><?= $r['w_wind'] !== null && $r['w_wind'] !== '' ? htmlspecialchars($r['w_wind']) : '' ?></td>
            <td class="cell-rain"><?= $r['w_rainfall'] !== null && $r['w_rainfall'] !== '' ? htmlspecialchars($r['w_rainfall']) : '' ?></td>
            <td class="status-cell">
                <?php if ($hasMissing): ?>
                    <span class="badge badge-missing">Missing</span>
                <?php else: ?>
                    <span class="badge badge-complete">Complete</span>
                <?php endif; ?>
                <div class="status" id="status-<?= $id ?>"></div>
            </td>
            <td>
             
                <button type="button" class="btn-update-one" data-id="<?= $id ?>">Update</button>
                <button type="button" class="btn-check-weather" data-id="<?= $id ?>">Check</button>

            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div style="margin-top:15px;">
    <button id="btn-update-all" type="button">Update All 50 Records</button>
</div>

<div class="pagination">
    <?php
    $qs_base = 'missing=' . $missing;
    if ($page > 1): ?>
        <a href="weather_batch.php?<?= $qs_base ?>&page=<?= $page-1 ?>">&laquo; Previous</a>
    <?php endif; ?>

    <span class="current">Page <?= $page ?> of <?= $total_pages ?></span>

    <?php if ($page < $total_pages): ?>
        <a href="weather_batch.php?<?= $qs_base ?>&page=<?= $page+1 ?>">Next &raquo;</a>
    <?php endif; ?>
</div>

<?php endif; ?>
<div id="weatherModal" style="
    display:none;
    position:fixed;
    left:0; top:0;
    width:100%; height:100%;
    background:rgba(0,0,0,0.6);
    z-index:9999;
">
    <div style="
        background:white;
        width:600px;
        max-height:80%;
        overflow:auto;
        margin:80px auto;
        padding:20px;
        border-radius:6px;
    ">
        <h3>Weather Check</h3>
        <div id="weatherModalContent">Loading…</div>
        <button onclick="closeWeatherModal()">Close</button>
    </div>
</div>

<script>
function showWeatherModal(html) {
    document.getElementById('weatherModalContent').innerHTML = html;
    document.getElementById('weatherModal').style.display = 'block';
}

function closeWeatherModal() {
    document.getElementById('weatherModal').style.display = 'none';
}
</script>

<script>
async function updateRecord(admissionId) {
    const statusEl = document.getElementById('status-' + admissionId);
    if (!statusEl) return;

    statusEl.textContent = 'Updating...';
    statusEl.className = 'status';

    try {
        const resp = await fetch('weather_update_single.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'admission_id=' + encodeURIComponent(admissionId)
        });

        const text = await resp.text();  // <-- get raw response first
        let data;

        try {
            data = JSON.parse(text);
        } catch (e) {
            // Server returned HTML or some error instead of JSON
            statusEl.textContent = 'Server did not return JSON. First 200 chars: ' + text.slice(0, 200);
            statusEl.className = 'status err';
            console.error('Non-JSON response from weather_update_single.php:', text);
            return;
        }

        if (!data.success) {
            statusEl.textContent = 'Error: ' + (data.message || 'Unknown error');
            statusEl.className = 'status err';
            return;
        }

        // Update cells with new data
        const row = document.getElementById('row-' + admissionId);
        if (row) {
            if (data.w_temp !== null)     row.querySelector('.cell-temp').textContent   = data.w_temp;
            if (data.w_humidity !== null) row.querySelector('.cell-hum').textContent    = data.w_humidity;
            if (data.w_wind !== null)     row.querySelector('.cell-wind').textContent   = data.w_wind;
            if (data.w_rainfall !== null) row.querySelector('.cell-rain').textContent   = data.w_rainfall;

            const badgeCell = row.querySelector('.status-cell');
            if (badgeCell) {
                const oldBadge = badgeCell.querySelector('.badge');
                if (oldBadge) oldBadge.remove();
                const badge = document.createElement('span');
                badge.className = 'badge badge-complete';
                badge.textContent = 'Complete';
                badgeCell.insertBefore(badge, statusEl);
            }
        }

        statusEl.textContent = 'Updated';
        statusEl.className = 'status ok';

    } catch (e) {
        statusEl.textContent = 'Error: ' + e.message;
        statusEl.className = 'status err';
    }
}

// Per-row button handler
document.querySelectorAll('.btn-update-one').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-id');
        updateRecord(id);
    });
});

// Update all 50 sequentially, with a delay between calls
document.getElementById('btn-update-all')?.addEventListener('click', async () => {
    const buttons = Array.from(document.querySelectorAll('.btn-update-one'));
    for (let i = 0; i < buttons.length; i++) {
        const id = buttons[i].getAttribute('data-id');
        await updateRecord(id);
        await new Promise(res => setTimeout(res, 1100));
    }
});
</script>

</body>
</html>
