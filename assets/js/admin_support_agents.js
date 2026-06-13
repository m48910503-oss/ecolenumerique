(function () {
  if (window.__ADMIN_SUPPORT_AGENTS_UI_BOUND__) {
    return;
  }
  window.__ADMIN_SUPPORT_AGENTS_UI_BOUND__ = true;

  const SELECTOR_MODAL = ".js-admin-modal";
  const REMOVE_INPUT_ID = "support-remove-user-id";

  function getModal(id) {
    if (!id) return null;
    return document.getElementById(String(id));
  }

  function openModal(id) {
    const modal = getModal(id);
    if (!modal) return;
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    modal.setAttribute("aria-hidden", "false");
  }

  function closeModal(id) {
    const modal = getModal(id);
    if (!modal) return;
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    modal.setAttribute("aria-hidden", "true");
  }

  function closeAllModals() {
    const modals = document.querySelectorAll(SELECTOR_MODAL);
    modals.forEach(function (modal) {
      if (!(modal instanceof HTMLElement)) return;
      modal.classList.add("hidden");
      modal.classList.remove("flex");
      modal.setAttribute("aria-hidden", "true");
    });
  }

  function normalizeText(value) {
    return String(value || "")
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase()
      .trim();
  }

  function applyAgentFilters() {
    const searchInput = document.getElementById("support-agent-search");
    const statusFilter = document.getElementById("support-agent-status-filter");
    const emptyState = document.getElementById("support-agent-empty-state");
    const cards = Array.from(document.querySelectorAll(".js-support-agent-card"));

    if (!cards.length) return;

    const query = normalizeText(searchInput && "value" in searchInput ? searchInput.value : "");
    const status = String(statusFilter && "value" in statusFilter ? statusFilter.value : "").trim();
    let visibleCount = 0;

    cards.forEach(function (card) {
      if (!(card instanceof HTMLElement)) return;
      const cardSearch = normalizeText(card.dataset.agentSearch || card.textContent || "");
      const cardStatus = String(card.dataset.agentStatus || "");
      const matchesSearch = query === "" || cardSearch.includes(query);
      const matchesStatus = status === "" || cardStatus === status;
      const shouldShow = matchesSearch && matchesStatus;

      card.classList.toggle("hidden", !shouldShow);
      if (shouldShow) {
        visibleCount += 1;
      }
    });

    if (emptyState instanceof HTMLElement) {
      emptyState.classList.toggle("hidden", visibleCount > 0);
    }
  }

  document.addEventListener("click", function (event) {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const removeButton = target.closest(".js-support-remove");
    if (removeButton) {
      event.preventDefault();
      const removeInput = document.getElementById(REMOVE_INPUT_ID);
      if (removeInput) {
        removeInput.value = String(removeButton.getAttribute("data-user-id") || "");
      }
      openModal("support-remove-modal");
      return;
    }

    const openButton = target.closest(".js-modal-open");
    if (openButton) {
      event.preventDefault();
      const modalId = openButton.getAttribute("data-modal-target");
      if (modalId) {
        openModal(modalId);
      }
      return;
    }

    const closeButton = target.closest(".js-modal-close");
    if (closeButton) {
      event.preventDefault();
      const modalId = closeButton.getAttribute("data-modal-target");
      if (modalId) {
        closeModal(modalId);
      } else {
        const parentModal = closeButton.closest(SELECTOR_MODAL);
        if (parentModal && parentModal.id) {
          closeModal(parentModal.id);
        }
      }
      return;
    }

    const modalBackdrop = target.closest(SELECTOR_MODAL);
    if (modalBackdrop && target === modalBackdrop && modalBackdrop.id) {
      closeModal(modalBackdrop.id);
    }
  });

  document.addEventListener("keydown", function (event) {
    if (event.key !== "Escape") return;
    closeAllModals();
  });

  const searchInput = document.getElementById("support-agent-search");
  const statusFilter = document.getElementById("support-agent-status-filter");
  if (searchInput) {
    searchInput.addEventListener("input", applyAgentFilters);
  }
  if (statusFilter) {
    statusFilter.addEventListener("change", applyAgentFilters);
  }
  applyAgentFilters();

  const modalToOpen = String(document.body?.dataset.supportOpenModal || "").trim();
  if (modalToOpen !== "") {
    openModal(modalToOpen);
  }
})();
