(() => {
  function onReady(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn);
      return;
    }
    fn();
  }

  onReady(() => {
    const root = document.querySelector("[data-onboarding-root]");
    if (!root) {
      return;
    }

    const steps = Array.from(root.querySelectorAll("[data-onboarding-step]"));
    const stepOrder = steps
      .map((step) => String(step.getAttribute("data-onboarding-step") || "").trim())
      .filter(Boolean);
    const progressBar = document.querySelector("[data-onboarding-progress-bar]");
    const titleInput = root.querySelector("#title");
    const descriptionInput = root.querySelector("#description");
    const categorySelect = root.querySelector("#category_id");
    const paceInputs = Array.from(root.querySelectorAll('input[name="pace"]'));
    const summaryTitle = root.querySelector("[data-summary-title]");
    const summaryCategory = root.querySelector("[data-summary-category]");
    const summaryPace = root.querySelector("[data-summary-pace]");
    const descriptionCounter = root.querySelector("[data-description-counter]");

    function selectedPaceLabel() {
      const selected = paceInputs.find((input) => input.checked);
      if (!selected) {
        return "Non defini";
      }
      const label = selected.closest("label");
      const title = label ? label.querySelector(".text-lg") : null;
      return title ? title.textContent.trim() : "Non defini";
    }

    function selectedCategoryLabel() {
      if (!(categorySelect instanceof HTMLSelectElement)) {
        return "Non selectionnee";
      }
      const option = categorySelect.selectedOptions[0];
      return option && option.value ? option.textContent.trim() : "Non selectionnee";
    }

    function syncSummary() {
      if (summaryTitle) {
        summaryTitle.textContent = titleInput && titleInput.value.trim() ? titleInput.value.trim() : "Non renseigne";
      }
      if (summaryCategory) {
        summaryCategory.textContent = selectedCategoryLabel();
      }
      if (summaryPace) {
        summaryPace.textContent = selectedPaceLabel();
      }
      if (descriptionCounter && descriptionInput) {
        descriptionCounter.textContent = `${descriptionInput.value.trim().length} caracteres`;
      }
    }

    function show(stepName) {
      const current = stepOrder.includes(stepName) ? stepName : stepOrder[0];
      steps.forEach((step) => {
        step.classList.toggle("hidden", step.getAttribute("data-onboarding-step") !== current);
      });

      const visualStepOrder = ["title", "description", "category", "availability"];
      const progressIndex = visualStepOrder.indexOf(current);
      const progress = progressIndex < 0 ? 0 : ((progressIndex + 1) / visualStepOrder.length) * 100;
      if (progressBar) {
        progressBar.style.width = `${progress}%`;
      }

      syncSummary();
      window.scrollTo({ top: 0, behavior: "smooth" });
    }

    root.querySelectorAll("[data-onboarding-next]").forEach((button) => {
      button.addEventListener("click", () => {
        show(String(button.getAttribute("data-onboarding-next") || stepOrder[0]));
      });
    });

    root.querySelectorAll("[data-onboarding-prev]").forEach((button) => {
      button.addEventListener("click", () => {
        show(String(button.getAttribute("data-onboarding-prev") || stepOrder[0]));
      });
    });

    if (titleInput) {
      titleInput.addEventListener("input", syncSummary);
    }
    if (descriptionInput) {
      descriptionInput.addEventListener("input", syncSummary);
    }
    if (categorySelect) {
      categorySelect.addEventListener("change", syncSummary);
    }
    paceInputs.forEach((input) => {
      input.addEventListener("change", syncSummary);
    });

    show(String(root.getAttribute("data-initial-step") || stepOrder[0] || "welcome"));
  });
})();
