<?php
include "../includes/databases/db_connection.php";

date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_students.php');
    exit;
}

function normalizeEmail($email) {
    $email = trim($email);
    if ($email === '') {
        return '';
    }
    if (strpos($email, '@') === false) {
        return strtolower($email . '@mucahub.com');
    }
    return strtolower($email);
}

function generateStudentId($conn) {
    $prefix = 'S-'.date('Y');
    do {
        $token = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $candidate = $prefix . '-' . $token;
        $stmt = $conn->prepare('SELECT id FROM students WHERE student_id = ? LIMIT 1');
        $stmt->bind_param('s', $candidate);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
    } while ($exists);
    return $candidate;
}

function generateLRN($conn) {
    // Generate a pseudo-LRN (12 digits) using two secure 6-digit segments and ensure uniqueness
    do {
        $part1 = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $part2 = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $candidate = $part1 . $part2;
        $stmt = $conn->prepare('SELECT id FROM students WHERE lrn = ? LIMIT 1');
        $stmt->bind_param('s', $candidate);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
    } while ($exists);
    return $candidate;
}

function ensureForcePasswordChangeColumnExists($conn) {
    $check = $conn->query("SHOW COLUMNS FROM students WHERE Field = 'force_password_change'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE students ADD COLUMN force_password_change TINYINT(1) NOT NULL DEFAULT 0 AFTER student_type");
    }
}

$studentId = trim($_POST['student_id'] ?? '');
$lrn = trim($_POST['lrn'] ?? '');
$email = normalizeEmail($_POST['email'] ?? '');
$passwordValue = trim($_POST['password'] ?? '');

if ($email !== '' && strpos($email, '@') === false) {
    $email = normalizeEmail($email);
}

ensureForcePasswordChangeColumnExists($conn);

if ($studentId === '') {
    $studentId = generateStudentId($conn);
}

if ($lrn === '') {
    $lrn = generateLRN($conn);
}

if ($passwordValue === '') {
    $passwordValue = $email !== '' ? $email : $studentId . '@mucahub.com';
}

// If email is empty, generate from student id
if ($email === '') {
    $email = strtolower($studentId) . '@mucahub.com';
}

$hashedPassword = password_hash($passwordValue, PASSWORD_DEFAULT);
$firstName = mysqli_real_escape_string($conn, $_POST['first_name'] ?? '');
$middleName = mysqli_real_escape_string($conn, $_POST['middle_name'] ?? '');
$lastName = mysqli_real_escape_string($conn, $_POST['last_name'] ?? '');

// Combine names into single name field for the database
$fullName = trim("$firstName $middleName $lastName");
if (empty($fullName)) {
    $fullName = $studentId ?? 'Student';
}

$dateOfBirth = mysqli_real_escape_string($conn, $_POST['date_of_birth'] ?? '');
$age = intval($_POST['age'] ?? 0);
$gender = mysqli_real_escape_string($conn, $_POST['gender'] ?? '');
$parentFirstName = mysqli_real_escape_string($conn, $_POST['parent_first_name'] ?? '');
$parentLastName = mysqli_real_escape_string($conn, $_POST['parent_last_name'] ?? '');
$relationship = mysqli_real_escape_string($conn, $_POST['relationship'] ?? '');
$contactNumber = mysqli_real_escape_string($conn, $_POST['contact_number'] ?? '');
$emergencyContact = mysqli_real_escape_string($conn, $_POST['emergency_contact'] ?? '');
$address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
$gradeLevel = mysqli_real_escape_string($conn, $_POST['grade_level'] ?? '');
$section = mysqli_real_escape_string($conn, $_POST['section'] ?? '');
$adviser = mysqli_real_escape_string($conn, $_POST['adviser'] ?? '');
$enrollmentStatus = mysqli_real_escape_string($conn, $_POST['enrollment_status'] ?? 'active');
$dateEnrolled = mysqli_real_escape_string($conn, $_POST['date_enrolled'] ?? date('Y-m-d'));
$studentType = mysqli_real_escape_string($conn, $_POST['student_type'] ?? '');

$sql = "INSERT INTO students (
    student_id,
    lrn,
    email,
    password,
    first_name,
    middle_name,
    last_name,
    date_of_birth,
    age,
    gender,
    parent_first_name,
    parent_last_name,
    relationship,
    contact_number,
    emergency_contact,
    address,
    grade_level,
    section,
    adviser,
    enrollment_status,
    date_enrolled,
    student_type,
    force_password_change,
    created_at
) VALUES (
    '$studentId',
    '$lrn',
    '$email',
    '$hashedPassword',
    '$firstName',
    '$middleName',
    '$lastName',
    '$dateOfBirth',
    $age,
    '$gender',
    '$parentFirstName',
    '$parentLastName',
    '$relationship',
    '$contactNumber',
    '$emergencyContact',
    '$address',
    '$gradeLevel',
    '$section',
    '$adviser',
    '$enrollmentStatus',
    '$dateEnrolled',
    '$studentType',
    1,
    NOW()
)";

if (mysqli_query($conn, $sql)) {
    header('Location: manage_students.php?success=1');
    exit;
}

$error = mysqli_error($conn);
echo "<h1>Error saving student</h1><p>$error</p>";
