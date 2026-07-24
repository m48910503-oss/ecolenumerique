// Gestion des avis de cours : formulaire inline, modale reutilisable et rappels de progression.
(function () {
  "use strict";

  var PROMPT_THRESHOLDS = [10, 50, 90];
  var promptMemory = {};
  var modal = null;
  var modalForm = null;
  var activeContext = null;
  var activeTrigger = null;

  function ready(callback) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", callback);
      return;
    }
    callback();
  }

  function getBody() {
    return document.body || document.documentElement;
  }

  function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute("content") || "" : "";
  }

  function getApiUrl(form) {
    if (form && form.getAttribute("action")) {
      return form.getAttribute("action");
    }

    var body = getBody();
    if (body.dataset && body.dataset.ratingApiUrl) {
      return body.dataset.ratingApiUrl;
    }

    var basePath = body.dataset ? body.dataset.basePath || "" : "";
    return (basePath || "") + "/api/rating";
  }

  function numberInRange(value, min, max) {
    var number = Number(value || 0);
    if (!Number.isFinite(number)) {
      return min;
    }
    return Math.max(min, Math.min(max, number));
  }

  function readContext(source) {
    var body = getBody();
    var card = source && source.closest ? source.closest("[data-course-card]") : null;
    var contextSource = card || source || body;
    var data = contextSource && contextSource.dataset ? contextSource.dataset : {};
    var sourceData = source && source.dataset ? source.dataset : {};
    var bodyData = body.dataset || {};

    return {
      courseId: String(sourceData.courseId || data.courseId || bodyData.courseId || "").trim(),
      title: String(sourceData.title || data.title || bodyData.courseTitle || "Cours").trim(),
      ratingValue: String(sourceData.ratingValue || data.ratingValue || bodyData.ratingValue || "").trim(),
      ratingComment: String(sourceData.ratingComment || data.ratingComment || bodyData.ratingComment || ""),
      source: String(sourceData.ratingSource || "manual").trim(),
    };
  }

  function isRated() {
    return Number((getBody().dataset || {}).ratingValue || 0) > 0;
  }

  function ratingLabel(ratingValue, longLabel) {
    if (Number(ratingValue || 0) > 0) {
      return "Modifier l'avis";
    }
    return longLabel ? "Noter le cours" : "Noter";
  }

  function updateRatingTriggers(courseId, ratingValue, comment) {
    Array.prototype.slice.call(document.querySelectorAll("[data-course-card]")).forEach(function (card) {
      if (String(card.dataset.courseId || "") !== String(courseId)) {
        return;
      }
      card.dataset.ratingValue = String(ratingValue);
      card.dataset.ratingComment = comment || "";
    });

    Array.prototype.slice.call(document.querySelectorAll("[data-course-rating-open]")).forEach(function (trigger) {
      var context = readContext(trigger);
      if (String(context.courseId) !== String(courseId)) {
        return;
      }
      trigger.dataset.ratingValue = String(ratingValue);
      trigger.dataset.ratingComment = comment || "";
      var labelNode = trigger.querySelector("[data-rating-trigger-label]");
      var label = ratingLabel(ratingValue, trigger.id === "course-modal-rating");
      if (labelNode) {
        labelNode.textContent = label;
      }
      if (trigger.hasAttribute("aria-label")) {
        trigger.setAttribute("aria-label", label);
      }
      if (trigger.hasAttribute("title")) {
        trigger.setAttribute("title", label);
      }
    });

    var body = getBody();
    if (String((body.dataset || {}).courseId || "") === String(courseId)) {
      body.dataset.ratingValue = String(ratingValue);
      body.dataset.ratingComment = comment || "";
    }
  }

  function dispatchSaved(courseId, ratingValue, comment) {
    window.dispatchEvent(
      new CustomEvent("course:ratingSaved", {
        detail: {
          courseId: courseId,
          rating: ratingValue,
          comment: comment || "",
        },
      })
    );
  }

  function friendlyError(payload) {
    var code = payload && payload.error ? String(payload.error) : "";
    if (code === "non_authentifie") {
      return "Vous devez etre connecte pour laisser un avis.";
    }
    if (code === "non_inscrit") {
      return "Vous devez etre inscrit a ce cours pour laisser un avis.";
    }
    if (code === "parametres_invalides") {
      return "Choisissez une note entre 1 et 5.";
    }
    if (code === "cours_introuvable") {
      return "Ce cours n'est plus disponible.";
    }
    return "Erreur lors de l'enregistrement de l'avis.";
  }

  function setStatus(statusEl, message, tone) {
    if (!statusEl) {
      return;
    }
    statusEl.textContent = message || "";
    statusEl.className =
      tone === "success"
        ? "text-sm text-emerald-600 dark:text-emerald-300"
        : tone === "error"
          ? "text-sm text-rose-600 dark:text-rose-300"
          : "text-sm text-gray-500 dark:text-gray-400";
  }

  async function submitRating(form, statusEl) {
    var formData = new FormData(form);
    var courseId = String(formData.get("course_id") || "").trim();
    var selectedRating = numberInRange(formData.get("rating"), 0, 5);
    var comment = String(formData.get("comment") || "");
    var csrf = getCsrfToken();

    if (csrf && !formData.get("_csrf")) {
      formData.append("_csrf", csrf);
    }

    setStatus(statusEl, "Enregistrement...", "neutral");

    try {
      var response = await fetch(getApiUrl(form), {
        method: "POST",
        body: formData,
      });
      var data = await response.json();

      if (!response.ok || !data || !data.ok) {
        setStatus(statusEl, friendlyError(data || {}), "error");
        return false;
      }

      updateRatingTriggers(courseId, selectedRating, comment);
      dispatchSaved(courseId, selectedRating, comment);
      PROMPT_THRESHOLDS.forEach(function (threshold) {
        setPromptState(courseId, threshold, "submitted");
      });
      setStatus(statusEl, "Avis enregistre.", "success");
      return true;
    } catch (_error) {
      setStatus(statusEl, "Erreur reseau. Reessayez dans un instant.", "error");
      return false;
    }
  }

  function initInlineForm() {
    var form = document.getElementById("rating-form");
    if (!form || form.dataset.ratingBound === "1") {
      return;
    }
    form.dataset.ratingBound = "1";

    var statusEl = document.getElementById("rating-status");
    var ratingPanel = form.closest("[data-rating-panel]");

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      submitRating(form, statusEl).then(function (saved) {
        if (!saved) {
          return;
        }

        var formData = new FormData(form);
        var selectedRating = Number(formData.get("rating") || 0);
        if (ratingPanel) {
          var donePanel = document.createElement("div");
          donePanel.className = "rounded-lg border border-emerald-700/40 bg-emerald-900/20 p-4";
          donePanel.innerHTML = '<p class="text-xs text-emerald-200">Avis deja enregistre (' + selectedRating + "/5).</p>";
          ratingPanel.replaceWith(donePanel);
        }
      });
    });
  }

  function ensureModal() {
    if (modal) {
      return modal;
    }

    modal = document.createElement("div");
    modal.id = "course-rating-modal";
    modal.className = "fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/60 px-4 py-6 backdrop-blur-sm";
    modal.setAttribute("role", "dialog");
    modal.setAttribute("aria-modal", "true");
    modal.setAttribute("aria-labelledby", "course-rating-title");
    modal.innerHTML =
      '<div class="w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900">' +
        '<div class="flex items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 dark:border-gray-800">' +
          '<div class="min-w-0">' +
            '<p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-600 dark:text-amber-300">Avis du cours</p>' +
            '<h2 id="course-rating-title" class="mt-1 truncate text-xl font-bold text-gray-900 dark:text-white">Votre avis</h2>' +
            '<p data-rating-course-title class="mt-1 truncate text-sm text-gray-500 dark:text-gray-400"></p>' +
          '</div>' +
          '<button type="button" data-rating-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:text-gray-900 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 dark:hover:text-white" aria-label="Fermer">' +
            '<i class="fas fa-times"></i>' +
          '</button>' +
        '</div>' +
        '<form data-rating-modal-form class="space-y-4 px-5 py-5">' +
          '<p data-rating-prompt-copy class="hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-100"></p>' +
          '<input type="hidden" name="_csrf" value="">' +
          '<input type="hidden" name="course_id" value="">' +
          '<input type="hidden" name="source" value="">' +
          '<label class="block space-y-2">' +
            '<span class="text-sm font-medium text-gray-700 dark:text-gray-300">Note</span>' +
            '<select name="rating" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-900 outline-none transition focus:border-primary-300 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100">' +
              '<option value="5">5/5</option>' +
              '<option value="4">4/5</option>' +
              '<option value="3">3/5</option>' +
              '<option value="2">2/5</option>' +
              '<option value="1">1/5</option>' +
            '</select>' +
          '</label>' +
          '<label class="block space-y-2">' +
            '<span class="text-sm font-medium text-gray-700 dark:text-gray-300">Commentaire</span>' +
            '<textarea name="comment" rows="4" placeholder="Expliquez ce qui vous a aide et ce qui pourrait encore etre ameliore." class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition focus:border-primary-300 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"></textarea>' +
          '</label>' +
          '<div class="flex flex-wrap items-center justify-between gap-3">' +
            '<span data-rating-modal-status class="text-sm text-gray-500 dark:text-gray-400"></span>' +
            '<div class="flex flex-wrap justify-end gap-2">' +
              '<button type="button" data-rating-later class="hidden rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 dark:hover:bg-gray-800">Plus tard</button>' +
              '<button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-primary-700">' +
                '<i class="fas fa-check"></i>' +
                '<span>Enregistrer mon avis</span>' +
              '</button>' +
            '</div>' +
          '</div>' +
        '</form>' +
      '</div>';

    document.body.appendChild(modal);
    modalForm = modal.querySelector("[data-rating-modal-form]");

    modal.addEventListener("click", function (event) {
      if (event.target === modal) {
        closeModal();
      }
    });

    Array.prototype.slice.call(modal.querySelectorAll("[data-rating-close]")).forEach(function (button) {
      button.addEventListener("click", closeModal);
    });

    var laterButton = modal.querySelector("[data-rating-later]");
    if (laterButton) {
      laterButton.addEventListener("click", closeModal);
    }

    if (modalForm) {
      modalForm.addEventListener("submit", function (event) {
        event.preventDefault();
        var statusEl = modal.querySelector("[data-rating-modal-status]");
        submitRating(modalForm, statusEl).then(function (saved) {
          if (saved) {
            window.setTimeout(closeModal, 650);
          }
        });
      });
    }

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && modal && !modal.classList.contains("hidden")) {
        closeModal();
      }
    });

    return modal;
  }

  function openModal(context, options) {
    var normalized = Object.assign({
      courseId: "",
      title: "Cours",
      ratingValue: "",
      ratingComment: "",
      source: "manual",
    }, context || {});

    if (!normalized.courseId) {
      return;
    }

    var promptThreshold = options && options.promptThreshold ? Number(options.promptThreshold) : 0;
    var node = ensureModal();
    activeContext = normalized;
    activeTrigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;

    var titleNode = node.querySelector("[data-rating-course-title]");
    var promptCopy = node.querySelector("[data-rating-prompt-copy]");
    var statusEl = node.querySelector("[data-rating-modal-status]");
    var laterButton = node.querySelector("[data-rating-later]");
    var csrfInput = node.querySelector('input[name="_csrf"]');
    var courseInput = node.querySelector('input[name="course_id"]');
    var sourceInput = node.querySelector('input[name="source"]');
    var select = node.querySelector('select[name="rating"]');
    var textarea = node.querySelector('textarea[name="comment"]');

    if (titleNode) {
      titleNode.textContent = normalized.title || "Cours";
    }
    if (promptCopy) {
      if (promptThreshold > 0) {
        promptCopy.textContent = "Vous avez atteint " + promptThreshold + "% du cours. Vous pouvez laisser une note maintenant ou continuer et revenir plus tard.";
        promptCopy.classList.remove("hidden");
      } else {
        promptCopy.textContent = "";
        promptCopy.classList.add("hidden");
      }
    }
    if (laterButton) {
      laterButton.classList.toggle("hidden", promptThreshold < 1);
    }
    if (statusEl) {
      setStatus(statusEl, "", "neutral");
    }
    if (csrfInput) {
      csrfInput.value = getCsrfToken();
    }
    if (courseInput) {
      courseInput.value = normalized.courseId;
    }
    if (sourceInput) {
      sourceInput.value = normalized.source || (promptThreshold > 0 ? "auto_" + promptThreshold : "manual");
    }
    if (select) {
      var currentRating = Number(normalized.ratingValue || 0);
      select.value = currentRating > 0 ? String(numberInRange(currentRating, 1, 5)) : "5";
    }
    if (textarea) {
      textarea.value = normalized.ratingComment || "";
    }

    node.classList.remove("hidden");
    node.classList.add("flex");
    node.setAttribute("aria-hidden", "false");

    window.setTimeout(function () {
      if (select) {
        select.focus();
      }
    }, 0);
  }

  function closeModal() {
    if (!modal) {
      return;
    }
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    modal.setAttribute("aria-hidden", "true");
    activeContext = null;
    if (activeTrigger && typeof activeTrigger.focus === "function") {
      activeTrigger.focus();
    }
    activeTrigger = null;
  }

  function storageKey(courseId, threshold) {
    return "course_rating_prompt:" + courseId + ":" + threshold;
  }

  function getPromptState(courseId, threshold) {
    var key = storageKey(courseId, threshold);
    if (promptMemory[key]) {
      return promptMemory[key];
    }
    try {
      return window.localStorage.getItem(key) || "";
    } catch (_error) {
      return "";
    }
  }

  function setPromptState(courseId, threshold, value) {
    var key = storageKey(courseId, threshold);
    promptMemory[key] = value;
    try {
      window.localStorage.setItem(key, value);
    } catch (_error) {
      // localStorage peut etre indisponible en navigation privee.
    }
  }

  function maybeOpenProgressPrompt(percent) {
    var body = getBody();
    var data = body.dataset || {};
    if (data.ratingAutoPrompts !== "1" || isRated()) {
      return;
    }
    if (modal && !modal.classList.contains("hidden")) {
      return;
    }

    var courseId = String(data.courseId || "").trim();
    if (!courseId) {
      return;
    }

    var progress = numberInRange(percent, 0, 100);
    var reached = PROMPT_THRESHOLDS.filter(function (threshold) {
      return progress >= threshold;
    });
    if (!reached.length) {
      return;
    }

    var threshold = reached[reached.length - 1];
    if (getPromptState(courseId, threshold)) {
      return;
    }

    setPromptState(courseId, threshold, "shown");
    openModal({
      courseId: courseId,
      title: data.courseTitle || "Cours",
      ratingValue: data.ratingValue || "",
      ratingComment: data.ratingComment || "",
      source: "auto_" + threshold,
    }, { promptThreshold: threshold });
  }

  function initTriggers() {
    document.addEventListener("click", function (event) {
      if (!(event.target instanceof Element)) {
        return;
      }

      var trigger = event.target.closest("[data-course-rating-open]");
      if (!trigger) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      openModal(readContext(trigger), { promptThreshold: 0 });
    });
  }

  function initAutoPrompts() {
    var body = getBody();
    if (!body.dataset || body.dataset.ratingAutoPrompts !== "1") {
      return;
    }

    window.addEventListener("course:progressChanged", function (event) {
      var detail = event.detail || {};
      var bodyCourseId = String((body.dataset || {}).courseId || "");
      if (detail.courseId && String(detail.courseId) !== bodyCourseId) {
        return;
      }
      maybeOpenProgressPrompt(Number(detail.percent || 0));
    });

    window.setTimeout(function () {
      maybeOpenProgressPrompt(Number(body.dataset.currentProgress || body.dataset.baseProgress || 0));
    }, 700);
  }

  window.CourseRatingPrompt = {
    open: function (context, options) {
      openModal(context || {}, options || {});
    },
    close: closeModal,
  };

  ready(function () {
    initInlineForm();
    initTriggers();
    initAutoPrompts();
  });
})();
