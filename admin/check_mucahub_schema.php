<?php
$host = "127.0.0.1";
$user = "root";
$password = "";
$port = 3306;

$conn = mysqli_connect($host, $user, $password, "mucahub_db", $port);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "=== STUDENTS TABLE IN MUCAHUB_DB ===\n";
$result = $conn->query("DESCRIBE students");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
