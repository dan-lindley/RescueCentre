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

    //Get centre ID
    $centre_id = test_input($_POST["centre_id"]);


    $rescue_name = "";
    $centre_type = "";
    $email = "";
    $office_tel = "";
    $mobile = "";
    $twentyfour = "";
    $address_line_one = "";
    $address_line_two = "";
    $city = "";
    $postcode = "";
    $accepting_admissions = "";
    $opening_hours = "";

    //Set variables using the POST data from the form
    $rescue_name = test_input($_POST["rescue_name"]);
    $centre_type = test_input($_POST["centre_type"]);
    $email = test_input($_POST["email"]);
    $office_tel = test_input($_POST["office_tel"]);
    $mobile = test_input($_POST["mobile"]);
    $twentyfour = test_input($_POST["twentyfour"]);
    $address_line_one = test_input($_POST["address_line_one"]);
    $address_line_two = test_input($_POST["address_line_two"]);
    $city = test_input($_POST["city"]);
    $postcode = test_input($_POST["postcode"]);
    $accepting_admissions = test_input($_POST["accepting_admissions"]);
    $opening_hours = test_input($_POST["opening_hours"]);

    try {
        //Update the database table
        $query = "UPDATE rescue_centres SET 
        rescue_name = :rescue_name,
        centre_type = :centre_type,
        email = :email, 
        office_tel = :office_tel,
        mobile = :mobile, 
        24_hour = :twentyfour,
        address_line_one = :address_line_one, 
        address_line_two = :address_line_two, 
        city = :city, 
        postcode = :postcode, 
        accepting_admissions = :accepting_admissions,
        opening_hours = :opening_hours
        WHERE rescue_id = :centre_id";

        $stmt = $conn->prepare($query);
        $stmt->bindParam('centre_id', $centre_id, PDO::PARAM_INT);
        $stmt->bindParam('rescue_name', $rescue_name, PDO::PARAM_STR);
        $stmt->bindParam('centre_type', $centre_type, PDO::PARAM_STR);
        $stmt->bindParam('email', $email, PDO::PARAM_STR);
        $stmt->bindParam('office_tel', $office_tel, PDO::PARAM_STR);
        $stmt->bindParam('mobile', $mobile, PDO::PARAM_STR);
        $stmt->bindParam('twentyfour', $twentyfour, PDO::PARAM_STR);
        $stmt->bindParam('address_line_one', $address_line_one, PDO::PARAM_STR);
        $stmt->bindParam('address_line_two', $address_line_two, PDO::PARAM_STR);
        $stmt->bindParam('city', $city, PDO::PARAM_STR);
        $stmt->bindParam('postcode', $postcode, PDO::PARAM_STR);
        $stmt->bindParam('accepting_admissions', $accepting_admissions, PDO::PARAM_STR);
        $stmt->bindParam('opening_hours', $opening_hours, PDO::PARAM_STR);
        $stmt->execute();
    } catch (PDOException $e) {
        echo $e->getMessage();
        die($e->getMessage());
    }
} else {
    echo "Error: Centre ID not defined";
    exit();
}
/*---------------------------------------------------------------------------------*/
