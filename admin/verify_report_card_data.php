<?php
/**
 * Report Card Data Verification Script
 * Tests if grades are being saved and retrieved correctly
 */

// Initialize database connection
$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "mucahub_db";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'status' => 'Database connection failed',
        'error' => $conn->connect_error
    ]));
}

header('Content-Type: application/json');

try {
    // 1. Check if teacher_grade_records table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'teacher_grade_records'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        throw new Exception("Table 'teacher_grade_records' does not exist");
    }
    
    // 2. Check table structure
    $structureCheck = $conn->query("DESCRIBE teacher_grade_records");
    if (!$structureCheck) {
        throw new Exception("Could not describe table structure");
    }
    
    $columns = [];
    while ($row = $structureCheck->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    $requiredColumns = ['id', 'student_id', 'grade_level', 'section', 'subject_name', 'grading_period', 'grade_value'];
    $missingColumns = array_diff($requiredColumns, $columns);
    
    if (!empty($missingColumns)) {
        throw new Exception("Missing columns in table: " . implode(', ', $missingColumns));
    }
    
    // 3. Count records in table
    $countResult = $conn->query("SELECT COUNT(*) as total FROM teacher_grade_records");
    $countRow = $countResult->fetch_assoc();
    $totalRecords = $countRow['total'];
    
    // 4. Get sample data
    $sampleResult = $conn->query("
        SELECT DISTINCT 
            student_id, 
            grade_level, 
            section, 
            subject_name,
            COUNT(*) as grade_count
        FROM teacher_grade_records
        GROUP BY student_id, grade_level, section, subject_name
        LIMIT 5
    ");
    
    $samples = [];
    if ($sampleResult) {
        while ($row = $sampleResult->fetch_assoc()) {
            $samples[] = $row;
        }
    }
    
    // 5. Check for any data issues
    $nullGradesResult = $conn->query("SELECT COUNT(*) as null_count FROM teacher_grade_records WHERE grade_value IS NULL");
    $nullGradesRow = $nullGradesResult->fetch_assoc();
    $nullGradeCount = $nullGradesRow['null_count'];
    
    echo json_encode([
        'success' => true,
        'status' => 'Database verification successful',
        'table_exists' => true,
        'table_columns' => $columns,
        'required_columns_present' => true,
        'total_grade_records' => $totalRecords,
        'records_with_null_grades' => $nullGradeCount,
        'sample_data' => $samples,
        'message' => $totalRecords > 0 
            ? "✓ Grade records found in database. Report card feature should work." 
            : "⚠ No grade records found yet. Teachers need to save grades first."
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'Database verification failed',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

$conn->close();
?>
