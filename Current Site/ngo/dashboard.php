 <?php
include_once "authentication.php";
include_once "connect_to_mysql.php";



echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* This bit just gets the organisation info from the URL */

/* Template Name: Organisational Dashboard */

get_header();

include_once "app_header.php";

//Retrieve the GET value from the URL, and sanitise it for security purposes
function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_GET["org_id"])) {
    $org_id = test_input($_GET["org_id"]);
} else {
    echo "Error #1 - Organisation not found.";
    exit();
}

if (isset($_GET["alert"])) {
    $alert = test_input($_GET["alert"]);

    if ($alert = 1) {
        $alertmsg = "<div class='alert alert-success' role='alert'>
        This organisation's details were updated in the database.
        </div>";
    } else if ($alert = 2) {
        $alertmsg = "<div class='alert alert-success' role='alert'>
        Alert
        </div>";
    } else {
        $alertmsg = "";
    }
} else {
    $alertmsg = "";
}

//Get the organisation information from the database
$sql = 'SELECT * FROM rescue_orgs WHERE org_id=:org_id LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':org_id', $org_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $org_name = $result["org_name"];
    $org_address = $result["org_address"];
    $org_valid_until = $result["org_valid_until"];
    
    $valid_until = new DateTime($org_valid_until);
    $valid_until= $valid_until->format('d-m-Y');
} else {
    echo "Sorry no valid organisation was found with this ID";
    exit();
}

?>
<!-- Begin Page Content -->
<div class="container-fluid">


<!-- Page Heading -->
  <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?php echo $org_name; ?> - Dashboard</h1>
  </div>

<!-- Content -->
<div class="card-body">
  
    <?php include_once("queries/$org_id.php"); ?>


</div>  


	<?php include_once "app_footer.php"; ?>

</div>


<!-- End of Main Content -->
<?php get_footer();

echo "</div>";