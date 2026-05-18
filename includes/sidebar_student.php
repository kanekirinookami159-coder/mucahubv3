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
</style>

<div class="sidebar">

  <div class="logo">
    <img src="../assets/images/mucalogo.png" alt="MUCAHUB Logo">
    MUCAHUB
  </div>

  <ul>
    <a href="../student/student_dashboard.php">
      <li><i class="fa fa-tachometer-alt"></i> Dashboard</li>
    </a>

    <a href="../student/home.php">
      <li><i class="fa fa-bullhorn"></i> Announcements</li>
    </a>

    <a href="../student/my_courses.php">
      <li><i class="fa fa-book-open"></i> My Courses</li>
    </a>

    <a href="#" onclick="openReportCardModal(event)">
      <li><i class="fa fa-graduation-cap"></i> Report Card</li>
    </a>
  </ul>

  <div class="sidebar-footer">
    <button id="openStudentProfileBtn"><i class="fa fa-user"></i> Profile</button>
    <button id="logoutBtn" class="btn-logout"><i class="fa fa-sign-out-alt"></i> Logout</button>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
  const openStudentProfileBtn = document.getElementById('openStudentProfileBtn');
  const logoutBtn = document.getElementById('logoutBtn');
  const closeStudentProfileBtn = document.getElementById('closeStudentProfileBtn');
  const saveStudentProfileBtn = document.getElementById('saveStudentProfileBtn');

  if (openStudentProfileBtn) {
    openStudentProfileBtn.addEventListener('click', function() {
      const profileModal = document.getElementById('profileModal');
      if (profileModal) {
        profileModal.classList.add('active');
      }
    });
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

  if (closeStudentProfileBtn) {
    closeStudentProfileBtn.addEventListener('click', function() {
      const profileModal = document.getElementById('profileModal');
      if (profileModal) {
        profileModal.classList.remove('active');
      }
    });
  }

  if (saveStudentProfileBtn) {
    saveStudentProfileBtn.addEventListener('click', function() {
      const profileModal = document.getElementById('profileModal');
      if (profileModal && profileModal.classList.contains('active')) {
        // Profile save logic can be added here
        profileModal.classList.remove('active');
      }
    });
  }
});
</script>