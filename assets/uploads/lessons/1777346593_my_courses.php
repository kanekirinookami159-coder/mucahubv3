<!DOCTYPE html>
<html>

<head>

<title>My Courses</title>

<link rel="stylesheet" href="../assets/css/student_dashboard.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.dropdown-menu, .menu-dropdown {
    font-size: 1rem;
    padding: 6px 0;
}
.dropdown-item {
    padding: 8px 18px;
    cursor: pointer;
    transition: background 0.2s;
    position: relative;
    color: #222;
    display: flex;
    align-items: center;
    white-space: nowrap;
}
.dropdown-item.selected::before {
    content: '\2713';
    color: #556b2f;
    font-size: 1.1em;
    margin-right: 10px;
    display: inline-block;
    width: 1.2em;
}
.dropdown-item:not(.selected)::before {
    content: '';
    display: inline-block;
    width: 1.2em;
}
.dropdown-item:hover {
    background: #e6f2d9;
    color: #556b2f;
}
.menu-dropdown .dropdown-item:hover {
    background: #e6f2d9;
    color: #556b2f;
}
.starred {
    border: 2px solid #ffd700 !important;
    box-shadow: 0 0 10px #ffd70055 !important;
    position: relative;
}
.starred::after {
    content: '\2605';
    color: #ffd700;
    font-size: 1.5em;
    position: absolute;
    top: 10px;
    right: 18px;
    pointer-events: none;
}
</style>

</head>

<body>

<!-- SIDEBAR -->
<?php include "../includes/sidebar_student.php"; ?>


<!-- MAIN -->
<div class="main">
    <h2 style="color: #ff8000; font-size: 2.5rem; font-weight: bold; margin-bottom: 10px;">My courses</h2>
    <div style="font-size: 1.3rem; color: #ff8000; font-weight: 600; margin-bottom: 18px;">Course overview</div>
    <!-- Filter/Search Bar -->
    <div class="course-filters" style="display: flex; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; align-items: center;">
        <div class="dropdown" style="position: relative;">
            <button class="dropdown-toggle" id="filterDropdownBtn" style="padding: 8px 18px 8px 12px; border-radius: 6px; border: 1px solid #ccc; background: #6c757d; color: white; font-weight: 500; min-width: 80px; text-align: left;">All <i class="fa fa-chevron-down" style="margin-left: 8px;"></i></button>
            <div class="dropdown-menu" id="filterDropdownMenu" style="display: none; position: absolute; left: 0; top: 110%; background: white; border: 1px solid #ccc; border-radius: 6px; min-width: 180px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); z-index: 10;">
                <div class="dropdown-item" data-value="All">All</div>
                <div class="dropdown-item" data-value="In progress">In progress</div>
                <div class="dropdown-item" data-value="Future">Future</div>
                <div class="dropdown-item" data-value="Past">Past</div>
                <hr style="margin: 4px 0;">
                <div class="dropdown-item" data-value="Starred">Starred</div>
                <div class="dropdown-item" data-value="Removed from view">Removed from view</div>
            </div>
        </div>
        <input type="text" id="searchInput" placeholder="Search" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; min-width: 180px;">
        <div class="dropdown" style="position: relative;">
            <button class="dropdown-toggle" id="sortDropdownBtn" style="padding: 8px 18px 8px 12px; border-radius: 6px; border: 1px solid #16213e; background: #16213e; color: white; font-weight: 500; min-width: 160px; text-align: left;">Sort by course name <i class="fa fa-chevron-down" style="margin-left: 8px;"></i></button>
            <div class="dropdown-menu" id="sortDropdownMenu" style="display: none; position: absolute; left: 0; top: 110%; background: white; border: 1px solid #ccc; border-radius: 6px; min-width: 180px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); z-index: 10;">
                <div class="dropdown-item" data-value="Sort by course name">Sort by course name</div>
                <div class="dropdown-item" data-value="Sort by last accessed">Sort by last accessed</div>
            </div>
        </div>
        <!-- Card dropdown removed as requested -->
    </div>
    <!-- Course Grid -->
    <div class="course-grid" id="courseGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 18px;">
        <!-- Decoy Announcement Card with 3-dots menu -->
        <div class="course-card announcement-card" id="announcementCard" data-status="All,In progress,Future,Past,Starred" style="background: linear-gradient(135deg, #e0ffe0 60%, #b6e388 100%); border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 0; overflow: hidden; min-height: 140px; display: flex; flex-direction: column; position: relative;">
            <div style="background: #556b2f; color: white; padding: 12px 18px; font-weight: bold; font-size: 1.1rem; display: flex; justify-content: space-between; align-items: center;">
                Announcement
                <div class="menu-btn" style="position: relative;">
                    <button class="dots-btn" style="background: none; border: none; font-size: 1.3rem; color: white; cursor: pointer; padding: 0 8px;">
                        <i class="fa fa-ellipsis-v"></i>
                    </button>
                    <div class="menu-dropdown" style="display: none; position: absolute; right: 0; top: 120%; background: white; border: 1px solid #ccc; border-radius: 6px; min-width: 180px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); z-index: 20;">
                        <div class="dropdown-item" id="starAnnouncement">Star this Course</div>
                        <div class="dropdown-item" id="removeAnnouncement">Remove from view</div>
                        <div class="dropdown-item" id="restoreAnnouncement" style="display:none;">Restore</div>
                    </div>
                </div>
            </div>
            <div style="padding: 18px; color: #333; flex: 1; display: flex; align-items: center;">
                <span style="font-size: 1.05rem;">Welcome! Announcements from the admin will appear here soon.</span>
            </div>
        </div>
    </div>
</div>

<!-- FLOAT BUTTONS -->
<?php include "../includes/float_student.php"; ?>

<!-- PROFILE -->
<?php include "../includes/profile_student.php"; ?>

<!-- FOOTER -->
<?php include "../includes/footer.php"; ?>

<script src="../assets/js/dashboard.js"></script>
<script>
// Dropdown logic for demo (show/hide on click)
function closeAllDropdowns() {
    document.querySelectorAll('.dropdown-menu').forEach(function(m) { m.style.display='none'; });
}
document.querySelectorAll('.dropdown-toggle').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        var menu = btn.parentElement.querySelector('.dropdown-menu');
        closeAllDropdowns();
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        e.stopPropagation();
    });
});
document.body.addEventListener('click', closeAllDropdowns);

// Dropdown item selection and persistence
function handleDropdown(dropdownBtnId, dropdownMenuId, storageKey, defaultValue) {
    var btn = document.getElementById(dropdownBtnId);
    var menu = document.getElementById(dropdownMenuId);
    var items = menu.querySelectorAll('.dropdown-item');
    // Restore from storage
    var saved = localStorage.getItem(storageKey) || defaultValue;
    btn.childNodes[0].nodeValue = saved + ' ';
    items.forEach(function(item) {
        item.classList.toggle('selected', item.textContent === saved);
    });
    // Click handler
    items.forEach(function(item) {
        item.onclick = function(e) {
            items.forEach(function(i) { i.classList.remove('selected'); });
            item.classList.add('selected');
            btn.childNodes[0].nodeValue = item.textContent + ' ';
            localStorage.setItem(storageKey, item.textContent);
            menu.style.display = 'none';
            applyFilters();
            e.stopPropagation();
        };
    });
}
// Apply filters (demo: only announcement card, but logic is ready for more)
function applyFilters() {
    var filter = localStorage.getItem('filterDropdown') || 'All';
    var search = document.getElementById('searchInput').value.toLowerCase();
    var isStarred = localStorage.getItem('announcementStarred') === 'true';
    var isRemoved = localStorage.getItem('announcementRemoved') === 'true';
    var card = document.getElementById('announcementCard');
    var starBtn = document.getElementById('starAnnouncement');
    var removeBtn = document.getElementById('removeAnnouncement');
    var restoreBtn = document.getElementById('restoreAnnouncement');
    
    // Starred visual
    card.classList.toggle('starred', isStarred);
    
    // Show/hide star/remove/restore buttons based on state
    if (isRemoved) {
        starBtn.style.display = 'none';
        removeBtn.style.display = 'none';
        restoreBtn.style.display = 'block';
    } else {
        starBtn.style.display = 'block';
        removeBtn.style.display = 'block';
        restoreBtn.style.display = 'none';
    }
    
    // Filter logic
    if (filter === 'Removed from view') {
        // Show only removed courses
        if (isRemoved) {
            card.style.display = (!search || card.textContent.toLowerCase().includes(search)) ? '' : 'none';
        } else {
            card.style.display = 'none';
        }
    } else if (filter === 'Starred') {
        // Show only starred courses
        if (!isRemoved && isStarred) {
            card.style.display = (!search || card.textContent.toLowerCase().includes(search)) ? '' : 'none';
        } else {
            card.style.display = 'none';
        }
    } else if (isRemoved) {
        // If not showing "Removed from view" filter, hide removed cards
        card.style.display = 'none';
    } else {
        // Normal filter
        if (filter === 'All' || card.getAttribute('data-status').includes(filter)) {
            card.style.display = (!search || card.textContent.toLowerCase().includes(search)) ? '' : 'none';
        } else {
            card.style.display = 'none';
        }
    }
}
// Setup all dropdowns
handleDropdown('filterDropdownBtn', 'filterDropdownMenu', 'filterDropdown', 'All');
handleDropdown('sortDropdownBtn', 'sortDropdownMenu', 'sortDropdown', 'Sort by course name');
document.getElementById('searchInput').addEventListener('input', function() {
    applyFilters();
});
// Initial filter
applyFilters();
// Three dots menu for announcement card
document.querySelectorAll('.dots-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        var menu = btn.parentElement.querySelector('.menu-dropdown');
        document.querySelectorAll('.menu-dropdown').forEach(function(m) { if(m!==menu) m.style.display='none'; });
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        e.stopPropagation();
    });
});
document.body.addEventListener('click', function() {
    document.querySelectorAll('.menu-dropdown').forEach(function(m) { m.style.display='none'; });
});
// Star/Remove actions for announcement card
var starBtn = document.getElementById('starAnnouncement');
var removeBtn = document.getElementById('removeAnnouncement');
starBtn.onclick = function(e) {
    var isStarred = localStorage.getItem('announcementStarred') === 'true';
    localStorage.setItem('announcementStarred', isStarred ? 'false' : 'true');
    applyFilters();
    e.stopPropagation();
};
removeBtn.onclick = function(e) {
    localStorage.setItem('announcementRemoved', 'true');
    applyFilters();
    e.stopPropagation();
};
// Restore action for announcement card
var restoreBtn = document.getElementById('restoreAnnouncement');
restoreBtn.onclick = function(e) {
    localStorage.setItem('announcementRemoved', 'false');
    applyFilters();
    e.stopPropagation();
};
// Restore state on load
window.addEventListener('DOMContentLoaded', function() {
    applyFilters();
});
</script>

</body>
</html>