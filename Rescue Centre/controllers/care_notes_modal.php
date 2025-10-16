<?php 
/*----------------------- FORM PROCESSING CARE NOTES-------------------*/
//Check if the notes form was submitted
if (isset($_POST['carenotes'])) {

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

<!-- The Modal -->
<div id="careNotes" class="modal">

  <!-- Modal content -->
  <div class="modal-content">
    <div class="modal-header">
      <span class="hidemodal">&times;</span>
        <h2>Add a care note</h2>
    </div>
<?php echo $patient_name; ?>
    <div class="modal-body">
      <b>Patient - <span class="admissionnameDisplay"></span></b> (CRN: <span class="admissionIDDisplay"></span>)
      <div class="form-group">
      <form action="" method="post">
        <p><label for="new_note">Enter your note below:</label></p>
        <textarea id="new_note" name="new_note" rows="4" style="min-width:500px; max-width:100%; min-height:50px; height:100%; width:100%;"></textarea>
	    <p><BR> Make this note public? <select id="public" name="public">
        <option selected="selected">No</option>
                        <option>Yes</option>
                        </select></td></div>
    
   


                        
    </div>
    <div class="modal-bottom">
      <h4>Use care notes to document the patients care progress</h4>
    </div>
</div>



<script>
// Get the modal
var modal = document.getElementById("careNotes");
// Get the button that opens the modal
var btn = document.getElementById("carenotesBtn");
// Get the <span> element that closes the modal
var span = document.getElementsByClassName("hidemodal")[0];
// When the user clicks the button, open the modal 
btn.onclick = function() {
  modal.style.display = "block";
}
// When the user clicks on <span> (x), close the modal
span.onclick = function() {
  modal.style.display = "none";
}
// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
}
</script>

