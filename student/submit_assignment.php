<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/databases/db_connection.php';

function respondJson(array $payload, int $statusCode = 200): void
{
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

$userRole = $_SESSION['role'] ?? '';
$userId = intval($_SESSION['user_id'] ?? 0);
$isJsonRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isJsonRequest) {
    $isJsonRequest = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
}

if ($userRole !== 'student' || $userId <= 0) {
    if ($isJsonRequest) {
        respondJson(['success' => false, 'message' => 'Unauthorized access.'], 401);
    }
    header('Location: ../auth/login.php');
    exit;
}

$assignmentId = intval($_POST['assignment_id'] ?? 0);
$subject = trim($_POST['subject'] ?? '');

if ($assignmentId <= 0 || !isset($_FILES['submission_file'])) {
    if ($isJsonRequest) {
        respondJson(['success' => false, 'message' => 'No assignment or file provided.'], 400);
    }
    header('Location: my_courses.php' . ($subject ? '?subject=' . urlencode($subject) : ''));
    exit;
}

$uploadedFiles = [];
if (is_array($_FILES['submission_file']['name'])) {
    foreach ($_FILES['submission_file']['name'] as $index => $name) {
        $uploadedFiles[] = [
            'name' => $name,
            'type' => $_FILES['submission_file']['type'][$index] ?? '',
            'tmp_name' => $_FILES['submission_file']['tmp_name'][$index] ?? '',
            'error' => $_FILES['submission_file']['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $_FILES['submission_file']['size'][$index] ?? 0,
        ];
    }
} else {
    $uploadedFiles[] = $_FILES['submission_file'];
}

$validFiles = array_filter($uploadedFiles, function ($file) {
    return isset($file['tmp_name']) && $file['error'] === UPLOAD_ERR_OK && !empty($file['tmp_name']);
});

if (empty($validFiles)) {
    if ($isJsonRequest) {
        respondJson(['success' => false, 'message' => 'Failed to upload the file(s).'], 400);
    }
    header('Location: my_courses.php' . ($subject ? '?subject=' . urlencode($subject) : ''));
    exit;
}

$allowedDir = __DIR__ . '/../assets/uploads/submissions/';
if (!is_dir($allowedDir)) {
    mkdir($allowedDir, 0755, true);
}

$relativePaths = [];
foreach ($validFiles as $uploadedFile) {
    $originalName = basename($uploadedFile['name']);
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    $timestamp = time() . rand(1000, 9999);
    $newFileName = sprintf('submission_%d_student_%d_%s_%s.%s', $assignmentId, $userId, $safeName, $timestamp, $extension ?: 'dat');
    $targetPath = $allowedDir . $newFileName;

    if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
        if ($isJsonRequest) {
            respondJson(['success' => false, 'message' => 'Unable to save uploaded file.'], 500);
        }
        header('Location: my_courses.php' . ($subject ? '?subject=' . urlencode($subject) : ''));
        exit;
    }

    $relativePaths[] = 'assets/uploads/submissions/' . $newFileName;
}

$submittedAt = date('Y-m-d H:i:s');
$existingId = null;
$checkSql = "SELECT id, submission_file_path FROM teacher_assignment_submissions WHERE assignment_id = ? AND student_id = ? LIMIT 1";
if ($stmt = $conn->prepare($checkSql)) {
    $stmt->bind_param('ii', $assignmentId, $userId);
    $stmt->execute();
    $stmt->bind_result($existingId, $existingPath);
    $stmt->fetch();
    $stmt->close();
}

$allPaths = $relativePaths;
if (!empty($existingPath)) {
    $existingPaths = json_decode($existingPath, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($existingPaths)) {
        $allPaths = array_merge($existingPaths, $relativePaths);
    } elseif (trim($existingPath) !== '') {
        $allPaths = array_merge([$existingPath], $relativePaths);
    }
}

$submissionPathValue = json_encode(array_values(array_filter($allPaths)));

if (!empty($existingId)) {
    $updateSql = "UPDATE teacher_assignment_submissions SET status = 'submitted', submission_file_path = ?, submitted_at = NOW() WHERE id = ? LIMIT 1";
    if ($stmt = $conn->prepare($updateSql)) {
        $stmt->bind_param('si', $submissionPathValue, $existingId);
        $stmt->execute();
        $stmt->close();
    }
} else {
    $insertSql = "INSERT INTO teacher_assignment_submissions (assignment_id, student_id, status, submission_file_path, submitted_at) VALUES (?, ?, 'submitted', ?, NOW())";
    if ($stmt = $conn->prepare($insertSql)) {
        $stmt->bind_param('iis', $assignmentId, $userId, $submissionPathValue);
        $stmt->execute();
        $stmt->close();
    }
}

if ($isJsonRequest) {
    respondJson([
        'success' => true,
        'assignment_id' => $assignmentId,
        'submission_file_path' => $allPaths[0] ?? '',
        'submission_file_paths' => $allPaths,
        'submitted_at' => $submittedAt,
    ]);
}

header('Location: my_courses.php' . ($subject ? '?subject=' . urlencode($subject) : ''));
exit;
