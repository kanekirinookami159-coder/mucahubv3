<?php
include __DIR__ . "/../includes/databases/db_connection.php";
header('Content-Type: application/json');

$teachers = [];
$query = "SELECT id, employee_number, first_name, last_name, email FROM instructors ORDER BY last_name, first_name";
$result = mysqli_query($conn, $query);
if($result){
    while($row = mysqli_fetch_assoc($result)){
        $teachers[] = $row;
    }
}

echo json_encode($teachers);
