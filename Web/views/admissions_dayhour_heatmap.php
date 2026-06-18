<?php include __DIR__ . '/../models/admissions_dayhour_model.php'; ?>

<div class="rc-heatmap-card">
    <h3 class="rc-heatmap-title"><?php echo htmlspecialchars($lang['DASH_ADMISSIONS_DAY_TIME_TITLE']); ?></h3>

    <div class="rc-heatmap-wrap">
        <div class="rc-heatmap-grid">
            <div class="heatmap-corner"></div>

            <?php foreach ($admissionsHeatmapHours as $hourLabel): ?>
                <div class="heatmap-hour"><?php echo htmlspecialchars($hourLabel); ?></div>
            <?php endforeach; ?>

            <?php foreach ($admissionsHeatmapDays as $dayIndex => $dayLabel): ?>
                <div class="heatmap-day"><?php echo htmlspecialchars($dayLabel); ?></div>

                <?php foreach ($admissionsHeatmapData[$dayIndex] as $hourIndex => $count): ?>
                    <?php
                    if ($admissionsHeatmapMax > 0 && $count > 0) {
                        $ratio = $count / $admissionsHeatmapMax;
                        $alpha = 0.14 + ($ratio * 0.86);
                    } else {
                        $alpha = 0.06;
                    }

                    $textClass = ($alpha >= 0.55) ? 'is-dark' : 'is-light';
                    $title = $dayLabel . ' ' . str_pad((string)$hourIndex, 2, '0', STR_PAD_LEFT) . ':00 - ' . $count . ' ' . $lang['ADMISSIONS'];
                    ?>
                    <div
                        class="heatmap-cell <?php echo $textClass; ?>"
                        title="<?php echo htmlspecialchars($title); ?>"
                        style="background-color: rgba(60, 186, 159, <?php echo number_format($alpha, 2, '.', ''); ?>);"
                    >
                        <?php echo (int) $count; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="heatmap-legend">
        <span><?php echo htmlspecialchars($lang['LOWER']); ?></span>
        <div class="heatmap-legend-bar"></div>
        <span><?php echo htmlspecialchars($lang['HIGHER']); ?></span>
    </div>
</div>

