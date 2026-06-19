<canvas id="admissionschart" class="chart-canvas-short"></canvas>

<script>
<?php
$endYear   = (int)date('Y');
$startYear = $endYear - 4;

$labels = [$lang['MONTH_SHORT_JAN'] ?? 'Jan',$lang['MONTH_SHORT_FEB'] ?? 'Feb',$lang['MONTH_SHORT_MAR'] ?? 'Mar',$lang['MONTH_SHORT_APR'] ?? 'Apr',$lang['MONTH_SHORT_MAY'] ?? 'May',$lang['MONTH_SHORT_JUN'] ?? 'Jun',$lang['MONTH_SHORT_JUL'] ?? 'Jul',$lang['MONTH_SHORT_AUG'] ?? 'Aug',$lang['MONTH_SHORT_SEP'] ?? 'Sept',$lang['MONTH_SHORT_OCT'] ?? 'Oct',$lang['MONTH_SHORT_NOV'] ?? 'Nov',$lang['MONTH_SHORT_DEC'] ?? 'Dec'];

// Base colours (newest at end)
$palette = ["#eeff00ff", "#20ee0dff", "#0aeebdff", "#ff7f50", "#2f80ed"];

// Helper to fade colours
function hexToRgba($hex, $alpha) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "rgba($r, $g, $b, $alpha)";
}

// Build empty structure
$dataByYearMonth = [];
for ($y = $startYear; $y <= $endYear; $y++) {
    $dataByYearMonth[$y] = array_fill(1, 12, 0);
}

// Query
$sql = "
SELECT
    YEAR(a.admission_date)  AS yr,
    MONTH(a.admission_date) AS mo,
    COUNT(a.admission_id)   AS cnt
FROM rescue_admissions a
WHERE a.centre_id = :centre_id
  AND YEAR(a.admission_date) BETWEEN :startYear AND :endYear
GROUP BY YEAR(a.admission_date), MONTH(a.admission_date)
ORDER BY YEAR(a.admission_date), MONTH(a.admission_date)
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':centre_id', $centre_id, PDO::PARAM_INT);
$stmt->bindValue(':startYear', $startYear, PDO::PARAM_INT);
$stmt->bindValue(':endYear', $endYear, PDO::PARAM_INT);
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $dataByYearMonth[(int)$row['yr']][(int)$row['mo']] = (int)$row['cnt'];
}

// Build datasets
$datasets = [];
$allYearsFlat = [];
$colorIdx = 0;

$currentYear  = (int)date('Y');
$currentMonth = (int)date('n');

foreach (range($startYear, $endYear) as $y) {

    $monthData = array_values($dataByYearMonth[$y]);

    if ($y === $currentYear) {
        for ($m = $currentMonth + 1; $m <= 12; $m++) {
            $monthData[$m - 1] = null;
        }
    }

    $allYearsFlat[$y] = $monthData;

    $baseColor = $palette[$colorIdx % count($palette)];

    // Fade logic
    if ($y <= $endYear - 2) {
        // 3+ years old → faded
        $borderColor = hexToRgba($baseColor, 0.6);
        $pointColor  = hexToRgba($baseColor, 0.6);
        $width = 2;
    } else {
        // last year + current → full strength
        $borderColor = $baseColor;
        $pointColor  = $baseColor;
        $width = ($y === $currentYear) ? 4 : 3;
    }

    $datasets[] = [
        'label' => (string)$y,
        'data' => $monthData,
        'borderColor' => $borderColor,
        'backgroundColor' => $pointColor,
        'fill' => false,
        'tension' => 0.35,
        'borderWidth' => $width,
        'pointRadius' => ($y === $currentYear) ? 4 : 2,
        'pointHoverRadius' => 5,
        'spanGaps' => false
    ];

    $colorIdx++;
}

// ---- Average line ----
$avgData = [];

for ($m = 0; $m < 12; $m++) {
    $vals = [];

    foreach (range($startYear, $endYear) as $y) {
        if ($allYearsFlat[$y][$m] !== null) {
            $vals[] = $allYearsFlat[$y][$m];
        }
    }

    $avgData[] = count($vals) ? round(array_sum($vals) / count($vals), 1) : null;
}

$datasets[] = [
    'label' => '5-' . ($lang['YEAR'] ?? 'Year') . ' ' . ($lang['AVERAGE'] ?? 'Average'),
    'data' => $avgData,
    'borderColor' => '#9c9da0ff',
    'backgroundColor' => '#9a9ca0ff',
    'borderDash' => [8,6],
    'borderWidth' => 3,
    'pointRadius' => 3,
    'pointHoverRadius' => 5,
    'fill' => false,
    'tension' => 0.35
];
?>

const admissionsChartIsDark = document.documentElement.getAttribute('data-theme') === 'dark';
const admissionsChartText = admissionsChartIsDark ? '#eaf6f4' : '#162334';
const admissionsChartMuted = admissionsChartIsDark ? '#9db0b5' : '#607086';
const admissionsChartGrid = admissionsChartIsDark ? 'rgba(148, 163, 184, .18)' : 'rgba(226, 232, 240, .9)';

new Chart(document.getElementById("admissionschart"), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: <?php echo json_encode($datasets); ?>
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            title: {
                display: true,
                color: admissionsChartText,
                font: { size: 18, weight: 'bold' }
            },
            legend: {
                position: 'bottom',
                labels: {
                    color: admissionsChartText,
                    usePointStyle: true,
                    padding: 14
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: admissionsChartMuted }
            },
            y: {
                beginAtZero: true,
                grid: { color: admissionsChartGrid },
                ticks: { precision: 0, color: admissionsChartMuted },
                title: {
                    display: true,
                    text: <?php echo json_encode($lang['ADMISSIONS']); ?>,
                    color: admissionsChartText
                }
            }
        }
    }
});
</script>

