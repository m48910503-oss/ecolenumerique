(function () {
  "use strict";

  var root = document.documentElement;
  var storageKey = "theme";
  var theme = "light";

  if (root) {
    var pageStorageKey = root.getAttribute("data-theme-storage-key");
    if (pageStorageKey) {
      storageKey = pageStorageKey;
    }

    var defaultTheme = root.getAttribute("data-default-theme");
    if (defaultTheme === "light" || defaultTheme === "dark") {
      theme = defaultTheme;
    }
  }

  try {
    var stored = localStorage.getItem(storageKey);
    if (stored === "dark" || stored === "light") {
      theme = stored;
    }
  } catch (_error) {
    /* Keep the page default when storage is unavailable. */
  }

  if (root) {
    root.classList.toggle("dark", theme === "dark");
    root.classList.toggle("light", theme === "light");
    root.setAttribute("data-theme", theme);
    root.style.colorScheme = theme;
  }

  window.tailwind = window.tailwind || {};
  window.tailwind.config = Object.assign({}, window.tailwind.config || {}, {
    darkMode: "class",
    theme: {
      extend: {
        fontFamily: {
          sans: ["Inter", "system-ui", "sans-serif"],
          display: ["Inter", "Georgia", "serif"],
          mono: ["IBM Plex Mono", "Cascadia Code", "Consolas", "monospace"],
          numeric: ["IBM Plex Mono", "Cascadia Code", "Consolas", "monospace"]
        },
        colors: {
          primary: {
            50: "#eff6ff",
            100: "#dbeafe",
            200: "#bfdbfe",
            300: "#93c5fd",
            400: "#60a5fa",
            500: "#3b82f6",
            600: "#2563eb",
            700: "#1d4ed8",
            800: "#1e40af",
            900: "#1e3a8a"
          },
          accent: {
            400: "#22d3ee",
            500: "#06b6d4",
            600: "#0891b2"
          }
        },
        boxShadow: {
          xs: "0 1px 2px rgba(15, 23, 42, 0.06)",
          soft: "0 10px 40px -10px rgba(0, 0, 0, 0.08)",
          glow: "0 0 30px -5px rgba(59, 130, 246, 0.2)",
          card: "0 20px 30px -10px rgba(0, 0, 0, 0.05)"
        }
      }
    }
  });


  if (typeof window.tailwind.refresh === "function") {
    window.tailwind.refresh();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      if (typeof window.tailwind.refresh === "function") {
        window.tailwind.refresh();
      }
    }, { once: true });
  }
}) ();
