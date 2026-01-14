<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['email'])){
    header("Location: index.php");
    exit();
}

if(isset($_GET['id'])){
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM users WHERE id = $id");
}

header("Location: admin_page.php");
exit();
?>
