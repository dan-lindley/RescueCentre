<?php 
//All the current data
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

//All data but year to date
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