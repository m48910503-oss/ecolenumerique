(() => {
  function cleanHashFromUrl() {
    if (!window.location.hash) return;
    const cleanUrl = window.location.pathname + window.location.search;
    try {
      history.replaceState(history.state, document.title, cleanUrl);
    } catch {
      // Ignore (older browsers / blocked).
    }
  }

  function getTargetFromHash(hash) {
    if (!hash || hash === "#") return null;
    const rawId = hash.startsWith("#") ? hash.slice(1) : hash;
    if (!rawId) return null;
    let id = rawId;
    try {
      id = decodeURIComponent(rawId);
    } catch {
      // Keep raw.
    }
    return document.getElementById(id);
  }

  function scrollToTarget(target) {
    if (!target) return;
    try {
      target.scrollIntoView({ behavior: "smooth", block: "start" });
    } catch {
      target.scrollIntoView();
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    // Intercepte les ancres (#section) pour ne pas afficher le hash dans l'URL.
    document.querySelectorAll('a[href^="#"]').forEach((link) => {
      if (!(link instanceof HTMLAnchorElement)) return;
      if (link.hasAttribute("data-keep-hash")) return;
      const href = link.getAttribute("href") || "";
      if (!href || href === "#") return;
      const target = getTargetFromHash(href);
      if (!target) return;

      link.addEventListener("click", (e) => {
        e.preventDefault();
        scrollToTarget(target);
        cleanHashFromUrl();
      });
    });

    // Si l'utilisateur arrive avec un hash, on scroll puis on le retire.
    if (window.location.hash) {
      const target = getTargetFromHash(window.location.hash);
      if (!target) {
        cleanHashFromUrl();
        return;
      }
      requestAnimationFrame(() => {
        scrollToTarget(target);
        cleanHashFromUrl();
      });
    }
  });
})();

