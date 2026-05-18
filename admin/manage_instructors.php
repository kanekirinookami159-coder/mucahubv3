<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/databases/db_connection.php';
?>
<!DOCTYPE html>

<html>

<head>

<title>MUCAHUB Dashboard - Manage Instructors</title>

<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<style>

/* FORM LAYOUT */

.flex-fieldset{
display:flex;
gap:2rem;
flex-wrap:wrap;
border:1px solid #ccc;
padding:1rem;
margin-bottom:1.5rem;
border-radius:8px;
}

.flex-fieldset legend{
font-weight:bold;
padding:0 0.5rem;
}

.form-column{
flex:1;
min-width:250px;
}

.form-group{
margin-bottom:1rem;
}

.form-group label{
display:block;
font-weight:500;
margin-bottom:0.3rem;
}

.form-group input,
.form-group select,
.form-group textarea{
width:100%;
padding:0.4rem 0.6rem;
border-radius:5px;
border:1px solid #ccc;
}

.form-actions{
text-align:right;
margin-top:1rem;
}

.btn-save{
background-color:#1E3A8A;
color:white;
border:none;
padding:0.6rem 1.2rem;
border-radius:5px;
cursor:pointer;
}

.btn-save:hover{
background-color:#3749b0;
}

/* SUBJECT GRID */

.grade-section{
border:1px solid #ddd;
border-radius:8px;
padding:12px;
margin-bottom:15px;
background:#fafafa;
}

.grade-title{
font-weight:bold;
margin-bottom:8px;
}

.subject-grid{
display:flex;
flex-wrap:wrap;
gap:8px;
}

/* SUBJECT BOX */
.subject-box{
padding:10px;
border:1px solid #ccc;
border-radius:6px;
cursor:pointer;
background:#f5f5f5;
font-size:14px;
position:relative;
min-width:140px;
transition:0.2s;
}

/* TITLE */
.subject-title{
font-weight:bold;
margin-bottom:5px;
}

/* HIDE MASTER CHECK */
.subject-master{
display:none;
}

/* GREEN STATE */
.subject-box.selected,
.subject-box.has-data{
background:#556B2F;
color:white;
border-color:#556B2F;
}

/* SECTION DROPDOWN */
.section-dropdown{
margin-top:6px;
padding-top:6px;
border-top:1px solid rgba(0,0,0,0.1);
}

.section-dropdown label{
display:block;
font-size:13px;
margin:2px 0;
}

.section-check{
margin-right:5px;
}

/* MODAL BACKDROP */
#timeModal{
display:none;
position:fixed;
top:0;left:0;
width:100%;height:100%;
background:rgba(0,0,0,0.6);
z-index:10002;
justify-content:center;
align-items:center;
}

/* MODAL BOX */
#timeModalBox{
background:white;
padding:20px;
border-radius:10px;
width:320px;
position:relative;
box-shadow:0 10px 25px rgba(0,0,0,0.3);
z-index:10003;
}

/* CLOSE */
#closeTimeModal{
position:absolute;
top:10px;
right:12px;
font-size:20px;
cursor:pointer;
}

/* INPUTS */
.modal-group{
margin-bottom:12px;
}

.modal-group label{
font-size:13px;
display:block;
margin-bottom:4px;
}

.modal-group input{
width:100%;
padding:6px;
border:1px solid #ccc;
border-radius:5px;
}

.day-checkboxes label{
display:inline-flex;
align-items:center;
gap:4px;
margin-right:10px;
font-size:13px;
}

/* BUTTON */
#saveTimeBtn{
width:100%;
padding:8px;
background:#1E3A8A;
color:white;
border:none;
border-radius:5px;
cursor:pointer;
}

#saveTimeBtn:hover{
background:#3749b0;
}

/* ================= FLOATING SEARCH ================= */

#instructorSearchBtn {
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
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 15px rgba(0,0,0,0.35);
    transition: background 0.2s, transform 0.2s;
}

#instructorSearchBtn:hover {
    background-color: #3749b0;
    transform: scale(1.08);
}

#instructorSearchModal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
    z-index: 10000;
}

#instructorSearchBox {
    background: white;
    width: 700px;
    max-width: 95%;
    padding: 25px;
    border-radius: 12px;
    max-height: 90%;
    overflow: auto;
    box-shadow: 0 15px 40px rgba(0,0,0,0.3);
}

#instructorSearchBox h3 {
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
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    transition: background 0.15s;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.suggest-item:hover {
    background: #eef2ff;
}

.suggest-item b {
    color: #1E3A8A;
}

.view-section {
    display: none;
}

.view-section.active {
    display: block;
}

.field-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    border-bottom: 1px solid #eee;
    padding: 8px 0;
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

.field-row input,
.field-row select,
.field-row textarea {
    flex: 1;
    padding: 6px 8px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 14px;
}

.subject-summary {
    margin: 16px 0;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9fafb;
}

.subject-summary .summary-line {
    margin-bottom: 6px;
    color: #333;
}

.subject-summary .summary-line strong {
    display: inline-block;
    min-width: 180px;
}

.edit-assignments-btn {
    margin-top: 8px;
    padding: 8px 14px;
    border: none;
    border-radius: 5px;
    background: #1E3A8A;
    color: white;
    cursor: pointer;
}

.edit-assignments-btn:hover {
    background: #3749b0;
}

.edit-btn {
    background: #1E3A8A;
    color: white;
    border: none;
    padding: 4px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
}

.edit-btn:hover {
    background: #3749b0;
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


.btn-secondary:hover {
    background: #d1d5db;
}

</style>

</head>

<body>

<?php include "../includes/sidebar_admin.php"; ?>

<div class="main">

<h2>Manage Instructor Information</h2>

<!-- ✅ FIX 1 APPLIED -->
<form id="instructorForm" action="save_instructor.php" method="POST" onsubmit="return submitInstructorForm();">

<!-- BASIC INFO -->
<fieldset class="flex-fieldset">
<legend>Instructor Information</legend>

<div class="form-column">

<div class="form-group"><label>Employee Number <small>(leave blank to auto-generate)</small></label><input type="text" name="employeeNumber" placeholder="Leave blank to auto-generate"></div>
<div class="form-group"><label>First Name</label><input type="text" name="firstName" required></div>
<div class="form-group"><label>Middle Name</label><input type="text" name="middleName"></div>
<div class="form-group"><label>Last Name</label><input type="text" name="lastName" required></div>
<div class="form-group"><label>DOB</label><input type="date" name="dob"></div>
<div class="form-group"><label>Age</label><input type="number" name="age"></div>

</div>

<div class="form-column">

<div class="form-group"><label>Address</label><textarea name="address"></textarea></div>
<div class="form-group"><label>Email</label><input type="email" name="email" id="instructorEmail"></div>
<div class="form-group"><label>Password</label><input type="text" id="instructorPasswordDisplay" disabled placeholder="Password auto-filled from email"><input type="hidden" name="password" id="instructorPassword"></div>
<div class="form-group"><label>Contact</label><input type="text" name="contactNumber"></div>
<div class="form-group"><label>Employment Date</label><input type="date" name="dateEmployment"></div>
<div class="form-group"><label>Employee Type</label>
<select name="employeeType">
<option>Regular</option>
<option>Trainee</option>
<option>Part-Time</option>
</select>
</div>

</div>

</fieldset>

<!-- ADVISORY -->
<fieldset class="flex-fieldset">
<legend>Advisory Of</legend>

<div class="form-column">
<div class="form-group">
<label>Grade Level</label>
<select name="advisoryGrade">
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
</div>

<div class="form-column">
<div class="form-group">
<label>Section</label>
<select name="advisorySection">
<option>A</option>
<option>B</option>
<option>C</option>
</select>
</div>
</div>

</fieldset>

<!-- SUBJECTS -->
<fieldset id="subjectAssignmentArea">
<legend><b>Subject Assignment Per Grade</b></legend>

<?php
include "../includes/databases/db_connection.php";

$grades = ["Kinder","Grade 1","Grade 2","Grade 3","Grade 4","Grade 5",
"Grade 6","Grade 7","Grade 8","Grade 9","Grade 10"];

foreach($grades as $g){

echo '<div class="grade-section">';
echo '<div class="grade-title">'.$g.'</div>';
echo '<div class="subject-grid">';

$sql = "SELECT * FROM courses WHERE grade_level='$g' ORDER BY subject_name ASC";
$result = mysqli_query($conn,$sql);

while($row = mysqli_fetch_assoc($result)){
$s = $row['subject_name'];

echo '
<div class="subject-box" data-grade="'.$g.'" data-subject="'.$s.'">
<div class="subject-title">'.$s.'</div>

<input type="checkbox" class="subject-master"
name="subjects['.$g.']['.$s.'][selected]" value="1">

<div class="section-dropdown">
<label><input type="checkbox" class="section-check"
name="subjects['.$g.']['.$s.'][sections][]" value="A"> A</label>
<label><input type="checkbox" class="section-check"
name="subjects['.$g.']['.$s.'][sections][]" value="B"> B</label>
<label><input type="checkbox" class="section-check"
name="subjects['.$g.']['.$s.'][sections][]" value="C"> C</label>
</div>

</div>
';
}

echo '</div></div>';
}
?>

</fieldset>

<div class="form-actions">
<button type="submit" class="btn-save">Save Instructor</button>
</div>

<input type="hidden" name="id" id="instructorFormId">
<input type="hidden" name="subjects_json" id="subjects_json">

</form>

<button id="instructorSearchBtn" onclick="openInstructorSearch()" title="Search Instructor">
    <i class="fa fa-search"></i>
</button>

<div id="instructorSearchModal">
    <div id="instructorSearchBox">

        <div id="instructorSearchView" class="view-section active">
            <div class="modal-header-actions">
                <h3><i class="fa fa-search"></i> Search Instructor</h3>
                <button type="button" class="btn-secondary" onclick="showAllTeachers()">Show All Teachers</button>
            </div>

            <div class="search-input-wrap">
                <input type="text" id="instructorSearchInput" placeholder="Type employee number or name">
            </div>

            <div id="instructorSuggestBox"></div>

            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeInstructorSearch()">Close</button>
            </div>
        </div>

        <div id="instructorDetailsView" class="view-section">
            <div class="modal-header-actions">
                <h3><i class="fa fa-user"></i> Instructor Details</h3>
                <div>
                    <button type="button" class="btn-secondary" onclick="loadInstructorIntoForm()" style="margin-right: 8px;">Load To Form</button>
                    <button type="button" class="btn-primary" id="editAllInstructorBtn" onclick="toggleEditAllInstructors()" style="margin-right: 8px;">Edit</button>
                    <button type="button" class="btn-primary" id="exportInstructorPdfBtn" onclick="exportInstructorPdf()">Export Info</button>
                </div>
            </div>

            <input type="hidden" id="iid">
            <div id="instructorInfoBox"></div>
            <div id="instructorSubjectSummary" class="subject-summary"></div>
            <div id="instructorModalSubjectAssignments" class="modal-subject-assignments">
                <h4>Subject Assignment Per Grade</h4>
                <?php
                foreach($grades as $g){
                    echo '<div class="grade-section">';
                    echo '<div class="grade-title">'.$g.'</div>';
                    echo '<div class="subject-grid">';

                    $sql = "SELECT * FROM courses WHERE grade_level='$g' ORDER BY subject_name ASC";
                    $result = mysqli_query($conn,$sql);
                    while($row = mysqli_fetch_assoc($result)){
                        $s = $row['subject_name'];
                        echo '<div class="subject-box" data-grade="'.$g.'" data-subject="'.$s.'">';
                        echo '<div class="subject-title">'.$s.'</div>';
                        echo '<div class="section-dropdown">';
                        echo '<label><input type="checkbox" class="section-check" value="A"> A</label>';
                        echo '<label><input type="checkbox" class="section-check" value="B"> B</label>';
                        echo '<label><input type="checkbox" class="section-check" value="C"> C</label>';
                        echo '</div></div>';
                    }

                    echo '</div></div>';
                }
                ?>
            </div>

            <div class="modal-actions">
                <button class="btn-secondary" onclick="backToInstructorSearch()">Back</button>
                <button class="btn-danger" onclick="deleteInstructor()">Delete</button>
                <button class="btn-success" onclick="saveInstructorEdit()">Save Changes</button>
            </div>
        </div>

    </div>
</div>

</div>

<!-- TIME MODAL -->
<div id="timeModal">
<div id="timeModalBox">

<span id="closeTimeModal">×</span>

<h3>Assign Time Schedule</h3>

<div class="modal-group">
<label>Start Time</label>
<input type="time" id="startTime">
</div>

<div class="modal-group">
<label>End Time</label>
<input type="time" id="endTime">
</div>

<div class="modal-group">
<label>Days</label>
<div class="day-checkboxes">
    <label><input type="checkbox" name="scheduleDays" value="M"> M</label>
    <label><input type="checkbox" name="scheduleDays" value="T"> T</label>
    <label><input type="checkbox" name="scheduleDays" value="W"> W</label>
    <label><input type="checkbox" name="scheduleDays" value="Th"> Th</label>
    <label><input type="checkbox" name="scheduleDays" value="F"> F</label>
</div>
</div>

<button id="saveTimeBtn">Save</button>

</div>
</div>
<?php include "../includes/back_to_top.php"; ?>
<?php include "../includes/float.php"; ?>
<?php include "../includes/footer.php"; ?>
<script src="../assets/js/notifications.js"></script>
<script>

let selectedGrade, selectedSubject, selectedSection;

/* SUBJECT GREEN LOGIC */
function updateColor(box){
    let checked = box.querySelectorAll('.section-check:checked').length;
    if(checked > 0){
        box.classList.add('has-data','selected');
    } else {
        box.classList.remove('has-data','selected');
    }
}

function syncSectionCheckboxes(grade, subject, section, checked){
    document.querySelectorAll(`.subject-box[data-grade="${CSS.escape(grade)}"][data-subject="${CSS.escape(subject)}"] .section-check[value="${section}"]`).forEach(other => {
        other.checked = checked;
    });
    document.querySelectorAll(`.subject-box[data-grade="${CSS.escape(grade)}"][data-subject="${CSS.escape(subject)}"]`).forEach(updateColor);
}

function attachSubjectCheckboxListeners(){
    document.querySelectorAll('.section-check').forEach(cb => {
        cb.removeEventListener('change', sectionCheckboxChangeHandler);
        cb.addEventListener('change', sectionCheckboxChangeHandler);
    });
}

function sectionCheckboxChangeHandler(){
    let box = this.closest('.subject-box');
    selectedGrade = box.dataset.grade || this.name?.split('[')[1]?.split(']')[0] || '';
    selectedSubject = box.dataset.subject || box.querySelector('.subject-title').innerText.trim();
    selectedSection = this.value;
    let checked = this.checked;

    syncSectionCheckboxes(selectedGrade, selectedSubject, selectedSection, checked);

    if(checked){
        document.getElementById('timeModal').style.display='flex';
    } else {
        removeInstructorSubjectSection(selectedGrade, selectedSubject, selectedSection);
    }
}

attachSubjectCheckboxListeners();

/* CLOSE MODAL */
document.getElementById('closeTimeModal').onclick=function(){
document.getElementById('timeModal').style.display='none';
}

/* SAVE TIME */
document.getElementById('saveTimeBtn').onclick=function(){
    let start=document.getElementById('startTime').value;
    let end=document.getElementById('endTime').value;

    if(!start||!end){
        alert("Complete time");
        return;
    }

    let selectedDays = Array.from(document.querySelectorAll('input[name="scheduleDays"]:checked')).map(cb => cb.value);
    if(selectedDays.length === 0){
        alert('Select at least one teaching day.');
        return;
    }

    let input=document.getElementById('subjects_json');
    let data=input.value?JSON.parse(input.value):{};

    if(!data[selectedGrade])data[selectedGrade]={};
    if(!data[selectedGrade][selectedSubject])data[selectedGrade][selectedSubject]={sections:{}};

    data[selectedGrade][selectedSubject].sections[selectedSection]={
        start:start,
        end:end,
        days:selectedDays.join(' ')
    };

    input.value=JSON.stringify(data);
    renderInstructorSubjectSummary(data);
    document.getElementById('timeModal').style.display='none';
    document.getElementById('startTime').value = '';
    document.getElementById('endTime').value = '';
    document.querySelectorAll('input[name="scheduleDays"]').forEach(cb => cb.checked = false);
};

/* ✅ ENSURE JSON ALWAYS EXISTS */
function submitInstructorForm(){
    prepareSubjects();
    let id = document.getElementById('instructorFormId').value;
    if (!id) {
        return true;
    }

    let form = document.getElementById('instructorForm');
    let formData = new FormData(form);
    fetch('update_instructor.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Instructor updated successfully.');
            window.location.href = 'manage_instructors.php';
        } else {
            alert('Save Failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred while saving.');
    });

    return false;
}

function getSubjectsJson(){
    let input = document.getElementById('subjects_json');
    if(!input.value) return {};
    try {
        return JSON.parse(input.value);
    } catch(e){
        return {};
    }
}

function updateSubjectsJson(data){
    let input = document.getElementById('subjects_json');
    input.value = JSON.stringify(data);
}

function removeInstructorSubjectSection(grade, subject, section){
    let data = getSubjectsJson();
    if(!data[grade] || !data[grade][subject] || !data[grade][subject].sections) return;

    delete data[grade][subject].sections[section];
    if(Object.keys(data[grade][subject].sections).length === 0){
        delete data[grade][subject];
    }
    if(Object.keys(data[grade]).length === 0){
        delete data[grade];
    }

    updateSubjectsJson(data);
    renderInstructorSubjectSummary(data);
}

function resetSubjectAssignments(){
    ['#subjectAssignmentArea', '#instructorModalSubjectAssignments'].forEach(selector => {
        document.querySelectorAll(selector + ' .section-check').forEach(cb => cb.checked = false);
        document.querySelectorAll(selector + ' .subject-box').forEach(box => updateColor(box));
    });
    updateSubjectsJson({});
}

function applySubjectAssignments(data){
    resetSubjectAssignments();
    ['#subjectAssignmentArea', '#instructorModalSubjectAssignments'].forEach(selector => {
        applySubjectAssignmentsToContainer(selector, data);
    });
}

function applySubjectAssignmentsToContainer(selector, data){
    Object.keys(data).forEach(grade => {
        let gradeSections = Array.from(document.querySelectorAll(selector + ' .grade-section')).filter(section => {
            let title = section.querySelector('.grade-title');
            return title && title.innerText.trim() === grade;
        });
        if(!gradeSections.length) return;

        let subjects = data[grade];
        Object.keys(subjects).forEach(subject => {
            gradeSections.forEach(gradeSection => {
                let box = Array.from(gradeSection.querySelectorAll('.subject-box')).find(box => {
                    let titleElem = box.querySelector('.subject-title');
                    return titleElem && titleElem.innerText.trim() === subject;
                });
                if(!box) return;

                let sections = subjects[subject].sections || {};
                Object.keys(sections).forEach(sectionKey => {
                    let checkbox = box.querySelector(`.section-check[value="${sectionKey}"]`);
                    if(checkbox){
                        checkbox.checked = true;
                    }
                });
                updateColor(box);
            });
        });
    });
}

function renderInstructorSubjectSummary(data){
    let container = document.getElementById('instructorSubjectSummary');
    if(!container) return;

    let rows = [];
    Object.keys(data).forEach(grade => {
        let subjects = data[grade];
        Object.keys(subjects).forEach(subject => {
            let sections = subjects[subject].sections || {};
            let sectionKeys = Object.keys(sections);
            if (sectionKeys.length === 0) {
                rows.push(`<div class="summary-line"><strong>${grade} / ${subject}</strong>: Assigned</div>`);
                return;
            }
            sectionKeys.forEach(sectionKey => {
                let section = sections[sectionKey] || {};
                let days = section.days || 'M T W Th F';
                let start = section.start || '';
                let end = section.end || '';
                let timeLabel = start || end ? `${start}${start && end ? ' - ' : ''}${end}` : 'No time set';
                rows.push(`<div class="summary-line"><strong>${grade} / ${subject} - ${sectionKey}</strong>: ${days} | ${timeLabel}</div>`);
            });
        });
    });

    if(rows.length === 0){
        container.innerHTML = '<div class="summary-line"><strong>Subject Assignments</strong></div><div>No subject assignments found for this instructor.</div><div style="margin-top:10px;">Use the Subject Assignment Per Grade section below to assign subjects.</div>';
    } else {
        container.innerHTML = '<div class="summary-line"><strong>Subject Assignments</strong></div>' + rows.join('');
    }
}

function backToEditSubjectAssignments(){
    closeInstructorSearch();
    document.querySelector('input[name="employeeNumber"]').scrollIntoView({behavior:'smooth', block:'start'});
}

function exportInstructorPdf(){
    if(!window.currentInstructorData){
        alert('No instructor selected.');
        return;
    }

    const { jsPDF } = window.jspdf || {};
    if(!jsPDF){
        alert('PDF library not loaded.');
        return;
    }

    const doc = new jsPDF();
    const instructor = window.currentInstructorData;
    const subjects = getSubjectsJson();

    const logoSrc = '../assets/images/mucalogo.png';
    const addHeader = (imgData) => {
        if(imgData){
            doc.addImage(imgData, 'PNG', 15, 10, 25, 25);
        }
        doc.setFontSize(16);
        doc.text('Medicion Unida Christian Academy', 50, 20);
        doc.setFontSize(12);
        doc.text('Instructor Information and Schedule', 50, 28);
        doc.setLineWidth(0.5);
        doc.line(15, 34, 195, 34);
    };

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

    const buildEntries = () => {
        const entries = [];
        Object.keys(subjects).forEach(grade => {
            const gradeSubjects = subjects[grade] || {};
            Object.keys(gradeSubjects).forEach(subject => {
                const sections = gradeSubjects[subject].sections || {};
                const sectionEntries = Object.keys(sections).map(key => {
                    const section = sections[key] || {};
                    return {
                        key,
                        start: section.start || '',
                        end: section.end || '',
                        days: section.days || ''
                    };
                });
                const earliestStart = sectionEntries.reduce((min, s) => {
                    if(!s.start) return min;
                    if(min === null) return s.start;
                    return s.start < min ? s.start : min;
                }, null);
                entries.push({ grade, subject, sections: sectionEntries, earliestStart });
            });
        });
        entries.sort((a, b) => {
            if(!a.earliestStart) return 1;
            if(!b.earliestStart) return -1;
            return a.earliestStart.localeCompare(b.earliestStart);
        });
        return entries;
    };

    const drawDocument = (imgData) => {
        addHeader(imgData);
        let y = 42;
        const lineHeight = 7;
        const pageWidth = doc.internal.pageSize.getWidth();
        const marginLeft = 15;
        const marginRight = 15;
        const maxWidth = pageWidth - marginLeft - marginRight;

        const addLine = (label, value) => {
            doc.setFontSize(11);
            doc.text(`${label}:`, marginLeft, y);
            const valueLines = doc.splitTextToSize(String(value || ''), maxWidth - 45);
            doc.text(valueLines, marginLeft + 45, y);
            y += lineHeight * valueLines.length;
        };

        addLine('Employee #', instructor.employee_number || '');
        addLine('Name', `${instructor.first_name || ''} ${instructor.middle_name || ''} ${instructor.last_name || ''}`.trim());
        addLine('Email', instructor.email || '');
        addLine('Contact', instructor.contact_number || '');
        addLine('DOB', instructor.dob || '');
        addLine('Age', instructor.age || '');
        addLine('Address', instructor.address || '');
        addLine('Employment Date', instructor.date_employment || '');
        addLine('Employee Type', instructor.employee_type || '');
        addLine('Advisory', `${instructor.advisory_grade || ''} ${instructor.advisory_section || ''}`);
        y += lineHeight;
        doc.setFontSize(13);
        doc.text('Assigned Subjects & Schedule', marginLeft, y);
        y += lineHeight;

        doc.setFontSize(11);
        doc.text('Grade / Subject', marginLeft, y);
        doc.text('Days', 95, y);
        doc.text('Time', 145, y);
        y += lineHeight;
        doc.setLineWidth(0.3);
        doc.line(marginLeft, y-3, pageWidth - marginRight, y-3);

        const entries = buildEntries();
        if(entries.length === 0){
            y += lineHeight;
            doc.text('No assigned subjects found.', marginLeft, y);
        }

        entries.forEach(entry => {
            if(y > 270){
                doc.addPage();
                y = 20;
            }
            const sectionLabels = entry.sections.map(s => s.key).join(', ');
            const times = entry.sections.map(s => {
                const start = formatTime12(s.start);
                const end = formatTime12(s.end);
                return s.key + (start || end ? `: ${start}-${end}` : '');
            }).join('; ');
            const days = [...new Set(entry.sections.map(s => s.days && s.days.trim() ? s.days : 'M T W Th F').filter(Boolean))].join(', ');
            const subjectText = `${entry.grade} / ${entry.subject}`;
            const timeText = times || sectionLabels || 'N/A';
            const subjectLines = doc.splitTextToSize(subjectText, 70);
            const daysLines = doc.splitTextToSize(days || 'M T W Th F', 35);
            const timeLines = doc.splitTextToSize(timeText, 45);
            const maxLines = Math.max(subjectLines.length, daysLines.length, timeLines.length);

            for(let i = 0; i < maxLines; i++){
                if(y > 270){
                    doc.addPage();
                    y = 20;
                }
                doc.text(subjectLines[i] || '', marginLeft, y);
                doc.text(daysLines[i] || '', 95, y);
                doc.text(timeLines[i] || '', 145, y);
                y += lineHeight;
            }
        });

        doc.save(`${instructor.employee_number || 'instructor'}_info.pdf`);
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

function prepareSubjects(){
    let input=document.getElementById('subjects_json');
    if(!input.value){
        input.value="{}";
    }
    return true;
}

/* ===== INSTRUCTOR SEARCH ===== */

function openInstructorSearch(){
    document.getElementById('instructorSearchModal').style.display = 'flex';
    document.getElementById('instructorSearchInput').focus();
}

function closeInstructorSearch(){
    document.getElementById('instructorSearchModal').style.display = 'none';
    document.getElementById('instructorSearchView').classList.add('active');
    document.getElementById('instructorDetailsView').classList.remove('active');
    document.getElementById('instructorSearchInput').value = '';
    document.getElementById('instructorSuggestBox').innerHTML = '';
}

function showInstructorDetails(){
    document.getElementById('instructorSearchView').classList.remove('active');
    document.getElementById('instructorDetailsView').classList.add('active');
}

function backToInstructorSearch(){
    document.getElementById('instructorSearchView').classList.add('active');
    document.getElementById('instructorDetailsView').classList.remove('active');
}

function showAllTeachers(){
    fetch('list_teachers.php')
    .then(res => res.json())
    .then(data => {
        let html = '';
        if(data.length > 0){
            data.forEach(t => {
                html += `
                    <div class="suggest-item" onclick="loadInstructor(${t.id})">
                        <span><b>${t.employee_number}</b> - ${t.first_name} ${t.last_name} <br><small>${t.email || 'No email'}</small></span>
                        <i class="fa fa-chevron-right" style="color:#999;"></i>
                    </div>`;
            });
        } else {
            html = `<div class="suggest-item" style="cursor:default;">No teachers found</div>`;
        }
        document.getElementById('instructorSuggestBox').innerHTML = html;
    })
    .catch(() => {
        document.getElementById('instructorSuggestBox').innerHTML = `<div class="suggest-item" style="cursor:default;">Unable to load teacher list</div>`;
    });
}

const instructorEmail = document.getElementById('instructorEmail');
const instructorPasswordDisplay = document.getElementById('instructorPasswordDisplay');
const instructorPasswordHidden = document.getElementById('instructorPassword');
const instructorIdField = document.getElementById('instructorFormId');

instructorEmail.addEventListener('input', () => {
    let value = instructorEmail.value.trim();
    if (value && value.indexOf('@') === -1) {
        value = value + '@mucahub.com';
    }
    instructorEmail.value = value;

    const isCreating = instructorIdField.value === '';
    if (isCreating) {
        instructorPasswordDisplay.value = value;
        instructorPasswordHidden.value = value;
    }
});

document.getElementById('instructorSearchInput').addEventListener('keydown', function(e){
    if(e.key === 'Enter'){
        triggerInstructorSearch();
    }
});

function triggerInstructorSearch(){
    let q = document.getElementById('instructorSearchInput').value.trim();
    if(q === ''){
        document.getElementById('instructorSuggestBox').innerHTML = '';
        return;
    }

    fetch('search_instructor.php?q=' + encodeURIComponent(q))
    .then(res => res.json())
    .then(data => {
        let html = '';
        if(data.length > 0){
            data.forEach(i => {
                html += `
                    <div class="suggest-item" onclick="loadInstructor(${i.id})">
                        <span><b>${i.employee_number}</b> - ${i.first_name} ${i.last_name}</span>
                        <i class="fa fa-chevron-right" style="color:#999;"></i>
                    </div>`;
            });
        } else {
            html = `<div class="suggest-item" style="cursor:default;">No matching instructor found</div>`;
        }
        document.getElementById('instructorSuggestBox').innerHTML = html;
    });
}

function loadInstructor(id){
    fetch('get_instructor.php?id=' + id)
    .then(res => res.json())
    .then(i => {
        document.getElementById('iid').value = i.id;
        window.currentInstructorData = i;

        let subjectData = {};
        try {
            subjectData = i.subjects ? JSON.parse(i.subjects) : {};
        } catch(e) {
            subjectData = {};
        }
        applySubjectAssignments(subjectData);
        updateSubjectsJson(subjectData);
        renderInstructorSubjectSummary(subjectData);

        let html = '';
        for(let key in i){
            if(key === 'id' || key === 'subjects' || key === 'last_modified') continue;
            let displayValue = i[key] ?? '';
            let safeValue = displayValue.toString().replace(/'/g, "\\'");
            html += `
                <div class="field-row editable-field" data-key="${key}">
                    <span class="field-label">${key.replace(/_/g, ' ')}</span>
                    <span class="field-value" id="val-${key}">${displayValue}</span>
                </div>`;
        }

        html += `
            <div class="field-row editable-field" data-key="password">
                <span class="field-label">Password</span>
                <span class="field-value" id="val-password">********</span>
            </div>`;

        document.getElementById('instructorInfoBox').innerHTML = html;
        showInstructorDetails();
    });
}

function loadInstructorIntoForm(){
    if (!window.currentInstructorData || !window.currentInstructorData.id) {
        alert('No instructor data loaded.');
        return;
    }

    const instructor = window.currentInstructorData;
    const fieldMap = {
        employee_number: 'employeeNumber',
        first_name: 'firstName',
        middle_name: 'middleName',
        last_name: 'lastName',
        dob: 'dob',
        age: 'age',
        address: 'address',
        email: 'email',
        contact_number: 'contactNumber',
        date_employment: 'dateEmployment',
        employee_type: 'employeeType',
        advisory_grade: 'advisoryGrade',
        advisory_section: 'advisorySection'
    };

    Object.keys(fieldMap).forEach(key => {
        let el = document.querySelector(`[name="${fieldMap[key]}"]`);
        if (!el) return;
        el.value = instructor[key] || '';
    });

    document.getElementById('instructorFormId').value = instructor.id;
    instructorPasswordDisplay.value = '********';
    instructorPasswordHidden.value = '';

    let subjectData = {};
    try {
        subjectData = instructor.subjects ? JSON.parse(instructor.subjects) : {};
    } catch (e) {
        subjectData = {};
    }
    updateSubjectsJson(subjectData);
    applySubjectAssignments(subjectData);
    renderInstructorSubjectSummary(subjectData);

    closeInstructorSearch();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

let isInstructorEditingAll = false;

function toggleEditAllInstructors(){
    isInstructorEditingAll = !isInstructorEditingAll;
    let btn = document.getElementById('editAllInstructorBtn');
    
    if(isInstructorEditingAll){
        // Enable edit mode for all editable fields
        document.querySelectorAll('#instructorInfoBox .field-row.editable-field').forEach(row => {
            let key = row.getAttribute('data-key');
            let span = row.querySelector('.field-value');
            if(!span) return;
            
            let currentValue = span.innerText;
            if(key === 'password') currentValue = '';
            
            let input;
            if(key === 'dob' || key === 'date_employment'){
                input = document.createElement('input');
                input.type = 'date';
            } else if(key === 'employee_type'){
                input = document.createElement('select');
                ['Regular','Trainee','Part-Time'].forEach(optText => {
                    let opt = document.createElement('option');
                    opt.value = optText;
                    opt.innerText = optText;
                    if(optText === currentValue) opt.selected = true;
                    input.appendChild(opt);
                });
            } else {
                input = document.createElement('input');
                input.type = 'text';
            }
            input.id = 'input-' + key;
            input.value = currentValue;
            input.className = 'form-control';
            span.replaceWith(input);
        });
        btn.innerText = 'Done Editing';
    } else {
        // Disable edit mode for all fields
        document.querySelectorAll('#instructorInfoBox .field-row.editable-field').forEach(row => {
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

function editInstructorField(key, currentValue){
    let container = document.querySelector(`.field-row[data-key="${key}"]`);
    if(!container) return;
    let existing = container.querySelector('.field-value');
    if(!existing) return;

    let input;
    if(key === 'dob' || key === 'date_employment'){
        input = document.createElement('input');
        input.type = 'date';
    } else if(key === 'employee_type'){
        input = document.createElement('select');
        ['Regular','Trainee','Part-Time'].forEach(optText => {
            let opt = document.createElement('option');
            opt.value = optText;
            opt.innerText = optText;
            if(optText === currentValue) opt.selected = true;
            input.appendChild(opt);
        });
    } else if(key === 'address'){
        input = document.createElement('textarea');
        input.rows = 3;
    } else if(key === 'password'){
        input = document.createElement('input');
        input.type = 'password';
    } else {
        input = document.createElement('input');
        input.type = 'text';
    }

    input.value = currentValue;
    input.id = 'input-' + key;
    input.style.flex = '1';

    existing.replaceWith(input);
    input.focus();

    let btn = container.querySelector('.edit-btn');
    if(btn){
        btn.innerText = 'Done';
        btn.onclick = function(){ finishInstructorEdit(key); };
    }
}

function finishInstructorEdit(key){
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
        btn.onclick = function(){ editInstructorField(key, safeValue); };
    }
}

function saveInstructorEdit(){
    let id = document.getElementById('iid').value;
    if(!id){
        alert('No instructor selected.');
        return;
    }

    let form = new FormData();
    form.append('id', id);

    document.querySelectorAll('#instructorInfoBox .field-row').forEach(row => {
        let key = row.getAttribute('data-key');
        if(!key) return;
        let val = '';
        let input = row.querySelector('input, select, textarea');
        if(input){
            val = input.value;
        } else {
            let span = row.querySelector('.field-value');
            if(span) val = span.innerText;
        }

        if(key === 'password'){
            if(!input){
                return;
            }
            if(!val || val === '********'){
                return;
            }
        }

        form.append(key, val);
    });

    let subjectsJson = document.getElementById('subjects_json').value || '{}';
    form.append('subjects_json', subjectsJson);

    fetch('update_instructor.php', {
        method: 'POST',
        body: form
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            alert('Saved Successfully');
        } else {
            alert('Save Failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred while saving.');
    });
}

function deleteInstructor(){
    let id = document.getElementById('iid').value;
    if(!id){
        alert('No instructor selected.');
        return;
    }

    if(!confirm('Are you sure you want to delete this instructor? This cannot be undone.')){
        return;
    }

    fetch('delete_instructor.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id)
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            alert('Instructor deleted successfully.');
            closeInstructorSearch();
        } else {
            alert('Delete failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred while deleting.');
    });
}

</script>

</body>
</html>