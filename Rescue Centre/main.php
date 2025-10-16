<?php
// The main file contains the database connection, session initializing, and functions, other PHP files will depend on this file.
// Include the configuration file
include_once 'config.php';
session_start();
// Namespaces
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Connect to the MySQL database using the PDO interface
try {
	$pdo = new PDO('mysql:host=' . db_host . ';dbname=' . db_name . ';charset=' . db_charset, db_user, db_pass);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to database: ' . $exception->getMessage());
}
// Template header function
function template_header($title) {
// Admin panel link - will only be visible if the user is an admin
	$admin_panel_link = isset($_SESSION['account_role']) && $_SESSION['account_role'] == 'Admin' ? '<a href="admin/index.php" target="_blank">Admin</a>' : '';
// Get the current file name (eg. home.php, profile.php)
	$current_file_name = basename($_SERVER['PHP_SELF']);

	// Indenting the below code may cause HTML validation errors
echo '<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,minimum-scale=1">
		<title>' . $title . '</title>
		<link href="style.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="style.css">
			
	</head>
	<body>

		<header class="header">

			<div class="wrapper">

				<h1>Rescue Centre</h1>

				<!-- If you prefer to use a logo instead of text uncomment the below code and remove the above h1 tag and replace the src attribute with the path to your logo image
				<img src="https://via.placeholder.com/200x45" width="200" height="45" alt="Logo" class="logo">
				-->

				<!-- Responsive menu toggle icon -->
				<input type="checkbox" id="menu">
				<label for="menu"></label>
				
				<nav class="menu">
				<a href="dashboard.php" class="' . ($current_file_name == 'dashboard.php' ? 'active' : '') . '">Dashboard</a>
					<a href="home.php" class="' . ($current_file_name == 'home.php' ? 'active' : '') . '">Home</a>
					<a href="profile.php" class="' . ($current_file_name == 'profile.php' ? 'active' : '') . '">Profile</a>
					' . $admin_panel_link . '
					<a href="logout.php" class="alt">
						<svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"/></svg>
						Logout
					</a>
				</nav>

			</div>

		</header>

		<div class="content">
';
}
// Template footer function
function template_footer() {
	// Output the footer HTML
	echo '</div>
	</body>
</html>';
}
	if (isset($_SESSION['account_loggedin'])) {
    $stmt = $pdo->prepare('SELECT centre_id FROM accounts WHERE id = ?');
    $stmt->execute([ $_SESSION['account_id'] ]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
	}
// The below function will check if the user is logged-in and also check the remember me cookie
function check_loggedin($pdo, $redirect_file = 'index.php') {
	// Comment the below code if you don't want to update the last seen date on each page load
	if (isset($_SESSION['account_loggedin'])) {
		$date = date('Y-m-d\TH:i:s');
		$stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
		$stmt->execute([ $date, $_SESSION['account_id'] ]);
	}	
	// Check for remember me cookie variable and loggedin session variable
    if (isset($_COOKIE['remember_me']) && !empty($_COOKIE['remember_me']) && !isset($_SESSION['account_loggedin'])) {
    	// If the remember me cookie matches one in the database then we can update the session variables.
    	$stmt = $pdo->prepare('SELECT * FROM accounts WHERE remember_me_code = ?');
    	$stmt->execute([ $_COOKIE['remember_me'] ]);
    	$account = $stmt->fetch(PDO::FETCH_ASSOC);
		// If account exists...
    	if ($account) {
    		// Found a match, update the session variables and keep the user logged-in
    		session_regenerate_id();
    		$_SESSION['account_loggedin'] = TRUE;
    		$_SESSION['account_name'] = $account['username'];
    		$_SESSION['account_id'] = $account['id'];
			$_SESSION['account_role'] = $account['role'];
		
			// Update last seen date
			$date = date('Y-m-d\TH:i:s');
				
			$stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
			$stmt->execute([ $date, $account['id'] ]);
    	} else {
    		// If the user is not remembered redirect to the login page.
    		header('Location: ' . $redirect_file);
    		exit;
    	}
    } else if (!isset($_SESSION['account_loggedin'])) {
    	// If the user is not logged in redirect to the login page.
    	header('Location: ' . $redirect_file);
    	exit;
    }
}
// Send activation email function
function send_activation_email($email, $code) {
	if (!mail_enabled) return;
	// Include PHPMailer library
	include_once 'lib/phpmailer/Exception.php';
	include_once 'lib/phpmailer/PHPMailer.php';
	include_once 'lib/phpmailer/SMTP.php';
	// Create an instance; passing `true` enables exceptions
	$mail = new PHPMailer(true);
	try {
		// Server settings
		if (SMTP) {
			$mail->isSMTP();
			$mail->Host = smtp_host;
			$mail->SMTPAuth = true;
			$mail->Username = smtp_user;
			$mail->Password = smtp_pass;
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
			$mail->Port = smtp_port;
		}
		// Recipients
		$mail->setFrom(mail_from, mail_name);
		$mail->addAddress($email);
		$mail->addReplyTo(mail_from, mail_name);
		// Content
		$mail->isHTML(true);
		$mail->Subject = 'Account Activation Required';
		// Activation link
		$activate_link = base_url . 'activate.php?code=' . $code;
		// Read the template contents and replace the "%link" placeholder with the above variable
		$email_template = str_replace('%link%', $activate_link, file_get_contents('activation-email-template.html'));
		// Email body content
		$body = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,minimum-scale=1"><title>Account Activation Required</title></head><body style="margin:0;padding:0">' . $email_template . '</body></html>';
		// Set email body
		$mail->Body = $body;
		$mail->AltBody = strip_tags($email_template);
		// Send mail
		$mail->send();
	} catch (Exception $e) {
		// Output error message
		exit('Error: Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
	}
}
// Send notification email function
function send_notification_email($account_id, $account_username, $account_email, $account_date) {
	if (!mail_enabled) return;
	// Include PHPMailer library
	include_once 'lib/phpmailer/Exception.php';
	include_once 'lib/phpmailer/PHPMailer.php';
	include_once 'lib/phpmailer/SMTP.php';
	// Create an instance; passing `true` enables exceptions
	$mail = new PHPMailer(true);
	try {
		// Server settings
		if (SMTP) {
			$mail->isSMTP();
			$mail->Host = smtp_host;
			$mail->SMTPAuth = true;
			$mail->Username = smtp_user;
			$mail->Password = smtp_pass;
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
			$mail->Port = smtp_port;
		}
		// Recipients
		$mail->setFrom(mail_from, mail_name);
		$mail->addAddress(notification_email);
		$mail->addReplyTo(mail_from, mail_name);
		// Content
		$mail->isHTML(true);
		$mail->Subject = 'A new user has registered!';
		// Read the template contents and replace the "%link" placeholder with the above variable
		$email_template = str_replace(['%id%','%username%','%date%','%email%'], [$account_id, htmlspecialchars($account_username, ENT_QUOTES), $account_date, $account_email], file_get_contents('notification-email-template.html'));
		// Email body content
		$body = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,minimum-scale=1"><title>A new user has registered!</title></head><body style="margin:0;padding:0">' . $email_template . '</body></html>';
		// Set email body
		$mail->Body = $body;
		$mail->AltBody = strip_tags($email_template);
		// Send mail
		$mail->send();
	} catch (Exception $e) {
		// Output error message
		exit('Error: Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
	}
}
// Send password reset email function
function send_password_reset_email($email, $username, $code) {
	if (!mail_enabled) return;
	// Include PHPMailer library
	include_once 'lib/phpmailer/Exception.php';
	include_once 'lib/phpmailer/PHPMailer.php';
	include_once 'lib/phpmailer/SMTP.php';
	// Create an instance; passing `true` enables exceptions
	$mail = new PHPMailer(true);
	try {
		// Server settings
		if (SMTP) {
			$mail->isSMTP();
			$mail->Host = smtp_host;
			$mail->SMTPAuth = true;
			$mail->Username = smtp_user;
			$mail->Password = smtp_pass;
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
			$mail->Port = smtp_port;
		}
		// Recipients
		$mail->setFrom(mail_from, mail_name);
		$mail->addAddress($email);
		$mail->addReplyTo(mail_from, mail_name);
		// Content
		$mail->isHTML(true);
		$mail->Subject = 'Password Reset';
		// Password reset link
		$reset_link = base_url . 'reset-password.php?code=' . $code;
		// Read the template contents and replace the "%link%" placeholder with the above variable
		$email_template = str_replace(['%link%','%username%'], [$reset_link,htmlspecialchars($username, ENT_QUOTES)], file_get_contents('resetpass-email-template.html'));
		// Email body content
		$body = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,minimum-scale=1"><title>Password Reset</title></head><body style="margin:0;padding:0">' . $email_template . '</body></html>';
		// Set email body
		$mail->Body = $body;
		$mail->AltBody = strip_tags($email_template);
		// Send mail
		$mail->send();
	} catch (Exception $e) {
		// Output error message
		exit('Error: Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
	}
}
?>
