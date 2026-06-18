<?php
if (!defined('APP_LOADED')) exit;

if (!function_exists('report_medication_top_key')) {
    function report_medication_top_key(array $map, string $fallback = 'n/a'): string {
        if (!$map) {
            return $fallback;
        }
        arsort($map);
        $key = array_key_first($map);
        return $key !== null && $key !== '' ? (string)$key : $fallback;
    }
}

if (!function_exists('report_medication_month_label')) {
    function report_medication_month_label(string $month): string {
        if ($month === '') {
            return 'Not recorded';
        }
        $time = strtotime($month . '-01');
        return $time ? date('M Y', $time) : $month;
    }
}

if (!function_exists('report_medication_intensive_admissions')) {
    function report_medication_intensive_admissions(array $rows): array {
        $counts = [];
        foreach ($rows as $row) {
            $medication = trim((string)report_value($row, ['Medication'], 'No medication'));
            if ($medication === '' || $medication === 'No medication') {
                continue;
            }

            $admissionId = trim((string)report_value($row, ['Admission ID'], ''));
            $patientId = trim((string)report_value($row, ['Patient ID'], ''));
            $species = trim((string)report_value($row, ['Animal Species'], 'Unknown species'));
            $name = trim((string)report_value($row, ['Patient Name'], 'Patient'));

            if ($admissionId !== '') {
                $label = 'CRN #' . $admissionId . ' - ' . $species;
            } elseif ($patientId !== '') {
                $label = 'Patient #' . $patientId . ' - ' . $species;
            } else {
                $label = $name . ' - ' . $species;
            }

            $counts[$label] = ($counts[$label] ?? 0) + 1;
        }
        arsort($counts);
        return $counts;
    }
}

if (!function_exists('report_medication_stacked_counts')) {
    function report_medication_stacked_counts(array $rows, array $labelKeys, array $stackKeys, int $labelLimit = 6, int $stackLimit = 5): array {
        $labelTotals = [];
        $stackTotals = [];
        $matrix = [];

        foreach ($rows as $row) {
            $label = trim((string)report_value($row, $labelKeys, 'Not recorded'));
            $stack = trim((string)report_value($row, $stackKeys, 'Unclassified'));
            if ($label === '') {
                $label = 'Not recorded';
            }
            if ($stack === '') {
                $stack = 'Unclassified';
            }

            $labelTotals[$label] = ($labelTotals[$label] ?? 0) + 1;
            $stackTotals[$stack] = ($stackTotals[$stack] ?? 0) + 1;
            $matrix[$label][$stack] = ($matrix[$label][$stack] ?? 0) + 1;
        }

        arsort($labelTotals);
        arsort($stackTotals);
        $labels = array_slice(array_keys($labelTotals), 0, $labelLimit);
        $stacks = array_slice(array_keys($stackTotals), 0, $stackLimit);
        $datasets = [];

        foreach ($stacks as $stack) {
            $data = [];
            foreach ($labels as $label) {
                $data[] = (int)($matrix[$label][$stack] ?? 0);
            }
            $datasets[] = ['label' => $stack, 'data' => $data];
        }

        return ['labels' => $labels, 'datasets' => $datasets];
    }
}

if (!function_exists('report_medication_patient_level_stacked_counts')) {
    function report_medication_patient_level_stacked_counts(array $rows, array $labelKeys, int $labelLimit = 6, int $stackLimit = 5): array {
        $groups = [];

        foreach ($rows as $row) {
            $admissionId = trim((string)report_value($row, ['Admission ID'], ''));
            $patientId = trim((string)report_value($row, ['Patient ID'], ''));
            $key = $admissionId !== '' ? 'a:' . $admissionId : 'p:' . $patientId;
            if ($key === 'p:') {
                continue;
            }

            $label = trim((string)report_value($row, $labelKeys, 'Not recorded'));
            if ($label === '') {
                $label = 'Not recorded';
            }

            $class = trim((string)report_value($row, ['Medication Class'], 'Unclassified'));
            if ($class === '') {
                $class = 'Unclassified';
            }

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'label' => $label,
                    'classes' => [],
                ];
            }

            $groups[$key]['label'] = $label;
            $groups[$key]['classes'][$class] = ($groups[$key]['classes'][$class] ?? 0) + 1;
        }

        $labelTotals = [];
        $classTotals = [];
        $matrix = [];

        foreach ($groups as $group) {
            arsort($group['classes']);
            $dominantClass = array_key_first($group['classes']) ?: 'Unclassified';
            $label = $group['label'];

            $labelTotals[$label] = ($labelTotals[$label] ?? 0) + 1;
            $classTotals[$dominantClass] = ($classTotals[$dominantClass] ?? 0) + 1;
            $matrix[$label][$dominantClass] = ($matrix[$label][$dominantClass] ?? 0) + 1;
        }

        arsort($labelTotals);
        arsort($classTotals);
        $labels = array_slice(array_keys($labelTotals), 0, $labelLimit);
        $classes = array_slice(array_keys($classTotals), 0, $stackLimit);
        $datasets = [];

        foreach ($classes as $class) {
            $data = [];
            foreach ($labels as $label) {
                $data[] = (int)($matrix[$label][$class] ?? 0);
            }
            $datasets[] = ['label' => $class, 'data' => $data];
        }

        return ['labels' => $labels, 'datasets' => $datasets];
    }
}

if (!function_exists('report_medication_primary_exposure_stacked_counts')) {
    function report_medication_primary_exposure_stacked_counts(array $rows, array $labelKeys, int $labelLimit = 8, int $medicationLimit = 0): array {
        $groups = [];
        $medTotals = [];

        foreach ($rows as $row) {
            $admissionId = trim((string)report_value($row, ['Admission ID'], ''));
            $patientId = trim((string)report_value($row, ['Patient ID'], ''));
            $key = $admissionId !== '' ? 'a:' . $admissionId : 'p:' . $patientId;
            if ($key === 'p:') {
                continue;
            }

            $label = trim((string)report_value($row, $labelKeys, 'Not recorded'));
            if ($label === '') {
                $label = 'Not recorded';
            }

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'label' => $label,
                    'medications' => [],
                ];
            }
            $groups[$key]['label'] = $label;

            $medication = trim((string)report_value($row, ['Medication'], 'No medication'));
            if ($medication === '' || $medication === 'No medication') {
                continue;
            }

            $groups[$key]['medications'][$medication] = ($groups[$key]['medications'][$medication] ?? 0) + 1;
            $medTotals[$medication] = ($medTotals[$medication] ?? 0) + 1;
        }

        arsort($medTotals);
        $topMedications = array_keys($medTotals);
        if ($medicationLimit > 0) {
            $topMedications = array_slice($topMedications, 0, $medicationLimit);
        }
        $segments = array_merge($topMedications, ['No medication']);
        $labelTotals = [];
        $matrix = [];

        foreach ($groups as $group) {
            $label = $group['label'];
            $labelTotals[$label] = ($labelTotals[$label] ?? 0) + 1;
        }

        arsort($labelTotals);
        $labels = array_slice(array_keys($labelTotals), 0, $labelLimit);
        foreach ($segments as $segment) {
            $matrix[$segment] = array_fill_keys($labels, 0);
        }

        foreach ($groups as $group) {
            $label = $group['label'];
            if (!in_array($label, $labels, true)) {
                continue;
            }

            if (empty($group['medications'])) {
                $matrix['No medication'][$label]++;
                continue;
            }

            foreach (array_keys($group['medications']) as $medication) {
                if (!in_array($medication, $topMedications, true)) {
                    continue;
                }
                $matrix[$medication][$label]++;
            }
        }

        $datasets = [];
        foreach ($segments as $segment) {
            $datasets[] = [
                'label' => report_medication_short_medication_label($segment),
                'data' => array_values($matrix[$segment]),
            ];
        }

        return ['labels' => $labels, 'datasets' => report_medication_style_exposure_datasets($datasets)];
    }
}

if (!function_exists('report_medication_disposition_label')) {
    function report_medication_disposition_label($value): ?string {
        $raw = strtolower(trim((string)$value));
        $raw = preg_replace('/\s+/', ' ', $raw);
        if ($raw === '') {
            return null;
        }

        $map = [
            'released' => 'Released',
            'r' => 'Released',
            'died - within 48 hours' => 'Died - within 48 hours',
            'died within 48 hours' => 'Died - within 48 hours',
            'died - on admission' => 'Died - on admission',
            'died on admission' => 'Died - on admission',
            'dead on admission' => 'Died - on admission',
            'doa' => 'Died - on admission',
            'died - euthanised' => 'Died - Euthanised',
            'died euthanised' => 'Died - Euthanised',
            'euthanised' => 'Died - Euthanised',
            'euthanized' => 'Died - Euthanised',
            'died after 48 hours' => 'Died after 48 hours',
            'died - after 48 hours' => 'Died after 48 hours',
            'transferred to another rescue' => 'Transferred to another rescue',
            'transferred to another rescue centre' => 'Transferred to another rescue',
            'transferred out' => 'Transferred to another rescue',
            'transferred' => 'Transferred to another rescue',
        ];

        return $map[$raw] ?? null;
    }
}

if (!function_exists('report_medication_disposition_short_label')) {
    function report_medication_disposition_short_label(string $label): string {
        $map = [
            'Released' => 'Released',
            'Died - within 48 hours' => 'Died <48h',
            'Died - on admission' => 'DOA',
            'Died - Euthanised' => 'Euthanised',
            'Died after 48 hours' => 'Died >48h',
            'Transferred to another rescue' => 'Transferred',
        ];
        return $map[$label] ?? $label;
    }
}

if (!function_exists('report_medication_short_medication_label')) {
    function report_medication_short_medication_label(string $label): string {
        $label = trim($label);
        if (strlen($label) <= 18 || $label === 'No medication') {
            return $label;
        }
        return substr($label, 0, 15) . '...';
    }
}

if (!function_exists('report_medication_chart_palette')) {
    function report_medication_chart_palette(): array {
        return ['#e11d48', '#f59e0b', '#10b981', '#8b5cf6', '#06b6d4', '#f97316', '#84cc16', '#ec4899'];
    }
}

if (!function_exists('report_medication_style_exposure_datasets')) {
    function report_medication_style_exposure_datasets(array $datasets): array {
        usort($datasets, static function (array $a, array $b): int {
            $aNoMed = (string)($a['label'] ?? '') === 'No medication';
            $bNoMed = (string)($b['label'] ?? '') === 'No medication';
            if ($aNoMed === $bNoMed) {
                return 0;
            }
            return $aNoMed ? 1 : -1;
        });

        $palette = report_medication_chart_palette();
        $colourIndex = 0;
        foreach ($datasets as $index => $dataset) {
            $isNoMedication = (string)($dataset['label'] ?? '') === 'No medication';
            if ($isNoMedication) {
                $datasets[$index]['backgroundColor'] = 'rgba(148, 163, 184, .28)';
                $datasets[$index]['borderColor'] = '#94a3b8';
                $datasets[$index]['borderWidth'] = 1;
                continue;
            }

            $colour = $palette[$colourIndex % count($palette)];
            $datasets[$index]['backgroundColor'] = $colour;
            $datasets[$index]['borderColor'] = $colour;
            $datasets[$index]['borderWidth'] = 1;
            $colourIndex++;
        }

        return $datasets;
    }
}

if (!function_exists('report_medication_disposition_exposure_chart')) {
    function report_medication_disposition_exposure_chart(array $rows, int $medicationLimit = 0): void {
        $dispositions = [
            'Released',
            'Died - within 48 hours',
            'Died - on admission',
            'Died - Euthanised',
            'Died after 48 hours',
            'Transferred to another rescue',
        ];
        $admissions = [];
        $medTotals = [];

        foreach ($rows as $row) {
            $disposition = report_medication_disposition_label(report_value($row, ['Disposition'], ''));
            if ($disposition === null) {
                continue;
            }

            $admissionId = trim((string)report_value($row, ['Admission ID'], ''));
            $patientId = trim((string)report_value($row, ['Patient ID'], ''));
            $key = $admissionId !== '' ? 'a:' . $admissionId : 'p:' . $patientId;
            if ($key === 'p:') {
                continue;
            }

            if (!isset($admissions[$key])) {
                $admissions[$key] = [
                    'disposition' => $disposition,
                    'medications' => [],
                ];
            }

            $admissions[$key]['disposition'] = $disposition;
            $medication = trim((string)report_value($row, ['Medication'], 'No medication'));
            if ($medication === '' || $medication === 'No medication') {
                continue;
            }

            $admissions[$key]['medications'][$medication] = ($admissions[$key]['medications'][$medication] ?? 0) + 1;
            $medTotals[$medication] = ($medTotals[$medication] ?? 0) + 1;
        }

        arsort($medTotals);
        $topMedications = array_keys($medTotals);
        if ($medicationLimit > 0) {
            $topMedications = array_slice($topMedications, 0, $medicationLimit);
        }
        $segments = array_merge($topMedications, ['No medication']);
        $displayLabels = array_map('report_medication_disposition_short_label', $dispositions);
        $matrix = [];
        foreach ($segments as $segment) {
            $matrix[$segment] = array_fill_keys($dispositions, 0);
        }

        foreach ($admissions as $admission) {
            $disposition = $admission['disposition'];
            if (empty($admission['medications'])) {
                $matrix['No medication'][$disposition]++;
                continue;
            }

            foreach (array_keys($admission['medications']) as $medication) {
                if (!in_array($medication, $topMedications, true)) {
                    continue;
                }
                $matrix[$medication][$disposition]++;
            }
        }

        $medicationPalette = report_medication_chart_palette();
        $datasets = [];
        foreach ($segments as $segmentIndex => $segment) {
            $isNoMedication = $segment === 'No medication';
            $background = $isNoMedication
                ? 'rgba(148, 163, 184, .28)'
                : array_fill(0, count($dispositions), $medicationPalette[$segmentIndex % count($medicationPalette)]);

            $datasets[] = [
                'label' => report_medication_short_medication_label($segment),
                'data' => array_values($matrix[$segment]),
                'backgroundColor' => $background,
                'borderColor' => $isNoMedication ? '#94a3b8' : $medicationPalette[$segmentIndex % count($medicationPalette)],
                'borderWidth' => 1,
            ];
        }

        report_render_chart_config(
            'Disposition by medication exposure',
            [
                'type' => 'bar',
                'stacked' => true,
                'indexAxis' => 'y',
                'showValues' => true,
                'data' => [
                    'labels' => $displayLabels,
                    'datasets' => $datasets,
                ],
            ],
            'Final outcomes by medication exposure. Journeys with multiple medications appear in each relevant medication layer.'
        );
    }
}

if (!function_exists('report_medication_completed_journey_rows')) {
    function report_medication_completed_journey_rows(array $rows): array {
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

if (!function_exists('report_medication_render_intensive_admissions_chart')) {
    function report_medication_render_intensive_admissions_chart(array $counts, int $limit = 12): void {
        arsort($counts);
        $counts = array_slice($counts, 0, $limit, true);
        if (!$counts) {
            return;
        }

        $labels = [];
        foreach (array_keys($counts) as $label) {
            $parts = explode(' - ', (string)$label, 2);
            $labels[] = count($parts) === 2 ? [$parts[0], $parts[1]] : [(string)$label];
        }

        report_render_chart_config(
            'Most medication-intensive admissions',
            [
                'type' => 'bar',
                'indexAxis' => 'y',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => 'Medication administrations',
                        'data' => array_values($counts),
                    ]],
                ],
            ],
            'Uses CRN/admission ID and species so high-input cases can be reviewed.'
        );
    }
}

if (!function_exists('report_medication_log_main')) {
    function report_medication_log_main(array $module, array $rows, array $context): void {
        $reportRows = report_medication_completed_journey_rows($rows);
        $patients = [];
        $medications = [];
        $classes = [];
        $months = [];
        $species = [];
        $expiry = [];
        $review = [];

        foreach ($reportRows as $row) {
            $patientId = (string)report_value($row, ['Patient ID'], '');
            if ($patientId !== '') {
                $patients[$patientId] = true;
            }

            $medication = (string)report_value($row, ['Medication'], 'Unknown medication');
            $class = (string)report_value($row, ['Medication Class'], 'Unclassified');
            $animalSpecies = (string)report_value($row, ['Animal Species'], 'Unknown species');
            $month = (string)report_value($row, ['Medication Month'], '');

            if ($medication !== 'No medication') {
                $medications[$medication] = ($medications[$medication] ?? 0) + 1;
                $classes[$class] = ($classes[$class] ?? 0) + 1;
            }
            if ($medication !== 'No medication') {
                $species[$animalSpecies] = ($species[$animalSpecies] ?? 0) + 1;
            }
            if ($month !== '' && $medication !== 'No medication') {
                $months[$month] = ($months[$month] ?? 0) + 1;
            }

            $expiryDate = report_value($row, ['Expiry Date'], '');
            if ($expiryDate && strtotime((string)$expiryDate) && strtotime((string)$expiryDate) < time()) {
                $expiry[] = [
                    'title' => $medication . ' - ' . report_value($row, ['Patient Name'], 'Patient'),
                    'meta' => 'Batch ' . report_value($row, ['Batch'], 'n/a') . ' - expired ' . $expiryDate,
                    'tag' => 'Expiry',
                ];
            }

            if (trim((string)report_value($row, ['Batch'], '')) === '') {
                $review[] = [
                    'title' => $medication . ' - ' . report_value($row, ['Patient Name'], 'Patient'),
                    'meta' => 'No batch recorded for CRN #' . report_value($row, ['Admission ID'], 'n/a'),
                    'tag' => 'Batch',
                ];
            }
        }

        arsort($months);
        $peakMonth = array_key_first($months);
        $intensiveAdmissions = report_medication_intensive_admissions($reportRows);
        $mostIntensive = report_medication_top_key($intensiveAdmissions);

        echo '<div class="report-summary-grid report-insight-grid report-grid-four">';
        foreach ([
            [
                'label' => 'Most used medication',
                'value' => report_medication_top_key($medications),
                'note' => number_format((int)($medications[report_medication_top_key($medications)] ?? 0)) . ' administrations',
            ],
            [
                'label' => 'Most used class',
                'value' => report_medication_top_key($classes),
                'note' => 'Based on medication stock class where available',
            ],
            [
                'label' => 'Peak medication month',
                'value' => report_medication_month_label((string)$peakMonth),
                'note' => $peakMonth ? number_format((int)$months[$peakMonth]) . ' administrations' : 'No dated records',
            ],
            [
                'label' => 'Most intensive admission',
                'value' => $mostIntensive,
                'note' => number_format((int)($intensiveAdmissions[$mostIntensive] ?? 0)) . ' administrations',
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
        report_chart_from_map(
            'Top medications by use',
            $medications,
            'bar',
            'Shows which medicines drive the greatest administration workload.',
            10,
            'y'
        );
        report_chart_from_map(
            'Medication use by species',
            $species,
            'bar',
            'Top species groups by medication administration count.',
            5,
            'y'
        );

        $complaintStack = report_medication_primary_exposure_stacked_counts($reportRows, ['Presenting Complaint'], 8);
        report_render_chart_config(
            'Medication exposure by presenting complaint',
            ['type' => 'bar', 'stacked' => true, 'indexAxis' => 'y', 'showValues' => true, 'data' => $complaintStack],
            'Completed patient journeys only, grouped by presenting complaint and medication exposure.'
        );
        report_medication_disposition_exposure_chart($reportRows);
        report_medication_render_intensive_admissions_chart($intensiveAdmissions);
        echo '</div>';

    }
}

if (!function_exists('report_medication_group_appendix_rows')) {
    function report_medication_group_appendix_rows(array $rows): array {
        $groups = [];

        foreach ($rows as $row) {
            $admissionId = trim((string)report_value($row, ['Admission ID'], ''));
            $patientId = trim((string)report_value($row, ['Patient ID'], ''));
            $key = $admissionId !== '' ? 'a:' . $admissionId : 'p:' . $patientId;
            if ($key === 'p:') {
                continue;
            }

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'patient_id' => $patientId,
                    'admission_id' => $admissionId,
                    'patient_name' => report_value($row, ['Patient Name'], 'Patient'),
                    'animal_type' => report_value($row, ['Animal Type'], ''),
                    'animal_order' => report_value($row, ['Animal Order'], ''),
                    'animal_species' => report_value($row, ['Animal Species'], ''),
                    'admission_date' => report_value($row, ['Admission Date'], ''),
                    'disposition' => report_value($row, ['Disposition'], ''),
                    'disposition_date' => report_value($row, ['Disposition Date'], ''),
                    'presenting_complaint' => report_value($row, ['Presenting Complaint'], ''),
                    'journey_cohort' => report_value($row, ['Journey Cohort'], ''),
                    'medications' => [],
                ];
            }

            $medication = trim((string)report_value($row, ['Medication'], 'No medication'));
            if ($medication === '' || $medication === 'No medication') {
                continue;
            }

            $dose = trim((string)report_value($row, ['Dose'], ''));
            $doseUnit = trim((string)report_value($row, ['Dose Unit'], ''));
            $volume = trim((string)report_value($row, ['Volume Given'], ''));
            $class = trim((string)report_value($row, ['Medication Class'], ''));
            $medKey = strtolower($medication . '|' . $dose . '|' . $doseUnit . '|' . $volume . '|' . $class);

            if (!isset($groups[$key]['medications'][$medKey])) {
                $groups[$key]['medications'][$medKey] = [
                    'medication' => $medication,
                    'class' => $class !== '' ? $class : 'Unclassified',
                    'dose' => trim($dose . ' ' . $doseUnit),
                    'volume' => $volume,
                    'count' => 0,
                    'first_date' => report_value($row, ['Date Given'], ''),
                    'last_date' => report_value($row, ['Date Given'], ''),
                ];
            }

            $groups[$key]['medications'][$medKey]['count']++;
            $dateGiven = (string)report_value($row, ['Date Given'], '');
            if ($dateGiven !== '') {
                $firstDate = (string)$groups[$key]['medications'][$medKey]['first_date'];
                $lastDate = (string)$groups[$key]['medications'][$medKey]['last_date'];
                if ($firstDate === '' || strtotime($dateGiven) < strtotime($firstDate)) {
                    $groups[$key]['medications'][$medKey]['first_date'] = $dateGiven;
                }
                if ($lastDate === '' || strtotime($dateGiven) > strtotime($lastDate)) {
                    $groups[$key]['medications'][$medKey]['last_date'] = $dateGiven;
                }
            }
        }

        return $groups;
    }
}

if (!function_exists('report_medication_appendix_date')) {
    function report_medication_appendix_date($value): string {
        $value = trim((string)$value);
        if ($value === '') {
            return 'n/a';
        }
        $time = strtotime($value);
        return $time ? date('d M Y', $time) : $value;
    }
}

if (!function_exists('report_medication_log_appendix')) {
    function report_medication_log_appendix(array $module, array $rows, array $context): void {
        $groups = report_medication_group_appendix_rows($rows);

        echo '<section class="report-appendix">';
        echo '<h3>' . report_h($module['name'] ?? 'Medication Log') . ' supporting records</h3>';
        echo '<p class="rc-muted">Patient-level medication summaries used to support the report section.</p>';

        if (!$groups) {
            echo '<div class="rc-card report-block">No medication supporting records were found for this report period.</div>';
            echo '</section>';
            return;
        }

        foreach ($groups as $group) {
            $species = trim((string)$group['animal_species']);
            $animalLabel = trim(implode(' | ', array_filter([
                $species,
                trim((string)$group['animal_type']),
                trim((string)$group['animal_order']),
            ])));
            $metaChips = [
                'Animal: ' . ($animalLabel !== '' ? $animalLabel : 'n/a'),
                'Admitted: ' . report_medication_appendix_date($group['admission_date']),
            ];
            if (trim((string)$group['presenting_complaint']) !== '') {
                $metaChips[] = 'Complaint: ' . trim((string)$group['presenting_complaint']);
            }
            if (trim((string)$group['disposition']) !== '') {
                $metaChips[] = 'Outcome: ' . trim((string)$group['disposition']) . ' ' . report_medication_appendix_date($group['disposition_date']);
            }
            if (trim((string)$group['journey_cohort']) !== '') {
                $metaChips[] = 'Cohort: ' . trim((string)$group['journey_cohort']);
            }

            echo '<div class="rc-card">';
            echo '<div class="rc-split-head">';
            echo '<h4>CRN #' . report_h($group['admission_id'] ?: $group['patient_id']) . ' - ' . report_h($group['patient_name']) . '</h4>';
            if ($group['medications']) {
                echo '<span class="rc-badge blue">' . number_format(count($group['medications'])) . ' medication' . (count($group['medications']) === 1 ? '' : 's') . '</span>';
            } else {
                echo '<span class="rc-badge na">No medication</span>';
            }
            echo '</div>';
            echo '<div class="rc-chip-row">';
            foreach ($metaChips as $chip) {
                echo '<span class="rc-chip">' . report_h($chip) . '</span>';
            }
            echo '</div>';

            if (!$group['medications']) {
                echo '</div>';
                continue;
            }

            echo '<div class="rc-table-scroll">';
            echo '<table class="rc-table">';
            echo '<thead><tr><th>Medication</th><th>Class</th><th>Dose strength</th><th>Volume</th><th># doses</th><th>Date range</th></tr></thead>';
            echo '<tbody>';
            foreach ($group['medications'] as $medication) {
                $dateRange = report_medication_appendix_date($medication['first_date']);
                if ($medication['last_date'] !== $medication['first_date']) {
                    $dateRange .= ' - ' . report_medication_appendix_date($medication['last_date']);
                }
                echo '<tr>';
                echo '<td>' . report_h($medication['medication']) . '</td>';
                echo '<td>' . report_h($medication['class']) . '</td>';
                echo '<td>' . report_h($medication['dose'] !== '' ? $medication['dose'] : 'n/a') . '</td>';
                echo '<td>' . report_h($medication['volume'] !== '' ? $medication['volume'] : 'n/a') . '</td>';
                echo '<td>' . number_format((int)$medication['count']) . '</td>';
                echo '<td>' . report_h($dateRange) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
            echo '</div>';
        }

        echo '</section>';
    }
}
