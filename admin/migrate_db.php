<?php
include "../config/config.php";
include "../includes/databases/db_connection.php";

date_default_timezone_set('Asia/Manila');

echo "=== MUCAHUB DATABASE MIGRATION ===\n\n";

// Check and add created_at to students table
echo "Checking students table...\n";
$studentsCheck = $conn->query("SHOW COLUMNS FROM students WHERE Field = 'created_at'");
if ($studentsCheck->num_rows == 0) {
    echo "  - Adding created_at column to students table...\n";
    $conn->query("ALTER TABLE students ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "  ✓ created_at added to students table\n";
} else {
    echo "  ✓ created_at already exists in students table\n";
}

// Check and add force_password_change to students table
$studentsForceCheck = $conn->query("SHOW COLUMNS FROM students WHERE Field = 'force_password_change'");
if ($studentsForceCheck->num_rows == 0) {
    echo "  - Adding force_password_change column to students table...\n";
    $conn->query("ALTER TABLE students ADD COLUMN force_password_change TINYINT(1) NOT NULL DEFAULT 0 AFTER student_type");
    echo "  ✓ force_password_change added to students table\n";
} else {
    echo "  ✓ force_password_change already exists in students table\n";
}

// Check and add created_at to instructors table
echo "\nChecking instructors table...\n";
$instructorsCheck = $conn->query("SHOW COLUMNS FROM instructors WHERE Field = 'created_at'");
if ($instructorsCheck->num_rows == 0) {
    echo "  - Adding created_at column to instructors table...\n";
    $conn->query("ALTER TABLE instructors ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "  ✓ created_at added to instructors table\n";
} else {
    echo "  ✓ created_at already exists in instructors table\n";
}

// Check and add force_password_change to instructors table
$instructorsForceCheck = $conn->query("SHOW COLUMNS FROM instructors WHERE Field = 'force_password_change'");
if ($instructorsForceCheck->num_rows == 0) {
    echo "  - Adding force_password_change column to instructors table...\n";
    $conn->query("ALTER TABLE instructors ADD COLUMN force_password_change TINYINT(1) NOT NULL DEFAULT 0 AFTER subjects");
    echo "  ✓ force_password_change added to instructors table\n";
} else {
    echo "  ✓ force_password_change already exists in instructors table\n";
}

// Check and add created_at to courses table
echo "\nChecking courses table...\n";
$coursesCheck = $conn->query("SHOW COLUMNS FROM courses WHERE Field = 'created_at'");
if ($coursesCheck->num_rows == 0) {
    echo "  - Adding created_at column to courses table...\n";
    $conn->query("ALTER TABLE courses ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "  ✓ created_at added to courses table\n";
} else {
    echo "  ✓ created_at already exists in courses table\n";
}

echo "\n✓ Migration completed successfully!\n";
?>
