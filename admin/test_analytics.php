<?php
include "../config/database.php";
include "../config/config.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Analytics Debug</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        button { padding: 8px 16px; background: #556b2f; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Analytics Debug Page</h1>
    
    <div class="section">
        <h2>Database Connection</h2>
        <p>Status: 
            <?php echo ($conn && !$conn->connect_error) ? '<span style="color:green">✓ Connected</span>' : '<span style="color:red">✗ Failed</span>'; ?>
        </p>
    </div>

    <div class="section">
        <h2>Test API Endpoints</h2>
        <button onclick="testEndpoint('student_enrollment')">Test Student Enrollment</button>
        <button onclick="testEndpoint('platform_usage')">Test Platform Usage</button>
        <button onclick="testEndpoint('assignment_submissions')">Test Assignment Submissions</button>
        <pre id="output">Click a button to test an endpoint...</pre>
    </div>

    <div class="section">
        <h2>Database Tables Check</h2>
        <pre id="tablesInfo"></pre>
    </div>

    <script>
        function testEndpoint(type) {
            const output = document.getElementById('output');
            output.textContent = 'Loading...';
            
            fetch('get_analytics_data.php?type=' + type)
                .then(r => r.json())
                .then(data => {
                    output.textContent = JSON.stringify(data, null, 2);
                })
                .catch(err => {
                    output.textContent = 'Error: ' + err.message;
                });
        }

        // Check tables on page load
        window.addEventListener('load', () => {
            const tablesInfo = document.getElementById('tablesInfo');
            fetch('get_analytics_data.php?type=check')
                .then(r => r.text())
                .then(text => {
                    // Just show a message since we're not getting table info yet
                    tablesInfo.textContent = 'Tables should be checked. Run a test above.';
                });
        });
    </script>
</body>
</html>
