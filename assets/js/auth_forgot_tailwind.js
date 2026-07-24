(() => {
    'use strict';

    const root = document.documentElement;
    const storageKey = 'theme';
    let storedTheme = null;

    try {
        storedTheme = localStorage.getItem(storageKey);
    } catch (_error) {
        storedTheme = null;
    }

    const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches === true;
    const theme = storedTheme === 'dark' || (!storedTheme && prefersDark) ? 'dark' : 'light';

    root.classList.toggle('dark', theme === 'dark');
    root.classList.toggle('light', theme !== 'dark');
    root.setAttribute('data-theme', theme);
    root.style.colorScheme = theme;

    window.tailwind = window.tailwind || {};
    window.tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Inter', 'sans-serif'],
                },
            },
        },
    };
})();
