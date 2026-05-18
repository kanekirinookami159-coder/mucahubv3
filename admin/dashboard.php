<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/databases/db_connection.php';

// Get admin ID from session for calendar tasks isolation
$adminId = intval($_SESSION['user_id'] ?? 0);

$totalCourses = 0;
$totalStudents = 0;
$totalInstructors = 0;
$totalAnnouncements = 0;

// Total Courses
if ($result = $conn->query("SELECT COUNT(*) AS total FROM courses")) {
    $row = $result->fetch_assoc();
    $totalCourses = intval($row['total'] ?? 0);
}

// Total Students
if ($result = $conn->query("SELECT COUNT(*) AS total FROM students")) {
    $row = $result->fetch_assoc();
    $totalStudents = intval($row['total'] ?? 0);
}

// Total Instructors
if ($result = $conn->query("SELECT COUNT(*) AS total FROM instructors")) {
    $row = $result->fetch_assoc();
    $totalInstructors = intval($row['total'] ?? 0);
}

// Total Announcements
if ($result = $conn->query("SELECT COUNT(*) AS total FROM announcements")) {
    $row = $result->fetch_assoc();
    $totalAnnouncements = intval($row['total'] ?? 0);
}

// Get recent announcements
$recentAnnouncements = [];
$announcementsQuery = "SELECT id, title, created_at FROM announcements ORDER BY created_at DESC LIMIT 6";
if ($result = $conn->query($announcementsQuery)) {
    while ($row = $result->fetch_assoc()) {
        $recentAnnouncements[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
  <title>MUCAHUB Dashboard</title>

  <link rel="stylesheet" href="../assets/css/dashboard.css">

  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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

        /* CALENDAR TASK CARD STYLES */
        .calendar-task-card {
            background: linear-gradient(135deg, #556b2f 0%, #6b8e23 100%);
            color: white;
            padding: 6px 8px;
            border-radius: 4px;
            margin-top: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
            transition: all 0.2s ease;
            border-left: 3px solid #9fbc8f;
        }

        .calendar-task-card:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(85, 107, 47, 0.4);
            background: linear-gradient(135deg, #6b8e23 0%, #556b2f 100%);
        }

        .task-card-text {
            display: inline;
            word-break: break-word;
        }

        /* ANNOUNCEMENT BEAT ANIMATION */
        .announcement-card.beat-active {
            animation: beat-pulse 1.2s ease 2;
            background: #fff9e6 !important;
            box-shadow: 0 0 0 4px rgba(85, 107, 47, 0.2) !important;
        }

        @keyframes beat-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        /* ANALYTICS STYLES */
        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            margin-top: 30px;
        }

        .export-controls {
            display: flex;
            gap: 10px;
        }

        .export-btn {
            background: #556b2f;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            cursor: pointer;
        }

        /* ANNOUNCEMENTS SECTION STYLES */
        .announcements-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .announcements-section h3 {
            color: #556b2f;
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 1.3rem;
        }

        .announcements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 16px;
        }

        .announcement-card {
            background: linear-gradient(135deg, #f0f5e8 0%, #eef6df 100%);
            border: 2px solid #dbe7c4;
            border-radius: 8px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .announcement-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(85, 107, 47, 0.2);
            border-color: #556b2f;
        }

        .announcement-title {
            font-weight: 700;
            color: #334155;
            margin: 0 0 8px 0;
            font-size: 1.05rem;
            word-break: break-word;
        }

        .announcement-date {
            font-size: 0.85rem;
            color: #64748b;
            margin: 0;
        }

        .announcement-empty {
            text-align: center;
            color: #64748b;
            padding: 24px;
            font-style: italic;
        }

        .confirm-btn {
            background: #6b8e23;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            cursor: pointer;
            display: none;
        }

        .cancel-btn {
            background: red;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            cursor: pointer;
            display: none;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        @media (max-width: 900px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }

        .analytics-card {
            background: white;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .analytics-card canvas {
            height: 320px !important;
            width: 100% !important;
        }

        .graph-check {
            position: absolute;
            top: 15px;
            right: 15px;
            display: none;
        }

        .export-mode .graph-check {
            display: block;
        }
    </style>


</head>

<body>

  <!-- SIDEBAR -->
  <?php include "../includes/sidebar_admin.php"; ?>

  <!-- MAIN CONTENT -->
  <div class="main">

    <h2>Hi, Admin</h2>

    <!-- DASHBOARD STATISTICS -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center;">
            <h3 style="color: #556b2f; font-size: 32px; margin: 0;">
                <?php echo $totalStudents; ?>
            </h3>
            <p style="color: #666; margin: 10px 0 0 0;">Total Students</p>
        </div>
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center;">
            <h3 style="color: #556b2f; font-size: 32px; margin: 0;">
                <?php echo $totalCourses; ?>
            </h3>
            <p style="color: #666; margin: 10px 0 0 0;">Total Courses</p>
        </div>
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center;">
            <h3 style="color: #556b2f; font-size: 32px; margin: 0;">
                <?php echo $totalInstructors; ?>
            </h3>
            <p style="color: #666; margin: 10px 0 0 0;">Total Instructors</p>
        </div>
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center;">
            <h3 style="color: #556b2f; font-size: 32px; margin: 0;">
                <?php echo $totalAnnouncements; ?>
            </h3>
            <p style="color: #666; margin: 10px 0 0 0;">Announcements</p>
        </div>
    </div>

    <!-- ANNOUNCEMENTS SECTION -->
    <div class="announcements-section">
        <h3>Recent Announcements</h3>
        <div class="announcements-grid" id="announcementsGrid">
            <?php if (empty($recentAnnouncements)): ?>
                <div class="announcement-empty">No announcements yet</div>
            <?php else: ?>
                <?php foreach ($recentAnnouncements as $announcement): ?>
                    <div class="announcement-card" data-announcement-id="<?php echo intval($announcement['id']); ?>" onclick="navigateToAnnouncement(<?php echo intval($announcement['id']); ?>)">
                        <h4 class="announcement-title"><?php echo htmlspecialchars($announcement['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                        <p class="announcement-date"><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ANALYTICS DASHBOARD -->
    <div id="analyticsSection" class="analytics-header">
        <h2>Analytics Dashboard</h2>
        <div class="export-controls">
            <button class="export-btn" onclick="startExport()">Export Graphs</button>
            <button id="confirmExport" class="confirm-btn" onclick="exportGraphs()">Confirm</button>
            <button id="cancelExport" class="cancel-btn" onclick="cancelExport()">Cancel</button>
        </div>
    </div>

    <div class="analytics-grid">
        <div class="analytics-card">
            <input type="checkbox" class="graph-check">
            <h3>Student Enrollment</h3>
            <canvas id="studentsChart"></canvas>
        </div>

        <div class="analytics-card">
            <input type="checkbox" class="graph-check">
            <h3>Platform Usage</h3>
            <canvas id="usageChart"></canvas>
        </div>

        <div class="analytics-card">
            <input type="checkbox" class="graph-check">
            <h3>Assignment Submissions</h3>
            <canvas id="assignmentChart"></canvas>
        </div>
    </div>

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

  <!-- FLOAT BUTTONS -->
  <?php include "../includes/float.php"; ?>

  <?php include "../includes/back_to_top.php"; ?>

  <!-- RECENT ACTIVITY -->
  <?php include "../includes/recent.php"; ?>

  <!-- TODO PANEL -->
  <?php include "../includes/todo.php"; ?>

  <?php include "../includes/footer.php"; ?>

  <script src="../assets/js/dashboard.js"></script>
  <script src="../assets/js/notifications.js"></script>

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

            // Load personal tasks from localStorage using user-specific key
            const adminId = window.mucahubUserId || 'default';
            const storageKey = `personalTasks_${adminId}`;
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
          const adminId = window.mucahubUserId || 'default';
          const storageKey = `personalTasks_${adminId}`;
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
      const adminId = window.mucahubUserId || 'default';
      const storageKey = `personalTasks_${adminId}`;
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
      const adminId = window.mucahubUserId || 'default';
      const storageKey = `personalTasks_${adminId}`;
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
      const adminId = window.mucahubUserId || 'default';
      const storageKey = `personalTasks_${adminId}`;
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

  /* ===== ANALYTICS FUNCTIONS ===== */

  function startExport() {
      document.querySelector(".main").classList.add("export-mode");
      document.getElementById("confirmExport").style.display = "inline-block";
      document.getElementById("cancelExport").style.display = "inline-block";
  }

  function cancelExport() {
      document.querySelector(".main").classList.remove("export-mode");
      document.querySelectorAll(".graph-check").forEach(c => c.checked = false);
      document.getElementById("confirmExport").style.display = "none";
      document.getElementById("cancelExport").style.display = "none";
  }

  function exportGraphs() {
      let selected = [];
      document.querySelectorAll(".analytics-card").forEach(card => {
          let check = card.querySelector(".graph-check");
          if (check.checked) {
              selected.push(card.querySelector("canvas"));
          }
      });

      if (selected.length == 0) {
          alert("Please select at least one graph.");
          return;
      }

      for (let i = 0; i < selected.length; i += 2) {
          let canvas = document.createElement("canvas");
          canvas.width = 900;
          canvas.height = 700;

          let ctx = canvas.getContext("2d");
          ctx.fillStyle = "white";
          ctx.fillRect(0, 0, canvas.width, canvas.height);

          ctx.drawImage(selected[i], 50, 50, 350, 300);

          if (selected[i + 1]) {
              ctx.drawImage(selected[i + 1], 450, 50, 350, 300);
          }

          let link = document.createElement("a");
          link.download = "MUCAHUB-analytics-" + (i / 2 + 1) + ".png";
          link.href = canvas.toDataURL();
          link.click();
      }

      cancelExport();
  }

  /* ANNOUNCEMENT NAVIGATION WITH BEAT ANIMATION */
  function navigateToAnnouncement(announcementId) {
      // Find the announcement card
      const card = document.querySelector(`[data-announcement-id="${announcementId}"]`);
      if (!card) {
          // Navigate directly if card not found
          window.location.href = `announcements.php#announcement-${announcementId}`;
          return;
      }

      // Add beat animation
      card.classList.add('beat-active');
      
      // Navigate after animation starts
      setTimeout(() => {
          window.location.href = `announcements.php#announcement-${announcementId}`;
      }, 300);
  }

  /* COMMON CHART SETTINGS */
  const options = {
      responsive: true,
      maintainAspectRatio: false,
      animation: {
          duration: 2000,
          easing: "easeOutQuart"
      }
  };

  /* REAL-TIME CHART MANAGEMENT */
  let chartsData = {};
  let charts = {};
  let lastUpdateTime = new Date();

  // Function to fetch analytics data from all endpoints
  function fetchAnalyticsData() {
      return Promise.all([
          fetch('get_analytics_data.php?type=student_enrollment')
              .then(r => {
                  if (!r.ok) throw new Error('Student enrollment fetch failed');
                  return r.json();
              })
              .then(data => {
                  if (data.error) throw new Error(data.error);
                  return data;
              }),
          fetch('get_analytics_data.php?type=platform_usage')
              .then(r => {
                  if (!r.ok) throw new Error('Platform usage fetch failed');
                  return r.json();
              })
              .then(data => {
                  if (data.error) throw new Error(data.error);
                  return data;
              }),
          fetch('get_analytics_data.php?type=assignment_submissions')
              .then(r => {
                  if (!r.ok) throw new Error('Assignment submissions fetch failed');
                  return r.json();
              })
              .then(data => {
                  if (data.error) throw new Error(data.error);
                  return data;
              })
      ]);
  }

  // Function to update or create charts
  function updateCharts(enrollmentData, usageData, submissionData) {
      console.log("Updating charts at", new Date().toLocaleTimeString());

      // Update Student Enrollment Chart
      if (enrollmentData.labels && enrollmentData.data) {
          if (charts.students) {
              charts.students.data.labels = enrollmentData.labels;
              charts.students.data.datasets[0].data = enrollmentData.data;
              charts.students.update('none'); // Update without animation
          } else {
              charts.students = new Chart(document.getElementById("studentsChart"), {
                  type: "line",
                  data: {
                      labels: enrollmentData.labels,
                      datasets: [{
                          label: "Student Enrollment",
                          data: enrollmentData.data,
                          borderColor: "#556b2f",
                          backgroundColor: "#8fbc8f",
                          fill: true,
                          tension: 0.4
                      }]
                  },
                  options: {
                      ...options,
                      plugins: {
                          legend: { display: true }
                      },
                      scales: {
                          y: {
                              beginAtZero: true,
                              max: 100,
                              ticks: {
                                  stepSize: 20
                              }
                          }
                      }
                  }
              });
          }
      }

      // Update Platform Usage Chart
      if (usageData.labels && usageData.data) {
          if (charts.usage) {
              charts.usage.data.labels = usageData.labels;
              charts.usage.data.datasets[0].data = usageData.data;
              charts.usage.update('none');
          } else {
              charts.usage = new Chart(document.getElementById("usageChart"), {
                  type: "line",
                  data: {
                      labels: usageData.labels,
                      datasets: [{
                          label: "Platform Logins",
                          data: usageData.data,
                          borderColor: "#556b2f",
                          backgroundColor: "#dbe7c9",
                          fill: true
                      }]
                  },
                  options: {
                      ...options,
                      plugins: {
                          legend: { display: true }
                      }
                  }
              });
          }
      }

      // Update Assignment Submissions Chart
      if (submissionData.labels && submissionData.data) {
          if (charts.assignments) {
              charts.assignments.data.labels = submissionData.labels;
              charts.assignments.data.datasets[0].data = submissionData.data;
              charts.assignments.update('none');
          } else {
              charts.assignments = new Chart(document.getElementById("assignmentChart"), {
                  type: "bar",
                  data: {
                      labels: submissionData.labels,
                      datasets: [{
                          label: "Submissions",
                          data: submissionData.data,
                          backgroundColor: ["#556b2f", "#6b8e23", "#8fbc8f"]
                      }]
                  },
                  options: {
                      ...options,
                      plugins: {
                          legend: { display: false }
                      }
                  }
              });
          }
      }

      lastUpdateTime = new Date();
      console.log("Charts updated successfully at", lastUpdateTime.toLocaleTimeString());
  }

  // Initial load
  fetchAnalyticsData()
      .then(([enrollmentData, usageData, submissionData]) => {
          updateCharts(enrollmentData, usageData, submissionData);
      })
      .catch(error => {
          console.error("Error loading analytics data:", error);
          alert("Error loading chart data. Check browser console for details.");
      });

  // Auto-refresh every 30 seconds
  setInterval(() => {
      fetchAnalyticsData()
          .then(([enrollmentData, usageData, submissionData]) => {
              updateCharts(enrollmentData, usageData, submissionData);
          })
          .catch(error => {
              console.error("Error refreshing analytics data:", error);
          });
  }, 30000); // 30 seconds
  </script>
<script>
// Make user ID available globally for calendar and notification tasks
window.mucahubUserId = <?php echo $adminId; ?>;
window.mucahubUserRole = 'admin';
</script>

</body>

</html>