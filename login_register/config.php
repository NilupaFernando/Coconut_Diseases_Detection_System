<?php
// MySQL connection for users DB
$host = "localhost";
$user = "root";
$password = "";
$database = "users_db";

$conn = new mysqli($host, $user, $password, $database);

if($conn->connect_error){
    die("Connection Failed: " . $conn->connect_error);
}

// ✅ OpenAI API Key
$openai_api_key = getenv("OPENAI_API_KEY");
?>
