<?php
include __DIR__ . '/../includes/databases/db_connection.php';

$grade = $_GET['grade'] ?? '';
$section = $_GET['section'] ?? '';

$adviser = '';

if ($grade != '' && $section != '') {

    $stmt = $conn->prepare("
        SELECT first_name, last_name 
        FROM instructors 
        WHERE advisory_grade = ? 
        AND advisory_section = ? 
        LIMIT 1
    ");

    $stmt->bind_param("ss", $grade, $section);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $adviser = $row['first_name'] . ' ' . $row['last_name'];
    }
}

echo $adviser;
?>