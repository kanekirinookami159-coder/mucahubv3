<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/
require_once __DIR__ . "/../includes/databases/db_connection.php";

/*
|--------------------------------------------------------------------------
| FUNCTIONS
|--------------------------------------------------------------------------
*/
require_once __DIR__ . "/../includes/functions.php";

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/
checkLogin();
checkRole('instructor');

/*
|--------------------------------------------------------------------------
| SESSION VALUES
|--------------------------------------------------------------------------
*/
$instructorId = intval($_SESSION['user_id'] ?? 0);
$instructorName = 'Instructor';

/*
|--------------------------------------------------------------------------
| GET INSTRUCTOR INFO
|--------------------------------------------------------------------------
*/
$instructorSql = "
    SELECT
        first_name,
        middle_name,
        last_name,
        email,
        advisory_grade,
        advisory_section,
        subjects
    FROM instructors
    WHERE id = ?
    LIMIT 1
";

$instructor = [];

if ($stmt = $conn->prepare($instructorSql)) {

    $stmt->bind_param('i', $instructorId);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {

        $instructor = $result->fetch_assoc();

        $instructorName =
            trim(
                ($instructor['first_name'] ?? '') . ' ' .
                ($instructor['middle_name'] ?? '') . ' ' .
                ($instructor['last_name'] ?? '')
            );
    }

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| GET STUDENTS
|--------------------------------------------------------------------------
*/
$students = [];

$gradeLevel = $instructor['advisory_grade'] ?? '';
$section = $instructor['advisory_section'] ?? '';

if (!empty($gradeLevel) && !empty($section)) {

    $studentSql = "
        SELECT
            id,
            student_id,
            first_name,
            middle_name,
            last_name,
            grade_level,
            section,
            email
        FROM students
        WHERE grade_level = ?
        AND section = ?
        ORDER BY last_name ASC
    ";

    if ($stmt = $conn->prepare($studentSql)) {

        $stmt->bind_param(
            'ss',
            $gradeLevel,
            $section
        );

        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {

            $students[] = $row;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Instructor My Class</title>

<style>

body{
    margin:0;
    padding:0;
    background:#f4f6f9;
    font-family:Arial,sans-serif;
}

.wrapper{
    width:95%;
    max-width:1200px;
    margin:30px auto;
}

.card{
    background:#fff;
    border-radius:10px;
    padding:25px;
    margin-bottom:20px;
    box-shadow:0 2px 10px rgba(0,0,0,0.1);
}

h1,h2,h3{
    margin-top:0;
    color:#333;
}

.info{
    margin:8px 0;
    color:#555;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

table th{
    background:#007bff;
    color:#fff;
    padding:12px;
    text-align:left;
}

table td{
    padding:12px;
    border-bottom:1px solid #ddd;
}

table tr:hover{
    background:#f1f1f1;
}

.badge{
    background:#28a745;
    color:#fff;
    padding:4px 10px;
    border-radius:20px;
    font-size:12px;
}

.empty{
    padding:20px;
    background:#fff3cd;
    border:1px solid #ffeeba;
    border-radius:8px;
    color:#856404;
}

</style>

</head>

<body>

<div class="wrapper">

    <!-- INSTRUCTOR INFO -->

    <div class="card">

        <h1>
            Welcome,
            <?php echo htmlspecialchars($instructorName); ?>
        </h1>

        <div class="info">
            <strong>Email:</strong>
            <?php echo htmlspecialchars($instructor['email'] ?? 'N/A'); ?>
        </div>

        <div class="info">
            <strong>Advisory Grade:</strong>
            <?php echo htmlspecialchars($gradeLevel ?: 'N/A'); ?>
        </div>

        <div class="info">
            <strong>Section:</strong>
            <?php echo htmlspecialchars($section ?: 'N/A'); ?>
        </div>

        <div class="info">
            <strong>Subjects:</strong>
            <?php echo htmlspecialchars($instructor['subjects'] ?? 'N/A'); ?>
        </div>

    </div>

    <!-- STUDENT LIST -->

    <div class="card">

        <h2>My Students</h2>

        <?php if (!empty($students)): ?>

            <table>

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Grade</th>
                        <th>Section</th>
                        <th>Email</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($students as $index => $student): ?>

                        <tr>

                            <td>
                                <?php echo $index + 1; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student['student_id']); ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    trim(
                                        $student['first_name'] . ' ' .
                                        $student['middle_name'] . ' ' .
                                        $student['last_name']
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student['grade_level']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student['section']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($student['email']); ?>
                            </td>

                            <td>
                                <span class="badge">
                                    Active
                                </span>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="empty">
                No students found for your advisory class.
            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
