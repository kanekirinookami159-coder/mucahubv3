<?php
include "../includes/databases/db_connection.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT * FROM instructors WHERE id = $id";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
if ($row && isset($row['password'])) {
    unset($row['password']);
}
echo json_encode($row);
?>