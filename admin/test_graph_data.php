<?php
include '../config/database.php';

echo "=== TESTING ANALYTICS DATA ===\n\n";

// Test 1: Student Enrollment
echo "1. STUDENT ENROLLMENT\n";
$result = $conn->query("SELECT COUNT(*) as total FROM students");
$row = $result->fetch_assoc();
echo "   Total students: " . $row['total'] . "\n";
echo "   Expected on graph: Non-zero data\n\n";

// Test 2: Assignment Submissions
echo "2. ASSIGNMENT SUBMISSIONS\n";
$assignmentCheck = $conn->query("SHOW TABLES LIKE 'assignments'");
$submissionCheck = $conn->query("SHOW TABLES LIKE 'assignment_submissions'");

if ($assignmentCheck->num_rows > 0 && $submissionCheck->num_rows > 0) {
    $sql = "SELECT 
            CASE 
                WHEN s.submitted_at IS NOT NULL AND DATE(s.submitted_at) <= DATE(a.due_date) THEN 'Submitted'
                WHEN s.submitted_at IS NULL AND DATE(NOW()) > DATE(a.due_date) THEN 'Late'
                WHEN s.submitted_at IS NULL THEN 'Pending'
                ELSE 'Submitted'
            END as status,
            COUNT(*) as count
            FROM assignments a
            LEFT JOIN assignment_submissions s ON a.id = s.assignment_id
            GROUP BY status";
    
    $result = $conn->query($sql);
    
    if ($result) {
        $submitted = $pending = $late = 0;
        while ($row = $result->fetch_assoc()) {
            echo "   " . $row['status'] . ": " . $row['count'] . "\n";
            switch($row['status']) {
                case 'Submitted':
                    $submitted = $row['count'];
                    break;
                case 'Pending':
                    $pending = $row['count'];
                    break;
                case 'Late':
                    $late = $row['count'];
                    break;
            }
        }
        echo "   Total: " . ($submitted + $pending + $late) . "\n";
        echo "   Expected on graph: [Submitted: $submitted, Pending: $pending, Late: $late]\n";
    }
}

echo "\n3. PLATFORM USAGE\n";
$result = $conn->query("SELECT DATE(login_time) as day, COUNT(*) as logins 
                        FROM login_history 
                        WHERE login_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        GROUP BY DATE(login_time)
                        ORDER BY DATE(login_time)");

if ($result && $result->num_rows > 0) {
    $totalLogins = 0;
    echo "   Logins by day:\n";
    while ($row = $result->fetch_assoc()) {
        echo "   " . $row['day'] . ": " . $row['logins'] . " logins\n";
        $totalLogins += $row['logins'];
    }
    echo "   Total logins (last 7 days): $totalLogins\n";
}

$conn->close();
?>
