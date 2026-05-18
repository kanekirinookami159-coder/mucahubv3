<?php

$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "mucahub_db";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

?>