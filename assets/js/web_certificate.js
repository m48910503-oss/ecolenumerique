(function () {
  const qrTargets = Array.from(document.querySelectorAll("[data-certificate-qr]"));

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

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", renderQrCodes, { once: true });
  } else {
    renderQrCodes();
  }

  window.addEventListener("load", renderQrCodes);
  window.addEventListener("beforeprint", renderQrCodes);
})();
