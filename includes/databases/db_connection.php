<?php

$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "mucahub_db";
$port = 3306;

$conn = mysqli_connect($host, $user, $password, "", $port);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$dbCreated = mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
if (!$dbCreated) {
    die("Database creation failed: " . mysqli_error($conn));
}

if (!mysqli_select_db($conn, $database)) {
    die("Failed to select database: " . mysqli_error($conn));
}

?>