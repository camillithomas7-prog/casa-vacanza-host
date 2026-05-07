// Service Worker for Patrizia Mancini Casa Vacanza
// Handles push notifications and notification clicks.

const SW_VERSION = 'v1';
const ICON = '/assets/logo-512.png?v=2';
const BADGE = '/assets/logo-192.png?v=2';

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
  let payload = { title: 'Casa Vacanza', body: 'Hai una nuova notifica', link: '/admin/notifiche.php' };
  if (event.data) {
    try { payload = Object.assign(payload, event.data.json()); }
    catch (e) { try { payload.body = event.data.text(); } catch (e2) {} }
  }

  const options = {
    body: payload.body,
    icon: ICON,
    badge: BADGE,
    tag: payload.type || 'cv-notif',
    renotify: true,
    requireInteraction: false,
    vibrate: [100, 50, 100],
    data: {
      link: payload.link || '/admin/notifiche.php',
      id: payload.id || null,
      type: payload.type || null,
    },
  };

  event.waitUntil(self.registration.showNotification(payload.title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const targetUrl = (event.notification.data && event.notification.data.link) || '/admin/notifiche.php';
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        try {
          const url = new URL(client.url);
          if (url.origin === self.location.origin) {
            return client.focus().then((c) => c.navigate(targetUrl)).catch(() => {
              return self.clients.openWindow(targetUrl);
            });
          }
        } catch (e) {}
      }
      return self.clients.openWindow(targetUrl);
    })
  );
});
