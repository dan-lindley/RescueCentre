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

/* Template Name: Knowledge Base */

get_header();

include_once "app_header.php";


?>




<div id="page-top">

    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
 	
	<!-- Location settings -->
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Knowledge Base</h6>
                <p class="card_subheading">Browse and view articles in the knowledge base</p>
            </div>
            <div class="card-body">
		<?php echo do_shortcode('[epkb-knowledge-base id=1]'); ?>		
	
	          
                    
				
                
                
</div>

<!-- end of locations section -->
	


    </div>
    <!-- /.container-fluid -->

</div>
<!-- End of Main Content -->

  </div> </div>                 
<!-- Add an "active" CSS class to the current page on the menu -->
<script>
    document.getElementById("knowledge_link").classList.add("active");
</script>


<?php include_once "app_footer.php";?>


<!-- End of Main Content -->

<?php echo "</div>";