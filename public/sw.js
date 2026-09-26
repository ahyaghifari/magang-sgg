// Service worker untuk Web Push notification (portal magang).
// Diregistrasi dari resources/js/push-notifications.js dengan scope root ('/').

// Versi baru service worker langsung aktif tanpa menunggu semua tab portal ditutup.
self.addEventListener('install', function () {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

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
        // icon = gambar besar (foto profil pengirim / logo); badge = siluet putih kecil di status bar Android.
        icon: payload.icon || '/images/app-icon-192.png',
        badge: payload.badge || '/images/notif-badge.png',
        // WebPushMessage::data() dikirim sebagai payload.data — url ada di dalamnya.
        data: { url: (payload.data && payload.data.url) || payload.url || '/' },
        // Tombol aksi (mis. "Lihat Tugas" / "Nanti") — tampil di Android & Windows, diabaikan di iOS.
        actions: Array.isArray(payload.actions) ? payload.actions : [],
        vibrate: payload.vibrate || [120, 60, 120],
        timestamp: Date.now(),
        tag: payload.tag,
        renotify: Boolean(payload.tag && payload.renotify),
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    // Tombol "Nanti" cukup menutup notifikasi.
    if (event.action === 'dismiss') {
        return;
    }

    const url = new URL((event.notification.data && event.notification.data.url) || '/', self.location.origin).href;

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
