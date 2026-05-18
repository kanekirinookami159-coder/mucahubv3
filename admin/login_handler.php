<?php
include "../config/config.php";
include "../includes/databases/db_connection.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($email === '' || $password === '') {
    header('Location: login.php?error=empty');
    exit;
}

// Query ONLY the admins table
$stmt = $conn->prepare("SELECT id, email, password, first_name, middle_name, last_name FROM admins WHERE email = ? LIMIT 1");

if (!$stmt) {
    header('Location: login.php?error=invalid');
    exit;
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Check if user exists and password matches
if (!$user || empty($user['password']) || !password_verify($password, $user['password'])) {
    header('Location: login.php?error=invalid');
    exit;
}

// User is authenticated as admin
session_regenerate_id(true);
$_SESSION['user_id'] = intval($user['id']);
$_SESSION['role'] = 'admin';
$_SESSION['email'] = $user['email'];
$_SESSION['name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

// Record login in login_history table
recordLogin($conn, $user['id'], $_SESSION['name'], 'admin');

header('Location: dashboard.php');
exit;

function recordLogin($conn, $user_id, $name, $role) {
    // Create table if it doesn't exist
    $createTableSQL = "CREATE TABLE IF NOT EXISTS login_history (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        role ENUM('student', 'instructor', 'admin') NOT NULL,
        employee_number VARCHAR(50),
        login_time DATETIME NOT NULL,
        logout_time DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $conn->query($createTableSQL);
    
    // Insert login record
    $loginTime = date('Y-m-d H:i:s');
    $insertSQL = "INSERT INTO login_history (user_id, name, role, login_time) 
                  VALUES ($user_id, '" . $conn->real_escape_string($name) . "', '$role', '$loginTime')";
    
    $conn->query($insertSQL);
}
?>
