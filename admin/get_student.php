<?php
include "../includes/databases/db_connection.php";

$id = $_GET['id'];

$sql = "SELECT * FROM students WHERE id = $id";
$result = $conn->query($sql);
if (!$result) {
    echo json_encode(["error" => $conn->error]);
    exit;
}

$row = $result->fetch_assoc();
if (!$row) {
    echo json_encode(["error" => "Student not found"]);
    exit;
}

if (isset($row['password'])) {
    unset($row['password']);
}

// Debug: Log what we're returning
error_log("Student data returned: " . json_encode($row));

echo json_encode($row);
?>