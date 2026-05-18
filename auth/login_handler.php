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

$authenticated = false;
$user = null;
$role = '';

// Check instructors (includes potential admins)
$stmt = $conn->prepare("SELECT id, email, password, first_name, middle_name, last_name, employee_type FROM instructors WHERE email = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if ($row && !empty($row['password']) && password_verify($password, $row['password'])) {
        $authenticated = true;
        $user = $row;
        $employeeType = strtolower(trim($row['employee_type'] ?? ''));
        $role = ($employeeType === 'admin' || strtolower($row['email']) === 'admin@mucahub.com') ? 'admin' : 'instructor';
        $forcePasswordChange = isset($row['force_password_change']) && intval($row['force_password_change']) === 1;
    }
}

// Check students if instructor auth failed
if (!$authenticated) {
    $stmt = $conn->prepare("SELECT id, email, password, first_name, middle_name, last_name FROM students WHERE email = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($row && !empty($row['password']) && password_verify($password, $row['password'])) {
            $authenticated = true;
            $user = $row;
            $role = 'student';
        }
    }
}

if (!$authenticated || !$user) {
    header('Location: login.php?error=invalid');
    exit;
}

$code = strval(random_int(100000, 999999));
$_SESSION['pending_auth'] = [
    'user_id' => intval($user['id']),
    'role' => $role,
    'email' => $user['email'],
    'name' => trim(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
    'code' => $code,
    'expires' => time() + 300,
    'force_password_change' => $forcePasswordChange ?? false,
];

header('Location: verify_2fa.php');
exit;
