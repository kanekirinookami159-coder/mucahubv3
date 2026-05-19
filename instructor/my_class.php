<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../includes/functions.php";
require_once "../includes/databases/db_connection.php";

/*
|--------------------------------------------------------------------------
| CHECK LOGIN / ROLE
|--------------------------------------------------------------------------
*/
checkLogin();
checkRole('instructor');

/*
|--------------------------------------------------------------------------
| GET INSTRUCTOR INFO
|--------------------------------------------------------------------------
*/
$instructorId = intval($_SESSION['user_id'] ?? 0);

$instructorName = 'Instructor';

$sql = "
    SELECT first_name, last_name
    FROM instructors
    WHERE id = ?
    LIMIT 1
";

if ($stmt = $conn->prepare($sql)) {

    $stmt->bind_param('i', $instructorId);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {

        $instructorName =
            $row['first_name'] . ' ' . $row['last_name'];
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Class</title>

<style>

body{
    font-family: Arial, sans-serif;
    background:#f5f5f5;
    margin:0;
    padding:30px;
}

.container{
    max-width:900px;
    margin:auto;
    background:#fff;
    padding:30px;
    border-radius:10px;
    box-shadow:0 0 10px rgba(0,0,0,0.1);
}

h1{
    color:#333;
}

</style>

</head>

<body>

<div class="container">

    <h1>
        Welcome,
        <?php echo htmlspecialchars($instructorName); ?>
    </h1>

    <p>
        My Class page loaded successfully.
    </p>

</div>

</body>
</html>
