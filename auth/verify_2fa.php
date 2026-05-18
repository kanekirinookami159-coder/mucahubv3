<?php
include "../config/config.php";

if (!isset($_SESSION['pending_auth']) || !is_array($_SESSION['pending_auth'])) {
    header('Location: login.php');
    exit;
}

$pending = $_SESSION['pending_auth'];
$expires = isset($pending['expires']) ? intval($pending['expires']) : 0;
if (time() > $expires) {
    unset($_SESSION['pending_auth']);
    header('Location: login.php?error=expired');
    exit;
}

$errorMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';
    if ($code === '') {
        $errorMessage = 'Please enter the verification code.';
    } elseif (!preg_match('/^\d{6}$/', $code)) {
        $errorMessage = 'Code must be exactly 6 digits and contain only numbers.';
        $pending['code'] = strval(random_int(100000, 999999));
        $pending['expires'] = time() + 300;
        $_SESSION['pending_auth'] = $pending;
    } elseif ($code !== $pending['code']) {
        $errorMessage = 'Invalid verification code. A new QR code has been issued.';
        $pending['code'] = strval(random_int(100000, 999999));
        $pending['expires'] = time() + 300;
        $_SESSION['pending_auth'] = $pending;
    } else {
        $_SESSION['user_id'] = $pending['user_id'];
        $_SESSION['role'] = $pending['role'];
        $_SESSION['email'] = $pending['email'];
        $_SESSION['name'] = $pending['name'];
        $_SESSION['require_password_change'] = !empty($pending['force_password_change']);
        unset($_SESSION['pending_auth']);

        recordLogin($pending['user_id'], $pending['name'], $pending['role']);

        if (!empty($_SESSION['require_password_change'])) {
            header('Location: change_password.php');
            exit;
        }

        switch ($pending['role']) {
            case 'admin':
                $destination = '../admin/dashboard.php';
                break;
            case 'instructor':
                $destination = '../instructor/instructor_dashboard.php';
                break;
            default:
                $destination = '../student/student_dashboard.php';
                break;
        }

        header('Location: ' . $destination);
        exit;
    }
}

$qrData = 'MUCAHUB verification code: ' . $pending['code'];

// Generate QR image as a data URI so the code is not visible in page text or
// query strings. Try file_get_contents first, fall back to cURL.
$qrApi = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($qrData);
$qrImage = false;
if (ini_get('allow_url_fopen')) {
    $qrImage = @file_get_contents($qrApi);
}
if ($qrImage === false) {
    // cURL fallback
    $ch = curl_init($qrApi);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $qrImage = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);
    if ($qrImage === false) {
        // If QR generation fails, fall back to an empty image placeholder
        $qrImage = null;
    }
}
$qrDataUri = '';
if ($qrImage !== null && $qrImage !== false) {
    $qrDataUri = 'data:image/png;base64,' . base64_encode($qrImage);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MUCAHUB - Verify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { min-height:100vh; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%); color:#fff; }
        .container { width:min(560px,95%); padding:36px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.14); border-radius:24px; box-shadow:0 30px 80px rgba(0,0,0,0.25); backdrop-filter:blur(16px); }
        .header { text-align:center; margin-bottom:24px; }
        .header h1 { font-size:34px; margin-bottom:8px; }
        .header p { color:rgba(255,255,255,0.75); }
        .qr-box { display:grid; gap:18px; align-items:center; justify-items:center; margin-bottom:26px; }
        .qr-box img { width:220px; height:220px; border-radius:18px; background:#fff; padding:12px; }
        .code-label { color:rgba(255,255,255,0.7); font-size:14px; text-align:center; }
        form { display:grid; gap:18px; }
        .input-group { display:flex; align-items:center; gap:12px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.14); border-radius:14px; padding:14px 16px; }
        .input-group input { width:100%; border:none; outline:none; background:transparent; color:#fff; font-size:15px; }
        .input-group i { color:rgba(255,255,255,0.65); }
        .submit-btn { width:100%; border:none; border-radius:14px; padding:16px; color:#fff; font-size:17px; background:linear-gradient(135deg,#556b2f,#78a74b); cursor:pointer; }
        .error-box { min-height:24px; padding:12px 14px; background:rgba(255,66,66,0.12); border:1px solid rgba(255,66,66,0.22); border-radius:12px; color:#ffb3b3; display:<?= $errorMessage ? 'block' : 'none' ?>; }
        .small-text { color:rgba(255,255,255,0.72); font-size:13px; text-align:center; }
        @media(max-width:600px){ .qr-box img{width:180px;height:180px;} }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Verify Your Login</h1>
            <p>Scan the QR code with your camera or authenticator app; the verification code will appear in the scanner.</p>
        </div>
        <div class="qr-box">
            <?php if ($qrDataUri): ?>
                <img src="<?= $qrDataUri ?>" alt="Verification QR Code">
            <?php else: ?>
                <div style="width:220px;height:220px;border-radius:12px;background:#f3f3f3;display:flex;align-items:center;justify-content:center;color:#666">QR unavailable</div>
            <?php endif; ?>
            <div class="small-text">This code expires in <?= max(0, $expires - time()) ?> seconds.</div>
        </div>
        <div class="error-box"><?= htmlspecialchars($errorMessage) ?></div>
        <form method="POST" action="verify_2fa.php">
            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="text" name="code" placeholder="Enter verification code" required autocomplete="one-time-code" inputmode="numeric" pattern="\d{6}" maxlength="6" oninput="this.value=this.value.replace(/\D/g,'').slice(0,6);">
            </div>
            <button type="submit" class="submit-btn">Verify and Sign In</button>
        </form>
    </div>
</body>
</html>

<?php
function recordLogin($userId, $name, $role) {
    include "../includes/databases/db_connection.php";
    if (!$conn) {
        return;
    }
    $createTableSQL = "CREATE TABLE IF NOT EXISTS login_history (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        role ENUM('student','instructor','admin') NOT NULL,
        employee_number VARCHAR(50),
        login_time DATETIME NOT NULL,
        logout_time DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($createTableSQL);
    $loginTime = date('Y-m-d H:i:s');
    $insertSQL = "INSERT INTO login_history (user_id, name, role, login_time) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSQL);
    if ($stmt) {
        $stmt->bind_param('isss', $userId, $name, $role, $loginTime);
        $stmt->execute();
        $stmt->close();
    }
}
?>