<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "coconut_diseases_db";

$conn_disease = new mysqli($host, $user, $password, $database);

if ($conn_disease->connect_error) {
    die("Connection failed: " . $conn_disease->connect_error);
}
?>
