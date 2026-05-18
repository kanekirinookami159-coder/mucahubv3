<?php
session_start();
include "../config/config.php";
include "../includes/databases/db_connection.php";

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// Fetch analytics data
$totalStudents = 0;
$totalInstructors = 0;
$totalCourses = 0;
$totalAssignments = 0;
$totalSubmissions = 0;

// Get counts
$result = $conn->query("SELECT COUNT(*) as count FROM students WHERE enrollment_status='active'");
if ($result) {
    $row = $result->fetch_assoc();
    $totalStudents = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM instructors WHERE employee_type != 'admin'");
if ($result) {
    $row = $result->fetch_assoc();
    $totalInstructors = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM courses");
if ($result) {
    $row = $result->fetch_assoc();
    $totalCourses = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM teacher_assignments");
if ($result) {
    $row = $result->fetch_assoc();
    $totalAssignments = $row['count'];
}

$result = $conn->query("SELECT COUNT(*) as count FROM teacher_assignment_submissions");
if ($result) {
    $row = $result->fetch_assoc();
    $totalSubmissions = $row['count'];
}

// Enrollment by grade
$enrollmentByGrade = [];
$result = $conn->query("SELECT grade_level, COUNT(*) as count FROM students WHERE enrollment_status='active' GROUP BY grade_level ORDER BY grade_level");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $enrollmentByGrade[] = ['label' => $row['grade_level'], 'count' => (int)$row['count']];
    }
}

// Course statistics
$courseStats = [];
$result = $conn->query("SELECT c.course_code, c.course_name, COUNT(DISTINCT ta.id) as assignments FROM courses c LEFT JOIN teacher_assignments ta ON c.id = ta.course_id GROUP BY c.id ORDER BY assignments DESC LIMIT 10");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courseStats[] = ['name' => $row['course_name'], 'assignments' => (int)$row['assignments']];
    }
}

// Submission rates
$submissionStats = [];
$result = $conn->query("SELECT 
    (SELECT COUNT(*) FROM teacher_assignment_submissions WHERE status='submitted') as submitted,
    (SELECT COUNT(*) FROM teacher_assignment_submissions WHERE status='pending') as pending,
    (SELECT COUNT(*) FROM teacher_assignment_submissions WHERE status='late') as late");
if ($result) {
    $row = $result->fetch_assoc();
    $submissionStats = $row;
}

// Teacher activity
$teacherActivity = [];
$result = $conn->query("SELECT i.first_name, i.last_name, COUNT(ta.id) as assignments_created FROM instructors i LEFT JOIN teacher_assignments ta ON i.id = ta.instructor_id WHERE i.employee_type != 'admin' GROUP BY i.id ORDER BY assignments_created DESC LIMIT 10");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teacherActivity[] = ['name' => $row['first_name'] . ' ' . $row['last_name'], 'count' => (int)$row['assignments_created']];
    }
}

// Login activity (last 7 days)
$loginActivity = [];
$result = $conn->query("SELECT DATE(login_time) as date, COUNT(*) as count FROM login_history WHERE login_time >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(login_time) ORDER BY date DESC LIMIT 7");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $loginActivity[] = ['date' => $row['date'], 'count' => (int)$row['count']];
    }
}
$loginActivity = array_reverse($loginActivity);
?>
<!DOCTYPE html>
<html>
<head>
    <title>MUCAHUB Analytics</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; gap:20px; flex-wrap:wrap; }
        .analytics-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:28px; }
        .stat-card { background:white; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1); border-left:4px solid #556b2f; }
        .stat-card h4 { color:#666; font-size:13px; margin-bottom:8px; text-transform:uppercase; }
        .stat-card .number { font-size:32px; font-weight:bold; color:#556b2f; }
        .export-btn { background:#556b2f; color:white; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; }
        .analytics-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(400px, 1fr)); gap:24px; }
        .analytics-card { background:white; padding:24px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
        .analytics-card h3 { color:#333; margin-bottom:16px; font-size:18px; }
        .analytics-card canvas { height:320px; }
        @media(max-width:1024px) { .analytics-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<?php include "../includes/sidebar_admin.php"; ?>
<div class="main">
    <div class="analytics-header">
        <h2>System Analytics Dashboard</h2>
        <button class="export-btn" onclick="window.print()"><i class="fas fa-download"></i> Print Report</button>
    </div>

    <div class="analytics-stats">
        <div class="stat-card">
            <h4>Total Students</h4>
            <div class="number"><?= $totalStudents ?></div>
        </div>
        <div class="stat-card">
            <h4>Total Instructors</h4>
            <div class="number"><?= $totalInstructors ?></div>
        </div>
        <div class="stat-card">
            <h4>Active Courses</h4>
            <div class="number"><?= $totalCourses ?></div>
        </div>
        <div class="stat-card">
            <h4>Total Assignments</h4>
            <div class="number"><?= $totalAssignments ?></div>
        </div>
        <div class="stat-card">
            <h4>Submissions</h4>
            <div class="number"><?= $totalSubmissions ?></div>
        </div>
    </div>

    <div class="analytics-grid">
        <div class="analytics-card">
            <h3>Student Enrollment by Grade Level</h3>
            <canvas id="enrollmentChart"></canvas>
        </div>

        <div class="analytics-card">
            <h3>Assignment Submission Status</h3>
            <canvas id="submissionChart"></canvas>
        </div>

        <div class="analytics-card">
            <h3>Top Courses by Assignments</h3>
            <canvas id="courseChart"></canvas>
        </div>

        <div class="analytics-card">
            <h3>Teacher Activity</h3>
            <canvas id="teacherChart"></canvas>
        </div>

        <div class="analytics-card">
            <h3>System Login Activity (Last 7 Days)</h3>
            <canvas id="loginChart"></canvas>
        </div>
    </div>
</div>

<?php include "../includes/float.php"; ?>
<?php include "../includes/back_to_top.php"; ?>
<?php include "../includes/footer.php"; ?>

<script>
const chartOptions = {
    responsive: true,
    maintainAspectRatio: true,
    animation: { duration: 1500, easing: 'easeInOutQuart' }
};

// Enrollment by grade
new Chart(document.getElementById('enrollmentChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($x) => $x['label'], $enrollmentByGrade)) ?>,
        datasets: [{
            label: 'Student Count',
            data: <?= json_encode(array_map(fn($x) => $x['count'], $enrollmentByGrade)) ?>,
            backgroundColor: '#556b2f',
            borderColor: '#3b5323',
            borderWidth: 1
        }]
    },
    options: chartOptions
});

// Submission status
new Chart(document.getElementById('submissionChart'), {
    type: 'doughnut',
    data: {
        labels: ['Submitted', 'Pending', 'Late'],
        datasets: [{
            data: [<?= $submissionStats['submitted'] ?>, <?= $submissionStats['pending'] ?>, <?= $submissionStats['late'] ?>],
            backgroundColor: ['#556b2f', '#8fbc8f', '#dbe7c9']
        }]
    },
    options: chartOptions
});

// Top courses
new Chart(document.getElementById('courseChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($x) => $x['name'], $courseStats)) ?>,
        datasets: [{
            label: 'Assignment Count',
            data: <?= json_encode(array_map(fn($x) => $x['assignments'], $courseStats)) ?>,
            backgroundColor: '#6b8e23'
        }]
    },
    options: chartOptions
});

// Teacher activity
new Chart(document.getElementById('teacherChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_map(fn($x) => $x['name'], $teacherActivity)) ?>,
        datasets: [{
            label: 'Assignments Created',
            data: <?= json_encode(array_map(fn($x) => $x['count'], $teacherActivity)) ?>,
            backgroundColor: '#78a74b'
        }]
    },
    options: { ...chartOptions, scales: { y: { beginAtZero: true } } }
});

// Login activity
new Chart(document.getElementById('loginChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($x) => $x['date'], $loginActivity)) ?>,
        datasets: [{
            label: 'Login Count',
            data: <?= json_encode(array_map(fn($x) => $x['count'], $loginActivity)) ?>,
            borderColor: '#556b2f',
            backgroundColor: 'rgba(85, 107, 47, 0.1)',
            fill: true,
            tension: 0.4,
            borderWidth: 2
        }]
    },
    options: chartOptions
});
</script>
</body>
</html>
