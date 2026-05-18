<?php
include '../config/database.php';

echo "=== COURSES TABLE SCHEMA ===\n";
$result = $conn->query("DESCRIBE courses");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} else {
    echo "Courses table doesn't exist\n";
}

echo "\n=== COURSES TABLE DATA ===\n";
$result = $conn->query("SELECT * FROM courses LIMIT 5");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No courses found\n";
}

$conn->close();
?>
