function updatePasswordToggleLabel(button, isVisible) {
    if (!(button instanceof HTMLElement)) {
        return;
    }

    const showLabel = button.getAttribute('data-label-show') || 'Afficher';
    const hideLabel = button.getAttribute('data-label-hide') || 'Masquer';
    const label = isVisible ? hideLabel : showLabel;
    const labelTarget = button.querySelector('[data-password-toggle-label]');
    const iconTarget = button.querySelector('[data-password-toggle-icon]');

    button.setAttribute('aria-pressed', isVisible ? 'true' : 'false');
    button.setAttribute('aria-label', label);
    button.setAttribute('title', label);

    if (labelTarget) {
        labelTarget.textContent = label;
    } else if (iconTarget) {
        button.setAttribute('data-password-toggle-state', isVisible ? 'visible' : 'hidden');
    } else {
        button.textContent = label;
    }

    if (iconTarget) {
        iconTarget.classList.toggle('fa-eye', !isVisible);
        iconTarget.classList.toggle('fa-eye-slash', isVisible);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const selector = button.getAttribute('data-password-toggle');
        if (!selector) {
            return;
        }

        const field = document.querySelector(selector);
        if (!(field instanceof HTMLInputElement)) {
            return;
        }

        if (field.id) {
            button.setAttribute('aria-controls', field.id);
        }
        updatePasswordToggleLabel(button, field.type === 'text');

        button.addEventListener('click', (event) => {
            event.preventDefault();
            const isVisible = field.type === 'password';
            field.type = isVisible ? 'text' : 'password';
            updatePasswordToggleLabel(button, isVisible);
        });
    });

    const animated = document.querySelectorAll('[data-animate]');
    if (animated.length && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                const element = entry.target;
                const animation = element.dataset.animate || 'slide-up';
                const delay = Number(element.dataset.delay || 0);
                element.style.animationDelay = `${delay}ms`;
                element.classList.add(`animate-${animation}`);
                element.classList.remove('opacity-0');
                observer.unobserve(element);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        animated.forEach((element) => {
            element.classList.add('opacity-0');
            observer.observe(element);
        });
    }

    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', () => {
            const submitButton = form.querySelector('[type="submit"]');
            if (!(submitButton instanceof HTMLButtonElement) || submitButton.dataset.noLoader) {
                return;
            }
            if (submitButton.disabled) {
                return;
            }

            const loadingLabel = submitButton.getAttribute('data-loading-label') || 'Traitement en cours...';
            submitButton.disabled = true;
            submitButton.setAttribute('aria-disabled', 'true');
            submitButton.setAttribute('aria-busy', 'true');
            submitButton.dataset.originalText = submitButton.innerHTML;
            submitButton.innerHTML = `
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span>${loadingLabel}</span>`;
        });
    });
});
