(() => {
  function copyText(text) {
    if (!text) return Promise.resolve(false);

    if (navigator.clipboard && typeof navigator.clipboard.writeText === "function") {
      return navigator.clipboard
        .writeText(text)
        .then(() => true)
        .catch(() => false);
    }

    try {
      const textarea = document.createElement("textarea");
      textarea.value = text;
      textarea.setAttribute("readonly", "");
      textarea.style.position = "fixed";
      textarea.style.top = "-1000px";
      textarea.style.left = "-1000px";
      document.body.appendChild(textarea);
      textarea.select();
      textarea.setSelectionRange(0, textarea.value.length);
      const ok = document.execCommand("copy");
      document.body.removeChild(textarea);
      return Promise.resolve(ok);
    } catch {
      return Promise.resolve(false);
    }
  }

  function setStatus(el, message, kind) {
    if (!el) return;
    if (typeof window.appShowToast === "function") {
      el.textContent = "";
      return;
    }
    el.textContent = message || "";
    el.classList.remove("text-emerald-300", "text-rose-300", "text-slate-400");
    if (kind === "success") el.classList.add("text-emerald-300");
    else if (kind === "error") el.classList.add("text-rose-300");
    else el.classList.add("text-slate-400");
  }

  function notify(message, type) {
    if (typeof window.appShowToast === "function") {
      window.appShowToast(message, type);
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    const root = document.getElementById("course-share");
    if (!root) return;

    const shareUrl = root.getAttribute("data-share-url") || "";
    const shareTitle = root.getAttribute("data-share-title") || document.title || "Cours";

    const panel = root.querySelector("#course-share-panel");
    const btnToggle = root.querySelector("#btn-toggle-share");
    const input = root.querySelector("#course-share-url");
    const status = root.querySelector("#course-share-status");
    const btnCopy = root.querySelector("#btn-copy-course-link");
    const refInput = root.querySelector("#course-ref-code");
    const btnCopyRef = root.querySelector("#btn-copy-ref-code");
    const refLinkInput = root.querySelector("#course-ref-link");
    const btnCopyRefLink = root.querySelector("#btn-copy-ref-link");
    const btnNative = root.querySelector("#btn-native-share");

    if (btnToggle && panel) {
      btnToggle.addEventListener("click", () => {
        const isHidden = panel.classList.contains("hidden");
        panel.classList.toggle("hidden");
        btnToggle.setAttribute("aria-expanded", isHidden ? "true" : "false");
        if (isHidden) {
          setStatus(status, "", "neutral");
          if (input) input.focus();
        }
      });
    }

    if (input && shareUrl) input.value = shareUrl;

    if (btnCopy) {
      btnCopy.addEventListener("click", async () => {
        setStatus(status, "", "neutral");
        const ok = await copyText(shareUrl);
        if (ok) {
          setStatus(status, "Lien copi\u00e9.", "success");
          notify("Lien copi\u00e9.", "success");
        } else {
          setStatus(status, "Copie impossible. S\u00e9lectionnez le lien et copiez-le manuellement.", "error");
          notify("Copie impossible. S\u00e9lectionnez le lien et copiez-le manuellement.", "error");
          if (input) input.focus();
        }
      });
    }

    if (btnCopyRef && refInput) {
      btnCopyRef.addEventListener("click", async () => {
        setStatus(status, "", "neutral");
        const ok = await copyText(refInput.value || "");
        if (ok) {
          setStatus(status, "Code de parrainage copi\u00e9.", "success");
          notify("Code de parrainage copi\u00e9.", "success");
        } else {
          setStatus(status, "Copie impossible. S\u00e9lectionnez le code et copiez-le manuellement.", "error");
          notify("Copie impossible. S\u00e9lectionnez le code et copiez-le manuellement.", "error");
          refInput.focus();
        }
      });
    }

    if (btnCopyRefLink && refLinkInput) {
      btnCopyRefLink.addEventListener("click", async () => {
        setStatus(status, "", "neutral");
        const ok = await copyText(refLinkInput.value || "");
        if (ok) {
          setStatus(status, "Lien de parrainage copi\u00e9.", "success");
          notify("Lien de parrainage copi\u00e9.", "success");
        } else {
          setStatus(status, "Copie impossible. S\u00e9lectionnez le lien et copiez-le manuellement.", "error");
          notify("Copie impossible. S\u00e9lectionnez le lien et copiez-le manuellement.", "error");
          refLinkInput.focus();
        }
      });
    }

    if (btnNative) {
      if (typeof navigator.share === "function") {
        btnNative.classList.remove("hidden");
        btnNative.addEventListener("click", async () => {
          try {
            await navigator.share({
              title: shareTitle,
              text: shareTitle,
              url: shareUrl,
            });
          } catch {
            // Ignore cancel/errors.
          }
        });
      } else {
        btnNative.classList.add("hidden");
      }
    }
  });
})();
