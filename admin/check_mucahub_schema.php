<?php
include '../includes/databases/db_connection.php';

echo "=== STUDENTS TABLE IN {$database} ===\n";
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
