<?php defined('ABSPATH') or die('This script cannot be accessed directly.');
// Report all errors except E_NOTICE   
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */

include_once "authentication.php";
include_once "connect_to_mysql.php";


echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* Template Name: User Management */

get_header();
include_once "app_header.php";
$current_user_id = get_current_user_id();

?>

<div id="page-top">
    <div class="container-fluid">
        <div class="row dashboard_heading_withfilter">
            <div class="col-md-6 my-auto">
                <h1 class="h3 mb-0 text-gray-800 portal_heading">Manage Centre Users</h1>
            </div>
        </div>
    <div id="alertMsg"><?php echo $alertMsg; ?></div>

<div class="card shadow mb-4" id="databasetable">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">My Centre Users</h6>
        <p class="card_subheading">View the user accounts assigned to your rescue and change the role for your staff <BR>	
    </div>
    <div class="card-body">
        <?php include "settings/view_users.php"; ?>
    </div>
</div>
</div>

<div class="card shadow mb-4" id="databasetable">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Add a new user to the rescue</h6>
    </div>
    <div class="card-body">
        <p class="card_subheading">
            Add a user with the form below and assign them a role.
        
        <div class="row lead_form_row">
            <div class="col">
                <?php include "settings/add_new_user.php"; ?>
            </div>
        </div>
</div>
</div>

<div class="card shadow mb-4" id="databasetable">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Link an existing user to this rescue</h6>
    </div>
    <div class="card-body">
        <p class="card_subheading">
            Search by email for a user and assign them to this rescue. If they are already linked to another Centre
            they will no longer be linked to that rescue. If they require access to more than one rescue please get in touch.
        
        <div class="row lead_form_row">
            <div class="col">
                <?php include "settings/assign_user.php"; ?>
            </div>
        </div>
</div>




</div>
</div>
</div>

 <?php //depreciated include file. include "settings/assign_user.php";?>
 <!--<a href="https://rescuecentre.org.uk/wp-login.php?action=register" class="btn btn-success" target="_blank">Register (new window)</a>-->
<!-- using the bove two items, use the assign and search to add tertiary users to a centre that may be registered elsewhere, eg vets or people at other centres -->

<?php include_once "app_footer.php";?>

<!-- Add an "active" CSS class to the current page on the menu -->
<script>
    document.getElementById("settings_link").classList.add("active");
</script>
<script>
    document.getElementById("users_link").classList.add("active");
</script>

</div>
<!-- End of Main Content -->
