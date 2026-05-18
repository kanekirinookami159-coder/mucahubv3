<?php
include '../config/database.php';

echo "=== CHECKING INSTRUCTORS AND COURSES ===\n\n";

// Check for existing instructors
$result = $conn->query("SELECT id FROM instructors LIMIT 1");
$instructorId = null;

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $instructorId = $row['id'];
    echo "Using existing instructor ID: $instructorId\n";
} else {
    // Create a default instructor
    $sql = "INSERT INTO instructors (name, email, password, status) VALUES ('Admin Instructor', 'admin@instructor.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'active')";
    if (mysqli_query($conn, $sql)) {
        $instructorId = $conn->insert_id;
        echo "✓ Created new instructor with ID: $instructorId\n";
    } else {
        echo "✗ Error creating instructor: " . mysqli_error($conn) . "\n";
        $conn->close();
        exit;
    }
}

// Check for existing courses
$result = $conn->query("SELECT id FROM courses LIMIT 1");
$courseId = null;

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $courseId = $row['id'];
    echo "Using existing course ID: $courseId\n";
} else {
    // Create a default course
    $sql = "INSERT INTO courses (code, name, description, instructor_id, status) VALUES ('GEN-101', 'General', 'General course', $instructorId, 'active')";
    if (mysqli_query($conn, $sql)) {
        $courseId = $conn->insert_id;
        echo "✓ Created new course with ID: $courseId\n";
    } else {
        echo "✗ Error creating course: " . mysqli_error($conn) . "\n";
        $conn->close();
        exit;
    }
}

echo "\n=== INSERTING ASSIGNMENT ===\n\n";

// Now insert the assignment
$title = "Math Homework Chapter 5";
$description = "Solve problems 1-20 from the textbook";
$dueDate = date('Y-m-d H:i:s', strtotime('+1 week'));
$maxScore = 100;

$sql = "INSERT INTO assignments(course_id, title, description, due_date, max_score, status, created_at) 
        VALUES($courseId, '$title', '$description', '$dueDate', $maxScore, 'open', NOW())";

echo "SQL: $sql\n\n";

if (mysqli_query($conn, $sql)) {
    echo "✓ SUCCESS! Assignment inserted.\n";
    
    // Verify
    $result = $conn->query("SELECT COUNT(*) as total FROM assignments");
    $row = $result->fetch_assoc();
    echo "Total assignments now: " . $row['total'] . "\n";
    
    $result = $conn->query("SELECT * FROM assignments ORDER BY id DESC LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        echo "Latest assignment: " . $row['title'] . " (Due: " . $row['due_date'] . ")\n";
    }
} else {
    echo "✗ ERROR: " . mysqli_error($conn) . "\n";
}

$conn->close();
?>
