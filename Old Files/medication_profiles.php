<?php defined('ABSPATH') or die('This script cannot be accessed directly.');

// Report all errors except E_NOTICE   
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */

include_once "authentication.php";
include_once "connect_to_mysql.php";

echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* Template Name: Medication Profiles*/

get_header();

include_once "app_header.php";

$current_user_id = get_current_user_id();

/*---------------------LOGIC FOR MEDICATION CALCULATIONS ---------------------*/
//Row Count (total meds in)
$medsin = "SELECT
  med_profile_id,
  rescue_stock_medication.medication,
  medication_name,
  rescue_medication_trans.centre_id,
  SUM(packs_in) AS total_quantity
FROM rescue_medication_trans
LEFT JOIN
rescue_stock_medication
ON rescue_stock_medication.medication_profile_id = rescue_medication_trans.med_profile_id
LEFT JOIN
rescue_medications
ON rescue_medications.medication_id = rescue_stock_medication.medication
GROUP BY rescue_medication_trans.centre_id, med_profile_id";
$stmt = $conn->prepare($medsin);

// execute query
$stmt->execute();

// get row count
$total_medication_in = $row["total_quantity"];

?>

<div id="page-top">

<?php include ("medication/add_profiles.php"); ?>

<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
        <div>
             <div class="row dashboard_heading_withfilter">
                <div class="col-md-12 my-auto">
                    <h1 class="h3 mb-0 text-gray-800 portal_heading">Medication Management</h1>
					<p class="card_subheading">This allows you to set up profiles for the medications you keep in stock and set reorder levels </p>
                </div>
			</div>
		</div>


<!-- Display list of medication carried and stock levels -->
<div class="card shadow mb-4">
<div class="card-header py-3">
    <button type="button" class="btn btn-outline-success" data-toggle="modal" data-target="#add_medicationModal"> Add a medication profile to centre </button>
	<button type="button" class="btn btn-outline-success" data-toggle="modal" data-target="#add_stockModal"> Add stock (in) </button>
	<button type="button" class="btn btn-outline-danger" data-toggle="modal" data-target="#"> Generate Report </button>
</div>

<div class="card-body">
<?php include ("medication/view_stock.php"); ?> 
<?php include ("medication/view_profiles.php"); ?> 
</div>
        <!------------------------------------------------------->
		
 

  <!-- Add Stock modal -->
				   
                    <div class="modal fade" id="add_stockModal" tabindex="-1" role="dialog" aria-labelledby="add_stockModal" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                               <div class="modal-header">
                                    <h4 class="font-weight-bold text-primary">Add stock to inventory</h4>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                         <div class="modal-body">
						   <form action="" method="post">
                           <div class="row lead_form_row"> 
							   <div class="col-md-12 my-auto">
					          <p class="angelo_form_label">Medication</p>
                                        <select name="medication" name="medication" id="medication" required>
                                            <option value="" disabled selected>Medication</option>
                                            <?php
                                            //Find medications
                                            $stmt = $conn->prepare("SELECT * 
                                            FROM rescue_stock_medication
											LEFT JOIN rescue_medications ON rescue_stock_medication.medication = rescue_medications.medication_id
                                            ORDER BY medication ASC");

                                            // initialise an array for the results
                                            $stockmeds = array();
                                            $stmt->execute();
                                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                                                $medication = $row["medication"];
												$stock_medication_name = $row["medication_name"];
												$stock_common_name = $row["common_name"];
												$stock_concentration_dose = $row["concentration_dose"];
												$stock_concentration_volume = $row["concentration_volume"];
                                                

                                                print '<option value="' . $medication . '">' . $stock_medication_name . ' (' . $stock_common_name . ') ' . $stock_concentration_dose . ' mg in ' . $stock_concentration_volume . ' ml (or tablet) </option>'; } ?> </select>
					
							   </div></div>
							    <div class="row lead_form_row">
                            <div class="col-md-4 my-auto">
                            <p class="angelo_form_label">Batch code</p>
                            <input type="text" placeholder="full batch code" name="batch_number" id="batch_number" value="">
									</div>
						<div class="col-md-4">
                         <p class="angelo_form_label">Pack Expiry</p>
                         <input type="date" placeholder="expiry for the pack" name="expiry" id="expiry" value="">
                             </div>
							  
							
                            
                           
                           
									
							   </div>
							   
					   
	   
                    <input type="hidden" placeholder="for a 100ml, type 100" name="qty_added" id="qty_added" value="1">		   
					<input type="hidden" name="centre_id" id="centre_id" value="<?php echo $centre_id; ?>">
					<input type="hidden" name="user_id" id="user_id" value="<?php $current_user = wp_get_current_user(); print($current_user->id); ?>">
                    
                    <input type="submit" id="submit" name="add_stock_meds" value="Add to Stock" class="form_submit">
                    
                    </form></div></div></div></div>

                    <!--- End Of add stock modal ---->
					

    <!-- /.container-fluid -->

</div>
<!-- End of Main Content -->


<?php include_once "app_footer.php";
?>
<!-- Page level plugins -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Page level custom scripts -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/js/demo/datatables-demo.js"></script>






<!-- Add an "active" CSS class to the current page on the menu -->
<script>
    document.getElementById("medications_profile_link").classList.add("active");
</script>


</div>
<!-- End of Main Content -->
<?php echo "</div>";