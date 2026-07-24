(() => {
  const onReady = () => {
    initSiteAnnouncement();
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", onReady, { once: true });
  } else {
    onReady();
  }

  function initSiteAnnouncement() {
    if (!(document.body instanceof HTMLBodyElement)) {
      return;
    }

    const existingBar = document.querySelector("[data-site-announcement]");
    if (existingBar instanceof HTMLElement) {
      syncOffset(existingBar);
      return;
    }

    const payload = readPayload();
    if (!payload) {
      clearOffset();
      return;
    }

    const announcementId = Number.parseInt(String(payload.id || "0"), 10);
    const storageKey = Number.isFinite(announcementId) && announcementId > 0
      ? `mk-public-announcement-dismissed:${announcementId}`
      : "";

    if (storageKey !== "" && canUseStorage()) {
      try {
        if (window.localStorage.getItem(storageKey) === "1") {
          clearOffset();
          return;
        }
      } catch (_error) {
        // Ignore storage access issues.
      }
    }

    const bar = buildAnnouncementBar(payload, announcementId);
    document.body.insertAdjacentElement("afterbegin", bar);

    let resizeObserver = null;
    const updateOffset = () => syncOffset(bar);
    const hideBar = () => {
      if (storageKey !== "" && canUseStorage()) {
        try {
          window.localStorage.setItem(storageKey, "1");
        } catch (_error) {
          // Ignore storage access issues.
        }
      }

      bar.remove();
      if (resizeObserver && typeof resizeObserver.disconnect === "function") {
        resizeObserver.disconnect();
      }
      clearOffset();
    };

    const closeButton = bar.querySelector("[data-site-announcement-close]");
    if (closeButton instanceof HTMLButtonElement) {
      closeButton.addEventListener("click", hideBar);
    }

    updateOffset();
    window.addEventListener("load", updateOffset, { once: true });
    window.addEventListener("resize", updateOffset, { passive: true });
    window.addEventListener("orientationchange", updateOffset, { passive: true });

    if (typeof ResizeObserver === "function") {
      resizeObserver = new ResizeObserver(updateOffset);
      resizeObserver.observe(bar);
    }
  }

  function readPayload() {
    const node = document.getElementById("global-site-announcement-data");
    if (!(node instanceof HTMLScriptElement)) {
      return null;
    }

    const raw = (node.textContent || "").trim();
    if (raw === "") {
      return null;
    }

    try {
      const payload = JSON.parse(raw);
      if (!payload || typeof payload !== "object") {
        return null;
      }

      const title = normalizeText(payload.title || "");
      const message = normalizeText(payload.message || "");
      if (title === "" && message === "") {
        return null;
      }

      return {
        id: payload.id || 0,
        title: title || "Annonce",
        message,
        url: normalizeText(payload.url || ""),
        linkLabel: normalizeText(payload.linkLabel || "") || "En savoir plus",
      };
    } catch (_error) {
      return null;
    }
  }

  function buildAnnouncementBar(payload, announcementId) {
    const bar = document.createElement("div");
    bar.className = "app-site-announcement";
    bar.dataset.siteAnnouncement = "";
    bar.dataset.announcementId = String(Math.max(0, announcementId || 0));

    const inner = document.createElement("div");
    inner.className = "app-site-announcement__inner";

    const text = document.createElement("p");
    text.className = "app-site-announcement__text";

    const title = document.createElement("span");
    title.className = "app-site-announcement__title";
    title.textContent = payload.title || "Annonce";
    text.appendChild(title);

    if (payload.message !== "") {
      const message = document.createElement("span");
      message.className = "app-site-announcement__message";
      message.textContent = payload.message;
      text.appendChild(message);
    }

    if (payload.url !== "") {
      const link = document.createElement("a");
      link.className = "app-site-announcement__link";
      link.href = payload.url;
      link.textContent = payload.linkLabel || "En savoir plus";
      text.appendChild(link);
    }

    const closeButton = document.createElement("button");
    closeButton.type = "button";
    closeButton.className = "app-site-announcement__close";
    closeButton.dataset.siteAnnouncementClose = "";
    closeButton.setAttribute("aria-label", "Masquer l'annonce");

    const closeIcon = document.createElement("span");
    closeIcon.setAttribute("aria-hidden", "true");
    closeIcon.textContent = String.fromCharCode(215);
    closeButton.appendChild(closeIcon);

    inner.appendChild(text);
    inner.appendChild(closeButton);
    bar.appendChild(inner);

    return bar;
  }

  function syncOffset(bar) {
    if (!(bar instanceof HTMLElement) || !bar.isConnected || bar.hidden) {
      clearOffset();
      return;
    }

    const height = Math.max(0, Math.ceil(bar.getBoundingClientRect().height));
    document.documentElement.style.setProperty("--app-site-announcement-offset", `${height}px`);
  }

  function clearOffset() {
    document.documentElement.style.setProperty("--app-site-announcement-offset", "0px");
  }

  function canUseStorage() {
    try {
      return typeof window.localStorage !== "undefined";
    } catch (_error) {
      return false;
    }
  }

  function normalizeText(value) {
    return String(value || "").trim();
  }
})();