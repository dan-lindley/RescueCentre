<?php
// species_chart.php
// INCLUDE ONLY
// Assumes $pdo and $centre_id already exist before include

$spch_totalAdmissionsAllTime = 0;
$spch_totalSpeciesAllTime    = 0;
$spch_mostCommonSpecies      = $lang['NO'];
$spch_mostCommonCount        = 0;
$spch_avgStayOverall         = 0;

$spch_topSpeciesLabels = [];
$spch_topSpeciesCounts = [];

$spch_stayLabels = [];
$spch_stayDays   = [];

$spch_pieLabels = [];
$spch_pieCounts = [];

$spch_monthLabels = [$lang['MONTH_SHORT_JAN'] ?? 'Jan',$lang['MONTH_SHORT_FEB'] ?? 'Feb',$lang['MONTH_SHORT_MAR'] ?? 'Mar',$lang['MONTH_SHORT_APR'] ?? 'Apr',$lang['MONTH_SHORT_MAY'] ?? 'May',$lang['MONTH_SHORT_JUN'] ?? 'Jun',$lang['MONTH_SHORT_JUL'] ?? 'Jul',$lang['MONTH_SHORT_AUG'] ?? 'Aug',$lang['MONTH_SHORT_SEP'] ?? 'Sept',$lang['MONTH_SHORT_OCT'] ?? 'Oct',$lang['MONTH_SHORT_NOV'] ?? 'Nov',$lang['MONTH_SHORT_DEC'] ?? 'Dec'];
$spch_seasonSpecies = [];
$spch_seasonalDatasets = [];

$spch_outcomeLabels   = [];
$spch_outcomeReleased = [];
$spch_outcomeDied     = [];
$spch_outcomeEuth     = [];
$spch_outcomeOther    = [];

// --------------------------------------------------
// Headline totals
// --------------------------------------------------
$spch_sqlTotalAdmissions = "
    SELECT COUNT(*) 
    FROM rescue_admissions
    WHERE centre_id = :spch_centre_id
";
$spch_stmt = $pdo->prepare($spch_sqlTotalAdmissions);
$spch_stmt->execute([':spch_centre_id' => $centre_id]);
$spch_totalAdmissionsAllTime = (int)$spch_stmt->fetchColumn();

$spch_sqlDistinctSpecies = "
    SELECT COUNT(DISTINCT COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown Species'))
    FROM rescue_admissions a
    INNER JOIN rescue_patients p
        ON p.patient_id = a.patient_id
    WHERE a.centre_id = :spch_centre_id
";
$spch_stmt = $pdo->prepare($spch_sqlDistinctSpecies);
$spch_stmt->execute([':spch_centre_id' => $centre_id]);
$spch_totalSpeciesAllTime = (int)$spch_stmt->fetchColumn();

$spch_sqlAvgStayOverall = "
    SELECT ROUND(AVG(DATEDIFF(a.disposition_date, a.admission_date)), 1)
    FROM rescue_admissions a
    WHERE a.centre_id = :spch_centre_id
      AND a.disposition_date IS NOT NULL
      AND a.disposition_date <> ''
      AND a.disposition_date >= a.admission_date
";
$spch_stmt = $pdo->prepare($spch_sqlAvgStayOverall);
$spch_stmt->execute([':spch_centre_id' => $centre_id]);
$spch_avgStayOverall = $spch_stmt->fetchColumn();
$spch_avgStayOverall = ($spch_avgStayOverall !== null) ? (float)$spch_avgStayOverall : 0;

// --------------------------------------------------
// Top species all time
// --------------------------------------------------
$spch_sqlTopSpecies = "
    SELECT
        COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown Species') AS species_name,
        COUNT(a.admission_id) AS total_admissions
    FROM rescue_admissions a
    INNER JOIN rescue_patients p
        ON p.patient_id = a.patient_id
    WHERE a.centre_id = :spch_centre_id
    GROUP BY COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown Species')
    ORDER BY total_admissions DESC, species_name ASC
    LIMIT 8
";
$spch_stmt = $pdo->prepare($spch_sqlTopSpecies);
$spch_stmt->execute([':spch_centre_id' => $centre_id]);

while ($spch_row = $spch_stmt->fetch(PDO::FETCH_ASSOC)) {
    $spch_topSpeciesLabels[] = $spch_row['species_name'];
    $spch_topSpeciesCounts[] = (int)$spch_row['total_admissions'];
}

$spch_mostCommonSpecies = $spch_topSpeciesLabels[0] ?? $lang['NO'];
$spch_mostCommonCount   = $spch_topSpeciesCounts[0] ?? 0;

// --------------------------------------------------
// Species mix pie chart data (top 10 + Other)
// --------------------------------------------------
$spch_sqlSpeciesPie = "
    SELECT
        COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown Species') AS species_name,
        COUNT(a.admission_id) AS total_admissions
    FROM rescue_admissions a
    INNER JOIN rescue_patients p
        ON p.patient_id = a.patient_id
    WHERE a.centre_id = :spch_centre_id
    GROUP BY COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown Species')
    ORDER BY total_admissions DESC, species_name ASC
";
$spch_stmt = $pdo->prepare($spch_sqlSpeciesPie);
$spch_stmt->execute([':spch_centre_id' => $centre_id]);

$spch_allSpeciesRows = $spch_stmt->fetchAll(PDO::FETCH_ASSOC);
$spch_otherTotal = 0;
$spch_maxPieSlices = 10;

foreach ($spch_allSpeciesRows as $spch_index => $spch_row) {
    $spch_speciesName = $spch_row['species_name'];
    $spch_speciesTotal = (int)$spch_row['total_admissions'];

    if ($spch_index < $spch_maxPieSlices) {
        $spch_pieLabels[] = $spch_speciesName;
        $spch_pieCounts[] = $spch_speciesTotal;
    } else {
        $spch_otherTotal += $spch_speciesTotal;
    }
}

if ($spch_otherTotal > 0) {
    $spch_pieLabels[] = $lang['OTHER'];
    $spch_pieCounts[] = $spch_otherTotal;
}

// --------------------------------------------------
// Average stay by species
// --------------------------------------------------
$spch_sqlAvgStayBySpecies = "
    SELECT
        COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown Species') AS species_name,
        ROUND(AVG(DATEDIFF(a.disposition_date, a.admission_date)), 1) AS avg_days,
        COUNT(*) AS total_cases
    FROM rescue_admissions a
    INNER JOIN rescue_patients p
        ON p.patient_id = a.patient_id
    WHERE a.centre_id = :spch_centre_id
      AND a.disposition_date IS NOT NULL
      AND a.disposition_date <> ''
      AND a.disposition_date >= a.admission_date
    GROUP BY COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown Species')
    HAVING total_cases >= 3
    ORDER BY total_cases DESC, species_name ASC
    LIMIT 8
";
$spch_stmt = $pdo->prepare($spch_sqlAvgStayBySpecies);
$spch_stmt->execute([':spch_centre_id' => $centre_id]);

while ($spch_row = $spch_stmt->fetch(PDO::FETCH_ASSOC)) {
    $spch_stayLabels[] = $spch_row['species_name'];
    $spch_stayDays[]   = (float)$spch_row['avg_days'];
}

// --------------------------------------------------
// Seasonal pattern by top 4 species
// average admissions per month across active years
// --------------------------------------------------
$spch_seasonSpecies = array_slice($spch_topSpeciesLabels, 0, 4);

if (!empty($spch_seasonSpecies)) {
    $spch_inParts = [];
    $spch_params = [':spch_centre_id' => $centre_id];

    foreach ($spch_seasonSpecies as $spch_i => $spch_speciesName) {
        $spch_key = ':spch_species_' . $spch_i;
        $spch_inParts[] = $spch_key;
        $spch_params[$spch_key] = $spch_speciesName;
    }

    $spch_sqlSeasonal = "
        SELECT
            COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown Species') AS species_name,
            MONTH(a.admission_date) AS month_num,
            COUNT(*) AS total_count,
            COUNT(DISTINCT YEAR(a.admission_date)) AS active_years
        FROM rescue_admissions a
        INNER JOIN rescue_patients p
            ON p.patient_id = a.patient_id
        WHERE a.centre_id = :spch_centre_id
          AND COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown Species') IN (" . implode(', ', $spch_inParts) . ")
        GROUP BY
            COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown Species'),
            MONTH(a.admission_date)
        ORDER BY species_name, month_num
    ";

    $spch_stmt = $pdo->prepare($spch_sqlSeasonal);
    $spch_stmt->execute($spch_params);

    $spch_seasonalMap = [];
    foreach ($spch_seasonSpecies as $spch_speciesName) {
        $spch_seasonalMap[$spch_speciesName] = array_fill(1, 12, 0);
    }

    while ($spch_row = $spch_stmt->fetch(PDO::FETCH_ASSOC)) {
        $spch_speciesName = $spch_row['species_name'];
        $spch_monthNum    = (int)$spch_row['month_num'];
        $spch_totalCount  = (int)$spch_row['total_count'];
        $spch_activeYears = max(1, (int)$spch_row['active_years']);

        if (isset($spch_seasonalMap[$spch_speciesName]) && $spch_monthNum >= 1 && $spch_monthNum <= 12) {
            $spch_seasonalMap[$spch_speciesName][$spch_monthNum] = round($spch_totalCount / $spch_activeYears, 1);
        }
    }

    $spch_seasonColours = ['#2f80ed', '#ff7f50', '#0aeebd', '#20ee0d'];

    foreach ($spch_seasonSpecies as $spch_i => $spch_speciesName) {
        $spch_seasonalDatasets[] = [
            'label' => $spch_speciesName,
            'data' => array_values($spch_seasonalMap[$spch_speciesName]),
            'borderColor' => $spch_seasonColours[$spch_i % count($spch_seasonColours)],
            'backgroundColor' => $spch_seasonColours[$spch_i % count($spch_seasonColours)],
            'fill' => false,
            'tension' => 0.35,
            'borderWidth' => 3,
            'pointRadius' => 3,
            'pointHoverRadius' => 5
        ];
    }
}
// --------------------------------------------------
// Avg stay summary card: top 5 species vs overall avg
// --------------------------------------------------
$spch_avgStayCardRows = [];

$spch_sqlAvgStayOverallForCard = "
    SELECT ROUND(AVG(DATEDIFF(a.disposition_date, a.admission_date)), 1) AS overall_avg_days
    FROM rescue_admissions a
    WHERE a.centre_id = :spch_centre_id
      AND a.disposition_date IS NOT NULL
      AND a.disposition_date <> ''
      AND a.disposition_date >= a.admission_date
";
$spch_stmt = $pdo->prepare($spch_sqlAvgStayOverallForCard);
$spch_stmt->execute([':spch_centre_id' => $centre_id]);
$spch_avgStayOverallForCard = $spch_stmt->fetchColumn();
$spch_avgStayOverallForCard = ($spch_avgStayOverallForCard !== null) ? (float)$spch_avgStayOverallForCard : 0;

$spch_sqlAvgStayCard = "
    SELECT
        COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown Species') AS species_name,
        ROUND(AVG(DATEDIFF(a.disposition_date, a.admission_date)), 1) AS avg_days,
        COUNT(*) AS total_cases
    FROM rescue_admissions a
    INNER JOIN rescue_patients p
        ON p.patient_id = a.patient_id
    WHERE a.centre_id = :spch_centre_id
      AND a.disposition_date IS NOT NULL
      AND a.disposition_date <> ''
      AND a.disposition_date >= a.admission_date
    GROUP BY COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown Species')
    HAVING total_cases >= 1
    ORDER BY total_cases DESC, species_name ASC
    LIMIT 5
";
$spch_stmt = $pdo->prepare($spch_sqlAvgStayCard);
$spch_stmt->execute([':spch_centre_id' => $centre_id]);

while ($spch_row = $spch_stmt->fetch(PDO::FETCH_ASSOC)) {
    $spch_speciesName = $spch_row['species_name'];
    $spch_avgDays = (float)$spch_row['avg_days'];
    $spch_diffDays = round($spch_avgDays - $spch_avgStayOverallForCard, 1);

    $spch_avgStayCardRows[] = [
        'species_name' => $spch_speciesName,
        'avg_days'     => $spch_avgDays,
        'diff_days'    => $spch_diffDays
    ];
}
// --------------------------------------------------
// Outcomes by species
// --------------------------------------------------
$spch_sqlOutcomes = "
    SELECT
        spch_x.species_name,
        SUM(CASE WHEN spch_x.disposition_group = 'Released' THEN spch_x.total ELSE 0 END) AS released_total,
        SUM(CASE WHEN spch_x.disposition_group = 'Died' THEN spch_x.total ELSE 0 END) AS died_total,
        SUM(CASE WHEN spch_x.disposition_group = 'Euthanised' THEN spch_x.total ELSE 0 END) AS euth_total,
        SUM(CASE WHEN spch_x.disposition_group = 'Other' THEN spch_x.total ELSE 0 END) AS other_total,
        SUM(spch_x.total) AS grand_total
    FROM (
        SELECT
            COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown Species') AS species_name,
            CASE
                WHEN a.disposition IS NULL OR TRIM(a.disposition) = '' THEN 'Other'
                WHEN LOWER(a.disposition) LIKE '%release%' THEN 'Released'
                WHEN LOWER(a.disposition) LIKE '%euth%' THEN 'Euthanised'
                WHEN LOWER(a.disposition) LIKE '%died%' THEN 'Died'
                ELSE 'Other'
            END AS disposition_group,
            COUNT(*) AS total
        FROM rescue_admissions a
        INNER JOIN rescue_patients p
            ON p.patient_id = a.patient_id
        WHERE a.centre_id = :spch_centre_id
        GROUP BY
            COALESCE(NULLIF(TRIM(p.animal_species), ''), 'Unknown Species'),
            CASE
                WHEN a.disposition IS NULL OR TRIM(a.disposition) = '' THEN 'Other'
                WHEN LOWER(a.disposition) LIKE '%release%' THEN 'Released'
                WHEN LOWER(a.disposition) LIKE '%euth%' THEN 'Euthanised'
                WHEN LOWER(a.disposition) LIKE '%died%' THEN 'Died'
                ELSE 'Other'
            END
    ) spch_x
    GROUP BY spch_x.species_name
    ORDER BY grand_total DESC, spch_x.species_name ASC
    LIMIT 6
";
$spch_stmt = $pdo->prepare($spch_sqlOutcomes);
$spch_stmt->execute([':spch_centre_id' => $centre_id]);

while ($spch_row = $spch_stmt->fetch(PDO::FETCH_ASSOC)) {
    $spch_outcomeLabels[]   = $spch_row['species_name'];
    $spch_outcomeReleased[] = (int)$spch_row['released_total'];
    $spch_outcomeDied[]     = (int)$spch_row['died_total'];
    $spch_outcomeEuth[]     = (int)$spch_row['euth_total'];
    $spch_outcomeOther[]    = (int)$spch_row['other_total'];
}
?>

<br>
<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm320 96c0-26.9-16.5-49.9-40-59.3V88c0-13.3-10.7-24-24-24s-24 10.7-24 24V292.7c-23.5 9.5-40 32.5-40 59.3c0 35.3 28.7 64 64 64s64-28.7 64-64zM144 176a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm-16 80a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64zM400 144a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>
        </div>
        <div class="txt">
            <h2><?php echo ($lang['SPECIES'] ?? 'Species') . ' ' . ($lang['INSIGHTS'] ?? 'Insights'); ?></h2>
            <p><?php echo $lang['DASH_SPECIES_INSIGHTS_SUB']; ?></p>
                    
        </div>
    </div>
</div>


<div class="rc-stat-grid">
    <div class="rc-stat">
        <strong><?php echo number_format($spch_totalAdmissionsAllTime); ?></strong>
        <span><?php echo ($lang['ADMISSIONS'] ?? 'Admissions') . ' ' . ($lang['ALL_TIME'] ?? 'All Time'); ?></span>
    </div>

    <div class="rc-stat">
        <strong><?php echo number_format($spch_totalSpeciesAllTime); ?></strong>
        <span><?php echo ($lang['SPECIES'] ?? 'Species') . ' ' . ($lang['ALL_TIME'] ?? 'All Time'); ?></span>
    </div>

    <div class="rc-stat">
        <strong><?php echo htmlspecialchars($spch_mostCommonSpecies); ?></strong>
        <span><?php echo ($lang['MOST_COMMON'] ?? 'Most Common') . ' ' . ($lang['SPECIES'] ?? 'Species'); ?> · <?php echo number_format($spch_mostCommonCount); ?> <?php echo strtolower($lang['ADMISSIONS']); ?></span>
    </div>

    <div class="rc-stat">
        <strong><?php echo number_format($spch_avgStayOverall, 1); ?></strong>
        <span><?php echo $lang['DASH_AVG_STAY_OVERALL']; ?> · <?php echo $lang['DASH_DAYS_ADMISSION_TO_DISPOSITION']; ?></span>
    </div>
</div>

<div class="content-block-wrapper" style="display:flex; gap:16px; align-items:stretch; flex-wrap:nowrap; margin-bottom:16px;">

    <div class="content-block" style="flex:1; min-width:0;">
        <div class="block-header">
            <div class="content-left">
                <span class="icon"></span>
                <?php echo ($lang['TOP'] ?? 'Top') . ' ' . ($lang['SPECIES'] ?? 'Species') . ' ' . ($lang['ALL_TIME'] ?? 'All Time'); ?>
            </div>
        </div>
        <div style="height:340px; position:relative;">
            <canvas id="spch_topSpeciesAllTimeChart"></canvas>
        </div>
    </div>

    <div class="content-block" style="flex:1; min-width:0;">
        <div class="block-header">
            <div class="content-left">
                <span class="icon"></span>
                <?php echo ($lang['SPECIES'] ?? 'Species') . ' ' . ($lang['MIX'] ?? 'Mix'); ?>
            </div>
        </div>
        <div style="height:260px; position:relative;">
            <canvas id="spch_speciesPieChart"></canvas>
        </div>
    </div>

    <div class="content-block" style="flex:1; min-width:0;">
    <div class="block-header">
        <div class="content-left">
            <span class="icon"></span>
            <?php echo ($lang['AVERAGE'] ?? 'Average') . ' ' . ($lang['STAY'] ?? 'Stay') . ' / ' . ($lang['SPECIES'] ?? 'Species'); ?>
        </div>
    </div>

    <div class="rc-comparison-body">
        <div class="rc-comparison-note">
            <?php echo $lang['DASH_TOP_5_OVERALL_AVG']; ?>
            <strong><?php echo number_format($spch_avgStayOverallForCard, 1); ?> <?php echo $lang['DAYS']; ?></strong>
        </div>

        <?php if (!empty($spch_avgStayCardRows)): ?>
            <?php foreach ($spch_avgStayCardRows as $spch_item): ?>
                <?php
                    $spch_diff = (float)$spch_item['diff_days'];
                    $spch_isHigher = $spch_diff > 0;
                    $spch_isLower  = $spch_diff < 0;

                    $spch_triangle = 'flat';
                    $spch_diffClass = 'trend-flat';

                    if ($spch_isHigher) {
                        $spch_triangle = 'up';
                        $spch_diffClass = 'trend-down';
                    } elseif ($spch_isLower) {
                        $spch_triangle = 'down';
                        $spch_diffClass = 'trend-up';
                    }

                    $spch_diffText = number_format(abs($spch_diff), 1);
                ?>
                <div class="rc-comparison-row">
                    <div class="rc-comparison-main">
                        <div class="rc-comparison-name">
                            <?php echo htmlspecialchars($spch_item['species_name']); ?>
                        </div>
                        <div class="rc-comparison-meta">
                            <?php echo number_format($spch_item['avg_days'], 1); ?> <?php echo $lang['DAYS']; ?>
                        </div>
                    </div>

                    <div class="rc-comparison-delta trend-chip <?php echo $spch_diffClass; ?>">
                        <?php if ($spch_diff == 0.0): ?>
                            <?php echo $lang['AVERAGE']; ?>
                        <?php else: ?>
                            <span class="triangle <?php echo $spch_triangle; ?>"></span>
                            <?php echo $spch_diffText; ?> <?php echo $lang['DAYS']; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="rc-comparison-empty"><?php echo $lang['DASH_NO_STAY_DATA']; ?></div>
        <?php endif; ?>
    </div>
</div>

</div>

<div class="content-block-wrapper" style="display:flex; gap:16px; align-items:stretch; flex-wrap:nowrap; margin-bottom:16px;">
<div class="content-block" style="margin-top:16px;">
    <div class="block-header">
        <div class="content-left">
            <span class="icon"></span>
            <?php echo ($lang['SEASONAL'] ?? 'Seasonal') . ' ' . ($lang['SPECIES'] ?? 'Species'); ?>
        </div>
    </div>
    <div style="height:340px; position:relative;">
        <canvas id="spch_seasonalSpeciesChart"></canvas>
    </div>
</div>

<div class="content-block" style="margin-top:16px;">
    <div class="block-header">
        <div class="content-left">
            <span class="icon"></span>
            <?php echo ($lang['OUTCOMES'] ?? 'Outcomes') . ' / ' . ($lang['SPECIES'] ?? 'Species'); ?>
        </div>
    </div>
    <div style="height:360px; position:relative;">
        <canvas id="spch_speciesOutcomeChart"></canvas>
    </div>
</div>

</div>
<script>
(function () {
    const spch_isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const spch_textColor = spch_isDark ? '#eaf6f4' : '#162334';
    const spch_mutedColor = spch_isDark ? '#9db0b5' : '#607086';
    const spch_gridColor = spch_isDark ? 'rgba(148, 163, 184, .18)' : 'rgba(226, 232, 240, .9)';

    if (window.Chart && Chart.defaults) {
        Chart.defaults.color = spch_textColor;
        if (Chart.defaults.global) {
            Chart.defaults.global.defaultFontColor = spch_textColor;
        }
        if (Chart.defaults.plugins && Chart.defaults.plugins.legend && Chart.defaults.plugins.legend.labels) {
            Chart.defaults.plugins.legend.labels.color = spch_textColor;
        }
    }

    const spch_topSpeciesLabels = <?php echo json_encode($spch_topSpeciesLabels); ?>;
    const spch_topSpeciesCounts = <?php echo json_encode($spch_topSpeciesCounts); ?>;

    const spch_stayLabels = <?php echo json_encode($spch_stayLabels); ?>;
    const spch_stayDays = <?php echo json_encode($spch_stayDays); ?>;

    const spch_pieLabels = <?php echo json_encode($spch_pieLabels); ?>;
    const spch_pieCounts = <?php echo json_encode($spch_pieCounts); ?>;

    const spch_monthLabels = <?php echo json_encode($spch_monthLabels); ?>;
    const spch_seasonalDatasets = <?php echo json_encode($spch_seasonalDatasets); ?>;

    const spch_outcomeLabels = <?php echo json_encode($spch_outcomeLabels); ?>;
    const spch_outcomeReleased = <?php echo json_encode($spch_outcomeReleased); ?>;
    const spch_outcomeDied = <?php echo json_encode($spch_outcomeDied); ?>;
    const spch_outcomeEuth = <?php echo json_encode($spch_outcomeEuth); ?>;
    const spch_outcomeOther = <?php echo json_encode($spch_outcomeOther); ?>;

    const spch_topSpeciesCanvas = document.getElementById('spch_topSpeciesAllTimeChart');
    if (spch_topSpeciesCanvas && spch_topSpeciesLabels.length) {
        new Chart(spch_topSpeciesCanvas, {
            type: 'bar',
            data: {
                labels: spch_topSpeciesLabels,
                datasets: [{
                    label: <?php echo json_encode($lang['ADMISSIONS']); ?>,
                    data: spch_topSpeciesCounts,
                    backgroundColor: [
                        'rgba(47, 128, 237, 0.90)',
                        'rgba(255, 127, 80, 0.90)',
                        'rgba(10, 238, 189, 0.90)',
                        'rgba(32, 238, 13, 0.90)',
                        'rgba(156, 157, 160, 0.90)',
                        'rgba(111, 66, 193, 0.90)',
                        'rgba(220, 53, 69, 0.90)',
                        'rgba(255, 193, 7, 0.90)'
                    ],
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        title: {
                            display: true,
                            text: <?php echo json_encode($lang['ADMISSIONS']); ?>
                        }
                    },
                    y: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    const spch_avgStayCanvas = document.getElementById('spch_avgStaySpeciesChart');
    if (spch_avgStayCanvas && spch_stayLabels.length) {
        new Chart(spch_avgStayCanvas, {
            type: 'bar',
            data: {
                labels: spch_stayLabels,
                datasets: [{
                    label: <?php echo json_encode(($lang['AVERAGE'] ?? 'Average') . ' ' . ($lang['DAYS'] ?? 'days')); ?>,
                    data: spch_stayDays,
                    backgroundColor: 'rgba(111, 66, 193, 0.85)',
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: <?php echo json_encode(($lang['AVERAGE'] ?? 'Average') . ' ' . ($lang['DAYS'] ?? 'days') . ' ' . ($lang['IN'] ?? 'in') . ' ' . strtolower($lang['CARE'] ?? 'care')); ?>
                        }
                    },
                    y: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    const spch_pieCanvas = document.getElementById('spch_speciesPieChart');
    if (spch_pieCanvas && spch_pieLabels.length) {
        new Chart(spch_pieCanvas, {
            type: 'pie',
            data: {
                labels: spch_pieLabels,
                datasets: [{
                    data: spch_pieCounts,
                    backgroundColor: [
                        'rgba(47, 128, 237, 0.90)',
                        'rgba(255, 127, 80, 0.90)',
                        'rgba(10, 238, 189, 0.90)',
                        'rgba(32, 238, 13, 0.90)',
                        'rgba(156, 157, 160, 0.90)',
                        'rgba(111, 66, 193, 0.90)',
                        'rgba(220, 53, 69, 0.90)',
                        'rgba(255, 193, 7, 0.90)',
                        'rgba(23, 162, 184, 0.90)',
                        'rgba(108, 117, 125, 0.90)',
                        'rgba(52, 58, 64, 0.90)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 12
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const spch_total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const spch_value = context.raw || 0;
                                const spch_pct = spch_total ? ((spch_value / spch_total) * 100).toFixed(1) : '0.0';
                                return context.label + ': ' + spch_value + ' (' + spch_pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    const spch_seasonalCanvas = document.getElementById('spch_seasonalSpeciesChart');
    if (spch_seasonalCanvas && spch_seasonalDatasets.length) {
        new Chart(spch_seasonalCanvas, {
            type: 'line',
            data: {
                labels: spch_monthLabels,
                datasets: spch_seasonalDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 12
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        title: {
                            display: true,
                            text: <?php echo json_encode(($lang['AVERAGE'] ?? 'Average') . ' ' . strtolower($lang['ADMISSIONS'] ?? 'admissions') . ' / ' . ($lang['MONTH'] ?? 'Month')); ?>
                        }
                    }
                }
            }
        });
    }

    const spch_outcomeCanvas = document.getElementById('spch_speciesOutcomeChart');
    if (spch_outcomeCanvas && spch_outcomeLabels.length) {
        new Chart(spch_outcomeCanvas, {
            type: 'bar',
            data: {
                labels: spch_outcomeLabels,
                datasets: [
                    {
                        label: <?php echo json_encode($lang['RELEASED']); ?>,
                        data: spch_outcomeReleased,
                        backgroundColor: 'rgba(32, 238, 13, 0.85)',
                        borderRadius: 4
                    },
                    {
                        label: <?php echo json_encode($lang['DECEASED']); ?>,
                        data: spch_outcomeDied,
                        backgroundColor: 'rgba(220, 53, 69, 0.85)',
                        borderRadius: 4
                    },
                    {
                        label: <?php echo json_encode($lang['EUTHANISED']); ?>,
                        data: spch_outcomeEuth,
                        backgroundColor: 'rgba(255, 193, 7, 0.85)',
                        borderRadius: 4
                    },
                    {
                        label: <?php echo json_encode($lang['OTHER']); ?>,
                        data: spch_outcomeOther,
                        backgroundColor: 'rgba(108, 117, 125, 0.80)',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 12
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: { display: false }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        title: {
                            display: true,
                            text: <?php echo json_encode($lang['CASES']); ?>
                        }
                    }
                }
            }
        });
    }
})();
</script>
