document.addEventListener('DOMContentLoaded', () => {
    const submitDebounce = (form, delayMs) => {
        if (!form) {
            return;
        }
        const stateKey = '__statsSubmitTimer';
        if (form[stateKey]) {
            clearTimeout(form[stateKey]);
        }
        form[stateKey] = setTimeout(() => {
            form.requestSubmit();
        }, delayMs);
    };

    const periodForm = document.querySelector('[data-admin-period-form]');
    if (periodForm) {
        const scopeSelect = periodForm.querySelector('[data-period-scope]');
        const scopeFields = periodForm.querySelectorAll('[data-scope-only]');
        const numberInputs = periodForm.querySelectorAll('input[type="number"]');

        const refreshPeriodFieldVisibility = () => {
            if (!scopeSelect) {
                return;
            }
            const scope = String(scopeSelect.value || '').trim();
            scopeFields.forEach((field) => {
                const raw = String(field.getAttribute('data-scope-only') || '').trim();
                const scopes = raw === '' ? [] : raw.split(/\s+/);
                const visible = scopes.includes(scope);
                field.hidden = !visible;

                field.querySelectorAll('input, select, textarea').forEach((input) => {
                    input.disabled = !visible;
                });
            });
        };

        refreshPeriodFieldVisibility();

        periodForm.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            if (target === scopeSelect) {
                refreshPeriodFieldVisibility();
            }

            if (target.matches('select')) {
                periodForm.requestSubmit();
            }

            if (target.matches('input[type="number"]')) {
                submitDebounce(periodForm, 420);
            }
        });

        numberInputs.forEach((input) => {
            input.addEventListener('input', () => submitDebounce(periodForm, 550));
        });
    }

    const chartForm = document.querySelector('[data-admin-chart-form]');
    if (chartForm) {
        const metricSelect = chartForm.querySelector('[data-chart-metric]');
        const granularitySelect = chartForm.querySelector('[data-chart-granularity]');
        const pointsInput = chartForm.querySelector('[data-chart-points]');
        const granularityRaw = String(chartForm.getAttribute('data-chart-granularity-map') || '{}');
        let granularityMap = {};
        try {
            granularityMap = JSON.parse(granularityRaw);
        } catch (error) {
            granularityMap = {};
        }

        const refreshPointsBoundaries = () => {
            if (!(granularitySelect instanceof HTMLSelectElement) || !(pointsInput instanceof HTMLInputElement)) {
                return;
            }
            const key = String(granularitySelect.value || '').trim();
            const config = granularityMap[key];
            if (!config || typeof config !== 'object') {
                return;
            }

            const min = Number(config.min);
            const max = Number(config.max);
            const defaultValue = Number(config.default);

            if (Number.isFinite(min)) {
                pointsInput.min = String(Math.max(1, Math.floor(min)));
            }
            if (Number.isFinite(max)) {
                pointsInput.max = String(Math.max(1, Math.floor(max)));
            }

            const current = Number(pointsInput.value);
            if (!Number.isFinite(current) || current < min || current > max) {
                pointsInput.value = String(Number.isFinite(defaultValue) ? defaultValue : min);
            }
        };

        refreshPointsBoundaries();

        if (granularitySelect) {
            granularitySelect.addEventListener('change', () => {
                refreshPointsBoundaries();
                chartForm.requestSubmit();
            });
        }

        if (metricSelect) {
            metricSelect.addEventListener('change', () => {
                chartForm.requestSubmit();
            });
        }

        if (pointsInput) {
            pointsInput.addEventListener('change', () => {
                refreshPointsBoundaries();
                chartForm.requestSubmit();
            });
            pointsInput.addEventListener('input', () => {
                refreshPointsBoundaries();
                submitDebounce(chartForm, 520);
            });
        }
    }
});
