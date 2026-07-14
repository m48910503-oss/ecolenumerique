(function () {
  'use strict';

  function toArray(value) {
    return Array.prototype.slice.call(value || []);
  }

  function getStoredTheme() {
    try {
      return localStorage.getItem('theme');
    } catch (_error) {
      return null;
    }
  }

  function setStoredTheme(value) {
    try {
      localStorage.setItem('theme', value);
    } catch (_error) {
      /* Storage unavailable. */
    }
  }

  function themeIcon(isDark) {
    return isDark
      ? '<i class="fas fa-sun" aria-hidden="true"></i>'
      : '<i class="fas fa-moon" aria-hidden="true"></i>';
  }

  function setThemeNodeVisible(node, visible) {
    if (!(node instanceof HTMLElement)) {
      return;
    }

    node.hidden = !visible;
    node.classList.toggle('hidden', !visible);
    node.style.display = visible ? '' : 'none';
    node.setAttribute('aria-hidden', 'true');
  }

  function syncThemeButton(button, isDark) {
    button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
    button.setAttribute('title', isDark ? 'Passer au mode clair' : 'Passer au mode sombre');

    var darkIcon = button.querySelector('[data-theme-icon-dark]');
    var lightIcon = button.querySelector('[data-theme-icon-light]');
    if (darkIcon || lightIcon) {
      setThemeNodeVisible(darkIcon, !isDark);
      setThemeNodeVisible(lightIcon, isDark);
    }

    var label = button.querySelector('[data-theme-label]');
    if (label) {
      label.innerHTML = themeIcon(isDark);
      label.setAttribute('aria-hidden', 'true');
    }

    toArray(button.children).forEach(function (node) {
      if (!(node instanceof HTMLElement)) {
        return;
      }
      if (node.classList.contains('dark:hidden')) {
        setThemeNodeVisible(node, !isDark);
        return;
      }
      if (node.classList.contains('dark:inline')) {
        setThemeNodeVisible(node, isDark);
      }
    });
  }

  function applyTheme(isDark) {
    var html = document.documentElement;
    html.classList.toggle('dark', isDark);
    html.classList.toggle('light', !isDark);
    html.setAttribute('data-theme', isDark ? 'dark' : 'light');
    html.style.colorScheme = isDark ? 'dark' : 'light';
    setStoredTheme(isDark ? 'dark' : 'light');

    toArray(document.querySelectorAll('[data-theme-toggle], #theme-toggle, #theme-toggle-sidebar')).forEach(function (button) {
      syncThemeButton(button, isDark);
    });
  }

  function toggleTheme() {
    applyTheme(!document.documentElement.classList.contains('dark'));
  }

  function bindThemeToggles() {
    toArray(document.querySelectorAll('[data-theme-toggle], #theme-toggle, #theme-toggle-sidebar')).forEach(function (button) {
      if (button.dataset.themeToggleBound === '1') {
        return;
      }

      button.addEventListener('click', function (event) {
        event.preventDefault();
        toggleTheme();
      });

      button.dataset.themeToggleBound = '1';
    });
  }

  function submitForm(form) {
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
      return;
    }
    form.submit();
  }

  function bindPeriodForm() {
    var form = document.querySelector('[data-period-form]');
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    var hidden = form.querySelector('[data-period-value]');
    var customFields = form.querySelector('[data-period-custom-fields]');
    var customApply = form.querySelector('[data-period-custom-apply]');
    var presetButtons = toArray(form.querySelectorAll('[data-period-preset]'));
    var fromInput = form.querySelector('input[name="from"]');
    var toInput = form.querySelector('input[name="to"]');

    if (!(hidden instanceof HTMLInputElement) || !(customFields instanceof HTMLElement)) {
      return;
    }

    var updateVisibility = function () {
      var isCustom = String(hidden.value || '') === 'custom';
      form.setAttribute('data-period-custom', isCustom ? 'true' : 'false');
      customFields.hidden = !isCustom;
      customFields.classList.toggle('hidden', !isCustom);
      customFields.classList.toggle('is-visible', isCustom);
      customFields.setAttribute('aria-hidden', isCustom ? 'false' : 'true');
    };

    presetButtons.forEach(function (button) {
      if (button.dataset.periodPresetBound === '1') {
        return;
      }

      button.addEventListener('click', function () {
        var value = String(button.dataset.periodPreset || '30d');
        hidden.value = value;
        updateVisibility();

        if (value === 'custom') {
          if (fromInput instanceof HTMLInputElement) {
            fromInput.focus();
          }
          return;
        }

        submitForm(form);
      });

      button.dataset.periodPresetBound = '1';
    });

    if (customApply instanceof HTMLElement && customApply.dataset.periodCustomBound !== '1') {
      customApply.addEventListener('click', function (event) {
        event.preventDefault();
        hidden.value = 'custom';
        updateVisibility();
        submitForm(form);
      });
      customApply.dataset.periodCustomBound = '1';
    }

    if (form.dataset.periodSubmitBound !== '1') {
      form.addEventListener('submit', function () {
        form.setAttribute('data-period-custom', String(hidden.value || '') === 'custom' ? 'true' : 'false');
        if (hidden.value === 'custom' && fromInput instanceof HTMLInputElement && toInput instanceof HTMLInputElement) {
          form.querySelectorAll('input[name="from"], input[name="to"]').forEach(function (input) {
            if (input instanceof HTMLInputElement) {
              input.value = input.value.trim();
            }
          });
        }
      });
      form.dataset.periodSubmitBound = '1';
    }

    updateVisibility();
  }

  function openModal(modal) {
    if (!(modal instanceof HTMLElement)) {
      return;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');

    var amountInput = modal.querySelector('input[name="amount_xof"]');
    if (amountInput instanceof HTMLInputElement) {
      amountInput.focus();
      amountInput.select();
    }
  }

  function closeModal(modal) {
    if (!(modal instanceof HTMLElement)) {
      return;
    }

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
  }

  function bindWithdrawalModal() {
    var modal = document.querySelector('[data-withdraw-modal]');
    if (!(modal instanceof HTMLElement)) {
      return;
    }

    var openButtons = toArray(document.querySelectorAll('[data-open-withdraw-modal]'));
    var closeButtons = toArray(modal.querySelectorAll('[data-close-withdraw-modal]'));
    var form = modal.querySelector('[data-withdraw-form]');
    var typeInput = modal.querySelector('input[name="withdrawal_type"]');
    var amountInput = modal.querySelector('input[name="amount_xof"]');
    var methodSelect = modal.querySelector('select[name="method"]');
    var kicker = modal.querySelector('[data-withdraw-modal-kicker]');
    var title = modal.querySelector('[data-withdraw-modal-title]');
    var minLabel = modal.querySelector('[data-withdraw-modal-min-label]');
    var availableLabel = modal.querySelector('[data-withdraw-modal-available-label]');

    var readMeta = function (source, key, fallback) {
      if (!source || typeof source.getAttribute !== 'function') {
        return fallback;
      }
      var value = source.getAttribute(key);
      return value !== null && value !== '' ? value : fallback;
    };

    var applyWithdrawalContext = function (source) {
      var fallbackType = readMeta(modal, 'data-withdraw-default-type', 'teacher_sales');
      var nextType = readMeta(source, 'data-withdrawal-type', fallbackType);
      var nextKicker = readMeta(source, 'data-withdrawal-kicker', readMeta(modal, 'data-withdraw-default-kicker', 'Retrait ventes'));
      var nextTitle = readMeta(source, 'data-withdrawal-title', readMeta(modal, 'data-withdraw-default-title', 'Demander un retrait'));
      var nextMin = readMeta(source, 'data-withdrawal-min', readMeta(modal, 'data-withdraw-default-min', '0'));
      var nextAvailable = readMeta(source, 'data-withdrawal-available', readMeta(modal, 'data-withdraw-default-available', '0'));
      var nextMinLabel = readMeta(source, 'data-withdrawal-min-label', readMeta(modal, 'data-withdraw-default-min-label', 'Aucun minimum'));
      var nextAvailableLabel = readMeta(source, 'data-withdrawal-available-label', readMeta(modal, 'data-withdraw-default-available-label', '0 F CFA'));

      if (typeInput instanceof HTMLInputElement) {
        typeInput.value = nextType === 'referral' ? 'referral' : 'teacher_sales';
      }
      if (kicker instanceof HTMLElement) {
        kicker.textContent = nextKicker;
      }
      if (title instanceof HTMLElement) {
        title.textContent = nextTitle;
      }
      if (minLabel instanceof HTMLElement) {
        minLabel.textContent = nextMinLabel;
      }
      if (availableLabel instanceof HTMLElement) {
        availableLabel.textContent = nextAvailableLabel;
      }
      if (amountInput instanceof HTMLInputElement) {
        var minNumber = parseInt(nextMin || '0', 10);
        var availableNumber = parseInt(nextAvailable || '0', 10);
        var safeMin = Number.isFinite(minNumber) ? Math.max(1, minNumber) : 1;
        var safeMax = Number.isFinite(availableNumber) ? Math.max(availableNumber, safeMin) : safeMin;
        amountInput.min = String(safeMin);
        amountInput.max = String(safeMax);
      }
    };

    openButtons.forEach(function (button) {
      if (button.dataset.withdrawOpenBound === '1') {
        return;
      }

      button.addEventListener('click', function (event) {
        event.preventDefault();
        applyWithdrawalContext(button);
        if (amountInput instanceof HTMLInputElement) {
          if (button.dataset.withdrawalAmount) {
            amountInput.value = button.dataset.withdrawalAmount;
          } else if (button.dataset.withdrawalClear === '1') {
            amountInput.value = '';
          }
        }
        if (methodSelect instanceof HTMLSelectElement && button.dataset.withdrawalMethod) {
          methodSelect.value = button.dataset.withdrawalMethod;
        } else if (methodSelect instanceof HTMLSelectElement && button.dataset.withdrawalClear === '1') {
          methodSelect.value = 'fedapay';
        }
        openModal(modal);
      });

      button.dataset.withdrawOpenBound = '1';
    });

    closeButtons.forEach(function (button) {
      if (button.dataset.withdrawCloseBound === '1') {
        return;
      }

      button.addEventListener('click', function (event) {
        event.preventDefault();
        closeModal(modal);
      });

      button.dataset.withdrawCloseBound = '1';
    });

    if (modal.dataset.withdrawModalBound !== '1') {
      modal.addEventListener('click', function (event) {
        if (event.target === modal) {
          closeModal(modal);
        }
      });

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
          closeModal(modal);
        }
      });

      modal.dataset.withdrawModalBound = '1';
    }

    if (form instanceof HTMLFormElement) {
      form.addEventListener('submit', function () {
        var submitButton = form.querySelector('button[type="submit"]');
        if (submitButton instanceof HTMLButtonElement) {
          submitButton.disabled = true;
          submitButton.classList.add('opacity-80');
        }
      });
    }

    if (modal.dataset.withdrawModalOpen === '1') {
      applyWithdrawalContext(modal);
      openModal(modal);
    }
  }

  function bindCancelConfirmations() {
    toArray(document.querySelectorAll('form[data-confirm-withdraw-cancel="1"]')).forEach(function (form) {
      if (form.dataset.cancelBound === '1') {
        return;
      }

      form.addEventListener('submit', function (event) {
        var shouldContinue = window.confirm('Annuler cette demande de retrait ?');
        if (!shouldContinue) {
          event.preventDefault();
        }
      });

      form.dataset.cancelBound = '1';
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var storedTheme = getStoredTheme();
    applyTheme(storedTheme === 'dark' || (!storedTheme && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches));
    bindThemeToggles();
    bindWithdrawalModal();
    bindCancelConfirmations();
    bindPeriodForm();
  });
})();
