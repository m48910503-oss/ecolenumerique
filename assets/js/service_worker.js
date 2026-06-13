/* global self, clients */

'use strict';

let lastHandledNotificationId = 0;

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
  event.waitUntil(handlePushEvent(event));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const target = event.notification && event.notification.data && event.notification.data.url
    ? String(event.notification.data.url)
    : 'notifications';
  event.waitUntil(openOrFocus(target));
});

async function handlePushEvent(event) {
  let payload = null;
  try {
    if (event.data) {
      payload = event.data.json();
    }
  } catch (err) {
    payload = null;
  }

  if (payload && typeof payload === 'object') {
    await showPayloadNotification(payload);
    return;
  }

  const fetched = await fetchLatestNotification();
  if (fetched) {
    await showPayloadNotification(fetched);
  }
}

async function fetchLatestNotification() {
  try {
    const afterId = Number.isFinite(lastHandledNotificationId) ? Math.max(0, lastHandledNotificationId) : 0;
    const response = await fetch(`api/notifications_feed?after_id=${afterId}&limit=10`, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    });
    if (!response.ok) {
      return null;
    }
    const payload = await response.json();
    if (!payload || payload.ok !== true || !Array.isArray(payload.items) || payload.items.length === 0) {
      return null;
    }

    const latest = payload.items[payload.items.length - 1] || null;
    if (!latest) {
      return null;
    }

    const latestId = Number.parseInt(String(latest.id || '0'), 10);
    if (Number.isFinite(latestId) && latestId > 0) {
      lastHandledNotificationId = Math.max(lastHandledNotificationId, latestId);
    }

    return latest;
  } catch (err) {
    return null;
  }
}

async function showPayloadNotification(payload) {
  const id = Number.parseInt(String(payload.id || '0'), 10);
  if (Number.isFinite(id) && id > 0) {
    lastHandledNotificationId = Math.max(lastHandledNotificationId, id);
  }

  const title = normalizeText(payload.title, 'Nouvelle notification');
  const body = normalizeText(payload.body, 'Vous avez une nouvelle notification.');
  const url = normalizeUrl(payload.url || 'notifications');

  await self.registration.showNotification(title, {
    body,
    tag: Number.isFinite(id) && id > 0 ? `notif-${id}` : 'notif-generic',
    data: { url },
  });
}

function normalizeText(value, fallback) {
  const text = typeof value === 'string' ? value.trim() : '';
  return text !== '' ? text : fallback;
}

function normalizeUrl(value) {
  const raw = typeof value === 'string' ? value.trim() : '';
  if (raw === '') {
    return 'notifications';
  }
  if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('/')) {
    return raw;
  }
  return raw.replace(/^\/+/, '');
}

async function openOrFocus(targetUrl) {
  const absolute = new URL(targetUrl, self.registration.scope).href;
  const wins = await clients.matchAll({ type: 'window', includeUncontrolled: true });
  for (const win of wins) {
    try {
      const samePath = new URL(win.url).pathname === new URL(absolute).pathname;
      if (samePath && 'focus' in win) {
        await win.focus();
        win.postMessage({ type: 'notification-click', url: absolute });
        return;
      }
    } catch (err) {
      // Ignore malformed URL edge cases.
    }
  }
  if (clients.openWindow) {
    await clients.openWindow(absolute);
  }
}
