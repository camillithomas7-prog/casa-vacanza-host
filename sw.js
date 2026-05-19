// Service Worker for Patrizia Mancini Casa Vacanza
// Handles push notifications and notification clicks.

const SW_VERSION = 'v4';
const ICON = '/assets/logo-512.png?v=2';
const BADGE = '/assets/logo-192.png?v=2';

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
  // IMPORTANTE: dobbiamo SEMPRE chiamare showNotification, anche se il payload
  // è vuoto o malformato. Se il browser riceve un push e non vede una notifica
  // visibile, dopo qualche tentativo annulla la subscription.
  const showFallback = () => self.registration.showNotification('Casa Vacanza', {
    body: 'Hai una nuova notifica',
    icon: ICON,
    badge: BADGE,
    tag: 'cv-notif',
    renotify: true,
    requireInteraction: true,
    vibrate: [200, 100, 200],
    data: { link: '/admin/notifiche.php' },
  });

  let payload = null;
  if (event.data) {
    try { payload = event.data.json(); }
    catch (e) {
      try { payload = { body: event.data.text() }; } catch (e2) {}
    }
  }

  if (!payload || typeof payload !== 'object') {
    event.waitUntil(showFallback());
    return;
  }

  const title = payload.title || 'Casa Vacanza';
  const options = {
    body: payload.body || 'Hai una nuova notifica',
    icon: ICON,
    badge: BADGE,
    // Tag univoco: con payload.id ogni notifica è separata (iOS le mostra tutte
    // invece di sostituire la precedente). Fallback al type se manca l'id.
    tag: payload.id || payload.type || ('cv-' + Date.now()),
    renotify: true,
    // requireInteraction = true: la notifica resta finché l'utente non la
    // tocca. Importante su Android perché altrimenti schermo spento + schermata
    // bloccata può farla scomparire troppo presto.
    requireInteraction: true,
    vibrate: [200, 100, 200],
    data: {
      link: payload.link || '/admin/notifiche.php',
      id: payload.id || null,
      type: payload.type || null,
    },
  };

  event.waitUntil(
    self.registration.showNotification(title, options).catch(() => showFallback())
  );
});

// Quando la subscription viene rinnovata dal browser (o invalidata), rifa il
// subscribe e la rimanda al server. Senza questo handler, dopo un cambio di
// endpoint il telefono smette di ricevere push.
self.addEventListener('pushsubscriptionchange', (event) => {
  event.waitUntil((async () => {
    try {
      const r = await fetch('/api/push-vapid-public.php', { credentials: 'include' });
      const j = await r.json();
      if (!j.publicKey) return;
      const sub = await self.registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: Uint8Array.from(atob(j.publicKey.replace(/-/g,'+').replace(/_/g,'/')), c => c.charCodeAt(0)),
      });
      await fetch('/api/push-subscribe.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(sub),
      });
    } catch (e) {}
  })());
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
