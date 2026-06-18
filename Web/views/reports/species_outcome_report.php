<?php
if (!defined('APP_LOADED')) exit;

if (!function_exists('report_species_outcome_label')) {
    function report_species_outcome_label(array $row): string {
        $species = trim((string)report_value($row, ['Animal Species'], ''));
        $type = trim((string)report_value($row, ['Animal Type'], ''));
        $order = trim((string)report_value($row, ['Animal Order'], ''));

        if ($species !== '') {
            return $species;
        }
        if ($type !== '') {
            return $type;
        }
        if ($order !== '') {
            return $order;
        }
        return 'Unknown species';
    }
}

if (!function_exists('report_species_outcome_counts')) {
    function report_species_outcome_counts(array $row): array {
        return [
            'Released' => (int)report_value($row, ['Released (R)'], 0),
            'Transferred' => (int)report_value($row, ['Transferred (T)'], 0),
            'Euthanised' => (int)report_value($row, ['Euthanised (E)'], 0),
            'Died' => (int)report_value($row, ['Died After Intake (D)'], 0),
            'Dead on arrival' => (int)report_value($row, ['Dead On Admission (DOA)'], 0),
        ];
    }
}

if (!function_exists('report_species_outcome_rate_map')) {
    function report_species_outcome_rate_map(array $rows, string $type): array {
        $rates = [];
        foreach ($rows as $row) {
            $label = report_species_outcome_label($row);
            $total = (int)report_value($row, ['Admitted Total'], 0);
            if ($total <= 0) {
                continue;
            }

            if ($type === 'release') {
                $value = (int)report_value($row, ['Released (R)'], 0);
            } else {
                $value = (int)report_value($row, ['Euthanised (E)'], 0)
                    + (int)report_value($row, ['Died After Intake (D)'], 0)
                    + (int)report_value($row, ['Dead On Admission (DOA)'], 0);
            }

            $rates[$label] = report_pct($value, $total);
        }
        arsort($rates);
        return $rates;
    }
}

if (!function_exists('report_species_outcome_clinical_efficiency_for_row')) {
    function report_species_outcome_clinical_efficiency_for_row(array $row): float {
        $total = (int)report_value($row, ['Admitted Total'], 0);
        $released = (int)report_value($row, ['Released (R)'], 0);
        $excluded = (int)report_value($row, ['Died Within 48 Hours'], 0)
            + (int)report_value($row, ['Dead On Admission (DOA)'], 0)
            + (int)report_value($row, ['Euthanised (E)'], 0)
            + (int)report_value($row, ['Transferred (T)'], 0)
            + (int)report_value($row, ['Held in Captivity (IC)'], 0);

        $denominator = $total - $excluded;
        if ($denominator <= 0) {
            $denominator = 1;
        }

        return report_pct($released, $denominator);
    }
}

if (!function_exists('report_species_outcome_clinical_efficiency_map')) {
    function report_species_outcome_clinical_efficiency_map(array $rows): array {
        $rates = [];
        foreach ($rows as $row) {
            $total = (int)report_value($row, ['Admitted Total'], 0);
            if ($total <= 0) {
                continue;
            }
            $rates[report_species_outcome_label($row)] = report_species_outcome_clinical_efficiency_for_row($row);
        }
        arsort($rates);
        return $rates;
    }
}

if (!function_exists('report_species_outcome_mix_chart')) {
    function report_species_outcome_mix_chart(array $rows, int $limit = 10): void {
        usort($rows, static function (array $a, array $b): int {
            return (int)report_value($b, ['Admitted Total'], 0) <=> (int)report_value($a, ['Admitted Total'], 0);
        });

        $rows = array_slice($rows, 0, $limit);
        $labels = [];
        $families = ['Released', 'Transferred', 'Euthanised', 'Died', 'Dead on arrival'];
        $datasets = [];
        foreach ($families as $family) {
            $datasets[$family] = ['label' => $family, 'data' => []];
        }

        foreach ($rows as $row) {
            $labels[] = report_species_outcome_label($row);
            foreach (report_species_outcome_counts($row) as $family => $count) {
                $datasets[$family]['data'][] = $count;
            }
        }

        report_render_chart_config(
            'Species outcome mix',
            [
                'type' => 'bar',
                'stacked' => true,
                'indexAxis' => 'y',
                'showValues' => true,
                'data' => [
                    'labels' => $labels,
                    'datasets' => array_values($datasets),
                ],
            ],
            'Completed patient journeys split by species and final outcome.'
        );
    }
}

if (!function_exists('report_species_outcome_clinical_efficiency')) {
    function report_species_outcome_clinical_efficiency(array $rows): float {
        $total = report_sum_column($rows, 'Admitted Total');
        $released = report_sum_column($rows, 'Released (R)');
        $excluded = report_sum_column($rows, 'Died Within 48 Hours')
            + report_sum_column($rows, 'Dead On Admission (DOA)')
            + report_sum_column($rows, 'Euthanised (E)')
            + report_sum_column($rows, 'Transferred (T)')
            + report_sum_column($rows, 'Held in Captivity (IC)');

        $denominator = $total - $excluded;
        if ($denominator <= 0) {
            $denominator = 1;
        }

        return report_pct($released, $denominator);
    }
}

if (!function_exists('report_species_outcome_main')) {
    function report_species_outcome_main(array $module, array $rows, array $context): void {
        $cohortSummary = (array)($context['cohort_summary'] ?? []);
        $totalJourneys = (int)($cohortSummary['Complete Patient Journeys'] ?? report_sum_column($rows, 'Admitted Total'));
        $clinicalEfficiency = report_species_outcome_clinical_efficiency($rows);
        $releaseRates = report_species_outcome_rate_map($rows, 'release');
        $efficiencyRates = report_species_outcome_clinical_efficiency_map($rows);
        $bestRelease = array_key_first($releaseRates) ?: 'n/a';
        $lowestEfficiency = $efficiencyRates ? array_key_last($efficiencyRates) : 'n/a';
        $outcomeTotals = [
            'Released' => report_sum_column($rows, 'Released (R)'),
            'Transferred' => report_sum_column($rows, 'Transferred (T)'),
            'Euthanised' => report_sum_column($rows, 'Euthanised (E)'),
            'Died after intake' => report_sum_column($rows, 'Died After Intake (D)'),
            'Dead on arrival' => report_sum_column($rows, 'Dead On Admission (DOA)'),
        ];

        echo '<div class="report-summary-grid report-insight-grid report-grid-four">';
        foreach ([
            [
                'label' => 'Patient journeys',
                'value' => number_format($totalJourneys),
                'note' => 'Complete journeys in the report period',
            ],
            [
                'label' => 'Clinical efficiency',
                'value' => number_format($clinicalEfficiency, 1) . '%',
                'note' => 'Released / eligible clinical outcomes',
            ],
            [
                'label' => 'Best release signal',
                'value' => $bestRelease,
                'note' => 'Highest release percentage',
            ],
            [
                'label' => 'Lowest efficiency signal',
                'value' => $lowestEfficiency,
                'note' => 'Lowest clinical efficiency score',
            ],
        ] as $item) {
            echo '<div class="report-summary-card report-insight">';
            echo '<span class="report-summary-label">' . report_h($item['label']) . '</span>';
            echo '<strong class="report-summary-value">' . report_h($item['value']) . '</strong>';
            echo '<span class="report-summary-note">' . report_h($item['note']) . '</span>';
            echo '</div>';
        }
        echo '</div>';

        echo '<div class="report-chart-grid">';
        report_species_outcome_mix_chart($rows);
        report_chart_from_map('Outcome totals', $outcomeTotals, 'doughnut', 'Overall final outcome mix for completed journeys.');
        report_chart_from_map('Release rate by species (%)', $releaseRates, 'bar', 'Compares release percentage, not admission volume.', 10, 'y');
        report_chart_from_map('Clinical efficiency by species (%)', $efficiencyRates, 'bar', '0 is poor, 100 is best. Uses the dashboard clinical efficiency formula.', 10, 'y');
        echo '</div>';
    }
}

if (!function_exists('report_species_outcome_appendix')) {
    function report_species_outcome_appendix(array $module, array $rows, array $context): void {
        echo '<section class="report-appendix">';
        echo '<h3>' . report_h($module['name'] ?? 'Species and Outcomes') . ' supporting records</h3>';
        echo '<p class="rc-muted">Species-level totals generated from completed patient journeys in the report period.</p>';

        if (!$rows) {
            echo '<div class="rc-card">No completed species outcome records were found for this report period.</div>';
            echo '</section>';
            return;
        }

        foreach ($rows as $row) {
            $label = report_species_outcome_label($row);
            $total = (int)report_value($row, ['Admitted Total'], 0);
            $release = (int)report_value($row, ['Released (R)'], 0);
            $poor = (int)report_value($row, ['Euthanised (E)'], 0)
                + (int)report_value($row, ['Died After Intake (D)'], 0)
                + (int)report_value($row, ['Dead On Admission (DOA)'], 0);
            $chips = [
                'Total journeys: ' . number_format($total),
                'Released: ' . number_format($release),
                'Transferred: ' . number_format((int)report_value($row, ['Transferred (T)'], 0)),
                'Euthanised: ' . number_format((int)report_value($row, ['Euthanised (E)'], 0)),
                'Died: ' . number_format((int)report_value($row, ['Died After Intake (D)'], 0)),
                'DOA: ' . number_format((int)report_value($row, ['Dead On Admission (DOA)'], 0)),
            ];

            echo '<div class="rc-card">';
            echo '<div class="rc-split-head">';
            echo '<h4>' . report_h($label) . '</h4>';
            echo '<span class="rc-badge blue">' . report_h(report_pct($release, $total)) . '% released</span>';
            echo '</div>';
            echo '<div class="rc-chip-row">';
            foreach ($chips as $chip) {
                echo '<span class="rc-chip">' . report_h($chip) . '</span>';
            }
            echo '<span class="rc-chip">' . report_h(report_pct($poor, $total)) . '% poor outcome</span>';
            echo '</div>';
            echo '</div>';
        }

        echo '</section>';
    }
}
