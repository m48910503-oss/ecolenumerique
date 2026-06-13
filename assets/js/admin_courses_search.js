(function () {
  "use strict";

  function normalize(value) {
    return String(value || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .trim();
  }

  function initFilterTabs() {
    var tabs = document.querySelectorAll("[data-course-status-tab]");
    var cards = document.querySelectorAll("[data-course-card]");
    var searchInput = document.getElementById("admin-courses-inline-search");
    var categoryFilter = document.getElementById("admin-courses-category-filter");
    var emptyState = document.getElementById("admin-courses-empty-state");
    var activeStatus = "submitted";

    if (!tabs.length || !cards.length) {
      return;
    }

    function updateTabState(selectedTab) {
      tabs.forEach(function (tab) {
        var isActive = tab === selectedTab;
        tab.classList.toggle("text-primary-600", isActive);
        tab.classList.toggle("border-primary-600", isActive);
        tab.classList.toggle("dark:text-primary-400", isActive);
        tab.classList.toggle("dark:border-primary-400", isActive);
        tab.classList.toggle("text-gray-500", !isActive);
        tab.classList.toggle("dark:text-gray-400", !isActive);
        tab.classList.toggle("border-transparent", !isActive);
        tab.setAttribute("aria-pressed", isActive ? "true" : "false");
      });
    }

    function applyFilters() {
      var query = normalize(searchInput ? searchInput.value : "");
      var category = normalize(categoryFilter ? categoryFilter.value : "all");
      var visibleCount = 0;

      cards.forEach(function (card) {
        var status = String(card.getAttribute("data-status") || "");
        var cardCategory = normalize(card.getAttribute("data-category") || "");
        var haystack = normalize(card.getAttribute("data-search") || "");
        var statusMatches = status === activeStatus;
        var categoryMatches = category === "all" || cardCategory === category;
        var searchMatches = query === "" || haystack.indexOf(query) !== -1;
        var visible = statusMatches && categoryMatches && searchMatches;

        card.classList.toggle("hidden", !visible);
        if (visible) {
          visibleCount += 1;
        }
      });

      if (emptyState) {
        emptyState.classList.toggle("hidden", visibleCount > 0);
      }
    }

    tabs.forEach(function (tab) {
      tab.addEventListener("click", function () {
        activeStatus = String(tab.getAttribute("data-course-status-tab") || "submitted");
        updateTabState(tab);
        applyFilters();
      });
    });

    if (searchInput) {
      searchInput.addEventListener("input", applyFilters);
    }

    if (categoryFilter) {
      categoryFilter.addEventListener("change", applyFilters);
    }

    updateTabState(tabs[0]);
    applyFilters();
  }

  function initDetailModal() {
    var modal = document.getElementById("admin-course-detail-modal");
    var content = document.getElementById("admin-course-detail-content");
    if (!modal || !content) {
      return;
    }

    function closeModal() {
      modal.classList.add("hidden");
      modal.classList.remove("flex");
      content.innerHTML = "";
    }

    document.querySelectorAll("[data-course-detail]").forEach(function (button) {
      button.addEventListener("click", function () {
        var card = button.closest("[data-course-card]");
        if (!card) {
          return;
        }

        var title = card.getAttribute("data-title") || "Cours";
        var teacher = card.getAttribute("data-teacher") || "Enseignant";
        var category = card.getAttribute("data-category-label") || "Non renseignée";
        var price = card.getAttribute("data-price") || "-";
        var duration = card.getAttribute("data-duration") || "-";
        var level = card.getAttribute("data-level") || "-";
        var submitted = card.getAttribute("data-submitted") || "-";
        var description = card.getAttribute("data-description") || "Aucune description disponible.";
        var cover = card.getAttribute("data-cover") || "";
        var previewUrl = card.getAttribute("data-preview-url") || "#";

        content.innerHTML = [
          cover ? '<img src="' + escapeHtml(cover) + '" class="w-full h-48 object-cover rounded-xl mb-2" alt="">' : "",
          '<h2 class="text-2xl font-bold">' + escapeHtml(title) + "</h2>",
          '<div class="flex flex-wrap gap-2 text-xs"><span class="admin-course-mini admin-course-mini--teacher"><i class="fas fa-user"></i>Enseignant : ' + escapeHtml(teacher) + '</span><span class="admin-course-mini admin-course-mini--date"><i class="far fa-calendar"></i>Soumis : ' + escapeHtml(submitted) + "</span></div>",
          '<div class="grid gap-2 rounded-xl bg-gray-50 p-3 text-xs dark:bg-gray-700/30 sm:grid-cols-2"><span class="admin-course-chip admin-course-chip--category"><i class="fas fa-tag"></i>Catégorie : ' + escapeHtml(category) + '</span><span class="admin-course-chip admin-course-chip--price"><i class="fas fa-money-bill-wave"></i>Prix : ' + escapeHtml(price) + '</span><span class="admin-course-chip admin-course-chip--duration"><i class="fas fa-hourglass"></i>Durée : ' + escapeHtml(duration) + '</span><span class="admin-course-chip admin-course-chip--level"><i class="fas fa-signal"></i>Niveau : ' + escapeHtml(level) + "</span></div>",
          '<div><h4 class="font-semibold">Description complète :</h4><p class="text-gray-700 dark:text-gray-300">' + escapeHtml(description) + "</p></div>",
          '<a href="' + escapeHtml(previewUrl) + '" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700"><i class="fas fa-up-right-from-square"></i> Ouvrir l’aperçu complet</a>'
        ].join("");

        modal.classList.remove("hidden");
        modal.classList.add("flex");
      });
    });

    modal.querySelectorAll("[data-course-detail-close]").forEach(function (button) {
      button.addEventListener("click", closeModal);
    });

    modal.addEventListener("click", function (event) {
      if (event.target === modal) {
        closeModal();
      }
    });
  }

  function initRejectModal() {
    var modal = document.getElementById("admin-course-reject-modal");
    var courseIdInput = document.getElementById("admin-course-reject-id");
    var titleTarget = document.getElementById("admin-course-reject-title");
    var customReason = document.getElementById("admin-course-reject-custom");

    if (!modal || !courseIdInput) {
      return;
    }

    function closeModal() {
      modal.classList.add("hidden");
      modal.classList.remove("flex");
      courseIdInput.value = "";
      if (titleTarget) {
        titleTarget.textContent = "";
      }
      if (customReason) {
        customReason.value = "";
      }
    }

    document.querySelectorAll("[data-course-reject]").forEach(function (button) {
      button.addEventListener("click", function () {
        courseIdInput.value = String(button.getAttribute("data-course-id") || "");
        if (titleTarget) {
          titleTarget.textContent = String(button.getAttribute("data-course-title") || "");
        }
        modal.classList.remove("hidden");
        modal.classList.add("flex");
        if (customReason) {
          customReason.focus();
        }
      });
    });

    modal.querySelectorAll("[data-course-reject-close]").forEach(function (button) {
      button.addEventListener("click", closeModal);
    });

    modal.addEventListener("click", function (event) {
      if (event.target === modal) {
        closeModal();
      }
    });
  }

  function initStatsModal() {
    var modal = document.getElementById("admin-course-stats-modal");
    var titleTarget = document.getElementById("admin-course-stats-title");
    var cardsTarget = document.getElementById("admin-course-stats-cards");
    var chartTarget = document.getElementById("admin-course-stats-chart");

    if (!modal || !cardsTarget || !chartTarget) {
      return;
    }

    function closeModal() {
      modal.classList.add("hidden");
      modal.classList.remove("flex");
      if (titleTarget) {
        titleTarget.textContent = "";
      }
      cardsTarget.innerHTML = "";
      chartTarget.innerHTML = "";
    }

    document.querySelectorAll("[data-course-stats-open]").forEach(function (button) {
      button.addEventListener("click", function () {
        var card = button.closest("[data-course-card]");
        if (!card) {
          return;
        }

        var title = card.getAttribute("data-title") || "Cours";
        var teacher = card.getAttribute("data-teacher") || "Enseignant";
        var stats = parseStats(card.getAttribute("data-stats") || "{}");

        if (titleTarget) {
          titleTarget.textContent = title + " · " + teacher;
        }

        cardsTarget.innerHTML = renderStatsCards(stats);
        chartTarget.innerHTML = renderEnrollmentChart(stats.monthly_enrollments || []);
        modal.classList.remove("hidden");
        modal.classList.add("flex");
      });
    });

    modal.querySelectorAll("[data-course-stats-close]").forEach(function (button) {
      button.addEventListener("click", closeModal);
    });

    modal.addEventListener("click", function (event) {
      if (event.target === modal) {
        closeModal();
      }
    });
  }

  function escapeHtml(value) {
    return String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function parseStats(value) {
    try {
      var parsed = JSON.parse(String(value || "{}"));
      return parsed && typeof parsed === "object" ? parsed : {};
    } catch (_error) {
      return {};
    }
  }

  function asNumber(value) {
    var number = Number(value || 0);
    return Number.isFinite(number) ? number : 0;
  }

  function formatNumber(value) {
    try {
      return new Intl.NumberFormat("fr-FR").format(Math.max(0, Math.round(asNumber(value))));
    } catch (_error) {
      return String(Math.max(0, Math.round(asNumber(value))));
    }
  }

  function formatMoney(value) {
    return formatNumber(value) + " F CFA";
  }

  function formatRating(avg, count) {
    var total = Math.max(0, Math.round(asNumber(count)));
    if (total === 0) {
      return "Aucun avis";
    }
    return asNumber(avg).toFixed(1).replace(".", ",") + " / 5";
  }

  function renderStatsCards(stats) {
    var cards = [
      {
        label: "Élèves inscrits",
        value: formatNumber(stats.students),
        meta: "Inscriptions actives",
        icon: "fa-user-graduate",
        tone: "blue"
      },
      {
        label: "Revenus générés",
        value: formatMoney(stats.revenue_xof),
        meta: formatNumber(stats.paid_orders) + " commande(s) payée(s)",
        icon: "fa-wallet",
        tone: "green"
      },
      {
        label: "Note moyenne",
        value: formatRating(stats.avg_rating, stats.rating_count),
        meta: formatNumber(stats.rating_count) + " avis",
        icon: "fa-star",
        tone: "amber"
      },
      {
        label: "Taux de complétion",
        value: formatNumber(stats.completion_rate) + "%",
        meta: "Leçons terminées",
        icon: "fa-circle-check",
        tone: "indigo"
      }
    ];

    return cards.map(function (card) {
      return [
        '<article class="admin-course-stats-kpi admin-course-stats-kpi--' + card.tone + '">',
        '<div class="admin-course-stats-kpi__icon"><i class="fas ' + card.icon + '"></i></div>',
        '<p class="admin-course-stats-kpi__label">' + escapeHtml(card.label) + '</p>',
        '<p class="admin-course-stats-kpi__value">' + escapeHtml(card.value) + '</p>',
        '<p class="admin-course-stats-kpi__meta">' + escapeHtml(card.meta) + '</p>',
        "</article>"
      ].join("");
    }).join("");
  }

  function renderEnrollmentChart(rows) {
    var data = Array.isArray(rows) ? rows : [];
    if (!data.length) {
      return '<div class="admin-course-stats-empty"><i class="fas fa-chart-line"></i><span>Aucune inscription sur les six derniers mois.</span></div>';
    }

    var values = data.map(function (row) {
      return Math.max(0, Math.round(asNumber(row && row.count)));
    });
    var labels = data.map(function (row) {
      return escapeHtml(row && row.label ? row.label : "-");
    });
    var maxValue = Math.max.apply(null, values.concat([1]));
    var width = 760;
    var height = 270;
    var left = 46;
    var right = 22;
    var top = 24;
    var bottom = 42;
    var chartWidth = width - left - right;
    var chartHeight = height - top - bottom;
    var step = values.length > 1 ? chartWidth / (values.length - 1) : 0;
    var points = values.map(function (value, index) {
      var x = left + (step * index);
      var y = top + chartHeight - ((value / maxValue) * chartHeight);
      return { x: x, y: y, value: value };
    });
    var line = points.map(function (point) {
      return point.x.toFixed(2) + "," + point.y.toFixed(2);
    }).join(" ");
    var area = [
      left + "," + (top + chartHeight),
      line,
      (left + chartWidth) + "," + (top + chartHeight)
    ].join(" ");
    var grid = [];
    for (var i = 0; i <= 4; i += 1) {
      var y = top + ((chartHeight / 4) * i);
      var labelValue = Math.round(maxValue - ((maxValue / 4) * i));
      grid.push('<line x1="' + left + '" y1="' + y.toFixed(2) + '" x2="' + (left + chartWidth) + '" y2="' + y.toFixed(2) + '" class="admin-course-stats-grid-line"></line>');
      grid.push('<text x="' + (left - 12) + '" y="' + (y + 4).toFixed(2) + '" text-anchor="end" class="admin-course-stats-axis-label">' + labelValue + '</text>');
    }
    var dots = points.map(function (point) {
      return '<circle cx="' + point.x.toFixed(2) + '" cy="' + point.y.toFixed(2) + '" r="4.5" class="admin-course-stats-dot"><title>' + point.value + ' inscription(s)</title></circle>';
    }).join("");
    var xLabels = points.map(function (point, index) {
      return '<text x="' + point.x.toFixed(2) + '" y="' + (height - 12) + '" text-anchor="middle" class="admin-course-stats-axis-label">' + labels[index] + '</text>';
    }).join("");

    return [
      '<div class="admin-course-stats-chart-wrap">',
      '<svg viewBox="0 0 ' + width + " " + height + '" role="img" aria-label="Évolution des inscriptions sur six mois">',
      '<defs><linearGradient id="adminCourseStatsArea" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#3b82f6" stop-opacity="0.26"></stop><stop offset="100%" stop-color="#3b82f6" stop-opacity="0.02"></stop></linearGradient></defs>',
      grid.join(""),
      '<polygon points="' + area + '" fill="url(#adminCourseStatsArea)"></polygon>',
      '<polyline points="' + line + '" class="admin-course-stats-line"></polyline>',
      dots,
      xLabels,
      "</svg>",
      "</div>"
    ].join("");
  }

  function init() {
    initFilterTabs();
    initDetailModal();
    initStatsModal();
    initRejectModal();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
