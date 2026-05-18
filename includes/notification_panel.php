<style>
/* NOTIFICATION PANEL STYLES */
.notification-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding: 0 20px;
}

.notification-panel-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
}

.notification-section {
    margin-bottom: 20px;
}

.notification-section-title {
    padding: 0 20px 10px 20px;
    margin: 0;
    font-size: 0.9rem;
    font-weight: 700;
    color: #3b82f6;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.notification-item {
    padding: 12px 20px;
    margin: 0 8px 8px 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    border-left: 4px solid #3b82f6;
}

.notification-item:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.notification-announcement {
    background: #eff6ff;
}

.notification-announcement:hover {
    background: #e0f2fe;
}

.notification-assignment {
    background: #f0fdf4;
}

.notification-assignment:hover {
    background: #dcfce7;
}

.notification-submission {
    background: #fef3c7;
}

.notification-submission:hover {
    background: #fde68a;
}

.notification-item-title {
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 4px;
    font-size: 0.95rem;
}

.notification-item-meta {
    color: #475569;
    font-size: 0.85rem;
}

.notification-item-empty {
    padding: 12px 20px;
    color: #64748b;
    text-align: center;
    font-size: 0.9rem;
}
</style>

<div class="floating-buttons">
  <button id="activityBtn" title="Notifications">
    <i class="fa fa-bell"></i>
    <span class="notification-dot" id="notificationDot"></span>
  </button>
</div>

<div id="activityPanel" class="sidepanel">
  <button class="closeBtn" onclick="closeActivity()">✖</button>
  <div class="notification-panel-header">
    <h3>Notifications</h3>
  </div>
  
  <div class="notification-section">
    <h4 class="notification-section-title">📢 Announcements</h4>
    <div id="announcementsList">
      <?php if (empty($dashboardNotificationEvents)): ?>
        <div class="notification-item-empty">No announcements</div>
      <?php else: ?>
        <?php $annCount = 0; ?>
        <?php foreach ($dashboardNotificationEvents as $event): ?>
          <?php if (isset($event['type']) && $event['type'] === 'Announcement'): ?>
            <?php $annCount++; if ($annCount > 5) break; ?>
            <div class="notification-item notification-announcement" 
              <?php if (!empty($event['link']) && $event['link'] !== '#'): ?>
                style="cursor: pointer;" 
                onclick="window.location.href='<?php echo htmlspecialchars($event['link']); ?>';"
              <?php endif; ?>>
              <div class="notification-item-title"><?php echo htmlspecialchars($event['title']); ?></div>
              <div class="notification-item-meta"><?php echo date('M j, Y', strtotime($event['date'])); ?></div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($annCount === 0): ?>
          <div class="notification-item-empty">No announcements</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="notification-section">
    <h4 class="notification-section-title">✓ Activities</h4>
    <div id="activitiesList" style="max-height: 300px; overflow-y: auto;">
      <?php if (empty($dashboardNotificationEvents)): ?>
        <div class="notification-item-empty">No pending activities</div>
      <?php else: ?>
        <?php $activityCount = 0; ?>
        <?php foreach ($dashboardNotificationEvents as $event): ?>
          <?php if (isset($event['type']) && in_array($event['type'], ['Submission', 'Assignment', 'Activity', 'Lesson', 'User'])): ?>
            <?php $activityCount++; if ($activityCount > 5) break; ?>
            <div class="notification-item notification-assignment" 
              <?php if (!empty($event['link']) && $event['link'] !== '#'): ?>
                style="cursor: pointer; border-left-color: #3b82f6;" 
                onclick="window.location.href='<?php echo htmlspecialchars($event['link']); ?>';"
              <?php else: ?>
                style="border-left-color: #3b82f6;"
              <?php endif; ?>>
              <div class="notification-item-title"><?php echo htmlspecialchars($event['title']); ?></div>
              <div class="notification-item-meta"><?php echo htmlspecialchars($event['text'] ?? ''); ?> • <?php echo date('M j, Y', strtotime($event['date'])); ?></div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($activityCount === 0): ?>
          <div class="notification-item-empty">No activities</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
window.dashboardNotificationEvents = <?php echo json_encode($dashboardNotificationEvents ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.mucahubUserId = <?php echo json_encode($_SESSION['user_id'] ?? 'guest'); ?>;
window.mucahubUserRole = <?php echo json_encode($_SESSION['role'] ?? 'guest'); ?>;
</script>
