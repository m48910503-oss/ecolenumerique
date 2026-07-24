(function () {
  "use strict";

  var currentStep = 1;
  var totalSteps = 8;
  var totalPhases = 4;
  var moduleSeq = 0;
  var examSeq = 0;
  var questionSeq = 0;
  var lessonFileSeq = 0;
  var autosaveReady = false;
  var autosaveTimer = null;
  var thumbObjectUrl = "";
  var lastSavedCoverSignature = "";
  var draftPrompt = {
    enabled: false,
    root: null,
    meta: null,
    form: null,
    continueBtn: null,
    newBtn: null,
    backBtn: null,
    restoreLocalBtn: null,
    choicePanel: null,
    listPanel: null,
    view: "choice",
    pending: null
  };
  var stepRouteMap = {
    1: "presentation",
    2: "presentation-form",
    3: "offre",
    4: "offre-form",
    5: "contenu",
    6: "contenu-form",
    7: "evaluation",
    8: "evaluation-form"
  };

  function ready(fn) {
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", fn);
    else fn();
  }

  function q(sel, root) {
    return (root || document).querySelector(sel);
  }

  function qa(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  function clean(value) {
    return String(value || "").replace(/\s+/g, " ").trim();
  }

  function escapeHtml(value) {
    return String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function parsePayload(field, fallback) {
    if (!field || !clean(field.value)) return fallback;
    try {
      var parsed = JSON.parse(field.value);
      return parsed && typeof parsed === "object" ? parsed : fallback;
    } catch (_e) {
      return fallback;
    }
  }

  function asArray(value) {
    return Array.isArray(value) ? value : [];
  }

  function numberValue(value, fallback) {
    var parsed = parseInt(String(value || ""), 10);
    return Number.isFinite(parsed) ? parsed : fallback;
  }

  function countLabel(count, singular, plural) {
    if (count <= 0) {
      return "Aucun " + singular;
    }
    return String(count) + " " + (count === 1 ? singular : (plural || (singular + "s")));
  }

  function revokeThumbObjectUrl() {
    if (!thumbObjectUrl || !window.URL || !window.URL.revokeObjectURL) return;
    window.URL.revokeObjectURL(thumbObjectUrl);
    thumbObjectUrl = "";
  }

  function fileSignature(file) {
    if (!file) return "";
    return [
      clean(file.name),
      String(file.size || 0),
      String(file.lastModified || 0),
      clean(file.type)
    ].join("::");
  }

  function stepSlug(step) {
    return stepRouteMap[step] || stepRouteMap[1];
  }

  function phaseFromStep(step) {
    return Math.max(1, Math.min(totalPhases, Math.ceil(step / 2)));
  }

  function isIntroStep(step) {
    return step % 2 === 1;
  }

  function stepNumberFromValue(value) {
    var normalized = clean(value).toLowerCase();
    if (normalized === "") return null;
    var directNumber = numberValue(normalized, 0);
    if (directNumber >= 1 && directNumber <= totalSteps) {
      return directNumber;
    }
    return Object.keys(stepRouteMap).reduce(function (matched, key) {
      if (matched) return matched;
      return stepRouteMap[key] === normalized ? numberValue(key, 1) : null;
    }, null);
  }

  function requestedStepFromUrl() {
    try {
      var url = new URL(window.location.href);
      return stepNumberFromValue(url.searchParams.get("step"));
    } catch (_error) {
      return null;
    }
  }

  function syncStepInUrl(step, historyMode) {
    if (!window.history || !window.history.replaceState) return;
    try {
      var url = new URL(window.location.href);
      var nextSlug = stepSlug(step);
      if (url.searchParams.get("step") === nextSlug && historyMode !== "push") {
        return;
      }
      url.searchParams.set("step", nextSlug);
      var state = Object.assign({}, window.history.state || {}, {
        courseCreateStep: step
      });
      if (historyMode === "push" && typeof window.history.pushState === "function") {
        window.history.pushState(state, "", url.toString());
        return;
      }
      window.history.replaceState(state, "", url.toString());
    } catch (_error) {
      // Ignore URL/history issues; wizard still works without deep-linking.
    }
  }

  function makeLessonFileKey() {
    lessonFileSeq += 1;
    return "lesson_" + Date.now().toString(36) + "_" + String(lessonFileSeq);
  }

  function lessonFileName(lesson) {
    var input = q(".lesson-file-input", lesson);
    if (input && input.files && input.files.length) {
      return clean(input.files[0].name);
    }
    var label = q(".lesson-file-name", lesson);
    return clean(label && label.getAttribute("data-file-name"));
  }

  function uploadedResourceLooksValid(value) {
    value = clean(value);
    return /^https?:\/\//i.test(value) || /^storage\//i.test(value);
  }

  function displayNameFromResource(value) {
    value = clean(value);
    if (value === "") return "";
    var path = value.split("?")[0].split("#")[0].replace(/\\/g, "/");
    var name = path.split("/").pop() || "";
    try {
      name = decodeURIComponent(name);
    } catch (error) {
      // Keep the raw name if URL decoding fails.
    }
    if (["view", "edit", "preview", "download"].indexOf(clean(name).toLowerCase()) !== -1) {
      return "Document déjà associé";
    }
    return clean(name) || "Document déjà associé";
  }

  function mapLessonTypeToUi(type) {
    return type === "meeting" ? "appointment" : (type === "document" ? "document" : "video");
  }

  function mapLessonTypeToPayload(type) {
    return type === "appointment" ? "meeting" : (type === "document" ? "document" : "video");
  }

  function mapExamTypeToUi(type) {
    return type === "drive" ? "document" : (type === "meeting" ? "appointment" : "qcm");
  }

  function mapExamTypeToPayload(type) {
    return type === "document" ? "drive" : (type === "appointment" ? "meeting" : "qcm");
  }

  function lessonTypeLabel(type) {
    if (type === "document") return "Document";
    if (type === "appointment") return "Rendez-vous";
    return "Vidéo";
  }

  function defaultModule() {
    return {
      title: "",
      description: "",
      items: [
        { type: "video", title: "", description: "", resource: "", duration_min: 0 }
      ]
    };
  }

  function defaultNewModule() {
    return {
      title: "",
      description: "",
      items: [{ type: "video", title: "", description: "", resource: "", duration_min: 0 }]
    };
  }

  function defaultExam() {
    return {
      title: "Évaluation finale",
      type: "qcm",
      description: "",
      duration_min: 45,
      min_score: 50,
      points_per_question: 1,
      version_count: 3,
      versions: {
        "1": [{ text: "", type: "single", options: [{ text: "", correct: true }, { text: "", correct: false }] }],
        "2": [{ text: "", type: "single", options: [{ text: "", correct: true }, { text: "", correct: false }] }],
        "3": [{ text: "", type: "single", options: [{ text: "", correct: true }, { text: "", correct: false }] }]
      }
    };
  }

  function defaultNewExam() {
    return {
      title: "Évaluation finale",
      type: "qcm",
      description: "",
      duration_min: 45,
      min_score: 50,
      points_per_question: 1,
      version_count: 3,
      versions: {
        "1": [{ text: "", type: "single", options: [{ text: "", correct: true }, { text: "", correct: false }] }],
        "2": [{ text: "", type: "single", options: [{ text: "", correct: true }, { text: "", correct: false }] }],
        "3": [{ text: "", type: "single", options: [{ text: "", correct: true }, { text: "", correct: false }] }]
      }
    };
  }

  function lessonDynamicHtml(type, resource, mode, fileKey, fileName) {
    var value = escapeHtml(resource || "");
    var currentMode = mode === "onsite" ? "onsite" : "online";
    if (type === "document") {
      var safeKey = escapeHtml(fileKey || makeLessonFileKey());
      var existingResource = clean(resource || "");
      var hasExistingResource = uploadedResourceLooksValid(existingResource);
      var safeResource = escapeHtml(existingResource);
      var safeFileName = escapeHtml(fileName || (hasExistingResource ? displayNameFromResource(existingResource) : ""));
      var fileLabel = safeFileName !== "" ? safeFileName : "Importer le document";
      return '' +
        '<label class="course-builder-field course-builder-field--file">' +
          '<span>Document / exercice *</span>' +
          '<div class="course-builder-file-drop' + (hasExistingResource ? ' has-file' : '') + '" data-lesson-file-shell>' +
            '<input type="file" name="lesson_files[' + safeKey + ']" class="lesson-file-input" data-lesson-file-input accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.rar,.txt,.csv,image/*,application/pdf">' +
            '<span class="course-builder-file-icon"><i class="fa-solid fa-file-arrow-up"></i></span>' +
            '<span class="course-builder-file-copy">' +
              '<b class="lesson-file-name" data-file-name="' + safeFileName + '">' + fileLabel + '</b>' +
            '</span>' +
          '</div>' +
        '</label>' +
        '<input type="hidden" class="lesson-resource" value="' + (hasExistingResource ? safeResource : safeFileName) + '">';
    }
    if (type === "appointment") {
      return '' +
        '<label class="course-builder-field course-builder-field--meeting-mode">' +
          '<span>Mode du rendez-vous *</span>' +
          '<select class="lesson-meeting-mode course-builder-input">' +
            '<option value="online"' + (currentMode === "online" ? " selected" : "") + '>En ligne</option>' +
            '<option value="onsite"' + (currentMode === "onsite" ? " selected" : "") + '>Présentiel</option>' +
          '</select>' +
        '</label>' +
        '<input type="hidden" class="lesson-resource" value="">' +
        '<p class="course-builder-file-help">Aucun lien ni adresse n’est demandé ici. Le détail du rendez-vous se gère après validation du cours.</p>';
    }
    return '' +
      '<label class="course-builder-field">' +
        '<span>Lien YouTube *</span>' +
        '<input type="url" inputmode="url" autocomplete="off" pattern="https?://((www|m)\\.)?youtube\\.com/.+|https?://youtu\\.be/.+" title="Collez uniquement un lien YouTube valide." placeholder="https://www.youtube.com/watch?v=... ou https://youtu.be/..." value="' + value + '" class="lesson-resource course-builder-input lesson-resource--youtube">' +
      '</label>';
  }

  function examSubjectFileName(exam) {
    if (!exam) return "";
    var label = q(".exam-subject-file-name", exam);
    if (label) {
      var stored = clean(label.getAttribute("data-file-name"));
      if (stored !== "") return stored;
      var text = clean(label.textContent);
      if (text !== "" && text !== "Importer le sujet") return text;
    }
    return "";
  }

  function examSubjectUploadHtml(exam) {
    var subject = clean(exam && exam.subject);
    var explicitName = clean(exam && exam.subject_file_name);
    var displayName = explicitName || displayNameFromResource(subject);
    var hasExistingResource = uploadedResourceLooksValid(subject);
    var safeSubject = escapeHtml(subject);
    var safeName = escapeHtml(displayName);
    var label = displayName !== "" ? displayName : "Importer le sujet";
    return '' +
      '<label class="course-builder-exam-field course-builder-field--file">' +
        '<span><i class="fa-solid fa-file-arrow-up"></i> Document sujet à uploader *</span>' +
        '<div class="course-builder-file-drop course-builder-exam-file-drop' + (hasExistingResource ? ' has-file' : '') + '" data-exam-subject-file-shell>' +
          '<input type="file" name="exam_subject_file" class="exam-subject-file-input" data-exam-subject-file-input accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.rar,.txt,.csv,image/*,application/pdf">' +
          '<span class="course-builder-file-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>' +
          '<span class="course-builder-file-copy">' +
            '<b class="exam-subject-file-name" data-file-name="' + safeName + '">' + escapeHtml(label) + '</b>' +
            '<small>' + (hasExistingResource ? "Sujet déjà associé. Choisissez un fichier seulement pour le remplacer." : "PDF, Word, PowerPoint, Excel, ZIP ou image") + '</small>' +
          '</span>' +
        '</div>' +
        '<span class="course-builder-file-help">Ce fichier est le sujet transmis aux apprenants pour leur soumission.</span>' +
      '</label>' +
      '<input type="hidden" class="document-subject" value="' + safeSubject + '">';
  }

  function lessonHtml(item) {
    var type = mapLessonTypeToUi(clean(item && item.type) || "video");
    var title = escapeHtml(item && item.title ? item.title : "");
    var summaryTitle = escapeHtml(clean(item && item.title) || "Nouvelle leçon");
    var resource = item && item.resource ? item.resource : "";
    var descriptionText = clean(item && item.description);
    var description = escapeHtml(item && item.description ? item.description : "");
    var duration = numberValue(item && item.duration_min, type === "appointment" ? 60 : 0);
    var durationValue = duration > 0 ? String(duration) : "";
    var fileKey = clean(item && item.file_key) || makeLessonFileKey();
    var fileName = clean(item && item.file_name) || (type === "document" ? displayNameFromResource(resource) : "");

    return '' +
      '<div class="lesson-item course-builder-lesson" data-lesson-type="' + type + '" data-file-key="' + escapeHtml(fileKey) + '">' +
        '<div class="course-builder-lesson-head" data-toggle-lesson-head>' +
          '<span class="lesson-number course-builder-lesson-number">1.</span>' +
          '<div class="course-builder-lesson-summary">' +
            '<strong class="lesson-summary-title">' + summaryTitle + '</strong>' +
            '<span class="lesson-summary-subtitle">Leçon</span>' +
          '</div>' +
          '<div class="course-builder-lesson-head-meta">' +
            '<span class="lesson-type-pill">' + lessonTypeLabel(type) + '</span>' +
            '<span class="lesson-duration-pill' + (type === "appointment" ? " hidden" : "") + '">' + String(duration) + ' min</span>' +
            '<button type="button" data-remove-lesson class="course-builder-icon-button course-builder-icon-button--danger" aria-label="Supprimer la leçon"><i class="fa-solid fa-xmark"></i></button>' +
            '<button type="button" data-toggle-lesson class="course-builder-lesson-toggle" aria-expanded="true" aria-label="Replier la leçon"><i class="fa-solid fa-chevron-up"></i></button>' +
          '</div>' +
        '</div>' +
        '<div class="course-builder-lesson-body">' +
          '<div class="course-builder-lesson-top">' +
            '<label class="course-builder-field course-builder-field--title">' +
              '<span>Titre *</span>' +
              '<input type="text" value="' + title + '" class="lesson-title course-builder-input course-builder-input--strong" placeholder="Ex: Bienvenue, Installation, Quiz de révision">' +
            '</label>' +
            '<label class="course-builder-field course-builder-field--type">' +
              '<span>Type</span>' +
              '<select class="lesson-type-select course-builder-input" data-lesson-type-select>' +
            '<option value="video"' + (type === "video" ? " selected" : "") + '>Vidéo</option>' +
            '<option value="document"' + (type === "document" ? " selected" : "") + '>Document / exercice</option>' +
            '<option value="appointment"' + (type === "appointment" ? " selected" : "") + '>Rendez-vous</option>' +
              '</select>' +
            '</label>' +
            '<label class="course-builder-field course-builder-field--duration' + (type === "appointment" ? " hidden" : "") + '">' +
              '<span>Durée *</span>' +
              '<input type="number" min="1" required value="' + durationValue + '" class="lesson-duration course-builder-input" placeholder="15">' +
            '</label>' +
          '</div>' +
          '<div class="lesson-dynamic course-builder-dynamic">' + lessonDynamicHtml(type, resource, item && item.mode, fileKey, fileName) + '</div>' +
          '<details class="course-builder-lesson-notes' + (descriptionText !== "" ? " has-content" : "") + '">' +
            '<summary><span>Description / consignes</span><small class="lesson-description-preview">' + escapeHtml(descriptionText || "Optionnelle") + '</small><i class="fa-solid fa-chevron-down"></i></summary>' +
            '<textarea rows="2" class="lesson-description course-builder-textarea" placeholder="Objectifs, consignes ou contexte de cette leçon">' + description + '</textarea>' +
          '</details>' +
        '</div>' +
      '</div>';
  }

  function syncLessonSummary(lesson, fallbackIndex) {
    if (!lesson) return;
    var title = clean(q(".lesson-title", lesson) && q(".lesson-title", lesson).value);
    var type = q(".lesson-type-select", lesson) ? q(".lesson-type-select", lesson).value : "video";
    var duration = Math.max(0, numberValue(q(".lesson-duration", lesson) && q(".lesson-duration", lesson).value, type === "appointment" ? 60 : 0));
    var summaryTitle = q(".lesson-summary-title", lesson);
    var summarySubtitle = q(".lesson-summary-subtitle", lesson);
    var typePill = q(".lesson-type-pill", lesson);
    var durationPill = q(".lesson-duration-pill", lesson);
    var lessonNumber = q(".lesson-number", lesson);
    var notes = q(".course-builder-lesson-notes", lesson);
    var descriptionPreview = q(".lesson-description-preview", lesson);
    var description = clean(q(".lesson-description", lesson) && q(".lesson-description", lesson).value);

    if (summaryTitle) summaryTitle.textContent = title || "Nouvelle leçon";
    if (summarySubtitle) summarySubtitle.textContent = "Leçon " + String((fallbackIndex || 0) + 1);
    if (lessonNumber) lessonNumber.textContent = String((fallbackIndex || 0) + 1) + ".";
    if (typePill) {
      typePill.textContent = lessonTypeLabel(type);
    }
    if (durationPill) {
      durationPill.classList.toggle("hidden", type === "appointment");
      durationPill.textContent = String(duration) + ' min';
    }
    if (notes) notes.classList.toggle("has-content", description !== "");
    if (descriptionPreview) descriptionPreview.textContent = description || "Optionnelle";
  }

  function setLessonCollapsed(lesson, collapsed, markAuto) {
    if (!lesson) return;
    lesson.classList.toggle("is-collapsed", !!collapsed);
    var toggle = q("[data-toggle-lesson]", lesson);
    if (toggle) {
      toggle.setAttribute("aria-expanded", collapsed ? "false" : "true");
      toggle.setAttribute("aria-label", collapsed ? "Ouvrir la leçon" : "Replier la leçon");
    }
    if (markAuto) {
      lesson.setAttribute("data-auto-collapsed", "1");
    }
  }

  function toggleLesson(launcher) {
    var lesson = launcher && launcher.closest(".lesson-item");
    if (!lesson) return;
    lesson.removeAttribute("data-auto-collapsed");
    setLessonCollapsed(lesson, !lesson.classList.contains("is-collapsed"), false);
  }

  function syncLessonCollapseState(lesson, isComplete) {
    if (!lesson) return;
    lesson.classList.toggle("is-complete", !!isComplete);
    if (!isComplete) {
      lesson.removeAttribute("data-auto-collapsed");
      setLessonCollapsed(lesson, false, false);
      return;
    }
    lesson.removeAttribute("data-auto-collapsed");
  }

  function moduleHtml(module) {
    moduleSeq += 1;
    var moduleDescription = clean(module && module.description);
    return '' +
      '<div class="module-item course-builder-module animate-fadeIn" data-module-id="' + moduleSeq + '">' +
        '<div class="course-builder-module-header" data-toggle-module>' +
          '<div class="course-builder-module-main">' +
            '<span class="course-builder-drag-dots" aria-hidden="true"><i class="fa-solid fa-grip-vertical"></i></span>' +
            '<span class="module-number course-builder-module-number">1</span>' +
            '<div class="course-builder-module-title-wrap">' +
              '<label class="course-builder-module-title-field">' +
                '<span>Titre du module *</span>' +
                '<input type="text" value="' + escapeHtml(module.title || "") + '" class="module-title course-builder-module-title" placeholder="Ex: Introduction au cours">' +
              '</label>' +
              '<div class="course-builder-module-meta">' +
                '<span class="module-status-badge course-builder-status course-builder-status--incomplete">Incomplet</span>' +
                '<span><b class="module-lessons-count">0</b> <span class="module-lessons-label">leçon</span></span>' +
                '<span><b class="module-duration-total">0</b> min</span>' +
              '</div>' +
            '</div>' +
          '</div>' +
          '<div class="course-builder-module-actions">' +
            '<button type="button" data-add-lesson class="course-builder-add-lesson"><i class="fa-solid fa-plus"></i> Leçon</button>' +
            '<button type="button" data-remove-module class="course-builder-icon-button course-builder-icon-button--danger" aria-label="Supprimer le module"><i class="fa-solid fa-trash-can"></i></button>' +
            '<i class="fa-solid fa-chevron-up module-chevron course-builder-chevron"></i>' +
          '</div>' +
        '</div>' +
        '<div class="course-builder-module-content">' +
          '<div class="module-lessons course-builder-lessons"></div>' +
          '<details class="course-builder-module-notes' + (moduleDescription !== "" ? " has-content" : "") + '">' +
            '<summary>' +
              '<span>Description du module</span>' +
              '<small class="module-description-preview">' + escapeHtml(moduleDescription || "Optionnelle") + '</small>' +
              '<i class="fa-solid fa-chevron-down"></i>' +
            '</summary>' +
            '<textarea rows="2" class="module-description course-builder-textarea course-builder-textarea--module" placeholder="Résumé, objectifs ou consignes du module">' + escapeHtml(module.description || "") + '</textarea>' +
          '</details>' +
        '</div>' +
      '</div>';
  }

  function syncModuleDescriptionPreview(module) {
    if (!module) return;
    var details = q(".course-builder-module-notes", module);
    var preview = q(".module-description-preview", module);
    var textarea = q(".module-description", module);
    var value = clean(textarea && textarea.value);
    if (preview) preview.textContent = value || "Optionnelle";
    if (details) details.classList.toggle("has-content", value !== "");
  }

  function updateModuleSummary(module) {
    if (!module) return;
    var lessons = qa(".lesson-item", module);
    var lessonCount = lessons.length;
    var totalMinutes = 0;
    var isComplete = clean(q(".module-title", module) && q(".module-title", module).value) !== "" && lessonCount > 0;

      lessons.forEach(function (lesson, lessonIndex) {
        var type = q(".lesson-type-select", lesson) ? q(".lesson-type-select", lesson).value : "video";
        var title = clean(q(".lesson-title", lesson) && q(".lesson-title", lesson).value);
        var resource = clean(q(".lesson-resource", lesson) && q(".lesson-resource", lesson).value);
        var duration = Math.max(0, numberValue(q(".lesson-duration", lesson) && q(".lesson-duration", lesson).value, 0));
      var fileInput = q(".lesson-file-input", lesson);
      var hasUploadedDocument = fileInput && fileInput.files && fileInput.files.length;
      var hasExistingDocument = uploadedResourceLooksValid(resource);
      var lessonComplete = title !== "";
      totalMinutes += duration;
      syncLessonSummary(lesson, lessonIndex);

      if (title === "") {
        lessonComplete = false;
      }
      if (type !== "appointment" && duration < 1) {
        lessonComplete = false;
      }
      if (type === "video") {
        if (!youtubeUrlLooksValid(resource)) {
          lessonComplete = false;
        }
      }
      if (type === "document" && !hasUploadedDocument && !hasExistingDocument) {
        lessonComplete = false;
      }
      if (!lessonComplete) isComplete = false;
      syncLessonCollapseState(lesson, lessonComplete);
    });

    var lessonsCount = q(".module-lessons-count", module);
    var lessonsLabel = q(".module-lessons-label", module);
    var durationTotal = q(".module-duration-total", module);
    var status = q(".module-status-badge", module);
    if (lessonsCount) lessonsCount.textContent = String(lessonCount);
    if (lessonsLabel) lessonsLabel.textContent = lessonCount > 1 ? "leçons" : "leçon";
    if (durationTotal) durationTotal.textContent = String(totalMinutes);
    syncModuleDescriptionPreview(module);
    module.classList.toggle("is-ready", isComplete);
    module.classList.toggle("is-incomplete", !isComplete);
    if (status) {
      status.textContent = isComplete ? "Prêt" : "Incomplet";
      status.classList.toggle("course-builder-status--ready", isComplete);
      status.classList.toggle("course-builder-status--incomplete", !isComplete);
    }
  }

  function updateModuleSummaries() {
    qa("#modules-container .module-item").forEach(updateModuleSummary);
  }

  function renumberModules() {
    qa("#modules-container .module-item").forEach(function (module, moduleIndex) {
      var number = q(".module-number", module);
      if (number) number.textContent = String(moduleIndex + 1);
      qa(".lesson-item", module).forEach(function (lesson, lessonIndex) {
        syncLessonSummary(lesson, lessonIndex);
      });
      updateModuleSummary(module);
    });
    var empty = qa("#modules-container .module-item").length === 0;
    var message = q("#no-modules-message");
    if (message) message.classList.toggle("hidden", !empty);
  }

  function qcmOptionLabel(index) {
    var value = Math.max(0, numberValue(index, 0));
    if (value < 26) {
      return String.fromCharCode(97 + value);
    }
    return String(value + 1);
  }

  function optionRowHtml(option, index, optionInputType, group, hasCorrect) {
    var optionLabel = qcmOptionLabel(index);
    return '' +
      '<div class="option-row course-builder-option">' +
        '<span class="option-number course-builder-option-number">' + optionLabel + '</span>' +
        '<input type="' + optionInputType + '" name="' + group + '" class="course-builder-option-check"' + ((option.correct || (!hasCorrect && index === 0)) ? " checked" : "") + '>' +
        '<input type="text" value="' + escapeHtml(option.text || "") + '" class="option-text course-builder-option-input" placeholder="Proposition ' + optionLabel + '">' +
        '<button type="button" data-remove-option class="course-builder-mini-action course-builder-mini-action--danger" aria-label="Supprimer la proposition"><i class="fa-solid fa-xmark"></i></button>' +
      '</div>';
  }

  function questionHtml(question, questionIndex) {
    questionSeq += 1;
    var group = "qcm_question_" + questionSeq;
    var options = asArray(question && question.options);
    var qType = clean(question && question.type) || "single";
    if (["single", "multiple", "text"].indexOf(qType) === -1) qType = "single";
    if (options.length < 2) {
      options = [{ text: "", correct: true }, { text: "", correct: false }];
    }
    var hasCorrect = options.some(function (option) { return !!option.correct; });
    var optionInputType = qType === "multiple" ? "checkbox" : "radio";
    var visibleIndex = Math.max(1, (questionIndex || 0) + 1);
    var optionHtml = options.slice(0, 6).map(function (option, index) {
      return optionRowHtml(option, index, optionInputType, group, hasCorrect);
    }).join("");

    return '' +
      '<div class="question-item course-builder-question" data-question-type="' + qType + '">' +
        '<div class="course-builder-question-head">' +
          '<span class="question-number course-builder-question-number">' + String(visibleIndex) + '</span>' +
          '<label class="course-builder-question-field course-builder-question-field--title">' +
            '<span><i class="fa-solid fa-heading"></i> Question <b class="question-number-label">' + String(visibleIndex) + '</b> *</span>' +
            '<input type="text" placeholder="Intitulé de la question" value="' + escapeHtml(question && question.text ? question.text : "") + '" class="question-text course-builder-question-input">' +
          '</label>' +
          '<label class="course-builder-question-field course-builder-question-field--type">' +
            '<span><i class="fa-solid fa-list-check"></i> Type</span>' +
            '<select class="question-type-select course-builder-question-input" data-question-type-select>' +
              '<option value="single"' + (qType === "single" ? " selected" : "") + '>Choix unique</option>' +
              '<option value="multiple"' + (qType === "multiple" ? " selected" : "") + '>Choix multiple</option>' +
              '<option value="text"' + (qType === "text" ? " selected" : "") + '>Réponse texte</option>' +
            '</select>' +
          '</label>' +
          '<button type="button" data-remove-question class="course-builder-icon-button course-builder-icon-button--danger" aria-label="Supprimer la question"><i class="fa-solid fa-trash-can"></i></button>' +
        '</div>' +
        '<div class="options course-builder-options' + (qType === "text" ? " hidden" : "") + '">' + optionHtml + '</div>' +
        '<button type="button" data-add-option class="course-builder-add-inline course-builder-add-inline--emerald' + (qType === "text" ? " hidden" : "") + '"><i class="fa-solid fa-plus"></i> Ajouter une proposition</button>' +
      '</div>';
  }

  function versionHtml(label, variant, questions) {
    var list = asArray(questions);
    if (!list.length) list = [{ text: "", options: [{ text: "", correct: true }, { text: "", correct: false }] }];
    return '' +
      '<div class="qcm-version course-builder-qcm-version" data-version="' + variant + '">' +
        '<div class="course-builder-qcm-version-head">' +
          '<span class="course-builder-version-letter">' + label + '</span>' +
          '<div>' +
            '<h4>Version ' + label + '</h4>' +
            '<p>Construisez cette variante, puis passez à la version suivante.</p>' +
          '</div>' +
        '</div>' +
        '<div class="questions-container course-builder-questions">' + list.map(function (question, index) { return questionHtml(question, index); }).join("") + '</div>' +
        '<button type="button" data-add-question class="course-builder-add-inline course-builder-add-inline--emerald"><i class="fa-solid fa-plus"></i> Ajouter une question</button>' +
      '</div>';
  }

  function versionTabHtml(label, variant) {
    return '' +
      '<button type="button" class="course-builder-version-tab" data-qcm-version-tab="' + variant + '">' +
        '<span>' + label + '</span>' +
        '<b>Version ' + label + '</b>' +
      '</button>';
  }

  function examTitleValue(exam) {
    var title = clean(exam && exam.title);
    return title === "" || /^examen\s+1$/i.test(title) ? "Évaluation finale" : title;
  }

  function examHasEditableData(exam) {
    if (!exam || typeof exam !== "object") return false;
    var title = clean(exam.title).toLowerCase();
    if (title !== "" && title !== "évaluation finale" && title !== "examen 1") return true;
    if (clean(exam.description) !== "" || clean(exam.subject) !== "") return true;
    if (clean(exam.meeting_link) !== "" || clean(exam.meeting_place) !== "") return true;
    var versions = exam.versions && typeof exam.versions === "object" ? exam.versions : {};
    return [1, 2, 3].some(function (variant) {
      return asArray(versions[String(variant)] || versions[variant]).some(function (question) {
        if (!question || typeof question !== "object") return false;
        if (clean(question.text) !== "") return true;
        return asArray(question.options).some(function (option) {
          return option && typeof option === "object" && clean(option.text) !== "";
        });
      });
    });
  }

  function lessonHasEditableData(lesson) {
    if (!lesson || typeof lesson !== "object") return false;

    var title = clean(q(".lesson-title", lesson) && q(".lesson-title", lesson).value);
    var description = clean(q(".lesson-description", lesson) && q(".lesson-description", lesson).value);
    var resource = clean(q(".lesson-resource", lesson) && q(".lesson-resource", lesson).value);
    var duration = Math.max(0, numberValue(q(".lesson-duration", lesson) && q(".lesson-duration", lesson).value, 0));
    var fileInput = q(".lesson-file-input", lesson);
    var type = clean(q(".lesson-type-select", lesson) && q(".lesson-type-select", lesson).value) || clean(lesson.getAttribute("data-lesson-type"));

    if (title !== "" || description !== "" || resource !== "" || duration > 0) {
      return true;
    }

    if (fileInput && fileInput.files && fileInput.files.length) {
      return true;
    }

    return type !== "" && type !== "video";
  }

  function moduleHasEditableData(module) {
    if (!module || typeof module !== "object") return false;

    if (clean(q(".module-title", module) && q(".module-title", module).value) !== "") {
      return true;
    }
    if (clean(q(".module-description", module) && q(".module-description", module).value) !== "") {
      return true;
    }

    return qa(".lesson-item", module).some(lessonHasEditableData);
  }

  function countMeaningfulModules() {
    return qa("#modules-container .module-item").filter(moduleHasEditableData).length;
  }

  function countMeaningfulExams() {
    return qa("#exams-container .exam-item").filter(examHasEditableData).length;
  }

  function phaseProgressInfo(phase) {
    if (phase === 1) {
      var presentationComplete = !blockingIssueForStep(2);
      return {
        complete: presentationComplete,
        label: presentationComplete ? "Prêt" : "À faire"
      };
    }

    if (phase === 2) {
      var commercialComplete = !blockingIssueForStep(4);
      return {
        complete: commercialComplete,
        label: commercialComplete ? "Prêt" : "À faire"
      };
    }

    if (phase === 3) {
      var moduleCount = countMeaningfulModules();
      return {
        complete: moduleCount > 0 && !blockingIssueForStep(6),
        label: moduleCount > 0 ? countLabel(moduleCount, "module", "modules") : "À faire"
      };
    }

    if (phase === 4) {
      var examCount = countMeaningfulExams();
      return {
        complete: examCount > 0 && !blockingIssueForStep(8),
        label: examCount > 0 ? countLabel(examCount, "examen", "examens") : "À faire"
      };
    }

    return {
      complete: false,
      label: "À faire"
    };
  }

  function qcmQuestionHasData(question) {
    if (!question || typeof question !== "object") return false;
    if (clean(question.text) !== "") return true;
    return asArray(question.options).some(function (option) {
      return option && typeof option === "object" && clean(option.text) !== "";
    });
  }

  function countQcmFilledVersions(versions, versionCount) {
    var count = 0;
    for (var variant = 1; variant <= versionCount; variant++) {
      if (asArray(versions[String(variant)] || versions[variant]).some(qcmQuestionHasData)) {
        count += 1;
      }
    }
    return count;
  }

  function examHtml(exam) {
    examSeq += 1;
    var type = mapExamTypeToUi(clean(exam.type) || "qcm");
    var versions = exam.versions && typeof exam.versions === "object" ? exam.versions : {};
    var versionCount = numberValue(exam.version_count, 3);
    if (!exam.version_count) {
      versionCount = [1, 2, 3].reduce(function (max, variant) {
        return asArray(versions[String(variant)] || versions[variant]).length ? variant : max;
      }, 3);
    }
    versionCount = Math.max(1, Math.min(3, versionCount));
    var filledVersions = countQcmFilledVersions(versions, versionCount);
    var qcmStatus = filledVersions > 0
      ? String(filledVersions) + "/" + String(versionCount) + " version(s) prêtes"
      : "Aucune version prête";

    return '' +
      '<div class="exam-item course-builder-exam animate-fadeIn" data-exam-id="' + examSeq + '">' +
        '<div class="course-builder-exam-header" data-toggle-exam>' +
          '<div class="course-builder-exam-heading">' +
            '<span class="course-builder-exam-icon"><i class="fa-solid fa-clipboard-check"></i></span>' +
            '<div class="course-builder-exam-title-wrap">' +
              '<span class="course-builder-exam-kicker">Évaluation finale unique</span>' +
              '<input type="text" value="' + escapeHtml(examTitleValue(exam)) + '" class="exam-title course-builder-exam-title" placeholder="Titre de l’évaluation">' +
            '</div>' +
          '</div>' +
          '<div class="course-builder-exam-controls">' +
            '<span class="course-builder-exam-pill"><i class="fa-solid fa-lock"></i> Un seul examen</span>' +
            '<select class="exam-type-select course-builder-exam-select" data-exam-type-select>' +
              '<option value="qcm"' + (type === "qcm" ? " selected" : "") + '>QCM</option>' +
              '<option value="document"' + (type === "document" ? " selected" : "") + '>Document à soumettre</option>' +
              '<option value="appointment"' + (type === "appointment" ? " selected" : "") + '>Rendez-vous</option>' +
            '</select>' +
            '<i class="fa-solid fa-chevron-down exam-chevron course-builder-chevron"></i>' +
          '</div>' +
        '</div>' +
        '<div class="exam-content course-builder-exam-content">' +
          '<div class="course-builder-exam-grid">' +
            '<label class="course-builder-exam-field course-builder-exam-field--wide">' +
              '<span>Description / consignes</span>' +
              '<textarea rows="3" class="exam-description course-builder-exam-input course-builder-exam-textarea" placeholder="Expliquez les attentes, la notation ou les consignes.">' + escapeHtml(exam.description || "") + '</textarea>' +
            '</label>' +
            '<label class="course-builder-exam-field">' +
              '<span>Durée (min)</span>' +
              '<input type="number" min="0" value="' + String(numberValue(exam.duration_min, type === "qcm" ? 45 : 0)) + '" class="exam-duration course-builder-exam-input" placeholder="45">' +
            '</label>' +
            '<label class="course-builder-exam-field">' +
              '<span>Note minimale (%)</span>' +
              '<input type="number" min="0" max="100" value="' + String(numberValue(exam.min_score, 50)) + '" class="exam-min-score course-builder-exam-input" placeholder="50">' +
            '</label>' +
          '</div>' +
          '<div class="exam-qcm-section course-builder-exam-pane">' +
            '<div class="course-builder-qcm-setup">' +
              '<div class="course-builder-qcm-setup-copy">' +
                '<span class="course-builder-qcm-setup-kicker">QCM</span>' +
                '<h4>Questions, versions et import</h4>' +
                '<p>Tout se remplit directement ici.</p>' +
              '</div>' +
              '<div class="course-builder-qcm-setup-side">' +
                '<span class="course-builder-qcm-setup-status">' + escapeHtml(qcmStatus) + '</span>' +
              '</div>' +
            '</div>' +
            '<div class="course-builder-qcm-toolbar">' +
              '<div class="course-builder-qcm-config">' +
                '<label class="course-builder-exam-field course-builder-exam-field--compact">' +
                  '<span>Versions QCM</span>' +
                  '<select class="qcm-version-count course-builder-exam-input" data-qcm-version-count>' +
                    '<option value="1"' + (versionCount === 1 ? " selected" : "") + '>1 version</option>' +
                    '<option value="2"' + (versionCount === 2 ? " selected" : "") + '>2 versions</option>' +
                    '<option value="3"' + (versionCount === 3 ? " selected" : "") + '>3 versions</option>' +
                  '</select>' +
                '</label>' +
                '<label class="course-builder-exam-field course-builder-exam-field--compact">' +
                  '<span>Points / question</span>' +
                  '<input type="number" min="1" value="' + String(numberValue(exam.points_per_question, 1)) + '" class="points-per-question course-builder-exam-input">' +
                '</label>' +
              '</div>' +
              '<div class="course-builder-qcm-import">' +
                '<input type="file" class="course-builder-qcm-import-input" data-qcm-import-input accept=".csv,.xlsx,.xls">' +
                '<button type="button" class="course-builder-qcm-import-btn" data-qcm-import-button><i class="fa-solid fa-file-import"></i> Importer</button>' +
                '<a class="course-builder-qcm-template" href="../assets/samples/qcm_import_template.xlsx" download>Modèle Excel</a>' +
                '<span class="course-builder-qcm-import-status" data-qcm-import-status></span>' +
              '</div>' +
              '<div class="course-builder-version-tabs" role="tablist" aria-label="Versions QCM">' +
                versionTabHtml("A", 1) +
                versionTabHtml("B", 2) +
                versionTabHtml("C", 3) +
              '</div>' +
            '</div>' +
            '<div class="qcm-versions-container course-builder-qcm-versions">' +
              versionHtml("A", 1, versions["1"] || versions[1]) +
              versionHtml("B", 2, versions["2"] || versions[2]) +
              versionHtml("C", 3, versions["3"] || versions[3]) +
            '</div>' +
            '<div class="course-builder-version-footer">' +
              '<button type="button" class="course-builder-version-nav" data-qcm-version-prev><i class="fa-solid fa-arrow-left"></i> Version précédente</button>' +
              '<span class="course-builder-version-progress" data-qcm-version-progress>Version A sur ' + String(versionCount) + '</span>' +
              '<button type="button" class="course-builder-version-nav course-builder-version-nav--primary" data-qcm-version-next>Version suivante <i class="fa-solid fa-arrow-right"></i></button>' +
            '</div>' +
          '</div>' +
          '<div class="exam-document-section course-builder-exam-pane hidden">' +
            '<div class="course-builder-document-intro">' +
              '<span><i class="fa-solid fa-file-circle-check"></i></span>' +
              '<div>' +
                '<h4>Document à soumettre</h4>' +
                '<p>L’apprenant remettra son travail. Ajoutez ici le document sujet à télécharger.</p>' +
              '</div>' +
            '</div>' +
            examSubjectUploadHtml(exam) +
          '</div>' +
          '<div class="exam-appointment-section course-builder-exam-pane hidden">' +
            '<div class="course-builder-appointment-card course-builder-appointment-card--simple">' +
              '<div class="course-builder-document-intro">' +
                '<span><i class="fa-solid fa-calendar-check"></i></span>' +
                '<div>' +
                  '<h4>Rendez-vous</h4>' +
                  '<p>Choisissez seulement le format. Les détails pratiques se gèrent après validation du cours.</p>' +
                '</div>' +
              '</div>' +
              '<label class="course-builder-exam-field course-builder-exam-field--compact">' +
                '<span><i class="fa-solid fa-calendar-check"></i> Format du rendez-vous</span>' +
                '<select class="meeting-mode course-builder-exam-input">' +
                  '<option value="online"' + ((exam.meeting_mode || "online") === "online" ? " selected" : "") + '>En ligne</option>' +
                  '<option value="onsite"' + (exam.meeting_mode === "onsite" ? " selected" : "") + '>Présentiel</option>' +
                '</select>' +
              '</label>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>';
  }

  function syncExamType(exam) {
    var select = q(".exam-type-select", exam);
    var type = select ? select.value : "qcm";
    var qcm = q(".exam-qcm-section", exam);
    var documentSection = q(".exam-document-section", exam);
    var appointmentSection = q(".exam-appointment-section", exam);
    if (qcm) qcm.classList.toggle("hidden", type !== "qcm");
    if (documentSection) documentSection.classList.toggle("hidden", type !== "document");
    if (appointmentSection) appointmentSection.classList.toggle("hidden", type !== "appointment");
    qa(".exam-subject-file-input", exam).forEach(function (input) {
      input.disabled = type !== "document";
    });
  }

  function setActiveQcmVersion(exam, variant) {
    if (!exam) return;
    var select = q(".qcm-version-count", exam);
    var count = Math.max(1, Math.min(3, numberValue(select ? select.value : 3, 3)));
    var nextVariant = Math.max(1, Math.min(count, numberValue(variant, 1)));
    exam.setAttribute("data-active-version", String(nextVariant));
    syncQcmVersionTabs(exam);
  }

  function syncQcmVersionTabs(exam) {
    if (!exam) return;
    var select = q(".qcm-version-count", exam);
    var count = Math.max(1, Math.min(3, numberValue(select ? select.value : 3, 3)));
    var active = Math.max(1, Math.min(count, numberValue(exam.getAttribute("data-active-version"), 1)));
    exam.setAttribute("data-active-version", String(active));

    qa(".qcm-version", exam).forEach(function (version) {
      var variant = numberValue(version.getAttribute("data-version"), 1);
      version.classList.toggle("hidden", variant > count || variant !== active);
      version.setAttribute("aria-hidden", variant === active ? "false" : "true");
    });

    qa("[data-qcm-version-tab]", exam).forEach(function (tab) {
      var variant = numberValue(tab.getAttribute("data-qcm-version-tab"), 1);
      var visible = variant <= count;
      tab.classList.toggle("hidden", !visible);
      tab.classList.toggle("is-active", visible && variant === active);
      tab.setAttribute("aria-selected", visible && variant === active ? "true" : "false");
      tab.disabled = !visible;
    });

    var progress = q("[data-qcm-version-progress]", exam);
    if (progress) {
      progress.textContent = "Version " + String.fromCharCode(64 + active) + " sur " + String(count);
    }

    var prev = q("[data-qcm-version-prev]", exam);
    var next = q("[data-qcm-version-next]", exam);
    if (prev) prev.disabled = active <= 1;
    if (next) next.disabled = active >= count;
  }

  function syncVersionCount(exam) {
    var select = q(".qcm-version-count", exam);
    var count = Math.max(1, Math.min(3, numberValue(select ? select.value : 3, 3)));
    var active = Math.max(1, Math.min(count, numberValue(exam.getAttribute("data-active-version"), 1)));
    exam.setAttribute("data-active-version", String(active));
    syncQcmVersionTabs(exam);
    renumberQuestions(exam);
  }

  function syncQuestionType(question) {
    var select = q(".question-type-select", question);
    var type = select ? select.value : "single";
    if (["single", "multiple", "text"].indexOf(type) === -1) type = "single";
    question.setAttribute("data-question-type", type);
    var options = q(".options", question);
    var addButton = q("[data-add-option]", question);
    if (options) options.classList.toggle("hidden", type === "text");
    if (addButton) addButton.classList.toggle("hidden", type === "text");
    qa(".option-row input[type='radio'], .option-row input[type='checkbox']", question).forEach(function (input, index) {
      input.type = type === "multiple" ? "checkbox" : "radio";
      if (type === "single") {
        input.name = input.name || ("qcm_question_" + questionSeq);
        if (index > 0 && input.checked && qa(".option-row input:checked", question).length > 1) {
          input.checked = false;
        }
      }
    });
    renumberQuestions(question);
  }

  function renumberQuestions(root) {
    var scope = root || document;
    var versions = [];
    if (scope.matches && scope.matches(".qcm-version")) {
      versions = [scope];
    } else if (scope.matches && scope.matches(".question-item")) {
      var version = scope.closest(".qcm-version");
      versions = version ? [version] : [];
    } else {
      versions = qa(".qcm-version", scope);
    }

    versions.forEach(function (version) {
      qa(".question-item", version).forEach(function (question, questionIndex) {
        var number = q(".question-number", question);
        var label = q(".question-number-label", question);
        var questionText = q(".question-text", question);
        var visibleNumber = String(questionIndex + 1);
        if (number) number.textContent = visibleNumber;
        if (label) label.textContent = visibleNumber;
        if (questionText) questionText.setAttribute("placeholder", "Question " + visibleNumber);
        qa(".option-row", question).forEach(function (option, optionIndex) {
          var optionNumber = q(".option-number", option);
          var optionText = q(".option-text", option);
          var visibleOptionLabel = qcmOptionLabel(optionIndex);
          if (optionNumber) optionNumber.textContent = visibleOptionLabel;
          if (optionText) optionText.setAttribute("placeholder", "Proposition " + visibleOptionLabel);
        });
      });
    });
  }

  function renumberExams() {
    var container = q("#exams-container");
    var exams = container ? qa(".exam-item", container) : [];
    exams.slice(1).forEach(function (exam) {
      exam.remove();
    });
    qa("#exams-container .exam-item").forEach(function (exam) {
      syncExamType(exam);
      syncVersionCount(exam);
      renumberQuestions(exam);
    });
    var empty = qa("#exams-container .exam-item").length === 0;
    var message = q("#no-exam-message");
    if (message) message.classList.toggle("hidden", !empty);
  }

  function qcmImportStatusNode(exam) {
    return q("[data-qcm-import-status]", exam);
  }

  function setQcmImportStatus(exam, message, tone) {
    var status = qcmImportStatusNode(exam);
    if (!status) return;
    status.textContent = message || "";
    status.classList.remove("is-ok", "is-error");
    if (tone === "ok") {
      status.classList.add("is-ok");
    } else if (tone === "error") {
      status.classList.add("is-error");
    }
  }

  function splitCsvLine(line, delimiter) {
    var result = [];
    var curr = "";
    var inQuotes = false;
    for (var i = 0; i < line.length; i++) {
      var c = line[i];
      if (c === '"') {
        if (inQuotes && line[i + 1] === '"') {
          curr += '"';
          i += 1;
        } else {
          inQuotes = !inQuotes;
        }
      } else if (c === delimiter && !inQuotes) {
        result.push(curr.trim());
        curr = "";
      } else {
        curr += c;
      }
    }
    result.push(curr.trim());
    return result;
  }

  async function readQcmImportText(file) {
    if (!file) return "";
    var fileName = String(file.name || "").toLowerCase();
    var isExcel = fileName.endsWith(".xlsx") || fileName.endsWith(".xls");
    if (!isExcel) {
      return file.text();
    }
    if (typeof window.XLSX === "undefined" || !window.XLSX?.utils?.sheet_to_csv) {
      throw new Error("excel_lib_missing");
    }
    var buffer = await file.arrayBuffer();
    var workbook = window.XLSX.read(buffer, { type: "array" });
    var firstSheet = workbook && workbook.SheetNames && workbook.SheetNames[0];
    if (!firstSheet || !workbook.Sheets || !workbook.Sheets[firstSheet]) {
      return "";
    }
    return window.XLSX.utils.sheet_to_csv(workbook.Sheets[firstSheet], {
      FS: ";",
      RS: "\n",
      blankrows: false
    });
  }

  function importQcmCsvText(exam, text) {
    if (!exam || clean(text) === "") return 0;

    var lines = String(text).replace(/^\uFEFF/, "").replace(/\r/g, "").split("\n").filter(function (line) {
      return clean(line) !== "";
    });
    if (!lines.length) return 0;

    var delimiter = lines[0].indexOf(";") !== -1 ? ";" : ",";
    var headers = splitCsvLine(lines[0], delimiter).map(function (header) {
      return clean(header).toLowerCase();
    });
    var idx = function (name) {
      return headers.indexOf(name);
    };

    var hQuestion = idx("question");
    if (hQuestion < 0) return 0;

    var hVariant = idx("variant");
    var hType = idx("type");
    var hCorrect = idx("correct");
    var optionIndexes = ["option_a", "option_b", "option_c", "option_d", "option_e", "option_f"].map(idx).filter(function (value) {
      return value >= 0;
    });
    var defaultTargets = ["1", "2", "3"];
    var variantMap = { a: "1", b: "2", c: "3", 1: "1", 2: "2", 3: "3" };
    var imported = 0;
    var firstImportedVariant = "";

    for (var i = 1; i < lines.length; i += 1) {
      var cols = splitCsvLine(lines[i], delimiter);
      var questionText = clean(cols[hQuestion] || "");
      if (questionText === "") continue;

      var typeRaw = clean(hType >= 0 ? cols[hType] : "single") || "single";
      var qType = ["single", "multiple", "text"].indexOf(typeRaw.toLowerCase()) !== -1 ? typeRaw.toLowerCase() : "single";
      var variantRaw = clean(hVariant >= 0 ? cols[hVariant] : "");
      var lowerVariant = variantRaw.toLowerCase();
      var targetVariants = defaultTargets.slice();

      if (["1", "2", "3"].indexOf(variantRaw) !== -1) {
        targetVariants = [variantRaw];
      } else if (variantMap[lowerVariant]) {
        targetVariants = [variantMap[lowerVariant]];
      } else if (["all", "a+b+c", "a,b,c", "a|b|c", "abc", "123"].indexOf(lowerVariant) !== -1) {
        targetVariants = defaultTargets.slice();
      }

      var options = [];
      optionIndexes.forEach(function (optionIndex) {
        var optionText = clean(cols[optionIndex] || "");
        if (optionText !== "") {
          options.push({ text: optionText, correct: false });
        }
      });
      if (qType !== "text" && options.length < 2) {
        continue;
      }

      var correctRaw = clean(hCorrect >= 0 ? cols[hCorrect] : "");
      if (correctRaw !== "" && qType !== "text") {
        correctRaw.toUpperCase().split(/[|,; ]+/).filter(Boolean).forEach(function (token) {
          var numeric = parseInt(token, 10);
          var optionIndex = -1;
          if (Number.isFinite(numeric)) {
            optionIndex = numeric - 1;
          } else if (/^[A-F]$/.test(token)) {
            optionIndex = token.charCodeAt(0) - 65;
          }
          if (optionIndex >= 0 && optionIndex < options.length) {
            options[optionIndex].correct = true;
          }
        });
      }
      if (qType !== "text" && !options.some(function (option) { return !!option.correct; })) {
        options[0].correct = true;
      }

      targetVariants.forEach(function (variant) {
        var version = q('.qcm-version[data-version="' + String(variant) + '"]', exam);
        var container = version ? q(".questions-container", version) : null;
        if (!container) return;
        if (firstImportedVariant === "") {
          firstImportedVariant = String(variant);
        }
        container.insertAdjacentHTML("beforeend", questionHtml({ text: questionText, type: qType, options: options }, qa(".question-item", container).length));
        imported += 1;
      });
    }

    if (imported > 0) {
      renumberQuestions(exam);
      if (firstImportedVariant !== "") {
        setActiveQcmVersion(exam, firstImportedVariant);
      }
      serializeBuilders();
    }

    return imported;
  }

  window.importQcmFile = async function (button) {
    var exam = button && button.closest(".exam-item");
    if (!exam) return 0;
    var input = q("[data-qcm-import-input]", exam);
    var file = input && input.files && input.files.length ? input.files[0] : null;
    if (!file) {
      setQcmImportStatus(exam, "Choisissez un fichier CSV ou Excel avant l'import.", "error");
      return 0;
    }

    try {
      var text = await readQcmImportText(file);
      var count = importQcmCsvText(exam, text);
      if (count > 0) {
        setQcmImportStatus(exam, String(count) + " question(s) importée(s).", "ok");
        showToast("Import QCM terminé.", "info");
        return count;
      }
      setQcmImportStatus(exam, "Aucune question importée. Vérifiez les colonnes du fichier.", "error");
      return 0;
    } catch (err) {
      if (err && err.message === "excel_lib_missing") {
        setQcmImportStatus(exam, "Import Excel indisponible (librairie manquante).", "error");
        return 0;
      }
      setQcmImportStatus(exam, "Échec d'import. Réessayez avec un CSV valide.", "error");
      return 0;
    }
  };

  function renderModules(modules) {
    var container = q("#modules-container");
    if (!container) return;
    container.innerHTML = "";
    asArray(modules).forEach(function (module) {
      addModule(module);
    });
    if (!qa(".module-item", container).length) addModule(defaultModule());
    renumberModules();
  }

  function renderExams(exams) {
    var container = q("#exams-container");
    if (!container) return;
    container.innerHTML = "";
    var validExams = asArray(exams).filter(function (exam) {
      return exam && typeof exam === "object";
    });
    var firstExam = validExams.find(examHasEditableData) || validExams[0];
    addExam(firstExam || defaultExam());
    if (!qa(".exam-item", container).length) addExam(defaultExam());
    renumberExams();
  }

  function serializeContent() {
    var totalMinutes = 0;
    var modules = qa("#modules-container .module-item").map(function (module, moduleIndex) {
      var explicitTitle = clean(q(".module-title", module) && q(".module-title", module).value);
      var title = explicitTitle || ("Module " + (moduleIndex + 1));
      var moduleDescription = clean(q(".module-description", module) && q(".module-description", module).value);
      var items = qa(".lesson-item", module).map(function (lesson) {
        var uiType = q(".lesson-type-select", lesson) ? q(".lesson-type-select", lesson).value : "video";
        var type = mapLessonTypeToPayload(uiType);
        var titleInput = q(".lesson-title", lesson);
        var resourceInput = q(".lesson-resource", lesson);
        var descriptionInput = q(".lesson-description", lesson);
        var durationInput = q(".lesson-duration", lesson);
        var modeInput = q(".lesson-meeting-mode", lesson);
        var fileInput = q(".lesson-file-input", lesson);
        var fileKey = clean(lesson.getAttribute("data-file-key")) || makeLessonFileKey();
        var lessonTitle = clean(titleInput && titleInput.value);
        var fileName = type === "document" ? lessonFileName(lesson) : "";
        var existingResource = clean(resourceInput && resourceInput.value);
        var resource = type === "document" ? existingResource : clean(resourceInput && resourceInput.value);
        if (type === "meeting") {
          resource = "";
        }
        var lessonDescription = clean(descriptionInput && descriptionInput.value);
        var durationMin = Math.max(0, numberValue(durationInput && durationInput.value, type === "meeting" ? 60 : 0));
        var mode = modeInput && modeInput.value === "onsite" ? "onsite" : "online";
        if (type === "meeting" && durationMin < 1) {
          durationMin = 60;
        }
        if (type === "document" && fileInput && fileInput.files && fileInput.files.length) {
          fileName = clean(fileInput.files[0].name);
          resource = fileName;
        } else if (type === "document" && resource === "" && fileName !== "") {
          resource = fileName;
        }
        if (!clean(lesson.getAttribute("data-file-key"))) {
          lesson.setAttribute("data-file-key", fileKey);
        }

        if (lessonTitle === "" && resource === "" && lessonDescription === "" && fileName === "") return null;
        totalMinutes += durationMin;

        return {
          type: type,
          title: lessonTitle || (type === "meeting" ? "Rendez-vous" : "Leçon"),
          description: lessonDescription,
          resource: resource,
          duration_min: durationMin,
          mode: mode,
          file_key: type === "document" ? fileKey : "",
          file_name: type === "document" ? fileName : ""
        };
      }).filter(Boolean);

      if (explicitTitle === "" && moduleDescription === "" && items.length === 0) return null;
      return { title: title, description: moduleDescription, items: items };
    }).filter(Boolean);

    var field = q("#content_payload");
    if (field) field.value = JSON.stringify({ modules: modules });
    var durationField = q("#duration_hours");
    if (durationField) {
      var hours = totalMinutes > 0 ? Math.max(0.25, Math.round((totalMinutes / 60) * 100) / 100) : 1;
      durationField.value = String(hours);
    }
  }

  function serializeQuestion(question) {
    var questionText = clean(q(".question-text", question) && q(".question-text", question).value);
    var typeSelect = q(".question-type-select", question);
    var questionType = typeSelect ? typeSelect.value : (question.getAttribute("data-question-type") || "single");
    if (["single", "multiple", "text"].indexOf(questionType) === -1) questionType = "single";
    var options = qa(".option-row", question).map(function (row) {
      var text = clean(q(".option-text", row) && q(".option-text", row).value);
      if (text === "") return null;
      return {
        text: text,
        correct: !!(q('input[type="radio"], input[type="checkbox"]', row) && q('input[type="radio"], input[type="checkbox"]', row).checked)
      };
    }).filter(Boolean);

    if (questionText === "" && options.length === 0) return null;
    return {
      text: questionText,
      type: questionType,
      options: questionType === "text" ? [] : options
    };
  }

  function serializeExams() {
    var exams = qa("#exams-container .exam-item").slice(0, 1).map(function (exam) {
      var uiType = q(".exam-type-select", exam) ? q(".exam-type-select", exam).value : "qcm";
      var type = mapExamTypeToPayload(uiType);
      var title = clean(q(".exam-title", exam) && q(".exam-title", exam).value) || "Évaluation finale";
      var durationMin = Math.max(0, numberValue(q(".exam-duration", exam) && q(".exam-duration", exam).value, type === "qcm" ? 45 : 0));
      var minScore = Math.max(0, numberValue(q(".exam-min-score", exam) && q(".exam-min-score", exam).value, 0));
      var payload = {
        title: title,
        type: type,
        description: clean(q(".exam-description", exam) && q(".exam-description", exam).value),
        duration_min: durationMin,
        min_score: minScore,
        points_per_question: Math.max(1, numberValue(q(".points-per-question", exam) && q(".points-per-question", exam).value, 1)),
        subject: "",
        meeting_mode: "online",
        meeting_link: "",
        meeting_place: "",
        versions: {}
      };

      if (type === "qcm") {
        var versionCount = Math.max(1, Math.min(3, numberValue(q(".qcm-version-count", exam) && q(".qcm-version-count", exam).value, 3)));
        payload.version_count = versionCount;
        qa(".qcm-version", exam).forEach(function (version, index) {
          if (index >= versionCount) return;
          payload.versions[String(index + 1)] = qa(".question-item", version).map(serializeQuestion).filter(Boolean);
        });
      } else if (type === "drive") {
        payload.subject = clean(q(".document-subject", exam) && q(".document-subject", exam).value);
        payload.subject_file_name = examSubjectFileName(exam);
      } else if (type === "meeting") {
        var mode = q(".meeting-mode", exam) ? q(".meeting-mode", exam).value : "online";
        payload.meeting_mode = mode === "onsite" ? "onsite" : "online";
        payload.meeting_link = "";
        payload.meeting_place = "";
        payload.duration_min = durationMin > 0 ? durationMin : 60;
      }

      return payload;
    });

    var field = q("#exams_payload");
    if (field) field.value = JSON.stringify({ exams: exams });
  }

  function serializeBuilders() {
    serializeContent();
    serializeExams();
    updateStepButtons();
    if (autosaveReady) {
      scheduleLocalDraftSave();
    }
  }

  function enterprisePricingConflict() {
    var price = q("#enterprise_price_xof");
    var discount = q("#enterprise_discount_percent");
    return !!(price && discount && !price.disabled && !discount.disabled && clean(price.value) !== "" && clean(discount.value) !== "");
  }

  function syncEnterprisePricing() {
    var modeField = q("#enterprise_pricing_mode");
    var price = q("#enterprise_price_xof");
    var discount = q("#enterprise_discount_percent");
    var warning = q("[data-enterprise-warning]");
    if (!price || !discount) return;

    var mode = modeField ? modeField.value : "";
    if (mode !== "price" && mode !== "discount" && mode !== "none") {
      mode = clean(price.value) !== "" ? "price" : (clean(discount.value) !== "" ? "discount" : "none");
      if (modeField) modeField.value = mode;
    }

    if (mode === "price") {
      discount.value = "";
    } else if (mode === "discount") {
      price.value = "";
    } else {
      price.value = "";
      discount.value = "";
    }

    price.disabled = mode !== "price";
    discount.disabled = mode !== "discount";

    [price, discount].forEach(function (field) {
      field.classList.toggle("opacity-50", field.disabled);
      field.classList.toggle("cursor-not-allowed", field.disabled);
      field.classList.remove("border-amber-300");
      field.classList.remove("bg-amber-50");
    });

    qa("[data-enterprise-panel]").forEach(function (panel) {
      panel.classList.toggle("hidden", panel.getAttribute("data-enterprise-panel") !== mode);
    });

    if (warning) warning.classList.add("hidden");
  }

  function updateStepButtons() {
    var activePhase = phaseFromStep(currentStep);
    [1, 2, 3, 4].forEach(function (phase) {
      var button = q("#stepBtn" + phase);
      if (!button) return;
      var progress = phaseProgressInfo(phase);
      var complete = progress.complete;
      var stateLabel = progress.label;
      var state = q(".course-builder-workflow__step-state", button);
      var active = phase === activePhase;
      button.setAttribute("data-step-complete", complete ? "1" : "0");
      button.classList.toggle("course-builder-workflow__step--complete", complete);
      button.classList.toggle("course-builder-workflow__step--pending", !complete);
      button.classList.toggle("is-active", active);
      if (active) button.setAttribute("aria-current", "step");
      else button.removeAttribute("aria-current");
      if (state) state.textContent = stateLabel;
    });

    qa(".course-side-roadmap__item[data-step-target]").forEach(function (button) {
      var step = numberValue(button.getAttribute("data-step-target"), 1);
      var phase = phaseFromStep(step);
      var progress = phaseProgressInfo(phase);
      var complete = progress.complete;
      var stateLabel = progress.label;
      var state = q(".course-side-roadmap__copy small", button);
      var active = phase === activePhase;
      button.classList.toggle("is-active", active);
      if (active) button.setAttribute("aria-current", "step");
      else button.removeAttribute("aria-current");
      if (state) state.textContent = stateLabel;
    });
  }

  function updateProgressBar() {
    var bar1 = q("#progressBar");
    var bar2 = q("#progressBar2");
    var bar3 = q("#progressBar3");
    var activePhase = phaseFromStep(currentStep);
    if (bar1) bar1.classList.toggle("is-complete", activePhase >= 2);
    if (bar2) bar2.classList.toggle("is-complete", activePhase >= 3);
    if (bar3) bar3.classList.toggle("is-complete", activePhase >= 4);
  }

  function showStep(step, scrollTop, historyMode) {
    currentStep = step;
    [1, 2, 3, 4].forEach(function (phase) {
      var intro = q("#step" + phase + "Intro");
      var content = q("#step" + phase + "Content");
      var introStep = (phase - 1) * 2 + 1;
      var contentStep = phase * 2;
      if (intro) intro.classList.toggle("hidden", step !== introStep);
      if (content) content.classList.toggle("hidden", step !== contentStep);
    });
    updateStepButtons();
    updateProgressBar();
    syncStepInUrl(step, historyMode || "replace");
    if (autosaveReady) {
      scheduleLocalDraftSave();
    }
    if (scrollTop !== false) window.scrollTo({ top: 0, behavior: "smooth" });
  }

  function clearInvalidMarkers(root) {
    var fields = qa("[aria-invalid='true']", root || document);
    if (root && root.matches && root.matches("[aria-invalid='true']")) {
      fields.push(root);
    }
    fields.forEach(function (field) {
      field.removeAttribute("aria-invalid");
      field.classList.remove("border-red-400", "bg-red-50", "ring-2", "ring-red-100");
    });
  }

  function revealForFocus(element) {
    var module = element && element.closest ? element.closest(".module-item") : null;
    if (module) {
      var lessons = q(".module-lessons", module);
      var icon = q(".module-chevron", module);
      if (lessons) lessons.classList.remove("hidden");
      if (icon) icon.classList.remove("rotate-180");
    }
    var exam = element && element.closest ? element.closest(".exam-item") : null;
    if (exam) {
      var examContent = q(".exam-content", exam);
      var examIcon = q(".exam-chevron", exam);
      if (examContent) examContent.classList.remove("hidden");
      if (examIcon) examIcon.classList.add("rotate-180");
      var version = element.closest ? element.closest(".qcm-version") : null;
      if (version) {
        setActiveQcmVersion(exam, numberValue(version.getAttribute("data-version"), 1));
      }
    }
  }

  function focusInvalid(element) {
    if (!element) return;
    revealForFocus(element);
    element.classList.add("border-red-400", "bg-red-50", "ring-2", "ring-red-100");
    element.setAttribute("aria-invalid", "true");
    if (!/^(INPUT|SELECT|TEXTAREA|BUTTON|A)$/.test(element.tagName || "")) {
      element.setAttribute("tabindex", "-1");
    }
    element.scrollIntoView({ behavior: "smooth", block: "center" });
    window.setTimeout(function () {
      if (element.focus) element.focus({ preventScroll: true });
    }, 220);
  }

  function showBlockingIssue(issue) {
    if (!issue) return false;
    if (issue.step && issue.step !== currentStep) {
      showStep(issue.step, false);
    }
    clearInvalidMarkers();
    window.setTimeout(function () {
      focusInvalid(issue.element || null);
    }, 0);
    showToast(issue.message, "error");
    return true;
  }

  function selectedOptionText(select, fallback) {
    if (!select) return fallback;
    var option = select.options && select.selectedIndex >= 0 ? select.options[select.selectedIndex] : null;
    var label = clean(option && (option.textContent || option.label || option.value));
    return label !== "" ? label : fallback;
  }

  function formatDurationPreview(value) {
    var hours = Number(String(value || "").replace(",", "."));
    if (!isFinite(hours) || hours <= 0) return "0 min";
    var totalMinutes = Math.max(1, Math.round(hours * 60));
    var durationHours = Math.floor(totalMinutes / 60);
    var durationMinutes = totalMinutes % 60;
    if (durationHours <= 0) return String(totalMinutes) + " min";
    return durationMinutes > 0
      ? durationHours + " h " + String(durationMinutes).padStart(2, "0") + " min"
      : durationHours + " h";
  }

  function formatPricePreview(value) {
    var amount = Number(String(value || "").replace(",", "."));
    if (!isFinite(amount) || amount <= 0) return "Gratuit";
    try {
      return new Intl.NumberFormat("fr-FR", { maximumFractionDigits: 0 }).format(amount) + " F CFA";
    } catch (_error) {
      return String(Math.round(amount)) + " F CFA";
    }
  }

  function previewAlertMessages() {
    var messages = [];
    var seen = {};

    [2, 4, 6, 8].forEach(function (step) {
      var issue = blockingIssueForStep(step);
      if (issue && issue.message && !seen[issue.message]) {
        seen[issue.message] = true;
        messages.push(issue.message);
      }
    });

    if (!hasSimpleValue("objectives-container")) {
      var objectiveMessage = "Ajoutez au moins un objectif pedagogique.";
      if (!seen[objectiveMessage]) {
        seen[objectiveMessage] = true;
        messages.push(objectiveMessage);
      }
    }

    return messages;
  }

  function previewCoverSource() {
    var cover = q("#cover-preview");
    if (cover) {
      return clean(cover.getAttribute("src") || cover.src);
    }
    var thumb = q("#thumb-img");
    if (thumb) {
      return clean(thumb.getAttribute("src") || thumb.src);
    }
    return "";
  }

  function previewModuleTypeLabel(type) {
    var normalized = clean(type).toLowerCase();
    if (normalized === "document") return "Document";
    if (normalized === "appointment" || normalized === "meeting") return "Rendez-vous";
    if (normalized === "exam") return "Evaluation";
    return "Video";
  }

  function previewExamTypeLabel(type) {
    var normalized = clean(type).toLowerCase();
    if (normalized === "drive" || normalized === "document") return "Document";
    if (normalized === "meeting" || normalized === "appointment") return "Rendez-vous";
    return "QCM";
  }

  function previewQuestionCount(exam) {
    if (!exam || typeof exam !== "object") return 0;
    var versions = exam.versions && typeof exam.versions === "object" ? exam.versions : {};
    return Object.keys(versions).reduce(function (total, key) {
      return total + (Array.isArray(versions[key]) ? versions[key].length : 0);
    }, 0);
  }

  function renderPreviewList(items, emptyLabel, toneClass) {
    var tone = clean(toneClass);
    if (!items.length) {
      return '<li class="rounded-2xl border border-dashed border-slate-200 bg-white/80 px-3 py-3 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-400">' + escapeHtml(emptyLabel) + '</li>';
    }
    return items.slice(0, 4).map(function (item) {
      return '<li class="flex items-start gap-3 rounded-2xl border border-white/70 bg-white/90 px-3 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">' +
        '<span class="mt-1 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full ' + tone + ' text-white"><i class="fas fa-check text-[10px]"></i></span>' +
        '<span class="min-w-0 text-sm leading-6 text-slate-700 dark:text-slate-200">' + escapeHtml(item) + '</span>' +
      '</li>';
    }).join("");
  }

  function renderPreviewModules(modules) {
    if (!modules.length) {
      return '<article class="rounded-2xl border border-dashed border-slate-200 bg-white/80 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-400">Aucun module n\'est encore ajoute.</article>';
    }

    return modules.slice(0, 4).map(function (module, index) {
      var title = clean(module && module.title) || "Module " + String(index + 1);
      var description = clean(module && module.description);
      var items = Array.isArray(module && module.items) ? module.items : [];
      var itemCount = items.length;
      var itemMarkup = items.slice(0, 3).map(function (item) {
        var itemTitle = clean(item && item.title) || "Lecon sans titre";
        var itemType = previewModuleTypeLabel(item && item.type);
        var duration = Number(String((item && item.duration_min) || 0).replace(",", "."));
        var durationLabel = duration > 0 ? String(Math.round(duration)) + " min" : "Duree a definir";
        return '<li class="flex items-start justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-200">' +
          '<div class="min-w-0"><p class="font-semibold text-slate-900 dark:text-white">' + escapeHtml(itemTitle) + '</p><p class="text-xs text-slate-500 dark:text-slate-400">' + escapeHtml(itemType) + '</p></div>' +
          '<span class="shrink-0 text-xs font-semibold text-slate-500 dark:text-slate-400">' + escapeHtml(durationLabel) + '</span>' +
        '</li>';
      }).join("");

      if (itemCount > 3) {
        itemMarkup += '<li class="rounded-2xl border border-dashed border-slate-200 bg-white/70 px-3 py-2 text-xs font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-400">+' + escapeHtml(String(itemCount - 3)) + " autre(s) element(s)</li>";
      }

      return '<article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">' +
        '<div class="flex items-start justify-between gap-3">' +
          '<div class="min-w-0">' +
            '<p class="text-base font-black text-slate-900 dark:text-white">' + escapeHtml(title) + '</p>' +
            (description !== "" ? '<p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">' + escapeHtml(description) + '</p>' : '<p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">Aucune description ajoutee.</p>') +
          '</div>' +
          '<span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">' + escapeHtml(String(itemCount)) + " lecon" + (itemCount > 1 ? "s" : "") + '</span>' +
        '</div>' +
        '<ul class="mt-3 space-y-2">' + itemMarkup + '</ul>' +
      '</article>';
    }).join("");
  }

  function renderPreviewExams(exams) {
    if (!exams.length) {
      return '<article class="rounded-2xl border border-dashed border-slate-200 bg-white/80 p-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-400">Aucune evaluation n\'est encore definie.</article>';
    }

    return exams.slice(0, 2).map(function (exam, index) {
      var title = clean(exam && exam.title) || "Evaluation finale";
      var type = previewExamTypeLabel(exam && exam.type);
      var duration = Number(String((exam && exam.duration_min) || 0).replace(",", "."));
      var minScore = Number(String((exam && exam.min_score) || 0).replace(",", "."));
      var versionCount = Number(exam && exam.version_count || Object.keys(exam && exam.versions ? exam.versions : {}).length || 0);
      var questionCount = previewQuestionCount(exam);
      var subject = clean(exam && exam.subject);
      var subjectFileName = clean(exam && exam.subject_file_name);
      var meta = [];
      if (duration > 0) meta.push(String(Math.round(duration)) + " min");
      if (minScore > 0) meta.push(String(Math.round(minScore)) + "% min.");
      if (versionCount > 0) meta.push(String(versionCount) + " version" + (versionCount > 1 ? "s" : ""));
      if (questionCount > 0) meta.push(String(questionCount) + " question" + (questionCount > 1 ? "s" : ""));
      if (subject !== "") meta.push(subject);
      if (subjectFileName !== "") meta.push(subjectFileName);

      return '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">' +
        '<div class="flex items-start justify-between gap-3">' +
          '<div class="min-w-0">' +
            '<p class="text-base font-black text-slate-900 dark:text-white">' + escapeHtml(title) + '</p>' +
            '<p class="mt-1 text-sm font-semibold text-sky-600 dark:text-sky-300">' + escapeHtml(type) + '</p>' +
          '</div>' +
          '<span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[11px] font-bold text-slate-600 shadow-sm dark:bg-slate-900 dark:text-slate-300">#' + escapeHtml(String(index + 1)) + '</span>' +
        '</div>' +
        '<div class="mt-3 flex flex-wrap gap-2">' + meta.slice(0, 5).map(function (entry) {
          return '<span class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">' + escapeHtml(entry) + '</span>';
        }).join("") + '</div>' +
      '</article>';
    }).join("");
  }

  function renderPreviewModal() {
    var modal = q("#course-preview-modal");
    if (!modal) return false;

    serializeBuilders();

    var form = q("[data-course-create-form]");
    if (!form) return false;

    var coverSrc = previewCoverSource();
    var title = clean(q("#title", form) && q("#title", form).value);
    var description = clean(q("#description", form) && q("#description", form).value);
    var category = selectedOptionText(q("#category_id", form), "Non categorise");
    var level = selectedOptionText(q("#level", form), "Debutant");
    var durationRaw = clean(q("#duration_hours", form) && q("#duration_hours", form).value);
    var priceRaw = clean(q("#price_xof", form) && q("#price_xof", form).value);
    var duration = durationRaw !== "" ? formatDurationPreview(durationRaw) : "Non defini";
    var price = priceRaw !== "" ? formatPricePreview(priceRaw) : "Non defini";
    var enterpriseMode = q("#enterprise_pricing_mode", form) ? q("#enterprise_pricing_mode", form).value : "none";
    var enterprisePriceRaw = clean(q("#enterprise_price_xof", form) && q("#enterprise_price_xof", form).value);
    var enterprisePrice = enterprisePriceRaw !== "" ? formatPricePreview(enterprisePriceRaw) : "Non defini";
    var enterpriseDiscount = clean(q("#enterprise_discount_percent", form) && q("#enterprise_discount_percent", form).value);
    var generalFileInput = q("#general_file", form);
    var generalFile = generalFileInput && generalFileInput.files && generalFileInput.files.length ? generalFileInput.files[0] : null;
    var objectives = simpleRowValues("objectives-container");
    var prerequisites = simpleRowValues("prerequisites-container");
    var equipment = simpleRowValues("equipment-container");
    var contentPayload = parsePayload(q("#content_payload", form), { modules: [] });
    var examsPayload = parsePayload(q("#exams_payload", form), { exams: [] });
    var modules = asArray(contentPayload.modules);
    var exams = asArray(examsPayload.exams);
    var issues = previewAlertMessages();
    var readinessText = issues.length ? "A completer avant publication" : "Pret a publier";
    var enterpriseText = "Aucune offre dediee";

    if (enterpriseMode === "price") {
      enterpriseText = "Prix entreprise fixe a " + enterprisePrice;
    } else if (enterpriseMode === "discount") {
      enterpriseText = "Reduction entreprise de " + (enterpriseDiscount !== "" ? enterpriseDiscount + "%" : "0%") + " sur le tarif public";
    }

    var alertBox = q("[data-course-preview-alert]", modal);
    var alertMessage = q("[data-course-preview-alert-message]", modal);
    var alertTitle = q("[data-course-preview-alert-title]", modal);
    var statusEl = q("[data-course-preview-status]", modal);
    var stageEl = q("[data-course-preview-stage]", modal);
    var subtitleEl = q("#course-preview-modal-subtitle", modal);
    var titleEl = q("#course-preview-title", modal);
    var descriptionEl = q("#course-preview-description", modal);
    var categoryEl = q("#course-preview-category", modal);
    var levelEl = q("#course-preview-level", modal);
    var durationEl = q("#course-preview-duration", modal);
    var priceEl = q("#course-preview-price", modal);
    var enterpriseEl = q("#course-preview-enterprise", modal);
    var objectivesEl = q("#course-preview-objectives", modal);
    var prerequisitesEl = q("#course-preview-prerequisites", modal);
    var equipmentEl = q("#course-preview-equipment", modal);
    var modulesEl = q("#course-preview-modules", modal);
    var examsEl = q("#course-preview-exams", modal);
    var generalEl = q("#course-preview-general-resource", modal);
    var readinessEl = q("#course-preview-readiness", modal);
    var contentSummaryEl = q("[data-course-preview-content-summary]", modal);
    var examSummaryEl = q("[data-course-preview-exam-summary]", modal);
    var moduleCountEl = q("[data-course-preview-module-count]", modal);
    var examCountEl = q("[data-course-preview-exam-count]", modal);
    var objectiveCountEl = q("[data-course-preview-objectives-count]", modal);
    var prerequisiteCountEl = q("[data-course-preview-prerequisites-count]", modal);
    var equipmentCountEl = q("[data-course-preview-equipment-count]", modal);
    var coverEl = q("#course-preview-cover", modal);

    if (coverEl) {
      if (coverSrc !== "") {
        coverEl.src = coverSrc;
        coverEl.hidden = false;
        coverEl.removeAttribute("data-course-preview-cover-empty");
      } else {
        coverEl.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==";
        coverEl.hidden = false;
        coverEl.setAttribute("data-course-preview-cover-empty", "1");
      }
    }

    if (statusEl) statusEl.textContent = "Brouillon";
    if (stageEl) stageEl.textContent = readinessText;
    if (subtitleEl) subtitleEl.textContent = title !== "" ? title : "Titre du cours";
    if (titleEl) titleEl.textContent = title !== "" ? title : "Titre du cours";
    if (descriptionEl) descriptionEl.textContent = description !== "" ? description : "La description du cours apparaîtra ici.";
    if (categoryEl) categoryEl.textContent = category !== "" ? category : "Non categorise";
    if (levelEl) levelEl.textContent = level !== "" ? level : "Debutant";
    if (durationEl) durationEl.textContent = duration;
    if (priceEl) priceEl.textContent = price;
    if (enterpriseEl) enterpriseEl.textContent = enterpriseText;
    if (objectivesEl) objectivesEl.innerHTML = renderPreviewList(objectives, "Aucun objectif ajoute pour l’instant.", "bg-blue-500");
    if (prerequisitesEl) prerequisitesEl.innerHTML = renderPreviewList(prerequisites, "Aucun prerequis ajoute pour l’instant.", "bg-amber-500");
    if (equipmentEl) equipmentEl.innerHTML = renderPreviewList(equipment, "Aucun equipement ajoute pour l’instant.", "bg-teal-500");
    if (modulesEl) modulesEl.innerHTML = renderPreviewModules(modules);
    if (examsEl) examsEl.innerHTML = renderPreviewExams(exams);
    if (generalEl) {
      if (generalFile) {
        generalEl.textContent = "Fichier en attente d'envoi : " + clean(generalFile.name);
      } else if (q('input[name="general_file_clear"]', form) && q('input[name="general_file_clear"]', form).checked) {
        generalEl.textContent = "La ressource generale sera supprimee";
      } else {
        generalEl.textContent = "Aucune action sur la ressource generale";
      }
    }
    if (readinessEl) readinessEl.textContent = readinessText;
    if (contentSummaryEl) {
      var lessonTotal = modules.reduce(function (total, module) {
        return total + (Array.isArray(module && module.items) ? module.items.length : 0);
      }, 0);
      contentSummaryEl.textContent = modules.length > 0
        ? countLabel(modules.length, "module", "modules") + " · " + countLabel(lessonTotal, "leçon", "leçons")
        : "Aucun module pour l’instant";
    }
    if (examSummaryEl) {
      var questionTotal = exams.reduce(function (total, exam) {
        return total + previewQuestionCount(exam);
      }, 0);
      examSummaryEl.textContent = exams.length > 0
        ? countLabel(exams.length, "examen", "examens") + " · " + countLabel(questionTotal, "question", "questions")
        : "Aucun examen pour l’instant";
    }
    if (moduleCountEl) moduleCountEl.textContent = countLabel(modules.length, "module", "modules");
    if (examCountEl) examCountEl.textContent = countLabel(exams.length, "evaluation", "evaluations");
    if (objectiveCountEl) objectiveCountEl.textContent = String(objectives.length);
    if (prerequisiteCountEl) prerequisiteCountEl.textContent = String(prerequisites.length);
    if (equipmentCountEl) equipmentCountEl.textContent = String(equipment.length);

    if (alertBox && alertTitle && alertMessage) {
      alertBox.hidden = false;
      if (issues.length) {
        alertTitle.textContent = "Quelques elements restent a completer";
        alertMessage.textContent = issues.slice(0, 3).join(" ");
      } else {
        alertTitle.textContent = "Le cours peut etre publie";
        alertMessage.textContent = "L’aperçu montre un cours coherent avec les informations deja renseignees.";
      }
    }

    return true;
  }

  function openPreviewModal() {
    var modal = q("#course-preview-modal");
    if (!modal) return false;
    modal.hidden = false;
    modal.style.display = "flex";
    document.body.classList.add("course-builder-modal-open");

    var closeBtn = q("[data-course-preview-close]", modal) || q("[data-preview-publish]", modal);
    if (closeBtn && typeof closeBtn.focus === "function") {
      window.setTimeout(function () {
        try {
          closeBtn.focus({ preventScroll: true });
        } catch (_error) {
          try {
            closeBtn.focus();
          } catch (_fallbackError) {
            // Ignore focus failures.
          }
        }
      }, 0);
    }
    return true;
  }

  function closePreviewModal() {
    var modal = q("#course-preview-modal");
    if (!modal) return false;
    modal.style.display = "none";
    modal.hidden = true;
    document.body.classList.remove("course-builder-modal-open");
    var trigger = q("[data-preview-course]");
    if (trigger && typeof trigger.focus === "function") {
      try {
        trigger.focus({ preventScroll: true });
      } catch (_error) {
        try {
          trigger.focus();
        } catch (_fallbackError) {
          // Ignore focus failures.
        }
      }
    }
    return true;
  }

  function youtubeUrlLooksValid(value) {
    var raw = clean(value);
    if (raw === "") return false;
    try {
      var url = new URL(raw);
      var host = url.hostname.toLowerCase();
      var protocol = url.protocol.toLowerCase();
      if (protocol !== "http:" && protocol !== "https:") return false;
      if (host === "youtu.be") {
        return clean(url.pathname.replace(/^\/+/, "")) !== "";
      }
      if (["youtube.com", "www.youtube.com", "m.youtube.com"].indexOf(host) !== -1) {
        var path = clean(url.pathname.replace(/^\/+/, ""));
        if (path === "watch") {
          return clean(url.searchParams.get("v")) !== "";
        }
        return /^(embed|shorts|live)\/[^/]+/i.test(path);
      }
    } catch (error) {
      return false;
    }
    return false;
  }

  function blockingIssueForStep(step) {
    if (isIntroStep(step)) {
      return null;
    }

    var phase = phaseFromStep(step);

    if (phase === 1) {
      if (!clean(q("#title") && q("#title").value)) {
        return { step: 2, element: q("#title"), message: "Le titre du cours est obligatoire." };
      }
      if (!clean(q("#description") && q("#description").value)) {
        return { step: 2, element: q("#description"), message: "La description du cours est obligatoire." };
      }
    }

    if (phase === 2) {
      if (!clean(q("#price_xof") && q("#price_xof").value)) {
        return { step: 4, element: q("#price_xof"), message: "Le prix public est obligatoire." };
      }
      var enterpriseMode = q("#enterprise_pricing_mode");
      var enterprisePrice = q("#enterprise_price_xof");
      var enterpriseDiscount = q("#enterprise_discount_percent");
      if (enterpriseMode && enterpriseMode.value === "price" && !clean(enterprisePrice && enterprisePrice.value)) {
        return { step: 4, element: enterprisePrice, message: "Renseignez le prix fixe entreprise ou choisissez un autre mode." };
      }
      if (enterpriseMode && enterpriseMode.value === "discount" && !clean(enterpriseDiscount && enterpriseDiscount.value)) {
        return { step: 4, element: enterpriseDiscount, message: "Renseignez la réduction entreprise ou choisissez un autre mode." };
      }
      if (enterprisePricingConflict()) {
        return { step: 4, element: enterprisePrice || enterpriseDiscount, message: "Choisissez soit un prix entreprise, soit une réduction." };
      }
      if (!clean(q("#category_id") && q("#category_id").value)) {
        return { step: 4, element: q("#category_id"), message: "Sélectionnez une catégorie avant de continuer." };
      }
      if (!clean(q("#cover_path_existing") && q("#cover_path_existing").value)) {
        var cover = q("#cover_file");
        if (!cover || !cover.files || !cover.files.length) {
          return { step: 4, element: q("#thumbnail-zone"), message: "Ajoutez une miniature de cours." };
        }
      }
    }

    if (phase === 3) {
      var modules = qa("#modules-container .module-item");
      if (!modules.length) {
        return { step: 6, element: q("[data-add-module]"), message: "Ajoutez au moins un module." };
      }

      for (var moduleIndex = 0; moduleIndex < modules.length; moduleIndex++) {
        var module = modules[moduleIndex];
        var moduleTitle = q(".module-title", module);
        if (!clean(moduleTitle && moduleTitle.value)) {
          return { step: 6, element: moduleTitle, message: "Renseignez le titre du module " + String(moduleIndex + 1) + "." };
        }

        var lessons = qa(".lesson-item", module);
        if (!lessons.length) {
          return { step: 6, element: q("[data-add-lesson]", module), message: "Ajoutez au moins une leçon dans le module " + String(moduleIndex + 1) + "." };
        }

        for (var lessonIndex = 0; lessonIndex < lessons.length; lessonIndex++) {
          var lesson = lessons[lessonIndex];
          var lessonTitle = q(".lesson-title", lesson);
          var lessonType = q(".lesson-type-select", lesson);
          var duration = q(".lesson-duration", lesson);
          var resource = q(".lesson-resource", lesson);
          var fileInput = q(".lesson-file-input", lesson);
          var type = lessonType ? lessonType.value : "video";
          var label = "module " + String(moduleIndex + 1) + ", leçon " + String(lessonIndex + 1);

          if (!clean(lessonTitle && lessonTitle.value)) {
            return { step: 6, element: lessonTitle, message: "Renseignez le titre de la " + label + "." };
          }
          if (type === "video") {
            if (!clean(resource && resource.value)) {
              return { step: 6, element: resource, message: "Ajoutez le lien YouTube de la " + label + "." };
            }
            if (!youtubeUrlLooksValid(resource.value)) {
              return { step: 6, element: resource, message: "Le lien YouTube de la " + label + " est invalide." };
            }
          }
          if (type === "document" && !(fileInput && fileInput.files && fileInput.files.length) && !uploadedResourceLooksValid(resource && resource.value)) {
            return {
              step: 6,
              element: q(".course-builder-file-drop", lesson) || fileInput,
              message: "Ajoutez le fichier document/exercice de la " + label + "."
            };
          }
          if (type !== "appointment" && numberValue(duration && duration.value, 0) < 1) {
            return { step: 6, element: duration, message: "Renseignez une durée valide pour la " + label + "." };
          }
        }
      }
    }

    if (phase === 4) {
      var exam = q("#exams-container .exam-item");
      if (!exam) {
        return { step: 8, element: q("#exams-container"), message: "Configurez l’évaluation finale." };
      }

      var examTitle = q(".exam-title", exam);
      if (!clean(examTitle && examTitle.value)) {
        return { step: 8, element: examTitle, message: "Renseignez le titre de l’évaluation finale." };
      }

      var examType = q(".exam-type-select", exam) ? q(".exam-type-select", exam).value : "qcm";
      var examDuration = q(".exam-duration", exam);
      if (numberValue(examDuration && examDuration.value, 0) < 1) {
        return { step: 8, element: examDuration, message: "Renseignez une durée valide pour l’évaluation finale." };
      }

      if (examType === "document") {
        var subject = q(".document-subject", exam);
        var subjectFile = q(".exam-subject-file-input", exam);
        var hasSelectedSubject = !!(subjectFile && subjectFile.files && subjectFile.files.length);
        var hasExistingSubject = uploadedResourceLooksValid(subject && subject.value);
        if (!hasSelectedSubject && !hasExistingSubject) {
          return { step: 8, element: q("[data-exam-subject-file-shell]", exam) || subjectFile || subject, message: "Ajoutez le document sujet à uploader pour cette évaluation." };
        }
      }

      if (examType === "qcm") {
        var qcmSection = q(".exam-qcm-section", exam);
        var qcmMessage = "Complétez le QCM avant de publier.";
        var versionCount = Math.max(1, Math.min(3, numberValue(q(".qcm-version-count", exam) && q(".qcm-version-count", exam).value, 3)));
        var versions = qa(".qcm-version", exam);
        for (var versionIndex = 0; versionIndex < versionCount; versionIndex++) {
          var version = versions[versionIndex];
          var versionLabel = "version " + String.fromCharCode(65 + versionIndex);
          var questions = version ? qa(".question-item", version) : [];
          if (!questions.length) {
            return { step: 8, element: version || qcmSection, message: qcmMessage + " Il manque une question dans la " + versionLabel + "." };
          }
          for (var questionIndex = 0; questionIndex < questions.length; questionIndex++) {
            var question = questions[questionIndex];
            var questionNumber = questionIndex + 1;
            var questionText = q(".question-text", question);
            if (!clean(questionText && questionText.value)) {
              return { step: 8, element: questionText || qcmSection, message: qcmMessage + " Question " + questionNumber + " : intitulé requis." };
            }

            var questionType = q(".question-type-select", question) ? q(".question-type-select", question).value : "single";
            if (questionType !== "text") {
              var optionRows = qa(".option-row", question);
              var filledOptions = optionRows.filter(function (option) {
                return clean(q(".option-text", option) && q(".option-text", option).value) !== "";
              });
              if (filledOptions.length < 2) {
                return { step: 8, element: (optionRows[0] ? q(".option-text", optionRows[0]) : question) || qcmSection, message: qcmMessage + " Question " + questionNumber + " : deux propositions minimum." };
              }
              var missingOption = optionRows.find(function (option) {
                return clean(q(".option-text", option) && q(".option-text", option).value) === "";
              });
              if (missingOption) {
                return { step: 8, element: q(".option-text", missingOption) || qcmSection, message: qcmMessage + " Question " + questionNumber + " : proposition vide." };
              }
              var correctCount = optionRows.filter(function (option) {
                var input = q('input[type="radio"], input[type="checkbox"]', option);
                return !!(input && input.checked);
              }).length;
              if (correctCount < 1) {
                return { step: 8, element: question || qcmSection, message: qcmMessage + " Question " + questionNumber + " : bonne réponse requise." };
              }
              if (questionType === "single" && correctCount > 1) {
                return { step: 8, element: question || qcmSection, message: qcmMessage + " Question " + questionNumber + " : une seule bonne réponse." };
              }
            }
          }
        }
      }
    }

    return null;
  }

  window.goToStep = function (step) {
    step = numberValue(step, currentStep);
    if (step < 1 || step > totalSteps) return;
    serializeBuilders();
    if (step > currentStep + 1) {
      step = currentStep + 1;
    }
    if (step > currentStep && !isIntroStep(currentStep)) {
      var issue = blockingIssueForStep(currentStep);
      if (showBlockingIssue(issue)) return;
    }
    clearInvalidMarkers();
    showStep(step, true, "push");
  };

  window.addObjective = function () {
    addSimpleRow("objectives-container", "objectives", "Objectif");
  };

  window.addPrerequisite = function () {
    addSimpleRow("prerequisites-container", "prerequisites", "Prérequis");
  };

  window.addEquipment = function () {
    addSimpleRow("equipment-container", "equipment", "Équipement");
  };

  function addSimpleRow(containerId, name, placeholder) {
    var container = q("#" + containerId);
    if (!container) return;
    container.insertAdjacentHTML("beforeend", '' +
      '<div class="flex items-center gap-2">' +
        '<input type="text" name="' + name + '[]" placeholder="' + escapeHtml(placeholder) + '" class="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">' +
        '<button type="button" data-remove-simple-row class="text-slate-400 hover:text-red-500 transition" aria-label="Supprimer"><i class="fa-solid fa-trash-can"></i></button>' +
      '</div>');
    updatePedagogyCount(false);
    scheduleLocalDraftSave();
  }

  window.removeItem = function (button) {
    var row = button && button.closest(".flex.items-center.gap-2");
    if (row) row.remove();
    updatePedagogyCount(false);
    scheduleLocalDraftSave();
  };

  window.addModule = function (data) {
    var container = q("#modules-container");
    if (!container) return;
    var module = data && typeof data === "object" && !data.target ? data : defaultNewModule();
    container.insertAdjacentHTML("beforeend", moduleHtml(module));
    var element = container.lastElementChild;
    var lessons = q(".module-lessons", element);
    asArray(module.items).forEach(function (item) {
      lessons.insertAdjacentHTML("beforeend", lessonHtml(item));
    });
    if (!asArray(module.items).length) {
      lessons.insertAdjacentHTML("beforeend", lessonHtml({ type: "video", title: "", resource: "" }));
    }
    renumberModules();
    serializeBuilders();
  };

  window.removeModule = function (button) {
    var item = button && button.closest(".module-item");
    if (item) item.remove();
    renumberModules();
    serializeBuilders();
  };

  window.toggleModule = function (header) {
    var item = header && header.closest(".module-item");
    if (!item) return;
    var lessons = q(".module-lessons", item);
    var icon = q(".module-chevron", item);
    var collapsed = lessons ? lessons.classList.toggle("hidden") : false;
    if (icon) icon.classList.toggle("rotate-180", collapsed);
  };

  window.addLesson = function (button) {
    var module = button && button.closest(".module-item");
    var lessons = module ? q(".module-lessons", module) : null;
    if (!lessons) return;
    lessons.insertAdjacentHTML("beforeend", lessonHtml({ type: "video", title: "", resource: "" }));
    renumberModules();
    serializeBuilders();
  };

  window.removeLesson = function (button) {
    var lesson = button && button.closest(".lesson-item");
    if (lesson) lesson.remove();
    renumberModules();
    serializeBuilders();
  };

  window.handleLessonTypeChange = function (select) {
    var lesson = select && select.closest(".lesson-item");
    if (!lesson) return;
    var dynamic = q(".lesson-dynamic", lesson);
    var duration = q(".lesson-duration", lesson);
    var durationField = q(".course-builder-field--duration", lesson);
    var fileKey = clean(lesson.getAttribute("data-file-key")) || makeLessonFileKey();
    var previousType = lesson.getAttribute("data-lesson-type") || "video";
    var previousDefault = previousType === "appointment" ? 60 : 0;
    var currentDuration = numberValue(duration && duration.value, previousDefault);
    lesson.setAttribute("data-lesson-type", select.value);
    lesson.setAttribute("data-file-key", fileKey);
    if (dynamic) dynamic.innerHTML = lessonDynamicHtml(select.value, "", "", fileKey, "");
    if (durationField) durationField.classList.toggle("hidden", select.value === "appointment");
    if (duration && (clean(duration.value) === "" || currentDuration === previousDefault)) {
      duration.value = select.value === "appointment" ? "60" : "";
    }
    updateModuleSummary(lesson.closest(".module-item"));
    serializeBuilders();
  };

  window.handleLessonFileChange = function (input) {
    var lesson = input && input.closest(".lesson-item");
    if (!lesson) return;
    var fileName = input.files && input.files.length ? clean(input.files[0].name) : "";
    var label = q(".lesson-file-name", lesson);
    var shell = q(".course-builder-file-drop", lesson);
    var resource = q(".lesson-resource", lesson);

    if (label) {
      label.textContent = fileName || "Importer le document";
      label.setAttribute("data-file-name", fileName);
    }
    if (resource) {
      resource.value = fileName;
    }
    if (shell) {
      shell.classList.toggle("has-file", fileName !== "");
      clearInvalidMarkers(shell);
    }
    updateModuleSummary(lesson.closest(".module-item"));
    serializeBuilders();
  };

  window.addExam = function (data) {
    var container = q("#exams-container");
    if (!container) return;
    var exam = data && typeof data === "object" && !data.target ? data : defaultNewExam();
    container.innerHTML = "";
    container.insertAdjacentHTML("beforeend", examHtml(exam));
    var element = container.lastElementChild;
    syncExamType(element);
    syncVersionCount(element);
    renumberExams();
    serializeBuilders();
  };

  window.removeExam = function (button) {
    var container = q("#exams-container");
    if (!container) return;
    container.innerHTML = examHtml(defaultExam());
    var item = q(".exam-item", container);
    if (item) {
      syncExamType(item);
      syncVersionCount(item);
    }
    renumberExams();
    serializeBuilders();
  };

  window.toggleExam = function (header) {
    var item = header && header.closest(".exam-item");
    if (!item) return;
    var content = q(".exam-content", item);
    var icon = q(".exam-chevron", item);
    if (content) content.classList.toggle("hidden");
    if (icon) icon.classList.toggle("rotate-180");
  };

  window.handleExamTypeChange = function (select) {
    var exam = select && select.closest(".exam-item");
    if (!exam) return;
    syncExamType(exam);
    serializeBuilders();
  };

  window.updateQCMVersions = function (select) {
    var exam = select && select.closest(".exam-item");
    if (!exam) return;
    syncVersionCount(exam);
    serializeBuilders();
  };

  window.showQcmVersion = function (button) {
    var exam = button && button.closest(".exam-item");
    if (!exam) return;
    setActiveQcmVersion(exam, button.getAttribute("data-qcm-version-tab"));
    serializeBuilders();
  };

  window.moveQcmVersion = function (button, direction) {
    var exam = button && button.closest(".exam-item");
    if (!exam) return;
    var active = numberValue(exam.getAttribute("data-active-version"), 1);
    setActiveQcmVersion(exam, active + direction);
    serializeBuilders();
  };

  window.handleExamSubjectFileChange = function (input) {
    var exam = input && input.closest(".exam-item");
    if (!exam) return;
    var fileName = input.files && input.files.length ? clean(input.files[0].name) : "";
    var label = q(".exam-subject-file-name", exam);
    var shell = q("[data-exam-subject-file-shell]", exam);
    var subject = q(".document-subject", exam);
    if (label) {
      label.textContent = fileName || "Importer le sujet";
      label.setAttribute("data-file-name", fileName);
    }
    if (subject && fileName !== "") {
      subject.value = fileName;
    }
    if (shell) {
      shell.classList.toggle("has-file", fileName !== "");
      clearInvalidMarkers(shell);
    }
    serializeBuilders();
  };

  window.addQuestion = function (button) {
    var version = button && button.closest(".qcm-version");
    var container = version ? q(".questions-container", version) : null;
    if (!container) return;
    container.insertAdjacentHTML("beforeend", questionHtml({ text: "", options: [{ text: "", correct: true }, { text: "", correct: false }] }, qa(".question-item", container).length));
    renumberQuestions(version);
    serializeBuilders();
  };

  window.removeQuestion = function (button) {
    var question = button && button.closest(".question-item");
    var version = question && question.closest(".qcm-version");
    if (question) question.remove();
    if (version && !qa(".question-item", version).length) {
      q(".questions-container", version).insertAdjacentHTML("beforeend", questionHtml({ text: "", options: [{ text: "", correct: true }, { text: "", correct: false }] }, 0));
    }
    renumberQuestions(version);
    serializeBuilders();
  };

  window.addOption = function (button) {
    var question = button && button.closest(".question-item");
    var options = question ? q(".options", question) : null;
    if (!question || !options) return;
    var typeSelect = q(".question-type-select", question);
    var type = typeSelect ? typeSelect.value : "single";
    var existingInput = q(".option-row input[type='radio'], .option-row input[type='checkbox']", question);
    var group = existingInput && existingInput.name ? existingInput.name : "qcm_question_" + (++questionSeq);
    var inputType = type === "multiple" ? "checkbox" : "radio";
    options.insertAdjacentHTML("beforeend", optionRowHtml({ text: "", correct: false }, qa(".option-row", options).length, inputType, group, false));
    renumberQuestions(question);
    serializeBuilders();
  };

  window.removeOption = function (button) {
    var option = button && button.closest(".option-row");
    var question = option && option.closest(".question-item");
    if (option) option.remove();
    if (question && qa(".option-row", question).length < 2) {
      window.addOption(q("[data-add-option]", question));
    }
    renumberQuestions(question);
    serializeBuilders();
  };

  window.handleQuestionTypeChange = function (select) {
    var question = select && select.closest(".question-item");
    if (!question) return;
    syncQuestionType(question);
    serializeBuilders();
  };

  function syncReferralToggleUi() {
    var field = q("#referral_override");
    var toggle = q("#referral-toggle");
    var status = q("#ref-status");
    var enabled = clean(field && field.value) === "1";

    if (toggle) {
      toggle.classList.toggle("is-active", enabled);
      toggle.classList.toggle("is-inactive", !enabled);
      toggle.setAttribute("aria-pressed", enabled ? "true" : "false");
      toggle.setAttribute("aria-label", enabled ? "Désactiver le parrainage" : "Activer le parrainage");
    }
    if (status) status.textContent = enabled ? "Activé" : "Désactivé";
    syncReferralOptions(enabled);
  }

  function syncReferralOptions(enabled) {
    var options = q("#referral-options");
    var l2Field = q("#referral_l2_enabled");
    var l2Toggle = q("#referral-l2-toggle");

    if (options) options.classList.toggle("hidden", !enabled);
    if (!enabled) {
      if (l2Toggle) l2Toggle.checked = false;
      if (l2Field) l2Field.value = "0";
      return;
    }

    if (l2Toggle && l2Field) {
      l2Toggle.checked = clean(l2Field.value) === "1";
    }
    if (l2Field && l2Toggle) {
      l2Field.value = l2Toggle.checked ? "1" : "0";
    }
  }

  window.toggleReferral = function () {
    var field = q("#referral_override");
    var toggle = q("#referral-toggle");
    var status = q("#ref-status");
    var enabled = !(field && field.value === "1");
    if (field) field.value = enabled ? "1" : "0";
    if (toggle) {
      toggle.classList.toggle("is-active", enabled);
      toggle.classList.toggle("is-inactive", !enabled);
      toggle.setAttribute("aria-pressed", enabled ? "true" : "false");
      toggle.setAttribute("aria-label", enabled ? "Désactiver le parrainage" : "Activer le parrainage");
    }
    if (status) status.textContent = enabled ? "Activé" : "Désactivé";
    syncReferralOptions(enabled);
    scheduleLocalDraftSave();
  };

  window.toggleReferralL2 = function (checkbox) {
    var field = q("#referral_l2_enabled");
    if (field) field.value = checkbox && checkbox.checked ? "1" : "0";
    scheduleLocalDraftSave();
  };

  window.handleThumb = function (input) {
    var file = input && input.files && input.files[0];
    var form = q("[data-course-create-form]");
    if (!file) return;
    var existing = q("#cover_path_existing");
    if (existing) existing.value = "";
    if (form) removeCoverPreviewRecord(localDraftCoverPreviewKey(form));
    setThumbPreviewFromFile(file);
    captureCoverPreviewDraft(form, file);
    updateStepButtons();
    if (autosaveReady && form) saveLocalDraft(form);
    else scheduleLocalDraftSave();
  };

  window.removeThumb = function () {
    var form = q("[data-course-create-form]");
    var input = q("#cover_file");
    var existing = q("#cover_path_existing");
    if (input) input.value = "";
    if (existing) existing.value = "";
    revokeThumbObjectUrl();
    setThumbPreview("");
    updateStepButtons();
    if (form) {
      lastSavedCoverSignature = "";
      removeCoverPreviewRecord(localDraftCoverPreviewKey(form));
      removeCoverDraftFile(localDraftCoverKey(form));
    }
    if (autosaveReady && form) saveLocalDraft(form);
    else scheduleLocalDraftSave();
  };

  window.previewCourse = function () {
    if (!renderPreviewModal()) {
      showToast("L’aperçu complet n’est pas disponible sur cette page.", "error");
      return;
    }
    openPreviewModal();
  };

  window.publishCourse = function () {
    var form = q("[data-course-create-form]");
    var submitButton = form ? q('button[type="submit"][name="submit_action"][value="submit"]', form) : null;
    serializeBuilders();
    if (form && form.requestSubmit && submitButton) {
      form.requestSubmit(submitButton);
      return;
    }
    if (form && !submitButton) {
      var fallbackAction = q('input[name="submit_action"][data-publish-fallback="1"]', form);
      if (!fallbackAction) {
        fallbackAction = document.createElement("input");
        fallbackAction.type = "hidden";
        fallbackAction.name = "submit_action";
        fallbackAction.setAttribute("data-publish-fallback", "1");
        form.appendChild(fallbackAction);
      }
      fallbackAction.value = "submit";
    }
    if (form && form.requestSubmit) form.requestSubmit();
    else if (form) form.submit();
  };

  function showToast(message, type) {
    var toast = q("#toast");
    var icon = q("#toast-icon");
    var msg = q("#toast-msg");
    if (!toast || !msg) return;
    msg.textContent = message;
    if (icon) {
      icon.className = type === "error"
        ? "fa-solid fa-circle-exclamation text-red-400"
        : "fa-solid fa-circle-check text-emerald-400";
    }
    toast.classList.remove("translate-y-20", "opacity-0");
    window.clearTimeout(showToast.timer);
    showToast.timer = window.setTimeout(function () {
      toast.classList.add("translate-y-20", "opacity-0");
    }, 2400);
  }

  function hasSimpleValue(containerId) {
    return qa("#" + containerId + " input").some(function (input) {
      return clean(input.value) !== "";
    });
  }

  function updatePedagogyCount(openWhenFilled) {
    var count = ["objectives-container", "prerequisites-container", "equipment-container"].reduce(function (total, containerId) {
      return total + simpleRowValues(containerId).length;
    }, 0);
    var label = q("[data-pedagogy-count]");
    if (label) {
      label.textContent = count + (count > 1 ? " éléments" : " élément");
    }
    if (openWhenFilled && count > 0) {
      var details = label && label.closest ? label.closest("details") : null;
      if (details) details.open = true;
    }
  }

  function firstBlockingIssue() {
    var step1Issue = blockingIssueForStep(2);
    if (step1Issue) return step1Issue;
    if (!hasSimpleValue("objectives-container")) {
      return { step: 2, element: q("#objectives-container input"), message: "Ajoutez au moins un objectif pédagogique." };
    }
    var step2Issue = blockingIssueForStep(4);
    if (step2Issue) return step2Issue;
    var step3Issue = blockingIssueForStep(6);
    if (step3Issue) return step3Issue;
    var step4Issue = blockingIssueForStep(8);
    if (step4Issue) return step4Issue;
    return null;
  }

  function isInteractiveTarget(target) {
    return !!(target && target.closest && target.closest("a,button,input,select,textarea,label"));
  }

  function localDraftKey(form) {
    return clean(form && form.getAttribute("data-autosave-key"));
  }

  function localDraftCoverKey(form) {
    var key = localDraftKey(form);
    return key === "" ? "" : key + ":cover";
  }

  function localStorageRef() {
    try {
      return window.localStorage || null;
    } catch (_e) {
      return null;
    }
  }

  function previewStorageRefs() {
    var refs = [];
    try {
      if (window.sessionStorage) refs.push(window.sessionStorage);
    } catch (_e) {}
    try {
      if (window.localStorage) refs.push(window.localStorage);
    } catch (_e2) {}
    return refs;
  }

  function localDraftCoverPreviewKey(form) {
    var key = localDraftKey(form);
    return key === "" ? "" : key + ":cover_preview";
  }

  function readCoverPreviewRecord(key) {
    if (key === "") return null;
    return previewStorageRefs().reduce(function (found, storage) {
      if (found || !storage) return found;
      try {
        var raw = storage.getItem(key);
        if (!raw) return null;
        var parsed = JSON.parse(raw);
        return parsed && typeof parsed === "object" ? parsed : null;
      } catch (_e) {
        return null;
      }
    }, null);
  }

  function writeCoverPreviewRecord(key, record) {
    if (key === "") return false;
    var payload = JSON.stringify(record);
    var refs = previewStorageRefs();
    for (var i = 0; i < refs.length; i += 1) {
      try {
        refs[i].setItem(key, payload);
        for (var j = 0; j < refs.length; j += 1) {
          if (j === i) continue;
          try {
            refs[j].removeItem(key);
          } catch (_cleanupError) {
            // Keep the freshest preview even if one backend cannot be cleaned up.
          }
        }
        return true;
      } catch (_e) {
        // Try the next storage backend.
      }
    }
    return false;
  }

  function removeCoverPreviewRecord(key) {
    if (key === "") return;
    previewStorageRefs().forEach(function (storage) {
      try {
        storage.removeItem(key);
      } catch (_e) {
        // Ignore storage cleanup failures.
      }
    });
  }

  function fileFromDataUrl(dataUrl, name, type, lastModified) {
    var source = clean(dataUrl);
    if (source === "" || source.indexOf("data:") !== 0) return null;

    var commaIndex = source.indexOf(",");
    if (commaIndex === -1) return null;

    var header = source.slice(5, commaIndex);
    var payload = source.slice(commaIndex + 1);
    var mime = clean(type || header.replace(/;base64/i, ""));

    try {
      var binary = /;base64/i.test(header) ? window.atob(payload) : decodeURIComponent(payload);
      var bytes = new Uint8Array(binary.length);
      for (var i = 0; i < binary.length; i += 1) {
        bytes[i] = binary.charCodeAt(i);
      }

      var blob = new Blob([bytes], { type: mime || "application/octet-stream" });
      var fileName = clean(name) || "miniature";
      var modifiedAt = numberValue(lastModified, Date.now());

      if (typeof File === "function") {
        return new File([blob], fileName, {
          type: blob.type || mime || "",
          lastModified: modifiedAt
        });
      }

      blob.name = fileName;
      blob.lastModified = modifiedAt;
      return blob;
    } catch (_e) {
      return null;
    }
  }

  function coverDraftDb() {
    if (!window.indexedDB) return Promise.resolve(null);
    if (coverDraftDb.cache) return coverDraftDb.cache;

    coverDraftDb.cache = new Promise(function (resolve) {
      try {
        var request = window.indexedDB.open("ecole-course-drafts", 1);
        request.onupgradeneeded = function () {
          var db = request.result;
          if (db && !db.objectStoreNames.contains("files")) {
            db.createObjectStore("files");
          }
        };
        request.onsuccess = function () {
          resolve(request.result || null);
        };
        request.onerror = function () {
          resolve(null);
        };
      } catch (_error) {
        resolve(null);
      }
    });

    return coverDraftDb.cache;
  }

  function writeCoverDraftFile(key, file) {
    return coverDraftDb().then(function (db) {
      return new Promise(function (resolve) {
        if (!key || !db || !file) {
          resolve(false);
          return;
        }

        try {
          var transaction = db.transaction("files", "readwrite");
          transaction.oncomplete = function () { resolve(true); };
          transaction.onabort = transaction.onerror = function () { resolve(false); };
          transaction.objectStore("files").put({
            file: file,
            name: file.name || "miniature",
            type: file.type || "",
            size: file.size || 0,
            lastModified: file.lastModified || Date.now()
          }, key);
        } catch (_error) {
          resolve(false);
        }
      });
    });
  }

  function readCoverDraftFile(key) {
    return coverDraftDb().then(function (db) {
      return new Promise(function (resolve) {
        if (!key || !db) {
          resolve(null);
          return;
        }

        try {
          var transaction = db.transaction("files", "readonly");
          var request = transaction.objectStore("files").get(key);
          request.onsuccess = function () {
            resolve(request.result || null);
          };
          request.onerror = function () {
            resolve(null);
          };
        } catch (_error) {
          resolve(null);
        }
      });
    });
  }

  function removeCoverDraftFile(key) {
    return coverDraftDb().then(function (db) {
      return new Promise(function (resolve) {
        if (!key || !db) {
          resolve(false);
          return;
        }

        try {
          var transaction = db.transaction("files", "readwrite");
          transaction.oncomplete = function () { resolve(true); };
          transaction.onabort = transaction.onerror = function () { resolve(false); };
          transaction.objectStore("files").delete(key);
        } catch (_error) {
          resolve(false);
        }
      });
    });
  }

  function coverDraftFileFromRecord(record) {
    if (!record) return null;
    var blob = record.file instanceof Blob ? record.file : (record instanceof Blob ? record : null);
    if (!blob) return null;

    var name = clean(record.name || blob.name || "miniature");
    var type = clean(record.type || blob.type || "application/octet-stream");
    var lastModified = numberValue(record.lastModified, Date.now());

    try {
      if (typeof File === "function") {
        return new File([blob], name, {
          type: type,
          lastModified: lastModified
        });
      }
    } catch (_error) {
      // Fall back to the raw blob for preview-only restoration.
    }

    blob.name = name;
    blob.lastModified = lastModified;
    return blob;
  }

  function setThumbPreview(src) {
    var img = q("#thumb-img");
    var preview = q("#thumb-preview");
    var placeholder = q("#thumb-placeholder");
    var hasSource = clean(src) !== "";

    if (img) {
      if (hasSource) img.src = src;
      else img.removeAttribute("src");
    }
    if (preview) preview.classList.toggle("hidden", !hasSource);
    if (placeholder) placeholder.classList.toggle("hidden", hasSource);
  }

  function setThumbPreviewFromFile(file) {
    revokeThumbObjectUrl();
    if (!file) {
      setThumbPreview("");
      return;
    }
    if (window.URL && window.URL.createObjectURL) {
      thumbObjectUrl = window.URL.createObjectURL(file);
      setThumbPreview(thumbObjectUrl);
      return;
    }
    if (typeof FileReader === "function") {
      var reader = new FileReader();
      reader.onload = function (event) {
        setThumbPreview(clean(event && event.target && event.target.result));
      };
      reader.readAsDataURL(file);
    }
  }

  function captureCoverPreviewDraft(form, file) {
    var key = localDraftCoverPreviewKey(form);
    if (key === "" || !file || typeof FileReader !== "function") {
      removeCoverPreviewRecord(key);
      return;
    }

    try {
      var reader = new FileReader();
      reader.onload = function (event) {
        var dataUrl = clean(event && event.target && event.target.result);
        if (dataUrl === "") return;
        writeCoverPreviewRecord(key, {
          data_url: dataUrl,
          name: clean(file.name),
          type: clean(file.type),
          size: String(file.size || 0),
          last_modified: String(file.lastModified || Date.now())
        });
      };
      reader.onerror = function () {
        removeCoverPreviewRecord(key);
      };
      reader.readAsDataURL(file);
    } catch (_e) {
      removeCoverPreviewRecord(key);
    }
  }

  function assignFileToInput(input, file) {
    try {
      if (!input || !file || typeof DataTransfer === "undefined") return false;
      var transfer = new DataTransfer();
      transfer.items.add(file);
      input.files = transfer.files;
      return !!(input.files && input.files.length);
    } catch (_error) {
      return false;
    }
  }

  function syncCoverDraftStorage(form) {
    var key = localDraftCoverKey(form);
    var input = q("#cover_file", form);
    var file = input && input.files && input.files.length ? input.files[0] : null;
    var signature = fileSignature(file);

    if (key === "") return;

    if (!file) {
      lastSavedCoverSignature = "";
      removeCoverDraftFile(key);
      return;
    }

    if (signature === lastSavedCoverSignature) return;

    lastSavedCoverSignature = signature;
    writeCoverDraftFile(key, file).then(function (saved) {
      if (!saved && lastSavedCoverSignature === signature) {
        lastSavedCoverSignature = "";
      }
    });
  }

  function restoreCoverDraftStorage(form) {
    var key = localDraftCoverKey(form);
    var input = q("#cover_file", form);
    var existing = q("#cover_path_existing", form);

    return readCoverDraftFile(key).then(function (record) {
      var file = coverDraftFileFromRecord(record);
      if (!file) return false;

      if (existing) existing.value = "";
      assignFileToInput(input, file);
      setThumbPreviewFromFile(file);
      updateStepButtons();
      lastSavedCoverSignature = fileSignature(file);
      return true;
    });
  }

  function restoreCoverPreviewStorage(form) {
    var key = localDraftCoverPreviewKey(form);
    var input = q("#cover_file", form);
    var existing = q("#cover_path_existing", form);
    var record = readCoverPreviewRecord(key);
    if (!record) return false;

    var dataUrl = clean(record.data_url);
    if (dataUrl === "") return false;

    var file = fileFromDataUrl(dataUrl, record.name, record.type, record.last_modified);
    if (existing) existing.value = "";

    if (file) {
      assignFileToInput(input, file);
      setThumbPreviewFromFile(file);
      lastSavedCoverSignature = fileSignature(file);
      updateStepButtons();
      return true;
    }

    setThumbPreview(dataUrl);
    updateStepButtons();
    return true;
  }

  function setFieldValue(root, selector, value) {
    var field = q(selector, root);
    if (field && value !== undefined && value !== null) {
      field.value = String(value);
    }
  }

  function setCheckboxChecked(root, selector, value) {
    var field = q(selector, root);
    if (!field) return;
    field.checked = value === true || clean(value) === "1" || clean(value).toLowerCase() === "true";
  }

  function simpleRowValues(containerId) {
    return qa("#" + containerId + " input").map(function (input) {
      return clean(input.value);
    }).filter(Boolean);
  }

  function setSimpleRows(containerId, name, placeholder, values) {
    var container = q("#" + containerId);
    if (!container || !Array.isArray(values)) return;
    container.innerHTML = "";
    var rows = values.length ? values : [""];
    rows.forEach(function (value) {
      container.insertAdjacentHTML("beforeend", '' +
        '<div class="flex items-center gap-2">' +
          '<input type="text" name="' + name + '[]" value="' + escapeHtml(value) + '" placeholder="' + escapeHtml(placeholder) + '" class="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">' +
          '<button type="button" data-remove-simple-row class="text-slate-400 hover:text-red-500 transition" aria-label="Supprimer"><i class="fa-solid fa-trash-can"></i></button>' +
        '</div>');
    });
    updatePedagogyCount(true);
  }

  function readLocalDraft(form) {
    var key = localDraftKey(form);
    var storage = localStorageRef();
    if (key === "" || !storage) return null;
    try {
      var raw = storage.getItem(key);
      if (!raw) return null;
      var parsed = JSON.parse(raw);
      return parsed && typeof parsed === "object" ? parsed : null;
    } catch (_e) {
      return null;
    }
  }

  function removeLocalDraft(form) {
    var key = localDraftKey(form);
    var coverKey = localDraftCoverKey(form);
    var coverPreviewKey = localDraftCoverPreviewKey(form);
    var storage = localStorageRef();
    if (coverKey !== "") {
      lastSavedCoverSignature = "";
      removeCoverDraftFile(coverKey);
    }
    if (coverPreviewKey !== "") {
      removeCoverPreviewRecord(coverPreviewKey);
    }
    if (key === "" || !storage) return;
    try {
      storage.removeItem(key);
    } catch (_e) {
      // Storage can be disabled in private browsing.
    }
  }

  function focusDraftFallback(form) {
    var fallback = q("#title", form) || q("#description", form) || q('textarea, select, button, input:not([type="hidden"])', form);
    if (fallback && typeof fallback.focus === "function") {
      try {
        fallback.focus({ preventScroll: true });
      } catch (_error) {
        try {
          fallback.focus();
        } catch (_fallbackError) {
          // Ignore focus failures.
        }
      }
      return;
    }

    if (document.activeElement && typeof document.activeElement.blur === "function") {
      document.activeElement.blur();
    }
  }

  function draftHasMeaningfulContent(draft) {
    if (!draft || typeof draft !== "object") return false;

    var fields = draft.fields && typeof draft.fields === "object" ? draft.fields : {};
    var lists = draft.lists && typeof draft.lists === "object" ? draft.lists : {};
    var content = draft.content && typeof draft.content === "object" ? draft.content : {};
    var exams = draft.exams && typeof draft.exams === "object" ? draft.exams : {};

    var fieldChecks = [
      clean(fields.title) !== "",
      clean(fields.description) !== "",
      clean(fields.price_xof) !== "",
      clean(fields.enterprise_price_xof) !== "",
      clean(fields.enterprise_discount_percent) !== "",
      clean(fields.category_id) !== "",
      clean(fields.cover_path_existing) !== "",
      clean(fields.cover_file_name) !== "",
      clean(fields.cover_file_type) !== "",
      clean(fields.cover_file_size) !== "" && clean(fields.cover_file_size) !== "0",
      clean(fields.referral_override) === "1",
      clean(fields.referral_l2_enabled) === "1",
      clean(fields.general_file_clear) === "1",
      clean(fields.level) !== "" && clean(fields.level) !== "beginner",
      clean(fields.duration_hours) !== "" && clean(fields.duration_hours) !== "1",
      clean(fields.enterprise_pricing_mode) !== "" && clean(fields.enterprise_pricing_mode) !== "none"
    ];

    if (fieldChecks.some(Boolean)) return true;
    if (asArray(lists.objectives).some(function (value) { return clean(value) !== ""; })) return true;
    if (asArray(lists.prerequisites).some(function (value) { return clean(value) !== ""; })) return true;
    if (asArray(lists.equipment).some(function (value) { return clean(value) !== ""; })) return true;
    if (asArray(content.modules).length > 0) return true;
    if (asArray(exams.exams).length > 0) return true;
    return false;
  }

  function formatDraftSavedAt(savedAt) {
    var ts = Number(savedAt || 0);
    if (!isFinite(ts) || ts <= 0) return "";

    try {
      return new Intl.DateTimeFormat("fr-FR", {
        dateStyle: "medium",
        timeStyle: "short"
      }).format(new Date(ts));
    } catch (_e) {
      try {
        return new Date(ts).toLocaleString("fr-FR");
      } catch (_e2) {
        return "";
      }
    }
  }

  function syncDraftPromptView(view) {
    var nextView = view === "list" ? "list" : "choice";
    draftPrompt.view = nextView;
    if (draftPrompt.choicePanel) {
      draftPrompt.choicePanel.hidden = nextView !== "choice";
    }
    if (draftPrompt.listPanel) {
      draftPrompt.listPanel.hidden = nextView !== "list";
    }
  }

  function focusDraftView() {
    if (!draftPrompt.root) return;

    var target = null;
    if (draftPrompt.view === "list") {
      target = q("[data-course-create-draft-list-panel] a[href]", draftPrompt.root)
        || draftPrompt.restoreLocalBtn
        || draftPrompt.backBtn;
    } else {
      target = draftPrompt.continueBtn || draftPrompt.newBtn;
    }

    if (!target || typeof target.focus !== "function") return;

    try {
      target.focus({ preventScroll: true });
    } catch (_error) {
      try {
        target.focus();
      } catch (_fallbackError) {
        // Ignore focus failures; the modal must remain usable.
      }
    }
  }

  function hideDraftPrompt() {
    if (!draftPrompt.root) return;
    focusDraftFallback(draftPrompt.form);
    syncDraftPromptView("choice");
    draftPrompt.root.style.display = "none";
    draftPrompt.root.hidden = true;
    document.body.classList.remove("course-builder-modal-open");
  }

  function showDraftPrompt(form, draft) {
    if (!draftPrompt.enabled || !draftPrompt.root) return;

    draftPrompt.pending = draft && typeof draft === "object" ? draft : null;
    if (draftPrompt.meta) {
      var savedLabel = formatDraftSavedAt(draftPrompt.pending && draftPrompt.pending.saved_at);
      draftPrompt.meta.textContent = savedLabel !== ""
        ? "Dernière sauvegarde : " + savedLabel
        : "Le brouillon local a été conservé sur cet appareil.";
    }

    draftPrompt.root.hidden = false;
    draftPrompt.root.style.display = "flex";
    document.body.classList.add("course-builder-modal-open");
    if (draftPrompt.meta) {
      var baseMessage = draftPrompt.pending && draftPrompt.pending.saved_at
        ? "Dernière sauvegarde locale : " + formatDraftSavedAt(draftPrompt.pending.saved_at) + "."
        : "Le brouillon local a été conservé sur cet appareil.";
      draftPrompt.meta.textContent = baseMessage + " Vous pouvez aussi ouvrir un brouillon enregistré ci-dessous.";
    }

    if (draftPrompt.continueBtn && typeof draftPrompt.continueBtn.focus === "function") {
      try {
        draftPrompt.continueBtn.focus({ preventScroll: true });
      } catch (_error) {
        try {
          draftPrompt.continueBtn.focus();
        } catch (_fallbackError) {
          // Ignore focus failures; the modal must remain usable.
        }
      }
    }
  }

  function restoreDraftIntoForm(form, draft) {
    var localDraft = draft && typeof draft === "object" ? draft : readLocalDraft(form);
    if (!localDraft) {
      return { restored: false, step: 1 };
    }

    autosaveReady = false;
    var restored = restoreLocalDraft(form, localDraft);
    var contentPayload = parsePayload(q("#content_payload", form), { modules: [] });
    var examsPayload = parsePayload(q("#exams_payload", form), { exams: [] });

    renderModules(asArray(contentPayload.modules));
    renderExams(asArray(examsPayload.exams));
    syncEnterprisePricing();
    syncReferralToggleUi();
    updatePedagogyCount(true);
    showStep(restored.step || 1, false, "replace");
    serializeBuilders();
    autosaveReady = true;

    return restored;
  }

  function collectLocalDraft(form) {
    serializeContent();
    serializeExams();
    var coverInput = q("#cover_file", form);
    var coverFile = coverInput && coverInput.files && coverInput.files.length ? coverInput.files[0] : null;
    return {
      version: 2,
      saved_at: Date.now(),
      step: currentStep,
      fields: {
        title: q("#title", form) ? q("#title", form).value : "",
        description: q("#description", form) ? q("#description", form).value : "",
        price_xof: q("#price_xof", form) ? q("#price_xof", form).value : "",
        enterprise_pricing_mode: q("#enterprise_pricing_mode", form) ? q("#enterprise_pricing_mode", form).value : "none",
        enterprise_price_xof: q("#enterprise_price_xof", form) ? q("#enterprise_price_xof", form).value : "",
        enterprise_discount_percent: q("#enterprise_discount_percent", form) ? q("#enterprise_discount_percent", form).value : "",
        category_id: q("#category_id", form) ? q("#category_id", form).value : "",
        cover_path_existing: q("#cover_path_existing", form) ? q("#cover_path_existing", form).value : "",
        has_cover_file: coverFile ? "1" : "0",
        cover_file_name: coverFile ? clean(coverFile.name) : "",
        cover_file_type: coverFile ? clean(coverFile.type) : "",
        cover_file_size: coverFile ? String(coverFile.size || 0) : "0",
        referral_override: q("#referral_override", form) ? q("#referral_override", form).value : "0",
        referral_l2_enabled: q("#referral_l2_enabled", form) ? q("#referral_l2_enabled", form).value : "0",
        general_file_clear: q('input[name="general_file_clear"]', form) && q('input[name="general_file_clear"]', form).checked ? "1" : "0",
        level: q("#level", form) ? q("#level", form).value : "beginner",
        duration_hours: q("#duration_hours", form) ? q("#duration_hours", form).value : "1"
      },
      lists: {
        objectives: simpleRowValues("objectives-container"),
        prerequisites: simpleRowValues("prerequisites-container"),
        equipment: simpleRowValues("equipment-container")
      },
      content: parsePayload(q("#content_payload", form), { modules: [] }),
      exams: parsePayload(q("#exams_payload", form), { exams: [] })
    };
  }

  function saveLocalDraft(form) {
    var key = localDraftKey(form);
    var storage = localStorageRef();
    if (key === "" || !storage || form.getAttribute("data-autosave-clear") === "1") return;
    try {
      storage.setItem(key, JSON.stringify(collectLocalDraft(form)));
    } catch (_e) {
      // Ignore quota/storage failures; server-side draft remains available.
    }
    syncCoverDraftStorage(form);
  }

  function scheduleLocalDraftSave() {
    if (!autosaveReady) return;
    var form = q("[data-course-create-form]");
    if (!form) return;
    window.clearTimeout(autosaveTimer);
    autosaveTimer = window.setTimeout(function () {
      saveLocalDraft(form);
    }, 300);
  }

  function restoreLocalDraft(form, draft) {
    if (form.getAttribute("data-autosave-clear") === "1") {
      removeLocalDraft(form);
      return { restored: false, step: 1 };
    }

    var localDraft = draft && typeof draft === "object" ? draft : readLocalDraft(form);
    if (!localDraft) return { restored: false, step: 1 };

    var fields = localDraft.fields && typeof localDraft.fields === "object" ? localDraft.fields : {};
    setFieldValue(form, "#title", fields.title);
    setFieldValue(form, "#description", fields.description);
    setFieldValue(form, "#price_xof", fields.price_xof);
    setFieldValue(form, "#enterprise_pricing_mode", fields.enterprise_pricing_mode);
    setFieldValue(form, "#enterprise_price_xof", fields.enterprise_price_xof);
    setFieldValue(form, "#enterprise_discount_percent", fields.enterprise_discount_percent);
    setFieldValue(form, "#category_id", fields.category_id);
    setFieldValue(form, "#cover_path_existing", fields.cover_path_existing);
    setFieldValue(form, "#referral_override", fields.referral_override);
    setFieldValue(form, "#referral_l2_enabled", fields.referral_l2_enabled);
    setCheckboxChecked(form, "#referral-l2-toggle", fields.referral_l2_enabled);
    setCheckboxChecked(form, 'input[name="general_file_clear"]', fields.general_file_clear);
    setFieldValue(form, "#level", fields.level);
    setFieldValue(form, "#duration_hours", fields.duration_hours);

    var lists = localDraft.lists && typeof localDraft.lists === "object" ? localDraft.lists : {};
    setSimpleRows("objectives-container", "objectives", "Objectif", asArray(lists.objectives));
    setSimpleRows("prerequisites-container", "prerequisites", "Prérequis", asArray(lists.prerequisites));
    setSimpleRows("equipment-container", "equipment", "Équipement", asArray(lists.equipment));

    if (localDraft.content && typeof localDraft.content === "object" && q("#content_payload", form)) {
      q("#content_payload", form).value = JSON.stringify(localDraft.content);
    }
    if (localDraft.exams && typeof localDraft.exams === "object" && q("#exams_payload", form)) {
      q("#exams_payload", form).value = JSON.stringify(localDraft.exams);
    }

    if (clean(fields.has_cover_file) === "1") {
      if (!restoreCoverPreviewStorage(form)) {
        restoreCoverDraftStorage(form);
      }
    } else {
      lastSavedCoverSignature = "";
      removeCoverPreviewRecord(localDraftCoverPreviewKey(form));
      removeCoverDraftFile(localDraftCoverKey(form));
    }

    return {
      restored: true,
      step: Math.max(1, Math.min(totalSteps, numberValue(localDraft.step, 1)))
    };
  }

  function bindCourseCreateEvents(form) {
    form.addEventListener("click", function (event) {
      var target = event.target;
      if (!target || !target.closest) return;

      var stepButton = target.closest("[data-step-target]");
      if (stepButton) {
        event.preventDefault();
        window.goToStep(numberValue(stepButton.getAttribute("data-step-target"), 1));
        return;
      }

      if (target.closest("[data-preview-course]")) {
        event.preventDefault();
        window.previewCourse();
        return;
      }

      var addSimpleButton = target.closest("[data-add-simple-row]");
      if (addSimpleButton) {
        event.preventDefault();
        addSimpleRow(
          addSimpleButton.getAttribute("data-container") || "",
          addSimpleButton.getAttribute("data-name") || "",
          addSimpleButton.getAttribute("data-placeholder") || ""
        );
        return;
      }

      if (target.closest("[data-remove-simple-row]")) {
        event.preventDefault();
        window.removeItem(target.closest("[data-remove-simple-row]"));
        return;
      }

      if (target.closest("[data-remove-thumb]")) {
        event.preventDefault();
        event.stopPropagation();
        window.removeThumb();
        return;
      }

      if (target.closest("[data-thumbnail-zone]")) {
        var coverInput = q("#cover_file");
        if (coverInput) coverInput.click();
        return;
      }

      if (target.closest("[data-referral-toggle]")) {
        event.preventDefault();
        window.toggleReferral();
        return;
      }

      if (target.closest("[data-add-module]")) {
        event.preventDefault();
        window.addModule();
        return;
      }

      if (target.closest("[data-add-lesson]")) {
        event.preventDefault();
        window.addLesson(target.closest("[data-add-lesson]"));
        return;
      }

      if (target.closest("[data-remove-module]")) {
        event.preventDefault();
        window.removeModule(target.closest("[data-remove-module]"));
        return;
      }

      if (target.closest("[data-remove-lesson]")) {
        event.preventDefault();
        window.removeLesson(target.closest("[data-remove-lesson]"));
        return;
      }

      if (target.closest("[data-toggle-lesson]")) {
        event.preventDefault();
        toggleLesson(target.closest("[data-toggle-lesson]"));
        return;
      }

      if (target.closest("[data-qcm-import-button]")) {
        event.preventDefault();
        window.importQcmFile(target.closest("[data-qcm-import-button]"));
        return;
      }

      if (target.closest("[data-qcm-version-tab]")) {
        event.preventDefault();
        window.showQcmVersion(target.closest("[data-qcm-version-tab]"));
        return;
      }

      if (target.closest("[data-qcm-version-prev]")) {
        event.preventDefault();
        window.moveQcmVersion(target.closest("[data-qcm-version-prev]"), -1);
        return;
      }

      if (target.closest("[data-qcm-version-next]")) {
        event.preventDefault();
        window.moveQcmVersion(target.closest("[data-qcm-version-next]"), 1);
        return;
      }

      if (target.closest("[data-add-question]")) {
        event.preventDefault();
        window.addQuestion(target.closest("[data-add-question]"));
        return;
      }

      if (target.closest("[data-remove-question]")) {
        event.preventDefault();
        window.removeQuestion(target.closest("[data-remove-question]"));
        return;
      }

      if (target.closest("[data-add-option]")) {
        event.preventDefault();
        window.addOption(target.closest("[data-add-option]"));
        return;
      }

      if (target.closest("[data-remove-option]")) {
        event.preventDefault();
        window.removeOption(target.closest("[data-remove-option]"));
        return;
      }

      var moduleHeader = target.closest("[data-toggle-module]");
      if (moduleHeader && !isInteractiveTarget(target)) {
        event.preventDefault();
        window.toggleModule(moduleHeader);
        return;
      }

      var lessonHeader = target.closest("[data-toggle-lesson-head]");
      if (lessonHeader && !isInteractiveTarget(target)) {
        event.preventDefault();
        toggleLesson(lessonHeader);
        return;
      }

      var examHeader = target.closest("[data-toggle-exam]");
      if (examHeader && !isInteractiveTarget(target)) {
        event.preventDefault();
        window.toggleExam(examHeader);
      }
    });

    form.addEventListener("change", function (event) {
      var target = event.target;
      if (!target || !target.matches) return;

      if (target.matches("#cover_file")) {
        window.handleThumb(target);
        return;
      }

      if (target.matches("[data-referral-l2-toggle]")) {
        window.toggleReferralL2(target);
        return;
      }

      if (target.matches("[data-lesson-type-select]")) {
        window.handleLessonTypeChange(target);
        return;
      }

      if (target.matches("[data-lesson-file-input]")) {
        window.handleLessonFileChange(target);
        return;
      }

      if (target.matches("[data-exam-type-select]")) {
        window.handleExamTypeChange(target);
        return;
      }

      if (target.matches("[data-qcm-version-count]")) {
        window.updateQCMVersions(target);
        return;
      }

      if (target.matches("[data-question-type-select]")) {
        window.handleQuestionTypeChange(target);
      }
    });
  }

  ready(function () {
    var form = q("[data-course-create-form]");
    if (!form) return;

    var requestedStep = requestedStepFromUrl();
    draftPrompt.enabled = clean(form.getAttribute("data-course-create-draft-prompt")) === "1";
    draftPrompt.root = q("#course-create-draft-modal");
    draftPrompt.meta = q("[data-course-create-draft-meta]", draftPrompt.root);
    draftPrompt.form = form;
    draftPrompt.continueBtn = q("#course-create-continue-draft-btn", draftPrompt.root);
    draftPrompt.newBtn = q("#course-create-new-course-btn", draftPrompt.root);
    draftPrompt.backBtn = q("#course-create-back-to-choice-btn", draftPrompt.root);
    draftPrompt.restoreLocalBtn = q("#course-create-restore-local-draft-btn", draftPrompt.root);
    draftPrompt.choicePanel = q("[data-course-create-draft-choice-panel]", draftPrompt.root);
    draftPrompt.listPanel = q("[data-course-create-draft-list-panel]", draftPrompt.root);
    var pendingDraft = draftPrompt.enabled ? readLocalDraft(form) : null;
    bindCourseCreateEvents(form);

    var contentPayload = parsePayload(q("#content_payload", form), { modules: [] });
    var examsPayload = parsePayload(q("#exams_payload", form), { exams: [] });

    renderModules(asArray(contentPayload.modules));
    renderExams(asArray(examsPayload.exams));
    serializeBuilders();
    syncEnterprisePricing();
    syncReferralToggleUi();
    updatePedagogyCount(false);
    autosaveReady = false;
    showStep(requestedStep || 1, false, "replace");
    if (draftPrompt.enabled && pendingDraft) {
      showDraftPrompt(form, pendingDraft);
    } else {
      hideDraftPrompt();
      draftPrompt.pending = null;
    }
    autosaveReady = true;

    if (draftPrompt.continueBtn) {
      draftPrompt.continueBtn.addEventListener("click", function () {
        syncDraftPromptView("list");
        focusDraftView();
        return;
        var draft = draftPrompt.pending || (draftPrompt.enabled ? readLocalDraft(form) : null);
        hideDraftPrompt();
        if (!draft) {
          draftPrompt.pending = null;
          autosaveReady = true;
          return;
        }
        draftPrompt.pending = null;
        autosaveReady = false;
        restoreDraftIntoForm(form, draft);
        showToast("Brouillon local restauré. Vérifiez les fichiers avant publication.", "info");
      });
    }

    if (draftPrompt.backBtn) {
      draftPrompt.backBtn.addEventListener("click", function () {
        syncDraftPromptView("choice");
        focusDraftView();
      });
    }

    if (draftPrompt.restoreLocalBtn) {
      draftPrompt.restoreLocalBtn.addEventListener("click", function () {
        var draft = draftPrompt.pending || (draftPrompt.enabled ? readLocalDraft(form) : null);
        if (!draft) {
          showToast("Aucun brouillon local disponible.", "error");
          return;
        }
        draftPrompt.pending = null;
        autosaveReady = false;
        hideDraftPrompt();
        restoreDraftIntoForm(form, draft);
        showToast("Brouillon local restauré. Vérifiez les fichiers avant publication.", "info");
      });
    }

    if (draftPrompt.newBtn) {
      draftPrompt.newBtn.addEventListener("click", function () {
        removeLocalDraft(form);
        draftPrompt.pending = null;
        hideDraftPrompt();
        autosaveReady = true;
        showToast("Nouveau cours prêt. Le brouillon local a été supprimé.", "info");
      });
    }

    if (draftPrompt.root) {
      draftPrompt.root.addEventListener("click", function (event) {
        if (!event || !event.target || !event.target.closest) return;
        if (event.target.closest("[data-course-create-draft-backdrop]")) {
          hideDraftPrompt();
        }
      });
    }

    var previewModal = q("#course-preview-modal");
    var previewBackdrop = previewModal ? q("[data-course-preview-backdrop]", previewModal) : null;
    var previewCloseBtn = previewModal ? q("[data-course-preview-close]", previewModal) : null;
    var previewPublishBtn = previewModal ? q("[data-preview-publish]", previewModal) : null;

    if (previewBackdrop) {
      previewBackdrop.addEventListener("click", closePreviewModal);
    }
    if (previewCloseBtn) {
      previewCloseBtn.addEventListener("click", closePreviewModal);
    }
    if (previewPublishBtn) {
      previewPublishBtn.addEventListener("click", function () {
        closePreviewModal();
        window.publishCourse();
      });
    }
    if (previewModal) {
      previewModal.addEventListener("click", function (event) {
        if (!event || !event.target || !event.target.closest) return;
        if (event.target.closest("[data-course-preview-backdrop]")) {
          closePreviewModal();
        }
      });
    }

    document.addEventListener("keydown", function (event) {
      if (previewModal && !previewModal.hidden && event.key === "Escape") {
        closePreviewModal();
        return;
      }
      if (!draftPrompt.root || draftPrompt.root.hidden) return;
      if (event.key === "Escape") {
        hideDraftPrompt();
      }
    });

    window.addEventListener("popstate", function () {
      var stepFromUrl = requestedStepFromUrl();
      if (!stepFromUrl) return;
      clearInvalidMarkers();
      showStep(stepFromUrl, false, "replace");
    });

    form.addEventListener("input", function (event) {
      if (event.target && event.target.classList) {
        clearInvalidMarkers(event.target);
      }
      if (event.target && (event.target.matches("#enterprise_pricing_mode") || event.target.matches("#enterprise_price_xof") || event.target.matches("#enterprise_discount_percent"))) {
        syncEnterprisePricing();
      }
      if (event.target && event.target.closest && event.target.closest("#objectives-container, #prerequisites-container, #equipment-container")) {
        updatePedagogyCount(false);
      }
      if (event.target && (event.target.closest("#modules-container") || event.target.closest("#exams-container"))) {
        if (event.target.closest("#modules-container")) updateModuleSummaries();
        serializeBuilders();
      }
      updateStepButtons();
      scheduleLocalDraftSave();
    });
    form.addEventListener("change", function (event) {
      if (event.target && event.target.classList) {
        clearInvalidMarkers(event.target);
      }
      if (event.target && event.target.matches("[data-exam-subject-file-input]")) {
        window.handleExamSubjectFileChange(event.target);
      }
      if (event.target && (event.target.matches("#enterprise_pricing_mode") || event.target.matches("#enterprise_price_xof") || event.target.matches("#enterprise_discount_percent"))) {
        syncEnterprisePricing();
      }
      if (event.target && event.target.closest && event.target.closest("#objectives-container, #prerequisites-container, #equipment-container")) {
        updatePedagogyCount(false);
      }
      if (event.target && (event.target.closest("#modules-container") || event.target.closest("#exams-container"))) {
        if (event.target.closest("#modules-container")) updateModuleSummaries();
        serializeBuilders();
      }
      scheduleLocalDraftSave();
    });
    form.addEventListener("submit", function (event) {
      serializeBuilders();
      syncEnterprisePricing();
      var submitter = event.submitter || document.activeElement;
      var fallbackSubmitAction = q('input[name="submit_action"][data-publish-fallback="1"]', form);
      if (submitter && submitter.name === "submit_action" && fallbackSubmitAction) {
        fallbackSubmitAction.remove();
        fallbackSubmitAction = null;
      }
      var submitAction = submitter && submitter.name === "submit_action"
        ? submitter.value
        : clean(fallbackSubmitAction && fallbackSubmitAction.value);
      if (submitAction === "submit") {
        var issue = firstBlockingIssue();
        if (issue) {
          event.preventDefault();
          showBlockingIssue(issue);
          return;
        }
      }
      if (enterprisePricingConflict()) {
        event.preventDefault();
        currentStep = 4;
        window.goToStep(4);
        showToast("Choisissez soit un prix entreprise, soit une réduction.", "error");
      }
    });
  });

})();
