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
        const panelCloseButton = panel.querySelector('[data-public-nav-close]:not([data-public-nav-backdrop])');
        const panelSheet = panel.querySelector('.public-mobile-panel__sheet');
        const toggleBarsIcon = toggle.querySelector('[data-public-nav-icon-bars]');
        const toggleCloseIcon = toggle.querySelector('[data-public-nav-icon-close]');
        const toggleLabel = toggle.querySelector('[data-public-nav-label]');
        let lastFocusedElement = null;

        const updateToggleVisualState = (open) => {
            toggle.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Fermer la navigation' : 'Ouvrir la navigation');

            if (toggleLabel instanceof HTMLElement) {
                toggleLabel.textContent = open ? 'Fermer la navigation' : 'Ouvrir la navigation';
            }

            if (toggleBarsIcon instanceof HTMLElement) {
                toggleBarsIcon.classList.toggle('hidden', open);
            }

            if (toggleCloseIcon instanceof HTMLElement) {
                toggleCloseIcon.classList.toggle('hidden', !open);
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

                const closeButton = panelCloseButton instanceof HTMLElement ? panelCloseButton : closeButtons[0];
                if (closeButton instanceof HTMLElement) {
                    closeButton.focus({ preventScroll: true });
                } else if (panelSheet instanceof HTMLElement) {
                    panelSheet.focus({ preventScroll: true });
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

        if (!fileInput || !fileName) {
            return;
        }

        const syncFileName = () => {
            const selected = fileInput.files && fileInput.files.length > 0 ? fileInput.files[0] : null;
            fileName.textContent = selected ? selected.name : 'Aucun fichier choisi';
        };

        fileInput.addEventListener('change', syncFileName);
        syncFileName();
    };

    const initTeacherInfoDrawer = () => {
        const drawer = document.querySelector('[data-teacher-info-drawer]');

        if (!(drawer instanceof HTMLElement)) {
            return;
        }

        const openButtons = Array.from(document.querySelectorAll('[data-teacher-info-open]'))
            .filter((button) => button instanceof HTMLButtonElement);
        const closeButtons = Array.from(document.querySelectorAll('[data-teacher-info-close]'));
        const panels = Array.from(drawer.querySelectorAll('[data-teacher-info-panel]'))
            .filter((panel) => panel instanceof HTMLElement);
        let lastTrigger = null;

        const showPanel = (key) => {
            panels.forEach((panel) => {
                const isCurrent = panel.getAttribute('data-teacher-info-panel') === key;
                panel.classList.toggle('hidden', !isCurrent);
            });
        };

        const closeDrawer = () => {
            drawer.classList.add('hidden');
            drawer.setAttribute('aria-hidden', 'true');
            document.body.style.removeProperty('overflow');

            if (lastTrigger instanceof HTMLElement) {
                lastTrigger.focus();
            }
        };

        const openDrawer = (key, trigger) => {
            showPanel(key);
            lastTrigger = trigger;
            drawer.classList.remove('hidden');
            drawer.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            const closeButton = drawer.querySelector('[data-teacher-info-close]');
            if (closeButton instanceof HTMLElement) {
                closeButton.focus();
            }
        };

        openButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const key = button.getAttribute('data-teacher-info-open') || '';
                if (key === '') {
                    return;
                }

                openDrawer(key, button);
            });
        });

        closeButtons.forEach((button) => {
            button.addEventListener('click', closeDrawer);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' || drawer.classList.contains('hidden')) {
                return;
            }

            closeDrawer();
        });
    };

    const onReady = () => {
        initPublicNav();
        initCvFileLabel();
        initTeacherInfoDrawer();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady, { once: true });
    } else {
        onReady();
    }
})();
