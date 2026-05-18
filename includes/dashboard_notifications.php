<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

if (!isset($conn)) {
    include_once __DIR__ . '/../config/config.php';
    include_once __DIR__ . '/databases/db_connection.php';
}

$dashboardAssignmentEvents = [];
$dashboardNotificationEvents = [];
$today = date('Y-m-d');
$twoDaysLater = date('Y-m-d', strtotime('+2 days'));
$userRole = $_SESSION['role'] ?? '';
$userId = intval($_SESSION['user_id'] ?? 0);

if ($userRole === 'instructor' && $userId > 0) {
    $instructorId = $userId;

    $lessonSql = "SELECT l.id, l.title, l.subject_name AS course_name, l.created_at FROM teacher_lessons l WHERE l.instructor_id = $instructorId ORDER BY l.created_at DESC LIMIT 5";
    if ($lessonResult = $conn->query($lessonSql)) {
        while ($lesson = $lessonResult->fetch_assoc()) {
            $date = substr($lesson['created_at'], 0, 10);
            $dashboardNotificationEvents[] = [
                'type' => 'Lesson',
                'title' => "Lesson uploaded: {$lesson['title']}",
                'text' => "Subject: {$lesson['course_name']}",
                'date' => $date,
                'link' => 'my_class.php?lesson_id=' . intval($lesson['id']) . '#lessons',
                'id' => "lesson-{$lesson['id']}"
            ];
        }
    }

    $assignmentSql = "SELECT a.id, a.title, a.due_date, a.created_at, a.subject_name AS course_name FROM teacher_assignments a WHERE a.instructor_id = $instructorId ORDER BY a.created_at DESC LIMIT 10";
    if ($assignmentResult = $conn->query($assignmentSql)) {
        while ($assignment = $assignmentResult->fetch_assoc()) {
            $dueDate = $assignment['due_date'];
            $createdDate = substr($assignment['created_at'], 0, 10);
            $assignmentLink = 'my_class.php?assignment_id=' . intval($assignment['id']) . '#grades';

            $dashboardAssignmentEvents[] = [
                'type' => 'assignment',
                'id' => intval($assignment['id']),
                'title' => $assignment['title'],
                'date' => $dueDate,
                'link' => $assignmentLink
            ];

            $dashboardNotificationEvents[] = [
                'type' => 'Assignment',
                'title' => "Assignment uploaded: {$assignment['title']}",
                'text' => "Subject: {$assignment['course_name']}",
                'date' => $createdDate,
                'link' => $assignmentLink,
                'id' => "assignment-upload-{$assignment['id']}"
            ];

            if ($dueDate >= $today && $dueDate <= $twoDaysLater) {
                $dashboardNotificationEvents[] = [
                    'type' => 'Assignment',
                    'title' => "Assignment due soon: {$assignment['title']}",
                    'text' => "Due on {$dueDate}",
                    'date' => $dueDate,
                    'link' => $assignmentLink,
                    'id' => "assignment-due-{$assignment['id']}"
                ];
            }
        }
    }

    $announcementSql = "SELECT id, title, description, created_at FROM announcements ORDER BY created_at DESC LIMIT 10";
    if ($announcementResult = $conn->query($announcementSql)) {
        while ($announcement = $announcementResult->fetch_assoc()) {
            $summary = trim(strip_tags($announcement['description']));
            if (strlen($summary) > 120) {
                $summary = substr($summary, 0, 117) . '...';
            }

            $dashboardNotificationEvents[] = [
                'type' => 'Announcement',
                'title' => "Announcement: {$announcement['title']}",
                'text' => $summary ?: 'New announcement available.',
                'date' => substr($announcement['created_at'], 0, 10),
                'link' => 'home.php',
                'id' => "announcement-{$announcement['id']}"
            ];
        }
    }

    $submissionSql = "SELECT s.assignment_id, a.title, s.status, s.submitted_at FROM teacher_assignment_submissions s JOIN teacher_assignments a ON s.assignment_id = a.id WHERE a.instructor_id = $instructorId ORDER BY s.submitted_at DESC LIMIT 5";
    if ($submissionResult = $conn->query($submissionSql)) {
        while ($submission = $submissionResult->fetch_assoc()) {
            $submissionDate = substr($submission['submitted_at'], 0, 10);
            $dashboardNotificationEvents[] = [
                'type' => 'Submission',
                'title' => "Submission received: {$submission['title']}",
                'text' => "Status: {$submission['status']}",
                'date' => $submissionDate,
                'link' => 'my_class.php?assignment_id=' . intval($submission['assignment_id']) . '#grades',
                'id' => "submission-{$submission['assignment_id']}-{$submission['status']}"
            ];
        }
    }
} elseif ($userRole === 'student' && $userId > 0) {
    $studentSql = "SELECT grade_level, section FROM students WHERE id = $userId LIMIT 1";
    $gradeLevel = '';
    $section = '';

    if ($studentResult = $conn->query($studentSql)) {
        if ($student = $studentResult->fetch_assoc()) {
            $gradeLevel = $conn->real_escape_string($student['grade_level']);
            $section = $conn->real_escape_string($student['section']);
        }
    }

    $announcementSql = "SELECT id, title, description, created_at FROM announcements ORDER BY created_at DESC LIMIT 10";
    if ($announcementResult = $conn->query($announcementSql)) {
        while ($announcement = $announcementResult->fetch_assoc()) {
            $summary = trim(strip_tags($announcement['description']));
            if (strlen($summary) > 120) {
                $summary = substr($summary, 0, 117) . '...';
            }

            $dashboardNotificationEvents[] = [
                'type' => 'Announcement',
                'title' => "Announcement: {$announcement['title']}",
                'text' => $summary ?: 'New announcement available.',
                'date' => substr($announcement['created_at'], 0, 10),
                'link' => 'home.php',
                'id' => "announcement-{$announcement['id']}"
            ];
        }
    }

    if ($gradeLevel && $section) {
        $assignmentSql = "SELECT id, title, due_date, created_at, subject_name FROM teacher_assignments WHERE grade_level = '$gradeLevel' AND section = '$section' ORDER BY created_at DESC LIMIT 10";
        if ($assignmentResult = $conn->query($assignmentSql)) {
            while ($assignment = $assignmentResult->fetch_assoc()) {
                $dueDate = $assignment['due_date'];
                $createdDate = substr($assignment['created_at'], 0, 10);
                $subject = $assignment['subject_name'] ?? '';
                $assignmentLink = $subject ? "my_courses.php?subject=" . urlencode($subject) : "my_courses.php";
                $dashboardNotificationEvents[] = [
                    'type' => 'Assignment',
                    'title' => "New assignment: {$assignment['title']}",
                    'text' => "Due {$dueDate}",
                    'date' => $createdDate ?: $dueDate,
                    'link' => $assignmentLink,
                    'id' => "student-assignment-{$assignment['id']}"
                ];

                if ($dueDate >= $today && $dueDate <= $twoDaysLater) {
                    $dashboardNotificationEvents[] = [
                        'type' => 'Assignment',
                        'title' => "Assignment due soon: {$assignment['title']}",
                        'text' => "Due on {$dueDate}",
                        'date' => $dueDate,
                        'link' => $assignmentLink,
                        'id' => "student-assignment-due-{$assignment['id']}"
                    ];
                }
            }
        }
    }

    $submissionSql = "SELECT s.assignment_id, a.title, s.status, s.submitted_at FROM teacher_assignment_submissions s JOIN teacher_assignments a ON s.assignment_id = a.id WHERE s.student_id = $userId ORDER BY s.submitted_at DESC LIMIT 5";
    if ($submissionResult = $conn->query($submissionSql)) {
        while ($submission = $submissionResult->fetch_assoc()) {
            $submissionDate = substr($submission['submitted_at'], 0, 10);
            $dashboardNotificationEvents[] = [
                'type' => 'Submission',
                'title' => "Submission status: {$submission['title']}",
                'text' => "Status: {$submission['status']}",
                'date' => $submissionDate,
                'link' => 'grade.php',
                'id' => "student-submission-{$submission['assignment_id']}-{$submission['status']}"
            ];
        }
    }
} else {
    $announcementSql = "SELECT id, title, description, created_at FROM announcements ORDER BY created_at DESC LIMIT 10";
    if ($announcementResult = $conn->query($announcementSql)) {
        while ($announcement = $announcementResult->fetch_assoc()) {
            $summary = trim(strip_tags($announcement['description']));
            if (strlen($summary) > 120) {
                $summary = substr($summary, 0, 117) . '...';
            }

            $dashboardNotificationEvents[] = [
                'type' => 'Announcement',
                'title' => "Announcement: {$announcement['title']}",
                'text' => $summary ?: 'New announcement available.',
                'date' => substr($announcement['created_at'], 0, 10),
                'link' => '#',
                'id' => "announcement-{$announcement['id']}"
            ];
        }
    }

    // ADMIN NOTIFICATIONS
    if ($userRole === 'admin' && $userId > 0) {
        // Recently added students
        $studentsSql = "SELECT id, student_id, first_name, last_name, COALESCE(created_at, NOW()) as created_at FROM students ORDER BY COALESCE(created_at, NOW()) DESC LIMIT 5";
        if ($studentsResult = $conn->query($studentsSql)) {
            while ($student = $studentsResult->fetch_assoc()) {
                $createdDate = substr($student['created_at'], 0, 10);
                $dashboardNotificationEvents[] = [
                    'type' => 'User',
                    'title' => "New student added: {$student['student_id']}",
                    'text' => "{$student['first_name']} {$student['last_name']}",
                    'date' => $createdDate,
                    'link' => 'manage_students.php',
                    'id' => "student-added-{$student['id']}"
                ];
            }
        }

        // Recently added instructors
        $instructorsSql = "SELECT id, employee_number, first_name, last_name, COALESCE(created_at, NOW()) as created_at FROM instructors ORDER BY COALESCE(created_at, NOW()) DESC LIMIT 5";
        if ($instructorsResult = $conn->query($instructorsSql)) {
            while ($instructor = $instructorsResult->fetch_assoc()) {
                $createdDate = substr($instructor['created_at'], 0, 10);
                $dashboardNotificationEvents[] = [
                    'type' => 'User',
                    'title' => "New instructor added: {$instructor['employee_number']}",
                    'text' => "{$instructor['first_name']} {$instructor['last_name']}",
                    'date' => $createdDate,
                    'link' => 'manage_instructors.php',
                    'id' => "instructor-added-{$instructor['id']}"
                ];
            }
        }

        // Recently added courses
        $coursesSql = "SELECT id, subject_name, grade_level, COALESCE(created_at, NOW()) as created_at FROM courses ORDER BY COALESCE(created_at, NOW()) DESC LIMIT 5";
        if ($coursesResult = $conn->query($coursesSql)) {
            while ($course = $coursesResult->fetch_assoc()) {
                $createdDate = substr($course['created_at'], 0, 10);
                $dashboardNotificationEvents[] = [
                    'type' => 'Activity',
                    'title' => "New course added: {$course['subject_name']}",
                    'text' => "Grade Level: {$course['grade_level']}",
                    'date' => $createdDate,
                    'link' => 'manage_courses.php',
                    'id' => "course-added-{$course['id']}"
                ];
            }
        }

        // Sort all notifications by date (descending)
        usort($dashboardNotificationEvents, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
    }
}
