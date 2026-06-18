<?php
declare(strict_types=1);

ini_set('display_errors', '0');

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }

    error_log('Handoff PDF fatal error: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }
    echo "\nHandoff PDF generation failed.\n" . $error['message'];
});

try {
    $document_kind = 'handoff';
    require __DIR__ . '/pdf/patient_document_pdf.php';
} catch (Throwable $e) {
    error_log('Handoff PDF generation failed: ' . $e);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }
    echo "Handoff PDF generation failed.\n";
    echo $e->getMessage();
}
