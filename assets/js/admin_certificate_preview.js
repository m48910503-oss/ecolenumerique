(function () {
  const downloadMenu = document.querySelector("[data-certificate-download-menu]");
  const downloadLinks = Array.from(
    document.querySelectorAll("[data-certificate-download-link]")
  );
  const printButtons = Array.from(document.querySelectorAll("[data-certificate-print]"));
  const qrTargets = Array.from(document.querySelectorAll("[data-certificate-qr]"));
  let menuToggleBound = false;

  function renderQrCodes() {
    if (typeof qrcode !== "function") return;

    qrTargets.forEach((target) => {
      const value = (target.getAttribute("data-certificate-qr") || "").trim();
      if (!value) return;

      try {
        const qr = qrcode(0, "M");
        qr.addData(value);
        qr.make();
        target.innerHTML = qr.createSvgTag({
          cellSize: 3,
          margin: 2,
          alt: "QR de vérification",
          title: "QR de vérification",
        });
        target.classList.add("is-ready");
      } catch (error) {
        target.classList.remove("is-ready");
      }
    });
  }

  function applyPrintPageStyle() {
    let style = document.getElementById("admin-certificate-print-page-style");
    if (!style) {
      style = document.createElement("style");
      style.id = "admin-certificate-print-page-style";
      document.head.appendChild(style);
    }

    style.textContent = "@page { size: A4 landscape; margin: 0; }";
  }

  function buildDownloadUrl(mode) {
    if (!downloadMenu) {
      return "";
    }

    const baseUrl = downloadMenu.getAttribute("data-certificate-download-base") || "";
    if (!baseUrl) {
      return "";
    }

    const url = new URL(baseUrl, window.location.href);
    url.searchParams.set("export", mode);
    return url.toString();
  }

  function syncDownloadLinks() {
    downloadLinks.forEach((link) => {
      const mode = link.getAttribute("data-certificate-download-link") || "";
      const nextUrl = buildDownloadUrl(mode);
      if (nextUrl) {
        link.setAttribute("href", nextUrl);
      }
    });
  }

  function closeDownloadMenu() {
    if (downloadMenu && downloadMenu.open) {
      downloadMenu.open = false;
    }
  }

  printButtons.forEach((button) => {
    button.addEventListener("click", () => {
      closeDownloadMenu();
      renderQrCodes();
      applyPrintPageStyle();
      window.print();
    });
  });

  downloadLinks.forEach((link) => {
    link.addEventListener("click", () => {
      closeDownloadMenu();
    });
  });

  if (downloadMenu && !menuToggleBound) {
    menuToggleBound = true;
    downloadMenu.addEventListener("toggle", () => {
      const summary = downloadMenu.querySelector("summary");
      if (summary) {
        summary.setAttribute("aria-expanded", downloadMenu.open ? "true" : "false");
      }
    });
    const summary = downloadMenu.querySelector("summary");
    if (summary) {
      summary.setAttribute("aria-expanded", downloadMenu.open ? "true" : "false");
    }
  }

  document.addEventListener("click", (event) => {
    if (!downloadMenu || !downloadMenu.open) {
      return;
    }
    if (!downloadMenu.contains(event.target)) {
      closeDownloadMenu();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeDownloadMenu();
    }
  });

  window.addEventListener("beforeprint", () => {
    renderQrCodes();
    applyPrintPageStyle();
  });

  syncDownloadLinks();
  applyPrintPageStyle();
  renderQrCodes();
})();
