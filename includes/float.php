<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

include_once __DIR__ . '/dashboard_notifications.php';
?>

<?php include __DIR__ . '/notification_panel.php'; ?>

<script>
window.dashboardNotificationEvents = <?php echo json_encode($dashboardNotificationEvents ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.mucahubUserId = <?php echo json_encode($_SESSION['user_id'] ?? 'admin'); ?>;
window.mucahubUserRole = <?php echo json_encode($_SESSION['role'] ?? 'admin'); ?>;
</script>