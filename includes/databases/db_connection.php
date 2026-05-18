<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

function env_var($key, $default = null) {
    $value = getenv($key);

    if ($value !== false && $value !== '') {
        return $value;
    }

    return $default;
}

$host = env_var('MYSQLHOST');
$user = env_var('MYSQLUSER');
$password = env_var('MYSQLPASSWORD');
$database = env_var('MYSQLDATABASE');
$port = env_var('MYSQLPORT', 3306);

if (!$host || !$user || !$database) {
    die("Missing Railway MySQL environment variables.");
}

if (!function_exists('mysqli_connect')) {
    die("mysqli extension is not enabled.");
}

$conn = mysqli_connect($host, $user, $password, $database, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>
