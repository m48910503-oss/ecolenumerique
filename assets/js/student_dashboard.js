(function () {
  "use strict";

  var chartCanvas = document.getElementById("activityChart");
  var rangeSelect = document.getElementById("activityRange");
  var chartDataPayload = document.getElementById("student-dashboard-chart-data");
  var userMenus = document.querySelectorAll("[data-dashboard-user-menu]");

  var fallbackChartData = {
    week: {
      labels: ["Lun", "Mar", "Mer", "Jeu", "Ven", "Sam", "Dim"],
      values: [2.5, 3.0, 1.8, 4.2, 2.0, 1.5, 0.5],
      max: 4.5
    },
    month: {
      labels: ["Sem. 1", "Sem. 2", "Sem. 3", "Sem. 4"],
      values: [2.6, 3.1, 2.8, 4.1],
      max: 4.5
    }
  };

  var chartInstance = null;
  var currentRange = "week";

  var refreshTailwind = function () {
    if (typeof window.tailwind?.refresh === "function") {
      window.tailwind.refresh();
    }
  };

  var closeUserMenus = function () {
    userMenus.forEach(function (menu) {
      if (menu.hasAttribute("open")) {
        menu.removeAttribute("open");
      }
    });
  };

  var normalizeSeries = function (series, fallback) {
    var safeFallback = fallback || { labels: [], values: [], max: 4.5 };
    var labels = Array.isArray(series?.labels) && series.labels.length > 0
      ? series.labels.map(function (label) { return String(label); })
      : safeFallback.labels.slice();
    var values = Array.isArray(series?.values) && series.values.length > 0
      ? series.values.map(function (value) {
          var numeric = Number(value);
          return Number.isFinite(numeric) ? numeric : 0;
        })
      : safeFallback.values.slice();
    var maxValue = Number(series?.max);
    var normalizedMax = Number.isFinite(maxValue) && maxValue > 0
      ? maxValue
      : values.reduce(function (acc, value) {
          return Math.max(acc, Number(value) || 0);
        }, 0);

    return {
      labels: labels,
      values: values,
      max: Math.max(4.5, Math.ceil((normalizedMax + 0.25) * 2) / 2)
    };
  };

  var readChartData = function () {
    if (!chartDataPayload) {
      return fallbackChartData;
    }

    try {
      var parsed = JSON.parse(chartDataPayload.textContent || "{}");
      return {
        week: normalizeSeries(parsed?.week, fallbackChartData.week),
        month: normalizeSeries(parsed?.month, fallbackChartData.month)
      };
    } catch (_error) {
      return fallbackChartData;
    }
  };

  var chartData = readChartData();

  var chartColors = function () {
    var isDark = document.documentElement.classList.contains("dark");

    return {
      grid: isDark ? "rgba(148, 163, 184, 0.18)" : "rgba(0, 0, 0, 0.05)",
      ticks: isDark ? "#94a3b8" : "#64748b",
      tooltip: "#1e293b"
    };
  };

  var buildChartConfig = function (range) {
    var colors = chartColors();
    var payload = chartData[range] || chartData.week;
    var maxValue = Number(payload.max) > 0 ? Number(payload.max) : Math.max(4.5, Math.max.apply(Math, payload.values.concat([0])));

    return {
      type: "bar",
      data: {
        labels: payload.labels,
        datasets: [{
          label: "Heures",
          data: payload.values,
          backgroundColor: "#3b82f6",
          borderRadius: 8,
          barPercentage: 0.6,
          categoryPercentage: 0.72
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { backgroundColor: colors.tooltip }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: maxValue,
            ticks: {
              stepSize: 0.5,
              color: colors.ticks
            },
            grid: {
              color: colors.grid
            }
          },
          x: {
            ticks: {
              color: colors.ticks
            },
            grid: {
              display: false
            }
          }
        }
      }
    };
  };

  var renderChart = function (range) {
    currentRange = range === "month" ? "month" : "week";

    if (chartInstance) {
      chartInstance.destroy();
    }

    chartInstance = new Chart(chartCanvas.getContext("2d"), buildChartConfig(currentRange));
  };

  document.addEventListener("click", function (event) {
    userMenus.forEach(function (menu) {
      if (!(event.target instanceof Node) || !menu.contains(event.target)) {
        menu.removeAttribute("open");
      }
    });
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      closeUserMenus();
    }
  });

  if (!chartCanvas || typeof window.Chart === "undefined") {
    refreshTailwind();
    return;
  }

  renderChart(rangeSelect && rangeSelect.value === "month" ? "month" : "week");
  refreshTailwind();

  if (rangeSelect) {
    rangeSelect.addEventListener("change", function () {
      renderChart(rangeSelect.value);
    });
  }

  window.addEventListener("themechange", function () {
    renderChart(currentRange);
    refreshTailwind();
  });
})();
