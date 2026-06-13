(() => {
    'use strict';

    const root = document.documentElement;
    const darkToggle = document.getElementById('darkModeToggle');
    const darkIcon = document.getElementById('darkIcon');
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const toggleNew = document.getElementById('toggleNewPwd');
    const toggleConfirm = document.getElementById('toggleConfirmPwd');
    const matchMsg = document.getElementById('matchMessage');
    const critLength = document.getElementById('critLength');
    const critUpper = document.getElementById('critUpper');
    const critDigit = document.getElementById('critDigit');
    const critSpecial = document.getElementById('critSpecial');
    const strengthBadge = document.getElementById('strengthBadge');
    const resetForm = document.getElementById('resetPasswordForm');
    const resetClientNotice = document.getElementById('resetClientNotice');

    const buildNoticeMarkup = (eyebrow, title, message) => `
        <div class="auth-notice__row">
            <span class="auth-notice__icon" aria-hidden="true">
                <i class="fas fa-triangle-exclamation"></i>
            </span>
            <div class="auth-notice__copy">
                <p class="auth-notice__eyebrow">${eyebrow}</p>
                <p class="auth-notice__title">${title}</p>
                <p class="auth-notice__message">${message}</p>
            </div>
        </div>`;

    const setStoredTheme = (theme) => {
        try {
            localStorage.setItem('theme', theme);
        } catch (_error) {
            /* Storage can be unavailable in private contexts. */
        }
    };

    const getStoredTheme = () => {
        try {
            return localStorage.getItem('theme');
        } catch (_error) {
            return null;
        }
    };

    const applyTheme = (theme) => {
        const isDark = theme === 'dark';
        root.classList.toggle('dark', isDark);
        root.classList.toggle('light', !isDark);
        root.setAttribute('data-theme', theme);
        root.style.colorScheme = theme;

        if (darkIcon) {
            darkIcon.classList.toggle('fa-moon', !isDark);
            darkIcon.classList.toggle('fa-sun', isDark);
        }
        if (darkToggle) {
            darkToggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        }
    };

    const hideNotice = () => {
        if (!resetClientNotice) {
            return;
        }
        resetClientNotice.innerHTML = '';
        resetClientNotice.classList.add('hidden');
    };

    const showNotice = (title, message, eyebrow = 'Attention') => {
        if (!resetClientNotice) {
            return;
        }
        resetClientNotice.innerHTML = buildNoticeMarkup(eyebrow, title, message);
        resetClientNotice.classList.remove('hidden');
        resetClientNotice.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    };

    const clearFieldError = (field) => {
        if (!(field instanceof HTMLElement)) {
            return;
        }
        field.classList.remove('auth-field-error');
        field.removeAttribute('aria-invalid');
        field.style.removeProperty('border-color');
        field.style.removeProperty('box-shadow');
        if (typeof field.setCustomValidity === 'function') {
            field.setCustomValidity('');
        }
    };

    const markFieldError = (field, message = '') => {
        if (!(field instanceof HTMLElement)) {
            return;
        }
        field.classList.add('auth-field-error');
        field.setAttribute('aria-invalid', 'true');
        field.style.setProperty('border-color', 'rgb(220, 38, 38)', 'important');
        field.style.setProperty('box-shadow', '0 0 0 3px rgba(220, 38, 38, 0.18)', 'important');
        if (typeof field.setCustomValidity === 'function') {
            field.setCustomValidity(message);
        }
    };

    const focusField = (field) => {
        if (!(field instanceof HTMLElement)) {
            return;
        }
        field.focus({ preventScroll: true });
        field.scrollIntoView({ block: 'center', behavior: 'smooth' });
    };

    const clearValidationState = () => {
        [newPasswordInput, confirmPasswordInput].forEach(clearFieldError);
        hideNotice();
    };

    const reportFieldIssue = (field, title, message, eyebrow = 'Attention') => {
        markFieldError(field, message);
        showNotice(title, message, eyebrow);
        focusField(field);
        return false;
    };

    const prefersDark = () => window.matchMedia?.('(prefers-color-scheme: dark)').matches === true;
    const initialTheme = getStoredTheme() || (prefersDark() ? 'dark' : 'light');
    applyTheme(initialTheme);

    if (darkToggle) {
        darkToggle.addEventListener('click', () => {
            const nextTheme = root.classList.contains('dark') ? 'light' : 'dark';
            setStoredTheme(nextTheme);
            applyTheme(nextTheme);
        });
    }

    const setCriteria = (el, valid) => {
        if (!el) {
            return;
        }

        const icon = el.querySelector('.fa-circle, .fa-check-circle');
        const checkIcon = el.querySelector('.check-icon');
        if (valid) {
            if (icon) {
                icon.className = 'fas fa-check-circle text-green-500 text-[10px]';
            }
            if (checkIcon) {
                checkIcon.classList.remove('hidden');
            }
            el.classList.add('auth-inline-valid');
            return;
        }

        if (icon) {
            icon.className = 'fas fa-circle text-gray-300 dark:text-gray-500 text-[8px]';
        }
        if (checkIcon) {
            checkIcon.classList.add('hidden');
        }
        el.classList.remove('auth-inline-valid');
    };

    const updateStrength = (password) => {
        const lenOk = password.length >= 8;
        const upperOk = /[A-Z]/.test(password);
        const digitOk = /\d/.test(password);
        const specialOk = /[^A-Za-z0-9]/.test(password);

        setCriteria(critLength, lenOk);
        setCriteria(critUpper, upperOk);
        setCriteria(critDigit, digitOk);
        setCriteria(critSpecial, specialOk);

        const allOk = lenOk && upperOk && digitOk && specialOk;
        if (!strengthBadge) {
            return allOk;
        }

        if (allOk && password.length > 0) {
            strengthBadge.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Robuste';
            strengthBadge.className = 'text-xs font-medium px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-800 text-green-700 dark:text-green-200';
        } else if (password.length === 0) {
            strengthBadge.innerHTML = 'À renforcer';
            strengthBadge.className = 'text-xs font-medium px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300';
        } else {
            strengthBadge.innerHTML = 'Moyen';
            strengthBadge.className = 'text-xs font-medium px-2 py-0.5 rounded-full bg-yellow-100 dark:bg-yellow-800 text-yellow-700 dark:text-yellow-200';
        }

        return allOk;
    };

    const checkMatch = () => {
        if (!newPasswordInput || !confirmPasswordInput || !matchMsg) {
            return true;
        }

        const pwd = newPasswordInput.value;
        const confirm = confirmPasswordInput.value;
        if (confirm.length === 0) {
            matchMsg.className = 'text-xs mt-1 hidden';
            clearFieldError(confirmPasswordInput);
            return true;
        }

        if (pwd === confirm) {
            matchMsg.innerHTML = '<i class="fas fa-check-circle text-green-500 text-xs mr-1"></i> Mots de passe identiques';
            matchMsg.className = 'text-xs mt-1 auth-inline-valid';
            clearFieldError(confirmPasswordInput);
            return true;
        }

        matchMsg.innerHTML = '<i class="fas fa-exclamation-triangle text-red-500 text-xs mr-1"></i> Mots de passe différents';
        matchMsg.className = 'text-xs mt-1 auth-inline-error';
        markFieldError(confirmPasswordInput, 'Les mots de passe ne correspondent pas.');
        return false;
    };

    const toggleVisibility = (input, btn) => {
        if (!input || !btn) {
            return;
        }

        const type = input.type === 'password' ? 'text' : 'password';
        input.type = type;
        btn.textContent = type === 'password' ? 'Afficher' : 'Masquer';
    };

    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', () => {
            clearFieldError(newPasswordInput);
            hideNotice();
            updateStrength(newPasswordInput.value);
            checkMatch();
        });
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', () => {
            clearFieldError(confirmPasswordInput);
            hideNotice();
            checkMatch();
        });
    }

    if (toggleNew) {
        toggleNew.addEventListener('click', () => toggleVisibility(newPasswordInput, toggleNew));
    }

    if (toggleConfirm) {
        toggleConfirm.addEventListener('click', () => toggleVisibility(confirmPasswordInput, toggleConfirm));
    }

    if (resetForm) {
        resetForm.addEventListener('submit', (event) => {
            clearValidationState();

            const pwd = newPasswordInput ? newPasswordInput.value : '';
            const strengthOk = updateStrength(pwd);
            const matchOk = checkMatch();

            if (!strengthOk) {
                event.preventDefault();
                reportFieldIssue(
                    newPasswordInput,
                    'Mot de passe insuffisant',
                    'Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un caractère spécial.'
                );
                return;
            }

            if (!matchOk) {
                event.preventDefault();
                reportFieldIssue(
                    confirmPasswordInput,
                    'Confirmation invalide',
                    'Les mots de passe saisis ne correspondent pas.'
                );
            }
        });
    }

    updateStrength(newPasswordInput?.value || '');
    checkMatch();
})();
