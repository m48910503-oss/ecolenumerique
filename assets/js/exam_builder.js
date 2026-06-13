document.addEventListener("DOMContentLoaded", () => {
  const AUTOSAVE_MS = 25000;
  const form = document.getElementById("exam-form");
  const typeSelect = document.getElementById("exam-type");
  const builder = document.getElementById("qcm-builder");
  const questionsField = document.getElementById("questions_json");
  const settingsField = document.getElementById("settings_json");
  const btnAddQuestion = document.getElementById("btn-add-question");
  const variantAddButtons = Array.from(document.querySelectorAll("[data-variant-add-question]"));
  const pointsInput = document.getElementById("points_per_question");
  const typePanels = Array.from(document.querySelectorAll("[data-exam-type-panel]"));
  const typeChoiceButtons = Array.from(document.querySelectorAll("[data-exam-type-choice]"));
  const driveFileInput = document.querySelector('input[name="drive_subject_file"]');
  const qcmErrors = document.getElementById("qcm-errors");
  const liveValidation = document.getElementById("exam-live-validation");
  const complianceList = document.getElementById("exam-compliance-list");
  const autosaveStatus = document.getElementById("exam-autosave-status");
  const btnRestoreAutosave = document.getElementById("btn-restore-autosave");
  const btnClearAutosave = document.getElementById("btn-clear-autosave");
  const templateDataNode = document.getElementById("exam-template-data");
  const templateSelect = document.getElementById("template-exam-id");
  const btnApplyTemplate = document.getElementById("btn-apply-template");
  const csvInput = document.getElementById("question-import-csv");
  const btnImportCsv = document.getElementById("btn-import-csv");
  const helpTriggers = Array.from(document.querySelectorAll("[data-help-trigger]"));
  const formHelpModal = document.getElementById("exam-help-modal");
  const formHelpModalCloseButtons = Array.from(document.querySelectorAll("[data-help-modal-close]"));
  const formHelpModalTitle = document.getElementById("exam-help-modal-title");
  const formHelpModalSubtitle = document.getElementById("exam-help-modal-subtitle");
  const formHelpModalList = document.getElementById("exam-help-modal-list");
  const driveProgressWrap = document.getElementById("drive-upload-progress");
  const driveProgressBar = document.getElementById("drive-upload-bar");
  const driveProgressPercent = document.getElementById("drive-upload-percent");
  const driveProgressLabel = document.getElementById("drive-upload-label");
  const meetingModeSelect = document.getElementById("meeting-mode");
  const meetingOnlineFields = document.querySelectorAll(".meeting-online-only");
  const meetingOnsiteFields = document.querySelectorAll(".meeting-onsite-only");
  const guideItems = Array.from(document.querySelectorAll("[data-guide-type]"));
  if (!form || !typeSelect || !builder || !questionsField || !settingsField || !pointsInput) return;

  const variantTabs = Array.from(builder.querySelectorAll("[data-variant-tab]"));
  const variantPanels = Array.from(builder.querySelectorAll("[data-variant-panel]"));
  const containers = Array.from(builder.querySelectorAll(".questions-container[data-variant]"));
  const readOnly = builder.dataset.readonly === "1";
  const labels = { "1": "Version A", "2": "Version B", "3": "Version C" };
  const typeUi = {
    qcm: {
      label: "QCM",
      title: "QCM \u00e0 trois versions",
      copy: "Le constructeur QCM ouvre trois versions A, B et C avec import de questions et r\u00e8gles de notation.",
      meta: "Le syst\u00e8me fixera automatiquement 3 tentatives et attendra une question minimum par version.",
      attempts: 3,
    },
    drive: {
      label: "Soumission de document",
      title: "Soumission de document",
      copy: "Le formulaire demande uniquement le sujet \u00e0 partager, sans collecte de lien c\u00f4t\u00e9 enseignant.",
      meta: "Le sujet doit \u00eatre disponible avant l'enregistrement.",
      attempts: 1,
    },
    meeting: {
      label: "Rendez-vous",
      title: "Rendez-vous",
      copy: "Choisissez simplement le mode (en ligne ou pr\u00e9sentiel).",
      meta: "Le rendez-vous sera planifi\u00e9 selon le mode choisi.",
      attempts: 1,
    },
  };
  const helpContentByType = {
    qcm: {
      title: "Guide QCM",
      subtitle: "Import CSV/Excel, variantes A/B/C et contr\u00f4le de correction.",
      items: [
        "Utilisez les colonnes question, variant, type, option_a \u00e0 option_f.",
        "En choix unique, la colonne correct doit indiquer une seule bonne r\u00e9ponse (A/B/C ou 1/2/3).",
        "En choix multiple, indiquez toutes les bonnes options dans correct (ex: A|C).",
        "Chaque version A, B et C doit contenir au moins une question valide avant l'envoi.",
      ],
    },
    drive: {
      title: "Guide Soumission Drive",
      subtitle: "Sujet \u00e0 d\u00e9poser, pr\u00e9requis et comportement c\u00f4t\u00e9 apprenant.",
      items: [
        "D\u00e9posez un fichier sujet (PDF, Office, image ou archive) avant publication.",
        "Si un sujet est d\u00e9j\u00e0 li\u00e9, il est conserv\u00e9 tant que vous ne chargez pas un nouveau fichier.",
        "L'apprenant voit le sujet puis remet son document dans le flux Drive.",
        "V\u00e9rifiez que la connexion Drive est active avant l'enregistrement final.",
      ],
    },
    meeting: {
      title: "Guide Rendez-vous oral",
      subtitle: "Mode en ligne/pr\u00e9sentiel et confirmation du cr\u00e9neau.",
      items: [
        "S\u00e9lectionnez le mode du rendez-vous : en ligne ou pr\u00e9sentiel.",
        "L'apprenant proposera un cr\u00e9neau \u00e0 partir de ce mode.",
        "Vous confirmerez ensuite le cr\u00e9neau et renseignerez le lien ou le lieu.",
        "Le rendez-vous est valide apr\u00e8s confirmation du cr\u00e9neau.",
      ],
    },
  };
  const autosaveKey = `ecole:exam_create:${form.getAttribute("data-course-id") || "0"}`;
  const templates = (() => {
    if (!templateDataNode) return [];
    try {
      const raw = templateDataNode.getAttribute("data-templates") || "[]";
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch (_) {
      return [];
    }
  })();

  let activeVariant = variantTabs[0]?.getAttribute("data-variant-tab") || "1";
  let isSubmittingDriveXhr = false;
  const escName = (value) => (window.CSS && typeof window.CSS.escape === "function")
    ? window.CSS.escape(String(value))
    : String(value).replace(/["\\]/g, "\\$&");
  const setText = (id, text) => {
    const node = document.getElementById(id);
    if (node) node.textContent = text;
  };
  const setVisibility = (node, visible) => {
    if (!node) return;
    node.classList.toggle("hidden", !visible);
    node.toggleAttribute("hidden", !visible);
  };
  let lastFocusedBeforeHelpModal = null;
  const isHelpModalOpen = () => !!formHelpModal && !formHelpModal.classList.contains("hidden");
  const renderHelpModal = (typeKey) => {
    if (!formHelpModalTitle || !formHelpModalSubtitle || !formHelpModalList) return;
    const fallbackType = typeSelect?.value || "qcm";
    const key = helpContentByType[typeKey] ? typeKey : (helpContentByType[fallbackType] ? fallbackType : "qcm");
    const content = helpContentByType[key];
    formHelpModalTitle.textContent = content.title;
    formHelpModalSubtitle.textContent = content.subtitle;
    formHelpModalList.innerHTML = content.items.map((item) => `<li>${item}</li>`).join("");
  };
  const closeHelpModal = (restoreFocus = true) => {
    if (!formHelpModal) return;
    formHelpModal.classList.add("hidden");
    formHelpModal.setAttribute("hidden", "");
    formHelpModal.setAttribute("aria-hidden", "true");
    helpTriggers.forEach((trigger) => trigger.setAttribute("aria-expanded", "false"));
    document.body.classList.remove("overflow-hidden");
    if (restoreFocus && lastFocusedBeforeHelpModal && typeof lastFocusedBeforeHelpModal.focus === "function") {
      lastFocusedBeforeHelpModal.focus();
    }
  };
  const openHelpModal = (typeKey) => {
    if (!formHelpModal) return;
    renderHelpModal(typeKey);
    lastFocusedBeforeHelpModal = document.activeElement;
    formHelpModal.classList.remove("hidden");
    formHelpModal.removeAttribute("hidden");
    formHelpModal.setAttribute("aria-hidden", "false");
    helpTriggers.forEach((trigger) => {
      const triggerType = trigger.getAttribute("data-help-type") || "";
      trigger.setAttribute("aria-expanded", triggerType === typeKey ? "true" : "false");
    });
    document.body.classList.add("overflow-hidden");
    const closeBtn = formHelpModal.querySelector("[data-help-modal-close]");
    if (closeBtn && typeof closeBtn.focus === "function") closeBtn.focus();
  };

  function currentTypeUi() {
    return typeUi[typeSelect.value] || typeUi.qcm;
  }

  function setAutoStatus(message, type = "info") {
    if (!autosaveStatus) return;
    autosaveStatus.textContent = message;
    autosaveStatus.classList.remove("exam-create-banner__status--ok", "exam-create-banner__status--error");
    if (type === "ok") autosaveStatus.classList.add("exam-create-banner__status--ok");
    if (type === "error") autosaveStatus.classList.add("exam-create-banner__status--error");
  }

  function collectSettings() {
    const checked = (id) => !!document.getElementById(id)?.checked;
    const value = (id) => (document.getElementById(id)?.value || "").trim();
    return {
      randomization: {
        question_shuffle: checked("rand_question_shuffle"),
        option_shuffle: checked("rand_option_shuffle"),
        variant_strategy: value("rand_variant_strategy") || "sequential",
      },
      scoring: {
        multiple_partial_credit: checked("score_partial_credit"),
        wrong_penalty: Math.max(0, Math.min(10, parseFloat(value("score_wrong_penalty") || "0") || 0)),
      },
      availability: {
        enabled: checked("availability_enabled"),
        start_at: value("availability_start_at"),
        end_at: value("availability_end_at"),
      },
      anti_cheat: {
        fullscreen_required: checked("anti_fullscreen"),
        copy_paste_block: checked("anti_copy_paste"),
        tab_switch_warning: checked("anti_tab_switch"),
        camera_required: checked("anti_camera"),
      },
    };
  }

  function applySettings(settings) {
    if (!settings || typeof settings !== "object") return;
    const setChecked = (id, v) => {
      const el = document.getElementById(id);
      if (el) el.checked = !!v;
    };
    const setValue = (id, v) => {
      const el = document.getElementById(id);
      if (el) el.value = v == null ? "" : String(v);
    };
    const rnd = settings.randomization || {};
    const scoring = settings.scoring || {};
    const availability = settings.availability || {};
    const anti = settings.anti_cheat || {};
    setChecked("rand_question_shuffle", rnd.question_shuffle);
    setChecked("rand_option_shuffle", rnd.option_shuffle);
    setValue("rand_variant_strategy", rnd.variant_strategy || "sequential");
    setChecked("score_partial_credit", scoring.multiple_partial_credit);
    setValue("score_wrong_penalty", scoring.wrong_penalty ?? 0);
    setChecked("availability_enabled", availability.enabled);
    setValue("availability_start_at", availability.start_at || "");
    setValue("availability_end_at", availability.end_at || "");
    setChecked("anti_fullscreen", anti.fullscreen_required);
    setChecked("anti_copy_paste", anti.copy_paste_block);
    setChecked("anti_tab_switch", anti.tab_switch_warning);
    setChecked("anti_camera", anti.camera_required);
  }

  function setActiveVariant(variant) {
    activeVariant = String(variant || "1");
    variantTabs.forEach((tab) => {
      const isActive = tab.getAttribute("data-variant-tab") === activeVariant;
      tab.classList.toggle("is-active", isActive);
      tab.classList.toggle("border-emerald-400", isActive);
      tab.classList.toggle("text-emerald-200", isActive);
      tab.classList.toggle("border-slate-700", !isActive);
      tab.classList.toggle("text-slate-200", !isActive);
    });
    variantPanels.forEach((panel) => {
      setVisibility(panel, panel.getAttribute("data-variant-panel") === activeVariant);
    });
  }

  function showQcmError(msg) {
    if (!qcmErrors) return;
    qcmErrors.textContent = msg;
    qcmErrors.classList.remove("hidden");
  }

  function clearQcmError() {
    if (!qcmErrors) return;
    qcmErrors.textContent = "";
    qcmErrors.classList.add("hidden");
  }

  function showLiveErrors(errors) {
    if (!liveValidation) return;
    if (!errors.length) {
      liveValidation.innerHTML = "";
      liveValidation.classList.add("hidden");
      return;
    }
    const listHtml = errors
      .map((error) => {
        let tone = "";

        if (error.startsWith("Soumission :")) {
          tone = " exam-create-banner__item--drive";
        } else if (error.startsWith("Rendez-vous :")) {
          tone = " exam-create-banner__item--meeting";
        } else if (error.startsWith("Version ")) {
          tone = " exam-create-banner__item--qcm";
        }

        return `<li class="exam-create-banner__item${tone}">${error}</li>`;
      })
      .join("");
    liveValidation.innerHTML = `
      <div class="exam-create-banner__copy">
        <span class="exam-create-banner__icon app-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 9v4"></path>
            <path d="M12 17h.01"></path>
            <path d="M10.29 3.86l-7.4 12.8a1 1 0 0 0 .86 1.5h16.5a1 1 0 0 0 .86-1.5l-7.4-12.8a1 1 0 0 0-1.72 0z"></path>
          </svg>
        </span>
        <div>
          <p class="exam-create-banner__title">V\u00e9rifications \u00e0 corriger</p>
          <ul class="exam-create-banner__list">${listHtml}</ul>
        </div>
      </div>
    `;
    liveValidation.classList.remove("hidden");
  }

  function makeOptionRow(opt = {}) {
    const row = document.createElement("div");
    row.className = "flex items-center gap-2 q-option";
    row.innerHTML = `
      <span class="opt-letter text-xs px-2 py-1 rounded bg-slate-800 text-slate-200">A</span>
      <input class="o-text flex-1 bg-slate-950 border border-slate-800 rounded px-3 py-2 text-sm" placeholder="Option">
      <label class="text-xs text-slate-300 flex items-center gap-1">
        <input type="checkbox" class="o-correct accent-emerald-500"> Correct
      </label>
      <button type="button" class="o-remove text-xs px-2 py-1 rounded border border-slate-700 text-slate-200 hover:border-rose-400">X</button>
    `;
    const textInput = row.querySelector(".o-text");
    if (textInput) textInput.value = opt.text || "";
    const correct = row.querySelector(".o-correct");
    if (correct) correct.checked = !!opt.correct;
    return row;
  }

  function applyQuestionData(block, data = {}) {
    const qText = block.querySelector(".q-text");
    if (qText) qText.value = data.text || "";
    const qType = block.querySelector(".q-type");
    if (qType) qType.value = data.type || "single";
    const optionsWrapper = block.querySelector(".options");
    if (optionsWrapper) {
      optionsWrapper.innerHTML = "";
      const opts = Array.isArray(data.options) && data.options.length ? data.options : [{ text: "", correct: false }];
      opts.forEach((opt) => optionsWrapper.appendChild(makeOptionRow(opt)));
    }
    const addBtn = block.querySelector(".add-option");
    if (qType && qType.value === "text") {
      optionsWrapper?.classList.add("hidden");
      addBtn?.classList.add("hidden");
    } else {
      optionsWrapper?.classList.remove("hidden");
      addBtn?.classList.remove("hidden");
    }
  }

  function renumber(container) {
    const blocks = Array.from(container.querySelectorAll(".q-block"));
    blocks.forEach((blk, idx) => {
      blk.dataset.qIndex = String(idx + 1);
      const n = blk.querySelector(".q-number");
      if (n) n.textContent = `Q${idx + 1}`;
      const opts = Array.from(blk.querySelectorAll(".q-option"));
      opts.forEach((opt, oIdx) => {
        const span = opt.querySelector(".opt-letter");
        if (span) span.textContent = String.fromCharCode(65 + oIdx);
      });
    });
  }

  function addQuestion(container, data = {}, prepend = false) {
    const block = document.createElement("div");
    block.className = "q-block border border-slate-800 rounded-lg p-3 bg-slate-900";
    block.innerHTML = `
      <div class="flex items-center gap-2 mb-2">
        <span class="q-number text-xs px-2 py-1 rounded bg-slate-800 text-slate-200">Q1</span>
        <input class="q-text flex-1 bg-slate-950 border border-slate-800 rounded px-3 py-2 text-sm" placeholder="Intitul\u00e9 de la question">
        <select class="q-type bg-slate-950 border border-slate-800 rounded px-2 py-2 text-sm">
          <option value="single">Choix unique</option>
          <option value="multiple">Choix multiple</option>
          <option value="text">R\u00e9ponse texte</option>
        </select>
        <button type="button" class="q-remove text-xs px-2 py-1 rounded border border-rose-400 text-rose-200 hover:bg-rose-500/10">Supprimer</button>
      </div>
      <div class="options space-y-2"></div>
      <button type="button" class="add-option mt-2 text-xs px-3 py-1 rounded border border-emerald-400 text-emerald-200 hover:bg-emerald-500/10">+ Option</button>
    `;
    if (prepend && container.firstChild) container.insertBefore(block, container.firstChild);
    else container.appendChild(block);
    applyQuestionData(block, data);
    renumber(container);
  }

  function serializeQuestions() {
    const payload = { variants: {} };
    const globalPoints = Math.max(1, parseInt(pointsInput.value || "1", 10) || 1);
    containers.forEach((container) => {
      const variant = container.getAttribute("data-variant") || "1";
      payload.variants[String(variant)] = Array.from(container.querySelectorAll(".q-block")).map((blk) => {
        return {
          text: blk.querySelector(".q-text")?.value || "",
          type: blk.querySelector(".q-type")?.value || "single",
          points: globalPoints,
          options: Array.from(blk.querySelectorAll(".q-option")).map((opt) => ({
            text: opt.querySelector(".o-text")?.value || "",
            correct: !!opt.querySelector(".o-correct")?.checked,
          })),
        };
      });
    });
    questionsField.value = JSON.stringify(payload);
    return payload;
  }

  function clearQuestions() {
    containers.forEach((container) => {
      container.innerHTML = "";
    });
  }

  function applyQuestionsPayload(payload) {
    const variants = payload?.variants || {};
    clearQuestions();
    containers.forEach((container) => {
      const variant = container.getAttribute("data-variant") || "1";
      const list = Array.isArray(variants[variant]) ? variants[variant] : [];
      if (list.length) list.forEach((q) => addQuestion(container, q));
      else addQuestion(container, {});
      renumber(container);
    });
  }

  function bindContainer(container) {
    container.addEventListener("click", (e) => {
      if (readOnly) return;
      const addOpt = e.target.closest(".add-option");
      if (addOpt) {
        addOpt.previousElementSibling?.appendChild(makeOptionRow());
        renumber(container);
      }
      const remOpt = e.target.closest(".o-remove");
      if (remOpt) {
        remOpt.closest(".q-option")?.remove();
        renumber(container);
      }
      const remQ = e.target.closest(".q-remove");
      if (remQ) {
        remQ.closest(".q-block")?.remove();
        renumber(container);
      }
      onDirty();
    });

    container.addEventListener("change", (e) => {
      if (readOnly) return;
      const select = e.target.closest(".q-type");
      if (!select) return;
      const blk = select.closest(".q-block");
      if (!blk) return;
      const opts = blk.querySelector(".options");
      const addBtn = blk.querySelector(".add-option");
      if (select.value === "text") {
        opts?.classList.add("hidden");
        addBtn?.classList.add("hidden");
      } else {
        opts?.classList.remove("hidden");
        addBtn?.classList.remove("hidden");
      }
      onDirty();
    });
  }

  function validateQcmPayload(payload) {
    const variants = payload.variants || {};
    for (const v of ["1", "2", "3"]) {
      const list = Array.isArray(variants[v]) ? variants[v] : [];
      if (!list.length) return `${labels[v]} : ajoutez au moins une question.`;
      for (let i = 0; i < list.length; i++) {
        const q = list[i];
        if (!q.text || !q.text.trim()) return `${labels[v]} - Question ${i + 1} : intitul\u00e9 requis.`;
        if (q.type === "single" || q.type === "multiple") {
          const filled = (q.options || []).filter((o) => (o.text || "").trim() !== "");
          if (filled.length < 2) return `${labels[v]} - Question ${i + 1} : au moins 2 options.`;
          const correct = filled.filter((o) => !!o.correct).length;
          if (!correct) return `${labels[v]} - Question ${i + 1} : au moins 1 option correcte.`;
          if (q.type === "single" && correct > 1) return `${labels[v]} - Question ${i + 1} : une seule option correcte en choix unique.`;
        }
      }
    }
    return "";
  }

  function collectValidationErrors(payload) {
    const errors = [];
    const title = (form.querySelector('input[name="title"]')?.value || "").trim();
    if (!title) errors.push("Titre obligatoire.");
    const type = typeSelect.value;
    if (type === "meeting") {
      const mode = form.querySelector('select[name="meeting_mode"]')?.value || "online";
      if (!mode) errors.push("Rendez-vous : mode requis.");
    }
    if (type === "drive") {
      const mustFile = driveFileInput?.dataset.requiredOnDrive === "1";
      if (mustFile && driveFileInput && !driveFileInput.files?.length) errors.push("Soumission : fichier du sujet obligatoire.");
    }
    if (type === "qcm") {
      const qcmErr = validateQcmPayload(payload);
      if (qcmErr) errors.push(qcmErr);
      if ((parseInt(pointsInput.value || "1", 10) || 1) < 1) errors.push("Points par question : minimum 1.");
    }
    return errors;
  }

  function updateCompliance(payload) {
    if (!complianceList) return;
    const type = typeSelect.value;
    const typeLabel = currentTypeUi().label;
    const title = (form.querySelector('input[name="title"]')?.value || "").trim();
    const items = [];
    items.push({
      ok: !!title,
      text: title ? "Titre renseign\u{00e9}" : "Titre obligatoire",
    });
    items.push({
      ok: !!type,
      text: type ? `Type : ${typeLabel}` : "Type d'examen obligatoire",
    });
    if (type === "qcm") {
      const qcmOk = !validateQcmPayload(payload);
      items.push({
        ok: qcmOk,
        text: qcmOk ? "3 versions QCM valides" : "3 versions QCM requises",
      });
    }
    if (type === "drive") {
      const hasCurrent = driveFileInput?.dataset.requiredOnDrive !== "1";
      const hasNew = !!driveFileInput?.files?.length;
      const driveOk = hasCurrent || hasNew;
      items.push({
        ok: driveOk,
        text: driveOk ? "Sujet pr\u{00e9}sent" : "Sujet obligatoire",
      });
    }
    if (type === "meeting") {
      const mode = form.querySelector('select[name="meeting_mode"]')?.value || "online";
      items.push({
        ok: !!mode,
        text: "Mode de rendez-vous choisi",
      });
    }
    const antiReady = !!(
      document.getElementById("anti_fullscreen")?.checked
      || document.getElementById("anti_copy_paste")?.checked
      || document.getElementById("anti_tab_switch")?.checked
      || document.getElementById("anti_camera")?.checked
    );
    items.push({
      ok: true,
      text: antiReady ? "Anti-triche : r\u{00e8}gles activ\u{00e9}es" : "Anti-triche : aucune r\u{00e8}gle activ\u{00e9}e",
    });

    complianceList.innerHTML = "";
    items.forEach((item) => {
      const li = document.createElement("li");
      li.className = `exam-create-compliance-item${item.ok ? " exam-create-compliance-item--ok" : ""}`;
      li.innerHTML = `
        <span class="exam-create-compliance-item__badge">${item.ok ? "OK" : "\u00c0 revoir"}</span>
        <span class="exam-create-compliance-item__text">${item.text}</span>
      `;
      complianceList.appendChild(li);
    });

    const blockerMeta = document.getElementById("exam-blocker-meta");
    if (blockerMeta) {
      const issues = items.filter((item) => !item.ok).length;
      blockerMeta.textContent = issues ? `${issues} \u00e0 corriger` : "Tout est bon";
    }
  }

  function updatePreview(payload) {
    const title = (form.querySelector('input[name="title"]')?.value || "").trim();
    const desc = (form.querySelector('textarea[name="description"]')?.value || "").trim();
    const duration = (form.querySelector('input[name="duration_min"]')?.value || "").trim();
    const minScore = (form.querySelector('input[name="min_score"]')?.value || "0").trim();
    const pTitle = document.getElementById("preview-exam-title");
    const pMeta = document.getElementById("preview-exam-meta");
    const pDesc = document.getElementById("preview-exam-description");
    const pSample = document.getElementById("preview-exam-sample");
    if (pTitle) pTitle.textContent = title || "Titre de l'examen";
    if (pMeta) {
      const durationMin = Number(duration || 0);
      let durationLabel = "0 min";
      if (Number.isFinite(durationMin) && durationMin > 0) {
        const totalMin = Math.round(durationMin);
        const h = Math.floor(totalMin / 60);
        const m = totalMin % 60;
        if (h > 0) {
          const mm = m < 10 ? `0${m}` : String(m);
          durationLabel = `${h} h ${mm} min`;
        } else {
          durationLabel = `${totalMin} min`;
        }
      }
      pMeta.textContent = `${currentTypeUi().label} | ${durationLabel} | Note minimale ${minScore || "0"}`;
    }
    if (pDesc) pDesc.textContent = desc || "La description appara\u00eetra ici d\u00e8s qu'elle sera renseign\u00e9e.";
    if (pSample) {
      if (typeSelect.value === "qcm") {
        const list = payload.variants?.[activeVariant] || [];
        const count = list.length;
        const unitLabel = count > 1 ? "questions" : "question";
        pSample.textContent = list.length
          ? `Aper\u00e7u ${labels[activeVariant]} : ${list[0].text || "Question 1"} - ${count} ${unitLabel}.`
          : `Ajoutez des questions dans ${labels[activeVariant]}.`;
      } else if (typeSelect.value === "drive") {
        pSample.textContent = "L'apprenant verra le sujet puis remettra son document.";
      } else {
        pSample.textContent = "L'apprenant demandera un rendez-vous selon le mode choisi.";
      }
    }
  }

  function updateTypeChoiceUi() {
    const ui = currentTypeUi();
    const attempts = String(ui.attempts || 1);
    typeChoiceButtons.forEach((button) => {
      const buttonType = button.getAttribute("data-exam-type-choice") || "";
      const active = buttonType === typeSelect.value;
      button.classList.toggle("is-active", active);
      button.setAttribute("aria-pressed", active ? "true" : "false");
    });
    setText("exam-type-hero-pill", ui.label);
    setText("exam-type-mode-pill", ui.label);
    setText("exam-reference-type", ui.label);
    setText("exam-reference-attempts", `Tentatives autoris\u00e9es : ${attempts}`);
    setText("summary-attempts-stat", `Tentatives : ${attempts}`);
  }

  function updateTypeMetrics(payload) {
    const type = typeSelect.value;
    let heroValue = "";
    let heroMeta = "";
    let summaryCount = "";
    let summaryMeta = "";
    let isReady = false;

    if (type === "qcm") {
      const variants = payload?.variants || {};
      const counts = ["1", "2", "3"].map((variant) => Array.isArray(variants[variant]) ? variants[variant].length : 0);
      const readyVariants = counts.filter((count) => count > 0).length;
      const total = counts.reduce((sum, count) => sum + count, 0);
      isReady = readyVariants === 3;
      heroValue = `${readyVariants}/3 versions`;
      heroMeta = `${total} question(s) r\u00e9partie(s) sur les trois versions`;
      summaryCount = String(readyVariants);
      summaryMeta = `${total} question(s) sur l'ensemble des versions.`;
    } else if (type === "drive") {
      const hasCurrent = driveFileInput?.dataset.requiredOnDrive !== "1";
      const hasNew = !!driveFileInput?.files?.length;
      isReady = hasCurrent || hasNew;
      heroValue = isReady ? "Sujet pr\u00eat" : "Sujet manquant";
      heroMeta = "Sujet d\u00e9pos\u00e9 pour la soumission";
      summaryCount = isReady ? "OK" : "\u00c0 revoir";
      summaryMeta = isReady ? "Sujet associ\u00e9 au flux Drive." : "Le sujet Drive est encore manquant.";
    } else {
      const mode = meetingModeSelect?.value || "online";
      isReady = !!mode;
      heroValue = mode === "online" ? "En ligne" : "Pr\u00e9sentiel";
      heroMeta = "Mode de rendez-vous";
      summaryCount = "OK";
      summaryMeta = "Mode s\u00e9lectionn\u00e9.";
    }

    setText("hero-structure-value", heroValue);
    setText("hero-structure-meta", heroMeta);
    setText("summary-structure-count", summaryCount);
    setText("summary-structure-meta", summaryMeta);
    const summaryItem = document.getElementById("summary-structure-count")?.closest(".exam-create-summary-item");
    if (summaryItem) summaryItem.classList.toggle("exam-create-summary-item--done", isReady);
  }

  function toggleMeetingModeFields() {
    const mode = meetingModeSelect?.value || "online";
    meetingOnlineFields.forEach((el) => setVisibility(el, mode === "online"));
    meetingOnsiteFields.forEach((el) => setVisibility(el, mode === "onsite"));
  }

  function toggleTypeGuide() {
    if (!guideItems.length) return;
    const t = typeSelect.value;
    guideItems.forEach((item) => {
      setVisibility(item, (item.getAttribute("data-guide-type") || "") === t);
    });
  }

  function toggleSections() {
    const t = typeSelect.value;
    typePanels.forEach((panel) => {
      setVisibility(panel, panel.getAttribute("data-exam-type-panel") === t);
    });
    document.querySelectorAll(".qcm-advanced-only").forEach((el) => setVisibility(el, t === "qcm"));
    if (t !== "qcm") clearQcmError();
    const maxAttemptsInput = form.querySelector('input[name="max_attempts"]');
    if (maxAttemptsInput) {
      const attemptsValue = t === "qcm" ? currentTypeUi().attempts || 1 : 1;
      maxAttemptsInput.value = String(attemptsValue);
    }
    if (driveFileInput) {
      const mustRequire = driveFileInput.dataset.requiredOnDrive === "1";
      if (t === "drive" && mustRequire) driveFileInput.setAttribute("required", "required");
      else driveFileInput.removeAttribute("required");
    }
    if (driveProgressWrap && t !== "drive") setVisibility(driveProgressWrap, false);
    toggleMeetingModeFields();
    toggleTypeGuide();
    updateTypeChoiceUi();
  }

  function makeSnapshot() {
    const fields = {};
    form.querySelectorAll("input, select, textarea").forEach((el) => {
      if (!el.name || el.type === "file" || el.name === "_csrf" || el.name === "questions_json" || el.name === "settings_json") return;
      if (el.type === "checkbox") fields[el.name] = !!el.checked;
      else fields[el.name] = el.value;
    });
    return { fields, questions: serializeQuestions(), settings: collectSettings(), activeVariant, savedAt: Date.now() };
  }

  function applySnapshot(snapshot) {
    if (!snapshot || typeof snapshot !== "object") return;
    const fields = snapshot.fields || {};
    Object.keys(fields).forEach((name) => {
      const el = form.querySelector(`[name="${escName(name)}"]`);
      if (!el) return;
      if (el.type === "checkbox") el.checked = !!fields[name];
      else el.value = fields[name];
    });
    if (snapshot.questions) applyQuestionsPayload(snapshot.questions);
    if (snapshot.settings) applySettings(snapshot.settings);
    if (snapshot.activeVariant) setActiveVariant(snapshot.activeVariant);
    toggleSections();
    onDirty();
  }

  function saveSnapshot() {
    try {
      localStorage.setItem(autosaveKey, JSON.stringify(makeSnapshot()));
      setAutoStatus(`Brouillon local sauvegard\u00e9 (${new Date().toLocaleTimeString()}).`, "ok");
    } catch (_) {
      setAutoStatus("\u00c9chec de la sauvegarde locale.", "error");
    }
  }

  function restoreSnapshot() {
    try {
      const raw = localStorage.getItem(autosaveKey);
      if (!raw) {
        setAutoStatus("Aucun brouillon local.");
        return;
      }
      applySnapshot(JSON.parse(raw));
      setAutoStatus("Brouillon restaur\u00e9.", "ok");
    } catch (_) {
      setAutoStatus("Brouillon local invalide.", "error");
    }
  }

  function clearSnapshot() {
    localStorage.removeItem(autosaveKey);
    setAutoStatus("Brouillon local supprim\u00e9.");
  }

  function applyTemplateById(id) {
    const tpl = templates.find((t) => String(t.id) === String(id));
    if (!tpl) return;
    if (!window.confirm("Remplacer le formulaire actuel par ce mod\u00e8le ?")) return;
    const byName = (name) => form.querySelector(`[name="${escName(name)}"]`);
    if (byName("title")) byName("title").value = tpl.title || "";
    if (byName("description")) byName("description").value = tpl.description || "";
    if (byName("type")) byName("type").value = tpl.type || "qcm";
    if (byName("duration_min")) byName("duration_min").value = tpl.duration_min || "";
    if (byName("max_attempts")) byName("max_attempts").value = tpl.max_attempts || "1";
    if (byName("min_score")) byName("min_score").value = tpl.min_score || "0";
    if (byName("meeting_mode")) byName("meeting_mode").value = tpl.meeting_mode || "online";
    if (byName("meeting_link")) byName("meeting_link").value = tpl.meeting_link || "";
    if (byName("meeting_place")) byName("meeting_place").value = tpl.meeting_place || "";
    pointsInput.value = String(tpl.points_per_question || 1);
    applyQuestionsPayload({ variants: tpl.questions_by_variant || {} });
    applySettings(tpl.settings || {});
    toggleSections();
    onDirty();
    setAutoStatus("Mod\u00e8le appliqu\u00e9.", "ok");
  }

  function splitCsvLine(line, delimiter) {
    const result = [];
    let curr = "";
    let inQuotes = false;
    for (let i = 0; i < line.length; i++) {
      const c = line[i];
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

  function importCsvText(text) {
    if (!text || !text.trim()) return 0;
    const lines = text.replace(/\r/g, "").split("\n").filter((l) => l.trim() !== "");
    if (!lines.length) return 0;
    const delimiter = lines[0].includes(";") ? ";" : ",";
    const headers = splitCsvLine(lines[0], delimiter).map((h) => h.toLowerCase());
    const idx = (name) => headers.indexOf(name);
    const hQuestion = idx("question");
    if (hQuestion < 0) return 0;
    const hVariant = idx("variant");
    const hType = idx("type");
    const hCorrect = idx("correct");
    const optionIndexes = ["option_a", "option_b", "option_c", "option_d", "option_e", "option_f"].map(idx).filter((v) => v >= 0);
    const defaultTargets = ["1", "2", "3"];
    const variantMap = { a: "1", b: "2", c: "3" };
    let imported = 0;
    for (let i = 1; i < lines.length; i++) {
      const cols = splitCsvLine(lines[i], delimiter);
      const qText = (cols[hQuestion] || "").trim();
      if (!qText) continue;
      const typeRaw = (hType >= 0 ? cols[hType] : "single") || "single";
      const qType = ["single", "multiple", "text"].includes(typeRaw.toLowerCase()) ? typeRaw.toLowerCase() : "single";
      const variantRaw = (hVariant >= 0 ? (cols[hVariant] || "") : "").trim();
      const normalizedVariant = variantRaw.toLowerCase();
      let targetVariants = defaultTargets;
      if (["1", "2", "3"].includes(variantRaw)) {
        targetVariants = [variantRaw];
      } else if (variantMap[normalizedVariant]) {
        targetVariants = [variantMap[normalizedVariant]];
      } else if (
        normalizedVariant === "all"
        || normalizedVariant === "a+b+c"
        || normalizedVariant === "a,b,c"
        || normalizedVariant === "a|b|c"
        || normalizedVariant === "abc"
        || normalizedVariant === "123"
      ) {
        targetVariants = ["1", "2", "3"];
      }
      const options = [];
      optionIndexes.forEach((optIdx) => {
        const t = (cols[optIdx] || "").trim();
        if (t) options.push({ text: t, correct: false });
      });
      if (qType !== "text" && options.length < 2) continue;
      const correctRaw = (hCorrect >= 0 ? cols[hCorrect] : "").trim();
      if (correctRaw && qType !== "text") {
        const parts = correctRaw.toUpperCase().split(/[|,; ]+/).filter(Boolean);
        parts.forEach((token) => {
          const numeric = parseInt(token, 10);
          let idxOpt = -1;
          if (Number.isFinite(numeric)) idxOpt = numeric - 1;
          else if (/^[A-Z]$/.test(token)) idxOpt = token.charCodeAt(0) - 65;
          if (idxOpt >= 0 && idxOpt < options.length) options[idxOpt].correct = true;
        });
      }
      if (qType !== "text" && !options.some((o) => o.correct)) options[0].correct = true;
      targetVariants.forEach((variant) => {
        const container = containers.find((c) => c.getAttribute("data-variant") === variant);
        if (!container) return;
        addQuestion(container, { text: qText, type: qType, options });
        imported += 1;
      });
    }
    return imported;
  }

  async function readImportText(file) {
    if (!file) return "";
    const fileName = String(file.name || "").toLowerCase();
    const isExcel = fileName.endsWith(".xlsx") || fileName.endsWith(".xls");
    if (!isExcel) {
      return file.text();
    }
    if (typeof window.XLSX === "undefined" || !window.XLSX?.utils?.sheet_to_csv) {
      throw new Error("excel_lib_missing");
    }
    const buffer = await file.arrayBuffer();
    const workbook = window.XLSX.read(buffer, { type: "array" });
    const firstSheet = workbook?.SheetNames?.[0];
    if (!firstSheet || !workbook.Sheets?.[firstSheet]) {
      return "";
    }
    return window.XLSX.utils.sheet_to_csv(workbook.Sheets[firstSheet], {
      FS: ";",
      RS: "\n",
      blankrows: false,
    });
  }

  function submitDriveViaXhr() {
    if (isSubmittingDriveXhr || !driveFileInput?.files?.length || !window.XMLHttpRequest || !window.FormData) return;
    const xhr = new XMLHttpRequest();
    const fd = new FormData(form);
    isSubmittingDriveXhr = true;
    if (driveProgressWrap) setVisibility(driveProgressWrap, true);
    if (driveProgressLabel) driveProgressLabel.textContent = "T\u00e9l\u00e9versement du sujet en cours...";
    if (driveProgressBar) driveProgressBar.style.width = "0%";
    if (driveProgressPercent) driveProgressPercent.textContent = "0%";
    xhr.upload.addEventListener("progress", (ev) => {
      if (!ev.lengthComputable) return;
      const pct = Math.max(0, Math.min(100, Math.round((ev.loaded / ev.total) * 100)));
      if (driveProgressBar) driveProgressBar.style.width = `${pct}%`;
      if (driveProgressPercent) driveProgressPercent.textContent = `${pct}%`;
    });
    xhr.addEventListener("load", () => {
      isSubmittingDriveXhr = false;
      if (xhr.status >= 200 && xhr.status < 300) {
        if (xhr.responseURL && xhr.responseURL !== window.location.href) {
          window.location.assign(xhr.responseURL);
          return;
        }
        document.open();
        document.write(xhr.responseText || "");
        document.close();
        return;
      }
      if (driveProgressLabel) driveProgressLabel.textContent = "\u00c9chec de l'envoi. R\u00e9essayez.";
      showLiveErrors(["T\u00e9l\u00e9versement Drive impossible."]);
    });
    xhr.addEventListener("error", () => {
      isSubmittingDriveXhr = false;
      if (driveProgressLabel) driveProgressLabel.textContent = "Erreur r\u00e9seau pendant l'envoi.";
      showLiveErrors(["Erreur r\u00e9seau pendant l'envoi vers Drive."]);
    });
    xhr.open("POST", form.action || window.location.href, true);
    xhr.send(fd);
  }

  function onDirty() {
    const payload = serializeQuestions();
    settingsField.value = JSON.stringify(collectSettings());
    clearQcmError();
    showLiveErrors(collectValidationErrors(payload));
    updateCompliance(payload);
    updatePreview(payload);
    updateTypeMetrics(payload);
  }

  const addQuestionToVariant = (variant) => {
    const nextVariant = String(variant || activeVariant || "1");
    const container = containers.find((c) => c.getAttribute("data-variant") === nextVariant) || containers[0];
    if (!container) return;
    setActiveVariant(nextVariant);
    addQuestion(container, {}, true);
    onDirty();
  };

  if (btnAddQuestion) {
    btnAddQuestion.addEventListener("click", () => addQuestionToVariant(activeVariant));
  }
  variantAddButtons.forEach((button) => {
    button.addEventListener("click", () => {
      if (button.disabled) return;
      const targetVariant = button.getAttribute("data-variant-add-question") || activeVariant;
      addQuestionToVariant(targetVariant);
    });
  });
  variantTabs.forEach((tab) => tab.addEventListener("click", () => setActiveVariant(tab.getAttribute("data-variant-tab"))));
  typeChoiceButtons.forEach((button) => {
    button.addEventListener("click", () => {
      if (button.disabled) return;
      const nextType = button.getAttribute("data-exam-type-choice") || "qcm";
      if (typeSelect.value === nextType) return;
      typeSelect.value = nextType;
      typeSelect.dispatchEvent(new Event("change"));
    });
  });
  typeSelect.addEventListener("change", () => {
    if (typeSelect.dataset.locked === "1") {
      const fixedValue = typeSelect.dataset.fixedValue || "qcm";
      if (typeSelect.value !== fixedValue) {
        typeSelect.value = fixedValue;
      }
    }
    toggleSections();
    onDirty();
  });
  if (meetingModeSelect) {
    meetingModeSelect.addEventListener("change", () => {
      toggleMeetingModeFields();
      onDirty();
    });
  }
  form.addEventListener("input", onDirty);
  form.addEventListener("change", onDirty);
  form.addEventListener("submit", (e) => {
    const payload = serializeQuestions();
    settingsField.value = JSON.stringify(collectSettings());
    const errors = collectValidationErrors(payload);
    showLiveErrors(errors);
    if (errors.length) {
      if (typeSelect.value === "qcm") showQcmError(errors[0]);
      e.preventDefault();
      return;
    }
    clearQcmError();
    saveSnapshot();
    if (typeSelect.value === "drive" && driveFileInput?.files?.length) {
      e.preventDefault();
      submitDriveViaXhr();
    }
  });

  if (btnRestoreAutosave) btnRestoreAutosave.addEventListener("click", restoreSnapshot);
  if (btnClearAutosave) btnClearAutosave.addEventListener("click", clearSnapshot);
  if (helpTriggers.length && formHelpModal) {
    helpTriggers.forEach((trigger) => {
      trigger.addEventListener("click", () => {
        const helpType = trigger.getAttribute("data-help-type") || typeSelect.value || "qcm";
        openHelpModal(helpType);
      });
    });
    formHelpModalCloseButtons.forEach((button) => {
      button.addEventListener("click", () => closeHelpModal());
    });
    document.addEventListener("keydown", (event) => {
      if (event.key !== "Escape" || !isHelpModalOpen()) return;
      event.preventDefault();
      closeHelpModal();
    });
  }
  if (btnApplyTemplate) btnApplyTemplate.addEventListener("click", () => applyTemplateById(templateSelect?.value || ""));
  if (btnImportCsv) {
    btnImportCsv.addEventListener("click", async () => {
      const file = csvInput?.files?.[0];
      if (!file) {
        setAutoStatus("Choisissez un fichier CSV/Excel \u{00e0} importer.");
        return;
      }
      try {
        const text = await readImportText(file);
        const count = importCsvText(text);
        if (count > 0) {
          setAutoStatus(`${count} question(s) import\u{00e9}e(s).`, "ok");
          onDirty();
        } else {
          setAutoStatus("Aucune question import\u{00e9}e (v\u{00e9}rifiez le format des colonnes).", "error");
        }
      } catch (err) {
        if (err instanceof Error && err.message === "excel_lib_missing") {
          setAutoStatus("Import Excel indisponible (librairie manquante).", "error");
          return;
        }
        setAutoStatus("\u{00c9}chec d'import de fichier. R\u{00e9}essayez avec un CSV valide.", "error");
      }
    });
  }

  containers.forEach((container) => {
    bindContainer(container);
    try {
      const initial = JSON.parse(container.dataset.initial || "[]");
      if (Array.isArray(initial) && initial.length) initial.forEach((q) => addQuestion(container, q));
      else addQuestion(container, {});
    } catch (_) {
      addQuestion(container, {});
    }
    renumber(container);
  });

  if (readOnly) {
    if (btnAddQuestion) {
      btnAddQuestion.disabled = true;
      btnAddQuestion.classList.add("opacity-60", "cursor-not-allowed");
    }
    variantAddButtons.forEach((button) => {
      button.disabled = true;
      button.classList.add("opacity-60", "cursor-not-allowed");
    });
    builder.querySelectorAll("input, select, textarea, button").forEach((el) => {
      el.disabled = true;
      el.classList.add("opacity-60", "cursor-not-allowed");
    });
  }

  toggleSections();
  setActiveVariant(activeVariant);
  onDirty();
  setAutoStatus("Sauvegarde locale active (25 s).");
  window.setInterval(() => {
    if (!isSubmittingDriveXhr) saveSnapshot();
  }, AUTOSAVE_MS);
});
