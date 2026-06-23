<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

// Include the root "config.php" and "main.php" files
include_once 'config.php';
include_once 'main.php';
include 'getcentreinfo.php';
include 'getuserinfo.php';
require_once __DIR__ . '/operations/modules_registry.php';
require_once __DIR__ . '/operations/permissions.php';
require_once __DIR__ . '/core/components/messaging/controllers/messaging_lib.php';
require 'lang.php';
require_once __DIR__ . '/operations/language_tracker.php';

// Check if the user is logged-in
check_loggedin($pdo, 'index.php');

// Global variables
$patient_count = get_patient_count($pdo, $centre_id);
$resident_count = get_resident_count($pdo, $centre_id);
$medication_count = get_medication_count($pdo, $centre_id);
$toadmit_count = get_toadmit_count($pdo, $centre_id);
$pending_friend_requests = get_pending_friend_request_count($pdo, (int)$centre_id);
$message_unread_count = messaging_unread_count($pdo, (int)($_SESSION['account_id'] ?? 0));

function get_patient_count(PDO $pdo, int $centre_id) {
    $sql = "
        SELECT COUNT(*) 
        FROM rescue_admissions ra
        INNER JOIN rescue_patients rp 
            ON ra.patient_id = rp.patient_id
        WHERE ra.disposition = 'Held in captivity'
          AND rp.state = 'Admitted'
          AND rp.centre_id = :centre_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['centre_id' => $centre_id]);
    return (int)$stmt->fetchColumn();
}

function get_resident_count(PDO $pdo, int $centre_id) {
    $sql = "
        SELECT COUNT(*) 
        FROM rescue_admissions ra
        INNER JOIN rescue_patients rp 
            ON ra.patient_id = rp.patient_id
        WHERE ra.disposition = 'Long-term Captive' 
          AND rp.centre_id = :centre_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['centre_id' => $centre_id]);
    return (int)$stmt->fetchColumn();
}

function get_medication_count(PDO $pdo, int $centre_id) {
    $softDeleteFilter = '';
    try {
        $colStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'rescue_prescriptions'
              AND COLUMN_NAME = 'is_deleted'
        ");
        $colStmt->execute();
        if ((int)$colStmt->fetchColumn() > 0) {
            $softDeleteFilter = " AND COALESCE(is_deleted, 0) = 0";
        }
    } catch (Throwable $e) {
        $softDeleteFilter = '';
    }

    $sql = "
        SELECT COUNT(DISTINCT patient_id)
        FROM rescue_prescriptions
        WHERE centre_id = :centre_id
          AND CURDATE() <= DATE_ADD(date, INTERVAL duration DAY)
          {$softDeleteFilter}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['centre_id' => $centre_id]);
    return (int)$stmt->fetchColumn();
}
function get_pending_friend_request_count(PDO $pdo, int $centre_id): int {
    $sql = "
        SELECT COUNT(*)
        FROM rescue_centre_friends
        WHERE status = 'pending'
          AND (centre_a_id = :centre_id OR centre_b_id = :centre_id)
          AND requested_by_centre_id <> :centre_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['centre_id' => $centre_id]);
    return (int)$stmt->fetchColumn();
}

function get_toadmit_count(PDO $pdo, int $centre_id) {
    $softDeleteFilter = '';
    try {
        $colStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'rescue_patients'
              AND COLUMN_NAME = 'is_deleted'
        ");
        $colStmt->execute();
        if ((int)$colStmt->fetchColumn() > 0) {
            $softDeleteFilter = " AND COALESCE(rp.is_deleted, 0) = 0";
        }
    } catch (Throwable $e) {
        $softDeleteFilter = '';
    }

    $sql = "
        SELECT COUNT(*) 
        FROM rescue_patients rp  
        WHERE rp.state = 'To Admit'
          AND rp.centre_id = :centre_id
          {$softDeleteFilter}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['centre_id' => $centre_id]);
    return (int)$stmt->fetchColumn();
}

// Fetch account details associated with the logged-in user
$stmt = $pdo->prepare('SELECT COUNT(*) FROM accounts WHERE id = ? AND role = "Admin"');

// Get the account info using the logged-in session ID
$stmt->execute([ $_SESSION['account_id'] ]);

// Admin panel link - will only be visible if the user is an admin
$admin_panel_link = isset($_SESSION['account_role']) && $_SESSION['account_role'] == 'Admin' ? '<a href="admin/index.php" target="_blank">Admin</a>' : '';

// Add/remove roles from the list
$roles_list = ['Admin', 'Member'];
// Icons for the table headers
$table_icons = [
    'asc' => '<svg width="10" height="10" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M350 177.5c3.8-8.8 2-19-4.6-26l-136-144C204.9 2.7 198.6 0 192 0s-12.9 2.7-17.4 7.5l-136 144c-6.6 7-8.4 17.2-4.6 26s12.5 14.5 22 14.5h88l0 192c0 17.7-14.3 32-32 32H32c-17.7 0-32 14.3-32 32v32c0 17.7 14.3 32 32 32l80 0c70.7 0 128-57.3 128-128l0-192h88c9.6 0 18.2-5.7 22-14.5z"/></svg>',
    'desc' => '<svg width="10" height="10" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M350 334.5c3.8 8.8 2 19-4.6 26l-136 144c-4.5 4.8-10.8 7.5-17.4 7.5s-12.9-2.7-17.4-7.5l-136-144c-6.6-7-8.4-17.2-4.6-26s12.5-14.5 22-14.5h88l0-192c0-17.7-14.3-32-32-32H32C14.3 96 0 81.7 0 64V32C0 14.3 14.3 0 32 0l80 0c70.7 0 128 57.3 128 128l0 192h88c9.6 0 18.2 5.7 22 14.5z"/></svg>'
];
// Update last seen
$d = date('Y-m-d H:i:s');
$stmt = $pdo->prepare('UPDATE accounts SET last_seen = ? WHERE id = ?');
$stmt->execute([ $d, $_SESSION['account_id'] ]);

// Get total number of accounts
$accounts_total = $pdo->query('SELECT COUNT(*) AS total FROM accounts')->fetchColumn();

// Template admin header
function template_admin_header($title, $selected = 'dashboard', $selected_child = '') {
     global $pdo, $patient_count, $medication_count, $resident_count, $toadmit_count, $pending_friend_requests, $message_unread_count;
     global $lang;
     global $page_css_files;

// ----------------------------------------
// Language dropdown (simple, no CSS)
// ----------------------------------------
$current_lang = $_SESSION['lang'] ?? 'en';

// Preserve current params except lang
$params = $_GET;
unset($params['lang']);

$language_names = [
    'en' => 'English',
    'es' => 'EspaÃ±ol',
    'fr' => 'FranÃ§ais',
    'de' => 'Deutsch',
    'pl' => 'Polski',
];

$language_options = [];

$flag_files = glob(__DIR__ . '/languages/*.png') ?: [];

foreach ($flag_files as $flag_file) {

    $code = strtolower(pathinfo($flag_file, PATHINFO_FILENAME));

    // Only allow two-letter language codes
    if (!preg_match('/^[a-z]{2}$/', $code)) {
        continue;
    }

    $query = $params;
    $query['lang'] = $code;

    $href = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');

    if (!empty($query)) {
        $href .= '?' . http_build_query($query);
    }

    $language_options[$code] = [
        'href' => $href,
        'flag' => 'languages/' . $code . '.png',
        'label' => $language_names[$code] ?? strtoupper($code),
    ];
}

// Fallback to English if current language doesn't exist
if (!isset($language_options[$current_lang])) {
    $current_lang = 'en';
}

$current_flag = $language_options[$current_lang]['flag']
    ?? 'languages/en.png';

$current_language_label = $language_options[$current_lang]['label']
    ?? 'English';

$language_links = '';

foreach ($language_options as $code => $language_option) {

    $language_links .= '
        <a class="rc-lang-option' . ($code === $current_lang ? ' is-active' : '') . '"
           href="' . htmlspecialchars($language_option['href'], ENT_QUOTES) . '"
           title="' . htmlspecialchars($language_option['label'], ENT_QUOTES) . '"
           aria-label="' . htmlspecialchars($language_option['label'], ENT_QUOTES) . '">

            <img src="' . htmlspecialchars($language_option['flag'], ENT_QUOTES) . '"
                 alt="' . htmlspecialchars($language_option['label'], ENT_QUOTES) . '">

        </a>';
}

$lang_dropdown = '
    <details class="rc-lang-switcher">

        <summary title="' . htmlspecialchars($current_language_label, ENT_QUOTES) . '"
                 aria-label="' . htmlspecialchars($current_language_label, ENT_QUOTES) . '">

            <img src="' . htmlspecialchars($current_flag, ENT_QUOTES) . '"
                 alt="' . htmlspecialchars($current_language_label, ENT_QUOTES) . '">

            <span class="rc-lang-arrow" aria-hidden="true"></span>

        </summary>

        <div class="rc-lang-menu">
            ' . $language_links . '
        </div>

    </details>';
        
// Admin HTML links
    $admin_links = '
        <a href="dashboard.php"' . ($selected == 'dashboard' ? ' class="selected"' : '') . ' title="' . htmlspecialchars($lang['LM_DASHBOARD'] ?? 'Dashboard') . '">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm320 96c0-26.9-16.5-49.9-40-59.3V88c0-13.3-10.7-24-24-24s-24 10.7-24 24V292.7c-23.5 9.5-40 32.5-40 59.3c0 35.3 28.7 64 64 64s64-28.7 64-64zM144 176a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm-16 80a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64zM400 144a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>            
            </span>
            <span class="txt">' . ($lang['LM_DASHBOARD'] ?? 'Dashboard') . '</span>
        </a>

        <a href="patients.php"' . ($selected == 'patients' ? ' class="selected"' : '') . ' title="' . htmlspecialchars($lang['LM_PATIENTS'] ?? 'Patients') . '">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M234.5 92.9c14.3 42.9-.3 86.2-32.6 96.8s-70.1-15.6-84.4-58.5 .3-86.2 32.6-96.8 70.1 15.6 84.4 58.5zM100.4 198.6c18.9 32.4 14.3 70.1-10.2 84.1s-59.7-.9-78.5-33.3-14.3-70.1 10.2-84.1 59.7 .9 78.5 33.3zM69.2 401.2C121.6 259.9 214.7 224 256 224s134.4 35.9 186.8 177.2c3.6 9.7 5.2 20.1 5.2 30.5l0 1.6c0 25.8-20.9 46.7-46.7 46.7-11.5 0-22.9-1.4-34-4.2l-88-22c-15.3-3.8-31.3-3.8-46.6 0l-88 22c-11.1 2.8-22.5 4.2-34 4.2-25.8 0-46.7-20.9-46.7-46.7l0-1.6c0-10.4 1.6-20.8 5.2-30.5zM421.8 282.7c-24.5-14-29.1-51.7-10.2-84.1s54-47.3 78.5-33.3 29.1 51.7 10.2 84.1-54 47.3-78.5 33.3zM310.1 189.7c-32.3-10.6-46.9-53.9-32.6-96.8s52.1-69.1 84.4-58.5 46.9 53.9 32.6 96.8-52.1 69.1-84.4 58.5z"/></svg>
            </span>
            <span class="txt">' . ($lang['LM_PATIENTS'] ?? 'Patients') . '</span>
            <span class="note">' . number_format($patient_count) . ' / ' . number_format($toadmit_count) . '</span>
        </a>
        <div class="sub">
            <a href="admission.php"' . ($selected == 'patients' && $selected_child == 'admission' ? ' class="selected"' : '') . '><span class="square"></span>' . ($lang['LM_NEW_ADMISSION'] ?? 'New Admission') . '</a>
            <a href="patients.php"' . ($selected == 'patients' && $selected_child == 'mypatients' ? ' class="selected"' : '') . '><span class="square"></span>' . ($lang['LM_MY_PATIENTS'] ?? 'My Patients') . '</a>
            ' . modules_nav_html($pdo, 'Patients', $selected, $selected_child) . '
        </div>

        <a href="medication.php"' . ($selected == 'medication' ? ' class="selected"' : '') . ' title="' . htmlspecialchars($lang['LM_MEDICATION'] ?? 'Medication') . '">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M128 176C128 149.5 149.5 128 176 128C202.5 128 224 149.5 224 176L224 288L128 288L128 176zM240 432C240 383.3 258.1 338.8 288 305L288 176C288 114.1 237.9 64 176 64C114.1 64 64 114.1 64 176L64 464C64 525.9 114.1 576 176 576C213.3 576 246.3 557.8 266.7 529.7C249.7 501.1 240 467.7 240 432zM304.7 499.4C309.3 508.1 321 509.1 328 502.1L502.1 328C509.1 321 508.1 309.3 499.4 304.7C479.3 294 456.4 288 432 288C352.5 288 288 352.5 288 432C288 456.3 294 479.3 304.7 499.4zM361.9 536C354.9 543 355.9 554.7 364.6 559.3C384.7 570 407.6 576 432 576C511.5 576 576 511.5 576 432C576 407.7 570 384.7 559.3 364.6C554.7 355.9 543 354.9 536 361.9L361.9 536z"/></svg>
            </span>
            <span class="txt">' . ($lang['LM_MEDICATION'] ?? 'Medication') . '</span>
            <span class="note">' . number_format($medication_count) . '</span>
        </a>
        <div class="sub">
            <a href="medication.php"' . ($selected == 'medication' && $selected_child == 'medsround' ? ' class="selected"' : '') . '><span class="square"></span>' . ($lang['LM_MEDICATION_ROUND'] ?? 'Medication Round') . '</a>
            <a href="medicationstock.php"' . ($selected == 'medication' && $selected_child == 'stock' ? ' class="selected"' : '') . '><span class="square"></span>' . ($lang['LM_STOCK_MANAGEMENT'] ?? 'Stock Management') . '</a>
            ' . modules_nav_html($pdo, 'Medication', $selected, $selected_child) . '
        </div>

        <a href="management.php"' . ($selected == 'management' ? ' class="selected"' : '') . ' title="' . htmlspecialchars($lang['LM_MANAGEMENT'] ?? ($lang['LM_CENTRE_MANAGEMENT'] ?? 'Management')) . '">
            <span class="icon">
                <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.21,8.95 2.27,9.22 2.46,9.37L4.57,11C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.21,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.67 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z" /></svg>
            </span>
            <span class="txt">' . ($lang['LM_MANAGEMENT'] ?? ($lang['LM_CENTRE_MANAGEMENT'] ?? 'Management')) . '</span>
            <span class="note">' . number_format($medication_count) . '</span>
        </a>
        <div class="sub">
            <a href="management.php"' . ($selected == 'management' && $selected_child == 'settings' ? ' class="selected"' : '') . '><span class="square"></span>' . ($lang['LM_CENTRE_SETTINGS'] ?? 'Centre Settings') . '</a>
            <a href="management.php?tab=sync"' . ($selected == 'management' && $selected_child == 'sync' ? ' class="selected"' : '') . '><span class="square"></span>Sync</a>
            <a href="locations.php"' . ($selected == 'management' && $selected_child == 'locations' ? ' class="selected"' : '') . '><span class="square"></span>' . ($lang['LM_LOCATIONS'] ?? 'Locations') . '</a>
            <a href="data.php"' . ($selected == 'management' && $selected_child == 'data' ? ' class="selected"' : '') . '><span class="square"></span>' . ($lang['LM_MANAGE_DATA'] ?? 'Manage Data') . '</a>
            <a href="reports.php"' . ($selected == 'management' && $selected_child == 'reports' ? ' class="selected"' : '') . '><span class="square"></span>' . ($lang['LM_REPORTS'] ?? 'Reports') . '</a>
            ' . modules_nav_html($pdo, 'Management', $selected, $selected_child) . '
        </div>

        <a href="user_accounts.php"' . ($selected == 'staff' ? ' class="selected"' : '') . ' title="Staff">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M96 128a128 128 0 1 1 256 0A128 128 0 1 1 96 128zM0 482.3C0 383.8 79.8 304 178.3 304h91.4C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3zM504 312v-64h-64c-13.3 0-24-10.7-24-24s10.7-24 24-24h64v-64c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24h-64v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/></svg>
            </span>
            <span class="txt">Staff</span>
        </a>
        <div class="sub">
            <a href="user_accounts.php"' . ($selected == 'staff' && $selected_child == 'accounts' ? ' class="selected"' : '') . '><span class="square"></span>Accounts &amp; Permissions</a>
            ' . modules_nav_html($pdo, 'Staff', $selected, $selected_child) . '
        </div>

        <a href="groups.php"' . (($selected == 'groups' || $selected == 'resources' || $selected == 'support') ? ' class="selected"' : '') . ' title="' . htmlspecialchars($lang['LM_SUPPORT'] ?? 'Support') . '">
            <span class="icon">
                <svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M96 192C96 130.1 146.1 80 208 80C269.9 80 320 130.1 320 192C320 253.9 269.9 304 208 304C146.1 304 96 253.9 96 192zM32 528C32 430.8 110.8 352 208 352C305.2 352 384 430.8 384 528L384 534C384 557.2 365.2 576 342 576L74 576C50.8 576 32 557.2 32 534L32 528zM464 128C517 128 560 171 560 224C560 277 517 320 464 320C411 320 368 277 368 224C368 171 411 128 464 128zM464 368C543.5 368 608 432.5 608 512L608 534.4C608 557.4 589.4 576 566.4 576L421.6 576C428.2 563.5 432 549.2 432 534L432 528C432 476.5 414.6 429.1 385.5 391.3C408.1 376.6 435.1 368 464 368z"/></svg>
            </span>
            <span class="txt">' . ($lang['LM_SUPPORT'] ?? 'Support') . '</span>  
            <span class="note">' . number_format($pending_friend_requests) . '</span>
        </a>
        <div class="sub">
            <a href="friends.php"' . ($selected == 'groups' && $selected_child == 'friends' ? ' class="selected"' : '') . '><span class="square"></span>' . ($lang['LM_FRIENDS'] ?? 'Friends') . '</a>
            <a href="groups.php"' . ($selected == 'groups' && $selected_child == 'groups' ? ' class="selected"' : '') . '><span class="square"></span>' . ($lang['LM_NETWORKS'] ?? 'Networks') . '</a>
            <a href="messageboard.php"' . ($selected == 'groups' && $selected_child == 'messageboard' ? ' class="selected"' : '') . '><span class="square"></span>' . ($lang['LM_MESSAGE_BOARD'] ?? 'Message Board') . '</a>
            <a href="resources.php"' . ($selected == 'resources' ? ' class="selected"' : '') . '><span class="square"></span>' . ($lang['LM_RESOURCES'] ?? 'Resources') . '</a>
            <a href="support.php"' . ($selected == 'support' ? ' class="selected"' : '') . '><span class="square"></span>' . ($lang['LM_SUPPORT'] ?? 'Support') . '</a>
            ' . modules_nav_html($pdo, 'Support', $selected, $selected_child) . '
        </div>
    ';


    
    // Profile image
    $profile_img = '
    <div class="profile-img">
        <span style="background-color:' . color_from_string($_SESSION['account_name']) . '">' . strtoupper(substr($_SESSION['account_name'], 0, 1)) . '</span>
        <i class="online"></i>
    </div>
    ';

    $return_admin_link = '';
    if (!empty($_SESSION['admin_impersonator_id']) && !empty($_SESSION['admin_impersonator_return_token'])) {
        $return_admin_link = '<a href="admin/tools/return_to_admin.php?token=' . urlencode((string)$_SESSION['admin_impersonator_return_token']) . '">Return to Admin</a>';
    }
    $admin_menu_link = isset($_SESSION['account_role']) && $_SESSION['account_role'] === 'Admin'
        ? '<a href="admin/index.php">Admin Menu</a>'
        : '';
    // Indenting the below code may cause an error
//CSS versioning
$admin_shell_css_path = __DIR__ . '/core/css/admin-shell.css';
$css_version = is_file($admin_shell_css_path) ? filemtime($admin_shell_css_path) : time();
$core_css_path = __DIR__ . '/core/css/core.css';
$core_css_version = file_exists($core_css_path) ? filemtime($core_css_path) : $css_version;
$favicon_path = __DIR__ . '/img/favicon.ico';
$favicon_version = is_file($favicon_path) ? filemtime($favicon_path) : time();
$page_css_links = '';
if (!empty($page_css_files) && is_array($page_css_files)) {
    foreach ($page_css_files as $page_css_file) {
        $page_css_file = ltrim((string)$page_css_file, '/\\');
        $page_css_path = __DIR__ . '/' . $page_css_file;
        if (is_file($page_css_path)) {
            $page_css_links .= '        <link href="' . htmlspecialchars(base_url . $page_css_file, ENT_QUOTES) . '?v=' . filemtime($page_css_path) . '" rel="stylesheet" type="text/css">' . "\n";
        }
    }
}
$theme_attr = !empty($_SESSION['dark_mode']) ? ' data-theme="dark"' : '';
$theme_name = !empty($_SESSION['dark_mode']) ? 'dark' : 'light';
echo '<!DOCTYPE html>
<html lang="en"' . $theme_attr . '>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,minimum-scale=1">
        <title>' . $title . '</title>
        <script>
            window.rescueAppTheme = "' . $theme_name . '";
            if (window.rescueAppTheme === "dark") {
                document.documentElement.setAttribute("data-theme", "dark");
            }
        </script>
        <link href="' . htmlspecialchars(base_url . 'core/css/admin-shell.css', ENT_QUOTES) . '?v=' . $css_version . '" rel="stylesheet" type="text/css">
        <link href="' . htmlspecialchars(base_url . 'core/css/core.css', ENT_QUOTES) . '?v=' . $core_css_version . '" rel="stylesheet" type="text/css">
' . $page_css_links . '        <link rel="icon" type="image/x-icon" href="' . base_url . 'img/favicon.ico?v=' . $favicon_version . '">
        <link rel="shortcut icon" type="image/x-icon" href="' . base_url . 'img/favicon.ico?v=' . $favicon_version . '">
    </head>
    <body class="admin">
        <aside>
            <h1>
                <span class="title"><img src="img/logo-square-white-cropped.png" width="40px" height="40px"></span>
                &nbsp;<span class="title">Rescue Centre</span>
            </h1>
            ' . $admin_links . '
            ' . $admin_panel_link . '
            
            <div class="footer">

                  ' . $_SESSION['rescue_name'] . '<br>
                <a href="https://www.rescuecentre.org.uk" target="_blank">Rescue Centre</a>
                <small>Version 2.2.1</small>
              
            </div>
            
        </aside>
        <main class="responsive-width-100">
            <header>
                <a class="responsive-toggle" href="#" title="Toggle Menu"></a>
                <div class="space-between"></div>
                <div class="space-between"></div>
                     ' . $lang_dropdown . '
                <a class="shortcut-link" href="' . base_url . 'messages.php" title="' . htmlspecialchars($lang['LM_MESSAGES'] ?? 'Messages') . '">
                    <svg width="16" height="16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" aria-hidden="true"><path d="M112 128C85.5 128 64 149.5 64 176C64 191.1 71.1 205.3 83.2 214.4L291.2 370.4C308.3 383.2 331.7 383.2 348.8 370.4L556.8 214.4C568.9 205.3 576 191.1 576 176C576 149.5 554.5 128 528 128L112 128zM64 260L64 448C64 483.3 92.7 512 128 512L512 512C547.3 512 576 483.3 576 448L576 260L377.6 408.8C343.5 434.4 296.5 434.4 262.4 408.8L64 260z"/></svg>
                    ' . number_format($message_unread_count) . '
                </a>
                <div class="dropdown right">
                    ' . $profile_img . '
                    <div class="list">
                        ' . $return_admin_link . '
                        <a href="home.php">Staff Dashboard</a>
                        <a href="' . base_url . 'messages.php"><span class="icon"><svg width="15" height="15" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" aria-hidden="true"><path d="M112 128C85.5 128 64 149.5 64 176C64 191.1 71.1 205.3 83.2 214.4L291.2 370.4C308.3 383.2 331.7 383.2 348.8 370.4L556.8 214.4C568.9 205.3 576 191.1 576 176C576 149.5 554.5 128 528 128L112 128zM64 260L64 448C64 483.3 92.7 512 128 512L512 512C547.3 512 576 483.3 576 448L576 260L377.6 408.8C343.5 434.4 296.5 434.4 262.4 408.8L64 260z"/></svg></span>' . ($lang['LM_MESSAGES'] ?? 'Messages') . '</a>
                        ' . $admin_menu_link . '
                        <a href="profile.php?action=edit">Edit Profile</a>
                        <a href="logout.php" class="red">Logout</a>
                    </div>
                </div> 
            </header>';
}
?>








<?php 
// Template admin footer
function template_admin_footer($footer = '') {
// DO NOT INDENT THE BELOW CODE
echo '  </main>
        <script src="' . htmlspecialchars(base_url . 'core/js/admin-shell.js', ENT_QUOTES) . '"></script>
        <script src="core/js/theme.js"></script>
  
        ' . $footer . '
    </body>
</html>';
}
// The following function will be used to assign a unique icon color to our users
function color_from_string($string) {
    // The list of hex colors
    $colors = ['#34568B','#FF6F61','#6B5B95','#88B04B','#F7CAC9','#92A8D1','#955251','#B565A7','#009B77','#DD4124','#D65076','#45B8AC','#EFC050','#5B5EA6','#9B2335','#DFCFBE','#BC243C','#C3447A','#363945','#939597','#E0B589','#926AA6','#0072B5','#E9897E','#B55A30','#4B5335','#798EA4','#00758F','#FA7A35','#6B5876','#B89B72','#282D3C','#C48A69','#A2242F','#006B54','#6A2E2A','#6C244C','#755139','#615550','#5A3E36','#264E36','#577284','#6B5B95','#944743','#00A591','#6C4F3D','#BD3D3A','#7F4145','#485167','#5A7247','#D2691E','#F7786B','#91A8D0','#4C6A92','#838487','#AD5D5D','#006E51','#9E4624'];
    // Find color based on the string
    $colorIndex = hexdec(substr(sha1($string), 0, 10)) % count($colors);
    // Return the hex color
    return $colors[$colorIndex];
}
// Convert date to elapsed string function
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $w = floor($diff->d / 7);
    $diff->d -= $w * 7;
    $string = ['y' => 'year','m' => 'month','w' => 'week','d' => 'day','h' => 'hour','i' => 'minute','s' => 'second'];
    foreach ($string as $k => &$v) {
        if ($k == 'w' && $w) {
            $v = $w . ' week' . ($w > 1 ? 's' : '');
        } else if (isset($diff->$k) && $diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
// Remove param from URL function
function remove_url_param($url, $param) {
    $url = preg_replace('/(&|\?)'.preg_quote($param).'=[^&]*$/', '', $url);
    $url = preg_replace('/(&|\?)'.preg_quote($param).'=[^&]*&/', '$1', $url);
    return $url;
}
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
