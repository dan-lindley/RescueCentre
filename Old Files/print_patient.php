<?php
include_once "authentication.php";
include_once "connect_to_mysql.php";

//Get logged in user's name 
$user_info = get_userdata(get_current_user_id());
$wp_first_name = $user_info->first_name;
$wp_last_name = $user_info->last_name;
$wp_fullname = "" . $wp_first_name . " " . $wp_last_name . "";

echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* Template Name: Print Individual Patient */
get_header();


//Retrieve the GET value from the URL, and sanitise it for security purposes
function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_GET["patient_id"])) {
    $patient_id = test_input($_GET["patient_id"]);
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
$sql = 'SELECT * FROM rescue_patients WHERE patient_id=:patient_id LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $patient_name = $result["name"];
    $patient_ringed = $result["ringed"];
    $patient_ring_number = $result["ring_number"];
    $patient_microchipped = $result["microchipped"];
    $patient_microchip_number = $result["microchip_number"];
    $patient_animal_type = $result["animal_type"];
    $patient_animal_order = $result["animal_order"];
    $patient_animal_species = $result["animal_species"];
    $patient_sex = $result["sex"];
    $patient_status = $result["status"];
    $date_added = $result["date_added"];
    $centre_id = $result["centre_id"];

    $formatted_date = new DateTime($date_added);
    $formatted_date = $formatted_date->format('d-m-Y H:i');
} else {
    echo "Error 2";
    exit();
}

//Get the admission information from the database
$sql = 'SELECT * FROM rescue_admissions AS adm
        LEFT JOIN rescue_centres as cen
        ON adm.centre_id = cen.rescue_id
            WHERE patient_id=:patient_id LIMIT 1';
$statement = $conn->prepare($sql);
$statement->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);
$statement->execute();
$result = $statement->fetch(PDO::FETCH_ASSOC);
/*---------------------------------------------------------------------------------*/
if ($result) {
    $adm_age = $result["age_on_admission"];
    $adm_pc = $result["presenting_complaint"];
    $adm_starv = $result["starved"];
    $adm_dehyd = $result["dehydrated"];
    $adm_cent = $result["rescue_name"];
    $adm_loc = $result["collection_location"];
    $adm_hpc = $result["hpc"];
    $adm_oe = $result["on_examination"];

    $form_adm_date = new DateTime($adm_date);
    $form_adm_date = $form_adm_date->format('d-m-Y H:i');
} else {
    echo "Error 2";
    exit();
}



?>

<!-- Custom styles for this template-->
<link href="https://rescuecentre.org.uk/wp-content/themes/brikk-child/css/sb-admin-2.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<style>
    footer{
    position: absolute;
    left: 0;
    bottom: 0;
    height: 50px;
    width: 100%;
    overflow:hidden;
}
</style>

<div class="container-fluid">
<!-- HEADER SECTION-->
    <div class="row">
        <div class="col-4">
            <img src="https://rescuecentre.org.uk/wp-content/uploads/2023/04/black-logo-v3.png" width="50%"></img>     
        </div>
        <div class="col-8">
           <H6 class="font-weight-bold text-primary">Rescue Centre:</h6>
            <H4 class="font-weight-bold text-primary"><?php print ' '. $adm_cent .' '?></h4>
        </div>
    </div>

<div class="row">
    <div class="col-12" align="center">
        <centre><BR><H2 class="font-weight-bold text-primary">Patient Care Summary Record for <?php print ' ' .$patient_name . ' '?></h2>
        <strong><p class="text-primary"><?php print ' ' . $patient_sex . ' ' . $patient_animal_type . ' - ' . $patient_animal_species . ' (' . $patient_animal_order . ')'  ?></p></strong></centre>
    </div>
</div>


<BR>
<!-- Header card -->
<div class="col">
    <div class="card mb-4">
        <div class="row">
            <div class="col-6">
                <H6 class="font-weight-bold text-primary"><U>Patient Details</u></h6>
                <?php print '
                <strong>CRN:</strong> ' . $patient_id . '
                <BR><b>Status:</B> ' . $patient_status . ' 
                <BR><b>Ringed:</b> ' . $patient_ringed . ' (<b>Number:</b> ' . $patient_ring_number . ')
                <BR><b>Microchipped: </b>' . $patient_microchipped . ' (<b>Number:</b> ' . $patient_microchip_number . ')  </strong></td>
                    ';
                        ?>
            </div>
            <div class="col-6">
                <H6 class="font-weight-bold text-primary"><u>Admission Information</u></h6>
                <?php print '
                <strong>Date of admission:</strong> ' . $form_adm_date . '
                <BR><strong>Age on admission:</strong> ' . $adm_age . '
                <BR><strong>Location Found:</strong> ' . $adm_loc . '
                <BR><strong>Starved:</strong> ' . $adm_starv . ' <strong>Dehydrated:</strong> ' . $adm_dehyd . '
                '; 
                    ?>
            </div>
        </div>
    </div>
</div>

<!-- History card -->
<div class="col">
    <div class="card mb-4">
        <div class="row">
            <div class="col-6">
                <p class="text-primary">
                <?php print '
                <strong>Presenting Complaint:</strong> ' . $adm_pc . '
                <BR><b>History of Presenting Complaint:</B> 
                <BR>' . $adm_hpc . ' 

                    ';
                        ?></p>
            </div>
            <div class="col-6">
                <p class="text-primary">
                <?php print '
                <strong>On examination:</strong> 
                <BR>' . $adm_oe. '
                '; 
                    ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Prescriptions and drugs given card -->
<div class="col">
    <div class="card mb-4">
        <div class="card-header"><p class="text-primary">
                <strong><u>Medication and Treatments</p></u></strong>
        </div>
<div class="row">
    <div class="col-6 px-5 py-3">
            <p class="text-primary">
            <strong><u>Prescriptions:</u></strong> </p>
            <p class="text-primary">
            <table class="table table-bordered table-sm"  width="100%" cellspacing="0">
                <thead class="thead-dark">
                    <tr>
                        <th>Date</th>
                        <th>Medication</th>
                        <th>Dose</th>
                        <th>Frequency</th>
                    </tr>
                <?php
                    //gets the prescriptions from the table to display 
                    $stmt = $conn->prepare("SELECT * FROM rescue_prescriptions WHERE patient_id = :patient_id ORDER by date DESC LIMIT 10");
                    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                    // initialise an array for the results
                    $prescribed = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                    $presdate = $row["date"];
                    $medication = $row["medication"];
                    $dose = $row["dose"];
                    $dose_type = $row["dose_type"];
                    $duration = $row["duration"];
		            $frequency = $row["frequency"];
										
		            $pres_formatted_date = new DateTime($presdate);
		            $pres_formatted_date = $pres_formatted_date->format('D j M Y '); ?>
		            
                    <tr>
                        <td><?php echo $pres_formatted_date; ?></td>
                        <td> <B> <?php echo $medication; ?></td>
                        <td><?php echo $dose; ?> <?php echo $dose_type; ?></td>
                        <td><?php echo $frequency; ?> for <?php echo $duration; ?> days </td>
                    </tr>
                    <?php } ?>
                    </table>
                </p>
                <hr>
                <p class="text-primary">
                    <strong><u>Medication Administered:</u></strong> </p>
                     <p class="text-primary"><?php
                    //gets the medications from the table to display 
                    $stmt = $conn->prepare("SELECT * FROM rescue_medications_given WHERE patient_id = :patient_id ORDER by date DESC LIMIT 10");
                    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                    // initialise an array for the results
                    $medsgiven = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                    $date = $row["date"];
                    $medication_given = $row["medication_given"];
                    $dose = $row["dose"];
                    $dose_type = $row["dose_type"];
                    $given_by = $row["given_by"];
										
					$meds_formatted_date = new DateTime($date);
					$meds_formatted_date = $meds_formatted_date->format('D j M Y \- H:i');

                    print '
		            ' . $meds_formatted_date . ' <b>' . $dose . '' . $dose_type . ' of ' . $medication_given . ' </b> given by ' . $given_by . ' <br>
                        ';
                            }
                    ?> </p>
    </div>
    <div class="col-6 px-5 py-3">
        <p class="text-primary">
                <strong><u>Treatments:</u></strong> </p>
                <p class="text-primary">
                    <?php
                    //gets the treatments from the table to display 
                    $stmt = $conn->prepare("SELECT * FROM rescue_treatments WHERE patient_id = :patient_id ORDER by date DESC");
                    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                    // initialise an array for the results
                    $treatments = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                    $tr_date = $row["date"];
                    $treatment = $row["treatment"];
                    $treatment_free_text = $row["treatment_free_text"];
                    $done_by = $row["done_by"];
										
					$treat_formatted_date = new DateTime($tr_date);
					$treat_formatted_date = $treat_formatted_date->format('D j M Y \- H:i');
        
        print '
               ' . $treat_formatted_date . '<BR> <b>' . $treatment . ' </b> <i>' . $treatment_free_text . ' </i> <BR> --- done by: ' . $done_by . '
                ';
                    }
                ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Care given -->
<div class="col">
    <div class="card mb-4">
        <div class="card-header">
                    <p class="text-primary">
                    <strong><u>Care Notes:</u></strong> </p>
                </div>
        <div class="row">
            <div class="col-12 px-5 py-3">  
                <p class="text-primary">
                    <?php
                    //Get care notes
                    $stmt = $conn->prepare("SELECT * FROM rescue_notes_patients WHERE patient_id=:patient_id AND deleted = 0 ORDER BY date DESC");
                    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

                    // initialise an array for the results
                    $carenotes = array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                    $note_id = $row["note_id"];
                    $note_message = $row["message"];
                    $note_date = $row["date"];
                    $note_author = $row["author"];
	                $public = $row["public"];

                    $care_formatted_date = new DateTime($note_date);
                    $care_formatted_date = $care_formatted_date->format('D j M Y \- H:i');

                    print '
		            <b>' . $note_message . '</b><br>
                     <i>-- Logged on: ' . $care_formatted_date . ' by ' . $note_author . '</i><br>
                        ';
                            }
                    ?>
                </p>
            </div>
            
        </div>
    </div>
</div>
<!--
<div class="row">
    <div class="col-5">  
        <div class="card">
            <div class="chart-area">
                    <canvas id="measurementChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-5"> 
        <div class="card">
            <div class="chart-area">
                <canvas id="myAreaChart"></canvas>
            </div>
        </div>
    </div>
</div>
 -->

</section>

</div>
<footer>
      RESCUE CENTRE - https://www.rescuecentre.org.uk
	  <small class="float-right">Printed on: <?php
// Return current date from the remote server
$todaysdate = date('d-m-y h:i:s');
echo $todaysdate;
?> by <?php echo $adm_cent; ?></small><BR>
    </footer>




<script>
  window.addEventListener("load", window.print());
</script>

<!-- CHART SCRIPTS -->
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/vendor/chart.js/Chart.min.js"></script>
<script src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/js/demo/chart-pie-demo.js"></script>

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
                        maxTicksLimit: 7
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