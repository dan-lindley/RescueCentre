<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Turn warnings/notices into exceptions so we ALWAYS return JSON
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    // Must be logged in
    $account_id = (int)($_SESSION['account_id'] ?? 0);
    if ($account_id <= 0) {
        respond(401, ['status' => 'error', 'message' => 'Not logged in']);
    }

    // Read JSON body
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        respond(400, ['status' => 'error', 'message' => 'Invalid JSON']);
    }

    $to        = trim((string)($data['to'] ?? ''));
    $subject   = trim((string)($data['subject'] ?? 'Certificate'));
    $message   = trim((string)($data['message'] ?? ''));
    $pngDataUrl = (string)($data['png_data_url'] ?? '');

    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        respond(400, ['status' => 'error', 'message' => 'Invalid email address']);
    }

    if ($pngDataUrl === '' || strpos($pngDataUrl, 'data:image/png;base64,') !== 0) {
        respond(400, ['status' => 'error', 'message' => 'Invalid PNG data']);
    }

    $base64 = substr($pngDataUrl, strlen('data:image/png;base64,'));
    $pngBinary = base64_decode($base64, true);
    if ($pngBinary === false) {
        respond(400, ['status' => 'error', 'message' => 'Failed to decode PNG']);
    }

    // PHPMailer flat layout (your structure)
    $phpmailerDir = realpath(__DIR__ . '/../lib/phpmailer');
    if (!$phpmailerDir) {
        respond(500, ['status' => 'error', 'message' => 'PHPMailer folder not found: /lib/phpmailer']);
    }

    $need = [
        $phpmailerDir . '/Exception.php',
        $phpmailerDir . '/PHPMailer.php',
        $phpmailerDir . '/SMTP.php',
    ];
    foreach ($need as $f) {
        if (!is_file($f)) {
            respond(500, ['status' => 'error', 'message' => 'Missing PHPMailer file: ' . $f]);
        }
    }

    require_once $phpmailerDir . '/Exception.php';
    require_once $phpmailerDir . '/PHPMailer.php';
    require_once $phpmailerDir . '/SMTP.php';

    // Instantiate
    if (!class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
        respond(500, ['status' => 'error', 'message' => 'PHPMailer class not found after include']);
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    // Use PHP's mail() transport (we can switch to SMTP if needed)
    $mail->isMail();

    // From address (some hosts require this to be a real mailbox on your domain)
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'example.com');
    $hostClean = preg_replace('/[^a-z0-9\.\-]/i', '', (string)$host);
    $fromEmail = 'no-reply@' . ($hostClean ?: 'example.com');

    $mail->setFrom($fromEmail, 'Rescue Centre');
    $mail->addAddress($to);

    $mail->Subject = ($subject !== '') ? $subject : 'Certificate';

    $body = ($message !== '') ? $message : "Please find your certificate attached.";
    $mail->Body = $body;
    $mail->AltBody = $body;

    // Attach PNG from memory
    $mail->addStringAttachment($pngBinary, 'certificate.png', 'base64', 'image/png');

    $mail->send();

    respond(200, ['status' => 'success', 'message' => 'Sent']);

} catch (\PHPMailer\PHPMailer\Exception $e) {
    respond(500, ['status' => 'error', 'message' => 'PHPMailer: ' . $e->getMessage()]);
} catch (\Throwable $e) {
    respond(500, ['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
