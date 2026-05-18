<?php
include '../config/database.php';

// Directly test each API endpoint function

echo "=== COMPREHENSIVE API TEST ===\n\n";

// Test 1: Student Enrollment
echo "1. Testing getStudentEnrollment():\n";
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM students");
    $row = $result->fetch_assoc();
    $totalStudents = $row['total'];
    
    $enrollmentMonths = ['Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May'];
    $enrollmentData = [0, 0, 0, 0, 0, 0];
    
    $output = json_encode([
        'labels' => $enrollmentMonths,
        'data' => $enrollmentData,
        'total' => $totalStudents
    ]);
    echo "   Output: " . $output . "\n";
    echo "   ✓ Success\n";
} catch(Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 2: Platform Usage
echo "\n2. Testing getPlatformUsage():\n";
try {
    $sql = "SELECT DATE(login_time) as day, COUNT(*) as logins 
            FROM login_history 
            WHERE login_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(login_time)
            ORDER BY DATE(login_time)";
    
    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $usageData = array_fill(0, 7, 0);
    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    
    $tempData = [];
    while($row = $result->fetch_assoc()) {
        $dayOfWeek = date('w', strtotime($row['day']));
        $dayOfWeek = ($dayOfWeek == 0) ? 6 : $dayOfWeek - 1;
        $tempData[$dayOfWeek] = $row['logins'];
    }
    
    for($i = 0; $i < 7; $i++) {
        if(isset($tempData[$i])) {
            $usageData[$i] = $tempData[$i];
        }
    }
    
    $output = json_encode([
        'labels' => $days,
        'data' => $usageData
    ]);
    echo "   Output: " . $output . "\n";
    echo "   ✓ Success\n";
} catch(Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 3: Assignment Submissions
echo "\n3. Testing getAssignmentSubmissions():\n";
try {
    $assignmentCheck = $conn->query("SHOW TABLES LIKE 'assignments'");
    $submissionCheck = $conn->query("SHOW TABLES LIKE 'assignment_submissions'");
    
    $submitted = 0;
    $pending = 0;
    $late = 0;
    
    if($assignmentCheck && $assignmentCheck->num_rows > 0 && $submissionCheck && $submissionCheck->num_rows > 0) {
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
        
        if(!$result) {
            throw new Exception("Query failed: " . $conn->error);
        }
        
        while($row = $result->fetch_assoc()) {
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
    }
    
    $output = json_encode([
        'labels' => ['Submitted', 'Pending', 'Late'],
        'data' => [$submitted, $pending, $late]
    ]);
    echo "   Output: " . $output . "\n";
    echo "   ✓ Success\n";
} catch(Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== ALL TESTS COMPLETE ===\n";
$conn->close();
?>
