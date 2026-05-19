<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/databases/db_connection.php';

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/
$userRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
$userId = intval($_SESSION['user_id'] ?? 0);

if ($userRole !== 'student' || $userId <= 0) {
    header('Location: ../auth/login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| GET LESSON ID
|--------------------------------------------------------------------------
*/
$lessonId = intval($_GET['lesson_id'] ?? 0);

if ($lessonId <= 0) {
    header('Location: my_courses.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| GET STUDENT GRADE + SECTION
|--------------------------------------------------------------------------
*/
$studentGrade = '';
$studentSection = '';

$studentSql = "
    SELECT grade_level, section
    FROM students
    WHERE id = ?
    LIMIT 1
";

if ($stmt = $conn->prepare($studentSql)) {

    $stmt->bind_param('i', $userId);

    $stmt->execute();

    $stmt->bind_result(
        $studentGrade,
        $studentSection
    );

    $stmt->fetch();

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| VALIDATE STUDENT
|--------------------------------------------------------------------------
*/
if (empty($studentGrade) || empty($studentSection)) {

    header('Location: my_courses.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| GET LESSON FILE
|--------------------------------------------------------------------------
*/
$filePath = '';

$lessonSql = "
    SELECT file_path
    FROM teacher_lessons
    WHERE id = ?
    AND grade_level = ?
    AND section = ?
    LIMIT 1
";

if ($stmt = $conn->prepare($lessonSql)) {

    $stmt->bind_param(
        'iss',
        $lessonId,
        $studentGrade,
        $studentSection
    );

    $stmt->execute();

    $stmt->bind_result($filePath);

    $stmt->fetch();

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| FILE FOUND
|--------------------------------------------------------------------------
*/
if (!empty($filePath)) {

    /*
    |--------------------------------------------------------------------------
    | OPTIONAL ACCESS LOGGING
    |--------------------------------------------------------------------------
    | Removed because Railway DB columns do not match.
    | You can re-add later after checking your table structure.
    |--------------------------------------------------------------------------
    */

    // Example:
    // INSERT INTO teacher_lesson_access ...

    /*
    |--------------------------------------------------------------------------
    | EXTERNAL URL
    |--------------------------------------------------------------------------
    */
    if (preg_match('#^https?://#i', trim($filePath))) {

        header('Location: ' . trim($filePath));
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | LOCAL FILE PATH
    |--------------------------------------------------------------------------
    */
    $filePath = trim($filePath);

    if (
        strpos($filePath, '../') !== 0 &&
        strpos($filePath, '/') !== 0
    ) {
        $filePath = '../' . ltrim($filePath, '/');
    }

    /*
    |--------------------------------------------------------------------------
    | REDIRECT TO FILE
    |--------------------------------------------------------------------------
    */
    header('Location: ' . $filePath);
    exit;
}

/*
|--------------------------------------------------------------------------
| FALLBACK
|--------------------------------------------------------------------------
*/
header('Location: my_courses.php');
exit;
?>
