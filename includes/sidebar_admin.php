<style>
/* SIDEBAR */
.sidebar {
  display: flex;
  flex-direction: column;
  height: 100vh;
  background: #556B2F; /* OLIVE GREEN */
}

/* LOGO */
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

/* MENU */
.sidebar ul {
  list-style: none;
  margin: 0;
  padding: 0;
  flex: 1;
}

.sidebar ul a,
.sidebar ul button {
  text-decoration: none;
  color: white;
  display: block;
}

.sidebar ul li {
  padding: 18px 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  transition: background 0.2s ease;
}

.sidebar ul li:hover {
  background: rgba(255, 255, 255, 0.12);
}

/* FOOTER */
.sidebar-footer {
  margin-top: auto;
  padding: 16px;
}

/* LOGOUT BUTTON (RED) */
.btn-logout {
  width: 100%;
  border: none;
  background: #dc2626; /* RED */
  color: white;
  text-align: left;
  padding: 14px 20px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  transition: background 0.2s ease, transform 0.2s ease;
}

.btn-logout:hover {
  background: #b91c1c; /* darker red */
  transform: translateY(-1px);
}

/* MODAL */
.logout-confirm-modal {
  position: fixed;
  inset: 0;
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  background: rgba(0, 0, 0, 0.68);
  padding: 16px;
}

.logout-confirm-modal.active {
  display: flex;
}

.logout-confirm-card {
  width: 100%;
  max-width: 420px;
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.98), rgba(15, 23, 42, 0.92));
  border-radius: 24px;
  padding: 24px;
  border: 1px solid rgba(255,255,255,0.12);
  box-shadow: 0 28px 70px rgba(0,0,0,0.35);
  text-align: center;
  color: #f8fafc;
}

.logout-confirm-card .icon {
  width: 68px;
  height: 68px;
  border-radius: 50%;
  margin: 0 auto 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  color: #f8fafc;
  background: rgba(195, 35, 51, 0.18);
}

.logout-confirm-card h2 {
  margin: 0 0 10px;
  font-size: 1.5rem;
}

.logout-confirm-card p {
  margin: 0 0 22px;
  color: #cbd5e1;
  line-height: 1.6;
}

.logout-confirm-actions {
  display: flex;
  gap: 10px;
  justify-content: center;
}

.logout-confirm-actions button {
  min-width: 110px;
  padding: 12px 18px;
  border: none;
  border-radius: 999px;
  cursor: pointer;
  font-weight: 600;
}

.logout-confirm-cancel {
  background: rgba(255,255,255,0.08);
  color: #f8fafc;
}

.logout-confirm-accept {
  background: #dc2626;
  color: white;
}

.sidebar a {
  text-decoration: none;
}

.btn-logout {
  text-decoration: none;
}

/* PROFILE BUTTON */
.btn-profile {
  width: 100%;
  border: none;
  background: #556B2F;
  color: white;
  text-align: left;
  padding: 14px 20px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  transition: background 0.2s ease, transform 0.2s ease;
  margin-bottom: 12px;
  border: 2px solid rgba(255, 255, 255, 0.2);
  font-size: 16px;
}

.btn-profile:hover {
  background: rgba(255, 255, 255, 0.15);
  transform: translateY(-1px);
}

.btn-profile i {
  font-size: 18px;
}

/* PROFILE MODAL */
.profile-modal {
  position: fixed;
  inset: 0;
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  background: rgba(0, 0, 0, 0.68);
  padding: 16px;
}

.profile-modal.active {
  display: flex;
}

.profile-card {
  width: 100%;
  max-width: 480px;
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.98), rgba(15, 23, 42, 0.92));
  border-radius: 24px;
  padding: 32px;
  border: 1px solid rgba(255,255,255,0.12);
  box-shadow: 0 28px 70px rgba(0,0,0,0.35);
  color: #f8fafc;
  max-height: 90vh;
  overflow-y: auto;
}

.profile-card h2 {
  margin: 0 0 24px;
  font-size: 1.8rem;
  text-align: center;
  color: #556B2F;
}

.profile-form-group {
  margin-bottom: 20px;
}

.profile-form-group label {
  display: block;
  margin-bottom: 8px;
  color: #cbd5e1;
  font-weight: 600;
  font-size: 14px;
}

.profile-form-group input {
  width: 100%;
  padding: 12px 16px;
  background: rgba(255,255,255,0.08);
  border: 2px solid rgba(255,255,255,0.1);
  border-radius: 8px;
  color: #f8fafc;
  font-size: 15px;
  transition: border-color 0.2s;
  outline: none;
}

.profile-form-group input:focus {
  border-color: #556B2F;
  background: rgba(255,255,255,0.12);
}

.profile-form-group input::placeholder {
  color: #94a3b8;
}

.profile-actions {
  display: flex;
  gap: 12px;
  margin-top: 28px;
  justify-content: center;
}

.profile-actions button {
  min-width: 120px;
  padding: 12px 20px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 15px;
  transition: all 0.2s;
}

.btn-save-profile {
  background: #556B2F;
  color: white;
}

.btn-save-profile:hover {
  background: #6b8e23;
  transform: translateY(-2px);
}

.btn-cancel-profile {
  background: rgba(255,255,255,0.08);
  color: #f8fafc;
}

.btn-cancel-profile:hover {
  background: rgba(255,255,255,0.15);
}

.profile-loading {
  text-align: center;
  color: #cbd5e1;
}

.profile-error {
  background: rgba(220, 38, 38, 0.2);
  color: #fca5a5;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 16px;
  border-left: 3px solid #dc2626;
}

.profile-success {
  background: rgba(34, 197, 94, 0.2);
  color: #86efac;
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 16px;
  border-left: 3px solid #22c55e;
}

/* PASSWORD TOGGLE BUTTON */
.toggle-password-btn {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  font-size: 16px;
  padding: 4px 8px;
  transition: color 0.2s;
}

.toggle-password-btn:hover {
  color: #cbd5e1;
}

.profile-form-group input[type="password"],
.profile-form-group input[type="text"] {
  padding-right: 45px;
}

hr {
  opacity: 0.5;
}
</style>

<div class="sidebar">

  <div class="logo">
    <img src="../assets/images/mucalogo.png" alt="MUCAHUB Logo">
    MUCAHUB
  </div>

  <ul>
    <a href="dashboard.php"><li><i class="fa fa-home"></i> Home</li></a>
    <a href="manage_students.php"><li><i class="fa fa-user-graduate"></i> Manage Students</li></a>
    <a href="manage_instructors.php"><li><i class="fa fa-chalkboard-teacher"></i> Manage Instructors</li></a>
    <a href="manage_courses.php"><li><i class="fa fa-book"></i> Manage Courses</li></a>
    <a href="announcements.php"><li><i class="fa fa-bullhorn"></i> Announcement</li></a>
  </ul>

  <!-- LOGOUT BOTTOM -->
  <div class="sidebar-footer">
    <button id="profileBtn" class="btn-profile" title="View Profile">
      <i class="fa fa-user-circle"></i> Profile
    </button>
    <a id="logoutBtn" class="btn-logout" href="../auth/logout.php">
      <i class="fa fa-sign-out-alt"></i> Logout
    </a>
  </div>

</div>

<!-- PROFILE MODAL -->
<div id="profileModal" class="profile-modal">
  <div class="profile-card">
    <h2>Admin Profile</h2>
    <div id="profileMessage"></div>
    <form id="profileForm">
      <div class="profile-form-group">
        <label for="adminEmail">Email</label>
        <input type="email" id="adminEmail" name="email" required>
      </div>
      <div class="profile-form-group">
        <label for="adminFirstName">First Name</label>
        <input type="text" id="adminFirstName" name="firstName" required>
      </div>
      <div class="profile-form-group">
        <label for="adminMiddleName">Middle Name</label>
        <input type="text" id="adminMiddleName" name="middleName">
      </div>
      <div class="profile-form-group">
        <label for="adminLastName">Last Name</label>
        <input type="text" id="adminLastName" name="lastName" required>
      </div>
      <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.1); margin: 24px 0;">
      <div class="profile-form-group">
        <label for="adminPassword">New Password (leave empty to keep current)</label>
        <div style="position: relative;">
          <input type="password" id="adminPassword" name="password" placeholder="Enter new password">
          <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('adminPassword')">
            <i class="fa fa-eye"></i>
          </button>
        </div>
      </div>
      <div class="profile-form-group">
        <label for="adminPasswordConfirm">Confirm Password</label>
        <div style="position: relative;">
          <input type="password" id="adminPasswordConfirm" name="passwordConfirm" placeholder="Confirm new password">
          <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('adminPasswordConfirm')">
            <i class="fa fa-eye"></i>
          </button>
        </div>
      </div>
      <div class="profile-actions">
        <button type="submit" class="btn-save-profile">Save Changes</button>
        <button type="button" class="btn-cancel-profile" onclick="closeProfileModal()">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL -->
<div id="logoutConfirmModal" class="logout-confirm-modal">
  <div class="logout-confirm-card">
    <div class="icon"><i class="fa fa-sign-out-alt"></i></div>
    <h2>Confirm Logout</h2>
    <p>Are you sure you want to sign out? Your session will close and you will return to the login page.</p>

    <div class="logout-confirm-actions">
      <button type="button" id="cancelLogoutBtn" class="logout-confirm-cancel">Cancel</button>
      <button type="button" id="confirmLogoutBtn" class="logout-confirm-accept">Log Out</button>
    </div>
  </div>
</div>

<script>
  const logoutBtn = document.getElementById('logoutBtn');
  const logoutModal = document.getElementById('logoutConfirmModal');
  const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
  const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');

  logoutBtn.addEventListener('click', function(e) {
    e.preventDefault();
    logoutModal.classList.add('active');
  });

  confirmLogoutBtn.addEventListener('click', () => {
    window.location.href = '../auth/login.php';
  });

  cancelLogoutBtn.addEventListener('click', () => {
    logoutModal.classList.remove('active');
  });

  logoutModal.addEventListener('click', (e) => {
    if (e.target === logoutModal) {
      logoutModal.classList.remove('active');
    }
  });

  // Profile Modal Functions
  const profileBtn = document.getElementById('profileBtn');
  const profileModal = document.getElementById('profileModal');
  const profileForm = document.getElementById('profileForm');

  profileBtn.addEventListener('click', () => {
    loadProfileData();
    profileModal.classList.add('active');
  });

  function closeProfileModal() {
    profileModal.classList.remove('active');
    document.getElementById('profileMessage').innerHTML = '';
  }

  profileModal.addEventListener('click', (e) => {
    if (e.target === profileModal) {
      closeProfileModal();
    }
  });

  function loadProfileData() {
    fetch('../admin/get_admin_profile.php')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          document.getElementById('adminEmail').value = data.email;
          document.getElementById('adminFirstName').value = data.first_name;
          document.getElementById('adminMiddleName').value = data.middle_name || '';
          document.getElementById('adminLastName').value = data.last_name;
        } else {
          showProfileMessage('Error loading profile: ' + data.message, 'error');
        }
      })
      .catch(error => {
        showProfileMessage('Error: ' + error.message, 'error');
      });
  }

  profileForm.addEventListener('submit', (e) => {
    e.preventDefault();
    saveProfileData();
  });

  function saveProfileData() {
    const password = document.getElementById('adminPassword').value;
    const passwordConfirm = document.getElementById('adminPasswordConfirm').value;

    // Validate password if provided
    if (password || passwordConfirm) {
      if (!password || !passwordConfirm) {
        showProfileMessage('Both password fields must be filled if changing password', 'error');
        return;
      }
      if (password !== passwordConfirm) {
        showProfileMessage('Passwords do not match', 'error');
        return;
      }
      if (password.length < 6) {
        showProfileMessage('Password must be at least 6 characters long', 'error');
        return;
      }
    }

    const formData = new FormData(profileForm);

    fetch('../admin/save_admin_profile.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showProfileMessage('Profile updated successfully!', 'success');
          setTimeout(() => {
            closeProfileModal();
            location.reload();
          }, 1500);
        } else {
          showProfileMessage('Error: ' + data.message, 'error');
        }
      })
      .catch(error => {
        showProfileMessage('Error: ' + error.message, 'error');
      });
  }

  function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const btn = event.currentTarget;
    if (field.type === 'password') {
      field.type = 'text';
      btn.innerHTML = '<i class="fa fa-eye-slash"></i>';
    } else {
      field.type = 'password';
      btn.innerHTML = '<i class="fa fa-eye"></i>';
    }
  }

  function showProfileMessage(message, type) {
    const messageDiv = document.getElementById('profileMessage');
    messageDiv.innerHTML = `<div class="profile-${type}">${message}</div>`;
  }
</script>