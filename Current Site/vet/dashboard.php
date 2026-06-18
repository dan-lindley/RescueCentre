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

/* Template Name: Vet Dashboard */
 get_header();

include_once "app_header.php";

?>
<!-- Begin Page Content -->
<div class="container-fluid">
	
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Vet Professional Dashboard</h1>     
    </div>

    <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Urgent Tasks</h6>
            </div>
        <div class="card-body">
            tasks lists that have been sent by rescue to the vet
            
        </div>	
    </div>

                
<?php include ("my_patients.php"); ?>
					
       
        <!------------------------------------------------------->

</div>
<!-- /.container-fluid -->


<?php include_once "app_footer.php";
?>



<!-- End of Main Content -->
<?php get_footer();

echo "</div>";