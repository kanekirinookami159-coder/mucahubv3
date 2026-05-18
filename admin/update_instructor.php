<?php
include "../includes/databases/db_connection.php";
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid instructor ID"]);
    exit;
}

$data = $_POST;
unset($data['id']);

if (empty($data)) {
    echo json_encode(["success" => false, "message" => "No fields to update"]);
    exit;
}

// Field mapping from form names to database column names
$fieldMap = [
    'employeeNumber' => 'employee_number',
    'firstName' => 'first_name',
    'middleName' => 'middle_name',
    'lastName' => 'last_name',
    'contactNumber' => 'contact_number',
    'dateEmployment' => 'date_employment',
    'advisoryGrade' => 'advisory_grade',
    'advisorySection' => 'advisory_section',
    'employeeType' => 'employee_type',
    'subjects_json' => 'subjects',
    'email' => 'email',
    'dob' => 'dob',
    'age' => 'age',
    'address' => 'address',
];

// Build the SET clause dynamically
$set_clause = [];
$bind_params = [];
$bind_types = '';

foreach ($data as $key => $value) {
    if ($key === 'subjects') {
        continue; // Skip raw checkbox array data
    }
    
    // Map field names
    $db_column = isset($fieldMap[$key]) ? $fieldMap[$key] : $key;
    
    // Handle password specially
    if ($db_column === 'password' && $value !== '' && $value !== '********') {
        $value = password_hash($value, PASSWORD_BCRYPT);
    }
    
    // Skip empty passwords that are just placeholders
    if ($db_column === 'password' && ($value === '' || $value === '********')) {
        continue;
    }
    
    // Handle array data (like JSON)
    if (is_array($value)) {
        $value = json_encode($value);
    }
    
    $set_clause[] = "`$db_column` = ?";
    $bind_params[] = $value;
    $bind_types .= 's'; // All values as strings
}

if (empty($set_clause)) {
    echo json_encode(["success" => false, "message" => "No valid fields to update"]);
    exit;
}

// Add updated_at timestamp
$set_clause[] = "`last_modified` = NOW()";

$sql = "UPDATE instructors SET " . implode(", ", $set_clause) . " WHERE id = ?";
$bind_params[] = $id;
$bind_types .= 'i'; // ID is integer

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
    exit;
}

// Bind parameters dynamically
$stmt->bind_param($bind_types, ...$bind_params);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Instructor updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Update failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
