<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "messages";

// Create connection
$conn_messages = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn_messages->connect_error) {
    die("Connection failed: " . $conn_messages->connect_error);
}
?>
