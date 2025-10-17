<?php

include_once "authentication.php";
include_once "connect_to_mysql.php";

echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* Template Name: Network View */
 get_header();

include_once "app_header.php";
//Retrieve the GET value from the URL, and sanitise it for security purposes
function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_GET["network_id"])) {
    $network_id = test_input($_GET["network_id"]);
} else {
    echo "Error #1 - Patient not found.";
    exit();
}

if (isset($_GET["alert"])) {
    $alert = test_input($_GET["alert"]);

    if ($alert = 1) {
        $alertmsg = "<div class='alert alert-success' role='alert'>
        This patient's details were updated in the database.
        </div>";
    } else if ($alert = 2) {
        $alertmsg = "<div class='alert alert-success' role='alert'>
        Alert
        </div>";
    } else {
        $alertmsg = "";
    }
} else {
    $alertmsg = "";
}


//Get the information from the database
$sql = 'SELECT * FROM rescue_networks WHERE network_id=:network_id LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':network_id', $network_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $network_name = $result["network_name"];
    $network_description = $result["network_description"];

//this can add an extra link or button dependent on the network ID value
if ($network_id = 1 ) {
      $networkbutton = '';
} elseif ($network_id = 2) {
     $networkbutton = '';
}
  
} else {
    echo "Error 2";
    exit();
}	

//Row Count (total admissions)
$totadm = "SELECT * FROM rescue_admissions
INNER JOIN rescue_patients
ON rescue_admissions.patient_id = rescue_patients.patient_id
LEFT JOIN network_cons ON 
network_cons.centre_id = rescue_admissions.centre_id
WHERE network_cons.network_id = :network_id
ORDER by `admission_date` DESC";
$stmt = $conn->prepare($totadm);

// bind parameters
$stmt->bindParam(':network_id', $network_id);

// execute query
$stmt->execute();

// get row count
$network_admissions = $stmt->rowCount();

//Row Count (in care)
$incare = "SELECT * from rescue_admissions
LEFT JOIN rescue_patients
ON rescue_admissions.patient_id = rescue_patients.patient_id
LEFT JOIN network_cons ON 
network_cons.centre_id = rescue_admissions.centre_id
WHERE rescue_admissions.disposition = 'Held in captivity' AND network_cons.network_id = :network_id 
ORDER by `admission_date` DESC";
$stmt = $conn->prepare($incare);

// bind parameters
$stmt->bindParam(':network_id', $network_id);

// execute query
$stmt->execute();

// get row count
$network_incare = $stmt->rowCount();

//Row Count (released)
$netreleased = "SELECT * from rescue_admissions
LEFT JOIN rescue_patients
ON rescue_admissions.patient_id = rescue_patients.patient_id
LEFT JOIN network_cons ON 
network_cons.centre_id = rescue_admissions.centre_id
WHERE rescue_admissions.disposition = 'Released' AND network_cons.network_id = :network_id 
ORDER by `admission_date` DESC";
$stmt = $conn->prepare($netreleased);

// bind parameters
$stmt->bindParam(':network_id', $network_id);

// execute query
$stmt->execute();

// get row count
$network_released = $stmt->rowCount();

/*------------------------------------------------------------------ FORM PROCESSING group chat -------------------------------------------------------------------*/
//Check if the location form was submitted
if (isset($_POST['chatform'])) {
    

    $from_centre = $_POST["from_centre"];
    $network_id = $_POST["network_id"];
    $chat_sent = $_POST["chat_sent"];
	  $chat_user = $_POST["chat_user"];
	  $chat = $_POST["chat"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_net_chat
            (
			from_centre,
			network_id,
			chat_sent,
			chat_user,
			chat)
            
            VALUES (
			:from_centre,
			:network_id,
            :chat_sent,	
			:chat_user,
			:chat)
			
			');

        $statement->execute([
            'from_centre' => $from_centre,
            'network_id' => $network_id,
			'chat_sent' => $chat_sent,
			'chat_user' => $chat_user,
            'chat' => $chat
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
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $network_name ?>'s Dashboard</h1>
        <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-download fa-sm text-white-50"></i> Generate Report</a>


<!-- button to go here for other network things --> 


      </div>

		<!-- Area Chart -->
 <div class="card shadow mb-4">
                <!-- Card Header - Dropdown -->
                  <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Occupancy</h6>

                </div>
                <!-- Card Body -->
               
		
<div class="card-body">
				
 <table>
		
	
		<?php
                    //Get count
                    $stmt = $conn->prepare("SELECT admission_id, rescue_admissions.centre_id, rescue_name, COUNT(admission_id) AS Total
													from rescue_admissions
													LEFT JOIN rescue_patients
													ON rescue_admissions.patient_id = rescue_patients.patient_id
													LEFT JOIN network_cons ON 
													network_cons.centre_id = rescue_admissions.centre_id
													LEFT JOIN rescue_centres ON
													network_cons.centre_id = rescue_centres.rescue_id
							WHERE rescue_admissions.disposition = 'Held in captivity' AND network_cons.network_id = :network_id
							GROUP BY network_cons.centre_id
							ORDER by 'rescue_name' DESC
											");
					
                   $stmt->bindParam(':network_id', $network_id, PDO::PARAM_INT);

                    // initialise an array for the results
                    $net_adm = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $rescue = $row["rescue_name"];
						$total = $row["Total"];
                             

                         print '
						<tr> <td width ="25%">
						 ' .$rescue. '</td><td width="5%"><strong>' .$total. '</strong></td></tr>
						 
						 
						 
						 	';
                    }

		?>
		
	</table>                 

</div>
		</div>		

	
	

			
    <div class="row">

        <!-- Admissions -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Admissions across network</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $network_admissions; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ambulance fa-2x text-gray-300"></i>
                        </div>
                    </div>
					
					
					
                </div>
            </div>
        </div>

        <!-- In care -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                In care in network</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $network_incare; ?></div>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Network animals released
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $network_released; ?></div>
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
                                Network animals that died</div>
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

    <div class="row">

        <!-- Area Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Network Admissions Per Month</h6>

                </div>
                <!-- Card Body -->
                <div class="card-body">
				
                    <div class="chart-area">
                        <canvas id="admissionChart"></canvas>
                    </div><br>
                </div>
            </div>
        </div>

        <!-- Pie Chart -->

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Network Admissions By Species</h6>
                </div>
                <!-- Card Body -->
                <div class="card-body">		<BR>		
				<div class="chart-area">
                        <canvas id="SpeciesChart"></canvas>
						</div>
				</div>
            </div>
        </div>
    </div>
	 <!-- Content Row -->

    <div class="row">

        <!-- Area Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Causes for admission</h6>

                </div>
                <!-- Card Body -->
                <div class="card-body">
				
                    <div class="chart-area">
                        <canvas id="complaintChart"></canvas>
                    </div><br>
                </div>
            </div>
        </div>

        <!-- Pie Chart -->
		  
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Admissions by Age</h6>
                </div>
                <!-- Card Body -->
                <div class="card-body">		<BR>		
				<div class="chart-area">
                        <canvas id="ageChart"></canvas>
						</div>
				</div>
            </div>
        </div>
    </div>	
	



				

<div class="row">
       
	 <!-- Chat window -->
        <div class="col-xl-12 col-md-12 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Group Chat</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
								
							<?php
                    //rgroup cjhat
                    $stmt = $conn->prepare("SELECT * FROM rescue_net_chat
                    
					WHERE (network_id = :network_id) 
					ORDER BY chat_id DESC LIMIT 5
					"); 
									
                    $stmt->bindParam(':network_id', $network_id, PDO::PARAM_INT);	
                    //initialise an array for the results
                    $groupchat = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                    $from_centre = $row["from_centre"];
					$chat_sent = $row["chat_sent"];
                    $chat = $row["chat"];
					$chat_user = $row["chat_user"];
					$network_id = $row["network_id"];
                    print '

				   <div class="note_message speech bottom"> ' . $chat_user . ' - ' . $chat . ' <br></div>

                          ';
                    }
								?><table>
						
						</table><tr>                     <td> <form method="post" action="">
								<input type="hidden" id="network_id" name="network_id" value="1">
								<input type="hidden" id="from_centre" name="from_centre" value="1">
								<input type="hidden" id="chat_user" name="chat_user" value="<?php echo $wp_fullname; ?>">
								<input type="hidden" id="chat_sent" name="chat_sent" value="31/10/2024">
							<td><input type="text" id="chat" name="chat" size="5"></td>
							<td><button type="submit" class="btn btn-sm btn-success" name="chatform">Send message</button> </td>
						</form></td>  </tr></table>

								
								
								
							
</div>
                        </div>
					</div></div>				
			</div></div>
	
	</div>		

				


        <!------------------------------------------------------->

</div>
<!-- /.container-fluid -->


<?php include_once "app_footer.php";
?>
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/js/demo/chart-area-demo.js"></script>
</div>

<script>
//BY SPECIES PIE CHART 
// Set new default font family and font color to mimic Bootstrap's default styling
Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#858796';

// Pie Chart Example
var ctx = document.getElementById("SpeciesChart");
var myPieChart = new Chart(ctx, {
  type: 'doughnut',
  data: {
    labels: [<?php
                    //Get by species breakdown
                    $stmt = $conn->prepare("SELECT animal_species, COUNT(animal_species) AS total_species
											FROM rescue_patients
											WHERE rescue_patients.centre_id = :centre_id
											GROUP BY animal_species");
					
                    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
					

                    // initialise an array for the results
                    $by_species = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $species_name = $row["animal_species"];
						
                   
                             

                         print '"' . $species_name . '"	,';
                    }

                    ?>],
    datasets: [{
      data: [<?php
                    //Get by species breakdown
                    $stmt = $conn->prepare("SELECT animal_species, COUNT(animal_species) AS total_species
											FROM rescue_patients
											WHERE rescue_patients.centre_id = :centre_id
											GROUP BY animal_species");
					
                    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
					

                    // initialise an array for the results
                    $by_species = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        
						$total_species = $row["total_species"];
                   
                             

                         print '' . $total_species . ',';
                    }

                    ?>],
      backgroundColor: ['#5AAb16', '#FBDb25', '#0f4c5c', '#F5a701', '#E34d36', '#A71c5d' ],
      hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf'],
      hoverBorderColor: "rgba(234, 236, 244, 1)",
    }],
  },
  options: {
    maintainAspectRatio: false,
    tooltips: {
      backgroundColor: "rgb(255,255,255)",
      bodyFontColor: "#858796",
      borderColor: '#dddfeb',
      borderWidth: 1,
      xPadding: 15,
      yPadding: 15,
      displayColors: false,
      caretPadding: 10,
    },
    legend: {
      display: true
    },
    cutoutPercentage: 20,
  },
});
</script>

<script>
//BY AGE PIE CHART 
// Set new default font family and font color to mimic Bootstrap's default styling
Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#000';

// Pie Chart Example
var ctx = document.getElementById("ageChart");
var myPieChart = new Chart(ctx, {
  type: 'doughnut',
  data: {
    labels: [<?php
                    //Get by species breakdown
                    $stmt = $conn->prepare("SELECT age_on_admission, COUNT(age_on_admission) AS total_by_age
											   FROM rescue_admissions
											INNER JOIN rescue_patients
											ON rescue_admissions.patient_id = rescue_patients.patient_id
                      LEFT JOIN network_cons ON 
                      network_cons.centre_id = rescue_admissions.centre_id
                      WHERE network_cons.network_id = :network_id
											GROUP BY age_on_admission
											ORDER BY age_on_admission");
					
                   $stmt->bindParam(':network_id', $network_id, PDO::PARAM_INT);
					

                    // initialise an array for the results
                    $by_species = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $species_age = $row["age_on_admission"];
						
                   
                             

                         print '"' . $species_age . '"	,';
                    }

                    ?>],
    datasets: [{
      data: [<?php
                    //Get by species breakdown
                    $stmt = $conn->prepare("SELECT age_on_admission, COUNT(age_on_admission) AS total_by_age
											
                      FROM rescue_admissions
											INNER JOIN rescue_patients
											ON rescue_admissions.patient_id = rescue_patients.patient_id
                      LEFT JOIN network_cons ON 
                      network_cons.centre_id = rescue_admissions.centre_id
                      WHERE network_cons.network_id = :network_id
											GROUP BY age_on_admission
											ORDER BY age_on_admission");
					
                    
					$stmt->bindParam(':network_id', $network_id, PDO::PARAM_INT);

                    // initialise an array for the results
                    $by_species = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        
						$total_age = $row["total_by_age"];

                         print '' . $total_age . ',';
                    }

                    ?>],
      backgroundColor: ['#5AAb16', '#FBDb25', '#0f4c5c', '#F5a701', '#E34d36', '#A71c5d', '#2e546c', '#4da67c', '#77e44c', '#caff00'  ],
      hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf'],
      hoverBorderColor: "rgba(234, 236, 244, 1)",
    }],
  },
  options: {
    maintainAspectRatio: false,
    tooltips: {
      backgroundColor: "rgb(255,255,255)",
      bodyFontColor: "#858796",
      borderColor: '#dddfeb',
      borderWidth: 1,
      xPadding: 15,
      yPadding: 15,
      displayColors: false,
      caretPadding: 10,
    },
    legend: {
      display: true
    },
    cutoutPercentage: 20,
  },
});
</script>

<script>
// Admissions by month chart
// Set new default font family and font color to mimic Bootstrap's default styling
Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#858796';

function number_format(number, decimals, dec_point, thousands_sep) {
  // *     example: number_format(1234.56, 2, ',', ' ');
  // *     return: '1 234,56'
  number = (number + '').replace(',', '').replace(' ', '');
  var n = !isFinite(+number) ? 0 : +number,
    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
    s = '',
    toFixedFix = function(n, prec) {
      var k = Math.pow(10, prec);
      return '' + Math.round(n * k) / k;
    };
  // Fix for IE parseFloat(0.55).toFixed(0) = 0;
  s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
  if (s[0].length > 3) {
    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
  }
  if ((s[1] || '').length < prec) {
    s[1] = s[1] || '';
    s[1] += new Array(prec - s[1].length + 1).join('0');
  }
  return s.join(dec);
}

// Area Chart Example
var ctx = document.getElementById("admissionChart");
var myLineChart = new Chart(ctx, {
  type: 'line',
  data: {
    labels:["<?php echo $lang['MONTH_JAN']; ?>", "<?php echo $lang['MONTH_FEB']; ?>", "<?php echo $lang['MONTH_MAR']; ?>", "<?php echo $lang['MONTH_APR']; ?>", "<?php echo $lang['MONTH_MAY']; ?>", "<?php echo $lang['MONTH_JUN']; ?>", "<?php echo $lang['MONTH_JUL']; ?>","<?php echo $lang['MONTH_AUG']; ?>", "<?php echo $lang['MONTH_SEP']; ?>", "<?php echo $lang['MONTH_OCT']; ?>", "<?php echo $lang['MONTH_NOV']; ?>", "<?php echo $lang['MONTH_DEC']; ?>"],
    datasets: [{
      label: "2023",
      lineTension: 0.3,
      backgroundColor: "rgba(78, 115, 223, 0.05)",
      borderColor: "rgba(78, 115, 223, 1)",
      pointRadius: 3,
      pointBackgroundColor: "rgba(78, 115, 223, 1)",
      pointBorderColor: "rgba(78, 115, 223, 1)",
      pointHoverRadius: 3,
      pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
      pointHoverBorderColor: "rgba(78, 115, 223, 1)",
      pointHitRadius: 10,
      pointBorderWidth: 2,
      data: [<?php
                    //Get by month count
                    $stmt = $conn->prepare("WITH specific_admissions AS
     ( SELECT EXTRACT(YEAR_MONTH FROM a.admission_date) AS adm_yrmonth
         FROM network_cons AS n 
       INNER JOIN rescue_admissions AS a  
           ON a.centre_id = n.centre_id 
        WHERE n.network_id = :network_id )
SELECT MONTHNAME(m.month) MONTH_NAME
     , COUNT(specific_admissions.adm_yrmonth)   COUNT_ADMISSIONS23
  FROM rescue_month_data AS m
LEFT 
  JOIN specific_admissions
    ON specific_admissions.adm_yrmonth = EXTRACT(YEAR_MONTH FROM m.month)
 WHERE YEAR(m.month)=2023
GROUP 
    BY MONTH(m.month)
ORDER 
    BY MONTH(m.month)
											");
					
                    $stmt->bindParam(':network_id', $network_id, PDO::PARAM_INT);

                    // initialise an array for the results
                    $months23 = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $count23 = $row["COUNT_ADMISSIONS23"];
						
                         print '"' . $count23 . '"	,';
                    }

                    ?>],
    },
	
{
      label: "2024",
      lineTension: 0.3,
      backgroundColor: "rgba(107, 223, 78, 0.05)",
      borderColor: "rgba(6, 213, 82, 1)",
      pointRadius: 3,
      pointBackgroundColor: "rgba(78, 115, 223, 1)",
      pointBorderColor: "rgba(6, 213, 82, 1)",
      pointHoverRadius: 3,
      pointHoverBackgroundColor: "rgba(6, 213, 82, 1)",
      pointHoverBorderColor: "rgba(6, 213, 82, 1)",
      pointHitRadius: 10,
      pointBorderWidth: 2,
      data: [<?php
                    //Get by month count
                    $stmt = $conn->prepare("WITH specific_admissions AS
     ( SELECT EXTRACT(YEAR_MONTH FROM a.admission_date) AS adm_yrmonth
         FROM network_cons AS n 
       INNER
         JOIN rescue_admissions AS a  
           ON a.centre_id = n.centre_id 
        WHERE n.network_id = :network_id )
SELECT MONTHNAME(m.month) MONTH_NAME
     , COUNT(specific_admissions.adm_yrmonth)   COUNT_ADMISSIONS24
  FROM rescue_month_data AS m
LEFT 
  JOIN specific_admissions
    ON specific_admissions.adm_yrmonth = EXTRACT(YEAR_MONTH FROM m.month)
 WHERE YEAR(m.month)=2024
GROUP 
    BY MONTH(m.month)
ORDER 
    BY MONTH(m.month)
											");
					
                    $stmt->bindParam(':network_id', $network_id, PDO::PARAM_INT);

                    // initialise an array for the results
                    $months24 = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $count24 = $row["COUNT_ADMISSIONS24"];

                         print '"' . $count24 . '"	,';
                    }

                    ?>],

}, 


{
      label: "2025",
      lineTension: 0.3,
      backgroundColor: "rgba(255, 253, 110, 0.64)",
      borderColor: "rgb(238, 255, 0)",
      pointRadius: 3,
      pointBackgroundColor: "rgb(253, 255, 139)",
      pointBorderColor: "rgb(255, 251, 8)",
      pointHoverRadius: 3,
      pointHoverBackgroundColor: "rgb(255, 251, 3)",
      pointHoverBorderColor: "rgb(230, 255, 4)",
      pointHitRadius: 10,
      pointBorderWidth: 2,
      data: [<?php
                    //Get by month count
                    $stmt = $conn->prepare("WITH specific_admissions AS
     ( SELECT EXTRACT(YEAR_MONTH FROM a.admission_date) AS adm_yrmonth
         FROM network_cons AS n 
       INNER
         JOIN rescue_admissions AS a  
           ON a.centre_id = n.centre_id 
        WHERE n.network_id = :network_id )
SELECT MONTHNAME(m.month) MONTH_NAME
     , COUNT(specific_admissions.adm_yrmonth)   COUNT_ADMISSIONS25
  FROM rescue_month_data AS m
LEFT 
  JOIN specific_admissions
    ON specific_admissions.adm_yrmonth = EXTRACT(YEAR_MONTH FROM m.month)
 WHERE YEAR(m.month)=2025
GROUP 
    BY MONTH(m.month)
ORDER 
    BY MONTH(m.month)
											");
					
                    $stmt->bindParam(':network_id', $network_id, PDO::PARAM_INT);
					
                    // initialise an array for the results
                    $months25 = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $count25 = $row["COUNT_ADMISSIONS25"];

                         print '"' . $count25 . '"	,';
                    }

                    ?>],

    }],
  },
  options: {
    maintainAspectRatio: false,
    layout: {
      padding: {
        left: 10,
        right: 25,
        top: 5,
        bottom: 0,
      }
    },
    scales: {
      xAxes: [{
        time: {
          unit: 'date'
        },
        gridLines: {
          display: false,
          drawBorder: false
        },
        ticks: {
          maxTicksLimit: 12
        }
      }],
      yAxes: [{
        ticks: {
          maxTicksLimit: 10,
          padding: 10,
          // Include a dollar sign in the ticks
          callback: function(value, index, values) {
            return number_format(value);
          }
        },
        gridLines: {
          color: "rgb(234, 236, 244)",
          zeroLineColor: "rgb(234, 236, 244)",
          drawBorder: false,
          borderDash: [2],
          zeroLineBorderDash: [2]
        }
      }],
    },
    legend: {
      display: true,
    },
    tooltips: {
      backgroundColor: "rgb(255,255,255)",
      bodyFontColor: "#858796",
      titleMarginBottom: 10,
      titleFontColor: '#6e707e',
      titleFontSize: 14,
      borderColor: '#dddfeb',
      borderWidth: 1,
      xPadding: 15,
      yPadding: 15,
      displayColors: false,
      intersect: false,
      mode: 'index',
      caretPadding: 10,
      callbacks: {
        label: function(tooltipItem, chart) {
          var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
          return datasetLabel + ': ' + number_format(tooltipItem.yLabel);
        }
      }
    }
  }
});
</script>



<script>
// Admissions by presenting complaint
// Set new default font family and font color to mimic Bootstrap's default styling
Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#000';

function number_format(number, decimals, dec_point, thousands_sep) {
  // *     example: number_format(1234.56, 2, ',', ' ');
  // *     return: '1 234,56'
  number = (number + '').replace(',', '').replace(' ', '');
  var n = !isFinite(+number) ? 0 : +number,
    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
    s = '',
    toFixedFix = function(n, prec) {
      var k = Math.pow(10, prec);
      return '' + Math.round(n * k) / k;
    };
  // Fix for IE parseFloat(0.55).toFixed(0) = 0;
  s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
  if (s[0].length > 3) {
    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
  }
  if ((s[1] || '').length < prec) {
    s[1] = s[1] || '';
    s[1] += new Array(prec - s[1].length + 1).join('0');
  }
  return s.join(dec);
}

// Bar Chart Example
var ctx = document.getElementById("complaintChart");
var myBarChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: [<?php
                    //Get compalint labels
                    $stmt = $conn->prepare("SELECT presenting_complaint, COUNT(presenting_complaint) AS total_complaint
											FROM rescue_admissions
											INNER JOIN rescue_patients
											ON rescue_admissions.patient_id = rescue_patients.patient_id
                      LEFT JOIN network_cons ON 
                      network_cons.centre_id = rescue_admissions.centre_id
                      WHERE network_cons.network_id = :network_id
											GROUP BY presenting_complaint
											ORDER BY presenting_complaint
											");
					
                    
					$stmt->bindParam(':network_id', $network_id, PDO::PARAM_INT);

                    // initialise an array for the results
                    $months = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $complaint = $row["presenting_complaint"];

                         print '"' . $complaint . '"	,';
                    }

                    ?>],
    datasets: [{
      label: "Number of admissions",
      backgroundColor: ['#5AAb16', '#FBDb25', '#0f4c5c', '#F5a701', '#E34d36', '#A71c5d', '#2e546c', '#4da67c', '#77e44c', '#caff00' ],
      hoverBackgroundColor: "#2e59d9",
      borderColor: "#4e73df",
      data: [<?php
                    //Get by month count
                    $stmt = $conn->prepare("SELECT presenting_complaint, COUNT(presenting_complaint) AS total_complaint
											FROM rescue_admissions
											INNER JOIN rescue_patients
											ON rescue_admissions.patient_id = rescue_patients.patient_id
											WHERE rescue_admissions.centre_id = :centre_id
											GROUP BY presenting_complaint
											ORDER BY presenting_complaint
											");
					
                   $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

                    // initialise an array for the results
                    $months = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $complaint_count = $row["total_complaint"];

                             

                         print '"' . $complaint_count . '"	,';
                    }

                    ?>],
    }],
  },
  options: {
    maintainAspectRatio: false,
    layout: {
      padding: {
        left: 10,
        right: 25,
        top: 25,
        bottom: 0
      }
    },
    scales: {
      xAxes: [{
        time: {
          unit: 'cause'
        },
        gridLines: {
          display: false,
          drawBorder: false
        },
        ticks: {
          maxTicksLimit: 30
        },
        maxBarThickness: 55,
      }],
      yAxes: [{
        ticks: {
          min: 0,
          max: 50,
          maxTicksLimit: 5,
          padding: 5,
          // Include a dollar sign in the ticks
          callback: function(value, index, values) {
            return '' + number_format(value);
          }
        },
        gridLines: {
          color: "rgb(234, 236, 244)",
          zeroLineColor: "rgb(234, 236, 244)",
          drawBorder: false,
          borderDash: [2],
          zeroLineBorderDash: [2]
        }
      }],
    },
    legend: {
      display: false
    },
    tooltips: {
      titleMarginBottom: 10,
      titleFontColor: '#6e707e',
      titleFontSize: 14,
      backgroundColor: "rgb(255,255,255)",
      bodyFontColor: "#858796",
      borderColor: '#dddfeb',
      borderWidth: 1,
      xPadding: 15,
      yPadding: 15,
      displayColors: false,
      caretPadding: 10,
      callbacks: {
        label: function(tooltipItem, chart) {
          var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
          return datasetLabel + ': ' + number_format(tooltipItem.yLabel);
        }
      }
    },
  }
});

</script>


<!-- End of Main Content -->
<?php echo "</div>";