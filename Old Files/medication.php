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

/* Template Name: Medication */

get_header();

include_once "app_header.php";

$current_user_id = get_current_user_id();

?>


<script>
$(function () {
  //triggered when modal is about to be shown
  $("#medicationModal").on("show.bs.modal", function (e) {
    //get data-id attribute of the clicked element
    var admissionPatientId = $(e.relatedTarget).data("id");
    var patientName = $(e.relatedTarget).data("name");
    //populate the form
    $(e.currentTarget).find(".admissionnameDisplay").text(patientName);
	  $(e.currentTarget).find(".admissionIDDisplay").text(admissionPatientId);
    $(e.currentTarget).find('input[name="patient_id"]').val(admissionPatientId);
  });
});
</script>


<div id="page-top">


    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <div>
            <div class="row dashboard_heading_withfilter">
                <div class="col-md-6 my-auto">
                    <h1 class="h3 mb-0 text-gray-800 portal_heading">Medication</h1>
                </div>

            </div>
        </div>
  


        <!-- Display list of meds times and patients -->
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">These patients require medication today</h6>
                <p class="card_subheading">These patients require medication.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered angelo_table" id="" width="100%" cellspacing="0">
                        <thead>
                            <tr>
							    <th>Time</th>
                                <th>Patient Name</th>
                                <th>Location</th>
                                <th>Medication</th>
                                <th>Route</th>
                                <th>Dose</th>
                                <th>Frequency</th>
								<th></th>
                                
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
							    <th>Time</th>
                                <th>Patient Name</th>
                                <th>Location</th>
                                <th>Medication</th>
                                <th>Route</th>
                                <th>Dose</th>
                                <th>Frequency</th>
                                <th></th>
                            </tr>
                        </tfoot>
                        <tbody>

                            <?php
                            //get orescriotioins
                            $stmt = $conn->prepare("SELECT * from rescue_prescriptions
										JOIN rescue_patients on rescue_patients.patient_id = rescue_prescriptions.patient_id
										JOIN rescue_admissions on rescue_admissions.admission_id = rescue_prescriptions.admission_id
										JOIN rescue_frequency_times on rescue_frequency_times.frequency_name = rescue_prescriptions.frequency
										WHERE CURDATE() <= DATE_ADD(rescue_prescriptions.date, INTERVAL rescue_prescriptions.duration DAY) and rescue_patients.centre_id=:centre_id
										ORDER BY time, medication ASC");
                            $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

                            // initialise an array for the results
                            $applicants = array();
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                                $patient_id = $row["patient_id"];
								$time = $row["time"];
                                $patient_name = $row["name"];
                                $medication = $row["medication"];
                                $dose = $row["dose"];
								$dose_type = $row["dose_type"];
								$duration = $row["duration"];
                                $route = $row["route"];
								$frequency = $row["frequency"];
								$current_location = $row["current_location"]; ?>
  

                        <tr>
						    <td><?php echo $time; ?></td>
                            <td><a href="https://rescuecentre.org.uk/view-patient/?patient_id="<?php echo $patient_id; ?>"><?php echo $patient_name; ?></a></td>
                            <td><?php echo $current_location; ?></td>
                            <td><?php echo $medication; ?></td>
                            <td><?php echo "by $route"; ?></td>
                            <td><?php echo $dose, $dose_type; ?></td>
                            <td><?php echo $frequency; ?></td>
                            <td><button type="button" class="btn btn-info" data-toggle="modal"  data-target="#medicationModal" data-id="<?php echo $patient_id; ?>" data-name="<?php echo $patient_name; ?>" data-toggle="tooltip" data-placement="top" title="Medications"><i class="fas fa-syringe" ></i></button></td> <?php } ?>
                            </tr>
                           


                        </tbody> 
                    </table>
                </div>
            </div>
        </div>
        <!------------------------------------------------------->
		
        <?php include ("care_plans/add_medsadmin.php"); ?>

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
    document.getElementById("medications_link").classList.add("active");
</script>


</div>
<!-- End of Main Content -->
<?php echo "</div>";