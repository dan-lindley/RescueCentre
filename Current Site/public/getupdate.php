
<?php
include_once "connect_to_mysql.php";
get_header();

//ini_set('display_errors', '1');
//ini_set('display_startup_errors', '1');
//error_reporting(E_ALL);
?>

<?php
//Purpose: to use posted GET values for CRN and passphrase to display the patients details.
/* Template Name: Get Update */
//Retrieve the GET values from the URL, and sanitise it for security purposes

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_GET['patient_id']) && !empty($_GET['patient_id']) AND isset($_GET['passphrase']) && !empty($_GET['passphrase'])) {
    $patient_id = test_input($_GET["patient_id"]);
    $passphrase = test_input($_GET["passphrase"]);

} else {
    echo "Update check error - The Patient ID was not found.";
    echo $patient_id;
    exit();
}

//Get the information from the database
$sql = 'SELECT name, animal_type, animal_order, animal_species, sex, disposition, rescue_name, passphrase FROM rescue_patients
    LEFT JOIN rescue_admissions
    ON rescue_admissions.patient_id = rescue_patients.patient_id 
    LEFT JOIN rescue_centres
	ON rescue_admissions.centre_id = rescue_centres.rescue_id
    WHERE rescue_patients.patient_id=:patient_id AND rescue_admissions.passphrase=:passphrase LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$statement->bindParam(':passphrase', $passphrase, PDO::PARAM_STR);
$statement->execute();

$result = $statement->fetch(PDO::FETCH_ASSOC);

/*---------------------------------------------------------------------------------*/
// need to check if rows = 0 
if (!$result) {
    header("Location: https://www.rescuecentre.org.uk?error=not_found");
    exit();
}
if ($result) {
    $p_name = $result["name"];
    $pt_type = $result["animal_type"];
    $pt_order = $result["animal_order"];
    $p_species = $result["animal_species"];
    $p_sex = $result["sex"];
    $p_disp = $result["disposition"];
    $p_centre = $result["rescue_name"];
    $dbpass = $result["passphrase]"];
    $formatted_date = new DateTime($date_added);
    $formatted_date = $formatted_date->format('d-m-Y H:i');



} else {
    echo "The patient was not found or there was an error in the passphrase";
    exit();
}   
 
//Get the weight and measurement units which were used upon admission for this patient
$sql = 'SELECT * FROM rescue_weights WHERE patient_id=:patient_id ORDER BY date ASC LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $first_weight_unit = $result["weight_unit"];

    //echo $first_weight_unit;
} else {
    echo "Error - Admission weight not found";
    exit();
}
/*---------------------------------------------------------------------------------*/ 
//Get the weight and measurement units which were used upon admission for this patient
$sql = 'SELECT * FROM rescue_measurements WHERE patient_id=:patient_id ORDER BY date ASC LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $first_measurement_unit = $result["measurement_unit"];

    //echo $first_measurement_unit;
} else {
    echo "Error - Admission measurement not found";
    exit();
}
/*---------------------------------------------------------------------------------*/
?>

<div class="container">
<?php get_template_part('templates/title'); ?>
<!-- HEADER SECTION -->
<div class="card shadow mb-4">
        <div class="card-header py-3">
	        <h6 class="m-0 font-weight-bold text">General Information</h6>
        </div>
    <div class="card-body">

        <div class="container-md">
            <div class="row">
                <div class="col-md-6"> 
                    <h4>Name: <?php echo $p_name; ?></h4>
                    <h4>Species: <?php echo $p_species; ?></h4>
                    <h4>Sex: <?php echo $p_sex; ?></h4>
                </div>
                <div class="col-md-6"> 
                    <h6>CRN: <?php echo $patient_id ?></h6>
                    <h6>Current Disposition: <?php echo $p_disp ?></h6>
                    <h6>Centre: <?php echo $p_centre ?></h6>  
                </div>
            </div>
        </div>
    </div>
</div>

<!--- CARE NOTES SECTION ---->  
<div class="card shadow mb-4"> 
    <div class="card-header py-3">
	    <h6 class="m-0 font-weight-bold text">Care Notes</h6>
    </div>
    <div class="card-body">
        <?php
            //Get existing notes
                $stmt = $conn->prepare("SELECT * FROM rescue_notes_patients 
                          LEFT JOIN rescue_images ON
                          rescue_notes_patients.image_id = rescue_images.image_id
                          WHERE rescue_notes_patients.patient_id=:patient_id 
                          AND deleted = 0 
                          AND (rescue_notes_patients.public = 'Yes')
                          ORDER BY date DESC");
                $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                // initialise an array for the results
                $p_carenotes = array();
                $stmt->execute();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                $note_message = $row["message"];
                $note_date = $row["date"];
                $imageurl = $row["image_url"];
                // change image url to defaul no image pic if empty
 			if (empty($imageurl)) {
                $imageurl = "img/no_image.png";
            }
   
                $fnew_date = new DateTime($note_date);
                $fnew_date = $fnew_date->format('jS \o\f F Y \a\t H:i'); ?>
                            

        <div class="row lead_form_row">	
            <div class="col-md-3 my-auto">
            <img src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/<?php echo$imageurl; ?>" width="200px">
                <br><h6>Date: <?php echo $fnew_date; ?></H6>
            </div>
      
            <div class="col-md-9 my-auto">
                <H5><?php echo $note_message; ?><br /></h5>     
            </div>
        </div><BR><?php } ?>	
    </div>
</div>
<!-- END OF CARE NOTES SECTION -->	  



<!-- NEW CARD - weights and measurements -->
<div class="card shadow mb-4">
        <div class="card-header py-3">
	        <h6 class="m-0 font-weight-bold text">Weights and Measurements</h6>
        </div>
    <div class="card-body">
        <P>Weights and measurements are only a guide. Wildlife rehabilitators may use these to determine if an animal is suitable for 
            release. Weight and growth gains can indicate an animal is feeding and developing well, however sometimes there may be dips
            in weight which can be a normal part of development in hand reared animals. 
        <div class="row lead_form_row">
        <!-- col 1 weights -->
            <div class="col-md-6 my-auto"> 
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text"><?php echo $p_name; ?>'s weight</h6>
	            </div>
                <div class="card-body">
	                <P><?php
                    //gets the targetsizes from the table to display 
                    $stmt = $conn->prepare("SELECT * FROM rescue_patients
                            RIGHT JOIN rescue_animal_species ON rescue_animal_species.species_name = rescue_patients.animal_species
							WHERE patient_id = :patient_id");
                    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                    // initialise an array for the results
                    $target_sizes = array();
                    $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $animal_species = $row["animal_species"];
				        $species_weight_from = $row["species_weight_from"];
				        $species_weight_to = $row["species_weight_to"];
				        $species_weight_unit = $row["species_weight_unit"];
				        $scientific_name = $row["scientific_name"];
				        $reference = $row["reference"];
                                        
                        print '
                        A typical adult ' . $animal_species . ' <I> (' . $scientific_name . ')</i> should weigh between <strong> ' . $species_weight_from . '' . $species_weight_unit . '  </strong> and
				        <strong> ' . $species_weight_to . '' . $species_weight_unit . ' </strong> <BR>Reference: <i> ' . $reference . ' </i>
                                    ';
                                    }
                                    ?>		

                    <div class="chart-area" style="position: relative; height:40vh;">
                        <canvas id="myAreaChart"></canvas>
                    </div>
                </div>						
            </div>
            <!-- col 2 measure -->
            <div class="col-md-6 my-auto">  
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text"><?php echo $p_name; ?>'s measurement</h6>
	            </div>

                <div class="card-body">
	                <P><?php
                    //gets the targetsizes from the table to display 
                     $stmt = $conn->prepare("SELECT * FROM rescue_patients
                            RIGHT JOIN rescue_animal_species ON rescue_animal_species.species_name = rescue_patients.animal_species
							WHERE patient_id = :patient_id");
                     $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                        // initialise an array for the results
                    $target_sizes = array();
                    $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $animal_species = $row["animal_species"];
	                    $species_measurement_from = $row["species_measurement_from"];
	                    $species_measurement_to = $row["species_measurement_to"];
	                    $species_measurement_unit = $row["species_measurement_unit"];
	                    $scientific_name = $row["scientific_name"];
	                    $reference = $row["reference"];
	                    $species_measurement_standard = $row["species_measurement_standard"];
    
                        print '
                        A typical adult ' . $animal_species . ' <I> (' . $scientific_name . ')</i> ' . $species_measurement_standard . ' should measure between <strong> ' . $species_measurement_from . '' . $species_measurement_unit . '  </strong> and
			            <strong> ' . $species_measurement_to . '' . $species_measurement_unit . ' </strong> <BR>Reference: <i> ' . $reference . ' </i>
                         ';
                          }
                          ?>

                    <div class="chart-area">
                        <canvas id="measurementChart" style="position: relative; height:40vh;"></canvas>
                    </div>	
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END OF CARD - weights and measurements -->


<div class="card shadow mb-4">
        <div class="card-header py-3">
	        <h6 class="m-0 font-weight-bold text">Donate to the Rescue Centre Platform</h6>
        </div>
    <div class="card-body">
          <div class="row lead_form_row">	
            <div class="col-md-9 my-auto">
                
                <h4>Rescue Centre is a completely FREE service for wildlife rescue centres and rehabilitators.</h4> 
                <BR><BR> While it is free, it does incur some costs to run and maintain. If you would like to 
                support this initiative please consider a donation of any amount.

            <BR><BR>Your donation will be processed securely by Happy Mole and Stripe.
            <BR><BR>Your donation will be used for the upkeep of the Rescue Centre Platform and not directly used by the rescue 
            caring for your animal. If you wish to donate to the rescue directly, please visit their website, social media accounts
            or contact the rescuer to find out how you can donate to them directly. 
            
            </div>
        
 <div class="col-md-3 my-auto">
<script async
  src="https://js.stripe.com/v3/buy-button.js">
</script>

<stripe-buy-button
  buy-button-id="buy_btn_1S5QpzLblJeLNg2w7mTzjG62"
  publishable-key="pk_live_51Qx47dLblJeLNg2wI2U6VJOld7gnhGnmGpYMv6JmTOYV3RQYU32sS6iy1TuDjwmC4cRx7x8w38o8zFauDB88w3Jx00MrKwXseh"
>
</stripe-buy-button>

                        </div>
                        </div>

                        </div>

   </div>
   </div>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/chart.js/Chart.min.js"></script>
<?php get_footer();

?>



<script>
    // Set new default font family and font color to mimic Bootstrap's default styling
    Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    Chart.defaults.global.defaultFontColor = '#858796';

    function number_format(number, decimals, dec_point, thousands_sep) {
        // *     example: number_format(1234.56, 2, ',', ' ');
        // *     return: '1 234,56'
        number = (number + '').replace(',', '').replace(' ', '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 2 : Math.abs(decimals),
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

    // Area Chart Example - WEIGHT
    var ctx = document.getElementById("myAreaChart");
    var myLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [<?php
                        //Loop through the measurement months from the database
                        $stmt = $conn->prepare("SELECT * FROM rescue_weights WHERE patient_id = :patient_id ORDER by date ASC");
                        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                        // initialise an array for the results
                        $applicants = array();
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                            $graph_date = $row["date"];


                            $day = date('j', strtotime($graph_date));

                            $suffix = '';

                            if ($day == 1 || $day == 21 || $day == 31) {
                                $suffix = 'st';
                            } elseif ($day == 2 || $day == 22) {
                                $suffix = 'nd';
                            } elseif ($day == 3 || $day == 23) {
                                $suffix = 'rd';
                            } else {
                                $suffix = 'th';
                            }

                            $dayWithSuffix = $day . $suffix;


                            $monthAbbreviation = date('M', strtotime($graph_date));
                            $yearDigits = date('y', strtotime($graph_date));

                            print '"' . $dayWithSuffix . ' ' . $monthAbbreviation . ' ' . $yearDigits . '",';
                        }

                        ?>],
            datasets: [{
                label: "Weight",
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
                        //Loop through this patient's weights from the database
                        $stmt = $conn->prepare("SELECT * FROM rescue_weights WHERE patient_id = :patient_id ORDER by date ASC");
                        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                        // initialise an array for the results
                        $applicants = array();
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                            $graph_weight = $row["weight"];

                            print '' . $graph_weight . ',';
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
                        maxTicksLimit: 10
                    }
                }],
                yAxes: [{
                    ticks: {
                        maxTicksLimit: 5,
                        padding: 10,
                        // Include a dollar sign in the ticks
                        callback: function(value, index, values) {
                            return number_format(value) + '<?php echo $first_weight_unit; ?>';
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
                        return datasetLabel + ': ' + number_format(tooltipItem.yLabel) + '<?php echo $first_weight_unit; ?>';
                    }
                }
            }
        }
    });
</script>
<script>
    // Set new default font family and font color to mimic Bootstrap's default styling
    Chart.defaults.global.defaultFontFamily = 'Nunito', '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    Chart.defaults.global.defaultFontColor = '#858796';

    function number_format(number, decimals, dec_point, thousands_sep) {
        // *     example: number_format(1234.56, 2, ',', ' ');
        // *     return: '1 234,56'
        number = (number + '').replace(',', '').replace(' ', '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 2 : Math.abs(decimals),
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

    // Area Chart Example - MEASUREMENTS 
    var ctx = document.getElementById("measurementChart");
    var myLineChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [<?php
                        //Loop through the measurement months from the database
                        $stmt = $conn->prepare("SELECT * FROM rescue_measurements WHERE patient_id = :patient_id ORDER by date ASC");
                        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                        // initialise an array for the results
                        $applicants = array();
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                            $graph_date = $row["date"];


                            $day = date('j', strtotime($graph_date));

                            $suffix = '';

                            if ($day == 1 || $day == 21 || $day == 31) {
                                $suffix = 'st';
                            } elseif ($day == 2 || $day == 22) {
                                $suffix = 'nd';
                            } elseif ($day == 3 || $day == 23) {
                                $suffix = 'rd';
                            } else {
                                $suffix = 'th';
                            }

                            $dayWithSuffix = $day . $suffix;


                            $monthAbbreviation = date('M', strtotime($graph_date));
                            $yearDigits = date('y', strtotime($graph_date));

                            print '"' . $dayWithSuffix . ' ' . $monthAbbreviation . ' ' . $yearDigits . '",';
                        }

                        ?>],
            datasets: [{
                label: "Measurement",
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
                        //Loop through this patient's measurements from the database
                        $stmt = $conn->prepare("SELECT * FROM rescue_measurements WHERE patient_id = :patient_id ORDER by date ASC");
                        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                        // initialise an array for the results
                        $applicants = array();
                        $stmt->execute();
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                            $graph_measurement = $row["measurement"];

                            print '' . $graph_measurement . ',';
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
                            return number_format(value) + '<?php echo $first_measurement_unit; ?>';
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
                        return datasetLabel + ': ' + number_format(tooltipItem.yLabel) + '<?php echo $first_measurement_unit; ?>';
                    }
                }
            }
        }
    });
</script>
