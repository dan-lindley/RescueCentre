<?php

//ini_set('display_errors', '1');
//ini_set('display_startup_errors', '1');
//error_reporting(E_ALL);


include_once "authentication.php";
include_once "connect_to_mysql.php";

echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there. use wp_id for user id
 */ 

/* Template Name: Vet Manage Staff */
 get_header();

include_once "app_header.php";

?>
<!-- Begin Page Content -->
<div class="container-fluid">
	
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Clinical Staff</h1>     
    </div>
    
    <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Add new staff</h6>
            </div>
        <div class="card-body">
            <?php include("add_new_user.php"); ?>
            
        </div>	
    </div>

    <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Staff at this practice</h6>
            </div>
        <div class="card-body">
            <?php include("my_centres.php"); ?>
            
        </div>	
    </div>




</div>
<!-- /.container-fluid -->
<!-- Add an "active" CSS class to the current page on the menu -->
<script>
    document.getElementById("vet_staff_link").classList.add("active");
</script>

<?php include_once "app_footer.php";
?>



<!-- End of Main Content -->
<?php get_footer();

echo "</div>";