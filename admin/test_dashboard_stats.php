<?php
include '../includes/databases/db_connection.php';

echo "=== DASHBOARD STATISTICS TEST ===\n\n";

$totalCourses = 0;
$totalStudents = 0;
$totalInstructors = 0;
$totalAnnouncements = 0;

// Total Courses
if ($result = $conn->query("SELECT COUNT(*) AS total FROM courses")) {
    $row = $result->fetch_assoc();
    $totalCourses = intval($row['total'] ?? 0);
    echo "✓ Total Courses: $totalCourses\n";
} else {
    echo "✗ Error getting courses: " . $conn->error . "\n";
}

// Total Students
if ($result = $conn->query("SELECT COUNT(*) AS total FROM students")) {
    $row = $result->fetch_assoc();
    $totalStudents = intval($row['total'] ?? 0);
    echo "✓ Total Students: $totalStudents\n";
} else {
    echo "✗ Error getting students: " . $conn->error . "\n";
}

// Total Instructors
if ($result = $conn->query("SELECT COUNT(*) AS total FROM instructors")) {
    $row = $result->fetch_assoc();
    $totalInstructors = intval($row['total'] ?? 0);
    echo "✓ Total Instructors: $totalInstructors\n";
} else {
    echo "✗ Error getting instructors: " . $conn->error . "\n";
}

// Total Announcements
if ($result = $conn->query("SELECT COUNT(*) AS total FROM announcements")) {
    $row = $result->fetch_assoc();
    $totalAnnouncements = intval($row['total'] ?? 0);
    echo "✓ Total Announcements: $totalAnnouncements\n";
} else {
    echo "✗ Error getting announcements: " . $conn->error . "\n";
}

echo "\n=== EXPECTED DASHBOARD DISPLAY ===\n";
echo "Students: $totalStudents\n";
echo "Courses: $totalCourses\n";
echo "Instructors: $totalInstructors\n";
echo "Announcements: $totalAnnouncements\n";

$conn->close();
?>
