<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/databases/db_connection.php';
?>
<!DOCTYPE html>
<html>
<head>
<title>MUCAHUB Dashboard - Manage Courses</title>

<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.main .toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 18px;
    align-items: center;
}

.main .toolbar input {
    flex: 1;
    min-width: 220px;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

.grade-section{
border:1px solid #ddd;
border-radius:8px;
padding:12px;
margin-bottom:15px;
background:#fafafa;
}
.grade-header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:10px;
font-weight:bold;
}
.subject-grid{
display:flex;
flex-wrap:wrap;
gap:8px;
}
.subject-box{
padding:8px 14px;
border:1px solid #ccc;
border-radius:6px;
background:#f5f5f5;
display:flex;
align-items:center;
gap:10px;
min-width:180px;
justify-content:space-between;
}
.subject-box.selected{
background:#1E3A8A;
color:white;
border-color:#1E3A8A;
}
.remove-btn,
.edit-btn,
.add-btn{
background:#556B2F;
color:white;
border:none;
border-radius:4px;
padding:6px 10px;
cursor:pointer;
}
.remove-btn{
background:#B22222;
}
.edit-btn {
background:#1E40AF;
}
.add-btn:hover,
.edit-btn:hover,
.remove-btn:hover{
opacity:0.92;
}
.modal{
display:none;
position:fixed;
top:0;left:0;
width:100%;height:100%;
background:rgba(0,0,0,0.4);
justify-content:center;
align-items:center;
}
.modal-box{
background:white;
padding:24px;
border-radius:12px;
width:360px;
max-width:95%;
box-shadow:0 20px 60px rgba(0,0,0,0.2);
}
.modal-box h3{
margin-top:0;
color:#1E3A8A;
}
.modal-box input,
.modal-box select{
width:100%;
padding:10px 12px;
border-radius:8px;
border:1px solid #ccc;
margin-top:10px;
}
.modal-actions{
margin-top:16px;
display:flex;
gap:10px;
justify-content:flex-end;
}
.modal-actions button{
min-width:90px;
}
.no-data{
color:#555;
font-style:italic;
}
</style>
</head>

<body>

<?php include "../includes/sidebar_admin.php"; ?>
<?php include "../includes/back_to_top.php"; ?>

<div class="main">
<h2>Manage Courses</h2>
<div class="toolbar">
    <input id="courseSearch" type="text" placeholder="Search subjects or grades" oninput="filterCourses()">
    <button class="add-btn" onclick="openModal('')"><i class="fa fa-plus"></i> Add Subject</button>
</div>

<?php
$grades = ["Kinder","Grade 1","Grade 2","Grade 3","Grade 4","Grade 5",
"Grade 6","Grade 7","Grade 8","Grade 9","Grade 10"];

$courseData = [];
$result = mysqli_query($conn, "SELECT * FROM courses ORDER BY FIELD(grade_level, 'Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'), subject_name ASC");
while ($row = mysqli_fetch_assoc($result)) {
    $courseData[$row['grade_level']][] = $row;
}

foreach ($grades as $grade) {
    $items = isset($courseData[$grade]) ? $courseData[$grade] : [];
    echo '<div class="grade-section" data-grade="'.htmlspecialchars($grade).'">';
    echo '<div class="grade-header">';
    echo '<span>'.$grade.' <small>('.count($items).' subject'.(count($items) === 1 ? '' : 's').')</small></span>';
    echo '<button class="add-btn" onclick="openManageModal(\''.addslashes($grade).'\')"><i class="fa fa-cogs"></i> Manage Subjects</button>';
    echo '</div>';
    echo '<div class="subject-grid">';

    if (count($items) === 0) {
        echo '<div class="no-data">No subjects assigned yet.</div>';
    }

    foreach ($items as $row) {
        echo '<div class="subject-box" data-subject="'.htmlspecialchars($row['subject_name']).'">';
        echo '<span>'.htmlspecialchars($row['subject_name']).'</span>';
        echo '<div>';
        echo '<button type="button" class="edit-btn" onclick="openModal(\''.addslashes($grade).'\', '.intval($row['id']).', \''.addslashes(htmlspecialchars($row['subject_name'], ENT_QUOTES)).'\')"><i class="fa fa-edit"></i></button>';
        echo '<button type="button" class="remove-btn" onclick="removeSubject('.intval($row['id']).')"><i class="fa fa-trash"></i></button>';
        echo '</div>';
        echo '</div>';
    }

    echo '</div></div>';
}
?>

</div>

<!-- MODAL -->
<div class="modal" id="modal">
<div class="modal-box">
<h3 id="modalTitle">Add Subject</h3>
<label for="subjectSelect" style="font-weight:600;margin-top:6px;display:block;color:#333;">Select Subject</label>
<select id="subjectSelect">
    <option value="">-- Select subject --</option>
</select>
<div style="margin-top:8px;margin-bottom:6px;">
    <label style="font-size:13px;"><input type="checkbox" id="enableCustomSubject"> Add custom subject</label>
</div>
<input type="text" id="subjectName" placeholder="Enter custom subject" style="display:none;">
<select id="gradeLevel">
    <?php foreach ($grades as $gradeOption) { echo '<option value="'.htmlspecialchars($gradeOption).'">'.htmlspecialchars($gradeOption).'</option>'; } ?>
</select>
<input type="hidden" id="subjectId" value="0">
<div class="modal-actions">
    <button onclick="saveSubject()" class="add-btn">Save</button>
    <button onclick="closeModal()" class="remove-btn">Cancel</button>
</div>
</div>

<!-- MANAGE GRADE SUBJECTS MODAL -->
<div class="modal" id="manageModal">
<div class="modal-box">
<h3 id="manageModalTitle">Manage Subjects for Grade</h3>
<div id="subjectCheckboxList" style="max-height:320px;overflow:auto;margin-top:8px;border:1px solid #eee;padding:10px;border-radius:8px;background:#fafafa;"></div>
<div style="margin-top:10px;display:flex;gap:8px;align-items:center;">
    <input type="text" id="newCustomSubject" placeholder="Add custom subject" style="flex:1;">
    <button type="button" id="addCustomBtn" class="add-btn">Add</button>
    <button type="button" id="clearAllBtn" class="remove-btn">Clear</button>
</div>
<input type="hidden" id="manageGrade" value="">
<div class="modal-actions">
    <button onclick="saveManageSubjects()" class="add-btn">Save Subjects</button>
    <button onclick="closeManageModal()" class="remove-btn">Cancel</button>
    <button onclick="removeAllSubjectsForGrade()" class="remove-btn" style="background:#B22222;">Remove All</button>
    </div>
    </div>
    </div>
</div>
<?php include "../includes/float.php"; ?>
<?php include "../includes/footer.php"; ?>
<script src="../assets/js/notifications.js"></script>
<script>
function openModal(grade, id = 0, subject = '') {
    document.getElementById('modal').style.display = 'flex';
    document.getElementById('modalTitle').innerText = id ? 'Edit Subject' : 'Add Subject';
    // If subject exists in catalog, select it; otherwise enable custom input
    populateSubjectCatalog().then(() => {
        const sel = document.getElementById('subjectSelect');
        const customChk = document.getElementById('enableCustomSubject');
        const customInput = document.getElementById('subjectName');
        customInput.style.display = 'none';
        customChk.checked = false;
        if (subject) {
            let found = Array.from(sel.options).some(o => o.value === subject);
            if (found) {
                sel.value = subject;
            } else {
                sel.value = '';
                customChk.checked = true;
                customInput.style.display = 'block';
                customInput.value = subject;
            }
        } else {
            sel.value = '';
            customInput.value = '';
        }
    });
    document.getElementById('gradeLevel').value = grade || 'Kinder';
    document.getElementById('subjectId').value = id;
    document.getElementById('subjectName').focus();
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
    document.getElementById('subjectName').value = '';
    document.getElementById('subjectId').value = '0';
}

function saveSubject() {
    const useCustom = document.getElementById('enableCustomSubject').checked;
    let subject = useCustom ? document.getElementById('subjectName').value.trim() : document.getElementById('subjectSelect').value.trim();
    let grade = document.getElementById('gradeLevel').value;
    let id = parseInt(document.getElementById('subjectId').value, 10) || 0;

    if (!subject) {
        return alert('Enter subject');
    }

    let body = 'subject=' + encodeURIComponent(subject) + '&grade=' + encodeURIComponent(grade);
    if (id) {
        body += '&subject_id=' + encodeURIComponent(id);
    }

    fetch('save_course.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Unable to save course.');
        }
    })
    .catch(() => alert('Unable to save course.'));
}

function removeSubject(id) {
    if (!confirm('Remove this subject?')) return;

    fetch('remove_course.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Unable to remove subject.');
        }
    })
    .catch(() => alert('Unable to remove subject.'));
}

function filterCourses() {
    let term = document.getElementById('courseSearch').value.trim().toLowerCase();
    document.querySelectorAll('.grade-section').forEach(section => {
        let grade = section.getAttribute('data-grade').toLowerCase();
        let matchesGrade = grade.includes(term);
        let subjectBoxes = Array.from(section.querySelectorAll('.subject-box'));
        let anyVisible = false;

        subjectBoxes.forEach(box => {
            let subject = box.getAttribute('data-subject').toLowerCase();
            let visible = !term || matchesGrade || subject.includes(term);
            box.style.display = visible ? 'flex' : 'none';
            if (visible) anyVisible = true;
        });

        let noData = section.querySelector('.no-data');
        if (term && !anyVisible) {
            if (!noData) {
                let placeholder = document.createElement('div');
                placeholder.className = 'no-data';
                placeholder.innerText = 'No subjects match this filter.';
                section.querySelector('.subject-grid').appendChild(placeholder);
            }
        } else if (noData) {
            noData.remove();
        }
    });
}

// Populate the subject select from catalog endpoint
function populateSubjectCatalog(){
    return fetch('subject_catalog.php')
    .then(res => res.json())
    .then(list => {
        const sel = document.getElementById('subjectSelect');
        sel.innerHTML = '<option value="">-- Select subject --</option>';
        list.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s;
            opt.innerText = s;
            sel.appendChild(opt);
        });
    })
    .catch(() => {
        // fallback to a few defaults
        const defaults = ['English','Mathematics','Science','Filipino'];
        const sel = document.getElementById('subjectSelect');
        sel.innerHTML = '<option value="">-- Select subject --</option>';
        defaults.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s; opt.innerText = s; sel.appendChild(opt);
        });
    });
}

document.getElementById('enableCustomSubject').addEventListener('change', function(){
    const show = this.checked;
    document.getElementById('subjectName').style.display = show ? 'block' : 'none';
    if(!show){ document.getElementById('subjectName').value = ''; }
});

// Preload catalog on page load
populateSubjectCatalog();

// Manage modal functions
function openManageModal(grade){
    document.getElementById('manageModal').style.display = 'flex';
    document.getElementById('manageModalTitle').innerText = 'Manage Subjects for ' + grade;
    document.getElementById('manageGrade').value = grade;
    // build list
    populateSubjectCatalog().then(() => {
        const list = document.getElementById('subjectCheckboxList');
        list.innerHTML = '';
        // existing subjects in this grade
        const existing = Array.from(document.querySelectorAll('.grade-section[data-grade="'+CSS.escape(grade)+'"] .subject-box')).map(b => b.getAttribute('data-subject'));
        fetch('subject_catalog.php').then(r=>r.json()).then(catalog=>{
            catalog.forEach(s => {
                const id = 'chk_' + s.replace(/[^a-z0-9]/ig,'_');
                const div = document.createElement('div');
                div.style.padding = '6px 4px';
                div.innerHTML = `<label style="cursor:pointer;"><input type="checkbox" class="catalog-check" value="${s}" id="${id}" ${existing.includes(s)?'checked':''}> ${s}</label>`;
                list.appendChild(div);
            });
        }).catch(()=>{
            // fallback
            ['English','Mathematics','Science','Filipino'].forEach(s=>{
                const id = 'chk_' + s.replace(/[^a-z0-9]/ig,'_');
                const div = document.createElement('div');
                div.style.padding = '6px 4px';
                div.innerHTML = `<label style="cursor:pointer;"><input type="checkbox" class="catalog-check" value="${s}" id="${id}" ${existing.includes(s)?'checked':''}> ${s}</label>`;
                list.appendChild(div);
            });
        });
    });
}

function closeManageModal(){
    document.getElementById('manageModal').style.display = 'none';
    document.getElementById('subjectCheckboxList').innerHTML = '';
}

document.getElementById('addCustomBtn').addEventListener('click', ()=>{
    const v = document.getElementById('newCustomSubject').value.trim();
    if(!v) return;
    const list = document.getElementById('subjectCheckboxList');
    const id = 'chk_' + v.replace(/[^a-z0-9]/ig,'_');
    if(document.getElementById(id)) return; // already exists
    const div = document.createElement('div');
    div.style.padding = '6px 4px';
    div.innerHTML = `<label style="cursor:pointer;"><input type="checkbox" class="catalog-check" value="${v}" id="${id}" checked> ${v}</label>`;
    list.prepend(div);
    document.getElementById('newCustomSubject').value = '';
});

document.getElementById('clearAllBtn').addEventListener('click', ()=>{
    document.querySelectorAll('#subjectCheckboxList .catalog-check').forEach(cb=>{ cb.checked = false; });
});

function saveManageSubjects(){
    const grade = document.getElementById('manageGrade').value;
    const checked = Array.from(document.querySelectorAll('#subjectCheckboxList .catalog-check:checked')).map(cb => cb.value.trim()).filter(Boolean);
    // send batch
    const form = new URLSearchParams();
    checked.forEach(s=> form.append('subjects[]', s));
    form.append('grade', grade);

    fetch('save_course.php',{
        method:'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: form.toString()
    }).then(r=>r.json()).then(data=>{
        if(data.success){ location.reload(); } else { alert(data.message || 'Failed to save subjects'); }
    }).catch(()=> alert('Network error saving subjects'));
}

function removeAllSubjectsForGrade(){
    if(!confirm('Remove all subjects for this grade?')) return;
    const grade = document.getElementById('manageGrade').value;
    const form = new URLSearchParams();
    // send an empty subjects[] to indicate delete-all
    form.append('subjects[]', '');
    form.append('grade', grade);
    fetch('save_course.php',{
        method:'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: form.toString()
    }).then(r=>r.json()).then(data=>{
        if(data.success){ location.reload(); } else { alert(data.message || 'Failed to remove subjects'); }
    }).catch(()=> alert('Network error'));
}
</script>

</body>
</html>