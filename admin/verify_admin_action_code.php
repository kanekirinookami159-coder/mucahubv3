<?php
require_once __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required.']);
    exit;
}

$code = trim($_POST['code'] ?? '');
if ($code === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Verification code is required.']);
    exit;
}

$expected = $_SESSION['admin_action_code'] ?? '';
$expires = intval($_SESSION['admin_action_expires'] ?? 0);

if ($expected === '' || time() > $expires) {
    unset($_SESSION['admin_action_code'], $_SESSION['admin_action_expires']);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Your authorization code has expired. Please request a new QR code.']);
    exit;
}

if ($code !== $expected) {
    $newCode = strval(random_int(100000, 999999));
    $_SESSION['admin_action_code'] = $newCode;
    $_SESSION['admin_action_expires'] = time() + 300;
    $qrData = 'MUCAHUB Admin Action Code: ' . $newCode;
    $qrApi = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . urlencode($qrData);
    $qrImage = false;

    if (ini_get('allow_url_fopen')) {
        $qrImage = @file_get_contents($qrApi);
    }

    if ($qrImage === false && function_exists('curl_init')) {
        $ch = curl_init($qrApi);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $qrImage = curl_exec($ch);
        curl_close($ch);
    }

    $qrDataUri = '';
    if ($qrImage !== false && $qrImage !== null) {
        $qrDataUri = 'data:image/png;base64,' . base64_encode($qrImage);
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid code. A new QR authorization code has been generated.',
        'qrDataUri' => $qrDataUri
    ]);
    exit;
}

unset($_SESSION['admin_action_code'], $_SESSION['admin_action_expires']);
echo json_encode(['success' => true, 'message' => 'Verification successful. You may now reset the password.']);
