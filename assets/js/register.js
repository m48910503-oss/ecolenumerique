document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('registerForm');
    if (!registerForm) {
        return;
    }

    const stepInput = document.getElementById('uiStep');
    const stepProfile = document.getElementById('stepProfile');
    const stepSecurity = document.getElementById('stepSecurity');
    const nextBtn = document.getElementById('nextToSecurity');
    const backBtn = document.getElementById('backToProfileStep');
    const step1Notice = document.getElementById('step1ClientNotice');
    const step2Notice = document.getElementById('step2ClientNotice');

    const personalRadio = document.querySelector('input[name="account_type"][value="personal"]');
    const companyRadio = document.querySelector('input[name="account_type"][value="company"]');
    const companyFieldDiv = document.getElementById('companyField');
    const companyInput = document.getElementById('companyName');
    const firstName = document.getElementById('firstName');
    const lastName = document.getElementById('lastName');
    const email = document.getElementById('email');

    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirmPassword');
    const togglePwd = document.getElementById('togglePwd');
    const toggleConfirm = document.getElementById('toggleConfirm');
    const acceptTerms = document.getElementById('acceptTerms');
    const submitBtn = document.getElementById('submitAccount');

    const critLength = document.getElementById('critLength');
    const critUpper = document.getElementById('critUpper');
    const critDigit = document.getElementById('critDigit');
    const critSpecial = document.getElementById('critSpecial');
    const strengthBadge = document.getElementById('strengthBadge');
    const matchMsg = document.getElementById('matchMessage');

    const darkToggle = document.getElementById('darkModeToggle');
    const darkIcon = document.getElementById('darkIcon');

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

    function applyThemeIcon() {
        if (!darkIcon) {
            return;
        }
        if (document.documentElement.classList.contains('dark')) {
            darkIcon.classList.remove('fa-moon');
            darkIcon.classList.add('fa-sun');
            return;
        }
        darkIcon.classList.remove('fa-sun');
        darkIcon.classList.add('fa-moon');
    }

    function setTheme(theme) {
        const nextTheme = theme === 'dark' ? 'dark' : 'light';
        document.documentElement.classList.toggle('dark', nextTheme === 'dark');
        document.documentElement.classList.toggle('light', nextTheme !== 'dark');
        document.documentElement.setAttribute('data-theme', nextTheme);
        document.documentElement.style.colorScheme = nextTheme;
        try {
            localStorage.setItem('theme', nextTheme);
        } catch (_error) {
            /* Storage can be unavailable in private contexts. */
        }
        applyThemeIcon();
    }

    function activeAccountType() {
        return companyRadio?.checked ? 'company' : 'personal';
    }

    function hideNotice(container) {
        if (!container) {
            return;
        }
        container.innerHTML = '';
        container.classList.add('hidden');
    }

    function showNotice(container, title, message, eyebrow = 'Attention') {
        if (!container) {
            return;
        }
        container.innerHTML = buildNoticeMarkup(eyebrow, title, message);
        container.classList.remove('hidden');
        container.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    function clearFieldError(field) {
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
    }

    function markFieldError(field, message = '') {
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
    }

    function focusField(field) {
        if (!(field instanceof HTMLElement)) {
            return;
        }
        field.focus({ preventScroll: true });
        field.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }

    function reportFieldIssue(container, field, title, message, eyebrow = 'Attention') {
        markFieldError(field, message);
        showNotice(container, title, message, eyebrow);
        focusField(field);
        return false;
    }

    function clearStep1State() {
        [firstName, lastName, email, companyInput].forEach(clearFieldError);
        hideNotice(step1Notice);
    }

    function clearStep2State() {
        [passwordInput, confirmInput, acceptTerms].forEach(clearFieldError);
        hideNotice(step2Notice);
    }

    function toggleCompanyField() {
        const isCompany = activeAccountType() === 'company';
        if (companyFieldDiv) {
            companyFieldDiv.classList.toggle('hidden', !isCompany);
        }
        if (companyInput) {
            companyInput.disabled = !isCompany;
            companyInput.required = isCompany;
            if (!isCompany) {
                companyInput.value = '';
                clearFieldError(companyInput);
            }
        }
    }

    function setSecurityDisabled(disabled) {
        stepSecurity?.querySelectorAll('input, button').forEach((control) => {
            control.disabled = disabled;
        });
    }

    function setStep(step) {
        const isSecurity = step === 2;
        stepProfile?.classList.toggle('hidden', isSecurity);
        stepSecurity?.classList.toggle('hidden', !isSecurity);
        if (stepInput) {
            stepInput.value = isSecurity ? '2' : '1';
        }
        if (isSecurity) {
            hideNotice(step1Notice);
        } else {
            hideNotice(step2Notice);
        }
        setSecurityDisabled(!isSecurity);
        toggleCompanyField();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validateStep1(options = {}) {
        const { revealStep = false } = options;
        const prenom = firstName?.value.trim() || '';
        const nom = lastName?.value.trim() || '';
        const mail = email?.value.trim() || '';
        const isCompany = activeAccountType() === 'company';
        const companyVal = companyInput?.value.trim() || '';

        clearStep1State();

        if (revealStep && stepInput?.value === '2') {
            setStep(1);
        }

        if (!prenom) {
            return reportFieldIssue(step1Notice, firstName, 'Informations incomplètes', 'Veuillez renseigner votre prénom.');
        }
        if (!nom) {
            return reportFieldIssue(step1Notice, lastName, 'Informations incomplètes', 'Veuillez renseigner votre nom.');
        }
        if (!mail) {
            return reportFieldIssue(step1Notice, email, 'Adresse e-mail requise', 'Veuillez renseigner votre adresse e-mail.');
        }
        if (email && typeof email.checkValidity === 'function' && !email.checkValidity()) {
            return reportFieldIssue(step1Notice, email, 'Adresse e-mail invalide', 'Veuillez saisir une adresse e-mail valide.');
        }
        if (isCompany && !companyVal) {
            return reportFieldIssue(step1Notice, companyInput, 'Entreprise requise', 'Veuillez indiquer le nom de votre entreprise.');
        }
        return true;
    }

    function togglePasswordVisibility(inputField, btn) {
        if (!inputField || !btn) {
            return;
        }
        const type = inputField.type === 'password' ? 'text' : 'password';
        inputField.type = type;
        btn.textContent = type === 'password' ? 'Afficher' : 'Masquer';
    }

    function setCriteria(el, valid) {
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
    }

    function updateStrength(password) {
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
    }

    function checkMatch() {
        if (!passwordInput || !confirmInput || !matchMsg) {
            return true;
        }
        const pwd = passwordInput.value;
        const confirm = confirmInput.value;
        if (confirm.length === 0) {
            matchMsg.className = 'text-xs mt-1 hidden';
            clearFieldError(confirmInput);
            return true;
        }
        if (pwd === confirm) {
            matchMsg.innerHTML = '<i class="fas fa-check-circle text-green-500 text-xs mr-1"></i> Mots de passe identiques';
            matchMsg.className = 'text-xs mt-1 auth-inline-valid';
            clearFieldError(confirmInput);
            return true;
        }
        matchMsg.innerHTML = '<i class="fas fa-exclamation-triangle text-red-500 text-xs mr-1"></i> Mots de passe différents';
        matchMsg.className = 'text-xs mt-1 auth-inline-error';
        markFieldError(confirmInput, 'Les mots de passe ne correspondent pas.');
        return false;
    }

    function finalValidation() {
        if (!validateStep1({ revealStep: true })) {
            return false;
        }

        clearStep2State();

        const pwd = passwordInput?.value || '';
        const confirm = confirmInput?.value || '';
        const strengthOk = updateStrength(pwd);
        const matchOk = pwd === confirm;

        if (!strengthOk) {
            return reportFieldIssue(
                step2Notice,
                passwordInput,
                'Mot de passe insuffisant',
                'Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un caractère spécial.'
            );
        }
        if (!matchOk) {
            return reportFieldIssue(
                step2Notice,
                confirmInput,
                'Confirmation invalide',
                'Les mots de passe saisis ne correspondent pas.'
            );
        }
        if (!acceptTerms?.checked) {
            return reportFieldIssue(
                step2Notice,
                acceptTerms,
                'Action requise',
                'Vous devez accepter les CGU et la politique de confidentialité pour créer le compte.'
            );
        }
        return true;
    }

    applyThemeIcon();
    darkToggle?.addEventListener('click', () => {
        setTheme(document.documentElement.classList.contains('dark') ? 'light' : 'dark');
    });

    personalRadio?.addEventListener('change', () => {
        toggleCompanyField();
        clearStep1State();
    });
    companyRadio?.addEventListener('change', () => {
        toggleCompanyField();
        clearStep1State();
    });

    [firstName, lastName, email, companyInput].forEach((field) => {
        field?.addEventListener('input', () => {
            clearFieldError(field);
            hideNotice(step1Notice);
        });
    });

    [passwordInput, confirmInput].forEach((field) => {
        field?.addEventListener('input', () => {
            clearFieldError(field);
            hideNotice(step2Notice);
        });
    });

    acceptTerms?.addEventListener('change', () => {
        clearFieldError(acceptTerms);
        hideNotice(step2Notice);
    });

    nextBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        if (validateStep1()) {
            setStep(2);
            passwordInput?.focus();
        }
    });

    backBtn?.addEventListener('click', () => {
        setStep(1);
    });

    togglePwd?.addEventListener('click', () => togglePasswordVisibility(passwordInput, togglePwd));
    toggleConfirm?.addEventListener('click', () => togglePasswordVisibility(confirmInput, toggleConfirm));

    passwordInput?.addEventListener('input', () => {
        updateStrength(passwordInput.value);
        checkMatch();
    });
    confirmInput?.addEventListener('input', checkMatch);

    registerForm.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || stepInput?.value === '2') {
            return;
        }
        const target = event.target;
        if (target instanceof HTMLElement && target.tagName === 'TEXTAREA') {
            return;
        }
        event.preventDefault();
        nextBtn?.click();
    });

    registerForm.addEventListener('submit', (event) => {
        if (stepInput) {
            stepInput.value = '2';
        }
        setSecurityDisabled(false);
        toggleCompanyField();
        if (!finalValidation()) {
            event.preventDefault();
            return;
        }
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Création du compte en cours...';
        }
    });

    toggleCompanyField();
    setSecurityDisabled(stepInput?.value !== '2');
    updateStrength(passwordInput?.value || '');
    checkMatch();
});
