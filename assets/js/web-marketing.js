(() => {
  const onReady = () => {
    initPublicAnnouncementBar();
    initPublicHeader();
    initPublicUserMenus();
    initTextRepair();
    initPromoCountdowns();
    initCatalogue();
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", onReady, { once: true });
  } else {
    onReady();
  }

  function initPublicHeader() {
    const toggle = document.querySelector("[data-public-nav-toggle]");
    const panel = document.querySelector("[data-public-nav-panel]");

    if (!(toggle instanceof HTMLButtonElement) || !(panel instanceof HTMLElement)) {
      return;
    }

    const mobileQuery = window.matchMedia("(max-width: 980px)");
    const closeButtons = Array.from(panel.querySelectorAll("[data-public-nav-close]")).filter((node) => node instanceof HTMLButtonElement);
    const panelCloseButton = panel.querySelector("[data-public-nav-close]:not([data-public-nav-backdrop])");
    const panelSheet = panel.querySelector(".public-mobile-panel__sheet");
    const toggleBarsIcon = toggle.querySelector("[data-public-nav-icon-bars]");
    const toggleCloseIcon = toggle.querySelector("[data-public-nav-icon-close]");
    const toggleLabel = toggle.querySelector("[data-public-nav-label]");
    let lastFocusedElement = null;

    const updateToggleVisualState = (open) => {
      toggle.classList.toggle("is-open", open);
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
      toggle.setAttribute("aria-label", open ? "Fermer la navigation" : "Ouvrir la navigation");

      if (toggleLabel instanceof HTMLElement) {
        toggleLabel.textContent = open ? "Fermer la navigation" : "Ouvrir la navigation";
      }

      if (toggleBarsIcon instanceof HTMLElement) {
        toggleBarsIcon.classList.toggle("hidden", open);
      }

      if (toggleCloseIcon instanceof HTMLElement) {
        toggleCloseIcon.classList.toggle("hidden", !open);
      }
    };

    const setOpen = (open, options = {}) => {
      const shouldOpen = open && mobileQuery.matches;
      const restoreFocus = options.restoreFocus === true;

      if (shouldOpen) {
        lastFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
      }

      panel.classList.toggle("is-open", shouldOpen);
      panel.setAttribute("aria-hidden", shouldOpen ? "false" : "true");
      document.body.classList.toggle("public-nav-open", shouldOpen);
      document.documentElement.classList.toggle("public-nav-open", shouldOpen);
      updateToggleVisualState(shouldOpen);

      if (!shouldOpen) {
        if (restoreFocus) {
          const nextFocus = lastFocusedElement instanceof HTMLElement && document.contains(lastFocusedElement)
            ? lastFocusedElement
            : toggle;
          if (nextFocus instanceof HTMLElement) {
            nextFocus.focus({ preventScroll: true });
          }
        }
        return;
      }

      window.requestAnimationFrame(() => {
        if (!panel.classList.contains("is-open")) {
          return;
        }

        const closeButton = panelCloseButton instanceof HTMLElement ? panelCloseButton : closeButtons[0];
        if (closeButton instanceof HTMLElement) {
          closeButton.focus({ preventScroll: true });
        } else if (panelSheet instanceof HTMLElement) {
          panelSheet.focus({ preventScroll: true });
        }
        });
      };

    toggle.addEventListener("click", () => {
      if (!mobileQuery.matches) {
        return;
      }

      setOpen(!panel.classList.contains("is-open"), { restoreFocus: true });
    });

    closeButtons.forEach((button) => {
      button.addEventListener("click", () => {
        if (!panel.classList.contains("is-open")) {
          return;
        }

        setOpen(false, { restoreFocus: true });
      });
    });

    panel.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => {
        if (!mobileQuery.matches) {
          return;
        }

        setOpen(false);
      });
    });

    document.addEventListener("click", (event) => {
      if (!mobileQuery.matches || !panel.classList.contains("is-open")) {
        return;
      }

      const target = event.target;
      if (!(target instanceof Node)) {
        return;
      }

      if (panel.contains(target) || toggle.contains(target)) {
        return;
      }

      setOpen(false, { restoreFocus: true });
    });

    document.addEventListener("keydown", (event) => {
      if (event.key !== "Escape" || !panel.classList.contains("is-open")) {
        return;
      }

      event.preventDefault();
      setOpen(false, { restoreFocus: true });
    });

    if (typeof mobileQuery.addEventListener === "function") {
      mobileQuery.addEventListener("change", () => {
        if (!mobileQuery.matches) {
          setOpen(false);
          return;
        }

        updateToggleVisualState(panel.classList.contains("is-open"));
      });
    } else if (typeof mobileQuery.addListener === "function") {
      mobileQuery.addListener(() => {
        if (!mobileQuery.matches) {
          setOpen(false);
          return;
        }

        updateToggleVisualState(panel.classList.contains("is-open"));
      });
    }

    if (!mobileQuery.matches) {
      setOpen(false);
      return;
    }

    updateToggleVisualState(panel.classList.contains("is-open"));
    panel.setAttribute("aria-hidden", panel.classList.contains("is-open") ? "false" : "true");
  }

  function initPublicUserMenus() {
    const menus = Array.from(document.querySelectorAll("[data-public-user-menu]")).filter(
      (node) => node instanceof HTMLElement && node.tagName === "DETAILS"
    );

    if (menus.length === 0) {
      return;
    }

    const closeOpenMenus = () => {
      menus.forEach((menu) => {
        if (menu.open) {
          menu.open = false;
        }
      });
    };

    document.addEventListener("click", (event) => {
      if (menus.every((menu) => !menu.open)) {
        return;
      }

      const target = event.target;
      if (!(target instanceof Node)) {
        return;
      }

      if (menus.some((menu) => menu.contains(target))) {
        return;
      }

      closeOpenMenus();
    });

    document.addEventListener("keydown", (event) => {
      if (event.key !== "Escape") {
        return;
      }

      closeOpenMenus();
    });
  }

  function initPublicAnnouncementBar() {
    const bar = document.querySelector("[data-public-announcement]");
    if (!(bar instanceof HTMLElement)) {
      return;
    }

    const closeButton = bar.querySelector("[data-public-announcement-close]");
    const announcementId = Number.parseInt(bar.dataset.announcementId || "0", 10);
    const storageKey = Number.isFinite(announcementId) && announcementId > 0
      ? `mk-public-announcement-dismissed:${announcementId}`
      : "";

    const canUseStorage = () => {
      try {
        return typeof window.localStorage !== "undefined";
      } catch (_error) {
        return false;
      }
    };

    const hideBar = () => {
      bar.hidden = true;
    };

    if (storageKey !== "" && canUseStorage()) {
      try {
        if (window.localStorage.getItem(storageKey) === "1") {
          hideBar();
          return;
        }
      } catch (_error) {
        // Ignore storage access issues.
      }
    }

    if (!(closeButton instanceof HTMLButtonElement)) {
      return;
    }

    closeButton.addEventListener("click", () => {
      if (storageKey !== "" && canUseStorage()) {
        try {
          window.localStorage.setItem(storageKey, "1");
        } catch (_error) {
          // Ignore storage access issues.
        }
      }
      hideBar();
    });
  }

  function initPromoCountdowns() {
    const selector = "[data-promo-countdown]";
    const pad = (value) => String(Math.max(0, value)).padStart(2, "0");
    const parseDeadline = (rawValue) => {
      const value = String(rawValue || "").trim();
      if (value === "") {
        return Number.NaN;
      }

      const normalized = value.includes("T") ? value : value.replace(" ", "T");
      const timestamp = Date.parse(normalized);
      return Number.isFinite(timestamp) ? timestamp : Number.NaN;
    };
    const formatRemaining = (remainingMs) => {
      const totalSeconds = Math.max(0, Math.floor(remainingMs / 1000));
      const days = Math.floor(totalSeconds / 86400);
      const hours = Math.floor((totalSeconds % 86400) / 3600);
      const minutes = Math.floor((totalSeconds % 3600) / 60);
      const seconds = totalSeconds % 60;

      if (days > 0) {
        return `${days} j ${pad(hours)} h ${pad(minutes)} min`;
      }
      if (hours > 0) {
        return `${hours} h ${pad(minutes)} min`;
      }
      if (minutes > 0) {
        return `${minutes} min ${pad(seconds)} s`;
      }
      return `${seconds} s`;
    };
    const updateNode = (node) => {
      const deadline = parseDeadline(node.dataset.promoCountdown);
      if (!Number.isFinite(deadline)) {
        node.hidden = true;
        return;
      }

      const remainingMs = deadline - Date.now();
      node.hidden = false;
      node.textContent = remainingMs > 0
        ? `Fin dans ${formatRemaining(remainingMs)}`
        : "Promotion termin\u00e9e";
    };
    const updateAll = () => {
      document.querySelectorAll(selector).forEach((node) => {
        if (node instanceof HTMLElement) {
          updateNode(node);
        }
      });
    };

    updateAll();
    window.setInterval(updateAll, 1000);
  }

  function initTextRepair() {
    if (!(document.body instanceof HTMLElement) || typeof TextDecoder !== "function") {
      return;
    }

    const mojibakePattern = /[\u00c3\u00c2\u00e2\u0192\ufffd]/;
    const plainFrenchReplacements = new Map([
      ["Debutant", "D\u00e9butant"],
      ["Intermediaire", "Interm\u00e9diaire"],
      ["Avance", "Avanc\u00e9"],
      ["Reinitialiser", "R\u00e9initialiser"],
      ["Categories", "Cat\u00e9gories"],
      ["categorie", "cat\u00e9gorie"],
      ["recents", "r\u00e9cents"],
      ["details", "d\u00e9tails"],
      ["revele", "r\u00e9v\u00e8le"],
      ["reserves", "r\u00e9serv\u00e9s"],
      ["theme", "th\u00e8me"],
      ["École Numérique", "\u00c9cole Num\u00e9rique"],
      ["Creer un compte", "Cr\u00e9er un compte"],
      ["Legal", "L\u00e9gal"],
      ["numeriques", "num\u00e9riques"],
      ["memorables", "m\u00e9morables"],
      ["num?riques", "num\u00e9riques"],
      ["m?morables", "m\u00e9morables"],
    ]);
    const cp1252ExtendedByteByCodePoint = new Map([
      [0x20ac, 0x80],
      [0x201a, 0x82],
      [0x0192, 0x83],
      [0x201e, 0x84],
      [0x2026, 0x85],
      [0x2020, 0x86],
      [0x2021, 0x87],
      [0x02c6, 0x88],
      [0x2030, 0x89],
      [0x0160, 0x8a],
      [0x2039, 0x8b],
      [0x0152, 0x8c],
      [0x017d, 0x8e],
      [0x2018, 0x91],
      [0x2019, 0x92],
      [0x201c, 0x93],
      [0x201d, 0x94],
      [0x2022, 0x95],
      [0x2013, 0x96],
      [0x2014, 0x97],
      [0x02dc, 0x98],
      [0x2122, 0x99],
      [0x0161, 0x9a],
      [0x203a, 0x9b],
      [0x0153, 0x9c],
      [0x017e, 0x9e],
      [0x0178, 0x9f],
    ]);
    const decoder = new TextDecoder("utf-8", { fatal: false });
    const shouldRepair = (value) => mojibakePattern.test(value) || Array.from(plainFrenchReplacements.keys()).some((token) => value.includes(token));
    const mojibakeScore = (value) => (value.match(/[\u00c3\u00c2\u00e2\u0192\ufffd]/g) || []).length;
    const toCp1252Bytes = (value) => {
      const bytes = [];
      for (const char of value) {
        const codePoint = char.codePointAt(0);
        if (typeof codePoint !== "number") {
          return null;
        }
        if (codePoint <= 0xff) {
          bytes.push(codePoint);
          continue;
        }
        const mappedByte = cp1252ExtendedByteByCodePoint.get(codePoint);
        if (typeof mappedByte !== "number") {
          return null;
        }
        bytes.push(mappedByte);
      }
      return Uint8Array.from(bytes);
    };
    const repairTextValue = (value) => {
      let current = String(value || "");
      for (let pass = 0; pass < 3; pass += 1) {
        if (!mojibakePattern.test(current)) {
          break;
        }
        const bytes = toCp1252Bytes(current);
        if (!(bytes instanceof Uint8Array)) {
          break;
        }
        const candidate = decoder.decode(bytes);
        if (mojibakeScore(candidate) >= mojibakeScore(current)) {
          break;
        }
        current = candidate;
      }
      plainFrenchReplacements.forEach((replacement, search) => {
        current = current.replaceAll(search, replacement);
      });
      return current;
    };
    const repairElementAttributes = (element) => {
      ["placeholder", "title", "aria-label"].forEach((attributeName) => {
        const raw = element.getAttribute(attributeName);
        if (typeof raw !== "string" || !shouldRepair(raw)) {
          return;
        }
        const repaired = repairTextValue(raw);
        if (repaired !== raw) {
          element.setAttribute(attributeName, repaired);
        }
      });
    };
    const repairNode = (node) => {
      if (node instanceof Text) {
        const parentName = node.parentElement ? node.parentElement.tagName : "";
        if (parentName === "SCRIPT" || parentName === "STYLE") {
          return;
        }
        const raw = node.nodeValue || "";
        if (!shouldRepair(raw)) {
          return;
        }
        const repaired = repairTextValue(raw);
        if (repaired !== raw) {
          node.nodeValue = repaired;
        }
        return;
      }
      if (!(node instanceof HTMLElement)) {
        return;
      }
      repairElementAttributes(node);
      const walker = document.createTreeWalker(node, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT);
      while (walker.nextNode()) {
        const currentNode = walker.currentNode;
        if (currentNode instanceof HTMLElement) {
          repairElementAttributes(currentNode);
        } else if (currentNode instanceof Text) {
          repairNode(currentNode);
        }
      }
    };

    repairNode(document.body);
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type === "characterData") {
          repairNode(mutation.target);
          return;
        }
        mutation.addedNodes.forEach((node) => repairNode(node));
      });
    });
    observer.observe(document.body, { childList: true, subtree: true, characterData: true });
  }

  function initCatalogue() {
    const root = document.querySelector("[data-catalog-root]");
    if (!(root instanceof HTMLElement)) {
      return;
    }

    const grid = root.querySelector("[data-catalog-grid]");
    const cards = Array.from(root.querySelectorAll("[data-course-card]")).filter((card) => card instanceof HTMLElement);
    const searchInput = document.querySelector("[data-catalog-search]");
    const categorySelect = root.querySelector('[data-filter-field="category"]');
    const levelSelect = root.querySelector('[data-filter-field="level"]');
    const priceSelect = root.querySelector('[data-filter-field="price"]');
    const durationSelect = root.querySelector('[data-filter-field="duration"]');
    const sortSelect = root.querySelector("[data-sort-control]");
    const resetButton = root.querySelector("[data-filter-reset]");
    const filterToggle = root.querySelector("[data-filter-toggle]");
    const filterPanel = root.querySelector("[data-filter-panel]");
    const activeFiltersHost = root.querySelector("[data-active-filters]");
    const resultCountNodes = Array.from(root.querySelectorAll("[data-result-count]")).filter((node) => node instanceof HTMLElement);
    const activeCountNodes = Array.from(root.querySelectorAll("[data-active-filter-count]")).filter((node) => node instanceof HTMLElement);
    const emptyState = root.querySelector("[data-empty-state]");
    const quickCategoryButtons = Array.from(document.querySelectorAll("[data-quick-category]")).filter((button) => button instanceof HTMLButtonElement);

    if (!(grid instanceof HTMLElement) || cards.length === 0) {
      return;
    }

    const mobileQuery = window.matchMedia("(max-width: 980px)");
    const numberFormatter = new Intl.NumberFormat("fr-FR");

    const normalizeText = (value) =>
      String(value || "")
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .trim();

    const setSelectValue = (select, value) => {
      if (!(select instanceof HTMLSelectElement)) {
        return;
      }

      const hasOption = Array.from(select.options).some((option) => option.value === value);
      select.value = hasOption ? value : "";
    };

    const params = new URLSearchParams(window.location.search);
    if (searchInput instanceof HTMLInputElement) {
      searchInput.value = params.get("q") || "";
    }
    setSelectValue(categorySelect, params.get("category") || "");
    setSelectValue(levelSelect, params.get("level") || "");
    setSelectValue(priceSelect, params.get("price") || "");
    setSelectValue(durationSelect, params.get("duration") || "");
    setSelectValue(sortSelect, params.get("sort") || "recent");

    const getState = () => ({
      searchRaw: searchInput instanceof HTMLInputElement ? searchInput.value.trim() : "",
      search: searchInput instanceof HTMLInputElement ? normalizeText(searchInput.value) : "",
      category: categorySelect instanceof HTMLSelectElement ? categorySelect.value : "",
      level: levelSelect instanceof HTMLSelectElement ? levelSelect.value : "",
      price: priceSelect instanceof HTMLSelectElement ? priceSelect.value : "",
      duration: durationSelect instanceof HTMLSelectElement ? durationSelect.value : "",
      sort: sortSelect instanceof HTMLSelectElement ? sortSelect.value : "recent",
    });

    const parseNumber = (value) => {
      const parsed = Number.parseFloat(String(value || "0"));
      return Number.isFinite(parsed) ? parsed : 0;
    };

    const getActiveFilters = (state) => {
      const active = [];

      if (state.searchRaw !== "") {
        active.push({ key: "search", label: `Recherche : ${state.searchRaw}` });
      }
      if (state.category !== "" && categorySelect instanceof HTMLSelectElement) {
        const option = categorySelect.selectedOptions[0];
        active.push({ key: "category", label: `CatÃ©gorie : ${option ? option.textContent.replace(/\s+\(\d+\)$/, "") : state.category}` });
      }
      if (state.level !== "" && levelSelect instanceof HTMLSelectElement) {
        const option = levelSelect.selectedOptions[0];
        active.push({ key: "level", label: `Niveau : ${option ? option.textContent : state.level}` });
      }
      if (state.price !== "") {
        active.push({ key: "price", label: state.price === "free" ? "Prix : Gratuit" : "Prix : Payant" });
      }
      if (state.duration !== "") {
        const durationLabelMap = {
          short: "DurÃ©e : moins de 3 h",
          medium: "DurÃ©e : entre 3 h et 8 h",
          long: "DurÃ©e : plus de 8 h",
        };
        active.push({ key: "duration", label: durationLabelMap[state.duration] || `DurÃ©e : ${state.duration}` });
      }

      return active;
    };

    const syncUrl = (state) => {
      const nextParams = new URLSearchParams();

      if (state.searchRaw !== "") nextParams.set("q", state.searchRaw);
      if (state.category !== "") nextParams.set("category", state.category);
      if (state.level !== "") nextParams.set("level", state.level);
      if (state.price !== "") nextParams.set("price", state.price);
      if (state.duration !== "") nextParams.set("duration", state.duration);
      if (state.sort !== "" && state.sort !== "recent") nextParams.set("sort", state.sort);

      const query = nextParams.toString();
      const nextUrl = query ? `${window.location.pathname}?${query}` : window.location.pathname;
      window.history.replaceState({}, "", nextUrl);
    };

    const sortCards = (state) => {
      const sorted = cards.slice().sort((cardA, cardB) => {
        const ratingA = parseNumber(cardA.dataset.rating);
        const ratingB = parseNumber(cardB.dataset.rating);
        const priceA = parseNumber(cardA.dataset.price);
        const priceB = parseNumber(cardB.dataset.price);
        const createdA = parseNumber(cardA.dataset.created);
        const createdB = parseNumber(cardB.dataset.created);

        if (state.sort === "rating") {
          return ratingB - ratingA || createdB - createdA;
        }
        if (state.sort === "price_asc") {
          return priceA - priceB || ratingB - ratingA;
        }
        if (state.sort === "price_desc") {
          return priceB - priceA || ratingB - ratingA;
        }
        return createdB - createdA || ratingB - ratingA;
      });

      sorted.forEach((card) => grid.appendChild(card));
    };

    const cardMatches = (card, state) => {
      const searchText = normalizeText(card.dataset.search || "");
      const category = card.dataset.category || "";
      const level = card.dataset.level || "";
      const price = parseNumber(card.dataset.price);
      const duration = parseNumber(card.dataset.duration);

      const matchesSearch = state.search === "" || searchText.includes(state.search);
      const matchesCategory = state.category === "" || category === state.category;
      const matchesLevel = state.level === "" || level === state.level;
      const matchesPrice = state.price === "" || (state.price === "free" ? price <= 0 : price > 0);

      let matchesDuration = true;
      if (state.duration === "short") {
        matchesDuration = duration > 0 && duration < 180;
      } else if (state.duration === "medium") {
        matchesDuration = duration >= 180 && duration <= 480;
      } else if (state.duration === "long") {
        matchesDuration = duration > 480;
      }

      return matchesSearch && matchesCategory && matchesLevel && matchesPrice && matchesDuration;
    };

    const renderActiveFilters = (state) => {
      if (!(activeFiltersHost instanceof HTMLElement)) {
        return;
      }

      const activeFilters = getActiveFilters(state);
      activeFiltersHost.innerHTML = "";

      activeFilters.forEach((filter) => {
        const button = document.createElement("button");
        button.type = "button";
        button.dataset.clearFilter = filter.key;
        button.innerHTML = `${escapeHtml(filter.label)} <span>Retirer</span>`;
        activeFiltersHost.appendChild(button);
      });
    };

    const updateQuickCategories = (state) => {
      quickCategoryButtons.forEach((button) => {
        button.setAttribute("aria-pressed", button.dataset.quickCategory === state.category ? "true" : "false");
      });
    };

    const syncFilterPanel = () => {
      if (!(filterPanel instanceof HTMLElement) || !(filterToggle instanceof HTMLButtonElement)) {
        return;
      }

      if (!mobileQuery.matches) {
        filterPanel.classList.remove("is-collapsible", "is-open");
        filterToggle.setAttribute("aria-expanded", "false");
        return;
      }

      filterPanel.classList.add("is-collapsible");
      filterToggle.setAttribute("aria-expanded", filterPanel.classList.contains("is-open") ? "true" : "false");
    };

    const refresh = () => {
      const state = getState();
      sortCards(state);

      let visibleCount = 0;
      cards.forEach((card) => {
        const matches = cardMatches(card, state);
        card.hidden = !matches;
        if (matches) {
          visibleCount += 1;
        }
      });

      resultCountNodes.forEach((node) => {
        node.textContent = numberFormatter.format(visibleCount);
      });

      const activeFilters = getActiveFilters(state);
      activeCountNodes.forEach((node) => {
        node.textContent = numberFormatter.format(activeFilters.length);
        node.hidden = activeFilters.length === 0;
      });

      if (emptyState instanceof HTMLElement) {
        emptyState.hidden = visibleCount !== 0;
      }

      renderActiveFilters(state);
      updateQuickCategories(state);
      syncUrl(state);
    };

    searchInput?.addEventListener("input", refresh);
    categorySelect?.addEventListener("change", refresh);
    levelSelect?.addEventListener("change", refresh);
    priceSelect?.addEventListener("change", refresh);
    durationSelect?.addEventListener("change", refresh);
    sortSelect?.addEventListener("change", refresh);

    resetButton?.addEventListener("click", () => {
      if (searchInput instanceof HTMLInputElement) searchInput.value = "";
      if (categorySelect instanceof HTMLSelectElement) categorySelect.value = "";
      if (levelSelect instanceof HTMLSelectElement) levelSelect.value = "";
      if (priceSelect instanceof HTMLSelectElement) priceSelect.value = "";
      if (durationSelect instanceof HTMLSelectElement) durationSelect.value = "";
      if (sortSelect instanceof HTMLSelectElement) sortSelect.value = "recent";
      refresh();
    });

    quickCategoryButtons.forEach((button) => {
      button.addEventListener("click", () => {
        if (!(categorySelect instanceof HTMLSelectElement)) {
          return;
        }

        const nextValue = button.dataset.quickCategory || "";
        categorySelect.value = categorySelect.value === nextValue ? "" : nextValue;
        refresh();
      });
    });

    activeFiltersHost?.addEventListener("click", (event) => {
      const target = event.target;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      const button = target.closest("[data-clear-filter]");
      if (!(button instanceof HTMLButtonElement)) {
        return;
      }

      const key = button.dataset.clearFilter;
      if (key === "search" && searchInput instanceof HTMLInputElement) searchInput.value = "";
      if (key === "category" && categorySelect instanceof HTMLSelectElement) categorySelect.value = "";
      if (key === "level" && levelSelect instanceof HTMLSelectElement) levelSelect.value = "";
      if (key === "price" && priceSelect instanceof HTMLSelectElement) priceSelect.value = "";
      if (key === "duration" && durationSelect instanceof HTMLSelectElement) durationSelect.value = "";
      refresh();
    });

    filterToggle?.addEventListener("click", () => {
      if (!(filterToggle instanceof HTMLButtonElement) || !(filterPanel instanceof HTMLElement) || !mobileQuery.matches) {
        return;
      }

      filterPanel.classList.toggle("is-open");
      syncFilterPanel();
    });

    if (typeof mobileQuery.addEventListener === "function") {
      mobileQuery.addEventListener("change", syncFilterPanel);
    } else if (typeof mobileQuery.addListener === "function") {
      mobileQuery.addListener(syncFilterPanel);
    }

    syncFilterPanel();
    refresh();
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }
})();
