<?php 
include_once "connect_to_mysql.php";
//GENERATE PASSCODE - using a table with 3 columns randomly select a row, then use one of the words in the 3 columns to select a single
// passphrase to be stored with an admission 

$stmt = $conn->prepare("SELECT * FROM rescue_words ORDER BY RAND() LIMIT 1");
                        
    // initialise an array for the results
    $randwords = array();
    $stmt->execute();
      while ($row = $stmt->fetch()) {
      $wd1 = $row["word_1"];
      $wd2 = $row["word_2"];
      $wd3 = $row["word_3"];
    
      }
?>