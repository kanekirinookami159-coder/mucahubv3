<?php
include "../config/config.php";
include "../includes/databases/db_connection.php";
include "../includes/functions.php";
checkLogin();
checkRole('instructor');

$instructorId = intval($_SESSION['user_id'] ?? 0);

$announcements = [];
$announcementQuery = "SELECT * FROM announcements WHERE target IN ('instructor','all') ORDER BY created_at DESC LIMIT 10";
if ($result = $conn->query($announcementQuery)) {
    while ($row = $result->fetch_assoc()) {
        $row['files'] = json_decode($row['files'], true) ?: [];
        $announcements[] = $row;
    }
}

include "../includes/dashboard_notifications.php";

// Get recent announcements for notification panel
$recentAnnouncements = [];
$announcementsForPanel = "SELECT id, title, created_at FROM announcements WHERE target IN ('teacher','all') ORDER BY created_at DESC LIMIT 5";
if ($result = $conn->query($announcementsForPanel)) {
    while ($row = $result->fetch_assoc()) {
        $recentAnnouncements[] = $row;
    }
}

// Get recent lessons/materials uploaded by this instructor
$recentMaterials = [];
$materialsQuery = "SELECT id, title, subject_name, created_at FROM teacher_lessons WHERE instructor_id = $instructorId ORDER BY created_at DESC LIMIT 5";
if ($result = $conn->query($materialsQuery)) {
    while ($row = $result->fetch_assoc()) {
        $recentMaterials[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>

<head>

<title>MUCAHUB Announcements</title>

<link rel="stylesheet" href="../assets/css/dashboard.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.welcome-section {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
  color: white;
  padding: 40px 20px;
  border-radius: 10px;
  margin-bottom: 30px;
  text-align: center;
  border: 1px solid rgba(85, 107, 47, 0.3);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.welcome-section h1 {
  margin: 0;
  font-size: 2.5em;
  margin-bottom: 10px;
  text-shadow: 0 0 20px rgba(85, 107, 47, 0.5);
}

.welcome-section p {
  margin: 0;
  font-size: 1.1em;
  opacity: 0.9;
}

.announcements-section {
  margin-top: 40px;
}

.announcements-section h2 {
  font-size: 1.8em;
  margin-bottom: 20px;
  color: #1a1a2e;
  border-bottom: 3px solid #556b2f;
  padding-bottom: 10px;
}

.announcement-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.announcement-card {
  background: white;
  border-radius: 12px;
  border: 1px solid #ddd;
  box-shadow: 0 3px 14px rgba(0, 0, 0, 0.08);
  padding: 18px;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.announcement-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.12);
}

.announcement-card.highlight-active {
  animation: beat-highlight 1.2s ease 2;
  border-color: #1d4ed8;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
}

@keyframes beat-highlight {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.02); }
}

.announcement-card-image {
  width: 100%;
  max-height: 180px;
  object-fit: cover;
  border-radius: 10px;
  margin-bottom: 14px;
}

.announcement-title-link,
.announcement-image-link {
  text-decoration: none;
  color: inherit;
  display: block;
  cursor: pointer;
}

.announcement-title-link:hover .announcement-card-title {
  color: #556b2f;
  text-decoration: underline;
}

.announcement-image-link:hover img {
  opacity: 0.9;
}

.announcement-card-title {
  font-size: 1.3em;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 8px;
}

.announcement-card-meta,
.announcement-card-target {
  font-size: 0.95em;
  color: #666;
  margin-bottom: 10px;
}

.announcement-card-content {
  font-size: 0.95em;
  color: #3a3a3a;
  line-height: 1.6;
  margin-bottom: 10px;
}

.announcement-files {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 10px;
}

.announcement-files a {
  display: inline-block;
  background: linear-gradient(135deg, #556b2f 0%, #6b8e23 100%);
  color: white;
  padding: 8px 15px;
  border-radius: 5px;
  text-decoration: none;
  font-size: 0.9em;
  transition: all 0.2s ease;
}

.announcement-files a:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(85, 107, 47, 0.3);
}

.announcement-popup {
  display: none;
  position: fixed;
  inset: 0;
  z-index: 1100;
  background: rgba(0, 0, 0, 0.6);
  justify-content: center;
  align-items: center;
  padding: 20px;
}

.announcement-popup-content {
  background: white;
  border-radius: 16px;
  padding: 24px;
  width: 760px;
  max-width: 760px;
  min-width: 760px;
  max-height: 85vh;
  overflow-y: auto;
  overflow-wrap: break-word;
  word-wrap: break-word;
  hyphens: auto;
  position: relative;
  box-shadow: 0 18px 60px rgba(0, 0, 0, 0.18);
}

@media (max-width: 820px) {
  .announcement-popup-content {
    width: 100%;
    max-width: 100%;
    min-width: auto;
  }
}

.popup-close {
  position: absolute;
  top: 14px;
  right: 14px;
  font-size: 24px;
  color: #333;
  cursor: pointer;
}

.popup-body {
  margin-top: 18px;
  line-height: 1.75;
  color: #333;
}

.announcement-popup-content img {
  width: 100%;
  border-radius: 10px;
  margin-top: 16px;
}

.no-announcements {
  text-align: center;
  padding: 40px;
  color: #6b8e23;
  font-size: 1.1em;
}

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
  color: #556b2f;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.notification-item {
  padding: 12px 20px;
  margin: 0 8px 8px 8px;
  border-radius: 8px;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
  border-left: 4px solid #556b2f;
}

.notification-item:hover {
  transform: translateX(4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.notification-announcement {
  background: #eef6df;
}

.notification-announcement:hover {
  background: #e0f0ce;
}

.notification-material {
  background: #e8f4f8;
}

.notification-material:hover {
  background: #d8ebf3;
}

.notification-item-title {
  font-weight: 600;
  color: #1a1a2e;
  margin-bottom: 4px;
  font-size: 0.95rem;
}

.notification-item-meta {
  font-size: 0.85rem;
  color: #666;
}

.notification-item-empty {
  padding: 12px 20px;
  color: #999;
  font-size: 0.9rem;
  text-align: center;
}

.notification-item.unread {
  background: white !important;
  border-color: #3b82f6;
}

.notification-activity {
  background: #fef3c7;
}

.notification-activity:hover {
  background: #fde68a;
}
</style>

</head>

<body>

<!-- SIDEBAR -->
<?php include "../includes/sidebar_instructor.php"; ?>


<!-- MAIN -->
<div class="main">

<div class="welcome-section">
  <h1>📢 Announcements</h1>
  <p>View and manage course announcements for your students.</p>
</div>

<div class="announcements-section">
  <h2>Recent Announcements</h2>
  <?php if (empty($announcements)): ?>
    <div id="announcementsContainer">
      <p class="no-announcements">No announcements available.</p>
    </div>
  <?php else: ?>
    <div class="announcement-grid" id="announcementsContainer">
      <?php foreach ($announcements as $index => $announcement): ?>
        <?php $firstImage = !empty($announcement['files']) ? $announcement['files'][0] : ''; ?>
        <div id="announcement-<?php echo intval($announcement['id']); ?>" class="announcement-card" onclick="openAnnouncementPopup(<?php echo $index; ?>)">
          <?php if ($firstImage): ?>
            <img src="../<?php echo htmlspecialchars($firstImage); ?>" class="announcement-card-image" alt="Announcement image">
          <?php endif; ?>
          <div class="announcement-card-title"><?php echo htmlspecialchars($announcement['title'] ?: 'Announcement'); ?></div>
          <div class="announcement-card-meta"><?php echo date('F j, Y g:i A', strtotime($announcement['created_at'])); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

</div>


<!-- FLOAT BUTTONS -->
<div class="floating-buttons">
    <button id="activityBtn" title="Notifications">
        <i class="fa fa-bell"></i>
        <span class="notification-dot" id="notificationDot"></span>
    </button>
</div>

<!-- NOTIFICATION PANEL -->
<div id="activityPanel" class="sidepanel">
    <button class="closeBtn" onclick="closeActivity()">✖</button>
    <div class="notification-panel-header">
        <h3>Notifications</h3>
    </div>
    
    <div class="notification-section">
        <h4 class="notification-section-title">📢 Announcements</h4>
        <div id="announcementsList">
            <?php if (empty($recentAnnouncements)): ?>
                <div class="notification-item-empty">No announcements</div>
            <?php else: ?>
                <?php foreach ($recentAnnouncements as $ann): ?>
                    <div class="notification-item notification-announcement" onclick="navigateToAnnouncement(<?php echo intval($ann['id']); ?>)">
                        <div class="notification-item-title"><?php echo htmlspecialchars($ann['title']); ?></div>
                        <div class="notification-item-meta"><?php echo date('M j, Y', strtotime($ann['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="notification-section">
        <h4 class="notification-section-title">📚 Materials</h4>
        <div id="materialsList">
            <?php if (empty($recentMaterials)): ?>
                <div class="notification-item-empty">No materials uploaded</div>
            <?php else: ?>
                <?php foreach ($recentMaterials as $mat): ?>
                    <div class="notification-item notification-material" onclick="navigateToMaterial(<?php echo intval($mat['id']); ?>)">
                        <div class="notification-item-title"><?php echo htmlspecialchars($mat['title']); ?></div>
                        <div class="notification-item-meta"><?php echo htmlspecialchars($mat['subject_name']); ?> • <?php echo date('M j, Y', strtotime($mat['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
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
                    <?php if (isset($event['type']) && in_array($event['type'], ['Submission', 'Assignment'])): ?>
                        <?php $activityCount++; if ($activityCount > 5) break; ?>
                        <div class="notification-item notification-activity" onclick="<?php echo !empty($event['link']) ? "window.location.href='" . htmlspecialchars($event['link']) . "'" : ""; ?>" style="cursor: pointer; border-left-color: #f59e0b;">
                            <div class="notification-item-title"><?php echo htmlspecialchars($event['title']); ?></div>
                            <div class="notification-item-meta"><?php echo htmlspecialchars($event['text']); ?> • <?php echo date('M j, Y', strtotime($event['date'])); ?></div>
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
<?php include "../includes/back_to_top.php"; ?>
<?php include "../includes/footer.php"; ?>

<script>window.mucahubInstructorId = <?php echo intval($_SESSION['user_id']); ?>;</script>
<script>
    window.dashboardNotificationEvents = <?php echo json_encode($dashboardNotificationEvents ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    window.dashboardAssignmentEvents = <?php echo json_encode($dashboardAssignmentEvents ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<div id="announcementPopup" class="announcement-popup">
  <div class="announcement-popup-content" id="announcementPopupContent"></div>
</div>
<script>
const announcementData = <?php echo json_encode($announcements, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

function navigateToAnnouncement(announcementId) {
  // Open popup for the announcement
  const announcement = announcementData.find(a => a.id == announcementId);
  if (announcement) {
    const index = announcementData.indexOf(announcement);
    openAnnouncementPopup(index);
  }
}

function escapeHtml(text) {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function linkifyHtml(html) {
  const wrapper = document.createElement('div');
  wrapper.innerHTML = html;
  const urlRegex = /((https?:\/\/|www\.)[^\s<]+)/gi;

  function walk(node) {
    if (node.nodeType === Node.TEXT_NODE) {
      const text = node.nodeValue;
      if (!text || !urlRegex.test(text)) {
        return;
      }
      const frag = document.createDocumentFragment();
      let lastIndex = 0;
      text.replace(urlRegex, (match, url, prefix, offset) => {
        const before = text.slice(lastIndex, offset);
        if (before) {
          frag.appendChild(document.createTextNode(before));
        }
        const href = prefix.toLowerCase().startsWith('http') ? match : 'http://' + match;
        const a = document.createElement('a');
        a.href = href;
        a.target = '_blank';
        a.rel = 'noopener noreferrer';
        a.textContent = match;
        frag.appendChild(a);
        lastIndex = offset + match.length;
      });
      const after = text.slice(lastIndex);
      if (after) {
        frag.appendChild(document.createTextNode(after));
      }
      node.replaceWith(frag);
    } else if (node.nodeType === Node.ELEMENT_NODE && node.tagName !== 'A' && node.tagName !== 'SCRIPT' && node.tagName !== 'STYLE') {
      Array.from(node.childNodes).forEach(child => walk(child));
    }
  }

  walk(wrapper);
  return wrapper.innerHTML;
}

function openAnnouncementPopup(index) {
  const announcement = announcementData[index];
  if (!announcement) return;
  const files = Array.isArray(announcement.files) ? announcement.files : [];
  let imagesHtml = '';
  files.forEach(file => {
    if (file && file.match(/\.(jpe?g|png|gif|webp)$/i)) {
      imagesHtml += `<img src="../${file}" alt="Announcement image">`;
    }
  });
  document.getElementById('announcementPopupContent').innerHTML = `
    <span class="popup-close" onclick="closeAnnouncementPopup()">×</span>
    <h2>${announcement.title ? announcement.title.replace(/</g, '&lt;').replace(/>/g, '&gt;') : 'Announcement'}</h2>
    <div class="popup-body">${linkifyHtml(announcement.description || '<p>No additional details.</p>')}</div>
    ${imagesHtml}
    <div class="popup-body" style="margin-top:14px;color:#666;font-size:0.95rem;">Posted: ${new Date(announcement.created_at).toLocaleString()}</div>
  `;
  document.getElementById('announcementPopup').style.display = 'flex';
}
function closeAnnouncementPopup() {
  document.getElementById('announcementPopup').style.display = 'none';
}
document.getElementById('announcementPopup').addEventListener('click', function(event) {
  if (event.target.id === 'announcementPopup') {
    closeAnnouncementPopup();
  }
});

function focusAnnouncementFromHash() {
  const hash = window.location.hash;
  if (!hash || !hash.startsWith('#announcement-')) {
    return;
  }
  
  // Remove animation from any previously highlighted cards
  document.querySelectorAll('.announcement-card.highlight-active').forEach(el => {
    el.classList.remove('highlight-active');
  });
  
  const card = document.querySelector(hash);
  if (card) {
    // Add the beating animation to the current card
    card.classList.add('highlight-active');
    
    // Smooth scroll to the card
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Keep the animation for longer (4 seconds)
    setTimeout(() => card.classList.remove('highlight-active'), 4000);
  }
}



// Navigate to material from notification
function navigateToMaterial(materialId) {
  const activityPanel = document.getElementById('activityPanel');
  if (activityPanel) {
    activityPanel.classList.remove('active');
  }
  window.location.href = `my_class.php?tab=grades#material-${materialId}`;
}

window.addEventListener('load', focusAnnouncementFromHash);
window.addEventListener('hashchange', focusAnnouncementFromHash);
</script>
<script src="../assets/js/instructor_dashboard.js"></script>

</body>
</html>