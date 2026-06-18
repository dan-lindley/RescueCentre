<?php 
/*------------------------------------------------------------------ FORM PROCESSING - Clear Telephone-------------------------------------------------------------------*/
if (isset($_POST['telclear'])) {

    $admission_id = $_POST["admission_id"];
    $finder_tel = $_POST["finder_tel"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_admissions
            ( 
            admission_id,
			finder_tel)
            
            VALUES (
            :admission_id,
			:finder_tel) 
			
			ON DUPLICATE KEY UPDATE
			finder_tel = :finder_tel	
			');

        $statement->execute([
            'admission_id' => $admission_id,
            'finder_tel' => $finder_tel
            
        ]);
		echo "<meta http-equiv='refresh' content='0'>";
		
    } catch (PDOException $e) {
        echo "Database Error: Could not clear finder tel.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: could not clear finder tel.<br>" . $e->getMessage();
        exit();
    }

}
?>

<!-- FORM AS FUNCTIUON TO CLEAR FINDER TEL -->

<!--<form method="post" action="">
    <input type="hidden" id="admission_id" name="admission_id" value="'. $currentAdmission_id .'">
    <input type="hidden" id="finder_tel" name="finder_tel" value="">
    <button type="submit" class="btn-sm btn-outline-danger" name="telclear" aria-describedby="clearwarning">Clear Finder Tel</button> 
    
</form>-->