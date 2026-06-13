(function () {
  const container = document.getElementById("certificate-container");
  const orientationButtons = Array.from(
    document.querySelectorAll("[data-certificate-orientation]")
  );
  const printButtons = Array.from(
    document.querySelectorAll("[data-certificate-print]")
  );

  function setOrientation(orientation) {
    if (!container) return;
    const next = orientation === "landscape" ? "landscape" : "portrait";
    container.classList.toggle("admin-certificate-card--portrait", next === "portrait");
    container.classList.toggle("admin-certificate-card--landscape", next === "landscape");

    orientationButtons.forEach((button) => {
      const isActive = button.getAttribute("data-certificate-orientation") === next;
      button.classList.toggle("is-active", isActive);
      button.setAttribute("aria-pressed", isActive ? "true" : "false");
    });
  }

  orientationButtons.forEach((button) => {
    button.addEventListener("click", () => {
      setOrientation(button.getAttribute("data-certificate-orientation") || "portrait");
    });
  });

  printButtons.forEach((button) => {
    button.addEventListener("click", () => {
      window.print();
    });
  });

  setOrientation("portrait");
})();
