 <?php defined('ABSPATH') or die('This script cannot be accessed directly.');

include_once "authentication.php";
include_once "connect_to_mysql.php";


echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *xx
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* Template Name: Custom Query */

get_header();
$current_user_id = get_current_user_id();

include_once "app_header.php";

$current_user_id = get_current_user_id();

//Get the current Rescue Centre data from the database
$sql = 'SELECT * FROM rescue_centres WHERE rescue_id=:centre_id LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $rescue_name = $result["rescue_name"];

} else {
    echo "Rescue centre not found";
    exit();
}

/*------------------------------------------------------------------ FORM PROCESSING - QUERY BUILDER-------------------------------------------------------------------*/
//QUERY - Only one record per centre at one time. *IMPORTANT* as multiple entries per centre will affect data pull. This either create a new one per centre OR updates -
// Key used for this is centre_id and this is indexed in the table rescue_queries
if (isset($_POST['queryform'])) {

    $q_from = $_POST["q_from"];
    $q_to = $_POST["q_to"];
	
	$q_date = date('Y-m-d H:i:s');
	
    try {
        $statement = $conn->prepare('INSERT INTO rescue_query
            (centre_id, 
            q_from,
			q_date,
            q_to)
            
            VALUES (:centre_id, 
            :q_from,
			:q_date,
            :q_to) 
			
			ON DUPLICATE KEY UPDATE
			q_from = :q_from,
			q_to = :q_to,
			q_date = :q_date
			');

        $statement->execute([
            'centre_id' => $centre_id,
            'q_from' => $q_from,
			'q_date' => $q_date,
            'q_to' => $q_to
        ]);
    } catch (PDOException $e) {
        echo "Database Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    }
}

?>
    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <div>
            <div class="row dashboard_heading_withfilter">
                <div class="col-md-6 my-auto">
                    <h1 class="h3 mb-0 text-gray-800 portal_heading">Generate Data report</h1>
                </div>
            </div>
        </div>




    
 <!-- Display query from the database -->
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Last Query</h6>
    
 
            </div>
            <div class="card-body">
			<P>Below you can select any dates that you wish to view the data for that period. 
			<BR>The query will show data based on the admission date of the animal.</p>
                <div class="table-responsive">
                    <table class="table table-bordered angelo_table" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Date query ran</th>
                                <th>Date from</th>
                                <th>Date to</th>                               
                            </tr>
                        </thead>
                        
                        <tbody>


                            <?php
                                //Find locations stored in the patients table 
                               $stmt = $conn->prepare("SELECT * 
                                FROM rescue_query
                                WHERE centre_id = :centre_id ORDER BY q_date DESC LIMIT 1");
                                $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

                                 // initialise an array for the results
                                $queries = array();
                                $stmt->execute();
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                                    $q_date = $row["q_date"];
                                    $q_from = $row["q_from"];
                                    $q_to = $row["q_to"];
									

                                    print '<TR>
                                    <td>' . $q_date . '</td>									
									<td>' . $q_from . '</td>
									<td>' . $q_to . '</td> </tr>';
                                }

                               ?>


                        </tbody> 
                    </table>
					<a href="#" data-toggle="modal" data-target="#querybuilderModal" class="btn btn-success">Edit Query</a>
					
                </div>
            </div>
        </div>
        <!------------------------------------------------------->



<div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Results </h6>
            </div>
            <div class="card-body">
			Use the download CSV button to download an excel suitable CSV file of the data shown.
                <div class="table-responsive">
                    <table class="table table-bordered angelo_table" id="dataTable" width="100%" cellspacing="0">
					 <form method='post' action='https://rescuecentre.org.uk/wp-content/themes/brikk-child/download_csv.php'>
					<input type='submit' value='Download CSV File' name='export'>   
					
                        <thead>
                            <tr>
                                <th>Admission Date</th>
                                <th>Patient</th>
								<th>Age</th>
                                <th>Animal Type</th>
                                <th>Sex</th>
                                <th>Presenting Complaint</th>
                                <th>Disposition</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>Admission Date</th>
                                <th>Patient</th>
								<th>Age</th>
                                <th>Animal Type</th>
                                <th>Sex</th>
                                <th>Presenting Complaint</th>
                                <th>Disposition</th>
                            </tr>
                        </tfoot>
                        <tbody>

                            <?php
                            //query to link custom query fields with data from admissions 
                            $stmt = $conn->prepare("SELECT * 
                            FROM rescue_admissions
                            INNER JOIN rescue_query
                            ON rescue_admissions.centre_id = rescue_query.centre_id
							INNER JOIN rescue_patients
                            ON rescue_admissions.patient_id = rescue_patients.patient_id
                            WHERE rescue_patients.centre_id = :centre_id AND rescue_admissions.admission_date >= rescue_query.q_from AND rescue_admissions.admission_date <= rescue_query.q_to
                            
							ORDER by `admission_date` DESC");
                            // bind parameters
							$stmt->bindParam(':centre_id', $centre_id);

                            // initialise an array for the results
                            $userquery = array();
							$user_arr = array();
                            $stmt->execute();
													
							// get row count
                            $totalrecords = $stmt->rowCount();
							
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $query_id = $row["admission_id"];
                                $query_patient_id = $row["patient_id"];
                                $query_date = $row["admission_date"];
                                $query_name = $row["name"];
								$query_age = $row["age_on_admission"];
                                $query_animal_type = $row["animal_type"];
                                $query_animal_species = $row["animal_species"];
								$query_animal_order = $row["animal_order"];
                                $query_sex = $row["sex"];
                                $query_presenting_complaint = $row["presenting_complaint"];
                                $query_starved = $row["starved"];
                                $query_dehydrated = $row["dehydrated"];
                                $query_weight = $row["weight"];
                                $query_weight_unit = $row["weight_unit"];
                                $query_measurement = $row["measurement"];
								$query_location = $row["current_location"];
                                $query_measurement_unit = $row["measurement_unit"];
								$query_time_to_admission = $row["time_to_admission"];
								$query_collection_location = $row["collection_location"];
								$query_disposition = $row["disposition"];
								$query_ring_number = $row["ring_number"];
							    $query_microchip_number = $row["microchip_number"];
								$query_weather_w = $row["w_wind"];
								$query_weather_h = $row["w_humidity"];
								$query_weather_t = $row["w_temp"];
								$query_weather_f = $row["w_freetext"];
								
						        // IF amending here - also amend headers in downloadcsv php file	
								$user_arr[] = array($query_date, $query_name, $query_animal_order, $query_animal_type, $query_animal_species, $query_age, $query_sex, $query_presenting_complaint, $query_disposition, $query_starved, $query_dehydrated, $query_weather_w, $query_weather_h, $query_weather_t, $query_weather_f,);
	
                                print '<tr>
							
                                <td>' . $query_date . '</td>
                                <td>' . $query_name . '</td>
								<td>' . $query_age . '</td>
                                <td>' . $query_animal_species . ' <BR>('. $query_animal_order .' - ' . $query_animal_type . ')</td>
                                <td>' . $query_sex . '</td>
                                <td>' . $query_presenting_complaint . '</td>
                                <td>' . $query_disposition . '</td>';
	
							}

                            ?>
							
<P> <?php echo $totalrecords; ?> records were found matching your criteria </p>	 
							 
                        </tbody>
						
						<?php $serialize_user_arr = serialize($user_arr); ?>
						<textarea name='export_data' style='display: none;'><?php echo $serialize_user_arr; ?></textarea>
    </form>
</div>


                    </table>

                </div>
            </div>
        </div>
        <!------------------------------------------------------->


 <div class="row">

        <!-- Admissions -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Admissions for this Query</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalrecords; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ambulance fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- adjusted efficency -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Efficiency for this query</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $incare; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-heartbeat fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Released -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Animals released
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $released; ?></div>
                                </div>
                                <div class="col">
                                    <!--<div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div> 
                                    </div> -->
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dove fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- died -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Animals that have died</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $dead; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
	
	

    <!-- Content Row -->














   
<!-- Add A Query builder Modal -->

				   
                    <div class="modal fade" id="querybuilderModal" tabindex="-1" role="dialog" aria-labelledby="querybuilderModal" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                               <div class="modal-header">
                                    <h4 class="font-weight-bold text-primary">Set Query Parameters</h4>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">

                   
                                        <form action="" method="post">
                        <input type="hidden" id="centre_id" name="centre_id" value="<?php echo $centre_id; ?>">

                       <div class="col-md-6">
                                 <p class="angelo_form_label">Query From</p>
								<input type="date" name="q_from" id="q_from" placeholder="date" required>

                        </div>
						<div class="col-md-6">
                                 <p class="angelo_form_label">Query To</p>
								<input type="date" name="q_to" id="q_to" placeholder="date" required>

                        </div>

                        <input type="submit" id="submit" name="queryform" value="Save parameters" class="form_submit">
                    
                    </form></div></div></div></div>

                    <!--- End Of Notes ---->
                         
                        
                    

                    <!---------------END of Query builder modal ----------------------------------------------------------->



<?php include_once "app_footer.php";
?>

</div>


<script>
    document.getElementById("query_link").classList.add("active");
</script>
<!-- End of Main Content -->

<?php
echo "</div>";?>