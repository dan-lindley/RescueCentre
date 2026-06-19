<?php
ob_start();
include 'dashmain.php';
include 'models/patient_data.php';

require_once __DIR__ . '/operations/permissions.php';
require_once __DIR__ . '/operations/audit.php';
registerPermission('patient.view', 'View patient care Plan', 'page');
registerPermission('patients.documents.delete', 'Delete patient documents', 'action');
registerPermission('patients.images.delete', 'Delete patient images', 'action');
requirePermission('patient.view');
$canDeleteDocuments = can('patients.documents.delete');
$canDeleteImages = can('patients.images.delete');

function patient_docs_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function patient_docs_redirect(int $patientId, array $params = []): void
{
    $params = ['patient_id' => $patientId] + $params;
    header('Location: docspatient.php?' . http_build_query($params));
    exit;
}

function patient_docs_clear_output(): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

function patient_docs_safe_name(string $name): string
{
    $extension = strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
    $base = (string)pathinfo($name, PATHINFO_FILENAME);
    $base = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $base);
    $base = trim((string)$base, '_');
    return ($base !== '' ? $base : 'document') . ($extension !== '' ? '.' . $extension : '');
}

function patient_docs_ensure_directory(string $path): bool
{
    return (is_dir($path) || mkdir($path, 0755, true)) && is_writable($path);
}

function patient_docs_detect_mime(string $path): string
{
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return strtolower((string)$finfo->file($path));
    }
    return function_exists('mime_content_type') ? strtolower((string)@mime_content_type($path)) : '';
}

function patient_docs_compress_image(string $sourcePath, string $mime, string $destinationPath): bool
{
    if (!function_exists('imagecreatetruecolor') || !function_exists('imagejpeg')) {
        return false;
    }

    $imageInfo = @getimagesize($sourcePath);
    if (!$imageInfo || empty($imageInfo[0]) || empty($imageInfo[1]) || ((int)$imageInfo[0] * (int)$imageInfo[1]) > 30000000) {
        return false;
    }

    if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
        $source = @imagecreatefromjpeg($sourcePath);
    } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
        $source = @imagecreatefrompng($sourcePath);
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $source = @imagecreatefromwebp($sourcePath);
    } else {
        return false;
    }
    if (!$source) {
        return false;
    }

    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    $scale = min(1, 2000 / max($sourceWidth, $sourceHeight));
    $width = max(1, (int)round($sourceWidth * $scale));
    $height = max(1, (int)round($sourceHeight * $scale));
    $output = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($output, 255, 255, 255);
    imagefilledrectangle($output, 0, 0, $width, $height, $white);
    imagecopyresampled($output, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
    imageinterlace($output, true);

    $saved = false;
    foreach ([85, 80, 75, 70, 65, 60, 55] as $quality) {
        if (!imagejpeg($output, $destinationPath, $quality)) {
            break;
        }
        clearstatcache(true, $destinationPath);
        $saved = true;
        if ((int)@filesize($destinationPath) <= 2000000) {
            break;
        }
    }

    imagedestroy($output);
    imagedestroy($source);
    return $saved;
}

function patient_docs_create_thumbnail(string $sourcePath, string $thumbnailPath): bool
{
    if (!patient_docs_ensure_directory(dirname($thumbnailPath))) {
        return false;
    }

    $mime = patient_docs_detect_mime($sourcePath);
    if (strpos($mime, 'image/') === 0 && function_exists('imagecreatetruecolor') && function_exists('imagejpeg')) {
        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo || empty($imageInfo[0]) || empty($imageInfo[1]) || ((int)$imageInfo[0] * (int)$imageInfo[1]) > 30000000) {
            return false;
        }
        if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
            $source = @imagecreatefromjpeg($sourcePath);
        } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
            $source = @imagecreatefrompng($sourcePath);
        } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
            $source = @imagecreatefromwebp($sourcePath);
        } else {
            $source = false;
        }
        if (!$source) {
            return false;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(360 / $sourceWidth, 240 / $sourceHeight, 1);
        $width = max(1, (int)round($sourceWidth * $scale));
        $height = max(1, (int)round($sourceHeight * $scale));
        $thumbnail = imagecreatetruecolor(360, 240);
        $white = imagecolorallocate($thumbnail, 255, 255, 255);
        imagefilledrectangle($thumbnail, 0, 0, 360, 240, $white);
        imagecopyresampled($thumbnail, $source, (int)((360 - $width) / 2), (int)((240 - $height) / 2), 0, 0, $width, $height, $sourceWidth, $sourceHeight);
        $saved = imagejpeg($thumbnail, $thumbnailPath, 78);
        imagedestroy($thumbnail);
        imagedestroy($source);
        return $saved;
    }

    if ($mime === 'application/pdf' && class_exists('Imagick')) {
        try {
            $thumbnail = new Imagick();
            $thumbnail->setResolution(120, 120);
            $thumbnail->readImage($sourcePath . '[0]');
            $thumbnail->setImageBackgroundColor('white');
            $thumbnail = $thumbnail->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
            $thumbnail->setImageFormat('jpeg');
            $thumbnail->thumbnailImage(360, 240, true, true);
            $thumbnail->setImageCompressionQuality(78);
            $saved = $thumbnail->writeImage($thumbnailPath);
            $thumbnail->clear();
            $thumbnail->destroy();
            return $saved;
        } catch (Throwable $e) {
            return false;
        }
    }

    return false;
}

function patient_docs_thumbnail_path(string $directory, string $documentName): string
{
    return $directory . '/.thumbnails/' . $documentName . '.jpg';
}

function patient_docs_password_valid(PDO $pdo, string $password): bool
{
    $accountId = (int)($_SESSION['account_id'] ?? $_SESSION['user_id'] ?? $GLOBALS['user_id'] ?? 0);
    if ($accountId <= 0 || $password === '') {
        return false;
    }
    $stmt = $pdo->prepare('SELECT password FROM accounts WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $accountId]);
    $hash = (string)$stmt->fetchColumn();
    return $hash !== '' && password_verify($password, $hash);
}

function patient_docs_patient_image_path(string $imageUrl): string
{
    $relative = ltrim(str_replace('\\', '/', $imageUrl), '/');
    if (strpos($relative, 'user_images/patient_images/') !== 0) {
        return '';
    }
    return rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\') . '/' . $relative;
}

$patientId = (int)$patient_id;
$centreId = (int)$centre_id;
$pdfEngineRoot = __DIR__ . '/lib/tcpdf';
$pdfEngineRequiredFiles = [
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
$pdfEngineMissingFiles = array_values(array_filter($pdfEngineRequiredFiles, static function (string $file) use ($pdfEngineRoot): bool {
    return !is_file($pdfEngineRoot . '/' . $file);
}));
$pdfEngineInstalled = !$pdfEngineMissingFiles;
$requestedTab = (string)($_GET['tab'] ?? 'create');
$tab = in_array($requestedTab, ['create', 'gallery', 'images'], true) ? $requestedTab : 'create';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$docsRoot = __DIR__ . '/user_documents/patient_documents';
$patientDocsDirectory = $docsRoot . '/centre_id_' . $centreId . '/patient_id_' . $patientId;
$legacyPatientDocsDirectory = __DIR__ . '/user_documents/' . $centreId . '/' . $patientId;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_generated_document'])) {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals((string)$_SESSION['csrf_token'], $postedToken)) {
        patient_docs_redirect($patientId, ['tab' => 'create', 'error' => 'Your session token expired. Please try again.']);
    }

    $recipient = trim((string)($_POST['recipient_email'] ?? ''));
    $documentKind = trim((string)($_POST['document_kind'] ?? ''));
    $documentTitles = [
        'transfer' => 'Patient Transfer Document',
        'handoff' => 'Patient Handoff Document',
        'record' => 'Full Patient Record',
    ];

    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        patient_docs_redirect($patientId, ['tab' => 'create', 'error' => 'Enter a valid recipient email address.']);
    }
    if (!isset($documentTitles[$documentKind])) {
        patient_docs_redirect($patientId, ['tab' => 'create', 'error' => 'Choose a valid document to email.']);
    }
    if (!$pdfEngineInstalled) {
        patient_docs_redirect($patientId, ['tab' => 'create', 'error' => 'The PDF engine is incomplete, so the document could not be emailed.']);
    }
    if (defined('mail_enabled') && !mail_enabled) {
        patient_docs_redirect($patientId, ['tab' => 'create', 'error' => 'Email sending is currently disabled.']);
    }

    try {
        $accountId = (int)($_SESSION['account_id'] ?? $_SESSION['user_id'] ?? $GLOBALS['user_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT email FROM accounts WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $accountId]);
        $userEmail = trim((string)$stmt->fetchColumn());

        $stmt = $pdo->prepare('SELECT rescue_name, email FROM rescue_centres WHERE rescue_id = :centre_id LIMIT 1');
        $stmt->execute([':centre_id' => $centreId]);
        $centreEmailDetails = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $centreName = trim((string)($centreEmailDetails['rescue_name'] ?? 'Rescue Centre'));
        $centreEmail = trim((string)($centreEmailDetails['email'] ?? ''));

        $patient_pdf_output_mode = 'string';
        $patient_pdf_binary = '';
        $patient_pdf_filename = '';
        $document_kind = $documentKind;
        require __DIR__ . '/views/print/pdf/patient_document_pdf.php';

        if (!is_string($patient_pdf_binary) || $patient_pdf_binary === '') {
            throw new RuntimeException('The PDF document could not be generated.');
        }

        $phpmailerDir = __DIR__ . '/lib/phpmailer';
        foreach (['Exception.php', 'PHPMailer.php', 'SMTP.php'] as $mailerFile) {
            if (!is_file($phpmailerDir . '/' . $mailerFile)) {
                throw new RuntimeException('The email library is incomplete.');
            }
            require_once $phpmailerDir . '/' . $mailerFile;
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        if (defined('SMTP') && SMTP) {
            $mail->isSMTP();
            $mail->Host = smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = smtp_user;
            $mail->Password = smtp_pass;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = smtp_port;
        } else {
            $mail->isMail();
        }

        $fromEmail = defined('mail_from') ? mail_from : 'noreply@rescuecentre.org.uk';
        $fromName = $centreName !== '' ? $centreName . ' via Rescue Centre' : 'Rescue Centre';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($recipient);

        $usedAddresses = [strtolower($recipient) => true];
        $ccAddresses = [];
        foreach ([$centreEmail, $userEmail] as $ccEmail) {
            $ccKey = strtolower($ccEmail);
            if (!filter_var($ccEmail, FILTER_VALIDATE_EMAIL) || isset($usedAddresses[$ccKey])) {
                continue;
            }
            $mail->addCC($ccEmail);
            $usedAddresses[$ccKey] = true;
            $ccAddresses[] = $ccEmail;
        }
        if (filter_var($centreEmail, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($centreEmail, $centreName);
        }

        $documentTitle = $documentTitles[$documentKind];
        $safeCentreName = patient_docs_h($centreName);
        $safeDocumentTitle = patient_docs_h($documentTitle);
        $safePatientName = patient_docs_h($patient_name);
        $mail->isHTML(true);
        $mail->Subject = $documentTitle . ' - CRN ' . $patientId;
        $mail->Body = '<!doctype html><html><body style="margin:0;background:#eef3f7;font-family:Arial,Helvetica,sans-serif;color:#263648;">'
            . '<div style="max-width:640px;margin:24px auto;background:#ffffff;border:1px solid #d7e2ec;">'
            . '<div style="padding:24px;background:#0b3a6f;color:#ffffff;">'
            . '<div style="font-size:22px;font-weight:bold;">' . $safeCentreName . '</div>'
            . '<div style="margin-top:5px;color:#d9e6f2;">Patient document</div></div>'
            . '<div style="padding:28px;"><h1 style="margin:0 0 16px;font-size:22px;color:#111111;">' . $safeDocumentTitle . '</h1>'
            . '<p style="line-height:1.6;">Please find the attached <strong>' . $safeDocumentTitle . '</strong> from ' . $safeCentreName . '.</p>'
            . '<div style="margin:20px 0;padding:16px;background:#f3f7fa;border-left:4px solid #0b3a6f;">'
            . '<strong>Patient:</strong> ' . $safePatientName . '<br><strong>CRN:</strong> ' . $patientId . '</div>'
            . '<p style="margin-bottom:0;line-height:1.6;color:#526b82;">The attached PDF was generated securely from the latest patient record held by the rescue centre.</p>'
            . '</div><div style="padding:14px 28px;background:#0b3a6f;color:#d9e6f2;font-size:12px;">Sent using Rescue Centre</div></div></body></html>';
        $mail->AltBody = $documentTitle . " from " . $centreName . "\nPatient: " . $patient_name . "\nCRN: " . $patientId . "\n\nPlease find the PDF document attached.";
        $mail->addStringAttachment($patient_pdf_binary, $patient_pdf_filename, 'base64', 'application/pdf');
        $mail->send();

        audit_write($pdo, 'patient_generated_document_emailed', 'patient_documents', null, [
            'patient_id' => $patientId,
            'document_kind' => $documentKind,
            'recipient' => $recipient,
            'cc' => $ccAddresses,
        ]);
        patient_docs_redirect($patientId, ['tab' => 'create', 'msg' => $documentTitle . ' emailed successfully.']);
    } catch (Throwable $e) {
        error_log('Patient document email failed: ' . $e);
        audit_write($pdo, 'patient_generated_document_email_failed', 'patient_documents', null, [
            'patient_id' => $patientId,
            'document_kind' => $documentKind,
        ]);
        patient_docs_redirect($patientId, ['tab' => 'create', 'error' => 'The document could not be emailed. Please try again.']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlink_patient_image'])) {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals((string)$_SESSION['csrf_token'], $postedToken)) {
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'Your session token expired. Please try again.']);
    }
    if (!$canDeleteImages) {
        audit_write($pdo, 'patient_image_unlink_denied', 'patient_images', null, ['patient_id' => $patientId]);
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'You do not have permission to unlink patient images.']);
    }
    if (!patient_docs_password_valid($pdo, (string)($_POST['password'] ?? ''))) {
        audit_write($pdo, 'patient_image_unlink_password_failed', 'patient_images', null, ['patient_id' => $patientId]);
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'Your password was incorrect. The image was not unlinked.']);
    }

    $imageId = (int)($_POST['image_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT image_id, file_name FROM rescue_images WHERE image_id = :image_id AND patient_id = :patient_id AND centre_id = :centre_id LIMIT 1');
    $stmt->execute([':image_id' => $imageId, ':patient_id' => $patientId, ':centre_id' => $centreId]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$image) {
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'Image not found.']);
    }

    $stmt = $pdo->prepare('UPDATE rescue_notes_patients SET image_id = NULL WHERE patient_id = :patient_id AND image_id = :image_id');
    $stmt->execute([':patient_id' => $patientId, ':image_id' => $imageId]);
    $unlinkedNotes = $stmt->rowCount();
    audit_write($pdo, 'patient_image_unlinked_from_care_notes', 'patient_images', [
        'patient_id' => $patientId,
        'image_id' => $imageId,
        'file_name' => (string)$image['file_name'],
        'care_notes_linked' => $unlinkedNotes,
    ], [
        'care_notes_linked' => 0,
    ]);
    patient_docs_redirect($patientId, ['tab' => 'images', 'msg' => $unlinkedNotes > 0
        ? 'Image unlinked from care notes. It can now be removed.'
        : 'The image was not attached to any care notes.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_patient_image'])) {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals((string)$_SESSION['csrf_token'], $postedToken)) {
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'Your session token expired. Please try again.']);
    }
    if (!$canDeleteImages) {
        audit_write($pdo, 'patient_image_delete_denied', 'patient_images', null, ['patient_id' => $patientId]);
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'You do not have permission to remove patient images.']);
    }
    if (!patient_docs_password_valid($pdo, (string)($_POST['password'] ?? ''))) {
        audit_write($pdo, 'patient_image_delete_password_failed', 'patient_images', null, ['patient_id' => $patientId]);
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'Your password was incorrect. The image was not removed.']);
    }

    $imageId = (int)($_POST['image_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT image_id, image_url, file_name, is_legacy FROM rescue_images WHERE image_id = :image_id AND patient_id = :patient_id AND centre_id = :centre_id LIMIT 1');
    $stmt->execute([':image_id' => $imageId, ':patient_id' => $patientId, ':centre_id' => $centreId]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$image || !empty($image['is_legacy'])) {
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'That image cannot be removed here.']);
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rescue_notes_patients WHERE image_id = :image_id');
    $stmt->execute([':image_id' => $imageId]);
    if ((int)$stmt->fetchColumn() > 0) {
        audit_write($pdo, 'patient_image_delete_linked_denied', 'patient_images', null, [
            'patient_id' => $patientId,
            'image_id' => $imageId,
        ]);
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'This image is attached to a care note and cannot be removed.']);
    }

    $imagePath = patient_docs_patient_image_path((string)$image['image_url']);
    if ($imagePath === '' || !is_file($imagePath)) {
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'The image file could not be safely located, so it was not removed.']);
    }
    if (!unlink($imagePath)) {
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'The image file could not be removed.']);
    }
    $stmt = $pdo->prepare('DELETE FROM rescue_images WHERE image_id = :image_id AND patient_id = :patient_id AND centre_id = :centre_id');
    $stmt->execute([':image_id' => $imageId, ':patient_id' => $patientId, ':centre_id' => $centreId]);
    audit_write($pdo, 'patient_image_deleted', 'patient_images', [
        'patient_id' => $patientId,
        'image_id' => $imageId,
        'file_name' => (string)$image['file_name'],
    ], null);
    patient_docs_redirect($patientId, ['tab' => 'images', 'msg' => 'Image removed successfully.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_patient_document'])) {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals((string)$_SESSION['csrf_token'], $postedToken)) {
        patient_docs_redirect($patientId, ['tab' => 'gallery', 'error' => 'Your session token expired. Please try again.']);
    }
    if (!$canDeleteDocuments) {
        audit_write($pdo, 'patient_document_delete_denied', 'patient_documents', null, ['patient_id' => $patientId]);
        patient_docs_redirect($patientId, ['tab' => 'gallery', 'error' => 'You do not have permission to remove patient documents.']);
    }

    $deleteName = basename((string)($_POST['document_name'] ?? ''));
    if ($deleteName === '') {
        patient_docs_redirect($patientId, ['tab' => 'gallery', 'error' => 'Document not found.']);
    }
    if (!patient_docs_password_valid($pdo, (string)($_POST['password'] ?? ''))) {
        audit_write($pdo, 'patient_document_delete_password_failed', 'patient_documents', null, [
            'patient_id' => $patientId,
            'file_name' => $deleteName,
        ]);
        patient_docs_redirect($patientId, ['tab' => 'gallery', 'error' => 'Your password was incorrect. The document was not removed.']);
    }

    $deletePath = '';
    $deleteDirectory = '';
    foreach ([$patientDocsDirectory, $legacyPatientDocsDirectory] as $documentDirectory) {
        $candidate = $documentDirectory . '/' . $deleteName;
        if (is_file($candidate)) {
            $deletePath = $candidate;
            $deleteDirectory = $documentDirectory;
            break;
        }
    }
    if ($deletePath === '') {
        patient_docs_redirect($patientId, ['tab' => 'gallery', 'error' => 'Document not found.']);
    }

    $deletedSize = (int)filesize($deletePath);
    if (!unlink($deletePath)) {
        patient_docs_redirect($patientId, ['tab' => 'gallery', 'error' => 'The document could not be removed.']);
    }
    $thumbnailPath = patient_docs_thumbnail_path($deleteDirectory, $deleteName);
    if (is_file($thumbnailPath)) {
        @unlink($thumbnailPath);
    }
    audit_write($pdo, 'patient_document_deleted', 'patient_documents', [
        'patient_id' => $patientId,
        'file_name' => $deleteName,
        'size_bytes' => $deletedSize,
    ], null);
    patient_docs_redirect($patientId, ['tab' => 'gallery', 'msg' => 'Document removed successfully.']);
}

if (!empty($_GET['thumbnail'])) {
    $thumbnailName = basename((string)$_GET['thumbnail']);
    foreach ([$patientDocsDirectory, $legacyPatientDocsDirectory] as $documentDirectory) {
        $documentPath = $documentDirectory . '/' . $thumbnailName;
        $thumbnailPath = patient_docs_thumbnail_path($documentDirectory, $thumbnailName);
        if (is_file($documentPath) && (!is_file($thumbnailPath) || filemtime($thumbnailPath) < filemtime($documentPath))) {
            patient_docs_create_thumbnail($documentPath, $thumbnailPath);
        }
        if (is_file($thumbnailPath)) {
            patient_docs_clear_output();
            header('Content-Type: image/jpeg');
            header('Content-Length: ' . filesize($thumbnailPath));
            header('Cache-Control: private, max-age=86400');
            header('X-Content-Type-Options: nosniff');
            readfile($thumbnailPath);
            exit;
        }
    }
    patient_docs_clear_output();
    http_response_code(404);
    exit;
}

if (!empty($_GET['download'])) {
    $downloadName = basename((string)$_GET['download']);
    $downloadPath = $patientDocsDirectory . '/' . $downloadName;
    if (!is_file($downloadPath)) {
        $downloadPath = $legacyPatientDocsDirectory . '/' . $downloadName;
    }
    if ($downloadName === '' || !is_file($downloadPath)) {
        patient_docs_redirect($patientId, ['tab' => 'gallery', 'error' => 'Document not found.']);
    }

    patient_docs_clear_output();
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . addcslashes($downloadName, '"\\') . '"');
    header('Content-Length: ' . filesize($downloadPath));
    header('X-Content-Type-Options: nosniff');
    readfile($downloadPath);
    exit;
}

if (!empty($_GET['patient_image'])) {
    $imageId = (int)$_GET['patient_image'];
    $stmt = $pdo->prepare('SELECT image_url FROM rescue_images WHERE image_id = :image_id AND patient_id = :patient_id AND centre_id = :centre_id LIMIT 1');
    $stmt->execute([':image_id' => $imageId, ':patient_id' => $patientId, ':centre_id' => $centreId]);
    $imagePath = patient_docs_patient_image_path((string)$stmt->fetchColumn());
    if ($imagePath !== '' && is_file($imagePath)) {
        patient_docs_clear_output();
        header('Content-Type: ' . (patient_docs_detect_mime($imagePath) ?: 'image/jpeg'));
        header('Content-Length: ' . filesize($imagePath));
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($imagePath);
        exit;
    }
    patient_docs_clear_output();
    http_response_code(404);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_patient_image'])) {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals((string)$_SESSION['csrf_token'], $postedToken)) {
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'Your session token expired. Please try again.']);
    }

    $file = $_FILES['patient_image'] ?? null;
    if (!is_array($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'Choose an image to upload.']);
    }
    if ((int)($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'The image upload failed.']);
    }
    $uploadSize = (int)($file['size'] ?? 0);
    if ($uploadSize <= 0 || $uploadSize > 3 * 1024 * 1024) {
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'Images must be 3 MB or smaller before compression.']);
    }

    $temporaryPath = (string)($file['tmp_name'] ?? '');
    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'Invalid image upload detected.']);
    }
    $mime = patient_docs_detect_mime($temporaryPath);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'Please upload a JPG, PNG, or WebP image.']);
    }

    $relativeDirectory = 'user_images/patient_images/centre_id_' . $centreId . '/patient_id_' . $patientId;
    $filesystemDirectory = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? __DIR__), '/\\') . '/' . $relativeDirectory;
    if (!patient_docs_ensure_directory($filesystemDirectory)) {
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'The patient image folder could not be created.']);
    }
    $safeName = patient_docs_safe_name((string)($file['name'] ?? 'image.jpg'));
    $safeBase = (string)pathinfo($safeName, PATHINFO_FILENAME);
    $storedName = $safeBase . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.jpg';
    $destination = $filesystemDirectory . '/' . $storedName;
    if (!patient_docs_compress_image($temporaryPath, $mime, $destination)) {
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'The image could not be processed.']);
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO rescue_images (centre_id, patient_id, image_url, file_name, is_legacy) VALUES (:centre_id, :patient_id, :image_url, :file_name, 0)');
        $stmt->execute([
            ':centre_id' => $centreId,
            ':patient_id' => $patientId,
            ':image_url' => $relativeDirectory . '/' . $storedName,
            ':file_name' => $storedName,
        ]);
        $imageId = (int)$pdo->lastInsertId();
    } catch (Throwable $e) {
        @unlink($destination);
        patient_docs_redirect($patientId, ['tab' => 'images', 'error' => 'The image record could not be saved.']);
    }

    audit_write($pdo, 'patient_image_uploaded', 'patient_images', null, [
        'patient_id' => $patientId,
        'image_id' => $imageId,
        'file_name' => $storedName,
        'original_name' => (string)($file['name'] ?? ''),
        'size_bytes' => (int)filesize($destination),
    ]);
    patient_docs_redirect($patientId, ['tab' => 'images', 'msg' => 'Image compressed and uploaded successfully.']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_patient_document'])) {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals((string)$_SESSION['csrf_token'], $postedToken)) {
        patient_docs_redirect($patientId, ['tab' => 'gallery', 'error' => 'Your session token expired. Please try again.']);
    }

    $file = $_FILES['patient_document'] ?? null;
    if (!is_array($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        patient_docs_redirect($patientId, ['tab' => 'gallery', 'error' => 'Choose a document to upload.']);
    }
    if ((int)($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        patient_docs_redirect($patientId, ['tab' => 'gallery', 'error' => 'The document upload failed.']);
    }
    $uploadSize = (int)($file['size'] ?? 0);
    if ($uploadSize <= 0 || $uploadSize > 10 * 1024 * 1024) {
        patient_docs_redirect($patientId, ['tab' => 'gallery', 'error' => 'Documents must be 10 MB or smaller.']);
    }

    $safeName = patient_docs_safe_name((string)($file['name'] ?? 'document'));
    $extension = strtolower((string)pathinfo($safeName, PATHINFO_EXTENSION));
    $temporaryPath = (string)($file['tmp_name'] ?? '');
    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        patient_docs_redirect($patientId, ['tab' => 'gallery', 'error' => 'Invalid document upload detected.']);
    }

    $mime = patient_docs_detect_mime($temporaryPath);
    $allowedTypes = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/x-ole-storage', 'application/cdfv2'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/cdfv2'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'csv' => ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'],
        'txt' => ['text/plain'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
    ];
    if (!isset($allowedTypes[$extension]) || !in_array($mime, $allowedTypes[$extension], true)) {
        patient_docs_redirect($patientId, ['tab' => 'gallery', 'error' => 'That document type is not supported.']);
    }
    $isImage = strpos($mime, 'image/') === 0;
    if ($isImage && $uploadSize > 3 * 1024 * 1024) {
        patient_docs_redirect($patientId, ['tab' => 'gallery', 'error' => 'Images must be 3 MB or smaller before compression.']);
    }

    if (!patient_docs_ensure_directory($patientDocsDirectory)) {
        patient_docs_redirect($patientId, ['tab' => 'gallery', 'error' => 'The document folder could not be created.']);
    }

    $safeBase = (string)pathinfo($safeName, PATHINFO_FILENAME);
    $storedName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safeBase . ($isImage ? '.jpg' : '.' . $extension);
    $destination = $patientDocsDirectory . '/' . $storedName;
    $saved = $isImage
        ? patient_docs_compress_image($temporaryPath, $mime, $destination)
        : move_uploaded_file($temporaryPath, $destination);
    if (!$saved) {
        patient_docs_redirect($patientId, ['tab' => 'gallery', 'error' => 'The document could not be saved.']);
    }
    patient_docs_create_thumbnail($destination, patient_docs_thumbnail_path($patientDocsDirectory, $storedName));
    audit_write($pdo, 'patient_document_uploaded', 'patient_documents', null, [
        'patient_id' => $patientId,
        'file_name' => $storedName,
        'original_name' => (string)($file['name'] ?? ''),
        'mime_type' => $mime,
        'size_bytes' => (int)filesize($destination),
        'image_compressed' => $isImage,
    ]);

    patient_docs_redirect($patientId, ['tab' => 'gallery', 'msg' => $isImage ? 'Image compressed and uploaded successfully.' : 'Document uploaded successfully.']);
}

$documents = [];
foreach ([$patientDocsDirectory, $legacyPatientDocsDirectory] as $documentDirectory) {
    if (is_dir($documentDirectory)) {
        foreach (new DirectoryIterator($documentDirectory) as $entry) {
            if (!$entry->isFile()) {
                continue;
            }
            $thumbnailPath = patient_docs_thumbnail_path($documentDirectory, $entry->getFilename());
            if (!is_file($thumbnailPath)) {
                patient_docs_create_thumbnail($entry->getPathname(), $thumbnailPath);
            }
            $documents[] = [
                'name' => $entry->getFilename(),
                'size' => $entry->getSize(),
                'modified' => $entry->getMTime(),
                'extension' => strtoupper((string)pathinfo($entry->getFilename(), PATHINFO_EXTENSION)),
                'has_thumbnail' => is_file($thumbnailPath),
            ];
        }
    }
}
usort($documents, static fn(array $a, array $b): int => $b['modified'] <=> $a['modified']);

$patientImages = [];
$stmt = $pdo->prepare('
    SELECT i.image_id, i.image_url, i.file_name, i.is_legacy,
           (SELECT COUNT(*) FROM rescue_notes_patients n WHERE n.image_id = i.image_id) AS care_note_count
    FROM rescue_images i
    WHERE i.patient_id = :patient_id AND i.centre_id = :centre_id
    ORDER BY i.image_id DESC
');
$stmt->execute([':patient_id' => $patientId, ':centre_id' => $centreId]);
$patientImages = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
foreach ($patientImages as &$patientImage) {
    $relativeImageUrl = ltrim((string)$patientImage['image_url'], '/');
    $patientImage['preview_url'] = !empty($patientImage['is_legacy'])
        ? 'https://legacy.rescuecentre.org.uk/wp-content/themes/brikk-child/' . $relativeImageUrl
        : 'docspatient.php?' . http_build_query(['patient_id' => $patientId, 'patient_image' => (int)$patientImage['image_id']]);
}
unset($patientImage);

echo template_admin_header(
    'CRN: ' . $patientId . ' - ' . $patient_name . ' - Patient Documents',
    'patients',
    'mypatients'
);

if (!empty($_GET['msg'])) {
    echo '<div class="rc-alert green">' . patient_docs_h($_GET['msg']) . '</div>';
}
if (!empty($_GET['error'])) {
    echo '<div class="rc-alert red">' . patient_docs_h($_GET['error']) . '</div>';
}
?>

<div class="content-title">
    <div class="title">
        <div class="icon">
            <svg width="30" height="30" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-256-128 0c-35.3 0-64-28.7-64-64L192 0 64 0zM256 0l0 128 128 0L256 0zM96 280c0-13.3 10.7-24 24-24l144 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-144 0c-13.3 0-24-10.7-24-24zm0 96c0-13.3 10.7-24 24-24l144 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-144 0c-13.3 0-24-10.7-24-24z"/></svg>
        </div>
        <div class="txt">
            <h2 class="pagehead">Patient Documents</h2>
            <b>CRN: <?= $patientId ?> - <?= patient_docs_h($patient_name) ?></b>
        </div>
    </div>
    <div class="btns">
        <a href="viewpatient.php?patient_id=<?= $patientId ?>" class="btn green">Care Plan</a>
        <a href="editpatient.php?patient_id=<?= $patientId ?>" class="btn orange">Edit</a>
        <a href="docspatient.php?patient_id=<?= $patientId ?>" class="btn red">Docs</a>
        <a href="views/print/patient_record_pdf.php?patient_id=<?= $patientId ?>" target="_blank" class="btn blue">Print</a>
    </div>
</div>

<div class="rc-card rc-card-muted">
    <div class="rc-stat-grid">
        <div class="rc-stat"><span>Species</span><strong><?= patient_docs_h($patient_animal_species) ?></strong></div>
        <div class="rc-stat"><span>Sex</span><strong><?= patient_docs_h($patient_sex) ?></strong></div>
        <div class="rc-stat"><span>Current location</span><strong><?= patient_docs_h($current_location ?: 'Not recorded') ?></strong></div>
        <div class="rc-stat"><span>Admission</span><strong><?= patient_docs_h($admission_date ?: 'Not recorded') ?></strong></div>
    </div>
</div>

<div class="rc-tabs rc-tabs-pill">
    <a href="docspatient.php?patient_id=<?= $patientId ?>&tab=create" class="rc-tab <?= $tab === 'create' ? 'is-active' : '' ?>">Create Documents</a>
    <a href="docspatient.php?patient_id=<?= $patientId ?>&tab=gallery" class="rc-tab <?= $tab === 'gallery' ? 'is-active' : '' ?>">Document Gallery</a>
    <a href="docspatient.php?patient_id=<?= $patientId ?>&tab=images" class="rc-tab <?= $tab === 'images' ? 'is-active' : '' ?>">Image Manager</a>
</div>

<?php if ($tab === 'create'): ?>
    <div class="rc-panel">
        <h2>Create / Generate Documents</h2>
        <?php if (!$pdfEngineInstalled): ?>
            <div class="rc-alert amber">
                Server-side PDF generation is incomplete. Upload the complete <strong>new/lib/tcpdf</strong> folder.
                Missing: <?= patient_docs_h(implode(', ', $pdfEngineMissingFiles)) ?>
            </div>
        <?php endif; ?>
        <p>Generate a printable document populated with the latest patient and admission details.</p>
        <div class="rc-card-grid">
            <div class="rc-card rc-card-muted">
                <h3>Transfer Document</h3>
                <p>Prepare patient details for transfer to another rescue centre, veterinary practice, or authorised recipient.</p>
                <a href="views/print/patient_transfer_pdf.php?patient_id=<?= $patientId ?>" target="_blank" class="btn red">Generate Transfer PDF</a>
                <form method="post" class="xform">
                    <input type="hidden" name="email_generated_document" value="1">
                    <input type="hidden" name="document_kind" value="transfer">
                    <input type="hidden" name="csrf_token" value="<?= patient_docs_h($_SESSION['csrf_token']) ?>">
                    <div class="rc-upload-row rc-delete-row">
                        <div class="xform-field">
                            <label class="xform-label" for="transfer-recipient">Email document to</label>
                            <input id="transfer-recipient" type="email" name="recipient_email" class="xform-input" placeholder="recipient@example.com" required>
                        </div>
                        <div><button type="submit" class="btn red">Send</button></div>
                    </div>
                </form>
            </div>
            <div class="rc-card rc-card-muted">
                <h3>Handoff Document</h3>
                <p>Create a concise handoff summary for staff changes, transport, or continuation of care.</p>
                <a href="views/print/patient_handoff_pdf.php?patient_id=<?= $patientId ?>" target="_blank" class="btn orange">Generate Handoff PDF</a>
                <form method="post" class="xform">
                    <input type="hidden" name="email_generated_document" value="1">
                    <input type="hidden" name="document_kind" value="handoff">
                    <input type="hidden" name="csrf_token" value="<?= patient_docs_h($_SESSION['csrf_token']) ?>">
                    <div class="rc-upload-row rc-delete-row">
                        <div class="xform-field">
                            <label class="xform-label" for="handoff-recipient">Email document to</label>
                            <input id="handoff-recipient" type="email" name="recipient_email" class="xform-input" placeholder="recipient@example.com" required>
                        </div>
                        <div><button type="submit" class="btn orange">Send</button></div>
                    </div>
                </form>
            </div>
            <div class="rc-card rc-card-muted">
                <h3>Full Patient Record</h3>
                <p>Generate the complete patient record as a styled server-side PDF.</p>
                <a href="views/print/patient_record_pdf.php?patient_id=<?= $patientId ?>" target="_blank" class="btn blue">Generate Full Record PDF</a>
                <form method="post" class="xform">
                    <input type="hidden" name="email_generated_document" value="1">
                    <input type="hidden" name="document_kind" value="record">
                    <input type="hidden" name="csrf_token" value="<?= patient_docs_h($_SESSION['csrf_token']) ?>">
                    <div class="rc-upload-row rc-delete-row">
                        <div class="xform-field">
                            <label class="xform-label" for="record-recipient">Email document to</label>
                            <input id="record-recipient" type="email" name="recipient_email" class="xform-input" placeholder="recipient@example.com" required>
                        </div>
                        <div><button type="submit" class="btn blue">Send</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php elseif ($tab === 'gallery'): ?>
    <div class="rc-panel">
        <h2>Upload Document</h2>
        <form method="post" enctype="multipart/form-data" class="xform">
            <input type="hidden" name="upload_patient_document" value="1">
            <input type="hidden" name="csrf_token" value="<?= patient_docs_h($_SESSION['csrf_token']) ?>">
            <div class="rc-upload-row">
                <div class="xform-field">
                    <label class="xform-label" for="patient_document">Document</label>
                    <input id="patient_document" type="file" name="patient_document" class="xform-input" required accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png,.webp">
                    <small>Documents: maximum 10 MB. Images: maximum 3 MB and automatically resized/compressed to JPEG.</small>
                </div>
                <div class="rc-upload-action">
                    <button type="submit" class="btn red">Upload Document</button>
                </div>
            </div>
        </form>
    </div>

    <div class="rc-panel">
        <h2>Document Gallery</h2>
        <?php if (!$documents): ?>
            <div class="rc-alert blue">No documents have been uploaded for this patient.</div>
        <?php else: ?>
            <div class="rc-card-grid-4">
                <?php foreach ($documents as $document): ?>
                    <div class="rc-card rc-card-muted">
                        <div class="rc-document-thumb">
                            <?php if ($document['has_thumbnail']): ?>
                                <img class="rc-document-preview" src="docspatient.php?<?= patient_docs_h(http_build_query(['patient_id' => $patientId, 'thumbnail' => $document['name']])) ?>" alt="Preview of <?= patient_docs_h($document['name']) ?>">
                                <div class="rc-document-filetype rc-document-preview-fallback">
                                    <strong><?= patient_docs_h($document['extension'] ?: 'FILE') ?></strong>
                                    <span>Document</span>
                                </div>
                            <?php else: ?>
                                <div class="rc-document-filetype">
                                    <strong><?= patient_docs_h($document['extension'] ?: 'FILE') ?></strong>
                                    <span>Document</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3 class="rc-document-name" title="<?= patient_docs_h($document['name']) ?>"><?= patient_docs_h($document['name']) ?></h3>
                        <p class="rc-document-meta">
                            <span><?= patient_docs_h(date('d-m-Y H:i', $document['modified'])) ?></span>
                            <span><?= number_format($document['size'] / 1024, 1) ?> KB</span>
                        </p>
                        <a href="docspatient.php?<?= patient_docs_h(http_build_query(['patient_id' => $patientId, 'download' => $document['name']])) ?>" class="btn blue">Download Document</a>
                        <?php if ($canDeleteDocuments): ?>
                            <form method="post" class="xform rc-delete-row">
                                <input type="hidden" name="delete_patient_document" value="1">
                                <input type="hidden" name="document_name" value="<?= patient_docs_h($document['name']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= patient_docs_h($_SESSION['csrf_token']) ?>">
                                <div class="xform-field">
                                    <label class="xform-label" for="delete-password-<?= patient_docs_h(md5($document['name'])) ?>">Password</label>
                                    <input id="delete-password-<?= patient_docs_h(md5($document['name'])) ?>" type="password" name="password" class="xform-input" autocomplete="current-password" required>
                                </div>
                                <button type="submit" class="btn red" onclick="return confirm('Remove this document permanently?')">Remove</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="rc-panel">
        <h2>Upload Patient Image</h2>
        <form method="post" enctype="multipart/form-data" class="xform">
            <input type="hidden" name="upload_patient_image" value="1">
            <input type="hidden" name="csrf_token" value="<?= patient_docs_h($_SESSION['csrf_token']) ?>">
            <div class="rc-upload-row">
                <div class="xform-field">
                    <label class="xform-label" for="patient_image">Image</label>
                    <input id="patient_image" type="file" name="patient_image" class="xform-input" required accept=".jpg,.jpeg,.png,.webp">
                    <small>Maximum 3 MB. Images are resized and compressed to JPEG.</small>
                </div>
                <div class="rc-upload-action">
                    <button type="submit" class="btn blue">Upload Image</button>
                </div>
            </div>
        </form>
    </div>

    <div class="rc-panel">
        <h2>Patient Image Gallery</h2>
        <?php if (!$patientImages): ?>
            <div class="rc-alert blue">No images have been uploaded for this patient.</div>
        <?php else: ?>
            <div class="rc-card-grid-4">
                <?php foreach ($patientImages as $image): ?>
                    <div class="rc-card rc-card-muted">
                        <div class="rc-document-thumb">
                            <img class="rc-document-preview" src="<?= patient_docs_h($image['preview_url']) ?>" alt="<?= patient_docs_h($image['file_name'] ?: 'Patient image') ?>">
                            <div class="rc-document-filetype rc-document-preview-fallback"><strong>IMG</strong><span>Image</span></div>
                        </div>
                        <h3 class="rc-document-name" title="<?= patient_docs_h($image['file_name']) ?>"><?= patient_docs_h($image['file_name'] ?: 'Patient image') ?></h3>
                        <?php if ((int)$image['care_note_count'] > 0): ?>
                            <div class="rc-alert blue">Attached to <?= (int)$image['care_note_count'] ?> care note<?= (int)$image['care_note_count'] === 1 ? '' : 's' ?>.</div>
                            <?php if ($canDeleteImages): ?>
                                <form method="post" class="xform rc-delete-row">
                                    <input type="hidden" name="unlink_patient_image" value="1">
                                    <input type="hidden" name="image_id" value="<?= (int)$image['image_id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= patient_docs_h($_SESSION['csrf_token']) ?>">
                                    <div class="xform-field">
                                        <label class="xform-label" for="unlink-image-password-<?= (int)$image['image_id'] ?>">Password</label>
                                        <input id="unlink-image-password-<?= (int)$image['image_id'] ?>" type="password" name="password" class="xform-input" autocomplete="current-password" required>
                                    </div>
                                    <button type="submit" class="btn orange" onclick="return confirm('Unlink this image from all attached care notes?')">Unlink</button>
                                </form>
                            <?php endif; ?>
                        <?php elseif (!empty($image['is_legacy'])): ?>
                            <div class="rc-alert grey">Legacy image. Removal is unavailable.</div>
                        <?php elseif ($canDeleteImages): ?>
                            <form method="post" class="xform rc-delete-row">
                                <input type="hidden" name="delete_patient_image" value="1">
                                <input type="hidden" name="image_id" value="<?= (int)$image['image_id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= patient_docs_h($_SESSION['csrf_token']) ?>">
                                <div class="xform-field">
                                    <label class="xform-label" for="delete-image-password-<?= (int)$image['image_id'] ?>">Password</label>
                                    <input id="delete-image-password-<?= (int)$image['image_id'] ?>" type="password" name="password" class="xform-input" autocomplete="current-password" required>
                                </div>
                                <button type="submit" class="btn red" onclick="return confirm('Remove this patient image permanently?')">Remove</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
document.querySelectorAll('.rc-document-preview').forEach(function (preview) {
    preview.addEventListener('error', function () {
        preview.style.display = 'none';
        const fallback = preview.nextElementSibling;
        if (fallback) fallback.style.display = 'flex';
    });
});
</script>

<?= template_admin_footer() ?>
