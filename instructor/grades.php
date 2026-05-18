<?php
include "../config/config.php";
include "../includes/databases/db_connection.php";
include "../includes/functions.php";

function browserPath($path) {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^(https?:|/|\\|\.\.)#i', $path)) {
        return $path;
    }
    return '../' . ltrim($path, '/');
}

if (function_exists('opcache_invalidate')) {
    opcache_invalidate(__FILE__, true);
}

checkLogin();
checkRole('instructor');

$instructorId = intval($_SESSION['user_id'] ?? 0);
include "../includes/dashboard_notifications.php";

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

$selectedGrade = trim($_REQUEST['grade'] ?? '');
$selectedSubject = trim($_REQUEST['subject'] ?? '');
$selectedSection = trim($_REQUEST['section'] ?? '');
$selectedStudentId = intval($_REQUEST['student_id'] ?? 0);
$selectedPeriod = trim($_REQUEST['period'] ?? $_SESSION['selected_period'] ?? $_COOKIE['selected_period'] ?? '1st');
$saveStatus = trim($_REQUEST['save_status'] ?? '');
$resetStatus = trim($_REQUEST['reset_status'] ?? '');
$saveError = '';
$resetError = '';

$gradingPeriods = [
    '1st' => '1st grading',
    '2nd' => '2nd grading',
    '3rd' => '3rd grading',
    '4th' => '4th grading',
    'Final' => 'Final grade',
];

$gradingPeriodOptions = [];
foreach ($gradingPeriods as $key => $label) {
    if ($key !== 'Final') {
        $gradingPeriodOptions[$key] = $label;
    }
}

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

function getGradingPeriodsUpTo($conn, $selectedPeriod, $includeFinal = false) {
    $selectedPeriod = normalizeGradingPeriod($selectedPeriod);
    $periodOrder = ['1st', '2nd', '3rd', '4th'];
    $variants = [];
    foreach ($periodOrder as $periodKey) {
        $variants[] = $periodKey;
        if ($periodKey === '1st') {
            $variants[] = '1';
        } elseif ($periodKey === '2nd') {
            $variants[] = '2';
        } elseif ($periodKey === '3rd') {
            $variants[] = '3';
        } elseif ($periodKey === '4th') {
            $variants[] = '4';
        }
        if ($periodKey === $selectedPeriod) {
            break;
        }
    }
    if ($includeFinal) {
        $variants[] = 'Final';
    }
    $variants = array_values(array_unique($variants));
    $safe = array_map([$conn, 'real_escape_string'], $variants);
    return "IN ('" . implode("','", $safe) . "')";
}

function computeSubjectFinalGrade(array $periodSummaries) {
    $periodKeys = ['1st', '2nd', '3rd', '4th'];
    $values = [];
    foreach ($periodKeys as $periodKey) {
        if (!isset($periodSummaries[$periodKey]) || $periodSummaries[$periodKey]['grade_value'] === null || $periodSummaries[$periodKey]['grade_value'] === '') {
            return null;
        }
        $values[] = floatval($periodSummaries[$periodKey]['grade_value']);
    }
    if (count($values) !== 4) {
        return null;
    }
    return round(array_sum($values) / 4, 2);
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

$selectedPeriod = normalizeGradingPeriod($selectedPeriod);
if (!array_key_exists($selectedPeriod, $gradingPeriods) || $selectedPeriod === 'Final') {
    $selectedPeriod = '1st';
}

$_SESSION['selected_period'] = $selectedPeriod;
setcookie('selected_period', $selectedPeriod, time() + 60 * 60 * 24 * 30, '/');
$periodCondition = getGradingPeriodCondition($conn, $selectedPeriod);
$periodEsc = $conn->real_escape_string($selectedPeriod);

$resetStatus = trim($_REQUEST['reset_status'] ?? '');

$subjectTeacherName = '';
if ($selectedGrade !== '' && $selectedSection !== '' && $selectedSubject !== '') {
    $gradeEsc = $conn->real_escape_string($selectedGrade);
    $sectionEsc = $conn->real_escape_string($selectedSection);
    $subjectEsc = $conn->real_escape_string($selectedSubject);
    $teacherResult = $conn->query("SELECT DISTINCT instructor_name FROM teacher_assignments WHERE grade_level = '$gradeEsc' AND section = '$sectionEsc' AND subject_name = '$subjectEsc' LIMIT 1");
    if ($teacherResult && $teacherResult->num_rows > 0) {
        $assignedTeacher = $teacherResult->fetch_assoc();
        $subjectTeacherName = trim($assignedTeacher['instructor_name'] ?? '');
    }
    if ($teacherResult) {
        $teacherResult->free();
    }
}

if ($subjectTeacherName === '') {
    if (isset($instructor) && is_array($instructor)) {
        $subjectTeacherName = trim(($instructor['first_name'] ?? '') . ' ' . ($instructor['last_name'] ?? ''));
    }
    if ($subjectTeacherName === '') {
        $subjectTeacherName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
    }
}

function ensureGradeRecordsColumns($conn) {
    $columnChecks = $conn->query("SHOW COLUMNS FROM teacher_grade_records LIKE 'grading_period'");
    if ($columnChecks && $columnChecks->num_rows === 0) {
        $conn->query("ALTER TABLE teacher_grade_records ADD COLUMN grading_period VARCHAR(20) NOT NULL DEFAULT '1st' AFTER subject_name");
    }
    $modifiedAtCheck = $conn->query("SHOW COLUMNS FROM teacher_grade_records LIKE 'modified_at'");
    if ($modifiedAtCheck && $modifiedAtCheck->num_rows === 0) {
        $conn->query("ALTER TABLE teacher_grade_records ADD COLUMN modified_at DATETIME NULL DEFAULT NULL AFTER recorded_at");
    }
}

ensureGradeRecordsColumns($conn);

$lessonAccessTableExists = $conn->query("SHOW TABLES LIKE 'teacher_lesson_access'");
if ($lessonAccessTableExists && $lessonAccessTableExists->num_rows > 0) {
    $fkCheck = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'teacher_lesson_access' AND COLUMN_NAME = 'student_id' AND REFERENCED_TABLE_NAME = 'students' LIMIT 1");
    if ($fkCheck && $fkCheck->num_rows > 0) {
        $fkRow = $fkCheck->fetch_assoc();
        $fkName = $fkRow['CONSTRAINT_NAME'];
        $conn->query("ALTER TABLE teacher_lesson_access DROP FOREIGN KEY `" . $conn->real_escape_string($fkName) . "`");
    }

    $colCheck = $conn->query("SHOW COLUMNS FROM teacher_lesson_access LIKE 'student_id'");
    if ($colCheck && $colCheck->num_rows > 0) {
        $conn->query("ALTER TABLE teacher_lesson_access DROP COLUMN student_id");
    }
}

$createLessonAccessTableSql = "CREATE TABLE IF NOT EXISTS teacher_lesson_access (
  id INT NOT NULL AUTO_INCREMENT,
  lesson_id INT NOT NULL,
  accessed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY lesson_id (lesson_id),
  CONSTRAINT fk_teacher_lesson_access_lesson FOREIGN KEY (lesson_id) REFERENCES teacher_lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
$conn->query($createLessonAccessTableSql);

$recentlyAccessedLessons = [];
$recentAccessResult = $conn->query("SELECT l.id, l.title, l.file_name, MAX(a.accessed_at) AS last_accessed, COUNT(*) AS access_count FROM teacher_lesson_access a JOIN teacher_lessons l ON a.lesson_id = l.id WHERE l.instructor_id = $instructorId GROUP BY l.id ORDER BY last_accessed DESC LIMIT 5");
if ($recentAccessResult) {
    while ($row = $recentAccessResult->fetch_assoc()) {
        $recentlyAccessedLessons[] = $row;
    }
}

$summaryRecord = null;
$periodSummaries = [];
$finalGradeTarget = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_final_grade'])) {
    $saveFinalGrade = trim($_POST['final_grade_value'] ?? '');
    $saveStudentId = intval($_POST['save_student_id'] ?? 0);
    $saveGrade = trim($_POST['save_grade'] ?? '');
    $saveSubject = trim($_POST['save_subject'] ?? '');
    $saveSection = trim($_POST['save_section'] ?? '');
    $savePeriod = trim($_POST['save_period'] ?? '1st');
    $allowedPeriods = array_keys($gradingPeriods);
    $savePeriod = normalizeGradingPeriod($savePeriod);
    if (!in_array($savePeriod, $allowedPeriods, true)) {
        $savePeriod = '1st';
    }

    if ($saveFinalGrade === '' || !is_numeric($saveFinalGrade) || $saveStudentId <= 0 || $saveGrade === '' || $saveSubject === '' || $saveSection === '') {
        $saveError = 'Please enter a valid point adjustment and ensure a student is selected.';
    } else {
        $saveGradeEsc = $conn->real_escape_string($saveGrade);
        $saveSubjectEsc = $conn->real_escape_string($saveSubject);
        $saveSectionEsc = $conn->real_escape_string($saveSection);
        $savePeriodEsc = $conn->real_escape_string($savePeriod);
        $savePeriodCondition = getGradingPeriodCondition($conn, $savePeriod);
        $adjustmentPoints = intval(round(floatval($saveFinalGrade)));

        $baseSum = 0;
        $baseCount = 0;
        $gradeSourceQuery = $conn->query("SELECT tgr.grade_value FROM teacher_assignments a LEFT JOIN teacher_grade_records tgr ON tgr.assignment_id = a.id AND tgr.student_id = $saveStudentId AND tgr.grading_period $savePeriodCondition WHERE a.grade_level = '$saveGradeEsc' AND a.section = '$saveSectionEsc' AND a.subject_name = '$saveSubjectEsc'");
        if ($gradeSourceQuery) {
            while ($gradeRow = $gradeSourceQuery->fetch_assoc()) {
                if ($gradeRow['grade_value'] !== null && $gradeRow['grade_value'] !== '') {
                    $baseSum += floatval($gradeRow['grade_value']);
                    $baseCount++;
                }
            }
            $gradeSourceQuery->free();
        }

        $baseAverage = $baseCount > 0 ? round($baseSum / $baseCount, 2) : 0;
        $saveGradeValue = intval(round(min(100, max(0, $baseAverage + $adjustmentPoints))));

        $studentResult = $conn->query("SELECT id, TRIM(CONCAT(IFNULL(first_name, ''), ' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''))) AS student_name, grade_level, section FROM students WHERE id = $saveStudentId LIMIT 1");
        if (!$studentResult || $studentResult->num_rows === 0) {
            $saveError = 'Selected student could not be found.';
        } else {
            $studentRow = $studentResult->fetch_assoc();
            $existingSummary = $conn->query("SELECT id FROM teacher_grade_records WHERE assignment_id IS NULL AND student_id = $saveStudentId AND grade_level = '$saveGradeEsc' AND section = '$saveSectionEsc' AND subject_name = '$saveSubjectEsc' AND grading_period $savePeriodCondition AND activity_title = 'Final grade summary' LIMIT 1");
            $instructorName = trim(($instructor['first_name'] ?? '') . ' ' . ($instructor['last_name'] ?? ''));
            if ($existingSummary && $existingSummary->num_rows > 0) {
                $existingRow = $existingSummary->fetch_assoc();
                $stmt = $conn->prepare("UPDATE teacher_grade_records SET instructor_id = ?, instructor_name = ?, grade_level = ?, section = ?, subject_name = ?, grading_period = ?, activity_title = 'Final grade summary', student_name = ?, grade_value = ?, modified_at = NOW() WHERE id = ? LIMIT 1");
                $stmt->bind_param('issssssii', $instructorId, $instructorName, $studentRow['grade_level'], $studentRow['section'], $saveSubject, $savePeriod, $studentRow['student_name'], $saveGradeValue, $existingRow['id']);
            } else {
                $stmt = $conn->prepare("INSERT INTO teacher_grade_records (assignment_id, instructor_id, instructor_name, grade_level, section, subject_name, grading_period, activity_title, student_id, student_name, grade_value, recorded_at, modified_at) VALUES (NULL, ?, ?, ?, ?, ?, ?, 'Final grade summary', ?, ?, ?, NOW(), NOW())");
                $stmt->bind_param('isssssisi', $instructorId, $instructorName, $studentRow['grade_level'], $studentRow['section'], $saveSubject, $savePeriod, $saveStudentId, $studentRow['student_name'], $saveGradeValue);
            }
            if ($stmt && $stmt->execute()) {
                $stmt->close();
                $redirectParams = http_build_query([
                    'grade' => $saveGrade,
                    'subject' => $saveSubject,
                    'section' => $saveSection,
                    'student_id' => $saveStudentId,
                    'period' => $savePeriod,
                    'save_status' => 'success',
                ]);
                header("Location: grades.php?$redirectParams");
                exit;
            }
            if ($stmt) {
                $saveError = 'Unable to save grade summary: ' . $stmt->error;
                $stmt->close();
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_subject_data'])) {
    $resetGrade = trim($_POST['reset_grade'] ?? '');
    $resetSubject = trim($_POST['reset_subject'] ?? '');
    $resetSection = trim($_POST['reset_section'] ?? '');
    $resetPeriod = trim($_POST['reset_period'] ?? '1st');
    $resetPassword = trim($_POST['reset_password'] ?? '');
    $allowedPeriods = array_keys($gradingPeriods);
    if (!in_array($resetPeriod, $allowedPeriods, true)) {
        $resetPeriod = '1st';
    }

    if ($resetGrade && $resetSubject && $resetSection) {
        if ($resetPassword === '') {
            $resetError = 'Password is required to confirm reset.';
        } else {
            $authResult = $conn->query("SELECT password FROM instructors WHERE id = $instructorId LIMIT 1");
            if (!$authResult || $authResult->num_rows === 0) {
                $resetError = 'Unable to verify your password at this time.';
            } else {
                $authRow = $authResult->fetch_assoc();
                if (!password_verify($resetPassword, $authRow['password'])) {
                    $resetError = 'Incorrect password. Subject reset canceled.';
                }
            }
        }

        if ($resetError === '') {
            $resetGradeEsc = $conn->real_escape_string($resetGrade);
            $resetSubjectEsc = $conn->real_escape_string($resetSubject);
            $resetSectionEsc = $conn->real_escape_string($resetSection);

            $assignmentIdsResult = $conn->query("SELECT id FROM teacher_assignments WHERE instructor_id = $instructorId AND grade_level = '$resetGradeEsc' AND section = '$resetSectionEsc' AND subject_name = '$resetSubjectEsc'");
            $assignmentIds = [];
            if ($assignmentIdsResult) {
                while ($row = $assignmentIdsResult->fetch_assoc()) {
                    $assignmentIds[] = intval($row['id']);
                }
            }

            if (!empty($assignmentIds)) {
                $assignmentIdList = implode(',', $assignmentIds);
                $conn->query("DELETE FROM teacher_activity_grade_records WHERE assignment_id IN ($assignmentIdList)");
                $conn->query("DELETE FROM teacher_assignment_submissions WHERE assignment_id IN ($assignmentIdList)");
                $conn->query("UPDATE teacher_grade_records SET assignment_id = NULL WHERE assignment_id IN ($assignmentIdList)");
                $conn->query("DELETE FROM teacher_assignments WHERE id IN ($assignmentIdList)");
            }

            $conn->query("DELETE FROM teacher_lessons WHERE instructor_id = $instructorId AND grade_level = '$resetGradeEsc' AND section = '$resetSectionEsc' AND subject_name = '$resetSubjectEsc'");

            $redirectParams = http_build_query([
                'grade' => $resetGrade,
                'subject' => $resetSubject,
                'section' => $resetSection,
                'period' => $resetPeriod,
                'reset_status' => 'success',
            ]);
            header("Location: grades.php?$redirectParams");
            exit;
        }
    }

    if ($resetError !== '') {
        $resetStatus = 'error';
    } else {
        $redirectParams = http_build_query(['reset_status' => 'error']);
        header("Location: grades.php?$redirectParams");
        exit;
    }
}

$subjectStructure = [];
$subjectGroups = [];
$students = [];
$studentDetails = null;
$submissionRows = [];
$gradeReport = [
    'assignments_count' => 0,
    'graded_count' => 0,
    'average_grade' => null,
    'final_grade' => null,
    'remark' => 'No grades available yet.',
];

$instructorResult = $conn->query("SELECT subjects FROM instructors WHERE id = $instructorId LIMIT 1");
if ($instructorResult && $row = $instructorResult->fetch_assoc()) {
    $subjectStructure = json_decode($row['subjects'] ?? '', true) ?: [];
}

function fetchStudentCount($conn, $grade, $section, $subject = null) {
    $gradeColumn = detectStudentGradeColumn($conn);
    if (!$gradeColumn) {
        error_log('grades.php fetchStudentCount cannot detect grade column in students table');
        return 0;
    }

    $query = "SELECT COUNT(*) AS count FROM `students` WHERE `$gradeColumn` = ? AND `section` = ?";
    $oldReport = mysqli_report(MYSQLI_REPORT_OFF);

    try {
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log('grades.php fetchStudentCount prepare failed: ' . $conn->error . ' | query: ' . $query);
            return 0;
        }
        $stmt->bind_param('ss', $grade, $section);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row ? intval($row['count']) : 0;
    } catch (Throwable $e) {
        error_log('grades.php fetchStudentCount exception: ' . $e->getMessage() . ' | query: ' . $query);
        return 0;
    } finally {
        mysqli_report($oldReport);
    }
}

function detectStudentGradeColumn($conn) {
    static $cachedColumn;
    if ($cachedColumn !== null) {
        return $cachedColumn;
    }

    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM `students`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }

    $candidates = ['grade_level', 'grade', 'level', 'gradelevel'];
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            $cachedColumn = $candidate;
            return $cachedColumn;
        }
    }

    return $cachedColumn = null;
}

function ensureGradeRecordsGradingPeriodColumn($conn) {
    $columnCheck = $conn->query("SHOW COLUMNS FROM teacher_grade_records LIKE 'grading_period'");
    if ($columnCheck && $columnCheck->num_rows === 0) {
        $conn->query("ALTER TABLE teacher_grade_records ADD COLUMN grading_period VARCHAR(20) NOT NULL DEFAULT '1st' AFTER subject_name");
    }
}

ensureGradeRecordsGradingPeriodColumn($conn);

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
            $subjectGroups[] = [
                'grade_level' => $gradeLevel,
                'subject_name' => $subjectName,
                'section' => $sectionName,
                'students_count' => $studentCount,
            ];
        }
    }
}

if ($selectedGrade && $selectedSubject && $selectedSection) {
    $gradeEsc = $conn->real_escape_string($selectedGrade);
    $subjectEsc = $conn->real_escape_string($selectedSubject);
    $sectionEsc = $conn->real_escape_string($selectedSection);
    $periodEsc = $conn->real_escape_string($selectedPeriod);
    $sectionStudentCount = fetchStudentCount($conn, $selectedGrade, $selectedSection, $selectedSubject);

    $studentResult = $conn->query(
        "SELECT id, student_id, enrollment_status, `grade_level`, `section`, TRIM(CONCAT(IFNULL(first_name, ''), ' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''))) AS full_name FROM students WHERE `grade_level` = '$gradeEsc' AND `section` = '$sectionEsc' ORDER BY last_name, first_name"
    );
    if ($studentResult) {
        while ($row = $studentResult->fetch_assoc()) {
            $students[] = $row;
        }
    }

    if ($selectedStudentId > 0) {
        $studentDetailsResult = $conn->query("SELECT id, student_id, enrollment_status, `grade_level`, `section`, address, TRIM(CONCAT(IFNULL(first_name, ''), ' ', IFNULL(middle_name, ''), ' ', IFNULL(last_name, ''))) AS full_name FROM students WHERE id = $selectedStudentId LIMIT 1");
        if ($studentDetailsResult && $studentDetailsResult->num_rows > 0) {
            $studentDetails = $studentDetailsResult->fetch_assoc();
        }

        $reportPeriodCondition = getGradingPeriodsUpTo($conn, $selectedPeriod, $selectedPeriod === '4th');
        $summaryResult = $conn->query("SELECT grading_period, grade_value, recorded_at, modified_at FROM teacher_grade_records WHERE assignment_id IS NULL AND student_id = $selectedStudentId AND grade_level = '$gradeEsc' AND section = '$sectionEsc' AND subject_name = '$subjectEsc' AND grading_period $reportPeriodCondition AND activity_title = 'Final grade summary'");
        if ($summaryResult) {
            while ($row = $summaryResult->fetch_assoc()) {
                $periodKey = normalizeGradingPeriod($row['grading_period']);
                if (isset($gradingPeriods[$periodKey])) {
                    $periodSummaries[$periodKey] = $row;
                }
            }
        }

        if ($selectedPeriod === '4th') {
            $computedFinalGrade = computeSubjectFinalGrade($periodSummaries);
            if ($computedFinalGrade !== null) {
                if (!isset($periodSummaries['Final'])) {
                    $periodSummaries['Final'] = [
                        'grading_period' => 'Final',
                        'grade_value' => $computedFinalGrade,
                        'recorded_at' => null,
                        'modified_at' => null,
                    ];
                }
                $summaryRecord = $periodSummaries['Final'];
                $finalGradeTarget = intval($summaryRecord['grade_value']);
            } else {
                $summaryRecord = $periodSummaries[$selectedPeriod] ?? null;
                if ($summaryRecord && $summaryRecord['grade_value'] !== null) {
                    $finalGradeTarget = intval($summaryRecord['grade_value']);
                }
            }
        } else {
            $summaryRecord = $periodSummaries[$selectedPeriod] ?? null;
            if ($summaryRecord && $summaryRecord['grade_value'] !== null) {
                $finalGradeTarget = intval($summaryRecord['grade_value']);
            }
        }

        $assignmentTotalsBySubjectType = [];
        $totalAssignmentsAcrossSubjects = 0;
        $assignmentTotalsResult = $conn->query("SELECT assignment_type, COUNT(*) AS total_assignments FROM teacher_assignments WHERE grade_level = '$gradeEsc' AND section = '$sectionEsc' AND subject_name = '$subjectEsc' AND grading_period $periodCondition GROUP BY assignment_type");
        if ($assignmentTotalsResult) {
            while ($row = $assignmentTotalsResult->fetch_assoc()) {
                $assignmentType = $row['assignment_type'] ?? 'activity';
                $countValue = intval($row['total_assignments']);
                $assignmentTotalsBySubjectType[$selectedSubject][$assignmentType] = $countValue;
                $totalAssignmentsAcrossSubjects += $countValue;
            }
        }

        $gradedTotalsBySubjectType = [];
        $totalGradedItemsAcrossSubjects = 0;
        $gradedTotalsResult = $conn->query("SELECT a.assignment_type, COUNT(*) AS graded_count FROM teacher_assignments a JOIN teacher_grade_records tgr ON tgr.assignment_id = a.id WHERE a.grade_level = '$gradeEsc' AND a.section = '$sectionEsc' AND a.subject_name = '$subjectEsc' AND a.grading_period $periodCondition AND tgr.student_id = $selectedStudentId AND tgr.grade_value IS NOT NULL AND tgr.grading_period $periodCondition GROUP BY a.assignment_type");
        if ($gradedTotalsResult) {
            while ($row = $gradedTotalsResult->fetch_assoc()) {
                $assignmentType = $row['assignment_type'] ?? 'activity';
                $countValue = intval($row['graded_count']);
                $gradedTotalsBySubjectType[$selectedSubject][$assignmentType] = $countValue;
                $totalGradedItemsAcrossSubjects += $countValue;
            }
        }

        $assignmentTooltip = '';
        if (!empty($assignmentTotalsBySubjectType)) {
            $tooltipParts = [];
            foreach ($assignmentTotalsBySubjectType as $subjectName => $types) {
                foreach (['activity', 'quiz'] as $type) {
                    $tooltipParts[] = htmlspecialchars($subjectName . ' ' . ucfirst($type) . ': ' . intval($types[$type] ?? 0));
                }
            }
            $assignmentTooltip = implode(' ', $tooltipParts);
        }

        $gradedTooltip = '';
        if (!empty($gradedTotalsBySubjectType)) {
            $tooltipParts = [];
            foreach ($gradedTotalsBySubjectType as $subjectName => $types) {
                foreach (['activity', 'quiz'] as $type) {
                    $tooltipParts[] = htmlspecialchars($subjectName . ' ' . ucfirst($type) . ': ' . intval($types[$type] ?? 0));
                }
            }
            $gradedTooltip = implode(' ', $tooltipParts);
        }

        $submissionQuery = $conn->query(
            "SELECT a.id AS assignment_id, a.title AS assignment_title, a.assignment_type, a.open_date, a.open_time, a.due_date, a.due_time, a.max_points, COALESCE(tas.status, 'not submitted') AS status, tas.submission_file_path, tgr.grade_value, tas.submitted_at FROM teacher_assignments a LEFT JOIN teacher_assignment_submissions tas ON tas.assignment_id = a.id AND tas.student_id = $selectedStudentId LEFT JOIN teacher_grade_records tgr ON tgr.assignment_id = a.id AND tgr.student_id = $selectedStudentId AND tgr.grading_period $periodCondition WHERE a.instructor_id = $instructorId AND a.grade_level = '$gradeEsc' AND a.section = '$sectionEsc' AND a.subject_name = '$subjectEsc' AND a.grading_period $periodCondition ORDER BY a.due_date ASC, a.due_time ASC, a.title ASC"
        );
        if ($submissionQuery) {
            while ($row = $submissionQuery->fetch_assoc()) {
                $submissionRows[] = $row;
            }
        }

        $gradeReport['assignments_count'] = $totalAssignmentsAcrossSubjects;
        $gradeReport['graded_count'] = $totalGradedItemsAcrossSubjects;
        $sum = 0;
        $count = 0;
        foreach ($submissionRows as $row) {
            if ($row['grade_value'] !== null && $row['grade_value'] !== '') {
                $sum += floatval($row['grade_value']);
                $count++;
            }
        }
        if ($count > 0) {
            $gradeReport['average_grade'] = round($sum / $count, 2);
            $gradeReport['final_grade'] = $gradeReport['average_grade'];
            $gradeReport['original_average_grade'] = $gradeReport['average_grade'];
            $gradeReport['remark'] = $gradeReport['average_grade'] >= 75 ? 'Passed' : 'Failed';
            $gradeReport['graded_count'] = $count;
        }

        if ($finalGradeTarget !== null) {
            if (!isset($gradeReport['original_average_grade'])) {
                $gradeReport['original_average_grade'] = $gradeReport['average_grade'];
            }
            $gradeReport['average_grade'] = $finalGradeTarget;
            $gradeReport['final_grade'] = $finalGradeTarget;
            $gradeReport['remark'] = $finalGradeTarget >= 75 ? 'Passed' : 'Failed';
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>MUCAHUB - Grading</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .grading-container { padding: 20px; }
        .subject-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
        .subject-card { background: rgba(255,255,255,0.08); backdrop-filter: blur(10px); border: 1px solid rgba(85,107,47,0.2); border-radius: 12px; padding: 22px; transition: transform .2s ease, box-shadow .2s ease; }
        .subject-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.08); }
        .subject-card h3 { margin: 0 0 10px; font-size: 1.25rem; }
        .grade-tag { display: inline-block; background: linear-gradient(135deg, #556b2f 0%, #6b8e23 100%); color: white; padding: 7px 14px; border-radius: 18px; font-size: .86rem; }
        .subject-card .stats { margin-top: 14px; display: grid; gap: 10px; font-size: .95rem; color: #444; }
        .subject-card .stats span { display: flex; align-items: center; gap: 8px; }
        .card-link { color: inherit; text-decoration: none; }
        .section-header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 22px; flex-wrap: wrap; }
        .section-header h2 { margin: 0; font-size: 1.8rem; }
        .section-meta { color: #556b2f; font-weight: 600; }
        .student-table, .submission-table { width: 100%; border-collapse: collapse; margin-top: 18px; background: white; border-radius: 10px; overflow: visible; max-height: none; }
        .student-table th, .student-table td, .submission-table th, .submission-table td { padding: 12px 14px; border-bottom: 1px solid #eee; text-align: left; }
        #studentListContainer, .student-table, .submission-table, .item-list { max-height: none; overflow: visible; }
        .student-table th, .submission-table th { background: #556b2f; color: white; font-weight: 600; }
        .student-table tr:hover, .submission-table tr:hover { background: rgba(85,107,47,0.07); }
        .student-link { color: #204a11; font-weight: 600; text-decoration: none; }
        .student-link:hover { text-decoration: underline; }
        .detail-box { background: #f6fff0; border: 1px solid #d4edda; border-radius: 12px; padding: 18px; margin-top: 22px; }
        .detail-box h3 { margin-top: 0; }
        .detail-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-top: 10px; }
        .detail-item { background: white; padding: 14px; border-radius: 10px; border: 1px solid #e6f1de; }
        .period-options { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
        .period-option { display: inline-flex; align-items: center; gap: 8px; background: #ffffff; border: 1px solid #d1e5c8; border-radius: 10px; padding: 8px 12px; cursor: pointer; transition: background .15s ease; }
        .period-option:hover { background: #f2f9ef; }
        .period-option input { width: 16px; height: 16px; }
        .alert-box { background: #e8f7df; border: 1px solid #c9e5b9; border-radius: 10px; padding: 14px 16px; margin-bottom: 18px; color: #2f5f1f; }
        .detail-item[title] { cursor: help; }
        .detail-item strong { display: block; margin-bottom: 6px; color: #334d1d; }
        .final-grade { font-weight: bold; font-size: 1.2em; color: #556b2f; }
        .remark-pass { color: #1f7a1f; }
        .remark-fail { color: #a12828; }
        .no-data { padding: 26px; text-align: center; color: #666; }
        .back-btn { display: inline-flex; gap: 8px; align-items: center; background: #fff; border: 1px solid #cddcc6; color: #2f4f1f; padding: 10px 16px; border-radius: 10px; text-decoration: none; font-weight: 600; }
        .pdf-card { width: 100%; max-width: 760px; margin: 0 auto 24px; padding: 24px; border: 1px solid #d8d8d8; border-radius: 16px; background: #ffffff; box-shadow: 0 12px 30px rgba(0, 0, 0, 0.06); }
        .pdf-card-header { display:flex; align-items:center; gap:14px; margin-bottom: 18px; }
        .pdf-card-logo { width: 64px; height: auto; object-fit: contain; border-radius: 10px; }
        .pdf-card-header h2 { margin: 0 0 6px; font-size: 1.4rem; color: #264d00; }
        .pdf-card-header p { margin: 0; color: #4d4d4d; font-size: 0.92rem; }
        .signature-block { margin-top: 32px; padding-top: 14px; text-align: center; }
        .signature-line { width: 340px; height: 1px; background: #333; margin: 0 auto; position: relative; }
        .signature-name { display: inline-block; position: relative; top: 8px; background: #fff; padding: 0 10px; font-weight: 700; font-size: 1rem; text-decoration: none; }
        .signature-subject { color: #555; font-size: 0.95rem; margin-top: 10px; text-decoration: none; }
        .pdf-card-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 14px; }
        .pdf-card-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .pdf-card-row label { font-weight: 700; color: #2f4f1f; }
        .pdf-card-address { white-space: pre-wrap; color: #3d3d3d; line-height: 1.45; }
        .pdf-card-table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .pdf-card-table th, .pdf-card-table td { border: 1px solid #e2e2e2; padding: 10px 12px; }
        .pdf-card-table th { background: #f0f6e8; color: #23440a; text-align: left; }
        .pdf-card-table td { color: #2d2d2d; }
        .pdf-card-grade { font-weight: 700; color: #1d4d1d; }

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
</head>

<body>

<?php include "../includes/sidebar_instructor.php"; ?>

<div class="main">
    <div class="grading-container">
        <h2><i class="fa fa-pen-to-square"></i> Grade Management</h2>

        <?php if (empty($subjectGroups)): ?>
            <div class="no-subjects">
                <i class="fa fa-book"></i>
                <p>No subjects assigned to you yet.</p>
                <p>Contact the administrator to assign subjects.</p>
            </div>
        <?php elseif (!$selectedGrade || !$selectedSubject || !$selectedSection): ?>
            <div class="grading-period-selector" style="margin: 12px 0 18px; display:flex; flex-wrap:wrap; align-items:center; gap:12px;">
                <form method="GET" style="display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin:0;">
                    <?php foreach ($gradingPeriodOptions as $periodKey => $periodLabel): ?>
                        <label class="period-option">
                            <input type="radio" name="period" value="<?php echo $periodKey; ?>" <?php echo $selectedPeriod === $periodKey ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($periodLabel); ?>
                        </label>
                    <?php endforeach; ?>
                    <button type="submit" class="secondary-btn" style="padding:8px 14px;">Change Period</button>
                </form>
            </div>
            <div class="subject-grid">
                <?php foreach ($subjectGroups as $subject): ?>
                    <a class="card-link" href="grades.php?grade=<?php echo urlencode($subject['grade_level']); ?>&subject=<?php echo urlencode($subject['subject_name']); ?>&section=<?php echo urlencode($subject['section']); ?>">
                        <div class="subject-card">
                            <h3><i class="fa fa-book"></i> <?php echo htmlspecialchars($subject['subject_name']); ?></h3>
                            <div class="grade-tag"><?php echo htmlspecialchars($subject['grade_level'] . ' - ' . $subject['section']); ?></div>
                            <div class="stats">
                                <span><i class="fa fa-users"></i> <?php echo intval($subject['students_count']); ?> Students</span>
                                <span><i class="fa fa-arrow-right"></i> View students</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="section-header">
                <div>
                    <h2><i class="fa fa-book"></i> <?php echo htmlspecialchars($selectedSubject); ?></h2>
                    <div class="section-meta"><?php echo htmlspecialchars($selectedGrade . ' - ' . $selectedSection); ?></div>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <form id="resetForm" method="POST" style="margin:0;">
                        <input type="hidden" name="reset_subject_data" value="1">
                        <input type="hidden" name="reset_grade" value="<?php echo htmlspecialchars($selectedGrade); ?>">
                        <input type="hidden" name="reset_subject" value="<?php echo htmlspecialchars($selectedSubject); ?>">
                        <input type="hidden" name="reset_section" value="<?php echo htmlspecialchars($selectedSection); ?>">
                        <input type="hidden" name="reset_period" value="<?php echo htmlspecialchars($selectedPeriod); ?>">
                        <input type="hidden" name="reset_password" id="resetPasswordInput" value="">
                        <button type="button" class="secondary-btn" onclick="confirmResetSubject()"><i class="fa fa-eraser"></i> Reset Subject</button>
                    </form>
                    <a class="back-btn" href="grades.php"><i class="fa fa-arrow-left"></i> Back to Subjects</a>
                </div>
            </div>

            <div class="grading-period-selector" style="margin: 8px 0 18px; display:flex; flex-wrap:wrap; align-items:center; gap:12px;">
                <form method="GET" style="display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin:0;">
                    <input type="hidden" name="grade" value="<?php echo htmlspecialchars($selectedGrade); ?>">
                    <input type="hidden" name="subject" value="<?php echo htmlspecialchars($selectedSubject); ?>">
                    <input type="hidden" name="section" value="<?php echo htmlspecialchars($selectedSection); ?>">
                    <?php if ($selectedStudentId > 0): ?>
                        <input type="hidden" name="student_id" value="<?php echo intval($selectedStudentId); ?>">
                    <?php endif; ?>
                    <?php foreach ($gradingPeriodOptions as $periodKey => $periodLabel): ?>
                        <label class="period-option">
                            <input type="radio" name="period" value="<?php echo $periodKey; ?>" <?php echo $selectedPeriod === $periodKey ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($periodLabel); ?>
                        </label>
                    <?php endforeach; ?>
                    <button type="submit" class="secondary-btn" style="padding:8px 14px;">Change Period</button>
                </form>
            </div>

            <?php if ($resetStatus === 'success'): ?>
                <div class="alert-box">Subject lesson content, assignments, and grading activity have been cleared for the selected subject while preserving historical grade records.</div>
            <?php elseif (!empty($resetError)): ?>
                <div class="alert-box" style="background:#fde5e5; border-color:#f5c2c7; color:#7a1f1f;"><?php echo htmlspecialchars($resetError); ?></div>
            <?php endif; ?>
            <?php if ($saveStatus === 'success'): ?>
                <div class="alert-box">Final grade saved successfully for this student, subject, and grading period.</div>
            <?php endif; ?>
            <?php if ($saveError): ?>
                <div class="alert-box" style="background:#fde5e5; border-color:#f5c2c7; color:#7a1f1f;"><?php echo htmlspecialchars($saveError); ?></div>
            <?php endif; ?>

            <div style="display:flex; justify-content:space-between; align-items:center; margin:16px 0 10px;">
                <h3 style="margin:0; font-size:1rem; color:#264d00;">Student list</h3>
                <button type="button" class="secondary-btn" id="studentListToggleBtn" onclick="toggleStudentList()"><i class="fa fa-chevron-up"></i> Collapse student list</button>
            </div>
            <div id="studentListContainer">
                <table class="student-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Student ID</th>
                            <th>Status</th>
                            <th>Grade / Section</th>
                        </tr>
                    </thead>
                <tbody>
                    <?php if (count($students) === 0): ?>
                        <tr><td colspan="4" class="no-data">No students enrolled in this subject yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><a class="student-link" href="grades.php?grade=<?php echo urlencode($selectedGrade); ?>&subject=<?php echo urlencode($selectedSubject); ?>&section=<?php echo urlencode($selectedSection); ?>&period=<?php echo urlencode($selectedPeriod); ?>&student_id=<?php echo intval($student['id']); ?>"><?php echo htmlspecialchars($student['full_name']); ?></a></td>
                                <td><?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($student['enrollment_status'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($student['grade_level'] . ' / ' . $student['section']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>

            <div class="detail-row">
                <?php if ($selectedStudentId > 0 && $studentDetails): ?>
                    <div class="detail-item">
                        <strong>Selected Student</strong>
                        <?php echo htmlspecialchars($studentDetails['full_name']); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Student Status</strong>
                        <?php echo htmlspecialchars($studentDetails['enrollment_status'] ?: 'Unknown'); ?>
                    </div>
                <?php else: ?>
                    <div class="detail-item">
                        <strong>Total Students</strong>
                        <?php echo intval($sectionStudentCount ?? fetchStudentCount($conn, $selectedGrade, $selectedSection)); ?>
                    </div>
                    <div class="detail-item">
                        <strong>Selected Student</strong>
                        Click a student above
                    </div>
                <?php endif; ?>
                <div class="detail-item" style="grid-column: span 2;">
                    <strong>Select Grading Period</strong>
                    <div class="period-options">
                        <?php foreach ($gradingPeriodOptions as $periodKey => $periodLabel): ?>
                            <label class="period-option">
                                <input class="period-checkbox" name="grading_period_selection" type="radio" value="<?php echo htmlspecialchars($periodKey); ?>" <?php echo $periodKey === $selectedPeriod ? 'checked' : ''; ?> onchange="updateSelectedPeriod(this)">
                                <?php echo htmlspecialchars($periodLabel); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="changePeriodBtn" class="secondary-btn" style="margin-top: 10px;" onclick="confirmChangePeriod()">Change Period</button>
                </div>
            </div>

            <?php if ($selectedStudentId > 0): ?>
                <div class="detail-box" id="gradeReportSection">
                    <h3>Submission details for <?php echo htmlspecialchars($studentDetails['full_name'] ?? 'Student'); ?></h3>
                    <?php if (!$studentDetails): ?>
                        <p class="no-data">Selected student was not be found. Please choose a valid student.</p>
                    <?php else: ?>
                        <div class="detail-row">
                            <div class="detail-item">
                                <strong>Student ID</strong>
                                <?php echo htmlspecialchars($studentDetails['student_id'] ?? 'N/A'); ?>
                            </div>
                            <div class="detail-item">
                                <strong>Status</strong>
                                <?php echo htmlspecialchars($studentDetails['enrollment_status'] ?? 'Unknown'); ?>
                            </div>
                            <div class="detail-item" title="<?php echo htmlspecialchars($assignmentTooltip); ?>">
                                <strong>Assignments</strong>
                                <?php echo intval($gradeReport['assignments_count']); ?>
                            </div>
                            <div class="detail-item" title="<?php echo htmlspecialchars($gradedTooltip); ?>">
                                <strong>Graded Items</strong>
                                <?php echo intval($gradeReport['graded_count']); ?>
                            </div>
                        </div>

                        <?php
                            $displayBaseGrade = $gradeReport['original_average_grade'] ?? ($gradeReport['average_grade'] !== null ? $gradeReport['average_grade'] : null);
                            $displayFinalGrade = $gradeReport['average_grade'] !== null ? $gradeReport['average_grade'] : null;
                            $displayAddedPoints = null;
                            if ($summaryRecord && $displayBaseGrade !== null && $displayFinalGrade !== null) {
                                $displayAddedPoints = intval($displayFinalGrade) - intval($displayBaseGrade);
                                if ($displayAddedPoints < 0) {
                                    $displayAddedPoints = 0;
                                }
                            }
                        ?>
                        <div class="detail-row" style="margin-top: 18px;">
                            <div class="detail-item">
                                <strong>Final Average</strong>
                                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px;">
                                    <span class="final-grade">
                                        <?php
                                            if ($displayFinalGrade !== null) {
                                                if ($displayAddedPoints > 0) {
                                                    echo htmlspecialchars($displayFinalGrade . ' (' . intval($displayBaseGrade) . ' + ' . $displayAddedPoints . ')');
                                                } else {
                                                    echo htmlspecialchars($displayFinalGrade);
                                                }
                                            } else {
                                                echo '-';
                                            }
                                        ?>
                                    </span>
                                    <button type="button" class="secondary-btn" onclick="toggleFinalGradeEditor()"><i class="fa fa-plus"></i> Add Points</button>
                                </div>
                            </div>
                            <div class="detail-item">
                                <strong>Remark</strong>
                                <span class="<?php echo $gradeReport['average_grade'] !== null ? ($gradeReport['average_grade'] >= 75 ? 'remark-pass' : 'remark-fail') : ''; ?>"><?php echo htmlspecialchars($gradeReport['remark']); ?></span>
                            </div>
                            <div class="detail-item">
                                <strong>Grading Period</strong>
                                <?php echo htmlspecialchars($gradingPeriods[$selectedPeriod] ?? $gradingPeriods['1st']); ?>
                            </div>
                        </div>

                        <div id="finalGradeEditor" style="display:none; margin-top: 14px;">
                            <form id="finalGradeForm" method="POST" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
                                <input type="hidden" name="save_final_grade" value="1">
                                <input type="hidden" name="save_student_id" value="<?php echo intval($selectedStudentId); ?>">
                                <input type="hidden" name="save_grade" value="<?php echo htmlspecialchars($selectedGrade); ?>">
                                <input type="hidden" name="save_subject" value="<?php echo htmlspecialchars($selectedSubject); ?>">
                                <input type="hidden" name="save_section" value="<?php echo htmlspecialchars($selectedSection); ?>">
                                <input type="hidden" name="save_period" value="<?php echo htmlspecialchars($selectedPeriod); ?>">
                                <label style="width:100%; max-width:320px; display:flex; flex-direction:column;">
                                    Adjustment Points
                                    <input type="number" name="final_grade_value" id="finalGradeValueField" min="0" max="100" step="1" value="<?php echo htmlspecialchars($displayFinalGrade !== null ? $displayAddedPoints ?? 0 : '0'); ?>" placeholder="Enter points to add" style="margin-top:8px; padding:10px; border:1px solid #cbd5c1; border-radius:8px; width:100%;">
                                </label>
                            </form>
                        </div>

                        <div style="margin-top: 10px; display:flex; justify-content:flex-end;">
                            <button type="submit" form="finalGradeForm" class="secondary-btn" style="background:#6b8e23; border-color:#6b8e23; color:#ffffff;"><i class="fa fa-save"></i> Save Record</button>
                        </div>

                        <table class="submission-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Activity / Quiz</th>
                                    <th>Submission Status</th>
                                    <th>Files</th>
                                    <th>Grade</th>
                                    <th>Submitted At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($submissionRows) === 0): ?>
                                    <tr><td colspan="6" class="no-data">No assignments are configured for this subject yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($submissionRows as $submission): ?>
                                        <?php
                                            $filePaths = [];
                                            if (!empty($submission['submission_file_path'])) {
                                                $decodedPaths = json_decode($submission['submission_file_path'], true);
                                                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedPaths)) {
                                                    $filePaths = array_values(array_filter(array_map('trim', $decodedPaths)));
                                                } else {
                                                    $filePaths = array_values(array_filter(array_map('trim', explode('|', $submission['submission_file_path']))));
                                                }
                                            }
                                        ?>
                                        <tr class="activity-row" data-id="<?php echo intval($submission['assignment_id']); ?>" data-total-students="<?php echo intval($sectionStudentCount ?? fetchStudentCount($conn, $selectedGrade, $selectedSection)); ?>" data-graded-students="<?php echo isset($submission['graded_students']) ? intval($submission['graded_students']) : 0; ?>">
                                            <td><?php echo htmlspecialchars(ucfirst($submission['assignment_type'] ?? 'activity')); ?></td>
                                            <td>
                                                <a href="#" class="assignment-link" data-assignment-id="<?php echo intval($submission['assignment_id']); ?>" style="color:#204a11;text-decoration:underline;cursor:pointer;">
                                                    <?php echo htmlspecialchars($submission['assignment_title']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($submission['status']); ?></td>
                                            <td>
                                                <?php if (!empty($filePaths)): ?>
                                                    <?php foreach ($filePaths as $idx => $path): ?>
                                                        <a href="<?php echo htmlspecialchars(browserPath($path), ENT_QUOTES); ?>" target="_blank" rel="noopener">File <?php echo $idx + 1; ?></a><br>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $submission['grade_value'] !== null ? htmlspecialchars($submission['grade_value'] . (!empty($submission['max_points']) ? '/' . intval($submission['max_points']) : '')) : '-'; ?></td>
                                            <td><?php echo !empty($submission['submitted_at']) ? htmlspecialchars(date('F j, Y g:i A', strtotime($submission['submitted_at']))) : '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <script>
                                // Assignment click: go to Activity Grades tab and highlight
                                document.addEventListener('DOMContentLoaded', function() {
                                    document.querySelectorAll('.assignment-link[data-assignment-id]').forEach(function(link) {
                                        link.addEventListener('click', function(e) {
                                            e.preventDefault();
                                            var id = this.getAttribute('data-assignment-id');
                                            if (id) {
                                                // Go to activity grades tab and highlight
                                                var url = window.location.pathname + window.location.search.replace(/([&?])assignment_id=\d+/, '').replace(/([&?])tab=\w+/, '') + (window.location.search ? '&' : '?') + 'assignment_id=' + id + '#activity-grades';
                                                window.location.href = url;
                                            }
                                        });
                                    });
                                    // Highlight activity card if assignment_id in URL
                                    var params = new URLSearchParams(window.location.search);
                                    var focusId = params.get('assignment_id');
                                    if (focusId) {
                                        var tab = window.location.hash.replace('#','');
                                        if (tab !== 'activity-grades') {
                                            window.location.hash = '#activity-grades';
                                        }
                                        setTimeout(function() {
                                            var row = document.querySelector('.activity-row[data-id="' + focusId + '"]');
                                            if (row) {
                                                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                                row.classList.add('highlight-flash');
                                                setTimeout(function() { row.classList.remove('highlight-flash'); }, 1700);
                                            }
                                        }, 400);
                                    }
                                    // Olive green for completed activities
                                    document.querySelectorAll('.activity-row[data-id]').forEach(function(row) {
                                        var total = parseInt(row.getAttribute('data-total-students'));
                                        var graded = parseInt(row.getAttribute('data-graded-students'));
                                        if (!isNaN(total) && !isNaN(graded) && total > 0 && graded === total) {
                                            row.style.background = '#b6c96b';
                                            row.style.borderColor = '#7a8c3a';
                                        }
                                    });
                                });
                                </script>
                                <?php endif; ?>
                            </tbody>
                        </table>

                        <div class="pdf-card" id="gradeReportCard" style="margin-top: 24px;">
                            <div class="pdf-card-header">
                                <img src="../assets/images/mucalogo.png" alt="School Logo" class="pdf-card-logo">
                                <div>
                                    <h2>Grade Report Card</h2>
                                    <p><?php echo htmlspecialchars($selectedSubject); ?> - <?php echo htmlspecialchars($gradingPeriods[$selectedPeriod] ?? 'Selected period'); ?></p>
                                </div>
                            </div>
                            <div class="pdf-card-columns">
                                <div>
                                    <div class="pdf-card-row"><label>Student Name</label><span><?php echo htmlspecialchars($studentDetails['full_name'] ?? '-'); ?></span></div>
                                    <div class="pdf-card-row"><label>Student ID</label><span><?php echo htmlspecialchars($studentDetails['student_id'] ?? '-'); ?></span></div>
                                    <div class="pdf-card-row"><label>Grade Level</label><span><?php echo htmlspecialchars($studentDetails['grade_level'] ?? $selectedGrade); ?></span></div>
                                    <div class="pdf-card-row"><label>Section</label><span><?php echo htmlspecialchars($studentDetails['section'] ?? $selectedSection); ?></span></div>
                                </div>
                                <div>
                                    <div class="pdf-card-row"><label>Subject</label><span><?php echo htmlspecialchars($selectedSubject); ?></span></div>
                                    <div class="pdf-card-row"><label>Current Period</label><span><?php echo htmlspecialchars($gradingPeriods[$selectedPeriod] ?? '-'); ?></span></div>
                                    <div class="pdf-card-row"><label>Address</label><span class="pdf-card-address"><?php echo htmlspecialchars($studentDetails['address'] ?? 'Not provided'); ?></span></div>
                                </div>
                            </div>
                            <table class="pdf-card-table">
                                <thead>
                                    <tr>
                                        <th>Grading Period</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gradingPeriods as $periodKey => $periodLabel): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($periodLabel); ?></td>
                                            <td class="pdf-card-grade"><?php echo isset($periodSummaries[$periodKey]) && $periodSummaries[$periodKey]['grade_value'] !== null ? htmlspecialchars($periodSummaries[$periodKey]['grade_value']) : ''; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div class="signature-block">
                                <div class="signature-name"><?php echo htmlspecialchars($subjectTeacherName ?: '________________________'); ?></div>
                                <div class="signature-line"></div>
                                <div class="signature-subject">Subject Teacher: <?php echo htmlspecialchars($selectedSubject); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="margin-top: 8px; display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="secondary-btn" style="background:#6b8e23; border-color:#6b8e23; color:#ffffff;" onclick="exportGradeReport()"><i class="fa fa-file-pdf"></i> Export PDF</button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

    <?php include "../includes/back_to_top.php"; ?>
    <?php include "../includes/footer.php"; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
    function confirmResetSubject() {
        const password = prompt('Enter your password to confirm subject reset:');
        if (password === null) {
            return;
        }
        if (password.trim() === '') {
            alert('Password is required to reset the subject.');
            return;
        }
        if (!confirm('Resetting this subject will clear current assignments and lesson content. Do you want to continue?')) {
            return;
        }
        document.getElementById('resetPasswordInput').value = password;
        document.getElementById('resetForm').submit();
    }

    const currentSelectedPeriod = '<?php echo addslashes($selectedPeriod); ?>';
    let targetSelectedPeriod = currentSelectedPeriod;

    function updateSelectedPeriod(radio) {
        if (!radio.checked) {
            return;
        }
        targetSelectedPeriod = radio.value;
    }

    function confirmChangePeriod() {
        if (targetSelectedPeriod === currentSelectedPeriod) {
            alert('You are already viewing this grading period.');
            return;
        }

        if (!confirm('Change grading period and reload the page for the selected period?')) {
            return;
        }

        const params = new URLSearchParams(window.location.search);
        params.set('period', targetSelectedPeriod);
        window.location.search = params.toString();
    }

    function exportGradeReport() {
        const reportSection = document.getElementById('gradeReportCard');
        if (!reportSection) {
            alert('Grade report card is not available for export.');
            return;
        }
        if (!window.jspdf || !window.jspdf.jsPDF || typeof html2canvas === 'undefined') {
            alert('PDF export is unavailable. Please make sure jsPDF and html2canvas are loaded.');
            return;
        }
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ unit: 'pt', format: 'letter' });

        html2canvas(reportSection, { scale: 2, useCORS: true }).then(function (canvas) {
            const imgData = canvas.toDataURL('image/png');
            const pageWidth = doc.internal.pageSize.getWidth() - 40;
            const pageHeight = (canvas.height * pageWidth) / canvas.width;
            doc.addImage(imgData, 'PNG', 20, 20, pageWidth, pageHeight);
            doc.save('grade-report-<?php echo preg_replace('/[^A-Za-z0-9]+/', '_', $selectedSubject); ?>-<?php echo time(); ?>.pdf');
        }).catch(function (error) {
            console.error('PDF export failed:', error);
            alert('PDF export failed. Please try again.');
        });
    }

    function toggleStudentList() {
        const container = document.getElementById('studentListContainer');
        const button = document.getElementById('studentListToggleBtn');
        if (!container || !button) {
            return;
        }
        const isHidden = container.style.display === 'none';
        container.style.display = isHidden ? 'block' : 'none';
        button.innerHTML = isHidden ? '<i class="fa fa-chevron-up"></i> Collapse student list' : '<i class="fa fa-chevron-down"></i> Show student list';
    }

    function toggleFinalGradeEditor() {
        const editor = document.getElementById('finalGradeEditor');
        if (!editor) {
            return;
        }
        editor.style.display = editor.style.display === 'none' || editor.style.display === '' ? 'block' : 'none';
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
        window.location.href = `my_class.php?tab=grades#material-${materialId}`;
    }
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
        <h4 class="notification-section-title">📚 Materials to Check</h4>
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
