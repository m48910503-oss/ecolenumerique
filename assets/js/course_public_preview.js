(() => {
  document.addEventListener("DOMContentLoaded", () => {
    const buttons = Array.from(document.querySelectorAll("[data-public-preview-play]"));
    const previewFrame = document.getElementById("public-preview-player");
    const previewCard = document.getElementById("preview-player-card") || document.getElementById("player-card");
    const playerWrapper = document.getElementById("player-wrapper");
    const playerToggle = document.getElementById("btn-play") || document.querySelector("[data-player-toggle]");
    if (!buttons.length) return;

    const onPreviewClick = (button) => {
      if (playerWrapper && playerToggle) {
        if (previewCard && previewCard.hidden) {
          previewCard.hidden = false;
          previewCard.classList.remove("hidden");
        }
        previewCard?.scrollIntoView({ behavior: "smooth", block: "center" });
        if (typeof playerToggle.click === "function") {
          window.setTimeout(() => playerToggle.click(), 100);
        }
        return;
      }

      if (!previewFrame) {
        return;
      }

      const openUrl = String(previewFrame.getAttribute("data-preview-open-url") || "").trim();
      if (!openUrl) {
        return;
      }
      const current = String(previewFrame.getAttribute("src") || "").trim();
      if (current !== openUrl) {
        previewFrame.setAttribute("src", openUrl);
      }
      previewFrame.scrollIntoView({ behavior: "smooth", block: "center" });
      buttons.forEach((btn) => {
        btn.setAttribute("aria-disabled", "true");
        btn.classList.add("opacity-70");
      });
    };

    buttons.forEach((button) => {
      button.addEventListener("click", () => onPreviewClick(button));
    });
  });
})();
