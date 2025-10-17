<?php
/* Connect to MySQL */
    $servername = "localhost";
    $username = "USERNAME";
    $password = "PASSWORD";
    $dbPort   = 3306;
    $dbCharset = 'utf8mb4';
    
    try {
      $conn = new PDO("mysql:host=$servername;dbname=[[[dbNAME]]]", $username, $password);
      // set the PDO error mode to exception
      $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
      echo "Connection failed: " . $e->getMessage();
    }
    $dsn = "mysql:host={$servername};port={$dbPort};dbname={$username};charset={$dbCharset}";
?>