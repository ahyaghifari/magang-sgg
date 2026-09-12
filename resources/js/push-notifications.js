// Aktivasi Web Push notification. Tombol "Aktifkan Notifikasi" di halaman
// Tugas intern memanggil window.enablePushNotifications() lewat gesture klik
// (izin browser wajib dipicu dari interaksi user, tidak bisa otomatis saat load).

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

async function sendSubscriptionToServer(subscription) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    await fetch('/push/subscribe', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            Accept: 'application/json',
        },
        body: JSON.stringify(subscription),
    });
}

window.enablePushNotifications = async function () {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        alert('Browser ini tidak mendukung notifikasi push.');
        return false;
    }

    try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            return false;
        }

        const vapidPublicKey = document.querySelector('meta[name="vapid-public-key"]')?.getAttribute('content');
        if (!vapidPublicKey) {
            return false;
        }

        const registration = await navigator.serviceWorker.register('/sw.js');
        await navigator.serviceWorker.ready;

        let subscription = await registration.pushManager.getSubscription();
        if (!subscription) {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });
        }

        await sendSubscriptionToServer(subscription);

        return true;
    } catch (e) {
        console.error('Gagal mengaktifkan notifikasi push', e);
        return false;
    }
};

// Registrasi service worker lebih awal (tanpa minta izin) supaya siap dipakai
// begitu user menekan tombol aktifkan.
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(function () {});
}
