<?php 
/*----------------------- FORM PROCESSING CARE NOTES-------------------*/
//Check if the notes form was submitted
if (isset($_POST['form1'])) {

	$patient_id = $_POST["patient_id"];
    $new_note = $_POST["new_note"];
    $note_author = $_POST["note_author"];
	$public = $_POST["public"];
    $image = $_POST["image_id"];

    //Get the current time from the server
    $date = date('Y-m-d H:i:s');

    try {
        $statement = $conn->prepare('INSERT INTO rescue_notes_patients
            (patient_id, 
            message,
            author,
			public,
            image_id,
            date)
            
            VALUES (:patient_id, 
            :message,
            :author,
			:public,
            :image_id,
            :date)');

        $statement->execute([
            'patient_id' => $patient_id,
            'message' => $new_note,
            'author' => $note_author,
			'public' => $public,
            'image_id' => $image,
            'date' => $date
        ]);
		
		  echo "<script>window.location = window.location</script>";
		
    } catch (PDOException $e) {
        echo "Database Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The note could not be added.<br>" . $e->getMessage();
        exit();
    }
}
/*------------ END FORM ----------------*/
?>
<!-- CARE NOTES MODAL -->
				   
<div class="modal fade" id="carenotesModal" tabindex="-1" role="dialog" aria-labelledby="carenotesModal" aria-hidden="true">		
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="font-weight-bold text-primary">Add a care note</h4><button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span></button>
            </div>
                                
<div class="modal-body">
	<b>Patient - <span class="admissionnameDisplay"><?php echo $patient_name ?></span></b> (CRN: <span class="admissionIDDisplay"><?php echo $patient_id ?></span>)
    <form action="" method="post">
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
        //gets the imaeges
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
        <input type="hidden" id="patient_id" name="patient_id" value="<?php echo $patient_id;?>">
        <input type="hidden" id="note_author" name="note_author" value="<?php $current_user = wp_get_current_user();
                                                                            print($current_user->user_firstname); ?>">
        <input type="submit" id="submit" name="form1" value="Add Care Note" class="form_submit">
    </div>
</form>
</div>

        </div>
    </div>
</div>

<!--- END OF CARE NOTES MODAL  ---->