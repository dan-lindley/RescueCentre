<?php
include "../connect_to_mysql.php";
header('Location: ' . $_SERVER['HTTP_REFERER']);
//Get the logged in user's information from the form post database
//Check if the notes form was submitted
if (isset($_POST['upload_image'])) {

	$patient_id = $_POST["patient_id"];
    $rescue_name = $_POST["rescue_name"];
    $centre_id = $_POST["centre_id"];
}
//change spaces in rescue name fr underscores
$rescue_name = str_replace(' ', '_', $rescue_name);

//below creates a new directory within the user_images folder for each rescue and a folder for the patient
if (!file_exists('../user_images/'.$rescue_name.'/'.$patient_id.'')) {
    mkdir('../user_images/'.$rescue_name.'/'.$patient_id.'', 0755, true);
}

// get the image ready for upload and put in the right folder
$target_dir = '../user_images/'.$rescue_name.'/'.$patient_id.'/';
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

$saved_dir = 'user_images/'.$rescue_name.'/'.$patient_id.'/'.( $_FILES["fileToUpload"]["name"]).'';

// Check if image file is a actual image or fake image
if(isset($_POST["upload_image"])) {
  $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
  if($check !== false) {
    echo "File is an image - " . $check["mime"] . ".";
    $uploadOk = 1;
  } else {
    echo "File is not an image.";
    $uploadOk = 0;
  }
}
// Check if file already exists
if (file_exists($target_file)) {
  echo "Sorry, file already exists.";
  $uploadOk = 0;
}

// Check file size
if ($_FILES["fileToUpload"]["size"] > 10000000) {
    $imgmsg = '<div class="alert alert-warning" role="alert">
        Sorry, file is too big.</div>';
  echo "Sorry, your file is too large.";
  $uploadOk = 0;
}

// Allow certain file formats
if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
&& $imageFileType != "gif" ) {
    $imgmsg = '<div class="alert alert-warning" role="alert">
        Sorry, only JPG, JPEG, PNG & GIF files are allowed.
        </div>';
  echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
  $uploadOk = 0;
}

// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    $imgmsg = '<div class="alert alert-warning" role="alert">
    Sorry, image was not uploaded.
    </div>';
  echo "Sorry, your file was not uploaded.";
// if everything is ok, try to upload file
} else {
  if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
    $imgmsg = '<div class="alert alert-warning" role="alert">
    Success! Image uploaded
    </div>';
    echo "The file ". htmlspecialchars( basename( $_FILES["fileToUpload"]["name"])). " has been uploaded.";
    echo $centre_id;
    echo $rescue_name;
    echo $target_file;
  } else {
    echo "Sorry, there was an error uploading your file.";
    $imgmsg = '<div class="alert alert-warning" role="alert">
    Sorry, image was not uploaded.
    </div>';
  }

// Add the record of the image to the database and patient record
try {
$statement = $conn->prepare('INSERT INTO rescue_images
(centre_id, 
patient_id,
image_url,
file_name)

VALUES (:centre_id, 
:patient_id,
:image_url,
:file_name)');

$statement->execute([
'patient_id' => $patient_id,
'centre_id' => $centre_id,
'image_url' => $saved_dir,
'file_name' => $_FILES["fileToUpload"]["name"]
]);  
} catch (PDOException $e) {
    echo $e->getMessage();
    die($e->getMessage());
    exit(); }

}
?>