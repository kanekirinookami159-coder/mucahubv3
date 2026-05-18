<?php
include __DIR__ . "/../includes/databases/db_connection.php";

header('Content-Type: application/json');

$grade = $_GET['grade'] ?? '';
$section = $_GET['section'] ?? '';

$result = [];

if ($grade && $section) {

    // Get all subjects for selected grade
    $courses = mysqli_query($conn, "
        SELECT subject_name 
        FROM courses 
        WHERE grade_level = '$grade'
    ");

    while ($course = mysqli_fetch_assoc($courses)) {

        $subject = $course['subject_name'];
        $teacherName = "Not Assigned";
        $start = '';
        $end = '';
        $days = 'M T W Th F';

        // Get all instructors
        $instructors = mysqli_query($conn, "SELECT first_name, last_name, subjects FROM instructors");

        while ($inst = mysqli_fetch_assoc($instructors)) {

            $json = json_decode($inst['subjects'], true);

            // Check if JSON is valid
            if (!is_array($json)) {
                continue;
            }

            // STEP 1: Check Grade exists
            if (!isset($json[$grade])) {
                continue;
            }

            // STEP 2: Check Subject exists under that grade
            if (!isset($json[$grade][$subject])) {
                continue;
            }

            // STEP 3: Check Section exists inside subject
            if (isset($json[$grade][$subject]['sections'][$section])) {
                $sectionData = $json[$grade][$subject]['sections'][$section];
                $teacherName = $inst['first_name'] . " " . $inst['last_name'];
                $start = isset($sectionData['start']) ? $sectionData['start'] : '';
                $end = isset($sectionData['end']) ? $sectionData['end'] : '';
                $days = isset($sectionData['days']) && trim($sectionData['days']) !== '' ? $sectionData['days'] : 'M T W Th F';
                break;
            }
        }

        $result[] = [
            "subject" => $subject,
            "teacher" => $teacherName,
            "start" => $start,
            "end" => $end,
            "days" => $days
        ];
    }
}

echo json_encode($result);
?>