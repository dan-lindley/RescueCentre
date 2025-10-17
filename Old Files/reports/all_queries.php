
 <?php

//This section is to set up the query dates for the annual report
    $stmt = $conn->prepare("SELECT * 
                            FROM rescue_query
                            WHERE centre_id = :centre_id ORDER BY q_date DESC LIMIT 1");
    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
    $queries = array();
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $q_date = $row["q_date"];
    $q_from = $row["q_from"];
    $q_to = $row["q_to"];
									
     }

//We are just running the query for the dates selected so additional years need not be included
//Get the values for the dispositions
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
JOIN
rescue_query
ON rescue_query.centre_id = rescue_admissions.centre_id
WHERE rescue_patients.centre_id = :centre_id AND admission_date BETWEEN rescue_query.q_from AND rescue_query.q_to");
      
$stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
// initialise an array for the result
$ytddispdata = array();
$stmt->execute();
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $artotal = $row["total"];
  $arcaptive = $row["Captive"];
  $arreleased = $row["Released"];
  $artrans = $row ["Transferred"];
  $areuth = $row["DiedEuth"];
  $arin48 = $row["Diedin48"];
  $arfter48 = $row["Diedafter48"];
  $ardoa = $row["Diedadmit"];


  $ardiedtotal = ($areuth + $arin48 + $arfter48 + $ardoa);

//Calculate the clinical efficiency
//start by checking the total is zero and making it 0 if so
  if ($ytddisptotal == 0 ) {
  $ytdclinefficiency = '0';
 	} elseif ($ytddisptotal > 0) {
	$ytdclinefficiency = ($ytddispreleased / ($ytddisptotal - ($ytddispin48 + $ytddispdoa + $ytddispeuth + $ytddisptrans +$ytddispcaptive)) * 100);
  }
 

};


?>