<?php
include '../config/database.php';

echo "=== TESTING ASSIGNMENT INSERT ===\n\n";

// Simulate an assignment form submission
$_POST = [
    'title' => 'Math Homework Chapter 5',
    'description' => 'Solve problems 1-20 from the textbook',
    'due_date' => date('Y-m-d H:i:s', strtotime('+1 week')),
    'max_score' => 100
];

$title = $_POST['title'];
$description = $_POST['description'];
$dueDate = $_POST['due_date'];
$maxScore = intval($_POST['max_score']);

$sql = "INSERT INTO assignments(title, description, due_date, max_score, status, created_at) VALUES('$title', '$description', '$dueDate', $maxScore, 'open', NOW())";

echo "SQL: $sql\n\n";

if (mysqli_query($conn, $sql)) {
    echo "✓ SUCCESS! Assignment inserted.\n";
    
    // Verify
    $result = $conn->query("SELECT COUNT(*) as total FROM assignments");
    $row = $result->fetch_assoc();
    echo "Total assignments now: " . $row['total'] . "\n";
    
    $result = $conn->query("SELECT * FROM assignments ORDER BY id DESC LIMIT 1");
    if ($row = $result->fetch_assoc()) {
        echo "Latest assignment: " . $row['title'] . " (Due: " . $row['due_date'] . ")\n";
    }
} else {
    echo "✗ ERROR: " . mysqli_error($conn) . "\n";
}

$conn->close();
?>
