<?php 
/* This file needs to remain at top level and others can plug into it */
/* Template Name: The Annual Report  */

    include_once ("reports/report_setup.php");
    include_once ("authentication.php");
    include_once ("connect_to_mysql.php");
    include_once ("chart_data.php");
    //include_once ("summary_stats_data.php");

//Get the current Rescue Centre data from the database
$sql = 'SELECT * FROM rescue_centres WHERE rescue_id=:centre_id LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $centre_name = $result["rescue_name"];
    $centre_tel = $result["office_tel"];
    $centre_email = $result["email"];
    $centre_addr_1 = $result["address_line_one"];
    $centre_city = $result["city"];

} else {
    echo "Rescue centre not found";
    exit();
}
?> <!-- END OF PHP -->



<style> @media print {
    .pagebreak { page-break-before: always; } /* page-break-after works, as well */
}
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet"/>

<!--<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<link rel="stylesheet" href="print.css">-->

<!-- FRONT COVER -->
<img src="https://rescuecentre.org.uk/wp-content/uploads/2024/11/ai-image2.jpg" class="position-absolute" alt="..." width="130%" height="130%">

<div class="wrapper">
    <div class="row">
        <div class="col-12">
        <img src="https://rescuecentre.org.uk/wp-content/uploads/2023/04/black-logo-v3.png" width="30% height="30%"></img>
        <br><br><br><br><br>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
         <div class="p-3 mb-2 bg-info bg-gradient text-dark z-0 position-absolute"><figure class="text-center"><h1><strong>ANNUAL REPORT YY/YY</strong></h1><BR><h2><?php echo $centre_name; ?></h2></figure></div>
        </div>
    </div>
</div>
<?php include ("reports/all_queries.php"); ?>
<!-- Page 1 -->
<div class="pagebreak"></div>
<div class="wrapper2">

    </div>
      <?php include ("reports/overview.php");?>
</div>

<!-- Page 2 -->
<div class="pagebreak"> </div>

Page 2

<!-- Page 3 -->
<div class="pagebreak"> </div>

Page 3

<script>
  window.addEventListener("load", window.print());
</script>