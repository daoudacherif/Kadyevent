<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbName = "event agency";

try {
  $conn = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  
} catch(PDOException $e) {
  #echo "Connection failed: " . $e->getMessage();
  include './under-construction.php';
  die();
}
?>