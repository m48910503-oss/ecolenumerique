(function () {
  "use strict";

  var root = document.querySelector("[data-student-courses-page]");
  if (!root) {
    return;
  }

  var searchInput = root.querySelector("[data-course-search]");
  var stateInputs = {
    filter: root.querySelector('[data-course-state="filter"]'),
    sort: root.querySelector('[data-course-state="sort"]'),
    layout: root.querySelector('[data-course-state="layout"]')
  };
  var courseGrid = root.querySelector("[data-course-grid]");
  var courseCards = Array.prototype.slice.call(root.querySelectorAll("[data-course-card]"));
  var filterButtons = Array.prototype.slice.call(root.querySelectorAll("[data-course-filter]"));
  var viewButtons = Array.prototype.slice.call(root.querySelectorAll("[data-course-view]"));
  var sortSelect = root.querySelector("[data-course-sort]");
  var resetButtons = Array.prototype.slice.call(root.querySelectorAll("[data-course-reset]"));
  var noMatchState = root.querySelector("[data-courses-no-match]");
  var modal = document.querySelector("#course-modal");
  var modalCover = modal ? modal.querySelector("#course-modal-cover") : null;
  var modalStatus = modal ? modal.querySelector("#course-modal-status") : null;
  var modalLevel = modal ? modal.querySelector("#course-modal-level") : null;
  var modalTitle = modal ? modal.querySelector("#course-modal-title") : null;
  var modalMeta = modal ? modal.querySelector("#course-modal-meta") : null;
  var modalProgressText = modal ? modal.querySelector("#course-modal-progress-text") : null;
  var modalProgressBar = modal ? modal.querySelector("#course-modal-progress-bar") : null;
  var modalLessons = modal ? modal.querySelector("#course-modal-lessons") : null;
  var modalEnrolled = modal ? modal.querySelector("#course-modal-enrolled") : null;
  var modalCta = modal ? modal.querySelector("#course-modal-cta") : null;
  var modalRating = modal ? modal.querySelector("#course-modal-rating") : null;
  var modalCloseButtons = modal ? Array.prototype.slice.call(modal.querySelectorAll("[data-course-modal-close]")) : [];

  if (!courseGrid || courseCards.length === 0) {
    return;
  }

  var classNames = {
    viewActive: ["bg-white", "dark:bg-gray-600", "text-gray-900", "dark:text-white", "shadow-sm"],
    viewInactive: ["text-gray-500", "dark:text-gray-400"],
    tabActive: ["tab-active", "text-primary-600", "dark:text-primary-400", "font-semibold", "border-primary-600", "dark:border-primary-400"],
    tabInactive: ["font-medium", "text-gray-500", "dark:text-gray-400", "hover:text-gray-900", "dark:hover:text-white", "hover:border-gray-300"]
  };

  var state = {
    filter: "all",
    sort: "recent",
    layout: courseGrid.dataset.layout === "list" ? "list" : "grid",
    query: searchInput ? searchInput.value || "" : ""
  };

  var normalize = function (value) {
    var text = String(value || "").trim().toLowerCase();
    if (text === "") {
      return "";
    }

    try {
      return text.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    } catch (_error) {
      return text;
    }
  };

  var updateStateInput = function (key, value) {
    var input = stateInputs[key];
    if (input) {
      input.value = value;
    }
  };

  var setViewButtons = function () {
    viewButtons.forEach(function (button) {
      var isActive = button.dataset.courseView === state.layout;
      classNames.viewActive.forEach(function (className) {
        button.classList.toggle(className, isActive);
      });
      classNames.viewInactive.forEach(function (className) {
        button.classList.toggle(className, !isActive);
      });
      button.setAttribute("aria-pressed", isActive ? "true" : "false");
    });
  };

  var setFilterButtons = function () {
    filterButtons.forEach(function (button) {
      var isActive = button.dataset.courseFilter === state.filter;
      classNames.tabActive.forEach(function (className) {
        button.classList.toggle(className, isActive);
      });
      classNames.tabInactive.forEach(function (className) {
        button.classList.toggle(className, !isActive);
      });
      button.setAttribute("aria-pressed", isActive ? "true" : "false");
    });
  };

  var applyLayout = function () {
    courseGrid.dataset.layout = state.layout;

    if (state.layout === "list") {
      courseGrid.classList.remove("gap-5", "sm:grid-cols-2", "lg:grid-cols-3");
      courseGrid.classList.add("gap-4", "grid-cols-1");
    } else {
      courseGrid.classList.remove("gap-4");
      courseGrid.classList.add("gap-5", "grid-cols-1", "sm:grid-cols-2", "lg:grid-cols-3");
    }

    updateStateInput("layout", state.layout);
    setViewButtons();
  };

  var sortCards = function () {
    var sortedCards = courseCards.slice().sort(function (a, b) {
      var aProgress = Number(a.dataset.progress || 0);
      var bProgress = Number(b.dataset.progress || 0);
      var aRecent = Number(a.dataset.sortRecent || 0);
      var bRecent = Number(b.dataset.sortRecent || 0);
      var aAlpha = normalize(a.dataset.sortAlpha || a.dataset.title || "");
      var bAlpha = normalize(b.dataset.sortAlpha || b.dataset.title || "");

      if (state.sort === "progress-asc") {
        if (aProgress !== bProgress) {
          return aProgress - bProgress;
        }
      } else if (state.sort === "progress-desc") {
        if (aProgress !== bProgress) {
          return bProgress - aProgress;
        }
      } else if (state.sort === "alpha") {
        var alphaCompare = aAlpha.localeCompare(bAlpha);
        if (alphaCompare !== 0) {
          return alphaCompare;
        }
      } else if (aRecent !== bRecent) {
        return bRecent - aRecent;
      }

      if (aRecent !== bRecent) {
        return bRecent - aRecent;
      }

      if (aProgress !== bProgress) {
        return bProgress - aProgress;
      }

      return aAlpha.localeCompare(bAlpha);
    });

    sortedCards.forEach(function (card) {
      courseGrid.appendChild(card);
    });
  };

  var getVisibleMatchCount = function () {
    var query = normalize(state.query);
    var count = 0;

    courseCards.forEach(function (card) {
      var status = card.dataset.status || "all";
      var matchesFilter = state.filter === "all" || status === state.filter;
      var searchText = normalize(card.dataset.searchText || card.dataset.title || "");
      var matchesSearch = query === "" || searchText.indexOf(query) !== -1;
      var visible = matchesFilter && matchesSearch;

      card.classList.toggle("hidden", !visible);
      if (visible) {
        count++;
      }
    });

    return count;
  };

  var applyFilters = function () {
    var visibleCount = getVisibleMatchCount();
    if (noMatchState) {
      noMatchState.classList.toggle("hidden", visibleCount > 0);
    }
  };

  var render = function () {
    sortCards();
    applyLayout();
    applyFilters();
    updateStateInput("filter", state.filter);
    updateStateInput("sort", state.sort);
  };

  var resetState = function () {
    state.filter = "all";
    state.sort = "recent";
    state.layout = "grid";
    state.query = "";

    if (searchInput) {
      searchInput.value = "";
    }
    if (sortSelect) {
      sortSelect.value = "recent";
    }

    render();
    setFilterButtons();
  };

  var openModal = function (card) {
    if (!modal) {
      return;
    }

    var title = card.dataset.title || "Cours";
    var courseId = card.dataset.courseId || "";
    var coverUrl = card.dataset.coverUrl || "";
    var coverAlt = card.dataset.coverAlt || title;
    var status = card.dataset.status || "in-progress";
    var statusLabel = card.dataset.statusLabel || "En cours";
    var statusBadgeClass = card.dataset.statusBadgeClass || "course-status-badge course-status-badge--in-progress";
    var levelLabel = card.dataset.levelLabel || "Niveau";
    var progress = Math.max(0, Math.min(100, Number(card.dataset.progress || 0)));
    var doneLessons = card.dataset.doneLessons || "0";
    var totalLessons = card.dataset.totalLessons || "0";
    var lessonProgressLabel = card.dataset.lessonProgressLabel || "";
    var remainingLabel = card.dataset.remainingLabel || "";
    var enrolledLabel = card.dataset.enrolledLabel || "-";
    var courseUrl = card.dataset.courseUrl || "#";
    var ctaLabel = card.dataset.ctaLabel || "Continuer";
    var ctaFullLabel = card.dataset.ctaFullLabel || (ctaLabel + " le cours");
    var ctaIcon = card.dataset.ctaIcon || "fa-play";
    var ctaClass = card.dataset.ctaClass || "course-action-btn course-action-btn--in-progress";
    var canRate = card.dataset.canRate !== "0";
    var ratingValue = Number(card.dataset.ratingValue || 0);
    var ratingComment = card.dataset.ratingComment || "";
    var ratingLabel = ratingValue > 0 ? "Modifier l'avis" : "Noter le cours";
    var safeCoverUrl = coverUrl ? encodeURI(coverUrl) : "";
    var lessonWord = Number(totalLessons) === 1 ? "leçon" : "leçons";
    if (lessonProgressLabel === "") {
      lessonProgressLabel = doneLessons + " sur " + totalLessons + " " + lessonWord;
    }
    var progressBarTone = status === "completed" ? "bg-green-500" : (status === "not-started" ? "bg-amber-500" : (status === "archived" ? "bg-slate-400" : "bg-blue-600"));

    if (modalStatus) {
      modalStatus.className = statusBadgeClass;
      modalStatus.textContent = statusLabel;
    }
    if (modalLevel) {
      modalLevel.textContent = levelLabel;
    }
    if (modalTitle) {
      modalTitle.textContent = title;
    }
    if (modalMeta) {
      modalMeta.textContent = lessonProgressLabel + " · " + remainingLabel;
    }
    if (modalProgressText) {
      modalProgressText.textContent = progress + "%";
    }
    if (modalProgressBar) {
      modalProgressBar.className = "h-2 rounded-full " + progressBarTone;
      modalProgressBar.style.width = progress + "%";
    }
    if (modalLessons) {
      modalLessons.textContent = lessonProgressLabel;
    }
    if (modalEnrolled) {
      modalEnrolled.textContent = enrolledLabel;
    }
    if (modalCta) {
      modalCta.className = ctaClass + " course-modal-primary-action";
      modalCta.href = courseUrl;
      modalCta.textContent = "";

      var icon = document.createElement("i");
      icon.className = "fas " + ctaIcon;
      icon.setAttribute("aria-hidden", "true");

      var label = document.createElement("span");
      label.textContent = ctaFullLabel;

      modalCta.appendChild(icon);
      modalCta.appendChild(label);
    }
    if (modalRating) {
      modalRating.classList.toggle("hidden", !canRate);
      modalRating.dataset.courseId = courseId;
      modalRating.dataset.title = title;
      modalRating.dataset.ratingValue = ratingValue > 0 ? String(ratingValue) : "";
      modalRating.dataset.ratingComment = ratingComment;
      modalRating.setAttribute("aria-label", ratingLabel + " pour " + title);
      var ratingLabelNode = modalRating.querySelector("[data-rating-trigger-label]");
      if (ratingLabelNode) {
        ratingLabelNode.textContent = ratingLabel;
      }
    }
    if (modalCover) {
      if (coverUrl) {
        modalCover.style.backgroundImage = "linear-gradient(135deg, rgba(37, 99, 235, 0.75), rgba(6, 182, 212, 0.65)), url('" + safeCoverUrl + "')";
        modalCover.style.backgroundSize = "cover";
        modalCover.style.backgroundPosition = "center";
        modalCover.setAttribute("aria-label", coverAlt);
      } else {
        modalCover.style.backgroundImage = "linear-gradient(135deg, #2563eb 0%, #3b82f6 45%, #06b6d4 100%)";
        modalCover.removeAttribute("aria-label");
      }
    }

    modal.classList.remove("hidden");
    modal.classList.add("flex");
    modal.setAttribute("aria-hidden", "false");
  };

  var closeModal = function () {
    if (!modal) {
      return;
    }

    modal.classList.add("hidden");
    modal.classList.remove("flex");
    modal.setAttribute("aria-hidden", "true");
  };

  if (searchInput) {
    searchInput.addEventListener("input", function () {
      state.query = searchInput.value || "";
      applyFilters();
    });
  }

  if (sortSelect) {
    sortSelect.addEventListener("change", function () {
      state.sort = sortSelect.value || "recent";
      updateStateInput("sort", state.sort);
      sortCards();
      applyFilters();
    });
  }

  filterButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      state.filter = button.dataset.courseFilter || "all";
      setFilterButtons();
      updateStateInput("filter", state.filter);
      applyFilters();
    });
  });

  viewButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      state.layout = button.dataset.courseView === "list" ? "list" : "grid";
      applyLayout();
    });
  });

  resetButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      resetState();
    });
  });

  root.addEventListener("click", function (event) {
    if (!(event.target instanceof Element)) {
      return;
    }

    var detailsTrigger = event.target.closest("[data-course-open-details]");
    if (!detailsTrigger) {
      return;
    }

    var card = detailsTrigger.closest("[data-course-card]");
    if (!card) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    openModal(card);
  });

  courseCards.forEach(function (card) {
    var detailsButton = card.querySelector("[data-course-open-details]");

    if (detailsButton) {
      detailsButton.addEventListener("click", function (event) {
        event.preventDefault();
        event.stopPropagation();
        openModal(card);
      });
    }

    card.addEventListener("click", function (event) {
      if (!(event.target instanceof Element)) {
        openModal(card);
        return;
      }

      if (event.target.closest("[data-course-open-details]")) {
        event.preventDefault();
        openModal(card);
        return;
      }

      if (event.target.closest("a, button")) {
        return;
      }
      openModal(card);
    });
  });

  modalCloseButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      closeModal();
    });
  });

  if (modal) {
    modal.addEventListener("click", function (event) {
      if (event.target === modal) {
        closeModal();
      }
    });
  }

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      closeModal();
    }
  });

  var activeFilterButton = filterButtons.find(function (button) {
    return button.classList.contains("tab-active");
  });
  if (activeFilterButton && activeFilterButton.dataset.courseFilter) {
    state.filter = activeFilterButton.dataset.courseFilter;
  }
  state.layout = courseGrid.dataset.layout === "list" ? "list" : state.layout;
  state.sort = sortSelect ? sortSelect.value || state.sort : state.sort;
  state.query = searchInput ? searchInput.value || state.query : state.query;

  setFilterButtons();
  applyLayout();
  sortCards();
  applyFilters();
})();
