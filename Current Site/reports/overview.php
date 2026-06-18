<div class="row">
    <div class ="col">
        <H1 class="font-weight-bold text-primary"> Centre Statistics </h1>
    </div>
</div>
<div class="row">
    <div class="col-6">
        <H6 class="font-weight-bold text-primary"><U>Totals:</u></h6>
            <?php print '
                <strong>Total admissions:</strong> ' . $artotal . '
                <BR><b>Currently Captive:</B> ' . $arcaptive . ' 
                <BR><b>Released:</b> ' . $arreleased . ' 
                <BR><b>Total that have died: </b>' . $ardiedtotal . ' 
                <BR><b>Transferred out: </b>' . $artrans . ' 
                    ';
                        ?>
    </div>
    <div class="col-6">
        <H6 class="font-weight-bold text-primary"><U>Patients that have died:</u></h6>
            <?php print '
                <b>Died on arrival: </b>' . $ardoa . '
                <BR><b>Euthanised:</b> ' . $areuth . ' 
                <BR><strong>Died in 48 hours:</strong> ' . $arin48 . '
                <BR><b>Died after 48 hours:</B> ' . $arafter48 . ' 
                
                
                    ';
                        ?>
    </div>
</div>

<div class="row">
    <div class ="col">
        <H1 class="font-weight-bold text-primary"> Species </h1>
    </div>
</div>
<div class="row">
    <div class="col-6">
        <H6 class="font-weight-bold text-primary"><U>Totals:</u></h6>
       <?php // Query for the species information

    $stmt = $conn->prepare("SELECT p.animal_species, a.admission_date, COUNT(p.animal_species) as speciescount
                            FROM rescue_admissions AS a
                            JOIN rescue_patients AS p
                            ON p.patient_id = a.patient_id
                            JOIN
                            rescue_query as q ON q.centre_id = :centre_id
                            WHERE a.centre_id = :centre_id AND a.admission_date BETWEEN q.q_from AND q.q_to 
                            GROUP BY p.animal_species
                            ORDER BY p.animal_species");
    $stmt->bindParam(':centre_id', $centre_id, PDO::PARAM_INT);
    $species = array();
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $s_count = $row["speciescount"];
    $s_name = $row["animal_species"]; ?>
   
            <?php print '
                <strong>' . $s_name. ' - ' . $s_count . ' <BR>

                    ';
    } ?>
    </div>
    <div class="col-6">
        <H6 class="font-weight-bold text-primary"><U></u></h6>
           
    </div>
</div>
