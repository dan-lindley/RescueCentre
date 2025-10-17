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

/* Template Name: Patients */

get_header();


include_once "app_header.php";

$current_user_id = get_current_user_id();

?>

<div id="page-top">
<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- Page Heading -->
    <div>
        <div class="row dashboard_heading_withfilter">
            <div class="col-md-6 my-auto">
            <h1 class="h3 mb-0 text-gray-800 portal_heading"><?php echo $lang['PAT_RESIDENTS']; ?></h1>
            </div>
        </div>
    </div>
<div id="alertMsg2"><?php echo $alertMsg; ?></div>
		
<?php include ("my_residents.php"); ?>

  
            </div>
        </div>
    </div>

<!-- End of Main Content -->

<?php include_once "app_footer.php"; ?>

<!-- Page level plugins -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Page level custom scripts -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/js/demo/datatables-demo.js"></script>




<!-- Add an "active" CSS class to the current page on the menu -->
<script>
    document.getElementById("residents_link").classList.add("active");
</script>


</div>
<!-- End of Main Content -->
<?php get_footer();


echo "</div>";