(function () {
  if (window.__ADMIN_CATEGORIES_UI_BOUND__) {
    return;
  }
  window.__ADMIN_CATEGORIES_UI_BOUND__ = true;

  const SELECTOR_MODAL = ".js-admin-modal";
  const EDIT_MODAL_ID = "category-edit-modal";
  const DELETE_MODAL_ID = "delete-modal";

  const editIdInput = document.getElementById("category-edit-id");
  const editNameInput = document.getElementById("category-edit-name");
  const editSlugInput = document.getElementById("category-edit-slug");
  const editActiveInput = document.getElementById("category-edit-active");
  const deleteIdInput = document.getElementById("delete-id");
  const deleteMsg = document.getElementById("delete-message");
  const searchInput = document.getElementById("admin-category-search");
  const statusFilter = document.getElementById("admin-category-status-filter");
  const emptyState = document.getElementById("admin-category-empty");
  const categoryRows = Array.from(
    document.querySelectorAll("[data-category-row]")
  ).filter(function (row) {
    return row instanceof HTMLElement;
  });

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

  function normalize(value) {
    return String(value || "")
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase()
      .trim();
  }

  function setEditContext(id, name, slug, active) {
    if (editIdInput) {
      editIdInput.value = String(id || "");
    }
    if (editNameInput) {
      editNameInput.value = String(name || "");
    }
    if (editSlugInput) {
      editSlugInput.value = String(slug || "");
    }
    if (editActiveInput) {
      editActiveInput.checked = String(active || "0") === "1";
    }
  }

  function setDeleteContext(id, name) {
    if (deleteIdInput) {
      deleteIdInput.value = String(id || "");
    }
    if (deleteMsg) {
      const safeName = String(name || "").trim();
      deleteMsg.textContent = safeName
        ? `Supprimer la cat\u00e9gorie "${safeName}" ?`
        : "Supprimer cette cat\u00e9gorie ?";
    }
  }

  document.addEventListener("click", function (event) {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const editButton = target.closest(".js-category-edit");
    if (editButton) {
      event.preventDefault();
      setEditContext(
        editButton.getAttribute("data-edit-id"),
        editButton.getAttribute("data-edit-name"),
        editButton.getAttribute("data-edit-slug"),
        editButton.getAttribute("data-edit-active")
      );
      openModal(EDIT_MODAL_ID);
      return;
    }

    const deleteButton = target.closest("[data-delete-id]");
    if (deleteButton) {
      event.preventDefault();
      setDeleteContext(
        deleteButton.getAttribute("data-delete-id"),
        deleteButton.getAttribute("data-delete-name")
      );
      openModal(DELETE_MODAL_ID);
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

  const modalToOpen = String(
    document.body?.dataset.categoryOpenModal || ""
  ).trim();
  if (modalToOpen !== "") {
    if (modalToOpen === EDIT_MODAL_ID) {
      setEditContext(
        document.body?.dataset.categoryEditId || "",
        document.body?.dataset.categoryEditName || "",
        document.body?.dataset.categoryEditSlug || "",
        document.body?.dataset.categoryEditActive || "1"
      );
    }
    openModal(modalToOpen);
  }

  function applyCategoryFilters() {
    if (categoryRows.length === 0) {
      if (emptyState) {
        emptyState.classList.remove("hidden");
      }
      return;
    }

    const query = normalize(searchInput ? searchInput.value : "");
    const selectedStatus = String(statusFilter ? statusFilter.value : "all");
    let visibleCount = 0;

    categoryRows.forEach(function (row) {
      if (!(row instanceof HTMLElement)) return;
      const haystack = normalize(
        `${row.dataset.categoryName || ""} ${row.dataset.categorySlug || ""}`
      );
      const rowStatus = String(row.dataset.categoryStatus || "active");
      const matchesQuery = query === "" || haystack.includes(query);
      const matchesStatus =
        selectedStatus === "all" || rowStatus === selectedStatus;
      const isVisible = matchesQuery && matchesStatus;

      row.hidden = !isVisible;
      if (isVisible) {
        visibleCount += 1;
      }
    });

    if (emptyState) {
      emptyState.classList.toggle("hidden", visibleCount > 0);
    }
  }

  if (searchInput) {
    searchInput.addEventListener("input", applyCategoryFilters);
  }

  if (statusFilter) {
    statusFilter.addEventListener("change", applyCategoryFilters);
  }

  applyCategoryFilters();
})();
