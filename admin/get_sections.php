<?php
include "../includes/db.php";

$grade = $_GET['grade'];

$stmt = $conn->prepare("SELECT subject_name FROM courses WHERE grade_level = ?");
$stmt->bind_param("s", $grade);
$stmt->execute();
$result = $stmt->get_result();

$courses = [];

while($row = $result->fetch_assoc()){
    $courses[] = $row['subject_name'];
}

echo json_encode($courses);
?>