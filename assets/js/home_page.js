(function () {
  "use strict";

  function initCategoryCarousel() {
    var carousel = document.querySelector("[data-category-carousel]");
    var track = carousel ? carousel.querySelector("[data-category-carousel-track]") : null;
    var primaryGroup = carousel ? carousel.querySelector("[data-category-carousel-group]") : null;

    if (!(carousel instanceof HTMLElement) || !(track instanceof HTMLElement) || !(primaryGroup instanceof HTMLElement)) {
      return;
    }

    var groupWidth = 0;
    var speed = 48;
    var resizeObserver = null;

    function measure() {
      groupWidth = Math.max(
        primaryGroup.scrollWidth || 0,
        primaryGroup.offsetWidth || 0,
        primaryGroup.getBoundingClientRect().width || 0
      );

      if (groupWidth > 0) {
        track.style.setProperty("--home-carousel-shift", groupWidth + "px");
        track.style.setProperty("--home-carousel-duration", Math.max(18, groupWidth / speed) + "s");
      }
    }
    window.addEventListener("resize", measure, { passive: true });
    window.addEventListener("load", measure, { once: true });
    window.addEventListener("beforeunload", function () {
      if (resizeObserver) {
        resizeObserver.disconnect();
      }
    });

    if (typeof ResizeObserver !== "undefined") {
      resizeObserver = new ResizeObserver(measure);
      resizeObserver.observe(carousel);
      resizeObserver.observe(primaryGroup);
    }

    measure();
  }

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

  function initCategoryPicker() {
    var picker = document.querySelector("[data-home-category-picker]");
    var trigger = picker ? picker.querySelector("[data-home-category-trigger]") : null;
    var menu = picker ? picker.querySelector("[data-home-category-menu]") : null;
    var label = picker ? picker.querySelector("[data-home-category-label]") : null;
    var input = picker ? picker.querySelector("[data-home-category-input]") : null;
    var options = picker ? Array.prototype.slice.call(picker.querySelectorAll("[data-home-category-option]")) : [];

    if (!(picker instanceof HTMLElement) || !(trigger instanceof HTMLButtonElement) || !(menu instanceof HTMLElement) || !(label instanceof HTMLElement) || !(input instanceof HTMLInputElement) || !options.length) {
      return;
    }

    var isOpen = false;
    var focusTimer = 0;

    function getOptionValue(option) {
      return option ? String(option.getAttribute("data-category-value") || "") : "";
    }

    function getOptionLabel(option) {
      return option ? String(option.getAttribute("data-category-label") || option.textContent || "").trim() : "";
    }

    function getSelectedOption(value) {
      var normalized = String(value || "");
      for (var index = 0; index < options.length; index++) {
        if (getOptionValue(options[index]) === normalized) {
          return options[index];
        }
      }
      return options[0] || null;
    }

    function syncOptions(value) {
      var normalized = String(value || "");
      options.forEach(function (option) {
        var isActive = getOptionValue(option) === normalized;
        option.classList.toggle("is-active", isActive);
        option.setAttribute("aria-selected", isActive ? "true" : "false");
      });
    }

    function setValue(value) {
      var normalized = String(value || "");
      input.value = normalized;
      var selectedOption = getSelectedOption(normalized);
      label.textContent = getOptionLabel(selectedOption) || "Tous les cours";
      syncOptions(normalized);
    }

    function closeMenu() {
      if (!isOpen) {
        return;
      }
      if (focusTimer) {
        window.clearTimeout(focusTimer);
        focusTimer = 0;
      }
      isOpen = false;
      picker.classList.remove("is-open");
      menu.hidden = true;
      trigger.setAttribute("aria-expanded", "false");
    }

    function openMenu() {
      if (isOpen) {
        return;
      }
      isOpen = true;
      picker.classList.add("is-open");
      menu.hidden = false;
      trigger.setAttribute("aria-expanded", "true");

      var activeOption = getSelectedOption(input.value || "");
      if (activeOption && typeof activeOption.focus === "function") {
        if (focusTimer) {
          window.clearTimeout(focusTimer);
        }
        focusTimer = window.setTimeout(function () {
          if (isOpen) {
            activeOption.focus();
          }
          focusTimer = 0;
        }, 0);
      }
    }

    function toggleMenu() {
      if (isOpen) {
        closeMenu();
      } else {
        openMenu();
      }
    }

    trigger.addEventListener("click", function (event) {
      event.preventDefault();
      toggleMenu();
    });

    trigger.addEventListener("keydown", function (event) {
      if (event.key === "ArrowDown" || event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        openMenu();
      } else if (event.key === "Escape") {
        closeMenu();
      }
    });

    options.forEach(function (option) {
      option.addEventListener("click", function () {
        setValue(getOptionValue(option));
        closeMenu();
        trigger.focus();
      });

      option.addEventListener("keydown", function (event) {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          option.click();
        } else if (event.key === "Escape") {
          closeMenu();
          trigger.focus();
        }
      });
    });

    picker.addEventListener("focusout", function (event) {
      if (!event.relatedTarget || !picker.contains(event.relatedTarget)) {
        closeMenu();
      }
    });

    document.addEventListener("click", function (event) {
      if (!picker.contains(event.target)) {
        closeMenu();
      }
    });

    window.addEventListener("resize", closeMenu, { passive: true });

    var form = picker.closest("form");
    if (form instanceof HTMLFormElement) {
      form.addEventListener("submit", closeMenu);
    }

    setValue(input.value || "");
  }

  function applyAmbientSettings(item) {
    var durationValue = parseFloat(item.getAttribute("data-home-ambient-duration") || "");
    if (!Number.isNaN(durationValue) && durationValue > 0) {
      item.style.setProperty("--home-ambient-duration", durationValue + "s");
    }

    var delayValue = parseInt(item.getAttribute("data-home-ambient-delay") || "", 10);
    if (!Number.isNaN(delayValue) && delayValue > 0) {
      item.style.setProperty("--home-ambient-delay", delayValue + "ms");
    }
  }

  function initRevealAnimations() {
    var revealItems = Array.prototype.slice.call(document.querySelectorAll("[data-home-reveal]"));

    if (!revealItems.length) {
      return;
    }

    var prefersReducedMotion = typeof window.matchMedia === "function" && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    revealItems.forEach(function (item) {
      applyAmbientSettings(item);
      var delayValue = parseInt(item.getAttribute("data-home-delay") || "", 10);
      if (!Number.isNaN(delayValue) && delayValue > 0) {
        item.style.setProperty("--home-delay", delayValue + "ms");
      }
    });

    if (prefersReducedMotion || typeof IntersectionObserver === "undefined") {
      revealItems.forEach(function (item) {
        item.classList.add("is-visible");
        if (item.hasAttribute("data-home-ambient") && !prefersReducedMotion) {
          item.classList.add("is-ambient-active");
        }
      });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }

        entry.target.classList.add("is-visible");
        if (entry.target.hasAttribute("data-home-ambient")) {
          entry.target.classList.add("is-ambient-active");
        }
        observer.unobserve(entry.target);
      });
    }, {
      threshold: 0.14,
      rootMargin: "0px 0px -8% 0px",
    });

    revealItems.forEach(function (item) {
      observer.observe(item);
    });
  }

  function initAmbientAnimations() {
    var ambientItems = Array.prototype.slice.call(document.querySelectorAll("[data-home-ambient]:not([data-home-reveal])"));

    if (!ambientItems.length) {
      return;
    }

    var prefersReducedMotion = typeof window.matchMedia === "function" && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    ambientItems.forEach(function (item) {
      applyAmbientSettings(item);
    });

    if (prefersReducedMotion) {
      return;
    }

    if (typeof IntersectionObserver === "undefined") {
      ambientItems.forEach(function (item) {
        item.classList.add("is-ambient-active");
      });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }

        entry.target.classList.add("is-ambient-active");
        observer.unobserve(entry.target);
      });
    }, {
      threshold: 0.12,
      rootMargin: "0px 0px -8% 0px",
    });

    ambientItems.forEach(function (item) {
      observer.observe(item);
    });
  }

  function initFloatingActions() {
    var scrollTopButton = document.querySelector("[data-scroll-top]");
    var supportFab = document.querySelector("[data-support-fab]");

    if (!(scrollTopButton instanceof HTMLElement) && !(supportFab instanceof HTMLElement)) {
      return;
    }

    function syncFloatingActions() {
      var hasImportantScroll = window.scrollY > 620;

      if (scrollTopButton instanceof HTMLElement) {
        var shouldShowTop = hasImportantScroll;
        scrollTopButton.classList.toggle("opacity-0", !shouldShowTop);
        scrollTopButton.classList.toggle("translate-y-3", !shouldShowTop);
        scrollTopButton.classList.toggle("scale-95", !shouldShowTop);
        scrollTopButton.classList.toggle("pointer-events-none", !shouldShowTop);
      }

      if (supportFab instanceof HTMLElement) {
        var showSupport = window.innerWidth >= 640 || hasImportantScroll;
        supportFab.classList.toggle("opacity-0", !showSupport);
        supportFab.classList.toggle("translate-y-3", !showSupport);
        supportFab.classList.toggle("scale-95", !showSupport);
        supportFab.classList.toggle("pointer-events-none", !showSupport);
      }
    }

    if (scrollTopButton instanceof HTMLElement) {
      scrollTopButton.addEventListener("click", function () {
        window.scrollTo({ top: 0, behavior: "smooth" });
      });
    }

    window.addEventListener("resize", syncFloatingActions, { passive: true });
    window.addEventListener("scroll", syncFloatingActions, { passive: true });
    window.addEventListener("load", syncFloatingActions, { passive: true });
    syncFloatingActions();
  }

  function initHomePage() {
    initCategoryCarousel();
    initCategoryPicker();
    initRevealAnimations();
    initAmbientAnimations();
    initFaqAccordion();
    initFloatingActions();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHomePage, { once: true });
  } else {
    initHomePage();
  }
})();
