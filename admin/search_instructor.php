<?php
include "../includes/databases/db_connection.php";

$q = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

$sql = "SELECT id, employee_number, first_name, last_name FROM instructors
        WHERE employee_number LIKE '%$q%'
        OR first_name LIKE '%$q%'
        OR last_name LIKE '%$q%'";

$result = $conn->query($sql);
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>