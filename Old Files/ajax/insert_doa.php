<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    include "../connect_to_mysql.php";

    //Add New Admission Form 
    $current_user_id = $_POST["thestaffid"];
  

    $alertmsg = "";
    $errorName = "";
    $errorSex = "";
    $errorRinged = "";
    $errorMicrochipped = "";
    $errorOrder = "";
    $errorType = "";
    $errorSpecies = ""; 


    function test_input($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    //Get the current time from the server
    $date_added = date('Y-m-d H:i:s');

    $new_patient_id = test_input($_POST["the_patient"]);
	$new_current_location = test_input($_POST["current_location"]);
    $new_location = test_input($_POST["location"]);
    $new_finder_name = test_input($_POST["finder_name"]);
    $new_age_on_admission = test_input($_POST["age_on_admission"]);
	$new_admission_date = test_input($_POST["admission_date"]);
    $new_presenting_complaint = test_input($_POST["presenting_complaint"]);
    $new_dehydrated = test_input($_POST["dehydrated"]);
    $new_starved = test_input($_POST["starved"]);
    $new_weight = test_input($_POST["weight"]);
    $new_weight_unit = test_input($_POST["weight_unit"]);
    $new_measurement = test_input($_POST["measurement"]);
    $new_measurement_unit = test_input($_POST["measurement_unit"]);
    $new_disposition = test_input($_POST["disposition"]);
    $new_staff_id = test_input($_POST["thestaffid"]);
    $new_status = test_input($_POST["status"]);
    $centre_id = test_input($_POST["centre_id"]);
	$owner_id = test_input($_POST["owner_id"]);
	$w_temp = test_input($_POST["w_temp"]);
	$w_wind = test_input($_POST["w_wind"]);
	$w_humidity = test_input($_POST["w_humidity"]);
	$w_freetext = test_input($_POST["w_freetext"]);
	$finder_tel= test_input($_POST["finder_tel"]);
	$consent_to_update = test_input($_POST["consent_to_update"]);
    $adm_hpc = test_input($_POST["hpc"]);
    $adm_on_examination = test_input($_POST["on_examination"]);
    $adm_bcs_text = test_input($_POST["bcs_text"]);
    $adm_ss_text = test_input($_POST["ss_text"]);
    $adm_lat = test_input($_POST["location_lat"]);
    $adm_long = test_input($_POST["location_long"]);
    $adm_passphrase = test_input($_POST["passphrase"]);

// figure out the age score from the posted age
if ($new_age_on_admission == 'Newborn') {
    $adm_age_score = '3';
} elseif ($new_age_on_admission == 'Dependent Juvenile') {
    $adm_age_score = '2';
} elseif ($new_age_on_admission == 'Independent Juvenile') {
     $adm_age_score = '1';
} elseif ($new_age_on_admission == 'Hatchling') {
    $adm_age_score = '3';
} elseif ($new_age_on_admission == 'Fledgling') {
    $adm_age_score = '2';
} elseif ($new_age_on_admission == 'Adult') {
    $adm_age_score = '0';
}

// figure out the severity score from the severity 
if ($adm_ss_text == 'Apparently Healthy') {
    $severity_score = '0';
} elseif ($adm_ss_text == 'Mildly unwell') {
    $severity_score = '0';
} elseif ($adm_ss_text == 'Obvious Injuries') {
     $severity_score = '1';
} elseif ($adm_ss_text == 'Severe Injuries') {
    $severity_score = '2';
} elseif ($adm_ss_text == 'Near Death') {
    $severity_score = '3';
}

// figure out the body score from posted 
if ($adm_bcs_text == 'BCS 1 Skeletal') {
    $bc_score = '3';
} elseif ($adm_bcs_text == 'BCS 2 Underweight') {
    $bc_score= '2';
} elseif ($adm_bcs_text == 'BCS 3 Slightly Underweight') {
    $bc_score = '1';
} elseif ($adm_bcs_text == 'BCS 4 Healthy') {
    $bc_score = '0';
} elseif ($adm_bcs_text == 'BCS 5 Overweight') {
    $bc_score = '0';
}

// Update patient state on patients table 
$update_state = "Deceased";
$update_status = "Deceased";
$statement = $conn->prepare('UPDATE rescue_patients
SET rescue_patients.state = :pt_state,
    rescue_patients.status = :pt_status
WHERE patient_id = :patient_id');

$statement->execute([
    'patient_id' => $new_patient_id,
    'pt_state' => $update_state,
    'pt_status' => $update_status
]);


//Insert into the measurements table
    $statement = $conn->prepare('INSERT INTO rescue_measurements
    (patient_id,
    date,
    measurement,
    measurement_unit)

    VALUES (:patient_id,
    :date,
    :measurement,
    :measurement_unit)');

    $statement->execute([
        'patient_id' => $new_patient_id,
        'date' => $new_admission_date,
        'measurement' => $new_measurement,
        'measurement_unit' => $new_measurement_unit
    ]);

//Insert into the weights table
    $statement = $conn->prepare('INSERT INTO rescue_weights
    (patient_id,
    date,
    weight,
    weight_unit)

    VALUES (:patient_id,
    :date,
    :weight,
    :weight_unit)');

    $statement->execute([
        'patient_id' => $new_patient_id,
        'date' => $new_admission_date,
        'weight' => $new_weight,
        'weight_unit' => $new_weight_unit
    ]);

    

//Insert into the admissions table
    $statement = $conn->prepare('INSERT INTO rescue_admissions
        (patient_id,
        admission_date,
        age_on_admission,
        presenting_complaint,
        dehydrated,
        starved,
        status,
		current_location,
        collection_location,
        finder_name,
        disposition,
        weight,
        weight_unit,
        measurement,
        measurement_unit,
        centre_id,
		owner_id,
		severity_score,
		w_temp,
		w_wind,
		w_humidity, 
		w_freetext,
		finder_tel,
		consent_to_update,
        hpc,
        on_examination,
        ss_text,
        bc_score,
        bcs_text,
        age_score,
        location_lat,
        location_long,
        passphrase,
        staff_wp_id)

        VALUES (:patient_id,
        :admission_date,
        :age_on_admission,
        :presenting_complaint,
        :dehydrated,
        :starved,
        :status,
		:current_location,
        :collection_location,
        :finder_name,
        :disposition,
        :weight,
        :weight_unit,
        :measurement,
        :measurement_unit,
        :centre_id,
		:owner_id,
		:severity_score,
		:w_temp,
		:w_wind,
		:w_humidity,
		:w_freetext,
		:finder_tel,
		:consent_to_update,
        :hpc,
        :on_examination,
        :ss_text,
        :bc_score,
        :bcs_text,
        :age_score,
        :location_lat,
        :location_long,
        :passphrase,
        :staff_wp_id)');

    $statement->execute([
        'patient_id' => $new_patient_id,
        'admission_date' => $new_admission_date,
        'age_on_admission' => $new_age_on_admission,
        'presenting_complaint' => $new_presenting_complaint,
        'dehydrated' => $new_dehydrated,
        'starved' => $new_starved,
        'status' => $new_status,
		'current_location' => $new_current_location,
        'collection_location' => $new_location,
        'finder_name' => $new_finder_name,
        'disposition' => $new_disposition,
        'weight' => $new_weight,
        'weight_unit' => $new_weight_unit,
        'measurement' => $new_measurement,
        'measurement_unit' => $new_measurement_unit,
        'centre_id' => $centre_id,
		'owner_id' => $owner_id,
		'severity_score' => $severity_score,
		'w_temp' => $w_temp,
		'w_wind' => $w_wind,
		'w_humidity' => $w_humidity,
		'w_freetext' => $w_freetext,		
		'finder_tel' => $finder_tel,	
		'consent_to_update' => $consent_to_update,
        'hpc' => $adm_hpc,
        'on_examination' => $adm_on_examination,
        'ss_text' => $adm_ss_text,
        'bc_score' => $bc_score,
        'bcs_text' => $adm_bcs_text,
        'age_score' => $adm_age_score,
        'location_lat' => $adm_lat,
        'location_long' => $adm_long,
        'passphrase' => $adm_passphrase,
		'staff_wp_id' => $current_user_id
    ]);



    $alertmsg = '<div class="alert alert-success" role="alert">
        The new admission was successfully added to the database.
        </div>';
	

	
} else {
    echo "error";
    exit();
}
?>
