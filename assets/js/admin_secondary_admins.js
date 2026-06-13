(function () {
  if (window.__ADMIN_SECONDARY_ADMINS_UI_BOUND__) {
    return;
  }
  window.__ADMIN_SECONDARY_ADMINS_UI_BOUND__ = true;

  const SELECTOR_MODAL = ".js-admin-modal";
  const REVOKE_INPUT_ID = "secondary-revoke-user-id";
  const REVOKE_TEXT_ID = "secondary-revoke-text";
  const TOGGLE_SELECTOR = ".js-secondary-toggle";

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

  function setRevokeContext(userId, userName) {
    const userIdInput = document.getElementById(REVOKE_INPUT_ID);
    const text = document.getElementById(REVOKE_TEXT_ID);

    if (userIdInput) {
      userIdInput.value = String(userId || "");
    }
    if (text) {
      text.textContent =
        'Vous allez retirer le rôle administrateur secondaire pour "' +
        String(userName || "cet utilisateur") +
        '".';
    }
  }

  function toggleDetails(button) {
    if (!button) return;
    const targetId =
      button.getAttribute("data-toggle-target") ||
      button.getAttribute("aria-controls");
    if (!targetId) return;
    const panel = document.getElementById(targetId);
    if (!panel) return;

    const isHidden = panel.classList.contains("hidden");
    if (isHidden) {
      panel.classList.remove("hidden");
    } else {
      panel.classList.add("hidden");
    }

    const expanded = isHidden;
    button.setAttribute("aria-expanded", expanded ? "true" : "false");

    const icon = button.querySelector(".js-secondary-toggle-icon");
    if (
      icon &&
      (icon.classList.contains("fa-chevron-down") ||
        icon.classList.contains("fa-chevron-up"))
    ) {
      icon.classList.toggle("rotate-180", expanded);
    }
    button.classList.toggle("is-open", expanded);

    const label = button.querySelector(".js-secondary-toggle-label");
    if (label) {
      label.textContent = expanded
        ? button.getAttribute("data-label-open") || "Masquer"
        : button.getAttribute("data-label-closed") || "Détails";
    }
  }

  function normalizeText(value) {
    return String(value || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .trim();
  }

  function setupAdminFilters() {
    const searchInput = document.getElementById("secondary-admin-search");
    const roleFilter = document.getElementById("secondary-role-filter");
    const cards = Array.from(document.querySelectorAll(".js-secondary-admin-card"));
    const emptyState = document.getElementById("secondary-admin-empty-state");

    if (!cards.length && !searchInput && !roleFilter) {
      return;
    }

    function applyFilters() {
      const search = normalizeText(searchInput ? searchInput.value : "");
      const role = String(roleFilter ? roleFilter.value : "all");
      let visibleCount = 0;

      cards.forEach(function (card) {
        if (!(card instanceof HTMLElement)) return;

        const haystack = normalizeText(
          (card.dataset.adminName || "") + " " + (card.dataset.adminEmail || "")
        );
        const cardRole = String(card.dataset.adminRole || "");
        const matchesSearch = search === "" || haystack.includes(search);
        const matchesRole = role === "all" || cardRole === role;
        const shouldShow = matchesSearch && matchesRole;

        card.classList.toggle("hidden", !shouldShow);
        if (shouldShow) {
          visibleCount += 1;
        }
      });

      if (emptyState) {
        emptyState.classList.toggle("hidden", visibleCount > 0);
      }
    }

    if (searchInput) {
      searchInput.addEventListener("input", applyFilters);
    }
    if (roleFilter) {
      roleFilter.addEventListener("change", applyFilters);
    }

    applyFilters();
  }

  document.addEventListener("click", function (event) {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const toggleButton = target.closest(TOGGLE_SELECTOR);
    if (toggleButton) {
      event.preventDefault();
      toggleDetails(toggleButton);
      return;
    }

    const revokeButton = target.closest(".js-secondary-revoke");
    if (revokeButton) {
      event.preventDefault();
      setRevokeContext(
        revokeButton.getAttribute("data-user-id"),
        revokeButton.getAttribute("data-user-name")
      );
      openModal("secondary-revoke-modal");
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

  setupAdminFilters();

  const modalToOpen = String(document.body?.dataset.secondaryOpenModal || "").trim();
  if (modalToOpen !== "") {
    openModal(modalToOpen);
  }
})();
