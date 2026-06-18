<?php
if (!defined('APP_LOADED')) exit;

if (!function_exists('report_outcomes_date')) {
    function report_outcomes_date($value): string {
        $value = trim((string)$value);
        if ($value === '') {
            return 'n/a';
        }
        $time = strtotime($value);
        return $time ? date('d M Y', $time) : $value;
    }
}

if (!function_exists('report_outcomes_family_for_row')) {
    function report_outcomes_family_for_row(array $row): string {
        return report_outcome_family(
            report_value($row, ['Universal Shortcode'], ''),
            report_value($row, ['Disposition (Text)'], '')
        );
    }
}

if (!function_exists('report_outcomes_average_days_by_family')) {
    function report_outcomes_average_days_by_family(array $rows): array {
        $daysByFamily = [];
        foreach ($rows as $row) {
            $family = report_outcomes_family_for_row($row);
            $days = (int)report_value($row, ['Days in Care'], 0);
            if ($days <= 0) {
                continue;
            }
            $daysByFamily[$family]['sum'] = ($daysByFamily[$family]['sum'] ?? 0) + $days;
            $daysByFamily[$family]['count'] = ($daysByFamily[$family]['count'] ?? 0) + 1;
        }

        $averages = [];
        foreach ($daysByFamily as $family => $values) {
            $averages[$family] = round($values['sum'] / max(1, $values['count']), 1);
        }
        arsort($averages);
        return $averages;
    }
}

if (!function_exists('report_outcomes_species_mix_chart')) {
    function report_outcomes_species_mix_chart(array $rows, int $limit = 8): void {
        $speciesTotals = [];
        $matrix = [];
        $families = [];

        foreach ($rows as $row) {
            $species = trim((string)report_value($row, ['Animal Species'], 'Unknown species'));
            if ($species === '') {
                $species = 'Unknown species';
            }
            $family = report_outcomes_family_for_row($row);
            $speciesTotals[$species] = ($speciesTotals[$species] ?? 0) + 1;
            $matrix[$species][$family] = ($matrix[$species][$family] ?? 0) + 1;
            $families[$family] = true;
        }

        arsort($speciesTotals);
        $labels = array_slice(array_keys($speciesTotals), 0, $limit);
        $familyOrder = ['Released', 'Transferred', 'Euthanised', 'Died', 'Dead on arrival', 'Captive care', 'Open / pending', 'Unmapped'];
        $datasets = [];

        foreach ($familyOrder as $family) {
            if (empty($families[$family])) {
                continue;
            }
            $data = [];
            foreach ($labels as $species) {
                $data[] = (int)($matrix[$species][$family] ?? 0);
            }
            $datasets[] = ['label' => $family, 'data' => $data];
        }

        report_render_chart_config(
            'Outcome mix by species',
            [
                'type' => 'bar',
                'stacked' => true,
                'indexAxis' => 'y',
                'showValues' => true,
                'data' => [
                    'labels' => $labels,
                    'datasets' => $datasets,
                ],
            ],
            'Top species groups split by final outcome family.'
        );
    }
}

if (!function_exists('report_outcomes_long_stay_rows')) {
    function report_outcomes_long_stay_rows(array $rows, int $threshold = 90): array {
        $long = [];
        foreach ($rows as $row) {
            $days = (int)report_value($row, ['Days in Care'], 0);
            if ($days < $threshold) {
                continue;
            }
            $long[] = [
                'title' => 'CRN #' . report_value($row, ['Admission ID'], report_value($row, ['Patient ID'], 'n/a')),
                'meta' => $days . ' days in care - ' . report_value($row, ['Disposition (Text)'], 'No disposition'),
                'tag' => '90+ days',
            ];
        }
        return $long;
    }
}

if (!function_exists('report_outcomes_completed_journey_rows')) {
    function report_outcomes_completed_journey_rows(array $rows): array {
        $hasCohort = false;
        foreach ($rows as $row) {
            if (array_key_exists('Journey Cohort', $row)) {
                $hasCohort = true;
                break;
            }
        }

        if (!$hasCohort) {
            return $rows;
        }

        return array_values(array_filter($rows, static function (array $row): bool {
            return trim((string)report_value($row, ['Journey Cohort'], '')) === 'Complete journey';
        }));
    }
}

if (!function_exists('report_outcomes_disposition_main')) {
    function report_outcomes_disposition_main(array $module, array $rows, array $context): void {
        $reportRows = report_outcomes_completed_journey_rows($rows);
        $families = [];
        $long = report_outcomes_long_stay_rows($reportRows, 90);
        $averageDays = report_average_column($reportRows, 'Days in Care');
        $longestStay = 0;

        foreach ($reportRows as $row) {
            $family = report_outcomes_family_for_row($row);
            $families[$family] = ($families[$family] ?? 0) + 1;
            $longestStay = max($longestStay, (int)report_value($row, ['Days in Care'], 0));
        }

        echo '<div class="report-summary-grid report-insight-grid report-grid-four">';
        foreach ([
            [
                'label' => 'Outcome records',
                'value' => number_format(count($reportRows)),
                'note' => 'Complete patient journeys in the report period',
            ],
            [
                'label' => 'Average care duration',
                'value' => $averageDays !== null ? $averageDays : 'n/a',
                'note' => 'Only where disposition date exists',
            ],
            [
                'label' => 'Long-stay records',
                'value' => number_format(count($long)),
                'note' => '90+ days in care',
            ],
            [
                'label' => 'Longest stay',
                'value' => number_format($longestStay),
                'note' => 'Days in care for the longest completed journey',
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
        report_chart_from_map('Outcome balance', $families, 'doughnut', 'Shows the overall outcome shape for this period.');
        report_outcomes_species_mix_chart($reportRows);
        report_chart_from_map('Average days in care by outcome', report_outcomes_average_days_by_family($reportRows), 'bar', 'Highlights outcomes associated with longer stays.', 10, 'y');
        report_chart_from_map('Disposition timing profile', report_age_buckets($reportRows), 'bar', 'Care-duration distribution for completed journeys in period.');
        echo '</div>';

        report_render_exception_list('Long-stay cases', $long);
    }
}

if (!function_exists('report_outcomes_disposition_appendix')) {
    function report_outcomes_disposition_appendix(array $module, array $rows, array $context): void {
        $reportRows = report_outcomes_completed_journey_rows($rows);

        echo '<section class="report-appendix">';
        echo '<h3>' . report_h($module['name'] ?? 'Outcomes and Dispositions') . ' supporting records</h3>';
        echo '<p class="rc-muted">Completed patient journeys used in the outcome charts and highlight cards.</p>';

        if (!$reportRows) {
            echo '<div class="rc-card">No completed patient journeys were found for this report period.</div>';
            echo '</section>';
            return;
        }

        foreach ($reportRows as $row) {
            $family = report_outcomes_family_for_row($row);
            $days = report_value($row, ['Days in Care'], '');
            $species = trim((string)report_value($row, ['Animal Species'], ''));
            $animalLabel = trim(implode(' | ', array_filter([
                $species,
                trim((string)report_value($row, ['Animal Type'], '')),
                trim((string)report_value($row, ['Animal Order'], '')),
                trim((string)report_value($row, ['Sex'], '')),
            ])));
            $chips = [
                'Animal: ' . ($animalLabel !== '' ? $animalLabel : 'n/a'),
                'Admitted: ' . report_outcomes_date(report_value($row, ['Admission Date (Start)'], '')),
                'Outcome: ' . (trim((string)report_value($row, ['Disposition (Text)'], '')) ?: 'Not recorded'),
                'Outcome date: ' . report_outcomes_date(report_value($row, ['Disposition Date (End)'], '')),
            ];
            if ($days !== '' && $days !== null) {
                $chips[] = 'Days in care: ' . (int)$days;
            }
            if (trim((string)report_value($row, ['Presenting Complaint'], '')) !== '') {
                $chips[] = 'Complaint: ' . trim((string)report_value($row, ['Presenting Complaint'], ''));
            }

            echo '<div class="rc-card">';
            echo '<div class="rc-split-head">';
            echo '<h4>CRN #' . report_h(report_value($row, ['Admission ID'], report_value($row, ['Patient ID'], 'n/a'))) . ' - ' . report_h(report_value($row, ['Patient Name'], 'Patient')) . '</h4>';
            echo '<span class="rc-badge blue">' . report_h($family) . '</span>';
            echo '</div>';
            echo '<div class="rc-chip-row">';
            foreach ($chips as $chip) {
                echo '<span class="rc-chip">' . report_h($chip) . '</span>';
            }
            echo '</div>';
            echo '</div>';
        }

        echo '</section>';
    }
}
