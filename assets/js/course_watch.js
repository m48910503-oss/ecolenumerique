(function () {
  "use strict";

  function toArray(list) {
    return Array.prototype.slice.call(list || []);
  }

  document.addEventListener("DOMContentLoaded", function () {
    var tabs = toArray(document.querySelectorAll("[data-watch-tab]"));
    var quickTargets = toArray(document.querySelectorAll("[data-watch-tab-target]"));
    var panes = toArray(document.querySelectorAll("[data-watch-pane]"));
    var resourcesLists = toArray(document.querySelectorAll("[data-lesson-resources-list]"));
    var resourcesEmpty = toArray(document.querySelectorAll("[data-lesson-resources-empty]"));
    var resourcesCount = toArray(document.querySelectorAll("[data-lesson-resources-count]"));
    var resourcesData = {};

    var resourcesJson = document.getElementById("lesson-resources-map");
    if (resourcesJson) {
      try {
        resourcesData = JSON.parse(resourcesJson.textContent || "{}") || {};
      } catch (_error) {
        resourcesData = {};
      }
    }

    function setActiveTab(tabName) {
      tabs.forEach(function (tab) {
        var active = tab.getAttribute("data-watch-tab") === tabName;
        tab.setAttribute("aria-selected", active ? "true" : "false");
        tab.classList.toggle("border-primary-600", active);
        tab.classList.toggle("text-primary-600", active);
        tab.classList.toggle("dark:text-primary-400", active);
        tab.classList.toggle("border-transparent", !active);
        tab.classList.toggle("text-gray-500", !active);
        tab.classList.toggle("dark:text-gray-400", !active);
      });

      panes.forEach(function (pane) {
        pane.classList.toggle(
          "hidden",
          pane.getAttribute("data-watch-pane") !== tabName
        );
      });
    }

    function createResourceItem(resource) {
      var link = document.createElement("a");
      link.href = resource.url || "#";
      link.target = "_blank";
      link.rel = "noopener noreferrer";
      link.className =
        "group flex items-center gap-4 rounded-2xl border border-gray-200 bg-white px-4 py-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-950/55 dark:hover:border-primary-400";

      var icon = document.createElement("span");
      icon.className =
        "flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-600 to-cyan-500 text-white shadow-md shadow-primary-600/20";
      icon.innerHTML = '<i class="fas fa-' + (resource.icon || "link") + '"></i>';

      var body = document.createElement("div");
      body.className = "min-w-0 flex-1";

      var title = document.createElement("p");
      title.className = "truncate font-semibold text-gray-900 dark:text-white";
      title.textContent = resource.label || "Ressource";

      var meta = document.createElement("p");
      meta.className = "mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400";
      meta.textContent = resource.kind || "Document";

      var badge = document.createElement("span");
      badge.className = "inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300";
      badge.textContent = resource.kind || "Document";

      meta.textContent = "";
      meta.appendChild(badge);

      body.appendChild(title);
      body.appendChild(meta);

      var arrow = document.createElement("span");
      arrow.className =
        "inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-200 text-gray-400 transition group-hover:border-primary-300 group-hover:text-primary-600 dark:border-gray-700 dark:text-gray-500 dark:group-hover:border-primary-400 dark:group-hover:text-primary-300";
      arrow.innerHTML = '<i class="fas fa-arrow-up-right-from-square text-xs"></i>';

      link.appendChild(icon);
      link.appendChild(body);
      link.appendChild(arrow);
      return link;
    }

    function renderResourcesForLesson(lessonId) {
      if (!resourcesLists.length) {
        return;
      }

      var key = String(lessonId || "");
      var items = Array.isArray(resourcesData[key]) ? resourcesData[key] : [];

      resourcesCount.forEach(function (node) {
        node.textContent = String(items.length);
      });

      resourcesLists.forEach(function (list) {
        list.innerHTML = "";
        if (!items.length) {
          return;
        }
        var fragment = document.createDocumentFragment();
        items.forEach(function (item) {
          fragment.appendChild(createResourceItem(item || {}));
        });
        list.appendChild(fragment);
      });

      resourcesEmpty.forEach(function (node) {
        node.classList.toggle("hidden", items.length > 0);
      });
    }

    function openChapterForLesson(lessonId) {
      var item = document.querySelector(
        '.queue-item[data-lesson-id="' + String(lessonId || '') + '"]'
      );
      if (!item) {
        return;
      }

      var details = item.closest ? item.closest('details') : null;
      if (!details) {
        return;
      }

      toArray(document.querySelectorAll('aside details')).forEach(function (node) {
        if (node !== details) {
          node.open = false;
        }
      });
      details.open = true;
    }

    tabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        setActiveTab(tab.getAttribute("data-watch-tab") || "overview");
      });
    });

    quickTargets.forEach(function (trigger) {
      trigger.addEventListener("click", function (event) {
        event.preventDefault();
        setActiveTab(trigger.getAttribute("data-watch-tab-target") || "overview");
      });
    });

    window.addEventListener("lesson:change", function (event) {
      var lessonId = event && event.detail ? event.detail.lessonId : "";
      if (lessonId) {
        renderResourcesForLesson(lessonId);
        openChapterForLesson(lessonId);
      }
    });

    var initialLessonId = document.body.getAttribute("data-current-lesson-id") || "";
    if (!initialLessonId) {
      var mainPlayerCard = document.getElementById("main-player-card");
      if (mainPlayerCard && mainPlayerCard.dataset && mainPlayerCard.dataset.currentLessonId) {
        initialLessonId = mainPlayerCard.dataset.currentLessonId;
      }
    }

    setActiveTab("overview");
    if (initialLessonId) {
      renderResourcesForLesson(initialLessonId);
      openChapterForLesson(initialLessonId);
    }
  });
})();
