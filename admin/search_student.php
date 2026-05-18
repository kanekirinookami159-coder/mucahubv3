<?php
include "../includes/databases/db_connection.php";

$q = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

$hasEmail = false;
$check = $conn->query("SHOW COLUMNS FROM students LIKE 'email'");
if($check && $check->num_rows > 0){
    $hasEmail = true;
}

$fields = "id, student_id, first_name, last_name";
if($hasEmail){
    $fields .= ", email";
}

$sql = "SELECT $fields FROM students
        WHERE student_id LIKE '%$q%'
        OR last_name LIKE '%$q%'
        OR first_name LIKE '%$q%'";

if($hasEmail){
    $sql .= " OR email LIKE '%$q%'";
}

$result = $conn->query($sql);

$data = [];

while($row = $result->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);
?>