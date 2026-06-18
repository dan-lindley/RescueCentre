<?php
;
 ?>
<select name="disposition" id="disposition" required  style="width: 100%">
        <option value="" disabled selected>Select patient disposition</option>
        <?php
        //Find dispositions
        $stmt = $conn->prepare("SELECT * 
                                FROM rescue_dispositions
                                ORDER BY disposition ASC");
        // initialise an array for the results
        $lkdisp = array();
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lkdisid = $row["disposition_id"];
        $lkdis = $row["disposition"];
                    
        print '<option value="' . $lkdis. '">' . $lkdis. ' </option>';
                                            } ?>
</select>

Test 