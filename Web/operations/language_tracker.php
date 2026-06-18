<?php
/**
 * ---------------------------------------------------------
 * Language Analytics Tracker
 * ---------------------------------------------------------
 *
 * Purpose:
 * Record the browser's preferred language and the language
 * selected within Rescue Centre Patients.
 *
 * This information helps determine:
 *
 * - Which translations are actually being used.
 * - Which browser languages are not currently supported.
 * - Whether new language packs should be prioritised.
 *
 * Data is stored in a simple CSV file because:
 *
 * - Usage volumes are currently low.
 * - No database migrations are required.
 * - Data can easily be exported into Excel.
 *
 * Recording frequency:
 *
 * To avoid excessive entries, each logged-in user is only
 * recorded ONCE PER DAY.
 *
 * ---------------------------------------------------------
 */


/*
|--------------------------------------------------------------------------
| Safety checks
|--------------------------------------------------------------------------
|
| Ensure a session exists.
|
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Prevent duplicate entries
|--------------------------------------------------------------------------
|
| Only record one entry per user session per calendar day.
|
*/

$today = date('Y-m-d');

if (
    isset($_SESSION['lang_tracking_logged']) &&
    $_SESSION['lang_tracking_logged'] === $today
) {
    return;
}


/*
|--------------------------------------------------------------------------
| Browser language
|--------------------------------------------------------------------------
|
| HTTP_ACCEPT_LANGUAGE example:
|
| en-GB,en;q=0.9
| pl-PL,pl;q=0.9,en;q=0.8
| pt-BR,pt;q=0.9,en;q=0.8
|
| We only want the user's FIRST preference.
|
*/

$accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'unknown';

$browser_language = strtolower(
    trim(
        explode(
            ',',
            explode(';', $accept_language)[0]
        )[0]
    )
);


/*
|--------------------------------------------------------------------------
| RCP selected language
|--------------------------------------------------------------------------
|
| This is the language currently being used by the system.
|
*/

$selected_language = $_SESSION['lang'] ?? 'en';


/*
|--------------------------------------------------------------------------
| Country
|--------------------------------------------------------------------------
|
| Uses whatever country information already exists within
| the system.
|
| If unavailable, records "Unknown".
|
*/

$country =
    $_SESSION['country']
    ?? $_SESSION['geo_country']
    ?? 'Unknown';


/*
|--------------------------------------------------------------------------
| RCP version (optional)
|--------------------------------------------------------------------------
|
| Useful for tracking adoption of translations after updates.
|
*/

$version = defined('RCP_VERSION')
    ? RCP_VERSION
    : 'Unknown';


/*
|--------------------------------------------------------------------------
| CSV storage location
|--------------------------------------------------------------------------
|
| Example:
|
| /core/analytics/lang_usage.csv
|
*/

$csv_file = __DIR__ . '/lang_usage.csv';


/*
|--------------------------------------------------------------------------
| Create CSV header if file doesn't exist
|--------------------------------------------------------------------------
*/

$file_exists = file_exists($csv_file);

$fp = fopen($csv_file, 'a');

if ($fp === false) {

    /*
    |--------------------------------------------------------------------------
    | Fail silently
    |--------------------------------------------------------------------------
    |
    | Analytics should NEVER prevent RCP from functioning.
    |
    */

    return;
}


if (!$file_exists) {

    fputcsv($fp, [

        'timestamp',

        'date',

        'country',

        'browser_language',

        'selected_language',

        'rcp_version'

    ]);
}


/*
|--------------------------------------------------------------------------
| Write tracking row
|--------------------------------------------------------------------------
*/

fputcsv($fp, [

    date('Y-m-d H:i:s'),

    $today,

    $country,

    $browser_language,

    $selected_language,

    $version

]);


fclose($fp);


/*
|--------------------------------------------------------------------------
| Mark as logged
|--------------------------------------------------------------------------
|
| Prevent additional entries for the remainder of the day.
|
*/

$_SESSION['lang_tracking_logged'] = $today;