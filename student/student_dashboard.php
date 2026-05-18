<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/databases/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

$userRole = $_SESSION['role'] ?? '';
$userId = intval($_SESSION['user_id'] ?? 0);
$dashboardAssignmentEvents = [];
$dashboardNotificationEvents = [];
include __DIR__ . '/../includes/dashboard_notifications.php';
if ($userRole !== 'student' || $userId <= 0) {
    header('Location: ../auth/login.php');
    exit;
}

$studentName = 'Student';
$gradeLevel = '';
$section = '';
$firstName = $middleName = $lastName = '';
$studentInfoSql = "SELECT first_name, middle_name, last_name, grade_level, section FROM students WHERE id = ? LIMIT 1";
if ($stmt = $conn->prepare($studentInfoSql)) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($firstName, $middleName, $lastName, $gradeLevel, $section);
    if ($stmt->fetch()) {
        $nameParts = array_filter([$firstName, $middleName, $lastName]);
        $studentName = implode(' ', $nameParts);
    }
    $stmt->close();
}

$latestAnnouncement = null;
$announcementQuery = "SELECT id, title, description, created_at, files FROM announcements WHERE target IN ('student','all') ORDER BY created_at DESC LIMIT 1";
if ($announcementResult = $conn->query($announcementQuery)) {
    $latestAnnouncement = $announcementResult->fetch_assoc();
    if ($latestAnnouncement) {
        $latestAnnouncement['files'] = json_decode($latestAnnouncement['files'], true) ?: [];
    }
}

$pendingActivities = [];
$assignmentQuery = "SELECT a.id, a.title, a.subject_name, a.due_date, a.due_time, COALESCE(tas.status, 'not submitted') AS status FROM teacher_assignments a LEFT JOIN teacher_assignment_submissions tas ON tas.assignment_id = a.id AND tas.student_id = ? WHERE a.grade_level = ? AND a.section = ? ORDER BY a.due_date ASC, a.due_time ASC";
if ($stmt = $conn->prepare($assignmentQuery)) {
    $stmt->bind_param('iss', $userId, $gradeLevel, $section);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $dueDate = trim($row['due_date'] ?? '');
        if ($dueDate === '') {
            continue;
        }
        $dueTime = trim($row['due_time'] ?: '23:59:59');
        $dueTimestamp = strtotime($dueDate . ' ' . $dueTime);
        if ($dueTimestamp === false) {
            continue;
        }
        if ($row['status'] !== 'submitted') {
            $pendingActivities[] = [
                'assignment_id' => intval($row['id']),
                'title' => $row['title'] ?: 'Untitled',
                'subject_name' => $row['subject_name'] ?: 'General',
                'due_timestamp' => $dueTimestamp,
                'due_date' => $dueDate,
                'due_time' => $dueTime,
            ];
        }
    }
    $stmt->close();
}

usort($pendingActivities, function ($a, $b) {
    return $a['due_timestamp'] <=> $b['due_timestamp'];
});
?>
<!DOCTYPE html>
<html>

<head>

<title>MUCAHUB Student Dashboard</title>

<link rel="stylesheet" href="../assets/css/student_dashboard.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.dashboard-timeline-card {
    display: grid;
    gap: 24px;
    margin-bottom: 28px;
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    padding: 24px;
    background: white;
}
.timeline-announcement,
.timeline-pending {
    border-radius: 20px;
    padding: 22px;
    background: #f8fafc;
}
.timeline-announcement {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) minmax(220px, 1fr);
    gap: 22px;
    align-items: stretch;
}
.timeline-announcement-preview {
    background: #eef6df;
    border-radius: 20px;
    padding: 0;
    color: #2f4921;
    font-size: 0.95rem;
    line-height: 1.6;
    border: 1px solid #dbe7c4;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 120px;
    overflow: hidden;
}

.timeline-announcement-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.timeline-announcement-image-link {
    display: block;
    width: 100%;
    height: 100%;
}
.timeline-announcement-label {
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #334155;
    margin-bottom: 14px;
}
.timeline-announcement-link {
    display: block;
    text-decoration: none;
    color: inherit;
}
.timeline-announcement-title {
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 10px;
}
.timeline-announcement-meta {
    color: #475569;
    margin-bottom: 12px;
}
.timeline-announcement-summary {
    color: #334155;
    line-height: 1.7;
}
.timeline-pending-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
}
.timeline-pending-controls {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
    margin-bottom: 18px;
}
.timeline-pending-controls select {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 14px;
    background: white;
    font-size: 0.95rem;
}
.timeline-pending-item {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 16px;
    padding: 18px 20px;
    border: 1px solid #cbd8a1;
    border-radius: 20px;
    margin-bottom: 12px;
    cursor: pointer;
    background: #eef3db;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.timeline-pending-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
}
.timeline-pending-item-title {
    font-weight: 700;
    font-size: 1rem;
    color: #0f172a;
    margin-bottom: 8px;
}
.timeline-pending-item-meta {
    color: #475569;
    font-size: 0.95rem;
}
.pending-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.pending-overdue {
    display: inline-flex;
    padding: 8px 12px;
    border-radius: 999px;
    background: #fee2e2;
    color: #991b1b;
    font-size: 0.85rem;
    font-weight: 700;
}
.pending-submit-btn {
    padding: 10px 14px;
    border-radius: 999px;
    border: none;
    background: #556b2f;
    color: white;
    cursor: pointer;
    font-weight: 700;
}
.pending-submit-btn:hover {
    background: #6b8e23;
}

/* TODAY HIGHLIGHT IN CALENDAR */
.day.today {
    background: #f0f5e8;
    border-radius: 8px;
}

.day.today .date-number {
    background: #6b8e23;
    color: white;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
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

/* REPORT CARD MODAL STYLES */
.report-card-modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.3s ease-in;
}

.report-card-modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.report-card-modal-content {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 900px;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.report-card-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px;
    border-bottom: 2px solid #e2e8f0;
    background: linear-gradient(135deg, #556b2f 0%, #6b8e23 100%);
    color: white;
}

.report-card-modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
}

.report-card-modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: white;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s ease;
}

.report-card-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.report-card-container {
    padding: 32px;
}

.report-card-header {
    text-align: center;
    margin-bottom: 28px;
    border-bottom: 2px solid #556b2f;
    padding-bottom: 16px;
}

.report-card-header h2 {
    margin: 0 0 8px 0;
    color: #556b2f;
    font-size: 1.8rem;
}

.school-year {
    color: #64748b;
    margin: 0;
    font-size: 0.95rem;
}

.student-info-section {
    background: linear-gradient(135deg, #f0f5e8 0%, #f8fafc 100%);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 28px;
    border: 1px solid #dbe7c4;
}

.info-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 16px;
}

.info-row:last-child {
    margin-bottom: 0;
}

.info-field {
    display: flex;
    flex-direction: column;
}

.info-field label {
    font-weight: 700;
    color: #334155;
    font-size: 0.9rem;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-field span {
    color: #1e293b;
    font-size: 1.05rem;
}

.grades-section {
    margin-bottom: 28px;
}

.grades-section h3 {
    color: #556b2f;
    font-size: 1.3rem;
    margin: 0 0 16px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #dbe7c4;
}

.grades-table {
    width: 100%;
    border-collapse: collapse;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border-radius: 10px;
    overflow: hidden;
}

.grades-table thead {
    background: linear-gradient(135deg, #556b2f 0%, #6b8e23 100%);
    color: white;
}

.grades-table th {
    padding: 14px 12px;
    text-align: center;
    font-weight: 700;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.grades-table th:first-child {
    text-align: left;
}

.grades-table td {
    padding: 14px 12px;
    border-bottom: 1px solid #e2e8f0;
    text-align: center;
    font-weight: 600;
    color: #334155;
}

.grades-table tbody tr:hover {
    background: #f8fafc;
}

.grades-table tbody tr:last-child td {
    border-bottom: none;
}

.subject-name {
    text-align: left;
    color: #1e293b;
    font-weight: 700;
    font-size: 1rem;
}

.grade-cell {
    font-size: 1.05rem;
    color: #556b2f;
}

.grade-cell strong {
    color: #334155;
    font-size: 1.1rem;
}

.grade-low {
    color: #dc2626 !important;
    background: #fee2e2;
    border-radius: 6px;
}

.grade-ok {
    color: #16a34a !important;
}

.period-average-row {
    background: #eef6df !important;
    border-top: 2px solid #556b2f;
}

.period-average-row td {
    font-weight: 700;
    color: #556b2f;
}

.overall-final-row {
    background: linear-gradient(90deg, #f0f5e8, #eef6df) !important;
    border-top: 2px solid #556b2f;
}

.overall-final-row td {
    font-weight: 700;
    color: #334155;
    padding: 16px 12px !important;
}

.overall-grade-cell {
    text-align: left;
    color: #556b2f;
    font-size: 0.95rem;
}

.no-data {
    text-align: center !important;
    color: #64748b !important;
    padding: 32px 12px !important;
    font-style: italic;
}

.adviser-section {
    background: #eef6df;
    padding: 16px 20px;
    border-radius: 10px;
    border-left: 4px solid #556b2f;
    margin-bottom: 28px;
    font-size: 1rem;
}

.adviser-section p {
    margin: 0;
    color: #334155;
}

.adviser-section strong {
    color: #556b2f;
}

.report-card-footer {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.btn-download-pdf,
.btn-close-report {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.btn-download-pdf {
    background: linear-gradient(135deg, #556b2f 0%, #6b8e23 100%);
    color: white;
}

.btn-download-pdf:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(85, 107, 47, 0.3);
}

.btn-close-report {
    background: #e2e8f0;
    color: #334155;
}

.btn-close-report:hover {
    background: #cbd5e1;
}

#reportCardLoading {
    display: none;
    text-align: center;
    padding: 40px;
}

.report-card-spinner {
    border: 4px solid #e2e8f0;
    border-top: 4px solid #556b2f;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 16px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.report-card-error {
    padding: 24px;
    text-align: center;
    color: #dc2626;
    background: #fee2e2;
    border-radius: 10px;
    border: 1px solid #fecaca;
    margin: 20px;
}

@media (max-width: 768px) {
    .report-card-modal-content {
        width: 95%;
        max-height: 90vh;
    }
    
    .report-card-container {
        padding: 16px;
    }
    
    .info-row {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .grades-table th,
    .grades-table td {
        padding: 10px 6px;
        font-size: 0.85rem;
    }
    
    .report-card-footer {
        flex-direction: column;
    }
    
    .btn-download-pdf,
    .btn-close-report {
        width: 100%;
        justify-content: center;
    }
}
</style>

</head>

<body>

<!-- SIDEBAR -->
<?php include "../includes/sidebar_student.php"; ?>


<!-- MAIN CONTENT -->
<div class="main">

<div class="dashboard-timeline-card">
    <div class="timeline-announcement">
        <div>
            <div class="timeline-announcement-label">Recent announcement</div>
            <?php if (!empty($latestAnnouncement)): ?>
                <a class="timeline-announcement-link" href="home.php#announcement-<?php echo intval($latestAnnouncement['id']); ?>">
                    <div class="timeline-announcement-title"><?php echo htmlspecialchars($latestAnnouncement['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="timeline-announcement-meta"><?php echo date('F j, Y g:i A', strtotime($latestAnnouncement['created_at'])); ?></div>
                </a>
            <?php else: ?>
                <div class="timeline-announcement-summary">No announcements yet.</div>
            <?php endif; ?>
        </div>
        <?php $firstImage = !empty($latestAnnouncement['files']) ? $latestAnnouncement['files'][0] : ''; ?>
        <?php if ($firstImage): ?>
        <div class="timeline-announcement-preview">
            <a class="timeline-announcement-image-link" href="home.php#announcement-<?php echo intval($latestAnnouncement['id']); ?>">
                <img src="../<?php echo htmlspecialchars($firstImage, ENT_QUOTES, 'UTF-8'); ?>" alt="Announcement image preview">
            </a>
        </div>
        <?php endif; ?>
    </div>
    <div class="timeline-pending">
        <div class="timeline-pending-header"><span>Pending activities</span><span id="pendingCount"><?php echo count($pendingActivities); ?> pending</span></div>
        <div class="timeline-pending-note">Showing all pending activities sorted by nearest due date.</div>
        <?php if (empty($pendingActivities)): ?>
            <div style="color: #64748b;">No pending activities right now.</div>
        <?php else: ?>
            <div id="pendingList">
                <?php foreach ($pendingActivities as $pending): ?>
                    <div class="timeline-pending-item" data-assignment-id="<?php echo intval($pending['assignment_id']); ?>" data-subject-name="<?php echo htmlspecialchars($pending['subject_name'], ENT_QUOTES, 'UTF-8'); ?>" data-due-timestamp="<?php echo intval($pending['due_timestamp']); ?>">
                        <div>
                            <div class="timeline-pending-item-title"><?php echo htmlspecialchars($pending['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="timeline-pending-item-meta"><?php echo htmlspecialchars($pending['subject_name'], ENT_QUOTES, 'UTF-8'); ?> &middot; <?php echo date('F j, Y g:i A', $pending['due_timestamp']); ?></div>
                        </div>
                        <div class="pending-actions">
                            <?php if ($pending['due_timestamp'] < time()): ?><span class="pending-overdue">Overdue</span><?php endif; ?>
                            <button type="button" class="pending-submit-btn">Open activity</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<h2>Hi, <?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></h2>


<!-- CALENDAR (ONLY HERE) -->
<div class="calendar-container">

<div class="calendar-header">

<button onclick="prevMonth()">◀</button>

<h3 id="monthYear"></h3>

<button onclick="nextMonth()">▶</button>

<button onclick="openTaskModal()" class="add-task-btn" title="Add Personal Task">
  <i class="fa fa-plus"></i> Add Task
</button>

</div>

<div id="calendar"></div>

</div>

<!-- TASK MODAL -->
<div id="taskModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Add Personal Task</h2>
      <button class="modal-close" onclick="closeTaskModal()">✖</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label for="modalTaskText">Task Description:</label>
        <input type="text" id="modalTaskText" placeholder="Enter your task" autofocus>
      </div>
      <div class="form-group">
        <label for="modalTaskDate">Due Date:</label>
        <input type="date" id="modalTaskDate">
      </div>
    </div>
    <div class="modal-footer">
      <button id="modalSaveBtn" onclick="saveTaskFromModal()" class="btn-save">Save Task</button>
      <button id="modalDeleteBtn" onclick="deleteCurrentTask()" class="btn-delete" style="display:none;">Delete</button>
      <button onclick="closeTaskModal()" class="btn-cancel">Cancel</button>
    </div>
  </div>
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
                            <div class="notification-item notification-announcement" onclick="<?php echo !empty($event['link']) ? "window.location.href='" . htmlspecialchars($event['link']) . "'" : ""; ?>" style="cursor: pointer;">
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

<!-- PROFILE BUTTON -->
<?php include "../includes/profile_student.php"; ?>

<?php include "../includes/back_to_top.php"; ?>
<!-- FOOTER -->
<?php include "../includes/footer.php"; ?>


<script>
(function () {
    const pendingList = document.getElementById('pendingList');
    const pendingActivities = <?php echo json_encode($pendingActivities, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const calendarElement = document.getElementById('calendar');
    let currentDate = new Date();

    function getPendingLink(subject, assignmentId) {
        return `my_courses.php?subject=${encodeURIComponent(subject)}#assignment-${assignmentId}`;
    }

    function formatAssignmentLabel(item) {
        const title = String(item.title || 'Untitled');
        return title.length > 24 ? `${title.slice(0, 22)}…` : title;
    }

    function attachPendingHandlers() {
        if (!pendingList) {
            return;
        }
        pendingList.querySelectorAll('.timeline-pending-item').forEach(item => {
            const subjectName = item.dataset.subjectName || '';
            const assignmentId = item.dataset.assignmentId;
            item.addEventListener('click', () => {
                window.location.href = getPendingLink(subjectName, assignmentId);
            });
            const button = item.querySelector('.pending-submit-btn');
            if (button) {
                button.addEventListener('click', function (event) {
                    event.stopPropagation();
                    window.location.href = getPendingLink(subjectName, assignmentId);
                });
            }
        });
    }

    function renderCalendar() {
        if (!calendarElement) {
            return;
        }
        calendarElement.innerHTML = '';
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        document.getElementById('monthYear').textContent = currentDate.toLocaleString('default', { month: 'long', year: 'numeric' });

        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        dayNames.forEach(name => {
            const dayHeader = document.createElement('div');
            dayHeader.className = 'calendar-day-name';
            dayHeader.textContent = name;
            calendarElement.appendChild(dayHeader);
        });

        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('div');
            calendarElement.appendChild(empty);
        }

        const assignmentsByDate = pendingActivities.reduce((map, activity) => {
            const dueDate = new Date(activity.due_date + 'T' + activity.due_time);
            if (Number.isNaN(dueDate.getTime())) {
                return map;
            }
            const key = dueDate.toISOString().slice(0, 10);
            if (!map[key]) {
                map[key] = [];
            }
            map[key].push(activity);
            return map;
        }, {});

        // Load personal tasks from localStorage using user-specific key
        const userId = window.mucahubUserId || 'default';
        const storageKey = `personalTasks_${userId}`;
        const personalTasks = JSON.parse(localStorage.getItem(storageKey)) || [];
        const tasksByDate = personalTasks.reduce((map, task) => {
            const key = task.date;
            if (!map[key]) {
                map[key] = [];
            }
            map[key].push(task);
            return map;
        }, {});

        const today = new Date();
        const todayString = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

        for (let day = 1; day <= daysInMonth; day++) {
            const div = document.createElement('div');
            div.className = 'day';
            const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            
            // Add today class if this is today
            if (dateString === todayString) {
                div.classList.add('today');
            }
            
            const dateLabel = document.createElement('div');
            dateLabel.className = 'date-number';
            dateLabel.textContent = day;
            div.appendChild(dateLabel);

            // Display assignments
            const events = assignmentsByDate[dateString] || [];
            events.forEach(activity => {
                const eventButton = document.createElement('button');
                eventButton.className = 'calendar-event assignment';
                eventButton.type = 'button';
                eventButton.textContent = `${activity.subject_name}: ${formatAssignmentLabel(activity)}`;
                eventButton.title = `${activity.title} • Due ${activity.due_date} ${activity.due_time}`;
                eventButton.addEventListener('click', () => {
                    window.location.href = getPendingLink(activity.subject_name, activity.assignment_id);
                });
                div.appendChild(eventButton);
            });

            // Display personal tasks
            const tasks = tasksByDate[dateString] || [];
            tasks.forEach(task => {
                const taskCard = document.createElement('div');
                taskCard.className = 'calendar-task-card';
                taskCard.title = task.text;
                taskCard.addEventListener('click', () => {
                    openTaskModal(task.id);
                });

                const taskTextLabel = document.createElement('div');
                taskTextLabel.className = 'task-card-text';
                taskTextLabel.textContent = task.text;

                taskCard.appendChild(taskTextLabel);
                div.appendChild(taskCard);
            });

            calendarElement.appendChild(div);
        }
    }

    function prevMonth() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    }

    function nextMonth() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    }

    window.prevMonth = prevMonth;
    window.nextMonth = nextMonth;
    window.renderCalendarGlobal = renderCalendar;
    attachPendingHandlers();
    renderCalendar();
})();
</script>

<script>
/* TASK MODAL FUNCTIONS */

let currentEditingTaskId = null;

function openTaskModal(taskId = null) {
    const modal = document.getElementById('taskModal');
    const taskText = document.getElementById('modalTaskText');
    const taskDate = document.getElementById('modalTaskDate');
    const modalTitle = document.querySelector('#taskModal .modal-header h2');
    const saveButton = document.getElementById('modalSaveBtn');
    const deleteButton = document.getElementById('modalDeleteBtn');

    const today = new Date();
    taskDate.valueAsDate = today;
    taskText.value = '';
    currentEditingTaskId = null;
    modalTitle.textContent = 'Add Personal Task';
    saveButton.textContent = 'Save Task';
    deleteButton.style.display = 'none';

    if (taskId !== null) {
        const userId = window.mucahubUserId || 'default';
        const storageKey = `personalTasks_${userId}`;
        const tasks = JSON.parse(localStorage.getItem(storageKey)) || [];
        const task = tasks.find(item => item.id === taskId);
        if (task) {
            taskText.value = task.text;
            taskDate.value = task.date;
            currentEditingTaskId = taskId;
            modalTitle.textContent = 'Edit Personal Task';
            saveButton.textContent = 'Save Changes';
            deleteButton.style.display = 'inline-flex';
        }
    }

    modal.classList.add('active');
    taskText.focus();
}

function closeTaskModal() {
    const modal = document.getElementById('taskModal');
    modal.classList.remove('active');
    document.getElementById('modalTaskText').value = '';
    document.getElementById('modalTaskDate').value = '';
    currentEditingTaskId = null;
}

function saveTaskFromModal() {
    const taskText = document.getElementById('modalTaskText').value.trim();
    const taskDate = document.getElementById('modalTaskDate').value;

    if (!taskText) {
        alert('Please enter a task description');
        return;
    }

    if (!taskDate) {
        alert('Please select a date');
        return;
    }

    // Get user-specific storage key
    const userId = window.mucahubUserId || 'default';
    const storageKey = `personalTasks_${userId}`;
    let tasks = JSON.parse(localStorage.getItem(storageKey)) || [];

    if (currentEditingTaskId !== null) {
        tasks = tasks.map(task => {
            if (task.id === currentEditingTaskId) {
                return {
                    ...task,
                    text: taskText,
                    date: taskDate
                };
            }
            return task;
        });
    } else {
        tasks.push({
            text: taskText,
            date: taskDate,
            id: Date.now()
        });
    }

    localStorage.setItem(storageKey, JSON.stringify(tasks));
    closeTaskModal();

    if (typeof renderCalendarGlobal === 'function') {
        renderCalendarGlobal();
    }
}

function deleteCurrentTask() {
    if (currentEditingTaskId === null) {
        return;
    }

    // Get user-specific storage key
    const userId = window.mucahubUserId || 'default';
    const storageKey = `personalTasks_${userId}`;
    let tasks = JSON.parse(localStorage.getItem(storageKey)) || [];
    tasks = tasks.filter(task => task.id !== currentEditingTaskId);
    localStorage.setItem(storageKey, JSON.stringify(tasks));
    closeTaskModal();

    if (typeof renderCalendarGlobal === 'function') {
        renderCalendarGlobal();
    }
}

function deleteTask(taskId) {
    // Get user-specific storage key
    const userId = window.mucahubUserId || 'default';
    const storageKey = `personalTasks_${userId}`;
    let tasks = JSON.parse(localStorage.getItem(storageKey)) || [];
    tasks = tasks.filter(task => task.id !== taskId);
    localStorage.setItem(storageKey, JSON.stringify(tasks));
    if (typeof renderCalendarGlobal === 'function') {
        renderCalendarGlobal();
    }
}

// Close modal when clicking outside of it
window.addEventListener('click', function(event) {
    const modal = document.getElementById('taskModal');
    if (event.target === modal) {
        closeTaskModal();
    }
});

// Allow Enter key to save task
document.addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        const modal = document.getElementById('taskModal');
        if (modal && modal.classList.contains('active')) {
            const activeElement = document.activeElement;
            if (activeElement.id === 'modalTaskText' || activeElement.id === 'modalTaskDate') {
                saveTaskFromModal();
            }
        }
    }
});

// ================ REPORT CARD FUNCTIONS ================
function openReportCardModal(event) {
    if (event) {
        event.preventDefault();
    }
    const reportCardModal = document.getElementById('reportCardModal');
    if (reportCardModal) {
        reportCardModal.classList.add('active');
        loadReportCardData();
    }
}

function closeReportCardModal() {
    const reportCardModal = document.getElementById('reportCardModal');
    if (reportCardModal) {
        reportCardModal.classList.remove('active');
    }
}

async function loadReportCardData() {
    const loadingDiv = document.getElementById('reportCardLoading');
    const contentDiv = document.getElementById('reportCardContent');
    
    if (loadingDiv) loadingDiv.style.display = 'block';
    if (contentDiv) contentDiv.innerHTML = '';
    
    try {
        const response = await fetch('../api/get_report_card.php');
        const data = await response.json();
        
        if (loadingDiv) loadingDiv.style.display = 'none';
        
        if (data.success) {
            renderReportCard(data.data);
        } else {
            if (contentDiv) contentDiv.innerHTML = '<div class="report-card-error"><p>' + (data.message || 'Failed to load report card') + '</p></div>';
        }
    } catch (error) {
        if (loadingDiv) loadingDiv.style.display = 'none';
        console.error('Error loading report card:', error);
        if (contentDiv) contentDiv.innerHTML = '<div class="report-card-error"><p>Error loading report card. Please try again.</p></div>';
    }
}

function renderReportCard(data) {
    const contentDiv = document.getElementById('reportCardContent');
    if (!contentDiv) return;
    
    const reportCardHTML = generateReportCardHTML(data);
    contentDiv.innerHTML = reportCardHTML;
    
    // Attach event listeners
    const downloadBtn = contentDiv.querySelector('#downloadReportCardPdfBtn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', () => downloadReportCardPDF(data));
    }
}

function generateReportCardHTML(data) {
    const student = data.student;
    const adviser = data.adviser || 'N/A';
    const subjectsData = data.subjects || [];
    const periodFinalGrades = data.periodFinalGrades || {};
    const overallFinalGrade = data.overallFinalGrade;
    
    let html = `
    <div class="report-card-container">
      <div class="report-card-header">
        <div class="school-info">
          <h2>School Report Card</h2>
          <p class="school-year">School Year: ${data.schoolYear || 'Current'}</p>
        </div>
      </div>
      
      <div class="student-info-section">
        <div class="info-row">
          <div class="info-field">
            <label>Student Name:</label>
            <span>${student.name}</span>
          </div>
          <div class="info-field">
            <label>Grade Level:</label>
            <span>${student.gradeLevel}</span>
          </div>
        </div>
        <div class="info-row">
          <div class="info-field">
            <label>Section:</label>
            <span>${student.section}</span>
          </div>
          <div class="info-field">
            <label>Student ID:</label>
            <span>${student.studentId || 'N/A'}</span>
          </div>
        </div>
      </div>
      
      <div class="grades-section">
        <h3>Academic Performance</h3>
        <table class="grades-table">
          <thead>
            <tr>
              <th>Subject</th>
              <th>1st Period</th>
              <th>2nd Period</th>
              <th>3rd Period</th>
              <th>4th Period</th>
              <th>Subject Final</th>
            </tr>
          </thead>
          <tbody>`;
    
    if (subjectsData.length === 0) {
        html += `<tr><td colspan="6" class="no-data">No grades available yet</td></tr>`;
    } else {
        // Add subject rows
        subjectsData.forEach(subject => {
            const finalGrade = subject.finalGrade !== null ? subject.finalGrade : '-';
            const finalGradeClass = finalGrade !== '-' && finalGrade < 75 ? 'grade-low' : 'grade-ok';
            
            html += `
        <tr>
          <td class="subject-name">${subject.name}</td>
          <td class="grade-cell">${subject.grade1st || '-'}</td>
          <td class="grade-cell">${subject.grade2nd || '-'}</td>
          <td class="grade-cell">${subject.grade3rd || '-'}</td>
          <td class="grade-cell">${subject.grade4th || '-'}</td>
          <td class="grade-cell ${finalGradeClass}"><strong>${finalGrade}</strong></td>
        </tr>`;
        });
        
        // Add period averages row
        const period1Final = periodFinalGrades['1st'] !== null ? periodFinalGrades['1st'] : '-';
        const period2Final = periodFinalGrades['2nd'] !== null ? periodFinalGrades['2nd'] : '-';
        const period3Final = periodFinalGrades['3rd'] !== null ? periodFinalGrades['3rd'] : '-';
        const period4Final = periodFinalGrades['4th'] !== null ? periodFinalGrades['4th'] : '-';
        
        html += `
        <tr class="period-average-row">
          <td class="subject-name"><strong>Period Average</strong></td>
          <td class="grade-cell"><strong>${period1Final}</strong></td>
          <td class="grade-cell"><strong>${period2Final}</strong></td>
          <td class="grade-cell"><strong>${period3Final}</strong></td>
          <td class="grade-cell"><strong>${period4Final}</strong></td>
          <td class="grade-cell"><strong>-</strong></td>
        </tr>`;
        
        // Add overall final grade row
        const overallClass = overallFinalGrade !== null && overallFinalGrade < 75 ? 'grade-low' : 'grade-ok';
        const overallDisplay = overallFinalGrade !== null ? overallFinalGrade : '-';
        
        html += `
        <tr class="overall-final-row">
          <td class="subject-name"><strong>Overall Final Grade</strong></td>
          <td colspan="4" class="overall-grade-cell"><strong>Average of All Subjects</strong></td>
          <td class="grade-cell ${overallClass}"><strong style="font-size: 1.2em;">${overallDisplay}</strong></td>
        </tr>`;
    }
    
    html += `
          </tbody>
        </table>
      </div>
      
      <div class="adviser-section">
        <p><strong>Class Adviser:</strong> ${adviser}</p>
      </div>
      
      <div class="report-card-footer">
        <button id="downloadReportCardPdfBtn" class="btn-download-pdf">
          <i class="fa fa-download"></i> Download as PDF
        </button>
        <button onclick="closeReportCardModal()" class="btn-close-report">
          <i class="fa fa-times"></i> Close
        </button>
      </div>
    </div>
  `;
    
    return html;
}

function downloadReportCardPDF(data) {
    try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        const student = data.student;
        const adviser = data.adviser || 'N/A';
        const subjectsData = data.subjects || [];
        const periodFinalGrades = data.periodFinalGrades || {};
        const overallFinalGrade = data.overallFinalGrade;
        
        // Colors matching site theme
        const primaryColor = [85, 107, 47];     // #556b2f
        const secondaryColor = [107, 142, 35];  // #6b8e23
        const lightColor = [239, 242, 245];     // Light gray
        const highlightColor = [239, 246, 223]; // Light green
        
        // Set fonts and colors
        doc.setFontSize(18);
        doc.setTextColor(...primaryColor);
        doc.text('SCHOOL REPORT CARD', 105, 15, { align: 'center' });
        
        doc.setFontSize(10);
        doc.setTextColor(100, 100, 100);
        doc.text(`School Year: ${data.schoolYear || 'Current'}`, 105, 22, { align: 'center' });
        
        // Horizontal line
        doc.setDrawColor(...primaryColor);
        doc.line(14, 25, 196, 25);
        
        // Student Information Section
        doc.setFontSize(11);
        doc.setTextColor(...primaryColor);
        doc.text('STUDENT INFORMATION', 14, 32);
        
        doc.setFontSize(9);
        doc.setTextColor(0, 0, 0);
        
        const infoX1 = 14;
        const infoX2 = 110;
        let infoY = 38;
        
        doc.text(`Student Name: ${student.name}`, infoX1, infoY);
        doc.text(`Grade Level: ${student.gradeLevel}`, infoX2, infoY);
        infoY += 6;
        
        doc.text(`Student ID: ${student.studentId || 'N/A'}`, infoX1, infoY);
        doc.text(`Section: ${student.section}`, infoX2, infoY);
        
        // Grades Table
        infoY += 12;
        doc.setFontSize(11);
        doc.setTextColor(...primaryColor);
        doc.text('ACADEMIC PERFORMANCE', 14, infoY);
        infoY += 6;
        
        const tableData = [];
        tableData.push(['Subject', '1st Period', '2nd Period', '3rd Period', '4th Period', 'Subject Final']);
        
        if (subjectsData.length > 0) {
            // Add subject rows
            subjectsData.forEach(subject => {
                const finalGrade = subject.finalGrade !== null ? subject.finalGrade.toString() : '-';
                tableData.push([
                    subject.name,
                    subject.grade1st !== null ? subject.grade1st.toString() : '-',
                    subject.grade2nd !== null ? subject.grade2nd.toString() : '-',
                    subject.grade3rd !== null ? subject.grade3rd.toString() : '-',
                    subject.grade4th !== null ? subject.grade4th.toString() : '-',
                    finalGrade
                ]);
            });
            
            // Add period averages row
            const period1Final = periodFinalGrades['1st'] !== null ? periodFinalGrades['1st'].toString() : '-';
            const period2Final = periodFinalGrades['2nd'] !== null ? periodFinalGrades['2nd'].toString() : '-';
            const period3Final = periodFinalGrades['3rd'] !== null ? periodFinalGrades['3rd'].toString() : '-';
            const period4Final = periodFinalGrades['4th'] !== null ? periodFinalGrades['4th'].toString() : '-';
            
            tableData.push([
                'Period Average',
                period1Final,
                period2Final,
                period3Final,
                period4Final,
                '-'
            ]);
            
            // Add overall final grade row
            const overallDisplay = overallFinalGrade !== null ? overallFinalGrade.toString() : '-';
            tableData.push([
                'Overall Final Grade',
                '',
                '',
                'Average of All Subjects',
                '',
                overallDisplay
            ]);
        } else {
            tableData.push(['No grades available yet', '', '', '', '', '']);
        }
        
        // AutoTable with styling
        doc.autoTable({
            head: [tableData[0]],
            body: tableData.slice(1),
            startY: infoY,
            margin: { left: 14, right: 14 },
            theme: 'grid',
            styles: { 
                fontSize: 8.5, 
                cellPadding: 3,
                halign: 'center',
                valign: 'middle'
            },
            headStyles: { 
                fillColor: primaryColor,
                textColor: [255, 255, 255], 
                fontStyle: 'bold',
                halign: 'center'
            },
            bodyStyles: {
                textColor: [0, 0, 0]
            },
            alternateRowStyles: { 
                fillColor: lightColor 
            },
            didDrawCell: (data) => {
                // Highlight period average row
                if (data.row.index === tableData.length - 2) {
                    doc.setFillColor(...highlightColor);
                    doc.rect(data.cell.x, data.cell.y, data.cell.width, data.cell.height, 'F');
                }
                // Highlight overall final row
                if (data.row.index === tableData.length - 1) {
                    doc.setFillColor(239, 246, 223);
                    doc.rect(data.cell.x, data.cell.y, data.cell.width, data.cell.height, 'F');
                }
            },
            columnStyles: {
                0: { halign: 'left' }
            }
        });
        
        // Adviser Section
        const finalY = doc.lastAutoTable.finalY + 8;
        doc.setDrawColor(...primaryColor);
        doc.line(14, finalY - 3, 196, finalY - 3);
        
        doc.setFontSize(10);
        doc.setTextColor(...primaryColor);
        doc.text('Class Adviser:', 14, finalY + 4);
        doc.setTextColor(0, 0, 0);
        doc.text(adviser, 40, finalY + 4);
        
        // Footer
        const pageHeight = doc.internal.pageSize.height;
        doc.setFontSize(8);
        doc.setTextColor(150, 150, 150);
        doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, pageHeight - 8);
        doc.text(`Page 1 of 1`, 196, pageHeight - 8, { align: 'right' });
        
        // Download PDF
        const fileName = `ReportCard_${student.name.replace(/\s+/g, '_')}_${new Date().getFullYear()}.pdf`;
        doc.save(fileName);
    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Error generating PDF: ' + error.message);
    }
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<!-- Report Card Modal -->
<div id="reportCardModal" class="report-card-modal">
    <div class="report-card-modal-content">
        <div class="report-card-modal-header">
            <h2>Student Report Card</h2>
            <button class="report-card-modal-close" onclick="closeReportCardModal()">✖</button>
        </div>
        <div id="reportCardLoading">
            <div class="report-card-spinner"></div>
            <p>Loading Report Card...</p>
        </div>
        <div id="reportCardContent"></div>
    </div>
</div>
<script>
// Make user ID available globally for calendar and notification tasks
window.mucahubUserId = <?php echo $userId; ?>;
window.mucahubUserRole = 'student';
</script>
<script src="../assets/js/notifications.js"></script>

</body>
</html>