let currentDate = new Date()

/* =========================
HELPER FUNCTION - Get user-specific storage key
========================= */

function getTaskStorageKey(){
    // Use user ID if available, otherwise fall back to legacy "tasks" key for backward compatibility
    const userId = window.mucahubUserId || 'legacy';
    return `tasks_${userId}`;
}

/* =========================
OPEN SIDE PANELS
========================= */

const activityBtn = document.getElementById("activityBtn")
const calendarBtn = document.getElementById("calendarBtn")

const activityPanel = document.getElementById("activityPanel")
const todoPanel = document.getElementById("todoPanel")

activityBtn.onclick = () => {
activityPanel.classList.add("active")
}

calendarBtn.onclick = () => {
todoPanel.classList.add("active")
}

function closeActivity(){
activityPanel.classList.remove("active")
}

function closeTodo(){
todoPanel.classList.remove("active")
}

/* =========================
LOAD TASKS FROM STORAGE
========================= */

function loadTasks(){

let tasks = JSON.parse(localStorage.getItem(getTaskStorageKey())) || []

let list = document.getElementById("taskList")

list.innerHTML = ""

tasks.forEach((task,index)=>{

let li = document.createElement("li")

li.innerHTML =
task.text+" ("+task.date+") <button onclick='deleteTask("+index+")'>X</button>"

list.appendChild(li)

})

}

/* =========================
SAVE TASK
========================= */

function saveTask(){

let text = document.getElementById("taskText").value
let date = document.getElementById("taskDate").value

if(text === "" || date === "") return

let tasks = JSON.parse(localStorage.getItem(getTaskStorageKey())) || []

tasks.push({
text:text,
date:date
})

localStorage.setItem(getTaskStorageKey(),JSON.stringify(tasks))

document.getElementById("taskText").value = ""
document.getElementById("taskDate").value = ""

loadTasks()
renderCalendar()

}

/* =========================
DELETE TASK
========================= */

function deleteTask(index){

let tasks = JSON.parse(localStorage.getItem(getTaskStorageKey())) || []

tasks.splice(index,1)

localStorage.setItem(getTaskStorageKey(),JSON.stringify(tasks))

loadTasks()
renderCalendar()

}

/* =========================
RENDER CALENDAR
========================= */

function renderCalendar(){

const calendar = document.getElementById("calendar")
const monthYear = document.getElementById("monthYear")

calendar.innerHTML = ""

let year = currentDate.getFullYear()
let month = currentDate.getMonth()

const firstDay = new Date(year,month,1).getDay()
const daysInMonth = new Date(year,month+1,0).getDate()

monthYear.innerText =
currentDate.toLocaleString("default",{month:"long"})+" "+year

let tasks = JSON.parse(localStorage.getItem("tasks")) || []

/* EMPTY DAYS */

for(let i=0;i<firstDay;i++){

let empty = document.createElement("div")
calendar.appendChild(empty)

}

/* DAYS */

for(let day=1;day<=daysInMonth;day++){

let div = document.createElement("div")

div.classList.add("day")

let dateString =
year+"-"+String(month+1).padStart(2,'0')+"-"+String(day).padStart(2,'0')

div.innerHTML = "<strong>"+day+"</strong>"

/* HIGHLIGHT TODAY */

let today = new Date()

if(
day === today.getDate() &&
month === today.getMonth() &&
year === today.getFullYear()
){
div.classList.add("today")
}

/* SHOW TASKS */

tasks.forEach(task=>{

if(task.date === dateString){

let taskDiv = document.createElement("div")

taskDiv.style.fontSize="11px"
taskDiv.style.background="#556b2f"
taskDiv.style.color="white"
taskDiv.style.padding="2px"
taskDiv.style.marginTop="3px"
taskDiv.style.borderRadius="3px"

taskDiv.innerText = task.text

div.appendChild(taskDiv)

}

})

calendar.appendChild(div)

}

}

/* =========================
MONTH NAVIGATION
========================= */

function prevMonth(){

currentDate.setMonth(currentDate.getMonth()-1)

renderCalendar()

}

function nextMonth(){

currentDate.setMonth(currentDate.getMonth()+1)

renderCalendar()

}

/* =========================
MODAL FUNCTIONS
========================= */

let currentEditingTaskIndex = null;

function openTaskModal(index = null) {
    const modal = document.getElementById('taskModal');
    const textInput = document.getElementById('modalTaskText');
    const dateInput = document.getElementById('modalTaskDate');
    const deleteBtn = document.getElementById('modalDeleteBtn');
    
    if (!modal || !textInput || !dateInput) return;
    
    currentEditingTaskIndex = index;
    
    if (index !== null) {
        const tasks = JSON.parse(localStorage.getItem('tasks')) || [];
        if (tasks[index]) {
            textInput.value = tasks[index].text;
            dateInput.value = tasks[index].date;
            if (deleteBtn) deleteBtn.style.display = 'inline-block';
        }
    } else {
        textInput.value = '';
        dateInput.value = '';
        if (deleteBtn) deleteBtn.style.display = 'none';
    }
    
    modal.classList.add('active');
    if (textInput) textInput.focus();
}

function closeTaskModal() {
    const modal = document.getElementById('taskModal');
    if (modal) {
        modal.classList.remove('active');
    }
    currentEditingTaskIndex = null;
}

function saveTaskFromModal() {
    const textInput = document.getElementById('modalTaskText');
    const dateInput = document.getElementById('modalTaskDate');
    
    if (!textInput || !dateInput) return;
    
    const text = textInput.value.trim();
    const date = dateInput.value;
    
    if (text === '' || date === '') {
        alert('Please enter task description and date.');
        return;
    }
    
    const tasks = JSON.parse(localStorage.getItem('tasks')) || [];
    
    if (currentEditingTaskIndex !== null) {
        tasks[currentEditingTaskIndex] = { text, date };
    } else {
        tasks.push({ text, date });
    }
    
    localStorage.setItem('tasks', JSON.stringify(tasks));
    loadTasks();
    renderCalendar();
    closeTaskModal();
}

function deleteCurrentTask() {
    if (currentEditingTaskIndex === null) return;
    
    if (!confirm('Delete this task?')) return;
    
    const tasks = JSON.parse(localStorage.getItem('tasks')) || [];
    tasks.splice(currentEditingTaskIndex, 1);
    localStorage.setItem('tasks', JSON.stringify(tasks));
    loadTasks();
    renderCalendar();
    closeTaskModal();
}

/* Close modal when clicking outside */
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('taskModal');
    if (modal) {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeTaskModal();
            }
        });
    }
});

/* =========================
INITIAL LOAD
========================= */

loadTasks()
renderCalendar()