<?php 
//$centre_id = $_SESSION['centre_id'];
$centre_id = $account['centre_id'];


$sql = 'SELECT * FROM rescue_centres WHERE rescue_id=:centre_id LIMIT 1';
$statement = $pdo->prepare($sql);
$statement->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $rescue_name = $result["rescue_name"];
}
$_SESSION["rescue_name"] = $rescue_name;

?>