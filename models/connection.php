<?php
  try {
    $conn = new PDO('mysql:host=localhost;dbname=gslottoa_JBsystem;charset=utf8', 'gslottoa_Para', 'z0)CGc%odWl6');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch(PDOException $e) {
      echo 'ERROR: ' . $e->getMessage();
  }
?>
