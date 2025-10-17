<?php

//ini_set('display_errors', '1');
//ini_set('display_startup_errors', '1');
//error_reporting(E_ALL);


include_once "authentication.php";
include_once "connect_to_mysql.php";


echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* Template Name: Dashboard */
 get_header();

include_once "app_header.php";

//Row Count (all admissions - all time)
$stmt = $conn->prepare("SELECT COUNT(*) as total,
    SUM(CASE WHEN disposition = 'Released' THEN 1 ELSE 0 END) AS Released,
    SUM(CASE WHEN disposition = 'Held in Captivity' THEN 1 ELSE 0 END) AS Captive,
	  SUM(CASE WHEN disposition = 'Transferred Out' THEN 1 ELSE 0 END) AS Transferred,
	  SUM(CASE WHEN disposition = 'Died - After 48 hours' THEN 1 ELSE 0 END) AS Diedafter48,
	  SUM(CASE WHEN disposition = 'Died - Euthanised' THEN 1 ELSE 0 END) AS DiedEuth,
	  SUM(CASE WHEN disposition = 'Died - On Admission' THEN 1 ELSE 0 END) AS Diedadmit,
	  SUM(CASE WHEN disposition = 'Died - Within 48 hours' THEN 1 ELSE 0 END) AS Diedin48
 FROM rescue_admissions
 INNER JOIN rescue_patients
 ON rescue_admissions.patient_id = rescue_patients.patient_id
 WHERE rescue_patients.centre_id = :centre_id");
      
$stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
// initialise an array for the result
$dispdata = array();
$stmt->execute();
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $disptotal = $row["total"];
  $dispcaptive = $row["Captive"];
  $dispreleased = $row["Released"];
  $disptrans = $row ["Transferred"];
  $dispeuth = $row["DiedEuth"];
  $dispin48 = $row["Diedin48"];
  $dispafter48 = $row["Diedafter48"];
  $dispdoa = $row["Diedadmit"];


  $dispdiedtotal = ($dispeuth + $dispin48 + $dispafter48 + $dispdoa); 
//Calculate the clinical efficiency
//start by checking the total is zero and making it 0 if so

$deductions = ($disptotal - ($dispin48 + $dispdoa + $dispeuth + $disptrans + $dispcaptive));
    if ($deductions == 0) {
    $deductions = 1;
    }

  if ($disptotal == 0 ) {
  $clinefficiency = 0;
 	} elseif ($disptotal > 0) {
	$clinefficiency = ($dispreleased / $deductions) * 100;
  }

}; 

//Row Count (all admissions - YTD)
$stmt = $conn->prepare("SELECT COUNT(*) as total,
    SUM(CASE WHEN disposition = 'Released' THEN 1 ELSE 0 END) AS Released,
    SUM(CASE WHEN disposition = 'Held in Captivity' THEN 1 ELSE 0 END) AS Captive,
	  SUM(CASE WHEN disposition = 'Transferred Out' THEN 1 ELSE 0 END) AS Transferred,
	  SUM(CASE WHEN disposition = 'Died - After 48 hours' THEN 1 ELSE 0 END) AS Diedafter48,
	  SUM(CASE WHEN disposition = 'Died - Euthanised' THEN 1 ELSE 0 END) AS DiedEuth,
	  SUM(CASE WHEN disposition = 'Died - On Admission' THEN 1 ELSE 0 END) AS Diedadmit,
	  SUM(CASE WHEN disposition = 'Died - Within 48 hours' THEN 1 ELSE 0 END) AS Diedin48
 FROM rescue_admissions
 INNER JOIN rescue_patients
 ON rescue_admissions.patient_id = rescue_patients.patient_id
 WHERE rescue_patients.centre_id = :centre_id AND Year(admission_date) = Year(curdate())");
      
$stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
// initialise an array for the result
$ytddispdata = array();
$stmt->execute();
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $ytddisptotal = $row["total"];
  $ytddispcaptive = $row["Captive"];
  $ytddispreleased = $row["Released"];
  $ytddisptrans = $row ["Transferred"];
  $ytddispeuth = $row["DiedEuth"];
  $ytddispin48 = $row["Diedin48"];
  $ytddispafter48 = $row["Diedafter48"];
  $ytddispdoa = $row["Diedadmit"];

  $ytdyear = date('Y'); 

  $ytddispdiedtotal = ($ytddispeuth + $ytddispin48 + $ytddispafter48 + $ytddispdoa);

//Calculate the clinical efficiency
//start by checking the total is zero and making it 0 if so
$ytddeductions = ($ytddisptotal - ($ytddispin48 + $ytddispdoa + $ytddispeuth + $ytddisptrans +$ytddispcaptive));
    if ($ytddeductions == 0) {
    $ytddeductions = 1;
    }

  if ($ytddisptotal == 0 ) {
  $ytdclinefficiency = '0';
 	} elseif ($ytddisptotal > 0) {
	$ytdclinefficiency = ($ytddispreleased / $ytddeductions) * 100;
  }
 

};
?>
<!-- Begin Page Content -->
<div class="container-fluid">

<!-- ALERTING SYSTEM -->			 					 			 
			 <?php
                $stmt = $conn->prepare("SELECT * FROM rescue_alerts WHERE (centre_id = 0 OR {$centre_id}) AND is_deleted=0 AND patient_id=0 AND is_active = 'yes'");
                                    // initialise an array for the results
                                    $alerts = array();
                                    $stmt->execute();
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            // initialise an array for the results
                            // $result = $conn->query($sql);
							//foreach($result as $row) {
                                $alert_message = $row["alert_message"];
                                $alert_type = $row["alert_type"];
								$is_closed = $row["is_closed"];
								$date = $row["date"];
							    $url = $row["url"];

									//This displays the alert IF is_closed is empty
									if (empty($is_closed)){	
										
                                print ' <div class="alert ' . $alert_type . ' alert-dismissible fade show" role="alert">
 										' . $date . ' - ' . $alert_message . '.    <a href ="' . $url . '" target="_blank">[Read more]</a><button type="button" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="true">&times;</span>
										</div>'; }
							 }

                            ?>				
	

	

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $lang['LM_DASHBOARD']; ?></h1>
        <a href="https://rescuecentre.org.uk/query/" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-download fa-sm text-white-50"></i> Generate Report</a>
    </div>

    <!-- Content Row -->
    <div class="row">

        <!-- Admissions -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                <?php echo $lang['DASH_TOTAL_ADMISSIONS']; ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $disptotal; ?></div>
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
                            <?php echo $lang['DASH_ANIMALS_IN_CARE']; ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $dispcaptive; ?></div>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1"> <?php echo $lang['DASH_ANIMALS_RELEASED']; ?>
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $dispreleased; ?></div>
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
                            <?php echo $lang['DASH_ANIMALS_THAT_DIED']; ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $dispdiedtotal; ?></div>
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

        <!-- Admissions -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                <?php echo $ytdyear; ?> <?php echo $lang['DASH_TOTAL_ADMISSIONS']; ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $ytddisptotal; ?></div>  
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ambulance fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- clinical refficiency -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            <?php echo $ytdyear; ?> <?php echo $lang['DASH_CLIN_EFFICIENCY']; ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format((float)$ytdclinefficiency, 2); ?>%</div>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1"><?php echo $ytdyear; ?> <?php echo $lang['DASH_ANIMALS_RELEASED']; ?>
                            </div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo $ytddispreleased; ?></div>
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
                            <?php echo $ytdyear; ?> <?php echo $lang['DASH_ANIMALS_THAT_DIED']; ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $ytddispdiedtotal; ?></div>
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
                    <h6 class="m-0 font-weight-bold text-primary"> <?php echo $lang['DASH_ADMISSIONS_MONTH']; ?></h6>

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
                    <h6 class="m-0 font-weight-bold text-primary"><?php echo $lang['DASH_ADMISSIONS_SPECIES']; ?></h6>
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
                    <h6 class="m-0 font-weight-bold text-primary"><?php echo $lang['DASH_ADMISSIONS_CAUSE']; ?></h6>

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
                    <h6 class="m-0 font-weight-bold text-primary"><?php echo $lang['DASH_ADMISSIONS_AGE']; ?></h6>
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
	



	

<!-- Little note from me -->
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">A little note from Dan</h6>
    
 
            </div>
<div class="card-body">
	<div class="alert alert-info" role="alert">
    Thank you all for joiing me on this journey as we build a great system for your rescue
    <br><br><strong><u>There have been some changes</u></strong>
		<p>Patient archive, My patients and My residents are located under "Patients"
    <br>Rescues that keep long term residents can mark residents with the disposition "Long-term Captive" which will populate them into the My residents page
    <br>Adding a new patient/admission has changed:
            <li>On <a href="https://rescuecentre.org.uk/patients/">"My Patients"</a> use the short form</li>
            <li>Once added, click "Admit" next to the patient name or "DOA" if only adding a deceased record </li>
            <li>As always, patient records and admissions are still treated seperately</li>
        </li>
  
			<BR>Please get in touch with feedback, bugs, issues via the facebook group/messenger and I'll do my best to sort it.
      <br><b><i>Without your help in identifying issues, they could go un-noticed and I would prefer to work to rectify them</i></b>
			
		<br><BR>- Dan
    <BR><a href="https://www.facebook.com/groups/6347770428619595">Join our Facebook Group for users</a></div>
                
					
                </div>
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
											WHERE rescue_patients.centre_id = :centre_id
											GROUP BY age_on_admission
											ORDER BY age_on_admission");
					
                   $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
					

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
											WHERE rescue_patients.centre_id = :centre_id
											GROUP BY age_on_admission
											ORDER BY age_on_admission");
					
                    
					$stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

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
                    $stmt = $conn->prepare("SELECT
  											 MONTHNAME(m.month)      MONTH_NAME,
  											 COUNT(a.admission_id)   COUNT_ADMISSIONS23
													FROM
   													rescue_month_data   AS m
														LEFT JOIN
  														rescue_admissions   AS a
      											ON EXTRACT(YEAR_MONTH FROM m.month) = EXTRACT(YEAR_MONTH FROM a.admission_date)     
      											AND a.centre_id = :centre_id
												WHERE
   												YEAR(m.month)=2023
												GROUP BY
   													MONTH(m.month)
												ORDER BY
  													 MONTH(m.month)
											");
					
                    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
					

                    // initialise an array for the results
                    $months = array();
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
      backgroundColor: "rgba(78, 115, 223, 0.05)",
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
                    $stmt = $conn->prepare("SELECT
  											 MONTHNAME(m.month)      MONTH_NAME,
  											 COUNT(a.admission_id)   COUNT_ADMISSIONS24
													FROM
   													rescue_month_data   AS m
														LEFT JOIN
  														rescue_admissions   AS a
      											ON EXTRACT(YEAR_MONTH FROM m.month) = EXTRACT(YEAR_MONTH FROM a.admission_date)     
      											AND a.centre_id = :centre_id
												WHERE
   												YEAR(m.month)=2024
												GROUP BY
   													MONTH(m.month)
												ORDER BY
  													 MONTH(m.month)
											");
					
                    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
					

                    // initialise an array for the results
                    $months = array();
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
      backgroundColor: "rgba(78, 115, 223, 0.05)",
      borderColor: "rgba(255, 179, 20, 0.8)",
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
                    $stmt = $conn->prepare("SELECT
  											 MONTHNAME(m.month)      MONTH_NAME,
  											 COUNT(a.admission_id)   COUNT_ADMISSIONS25
													FROM
   													rescue_month_data   AS m
														LEFT JOIN
  														rescue_admissions   AS a
      											ON EXTRACT(YEAR_MONTH FROM m.month) = EXTRACT(YEAR_MONTH FROM a.admission_date)     
      											AND a.centre_id = :centre_id
												WHERE
   												YEAR(m.month)=2025
												GROUP BY
   													MONTH(m.month)
												ORDER BY
  													 MONTH(m.month)
											");
					
                    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
					

                    // initialise an array for the results
                    $months = array();
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
											WHERE rescue_admissions.centre_id = :centre_id
											GROUP BY presenting_complaint
											ORDER BY presenting_complaint
											");
					
                    
					$stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

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
      backgroundColor: ['#5AAb16', '#FBDb25', '#0f4c5c', '#F5a701', '#E34d36', '#A71c5d', '#2e546c', '#4da67c', '#77e44c', '#caff00'  ],
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
          maxTicksLimit: 20
        },
        maxBarThickness: 55,
      }],
      yAxes: [{
        ticks: {
          min: 0,
          max: 20,
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
<?php get_footer();

echo "</div>";