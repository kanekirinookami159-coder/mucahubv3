<?php
require_once __DIR__ . '/../includes/databases/db_connection.php';
header('Content-Type: application/json');

$subjects = [];

// If a subject_catalog table exists, use it. Otherwise return a sensible default catalog.
$check = $conn->query("SHOW TABLES LIKE 'subject_catalog'");
if ($check && $check->num_rows > 0) {
    $res = $conn->query("SELECT name FROM subject_catalog ORDER BY name ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $subjects[] = $row['name'];
        }
    }
} else {
    // Default LMS subjects
    $subjects = [
        'English', 'Mathematics', 'Science', 'Filipino', 'Araling Panlipunan',
        'Computer Science', 'Physical Education', 'Music', 'Arts', 'Health',
        'Values Education', 'Practical Arts', 'Business Studies', 'Economics', 'Biology',
        'Chemistry', 'Physics', 'Algebra', 'Geometry', 'Statistics'
    ];
}

echo json_encode(array_values(array_unique($subjects)));

$conn->close();

?>
