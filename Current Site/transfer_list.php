<?php defined('ABSPATH') or die('This script cannot be accessed directly.');
include_once "authentication.php";
include_once "connect_to_mysql.php";


//Get logged in user's name 
$user_info = get_userdata(get_current_user_id());
$wp_first_name = $user_info->first_name;
$wp_last_name = $user_info->last_name;
$wp_fullname = "" . $wp_first_name . " " . $wp_last_name . "";
$centre_id = '1';

echo "<div class='app_page_container'>";
/**
 * The template for displaying pages
 *
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

/* Template Name: Print Transfer List */
get_header();
?>


<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<link rel="stylesheet" href="css/print.css">

<div class="wrapper">

<section class="invoice">

<img src="https://rescuecentre.org.uk/wp-content/uploads/2023/04/black-logo-v3.png" width="30% height="30%">
																										   

<div class="row">
<div class="col-12"><BR>
<table><TR><TD>
<h3 class="page-header">
Transfer List for <?php
 print ' ' .$patient_name . ' '?></h2>
 </td></tr></table>
</div>
</div>

<div class="row invoice-info">
<div class="col-sm-4 invoice-col">
<!-- Display patients from the database -->
        <div class="card shadow mb-4" id="databasetable">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">You have <?php echo $row_count; ?> admissions in your rescue</h6>

            </div>
            
        <!------------------------------------------------------->


                    <table class="table table-bordered" id="admittable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Admission Date</th>
                                <th>Patient</th>
								<th>Location</th>
                                <th>Animal Type</th>
                                <th>Sex</th>
								<th>Adm W</th>
								<th>Adm M</th>																			 
                                <th>Presenting Complaint</th>
             
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>Admission Date</th>
                                <th>Patient</th>
								<th>Location</th>
                                <th>Animal Type</th>
                                <th>Sex</th>
			     				<th>Adm W</th>
								<th>Adm M</th>
                                <th>Presenting Complaint</th>
                                
                            </tr>
                        </tfoot>
                        <tbody>

                            <?php
                            //Loop from admissions table
                            $stmt = $conn->prepare("SELECT * 
                            FROM rescue_admissions
                            INNER JOIN rescue_patients
                            ON rescue_admissions.patient_id = rescue_patients.patient_id
                            WHERE rescue_patients.centre_id = :centre_id AND rescue_admissions.disposition = 'Held in captivity' 
                            ORDER by 'admission_date' ASC");
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
                                $admission_date = $row["admission_date"];
                                
								


                                print '<tr>
                                <td>' . $admission_date . '</td>
                                <td>' . $admission_name . '</td>
								<td>' . $admission_location . '</td>
                                <td>' . $admission_animal_species . ' (' . $admission_animal_type . ')</td>
                                <td>' . $admission_sex . '</td>
								<td>' . $admission_weight . '' . $admission_weight_unit . '</td>
								<td>' . $admission_measurement . '' . $admission_measurement_unit . '</td>
                                <td>' . $admission_presenting_complaint . '</td>

                                ';
                            }

                            ?>


                        </tbody>
                    </table>
            

<footer>
      RESCUE CENTRE - https://www.rescuecentre.org.uk
	  Printed on: <small class="float-right"><?php
// Return current date from the remote server
$todaysdate = date('d-m-y h:i:s');
echo $todaysdate;
?></small><BR>
    </footer>
<script>
  window.addEventListener("load", window.print());
</script>