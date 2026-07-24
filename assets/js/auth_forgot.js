(() => {
    'use strict';

    const safeStorage = {
        get(storage, key) {
            try {
                return storage.getItem(key);
            } catch (_error) {
                return null;
            }
        },
        set(storage, key, value) {
            try {
                storage.setItem(key, value);
            } catch (_error) {
                /* Storage unavailable. */
            }
        },
    };

    const applyTheme = (theme, persist = false) => {
        const root = document.documentElement;
        const darkToggle = document.getElementById('darkModeToggle');
        const darkIcon = document.getElementById('darkIcon');
        const isDark = theme === 'dark';

        root.classList.toggle('dark', isDark);
        root.classList.toggle('light', !isDark);
        root.setAttribute('data-theme', isDark ? 'dark' : 'light');
        root.style.colorScheme = isDark ? 'dark' : 'light';

        if (persist) {
            safeStorage.set(localStorage, 'theme', isDark ? 'dark' : 'light');
        }

        if (darkToggle) {
            darkToggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        }

        if (darkIcon) {
            darkIcon.classList.toggle('fa-moon', !isDark);
            darkIcon.classList.toggle('fa-sun', isDark);
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        const currentTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        const darkToggle = document.getElementById('darkModeToggle');
        const stepRequest = document.getElementById('stepRequest');
        const stepConfirm = document.getElementById('stepConfirm');
        const forgotForm = document.getElementById('forgotForm');
        const resetEmailInput = document.getElementById('resetEmail');
        const submittedEmailSpan = document.getElementById('submittedEmail');
        const resendForm = document.getElementById('resendForm');
        const resendEmailInput = document.getElementById('resendEmail');

        applyTheme(currentTheme, false);

        darkToggle?.addEventListener('click', () => {
            const nextTheme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
            applyTheme(nextTheme, true);
        });

        forgotForm?.addEventListener('submit', () => {
            const email = resetEmailInput?.value.trim() || '';
            if (email) {
                safeStorage.set(sessionStorage, 'resetEmail', email);
            }
        });

        const storedEmail = safeStorage.get(sessionStorage, 'resetEmail') || '';
        if (storedEmail) {
            if (submittedEmailSpan) {
                submittedEmailSpan.textContent = storedEmail;
            }
            if (resendEmailInput) {
                resendEmailInput.value = storedEmail;
            }
        }

        resendForm?.addEventListener('submit', (event) => {
            const email = safeStorage.get(sessionStorage, 'resetEmail') || resetEmailInput?.value.trim() || '';
            if (!email) {
                event.preventDefault();
                stepConfirm?.classList.add('hidden');
                stepRequest?.classList.remove('hidden');
                resetEmailInput?.focus();
                return;
            }

            if (resendEmailInput) {
                resendEmailInput.value = email;
            }
        });
    });
})();
