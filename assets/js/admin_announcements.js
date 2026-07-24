(function () {
  'use strict';

  if (window.__ADMIN_ANNOUNCEMENTS_UI_BOUND__) {
    return;
  }
  window.__ADMIN_ANNOUNCEMENTS_UI_BOUND__ = true;

  const pageRoot = document.querySelector('[data-view="admin/announcements"]');
  if (!pageRoot) {
    return;
  }

  const MODAL_SELECTOR = '.js-admin-modal';
  const detailModal = document.getElementById('announcement-detail-modal');
  const detailModalBody = document.getElementById('announcement-detail-modal-body');
  const detailModalTitle = document.getElementById('announcement-detail-modal-title');
  const deleteModal = document.getElementById('announcement-delete-modal');
  const deleteIdInput = document.getElementById('announcement-delete-id');
  const deleteMessage = document.getElementById('announcement-delete-message');
  const initialOpenId = String(pageRoot.getAttribute('data-announcement-open-id') || '').trim();

  function getModal(modalId) {
    if (!modalId) {
      return null;
    }
    return document.getElementById(String(modalId));
  }

  function openModal(modal) {
    if (!(modal instanceof HTMLElement)) {
      return;
    }
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal(modal) {
    if (!(modal instanceof HTMLElement)) {
      return;
    }
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
  }

  function closeAllModals() {
    document.querySelectorAll(MODAL_SELECTOR).forEach((modal) => {
      if (modal instanceof HTMLElement) {
        closeModal(modal);
      }
    });
  }

  function setDeleteContext(id, title) {
    if (deleteIdInput instanceof HTMLInputElement) {
      deleteIdInput.value = String(id || '');
    }
    if (deleteMessage instanceof HTMLElement) {
      const safeTitle = String(title || '').trim();
      deleteMessage.textContent = safeTitle
        ? `Supprimer l'annonce "${safeTitle}" ?`
        : 'Supprimer cette annonce ?';
    }
  }

  function openDetailAnnouncement(id, title) {
    const content = document.getElementById('announcement-detail-content-' + String(id || ''));
    if (!(detailModalBody instanceof HTMLElement) || !(detailModal instanceof HTMLElement)) {
      return;
    }

    detailModalBody.innerHTML = content instanceof HTMLElement
      ? content.innerHTML
      : '<p class="text-sm text-slate-500 dark:text-slate-400">Détails indisponibles.</p>';

    if (detailModalTitle instanceof HTMLElement) {
      detailModalTitle.textContent = String(title || '').trim() || 'Détails de l’annonce';
    }

    openModal(detailModal);
  }

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) {
      return;
    }

    const detailButton = target.closest('[data-announcement-detail-open]');
    if (detailButton) {
      event.preventDefault();
      openDetailAnnouncement(
        detailButton.getAttribute('data-announcement-detail-open'),
        detailButton.getAttribute('data-announcement-detail-title')
      );
      return;
    }

    const deleteButton = target.closest('[data-announcement-delete-id]');
    if (deleteButton) {
      event.preventDefault();
      setDeleteContext(
        deleteButton.getAttribute('data-announcement-delete-id'),
        deleteButton.getAttribute('data-announcement-delete-title')
      );
      openModal(deleteModal);
      return;
    }

    const closeButton = target.closest('.js-modal-close');
    if (closeButton) {
      event.preventDefault();
      const modalId = closeButton.getAttribute('data-modal-target');
      if (modalId) {
        closeModal(getModal(modalId));
      } else {
        closeModal(closeButton.closest(MODAL_SELECTOR));
      }
      return;
    }

    const modalBackdrop = target.closest(MODAL_SELECTOR);
    if (modalBackdrop && target === modalBackdrop) {
      closeModal(modalBackdrop);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeAllModals();
    }
  });

  if (initialOpenId !== '') {
    window.setTimeout(() => {
      const button = pageRoot.querySelector('[data-announcement-detail-open="' + initialOpenId + '"]');
      if (button instanceof HTMLElement) {
        button.click();
      }
    }, 0);
  }
})();
