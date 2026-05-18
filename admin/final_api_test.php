<?php
// This simulates what the browser does - call the actual API endpoint

echo "<pre>";
echo "Testing Actual API Endpoints\n";
echo "=============================\n\n";

// Test each endpoint using file_get_contents (simulates HTTP GET)
$base_url = 'http://localhost/Mucahub/admin/';

$endpoints = [
    'student_enrollment' => 'Student Enrollment',
    'platform_usage' => 'Platform Usage', 
    'assignment_submissions' => 'Assignment Submissions'
];

foreach ($endpoints as $type => $label) {
    echo "Testing: $label\n";
    $url = $base_url . 'get_analytics_data.php?type=' . $type;
    
    $json = file_get_contents($url);
    $data = json_decode($json, true);
    
    echo "Raw JSON: " . substr($json, 0, 100) . "...\n";
    
    if (isset($data['error'])) {
        echo "ERROR: " . $data['error'] . "\n";
    } else {
        echo "Data array sample: ";
        if (is_array($data['data']) && count($data['data']) > 0) {
            echo "First value: " . var_export($data['data'][0], true) . " (";
            echo is_numeric($data['data'][0]) ? "NUMERIC ✓" : "NOT NUMERIC ✗";
            echo ")\n";
        }
    }
    echo "\n";
}

echo "</pre>";
?>
