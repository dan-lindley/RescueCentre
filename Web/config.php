<?php
// Your MySQL database hostname.
define('db_host','db'); //TODO: localhost
// Your MySQL database username.
define('db_user','user');
// Your MySQL database password.
define('db_pass','db_pass');
// Your MySQL database name.
define('db_name','local_rescue');
// Your MySQL database charset.
define('db_charset','utf8mb4');
// The secret key used for hashing purposes. Change this to a random unique string.
define('secret_key','yoursecretkey');
// The base URL of the PHP login system (e.g. https://example.com/phplogin/). Must include a trailing slash.
define('base_url','http://localhost:8082');
// The template editor to use for editing product descriptions, email templates, etc.
// List:tinymce=TinyMCE,textarea=Textarea
define('template_editor','tinymce');
/* Registration */
// If enabled, the user will be redirected to the homepage automatically upon registration.
define('auto_login_after_register',false);
// If enabled, the account will require email activation before the user can login.
define('account_activation',false);
// If enabled, the user will require admin approval before the user can login.
define('account_approval',false);
/* Mail */
// If enabled, mail will be sent upon registration with the activation link, etc.
define('mail_enabled',true);
// Send mail from which address?
define('mail_from','noreply@rescuecentre.org.uk');
// The name of your website/business.
define('mail_name','Rescue Centre');
// If enabled, you will receive email notifications when a new user registers.
define('notifications_enabled',true);
// The email address to send notification emails to.
define('notification_email','notifications@example.com');
// Is SMTP server?
define('SMTP',true); //TODO: false
// SMTP Hostname
define('smtp_host','mailhog'); //TODO: 'smtp.rescuecentre.org.uk'
// SMTP Port number
define('smtp_port',1025); //TODO: 465
// SMTP Username
define('smtp_user','test'); //TODO: 'user@rescuecentre.org.uk'
// SMTP Password
define('smtp_pass','test'); //TODO: 'secret'
// SMTP Disable security
define('SMTP_disable_security', true); //TODO: false

//TODO: comment out again
// Uncomment the below to output all errors
ini_set('log_errors', true);
ini_set('error_log', 'error.log');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>
