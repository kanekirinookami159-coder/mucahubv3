<?php
include '../config/database.php';

echo "=== DATABASE DATA DIAGNOSTIC ===\n\n";

// Check Students
echo "1. STUDENTS TABLE:\n";
$result = $conn->query("SELECT COUNT(*) as total FROM students");
if ($result) {
    $row = $result->fetch_assoc();
    echo "   Total count: " . $row['total'] . "\n";
    
    $result = $conn->query("SELECT * FROM students LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo "   Sample records:\n";
        while ($row = $result->fetch_assoc()) {
            echo "   - ID: " . $row['id'] . ", Name: " . $row['name'] . ", Email: " . $row['email'] . "\n";
        }
    } else {
        echo "   No records found\n";
    }
} else {
    echo "   ERROR: " . $conn->error . "\n";
}

// Check Assignments
echo "\n2. ASSIGNMENTS TABLE:\n";
$result = $conn->query("SELECT COUNT(*) as total FROM assignments");
if ($result) {
    $row = $result->fetch_assoc();
    echo "   Total count: " . $row['total'] . "\n";
    
    $result = $conn->query("SELECT * FROM assignments LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo "   Sample records:\n";
        while ($row = $result->fetch_assoc()) {
            echo "   - ID: " . $row['id'] . ", Title: " . $row['title'] . ", Course: " . $row['course_id'] . "\n";
        }
    } else {
        echo "   No records found\n";
    }
} else {
    echo "   ERROR: " . $conn->error . "\n";
}

// Check Assignment Submissions
echo "\n3. ASSIGNMENT_SUBMISSIONS TABLE:\n";
$result = $conn->query("SELECT COUNT(*) as total FROM assignment_submissions");
if ($result) {
    $row = $result->fetch_assoc();
    echo "   Total count: " . $row['total'] . "\n";
    
    $result = $conn->query("SELECT * FROM assignment_submissions LIMIT 5");
    if ($result && $result->num_rows > 0) {
        echo "   Sample records:\n";
        while ($row = $result->fetch_assoc()) {
            echo "   - ID: " . $row['id'] . ", Student: " . $row['student_id'] . ", Status: " . $row['status'] . "\n";
        }
    } else {
        echo "   No records found\n";
    }
} else {
    echo "   ERROR: " . $conn->error . "\n";
}

// Check Login History (for reference)
echo "\n4. LOGIN_HISTORY TABLE:\n";
$result = $conn->query("SELECT COUNT(*) as total FROM login_history");
if ($result) {
    $row = $result->fetch_assoc();
    echo "   Total count: " . $row['total'] . " (This is working)\n";
}

$conn->close();
?>
