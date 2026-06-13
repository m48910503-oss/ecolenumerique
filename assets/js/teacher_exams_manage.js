(() => {
  'use strict';

  const body = document.body;
  if (!body || body.dataset.view !== 'teacher/exams_manage') {
    return;
  }

  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    const message = String(form.getAttribute('data-confirm-message') || '').trim();
    if (message !== '' && !window.confirm(message)) {
      event.preventDefault();
    }
  });

  const syncMeetingMode = (form) => {
    const modeSelect = form.querySelector('[data-meeting-mode]');
    if (!(modeSelect instanceof HTMLSelectElement)) {
      return;
    }

    const onlineField = form.querySelector('[data-meeting-online]');
    const onsiteField = form.querySelector('[data-meeting-onsite]');
    const isOnline = modeSelect.value === 'online';

    if (onlineField instanceof HTMLElement) {
      onlineField.classList.toggle('opacity-50', !isOnline);
    }
    if (onsiteField instanceof HTMLElement) {
      onsiteField.classList.toggle('opacity-50', isOnline);
    }
  };

  document.querySelectorAll('[data-meeting-slot-form]').forEach((form) => {
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    syncMeetingMode(form);
    form.addEventListener('change', (event) => {
      if (event.target instanceof HTMLSelectElement && event.target.matches('[data-meeting-mode]')) {
        syncMeetingMode(form);
      }
    });
  });

  const normalizeSearchValue = (value) => String(value || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim();

  document.querySelectorAll('[data-exam-search]').forEach((input) => {
    if (!(input instanceof HTMLInputElement)) {
      return;
    }

    const roots = Array.from(document.querySelectorAll('[data-exam-search-root]'));
    const applySearch = () => {
      const query = normalizeSearchValue(input.value);

      roots.forEach((root) => {
        const items = Array.from(root.querySelectorAll('[data-exam-search-item]'));
        const emptyState = root.querySelector('[data-exam-search-empty]');
        let visibleCount = 0;

        items.forEach((item) => {
          if (!(item instanceof HTMLElement)) {
            return;
          }
          const haystack = normalizeSearchValue(`${item.dataset.searchText || ''} ${item.textContent || ''}`);
          const isVisible = query === '' || haystack.includes(query);
          item.classList.toggle('hidden', !isVisible);
          if (isVisible) {
            visibleCount += 1;
          }
        });

        if (emptyState instanceof HTMLElement && items.length > 0) {
          emptyState.classList.toggle('hidden', visibleCount !== 0);
        }
      });
    };

    input.addEventListener('input', applySearch);
    applySearch();
  });
})();
