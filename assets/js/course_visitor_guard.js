(() => {
  function stopEvent(e) {
    try {
      e.preventDefault();
      e.stopPropagation();
    } catch (_) {
      // noop
    }
    return false;
  }

  function isProtectedShortcut(e) {
    const key = String(e.key || "").toLowerCase();
    const ctrlOrMeta = !!(e.ctrlKey || e.metaKey);
    if (!ctrlOrMeta) {
      return key === "f12";
    }
    return key === "c" || key === "x" || key === "s" || key === "u" || key === "a";
  }

  document.addEventListener("DOMContentLoaded", () => {
    const body = document.body;
    if (!body || body.getAttribute("data-preview-guard") !== "1") {
      return;
    }

    document.addEventListener("contextmenu", stopEvent, { capture: true });
    document.addEventListener("copy", stopEvent, { capture: true });
    document.addEventListener("cut", stopEvent, { capture: true });
    document.addEventListener("dragstart", stopEvent, { capture: true });
    document.addEventListener("selectstart", stopEvent, { capture: true });
    document.addEventListener(
      "keydown",
      (e) => {
        if (isProtectedShortcut(e)) {
          stopEvent(e);
        }
      },
      { capture: true }
    );
  });
})();
