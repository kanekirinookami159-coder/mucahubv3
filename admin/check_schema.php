<?php
include '../config/database.php';

echo "=== LOGIN_HISTORY TABLE ===\n";
$result = $conn->query('DESCRIBE login_history');
if($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "Table doesn't exist yet\n";
}

echo "\n=== STUDENTS TABLE ===\n";
$result = $conn->query('DESCRIBE students');
if($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "Table doesn't exist\n";
}

echo "\n=== ASSIGNMENT_SUBMISSIONS TABLE ===\n";
$result = $conn->query('DESCRIBE assignment_submissions');
if($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "Table doesn't exist\n";
}

echo "\n=== ASSIGNMENTS TABLE ===\n";
$result = $conn->query('DESCRIBE assignments');
if($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "Table doesn't exist\n";
}

$conn->close();
?>
