<?php
include "../connect_to_mysql.php";

if(isset($_GET["location_id"]))
{
    $location_id = $_GET["location_id"];

    $data = [
        'location_id' => $location_id
      ];
      $sql = "UPDATE rescue_locations SET deleted = 1 WHERE location_id = :location_id";
      $stmt= $conn->prepare($sql);
      $stmt->execute($data);

      echo "<script>window.location = window.location</script>";

}
 else {
    exit();
 }

 ?>