<?php
if (!defined('APP_LOADED')) exit;

if (!function_exists('report_treatment_care_date')) {
    function report_treatment_care_date($value): string {
        $value = trim((string)$value);
        if ($value === '') {
            return 'n/a';
        }
        $time = strtotime($value);
        return $time ? date('d M Y H:i', $time) : $value;
    }
}

if (!function_exists('report_treatment_care_patient_groups')) {
    function report_treatment_care_patient_groups(array $rows): array {
        $groups = [];

        foreach ($rows as $row) {
            $patientId = trim((string)report_value($row, ['Patient ID'], ''));
            if ($patientId === '') {
                continue;
            }

            if (!isset($groups[$patientId])) {
                $groups[$patientId] = [
                    'patient_id' => $patientId,
                    'patient_name' => report_value($row, ['Patient Name'], 'Patient'),
                    'animal_type' => report_value($row, ['Animal Type'], ''),
                    'animal_order' => report_value($row, ['Animal Order'], ''),
                    'animal_species' => report_value($row, ['Animal Species'], ''),
                    'events' => [],
                ];
            }

            $groups[$patientId]['events'][] = [
                'date' => report_value($row, ['Event Date'], ''),
                'type' => report_value($row, ['Event Type'], 'Event'),
                'summary' => report_value($row, ['Summary'], ''),
                'details' => report_value($row, ['Details'], ''),
                'recorded_by' => report_value($row, ['Recorded By'], 'Not recorded'),
                'public' => report_value($row, ['Public'], ''),
                'image_id' => report_value($row, ['Image ID'], ''),
            ];
        }

        uasort($groups, static function (array $a, array $b): int {
            return count($b['events']) <=> count($a['events']);
        });

        return $groups;
    }
}

if (!function_exists('report_treatment_care_type_by_species_chart')) {
    function report_treatment_care_type_by_species_chart(array $rows, int $limit = 8): void {
        $speciesTotals = [];
        $matrix = [];
        $types = [];

        foreach ($rows as $row) {
            $species = trim((string)report_value($row, ['Animal Species'], 'Unknown species'));
            if ($species === '') {
                $species = 'Unknown species';
            }
            $type = trim((string)report_value($row, ['Event Type'], 'Event'));
            if ($type === '') {
                $type = 'Event';
            }
            $speciesTotals[$species] = ($speciesTotals[$species] ?? 0) + 1;
            $matrix[$species][$type] = ($matrix[$species][$type] ?? 0) + 1;
            $types[$type] = true;
        }

        arsort($speciesTotals);
        $labels = array_slice(array_keys($speciesTotals), 0, $limit);
        $datasets = [];
        foreach (array_keys($types) as $type) {
            $data = [];
            foreach ($labels as $species) {
                $data[] = (int)($matrix[$species][$type] ?? 0);
            }
            $datasets[] = ['label' => $type, 'data' => $data];
        }

        report_render_chart_config(
            'Care activity by species',
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
            'Treatment and care-note activity by species group.'
        );
    }
}

if (!function_exists('report_treatment_care_main')) {
    function report_treatment_care_main(array $module, array $rows, array $context): void {
        $patients = report_count_by($rows, ['Patient ID']);
        $eventTypes = report_count_by($rows, ['Event Type']);
        $staff = report_count_by($rows, ['Recorded By']);
        $eventByWeek = report_time_series($rows, ['Event Date'], 'week');
        $byPatient = report_patient_intensity($rows);
        $mostIntensive = array_key_first($byPatient) ?: 'n/a';
        $topRecorder = array_key_first(report_top_map($staff, 1)) ?: 'n/a';

        echo '<div class="report-summary-grid report-insight-grid report-grid-four">';
        foreach ([
            [
                'label' => 'Care events',
                'value' => number_format(count($rows)),
                'note' => 'Treatments and care notes in period',
            ],
            [
                'label' => 'Patients covered',
                'value' => number_format(count($patients)),
                'note' => 'Patients with recorded care activity',
            ],
            [
                'label' => 'Care intensity',
                'value' => count($patients) ? round(count($rows) / max(1, count($patients)), 1) : 'n/a',
                'note' => 'Events per patient with activity',
            ],
            [
                'label' => 'Most active recorder',
                'value' => $topRecorder,
                'note' => 'Highest number of entries',
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
        report_chart_from_map('Care event types', $eventTypes, 'doughnut', 'Split of treatments and care notes.');
        report_chart_from_map('Care activity over time', $eventByWeek, 'line', 'Shows treatment/care pressure through the period.');
        report_treatment_care_type_by_species_chart($rows);
        report_chart_from_map('Highest care-load patients', $byPatient, 'bar', 'Identifies cases consuming the most care recording.', 12, 'y');
        report_chart_from_map('Recording workload by person', $staff, 'bar', 'Shows recording distribution across the team.', 12, 'y');
        echo '</div>';
    }
}

if (!function_exists('report_treatment_care_appendix')) {
    function report_treatment_care_appendix(array $module, array $rows, array $context): void {
        $groups = report_treatment_care_patient_groups($rows);

        echo '<section class="report-appendix">';
        echo '<h3>' . report_h($module['name'] ?? 'Treatment and Care Log') . ' supporting records</h3>';
        echo '<p class="rc-muted">Patient-level treatment and care-note activity used in the charts and highlight cards.</p>';

        if (!$groups) {
            echo '<div class="rc-card">No treatment or care-note records were found for this report period.</div>';
            echo '</section>';
            return;
        }

        foreach ($groups as $group) {
            $animalLabel = trim(implode(' | ', array_filter([
                trim((string)$group['animal_species']),
                trim((string)$group['animal_type']),
                trim((string)$group['animal_order']),
            ])));

            echo '<div class="rc-card">';
            echo '<div class="rc-split-head">';
            echo '<h4>CRN #' . report_h($group['patient_id']) . ' - ' . report_h($group['patient_name']) . '</h4>';
            echo '<span class="rc-badge blue">' . number_format(count($group['events'])) . ' event' . (count($group['events']) === 1 ? '' : 's') . '</span>';
            echo '</div>';
            echo '<div class="rc-chip-row">';
            echo '<span class="rc-chip">Animal: ' . report_h($animalLabel !== '' ? $animalLabel : 'n/a') . '</span>';
            echo '</div>';

            echo '<div class="rc-table-scroll">';
            echo '<table class="rc-table">';
            echo '<thead><tr><th>Date</th><th>Type</th><th>Summary</th><th>Details</th><th>Recorded by</th></tr></thead>';
            echo '<tbody>';
            foreach ($group['events'] as $event) {
                echo '<tr>';
                echo '<td>' . report_h(report_treatment_care_date($event['date'])) . '</td>';
                echo '<td>' . report_h($event['type']) . '</td>';
                echo '<td>' . report_h($event['summary'] !== '' ? $event['summary'] : 'n/a') . '</td>';
                echo '<td>' . report_h($event['details'] !== '' ? $event['details'] : 'n/a') . '</td>';
                echo '<td>' . report_h($event['recorded_by'] !== '' ? $event['recorded_by'] : 'Not recorded') . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
            echo '</div>';
        }

        echo '</section>';
    }
}
