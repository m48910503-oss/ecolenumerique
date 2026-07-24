(function () {
    'use strict';

    var MAX_TOASTS = 12;
    var AUTO_CLOSE_MS = 5000;
    var MAX_SEEN_IDS = 600;
    var ANNOUNCEMENT_KEYWORDS = ['annonce', '[annonce]'];
    var safeLocalStorage = null;

    function resolveLocalStorage() {
        if (safeLocalStorage !== null) {
            return safeLocalStorage;
        }
        try {
            if (!window.localStorage) {
                safeLocalStorage = null;
                return null;
            }
            var testKey = '__popup_ls_test__';
            window.localStorage.setItem(testKey, '1');
            window.localStorage.removeItem(testKey);
            safeLocalStorage = window.localStorage;
            return safeLocalStorage;
        } catch (_e) {
            safeLocalStorage = null;
            return null;
        }
    }

    function initGlobalPopups() {
        var parsed = readPayload();
        var items = parsed.items;
        if (!items.length) {
            return;
        }

        var seenStore = createSeenStore(parsed.userId);
        var freshItems = [];
        items.forEach(function (item) {
            if (!item.id) {
                freshItems.push(item);
                return;
            }
            if (seenStore.has(item.id)) {
                return;
            }
            seenStore.add(item.id);
            freshItems.push(item);
        });
        if (!freshItems.length) {
            return;
        }

        var container = ensureContainer();
        var announcementItems = [];
        var toastItems = [];
        freshItems.forEach(function (item) {
            if (isAnnouncementItem(item)) {
                announcementItems.push(item);
            } else {
                toastItems.push(item);
            }
        });

        toastItems.slice(0, MAX_TOASTS).forEach(function (item, index) {
            showToast(container, item, index);
        });

        if (announcementItems.length) {
            showAnnouncementQueue(announcementItems);
        }
    }

    function readPayload() {
        var payloadNode = document.getElementById('global-popup-data');
        if (!payloadNode) {
            return { userId: 0, items: [] };
        }

        var raw = (payloadNode.textContent || '').trim();
        if (!raw) {
            return { userId: 0, items: [] };
        }

        var parsed = null;
        try {
            parsed = JSON.parse(raw);
        } catch (error) {
            return { userId: 0, items: [] };
        }

        if (!parsed || !Array.isArray(parsed.items)) {
            return { userId: 0, items: [] };
        }

        var out = [];
        parsed.items.forEach(function (entry) {
            if (!entry || typeof entry !== 'object') {
                return;
            }
            var message = typeof entry.message === 'string' ? entry.message.trim() : '';
            if (!message) {
                return;
            }
            out.push({
                type: normalizeType(entry.type),
                message: message,
                id: sanitizeId(entry.id),
                url: typeof entry.url === 'string' ? entry.url.trim() : ''
            });
        });

        return {
            userId: sanitizeId(parsed.user_id),
            items: out
        };
    }

    function sanitizeId(value) {
        var id = Number(value);
        if (!Number.isFinite(id)) {
            return 0;
        }
        id = Math.floor(id);
        return id > 0 ? id : 0;
    }

    function normalizeType(type) {
        if (type === 'success' || type === 'error' || type === 'warning') {
            return type;
        }
        return 'info';
    }

    function ensureContainer() {
        var container = document.getElementById('global-popup-container');
        if (container) {
            return container;
        }
        if (!document.body) {
            return null;
        }

        container = document.createElement('div');
        container.id = 'global-popup-container';
        container.style.position = 'fixed';
        container.style.top = '18px';
        container.style.left = '50%';
        container.style.transform = 'translateX(-50%)';
        container.style.zIndex = '9999';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.alignItems = 'stretch';
        container.style.gap = '10px';
        container.style.width = 'min(460px, calc(100vw - 24px))';
        container.style.maxWidth = 'calc(100vw - 24px)';
        container.style.pointerEvents = 'none';
        document.body.appendChild(container);
        return container;
    }

    function queueToast(message, type, url) {
        var text = typeof message === 'string' ? message.trim() : '';
        if (!text) {
            return;
        }

        var container = ensureContainer();
        if (!container) {
            return;
        }
        showToast(
            container,
            {
                type: normalizeType(type),
                message: text,
                id: 0,
                url: typeof url === 'string' ? url.trim() : ''
            },
            Math.min(container.childElementCount, MAX_TOASTS - 1)
        );
    }

    function isAnnouncementItem(item) {
        if (!item || typeof item.message !== 'string') {
            return false;
        }
        var message = item.message.toLowerCase();
        for (var i = 0; i < ANNOUNCEMENT_KEYWORDS.length; i += 1) {
            if (message.indexOf(ANNOUNCEMENT_KEYWORDS[i]) !== -1) {
                return true;
            }
        }
        return false;
    }

    function showAnnouncementQueue(items) {
        var queue = Array.isArray(items) ? items.slice() : [];
        if (!queue.length) {
            return;
        }

        var showNext = function () {
            if (!queue.length) {
                return;
            }
            var item = queue.shift();
            showAnnouncementModal(item, showNext);
        };

        showNext();
    }

    function showAnnouncementModal(item, onClose) {
        var existing = document.getElementById('global-announcement-modal');
        if (existing && existing.parentNode) {
            existing.parentNode.removeChild(existing);
        }
        if (!document.body) {
            return;
        }

        var overlay = document.createElement('div');
        overlay.id = 'global-announcement-modal';
        overlay.style.position = 'fixed';
        overlay.style.inset = '0';
        overlay.style.zIndex = '10000';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.style.padding = '18px';
        overlay.style.background = 'rgba(2, 8, 24, 0.62)';
        overlay.style.backdropFilter = 'blur(3px)';
        overlay.style.WebkitBackdropFilter = 'blur(3px)';

        var panel = document.createElement('section');
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'true');
        panel.style.width = 'min(720px, 100%)';
        panel.style.borderRadius = '18px';
        panel.style.border = '1px solid rgba(96, 165, 250, 0.42)';
        panel.style.background = 'linear-gradient(180deg, rgba(10, 21, 46, 0.98), rgba(4, 12, 30, 0.98))';
        panel.style.color = '#e6f0ff';
        panel.style.boxShadow = '0 34px 74px rgba(2, 6, 23, 0.62)';
        panel.style.padding = '20px';
        panel.style.display = 'grid';
        panel.style.gap = '12px';

        var header = document.createElement('div');
        header.style.display = 'flex';
        header.style.alignItems = 'center';
        header.style.justifyContent = 'space-between';
        header.style.gap = '12px';

        var titleBadge = document.createElement('span');
        titleBadge.textContent = 'Annonce';
        titleBadge.style.display = 'inline-flex';
        titleBadge.style.alignItems = 'center';
        titleBadge.style.padding = '4px 10px';
        titleBadge.style.borderRadius = '999px';
        titleBadge.style.fontSize = '12px';
        titleBadge.style.fontWeight = '700';
        titleBadge.style.letterSpacing = '0.04em';
        titleBadge.style.background = 'rgba(96, 165, 250, 0.16)';
        titleBadge.style.color = '#bfdbfe';
        header.appendChild(titleBadge);

        var closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.textContent = 'Fermer';
        closeButton.style.border = '1px solid rgba(148, 163, 184, 0.45)';
        closeButton.style.borderRadius = '10px';
        closeButton.style.background = 'transparent';
        closeButton.style.color = '#e2e8f0';
        closeButton.style.fontSize = '12px';
        closeButton.style.fontWeight = '600';
        closeButton.style.padding = '6px 10px';
        closeButton.style.cursor = 'pointer';
        header.appendChild(closeButton);

        var message = document.createElement('p');
        message.textContent = (item && typeof item.message === 'string') ? item.message : '';
        message.style.margin = '0';
        message.style.fontSize = '15px';
        message.style.lineHeight = '1.55';
        message.style.color = '#dbeafe';

        var actions = document.createElement('div');
        actions.style.display = 'flex';
        actions.style.alignItems = 'center';
        actions.style.justifyContent = 'flex-end';
        actions.style.flexWrap = 'wrap';
        actions.style.gap = '10px';

        var closeCta = document.createElement('button');
        closeCta.type = 'button';
        closeCta.textContent = 'Compris';
        closeCta.style.border = '1px solid rgba(148, 163, 184, 0.45)';
        closeCta.style.borderRadius = '10px';
        closeCta.style.background = 'rgba(15, 23, 42, 0.35)';
        closeCta.style.color = '#e2e8f0';
        closeCta.style.fontSize = '13px';
        closeCta.style.fontWeight = '600';
        closeCta.style.padding = '8px 12px';
        closeCta.style.cursor = 'pointer';
        actions.appendChild(closeCta);

        if (item && item.url) {
            var openLink = document.createElement('a');
            openLink.href = item.url;
            openLink.textContent = 'Ouvrir';
            openLink.style.display = 'inline-flex';
            openLink.style.alignItems = 'center';
            openLink.style.justifyContent = 'center';
            openLink.style.border = '1px solid rgba(37, 99, 235, 0.72)';
            openLink.style.borderRadius = '10px';
            openLink.style.background = 'linear-gradient(135deg, #2563eb, #1d4ed8)';
            openLink.style.color = '#eff6ff';
            openLink.style.fontSize = '13px';
            openLink.style.fontWeight = '700';
            openLink.style.padding = '8px 12px';
            openLink.style.textDecoration = 'none';
            if (/^https?:\/\//i.test(item.url) && !item.url.startsWith(window.location.origin)) {
                openLink.target = '_blank';
                openLink.rel = 'noopener noreferrer';
            }
            actions.appendChild(openLink);
        }

        panel.appendChild(header);
        panel.appendChild(message);
        panel.appendChild(actions);
        overlay.appendChild(panel);
        document.body.appendChild(overlay);

        var closed = false;
        var autoCloseTimer = null;
        var keyHandler = function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeModal();
            }
        };

        var closeModal = function () {
            if (closed) {
                return;
            }
            closed = true;
            if (autoCloseTimer) {
                window.clearTimeout(autoCloseTimer);
                autoCloseTimer = null;
            }
            document.removeEventListener('keydown', keyHandler);
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
            if (typeof onClose === 'function') {
                onClose();
            }
        };

        closeButton.addEventListener('click', closeModal);
        closeCta.addEventListener('click', closeModal);
        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                closeModal();
            }
        });
        document.addEventListener('keydown', keyHandler);
        autoCloseTimer = window.setTimeout(closeModal, AUTO_CLOSE_MS);
    }

    function createSeenStore(userId) {
        var safeUserId = sanitizeId(userId);
        var storage = resolveLocalStorage();
        if (!safeUserId || !storage) {
            return {
                has: function () { return false; },
                add: function () {}
            };
        }

        var key = 'global_popup_seen_ids_u' + safeUserId;
        var state = {};
        try {
            var raw = storage.getItem(key);
            if (raw) {
                var parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) {
                    parsed.forEach(function (value) {
                        var id = sanitizeId(value);
                        if (id > 0) {
                            state[id] = true;
                        }
                    });
                }
            }
        } catch (_e) {
            state = {};
        }

        function persist() {
            try {
                var keys = Object.keys(state);
                if (keys.length > MAX_SEEN_IDS) {
                    var trimmed = keys.slice(keys.length - MAX_SEEN_IDS);
                    var next = {};
                    trimmed.forEach(function (id) {
                        next[id] = true;
                    });
                    state = next;
                    keys = trimmed;
                }
                storage.setItem(key, JSON.stringify(keys));
            } catch (_e) {
                // Ignore localStorage failures (quota/private mode).
            }
        }

        return {
            has: function (id) {
                var safeId = sanitizeId(id);
                if (!safeId) {
                    return false;
                }
                return !!state[safeId];
            },
            add: function (id) {
                var safeId = sanitizeId(id);
                if (!safeId || state[safeId]) {
                    return;
                }
                state[safeId] = true;
                persist();
            }
        };
    }

    function isDarkTheme() {
        return document.documentElement.classList.contains('dark') ||
            document.documentElement.getAttribute('data-theme') === 'dark';
    }

    function showToast(container, item, index) {
        var theme = getTheme(item.type);
        var toast = document.createElement('section');
        toast.setAttribute('role', item.type === 'error' ? 'alert' : 'status');
        toast.setAttribute('aria-live', item.type === 'error' ? 'assertive' : 'polite');
        toast.className = 'app-global-toast app-global-toast--' + item.type;
        toast.style.pointerEvents = 'auto';
        toast.style.border = '1px solid ' + theme.border;
        toast.style.borderLeft = '4px solid ' + theme.accent;
        toast.style.background = theme.background;
        toast.style.color = theme.color;
        toast.style.borderRadius = '15px';
        toast.style.padding = '14px 16px';
        toast.style.boxShadow = theme.shadow;
        toast.style.backdropFilter = 'blur(10px) saturate(1.1)';
        toast.style.WebkitBackdropFilter = 'blur(10px) saturate(1.1)';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-12px) scale(0.98)';
        toast.style.transition = 'opacity 180ms ease, transform 180ms ease';
        toast.style.fontSize = '14px';
        toast.style.lineHeight = '1.42';
        toast.style.width = '100%';

        var header = document.createElement('div');
        header.style.display = 'flex';
        header.style.alignItems = 'center';
        header.style.justifyContent = 'flex-end';
        header.style.gap = '10px';

        var closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.textContent = 'Fermer';
        closeButton.style.border = '1px solid ' + theme.actionBorder;
        closeButton.style.background = theme.closeBg;
        closeButton.style.borderRadius = '8px';
        closeButton.style.padding = '3px 8px';
        closeButton.style.color = theme.color;
        closeButton.style.fontSize = '12px';
        closeButton.style.fontWeight = '600';
        closeButton.style.cursor = 'pointer';
        closeButton.style.opacity = '0.92';
        header.appendChild(closeButton);

        var message = document.createElement('p');
        message.textContent = item.message;
        message.style.margin = '8px 0 0 0';
        message.style.fontWeight = '600';

        toast.appendChild(header);
        toast.appendChild(message);

        if (item.url) {
            var actionWrap = document.createElement('div');
            actionWrap.style.marginTop = '10px';

            var openLink = document.createElement('a');
            openLink.href = item.url;
            openLink.textContent = 'Ouvrir';
            openLink.style.display = 'inline-flex';
            openLink.style.alignItems = 'center';
            openLink.style.justifyContent = 'center';
            openLink.style.padding = '7px 11px';
            openLink.style.borderRadius = '8px';
            openLink.style.border = '1px solid ' + theme.actionBorder;
            openLink.style.color = theme.actionText;
            openLink.style.fontSize = '12px';
            openLink.style.fontWeight = '700';
            openLink.style.textDecoration = 'none';
            openLink.style.background = theme.actionBg;

            if (/^https?:\/\//i.test(item.url) && !item.url.startsWith(window.location.origin)) {
                openLink.target = '_blank';
                openLink.rel = 'noopener noreferrer';
            }

            actionWrap.appendChild(openLink);
            toast.appendChild(actionWrap);
        }

        container.appendChild(toast);

        var closed = false;
        var closeToast = function () {
            if (closed) {
                return;
            }
            closed = true;
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-12px) scale(0.98)';
            window.setTimeout(function () {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 190);
        };

        closeButton.addEventListener('click', closeToast);

        window.setTimeout(function () {
            requestAnimationFrame(function () {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0) scale(1)';
            });
        }, index * 110);

        window.setTimeout(closeToast, AUTO_CLOSE_MS + (index * 110));
    }

    function getTheme(type) {
        var dark = isDarkTheme();

        if (dark) {
            if (type === 'success') {
                return {
                    label: 'SUCCES',
                    background: 'linear-gradient(135deg, rgba(6, 78, 59, 0.96), rgba(2, 44, 34, 0.96))',
                    border: 'rgba(52, 211, 153, 0.42)',
                    accent: '#34d399',
                    color: '#ecfdf5',
                    badgeBg: 'rgba(52, 211, 153, 0.18)',
                    badgeColor: '#d1fae5',
                    actionBg: '#10b981',
                    actionText: '#f0fdf4',
                    actionBorder: 'rgba(110, 231, 183, 0.45)',
                    closeBg: 'rgba(255, 255, 255, 0.06)',
                    shadow: '0 22px 48px rgba(2, 6, 23, 0.48)'
                };
            }
            if (type === 'error') {
                return {
                    label: 'ERREUR',
                    background: 'linear-gradient(135deg, rgba(136, 19, 55, 0.96), rgba(76, 5, 25, 0.96))',
                    border: 'rgba(251, 113, 133, 0.42)',
                    accent: '#fb7185',
                    color: '#fff1f2',
                    badgeBg: 'rgba(251, 113, 133, 0.18)',
                    badgeColor: '#ffe4e6',
                    actionBg: '#e11d48',
                    actionText: '#fff1f2',
                    actionBorder: 'rgba(253, 164, 175, 0.45)',
                    closeBg: 'rgba(255, 255, 255, 0.06)',
                    shadow: '0 22px 48px rgba(2, 6, 23, 0.52)'
                };
            }
            if (type === 'warning') {
                return {
                    label: 'WARNING',
                    background: 'linear-gradient(135deg, rgba(146, 64, 14, 0.96), rgba(69, 26, 3, 0.96))',
                    border: 'rgba(245, 158, 11, 0.42)',
                    accent: '#f59e0b',
                    color: '#fffbeb',
                    badgeBg: 'rgba(245, 158, 11, 0.18)',
                    badgeColor: '#fde68a',
                    actionBg: '#d97706',
                    actionText: '#fffbeb',
                    actionBorder: 'rgba(252, 211, 77, 0.45)',
                    closeBg: 'rgba(255, 255, 255, 0.06)',
                    shadow: '0 22px 48px rgba(2, 6, 23, 0.48)'
                };
            }
            return {
                label: 'INFO',
                background: 'linear-gradient(135deg, rgba(30, 64, 175, 0.96), rgba(23, 37, 84, 0.96))',
                border: 'rgba(96, 165, 250, 0.42)',
                accent: '#60a5fa',
                color: '#eff6ff',
                badgeBg: 'rgba(96, 165, 250, 0.18)',
                badgeColor: '#dbeafe',
                actionBg: '#2563eb',
                actionText: '#eff6ff',
                actionBorder: 'rgba(147, 197, 253, 0.45)',
                closeBg: 'rgba(255, 255, 255, 0.06)',
                shadow: '0 22px 48px rgba(2, 6, 23, 0.48)'
            };
        }

        if (type === 'success') {
            return {
                label: 'SUCCES',
                background: 'linear-gradient(135deg, #ecfdf5, #d1fae5)',
                border: '#a7f3d0',
                accent: '#10b981',
                color: '#065f46',
                badgeBg: 'rgba(16, 185, 129, 0.18)',
                badgeColor: '#065f46',
                actionBg: '#10b981',
                actionText: '#f0fdf4',
                actionBorder: '#10b981',
                closeBg: 'rgba(255, 255, 255, 0.72)',
                shadow: '0 18px 38px rgba(15, 23, 42, 0.12)'
            };
        }
        if (type === 'error') {
            return {
                label: 'ERREUR',
                background: 'linear-gradient(135deg, #fff1f2, #ffe4e6)',
                border: '#fda4af',
                accent: '#e11d48',
                color: '#881337',
                badgeBg: 'rgba(244, 63, 94, 0.18)',
                badgeColor: '#881337',
                actionBg: '#e11d48',
                actionText: '#fff1f2',
                actionBorder: '#e11d48',
                closeBg: 'rgba(255, 255, 255, 0.72)',
                shadow: '0 18px 38px rgba(15, 23, 42, 0.14)'
            };
        }
        if (type === 'warning') {
            return {
                label: 'WARNING',
                background: 'linear-gradient(135deg, #fffbeb, #fef3c7)',
                border: '#fcd34d',
                accent: '#d97706',
                color: '#78350f',
                badgeBg: 'rgba(245, 158, 11, 0.18)',
                badgeColor: '#78350f',
                actionBg: '#d97706',
                actionText: '#fffbeb',
                actionBorder: '#d97706',
                closeBg: 'rgba(255, 255, 255, 0.72)',
                shadow: '0 18px 38px rgba(15, 23, 42, 0.12)'
            };
        }
        return {
            label: 'INFO',
            background: 'linear-gradient(135deg, #eff6ff, #dbeafe)',
            border: '#93c5fd',
            accent: '#2563eb',
            color: '#1e3a8a',
            badgeBg: 'rgba(59, 130, 246, 0.18)',
            badgeColor: '#1e3a8a',
            actionBg: '#2563eb',
            actionText: '#eff6ff',
            actionBorder: '#2563eb',
            closeBg: 'rgba(255, 255, 255, 0.72)',
            shadow: '0 18px 38px rgba(15, 23, 42, 0.12)'
        };
    }

    window.appShowToast = queueToast;
    if (typeof window.showToast !== 'function') {
        window.showToast = queueToast;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGlobalPopups);
    } else {
        initGlobalPopups();
    }
})();
