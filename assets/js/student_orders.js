(function () {
  'use strict';

  const ORDERS_VIEW = 'student/orders';
  const isOrdersPage = () => document.body?.dataset.view === ORDERS_VIEW;

  const normalizeText = (value) => {
    const text = String(value || '').trim();
    if (text === '') {
      return '';
    }

    const lowered = text.toLowerCase();
    if (typeof lowered.normalize === 'function') {
      return lowered.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    return lowered;
  };

  const normalizeStatus = (value) => {
    const status = normalizeText(value);
    switch (status) {
      case 'paid':
      case 'completed':
      case 'succeeded':
      case 'success':
        return 'paid';
      case 'pending':
        return 'pending';
      case 'failed':
        return 'failed';
      case 'refunded':
        return 'refunded';
      case 'free':
        return 'free';
      case 'cancelled':
      case 'canceled':
        return 'cancelled';
      default:
        return status || 'unknown';
    }
  };

  const notify = (message, type = 'info') => {
    const safeMessage = String(message || '').trim();
    if (safeMessage === '') {
      return;
    }

    if (typeof window.appShowToast === 'function') {
      window.appShowToast(safeMessage, type);
      return;
    }

    if (typeof window.showToast === 'function') {
      window.showToast(safeMessage, type);
      return;
    }

    const normalizedType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
    const existing = document.querySelector('[data-student-orders-toast="1"]');
    if (existing && existing.parentNode) {
      existing.parentNode.removeChild(existing);
    }

    const toast = document.createElement('div');
    toast.dataset.studentOrdersToast = '1';
    toast.className = `teacher-withdrawal-toast teacher-withdrawal-toast--${normalizedType}`;
    toast.setAttribute('role', normalizedType === 'error' ? 'alert' : 'status');
    toast.setAttribute('aria-live', normalizedType === 'error' ? 'assertive' : 'polite');

    const copy = document.createElement('div');
    copy.className = 'teacher-withdrawal-toast__copy';

    const icon = document.createElement('span');
    icon.className = 'teacher-withdrawal-toast__icon';
    icon.setAttribute('aria-hidden', 'true');
    const iconGlyph = document.createElement('i');
    iconGlyph.className = normalizedType === 'success'
      ? 'fas fa-circle-check'
      : (normalizedType === 'error' ? 'fas fa-circle-exclamation' : (normalizedType === 'warning' ? 'fas fa-triangle-exclamation' : 'fas fa-circle-info'));
    icon.appendChild(iconGlyph);

    const text = document.createElement('div');
    const title = document.createElement('p');
    title.className = 'teacher-withdrawal-toast__title';
    title.textContent = normalizedType === 'success'
      ? 'Succès'
      : (normalizedType === 'error' ? 'Erreur' : (normalizedType === 'warning' ? 'Attention' : 'Information'));

    const body = document.createElement('p');
    body.className = 'teacher-withdrawal-toast__message';
    body.textContent = safeMessage;

    text.appendChild(title);
    text.appendChild(body);
    copy.appendChild(icon);
    copy.appendChild(text);

    const bar = document.createElement('span');
    bar.className = 'teacher-withdrawal-toast__bar';
    bar.setAttribute('aria-hidden', 'true');

    toast.appendChild(copy);
    toast.appendChild(bar);
    document.body.appendChild(toast);

    window.setTimeout(() => {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 3600);
  };

  const init = () => {
    if (!isOrdersPage()) {
      return;
    }

    const searchInputs = Array.from(document.querySelectorAll('[data-orders-search]'));
    const statusFilter = document.getElementById('status-filter');
    const rows = Array.from(document.querySelectorAll('.order-row'));
    const noSearchState = document.getElementById('orders-no-search-results');
    const detailButtons = Array.from(document.querySelectorAll('[data-order-detail]'));

    const syncSearchInputs = (source) => {
      if (!(source instanceof HTMLInputElement)) {
        return;
      }

      const value = source.value;
      searchInputs.forEach((input) => {
        if (input !== source && input.value !== value) {
          input.value = value;
        }
      });
    };

    const filterOrders = () => {
      if (rows.length === 0) {
        return;
      }

      const query = normalizeText(searchInputs[0]?.value || '');
      const status = normalizeText(statusFilter instanceof HTMLSelectElement ? statusFilter.value : 'all') || 'all';
      let visible = 0;

      rows.forEach((row) => {
        const haystack = String(row.dataset.search || '');
        const rowStatus = normalizeStatus(row.dataset.status || 'unknown');
        const matchesQuery = query === '' || haystack.includes(query);
        const matchesStatus = status === 'all' || rowStatus === status;
        const show = matchesQuery && matchesStatus;

        row.classList.toggle('hidden', !show);
        if (show) {
          visible += 1;
        }
      });

      if (noSearchState) {
        noSearchState.classList.toggle('hidden', visible !== 0);
      }
    };

    searchInputs.forEach((input) => {
      if (input.dataset.studentOrdersSearchBound === '1') {
        return;
      }

      input.addEventListener('input', () => {
        syncSearchInputs(input);
        filterOrders();
      });

      input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          input.value = '';
          syncSearchInputs(input);
          filterOrders();
          input.blur();
          return;
        }

        if (event.key === 'Enter' && input.form instanceof HTMLFormElement) {
          event.preventDefault();
        }
      });

      input.dataset.studentOrdersSearchBound = '1';
    });

    if (statusFilter instanceof HTMLSelectElement && statusFilter.dataset.studentOrdersFilterBound !== '1') {
      statusFilter.addEventListener('change', filterOrders);
      statusFilter.dataset.studentOrdersFilterBound = '1';
    }

    detailButtons.forEach((button) => {
      if (button.dataset.studentOrdersDetailBound === '1') {
        return;
      }

      button.addEventListener('click', () => {
        notify(button.dataset.orderDetailMessage || 'Détails de la commande.', 'info');
      });
      button.dataset.studentOrdersDetailBound = '1';
    });

    filterOrders();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
