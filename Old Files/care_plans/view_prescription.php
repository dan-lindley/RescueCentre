<!-- DISPLAYS THE PRESCRIPTION SECTION FOR THE PATIENT -->
<div class="table-responsive">
    <table class="table table-bordered table-sm" id="dataTable" width="100%" cellspacing="0">
    <thead>
        <tr>
            <th>Date</th>
            <th>Medication</th>
            <th>Dose</th>
            <th>Frequency</th>
            <th>Duration</th>
			<th>Route</th>                         
        </tr>
    </thead>
    <tbody>

    <?php
    //gets the prescriptions from the table to display 
        $stmt = $conn->prepare("SELECT * FROM rescue_prescriptions WHERE patient_id = :patient_id ORDER by date DESC LIMIT 10");
        $stmt->bindParam(':patient_id', $patient_id, PDO::PARAM_INT);

    // initialise an array for the results
        $prescribed = array();
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $date = $row["date"];
        $medication = $row["medication"];
        $dose = $row["dose"];
        $dose_type = $row["dose_type"];
        $duration = $row["duration"];
		$frequency = $row["frequency"];
		$route = $row["route"];

        print '
        <tr><td>' . $date . ' </td><td> ' . $medication . ' </td><td> ' . $dose . ' ' . $dose_type . ' </td><td>' . $frequency . '</td><TD> ' . $duration . ' days</td><td> ' .$route. '</td>
                                    </tr></tbody>';
                                    }
        ?></table></div>