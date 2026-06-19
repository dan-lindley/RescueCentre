<?php

// Rescue Centre Lite example config. Copy to config.php or run /install.

define('db_host', 'localhost');
define('db_user', 'root');
define('db_pass', '');
define('db_name', 'rescue_centre_lite');
define('db_charset', 'utf8mb4');
define('secret_key', 'change-this-to-a-long-random-string');
define('base_url', 'http://localhost/');
define('template_editor', 'textarea');

define('auto_login_after_register', false);
define('account_activation', false);
define('account_approval', false);

define('mail_enabled', false);
define('mail_from', 'noreply@example.org');
define('mail_name', 'Rescue Centre Lite');
define('notifications_enabled', false);
define('notification_email', 'notifications@example.org');
define('SMTP', false);
define('smtp_host', 'localhost');
define('smtp_port', 465);
define('smtp_user', '');
define('smtp_pass', '');
