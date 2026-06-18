<?php
    //gets the medications from the table to display 
        $stmt = $conn->prepare("SELECT obs_date, obs_id, obs_severity_score, obs_bcs_score, obs_age_score,
                                FROM rescue_observations AS o
                                WHERE o.patient_id = :patient_id ORDER by obs_date DESC LIMIT 1");
                                $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

        // initialise an array for the results
        $lastscore = array();
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $lastwradate = $row["obs_date"];
        $lastwraid = $row["obs_id"];
        $lastwra_ss = $row["obs_severity_score"];    
        $lastwra_bcs = $row["obs_bcs_score"];  
        $lastwra_age = $row["obs_age_score"];
		$lastwraformat_date = new DateTime($lastwra_date);
   		$lastwraformat_date = $lastwraformat_date->format('d-m-Y');
		$lastwraformat_time = new DateTime($lastwra_date);
		$lastformat_time = $lastwraformat_time->format('H:i');
        
        $last_wra = ($lastwra_bcs + $lastwra_age) + $lastwra_ss; 
        }
        

        ?>