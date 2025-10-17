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

/* Template Name: Connections */

get_header();

include_once "app_header.php";

/*------------------------------------------------------------------ FORM PROCESSING Connections -------------------------------------------------------------------*/
//Check if the location form was submitted
if (isset($_POST['connectionform'])) {
    

    $from_centre = $_POST["from_centre"];
    $to_centre = $_POST["to_centre"];
    $approved = $_POST["approved"];
	$centre_id = $_POST["centre_id"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_connections
            (
			to_centre,
			approved,
			centre_id,
			from_centre)
            
            VALUES (
			:to_centre,
			:approved,
            :centre_id,			
			:from_centre)
			
			');

        $statement->execute([
            'to_centre' => $to_centre,
            'from_centre' => $from_centre,
			'centre_id' => $centre_id,
            'approved' => $approved
			]);
    } catch (PDOException $e) {
        echo "Database Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    }
}

/*------------------------------------------------------------------ FORM PROCESSING UPDate/Approve -------------------------------------------------------------------*/
//Check if the location form was submitted
if (isset($_POST['approveform'])) {
    

    $connection_id = $_POST["connection_id"];
    $approved = $_POST["approved"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_connections
            (connection_id,
			approved)
            
            VALUES (:connection_id,
			:approved)
			
			ON DUPLICATE KEY UPDATE
			approved = :approved
			
			');

        $statement->execute([

			'connection_id' => $connection_id,
            'approved' => $approved
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


<div id="page-top">


    <!-- Begin Page Content -->
    <div class="container-fluid">

        <!-- Page Heading -->
        <div>
            <div class="row dashboard_heading_withfilter">
                <div class="col-md-6 my-auto">
                    <h1 class="h3 mb-0 text-gray-800 portal_heading">Connections</h1>
                </div>

            </div>
        </div>
		
  <!-- CONNECTIONS CARD  -->
		
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Manage your connections</h6>
                <p class="card_subheading">Connections are individual partnerships with other rescues. By connecting with another rescue you will be able to share a patient record with them. </p>
				
                                      </div>
            <div class="card-body">
			
	
	              <div class="table-responsive">
                             

 <div class="row">
	         <!-- My connections -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">My Connections</h6>

                </div>
                <!-- Card Body -->
                <div class="card-body">
									<div class="table-responsive">
										
					</div>
					<table class="table table-bordered table-sm table-hover" id="dataTable" width="100%" cellspacing="0">
					<thead>
                            <tr>
                                <th>Centre</th>
                                <th>City</th>
                                
                            </tr>
                        </thead>
                
                <?php
                    //Get ALL connections
                    $stmt = $conn->prepare("SELECT * FROM rescue_connections
                   		 LEFT JOIN rescue_centres
                   		 ON rescue_centres.rescue_id = rescue_connections.to_centre
					WHERE (from_centre = :centre_id) 
					AND rescue_connections.approved = 'Yes' 
					ORDER BY 'rescue_name'
					"); 
									
                    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);	
                    //initialise an array for the results
                    $approvedconnections = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                    $a_rescue_name = $row["rescue_name"];
					$a_rescue_city= $row["city"];
                    $a_approved = $row["approved"];

                    print '

					<tr><td>' . $a_rescue_name . ' </td><td>' . $a_rescue_city . '</td>

                          
                          ';
                    }

                    ?>
        		<?php
                    //Get ALL connections
                    $stmt = $conn->prepare("SELECT * FROM rescue_connections
                   		 LEFT JOIN rescue_centres
                   		 ON rescue_centres.rescue_id = rescue_connections.from_centre
					WHERE (to_centre = :centre_id) 
					AND rescue_connections.approved = 'Yes' 
					ORDER BY 'rescue_name'
					"); 
									
                    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);	
                    //initialise an array for the results
                    $approvedconnections = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                    $b_rescue_name = $row["rescue_name"];
					$b_rescue_city= $row["city"];
                    $b_approved = $row["approved"];

                    print '
                    <tr> <td>' . $b_rescue_name . ' </td><td>' . $b_rescue_city . '</td>
                          
                          ';
                    }

                    ?>					
						
						</tr>
                       </table></div>
						<BR>
							</div>
                    
        </div>

        <!-- Sent requests -->

        <!-- Pending requests - received -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Requests to approve</h6>

                </div>
                <!-- Card Body -->
                <div class="card-body">
									<div class="table-responsive">
										
					</div>
					<table class="table table-bordered table-sm table-hover" id="dataTable" width="100%" cellspacing="0">
					<thead>
                            <tr>
                                <th>Centre</th>
                                <th>City</th>
                               
							    <th></th>
                                
                            </tr>
                        </thead>
                
                
        		<?php
                    //receoivedconnections
                    $stmt = $conn->prepare("SELECT * FROM rescue_connections
                    LEFT JOIN rescue_centres
                    ON rescue_centres.rescue_id = rescue_connections.from_centre
					WHERE (to_centre = :centre_id) AND rescue_connections.approved = 'No' 
					ORDER BY rescue_name
					"); 
									
                    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);	
                    //initialise an array for the results
                    $mypendingout = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                    $c_rescue_name = $row["rescue_name"];
					$c_rescue_city= $row["city"];
                    $c_approved = $row["approved"];
					$c_connection_id = $row["connection_id"];
                    print '
                            
                    <tr>
					<TD>' . $c_rescue_name . ' </td>
                    <td> ' . $c_rescue_city . ' </td>
					
                     <td> <form method="post" action="">
							<input type="hidden" id="connection_id" name="connection_id" value="'. $c_connection_id. '">
							<input type="hidden" id="approved" name="approved" value="Yes">
							<button type="submit" class="btn btn-info" name="approveform">Approve</button> 
                    </form></td>     
                          ';
                    }

                    ?>


                       </table></div>
						<BR>
							</div>
                    
        </div>

        <!-- Sent requests -->
		  
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Sent Requests</h6>
                </div>
                <!-- Card Body -->
                <div class="card-body">					
                     <div class="table-responsive">
					<table class="table table-sm table-hover angelo_table" id="dataTable" width="100%" cellspacing="0">
					<thead>
                            <tr>
                                <th>Centre</th>
                                <th>City</th>
                         
							
                                
                            </tr>
                        </thead>
					<?php
                    //Sent connections
                    $stmt = $conn->prepare("SELECT * FROM rescue_centres
                    LEFT JOIN rescue_connections
                    ON rescue_centres.rescue_id = rescue_connections.to_centre
					WHERE centre_id = :centre_id AND approved='No'
					ORDER BY rescue_name
					"); 
									
                    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);	
                    //initialise an array for the results
                    $mypendingout = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                    $p_rescue_name = $row["rescue_name"];
					$p_rescue_city= $row["city"];
                    $p_approved = $row["approved"];

                    print '
                            
                    <tr>
					<TD>' . $p_rescue_name . ' </td>
                    <td> ' . $p_rescue_city . ' </td>
					
            </div>
                          
                          ';
                    }

                    ?>
                        </table></div>
						<BR>
            </div>
        </div>
    </div>					

<!-- END  OF CONNECTIONS CARD -->
			</div></div></div>	</div>
<!-- RESCUES VIEW CARD  -->
		
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Rescue centres</h6>
                <p class="card_subheading">You can view other rescue centres and request to connect with them</p>
				
                           
            </div>
            <div class="card-body">
			
	
	              <div class="table-responsive">
                  
					<table class="table table-bordered table-sm table-hover" id="dataTable" width="100%" cellspacing="0">

					<thead>
                            <tr>
                                <th>Centre</th>
                                <th>City</th>
                                <th>Action</th>
  
                            </tr>
                        </thead>
                
                
                  <?php
                    //Sent connections
                    $stmt = $conn->prepare("SELECT * FROM rescue_centres
                    						LEFT JOIN rescue_connections ON rescue_connections.to_centre = rescue_centres.rescue_id
											WHERE approved IS NULL
											ORDER BY rescue_name
											");
                                

                                 // initialise an array for the results
                                $centres = array();
                                $stmt->execute();
                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
									//this rescue_id is to_centre
                                    $rescue_id = $row["rescue_id"];
                                    $rescue_name = $row["rescue_name"];
                                    $city = $row["city"];
									

                                    print '<tr><td>' . $rescue_name . ' </td><td> ' . $city . '</td>
									<td> <form method="post" action="">
							<input type="hidden" id="to_centre" name="to_centre" value="'. $rescue_id. '">
							<input type="hidden" id="centre_id" name="centre_id" value="' . $centre_id . '">
							<input type="hidden" id="from_centre" name="from_centre" value="' . $centre_id . '">
							<input type="hidden" id="approved" name="approved" value="No">
							<button type="submit" class="btn btn-info btn-sm" name="connectionform">Connect</button> 
                    </form> </td>
									
									';
                                }

						?></tr>
                            </table>

                        </table> 
						</div> <br>
						
						
						
						<BR>
							</div></div>

<!-- END  OF CONNECTIONS CARD -->				
				


			
			
			
			
			
			
<!-- End of Main Content -->


<?php include_once "app_footer.php";
?>
<!-- Page level plugins -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Page level custom scripts -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/js/demo/datatables-demo.js"></script>


<script>
    //AJAX Scripts

    //Insert Patient AJAX
    $(document).ready(function() {
        $('#manualForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                type: 'post',
                url: 'https://rescuecentre.org.uk/wp-content/themes/brikk-child/ajax/insert_patient.php',
                data: $('#manualForm').serialize(),
                success: function() {
                    var currentFilter = document.getElementById("status_filter").value;
                    getPeople(currentFilter, <?php echo $current_user_id; ?>);
                    document.getElementById("alertMsg").innerHTML = '<div class="alert alert-success" role="alert">Your patient has been added to the database.</div>';
                }
            });
        });
    });
</script>

<!-- Add an "active" CSS class to the current page on the menu -->
<script>
    document.getElementById("settings_link").classList.add("active");
</script>
<script>
    document.getElementById("connections_link").classList.add("active");
</script>

</div>
<!-- End of Main Content -->
<?php 
echo "</div>";