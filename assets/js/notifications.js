(function() {
    const notificationSeenKeyPrefix = 'mucahub_notifications_last_seen_';
    const notificationDismissedKeyPrefix = 'mucahub_notifications_dismissed_';

    function getUserId() {
        return window.mucahubUserId || 'guest';
    }

    function getDismissedKey() {
        return notificationDismissedKeyPrefix + getUserId();
    }

    function getSeenKey() {
        return notificationSeenKeyPrefix + getUserId();
    }

    function loadDismissedNotificationIds() {
        try {
            const value = localStorage.getItem(getDismissedKey());
            return value ? JSON.parse(value) : [];
        } catch (error) {
            return [];
        }
    }

    function saveDismissedNotificationIds(ids) {
        localStorage.setItem(getDismissedKey(), JSON.stringify(ids));
    }

    function getLastNotificationSeenAt() {
        return localStorage.getItem(getSeenKey()) || '';
    }

    function setNotificationsLastSeenAt() {
        localStorage.setItem(getSeenKey(), new Date().toISOString());
        updateNotificationDot();
        renderNotificationsList();
    }

    function getDashboardNotificationItems() {
        const events = Array.isArray(window.dashboardNotificationEvents) ? window.dashboardNotificationEvents : [];
        return events.map(event => ({
            id: String(event.id || ''),
            title: String(event.title || ''),
            text: String(event.text || ''),
            date: String(event.date || ''),
            link: String(event.link || ''),
            type: String(event.type || 'Info')
        }));
    }

    function getNotificationItems() {
        const dismissed = loadDismissedNotificationIds();
        return getDashboardNotificationItems()
            .filter(item => item.id && !dismissed.includes(item.id))
            .sort((a, b) => String(b.date || '').localeCompare(String(a.date || '')));
    }

    function removeNotificationItem(notificationId) {
        const dismissed = loadDismissedNotificationIds();
        if (!dismissed.includes(notificationId)) {
            dismissed.push(notificationId);
            saveDismissedNotificationIds(dismissed);
        }
        renderNotificationsList();
        updateNotificationDot();
    }

    function clearAllNotifications() {
        const notifications = getDashboardNotificationItems();
        saveDismissedNotificationIds(notifications.map(item => item.id));
        setNotificationsLastSeenAt();
        renderNotificationsList();
    }

    function hasUnreadNotifications() {
        const lastSeen = getLastNotificationSeenAt();
        const notifications = getDashboardNotificationItems();
        if (!lastSeen) {
            return notifications.length > 0;
        }
        return notifications.some(item => item.date > lastSeen && !loadDismissedNotificationIds().includes(item.id));
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"]/g, char => {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;'
            }[char];
        });
    }

    function formatNotificationDate(value) {
        if (!value) {
            return '';
        }
        const date = new Date(value);
        if (isNaN(date.getTime())) {
            return escapeHtml(value);
        }
        return date.toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric'
        });
    }

    function renderNotificationsList() {
        const list = document.getElementById('recentAccessList');
        if (!list) {
            return;
        }

        const notifications = getNotificationItems();
        list.innerHTML = '';

        if (notifications.length === 0) {
            const emptyItem = document.createElement('li');
            emptyItem.className = 'notification-card empty';
            emptyItem.textContent = 'No new notifications.';
            list.appendChild(emptyItem);
            return;
        }

        const lastSeen = getLastNotificationSeenAt();
        const dismissed = loadDismissedNotificationIds();

        notifications.forEach(item => {
            const isUnread = !lastSeen || (item.date && item.date > lastSeen);
            const card = document.createElement('li');
            card.className = 'notification-card clickable' + (isUnread ? ' unread' : '');

            const header = document.createElement('div');
            header.className = 'notification-card-header';

            const title = document.createElement('div');
            title.className = 'notification-card-title';
            title.textContent = item.title || 'Notification';

            const meta = document.createElement('div');
            meta.className = 'notification-card-meta';
            meta.textContent = formatNotificationDate(item.date) || 'Today';

            const removeBtn = document.createElement('button');
            removeBtn.className = 'remove-btn';
            removeBtn.type = 'button';
            removeBtn.title = 'Dismiss';
            removeBtn.textContent = '×';
            removeBtn.onclick = function(event) {
                event.stopPropagation();
                removeNotificationItem(item.id);
            };

            header.appendChild(title);
            header.appendChild(meta);
            header.appendChild(removeBtn);

            const typeLabel = document.createElement('div');
            typeLabel.className = 'notification-card-type';
            typeLabel.textContent = item.type || 'Info';

            const text = document.createElement('p');
            text.className = 'notification-card-text';
            text.textContent = item.text || '';

            card.appendChild(header);
            card.appendChild(typeLabel);
            if (item.text) {
                card.appendChild(text);
            }

            card.onclick = function() {
                if (item.link) {
                    window.location.href = item.link;
                }
                if (card.classList.contains('unread')) {
                    card.classList.remove('unread');
                    setNotificationsLastSeenAt();
                }
            };

            list.appendChild(card);
        });
    }

    function updateNotificationDot() {
        const dot = document.getElementById('notificationDot');
        if (!dot) {
            return;
        }
        dot.style.display = hasUnreadNotifications() ? 'block' : 'none';
    }

    function closeActivity() {
        const panel = document.getElementById('activityPanel');
        if (panel) {
            panel.classList.remove('active');
        }
    }

    function initNotifications() {
        const activityBtn = document.getElementById('activityBtn');
        if (activityBtn) {
            activityBtn.addEventListener('click', function() {
                const panel = document.getElementById('activityPanel');
                if (panel) {
                    panel.classList.add('active');
                    updateNotificationDot();
                }
            });
        }

        const clearBtn = document.getElementById('clearNotificationsBtn');
        if (clearBtn) {
            clearBtn.addEventListener('click', function(event) {
                event.stopPropagation();
                clearAllNotifications();
            });
        }

        window.closeActivity = closeActivity;
        renderNotificationsList();
        updateNotificationDot();
    }

    document.addEventListener('DOMContentLoaded', initNotifications);
})();
