/**
 * ÉcoleNumérique — theme.js  v2.0
 * Gestion globale du thème clair/sombre + configuration Tailwind CDN.
 *
 * Responsabilités :
 *  1. Initialiser le thème (localStorage → préférence système → light)
 *  2. Appliquer les classes sémantiques sur <body> (rôle, vue)
 *  3. Configurer Tailwind CDN avec le design system
 *  4. Surligner le lien actif dans la barre latérale
 *  5. Créer le bouton FAB de bascule si aucun bouton n'existe
 */
(() => {
    'use strict';

    /* ── CONSTANTES ── */
    const STORAGE_KEY = 'theme';
    const FONT_SCALE_STORAGE_KEY = 'app_font_scale';
    const KPI_MOBILE_STORAGE_KEY = 'app_kpi_mobile_state';
    const KPI_MOBILE_HIDDEN_CLASS = 'app-kpi-mobile-hidden';
    const LOCKED_FONT_SCALE = 'compact';
    const VALID_THEMES = new Set(['light', 'dark']);
    const VALID_FONT_SCALES = new Set(['compact', 'comfort']);
    const ROLES = ['admin', 'teacher', 'student', 'auth', 'web', 'company'];

    /* ─────────────────────────────────────────
       1. STOCKAGE LOCAL SÉCURISÉ
    ───────────────────────────────────────── */
    const safeStorage = {
        /** @returns {string|null} */
        get(key) {
            try {
                return localStorage.getItem(key);
            } catch (_e) {
                return null;
            }
        },
        /** @returns {void} */
        set(key, value) {
            try {
                localStorage.setItem(key, value);
            } catch (_e) {
                /* Stockage non disponible (mode privé, quota dépassé) — ignoré. */
            }
        },
    };

    /* ─────────────────────────────────────────
       2. UTILITAIRES CHEMIN
    ───────────────────────────────────────── */

    /**
     * Normalise un chemin URL : supprime les barres obliques
     * finales et renvoie '/' pour la racine.
     * @param {string|null|undefined} value
     * @returns {string}
     */
    const normalizePath = (value) => {
        if (!value) return '/';
        let path = value;
        try {
            path = new URL(value, window.location.origin).pathname || '/';
        } catch (_e) {
            /* Conserve la chaîne originale si l'analyse échoue. */
        }
        path = path.replace(/\/+$/, '');
        return path === '' ? '/' : path;
    };

    /**
     * Résout le chemin de base du projet à partir de l'attribut
     * src du script courant.
     * @returns {string}
     */
    const resolveBasePath = () => {
        const SCRIPT_MARKER = '/assets/js/theme.js';
        for (const script of document.getElementsByTagName('script')) {
            const src = script.getAttribute('src') || '';
            if (!src.includes(SCRIPT_MARKER)) continue;
            try {
                const url = new URL(src, window.location.origin);
                const idx = url.pathname.indexOf(SCRIPT_MARKER);
                if (idx !== -1) {
                    return url.pathname.slice(0, idx).replace(/\/+$/, '');
                }
            } catch (_e) {
                /* Src invalide — ignoré. */
            }
        }
        return '';
    };

    const appBasePath = resolveBasePath();

    /**
     * Préfixe un chemin relatif avec le chemin de base de l'application.
     * @param {string} relative
     * @returns {string}
     */
    const withBasePath = (relative) => {
        const clean = (relative || '').replace(/^\/+/, '');
        return (appBasePath ? `${appBasePath}/` : '/') + clean;
    };

    /**
     * Détermine la clé de vue à partir du chemin courant.
     * @returns {string}
     */
    const viewPathFromLocation = () => {
        const current = normalizePath(window.location.pathname || '/');
        if (appBasePath && current.startsWith(appBasePath)) {
            const trimmed = current.slice(appBasePath.length);
            return normalizePath(trimmed === '' ? '/' : trimmed);
        }
        return current;
    };

    /* ─────────────────────────────────────────
       3. INJECTION DES STYLESHEETS DE SUPPORT
    ───────────────────────────────────────── */

    const ensureStylesheet = (selector, href, dataAttr, moveToEnd = false) => {
        const head = document.head || document.documentElement;
        if (!head) return;

        const existing = head.querySelector(selector);
        if (existing) {
            if (moveToEnd && existing.parentNode === head) {
                head.appendChild(existing);
            }
            return;
        }

        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        link.setAttribute(dataAttr, '1');
        head.appendChild(link);
    };

    const ensureFontStylesheet = () => {
        ensureStylesheet(
            'link[data-app-fonts="1"], link[href*="fonts.googleapis.com/css2?family=Inter"]',
            'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap',
            'data-app-fonts'
        );
    };

    const ensureIconStylesheet = () => {
        ensureStylesheet(
            'link[data-app-icons="1"], link[href*="/assets/vendor/fontawesome/all.min.css"], link[href*="assets/vendor/fontawesome/all.min.css"]',
            `${withBasePath('assets/vendor/fontawesome/all.min.css')}?v=20260612-icons`,
            'data-app-icons'
        );
    };

    const ensureIconColorStylesheet = (moveToEnd = false) => {
        ensureStylesheet(
            'link[data-app-icon-colors="1"], link[href*="/assets/css/icon-colors.css"], link[href*="assets/css/icon-colors.css"]',
            `${withBasePath('assets/css/icon-colors.css')}?v=20260618-icon-colors`,
            'data-app-icon-colors',
            moveToEnd
        );
    };

    const ensureTypographyStylesheet = (moveToEnd = false) => {
        ensureStylesheet(
            'link[data-app-typography="1"], link[href*="/assets/css/app-typography.css"], link[href*="assets/css/app-typography.css"]',
            `${withBasePath('assets/css/app-typography.css')}?v=20260612-typography`,
            'data-app-typography',
            moveToEnd
        );
    };

    const ensureStudentStylesheet = (moveToEnd = false) => {
        ensureStylesheet(
            'link[data-app-student-theme="1"], link[href*="/assets/css/student_dashboard.css"], link[href*="assets/css/student_dashboard.css"]',
            `${withBasePath('assets/css/student_dashboard.css')}?v=20260507-student-shell-v2`,
            'data-app-student-theme',
            moveToEnd
        );
    };


    /* ─────────────────────────────────────────
       4. SÉMANTIQUE DU BODY (rôle + vue)
    ───────────────────────────────────────── */

    const resolveRoleFromViewKey = (viewKey) => {
        const rootSegment = (viewKey || 'web').split('/')[0] || 'web';
        if (rootSegment === 'teacher') return 'teacher';
        if (rootSegment === 'student') return 'student';
        if (rootSegment === 'admin') return 'admin';
        if (rootSegment === 'company') return 'company';
        if (['login', 'register', 'forgot', 'reset', 'verify', 'auth'].includes(rootSegment)) {
            return 'auth';
        }
        return 'web';
    };

    const currentViewKey = () => {
        const viewPath = viewPathFromLocation();
        return viewPath === '/' ? 'web/home' : viewPath.replace(/^\/+/, '');
    };

    /**
     * Applique data-view, app-view et app-role-* sur <body>
     * selon l'URL courante.
     */
    const applyBodySemantics = () => {
        const body = document.body;
        if (!body) return;
        const root = document.documentElement;

        const hintedView = (body.dataset.view || '').trim();
        const explicitRoleClass = Array.from(body.classList).find((entry) => entry.startsWith('app-role-')) || '';
        const explicitRole = explicitRoleClass.replace('app-role-', '') || '';
        const viewKey = hintedView !== '' ? hintedView : currentViewKey();
        const role = hintedView !== ''
            ? resolveRoleFromViewKey(viewKey)
            : (explicitRole || resolveRoleFromViewKey(viewKey));

        if (!body.dataset.view) {
            body.dataset.view = viewKey;
        }

        if (root && !root.dataset.view) {
            root.dataset.view = viewKey;
        }

        if (
            !body.classList.contains('app-view') &&
            /^(teacher|student|admin|company)\b/.test(viewKey)
        ) {
            body.classList.add('app-view');
        }

        ROLES.forEach((name) => body.classList.remove(`app-role-${name}`));
        body.classList.add(`app-role-${role}`);
    };

    /**
     * Applique un mode de densité cohérent par espace.
     * Les espaces métier sont compacts, le public reste plus aéré.
     */
    const applyDensityMode = () => {
        const body = document.body;
        if (!body) return;

        const roleClass = Array.from(body.classList).find((entry) => entry.startsWith('app-role-')) || '';
        const role = roleClass.replace('app-role-', '') || 'web';
        const explicitDensity = body.dataset.density;

        if (explicitDensity === 'compact' || explicitDensity === 'comfortable') {
            return;
        }

        const denseRoles = new Set(['admin', 'teacher', 'student', 'company', 'auth']);
        body.dataset.density = denseRoles.has(role) ? 'compact' : 'comfortable';
    };

    /* ─────────────────────────────────────────
       5. CONFIGURATION TAILWIND CDN
    ───────────────────────────────────────── */

    const tailwindThemeConfig = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    primary: {
                        50: '#eff6ff',
                        100: '#dbeafe',
                        400: '#60a5fa',
                        500: '#3b82f6',
                        600: '#2563eb',
                        700: '#1d4ed8',
                        900: '#1e3a8a',
                    },
                    accent: {
                        400: '#7dd3fc',
                        500: '#38bdf8',
                    },
                    success: { DEFAULT: '#22C55E', dark: '#4ADE80' },
                    danger: { DEFAULT: '#EF4444', dark: '#F87171' },
                    warning: { DEFAULT: '#F59E0B', dark: '#FCD34D' },
                    info: { DEFAULT: '#2563eb', dark: '#60A5FA' },
                    slate: {
                        50: '#ffffff',
                        100: '#f8fbff',
                        200: '#ecf2ff',
                        300: '#dbe7ff',
                        400: '#9cb8dd',
                        500: '#7696c6',
                        600: '#5677ab',
                        700: '#35588d',
                        800: '#112d60',
                        900: '#081c42',
                        950: '#030d23',
                    },
                    surface: {
                        50: '#ffffff',
                        100: '#f8fbff',
                        800: '#112d60',
                        900: '#081c42',
                    },
                },
                fontFamily: {
                    sans: ['"Inter"', '"Segoe UI"', 'system-ui', 'sans-serif'],
                    heading: ['"Inter"', '"Segoe UI"', 'system-ui', 'sans-serif'],
                    body: ['"Inter"', '"Segoe UI"', 'system-ui', 'sans-serif'],
                    ui: ['"Inter"', '"Segoe UI"', 'system-ui', 'sans-serif'],
                    mono: ['"IBM Plex Mono"', '"Cascadia Code"', 'Consolas', 'monospace'],
                    numeric: ['"IBM Plex Mono"', '"Cascadia Code"', 'Consolas', 'monospace'],
                    display: ['"Inter"', '"Segoe UI"', 'system-ui', 'sans-serif'],
                },
                animation: {
                    'fade-in': 'fadeIn 0.4s ease-out both',
                    'fade-in-fast': 'fadeIn 0.2s ease-out both',
                    'fade-out': 'fadeOut 0.3s ease-in both',
                    'slide-up': 'slideUp 0.4s ease-out both',
                    'slide-down': 'slideDown 0.3s ease-out both',
                    'slide-left': 'slideLeft 0.35s ease-out both',
                    'slide-right': 'slideRight 0.35s ease-out both',
                    'bounce-in': 'bounceIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both',
                    'bounce-in-up': 'bounceInUp 0.5s cubic-bezier(0.34,1.56,0.64,1) both',
                    'scale-in': 'scaleIn 0.3s cubic-bezier(0.34,1.56,0.64,1) both',
                    'scale-out': 'scaleOut 0.2s ease-in both',
                    'spin-slow': 'spin 2s linear infinite',
                    'pulse-soft': 'pulseSoft 2s cubic-bezier(0.4,0,0.6,1) infinite',
                    shimmer: 'shimmer 1.8s ease-in-out infinite',
                    'ping-soft': 'pingSoft 1.5s cubic-bezier(0,0,0.2,1) infinite',
                    shake: 'shake 0.5s cubic-bezier(0.36,0.07,0.19,0.97) both',
                    wiggle: 'wiggle 0.6s ease-in-out',
                    heartbeat: 'heartbeat 1.2s ease-in-out infinite',
                    float: 'float 3s ease-in-out infinite',
                    'gradient-x': 'gradientX 4s ease infinite',
                    progress: 'progress linear both',
                    'count-up': 'countUp 0.6s ease-out both',
                    floaty: 'float 3s ease-in-out infinite',
                    fadeUp: 'slideUp 0.4s ease-out both',
                    pulseSoft: 'pulseSoft 2s cubic-bezier(0.4,0,0.6,1) infinite',
                },
                keyframes: {
                    fadeIn: { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                    fadeOut: { '0%': { opacity: '1' }, '100%': { opacity: '0' } },
                    slideUp: {
                        '0%': { opacity: '0', transform: 'translateY(20px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' },
                    },
                    slideDown: {
                        '0%': { opacity: '0', transform: 'translateY(-16px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' },
                    },
                    slideLeft: {
                        '0%': { opacity: '0', transform: 'translateX(20px)' },
                        '100%': { opacity: '1', transform: 'translateX(0)' },
                    },
                    slideRight: {
                        '0%': { opacity: '0', transform: 'translateX(-20px)' },
                        '100%': { opacity: '1', transform: 'translateX(0)' },
                    },
                    bounceIn: {
                        '0%': { opacity: '0', transform: 'scale(0.72)' },
                        '60%': { opacity: '1', transform: 'scale(1.04)' },
                        '100%': { transform: 'scale(1)' },
                    },
                    bounceInUp: {
                        '0%': { opacity: '0', transform: 'translateY(28px) scale(0.92)' },
                        '60%': { opacity: '1', transform: 'translateY(-6px) scale(1.02)' },
                        '100%': { transform: 'translateY(0) scale(1)' },
                    },
                    scaleIn: { '0%': { opacity: '0', transform: 'scale(0.85)' }, '100%': { opacity: '1', transform: 'scale(1)' } },
                    scaleOut: { '0%': { opacity: '1', transform: 'scale(1)' }, '100%': { opacity: '0', transform: 'scale(0.85)' } },
                    pulseSoft: { '0%,100%': { opacity: '1' }, '50%': { opacity: '0.35' } },
                    shimmer: { '0%': { backgroundPosition: '-200% 0' }, '100%': { backgroundPosition: '200% 0' } },
                    pingSoft: {
                        '0%': { transform: 'scale(1)', opacity: '0.8' },
                        '75%,100%': { transform: 'scale(1.8)', opacity: '0' },
                    },
                    shake: {
                        '0%,100%': { transform: 'translateX(0)' },
                        '10%,50%,90%': { transform: 'translateX(-6px)' },
                        '30%,70%': { transform: 'translateX(6px)' },
                    },
                    wiggle: {
                        '0%,100%': { transform: 'rotate(0deg)' },
                        '25%': { transform: 'rotate(-8deg)' },
                        '75%': { transform: 'rotate(8deg)' },
                    },
                    heartbeat: {
                        '0%,100%': { transform: 'scale(1)' },
                        '14%': { transform: 'scale(1.15)' },
                        '28%': { transform: 'scale(1)' },
                        '42%': { transform: 'scale(1.15)' },
                        '70%': { transform: 'scale(1)' },
                    },
                    float: {
                        '0%,100%': { transform: 'translateY(0px)' },
                        '50%': { transform: 'translateY(-8px)' },
                    },
                    gradientX: {
                        '0%,100%': { backgroundPosition: '0% 50%' },
                        '50%': { backgroundPosition: '100% 50%' },
                    },
                    progress: { '0%': { width: '100%' }, '100%': { width: '0%' } },
                    countUp: {
                        '0%': { opacity: '0', transform: 'translateY(10px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' },
                    },
                },
                transitionDuration: {
                    0: '0ms', 150: '150ms', 200: '200ms',
                    250: '250ms', 300: '300ms', 400: '400ms',
                    500: '500ms', 700: '700ms',
                },
                boxShadow: {
                    card: '0 10px 22px -20px rgba(15,23,42,0.18)',
                    'card-hover': '0 16px 32px -26px rgba(15,23,42,0.22)',
                    modal: '0 20px 60px rgba(0,0,0,0.20)',
                    glow: '0 0 20px rgba(37,99,235,0.35)',
                    'glow-accent': '0 0 20px rgba(56,189,248,0.35)',
                    'inner-top': 'inset 0 1px 0 rgba(255,255,255,0.10)',
                    soft: '0 18px 38px -30px rgba(15,23,42,0.24)',
                },
            },
        },
    };

    /* ─────────────────────────────────────────
       6. GESTION DU THÈME
    ───────────────────────────────────────── */

    /**
     * Normalise le mode de taille de police.
     * @param {string|null|undefined} value
     * @returns {'compact'|'comfort'|null}
     */
    const normalizeFontScale = (value) => {
        const normalized = String(value || '').trim().toLowerCase();
        if (normalized === 'compact') return 'compact';
        if (normalized === 'comfort' || normalized === 'comfortable') return 'comfort';
        return null;
    };

    /**
     * Synchronise l'etat visuel des controles de taille de police.
     * @param {string|null|undefined} mode
     */
    const updateFontScaleButtons = (mode) => {
        const currentMode = LOCKED_FONT_SCALE || normalizeFontScale(mode) || 'comfort';
        document.querySelectorAll('[data-font-scale-toggle]').forEach((button) => {
            const targetMode = normalizeFontScale(button.getAttribute('data-font-scale-toggle'));
            if (!targetMode) return;

            const isActive = targetMode === currentMode;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    };

    /**
     * Applique le mode de taille de police sur <html> et le persiste si demandé.
     * @param {string|null|undefined} next
     * @param {boolean} persist
     */
    const setFontScale = (next, persist) => {
        const fontScale = LOCKED_FONT_SCALE || normalizeFontScale(next) || 'comfort';
        document.documentElement.setAttribute('data-font-scale', fontScale);
        if (persist) safeStorage.set(FONT_SCALE_STORAGE_KEY, fontScale);
        updateFontScaleButtons(fontScale);
    };

    /** Initialise le mode de taille depuis le stockage local. */
    const initFontScale = () => {
        if (LOCKED_FONT_SCALE) {
            setFontScale(LOCKED_FONT_SCALE, true);
            return;
        }
        const stored = normalizeFontScale(safeStorage.get(FONT_SCALE_STORAGE_KEY));
        setFontScale(stored || 'comfort', false);
    };

    window.setAppFontScale = (mode) => {
        if (LOCKED_FONT_SCALE) {
            setFontScale(LOCKED_FONT_SCALE, true);
            return;
        }
        const normalized = normalizeFontScale(mode);
        if (!normalized || !VALID_FONT_SCALES.has(normalized)) return;
        setFontScale(normalized, true);
    };

    window.toggleAppFontScale = () => {
        if (LOCKED_FONT_SCALE) {
            setFontScale(LOCKED_FONT_SCALE, true);
            return;
        }
        const current = normalizeFontScale(document.documentElement.getAttribute('data-font-scale')) || 'comfort';
        window.setAppFontScale(current === 'compact' ? 'comfort' : 'compact');
    };

    /** @returns {'dark'|'light'} */
    const getSystemTheme = () =>
        window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

    /**
     * Met à jour le texte et l'état aria des boutons de bascule.
     * @param {'dark'|'light'} theme
     */
    const THEME_ICON_LIGHT = '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><path d="M12 1v2"></path><path d="M12 21v2"></path><path d="m4.22 4.22 1.42 1.42"></path><path d="m18.36 18.36 1.42 1.42"></path><path d="M1 12h2"></path><path d="M21 12h2"></path><path d="m4.22 19.78 1.42-1.42"></path><path d="m18.36 5.64 1.42-1.42"></path></svg>';
    const THEME_ICON_DARK = '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.4A8.5 8.5 0 1 1 11.6 3a7 7 0 1 0 9.4 9.4Z"></path></svg>';

    const updateButtons = (theme) => {
        const isDark = theme === 'dark';
        const label = isDark ? 'Activer le mode clair' : 'Activer le mode sombre';
        const pressed = isDark ? 'true' : 'false';
        const icon = isDark ? THEME_ICON_LIGHT : THEME_ICON_DARK;

        document.querySelectorAll('[data-theme-toggle], #theme-toggle').forEach((btn) => {
            let iconNode = btn.querySelector('[data-theme-label]');
            if (!iconNode) {
                iconNode = document.createElement('span');
                iconNode.setAttribute('data-theme-label', '');
                btn.replaceChildren(iconNode);
            }

            iconNode.innerHTML = icon;
            iconNode.setAttribute('aria-hidden', 'true');

            btn.classList.add('app-theme-toggle-icon');
            btn.setAttribute('aria-label', label);
            btn.setAttribute('title', label);
            btn.setAttribute('aria-pressed', pressed);
        });
    };

    /**
     * Applique un thème sur <html> et le persiste si demandé.
     * @param {string}  next    - Thème cible ('dark' | 'light')
     * @param {boolean} persist - Sauvegarder dans localStorage
     */
    const setTheme = (next, persist) => {
        const theme = VALID_THEMES.has(next) ? next : 'light';
        document.documentElement.classList.toggle('dark', theme === 'dark');
        document.documentElement.setAttribute('data-theme', theme);
        if (persist) safeStorage.set(STORAGE_KEY, theme);
        updateButtons(theme);
    };

    /** Initialise le thème depuis localStorage ou la préférence système. */
    const initTheme = () => {
        const stored  = safeStorage.get(STORAGE_KEY);
        const initial = VALID_THEMES.has(stored) ? stored : getSystemTheme();
        setTheme(initial, false);
    };

    /** Synchronise les boutons avec le thème courant du DOM. */
    const syncButtons = () => {
        const current = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        updateButtons(current);
    };

    /**
     * Bascule le thème et anime l'icône de thème si présente.
     * Exposé globalement pour usage depuis les vues PHP.
     */
    window.toggleTheme = () => {
        const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
        setTheme(next, true);
        document.querySelectorAll('[data-theme-label] svg').forEach((icon) => {
            icon.classList.add('animate-scale-in');
            setTimeout(() => icon.classList.remove('animate-scale-in'), 300);
        });
    };

    /* ─────────────────────────────────────────
       7. BOUTON FAB (créé si aucun bouton n'existe)
    ───────────────────────────────────────── */

    /**
     * Injecte un bouton FAB de bascule de thème dans <body>
     * si aucun bouton [data-theme-toggle] n'est présent.
     */
    const ensureFloatingToggle = () => {
        if (document.querySelector('[data-theme-toggle], #theme-toggle') || !document.body) {
            return;
        }
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'app-theme-fab';
        btn.setAttribute('data-theme-toggle', '1');
        btn.setAttribute('aria-label', 'Activer le mode sombre');
        btn.setAttribute('aria-pressed', document.documentElement.classList.contains('dark') ? 'true' : 'false');
        const icon = document.createElement('span');
        icon.setAttribute('data-theme-label', '');
        icon.setAttribute('aria-hidden', 'true');
        btn.appendChild(icon);
        document.body.appendChild(btn);
    };

    /* —————————————————————————————————————————————————————
       7.b BOUTON "RETOUR EN HAUT"
    ————————————————————————————————————————————————————— */

    const KPI_ICON_HIDE = '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12Z"></path><circle cx="12" cy="12" r="3.1"></circle><path d="m4 4 16 16"></path></svg>';
    const KPI_ICON_SHOW = '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12Z"></path><circle cx="12" cy="12" r="3.1"></circle></svg>';

    const isDashboardRole = () => {
        const body = document.body;
        if (!body) return false;
        return body.matches('.app-role-teacher[data-view], .app-role-student[data-view], .app-role-admin[data-view], .app-role-company[data-view]');
    };

    const updateKpiToggleButtons = (hidden) => {
        const label = hidden ? 'Afficher KPI' : 'Masquer KPI';
        const title = hidden ? 'Afficher les indicateurs KPI' : 'Masquer les indicateurs KPI';
        const icon = hidden ? KPI_ICON_SHOW : KPI_ICON_HIDE;

        document.querySelectorAll('[data-kpi-toggle]').forEach((button) => {
            const iconNode = button.querySelector('[data-kpi-icon]');
            if (iconNode) {
                iconNode.innerHTML = icon;
            }

            const labelNode = button.querySelector('[data-kpi-label]');
            if (labelNode) {
                labelNode.textContent = label;
            }

            button.classList.toggle('is-active', hidden);
            button.setAttribute('aria-pressed', hidden ? 'true' : 'false');
            button.setAttribute('aria-label', title);
            button.setAttribute('title', title);
        });
    };

    const setMobileKpiHidden = (hidden, persist) => {
        const body = document.body;
        if (!body) return;

        body.classList.toggle(KPI_MOBILE_HIDDEN_CLASS, hidden);
        body.dataset.kpiMobile = hidden ? 'hidden' : 'shown';
        updateKpiToggleButtons(hidden);

        if (persist) {
            safeStorage.set(KPI_MOBILE_STORAGE_KEY, hidden ? 'hidden' : 'shown');
        }
    };

    window.toggleMobileKpiVisibility = () => {
        const body = document.body;
        if (!body) return;
        const nextHidden = !body.classList.contains(KPI_MOBILE_HIDDEN_CLASS);
        setMobileKpiHidden(nextHidden, true);
    };

    const ensureKpiToggleButton = () => {
        if (!isDashboardRole()) return;

        let hasToggle = false;

        document.querySelectorAll('.app-shell-toolbar__actions').forEach((actions) => {
            if (actions.querySelector('[data-kpi-toggle]')) {
                hasToggle = true;
                return;
            }

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'app-shell-chip app-shell-chip--kpi inline-flex shrink-0 items-center gap-2 md:hidden';
            button.setAttribute('data-kpi-toggle', '1');
            button.setAttribute('aria-pressed', 'false');
            button.innerHTML = '<span class="app-icon" data-kpi-icon aria-hidden="true"></span><span data-kpi-label>KPI</span>';
            actions.appendChild(button);
            hasToggle = true;
        });

        if (!hasToggle && document.body && !document.querySelector('[data-kpi-toggle-fab]')) {
            const fallback = document.createElement('button');
            fallback.type = 'button';
            fallback.className = 'app-shell-chip app-shell-chip--kpi app-kpi-fab md:hidden inline-flex items-center gap-2';
            fallback.setAttribute('data-kpi-toggle', '1');
            fallback.setAttribute('data-kpi-toggle-fab', '1');
            fallback.setAttribute('aria-pressed', 'false');
            fallback.innerHTML = '<span class="app-icon" data-kpi-icon aria-hidden="true"></span><span data-kpi-label>KPI</span>';
            document.body.appendChild(fallback);
        }
    };

    const initMobileKpiToggle = () => {
        if (!document.body) return;

        ensureKpiToggleButton();
        if (!isDashboardRole()) return;

        const stored = safeStorage.get(KPI_MOBILE_STORAGE_KEY);
        setMobileKpiHidden(stored === 'hidden', false);
    };

    const fontScaleControl = {
        container: null,
    };

    /**
     * Injecte un mini-switch global de taille de police.
     */
    const ensureFontScaleControl = () => {
        if (!document.body) return;
        if (LOCKED_FONT_SCALE) {
            const existing = document.querySelector('[data-font-scale-fab]');
            if (existing && typeof existing.remove === 'function') {
                existing.remove();
            }
            return;
        }

        let container = fontScaleControl.container;
        if (!container || !document.body.contains(container)) {
            container = document.querySelector('[data-font-scale-fab]');
            if (!container) {
                container = document.createElement('div');
                container.className = 'app-font-scale-fab';
                container.setAttribute('data-font-scale-fab', '1');
                container.setAttribute('role', 'group');
                container.setAttribute('aria-label', 'Taille de police');
                container.innerHTML = [
                    '<button type="button" class="app-font-scale-btn" data-font-scale-toggle="compact" aria-label="Mode compact" title="Mode compact">A-</button>',
                    '<button type="button" class="app-font-scale-btn" data-font-scale-toggle="comfort" aria-label="Mode confort" title="Mode confort">A+</button>',
                ].join('');
                document.body.appendChild(container);
            }

            fontScaleControl.container = container;
        }

        const mode = normalizeFontScale(document.documentElement.getAttribute('data-font-scale')) || 'comfort';
        updateFontScaleButtons(mode);
    };

    const scrollTopControl = {
        initialized: false,
        button: null,
        root: null,
    };

    const getScrollTopRoot = () => {
        const candidate = document.querySelector('[data-scroll-top-root]');
        return candidate instanceof HTMLElement ? candidate : null;
    };

    const updateScrollTopButtonVisibility = () => {
        const button = scrollTopControl.button;
        if (!button) return;

        const root = scrollTopControl.root instanceof HTMLElement ? scrollTopControl.root : null;
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
        const distanceToBottom = Math.max(0, documentHeight - (scrollTop + viewportHeight));
        const progress = canScroll ? (scrollTop / Math.max(1, documentHeight - viewportHeight)) : 0;
        const showButton = canScroll && (progress >= 0.42 || distanceToBottom <= 420);

        button.classList.toggle('is-visible', showButton);
        button.setAttribute('aria-hidden', showButton ? 'false' : 'true');
    };

    const ensureScrollTopButton = () => {
        if (!document.body) return;

        let button = scrollTopControl.button;
        if (!button || !document.body.contains(button)) {
            button = document.querySelector('[data-scroll-top]');
            if (!button) {
                button = document.createElement('button');
                button.type = 'button';
                button.className = 'app-scroll-top';
                button.setAttribute('data-scroll-top', '1');
                button.setAttribute('aria-label', 'Remonter en haut de la page');
                button.setAttribute('aria-hidden', 'true');
                button.setAttribute('title', 'Remonter en haut');
                button.innerHTML = '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="m6 15 6-6 6 6"></path></svg>';
                if (document.querySelector('a[aria-label="Assistance"], button[aria-label="Assistance"], [data-support-fab]')) {
                    button.style.bottom = '5.25rem';
                }
                document.body.appendChild(button);
            }

            if (button.dataset.scrollTopBound !== '1') {
                button.addEventListener('click', (event) => {
                    event.preventDefault();
                    if (scrollTopControl.root instanceof HTMLElement) {
                        scrollTopControl.root.scrollTo({ top: 0, behavior: 'smooth' });
                        return;
                    }

                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
                button.dataset.scrollTopBound = '1';
            }

            scrollTopControl.button = button;
        }

        scrollTopControl.root = getScrollTopRoot();

        if (!scrollTopControl.initialized) {
            if (scrollTopControl.root instanceof HTMLElement) {
                scrollTopControl.root.addEventListener('scroll', updateScrollTopButtonVisibility, { passive: true });
            } else {
                window.addEventListener('scroll', updateScrollTopButtonVisibility, { passive: true });
            }
            window.addEventListener('resize', updateScrollTopButtonVisibility, { passive: true });
            window.addEventListener('load', updateScrollTopButtonVisibility, { passive: true });
            window.addEventListener('pageshow', updateScrollTopButtonVisibility, { passive: true });
            scrollTopControl.initialized = true;
        }

        updateScrollTopButtonVisibility();
    };

    const toolbarOffsetControl = {
        observer: null,
        toolbar: null,
    };

    const syncToolbarOffset = () => {
        const body = document.body;
        if (!body) return;

        const toolbar = toolbarOffsetControl.toolbar && document.body.contains(toolbarOffsetControl.toolbar)
            ? toolbarOffsetControl.toolbar
            : document.querySelector('.app-shell-toolbar');

        if (!toolbar) return;

        toolbarOffsetControl.toolbar = toolbar;
        const height = Math.ceil(toolbar.getBoundingClientRect().height);
        if (height > 0) {
            body.style.setProperty('--app-toolbar-offset-current', `${height}px`);
        }
    };

    const ensureToolbarOffsetSync = () => {
        if (!document.body) return;

        if (!toolbarOffsetControl.toolbar || !document.body.contains(toolbarOffsetControl.toolbar)) {
            toolbarOffsetControl.toolbar = document.querySelector('.app-shell-toolbar');
        }

        const toolbar = toolbarOffsetControl.toolbar;
        if (!toolbar) return;

        syncToolbarOffset();

        if (toolbarOffsetControl.observer) {
            toolbarOffsetControl.observer.disconnect();
            toolbarOffsetControl.observer = null;
        }

        if ('ResizeObserver' in window) {
            const observer = new ResizeObserver(syncToolbarOffset);
            observer.observe(toolbar);
            toolbarOffsetControl.observer = observer;
        }

        window.addEventListener('load', syncToolbarOffset, { once: true });
        window.addEventListener('pageshow', syncToolbarOffset, { passive: true });
        window.addEventListener('resize', syncToolbarOffset, { passive: true });
        window.addEventListener('orientationchange', syncToolbarOffset, { passive: true });
    };

    /* ─────────────────────────────────────────
       8. LIEN ACTIF DANS LA BARRE LATÉRALE
    ───────────────────────────────────────── */

    /**
     * Ajoute la classe `is-active-nav` et l'attribut `aria-current="page"`
     * au lien de la barre latérale correspondant à la page courante.
     */
    const highlightCurrentNavLink = () => {
        const current = normalizePath(window.location.pathname);
        document.querySelectorAll('.js-sidebar nav a[href]').forEach((anchor) => {
            const href = anchor.getAttribute('href');
            if (!href || href.startsWith('#')) return;
            try {
                const target = normalizePath(anchor.href);
                if (target === current) {
                    anchor.classList.add('is-active-nav');
                    anchor.setAttribute('aria-current', 'page');
                }
            } catch (_e) {
                /* href invalide — ignoré. */
            }
        });
    };

    /* ─────────────────────────────────────────────────────────
       8.b RECHERCHE RAPIDE GLOBALE (Ctrl/Cmd + K)
    ───────────────────────────────────────────────────────── */

    /**
     * Crée une palette de commandes globale pour naviguer rapidement.
     * Sources: liens sidebar, topbar et actions visibles de la page.
     */
    const initCommandPalette = () => {
        if (!document.body || document.getElementById('app-command-palette')) return;

        const overlay = document.createElement('div');
        overlay.id = 'app-command-palette';
        overlay.className = 'app-cmdk-overlay hidden';
        overlay.innerHTML = `
            <div class="app-cmdk-panel" role="dialog" aria-modal="true" aria-label="Recherche rapide">
                <div class="app-cmdk-head">
                    <input id="app-cmdk-input" class="app-cmdk-input" type="text" placeholder="Rechercher une page, action, section..." autocomplete="off" />
                    <span class="app-cmdk-hint">Echap</span>
                </div>
                <div id="app-cmdk-list" class="app-cmdk-list" role="listbox" aria-label="Résultats"></div>
                <div class="app-cmdk-foot">Navigation: ↑ ↓ · Entrée</div>
            </div>
        `;
        document.body.appendChild(overlay);

        const input = /** @type {HTMLInputElement|null} */ (overlay.querySelector('#app-cmdk-input'));
        const list = overlay.querySelector('#app-cmdk-list');
        if (!input || !list) return;

        let commands = [];
        let filtered = [];
        let activeIndex = 0;
        let isOpen = false;

        const norm = (value) =>
            String(value || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/\s+/g, ' ')
                .trim();

        const collectCommands = () => {
            const map = new Map();
            const anchors = Array.from(document.querySelectorAll('a[href]'));

            anchors.forEach((anchor) => {
                const rawHref = anchor.getAttribute('href') || '';
                if (!rawHref || rawHref.startsWith('#') || rawHref.startsWith('javascript:') || rawHref.startsWith('mailto:')) {
                    return;
                }

                const label = (anchor.textContent || '').replace(/\s+/g, ' ').trim();
                if (!label || label.length < 2) {
                    return;
                }

                let href = '';
                try {
                    href = anchor.href;
                } catch (_e) {
                    return;
                }

                const path = normalizePath(href);
                const key = `${path}::${norm(label)}`;
                if (map.has(key)) {
                    return;
                }

                map.set(key, {
                    type: 'link',
                    label,
                    path,
                    action: () => {
                        window.location.assign(href);
                    },
                });
            });

            map.set('__theme_toggle__', {
                type: 'action',
                label: 'Basculer le thème',
                path: 'action',
                action: () => window.toggleTheme?.(),
            });

            map.set('__font_scale_compact__', {
                type: 'action',
                label: 'Typographie compacte (fixe)',
                path: 'action',
                action: () => window.setAppFontScale?.('compact'),
            });

            commands = Array.from(map.values()).slice(0, 180);
        };

        const scoreCommand = (cmd, query) => {
            const q = norm(query);
            if (!q) return 1;
            const label = norm(cmd.label);
            const path = norm(cmd.path);
            const hay = `${label} ${path}`;
            if (!hay.includes(q)) return 0;
            if (label.startsWith(q)) return 120;
            if (path.startsWith(q)) return 90;
            if (label.includes(q)) return 70;
            return 50;
        };

        const render = () => {
            const q = input.value || '';
            filtered = commands
                .map((cmd) => ({ cmd, score: scoreCommand(cmd, q) }))
                .filter((item) => item.score > 0)
                .sort((a, b) => b.score - a.score)
                .slice(0, 10)
                .map((item) => item.cmd);

            activeIndex = Math.max(0, Math.min(activeIndex, filtered.length - 1));

            if (!filtered.length) {
                list.innerHTML = '<div class="app-cmdk-empty">Aucun résultat</div>';
                return;
            }

            list.innerHTML = '';
            filtered.forEach((cmd, index) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `app-cmdk-item ${index === activeIndex ? 'is-active' : ''}`;
                btn.setAttribute('role', 'option');
                btn.innerHTML = `<span>${cmd.label}</span><small>${cmd.path}</small>`;
                btn.addEventListener('click', () => {
                    close();
                    cmd.action();
                });
                list.appendChild(btn);
            });
        };

        const open = () => {
            collectCommands();
            overlay.classList.remove('hidden');
            isOpen = true;
            input.value = '';
            activeIndex = 0;
            render();
            setTimeout(() => input.focus(), 10);
        };

        const close = () => {
            overlay.classList.add('hidden');
            isOpen = false;
        };

        input.addEventListener('input', () => {
            activeIndex = 0;
            render();
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                activeIndex = Math.min(filtered.length - 1, activeIndex + 1);
                render();
                return;
            }
            if (event.key === 'ArrowUp') {
                event.preventDefault();
                activeIndex = Math.max(0, activeIndex - 1);
                render();
                return;
            }
            if (event.key === 'Enter') {
                event.preventDefault();
                const cmd = filtered[activeIndex];
                if (cmd) {
                    close();
                    cmd.action();
                }
            }
        });

        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) close();
        });

        document.addEventListener('keydown', (event) => {
            const hasModifier = event.ctrlKey || event.metaKey;
            if (hasModifier && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                if (isOpen) close();
                else open();
                return;
            }

            if (!isOpen) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                close();
            }
        });
    };

    /* ─────────────────────────────────────────
       9. CONFIGURATION TAILWIND CDN
    ───────────────────────────────────────── */

    /**
     * Applique la configuration Tailwind si le CDN est chargé.
     * @returns {boolean}
     */
    const ensureTailwindCdn = () => {
        if (window.tailwind) return;
        const head = document.head || document.documentElement;
        if (!head) return;

        const existing = head.querySelector('script[data-app-tailwind="1"], script[src*="cdn.tailwindcss.com"]');
        if (existing) return;

        const script = document.createElement('script');
        script.src = 'https://cdn.tailwindcss.com';
        script.defer = true;
        script.setAttribute('data-app-tailwind', '1');
        script.onload = () => {
            if (typeof window.tailwind === 'undefined') return;
            if (typeof applyTailwindConfig === 'function') {
                applyTailwindConfig();
                return;
            }
            if (typeof window.tailwind.refresh === 'function') {
                window.tailwind.refresh();
            }
        };
        head.appendChild(script);
    };

    const applyTailwindConfig = () => {
        if (!window.tailwind) return false;
        window.tailwind.config = tailwindThemeConfig;
        if (typeof window.tailwind.refresh === 'function') {
            window.tailwind.refresh();
        }
        return true;
    };

    /**
     * Tente d'appliquer la configuration Tailwind, avec des réessais
     * si le CDN n'est pas encore chargé (max 40 tentatives × 50 ms = 2 s).
     */
    const ensureTailwindConfig = () => {
        if (!window.tailwind) return;
        if (applyTailwindConfig()) return;
        let attempts = 0;
        const timer = setInterval(() => {
            attempts += 1;
            if (applyTailwindConfig() || attempts >= 40) clearInterval(timer);
        }, 50);
    };

    /* ─────────────────────────────────────────
       10. INITIALISATION
    ───────────────────────────────────────── */

    /* — Exécution immédiate (avant DOMContentLoaded) — */
    ensureFontStylesheet();
    ensureIconStylesheet();
    ensureIconColorStylesheet();
    ensureStudentStylesheet();
    ensureTypographyStylesheet(true);
    ensureTailwindCdn();
    const hintedBootView = (document.body?.dataset?.view || document.documentElement?.dataset?.view || '').trim();
    const hintedBootRoleClass = Array.from(document.body?.classList || []).find((entry) => entry.startsWith('app-role-')) || '';
    const hintedBootRole = hintedBootRoleClass.replace('app-role-', '') || '';
    const bootRole = resolveRoleFromViewKey(hintedBootView !== '' ? hintedBootView : (hintedBootRole !== '' ? hintedBootRole : currentViewKey()));
    if (bootRole === 'student') {
        ensureStudentStylesheet(true);
        ensureTypographyStylesheet(true);
    }
    initFontScale();
    initTheme();

    /* — Délégation d'événement pour les boutons de bascule — */
    document.addEventListener('click', (event) => {
        if (typeof event.target?.closest !== 'function') return;

        const themeBtn = event.target.closest('[data-theme-toggle], #theme-toggle');
        if (themeBtn) {
            event.preventDefault();
            window.toggleTheme();
            return;
        }

        const kpiBtn = event.target.closest('[data-kpi-toggle]');
        if (kpiBtn) {
            event.preventDefault();
            window.toggleMobileKpiVisibility?.();
            return;
        }

        const fontScaleBtn = event.target.closest('[data-font-scale-toggle], #font-scale-compact, #font-scale-comfort, #density-compact, #density-comfort, #td-density-compact, #td-density-comfort');
        if (!fontScaleBtn) return;

        let targetMode = normalizeFontScale(fontScaleBtn.getAttribute('data-font-scale-toggle'));
        if (!targetMode) {
            const controlId = String(fontScaleBtn.id || '').toLowerCase();
            if (controlId.includes('compact')) targetMode = 'compact';
            if (controlId.includes('comfort')) targetMode = 'comfort';
        }

        if (!targetMode || !VALID_FONT_SCALES.has(targetMode)) return;
        event.preventDefault();
        setFontScale(targetMode, true);
    });

    ensureTailwindConfig();

    /* — Actions post-DOM — */
    const initDom = () => {
        applyBodySemantics();
        applyDensityMode();
        const roleClass = Array.from(document.body?.classList || []).find((entry) => entry.startsWith('app-role-')) || '';
        const role = roleClass.replace('app-role-', '') || 'web';
        if (role === 'student') {
            ensureStudentStylesheet(true);
        }
        ensureIconStylesheet();
        ensureIconColorStylesheet(true);
        ensureTypographyStylesheet(true);
        ensureToolbarOffsetSync();
        initMobileKpiToggle();
        ensureFloatingToggle();
        ensureFontScaleControl();
        ensureScrollTopButton();
        syncButtons();
        highlightCurrentNavLink();
        initCommandPalette();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDom);
    } else {
        initDom();
    }
})();

