<?php
// views/groups/dashboard/occupancy.php
// Expects:
// - $networkStats
// - $centreStats
// - helper functions from dashboard_data.php

if (!isset($networkStats) || !is_array($networkStats)) {
    return;
}

if (!isset($centreStats) || !is_array($centreStats)) {
    $centreStats = [];
}

$totalCapacity       = (int)($networkStats['total_capacity'] ?? 0);
$occupiedSpaces      = (int)($networkStats['occupied_spaces'] ?? 0);
$occupancyPercent    = (float)($networkStats['occupancy_percent'] ?? 0);
$capacityState       = $networkStats['capacity_state'] ?? ['class' => 'is-good', 'label' => 'Comfortable capacity'];
$highOccupancyCount  = (int)($networkStats['centres_high_occupancy'] ?? 0);
$fullOrOverCount     = (int)($networkStats['centres_full_or_over'] ?? 0);
$averageCentreOcc    = (float)($networkStats['average_centre_occupancy'] ?? 0);
$centresCount        = (int)($networkStats['centres_count'] ?? count($centreStats));

$headlineBarWidth = max(0, min(100, (int)round($occupancyPercent)));

$stateClass = (string)($capacityState['class'] ?? 'is-good');
$stateLabel = (string)($capacityState['label'] ?? 'Comfortable capacity');

$stateBg = '#ecfdf5';
$stateText = '#065f46';
$stateBorder = '#a7f3d0';
$barGradient = 'linear-gradient(90deg, #22c55e 0%, #86efac 100%)';

if ($stateClass === 'is-medium') {
    $stateBg = '#eff6ff';
    $stateText = '#1d4ed8';
    $stateBorder = '#bfdbfe';
    $barGradient = 'linear-gradient(90deg, #3b82f6 0%, #93c5fd 100%)';
} elseif ($stateClass === 'is-high') {
    $stateBg = '#fff7ed';
    $stateText = '#c2410c';
    $stateBorder = '#fed7aa';
    $barGradient = 'linear-gradient(90deg, #f59e0b 0%, #fb923c 100%)';
}

$centresWithCapacity = 0;
$centresNoCapacity = 0;
$centresComfortable = 0;

foreach ($centreStats as $row) {
    $cap = (int)($row['total_capacity'] ?? 0);
    $pct = (float)($row['occupancy_percent'] ?? 0);

    if ($cap > 0) {
        $centresWithCapacity++;
        if ($pct < 85) {
            $centresComfortable++;
        }
    } else {
        $centresNoCapacity++;
    }
}
?>

<style>
.group-occupancy {
    margin-bottom: 14px;
}

.group-occupancy__grid {
    display: grid;
    grid-template-columns: minmax(320px, 1.05fr) minmax(320px, 1.95fr);
    gap: 14px;
    align-items: start;
}

.group-occupancy__card,
.group-occupancy__panel,
.group-occupancy__centre {
    border-radius: var(--rc-radius);
}

.group-occupancy__card {
    padding: 18px;
    position: sticky;
    top: 12px;
}

.group-occupancy__eyebrow {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: var(--rc-muted);
    margin-bottom: 6px;
}

.group-occupancy__title {
    font-size: 28px;
    font-weight: 900;
    line-height: 1;
    color: var(--rc-text);
    margin: 0;
}

.group-occupancy__subtitle {
    margin-top: 8px;
    font-size: 14px;
    color: var(--rc-muted);
}

.group-occupancy__pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 14px;
    padding: 8px 11px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    border: 1px solid transparent;
}

.group-occupancy__bar-wrap {
    margin-top: 16px;
}

.group-occupancy__bar-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: var(--rc-muted);
    margin-bottom: 7px;
}

.group-occupancy__bar {
    height: 14px;
    background: var(--rc-border);
    border-radius: 999px;
    overflow: hidden;
}

.group-occupancy__bar-fill {
    height: 14px;
    border-radius: 999px;
}

.group-occupancy__mini-grid {
    margin-top: 16px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.group-occupancy__mini {
    padding: 12px;
}

.group-occupancy__mini-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--rc-muted);
    text-transform: uppercase;
    letter-spacing: .03em;
}

.group-occupancy__mini-value {
    font-size: 24px;
    font-weight: 900;
    line-height: 1.05;
    color: var(--rc-text);
    margin-top: 7px;
}

.group-occupancy__mini-meta {
    margin-top: 6px;
    font-size: 12px;
    color: var(--rc-muted);
}

.group-occupancy__note {
    margin-top: 14px;
    font-size: 12px;
    color: var(--rc-muted);
    line-height: 1.45;
}

.group-occupancy__panel {
    padding: 16px;
}

.group-occupancy__panel-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}

.group-occupancy__panel-title {
    font-size: 18px;
    font-weight: 800;
    color: var(--rc-text);
    margin: 0;
}

.group-occupancy__panel-subtitle {
    margin-top: 4px;
    font-size: 13px;
    color: var(--rc-muted);
}

.group-occupancy__legend {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.group-occupancy__legend-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    background: var(--rc-surface-muted);
    border: 1px solid var(--rc-border);
    color: var(--rc-text);
}

.group-occupancy__dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    display: inline-block;
}

.group-occupancy__centre-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.group-occupancy__centre {
    padding: 14px;
    border-top: 4px solid #cbd5e1;
}

.group-occupancy__centre-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
}

.group-occupancy__centre-namewrap {
    min-width: 0;
}

.group-occupancy__centre-name {
    font-size: 16px;
    font-weight: 800;
    color: var(--rc-text);
    line-height: 1.2;
    margin: 0;
    word-break: break-word;
}

.group-occupancy__centre-id {
    margin-top: 4px;
    font-size: 12px;
    color: var(--rc-muted);
}

.group-occupancy__centre-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 7px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
    border: 1px solid transparent;
}

.group-occupancy__centre-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 12px;
}

.group-occupancy__centre-stat {
    padding: 10px;
}

.group-occupancy__centre-stat-label {
    font-size: 11px;
    font-weight: 700;
    color: var(--rc-muted);
    text-transform: uppercase;
    letter-spacing: .03em;
}

.group-occupancy__centre-stat-value {
    font-size: 18px;
    font-weight: 900;
    color: var(--rc-text);
    line-height: 1.05;
    margin-top: 6px;
}

.group-occupancy__centre-bar-meta {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    font-size: 12px;
    color: var(--rc-muted);
    margin-bottom: 6px;
}

.group-occupancy__centre-bar {
    height: 10px;
    background: var(--rc-border);
    border-radius: 999px;
    overflow: hidden;
}

.group-occupancy__centre-bar-fill {
    height: 10px;
    border-radius: 999px;
}

.group-occupancy__centre-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 10px;
    font-size: 12px;
    color: var(--rc-muted);
}

.group-occupancy__empty {
    padding: 18px;
    font-size: 14px;
}

@media (max-width: 1120px) {
    .group-occupancy__grid {
        grid-template-columns: 1fr;
    }

    .group-occupancy__card {
        position: static;
    }

    .group-occupancy__centre-list {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .group-occupancy__mini-grid,
    .group-occupancy__centre-stats {
        grid-template-columns: 1fr;
    }

    .group-occupancy__card,
    .group-occupancy__panel,
    .group-occupancy__centre {
        border-radius: 14px;
    }

    .group-occupancy__title {
        font-size: 24px;
    }
}
</style>

<div class="group-occupancy">
    <div class="group-occupancy__grid">

        <div class="rc-card group-occupancy__card">
            <div class="group-occupancy__eyebrow">Occupancy overview</div>
            <h3 class="group-occupancy__title">
                <?= group_dash_num($occupancyPercent, 1) ?>%
            </h3>
            <div class="group-occupancy__subtitle">
                <?= number_format($occupiedSpaces) ?> occupied spaces across
                <?= number_format($totalCapacity) ?> available capacity
            </div>

            <div class="group-occupancy__pill"
                 style="background: <?= htmlspecialchars($stateBg) ?>; color: <?= htmlspecialchars($stateText) ?>; border-color: <?= htmlspecialchars($stateBorder) ?>;">
                <?= htmlspecialchars($stateLabel) ?>
            </div>

            <div class="group-occupancy__bar-wrap">
                <div class="group-occupancy__bar-meta">
                    <span>Capacity utilisation</span>
                    <span><?= number_format($occupiedSpaces) ?> / <?= number_format($totalCapacity) ?></span>
                </div>
                <div class="group-occupancy__bar">
                    <div class="group-occupancy__bar-fill"
                         style="width: <?= $headlineBarWidth ?>%; background: <?= htmlspecialchars($barGradient) ?>;"></div>
                </div>
            </div>

            <div class="group-occupancy__mini-grid">
                <div class="rc-card rc-card-muted group-occupancy__mini">
                    <div class="group-occupancy__mini-label">Centres tracked</div>
                    <div class="group-occupancy__mini-value"><?= number_format($centresCount) ?></div>
                    <div class="group-occupancy__mini-meta">Active member centres in this group</div>
                </div>

                <div class="rc-card rc-card-muted group-occupancy__mini">
                    <div class="group-occupancy__mini-label">Average centre occupancy</div>
                    <div class="group-occupancy__mini-value"><?= group_dash_num($averageCentreOcc, 1) ?>%</div>
                    <div class="group-occupancy__mini-meta">Average across centres with capacity data</div>
                </div>

                <div class="rc-card rc-card-muted group-occupancy__mini">
                    <div class="group-occupancy__mini-label">High occupancy</div>
                    <div class="group-occupancy__mini-value"><?= number_format($highOccupancyCount) ?></div>
                    <div class="group-occupancy__mini-meta">Centres at 85% occupancy or above</div>
                </div>

                <div class="rc-card rc-card-muted group-occupancy__mini">
                    <div class="group-occupancy__mini-label">Full / over capacity</div>
                    <div class="group-occupancy__mini-value"><?= number_format($fullOrOverCount) ?></div>
                    <div class="group-occupancy__mini-meta">Centres at 100% occupancy or above</div>
                </div>
            </div>

            <div class="group-occupancy__note">
                Occupancy currently uses <strong>active admissions as the occupied-space proxy</strong>.
                Once a live location-assignment source is available, this section can be switched to true physical occupancy.
            </div>
        </div>

        <div class="rc-panel group-occupancy__panel">
            <div class="group-occupancy__panel-head">
                <div>
                    <h3 class="group-occupancy__panel-title">Centre occupancy</h3>
                    <div class="group-occupancy__panel-subtitle">
                        Compare capacity pressure across all member centres
                    </div>
                </div>

                <div class="group-occupancy__legend">
                    <div class="group-occupancy__legend-pill">
                        <span class="group-occupancy__dot" style="background:#22c55e;"></span>
                        Comfortable
                    </div>
                    <div class="group-occupancy__legend-pill">
                        <span class="group-occupancy__dot" style="background:#3b82f6;"></span>
                        Moderate
                    </div>
                    <div class="group-occupancy__legend-pill">
                        <span class="group-occupancy__dot" style="background:#f59e0b;"></span>
                        High / Full
                    </div>
                </div>
            </div>

        <?php if (empty($centreStats)): ?>
    <div class="rc-alert grey group-occupancy__empty">
        No centre occupancy data is available yet for this group.
    </div>
<?php else: ?>
    <style>
        .group-occupancy__inline-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .group-occupancy__inline-row {
            display: grid;
            grid-template-columns: minmax(180px, 240px) minmax(140px, 1fr) auto auto auto;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: var(--rc-surface-muted);
            border: 1px solid var(--rc-border);
            border-left: 4px solid #cbd5e1;
            border-radius: 12px;
        }

        .group-occupancy__inline-name {
            font-size: 14px;
            font-weight: 800;
            color: var(--rc-text);
            line-height: 1.2;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .group-occupancy__inline-bar {
            height: 12px;
            background: var(--rc-border);
            border-radius: 999px;
            overflow: hidden;
            min-width: 120px;
        }

        .group-occupancy__inline-bar-fill {
            height: 12px;
            border-radius: 999px;
        }

        .group-occupancy__inline-metric,
        .group-occupancy__inline-pct {
            font-size: 13px;
            font-weight: 800;
            color: var(--rc-text);
            white-space: nowrap;
        }

        .group-occupancy__inline-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        @media (max-width: 980px) {
            .group-occupancy__inline-row {
                grid-template-columns: minmax(140px, 1fr) minmax(120px, 1fr) auto auto;
            }

            .group-occupancy__inline-pill {
                grid-column: 1 / -1;
                justify-self: start;
            }
        }

        @media (max-width: 640px) {
            .group-occupancy__inline-row {
                grid-template-columns: 1fr;
                gap: 8px;
                align-items: start;
            }

            .group-occupancy__inline-name {
                white-space: normal;
            }

            .group-occupancy__inline-metric,
            .group-occupancy__inline-pct {
                display: inline-block;
            }
        }
    </style>

    <div class="group-occupancy__inline-list">
        <?php foreach ($centreStats as $row): ?>
            <?php
            $centreId         = (int)($row['centre_id'] ?? 0);
            $centreName       = trim((string)($row['centre_name'] ?? ('Centre #' . $centreId)));
            $colour           = trim((string)($row['colour'] ?? '#2563eb'));
            $activeAdmissions = (int)($row['active_admissions'] ?? 0);
            $capacity         = (int)($row['total_capacity'] ?? 0);
            $occupied         = (int)($row['occupied_spaces'] ?? $activeAdmissions);
            $occPct           = (float)($row['occupancy_percent'] ?? 0);
            $rowState         = $row['capacity_state'] ?? ['class' => 'is-good', 'label' => 'Comfortable capacity'];
            $rowStateClass    = (string)($rowState['class'] ?? 'is-good');
            $rowStateLabel    = (string)($rowState['label'] ?? 'Comfortable capacity');
            $rowBarWidth      = max(0, min(100, (int)round($occPct)));

            $badgeBg = '#ecfdf5';
            $badgeText = '#065f46';
            $badgeBorder = '#a7f3d0';
            $rowBar = 'linear-gradient(90deg, #22c55e 0%, #86efac 100%)';

            if ($capacity <= 0) {
                $rowStateLabel = 'No capacity set';
                $badgeBg = '#f8fafc';
                $badgeText = '#475569';
                $badgeBorder = '#cbd5e1';
                $rowBar = 'linear-gradient(90deg, #94a3b8 0%, #cbd5e1 100%)';
                $rowBarWidth = 0;
            } elseif ($rowStateClass === 'is-medium') {
                $badgeBg = '#eff6ff';
                $badgeText = '#1d4ed8';
                $badgeBorder = '#bfdbfe';
                $rowBar = 'linear-gradient(90deg, #3b82f6 0%, #93c5fd 100%)';
            } elseif ($rowStateClass === 'is-high') {
                $badgeBg = '#fff7ed';
                $badgeText = '#c2410c';
                $badgeBorder = '#fed7aa';
                $rowBar = 'linear-gradient(90deg, #f59e0b 0%, #fb923c 100%)';
            }
            ?>
            <div class="rc-card rc-card-muted group-occupancy__inline-row" style="border-left-color: <?= htmlspecialchars($colour) ?>;">
                <div class="group-occupancy__inline-name">
                    <?= htmlspecialchars($centreName) ?>
                </div>

                <div class="group-occupancy__inline-bar">
                    <div class="group-occupancy__inline-bar-fill"
                         style="width: <?= $rowBarWidth ?>%; background: <?= htmlspecialchars($rowBar) ?>;"></div>
                </div>

                <div class="group-occupancy__inline-metric">
                    <?= number_format($occupied) ?>/<?= number_format($capacity) ?>
                </div>

                <div class="group-occupancy__inline-pct">
                    (<?= group_dash_num($occPct, 1) ?>%)
                </div>

                <div class="group-occupancy__inline-pill"
                     style="background: <?= htmlspecialchars($badgeBg) ?>; color: <?= htmlspecialchars($badgeText) ?>; border-color: <?= htmlspecialchars($badgeBorder) ?>;">
                    <?= htmlspecialchars($rowStateLabel) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($centresNoCapacity > 0): ?>
        <div class="group-occupancy__note" style="margin-top:14px;">
            <?= number_format($centresNoCapacity) ?> centre<?= $centresNoCapacity === 1 ? '' : 's' ?>
            currently ha<?= $centresNoCapacity === 1 ? 's' : 've' ?> no capacity set in locations, so occupancy for those centres cannot yet be measured properly.
        </div>
    <?php endif; ?>
<?php endif; ?>
        
        </div>

    </div>
</div>
