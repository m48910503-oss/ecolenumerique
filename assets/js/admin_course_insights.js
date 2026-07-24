(function () {
  "use strict";

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

  function escapeHtml(value) {
    return String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function parseRows(value) {
    try {
      var parsed = JSON.parse(String(value || "[]"));
      return Array.isArray(parsed) ? parsed : [];
    } catch (_error) {
      return [];
    }
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
      grid.push('<text x="' + (left - 12) + '" y="' + (y + 4).toFixed(2) + '" text-anchor="end" class="admin-course-stats-axis-label">' + formatNumber(labelValue) + "</text>");
    }

    var dots = points.map(function (point) {
      return '<circle cx="' + point.x.toFixed(2) + '" cy="' + point.y.toFixed(2) + '" r="4.5" class="admin-course-stats-dot"><title>' + formatNumber(point.value) + ' inscription(s)</title></circle>';
    }).join("");
    var xLabels = points.map(function (point, index) {
      return '<text x="' + point.x.toFixed(2) + '" y="' + (height - 12) + '" text-anchor="middle" class="admin-course-stats-axis-label">' + labels[index] + "</text>";
    }).join("");

    return [
      '<div class="admin-course-stats-chart-wrap">',
      '<svg viewBox="0 0 ' + width + " " + height + '" role="img" aria-label="Évolution des inscriptions sur six mois">',
      '<defs><linearGradient id="adminCourseInsightsArea" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#3b82f6" stop-opacity="0.26"></stop><stop offset="100%" stop-color="#3b82f6" stop-opacity="0.02"></stop></linearGradient></defs>',
      grid.join(""),
      '<polygon points="' + area + '" fill="url(#adminCourseInsightsArea)"></polygon>',
      '<polyline points="' + line + '" class="admin-course-stats-line"></polyline>',
      dots,
      xLabels,
      "</svg>",
      "</div>"
    ].join("");
  }

  function init() {
    var chart = document.getElementById("admin-course-insights-chart");
    if (!chart) {
      return;
    }

    chart.innerHTML = renderEnrollmentChart(parseRows(chart.getAttribute("data-chart") || "[]"));
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
