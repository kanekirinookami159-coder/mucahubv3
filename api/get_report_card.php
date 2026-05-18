<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/databases/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Check user is logged in as student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$studentId = intval($_SESSION['user_id']);

try {
    // Fetch student info
    $studentQuery = "SELECT id, first_name, middle_name, last_name, student_id, grade_level, section, adviser FROM students WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($studentQuery);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    if (!$student) {
        throw new Exception("Student not found");
    }
    
    // Build student name
    $nameParts = array_filter([
        trim($student['first_name']),
        trim($student['middle_name']),
        trim($student['last_name'])
    ]);
    $studentName = implode(' ', $nameParts) ?: 'Student';
    
    // Fetch all grades for this student
    $gradesQuery = "
        SELECT subject_name, grading_period, grade_value
        FROM teacher_grade_records 
        WHERE student_id = ? AND grade_level = ? AND section = ?
        AND grading_period IN ('1st', '2nd', '3rd', '4th')
        ORDER BY subject_name ASC, FIELD(grading_period, '1st', '2nd', '3rd', '4th')
    ";
    
    $stmt = $conn->prepare($gradesQuery);
    if (!$stmt) {
        throw new Exception("Prepare grades failed: " . $conn->error);
    }
    
    $stmt->bind_param('iss', $studentId, $student['grade_level'], $student['section']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Organize grades by subject and period
    $allGrades = []; // subject => [period => grade]
    $periodGrades = ['1st' => [], '2nd' => [], '3rd' => [], '4th' => []]; // period => [grades]
    
    while ($row = $result->fetch_assoc()) {
        $subject = $row['subject_name'];
        $period = $row['grading_period'];
        $grade = intval($row['grade_value']);
        
        if (!isset($allGrades[$subject])) {
            $allGrades[$subject] = ['1st' => null, '2nd' => null, '3rd' => null, '4th' => null];
        }
        $allGrades[$subject][$period] = $grade;
        
        // Track for period averages
        if (!isset($periodGrades[$period])) {
            $periodGrades[$period] = [];
        }
        $periodGrades[$period][] = $grade;
    }
    $stmt->close();
    
    // Build subjects data with subject final grades
    $subjectsData = [];
    foreach ($allGrades as $subject => $grades) {
        // Calculate final grade for this subject (average of all periods)
        $gradeValues = array_filter($grades, function($v) { return $v !== null; });
        $finalGrade = null;
        
        if (count($gradeValues) === 4) {
            $finalGrade = round(array_sum($gradeValues) / 4, 2);
        }
        
        $subjectsData[] = [
            'name' => $subject,
            'grade1st' => $grades['1st'],
            'grade2nd' => $grades['2nd'],
            'grade3rd' => $grades['3rd'],
            'grade4th' => $grades['4th'],
            'finalGrade' => $finalGrade
        ];
    }
    
    // Calculate final grades for each period (average across all subjects)
    $periodFinalGrades = [];
    foreach ($periodGrades as $period => $grades) {
        if (count($grades) > 0) {
            $periodFinalGrades[$period] = round(array_sum($grades) / count($grades), 2);
        } else {
            $periodFinalGrades[$period] = null;
        }
    }
    
    // Calculate overall final grade (average of all subjects' final grades)
    $finalGradeValues = array_filter(array_map(function($s) { return $s['finalGrade']; }, $subjectsData), function($v) { return $v !== null; });
    $overallFinalGrade = null;
    if (count($finalGradeValues) > 0) {
        $overallFinalGrade = round(array_sum($finalGradeValues) / count($finalGradeValues), 2);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'student' => [
                'name' => $studentName,
                'studentId' => $student['student_id'],
                'gradeLevel' => $student['grade_level'],
                'section' => $student['section']
            ],
            'adviser' => trim($student['adviser']) ?: 'N/A',
            'subjects' => $subjectsData,
            'periodFinalGrades' => $periodFinalGrades,
            'overallFinalGrade' => $overallFinalGrade,
            'totalSubjects' => count($subjectsData),
            'schoolYear' => date('Y') . '-' . (date('Y') + 1)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
