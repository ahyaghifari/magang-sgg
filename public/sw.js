// Service worker untuk Web Push notification (portal magang).
// Diregistrasi dari resources/js/push-notifications.js dengan scope root ('/').

self.addEventListener('push', function (event) {
    if (!event.data) {
        return;
    }

    let payload = {};
    try {
        payload = event.data.json();
    } catch (e) {
        payload = { title: 'Notifikasi', body: event.data.text() };
    }

    const title = payload.title || 'Portal Magang';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/images/syifa-logo.png',
        badge: payload.badge || '/images/syifa-logo.png',
        data: { url: payload.url || '/' },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const url = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
            for (const client of windowClients) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
