(() => {
    'use strict';

    const form = document.getElementById('checkout-form');
    if (!form) {
        return;
    }

    const feedback = form.querySelector('[data-checkout-feedback]');
    const payButton = form.querySelector('button[name="checkout_action"][value="pay"]');
    const refreshButton = form.querySelector('button[name="checkout_action"][value="refresh"]');
    const payLabel = payButton ? payButton.querySelector('[data-checkout-pay-label]') : null;
    const defaultPayLabel = payButton ? (payLabel ? payLabel.textContent.trim() : payButton.textContent.trim()) : '';

    const escapeHtml = (value) => String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const renderFeedback = (errors = [], warnings = [], successes = []) => {
        if (!feedback) {
            return;
        }

        const messages = [];
        errors.forEach((message) => {
            messages.push(`<div class="checkout-status-card checkout-status-card--danger"><p>${escapeHtml(message)}</p></div>`);
        });
        warnings.forEach((message) => {
            messages.push(`<div class="checkout-status-card"><p>${escapeHtml(message)}</p></div>`);
        });
        successes.forEach((message) => {
            messages.push(`<div class="checkout-status-card checkout-status-card--success"><p>${escapeHtml(message)}</p></div>`);
        });

        feedback.innerHTML = messages.join('');
        feedback.hidden = messages.length === 0;
    };

    const setBusy = (busy) => {
        if (!payButton) {
            return;
        }
        payButton.disabled = busy;
        payButton.classList.toggle('is-disabled', busy);
        payButton.classList.toggle('opacity-75', busy);
        payButton.classList.toggle('cursor-wait', busy);
        payButton.setAttribute('aria-busy', busy ? 'true' : 'false');
        if (payLabel) {
            payLabel.textContent = busy ? 'Initialisation du paiement...' : defaultPayLabel;
        } else {
            payButton.textContent = busy ? 'Initialisation du paiement...' : defaultPayLabel;
        }
    };

    if (refreshButton) {
        let refreshTimer = null;
        const scheduleRefresh = () => {
            window.clearTimeout(refreshTimer);
            refreshTimer = window.setTimeout(() => {
                form.requestSubmit(refreshButton);
            }, 350);
        };

        form.querySelectorAll('input[name="enterprise_mode"], input[name^="seat_qty["], input[name="use_referral_wallet"], input[name="referral_wallet_amount"], input[name="payment_method"]').forEach((input) => {
            const eventName = input.matches('input[name^="seat_qty["], input[name="referral_wallet_amount"]') ? 'input' : 'change';
            input.addEventListener(eventName, scheduleRefresh);
            if (eventName !== 'change') {
                input.addEventListener('change', scheduleRefresh);
            }
        });
    }

    if (payButton) {
        form.addEventListener('submit', async (event) => {
            const submitter = event.submitter;
            if (!submitter || submitter.name !== 'checkout_action' || submitter.value !== 'pay') {
                return;
            }

            event.preventDefault();
            renderFeedback();
            setBusy(true);

            const formData = new FormData(form);
            formData.set('checkout_action', 'pay');

            try {
                const response = await fetch(form.getAttribute('action') || window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                const data = await response.json().catch(() => null);
                if (!data) {
                    throw new Error('Invalid checkout response.');
                }

                if (data.redirectTo) {
                    window.location.href = data.redirectTo;
                    return;
                }

                if (!response.ok || data.ok === false) {
                    renderFeedback(data.errors || ['Impossible de lancer le paiement.'], data.warnings || []);
                    return;
                }

                const warnings = Array.isArray(data.warnings) ? data.warnings : [];

                if (data.fedapayCheckout && window.checkoutFedapay && typeof window.checkoutFedapay.openFromPayload === 'function') {
                    if (warnings.length > 0) {
                        renderFeedback([], warnings);
                    }
                    await window.checkoutFedapay.openFromPayload(data.fedapayCheckout);
                    return;
                }

                if (data.success) {
                    renderFeedback([], warnings, [data.success]);
                    return;
                }

                if (warnings.length > 0) {
                    renderFeedback([], warnings);
                }

                renderFeedback(['Le paiement n’a pas pu être initialisé.']);
            } catch (_error) {
                renderFeedback(['Impossible de lancer le paiement pour le moment.']);
            } finally {
                setBusy(false);
            }
        });
    }
})();
