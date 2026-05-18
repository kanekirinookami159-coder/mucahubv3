<?php
include '../config/database.php';

echo "Testing Individual Endpoints\n";
echo "============================\n\n";

// Test 1: Student Enrollment
echo "1. Student Enrollment Test:\n";
$result = $conn->query("SELECT COUNT(*) as total FROM students");
$row = $result->fetch_assoc();
echo "   Total students: " . $row['total'] . "\n";
echo "   Expected: All zeros (no students)\n\n";

// Test 2: Platform Usage
echo "2. Platform Usage Test:\n";
$result = $conn->query("SELECT COUNT(*) as count FROM login_history");
$row = $result->fetch_assoc();
echo "   Login records: " . $row['count'] . "\n";

$result = $conn->query("SELECT DATE(login_time) as day, COUNT(*) as logins 
                        FROM login_history 
                        WHERE login_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        GROUP BY DATE(login_time)
                        ORDER BY DATE(login_time)");
echo "   Query successful: " . ($result ? "YES" : "NO") . "\n";
if ($result) {
    echo "   Logins by day:\n";
    while($row = $result->fetch_assoc()) {
        echo "     " . $row['day'] . ": " . $row['logins'] . " logins\n";
    }
}
echo "\n";

// Test 3: Assignment Submissions
echo "3. Assignment Submissions Test:\n";
$check1 = $conn->query("SHOW TABLES LIKE 'assignments'");
$check2 = $conn->query("SHOW TABLES LIKE 'assignment_submissions'");
echo "   Assignments table exists: " . ($check1->num_rows > 0 ? "YES" : "NO") . "\n";
echo "   Assignment_submissions table exists: " . ($check2->num_rows > 0 ? "YES" : "NO") . "\n";
echo "   Expected: All zeros (no assignments)\n";

$conn->close();
?>
