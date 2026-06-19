<?php
if (!defined('APP_LOADED')) exit;

if (!function_exists('report_case_index_date')) {
    function report_case_index_date($value): string {
        $value = trim((string)$value);
        if ($value === '') {
            return 'n/a';
        }
        $time = strtotime($value);
        return $time ? date('d M Y', $time) : $value;
    }
}

if (!function_exists('report_case_index_disposition_label')) {
    function report_case_index_disposition_label(array $row): string {
        $disposition = trim((string)report_value($row, ['disposition', 'Disposition'], ''));
        return $disposition !== '' ? $disposition : 'Open / pending';
    }
}

if (!function_exists('report_case_index_location_clusters')) {
    function report_case_index_location_clusters(array $rows, int $precision = 1): array {
        $clusters = [];
        foreach ($rows as $row) {
            $lat = report_value($row, ['location_lat'], '');
            $lng = report_value($row, ['location_long'], '');
            if (!is_numeric($lat) || !is_numeric($lng)) {
                continue;
            }
            $lat = round((float)$lat, $precision);
            $lng = round((float)$lng, $precision);
            if ($lat == 0.0 && $lng == 0.0) {
                continue;
            }

            $key = $lat . ',' . $lng;
            if (!isset($clusters[$key])) {
                $clusters[$key] = [
                    'lat' => $lat,
                    'lng' => $lng,
                    'count' => 0,
                ];
            }
            $clusters[$key]['count']++;
        }

        usort($clusters, static function (array $a, array $b): int {
            return $b['count'] <=> $a['count'];
        });
        return $clusters;
    }
}

if (!function_exists('report_case_index_location_map')) {
    function report_case_index_location_map(array $rows): void {
        $clusters = report_case_index_location_clusters($rows);
        if (!$clusters) {
            return;
        }
        $mapId = report_chart_id('report_location_map');

        echo '<div class="rc-card report-chart-card">';
        echo '<h4>Collection location clusters</h4>';
        echo '<p class="rc-muted">Rounded coordinate clusters; no addresses or postcodes shown.</p>';
        echo '<div id="' . report_h($mapId) . '" class="report-location-map" data-clusters="' . report_h(json_encode($clusters)) . '"></div>';
        echo '<script>
            (function(){
                function initReportMap(){
                    if (typeof L === "undefined") return;
                    var el = document.getElementById("' . report_h($mapId) . '");
                    if (!el || el.dataset.ready === "1") return;
                    var clusters = [];
                    try { clusters = JSON.parse(el.dataset.clusters || "[]"); } catch(e) { clusters = []; }
                    if (!clusters.length) return;
                    el.dataset.ready = "1";
                    var map = L.map(el, {
                        scrollWheelZoom: false,
                        dragging: true,
                        zoomControl: true,
                        attributionControl: true
                    });
                    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                        maxZoom: 13,
                        attribution: "&copy; OpenStreetMap contributors"
                    }).addTo(map);
                    var bounds = [];
                    var maxCount = clusters.reduce(function(max, c){ return Math.max(max, Number(c.count || 0)); }, 1);
                    clusters.forEach(function(cluster){
                        var lat = Number(cluster.lat);
                        var lng = Number(cluster.lng);
                        var count = Number(cluster.count || 0);
                        if (!isFinite(lat) || !isFinite(lng) || count <= 0) return;
                        bounds.push([lat, lng]);
                        var radius = 12 + (18 * (count / maxCount));
                        var marker = L.circleMarker([lat, lng], {
                            radius: radius,
                            color: "#1d4ed8",
                            weight: 2,
                            fillColor: "#2563eb",
                            fillOpacity: 0.82
                        }).addTo(map);
                        marker.bindTooltip(String(count), {
                            permanent: true,
                            direction: "center",
                            className: "report-map-count"
                        });
                    });
                    if (bounds.length === 1) {
                        map.setView(bounds[0], 10);
                    } else {
                        map.fitBounds(bounds, {padding: [34, 34], maxZoom: 10});
                    }
                    setTimeout(function(){ map.invalidateSize(); }, 150);
                }
                document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", initReportMap) : initReportMap();
            }());
        </script>';
        echo '</div>';
    }
}

if (!function_exists('report_case_index_is_release')) {
    function report_case_index_is_release(string $disposition): bool {
        $disposition = strtolower(trim($disposition));
        return in_array($disposition, ['released', 'r'], true);
    }
}

if (!function_exists('report_case_index_is_death')) {
    function report_case_index_is_death(string $disposition): bool {
        $disposition = strtolower(trim($disposition));
        return str_contains($disposition, 'died') || in_array($disposition, ['doa', 'dead on admission', 'euthanised', 'euthanized'], true);
    }
}

if (!function_exists('report_case_index_bucket_size')) {
    function report_case_index_bucket_size(string $fromDate, string $toDate): string {
        try {
            $from = new DateTime($fromDate);
            $to = new DateTime($toDate);
        } catch (Throwable $e) {
            return 'week';
        }

        $days = max(1, $from->diff($to)->days + 1);
        $weeks = (int)ceil($days / 7);
        if ($weeks <= 15) {
            return 'week';
        }
        if ((int)ceil($weeks / 2) <= 15) {
            return 'fortnight';
        }

        $months = (($to->format('Y') - $from->format('Y')) * 12) + ((int)$to->format('n') - (int)$from->format('n')) + 1;
        if ($months <= 15) {
            return 'month';
        }
        if ((int)ceil($months / 3) <= 15) {
            return 'quarter';
        }
        if ((int)ceil($months / 6) <= 15) {
            return 'half';
        }

        return 'year';
    }
}

if (!function_exists('report_case_index_period_key')) {
    function report_case_index_period_key($value, string $bucket, string $fromDate): ?string {
        if (!$value) {
            return null;
        }
        try {
            $date = new DateTime((string)$value);
            $from = new DateTime($fromDate);
        } catch (Throwable $e) {
            return null;
        }

        if ($bucket === 'fortnight') {
            $offset = max(0, $from->diff($date)->days);
            $periodStart = clone $from;
            $periodStart->modify('+' . ((int)floor($offset / 14) * 14) . ' days');
            return $periodStart->format('Y-m-d');
        }
        if ($bucket === 'month') {
            return $date->format('Y-m-01');
        }
        if ($bucket === 'quarter') {
            $month = ((int)floor(((int)$date->format('n') - 1) / 3) * 3) + 1;
            return $date->format('Y') . '-' . str_pad((string)$month, 2, '0', STR_PAD_LEFT) . '-01';
        }
        if ($bucket === 'half') {
            $month = ((int)$date->format('n') <= 6) ? 1 : 7;
            return $date->format('Y') . '-' . str_pad((string)$month, 2, '0', STR_PAD_LEFT) . '-01';
        }
        if ($bucket === 'year') {
            return $date->format('Y-01-01');
        }

        $date->modify('monday this week');
        return $date->format('Y-m-d');
    }
}

if (!function_exists('report_case_index_period_keys')) {
    function report_case_index_period_keys(string $fromDate, string $toDate, string $bucket): array {
        try {
            $cursor = new DateTime((string)report_case_index_period_key($fromDate, $bucket, $fromDate));
            $end = new DateTime((string)report_case_index_period_key($toDate, $bucket, $fromDate));
        } catch (Throwable $e) {
            return [];
        }

        $intervals = [
            'week' => '+1 week',
            'fortnight' => '+2 weeks',
            'month' => '+1 month',
            'quarter' => '+3 months',
            'half' => '+6 months',
            'year' => '+1 year',
        ];
        $step = $intervals[$bucket] ?? '+1 week';
        $keys = [];
        while ($cursor <= $end) {
            $keys[] = $cursor->format('Y-m-d');
            $cursor->modify($step);
        }
        return $keys;
    }
}

if (!function_exists('report_case_index_period_label')) {
    function report_case_index_period_label(string $key, string $bucket): string {
        try {
            $date = new DateTime($key);
        } catch (Throwable $e) {
            return $key;
        }

        if ($bucket === 'month') {
            return $date->format('M Y');
        }
        if ($bucket === 'quarter') {
            return 'Q' . (int)ceil((int)$date->format('n') / 3) . ' ' . $date->format('Y');
        }
        if ($bucket === 'half') {
            return ((int)$date->format('n') <= 6 ? 'H1 ' : 'H2 ') . $date->format('Y');
        }
        if ($bucket === 'year') {
            return $date->format('Y');
        }

        return $date->format('d M Y');
    }
}

if (!function_exists('report_case_index_weekly_flow_data')) {
    function report_case_index_weekly_flow_data(array $rows, int $broughtForward, string $fromDate, string $toDate): array {
        $bucket = report_case_index_bucket_size($fromDate, $toDate);
        $labels = report_case_index_period_keys($fromDate, $toDate, $bucket);
        $weekly = [
            'Admissions' => [],
            'Releases' => [],
            'Deaths' => [],
            'Other outcomes' => [],
        ];

        foreach ($rows as $row) {
            $admissionPeriod = report_case_index_period_key(report_value($row, ['admission_date'], ''), $bucket, $fromDate);
            if ($admissionPeriod !== null) {
                $weekly['Admissions'][$admissionPeriod] = ($weekly['Admissions'][$admissionPeriod] ?? 0) + 1;
            }

            $dispositionPeriod = report_case_index_period_key(report_value($row, ['disposition_date'], ''), $bucket, $fromDate);
            if ($dispositionPeriod === null) {
                continue;
            }

            $disposition = strtolower(trim((string)report_value($row, ['disposition'], '')));
            if (report_case_index_is_release($disposition)) {
                $weekly['Releases'][$dispositionPeriod] = ($weekly['Releases'][$dispositionPeriod] ?? 0) + 1;
            } elseif (report_case_index_is_death($disposition)) {
                $weekly['Deaths'][$dispositionPeriod] = ($weekly['Deaths'][$dispositionPeriod] ?? 0) + 1;
            } else {
                $weekly['Other outcomes'][$dispositionPeriod] = ($weekly['Other outcomes'][$dispositionPeriod] ?? 0) + 1;
            }
        }

        $opening = [];
        $closing = [];
        $net = [];
        $running = $broughtForward;
        foreach ($labels as $label) {
            $opening[$label] = $running;
            $weekNet = (int)($weekly['Admissions'][$label] ?? 0)
                - (int)($weekly['Releases'][$label] ?? 0)
                - (int)($weekly['Deaths'][$label] ?? 0)
                - (int)($weekly['Other outcomes'][$label] ?? 0);
            $net[$label] = $weekNet;
            $running += $weekNet;
            $closing[$label] = $running;
        }

        return [
            'labels' => $labels,
            'weekly' => $weekly,
            'opening' => $opening,
            'net' => $net,
            'closing' => $closing,
            'brought_forward' => $broughtForward,
            'bucket' => $bucket,
        ];
    }
}

if (!function_exists('report_case_index_weekly_flow_chart')) {
    function report_case_index_weekly_flow_chart(array $flow): void {
        $labels = $flow['labels'];
        $displayLabels = array_map(static function (string $label) use ($flow): string {
            return report_case_index_period_label($label, (string)$flow['bucket']);
        }, $labels);
        $datasets = [];
        foreach (['Admissions', 'Releases', 'Deaths'] as $name) {
            $data = [];
            foreach ($labels as $label) {
                $data[] = (int)($flow['weekly'][$name][$label] ?? 0);
            }
            $datasets[] = ['label' => $name, 'data' => $data];
        }

        echo '<div class="report-chart-wide">';
        report_render_chart_config(
            'Weekly admissions, releases and deaths',
            [
                'type' => 'line',
                'data' => [
                    'labels' => $displayLabels,
                    'datasets' => $datasets,
                ],
            ],
            'Counts are grouped automatically for the selected report length.'
        );
        echo '</div>';
    }
}

if (!function_exists('report_case_index_weekly_flow_table')) {
    function report_case_index_weekly_flow_table(array $flow): void {
        $labels = $flow['labels'];
        if (!$labels) {
            return;
        }

        $rows = [
            'Opening in care' => $flow['opening'],
            'Admissions' => $flow['weekly']['Admissions'],
            'Releases' => $flow['weekly']['Releases'],
            'Deaths' => $flow['weekly']['Deaths'],
            'Other outcomes' => $flow['weekly']['Other outcomes'],
            'Net movement (+/-)' => $flow['net'],
            'Closing in care' => $flow['closing'],
        ];

        echo '<div class="rc-card report-chart-wide">';
        echo '<h4>Weekly patient movement</h4>';
        echo '<p class="rc-muted">Starts with ' . report_h(number_format((int)$flow['brought_forward'])) . ' patients already in care before the reporting window. Net movement is admissions minus releases, deaths and other outcomes; negative values mean more animals left care than entered during that period.</p>';
        echo '<div class="rc-table-scroll">';
        echo '<table class="rc-table row-hover">';
        echo '<thead><tr><th>Movement</th>';
        foreach ($labels as $label) {
            echo '<th>' . report_h(report_case_index_period_label($label, (string)$flow['bucket'])) . '</th>';
        }
        echo '<th>Total</th></tr></thead><tbody>';

        foreach ($rows as $name => $values) {
            $total = 0;
            echo '<tr><th>' . report_h($name) . '</th>';
            foreach ($labels as $label) {
                $value = (int)($values[$label] ?? 0);
                if (!in_array($name, ['Opening in care', 'Closing in care'], true)) {
                    $total += $value;
                }
                echo '<td>' . report_h(number_format($value)) . '</td>';
            }
            echo '<td>' . (in_array($name, ['Opening in care', 'Closing in care'], true) ? '-' : report_h(number_format($total))) . '</td></tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
        echo '</div>';
    }
}

if (!function_exists('report_case_index_main')) {
    function report_case_index_main(array $module, array $rows, array $context): void {
        $patients = [];
        $cohortSummary = (array)($context['cohort_summary'] ?? []);
        $fromDate = (string)($context['from_date'] ?? '');
        $toDate = (string)($context['to_date'] ?? '');
        $broughtForward = (int)($cohortSummary['Active Before Report Start'] ?? 0);
        $flow = report_case_index_weekly_flow_data($rows, $broughtForward, $fromDate, $toDate);
        foreach ($rows as $row) {
            $patientId = trim((string)report_value($row, ['patient_id'], ''));
            if ($patientId !== '') {
                $patients[$patientId] = ($patients[$patientId] ?? 0) + 1;
            }
        }
        $readmissions = 0;
        foreach ($patients as $admissionCount) {
            if ($admissionCount > 1) {
                $readmissions += $admissionCount - 1;
            }
        }

        echo '<div class="report-summary-grid report-insight-grid report-grid-four">';
        foreach ([
            [
                'label' => 'Admissions',
                'value' => number_format(count($rows)),
                'note' => 'Complete journeys in the report period',
            ],
            [
                'label' => 'Readmissions',
                'value' => number_format($readmissions),
                'note' => 'Additional admissions for the same animal',
            ],
            [
                'label' => 'Final outcomes',
                'value' => number_format(count($rows)),
                'note' => 'Admission and disposition both in period',
            ],
            [
                'label' => 'Species groups',
                'value' => number_format(count(report_count_by($rows, ['animal_species'], 'Unknown species'))),
                'note' => 'Grouped by recorded species',
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
        report_case_index_weekly_flow_chart($flow);
        report_case_index_weekly_flow_table($flow);
        report_chart_from_map('Journey completion trend', report_time_series($rows, ['disposition_date'], 'week'), 'line', 'Completed journeys by final disposition week.');
        report_chart_from_map('Admissions by species', report_count_by($rows, ['animal_species'], 'Unknown species'), 'bar', 'Largest species groups in the report period.', 10, 'y');
        report_chart_from_map('Disposition mix', report_count_by($rows, ['disposition'], 'Not recorded'), 'doughnut', 'Final disposition for complete journeys in the ledger.');
        report_case_index_location_map($rows);
        echo '</div>';
    }
}

if (!function_exists('report_case_index_appendix')) {
    function report_case_index_appendix(array $module, array $rows, array $context): void {
        echo '<section class="report-appendix">';
        echo '<h3>' . report_h($module['name'] ?? 'Case Ledger') . ' supporting records</h3>';
        echo '<p class="rc-muted">Admission ledger records where admission and final disposition both fall inside the selected report period.</p>';

        if (!$rows) {
            echo '<div class="rc-card">No case ledger records were found for this report period.</div>';
            echo '</section>';
            return;
        }

        foreach ($rows as $row) {
            $species = trim((string)report_value($row, ['animal_species'], ''));
            $animalLabel = trim(implode(' | ', array_filter([
                $species,
                trim((string)report_value($row, ['animal_type'], '')),
                trim((string)report_value($row, ['animal_order'], '')),
                trim((string)report_value($row, ['sex'], '')),
            ])));
            $chips = [
                'Animal: ' . ($animalLabel !== '' ? $animalLabel : 'n/a'),
                'Admitted: ' . report_case_index_date(report_value($row, ['admission_date'], '')),
                'Disposition: ' . report_case_index_disposition_label($row),
                'Date: ' . report_case_index_date(report_value($row, ['disposition_date'], '')),
            ];

            if (trim((string)report_value($row, ['presenting_complaint'], '')) !== '') {
                $chips[] = 'Complaint: ' . trim((string)report_value($row, ['presenting_complaint'], ''));
            }
            if (trim((string)report_value($row, ['current_location'], '')) !== '') {
                $chips[] = 'Current location: ' . trim((string)report_value($row, ['current_location'], ''));
            }
            echo '<div class="rc-card">';
            echo '<div class="rc-split-head">';
            echo '<h4>CRN #' . report_h(report_value($row, ['admission_id'], report_value($row, ['patient_id'], 'n/a'))) . ' - ' . report_h(report_value($row, ['patient_name'], 'Patient')) . '</h4>';
            echo '<span class="rc-badge blue">' . report_h(report_case_index_disposition_label($row)) . '</span>';
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
