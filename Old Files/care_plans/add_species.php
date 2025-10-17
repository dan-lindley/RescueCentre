<?php 
/*----------------------- FORM PROCESSING Add Species-------------------*/
/* FORM for users to add species to the database */
// Check form details submitted 

//Fields: Species Name, Scientific Name, Animal Type, Species Weight From, Species Weight To, Species weight unit, 
//Species Measurement From, Species Measurement to, Species Measurement unit, Reference, Species Measurement Standard, Iucn Status

if (isset($_POST['addSpeciesForm'])) {

	$sp_name = $_POST["species_name"];
  $sc_name = $_POST["scientific_name"];
  $sp_type = $_POST["animal_type"];
	$we_from = $_POST["weight_from"];
  $we_to = $_POST["weight_to"];
  $we_unit = $_POST["weight_unit"];
  $me_from = $_POST["measure_from"];
  $me_to = $_POST["measure_to"];
  $me_unit = $_POST["measure_unit"];
  $me_stand = $_POST["measure_standard"];
  $ref = $_POST["reference"];
  $iucn = $_POST["iucn_status"];

    try {
        $statement = $conn->prepare('INSERT INTO rescue_animal_species
            (species_name,
            scientific_name,
            animal_type,
            species_weight_from,
            species_weight_to,
            species_weight_unit,
            species_measurement_from, 
            species_measurement_to,
            species_measurement_unit,
            reference, 
            species_measurement_standard,    
            iucn_status)
            
            VALUES (:species_name,
            :scientific_name,
            :animal_type,
            :species_weight_from,
            :species_weight_to,
            :species_weight_unit,
            :species_measurement_from, 
            :species_measurement_to,
            :species_measurement_unit,
            :reference, 
            :species_measurement_standard,    
            :iucn_status)');

        $statement->execute([
          'species_name' => $sp_name,
          'scientific_name' => $sc_name,
          'animal_type' => $sp_type,
          'species_weight_from' => $we_from,
          'species_weight_to' => $we_to,
          'species_weight_unit' => $we_unit,
          'species_measurement_from' => $me_from,
          'species_measurement_to' => $me_to,
          'species_measurement_unit' => $me_unit,
          'reference' => $ref,
          'species_measurement_standard' => $me_stand,
          'iucn_status' => $iucn
        ]);
		
		  echo "<script>window.location = window.location</script>";
		
    } catch (PDOException $e) {
        echo "Database Error: The species could not be added.<br>" . $e->getMessage();
        exit();
    } catch (Exception $e) {
        echo "General Error: The species could not be added.<br>" . $e->getMessage();
        exit();
    }
}
/*------------ END FORM ----------------*/
?>

<div class="row lead_form_row">		
    <div class="col-md-3 my-auto">
    <form action="" method="post" class="lead_form" id="addSpeciesForm">
    <label for="species_name" class="form-label">Name of Species</label>
      <input type="text" id="species_name" name="species_name" placeholder="Species Name"></td>
    </div>
    <div class="col-md-2 my-auto">
    <label for="animal_type" class="form-label">Type of Animal</label>
      <select name="animal_type" id="animal_type" required class="js-example-responsive" style="width: 100%">  <option value="" disabled selected>Animal Type</option>
                <?php
                    //Find severity scores
                    $stmt = $conn->prepare("SELECT * 
                                            FROM rescue_animal_types
                                            ORDER BY type_name ASC");

                    // initialise an array for the results
                    $animaltype= array();
                    $stmt->execute();
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $type = $row["type_name"];
                    $order = $row["animal_order"];
                    
                    print '<option value="' . $type . '">' . $type . ' (' . $order . ')</option>';
                                            } ?>
            </select>
      </div>
      <div class="col-md-3 my-auto">
      <label for="scientific_name" class="form-label">Scientific Name</label>
      <input type="text" id="scientific_name" pattern="[a-z]" name="scientific_name" placeholder="Scientific or latin Name" >
      </div>
      <div class="col-md-2 my-auto">
        <label for="iucn_status" class="form-label">IUCN Status</label>
        <select name="iucn_status" id="iucn_status">
        <option value="">Unknown</option>
			    <option value="Data Deficient">Data Deficient</option>
			    <option value="Least Concern">Least Concern</option>
			    <option value="Near Threatened">Near Threatened</option>
          <option value="Vulnerable">Vulnerable</option>
          <option value="Critically Endangered">Crtically Endangered</option>
        </select>
      </div>
      <div class="col-md-2 my-auto">
      <label for="reference" class="form-label">Appropriate Reference</label>
      <input type="text" id="reference" name="reference" placeholder="Academic or suitable reference">
      </div>
    </div>

<div class="row lead_form_row">		
<div class="col-md-12 my-auto">
  For measurements and weights where it is not known, put 0
</div></div>
<div class="row lead_form_row">		
  <div class="col-md-2 my-auto">
    <label for="weight_from" class="form-label">Weight from</label>
    <input type="number" id="weight_from" name="weight_from" placeholder="Weight from" step=".01">
    
  </div>
  <div class="col-md-2 my-auto">
    <label for="weight_to" class="form-label">Weight to</label>
    <input type="number" id="weight_to" name="weight_to" placeholder="Weight to" step=".01">
  </div>
  <div class="col-md-1 my-auto">
  <label for="weight_unit" class="form-label">Unit</label>
      <select id="weight_unit" name="weight_unit">
        <option value="g">g (Grams)</option>
        <option value="kg">kg (Kilograms)</option>
      </select>
  </div>
  <div class="col-md-2 my-auto">
    <label for="measure_from" class="form-label">Measure from</label>
    <input type="number" id="measure_from" name="measure_from" placeholder="Measurement from" step=".01">
  </div>
  <div class="col-md-2 my-auto">
    <label for="measure_to" class="form-label">Measure to</label>
    <input type="number" id="measure_to" name="measure_to" placeholder="Measurement to" step=".01">
  </div>
  <div class="col-md-1 my-auto">
  <label for="measure_unit" class="form-label">Unit</label>
  <select id="measure_unit" name="measure_unit">
      <option value="mm">mm (Millimeters)</option>
      <option value="cm">cm (Centimeters)</option>
      <option value="m">m (Meters)</option>
      </select>
  </div> 
  <div class="col-md-2 my-auto">
  <label for="measure_standard" class="form-label">Standard</label>
  <select id="measure_standard" name="measure_standard">
      <option value="">None/not listed</option>
      <option value="head to toe">Head to Toe</option>
      <option value="forearm">Forearm</option>
      <option value="maximum flattened chord">Maximum flattened chord</option>
      </select>
  </div>                                          
</div>
<div class="row lead_form_row">		
<div class="col-md-2 my-auto">
<input type="submit" name="addSpeciesForm" value="Add a new species">
<form>
</div>
</div>

