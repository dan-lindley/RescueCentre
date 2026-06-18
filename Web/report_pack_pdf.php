<?php
declare(strict_types=1);

define('APP_LOADED', true);

include 'main.php';
include 'getcentreinfo.php';
include 'getuserinfo.php';
check_loggedin($pdo, 'index.php');
require_once __DIR__ . '/operations/permissions.php';

registerPermission(
    'page_centre_reports',
    'Access to Rescue Reports Page',
    'page'
);
requirePermission('page_centre_reports');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require __DIR__ . '/views/print/pdf/report_pack_pdf.php';
