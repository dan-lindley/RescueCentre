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

/* Template Name: NGO Dashboard */
 get_header();

include_once "app_header.php";

//Row Count (total admissions)
$sql1 = "SELECT * 
FROM rescue_admissions
INNER JOIN rescue_patients
ON rescue_admissions.patient_id = rescue_patients.patient_id
WHERE rescue_patients.centre_id = :centre_id
ORDER by `admission_date` DESC";
$stmt = $conn->prepare($sql1);

// bind parameters
$stmt->bindParam(':centre_id', $centre_id);

// execute query
$stmt->execute();

// get row count
$total_admissions = $stmt->rowCount();

//Row Count (in care)
$sql1 = "SELECT * 
FROM rescue_admissions
INNER JOIN rescue_patients
ON rescue_admissions.patient_id = rescue_patients.patient_id
WHERE rescue_admissions.disposition = 'Held in captivity' AND rescue_patients.centre_id = :centre_id
ORDER by `admission_date` DESC";
$stmt = $conn->prepare($sql1);

// bind parameters
$stmt->bindParam(':centre_id', $centre_id);

// execute query
$stmt->execute();

// get row count
$incare = $stmt->rowCount();

//Row Count (released)
$sql1 = "SELECT * 
FROM rescue_admissions
INNER JOIN rescue_patients
ON rescue_admissions.patient_id = rescue_patients.patient_id
WHERE rescue_admissions.disposition = 'Released' AND rescue_patients.centre_id = :centre_id
ORDER by `admission_date` DESC";
$stmt = $conn->prepare($sql1);

// bind parameters
$stmt->bindParam(':centre_id', $centre_id);

// execute query
$stmt->execute();

// get row count
$released = $stmt->rowCount();


//Row Count (all dead)
$sql1 = "SELECT * 
FROM rescue_admissions
INNER JOIN rescue_patients
ON rescue_admissions.patient_id = rescue_patients.patient_id
WHERE rescue_patients.centre_id = :centre_id AND rescue_patients.status = 'Deceased'
ORDER by `admission_date` DESC";
$stmt = $conn->prepare($sql1);

// bind parameters
$stmt->bindParam(':centre_id', $centre_id);

// execute query
$stmt->execute();

// get row count
$dead = $stmt->rowCount();





//group by species 
//$sql2 = "SELECT animal_species, COUNT(animal_species) AS total_species
//FROM rescue_patients
//WHERE rescue_patients.centre_id = :centre_id
//GROUP BY animal_species


?>
<!-- Begin Page Content -->
<div class="container-fluid">


           


    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">NGO & Charity Dashboard</h1>
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
                                Total Admissions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_admissions; ?></div>
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
                                Animals in your care</div>
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

    <div class="row">

        <!-- Area Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <!-- Card Header - Dropdown -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Admissions Per Month</h6>

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
                    <h6 class="m-0 font-weight-bold text-primary">Admissions By Species</h6>
                </div>
                <!-- Card Body -->
                <div class="card-body">		<BR>		
				<div class="chart-area">
                        <canvas id="SpeciesChart"></canvas>
						</div><BR>
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
			<P>First of all, <strong><i>THANK YOU</i></strong> very much for giving the rescue centre database system a try. 
			<BR>At this moment, You are all early adopters and I hope you can appreciate that this is very much a work in progress at the moment
			<BR>That being said, the database is functional and there are some great features within it.
			<BR>Please get in touch with feedback, bugs, issues via the facebook group/messenger and I'll do my best to sort it.<BR>
			<BR>- Dan</p>
                
					
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
    labels: [<?php
                    //Get month labels
                    $stmt = $conn->prepare("SELECT MONTHNAME(month) MONTH, COUNT(admission_id) COUNT
											FROM rescue_month_data
											LEFT JOIN rescue_admissions
											ON EXTRACT(YEAR_MONTH FROM rescue_month_data.month) = EXTRACT(YEAR_MONTH FROM rescue_admissions.admission_date)		
											WHERE rescue_admissions.centre_id = :centre_id AND YEAR(month)=2023 OR rescue_admissions.admission_date IS NULL
											GROUP BY MONTH(month)
											ORDER BY MONTH(month)
											");
					
                    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
					

                    // initialise an array for the results
                    $months = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $month = $row["MONTH"];
						
                   
                             

                         print '"' . $month . '"	,';
                    }

                    ?>],
    datasets: [{
      label: "Admissions 2023",
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
                    $stmt = $conn->prepare("SELECT MONTHNAME(month) MONTH, COUNT(admission_id) COUNT
											FROM rescue_month_data
											LEFT JOIN rescue_admissions
											ON EXTRACT(YEAR_MONTH FROM rescue_month_data.month) = EXTRACT(YEAR_MONTH FROM rescue_admissions.admission_date)		
											WHERE rescue_admissions.centre_id = :centre_id AND YEAR(month)=2023 OR rescue_admissions.admission_date IS NULL
											GROUP BY MONTH(month)
											ORDER BY MONTH(month)
											");
					
                    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
					

                    // initialise an array for the results
                    $months = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $count = $row["COUNT"];
						
                   
                             

                         print '"' . $count . '"	,';
                    }

                    ?>],
    },
	
{
      label: "Admissions 2024",
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
                    $stmt = $conn->prepare("SELECT MONTHNAME(month) MONTH, COUNT(admission_id) COUNT
											FROM rescue_month_data
											LEFT JOIN rescue_admissions
											ON EXTRACT(YEAR_MONTH FROM rescue_month_data.month) = EXTRACT(YEAR_MONTH FROM rescue_admissions.admission_date)		
											WHERE rescue_admissions.centre_id = :centre_id AND YEAR(month)=2024 OR rescue_admissions.admission_date IS NULL
											GROUP BY MONTH(month)
											ORDER BY MONTH(month)
											");
					
                    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
					

                    // initialise an array for the results
                    $months = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $count = $row["COUNT"];
						
                   
                             

                         print '"' . $count . '"	,';
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
          unit: 'date'
        },
        gridLines: {
          display: false,
          drawBorder: false
        },
        ticks: {
          maxTicksLimit: 7
        }
      }],
      yAxes: [{
        ticks: {
          maxTicksLimit: 5,
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
      display: false
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

<!-- End of Main Content -->
<?php get_footer();

echo "</div>";