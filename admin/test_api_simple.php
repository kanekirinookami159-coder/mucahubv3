<?php
// Simple test to output API responses

echo "Testing API Endpoints\n";
echo "===================\n\n";

// Test Student Enrollment
echo "1. Student Enrollment:\n";
ob_start();
$_GET['type'] = 'student_enrollment';
include 'get_analytics_data.php';
$output = ob_get_clean();
echo $output . "\n\n";

// Test Platform Usage
echo "2. Platform Usage:\n";
ob_start();
$_GET['type'] = 'platform_usage';
include 'get_analytics_data.php';
$output = ob_get_clean();
echo $output . "\n\n";

// Test Assignment Submissions
echo "3. Assignment Submissions:\n";
ob_start();
$_GET['type'] = 'assignment_submissions';
include 'get_analytics_data.php';
$output = ob_get_clean();
echo $output . "\n";
?>
