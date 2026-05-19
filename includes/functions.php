<?php

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/
function checkLogin()
{
    if (empty($_SESSION['user_id'])) {

        header("Location: ../auth/login.php");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| CHECK ROLE
|--------------------------------------------------------------------------
*/
function checkRole($role)
{
    if (
        empty($_SESSION['role']) ||
        $_SESSION['role'] !== $role
    ) {

        header("Location: ../auth/login.php");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| GET USER NAME
|--------------------------------------------------------------------------
*/
function getUserName($conn, $user_id)
{
    $user_id = intval($user_id);

    $sql = "
        SELECT name
        FROM users
        WHERE id = ?
        LIMIT 1
    ";

    if ($stmt = $conn->prepare($sql)) {

        $stmt->bind_param('i', $user_id);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result && $row = $result->fetch_assoc()) {

            $stmt->close();

            return $row['name'];
        }

        $stmt->close();
    }

    return 'Unknown';
}

/*
|--------------------------------------------------------------------------
| CHECK ENROLLMENT
|--------------------------------------------------------------------------
*/
function isEnrolled($conn, $student_id, $subject_id)
{
    $student_id = intval($student_id);

    $subject_id = intval($subject_id);

    $sql = "
        SELECT id
        FROM enrollments
        WHERE student_id = ?
        AND subject_id = ?
        LIMIT 1
    ";

    if ($stmt = $conn->prepare($sql)) {

        $stmt->bind_param(
            'ii',
            $student_id,
            $subject_id
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $exists = (
            $result &&
            $result->num_rows > 0
        );

        $stmt->close();

        return $exists;
    }

    return false;
}
?>
