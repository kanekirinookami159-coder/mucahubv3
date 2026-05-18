<?php
$studentId = intval($_SESSION['user_id'] ?? 0);
?>

<style>
.modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.78);
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 24px;
}
.modal-overlay.active {
  display: flex;
}
.modal-panel {
  width: min(820px, 100%);
  max-height: calc(100vh - 80px);
  overflow-y: auto;
  background: #0f172a;
  border-radius: 24px;
  padding: 24px;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.35);
  border: 1px solid rgba(148, 163, 184, 0.2);
}
.profile-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
}
.profile-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
  margin-top: 18px;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.form-group label {
  font-size: 0.95rem;
  color: #cbd5e1;
}
.form-group input,
.form-group textarea {
  width: 100%;
  border-radius: 14px;
  border: 1px solid rgba(148, 163, 184, 0.35);
  padding: 12px 14px;
  background: #03121f;
  color: #f8fafc;
}
.form-group textarea {
  min-height: 90px;
  resize: vertical;
}
.profile-actions {
  margin-top: 24px;
  text-align: right;
}
.btn-save-profile {
  padding: 12px 20px;
  border: none;
  border-radius: 12px;
  background: #556b2f;
  color: white;
  cursor: pointer;
  font-weight: 700;
}
.btn-save-profile:hover {
  background: #6b8e23;
}
.modal-close {
  border: none;
  background: transparent;
  color: #ef4444;
  font-size: 32px;
  line-height: 1;
  cursor: pointer;
}
.modal-close:hover {
  color: #f87171;
}
@media (max-width: 720px) {
  .profile-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<div id="studentProfileModal" class="modal-overlay">
  <div class="modal-panel">
    <div class="modal-header profile-modal-header">
      <div>
        <h3>Student Profile</h3>
        <p style="color:#f8fafc; margin-top:6px;">View and edit your student information.</p>
      </div>
      <button class="modal-close" id="closeStudentProfileBtn" title="Close">&times;</button>
    </div>

    <form id="studentProfileForm">
      <input type="hidden" name="id" id="profileStudentId" value="<?php echo $studentId; ?>">
      <div class="profile-grid">
        <div class="form-group">
          <label>Student Number</label>
          <input type="text" id="profileStudentNumber" readonly>
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
          <label>Parent / Guardian First Name</label>
          <input type="text" name="parent_first_name" id="profileParentFirstName">
        </div>
        <div class="form-group">
          <label>Parent / Guardian Last Name</label>
          <input type="text" name="parent_last_name" id="profileParentLastName">
        </div>
        <div class="form-group">
          <label>Parent / Guardian Contact</label>
          <input type="text" name="emergency_contact" id="profileParentContactNumber">
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
          <label>Grade Level</label>
          <input type="text" id="profileGradeLevel" readonly>
        </div>
        <div class="form-group">
          <label>Section</label>
          <input type="text" id="profileSection" readonly>
        </div>
      </div>

      <div class="profile-actions">
        <button type="button" class="btn-save-profile" id="saveStudentProfileBtn">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  const studentProfileId = <?php echo $studentId; ?>;
  const openButton = document.getElementById('openStudentProfileBtn');
  const closeButton = document.getElementById('closeStudentProfileBtn');
  const logoutButton = document.getElementById('logoutBtn');
  const modal = document.getElementById('studentProfileModal');
  const saveButton = document.getElementById('saveStudentProfileBtn');

  function openStudentProfile() {
    if (!studentProfileId) {
      alert('Student profile is not available.');
      return;
    }
    modal.classList.add('active');
    loadStudentProfile();
  }

  function closeStudentProfile() {
    modal.classList.remove('active');
  }

  function renderProfileData(data) {
    document.getElementById('profileStudentId').value = data.id || '';
    document.getElementById('profileStudentNumber').value = data.student_id || data.student_number || '';
    document.getElementById('profileEmail').value = data.email || '';
    document.getElementById('profileFirstName').value = data.first_name || '';
    document.getElementById('profileMiddleName').value = data.middle_name || '';
    document.getElementById('profileLastName').value = data.last_name || '';
    document.getElementById('profilePassword').value = '';
    document.getElementById('profileDob').value = data.dob || data.date_of_birth || '';
    document.getElementById('profileAge').value = data.age || '';
    document.getElementById('profileContactNumber').value = data.contact_number || '';
    document.getElementById('profileParentFirstName').value = data.parent_first_name || '';
    document.getElementById('profileParentLastName').value = data.parent_last_name || '';
    document.getElementById('profileParentContactNumber').value = data.emergency_contact || '';
    document.getElementById('profileAddress').value = data.address || '';
    document.getElementById('profileGradeLevel').value = data.grade_level || '';
    document.getElementById('profileSection').value = data.section || '';
  }

  async function loadStudentProfile() {
    try {
      const res = await fetch(`../admin/get_student.php?id=${studentProfileId}`);
      if (!res.ok) {
        throw new Error('Unable to load profile.');
      }
      const data = await res.json();
      renderProfileData(data);
    } catch (err) {
      console.error(err);
      alert('Failed to load student profile.');
    }
  }

  async function saveStudentProfile() {
    const formData = new FormData();
    formData.append('id', document.getElementById('profileStudentId').value);
    formData.append('email', document.getElementById('profileEmail').value.trim());
    formData.append('first_name', document.getElementById('profileFirstName').value.trim());
    formData.append('middle_name', document.getElementById('profileMiddleName').value.trim());
    formData.append('last_name', document.getElementById('profileLastName').value.trim());
    formData.append('dob', document.getElementById('profileDob').value);
    formData.append('age', document.getElementById('profileAge').value);
    formData.append('contact_number', document.getElementById('profileContactNumber').value.trim());
    formData.append('parent_first_name', document.getElementById('profileParentFirstName').value.trim());
    formData.append('parent_last_name', document.getElementById('profileParentLastName').value.trim());
    formData.append('emergency_contact', document.getElementById('profileParentContactNumber').value.trim());
    formData.append('address', document.getElementById('profileAddress').value.trim());
    const password = document.getElementById('profilePassword').value;
    if (password) {
      formData.append('password', password);
    }

    try {
      const res = await fetch('../admin/update_student.php', {
        method: 'POST',
        body: formData
      });
      const text = await res.text();
      let result;
      try {
        result = JSON.parse(text);
      } catch (parseError) {
        throw new Error(text || 'Unable to parse server response.');
      }
      if (res.ok && result.success) {
        alert('Profile updated successfully.');
        loadStudentProfile();
      } else {
        throw new Error(result.message || 'Failed to save profile.');
      }
    } catch (err) {
      console.error(err);
      alert(err.message || 'Save failed. Please try again.');
    }
  }

  function logoutStudent() {
    if (confirm('Are you sure you want to log out?')) {
      window.location.href = '../auth/logout.php';
    }
  }

  if (openButton) {
    openButton.addEventListener('click', openStudentProfile);
  }
  if (closeButton) {
    closeButton.addEventListener('click', closeStudentProfile);
  }
  if (saveButton) {
    saveButton.addEventListener('click', saveStudentProfile);
  }
  if (logoutButton) {
    logoutButton.addEventListener('click', logoutStudent);
  }
})();
</script>

