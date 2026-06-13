(function () {
  'use strict';

  var configEl = document.getElementById('global-push-data');
  if (!configEl) {
    return;
  }

  var config = {};
  try {
    config = JSON.parse(configEl.textContent || '{}');
  } catch (e) {
    return;
  }

  var feedUrl = typeof config.feed_url === 'string' ? config.feed_url : '';
  var fallbackUrl = typeof config.fallback_url === 'string' ? config.fallback_url : '/notifications';
  var userId = Number.parseInt(String(config.user_id || '0'), 10);
  var pollInterval = Number.parseInt(String(config.poll_interval_ms || '20000'), 10);
  var webPushEnabled = config.webpush_enabled === true;
  var vapidPublicKey = typeof config.vapid_public_key === 'string' ? config.vapid_public_key.trim() : '';
  var pushSubscriptionUrl = typeof config.push_subscription_url === 'string' ? config.push_subscription_url : '';
  var serviceWorkerUrl = typeof config.service_worker_url === 'string' ? config.service_worker_url : '';
  var csrfToken = typeof config.csrf_token === 'string' ? config.csrf_token : '';

  if (!feedUrl || !Number.isFinite(userId) || userId < 1 || typeof window.fetch !== 'function') {
    return;
  }

  if (!Number.isFinite(pollInterval) || pollInterval < 800) {
    pollInterval = 2500;
  }

  var storageKey = 'push_notif_last_id_u' + String(userId);
  var permissionPromptKey = 'push_notif_perm_prompted_u' + String(userId);
  var lastId = 0;
  try {
    var rawLastId = window.localStorage.getItem(storageKey);
    if (rawLastId) {
      var parsed = Number.parseInt(rawLastId, 10);
      if (Number.isFinite(parsed) && parsed > 0) {
        lastId = parsed;
      }
    }
  } catch (e) {
    lastId = 0;
  }

  var stopped = false;
  var inFlight = false;
  var timerId = null;
  var swRegistration = null;
  var webPushActive = false;
  var latestUnreadCount = 0;
  var latestSupportUnreadCount = 0;

  function toAbsoluteUrl(urlValue) {
    if (typeof urlValue !== 'string' || urlValue.trim() === '') {
      return fallbackUrl;
    }
    try {
      return new URL(urlValue, window.location.origin).toString();
    } catch (e) {
      return fallbackUrl;
    }
  }

  function persistLastId() {
    try {
      window.localStorage.setItem(storageKey, String(lastId));
    } catch (e) {
      // Ignore storage errors.
    }
  }

  function updateBadgeList(selector, count) {
    var nodes = document.querySelectorAll(selector);
    if (!nodes || nodes.length === 0) {
      return;
    }
    for (var i = 0; i < nodes.length; i += 1) {
      var el = nodes[i];
      el.textContent = String(count);
      if (count > 0) {
        el.classList.remove('hidden');
      } else {
        el.classList.add('hidden');
      }
    }
  }

  function updateCountList(selector, count) {
    var nodes = document.querySelectorAll(selector);
    if (!nodes || nodes.length === 0) {
      return;
    }
    for (var i = 0; i < nodes.length; i += 1) {
      nodes[i].textContent = String(count);
    }
  }

  function emitLiveEvent(items, unreadCount, supportUnreadCount) {
    try {
      window.dispatchEvent(new window.CustomEvent('app:notifications-live', {
        detail: {
          items: Array.isArray(items) ? items : [],
          unreadCount: unreadCount,
          supportUnreadCount: supportUnreadCount,
          lastId: lastId,
        },
      }));
    } catch (e) {
      // Ignore custom event errors.
    }
  }

  function syncLiveCounters(unreadCount, supportUnreadCount, items) {
    latestUnreadCount = unreadCount;
    latestSupportUnreadCount = supportUnreadCount;

    updateBadgeList('[data-live-notif-badge]', unreadCount);
    updateBadgeList('[data-live-support-badge]', supportUnreadCount);
    updateBadgeList('[data-live-user-menu-total-badge]', unreadCount + supportUnreadCount);
    updateCountList('[data-live-notif-count]', unreadCount);
    updateCountList('[data-live-support-count]', supportUnreadCount);
    emitLiveEvent(items, unreadCount, supportUnreadCount);
  }

  function showNotification(item) {
    if (webPushActive) {
      return;
    }
    if (!('Notification' in window) || Notification.permission !== 'granted') {
      return;
    }
    var notifId = Number.parseInt(String(item.id || '0'), 10);
    var title = String(item.title || '').trim() || 'Nouvelle notification';
    var body = String(item.body || '').trim();
    var targetUrl = toAbsoluteUrl(String(item.url || ''));

    var instance = null;
    try {
      instance = new Notification(title, {
        body: body,
        tag: notifId > 0 ? ('notif-' + String(notifId)) : undefined,
      });
    } catch (e) {
      return;
    }

    instance.onclick = function () {
      try {
        window.focus();
      } catch (e) {
        // Ignore focus errors.
      }
      window.location.href = targetUrl;
      instance.close();
    };

    window.setTimeout(function () {
      try {
        instance.close();
      } catch (e) {
        // Ignore close errors.
      }
    }, 5000);
  }

  function maybePromptPermissionOnce() {
    if (!('Notification' in window)) {
      return;
    }
    if (Notification.permission !== 'default') {
      return;
    }
    try {
      if (window.sessionStorage.getItem(permissionPromptKey) === '1') {
        return;
      }
    } catch (e) {
      // Ignore session storage errors.
    }

    var ask = function () {
      try {
        window.sessionStorage.setItem(permissionPromptKey, '1');
      } catch (e) {
        // Ignore session storage errors.
      }
      Notification.requestPermission()
        .then(function (permission) {
          if (permission === 'granted') {
            ensurePushSubscription();
          } else if (permission === 'denied') {
            unsubscribeIfNeeded();
          }
        })
        .catch(function () {
          // Ignore permission prompt errors.
        });
    };

    document.addEventListener('click', ask, { once: true, capture: true });
    document.addEventListener('keydown', ask, { once: true, capture: true });
  }

  async function sendSubscription(action, subscription) {
    if (!pushSubscriptionUrl || !csrfToken) {
      return false;
    }
    try {
      var response = await window.fetch(pushSubscriptionUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-Token': csrfToken,
        },
        body: JSON.stringify({
          _csrf: csrfToken,
          action: action,
          subscription: subscription,
        }),
      });
      return response.ok;
    } catch (e) {
      return false;
    }
  }

  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var raw = window.atob(base64);
    var output = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i += 1) {
      output[i] = raw.charCodeAt(i);
    }
    return output;
  }

  async function ensurePushSubscription() {
    if (!webPushEnabled || !swRegistration || !vapidPublicKey || !('PushManager' in window) || !('Notification' in window)) {
      return;
    }
    if (Notification.permission !== 'granted') {
      return;
    }

    try {
      var subscription = await swRegistration.pushManager.getSubscription();
      if (!subscription) {
        subscription = await swRegistration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
        });
      }

      var payload = subscription.toJSON ? subscription.toJSON() : null;
      if (!payload) {
        return;
      }

      var sent = await sendSubscription('subscribe', payload);
      if (sent) {
        webPushActive = true;
      }
    } catch (e) {
      // Push subscription may fail on insecure origins or unsupported browsers.
    }
  }

  async function unsubscribeIfNeeded() {
    if (!swRegistration || !('PushManager' in window)) {
      return;
    }
    try {
      var subscription = await swRegistration.pushManager.getSubscription();
      if (!subscription) {
        return;
      }
      var payload = subscription.toJSON ? subscription.toJSON() : null;
      if (payload) {
        await sendSubscription('unsubscribe', payload);
      }
      await subscription.unsubscribe();
      webPushActive = false;
    } catch (e) {
      // Ignore unsubscribe issues.
    }
  }

  async function initServiceWorker() {
    if (!webPushEnabled || !serviceWorkerUrl || !('serviceWorker' in navigator)) {
      return;
    }
    try {
      var swScope = './';
      try {
        var scopeUrl = new URL(serviceWorkerUrl, window.location.origin);
        swScope = scopeUrl.pathname.replace(/[^/]+$/, '');
      } catch (e) {
        swScope = './';
      }
      swRegistration = await navigator.serviceWorker.register(serviceWorkerUrl, { scope: swScope });

      navigator.serviceWorker.addEventListener('message', function (event) {
        var data = event && event.data ? event.data : null;
        if (!data || data.type !== 'notification-click') {
          return;
        }
        if (typeof data.url === 'string' && data.url.trim() !== '') {
          window.location.href = data.url;
        }
      });

      if ('Notification' in window && Notification.permission === 'granted') {
        await ensurePushSubscription();
      } else if ('Notification' in window && Notification.permission === 'denied') {
        await unsubscribeIfNeeded();
      }
    } catch (e) {
      // Ignore service worker registration errors.
    }
  }

  async function poll(bootstrap) {
    if (stopped || inFlight) {
      return;
    }
    inFlight = true;
    try {
      var url = new URL(feedUrl, window.location.origin);
      url.searchParams.set('after_id', String(lastId));
      if (bootstrap) {
        url.searchParams.set('bootstrap', '1');
      }
      url.searchParams.set('limit', '20');

      var response = await window.fetch(url.toString(), {
        method: 'GET',
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      });

      if (response.status === 401 || response.status === 403) {
        stopped = true;
        if (timerId !== null) {
          window.clearInterval(timerId);
        }
        return;
      }
      if (!response.ok) {
        return;
      }

      var payload = await response.json();
      if (!payload || payload.ok !== true) {
        return;
      }

      var items = Array.isArray(payload.items) ? payload.items : [];
      var unreadCount = Number.parseInt(String(payload.unread_count || '0'), 10);
      if (!Number.isFinite(unreadCount) || unreadCount < 0) {
        unreadCount = latestUnreadCount;
      }
      var supportUnreadCount = Number.parseInt(String(payload.support_unread_count || '0'), 10);
      if (!Number.isFinite(supportUnreadCount) || supportUnreadCount < 0) {
        supportUnreadCount = latestSupportUnreadCount;
      }
      syncLiveCounters(unreadCount, supportUnreadCount, items);

      var hasLatestDbId = Object.prototype.hasOwnProperty.call(payload, 'latest_db_id');
      var latestDbId = Number.parseInt(String(payload.latest_db_id || '0'), 10);
      if (!bootstrap && items.length === 0 && hasLatestDbId && Number.isFinite(latestDbId) && latestDbId >= 0 && latestDbId < lastId) {
        lastId = latestDbId;
        persistLastId();
        return;
      }

      if (!bootstrap && items.length > 0) {
        for (var i = 0; i < items.length; i += 1) {
          var item = items[i];
          var itemId = Number.parseInt(String(item.id || '0'), 10);
          if (Number.isFinite(itemId) && itemId > lastId) {
            lastId = itemId;
          }
          showNotification(item);
        }
      }

      var latestId = Number.parseInt(String(payload.latest_id || '0'), 10);
      if (Number.isFinite(latestId) && latestId > lastId) {
        lastId = latestId;
      }
      persistLastId();

      if (payload.has_more === true && !bootstrap) {
        window.setTimeout(function () {
          poll(false);
        }, 400);
      }
    } catch (e) {
      // Ignore network errors and retry on next tick.
    } finally {
      inFlight = false;
    }
  }

  maybePromptPermissionOnce();
  initServiceWorker();

  poll(lastId <= 0);
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) {
      poll(false);
    }
  });
  window.addEventListener('focus', function () {
    poll(false);
  });
  timerId = window.setInterval(function () {
    poll(false);
  }, pollInterval);
})();
