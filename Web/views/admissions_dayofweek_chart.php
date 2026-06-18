<?php include __DIR__ . '/../models/admissions_dayofweek_model.php'; ?>

<div class="chart-block">
    <canvas id="admissionsDayOfWeekChart"></canvas>
</div>

<script>
(function () {
    const canvas = document.getElementById('admissionsDayOfWeekChart');
    if (!canvas) return;

    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#eaf6f4' : '#162334';
    const mutedColor = isDark ? '#9db0b5' : '#607086';
    const gridColor = isDark ? 'rgba(148, 163, 184, .18)' : 'rgba(226, 232, 240, .9)';

    if (window.admissionsDayOfWeekChartInstance) {
        window.admissionsDayOfWeekChartInstance.destroy();
    }

    window.admissionsDayOfWeekChartInstance = new Chart(canvas, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($admissionsDayLabels); ?>,
            datasets: <?php echo json_encode($admissionsDayDatasets); ?>
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: <?php echo json_encode($lang['DASH_ADMISSIONS_DAY_WEEK_TITLE']); ?>,
                    color: textColor
                },
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: textColor
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: gridColor
                    },
                    ticks: {
                        precision: 0,
                        color: mutedColor
                    },
                    title: {
                        display: true,
                        text: <?php echo json_encode($lang['ADMISSIONS']); ?>,
                        color: textColor
                    }
                },
                x: {
                    ticks: {
                        color: mutedColor
                    },
                    grid: {
                        color: gridColor
                    },
                    title: {
                        display: true,
                        text: <?php echo json_encode(($lang['DAY'] ?? 'Day') . ' / ' . ($lang['WEEK'] ?? 'Week')); ?>,
                        color: textColor
                    }
                }
            }
        }
    });
})();
</script>

