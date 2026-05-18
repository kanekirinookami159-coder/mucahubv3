<?php
include "../config/database.php";
include "../config/config.php";
include "../includes/functions.php";

checkLogin();

if(isset($_POST['upload'])){

$title = mysqli_real_escape_string($conn, $_POST['title']);
$description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
$dueDate = mysqli_real_escape_string($conn, $_POST['due_date'] ?? date('Y-m-d H:i:s', strtotime('+1 week')));
$maxScore = intval($_POST['max_score'] ?? 100);
$file = '';

// Handle file upload if provided
if(isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $fileName = basename($_FILES['file']['name']);
    $tmpName = $_FILES['file']['tmp_name'];
    $uploadDir = "../assets/uploads/lessons/";
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $filePath = $uploadDir . $fileName;
    if(move_uploaded_file($tmpName, $filePath)) {
        $file = $fileName;
    }
}

$stmt = $conn->prepare("INSERT INTO assignments(title, description, due_date, max_score, status, created_at) VALUES(?, ?, ?, ?, 'open', NOW())");
$stmt->bind_param("sssi", $title, $description, $dueDate, $maxScore);

if($stmt->execute()) {
    echo '<div style="color: green;">Assignment uploaded successfully!</div>';
} else {
    echo '<div style="color: red;">Error: ' . $stmt->error . '</div>';
}

}
?>

<link rel="stylesheet" href="../assets/css/style.css">

<div class="sidebar">
<a href="dashboard.php">Dashboard</a>
<a href="lessons.php">Lessons</a>
</div>

<div class="main">

<h2>Upload Lesson</h2>

<div class="card">

<form method="POST" enctype="multipart/form-data">

<input type="text" name="title" placeholder="Lesson Title">

<input type="file" name="file">

<button name="upload">Upload Lesson</button>

</form>

</div>

</div>