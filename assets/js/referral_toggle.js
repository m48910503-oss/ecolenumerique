(() => {
  const toggle = document.querySelector("[data-referral-toggle]");
  const panel = document.querySelector("[data-referral-panel]");
  if (!toggle || !panel) {
    return;
  }

  const setDisabled = (disabled) => {
    panel.querySelectorAll("input, select, textarea, button").forEach((el) => {
      el.disabled = disabled;
    });
  };

  const sync = () => {
    const hidden = !toggle.checked;
    panel.classList.toggle("hidden", hidden);
    setDisabled(hidden);
  };

  toggle.addEventListener("change", sync);
  sync();
})();
