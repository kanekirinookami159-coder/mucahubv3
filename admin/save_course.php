<?php
include "../includes/databases/db_connection.php";
header('Content-Type: application/json');

// Support single-subject operations or batch update using subjects[]
$grade = isset($_POST['grade']) ? mysqli_real_escape_string($conn, $_POST['grade']) : '';
$id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;

// Batch mode: subjects[] provided
if (isset($_POST['subjects']) && is_array($_POST['subjects'])) {
    if ($grade === '') {
        echo json_encode(["success" => false, "message" => "Grade is required for batch update."]);
        exit;
    }

    // Clean subjects
    $submitted = [];
    foreach ($_POST['subjects'] as $s) {
        $s = trim($s);
        if ($s === '') continue;
        $submitted[] = $conn->real_escape_string($s);
    }

    // Begin transaction
    $conn->begin_transaction();
    try {
        // Fetch existing subjects for grade
        $existing = [];
        $res = $conn->query("SELECT id, subject_name FROM courses WHERE grade_level='".$conn->real_escape_string($grade)."'");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $existing[$row['subject_name']] = intval($row['id']);
            }
        }

        // Insert new ones
        foreach ($submitted as $subj) {
            if (!isset($existing[$subj])) {
                $conn->query("INSERT INTO courses (subject_name, grade_level, created_at) VALUES ('".$conn->real_escape_string($subj)."', '".$conn->real_escape_string($grade)."', NOW())");
            }
        }

        // Delete those not in submitted list
        if (count($submitted) === 0) {
            // remove all for grade
            $conn->query("DELETE FROM courses WHERE grade_level='".$conn->real_escape_string($grade)."'");
        } else {
            $inList = "'" . implode("','", array_map(function($v){ return $v; }, $submitted)) . "'";
            $conn->query("DELETE FROM courses WHERE grade_level='".$conn->real_escape_string($grade)."' AND subject_name NOT IN ($inList)");
        }

        $conn->commit();
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Batch update failed: " . $e->getMessage()]);
    }
    exit;
}

// Single subject mode (legacy)
$subject = isset($_POST['subject']) ? mysqli_real_escape_string($conn, $_POST['subject']) : '';
if ($subject === '' || $grade === '') {
    echo json_encode(["success" => false, "message" => "Subject and grade are required."]);
    exit;
}

if ($id > 0) {
    $check = mysqli_query($conn, "SELECT * FROM courses WHERE subject_name='$subject' AND grade_level='$grade' AND id<>$id");
    if (mysqli_num_rows($check) === 0) {
        mysqli_query($conn, "UPDATE courses SET subject_name='$subject', grade_level='$grade' WHERE id=$id");
        echo json_encode(["success" => true, "id" => $id]);
    } else {
        echo json_encode(["success" => false, "message" => "A course with this name and grade already exists."]);
    }
    exit;
}

$check = mysqli_query($conn, "SELECT * FROM courses WHERE subject_name='$subject' AND grade_level='$grade'");

if (mysqli_num_rows($check) === 0) {
    mysqli_query($conn, "INSERT INTO courses (subject_name, grade_level, created_at) VALUES ('$subject','$grade', NOW())");
    echo json_encode(["success" => true, "id" => mysqli_insert_id($conn)]);
} else {
    echo json_encode(["success" => false, "message" => "Subject already exists for this grade."]);
}
?>