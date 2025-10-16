<?php
include 'main.php';
// No need for the user to see the login form if they're logged-in, so redirect them to the home page
if (isset($_SESSION['account_loggedin'])) {
	// If the user is logged in, redirect to the home page.
    header('Location: dashboard.php');
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
		// Update last seen date
		$date = date('Y-m-d\TH:i:s');
		$stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
		$stmt->execute([ $date, $account['id'] ]);
		// Redirect to home page
        header('Location: dashboard.php');
		exit;
	}
}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,minimum-scale=1">
		<title>Member Login</title>
		<link href="style.css" rel="stylesheet" type="text/css">
		<style>
body {
  background-image: url('img/woodlandscene-1.png');
  background-size: cover;
}
</style>
	</head>
	.
	<body>
		<div class="login">
			
			<div class="icon blue">
				<!-- You could add your own site logo or icon here 26 -->
				 <img src="img/logo-square-white-cropped.png" width="50px" height="50px">
			</div>

			<h1>Rescue Centre Login</h1>

			<form action="authenticate.php" method="post" class="form login-form">

				<label class="form-label" for="username">Username</label>
				<div class="form-group">
					<svg class="form-icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512H418.3c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304H178.3z"/></svg>
					<input class="form-input" type="text" name="username" placeholder="Username" id="username" required>
				</div>

				<label class="form-label" for="password">Password</label>
				<div class="form-group">
					<svg class="form-icon-left" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M144 144v48H304V144c0-44.2-35.8-80-80-80s-80 35.8-80 80zM80 192V144C80 64.5 144.5 0 224 0s144 64.5 144 144v48h16c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V256c0-35.3 28.7-64 64-64H80z"/></svg>
					<input class="form-input" type="password" name="password" placeholder="Password" id="password" required>
				</div>

				<div class="form-group pad-y-5">
					<label id="remember_me">
						<input type="checkbox" name="remember_me">Remember me
					</label>
					<a href="forgot-password.php" class="form-link">Forgot password?</a>
				</div>
				
				<div class="msg"></div>
							
				<button class="btn blue" type="submit">Login</button>

				<p class="register-link">Don't have an account? <a href="register.php" class="form-link">Register</a></p>

			</form>
		</div>
&nbsp;
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
	</body>
</html>