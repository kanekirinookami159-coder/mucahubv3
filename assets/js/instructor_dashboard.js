/* =========================
OPEN SIDE PANELS
========================= */
const activityBtn = document.getElementById("activityBtn");
const profileBtn = document.getElementById("profileBtn");
const activityPanel = document.getElementById("activityPanel");
const profilePanel = document.getElementById("profilePanel");

if (activityBtn && activityPanel) {
    activityBtn.onclick = () => {
        activityPanel.classList.add("active");
        updateNotificationDot();
    };
}

if (profileBtn && profilePanel) {
    profileBtn.onclick = () => profilePanel.classList.add("active");
}

function closeActivity() {
    if (activityPanel) {
        activityPanel.classList.remove("active");
    }
}

function closeProfile() {
    if (profilePanel) {
        profilePanel.classList.remove("active");
    }
}

/* =========================
NOTIFICATION PANEL STORAGE
========================= */
const notificationSeenKeyPrefix = 'mucahub_notifications_last_seen_';
const notificationDismissedKeyPrefix = 'mucahub_notifications_dismissed_';

function getNotificationSeenKey() {
    return notificationSeenKeyPrefix + (window.mucahubInstructorId || 'guest');
}

function getNotificationDismissedKey() {
    return notificationDismissedKeyPrefix + (window.mucahubInstructorId || 'guest');
}

function loadDismissedNotificationIds() {
    try {
        const stored = localStorage.getItem(getNotificationDismissedKey());
        const parsed = JSON.parse(stored);
        return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        return [];
    }
}

function saveDismissedNotificationIds(ids) {
    try {
        localStorage.setItem(getNotificationDismissedKey(), JSON.stringify(ids));
    } catch (error) {
        console.error('Unable to save dismissed notifications:', error);
    }
}

function getLastNotificationSeenAt() {
    try {
        const stored = localStorage.getItem(getNotificationSeenKey());
        const timestamp = Number(stored);
        return Number.isNaN(timestamp) ? null : new Date(timestamp);
    } catch (error) {
        return null;
    }
}

function setNotificationsLastSeenAt() {
    try {
        localStorage.setItem(getNotificationSeenKey(), Date.now().toString());
    } catch (error) {
        console.error('Unable to update notification last seen time:', error);
    }
}

function getNotificationItems() {
    const dismissedIds = loadDismissedNotificationIds();
    const activities = getDashboardActivityItems();
    const tasks = getTaskItems();
    return activities
        .concat(tasks)
        .filter(item => !dismissedIds.includes(item.id))
        .sort((a, b) => b.sortDate - a.sortDate);
}

function removeNotificationItem(notificationId) {
    const dismissedIds = loadDismissedNotificationIds();
    if (!dismissedIds.includes(notificationId)) {
        dismissedIds.push(notificationId);
        saveDismissedNotificationIds(dismissedIds);
    }
    renderNotificationsList();
    updateNotificationDot();
}

function clearAllNotifications() {
    const items = getNotificationItems();
    const dismissedIds = items.map(item => item.id);
    saveDismissedNotificationIds(dismissedIds);
    setNotificationsLastSeenAt();
    renderNotificationsList();
    updateNotificationDot();
}

function ensureNotificationPanelHeader() {
    const panel = document.getElementById('activityPanel');
    if (!panel) {
        return;
    }
    if (panel.querySelector('#clearNotificationsBtn')) {
        return;
    }
    const title = panel.querySelector('h3');
    if (!title) {
        return;
    }
    const clearBtn = document.createElement('button');
    clearBtn.type = 'button';
    clearBtn.id = 'clearNotificationsBtn';
    clearBtn.textContent = 'Clear All';
    clearBtn.style.cssText = 'margin-left:12px;padding:6px 10px;border-radius:6px;border:1px solid #d1d1d1;background:#fff;color:#333;cursor:pointer;font-size:0.9rem;';
    clearBtn.addEventListener('click', function(event) {
        event.stopPropagation();
        clearAllNotifications();
    });
    title.insertAdjacentElement('afterend', clearBtn);
}

function getTaskItems() {
    try {
        // Get user-specific storage key
        const userId = window.mucahubUserId || 'default';
        const storageKey = `personalTasks_${userId}`;
        const stored = localStorage.getItem(storageKey);
        const parsed = JSON.parse(stored);
        if (!Array.isArray(parsed)) {
            return [];
        }
        return parsed.map((task, index) => {
            const taskDate = new Date(task.date);
            const sortDate = Number.isNaN(taskDate.getTime()) ? Date.now() : taskDate.getTime();
            return {
                type: 'task',
                title: task.text || 'To-Do Item',
                text: '',
                date: task.date || '',
                sortDate,
                id: `task-${task.id || index}`
            };
        });
    } catch (error) {
        return [];
    }
}

function getDashboardActivityItems() {
    if (!Array.isArray(window.dashboardNotificationEvents)) {
        return [];
    }
    return window.dashboardNotificationEvents.map((item, index) => {
        const itemDate = new Date(item.date);
        const sortDate = Number.isNaN(itemDate.getTime()) ? Date.now() : itemDate.getTime();
        let link = item.link || '';
        if (!link) {
            if (item.type === 'assignment') {
                link = `my_class.php?assignment_id=${encodeURIComponent(item.id)}#grades`;
            } else if (item.type === 'activity') {
                link = 'my_class.php#grades';
            }
        }
        return {
            type: item.type || 'activity',
            title: item.title || 'Activity Update',
            text: item.text || '',
            date: item.date || '',
            sortDate,
            link,
            id: item.id || `activity-${index}`
        };
    });
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatNotificationDate(value) {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }
    return date.toLocaleString();
}

function renderNotificationsList() {
    const list = document.getElementById('recentAccessList');
    if (!list) {
        return;
    }

    const items = getNotificationItems();
    list.innerHTML = '';
    if (items.length === 0) {
        list.innerHTML = '<li class="notification-card empty">No notifications yet.</li>';
        return;
    }

    const lastSeenAt = getLastNotificationSeenAt();
    items.forEach(item => {
        const li = document.createElement('li');
        const typeLabel = item.type === 'announcement' ? 'Admin' : item.type === 'task' ? 'To-Do' : item.type === 'assignment' ? 'Assignment' : 'Activity';
        li.className = `notification-card notification-${item.type}`;
        li.style.position = 'relative';
        const itemDate = new Date(item.date);
        const isUnread = !lastSeenAt || itemDate > lastSeenAt;
        if (isUnread) {
            li.classList.add('unread');
        }
        const displayText = item.text ? (item.text.length > 120 ? item.text.slice(0, 117) + '...' : item.text) : '';
        li.innerHTML = `
            <div class="notification-card-header">
                <div class="notification-card-title">${escapeHtml(item.title)}</div>
                <div class="notification-card-type">${escapeHtml(typeLabel)}</div>
            </div>
            <div class="notification-card-meta">${escapeHtml(formatNotificationDate(item.date))}</div>
            ${displayText ? `<div class="notification-card-text">${escapeHtml(displayText)}</div>` : ''}
        `;
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'notification-remove-btn';
        removeBtn.textContent = '×';
        removeBtn.title = 'Dismiss notification';
        removeBtn.style.cssText = 'position:absolute;top:10px;right:10px;border:none;background:transparent;color:#777;font-size:18px;cursor:pointer;line-height:1;';
        removeBtn.addEventListener('click', function(event) {
            event.stopPropagation();
            removeNotificationItem(item.id);
        });
        li.appendChild(removeBtn);
        li.addEventListener('click', function(event) {
            if (event.target === removeBtn) {
                return;
            }
            if (li.classList.contains('unread')) {
                li.classList.remove('unread');
                setNotificationsLastSeenAt();
                updateNotificationDot();
            }
            if (item.link) {
                window.location.href = item.link;
            }
        });
        if (item.link) {
            li.classList.add('clickable');
        }
        list.appendChild(li);
    });
}

function hasUnreadNotifications() {
    const items = getNotificationItems();
    if (items.length === 0) {
        return false;
    }

    const lastSeenAt = getLastNotificationSeenAt();
    if (!lastSeenAt) {
        return true;
    }

    return items.some(item => {
        const itemDate = new Date(item.date);
        return itemDate > lastSeenAt;
    });
}

function updateNotificationDot() {
    const dot = document.getElementById('notificationDot');
    if (!dot) {
        return;
    }
    dot.style.display = hasUnreadNotifications() ? 'block' : 'none';
}

function initNotifications() {
    ensureNotificationPanelHeader();
    renderNotificationsList();
    updateNotificationDot();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNotifications);
} else {
    initNotifications();
}

/* =========================
INITIAL LOAD
========================= */
loadTasks();
renderCalendar();