<?php
// Shared MFA helpers for step-up verification on sensitive actions.

if (!function_exists('rc_mfa_h')) {
    function rc_mfa_h($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

function rc_mfa_base32_encode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    $encoded = '';

    for ($i = 0, $len = strlen($data); $i < $len; $i++) {
        $bits .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
    }

    foreach (str_split($bits, 5) as $chunk) {
        if (strlen($chunk) < 5) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        }
        $encoded .= $alphabet[bindec($chunk)];
    }

    return $encoded;
}

function rc_mfa_base32_decode(string $secret): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $secret));
    $bits = '';
    $decoded = '';

    for ($i = 0, $len = strlen($secret); $i < $len; $i++) {
        $pos = strpos($alphabet, $secret[$i]);
        if ($pos === false) {
            continue;
        }
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }

    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) === 8) {
            $decoded .= chr(bindec($byte));
        }
    }

    return $decoded;
}

function rc_mfa_generate_totp_secret(int $bytes = 20): string {
    return rc_mfa_base32_encode(random_bytes($bytes));
}

function rc_mfa_totp_code(string $secret, ?int $time = null): string {
    $key = rc_mfa_base32_decode($secret);
    if ($key === '') {
        return '';
    }

    $counter = intdiv($time ?? time(), 30);
    $binaryCounter = pack('N2', 0, $counter);
    $hash = hash_hmac('sha1', $binaryCounter, $key, true);
    $offset = ord($hash[19]) & 0x0f;
    $value = (
        ((ord($hash[$offset]) & 0x7f) << 24) |
        ((ord($hash[$offset + 1]) & 0xff) << 16) |
        ((ord($hash[$offset + 2]) & 0xff) << 8) |
        (ord($hash[$offset + 3]) & 0xff)
    );

    return str_pad((string)($value % 1000000), 6, '0', STR_PAD_LEFT);
}

function rc_mfa_verify_totp(string $secret, string $code, int $window = 1): bool {
    $code = preg_replace('/\D+/', '', $code);
    if (strlen($code) !== 6) {
        return false;
    }

    $now = time();
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(rc_mfa_totp_code($secret, $now + ($i * 30)), $code)) {
            return true;
        }
    }

    return false;
}

function rc_mfa_totp_uri(string $issuer, string $accountLabel, string $secret): string {
    $issuer = trim($issuer) !== '' ? trim($issuer) : 'RescueCentre';
    $label = $issuer . ':' . $accountLabel;

    return 'otpauth://totp/' . rawurlencode($label)
        . '?secret=' . rawurlencode($secret)
        . '&issuer=' . rawurlencode($issuer)
        . '&algorithm=SHA1&digits=6&period=30';
}

function rc_mfa_qr_url(string $uri, int $size = 220): string {
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . (int)$size . 'x' . (int)$size
        . '&format=png&data=' . urlencode($uri);
}

function rc_mfa_session_allows(string $purpose, ?int $targetId = null): bool {
    if (empty($_SESSION['mfa_verified_until']) || (int)$_SESSION['mfa_verified_until'] < time()) {
        return false;
    }
    if (empty($_SESSION['mfa_verified_purpose']) || (string)$_SESSION['mfa_verified_purpose'] !== $purpose) {
        return false;
    }
    if ($targetId === null) {
        return true;
    }

    return (string)($_SESSION['mfa_verified_target'] ?? '') === (string)$targetId;
}

function rc_mfa_mark_verified(string $purpose, ?int $targetId = null, int $seconds = 600): void {
    $_SESSION['mfa_verified_until'] = time() + $seconds;
    $_SESSION['mfa_verified_purpose'] = $purpose;
    $_SESSION['mfa_verified_target'] = $targetId;
}

function rc_mfa_safe_return(string $url, string $fallback = '/management.php'): string {
    if ($url === '' || $url[0] !== '/') {
        return $fallback;
    }
    if (strpos($url, '//') === 0 || preg_match('~^https?://~i', $url)) {
        return $fallback;
    }
    return $url;
}

function rc_mfa_redirect_url(string $purpose, ?int $targetId, string $return): string {
    return '/mfa_verify.php?purpose=' . urlencode($purpose)
        . ($targetId !== null ? '&target=' . (int)$targetId : '')
        . '&return=' . urlencode(rc_mfa_safe_return($return));
}

function rc_mfa_centre_enabled(PDO $pdo, int $centreId): bool {
    $stmt = $pdo->prepare('SELECT mfa_enabled FROM rescue_centre_meta WHERE centre_id = ? LIMIT 1');
    $stmt->execute([$centreId]);
    return (int)$stmt->fetchColumn() === 1;
}

function rc_mfa_centre_totp_enabled(PDO $pdo, int $centreId): bool {
    $stmt = $pdo->prepare('SELECT mfa_totp_enabled FROM rescue_centre_meta WHERE centre_id = ? LIMIT 1');
    $stmt->execute([$centreId]);
    return (int)$stmt->fetchColumn() === 1;
}

function rc_mfa_require(PDO $pdo, int $centreId, string $purpose, ?int $targetId, string $return): void {
    if (!rc_mfa_centre_enabled($pdo, $centreId) || rc_mfa_session_allows($purpose, $targetId)) {
        return;
    }

    $dest = rc_mfa_redirect_url($purpose, $targetId, $return);
    if (!headers_sent()) {
        header('Location: ' . $dest);
        exit;
    }

    echo '<div class="rc-alert blue">Redirecting to verification...</div>';
    echo '<script>window.location.href=' . json_encode($dest) . ';</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . rc_mfa_h($dest) . '"></noscript>';
    exit;
}
