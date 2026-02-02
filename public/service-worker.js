self.addEventListener('push', function (event) {
  let payload = null;

  // 1) Try JSON first (most push payloads are JSON)
  try {
    payload = event.data ? event.data.json() : null;
  } catch (e) {
    // Not JSON, try text
    try {
      payload = event.data ? { body: event.data.text() } : null;
    } catch (e2) {
      payload = null;
    }
  }

  // If no payload, do nothing (or show a default notification if you prefer)
  if (!payload) return;
  
  const title = payload.title || 'MISO';
  const options = {
    body: payload.body || '',
    icon: payload.icon || '/icons/icon-192.png',
    badge: payload.badge || '/icons/icon-192.png',
    data: payload.data || {} // e.g. { url: "/chats/123" }
  };

  event.waitUntil(self.registration.showNotification(title, options));
});
console.debug('SW scope origin:', self.location.origin);
self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const targetUrl = event.notification?.data?.url;
  if (!targetUrl) return;

  const url = new URL(targetUrl).href;

  event.waitUntil((async () => {
    // Try focus first (cheap)
    const list = await clients.matchAll({ type: 'window', includeUncontrolled: true });

    // If you have any window at all, focus it and stop trying to open windows
    if (list.length) {
      const client = list.find(c => c.visibilityState === 'visible') || list[0];
      if (client?.focus) await client.focus();
      // optionally navigate (may fail, but doesn’t throw InvalidAccessError)
      if (client?.navigate) return client.navigate(url);
      return;
    }

    // No window exists, open immediately
    return clients.openWindow(url);
  })());
});


