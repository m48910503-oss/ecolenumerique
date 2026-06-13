document.addEventListener('DOMContentLoaded', () => {
    const forms = Array.from(document.querySelectorAll('[data-auth-form]'));

    const getFieldContainer = (control) => control.closest('[data-auth-field]');
    const getFieldError = (control) => {
        const field = getFieldContainer(control);
        return field ? field.querySelector('[data-field-error]') : null;
    };

    const syncFieldState = (control, silent = false) => {
        if (!(control instanceof HTMLElement) || control.matches('[type="hidden"], [disabled]')) {
            return true;
        }

        const field = getFieldContainer(control);
        const error = getFieldError(control);
        const shouldValidate = typeof control.checkValidity === 'function';
        const hasValue = 'value' in control && typeof control.value === 'string' && control.value.trim() !== '';
        const valid = shouldValidate ? control.checkValidity() : true;
        const showError = !valid && !silent;

        if (field) {
            field.classList.toggle('is-invalid', !valid && !silent);
            field.classList.toggle('is-valid', valid && hasValue);
        }

        if (typeof control.setAttribute === 'function') {
            control.setAttribute('aria-invalid', showError ? 'true' : 'false');
        }

        if (error) {
            error.hidden = !showError;
            error.textContent = showError ? control.validationMessage : '';
        }

        return valid;
    };

    forms.forEach((form) => {
        form.setAttribute('novalidate', 'novalidate');
        const controls = Array.from(form.querySelectorAll('input, select, textarea')).filter((control) => {
            return !control.matches('[type="hidden"]');
        });

        controls.forEach((control) => {
            control.addEventListener('blur', () => syncFieldState(control));
            control.addEventListener('input', () => syncFieldState(control, true));
            control.addEventListener('change', () => syncFieldState(control));
            control.addEventListener('invalid', (event) => {
                event.preventDefault();
                syncFieldState(control);
            }, true);
        });

        form.addEventListener('submit', (event) => {
            let firstInvalid = null;

            controls.forEach((control) => {
                const valid = syncFieldState(control);
                if (!valid && firstInvalid === null) {
                    firstInvalid = control;
                }
            });

            if (firstInvalid) {
                event.preventDefault();
                if (typeof firstInvalid.focus === 'function') {
                    firstInvalid.focus();
                }
                if (typeof firstInvalid.reportValidity === 'function') {
                    firstInvalid.reportValidity();
                }
            }
        });
    });

    document.querySelectorAll('[data-password-policy]').forEach((block) => {
        const scope = block.closest('form') || document;
        const passwordInput = scope.querySelector('[data-password-source]');
        const confirmInput = scope.querySelector('[data-password-confirm]');
        const matchNode = scope.querySelector('[data-password-match]');
        const fillNode = block.querySelector('[data-password-strength-fill]');
        const labelNode = block.querySelector('[data-password-strength-label]');
        const scoreNode = block.querySelector('[data-password-strength-score]');
        const enforceStrong = block.getAttribute('data-password-enforce') === 'strong';

        if (!passwordInput) {
            return;
        }

        const rules = {
            length: (value) => value.length >= 8,
            upper: (value) => /[A-Z]/.test(value),
            digit: (value) => /\d/.test(value),
            special: (value) => /[^A-Za-z0-9]/.test(value),
        };

        const resolveRule = (ruleName) => {
            return {
                item: block.querySelector(`[data-password-rule="${ruleName}"]`),
                status: block.querySelector(`[data-password-rule-status="${ruleName}"]`),
            };
        };

        const ruleNodes = {
            length: resolveRule('length'),
            upper: resolveRule('upper'),
            digit: resolveRule('digit'),
            special: resolveRule('special'),
        };

        const updatePasswordState = () => {
            const passwordValue = passwordInput.value || '';
            const confirmValue = confirmInput ? (confirmInput.value || '') : '';
            const validity = {
                length: rules.length(passwordValue),
                upper: rules.upper(passwordValue),
                digit: rules.digit(passwordValue),
                special: rules.special(passwordValue),
            };

            const allStrong = validity.length && validity.upper && validity.digit && validity.special;
            const score = [
                validity.length,
                validity.upper,
                validity.digit,
                validity.special,
                passwordValue.length >= 12,
            ].filter(Boolean).length;

            Object.entries(ruleNodes).forEach(([ruleName, nodes]) => {
                const isValid = Boolean(validity[ruleName]);
                if (nodes.item) {
                    nodes.item.classList.toggle('is-valid', isValid);
                }
                if (nodes.status) {
                    nodes.status.textContent = isValid ? 'OK' : '\u00C0 faire';
                }
            });

            let label = '\u00C0 d\u00E9finir';
            let width = 10;
            let tone = 'weak';

            if (passwordValue.length > 0) {
                if (score <= 2) {
                    label = 'Faible';
                    width = 32;
                    tone = 'weak';
                } else if (score === 3) {
                    label = 'Correct';
                    width = 56;
                    tone = 'medium';
                } else if (score === 4) {
                    label = 'Solide';
                    width = 78;
                    tone = 'strong';
                } else {
                    label = 'Tr\u00E8s solide';
                    width = 100;
                    tone = 'strong';
                }
            }

            if (fillNode) {
                fillNode.style.width = `${width}%`;
                fillNode.setAttribute('data-strength-tone', tone);
            }
            if (labelNode) {
                labelNode.textContent = label;
            }
            if (scoreNode) {
                scoreNode.textContent = passwordValue.length > 0
                    ? (allStrong ? 'Pr\u00EAt pour la validation' : 'Compl\u00E9tez les crit\u00E8res requis')
                    : 'Ajoutez un mot de passe robuste.';
            }

            if (enforceStrong) {
                passwordInput.setCustomValidity(allStrong ? '' : 'Le mot de passe ne respecte pas les crit\u00E8res requis.');
            } else if (passwordValue.length > 0 && passwordValue.length < 8) {
                passwordInput.setCustomValidity('Le mot de passe doit contenir au moins 8 caract\u00E8res.');
            } else {
                passwordInput.setCustomValidity('');
            }

            if (confirmInput && matchNode) {
                if (confirmValue === '') {
                    matchNode.hidden = true;
                    matchNode.textContent = '';
                    matchNode.classList.remove('is-error', 'is-valid');
                    confirmInput.setCustomValidity('');
                } else if (confirmValue !== passwordValue) {
                    matchNode.hidden = false;
                    matchNode.textContent = 'Les mots de passe ne correspondent pas.';
                    matchNode.classList.add('is-error');
                    matchNode.classList.remove('is-valid');
                    confirmInput.setCustomValidity('Les mots de passe ne correspondent pas.');
                } else {
                    matchNode.hidden = false;
                    matchNode.textContent = 'Mot de passe confirm\u00E9.';
                    matchNode.classList.remove('is-error');
                    matchNode.classList.add('is-valid');
                    confirmInput.setCustomValidity('');
                }
            }
        };

        passwordInput.addEventListener('input', updatePasswordState);
        if (confirmInput) {
            confirmInput.addEventListener('input', updatePasswordState);
        }
        updatePasswordState();
    });

    document.querySelectorAll('[data-company-sso]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            const baseUrl = trigger.getAttribute('data-sso-base') || trigger.getAttribute('href') || '';
            const inputSelector = trigger.getAttribute('data-sso-source') || '#email';
            const emailInput = document.querySelector(inputSelector);
            const emailValue = emailInput && 'value' in emailInput ? String(emailInput.value || '').trim() : '';

            if (baseUrl === '') {
                return;
            }

            event.preventDefault();
            const separator = baseUrl.includes('?') ? '&' : '?';
            window.location.href = `${baseUrl}${separator}email=${encodeURIComponent(emailValue)}`;
        });
    });
});