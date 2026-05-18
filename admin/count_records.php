<?php
include '../config/database.php';

$tables = ['students', 'assignments', 'assignment_submissions', 'login_history'];

foreach ($tables as $table) {
    $r = $conn->query("SELECT COUNT(*) as c FROM $table");
    if ($r) {
        $row = $r->fetch_assoc();
        echo "$table: " . $row['c'] . "\n";
    } else {
        echo "$table: ERROR\n";
    }
}

$conn->close();
?>
