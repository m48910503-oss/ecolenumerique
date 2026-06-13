(function () {
  if (window.__ADMIN_ANNOUNCEMENTS_UI_BOUND__) {
    return;
  }
  window.__ADMIN_ANNOUNCEMENTS_UI_BOUND__ = true;

  const pageRoot = document.querySelector('[data-view="admin/announcements"]');
  if (!pageRoot) {
    return;
  }

  const searchForm = document.querySelector('[data-search-form]');
  const showSearchButtons = document.querySelectorAll('[data-search-toggle="show"]');
  const hideSearchButtons = document.querySelectorAll('[data-search-toggle="hide"]');

  const MODAL_SELECTOR = '.js-admin-modal';
  const DELETE_MODAL_ID = 'announcement-delete-modal';
  const deleteIdInput = document.getElementById('announcement-delete-id');
  const deleteMessage = document.getElementById('announcement-delete-message');

  function showSearch() {
    if (!searchForm) return;
    searchForm.classList.remove('hidden');
  }

  function hideSearch() {
    if (!searchForm) return;
    searchForm.classList.add('hidden');
  }

  function getModal(id) {
    if (!id) return null;
    return document.getElementById(String(id));
  }

  function openModal(id) {
    const modal = getModal(id);
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal(id) {
    const modal = getModal(id);
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
  }

  function closeAllModals() {
    const modals = document.querySelectorAll(MODAL_SELECTOR);
    modals.forEach((modal) => {
      if (!(modal instanceof HTMLElement)) return;
      modal.classList.add('hidden');
      modal.classList.remove('flex');
      modal.setAttribute('aria-hidden', 'true');
    });
  }

  function setDeleteContext(id, title) {
    if (deleteIdInput) {
      deleteIdInput.value = String(id || '');
    }
    if (deleteMessage) {
      const safeTitle = String(title || '').trim();
      deleteMessage.textContent = safeTitle
        ? `Supprimer l'annonce "${safeTitle}" ?`
        : "Supprimer cette annonce ?";
    }
  }

  showSearchButtons.forEach((button) => {
    button.addEventListener('click', showSearch);
  });

  hideSearchButtons.forEach((button) => {
    button.addEventListener('click', hideSearch);
  });

  if (searchForm && searchForm.dataset.searchOpen === '1') {
    showSearch();
  }

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const deleteButton = target.closest('[data-announcement-delete-id]');
    if (deleteButton) {
      event.preventDefault();
      setDeleteContext(
        deleteButton.getAttribute('data-announcement-delete-id'),
        deleteButton.getAttribute('data-announcement-delete-title')
      );
      openModal(DELETE_MODAL_ID);
      return;
    }

    const closeButton = target.closest('.js-modal-close');
    if (closeButton) {
      event.preventDefault();
      const modalId = closeButton.getAttribute('data-modal-target');
      if (modalId) {
        closeModal(modalId);
      } else {
        const parentModal = closeButton.closest(MODAL_SELECTOR);
        if (parentModal && parentModal.id) {
          closeModal(parentModal.id);
        }
      }
      return;
    }

    const modalBackdrop = target.closest(MODAL_SELECTOR);
    if (modalBackdrop && target === modalBackdrop && modalBackdrop.id) {
      closeModal(modalBackdrop.id);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    closeAllModals();
  });
})();
