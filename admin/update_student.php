<?php
include "../includes/databases/db_connection.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid student ID"]);
    exit;
}

$updates = [];

// Handle individual name fields (mucahub_db uses first_name, last_name, etc.)
if (isset($_POST['first_name'])) {
    $updates['first_name'] = mysqli_real_escape_string($conn, $_POST['first_name']);
}
if (isset($_POST['middle_name'])) {
    $updates['middle_name'] = mysqli_real_escape_string($conn, $_POST['middle_name']);
}
if (isset($_POST['last_name'])) {
    $updates['last_name'] = mysqli_real_escape_string($conn, $_POST['last_name']);
}

// Handle email
if (isset($_POST['email'])) {
    $updates['email'] = mysqli_real_escape_string($conn, $_POST['email']);
}

// Handle contact_number
if (isset($_POST['contact_number'])) {
    $updates['contact_number'] = mysqli_real_escape_string($conn, $_POST['contact_number']);
}

// Handle date_of_birth
if (isset($_POST['date_of_birth'])) {
    $updates['date_of_birth'] = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
}

// Handle gender
if (isset($_POST['gender'])) {
    $updates['gender'] = mysqli_real_escape_string($conn, $_POST['gender']);
}

// Handle password
if (isset($_POST['password']) && !empty($_POST['password']) && $_POST['password'] !== '********') {
    $updates['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
}

// Handle enrollment_status
if (isset($_POST['enrollment_status'])) {
    $updates['enrollment_status'] = mysqli_real_escape_string($conn, $_POST['enrollment_status']);
}

// Handle address
if (isset($_POST['address'])) {
    $updates['address'] = mysqli_real_escape_string($conn, $_POST['address']);
}

// Handle emergency_contact
if (isset($_POST['emergency_contact'])) {
    $updates['emergency_contact'] = mysqli_real_escape_string($conn, $_POST['emergency_contact']);
}

// Handle age
if (isset($_POST['age'])) {
    $updates['age'] = intval($_POST['age']);
}

if (empty($updates)) {
    echo json_encode(["success" => false, "message" => "No fields to update"]);
    exit;
}

// Build UPDATE query
$set_parts = [];
foreach ($updates as $column => $value) {
    if (is_numeric($value) && $column !== 'password') {
        $set_parts[] = "`$column` = $value";
    } else {
        $set_parts[] = "`$column` = '" . $value . "'";
    }
}
$set_clause = implode(", ", $set_parts);

$sql = "UPDATE students SET $set_clause, last_modified = NOW() WHERE id = $id";

if (mysqli_query($conn, $sql)) {
    echo json_encode(["success" => true, "message" => "Student updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Error: " . mysqli_error($conn)]);
}

$conn->close();
?>
