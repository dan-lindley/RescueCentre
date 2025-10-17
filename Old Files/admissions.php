<?php defined('ABSPATH') or die('This script cannot be accessed directly.');
include_once "authentication.php";
include_once "connect_to_mysql.php";

echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* Template Name: Admissions */

get_header();

include_once "app_header.php";

$current_user_id = get_current_user_id();


//Row Count
$sql = "SELECT * 
FROM rescue_admissions
INNER JOIN rescue_patients
ON rescue_admissions.patient_id = rescue_patients.patient_id
WHERE rescue_admissions.disposition = 'Held in captivity' AND rescue_patients.centre_id = :centre_id
ORDER by `admission_date` DESC";
$stmt = $conn->prepare($sql);

// bind parameters
$stmt->bindParam(':centre_id', $centre_id);

// execute query
$stmt->execute();

// get row count
$row_count = $stmt->rowCount();
?>


<script>
$(function () {
  //triggered when modal is about to be shown
  $("#carenotesModal").on("show.bs.modal", function (e) {
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
<script>
$(function () {
  //triggered when modal is about to be shown
  $("#treatmentModal").on("show.bs.modal", function (e) {
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
                    <h1 class="h3 mb-0 text-gray-800 portal_heading">Admissions</h1>
                </div>
                <div class="col-md-6">
                    <form>
                        <span class="filter_label">Filter By Disposition:</span>
                        <select name="status_filter" id="status_filter" onchange="getPeople(this.value, <?php echo $centre_id; ?>)">
                            <option value="Held in captivity">Held in captivity</option>
                            <option value="Released">Released</option>
							<option value="Transferred out">Transferred to another rescue</option>
                            <option value="Died - Euthanised">Died - Euthanised</option>
                            <option value="Died - after 48 hours">Died - after 48 hours</option>
                            <option value="Died - within 48 hours">Died - within 48 hours</option>
                            <option value="Died - on admission">Died - on admission</option>
                        </select>
                    </form>

                    <script type="text/javascript">
                        function getPeople(str, userid) {
                            if (str == "") {
                                document.getElementById("databasetable").innerHTML = "";
                                return;
                            } else {
                                var xmlhttp = new XMLHttpRequest();
                                xmlhttp.onreadystatechange = function() {
                                    if (this.readyState == 4 && this.status == 200) {
                                        document.getElementById("databasetable").innerHTML = this.responseText;
                                    }
                                };
                                xmlhttp.open("GET", "https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/get_admissions.php?status=" + str + "&id=" + userid, true);
                                xmlhttp.send();
                            }
                        }
                    </script>
                </div>
            </div>
        </div>
        <div id="alertMsg"><?php echo $alertMsg; ?></div>
		
		
        <!-- Area Chart -->
 <div class="card shadow mb-4">
                <!-- Card Header - Dropdown -->
                  <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Occupancy - Live state</h6>

                </div>
                <!-- Card Body -->
               
		
<div class="card-body">
				
 <table>
		
	
		<?php
                    //Get by month count
                    $stmt = $conn->prepare("SELECT location_name, rescue_locations.centre_id, current_location, max_occupancy,
											COUNT(current_location) AS in_location
											FROM rescue_admissions
											RIGHT JOIN rescue_locations
											ON rescue_admissions.current_location = rescue_locations.location_name
											WHERE rescue_locations.centre_id = :centre_id AND deleted=0 or location_name is NULL
											GROUP BY location_name
											ORDER BY location_name
											");
					
                   $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

                    // initialise an array for the results
                    $occupied = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $max = $row["max_occupancy"];
						$in = $row["in_location"];
						$name = $row["location_name"];
						

						$occupied = ($in / $max) * 100;
                             

                         print '
						<tr> <td width ="25%">
						 ' .$name. '</td><td width="5%"><strong>' .$in. '</strong></td><td> <div class="progress">
  <div class="progress-bar progress-bar-striped bg-info" role="progressbar" style="width: ' . $occupied .'%" aria-valuenow="' . $occupied .'" aria-valuemin="0" aria-valuemax="100"></div>
</div></td></tr>
						 
						 
						 
						 	';
                    }

		?>
		
	</table>                 

</div>
		</div>		
		
		
        <!-- Display patients from the database -->
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">You have <?php echo $row_count; ?> patients in your rescue</h6>
<Br> <a href="https://rescuecentre.org.uk/new_admission/" class="btn btn-outline-success">New Admission</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm table-hover" id="admittable" width="100%" cellspacing="0">
                        <thead class="thead-dark">
                            <tr>
                                <th width="120">Admission<br>Date</th>
                                <th class="align-middle" width="200">Location</th>
								<th class="align-middle">Patient</th>

                                <th class="align-middle">Presenting Complaint</th>
                                <th width="300"></th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php			
                            //Loop from admissions table
                            $stmt = $conn->prepare("SELECT * 
                            FROM rescue_admissions
                            INNER JOIN rescue_patients
                            ON rescue_admissions.patient_id = rescue_patients.patient_id
                            WHERE rescue_patients.centre_id = :centre_id AND rescue_admissions.disposition = 'Held in captivity' 
                            ORDER by 'admission_location' ASC");
                            $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

                            // initialise an array for the results
                            $admissions = array();
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $admission_id = $row["admission_id"];
                                $admission_patient_id = $row["patient_id"];
                                $admission_date = $row["admission_date"];
                                $admission_name = $row["name"];
                                $admission_animal_type = $row["animal_type"];
                                $admission_animal_species = $row["animal_species"];
                                $admission_sex = $row["sex"];
                                $admission_presenting_complaint = $row["presenting_complaint"];
                                $admission_starved = $row["starved"];
                                $admission_dehydrated = $row["dehydrated"];
                                $admission_weight = $row["weight"];
                                $admission_weight_unit = $row["weight_unit"];
                                $admission_measurement = $row["measurement"];
								$admission_location = $row["current_location"];
                                $admission_measurement_unit = $row["measurement_unit"];
								$admission_time_to_admission = $row["time_to_admission"];
                                $admission_date = $row["admission_date"];
                                
								$adm_format_date = new DateTime($admission_date);
   								$adm_format_date = $adm_format_date->format('d-m-Y <\b\r> H:i'); ?>


                                <tr>
                                <td><?php echo $adm_format_date; ?></td>
                                <td class="align-middle"><?php echo $admission_location; ?></td>
								<td class="align-middle">CRN: <?php echo $admission_patient_id; ?> - <b><?php echo $admission_name; ?></b> (<?php echo $admission_sex; ?>)<BR><?php echo $admission_animal_species; ?> (<?php echo  $admission_animal_type; ?>)</td>

                                <td class="align-middle"><?php echo $admission_presenting_complaint; ?></td> 

                                <td class="align-middle">
								<!-- icon button group -->
								
	<div class="btn-group">
	   <a href="https://rescuecentre.org.uk/view-patient/?patient_id=<?php echo $admission_patient_id; ?>" type="button" class="btn btn-success" data-toggle="tooltip" data-placement="top" title="Manage Patient Record"><i class="fas fa-file" ></i></a>				
      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#carenotesModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>"data-toggle="tooltip" data-placement="top" title="Add a care note"><i class="fas fa-clipboard" ></i></button>
      <button type="button" class="btn btn-info" data-toggle="modal"  data-target="#medicationModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>" data-toggle="tooltip" data-placement="top" title="Medications"><i class="fas fa-syringe" ></i></button>
 	<!--<button type="button" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="Triage"><i class="fas fa-user-injured" ></i></button> -->
	 <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#treatmentModal" data-id="<?php echo $admission_patient_id; ?>" data-name="<?php echo $admission_name; ?>" data-toggle="tooltip" data-placement="top" title="Add a treatment"><i class="fas fa-bath" ></i></button>							
		</div>
<div class="btn-group">		
<button type="button" class="btn btn-secondary" data-toggle="tooltip" data-placement="top" title="Add weight"><i class="fas fa-weight" ></i></button> 
<button type="button" class="btn btn-dark" data-toggle="tooltip" data-placement="top" title="Add measurement"><i class="fas fa-ruler" ></i></button> 
</div>
														
						</div>
									<?php } ?> 
								
															
								
								</td>


                        </tbody>
                    </table>
                </div>
            </div>
        </div>
			
		<?php include ("care_plans/add_carenote.php"); ?>
		<?php include ("care_plans/add_treatment.php"); ?>
		<?php include ("care_plans/add_medsadmin.php"); ?>
				
        <!------------------------------------------------------->
		
		        <!-- Display patients from the database that are trenasferred in -->
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Temporary guests</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered angelo_table" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Admission Date</th>
                                <th>Patient</th>
								<th>Location</th>
                                <th>Animal Type</th>
                                <th>Sex</th>
                                <th>Received from</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>Admission Date</th>
                                <th>Patient</th>
								<th>Location</th>
                                <th>Animal Type</th>
                                <th>Sex</th>
                                <th>Received from</th>
                                <th></th>
                            </tr>
                        </tfoot>
                        <tbody>

                            <?php
                            //Loop from admissions table - query not functioning - need the transfer ID to match the centre ID and then display results. 
                            $stmt = $conn->prepare("SELECT * 
                            FROM rescue_admissions
                            INNER JOIN rescue_patients
                            ON rescue_admissions.patient_id = rescue_patients.patient_id
                            WHERE rescue_admissions.disposition = 'Held in captivity' AND rescue_patients.transfer_id = :centre_id 
                            ORDER by `admission_date` DESC");
                            $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

                            // initialise an array for the results
                            $applicants = array();
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $admission_id = $row["admission_id"];
                                $admission_patient_id = $row["patient_id"];
                                $admission_date = $row["admission_date"];
                                $admission_name = $row["name"];
                                $admission_animal_type = $row["animal_type"];
                                $admission_animal_species = $row["animal_species"];
                                $admission_sex = $row["sex"];
                                $admission_presenting_complaint = $row["presenting_complaint"];
                                $admission_starved = $row["starved"];
                                $admission_dehydrated = $row["dehydrated"];
                                $admission_weight = $row["weight"];
                                $admission_weight_unit = $row["weight_unit"];
                                $admission_measurement = $row["measurement"];
								$admission_location = $row["current_location"];
                                $admission_measurement_unit = $row["measurement_unit"];
								$admission_time_to_admission = $row["time_to_admission"];
								
								$date_created = $row["date_created"];
                                $date_created  = new DateTime($date_created);
                                $date_created = $date_created->format('d/m/Y H:i');

                                print '<tr>
                                <td>' . $admission_date . '</td>
                                <td>' . $admission_name . '</td>
								<td>' . $admission_location . '</td>
                                <td>' . $admission_animal_species . ' (' . $admission_animal_type . ')</td>
                                <td>' . $admission_sex . '</td>
                                <td>' . $admission_presenting_complaint . '</td>

                                <td><a href="https://rescuecentre.org.uk/view-patient/?patient_id=' . $admission_patient_id . '" class="btn btn-success">Manage</a></td>';
                            }

                            ?>


                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!----------END OF HEADER SECTION --------------------------------------------->


<?php include_once "app_footer.php";?>
<!-- Page level plugins -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Page level custom scripts -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/js/demo/datatables-demo.js"></script>

<script>
  

<!-- Add an "active" CSS class to the current page on the menu -->
<script>
    document.getElementById("admissions_link").classList.add("active");
</script>


</div>
<!-- End of Main Content -->
<?php get_footer();


echo "</div>";