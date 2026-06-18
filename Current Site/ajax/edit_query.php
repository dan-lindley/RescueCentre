<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "../connect_to_mysql.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

/*------------------------------------------------------------------ FORM PROCESSING QUERY FORM -------------------------------------------------------------------*/
/* Edit Details Form Processing */
//Add New Patient Form 



    function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

        $centre_id = test_input($_POST["centre_id"]);
        $query_from = test_input($_POST["query_from"]);
        $query_to = test_input($_POST["query_to"]);

        //Insert into the table
        $statement = $conn->prepare('INSERT INTO rescue_queries
        (centre_id,
        query_from,
        query_to)

        VALUES (:centre_id,
        :query_from,
        :query_to)');

        $statement->execute([
            'centre_id' => $centre_id,
            'query_from' => $query_from,
            'query_to' => $query_to,
           
        ]);
 

        $alertmsg = '<div class="alert alert-success" role="alert">
        The edits were made.
        </div>';
    
    }

}
else {
    echo "error";
    exit();
}

/*------------------------------------------------------------------ END OF FORM PROCESSING -------------------------------------------------------------------*/