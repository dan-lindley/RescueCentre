<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../core/icons.php';
require_once __DIR__ . '/../../reports/report_helpers.php';

$centreId = (int)($centre_id ?? $_SESSION['centre_id'] ?? 0);
$rescueName = trim((string)($rescue_name ?? $_SESSION['rescue_name'] ?? 'Rescue Centre'));
if ($centreId <= 0) {
    throw new RuntimeException('Centre context is not available.');
}

$stmt = $pdo->prepare('
    SELECT c.rescue_name, c.email, c.office_tel, c.address_line_one, c.address_line_two, c.city, c.postcode,
           m.reporting_from, m.reporting_to, m.centre_logo, m.custom_colour
    FROM rescue_centres c
    LEFT JOIN rescue_centre_meta m ON m.centre_id = c.rescue_id
    WHERE c.rescue_id = :centre_id
    LIMIT 1
');
$stmt->execute([':centre_id' => $centreId]);
$centreReport = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$fromDate = (string)($centreReport['reporting_from'] ?? date('Y-m-d', strtotime('-30 days')));
$toDate = (string)($centreReport['reporting_to'] ?? date('Y-m-d'));
$rescueName = trim((string)($centreReport['rescue_name'] ?? $rescueName)) ?: 'Rescue Centre';

$requested = $_POST['module_codes'] ?? $_GET['module_codes'] ?? [];
if (is_string($requested)) {
    $requested = array_filter(array_map('trim', explode(',', $requested)));
}
$requested = is_array($requested) ? array_values(array_unique(array_map('strval', $requested))) : [];

$stmt = $pdo->query('
    SELECT code, name, description, query_path
    FROM rescue_reports_modules
    WHERE is_active = 1
    ORDER BY sort_order ASC, name ASC
');
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
if (!$requested) {
    $requested = array_map(static fn(array $module): string => (string)$module['code'], $modules);
}
$selectedModules = array_values(array_filter($modules, static fn(array $module): bool => in_array((string)$module['code'], $requested, true)));

$cohortSummary = [];
$cohortSqlFile = __DIR__ . '/../../../models/reporting/REPORT_COHORT_SUMMARY.sql';
if (is_file($cohortSqlFile)) {
    $cohortSql = file_get_contents($cohortSqlFile);
    if ($cohortSql !== false && trim($cohortSql) !== '') {
        $cohortStmt = $pdo->prepare($cohortSql);
        $cohortStmt->execute([':centre_id' => $centreId, ':from_date' => $fromDate, ':to_date' => $toDate]);
        $cohortSummary = $cohortStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}

$corporateColour = strtoupper(trim((string)($centreReport['custom_colour'] ?? '')));
if (!preg_match('/^#[0-9A-F]{6}$/', $corporateColour)) {
    $corporateColour = '#0B3A6F';
}
$corporateRgb = [
    hexdec(substr($corporateColour, 1, 2)),
    hexdec(substr($corporateColour, 3, 2)),
    hexdec(substr($corporateColour, 5, 2)),
];
$channels = array_map(static function (int $channel): float {
    $value = $channel / 255;
    return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
}, $corporateRgb);
$isLight = (0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2]) > 0.179;
$textColour = $isLight ? '#1F2937' : '#F8FAFC';
$textRgb = $isLight ? [31, 41, 55] : [248, 250, 252];
$mutedTextRgb = $isLight ? [55, 65, 81] : [226, 232, 240];

$centreLogo = trim((string)($centreReport['centre_logo'] ?? ''));
if ($centreLogo !== '' && !preg_match('~^(?:https?:)?//|^data:~i', $centreLogo)) {
    $localLogo = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\') . '/' . ltrim($centreLogo, '/\\');
    $centreLogo = is_file($localLogo) ? $localLogo : '';
}
$centreAddress = implode(', ', array_filter([
    $centreReport['address_line_one'] ?? '',
    $centreReport['address_line_two'] ?? '',
    $centreReport['city'] ?? '',
    $centreReport['postcode'] ?? '',
]));
$centreContact = implode(' | ', array_filter([
    $centreReport['email'] ?? '',
    $centreReport['office_tel'] ?? '',
]));

$tcpdfRoot = __DIR__ . '/../../../lib/tcpdf';
if (!is_file($tcpdfRoot . '/tcpdf.php')) {
    throw new RuntimeException('Server-side PDF generation is incomplete.');
}
require_once $tcpdfRoot . '/tcpdf.php';

class ReportPackPdf extends TCPDF
{
    public $centreName = 'Rescue Centre';
    public $centreAddress = '';
    public $centreContact = '';
    public $centreLogo = '';
    public $appLogo = '';
    public $corporateColour = [11, 58, 111];
    public $textColour = [248, 250, 252];
    public $mutedTextColour = [226, 232, 240];
    public $corporateColourHex = '#0B3A6F';
    public $textColourHex = '#F8FAFC';
    private $chartColumn = 0;
    private $chartRowY = null;

    public function Header()
    {
        $pageWidth = $this->getPageWidth();
        $this->SetFillColor(...$this->corporateColour);
        $this->Rect(0, 0, $pageWidth, 35, 'F');
        if ($this->centreLogo !== '' && is_file($this->centreLogo)) {
            $this->Image($this->centreLogo, 14, 8, 0, 19, '', '', '', true, 300);
        }
        $this->SetTextColor(...$this->textColour);
        $this->SetFont('dejavusanscondensed', 'B', 19);
        $this->SetXY(48, 6);
        $this->Cell($pageWidth - 62, 7, $this->centreName, 0, 1, 'R');
        $this->SetTextColor(...$this->mutedTextColour);
        $this->SetFont('dejavusans', '', 9);
        $this->SetX(48);
        $this->Cell($pageWidth - 62, 5, $this->centreAddress, 0, 1, 'R');
        $this->SetTextColor(...$this->textColour);
        $this->SetFont('dejavusans', 'B', 9);
        $this->SetX(48);
        $this->Cell($pageWidth - 62, 5, $this->centreContact, 0, 1, 'R');
    }

    public function Footer()
    {
        $pageWidth = $this->getPageWidth();
        $pageHeight = $this->getPageHeight();
        $this->SetFillColor(...$this->corporateColour);
        $this->Rect(0, $pageHeight - 18, $pageWidth, 18, 'F');
        $left = 14;
        $messageX = $left;
        if ($this->appLogo !== '') {
            $this->ImageSVG('@' . html_entity_decode($this->appLogo, ENT_QUOTES, 'UTF-8'), $left, $pageHeight - 16, 11, 12);
            $messageX = 28;
        }
        $this->SetTextColor(...$this->textColour);
        $this->SetFont('dejavusans', '', 8);
        $this->SetXY($messageX, $pageHeight - 15);
        $this->Cell($pageWidth - 14 - $messageX, 5, 'Rescue Centre is a free-to-use software application supporting rescue organisations.', 0, 1, 'L');
        $this->SetFont('dejavusans', 'B', 8);
        $this->SetXY($left, $pageHeight - 8);
        $this->Cell(0, 4, 'Report Pack | Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'R');
    }

    public function finishChartRow(): void
    {
        if ($this->chartColumn !== 0 && $this->chartRowY !== null) {
            $this->SetY($this->chartRowY + 76);
        }
        $this->chartColumn = 0;
        $this->chartRowY = null;
    }

    public function drawReportChart(string $title, array $config, string $note = ''): void
    {
        $labels = array_values((array)($config['data']['labels'] ?? []));
        $datasets = array_values((array)($config['data']['datasets'] ?? []));
        if (!$labels || !$datasets) return;

        $height = 73;
        if ($this->chartColumn === 0) {
            if ($this->GetY() + $height > $this->getPageHeight() - 24) {
                $this->AddPage();
            }
            $this->chartRowY = $this->GetY() + 3;
        }

        $gap = 5;
        $width = ($this->getPageWidth() - 28 - $gap) / 2;
        $x = 14 + ($this->chartColumn * ($width + $gap));
        $y = (float)$this->chartRowY;
        $svgWidth = 1000;
        $svgHeight = 700;
        $escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $palette = ['#2563EB', '#16A34A', '#D97706', '#DC2626', '#7C3AED', '#0891B2', '#BE185D', '#4F46E5', '#65A30D', '#EA580C'];
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 700">'
            . '<rect x="2" y="2" width="996" height="696" rx="18" fill="#FFFFFF" stroke="#CBD5E1" stroke-width="3"/>'
            . '<path d="M20 2 H980 Q998 2 998 20 V96 H2 V20 Q2 2 20 2 Z" fill="' . $this->corporateColourHex . '"/>'
            . '<text x="35" y="43" fill="' . $this->textColourHex . '" font-family="DejaVu Sans" font-size="34" font-weight="bold">' . $escape($title) . '</text>';
        if ($note !== '') {
            $shortNote = strlen($note) > 105 ? substr($note, 0, 102) . '...' : $note;
            $svg .= '<text x="35" y="73" fill="' . $this->textColourHex . '" opacity="0.88" font-family="DejaVu Sans" font-size="21">' . $escape($shortNote) . '</text>';
        }

        $type = strtolower((string)($config['type'] ?? 'bar'));
        if ($type === 'doughnut' || $type === 'pie') {
            $values = array_map('floatval', (array)($datasets[0]['data'] ?? []));
            $total = array_sum($values);
            $cx = 330; $cy = 370; $radius = 190; $inner = $type === 'pie' ? 0 : 105; $angle = -90;
            foreach ($values as $index => $value) {
                if ($value <= 0 || $total <= 0) continue;
                $sweep = ($value / $total) * 360;
                if ($sweep >= 359.999) {
                    $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $radius . '" fill="' . $palette[$index % count($palette)] . '"/>';
                    if ($inner > 0) {
                        $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $inner . '" fill="#FFFFFF"/>';
                    }
                    $angle += $sweep;
                    continue;
                }
                $end = $angle + $sweep;
                $large = $sweep > 180 ? 1 : 0;
                $x1 = $cx + $radius * cos(deg2rad($angle)); $y1 = $cy + $radius * sin(deg2rad($angle));
                $x2 = $cx + $radius * cos(deg2rad($end)); $y2 = $cy + $radius * sin(deg2rad($end));
                $ix2 = $cx + $inner * cos(deg2rad($end)); $iy2 = $cy + $inner * sin(deg2rad($end));
                $ix1 = $cx + $inner * cos(deg2rad($angle)); $iy1 = $cy + $inner * sin(deg2rad($angle));
                $path = $inner > 0
                    ? "M $x1 $y1 A $radius $radius 0 $large 1 $x2 $y2 L $ix2 $iy2 A $inner $inner 0 $large 0 $ix1 $iy1 Z"
                    : "M $cx $cy L $x1 $y1 A $radius $radius 0 $large 1 $x2 $y2 Z";
                $svg .= '<path d="' . $path . '" fill="' . $palette[$index % count($palette)] . '" stroke="#FFFFFF" stroke-width="5"/>';
                $angle = $end;
            }
            foreach (array_slice($labels, 0, 8) as $index => $label) {
                $ly = 205 + ($index * 48);
                $svg .= '<rect x="575" y="' . ($ly - 18) . '" width="24" height="24" rx="4" fill="' . $palette[$index % count($palette)] . '"/>'
                    . '<text x="615" y="' . $ly . '" fill="#111827" font-family="DejaVu Sans" font-size="24" font-weight="bold">' . $escape((string)$label) . '</text>';
            }
        } else {
            $horizontal = (($config['indexAxis'] ?? '') === 'y');
            $stacked = !empty($config['stacked']);
            $plotX = $horizontal ? 300 : 95; $plotY = 130; $plotW = $horizontal ? 650 : 835; $plotH = 475;
            $totals = array_fill(0, count($labels), 0.0);
            foreach ($datasets as $dataset) {
                foreach ((array)($dataset['data'] ?? []) as $i => $value) $totals[$i] += (float)$value;
            }
            $maximum = max(1, $stacked ? max($totals ?: [1]) : max(array_merge([1], ...array_map(static fn($d) => array_map('floatval', (array)($d['data'] ?? [])), $datasets))));
            for ($grid = 0; $grid <= 5; $grid++) {
                $gy = $plotY + ($grid * ($plotH / 5));
                $svg .= '<line x1="' . $plotX . '" y1="' . $gy . '" x2="' . ($plotX + $plotW) . '" y2="' . $gy . '" stroke="#E5E7EB" stroke-width="2"/>';
            }
            if ($type === 'line') {
                foreach ($datasets as $di => $dataset) {
                    $points = [];
                    foreach ((array)($dataset['data'] ?? []) as $i => $value) {
                        $px = $plotX + (($i + .5) * ($plotW / max(1, count($labels))));
                        $py = $plotY + $plotH - (((float)$value / $maximum) * $plotH);
                        $points[] = $px . ',' . $py;
                    }
                    $colour = $palette[$di % count($palette)];
                    $svg .= '<polyline points="' . implode(' ', $points) . '" fill="none" stroke="' . $colour . '" stroke-width="7" stroke-linejoin="round" stroke-linecap="round"/>';
                    foreach ($points as $point) {
                        [$px, $py] = explode(',', $point);
                        $svg .= '<circle cx="' . $px . '" cy="' . $py . '" r="7" fill="' . $colour . '"/>';
                    }
                }
            } elseif ($horizontal) {
                $rowH = $plotH / max(1, count($labels));
                foreach ($labels as $i => $label) {
                    $svg .= '<text x="285" y="' . ($plotY + ($i + .58) * $rowH) . '" text-anchor="end" fill="#111827" font-family="DejaVu Sans" font-size="21" font-weight="bold">' . $escape(substr((string)$label, 0, 28)) . '</text>';
                    $offset = 0;
                    foreach ($datasets as $di => $dataset) {
                        $value = (float)($dataset['data'][$i] ?? 0);
                        $barW = ($value / $maximum) * $plotW;
                        $svg .= '<rect x="' . ($plotX + $offset) . '" y="' . ($plotY + ($i + .2) * $rowH) . '" width="' . max(1, $barW) . '" height="' . ($rowH * .58) . '" rx="6" fill="' . $palette[$di % count($palette)] . '"/>';
                        $offset += $stacked ? $barW : 0;
                    }
                }
            } else {
                $groupW = $plotW / max(1, count($labels)); $datasetCount = max(1, count($datasets));
                foreach ($labels as $i => $label) {
                    $offset = 0;
                    foreach ($datasets as $di => $dataset) {
                        $value = (float)($dataset['data'][$i] ?? 0);
                        $barH = ($value / $maximum) * $plotH;
                        $barW = $stacked ? $groupW * .62 : ($groupW * .72) / $datasetCount;
                        $bx = $plotX + ($i * $groupW) + ($groupW * .14) + ($stacked ? 0 : $di * $barW);
                        $by = $plotY + $plotH - $barH - $offset;
                        $svg .= '<rect x="' . $bx . '" y="' . $by . '" width="' . $barW . '" height="' . max(1, $barH) . '" rx="7" fill="' . $palette[$di % count($palette)] . '"/>';
                        $offset += $stacked ? $barH : 0;
                    }
                    $svg .= '<text x="' . ($plotX + ($i + .5) * $groupW) . '" y="640" text-anchor="middle" fill="#111827" font-family="DejaVu Sans" font-size="20" font-weight="bold">' . $escape(substr((string)$label, 0, 14)) . '</text>';
                }
            }
        }
        $svg .= '</svg>';
        $this->ImageSVG('@' . $svg, $x, $y, $width, $height);
        $this->chartColumn++;
        if ($this->chartColumn >= 2) {
            $this->finishChartRow();
        }
    }

    public function drawAppendixSection(string $title, array $rows): void
    {
        $this->finishChartRow();
        if ($this->GetY() > $this->getPageHeight() - 45) {
            $this->AddPage();
        }

        $pageWidth = $this->getPageWidth();
        $left = 14;
        $width = $pageWidth - 28;
        $this->Ln(3);
        $this->SetFillColor(...$this->corporateColour);
        $this->SetTextColor(...$this->textColour);
        $this->SetFont('dejavusanscondensed', 'B', 14);
        $this->SetX($left);
        $this->Cell($width, 11, strtoupper($title), 0, 1, 'L', true);
        $this->SetTextColor(82, 107, 130);
        $this->SetFont('dejavusans', '', 7.5);
        $this->SetX($left);
        $this->Cell($width, 6, count($rows) . ' supporting record' . (count($rows) === 1 ? '' : 's'), 0, 1, 'L', true);
        $this->Ln(1.5);

        if (!$rows) {
            $this->Ln(2);
            return;
        }

        $this->setCellPaddings(0.5, 0.2, 0.5, 0.2);
        foreach (array_chunk(array_keys($rows[0]), 5) as $columns) {
            $columnWidth = $width / count($columns);
            $drawHeader = function () use ($columns, $columnWidth, $left): void {
                $this->SetX($left);
                $this->SetFillColor(...$this->corporateColour);
                $this->SetTextColor(...$this->textColour);
                $this->SetDrawColor(215, 226, 236);
                $this->SetFont('dejavusans', 'B', 6.5);
                foreach ($columns as $column) {
                    $this->MultiCell($columnWidth, 6, (string)$column, 1, 'L', true, 0, '', '', true, 0, false, true, 6, 'M');
                }
                $this->Ln(6);
            };

            if ($this->GetY() > $this->getPageHeight() - 35) {
                $this->AddPage();
            }
            $drawHeader();

            foreach ($rows as $row) {
                $values = [];
                $rowHeight = 4;
                $this->SetFont('dejavusans', '', 6.5);
                foreach ($columns as $column) {
                    $value = $row[$column] ?? '';
                    if (is_array($value) || is_object($value)) $value = json_encode($value);
                    $value = trim((string)$value);
                    $values[] = $value;
                    $rowHeight = max($rowHeight, $this->getStringHeight($columnWidth - 1, $value, false, true, null, 0) + 0.4);
                }

                if ($this->GetY() + $rowHeight > $this->getPageHeight() - 24) {
                    $this->AddPage();
                    $drawHeader();
                }

                $rowY = $this->GetY();
                $this->SetX($left);
                $this->SetTextColor(38, 54, 72);
                $this->SetDrawColor(215, 226, 236);
                foreach ($values as $value) {
                    $this->MultiCell($columnWidth, $rowHeight, $value, 1, 'L', false, 0, '', '', true, 0, false, true, $rowHeight, 'M');
                }
                $this->SetY($rowY + $rowHeight);
            }
            $this->Ln(2);
        }
        $this->setCellPaddings(1, 1, 1, 1);
    }
}

$h = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');

function report_pdf_chart_maps(array $module, array $rows): array
{
    $code = report_module_key($module);
    $charts = [];

    switch ($code) {
        case 'CASE_INDEX':
            $charts = [
                'Admissions by species' => report_count_by($rows, ['animal_species', 'Animal Species'], 'Unknown species'),
                'Disposition mix' => report_count_by($rows, ['disposition', 'Disposition (Text)', 'Disposition'], 'Not recorded'),
                'Presenting complaint themes' => report_count_by($rows, ['presenting_complaint', 'Presenting Complaint'], 'Not recorded'),
            ];
            break;

        case 'SPECIES_OUTCOME_SUMMARY':
            $speciesTotals = [];
            foreach ($rows as $row) {
                $species = (string)report_value($row, ['Animal Species', 'animal_species'], 'Unknown species');
                $speciesTotals[$species] = (int)report_value($row, ['Admitted Total'], 0);
            }
            $charts = [
                'Largest species groups' => $speciesTotals,
                'Outcome totals' => [
                    'Released' => report_sum_column($rows, 'Released (R)'),
                    'Transferred' => report_sum_column($rows, 'Transferred (T)'),
                    'Euthanised' => report_sum_column($rows, 'Euthanised (E)'),
                    'Died after intake' => report_sum_column($rows, 'Died After Intake (D)'),
                    'Dead on admission' => report_sum_column($rows, 'Dead On Admission (DOA)'),
                    'Held in captivity' => report_sum_column($rows, 'Held in Captivity (IC)'),
                ],
            ];
            break;

        case 'OUTCOMES_DISPOSITION_LOG':
            $charts = [
                'Outcome codes' => report_count_by($rows, ['Universal Shortcode'], 'Not recorded'),
                'Outcomes by species' => report_count_by($rows, ['Animal Species'], 'Unknown species'),
                'Disposition text' => report_count_by($rows, ['Disposition (Text)', 'Disposition'], 'Not recorded'),
            ];
            break;

        case 'MEDICATION_LOG':
            $charts = [
                'Most used medicines' => report_count_by($rows, ['Medication'], 'Unknown medication'),
                'Medication by species' => report_count_by($rows, ['Animal Species'], 'Unknown species'),
                'Administrations by staff member' => report_count_by($rows, ['Given By'], 'Not recorded'),
            ];
            break;

        case 'TREATMENT_CARE_LOG':
            $charts = [
                'Care event types' => report_count_by($rows, ['Event Type', 'event_type'], 'Not recorded'),
                'Care activity by species' => report_count_by($rows, ['Animal Species'], 'Unknown species'),
                'Recorded by' => report_count_by($rows, ['Recorded By'], 'Not recorded'),
            ];
            break;

        case 'NOTIFIABLE_INCIDENTS':
            $charts = [
                'Incident categories' => report_count_by($rows, ['Notifiable Category (Derived)'], 'Other / Review Required'),
                'Species involved' => report_count_by($rows, ['Animal Species', 'animal_species'], 'Unknown species'),
                'Disposition of flagged records' => report_count_by($rows, ['Disposition'], 'Not recorded'),
            ];
            break;
    }

    $charts = array_filter($charts, static function (array $values): bool {
        foreach ($values as $value) {
            if (is_numeric($value) && (float)$value > 0) return true;
        }
        return false;
    });
    if ($charts || !$rows) return $charts;

    foreach (array_keys($rows[0]) as $column) {
        if (preg_match('/(?:^id$|_id$|date|time|latitude|longitude|lat|long|weight|amount|dose|total|count)/i', (string)$column)) {
            continue;
        }
        $counts = report_count_by($rows, [(string)$column], 'Not recorded');
        if (count($counts) > 1 && count($counts) <= 30) {
            return ['Records by ' . $column => $counts];
        }
    }

    return ['Records in section' => ['Records' => count($rows)]];
}

function report_pdf_collect_exact_charts(array $module, array $rows, array $context): array
{
    $templates = [
        'CASE_INDEX' => ['case_index_report.php', 'report_case_index_main'],
        'SPECIES_OUTCOME_SUMMARY' => ['species_outcome_report.php', 'report_species_outcome_main'],
        'OUTCOMES_DISPOSITION_LOG' => ['outcomes_disposition_report.php', 'report_outcomes_disposition_main'],
        'MEDICATION_LOG' => ['medication_log_report.php', 'report_medication_log_main'],
        'TREATMENT_CARE_LOG' => ['treatment_care_report.php', 'report_treatment_care_main'],
        'NOTIFIABLE_INCIDENTS' => ['notifiable_incidents_report.php', 'report_notifiable_incidents_main'],
    ];
    $key = report_module_key($module);
    if (empty($templates[$key])) return [];

    [$file, $function] = $templates[$key];
    require_once __DIR__ . '/../../reports/' . $file;
    if (!function_exists($function)) return [];

    $charts = [];
    $GLOBALS['report_chart_config_collector'] = static function (string $title, array $config, string $note) use (&$charts): void {
        $charts[] = ['title' => $title, 'config' => $config, 'note' => $note];
    };
    ob_start();
    try {
        $function($module, $rows, $context);
    } finally {
        ob_end_clean();
        unset($GLOBALS['report_chart_config_collector']);
    }
    return $charts;
}

$html = '<style>
body{color:#263648;font-family:dejavusans,sans-serif;font-size:8.5pt;line-height:1.35}
.title{color:' . $h($textColour) . ';background-color:' . $h($corporateColour) . ';font-family:dejavusanscondensed,sans-serif;font-size:23pt;font-weight:bold;text-transform:uppercase}
.subtitle{color:#60758a;font-size:9pt}
.section{margin-top:5mm;border:1px solid #d7e2ec}
.section-title{color:' . $h($textColour) . ';background-color:' . $h($corporateColour) . ';font-family:dejavusanscondensed,sans-serif;font-size:12pt;font-weight:bold;text-transform:uppercase}
.description{color:#526b82;background-color:#f3f7fa;font-size:8.5pt}
.summary{margin-top:2mm;background-color:#f3f7fa;border:1px solid #d7e2ec}
.summary-value{font-size:16pt;font-weight:bold;color:#172a3d}
.appendix-title{color:' . $h($textColour) . ';background-color:' . $h($corporateColour) . ';font-family:dejavusanscondensed,sans-serif;font-size:20pt;font-weight:bold;text-transform:uppercase}
.muted{color:#60758a}
</style>';
$html .= '<div class="title">&nbsp; Operational Report Pack</div>';
$html .= '<div class="subtitle">' . $h($rescueName) . ' | ' . $h($fromDate) . ' to ' . $h($toDate) . ' | Generated ' . $h(date('d M Y H:i')) . '</div>';
$html .= '<table class="summary" cellpadding="8" cellspacing="0"><tr>'
    . '<td><span class="muted">Report sections</span><br><span class="summary-value">' . count($selectedModules) . '</span></td>'
    . '<td><span class="muted">Reporting period</span><br><strong>' . $h($fromDate) . '<br>to ' . $h($toDate) . '</strong></td>'
    . '</tr></table>';

$pdf = new ReportPackPdf('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->centreName = $rescueName;
$pdf->centreAddress = $centreAddress;
$pdf->centreContact = $centreContact;
$pdf->centreLogo = $centreLogo;
$pdf->corporateColour = $corporateRgb;
$pdf->textColour = $textRgb;
$pdf->mutedTextColour = $mutedTextRgb;
$pdf->corporateColourHex = $corporateColour;
$pdf->textColourHex = $textColour;
$pdf->appLogo = str_replace('#ffffff', $textColour, rc_icon('rclogo', 400, '', 'aria-hidden="true"'));
$pdf->SetCreator('Rescue Centre');
$pdf->SetAuthor($rescueName);
$pdf->SetTitle($rescueName . ' Report Pack');
$pdf->SetMargins(14, 39, 14);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);
$pdf->SetAutoPageBreak(true, 24);
$pdf->AddPage();
$pdf->writeHTML($html, true, false, true, false, '');

$appendixSections = [];
foreach ($selectedModules as $module) {
    try {
        $pdf->finishChartRow();
        $rows = report_run_module($pdo, $module, $centreId, $fromDate, $toDate);
        $appendixSections[] = ['module' => $module, 'rows' => $rows];
        $sectionHtml = '<table class="section" cellpadding="6" cellspacing="0">'
            . '<tr><td class="section-title">' . $h($module['name']) . '</td></tr>';
        if (trim((string)($module['description'] ?? '')) !== '') {
            $sectionHtml .= '<tr><td class="description">' . $h($module['description']) . '</td></tr>';
        }
        $sectionHtml .= '<tr><td><strong>' . count($rows) . ' record' . (count($rows) === 1 ? '' : 's') . ' in this section.</strong></td></tr></table>';
        $pdf->writeHTML($sectionHtml, true, false, true, false, '');

        $charts = report_pdf_collect_exact_charts($module, $rows, [
            'centre_id' => $centreId,
            'rescue_name' => $rescueName,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'print_mode' => true,
            'cohort_summary' => $cohortSummary,
        ]);
        if (!$charts) {
            foreach (report_pdf_chart_maps($module, $rows) as $chartTitle => $chartValues) {
                $charts[] = [
                    'title' => $chartTitle,
                    'note' => '',
                    'config' => [
                        'type' => 'bar',
                        'indexAxis' => 'y',
                        'data' => ['labels' => array_keys($chartValues), 'datasets' => [['label' => $chartTitle, 'data' => array_values($chartValues)]]],
                    ],
                ];
            }
        }
        foreach ($charts as $chart) {
            $pdf->drawReportChart((string)$chart['title'], (array)$chart['config'], (string)$chart['note']);
        }
        $pdf->finishChartRow();

        if (!$rows) {
            $pdf->writeHTML('<p class="muted">No data found for the selected reporting period.</p>', true, false, true, false, '');
            continue;
        }
    } catch (Throwable $e) {
        error_log('REPORT PACK PDF ERROR [' . ($module['code'] ?? '') . ']: ' . $e->getMessage());
        $pdf->writeHTML('<p>Could not generate this report section.</p>', true, false, true, false, '');
    }
}

if ($appendixSections) {
    $pdf->finishChartRow();
    $pdf->AddPage();
    $pdf->writeHTML(
        '<div class="appendix-title">&nbsp; Data Appendix</div>'
        . '<p class="muted">Supporting records used to produce the report charts and summaries.</p>',
        true,
        false,
        true,
        false,
        ''
    );
    foreach ($appendixSections as $appendixSection) {
        $pdf->drawAppendixSection(
            (string)($appendixSection['module']['name'] ?? $appendixSection['module']['code'] ?? 'Report Section'),
            $appendixSection['rows']
        );
    }
}

$filename = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $rescueName)) . '_report_pack_' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'I');
