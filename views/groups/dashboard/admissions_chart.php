<?php
// views/groups/dashboard/admissions_chart.php
// Expects:
// - $admissionsChartRows = [['label' => '2026-03-01', 'total' => 12], ...]
// - $rangeLabel

if (!isset($admissionsChartRows) || !is_array($admissionsChartRows)) {
    $admissionsChartRows = [];
}

$chartLabels = [];
$chartValues = [];

foreach ($admissionsChartRows as $row) {
    $chartLabels[] = (string)($row['label'] ?? '');
    $chartValues[] = (int)($row['total'] ?? 0);
}

$chartId = 'groupAdmissionsTrend_' . substr(md5(json_encode([$chartLabels, $chartValues, $rangeLabel ?? 'all'])), 0, 10);

$chartJsonLabels = json_encode($chartLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$chartJsonValues = json_encode($chartValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($chartJsonLabels === false) $chartJsonLabels = '[]';
if ($chartJsonValues === false) $chartJsonValues = '[]';

$totalPoints = count($chartValues);
$totalAdmissions = array_sum($chartValues);
$peakAdmissions = $totalPoints > 0 ? max($chartValues) : 0;
?>

<style>
.group-admissions-chart {
    margin-bottom: 14px;
}

.group-admissions-chart__card {
    border-radius: 18px;
    overflow: hidden;
}

.group-admissions-chart__head {
    padding: 16px;
    border-bottom: 1px solid var(--rc-border);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    flex-wrap: wrap;
}

.group-admissions-chart__title {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    line-height: 1.15;
    color: var(--rc-text);
}

.group-admissions-chart__subtitle {
    margin-top: 5px;
    font-size: 13px;
    color: var(--rc-muted);
}

.group-admissions-chart__meta {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.group-admissions-chart__body {
    padding: 16px;
}

.group-admissions-chart__canvas-wrap {
    position: relative;
    height: 360px;
}

.group-admissions-chart__empty {
    padding: 18px;
    border-radius: 14px;
    font-size: 14px;
    line-height: 1.5;
}

@media (max-width: 640px) {
    .group-admissions-chart__card {
        border-radius: 14px;
    }

    .group-admissions-chart__head,
    .group-admissions-chart__body {
        padding: 14px;
    }

    .group-admissions-chart__canvas-wrap {
        height: 300px;
    }
}
</style>

<div class="group-admissions-chart">
    <div class="rc-panel group-admissions-chart__card">
        <div class="group-admissions-chart__head">
            <div>
                <h3 class="group-admissions-chart__title">Admissions trend</h3>
                <div class="group-admissions-chart__subtitle">
                    Admissions over time across the network
                    • Range: <strong><?= htmlspecialchars((string)($rangeLabel ?? 'All time')) ?></strong>
                </div>
            </div>

            <div class="rc-chip-row group-admissions-chart__meta">
                <div class="rc-chip blue">
                    Total admissions: <strong><?= number_format($totalAdmissions) ?></strong>
                </div>
                <div class="rc-chip blue">
                    Peak period: <strong><?= number_format($peakAdmissions) ?></strong>
                </div>
                <div class="rc-chip">
                    Points: <strong><?= number_format($totalPoints) ?></strong>
                </div>
            </div>
        </div>

        <div class="group-admissions-chart__body">
            <?php if (empty($chartLabels) || empty($chartValues)): ?>
                <div class="rc-alert grey group-admissions-chart__empty">
                    No admissions trend data is available for the selected range.
                </div>
            <?php else: ?>
                <div class="group-admissions-chart__canvas-wrap">
                    <canvas id="<?= htmlspecialchars($chartId) ?>"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($chartLabels) && !empty($chartValues)): ?>
<script>
(function () {
    var canvasId = <?= json_encode($chartId) ?>;
    var labels = <?= $chartJsonLabels ?>;
    var values = <?= $chartJsonValues ?>;

    if (typeof Chart === 'undefined') {
        var el = document.getElementById(canvasId);
        if (el && el.parentNode) {
            el.parentNode.innerHTML = '<div style="padding:18px; color:#64748b; font-size:14px;">Chart.js is not loaded, so the admissions chart cannot be displayed.</div>';
        }
        return;
    }

    var canvas = document.getElementById(canvasId);
    if (!canvas) return;

    var ctx = canvas.getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Admissions',
                data: values,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.10)',
                borderWidth: 3,
                pointRadius: 3,
                pointHoverRadius: 5,
                pointBackgroundColor: '#2563eb',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                tension: 0.28,
                fill: true
            }]
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
                    display: false
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                        label: function (context) {
                            var value = context.parsed.y || 0;
                            return 'Admissions: ' + value;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#64748b',
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 10
                    },
                    border: {
                        color: '#e2e8f0'
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#64748b',
                        precision: 0
                    },
                    grid: {
                        color: '#eef2f7'
                    },
                    border: {
                        color: '#e2e8f0'
                    }
                }
            }
        }
    });
})();
</script>
<?php endif; ?>
