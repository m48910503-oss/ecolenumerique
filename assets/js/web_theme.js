(() => {
    'use strict';

    const STORAGE_KEY = 'theme';
    const DEFAULT_THEME = 'light';
    const VALID_THEMES = new Set(['light', 'dark']);
    const SELECTOR = '[data-web-theme-toggle], [data-theme-toggle], #theme-toggle, #theme-toggle-sidebar';
    const DARK_MEDIA = '(prefers-color-scheme: dark)';
    const SCROLLBAR_STYLE_ID = 'web-theme-scrollbar-style';
    const SCROLLBAR_CSS = `
        html {
            scrollbar-width: thin;
            scrollbar-color: rgba(100, 116, 139, 0.68) transparent;
            scrollbar-gutter: stable;
        }

        html::-webkit-scrollbar,
        body::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        html::-webkit-scrollbar-track,
        body::-webkit-scrollbar-track {
            background: transparent;
        }

        html::-webkit-scrollbar-thumb,
        body::-webkit-scrollbar-thumb {
            background: rgba(100, 116, 139, 0.58);
            border: 1px solid transparent;
            border-radius: 999px;
            background-clip: padding-box;
        }

        html::-webkit-scrollbar-thumb:hover,
        body::-webkit-scrollbar-thumb:hover {
            background: rgba(59, 130, 246, 0.78);
            border: 1px solid transparent;
            background-clip: padding-box;
        }
    `;
    const SCROLL_TOP_STYLE_ID = 'web-theme-scroll-top-style';
    const SCROLL_TOP_CSS = `
        .web-scroll-top {
            position: fixed;
            right: 1.25rem;
            bottom: 1.25rem;
            z-index: 45;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border: 0;
            border-radius: 9999px;
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 18px 35px -15px rgba(37, 99, 235, 0.9);
            opacity: 0;
            pointer-events: none;
            transform: translateY(0.5rem);
            transition: opacity 0.2s ease, transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
        }

        .web-scroll-top:hover {
            background: #1d4ed8;
            box-shadow: 0 20px 40px -16px rgba(37, 99, 235, 0.95);
        }

        .web-scroll-top.is-visible {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        html.dark .web-scroll-top {
            background: #3b82f6;
            color: #ffffff;
        }

        html.dark .web-scroll-top:hover {
            background: #2563eb;
        }

        @media (min-width: 640px) {
            .web-scroll-top {
                width: 3.25rem;
                height: 3.25rem;
            }
        }
    `;

    const THEME_ICON_LIGHT = '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4.5"></circle><path d="M12 1.5v2.5"></path><path d="M12 20v2.5"></path><path d="m4.57 4.57 1.77 1.77"></path><path d="m17.66 17.66 1.77 1.77"></path><path d="M1.5 12H4"></path><path d="M20 12h2.5"></path><path d="m4.57 19.43 1.77-1.77"></path><path d="m17.66 6.34 1.77-1.77"></path></svg>';
    const THEME_ICON_DARK = '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20.5 14.5A8.5 8.5 0 1 1 9.5 3.5a7 7 0 0 0 11 11Z"></path></svg>';
    const scrollTopState = {
        button: null,
        initialized: false,
        root: null,
    };

    const safeStorage = {
        get(key) {
            try {
                return localStorage.getItem(key);
            } catch (_error) {
                return null;
            }
        },
        set(key, value) {
            try {
                localStorage.setItem(key, value);
            } catch (_error) {
                /* Storage unavailable. */
            }
        },
    };

    const getStoredTheme = () => {
        const value = safeStorage.get(STORAGE_KEY);
        return VALID_THEMES.has(value) ? value : null;
    };

    const getInitialTheme = () => getStoredTheme() || DEFAULT_THEME;
    const ensureScrollbarStyle = () => {
        if (document.getElementById(SCROLLBAR_STYLE_ID)) {
            return;
        }

        const style = document.createElement('style');
        style.id = SCROLLBAR_STYLE_ID;
        style.textContent = SCROLLBAR_CSS;
        document.head.appendChild(style);
    };

    const ensureScrollTopStyle = () => {
        if (document.getElementById(SCROLL_TOP_STYLE_ID)) {
            return;
        }

        const style = document.createElement('style');
        style.id = SCROLL_TOP_STYLE_ID;
        style.textContent = SCROLL_TOP_CSS;
        document.head.appendChild(style);
    };

    const getScrollTopRoot = () => {
        const candidate = document.querySelector('[data-scroll-top-root]');
        return candidate instanceof HTMLElement ? candidate : null;
    };

    const updateScrollTopButtonVisibility = () => {
        const button = scrollTopState.button;
        if (!(button instanceof HTMLElement)) {
            return;
        }

        const root = scrollTopState.root instanceof HTMLElement ? scrollTopState.root : null;
        const doc = document.documentElement;
        const body = document.body;
        const scrollTop = root ? root.scrollTop : (window.scrollY || doc.scrollTop || body.scrollTop || 0);
        const viewportHeight = root ? root.clientHeight : (window.innerHeight || doc.clientHeight || 0);
        const documentHeight = root ? root.scrollHeight : Math.max(
            doc.scrollHeight || 0,
            body.scrollHeight || 0,
            doc.offsetHeight || 0,
            body.offsetHeight || 0
        );
        const canScroll = documentHeight > (viewportHeight + 240);
        const activationDistance = Math.max(120, Math.min(280, Math.round(viewportHeight * 0.18)));
        const showButton = canScroll && scrollTop >= activationDistance;

        button.classList.toggle('is-visible', showButton);
        button.setAttribute('aria-hidden', showButton ? 'false' : 'true');
    };

    const ensureScrollTopButton = () => {
        if (!document.body) {
            return;
        }

        if (document.querySelector('[data-scroll-top]')) {
            return;
        }

        if (!(scrollTopState.button instanceof HTMLElement) || !document.body.contains(scrollTopState.button)) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'web-scroll-top';
            button.setAttribute('data-scroll-top', '1');
            button.setAttribute('aria-label', 'Remonter en haut de la page');
            button.setAttribute('aria-hidden', 'true');
            button.setAttribute('title', 'Remonter en haut');
            button.innerHTML = '<i class="fas fa-arrow-up" aria-hidden="true"></i>';

            if (document.querySelector('a[aria-label="Assistance"], button[aria-label="Assistance"], [data-support-fab]')) {
                button.style.bottom = '5.25rem';
            }

            document.body.appendChild(button);
            scrollTopState.button = button;
        }

        const root = getScrollTopRoot();
        scrollTopState.root = root;

        if (scrollTopState.button && scrollTopState.button.dataset.scrollTopBound !== '1') {
            scrollTopState.button.addEventListener('click', (event) => {
                event.preventDefault();
                if (scrollTopState.root instanceof HTMLElement) {
                    scrollTopState.root.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }

                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
            scrollTopState.button.dataset.scrollTopBound = '1';
        }

        if (!scrollTopState.initialized) {
            if (scrollTopState.root instanceof HTMLElement) {
                scrollTopState.root.addEventListener('scroll', updateScrollTopButtonVisibility, { passive: true });
            } else {
                window.addEventListener('scroll', updateScrollTopButtonVisibility, { passive: true });
            }
            window.addEventListener('resize', updateScrollTopButtonVisibility, { passive: true });
            window.addEventListener('load', updateScrollTopButtonVisibility, { passive: true });
            window.addEventListener('pageshow', updateScrollTopButtonVisibility, { passive: true });
            scrollTopState.initialized = true;
        }

        updateScrollTopButtonVisibility();
    };

    const updateButtons = (theme) => {
        const isDark = theme === 'dark';
        const title = isDark ? 'Activer le mode clair' : 'Activer le mode sombre';
        const icon = isDark ? THEME_ICON_LIGHT : THEME_ICON_DARK;

        document.querySelectorAll(SELECTOR).forEach((button) => {
            const darkIconNode = button.querySelector('[data-theme-icon-dark]');
            const lightIconNode = button.querySelector('[data-theme-icon-light]');
            const legacyThemeIconNodes = Array.from(button.children).filter((node) => {
                if (!(node instanceof HTMLElement)) {
                    return false;
                }

                return node.classList.contains('dark:hidden') || node.classList.contains('dark:inline');
            });
            const hasLegacyThemeIcons = legacyThemeIconNodes.some((node) => node.classList.contains('dark:hidden')) &&
                legacyThemeIconNodes.some((node) => node.classList.contains('dark:inline'));
            let iconNode = button.querySelector('[data-theme-label]');

            if (darkIconNode || lightIconNode) {
                if (darkIconNode instanceof HTMLElement) {
                    darkIconNode.classList.toggle('hidden', isDark);
                }
                if (lightIconNode instanceof HTMLElement) {
                    lightIconNode.classList.toggle('hidden', !isDark);
                }
            } else if (hasLegacyThemeIcons) {
                // The markup already contains a moon/sun pair controlled by Tailwind's dark variants.
                // Keep it intact to avoid duplicating the icon set on teacher/admin shell buttons.
            } else {
                if (!iconNode) {
                    iconNode = document.createElement('span');
                    iconNode.setAttribute('data-theme-label', '');
                    iconNode.setAttribute('aria-hidden', 'true');
                    iconNode.className = 'inline-flex h-5 w-5 items-center justify-center';
                    button.prepend(iconNode);
                }

                iconNode.innerHTML = icon;
            }

            button.setAttribute('aria-label', title);
            button.setAttribute('title', title);
            button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            button.dataset.themeState = theme;
        });
    };

    const applyTheme = (nextTheme, persist) => {
        const theme = VALID_THEMES.has(nextTheme) ? nextTheme : 'light';
        const root = document.documentElement;
        const isDark = theme === 'dark';

        root.classList.toggle('dark', isDark);
        root.setAttribute('data-theme', theme);
        root.style.colorScheme = theme;

        if (persist) {
            safeStorage.set(STORAGE_KEY, theme);
        }

        updateButtons(theme);
        window.dispatchEvent(new CustomEvent('themechange', {
            detail: { theme }
        }));
    };

    const syncDocumentView = () => {
        const body = document.body;
        const root = document.documentElement;
        if (!body || !root) {
            return;
        }

        const view = String(body.dataset.view || '').trim();
        if (view !== '' && !root.dataset.view) {
            root.dataset.view = view;
        }
    };

    const syncTheme = (persist = false) => {
        applyTheme(getInitialTheme(), persist);
    };

    window.toggleTheme = () => {
        const current = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        applyTheme(current === 'dark' ? 'light' : 'dark', true);
    };

    document.addEventListener('click', (event) => {
        const button = event.target instanceof Element ? event.target.closest(SELECTOR) : null;
        if (!button) {
            return;
        }

        if (
            button.getAttribute('data-theme-bound') === '1' ||
            button.getAttribute('data-theme-toggle-bound') === '1'
        ) {
            return;
        }

        event.preventDefault();
        window.toggleTheme();
    });

    const bootstrapPublicTheme = () => {
        syncDocumentView();
        syncTheme(false);
        ensureScrollbarStyle();
        ensureScrollTopStyle();
        ensureScrollTopButton();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            syncDocumentView();
            bootstrapPublicTheme();
        }, { once: true });
    } else {
        syncDocumentView();
        bootstrapPublicTheme();
    }

    const mediaQuery = window.matchMedia?.(DARK_MEDIA);
    if (mediaQuery && typeof mediaQuery.addEventListener === 'function') {
        mediaQuery.addEventListener('change', () => {
            if (getStoredTheme() === null) {
                applyTheme(DEFAULT_THEME, false);
            }
        });
    }
})();
