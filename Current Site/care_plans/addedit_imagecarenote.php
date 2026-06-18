<?php 
/*----------------------- FORM PROCESSING EDIT IMAGE FOR CARE NOTES-------------------*/
//Check if the notes form was submitted
if (isset($_POST['imageupdate'])) {

	$note_id = $_POST["note_id"];
    $image_id = $_POST["image_id"];

    try {
        $imgqry = "UPDATE rescue_notes_patients
                    SET 
                    rescue_notes_patients.image_id = :image_id
                    WHERE rescue_rescue_notes_patients.note_id = :note_id";

        $stmt = $conn->prepare($imgqry);
        $stmt->bindParam('image_id', $image_id, PDO::PARAM_STR);
        $stmt->bindParam('note_id', $note_id, PDO::PARAM_STR);

        $stmt->execute();
		
		  echo "<script>window.location = window.location</script>";
		
        } catch (PDOException $e) {
            echo $e->getMessage();
            die($e->getMessage());
        }
    } else {
        echo "Could not update the image, sorry";
        exit();
    }
/*------------ END FORM ----------------*/
?>
<!-- CARE NOTES MODAL -->
				   
<div class="modal fade" id="imageupdateModal" tabindex="-1" role="dialog" aria-labelledby="imageupdateModal" aria-hidden="true">		
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
            <h4 class="font-weight-bold text-primary">Add a care note</h4><button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span></button>
            </div>
                                
    <div class="modal-body">
		<b>Patient - <span class="admissionnameDisplay"><?php echo $patient_name ?></span></b> 
            <form action="" method="post">
                
                <input type="hidden" id="note_author" name="note_author" value="<?php
                                                                                        $current_user = wp_get_current_user();
                                                                                        print($current_user->user_firstname); ?>">

                        <p><label for="new_note">Enter your note below:</label></p>
                        <textarea id="new_note" name="new_note" rows="4" cols="50"></textarea>
														<p><BR> Make this note public? <select id="public" name="public">
                                                        <option selected="selected">No</option>
                                                        <option>Yes</option>
                                                       
                                                    </select></td>
    
<div class="row lead_form_row">	
<div class="col-md-2 my-auto">
<input type="radio" id="html" name="image_id" value="0">
    <img src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/img/no_image.jpg" width="150px">
</div>
<?php
    //gets stuff from admissions table
    $stmt = $conn->prepare("SELECT * FROM rescue_images WHERE patient_id=:patient_id ORDER by image_id ASC");
    $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

    // initialise an array for the results
    $patientimage = array();
    $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $image_id = $row["image_id"];
            $imageurl = $row["image_url"];
            $name = $row["file_name"]; ?>

<div class="col-md-2 my-auto">
<input type="radio" id="html" name="image_id" value="<?php echo $image_id; ?>">
    <img src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/<?php echo $imageurl;?>" width="150px">
    <BR><?php echo $name; ?>
    </div>
    <?php } ?>

           
                        <input type="submit" id="submit" name="imageupdate" value="Add Care Note" class="form_submit">
						<input type="hidden" id="patient_id" name="patient_id" value="<?php echo $patient_id;?>">
                        <input type="hidden" id="noteID" name="noteID" value="<?php echo $note_id;?>">
                    
 </form></div></div></div></div>

<!--- END OF CARE NOTES MODAL  ---->