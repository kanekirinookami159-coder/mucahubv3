<?php
include "../config/config.php";
include "../includes/databases/db_connection.php";
include "../includes/functions.php";

checkLogin();
checkRole('instructor');

$instructorId = intval($_SESSION['user_id']);
include "../includes/dashboard_notifications.php";
$instructor = null;
$subjectStructure = [];
$subjectGroups = [];
$selectedGrade = trim($_REQUEST['grade'] ?? $_REQUEST['grade_level'] ?? '');
$selectedSubject = trim($_REQUEST['subject'] ?? $_REQUEST['subject_name'] ?? '');
$selectedSection = trim($_REQUEST['section'] ?? '');
$allowedPeriods = ['1st', '2nd', '3rd', '4th'];
function normalizeGradingPeriod($period) {
    $period = trim((string)$period);
    $mapping = [
        '1' => '1st',
        '2' => '2nd',
        '3' => '3rd',
        '4' => '4th',
        '1st' => '1st',
        '2nd' => '2nd',
        '3rd' => '3rd',
        '4th' => '4th',
    ];
    return $mapping[$period] ?? $period;
}

function getGradingPeriodCondition($conn, $period) {
    $period = normalizeGradingPeriod($period);
    $variants = [$period];
    if ($period === '1st') {
        $variants[] = '1';
    } elseif ($period === '2nd') {
        $variants[] = '2';
    } elseif ($period === '3rd') {
        $variants[] = '3';
    } elseif ($period === '4th') {
        $variants[] = '4';
    }
    $variants = array_values(array_unique($variants));
    $safe = array_map([$conn, 'real_escape_string'], $variants);
    return "IN ('" . implode("','", $safe) . "')";
}

$selectedPeriod = normalizeGradingPeriod(trim($_REQUEST['period'] ?? $_SESSION['selected_period'] ?? $_COOKIE['selected_period'] ?? '1st'));
if (!in_array($selectedPeriod, $allowedPeriods, true)) {
    $selectedPeriod = '1st';
}
$_SESSION['selected_period'] = $selectedPeriod;
setcookie('selected_period', $selectedPeriod, time() + 60 * 60 * 24 * 30, '/');
$allowedTabs = ['lessons', 'assignments', 'grades'];
$activeTab = trim($_POST['active_tab'] ?? $_GET['tab'] ?? 'lessons');

$focusAssignmentId = intval($_GET['assignment_id'] ?? 0);
if ($focusAssignmentId > 0 && !$selectedGrade) {
    $assignmentInfoResult = $conn->query("SELECT grade_level, section, subject_name, grading_period FROM teacher_assignments WHERE id = $focusAssignmentId AND instructor_id = $instructorId LIMIT 1");
    if ($assignmentInfoResult && $assignmentInfo = $assignmentInfoResult->fetch_assoc()) {
        $selectedGrade = $assignmentInfo['grade_level'];
        $selectedSubject = $assignmentInfo['subject_name'];
        $selectedSection = $assignmentInfo['section'];
        $selectedPeriod = normalizeGradingPeriod($assignmentInfo['grading_period'] ?? $selectedPeriod);
        if (!in_array($selectedPeriod, $allowedPeriods, true)) {
            $selectedPeriod = '1st';
        }
        $_SESSION['selected_period'] = $selectedPeriod;
        setcookie('selected_period', $selectedPeriod, time() + 60 * 60 * 24 * 30, '/');
    }
}
if ($focusAssignmentId > 0 && !in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'grades';
}
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'lessons';
}
$lesson_success = '';
$lesson_error = '';
$assignment_success = '';
$assignment_error = '';
$activity_success = '';
$activity_error = '';
$newlyCreatedAssignmentId = null;

// Get recent announcements for notification panel
$recentAnnouncements = [];
$announcementsForPanel = "SELECT id, title, created_at FROM announcements WHERE target IN ('teacher','all') ORDER BY created_at DESC LIMIT 5";
if ($result = $conn->query($announcementsForPanel)) {
    while ($row = $result->fetch_assoc()) {
        $recentAnnouncements[] = $row;
    }
}

// Get recent lessons/materials uploaded by this instructor
$recentMaterials = [];
$materialsQuery = "SELECT id, title, subject_name, created_at FROM teacher_lessons WHERE instructor_id = $instructorId ORDER BY created_at DESC LIMIT 5";
if ($result = $conn->query($materialsQuery)) {
    while ($row = $result->fetch_assoc()) {
        $recentMaterials[] = $row;
    }
}

$createLessonsTableSql = "CREATE TABLE IF NOT EXISTS teacher_lessons (
  id INT NOT NULL AUTO_INCREMENT,
  instructor_id INT NOT NULL,
  instructor_name VARCHAR(255) NOT NULL,
  grade_level VARCHAR(50) NOT NULL,
  section VARCHAR(50) NOT NULL,
  subject_name VARCHAR(100) NOT NULL,
  grading_period VARCHAR(20) NOT NULL DEFAULT '1st',
  title VARCHAR(255) NOT NULL,
  description TEXT,
  file_name VARCHAR(255) DEFAULT NULL,
  file_path VARCHAR(500) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY instructor_id (instructor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
$conn->query($createLessonsTableSql);

$createAssignmentsTableSql = "CREATE TABLE IF NOT EXISTS teacher_assignments (
  id INT NOT NULL AUTO_INCREMENT,
  instructor_id INT NOT NULL,
  instructor_name VARCHAR(255) NOT NULL,
  grade_level VARCHAR(50) NOT NULL,
  section VARCHAR(50) NOT NULL,
  subject_name VARCHAR(100) NOT NULL,
  grading_period VARCHAR(20) NOT NULL DEFAULT '1st',
  assignment_type ENUM('activity','quiz') NOT NULL DEFAULT 'activity',
  title VARCHAR(255) NOT NULL,
  description TEXT,
  open_date DATE NOT NULL DEFAULT '1970-01-01',
  open_time TIME NOT NULL DEFAULT '00:00:00',
  due_date DATE NOT NULL,
  due_time TIME NOT NULL DEFAULT '23:59:00',
  assignment_file_name VARCHAR(255) DEFAULT NULL,
  assignment_file_path VARCHAR(500) DEFAULT NULL,
  max_points INT DEFAULT 100,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY instructor_id (instructor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
$conn->query($createAssignmentsTableSql);

$checkAssignmentFileColumn = $conn->query("SHOW COLUMNS FROM teacher_assignments LIKE 'assignment_file_path'");
if (!$checkAssignmentFileColumn || $checkAssignmentFileColumn->num_rows === 0) {
    $conn->query("ALTER TABLE teacher_assignments ADD COLUMN assignment_file_name VARCHAR(255) DEFAULT NULL, ADD COLUMN assignment_file_path VARCHAR(500) DEFAULT NULL");
}

$checkOpenDateColumn = $conn->query("SHOW COLUMNS FROM teacher_assignments LIKE 'open_date'");
if (!$checkOpenDateColumn || $checkOpenDateColumn->num_rows === 0) {
    $conn->query("ALTER TABLE teacher_assignments ADD COLUMN open_date DATE NOT NULL DEFAULT '1970-01-01'");
}

$checkOpenTimeColumn = $conn->query("SHOW COLUMNS FROM teacher_assignments LIKE 'open_time'");
if (!$checkOpenTimeColumn || $checkOpenTimeColumn->num_rows === 0) {
    $conn->query("ALTER TABLE teacher_assignments ADD COLUMN open_time TIME NOT NULL DEFAULT '00:00:00'");
}

$checkDueTimeColumn = $conn->query("SHOW COLUMNS FROM teacher_assignments LIKE 'due_time'");
if (!$checkDueTimeColumn || $checkDueTimeColumn->num_rows === 0) {
    $conn->query("ALTER TABLE teacher_assignments ADD COLUMN due_time TIME NOT NULL DEFAULT '23:59:00'");
}

$checkAssignmentTypeColumn = $conn->query("SHOW COLUMNS FROM teacher_assignments LIKE 'assignment_type'");
if (!$checkAssignmentTypeColumn || $checkAssignmentTypeColumn->num_rows === 0) {
    $conn->query("ALTER TABLE teacher_assignments ADD COLUMN assignment_type ENUM('activity','quiz') NOT NULL DEFAULT 'activity'");
}
$checkAssignmentPeriodColumn = $conn->query("SHOW COLUMNS FROM teacher_assignments LIKE 'grading_period'");
if (!$checkAssignmentPeriodColumn || $checkAssignmentPeriodColumn->num_rows === 0) {
    $conn->query("ALTER TABLE teacher_assignments ADD COLUMN grading_period VARCHAR(20) NOT NULL DEFAULT '1st' AFTER subject_name");
}
$checkLessonPeriodColumn = $conn->query("SHOW COLUMNS FROM teacher_lessons LIKE 'grading_period'");
if (!$checkLessonPeriodColumn || $checkLessonPeriodColumn->num_rows === 0) {
    $conn->query("ALTER TABLE teacher_lessons ADD COLUMN grading_period VARCHAR(20) NOT NULL DEFAULT '1st' AFTER subject_name");
}

$conn->query("ALTER TABLE teacher_assignments
    MODIFY COLUMN assignment_type ENUM('activity','quiz') NOT NULL DEFAULT 'activity' AFTER subject_name,
    MODIFY COLUMN title VARCHAR(255) NOT NULL AFTER assignment_type,
    MODIFY COLUMN description TEXT AFTER title,
    MODIFY COLUMN open_date DATE NOT NULL DEFAULT '1970-01-01' AFTER description,
    MODIFY COLUMN open_time TIME NOT NULL DEFAULT '00:00:00' AFTER open_date,
    MODIFY COLUMN due_date DATE NOT NULL AFTER open_time,
    MODIFY COLUMN due_time TIME NOT NULL DEFAULT '23:59:00' AFTER due_date");

$createAssignmentSubmissionsTableSql = "CREATE TABLE IF NOT EXISTS teacher_assignment_submissions (
  id INT NOT NULL AUTO_INCREMENT,
  assignment_id INT NOT NULL,
  student_id INT NOT NULL,
  submission_text TEXT,
  submission_file_path VARCHAR(500) DEFAULT NULL,
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('submitted','graded','late') DEFAULT 'submitted',
  grade_value INT DEFAULT NULL,
  graded_at TIMESTAMP NULL DEFAULT NULL,
  graded_by VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY assignment_id (assignment_id),
  KEY student_id (student_id),
  CONSTRAINT fk_teacher_assignment_submissions_assignment FOREIGN KEY (assignment_id) REFERENCES teacher_assignments(id) ON DELETE CASCADE,
  CONSTRAINT fk_teacher_assignment_submissions_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
$conn->query($createAssignmentSubmissionsTableSql);

$createActivityGradeRecordsTableSql = "CREATE TABLE IF NOT EXISTS teacher_activity_grade_records (
  id INT NOT NULL AUTO_INCREMENT,
  assignment_id INT NOT NULL,
  activity_title VARCHAR(255) NOT NULL,
  instructor_id INT NOT NULL,
  instructor_name VARCHAR(255) NOT NULL,
  student_id INT NOT NULL,
  student_name VARCHAR(255) NOT NULL,
  grade_level VARCHAR(50) NOT NULL,
  section VARCHAR(50) NOT NULL,
  subject_name VARCHAR(100) NOT NULL,
  status ENUM('submitted','late','not submitted') NOT NULL DEFAULT 'not submitted',
  submission_file_path VARCHAR(500) DEFAULT NULL,
  grade_value INT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  graded_at TIMESTAMP NULL DEFAULT NULL,
  graded_by VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY assignment_id (assignment_id),
  KEY student_id (student_id),
  CONSTRAINT fk_teacher_activity_grade_records_assignment FOREIGN KEY (assignment_id) REFERENCES teacher_assignments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
$conn->query($createActivityGradeRecordsTableSql);
$conn->query("ALTER TABLE teacher_activity_grade_records MODIFY COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER grade_value, MODIFY COLUMN graded_at TIMESTAMP NULL DEFAULT NULL AFTER created_at, MODIFY COLUMN graded_by VARCHAR(255) DEFAULT NULL AFTER graded_at");

$createGradeRecordsTableSql = "CREATE TABLE IF NOT EXISTS teacher_grade_records (
  id INT NOT NULL AUTO_INCREMENT,
  assignment_id INT NULL,
  instructor_id INT NOT NULL,
  instructor_name VARCHAR(255) NOT NULL,
  grade_level VARCHAR(50) NOT NULL,
  section VARCHAR(50) NOT NULL,
  subject_name VARCHAR(100) NOT NULL,
  grading_period VARCHAR(20) NOT NULL DEFAULT '1st',
  activity_title VARCHAR(255) NOT NULL,
  student_id INT NOT NULL,
  student_name VARCHAR(255) NOT NULL,
  grade_value INT DEFAULT NULL,
  recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  modified_at DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY assignment_id (assignment_id),
  KEY student_id (student_id),
  CONSTRAINT fk_teacher_grade_records_assignment FOREIGN KEY (assignment_id) REFERENCES teacher_assignments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
$conn->query($createGradeRecordsTableSql);

$checkGradeRecordsGradingPeriodColumn = $conn->query("SHOW COLUMNS FROM teacher_grade_records LIKE 'grading_period'");
if (!$checkGradeRecordsGradingPeriodColumn || $checkGradeRecordsGradingPeriodColumn->num_rows === 0) {
    $conn->query("ALTER TABLE teacher_grade_records ADD COLUMN grading_period VARCHAR(20) NOT NULL DEFAULT '1st' AFTER subject_name");
}
$checkGradeRecordsModifiedAtColumn = $conn->query("SHOW COLUMNS FROM teacher_grade_records LIKE 'modified_at'");
if (!$checkGradeRecordsModifiedAtColumn || $checkGradeRecordsModifiedAtColumn->num_rows === 0) {
    $conn->query("ALTER TABLE teacher_grade_records ADD COLUMN modified_at DATETIME NULL DEFAULT NULL AFTER recorded_at");
}
$gradeRecordsAssignmentColumn = $conn->query("SHOW COLUMNS FROM teacher_grade_records LIKE 'assignment_id'");
if ($gradeRecordsAssignmentColumn && $gradeRecordsAssignmentColumn->num_rows > 0) {
    $assignmentInfo = $gradeRecordsAssignmentColumn->fetch_assoc();
    if (isset($assignmentInfo['Null']) && strtoupper($assignmentInfo['Null']) === 'NO') {
        $conn->query("ALTER TABLE teacher_grade_records MODIFY COLUMN assignment_id INT NULL");
    }
}
$foreignKeyCheck = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teacher_grade_records' AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'fk_teacher_grade_records_assignment'");
if ($foreignKeyCheck && $foreignKeyCheck->num_rows > 0) {
    $conn->query("ALTER TABLE teacher_grade_records DROP FOREIGN KEY fk_teacher_grade_records_assignment");
    $conn->query("ALTER TABLE teacher_grade_records ADD CONSTRAINT fk_teacher_grade_records_assignment FOREIGN KEY (assignment_id) REFERENCES teacher_assignments(id) ON DELETE SET NULL");
}

$createAssignmentGroupsTableSql = "CREATE TABLE IF NOT EXISTS teacher_assignment_groups (
  id INT NOT NULL AUTO_INCREMENT,
  assignment_id INT NOT NULL,
  group_name VARCHAR(255) NOT NULL,
  leader_student_id INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY assignment_id (assignment_id),
  KEY leader_student_id (leader_student_id),
  CONSTRAINT fk_teacher_assignment_groups_assignment FOREIGN KEY (assignment_id) REFERENCES teacher_assignments(id) ON DELETE CASCADE,
  CONSTRAINT fk_teacher_assignment_groups_leader FOREIGN KEY (leader_student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
$conn->query($createAssignmentGroupsTableSql);

$createAssignmentGroupMembersTableSql = "CREATE TABLE IF NOT EXISTS teacher_assignment_group_members (
  id INT NOT NULL AUTO_INCREMENT,
  group_id INT NOT NULL,
  student_id INT NOT NULL,
  PRIMARY KEY (id),
  KEY group_id (group_id),
  KEY student_id (student_id),
  CONSTRAINT fk_teacher_assignment_group_members_group FOREIGN KEY (group_id) REFERENCES teacher_assignment_groups(id) ON DELETE CASCADE,
  CONSTRAINT fk_teacher_assignment_group_members_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
$conn->query($createAssignmentGroupMembersTableSql);

$instructorResult = $conn->query("SELECT * FROM instructors WHERE id = $instructorId LIMIT 1");
if ($instructorResult) {
    $instructor = $instructorResult->fetch_assoc();
    if ($instructor) {
        $subjectStructure = json_decode($instructor['subjects'] ?? '', true) ?: [];
    }
}

function fetchStudentCount($conn, $grade, $section) {
    $gradeEsc = $conn->real_escape_string($grade);
    $sectionEsc = $conn->real_escape_string($section);
    $result = $conn->query("SELECT COUNT(*) AS count FROM students WHERE grade_level = '$gradeEsc' AND section = '$sectionEsc'");
    return $result ? intval($result->fetch_assoc()['count']) : 0;
}

$lessonAccessTableExists = $conn->query("SHOW TABLES LIKE 'teacher_lesson_access'");
if ($lessonAccessTableExists && $lessonAccessTableExists->num_rows > 0) {
    $colCheck = $conn->query("SHOW COLUMNS FROM teacher_lesson_access LIKE 'student_id'");
    if (!$colCheck || $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE teacher_lesson_access ADD COLUMN student_id INT NULL DEFAULT NULL AFTER lesson_id");
    }

    $fkCheck = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teacher_lesson_access' AND COLUMN_NAME = 'student_id' AND REFERENCED_TABLE_NAME = 'students' LIMIT 1");
    if ($fkCheck && $fkCheck->num_rows === 0) {
        $conn->query("ALTER TABLE teacher_lesson_access ADD CONSTRAINT fk_teacher_lesson_access_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL");
    }
}

$createLessonAccessTableSql = "CREATE TABLE IF NOT EXISTS teacher_lesson_access (
  id INT NOT NULL AUTO_INCREMENT,
  lesson_id INT NOT NULL,
  student_id INT NULL DEFAULT NULL,
  accessed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY lesson_id (lesson_id),
  KEY student_id (student_id),
  CONSTRAINT fk_teacher_lesson_access_lesson FOREIGN KEY (lesson_id) REFERENCES teacher_lessons(id) ON DELETE CASCADE,
  CONSTRAINT fk_teacher_lesson_access_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
$conn->query($createLessonAccessTableSql);

if (isset($_GET['download_lesson']) && isset($_GET['lesson_id'])) {
    $lessonId = intval($_GET['lesson_id']);
    $lessonQuery = $conn->query("SELECT file_path FROM teacher_lessons WHERE id = $lessonId AND instructor_id = $instructorId LIMIT 1");
    if ($lessonQuery && ($lessonRow = $lessonQuery->fetch_assoc()) && !empty($lessonRow['file_path'])) {
        $lessonPath = $lessonRow['file_path'];
        $studentId = null;
        $sessionRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
        if ($sessionRole === 'student') {
            $studentId = intval($_SESSION['user_id']);
        }
        $studentIdValue = $studentId ? $studentId : 'NULL';
        $conn->query("INSERT INTO teacher_lesson_access (lesson_id, student_id, accessed_at) VALUES ($lessonId, $studentIdValue, NOW())");
        if (strpos($lessonPath, '../') !== 0 && strpos($lessonPath, '/') !== 0) {
            $lessonPath = '../' . $lessonPath;
        }
        header('Location: ' . $lessonPath);
        exit;
    }
}

if (isset($_GET['assignment_submission_graph']) && isset($_GET['assignment_id']) && $selectedGrade && $selectedSubject && $selectedSection) {
    header('Content-Type: application/json');
    $assignmentId = intval($_GET['assignment_id']);
    $graphResult = [
        'normal' => 0,
        'late' => 0,
        'none' => 0,
    ];

    $assignmentMetaResult = $conn->query("SELECT due_date, due_time, grade_level, section, subject_name FROM teacher_assignments WHERE id = $assignmentId AND instructor_id = $instructorId LIMIT 1");
    $dueDateTime = null;
    $totalStudents = 0;
    if ($assignmentMetaResult && $assignmentMeta = $assignmentMetaResult->fetch_assoc()) {
        $dueDateTime = strtotime($assignmentMeta['due_date'] . ' ' . $assignmentMeta['due_time']);
        $totalStudents = fetchStudentCount($conn, $assignmentMeta['grade_level'], $assignmentMeta['section']);
    }

    $groupCountResult = $conn->query("SELECT COUNT(*) AS count FROM teacher_assignment_groups WHERE assignment_id = $assignmentId");
    $groupCount = $groupCountResult ? intval($groupCountResult->fetch_assoc()['count']) : 0;

    if ($groupCount > 0) {
        $groupSubmissionQuery = $conn->query("SELECT ag.id, MAX(tas.submitted_at) AS latest_submitted_at FROM teacher_assignment_groups ag LEFT JOIN teacher_assignment_submissions tas ON tas.assignment_id = ag.assignment_id AND tas.student_id = ag.leader_student_id AND tas.submission_file_path IS NOT NULL AND tas.submission_file_path <> '' WHERE ag.assignment_id = $assignmentId GROUP BY ag.id");
        if ($groupSubmissionQuery) {
            while ($row = $groupSubmissionQuery->fetch_assoc()) {
                if (!empty($row['latest_submitted_at']) && $dueDateTime !== null) {
                    $submittedAt = strtotime($row['latest_submitted_at']);
                    if ($submittedAt <= $dueDateTime) {
                        $graphResult['normal']++;
                    } else {
                        $graphResult['late']++;
                    }
                } else {
                    $graphResult['none']++;
                }
            }
        }
    } else {
        if ($dueDateTime !== null) {
            $submissionQuery = $conn->query("SELECT submitted_at FROM teacher_assignment_submissions WHERE assignment_id = $assignmentId AND submission_file_path IS NOT NULL AND submission_file_path <> ''");
            if ($submissionQuery) {
                while ($row = $submissionQuery->fetch_assoc()) {
                    $submittedAt = strtotime($row['submitted_at']);
                    if ($submittedAt <= $dueDateTime) {
                        $graphResult['normal']++;
                    } else {
                        $graphResult['late']++;
                    }
                }
            }
        } else {
            $submissionQuery = $conn->query("SELECT COUNT(*) AS count FROM teacher_assignment_submissions WHERE assignment_id = $assignmentId AND submission_file_path IS NOT NULL AND submission_file_path <> ''");
            if ($submissionQuery && $row = $submissionQuery->fetch_assoc()) {
                $graphResult['normal'] = intval($row['count']);
            }
        }
        $graphResult['none'] = max(0, $totalStudents - ($graphResult['normal'] + $graphResult['late']));
    }

    echo json_encode($graphResult);
    exit;
}

if (isset($_GET['activity_grade_data']) && isset($_GET['assignment_id'])) {
    header('Content-Type: application/json');
    $assignmentId = intval($_GET['assignment_id']);
    $assignmentMetaResult = $conn->query("SELECT title, description, grade_level, section, subject_name, max_points FROM teacher_assignments WHERE id = $assignmentId AND instructor_id = $instructorId LIMIT 1");
    $response = ['success' => false, 'students' => [], 'normal' => 0, 'late' => 0, 'none' => 0, 'total_students' => 0];
    if ($assignmentMetaResult && $assignmentMeta = $assignmentMetaResult->fetch_assoc()) {
        $response['success'] = true;
        $response['title'] = $assignmentMeta['title'];
        $response['description'] = $assignmentMeta['description'];
        $response['max_points'] = intval($assignmentMeta['max_points'] ?? 0);
        if ($response['max_points'] <= 0) {
            $fallbackMaxResult = $conn->query("SELECT MAX(grade_value) AS max_score FROM teacher_activity_grade_records WHERE assignment_id = $assignmentId");
            if ($fallbackMaxResult && $fallbackMaxRow = $fallbackMaxResult->fetch_assoc()) {
                $response['max_points'] = intval($fallbackMaxRow['max_score'] ?? 0);
            }
        }
        $gradeEsc = $conn->real_escape_string($assignmentMeta['grade_level']);
        $sectionEsc = $conn->real_escape_string($assignmentMeta['section']);
        $studentsCountResult = $conn->query("SELECT COUNT(*) AS count FROM students WHERE grade_level = '$gradeEsc' AND section = '$sectionEsc'");
        $response['total_students'] = $studentsCountResult ? intval($studentsCountResult->fetch_assoc()['count']) : 0;

        $studentQuery = $conn->query(
            "SELECT s.id, TRIM(CONCAT(IFNULL(s.last_name, ''), ', ', IFNULL(s.first_name, ''), IF(IFNULL(s.middle_name, '') != '', CONCAT(' ', s.middle_name), ''))) AS student_name, COALESCE(sub.status, 'not submitted') AS status, sub.submission_file_path, sub.submitted_at, gr.grade_value, gr.graded_at, gr.graded_by " .
            "FROM students s " .
            "LEFT JOIN (" .
            "  SELECT ta.student_id, ta.status, ta.submission_file_path, ta.submitted_at " .
            "  FROM teacher_assignment_submissions ta " .
            "  JOIN (" .
            "    SELECT student_id, MAX(submitted_at) AS max_submitted_at " .
            "    FROM teacher_assignment_submissions " .
            "    WHERE assignment_id = $assignmentId " .
            "    GROUP BY student_id" .
            "  ) latest_submissions ON latest_submissions.student_id = ta.student_id " .
            "      AND latest_submissions.max_submitted_at = ta.submitted_at " .
            "  WHERE ta.assignment_id = $assignmentId" .
            ") sub ON sub.student_id = s.id " .
            "LEFT JOIN (" .
            "  SELECT tgr.student_id, tgr.grade_value, tgr.graded_at, tgr.graded_by " .
            "  FROM teacher_activity_grade_records tgr " .
            "  JOIN (" .
            "    SELECT student_id, MAX(created_at) AS latest_created_at " .
            "    FROM teacher_activity_grade_records " .
            "    WHERE assignment_id = $assignmentId " .
            "    GROUP BY student_id" .
            "  ) latest_grades ON latest_grades.student_id = tgr.student_id " .
            "      AND latest_grades.latest_created_at = tgr.created_at " .
            "  WHERE tgr.assignment_id = $assignmentId" .
            ") gr ON gr.student_id = s.id " .
            "WHERE s.grade_level = '$gradeEsc' AND s.section = '$sectionEsc' " .
            "ORDER BY s.last_name, s.first_name"
        );

        if ($studentQuery) {
            while ($row = $studentQuery->fetch_assoc()) {
                $status = $row['status'] ?? null;
                $hasFile = !empty($row['submission_file_path']);

                if ($hasFile) {
                    $status = $status ?: 'submitted';
                } else {
                    $status = 'not submitted';
                }

                if ($status === 'late') {
                    $response['late']++;
                } elseif ($status === 'submitted') {
                    $response['normal']++;
                } else {
                    $response['none']++;
                }
                $submissionPaths = [];
                if (!empty($row['submission_file_path'])) {
                    $decodedPaths = json_decode($row['submission_file_path'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedPaths)) {
                        $submissionPaths = array_values(array_filter(array_map('trim', $decodedPaths)));
                    } else {
                        $splitPaths = strpos($row['submission_file_path'], '|') !== false ? explode('|', $row['submission_file_path']) : [$row['submission_file_path']];
                        $submissionPaths = array_values(array_filter(array_map('trim', $splitPaths)));
                    }
                }

                $response['students'][] = [
                    'id' => intval($row['id']),
                    'student_name' => $row['student_name'],
                    'status' => $status,
                    'file_path' => $submissionPaths ? $submissionPaths[0] : '',
                    'file_paths' => $submissionPaths,
                    'submitted_at' => $row['submitted_at'] ?? null,
                    'grade_value' => $row['grade_value'] !== null ? intval($row['grade_value']) : null,
                    'graded_at' => $row['graded_at'] ?? null,
                    'graded_by' => $row['graded_by'] ?? null,
                ];
            }
        }

    }
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_activity_grade'])) {
    header('Content-Type: application/json');
    $assignmentId = intval($_POST['assignment_id'] ?? 0);
    $studentId = intval($_POST['student_id'] ?? 0);
    $response = ['success' => false, 'message' => 'Invalid request.'];
    $gradingPeriod = trim($_POST['grading_period'] ?? '1st');
    $allowedPeriods = ['1st', '2nd', '3rd', '4th', 'Final'];
    if (!in_array($gradingPeriod, $allowedPeriods, true)) {
        $gradingPeriod = '1st';
    }
    if ($assignmentId > 0 && $studentId > 0) {
        $gradeResult = $conn->query("SELECT id FROM teacher_activity_grade_records WHERE assignment_id = $assignmentId AND student_id = $studentId ORDER BY created_at DESC LIMIT 1");
        if ($gradeResult && $gradeResult->num_rows > 0) {
            $deleteActivityResult = $conn->query("DELETE FROM teacher_activity_grade_records WHERE assignment_id = $assignmentId AND student_id = $studentId");
            if (!$deleteActivityResult) {
                $response['message'] = 'Failed to delete activity grade records: ' . $conn->error;
                echo json_encode($response);
                exit;
            }

            $deleteGradeRecordResult = $conn->query("DELETE FROM teacher_grade_records WHERE assignment_id = $assignmentId AND student_id = $studentId AND grading_period = '$gradingPeriod'");
            if ($deleteGradeRecordResult === false) {
                $response['message'] = 'Failed to delete grade records: ' . $conn->error;
                echo json_encode($response);
                exit;
            }

            $submissionResult = $conn->query("SELECT id, status, submission_file_path FROM teacher_assignment_submissions WHERE assignment_id = $assignmentId AND student_id = $studentId LIMIT 1");
            if ($submissionResult && $submissionResult->num_rows > 0) {
                $submissionRow = $submissionResult->fetch_assoc();
                $submissionFilePath = trim($submissionRow['submission_file_path'] ?? '');
                if ($submissionRow['status'] === 'graded') {
                    if ($submissionFilePath === '') {
                        $conn->query("UPDATE teacher_assignment_submissions SET status = 'not submitted' WHERE id = " . intval($submissionRow['id']));
                    } else {
                        $conn->query("UPDATE teacher_assignment_submissions SET status = 'submitted' WHERE id = " . intval($submissionRow['id']));
                    }
                }
            }
            $response['success'] = true;
            $response['message'] = 'Grade removed successfully.';
        } else {
            $response['message'] = 'No grade found for this student.';
        }
    }
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_activity_submission'])) {
    $assignmentId = intval($_POST['grade_assignment_id'] ?? 0);
    $studentId = intval($_POST['grade_student_id'] ?? 0);
    $mainScore = trim($_POST['grade_main_score'] ?? '');
    $extraScore = trim($_POST['grade_extra_points'] ?? '0');
    $gradingPeriod = trim($_POST['grading_period'] ?? '1st');
    $allowedPeriods = ['1st', '2nd', '3rd', '4th', 'Final'];
    if (!in_array($gradingPeriod, $allowedPeriods, true)) {
        $gradingPeriod = '1st';
    }
    $activity_error = '';
    if ($assignmentId <= 0 || $studentId <= 0 || $mainScore === '') {
        $activity_error = 'Please select an activity, student, and grade value.';
    } elseif (!ctype_digit($mainScore) || !ctype_digit($extraScore)) {
        $activity_error = 'Grade values must be whole numbers.';
    } else {
        $mainScore = intval($mainScore);
        $extraScore = intval($extraScore);
        $gradeValue = $mainScore + $extraScore;
        $assignmentResult = $conn->query("SELECT title, grade_level, section, subject_name, max_points FROM teacher_assignments WHERE id = $assignmentId AND instructor_id = $instructorId LIMIT 1");
        $studentResult = $conn->query("SELECT id, TRIM(CONCAT(IFNULL(first_name, ''), ' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''))) AS student_name, grade_level, section FROM students WHERE id = $studentId LIMIT 1");
        if (!$assignmentResult || !$studentResult || $assignmentResult->num_rows === 0 || $studentResult->num_rows === 0) {
            $activity_error = 'Invalid activity or student selected.';
        } else {
            $assignmentRow = $assignmentResult->fetch_assoc();
            $studentRow = $studentResult->fetch_assoc();
            $maxPoints = intval($assignmentRow['max_points'] ?? 0);
            if ($maxPoints <= 0) {
                $fallbackMaxResult = $conn->query("SELECT MAX(grade_value) AS max_score FROM teacher_activity_grade_records WHERE assignment_id = $assignmentId");
                if ($fallbackMaxResult && $fallbackMaxRow = $fallbackMaxResult->fetch_assoc()) {
                    $maxPoints = intval($fallbackMaxRow['max_score'] ?? 0);
                }
            }
            if ($maxPoints > 0 && ($mainScore > $maxPoints || $gradeValue > $maxPoints)) {
                $activity_error = 'Total score cannot exceed the assignment maximum of ' . $maxPoints . '.';
            } else {
                $status = 'not submitted';
                $submissionResult = $conn->query("SELECT id, status, submission_file_path FROM teacher_assignment_submissions WHERE assignment_id = $assignmentId AND student_id = $studentId LIMIT 1");
                if ($submissionResult && $submissionResult->num_rows > 0) {
                    $submissionRow = $submissionResult->fetch_assoc();
                    $status = $submissionRow['status'] ?: 'not submitted';
                    $submissionFilePath = $submissionRow['submission_file_path'] ?? null;
                } else {
                    $status = 'graded';
                    $submissionFilePath = null;
                    $stmt = $conn->prepare("INSERT INTO teacher_assignment_submissions (assignment_id, student_id, status) VALUES (?, ?, 'graded')");
                    $stmt->bind_param('ii', $assignmentId, $studentId);
                    $stmt->execute();
                    $stmt->close();
                }

                $gradedBy = trim(($instructor['first_name'] ?? '') . ' ' . ($instructor['last_name'] ?? ''));
                $activityTitle = $assignmentRow['title'];
                $studentName = $studentRow['student_name'];
                $statusValue = $status ?: 'not submitted';

                $effectiveGradingPeriod = $gradingPeriod;

                $existingGradeResult = $conn->query("SELECT id FROM teacher_activity_grade_records WHERE assignment_id = $assignmentId AND student_id = $studentId LIMIT 1");
                if ($existingGradeResult && $existingGradeResult->num_rows > 0) {
                    $existingGradeRow = $existingGradeResult->fetch_assoc();
                    $stmt = $conn->prepare("UPDATE teacher_activity_grade_records SET activity_title = ?, instructor_id = ?, instructor_name = ?, grade_level = ?, section = ?, subject_name = ?, status = ?, submission_file_path = ?, grade_value = ?, graded_at = NOW(), graded_by = ? WHERE id = ? LIMIT 1");
                    $stmt->bind_param('isssssssssi', $activityTitle, $instructorId, $gradedBy, $studentRow['grade_level'], $studentRow['section'], $assignmentRow['subject_name'], $statusValue, $submissionFilePath, $gradeValue, $gradedBy, $existingGradeRow['id']);
                } else {
                    $stmt = $conn->prepare("INSERT INTO teacher_activity_grade_records (assignment_id, activity_title, instructor_id, instructor_name, student_id, student_name, grade_level, section, subject_name, status, submission_file_path, grade_value, created_at, graded_at, graded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)");
                    $stmt->bind_param('isisssssssiis', $assignmentId, $activityTitle, $instructorId, $gradedBy, $studentId, $studentName, $studentRow['grade_level'], $studentRow['section'], $assignmentRow['subject_name'], $statusValue, $submissionFilePath, $gradeValue, $gradedBy);
                }
                if ($stmt->execute()) {
                    $activity_success = 'Grade recorded successfully.';
                } else {
                    $activity_error = 'Unable to save grade record: ' . $stmt->error;
                }
                $stmt->close();

                $existingHistoryResult = $conn->query("SELECT id FROM teacher_grade_records WHERE assignment_id = $assignmentId AND student_id = $studentId AND grading_period = '$effectiveGradingPeriod' LIMIT 1");
                if ($existingHistoryResult && $existingHistoryResult->num_rows > 0) {
                    $historyRow = $existingHistoryResult->fetch_assoc();
                    $stmt = $conn->prepare("UPDATE teacher_grade_records SET instructor_id = ?, instructor_name = ?, grade_level = ?, section = ?, subject_name = ?, grading_period = ?, activity_title = ?, student_name = ?, grade_value = ? WHERE id = ? LIMIT 1");
                    $stmt->bind_param('isssssssii', $instructorId, $gradedBy, $studentRow['grade_level'], $studentRow['section'], $assignmentRow['subject_name'], $effectiveGradingPeriod, $activityTitle, $studentName, $gradeValue, $historyRow['id']);
                } else {
                    $stmt = $conn->prepare("INSERT INTO teacher_grade_records (assignment_id, instructor_id, instructor_name, grade_level, section, subject_name, grading_period, activity_title, student_id, student_name, grade_value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param('iissssssisi', $assignmentId, $instructorId, $gradedBy, $studentRow['grade_level'], $studentRow['section'], $assignmentRow['subject_name'], $effectiveGradingPeriod, $activityTitle, $studentId, $studentName, $gradeValue);
                }
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

if (isset($_GET['lesson_access_graph']) && isset($_GET['lesson_id']) && $selectedGrade && $selectedSubject && $selectedSection) {
    header('Content-Type: application/json');
    $lessonId = intval($_GET['lesson_id']);
    $lessonGraph = [
        'accessed' => 0,
        'not_accessed' => 0,
        'total_students' => 0,
    ];

    $lessonMetaResult = $conn->query("SELECT grade_level, section, subject_name FROM teacher_lessons WHERE id = $lessonId AND instructor_id = $instructorId LIMIT 1");
    if ($lessonMetaResult && $meta = $lessonMetaResult->fetch_assoc()) {
        if ($meta['grade_level'] === $selectedGrade && $meta['section'] === $selectedSection && $meta['subject_name'] === $selectedSubject) {
            $lessonGraph['total_students'] = fetchStudentCount($conn, $meta['grade_level'], $meta['section']);
            $accessResult = $conn->query("SELECT COUNT(*) AS download_count, COUNT(DISTINCT student_id) AS students_downloaded FROM teacher_lesson_access WHERE lesson_id = $lessonId");
            if ($accessResult && $accessRow = $accessResult->fetch_assoc()) {
                $lessonGraph['download_count'] = intval($accessRow['download_count']);
                $lessonGraph['accessed'] = intval($accessRow['students_downloaded']);
            }
            $lessonGraph['not_accessed'] = max(0, $lessonGraph['total_students'] - $lessonGraph['accessed']);
        }
    }

    echo json_encode($lessonGraph);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_lesson'])) {
    $gradeLevel = trim($_POST['grade_level'] ?? '');
    $subjectName = trim($_POST['subject_name'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($gradeLevel === '' || $subjectName === '' || $section === '' || $title === '') {
        $lesson_error = 'Please complete the subject, grade, section, and title fields.';
    } else {
        $fileName = null;
        $filePath = null;

        if (!empty($_FILES['file']['name'])) {
            $originalName = basename($_FILES['file']['name']);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExtensions = ['pdf', 'docx', 'pptx', 'txt'];
            if (!in_array($extension, $allowedExtensions, true)) {
                $lesson_error = 'Only PDF, DOCX, PPTX, and TXT lesson files are allowed.';
            } else {
                $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
                $targetDir = __DIR__ . '/../assets/uploads/lessons/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $targetName = time() . '_' . $safeName;
                $targetFull = $targetDir . $targetName;

                if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFull)) {
                    $fileName = $targetName;
                    $filePath = 'assets/uploads/lessons/' . $targetName;
                } else {
                    $lesson_error = 'Unable to upload the lesson file. Please try again.';
                }
            }
        }

        if ($lesson_error === '') {
            $stmt = $conn->prepare("INSERT INTO teacher_lessons (instructor_id, instructor_name, grade_level, section, subject_name, grading_period, title, description, file_name, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $instructorName = trim(($instructor['first_name'] ?? '') . ' ' . ($instructor['last_name'] ?? ''));
            $stmt->bind_param('isssssssss', $instructorId, $instructorName, $gradeLevel, $section, $subjectName, $selectedPeriod, $title, $description, $fileName, $filePath);
            if ($stmt->execute()) {
                $lesson_success = 'Lesson uploaded successfully.';
                $selectedGrade = $gradeLevel;
                $selectedSubject = $subjectName;
                $selectedSection = $section;
            } else {
                $lesson_error = 'Unable to save lesson: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_lesson'])) {
    $lessonId = intval($_POST['lesson_id'] ?? 0);
    if ($lessonId > 0) {
        $deleteResult = $conn->query("DELETE FROM teacher_lessons WHERE id = $lessonId AND instructor_id = $instructorId");
        if ($deleteResult) {
            $lesson_success = 'Lesson deleted successfully.';
        } else {
            $lesson_error = 'Unable to delete lesson.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_assignment'])) {
    $assignmentId = intval($_POST['assignment_id'] ?? 0);
    if ($assignmentId > 0) {
        $deleteResult = $conn->query("DELETE FROM teacher_assignments WHERE id = $assignmentId AND instructor_id = $instructorId");
        if ($deleteResult) {
            $assignment_success = 'Assignment deleted successfully.';
        } else {
            $assignment_error = 'Unable to delete assignment.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lesson'])) {
    $lessonId = intval($_POST['lesson_id'] ?? 0);
    $title = trim($_POST['lesson_title'] ?? '');
    $description = trim($_POST['lesson_description'] ?? '');
    $existingFilePath = trim($_POST['lesson_existing_file_path'] ?? '');
    $existingFileName = trim($_POST['lesson_existing_file_name'] ?? '');
    $fileName = $existingFileName;
    $filePath = $existingFilePath;

    if ($lessonId <= 0 || $title === '') {
        $lesson_error = 'Lesson title is required for update.';
    } else {
        if (!empty($_FILES['lesson_file']['name'])) {
            $originalName = basename($_FILES['lesson_file']['name']);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExtensions = ['pdf', 'docx', 'pptx', 'txt'];
            if (!in_array($extension, $allowedExtensions, true)) {
                $lesson_error = 'Only PDF, DOCX, PPTX, and TXT lesson files are allowed.';
            } else {
                $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
                $targetDir = __DIR__ . '/../assets/uploads/lessons/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $targetName = time() . '_' . $safeName;
                $targetFull = $targetDir . $targetName;
                if (move_uploaded_file($_FILES['lesson_file']['tmp_name'], $targetFull)) {
                    $fileName = $targetName;
                    $filePath = 'assets/uploads/lessons/' . $targetName;
                } else {
                    $lesson_error = 'Unable to upload the lesson file. Please try again.';
                }
            }
        }
    }

    if ($lesson_error === '') {
        $stmt = $conn->prepare("UPDATE teacher_lessons SET title = ?, description = ?, file_name = ?, file_path = ? WHERE id = ? AND instructor_id = ?");
        $stmt->bind_param('ssssii', $title, $description, $fileName, $filePath, $lessonId, $instructorId);
        if ($stmt->execute()) {
            $lesson_success = 'Lesson updated successfully.';
        } else {
            $lesson_error = 'Unable to update lesson: ' . $stmt->error;
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_assignment'])) {
    $assignmentId = intval($_POST['assignment_id'] ?? 0);
    $title = trim($_POST['assignment_title'] ?? '');
    $description = trim($_POST['assignment_description'] ?? '');
    $assignmentType = trim($_POST['assignment_type'] ?? 'activity');
    $openDate = trim($_POST['assignment_open_date'] ?? '');
    $dueDate = trim($_POST['assignment_due_date'] ?? '');
    $openTime = trim($_POST['assignment_open_time'] ?? '');
    $dueTime = trim($_POST['assignment_due_time'] ?? '');
    $points = intval($_POST['assignment_points'] ?? 100);
    $existingFilePath = trim($_POST['assignment_existing_file_path'] ?? '');
    $existingFileName = trim($_POST['assignment_existing_file_name'] ?? '');
    $assignmentFileName = $existingFileName;
    $assignmentFilePath = $existingFilePath;

    if ($assignmentId <= 0 || $title === '' || $assignmentType === '' || $openDate === '' || $dueDate === '' || $openTime === '' || $dueTime === '') {
        $assignment_error = 'Please complete the assignment title, type, open date, open time, due date, and due time fields.';
    } else {
        if (!empty($_FILES['assignment_edit_file']['name'])) {
            $originalName = basename($_FILES['assignment_edit_file']['name']);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExtensions = ['pdf', 'docx', 'pptx', 'txt'];
            if (!in_array($extension, $allowedExtensions, true)) {
                $assignment_error = 'Only PDF, DOCX, PPTX, and TXT files are allowed for assignment attachments.';
            } else {
                $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
                $targetDir = __DIR__ . '/../assets/uploads/assignments/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $targetName = time() . '_' . $safeName;
                $targetFull = $targetDir . $targetName;
                if (move_uploaded_file($_FILES['assignment_edit_file']['tmp_name'], $targetFull)) {
                    $assignmentFileName = $targetName;
                    $assignmentFilePath = 'assets/uploads/assignments/' . $targetName;
                } else {
                    $assignment_error = 'Unable to upload the assignment file. Please try again.';
                }
            }
        }
    }

    if ($assignment_error === '') {
        $stmt = $conn->prepare("UPDATE teacher_assignments SET title = ?, description = ?, assignment_type = ?, open_date = ?, open_time = ?, due_date = ?, due_time = ?, assignment_file_name = ?, assignment_file_path = ?, max_points = ? WHERE id = ? AND instructor_id = ?");
        $stmt->bind_param('ssssssssssii', $title, $description, $assignmentType, $openDate, $openTime, $dueDate, $dueTime, $assignmentFileName, $assignmentFilePath, $points, $assignmentId, $instructorId);
        if ($stmt->execute()) {
            $assignment_success = 'Assignment updated successfully.';
        } else {
            $assignment_error = 'Unable to update assignment: ' . $stmt->error;
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment_group'])) {
    $assignmentId = intval($_POST['group_assignment_id'] ?? 0);
    $groupName = trim($_POST['group_name'] ?? '');
    $leaderId = intval($_POST['group_leader_id'] ?? 0);
    $memberIds = $_POST['group_member_ids'] ?? [];

    if ($assignmentId <= 0 || $groupName === '' || $leaderId <= 0) {
        $assignment_error = 'Group name and leader are required.';
    } else {
        if (!is_array($memberIds)) {
            $memberIds = [];
        }

        $memberIds = array_unique(array_filter(array_map('intval', $memberIds)));
        if (!in_array($leaderId, $memberIds, true)) {
            $memberIds[] = $leaderId;
        }

        $assignmentCheck = $conn->query("SELECT id FROM teacher_assignments WHERE id = $assignmentId AND instructor_id = $instructorId LIMIT 1");
        if (!$assignmentCheck || $assignmentCheck->num_rows === 0) {
            $assignment_error = 'Invalid assignment selected for grouping.';
        }
    }

    if ($assignment_error === '') {
        $stmt = $conn->prepare("INSERT INTO teacher_assignment_groups (assignment_id, group_name, leader_student_id) VALUES (?, ?, ?)");
        $stmt->bind_param('isi', $assignmentId, $groupName, $leaderId);
        if ($stmt->execute()) {
            $groupId = intval($stmt->insert_id);
            $stmt->close();
            $memberInsert = $conn->prepare("INSERT INTO teacher_assignment_group_members (group_id, student_id) VALUES (?, ?)");
            foreach ($memberIds as $memberId) {
                $memberInsert->bind_param('ii', $groupId, $memberId);
                $memberInsert->execute();
            }
            $memberInsert->close();
            $assignment_success = 'Group created successfully.';
        } else {
            $assignment_error = 'Unable to create group: ' . $stmt->error;
            $stmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
    $gradeLevel = trim($_POST['grade_level'] ?? '');
    $subjectName = trim($_POST['subject_name'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $assignmentType = trim($_POST['assignment_type'] ?? 'activity');
    $dueDate = trim($_POST['due_date'] ?? '');
    $openDate = trim($_POST['open_date'] ?? '');
    $openTime = trim($_POST['open_time'] ?? '');
    $dueTime = trim($_POST['due_time'] ?? '');
    $points = intval($_POST['points'] ?? 100);
    $assignmentPeriod = normalizeGradingPeriod(trim($_POST['assignment_period'] ?? $selectedPeriod));
    if (!in_array($assignmentPeriod, $allowedPeriods, true)) {
        $assignmentPeriod = '1st';
    }
    $assignmentFileName = null;
    $assignmentFilePath = null;

    if ($gradeLevel === '' || $subjectName === '' || $section === '' || $title === '' || $assignmentType === '' || $openDate === '' || $openTime === '' || $dueDate === '' || $dueTime === '') {
        $assignment_error = 'Please complete the subject, grade, section, title, type, open date, open time, due date, and due time fields.';
    } else {
        if (!empty($_FILES['assignment_file']['name'])) {
            $originalName = basename($_FILES['assignment_file']['name']);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExtensions = ['pdf', 'docx', 'pptx', 'txt'];
            if (!in_array($extension, $allowedExtensions, true)) {
                $assignment_error = 'Only PDF, DOCX, PPTX, and TXT files are allowed for assignment attachments.';
            } else {
                $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
                $targetDir = __DIR__ . '/../assets/uploads/assignments/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                $targetName = time() . '_' . $safeName;
                $targetFull = $targetDir . $targetName;
                if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $targetFull)) {
                    $assignmentFileName = $targetName;
                    $assignmentFilePath = 'assets/uploads/assignments/' . $targetName;
                } else {
                    $assignment_error = 'Unable to upload the assignment file. Please try again.';
                }
            }
        }
    }

    if ($assignment_error === '') {
        $stmt = $conn->prepare("INSERT INTO teacher_assignments (instructor_id, instructor_name, grade_level, section, subject_name, grading_period, assignment_type, title, description, open_date, open_time, due_date, due_time, assignment_file_name, assignment_file_path, max_points) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $instructorName = trim(($instructor['first_name'] ?? '') . ' ' . ($instructor['last_name'] ?? ''));
        $stmt->bind_param('issssisssssssssi', $instructorId, $instructorName, $gradeLevel, $section, $subjectName, $assignmentPeriod, $assignmentType, $title, $description, $openDate, $openTime, $dueDate, $dueTime, $assignmentFileName, $assignmentFilePath, $points);
        if ($stmt->execute()) {
            $assignment_success = 'Assignment created successfully.';
            $newlyCreatedAssignmentId = intval($stmt->insert_id);
            $selectedGrade = $gradeLevel;
            $selectedSubject = $subjectName;
            $selectedSection = $section;
            $stmt->close();
            header('Location: my_class.php?grade=' . urlencode($gradeLevel) . '&subject=' . urlencode($subjectName) . '&section=' . urlencode($section) . '&period=' . urlencode($assignmentPeriod) . '&tab=assignments');
            exit;
        } else {
            $assignment_error = 'Unable to save assignment: ' . $stmt->error;
            $stmt->close();
        }
    }
}

foreach ($subjectStructure as $gradeLevel => $subjects) {
    if (!is_array($subjects)) {
        continue;
    }
    foreach ($subjects as $subjectName => $subjectData) {
        $sections = $subjectData['sections'] ?? [];
        if (!is_array($sections)) {
            continue;
        }
        foreach ($sections as $sectionName => $sectionData) {
            $studentCount = fetchStudentCount($conn, $gradeLevel, $sectionName);
            $gradeEsc = $conn->real_escape_string($gradeLevel);
            $subjectEsc = $conn->real_escape_string($subjectName);
            $sectionEsc = $conn->real_escape_string($sectionName);
            $lessonCountResult = $conn->query("SELECT COUNT(*) AS count FROM teacher_lessons WHERE instructor_id = $instructorId AND grade_level = '$gradeEsc' AND subject_name = '$subjectEsc' AND section = '$sectionEsc' AND grading_period " . getGradingPeriodCondition($conn, $selectedPeriod));
            $lessonCount = $lessonCountResult ? intval($lessonCountResult->fetch_assoc()['count']) : 0;
            $assignmentCountResult = $conn->query("SELECT COUNT(*) AS count FROM teacher_assignments WHERE instructor_id = $instructorId AND grade_level = '$gradeEsc' AND subject_name = '$subjectEsc' AND section = '$sectionEsc' AND grading_period " . getGradingPeriodCondition($conn, $selectedPeriod));
            $assignmentCount = $assignmentCountResult ? intval($assignmentCountResult->fetch_assoc()['count']) : 0;
            $gradedCountResult = $conn->query("SELECT COUNT(*) AS count FROM teacher_grade_records WHERE grade_level = '$gradeEsc' AND section = '$sectionEsc' AND subject_name = '$subjectEsc' AND grading_period " . getGradingPeriodCondition($conn, $selectedPeriod));
            $gradedCount = $gradedCountResult ? intval($gradedCountResult->fetch_assoc()['count']) : 0;

            $subjectGroups[] = [
                'grade_level' => $gradeLevel,
                'subject_name' => $subjectName,
                'section' => $sectionName,
                'students_count' => $studentCount,
                'lessons_count' => $lessonCount,
                'assignments_count' => $assignmentCount,
                'graded_count' => $gradedCount,
            ];
        }
    }
}

$lessons = [];
$assignments = [];
$students = [];
if ($selectedGrade && $selectedSubject && $selectedSection) {
    $gradeEsc = $conn->real_escape_string($selectedGrade);
    $sectionEsc = $conn->real_escape_string($selectedSection);
    $studentResult = $conn->query("SELECT id, TRIM(CONCAT(IFNULL(first_name, ''), ' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''))) AS student_name FROM students WHERE grade_level = '$gradeEsc' AND section = '$sectionEsc' ORDER BY last_name, first_name");
    if ($studentResult) {
        while ($row = $studentResult->fetch_assoc()) {
            $students[] = $row;
        }
    }
    $gradeEsc = $conn->real_escape_string($selectedGrade);
    $subjectEsc = $conn->real_escape_string($selectedSubject);
    $sectionEsc = $conn->real_escape_string($selectedSection);
    $lessonResult = $conn->query("SELECT * FROM teacher_lessons WHERE instructor_id = $instructorId AND grade_level = '$gradeEsc' AND subject_name = '$subjectEsc' AND section = '$sectionEsc' AND grading_period " . getGradingPeriodCondition($conn, $selectedPeriod) . " ORDER BY created_at DESC");
    if ($lessonResult) {
        $lessons = $lessonResult->fetch_all(MYSQLI_ASSOC);
    }

    $assignmentResult = $conn->query("SELECT * FROM teacher_assignments WHERE instructor_id = $instructorId AND grade_level = '$gradeEsc' AND subject_name = '$subjectEsc' AND section = '$sectionEsc' AND grading_period " . getGradingPeriodCondition($conn, $selectedPeriod) . " ORDER BY due_date ASC, due_time ASC");
    if ($assignmentResult) {
        $assignments = $assignmentResult->fetch_all(MYSQLI_ASSOC);
        foreach ($assignments as &$assignment) {
            $assignmentId = intval($assignment['id']);
            $assignment['total_students'] = fetchStudentCount($conn, $selectedGrade, $selectedSection);
            $assignment['submitted_count'] = 0;
            $assignmentOpenDate = $assignment['open_date'] ?? $assignment['due_date'];
            $assignmentOpenTime = $assignment['open_time'] ?? '00:00:00';
            $assignment['is_open'] = strtotime($assignmentOpenDate . ' ' . $assignmentOpenTime) <= time();

            $submittedCountResult = $conn->query("SELECT COUNT(*) AS count FROM teacher_assignment_submissions WHERE assignment_id = $assignmentId AND submission_file_path IS NOT NULL AND submission_file_path <> ''");
            $assignment['submitted_count'] = $submittedCountResult ? intval($submittedCountResult->fetch_assoc()['count']) : 0;
            $assignment['no_submissions'] = max(0, $assignment['total_students'] - $assignment['submitted_count']);

            $statusCountResult = $conn->query("SELECT
                    SUM(CASE WHEN submitted_at <= CONCAT('" . $conn->real_escape_string($assignment['due_date']) . "', ' ', '" . $conn->real_escape_string($assignment['due_time']) . "') THEN 1 ELSE 0 END) AS normal_count,
                    SUM(CASE WHEN submitted_at > CONCAT('" . $conn->real_escape_string($assignment['due_date']) . "', ' ', '" . $conn->real_escape_string($assignment['due_time']) . "') THEN 1 ELSE 0 END) AS late_count
                FROM teacher_assignment_submissions
                WHERE assignment_id = $assignmentId AND submission_file_path IS NOT NULL AND submission_file_path <> ''");
            if ($statusCountResult) {
                $statusCounts = $statusCountResult->fetch_assoc();
                $assignment['normal_submissions'] = intval($statusCounts['normal_count']);
                $assignment['late_submissions'] = intval($statusCounts['late_count']);
            } else {
                $assignment['normal_submissions'] = 0;
                $assignment['late_submissions'] = 0;
            }

            $assignment['submissions'] = [];
            $submissionResult = $conn->query("SELECT s.id, TRIM(CONCAT(IFNULL(s.first_name, ''), ' ', IFNULL(s.middle_name, ''), ' ', IFNULL(s.last_name, ''))) AS student_name, tas.submitted_at, tas.status FROM teacher_assignment_submissions tas JOIN students s ON tas.student_id = s.id WHERE tas.assignment_id = $assignmentId AND tas.submission_file_path IS NOT NULL AND tas.submission_file_path <> '' ORDER BY tas.submitted_at DESC");
            if ($submissionResult) {
                while ($row = $submissionResult->fetch_assoc()) {
                    $assignment['submissions'][] = $row;
                }
            }
        }
        unset($assignment);
    }
}

$recentlyAccessedLessons = [];
$recentAccessResult = $conn->query("SELECT l.id, l.title, l.file_name, MAX(a.accessed_at) AS last_accessed, COUNT(*) AS access_count FROM teacher_lesson_access a JOIN teacher_lessons l ON a.lesson_id = l.id WHERE l.instructor_id = $instructorId GROUP BY l.id ORDER BY last_accessed DESC LIMIT 5");
if ($recentAccessResult) {
    while ($row = $recentAccessResult->fetch_assoc()) {
        $recentlyAccessedLessons[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>MUCAHUB - My Class</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <style>
        .my-class-container {
            padding: 20px;
        }
        
        .subject-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .subject-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(85, 107, 47, 0.2);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(85, 107, 47, 0.3);
            border-color: #556b2f;
        }
        
        .subject-card h3 {
            color: #1a1a2e;
            margin: 0 0 10px 0;
            font-size: 1.3em;
        }
        
        .subject-card .grade-tag {
            display: inline-block;
            background: linear-gradient(135deg, #556b2f 0%, #6b8e23 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85em;
        }
        
        .subject-card .stats {
            margin-top: 15px;
            display: flex;
            gap: 15px;
            color: #666;
            font-size: 0.9em;
        }
        
        .subject-card .stats span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Detail View */
        .detail-view {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(85, 107, 47, 0.2);
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
        }
        
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #556b2f;
        }
        
        .detail-header h2 {
            color: #1a1a2e;
            margin: 0;
        }
        
        .back-btn {
            background: #556b2f;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: #6b8e23;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .tab-btn {
            padding: 12px 24px;
            border: none;
            background: rgba(85, 107, 47, 0.1);
            color: #556b2f;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .tab-btn:hover, .tab-btn.active {
            background: linear-gradient(135deg, #556b2f 0%, #6b8e23 100%);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Forms */
        .form-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-card h3 {
            color: #1a1a2e;
            margin: 0 0 15px 0;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: #556b2f;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #556b2f 0%, #6b8e23 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(85, 107, 47, 0.4);
        }
        
        /* Lists */
        .item-list {
            background: white;
            border-radius: 10px;
            overflow: visible;
            max-height: none;
        }
        
        .item-list table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .item-list tbody,
        .item-list thead,
        .item-list tr,
        .item-list td,
        .item-list th {
            max-height: none;
        }
        
        .item-list th {
            background: #556b2f;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        .item-list td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .item-list tr:hover {
            background: rgba(85, 107, 47, 0.1);
        }
        
        .file-link {
            color: #556b2f;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .file-link:hover {
            text-decoration: underline;
        }
        
        .grade-input {
            width: 80px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .no-items {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .no-subjects {
            text-align: center;
            padding: 60px;
            color: #6b8e23;
        }
        
        .no-subjects i {
            font-size: 3em;
            margin-bottom: 20px;
        }

        .assignment-row,
        .activity-row {
            cursor: pointer;
        }

        .assignment-row:hover,
        .activity-row:hover {
            background: rgba(85, 107, 47, 0.08);
        }

        .highlight-flash {
            animation: pulse-beat 0.75s ease-out 2;
            border: 2px solid #5f9127;
            background: rgba(95, 145, 39, 0.12);
        }

        @keyframes pulse-beat {
            0% { box-shadow: 0 0 0 0 rgba(95, 145, 39, 0.6); }
            50% { box-shadow: 0 0 18px 8px rgba(95, 145, 39, 0.25); }
            100% { box-shadow: 0 0 0 0 rgba(95, 145, 39, 0); }
        }

        .activity-student-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        .activity-student-table th,
        .activity-student-table td {
            padding: 10px 12px;
            border: 1px solid #e8e8e8;
            text-align: left;
            font-size: 0.95rem;
        }

        .activity-student-table td {
            vertical-align: middle;
        }

        .grade-form-panel {
            display: none;
            padding: 18px;
            margin-top: 16px;
            background: #fff;
            border: 1px solid #e4e4e4;
            border-radius: 12px;
        }

        .grade-form-panel.active {
            display: block;
        }

        .grade-form-panel .form-group {
            margin-bottom: 12px;
        }

        .grade-form-panel label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
        }

        .grade-form-panel input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            font-size: 0.95rem;
            box-sizing: border-box;
        }

        .grade-form-panel .modal-actions {
            margin-top: 12px;
        }

        .modal-content {
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }

        .modal-body {
            max-height: calc(100vh - 220px);
            overflow-y: auto;
        }

        .grade-button {
            background: #556b2f;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 6px 10px;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .grade-button:hover {
            background: #3e561f;
        }

        .grade-value {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            color: #333;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            justify-content: center;
            align-items: center;
            z-index: 9999;
            padding: 20px;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            width: min(100%, 820px);
            background: white;
            border-radius: 14px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            animation: fadeIn 0.25s ease;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px 24px;
            border-bottom: 1px solid #eee;
            background: #f7fff7;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.2rem;
            color: #1a1a2e;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #c82333;
            cursor: pointer;
        }

        .modal-body {
            padding: 24px;
            display: grid;
            gap: 18px;
        }

        .modal-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: flex-start;
            padding: 10px 0 0;
        }

        .modal-actions .inline-form {
            margin: 0;
        }

        .modal-actions button,
        .modal-actions .inline-form button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid transparent;
            background: #f0f0f0;
            color: #333;
            cursor: pointer;
        }

        .modal-actions .danger-btn {
            background: #dc3545;
            color: #fff;
            border-color: #c82333;
        }

        .modal-actions .secondary-btn {
            background: #6c757d;
            color: #fff;
            border-color: #5a6268;
        }

        .modal-edit-form {
            background: #fff;
            border: 1px solid #d3d3d3;
            border-radius: 12px;
            padding: 16px;
            margin-top: 14px;
        }

        .modal-edit-form.hidden {
            display: none;
        }

        .student-checklist {
            display: grid;
            gap: 8px;
            max-height: 240px;
            overflow-y: auto;
            padding: 8px;
            background: #fff;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
        }

        .group-student-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }

        .modal-body .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
        }

        .modal-body .detail-card {
            background: #fafafa;
            border: 1px solid #e6f2d9;
            border-radius: 10px;
            padding: 16px;
        }

        #assignmentStatusChart,
        #lessonAccessChart {
            width: 100% !important;
            max-width: 100%;
            height: auto !important;
            max-height: 240px;
        }

        .modal-body .detail-card strong {
            display: block;
            margin-bottom: 8px;
            color: #556b2f;
        }

        .modal-body .description {
            white-space: pre-wrap;
            line-height: 1.6;
            color: #333;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* BEATING ANIMATION FOR HIGHLIGHTED MATERIALS */
        .lesson-row.highlight-active {
            animation: beat-highlight 1.2s ease 2;
            background-color: #fff9e6 !important;
            border-left: 4px solid #1d4ed8;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12) !important;
        }

        .activity-row.highlight-active {
            animation: beat-highlight 1.2s ease 2;
            background-color: #fff9e6 !important;
            border-left: 4px solid #1d4ed8;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12) !important;
        }

        @keyframes beat-highlight {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        /* NOTIFICATION PANEL STYLES */
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
            color: #556b2f;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .notification-item {
            padding: 12px 20px;
            margin: 0 8px 8px 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
            border-left: 4px solid #556b2f;
        }

        .notification-item:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .notification-announcement {
            background: #eef6df;
        }

        .notification-announcement:hover {
            background: #e0f0ce;
        }

        .notification-material {
            background: #e8f4f8;
        }

        .notification-material:hover {
            background: #d8ebf3;
        }

        .notification-item-title {
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 4px;
            font-size: 0.95rem;
        }

        .notification-item-meta {
            font-size: 0.85rem;
            color: #666;
        }

        .notification-item-empty {
            padding: 12px 20px;
            color: #999;
            font-size: 0.9rem;
            text-align: center;
        }
    </style>
    
    <script>
        function showTab(tabId, evt) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            if (evt && evt.target) {
                evt.target.classList.add('active');
            }
            history.replaceState(null, '', '#' + tabId);
            var activeTabInput = document.querySelector('input[name="active_tab"]');
            if (activeTabInput) {
                activeTabInput.value = tabId;
            }
        }

        function openAssignmentModalById(assignmentId) {
            if (!assignmentId) {
                return;
            }
            var row = document.querySelector('.assignment-row[data-id="' + assignmentId + '"]');
            if (row) {
                buildAssignmentModal(row);
            }
        }

        var newlyCreatedAssignmentId = <?php echo isset($newlyCreatedAssignmentId) && $newlyCreatedAssignmentId ? intval($newlyCreatedAssignmentId) : 'null'; ?>;

        function focusAssignmentRow(assignmentId) {
            if (!assignmentId) {
                return;
            }
            var row = document.querySelector('.activity-row[data-id="' + assignmentId + '"]');
            if (!row) {
                return;
            }
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.add('highlight-flash');
            setTimeout(function() {
                row.classList.remove('highlight-flash');
            }, 1700);
        }

        document.addEventListener('DOMContentLoaded', function() {
            var hashTab = window.location.hash ? window.location.hash.substring(1) : '';
            if (['lessons', 'assignments', 'grades'].includes(hashTab)) {
                showTab(hashTab, null);
            }
            if (newlyCreatedAssignmentId) {
                showTab('assignments', null);
                openAssignmentModalById(newlyCreatedAssignmentId);
            }
            var focusAssignmentId = <?php echo intval($_GET['assignment_id'] ?? 0); ?>;
            if (focusAssignmentId) {
                showTab('grades', null);
                focusAssignmentRow(focusAssignmentId);
            }

            // Handle hash navigation for materials (from notifications)
            highlightMaterialFromHash();
        });

        // Function to focus and animate material row when navigated from hash
        function highlightMaterialFromHash() {
            var hash = window.location.hash;
            if (!hash || !hash.startsWith('#material-')) {
                return;
            }

            var materialId = hash.substring('#material-'.length);
            if (!materialId) {
                return;
            }

            var row = document.querySelector('.lesson-row[data-id="' + materialId + '"]');
            if (!row) {
                return;
            }

            // Switch to lessons tab
            showTab('lessons', null);

            // Scroll into view and apply beating animation
            setTimeout(function() {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                row.classList.add('highlight-active');

                // Remove animation class after 2 seconds
                setTimeout(function() {
                    row.classList.remove('highlight-active');
                }, 2400);
            }, 100);
        }

        // Navigate to announcement from notification
        function navigateToAnnouncement(announcementId) {
            const activityPanel = document.getElementById('activityPanel');
            if (activityPanel) {
                activityPanel.classList.remove('active');
            }
            window.location.href = `home.php#announcement-${announcementId}`;
        }

        // Navigate to material from notification
        function navigateToMaterial(materialId) {
            const activityPanel = document.getElementById('activityPanel');
            if (activityPanel) {
                activityPanel.classList.remove('active');
            }
            window.location.href = `my_class.php#material-${materialId}`;
        }

        // Navigate to activity grades tab with beating animation
        function navigateToActivityGrades(assignmentId) {
            const activityPanel = document.getElementById('activityPanel');
            if (activityPanel) {
                activityPanel.classList.remove('active');
            }
            window.location.href = `my_class.php?assignment_id=${assignmentId}&tab=grades`;
        }

        // Highlight activity row when navigated from notification
        function highlightActivityFromHash() {
            var hash = window.location.hash;
            var params = new URLSearchParams(window.location.search);
            var assignmentId = params.get('assignment_id');

            if (!assignmentId) {
                return;
            }

            var row = document.querySelector('.activity-row[data-id="' + assignmentId + '"]');
            if (!row) {
                return;
            }

            // Switch to grades tab
            var gradesTab = document.getElementById('grades');
            if (gradesTab) {
                showTab('grades', null);
            }

            // Scroll into view and apply beating animation
            setTimeout(function() {
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                row.classList.add('highlight-active');

                // Remove animation class after 2.4 seconds
                setTimeout(function() {
                    row.classList.remove('highlight-active');
                }, 2400);
            }, 100);
        }
    </script>
</head>

<body>

<!-- SIDEBAR -->
<?php include "../includes/sidebar_instructor.php"; ?>

<!-- MAIN CONTENT -->
<div class="main">
    <div class="my-class-container">
        <h2><i class="fa fa-chalkboard"></i> My Class</h2>

        <div class="grading-period-selector" style="margin: 16px 0 20px; display:flex; flex-wrap:wrap; align-items:center; gap:12px;">
            <form method="GET" style="display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin:0;">
                <?php foreach ($allowedPeriods as $periodOption): ?>
                    <label style="display:flex; align-items:center; gap:6px;">
                        <input type="radio" name="period" value="<?php echo $periodOption; ?>" <?php echo $selectedPeriod === $periodOption ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($periodOption . ' Grading'); ?>
                    </label>
                <?php endforeach; ?>
                <button type="submit" class="secondary-btn" style="padding:8px 14px;">Change Period</button>
            </form>
        </div>

        <?php if(isset($lesson_success)): ?>
            <div class="success-msg"><?php echo $lesson_success; ?></div>
        <?php endif; ?>
        
        <?php if(isset($assignment_success) && $assignment_success): ?>
            <div class="success-msg"><?php echo $assignment_success; ?></div>
        <?php endif; ?>
        <?php if(isset($assignment_error) && $assignment_error): ?>
            <div class="success-msg" style="background:#f8d7da;color:#842029;border-color:#f5c2c7;">
                <?php echo htmlspecialchars($assignment_error); ?>
            </div>
        <?php endif; ?>
        <?php if(isset($activity_success) && $activity_success): ?>
            <div class="success-msg"><?php echo htmlspecialchars($activity_success); ?></div>
        <?php endif; ?>
        <?php if(isset($activity_error) && $activity_error): ?>
            <div class="success-msg" style="background:#f8d7da;color:#842029;border-color:#f5c2c7;">
                <?php echo htmlspecialchars($activity_error); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($grade_success)): ?>
            <div class="success-msg"><?php echo $grade_success; ?></div>
        <?php endif; ?>
        
        <?php if(count($subjectGroups) == 0): ?>
            <div class="no-subjects">
                <i class="fa fa-book"></i>
                <h3>No Assigned Subjects</h3>
                <p>Your current subject assignments are not available. Contact your administrator if you need your grade, subject, or section added.</p>
            </div>
        <?php elseif($selectedGrade && $selectedSubject && $selectedSection): ?>
            <div class="detail-view">
                <div class="detail-header">
                    <div>
                        <h2><i class="fa fa-book"></i> <?php echo htmlspecialchars($selectedSubject); ?></h2>
                        <p style="margin:4px 0 0; color:#556b2f; font-weight:600;"><?php echo htmlspecialchars($selectedGrade . ' - ' . $selectedSection); ?></p>
                    </div>
                    <a href="my_class.php<?php echo $selectedPeriod ? '?period=' . urlencode($selectedPeriod) : ''; ?>" class="back-btn"><i class="fa fa-arrow-left"></i> Back to Subjects</a>
                </div>

                <div class="grading-period-selector" style="margin:18px 0 12px; display:flex; flex-wrap:wrap; align-items:center; gap:12px;">
                    <form method="GET" style="display:flex; flex-wrap:wrap; align-items:center; gap:12px;">
                        <input type="hidden" name="grade" value="<?php echo htmlspecialchars($selectedGrade); ?>">
                        <input type="hidden" name="subject" value="<?php echo htmlspecialchars($selectedSubject); ?>">
                        <input type="hidden" name="section" value="<?php echo htmlspecialchars($selectedSection); ?>">
                        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($activeTab); ?>">
                        <?php foreach ($allowedPeriods as $periodOption): ?>
                            <label style="font-size:0.95rem; display:flex; align-items:center; gap:6px;">
                                <input type="radio" name="period" value="<?php echo $periodOption; ?>" <?php echo $selectedPeriod === $periodOption ? 'checked' : ''; ?>>
                                <?php echo htmlspecialchars($periodOption . ' Grading'); ?>
                            </label>
                        <?php endforeach; ?>
                        <button type="submit" class="secondary-btn" style="padding:8px 14px;">Change Period</button>
                    </form>
                </div>

                <?php if ($lesson_error): ?>
                    <div class="success-msg" style="background:#f8d7da;color:#842029;border-color:#f5c2c7;">
                        <?php echo htmlspecialchars($lesson_error); ?>
                    </div>
                <?php endif; ?>
                <?php if ($lesson_success): ?>
                    <div class="success-msg"><?php echo htmlspecialchars($lesson_success); ?></div>
                <?php endif; ?>

                <div class="tabs">
                    <button class="tab-btn <?php echo $activeTab === 'lessons' ? 'active' : ''; ?>" onclick="showTab('lessons', event)"><i class="fa fa-book-open"></i> Lessons</button>
                    <button class="tab-btn <?php echo $activeTab === 'assignments' ? 'active' : ''; ?>" onclick="showTab('assignments', event)"><i class="fa fa-tasks"></i> Assignments</button>
                    <button class="tab-btn <?php echo $activeTab === 'grades' ? 'active' : ''; ?>" onclick="showTab('grades', event)"><i class="fa fa-pen"></i> Activity Grades</button>
                </div>

                <div id="lessons" class="tab-content <?php echo $activeTab === 'lessons' ? 'active' : ''; ?>">
                    <div class="form-card">
                        <h3><i class="fa fa-plus"></i> Add New Lesson</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="active_tab" value="<?php echo htmlspecialchars($activeTab); ?>">
                            <input type="hidden" name="period" value="<?php echo htmlspecialchars($selectedPeriod); ?>">
                            <input type="hidden" name="grade_level" value="<?php echo htmlspecialchars($selectedGrade); ?>">
                            <input type="hidden" name="subject_name" value="<?php echo htmlspecialchars($selectedSubject); ?>">
                            <input type="hidden" name="section" value="<?php echo htmlspecialchars($selectedSection); ?>">
                            <div class="form-group">
                                <label>Lesson Title</label>
                                <input type="text" name="title" required placeholder="Enter lesson title">
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" rows="3" placeholder="Enter lesson description"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Upload File (PDF, DOCX, PPTX, TXT)</label>
                                <input type="file" name="file" accept=".pdf,.docx,.pptx,.txt">
                            </div>
                            <button type="submit" name="upload_lesson" class="submit-btn">
                                <i class="fa fa-upload"></i> Upload Lesson
                            </button>
                        </form>
                    </div>

                    <div class="item-list">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>File</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($lessons) == 0): ?>
                                    <tr><td colspan="4" class="no-items">No lessons uploaded yet for this subject.</td></tr>
                                <?php else: ?>
                                    <?php foreach($lessons as $lesson): ?>
                                        <tr id="material-<?php echo intval($lesson['id']); ?>" class="lesson-row"
                                            data-id="<?php echo intval($lesson['id']); ?>"
                                            data-title="<?php echo htmlspecialchars($lesson['title'], ENT_QUOTES); ?>"
                                            data-description="<?php echo htmlspecialchars($lesson['description'], ENT_QUOTES); ?>"
                                            data-file-path="<?php echo htmlspecialchars($lesson['file_path'] ?? '', ENT_QUOTES); ?>"
                                            data-file-name="<?php echo htmlspecialchars($lesson['file_name'] ?? '', ENT_QUOTES); ?>"
                                            data-created="<?php echo date('F j, Y', strtotime($lesson['created_at'])); ?>"
                                        >
                                            <td><?php echo htmlspecialchars($lesson['title']); ?></td>
                                            <td><?php echo htmlspecialchars($lesson['description']); ?></td>
                                            <td>
                                                <?php if(!empty($lesson['file_path'])): ?>
                                                    <a href="my_class.php?download_lesson=1&lesson_id=<?php echo intval($lesson['id']); ?>" class="file-link" data-lesson-id="<?php echo intval($lesson['id']); ?>" data-lesson-title="<?php echo htmlspecialchars($lesson['title'], ENT_QUOTES); ?>" data-lesson-file-name="<?php echo htmlspecialchars($lesson['file_name'] ?? '', ENT_QUOTES); ?>" target="_blank">
                                                        <i class="fa fa-file"></i> View
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('F j, Y', strtotime($lesson['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="assignments" class="tab-content <?php echo $activeTab === 'assignments' ? 'active' : ''; ?>">
                    <div class="form-card">
                        <h3><i class="fa fa-plus"></i> Create New Assignment</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="active_tab" value="<?php echo htmlspecialchars($activeTab); ?>">
                            <input type="hidden" name="period" value="<?php echo htmlspecialchars($selectedPeriod); ?>">
                            <input type="hidden" name="grade_level" value="<?php echo htmlspecialchars($selectedGrade); ?>">
                            <input type="hidden" name="subject_name" value="<?php echo htmlspecialchars($selectedSubject); ?>">
                            <input type="hidden" name="section" value="<?php echo htmlspecialchars($selectedSection); ?>">
                            <input type="hidden" name="assignment_period" value="<?php echo htmlspecialchars($selectedPeriod); ?>">
                            <div class="form-group">
                                <label>Assignment Title</label>
                                <input type="text" name="title" required placeholder="Enter assignment title">
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" rows="3" placeholder="Enter assignment description"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Assignment Type</label>
                                <select name="assignment_type" required>
                                    <option value="activity">Activity</option>
                                    <option value="quiz">Quiz</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Open Date</label>
                                <input type="date" name="open_date" required>
                            </div>
                            <div class="form-group">
                                <label>Open Time</label>
                                <input type="time" name="open_time" required value="00:00">
                            </div>
                            <div class="form-group">
                                <label>Due Date</label>
                                <input type="date" name="due_date" required>
                            </div>
                            <div class="form-group">
                                <label>Due Time</label>
                                <input type="time" name="due_time" required value="23:59">
                            </div>
                            <div class="form-group">
                                <label>Attachment</label>
                                <input type="file" name="assignment_file" accept=".pdf,.docx,.pptx,.txt">
                            </div>
                            <div class="form-group">
                                <label>Total Points (Max Score)</label>
                                <input type="number" name="points" required placeholder="100" value="100" min="0">
                            </div>
                            <button type="submit" name="create_assignment" class="submit-btn">
                                <i class="fa fa-plus"></i> Create Assignment
                            </button>
                        </form>
                    </div>

                    <div class="item-list">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Open / Due</th>
                                    <th>Students</th>
                                    <th>Submitted</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($assignments) == 0): ?>
                                    <tr><td colspan="5" class="no-items">No assignments created yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach($assignments as $assignment): ?>
                                        <tr class="assignment-row"
                                            data-id="<?php echo intval($assignment['id']); ?>"
                                            data-title="<?php echo htmlspecialchars($assignment['title'], ENT_QUOTES); ?>"
                                            data-description="<?php echo htmlspecialchars($assignment['description'], ENT_QUOTES); ?>"
                                            data-open-date="<?php echo htmlspecialchars($assignment['open_date'] ?? '', ENT_QUOTES); ?>"
                                            data-open-time="<?php echo htmlspecialchars($assignment['open_time'] ?? '00:00:00', ENT_QUOTES); ?>"
                                            data-due="<?php echo date('d/m/Y g:i A', strtotime($assignment['due_date'] . ' ' . $assignment['due_time'])); ?>"
                                            data-due-date="<?php echo htmlspecialchars($assignment['due_date'], ENT_QUOTES); ?>"
                                            data-due-time="<?php echo htmlspecialchars($assignment['due_time'], ENT_QUOTES); ?>"
                                            data-points="<?php echo intval($assignment['max_points']); ?>"
                                            data-submitted="<?php echo intval($assignment['submitted_count']); ?>"
                                            data-students="<?php echo intval($assignment['total_students']); ?>"
                                            data-created="<?php echo date('d/m/Y', strtotime($assignment['created_at'])); ?>"
                                            data-file-path="<?php echo htmlspecialchars($assignment['assignment_file_path'] ?? '', ENT_QUOTES); ?>"
                                            data-file-name="<?php echo htmlspecialchars($assignment['assignment_file_name'] ?? '', ENT_QUOTES); ?>"
                                            data-normal="<?php echo intval($assignment['normal_submissions'] ?? 0); ?>"
                                            data-late="<?php echo intval($assignment['late_submissions'] ?? 0); ?>"
                                            data-none="<?php echo intval($assignment['no_submissions'] ?? 0); ?>"
                                        >
                                            <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                            <td><?php echo date('d/m/Y g:i A', strtotime(($assignment['open_date'] ?? $assignment['due_date']) . ' ' . ($assignment['open_time'] ?? '00:00:00'))); ?> / <?php echo date('d/m/Y g:i A', strtotime($assignment['due_date'] . ' ' . $assignment['due_time'])); ?></td>
                                            <td><?php echo intval($assignment['total_students']); ?></td>
                                            <td><?php echo intval($assignment['submitted_count']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($assignment['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="grades" class="tab-content <?php echo $activeTab === 'grades' ? 'active' : ''; ?>">
                    <?php if(count($assignments) == 0): ?>
                        <div class="no-items">
                            <i class="fa fa-pen"></i>
                            <p>No assignments available for grading.</p>
                            <p>Create assignments first in the Assignments tab.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($assignments as $assignment): ?>
                            <div class="form-card activity-row"
                                 data-id="<?php echo intval($assignment['id']); ?>"
                                 data-title="<?php echo htmlspecialchars($assignment['title'], ENT_QUOTES); ?>"
                                 data-description="<?php echo htmlspecialchars($assignment['description'], ENT_QUOTES); ?>"
                            >
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap;">
                                    <h3 style="margin:0; font-size:1.15rem;"><i class="fa fa-tasks"></i> <?php echo htmlspecialchars($assignment['title']); ?></h3>
                                    <div style="text-align:right; color:#444; font-size:0.95rem;">
                                        <div><strong>Due:</strong> <?php echo date('F j, Y g:i A', strtotime($assignment['due_date'] . ' ' . $assignment['due_time'])); ?></div>
                                        <div><?php echo intval($assignment['submitted_count']); ?> / <?php echo intval($assignment['total_students']); ?> submitted</div>
                                    </div>
                                </div>

                                <?php if(count($assignment['submissions']) > 0): ?>
                                    <div style="margin-top:14px; padding:14px; background:#f8f9fa; border-radius:10px;">
                                        <strong>Submission details</strong>
                                        <ul style="margin:10px 0 0 18px; padding-left:0; list-style:disc; color:#333;">
                                            <?php foreach($assignment['submissions'] as $submission): ?>
                                                <li><?php echo htmlspecialchars($submission['student_name']); ?> — <?php echo htmlspecialchars($submission['status']); ?> on <?php echo date('F j, Y g:i A', strtotime($submission['submitted_at'])); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <p style="margin-top:14px; color:#666;">No submissions yet.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="subject-grid">
                <?php foreach($subjectGroups as $subject): ?>
                    <a href="?grade=<?php echo urlencode($subject['grade_level']); ?>&subject=<?php echo urlencode($subject['subject_name']); ?>&section=<?php echo urlencode($subject['section']); ?><?php echo $selectedPeriod ? '&period=' . urlencode($selectedPeriod) : ''; ?>" class="subject-card" style="text-decoration: none;">
                        <h3><i class="fa fa-book"></i> <?php echo htmlspecialchars($subject['subject_name']); ?></h3>
                        <span class="grade-tag"><?php echo htmlspecialchars($subject['grade_level'] . ' - ' . $subject['section']); ?></span>
                        <div class="stats">
                            <span><i class="fa fa-users"></i> <?php echo intval($subject['students_count']); ?> Students</span>
                            <span><i class="fa fa-book-open"></i> <?php echo intval($subject['lessons_count']); ?> Lessons</span>
                            <span><i class="fa fa-tasks"></i> <?php echo intval($subject['assignments_count']); ?> Assignments</span>
                            <span><i class="fa fa-check-circle"></i> <?php echo intval($subject['graded_count']); ?> Graded</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

    <div id="assignmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="assignmentModalTitle">Assignment Details</h3>
                <button class="modal-close" onclick="closeAssignmentModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-card description">
                    <strong>Description</strong>
                    <div id="assignmentModalDescription">No description provided.</div>
                </div>
                <div class="details-grid">
                    <div class="detail-card">
                        <strong>Due Date & Time</strong>
                        <div id="assignmentModalDue"></div>
                    </div>
                    <div class="detail-card">
                        <strong>Open Date & Time</strong>
                        <div id="assignmentModalOpen"></div>
                    </div>
                    <div class="detail-card">
                        <strong>Students</strong>
                        <div id="assignmentModalStudents"></div>
                    </div>
                    <div class="detail-card">
                        <strong>Submitted</strong>
                        <div id="assignmentModalSubmitted"></div>
                    </div>
                    <div class="detail-card">
                        <strong>Created</strong>
                        <div id="assignmentModalCreated"></div>
                    </div>
                    <div class="detail-card">
                        <strong>Attachment</strong>
                        <div id="assignmentModalAttachment">-</div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="secondary-btn" onclick="toggleAssignmentEditForm(true)"><i class="fa fa-edit"></i> Edit</button>
                    <form id="deleteAssignmentForm" method="POST" class="inline-form">
                        <input type="hidden" name="period" value="<?php echo htmlspecialchars($selectedPeriod); ?>">
                        <input type="hidden" name="assignment_id" id="assignmentDeleteId" value="">
                        <button type="submit" name="delete_assignment" class="danger-btn"><i class="fa fa-trash"></i> Delete</button>
                    </form>
                </div>
                <div id="assignmentGroupForm" class="modal-edit-form hidden">
                    <form method="POST">
                        <input type="hidden" name="period" value="<?php echo htmlspecialchars($selectedPeriod); ?>">
                        <input type="hidden" name="group_assignment_id" id="groupAssignmentId" value="">
                        <input type="hidden" name="grade_level" value="<?php echo htmlspecialchars($selectedGrade); ?>">
                        <input type="hidden" name="subject_name" value="<?php echo htmlspecialchars($selectedSubject); ?>">
                        <input type="hidden" name="section" value="<?php echo htmlspecialchars($selectedSection); ?>">
                        <div class="form-group">
                            <label>Group Name / Number</label>
                            <input type="text" name="group_name" id="groupNameField" required placeholder="e.g. Group 1">
                        </div>
                        <div class="form-group">
                            <label>Leader</label>
                            <select name="group_leader_id" id="groupLeaderField" required>
                                <option value="">Select leader</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Members</label>
                            <div id="assignmentGroupStudents" class="student-checklist"></div>
                        </div>
                        <div class="modal-actions">
                            <button type="submit" name="create_assignment_group" class="submit-btn"><i class="fa fa-save"></i> Save Group</button>
                            <button type="button" class="secondary-btn" onclick="toggleGroupForm(false)"><i class="fa fa-times"></i> Cancel</button>
                        </div>
                    </form>
                </div>
                <div id="assignmentEditForm" class="modal-edit-form hidden">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="period" value="<?php echo htmlspecialchars($selectedPeriod); ?>">
                        <input type="hidden" name="assignment_id" id="assignmentEditId" value="">
                        <input type="hidden" name="assignment_existing_file_path" id="assignmentExistingFilePath" value="">
                        <input type="hidden" name="assignment_existing_file_name" id="assignmentExistingFileName" value="">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="assignment_title" id="assignmentTitleField" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="assignment_description" id="assignmentDescriptionField" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Assignment Type</label>
                            <select name="assignment_type" id="assignmentTypeField" required>
                                <option value="activity">Activity</option>
                                <option value="quiz">Quiz</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Open Date</label>
                            <input type="date" name="assignment_open_date" id="assignmentOpenDateField" required>
                        </div>
                        <div class="form-group">
                            <label>Open Time</label>
                            <input type="time" name="assignment_open_time" id="assignmentOpenTimeField" required>
                        </div>
                        <div class="form-group">
                            <label>Due Date</label>
                            <input type="date" name="assignment_due_date" id="assignmentDueDateField" required>
                        </div>
                        <div class="form-group">
                            <label>Due Time</label>
                            <input type="time" name="assignment_due_time" id="assignmentDueTimeField" required>
                        </div>
                        <div class="form-group">
                            <label>Total Points (Max Score)</label>
                            <input type="number" name="assignment_points" id="assignmentPointsField" required min="0">
                        </div>
                        <div class="form-group">
                            <label>Replace Attachment</label>
                            <input type="file" name="assignment_edit_file" accept=".pdf,.docx,.pptx,.txt">
                        </div>
                        <div class="modal-actions">
                            <button type="submit" name="update_assignment" class="submit-btn"><i class="fa fa-save"></i> Save</button>
                            <button type="button" class="secondary-btn" onclick="toggleAssignmentEditForm(false)"><i class="fa fa-times"></i> Cancel</button>
                        </div>
                    </form>
                </div>
                <div class="detail-card" style="padding:18px;">
                    <strong>Submission Status</strong>
                    <canvas id="assignmentStatusChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div id="activityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="activityModalTitle">Activity Details</h3>
                <button class="modal-close" onclick="closeActivityModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-card description">
                    <strong>Description</strong>
                    <div id="activityModalDescription">No description provided.</div>
                </div>
                <div class="details-grid">
                    <div class="detail-card">
                        <strong>Students</strong>
                        <div id="activityModalStudents"></div>
                    </div>
                    <div class="detail-card">
                        <strong>Submitted</strong>
                        <div id="activityModalSubmitted"></div>
                    </div>
                    <div class="detail-card">
                        <strong>Not submitted</strong>
                        <div id="activityModalNotSubmitted"></div>
                    </div>
                </div>
                <div class="modal-actions" style="margin-bottom: 18px;">
                    <button type="button" class="secondary-btn" onclick="exportActivitySubmissionReport()"><i class="fa fa-file-pdf"></i> Export Submissions</button>
                </div>
                <div class="detail-card">
                    <strong>Student Submissions</strong>
                    <table class="activity-student-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Status</th>
                                <th>File</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody id="activityStudentList"></tbody>
                    </table>
                </div>
                <form id="activityGradeForm" method="POST">
                    <input type="hidden" name="grade_activity_submission" value="1">
                    <input type="hidden" name="grade_assignment_id" id="gradeAssignmentId" value="">
                    <input type="hidden" name="grade_student_id" id="gradeStudentId" value="">
                    <input type="hidden" name="grade_value" id="gradeValue" value="">
                    <input type="hidden" name="grading_period" id="gradingPeriod" value="<?php echo htmlspecialchars($selectedPeriod); ?>">
                    <div id="gradeEntryPanel" class="detail-card grade-form-panel">
                        <strong>Record Activity Grade</strong>
                        <div class="form-group">
                            <label>Student</label>
                            <input type="text" id="gradeStudentNameDisplay" readonly>
                        </div>
                        <div class="form-group">
                            <label>Main Score</label>
                            <input type="number" name="grade_main_score" id="gradeMainScore" min="0" step="1" oninput="setActivityTotalScore()">
                        </div>
                        <div class="form-group">
                            <label>Additional Points</label>
                            <input type="number" name="grade_extra_points" id="gradeExtraPoints" min="0" step="1" value="0" oninput="setActivityTotalScore()">
                        </div>
                        <div class="form-group">
                            <label>Max Score</label>
                            <input type="text" id="gradeMaxScore" readonly>
                        </div>
                        <div class="form-group">
                            <label>Total Score</label>
                            <input type="text" id="gradeTotalDisplay" readonly>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="submit-btn" onclick="submitActivityGrade()"><i class="fa fa-save"></i> Save Grade</button>
                            <button type="button" class="secondary-btn" onclick="cancelGradeEntry()"><i class="fa fa-times"></i> Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script id="enrolledStudentsData" type="application/json">
        <?php echo json_encode($students); ?>
    </script>

    <div id="lessonModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="lessonModalTitle">Lesson Details</h3>
                <button class="modal-close" onclick="closeLessonModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-card description">
                    <strong>Description</strong>
                    <div id="lessonModalDescription">No description provided.</div>
                </div>
                <div class="details-grid">
                    <div class="detail-card">
                        <strong>Uploaded</strong>
                        <div id="lessonModalCreated"></div>
                    </div>
                    <div class="detail-card">
                        <strong>Students</strong>
                        <div id="lessonModalStudents">0</div>
                    </div>
                    <div class="detail-card">
                        <strong>Downloaded by</strong>
                        <div id="lessonModalDownloadedBy">0</div>
                    </div>
                    <div class="detail-card">
                        <strong>Attachment</strong>
                        <div id="lessonModalAttachment">-</div>
                    </div>
                </div>
                <div class="detail-card" style="padding:18px;">
                    <strong>Lesson Access</strong>
                    <canvas id="lessonAccessChart" height="180"></canvas>
                </div>
                <div class="modal-actions">
                    <button type="button" class="secondary-btn" onclick="toggleLessonEditForm(true)"><i class="fa fa-edit"></i> Edit</button>
                    <form id="deleteLessonForm" method="POST" class="inline-form">
                        <input type="hidden" name="period" value="<?php echo htmlspecialchars($selectedPeriod); ?>">
                        <input type="hidden" name="lesson_id" id="lessonDeleteId" value="">
                        <button type="submit" name="delete_lesson" class="danger-btn"><i class="fa fa-trash"></i> Delete</button>
                    </form>
                </div>
                <div id="lessonEditForm" class="modal-edit-form hidden">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="period" value="<?php echo htmlspecialchars($selectedPeriod); ?>">
                        <input type="hidden" name="lesson_id" id="lessonEditId" value="">
                        <input type="hidden" name="lesson_existing_file_path" id="lessonExistingFilePath" value="">
                        <input type="hidden" name="lesson_existing_file_name" id="lessonExistingFileName" value="">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="lesson_title" id="lessonTitleField" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="lesson_description" id="lessonDescriptionField" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Replace File</label>
                            <input type="file" name="lesson_file" accept=".pdf,.docx,.pptx,.txt">
                        </div>
                        <div class="modal-actions">
                            <button type="submit" name="update_lesson" class="submit-btn"><i class="fa fa-save"></i> Save</button>
                            <button type="button" class="secondary-btn" onclick="toggleLessonEditForm(false)"><i class="fa fa-times"></i> Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php include "../includes/back_to_top.php"; ?>
<?php include "../includes/footer.php"; ?>

<script>
    let assignmentStatusChart = null;
    let lessonAccessChart = null;
    let currentActivityAssignmentId = null;
    let currentActivityData = null;

    function buildAssignmentModal(row) {
        const title = row.dataset.title || 'Assignment Details';
        const description = row.dataset.description || 'No description provided.';
        const due = row.dataset.due || '-';
        const dueDate = row.dataset.dueDate || '';
        const dueTime = row.dataset.dueTime || '';
        const openDate = row.dataset.openDate || '';
        const openTime = row.dataset.openTime || '00:00:00';
        const submitted = row.dataset.submitted || '0';
        const openTimeParts = openTime.split(':');
        const openTimeFormatted = openTimeParts.length >= 2 ? `${((parseInt(openTimeParts[0], 10) % 12) || 12)}:${openTimeParts[1]} ${parseInt(openTimeParts[0], 10) >= 12 ? 'PM' : 'AM'}` : openTime;
        const openDateFormatted = openDate ? openDate.split('-').reverse().join('/') : '';
        const openDisplay = openDateFormatted ? `${openDateFormatted} ${openTimeFormatted}` : openTimeFormatted;
        const students = row.dataset.students || '0';
        const created = row.dataset.created || '-';
        const filePath = row.dataset.filePath || '';
        const fileName = row.dataset.fileName || '';
        const points = row.dataset.points || '100';
        const normal = parseInt(row.dataset.normal || '0', 10);
        const late = parseInt(row.dataset.late || '0', 10);
        const none = parseInt(row.dataset.none || '0', 10);
        const assignmentId = row.dataset.id || '';

        document.getElementById('assignmentModalTitle').textContent = title;
        document.getElementById('assignmentModalDescription').textContent = description;
        document.getElementById('assignmentModalDue').textContent = due;
        document.getElementById('assignmentModalOpen').textContent = openDisplay;
        document.getElementById('assignmentModalSubmitted').textContent = submitted;
        document.getElementById('assignmentModalStudents').textContent = students;
        document.getElementById('assignmentModalCreated').textContent = created;
        document.getElementById('assignmentDeleteId').value = assignmentId;
        document.getElementById('assignmentEditId').value = assignmentId;
        document.getElementById('groupAssignmentId').value = assignmentId;
        document.getElementById('assignmentTitleField').value = title;
        document.getElementById('assignmentDescriptionField').value = description;
        document.getElementById('assignmentOpenDateField').value = openDate;
        document.getElementById('assignmentDueDateField').value = dueDate;
        document.getElementById('assignmentOpenTimeField').value = openTime;
        document.getElementById('assignmentDueTimeField').value = dueTime;
        document.getElementById('assignmentPointsField').value = points;
        document.getElementById('assignmentExistingFilePath').value = filePath;
        document.getElementById('assignmentExistingFileName').value = fileName;
        toggleAssignmentEditForm(false);
        toggleGroupForm(false);
        populateGroupStudentList();

        const attachmentEl = document.getElementById('assignmentModalAttachment');
        attachmentEl.textContent = '';
        if (filePath && fileName) {
            const link = document.createElement('a');
            link.href = '../' + encodeURI(filePath);
            link.target = '_blank';
            link.className = 'file-link';
            link.innerHTML = '<i class="fa fa-file"></i> ' + fileName;
            attachmentEl.appendChild(link);
        } else {
            attachmentEl.textContent = '-';
        }

        fetchAssignmentStatusData(assignmentId);
        openAssignmentModal();
    }

    function fetchLessonAccessData(lessonId) {
        const params = new URLSearchParams(window.location.search);
        params.set('lesson_access_graph', '1');
        params.set('lesson_id', lessonId);
        const url = window.location.pathname + '?' + params.toString();

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const accessed = data.accessed || 0;
                const notAccessed = data.not_accessed || 0;
                const totalStudents = data.total_students || 0;
                const downloadedBy = data.download_count || 0;
                document.getElementById('lessonModalStudents').textContent = totalStudents;
                document.getElementById('lessonModalDownloadedBy').textContent = downloadedBy;
                renderLessonAccessPie(accessed, notAccessed);
            })
            .catch(() => {
                document.getElementById('lessonModalStudents').textContent = '0';
                document.getElementById('lessonModalDownloadedBy').textContent = '0';
                renderLessonAccessPie(0, 0);
            });
    }

    function fetchAssignmentStatusData(assignmentId) {
        const params = new URLSearchParams(window.location.search);
        params.set('assignment_submission_graph', '1');
        params.set('assignment_id', assignmentId);
        const url = window.location.pathname + '?' + params.toString();

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const normal = data.normal || 0;
                const late = data.late || 0;
                const none = data.none || 0;
                renderAssignmentStatusChart(normal, late, none);
            })
            .catch(() => {
                renderAssignmentStatusChart(0, 0, 0);
            });
    }

    function buildActivityModal(row) {
        const assignmentId = row.dataset.id || '';
        const title = row.dataset.title || 'Activity Details';
        const description = row.dataset.description || 'No description provided.';

        currentActivityAssignmentId = assignmentId;
        currentActivityData = null;

        document.getElementById('activityModalTitle').textContent = title;
        document.getElementById('activityModalDescription').textContent = description;
        document.getElementById('gradeAssignmentId').value = assignmentId;
        document.getElementById('activityModalStudents').textContent = 'Loading...';
        document.getElementById('activityModalSubmitted').textContent = '-';
        document.getElementById('activityModalNotSubmitted').textContent = '-';
        document.getElementById('activityStudentList').innerHTML = '';

        fetchActivityGradeData(assignmentId);
        openActivityModal();
    }

    function fetchActivityGradeData(assignmentId) {
        const params = new URLSearchParams(window.location.search);
        params.set('activity_grade_data', '1');
        params.set('assignment_id', assignmentId);
        const url = window.location.pathname + '?' + params.toString();

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    document.getElementById('activityModalStudents').textContent = '0';
                    document.getElementById('activityModalSubmitted').textContent = '0';
                    document.getElementById('activityModalNotSubmitted').textContent = '0';
                    document.getElementById('activityStudentList').innerHTML = '<tr><td colspan="4" class="no-items">Unable to load activity details.</td></tr>';
                    return;
                }

                const total = data.total_students || 0;
                const normal = data.normal || 0;
                const late = data.late || 0;
                const none = data.none || 0;
                const submitted = normal + late;
                currentActivityData = data;
                document.getElementById('activityModalStudents').textContent = total;
                document.getElementById('activityModalSubmitted').textContent = submitted;
                document.getElementById('activityModalNotSubmitted').textContent = none;
                renderActivityStudentList(data.students || [], assignmentId);
            })
            .catch(() => {
                document.getElementById('activityModalStudents').textContent = '0';
                document.getElementById('activityModalSubmitted').textContent = '0';
                document.getElementById('activityModalNotSubmitted').textContent = '0';
                document.getElementById('activityStudentList').innerHTML = '<tr><td colspan="4" class="no-items">Unable to load activity details.</td></tr>';
            });
    }

    function normalizeSubmissionPaths(filePathValue) {
        if (!filePathValue) {
            return [];
        }
        if (Array.isArray(filePathValue)) {
            return filePathValue.filter(Boolean).map(String);
        }
        try {
            const parsed = JSON.parse(filePathValue);
            if (Array.isArray(parsed)) {
                return parsed.filter(Boolean).map(String);
            }
        } catch (e) {
            // not JSON
        }
        return String(filePathValue).split('|').map(p => p.trim()).filter(Boolean);
    }

    async function exportActivitySubmissionReport() {
        if (!currentActivityData || !currentActivityData.success) {
            alert('Activity data is not loaded yet. Please open an activity first and try again.');
            return;
        }

        const title = currentActivityData.title || 'Activity Report';
        const description = currentActivityData.description || '';
        const totalStudents = currentActivityData.total_students || 0;
        const normal = currentActivityData.normal || 0;
        const late = currentActivityData.late || 0;
        const submitted = normal + late;
        const notSubmitted = currentActivityData.none || 0;
        const rows = (currentActivityData.students || []).map(student => {
            const filePaths = normalizeSubmissionPaths(student.file_path || student.file_paths || '');
            const fileLinks = filePaths.length ? filePaths.map((path, index) => `<a href="../${encodeURI(path)}" target="_blank">${escapeHtml('File ' + (index + 1))}</a>`).join('<br>') : '-';
            const gradeValue = student.grade_value !== null ? student.grade_value : '-';
            return `<tr>
                        <td>${escapeHtml(student.student_name)}</td>
                        <td>${escapeHtml(title)}</td>
                        <td>${fileLinks}</td>
                        <td>${escapeHtml(student.status.replace('_', ' '))}</td>
                        <td>${escapeHtml(String(gradeValue))}</td>
                    </tr>`;
        }).join('');

        const printableHtml = `<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>${escapeHtml(title)} - Submission Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #111; }
        h1 { font-size: 24px; margin-bottom: 4px; }
        h2 { font-size: 18px; margin-top: 24px; margin-bottom: 12px; }
        .summary { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
        .summary-box { border: 1px solid #ccc; padding: 10px 12px; min-width: 160px; border-radius: 6px; background: #f9f9f9; }
        .summary-box strong { display: block; margin-bottom: 6px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table th, table td { border: 1px solid #bbb; padding: 8px 10px; text-align: left; }
        table th { background: #eee; }
        a { color: #1a0dab; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>${escapeHtml(title)}</h1>
    <p>${escapeHtml(description)}</p>
    <div class="summary">
        <div class="summary-box"><strong>Total students</strong>${totalStudents}</div>
        <div class="summary-box"><strong>Submitted</strong>${submitted}</div>
        <div class="summary-box"><strong>Late</strong>${late}</div>
        <div class="summary-box"><strong>Not submitted</strong>${notSubmitted}</div>
    </div>
    <h2>Activity Submission Records</h2>
    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Activity Name</th>
                <th>File</th>
                <th>Status</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            ${rows || '<tr><td colspan="5" style="text-align:center;">No student records available.</td></tr>'}
        </tbody>
    </table>
</body>
</html>`;

        const hiddenContainer = document.createElement('div');
        hiddenContainer.style.position = 'fixed';
        hiddenContainer.style.left = '-9999px';
        hiddenContainer.style.top = '-9999px';
        hiddenContainer.innerHTML = printableHtml;
        document.body.appendChild(hiddenContainer);

        const filename = `${title.replace(/[^a-zA-Z0-9-_ ]/g, '').trim().replace(/\s+/g, '_') || 'activity'}_submissions.pdf`;

        if (window.jspdf && window.jspdf.jsPDF) {
            try {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({ unit: 'pt', format: 'letter' });
                await doc.html(hiddenContainer, {
                    callback: function (doc) {
                        doc.save(filename);
                        document.body.removeChild(hiddenContainer);
                    },
                    x: 20,
                    y: 20,
                    html2canvas: { scale: 0.8, useCORS: true }
                });
                return;
            } catch (error) {
                console.error('PDF export failed', error);
                alert('PDF export failed. Please try again.');
            }
        } else {
            alert('PDF export is unavailable. Please make sure jsPDF and html2canvas are loaded.');
        }

        document.body.removeChild(hiddenContainer);
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderActivityStudentList(students, assignmentId) {
        const container = document.getElementById('activityStudentList');
        container.innerHTML = '';
        if (!students || students.length === 0) {
            container.innerHTML = '<tr><td colspan="4" class="no-items">No students found.</td></tr>';
            return;
        }

        students.forEach(student => {
            const row = document.createElement('tr');
            const status = student.status || 'not submitted';
            const fileCell = document.createElement('td');
            const filePaths = normalizeSubmissionPaths(student.file_path || student.file_paths || '');
            if (filePaths.length) {
                filePaths.forEach((path, index) => {
                    const link = document.createElement('a');
                    link.href = '../' + path;
                    link.target = '_blank';
                    link.className = 'file-link';
                    link.innerHTML = `<i class="fa fa-file"></i> View file ${index + 1}`;
                    link.style.display = 'inline-block';
                    link.style.marginBottom = '6px';
                    fileCell.appendChild(link);
                    if (index < filePaths.length - 1) {
                        fileCell.appendChild(document.createElement('br'));
                    }
                });
            } else {
                fileCell.textContent = '-';
            }

            const gradeCell = document.createElement('td');
            const gradeValue = student.grade_value !== null ? student.grade_value : '-';
            const gradeLabel = document.createElement('span');
            gradeLabel.className = 'grade-value';
            gradeLabel.textContent = gradeValue;
            const gradeButton = document.createElement('button');
            gradeButton.type = 'button';
            gradeButton.className = 'grade-button';
            gradeButton.innerHTML = '<i class="fa fa-star"></i>';
            gradeButton.addEventListener('click', function(event) {
                event.stopPropagation();
                gradeActivityStudent(assignmentId, student.id, student.student_name, gradeValue);
            });
            gradeCell.appendChild(gradeLabel);
            gradeCell.appendChild(document.createTextNode(' '));
            gradeCell.appendChild(gradeButton);

            if (student.grade_value !== null) {
                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'remove-grade-button';
                removeButton.innerHTML = '<i class="fa fa-trash"></i>';
                removeButton.addEventListener('click', function(event) {
                    event.stopPropagation();
                    removeActivityStudentGrade(assignmentId, student.id, student.student_name);
                });
                gradeCell.appendChild(document.createTextNode(' '));
                gradeCell.appendChild(removeButton);
            }

            row.innerHTML = '<td>' + escapeHtml(student.student_name) + '</td>' +
                '<td>' + escapeHtml(status.replace('_', ' ')) + '</td>' +
                '<td></td>' +
                '<td></td>';
            row.children[2].appendChild(fileCell);
            row.children[3].appendChild(gradeCell);
            container.appendChild(row);
        });
    }

    function gradeActivityStudent(assignmentId, studentId, studentName, currentGrade) {
        document.getElementById('gradeAssignmentId').value = assignmentId;
        document.getElementById('gradeStudentId').value = studentId;
        document.getElementById('gradeStudentNameDisplay').value = studentName;
        document.getElementById('gradeMainScore').value = currentGrade !== '-' ? currentGrade : '';
        document.getElementById('gradeExtraPoints').value = 0;
        document.getElementById('gradeMaxScore').value = currentActivityData ? (currentActivityData.max_points || 0) : 0;
        setActivityTotalScore();
        document.getElementById('gradeEntryPanel').classList.add('active');
        document.getElementById('gradeMainScore').focus();
    }

    function setActivityTotalScore() {
        const mainScore = parseInt(document.getElementById('gradeMainScore').value, 10);
        const extraScore = parseInt(document.getElementById('gradeExtraPoints').value, 10);
        const total = (isNaN(mainScore) ? 0 : mainScore) + (isNaN(extraScore) ? 0 : extraScore);
        document.getElementById('gradeTotalDisplay').value = total;
    }

    function submitActivityGrade() {
        const mainScoreField = document.getElementById('gradeMainScore');
        const extraScoreField = document.getElementById('gradeExtraPoints');
        const maxScoreField = document.getElementById('gradeMaxScore');
        const gradeValueField = document.getElementById('gradeValue');

        const mainScore = parseInt(mainScoreField.value, 10);
        const extraScore = parseInt(extraScoreField.value, 10);
        const maxPoints = parseInt(maxScoreField.value, 10) || 0;
        const totalScore = (isNaN(mainScore) ? 0 : mainScore) + (isNaN(extraScore) ? 0 : extraScore);

        if (mainScoreField.value.trim() === '') {
            alert('Please enter the main score.');
            return;
        }
        if (isNaN(mainScore) || mainScore < 0 || isNaN(extraScore) || extraScore < 0) {
            alert('Score values must be non-negative whole numbers.');
            return;
        }
        if (totalScore > maxPoints) {
            alert('Total score cannot exceed the assignment maximum of ' + maxPoints + '.');
            return;
        }

        gradeValueField.value = totalScore;
        document.getElementById('activityGradeForm').submit();
    }

    function cancelGradeEntry() {
        document.getElementById('gradeEntryPanel').classList.remove('active');
    }

    function removeActivityStudentGrade(assignmentId, studentId, studentName) {
        if (!confirm('Remove recorded grade for ' + studentName + '?')) {
            return;
        }
        const formData = new FormData();
        formData.append('remove_activity_grade', '1');
        formData.append('assignment_id', assignmentId);
        formData.append('student_id', studentId);
        const gradingPeriod = document.getElementById('gradingPeriod') ? document.getElementById('gradingPeriod').value : '';
        if (gradingPeriod) {
            formData.append('grading_period', gradingPeriod);
        }

        fetch(window.location.pathname, {
            method: 'POST',
            body: formData,
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fetchActivityGradeData(assignmentId);
                } else {
                    alert(data.message || 'Failed to remove grade.');
                }
            })
            .catch(() => {
                alert('Unable to remove grade. Please try again.');
            });
    }

    function renderActivityStatusChart(normal, late, none) {
        const ctx = document.getElementById('activityStatusChart').getContext('2d');
        const labels = ['On time', 'Late', 'Not submitted'];
        const values = [normal, late, none];
        const colors = ['#28a745', '#ffc107', '#dc3545'];

        if (activityStatusChart) {
            activityStatusChart.data.labels = labels;
            activityStatusChart.data.datasets[0].data = values;
            activityStatusChart.update();
        } else {
            activityStatusChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: { enabled: true }
                    }
                }
            });
        }
    }

    function toggleGroupForm(show) {
        const form = document.getElementById('assignmentGroupForm');
        if (form) {
            form.style.display = show ? 'block' : 'none';
        }
    }

    function populateGroupStudentList() {
        const studentDataElement = document.getElementById('enrolledStudentsData');
        const students = studentDataElement ? JSON.parse(studentDataElement.textContent || '[]') : [];
        const studentsContainer = document.getElementById('assignmentGroupStudents');
        const leaderSelect = document.getElementById('groupLeaderField');
        if (!studentsContainer || !leaderSelect) {
            return;
        }

        studentsContainer.innerHTML = '';
        leaderSelect.innerHTML = '<option value="">Select leader</option>';

        students.forEach(student => {
            const option = document.createElement('option');
            option.value = student.id;
            option.textContent = student.student_name;
            leaderSelect.appendChild(option);

            const row = document.createElement('div');
            row.className = 'group-student-row';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'group_member_ids[]';
            checkbox.value = student.id;
            checkbox.id = 'group_member_' + student.id;
            const label = document.createElement('label');
            label.setAttribute('for', checkbox.id);
            label.textContent = student.student_name;
            row.appendChild(checkbox);
            row.appendChild(label);
            studentsContainer.appendChild(row);

            checkbox.addEventListener('change', function() {
                if (this.checked && !leaderSelect.value) {
                    leaderSelect.value = student.id;
                }
            });
        });

        leaderSelect.onchange = function() {
            const leaderId = this.value;
            if (leaderId) {
                const leaderCheckbox = document.getElementById('group_member_' + leaderId);
                if (leaderCheckbox) {
                    leaderCheckbox.checked = true;
                }
            }
        };
    }

    function renderLessonAccessPie(accessed, notAccessed) {
        const ctx = document.getElementById('lessonAccessChart').getContext('2d');
        const labels = ['Downloaded', 'Not accessed'];
        const values = [accessed, notAccessed];
        const colors = ['#28a745', '#dc3545'];

        if (lessonAccessChart) {
            lessonAccessChart.data.labels = labels;
            lessonAccessChart.data.datasets[0].data = values;
            lessonAccessChart.update();
        } else {
            lessonAccessChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: { enabled: true }
                    }
                }
            });
        }
    }

    function renderAssignmentStatusChart(normal, late, none) {
        const ctx = document.getElementById('assignmentStatusChart').getContext('2d');
        const labels = ['On time', 'Late', 'Not submitted'];
        const values = [normal, late, none];
        const colors = ['#28a745', '#ffc107', '#dc3545'];

        if (assignmentStatusChart) {
            assignmentStatusChart.data.labels = labels;
            assignmentStatusChart.data.datasets[0].data = values;
            assignmentStatusChart.update();
        } else {
            assignmentStatusChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: { enabled: true }
                    }
                }
            });
        }
    }

    function openAssignmentModal() {
        document.getElementById('assignmentModal').classList.add('active');
    }

    function closeAssignmentModal() {
        document.getElementById('assignmentModal').classList.remove('active');
    }

    function openLessonModal() {
        document.getElementById('lessonModal').classList.add('active');
    }

    function closeLessonModal() {
        document.getElementById('lessonModal').classList.remove('active');
    }

    function openActivityModal() {
        document.getElementById('activityModal').classList.add('active');
    }

    function closeActivityModal() {
        document.getElementById('activityModal').classList.remove('active');
    }

    function buildLessonModal(row) {
        const title = row.dataset.title || 'Lesson Details';
        const description = row.dataset.description || 'No description provided.';
        const created = row.dataset.created || '-';
        const filePath = row.dataset.filePath || '';
        const fileName = row.dataset.fileName || '';
        const lessonId = row.dataset.id || '';

        document.getElementById('lessonModalTitle').textContent = title;
        document.getElementById('lessonModalDescription').textContent = description;
        document.getElementById('lessonModalCreated').textContent = created;
        document.getElementById('lessonModalStudents').textContent = '0';
        document.getElementById('lessonModalDownloadedBy').textContent = '0';
        document.getElementById('lessonDeleteId').value = lessonId;
        document.getElementById('lessonEditId').value = lessonId;
        document.getElementById('lessonTitleField').value = title;
        document.getElementById('lessonDescriptionField').value = description;
        document.getElementById('lessonExistingFilePath').value = filePath;
        document.getElementById('lessonExistingFileName').value = fileName;
        toggleLessonEditForm(false);
        fetchLessonAccessData(lessonId);

        const attachmentEl = document.getElementById('lessonModalAttachment');
        attachmentEl.textContent = '';
        if (filePath) {
            const link = document.createElement('a');
            const downloadUrl = `${window.location.pathname}?download_lesson=1&lesson_id=${encodeURIComponent(lessonId)}`;
            link.href = downloadUrl;
            link.target = '_blank';
            link.className = 'file-link';
            link.dataset.lessonId = lessonId;
            link.dataset.lessonTitle = title;
            link.dataset.lessonFileName = fileName;
            link.innerHTML = '<i class="fa fa-file"></i> View document';
            attachmentEl.appendChild(link);
        } else {
            attachmentEl.textContent = '-';
        }
        openLessonModal();
    }

    function toggleAssignmentEditForm(show) {
        document.getElementById('assignmentEditForm').style.display = show ? 'block' : 'none';
    }

    function toggleLessonEditForm(show) {
        document.getElementById('lessonEditForm').style.display = show ? 'block' : 'none';
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.assignment-row').forEach(function(row) {
            row.addEventListener('click', function(event) {
                if (event.target.closest('a')) return;
                buildAssignmentModal(this);
            });
        });

        document.querySelectorAll('.activity-row').forEach(function(row) {
            row.addEventListener('click', function(event) {
                if (event.target.closest('a')) return;
                buildActivityModal(this);
            });
        });

        document.querySelectorAll('.lesson-row').forEach(function(row) {
            row.addEventListener('click', function(event) {
                if (event.target.closest('a')) return;
                buildLessonModal(this);
            });
        });

        document.getElementById('assignmentModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeAssignmentModal();
            }
        });

        document.getElementById('lessonModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeLessonModal();
            }
        });

        document.getElementById('activityModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeActivityModal();
            }
        });

        document.getElementById('deleteAssignmentForm').addEventListener('submit', function() {
            return confirm('Are you sure you want to delete this assignment? This action cannot be undone.');
        });

        document.getElementById('deleteLessonForm').addEventListener('submit', function() {
            return confirm('Are you sure you want to delete this lesson? This action cannot be undone.');
        });

        // Call function to highlight activity row if navigating from notification
        highlightActivityFromHash();
    });
</script>

<div class="floating-buttons">
    <button id="activityBtn" title="Notifications">
        <i class="fa fa-bell"></i>
        <span class="notification-dot" id="notificationDot"></span>
    </button>
</div>

<div id="activityPanel" class="sidepanel">
    <button class="closeBtn" onclick="closeActivity()">✖</button>
    <div class="notification-panel-header">
        <h3>Notifications</h3>
    </div>
    
    <div class="notification-section">
        <h4 class="notification-section-title">📢 Announcements</h4>
        <div id="announcementsList">
            <?php if (empty($recentAnnouncements)): ?>
                <div class="notification-item-empty">No announcements</div>
            <?php else: ?>
                <?php foreach ($recentAnnouncements as $ann): ?>
                    <div class="notification-item notification-announcement" onclick="navigateToAnnouncement(<?php echo intval($ann['id']); ?>)">
                        <div class="notification-item-title"><?php echo htmlspecialchars($ann['title']); ?></div>
                        <div class="notification-item-meta"><?php echo date('M j, Y', strtotime($ann['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="notification-section">
        <h4 class="notification-section-title">📚 Materials</h4>
        <div id="materialsList">
            <?php if (empty($recentMaterials)): ?>
                <div class="notification-item-empty">No materials uploaded</div>
            <?php else: ?>
                <?php foreach ($recentMaterials as $mat): ?>
                    <div class="notification-item notification-material" onclick="navigateToMaterial(<?php echo intval($mat['id']); ?>)">
                        <div class="notification-item-title"><?php echo htmlspecialchars($mat['title']); ?></div>
                        <div class="notification-item-meta"><?php echo htmlspecialchars($mat['subject_name']); ?> • <?php echo date('M j, Y', strtotime($mat['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
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
                        <div class="notification-item notification-activity" onclick="<?php echo !empty($event['link']) ? "window.location.href='" . htmlspecialchars($event['link']) . "'" : ""; ?>" style="cursor: pointer; border-left-color: #f59e0b;">
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

<script>window.mucahubInstructorId = <?php echo intval($instructorId); ?>;</script>
<script>
    window.dashboardNotificationEvents = <?php echo json_encode($dashboardNotificationEvents ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    window.dashboardAssignmentEvents = <?php echo json_encode($dashboardAssignmentEvents ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<script src="../assets/js/instructor_dashboard.js"></script>
</body>
</html>
