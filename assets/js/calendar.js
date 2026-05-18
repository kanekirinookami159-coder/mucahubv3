let date = new Date();

// Helper function to get user-specific localStorage key
function getPersonalTasksKey() {
    // Use user ID if available, otherwise use 'personalTasks' for backward compatibility
    const userId = window.mucahubUserId || 'default';
    return `personalTasks_${userId}`;
}

function renderCalendar(){
    const calendar = document.getElementById("calendar");
    calendar.innerHTML = "";

    const monthYear = document.getElementById("monthYear");

    const year = date.getFullYear();
    const month = date.getMonth();

    const firstDay = new Date(year, month, 1).getDay();
    const lastDate = new Date(year, month + 1, 0).getDate();

    monthYear.innerText =
    date.toLocaleString("default",{month:"long"}) + " " + year;

    for(let i=0;i<firstDay;i++){
        const empty = document.createElement("div");
        calendar.appendChild(empty);
    }

    // Load tasks from localStorage using user-specific key
    const allTasks = JSON.parse(localStorage.getItem(getPersonalTasksKey())) || [];

    for(let day=1;day<=lastDate;day++){
        const dayBox = document.createElement("div");
        dayBox.classList.add("day");

        const dayNum = document.createElement("strong");
        dayNum.textContent = day;
        dayBox.appendChild(dayNum);

        // Filter tasks for this day
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayTasks = allTasks.filter(task => task.date === dateStr);

        // Add task cards to the day
        dayTasks.forEach(task => {
            const taskCard = document.createElement("div");
            taskCard.classList.add("calendar-task-card");
            taskCard.style.cursor = "pointer";
            taskCard.onclick = function(e) {
                e.stopPropagation();
                openTaskModal(task.id);
            };

            const taskText = document.createElement("span");
            taskText.classList.add("task-card-text");
            taskText.textContent = task.text;
            taskCard.appendChild(taskText);

            const taskActions = document.createElement("div");
            taskActions.classList.add("task-actions");

            const editBtn = document.createElement("button");
            editBtn.classList.add("task-action-btn", "edit");
            editBtn.textContent = "Edit";
            editBtn.onclick = function(e) {
                e.stopPropagation();
                openTaskModal(task.id);
            };
            taskActions.appendChild(editBtn);

            const deleteBtn = document.createElement("button");
            deleteBtn.classList.add("task-action-btn", "delete");
            deleteBtn.textContent = "Delete";
            deleteBtn.onclick = function(e) {
                e.stopPropagation();
                deleteTask(task.id);
            };
            taskActions.appendChild(deleteBtn);

            taskCard.appendChild(taskActions);
            dayBox.appendChild(taskCard);
        });

        calendar.appendChild(dayBox);
    }
}

// Global function to re-render calendar after task changes
function renderCalendarGlobal() {
    renderCalendar();
}

function prevMonth(){
    date.setMonth(date.getMonth()-1);
    renderCalendar();
}

function nextMonth(){
    date.setMonth(date.getMonth()+1);
    renderCalendar();
}

renderCalendar();