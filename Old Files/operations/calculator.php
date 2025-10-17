<?php

// Check if the form has been submitted
if (isset($_POST['submit'])) {
  // Get the values from the form
  $w = $_POST['num1'];
  $p = $_POST['num2'];
  $dc = $_POST['num3'];
  $dv = $_POST['num4'];  

  $operator = $_POST['operator'];

  // Check which operator was chosen and perform the corresponding calculation
  if ($operator == 'volume') {
    $result = ($p / $dc) *$dv;
	$result2 = $w  *$dc;  
  } elseif ($operator == 'weight') {
    $result = $w * $dc;
  } elseif ($operator == 'both') {
    $result = $num1 * $num2;
  } elseif ($operator == 'divide') {
    $result = $num1 / $num2;
  } elseif ($operator == '') {
    $result = $num1 + $num2 + $num1;
  }
	
} ?>

<form action="" method="post">

  <input type="text" name="num1" placeholder="weight">
  <input type="text" name="num2" placeholder="prescribed dose (mg)">
  <input type="text" name="num3" placeholder="medication dose (mg)">
  <input type="text" name="num4" placeholder="medication volume (ml)">
   <input type="hidden" name="operator" value="volume">
  <input type="submit" name="submit" value="Calculate">
</form>

<?php // If the form has been submitted, display the result
if (isset($result)) {
  echo "Result: $result ml and $result2 mg in total (per weight)";
}
?>