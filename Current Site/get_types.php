<?php
function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_GET["id"])) {

    include_once "connect_to_mysql.php";

    $order = test_input($_GET["id"]);

    //Get animal types from the database and loop through them
    $stmt = $conn->prepare("SELECT * FROM rescue_animal_types WHERE animal_order = :order ORDER BY type_name ASC");
    $stmt->bindParam(':order', $order, PDO::PARAM_STR); 

    // initialise an array for the results
    $animaltypes = array();
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $type_id = $row["type_id"];
        $type_name = $row["type_name"];

       
        print '<option value="' . $type_name . '">' . $type_name . ' </option>';
   }
} else {
    echo "Error, types not found";
    exit();
}
