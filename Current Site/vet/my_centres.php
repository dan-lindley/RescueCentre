

<!-- DISPLAYS THE CENTRES FOR THIS VET -->
    <table class="table table-bordered table-sm" id="dataTable" width="100%" cellspacing="0">
    <thead>
        <tr>
            <th>Rescue Name</th>
            <th>City</th>
            <th>Email</th>      
            <th>Tel</th>    
            <th>actions</th>               
        </tr>
    </thead>
    <tbody>

    <?php
    //Gets a list of the rescue centres the vet is assigned to 
        $stmt = $conn->prepare("SELECT *
                FROM rescue_vet_centres AS v
                JOIN rescue_centres AS c
                ON v.centre_id = c.rescue_id
                WHERE v.user_id = :user_id");
        $stmt->bindParam(':user_id', $wp_id, PDO::PARAM_INT);

    // initialise an array for the results
        $myrescues = array();
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $rescuename = $row["rescue_name"];
        $rescuecity = $row["city"];
        $rescueemail = $row["email"];
        $rescuetel = $row["24_hour"];


        print '
        <tr><td>' . $rescuename . ' </td>
            <td>' . $rescuecity . ' </td>
            <td>' . $rescueemail . ' </td>
            <td>' . $rescuetel . ' </td>
            <td></td>
        </tbody>';
                                    }
        ?></table>