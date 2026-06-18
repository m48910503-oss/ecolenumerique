(() => {
    const profileTabs = Array.from(document.querySelectorAll('.profile-tab'));
    const tabPanels = Array.from(document.querySelectorAll('.tab-panel'));

    if (profileTabs.length === 0 || tabPanels.length === 0) {
        return;
    }

    const activateTab = (tabName) => {
        profileTabs.forEach((tab) => {
            const isActive = tab.getAttribute('data-tab') === tabName;
            tab.classList.toggle('tab-active', isActive);
            tab.classList.toggle('border-primary-600', isActive);
            tab.classList.toggle('dark:border-primary-400', isActive);
            tab.classList.toggle('font-semibold', isActive);
            tab.classList.toggle('text-primary-600', isActive);
            tab.classList.toggle('dark:text-primary-400', isActive);
            tab.classList.toggle('font-medium', !isActive);
            tab.classList.toggle('text-gray-500', !isActive);
            tab.classList.toggle('dark:text-gray-400', !isActive);
            tab.classList.toggle('border-transparent', !isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            tab.tabIndex = isActive ? 0 : -1;
        });

        tabPanels.forEach((panel) => {
            panel.classList.add('hidden');
        });

        const panel = document.getElementById(`tab-${tabName}`);
        if (panel) {
            panel.classList.remove('hidden');
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        profileTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                activateTab(tab.getAttribute('data-tab'));
            });

            tab.addEventListener('keydown', (event) => {
                const currentIndex = profileTabs.indexOf(tab);
                if (currentIndex < 0) {
                    return;
                }

                let nextIndex = currentIndex;
                if (event.key === 'ArrowRight') {
                    nextIndex = (currentIndex + 1) % profileTabs.length;
                } else if (event.key === 'ArrowLeft') {
                    nextIndex = (currentIndex - 1 + profileTabs.length) % profileTabs.length;
                } else if (event.key === 'Home') {
                    nextIndex = 0;
                } else if (event.key === 'End') {
                    nextIndex = profileTabs.length - 1;
                } else {
                    return;
                }

                event.preventDefault();
                const nextTab = profileTabs[nextIndex];
                if (nextTab) {
                    nextTab.focus();
                    activateTab(nextTab.getAttribute('data-tab'));
                }
            });
        });

        const profileRoot = document.querySelector('[data-profile-initial-tab]');
        const requestedTab = profileRoot instanceof HTMLElement ? profileRoot.dataset.profileInitialTab : '';
        const defaultTab = profileTabs.find((tab) => tab.getAttribute('data-tab') === requestedTab)
            || profileTabs.find((tab) => tab.classList.contains('tab-active'))
            || profileTabs[0];
        if (defaultTab) {
            activateTab(defaultTab.getAttribute('data-tab'));
        }

        document.querySelectorAll('[data-delete-account-form]').forEach((form) => {
            const confirmationInput = form.querySelector('[data-delete-confirmation-input]');
            const exactMessage = 'Tapez exactement SUPPRIMER pour confirmer.';

            if (confirmationInput instanceof HTMLInputElement) {
                confirmationInput.addEventListener('input', () => {
                    confirmationInput.setCustomValidity(
                        confirmationInput.value === '' || confirmationInput.value === 'SUPPRIMER' ? '' : exactMessage
                    );
                });
            }

            form.addEventListener('submit', (event) => {
                if (confirmationInput instanceof HTMLInputElement && confirmationInput.value !== 'SUPPRIMER') {
                    confirmationInput.setCustomValidity(exactMessage);
                    confirmationInput.reportValidity();
                    event.preventDefault();
                    return;
                }

                const message = form.getAttribute('data-final-confirm-message') || 'Supprimer definitivement ce compte ?';
                if (!window.confirm(message)) {
                    event.preventDefault();
                }
            }, true);
        });
    });
})();
