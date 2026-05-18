<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/databases/db_connection.php';

$userRole = $_SESSION['role'] ?? '';
$userId = intval($_SESSION['user_id'] ?? 0);
if ($userRole !== 'student' || $userId <= 0) {
    header('Location: ../auth/login.php');
    exit;
}

function parseSubmissionFilePaths($path) {
    if (!$path) {
        return [];
    }
    $decoded = json_decode($path, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return array_values(array_filter(array_map('trim', $decoded)));
    }
    if (strpos($path, '|') !== false) {
        return array_values(array_filter(array_map('trim', explode('|', $path))));
    }
    return [trim($path)];
}

$studentName = 'Student';
$gradeLevel = '';
$section = '';
$firstName = $middleName = $lastName = '';
$studentInfoSql = "SELECT first_name, middle_name, last_name, grade_level, section FROM students WHERE id = ? LIMIT 1";
if ($stmt = $conn->prepare($studentInfoSql)) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($firstName, $middleName, $lastName, $gradeLevel, $section);
    if ($stmt->fetch()) {
        $nameParts = array_filter([$firstName, $middleName, $lastName]);
        $studentName = implode(' ', $nameParts);
        $gradeLevel = $conn->real_escape_string($gradeLevel);
        $section = $conn->real_escape_string($section);
    }
    $stmt->close();
}

$latestAnnouncement = null;
$announcementQuery = "SELECT id, title, description, created_at FROM announcements WHERE target IN ('student','all') ORDER BY created_at DESC LIMIT 1";
if ($announcementResult = $conn->query($announcementQuery)) {
    $latestAnnouncement = $announcementResult->fetch_assoc();
}

$classStudents = [];

// Get dashboard notifications
$userRole = $_SESSION['role'] ?? '';
$userId = intval($_SESSION['user_id'] ?? 0);
$dashboardAssignmentEvents = [];
$dashboardNotificationEvents = [];
include __DIR__ . '/../includes/dashboard_notifications.php';
$studentListQuery = "SELECT id, first_name, middle_name, last_name, student_id FROM students WHERE grade_level = ? AND section = ? ORDER BY last_name, first_name";
if ($stmt = $conn->prepare($studentListQuery)) {
    $stmt->bind_param('ss', $gradeLevel, $section);
    $stmt->execute();
    $studentResult = $stmt->get_result();
    while ($studentRow = $studentResult->fetch_assoc()) {
        $fullName = trim(trim($studentRow['first_name'] . ' ' . $studentRow['middle_name']) . ' ' . $studentRow['last_name']);
        $classStudents[] = [
            'id' => intval($studentRow['id']),
            'student_number' => $studentRow['student_id'] ?? '',
            'name' => trim($fullName),
        ];
    }
    $stmt->close();
}

$subjects = [];
$subjectQuery = "SELECT subject_name, instructor_name, grading_period, COUNT(*) AS items, SUM(assignment_type = 'activity') AS activities, SUM(assignment_type = 'quiz') AS quizzes, MAX(created_at) AS last_update FROM teacher_assignments WHERE grade_level = ? AND section = ? GROUP BY subject_name, instructor_name, grading_period";
if ($stmt = $conn->prepare($subjectQuery)) {
    $stmt->bind_param('ss', $gradeLevel, $section);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $name = $row['subject_name'];
        if (!isset($subjects[$name])) {
            $subjects[$name] = [
                'subject_name' => $name,
                'instructor_name' => $row['instructor_name'] ?: 'Teacher',
                'grading_period' => $row['grading_period'] ?: 'All',
                'lesson_count' => 0,
                'activity_count' => (int)$row['activities'],
                'quiz_count' => (int)$row['quizzes'],
                'latest_update' => $row['last_update'] ?: null,
                'assignment_count' => (int)$row['items'],
            ];
        } else {
            $subjects[$name]['activity_count'] += (int)$row['activities'];
            $subjects[$name]['quiz_count'] += (int)$row['quizzes'];
            $subjects[$name]['assignment_count'] += (int)$row['items'];
            if ($row['last_update'] && (!$subjects[$name]['latest_update'] || $row['last_update'] > $subjects[$name]['latest_update'])) {
                $subjects[$name]['latest_update'] = $row['last_update'];
            }
        }
    }
    $stmt->close();
}

$lessonQuery = "SELECT subject_name, instructor_name, grading_period, COUNT(*) AS lessons, MAX(created_at) AS last_update FROM teacher_lessons WHERE grade_level = ? AND section = ? GROUP BY subject_name, instructor_name, grading_period";
if ($stmt = $conn->prepare($lessonQuery)) {
    $stmt->bind_param('ss', $gradeLevel, $section);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $name = $row['subject_name'];
        if (!isset($subjects[$name])) {
            $subjects[$name] = [
                'subject_name' => $name,
                'instructor_name' => $row['instructor_name'] ?: 'Teacher',
                'grading_period' => $row['grading_period'] ?: 'All',
                'lesson_count' => (int)$row['lessons'],
                'activity_count' => 0,
                'quiz_count' => 0,
                'latest_update' => $row['last_update'] ?: null,
                'assignment_count' => 0,
            ];
        } else {
            $subjects[$name]['lesson_count'] += (int)$row['lessons'];
            if ($row['last_update'] && (!$subjects[$name]['latest_update'] || $row['last_update'] > $subjects[$name]['latest_update'])) {
                $subjects[$name]['latest_update'] = $row['last_update'];
            }
        }
    }
    $stmt->close();
}

if (empty($subjects)) {
    $subjects = [];
}

uksort($subjects, function ($a, $b) {
    return strcasecmp($a, $b);
});

$selectedSubject = $_GET['subject'] ?? array_key_first($subjects) ?? '';
if ($selectedSubject && !isset($subjects[$selectedSubject])) {
    $selectedSubject = array_key_first($subjects) ?? '';
}

function safeText($text) {
    return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
}

function browserPath($path) {
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^(https?:|/|\\\\)#i', $path)) {
        return $path;
    }
    if (strpos($path, '../') === 0) {
        return $path;
    }
    return '../' . ltrim($path, '/');
}

function weekLabel($date) {
    if (!$date) {
        return 'Other';
    }
    $timestamp = strtotime($date);
    if (!$timestamp) {
        return 'Other';
    }
    $monday = strtotime('monday this week', $timestamp);
    if (date('N', $timestamp) === '1') {
        $monday = $timestamp;
    }
    return 'Week of ' . date('F j, Y', $monday);
}

$subjectLessons = [];
$subjectAssignments = [];

foreach ($subjects as $subjectName => $subjectData) {
    $lessons = [];
    $assignmentItems = [];

    $lessonDetailQuery = "SELECT id, title, description, file_name, file_path, created_at FROM teacher_lessons WHERE grade_level = ? AND section = ? AND subject_name = ? ORDER BY created_at DESC";
    if ($stmt = $conn->prepare($lessonDetailQuery)) {
        $stmt->bind_param('sss', $gradeLevel, $section, $subjectName);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $lessons[] = $row;
        }
        $stmt->close();
    }

    $assignmentDetailQuery = "SELECT a.id, a.title, a.description, a.assignment_type, a.open_date, a.open_time, a.due_date, a.due_time, a.assignment_file_name, a.assignment_file_path, a.max_points, COALESCE(tas.status, 'not submitted') AS status, tas.submitted_at, tas.submission_file_path, gr.grade_value, gr.graded_at FROM teacher_assignments a LEFT JOIN teacher_assignment_submissions tas ON tas.assignment_id = a.id AND tas.student_id = ? LEFT JOIN (SELECT tgr.assignment_id, tgr.grade_value, tgr.recorded_at AS graded_at FROM teacher_grade_records tgr JOIN (SELECT assignment_id, MAX(recorded_at) AS max_recorded_at FROM teacher_grade_records WHERE student_id = ? GROUP BY assignment_id) latest_grades ON latest_grades.assignment_id = tgr.assignment_id AND latest_grades.max_recorded_at = tgr.recorded_at WHERE tgr.student_id = ?) gr ON gr.assignment_id = a.id WHERE a.grade_level = ? AND a.section = ? AND a.subject_name = ? ORDER BY a.open_date DESC, a.open_time DESC, a.due_date ASC, a.due_time ASC";
    if ($stmt = $conn->prepare($assignmentDetailQuery)) {
        $stmt->bind_param('iiisss', $userId, $userId, $userId, $gradeLevel, $section, $subjectName);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $assignmentItems[] = $row;
        }
        $stmt->close();
    }

    $subjectLessons[$subjectName] = $lessons;
    $subjectAssignments[$subjectName] = $assignmentItems;
}

$pendingActivities = [];
foreach ($subjectAssignments as $subjectName => $assignments) {
    foreach ($assignments as $assignment) {
        $status = $assignment['status'] ?? 'not submitted';
        $dueDate = trim($assignment['due_date'] ?? '');
        if ($dueDate === '') {
            continue;
        }
        $dueTime = trim($assignment['due_time'] ?: '23:59:59');
        $dueTimestamp = strtotime($dueDate . ' ' . $dueTime);
        if ($dueTimestamp === false) {
            continue;
        }
        if ($status !== 'submitted') {
            $pendingActivities[] = [
                'assignment_id' => intval($assignment['id']),
                'title' => $assignment['title'] ?? 'Untitled',
                'subject_name' => $subjectName,
                'due_date' => $dueDate,
                'due_time' => $dueTime,
                'due_timestamp' => $dueTimestamp,
                'status' => $status,
                'assignment_type' => $assignment['assignment_type'] ?? 'activity',
            ];
        }
    }
}

usort($pendingActivities, function ($a, $b) {
    return $a['due_timestamp'] <=> $b['due_timestamp'];
});
$pendingActivities = array_slice($pendingActivities, 0, 5);
?>

<!DOCTYPE html>
<html>

<head>
    <title>My Courses</title>
    <link rel="stylesheet" href="../assets/css/student_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .course-page-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-end;
            margin-bottom: 18px;
        }
        .course-summary {
            color: #334155;
            font-size: 1rem;
            line-height: 1.4;
        }
        .submission-file-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .submission-file-row input[type="file"] {
            flex: 1 1 100%;
            min-width: 180px;
        }
        .submission-file-row .remove-file-btn {
            padding: 6px 10px;
            font-size: 0.86rem;
            border-radius: 7px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            cursor: pointer;
        }
        .submission-file-row .remove-file-btn:hover {
            background: #eef2f7;
        }
        .add-file-btn {
            margin-top: 10px;
            padding: 10px 14px;
        }
        .courses-layout {
            display: grid;
            grid-template-columns: minmax(280px, 380px) 1fr;
            gap: 20px;
            align-items: start;
        }
        .subject-list {
            display: grid;
            gap: 14px;
        }
        .subject-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #d1d5db;
            padding: 18px;
            cursor: pointer;
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .subject-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(17, 24, 39, 0.08);
        }
        .subject-card.active {
            border-color: #556b2f;
            box-shadow: 0 12px 30px rgba(34, 71, 23, 0.12);
        }
        .subject-card .subject-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
        }
        .subject-card .subject-meta {
            color: #475569;
            font-size: 0.95rem;
            display: grid;
            gap: 4px;
        }
        .subject-card .subject-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        .badge {
            background: #f1f5f9;
            color: #334155;
            font-size: 0.82rem;
            padding: 5px 10px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .course-content {
            background: white;
            border-radius: 16px;
            border: 1px solid #d1d5db;
            padding: 24px;
            min-height: 620px;
        }
        .subject-students-button {
            display: inline-flex;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #1d4ed8;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease;
        }
        .subject-students-button:hover {
            background: #eff6ff;
            border-color: #93c5fd;
        }
        .overdue-reminder {
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid #fecaca;
            background: #fee2e2;
            color: #991b1b;
            font-weight: 700;
        }
        .student-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            z-index: 2100;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .student-modal.active {
            display: flex;
        }
        .student-modal-content {
            background: white;
            border-radius: 18px;
            width: min(920px, 100%);
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(15, 23, 42, 0.18);
            display: flex;
            flex-direction: column;
        }
        .student-modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .student-modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
            color: #0f172a;
        }
        .student-modal-body {
            padding: 20px 24px 24px;
            overflow-y: auto;
        }
        .student-table {
            width: 100%;
            border-collapse: collapse;
        }
        .student-table th,
        .student-table td {
            border: 1px solid #e2e8f0;
            padding: 12px 14px;
            text-align: left;
            color: #334155;
        }
        .student-table th {
            background: #f8fafc;
            font-weight: 700;
        }
        .student-modal-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .student-modal-actions button {
            padding: 10px 16px;
            border-radius: 999px;
            border: none;
            font-weight: 700;
            cursor: pointer;
        }
        .student-modal-actions .btn-export {
            background: #556b2f;
            color: white;
        }
        .student-modal-actions .btn-print {
            background: #1d4ed8;
            color: white;
        }
        .course-content-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 18px;
        }
        .course-content-header h2 {
            margin: 0;
            font-size: 1.7rem;
            color: #111827;
        }
        .course-content-header .sub-headline {
            color: #475569;
            font-size: 0.98rem;
        }
        .course-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }
        .course-stat {
            background: #f8fafc;
            border-radius: 12px;
            padding: 14px 16px;
            border: 1px solid #e2e8f0;
        }
        .course-stat strong {
            display: block;
            font-size: 1.2rem;
            color: #0f172a;
        }
        .course-stat span {
            color: #64748b;
            font-size: 0.92rem;
        }
        .course-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .course-tab {
            padding: 12px 20px;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            background: #f8fafc;
            color: #334155;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
        }
        .course-tab.active {
            background: #556b2f;
            color: #ffffff;
            border-color: #556b2f;
        }
        .tab-panel {
            display: none;
        }
        .tab-panel.active {
            display: block;
        }
        .week-section {
            margin-bottom: 22px;
        }
        .week-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            gap: 12px;
        }
        .week-title {
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .week-info {
            color: #64748b;
            font-size: 0.92rem;
        }
        .item-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            background: #ffffff;
            margin-bottom: 12px;
            transition: box-shadow 0.2s ease;
        }
        .item-card:hover {
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        }
        .item-card-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
        }
        .item-title {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
            color: #111827;
        }
        .item-meta {
            color: #64748b;
            font-size: 0.9rem;
        }
        .item-body {
            color: #334155;
            line-height: 1.6;
        }
        .item-actions {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .item-link,
        .item-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
        }
        .item-link {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
        }
        .item-status {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #e2e8f0;
        }
        .item-status.submitted {
            background: #bbf7d0;
            border-color: #86efac;
            color: #14532d;
        }
        .item-status.graded {
            background: #34d399;
            border-color: #10b981;
            color: #064e3b;
        }
        .item-status.missing {
            background: #fee2e2;
            border-color: #fecaca;
            color: #991b1b;
        }
        .item-card.graded {
            background: #bbf7d0;
            border: 1px solid #059669;
        }
        .item-card.graded .item-title {
            color: #064e3b;
        }
        .item-card.graded .item-meta {
            color: #065f46;
        }
        .item-card.clickable-item {
            cursor: pointer;
        }
        .item-card.clickable-item:hover {
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        }
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-backdrop.active {
            display: flex;
        }
        .modal-box {
            width: min(720px, 100%);
            max-height: min(90vh, 900px);
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(15, 23, 42, 0.25);
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            padding: 22px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            color: #0f172a;
        }
        .modal-header .modal-type {
            color: #475569;
            font-size: 0.95rem;
        }
        .modal-close {
            background: transparent;
            border: none;
            color: #475569;
            font-size: 1.5rem;
            cursor: pointer;
        }
        .modal-body {
            padding: 20px 24px 24px;
            overflow-y: auto;
        }
        .modal-section {
            margin-bottom: 20px;
        }
        .modal-section label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
            color: #334155;
        }
        .modal-dates {
            display: grid;
            gap: 10px;
            margin-top: 10px;
            color: #475569;
            font-size: 0.95rem;
        }
        .modal-file-link {
            margin-top: 14px;
        }
        .modal-file-link a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 999px;
            border: 1px solid #dbeafe;
            background: #eff6ff;
            color: #1d4ed8;
            text-decoration: none;
        }
        .modal-submit-panel {
            display: grid;
            gap: 14px;
            border-top: 1px solid #e2e8f0;
            padding-top: 18px;
            margin-top: 10px;
        }
        .modal-submit-panel input[type="file"] {
            width: 100%;
        }
        .modal-submit-panel button {
            width: fit-content;
            padding: 12px 20px;
            border: none;
            border-radius: 999px;
            background: #556b2f;
            color: white;
            cursor: pointer;
            font-weight: 700;
        }
        .modal-submit-status {
            background: #f8fafc;
            border: 1px solid #d1d5db;
            border-radius: 14px;
            padding: 14px 16px;
            color: #334155;
        }
        .empty-state {
            padding: 50px 24px;
            border-radius: 18px;
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            color: #475569;
            text-align: center;
        }
        @media (max-width: 1050px) {
            .courses-layout {
                grid-template-columns: 1fr;
            }
        }
        /* NOTIFICATION PANEL STYLES */
        .floating-buttons {
            position: fixed;
            right: 10px;
            top: 200px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .floating-buttons button {
            background: #556b2f;
            border: none;
            color: white;
            padding: 12px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            position: relative;
        }

        .floating-buttons button:hover {
            background: #6b8e23;
            transform: scale(1.1);
        }

        .notification-dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #e74c3c;
            box-shadow: 0 0 0 2px rgba(255,255,255,0.9);
            display: none;
        }

        .sidepanel {
            position: fixed;
            right: -350px;
            top: 0;
            width: 320px;
            height: 100vh;
            background: white;
            box-shadow: -3px 0 10px rgba(0,0,0,0.2);
            padding: 20px;
            transition: 0.4s;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }

        .sidepanel.active {
            right: 0;
        }

        .closeBtn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6b7280;
            padding: 0;
        }

        .closeBtn:hover {
            color: #1f2937;
        }

        .notification-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding: 0 20px;
        }

        .notification-panel-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 700;
        }

        .notification-section {
            margin-bottom: 20px;
        }

        .notification-section-title {
            padding: 0 20px 10px 20px;
            margin: 0;
            font-size: 0.9rem;
            font-weight: 700;
            color: #3b82f6;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .notification-item {
            padding: 12px 20px;
            margin: 0 8px 8px 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            border-left: 4px solid #3b82f6;
        }

        .notification-item:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .notification-announcement {
            background: #eff6ff;
        }

        .notification-announcement:hover {
            background: #e0f2fe;
        }

        .notification-assignment {
            background: #f0fdf4;
        }

        .notification-assignment:hover {
            background: #dcfce7;
        }

        .notification-submission {
            background: #fef3c7;
        }

        .notification-submission:hover {
            background: #fde68a;
        }

        .notification-item-title {
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 4px;
            font-size: 0.95rem;
        }

        .notification-item-meta {
            color: #475569;
            font-size: 0.85rem;
        }

        .notification-item-empty {
            padding: 12px 20px;
            color: #64748b;
            text-align: center;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->
<?php include __DIR__ . '/../includes/sidebar_student.php'; ?>

<div class="main">
    <div class="course-page-header">
        <div>
            <h2 style="color: #ff8000; font-size: 2.5rem; font-weight: bold; margin-bottom: 6px;">My courses</h2>
            <div style="font-size: 1.2rem; color: #334155;"><b><?php echo safeText($studentName); ?></b></div>
        </div>
        <div class="course-summary"> <b><?php echo safeText($gradeLevel); ?> • Section <?php echo safeText($section); ?> • <?php echo count($subjects); ?> enrolled subject<?php echo count($subjects) === 1 ? '' : 's'; ?></b></div>
    </div>

    <div class="courses-layout">
        <div class="subject-list">
            <?php if (count($subjects) === 0): ?>
                <div class="empty-state">
                    No enrolled subjects available yet for your grade and section.
                </div>
            <?php endif; ?>
            <?php foreach ($subjects as $subjectName => $subject): ?>
                <div class="subject-card<?php echo $subjectName === $selectedSubject ? ' active' : ''; ?>" data-subject="<?php echo htmlspecialchars($subjectName, ENT_QUOTES, 'UTF-8'); ?>" data-lesson-count="<?php echo (int)$subject['lesson_count']; ?>" data-activity-count="<?php echo (int)$subject['activity_count']; ?>" data-quiz-count="<?php echo (int)$subject['quiz_count']; ?>">
                    <div class="subject-title"><?php echo safeText($subjectName); ?></div>
                    <div class="subject-meta">Instructor: <?php echo safeText($subject['instructor_name']); ?> • Period: <?php echo safeText($subject['grading_period']); ?></div>
                    <div class="subject-badges">
                        <span class="badge"><i class="fa fa-book-open"></i> <?php echo (int)$subject['lesson_count']; ?> lesson<?php echo $subject['lesson_count'] === 1 ? '' : 's'; ?></span>
                        <span class="badge"><i class="fa fa-pencil"></i> <?php echo (int)$subject['activity_count']; ?> activity<?php echo $subject['activity_count'] === 1 ? '' : 'ies'; ?></span>
                        <span class="badge"><i class="fa fa-question-circle"></i> <?php echo (int)$subject['quiz_count']; ?> quiz<?php echo $subject['quiz_count'] === 1 ? '' : 'zes'; ?></span>
                    </div>
                    <button type="button" class="subject-students-button" data-subject="<?php echo htmlspecialchars($subjectName, ENT_QUOTES, 'UTF-8'); ?>">View students</button>
                    <?php if ($subject['latest_update']): ?>
                        <div class="subject-meta">Last updated <?php echo date('F j, Y', strtotime($subject['latest_update'])); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="course-content">
            <?php if (count($subjects) === 0): ?>
                <div class="empty-state">
                    We could not find any course material for Grade <?php echo safeText($gradeLevel); ?> Section <?php echo safeText($section); ?>.
                    <div style="margin-top: 12px; color: #334155;">Ask your teacher to assign lessons or activities to your grade and section.</div>
                </div>
            <?php else: ?>
                <?php $active = $selectedSubject ?: array_key_first($subjects); ?>
                <div class="course-content-header">
                    <div>
                        <h2><?php echo safeText($active); ?></h2>
                    </div>
                    <div class="course-stat-grid">
                        <div class="course-stat">
                            <strong><?php echo (int)$subjects[$active]['lesson_count']; ?></strong>
                            <span>Lessons available</span>
                        </div>
                        <div class="course-stat">
                            <strong><?php echo (int)$subjects[$active]['activity_count']; ?></strong>
                            <span>Activities</span>
                        </div>
                        <div class="course-stat">
                            <strong><?php echo (int)$subjects[$active]['quiz_count']; ?></strong>
                            <span>Quizzes</span>
                        </div>
                    </div>
                </div>
                <div class="course-tabs">
                    <button class="course-tab active" data-tab="lessons">Lessons</button>
                    <button class="course-tab" data-tab="activities">Activities</button>
                </div>

                <?php foreach ($subjects as $subjectName => $subject): ?>
                    <div class="tab-panel<?php echo $subjectName === $active ? ' active' : ''; ?>" data-subject-panel="<?php echo htmlspecialchars($subjectName, ENT_QUOTES, 'UTF-8'); ?>" data-tab-panel="lessons">
                        <?php $lessons = $subjectLessons[$subjectName] ?? []; ?>
                        <?php if (empty($lessons)): ?>
                            <div class="empty-state">No lessons have been published yet for this subject.</div>
                        <?php else: ?>
                            <?php
                                $groupedLessons = [];
                                foreach ($lessons as $lesson) {
                                    $groupedLessons[weekLabel($lesson['created_at'])][] = $lesson;
                                }
                            ?>
                            <?php foreach ($groupedLessons as $week => $items): ?>
                                <div class="week-section">
                                    <div class="week-section-header">
                                        <h3 class="week-title"><?php echo safeText($week); ?></h3>
                                        <div class="week-info"><?php echo count($items); ?> lesson<?php echo count($items) === 1 ? '' : 's'; ?></div>
                                    </div>
                                    <?php foreach ($items as $lesson): ?>
                                        <div class="item-card">
                                            <div class="item-card-header">
                                                <h4 class="item-title"><?php echo safeText($lesson['title']); ?></h4>
                                                <div class="item-meta"><b>Uploaded <?php echo date('F j, Y', strtotime($lesson['created_at'])); ?></b></div>
                                            </div>
                                            <?php if (trim($lesson['description'])): ?>
                                                <div class="item-body"><?php echo nl2br(safeText($lesson['description'])); ?></div>
                                            <?php endif; ?>
                                            <div class="item-actions">
                                                <?php if (!empty($lesson['file_path'])): ?>
                                                    <a class="item-link" href="download_lesson.php?lesson_id=<?php echo intval($lesson['id']); ?>" target="_blank" rel="noopener">Download material</a>
                                                <?php else: ?>
                                                    <span class="item-status">No downloadable file</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="tab-panel<?php echo $subjectName === $active ? ' active' : ''; ?>" data-subject-panel="<?php echo htmlspecialchars($subjectName, ENT_QUOTES, 'UTF-8'); ?>" data-tab-panel="activities">
                        <?php $assignments = $subjectAssignments[$subjectName] ?? []; ?>
                        <?php if (empty($assignments)): ?>
                            <div class="empty-state">No activities or quizzes have been posted yet for this subject.</div>
                        <?php else: ?>
                            <?php
                                $groupedAssignments = [];
                                foreach ($assignments as $assignment) {
                                    $groupKey = $assignment['open_date'] ?: $assignment['due_date'];
                                    $groupedAssignments[weekLabel($groupKey)][] = $assignment;
                                }
                            ?>
                            <?php foreach ($groupedAssignments as $week => $items): ?>
                                <div class="week-section">
                                    <div class="week-section-header">
                                        <h3 class="week-title"><?php echo safeText($week); ?></h3>
                                        <div class="week-info"><?php echo count($items); ?> item<?php echo count($items) === 1 ? '' : 's'; ?></div>
                                    </div>
                                    <?php foreach ($items as $assignment): ?>
                                        <?php
                                            $dueDate = trim($assignment['due_date'] ?? '');
                                            $dueTime = trim($assignment['due_time'] ?? '');
                                            $dueDateTime = '';
                                            if ($dueDate !== '') {
                                                $dueDateTime = $dueDate . ' ' . ($dueTime !== '' ? $dueTime : '23:59:59');
                                            }
                                            $isOverdue = false;
                                            if ($dueDateTime !== '' && strtotime($dueDateTime) !== false && strtotime($dueDateTime) < time() && $assignment['status'] !== 'submitted') {
                                                $isOverdue = true;
                                            }
                                            $assignment['submission_file_paths'] = parseSubmissionFilePaths($assignment['submission_file_path'] ?? '');
                                            $assignment['submission_file_path'] = $assignment['submission_file_paths'][0] ?? '';
                                            $isGraded = !empty($assignment['submission_file_path']) && !empty($assignment['grade_value']);
                                            $itemCardClass = 'item-card clickable-item' . ($isGraded ? ' graded' : '');
                                            $itemStatusClass = $isGraded ? 'graded' : ($assignment['status'] === 'submitted' ? 'submitted' : 'missing');
                                        ?>
                                        <div id="assignment-<?php echo intval($assignment['id']); ?>" class="<?php echo $itemCardClass; ?>" data-assignment-id="<?php echo intval($assignment['id']); ?>" data-assignment-title="<?php echo htmlspecialchars($assignment['title'], ENT_QUOTES, 'UTF-8'); ?>" data-assignment-description="<?php echo htmlspecialchars($assignment['description'], ENT_QUOTES, 'UTF-8'); ?>" data-assignment-type="<?php echo htmlspecialchars($assignment['assignment_type'] ?: 'activity', ENT_QUOTES, 'UTF-8'); ?>" data-assignment-open-date="<?php echo htmlspecialchars($assignment['open_date'], ENT_QUOTES, 'UTF-8'); ?>" data-assignment-open-time="<?php echo htmlspecialchars($assignment['open_time'], ENT_QUOTES, 'UTF-8'); ?>" data-assignment-due-date="<?php echo htmlspecialchars($assignment['due_date'], ENT_QUOTES, 'UTF-8'); ?>" data-assignment-due-time="<?php echo htmlspecialchars($assignment['due_time'], ENT_QUOTES, 'UTF-8'); ?>" data-assignment-due-datetime="<?php echo htmlspecialchars($dueDateTime, ENT_QUOTES, 'UTF-8'); ?>" data-subject-name="<?php echo htmlspecialchars($subjectName, ENT_QUOTES, 'UTF-8'); ?>" data-assignment-file-name="<?php echo htmlspecialchars($assignment['assignment_file_name'], ENT_QUOTES, 'UTF-8'); ?>" data-assignment-file-path="<?php echo htmlspecialchars(browserPath($assignment['assignment_file_path']), ENT_QUOTES, 'UTF-8'); ?>" data-status="<?php echo htmlspecialchars($assignment['status'], ENT_QUOTES, 'UTF-8'); ?>" data-submitted-at="<?php echo htmlspecialchars($assignment['submitted_at'], ENT_QUOTES, 'UTF-8'); ?>" data-submission-file-path="<?php echo htmlspecialchars($assignment['submission_file_path'], ENT_QUOTES, 'UTF-8'); ?>" data-submission-file-paths="<?php echo htmlspecialchars(json_encode($assignment['submission_file_paths']), ENT_QUOTES, 'UTF-8'); ?>" data-assignment-grade-value="<?php echo htmlspecialchars((string)($assignment['grade_value'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-assignment-max-points="<?php echo htmlspecialchars((string)($assignment['max_points'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-assignment-graded-at="<?php echo htmlspecialchars($assignment['graded_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            <div class="item-card-header">
                                                <h4 class="item-title"><?php echo safeText($assignment['title']); ?></h4>
                                                <div class="item-meta"><b><?php echo safeText(ucfirst($assignment['assignment_type'] ?: 'activity')); ?> • Due <?php echo date('F j, Y', strtotime($assignment['due_date'])); ?><?php echo !empty($assignment['due_time']) ? ' at ' . date('g:i A', strtotime($assignment['due_time'])) : ''; ?></b></div>
                                            </div>
                                            <?php if (trim($assignment['description'])): ?>
                                                <div class="item-body"><?php echo nl2br(safeText($assignment['description'])); ?></div>
                                            <?php endif; ?>
                                            <div class="item-actions">
                                                <?php if (!empty($assignment['assignment_file_path'])): ?>
                                                    <a class="item-link" href="<?php echo safeText(browserPath($assignment['assignment_file_path'])); ?>" target="_blank" rel="noopener">Download <?php echo safeText($assignment['assignment_file_name'] ?: 'file'); ?></a>
                                                <?php endif; ?>
                                                <?php if (!empty($assignment['submission_file_paths'])): ?>
                                                    <?php foreach ($assignment['submission_file_paths'] as $index => $submissionPath): ?>
                                                        <a class="item-link view-submission-link" href="<?php echo safeText(browserPath($submissionPath)); ?>" target="_blank" rel="noopener">View submission <?php echo $index + 1; ?></a>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                <?php if ($isOverdue): ?>
                                                    <span class="overdue-reminder">Overdue — submit now</span>
                                                <?php endif; ?>
                                                <span class="item-status <?php echo $itemStatusClass; ?>">
                                                    <?php if ($isGraded): ?>
                                                        Graded • <?php echo safeText($assignment['grade_value'] . (!empty($assignment['max_points']) ? '/' . intval($assignment['max_points']) : '')); ?>
                                                        <?php if (!empty($assignment['graded_at'])): ?>
                                                            • <?php echo date('F j, Y g:i A', strtotime($assignment['graded_at'])); ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <?php echo safeText(ucfirst($assignment['status'])); ?>
                                                        <?php if (!empty($assignment['submitted_at'])): ?>
                                                            • <?php echo date('F j, Y g:i A', strtotime($assignment['submitted_at'])); ?>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="assignmentModal">
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-header">
            <div>
                <h3 id="modalTitle">Activity details</h3>
                <div class="modal-type" id="modalType">Activity</div>
            </div>
            <button type="button" class="modal-close" id="closeModal">×</button>
        </div>
        <div class="modal-body">
            <div class="modal-section">
                <div class="modal-dates">
                    <div id="modalOpenDate">Opened: —</div>
                    <div id="modalDueDate">Due: —</div>
                </div>
            </div>
            <div class="modal-section">
                <label>Description</label>
                <div class="item-body" id="modalDescription">No description available.</div>
            </div>
            <div class="modal-section" id="modalGradeInfo"></div>
            <div class="modal-section modal-file-link" id="modalFileLink"></div>
            <div class="modal-section modal-submit-status" id="modalSubmissionStatus"></div>
            <form id="assignmentSubmitForm" class="modal-submit-panel" method="post" enctype="multipart/form-data" action="submit_assignment.php">
                <input type="hidden" name="assignment_id" id="modalAssignmentId" value="">
                <input type="hidden" name="subject" id="modalSubject" value="<?php echo htmlspecialchars($active ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <label for="submissionFile">Submit file(s)</label>
                <div id="submissionFilesContainer">
                    <div class="submission-file-row">
                        <input type="file" id="submissionFile" name="submission_file[]" accept="*/*">
                    </div>
                </div>
                <div class="submission-note">Select one file to submit. Once submitted, your submission cannot be changed.</div>
                <button type="submit">Submit assignment</button>
            </form>
        </div>
    </div>
</div>

<div class="student-modal" id="studentModal">
    <div class="student-modal-content">
        <div class="student-modal-header">
            <div>
                <h3 id="studentModalTitle">Class students</h3>
                <div id="studentModalSubtitle" style="color:#64748b;font-size:0.95rem;"></div>
            </div>
            <button type="button" id="closeStudentModal" style="background:transparent;border:none;font-size:1.5rem;color:#475569;cursor:pointer;">×</button>
        </div>
        <div class="student-modal-body">
            <div class="student-modal-actions">
                <button class="btn-export" id="exportStudentCsv">Export CSV</button>
                <button class="btn-print" id="printStudentList">Print list</button>
            </div>
            <div style="margin-top:18px; overflow:auto;">
                <table class="student-table" id="studentTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Student number</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- RIGHT FLOAT BUTTONS -->
    <div class="floating-buttons">
        <button id="activityBtn" title="Notifications">
            <i class="fa fa-bell"></i>
            <span class="notification-dot" id="notificationDot"></span>
        </button>
    </div>

    <!-- NOTIFICATION PANEL -->
    <div id="activityPanel" class="sidepanel">
        <button class="closeBtn" onclick="closeActivity()">✖</button>
        <div class="notification-panel-header">
            <h3>Notifications</h3>
        </div>
        
        <div class="notification-section">
            <h4 class="notification-section-title">📢 Announcements</h4>
            <div id="announcementsList">
                <?php if (empty($dashboardNotificationEvents)): ?>
                    <div class="notification-item-empty">No announcements</div>
                <?php else: ?>
                    <?php $annCount = 0; ?>
                    <?php foreach ($dashboardNotificationEvents as $event): ?>
                        <?php if (isset($event['type']) && $event['type'] === 'Announcement'): ?>
                            <?php $annCount++; if ($annCount > 5) break; ?>
                            <div class="notification-item notification-announcement" onclick="<?php echo !empty($event['link']) ? "window.location.href='" . htmlspecialchars($event['link']) . "'" : ""; ?>" style="cursor: pointer;">
                                <div class="notification-item-title"><?php echo htmlspecialchars($event['title']); ?></div>
                                <div class="notification-item-meta"><?php echo date('M j, Y', strtotime($event['date'])); ?></div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($annCount === 0): ?>
                        <div class="notification-item-empty">No announcements</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="notification-section">
            <h4 class="notification-section-title">✓ Activities</h4>
            <div id="activitiesList" style="max-height: 300px; overflow-y: auto;">
                <?php if (empty($dashboardNotificationEvents)): ?>
                    <div class="notification-item-empty">No pending activities</div>
                <?php else: ?>
                    <?php $activityCount = 0; ?>
                    <?php foreach ($dashboardNotificationEvents as $event): ?>
                        <?php if (isset($event['type']) && in_array($event['type'], ['Submission', 'Assignment'])): ?>
                            <?php $activityCount++; if ($activityCount > 5) break; ?>
                            <div class="notification-item notification-assignment" onclick="<?php echo !empty($event['link']) ? "window.location.href='" . htmlspecialchars($event['link']) . "'" : ""; ?>" style="cursor: pointer; border-left-color: #3b82f6;">
                                <div class="notification-item-title"><?php echo htmlspecialchars($event['title']); ?></div>
                                <div class="notification-item-meta"><?php echo htmlspecialchars($event['text']); ?> • <?php echo date('M j, Y', strtotime($event['date'])); ?></div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($activityCount === 0): ?>
                        <div class="notification-item-empty">No activities</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include "../includes/back_to_top.php"; ?>

<!-- PROFILE -->
<?php include __DIR__ . '/../includes/profile_student.php'; ?>

<!-- FOOTER -->
<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    const subjectCards = document.querySelectorAll('.subject-card');
    const tabs = document.querySelectorAll('.course-tab');
    const panels = document.querySelectorAll('.tab-panel');
    let activeSubject = '<?php echo addslashes($active ?? ''); ?>';
    let activeTab = 'lessons';

    function showSubject(subjectName) {
        if (!subjectName) return;
        activeSubject = subjectName;
        subjectCards.forEach(card => {
            card.classList.toggle('active', card.dataset.subject === subjectName);
        });
        const title = document.querySelector('.course-content-header h2');
        if (title) {
            title.textContent = subjectName;
        }
        const activeCard = Array.from(subjectCards).find(card => card.dataset.subject === subjectName);
        if (activeCard) {
            const lessonCount = activeCard.dataset.lessonCount || '0';
            const activityCount = activeCard.dataset.activityCount || '0';
            const quizCount = activeCard.dataset.quizCount || '0';
            const statValues = document.querySelectorAll('.course-stat strong');
            if (statValues.length >= 3) {
                statValues[0].textContent = lessonCount;
                statValues[1].textContent = activityCount;
                statValues[2].textContent = quizCount;
            }
        }
        panels.forEach(panel => {
            const matchesSubject = panel.dataset.subjectPanel === subjectName;
            const isTab = panel.dataset.tabPanel === activeTab;
            panel.classList.toggle('active', matchesSubject && isTab);
        });
    }

    function setActiveTab(tabName) {
        activeTab = tabName;
        tabs.forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tabName));
        panels.forEach(panel => {
            const matchesTab = panel.dataset.tabPanel === tabName;
            const matchesSubject = panel.dataset.subjectPanel === activeSubject;
            panel.classList.toggle('active', matchesTab && matchesSubject);
        });
    }

    subjectCards.forEach(card => {
        card.addEventListener('click', function () {
            const subjectName = this.dataset.subject;
            showSubject(subjectName);
        });
    });

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            setActiveTab(this.dataset.tab);
        });
    });

    if (activeSubject) {
        showSubject(activeSubject);
    }

    const assignmentModal = document.getElementById('assignmentModal');
    const closeModal = document.getElementById('closeModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalType = document.getElementById('modalType');
    const modalOpenDate = document.getElementById('modalOpenDate');
    const modalDueDate = document.getElementById('modalDueDate');
    const modalDescription = document.getElementById('modalDescription');
    const modalFileLink = document.getElementById('modalFileLink');
    const modalSubmissionStatus = document.getElementById('modalSubmissionStatus');
    const modalGradeInfo = document.getElementById('modalGradeInfo');
    const modalAssignmentId = document.getElementById('modalAssignmentId');
    const modalSubject = document.getElementById('modalSubject');
    const submissionFilesContainer = document.getElementById('submissionFilesContainer');
    const addSubmissionFileBtn = document.getElementById('addSubmissionFileBtn');
    const assignmentSubmitForm = document.getElementById('assignmentSubmitForm');
    const submissionPanel = document.querySelector('.modal-submit-panel');
    const studentModal = document.getElementById('studentModal');
    const studentModalTitle = document.getElementById('studentModalTitle');
    const studentModalSubtitle = document.getElementById('studentModalSubtitle');
    const studentTableBody = document.querySelector('#studentTable tbody');
    const exportCsvButton = document.getElementById('exportStudentCsv');
    const printStudentButton = document.getElementById('printStudentList');
    const closeStudentModal = document.getElementById('closeStudentModal');
    const classStudents = <?php echo json_encode($classStudents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    function formatDateTime(timestamp) {
        if (!timestamp) {
            return '';
        }
        const date = new Date(timestamp);
        if (Number.isNaN(date.getTime())) {
            return timestamp;
        }
        return date.toLocaleString([], { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });
    }

    function normalizeBrowserPath(path) {
        const value = String(path || '').trim();
        if (value === '') {
            return '';
        }
        if (/^(https?:|\/|\\|\.\.)/i.test(value)) {
            return value;
        }
        return '../' + value.replace(/^\/+/, '');
    }

    function renderStudentTable(subjectName) {
        studentModalTitle.textContent = `Students for ${subjectName}`;
        studentModalSubtitle.textContent = `${classStudents.length} student${classStudents.length === 1 ? '' : 's'} in grade ${safeText('<?php echo addslashes($gradeLevel); ?>')} section ${safeText('<?php echo addslashes($section); ?>')}`;
        studentTableBody.innerHTML = '';
        classStudents.forEach((student, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `<td>${index + 1}</td><td>${student.name}</td><td>${student.student_number || '—'}</td>`;
            studentTableBody.appendChild(row);
        });
    }

    function resetSubmissionForm() {
        submissionFilesContainer.innerHTML = '';
        submissionFilesContainer.appendChild(createSubmissionFileInput());
        if (submissionPanel) {
            submissionPanel.style.display = '';
        }
        const submitBtn = assignmentSubmitForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
        }
    }

    function hideSubmissionPanel() {
        if (submissionPanel) {
            submissionPanel.style.display = 'none';
        }
        const submitBtn = assignmentSubmitForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
        }
    }

    function createSubmissionFileInput() {
        const wrapper = document.createElement('div');
        wrapper.className = 'submission-file-row';

        const input = document.createElement('input');
        input.type = 'file';
        input.name = 'submission_file[]';
        input.accept = '*/*';

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'remove-file-btn';
        removeButton.textContent = 'Remove file';
        removeButton.addEventListener('click', () => {
            input.value = '';
        });

        wrapper.appendChild(input);
        wrapper.appendChild(removeButton);
        return wrapper;
    }

    function getSelectedSubmissionFiles() {
        return Array.from(submissionFilesContainer.querySelectorAll('input[type="file"]'))
            .flatMap(input => Array.from(input.files));
    }

    function exportStudentCsv() {
        const header = ['#', 'Name', 'Student number'];
        const rows = classStudents.map((student, index) => [index + 1, student.name, student.student_number || '']);
        const csvContent = [header, ...rows].map(row => row.map(value => `"${String(value).replace(/"/g, '""')}"`).join(',')).join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `students_${activeSubject.replace(/\s+/g, '_').toLowerCase() || 'class'}.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function printStudentList() {
        const printWindow = window.open('', '_blank');
        if (!printWindow) return;
        const rowsHtml = classStudents.map((student, index) => `<tr><td>${index + 1}</td><td>${student.name}</td><td>${student.student_number || '—'}</td></tr>`).join('');
        printWindow.document.write(`<!DOCTYPE html><html><head><title>Student List</title><style>body{font-family:Arial,sans-serif;padding:20px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ccc;padding:10px;text-align:left;}th{background:#f4f4f4;}</style></head><body><h1>Student list for ${activeSubject}</h1><table><thead><tr><th>#</th><th>Name</th><th>Student number</th></tr></thead><tbody>${rowsHtml}</tbody></table></body></html>`);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }

    function openStudentModal(subjectName) {
        renderStudentTable(subjectName);
        studentModal.classList.add('active');
    }

    function safeText(text) {
        return String(text).replace(/[&<>\"]/g, function (match) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[match];
        });
    }

    document.querySelectorAll('.subject-students-button').forEach(button => {
        button.addEventListener('click', function (event) {
            event.stopPropagation();
            openStudentModal(this.dataset.subject);
        });
    });

    closeStudentModal.addEventListener('click', function () {
        studentModal.classList.remove('active');
    });
    studentModal.addEventListener('click', function (event) {
        if (event.target === studentModal) {
            studentModal.classList.remove('active');
        }
    });
    exportCsvButton.addEventListener('click', exportStudentCsv);
    printStudentButton.addEventListener('click', printStudentList);

    function openAssignmentFromHash() {
        const hash = window.location.hash;
        if (!hash.startsWith('#assignment-')) {
            return;
        }
        const assignmentId = hash.replace('#assignment-', '');
        if (!assignmentId) {
            return;
        }
        const card = document.getElementById(`assignment-${assignmentId}`);
        if (!card) {
            return;
        }
        const subject = card.dataset.subjectName;
        if (subject) {
            showSubject(subject);
        }
        setActiveTab('activities');
        window.requestAnimationFrame(() => {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            openAssignmentModal(card);
        });
    }

    window.addEventListener('load', openAssignmentFromHash);

    function updateAssignmentCardAfterSubmit(assignmentId, submittedAt, submissionPaths) {
        const card = document.querySelector(`.item-card.clickable-item[data-assignment-id="${assignmentId}"]`);
        if (!card) {
            return;
        }
        card.dataset.status = 'submitted';
        card.dataset.submittedAt = submittedAt;

        const normalizedPaths = Array.isArray(submissionPaths) ? submissionPaths.filter(Boolean) : [submissionPaths].filter(Boolean);
        if (normalizedPaths.length) {
            card.dataset.submissionFilePath = normalizedPaths[0];
            card.dataset.submissionFilePaths = JSON.stringify(normalizedPaths);
        }

        const statusElement = card.querySelector('.item-status');
        if (statusElement) {
            statusElement.textContent = 'Submitted • ' + submittedAt;
            statusElement.classList.remove('missing');
            statusElement.classList.add('submitted');
        }
        const actions = card.querySelector('.item-actions');
        if (actions) {
            actions.querySelectorAll('a.view-submission-link').forEach(link => link.remove());
            normalizedPaths.forEach((path, index) => {
                const link = document.createElement('a');
                link.className = 'item-link view-submission-link';
                link.target = '_blank';
                link.rel = 'noopener';
                link.textContent = `View submission ${index + 1}`;
                link.href = normalizeBrowserPath(path);
                link.style.display = 'inline-block';
                link.style.marginRight = '10px';
                actions.insertBefore(link, actions.firstChild);
            });
        }
    }

    assignmentSubmitForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        const selectedFiles = getSelectedSubmissionFiles();
        if (!selectedFiles.length) {
            alert('Please select a file before submitting.');
            return;
        }
        if (!confirm('Once you submit, you will not be able to change your submission. Are you sure you want to continue?')) {
            return;
        }

        const formData = new FormData(assignmentSubmitForm);
        formData.set('subject', activeSubject);

        try {
            const response = await fetch(assignmentSubmitForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to submit the assignment.');
            }

            const submittedAt = result.submitted_at ? formatDateTime(result.submitted_at) : formatDateTime(new Date().toISOString());
            const submissionPaths = Array.isArray(result.submission_file_paths) && result.submission_file_paths.length ? result.submission_file_paths : (result.submission_file_path ? [result.submission_file_path] : []);

            modalSubmissionStatus.innerHTML = '';
            const statusDetail = document.createElement('div');
            statusDetail.textContent = 'Submission status: submitted';
            modalSubmissionStatus.appendChild(statusDetail);
            const submittedInfo = document.createElement('div');
            submittedInfo.textContent = 'Submitted: ' + submittedAt;
            modalSubmissionStatus.appendChild(submittedInfo);

            submissionPaths.forEach((path, index) => {
                const viewLink = document.createElement('a');
                viewLink.href = normalizeBrowserPath(path);
                viewLink.target = '_blank';
                viewLink.rel = 'noopener';
                viewLink.textContent = `View submitted file ${index + 1}`;
                viewLink.style.display = 'inline-block';
                viewLink.style.marginTop = '10px';
                viewLink.style.color = '#1d4ed8';
                modalSubmissionStatus.appendChild(viewLink);
            });

            updateAssignmentCardAfterSubmit(modalAssignmentId.value, submittedAt, submissionPaths);
            hideSubmissionPanel();
            resetSubmissionForm();
        } catch (error) {
            alert(error.message || 'Unable to submit assignment at this time.');
        }
    });

    function openAssignmentModal(card) {
        const title = card.dataset.assignmentTitle || 'Activity details';
        const type = card.dataset.assignmentType || 'activity';
        const openDate = card.dataset.assignmentOpenDate || '—';
        const openTime = card.dataset.assignmentOpenTime || '';
        const dueDate = card.dataset.assignmentDueDate || '—';
        const dueTime = card.dataset.assignmentDueTime || '';
        const description = card.dataset.assignmentDescription || 'No description available.';
        const filePath = card.dataset.assignmentFilePath || '';
        const fileName = card.dataset.assignmentFileName || 'Download assignment file';
        const status = card.dataset.status || 'not submitted';
        const submittedAt = card.dataset.submittedAt || '';
        const submissionFilePaths = (() => {
            try {
                return card.dataset.submissionFilePaths ? JSON.parse(card.dataset.submissionFilePaths) : [];
            } catch {
                return [];
            }
        })();
        const submissionFilePath = submissionFilePaths.length ? submissionFilePaths[0] : card.dataset.submissionFilePath || '';
        const gradeValue = card.dataset.assignmentGradeValue || '';
        const gradeMaxPoints = card.dataset.assignmentMaxPoints || '';
        const gradedAt = card.dataset.assignmentGradedAt || '';

        modalTitle.textContent = title;
        modalType.textContent = type.charAt(0).toUpperCase() + type.slice(1);
        modalOpenDate.textContent = 'Opened: ' + (openDate !== '0000-00-00' ? formatDateTime(openDate + (openTime ? ' ' + openTime : '')) : '—');
        modalDueDate.textContent = 'Due: ' + (dueDate !== '0000-00-00' ? formatDateTime(dueDate + (dueTime ? ' ' + dueTime : '')) : '—');
        modalDescription.innerHTML = description ? description.replace(/\n/g, '<br>') : 'No description available.';
        modalAssignmentId.value = card.dataset.assignmentId || '';
        modalSubject.value = activeSubject;

        modalFileLink.innerHTML = '';
        if (filePath) {
            const link = document.createElement('a');
            link.href = normalizeBrowserPath(filePath);
            link.target = '_blank';
            link.rel = 'noopener';
            link.textContent = fileName || 'Download assignment file';
            modalFileLink.appendChild(link);
        }

        modalGradeInfo.innerHTML = '';
        if (gradeValue) {
            const gradeInfo = document.createElement('div');
            gradeInfo.textContent = 'Grade: ' + gradeValue + (gradeMaxPoints ? '/' + gradeMaxPoints : '');
            modalGradeInfo.appendChild(gradeInfo);
            if (gradedAt) {
                const gradedInfo = document.createElement('div');
                gradedInfo.textContent = 'Graded: ' + formatDateTime(gradedAt);
                modalGradeInfo.appendChild(gradedInfo);
            }
        }

        modalSubmissionStatus.innerHTML = '';
        const statusDetail = document.createElement('div');
        statusDetail.textContent = 'Submission status: ' + status.replace(/_/g, ' ');
        modalSubmissionStatus.appendChild(statusDetail);
        if (submittedAt) {
            const submittedInfo = document.createElement('div');
            submittedInfo.textContent = 'Submitted: ' + submittedAt;
            modalSubmissionStatus.appendChild(submittedInfo);
        }
        if (status === 'submitted' || submissionFilePaths.length) {
            hideSubmissionPanel();
        } else {
            resetSubmissionForm();
        }
        if (submissionFilePaths.length) {
            submissionFilePaths.forEach((path, idx) => {
                const viewLink = document.createElement('a');
                viewLink.href = normalizeBrowserPath(path);
                viewLink.target = '_blank';
                viewLink.rel = 'noopener';
                viewLink.textContent = 'View submitted file ' + (idx + 1);
                viewLink.style.display = 'inline-block';
                viewLink.style.marginTop = '10px';
                viewLink.style.color = '#1d4ed8';
                modalSubmissionStatus.appendChild(viewLink);
            });
        } else if (submissionFilePath) {
            const viewLink = document.createElement('a');
            viewLink.href = normalizeBrowserPath(submissionFilePath);
            viewLink.target = '_blank';
            viewLink.rel = 'noopener';
            viewLink.textContent = 'View submitted file';
            viewLink.style.display = 'inline-block';
            viewLink.style.marginTop = '10px';
            viewLink.style.color = '#1d4ed8';
            modalSubmissionStatus.appendChild(viewLink);
        }

        assignmentModal.classList.add('active');
    }

    function closeAssignmentModal() {
        assignmentModal.classList.remove('active');
        resetSubmissionForm();
    }

    document.querySelectorAll('.item-card.clickable-item').forEach(card => {
        card.addEventListener('click', function (event) {
            if (event.target.closest('a')) {
                return;
            }
            openAssignmentModal(card);
        });
    });

    closeModal.addEventListener('click', closeAssignmentModal);
    assignmentModal.addEventListener('click', function (event) {
        if (event.target === assignmentModal) {
            closeAssignmentModal();
        }
    });

    // NOTIFICATION PANEL FUNCTIONALITY
    const activityBtn = document.getElementById('activityBtn');
    const activityPanel = document.getElementById('activityPanel');
    const closeBtn = document.querySelector('.closeBtn');

    // Open/Close panel on bell button click
    if (activityBtn && activityPanel) {
        activityBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            activityPanel.classList.toggle('active');
        });
    }

    // Close panel on close button click
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            if (activityPanel) {
                activityPanel.classList.remove('active');
            }
        });
    }

    // Close panel on outside click
    document.addEventListener('click', function(e) {
        if (activityPanel && !activityPanel.contains(e.target) && !activityBtn.contains(e.target)) {
            activityPanel.classList.remove('active');
        }
    });

    // Render notifications when page loads
    if (typeof renderNotificationsList === 'function') {
        renderNotificationsList();
        updateNotificationDot();
    }

function closeActivity() {
    const activityPanel = document.getElementById('activityPanel');
    if (activityPanel) {
        activityPanel.classList.remove('active');
    }
}
</script>

<script src="../assets/js/notifications.js"></script>

</body>
</html>