(function () {
    'use strict';

    const initPublicNav = () => {
        const toggle = document.querySelector('[data-public-nav-toggle]');
        const panel = document.querySelector('[data-public-nav-panel]');

        if (!(toggle instanceof HTMLButtonElement) || !(panel instanceof HTMLElement)) {
            return;
        }

        const mobileQuery = window.matchMedia('(max-width: 980px)');
        const closeButtons = Array.from(panel.querySelectorAll('[data-public-nav-close]')).filter((node) => node instanceof HTMLButtonElement);
        const panelSheet = panel.querySelector('.public-mobile-panel__sheet');
        const toggleLabel = toggle.querySelector('[data-public-nav-label]');
        let lastFocusedElement = null;

        const updateToggleVisualState = (open) => {
            toggle.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Fermer la navigation' : 'Ouvrir la navigation');

            if (toggleLabel instanceof HTMLElement) {
                toggleLabel.textContent = open ? 'Fermer la navigation' : 'Ouvrir la navigation';
            }
        };

        const setOpen = (open, options = {}) => {
            const shouldOpen = open && mobileQuery.matches;
            const restoreFocus = options.restoreFocus === true;

            if (shouldOpen) {
                lastFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            }

            panel.classList.toggle('is-open', shouldOpen);
            panel.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');
            document.body.classList.toggle('public-nav-open', shouldOpen);
            document.documentElement.classList.toggle('public-nav-open', shouldOpen);
            updateToggleVisualState(shouldOpen);

            if (!shouldOpen) {
                if (restoreFocus) {
                    const nextFocus = lastFocusedElement instanceof HTMLElement && document.contains(lastFocusedElement)
                        ? lastFocusedElement
                        : toggle;
                    if (nextFocus instanceof HTMLElement) {
                        nextFocus.focus({ preventScroll: true });
                    }
                }
                return;
            }

            window.requestAnimationFrame(() => {
                if (!panel.classList.contains('is-open')) {
                    return;
                }

                if (panelSheet instanceof HTMLElement) {
                    panelSheet.focus({ preventScroll: true });
                } else {
                    toggle.focus({ preventScroll: true });
                }
            });
        };

        toggle.addEventListener('click', () => {
            if (!mobileQuery.matches) {
                return;
            }

            setOpen(!panel.classList.contains('is-open'), { restoreFocus: true });
        });

        closeButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (!panel.classList.contains('is-open')) {
                    return;
                }

                setOpen(false, { restoreFocus: true });
            });
        });

        panel.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                if (!mobileQuery.matches) {
                    return;
                }

                setOpen(false);
            });
        });

        document.addEventListener('click', (event) => {
            if (!mobileQuery.matches || !panel.classList.contains('is-open')) {
                return;
            }

            const target = event.target;
            if (!(target instanceof Node)) {
                return;
            }

            if (panel.contains(target) || toggle.contains(target)) {
                return;
            }

            setOpen(false, { restoreFocus: true });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' || !panel.classList.contains('is-open')) {
                return;
            }

            event.preventDefault();
            setOpen(false, { restoreFocus: true });
        });

        if (typeof mobileQuery.addEventListener === 'function') {
            mobileQuery.addEventListener('change', () => {
                if (!mobileQuery.matches) {
                    setOpen(false);
                    return;
                }

                updateToggleVisualState(panel.classList.contains('is-open'));
            });
        } else if (typeof mobileQuery.addListener === 'function') {
            mobileQuery.addListener(() => {
                if (!mobileQuery.matches) {
                    setOpen(false);
                    return;
                }

                updateToggleVisualState(panel.classList.contains('is-open'));
            });
        }

        if (!mobileQuery.matches) {
            setOpen(false);
            return;
        }

        updateToggleVisualState(panel.classList.contains('is-open'));
        panel.setAttribute('aria-hidden', panel.classList.contains('is-open') ? 'false' : 'true');
    };

    const initCvFileLabel = () => {
        const fileInput = document.getElementById('cv_file');
        const fileName = document.getElementById('cv-file-name');
        const fileError = document.getElementById('cv-file-error');

        if (!fileInput || !fileName || !fileError) {
            return;
        }

        const allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'];
        const allowedPattern = /\.(pdf|docx?|jpe?g|png|webp)$/i;

        const showError = (message) => {
            if (message.trim() === '') {
                fileError.textContent = '';
                fileError.classList.add('hidden');
                return;
            }

            fileError.textContent = message;
            fileError.classList.remove('hidden');
        };

        const syncFileName = () => {
            const selected = fileInput.files && fileInput.files.length > 0 ? fileInput.files[0] : null;
            fileName.textContent = selected ? selected.name : 'Aucun fichier choisi';
            if (!selected) {
                showError('');
                return;
            }

            const extension = (selected.name.split('.').pop() || '').toLowerCase();
            if (!allowedExtensions.includes(extension) || !allowedPattern.test(selected.name)) {
                showError('Le CV doit être un fichier PDF, Word ou une image (JPG, PNG ou WebP).');
                return;
            }

            showError('');
        };

        fileInput.addEventListener('change', syncFileName);
        fileInput.addEventListener('invalid', () => {
            showError('Ajoutez votre CV avant d’envoyer votre candidature.');
        });
        syncFileName();
    };

    const initApplyWizard = () => {
        const form = document.querySelector('[data-apply-wizard-form]');

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const panels = Array.from(form.querySelectorAll('[data-apply-step-panel]')).filter((panel) => panel instanceof HTMLElement);
        const indicators = Array.from(document.querySelectorAll('[data-apply-step-indicator]')).filter((indicator) => indicator instanceof HTMLElement);
        const nextButtons = Array.from(form.querySelectorAll('[data-apply-next]')).filter((button) => button instanceof HTMLButtonElement);
        const prevButtons = Array.from(form.querySelectorAll('[data-apply-prev]')).filter((button) => button instanceof HTMLButtonElement);
        const progressBars = Array.from(document.querySelectorAll('[data-apply-progress-bar]')).filter((node) => node instanceof HTMLElement);
        const currentStepLabels = Array.from(document.querySelectorAll('[data-apply-step-current]')).filter((node) => node instanceof HTMLElement);
        const currentStepTitles = Array.from(document.querySelectorAll('[data-apply-step-title-current]')).filter((node) => node instanceof HTMLElement);
        const titleInput = document.getElementById('title');
        const experienceInputs = Array.from(form.querySelectorAll('input[name="teaching_experience"]')).filter((input) => input instanceof HTMLInputElement);
        const focusInputs = Array.from(form.querySelectorAll('[data-apply-focus-option]')).filter((input) => input instanceof HTMLInputElement);
        const summaryTitle = document.querySelector('[data-apply-summary-title]');
        const summaryExperience = document.querySelector('[data-apply-summary-experience]');
        const stepTitles = {
            1: 'Présentation du parcours',
            2: 'Choix de la spécialité',
            3: 'Préparation du dossier'
        };
        let autofilledTitle = '';
        let currentStep = Number.parseInt(form.dataset.initialStep || '1', 10);

        if (!Number.isFinite(currentStep) || currentStep < 1 || currentStep > panels.length) {
            currentStep = 1;
        }

        const clearToggleableSelection = (label) => {
            if (!(label instanceof HTMLLabelElement)) {
                return;
            }

            const input = label.querySelector('input[type="radio"]');
            if (!(input instanceof HTMLInputElement)) {
                return;
            }

            const suggestion = (input.dataset.titleSuggestion || '').trim();
            const titleValue = titleInput instanceof HTMLInputElement ? titleInput.value.trim() : '';
            const shouldResetTitle = titleInput instanceof HTMLInputElement
                && suggestion !== ''
                && (titleInput.dataset.autofilled === '1' || titleValue === suggestion || titleValue === autofilledTitle);

            input.checked = false;
            if (shouldResetTitle) {
                titleInput.value = '';
                titleInput.dataset.autofilled = '0';
                autofilledTitle = '';
            }

            updateSummary();
        };

        const getPanel = (step) => panels.find((panel) => Number.parseInt(panel.dataset.step || '0', 10) === step) || null;

        const setStepError = (step, message) => {
            const panel = getPanel(step);

            if (!(panel instanceof HTMLElement)) {
                return;
            }

            const errorBox = panel.querySelector('[data-apply-step-error]');
            if (!(errorBox instanceof HTMLElement)) {
                return;
            }

            if (message.trim() === '') {
                errorBox.textContent = '';
                errorBox.classList.add('hidden');
                return;
            }

            errorBox.textContent = message;
            errorBox.classList.remove('hidden');
        };

        const updateSummary = () => {
            const checkedExperience = experienceInputs.find((input) => input.checked);

            if (summaryExperience instanceof HTMLElement) {
                summaryExperience.textContent = checkedExperience
                    ? (checkedExperience.closest('label')?.querySelector('.text-xl, .text-lg')?.textContent || checkedExperience.value)
                    : 'À définir';
            }

            if (summaryTitle instanceof HTMLElement) {
                const value = titleInput instanceof HTMLInputElement ? titleInput.value.trim() : '';
                summaryTitle.textContent = value !== '' ? value : 'À définir';
            }
        };

        const applyFocusSuggestion = (input) => {
            if (!(titleInput instanceof HTMLInputElement) || !(input instanceof HTMLInputElement)) {
                return;
            }

            const suggestion = (input.dataset.titleSuggestion || '').trim();
            if (suggestion === '') {
                return;
            }

            const canAutofill = titleInput.value.trim() === ''
                || titleInput.dataset.autofilled === '1'
                || titleInput.value.trim() === autofilledTitle;

            if (!canAutofill) {
                return;
            }

            titleInput.value = suggestion;
            titleInput.dataset.autofilled = '1';
            autofilledTitle = suggestion;
            updateSummary();
        };

        const setStep = (step, options = {}) => {
            const targetStep = Math.min(Math.max(step, 1), panels.length);
            const focusPanel = options.focusPanel !== false;

            currentStep = targetStep;

            panels.forEach((panel) => {
                const panelStep = Number.parseInt(panel.dataset.step || '0', 10);
                panel.hidden = panelStep !== currentStep;
            });

            indicators.forEach((indicator) => {
                const stepNumber = Number.parseInt(indicator.dataset.step || '0', 10);
                let state = 'upcoming';

                if (stepNumber < currentStep) {
                    state = 'complete';
                } else if (stepNumber === currentStep) {
                    state = 'active';
                }

                indicator.dataset.state = state;
            });

            progressBars.forEach((progressBar) => {
                progressBar.style.width = `${(currentStep / panels.length) * 100}%`;
            });

            currentStepLabels.forEach((currentStepLabel) => {
                currentStepLabel.textContent = String(currentStep);
            });

            currentStepTitles.forEach((currentStepTitle) => {
                currentStepTitle.textContent = stepTitles[currentStep] || '';
            });

            updateSummary();

            if (!focusPanel) {
                return;
            }

            const activePanel = getPanel(currentStep);
            if (!(activePanel instanceof HTMLElement)) {
                return;
            }

            window.requestAnimationFrame(() => {
                const target = activePanel.querySelector('input, textarea, button');
                if (target instanceof HTMLElement) {
                    target.focus({ preventScroll: true });
                }
            });
        };

        const validateStep = (step) => {
            setStepError(step, '');

            if (step === 1) {
                const hasChoice = experienceInputs.some((input) => input.checked);
                if (!hasChoice) {
                    setStepError(step, 'Sélectionnez votre niveau d’expérience pour continuer.');
                    return false;
                }
            }

            if (step === 2) {
                if (!(titleInput instanceof HTMLInputElement) || titleInput.value.trim() === '') {
                    setStepError(step, 'Précisez la compétence principale que vous souhaitez enseigner.');
                    if (titleInput instanceof HTMLInputElement) {
                        titleInput.focus({ preventScroll: true });
                    }
                    return false;
                }
            }

            return true;
        };

        nextButtons.forEach((button) => {
            button.addEventListener('click', () => {
                if (!validateStep(currentStep)) {
                    return;
                }

                setStep(currentStep + 1);
            });
        });

        prevButtons.forEach((button) => {
            button.addEventListener('click', () => {
                setStep(currentStep - 1);
            });
        });

        focusInputs.forEach((input) => {
            input.addEventListener('change', () => {
                if (input.checked) {
                    applyFocusSuggestion(input);
                }
            });
        });

        let pendingToggleLabel = null;
        let pendingToggleWasChecked = false;

        form.addEventListener('pointerdown', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                pendingToggleLabel = null;
                pendingToggleWasChecked = false;
                return;
            }

            const label = target.closest('[data-apply-toggleable-option="step-2"]');
            if (!(label instanceof HTMLLabelElement)) {
                pendingToggleLabel = null;
                pendingToggleWasChecked = false;
                return;
            }

            const input = label.querySelector('input[type="radio"]');
            pendingToggleLabel = label;
            pendingToggleWasChecked = input instanceof HTMLInputElement ? input.checked : false;
        });

        form.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                pendingToggleLabel = null;
                pendingToggleWasChecked = false;
                return;
            }

            const label = target.closest('[data-apply-toggleable-option="step-2"]');
            if (!(label instanceof HTMLLabelElement)) {
                pendingToggleLabel = null;
                pendingToggleWasChecked = false;
                return;
            }

            if (label !== pendingToggleLabel || !pendingToggleWasChecked) {
                pendingToggleLabel = null;
                pendingToggleWasChecked = false;
                return;
            }

            const input = label.querySelector('input[type="radio"]');
            if (!(input instanceof HTMLInputElement)) {
                pendingToggleLabel = null;
                pendingToggleWasChecked = false;
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            clearToggleableSelection(label);
            pendingToggleLabel = null;
            pendingToggleWasChecked = false;
        });

        experienceInputs.forEach((input) => {
            input.addEventListener('change', updateSummary);
        });

        if (titleInput instanceof HTMLInputElement) {
            titleInput.addEventListener('input', () => {
                if (titleInput.value.trim() !== autofilledTitle) {
                    titleInput.dataset.autofilled = '0';
                }
                updateSummary();
            });
        }

        form.addEventListener('submit', (event) => {
            const fileInput = document.getElementById('cv_file');
            const fileError = document.getElementById('cv-file-error');
            if (fileInput instanceof HTMLInputElement && fileError instanceof HTMLElement && fileInput.files && fileInput.files.length > 0) {
                const selected = fileInput.files[0];
                const valid = /\.(pdf|docx?|jpe?g|png|webp)$/i.test(selected.name);
                if (!valid) {
                    fileError.textContent = 'Le CV doit être un fichier PDF, Word ou une image (JPG, PNG ou WebP).';
                    fileError.classList.remove('hidden');
                    fileInput.focus({ preventScroll: true });
                    event.preventDefault();
                    return;
                }
            }
            setStep(3, { focusPanel: false });
        });

        const preselectedFocus = focusInputs.find((input) => input.checked);
        if (preselectedFocus instanceof HTMLInputElement) {
            applyFocusSuggestion(preselectedFocus);
        }

        setStep(currentStep, { focusPanel: false });
        updateSummary();
    };

    const initApplicationNoticeModal = () => {
        const modal = document.querySelector('[data-application-notice-modal]');

        if (!(modal instanceof HTMLElement)) {
            return;
        }

        const noticeState = modal.dataset.applicationNoticeState || 'preflight';
        const autoOpen = modal.dataset.applicationNoticeAutoOpen === '1';
        const sessionKey = `teacherApplicationNoticeSeen:${noticeState}`;
        const triggers = Array.from(document.querySelectorAll('[data-application-notice-trigger]')).filter((node) => node instanceof HTMLElement);
        const form = document.querySelector('[data-apply-wizard-form]');
        const closeButtons = Array.from(modal.querySelectorAll(
            '[data-application-notice-close], [data-application-notice-confirm]'
        )).filter((button) => button instanceof HTMLElement);
        const confirmButton = modal.querySelector('[data-application-notice-confirm]');
        let previousBodyOverflow = '';
        let previouslyFocusedElement = null;
        let openRequested = false;
        const shouldPersistNoticeSeen = noticeState !== 'success';

        const rememberNotice = () => {
            if (!shouldPersistNoticeSeen) {
                return;
            }

            try {
                window.sessionStorage.setItem(sessionKey, '1');
            } catch (error) {
                // The modal still works when browser storage is unavailable.
            }
        };

        const wasNoticeSeen = () => {
            try {
                return window.sessionStorage.getItem(sessionKey) === '1';
            } catch (error) {
                return false;
            }
        };

        const closeModal = () => {
            if (modal.getAttribute('aria-hidden') === 'true') {
                return;
            }

            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
            modal.setAttribute('inert', '');
            document.body.style.overflow = previousBodyOverflow;
            rememberNotice();

            if (previouslyFocusedElement instanceof HTMLElement && document.contains(previouslyFocusedElement)) {
                previouslyFocusedElement.focus({ preventScroll: true });
            }
        };

        const openModal = (options = {}) => {
            const force = options.force === true;

            if (!force && (wasNoticeSeen() || openRequested)) {
                return;
            }

            openRequested = true;
            previouslyFocusedElement = document.activeElement instanceof HTMLElement
                ? document.activeElement
                : null;
            previousBodyOverflow = document.body.style.overflow;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
            modal.removeAttribute('inert');
            document.body.style.overflow = 'hidden';

            window.requestAnimationFrame(() => {
                if (confirmButton instanceof HTMLElement) {
                    confirmButton.focus({ preventScroll: true });
                }
            });
        };

        closeButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' || modal.getAttribute('aria-hidden') === 'true') {
                return;
            }

            event.preventDefault();
            closeModal();
        });

        triggers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                if (wasNoticeSeen()) {
                    return;
                }

                window.setTimeout(openModal, 120);
            });
        });

        if (form instanceof HTMLFormElement) {
            form.addEventListener('focusin', () => {
                if (wasNoticeSeen() || openRequested) {
                    return;
                }

                openModal();
            }, { once: true });
        }

        if (autoOpen) {
            window.setTimeout(() => openModal({ force: noticeState === 'success' }), 120);
        }

        window.addEventListener('pageshow', (event) => {
            if (event.persisted && noticeState === 'success') {
                closeModal();
            }
        });
    };

    const onReady = () => {
        initPublicNav();
        initCvFileLabel();
        initApplyWizard();
        initApplicationNoticeModal();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady, { once: true });
    } else {
        onReady();
    }
})();
