(() => {
  'use strict';

  const body = document.body;
  if (!body || String(body.dataset.view || '') !== 'teacher/meetings') {
    return;
  }

  const form = document.querySelector('[data-meetings-filter-form]');
  if (!(form instanceof HTMLFormElement)) {
    return;
  }

  const toggleButton = form.querySelector('[data-meetings-filter-toggle]');
  const toggleLabel = form.querySelector('[data-meetings-filter-toggle-label]');
  const filterPanel = form.querySelector('[data-meetings-filter-panel]');
  const filterTypeSelect = form.querySelector('[data-meetings-filter-type]');
  const filterFields = Array.from(form.querySelectorAll('[data-meetings-filter-field]'));

  const setPanelVisible = (isOpen) => {
    form.dataset.filterOpen = isOpen ? 'true' : 'false';
    if (filterPanel instanceof HTMLElement) {
      filterPanel.hidden = !isOpen;
    }

    if (toggleButton instanceof HTMLElement) {
      toggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      toggleButton.classList.toggle('teacher-meetings-filter-toggle--active', isOpen);
    }

    if (toggleLabel instanceof HTMLElement) {
      toggleLabel.textContent = isOpen ? 'Masquer les filtres' : 'Filtres';
    }
  };

  const syncFilterFields = () => {
    if (!(filterTypeSelect instanceof HTMLSelectElement) || filterFields.length === 0) {
      return;
    }

    const selectedType = String(filterTypeSelect.value || 'status').trim();
    filterFields.forEach((field) => {
      const fieldType = String(field.getAttribute('data-meetings-filter-field') || '').trim();
      const isActive = fieldType === selectedType;
      field.classList.toggle('hidden', !isActive);

      const controls = field.querySelectorAll('input, select, textarea');
      controls.forEach((control) => {
        control.disabled = !isActive;
      });
    });
  };

  const isOpenInitially = form.dataset.filterOpen === 'true';
  setPanelVisible(isOpenInitially);
  syncFilterFields();

  if (toggleButton instanceof HTMLElement && filterPanel instanceof HTMLElement) {
    toggleButton.addEventListener('click', () => {
      const nextState = form.dataset.filterOpen !== 'true';
      setPanelVisible(nextState);

      if (nextState) {
        const firstField = filterPanel.querySelector('input, select, textarea, button');
        if (firstField instanceof HTMLElement) {
          window.setTimeout(() => {
            firstField.focus({ preventScroll: true });
          }, 80);
        }
      }
    });
  }

  if (filterTypeSelect instanceof HTMLSelectElement) {
    filterTypeSelect.addEventListener('change', syncFilterFields);
  }

  document.addEventListener('submit', (event) => {
    const submittedForm = event.target;
    if (!(submittedForm instanceof HTMLFormElement)) {
      return;
    }

    const message = String(submittedForm.getAttribute('data-confirm-message') || '').trim();
    if (message !== '' && !window.confirm(message)) {
      event.preventDefault();
    }
  });
})();
