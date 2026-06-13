(function () {
  "use strict";

  function initFaqAccordion() {
    var items = Array.prototype.slice.call(document.querySelectorAll(".faq-item"));

    if (!items.length) {
      return;
    }

    items.forEach(function (item) {
      item.addEventListener("toggle", function () {
        if (!item.open) {
          return;
        }

        items.forEach(function (other) {
          if (other !== item) {
            other.removeAttribute("open");
          }
        });
      });
    });
  }

  function initHomePage() {
    initFaqAccordion();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHomePage, { once: true });
  } else {
    initHomePage();
  }
})();
