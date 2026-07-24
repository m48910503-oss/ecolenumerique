(() => {
  const forms = document.querySelectorAll('form[data-period-toggle]');
  if (!forms.length) {
    return;
  }

  forms.forEach((form) => {
    const customFields = form.querySelector('.period-custom-fields');
    const customButton = form.querySelector('button[name="period"][value="custom"]');
    if (!customFields || !customButton) {
      return;
    }

    const periodButtons = Array.from(form.querySelectorAll('button[name="period"]'));

    const setActiveButton = (activeButton) => {
      periodButtons.forEach((btn) => {
        if (!btn.classList.contains('period-btn')) {
          return;
        }
        if (btn === activeButton) {
          btn.classList.add('period-btn--active');
          btn.classList.remove('period-btn--inactive');
        } else {
          btn.classList.remove('period-btn--active');
          btn.classList.add('period-btn--inactive');
        }
      });
    };

    const setCustomVisible = (isCustom) => {
      form.dataset.periodCustom = isCustom ? 'true' : 'false';
      customFields.hidden = !isCustom;
      customFields.setAttribute('aria-hidden', isCustom ? 'false' : 'true');
      if (isCustom) {
        customFields.classList.add('is-visible');
      } else {
        customFields.classList.remove('is-visible');
      }
    };

    const filterToggleButton = form.querySelector('[data-filter-toggle]');
    const filterPanel = form.querySelector('[data-filter-panel]');

    const setFilterPanelVisible = (isOpen) => {
      form.dataset.filterOpen = isOpen ? 'true' : 'false';
      if (filterPanel) {
        filterPanel.hidden = !isOpen;
      }
      if (filterToggleButton) {
        filterToggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        filterToggleButton.classList.toggle('period-filter-toggle--active', isOpen);
      }
    };

    const isInitiallyCustom = form.dataset.periodCustom === 'true';
    setCustomVisible(isInitiallyCustom);
    if (isInitiallyCustom) {
      setActiveButton(customButton);
    }

    if (filterToggleButton && filterPanel) {
      const isInitiallyOpen = form.dataset.filterOpen === 'true';
      setFilterPanelVisible(isInitiallyOpen);

      filterToggleButton.addEventListener('click', () => {
        const nextVisible = form.dataset.filterOpen !== 'true';
        setFilterPanelVisible(nextVisible);

        if (nextVisible) {
          const firstField = filterPanel.querySelector('button[name="period"], input, select, textarea');
          if (firstField) {
            window.setTimeout(() => {
              firstField.focus({ preventScroll: true });
            }, 80);
          }
        }
      });
    }

    customButton.addEventListener('click', (event) => {
      if (form.dataset.periodCustom !== 'true') {
        event.preventDefault();
        setCustomVisible(true);
        setActiveButton(customButton);
        if (filterToggleButton && filterPanel && form.dataset.filterOpen !== 'true') {
          setFilterPanelVisible(true);
        }
        const firstInput = customFields.querySelector('input, select, textarea');
        if (firstInput) {
          window.setTimeout(() => {
            firstInput.focus({ preventScroll: true });
          }, 160);
        }
      }
    });

    periodButtons.forEach((btn) => {
      if (btn === customButton) {
        return;
      }
      btn.addEventListener('click', () => setCustomVisible(false));
    });
  });
})();
