document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('[data-referral-copy]');
    const shareButtons = document.querySelectorAll('[data-referral-share]');
    if (!buttons.length && !shareButtons.length) {
        return;
    }

    const currentView = String(document.body?.getAttribute('data-view') || '').trim();
    const usesUnifiedToast = /^(teacher|student)\//.test(currentView);
    let localToastContainer = null;

    const getToastApi = () => {
        if (typeof window.appShowToast === 'function') {
            return window.appShowToast.bind(window);
        }

        if (typeof window.showToast === 'function') {
            return window.showToast.bind(window);
        }

        return null;
    };

    const ensureFallbackToastStack = () => {
        let stack = document.querySelector('[data-referral-toast-stack]');
        if (stack instanceof HTMLElement) {
            return stack;
        }

        stack = document.createElement('div');
        stack.dataset.referralToastStack = '1';
        stack.setAttribute('aria-live', 'polite');
        stack.style.position = 'fixed';
        stack.style.right = '1rem';
        stack.style.bottom = '5.5rem';
        stack.style.zIndex = '90';
        stack.style.display = 'flex';
        stack.style.flexDirection = 'column';
        stack.style.alignItems = 'flex-end';
        stack.style.gap = '0.75rem';
        stack.style.width = 'min(24rem, calc(100vw - 2rem))';
        stack.style.pointerEvents = 'none';
        document.body.appendChild(stack);
        return stack;
    };

    const showFallbackToast = (message, type = 'info') => {
        const stack = ensureFallbackToastStack();
        const toast = document.createElement('div');
        const palette = {
            success: {
                role: 'status',
                border: 'rgba(74, 222, 128, 0.28)',
                background: 'linear-gradient(135deg, rgba(240, 253, 244, 0.98), rgba(220, 252, 231, 0.96))',
                color: '#052e16',
                iconBg: 'rgba(34, 197, 94, 0.16)',
                iconColor: '#16a34a',
                icon: 'fa-check-circle',
            },
            error: {
                role: 'alert',
                border: 'rgba(248, 113, 113, 0.34)',
                background: 'linear-gradient(135deg, rgba(255, 241, 242, 0.98), rgba(254, 226, 226, 0.95))',
                color: '#7f1d1d',
                iconBg: 'rgba(239, 68, 68, 0.14)',
                iconColor: '#b91c1c',
                icon: 'fa-circle-exclamation',
            },
            info: {
                role: 'status',
                border: 'rgba(96, 165, 250, 0.34)',
                background: 'linear-gradient(135deg, rgba(239, 246, 255, 0.98), rgba(219, 234, 254, 0.95))',
                color: '#1e3a8a',
                iconBg: 'rgba(59, 130, 246, 0.14)',
                iconColor: '#2563eb',
                icon: 'fa-circle-info',
            },
        };

        const theme = palette[type] || palette.info;
        toast.setAttribute('role', theme.role);
        toast.className = 'student-referral-toast pointer-events-auto w-full';
        toast.style.pointerEvents = 'auto';
        toast.style.width = '100%';
        toast.style.borderRadius = '18px';
        toast.style.border = `1px solid ${theme.border}`;
        toast.style.background = theme.background;
        toast.style.color = theme.color;
        toast.style.boxShadow = '0 18px 40px rgba(15, 23, 42, 0.16)';
        toast.style.padding = '14px 16px';
        toast.style.backdropFilter = 'blur(12px) saturate(1.08)';
        toast.style.WebkitBackdropFilter = 'blur(12px) saturate(1.08)';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px) scale(0.985)';
        toast.style.transition = 'opacity 180ms ease, transform 180ms ease';
        toast.innerHTML = `
            <div class="flex items-start gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full" style="background:${theme.iconBg};color:${theme.iconColor}" aria-hidden="true">
                    <i class="fas ${theme.icon} text-sm"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold leading-5">${message}</p>
                </div>
            </div>
        `;

        stack.appendChild(toast);
        window.requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0) scale(1)';
        });
        window.setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px) scale(0.985)';
            window.setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 220);
        }, 2200);
    };

    const ensureNotificationToastContainer = () => {
        if (localToastContainer && document.body.contains(localToastContainer)) {
            return localToastContainer;
        }

        let container = document.querySelector('[data-notification-toast-container]');
        if (container instanceof HTMLElement) {
            localToastContainer = container;
            return container;
        }

        container = document.createElement('div');
        container.setAttribute('data-notification-toast-container', '1');
        container.style.position = 'fixed';
        container.style.top = 'calc(var(--teacher-topbar-height, 4.5rem) + 0.75rem)';
        container.style.left = '50%';
        container.style.transform = 'translateX(-50%)';
        container.style.zIndex = '9999';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '0.5rem';
        container.style.width = 'min(42rem, calc(100vw - 1rem))';
        container.style.pointerEvents = 'none';

        if (window.matchMedia && window.matchMedia('(max-width: 640px)').matches) {
            container.style.top = '4.75rem';
            container.style.width = 'calc(100vw - 0.75rem)';
        }

        document.body.appendChild(container);
        localToastContainer = container;
        return container;
    };

    const showNotificationToast = (message, type = 'info') => {
        const text = String(message || '').trim();
        if (!text || !document.body) {
            return false;
        }

        const palette = {
            success: {
                title: 'Succes',
                icon: 'fas fa-circle-check',
                variant: 'success',
            },
            error: {
                title: 'Erreur',
                icon: 'fas fa-circle-exclamation',
                variant: 'error',
            },
            warning: {
                title: 'Attention',
                icon: 'fas fa-triangle-exclamation',
                variant: 'warning',
            },
            info: {
                title: 'Info',
                icon: 'fas fa-circle-info',
                variant: 'info',
            },
        };

        const tone = String(type || 'info').toLowerCase();
        const theme = palette[tone] || palette.info;
        const existing = document.querySelector('[data-notification-local-toast="1"]');
        if (existing && existing.parentNode) {
            existing.parentNode.removeChild(existing);
        }

        const toast = document.createElement('div');
        toast.dataset.notificationLocalToast = '1';
        toast.dataset.notificationToastView = currentView || 'unknown';
        toast.className = `teacher-withdrawal-toast teacher-withdrawal-toast--${theme.variant}`;
        toast.setAttribute('role', tone === 'error' ? 'alert' : 'status');
        toast.setAttribute('aria-live', tone === 'error' ? 'assertive' : 'polite');

        const copyWrap = document.createElement('div');
        copyWrap.className = 'teacher-withdrawal-toast__copy';

        const icon = document.createElement('span');
        icon.className = 'teacher-withdrawal-toast__icon';
        icon.setAttribute('aria-hidden', 'true');

        const iconEl = document.createElement('i');
        iconEl.className = theme.icon;
        icon.appendChild(iconEl);

        const body = document.createElement('div');
        body.className = 'min-w-0';

        const title = document.createElement('p');
        title.className = 'teacher-withdrawal-toast__title';
        title.textContent = theme.title;

        const messageEl = document.createElement('p');
        messageEl.className = 'teacher-withdrawal-toast__message';
        messageEl.textContent = text;

        body.appendChild(title);
        body.appendChild(messageEl);
        copyWrap.appendChild(icon);
        copyWrap.appendChild(body);

        const barWrap = document.createElement('span');
        barWrap.className = 'teacher-withdrawal-toast__bar';
        barWrap.setAttribute('aria-hidden', 'true');

        toast.appendChild(copyWrap);
        toast.appendChild(barWrap);

        const container = ensureNotificationToastContainer();
        container.appendChild(toast);

        window.setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 3600);

        return true;
    };

    const notify = (message, type = 'info') => {
        if (currentView === 'teacher/referrals' && showNotificationToast(message, type)) {
            return;
        }

        const toastApi = getToastApi();
        if (toastApi) {
            toastApi(message, type);
            return;
        }

        showFallbackToast(message, type);
    };

    const copyText = async (text) => {
        if (!text) {
            return false;
        }

        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (error) {
                return false;
            }
        }

        const helper = document.createElement('textarea');
        helper.value = text;
        helper.setAttribute('readonly', '');
        helper.style.position = 'absolute';
        helper.style.left = '-9999px';
        document.body.appendChild(helper);
        helper.select();

        let ok = false;
        try {
            ok = document.execCommand('copy');
        } catch (error) {
            ok = false;
        }

        document.body.removeChild(helper);
        return ok;
    };

    const showStatus = (button, ok, successMessage, errorMessage) => {
        if (usesUnifiedToast || currentView === 'student/meetings' || currentView === 'student/referrals') {
            return;
        }

        const wrap = button.closest('[data-referral-copy-wrap]');
        const statusEl = wrap ? wrap.querySelector('[data-referral-copy-status]') : null;
        if (!statusEl) {
            return;
        }
        statusEl.textContent = ok ? successMessage : errorMessage;
        statusEl.classList.remove('hidden');
        statusEl.classList.remove('text-emerald-600', 'text-red-600');
        statusEl.classList.add(ok ? 'text-emerald-600' : 'text-red-600');

        if (statusEl._referralHideTimer) {
            window.clearTimeout(statusEl._referralHideTimer);
        }

        statusEl._referralHideTimer = window.setTimeout(() => {
            statusEl.classList.add('hidden');
        }, 2200);
    };

    buttons.forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();

            const value = button.getAttribute('data-referral-value') || button.textContent.trim();
            if (!value || value === '---') {
                return;
            }

            const successMessage = button.getAttribute('data-referral-copy-success') || 'Copie effectuée.';
            const errorMessage = button.getAttribute('data-referral-copy-error') || 'Copie impossible. Utilisez Ctrl+C.';
            const ok = await copyText(value);

            showStatus(button, ok, successMessage, errorMessage);
            notify(ok ? successMessage : errorMessage, ok ? 'success' : 'error');
        });
    });

    shareButtons.forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();

            const value = button.getAttribute('data-referral-value') || '';
            if (!value || value === '---') {
                return;
            }

            const shareSuccessMessage = button.getAttribute('data-referral-copy-success') || 'Lien partagé.';
            const copySuccessMessage = button.getAttribute('data-referral-copy-fallback-success') || 'Lien copié.';
            const errorMessage = button.getAttribute('data-referral-copy-error') || 'Partage impossible.';

            let ok = false;
            let shared = false;

            if (navigator.share && window.isSecureContext) {
                try {
                    await navigator.share({
                        title: 'Mon lien de parrainage',
                        text: value,
                        url: value,
                    });
                    ok = true;
                    shared = true;
                } catch (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }
                }
            }

            if (!ok) {
                ok = await copyText(value);
            }

            const successMessage = shared ? shareSuccessMessage : copySuccessMessage;
            showStatus(button, ok, successMessage, errorMessage);
            notify(ok ? successMessage : errorMessage, ok ? 'success' : 'error');
        });
    });
});
