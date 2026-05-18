<?php

function checkLogin() {
    if(empty($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit;
    }
}

function checkRole($role) {
    if(empty($_SESSION['role']) || $_SESSION['role'] != $role) {
        header("Location: ../auth/login.php");
        exit;
    }
}

function getUserName($conn, $user_id) {
    $result = $conn->query("SELECT name FROM users WHERE id = '$user_id'");
    if($result && $row = $result->fetch_assoc()) {
        return $row['name'];
    }
    return 'Unknown';
}

function isEnrolled($conn, $student_id, $subject_id) {
    $result = $conn->query("SELECT id FROM enrollments WHERE student_id = '$student_id' AND subject_id = '$subject_id'");
    return $result && $result->num_rows > 0;
}

?>