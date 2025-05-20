let notificationCheckInterval;

function startNotificationPolling() {
    // Verificar cada 30 segundos
    notificationCheckInterval = setInterval(fetchNotifications, 30000);
    // Ejecutar inmediatamente al cargar
    fetchNotifications();
}

function fetchNotifications() {
    fetch('/api/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }
            updateNotificationBadge(data.length);
            renderNotifications(data);
        });
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notification-badge');
    if (badge) {
        badge.textContent = count > 0 ? count : '';
        badge.style.display = count > 0 ? 'block' : 'none';
    }
}

function renderNotifications(notifications) {
    const container = document.getElementById('notifications-container');
    if (!container) return;

    container.innerHTML = notifications.map(notif => `
        <div class="notification-item" data-id="${notif.id}">
            <strong>${notif.title}</strong>
            <p>${notif.message}</p>
            <small>${new Date(notif.created_at).toLocaleString()}</small>
        </div>
    `).join('');

    // Marcar como leído al hacer clic
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            const notifId = this.getAttribute('data-id');
            markAsRead(notifId);
            this.remove();
            updateNotificationBadge(parseInt(document.getElementById('notification-badge').textContent || 0 - 1));
        });
    });
}

function markAsRead(notificationId) {
    fetch('/api/mark_as_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: notificationId })
    });
}

// Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', startNotificationPolling);