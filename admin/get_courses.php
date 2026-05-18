<?php
include '../includes/db.php'; // adjust if your connection file is different

$grade = $_GET['grade'] ?? '';

$data = [];

if ($grade != '') {
    $stmt = $conn->prepare("SELECT subject_name FROM courses WHERE grade_level = ?");
    $stmt->bind_param("s", $grade);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($data);
?>