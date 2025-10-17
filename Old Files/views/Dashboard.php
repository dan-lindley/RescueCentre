<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Centre Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<h2>Centre Dashboard</h2>

<form method="get">
    <label>Year:
        <input type="number" name="year" value="<?= htmlspecialchars($filters['year']) ?>">
    </label>

    <br><label>Species:</label><br>
    <select name="species[]" multiple>
        <?php foreach ($speciesList as $sp): ?>
            <option value="<?= htmlspecialchars($sp) ?>"
                <?= in_array($sp, (array)$filters['species']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($sp) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Disposition:</label>
    <select name="disposition[]" multiple>
        <?php foreach ($dispositionList as $d): ?>
            <option value="<?= htmlspecialchars($d) ?>"
                <?= in_array($d, (array)$filters['disposition']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($d) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Apply Filters</button>
</form>

<div>
    <div class="card-body">
		<div class="chart-area">
            <canvas id="speciesChart"></canvas></div>
        </div>
    <canvas id="monthlyChart"></canvas>
    <canvas id="dispositionChart"></canvas>
</div>

<script>
const speciesData = <?= json_encode($data['species']); ?>;
const monthlyData = <?= json_encode($data['monthly']); ?>;
const disposData  = <?= json_encode($data['dispos']); ?>;

// Species Chart
if (speciesData.length) {
    new Chart(document.getElementById('speciesChart'), {
        type: 'bar',
        data: {
            labels: speciesData.map(i => i.animal_species),
            datasets: [{ label: 'Species', data: speciesData.map(i => i.total) }]
        }
    });
}

// Monthly Admissions
if (monthlyData.length) {
    new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: {
            labels: monthlyData.map(i => i.month),
            datasets: [{ label: 'Admissions', data: monthlyData.map(i => i.total) }]
        }
    });
}

// Dispositions
if (disposData.length) {
    new Chart(document.getElementById('dispositionChart'), {
        type: 'pie',
        data: {
            labels: disposData.map(i => i.disposition),
            datasets: [{ label: 'Dispositions', data: disposData.map(i => i.total) }]
        }
    });
}
</script>
</body>
</html>
