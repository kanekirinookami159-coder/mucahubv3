<?php
include_once "../config/database.php";
$instructorId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
?>
<style>
.sidebar {
  position: fixed;
  left: 0;
  top: 0;
  width: 240px;
  height: 100vh;
  background: rgba(17, 24, 39, 0.95);
  color: white;
  padding-top: 20px;
  padding-bottom: 120px;
  z-index: 1000;
}
.sidebar .logo{
display:flex;
align-items:center;
padding:12px;
font-size:24px;
font-weight:bold;
color:white;
gap:10px;
}

.sidebar .logo img{
height:40.5px;
width:auto;
}

.sidebar ul {
  list-style: none;
  margin: 0;
  padding: 0;
}

.sidebar ul a {
  text-decoration: none;
  color: white;
}

.sidebar ul li {
  padding: 18px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  transition: background 0.2s ease;
}

.sidebar ul li:hover {
  background: rgba(255, 255, 255, 0.08);
}

.sidebar-footer {
  position: absolute;
  bottom: 20px;
  width: 100%;
  padding: 0 20px;
}

.sidebar-footer button {
  width: 100%;
  padding: 14px 18px;
  border: none;
  border-radius: 12px;
  background: #34480f;
  color: white;
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.sidebar-footer button:hover {
  background: #2b3e0c;
}

.sidebar-footer .btn-logout {
  margin-top: 12px;
  background: #c82333;
}

.sidebar-footer .btn-logout:hover {
  background: #a71d2b;
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(10, 18, 34, 0.9);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
}

.modal-overlay.active {
  display: flex;
}

.modal-panel {
  width: 100%;
  max-width: 900px;
  max-height: 95vh;
  overflow-y: auto;
  background: rgba(15, 23, 42, 0.98);
  border-radius: 24px;
  border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 40px 120px rgba(0,0,0,0.45);
  padding: 32px;
  color: #f8fafc;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
}

.profile-modal-header {
  background: #556b2f;
  padding: 18px 20px;
  border-radius: 18px;
}

.profile-modal-header h3,
.profile-modal-header p {
  color: #f8fafc;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.6rem;
}

.modal-close {
  background: transparent;
  border: none;
  color: #c82333;
  font-size: 1.4rem;
  cursor: pointer;
}

.profile-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px 20px;
}

.profile-grid .form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.profile-grid label {
  font-size: 0.95rem;
  color: #cbd5e1;
}

.profile-grid input,
.profile-grid textarea {
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 10px;
  padding: 12px 14px;
  background: rgba(15, 23, 42, 0.95);
  color: #f8fafc;
  font-size: 0.95rem;
}

.profile-grid textarea {
  min-height: 100px;
  resize: vertical;
}

.profile-grid input[readonly],
.profile-grid textarea[readonly] {
  opacity: 0.8;
  cursor: not-allowed;
}

.profile-actions {
  margin-top: 28px;
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
}
.logout-confirm-modal {
  position: fixed;
  inset: 0;
  background: rgba(10, 18, 34, 0.88);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 24px;
}

.logout-confirm-modal.active {
  display: flex;
}

.logout-confirm-card {
  width: min(460px, 100%);
  background: #0f172a;
  border-radius: 24px;
  padding: 28px 26px;
  border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 30px 90px rgba(0,0,0,0.28);
  text-align: center;
}

.logout-confirm-card .icon {
  font-size: 42px;
  color: #f87171;
  margin-bottom: 16px;
}

.logout-confirm-card h2 {
  margin: 0;
  font-size: 1.6rem;
  color: #f8fafc;
}

.logout-confirm-card p {
  margin: 16px 0 24px;
  color: rgba(255,255,255,0.78);
  line-height: 1.7;
}

.logout-confirm-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
}

.logout-confirm-actions button {
  border: none;
  border-radius: 14px;
  padding: 12px 18px;
  font-size: 0.95rem;
  cursor: pointer;
  transition: transform 0.2s ease, background 0.2s ease;
}

.logout-confirm-cancel {
  background: rgba(255,255,255,0.08);
  color: #f8fafc;
}

.logout-confirm-accept {
  background: #dc2626;
  color: #fff;
}

.logout-confirm-actions button:hover {
  transform: translateY(-1px);
}

.logout-confirm-cancel:hover {
  background: rgba(255,255,255,0.14);
}

.logout-confirm-accept:hover {
  background: #b91c1c;
}
.profile-actions button {
  border: none;
  border-radius: 12px;
  padding: 14px 22px;
  cursor: pointer;
  font-size: 0.95rem;
}

.btn-save-profile {
  background: linear-gradient(135deg, #556b2f, #3d5919);
  color: white;
}

.btn-export-profile {
  background: rgba(255,255,255,0.08);
  color: #f8fafc;
  border: 1px solid rgba(255,255,255,0.14);
}

.profile-summary {
  margin-top: 24px;
  padding: 20px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 16px;
}

.profile-summary h4 {
  margin: 0 0 14px;
  font-size: 1rem;
}

.profile-summary ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.profile-summary li {
  display: flex;
  justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid rgba(255,255,255,0.08);
}

.profile-summary li:last-child {
  border-bottom: none;
}

.profile-summary .field-label {
  color: #cbd5e1;
}

.profile-summary .field-value {
  color: #f8fafc;
  text-align: right;
}

@media (max-width: 860px) {
  .profile-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="sidebar">

  <div class="logo">
    <img src="../assets/images/mucalogo.png" alt="MUCAHUB Logo">
    MUCAHUB
  </div>

  <ul>
    <a href="../instructor/instructor_dashboard.php">
      <li><i class="fa fa-tachometer-alt"></i> Dashboard</li>
    </a>

    <a href="../instructor/home.php">
      <li><i class="fa fa-bullhorn"></i> Announcements</li>
    </a>

    <a href="../instructor/my_class.php">
      <li><i class="fa fa-chalkboard"></i> My Class</li>
    </a>

    <a href="../instructor/grades.php">
      <li><i class="fa fa-pen-to-square"></i> Grading</li>
    </a>
  </ul>

  <div class="sidebar-footer">
    <button id="openInstructorProfileBtn"><i class="fa fa-user"></i> Profile</button>
    <button id="logoutBtn" class="btn-logout"><i class="fa fa-sign-out-alt"></i> Logout</button>
  </div>

</div>

<div id="instructorProfileModal" class="modal-overlay">
  <div class="modal-panel">
    <div class="modal-header profile-modal-header">
      <div>
        <h3>Instructor Profile</h3>
        <p style="color:#f8fafc; margin-top:6px;">View and update your profile details.</p>
      </div>
      <button class="modal-close" id="closeInstructorProfileBtn" title="Close">&times;</button>
    </div>

    <form id="instructorProfileForm">
      <input type="hidden" name="id" id="profileInstructorId" value="">
      <div class="profile-grid">
        <div class="form-group">
          <label>Employee Number</label>
          <input type="text" id="profileEmployeeNumber" readonly>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" id="profileEmail" required>
        </div>
        <div class="form-group">
          <label>First Name</label>
          <input type="text" name="firstName" id="profileFirstName" required>
        </div>
        <div class="form-group">
          <label>Middle Name</label>
          <input type="text" name="middleName" id="profileMiddleName">
        </div>
        <div class="form-group">
          <label>Last Name</label>
          <input type="text" name="lastName" id="profileLastName" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" id="profilePassword" placeholder="Leave blank to keep current password">
        </div>
        <div class="form-group">
          <label>Date of Birth</label>
          <input type="date" name="dob" id="profileDob">
        </div>
        <div class="form-group">
          <label>Age</label>
          <input type="number" name="age" id="profileAge">
        </div>
        <div class="form-group">
          <label>Contact Number</label>
          <input type="text" name="contactNumber" id="profileContactNumber">
        </div>
        <div class="form-group">
          <label>Address</label>
          <textarea name="address" id="profileAddress"></textarea>
        </div>
        <div class="form-group">
          <label>Employee Type</label>
          <input type="text" id="profileEmployeeType" readonly>
        </div>
        <div class="form-group">
          <label>Employment Date</label>
          <input type="date" id="profileEmploymentDate" readonly>
        </div>
        <div class="form-group">
          <label>Advisory Grade</label>
          <input type="text" id="profileAdvisoryGrade" readonly>
        </div>
        <div class="form-group">
          <label>Advisory Section</label>
          <input type="text" id="profileAdvisorySection" readonly>
        </div>
      </div>

      <div class="profile-summary" id="profileSubjectSummary">
        <h4>Assigned Subjects</h4>
        <ul id="profileSubjectsList"></ul>
      </div>

      <div class="profile-actions">
        <button type="button" class="btn-export-profile" id="exportInstructorProfileBtn">Export Subjects</button>
        <button type="button" class="btn-save-profile" id="saveInstructorProfileBtn">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<div id="logoutConfirmModal" class="logout-confirm-modal">
  <div class="logout-confirm-card">
    <div class="icon"><i class="fa fa-sign-out-alt"></i></div>
    <h2>Confirm Logout</h2>
    <p>You're about to sign out of MUCAHUB. Any unsaved changes will be lost. Do you want to continue?</p>
    <div class="logout-confirm-actions">
      <button type="button" id="cancelLogoutBtn" class="logout-confirm-cancel">Cancel</button>
      <button type="button" id="confirmLogoutBtn" class="logout-confirm-accept">Log Out</button>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
const instructorProfileId = <?= $instructorId ?>;
let profileSubjectsData = {};
let profileSubjectsList = null;

function openInstructorProfile() {
  if (!instructorProfileId) {
    alert('Instructor profile not available.');
    return;
  }

  fetch(`../admin/get_instructor.php?id=${instructorProfileId}`)
    .then(res => res.json())
    .then(data => {
      if (!data || !data.id) {
        throw new Error('Unable to load profile data.');
      }

      document.getElementById('profileInstructorId').value = data.id;
      document.getElementById('profileEmployeeNumber').value = data.employee_number || '';
      document.getElementById('profileEmail').value = data.email || '';
      document.getElementById('profileFirstName').value = data.first_name || '';
      document.getElementById('profileMiddleName').value = data.middle_name || '';
      document.getElementById('profileLastName').value = data.last_name || '';
      document.getElementById('profilePassword').value = '';
      document.getElementById('profileDob').value = data.dob || '';
      document.getElementById('profileAge').value = data.age || '';
      document.getElementById('profileContactNumber').value = data.contact_number || '';
      document.getElementById('profileAddress').value = data.address || '';
      document.getElementById('profileEmployeeType').value = data.employee_type || '';
      document.getElementById('profileEmploymentDate').value = data.date_employment || '';
      document.getElementById('profileAdvisoryGrade').value = data.advisory_grade || '';
      document.getElementById('profileAdvisorySection').value = data.advisory_section || '';
      profileSubjectsData = parseSubjects(data.subjects);
      renderProfileSubjects(profileSubjectsData);
      document.getElementById('instructorProfileModal').classList.add('active');
    })
    .catch(err => {
      console.error(err);
      alert('Unable to load profile details. Please try again later.');
    });
}

function closeInstructorProfile() {
  document.getElementById('instructorProfileModal').classList.remove('active');
}

function parseSubjects(raw) {
  if (!raw) return {};
  try {
    return typeof raw === 'string' ? JSON.parse(raw) : raw;
  } catch (e) {
    return {};
  }
}

function renderProfileSubjects(data) {
  if (!profileSubjectsList) {
    profileSubjectsList = document.getElementById('profileSubjectsList');
  }
  if (!profileSubjectsList) {
    return;
  }

  profileSubjectsList.innerHTML = '';
  const grades = Object.keys(data || {});
  if (!grades.length) {
    profileSubjectsList.innerHTML = '<li><span class="field-label">No subjects assigned.</span></li>';
    return;
  }

  grades.forEach(grade => {
    const subjects = data[grade] || {};
    Object.keys(subjects).forEach(subject => {
      const sections = subjects[subject].sections || {};
      const sectionLines = Object.keys(sections).map(key => {
        const section = sections[key] || {};
        const days = section.days || '';
        const time = section.start || section.end ? `${section.start || ''} ${section.end || ''}`.trim() : '';
        return `${key}${days ? ' | ' + days : ''}${time ? ' | ' + time : ''}`;
      }).join('; ');

      const item = document.createElement('li');
      item.innerHTML = `<span class="field-label">${grade} / ${subject}</span><span class="field-value">${sectionLines || 'No section details'}</span>`;
      profileSubjectsList.appendChild(item);
    });
  });
}

function gatherProfileFormData() {
  const formData = new FormData();
  formData.append('id', document.getElementById('profileInstructorId').value);
  formData.append('firstName', document.getElementById('profileFirstName').value.trim());
  formData.append('middleName', document.getElementById('profileMiddleName').value.trim());
  formData.append('lastName', document.getElementById('profileLastName').value.trim());
  formData.append('email', document.getElementById('profileEmail').value.trim());
  formData.append('dob', document.getElementById('profileDob').value);
  formData.append('age', document.getElementById('profileAge').value);
  formData.append('address', document.getElementById('profileAddress').value.trim());
  formData.append('contactNumber', document.getElementById('profileContactNumber').value.trim());
  const passwordValue = document.getElementById('profilePassword').value;
  if (passwordValue) {
    formData.append('password', passwordValue);
  }
  return formData;
}

function saveInstructorProfile() {
  const formData = gatherProfileFormData();
  fetch('../admin/update_instructor.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('Profile saved successfully.');
      openInstructorProfile();
    } else {
      alert('Save failed: ' + (data.message || 'Unknown error'));
    }
  })
  .catch(err => {
    console.error(err);
    alert('An error occurred while saving the profile.');
  });
}

function exportInstructorProfilePdf() {
  const instructor = {
    employee_number: document.getElementById('profileEmployeeNumber').value,
    first_name: document.getElementById('profileFirstName').value,
    middle_name: document.getElementById('profileMiddleName').value,
    last_name: document.getElementById('profileLastName').value,
    email: document.getElementById('profileEmail').value,
    contact_number: document.getElementById('profileContactNumber').value,
    dob: document.getElementById('profileDob').value,
    age: document.getElementById('profileAge').value,
    address: document.getElementById('profileAddress').value,
    date_employment: document.getElementById('profileEmploymentDate').value,
    employee_type: document.getElementById('profileEmployeeType').value,
    advisory_grade: document.getElementById('profileAdvisoryGrade').value,
    advisory_section: document.getElementById('profileAdvisorySection').value,
  };

  const { jsPDF } = window.jspdf || {};
  if (!jsPDF) {
    alert('Export library not loaded.');
    return;
  }

  const doc = new jsPDF();
  const logoSrc = '../assets/images/mucalogo.png';
  const addHeader = (imgData) => {
    if(imgData){
      doc.addImage(imgData, 'PNG', 15, 10, 25, 25);
    }
    doc.setFontSize(16);
    doc.text('Medicion Unida Christian Academy', 50, 20);
    doc.setFontSize(12);
    doc.text('Instructor Profile and Assigned Subjects', 50, 28);
    doc.setLineWidth(0.5);
    doc.line(15, 34, 195, 34);
  };

  const addLine = (label, value, y) => {
    doc.setFontSize(11);
    doc.text(`${label}:`, 15, y);
    doc.text(String(value || ''), 70, y);
  };

  const drawDocument = (imgData) => {
    addHeader(imgData);
    let y = 44;
    const lineHeight = 7;

    addLine('Employee #', instructor.employee_number, y); y += lineHeight;
    addLine('Name', `${instructor.first_name} ${instructor.middle_name} ${instructor.last_name}`.trim(), y); y += lineHeight;
    addLine('Email', instructor.email, y); y += lineHeight;
    addLine('Contact', instructor.contact_number, y); y += lineHeight;
    addLine('DOB', instructor.dob, y); y += lineHeight;
    addLine('Age', instructor.age, y); y += lineHeight;
    addLine('Address', instructor.address, y); y += lineHeight;
    addLine('Employment Date', instructor.date_employment, y); y += lineHeight;
    addLine('Employee Type', instructor.employee_type, y); y += lineHeight;
    addLine('Advisory', `${instructor.advisory_grade || ''} ${instructor.advisory_section || ''}`.trim(), y); y += lineHeight * 2;

    doc.setFontSize(13);
    doc.text('Assigned Subjects', 15, y); y += lineHeight;
    doc.setFontSize(11);

    const entries = parseSubjects(profileSubjectsData);
    const lines = [];
    Object.keys(entries).forEach(grade => {
      const subjects = entries[grade] || {};
      Object.keys(subjects).forEach(subject => {
        const sectionData = subjects[subject].sections || {};
        Object.keys(sectionData).forEach(sectionKey => {
          const section = sectionData[sectionKey] || {};
          const days = section.days || '';
          const time = section.start || section.end ? `${section.start || ''} ${section.end || ''}`.trim() : '';
          lines.push(`${grade} / ${subject} | ${sectionKey}${days ? ' | ' + days : ''}${time ? ' | ' + time : ''}`);
        });
      });
    });

    if (!lines.length) {
      doc.text('No subjects assigned.', 15, y);
    } else {
      lines.forEach(line => {
        if (y > 280) { doc.addPage(); y = 20; }
        const split = doc.splitTextToSize(line, 180);
        doc.text(split, 15, y);
        y += lineHeight * split.length;
      });
    }

    doc.save(`${instructor.employee_number || 'instructor'}_profile.pdf`);
  };

  const img = new Image();
  img.crossOrigin = 'anonymous';
  img.onload = function() {
    const canvas = document.createElement('canvas');
    canvas.width = img.width;
    canvas.height = img.height;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0);
    drawDocument(canvas.toDataURL('image/png'));
  };
  img.onerror = function() {
    drawDocument();
  };
  img.src = logoSrc;
}

document.addEventListener('DOMContentLoaded', function() {
  const openInstructorProfileBtn = document.getElementById('openInstructorProfileBtn');
  const logoutBtn = document.getElementById('logoutBtn');
  const closeInstructorProfileBtn = document.getElementById('closeInstructorProfileBtn');
  const saveInstructorProfileBtn = document.getElementById('saveInstructorProfileBtn');
  const exportInstructorProfileBtn = document.getElementById('exportInstructorProfileBtn');

  if (openInstructorProfileBtn) {
    openInstructorProfileBtn.addEventListener('click', openInstructorProfile);
  }
  const logoutModal = document.getElementById('logoutConfirmModal');
  const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
  const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');

  if (logoutBtn) {
    logoutBtn.addEventListener('click', () => {
      if (logoutModal) {
        logoutModal.classList.add('active');
      }
    });
  }

  if (confirmLogoutBtn) {
    confirmLogoutBtn.addEventListener('click', () => {
      window.location.href = '../auth/logout.php';
    });
  }

  if (cancelLogoutBtn) {
    cancelLogoutBtn.addEventListener('click', () => {
      if (logoutModal) {
        logoutModal.classList.remove('active');
      }
    });
  }

  if (logoutModal) {
    logoutModal.addEventListener('click', (e) => {
      if (e.target === logoutModal) {
        logoutModal.classList.remove('active');
      }
    });
  }

  if (closeInstructorProfileBtn) {
    closeInstructorProfileBtn.addEventListener('click', closeInstructorProfile);
  }
  if (saveInstructorProfileBtn) {
    saveInstructorProfileBtn.addEventListener('click', saveInstructorProfile);
  }
  if (exportInstructorProfileBtn) {
    exportInstructorProfileBtn.addEventListener('click', exportInstructorProfilePdf);
  }
});
</script>