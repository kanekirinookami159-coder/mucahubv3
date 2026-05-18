<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/dashboard_notifications.php';
?>

<?php include __DIR__ . '/notification_panel.php'; ?>