(function () {
  "use strict";

  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function initHomeCarousel() {
    const dataNode = document.getElementById("home-carousel-categories-data");
    const track = document.getElementById("carouselTrack");
    const container = document.getElementById("carouselContainer");

    if (!(track instanceof HTMLElement) || !(container instanceof HTMLElement)) {
      return;
    }

    let categoriesData = [];
    if (dataNode instanceof HTMLScriptElement) {
      try {
        categoriesData = JSON.parse(dataNode.textContent || "[]");
      } catch (_error) {
        categoriesData = [];
      }
    }

    let originalCount = 0;
    let buildFromData = false;
    if (Array.isArray(categoriesData) && categoriesData.length > 0) {
      originalCount = categoriesData.length;
      buildFromData = true;
    } else {
      categoriesData = [];
      const originalNodes = Array.from(track.children).filter(function (node) {
        return node instanceof HTMLElement;
      });
      originalCount = originalNodes.length;
      if (originalCount === 0) {
        return;
      }

      for (let i = 0; i < 2; i += 1) {
        originalNodes.forEach(function (node) {
          track.appendChild(node.cloneNode(true));
        });
      }
    }

    let scrollAmount = 0;
    let animationFrame = null;
    let isPaused = false;
    let singleSetWidth = 0;
    const speed = 1.2;

    const buildCategoryCards = function () {
      if (!buildFromData) {
        return;
      }
      let html = "";
      for (let i = 0; i < 3; i += 1) {
        categoriesData.forEach(function (category) {
          html += '<div class="flex-shrink-0 w-52 bg-white p-5 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all badge-animation">';
          html += '<div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 text-xl"><i class="fas ' + escapeHtml(category.icon || "fa-code") + '"></i></div>';
          html += '<h3 class="font-bold mt-3">' + escapeHtml(category.name || "Formation") + '</h3>';
          html += '<p class="text-gray-500 text-sm">' + escapeHtml(category.count || "") + "</p>";
          html += "</div>";
        });
      }
      track.innerHTML = html;
    };

    const updateSingleSetWidth = function () {
      const items = track.children;
      const lastOriginalItem = items[originalCount - 1];
      if (!(lastOriginalItem instanceof HTMLElement)) {
        singleSetWidth = 0;
        return;
      }

      singleSetWidth = lastOriginalItem.offsetLeft + lastOriginalItem.offsetWidth;
    };

    const step = function () {
      if (singleSetWidth <= 0) {
        updateSingleSetWidth();
        animationFrame = window.requestAnimationFrame(step);
        return;
      }

      if (!isPaused) {
        scrollAmount += speed;
        if (scrollAmount >= singleSetWidth) {
          scrollAmount -= singleSetWidth;
        }
        track.style.transform = "translateX(-" + scrollAmount + "px)";
      }

      animationFrame = window.requestAnimationFrame(step);
    };

    buildCategoryCards();
    updateSingleSetWidth();
    animationFrame = window.requestAnimationFrame(step);

    container.addEventListener("mouseenter", function () {
      isPaused = true;
    });
    container.addEventListener("mouseleave", function () {
      isPaused = false;
    });
    container.addEventListener("focusin", function () {
      isPaused = true;
    });
    container.addEventListener("focusout", function () {
      isPaused = false;
    });

    window.addEventListener("resize", function () {
      updateSingleSetWidth();
      if (singleSetWidth > 0) {
        scrollAmount = scrollAmount % singleSetWidth;
      } else {
        scrollAmount = 0;
      }
    });

    window.addEventListener("beforeunload", function () {
      if (animationFrame) {
        window.cancelAnimationFrame(animationFrame);
      }
    });
  }

  function initHomeFaq() {
    document.querySelectorAll(".faq-item").forEach(function (details) {
      if (!(details instanceof HTMLDetailsElement)) {
        return;
      }

      const icon = details.querySelector("summary i");
      if (!(icon instanceof HTMLElement)) {
        return;
      }

      if (details.open) {
        icon.style.transform = "rotate(180deg)";
      }

      details.addEventListener("toggle", function () {
        icon.style.transform = details.open ? "rotate(180deg)" : "rotate(0deg)";
      });
    });
  }

  const onReady = function () {
    initHomeCarousel();
    initHomeFaq();
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", onReady, { once: true });
  } else {
    onReady();
  }
})();
