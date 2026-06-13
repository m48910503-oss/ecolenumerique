(() => {
  'use strict';

  const normalizeText = (value) => {
    const text = String(value || '').trim();
    if (!text) {
      return '';
    }

    const lower = text.toLowerCase();
    if (typeof lower.normalize === 'function') {
      return lower.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    return lower;
  };

  const notify = (message, type = 'info') => {
    if (typeof window.appShowToast === 'function') {
      window.appShowToast(message, type);
      return;
    }

    if (typeof window.showToast === 'function') {
      window.showToast(message, type);
      return;
    }

    window.alert(message);
  };

  const setHidden = (node, hidden) => {
    if (!(node instanceof HTMLElement)) {
      return;
    }

    node.classList.toggle('hidden', hidden);
  };

  document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-view="student/meetings"]');
    if (!(root instanceof HTMLElement)) {
      return;
    }

    const cards = Array.from(root.querySelectorAll('[data-meeting-card]'));
    const tabs = Array.from(root.querySelectorAll('[data-meeting-tab]'));
    const searchForm = root.querySelector('.student-topbar-search');
    const searchInput = searchForm instanceof HTMLFormElement
      ? searchForm.querySelector('input[type="search"]')
      : null;
    const noMatchState = root.querySelector('[data-meetings-no-match]');
    const resetButtons = Array.from(root.querySelectorAll('[data-meetings-reset]'));
    const detailButtons = Array.from(root.querySelectorAll('[data-meeting-action="details"]'));
    const placeButtons = Array.from(root.querySelectorAll('[data-meeting-action="place"]'));
    const modal = root.querySelector('[data-onsite-disclaimer-modal]');
    const laterButton = modal ? modal.querySelector('[data-onsite-disclaimer-later]') : null;
    const ackButton = modal ? modal.querySelector('[data-onsite-disclaimer-ack]') : null;
    const userId = String(root.dataset.userId || '').trim();
    const storageKey = userId !== '' ? `onsite_disclaimer_ack_u${userId}` : '';

    let activeFilter = tabs.find((tab) => tab.classList.contains('tab-active'))?.dataset.meetingTab
      || tabs.find((tab) => !(tab instanceof HTMLButtonElement) || !tab.disabled)?.dataset.meetingTab
      || 'all';

    const updateTabs = () => {
      tabs.forEach((tab) => {
        const isDisabled = tab instanceof HTMLButtonElement ? tab.disabled : tab.dataset.meetingTabDisabled === '1';
        const isActive = (tab.dataset.meetingTab || 'all') === activeFilter;
        tab.classList.toggle('tab-active', isActive);
        tab.classList.toggle('text-blue-600', isActive);
        tab.classList.toggle('dark:text-blue-400', isActive);
        tab.classList.toggle('font-semibold', isActive);
        tab.classList.toggle('border-blue-600', isActive);
        tab.classList.toggle('dark:border-blue-400', isActive);
        tab.classList.toggle('text-gray-500', !isActive && !isDisabled);
        tab.classList.toggle('dark:text-gray-400', !isActive && !isDisabled);
        tab.classList.toggle('font-medium', !isActive);
        tab.classList.toggle('border-transparent', !isActive);
        tab.classList.toggle('tab-filter--disabled', isDisabled);
        tab.classList.toggle('text-gray-300', isDisabled && !isActive);
        tab.classList.toggle('dark:text-gray-600', isDisabled && !isActive);
        tab.classList.toggle('cursor-not-allowed', isDisabled);
        tab.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
    };

    const getQuery = () => normalizeText(searchInput instanceof HTMLInputElement ? searchInput.value : '');

    const applyFilters = () => {
      const query = getQuery();
      let visibleCount = 0;

      cards.forEach((card) => {
        const status = String(card.dataset.status || 'upcoming');
        const searchable = String(card.dataset.search || '');
        const matchesFilter = activeFilter === 'all' || status === activeFilter;
        const matchesSearch = query === '' || searchable.includes(query);
        const visible = matchesFilter && matchesSearch;
        card.classList.toggle('hidden', !visible);
        if (visible) {
          visibleCount += 1;
        }
      });

      if (cards.length === 0) {
        setHidden(noMatchState, true);
        return;
      }

      setHidden(noMatchState, visibleCount > 0);
    };

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        if ((tab instanceof HTMLButtonElement && tab.disabled) || tab.dataset.meetingTabDisabled === '1') {
          return;
        }
        activeFilter = tab.dataset.meetingTab || 'all';
        updateTabs();
        applyFilters();
      });
    });

    if (searchInput instanceof HTMLInputElement) {
      searchInput.addEventListener('input', applyFilters);
    }

    if (searchForm instanceof HTMLFormElement) {
      searchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        applyFilters();
      });
    }

    resetButtons.forEach((button) => {
      button.addEventListener('click', () => {
        activeFilter = tabs.find((tab) => (tab.dataset.meetingTab || '') === 'all' && !(tab instanceof HTMLButtonElement && tab.disabled))?.dataset.meetingTab
          || tabs.find((tab) => (tab.dataset.meetingTab || '') === 'upcoming' && !(tab instanceof HTMLButtonElement && tab.disabled))?.dataset.meetingTab
          || tabs.find((tab) => (tab.dataset.meetingTab || '') === 'past' && !(tab instanceof HTMLButtonElement && tab.disabled))?.dataset.meetingTab
          || 'all';
        if (searchInput instanceof HTMLInputElement) {
          searchInput.value = '';
          searchInput.focus();
        }
        updateTabs();
        applyFilters();
      });
    });

    detailButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const summary = String(button.dataset.meetingSummary || '').trim();
        notify(summary !== '' ? summary : 'Détails du rendez-vous.', 'info');
      });
    });

    placeButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const place = String(button.dataset.meetingPlace || '').trim();
        const title = String(button.dataset.meetingTitle || 'Rendez-vous').trim();
        notify(
          place !== ''
            ? `Lieu du rendez-vous pour "${title}" : ${place}`
            : `Lieu indisponible pour "${title}".`,
          'info'
        );
      });
    });

    if (modal instanceof HTMLElement && storageKey !== '') {
      const showModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      };

      const hideModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      };

      let acknowledged = false;
      try {
        acknowledged = window.localStorage.getItem(storageKey) === '1';
      } catch (_error) {
        acknowledged = false;
      }

      if (!acknowledged) {
        showModal();
      }

      if (laterButton instanceof HTMLElement) {
        laterButton.addEventListener('click', hideModal);
      }

      if (ackButton instanceof HTMLElement) {
        ackButton.addEventListener('click', () => {
          try {
            window.localStorage.setItem(storageKey, '1');
          } catch (_error) {
            /* Stockage local indisponible. */
          }
          hideModal();
        });
      }
    }

    updateTabs();
    applyFilters();
  });
})();
