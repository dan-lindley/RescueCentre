<?php

$filename = 'rescuecentredata.csv';
$export_data = unserialize($_POST['export_data']);

$headers = array("Admission Date", "Animal Identifier", "Order", "Type", "Species", "Age", "Sex", "Complaint", "Disposition", "Starved", "Dehydrated", "Wind Speed", "Humidity", "Temperature", "Weather Freetext");

// Create File
$file = fopen($filename,"w");

fputcsv($file,$headers);

foreach ($export_data as $line){
    fputcsv($file,$line);
}

fclose($file);

// Download
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=".$filename);
header("Content-Type: application/csv; "); 

readfile($filename);

// Deleting File
unlink($filename);

exit();