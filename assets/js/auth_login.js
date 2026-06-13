document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    if (!form) {
        return;
    }

    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const emailShell = document.getElementById('emailShell');
    const passwordShell = document.getElementById('passwordShell');
    const emailError = document.getElementById('emailError');
    const passwordError = document.getElementById('passwordError');
    const submitButton = form.querySelector('button[type="submit"]');

    const setFieldError = (field, shell, errorNode, message) => {
        if (field instanceof HTMLElement) {
            field.setAttribute('aria-invalid', 'true');
            field.classList.add('auth-field-error');
        }
        if (shell instanceof HTMLElement) {
            shell.classList.add('is-invalid');
        }
        if (errorNode instanceof HTMLElement) {
            errorNode.textContent = message;
        }
    };

    const clearFieldError = (field, shell, errorNode) => {
        if (field instanceof HTMLElement) {
            field.removeAttribute('aria-invalid');
            field.classList.remove('auth-field-error');
        }
        if (shell instanceof HTMLElement) {
            shell.classList.remove('is-invalid');
        }
        if (errorNode instanceof HTMLElement) {
            errorNode.textContent = '';
        }
    };

    const focusField = (field) => {
        if (!(field instanceof HTMLElement)) {
            return;
        }
        field.focus({ preventScroll: true });
        field.scrollIntoView({ block: 'center', behavior: 'smooth' });
    };

    const validate = () => {
        let firstInvalidField = null;

        clearFieldError(email, emailShell, emailError);
        clearFieldError(password, passwordShell, passwordError);

        const emailValue = email instanceof HTMLInputElement ? email.value.trim() : '';
        const passwordValue = password instanceof HTMLInputElement ? password.value : '';

        if (emailValue === '') {
            setFieldError(email, emailShell, emailError, 'Veuillez renseigner ce champ.');
            firstInvalidField = firstInvalidField || email;
        } else if (email instanceof HTMLInputElement && typeof email.checkValidity === 'function' && !email.checkValidity()) {
            setFieldError(email, emailShell, emailError, 'Veuillez renseigner une adresse e-mail valide.');
            firstInvalidField = firstInvalidField || email;
        }

        if (passwordValue.trim() === '') {
            setFieldError(password, passwordShell, passwordError, 'Veuillez renseigner ce champ.');
            firstInvalidField = firstInvalidField || password;
        }

        if (firstInvalidField) {
            focusField(firstInvalidField);
            return false;
        }

        return true;
    };

    email?.addEventListener('input', () => clearFieldError(email, emailShell, emailError));
    password?.addEventListener('input', () => clearFieldError(password, passwordShell, passwordError));

    form.addEventListener('submit', (event) => {
        if (!validate()) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }

        if (submitButton instanceof HTMLButtonElement && !submitButton.disabled) {
            submitButton.disabled = true;
            submitButton.setAttribute('aria-disabled', 'true');
            submitButton.setAttribute('aria-busy', 'true');
            submitButton.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i><span>Connexion en cours...</span>';
        }
    }, true);
});
