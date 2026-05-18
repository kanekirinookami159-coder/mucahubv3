<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/databases/db_connection.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['require_password_change'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: ../admin/dashboard.php');
            break;
        case 'instructor':
            header('Location: ../instructor/instructor_dashboard.php');
            break;
        default:
            header('Location: ../student/student_dashboard.php');
            break;
    }
    exit;
}

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['password_confirm'] ?? '');

    if ($password === '' || $confirm === '') {
        $errorMessage = 'Both password fields are required.';
    } elseif ($password !== $confirm) {
        $errorMessage = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $errorMessage = 'Password must be at least 6 characters long.';
    } else {
        $table = '';
        switch ($_SESSION['role']) {
            case 'student':
                $table = 'students';
                break;
            case 'instructor':
            case 'admin':
                $table = 'instructors';
                break;
        }

        if ($table === '') {
            $errorMessage = 'Unable to update password for your account.';
        } else {
            $userId = intval($_SESSION['user_id']);
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE $table SET password = ?, force_password_change = 0 WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('si', $hash, $userId);
                if ($stmt->execute()) {
                    $_SESSION['require_password_change'] = false;
                    $successMessage = 'Password updated successfully. Redirecting...';
                    switch ($_SESSION['role']) {
                        case 'admin':
                            header('Refresh: 1; url=../admin/dashboard.php');
                            break;
                        case 'instructor':
                            header('Refresh: 1; url=../instructor/instructor_dashboard.php');
                            break;
                        default:
                            header('Refresh: 1; url=../student/student_dashboard.php');
                            break;
                    }
                } else {
                    $errorMessage = 'Unable to update password: ' . htmlspecialchars($stmt->error);
                }
                $stmt->close();
            } else {
                $errorMessage = 'Database error while updating your password.';
            }
        }
    }
}

$displayName = htmlspecialchars($_SESSION['name'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MUCAHUB Change Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:Segoe UI, sans-serif; background:linear-gradient(135deg,#1b263b,#0f172a); color:#fff; }
        .container { width:min(500px,95%); background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.14); backdrop-filter:blur(16px); border-radius:24px; padding:32px; box-shadow:0 30px 80px rgba(0,0,0,0.3); }
        h1 { font-size:28px; margin-bottom:6px; }
        p { color:rgba(255,255,255,0.76); margin-bottom:24px; }
        .input-group { display:grid; gap:10px; margin-bottom:18px; }
        .input-group label { font-size:0.95rem; color:#dbeafe; }
        .input-group input { width:100%; padding:14px 16px; border-radius:14px; border:1px solid rgba(255,255,255,0.18); background:rgba(255,255,255,0.08); color:#fff; font-size:1rem; }
        .message { padding:14px 16px; border-radius:12px; margin-bottom:18px; }
        .message.error { background:rgba(255,66,66,0.16); border:1px solid rgba(255,66,66,0.28); color:#ffe3e3; }
        .message.success { background:rgba(34,197,94,0.16); border:1px solid rgba(34,197,94,0.28); color:#dcfce7; }
        .submit-btn { width:100%; border:none; border-radius:14px; padding:16px; font-size:1rem; color:#fff; background:linear-gradient(135deg,#4f46e5,#2563eb); cursor:pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome back, <?= $displayName ?></h1>
        <p>For security, please set a new password before continuing into MUCAHUB.</p>
        <?php if ($errorMessage): ?>
            <div class="message error"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>
        <?php if ($successMessage): ?>
            <div class="message success"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>
        <form method="POST" action="change_password.php">
            <div class="input-group">
                <label for="password">New Password</label>
                <input type="password" name="password" id="password" required autocomplete="new-password" placeholder="Enter new password">
            </div>
            <div class="input-group">
                <label for="password_confirm">Confirm New Password</label>
                <input type="password" name="password_confirm" id="password_confirm" required autocomplete="new-password" placeholder="Repeat new password">
            </div>
            <button type="submit" class="submit-btn">Update Password</button>
        </form>
    </div>
</body>
</html>
