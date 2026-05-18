<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/databases/db_connection.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>MUCAHUB Dashboard - Manage Students</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <?php include "../includes/sidebar_admin.php"; ?>

    <style>
        .flex-fieldset {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            border: 1px solid #ccc;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
        }

        .flex-fieldset legend {
            font-weight: bold;
            padding: 0 0.5rem;
        }

        .form-column { flex: 1; min-width: 250px; }

        .form-group { margin-bottom: 1rem; }

        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.3rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.4rem 0.6rem;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .form-actions {
            text-align: right;
            margin-top: 1rem;
        }

        .btn-save {
            background-color: #1E3A8A;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-save:hover { background-color: #3749b0; }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        table th, table td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        table th {
            background: #f3f3f3;
        }

        .subject-summary {
            margin: 16px 0;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9fafb;
        }

        /* ================= FLOATING SEARCH ================= */

        #studentSearchBtn {
            position: fixed;
            top: 30px;
            right: 30px;
            background-color: #1E3A8A;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 22px;
            cursor: pointer;
            z-index: 9999;
            box-shadow: 0 6px 15px rgba(0,0,0,0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, transform 0.2s;
        }

        #studentSearchBtn:hover {
            background-color: #3749b0;
            transform: scale(1.08);
        }

        #studentSearchModal {
            display:none;
            position:fixed;
            top:0;
            left:0;
            width:100%;
            height:100%;
            background:rgba(0,0,0,0.6);
            justify-content:center;
            align-items:center;
            z-index:10000;
        }

        #studentSearchBox {
            background:white;
            width:700px;
            max-width: 95%;
            padding:25px;
            border-radius:12px;
            max-height:90%;
            overflow:auto;
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        #studentSearchBox h3 {
            margin-top: 0;
            color: #1E3A8A;
        }

        .search-input-wrap {
            position: relative;
            margin-bottom: 10px;
        }

        .search-input-wrap input {
            width: 100%;
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 15px;
        }

        .suggest-item {
            padding:10px 12px;
            border-bottom:1px solid #eee;
            cursor:pointer;
            transition: background 0.15s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .suggest-item:hover {
            background:#eef2ff;
        }

        .suggest-item b {
            color: #1E3A8A;
        }

        .field-row {
            display:flex;
            justify-content:space-between;
            align-items: center;
            margin-bottom:6px;
            border-bottom:1px solid #eee;
            padding:8px 0;
            gap: 10px;
        }

        .field-row .field-label {
            font-weight: 600;
            color: #333;
            min-width: 140px;
            text-transform: capitalize;
        }

        .field-row .field-value {
            flex: 1;
            color: #555;
        }

        .field-row input {
            flex: 1;
            padding: 6px 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .edit-btn {
            background:#1E3A8A;
            color:white;
            border:none;
            padding:4px 10px;
            border-radius: 4px;
            cursor:pointer;
            font-size: 13px;
        }

        .edit-btn:hover {
            background:#3749b0;
        }

        .modal-header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            gap: 12px;
        }

        .modal-header-actions h3 {
            margin: 0;
            font-size: 1.1rem;
        }

        .modal-header-actions button {
            white-space: nowrap;
        }

        .modal-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .modal-actions button {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            background: #1E3A8A;
            color: white;
        }

        .btn-primary:hover {
            background: #3749b0;
        }

        .btn-success {
            background: #166534;
            color: white;
        }

        .btn-success:hover {
            background: #15803d;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .view-section {
            display: none;
        }

        .view-section.active {
            display: block;
        }
    </style>
</head>

<body>

<div class="main">
    <h2>Manage Student Information</h2>

    <form id="studentForm" action="save_student.php" method="POST">

        <!-- ================= YOUR ORIGINAL CONTENT (UNCHANGED) ================= -->

        <fieldset class="flex-fieldset">
            <legend>Basic Information</legend>

            <div class="form-column">
                <div class="form-group">
                    <label>Student ID <small>(leave blank to auto-generate)</small></label>
                    <input type="text" name="student_id" placeholder="Leave blank to auto-generate">
                </div>

                <div class="form-group">
                    <label>LRN <small>(leave blank to auto-generate)</small></label>
                    <input type="text" name="lrn" placeholder="Leave blank to auto-generate">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="studentEmail">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="text" id="studentPasswordDisplay" disabled placeholder="Password auto-filled from email">
                    <input type="hidden" name="password" id="studentPassword">
                </div>

                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" required>
                </div>

                <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="middle_name">
                </div>

                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required>
                </div>

                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" required>
                </div>

                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" required>
                </div>
            </div>

            <div class="form-column">
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender">
                        <option>Select</option>
                        <option>Male</option>
                        <option>Female</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Parent First Name</label>
                    <input type="text" name="parent_first_name">
                </div>

                <div class="form-group">
                    <label>Parent Last Name</label>
                    <input type="text" name="parent_last_name">
                </div>

                <div class="form-group">
                    <label>Relationship</label>
                    <select name="relationship" placeholder="Select Relationship">
                        <option value="Select">Select</option>
                        <option value="Mother">Mother</option>
                        <option value="Father">Father</option>
                        <option value="Guardian">Guardian</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number">
                </div>

                <div class="form-group">
                    <label>Emergency Contact</label>
                    <input type="text" name="emergency_contact">
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address"></textarea>
                </div>
            </div>
        </fieldset>

        <fieldset class="flex-fieldset">
            <legend>Academic & Enrollment</legend>

            <div class="form-column">
                <div class="form-group">
                    <label>Grade Level</label>
                    <select id="gradeLevel" name="grade_level" required>
                        <option value="">Select</option>
                        <option>Nursery</option>
                        <option>Kinder</option>
                        <option>Grade 1</option>
                        <option>Grade 2</option>
                        <option>Grade 3</option>
                        <option>Grade 4</option>
                        <option>Grade 5</option>
                        <option>Grade 6</option>
                        <option>Grade 7</option>
                        <option>Grade 8</option>
                        <option>Grade 9</option>
                        <option>Grade 10</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Section</label>
                    <select id="sectionSelect" name="section" required>
                        <option value="">Select</option>
                        <option>A</option>
                        <option>B</option>
                        <option>C</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Adviser</label>
                    <input type="text" id="adviser" name="adviser" readonly>
                </div>
            </div>

            <div class="form-column">
                <div class="form-group">
                    <label>Status</label>
                    <select name="enrollment_status">
                        <option>Enrolled</option>
                        <option>Pending</option>
                        <option>Dropped</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date Enrolled</label>
                    <input type="date" name="date_enrolled">
                </div>

                <div class="form-group">
                    <label>Student Type</label>
                    <select name="student_type">
                        <option>New</option>
                        <option>Old</option>
                        <option>Transferee</option>
                    </select>
                </div>
            </div>
        </fieldset>

        <fieldset class="flex-fieldset" id="subjectsSection" style="display:none;">
            <legend>Subjects & Assigned Teachers</legend>
            <div id="subjectsList"></div>
        </fieldset>

        <div class="form-actions">
            <button type="submit" class="btn-save">Save Student</button>
        </div>

    </form>
</div>

<!-- ================= FLOATING SEARCH ================= -->

<button id="studentSearchBtn" onclick="openStudentSearch()" title="Search Student">
    <i class="fa fa-search"></i>
</button>

<div id="studentSearchModal">
    <div id="studentSearchBox">

        <!-- SEARCH VIEW -->
        <div id="searchView" class="view-section active">
            <div class="modal-header-actions">
                <h3><i class="fa fa-search"></i> Search Student</h3>
                <button type="button" class="btn-secondary" onclick="showAllStudents()">Show All Students</button>
            </div>

            <div class="search-input-wrap">
                <input type="text" id="studentSearchInput"
                placeholder="Type Student ID or Name and press Enter">
            </div>

            <div id="studentSuggestBox"></div>

            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeStudentSearch()">Close</button>
            </div>
        </div>

        <!-- DETAILS VIEW -->
        <div id="detailsView" class="view-section">
            <div class="modal-header-actions">
                <h3><i class="fa fa-user"></i> Student Details</h3>
                <div>
                    <button type="button" class="btn-primary" id="editAllBtn" onclick="toggleEditAllStudents()" style="margin-right: 8px;">Edit</button>
                    <button type="button" class="btn-primary" onclick="exportStudentPdf()">Export Info</button>
                </div>
            </div>

            <input type="hidden" id="sid">

            <div id="studentInfoBox"></div>
            <div id="studentScheduleBox" class="subject-summary" style="margin-top:16px;"></div>

            <div class="modal-actions">
                <button class="btn-secondary" onclick="backToSearch()">Back</button>
                <button class="btn-danger" onclick="deleteStudent()">Delete</button>
                <button class="btn-secondary" onclick="openAdminPasswordReset('student')">Reset Password</button>
                <button class="btn-success" onclick="saveStudentEdit()">Save Changes</button>
            </div>
        </div>

    </div>
</div>
<?php include "../includes/back_to_top.php"; ?>
<?php include "../includes/float.php"; ?>
<?php include "../includes/footer.php"; ?>

<div id="adminQrModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:10010;align-items:center;justify-content:center;">
    <div style="width:min(520px,95%);background:#fff;border-radius:14px;padding:24px;position:relative;">
        <button onclick="closeAdminQrModal()" style="position:absolute;top:14px;right:14px;border:none;background:transparent;font-size:18px;cursor:pointer;">&times;</button>
        <h3 style="margin-top:0;color:#1E3A8A;">Admin Password Reset Authorization</h3>
        <p style="margin-bottom:16px;color:#444;">Scan the QR code and enter the six-digit code to confirm the password reset.</p>
        <div id="adminQrImage" style="width:240px;height:240px;margin:0 auto 16px;border:1px solid #ddd;border-radius:12px;display:flex;align-items:center;justify-content:center;background:#f7f7f7;">
            <span style="color:#888;font-size:14px;">Loading QR...</span>
        </div>
        <div style="margin-bottom:12px;">
            <label style="display:block;margin-bottom:6px;font-weight:600;color:#333;">Enter code</label>
            <input id="adminQrCodeInput" type="text" maxlength="6" inputmode="numeric" pattern="\d{6}" style="width:100%;padding:12px;border:1px solid #ccc;border-radius:10px;font-size:14px;" oninput="this.value=this.value.replace(/\D/g,'').slice(0,6);">
        </div>
        <div id="adminQrStatus" style="min-height:20px;color:#d9534f;margin-bottom:12px;"></div>
        <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;">
            <button type="button" onclick="closeAdminQrModal()" class="btn-secondary">Cancel</button>
            <button type="button" onclick="submitAdminQrCode()" class="btn-success">Authorize</button>
        </div>
    </div>
</div>

<script src="../assets/js/notifications.js"></script>
<script>

/* ===== ORIGINAL FUNCTIONS (UNCHANGED) ===== */

const gradeLevel = document.getElementById('gradeLevel');
const sectionSelect = document.getElementById('sectionSelect');
const adviser = document.getElementById('adviser');
const subjectsSection = document.getElementById('subjectsSection');
const subjectsList = document.getElementById('subjectsList');
const studentEmail = document.getElementById('studentEmail');
const studentPasswordDisplay = document.getElementById('studentPasswordDisplay');
const studentPasswordHidden = document.getElementById('studentPassword');

studentEmail.addEventListener('input', () => {
    let value = studentEmail.value.trim();
    if (value && value.indexOf('@') === -1) {
        value = value + '@mucahub.com';
    }
    studentEmail.value = value;
    studentPasswordDisplay.value = value;
    studentPasswordHidden.value = value;
});

const studentFieldOptions = {
    grade_level: ["Nursery","Kinder","Grade 1","Grade 2","Grade 3","Grade 4","Grade 5","Grade 6","Grade 7","Grade 8","Grade 9","Grade 10"],
    section: ["A","B","C","D"],
    gender: ["Male","Female","Other"],
        relationship: ["Mother","Father","Guardian"],
    student_type: ["New","Old","Transferee"]
};

function formatLabel(key){
    return key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function getEditor(key, value){
    let normalized = key.toLowerCase();

    if(normalized === 'address'){
        let textarea = document.createElement('textarea');
        textarea.value = value;
        textarea.id = 'input-' + key;
        textarea.style.flex = '1';
        textarea.rows = 3;
        return textarea;
    }

    if(normalized === 'dob' || normalized === 'date_of_birth' || normalized === 'date_enrolled'){
        let input = document.createElement('input');
        input.type = 'date';
        input.value = value;
        input.id = 'input-' + key;
        input.style.flex = '1';
        return input;
    }

    if(normalized === 'password'){
        let input = document.createElement('input');
        input.type = 'password';
        input.value = value;
        input.id = 'input-' + key;
        input.style.flex = '1';
        return input;
    }

    if(normalized === 'email'){
        let input = document.createElement('input');
        input.type = 'email';
        input.value = value;
        input.id = 'input-' + key;
        input.style.flex = '1';
        return input;
    }

    if(normalized === 'contact_number' || normalized === 'emergency_contact'){
        let input = document.createElement('input');
        input.type = 'tel';
        input.value = value;
        input.id = 'input-' + key;
        input.style.flex = '1';
        return input;
    }

    if(studentFieldOptions[normalized]){
        let select = document.createElement('select');
        select.id = 'input-' + key;
        select.style.flex = '1';

        studentFieldOptions[normalized].forEach(opt => {
            let option = document.createElement('option');
            option.value = opt;
            option.innerText = opt;
            if(opt === value) option.selected = true;
            select.appendChild(option);
        });

        return select;
    }

    let input = document.createElement('input');
    input.type = 'text';
    input.value = value;
    input.id = 'input-' + key;
    input.style.flex = '1';
    return input;
}

function loadSubjects() {
    const g = gradeLevel.value.trim();
    const s = sectionSelect.value.trim();

    if (g !== "" && s !== "") {

        subjectsSection.style.display = "block";

        fetch(`get_subjects_teachers.php?grade=${encodeURIComponent(g)}&section=${encodeURIComponent(s)}`)
        .then(res => res.json())
        .then(data => {

            let html = `<table><tr><th>Subject</th><th>Teacher</th></tr>`;

            data.forEach(row => {
                html += `<tr><td>${row.subject}</td><td>${row.teacher}</td></tr>`;
            });

            html += `</table>`;
            subjectsList.innerHTML = html;
        });
    }
}

function loadAdviser() {
    const g = gradeLevel.value;
    const s = sectionSelect.value;

    if (g && s) {
        fetch(`get_adviser.php?grade=${g}&section=${s}`)
        .then(res => res.text())
        .then(data => adviser.value = data);
    }
}

gradeLevel.addEventListener('change', () => {
    loadSubjects();
    loadAdviser();
});

sectionSelect.addEventListener('change', () => {
    loadSubjects();
    loadAdviser();
});

/* ===== FLOATING SEARCH ===== */

function openStudentSearch(){
    document.getElementById("studentSearchModal").style.display = "flex";
    document.getElementById("studentSearchInput").focus();
}

function closeStudentSearch(){
    document.getElementById("studentSearchModal").style.display = "none";
    // Reset to search view on close
    document.getElementById("searchView").classList.add("active");
    document.getElementById("detailsView").classList.remove("active");
    document.getElementById("studentSearchInput").value = "";
    document.getElementById("studentSuggestBox").innerHTML = "";
}

function showDetailsView(){
    document.getElementById("searchView").classList.remove("active");
    document.getElementById("detailsView").classList.add("active");
}

function backToSearch(){
    document.getElementById("searchView").classList.add("active");
    document.getElementById("detailsView").classList.remove("active");
}

function showAllStudents(){
    fetch('search_student.php?q=')
    .then(res => res.json())
    .then(data => {
        let html = '';
        if(data.length > 0){
            data.forEach(s => {
                let emailText = s.email ? ` - ${s.email}` : '';
                html += `
                    <div class="suggest-item" onclick="loadStudent(${s.id})">
                        <span><b>${s.student_id}</b> - ${s.first_name} ${s.last_name}${emailText}</span>
                        <i class="fa fa-chevron-right" style="color:#999;"></i>
                    </div>`;
            });
        } else {
            html = `<div class="suggest-item" style="cursor:default;">No students found</div>`;
        }
        document.getElementById('studentSuggestBox').innerHTML = html;
    })
    .catch(() => {
        document.getElementById('studentSuggestBox').innerHTML = `<div class="suggest-item" style="cursor:default;">Unable to load student list</div>`;
    });
}

// ENTER KEY SUPPORT
document.getElementById("studentSearchInput").addEventListener("keydown", function(e){
    if(e.key === "Enter"){
        triggerSearch();
    }
});

function triggerSearch(){

    let q = document.getElementById("studentSearchInput").value.trim();

    if(q === ""){
        document.getElementById("studentSuggestBox").innerHTML = "";
        return;
    }

    fetch("search_student.php?q=" + encodeURIComponent(q))
    .then(res => res.json())
    .then(data => {

        let html = "";

        if(data.length > 0){

            data.forEach(s => {
                let emailText = s.email ? ` - ${s.email}` : "";
                html += `
                    <div class="suggest-item" onclick="loadStudent(${s.id})">
                        <span><b>${s.student_id}</b> - ${s.first_name} ${s.last_name}${emailText}</span>
                        <i class="fa fa-chevron-right" style="color:#999;"></i>
                    </div>`;
            });

        } else {
            html = `<div class="suggest-item" style="cursor:default;">No matching student found</div>`;
        }

        document.getElementById("studentSuggestBox").innerHTML = html;
    });
}

function loadStudent(id){

    fetch("get_student.php?id=" + id)
    .then(res => res.json())
    .then(s => {

        document.getElementById("sid").value = s.id;

        let html = "";
        // Fields that should not be editable
        const nonEditableFields = ['id', 'created_at', 'last_modified', 'student_id'];
        
        // Always show these fields first, even if empty
        const priorityFields = ['student_id', 'dob'];
        
        for(let key in s){
            if(key === "id" || key === "created_at" || key === "last_modified") continue;

            let displayValue = s[key] ?? '';
            let safeValue = displayValue.toString().replace(/'/g, "\\'");
            let label = formatLabel(key);
            
            // Check if this field is editable
            const isEditable = !nonEditableFields.includes(key);
            const editableClass = isEditable ? 'editable-field' : '';

            html += `
                <div class="field-row ${editableClass}" data-key="${key}">
                    <span class="field-label">${label}</span>
                    <span class="field-value" id="val-${key}">${displayValue}</span>
                </div>`;
        }

        html += `
            <div class="field-row editable-field" data-key="password">
                <span class="field-label">Password</span>
                <span class="field-value" id="val-password">********</span>
            </div>`;

        document.getElementById("studentInfoBox").innerHTML = html;
        window.currentStudentData = s;
        loadStudentSchedule(s.grade_level, s.section);
        showDetailsView();
    });
}

function formatTime12(time24){
    if(!time24) return '';
    const parts = time24.split(':');
    if(parts.length !== 2) return time24;
    let hour = parseInt(parts[0], 10);
    const minute = parts[1];
    const suffix = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12;
    if(hour === 0) hour = 12;
    return `${hour}:${minute} ${suffix}`;
}

function renderStudentSchedule(schedule){
    const box = document.getElementById('studentScheduleBox');
    const items = (schedule || []).slice().sort((a, b) => {
        if(!a.start && !b.start) return 0;
        if(!a.start) return 1;
        if(!b.start) return -1;
        return a.start.localeCompare(b.start);
    });
    window.currentStudentSchedule = items;

    if(!items.length){
        box.innerHTML = '<div class="summary-line"><strong>Schedule</strong></div><div>No subjects assigned for this student.</div>';
        return;
    }

    let html = '<div class="summary-line"><strong>Schedule</strong></div>';
    html += '<table style="width:100%; border-collapse: collapse; margin-top: 10px;">';
    html += '<thead><tr style="background:#f3f3f3;"><th style="padding:8px; border:1px solid #ccc; text-align:left;">Subject</th><th style="padding:8px; border:1px solid #ccc; text-align:left;">Teacher</th><th style="padding:8px; border:1px solid #ccc; text-align:left;">Days</th><th style="padding:8px; border:1px solid #ccc; text-align:left;">Time</th></tr></thead>';
    html += '<tbody>';
    items.forEach(item => {
        const days = item.days ? item.days : 'M T W Th F';
        const timeLabel = item.start || item.end ? `${formatTime12(item.start)} - ${formatTime12(item.end)}`.trim() : 'TBD';
        html += `<tr><td style="padding:8px; border:1px solid #ccc;">${item.subject}</td><td style="padding:8px; border:1px solid #ccc;">${item.teacher}</td><td style="padding:8px; border:1px solid #ccc;">${days}</td><td style="padding:8px; border:1px solid #ccc;">${timeLabel}</td></tr>`;
    });
    html += '</tbody></table>';

    box.innerHTML = html;
}

function loadStudentSchedule(grade, section){
    if(!grade || !section){
        renderStudentSchedule([]);
        return;
    }

    fetch(`get_subjects_teachers.php?grade=${encodeURIComponent(grade)}&section=${encodeURIComponent(section)}`)
    .then(res => res.json())
    .then(data => {
        renderStudentSchedule(data);
    })
    .catch(() => {
        renderStudentSchedule([]);
    });
}

function exportStudentPdf(){
    if(!window.currentStudentData){
        alert('No student selected.');
        return;
    }

    const { jsPDF } = window.jspdf || {};
    if(!jsPDF){
        alert('PDF library not loaded.');
        return;
    }

    const doc = new jsPDF();
    const student = window.currentStudentData;
    const schedule = (window.currentStudentSchedule || []).slice().sort((a, b) => {
        if(!a.start && !b.start) return 0;
        if(!a.start) return 1;
        if(!b.start) return -1;
        return a.start.localeCompare(b.start);
    });
    const logoSrc = '../assets/images/mucalogo.png';

    const formatTime12 = (time24) => {
        if(!time24) return '';
        const parts = time24.split(':');
        if(parts.length !== 2) return time24;
        let hour = parseInt(parts[0], 10);
        const minute = parts[1];
        const suffix = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12;
        if(hour === 0) hour = 12;
        return `${hour}:${minute} ${suffix}`;
    };

    const addHeader = (imgData) => {
        if(imgData){
            doc.addImage(imgData, 'PNG', 15, 10, 18, 18);
        }
        doc.setFontSize(16);
        doc.text('Medicion Unida Christian Academy', 40, 18);
        doc.setFontSize(11);
        doc.text('Student Details Report', 40, 24);
        doc.setLineWidth(0.4);
        doc.line(15, 28, 195, 28);
    };

    const drawDocument = (imgData) => {
        addHeader(imgData);

        let y = 36;
        const lineHeight = 7;

        const addLine = (label, value) => {
            doc.setFontSize(11);
            doc.text(`${label}:`, 15, y);
            doc.text(String(value || ''), 60, y);
            y += lineHeight;
        };

        addLine('Student ID', student.student_id || '');
        addLine('LRN', student.lrn || '');
        addLine('Name', `${student.first_name || ''} ${student.middle_name || ''} ${student.last_name || ''}`.trim());
        addLine('Email', student.email || '');
        addLine('Gender', student.gender || '');
        addLine('Age', student.age || '');
        addLine('Grade Level', student.grade_level || '');
        addLine('Section', student.section || '');
        addLine('Adviser', student.adviser || '');
        addLine('Status', student.enrollment_status || '');
        addLine('Date Enrolled', student.date_enrolled || '');
        y += lineHeight;

        doc.setFontSize(12);
        doc.text('Schedule', 15, y);
        y += lineHeight;

        if(schedule.length === 0){
            doc.setFontSize(11);
            doc.text('No subjects assigned for this student.', 15, y);
            doc.save(`${student.student_id || 'student'}_details.pdf`);
            return;
        }

        doc.setFontSize(10);
        doc.text('Subject', 15, y);
        doc.text('Teacher', 70, y);
        doc.text('Days', 120, y);
        doc.text('Time', 165, y);
        y += lineHeight;
        doc.setLineWidth(0.3);
        doc.line(15, y - 3, 195, y - 3);

        schedule.forEach(item => {
            if(y > 270){
                doc.addPage();
                y = 20;
            }
            const days = item.days ? item.days : 'M T W Th F';
            const timeLabel = item.start || item.end ? `${formatTime12(item.start)} - ${formatTime12(item.end)}`.trim() : 'TBD';
            doc.text(item.subject, 15, y);
            doc.text(item.teacher, 70, y);
            doc.text(days, 120, y);
            doc.text(timeLabel, 165, y);
            y += lineHeight;
        });

        doc.save(`${student.student_id || 'student'}_details.pdf`);
    };

    const img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = function(){
        const canvas = document.createElement('canvas');
        canvas.width = img.width;
        canvas.height = img.height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0);
        const imgData = canvas.toDataURL('image/png');
        drawDocument(imgData);
    };
    img.onerror = function(){
        drawDocument();
    };
    img.src = logoSrc;
}

let isStudentEditingAll = false;

function toggleEditAllStudents(){
    isStudentEditingAll = !isStudentEditingAll;
    let btn = document.getElementById('editAllBtn');
    
    if(isStudentEditingAll){
        // Enable edit mode for all editable fields
        document.querySelectorAll('#studentInfoBox .field-row.editable-field').forEach(row => {
            let key = row.getAttribute('data-key');
            let span = row.querySelector('.field-value');
            if(!span) return;
            
            let currentValue = span.innerText;
            if(key === 'password') currentValue = '';
            
            let input = getEditor(key, currentValue);
            span.replaceWith(input);
        });
        btn.innerText = 'Done Editing';
    } else {
        // Disable edit mode for all fields
        document.querySelectorAll('#studentInfoBox .field-row.editable-field').forEach(row => {
            let key = row.getAttribute('data-key');
            let input = row.querySelector('input, textarea, select');
            if(!input) return;
            
            let newValue = input.value;
            let span = document.createElement('span');
            span.className = 'field-value';
            span.id = 'val-' + key;
            span.innerText = (key === 'password') ? '********' : newValue;
            
            input.replaceWith(span);
        });
        btn.innerText = 'Edit';
    }
}

function editField(key, currentValue){
    let container = document.querySelector(`.field-row[data-key="${key}"]`);
    if(!container) return;

    let existing = container.querySelector('.field-value');
    if(!existing) return;

    let input = getEditor(key, currentValue);
    existing.replaceWith(input);
    input.focus();

    let btn = container.querySelector('.edit-btn');
    if(btn){
        btn.innerText = 'Done';
        btn.onclick = function(){ finishEdit(key); };
    }
}

function finishEdit(key){
    let input = document.getElementById('input-' + key);
    if(!input) return;

    let newValue = input.value;
    let span = document.createElement('span');
    span.className = 'field-value';
    span.id = 'val-' + key;
    span.innerText = (key === 'password') ? '********' : newValue;

    input.replaceWith(span);

    let container = document.querySelector(`.field-row[data-key="${key}"]`);
    let btn = container.querySelector('.edit-btn');
    if(btn){
        btn.innerText = 'Edit';
        let safeValue = newValue.toString().replace(/'/g, "\\'");
        btn.onclick = function(){ editField(key, safeValue); };
    }
}

function saveStudentEdit(){

    let id = document.getElementById("sid").value;

    if(!id){
        alert("No student selected.");
        return;
    }

    let form = new FormData();
    form.append("id", id);

    // Fields that should not be sent in updates
    const nonEditableFields = ['student_id'];

    // Collect all field values from the details view
    let rows = document.querySelectorAll("#studentInfoBox .field-row");
    rows.forEach(row => {
        let key = row.getAttribute("data-key");
        if(!key) return;
        
        // Skip non-editable fields
        if(nonEditableFields.includes(key)) return;

        let val = "";
        let input = row.querySelector("input");
        if(input){
            val = input.value;
        } else {
            let span = row.querySelector(".field-value");
            if(span){
                val = span.innerText;
            }
        }

        if(key === 'password'){
            if(!input){
                return; // do not update password when unchanged
            }
            if(!val || val === '********'){
                return;
            }
        }

        form.append(key, val);
    });

    fetch("update_student.php", {
        method: "POST",
        body: form
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            alert("Saved Successfully");
        } else {
            alert("Save Failed: " + (data.message || "Unknown error"));
        }
    })
    .catch(err => {
        console.error(err);
        alert("An error occurred while saving.");
    });
}

function deleteStudent(){
    let id = document.getElementById('sid').value;
    if(!id){
        alert('No student selected.');
        return;
    }

    if(!confirm('Are you sure you want to delete this student? This cannot be undone.')){
        return;
    }

    fetch('delete_student.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id)
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            alert('Student deleted successfully.');
            closeStudentSearch();
        } else {
            alert('Delete failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred while deleting.');
    });
}

let adminResetTarget = null;

function openAdminPasswordReset(role) {
    if (!role) return;

    const id = document.getElementById('sid').value;
    if (!id) {
        alert('Please select a student before resetting password.');
        return;
    }

    adminResetTarget = { role, id };
    document.getElementById('adminQrStatus').innerText = '';
    document.getElementById('adminQrCodeInput').value = '';
    document.getElementById('adminQrModal').style.display = 'flex';

    fetch('request_admin_action_qr.php')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.qrDataUri) {
                document.getElementById('adminQrImage').innerHTML = `<img src="${data.qrDataUri}" alt="QR code" style="max-width:100%;max-height:100%;border-radius:12px;">`;
            } else {
                document.getElementById('adminQrImage').innerText = 'Unable to load QR';
            }
        })
        .catch(() => {
            document.getElementById('adminQrImage').innerText = 'Unable to load QR';
        });
}

function closeAdminQrModal() {
    document.getElementById('adminQrModal').style.display = 'none';
}

function submitAdminQrCode() {
    const code = document.getElementById('adminQrCodeInput').value.trim();
    if (!code || code.length !== 6) {
        document.getElementById('adminQrStatus').innerText = 'Please enter the 6-digit code.';
        return;
    }

    fetch('verify_admin_action_code.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'code=' + encodeURIComponent(code)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            resetPasswordForTarget();
        } else {
            document.getElementById('adminQrStatus').innerText = data.message || 'Invalid code. A new QR has been generated.';
            if (data.qrDataUri) {
                document.getElementById('adminQrImage').innerHTML = `<img src="${data.qrDataUri}" alt="QR code" style="max-width:100%;max-height:100%;border-radius:12px;">`;
            }
        }
    })
    .catch(() => {
        document.getElementById('adminQrStatus').innerText = 'Unable to verify code. Please try again.';
    });
}

function resetPasswordForTarget() {
    if (!adminResetTarget) {
        alert('No reset target selected.');
        closeAdminQrModal();
        return;
    }

    const studentEmail = document.getElementById('studentEmail').value.trim();
    if (!studentEmail) {
        alert('Selected student has no email on file.');
        closeAdminQrModal();
        return;
    }

    fetch('../api/password_reset_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `email=${encodeURIComponent(studentEmail)}&role=${encodeURIComponent(adminResetTarget.role)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Password reset completed.');
            closeAdminQrModal();
        } else {
            alert(data.message || 'Password reset failed.');
            closeAdminQrModal();
        }
    })
    .catch(() => {
        alert('Password reset failed due to network error.');
        closeAdminQrModal();
    });
}

</script>

</body>
</html>

