<?php
include 'main.php';
// No need for the user to see the login form if they're logged-in, so redirect them to the home page
if (isset($_SESSION['account_loggedin'])) {
	// If the user is logged in, redirect to the home page.
    header('Location: home.php');
    exit;
}
// Also check if they are "remembered"
if (isset($_COOKIE['remember_me']) && !empty($_COOKIE['remember_me'])) {
	// If the remember me cookie matches one in the database then we can update the session variables and the user will be logged-in.
	$stmt = $pdo->prepare('SELECT * FROM accounts WHERE remember_me_code = ?');
	$stmt->execute([ $_COOKIE['remember_me'] ]);
	$account = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($account) {
		// Authenticate the user
session_regenerate_id();
$_SESSION['account_loggedin'] = TRUE;
$_SESSION['account_name'] = $account['username'];
$_SESSION['account_id'] = $account['id'];
$_SESSION['centre_id'] = $account['centre_id'];
$_SESSION['account_role'] = $account['role'];

// NEW: secondary access + onboarding
$_SESSION['vet_id'] = $account['vet_id'] ?? null;
$_SESSION['ngo_id'] = $account['ngo_id'] ?? null;
$_SESSION['vet_ok'] = isset($account['vet_ok']) ? (int)$account['vet_ok'] : 0;
$_SESSION['ngo_ok'] = isset($account['ngo_ok']) ? (int)$account['ngo_ok'] : 0;
$_SESSION['onboarded'] = isset($account['onboarded']) ? (int)$account['onboarded'] : 0;

		$_SESSION['account_role'] = $account['role'];
		// Update last seen date
		$date = date('Y-m-d\TH:i:s');
		$stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
		$stmt->execute([ $date, $account['id'] ]);
		// Redirect to home page
        header('Location: home.php');
		exit;
	}
}
$favicon_path = __DIR__ . '/img/favicon.ico';
$favicon_version = is_file($favicon_path) ? filemtime($favicon_path) : time();

$login_centre_name = 'MyRescueCentre';
$login_centre_logo = '';
$login_centre_initials = 'RC';

try {
	$stmt = $pdo->query("
		SELECT rc.rescue_name, cm.centre_logo
		FROM rescue_centres rc
		LEFT JOIN rescue_centre_meta cm
			ON cm.centre_id = rc.rescue_id
		ORDER BY rc.rescue_id ASC
		LIMIT 1
	");
	$centre_brand = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
	if ($centre_brand) {
		$login_centre_name = trim((string)($centre_brand['rescue_name'] ?? '')) ?: $login_centre_name;
		$logo = trim((string)($centre_brand['centre_logo'] ?? ''));
		if ($logo !== '' && strpos($logo, 'placeholder') === false) {
			$login_centre_logo = $logo;
		}
	}
} catch (Throwable $e) {
	// Keep the login screen usable before/around first install.
}

$initial_words = preg_split('/\s+/', preg_replace('/[^A-Za-z0-9 ]/', ' ', $login_centre_name) ?: '', -1, PREG_SPLIT_NO_EMPTY);
if ($initial_words) {
	$login_centre_initials = strtoupper(substr((string)$initial_words[0], 0, 1) . substr((string)($initial_words[1] ?? $initial_words[0]), 0, 1));
}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,minimum-scale=1">
		<title><?= htmlspecialchars($login_centre_name, ENT_QUOTES, 'UTF-8') ?> - Rescue Centre Lite</title>
		<link href="style.css" rel="stylesheet" type="text/css">
		<link rel="icon" type="image/x-icon" href="<?= htmlspecialchars(base_url . 'img/favicon.ico?v=' . $favicon_version, ENT_QUOTES, 'UTF-8') ?>">
		<link rel="shortcut icon" type="image/x-icon" href="<?= htmlspecialchars(base_url . 'img/favicon.ico?v=' . $favicon_version, ENT_QUOTES, 'UTF-8') ?>">
		<meta property="og:type" content="website">
		<meta property="og:site_name" content="MyRescueCentre">
		<meta property="og:title" content="MyRescueCentre – RescueCentre Login">
		<meta property="og:description" content="Secure access to the RescueCentre platform for rescue organisations worldwide.">
		<meta property="og:url" content="https://myrescuecentre.com/">
		<meta property="og:image" content="https://myrescuecentre.com/myrescuecentrelogin.png">
		<meta property="og:image:secure_url" content="https://myrescuecentre.com/myrescuecentrelogin.png">
		<meta property="og:image:width" content="1200">
		<meta property="og:image:height" content="630">
	</head>
	<body>
<div class="login-page month-<?= htmlspecialchars($GLOBALS['login_month_key'], ENT_QUOTES, 'UTF-8'); ?>">
		<div class="login">
			
			<div class="login-brand">
				<?php if ($login_centre_logo !== ''): ?>
					<img src="<?= htmlspecialchars($login_centre_logo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($login_centre_name, ENT_QUOTES, 'UTF-8') ?> logo">
				<?php else: ?>
					<div class="login-brand-initials" aria-hidden="true"><?= htmlspecialchars($login_centre_initials, ENT_QUOTES, 'UTF-8') ?></div>
				<?php endif; ?>
			</div>

			<h1><?= htmlspecialchars($login_centre_name, ENT_QUOTES, 'UTF-8') ?></h1>
			<p class="login-subtitle">Rescue Centre Lite</p>

			<form action="authenticate.php" method="post" class="form login-form">

				<label class="form-label" for="identity">Username or email</label>
				<div class="form-group">
					<svg class="form-icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z"/></svg>
					<input class="form-input" type="text" name="identity" placeholder="Username or email" id="identity" required autofocus autocomplete="username">
				</div>

				<label class="form-label" for="password">Password</label>
					<div class="form-group">
						<svg class="form-icon-left" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 448 512"><path d="M144 144v48H304V144c0-44.2-35.8-80-80-80s-80 35.8-80 80zM80 192V144C80 64.5 144.5 0 224 0s144 64.5 144 144v48h16c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V256c0-35.3 28.7-64 64-64H80z"/></svg>
						<input class="form-input" type="password" name="password" placeholder="Password" id="password" required autocomplete="current-password">
						<button type="button" id="togglePassword" aria-label="Show password" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:transparent; border:0; padding:6px; cursor:pointer;">
						<svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
							<path fill="#98a0a8" d="M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Zm0-2.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
						</svg>
						</button>
					</div>


				<div class="form-group pad-y-5">
					<label id="remember_me">
						<input type="checkbox" name="remember_me">Remember me
					</label>
					<a href="forgot-password.php" class="form-link">Forgot password?</a>
				</div>
				
				<div class="msg"></div>
							
				<button class="btn blue" type="submit">Login</button>
				<br>
				
			</form>
		</div>
	</div>
		<script>
		// AJAX code
		const loginForm = document.querySelector('.login-form');
		loginForm.onsubmit = event => {
			event.preventDefault();
			fetch(loginForm.action, { method: 'POST', body: new FormData(loginForm), cache: 'no-store' }).then(response => response.text()).then(result => {
				if (result.toLowerCase().includes('success:')) {
					loginForm.querySelector('.msg').classList.remove('error','success');
					loginForm.querySelector('.msg').classList.add('success');
					loginForm.querySelector('.msg').innerHTML = result.replace('Success: ', '');
				} else if (result.toLowerCase().includes('redirect:')) {
					window.location.href = result.replace('Redirect:', '').trim();
				} else {
					loginForm.querySelector('.msg').classList.remove('error','success');
					loginForm.querySelector('.msg').classList.add('error');
					loginForm.querySelector('.msg').innerHTML = result.replace('Error: ', '');
				}
			});
		};
		</script>
		<script src="https://accounts.google.com/gsi/client" async defer></script>

<script>
  const pwd = document.getElementById('password');
  const btn = document.getElementById('togglePassword');
  const icon = document.getElementById('eyeIcon');
  if (pwd && btn) {
    btn.addEventListener('click', () => {
      const showing = pwd.type === 'text';
      pwd.type = showing ? 'password' : 'text';
      btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
      // Swap icon (eye / eye-off) by simple path swap
      icon.innerHTML = showing
        ? '<path fill="#98a0a8" d="M12 5c-7 0-11 7-11 7s4 7 11 7 11-7 11-7-4-7-11-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Zm0-2.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>'
        : '<path fill="#98a0a8" d="M2.1 3.5 3.5 2.1 21.9 20.5 20.5 21.9l-2.2-2.2A12.9 12.9 0 0 1 12 19c-7 0-11-7-11-7a19 19 0 0 1 5.2-5.9L2.1 3.5ZM12 7a5 5 0 0 1 5 5c0 .6-.1 1.2-.3 1.7l-1.6-1.6c0-2-1.6-3.6-3.6-3.6l-1.6-1.6c.5-.2 1.1-.3 1.7-.3Zm-5 5c0-.6.1-1.2.3-1.7l6.4 6.4c-.5.2-1.1.3-1.7.3a5 5 0 0 1-5-5Zm10.8 4.7-2-2A7 7 0 0 0 9.3 8.2l-2-2A12 12 0 0 1 12 5c7 0 11 7 11 7a19 19 0 0 1-5.2 5.7Z"/>';
    });
  }
</script>

	</body>
</html>
