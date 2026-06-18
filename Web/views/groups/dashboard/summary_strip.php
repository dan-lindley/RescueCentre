<?php
// views/groups/dashboard/summary_strip.php
// Expects:
// - $networkStats
// - $rangeLabel
// - $gid
// - helper functions from dashboard_data.php

if (!isset($networkStats) || !is_array($networkStats)) {
    return;
}

$centresCount          = (int)($networkStats['centres_count'] ?? 0);
$activeAdmissions      = (int)($networkStats['active_admissions'] ?? 0);
$totalCapacity         = (int)($networkStats['total_capacity'] ?? 0);
$occupiedSpaces        = (int)($networkStats['occupied_spaces'] ?? 0);
$occupancyPercent      = (float)($networkStats['occupancy_percent'] ?? 0);
$capacityState         = $networkStats['capacity_state'] ?? ['class' => 'is-good', 'label' => 'Comfortable capacity'];
$admissionsInRange     = (int)($networkStats['admissions_in_range'] ?? 0);
$admissionsAllTime     = (int)($networkStats['admissions_all_time'] ?? 0);
$highOccupancyCentres  = (int)($networkStats['centres_high_occupancy'] ?? 0);
$fullOrOverCentres     = (int)($networkStats['centres_full_or_over'] ?? 0);
$averageCentreOcc      = (float)($networkStats['average_centre_occupancy'] ?? 0);
$topCentreName         = trim((string)($networkStats['top_centre_name'] ?? ''));
$topCentreActive       = (int)($networkStats['top_centre_active'] ?? 0);

$rangeBtns = [
    'all' => 'All',
    '30'  => '30d',
    '90'  => '3m',
    '180' => '6m',
    '365' => '1y',
    '730' => '2y',
];

$stateClass = (string)($capacityState['class'] ?? 'is-good');
$stateLabel = (string)($capacityState['label'] ?? 'Comfortable capacity');

$stateBg = '#ecfdf5';
$stateText = '#065f46';
$stateBorder = '#a7f3d0';

if ($stateClass === 'is-medium') {
    $stateBg = '#eff6ff';
    $stateText = '#1d4ed8';
    $stateBorder = '#bfdbfe';
} elseif ($stateClass === 'is-high') {
    $stateBg = '#fff7ed';
    $stateText = '#c2410c';
    $stateBorder = '#fed7aa';
}

$occBarWidth = max(0, min(100, (int)round($occupancyPercent)));
?>

<style>
.group-summary-strip {
    background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 55%, #38bdf8 100%);
    color: #fff;
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.14);
    margin-bottom: 14px;
    overflow: hidden;
    position: relative;
}

.group-summary-strip::after {
    content: "";
    position: absolute;
    inset: auto -40px -40px auto;
    width: 180px;
    height: 180px;
    background: rgba(255,255,255,0.08);
    border-radius: 999px;
    pointer-events: none;
}

.group-summary-strip__top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}

.group-summary-strip__title {
    font-size: 24px;
    font-weight: 800;
    line-height: 1.1;
    margin: 0;
}

.group-summary-strip__subtitle {
    margin-top: 6px;
    font-size: 13px;
    color: rgba(255,255,255,0.88);
}

.group-summary-strip__filters {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.group-summary-strip__filter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
    padding: 7px 11px;
    border-radius: 999px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid rgba(255,255,255,0.24);
    color: #fff;
    background: rgba(255,255,255,0.10);
    transition: all .18s ease;
}

.group-summary-strip__filter:hover {
    background: rgba(255,255,255,0.18);
    color: #fff;
    text-decoration: none;
}

.group-summary-strip__filter.is-active {
    background: var(--rc-surface);
    color: var(--rc-text);
    border-color: var(--rc-surface);
}

.group-summary-strip__main {
    margin-top: 18px;
    display: grid;
    grid-template-columns: minmax(320px, 1.6fr) minmax(240px, 1fr);
    gap: 14px;
    position: relative;
    z-index: 1;
}

.group-summary-strip__hero {
    background: rgba(255,255,255,0.10);
    border: 1px solid rgba(255,255,255,0.16);
    border-radius: 16px;
    padding: 16px;
    backdrop-filter: blur(4px);
}

.group-summary-strip__hero-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    flex-wrap: wrap;
}

.group-summary-strip__label {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.75);
}

.group-summary-strip__hero-value {
    font-size: 34px;
    font-weight: 900;
    line-height: 1;
    margin-top: 8px;
}

.group-summary-strip__hero-meta {
    margin-top: 8px;
    font-size: 13px;
    color: rgba(255,255,255,0.90);
}

.group-summary-strip__pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    background: var(--rc-surface);
    color: var(--rc-text);
    white-space: nowrap;
}

.group-summary-strip__bar-wrap {
    margin-top: 14px;
}

.group-summary-strip__bar-meta {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    font-size: 12px;
    color: rgba(255,255,255,0.82);
    margin-bottom: 6px;
}

.group-summary-strip__bar {
    height: 12px;
    background: rgba(255,255,255,0.18);
    border-radius: 999px;
    overflow: hidden;
}

.group-summary-strip__bar-fill {
    height: 12px;
    border-radius: 999px;
    background: linear-gradient(90deg, #86efac 0%, #fde68a 60%, #fdba74 100%);
}

.group-summary-strip__stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.group-summary-strip__stat {
    border-radius: 16px;
    padding: 14px;
    min-height: 102px;
}

    .group-summary-strip__stat-label {
    font-size: 12px;
    color: var(--rc-muted);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .03em;
}

.group-summary-strip__stat-value {
    font-size: 26px;
    font-weight: 900;
    line-height: 1.05;
    margin-top: 8px;
}

.group-summary-strip__stat-meta {
    margin-top: 6px;
    font-size: 12px;
    color: var(--rc-muted);
}

.group-summary-strip__alerts {
    margin-top: 14px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}

.group-summary-strip__alert {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 11px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.16);
    color: #fff;
}

.group-summary-strip__alert strong {
    font-weight: 900;
}

@media (max-width: 980px) {
    .group-summary-strip__main {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .group-summary-strip {
        padding: 14px;
        border-radius: 14px;
    }

    .group-summary-strip__title {
        font-size: 20px;
    }

    .group-summary-strip__hero-value {
        font-size: 28px;
    }

    .group-summary-strip__stats {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="group-summary-strip">
    <div class="group-summary-strip__top">
        <div>
            <h2 class="group-summary-strip__title">Network overview</h2>
            <div class="group-summary-strip__subtitle">
                Live group view across <?= number_format($centresCount) ?> centre<?= $centresCount === 1 ? '' : 's' ?>
                • Range: <strong><?= htmlspecialchars($rangeLabel) ?></strong>
            </div>
        </div>

        <div class="group-summary-strip__filters">
            <?php foreach ($rangeBtns as $key => $label): ?>
                <?php
                $href = 'viewnetwork.php?network_id=' . (int)$gid . '&tab=dashboard&range=' . urlencode($key);
                $activeClass = (($range ?? 'all') === $key) ? 'is-active' : '';
                ?>
                <a href="<?= htmlspecialchars($href) ?>" class="group-summary-strip__filter <?= $activeClass ?>">
                    <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="group-summary-strip__main">
        <div class="group-summary-strip__hero">
            <div class="group-summary-strip__hero-top">
                <div>
                    <div class="group-summary-strip__label">Network occupancy</div>
                    <div class="group-summary-strip__hero-value">
                        <?= group_dash_num($occupancyPercent, 1) ?>%
                    </div>
                    <div class="group-summary-strip__hero-meta">
                        <?= number_format($occupiedSpaces) ?> occupied
                        / <?= number_format($totalCapacity) ?> total capacity
                    </div>
                </div>

                <div class="group-summary-strip__pill"
                     style="background: <?= htmlspecialchars($stateBg) ?>; color: <?= htmlspecialchars($stateText) ?>; border:1px solid <?= htmlspecialchars($stateBorder) ?>;">
                    <?= htmlspecialchars($stateLabel) ?>
                </div>
            </div>

            <div class="group-summary-strip__bar-wrap">
                <div class="group-summary-strip__bar-meta">
                    <span>Capacity utilisation</span>
                    <span><?= number_format($occupiedSpaces) ?> / <?= number_format($totalCapacity) ?></span>
                </div>
                <div class="group-summary-strip__bar">
                    <div class="group-summary-strip__bar-fill" style="width: <?= $occBarWidth ?>%;"></div>
                </div>
            </div>

            <div class="group-summary-strip__alerts">
                <div class="group-summary-strip__alert">
                    High occupancy centres:
                    <strong><?= number_format($highOccupancyCentres) ?></strong>
                </div>

                <div class="group-summary-strip__alert">
                    Full / over capacity:
                    <strong><?= number_format($fullOrOverCentres) ?></strong>
                </div>

                <div class="group-summary-strip__alert">
                    Average centre occupancy:
                    <strong><?= group_dash_num($averageCentreOcc, 1) ?>%</strong>
                </div>
            </div>
        </div>

        <div class="group-summary-strip__stats">
            <div class="rc-card group-summary-strip__stat">
                <div class="group-summary-strip__stat-label">Active admissions</div>
                <div class="group-summary-strip__stat-value"><?= number_format($activeAdmissions) ?></div>
                <div class="group-summary-strip__stat-meta">
                    Current active cases across all member centres
                </div>
            </div>

            <div class="rc-card group-summary-strip__stat">
                <div class="group-summary-strip__stat-label">Admissions in range</div>
                <div class="group-summary-strip__stat-value"><?= number_format($admissionsInRange) ?></div>
                <div class="group-summary-strip__stat-meta">
                    For <?= htmlspecialchars($rangeLabel) ?>
                </div>
            </div>

            <div class="rc-card group-summary-strip__stat">
                <div class="group-summary-strip__stat-label">Total admissions</div>
                <div class="group-summary-strip__stat-value"><?= number_format($admissionsAllTime) ?></div>
                <div class="group-summary-strip__stat-meta">
                    All-time admissions recorded for this network
                </div>
            </div>

            <div class="rc-card group-summary-strip__stat">
                <div class="group-summary-strip__stat-label">Busiest centre</div>
                <div class="group-summary-strip__stat-value" style="font-size:20px;">
                    <?= $topCentreName !== '' ? htmlspecialchars($topCentreName) : '—' ?>
                </div>
                <div class="group-summary-strip__stat-meta">
                    <?= $topCentreName !== '' ? number_format($topCentreActive) . ' active admissions' : 'No centre data yet' ?>
                </div>
            </div>
        </div>
    </div>
</div>
