<?php
// /mfa_verify.php
// Clean reusable MFA gate (email OTP)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_start(); // allow safe redirects

include 'dashmain.php';
require_once __DIR__ . '/core/mfa.php';

$user_id   = (int)$GLOBALS['user_id'];
$centre_id = (int)$GLOBALS['centre_id'];

if (!$user_id || !$centre_id) {
    ob_end_clean();
    echo "Not authorised.";
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function redirect_to(string $url): void {
    if (!headers_sent()) {
        header("Location: $url");
        exit;
    }
    echo '<script>window.location.href=' . json_encode($url) . ';</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></noscript>';
    exit;
}

function get_user_email(PDO $pdo, int $user_id): string {
    $stmt = $pdo->prepare("SELECT email FROM accounts WHERE id=? LIMIT 1");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['email'] : '';
}

function get_mfa_account(PDO $pdo, int $user_id): array {
    $stmt = $pdo->prepare("SELECT email, username, totp_secret, totp_enabled FROM accounts WHERE id=? LIMIT 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function send_email_code(string $to, string $code): bool {
    $subject = 'Your RescueCentre verification code';
    $bodyTxt = "Your verification code is: {$code}\n\nThis code expires in 5 minutes.";
    $bodyHtml = "<p>Your verification code is:</p><div style=\"font-size:28px;font-weight:bold;letter-spacing:2px;\">{$code}</div><p>This code expires in 5 minutes.</p>";

    $dir = __DIR__ . '/lib/phpmailer/';
    $phpp  = $dir . 'PHPMailer.php';
    $excp  = $dir . 'Exception.php';
    $smtpp = $dir . 'SMTP.php';

    if (!is_file($phpp) || !is_file($excp) || !is_file($smtpp)) {
        return false;
    }

    require_once $excp;
    require_once $phpp;
    require_once $smtpp;

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // If your app configures SMTP elsewhere, keep it there.
        // We do not assume SMTP constants here.

        $fromEmail = 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($fromEmail, 'RescueCentre');
        $mail->addAddress($to);

        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $bodyHtml;
        $mail->AltBody = $bodyTxt;

        $mail->send();
        return true;

    } catch (Throwable $e) {
        return false;
    }
}


$purpose = isset($_GET['purpose']) ? trim($_GET['purpose']) : '';
$target  = isset($_GET['target']) ? (int)$_GET['target'] : null;
$return  = rc_mfa_safe_return($_GET['return'] ?? '/management.php');

if (!$purpose) {
    ob_end_clean();
    echo "Invalid request.";
    exit;
}

// Ensure table exists
$stmt = $pdo->query("SHOW TABLES LIKE 'rescue_mfa_challenges'");
if (!$stmt->fetchColumn()) {
    ob_end_clean();
    echo "MFA table missing.";
    exit;
}

$msg = "";
$account = get_mfa_account($pdo, $user_id);
$totpAvailable = rc_mfa_centre_totp_enabled($pdo, $centre_id)
    && !empty($account['totp_enabled'])
    && !empty($account['totp_secret']);
$requestedMethod = (string)($_GET['method'] ?? ($totpAvailable ? 'totp' : 'email'));
if (!in_array($requestedMethod, ['totp', 'email'], true)) {
    $requestedMethod = $totpAvailable ? 'totp' : 'email';
}
if ($requestedMethod === 'totp' && !$totpAvailable) {
    $requestedMethod = 'email';
}

/* -------------------------
   VERIFY POST
-------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_mfa') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $msg = '<div class="alert alert-danger">Invalid request.</div>';
    } else {

        $code = preg_replace('/\D+/', '', $_POST['code'] ?? '');
        $method = (string)($_POST['method'] ?? $requestedMethod);

        if (strlen($code) !== 6) {
            $msg = '<div class="alert alert-danger">Please enter the 6-digit code.</div>';
        } elseif ($method === 'totp' && $totpAvailable) {
            if (!rc_mfa_verify_totp((string)$account['totp_secret'], $code)) {
                $msg = '<div class="alert alert-danger">Incorrect authenticator code.</div>';
            } else {
                rc_mfa_mark_verified($purpose, $target, 600);
                ob_end_clean();
                redirect_to($return);
            }
        } else {

            $stmt = $pdo->prepare("
                SELECT * FROM rescue_mfa_challenges
                WHERE user_id=? AND centre_id=? AND purpose=? AND status='pending'
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$user_id, $centre_id, $purpose]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $msg = '<div class="alert alert-danger">No active code.</div>';
            } elseif (strtotime($row['expires_at']) < time()) {
                $pdo->prepare("UPDATE rescue_mfa_challenges SET status='expired' WHERE id=?")
                    ->execute([$row['id']]);
                $msg = '<div class="alert alert-danger">Code expired.</div>';
            } elseif (!password_verify($code, $row['code_hash'])) {
                $pdo->prepare("UPDATE rescue_mfa_challenges SET attempts=attempts+1 WHERE id=?")
                    ->execute([$row['id']]);
                $msg = '<div class="alert alert-danger">Incorrect code.</div>';
            } else {

                $pdo->prepare("UPDATE rescue_mfa_challenges SET status='used', used_at=NOW() WHERE id=?")
                    ->execute([$row['id']]);

                rc_mfa_mark_verified($purpose, $target, 600);

                ob_end_clean();
                redirect_to($return);
            }
        }
    }
}

/* -------------------------
   SEND CODE (initial load)
-------------------------- */
$stmt = $pdo->prepare("
    SELECT id FROM rescue_mfa_challenges
    WHERE user_id=? AND centre_id=? AND purpose=? AND status='pending'
    ORDER BY id DESC LIMIT 1
");
$stmt->execute([$user_id, $centre_id, $purpose]);
$pending = $stmt->fetch(PDO::FETCH_ASSOC);

if ($requestedMethod === 'email' && !$pending) {
    $email = get_user_email($pdo, $user_id);

    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $code = random_int(100000, 999999);
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $expires = date('Y-m-d H:i:s', time() + 300);

        $pdo->prepare("
            INSERT INTO rescue_mfa_challenges
            (centre_id,user_id,purpose,target_id,method,code_hash,attempts,status,expires_at)
            VALUES (?,?,?,?, 'email', ?,0,'pending',?)
        ")->execute([$centre_id,$user_id,$purpose,$target,$hash,$expires]);

        send_email_code($email, (string)$code);
        $msg = '<div class="alert alert-success">Verification code sent.</div>';
    } else {
        $msg = '<div class="alert alert-danger">No valid email on account.</div>';
    }
}

$out = ob_get_clean();
echo $out;

template_admin_header("Verification Required");

?>

<div class="content-title">
  <div class="title">
    <div class="txt">
      <h2>Verification Required</h2>
      <p><?= $requestedMethod === 'totp' ? 'Enter the 6-digit code from your authenticator app.' : 'Enter the 6-digit code sent to your email.' ?></p>
    </div>
  </div>
</div>

<?= $msg ?>

<div class="card">
  <div class="card-body">
    <form method="post" id="otpForm">
      <input type="hidden" name="action" value="verify_mfa">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <input type="hidden" name="code" id="otp_code">
      <input type="hidden" name="method" value="<?= htmlspecialchars($requestedMethod, ENT_QUOTES) ?>">

      <div style="display:flex; gap:10px; margin:20px 0;">
        <?php for ($i=0;$i<6;$i++): ?>
          <input class="otp-digit" inputmode="numeric" maxlength="1">
        <?php endfor; ?>
      </div>

      <button type="submit" class="btn btn-primary">Verify</button>
      <a href="<?= htmlspecialchars($return) ?>" class="btn btn-light">Cancel</a>
      <?php if ($totpAvailable): ?>
        <?php if ($requestedMethod === 'totp'): ?>
          <a href="<?= htmlspecialchars('/mfa_verify.php?purpose=' . urlencode($purpose) . ($target !== null ? '&target=' . (int)$target : '') . '&method=email&return=' . urlencode($return), ENT_QUOTES) ?>" class="btn btn-light">Use Email Code</a>
        <?php else: ?>
          <a href="<?= htmlspecialchars('/mfa_verify.php?purpose=' . urlencode($purpose) . ($target !== null ? '&target=' . (int)$target : '') . '&method=totp&return=' . urlencode($return), ENT_QUOTES) ?>" class="btn btn-light">Use Authenticator</a>
        <?php endif; ?>
      <?php endif; ?>
    </form>
  </div>
</div>

<style>
.otp-digit {
    width:52px;
    height:58px;
    font-size:26px;
    text-align:center;
    border:2px solid #dcdcdc;
    border-radius:12px;
}
.otp-digit:focus {
    border-color:#28a745;
    box-shadow:0 0 0 3px rgba(40,167,69,0.2);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const boxes = document.querySelectorAll('.otp-digit');
  const hidden = document.getElementById('otp_code');
  const form = document.getElementById('otpForm');

  function sync() {
    hidden.value = Array.from(boxes).map(b => b.value).join('');
  }

  boxes.forEach((box,i)=>{
    box.addEventListener('input', ()=>{
      box.value = box.value.replace(/\D/g,'').slice(0,1);
      if (box.value && boxes[i+1]) boxes[i+1].focus();
      sync();
    });
    box.addEventListener('keydown',(e)=>{
      if(e.key==='Backspace' && !box.value && boxes[i-1]){
        boxes[i-1].focus();
      }
    });
  });

  boxes[0].focus();
  form.addEventListener('submit', sync);
});
</script>

<?php template_admin_footer(); ?>
