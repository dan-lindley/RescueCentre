<?php include_once __DIR__ . '/../models/all_dataModel.php'; ?>




<?php
$admissionsDiffText = ($admissionsYtdDiff >= 0 ? '+' : '') . number_format((int)$admissionsYtdDiff);
$releasesDiffText   = ($releasesYtdDiff >= 0 ? '+' : '') . number_format((int)$releasesYtdDiff);
$deathsDiffText     = ($deathsYtdDiff >= 0 ? '+' : '') . number_format((int)$deathsYtdDiff);
$clinicalPctText    = ($clinicalEfficiencyChangePct >= 0 ? '+' : '') . number_format((float)$clinicalEfficiencyChangePct, 1) . '%';

$capacityBarPercent = max(0, min(100, (float)$capacityPercent));
$capacityBarClass = 'is-good';
$capacityStatusText = $lang['DASH_COMFORTABLE_CAPACITY'];
if ($capacityBarPercent >= 85) {
    $capacityBarClass = 'is-high';
    $capacityStatusText = $lang['DASH_HIGH_OCCUPANCY'];
} elseif ($capacityBarPercent >= 60) {
    $capacityBarClass = 'is-medium';
    $capacityStatusText = $lang['DASH_MODERATE_OCCUPANCY'];
}

$seasonalPressureLabel = [
    'season-peak' => $lang['PEAK'],
    'season-high' => $lang['HIGH'],
    'season-low' => $lang['LOW'],
    'season-normal' => $lang['NORMAL'],
][$seasonalPressureClass] ?? $lang['NORMAL'];
$seasonalPressureText = [
    'season-peak' => $lang['DASH_SEASON_WELL_ABOVE'],
    'season-high' => $lang['DASH_SEASON_ABOVE'],
    'season-low' => $lang['DASH_SEASON_BELOW'],
    'season-normal' => $lang['DASH_SEASON_CLOSE'],
][$seasonalPressureClass] ?? $lang['DASH_SEASON_CLOSE'];

$seasonMarkerPercent = 50;
if ($seasonalDifferencePct <= -30) {
    $seasonMarkerPercent = 10;
} elseif ($seasonalDifferencePct < -15) {
    $seasonMarkerPercent = 22;
} elseif ($seasonalDifferencePct <= 15) {
    $seasonMarkerPercent = 50;
} elseif ($seasonalDifferencePct <= 30) {
    $seasonMarkerPercent = 76;
} else {
    $seasonMarkerPercent = 92;
}

$ad1Arrow = ($admissionComparisons['1d']['pct'] > 0) ? 'up' : (($admissionComparisons['1d']['pct'] < 0) ? 'down' : 'flat');
$ad7Arrow = ($admissionComparisons['7d']['pct'] > 0) ? 'up' : (($admissionComparisons['7d']['pct'] < 0) ? 'down' : 'flat');
$ad31Arrow = ($admissionComparisons['31d']['pct'] > 0) ? 'up' : (($admissionComparisons['31d']['pct'] < 0) ? 'down' : 'flat');
$adytdArrow = ($admissionComparisons['ytd']['pct'] > 0) ? 'up' : (($admissionComparisons['ytd']['pct'] < 0) ? 'down' : 'flat');

$ad1Class = ($admissionComparisons['1d']['pct'] > 0) ? 'trend-up' : (($admissionComparisons['1d']['pct'] < 0) ? 'trend-down' : 'trend-flat');
$ad7Class = ($admissionComparisons['7d']['pct'] > 0) ? 'trend-up' : (($admissionComparisons['7d']['pct'] < 0) ? 'trend-down' : 'trend-flat');
$ad31Class = ($admissionComparisons['31d']['pct'] > 0) ? 'trend-up' : (($admissionComparisons['31d']['pct'] < 0) ? 'trend-down' : 'trend-flat');
$adytdClass = ($admissionComparisons['ytd']['pct'] > 0) ? 'trend-up' : (($admissionComparisons['ytd']['pct'] < 0) ? 'trend-down' : 'trend-flat');

$rel1Arrow = ($releaseComparisons['1d']['pct'] > 0) ? 'up' : (($releaseComparisons['1d']['pct'] < 0) ? 'down' : 'flat');
$rel7Arrow = ($releaseComparisons['7d']['pct'] > 0) ? 'up' : (($releaseComparisons['7d']['pct'] < 0) ? 'down' : 'flat');
$rel31Arrow = ($releaseComparisons['31d']['pct'] > 0) ? 'up' : (($releaseComparisons['31d']['pct'] < 0) ? 'down' : 'flat');
$relytdArrow = ($releaseComparisons['ytd']['pct'] > 0) ? 'up' : (($releaseComparisons['ytd']['pct'] < 0) ? 'down' : 'flat');

$rel1Class = ($releaseComparisons['1d']['pct'] > 0) ? 'trend-up' : (($releaseComparisons['1d']['pct'] < 0) ? 'trend-down' : 'trend-flat');
$rel7Class = ($releaseComparisons['7d']['pct'] > 0) ? 'trend-up' : (($releaseComparisons['7d']['pct'] < 0) ? 'trend-down' : 'trend-flat');
$rel31Class = ($releaseComparisons['31d']['pct'] > 0) ? 'trend-up' : (($releaseComparisons['31d']['pct'] < 0) ? 'trend-down' : 'trend-flat');
$relytdClass = ($releaseComparisons['ytd']['pct'] > 0) ? 'trend-up' : (($releaseComparisons['ytd']['pct'] < 0) ? 'trend-down' : 'trend-flat');

$death1Arrow = ($deathComparisons['1d']['pct'] > 0) ? 'up' : (($deathComparisons['1d']['pct'] < 0) ? 'down' : 'flat');
$death7Arrow = ($deathComparisons['7d']['pct'] > 0) ? 'up' : (($deathComparisons['7d']['pct'] < 0) ? 'down' : 'flat');
$death31Arrow = ($deathComparisons['31d']['pct'] > 0) ? 'up' : (($deathComparisons['31d']['pct'] < 0) ? 'down' : 'flat');
$deathytdArrow = ($deathComparisons['ytd']['pct'] > 0) ? 'up' : (($deathComparisons['ytd']['pct'] < 0) ? 'down' : 'flat');

$death1Class = ($deathComparisons['1d']['pct'] > 0) ? 'trend-down' : (($deathComparisons['1d']['pct'] < 0) ? 'trend-up' : 'trend-flat');
$death7Class = ($deathComparisons['7d']['pct'] > 0) ? 'trend-down' : (($deathComparisons['7d']['pct'] < 0) ? 'trend-up' : 'trend-flat');
$death31Class = ($deathComparisons['31d']['pct'] > 0) ? 'trend-down' : (($deathComparisons['31d']['pct'] < 0) ? 'trend-up' : 'trend-flat');
$deathytdClass = ($deathComparisons['ytd']['pct'] > 0) ? 'trend-down' : (($deathComparisons['ytd']['pct'] < 0) ? 'trend-up' : 'trend-flat');
?>

<div class="metric-board">

    <div class="metric-summary">
        <div class="metric-summary-head">
            <h3><?php echo $lang['DASH_ALL_TIME_SUMMARY']; ?></h3>
            <div class="metric-summary-note"><?php echo $lang['DASH_SINCE_RECORDS_BEGAN']; ?></div>
        </div>

        <div class="metric-summary-grid">
            <div class="metric-summary-item is-blue">
                <span class="label"><?php echo ($lang['TOTAL'] ?? 'Total') . ' ' . ($lang['ADMISSIONS'] ?? 'Admissions'); ?></span>
                <span class="value"><?php echo number_format((int)$disptotal); ?></span>
            </div>

            <div class="metric-summary-item is-green">
                <span class="label"><?php echo $lang['DASH_ANIMALS_RELEASED']; ?></span>
                <span class="value"><?php echo number_format((int)$dispreleased); ?></span>
            </div>

            <div class="metric-summary-item is-red">
                <span class="label"><?php echo $lang['DASH_ANIMALS_THAT_DIED']; ?></span>
                <span class="value"><?php echo number_format((int)$dispdiedtotal); ?></span>
            </div>

            <div class="metric-summary-item is-teal">
                <span class="label"><?php echo $lang['DASH_ANIMALS_IN_CARE']; ?></span>
                <span class="value"><?php echo number_format((int)$dispcaptive); ?></span>
            </div>

            <div class="metric-summary-item is-purple">
                <span class="label"><?php echo ($lang['CLINICAL'] ?? 'Clinical') . ' ' . ($lang['EFFICIENCY'] ?? 'Efficiency'); ?></span>
                <span class="value"><?php echo number_format((float)$clinefficiency, 1); ?>%</span>
            </div>
        </div>
    </div>

    <div class="metric-grid">

        <div class="metric-card card-amber">
            <div class="metric-card-head">
                <h4 class="metric-title"><?php echo $lang['DASH_CAPACITY_UTILISATION']; ?></h4>
                <div class="metric-icon">
                    <svg width="18" height="18" viewBox="0 0 640 512" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M32 192C32 85.96 117.96 0 224 0H416C522 0 608 85.96 608 192V320C608 355.3 579.3 384 544 384H512V448C512 483.3 483.3 512 448 512C412.7 512 384 483.3 384 448V384H256V448C256 483.3 227.3 512 192 512C156.7 512 128 483.3 128 448V384H96C60.65 384 32 355.3 32 320V192z"/></svg>
                </div>
            </div>

            <div class="metric-value">
                <?php echo number_format((int)$occupiedSpaces); ?> / <?php echo number_format((int)$totalCapacity); ?>
            </div>

            <div class="metric-footer">
                <div class="rc-meter-wrap">
                    <div class="rc-meter">
                        <div class="rc-meter-fill <?php echo $capacityBarClass; ?>" style="width:<?php echo $capacityBarPercent; ?>%;"></div>
                    </div>

                    <div class="rc-meter-meta">
                        <span><?php echo number_format((float)$capacityPercent, 1); ?>% <?php echo $lang['OCCUPIED']; ?></span>
                        <span class="rc-status-pill <?php echo $capacityBarClass; ?>">
                            <?php echo htmlspecialchars($capacityStatusText); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="metric-card card-blue">
            <div class="metric-card-head">
                <h4 class="metric-title"><?php echo ($lang['ADMISSIONS'] ?? 'Admissions') . ' ' . ($lang['THIS_YEAR'] ?? 'this year'); ?></h4>
                <div class="metric-icon">
                    <svg width="18" height="18" viewBox="0 0 640 512" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M96 128a128 128 0 1 1 256 0A128 128 0 1 1 96 128zM0 482.3C0 383.8 79.8 304 178.3 304h91.4C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3z"/></svg>
                </div>
            </div>
            <div class="metric-value-row">
                <div class="metric-value"><?php echo number_format((int)$ytddisptotal); ?></div>
                <div class="metric-inline-note">(<?php echo $admissionsDiffText; ?> <?php echo strtolower($lang['DASH_CHANGE_ON']); ?> <?php echo (int)$lastYear; ?>)</div>
            </div>
            <div class="metric-footer"><?php echo $lang['DASH_CHANGE_ON']; ?> <?php echo (int)$lastYear; ?></div>
            <div class="metric-mini-grid">
                <div class="metric-mini-cell"><span class="trend-chip <?php echo $ad1Class; ?>"><span class="triangle <?php echo $ad1Arrow; ?>"></span><?php echo number_format(abs($admissionComparisons['1d']['pct']), 0); ?>% 1d</span></div>
                <div class="metric-mini-cell"><span class="trend-chip <?php echo $ad7Class; ?>"><span class="triangle <?php echo $ad7Arrow; ?>"></span><?php echo number_format(abs($admissionComparisons['7d']['pct']), 0); ?>% 7d</span></div>
                <div class="metric-mini-cell"><span class="trend-chip <?php echo $ad31Class; ?>"><span class="triangle <?php echo $ad31Arrow; ?>"></span><?php echo number_format(abs($admissionComparisons['31d']['pct']), 0); ?>% 31d</span></div>
                <div class="metric-mini-cell"><span class="trend-chip <?php echo $adytdClass; ?>"><span class="triangle <?php echo $adytdArrow; ?>"></span><?php echo number_format(abs($admissionComparisons['ytd']['pct']), 0); ?>% YTD</span></div>
            </div>
        </div>

        <div class="metric-card card-green">
            <div class="metric-card-head">
                <h4 class="metric-title"><?php echo ($lang['RELEASES'] ?? 'Releases') . ' ' . ($lang['THIS_YEAR'] ?? 'this year'); ?></h4>
                <div class="metric-icon">
                    <svg width="18" height="18" viewBox="0 0 640 512" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M128 224c0-17.7 14.3-32 32-32H448c17.7 0 32 14.3 32 32s-14.3 32-32 32H160c-17.7 0-32-14.3-32-32zm352 96c0 17.7-14.3 32-32 32H160c-17.7 0-32-14.3-32-32s14.3-32 32-32H448c17.7 0 32 14.3 32 32z"/></svg>
                </div>
            </div>
            <div class="metric-value-row">
                <div class="metric-value"><?php echo number_format((int)$ytddispreleased); ?></div>
                <div class="metric-inline-note">(<?php echo $releasesDiffText; ?> <?php echo strtolower($lang['DASH_CHANGE_ON']); ?> <?php echo (int)$lastYear; ?>)</div>
            </div>
            <div class="metric-footer"><?php echo $lang['DASH_CHANGE_ON']; ?> <?php echo (int)$lastYear; ?></div>
            <div class="metric-mini-grid">
                <div class="metric-mini-cell"><span class="trend-chip <?php echo $rel1Class; ?>"><span class="triangle <?php echo $rel1Arrow; ?>"></span><?php echo number_format(abs($releaseComparisons['1d']['pct']), 0); ?>% 1d</span></div>
                <div class="metric-mini-cell"><span class="trend-chip <?php echo $rel7Class; ?>"><span class="triangle <?php echo $rel7Arrow; ?>"></span><?php echo number_format(abs($releaseComparisons['7d']['pct']), 0); ?>% 7d</span></div>
                <div class="metric-mini-cell"><span class="trend-chip <?php echo $rel31Class; ?>"><span class="triangle <?php echo $rel31Arrow; ?>"></span><?php echo number_format(abs($releaseComparisons['31d']['pct']), 0); ?>% 31d</span></div>
                <div class="metric-mini-cell"><span class="trend-chip <?php echo $relytdClass; ?>"><span class="triangle <?php echo $relytdArrow; ?>"></span><?php echo number_format(abs($releaseComparisons['ytd']['pct']), 0); ?>% YTD</span></div>
            </div>
        </div>

        <div class="metric-card card-red">
            <div class="metric-card-head">
                <h4 class="metric-title"><?php echo ($lang['DEATHS'] ?? 'Deaths') . ' ' . ($lang['THIS_YEAR'] ?? 'this year'); ?></h4>
                <div class="metric-icon">
                    <svg width="18" height="18" viewBox="0 0 640 512" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M320 32C196.3 32 96 132.3 96 256s100.3 224 224 224 224-100.3 224-224S443.7 32 320 32zM224 224H416v64H224V224z"/></svg>
                </div>
            </div>
            <div class="metric-value-row">
                <div class="metric-value"><?php echo number_format((int)$ytddispdiedtotal); ?></div>
                <div class="metric-inline-note">(<?php echo $deathsDiffText; ?> <?php echo strtolower($lang['DASH_CHANGE_ON']); ?> <?php echo (int)$lastYear; ?>)</div>
            </div>
            <div class="metric-footer"><?php echo $lang['DASH_CHANGE_ON']; ?> <?php echo (int)$lastYear; ?></div>
            <div class="metric-mini-grid">
                <div class="metric-mini-cell"><span class="trend-chip <?php echo $death1Class; ?>"><span class="triangle <?php echo $death1Arrow; ?>"></span><?php echo number_format(abs($deathComparisons['1d']['pct']), 0); ?>% 1d</span></div>
                <div class="metric-mini-cell"><span class="trend-chip <?php echo $death7Class; ?>"><span class="triangle <?php echo $death7Arrow; ?>"></span><?php echo number_format(abs($deathComparisons['7d']['pct']), 0); ?>% 7d</span></div>
                <div class="metric-mini-cell"><span class="trend-chip <?php echo $death31Class; ?>"><span class="triangle <?php echo $death31Arrow; ?>"></span><?php echo number_format(abs($deathComparisons['31d']['pct']), 0); ?>% 31d</span></div>
                <div class="metric-mini-cell"><span class="trend-chip <?php echo $deathytdClass; ?>"><span class="triangle <?php echo $deathytdArrow; ?>"></span><?php echo number_format(abs($deathComparisons['ytd']['pct']), 0); ?>% YTD</span></div>
            </div>
        </div>
    </div>

    <div class="metric-grid-secondary">

        <div class="metric-card card-purple">
            <div class="metric-card-head">
                <h4 class="metric-title"><?php echo ($lang['CLINICAL'] ?? 'Clinical') . ' ' . ($lang['EFFICIENCY'] ?? 'Efficiency'); ?></h4>
                <div class="metric-icon">
                    <svg width="18" height="18" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M256 48a208 208 0 1 0 208 208A208 208 0 0 0 256 48zm0 96a24 24 0 1 1-24 24 24 24 0 0 1 24-24zm40 232H216a24 24 0 0 1 0-48h16V224H216a24 24 0 0 1 0-48h40a24 24 0 0 1 24 24V328h16a24 24 0 0 1 0 48z"/></svg>
                </div>
            </div>

            <div class="metric-value-row">
                <div class="metric-value"><?php echo number_format((float)$ytdclinefficiency, 1); ?>%</div>
                <div class="metric-inline-note">(<?php echo $clinicalPctText; ?> <?php echo $lang['DASH_VS_LAST_YEAR_YTD']; ?>)</div>
            </div>

            <div class="metric-footer"><?php echo $lang['DASH_THREE_YEAR_TREND']; ?></div>

            <div class="metric-mini" style="display:flex; gap:10px; flex-wrap:wrap;">
                <?php
                $historyYears = array_keys($clinicalHistory);
                rsort($historyYears);
                $historyCount = count($historyYears);
                $historyIndex = 0;

                foreach ($historyYears as $histYear):
                    $historyIndex++;
                    $histEff = (float)$clinicalHistory[$histYear]['efficiency'];
                    $histChange = (float)$clinicalHistory[$histYear]['change_pct'];

                    $histArrow = ($histChange > 0) ? 'up' : (($histChange < 0) ? 'down' : 'flat');
                    $histClass = ($histChange > 0) ? 'trend-up' : (($histChange < 0) ? 'trend-down' : 'trend-flat');
                ?>
                    <span>
                        <strong style="color:#475569;"><?php echo (int)$histYear; ?></strong>
                        <?php echo number_format($histEff, 1); ?>%
                        <?php if ($histYear != $historyStartYear): ?>
                            <span class="trend-chip <?php echo $histClass; ?>" style="margin-left:4px;">
                                <span class="triangle <?php echo $histArrow; ?>"></span>
                                <?php echo number_format(abs($histChange), 1); ?>%
                            </span>
                        <?php endif; ?>
                    </span>
                    <?php if ($historyIndex < $historyCount): ?>
                        <span style="color:#cbd5e1;">|</span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="metric-card card-indigo">
            <div class="metric-card-head">
                <h4 class="metric-title"><?php echo $lang['DASH_SEASONAL_PRESSURE']; ?></h4>
                <div class="metric-icon">
                    <svg width="18" height="18" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M256 32C150 32 64 118 64 224c0 124.6 147.2 228.3 179.6 249.4c7.4 4.8 17 4.8 24.4 0C300.8 452.3 448 348.6 448 224C448 118 362 32 256 32zm0 256c-35.3 0-64-28.7-64-64s28.7-64 64-64s64 28.7 64 64s-28.7 64-64 64z"/></svg>
                </div>
            </div>

            <div class="rc-pressure-row">
                <div class="metric-value"><?php echo number_format((int)$current7DayAdmissions); ?></div>
                <div class="rc-pressure-badge <?php echo $seasonalPressureClass; ?>">
                    <?php echo htmlspecialchars($seasonalPressureLabel); ?>
                </div>
            </div>

            <div class="metric-footer"><?php echo $lang['DASH_LAST_7_VS_NORM']; ?></div>

            <div class="rc-pressure-grid">
                <div class="rc-pressure-item">
                    <span class="label"><?php echo $lang['CURRENT']; ?></span>
                    <span class="value"><?php echo number_format((int)$current7DayAdmissions); ?></span>
                </div>

                <div class="rc-pressure-item">
                    <span class="label"><?php echo $lang['TYPICAL']; ?></span>
                    <span class="value"><?php echo number_format((float)$seasonalAverageAdmissions, 1); ?></span>
                </div>

                <div class="rc-pressure-item">
                    <span class="label"><?php echo $lang['DIFFERENCE']; ?></span>
                    <span class="value"><?php echo ($seasonalDifferencePct >= 0 ? '+' : ''); ?><?php echo number_format((float)$seasonalDifferencePct, 1); ?>%</span>
                </div>
            </div>

            <div class="rc-pressure-meter-wrap">
                <div class="rc-pressure-meter">
                    <div class="rc-pressure-marker" style="left: <?php echo $seasonMarkerPercent; ?>%;"></div>
                </div>
                <div class="rc-pressure-caption">
                    <?php echo htmlspecialchars($seasonalPressureText); ?> <?php echo htmlspecialchars(sprintf($lang['DASH_BASED_ON_PREVIOUS_YEARS'], (int)$seasonalHistoryYears)); ?>
                </div>
            </div>
        </div>

    </div>
</div>
