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

  const init = () => {
    if (!isOrdersPage()) {
      return;
    }

    const searchInputs = Array.from(document.querySelectorAll('[data-orders-search]'));
    const statusFilter = document.getElementById('status-filter');
    const rows = Array.from(document.querySelectorAll('.order-row, .order-card'));
    const noSearchState = document.getElementById('orders-no-search-results');

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

    filterOrders();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
