<?php
include "../config/config.php";
include "../includes/databases/db_connection.php";
include "../includes/functions.php";
checkLogin();
checkRole('instructor');

$instructorId = intval($_SESSION['user_id'] ?? 0);
$dashboardAssignmentEvents = [];
$dashboardNotificationEvents = [];
include "../includes/dashboard_notifications.php";

$latestAnnouncement = null;
$announcementQuery = "SELECT id, title, created_at, files FROM announcements WHERE target IN ('teacher','all') ORDER BY created_at DESC LIMIT 1";
if ($announcementResult = $conn->query($announcementQuery)) {
    $latestAnnouncement = $announcementResult->fetch_assoc();
    if ($latestAnnouncement) {
        $latestAnnouncement['files'] = json_decode($latestAnnouncement['files'], true) ?: [];
    }
}

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
    <title>MUCAHUB Instructor Dashboard</title>

    <link rel="stylesheet" href="../assets/css/student_dashboard.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* ADD TASK BUTTON */
        .add-task-btn {
            margin-left: auto;
            background: #556b2f;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.3s;
        }

        .add-task-btn:hover {
            background: #6b8e23;
        }

        /* TASK MODAL */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .modal-content {
            background-color: white;
            padding: 0;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 500px;
            animation: slideDown 0.3s;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #333;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            color: #000;
        }

        .modal-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: #000;
            font-size: 14px;
        }

        .form-group input {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }

        .form-group input[type="date"]::-webkit-calendar-picker-indicator {
            filter: brightness(0);
            cursor: pointer;
        }

        .form-group input:focus {
            outline: none;
            border-color: #556b2f;
            box-shadow: 0 0 0 3px rgba(85, 107, 47, 0.1);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .btn-save, .btn-cancel, .btn-delete {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-save {
            background: #556b2f;
            color: white;
        }

        .btn-save:hover {
            background: #6b8e23;
        }

        .btn-delete {
            background: #dc2626;
            color: white;
        }

        .btn-delete:hover {
            background: #b91c1c;
        }

        .btn-cancel {
            background: #e0e0e0;
            color: #333;
        }

        .btn-cancel:hover {
            background: #d1d5db;
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

        /* BEAT ANIMATION FOR HIGHLIGHTED ITEMS */
        .notification-item.highlight-active {
            animation: beat-pulse 1.2s ease 2;
            background: #fff9e6 !important;
            border-left-color: #1d4ed8;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        }

        .announcement-banner {
            margin: 22px 0;
            padding: 22px 24px;
            border-radius: 22px;
            background: linear-gradient(135deg, #f1f8e9 0%, #d9ead3 100%);
            border: 1px solid #c8e6c9;
            color: #1f3d1f;
            box-shadow: 0 18px 40px rgba(17, 24, 39, 0.08);
        }

        .announcement-banner-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            flex-wrap: wrap;
        }

        .announcement-banner-label {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #2e5f2e;
        }

        .announcement-banner-title {
            font-size: 1.14rem;
            font-weight: 700;
            margin: 6px 0 4px;
            color: #163a16;
        }

        .announcement-banner-meta {
            color: #4f6a4f;
            font-size: 0.95rem;
        }

        .announcement-banner-action {
            padding: 12px 18px;
            background: #556b2f;
            color: #fff;
            border-radius: 14px;
            text-decoration: none;
            border: 1px solid transparent;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .announcement-banner-action:hover {
            background: #3d5919;
            transform: translateY(-1px);
        }

        @keyframes beat-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
    </style>

</head>

<body>

    <!-- SIDEBAR -->
    <?php include "../includes/sidebar_instructor.php"; ?>

    <!-- MAIN CONTENT -->
    <div class="main">
        <h2>Hi, INSTRUCTOR 👋</h2>

        <?php if (!empty($latestAnnouncement)): ?>
        <div class="announcement-banner">
            <div class="announcement-banner-content">
                <div>
                    <div class="announcement-banner-label">Latest announcement</div>
                    <div class="announcement-banner-title"><?php echo htmlspecialchars($latestAnnouncement['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="announcement-banner-meta"><?php echo date('F j, Y g:i A', strtotime($latestAnnouncement['created_at'])); ?></div>
                </div>
                <a class="announcement-banner-action" href="../instructor/home.php#announcement-<?php echo intval($latestAnnouncement['id']); ?>">View details</a>
            </div>
        </div>
        <?php endif; ?>

        <?php $firstImage = !empty($latestAnnouncement['files']) ? $latestAnnouncement['files'][0] : ''; ?>
        <?php if ($firstImage): ?>
        <div class="dashboard-timeline-card">
            <div class="timeline-announcement">
                <div>
                    <div class="timeline-announcement-label">Recent announcement</div>
                    <a class="timeline-announcement-link" href="../instructor/home.php#announcement-<?php echo intval($latestAnnouncement['id']); ?>">
                        <div class="timeline-announcement-title"><?php echo htmlspecialchars($latestAnnouncement['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="timeline-announcement-meta"><?php echo date('F j, Y g:i A', strtotime($latestAnnouncement['created_at'])); ?></div>
                    </a>
                </div>
                <div class="timeline-announcement-preview">
                    <a class="timeline-announcement-image-link" href="../instructor/home.php#announcement-<?php echo intval($latestAnnouncement['id']); ?>">
                        <img src="../<?php echo htmlspecialchars($firstImage, ENT_QUOTES, 'UTF-8'); ?>" alt="Announcement image preview">
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

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
    
    <script>window.mucahubInstructorId = <?php echo intval($_SESSION['user_id']); ?>;</script>
    <script>
        window.dashboardAssignmentEvents = <?php echo json_encode($dashboardAssignmentEvents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.dashboardNotificationEvents = <?php echo json_encode($dashboardNotificationEvents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>

    <script>
(function () {
    function initializeCalendar() {
        const calendarElement = document.getElementById('calendar');
        if (!calendarElement) {
            return;
        }
        
        let currentDate = new Date();

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

            // Load personal tasks from localStorage
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

            // Get assignment events and group by date
            const assignmentEvents = Array.isArray(window.dashboardAssignmentEvents) ? window.dashboardAssignmentEvents : [];
            const eventsByDate = assignmentEvents.reduce((map, event) => {
                const key = event.date || '';
                if (!map[key]) {
                    map[key] = [];
                }
                map[key].push(event);
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

                // Display assignment events
                const events = eventsByDate[dateString] || [];
                events.forEach(event => {
                    const eventButton = document.createElement('button');
                    eventButton.className = 'calendar-event assignment';
                    eventButton.type = 'button';
                    const title = String(event.title || 'Assignment');
                    eventButton.textContent = title.length > 22 ? `${title.slice(0, 20)}…` : title;
                    eventButton.title = event.title || 'Assignment';
                    if (event.link) {
                        eventButton.addEventListener('click', () => {
                            window.location.href = event.link;
                        });
                    }
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
        renderCalendar();
    }
    
    // Initialize calendar when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeCalendar);
    } else {
        initializeCalendar();
    }
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
        localStorage.setItem('personalTasks', JSON.stringify(tasks));
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

    /* NAVIGATION FUNCTIONS WITH BEATING ANIMATION */
    function navigateToAnnouncement(announcementId) {
        const activityPanel = document.getElementById('activityPanel');
        if (activityPanel) {
            activityPanel.classList.remove('active');
        }
        
        // Navigate to home page with anchor
        window.location.href = `home.php#announcement-${announcementId}`;
    }

    function navigateToMaterial(materialId) {
        const activityPanel = document.getElementById('activityPanel');
        if (activityPanel) {
            activityPanel.classList.remove('active');
        }
        
        // Navigate to my_class with material ID to show in grades tab
        window.location.href = `my_class.php?tab=grades#material-${materialId}`;
    }

    // Highlight notification item when coming from redirect
    function highlightNotificationFromHash() {
        const hash = window.location.hash;
        if (!hash) return;
        
        const items = document.querySelectorAll('.notification-item');
        items.forEach(item => {
            item.classList.remove('highlight-active');
        });
    }

    window.addEventListener('load', highlightNotificationFromHash);
    </script>
<?php include "../includes/footer.php"; ?>
<script>
// Make user ID available globally for calendar and notification tasks
window.mucahubUserId = <?php echo $instructorId; ?>;
window.mucahubUserRole = 'instructor';
</script>
    <script src="../assets/js/instructor_dashboard.js"></script>
</body>
</html>