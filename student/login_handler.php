<?php
session_start();
include "../config/config.php";
include "../includes/databases/db_connection.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit;
}

// Query the students table
$stmt = $conn->prepare("SELECT id, email, password, first_name, middle_name, last_name, student_id FROM students WHERE email = ? LIMIT 1");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    exit;
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Check if user exists and password matches
if (!$user || empty($user['password']) || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    exit;
}

// User is authenticated as student
session_regenerate_id(true);
$_SESSION['user_id'] = intval($user['id']);
$_SESSION['role'] = 'student';
$_SESSION['email'] = $user['email'];
$_SESSION['name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$_SESSION['student_id'] = $user['student_id'];

// Record login in login_history table
recordLogin($conn, $user['id'], $_SESSION['name'], 'student', $user['student_id']);

echo json_encode(['success' => true, 'redirect' => 'student_dashboard.php']);
exit;

function recordLogin($conn, $user_id, $name, $role, $employee_number = null) {
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
    $empNum = $employee_number ? "'" . $conn->real_escape_string($employee_number) . "'" : 'NULL';
    $insertSQL = "INSERT INTO login_history (user_id, name, role, employee_number, login_time) 
                  VALUES ($user_id, '" . $conn->real_escape_string($name) . "', '$role', $empNum, '$loginTime')";
    
    $conn->query($insertSQL);
}
?>
