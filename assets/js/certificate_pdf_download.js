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

  function createPdfSource(source) {
    const wrapper = document.createElement("div");
    wrapper.setAttribute("aria-hidden", "true");
    wrapper.style.position = "fixed";
    wrapper.style.left = "-20000px";
    wrapper.style.top = "0";
    wrapper.style.width = "1120px";
    wrapper.style.background = "#ffffff";
    wrapper.style.zIndex = "-2147483647";

    const clone = source.cloneNode(true);
    clone.removeAttribute("id");
    clone.classList.add("certificate-design--pdf-source");
    wrapper.appendChild(clone);
    document.body.appendChild(wrapper);

    return { wrapper: wrapper, certificate: clone };
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

    const pdfSource = createPdfSource(source);
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
            windowWidth: 1120,
            windowHeight: 792,
          },
          jsPDF: {
            unit: "mm",
            format: "a4",
            orientation: "landscape",
            compress: true,
          },
          pagebreak: { mode: ["avoid-all"] },
        })
        .from(pdfSource.certificate)
        .save();
    } catch (error) {
      window.location.href = trigger.href;
    } finally {
      pdfSource.wrapper.remove();
      trigger.removeAttribute("aria-busy");
      downloadInProgress = false;
    }
  }

  downloadTriggers.forEach(function (trigger) {
    trigger.addEventListener("click", function (event) {
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
