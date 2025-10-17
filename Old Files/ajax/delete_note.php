<?php
include "../connect_to_mysql.php";

if(isset($_GET["id"]))
{
    $location_id = $_GET["id"];

    $data = [
        'note_id' => $note_id
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