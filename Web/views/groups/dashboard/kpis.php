<?php
// views/groups/dashboard/kpis.php
// Expects:
// - $networkStats
// - $centreStats
// - $rangeLabel
// - helper functions from dashboard_data.php

if (!isset($networkStats) || !is_array($networkStats)) {
    return;
}

if (!isset($centreStats) || !is_array($centreStats)) {
    $centreStats = [];
}

$activeAdmissions     = (int)($networkStats['active_admissions'] ?? 0);
$admissionsInRange    = (int)($networkStats['admissions_in_range'] ?? 0);
$admissionsAllTime    = (int)($networkStats['admissions_all_time'] ?? 0);
$totalCapacity        = (int)($networkStats['total_capacity'] ?? 0);
$occupiedSpaces       = (int)($networkStats['occupied_spaces'] ?? 0);
$occupancyPercent     = (float)($networkStats['occupancy_percent'] ?? 0);
$averageCentreOcc     = (float)($networkStats['average_centre_occupancy'] ?? 0);
$highOccupancyCount   = (int)($networkStats['centres_high_occupancy'] ?? 0);
$fullOrOverCount      = (int)($networkStats['centres_full_or_over'] ?? 0);
$topCentreName        = trim((string)($networkStats['top_centre_name'] ?? ''));
$topCentreActive      = (int)($networkStats['top_centre_active'] ?? 0);
$centresCount         = (int)($networkStats['centres_count'] ?? count($centreStats));

$capacityRemaining = max(0, $totalCapacity - $occupiedSpaces);
$centresWithCapacity = 0;
$comfortableCount = 0;
$highestOccupancyCentre = null;
$highestOccupancyPct = -1;

foreach ($centreStats as $row) {
    $cap = (int)($row['total_capacity'] ?? 0);
    $pct = (float)($row['occupancy_percent'] ?? 0);

    if ($cap > 0) {
        $centresWithCapacity++;
        if ($pct < 85) {
            $comfortableCount++;
        }
        if ($pct > $highestOccupancyPct) {
            $highestOccupancyPct = $pct;
            $highestOccupancyCentre = $row;
        }
    }
}

$highestOccName = '';
$highestOccPct = 0.0;
$highestOccState = ['class' => 'is-good', 'label' => 'Comfortable capacity'];

if ($highestOccupancyCentre) {
    $highestOccName = trim((string)($highestOccupancyCentre['centre_name'] ?? ''));
    $highestOccPct = (float)($highestOccupancyCentre['occupancy_percent'] ?? 0);
    $highestOccState = $highestOccupancyCentre['capacity_state'] ?? $highestOccState;
}

function group_kpi_state_colours(string $stateClass): array
{
    if ($stateClass === 'is-high') {
        return [
            'bg'     => '#fff7ed',
            'text'   => '#c2410c',
            'border' => '#fed7aa',
        ];
    }

    if ($stateClass === 'is-medium') {
        return [
            'bg'     => '#eff6ff',
            'text'   => '#1d4ed8',
            'border' => '#bfdbfe',
        ];
    }

    return [
        'bg'     => '#ecfdf5',
        'text'   => '#065f46',
        'border' => '#a7f3d0',
    ];
}

$networkState = group_dash_capacity_state($occupancyPercent);
$networkStateColours = group_kpi_state_colours((string)($networkState['class'] ?? 'is-good'));
$highestStateColours = group_kpi_state_colours((string)($highestOccState['class'] ?? 'is-good'));
?>

<style>
.group-kpis {
    margin-bottom: 14px;
}

.group-kpis__grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.group-kpis__card {
    padding: 15px;
    position: relative;
    overflow: hidden;
}

.group-kpis__card::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: #cbd5e1;
}

.group-kpis__card.is-blue::before   { background: #2563eb; }
.group-kpis__card.is-green::before  { background: #16a34a; }
.group-kpis__card.is-orange::before { background: #ea580c; }
.group-kpis__card.is-purple::before { background: #7c3aed; }
.group-kpis__card.is-cyan::before   { background: #0891b2; }
.group-kpis__card.is-amber::before  { background: #d97706; }
.group-kpis__card.is-rose::before   { background: #e11d48; }
.group-kpis__card.is-slate::before  { background: #475569; }

.group-kpis__label {
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--rc-muted);
}

.group-kpis__value {
    margin-top: 8px;
    font-size: 30px;
    font-weight: 900;
    line-height: 1;
    color: var(--rc-text);
}

.group-kpis__value.is-name {
    font-size: 20px;
    line-height: 1.15;
    word-break: break-word;
}

.group-kpis__meta {
    margin-top: 8px;
    font-size: 13px;
    color: var(--rc-muted);
    line-height: 1.45;
}

.group-kpis__pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 10px;
    padding: 7px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
    border: 1px solid transparent;
}

.group-kpis__subgrid {
    margin-top: 10px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.group-kpis__mini {
    padding: 9px 10px;
}

.group-kpis__mini-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: var(--rc-muted);
}

.group-kpis__mini-value {
    margin-top: 5px;
    font-size: 16px;
    font-weight: 900;
    color: var(--rc-text);
    line-height: 1.1;
}

.group-kpis__foot {
    margin-top: 10px;
    font-size: 12px;
    color: var(--rc-muted);
}

@media (max-width: 1180px) {
    .group-kpis__grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .group-kpis__grid {
        grid-template-columns: 1fr;
    }

    .group-kpis__card {
        border-radius: var(--rc-radius);
    }

    .group-kpis__value {
        font-size: 26px;
    }

    .group-kpis__subgrid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="group-kpis">
    <div class="group-kpis__grid">

        <div class="rc-card group-kpis__card is-blue">
            <div class="group-kpis__label">Admissions in range</div>
            <div class="group-kpis__value"><?= number_format($admissionsInRange) ?></div>
            <div class="group-kpis__meta">
                New admissions recorded for <strong><?= htmlspecialchars((string)$rangeLabel) ?></strong>.
            </div>
            <div class="group-kpis__foot">
                All-time total: <strong><?= number_format($admissionsAllTime) ?></strong>
            </div>
        </div>

        <div class="rc-card group-kpis__card is-green">
            <div class="group-kpis__label">Network utilisation</div>
            <div class="group-kpis__value"><?= group_dash_num($occupancyPercent, 1) ?>%</div>
            <div class="group-kpis__meta">
                <?= number_format($occupiedSpaces) ?> occupied out of <?= number_format($totalCapacity) ?> capacity.
            </div>
            <div class="group-kpis__pill"
                 style="background: <?= htmlspecialchars($networkStateColours['bg']) ?>; color: <?= htmlspecialchars($networkStateColours['text']) ?>; border-color: <?= htmlspecialchars($networkStateColours['border']) ?>;">
                <?= htmlspecialchars((string)($networkState['label'] ?? 'Comfortable capacity')) ?>
            </div>
        </div>

        <div class="rc-card group-kpis__card is-orange">
            <div class="group-kpis__label">Busiest centre</div>
            <div class="group-kpis__value is-name">
                <?= $topCentreName !== '' ? htmlspecialchars($topCentreName) : '—' ?>
            </div>
            <div class="group-kpis__meta">
                <?= $topCentreName !== '' ? number_format($topCentreActive) . ' active admissions currently' : 'No centre activity available yet.' ?>
            </div>
            <div class="group-kpis__foot">
                Based on current active admissions across the network.
            </div>
        </div>

        <div class="rc-card group-kpis__card is-purple">
            <div class="group-kpis__label">Highest occupancy centre</div>
            <div class="group-kpis__value is-name">
                <?= $highestOccName !== '' ? htmlspecialchars($highestOccName) : '—' ?>
            </div>
            <div class="group-kpis__meta">
                <?= $highestOccName !== '' ? group_dash_num($highestOccPct, 1) . '% occupancy' : 'No capacity-backed occupancy data available yet.' ?>
            </div>
            <?php if ($highestOccName !== ''): ?>
                <div class="group-kpis__pill"
                     style="background: <?= htmlspecialchars($highestStateColours['bg']) ?>; color: <?= htmlspecialchars($highestStateColours['text']) ?>; border-color: <?= htmlspecialchars($highestStateColours['border']) ?>;">
                    <?= htmlspecialchars((string)($highestOccState['label'] ?? 'Occupancy status')) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="rc-card group-kpis__card is-cyan">
            <div class="group-kpis__label">Current caseload</div>
            <div class="group-kpis__value"><?= number_format($activeAdmissions) ?></div>
            <div class="group-kpis__meta">
                Active admissions currently being managed across the group.
            </div>
            <div class="group-kpis__subgrid">
                <div class="rc-card rc-card-muted group-kpis__mini">
                    <div class="group-kpis__mini-label">Centres tracked</div>
                    <div class="group-kpis__mini-value"><?= number_format($centresCount) ?></div>
                </div>
                <div class="rc-card rc-card-muted group-kpis__mini">
                    <div class="group-kpis__mini-label">With capacity data</div>
                    <div class="group-kpis__mini-value"><?= number_format($centresWithCapacity) ?></div>
                </div>
            </div>
        </div>

        <div class="rc-card group-kpis__card is-amber">
            <div class="group-kpis__label">Capacity pressure</div>
            <div class="group-kpis__value"><?= number_format($highOccupancyCount) ?></div>
            <div class="group-kpis__meta">
                Centres currently at <strong>85% occupancy or above</strong>.
            </div>
            <div class="group-kpis__subgrid">
                <div class="rc-card rc-card-muted group-kpis__mini">
                    <div class="group-kpis__mini-label">Full / over</div>
                    <div class="group-kpis__mini-value"><?= number_format($fullOrOverCount) ?></div>
                </div>
                <div class="rc-card rc-card-muted group-kpis__mini">
                    <div class="group-kpis__mini-label">Comfortable</div>
                    <div class="group-kpis__mini-value"><?= number_format($comfortableCount) ?></div>
                </div>
            </div>
        </div>

        <div class="rc-card group-kpis__card is-rose">
            <div class="group-kpis__label">Average centre occupancy</div>
            <div class="group-kpis__value"><?= group_dash_num($averageCentreOcc, 1) ?>%</div>
            <div class="group-kpis__meta">
                Mean occupancy across centres that have usable capacity data.
            </div>
            <div class="group-kpis__foot">
                Useful for comparing overall pressure vs the busiest individual sites.
            </div>
        </div>

        <div class="rc-card group-kpis__card is-slate">
            <div class="group-kpis__label">Remaining capacity</div>
            <div class="group-kpis__value"><?= number_format($capacityRemaining) ?></div>
            <div class="group-kpis__meta">
                Spare capacity available across the network at the moment.
            </div>
            <div class="group-kpis__subgrid">
                <div class="rc-card rc-card-muted group-kpis__mini">
                    <div class="group-kpis__mini-label">Occupied</div>
                    <div class="group-kpis__mini-value"><?= number_format($occupiedSpaces) ?></div>
                </div>
                <div class="rc-card rc-card-muted group-kpis__mini">
                    <div class="group-kpis__mini-label">Capacity</div>
                    <div class="group-kpis__mini-value"><?= number_format($totalCapacity) ?></div>
                </div>
            </div>
        </div>

    </div>
</div>
