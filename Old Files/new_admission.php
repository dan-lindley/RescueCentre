<?php defined('ABSPATH') or die('This script cannot be accessed directly.');
include_once "authentication.php";
include_once "connect_to_mysql.php";

echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *
 * Do not overload this file directly. 
 */

/* Template Name: Admissions */

get_header();

include_once "app_header.php";
include_once "operations/generatepasscode.php";
$current_user_id = get_current_user_id();


//Retrieve the GET value from the URL, and sanitise it for security purposes
function test_input($data)
{
   $data = trim($data);
   $data = stripslashes($data);
   $data = htmlspecialchars($data);
   return $data;
}
if (isset($_GET["patient_id"])) {
  $patient_id = test_input($_GET["patient_id"]);
  $form_type = test_input($_GET["form"]);
} else {
   echo "Error #1 - Patient not found.";
   exit();
}


?>


<div id="page-top">
    <!-- Begin Page Content -->
    <div class="container-fluid">
		<div id="alertMsg"><?php echo $alertMsg; ?></div>

<!-- DATA ENTRY FORM -->

<?php include("forms/$form_type.php"); ?>		
		

<?php include_once "app_footer.php";
?>
<!-- Page level plugins -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Page level custom scripts -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/js/demo/datatables-demo.js"></script>




<!-- Add an "active" CSS class to the current page on the menu -->
<script>
    document.getElementById("new_admissions_link").classList.add("active");
</script>

</div>
<!-- End of Main Content -->
<?php 


echo "</div>";