<?php
if (!defined('APP_LOADED')) exit;

if (!function_exists('report_notifiable_date')) {
    function report_notifiable_date($value): string {
        $value = trim((string)$value);
        if ($value === '') {
            return 'n/a';
        }
        $time = strtotime($value);
        return $time ? date('d M Y', $time) : $value;
    }
}

if (!function_exists('report_notifiable_priority')) {
    function report_notifiable_priority(string $category, string $sourceType = ''): string {
        if ($sourceType === 'Positive Lab Result' || $sourceType === 'Mass Casualty Incident') {
            return 'High priority';
        }

        if (stripos($category, 'Other') !== false) {
            return 'Low confidence';
        }

        foreach (['Poison', 'Shooting', 'Cruelty', 'Abuse', 'Disease'] as $term) {
            if (stripos($category, $term) !== false) {
                return 'High priority';
            }
        }

        return 'Review';
    }
}

if (!function_exists('report_notifiable_positive_lab_breakdown')) {
    function report_notifiable_positive_lab_breakdown(array $rows): array {
        $labs = [];
        foreach ($rows as $row) {
            if ((string)report_value($row, ['Source Type'], '') !== 'Positive Lab Result') {
                continue;
            }
            $test = trim((string)report_value($row, ['Lab Test'], 'Unknown test'));
            if ($test === '') {
                $test = 'Unknown test';
            }
            $labs[$test] = ($labs[$test] ?? 0) + 1;
        }
        arsort($labs);
        return $labs;
    }
}

if (!function_exists('report_notifiable_mass_casualty_totals')) {
    function report_notifiable_mass_casualty_totals(array $rows): array {
        $totals = [];
        foreach ($rows as $row) {
            if ((string)report_value($row, ['Source Type'], '') !== 'Mass Casualty Incident') {
                continue;
            }
            $sourceId = trim((string)report_value($row, ['Source ID'], ''));
            $label = 'Incident #' . ($sourceId !== '' ? $sourceId : 'n/a');
            $incidentTotal = (int)report_value($row, ['Incident Reportable Casualties'], 0);
            $totals[$label] = max((int)($totals[$label] ?? 0), $incidentTotal);
        }
        arsort($totals);
        return $totals;
    }
}

if (!function_exists('report_notifiable_patient_journey_keys')) {
    function report_notifiable_patient_journey_keys(array $rows): array {
        $journeys = [];
        foreach ($rows as $row) {
            if ((string)report_value($row, ['Source Type'], '') === 'Mass Casualty Incident') {
                continue;
            }
            $admissionId = trim((string)report_value($row, ['Admission ID'], ''));
            $patientId = trim((string)report_value($row, ['Patient ID'], ''));
            if ($admissionId !== '') {
                $journeys['a:' . $admissionId] = true;
            } elseif ($patientId !== '') {
                $journeys['p:' . $patientId] = true;
            }
        }
        return $journeys;
    }
}

if (!function_exists('report_notifiable_species_counts')) {
    function report_notifiable_species_counts(array $rows): array {
        $counts = [];
        $incidents = [];

        foreach ($rows as $row) {
            $source = (string)report_value($row, ['Source Type'], '');
            $species = trim((string)report_value($row, ['Animal Species'], ''));

            if ($source !== 'Mass Casualty Incident') {
                if ($species !== '') {
                    $counts[$species] = ($counts[$species] ?? 0) + 1;
                }
                continue;
            }

            $incidentId = trim((string)report_value($row, ['Source ID'], ''));
            if ($incidentId === '') {
                $incidentId = 'unknown';
            }
            if (!isset($incidents[$incidentId])) {
                $incidents[$incidentId] = [
                    'total' => 0,
                    'doa' => 0,
                    'linked_keys' => [],
                    'species_counts' => [],
                ];
            }

            $incidents[$incidentId]['total'] = max($incidents[$incidentId]['total'], (int)report_value($row, ['Incident Reportable Casualties'], 0));
            $incidents[$incidentId]['doa'] = max($incidents[$incidentId]['doa'], (int)report_value($row, ['Incident DOA'], 0));

            $admissionId = trim((string)report_value($row, ['Admission ID'], ''));
            $patientId = trim((string)report_value($row, ['Patient ID'], ''));
            if ($admissionId !== '' || $patientId !== '') {
                $linkedKey = $admissionId !== '' ? 'a:' . $admissionId : 'p:' . $patientId;
                if (isset($incidents[$incidentId]['linked_keys'][$linkedKey])) {
                    continue;
                }
                $incidents[$incidentId]['linked_keys'][$linkedKey] = true;
                $label = $species !== '' ? $species : 'Unknown linked casualty';
                $incidents[$incidentId]['species_counts'][$label] = ($incidents[$incidentId]['species_counts'][$label] ?? 0) + 1;
            }
        }

        foreach ($incidents as $incident) {
            foreach ($incident['species_counts'] as $species => $count) {
                $counts[$species] = ($counts[$species] ?? 0) + (int)$count;
            }

            $linkedCount = count($incident['linked_keys']);
            $missing = max(0, $incident['total'] - $linkedCount);
            $unknownDoa = min($incident['doa'], $missing);
            $unknownOther = $missing - $unknownDoa;

            if ($unknownDoa > 0) {
                $counts['Unknown DOA'] = ($counts['Unknown DOA'] ?? 0) + $unknownDoa;
            }
            if ($unknownOther > 0) {
                $counts['Unknown / unlinked casualty'] = ($counts['Unknown / unlinked casualty'] ?? 0) + $unknownOther;
            }
        }

        arsort($counts);
        return $counts;
    }
}

if (!function_exists('report_notifiable_category_species_chart')) {
    function report_notifiable_category_species_chart(array $rows, int $limit = 8): void {
        $categoryTotals = [];
        $matrix = [];
        $speciesTotals = [];
        $incidents = [];

        foreach ($rows as $row) {
            $category = trim((string)report_value($row, ['Notifiable Category (Derived)'], 'Other / Review Required'));
            $source = (string)report_value($row, ['Source Type'], '');
            $species = trim((string)report_value($row, ['Animal Species'], ''));
            if ($category === '') {
                $category = 'Other / Review Required';
            }

            if ($source === 'Mass Casualty Incident') {
                $incidentId = trim((string)report_value($row, ['Source ID'], ''));
                if ($incidentId === '') {
                    $incidentId = 'unknown';
                }
                if (!isset($incidents[$incidentId])) {
                    $incidents[$incidentId] = [
                        'category' => $category,
                        'total' => 0,
                        'doa' => 0,
                        'linked_keys' => [],
                        'species_counts' => [],
                    ];
                }
                $incidents[$incidentId]['total'] = max($incidents[$incidentId]['total'], (int)report_value($row, ['Incident Reportable Casualties'], 0));
                $incidents[$incidentId]['doa'] = max($incidents[$incidentId]['doa'], (int)report_value($row, ['Incident DOA'], 0));
                $admissionId = trim((string)report_value($row, ['Admission ID'], ''));
                $patientId = trim((string)report_value($row, ['Patient ID'], ''));
                if ($admissionId === '' && $patientId === '') {
                    continue;
                }
                $linkedKey = $admissionId !== '' ? 'a:' . $admissionId : 'p:' . $patientId;
                if (isset($incidents[$incidentId]['linked_keys'][$linkedKey])) {
                    continue;
                }
                $incidents[$incidentId]['linked_keys'][$linkedKey] = true;
                if ($species === '') {
                    $species = 'Unknown linked casualty';
                }
                $incidents[$incidentId]['species_counts'][$species] = ($incidents[$incidentId]['species_counts'][$species] ?? 0) + 1;
                continue;
            } elseif ($species === '') {
                continue;
            }

            $categoryTotals[$category] = ($categoryTotals[$category] ?? 0) + 1;
            $speciesTotals[$species] = ($speciesTotals[$species] ?? 0) + 1;
            $matrix[$category][$species] = ($matrix[$category][$species] ?? 0) + 1;
        }

        foreach ($incidents as $incident) {
            $category = $incident['category'];
            foreach ($incident['species_counts'] as $species => $count) {
                $categoryTotals[$category] = ($categoryTotals[$category] ?? 0) + (int)$count;
                $speciesTotals[$species] = ($speciesTotals[$species] ?? 0) + (int)$count;
                $matrix[$category][$species] = ($matrix[$category][$species] ?? 0) + (int)$count;
            }

            $linkedCount = count($incident['linked_keys']);
            $missing = max(0, $incident['total'] - $linkedCount);
            $unknownDoa = min($incident['doa'], $missing);
            $unknownOther = $missing - $unknownDoa;

            foreach (['Unknown DOA' => $unknownDoa, 'Unknown / unlinked casualty' => $unknownOther] as $species => $count) {
                if ($count <= 0) {
                    continue;
                }
                $categoryTotals[$category] = ($categoryTotals[$category] ?? 0) + $count;
                $speciesTotals[$species] = ($speciesTotals[$species] ?? 0) + $count;
                $matrix[$category][$species] = ($matrix[$category][$species] ?? 0) + $count;
            }
        }

        arsort($categoryTotals);
        arsort($speciesTotals);
        $categories = array_keys($categoryTotals);
        $topSpecies = array_slice(array_keys($speciesTotals), 0, 6);
        $speciesList = count($speciesTotals) > 6 ? array_merge($topSpecies, ['Other species']) : $topSpecies;
        $datasets = [];

        foreach ($speciesList as $species) {
            $data = [];
            foreach ($categories as $category) {
                if ($species !== 'Other species') {
                    $data[] = (int)($matrix[$category][$species] ?? 0);
                    continue;
                }

                $otherTotal = 0;
                foreach ($matrix[$category] ?? [] as $speciesName => $count) {
                    if (!in_array($speciesName, $topSpecies, true)) {
                        $otherTotal += (int)$count;
                    }
                }
                $data[] = $otherTotal;
            }
            $datasets[] = ['label' => $species, 'data' => $data];
        }

        report_render_chart_config(
            'Incident theme by species',
            [
                'type' => 'bar',
                'stacked' => true,
                'indexAxis' => 'y',
                'showValues' => true,
                'data' => [
                    'labels' => $categories,
                    'datasets' => $datasets,
                ],
            ],
            'Complaint and lab-derived incident themes split by species.'
        );
    }
}

if (!function_exists('report_notifiable_review_queue')) {
    function report_notifiable_review_queue(array $rows): array {
        $queue = [];
        foreach ($rows as $row) {
            $category = (string)report_value($row, ['Notifiable Category (Derived)'], 'Other / Review Required');
            $source = (string)report_value($row, ['Source Type'], 'Presenting Complaint');
            $priority = report_notifiable_priority($category, $source);
            $queue[] = [
                'title' => report_notifiable_queue_title($row),
                'meta' => $category . ' - ' . report_notifiable_queue_meta($row),
                'tag' => $priority,
            ];
        }
        return $queue;
    }
}

if (!function_exists('report_notifiable_queue_title')) {
    function report_notifiable_queue_title(array $row): string {
        $source = (string)report_value($row, ['Source Type'], '');
        if ($source === 'Mass Casualty Incident') {
            return 'Incident #' . report_value($row, ['Source ID'], 'n/a');
        }
        return 'CRN #' . report_value($row, ['Admission ID'], report_value($row, ['Patient ID'], 'n/a'));
    }
}

if (!function_exists('report_notifiable_queue_meta')) {
    function report_notifiable_queue_meta(array $row): string {
        $source = (string)report_value($row, ['Source Type'], '');
        if ($source === 'Positive Lab Result') {
            return trim((string)report_value($row, ['Lab Test'], 'Positive lab result') . ' - ' . (string)report_value($row, ['Lab Result'], ''));
        }
        if ($source === 'Mass Casualty Incident') {
            return number_format((int)report_value($row, ['Incident Reportable Casualties'], 0)) . ' reportable casualties';
        }
        return (string)report_value($row, ['Presenting Complaint'], '');
    }
}

if (!function_exists('report_notifiable_incidents_main')) {
    function report_notifiable_incidents_main(array $module, array $rows, array $context): void {
        $priority = ['High priority' => 0, 'Review' => 0, 'Low confidence' => 0];
        $categories = [];
        $species = [];
        $sources = [];
        $positiveLabs = report_notifiable_positive_lab_breakdown($rows);
        $massCasualties = report_notifiable_mass_casualty_totals($rows);
        $species = report_notifiable_species_counts($rows);
        $patientJourneys = report_notifiable_patient_journey_keys($rows);
        $cohortSummary = (array)($context['cohort_summary'] ?? []);
        $totalJourneys = (int)($cohortSummary['Complete Patient Journeys'] ?? 0);
        $flaggedJourneys = count($patientJourneys);
        $notNotifiableJourneys = max(0, $totalJourneys - $flaggedJourneys);
        $queue = report_notifiable_review_queue($rows);

        foreach ($rows as $row) {
            $category = (string)report_value($row, ['Notifiable Category (Derived)'], 'Other / Review Required');
            $source = (string)report_value($row, ['Source Type'], 'Presenting Complaint');
            $priority[report_notifiable_priority($category, $source)]++;
            $categories[$category] = ($categories[$category] ?? 0) + 1;
            $sources[$source] = ($sources[$source] ?? 0) + 1;
        }
        $priorityChart = array_filter($priority, static function (int $count): bool {
            return $count > 0;
        });

        echo '<div class="rc-card report-block">';
        echo '<strong>Important:</strong> This section combines reportable presenting complaint keywords, positive laboratory results, and incident-log mass casualty events. It should be manually confirmed before external reporting.';
        echo '</div>';

        echo '<div class="report-summary-grid report-insight-grid report-grid-four">';
        foreach ([
            [
                'label' => 'Total cases',
                'value' => number_format($totalJourneys),
                'note' => 'Complete patient journeys in the report period',
            ],
            [
                'label' => 'Of which notifiable',
                'value' => number_format($flaggedJourneys),
                'note' => 'Complete patient journeys with one or more flags',
            ],
            [
                'label' => 'Positive labs',
                'value' => number_format(array_sum($positiveLabs)),
                'note' => 'Positive lab result records in period',
            ],
            [
                'label' => 'Mass casualty casualties',
                'value' => number_format(array_sum($massCasualties)),
                'note' => 'incident_total_casualties from incident records',
            ],
        ] as $item) {
            echo '<div class="report-summary-card report-insight">';
            echo '<span class="report-summary-label">' . report_h($item['label']) . '</span>';
            echo '<strong class="report-summary-value">' . report_h($item['value']) . '</strong>';
            echo '<span class="report-summary-note">' . report_h($item['note']) . '</span>';
            echo '</div>';
        }
        echo '</div>';

        $notifiableComparator = [
            'Notifiable flagged' => $flaggedJourneys,
            'Not notifiable' => $notNotifiableJourneys,
        ];

        echo '<div class="report-chart-grid">';
        report_chart_from_map('Notifiable vs all cases', $notifiableComparator, 'doughnut', 'Compares flagged complete journeys with the remaining complete journeys.');
        report_chart_from_map('Positive lab results by disease/test', $positiveLabs, 'bar', 'Positive laboratory results grouped by test.', 10, 'y');
        report_chart_from_map('Mass casualty event size', $massCasualties, 'bar', 'Uses incident_total_casualties from the incidents table.', 10, 'y');
        report_notifiable_category_species_chart($rows);
        report_chart_from_map('Species involved', $species, 'bar', 'Species most often appearing in the review queue.', 10, 'y');
        echo '</div>';

    }
}

if (!function_exists('report_notifiable_incidents_appendix')) {
    function report_notifiable_incidents_appendix(array $module, array $rows, array $context): void {
        echo '<section class="report-appendix">';
        echo '<h3>' . report_h($module['name'] ?? 'Notifiable Incidents') . ' supporting records</h3>';
        echo '<p class="rc-muted">Records flagged from presenting complaints, positive lab results, and mass casualty incidents for manual review.</p>';

        if (!$rows) {
            echo '<div class="rc-card">No potential notifiable incident records were found for this report period.</div>';
            echo '</section>';
            return;
        }

        foreach ($rows as $row) {
            $category = (string)report_value($row, ['Notifiable Category (Derived)'], 'Other / Review Required');
            $source = (string)report_value($row, ['Source Type'], 'Presenting Complaint');
            $priority = report_notifiable_priority($category, $source);
            $animalLabel = trim(implode(' | ', array_filter([
                trim((string)report_value($row, ['Animal Species'], '')),
                trim((string)report_value($row, ['Animal Type'], '')),
                trim((string)report_value($row, ['Animal Order'], '')),
            ])));

            echo '<div class="rc-card">';
            echo '<div class="rc-split-head">';
            echo '<h4>' . report_h(report_notifiable_queue_title($row)) . ' - ' . report_h(report_value($row, ['Patient Name'], $source)) . '</h4>';
            echo '<span class="rc-badge blue">' . report_h($priority) . '</span>';
            echo '</div>';
            echo '<div class="rc-chip-row">';
            $chips = [
                'Source: ' . $source,
                'Date: ' . report_notifiable_date(report_value($row, ['Event Date'], '')),
                'Category: ' . $category,
            ];
            if ($animalLabel !== '') {
                $chips[] = 'Animal: ' . $animalLabel;
            }
            if ($source === 'Positive Lab Result') {
                $chips[] = 'Lab test: ' . report_value($row, ['Lab Test'], 'n/a');
                $chips[] = 'Lab category: ' . report_value($row, ['Lab Category'], 'n/a');
                $chips[] = 'Result: ' . report_value($row, ['Lab Result'], 'n/a');
            } elseif ($source === 'Mass Casualty Incident') {
                $chips[] = 'Reference: ' . report_value($row, ['Incident Reference'], 'n/a');
                $chips[] = 'Casualties: ' . report_value($row, ['Incident Total Casualties'], 0);
                $chips[] = 'DOA: ' . report_value($row, ['Incident DOA'], 0);
                $chips[] = 'Reportable total: ' . report_value($row, ['Incident Reportable Casualties'], 0);
            } else {
                $chips[] = 'Complaint: ' . report_value($row, ['Presenting Complaint'], '');
                $chips[] = 'Disposition: ' . (trim((string)report_value($row, ['Disposition'], '')) ?: 'Not recorded');
            }
            $chips[] = 'Location: ' . report_value($row, ['Collection Location'], 'n/a');

            foreach ($chips as $chip) {
                echo '<span class="rc-chip">' . report_h($chip) . '</span>';
            }
            echo '</div>';
            echo '</div>';
        }

        echo '</section>';
    }
}
