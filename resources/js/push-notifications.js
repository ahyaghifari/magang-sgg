// Aktivasi Web Push notification. Tombol "Aktifkan Notifikasi" (halaman Tugas intern &
// pembimbing) memanggil window.enablePushNotifications() lewat gesture klik — izin browser
// wajib dipicu dari interaksi user, tidak bisa otomatis saat load.
//
// Selain itu, setiap halaman dibuka dan izin SUDAH diberikan, subscription browser ini
// disinkron ulang ke server secara diam-diam (lihat syncPushSubscription()). Ini menambal
// kasus notifikasi "sudah diaktifkan tapi tidak pernah muncul": subscription di server
// terhapus karena kedaluwarsa, browser memperbarui endpoint-nya, atau kunci VAPID server
// diganti sehingga subscription lama tidak lagi valid.

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

function vapidPublicKey() {
    return document.querySelector('meta[name="vapid-public-key"]')?.getAttribute('content') || '';
}

function isIos() {
    return /iphone|ipad|ipod/i.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
}

function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
}

function pushSupported() {
    return 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
}

/** Apakah subscription dibuat dengan kunci VAPID yang sama dengan kunci server sekarang. */
function sameServerKey(subscription, key) {
    const current = subscription.options?.applicationServerKey;
    if (!current) {
        return true; // Browser tidak mengekspos kuncinya — anggap sama.
    }
    const a = new Uint8Array(current);
    const b = urlBase64ToUint8Array(key);
    return a.length === b.length && a.every((v, i) => v === b[i]);
}

async function sendSubscriptionToServer(subscription) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const response = await fetch('/push/subscribe', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            Accept: 'application/json',
        },
        body: JSON.stringify({
            ...subscription.toJSON(),
            contentEncoding: (PushManager.supportedContentEncodings || ['aes128gcm'])[0],
        }),
    });

    if (!response.ok) {
        throw new Error('Server menolak subscription (HTTP ' + response.status + ')');
    }
}

/** Ambil subscription yang valid untuk kunci VAPID sekarang (buat baru kalau belum ada/kuncinya beda). */
async function getValidSubscription(registration, key) {
    let subscription = await registration.pushManager.getSubscription();

    if (subscription && !sameServerKey(subscription, key)) {
        await subscription.unsubscribe();
        subscription = null;
    }

    if (!subscription) {
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(key),
        });
    }

    return subscription;
}

/** Sinkron ulang diam-diam — hanya kalau izin sudah diberikan (tidak pernah memunculkan prompt). */
async function syncPushSubscription() {
    const key = vapidPublicKey();
    if (!pushSupported() || Notification.permission !== 'granted' || !key) {
        return false;
    }

    try {
        const registration = await navigator.serviceWorker.register('/sw.js');
        await navigator.serviceWorker.ready;
        const subscription = await getValidSubscription(registration, key);
        await sendSubscriptionToServer(subscription);
        return true;
    } catch (e) {
        console.warn('Sinkron subscription push gagal', e);
        return false;
    }
}

let syncPromise = null;

/** Dipakai tombol "Aktifkan Notifikasi" untuk menyembunyikan diri kalau notifikasi sudah aktif. */
window.pushNotificationsActive = function () {
    syncPromise ??= syncPushSubscription();
    return syncPromise;
};

window.enablePushNotifications = async function () {
    if (isIos() && !isStandalone()) {
        alert('Di iPhone/iPad, notifikasi hanya bisa aktif kalau portal dibuka dari Home Screen.\n\n'
            + 'Caranya: buka portal di Safari → tombol Bagikan (kotak dengan panah ke atas) → '
            + '"Tambah ke Layar Utama" → buka portal dari ikon itu, lalu tekan "Aktifkan Notifikasi" lagi.');
        return false;
    }

    if (!pushSupported()) {
        alert('Browser ini tidak mendukung notifikasi push. Coba pakai Chrome (Android/komputer) atau Safari dari Home Screen (iPhone).');
        return false;
    }

    const key = vapidPublicKey();
    if (!key) {
        alert('Notifikasi belum dikonfigurasi di server (kunci VAPID kosong). Hubungi admin.');
        return false;
    }

    try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            alert(permission === 'denied'
                ? 'Izin notifikasi diblokir. Buka pengaturan situs di browser (ikon gembok di address bar) → izinkan Notifikasi, lalu coba lagi.'
                : 'Izin notifikasi belum diberikan.');
            return false;
        }

        const registration = await navigator.serviceWorker.register('/sw.js');
        await navigator.serviceWorker.ready;
        const subscription = await getValidSubscription(registration, key);
        await sendSubscriptionToServer(subscription);

        syncPromise = Promise.resolve(true);

        return true;
    } catch (e) {
        console.error('Gagal mengaktifkan notifikasi push', e);
        alert('Gagal mengaktifkan notifikasi: ' + (e?.message || e));
        return false;
    }
};

// Registrasi service worker lebih awal (tanpa minta izin), lalu sinkron ulang subscription
// kalau izin sudah pernah diberikan.
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(function () {});
    window.pushNotificationsActive();
}
