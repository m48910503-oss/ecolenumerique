(function () {
  const downloadTriggers = Array.from(
    document.querySelectorAll("[data-certificate-pdf-download]")
  );
  let downloadInProgress = false;

  function normalizeFilename(value) {
    const raw = (value || "certificat.pdf").trim();
    const withExtension = raw.toLowerCase().endsWith(".pdf") ? raw : `${raw}.pdf`;
    return withExtension.replace(/[\\/:*?"<>|]+/g, "-");
  }

  function waitForFonts() {
    if (!document.fonts || !document.fonts.ready) {
      return Promise.resolve();
    }

    return document.fonts.ready.catch(function () {});
  }

  function setInlinePdfStyles(source) {
    const recipient = source.querySelector(".certificate-design__recipient h2");
    if (!recipient) {
      return function () {};
    }

    const previous = {
      color: recipient.style.color,
      background: recipient.style.background,
      backgroundImage: recipient.style.backgroundImage,
      backgroundClip: recipient.style.backgroundClip,
      webkitBackgroundClip: recipient.style.webkitBackgroundClip,
      webkitTextFillColor: recipient.style.webkitTextFillColor,
      textShadow: recipient.style.textShadow,
      filter: recipient.style.filter,
    };

    recipient.style.color = "#2563eb";
    recipient.style.background = "none";
    recipient.style.backgroundImage = "none";
    recipient.style.backgroundClip = "border-box";
    recipient.style.webkitBackgroundClip = "border-box";
    recipient.style.webkitTextFillColor = "#2563eb";
    recipient.style.textShadow = "none";
    recipient.style.filter = "none";

    return function restoreInlinePdfStyles() {
      recipient.style.color = previous.color;
      recipient.style.background = previous.background;
      recipient.style.backgroundImage = previous.backgroundImage;
      recipient.style.backgroundClip = previous.backgroundClip;
      recipient.style.webkitBackgroundClip = previous.webkitBackgroundClip;
      recipient.style.webkitTextFillColor = previous.webkitTextFillColor;
      recipient.style.textShadow = previous.textShadow;
      recipient.style.filter = previous.filter;
    };
  }

  async function downloadCertificate(trigger) {
    const source = document.getElementById("certificate-container");
    if (!source || typeof window.html2pdf !== "function") {
      window.location.href = trigger.href;
      return;
    }

    downloadInProgress = true;
    trigger.setAttribute("aria-busy", "true");
    await waitForFonts();
    source.classList.add("certificate-design--pdf-source");
    const restoreInlinePdfStyles = setInlinePdfStyles(source);

    const filename = normalizeFilename(
      trigger.getAttribute("data-certificate-filename")
    );

    try {
      await window
        .html2pdf()
        .set({
          margin: 0,
          filename: filename,
          image: { type: "jpeg", quality: 1 },
          html2canvas: {
            scale: 2,
            useCORS: true,
            allowTaint: false,
            logging: false,
            backgroundColor: "#ffffff",
            scrollX: 0,
            scrollY: 0,
          },
          jsPDF: {
            unit: "mm",
            format: "a4",
            orientation: "landscape",
            compress: true,
          },
          pagebreak: { mode: ["avoid-all"] },
        })
        .from(source)
        .save();
    } catch (error) {
      window.location.href = trigger.href;
    } finally {
      restoreInlinePdfStyles();
      source.classList.remove("certificate-design--pdf-source");
      trigger.removeAttribute("aria-busy");
      downloadInProgress = false;
    }
  }

  downloadTriggers.forEach(function (trigger) {
    trigger.addEventListener("click", function (event) {
      const downloadMenu = trigger.closest("[data-certificate-download-menu]");
      if (downloadMenu && downloadMenu.open) {
        downloadMenu.open = false;
      }

      if (downloadInProgress) {
        event.preventDefault();
        return;
      }

      if (typeof window.html2pdf !== "function") {
        return;
      }

      event.preventDefault();
      downloadCertificate(trigger);
    });
  });
})();
