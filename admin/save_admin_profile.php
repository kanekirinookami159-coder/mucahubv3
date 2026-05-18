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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit;
}

$admin_id = $_SESSION['user_id'];
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$first_name = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
$middle_name = isset($_POST['middleName']) ? trim($_POST['middleName']) : '';
$last_name = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$password_confirm = isset($_POST['passwordConfirm']) ? trim($_POST['passwordConfirm']) : '';

// Validate inputs
if (empty($email) || empty($first_name) || empty($last_name)) {
    echo json_encode(["success" => false, "message" => "Email, first name, and last name are required"]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit;
}

// Validate password if provided
if ($password || $password_confirm) {
    if ($password !== $password_confirm) {
        echo json_encode(["success" => false, "message" => "Passwords do not match"]);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(["success" => false, "message" => "Password must be at least 6 characters long"]);
        exit;
    }
}

// Check if email is already taken by another admin
$checkStmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ? LIMIT 1");
if (!$checkStmt) {
    echo json_encode(["success" => false, "message" => "Database error"]);
    exit;
}

$checkStmt->bind_param('si', $email, $admin_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    $checkStmt->close();
    echo json_encode(["success" => false, "message" => "Email is already in use by another admin"]);
    exit;
}

$checkStmt->close();

// Update admin profile
if ($password) {
    // Hash the new password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $updateStmt = $conn->prepare("UPDATE admins SET email = ?, first_name = ?, middle_name = ?, last_name = ?, password = ? WHERE id = ?");
    
    if (!$updateStmt) {
        echo json_encode(["success" => false, "message" => "Database error"]);
        exit;
    }
    
    $updateStmt->bind_param('sssssi', $email, $first_name, $middle_name, $last_name, $hashed_password, $admin_id);
} else {
    // Update without password
    $updateStmt = $conn->prepare("UPDATE admins SET email = ?, first_name = ?, middle_name = ?, last_name = ? WHERE id = ?");
    
    if (!$updateStmt) {
        echo json_encode(["success" => false, "message" => "Database error"]);
        exit;
    }
    
    $updateStmt->bind_param('ssssi', $email, $first_name, $middle_name, $last_name, $admin_id);
}

if ($updateStmt->execute()) {
    $updateStmt->close();
    
    // Update session name
    $_SESSION['name'] = trim($first_name . ' ' . $middle_name . ' ' . $last_name);
    $_SESSION['email'] = $email;
    
    echo json_encode(["success" => true, "message" => "Profile updated successfully"]);
} else {
    $updateStmt->close();
    echo json_encode(["success" => false, "message" => "Failed to update profile"]);
}
?>
