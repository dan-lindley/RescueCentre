<!--- CARE NOTES SECTION ---->   

 <?php
   //Get existing notes
   $stmt = $conn->prepare("SELECT * FROM rescue_notes_patients 
                          LEFT JOIN rescue_images ON
                          rescue_notes_patients.image_id = rescue_images.image_id
                          WHERE rescue_notes_patients.patient_id=:patient_id AND deleted = 0 ORDER BY date DESC");
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
   $imageurl = $row["image_url"];
   $imagename = $row["file_name"];
   $image_id=["image_id"];

   if (empty($imageurl)) {
    $imageurl = "img/no_image.png";
}
   
   $formatted_date = new DateTime($note_date);
   $formatted_date = $formatted_date->format('jS \o\f F Y \a\t H:i'); ?>
                            
  <div class="note_message speech bottom">
    <div class="row lead_form_row">	
      <div class="col-md-2 my-auto">
       
          <img src="https://rescuecentre.org.uk/wp-content/themes/brikk-child/<?php echo$imageurl; ?>" width="150px">
          <BR><strong><?php echo $imagename; ?></strong>

        
      </div>
      <div class="col-md-10 my-auto">
          <?php echo $note_message; ?><br />
             Care note added: <?php echo $formatted_date; ?> by <strong><?php echo $note_author; ?></strong> <BR>Is this note visible to the public? <strong><?php echo $public ?></strong>
      </div>
    </div>
   </div>
		<?php } ?>	
   
							
                        <br>
		   <!-- Add new care note Button -->
<button type="button" class="btn btn-success" data-toggle="modal" data-target="#carenotesModal"> Add Care Note</button> <BR>

<!-- END OF CARE NOTES SECTION -->	  
