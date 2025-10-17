 <?php defined('ABSPATH') or die('This script cannot be accessed directly.');

include_once "authentication.php";
include_once "connect_to_mysql.php";

echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *xx
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* Template Name: Manage Tasks */

get_header();
$current_user_id = get_current_user_id();
include_once "app_header.php";

$current_user_id = get_current_user_id();

//Get the current Rescue Centre data from the database
$sql = 'SELECT * FROM rescue_centres WHERE rescue_id=:centre_id LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $rescue_name = $result["rescue_name"];

} else {
    echo "Rescue centre not found";
    exit();

}

?>
 <!-- Begin Page Content -->
<div class="container-fluid">

        <!-- Page Heading -->
        <div>
            <div class="row dashboard_heading_withfilter">
                <div class="col-md-6 my-auto">
                    <h1 class="h3 mb-0 text-gray-800 portal_heading">Manage the Tasks in your Centre</h1>                    
                </div>
            </div>
        </div>




<?php //include ("tasks/tasklist.php"); ?>
<?php include ("settings/add_task.php"); ?>   



<?php include_once "app_footer.php";
?>
<!-- End of Main Content -->
<?php get_footer();


echo "</div>";