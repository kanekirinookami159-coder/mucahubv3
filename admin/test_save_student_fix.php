<?php
include '../includes/databases/db_connection.php';

echo "=== TESTING SAVE STUDENT IN MUCAHUB_DB ===\n\n";

// Simulate form submission
$_POST = [
    'student_id' => 'STU002',
    'lrn' => 'LRN002',
    'first_name' => 'Jane',
    'middle_name' => 'M',
    'last_name' => 'Doe',
    'email' => 'jane@example.com',
    'password' => 'password123',
    'date_of_birth' => '2008-03-20',
    'age' => 18,
    'gender' => 'Female',
    'parent_first_name' => 'Mary',
    'parent_last_name' => 'Doe',
    'relationship' => 'Mother',
    'contact_number' => '09234567890',
    'emergency_contact' => '09234567890',
    'address' => '123 Main St',
    'grade_level' => '10',
    'section' => 'A',
    'adviser' => 'Mr. Smith',
    'enrollment_status' => 'active',
    'date_enrolled' => '2026-05-14',
    'student_type' => 'Regular'
];

// Replicate the save_student.php logic
$studentId = mysqli_real_escape_string($conn, $_POST['student_id'] ?? '');
$lrn = mysqli_real_escape_string($conn, $_POST['lrn'] ?? '');
$email = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
$passwordValue = isset($_POST['password']) ? mysqli_real_escape_string($conn, $_POST['password']) : '';
$hashedPassword = $passwordValue !== '' ? password_hash($passwordValue, PASSWORD_DEFAULT) : '';
$firstName = mysqli_real_escape_string($conn, $_POST['first_name'] ?? '');
$middleName = mysqli_real_escape_string($conn, $_POST['middle_name'] ?? '');
$lastName = mysqli_real_escape_string($conn, $_POST['last_name'] ?? '');
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
    student_type
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
    '$studentType'
)";

echo "Testing SQL insert...\n";
if (mysqli_query($conn, $sql)) {
    echo "✓ SUCCESS! Student inserted into mucahub_db\n";
    
    // Verify
    $result = $conn->query("SELECT COUNT(*) as total FROM students");
    $row = $result->fetch_assoc();
    echo "Total students now: " . $row['total'] . "\n";
    
    $result = $conn->query("SELECT * FROM students WHERE student_id='$studentId'");
    if ($row = $result->fetch_assoc()) {
        echo "Student: " . $row['first_name'] . " " . $row['last_name'] . " (" . $row['email'] . ")\n";
    }
} else {
    echo "✗ ERROR: " . mysqli_error($conn) . "\n";
}

$conn->close();
?>
