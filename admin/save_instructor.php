<?php
include "../includes/databases/db_connection.php";

/* ✅ FIX TIMEZONE (IMPORTANT FOR WRONG TIME ISSUE) */
date_default_timezone_set('Asia/Manila');

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

function generateInstructorId($conn) {
    $prefix = 'T-'.date('Y');
    do {
        $token = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $candidate = $prefix . '-' . $token;
        $stmt = $conn->prepare('SELECT id FROM instructors WHERE employee_number = ? LIMIT 1');
        $stmt->bind_param('s', $candidate);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
    } while ($exists);
    return $candidate;
}

function ensureForcePasswordChangeColumnExists($conn) {
    $check = $conn->query("SHOW COLUMNS FROM instructors WHERE Field = 'force_password_change'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE instructors ADD COLUMN force_password_change TINYINT(1) NOT NULL DEFAULT 0 AFTER subjects");
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST"){

/* BASIC INFO */
$employeeNumber = trim($_POST['employeeNumber'] ?? '');
$firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
$middleName = mysqli_real_escape_string($conn, $_POST['middleName']);
$lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
$dob = mysqli_real_escape_string($conn, $_POST['dob']);
$age = intval($_POST['age']);
$address = mysqli_real_escape_string($conn, $_POST['address']);
$email = normalizeEmail($_POST['email'] ?? '');
$passwordValue = trim($_POST['password'] ?? '');
$contactNumber = mysqli_real_escape_string($conn, $_POST['contactNumber']);
$dateEmployment = mysqli_real_escape_string($conn, $_POST['dateEmployment']);
$employeeType = mysqli_real_escape_string($conn, $_POST['employeeType']);

ensureForcePasswordChangeColumnExists($conn);

if ($employeeNumber === '') {
    $employeeNumber = generateInstructorId($conn);
}

// If email is empty, use employee number as email local-part
if ($email === '') {
    $email = strtolower($employeeNumber) . '@mucahub.com';
}

if ($passwordValue === '') {
    $passwordValue = $email !== '' ? $email : $employeeNumber . '@mucahub.com';
}

$hashedPassword = password_hash($passwordValue, PASSWORD_DEFAULT);

/* ADVISORY */
$advisoryGrade = $_POST['advisoryGrade'];
$advisorySection = $_POST['advisorySection'];

/* SUBJECTS JSON */
$subjects = isset($_POST['subjects_json']) ? $_POST['subjects_json'] : '{}';

/* INSERT QUERY */
$sql = "INSERT INTO instructors (
employee_number,
first_name,
middle_name,
last_name,
dob,
age,
address,
email,
password,
contact_number,
date_employment,
employee_type,
advisory_grade,
advisory_section,
subjects,
force_password_change,
created_at
) VALUES (
'$employeeNumber',
'$firstName',
'$middleName',
'$lastName',
'$dob',
'$age',
'$address',
'$email',
'$hashedPassword',
'$contactNumber',
'$dateEmployment',
'$employeeType',
'$advisoryGrade',
'$advisorySection',
'$subjects',
1,
NOW()
)";

/* EXECUTE */
if(mysqli_query($conn,$sql)){

/* ✅ REMINDER + REDIRECT ON OK */
echo "<script>
if (confirm('✅ Instructor successfully saved! Click OK to return.')) {
    window.location.href = 'manage_instructors.php';
}
</script>";

}else{

echo "Error: " . mysqli_error($conn);

}

}
?>