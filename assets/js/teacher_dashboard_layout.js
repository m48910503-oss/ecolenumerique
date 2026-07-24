(() => {
    'use strict';

    const body = document.body;
    if (!body || body.dataset.view !== 'teacher/dashboard') {
        return;
    }

    const STORAGE_KEY = 'teacher.dashboard.layout.v1';
    const FILTER_STORAGE_KEY = 'teacher.dashboard.filters.v1';
    const FILTER_KEYS = ['period', 'inbox', 'from', 'to', 'goal_xof'];
    const primaryContainer = document.getElementById('teacher-dashboard-primary');
    const advancedContainer = document.getElementById('teacher-dashboard-advanced');
    if (!primaryContainer) {
        return;
    }

    const containers = {
        primary: primaryContainer,
        advanced: advancedContainer,
    };

    const densityComfortBtn = document.getElementById('td-density-comfort');
    const densityCompactBtn = document.getElementById('td-density-compact');
    const priorityBtn = document.getElementById('td-priority-toggle');
    const layoutToggleBtn = document.getElementById('td-layout-toggle');
    const layoutPanel = document.getElementById('td-layout-panel');
    const layoutResetBtn = document.getElementById('td-layout-reset');
    const customRangeToggleBtn = document.getElementById('td-custom-range-toggle');
    const customRangePanel = document.getElementById('td-custom-range-panel');
    const periodSubmitButtons = Array.from(document.querySelectorAll('.js-td-period-submit'));
    const customRangeInputs = customRangePanel
        ? Array.from(customRangePanel.querySelectorAll('input[name="from"], input[name="to"]'))
        : [];
    const listPrimary = document.getElementById('td-widget-list-primary');
    const listAdvanced = document.getElementById('td-widget-list-advanced');

    const safeStorage = {
        get(key = STORAGE_KEY) {
            try {
                return localStorage.getItem(key);
            } catch (_error) {
                return null;
            }
        },
        set(value, key = STORAGE_KEY) {
            try {
                localStorage.setItem(key, value);
            } catch (_error) {
                /* noop */
            }
        },
    };

    const readFiltersFromParams = (params) => {
        const next = {};
        FILTER_KEYS.forEach((key) => {
            const value = (params.get(key) || '').trim();
            if (value !== '') {
                next[key] = value;
            }
        });
        return next;
    };

    const saveDashboardFilters = (filters) => {
        try {
            safeStorage.set(JSON.stringify(filters || {}), FILTER_STORAGE_KEY);
        } catch (_error) {
            /* noop */
        }
    };

    const loadDashboardFilters = () => {
        const raw = safeStorage.get(FILTER_STORAGE_KEY);
        if (!raw) {
            return {};
        }
        try {
            const parsed = JSON.parse(raw);
            return typeof parsed === 'object' && parsed !== null ? parsed : {};
        } catch (_error) {
            return {};
        }
    };

    const restoreFiltersIfNeeded = () => {
        const url = new URL(window.location.href);
        const params = url.searchParams;
        const hasQueryFilters = FILTER_KEYS.some((key) => params.has(key));
        if (hasQueryFilters) {
            const currentFilters = readFiltersFromParams(params);
            if (Object.keys(currentFilters).length > 0) {
                saveDashboardFilters(currentFilters);
            }
            return false;
        }

        const savedFilters = loadDashboardFilters();
        const savedEntries = Object.entries(savedFilters).filter(([, value]) => typeof value === 'string' && value.trim() !== '');
        if (savedEntries.length === 0) {
            return false;
        }

        savedEntries.forEach(([key, value]) => {
            params.set(key, value);
        });

        const target = `${url.pathname}?${params.toString()}${url.hash || ''}`;
        if (target !== `${window.location.pathname}${window.location.search}${window.location.hash}`) {
            window.location.replace(target);
            return true;
        }

        return false;
    };

    if (restoreFiltersIfNeeded()) {
        return;
    }

    const widgetChildren = (container) =>
        Array.from(container?.children || []).filter((el) => el.classList?.contains('td-widget') && el.dataset.widgetId);

    const defaultOrder = {
        primary: widgetChildren(primaryContainer).map((el) => el.dataset.widgetId),
        advanced: widgetChildren(advancedContainer).map((el) => el.dataset.widgetId),
    };

    const defaultState = {
        density: 'comfort',
        priorityFirst: true,
        panelOpen: false,
        order: {
            primary: defaultOrder.primary.slice(),
            advanced: defaultOrder.advanced.slice(),
        },
        hidden: {},
    };

    const cloneDefaultState = () => JSON.parse(JSON.stringify(defaultState));

    const loadState = () => {
        const raw = safeStorage.get();
        if (!raw) {
            return cloneDefaultState();
        }
        try {
            const parsed = JSON.parse(raw);
            const density = parsed?.density === 'compact' ? 'compact' : 'comfort';
            const priorityFirst = parsed?.priorityFirst === true;
            const panelOpen = parsed?.panelOpen === true;
            const hidden = typeof parsed?.hidden === 'object' && parsed.hidden !== null ? parsed.hidden : {};
            const orderPrimary = Array.isArray(parsed?.order?.primary) ? parsed.order.primary : defaultOrder.primary;
            const orderAdvanced = Array.isArray(parsed?.order?.advanced) ? parsed.order.advanced : defaultOrder.advanced;

            return {
                density,
                priorityFirst,
                panelOpen,
                order: {
                    primary: orderPrimary.map(String),
                    advanced: orderAdvanced.map(String),
                },
                hidden,
            };
        } catch (_error) {
            return cloneDefaultState();
        }
    };

    const state = loadState();

    const saveState = () => {
        safeStorage.set(JSON.stringify(state));
    };

    const normalizeOrder = (groupName) => {
        const container = containers[groupName];
        if (!container) {
            return [];
        }
        const ids = widgetChildren(container).map((el) => el.dataset.widgetId);
        const current = Array.isArray(state.order[groupName]) ? state.order[groupName] : [];
        const known = current.filter((id) => ids.includes(id));
        const missing = ids.filter((id) => !known.includes(id));
        const normalized = known.concat(missing);
        state.order[groupName] = normalized;
        return normalized;
    };

    const priorityValue = (el) => {
        const raw = Number.parseInt(el.dataset.widgetPriority || '0', 10);
        return Number.isFinite(raw) ? raw : 0;
    };

    const applyGroup = (groupName) => {
        const container = containers[groupName];
        if (!container) {
            return;
        }

        const map = new Map();
        widgetChildren(container).forEach((el) => {
            map.set(el.dataset.widgetId, el);
        });

        const ids = normalizeOrder(groupName);
        const baseNodes = ids.map((id) => map.get(id)).filter(Boolean);

        const nodes = state.priorityFirst
            ? baseNodes.slice().sort((a, b) => {
                const score = priorityValue(b) - priorityValue(a);
                if (score !== 0) {
                    return score;
                }
                return ids.indexOf(a.dataset.widgetId) - ids.indexOf(b.dataset.widgetId);
            })
            : baseNodes;

        nodes.forEach((node) => {
            container.appendChild(node);
        });

        nodes.forEach((node) => {
            const id = node.dataset.widgetId;
            const hidden = state.hidden[id] === true;
            node.style.display = hidden ? 'none' : '';
            node.setAttribute('aria-hidden', hidden ? 'true' : 'false');
        });

        nodes.forEach((node) => node.classList.remove('is-priority-top'));
        nodes
            .filter((node) => node.style.display !== 'none')
            .slice(0, 2)
            .forEach((node) => node.classList.add('is-priority-top'));
    };

    const applyDensity = () => {
        body.setAttribute('data-density', state.density === 'compact' ? 'compact' : 'comfort');

        const comfortActive = state.density !== 'compact';
        if (densityComfortBtn) {
            densityComfortBtn.classList.toggle('period-btn--active', comfortActive);
            densityComfortBtn.classList.toggle('period-btn--inactive', !comfortActive);
        }
        if (densityCompactBtn) {
            densityCompactBtn.classList.toggle('period-btn--active', !comfortActive);
            densityCompactBtn.classList.toggle('period-btn--inactive', comfortActive);
        }
    };

    const applyPriorityButton = () => {
        if (!priorityBtn) {
            return;
        }
        priorityBtn.setAttribute('aria-pressed', state.priorityFirst ? 'true' : 'false');
        priorityBtn.classList.toggle('period-btn--active', state.priorityFirst);
        priorityBtn.classList.toggle('period-btn--inactive', !state.priorityFirst);
    };

    const widgetMeta = (groupName) => {
        const container = containers[groupName];
        if (!container) {
            return [];
        }
        const map = new Map(widgetChildren(container).map((el) => [el.dataset.widgetId, el]));
        return normalizeOrder(groupName)
            .map((id) => map.get(id))
            .filter(Boolean)
            .map((el) => ({
                id: el.dataset.widgetId,
                name: el.dataset.widgetName || el.dataset.widgetId,
                hidden: state.hidden[el.dataset.widgetId] === true,
            }));
    };

    const moveWidget = (groupName, id, direction) => {
        const order = normalizeOrder(groupName).slice();
        const index = order.indexOf(id);
        if (index < 0) {
            return;
        }
        const target = direction === 'up' ? index - 1 : index + 1;
        if (target < 0 || target >= order.length) {
            return;
        }
        const temp = order[index];
        order[index] = order[target];
        order[target] = temp;
        state.order[groupName] = order;
        saveState();
        renderWidgetsPanel();
        applyGroup(groupName);
    };

    const toggleWidgetVisibility = (id) => {
        state.hidden[id] = state.hidden[id] !== true;
        saveState();
        renderWidgetsPanel();
        applyGroup('primary');
        applyGroup('advanced');
    };

    const renderGroupPanel = (groupName, mountPoint) => {
        if (!mountPoint) {
            return;
        }
        mountPoint.innerHTML = '';
        widgetMeta(groupName).forEach((item) => {
            const row = document.createElement('div');
            row.className = 'dash-widget-row';

            const label = document.createElement('span');
            label.className = 'text-xs font-semibold text-slate-200 truncate';
            label.textContent = item.name;

            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'dash-widget-btn';
            toggle.textContent = item.hidden ? 'Afficher' : 'Masquer';
            toggle.addEventListener('click', () => toggleWidgetVisibility(item.id));

            const up = document.createElement('button');
            up.type = 'button';
            up.className = 'dash-widget-btn';
            up.textContent = '↑';
            up.setAttribute('aria-label', `Monter ${item.name}`);
            up.addEventListener('click', () => moveWidget(groupName, item.id, 'up'));

            const down = document.createElement('button');
            down.type = 'button';
            down.className = 'dash-widget-btn';
            down.textContent = '↓';
            down.setAttribute('aria-label', `Descendre ${item.name}`);
            down.addEventListener('click', () => moveWidget(groupName, item.id, 'down'));

            row.appendChild(label);
            row.appendChild(toggle);
            row.appendChild(up);
            row.appendChild(down);
            mountPoint.appendChild(row);
        });
    };

    const renderWidgetsPanel = () => {
        renderGroupPanel('primary', listPrimary);
        renderGroupPanel('advanced', listAdvanced);
    };

    const setCustomRangeOpen = (open, shouldFocus = false) => {
        if (!customRangePanel) {
            return;
        }
        customRangePanel.hidden = !open;
        customRangePanel.classList.toggle('is-open', open);
        customRangeInputs.forEach((input) => {
            input.disabled = !open;
        });
        if (customRangeToggleBtn) {
            customRangeToggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            customRangeToggleBtn.classList.toggle('period-btn--active', open);
            customRangeToggleBtn.classList.toggle('period-btn--inactive', !open);
        }
        if (open && shouldFocus && customRangeInputs[0]) {
            customRangeInputs[0].focus();
        }
    };

    const setPanelOpen = (open) => {
        if (!layoutPanel) {
            return;
        }
        state.panelOpen = open;
        layoutPanel.hidden = !open;
        if (layoutToggleBtn) {
            layoutToggleBtn.classList.toggle('period-btn--active', open);
            layoutToggleBtn.classList.toggle('period-btn--inactive', !open);
        }
        saveState();
    };

    const applyAll = () => {
        applyDensity();
        applyPriorityButton();
        applyGroup('primary');
        applyGroup('advanced');
        renderWidgetsPanel();
        setPanelOpen(state.panelOpen === true);
    };

    if (densityComfortBtn) {
        densityComfortBtn.addEventListener('click', () => {
            state.density = 'comfort';
            saveState();
            applyDensity();
        });
    }

    if (densityCompactBtn) {
        densityCompactBtn.addEventListener('click', () => {
            state.density = 'compact';
            saveState();
            applyDensity();
        });
    }

    if (priorityBtn) {
        priorityBtn.addEventListener('click', () => {
            state.priorityFirst = !state.priorityFirst;
            saveState();
            applyPriorityButton();
            applyGroup('primary');
            applyGroup('advanced');
            renderWidgetsPanel();
        });
    }

    if (layoutToggleBtn) {
        layoutToggleBtn.addEventListener('click', () => {
            setPanelOpen(layoutPanel ? layoutPanel.hidden : true);
        });
    }

    if (layoutResetBtn) {
        layoutResetBtn.addEventListener('click', () => {
            state.density = defaultState.density;
            state.priorityFirst = defaultState.priorityFirst;
            state.hidden = {};
            state.order.primary = defaultOrder.primary.slice();
            state.order.advanced = defaultOrder.advanced.slice();
            saveState();
            applyAll();
        });
    }

    if (customRangeToggleBtn) {
        customRangeToggleBtn.addEventListener('click', () => {
            const nextOpen = customRangePanel ? customRangePanel.hidden : true;
            setCustomRangeOpen(nextOpen, true);
        });
    }

    periodSubmitButtons.forEach((button) => {
        if (button.name !== 'period') {
            return;
        }
        button.addEventListener('click', () => {
            if (button.value === 'custom') {
                setCustomRangeOpen(true, false);
                return;
            }
            setCustomRangeOpen(false, false);
        });
    });

    const filtersForm = document.querySelector('.filter-bar form');
    if (filtersForm) {
        filtersForm.addEventListener('submit', (event) => {
            const submitter = event.submitter;
            const formData = new FormData(filtersForm);
            const nextFilters = {};

            FILTER_KEYS.forEach((key) => {
                const rawValue = (formData.get(key) || '').toString().trim();
                if (rawValue !== '') {
                    nextFilters[key] = rawValue;
                }
            });

            if (submitter && submitter.name === 'period' && submitter.value) {
                nextFilters.period = String(submitter.value);
                if (submitter.value !== 'custom') {
                    delete nextFilters.from;
                    delete nextFilters.to;
                }
            }

            if (Object.keys(nextFilters).length > 0) {
                saveDashboardFilters(nextFilters);
            }
        });
    }

    setCustomRangeOpen(customRangePanel ? !customRangePanel.hidden : false);
    applyAll();
})();
