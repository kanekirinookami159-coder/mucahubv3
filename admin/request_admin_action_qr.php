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

$code = strval(random_int(100000, 999999));
$_SESSION['admin_action_code'] = $code;
$_SESSION['admin_action_expires'] = time() + 300;

$qrData = 'MUCAHUB Admin Action Code: ' . $code;
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

echo json_encode([
    'success' => true,
    'qrDataUri' => $qrDataUri,
    'message' => 'Scan the QR code and enter the code shown in the scanner to authorize the password reset.'
]);
