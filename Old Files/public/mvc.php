
<?php 

//Report all errors except E_NOTICE   
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL); 

//session_start();
$root = $_SERVER['DOCUMENT_ROOT'];
include_once("".$root."/wp-content/themes/brikk-child/connect_to_mysql.php");
//include_once("".$root."/wp-content/themes/brikk-child/authentication.php");
include_once("".$root."/wp-content/themes/brikk-child/app_header.php");
include_once("".$root."/wp-content/themes/brikk-child/models/DashboardModel.php");
include_once("".$root."/wp-content/themes/brikk-child/controllers/DashboardController.php");
echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* Template Name: MVC DASHBOARD */

get_header();

$current_user_id = get_current_user_id();

?>

<div id="page-top">
<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div>
        <div class="row dashboard_heading_withfilter">
            <div class="col-md-6 my-auto">
            <h1 class="h3 mb-0 text-gray-800 portal_heading">Other heading></h1>
            </div>
        </div>
    </div>
<div id="alertMsg2"></div>
		
<?php
$controller = new DashboardController($conn);
$controller->index($centre_id);
?>