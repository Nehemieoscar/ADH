<!-- Panneau des Notifications -->
<div id="notifications-panel" class="notifications-panel" style="display: none;">
    <div class="notifications-header">
        <h3>📬 Notifications</h3>
        <button onclick="closeNotifications()" style="background: none; border: none; cursor: pointer; font-size: 1.2rem;">✕</button>
    </div>
    
    <div class="notifications-list">
        <div id="notifications-container" style="max-height: 400px; overflow-y: auto;">
            <p style="text-align: center; padding: 2rem; color: #999;">Chargement...</p>
        </div>
    </div>
    
    <div class="notifications-footer" style="text-align: center; padding: 1rem; border-top: 1px solid #eee;">
        <a href="dashboard/dashboard.php" style="color: var(--couleur-primaire); text-decoration: none; font-weight: 600;">Voir toutes les notifications →</a>
    </div>
</div>

<style>
.notifications-panel {
    position: fixed;
    top: 60px;
    right: 20px;
    width: 380px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 1000;
    border: 1px solid #eee;
}

.notifications-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #eee;
}

.notifications-header h3 {
    margin: 0;
    font-size: 1.1rem;
}

.notifications-list {
    padding: 0;
}

.notification-item {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #eee;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    cursor: pointer;
    transition: background 0.2s;
}

.notification-item:hover {
    background: #f9f9f9;
}

.notification-item.unread {
    background: #f0f7ff;
}

.notification-badge {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--couleur-primaire);
    flex-shrink: 0;
    margin-top: 4px;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-content-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.3rem;
}

.notification-title {
    font-weight: 600;
    color: #333;
    margin: 0;
    font-size: 0.95rem;
}

.notification-time {
    font-size: 0.75rem;
    color: #999;
}

.notification-message {
    font-size: 0.85rem;
    color: #666;
    margin: 0.3rem 0 0 0;
    line-height: 1.4;
}

.notification-type {
    display: inline-block;
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: 3px;
    margin-top: 0.5rem;
}

.notification-type.info {
    background: #e7f3ff;
    color: #004085;
}

.notification-type.warning {
    background: #fff3cd;
    color: #856404;
}

.notification-type.error {
    background: #f8d7da;
    color: #721c24;
}

.notification-type.alert {
    background: #f8d7da;
    color: #721c24;
}

.notification-type.rappel {
    background: #d1ecf1;
    color: #0c5460;
}

.notification-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.notification-actions button {
    padding: 0.3rem 0.6rem;
    font-size: 0.75rem;
    border: none;
    border-radius: 3px;
    background: var(--couleur-primaire);
    color: white;
    cursor: pointer;
}

.notification-actions button:hover {
    opacity: 0.8;
}

.notification-empty {
    text-align: center;
    padding: 2rem;
    color: #999;
}

@media (max-width: 768px) {
    .notifications-panel {
        width: calc(100% - 40px);
        right: 20px;
    }
}
</style>

<script>
let notificationsLoaded = false;

function toggleNotifications() {
    const panel = document.getElementById('notifications-panel');
    if (panel.style.display === 'none' || panel.style.display === '') {
        panel.style.display = 'block';
        if (!notificationsLoaded) {
            loadNotifications();
        }
    } else {
        panel.style.display = 'none';
    }
}

function closeNotifications() {
    document.getElementById('notifications-panel').style.display = 'none';
}

function loadNotifications() {
    const container = document.getElementById('notifications-container');
    
    fetch('api/notifications.php?action=get_notifications&limit=10')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.notifications) {
                if (data.notifications.length === 0) {
                    container.innerHTML = '<div class="notification-empty">Aucune notification</div>';
                } else {
                    container.innerHTML = data.notifications.map(notif => `
                        <div class="notification-item ${notif.lu ? '' : 'unread'}">
                            ${!notif.lu ? '<div class="notification-badge"></div>' : '<div style="width: 12px;"></div>'}
                            <div class="notification-content">
                                <div class="notification-content-header">
                                    <h4 class="notification-title">${escapeHtml(notif.titre)}</h4>
                                    <span class="notification-time">${formatDate(notif.date_creation)}</span>
                                </div>
                                <p class="notification-message">${escapeHtml(notif.message)}</p>
                                <span class="notification-type ${notif.type}">${notif.type}</span>
                                <div class="notification-actions">
                                    ${!notif.lu ? `<button onclick="markAsRead(${notif.id})">Marquer comme lu</button>` : ''}
                                    <button onclick="deleteNotification(${notif.id})">Supprimer</button>
                                </div>
                            </div>
                        </div>
                    `).join('');
                }
                notificationsLoaded = true;
                updateNotificationBadge(data.unread_count);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            container.innerHTML = '<div class="notification-empty">Erreur de chargement</div>';
        });
}

function markAsRead(notificationId) {
    const formData = new FormData();
    formData.append('notification_id', notificationId);
    
    fetch('api/notifications.php?action=mark_as_read', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            notificationsLoaded = false;
            loadNotifications();
        }
    });
}

function deleteNotification(notificationId) {
    const formData = new FormData();
    formData.append('notification_id', notificationId);
    
    fetch('api/notifications.php?action=delete_notification', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            notificationsLoaded = false;
            loadNotifications();
        }
    });
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notification-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'inline';
        } else {
            badge.style.display = 'none';
        }
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'À l\'instant';
    if (diffMins < 60) return diffMins + ' min';
    if (diffHours < 24) return diffHours + 'h';
    if (diffDays < 7) return diffDays + 'j';
    return date.toLocaleDateString('fr-FR');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Charger les notifications au démarrage
document.addEventListener('DOMContentLoaded', () => {
    // Mettre à jour le badge de notifications non lues
    fetch('api/notifications.php?action=get_notifications&limit=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.unread_count);
            }
        });
    
    // Actualiser les notifications toutes les 30 secondes
    setInterval(() => {
        if (document.getElementById('notifications-panel').style.display !== 'none') {
            notificationsLoaded = false;
            loadNotifications();
        }
    }, 30000);
});

// Fermer le panneau en cliquant en dehors
document.addEventListener('click', (e) => {
    const panel = document.getElementById('notifications-panel');
    const btn = document.getElementById('notification-btn');
    if (panel && btn && !panel.contains(e.target) && !btn.contains(e.target)) {
        panel.style.display = 'none';
    }
});
</script>
