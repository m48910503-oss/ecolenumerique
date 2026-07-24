(() => {
  "use strict";

  const q = (sel, parent) => (parent || document).querySelector(sel);
  const qa = (sel, parent) => Array.prototype.slice.call((parent || document).querySelectorAll(sel));

  const ready = (fn) => {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn);
    } else {
      fn();
    }
  };

  const clamp = (n, min, max) => Math.max(min, Math.min(max, n));

  ready(() => {
    const form = q("[data-course-create-form]");
    const stepper = q("[data-course-stepper]");
    if (!form || !stepper) return;

    const steps = qa("[data-step]", form);
    if (!steps.length) return;

    const stepItems = qa("[data-step-item]", stepper);
    const currentEl = q("[data-step-current]", stepper);
    const totalEl = q("[data-step-total]", stepper);
    const progressEl = q("[data-step-progress]", stepper);
    const total = steps.length;
    const autosaveKey = form.getAttribute("data-autosave-key") || "course_create_default";
    const storageKey = `ecole:course_step:${autosaveKey}`;

    if (totalEl) totalEl.textContent = String(total);

    let current = 1;

    const getStepEl = (idx) => steps[idx - 1] || null;

    const updateStepper = () => {
      if (currentEl) currentEl.textContent = String(current);
      stepItems.forEach((item) => {
        const idx = parseInt(item.getAttribute("data-step-item") || "0", 10);
        const active = idx === current;
        const done = idx > 0 && idx < current;
        item.classList.toggle("is-active", active);
        item.classList.toggle("is-done", done);
        if (active) item.setAttribute("aria-current", "step");
        else item.removeAttribute("aria-current");
      });

      if (progressEl) {
        const pct = total <= 1 ? 100 : ((current - 1) / (total - 1)) * 100;
        progressEl.style.width = `${clamp(pct, 0, 100).toFixed(0)}%`;
      }
    };

    const showStep = (idx) => {
      current = clamp(idx, 1, total);
      steps.forEach((stepEl, i) => {
        const active = i + 1 === current;
        stepEl.classList.toggle("hidden", !active);
        stepEl.setAttribute("aria-hidden", active ? "false" : "true");
      });
      updateStepper();
      try {
        localStorage.setItem(storageKey, String(current));
      } catch (_e) {}
      window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const validateStep = (stepEl) => {
      if (!stepEl) return true;
      const fields = qa("input, select, textarea", stepEl).filter(
        (el) => !el.disabled && el.hasAttribute("required")
      );
      for (const field of fields) {
        if (!field.checkValidity()) {
          field.reportValidity();
          return false;
        }
      }
      return true;
    };

    qa("[data-step-next]", form).forEach((btn) => {
      btn.addEventListener("click", () => {
        const stepEl = getStepEl(current);
        if (!validateStep(stepEl)) return;
        showStep(current + 1);
      });
    });

    qa("[data-step-prev]", form).forEach((btn) => {
      btn.addEventListener("click", () => {
        showStep(current - 1);
      });
    });

    qa("[data-step-jump]", stepper).forEach((btn) => {
      btn.addEventListener("click", () => {
        const target = parseInt(btn.getAttribute("data-step-jump") || "1", 10);
        if (target === current) return;
        if (target > current) {
          const stepEl = getStepEl(current);
          if (!validateStep(stepEl)) return;
        }
        showStep(target);
      });
    });

    const urlStep = new URLSearchParams(window.location.search).get("step");
    let savedStep = null;
    try {
      const raw = localStorage.getItem(storageKey);
      const parsed = parseInt(raw || "", 10);
      if (Number.isFinite(parsed) && parsed > 0) savedStep = parsed;
    } catch (_e) {}
    const dataStart = stepper.getAttribute("data-step-start");
    const start = parseInt(urlStep || savedStep || dataStart || "1", 10);
    showStep(start);
  });
})();
