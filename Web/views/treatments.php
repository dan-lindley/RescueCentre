<!--- DISPLAYS THE TREATMENTS GIVEN -->
<?php
      //gets the treatments from the table to display 
      $stmt = $conn->prepare("SELECT * FROM rescue_treatments WHERE patient_id = :patient_id ORDER by date DESC LIMIT 10");
      $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

      // initialise an array for the results
       $treatments = array();
       $stmt->execute();
       while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

       $date = $row["date"];
       $treatment = $row["treatment"];
       $treatment_free_text = $row["treatment_free_text"];
       $done_by = $row["done_by"]; ?>
       
<div class="note message speech bottom"><?php echo $date;?>  - <strong><?php echo $treatment;?> </strong> given by <strong> <?php echo $done_by; ?> 
<BR><U>Notes:</U></strong> <?php echo $treatment_free_text ?> </td> </div> 
<?php }?>
