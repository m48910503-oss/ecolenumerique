(function () {
  const container = document.getElementById("certificate-container");
  const orientationButtons = Array.from(
    document.querySelectorAll("[data-certificate-orientation]")
  );
  const printButtons = Array.from(
    document.querySelectorAll("[data-certificate-print]")
  );
  const qrTargets = Array.from(document.querySelectorAll("[data-certificate-qr]"));
  let currentOrientation = "portrait";

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

    const size = currentOrientation === "landscape" ? "A4 landscape" : "A4 portrait";
    style.textContent = "@page { size: " + size + "; margin: 0; }";
  }

  function setOrientation(orientation) {
    if (!container) return;
    const next = orientation === "landscape" ? "landscape" : "portrait";
    currentOrientation = next;
    container.classList.toggle("admin-certificate-card--portrait", next === "portrait");
    container.classList.toggle("admin-certificate-card--landscape", next === "landscape");

    orientationButtons.forEach((button) => {
      const isActive = button.getAttribute("data-certificate-orientation") === next;
      button.classList.toggle("is-active", isActive);
      button.setAttribute("aria-pressed", isActive ? "true" : "false");
    });

    applyPrintPageStyle();
  }

  orientationButtons.forEach((button) => {
    button.addEventListener("click", () => {
      setOrientation(button.getAttribute("data-certificate-orientation") || "portrait");
    });
  });

  printButtons.forEach((button) => {
    button.addEventListener("click", () => {
      renderQrCodes();
      applyPrintPageStyle();
      window.print();
    });
  });

  window.addEventListener("beforeprint", () => {
    renderQrCodes();
    applyPrintPageStyle();
  });
  setOrientation("portrait");
  renderQrCodes();
})();
