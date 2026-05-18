<?php
session_start();
include "../config/config.php";
include "../includes/databases/db_connection.php";

header('Content-Type: application/json');

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$admin_id = $_SESSION['user_id'];

// Get admin profile from database
$stmt = $conn->prepare("SELECT id, email, first_name, middle_name, last_name FROM admins WHERE id = ? LIMIT 1");

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}

$stmt->bind_param('i', $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if (!$admin) {
    echo json_encode(["success" => false, "message" => "Admin not found"]);
    exit;
}

echo json_encode([
    "success" => true,
    "email" => $admin['email'],
    "first_name" => $admin['first_name'],
    "middle_name" => $admin['middle_name'],
    "last_name" => $admin['last_name']
]);
?>
