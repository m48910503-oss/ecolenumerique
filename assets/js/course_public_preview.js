(() => {
  document.addEventListener("DOMContentLoaded", () => {
    const buttons = Array.from(document.querySelectorAll("[data-public-preview-play]"));
    const frame = document.getElementById("public-preview-player");
    if (!buttons.length || !frame) return;

    const onPreviewClick = (button) => {
      const openUrl = String(frame.getAttribute("data-preview-open-url") || "").trim();
      if (!openUrl) {
        return;
      }
      const current = String(frame.getAttribute("src") || "").trim();
      if (current !== openUrl) {
        frame.setAttribute("src", openUrl);
      }
      frame.scrollIntoView({ behavior: "smooth", block: "center" });
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
