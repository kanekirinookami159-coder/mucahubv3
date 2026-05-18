<?php
include "../includes/databases/db_connection.php";
header('Content-Type: application/json');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid course ID"]);
    exit;
}

if (mysqli_query($conn, "DELETE FROM courses WHERE id=$id")) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
}
?>