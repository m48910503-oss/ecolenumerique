(function () {
  "use strict";

  var storageKey = "theme";
  var scrollTopState = {
    button: null,
    initialized: false,
    root: null
  };

  function getStoredTheme() {
    try {
      return localStorage.getItem(storageKey);
    } catch (_error) {
      return null;
    }
  }

  function setStoredTheme(value) {
    try {
      localStorage.setItem(storageKey, value);
    } catch (_error) {
      /* Storage unavailable. */
    }
  }

  function themeIcon(isDark) {
    if (isDark) {
      return '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3v2.1M12 18.9V21M4.57 4.57l1.49 1.49M17.94 17.94l1.49 1.49M3 12h2.1M18.9 12H21M4.57 19.43l1.49-1.49M17.94 6.06l1.49-1.49" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12 16.2a4.2 4.2 0 1 0 0-8.4 4.2 4.2 0 0 0 0 8.4Z" stroke="currentColor" stroke-width="1.8"/></svg>';
    }

    return '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20.1 14.6A7.2 7.2 0 0 1 9.4 3.9 8.9 8.9 0 1 0 20.1 14.6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>';
  }

  function applyTheme(isDark) {
    var html = document.documentElement;
    html.classList.toggle("dark", isDark);
    html.classList.toggle("light", !isDark);
    html.setAttribute("data-theme", isDark ? "dark" : "light");
    html.style.colorScheme = isDark ? "dark" : "light";
    setStoredTheme(isDark ? "dark" : "light");

    document.querySelectorAll("[data-theme-toggle], #theme-toggle, #theme-toggle-sidebar").forEach(function (button) {
      button.setAttribute("aria-pressed", isDark ? "true" : "false");
      button.setAttribute("title", isDark ? "Passer au mode clair" : "Passer au mode sombre");

      var label = button.querySelector("[data-theme-label]");
      if (label) {
        label.innerHTML = themeIcon(isDark);
      }
    });
  }

  function toggleTheme() {
    applyTheme(!document.documentElement.classList.contains("dark"));
  }

  function syncToolbarOffset() {
    var body = document.body;
    if (!body || !body.matches(".app-role-admin[data-view]")) {
      return;
    }

    var toolbar = document.querySelector(".app-shell-toolbar.admin-shell-toolbar");
    if (!toolbar) {
      return;
    }

    var height = Math.ceil(toolbar.getBoundingClientRect().height);
    if (height > 0) {
      body.style.setProperty("--admin-topbar-offset-desktop", height + "px");
      body.style.setProperty("--admin-topbar-offset-mobile", height + "px");
      body.style.setProperty("--app-toolbar-offset-current", height + "px");
    }
  }

  function bindToolbarOffset() {
    syncToolbarOffset();
    window.addEventListener("load", syncToolbarOffset, { once: true });
    window.addEventListener("resize", syncToolbarOffset, { passive: true });
    window.addEventListener("orientationchange", syncToolbarOffset, { passive: true });

    if ("ResizeObserver" in window) {
      var toolbar = document.querySelector(".app-shell-toolbar.admin-shell-toolbar");
      if (toolbar) {
        new ResizeObserver(syncToolbarOffset).observe(toolbar);
      }
    }
  }

  function getScrollTopRoot() {
    var candidate = document.querySelector("[data-scroll-top-root]");
    return candidate instanceof HTMLElement ? candidate : null;
  }

  function updateScrollTopButtonVisibility() {
    var button = scrollTopState.button;
    if (!(button instanceof HTMLElement)) {
      return;
    }

    var root = scrollTopState.root instanceof HTMLElement ? scrollTopState.root : null;
    var doc = document.documentElement;
    var body = document.body;
    var scrollTop = root ? root.scrollTop : (window.scrollY || doc.scrollTop || body.scrollTop || 0);
    var viewportHeight = root ? root.clientHeight : (window.innerHeight || doc.clientHeight || 0);
    var documentHeight = root ? root.scrollHeight : Math.max(
      doc.scrollHeight || 0,
      body.scrollHeight || 0,
      doc.offsetHeight || 0,
      body.offsetHeight || 0
    );
    var showButton = documentHeight > viewportHeight + 240 && scrollTop >= Math.max(120, Math.round(viewportHeight * 0.18));

    button.classList.toggle("is-visible", showButton);
    button.setAttribute("aria-hidden", showButton ? "false" : "true");
  }

  function ensureScrollTopButton() {
    if (!document.body) {
      return;
    }

    var button = document.querySelector("[data-scroll-top]");
    if (!(button instanceof HTMLElement)) {
      button = document.createElement("button");
      button.type = "button";
      button.className = "web-scroll-top";
      button.setAttribute("data-scroll-top", "1");
      button.setAttribute("aria-label", "Remonter en haut de la page");
      button.setAttribute("aria-hidden", "true");
      button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 14 6-6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      document.body.appendChild(button);
    }

    scrollTopState.button = button;
    scrollTopState.root = getScrollTopRoot();

    if (button.dataset.scrollTopBound !== "1") {
      button.addEventListener("click", function (event) {
        event.preventDefault();
        if (scrollTopState.root instanceof HTMLElement) {
          scrollTopState.root.scrollTo({ top: 0, behavior: "smooth" });
          return;
        }
        window.scrollTo({ top: 0, behavior: "smooth" });
      });
      button.dataset.scrollTopBound = "1";
    }

    if (!scrollTopState.initialized) {
      if (scrollTopState.root instanceof HTMLElement) {
        scrollTopState.root.addEventListener("scroll", updateScrollTopButtonVisibility, { passive: true });
      } else {
        window.addEventListener("scroll", updateScrollTopButtonVisibility, { passive: true });
      }

      window.addEventListener("resize", updateScrollTopButtonVisibility, { passive: true });
      window.addEventListener("load", updateScrollTopButtonVisibility, { passive: true });
      scrollTopState.initialized = true;
    }

    updateScrollTopButtonVisibility();
  }

  function bindThemeToggles() {
    document.querySelectorAll("[data-theme-toggle], #theme-toggle, #theme-toggle-sidebar").forEach(function (button) {
      if (button.dataset.themeBound === "1") {
        return;
      }
      button.addEventListener("click", function (event) {
        event.preventDefault();
        toggleTheme();
      });
      button.dataset.themeBound = "1";
    });
  }

  function bindConfirmForms() {
    document.querySelectorAll("form[data-confirm-message]").forEach(function (form) {
      if (form.dataset.confirmBound === "1") {
        return;
      }
      form.addEventListener("submit", function (event) {
        var message = (form.dataset.confirmMessage || "").trim();
        if (message !== "" && !window.confirm(message)) {
          event.preventDefault();
        }
      });
      form.dataset.confirmBound = "1";
    });
  }

  function bindUserMenus() {
    var openUserMenuSelector = ".app-user-menu[open], [data-dashboard-user-menu][open]";

    document.addEventListener("click", function (event) {
      document.querySelectorAll(openUserMenuSelector).forEach(function (menu) {
        if (!menu.contains(event.target)) {
          menu.removeAttribute("open");
        }
      });
    });

    document.addEventListener("keydown", function (event) {
      if (event.key !== "Escape") {
        return;
      }
      document.querySelectorAll(openUserMenuSelector).forEach(function (menu) {
        menu.removeAttribute("open");
      });
    });
  }

  function init() {
    var storedTheme = getStoredTheme();
    applyTheme(storedTheme === "dark" || (!storedTheme && window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches));

    bindThemeToggles();
    bindToolbarOffset();
    bindConfirmForms();
    bindUserMenus();
    ensureScrollTopButton();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
