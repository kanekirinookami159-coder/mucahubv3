<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate the three API calls the dashboard makes
$base_url = 'http://localhost/Mucahub/admin/get_analytics_data.php';

echo "<html><body style='font-family: Arial; padding: 20px;'>";
echo "<h1>Dashboard API Test</h1>";

// Test each endpoint
$endpoints = [
    'student_enrollment' => 'Student Enrollment',
    'platform_usage' => 'Platform Usage',
    'assignment_submissions' => 'Assignment Submissions'
];

foreach ($endpoints as $type => $label) {
    echo "<h2>$label</h2>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";
    
    $url = $base_url . '?type=' . $type;
    $json = file_get_contents($url);
    $data = json_decode($json, true);
    
    echo htmlspecialchars($json);
    echo "</pre>";
    
    if (isset($data['error'])) {
        echo "<span style='color: red;'><strong>ERROR:</strong> " . htmlspecialchars($data['error']) . "</span>";
    } else {
        echo "<span style='color: green;'><strong>✓ OK</strong></span>";
    }
    echo "<hr>";
}

echo "</body></html>";
?>
