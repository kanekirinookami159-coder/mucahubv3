<?php

include "../config/database.php";
include "../config/config.php";

?>

<!DOCTYPE html>
<html>
<head>
<title>School Calendar</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.calendar-header-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.calendar-controls {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  flex: 1;
}

.calendar-controls button {
  background: #556b2f;
  border: none;
  color: white;
  padding: 6px 12px;
  cursor: pointer;
  border-radius: 4px;
}

.calendar-controls button:hover {
  background: #6b8e23;
}

#monthYear {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 700;
  color: #334155;
}

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
</style>
</head>
<body>

<div class="main">

<div class="calendar-header-wrapper">
  <h2>School Calendar</h2>
  
  <div class="calendar-controls">
    <button onclick="prevMonth()">◀</button>
    <h3 id="monthYear"></h3>
    <button onclick="nextMonth()">▶</button>
  </div>
  
  <button onclick="openTaskModal()" class="add-task-btn" title="Add Personal Task">
    <i class="fa fa-plus"></i> Add Task
  </button>
</div>

<div id="calendar"></div>

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

<script src="../assets/js/calendar.js"></script>

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
        // Get user-specific storage key
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

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeTaskModal();
    }
});
</script>

</body>
</html>
