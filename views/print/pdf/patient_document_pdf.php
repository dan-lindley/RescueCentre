<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../connection.php';
require_once __DIR__ . '/../../../core/icons.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../patient_data.php';

$document_kind = in_array(($document_kind ?? ''), ['handoff', 'record'], true) ? $document_kind : 'transfer';
$document_title = $document_kind === 'handoff'
    ? 'Patient Handoff Document'
    : ($document_kind === 'record' ? 'Full Patient Record' : 'Patient Transfer Document');

function patient_pdf_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function patient_pdf_dt($value): string
{
    if (!$value) {
        return 'Not recorded';
    }
    try {
        return (new DateTime((string)$value))->format('d M Y H:i');
    } catch (Throwable $e) {
        return (string)$value;
    }
}

$declaration = "By handing over this animal to the rescue centre, the finder confirms that they transfer ongoing responsibility for the care and welfare of the animal to the rescue.\n\nThe finder understands that their personal details, where provided and consented, may be stored and used for updates and audit or legal purposes in line with GDPR and the centre's privacy policy.";
$signature = [];
$centreLogo = '';
$corporateColour = '#0B3A6F';

try {
    $stmt = $pdo->prepare('SELECT centre_logo, handover_declaration_text, custom_colour FROM rescue_centre_meta WHERE centre_id = :centre_id LIMIT 1');
    $stmt->execute([':centre_id' => (int)$patient['centre_id']]);
    $centreMeta = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $centreLogo = trim((string)($centreMeta['centre_logo'] ?? ''));
    $storedColour = strtoupper(trim((string)($centreMeta['custom_colour'] ?? '')));
    if (preg_match('/^#[0-9A-F]{6}$/', $storedColour)) {
        $corporateColour = $storedColour;
    }
    if ($document_kind === 'handoff' && trim((string)($centreMeta['handover_declaration_text'] ?? '')) !== '') {
        $declaration = trim((string)$centreMeta['handover_declaration_text']);
    }
} catch (Throwable $e) {
    $centreLogo = '';
}

if ($centreLogo !== '' && !preg_match('~^(?:https?:)?//|^data:~i', $centreLogo)) {
    $localLogo = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\') . '/' . ltrim($centreLogo, '/\\');
    $centreLogo = is_file($localLogo) ? $localLogo : '';
}

$corporateRgb = [
    hexdec(substr($corporateColour, 1, 2)),
    hexdec(substr($corporateColour, 3, 2)),
    hexdec(substr($corporateColour, 5, 2)),
];
$luminanceChannels = array_map(static function (int $channel): float {
    $value = $channel / 255;
    return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
}, $corporateRgb);
$corporateIsLight = (
    0.2126 * $luminanceChannels[0]
    + 0.7152 * $luminanceChannels[1]
    + 0.0722 * $luminanceChannels[2]
) > 0.179;
$corporateTextRgb = $corporateIsLight ? [31, 41, 55] : [248, 250, 252];
$corporateMutedTextRgb = $corporateIsLight ? [55, 65, 81] : [226, 232, 240];
$corporateIconColour = $corporateIsLight ? '#1F2937' : '#F8FAFC';

if ($document_kind === 'handoff' && !empty($admission['admission_id'])) {
    try {
        $stmt = $pdo->prepare('
            SELECT signature_data, refused, signed_at
            FROM rescue_signatures
            WHERE centre_id = :centre_id AND admission_id = :admission_id AND patient_id = :patient_id
            ORDER BY signed_at DESC
            LIMIT 1
        ');
        $stmt->execute([
            ':centre_id' => (int)$patient['centre_id'],
            ':admission_id' => (int)$admission['admission_id'],
            ':patient_id' => (int)$patient['patient_id'],
        ]);
        $signature = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $signature = [];
    }
}

$centreAddress = implode(', ', array_filter([
    $centre['address_line_one'] ?? '',
    $centre['address_line_two'] ?? '',
    $centre['city'] ?? '',
    $centre['postcode'] ?? '',
]));
$centreContact = implode(' | ', array_filter([
    $centre['email'] ?? '',
    $centre['office_tel'] ?? '',
]));

$tcpdfRoot = __DIR__ . '/../../../lib/tcpdf';
$tcpdfRequiredFiles = [
    'tcpdf.php',
    'tcpdf_autoconfig.php',
    'config/tcpdf_config.php',
    'include/tcpdf_colors.php',
    'include/tcpdf_filters.php',
    'include/tcpdf_fonts.php',
    'include/tcpdf_font_data.php',
    'include/tcpdf_images.php',
    'include/tcpdf_static.php',
    'fonts/helvetica.php',
    'fonts/helveticab.php',
    'fonts/dejavusans.php',
    'fonts/dejavusans.z',
    'fonts/dejavusans.ctg.z',
    'fonts/dejavusanscondensedb.php',
    'fonts/dejavusanscondensedb.z',
    'fonts/dejavusanscondensedb.ctg.z',
];
$tcpdfMissingFiles = array_values(array_filter($tcpdfRequiredFiles, static function (string $file) use ($tcpdfRoot): bool {
    return !is_file($tcpdfRoot . '/' . $file);
}));

if ($tcpdfMissingFiles) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Server-side PDF generation is incomplete. Upload the complete new/lib/tcpdf folder.\n\n";
    echo "Missing files:\n- " . implode("\n- ", $tcpdfMissingFiles) . "\n";
    exit;
}
require_once $tcpdfRoot . '/tcpdf.php';

class PatientDocumentPdf extends TCPDF
{
    public $centreName = 'Rescue Centre';
    public $centreAddress = '';
    public $centreContact = '';
    public $centreLogo = '';
    public $appLogo = '';
    public $documentTitle = '';
    public $patientId = 0;
    public $corporateColour = [11, 58, 111];
    public $corporateTextColour = [248, 250, 252];
    public $corporateMutedTextColour = [226, 232, 240];

    public function Header()
    {
        $pageWidth = $this->getPageWidth();
        $this->SetFillColor(...$this->corporateColour);
        $this->Rect(0, 0, $pageWidth, 35, 'F');

        if ($this->centreLogo !== '' && is_file($this->centreLogo)) {
            $this->Image($this->centreLogo, 14, 8, 0, 19, '', '', '', true, 300);
        }

        $this->SetTextColor(...$this->corporateTextColour);
        $this->SetFont('dejavusanscondensed', 'B', 19);
        $this->SetXY(48, 6);
        $this->Cell($pageWidth - 62, 7, $this->centreName, 0, 1, 'R');

        $this->SetTextColor(...$this->corporateMutedTextColour);
        $this->SetFont('dejavusans', '', 9);
        $this->SetX(48);
        $this->Cell($pageWidth - 62, 5, $this->centreAddress, 0, 1, 'R');

        $this->SetTextColor(...$this->corporateTextColour);
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

        $contentLeft = 14;
        $contentRight = $pageWidth - 14;
        $messageX = $contentLeft;
        if ($this->appLogo !== '') {
            $this->ImageSVG('@' . html_entity_decode($this->appLogo, ENT_QUOTES, 'UTF-8'), $contentLeft, $pageHeight - 16, 11, 12);
            $messageX = 28;
        }

        $this->SetY($pageHeight - 15);
        $this->SetTextColor(...$this->corporateTextColour);
        $this->SetFont('dejavusans', '', 8);
        $this->SetX($messageX);
        $this->Cell($contentRight - $messageX, 5, 'Rescue Centre is a free-to-use software application supporting rescue organisations.', 0, 1, 'L');

        $this->SetFont('dejavusans', 'B', 8);
        $meta = $this->getAliasRightShift() . $this->documentTitle . ' | CRN: ' . $this->patientId . ' | Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages();
        $this->SetXY($contentLeft, $pageHeight - 8);
        $this->Cell(0, 4, $meta, 0, 0, 'R', false, '', 0, false, 'T', 'M');
    }
}

ob_start();
require __DIR__ . ($document_kind === 'record' ? '/patient_record_pdf_template.php' : '/patient_document_pdf_template.php');
$html = (string)ob_get_clean();

$pdf = new PatientDocumentPdf('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->centreName = (string)($centre['rescue_name'] ?? 'Rescue Centre');
$pdf->centreAddress = $centreAddress;
$pdf->centreContact = $centreContact;
$pdf->centreLogo = $centreLogo;
$pdf->appLogo = str_replace('#ffffff', $corporateIconColour, rc_icon('rclogo', 400, '', 'aria-hidden="true"'));
$pdf->documentTitle = $document_title;
$pdf->patientId = (int)$patient['patient_id'];
$pdf->corporateColour = $corporateRgb;
$pdf->corporateTextColour = $corporateTextRgb;
$pdf->corporateMutedTextColour = $corporateMutedTextRgb;
$pdf->SetCreator('Rescue Centre');
$pdf->SetAuthor($pdf->centreName);
$pdf->SetTitle($document_title . ' - CRN ' . $pdf->patientId);
$pdf->SetMargins(14, 39, 14);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);
$pdf->SetAutoPageBreak(true, 24);
$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->AddPage();
$pdf->writeHTML($html, true, false, true, false, '');
$filename = strtolower(str_replace(' ', '_', $document_title)) . '_crn_' . (int)$patient['patient_id'] . '.pdf';

if (($patient_pdf_output_mode ?? 'inline') === 'string') {
    $patient_pdf_filename = $filename;
    $patient_pdf_binary = $pdf->Output($filename, 'S');
} else {
    $pdf->Output($filename, 'I');
}
