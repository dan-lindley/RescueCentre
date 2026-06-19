<?php
// views/feed_chart.php
// Feeding chart (Liquid ml/day, Solid g/day, Solid units/day) + daily aggregates (last 5 days) + kcal chart
// - Continuous dates (zero-filled)
// - Enriched tooltips
// - Auto-hide datasets that are all zeros (for all 3 intake lines + kcal chart)
// - No global function declarations (avoids redeclare fatal in multi-tab includes)

if (!isset($patient_id) || (int)$patient_id <= 0) {
    echo '<div class="alert-box alert-red" style="margin-bottom: 12px;"><strong>Feeding</strong><br>Patient context not available.</div>';
    return;
}

$days = 30;
$tableDays = 5;

$end = new DateTime('today');
$start = (clone $end)->modify('-' . ($days - 1) . ' days');

$startStr = $start->format('Y-m-d');
$endStr   = $end->format('Y-m-d');

$stmt = $pdo->prepare("
    SELECT
        DATE(fe.feed_at) AS day,

        SUM(
            CASE
                WHEN fe.feed_type = 'liquid' AND fe.status <> 'skipped'
                THEN COALESCE(fe.consumed_value, 0)
                ELSE 0
            END
        ) AS liquid_ml,

        SUM(
            CASE
                WHEN fe.feed_type = 'solid' AND fe.status <> 'skipped' AND fe.consumed_unit = 'g'
                THEN COALESCE(fe.consumed_value, 0)
                ELSE 0
            END
        ) AS solid_g,

        SUM(
            CASE
                WHEN fe.feed_type = 'solid' AND fe.status <> 'skipped' AND fe.consumed_unit = 'unit'
                THEN COALESCE(fe.consumed_value, 0)
                ELSE 0
            END
        ) AS solid_units,

        SUM(
            CASE
                WHEN fe.status = 'skipped' THEN 0

                WHEN fe.feed_type = 'liquid'
                 AND di.kcal_per_ml IS NOT NULL
                THEN COALESCE(fe.consumed_value, 0) * di.kcal_per_ml

                WHEN fe.feed_type = 'solid'
                 AND fe.consumed_unit = 'g'
                 AND di.kcal_per_g IS NOT NULL
                THEN COALESCE(fe.consumed_value, 0) * di.kcal_per_g

                WHEN fe.feed_type = 'solid'
                 AND fe.consumed_unit = 'unit'
                 AND di.grams_per_unit IS NOT NULL
                 AND di.kcal_per_g IS NOT NULL
                THEN COALESCE(fe.consumed_value, 0) * di.grams_per_unit * di.kcal_per_g

                ELSE 0
            END
        ) AS kcal_total,

        SUM(CASE WHEN fe.status <> 'skipped' THEN 1 ELSE 0 END) AS feed_count,
        SUM(CASE WHEN fe.status = 'refused' THEN 1 ELSE 0 END) AS refused_count,
        SUM(CASE WHEN fe.status = 'skipped' THEN 1 ELSE 0 END) AS skipped_count,
        SUM(CASE WHEN fe.is_estimated = 1 THEN 1 ELSE 0 END) AS estimated_count

    FROM rescue_feeding_events fe
    LEFT JOIN rescue_diet_items di ON di.diet_item_id = fe.diet_item_id
    WHERE fe.patient_id = :patient_id
      AND DATE(fe.feed_at) BETWEEN :start_day AND :end_day
    GROUP BY DATE(fe.feed_at)
    ORDER BY day ASC
");
$stmt->execute([
    ':patient_id' => (int)$patient_id,
    ':start_day'  => $startStr,
    ':end_day'    => $endStr
]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$byDay = [];
foreach ($rows as $r) {
    $byDay[$r['day']] = $r;
}

$fmt_num = function($n, $dp = 2) {
    $n = (float)$n;
    $s = number_format($n, $dp, '.', '');
    return rtrim(rtrim($s, '0'), '.');
};

$day_label = function($ymd) {
    $ts = strtotime($ymd);
    $day = (int)date('j', $ts);
    $suffix = ($day % 100 >= 11 && $day % 100 <= 13) ? 'th' : ([1=>'st',2=>'nd',3=>'rd'][$day % 10] ?? 'th');
    return $day . $suffix . ' ' . date('M y', $ts);
};

$trend_arrow = function($current, $previous) {
    if ($previous === null) return '';
    $c = (float)$current;
    $p = (float)$previous;
    $eps = 0.00001;
    if ($c > $p + $eps) return '▲';
    if ($c < $p - $eps) return '▼';
    return '→';
};

$trend_state = function($current, $previous) {
    if ($previous === null) return '';
    $c = (float)$current;
    $p = (float)$previous;
    $eps = 0.00001;
    if ($c > $p + $eps) return 'up';
    if ($c < $p - $eps) return 'down';
    return 'flat';
};

// Continuous series
$daysSeries = [];
$cursor = clone $start;
while ($cursor <= $end) {
    $daysSeries[] = $cursor->format('Y-m-d');
    $cursor->modify('+1 day');
}

$labels = [];
$liquid = [];
$solidg = [];
$solidu = [];
$kcal   = [];
$feedsCount = [];
$refusedCount = [];
$skippedCount = [];
$estimatedCount = [];

foreach ($daysSeries as $ymd) {
    $labels[] = $day_label($ymd);
    $r = $byDay[$ymd] ?? null;

    $liquid[] = $r ? (float)$r['liquid_ml'] : 0.0;
    $solidg[] = $r ? (float)$r['solid_g'] : 0.0;
    $solidu[] = $r ? (float)$r['solid_units'] : 0.0;
    $kcal[]   = $r ? (float)$r['kcal_total'] : 0.0;

    $feedsCount[]     = $r ? (int)$r['feed_count'] : 0;
    $refusedCount[]   = $r ? (int)$r['refused_count'] : 0;
    $skippedCount[]   = $r ? (int)$r['skipped_count'] : 0;
    $estimatedCount[] = $r ? (int)$r['estimated_count'] : 0;
}

$hasAny = false;
for ($i=0; $i<count($daysSeries); $i++) {
    if ($liquid[$i] > 0 || $solidg[$i] > 0 || $solidu[$i] > 0 || $kcal[$i] > 0 || $feedsCount[$i] > 0 || $skippedCount[$i] > 0) {
        $hasAny = true;
        break;
    }
}
?>

<?php if (!$hasAny): ?>

    <div class="alert-box alert-brown" style="margin-bottom:12px;">
        <strong>Feeding</strong><br>
        No feeding records available to chart.
    </div>

<?php else: ?>

        <strong>Feeding Trend (last <?= (int)$days ?> days)</strong><br>
        Shows solid intake, liquid intake and kcal/day. Lines with no data are hidden automatically.
  

    <canvas id="feedchart" style="margin-bottom:14px;"></canvas>

    <script>
        const feedLabels = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;

        const liquidTotals = <?= json_encode($liquid, JSON_UNESCAPED_UNICODE) ?>;
        const solidTotalsG = <?= json_encode($solidg, JSON_UNESCAPED_UNICODE) ?>;
        const solidTotalsU = <?= json_encode($solidu, JSON_UNESCAPED_UNICODE) ?>;
        const kcalTotals   = <?= json_encode($kcal, JSON_UNESCAPED_UNICODE) ?>;

        const feedCounts      = <?= json_encode($feedsCount, JSON_UNESCAPED_UNICODE) ?>;
        const refusedCounts   = <?= json_encode($refusedCount, JSON_UNESCAPED_UNICODE) ?>;
        const skippedCounts   = <?= json_encode($skippedCount, JSON_UNESCAPED_UNICODE) ?>;
        const estimatedCounts = <?= json_encode($estimatedCount, JSON_UNESCAPED_UNICODE) ?>;

        function hasNonZero(arr) {
            for (let i = 0; i < arr.length; i++) {
                if (Number(arr[i] || 0) !== 0) return true;
            }
            return false;
        }

        const showLiquid = hasNonZero(liquidTotals);
        const showSolidG = hasNonZero(solidTotalsG);
        const showSolidU = hasNonZero(solidTotalsU);
        const showKcal = hasNonZero(kcalTotals);

        const datasets = [];
        if (showSolidG) {
            datasets.push({
                label: "Solid (g/day)",
                data: solidTotalsG,
                yAxisID: "amountAxis",
                borderColor: "#8b5a2b",
                borderWidth: 2,
                fill: false,
                tension: 0.3,
                pointRadius: 2
            });
        }
        if (showSolidU) {
            datasets.push({
                label: "Solid (units/day)",
                data: solidTotalsU,
                yAxisID: "amountAxis",
                borderColor: "#b7791f",
                borderDash: [5, 4],
                borderWidth: 2,
                fill: false,
                tension: 0.3,
                pointRadius: 2
            });
        }
        if (showLiquid) {
            datasets.push({
                label: "Liquid (ml/day)",
                data: liquidTotals,
                yAxisID: "amountAxis",
                borderColor: "#007bff",
                borderWidth: 2,
                fill: false,
                tension: 0.3,
                pointRadius: 2
            });
        }
        if (showKcal) {
            datasets.push({
                label: "kcal/day",
                data: kcalTotals,
                yAxisID: "kcalAxis",
                borderColor: "#16a34a",
                borderWidth: 2,
                fill: false,
                tension: 0.3,
                pointRadius: 2
            });
        }

        new Chart(document.getElementById("feedchart"), {
            type: 'line',
            data: { labels: feedLabels, datasets },
            options: {
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: true },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function(ctx) {
                                const v = (ctx.parsed.y ?? 0);
                                const label = ctx.dataset.label ? ctx.dataset.label + ": " : "";
                                if (ctx.dataset.label.includes("Liquid")) return label + v + " ml";
                                if (ctx.dataset.label.includes("Solid (g")) return label + v + " g";
                                if (ctx.dataset.label.includes("units")) return label + v + " units";
                                if (ctx.dataset.label.includes("kcal")) return label + v + " kcal";
                                return label + v;
                            },
                            afterBody: function(contexts) {
                                if (!contexts || !contexts.length) return [];
                                const idx = contexts[0].dataIndex;

                                const lines = [];
                                lines.push("kcal: " + (kcalTotals[idx] ?? 0) + " kcal");
                                lines.push("feeds: " + (feedCounts[idx] ?? 0));
                                lines.push("refused: " + (refusedCounts[idx] ?? 0));
                                lines.push("skipped: " + (skippedCounts[idx] ?? 0));
                                lines.push("estimated: " + (estimatedCounts[idx] ?? 0));

                                // Add explicit note if some series hidden (still useful for clinicians)
                                if (!showLiquid && (liquidTotals[idx] ?? 0) !== 0) lines.push("Liquid hidden");
                                if (!showSolidG && (solidTotalsG[idx] ?? 0) !== 0) lines.push("Solid g hidden");
                                if (!showSolidU && (solidTotalsU[idx] ?? 0) !== 0) lines.push("Solid units hidden");
                                if (!showKcal && (kcalTotals[idx] ?? 0) !== 0) lines.push("kcal hidden");

                                return lines;
                            }
                        }
                    }
                },
                scales: {
                    amountAxis: {
                        type: 'linear',
                        position: 'left',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Feed amount'
                        }
                    },
                    kcalAxis: {
                        type: 'linear',
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'kcal'
                        }
                    }
                }
            }
        });
    </script>

    <!-- ==========================================================
         DAILY AGGREGATES TABLE (last 5 days)
    ========================================================== -->
    <div class="alert-box alert-grey"
         style="margin-top: 10px; margin-bottom: 6px; padding: 6px 12px; font-size: 0.75rem; opacity: 0.9;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th align="left" style="width:90px;">Date</th>
                    <th align="center" style="width:110px;">Liquid (ml)</th>
                    <th align="center" style="width:110px;">Solid (g)</th>
                    <th align="center" style="width:110px;">Solid (units)</th>
                    <th align="center" style="width:110px;">kcal</th>
                </tr>
            </thead>
        </table>
    </div>

    <?php
    $seriesCount = count($daysSeries);
    $tableStartIndex = max(0, $seriesCount - $tableDays);

    $prevLiquid = null;
    $prevSolidG = null;
    $prevSolidU = null;
    $prevKcal   = null;

    for ($i = $tableStartIndex; $i < $seriesCount; $i++):
        $ymd = $daysSeries[$i];

        $liq = $liquid[$i];
        $sg  = $solidg[$i];
        $su  = $solidu[$i];
        $kc  = $kcal[$i];

        $liqTrend = $trend_state($liq, $prevLiquid);
        $sgTrend  = $trend_state($sg,  $prevSolidG);
        $suTrend  = $trend_state($su,  $prevSolidU);
        $kcTrend  = $trend_state($kc,  $prevKcal);

        $prevLiquid = $liq;
        $prevSolidG = $sg;
        $prevSolidU = $su;
        $prevKcal   = $kc;
    ?>
        <div class="alert-box alert-brown" style="margin-bottom: 6px; padding: 8px 12px;">
            <table style="width:100%; border-collapse:collapse;">
                <tbody>
                    <tr>
                        <td style="width:90px; white-space:nowrap;">
                            <?= htmlspecialchars(date('d/m/y', strtotime($ymd))) ?>
                        </td>

                        <td style="width:110px; text-align:center; white-space:nowrap;">
                            <strong><?= htmlspecialchars($fmt_num($liq)) ?></strong>
                            <?php if ($liqTrend !== ''): ?><span class="trend-chip trend-<?= htmlspecialchars($liqTrend) ?>"><span class="triangle <?= htmlspecialchars($liqTrend) ?>"></span></span><?php endif; ?>
                        </td>

                        <td style="width:110px; text-align:center; white-space:nowrap;">
                            <strong><?= htmlspecialchars($fmt_num($sg)) ?></strong>
                            <?php if ($sgTrend !== ''): ?><span class="trend-chip trend-<?= htmlspecialchars($sgTrend) ?>"><span class="triangle <?= htmlspecialchars($sgTrend) ?>"></span></span><?php endif; ?>
                        </td>

                        <td style="width:110px; text-align:center; white-space:nowrap;">
                            <strong><?= htmlspecialchars($fmt_num($su)) ?></strong>
                            <?php if ($suTrend !== ''): ?><span class="trend-chip trend-<?= htmlspecialchars($suTrend) ?>"><span class="triangle <?= htmlspecialchars($suTrend) ?>"></span></span><?php endif; ?>
                        </td>

                        <td style="width:110px; text-align:center; white-space:nowrap;">
                            <strong><?= htmlspecialchars($fmt_num($kc)) ?></strong>
                            <?php if ($kcTrend !== ''): ?><span class="trend-chip trend-<?= htmlspecialchars($kcTrend) ?>"><span class="triangle <?= htmlspecialchars($kcTrend) ?>"></span></span><?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endfor; ?>

<?php endif; ?>
