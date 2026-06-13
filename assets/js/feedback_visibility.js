(function () {
    'use strict';

    var ALERT_SELECTORS = [
        '.app-feedback',
        '.admin-shell-alert',
        '.admin-auth-alert',
        '.checkout-alert',
        '.checkout-inline-state',
        ':is(div,section,article)[class*="border-rose-"][class*="bg-rose-"]',
        ':is(div,section,article)[class*="border-red-"][class*="bg-red-"]',
        ':is(div,section,article)[class*="border-amber-"][class*="bg-amber-"]',
        ':is(div,section,article)[class*="border-yellow-"][class*="bg-yellow-"]',
        ':is(div,section,article)[class*="border-emerald-"][class*="bg-emerald-"]',
        ':is(div,section,article)[class*="border-green-"][class*="bg-green-"]',
        ':is(div,section,article)[class*="border-blue-"][class*="bg-blue-"][class*="text-sky-"]'
    ].join(',');

    function inferTypeFromClasses(className) {
        var value = String(className || '').toLowerCase();
        if (value.indexOf('danger') !== -1 || value.indexOf('error') !== -1 || value.indexOf('rose-') !== -1 || value.indexOf('red-') !== -1) {
            return 'error';
        }
        if (value.indexOf('warning') !== -1 || value.indexOf('amber-') !== -1 || value.indexOf('yellow-') !== -1) {
            return 'warning';
        }
        if (value.indexOf('success') !== -1 || value.indexOf('emerald-') !== -1 || value.indexOf('green-') !== -1) {
            return 'success';
        }
        return 'info';
    }

    function isInteractiveContainer(node) {
        return !!node.querySelector('form, input, select, textarea, table, video, iframe');
    }

    function isInsideIgnoredZone(node) {
        return !!node.closest(
            '#global-popup-container, #catalog-toast-stack, #toast-container, .notif-card, .notifications-page [aria-label="Pagination notifications"], .teacher-hub-card'
        );
    }

    function looksLikeFeedback(node) {
        if (!(node instanceof HTMLElement)) {
            return false;
        }
        if (node.closest('[data-reviews-courses-list], [data-reviews-list]')) {
            var compactText = String(node.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
            if (compactText.indexOf('avis:') === 0 || compactText.indexOf('visibles:') === 0 || compactText.indexOf('masqu') === 0) {
                return false;
            }
        }
        if (node.dataset.feedbackVisible === '1') {
            return false;
        }
        if (node.dataset.feedbackInline === '1') {
            return false;
        }
        if (isInsideIgnoredZone(node)) {
            return false;
        }
        if (isInteractiveContainer(node)) {
            return false;
        }

        if (node.classList.contains('app-feedback')
            || node.classList.contains('admin-shell-alert')
            || node.classList.contains('admin-auth-alert')
            || node.classList.contains('checkout-alert')
            || node.classList.contains('checkout-inline-state')) {
            return true;
        }

        var text = String(node.textContent || '').replace(/\s+/g, ' ').trim();
        if (text.length < 3 || text.length > 900) {
            return false;
        }

        var className = String(node.className || '').toLowerCase();
        if (className.indexOf('rounded') === -1 || className.indexOf('border') === -1) {
            return false;
        }

        return true;
    }

    function extractToastMessage(node) {
        var message = '';
        if (node.dataset.toastMessage) {
            message = String(node.dataset.toastMessage).trim();
        }
        if (!message) {
            message = String(node.textContent || '').replace(/\s+/g, ' ').trim();
        }
        return message;
    }

    function extractToastUrl(node) {
        var link = node.querySelector('a[href]');
        if (!link || !link.href) {
            return '';
        }
        return link.href;
    }

    function maybeToast(node) {
        if (!node || node.dataset.toastified === '1') {
            return;
        }
        if (typeof window.appShowToast !== 'function') {
            return;
        }

        var message = extractToastMessage(node);
        if (!message) {
            return;
        }
        var type = inferTypeFromClasses(node.className);
        var url = extractToastUrl(node);
        window.appShowToast(message, type, url);
        node.dataset.toastified = '1';
        node.setAttribute('aria-hidden', 'true');
        node.hidden = true;
    }

    function applyFeedbackStyle(node) {
        if (!looksLikeFeedback(node)) {
            return;
        }

        var tone = inferTypeFromClasses(node.className);
        node.classList.add('app-feedback', 'app-feedback--' + tone);

        if (!node.hasAttribute('role')) {
            node.setAttribute('role', tone === 'error' ? 'alert' : 'status');
        }
        if (!node.hasAttribute('aria-live')) {
            node.setAttribute('aria-live', tone === 'error' ? 'assertive' : 'polite');
        }

        node.dataset.feedbackVisible = '1';
        maybeToast(node);
    }

    function normalizeNode(root) {
        if (!(root instanceof HTMLElement)) {
            return;
        }

        if (root.matches(ALERT_SELECTORS)) {
            applyFeedbackStyle(root);
        }

        root.querySelectorAll(ALERT_SELECTORS).forEach(function (node) {
            applyFeedbackStyle(node);
        });
    }

    function init() {
        normalizeNode(document.body);

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node instanceof HTMLElement) {
                        normalizeNode(node);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
