(function () {
  "use strict";

  const root = document.documentElement;
  const STORAGE_KEY = root.getAttribute("data-theme-storage-key") || "student.notifications.theme.v2";
  const themeButtons = Array.from(document.querySelectorAll("#theme-toggle-sidebar, [data-theme-toggle]"));
  const tabFilters = Array.from(document.querySelectorAll(".tab-filter"));
  const messages = Array.from(document.querySelectorAll(".message"));
  const noMessages = document.getElementById("no-messages");
  const searchInput = document.getElementById("search-notifications");
  const toggleLabel = document.querySelector("#theme-toggle-sidebar span");

  function readStoredTheme() {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored === "dark" || stored === "light") {
        return stored;
      }
    } catch (_error) {
      // Ignore storage access issues and fall back to the DOM default.
    }

    return root.getAttribute("data-default-theme") === "dark" ? "dark" : "light";
  }

  function updateThemeButtons(isDark) {
    themeButtons.forEach((button) => {
      button.setAttribute("aria-pressed", String(isDark));
    });

    if (toggleLabel) {
      toggleLabel.textContent = isDark ? "Mode clair" : "Mode sombre";
    }
  }

  function setTheme(theme) {
    const isDark = theme === "dark";

    root.classList.toggle("dark", isDark);
    root.classList.toggle("light", !isDark);
    root.setAttribute("data-theme", theme);
    root.style.colorScheme = theme;

    try {
      localStorage.setItem(STORAGE_KEY, theme);
    } catch (_error) {
      // Ignore storage errors.
    }

    updateThemeButtons(isDark);
  }

  function toggleTheme() {
    setTheme(root.classList.contains("dark") ? "light" : "dark");
  }

  function getActiveFilter() {
    const active = tabFilters.find((btn) => btn.classList.contains("tab-active"));
    return active?.getAttribute("data-filter") || "all";
  }

  function setActiveFilter(activeBtn) {
    tabFilters.forEach((btn) => {
      const isActive = btn === activeBtn;
      btn.classList.toggle("tab-active", isActive);
      btn.classList.toggle("text-primary-600", isActive);
      btn.classList.toggle("dark:text-primary-400", isActive);
      btn.classList.toggle("font-semibold", isActive);
      btn.classList.toggle("border-primary-600", isActive);
      btn.classList.toggle("dark:border-primary-400", isActive);
      btn.classList.toggle("font-medium", !isActive);
      btn.classList.toggle("text-gray-500", !isActive);
      btn.classList.toggle("dark:text-gray-400", !isActive);
      btn.classList.toggle("border-transparent", !isActive);
      btn.setAttribute("aria-pressed", String(isActive));
    });
  }

  function filterMessages() {
    const query = (searchInput?.value || "").toLowerCase().trim();
    const currentFilter = getActiveFilter();
    let visibleCount = 0;

    messages.forEach((message) => {
      const status = message.getAttribute("data-status") || "all";
      const title = (message.querySelector("[data-message-title]")?.textContent || "").toLowerCase();
      const body = (message.querySelector("[data-message-body]")?.textContent || "").toLowerCase();
      const matchesSearch = title.includes(query) || body.includes(query);
      const matchesFilter = currentFilter === "all" || status === currentFilter;
      const visible = matchesSearch && matchesFilter;

      themeButtons.forEach((button) => {
        button.addEventListener("click", toggleTheme);
      });      
      message.classList.toggle("hidden", !visible);
      if (visible) {
        visibleCount += 1;
      }
    });

    if (noMessages) {
      noMessages.classList.toggle("hidden", visibleCount > 0);
    }
  }

  tabFilters.forEach((button) => {
    button.addEventListener("click", () => {
      setActiveFilter(button);
      filterMessages();
    });
  });

  if (searchInput) {
    searchInput.addEventListener("input", filterMessages);
  }

  filterMessages();
})();
