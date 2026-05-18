<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/databases/db_connection.php';

$userRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
$userId = intval($_SESSION['user_id'] ?? 0);
if ($userRole !== 'student' || $userId <= 0) {
    header('Location: ../auth/login.php');
    exit;
}

$lessonId = intval($_GET['lesson_id'] ?? 0);
if ($lessonId <= 0) {
    header('Location: my_courses.php');
    exit;
}

$studentGrade = '';
$studentSection = '';
$studentSql = "SELECT grade_level, section FROM students WHERE id = ? LIMIT 1";
if ($stmt = $conn->prepare($studentSql)) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($studentGrade, $studentSection);
    $stmt->fetch();
    $stmt->close();
}

if ($studentGrade === '' || $studentSection === '') {
    header('Location: my_courses.php');
    exit;
}

$lessonSql = "SELECT file_path FROM teacher_lessons WHERE id = ? AND grade_level = ? AND section = ? LIMIT 1";
$filePath = '';
if ($stmt = $conn->prepare($lessonSql)) {
    $stmt->bind_param('iss', $lessonId, $studentGrade, $studentSection);
    $stmt->execute();
    $stmt->bind_result($filePath);
    $stmt->fetch();
    $stmt->close();
}

if ($filePath) {
    $accessSql = "INSERT INTO teacher_lesson_access (lesson_id, student_id, accessed_at) VALUES (?, ?, NOW())";
    if ($stmt = $conn->prepare($accessSql)) {
        $stmt->bind_param('ii', $lessonId, $userId);
        $stmt->execute();
        $stmt->close();
    }

    if (preg_match('#^https?://#i', trim($filePath))) {
        header('Location: ' . trim($filePath));
        exit;
    }

    $filePath = trim($filePath);
    if (strpos($filePath, '../') !== 0 && strpos($filePath, '/') !== 0) {
        $filePath = '../' . ltrim($filePath, '/');
    }
    header('Location: ' . $filePath);
    exit;
}

header('Location: my_courses.php');
exit;
