(function () {
  "use strict";

  var currentStep = 1;
  var totalSteps = 3;
  var moduleSeq = 0;
  var examSeq = 0;
  var questionSeq = 0;
  var lessonFileSeq = 0;
  var autosaveReady = false;
  var autosaveTimer = null;

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
        { type: "video", title: "", description: "", resource: "", duration_min: 15 }
      ]
    };
  }

  function defaultNewModule() {
    return {
      title: "",
      description: "",
      items: [{ type: "video", title: "", description: "", resource: "", duration_min: 15 }]
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
    var duration = numberValue(item && item.duration_min, type === "appointment" ? 60 : (type === "video" ? 15 : 0));
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
              '<span>Durée</span>' +
              '<input type="number" min="0" value="' + String(duration) + '" class="lesson-duration course-builder-input" placeholder="15">' +
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
    var duration = Math.max(0, numberValue(q(".lesson-duration", lesson) && q(".lesson-duration", lesson).value, type === "appointment" ? 60 : (type === "video" ? 15 : 0)));
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
    if (lesson.getAttribute("data-auto-collapsed") !== "1") {
      setLessonCollapsed(lesson, true, true);
    }
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
      if (type === "video" && (!youtubeUrlLooksValid(resource) || duration < 1)) {
        lessonComplete = false;
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

  function optionRowHtml(option, index, optionInputType, group, hasCorrect) {
    return '' +
      '<div class="option-row course-builder-option">' +
        '<span class="option-number course-builder-option-number">' + String(index + 1) + '</span>' +
        '<input type="' + optionInputType + '" name="' + group + '" class="course-builder-option-check"' + ((option.correct || (!hasCorrect && index === 0)) ? " checked" : "") + '>' +
        '<input type="text" value="' + escapeHtml(option.text || "") + '" class="option-text course-builder-option-input" placeholder="Proposition ' + String(index + 1) + '">' +
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
      ? String(filledVersions) + "/" + String(versionCount) + " version(s) renseignée(s)"
      : "À configurer";

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
              '<span>Score min (%)</span>' +
              '<input type="number" min="0" max="100" value="' + String(numberValue(exam.min_score, 50)) + '" class="exam-min-score course-builder-exam-input" placeholder="50">' +
            '</label>' +
          '</div>' +
          '<div class="exam-qcm-section course-builder-exam-pane">' +
            '<div class="course-builder-qcm-setup">' +
              '<div class="course-builder-qcm-setup-copy">' +
                '<span class="course-builder-qcm-setup-kicker">QCM</span>' +
                '<h4>Questions et import Excel</h4>' +
                '<p>Configurez les versions A/B/C sans quitter cette page.</p>' +
              '</div>' +
              '<div class="course-builder-qcm-setup-side">' +
                '<span class="course-builder-qcm-setup-status">' + escapeHtml(qcmStatus) + '</span>' +
                '<button type="button" class="course-builder-qcm-setup-btn" data-qcm-modal-open>' +
                  '<i class="fa-solid fa-list-check"></i> Configurer le QCM' +
                '</button>' +
              '</div>' +
            '</div>' +
            '<div class="course-builder-qcm-modal hidden" data-qcm-modal aria-hidden="true">' +
              '<div class="course-builder-qcm-modal-backdrop" data-qcm-modal-close></div>' +
              '<section class="course-builder-qcm-modal-panel" role="dialog" aria-modal="true" aria-label="Configurer le QCM">' +
                '<div class="course-builder-qcm-modal-head">' +
                  '<div>' +
                    '<span class="course-builder-qcm-setup-kicker">QCM</span>' +
                    '<h4>Configurer le QCM</h4>' +
                  '</div>' +
                  '<button type="button" class="course-builder-qcm-modal-close" data-qcm-modal-close aria-label="Fermer le QCM"><i class="fa-solid fa-xmark"></i></button>' +
                '</div>' +
                '<div class="course-builder-qcm-modal-body">' +
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
              '</section>' +
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
          var visibleOptionNumber = String(optionIndex + 1);
          if (optionNumber) optionNumber.textContent = visibleOptionNumber;
          if (optionText) optionText.setAttribute("placeholder", "Proposition " + visibleOptionNumber);
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
        var durationMin = Math.max(0, numberValue(durationInput && durationInput.value, type === "meeting" ? 60 : (type === "video" ? 15 : 0)));
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
    [1, 2, 3].forEach(function (step) {
      var button = q("#stepBtn" + step);
      if (!button) return;
      var bubble = q("span:first-child", button);
      var active = step === currentStep;
      button.classList.toggle("bg-primary-600", active);
      button.classList.toggle("text-white", active);
      button.classList.toggle("shadow-lg", active);
      button.classList.toggle("shadow-primary-500/25", active);
      button.classList.toggle("bg-white", !active);
      button.classList.toggle("text-slate-600", !active);
      button.classList.toggle("border", !active);
      button.classList.toggle("border-slate-200", !active);
      if (bubble) {
        bubble.classList.toggle("bg-white", active);
        bubble.classList.toggle("text-primary-600", active);
        bubble.classList.toggle("bg-slate-100", !active);
        bubble.classList.toggle("text-slate-500", !active);
      }
    });
  }

  function updateProgressBar() {
    var bar1 = q("#progressBar");
    var bar2 = q("#progressBar2");
    if (bar1) bar1.classList.toggle("is-complete", currentStep >= 2);
    if (bar2) bar2.classList.toggle("is-complete", currentStep >= 3);
  }

  function showStep(step, scrollTop) {
    currentStep = step;
    var step1 = q("#step1Content");
    var step2 = q("#step2Content");
    var step3 = q("#step3Content");
    if (step1) step1.classList.toggle("hidden", step !== 1);
    if (step2) step2.classList.toggle("hidden", step !== 2);
    if (step3) step3.classList.toggle("hidden", step !== 3);
    updateStepButtons();
    updateProgressBar();
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
    if (issue.element && issue.element.closest && issue.element.closest("[data-qcm-modal]")) {
      window.openQcmModal(issue.element);
    }
    window.setTimeout(function () {
      focusInvalid(issue.element || null);
    }, 0);
    showToast(issue.message, "error");
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
    if (step === 1) {
      if (!clean(q("#title") && q("#title").value)) {
        return { step: 1, element: q("#title"), message: "Le titre du cours est obligatoire." };
      }
      if (!clean(q("#description") && q("#description").value)) {
        return { step: 1, element: q("#description"), message: "La description du cours est obligatoire." };
      }
      if (!clean(q("#price_xof") && q("#price_xof").value)) {
        return { step: 1, element: q("#price_xof"), message: "Le prix public est obligatoire." };
      }
      var enterpriseMode = q("#enterprise_pricing_mode");
      var enterprisePrice = q("#enterprise_price_xof");
      var enterpriseDiscount = q("#enterprise_discount_percent");
      if (enterpriseMode && enterpriseMode.value === "price" && !clean(enterprisePrice && enterprisePrice.value)) {
        return { step: 1, element: enterprisePrice, message: "Renseignez le prix fixe entreprise ou choisissez un autre mode." };
      }
      if (enterpriseMode && enterpriseMode.value === "discount" && !clean(enterpriseDiscount && enterpriseDiscount.value)) {
        return { step: 1, element: enterpriseDiscount, message: "Renseignez la réduction entreprise ou choisissez un autre mode." };
      }
      if (enterprisePricingConflict()) {
        return { step: 1, element: enterprisePrice || enterpriseDiscount, message: "Choisissez soit un prix entreprise, soit une réduction." };
      }
      if (!clean(q("#category_id") && q("#category_id").value)) {
        return { step: 1, element: q("#category_id"), message: "Sélectionnez une catégorie avant de continuer." };
      }
      if (!clean(q("#cover_path_existing") && q("#cover_path_existing").value)) {
        var cover = q("#cover_file");
        if (!cover || !cover.files || !cover.files.length) {
          return { step: 1, element: q("#thumbnail-zone"), message: "Ajoutez une miniature de cours." };
        }
      }
    }

    if (step === 2) {
      var modules = qa("#modules-container .module-item");
      if (!modules.length) {
        return { step: 2, element: q("[data-add-module]"), message: "Ajoutez au moins un module." };
      }

      for (var moduleIndex = 0; moduleIndex < modules.length; moduleIndex++) {
        var module = modules[moduleIndex];
        var moduleTitle = q(".module-title", module);
        if (!clean(moduleTitle && moduleTitle.value)) {
          return { step: 2, element: moduleTitle, message: "Renseignez le titre du module " + String(moduleIndex + 1) + "." };
        }

        var lessons = qa(".lesson-item", module);
        if (!lessons.length) {
          return { step: 2, element: q("[data-add-lesson]", module), message: "Ajoutez au moins une leçon dans le module " + String(moduleIndex + 1) + "." };
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
            return { step: 2, element: lessonTitle, message: "Renseignez le titre de la " + label + "." };
          }
          if (type === "video") {
            if (!clean(resource && resource.value)) {
              return { step: 2, element: resource, message: "Ajoutez le lien YouTube de la " + label + "." };
            }
            if (!youtubeUrlLooksValid(resource.value)) {
              return { step: 2, element: resource, message: "Le lien YouTube de la " + label + " est invalide." };
            }
            if (numberValue(duration && duration.value, 0) < 1) {
              return { step: 2, element: duration, message: "Renseignez une durée valide pour la " + label + "." };
            }
          }
          if (type === "document" && !(fileInput && fileInput.files && fileInput.files.length) && !uploadedResourceLooksValid(resource && resource.value)) {
            return {
              step: 2,
              element: q(".course-builder-file-drop", lesson) || fileInput,
              message: "Ajoutez le fichier document/exercice de la " + label + "."
            };
          }
        }
      }
    }

    if (step === 3) {
      var exam = q("#exams-container .exam-item");
      if (!exam) {
        return { step: 3, element: q("#exams-container"), message: "Configurez l’évaluation finale." };
      }

      var examTitle = q(".exam-title", exam);
      if (!clean(examTitle && examTitle.value)) {
        return { step: 3, element: examTitle, message: "Renseignez le titre de l’évaluation finale." };
      }

      var examType = q(".exam-type-select", exam) ? q(".exam-type-select", exam).value : "qcm";
      var examDuration = q(".exam-duration", exam);
      if (numberValue(examDuration && examDuration.value, 0) < 1) {
        return { step: 3, element: examDuration, message: "Renseignez une durée valide pour l’évaluation finale." };
      }

      if (examType === "document") {
        var subject = q(".document-subject", exam);
        var subjectFile = q(".exam-subject-file-input", exam);
        var hasSelectedSubject = !!(subjectFile && subjectFile.files && subjectFile.files.length);
        var hasExistingSubject = uploadedResourceLooksValid(subject && subject.value);
        if (!hasSelectedSubject && !hasExistingSubject) {
          return { step: 3, element: q("[data-exam-subject-file-shell]", exam) || subjectFile || subject, message: "Ajoutez le document sujet à uploader pour cette évaluation." };
        }
      }

      if (examType === "qcm") {
        var qcmModal = q("[data-qcm-modal]", exam);
        var qcmOpenButton = q("[data-qcm-modal-open]", exam);
        var qcmMessage = "Complétez le QCM avant de publier.";
        var versionCount = Math.max(1, Math.min(3, numberValue(q(".qcm-version-count", exam) && q(".qcm-version-count", exam).value, 3)));
        var versions = qa(".qcm-version", exam);
        for (var versionIndex = 0; versionIndex < versionCount; versionIndex++) {
          var version = versions[versionIndex];
          var versionLabel = "version " + String.fromCharCode(65 + versionIndex);
          var questions = version ? qa(".question-item", version) : [];
          if (!questions.length) {
            return { step: 3, element: version || qcmModal || qcmOpenButton, message: qcmMessage + " Il manque une question dans la " + versionLabel + "." };
          }
          for (var questionIndex = 0; questionIndex < questions.length; questionIndex++) {
            var question = questions[questionIndex];
            var questionNumber = questionIndex + 1;
            var questionText = q(".question-text", question);
            if (!clean(questionText && questionText.value)) {
              return { step: 3, element: questionText || qcmModal || qcmOpenButton, message: qcmMessage + " Question " + questionNumber + " : intitulé requis." };
            }

            var questionType = q(".question-type-select", question) ? q(".question-type-select", question).value : "single";
            if (questionType !== "text") {
              var optionRows = qa(".option-row", question);
              var filledOptions = optionRows.filter(function (option) {
                return clean(q(".option-text", option) && q(".option-text", option).value) !== "";
              });
              if (filledOptions.length < 2) {
                return { step: 3, element: (optionRows[0] ? q(".option-text", optionRows[0]) : question) || qcmModal || qcmOpenButton, message: qcmMessage + " Question " + questionNumber + " : deux propositions minimum." };
              }
              var missingOption = optionRows.find(function (option) {
                return clean(q(".option-text", option) && q(".option-text", option).value) === "";
              });
              if (missingOption) {
                return { step: 3, element: q(".option-text", missingOption) || qcmModal || qcmOpenButton, message: qcmMessage + " Question " + questionNumber + " : proposition vide." };
              }
              var correctCount = optionRows.filter(function (option) {
                var input = q('input[type="radio"], input[type="checkbox"]', option);
                return !!(input && input.checked);
              }).length;
              if (correctCount < 1) {
                return { step: 3, element: question || qcmModal || qcmOpenButton, message: qcmMessage + " Question " + questionNumber + " : bonne réponse requise." };
              }
              if (questionType === "single" && correctCount > 1) {
                return { step: 3, element: question || qcmModal || qcmOpenButton, message: qcmMessage + " Question " + questionNumber + " : une seule bonne réponse." };
              }
            }
          }
        }
      }
    }

    return null;
  }

  window.goToStep = function (step) {
    if (step < 1 || step > totalSteps) return;
    serializeBuilders();
    if (step > currentStep + 1) {
      step = currentStep + 1;
    }
    if (step > currentStep) {
      var issue = blockingIssueForStep(currentStep);
      if (showBlockingIssue(issue)) return;
    }
    clearInvalidMarkers();
    showStep(step, true);
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
    var previousDefault = previousType === "appointment" ? 60 : (previousType === "video" ? 15 : 0);
    var nextDefault = select.value === "appointment" ? 60 : (select.value === "video" ? 15 : 0);
    var currentDuration = numberValue(duration && duration.value, previousDefault);
    lesson.setAttribute("data-lesson-type", select.value);
    lesson.setAttribute("data-file-key", fileKey);
    if (dynamic) dynamic.innerHTML = lessonDynamicHtml(select.value, "", "", fileKey, "");
    if (durationField) durationField.classList.toggle("hidden", select.value === "appointment");
    if (duration && (clean(duration.value) === "" || currentDuration === previousDefault)) {
      duration.value = String(nextDefault);
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
    var dot = q("#ref-dot");
    var status = q("#ref-status");
    var enabled = clean(field && field.value) === "1";

    if (toggle) {
      toggle.classList.toggle("bg-primary-600", enabled);
      toggle.classList.toggle("bg-slate-300", !enabled);
    }
    if (dot) {
      dot.classList.toggle("translate-x-6", enabled);
      dot.classList.toggle("translate-x-1", !enabled);
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

    if (l2Field && l2Toggle) {
      l2Field.value = l2Toggle.checked ? "1" : "0";
    }
  }

  window.toggleReferral = function () {
    var field = q("#referral_override");
    var toggle = q("#referral-toggle");
    var dot = q("#ref-dot");
    var status = q("#ref-status");
    var enabled = !(field && field.value === "1");
    if (field) field.value = enabled ? "1" : "0";
    if (toggle) {
      toggle.classList.toggle("bg-primary-600", enabled);
      toggle.classList.toggle("bg-slate-300", !enabled);
    }
    if (dot) {
      dot.classList.toggle("translate-x-6", enabled);
      dot.classList.toggle("translate-x-1", !enabled);
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
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function (event) {
      var img = q("#thumb-img");
      var preview = q("#thumb-preview");
      var placeholder = q("#thumb-placeholder");
      if (img) img.src = event.target.result;
      if (preview) preview.classList.remove("hidden");
      if (placeholder) placeholder.classList.add("hidden");
    };
    reader.readAsDataURL(file);
  };

  window.removeThumb = function () {
    var input = q("#cover_file");
    var existing = q("#cover_path_existing");
    var img = q("#thumb-img");
    var preview = q("#thumb-preview");
    var placeholder = q("#thumb-placeholder");
    if (input) input.value = "";
    if (existing) existing.value = "";
    if (img) img.removeAttribute("src");
    if (preview) preview.classList.add("hidden");
    if (placeholder) placeholder.classList.remove("hidden");
    scheduleLocalDraftSave();
  };

  window.previewCourse = function () {
    serializeBuilders();
    var issue = firstBlockingIssue();
    if (issue) {
      showBlockingIssue(issue);
      return;
    }
    showToast("Le brouillon est cohérent pour un enregistrement.", "info");
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
    var step1Issue = blockingIssueForStep(1);
    if (step1Issue) return step1Issue;
    if (!hasSimpleValue("objectives-container")) {
      return { step: 1, element: q("#objectives-container input"), message: "Ajoutez au moins un objectif pédagogique." };
    }
    var step2Issue = blockingIssueForStep(2);
    if (step2Issue) return step2Issue;
    var step3Issue = blockingIssueForStep(3);
    if (step3Issue) return step3Issue;
    return null;
  }

  function isInteractiveTarget(target) {
    return !!(target && target.closest && target.closest("a,button,input,select,textarea,label"));
  }

  function localDraftKey(form) {
    return clean(form && form.getAttribute("data-autosave-key"));
  }

  function localStorageRef() {
    try {
      return window.localStorage || null;
    } catch (_e) {
      return null;
    }
  }

  function setFieldValue(root, selector, value) {
    var field = q(selector, root);
    if (field && value !== undefined && value !== null) {
      field.value = String(value);
    }
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
    var storage = localStorageRef();
    if (key === "" || !storage) return;
    try {
      storage.removeItem(key);
    } catch (_e) {
      // Storage can be disabled in private browsing.
    }
  }

  function collectLocalDraft(form) {
    serializeContent();
    serializeExams();
    return {
      version: 1,
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
        referral_override: q("#referral_override", form) ? q("#referral_override", form).value : "0",
        referral_l2_enabled: q("#referral_l2_enabled", form) ? q("#referral_l2_enabled", form).value : "0",
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

  function restoreLocalDraft(form) {
    if (form.getAttribute("data-autosave-clear") === "1") {
      removeLocalDraft(form);
      return { restored: false, step: 1 };
    }

    var draft = readLocalDraft(form);
    if (!draft) return { restored: false, step: 1 };

    var fields = draft.fields && typeof draft.fields === "object" ? draft.fields : {};
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
    setFieldValue(form, "#level", fields.level);
    setFieldValue(form, "#duration_hours", fields.duration_hours);

    var lists = draft.lists && typeof draft.lists === "object" ? draft.lists : {};
    setSimpleRows("objectives-container", "objectives", "Objectif", asArray(lists.objectives));
    setSimpleRows("prerequisites-container", "prerequisites", "Prérequis", asArray(lists.prerequisites));
    setSimpleRows("equipment-container", "equipment", "Équipement", asArray(lists.equipment));

    if (draft.content && typeof draft.content === "object" && q("#content_payload", form)) {
      q("#content_payload", form).value = JSON.stringify(draft.content);
    }
    if (draft.exams && typeof draft.exams === "object" && q("#exams_payload", form)) {
      q("#exams_payload", form).value = JSON.stringify(draft.exams);
    }

    return {
      restored: true,
      step: Math.max(1, Math.min(totalSteps, numberValue(draft.step, 1)))
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

      if (target.closest("[data-qcm-modal-open]")) {
        event.preventDefault();
        window.openQcmModal(target.closest("[data-qcm-modal-open]"));
        return;
      }

      if (target.closest("[data-qcm-modal-close]")) {
        event.preventDefault();
        window.closeQcmModal(target.closest("[data-qcm-modal-close]"));
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

    var localDraft = restoreLocalDraft(form);
    bindCourseCreateEvents(form);

    var contentPayload = parsePayload(q("#content_payload", form), { modules: [] });
    var examsPayload = parsePayload(q("#exams_payload", form), { exams: [] });

    renderModules(asArray(contentPayload.modules));
    renderExams(asArray(examsPayload.exams));
    serializeBuilders();
    syncEnterprisePricing();
    syncReferralToggleUi();
    updatePedagogyCount(localDraft.restored);
    autosaveReady = true;
    showStep(localDraft.step || 1, false);
    if (localDraft.restored) {
      showToast("Brouillon local restauré. Les fichiers doivent être re-sélectionnés avant publication.", "info");
    }

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
        currentStep = 1;
        window.goToStep(1);
        showToast("Choisissez soit un prix entreprise, soit une réduction.", "error");
      }
    });
    scheduleLocalDraftSave();
  });

})();
