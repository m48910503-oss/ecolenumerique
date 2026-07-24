(() => {
    'use strict';

    const FEDAPAY_SRC = 'https://cdn.fedapay.com/checkout.js?v=1.1.7';
    let sdkPromise = null;

    const ensureSdk = () => {
        if (window.FedaPay && typeof window.FedaPay.init === 'function') {
            return Promise.resolve(window.FedaPay);
        }

        if (sdkPromise) {
            return sdkPromise;
        }

        sdkPromise = new Promise((resolve, reject) => {
            const existing = document.querySelector(`script[src="${FEDAPAY_SRC}"]`);
            if (existing) {
                existing.addEventListener('load', () => resolve(window.FedaPay), { once: true });
                existing.addEventListener('error', () => reject(new Error('FedaPay script not available.')), { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = FEDAPAY_SRC;
            script.async = true;
            script.defer = true;
            script.addEventListener('load', () => resolve(window.FedaPay), { once: true });
            script.addEventListener('error', () => reject(new Error('FedaPay script not available.')), { once: true });
            document.head.appendChild(script);
        });

        return sdkPromise;
    };

    const initButton = async (button, autoOpen = false) => {
        if (!button) {
            return;
        }

        await ensureSdk();
        if (!window.FedaPay || typeof window.FedaPay.init !== 'function') {
            throw new Error('FedaPay script not available.');
        }

        if (button.dataset.fedapayReady !== '1') {
            const returnUrl = button.getAttribute('data-return-url') || '';
            window.FedaPay.init(button, {
                onComplete: (result) => {
                    if (!result || !result.transaction || !result.transaction.id) {
                        return;
                    }
                    if (result.reason === window.FedaPay.CHECKOUT_COMPLETED && returnUrl !== '') {
                        window.location.href = `${returnUrl}?id=${encodeURIComponent(result.transaction.id)}`;
                    }
                },
            });
            button.dataset.fedapayReady = '1';
        }

        if (autoOpen) {
            window.setTimeout(() => {
                button.click();
            }, 120);
        }
    };

    const createRuntimeButton = (payload) => {
        const existing = document.getElementById('fedapay-runtime-button');
        if (existing) {
            existing.remove();
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.id = 'fedapay-runtime-button';
        button.style.position = 'fixed';
        button.style.left = '-9999px';
        button.style.top = '0';
        button.setAttribute('aria-hidden', 'true');
        button.dataset.publicKey = String(payload.public_key || '');
        button.dataset.transactionId = String(payload.transaction_id || 0);
        button.dataset.transactionAmount = String(payload.amount || 0);
        button.dataset.transactionDescription = `Commande ${String(payload.order_ref || '')}`.trim();
        button.dataset.currencyIso = 'XOF';
        button.dataset.customerFirstname = String(payload.customer?.firstname || '');
        button.dataset.customerLastname = String(payload.customer?.lastname || '');
        button.dataset.customerEmail = String(payload.customer?.email || '');
        button.dataset.customerPhoneNumberNumber = String(payload.customer?.phone_number?.number || '');
        button.dataset.customerPhoneNumberCountry = String(payload.customer?.phone_number?.country || '');
        button.dataset.returnUrl = String(payload.return_url || '');
        document.body.appendChild(button);
        return button;
    };

    const openFromPayload = async (payload) => {
        const button = createRuntimeButton(payload || {});
        await initButton(button, true);
    };

    window.checkoutFedapay = {
        initButton,
        openFromPayload,
    };

    const existingButton = document.getElementById('fedapay-button');
    if (existingButton) {
        initButton(existingButton, existingButton.getAttribute('data-auto-open') === '1').catch((error) => {
            if (window.console && typeof window.console.error === 'function') {
                window.console.error(error);
            }
        });
    }
})();
