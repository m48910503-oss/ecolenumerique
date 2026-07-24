// assets/js/notifications_page.js
(function () {
  'use strict';

  var urlPattern = /((https?:\/\/|www\.)[^\s<]+)/gi;
  var localToastContainer = null;
  var TOAST_DURATION_MS = 3200;
  var TOAST_FADE_MS = 220;
  var TOAST_QUEUE_KEY = 'teacher_notifications_toast';
  var currentView = String((document.body && document.body.getAttribute('data-view')) || '').trim();

  function logToast() {
    if (!window.console || typeof window.console.log !== 'function') {
      return;
    }

    var args = Array.prototype.slice.call(arguments);
    args.unshift('[teacher/notifications toast]');
    window.console.log.apply(window.console, args);
  }

  function normalizeUrl(raw) {
    var value = String(raw || '').trim();
    if (!value) return '';
    if (/^https?:\/\//i.test(value)) return value;
    if (/^www\./i.test(value)) return 'https://' + value;
    return '';
  }

  function ensureLocalToastContainer() {
    if (localToastContainer && document.body.contains(localToastContainer)) {
      return localToastContainer;
    }

    var container = document.querySelector('[data-notification-toast-container]');
    if (container instanceof HTMLElement) {
      localToastContainer = container;
      return container;
    }

    container = document.createElement('div');
    container.setAttribute('data-notification-toast-container', '1');
    container.style.position = 'fixed';
    container.style.top = 'calc(var(--teacher-topbar-height, 4.5rem) + 0.75rem)';
    container.style.left = '50%';
    container.style.transform = 'translateX(-50%)';
    container.style.zIndex = '9999';
    container.style.display = 'flex';
    container.style.flexDirection = 'column';
    container.style.gap = '0.5rem';
    container.style.width = 'min(42rem, calc(100vw - 1rem))';
    container.style.pointerEvents = 'none';

    if (window.matchMedia && window.matchMedia('(max-width: 640px)').matches) {
      container.style.top = '4.75rem';
      container.style.width = 'calc(100vw - 0.75rem)';
    }

    document.body.appendChild(container);
    localToastContainer = container;
    return container;
  }

  function createLocalToast(message, type) {
    var text = String(message || '').trim();
    if (!text) return;

    logToast('create', { message: text, type: type || 'info' });

    var tone = String(type || 'info').toLowerCase();
    var palette = {
      success: {
        title: 'Succès',
        icon: 'fas fa-circle-check',
        variant: 'success'
      },
      error: {
        title: 'Erreur',
        icon: 'fas fa-circle-exclamation',
        variant: 'error'
      },
      warning: {
        title: 'Attention',
        icon: 'fas fa-triangle-exclamation',
        variant: 'warning'
      },
      info: {
        title: 'Info',
        icon: 'fas fa-circle-info',
        variant: 'info'
      }
    };
    var theme = palette[tone] || palette.info;
    var existing = document.querySelector('[data-notification-local-toast="1"]');
    if (existing && existing.parentNode) {
      existing.parentNode.removeChild(existing);
    }

    var toast = document.createElement('div');
    toast.dataset.notificationLocalToast = '1';
    toast.dataset.notificationToastView = currentView || 'unknown';
    toast.className = 'teacher-withdrawal-toast teacher-withdrawal-toast--' + theme.variant;
    toast.setAttribute('role', tone === 'error' ? 'alert' : 'status');
    toast.setAttribute('aria-live', tone === 'error' ? 'assertive' : 'polite');

    var copyWrap = document.createElement('div');
    copyWrap.className = 'teacher-withdrawal-toast__copy';

    var icon = document.createElement('span');
    icon.className = 'teacher-withdrawal-toast__icon';
    icon.setAttribute('aria-hidden', 'true');

    var iconEl = document.createElement('i');
    iconEl.className = theme.icon;
    icon.appendChild(iconEl);

    var body = document.createElement('div');
    body.className = 'min-w-0';

    var title = document.createElement('p');
    title.className = 'teacher-withdrawal-toast__title';
    title.textContent = theme.title;

    var messageEl = document.createElement('p');
    messageEl.className = 'teacher-withdrawal-toast__message';
    messageEl.textContent = text;

    body.appendChild(title);
    body.appendChild(messageEl);
    copyWrap.appendChild(icon);
    copyWrap.appendChild(body);

    var barWrap = document.createElement('span');
    barWrap.className = 'teacher-withdrawal-toast__bar';
    barWrap.setAttribute('aria-hidden', 'true');

    toast.appendChild(copyWrap);
    toast.appendChild(barWrap);
    document.body.appendChild(toast);

    window.setTimeout(function () {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, TOAST_DURATION_MS + TOAST_FADE_MS + 180);
  }

  function readQueuedToast() {
    try {
      var raw = window.sessionStorage ? window.sessionStorage.getItem(TOAST_QUEUE_KEY) : null;
      if (!raw) return null;
      logToast('read queued raw', raw);
      if (window.sessionStorage) {
        window.sessionStorage.removeItem(TOAST_QUEUE_KEY);
      }
      var parsed = JSON.parse(raw);
      if (!parsed || typeof parsed !== 'object') {
        return null;
      }
      var message = String(parsed.message || '').trim();
      if (!message) {
        return null;
      }
      logToast('read queued parsed', parsed);
      return {
        message: message,
        type: String(parsed.type || 'success').trim() || 'success'
      };
    } catch (_error) {
      return null;
    }
  }

  function queueToast(message, type) {
    try {
      if (!window.sessionStorage) {
        logToast('queue skipped: sessionStorage unavailable');
        return;
      }
      logToast('queue', { message: String(message || '').trim(), type: String(type || 'success').trim() || 'success' });
      window.sessionStorage.setItem(TOAST_QUEUE_KEY, JSON.stringify({
        message: String(message || '').trim(),
        type: String(type || 'success').trim() || 'success'
      }));
    } catch (_error) {
      /* ignore */
    }
  }

  function queueToastForForm(form) {
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    var actionInput = form.querySelector('input[name="action"]');
    if (!(actionInput instanceof HTMLInputElement)) {
      return;
    }

    var action = String(actionInput.value || '').trim();
    if (action === 'mark_read') {
      logToast('form action mark_read');
      queueToast('Notification marquée comme lue.', 'success');
    } else if (action === 'mark_unread') {
      logToast('form action mark_unread');
      queueToast('Notification marquée comme non lue.', 'success');
    } else if (action === 'mark_all_read') {
      logToast('form action mark_all_read');
      queueToast('Notifications marquées comme lues.', 'success');
    } else if (action === 'archive') {
      logToast('form action archive');
      queueToast('Notification archivée.', 'success');
    } else if (action === 'restore') {
      logToast('form action restore');
      queueToast('Notification restaurée.', 'success');
    } else if (action === 'mark_important') {
      logToast('form action mark_important');
      queueToast('Notification marquée comme importante.', 'success');
    } else if (action === 'clear_importance') {
      logToast('form action clear_importance');
      queueToast("Importance retirée de la notification.", 'success');
    } else if (action === 'delete') {
      logToast('form action delete');
      queueToast('Notification supprimée.', 'success');
    }
  }

  function showToast(message, type) {
    var text = String(message || '').trim();
    if (!text) return;
    createLocalToast(text, type || 'info');
  }

  function copyText(value) {
    if (!value) return Promise.resolve(false);
    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
      return navigator.clipboard
        .writeText(value)
        .then(function () {
          return true;
        })
        .catch(function () {
          return false;
        });
    }

    var area = document.createElement('textarea');
    area.value = value;
    area.setAttribute('readonly', 'readonly');
    area.style.position = 'absolute';
    area.style.left = '-9999px';
    document.body.appendChild(area);
    area.select();
    var ok = false;
    try {
      ok = document.execCommand('copy');
    } catch (e) {
      ok = false;
    }
    document.body.removeChild(area);
    return Promise.resolve(ok);
  }

  function getReadableLinkLabel(safeUrl) {
    try {
      var parsed = new URL(safeUrl);
      var host = parsed.hostname.replace(/^www\./i, '');
      var path = parsed.pathname.toLowerCase();

      if (host === 'docs.google.com') {
        if (path.indexOf('/presentation/') !== -1) return 'Présentation Google';
        if (path.indexOf('/document/') !== -1) return 'Document Google';
        if (path.indexOf('/spreadsheets/') !== -1) return 'Feuille Google';
        if (path.indexOf('/forms/') !== -1) return 'Formulaire Google';
        return 'Fichier Google';
      }

      if (host === 'drive.google.com') {
        return 'Fichier Google Drive';
      }

      return host || 'Lien externe';
    } catch (_error) {
      return 'Lien externe';
    }
  }

  function buildLinkNode(rawUrl) {
    var safeUrl = normalizeUrl(rawUrl);
    if (!safeUrl) {
      return document.createTextNode(rawUrl);
    }

    var wrapper = document.createElement('span');
    wrapper.className = 'notif-inline-link';

    var link = document.createElement('a');
    link.className = 'notif-body-link';
    link.href = safeUrl;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.title = rawUrl;

    var linkIcon = document.createElement('i');
    linkIcon.className = 'fas fa-arrow-up-right-from-square';
    linkIcon.setAttribute('aria-hidden', 'true');

    var linkText = document.createElement('span');
    linkText.textContent = getReadableLinkLabel(safeUrl);

    link.appendChild(linkIcon);
    link.appendChild(linkText);
    wrapper.appendChild(link);

    var copyBtn = document.createElement('button');
    copyBtn.type = 'button';
    copyBtn.className = 'notif-copy-link-btn';
    copyBtn.setAttribute('data-copy-url', safeUrl);

    var copyIcon = document.createElement('i');
    copyIcon.className = 'fas fa-copy';
    copyIcon.setAttribute('aria-hidden', 'true');

    var copyTextNode = document.createElement('span');
    copyTextNode.textContent = 'Copier';

    copyBtn.appendChild(copyIcon);
    copyBtn.appendChild(copyTextNode);
    wrapper.appendChild(copyBtn);

    return wrapper;
  }

  function linkifyElement(el) {
    var source = String(el.textContent || '');
    if (!source) return;

    var fragment = document.createDocumentFragment();
    var lastIndex = 0;
    source.replace(urlPattern, function (match, _prefix, _scheme, offset) {
      if (offset > lastIndex) {
        fragment.appendChild(document.createTextNode(source.slice(lastIndex, offset)));
      }
      fragment.appendChild(buildLinkNode(match));
      lastIndex = offset + match.length;
      return match;
    });

    if (lastIndex === 0) {
      return;
    }

    if (lastIndex < source.length) {
      fragment.appendChild(document.createTextNode(source.slice(lastIndex)));
    }

    el.textContent = '';
    el.appendChild(fragment);
  }

  function initLinkify() {
    var nodes = document.querySelectorAll('[data-linkify="true"]');
    if (!nodes.length) return;

    nodes.forEach(function (node) {
      linkifyElement(node);
    });

    document.addEventListener('click', function (event) {
      var target = event.target;
      if (!(target instanceof HTMLElement)) return;
      var button = target.closest('.notif-copy-link-btn');
      if (!(button instanceof HTMLButtonElement)) return;

      var toCopy = button.getAttribute('data-copy-url') || '';
      if (!toCopy) return;

      var initialLabel = button.textContent || 'Copier';
      button.disabled = true;
      copyText(toCopy).then(function (ok) {
        showToast(ok ? 'Lien copi\u00e9 dans le presse-papiers.' : 'Copie impossible du lien.', ok ? 'success' : 'error');
        var label = button.querySelector('span');
        if (label) {
          label.textContent = ok ? 'Copi\u00e9' : '\u00c9chec';
        } else {
          button.textContent = ok ? 'Copi\u00e9' : '\u00c9chec';
        }
        window.setTimeout(function () {
          var resetLabel = button.querySelector('span');
          if (resetLabel) {
            resetLabel.textContent = initialLabel;
          } else {
            button.textContent = initialLabel;
          }
          button.disabled = false;
        }, 1200);
      });
    });
  }

  function initNotificationFlash() {
    var flash = document.querySelector('[data-notification-toast]');
    if (!(flash instanceof HTMLElement)) {
      logToast('no flash element, checking queue');
      var queued = readQueuedToast();
      if (queued && queued.message) {
        logToast('show queued toast', queued);
        showToast(queued.message, queued.type);
      }
      return;
    }

    var message = flash.getAttribute('data-notification-toast-message') || flash.textContent || '';
    var type = flash.getAttribute('data-notification-toast-type') || 'success';
    if (message.trim() !== '') {
      logToast('flash element found', { message: message, type: type });
      readQueuedToast();
      return;
    }

    var queuedToast = readQueuedToast();
    if (queuedToast && queuedToast.message) {
      logToast('show fallback queued toast', queuedToast);
      showToast(queuedToast.message, queuedToast.type);
    }
  }

  function initNotificationSubmitQueue() {
    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (!(form instanceof HTMLFormElement)) {
        return;
      }

      var actionInput = form.querySelector('input[name="action"]');
      if (!(actionInput instanceof HTMLInputElement)) {
        return;
      }

      var action = String(actionInput.value || '').trim();
      if (!action) {
        return;
      }

      if (action === 'delete') {
        var confirmMessage = form.getAttribute('data-confirm-delete') || 'Supprimer cette notification ?';
        if (!window.confirm(confirmMessage)) {
          event.preventDefault();
          event.stopImmediatePropagation();
          logToast('delete cancelled');
          return;
        }
      }

      queueToastForForm(form);
    }, true);
  }

  function closeNotificationMenus(exceptMenu) {
    var menus = document.querySelectorAll('.notification-filter-more[open], .notification-card-menu[open]');
    menus.forEach(function (menu) {
      if (exceptMenu && menu === exceptMenu) {
        return;
      }
      menu.removeAttribute('open');
    });
  }

  function initNotificationMenus() {
    document.addEventListener('click', function (event) {
      var target = event.target;
      if (!(target instanceof Node)) {
        return;
      }

      var openMenu = target instanceof Element ? target.closest('.notification-filter-more, .notification-card-menu') : null;
      closeNotificationMenus(openMenu);
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeNotificationMenus(null);
      }
    });

    document.addEventListener('toggle', function (event) {
      var menu = event.target;
      if (!(menu instanceof HTMLDetailsElement) || !menu.open) {
        return;
      }
      if (!menu.matches('.notification-filter-more, .notification-card-menu')) {
        return;
      }
      closeNotificationMenus(menu);
    }, true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      logToast('DOMContentLoaded');
      initLinkify();
      initNotificationMenus();
      initNotificationSubmitQueue();
      initNotificationFlash();
    });
  } else {
    logToast('init immediately');
    initLinkify();
    initNotificationMenus();
    initNotificationSubmitQueue();
    initNotificationFlash();
  }
})();
