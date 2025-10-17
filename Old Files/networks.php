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

/* Template Name: Networks */

get_header();

include_once "app_header.php";

$current_user_id = get_current_user_id();

/*------------------------------------------------------------------ FORM PROCESSING - join or remove network-------------------------------------------------------------------*/
if (isset($_POST['networkcons'])) {

	$net_con_id = $_POST["net_con_id"];	
    $network_id = $_POST["network_id"];
    $centre_id = $_POST["centre_id"];

    try {
        $statement = $conn->prepare('INSERT INTO network_cons
            ( 
            net_con_id,
			network_id,
			centre_id)
            
            VALUES (
			:net_con_id,
            :network_id,
			:centre_id) 
			
			ON DUPLICATE KEY UPDATE
			network_id = :network_id,
			centre_id = :centre_id
			');

        $statement->execute([
            'net_con_id' => $net_con_id,
            'network_id' => $network_id,
			'centre_id' => $centre_id
            
        ]);
		echo "<meta http-equiv='refresh' content='0'>";
		
    } catch (PDOException $e) {
        echo "Database Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    }

}
/*--------------------------------  FORM PROCESSING - CREATE A NETWORK --------------------------------*/
if (isset($_POST['networkform'])) {

$n_name = $_POST["network_name"];
$n_desc = $_POST["network_description"];

try {
    $statement = $conn->prepare('INSERT INTO rescue_networks
        (network_name, 
        network_description)
        
        VALUES (:network_name, 
        :network_description) 
        ');

    $statement->execute([
        'network_name' => $n_name,
        'network_description' => $n_desc
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
                    <h1 class="h3 mb-0 text-gray-800 portal_heading">Networks</h1>
                </div>

            </div>
        </div>
		
   <!-- Display my networks from the database -->
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">My Networks</h6>
                <p class="card_subheading">These are the networks you are affiliated to.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered angelo_table" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Network</th>
                                <th>Description</th>
								<th colspan="2" width="75px">Actions</th>
								
                                
                            </tr>
                        </thead>
                        
                        <tbody>

                            <?php
                            //Find networks in table. 
                            $stmt = $conn->prepare("SELECT * 
								FROM network_cons
								JOIN rescue_networks
									ON rescue_networks.network_id = network_cons.network_id
                           			WHERE network_cons.centre_id = $centre_id
								ORDER BY network_name DESC");
							
                            // initialise an array for the results
                            $mynetworks = array();
                            $stmt->execute();
							$stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
 								
							while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $network_id = $row["network_id"];
                                $network_name = $row["network_name"];
                                $description = $row["network_description"];
								$net_con_id = $row["net_con_id"];

                                print '<tr>
                            <td>' . $network_name . '</a></td>
                            <td>' . $description . '</td>

								
							<td width="50px"><form method="post" action="">
							<input type="hidden" id="net_con_id" name="net_con_id" value="'. $net_con_id . '">
							<input type="hidden" id="centre_id" name="centre_id" value="0">
							<input type="hidden" id="network_id" name="network_id" value="0">
							<button type="submit" class="delete btn btn-danger btn-sm" name="networkcons">Unjoin</button> 
                    </form>              
                           <td width="50px"><a href="https://rescuecentre.org.uk/view-network/?network_id=' . $network_id . '"> <button type="submit" class="btn btn-success btn-sm">View</button></a>
						   </td>  
							</tr>';
								
					
								
                            }

                            ?>


                        </tbody> 
                    </table>
                </div>
            </div>
		</div>
        <!-- Display networks from the database -->
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Available Networks</h6>
                <p class="card_subheading">These are the available networks you/your centre can affiliate to.</p>
                <a href="#" data-toggle="modal" data-target="#networkModal" class="btn btn-success">Create a Network</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered angelo_table" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Network</th>
                                <th>Description</th>
								<th></th>
							
                                
                            </tr>
                        </thead>
                        
                        <tbody>

                            <?php
                            //Find networks in table. 
                            $stmt = $conn->prepare("SELECT * 
								FROM rescue_networks
								ORDER BY network_name DESC");
							
                            // initialise an array for the results
                            $applicants = array();
                            $stmt->execute();
							$stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
 								
							while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $network_id = $row["network_id"];
                                $network_name = $row["network_name"];
                                $description = $row["network_description"];

                                print '<tr>
                            <td>' . $network_name . '</a></td>
                            <td>' . $description . '</td>

								
							<td width="50px"><form method="post" action="">
							<input type="hidden" id="network_id" name="network_id" value="'. $network_id . '">
							<input type="hidden" id="centre_id" name="centre_id" value="' . $centre_id . '">
							<button type="submit" class="delete btn btn-info btn-sm" name="networkcons">Join</button> 
                    </form>
							
							</tr>';
								
					
								
                            }

                            ?>


                        </tbody> 
                    </table>
                </div>
            </div>
		</div>
		

</div>
<!-- End of Main Content -->
  
<!-- Add A NEW NETWORK Modal -->

				   
<div class="modal fade" id="networkModal" tabindex="-1" role="dialog" aria-labelledby="networkModal" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="font-weight-bold text-primary">Create a new network</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="post">
                <input type="hidden" id="centre_id" name="centre_id" value="<?php echo $centre_id; ?>">
                <div class="col-md-6">
                    <p class="angelo_form_label">Network Name</p>
					<input type="text" name="network_name" id="network_name" placeholder="Name of your network" required>
                </div>
				<div class="col-md-6">
                    <p class="angelo_form_label">Description</p>
				    <input type="text" name="network_description" id="network_description" placeholder="Brief description" required>
                </div>
                <input type="submit" id="submit" name="networkform" value="Save" class="form_submit">
                </form>
            </div>
        </div>
    </div>
</div>
<!-- END OF ADD NEW NETWORK MODAL -->

<?php include_once "app_footer.php";
?>
<!-- Page level plugins -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/datatables/dataTables.bootstrap4.min.js"></script>

<!-- Page level custom scripts -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/js/demo/datatables-demo.js"></script>



<!-- Add an "active" CSS class to the current page on the menu -->
<script>
    document.getElementById("settings_link").classList.add("active");
</script>

<script>
    document.getElementById("networks_link").classList.add("active");
</script>
</div>
<!-- End of Main Content -->
<?php 
echo "</div>";