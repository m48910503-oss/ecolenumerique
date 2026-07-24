(() => {
  'use strict';

  const body = document.body;
  if (!body || !body.classList.contains('app-role-teacher')) {
    return;
  }
  const currentView = String(body.dataset.view || '');

  const initToolbarOffsetSync = () => {
    const toolbar = document.querySelector('.app-shell-toolbar');
    if (!toolbar) {
      return;
    }
    const syncToolbarOffset = () => {
      const height = Math.ceil(toolbar.getBoundingClientRect().height);
      if (height > 0) {
        body.style.setProperty('--app-toolbar-offset-current', `${height}px`);
      }
    };
    syncToolbarOffset();
    window.addEventListener('load', syncToolbarOffset, { once: true });
    window.addEventListener('resize', syncToolbarOffset, { passive: true });
    window.addEventListener('orientationchange', syncToolbarOffset, { passive: true });
    if ('ResizeObserver' in window) {
      const observer = new ResizeObserver(syncToolbarOffset);
      observer.observe(toolbar);
    }
  };

  initToolbarOffsetSync();

  const root =
    document.querySelector('.teacher-hub-main') ||
    document.querySelector('.teacher-workflow-main') ||
    document.querySelector('.teacher-courses-main');
  if (!root) {
    return;
  }

  const toInt = (value, fallback) => {
    const parsed = Number.parseInt(String(value ?? ''), 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
  };

  const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
  const isMobileViewport = () => window.matchMedia('(max-width: 768px)').matches;

  const parseResponsiveSizePair = (value) => {
    const raw = String(value || '').trim();
    if (!raw) {
      return null;
    }
    const parts = raw.split(/[|,:/]/).map((part) => part.trim()).filter(Boolean);
    if (parts.length < 2) {
      return null;
    }
    const mobile = toInt(parts[0], 0);
    const desktop = toInt(parts[1], 0);
    if (mobile <= 0 && desktop <= 0) {
      return null;
    }
    return { mobile: mobile > 0 ? mobile : desktop, desktop: desktop > 0 ? desktop : mobile };
  };

  const getDefaultPerPagePair = (kind, view) => {
    if (kind === 'list') {
      if (view === 'teacher/meetings') {
        return { mobile: 3, desktop: 5 };
      }
      if (view === 'teacher/reviews') {
        return { mobile: 4, desktop: 6 };
      }
      if (view === 'teacher/promotions' || view === 'teacher/coupons') {
        return { mobile: 4, desktop: 6 };
      }
      if (view === 'teacher/withdrawals') {
        return { mobile: 4, desktop: 6 };
      }
      if (view === 'teacher/course_updates') {
        return { mobile: 6, desktop: 10 };
      }
      return { mobile: 5, desktop: 7 };
    }

    if (view === 'teacher/referrals') {
      return { mobile: 6, desktop: 10 };
    }
    if (view === 'teacher/promotions' || view === 'teacher/coupons') {
      return { mobile: 6, desktop: 10 };
    }
    if (view === 'teacher/withdrawals') {
      return { mobile: 6, desktop: 10 };
    }
    return { mobile: 6, desktop: 8 };
  };

  const resolvePerPage = (sourceElement, kind, view) => {
    const defaultPair = getDefaultPerPagePair(kind, view);
    if (!sourceElement) {
      return isMobileViewport() ? defaultPair.mobile : defaultPair.desktop;
    }

    const explicitMobile = toInt(sourceElement.dataset.paginationSizeMobile, 0);
    const explicitDesktop = toInt(sourceElement.dataset.paginationSizeDesktop, 0);
    let mobile = explicitMobile > 0 ? explicitMobile : 0;
    let desktop = explicitDesktop > 0 ? explicitDesktop : 0;

    if (mobile <= 0 || desktop <= 0) {
      const pair = parseResponsiveSizePair(sourceElement.dataset.paginationSize || '');
      if (pair) {
        if (mobile <= 0) {
          mobile = pair.mobile;
        }
        if (desktop <= 0) {
          desktop = pair.desktop;
        }
      } else {
        const fixed = toInt(sourceElement.dataset.paginationSize || '', 0);
        if (fixed > 0) {
          if (mobile <= 0) {
            mobile = fixed;
          }
          if (desktop <= 0) {
            desktop = fixed;
          }
        }
      }
    }

    if (mobile <= 0) {
      mobile = defaultPair.mobile;
    }
    if (desktop <= 0) {
      desktop = defaultPair.desktop;
    }

    return isMobileViewport() ? mobile : desktop;
  };

  const buildIcon = (paths) =>
    `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${paths}</svg>`;

  const ICONS = {
    add: buildIcon('<path d="M12 5v14"/><path d="M5 12h14"/>'),
    open: buildIcon('<path d="M7 17L17 7"/><path d="M8 7h9v9"/>'),
    view: buildIcon('<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6z"/><circle cx="12" cy="12" r="2.5"/>'),
    edit: buildIcon('<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>'),
    trash: buildIcon('<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M7 6l1 14h8l1-14"/>'),
    filter: buildIcon('<path d="M4 6h16"/><path d="M7 12h10"/><path d="M10 18h4"/>'),
    copy: buildIcon('<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>'),
    export: buildIcon('<path d="M12 3v12"/><path d="M8 7l4-4 4 4"/><path d="M4 21h16"/>'),
    reset: buildIcon('<path d="M3 12a9 9 0 109-9"/><path d="M3 3v5h5"/>'),
    check: buildIcon('<path d="M20 6L9 17l-5-5"/>'),
    close: buildIcon('<path d="M18 6L6 18"/><path d="M6 6l12 12"/>'),
    wallet: buildIcon('<rect x="2.5" y="6" width="19" height="12" rx="2"/><path d="M16 12h4.5"/><path d="M6 10h4"/>'),
    chart: buildIcon('<path d="M3 3v18h18"/><rect x="7" y="12" width="3" height="6"/><rect x="12" y="9" width="3" height="9"/><rect x="17" y="6" width="3" height="12"/>'),
    calendar: buildIcon('<rect x="3" y="4.5" width="18" height="16" rx="2"/><path d="M16 2.5v4"/><path d="M8 2.5v4"/><path d="M3 9h18"/>'),
    star: buildIcon('<path d="M12 3.5l2.7 5.5 6.1.9-4.4 4.3 1 6.1L12 17.7l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3.5z"/>'),
    users: buildIcon('<circle cx="9" cy="8.5" r="3"/><circle cx="16.5" cy="9.5" r="2.5"/><path d="M3.5 19c.8-3.1 3-4.8 5.5-4.8s4.7 1.7 5.5 4.8"/><path d="M13 19c.5-2.2 2-3.4 3.9-3.4 1.7 0 3.2 1.1 3.8 3.4"/>'),
    ticket: buildIcon('<path d="M4 7h16a1 1 0 011 1v3a2 2 0 000 4v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3a2 2 0 000-4V8a1 1 0 011-1z"/><path d="M12 7v11"/><path d="M12 9.8h.01"/><path d="M12 13.2h.01"/><path d="M12 16.6h.01"/>'),
    book: buildIcon('<path d="M4.5 5.5A2.5 2.5 0 017 3h13v16H7a2.5 2.5 0 010-5h13"/><path d="M7 3v11"/>'),
    bell: buildIcon('<path d="M18 8a6 6 0 10-12 0c0 7-3 8-3 8h18s-3-1-3-8"/><path d="M10.5 20a1.5 1.5 0 003 0"/>'),
    support: buildIcon('<circle cx="12" cy="12" r="9"/><path d="M8.5 12a3.5 3.5 0 117 0c0 2.2-2 2.7-2 4"/><path d="M12 18.2h.01"/>'),
  };

  const ACTION_ICON_RULES = [
    { pattern: /\bcreer\b/i, icon: 'add' },
    { pattern: /\bouvrir\b/i, icon: 'open' },
    { pattern: /\bvoir\b/i, icon: 'view' },
    { pattern: /\bmodifier\b/i, icon: 'edit' },
    { pattern: /\bsupprimer\b/i, icon: 'trash' },
    { pattern: /\bappliquer\b|\bfiltrer\b/i, icon: 'filter' },
    { pattern: /\bcopier\b/i, icon: 'copy' },
    { pattern: /\bexporter\b/i, icon: 'export' },
    { pattern: /\breinitialiser\b/i, icon: 'reset' },
    { pattern: /\bconfirmer\b/i, icon: 'check' },
    { pattern: /\bannuler\b|\bfermer\b/i, icon: 'close' },
    { pattern: /\bdemander\b.*\bretrait\b/i, icon: 'wallet' },
    { pattern: /\bstatistique(s)?\b/i, icon: 'chart' },
    { pattern: /\bcours\b/i, icon: 'book' },
    { pattern: /\bpromotion(s)?\b|\bcoupon(s)?\b/i, icon: 'ticket' },
    { pattern: /\baffiliation\b|\bparrainage\b/i, icon: 'users' },
    { pattern: /\brendez[-\s]?vous\b|\bmeeting(s)?\b/i, icon: 'calendar' },
    { pattern: /\bavis\b|\bnote(s)?\b/i, icon: 'star' },
    { pattern: /\bnotification(s)?\b/i, icon: 'bell' },
    { pattern: /\bsupport\b/i, icon: 'support' },
    { pattern: /\bcatalogue\b/i, icon: 'book' },
  ];

  const HEADING_ICON_RULES = [
    { pattern: /\bvue d'?ensemble\b|\btableau\b/i, icon: 'chart' },
    { pattern: /\bstatistique(s)?\b|\bpilotage\b/i, icon: 'chart' },
    { pattern: /\bcours\b|\bportefeuille\b/i, icon: 'book' },
    { pattern: /\bpromotion(s)?\b|\bcoupon(s)?\b/i, icon: 'ticket' },
    { pattern: /\brendez[-\s]?vous\b|\bmeeting(s)?\b/i, icon: 'calendar' },
    { pattern: /\bavis\b|\bnote(s)?\b/i, icon: 'star' },
    { pattern: /\baffiliation\b|\bparrainage\b/i, icon: 'users' },
    { pattern: /\bvente(s)?\b|\bretrait(s)?\b|\bgain(s)?\b/i, icon: 'wallet' },
  ];

  const normalizeText = (value) =>
    String(value || '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/\s+/g, ' ')
      .trim();

  const shouldSkipActionIcon = (element, text) => {
    if (!text || text.length > 70) {
      return true;
    }
    if (element.hasAttribute('data-no-auto-icon')) {
      return true;
    }
    if (element.closest('[data-no-auto-icon]')) {
      return true;
    }
    if (element.querySelector('.app-icon, .app-action-icon, svg')) {
      return true;
    }
    if (/^(tout|7j|30j|90j|personnalisee?)$/i.test(text)) {
      return true;
    }
    return false;
  };

  const applyActionIcons = (scope) => {
    const targets = scope.querySelectorAll('a, button, summary');
    targets.forEach((element) => {
      const text = normalizeText(element.textContent);
      if (shouldSkipActionIcon(element, text)) {
        return;
      }
      const rule = ACTION_ICON_RULES.find((item) => item.pattern.test(text));
      if (!rule) {
        return;
      }
      const iconMarkup = ICONS[rule.icon];
      if (!iconMarkup) {
        return;
      }
      const icon = document.createElement('span');
      icon.className = 'app-action-icon';
      icon.setAttribute('aria-hidden', 'true');
      icon.innerHTML = iconMarkup;
      element.prepend(icon);
      element.setAttribute('data-auto-iconized', 'true');
    });
  };

  const shouldSkipHeadingIcon = (element, text) => {
    if (!text || text.length > 90) {
      return true;
    }
    if (element.hasAttribute('data-no-auto-icon') || element.closest('[data-no-auto-icon]')) {
      return true;
    }
    if (element.querySelector('.app-icon, .app-heading-icon, .app-action-icon, svg')) {
      return true;
    }
    return false;
  };

  const applyHeadingIcons = (scope) => {
    const headings = scope.querySelectorAll('h1, h2, h3');
    headings.forEach((heading) => {
      const text = normalizeText(heading.textContent);
      if (shouldSkipHeadingIcon(heading, text)) {
        return;
      }
      const rule = HEADING_ICON_RULES.find((item) => item.pattern.test(text));
      if (!rule) {
        return;
      }
      const iconMarkup = ICONS[rule.icon];
      if (!iconMarkup) {
        return;
      }
      const icon = document.createElement('span');
      icon.className = 'app-heading-icon';
      icon.setAttribute('aria-hidden', 'true');
      icon.innerHTML = iconMarkup;
      heading.classList.add('app-heading-iconized');
      heading.prepend(icon);
    });
  };

  const buildPaginationUI = (onSelectPage) => {
    const wrapper = document.createElement('nav');
    wrapper.className = 'app-pagination is-hidden';
    wrapper.setAttribute('aria-label', 'Pagination');

    const summary = document.createElement('p');
    summary.className = 'app-pagination__summary';
    wrapper.appendChild(summary);

    const controls = document.createElement('div');
    controls.className = 'app-pagination__controls';
    wrapper.appendChild(controls);

    const prevBtn = document.createElement('button');
    prevBtn.type = 'button';
    prevBtn.className = 'app-pagination__button';
    prevBtn.textContent = 'Precedent';
    controls.appendChild(prevBtn);

    const pages = document.createElement('div');
    pages.className = 'app-pagination__pages';
    controls.appendChild(pages);

    const nextBtn = document.createElement('button');
    nextBtn.type = 'button';
    nextBtn.className = 'app-pagination__button';
    nextBtn.textContent = 'Suivant';
    controls.appendChild(nextBtn);

    const windowPages = (page, totalPages) => {
      const maxVisible = 5;
      if (totalPages <= maxVisible) {
        return [1, totalPages];
      }
      let start = Math.max(1, page - 2);
      let end = Math.min(totalPages, start + maxVisible - 1);
      start = Math.max(1, end - maxVisible + 1);
      return [start, end];
    };

    const render = ({ page, totalPages, totalItems, startItem, endItem }) => {
      if (totalPages <= 1) {
        wrapper.classList.add('is-hidden');
        return;
      }
      wrapper.classList.remove('is-hidden');
      summary.textContent = `Affichage ${startItem} a ${endItem} sur ${totalItems}`;

      prevBtn.disabled = page <= 1;
      nextBtn.disabled = page >= totalPages;
      prevBtn.classList.toggle('is-disabled', prevBtn.disabled);
      nextBtn.classList.toggle('is-disabled', nextBtn.disabled);

      pages.innerHTML = '';
      const [start, end] = windowPages(page, totalPages);
      for (let index = start; index <= end; index += 1) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'app-pagination__button app-pagination__button--page';
        btn.textContent = String(index);
        if (index === page) {
          btn.classList.add('is-active');
          btn.setAttribute('aria-current', 'page');
        }
        btn.addEventListener('click', () => onSelectPage(index));
        pages.appendChild(btn);
      }
    };

    prevBtn.addEventListener('click', () => onSelectPage('prev'));
    nextBtn.addEventListener('click', () => onSelectPage('next'));

    return { wrapper, render };
  };

  const initTablePagination = (table) => {
    if (!table || table.dataset.paginationReady === 'true') {
      return;
    }
    if (table.closest('[data-pagination-off]')) {
      return;
    }

    const tbody = table.tBodies && table.tBodies.length ? table.tBodies[0] : null;
    if (!tbody) {
      return;
    }

    const rows = Array.from(tbody.rows);
    if (!rows.length) {
      return;
    }

    const sizeSource = table.closest('[data-pagination-anchor]') || table;
    const perPage = resolvePerPage(sizeSource, 'table', currentView);
    if (rows.length <= perPage) {
      return;
    }

    let currentPage = 1;
    const totalPages = Math.ceil(rows.length / perPage);
    const ui = buildPaginationUI((target) => {
      if (target === 'prev') {
        currentPage = clamp(currentPage - 1, 1, totalPages);
      } else if (target === 'next') {
        currentPage = clamp(currentPage + 1, 1, totalPages);
      } else {
        currentPage = clamp(toInt(target, 1), 1, totalPages);
      }
      render();
    });

    const anchor =
      table.closest('[data-pagination-anchor]') ||
      table.closest('[data-promo-table-wrap]') ||
      table.closest('.overflow-x-auto') ||
      table;
    anchor.insertAdjacentElement('afterend', ui.wrapper);

    const render = () => {
      const safePage = clamp(currentPage, 1, totalPages);
      const startIndex = (safePage - 1) * perPage;
      const endIndex = Math.min(startIndex + perPage, rows.length);
      rows.forEach((row, index) => {
        row.style.display = index >= startIndex && index < endIndex ? '' : 'none';
      });
      ui.render({
        page: safePage,
        totalPages,
        totalItems: rows.length,
        startItem: startIndex + 1,
        endItem: endIndex,
      });
    };

    table.dataset.paginationReady = 'true';
    render();
  };

  const collectScopedItems = (container, selector) =>
    Array.from(container.querySelectorAll(selector)).filter((item) => {
      const owner = item.closest('[data-pagination-list]');
      return owner === container;
    });

  const initListPagination = (container) => {
    if (!container || container.dataset.paginationReady === 'true') {
      return;
    }
    if (container.closest('[data-pagination-off]') || container.dataset.paginationOff === 'true') {
      return;
    }

    const selector =
      container.dataset.paginationItem ||
      '[data-pagination-item], [data-review-item], [data-course-update-item], [data-withdrawal-item], [data-promo-card], .teacher-meeting-card';

    let items = collectScopedItems(container, selector);
    if (!items.length) {
      return;
    }

    items.forEach((item) => {
      if (!item.hasAttribute('data-pagination-display')) {
        item.setAttribute('data-pagination-display', item.style.display || '');
      }
    });

    const perPage = resolvePerPage(container, 'list', currentView);
    if (items.length <= perPage) {
      return;
    }

    let currentPage = 1;
    const ui = buildPaginationUI((target) => {
      const visibleItems = getVisibleItems();
      const totalPages = Math.max(1, Math.ceil(visibleItems.length / perPage));
      if (target === 'prev') {
        currentPage = clamp(currentPage - 1, 1, totalPages);
      } else if (target === 'next') {
        currentPage = clamp(currentPage + 1, 1, totalPages);
      } else {
        currentPage = clamp(toInt(target, 1), 1, totalPages);
      }
      render();
    });

    container.insertAdjacentElement('afterend', ui.wrapper);

    const isExternallyHidden = (item) =>
      item.hasAttribute('hidden') ||
      item.classList.contains('hidden') ||
      item.getAttribute('aria-hidden') === 'true' ||
      (item.style.display === 'none' && item.dataset.paginationManagedHidden !== '1') ||
      item.dataset.paginationExclude === 'true';

    const getVisibleItems = () => items.filter((item) => !isExternallyHidden(item));

    const restoreDisplay = (item) => {
      const original = item.getAttribute('data-pagination-display') || '';
      delete item.dataset.paginationManagedHidden;
      item.style.display = original;
    };

    const render = () => {
      const visibleItems = getVisibleItems();
      const totalItems = visibleItems.length;
      const totalPages = Math.max(1, Math.ceil(totalItems / perPage));
      currentPage = clamp(currentPage, 1, totalPages);
      const startIndex = (currentPage - 1) * perPage;
      const endIndex = Math.min(startIndex + perPage, totalItems);

      items.forEach((item) => {
        if (isExternallyHidden(item)) {
          restoreDisplay(item);
          return;
        }
        restoreDisplay(item);
      });

      visibleItems.forEach((item, index) => {
        if (index >= startIndex && index < endIndex) {
          delete item.dataset.paginationManagedHidden;
          item.style.display = item.getAttribute('data-pagination-display') || '';
        } else {
          item.dataset.paginationManagedHidden = '1';
          item.style.display = 'none';
        }
      });

      ui.render({
        page: currentPage,
        totalPages,
        totalItems,
        startItem: totalItems === 0 ? 0 : startIndex + 1,
        endItem: totalItems === 0 ? 0 : endIndex,
      });
    };

    const observer = new MutationObserver(() => {
      items = collectScopedItems(container, selector);
      items.forEach((item) => {
        if (!item.hasAttribute('data-pagination-display')) {
          item.setAttribute('data-pagination-display', item.style.display || '');
        }
      });
      render();
    });

    observer.observe(container, {
      childList: true,
      subtree: false,
      attributes: true,
      attributeFilter: ['hidden', 'class', 'aria-hidden', 'style', 'data-pagination-exclude'],
    });

    container.dataset.paginationReady = 'true';
    render();
  };

  const decorateKnownListBlocks = () => {
    const presets = [
      { selector: '[data-reviews-list]', item: '[data-review-item]', size: 6 },
      { selector: '[data-meetings-list]', item: '.teacher-meeting-card', size: 5 },
      { selector: '[data-promo-cards]', item: '[data-promo-card]', size: 6 },
      { selector: '[data-course-updates-list]', item: '[data-course-update-item]', size: 8 },
      { selector: '[data-withdrawals-list]', item: '[data-withdrawal-item]', size: 6 },
    ];
    presets.forEach(({ selector, item, size }) => {
      document.querySelectorAll(selector).forEach((container) => {
        if (container.closest('[data-pagination-off]') || container.dataset.paginationOff === 'true') {
          return;
        }
        if (!container.hasAttribute('data-pagination-list')) {
          container.setAttribute('data-pagination-list', 'true');
        }
        if (!container.hasAttribute('data-pagination-item')) {
          container.setAttribute('data-pagination-item', item);
        }
        if (!container.hasAttribute('data-pagination-size')) {
          container.setAttribute('data-pagination-size', String(size));
        }
      });
    });
  };

  const initRealtimeRefresh = () => {
    const view = String(body.dataset.view || '');
    const liveViews = new Set([
      'teacher/dashboard',
      'teacher/promotions',
      'teacher/coupons',
      'teacher/reviews',
      'teacher/meetings',
      'teacher/withdrawals',
      'teacher/referrals',
      'teacher/course_updates',
      'teacher/courses',
    ]);

    if (!liveViews.has(view)) {
      return;
    }

    const params = new URLSearchParams(window.location.search);
    if (params.get('live') === 'off') {
      return;
    }

    const probeIntervalMs = toInt(body.dataset.liveProbeMs, 30000);
    const idleThresholdMs = toInt(body.dataset.liveIdleMs, 120000);
    let lastInteractionAt = Date.now();

    const markInteraction = () => {
      lastInteractionAt = Date.now();
    };

    ['pointerdown', 'keydown', 'scroll', 'input', 'change', 'touchstart'].forEach((eventName) => {
      window.addEventListener(eventName, markInteraction, { passive: true, capture: true });
    });

    window.setInterval(() => {
      if (document.hidden || !navigator.onLine) {
        return;
      }
      if (Date.now() - lastInteractionAt < idleThresholdMs) {
        return;
      }
      const active = document.activeElement;
      if (active && /^(INPUT|TEXTAREA|SELECT)$/i.test(active.tagName)) {
        return;
      }
      if (document.querySelector('[aria-modal="true"]:not(.hidden), [data-modal-open="true"], .is-modal-open')) {
        return;
      }
      if (window.__teacherLiveRefreshLock === true) {
        return;
      }
      window.location.reload();
    }, probeIntervalMs);
  };

  decorateKnownListBlocks();
  applyActionIcons(root);
  applyHeadingIcons(root);

  const listContainers = root.querySelectorAll('[data-pagination-list]');
  listContainers.forEach((container) => initListPagination(container));

  const tables = root.querySelectorAll('table');
  tables.forEach((table) => initTablePagination(table));

  initRealtimeRefresh();
})();
