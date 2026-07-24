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

  function buildReturnUrl() {
    return window.location.pathname + window.location.search + window.location.hash;
  }

  function redirectToLogin(loginUrl) {
    if (!loginUrl) return;
    const separator = loginUrl.indexOf("?") === -1 ? "?" : "&";
    window.location.href = loginUrl + separator + "return=" + encodeURIComponent(buildReturnUrl());
  }

  function postForm(url, payload) {
    return fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: payload.toString(),
      credentials: "same-origin",
    }).then((response) => {
      return response
        .json()
        .catch(() => null)
        .then((data) => ({
          ok: response.ok,
          status: response.status,
          data,
        }));
    });
  }

  function updateFavoriteButton(button, active) {
    if (!button) return;
    button.classList.toggle("is-active", !!active);
    button.setAttribute("aria-pressed", active ? "true" : "false");
    button.setAttribute("aria-label", active ? "Retirer des favoris" : "Ajouter aux favoris");
    button.dataset.favoriteActive = active ? "1" : "0";
    button.innerHTML = `<i class="${active ? "fas" : "far"} fa-heart" aria-hidden="true"></i>`;
  }

  document.addEventListener("DOMContentLoaded", () => {
    const root = document.getElementById("course-share");
    if (!root) return;

    const shareUrl = root.getAttribute("data-share-url") || window.location.href || "";
    const shareTitle = root.getAttribute("data-share-title") || document.title || "Cours";
    const courseId = Number(root.getAttribute("data-course-id") || 0);
    const isAuthenticated = root.getAttribute("data-authenticated") === "1";
    const loginUrl = root.getAttribute("data-login-url") || "";
    const csrfToken =
      root.getAttribute("data-csrf-token")
      || document.querySelector('meta[name="csrf-token"]')?.content
      || "";
    const favoritesApiUrl = root.getAttribute("data-favorites-api") || "";
    const favoriteInitial = root.getAttribute("data-favorite-initial") === "1";

    const panel = root.querySelector("#course-share-panel");
    const btnToggle = root.querySelector("#btn-toggle-share");
    const btnFavorite = root.querySelector("[data-course-favorite]") || root.querySelector("#btn-toggle-favorite");
    const input = root.querySelector("#course-share-url");
    const status = root.querySelector("#course-share-status");
    const btnCopy = root.querySelector("#btn-copy-course-link");
    const refInput = root.querySelector("#course-ref-code");
    const btnCopyRef = root.querySelector("#btn-copy-ref-code");
    const refLinkInput = root.querySelector("#course-ref-link");
    const btnCopyRefLink = root.querySelector("#btn-copy-ref-link");
    const btnNative = root.querySelector("#btn-native-share");
    let favoriteActive = favoriteInitial;

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

    if (btnFavorite) {
      updateFavoriteButton(btnFavorite, favoriteActive);
      btnFavorite.addEventListener("click", async () => {
        if (courseId < 1) {
          setStatus(status, "Cours introuvable.", "error");
          notify("Cours introuvable.", "error");
          return;
        }
        if (!isAuthenticated) {
          redirectToLogin(loginUrl);
          return;
        }
        if (!favoritesApiUrl) {
          setStatus(status, "Service des favoris indisponible.", "error");
          notify("Service des favoris indisponible.", "error");
          return;
        }

        const payload = new URLSearchParams();
        payload.set("_csrf", csrfToken);
        payload.set("course_id", String(courseId));
        payload.set("action", "toggle");

        btnFavorite.disabled = true;
        btnFavorite.classList.add("is-busy");
        setStatus(status, "", "neutral");

        try {
          const result = await postForm(favoritesApiUrl, payload);
          if (result && result.status === 401) {
            redirectToLogin(loginUrl);
            return;
          }
          if (!result || !result.ok || !result.data || result.data.ok === false) {
            throw new Error("favorite_request_failed");
          }

          favoriteActive = Number(result.data.favorited || 0) > 0;
          updateFavoriteButton(btnFavorite, favoriteActive);
          setStatus(status, favoriteActive ? "Ajouté aux favoris." : "Retiré des favoris.", "success");
          notify(favoriteActive ? "Formation ajoutée aux favoris." : "Formation retirée des favoris.", "success");
        } catch (_) {
          setStatus(status, "Impossible de mettre à jour les favoris.", "error");
          notify("Impossible de mettre à jour les favoris.", "error");
        } finally {
          btnFavorite.disabled = false;
          btnFavorite.classList.remove("is-busy");
        }
      });
    }

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
