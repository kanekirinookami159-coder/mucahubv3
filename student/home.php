<?php
include "../config/config.php";
include "../includes/databases/db_connection.php";
include "../includes/functions.php";
checkLogin();
checkRole('student');

$userRole = $_SESSION['role'] ?? '';
$userId = intval($_SESSION['user_id'] ?? 0);
$dashboardAssignmentEvents = [];
$dashboardNotificationEvents = [];
include __DIR__ . '/../includes/dashboard_notifications.php';

$announcements = [];
$announcementCount = 0;
$announcementQuery = "SELECT * FROM announcements WHERE target IN ('student','all') ORDER BY created_at DESC LIMIT 10";
if ($result = $conn->query($announcementQuery)) {
    while ($row = $result->fetch_assoc()) {
        $row['files'] = json_decode($row['files'], true) ?: [];
        $announcements[] = $row;
    }
    $announcementCount = count($announcements);
}
?>

<!DOCTYPE html>
<html>

<head>

<title>MUCAHUB Home</title>

<link rel="stylesheet" href="../assets/css/student_dashboard.css">

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

.announcement-notice {
  margin-bottom: 20px;
  color: #2f5d32;
  font-weight: 600;
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
  box-shadow: 0 3px 12px rgba(0,0,0,0.08);
  padding: 18px;
  cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.announcement-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.12);
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

.announcement-card img {
  width: 100%;
  max-height: 180px;
  object-fit: cover;
  border-radius: 10px;
  margin-bottom: 12px;
}

.announcement-card-title {
  font-size: 1.2em;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 10px;
}

.announcement-card-meta,
.announcement-card-target {
  font-size: 0.95em;
  color: #666;
  margin-top: 6px;
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

@media (max-width: 900px) {
  .announcement-grid {
    grid-template-columns: 1fr;
  }
}

/* NOTIFICATION PANEL STYLES */
.floating-buttons {
    position: fixed;
    right: 10px;
    top: 200px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.floating-buttons button {
    background: #556b2f;
    border: none;
    color: white;
    padding: 12px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 16px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    position: relative;
}

.floating-buttons button:hover {
    background: #6b8e23;
    transform: scale(1.1);
}

.notification-dot {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #e74c3c;
    box-shadow: 0 0 0 2px rgba(255,255,255,0.9);
    display: none;
}

.sidepanel {
    position: fixed;
    right: -350px;
    top: 0;
    width: 320px;
    height: 100vh;
    background: white;
    box-shadow: -3px 0 10px rgba(0,0,0,0.2);
    padding: 20px;
    transition: 0.4s;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    z-index: 1000;
}

.sidepanel.active {
    right: 0;
}

.closeBtn {
    position: absolute;
    top: 20px;
    right: 20px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6b7280;
    padding: 0;
}

.closeBtn:hover {
    color: #1f2937;
}

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
</style>

</head>

<body>

<!-- SIDEBAR -->
<?php include "../includes/sidebar_student.php"; ?>


<!-- MAIN -->
<div class="main">

<div class="welcome-section">
  <h1>Welcome to MUCAHUB</h1>
  <p>Welcome back! Explore your courses, check announcements, and manage your learning journey.</p>
</div>

<div class="announcements-section">
  <h2>📢 Announcements</h2>
  <?php if ($announcementCount > 0): ?>
    <div class="announcement-notice"><?php echo $announcementCount; ?> announcement<?php echo $announcementCount === 1 ? '' : 's'; ?> available for you.</div>
  <?php endif; ?>
  <?php if (empty($announcements)): ?>
    <p class="no-announcements">No announcements available.</p>
  <?php else: ?>
    <div class="announcement-grid">
      <?php foreach ($announcements as $index => $announcement): ?>
        <?php $firstImage = !empty($announcement['files']) ? $announcement['files'][0] : ''; ?>
        <div id="announcement-<?php echo intval($announcement['id']); ?>" class="announcement-card" onclick="openAnnouncementPopup(<?php echo $index; ?>)">
          <?php if ($firstImage): ?>
            <img src="../<?php echo htmlspecialchars($firstImage); ?>" alt="Announcement image">
          <?php endif; ?>
          <div class="announcement-card-title"><?php echo htmlspecialchars($announcement['title'] ?: 'Announcement'); ?></div>
          <div class="announcement-card-meta"><?php echo date('F j, Y g:i A', strtotime($announcement['created_at'])); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

</div>

<!-- RIGHT FLOAT BUTTONS -->
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
                <?php if (empty($dashboardNotificationEvents)): ?>
                    <div class="notification-item-empty">No announcements</div>
                <?php else: ?>
                    <?php $annCount = 0; ?>
                    <?php foreach ($dashboardNotificationEvents as $event): ?>
                        <?php if (isset($event['type']) && $event['type'] === 'Announcement'): ?>
                            <?php $annCount++; if ($annCount > 5) break; ?>
                            <div class="notification-item notification-announcement" onclick="navigateToAnnouncement('<?php echo htmlspecialchars(addslashes($event['title']), ENT_QUOTES, 'UTF-8'); ?>')" style="cursor: pointer;">
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
                        <?php if (isset($event['type']) && in_array($event['type'], ['Submission', 'Assignment'])): ?>
                            <?php $activityCount++; if ($activityCount > 5) break; ?>
                            <div class="notification-item notification-assignment" onclick="<?php echo !empty($event['link']) ? "window.location.href='" . htmlspecialchars($event['link']) . "'" : ""; ?>" style="cursor: pointer; border-left-color: #3b82f6;">
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

</div>

<!-- PROFILE -->
<?php include "../includes/profile_student.php"; ?>

<!-- FOOTER -->
<?php include "../includes/footer.php"; ?>

<div id="announcementPopup" class="announcement-popup">
  <div class="announcement-popup-content" id="announcementPopupContent"></div>
</div>


<?php include "../includes/back_to_top.php"; ?>

<script>
const announcementData = <?php echo json_encode($announcements, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

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

function navigateToAnnouncement(announcementTitle) {
  // Close notification panel
  const activityPanel = document.getElementById('activityPanel');
  if (activityPanel) {
    activityPanel.classList.remove('active');
  }
  
  // Find announcement by title and open popup
  const announcement = announcementData.find(a => a.title === announcementTitle);
  if (announcement) {
    const index = announcementData.indexOf(announcement);
    openAnnouncementPopup(index);
  }
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
  const card = document.querySelector(hash);
  if (!card) {
    return;
  }
  card.scrollIntoView({ behavior: 'smooth', block: 'center' });
  card.classList.add('highlight-active');
  setTimeout(() => card.classList.remove('highlight-active'), 3000);
}

window.addEventListener('load', focusAnnouncementFromHash);

// NOTIFICATION PANEL FUNCTIONALITY
document.addEventListener('DOMContentLoaded', function() {
    const activityBtn = document.getElementById('activityBtn');
    const activityPanel = document.getElementById('activityPanel');
    const closeBtn = document.querySelector('.closeBtn');

    // Open/Close panel on bell button click
    if (activityBtn && activityPanel) {
        activityBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            activityPanel.classList.toggle('active');
        });
    }

    // Close panel on close button click
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            if (activityPanel) {
                activityPanel.classList.remove('active');
            }
        });
    }

    // Close panel on outside click
    document.addEventListener('click', function(e) {
        if (activityPanel && !activityPanel.contains(e.target) && !activityBtn.contains(e.target)) {
            activityPanel.classList.remove('active');
        }
    });

    // Render notifications when page loads
    if (typeof renderNotificationsList === 'function') {
        renderNotificationsList();
        updateNotificationDot();
    }
});

function closeActivity() {
    const activityPanel = document.getElementById('activityPanel');
    if (activityPanel) {
        activityPanel.classList.remove('active');
    }
}
</script>

<script src="../assets/js/dashboard.js"></script>
<script src="../assets/js/notifications.js"></script>

</body>
</html>
