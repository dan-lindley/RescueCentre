<?php
$root = $_SERVER['DOCUMENT_ROOT'];
require_once("authentication.php");
require_once("" . $root . "/wp-load.php");

//Get the logged in user's information from the database
$logged_in_id = get_current_user_id();

$sql = 'SELECT * FROM wpxp_users 
JOIN rescue_roles
ON wpxp_users.rescue_role = rescue_roles.role_id
WHERE ID=:logged_in_id LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':logged_in_id', $logged_in_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $centre_id = $result["centre_id"];
    $accesslevel = $result["rescue_role"];
    $accessrole = $result["role_name"];
    $assignedorg = $result["assigned_org"];
    $user_id = $result["ID"];
    

} else {
    echo "Error 5 - Logged in user not found";
    exit();
}
/*---------------------------------------------------------------------------------*/

//Get the Rescue Centre information from the database
$logged_in_id = get_current_user_id();

$sql = 'SELECT * FROM rescue_centres WHERE rescue_id=:centre_id LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $rescue_name = $result["rescue_name"];

} else {
    //Rescue centre not found
    
    $currentUrl = $_SERVER['REQUEST_URI'];
    $searchValue = 'onboarding';

    if (strpos($currentUrl, $searchValue) !== false) {
        //The current URL contains the value $searchValue
    } else {
         echo '<script>
         window.location.replace("https://rescuecentre.org.uk/onboarding-form/");
         </script>';
    }
}
/*---------------------------------------------------------------------------------*/

?>
