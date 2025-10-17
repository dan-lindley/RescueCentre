<?php 
/*----------------------- FORM PROCESSING SEARCH-------------------*/
//Check search form was submitted
if (isset($_POST['search'])) {

	$centre_id = $_POST["centre_id"];
    $search = $_POST["search"];


    try {
        $statement = $conn->prepare('INSERT INTO lat_search
        (centre_id,
        query)
        
        VALUES (:centre_id,
        :query) 
        
        ON DUPLICATE KEY UPDATE
        centre_id = :centre_id,
        query = :query
        ');

    $statement->execute([
        'centre_id' => $centre_id,
        'query' => $search
		
    ]);
    echo "<meta http-equiv='refresh' content='0'>";
		
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

<!-- LAT/Long search MODAL -->
				   
<div class="modal fade" id="latsearchModal" tabindex="-1" role="dialog" aria-labelledby="latsearchModal" aria-hidden="true">		
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
            <h4 class="font-weight-bold text-primary">Search for Lat long</h4> 
           
            <span aria-hidden="true">&times;</span>
            </button>
            </div>
        <div class="modal-body">
            <div class="row lead_form_row"> 
	            <div class="col-md-12 my-auto">
                    <form action="" method="post" id="latsearch">
                    <input type="hidden" id="centre_id" name="centre_id" value="<?php echo $centre_id; ?>">
                    <input type="text" id="search" name="search">
                    <input type="submit" value="Submit" class="form-submit">   
                    </form>
                </div>
            </div>
            <div class="row lead_form_row"> 
	            <div class="col-md-12 my-auto">
           <table class="table-bordered table-sm table-hover" id="" width="100%" cellspacing="0"> 
            
                <thead>
                        <tr>
		                <th>Postcode</th>
                        <th>Coiuntry/state</th>
                        <th>Latitude</th>
		                <th>Longitude</th>
                        </tr>
                        </thead>
                        <tbody> 
                        <?php
                            //get orescriotioins
                            $stmt = $conn->prepare("SELECT * FROM
	                    postcodelatlng,
	                   lat_search 
                        WHERE INSTR(postcodelatlng.postcode, lat_search.query) > 0 AND
						lat_search.centre_id = :centre_id ");
                            $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);

                            // initialise an array for the results
                            $postcodesearcharray = array();
                            $stmt->execute();
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                                $postcode = $row["postcode"];
								$country_code = $row["country_code"];
                                $latitude = $row["latitude"];
                                $longitude = $row["longitude"]; ?>
  

                        <tr>
						    <td><?php echo $postcode; ?></td>
                      
                            <td><?php echo $country_code; ?></td>
                            <td><?php echo $latitude; ?></td>
                            <td><?php echo $longitude; ?></td>
                       
                     <?php } ?>
                            </tr>
                           

                            </tbody> 
                            </table>
                            </div>
                            </div>
 
                            <button type = "button" class = "btn btn-default" data-dismiss = "modal">Cancel</button>
</div></div></div></div>



<!--- END OF MODAL  ---->

