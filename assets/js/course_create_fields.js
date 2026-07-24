(function () {
  "use strict";

  var AUTOSAVE_MS = 25000;
  var DESC_MIN = 140;
  var COVER_MAX = 5 * 1024 * 1024;
  var COVER_EXT = ["jpg", "jpeg", "png", "webp"];

  function q(sel, p) { return (p || document).querySelector(sel); }
  function qa(sel, p) { return Array.prototype.slice.call((p || document).querySelectorAll(sel)); }
  function txt(v) { return String(v || "").replace(/\s+/g, " ").trim(); }
  function clamp(n, min, max) { return Math.max(min, Math.min(max, n)); }
  function fmtXof(n) {
    var v = Number(n || 0);
    if (!isFinite(v)) v = 0;
    if (v <= 0) return "Gratuit";
    return Math.round(v).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") + " F CFA";
  }

  function initEnterpriseOffer(root, onChange) {
    if (!root) return null;

    var modeInput = q("[data-enterprise-mode-input]", root);
    var pricePanel = q("[data-enterprise-price-panel]", root);
    var discountPanel = q("[data-enterprise-discount-panel]", root);
    var priceInput = q('[name="enterprise_price_xof"]', root);
    var discountInput = q('[name="enterprise_discount_percent"]', root);
    var buttons = qa("[data-enterprise-mode-btn]", root);

    if (!priceInput || !discountInput || !buttons.length) return null;

    function normalizeMode(mode) {
      return mode === "price" || mode === "discount" ? mode : "none";
    }

    function detectMode() {
      if (modeInput && txt(modeInput.value) !== "") return normalizeMode(txt(modeInput.value));
      if (txt(priceInput.value) !== "") return "price";
      if (txt(discountInput.value) !== "") return "discount";
      return "none";
    }

    function togglePanel(panel, visible) {
      if (!panel) return;
      panel.classList.toggle("hidden", !visible);
      panel.setAttribute("aria-hidden", visible ? "false" : "true");
    }

    function syncButtons(mode) {
      buttons.forEach(function (btn) {
        var active = normalizeMode(btn.getAttribute("data-enterprise-mode-btn")) === mode;
        btn.setAttribute("aria-pressed", active ? "true" : "false");
        btn.classList.toggle("border-sky-400", active);
        btn.classList.toggle("bg-sky-500/15", active);
        btn.classList.toggle("text-white", active);
        btn.classList.toggle("shadow-[0_0_0_1px_rgba(56,189,248,0.22)]", active);
        btn.classList.toggle("border-slate-700", !active);
        btn.classList.toggle("text-slate-200", !active);
      });
    }

    function setMode(mode, options) {
      var opts = options || {};
      var nextMode = normalizeMode(mode);

      togglePanel(pricePanel, nextMode === "price");
      togglePanel(discountPanel, nextMode === "discount");

      if (!opts.preserveValues) {
        if (nextMode === "price") {
          discountInput.value = "";
        } else if (nextMode === "discount") {
          priceInput.value = "";
        } else {
          priceInput.value = "";
          discountInput.value = "";
        }
      }

      if (modeInput) modeInput.value = nextMode;
      syncButtons(nextMode);

      if (!opts.silent && typeof onChange === "function") {
        onChange(nextMode);
      }
    }

    buttons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        setMode(btn.getAttribute("data-enterprise-mode-btn"));
      });
    });

    setMode(detectMode(), { preserveValues: true, silent: true });

    return {
      getMode: function () {
        return normalizeMode(modeInput ? modeInput.value : detectMode());
      },
      setMode: setMode
    };
  }

  function ready(fn) {
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", fn);
    else fn();
  }

  ready(function () {
    var form = q("[data-course-create-form]");
    if (!form) {
      qa("[data-enterprise-offer-root]").forEach(function (root) {
        initEnterpriseOffer(root);
      });
      return;
    }

    var liteMode = form.hasAttribute("data-course-create-lite");
    var S = { submitting: false, dirty: false, savedHash: "", url: null };
    var key = "ecole:" + (form.getAttribute("data-autosave-key") || "course_create_default");

    var F = {
      title: q("#title", form),
      category: q("#category_id", form),
      level: q("#level", form),
      duration: q("#duration_hours", form),
      price: q("#price_xof", form),
      enterpriseMode: q("[data-enterprise-mode-input]", form),
      enterprisePrice: q('[name="enterprise_price_xof"]', form),
      enterpriseDiscount: q('[name="enterprise_discount_percent"]', form),
      desc: q("#description", form),
      cover: q("#cover_file", form),
      general: q("#general_file", form),
      contentPayload: q("#content_payload", form),
      examsPayload: q("#exams_payload", form),
      ref: q('[name="referral_override"]', form),
      ref2: q('[name="referral_l2_enabled"]', form)
    };

    var P = {
      coverMain: q("#cover-preview"),
      coverCard: q("#preview-cover"),
      title: q("#preview-title"),
      category: q("#preview-category"),
      level: q("#preview-level"),
      duration: q("#preview-duration"),
      price: q("#preview-price"),
      desc: q("#preview-description"),
      obj: q("#preview-objectives"),
      pre: q("#preview-prerequisites"),
      equip: q("#preview-equipment")
    };

    var U = {
      gWrap: q("#upload-progress-wrap"),
      gBar: q("#upload-progress-bar"),
      gTxt: q("#upload-progress-text"),
      cWrap: q("#cover-progress-wrap"),
      cBar: q("#cover-progress-bar"),
      cTxt: q("#cover-progress-text"),
      xWrap: q("#general-progress-wrap"),
      xBar: q("#general-progress-bar"),
      xTxt: q("#general-progress-text")
    };

    var B = {
      restore: q("#course-create-restore-banner"),
      restoreBtn: q("#course-create-restore-btn"),
      ignoreBtn: q("#course-create-ignore-restore-btn"),
      status: q("#course-create-local-status"),
      laterBtn: q("#save-continue-later-btn"),
      descCount: q("[data-description-counter]")
    };
    var localDraftUiEnabled = !!(B.restore || B.status || B.laterBtn);

    var M = {
      root: q("[data-course-summary]"),
      pill: q("[data-summary-percent]"),
      fill: q("[data-summary-progress-fill]"),
      meta: q("[data-summary-meta]"),
      notesAlert: q("[data-summary-notes-alert]"),
      notes: q("[data-summary-notes]"),
      submitBtn: q("[data-submit-course-btn]")
    };

    var Step = {
      current: 1,
      max: qa("[data-step-panel]", form).length || 1,
      initial: parseInt(form.getAttribute("data-initial-step") || "1", 10) || 1,
      panels: qa("[data-step-panel]", form),
      nav: qa("[data-step-nav]", form),
      prev: q("[data-step-prev]", form),
      next: q("[data-step-next]", form),
      nextLabel: q("[data-step-next-label]", form),
      finalActions: q("[data-step-final-actions]", form),
      badge: q("[data-step-badge]", form),
      heading: q("[data-step-heading]", form),
      helper: q("[data-step-helper]", form)
    };

    var STEP_META = {
      1: { title: "Informations", helper: "Complétez les champs de base.", next: "Continuer" },
      2: { title: "Médias", helper: "Ajoutez vos fichiers.", next: "Continuer" },
      3: { title: "Pédagogie", helper: "Renseignez les éléments pédagogiques.", next: "Prévisualiser" },
      4: { title: "Vérification", helper: "Vérifiez puis validez.", next: "" }
    };

    var enterpriseUi = initEnterpriseOffer(q("[data-enterprise-offer-root]", form), function () {
      vEnterprise();
      markDirty();
    });

    var hints = {};
    var hintFields = {
      title: F.title,
      category: F.category,
      duration: F.duration,
      price: F.price,
      description: F.desc,
      cover: F.cover
    };

    qa("[data-hint-for]", form).forEach(function (el) {
      var k = el.getAttribute("data-hint-for");
      hints[k] = el;
    });

    function normalizeTone(tone) {
      return ["error", "success", "warning", "info"].indexOf(tone) > -1 ? tone : "info";
    }

    function setToneClasses(el, base, tone, hidden) {
      if (!el) return;
      el.classList.remove(
        base + "--info",
        base + "--success",
        base + "--warning",
        base + "--error"
      );
      el.classList.add(base, base + "--" + normalizeTone(tone));
      el.classList.toggle("hidden", !!hidden);
    }

    function setFieldTone(field, tone) {
      if (!field) return;
      field.classList.remove(
        "course-create-field--error",
        "course-create-field--success",
        "course-create-field--warning"
      );
      if (tone === "error" || tone === "success" || tone === "warning") {
        field.classList.add("course-create-field--" + tone);
      }
    }

    function hint(k, m, t) {
      var el = hints[k];
      var tone = normalizeTone(t);
      var show = !!m && tone !== "success";
      if (el) {
        setToneClasses(el, "course-create-hint", tone, !show);
        el.textContent = show ? m : "";
      }
      setFieldTone(hintFields[k], m ? tone : "info");
    }

    function status(msg, t) {
      if (!B.status) return;
      setToneClasses(B.status, "course-create-local-status", t, !msg);
      B.status.textContent = msg || "";
    }

    function listValues(id) {
      var l = q("#" + id, form);
      if (!l) return [];
      return qa("input[data-list-input]", l).map(function (i) { return txt(i.value); }).filter(function (v) { return v !== ""; });
    }

    function updateListCounts() {
      qa("[data-list-count]", form).forEach(function (el) {
        var id = el.getAttribute("data-list-count") || "";
        if (!id) return;
        var maxAttr = el.getAttribute("data-list-max") || "";
        var max = parseInt(maxAttr, 10);
        var count = listValues(id).length;
        if (Number.isFinite(max) && max > 0) {
          el.textContent = String(count) + "/" + String(max);
          el.classList.toggle("is-full", count >= max);
        } else {
          el.textContent = String(count);
          el.classList.remove("is-full");
        }
      });
    }

    function snap() {
      return {
        v: 1,
        at: new Date().toISOString(),
        f: {
          t: F.title ? F.title.value : "",
          c: F.category ? F.category.value : "",
          l: F.level ? F.level.value : "",
          d: F.duration ? F.duration.value : "",
          p: F.price ? F.price.value : "",
          em: F.enterpriseMode ? F.enterpriseMode.value : "",
          ep: F.enterprisePrice ? F.enterprisePrice.value : "",
          ed: F.enterpriseDiscount ? F.enterpriseDiscount.value : "",
          x: F.desc ? F.desc.value : "",
          cp: F.contentPayload ? F.contentPayload.value : "",
          xp: F.examsPayload ? F.examsPayload.value : "",
          r: F.ref && F.ref.checked ? 1 : 0,
          r2: F.ref2 && F.ref2.checked ? 1 : 0
        },
        l: {
          o: listValues("objectives-list"),
          p: listValues("prerequisites-list"),
          e: listValues("equipment-list")
        }
      };
    }

    function hash(s) { return JSON.stringify({ f: s.f || {}, l: s.l || {} }); }

    function markDirty() { S.dirty = hash(snap()) !== S.savedHash; }

    function saveLocal(manual) {
      try {
        var s = snap();
        localStorage.setItem(key, JSON.stringify(s));
        S.savedHash = hash(s);
        S.dirty = false;
        status(manual ? "Brouillon local enregistr\u00e9. Vous pouvez reprendre plus tard." : "Brouillon local sauvegard\u00e9.", "success");
      } catch (e) {
        status("Sauvegarde locale impossible.", "error");
      }
    }

    function readLocal() {
      try {
        var r = localStorage.getItem(key);
        if (!r) return null;
        var p = JSON.parse(r);
        return p && typeof p === "object" ? p : null;
      } catch (e) { return null; }
    }

    function clearLocal() {
      try { localStorage.removeItem(key); } catch (e) {}
    }

    function row(list, name, ph, value) {
      var wrap = document.createElement("div");
      wrap.className = "course-list-row";
      wrap.setAttribute("data-list-row", "1");
      wrap.draggable = true;

      var drag = document.createElement("button");
      drag.type = "button";
      drag.className = "course-list-handle";
      drag.textContent = "⋮⋮";
      drag.title = "R\u00e9ordonner";
      drag.setAttribute("aria-label", "R\u00e9ordonner");

      var input = document.createElement("input");
      input.type = "text";
      input.name = name;
      input.value = value || "";
      input.placeholder = ph || "";
      input.required = true;
      input.setAttribute("data-list-input", "1");
      input.className = "course-list-input";
      input.addEventListener("input", function () { markDirty(); preview(); });

      var del = document.createElement("button");
      del.type = "button";
      del.textContent = "×";
      del.className = "course-list-remove";
      del.setAttribute("aria-label", "Supprimer");
      del.addEventListener("click", function () {
        wrap.remove();
        ensureOne(list);
        markDirty();
        preview();
      });

      wrap.appendChild(drag);
      wrap.appendChild(input);
      wrap.appendChild(del);
      return wrap;
    }

    function ensureOne(list) {
      if (qa("[data-list-row]", list).length > 0) return;
      list.appendChild(row(list, list.getAttribute("data-name") || "", list.getAttribute("data-placeholder") || "", ""));
    }

    function fillList(list, values) {
      list.innerHTML = "";
      var name = list.getAttribute("data-name") || "";
      var ph = list.getAttribute("data-placeholder") || "";
      var arr = Array.isArray(values) && values.length ? values : [""];
      arr.forEach(function (v) { list.appendChild(row(list, name, ph, String(v || ""))); });
      ensureOne(list);
    }

    function addListValue(list, value) {
      var v = txt(value);
      if (!v) return false;
      var name = list.getAttribute("data-name") || "";
      var ph = list.getAttribute("data-placeholder") || "";
      var emptyRow = qa("[data-list-row]", list).find(function (r) {
        var i = q("input[data-list-input]", r);
        return i && txt(i.value) === "";
      });
      if (emptyRow) {
        var input = q("input[data-list-input]", emptyRow);
        if (input) input.value = v;
      } else {
        list.appendChild(row(list, name, ph, v));
      }
      markDirty();
      preview();
      return true;
    }

    function dragAfter(list, y) {
      var rows = qa("[data-list-row]", list).filter(function (r) { return !r.classList.contains("opacity-60"); });
      var closest = { offset: Number.NEGATIVE_INFINITY, el: null };
      rows.forEach(function (r) {
        var b = r.getBoundingClientRect();
        var o = y - b.top - b.height / 2;
        if (o < 0 && o > closest.offset) closest = { offset: o, el: r };
      });
      return closest.el;
    }

    function wireDrag(list) {
      var dragging = null;
      list.addEventListener("dragstart", function (e) {
        var r = e.target.closest("[data-list-row]");
        if (!r) return;
        dragging = r;
        r.classList.add("opacity-60", "ring-1", "ring-emerald-500");
        if (e.dataTransfer) {
          e.dataTransfer.effectAllowed = "move";
          e.dataTransfer.setData("text/plain", "1");
        }
      });
      list.addEventListener("dragover", function (e) {
        if (!dragging) return;
        e.preventDefault();
        var after = dragAfter(list, e.clientY);
        if (!after) list.appendChild(dragging); else list.insertBefore(dragging, after);
      });
      list.addEventListener("drop", function (e) {
        if (!dragging) return;
        e.preventDefault();
        markDirty();
        preview();
      });
      list.addEventListener("dragend", function () {
        if (!dragging) return;
        dragging.classList.remove("opacity-60", "ring-1", "ring-emerald-500");
        dragging = null;
      });
    }

    function initLists() {
      qa('[id$="-list"][data-name]', form).forEach(function (l) {
        var v = [];
        try { v = JSON.parse(l.getAttribute("data-initial") || "[]"); } catch (e) { v = []; }
        if (!Array.isArray(v)) v = [];
        fillList(l, v);
        wireDrag(l);
      });

      qa("[data-add-target]", form).forEach(function (btn) {
        btn.addEventListener("click", function () {
          var id = btn.getAttribute("data-add-target") || "";
          var l = document.getElementById(id);
          if (!l) return;
          var quick = q('[data-list-quick-input][data-list-target="' + id + '"]', form);
          if (quick && txt(quick.value) !== "") {
            addListValue(l, quick.value);
            quick.value = "";
            quick.focus();
            return;
          }
          l.appendChild(row(l, l.getAttribute("data-name") || "", l.getAttribute("data-placeholder") || "", ""));
          markDirty();
          preview();
        });
      });

      qa("[data-list-quick-input]", form).forEach(function (input) {
        input.addEventListener("keydown", function (e) {
          if (e.key !== "Enter") return;
          e.preventDefault();
          var id = input.getAttribute("data-list-target") || "";
          var l = document.getElementById(id);
          if (!l) return;
          if (addListValue(l, input.value)) {
            input.value = "";
          }
          input.focus();
        });
      });
    }

    function setCover(src, empty) {
      [P.coverMain, P.coverCard].forEach(function (img) {
        if (!img) return;
        img.src = src;
        if (empty) img.setAttribute("data-cover-empty", "1");
        else img.removeAttribute("data-cover-empty");
      });
    }

    function renderList(target, values, emptyLabel) {
      if (!target) return;
      target.innerHTML = "";
      if (!values.length) {
        var li0 = document.createElement("li");
        li0.textContent = emptyLabel;
        target.appendChild(li0);
        return;
      }
      values.forEach(function (it) {
        var li = document.createElement("li");
        li.textContent = it;
        target.appendChild(li);
      });
    }

    function setSummaryItem(key, done, text) {
      if (!M.root) return;
      var item = q('[data-summary-item="' + key + '"]', M.root);
      if (!item) return;
      item.classList.toggle("is-done", !!done);
      var body = q('[data-summary-item-text="' + key + '"]', item);
      if (body) body.textContent = text;
    }

    function updateCourseSummary() {
      if (!M.root) return;

      var chapters = parseInt(M.root.getAttribute("data-chapter-count") || "0", 10) || 0;
      var lessons = parseInt(M.root.getAttribute("data-lesson-count") || "0", 10) || 0;
      var meetings = parseInt(M.root.getAttribute("data-meeting-count") || "0", 10) || 0;
      var locked = String(M.root.getAttribute("data-is-locked") || "0") === "1";

      var titleTxt = txt(F.title && F.title.value);
      var descTxt = txt(F.desc && F.desc.value);
      var titleOk = !!titleTxt && titleTxt.length >= 6;
      var categoryOk = !!(F.category && txt(F.category.value));
      var descOk = !!descTxt;
      var coverReady = !!((F.cover && F.cover.files && F.cover.files[0]) || q("[data-cover-existing]", form));
      var identityReady = titleOk && categoryOk && descOk;

      var structureReady = chapters > 0 && lessons > 0 && meetings > 0;

      var objCount = listValues("objectives-list").length;
      var preCount = listValues("prerequisites-list").length;
      var equipCount = listValues("equipment-list").length;
      var pedagogyReady = objCount > 0;

      var publicationReady = !locked;

      var readyCount = 0;
      if (identityReady) readyCount++;
      if (structureReady) readyCount++;
      if (pedagogyReady) readyCount++;
      if (publicationReady && coverReady) readyCount++;
      var pct = Math.round((readyCount / 4) * 100);

      if (M.pill) M.pill.textContent = String(pct) + "% pr\u00eat";
      if (M.fill) M.fill.style.width = String(pct) + "%";
      if (M.meta) M.meta.textContent = String(readyCount) + "/4 rep\u00e8res valid\u00e9s.";

      setSummaryItem(
        "identity",
        identityReady,
        identityReady ? "Base pr\u00eate." : "Base \u00e0 compl\u00e9ter."
      );
      setSummaryItem(
        "structure",
        structureReady,
        String(chapters) + " chap., " + String(lessons) + " le\u00e7., " + String(meetings) + " RDV."
      );
      setSummaryItem(
        "pedagogy",
        pedagogyReady,
        String(objCount) + " obj., " + String(preCount) + " pr\u00e9req., " + String(equipCount) + " \u00e9quip."
      );
      setSummaryItem(
        "publication",
        publicationReady && coverReady,
        !coverReady
          ? "Couverture manquante."
          : (publicationReady ? "Pr\u00eat." : "Verrouill\u00e9.")
      );

      var notes = [];
      if (!identityReady) notes.push("Titre, cat\u00e9gorie, description.");
      if (!structureReady) {
        if (!(chapters > 0 && lessons > 0)) {
          notes.push("Chapitre et le\u00e7on.");
        }
        if (meetings < 1) {
          notes.push("Rendez-vous.");
        }
      }
      if (!pedagogyReady) notes.push("Objectifs.");
      if (!coverReady) notes.push("Image de couverture.");
      if (locked) notes.push("Cours verrouill\u00e9.");

      if (M.notesAlert) {
        var show = notes.length > 0;
        M.notesAlert.classList.toggle("hidden", !show);
        M.notesAlert.setAttribute("aria-hidden", show ? "false" : "true");
      }
      if (M.notes) {
        M.notes.innerHTML = "";
        notes.forEach(function (note) {
          var p = document.createElement("p");
          p.textContent = "\u2610 " + note;
          M.notes.appendChild(p);
        });
      }

      if (M.submitBtn) {
        var canSubmit = identityReady && structureReady && pedagogyReady && coverReady && !locked;
        M.submitBtn.disabled = !canSubmit;
        M.submitBtn.classList.toggle("opacity-60", !canSubmit);
        M.submitBtn.classList.toggle("cursor-not-allowed", !canSubmit);
      }
    }

    function preview() {
      var objectiveValues = listValues("objectives-list");
      var prerequisiteValues = listValues("prerequisites-list");
      var equipmentValues = listValues("equipment-list");

      if (P.title) P.title.textContent = txt(F.title && F.title.value) || "Titre du cours";
      if (P.category && F.category) {
        var o = F.category.options[F.category.selectedIndex];
        var t = o ? txt(o.textContent) : "";
        P.category.textContent = t && t.indexOf("S\u00e9lectionner") === -1 ? t : "Cat\u00e9gorie";
      }
      if (P.level && F.level) {
        var lo = F.level.options[F.level.selectedIndex];
        P.level.textContent = lo ? txt(lo.textContent) : "Niveau";
      }
      if (P.duration && F.duration) {
        var dv = Number(F.duration.value || 0);
        if (isFinite(dv) && dv > 0) {
          var totalMin = Math.round(dv * 60);
          var h = Math.floor(totalMin / 60);
          var m = totalMin % 60;
          var mm = m < 10 ? "0" + String(m) : String(m);
          P.duration.textContent = String(h) + " h " + mm + " min";
        } else {
          P.duration.textContent = "0 h";
        }
      }
      if (P.price && F.price) P.price.textContent = fmtXof(F.price.value);
      if (P.desc && F.desc) {
        var d = String(F.desc.value || "").trim();
        P.desc.textContent = d ? d : "Description du cours.";
      }
      renderList(P.obj, objectiveValues, "Ajoutez des objectifs pour enrichir la fiche.");
      renderList(P.pre, prerequisiteValues, "Aucun pr\u00e9requis sp\u00e9cifique.");
      renderList(P.equip, equipmentValues, "Aucun mat\u00e9riel requis.");
      var previewObjectiveCount = q('[data-preview-count="objectives"]', form);
      var previewPrerequisiteCount = q('[data-preview-count="prerequisites"]', form);
      var previewEquipmentCount = q('[data-preview-count="equipment"]', form);
      if (previewObjectiveCount) previewObjectiveCount.textContent = String(objectiveValues.length);
      if (previewPrerequisiteCount) previewPrerequisiteCount.textContent = String(prerequisiteValues.length);
      if (previewEquipmentCount) previewEquipmentCount.textContent = String(equipmentValues.length);
      updateListCounts();
      updateCourseSummary();
    }

    function updateDescCounter() {
      if (!B.descCount || !F.desc) return;
      var n = String(F.desc.value || "").length;
      B.descCount.textContent = String(n) + " / 3000";
      B.descCount.className = n > 2500 ? "text-amber-300" : "text-slate-400";
    }

    function vTitle() {
      if (!F.title) return true;
      var v = txt(F.title.value);
      if (!v) { F.title.setCustomValidity("Le titre est obligatoire."); hint("title", "Titre obligatoire.", "error"); return false; }
      if (v.length < 6) { F.title.setCustomValidity("Le titre doit contenir au moins 6 caract\u00e8res."); hint("title", "Minimum requis : 6 caract\u00e8res.", "error"); return false; }
      F.title.setCustomValidity("");
      hint("title", v.length < 12 ? "Un titre plus descriptif est recommand\u00e9 (12+)." : "Titre valide.", v.length < 12 ? "warning" : "success");
      return true;
    }

    function vCategory() {
      if (!F.category) return true;
      if (!String(F.category.value || "")) { F.category.setCustomValidity(""); hint("category", "Facultatif pour le brouillon, requis pour la soumission.", "warning"); return true; }
      F.category.setCustomValidity("");
      hint("category", "Cat\u00e9gorie valide.", "success");
      return true;
    }

    function vDuration() {
      if (!F.duration) return true;
      var n = Number(F.duration.value || 0);
      if (!isFinite(n) || n <= 0) { F.duration.setCustomValidity("La dur\u00e9e doit \u00eatre sup\u00e9rieure \u00e0 0."); hint("duration", "Entrez une dur\u00e9e positive.", "error"); return false; }
      F.duration.setCustomValidity("");
      hint("duration", n < 0.5 ? "Dur\u00e9e faible, v\u00e9rifiez la valeur." : "Dur\u00e9e valide.", n < 0.5 ? "warning" : "success");
      return true;
    }

    function vPrice() {
      if (!F.price) return true;
      var raw = String(F.price.value || "").trim();
      var n = Number(raw);
      if (!raw) { F.price.setCustomValidity("Le prix est obligatoire."); hint("price", "Entrez un prix entier (>= 0).", "error"); return false; }
      if (!isFinite(n) || n < 0 || Math.floor(n) !== n) { F.price.setCustomValidity("Le prix doit \u00eatre un entier positif ou nul."); hint("price", "Entrez un prix entier (>= 0).", "error"); return false; }
      F.price.setCustomValidity("");
      hint("price", n === 0 ? "Cours gratuit d\u00e9tect\u00e9." : "Prix valide.", n === 0 ? "warning" : "success");
      return true;
    }

    function vEnterprise() {
      if (!F.enterprisePrice) return true;

      var mode = enterpriseUi ? enterpriseUi.getMode() : (F.enterpriseMode ? F.enterpriseMode.value : "");
      var raw = String(F.enterprisePrice.value || "").trim();

      if (mode !== "price") {
        F.enterprisePrice.setCustomValidity("");
        return true;
      }

      if (!raw) { F.enterprisePrice.setCustomValidity("Le prix entreprise est obligatoire."); return false; }

      var n = Number(raw);
      if (!isFinite(n) || n <= 0 || Math.floor(n) !== n) {
        F.enterprisePrice.setCustomValidity("Le prix entreprise doit etre un entier positif.");
        return false;
      }

      var baseRaw = F.price ? String(F.price.value || "").trim() : "";
      var base = Number(baseRaw);
      if (!baseRaw || !isFinite(base) || base < 0 || Math.floor(base) !== base) {
        F.enterprisePrice.setCustomValidity("Renseignez d'abord un prix public valide.");
        return false;
      }

      if (n > base) {
        F.enterprisePrice.setCustomValidity("Le prix entreprise ne peut pas d\u00e9passer le prix public.");
        return false;
      }

      F.enterprisePrice.setCustomValidity("");
      return true;
    }

    function vDesc() {
      if (!F.desc) return true;
      var v = txt(F.desc.value);
      updateDescCounter();
      if (!v) { F.desc.setCustomValidity("La description est obligatoire."); hint("description", "Description obligatoire.", "error"); return false; }
      F.desc.setCustomValidity("");
      hint("description", v.length < DESC_MIN ? ("Minimum recommand\u00e9 : " + DESC_MIN + " caract\u00e8res.") : "Description d\u00e9taill\u00e9e : OK.", v.length < DESC_MIN ? "warning" : "success");
      return true;
    }

    function vCover() {
      if (!F.cover) return true;
      var file = F.cover.files && F.cover.files[0] ? F.cover.files[0] : null;
      var existing = !!q("[data-cover-existing]", form);

      if (!file && !existing && F.cover.required) {
        F.cover.setCustomValidity("La miniature est obligatoire.");
        hint("cover", "Ajoutez une miniature.", "error");
        return false;
      }
      if (!file) {
        F.cover.setCustomValidity("");
        hint("cover", existing ? "Miniature actuelle conserv\u00e9e." : "", existing ? "success" : "info");
        return true;
      }

      var low = String(file.name || "").toLowerCase();
      var ext = low.indexOf(".") > -1 ? low.split(".").pop() : "";
      if (COVER_EXT.indexOf(ext) === -1) {
        F.cover.setCustomValidity("Format non autoris\u00e9 (JPG/PNG/WebP).");
        hint("cover", "Format non autoris\u00e9.", "error");
        return false;
      }
      if (file.size > COVER_MAX) {
        F.cover.setCustomValidity("La miniature d\u00e9passe 5 Mo.");
        hint("cover", "Fichier sup\u00e9rieur \u00e0 5 Mo.", "error");
        return false;
      }

      F.cover.setCustomValidity("");
      hint("cover", "Miniature valide (" + (file.size / (1024 * 1024)).toFixed(2) + " Mo).", "success");
      return true;
    }

    function validateAll() {
      var ok = true;
      ok = vTitle() && ok;
      ok = vCategory() && ok;
      ok = vDuration() && ok;
      ok = vPrice() && ok;
      ok = vEnterprise() && ok;
      ok = vDesc() && ok;
      ok = vCover() && ok;
      return ok;
    }

    function validateRequiredLists() {
      var objectiveList = q("#objectives-list", form);
      var objectiveInput = q('[data-list-quick-input][data-list-target="objectives-list"]', form);
      if (objectiveList && objectiveInput && txt(objectiveInput.value) !== "") {
        addListValue(objectiveList, objectiveInput.value);
        objectiveInput.value = "";
      }
      if (objectiveList && listValues("objectives-list").length < 1) {
        status("Ajoutez au moins un objectif pour expliquer ce que l'apprenant saura faire.", "error");
        if (objectiveInput && typeof objectiveInput.focus === "function") {
          objectiveInput.focus();
        }
        return false;
      }
      return true;
    }

    function focusFirstInvalid(fields) {
      fields.some(function (field) {
        if (!field || typeof field.checkValidity !== "function") return false;
        if (field.checkValidity()) return false;
        if (typeof field.focus === "function") field.focus();
        return true;
      });
    }

    function validateStep(step) {
      if (step === 1) {
        var step1Ok = vTitle() && vCategory() && vDuration() && vPrice() && vDesc();
        if (!step1Ok) focusFirstInvalid([F.title, F.duration, F.price, F.desc]);
        return step1Ok;
      }
      if (step === 2) {
        var step2Ok = vCover();
        if (!step2Ok) focusFirstInvalid([F.cover]);
        return step2Ok;
      }
      if (step === 3) {
        var step3Ok = vEnterprise();
        if (!step3Ok) focusFirstInvalid([F.enterprisePrice]);
        return step3Ok;
      }
      return true;
    }

    function stepTopOffset() {
      var rect = form.getBoundingClientRect();
      return Math.max(0, rect.top + window.scrollY - 108);
    }

    function refreshStepperUi() {
      var meta = STEP_META[Step.current] || STEP_META[1];

      Step.panels.forEach(function (panel) {
        var idx = parseInt(panel.getAttribute("data-step-panel") || "1", 10) || 1;
        var active = idx === Step.current;
        panel.classList.toggle("hidden", !active);
        panel.setAttribute("aria-hidden", active ? "false" : "true");
      });

      Step.nav.forEach(function (btn) {
        var idx = parseInt(btn.getAttribute("data-step-nav") || "1", 10) || 1;
        var active = idx === Step.current;
        var complete = idx < Step.current;
        var unlocked = idx <= Step.current + 1;

        btn.classList.toggle("is-active", active);
        btn.classList.toggle("is-complete", complete);
        btn.classList.toggle("is-upcoming", !active && !complete);
        btn.disabled = !complete && !active && !unlocked;
        btn.setAttribute("aria-current", active ? "step" : "false");
      });

      if (Step.prev) Step.prev.disabled = Step.current <= 1;
      if (Step.next) Step.next.classList.toggle("hidden", Step.current >= Step.max);
      if (Step.finalActions) Step.finalActions.classList.toggle("hidden", Step.current !== Step.max);
      if (Step.nextLabel) Step.nextLabel.textContent = meta.next || "Continuer";
      if (Step.badge) Step.badge.textContent = "Étape " + String(Step.current) + " / " + String(Step.max);
      if (Step.heading) Step.heading.textContent = meta.title;
      if (Step.helper) Step.helper.textContent = meta.helper;
    }

    function goToStep(step, options) {
      var opts = options || {};
      var next = clamp(Number(step) || 1, 1, Step.max);
      Step.current = next;
      refreshStepperUi();
      if (!opts.silent) {
        window.scrollTo({ top: stepTopOffset(), behavior: "smooth" });
      }
    }

    function wireStepper() {
      if (!Step.panels.length) return;

      Step.nav.forEach(function (btn) {
        btn.addEventListener("click", function () {
          var target = parseInt(btn.getAttribute("data-step-nav") || "1", 10) || 1;
          if (target <= Step.current) {
            goToStep(target);
            return;
          }
          if (target === Step.current + 1 && validateStep(Step.current)) {
            goToStep(target);
          }
        });
      });

      if (Step.prev) {
        Step.prev.addEventListener("click", function () {
          if (Step.current <= 1) return;
          goToStep(Step.current - 1);
        });
      }

      if (Step.next) {
        Step.next.addEventListener("click", function () {
          if (Step.current >= Step.max) return;
          if (!validateStep(Step.current)) return;
          goToStep(Step.current + 1);
        });
      }

      goToStep(clamp(Step.initial, 1, Step.max), { silent: true });
    }

    function applyLocal(snapshot) {
      if (!snapshot || typeof snapshot !== "object") return;
      var f = snapshot.f || {};
      var l = snapshot.l || {};

      if (F.title && typeof f.t === "string") F.title.value = f.t;
      if (F.category && typeof f.c === "string") F.category.value = f.c;
      if (F.level && typeof f.l === "string") F.level.value = f.l;
      if (F.duration && typeof f.d === "string") F.duration.value = f.d;
      if (F.price && typeof f.p === "string") F.price.value = f.p;
      if (F.enterpriseMode && typeof f.em === "string") F.enterpriseMode.value = f.em;
      if (F.enterprisePrice && typeof f.ep === "string") F.enterprisePrice.value = f.ep;
      if (F.enterpriseDiscount && typeof f.ed === "string") F.enterpriseDiscount.value = f.ed;
      if (F.desc && typeof f.x === "string") F.desc.value = f.x;
      if (F.contentPayload && typeof f.cp === "string") F.contentPayload.value = f.cp;
      if (F.examsPayload && typeof f.xp === "string") F.examsPayload.value = f.xp;
      if (F.ref) F.ref.checked = Number(f.r || 0) === 1;
      if (F.ref2) F.ref2.checked = Number(f.r2 || 0) === 1;

      if (q("#objectives-list", form)) fillList(q("#objectives-list", form), l.o || []);
      if (q("#prerequisites-list", form)) fillList(q("#prerequisites-list", form), l.p || []);
      if (q("#equipment-list", form)) fillList(q("#equipment-list", form), l.e || []);

      if (F.ref) F.ref.dispatchEvent(new Event("change", { bubbles: true }));
      if (enterpriseUi) {
        var restoredMode = F.enterpriseMode && txt(F.enterpriseMode.value) !== ""
          ? F.enterpriseMode.value
          : (F.enterprisePrice && txt(F.enterprisePrice.value) !== ""
              ? "price"
              : (F.enterpriseDiscount && txt(F.enterpriseDiscount.value) !== "" ? "discount" : "none"));
        enterpriseUi.setMode(restoredMode, { preserveValues: true, silent: true });
      }
      validateAll();
      preview();
      S.savedHash = hash(snap());
      S.dirty = false;
      status("Brouillon local restaur\u00e9.", "success");
    }

    function refreshRestore() {
      if (!B.restore) return;
      var s = readLocal();
      if (!s) { B.restore.classList.add("hidden"); return; }
      if (hash(s) === hash(snap())) { B.restore.classList.add("hidden"); return; }
      B.restore.classList.remove("hidden");
    }

    function uploadReset() {
      if (U.gWrap) U.gWrap.classList.add("hidden");
      if (U.cWrap) U.cWrap.classList.add("hidden");
      if (U.xWrap) U.xWrap.classList.add("hidden");
      if (U.gBar) U.gBar.style.width = "0%";
      if (U.cBar) U.cBar.style.width = "0%";
      if (U.xBar) U.xBar.style.width = "0%";
      if (U.gTxt) U.gTxt.textContent = "0%";
      if (U.cTxt) U.cTxt.textContent = "0%";
      if (U.xTxt) U.xTxt.textContent = "0%";
    }

    function uploadProgress(loaded, total, cBytes, xBytes) {
      if (U.gWrap) U.gWrap.classList.remove("hidden");
      var g = total > 0 ? clamp((loaded / total) * 100, 0, 100) : 0;
      if (U.gBar) U.gBar.style.width = g.toFixed(1) + "%";
      if (U.gTxt) U.gTxt.textContent = Math.round(g) + "%";

      var tracked = cBytes + xBytes;
      if (tracked <= 0) return;
      var loadedFiles = clamp((g / 100) * tracked, 0, tracked);
      var cLoaded = clamp(loadedFiles, 0, cBytes);
      var xLoaded = clamp(loadedFiles - cBytes, 0, xBytes);

      if (cBytes > 0 && U.cWrap) {
        U.cWrap.classList.remove("hidden");
        var c = clamp((cLoaded / cBytes) * 100, 0, 100);
        if (U.cBar) U.cBar.style.width = c.toFixed(1) + "%";
        if (U.cTxt) U.cTxt.textContent = Math.round(c) + "%";
      }
      if (xBytes > 0 && U.xWrap) {
        U.xWrap.classList.remove("hidden");
        var x = clamp((xLoaded / xBytes) * 100, 0, 100);
        if (U.xBar) U.xBar.style.width = x.toFixed(1) + "%";
        if (U.xTxt) U.xTxt.textContent = Math.round(x) + "%";
      }
    }

    function hydrateResponse(xhr) {
      var url = String(xhr.responseURL || "");
      var curr = window.location.href.split("#")[0];
      if (url && url !== curr) { window.location.assign(url); return; }

      var body = String(xhr.responseText || "");
      if (body.indexOf("<html") !== -1) {
        document.open();
        document.write(body);
        document.close();
        return;
      }
      window.location.reload();
    }

    function wireInputs() {
      if (F.title) F.title.addEventListener("input", function () { vTitle(); markDirty(); preview(); });
      if (F.category) F.category.addEventListener("change", function () { vCategory(); markDirty(); preview(); });
      if (F.level) F.level.addEventListener("change", function () { markDirty(); preview(); });
      if (F.duration) F.duration.addEventListener("input", function () { vDuration(); markDirty(); preview(); });
      if (F.price) F.price.addEventListener("input", function () { vPrice(); vEnterprise(); markDirty(); preview(); });
      if (F.enterprisePrice) F.enterprisePrice.addEventListener("input", function () { vEnterprise(); markDirty(); });
      if (F.enterpriseDiscount) F.enterpriseDiscount.addEventListener("input", markDirty);
      if (F.desc) F.desc.addEventListener("input", function () { vDesc(); markDirty(); preview(); });
      if (F.ref) F.ref.addEventListener("change", markDirty);
      if (F.ref2) F.ref2.addEventListener("change", markDirty);

      if (F.cover) {
        F.cover.addEventListener("change", function () {
          vCover();
          markDirty();
          if (S.url) { URL.revokeObjectURL(S.url); S.url = null; }
          var f = F.cover.files && F.cover.files[0] ? F.cover.files[0] : null;
          if (f) {
            S.url = URL.createObjectURL(f);
            setCover(S.url, false);
          } else if (q("[data-cover-existing]", form)) {
            setCover(q("[data-cover-existing]", form).getAttribute("src"), false);
          } else if (P.coverMain && P.coverMain.getAttribute("data-cover-fallback-src")) {
            setCover(P.coverMain.getAttribute("data-cover-fallback-src"), true);
          }
          preview();
        });
      }

      if (F.general) F.general.addEventListener("change", markDirty);
      form.addEventListener("input", markDirty);
    }

    function wireAutosave() {
      S.savedHash = hash(snap());
      var ls = readLocal();
      if (ls) {
        if (hash(ls) !== hash(snap())) {
          applyLocal(ls);
          if (B.restore) B.restore.classList.remove("hidden");
        } else {
          refreshRestore();
        }
      } else {
        refreshRestore();
      }

      if (B.restoreBtn) {
        B.restoreBtn.addEventListener("click", function () {
          applyLocal(readLocal());
          if (B.restore) B.restore.classList.add("hidden");
        });
      }
      if (B.ignoreBtn) {
        B.ignoreBtn.addEventListener("click", function () {
          if (B.restore) B.restore.classList.add("hidden");
        });
      }
      if (B.laterBtn) {
        B.laterBtn.addEventListener("click", function () {
          saveLocal(true);
          window.scrollTo({ top: 0, behavior: "smooth" });
        });
      }

      window.setInterval(function () {
        if (S.submitting || !S.dirty) return;
        saveLocal(false);
      }, AUTOSAVE_MS);
    }

    function wireUnloadGuard() {
      window.addEventListener("beforeunload", function (e) {
        if (S.submitting || !S.dirty) return;
        e.preventDefault();
        e.returnValue = "";
      });
    }

    function disableSubmit(disabled) {
      qa('button[type="submit"]', form).forEach(function (b) {
        b.disabled = !!disabled;
        b.classList.toggle("opacity-60", !!disabled);
        b.classList.toggle("cursor-not-allowed", !!disabled);
      });
    }

    function wireSubmit() {
      form.addEventListener("submit", function (e) {
        if (S.submitting) return;
        var submitter = e.submitter || null;
        var isCategoryProposal = !!(submitter && submitter.name === "category_action" && submitter.value === "propose");
        if (!isCategoryProposal) {
          validateAll();
          if (!validateRequiredLists()) return;
          if (!form.checkValidity()) return;
        }
        if (!window.XMLHttpRequest || !window.FormData) return;

        e.preventDefault();
        S.submitting = true;
        disableSubmit(true);
        uploadReset();
        status("Envoi en cours...", "warning");

        var data = new FormData(form);
        if (submitter && submitter.name) data.set(submitter.name, submitter.value || "");

        var cBytes = F.cover && F.cover.files && F.cover.files[0] ? Number(F.cover.files[0].size || 0) : 0;
        var xBytes = F.general && F.general.files && F.general.files[0] ? Number(F.general.files[0].size || 0) : 0;

        var xhr = new XMLHttpRequest();
        xhr.open("POST", form.action, true);
        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        xhr.timeout = 300000;

        xhr.upload.addEventListener("progress", function (pe) {
          if (!pe.lengthComputable) return;
          uploadProgress(pe.loaded, pe.total, cBytes, xBytes);
        });

        xhr.onload = function () {
          S.submitting = false;
          disableSubmit(false);
          if (xhr.status >= 200 && xhr.status < 400) {
            clearLocal();
            S.savedHash = hash(snap());
            S.dirty = false;
            status("Envoi termin\u00e9.", "success");
          } else {
            status("Erreur serveur pendant la soumission.", "error");
          }
          hydrateResponse(xhr);
        };

        xhr.onerror = function () {
          S.submitting = false;
          disableSubmit(false);
          status("Erreur r\u00e9seau. Soumission classique...", "error");
          if (submitter && submitter.name) {
            var hiddenAction = q('input[name="' + submitter.name + '"][data-fallback-submitter="1"]', form);
            if (!hiddenAction) {
              hiddenAction = document.createElement("input");
              hiddenAction.type = "hidden";
              hiddenAction.name = submitter.name;
              hiddenAction.setAttribute("data-fallback-submitter", "1");
              form.appendChild(hiddenAction);
            }
            hiddenAction.value = submitter.value || "";
          }
          form.submit();
        };

        xhr.ontimeout = function () {
          S.submitting = false;
          disableSubmit(false);
          status("D\u00e9lai d\u00e9pass\u00e9. Soumission classique...", "warning");
          if (submitter && submitter.name) {
            var hiddenAction = q('input[name="' + submitter.name + '"][data-fallback-submitter="1"]', form);
            if (!hiddenAction) {
              hiddenAction = document.createElement("input");
              hiddenAction.type = "hidden";
              hiddenAction.name = submitter.name;
              hiddenAction.setAttribute("data-fallback-submitter", "1");
              form.appendChild(hiddenAction);
            }
            hiddenAction.value = submitter.value || "";
          }
          form.submit();
        };

        xhr.send(data);
      });
    }

    initLists();
    if (liteMode) {
      updateListCounts();
      return;
    }
    wireInputs();
    wireStepper();
    updateDescCounter();
    preview();
    if (localDraftUiEnabled) {
      wireAutosave();
      wireUnloadGuard();
    }
    wireSubmit();
  });
})();
