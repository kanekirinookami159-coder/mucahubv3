<?php
include "../includes/databases/db_connection.php";
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid instructor ID"]);
    exit;
}

$sql = "DELETE FROM instructors WHERE id = $id";
if (mysqli_query($conn, $sql)) {
    echo json_encode(["success" => true, "message" => "Instructor deleted successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Delete failed: " . mysqli_error($conn)]);
}
?>