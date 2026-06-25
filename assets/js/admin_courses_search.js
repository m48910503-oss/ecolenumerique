(function () {
  "use strict";

  function normalize(value) {
    return String(value || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .trim();
  }

  function initFilterTabs() {
    var tabs = document.querySelectorAll("[data-course-status-tab]");
    var cards = document.querySelectorAll("[data-course-card]");
    var statusHost = document.querySelector("[data-course-current-status]");
    var searchInput = document.getElementById("admin-courses-inline-search");
    var categoryFilter = document.getElementById("admin-courses-category-filter");
    var resetButton = document.getElementById("admin-courses-filter-reset");
    var emptyState = document.getElementById("admin-courses-empty-state");
    var countTarget = document.getElementById("admin-courses-filter-count");
    var statusTarget = document.getElementById("admin-courses-filter-status");
    var activeStatus = String(
      (statusHost && statusHost.getAttribute("data-course-current-status")) ||
      (tabs[0] && tabs[0].getAttribute("data-course-status-tab")) ||
      (cards[0] && cards[0].getAttribute("data-status")) ||
      "submitted"
    );
    var statusLabels = {
      submitted: "En attente",
      approved: "Approuvés"
    };

    if (!cards.length && !countTarget && !statusTarget) {
      return;
    }

    function updateTabState(selectedTab) {
      tabs.forEach(function (tab) {
        var isActive = tab === selectedTab;
        tab.classList.toggle("text-primary-600", isActive);
        tab.classList.toggle("border-primary-600", isActive);
        tab.classList.toggle("dark:text-primary-400", isActive);
        tab.classList.toggle("dark:border-primary-400", isActive);
        tab.classList.toggle("text-gray-500", !isActive);
        tab.classList.toggle("dark:text-gray-400", !isActive);
        tab.classList.toggle("border-transparent", !isActive);
        tab.setAttribute("aria-pressed", isActive ? "true" : "false");
      });
    }

    function applyFilters() {
      var query = normalize(searchInput ? searchInput.value : "");
      var category = normalize(categoryFilter ? categoryFilter.value : "");
      var visibleCount = 0;

      cards.forEach(function (card) {
        var status = String(card.getAttribute("data-status") || "");
        var cardCategory = normalize(card.getAttribute("data-category") || "");
        var haystack = normalize(card.getAttribute("data-search") || "");
        var statusMatches = activeStatus === "" || status === activeStatus;
        var categoryMatches = category === "" || cardCategory === category;
        var searchMatches = query === "" || haystack.indexOf(query) !== -1;
        var visible = statusMatches && categoryMatches && searchMatches;

        card.classList.toggle("hidden", !visible);
        if (visible) {
          visibleCount += 1;
        }
      });

      if (emptyState) {
        emptyState.classList.toggle("hidden", visibleCount > 0);
      }

      if (countTarget) {
        countTarget.textContent = visibleCount + " cours visible" + (visibleCount === 1 ? "" : "s");
      }

      if (statusTarget) {
        statusTarget.textContent = statusLabels[activeStatus] || "Cours";
      }
    }

    tabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        activeStatus = String(tab.getAttribute("data-course-status-tab") || "submitted");
        updateTabState(tab);
        applyFilters();
      });
    });

    if (searchInput) {
      searchInput.addEventListener("input", applyFilters);
    }

    if (categoryFilter) {
      categoryFilter.addEventListener("change", applyFilters);
    }

    if (resetButton) {
      resetButton.addEventListener("click", function () {
        if (searchInput) {
          searchInput.value = "";
        }
        if (categoryFilter) {
          categoryFilter.value = "";
        }
        applyFilters();
      });
    }

    if (tabs.length) {
      var initialTab = Array.prototype.find.call(tabs, function (tab) {
        return String(tab.getAttribute("data-course-status-tab") || "") === activeStatus;
      }) || tabs[0];
      updateTabState(initialTab);
    }
    applyFilters();
  }

  function initRejectModal() {
    var modal = document.getElementById("admin-course-reject-modal");
    var actionInput = document.getElementById("admin-course-reject-action");
    var courseIdInput = document.getElementById("admin-course-reject-id");
    var headingTarget = document.getElementById("admin-course-reject-heading");
    var titleTarget = document.getElementById("admin-course-reject-title");
    var noteTarget = document.getElementById("admin-course-reject-note");
    var customReason = document.getElementById("admin-course-reject-custom");
    var submitButton = document.getElementById("admin-course-reject-submit");

    if (!modal || !courseIdInput) {
      return;
    }

    function closeModal() {
      modal.classList.add("hidden");
      modal.classList.remove("flex");
      if (actionInput) {
        actionInput.value = "reject";
      }
      courseIdInput.value = "";
      if (headingTarget) {
        headingTarget.textContent = "Motif du rejet";
      }
      if (titleTarget) {
        titleTarget.textContent = "";
      }
      if (noteTarget) {
        noteTarget.textContent = "L’enseignant sera notifié avec ce motif.";
      }
      if (customReason) {
        customReason.value = "";
        customReason.placeholder = "Qualité insuffisante, contenu incomplet, problème de licence...";
      }
      if (submitButton) {
        submitButton.textContent = "Confirmer le rejet";
      }
    }

    document.querySelectorAll("[data-course-moderation]").forEach(function (button) {
      button.addEventListener("click", function () {
        courseIdInput.value = String(button.getAttribute("data-course-id") || "");
        if (actionInput) {
          actionInput.value = String(button.getAttribute("data-course-action") || "reject");
        }
        if (headingTarget) {
          headingTarget.textContent = String(button.getAttribute("data-course-modal-title") || "Motif du rejet");
        }
        if (titleTarget) {
          titleTarget.textContent = String(button.getAttribute("data-course-title") || "");
        }
        if (noteTarget) {
          noteTarget.textContent = String(button.getAttribute("data-course-modal-note") || "L’enseignant sera notifié avec ce motif.");
        }
        if (submitButton) {
          submitButton.textContent = String(button.getAttribute("data-course-modal-submit") || "Confirmer");
        }
        modal.classList.remove("hidden");
        modal.classList.add("flex");
        if (customReason) {
          customReason.placeholder = String(button.getAttribute("data-course-modal-placeholder") || "Précisez le motif...");
          customReason.focus();
        }
      });
    });

    modal.querySelectorAll("[data-course-reject-close]").forEach(function (button) {
      button.addEventListener("click", closeModal);
    });

    modal.addEventListener("click", function (event) {
      if (event.target === modal) {
        closeModal();
      }
    });
  }

  function init() {
    initFilterTabs();
    initRejectModal();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
