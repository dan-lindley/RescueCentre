<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* Edit Details Form Processing */
include "../connect_to_mysql.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    function test_input($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    //Get user ID
    $remove_user_id = test_input($_POST["remove_user_id"]);


    $remove_user_id = "";


    //Set variables using the POST data from the form
    $remove_user_id = test_input($_POST["remove_user_id"]);

    try {
        //Update the database table
        $query = "UPDATE wpxp_users SET 
        centre_id = "0",
        rescue_role = "0"
        WHERE ID = :remove_user_id";

        $stmt = $conn->prepare($query);
        $stmt->bindParam('remove_user_id', $remove_user_id, PDO::PARAM_INT);
        $stmt->bindParam('centre_id', $centre_id, PDO::PARAM_STR);
        $stmt->bindParam('rescue_role', $rescue_role, PDO::PARAM_STR);

        $stmt->execute();
    } catch (PDOException $e) {
        echo $e->getMessage();
        die($e->getMessage());
    }
} else {
    echo "Error: User ID not defined - delete attempt";
    exit();
}
/*---------------------------------------------------------------------------------*/
