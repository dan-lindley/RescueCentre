<?php
// onboarding.php (PASTE-OVER) — only change is CSRF hidden field
include 'main.php';
check_loggedin($pdo);

if (isset($_SESSION['onboarded']) && (int)$_SESSION['onboarded'] === 1) {
    header('Location: home.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$role = $_SESSION['account_role'] ?? 'Member';

$title = 'Onboarding';
template_header($title);

$heading = 'Welcome — let’s get you set up';
$sub = 'You can update these details later.';
$name_label = 'Organisation Name';

if ($role === 'Member') {
    $name_label = 'Rescue / Rehab Name';
} elseif ($role === 'Vet') {
    $name_label = 'Practice Name';
} elseif ($role === 'NGO') {
    $name_label = 'Organisation Name';
}

$error = isset($_GET['error']) ? trim($_GET['error']) : '';
?>
<div class="">
    <div class="login">
        <div class="icon blue">
            <img src="img/logo-square-white-cropped.png" width="50" height="50" alt="">
        </div>

        <h1><?= htmlspecialchars($heading, ENT_QUOTES) ?></h1>
        <p style="margin-top:-6px; opacity:.9;"><?= htmlspecialchars($sub, ENT_QUOTES) ?></p>

        <?php if ($error): ?>
            <div class="msg error" style="display:block; margin-bottom:10px;">
                <?= htmlspecialchars($error, ENT_QUOTES) ?>
            </div>
        <?php endif; ?>

        <form action="onboarding_process.php" method="post" class="form login-form" style="margin-top:10px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">

            <label class="form-label" for="name"><?= htmlspecialchars($name_label, ENT_QUOTES) ?></label>
            <div class="form-group">
                <input class="form-input" type="text" name="name" id="name" required>
            </div>

            <?php if ($role === 'Vet'): ?>
                <label class="form-label" for="tel">Practice Telephone (optional)</label>
                <div class="form-group">
                    <input class="form-input" type="text" name="tel" id="tel">
                </div>
            <?php endif; ?>

            <?php if ($role === 'NGO'): ?>
                <label class="form-label" for="org_type">Organisation Type</label>
                <div class="form-group">
                    <select name="org_type" id="org_type" class="form-input" required>
                        <option value="">— Select —</option>
                        <option value="Charity">Charity</option>
                        <option value="Academic Institute">Academic Institute</option>
                        <option value="Government Body">Government Body</option>
                        <option value="Public Body">Public Body</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <label class="form-label" for="address">Address (optional)</label>
                <div class="form-group">
                    <input class="form-input" type="text" name="address" id="address">
                </div>
            <?php endif; ?>

            <br><button class="btn blue" type="submit">Continue</button>
        </form>
    </div>
</div>

<?php template_footer(); ?>
