<?php
include '../config/database.php';

echo "=== TESTING STUDENT INSERT ===\n\n";

// Simulate a student form submission
$_POST = [
    'student_id' => 'STU001',
    'first_name' => 'John',
    'middle_name' => 'Q',
    'last_name' => 'Student',
    'email' => 'john@example.com',
    'password' => 'password123',
    'contact_number' => '09123456789',
    'date_of_birth' => '2008-05-15',
    'enrollment_status' => 'active'
];

// Replicate the save_student logic
$firstName = $_POST['first_name'];
$middleName = $_POST['middle_name'];
$lastName = $_POST['last_name'];
$fullName = trim("$firstName $middleName $lastName");
$email = $_POST['email'];
$hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
$contactNumber = $_POST['contact_number'];
$dateOfBirth = $_POST['date_of_birth'];
$enrollmentStatus = $_POST['enrollment_status'];

$sql = "INSERT INTO students (
    name,
    email,
    password,
    phone,
    dob,
    status,
    created_at
) VALUES (
    '$fullName',
    '$email',
    '$hashedPassword',
    '$contactNumber',
    '$dateOfBirth',
    '$enrollmentStatus',
    NOW()
)";

echo "SQL: $sql\n\n";

if (mysqli_query($conn, $sql)) {
    echo "✓ SUCCESS! Student inserted.\n";
    
    // Verify
    $result = $conn->query("SELECT COUNT(*) as total FROM students");
    $row = $result->fetch_assoc();
    echo "Total students now: " . $row['total'] . "\n";
    
    $result = $conn->query("SELECT * FROM students ORDER BY id DESC LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        echo "Latest student: " . $row['name'] . " (" . $row['email'] . ")\n";
    }
} else {
    echo "✗ ERROR: " . mysqli_error($conn) . "\n";
}

$conn->close();
?>
