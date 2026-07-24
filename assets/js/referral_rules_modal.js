(function () {
    const modal = document.querySelector('[data-referral-rules-modal]');

    if (!modal) {
        return;
    }

    const panel = modal.querySelector('[data-modal-panel]') || modal.firstElementChild;
    const openers = Array.from(document.querySelectorAll('[data-referral-rules-open]'));
    const closers = Array.from(document.querySelectorAll('[data-referral-rules-close]'));
    let lastFocusedElement = null;
    let closeTimer = null;

    const isOpen = () => !modal.classList.contains('hidden');

    const lockBodyScroll = () => {
        document.body.dataset.referralRulesOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
    };

    const unlockBodyScroll = () => {
        if (Object.prototype.hasOwnProperty.call(document.body.dataset, 'referralRulesOverflow')) {
            document.body.style.overflow = document.body.dataset.referralRulesOverflow || '';
            delete document.body.dataset.referralRulesOverflow;
        }
    };

    const focusCloseButton = () => {
        const closeButton = modal.querySelector('[data-referral-rules-close]');

        if (closeButton && typeof closeButton.focus === 'function') {
            closeButton.focus();
        }
    };

    const openModal = () => {
        if (!modal.classList.contains('hidden')) {
            return;
        }

        if (closeTimer) {
            window.clearTimeout(closeTimer);
            closeTimer = null;
        }

        lastFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        lockBodyScroll();

        window.requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');

            if (panel instanceof HTMLElement) {
                panel.classList.remove('opacity-0', 'translate-y-3', 'scale-95');
            }

            window.setTimeout(focusCloseButton, 0);
        });
    };

    const closeModal = () => {
        if (modal.classList.contains('hidden')) {
            return;
        }

        modal.setAttribute('aria-hidden', 'true');
        modal.classList.add('opacity-0');

        if (panel instanceof HTMLElement) {
            panel.classList.add('opacity-0', 'translate-y-3', 'scale-95');
        }

        if (closeTimer) {
            window.clearTimeout(closeTimer);
        }

        closeTimer = window.setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex', 'opacity-0');
            unlockBodyScroll();

            if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
                lastFocusedElement.focus();
            }
        }, 180);
    };

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    openers.forEach((button) => {
        button.addEventListener('click', openModal);
    });

    closers.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen()) {
            closeModal();
        }
    });
})();
